<?php

declare(strict_types=1);

namespace Ava\Content\Backends;

use Ava\Content\Item;

/**
 * Backend Interface
 *
 * Defines the contract for content index backends.
 * All backends must implement this interface to be swappable.
 *
 * The Query class uses these methods to retrieve content, allowing
 * seamless switching between array-based and SQLite backends.
 */
interface BackendInterface
{
    /**
     * Get the backend name for identification.
     */
    public function name(): string;

    /**
     * Check if the backend is available and properly initialized.
     */
    public function isAvailable(): bool;

    // -------------------------------------------------------------------------
    // Single Item Retrieval
    // -------------------------------------------------------------------------

    /**
     * Get a content item by type and slug.
     *
     * @param string $type Content type (e.g., 'post', 'page')
     * @param string $slug URL slug
     * @return array|null Raw item data or null if not found
     */
    public function getBySlug(string $type, string $slug): ?array;

    /**
     * Get a content item by ID.
     *
     * @param string $id Unique item ID (ULID)
     * @return array|null Raw item data or null if not found
     */
    public function getById(string $id): ?array;

    /**
     * Get a content item by relative file path.
     *
     * @param string $relativePath Path relative to content directory
     * @return array|null Raw item data or null if not found
     */
    public function getByPath(string $relativePath): ?array;

    // -------------------------------------------------------------------------
    // Bulk Retrieval
    // -------------------------------------------------------------------------

    /**
     * Get all raw item data for a content type.
     *
     * @param string $type Content type
     * @return array<array> Array of raw item data
     */
    public function allRaw(string $type): array;

    /**
     * Get all content types that have items.
     *
     * @return array<string> Array of content type names
     */
    public function types(): array;

    /**
     * Get count of items by type, optionally filtered by status.
     *
     * @param string $type Content type
     * @param string|null $status Optional status filter
     * @return int Number of items
     */
    public function count(string $type, ?string $status = null): int;

    /**
     * Check if a content item exists.
     *
     * @param string $type Content type
     * @param string $slug URL slug
     * @return bool Whether the item exists
     */
    public function exists(string $type, string $slug): bool;

    // -------------------------------------------------------------------------
    // Query Operations
    // -------------------------------------------------------------------------

    /**
     * Execute a query and return matching items.
     *
     * This is the main query method used by Query class. It should handle
     * filtering, sorting, and pagination efficiently based on the backend.
     *
     * @param array $params Query parameters:
     *   - type: string|null - Content type filter
     *   - status: string|null - Status filter
     *   - taxonomies: array - Taxonomy filters [taxonomy => term]
     *   - fields: array - Field filters [{field, value, operator}]
     *   - search: string|null - Search query
     *   - orderBy: string - Field to sort by
     *   - order: string - Sort direction (asc/desc)
     *   - page: int - Page number (1-based)
     *   - perPage: int - Items per page
     * @return array{items: array, total: int} Matching items and total count
     */
    public function query(array $params): array;

    // -------------------------------------------------------------------------
    // Recent Cache Operations
    // -------------------------------------------------------------------------

    /**
     * Check if a query can be served from fast cache.
     *
     * For simple queries (published, date desc, no filters), backends may
     * have optimized paths that are faster than a full query.
     *
     * @param string $type Content type
     * @param int $page Page number
     * @param int $perPage Items per page
     * @return bool Whether fast cache can be used
     */
    public function canUseFastCache(string $type, int $page, int $perPage): bool;

    /**
     * Get recent items using fast cache path.
     *
     * @param string $type Content type
     * @param int $page Page number
     * @param int $perPage Items per page
     * @return array{items: array, total: int} Recent items and total count
     */
    public function getRecentItems(string $type, int $page, int $perPage): array;

    // -------------------------------------------------------------------------
    // Taxonomy Operations
    // -------------------------------------------------------------------------

    /**
     * Get all terms for a taxonomy.
     *
     * @param string $taxonomy Taxonomy name
     * @return array<string, array> Terms indexed by slug
     */
    public function terms(string $taxonomy): array;

    /**
     * Get a specific taxonomy term.
     *
     * @param string $taxonomy Taxonomy name
     * @param string $slug Term slug
     * @return array|null Term data or null if not found
     */
    public function term(string $taxonomy, string $slug): ?array;

    /**
     * Get all taxonomy names.
     *
     * @return array<string> Array of taxonomy names
     */
    public function taxonomies(): array;

    // -------------------------------------------------------------------------
    // Route Operations
    // -------------------------------------------------------------------------

    /**
     * Get the full routes index.
     *
     * @return array Routes data structure
     */
    public function routes(): array;

    // -------------------------------------------------------------------------
    // Cache Management
    // -------------------------------------------------------------------------

    /**
     * Clear any in-memory cached data.
     */
    public function clearMemoryCache(): void;
}
