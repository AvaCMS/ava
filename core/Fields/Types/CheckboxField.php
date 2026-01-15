<?php

declare(strict_types=1);

namespace Ava\Fields\Types;

use Ava\Fields\AbstractFieldType;
use Ava\Fields\ValidationResult;

/**
 * Checkbox Field Type
 *
 * Boolean toggle input.
 */
final class CheckboxField extends AbstractFieldType
{
    public function name(): string
    {
        return 'checkbox';
    }

    public function label(): string
    {
        return 'Checkbox';
    }

    public function schema(): array
    {
        return array_merge($this->baseSchema(), [
            'checkboxLabel' => [
                'type' => 'string',
                'label' => 'Checkbox Label',
                'description' => 'Text shown next to the checkbox',
            ],
        ]);
    }

    public function validate(mixed $value, array $config): ValidationResult
    {
        // Checkbox values can be bool, "true", "false", 0, 1, etc.
        // We consider all of these valid
        return ValidationResult::success();
    }

    public function toStorage(mixed $value, array $config): mixed
    {
        // Convert to boolean for YAML storage
        if ($value === 'true' || $value === '1' || $value === 1 || $value === true || $value === 'on') {
            return true;
        }
        return false;
    }

    public function fromStorage(mixed $value, array $config): mixed
    {
        return (bool) $value;
    }

    public function defaultValue(array $config): mixed
    {
        return (bool) ($config['default'] ?? false);
    }

    public function render(string $name, mixed $value, array $config, array $context = []): string
    {
        $id = 'field-' . $this->e($name);
        $checked = $this->toStorage($value, $config);
        $checkboxLabel = $config['checkboxLabel'] ?? $config['label'] ?? ucfirst(str_replace(['_', '-'], ' ', $name));
        
        // Hidden input to ensure false is sent when unchecked
        $input = '<input type="hidden" name="fields[' . $this->e($name) . ']" value="false">';
        
        $attrs = [
            'type' => 'checkbox',
            'id' => $id,
            'name' => 'fields[' . $name . ']',
            'value' => 'true',
            'class' => 'form-checkbox',
            'checked' => $checked,
            'data-field-type' => 'checkbox',
        ];

        $input .= '<label class="checkbox-label">';
        $input .= '<input ' . $this->attributes($attrs) . '>';
        $input .= '<span>' . $this->e($checkboxLabel) . '</span>';
        $input .= '</label>';

        // Wrap without duplicate label
        $description = $config['description'] ?? null;
        $html = '<div class="field-group field-group-checkbox" data-field="' . $this->e($name) . '" data-type="checkbox">';
        $html .= $input;
        if ($description) {
            $html .= '<p class="field-hint">' . $this->e($description) . '</p>';
        }
        $html .= '<p class="field-error" hidden></p>';
        $html .= '</div>';

        return $html;
    }
}
