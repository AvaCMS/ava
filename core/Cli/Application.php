<?php

declare(strict_types=1);

namespace Ava\Cli;

use Ava\Application as AvaApp;
use Ava\Support\Ulid;
use Ava\Support\Str;

/**
 * CLI Application
 *
 * Handles command-line interface for Ava CMS.
 */
final class Application
{
    private AvaApp $app;
    private array $commands = [];

    public function __construct()
    {
        $this->app = AvaApp::getInstance();
        $this->registerCommands();
    }

    /**
     * Run the CLI application.
     */
    public function run(array $argv): int
    {
        $script = array_shift($argv);
        $command = array_shift($argv) ?? 'help';

        // Handle help
        if ($command === 'help' || $command === '--help' || $command === '-h') {
            $this->showHelp();
            return 0;
        }

        // Handle version
        if ($command === 'version' || $command === '--version' || $command === '-v') {
            $this->writeln('Ava CMS v' . AVA_VERSION);
            return 0;
        }

        // Find and run command
        if (!isset($this->commands[$command])) {
            $this->error("Unknown command: {$command}");
            $this->showHelp();
            return 1;
        }

        try {
            return $this->commands[$command]($argv);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return 1;
        }
    }

    /**
     * Register available commands.
     */
    private function registerCommands(): void
    {
        $this->commands['status'] = [$this, 'cmdStatus'];
        $this->commands['rebuild'] = [$this, 'cmdRebuild'];
        $this->commands['lint'] = [$this, 'cmdLint'];
        $this->commands['make'] = [$this, 'cmdMake'];
        $this->commands['prefix'] = [$this, 'cmdPrefix'];
        $this->commands['pages:clear'] = [$this, 'cmdPagesClear'];
        $this->commands['pages:stats'] = [$this, 'cmdPagesStats'];
        $this->commands['stress:generate'] = [$this, 'cmdStressGenerate'];
        $this->commands['stress:clean'] = [$this, 'cmdStressClean'];
        $this->commands['user:add'] = [$this, 'cmdUserAdd'];
        $this->commands['user:password'] = [$this, 'cmdUserPassword'];
        $this->commands['user:remove'] = [$this, 'cmdUserRemove'];
        $this->commands['user:list'] = [$this, 'cmdUserList'];
        $this->commands['update:check'] = [$this, 'cmdUpdateCheck'];
        $this->commands['update:apply'] = [$this, 'cmdUpdateApply'];
    }

    // =========================================================================
    // Commands
    // =========================================================================

    /**
     * Show site status.
     */
    private function cmdStatus(array $args): int
    {
        $this->writeln('');
        $this->writeln('=== Ava CMS Status ===');
        $this->writeln('');

        // PHP environment
        $this->writeln('PHP ' . PHP_VERSION);
        $extensions = [];
        if (extension_loaded('igbinary')) {
            $extensions[] = 'igbinary';
        }
        if (extension_loaded('opcache') && ini_get('opcache.enable')) {
            $extensions[] = 'opcache';
        }
        if (!empty($extensions)) {
            $this->writeln('Extensions: ' . implode(', ', $extensions));
        }
        $this->writeln('');

        // Site info
        $this->writeln('Site: ' . $this->app->config('site.name'));
        $this->writeln('URL:  ' . $this->app->config('site.base_url'));
        $this->writeln('');

        // Cache status
        $cachePath = $this->app->configPath('storage') . '/cache';
        $fingerprintPath = $cachePath . '/fingerprint.json';

        if (file_exists($fingerprintPath)) {
            $fingerprint = json_decode(file_get_contents($fingerprintPath), true);
            $fresh = $this->app->indexer()->isCacheFresh();

            $this->writeln('Cache:');
            $this->writeln('  Status: ' . ($fresh ? '✓ Fresh' : '✗ Stale'));
            $this->writeln('  Mode:   ' . $this->app->config('cache.mode', 'auto'));

            if (file_exists($cachePath . '/content_index.php')) {
                $mtime = filemtime($cachePath . '/content_index.php');
                $this->writeln('  Built:  ' . date('Y-m-d H:i:s', $mtime));
            }
        } else {
            $this->writeln('Cache: Not built');
        }
        $this->writeln('');

        // Content counts
        $this->writeln('Content:');
        $repository = $this->app->repository();

        foreach ($repository->types() as $type) {
            $total = $repository->count($type);
            $published = $repository->count($type, 'published');
            $drafts = $repository->count($type, 'draft');

            $this->writeln("  {$type}: {$total} total ({$published} published, {$drafts} drafts)");
        }
        $this->writeln('');

        // Taxonomies
        $this->writeln('Taxonomies:');
        foreach ($repository->taxonomies() as $taxonomy) {
            $terms = $repository->terms($taxonomy);
            $this->writeln("  {$taxonomy}: " . count($terms) . ' terms');
        }
        $this->writeln('');

        // Page cache stats
        $pageCache = $this->app->pageCache();
        $stats = $pageCache->stats();
        $this->writeln('Page Cache:');
        $this->writeln('  Status: ' . ($stats['enabled'] ? '✓ Enabled' : '✗ Disabled'));
        if ($stats['enabled']) {
            $ttl = $stats['ttl'] ?? null;
            $this->writeln('  TTL:    ' . ($ttl ? $ttl . 's' : 'Forever'));
            $this->writeln('  Pages:  ' . $stats['count'] . ' cached');
            if ($stats['count'] > 0) {
                $this->writeln('  Size:   ' . $this->formatBytes($stats['size']));
            }
        }
        $this->writeln('');

        return 0;
    }

