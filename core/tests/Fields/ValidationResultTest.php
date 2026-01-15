<?php

declare(strict_types=1);

namespace Ava\Tests\Fields;

use Ava\Fields\ValidationResult;
use Ava\Testing\TestCase;

/**
 * Tests for ValidationResult.
 */
final class ValidationResultTest extends TestCase
{
    // =========================================================================
    // Success/Error Creation
    // =========================================================================

    public function testSuccessCreatesValidResult(): void
    {
        $result = ValidationResult::success();
        
        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->errors());
        $this->assertEmpty($result->warnings());
    }

    public function testErrorCreatesInvalidResult(): void
    {
        $result = ValidationResult::error('Something went wrong');
        
        $this->assertFalse($result->isValid());
        $this->assertContains('Something went wrong', $result->errors());
    }

    public function testWarningCreatesValidResultWithWarning(): void
    {
        $result = ValidationResult::warning('Be careful');
        
        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->errors());
        $this->assertContains('Be careful', $result->warnings());
    }

    // =========================================================================
    // Merging Results
    // =========================================================================

    public function testMergeSuccessResults(): void
    {
        $r1 = ValidationResult::success();
        $r2 = ValidationResult::success();
        
        $merged = ValidationResult::merge($r1, $r2);
        
        $this->assertTrue($merged->isValid());
    }

    public function testMergeWithErrorResultsInInvalid(): void
    {
        $r1 = ValidationResult::success();
        $r2 = ValidationResult::error('Error 1');
        $r3 = ValidationResult::error('Error 2');
        
        $merged = ValidationResult::merge($r1, $r2, $r3);
        
        $this->assertFalse($merged->isValid());
        $this->assertCount(2, $merged->errors());
        $this->assertContains('Error 1', $merged->errors());
        $this->assertContains('Error 2', $merged->errors());
    }

    public function testMergePreservesWarnings(): void
    {
        $r1 = ValidationResult::warning('Warning 1');
        $r2 = ValidationResult::warning('Warning 2');
        
        $merged = ValidationResult::merge($r1, $r2);
        
        $this->assertTrue($merged->isValid());
        $this->assertCount(2, $merged->warnings());
    }

    // =========================================================================
    // Array Conversion
    // =========================================================================

    public function testToArrayReturnsStructuredResult(): void
    {
        $result = ValidationResult::error('An error occurred');
        $array = $result->toArray();
        
        $this->assertArrayHasKey('valid', $array);
        $this->assertArrayHasKey('errors', $array);
        $this->assertArrayHasKey('warnings', $array);
        $this->assertFalse($array['valid']);
        $this->assertContains('An error occurred', $array['errors']);
    }
}
