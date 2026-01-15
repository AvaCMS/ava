<?php

declare(strict_types=1);

namespace Ava\Fields\Types;

use Ava\Fields\AbstractFieldType;
use Ava\Fields\ValidationResult;

/**
 * Select Field Type
 *
 * Dropdown/select input with configurable options.
 */
final class SelectField extends AbstractFieldType
{
    public function name(): string
    {
        return 'select';
    }

    public function label(): string
    {
        return 'Select';
    }

    public function schema(): array
    {
        return array_merge($this->baseSchema(), [
            'options' => [
                'type' => 'array',
                'label' => 'Options',
                'description' => 'List of options: array of values or key=>label pairs',
                'required' => true,
            ],
            'multiple' => [
                'type' => 'bool',
                'label' => 'Multiple',
                'description' => 'Allow multiple selections',
                'default' => false,
            ],
            'emptyOption' => [
                'type' => 'string',
                'label' => 'Empty Option',
                'description' => 'Text for the empty/placeholder option',
                'default' => '— Select —',
            ],
        ]);
    }

    public function validate(mixed $value, array $config): ValidationResult
    {
        $options = $this->normalizeOptions($config['options'] ?? []);
        $validValues = array_keys($options);
        $multiple = $config['multiple'] ?? false;

        if ($multiple) {
            if (!is_array($value)) {
                return ValidationResult::error('Value must be an array for multiple select.');
            }
            foreach ($value as $v) {
                if (!in_array((string) $v, $validValues, true)) {
                    return ValidationResult::error("Invalid option: {$v}");
                }
            }
        } else {
            if (is_array($value)) {
                return ValidationResult::error('Value must be a single option.');
            }
            if ($value !== '' && $value !== null && !in_array((string) $value, $validValues, true)) {
                return ValidationResult::error("Invalid option: {$value}");
            }
        }

        return ValidationResult::success();
    }

    public function toStorage(mixed $value, array $config): mixed
    {
        $multiple = $config['multiple'] ?? false;
        
        if ($multiple && is_array($value)) {
            return array_values(array_filter($value, fn($v) => $v !== '' && $v !== null));
        }
        
        return $value === '' ? null : $value;
    }

    public function fromStorage(mixed $value, array $config): mixed
    {
        $multiple = $config['multiple'] ?? false;
        
        if ($multiple) {
            if (!is_array($value)) {
                return $value === null ? [] : [$value];
            }
            return $value;
        }
        
        return $value;
    }

    public function defaultValue(array $config): mixed
    {
        $multiple = $config['multiple'] ?? false;
        return $config['default'] ?? ($multiple ? [] : null);
    }

    /**
     * Normalize options to [value => label] format.
     */
    private function normalizeOptions(array $options): array
    {
        $normalized = [];
        foreach ($options as $key => $value) {
            if (is_int($key)) {
                // Simple array: ['option1', 'option2']
                $normalized[(string) $value] = (string) $value;
            } else {
                // Associative: ['value' => 'Label']
                $normalized[(string) $key] = (string) $value;
            }
        }
        return $normalized;
    }

    public function render(string $name, mixed $value, array $config, array $context = []): string
    {
        $id = 'field-' . $this->e($name);
        $options = $this->normalizeOptions($config['options'] ?? []);
        $multiple = $config['multiple'] ?? false;
        $emptyOption = $config['emptyOption'] ?? '— Select —';
        $selectedValues = $multiple ? (array) ($value ?? []) : [$value];
        
        $attrs = [
            'id' => $id,
            'name' => 'fields[' . $name . ']' . ($multiple ? '[]' : ''),
            'class' => 'form-control field-input',
            'required' => $config['required'] ?? false,
            'multiple' => $multiple,
            'data-field-type' => 'select',
        ];

        $input = '<select ' . $this->attributes($attrs) . '>';
        
        if (!$multiple && $emptyOption) {
            $input .= '<option value="">' . $this->e($emptyOption) . '</option>';
        }
        
        foreach ($options as $optValue => $optLabel) {
            $selected = in_array((string) $optValue, array_map('strval', $selectedValues), true);
            $input .= '<option value="' . $this->e($optValue) . '"' . ($selected ? ' selected' : '') . '>';
            $input .= $this->e($optLabel);
            $input .= '</option>';
        }
        
        $input .= '</select>';

        if ($multiple) {
            $input .= '<p class="field-hint-inline">Hold Ctrl/Cmd to select multiple</p>';
        }

        return $this->wrapField($name, $input, $config);
    }
}