    /**
     * Rebuild cache.
     */
    private function cmdRebuild(array $args): int
    {
        $this->writeln('Rebuilding cache...');

        $start = microtime(true);
        $this->app->indexer()->rebuild();
        $elapsed = round((microtime(true) - $start) * 1000);

        $this->success("Cache rebuilt in {$elapsed}ms");

        return 0;
    }

    /**
     * Lint content files.
     */
    private function cmdLint(array $args): int
    {
        $this->writeln('Validating content...');

        $errors = $this->app->indexer()->lint();

        if (empty($errors)) {
            $this->success('All content files are valid.');
            return 0;
        }

        $this->error('Found ' . count($errors) . ' error(s):');
        foreach ($errors as $error) {
            $this->writeln('  - ' . $error);
        }

        return 1;
    }

    /**
     * Create content of a specific type.
     */
    private function cmdMake(array $args): int
    {
        if (count($args) < 2) {
            $this->error('Usage: ava make <type> "Title"');
            $this->writeln('');
            $this->showAvailableTypes();
            return 1;
        }

        $type = array_shift($args);
        $title = implode(' ', $args);

        // Verify type exists
        $contentTypes = require $this->app->path('app/config/content_types.php');
        if (!isset($contentTypes[$type])) {
            $this->error("Unknown content type: {$type}");
            $this->showAvailableTypes();
            return 1;
        }

        $typeConfig = $contentTypes[$type];
        $extra = ['status' => 'draft'];

        // Add date for dated content types
        if (($typeConfig['sorting'] ?? 'manual') === 'date_desc') {
            $extra['date'] = date('Y-m-d');
        }

        return $this->createContent($type, $title, $extra);
    }

    /**
     * Show available content types.
     */
    private function showAvailableTypes(): void
    {
        $contentTypes = require $this->app->path('app/config/content_types.php');
        $this->writeln('Available types:');
        foreach ($contentTypes as $name => $config) {
            $label = $config['label'] ?? ucfirst($name);
            $this->writeln("  {$name} - {$label}");
        }
    }

    /**
     * Create a content file.
     */
    private function createContent(string $type, string $title, array $extra = []): int
    {
        // Load content type config
        $contentTypes = require $this->app->path('app/config/content_types.php');
        $typeConfig = $contentTypes[$type] ?? [];
        $contentDir = $typeConfig['content_dir'] ?? $type;

        // Generate slug and ID
        $slug = Str::slug($title);
        $id = Ulid::generate();

        // Build frontmatter
        $frontmatter = array_merge([
            'id' => $id,
            'title' => $title,
            'slug' => $slug,
        ], $extra);

        // Generate YAML
        $yaml = "---\n";
        foreach ($frontmatter as $key => $value) {
            if (is_array($value)) {
                $yaml .= "{$key}:\n";
                foreach ($value as $item) {
                    $yaml .= "  - {$item}\n";
                }
            } else {
                $yaml .= "{$key}: {$value}\n";
            }
        }
        $yaml .= "---\n\n";
        $yaml .= "Your content here.\n";

        // Determine file path
        $basePath = $this->app->configPath('content') . '/' . $contentDir;
        if (!is_dir($basePath)) {
            mkdir($basePath, 0755, true);
        }

        $filePath = $basePath . '/' . $slug . '.md';

        // Check if file exists
        if (file_exists($filePath)) {
            $this->error("File already exists: {$filePath}");
            return 1;
        }

        // Write file
        file_put_contents($filePath, $yaml);

        $this->success("Created: {$filePath}");
        $this->writeln("  ID: {$id}");
        $this->writeln("  Slug: {$slug}");

        return 0;
    }

