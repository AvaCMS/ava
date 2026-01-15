<?php

declare(strict_types=1);

namespace Ava\Tests\Fields;

use Ava\Fields\FieldRegistry;
use Ava\Fields\Field;
use Ava\Testing\TestCase;

/**
 * Tests for the Field Registry.
 */
final class FieldRegistryTest extends TestCase
{
    private FieldRegistry $registry;

    public function setUp(): void
    {
        $this->registry = new FieldRegistry();
    }

    // =========================================================================
    // Registry Basics
    // =========================================================================

    public function testRegistryHasBuiltInTypes(): void
    {
        $expectedTypes = [
            'text', 'textarea', 'number', 'checkbox', 'select', 'date',
            'color', 'file', 'image', 'gallery', 'array', 'content',
            'status', 'template', 'taxonomy'
        ];

        foreach ($expectedTypes as $typeName) {
            $type = $this->registry->get($typeName);
            $this->assertNotNull($type, "Type '{$typeName}' should be registered");
        }
    }

    public function testRegistryReturnsNullForUnknownType(): void
    {
        $type = $this->registry->get('nonexistent');
        $this->assertNull($type);
    }

    public function testCreateFieldReturnsFieldInstance(): void
    {
        $field = $this->registry->createField('test_field', [
            'type' => 'text',
            'label' => 'Test Field',
        ]);

        $this->assertNotNull($field);
        $this->assertInstanceOf(Field::class, $field);
        $this->assertEquals('test_field', $field->name());
        $this->assertEquals('Test Field', $field->label());
    }

    public function testCreateFieldReturnsNullForUnknownType(): void
    {
        $field = $this->registry->createField('test_field', [
            'type' => 'nonexistent',
        ]);

        $this->assertNull($field);
    }

    public function testGetAllTypesReturnsArray(): void
    {
        $types = $this->registry->getAll();
        $this->assertIsArray($types);
        $this->assertNotEmpty($types);
    }

    // =========================================================================
    // Custom Type Registration
    // =========================================================================

    public function testCanRegisterCustomType(): void
    {
        $mockType = new class implements \Ava\Fields\FieldType {
            public function name(): string { return 'custom'; }
            public function label(): string { return 'Custom'; }
            public function schema(): array { return []; }
            public function validate(mixed $value, array $config): \Ava\Fields\ValidationResult {
                return \Ava\Fields\ValidationResult::success();
            }
            public function toStorage(mixed $value, array $config): mixed { return $value; }
            public function fromStorage(mixed $value, array $config): mixed { return $value; }
            public function defaultValue(array $config): mixed { return null; }
            public function render(string $name, mixed $value, array $config, array $context = []): string { return ''; }
            public function javascript(): string { return ''; }
        };

        $this->registry->register($mockType);
        $retrieved = $this->registry->get('custom');
        
        $this->assertNotNull($retrieved);
        $this->assertEquals('custom', $retrieved->name());
    }
}
