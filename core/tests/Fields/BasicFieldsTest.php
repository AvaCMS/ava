<?php

declare(strict_types=1);

namespace Ava\Tests\Fields;

use Ava\Fields\FieldRegistry;
use Ava\Testing\TestCase;

/**
 * Tests for Field Types - Number, Checkbox, Select, Date.
 */
final class BasicFieldsTest extends TestCase
{
    private FieldRegistry $registry;

    public function setUp(): void
    {
        $this->registry = new FieldRegistry();
    }

    // =========================================================================
    // Number Field
    // =========================================================================

    public function testNumberFieldValidatesRequired(): void
    {
        $field = $this->registry->createField('quantity', [
            'type' => 'number',
            'required' => true,
        ]);

        $result = $field->validate(null);
        $this->assertFalse($result->isValid());

        $result = $field->validate(5);
        $this->assertTrue($result->isValid());
    }

    public function testNumberFieldValidatesMin(): void
    {
        $field = $this->registry->createField('age', [
            'type' => 'number',
            'min' => 18,
        ]);

        $result = $field->validate(17);
        $this->assertFalse($result->isValid());

        $result = $field->validate(18);
        $this->assertTrue($result->isValid());

        $result = $field->validate(25);
        $this->assertTrue($result->isValid());
    }

    public function testNumberFieldValidatesMax(): void
    {
        $field = $this->registry->createField('rating', [
            'type' => 'number',
            'max' => 5,
        ]);

        $result = $field->validate(3);
        $this->assertTrue($result->isValid());

        $result = $field->validate(5);
        $this->assertTrue($result->isValid());

        $result = $field->validate(6);
        $this->assertFalse($result->isValid());
    }

    public function testNumberFieldValidatesIntegerType(): void
    {
        $field = $this->registry->createField('count', [
            'type' => 'number',
            'numberType' => 'int',
        ]);

        $result = $field->validate(10);
        $this->assertTrue($result->isValid());

        $result = $field->validate(10.5);
        $this->assertFalse($result->isValid());
    }

    public function testNumberFieldValidatesFloatType(): void
    {
        $field = $this->registry->createField('price', [
            'type' => 'number',
            'numberType' => 'float',
        ]);

        $result = $field->validate(10);
        $this->assertTrue($result->isValid());

        $result = $field->validate(10.99);
        $this->assertTrue($result->isValid());
    }

    public function testNumberFieldToStorage(): void
    {
        $field = $this->registry->createField('count', [
            'type' => 'number',
            'numberType' => 'int',
        ]);

        $this->assertEquals(42, $field->toStorage('42'));
        $this->assertEquals(0, $field->toStorage(''));
    }

    // =========================================================================
    // Checkbox Field
    // =========================================================================

    public function testCheckboxFieldValidatesBoolean(): void
    {
        $field = $this->registry->createField('featured', [
            'type' => 'checkbox',
        ]);

        $result = $field->validate(true);
        $this->assertTrue($result->isValid());

        $result = $field->validate(false);
        $this->assertTrue($result->isValid());

        $result = $field->validate(1);
        $this->assertTrue($result->isValid());

        $result = $field->validate(0);
        $this->assertTrue($result->isValid());

        $result = $field->validate('yes');
        $this->assertTrue($result->isValid());
    }

    public function testCheckboxFieldToStorage(): void
    {
        $field = $this->registry->createField('enabled', [
            'type' => 'checkbox',
        ]);

        $this->assertTrue($field->toStorage(true));
        $this->assertTrue($field->toStorage(1));
        $this->assertTrue($field->toStorage('1'));
        $this->assertTrue($field->toStorage('true'));
        $this->assertTrue($field->toStorage('on'));

        $this->assertFalse($field->toStorage(false));
        $this->assertFalse($field->toStorage(0));
        $this->assertFalse($field->toStorage(''));
        $this->assertFalse($field->toStorage(null));
    }

