<?php

declare(strict_types=1);

/**
 * Ava CMS Main Configuration
 *
 * This file returns the core configuration array.
 * All paths are relative to AVA_ROOT unless otherwise noted.
 */

return [
    // Site settings
    'site' => [
        'name' => 'My Ava Site',
        'base_url' => 'https://ava-dev.addy.zone',
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

    // Cache settings
    'cache' => [
        // auto: rebuild when fingerprint changes
        // always: rebuild on every request (dev)
        // never: only rebuild via CLI (prod)
        'mode' => 'auto',
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
