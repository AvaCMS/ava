<?php

declare(strict_types=1);

namespace Ava\Fields;

/**
 * Field Type Interface
 *
 * Defines the contract for all field types. Each field type knows how to:
 * - Validate values
 * - Render admin UI
 * - Transform values for storage/display
 * - Provide configuration schema
 */
interface FieldType
{
    /**
     * Get the field type name.
     */
    public function name(): string;

    /**
     * Get the display label for this type.
     */
    public function label(): string;

    /**
     * Get the configuration schema for this field type.
     *
     * Returns an array describing available options:
     * [
     *     'option_name' => [
     *         'type' => 'string|int|bool|array',
     *         'label' => 'Display Label',
     *         'description' => 'Help text',
     *         'default' => 'default value',
     *         'required' => false,
     *     ],
     * ]
     *
     * @return array<string, array{type: string, label: string, description?: string, default?: mixed, required?: bool}>
     */
    public function schema(): array;

    /**
     * Validate a value against this field type and its configuration.
     *
     * @param mixed $value The value to validate
     * @param array $config Field configuration
     * @return ValidationResult
     */
    public function validate(mixed $value, array $config): ValidationResult;

    /**
     * Transform a value for storage (YAML frontmatter).
     *
     * @param mixed $value The value to transform
     * @param array $config Field configuration
     * @return mixed The transformed value suitable for YAML
     */
    public function toStorage(mixed $value, array $config): mixed;

    /**
     * Transform a stored value for display/editing.
     *
     * @param mixed $value The stored value
     * @param array $config Field configuration
     * @return mixed The value suitable for editing
     */
    public function fromStorage(mixed $value, array $config): mixed;

    /**
     * Get the default value for this field type.
     *
     * @param array $config Field configuration
     * @return mixed
     */
    public function defaultValue(array $config): mixed;

    /**
     * Render the admin form field HTML.
     *
     * @param string $name Field name
     * @param mixed $value Current value
     * @param array $config Field configuration
     * @param array $context Additional context (csrf, admin_url, etc.)
     * @return string HTML for the form field
     */
    public function render(string $name, mixed $value, array $config, array $context = []): string;

    /**
     * Get JavaScript for this field type (for client-side validation/behavior).
     *
     * @return string JavaScript code (without <script> tags)
     */
    public function javascript(): string;
}
