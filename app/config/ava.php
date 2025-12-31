<?php

declare(strict_types=1);

/**
 * Ava CMS Main Configuration
 *
 * This file returns the core configuration array.
 * All paths are relative to AVA_ROOT unless otherwise noted.
 * Docs: https://ava.addy.zone/#/configuration?id=main-settings-avaphp
 */

return [
    // Site settings
    'site' => [
        'name' => 'My Ava Site',                    // Display name for templates/feeds
        'base_url' => 'http://localhost:8000',      // Full URL (no trailing slash)
        'timezone' => 'UTC',                        // See: https://www.php.net/timezones
        'locale' => 'en_GB',                        // See: https://www.php.net/setlocale
    ],

    // Paths (rarely need to change these)
    'paths' => [
        'content' => 'content',
        'themes' => 'themes',
        'plugins' => 'plugins',
        'snippets' => 'snippets',
        'storage' => 'storage',

        // Path aliases - use @media:file.jpg in content instead of /media/file.jpg
        'aliases' => [
            '@media:' => '/media/',
        ],
    ],

    // Active theme
    'theme' => 'default',

    // Content Index - builds binary cache for fast lookups
    'content_index' => [
        'mode' => 'auto',           // auto/never/always
        'backend' => 'array',       // array/sqlite (sqlite for 10k+ items)
        'use_igbinary' => true,     // ~5x faster cache serialization
    ],

    // Page Cache - stores rendered HTML (~0.1ms vs ~30ms)
    'page_cache' => [
        'enabled' => true,
        'ttl' => null,              // Seconds (null = until rebuild)
        'exclude' => [              // Never cache these URL patterns
            '/api/*',
            '/preview/*',
        ],
    ],

    // Routing
    'routing' => [
        'trailing_slash' => false,
    ],

    // Content parsing
    'content' => [
        'frontmatter' => [
            'format' => 'yaml',
        ],
        'markdown' => [
            'allow_html' => true,
        ],
        'id' => [
            // ulid or uuid7
            'type' => 'ulid',
        ],
    ],

    // Security
    'security' => [
        'shortcodes' => [
            'allow_php_snippets' => true,   // Allow [snippet] to execute PHP
        ],
        'preview_token' => 'ava-preview-secret',    // For /page?preview=1&token=...
    ],

    // Admin Dashboard - web UI for site overview (create users: ./ava user:add)
    'admin' => [
        'enabled' => true,
        'path' => '/admin',
    ],

    // Active plugins
    'plugins' => [
        'sitemap',
        'feed',
        'redirects',
    ],

    'cli' => [
        'colors' => true,           // Colored terminal output
    ],

    'logs' => [
        'max_size' => 10 * 1024 * 1024,     // 10MB before rotation
        'max_files' => 3,                   // Keep 3 rotated files
    ],

    'debug' => [
        'enabled' => false,
        'display_errors' => false,  // NEVER true in production!
        'log_errors' => true,
        'level' => 'errors',        // all/errors/none
    ],
];