    /**
     * Toggle date prefix on content filenames.
     */
    private function cmdPrefix(array $args): int
    {
        $action = $args[0] ?? null;
        $typeFilter = $args[1] ?? null;

        if (!in_array($action, ['add', 'remove'], true)) {
            $this->error('Usage: ava prefix <add|remove> [type]');
            $this->writeln('');
            $this->writeln('Examples:');
            $this->writeln('  ava prefix add post      # Add date prefix to posts');
            $this->writeln('  ava prefix remove post   # Remove date prefix from posts');
            $this->writeln('  ava prefix add           # Add to all dated types');
            return 1;
        }

        $contentTypes = require $this->app->path('app/config/content_types.php');
        $parser = new \Ava\Content\Parser();
        $renamed = 0;
        $skipped = 0;

        foreach ($contentTypes as $typeName => $typeConfig) {
            // Filter by type if specified
            if ($typeFilter !== null && $typeName !== $typeFilter) {
                continue;
            }

            $contentDir = $this->app->path('content/' . ($typeConfig['content_dir'] ?? $typeName));
            if (!is_dir($contentDir)) {
                continue;
            }

            $files = $this->findMarkdownFiles($contentDir);

            foreach ($files as $filePath) {
                $result = $this->processFilePrefix($filePath, $typeName, $parser, $action);
                if ($result === true) {
                    $renamed++;
                } elseif ($result === false) {
                    $skipped++;
                }
            }
        }

        if ($renamed > 0) {
            $this->success("Renamed {$renamed} file(s)");
            $this->writeln('Run "ava rebuild" to update the cache.');
        } else {
            $this->writeln('No files needed renaming.');
        }

        return 0;
    }

    /**
     * Process a single file for prefix add/remove.
     *
     * @return bool|null true=renamed, false=skipped, null=no action needed
     */
    private function processFilePrefix(string $filePath, string $type, \Ava\Content\Parser $parser, string $action): ?bool
    {
        try {
            $item = $parser->parseFile($filePath, $type);
        } catch (\Exception $e) {
            $this->warning("Skipping {$filePath}: " . $e->getMessage());
            return false;
        }

        $date = $item->date();
        if ($date === null) {
            // No date field, skip
            return null;
        }

        $dir = dirname($filePath);
        $filename = basename($filePath);
        $datePrefix = $date->format('Y-m-d') . '-';

        // Check current state
        $hasPrefix = preg_match('/^\d{4}-\d{2}-\d{2}-/', $filename);

        if ($action === 'add' && !$hasPrefix) {
            // Add date prefix
            $newFilename = $datePrefix . $filename;
            $newPath = $dir . '/' . $newFilename;

            if (file_exists($newPath)) {
                $this->warning("Cannot rename {$filename}: {$newFilename} already exists");
                return false;
            }

            rename($filePath, $newPath);
            $this->writeln("  {$filename} → {$newFilename}");
            return true;

        } elseif ($action === 'remove' && $hasPrefix) {
            // Remove date prefix
            $newFilename = preg_replace('/^\d{4}-\d{2}-\d{2}-/', '', $filename);
            $newPath = $dir . '/' . $newFilename;

            if (file_exists($newPath)) {
                $this->warning("Cannot rename {$filename}: {$newFilename} already exists");
                return false;
            }

            rename($filePath, $newPath);
            $this->writeln("  {$filename} → {$newFilename}");
            return true;
        }

        return null;
    }

