<?php

declare(strict_types=1);

namespace Ava\Tests\Fields;

use Ava\Fields\FieldRegistry;
use Ava\Testing\TestCase;

/**
 * Tests for Field Types - Status, Template.
 */
final class SystemFieldsTest extends TestCase
{
    private FieldRegistry $registry;

    public function setUp(): void
    {
        $this->registry = new FieldRegistry();
    }

    // =========================================================================
    // Status Field
    // =========================================================================

    public function testStatusFieldValidatesOptions(): void
    {
        $type = $this->registry->get('status');
        $this->assertNotNull($type);

        $result = $type->validate('draft', []);
        $this->assertTrue($result->isValid());

        $result = $type->validate('published', []);
        $this->assertTrue($result->isValid());

        $result = $type->validate('unlisted', []);
        $this->assertTrue($result->isValid());

        $result = $type->validate('pending', []);
        $this->assertFalse($result->isValid());

        $result = $type->validate('invalid', []);
        $this->assertFalse($result->isValid());
    }

    public function testStatusFieldToStorage(): void
    {
        $type = $this->registry->get('status');

        $stored = $type->toStorage('published', []);
        $this->assertEquals('published', $stored);

        // Empty should default to draft
        $stored = $type->toStorage('', []);
        $this->assertEquals('draft', $stored);

        $stored = $type->toStorage(null, []);
        $this->assertEquals('draft', $stored);
    }

    // =========================================================================
    // Template Field
    // =========================================================================

    public function testTemplateFieldValidatesFilename(): void
    {
        $type = $this->registry->get('template');
        $this->assertNotNull($type);

        $result = $type->validate('single.php', []);
        $this->assertTrue($result->isValid());

        $result = $type->validate('custom-template.php', []);
        $this->assertTrue($result->isValid());

        $result = $type->validate('', []);
        $this->assertTrue($result->isValid()); // Empty is allowed (use default)

        // Should reject paths
        $result = $type->validate('../escape.php', []);
        $this->assertFalse($result->isValid());

        $result = $type->validate('/etc/passwd', []);
        $this->assertFalse($result->isValid());
    }

    public function testTemplateFieldRejectsNonPhp(): void
    {
        $type = $this->registry->get('template');

        $result = $type->validate('template.html', []);
        $this->assertFalse($result->isValid());

        $result = $type->validate('template.txt', []);
        $this->assertFalse($result->isValid());
    }

    // =========================================================================
    // Taxonomy Field
    // =========================================================================

    public function testTaxonomyFieldValidatesArray(): void
    {
        $type = $this->registry->get('taxonomy');
        $this->assertNotNull($type);

        $result = $type->validate(['news', 'tech'], ['multiple' => true]);
        $this->assertTrue($result->isValid());

        $result = $type->validate('news', []);
        $this->assertTrue($result->isValid());

        $result = $type->validate([], []);
        $this->assertTrue($result->isValid());
    }

    public function testTaxonomyFieldValidatesRequired(): void
    {
        $type = $this->registry->get('taxonomy');

        $result = $type->validate(null, ['required' => true]);
        $this->assertFalse($result->isValid());

        $result = $type->validate([], ['required' => true]);
        $this->assertFalse($result->isValid());

        $result = $type->validate(['news'], ['required' => true]);
        $this->assertTrue($result->isValid());
    }

    public function testTaxonomyFieldValidatesSlugFormat(): void
    {
        $type = $this->registry->get('taxonomy');

        // Valid slugs
        $result = $type->validate(['valid-slug', 'another-one'], []);
        $this->assertTrue($result->isValid());

        // Invalid slugs should be warned about
        $result = $type->validate(['Invalid Slug', 'CAPS'], []);
        $this->assertNotEmpty($result->warnings());
    }

    public function testTaxonomyFieldToStorage(): void
    {
        $type = $this->registry->get('taxonomy');

        // Single value
        $stored = $type->toStorage('news', []);
        $this->assertEquals(['news'], $stored);

        // Array value
        $stored = $type->toStorage(['news', 'tech'], []);
        $this->assertEquals(['news', 'tech'], $stored);

        // Comma-separated string
        $stored = $type->toStorage('news, tech, sports', []);
        $this->assertEquals(['news', 'tech', 'sports'], $stored);
    }
}
