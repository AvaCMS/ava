<?php

declare(strict_types=1);

namespace Ava\Fields;

/**
 * Field Registry
 *
 * Central registry for all field types. Manages field type registration,
 * instantiation, and configuration resolution.
 */
final class FieldRegistry
{
    /** @var array<string, class-string<FieldType>> */
    private array $types = [];

    /** @var array<string, FieldType> Cached field type instances */
    private array $instances = [];

    public function __construct()
    {
        $this->registerBuiltInTypes();
    }

    /**
     * Register built-in field types.
     */
    private function registerBuiltInTypes(): void
    {
        $this->register('text', Types\TextField::class);
        $this->register('textarea', Types\TextareaField::class);
        $this->register('number', Types\NumberField::class);
        $this->register('checkbox', Types\CheckboxField::class);
        $this->register('select', Types\SelectField::class);
        $this->register('date', Types\DateField::class);
        $this->register('color', Types\ColorField::class);
        $this->register('file', Types\FileField::class);
        $this->register('image', Types\ImageField::class);
        $this->register('gallery', Types\GalleryField::class);
        $this->register('array', Types\ArrayField::class);
        $this->register('content', Types\ContentField::class);
        
        // Built-in system fields for existing options
        $this->register('status', Types\StatusField::class);
        $this->register('template', Types\TemplateField::class);
        $this->register('taxonomy', Types\TaxonomyField::class);
    }

    /**
     * Register a field type.
     *
     * @param string|FieldType $nameOrType The field type name or instance
     * @param class-string<FieldType>|null $class The field type class (if $nameOrType is string)
     */
    public function register(string|FieldType $nameOrType, ?string $class = null): void
    {
        if ($nameOrType instanceof FieldType) {
            // Register instance directly
            $name = $nameOrType->name();
            $this->instances[$name] = $nameOrType;
            $this->types[$name] = get_class($nameOrType);
        } else {
            // Register by name and class
            $this->types[$nameOrType] = $class;
            unset($this->instances[$nameOrType]); // Clear cached instance
        }
    }

    /**
     * Get a field type instance.
     */
    public function get(string $name): ?FieldType
    {
        if (!isset($this->types[$name])) {
            return null;
        }

        if (!isset($this->instances[$name])) {
            $class = $this->types[$name];
            $this->instances[$name] = new $class();
        }

        return $this->instances[$name];
    }

    /**
     * Check if a field type is registered.
     */
    public function has(string $name): bool
    {
        return isset($this->types[$name]);
    }

    /**
     * Get all registered field type names.
     *
     * @return array<string>
     */
    public function types(): array
    {
        return array_keys($this->types);
    }

    /**
     * Get all registered field types.
     *
     * @return array<string, FieldType>
     */
    public function getAll(): array
    {
        $all = [];
        foreach ($this->types() as $name) {
            $all[$name] = $this->get($name);
        }
        return $all;
    }

    /**
     * Create a Field instance from configuration.
     */
    public function createField(string $name, array $config): ?Field
    {
        $typeName = $config['type'] ?? 'text';
        $type = $this->get($typeName);

        if ($type === null) {
            return null;
        }

        return new Field($name, $type, $config);
    }
}