    public function testCheckboxFieldFromStorage(): void
    {
        $field = $this->registry->createField('active', [
            'type' => 'checkbox',
        ]);

        $this->assertTrue($field->fromStorage(true));
        $this->assertFalse($field->fromStorage(false));
    }

    // =========================================================================
    // Select Field
    // =========================================================================

    public function testSelectFieldValidatesOptions(): void
    {
        $field = $this->registry->createField('color', [
            'type' => 'select',
            'options' => [
                'red' => 'Red',
                'green' => 'Green',
                'blue' => 'Blue',
            ],
        ]);

        $result = $field->validate('red');
        $this->assertTrue($result->isValid());

        $result = $field->validate('green');
        $this->assertTrue($result->isValid());

        $result = $field->validate('yellow');
        $this->assertFalse($result->isValid());
    }

    public function testSelectFieldValidatesRequired(): void
    {
        $field = $this->registry->createField('country', [
            'type' => 'select',
            'required' => true,
            'options' => [
                'us' => 'United States',
                'uk' => 'United Kingdom',
            ],
        ]);

        $result = $field->validate(null);
        $this->assertFalse($result->isValid());

        $result = $field->validate('');
        $this->assertFalse($result->isValid());

        $result = $field->validate('us');
        $this->assertTrue($result->isValid());
    }

    public function testSelectFieldValidatesMultiple(): void
    {
        $field = $this->registry->createField('tags', [
            'type' => 'select',
            'multiple' => true,
            'options' => [
                'news' => 'News',
                'tech' => 'Technology',
                'sports' => 'Sports',
            ],
        ]);

        $result = $field->validate(['news', 'tech']);
        $this->assertTrue($result->isValid());

        $result = $field->validate(['news', 'invalid']);
        $this->assertFalse($result->isValid());
    }

    // =========================================================================
    // Date Field
    // =========================================================================

    public function testDateFieldValidatesFormat(): void
    {
        $field = $this->registry->createField('published_at', [
            'type' => 'date',
        ]);

        $result = $field->validate('2024-01-15');
        $this->assertTrue($result->isValid());

        $result = $field->validate('01/15/2024');
        $this->assertFalse($result->isValid());

        $result = $field->validate('invalid-date');
        $this->assertFalse($result->isValid());
    }

    public function testDateFieldValidatesDatetime(): void
    {
        $field = $this->registry->createField('event_time', [
            'type' => 'date',
            'includeTime' => true,
        ]);

        $result = $field->validate('2024-01-15T10:30:00');
        $this->assertTrue($result->isValid());

        $result = $field->validate('2024-01-15 10:30:00');
        $this->assertTrue($result->isValid());

        $result = $field->validate('2024-01-15');
        $this->assertTrue($result->isValid());
    }

    public function testDateFieldValidatesMinMax(): void
    {
        $field = $this->registry->createField('booking_date', [
            'type' => 'date',
            'min' => '2024-01-01',
            'max' => '2024-12-31',
        ]);

        $result = $field->validate('2023-12-31');
        $this->assertFalse($result->isValid());

        $result = $field->validate('2024-06-15');
        $this->assertTrue($result->isValid());

        $result = $field->validate('2025-01-01');
        $this->assertFalse($result->isValid());
    }

    public function testDateFieldAllowsEmptyWhenNotRequired(): void
    {
        $field = $this->registry->createField('optional_date', [
            'type' => 'date',
            'required' => false,
        ]);

        $result = $field->validate(null);
        $this->assertTrue($result->isValid());

        $result = $field->validate('');
        $this->assertTrue($result->isValid());
    }

    public function testDateFieldToStorage(): void
    {
        $field = $this->registry->createField('date', [
            'type' => 'date',
        ]);

        // Should normalize to Y-m-d format
        $stored = $field->toStorage('2024-01-15');
        $this->assertEquals('2024-01-15', $stored);
    }
}