    /**
     * Find all markdown files in a directory recursively.
     */
    private function findMarkdownFiles(string $dir): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'md') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    // =========================================================================
    // User commands
    // =========================================================================

    /**
     * Add a new user.
     */
    private function cmdUserAdd(array $args): int
    {
        if (count($args) < 2) {
            $this->error('Usage: ava user:add <email> <password> [name]');
            return 1;
        }

        $email = $args[0];
        $password = $args[1];
        $name = $args[2] ?? null;

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email address.');
            return 1;
        }

        if (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters.');
            return 1;
        }

        $usersFile = $this->app->path('app/config/users.php');
        $users = $this->loadUsers($usersFile);

        if (isset($users[$email])) {
            $this->error("User already exists: {$email}");
            return 1;
        }

        $users[$email] = [
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'name' => $name ?? explode('@', $email)[0],
            'created' => date('Y-m-d'),
        ];

        $this->saveUsers($usersFile, $users);

        $this->success("User created: {$email}");
        return 0;
    }

    /**
     * Update a user's password.
     */
    private function cmdUserPassword(array $args): int
    {
        if (count($args) < 2) {
            $this->error('Usage: ava user:password <email> <new-password>');
            return 1;
        }

        $email = $args[0];
        $password = $args[1];

        if (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters.');
            return 1;
        }

        $usersFile = $this->app->path('app/config/users.php');
        $users = $this->loadUsers($usersFile);

        if (!isset($users[$email])) {
            $this->error("User not found: {$email}");
            return 1;
        }

        $users[$email]['password'] = password_hash($password, PASSWORD_DEFAULT);
        $users[$email]['updated'] = date('Y-m-d');

        $this->saveUsers($usersFile, $users);

        $this->success("Password updated for: {$email}");
        return 0;
    }

    /**
     * Remove a user.
     */
    private function cmdUserRemove(array $args): int
    {
        if (count($args) < 1) {
            $this->error('Usage: ava user:remove <email>');
            return 1;
        }

        $email = $args[0];

        $usersFile = $this->app->path('app/config/users.php');
        $users = $this->loadUsers($usersFile);

        if (!isset($users[$email])) {
            $this->error("User not found: {$email}");
            return 1;
        }

        unset($users[$email]);

        $this->saveUsers($usersFile, $users);

        $this->success("User removed: {$email}");
        return 0;
    }

    /**
     * List all users.
     */
    private function cmdUserList(array $args): int
    {
        $usersFile = $this->app->path('app/config/users.php');
        $users = $this->loadUsers($usersFile);

        if (empty($users)) {
            $this->writeln('No users configured.');
            $this->writeln('');
            $this->writeln('Create one with: ava user:add <email> <password>');
            return 0;
        }

        $this->writeln('');
        $this->writeln('Users:');
        foreach ($users as $email => $data) {
            $name = $data['name'] ?? '';
            $created = $data['created'] ?? '';
            $this->writeln("  {$email} - {$name} (created: {$created})");
        }
        $this->writeln('');

        return 0;
    }

    /**
     * Load users from file.
     */
    private function loadUsers(string $file): array
    {
        if (!file_exists($file)) {
            return [];
        }
        return require $file;
    }

    /**
     * Save users to file.
     */
    private function saveUsers(string $file, array $users): void
    {
        $content = "<?php\n\ndeclare(strict_types=1);\n\n/**\n * Users Configuration\n *\n * Managed by CLI. Do not edit manually.\n */\n\nreturn " . var_export($users, true) . ";\n";
        file_put_contents($file, $content);
    }

    // =========================================================================
    // Update Commands
    // =========================================================================

    /**
     * Check for updates.
     */
    private function cmdUpdateCheck(array $args): int
    {
        $force = in_array('--force', $args) || in_array('-f', $args);

        $this->writeln('');
        $this->writeln('Checking for updates...');
        $this->writeln('');

        $updater = new \Ava\Updater($this->app);
        $result = $updater->check($force);

        $this->writeln('Current version: ' . $result['current']);
        $this->writeln('Latest version:  ' . $result['latest']);

        if ($result['error']) {
            $this->writeln('');
            $this->error($result['error']);
            return 1;
        }

        if ($result['available']) {
            $this->writeln('');
            $this->success('Update available!');
            $this->writeln('');

            if ($result['release']) {
                if ($result['release']['name']) {
                    $this->writeln('Release: ' . $result['release']['name']);
                }
                if ($result['release']['published_at']) {
                    $date = date('Y-m-d', strtotime($result['release']['published_at']));
                    $this->writeln('Published: ' . $date);
                }
                if ($result['release']['body']) {
                    $this->writeln('');
                    $this->writeln('Changelog:');
                    $this->writeln('----------');
                    // Show first 20 lines of changelog
                    $lines = explode("\n", $result['release']['body']);
                    foreach (array_slice($lines, 0, 20) as $line) {
                        $this->writeln($line);
                    }
                    if (count($lines) > 20) {
                        $this->writeln('... (truncated)');
                    }
                }
            }

            $this->writeln('');
            $this->writeln('Run `php bin/ava update:apply` to update.');

        } else {
            $this->writeln('');
            $this->success('You are running the latest version.');
        }

        if (isset($result['from_cache']) && $result['from_cache']) {
            $this->writeln('');
            $this->writeln('(cached result - use --force to refresh)');
        }

        $this->writeln('');
        return 0;
    }

    /**
     * Apply update.
     */
    private function cmdUpdateApply(array $args): int
    {
        $this->writeln('');

        // Check for available update first
        $updater = new \Ava\Updater($this->app);
        $check = $updater->check(true);

        if ($check['error']) {
            $this->error('Could not check for updates: ' . $check['error']);
            return 1;
        }

        if (!$check['available']) {
            $this->success('Already running the latest version (' . $check['current'] . ')');
            return 0;
        }

        $this->writeln('Update available: ' . $check['current'] . ' → ' . $check['latest']);
        $this->writeln('');

        // Confirm unless --yes flag
        if (!in_array('--yes', $args) && !in_array('-y', $args)) {
            $this->writeln('This will update the following:');
            $this->writeln('  - Core files (core/, bin/, bootstrap.php)');
            $this->writeln('  - Default theme (themes/default/)');
            $this->writeln('  - Bundled plugins (plugins/sitemap, feed, redirects)');
            $this->writeln('  - Documentation (docs/)');
            $this->writeln('');
            $this->writeln('These will NOT be modified:');
            $this->writeln('  - Your content (content/)');
            $this->writeln('  - Your configuration (app/)');
            $this->writeln('  - Your custom themes');
            $this->writeln('  - Your custom plugins');
            $this->writeln('  - Storage and cache files');
            $this->writeln('');
            echo 'Continue? [y/N]: ';
            $answer = trim(fgets(STDIN));
            if (strtolower($answer) !== 'y') {
                $this->writeln('Update cancelled.');
                return 0;
            }
            $this->writeln('');
        }

        $this->writeln('Downloading update...');

        $result = $updater->apply();

        if (!$result['success']) {
            $this->error($result['message']);
            return 1;
        }

        $this->success($result['message']);

        if (!empty($result['new_plugins'])) {
            $this->writeln('');
            $this->writeln('New bundled plugins available (not activated):');
            foreach ($result['new_plugins'] as $plugin) {
                $this->writeln('  - ' . $plugin);
            }
            $this->writeln('');
            $this->writeln('To activate, add them to your plugins array in app/config/ava.php');
        }

        $this->writeln('');
        $this->writeln('Rebuilding cache...');
        $this->app->indexer()->rebuild();
        $this->success('Cache rebuilt.');

        $this->writeln('');
        return 0;
    }

    // =========================================================================
    // Stress Testing
    // =========================================================================

    /**
     * Lorem ipsum words for generating dummy content.
     */
    private const LOREM_WORDS = [
        'lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit',
        'sed', 'do', 'eiusmod', 'tempor', 'incididunt', 'ut', 'labore', 'et', 'dolore',
        'magna', 'aliqua', 'enim', 'ad', 'minim', 'veniam', 'quis', 'nostrud',
        'exercitation', 'ullamco', 'laboris', 'nisi', 'aliquip', 'ex', 'ea', 'commodo',
        'consequat', 'duis', 'aute', 'irure', 'in', 'reprehenderit', 'voluptate',
        'velit', 'esse', 'cillum', 'fugiat', 'nulla', 'pariatur', 'excepteur', 'sint',
        'occaecat', 'cupidatat', 'non', 'proident', 'sunt', 'culpa', 'qui', 'officia',
        'deserunt', 'mollit', 'anim', 'id', 'est', 'laborum', 'perspiciatis', 'unde',
        'omnis', 'iste', 'natus', 'error', 'voluptatem', 'accusantium', 'doloremque',
        'laudantium', 'totam', 'rem', 'aperiam', 'eaque', 'ipsa', 'quae', 'ab', 'illo',
        'inventore', 'veritatis', 'quasi', 'architecto', 'beatae', 'vitae', 'dicta',
    ];

    /**
     * Generate dummy content for stress testing.
     */
    private function cmdStressGenerate(array $args): int
    {
        if (count($args) < 2) {
            $this->error('Usage: ava stress:generate <type> <count>');
            $this->writeln('');
            $this->writeln('Examples:');
            $this->writeln('  ava stress:generate post 100    # Generate 100 posts');
            $this->writeln('  ava stress:generate post 1000   # Generate 1000 posts');
            $this->writeln('');
            $this->showAvailableTypes();
            return 1;
        }

        $type = $args[0];
        $count = (int) $args[1];

        if ($count < 1 || $count > 10000) {
            $this->error('Count must be between 1 and 10000');
            return 1;
        }

        // Verify type exists
        $contentTypes = require $this->app->path('app/config/content_types.php');
        if (!isset($contentTypes[$type])) {
            $this->error("Unknown content type: {$type}");
            $this->showAvailableTypes();
            return 1;
        }

        $typeConfig = $contentTypes[$type];
        $contentDir = $typeConfig['content_dir'] ?? $type;
        $basePath = $this->app->configPath('content') . '/' . $contentDir;

        if (!is_dir($basePath)) {
            mkdir($basePath, 0755, true);
        }

        // Get taxonomies for this type
        $taxonomies = $typeConfig['taxonomies'] ?? [];
        $taxonomyTerms = $this->loadTaxonomyTerms($taxonomies);

        // Determine if content is dated
        $isDated = ($typeConfig['sorting'] ?? 'manual') === 'date_desc';

        $this->writeln("Generating {$count} dummy {$type}(s)...");
        $this->writeln('');

        $start = microtime(true);
        $created = 0;

        for ($i = 1; $i <= $count; $i++) {
            $result = $this->generateDummyContent($type, $basePath, $isDated, $taxonomies, $taxonomyTerms, $i);
            if ($result) {
                $created++;
                // Progress indicator
                if ($i % 100 === 0 || $i === $count) {
                    $this->writeln("  Created {$i}/{$count}...");
                }
            }
        }

        $elapsed = round((microtime(true) - $start) * 1000);

        $this->writeln('');
        $this->success("Generated {$created} dummy content files in {$elapsed}ms");
        $this->writeln('');
        $this->writeln('Rebuilding cache...');

        $rebuildStart = microtime(true);
        $this->app->indexer()->rebuild();
        $rebuildTime = round((microtime(true) - $rebuildStart) * 1000);

        $this->success("Cache rebuilt in {$rebuildTime}ms");
        $this->writeln('');
        $this->writeln('Run "ava stress:clean ' . $type . '" to remove generated content.');

        return 0;
    }

    /**
     * Clean up generated dummy content.
     */
    private function cmdStressClean(array $args): int
    {
        if (count($args) < 1) {
            $this->error('Usage: ava stress:clean <type>');
            $this->writeln('');
            $this->writeln('This will remove all content files with the _dummy- prefix.');
            return 1;
        }

        $type = $args[0];

        // Verify type exists
        $contentTypes = require $this->app->path('app/config/content_types.php');
        if (!isset($contentTypes[$type])) {
            $this->error("Unknown content type: {$type}");
            $this->showAvailableTypes();
            return 1;
        }

        $typeConfig = $contentTypes[$type];
        $contentDir = $typeConfig['content_dir'] ?? $type;
        $basePath = $this->app->configPath('content') . '/' . $contentDir;

        if (!is_dir($basePath)) {
            $this->writeln('No content directory found.');
            return 0;
        }

        // Find all dummy files
        $pattern = $basePath . '/_dummy-*.md';
        $files = glob($pattern);

        if (empty($files)) {
            $this->writeln('No dummy content files found.');
            return 0;
        }

        $count = count($files);
        $this->writeln("Found {$count} dummy content file(s).");
        echo 'Delete all? [y/N]: ';
        $answer = trim(fgets(STDIN));

        if (strtolower($answer) !== 'y') {
            $this->writeln('Cancelled.');
            return 0;
        }

        $deleted = 0;
        foreach ($files as $file) {
            if (unlink($file)) {
                $deleted++;
            }
        }

        $this->success("Deleted {$deleted} file(s)");

        $this->writeln('');
        $this->writeln('Rebuilding cache...');
        $this->app->indexer()->rebuild();
        $this->success('Cache rebuilt.');

        return 0;
    }

    /**
     * Clear page cache.
     */
    private function cmdPagesClear(array $args): int
    {
        $pageCache = $this->app->pageCache();

        if (!$pageCache->isEnabled()) {
            $this->writeln('Page cache is not enabled.');
            $this->writeln('Enable it in app/config/ava.php with page_cache.enabled = true');
            return 0;
        }

        $stats = $pageCache->stats();
        if ($stats['count'] === 0) {
            $this->writeln('Page cache is empty.');
            return 0;
        }

        $this->writeln("Found {$stats['count']} cached page(s).");

        // Check for pattern argument
        if (isset($args[0])) {
            $pattern = $args[0];
            $count = $pageCache->clearPattern($pattern);
            $this->success("Cleared {$count} page(s) matching: {$pattern}");
        } else {
            echo 'Clear all cached pages? [y/N]: ';
            $answer = trim(fgets(STDIN));

            if (strtolower($answer) !== 'y') {
                $this->writeln('Cancelled.');
                return 0;
            }

            $count = $pageCache->clear();
            $this->success("Cleared {$count} cached page(s)");
        }

        return 0;
    }

    /**
     * Show page cache statistics.
     */
    private function cmdPagesStats(array $args): int
    {
        $pageCache = $this->app->pageCache();
        $stats = $pageCache->stats();

        $this->writeln('');
        $this->writeln('=== Page Cache Stats ===');
        $this->writeln('');
        $this->writeln('Status:  ' . ($stats['enabled'] ? '✓ Enabled' : '✗ Disabled'));

        if (!$stats['enabled']) {
            $this->writeln('');
            $this->writeln('Enable page caching in app/config/ava.php:');
            $this->writeln("  'page_cache' => ['enabled' => true]");
            $this->writeln('');
            return 0;
        }

        $this->writeln('TTL:     ' . ($stats['ttl'] ? $stats['ttl'] . ' seconds' : 'Forever (until cleared)'));
        $this->writeln('');
        $this->writeln('Cached:  ' . $stats['count'] . ' page(s)');
        $this->writeln('Size:    ' . $this->formatBytes($stats['size']));

        if ($stats['oldest']) {
            $this->writeln('Oldest:  ' . $stats['oldest']);
            $this->writeln('Newest:  ' . $stats['newest']);
        }

        $this->writeln('');

        return 0;
    }

    /**
     * Generate a single dummy content file.
     */
    private function generateDummyContent(
        string $type,
        string $basePath,
        bool $isDated,
        array $taxonomies,
        array $taxonomyTerms,
        int $index
    ): bool {
        // Generate unique slug with _dummy- prefix for easy cleanup
        $uniqueId = bin2hex(random_bytes(4));
        $slug = "_dummy-{$index}-{$uniqueId}";
        $filePath = $basePath . '/' . $slug . '.md';

        // Skip if somehow exists
        if (file_exists($filePath)) {
            return false;
        }

        // Generate random title
        $titleWords = array_map('ucfirst', $this->randomWords(rand(3, 8)));
        $title = implode(' ', $titleWords);

        // Build frontmatter
        $frontmatter = [
            'id' => Ulid::generate(),
            'title' => $title,
            'slug' => $slug,
            'status' => $this->randomStatus(),
        ];

        // Add date for dated content (random date within last 2 years)
        if ($isDated) {
            $daysAgo = rand(0, 730);
            $date = date('Y-m-d', strtotime("-{$daysAgo} days"));
            $frontmatter['date'] = $date;
        }

        // Add random excerpt
        $frontmatter['excerpt'] = ucfirst(implode(' ', $this->randomWords(rand(10, 25)))) . '.';

        // Add random taxonomy terms
        foreach ($taxonomies as $taxonomy) {
            if (isset($taxonomyTerms[$taxonomy]) && !empty($taxonomyTerms[$taxonomy])) {
                $terms = $taxonomyTerms[$taxonomy];
                // Pick 1-3 random terms
                $numTerms = min(count($terms), rand(1, 3));
                shuffle($terms);
                $selectedTerms = array_slice($terms, 0, $numTerms);
                $frontmatter[$taxonomy] = $selectedTerms;
            }
        }

        // Generate YAML frontmatter
        $yaml = "---\n";
        foreach ($frontmatter as $key => $value) {
            if (is_array($value)) {
                $yaml .= "{$key}:\n";
                foreach ($value as $item) {
                    $yaml .= "  - {$item}\n";
                }
            } else {
                // Escape values that might need quoting
                if (is_string($value) && (str_contains($value, ':') || str_contains($value, '#'))) {
                    $value = '"' . addslashes($value) . '"';
                }
                $yaml .= "{$key}: {$value}\n";
            }
        }
        $yaml .= "---\n\n";

        // Generate random content (3-10 paragraphs)
        $numParagraphs = rand(3, 10);
        $content = '';
        for ($p = 0; $p < $numParagraphs; $p++) {
            // 3-8 sentences per paragraph
            $numSentences = rand(3, 8);
            $sentences = [];
            for ($s = 0; $s < $numSentences; $s++) {
                $sentence = ucfirst(implode(' ', $this->randomWords(rand(8, 20)))) . '.';
                $sentences[] = $sentence;
            }
            $content .= implode(' ', $sentences) . "\n\n";
        }

        // Add a heading occasionally
        if (rand(0, 2) === 0) {
            $headingWords = array_map('ucfirst', $this->randomWords(rand(2, 5)));
            $content = "## " . implode(' ', $headingWords) . "\n\n" . $content;
        }

        // Write file
        return file_put_contents($filePath, $yaml . $content) !== false;
    }

    /**
     * Get random words from lorem ipsum.
     */
    private function randomWords(int $count): array
    {
        $words = [];
        for ($i = 0; $i < $count; $i++) {
            $words[] = self::LOREM_WORDS[array_rand(self::LOREM_WORDS)];
        }
        return $words;
    }

    /**
     * Get random status (weighted towards published).
     */
    private function randomStatus(): string
    {
        return rand(1, 10) <= 8 ? 'published' : 'draft';
    }

    /**
     * Load taxonomy terms from definition files.
     */
    private function loadTaxonomyTerms(array $taxonomies): array
    {
        $result = [];
        $taxPath = $this->app->configPath('content') . '/_taxonomies';

        foreach ($taxonomies as $taxonomy) {
            $result[$taxonomy] = [];
            $file = $taxPath . '/' . $taxonomy . '.yml';

            if (file_exists($file)) {
                $content = file_get_contents($file);
                // Simple YAML parsing for term slugs
                if (preg_match_all('/^\s*-?\s*slug:\s*(\S+)/m', $content, $matches)) {
                    $result[$taxonomy] = $matches[1];
                }
            }

            // If no terms found, add some defaults
            if (empty($result[$taxonomy])) {
                $result[$taxonomy] = ['general', 'misc', 'other'];
            }
        }

        return $result;
    }

    // =========================================================================
    // Output helpers
    // =========================================================================

    private function showHelp(): void
    {
        $this->writeln('');
        $this->writeln('Ava CMS v' . AVA_VERSION . ' - Command Line Interface');
        $this->writeln('');
        $this->writeln('Usage:');
        $this->writeln('  php ava <command> [options] [arguments]');
        $this->writeln('');
        $this->writeln('Commands:');
        $this->writeln('  status         Show site status and cache info');
        $this->writeln('  rebuild        Rebuild all cache files');
        $this->writeln('  lint           Validate content files');
        $this->writeln('  make <type>    Create content of a specific type');
        $this->writeln('  prefix <add|remove> [type]  Toggle date prefix on filenames');
        $this->writeln('');
        $this->writeln('Page Cache:');
        $this->writeln('  pages:stats              Show page cache statistics');
        $this->writeln('  pages:clear [pattern]    Clear cached pages (all or by URL pattern)');
        $this->writeln('');
        $this->writeln('Stress Testing:');
        $this->writeln('  stress:generate <type> <count>  Generate dummy content for testing');
        $this->writeln('  stress:clean <type>             Remove all generated dummy content');
        $this->writeln('');
        $this->writeln('User Management:');
        $this->writeln('  user:add <email> <password> [name]  Create admin user');
        $this->writeln('  user:password <email> <password>    Update password');
        $this->writeln('  user:remove <email>                 Remove user');
        $this->writeln('  user:list                           List all users');
        $this->writeln('');
        $this->writeln('Updates:');
        $this->writeln('  update:check   Check for available updates');
        $this->writeln('  update:apply   Download and apply the latest update');
        $this->writeln('');
        $this->writeln('Examples:');
        $this->writeln('  php ava status');
        $this->writeln('  php ava make post "Hello World"');
        $this->writeln('  php ava update:check');
        $this->writeln('');
    }

    private function writeln(string $message): void
    {
        echo $message . "\n";
    }

    private function success(string $message): void
    {
        echo "\033[32m✓ {$message}\033[0m\n";
    }

    private function error(string $message): void
    {
        echo "\033[31m✗ {$message}\033[0m\n";
    }

    private function warning(string $message): void
    {
        echo "\033[33m⚠ {$message}\033[0m\n";
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $exp = floor(log($bytes) / log(1024));
        $exp = min($exp, count($units) - 1);

        return round($bytes / pow(1024, $exp), 1) . ' ' . $units[$exp];
    }
}
