<?php

declare(strict_types=1);

namespace Ava\Fields;

/**
 * Field Instance
 *
 * Represents a configured field with its type and options.
 * Created from content type field definitions.
 */
final class Field
{
    private string $name;
    private FieldType $type;
    private array $config;

    public function __construct(string $name, FieldType $type, array $config)
    {
        $this->name = $name;
        $this->type = $type;
        $this->config = $config;
    }

    /**
     * Get the field name (key in frontmatter).
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Get the display label.
     */
    public function label(): string
    {
        return $this->config['label'] ?? ucfirst(str_replace(['_', '-'], ' ', $this->name));
    }

    /**
     * Get the description/help text.
     */
    public function description(): ?string
    {
        return $this->config['description'] ?? null;
    }

    /**
     * Check if the field is required.
     */
    public function required(): bool
    {
        return (bool) ($this->config['required'] ?? false);
    }

    /**
     * Get the placeholder text.
     */
    public function placeholder(): ?string
    {
        return $this->config['placeholder'] ?? null;
    }

    /**
     * Get the default value.
     */
    public function defaultValue(): mixed
    {
        if (array_key_exists('default', $this->config)) {
            return $this->config['default'];
        }
        return $this->type->defaultValue($this->config);
    }

    /**
     * Get the field type.
     */
    public function type(): FieldType
    {
        return $this->type;
    }

    /**
     * Get the type name.
     */
    public function typeName(): string
    {
        return $this->type->name();
    }

    /**
     * Get the full configuration.
     */
    public function config(): array
    {
        return $this->config;
    }

    /**
     * Get a specific configuration option.
     */
    public function option(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Validate a value.
     */
    public function validate(mixed $value): ValidationResult
    {
        // Check required first
        if ($this->required() && $this->isEmpty($value)) {
            return ValidationResult::error($this->label() . ' is required.');
        }

        // Skip type validation if empty and not required
        if ($this->isEmpty($value)) {
            return ValidationResult::success();
        }

        return $this->type->validate($value, $this->config);
    }

    /**
     * Check if a value is considered empty.
     */
    private function isEmpty(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }
        if (is_string($value) && trim($value) === '') {
            return true;
        }
        if (is_array($value) && count($value) === 0) {
            return true;
        }
        return false;
    }

    /**
     * Transform for storage.
     */
    public function toStorage(mixed $value): mixed
    {
        return $this->type->toStorage($value, $this->config);
    }

    /**
     * Transform from storage.
     */
    public function fromStorage(mixed $value): mixed
    {
        return $this->type->fromStorage($value, $this->config);
    }

    /**
     * Render the form field HTML.
     */
    public function render(mixed $value, array $context = []): string
    {
        return $this->type->render($this->name, $value, $this->config, $context);
    }

    /**
     * Get JavaScript for the field type.
     */
    public function javascript(): string
    {
        return $this->type->javascript();
    }

    /**
     * Convert to array for JSON serialization.
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type->name(),
            'label' => $this->label(),
            'description' => $this->description(),
            'required' => $this->required(),
            'placeholder' => $this->placeholder(),
            'default' => $this->defaultValue(),
            'config' => $this->config,
        ];
    }
}
