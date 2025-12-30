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
        // Your site's display name (used in templates, feeds, etc.)
        'name' => 'My Ava Site',

        // Full URL where your site is hosted (no trailing slash)
        // Used for sitemaps, feeds, and absolute URL generation
        'base_url' => 'http://localhost:8000',

        // Timezone for dates and times
        // Use a standard timezone identifier from: https://www.php.net/manual/en/timezones.php
        // Examples: 'UTC', 'America/New_York', 'Europe/London', 'Asia/Tokyo', 'Australia/Sydney'
        'timezone' => 'UTC',

        // Locale for number/date formatting (uses PHP's setlocale)
        // Examples: 'en_GB', 'en_US', 'de_DE', 'fr_FR', 'ja_JP'
        'locale' => 'en_GB',
    ],

    // Paths (relative to AVA_ROOT) - you usually won't need to change these
    'paths' => [
        'content' => 'content',      // Where your Markdown files live
        'themes' => 'themes',        // Available themes
        'plugins' => 'plugins',      // Plugin directory
        'snippets' => 'snippets',    // PHP snippets for [snippet] shortcode
        'storage' => 'storage',      // Cache, logs, temp files

        // Path aliases for use in your Markdown content
        // Write @media:image.jpg instead of /media/image.jpg
        // Makes it easy to reorganize assets later without updating every file
        'aliases' => [
            '@media:' => '/media/',
            '@uploads:' => '/media/uploads/',
            '@assets:' => '/assets/',
        ],
    ],

    // Active theme
    'theme' => 'default',

    // Content Index
    // Ava builds an efficient binary index of your content for fast lookups.
    // This avoids re-parsing Markdown files on every request.
    'content_index' => [
        // 'auto'   - Rebuilds automatically when content changes (recommended for most sites)
        // 'never'  - Only rebuilds when you run ./ava rebuild (best for high-traffic production)
        // 'always' - Rebuilds on every request (slow! only for debugging)
        'mode' => 'auto',

        // Backend for storing the content index
        // 'array'  - Binary serialized arrays (default, works great for most sites)
        // 'sqlite' - SQLite database (opt-in for large sites with 10k+ items)
        'backend' => 'array',
    ],

    // Page Cache
    // Stores fully-rendered HTML pages for instant serving (~0.1ms vs ~30ms).
    // Pages are cached on first visit and cleared automatically on rebuild.
    'page_cache' => [
        'enabled' => true,             // Set to false to disable caching entirely

        'ttl' => null,                 // Cache lifetime in seconds (null = until next rebuild)

        // URL patterns that should never be cached (glob-style wildcards)
        'exclude' => [
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
            // Allow the [snippet] shortcode to execute PHP files from snippets/
            // Set to false if you don't use snippets or want to restrict this
            'allow_php_snippets' => true,
        ],

        // Secret token for previewing draft content
        // Access drafts via: /your-draft-url?preview=1&token=your-token-here
        // Use a long random string in production!
        'preview_token' => 'ava-preview-secret',
    ],

    // Admin Dashboard
    // A simple web UI for site health, content overview, and quick actions.
    // Not an editor—your files remain the source of truth.
    // Create users first with: ./ava user:add
    'admin' => [
        'enabled' => true,             // Set to false to disable the dashboard
        'path' => '/admin',            // URL path (e.g., /admin, /dashboard, /_ava)
    ],

    // Active plugins (in load order)
    'plugins' => [
        'sitemap',
        'feed',
        'redirects',
    ],
];
