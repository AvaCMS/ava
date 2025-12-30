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
        'name' => 'My Ava Site',
        'base_url' => 'http://localhost:8000',
        'timezone' => 'UTC',
        'locale' => 'en_GB',
    ],

    // Paths (relative to AVA_ROOT)
    'paths' => [
        'content' => 'content',
        'themes' => 'themes',
        'plugins' => 'plugins',
        'snippets' => 'snippets',
        'storage' => 'storage',

        // Path aliases for content references
        'aliases' => [
            '@media:' => '/media/',
            '@uploads:' => '/media/uploads/',
            '@assets:' => '/assets/',
        ],
    ],

    // Active theme
    'theme' => 'default',

    // Content Index - binary index of content metadata (routes, frontmatter, taxonomies)
    // This index is rebuilt when content files change
    'content_index' => [
        // auto: rebuild when content fingerprint changes
        // always: rebuild on every request (debugging only)
        // never: only rebuild via CLI (production)
        'mode' => 'auto',
    ],

    // Page Cache - stores rendered HTML for instant serving
    'page_cache' => [
        // Enable/disable HTML page caching
        'enabled' => true,

        // Time-to-live in seconds (null = forever, cleared on rebuild)
        'ttl' => null,

        // URL patterns to exclude from caching (glob-style)
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
            'allow_php_snippets' => true,
        ],
        // Token for previewing draft content via ?preview=1&token=xxx
        'preview_token' => 'ava-preview-secret',
    ],

    // Admin settings (disabled by default)
    'admin' => [
        'enabled' => true,
        'path' => '/admin',
    ],

    // Active plugins (in load order)
    'plugins' => [
        'sitemap',
        'feed',
        'redirects',
    ],
];
