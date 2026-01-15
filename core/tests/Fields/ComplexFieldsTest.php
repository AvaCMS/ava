<?php

declare(strict_types=1);

namespace Ava\Tests\Fields;

use Ava\Fields\FieldRegistry;
use Ava\Testing\TestCase;

/**
 * Tests for Field Types - Array, Color, Image, File, Gallery.
 */
final class ComplexFieldsTest extends TestCase
{
    private FieldRegistry $registry;

    public function setUp(): void
    {
        $this->registry = new FieldRegistry();
    }

    // =========================================================================
    // Array Field
    // =========================================================================

    public function testArrayFieldValidatesArrayType(): void
    {
        $field = $this->registry->createField('tags', [
            'type' => 'array',
        ]);

        $result = $field->validate(['tag1', 'tag2']);
        $this->assertTrue($result->isValid());

        $result = $field->validate([]);
        $this->assertTrue($result->isValid());

        $result = $field->validate('not-an-array');
        $this->assertFalse($result->isValid());
    }

    public function testArrayFieldValidatesRequired(): void
    {
        $field = $this->registry->createField('categories', [
            'type' => 'array',
            'required' => true,
        ]);

        $result = $field->validate(null);
        $this->assertFalse($result->isValid());

        $result = $field->validate([]);
        $this->assertFalse($result->isValid());

        $result = $field->validate(['category1']);
        $this->assertTrue($result->isValid());
    }

    public function testArrayFieldValidatesMinMax(): void
    {
        $field = $this->registry->createField('options', [
            'type' => 'array',
            'minItems' => 2,
            'maxItems' => 5,
        ]);

        $result = $field->validate(['one']);
        $this->assertFalse($result->isValid());

        $result = $field->validate(['one', 'two']);
        $this->assertTrue($result->isValid());

        $result = $field->validate(['a', 'b', 'c', 'd', 'e']);
        $this->assertTrue($result->isValid());

        $result = $field->validate(['a', 'b', 'c', 'd', 'e', 'f']);
        $this->assertFalse($result->isValid());
    }

    public function testArrayFieldToStorage(): void
    {
        $field = $this->registry->createField('items', [
            'type' => 'array',
        ]);

        // String input should be converted to array
        $stored = $field->toStorage('item1, item2, item3');
        $this->assertIsArray($stored);
        $this->assertCount(3, $stored);

        // Array input should pass through
        $stored = $field->toStorage(['a', 'b']);
        $this->assertEquals(['a', 'b'], $stored);
    }

    // =========================================================================
    // Color Field
    // =========================================================================

    public function testColorFieldValidatesHexFormat(): void
    {
        $field = $this->registry->createField('brand_color', [
            'type' => 'color',
        ]);

        $result = $field->validate('#ff0000');
        $this->assertTrue($result->isValid());

        $result = $field->validate('#f00');
        $this->assertTrue($result->isValid());

        $result = $field->validate('#AABBCC');
        $this->assertTrue($result->isValid());

        $result = $field->validate('ff0000');
        $this->assertFalse($result->isValid());

        $result = $field->validate('#gggggg');
        $this->assertFalse($result->isValid());
    }

    public function testColorFieldValidatesRgbFormat(): void
    {
        $field = $this->registry->createField('bg_color', [
            'type' => 'color',
            'format' => 'rgb',
        ]);

        $result = $field->validate('rgb(255, 0, 0)');
        $this->assertTrue($result->isValid());

        $result = $field->validate('rgb(0, 128, 255)');
        $this->assertTrue($result->isValid());
    }

    public function testColorFieldAllowsEmptyWhenNotRequired(): void
    {
        $field = $this->registry->createField('accent', [
            'type' => 'color',
            'required' => false,
        ]);

        $result = $field->validate(null);
        $this->assertTrue($result->isValid());

        $result = $field->validate('');
        $this->assertTrue($result->isValid());
    }

    // =========================================================================
    // Image Field
    // =========================================================================

    public function testImageFieldValidatesPath(): void
    {
        $field = $this->registry->createField('hero_image', [
            'type' => 'image',
        ]);

        $result = $field->validate('/media/hero.jpg');
        $this->assertTrue($result->isValid());

        $result = $field->validate('@media:images/photo.png');
        $this->assertTrue($result->isValid());
    }

    public function testImageFieldValidatesExtensions(): void
    {
        $field = $this->registry->createField('photo', [
            'type' => 'image',
            'allowedTypes' => ['jpg', 'png'],
        ]);

        $result = $field->validate('/media/photo.jpg');
        $this->assertTrue($result->isValid());

        $result = $field->validate('/media/photo.png');
        $this->assertTrue($result->isValid());

        $result = $field->validate('/media/photo.gif');
        $this->assertFalse($result->isValid());
    }

    public function testImageFieldValidatesRequired(): void
    {
        $field = $this->registry->createField('avatar', [
            'type' => 'image',
            'required' => true,
        ]);

        $result = $field->validate(null);
        $this->assertFalse($result->isValid());

        $result = $field->validate('');
        $this->assertFalse($result->isValid());

        $result = $field->validate('/media/avatar.jpg');
        $this->assertTrue($result->isValid());
    }

    // =========================================================================
    // File Field
    // =========================================================================

    public function testFileFieldValidatesPath(): void
    {
        $field = $this->registry->createField('document', [
            'type' => 'file',
        ]);

        $result = $field->validate('/media/doc.pdf');
        $this->assertTrue($result->isValid());

        $result = $field->validate('@media:files/report.xlsx');
        $this->assertTrue($result->isValid());
    }

    public function testFileFieldValidatesExtensions(): void
    {
        $field = $this->registry->createField('resume', [
            'type' => 'file',
            'accept' => '.pdf, .doc, .docx',
        ]);

        $result = $field->validate('/media/resume.pdf');
        $this->assertTrue($result->isValid());

        $result = $field->validate('/media/resume.exe');
        $this->assertFalse($result->isValid());
    }

    // =========================================================================
    // Gallery Field
    // =========================================================================

    public function testGalleryFieldValidatesArrayOfImages(): void
    {
        $field = $this->registry->createField('photos', [
            'type' => 'gallery',
        ]);

        $result = $field->validate([
            '/media/photo1.jpg',
            '/media/photo2.jpg',
        ]);
        $this->assertTrue($result->isValid());

        $result = $field->validate([]);
        $this->assertTrue($result->isValid());

        $result = $field->validate('not-an-array');
        $this->assertFalse($result->isValid());
    }

    public function testGalleryFieldValidatesMinMax(): void
    {
        $field = $this->registry->createField('portfolio', [
            'type' => 'gallery',
            'minItems' => 3,
            'maxItems' => 10,
        ]);

        $result = $field->validate(['/a.jpg', '/b.jpg']);
        $this->assertFalse($result->isValid());

        $result = $field->validate(['/a.jpg', '/b.jpg', '/c.jpg']);
        $this->assertTrue($result->isValid());
    }

    public function testGalleryFieldValidatesImageExtensions(): void
    {
        $field = $this->registry->createField('slides', [
            'type' => 'gallery',
            'allowedTypes' => ['jpg', 'png'],
        ]);

        $result = $field->validate(['/a.jpg', '/b.png']);
        $this->assertTrue($result->isValid());

        $result = $field->validate(['/a.jpg', '/b.gif']);
        $this->assertFalse($result->isValid());
    }
}
