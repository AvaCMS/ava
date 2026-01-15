<?php

declare(strict_types=1);

namespace Ava\Fields\Types;

use Ava\Fields\AbstractFieldType;
use Ava\Fields\ValidationResult;

/**
 * Text Field Type
 *
 * Single-line text input with optional length limits.
 */
final class TextField extends AbstractFieldType
{
    public function name(): string
    {
        return 'text';
    }

    public function label(): string
    {
        return 'Text';
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
            'pattern' => [
                'type' => 'string',
                'label' => 'Pattern',
                'description' => 'Regular expression pattern for validation',
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

        if (isset($config['pattern'])) {
            $pattern = $config['pattern'];
            // Add delimiters if not already present
            if ($pattern !== '' && $pattern[0] !== '/') {
                $pattern = '/' . $pattern . '/';
            }
            if (!preg_match($pattern, $value)) {
                return ValidationResult::error(
                    $config['patternMessage'] ?? 'Value does not match required format.'
                );
            }
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
        $attrs = [
            'type' => 'text',
            'id' => $id,
            'name' => 'fields[' . $name . ']',
            'value' => $value ?? '',
            'class' => 'form-control field-input',
            'placeholder' => $config['placeholder'] ?? null,
            'required' => $config['required'] ?? false,
            'minlength' => $config['minLength'] ?? null,
            'maxlength' => $config['maxLength'] ?? null,
            'pattern' => isset($config['pattern']) ? trim($config['pattern'], '/') : null,
            'data-field-type' => 'text',
        ];

        $input = '<input ' . $this->attributes($attrs) . '>';

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
// Text field - character counter
document.querySelectorAll('[data-field-type="text"]').forEach(function(input) {
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
