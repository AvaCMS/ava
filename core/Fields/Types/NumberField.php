<?php

declare(strict_types=1);

namespace Ava\Fields\Types;

use Ava\Fields\AbstractFieldType;
use Ava\Fields\ValidationResult;

/**
 * Number Field Type
 *
 * Numeric input with support for integers and floats.
 */
final class NumberField extends AbstractFieldType
{
    public function name(): string
    {
        return 'number';
    }

    public function label(): string
    {
        return 'Number';
    }

    public function schema(): array
    {
        return array_merge($this->baseSchema(), [
            'numberType' => [
                'type' => 'string',
                'label' => 'Number Type',
                'description' => 'Integer or floating point',
                'default' => 'int',
                'options' => ['int', 'float'],
            ],
            'min' => [
                'type' => 'number',
                'label' => 'Minimum',
                'description' => 'Minimum allowed value',
            ],
            'max' => [
                'type' => 'number',
                'label' => 'Maximum',
                'description' => 'Maximum allowed value',
            ],
            'step' => [
                'type' => 'number',
                'label' => 'Step',
                'description' => 'Step increment (e.g., 0.01 for currency)',
            ],
        ]);
    }

    public function validate(mixed $value, array $config): ValidationResult
    {
        if (!is_numeric($value)) {
            return ValidationResult::error('Value must be a number.');
        }

        $numValue = (float) $value;
        $isInt = $config['numberType'] ?? 'int';

        if ($isInt === 'int' && floor($numValue) !== $numValue) {
            return ValidationResult::error('Value must be a whole number.');
        }

        if (isset($config['min']) && $numValue < $config['min']) {
            return ValidationResult::error(
                "Must be at least {$config['min']}."
            );
        }

        if (isset($config['max']) && $numValue > $config['max']) {
            return ValidationResult::error(
                "Must be no more than {$config['max']}."
            );
        }

        return ValidationResult::success();
    }

    public function toStorage(mixed $value, array $config): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        $numberType = $config['numberType'] ?? 'int';
        return $numberType === 'int' ? (int) $value : (float) $value;
    }

    public function defaultValue(array $config): mixed
    {
        return $config['default'] ?? null;
    }

    public function render(string $name, mixed $value, array $config, array $context = []): string
    {
        $id = 'field-' . $this->e($name);
        $numberType = $config['numberType'] ?? 'int';
        
        $attrs = [
            'type' => 'number',
            'id' => $id,
            'name' => 'fields[' . $name . ']',
            'value' => $value,
            'class' => 'form-control field-input',
            'placeholder' => $config['placeholder'] ?? null,
            'required' => $config['required'] ?? false,
            'min' => $config['min'] ?? null,
            'max' => $config['max'] ?? null,
            'step' => $config['step'] ?? ($numberType === 'float' ? 'any' : '1'),
            'data-field-type' => 'number',
            'data-number-type' => $numberType,
        ];

        $input = '<input ' . $this->attributes($attrs) . '>';

        return $this->wrapField($name, $input, $config);
    }
}
