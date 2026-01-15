<?php

declare(strict_types=1);

namespace Ava\Tests\Fields;

use Ava\Fields\FieldRegistry;
use Ava\Testing\TestCase;

/**
 * Tests for Field Types - Text and Textarea.
 */
final class TextFieldsTest extends TestCase
{
    private FieldRegistry $registry;

    public function setUp(): void
    {
        $this->registry = new FieldRegistry();
    }

    // =========================================================================
    // Text Field
    // =========================================================================

    public function testTextFieldValidatesRequired(): void
    {
        $field = $this->registry->createField('name', [
            'type' => 'text',
            'required' => true,
        ]);

        $result = $field->validate(null);
        $this->assertFalse($result->isValid());
        $this->assertNotEmpty($result->errors());

        $result = $field->validate('');
        $this->assertFalse($result->isValid());

        $result = $field->validate('John');
        $this->assertTrue($result->isValid());
    }

    public function testTextFieldValidatesMinLength(): void
    {
        $field = $this->registry->createField('username', [
            'type' => 'text',
            'minLength' => 3,
        ]);

        $result = $field->validate('ab');
        $this->assertFalse($result->isValid());

        $result = $field->validate('abc');
        $this->assertTrue($result->isValid());

        $result = $field->validate('abcdef');
        $this->assertTrue($result->isValid());
    }

    public function testTextFieldValidatesMaxLength(): void
    {
        $field = $this->registry->createField('code', [
            'type' => 'text',
            'maxLength' => 5,
        ]);

        $result = $field->validate('12345');
        $this->assertTrue($result->isValid());

        $result = $field->validate('123456');
        $this->assertFalse($result->isValid());
    }

    public function testTextFieldValidatesPattern(): void
    {
        $field = $this->registry->createField('email', [
            'type' => 'text',
            'pattern' => '^[a-z]+@[a-z]+\.[a-z]+$',
        ]);

        $result = $field->validate('test@example.com');
        $this->assertTrue($result->isValid());

        $result = $field->validate('invalid-email');
        $this->assertFalse($result->isValid());
    }

    public function testTextFieldAllowsEmptyWhenNotRequired(): void
    {
        $field = $this->registry->createField('optional', [
            'type' => 'text',
            'required' => false,
        ]);

        $result = $field->validate(null);
        $this->assertTrue($result->isValid());

        $result = $field->validate('');
        $this->assertTrue($result->isValid());
    }

    // =========================================================================
    // Textarea Field
    // =========================================================================

    public function testTextareaFieldValidatesRequired(): void
    {
        $field = $this->registry->createField('bio', [
            'type' => 'textarea',
            'required' => true,
        ]);

        $result = $field->validate(null);
        $this->assertFalse($result->isValid());

        $result = $field->validate('A long biography...');
        $this->assertTrue($result->isValid());
    }

    public function testTextareaFieldValidatesMinLength(): void
    {
        $field = $this->registry->createField('description', [
            'type' => 'textarea',
            'minLength' => 10,
        ]);

        $result = $field->validate('Short');
        $this->assertFalse($result->isValid());

        $result = $field->validate('This is a long enough description.');
        $this->assertTrue($result->isValid());
    }

    public function testTextareaFieldValidatesMaxLength(): void
    {
        $field = $this->registry->createField('notes', [
            'type' => 'textarea',
            'maxLength' => 20,
        ]);

        $result = $field->validate('Short notes');
        $this->assertTrue($result->isValid());

        $result = $field->validate('This is a very long text that exceeds the limit');
        $this->assertFalse($result->isValid());
    }
}
