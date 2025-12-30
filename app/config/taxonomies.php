<?php

declare(strict_types=1);

/**
 * Taxonomy Definitions
 *
 * Taxonomies let you organize content into groups.
 * Think of them as labels you can attach to posts, pages, or any content type.
 *
 * Examples: Categories, Tags, Authors, Series, Ingredients, Genres, etc.
 *
 * Docs: https://ava.addy.zone/#/configuration?id=taxonomies-taxonomiesphp
 */

return [
    // Categories - hierarchical (can have parent/child relationships)
    // Example: Tutorials > PHP > Beginners
    'category' => [
        'label' => 'Categories',
        'hierarchical' => true,          // Allow parent/child relationships
        'public' => true,                // Show on frontend (set false to hide)
        'rewrite' => [
            'base' => '/category',       // URL prefix: /category/tutorials
            'separator' => '/',          // For nested: /category/tutorials/php
        ],
        'behaviour' => [
            'allow_unknown_terms' => true,   // Auto-create terms used in content
            'hierarchy_rollup' => true,      // Include child terms when filtering by parent
        ],
        'ui' => [
            'show_counts' => true,       // Show item count next to terms
            'sort_terms' => 'name_asc',  // Sort alphabetically
        ],
    ],

    // Tags - flat (no hierarchy, just simple labels)
    'tag' => [
        'label' => 'Tags',
        'hierarchical' => false,         // Flat list, no parent/child
        'public' => true,
        'rewrite' => [
            'base' => '/tag',            // URL prefix: /tag/php
        ],
        'behaviour' => [
            'allow_unknown_terms' => true,
        ],
        'ui' => [
            'show_counts' => true,
            'sort_terms' => 'count_desc', // Most used first
        ],
    ],
];
