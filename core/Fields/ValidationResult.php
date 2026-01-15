<?php

declare(strict_types=1);

namespace Ava\Fields;

/**
 * Validation Result
 *
 * Immutable value object representing the result of field validation.
 */
final class ValidationResult
{
    private bool $valid;
    private array $errors;
    private array $warnings;

    private function __construct(bool $valid, array $errors = [], array $warnings = [])
    {
        $this->valid = $valid;
        $this->errors = $errors;
        $this->warnings = $warnings;
    }

    /**
     * Create a successful validation result.
     */
    public static function success(array $warnings = []): self
    {
        return new self(true, [], $warnings);
    }

    /**
     * Create a failed validation result.
     */
    public static function error(string $message): self
    {
        return new self(false, [$message]);
    }

    /**
     * Create a failed validation result with multiple errors.
     */
    public static function fromErrors(array $messages): self
    {
        return new self(false, $messages);
    }

    /**
     * Create a result with warnings (still valid, but with advisory messages).
     */
    public static function warning(string $message): self
    {
        return new self(true, [], [$message]);
    }

    /**
     * Merge multiple results into one.
     */
    public static function merge(ValidationResult ...$results): self
    {
        $valid = true;
        $errors = [];
        $warnings = [];

        foreach ($results as $result) {
            if (!$result->valid) {
                $valid = false;
            }
            $errors = array_merge($errors, $result->errors);
            $warnings = array_merge($warnings, $result->warnings);
        }

        return new self($valid, $errors, $warnings);
    }

    /**
     * Check if validation passed.
     */
    public function isValid(): bool
    {
        return $this->valid;
    }

    /**
     * Get all error messages.
     *
     * @return array<string>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Get the first error message.
     */
    public function firstError(): ?string
    {
        return $this->errors[0] ?? null;
    }

    /**
     * Get all warning messages.
     *
     * @return array<string>
     */
    public function warnings(): array
    {
        return $this->warnings;
    }

    /**
     * Check if there are warnings.
     */
    public function hasWarnings(): bool
    {
        return count($this->warnings) > 0;
    }

    /**
     * Add an error to this result.
     */
    public function withError(string $message): self
    {
        return new self(false, [...$this->errors, $message], $this->warnings);
    }

    /**
     * Add a warning to this result.
     */
    public function withWarning(string $message): self
    {
        return new self($this->valid, $this->errors, [...$this->warnings, $message]);
    }

    /**
     * Convert to array representation.
     *
     * @return array{valid: bool, errors: array<string>, warnings: array<string>}
     */
    public function toArray(): array
    {
        return [
            'valid' => $this->valid,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
        ];
    }
}
