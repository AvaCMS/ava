<?php

declare(strict_types=1);

namespace Ava\Fields\Types;

use Ava\Fields\AbstractFieldType;
use Ava\Fields\ValidationResult;

/**
 * Textarea Field Type
 *
 * Multi-line text input with optional length limits.
 */
final class TextareaField extends AbstractFieldType
{
    public function name(): string
    {
        return 'textarea';
    }

    public function label(): string
    {
        return 'Textarea';
    }

    public function schema(): array
    {
        return array_merge($this->baseSchema(), [
            'minLength' => [
                'type' => 'int',
                'label' => 'Minimum Length',
                'description' => 'Minimum number of characters',
            ],
            'maxLength' => [
                'type' => 'int',
                'label' => 'Maximum Length',
                'description' => 'Maximum number of characters',
            ],
            'rows' => [
                'type' => 'int',
                'label' => 'Rows',
                'description' => 'Number of visible text rows',
                'default' => 4,
            ],
        ]);
    }

    public function validate(mixed $value, array $config): ValidationResult
    {
        if (!is_string($value)) {
            return ValidationResult::error('Value must be text.');
        }

        $length = mb_strlen($value);

        if (isset($config['minLength']) && $length < $config['minLength']) {
            return ValidationResult::error(
                "Must be at least {$config['minLength']} characters."
            );
        }

        if (isset($config['maxLength']) && $length > $config['maxLength']) {
            return ValidationResult::error(
                "Must be no more than {$config['maxLength']} characters."
            );
        }

        return ValidationResult::success();
    }

    public function defaultValue(array $config): mixed
    {
        return $config['default'] ?? '';
    }

    public function render(string $name, mixed $value, array $config, array $context = []): string
    {
        $id = 'field-' . $this->e($name);
        $rows = $config['rows'] ?? 4;
        
        $attrs = [
            'id' => $id,
            'name' => 'fields[' . $name . ']',
            'class' => 'form-control field-input',
            'placeholder' => $config['placeholder'] ?? null,
            'required' => $config['required'] ?? false,
            'minlength' => $config['minLength'] ?? null,
            'maxlength' => $config['maxLength'] ?? null,
            'rows' => $rows,
            'data-field-type' => 'textarea',
        ];

        $input = '<textarea ' . $this->attributes($attrs) . '>' . $this->e($value ?? '') . '</textarea>';

        // Show character count if max length is set
        if (isset($config['maxLength'])) {
            $current = mb_strlen((string) $value);
            $max = $config['maxLength'];
            $input .= '<span class="field-char-count"><span class="current">' . $current . '</span>/' . $max . '</span>';
        }

        return $this->wrapField($name, $input, $config);
    }

    public function javascript(): string
    {
        return <<<'JS'
// Textarea field - character counter
document.querySelectorAll('[data-field-type="textarea"]').forEach(function(input) {
    const counter = input.parentElement.querySelector('.field-char-count .current');
    if (counter) {
        input.addEventListener('input', function() {
            counter.textContent = this.value.length;
        });
    }
});
JS;
    }
}
