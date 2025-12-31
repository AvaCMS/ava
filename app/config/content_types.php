<?php

declare(strict_types=1);

/**
 * Content Type Definitions
 *
 * Each content type defines how content is organized, routed, and rendered.
 * Docs: https://ava.addy.zone/#/configuration?id=content-types-content_typesphp
 */

return [
    // Pages - hierarchical URLs mirror folder structure
    'page' => [
        'label' => 'Pages',
        'content_dir' => 'pages',
        'url' => [
            'type' => 'hierarchical',    // content/pages/about.md → /about
            'base' => '/',
        ],
        'templates' => [
            'single' => 'page.php',
        ],
        'taxonomies' => [],
        'fields' => [],
        'sorting' => 'manual',
        'search' => [
            'enabled' => true,
            'fields' => ['title', 'body'],
        ],
    ],

    // Posts - dated content with pattern-based URLs
    'post' => [
        'label' => 'Posts',
        'content_dir' => 'posts',
        'url' => [
            'type' => 'pattern',
            'pattern' => '/blog/{slug}',  // content/posts/hello.md → /blog/hello
            'archive' => '/blog',        // Posts listing page
        ],
        'templates' => [
            'single' => 'post.php',
            'archive' => 'archive.php',
        ],
        'taxonomies' => ['category', 'tag'],
        'fields' => [],
        'sorting' => 'date_desc',
        'search' => [
            'enabled' => true,
            'fields' => ['title', 'excerpt', 'body'],
            // Optional: Custom search weights (see docs for defaults)
            // 'weights' => ['title_phrase' => 80, 'body_token' => 2, ...],
        ],
        // Optional: Extra fields for archive cache (id, title, date always included)
        // 'cache_fields' => ['author', 'featured_image'],
    ],
];
