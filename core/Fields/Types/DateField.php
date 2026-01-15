<?php

declare(strict_types=1);

namespace Ava\Fields\Types;

use Ava\Fields\AbstractFieldType;
use Ava\Fields\ValidationResult;

/**
 * Date Field Type
 *
 * Date picker with ISO 8601 storage format (Y-m-d).
 */
final class DateField extends AbstractFieldType
{
    public function name(): string
    {
        return 'date';
    }

    public function label(): string
    {
        return 'Date';
    }

    public function schema(): array
    {
        return array_merge($this->baseSchema(), [
            'min' => [
                'type' => 'string',
                'label' => 'Minimum Date',
                'description' => 'Earliest allowed date (Y-m-d format)',
            ],
            'max' => [
                'type' => 'string',
                'label' => 'Maximum Date',
                'description' => 'Latest allowed date (Y-m-d format)',
            ],
            'includeTime' => [
                'type' => 'bool',
                'label' => 'Include Time',
                'description' => 'Also capture time (datetime-local input)',
                'default' => false,
            ],
        ]);
    }

    public function validate(mixed $value, array $config): ValidationResult
    {
        if (!is_string($value) && !$value instanceof \DateTimeInterface) {
            return ValidationResult::error('Value must be a valid date.');
        }

        $dateStr = (string) $value;
        
        // Parse the date - try multiple formats
        $date = null;
        $formats = [
            'Y-m-d\TH:i:s',    // 2024-01-15T10:30:00
            'Y-m-d\TH:i',      // 2024-01-15T10:30
            'Y-m-d H:i:s',     // 2024-01-15 10:30:00
            'Y-m-d H:i',       // 2024-01-15 10:30
            'Y-m-d',           // 2024-01-15
        ];
        
        foreach ($formats as $format) {
            $parsed = \DateTimeImmutable::createFromFormat($format, $dateStr);
            if ($parsed !== false) {
                $date = $parsed;
                break;
            }
        }
        
        if ($date === null) {
            $includeTime = $config['includeTime'] ?? false;
            return ValidationResult::error('Invalid date format. Use ' . ($includeTime ? 'YYYY-MM-DDTHH:MM' : 'YYYY-MM-DD') . '.');
        }

        if (isset($config['min'])) {
            $min = new \DateTimeImmutable($config['min']);
            if ($date < $min) {
                return ValidationResult::error("Date must be {$config['min']} or later.");
            }
        }

        if (isset($config['max'])) {
            $max = new \DateTimeImmutable($config['max']);
            if ($date > $max) {
                return ValidationResult::error("Date must be {$config['max']} or earlier.");
            }
        }

        return ValidationResult::success();
    }

    public function toStorage(mixed $value, array $config): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Parse and normalize to Y-m-d or Y-m-d H:i:s
        $includeTime = $config['includeTime'] ?? false;
        
        if ($value instanceof \DateTimeInterface) {
            return $includeTime ? $value->format('Y-m-d H:i:s') : $value->format('Y-m-d');
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $value)
             ?: \DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s', $value)
             ?: \DateTimeImmutable::createFromFormat('Y-m-d', $value);

        if ($date === false) {
            return $value; // Store as-is if we can't parse
        }

        return $includeTime ? $date->format('Y-m-d H:i:s') : $date->format('Y-m-d');
    }

    public function fromStorage(mixed $value, array $config): mixed
    {
        if ($value === null || $value === '') {
            return '';
        }

        // For editing, we need the HTML datetime-local or date format
        $includeTime = $config['includeTime'] ?? false;
        
        if ($value instanceof \DateTimeInterface) {
            return $includeTime ? $value->format('Y-m-d\TH:i') : $value->format('Y-m-d');
        }

        // Try to parse and reformat
        $date = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', (string) $value)
             ?: \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', (string) $value)
             ?: \DateTimeImmutable::createFromFormat('Y-m-d', (string) $value);

        if ($date === false) {
            return $value;
        }

        return $includeTime ? $date->format('Y-m-d\TH:i') : $date->format('Y-m-d');
    }

    public function render(string $name, mixed $value, array $config, array $context = []): string
    {
        $id = 'field-' . $this->e($name);
        $includeTime = $config['includeTime'] ?? false;
        $inputType = $includeTime ? 'datetime-local' : 'date';
        
        $attrs = [
            'type' => $inputType,
            'id' => $id,
            'name' => 'fields[' . $name . ']',
            'value' => $this->fromStorage($value, $config),
            'class' => 'form-control field-input',
            'required' => $config['required'] ?? false,
            'min' => $config['min'] ?? null,
            'max' => $config['max'] ?? null,
            'data-field-type' => 'date',
        ];

        $input = '<input ' . $this->attributes($attrs) . '>';

        return $this->wrapField($name, $input, $config);
    }
}
