<?php

declare(strict_types=1);

/**
 * Taxonomy Definitions
 *
 * Taxonomies organize content into groups (Categories, Tags, Authors, etc.).
 * Docs: https://ava.addy.zone/#/configuration?id=taxonomies-taxonomiesphp
 */

return [
    // Categories - hierarchical (parent/child relationships)
    'category' => [
        'label' => 'Categories',
        'hierarchical' => true,
        'public' => true,
        'rewrite' => [
            'base' => '/category',       // /category/tutorials
            'separator' => '/',          // /category/tutorials/php
        ],
        'behaviour' => [
            'allow_unknown_terms' => true,   // Auto-create terms from content
            'hierarchy_rollup' => true,      // Include child terms when filtering parent
        ],
        'ui' => [
            'show_counts' => true,
            'sort_terms' => 'name_asc',
        ],
    ],

    // Tags - flat list (no hierarchy)
    'tag' => [
        'label' => 'Tags',
        'hierarchical' => false,
        'public' => true,
        'rewrite' => [
            'base' => '/tag',            // /tag/php
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
