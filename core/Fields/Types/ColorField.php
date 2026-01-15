<?php

declare(strict_types=1);

namespace Ava\Fields\Types;

use Ava\Fields\AbstractFieldType;
use Ava\Fields\ValidationResult;

/**
 * Color Field Type
 *
 * Color picker with multiple format support (hex, rgb, rgba, hsl).
 */
final class ColorField extends AbstractFieldType
{
    public function name(): string
    {
        return 'color';
    }

    public function label(): string
    {
        return 'Color';
    }

    public function schema(): array
    {
        return array_merge($this->baseSchema(), [
            'format' => [
                'type' => 'string',
                'label' => 'Color Format',
                'description' => 'Storage format for the color value',
                'default' => 'hex',
                'options' => ['hex', 'rgb', 'rgba', 'hsl'],
            ],
            'alpha' => [
                'type' => 'bool',
                'label' => 'Allow Transparency',
                'description' => 'Allow alpha/transparency values',
                'default' => false,
            ],
        ]);
    }

    public function validate(mixed $value, array $config): ValidationResult
    {
        if (!is_string($value)) {
            return ValidationResult::error('Value must be a color string.');
        }

        $format = $config['format'] ?? 'hex';
        $alpha = $config['alpha'] ?? false;

        // Validate based on format
        switch ($format) {
            case 'hex':
                if (!preg_match('/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6}|[0-9A-Fa-f]{8})$/', $value)) {
                    return ValidationResult::error('Invalid hex color format. Use #RGB, #RRGGBB' . ($alpha ? ' or #RRGGBBAA' : '') . '.');
                }
                break;
                
            case 'rgb':
            case 'rgba':
                $pattern = $alpha 
                    ? '/^rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*(,\s*(0|1|0?\.\d+))?\s*\)$/i'
                    : '/^rgb\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*\)$/i';
                if (!preg_match($pattern, $value)) {
                    return ValidationResult::error('Invalid RGB format. Use ' . ($alpha ? 'rgba(r, g, b, a)' : 'rgb(r, g, b)') . '.');
                }
                break;
                
            case 'hsl':
                $pattern = $alpha 
                    ? '/^hsla?\(\s*\d{1,3}\s*,\s*\d{1,3}%\s*,\s*\d{1,3}%\s*(,\s*(0|1|0?\.\d+))?\s*\)$/i'
                    : '/^hsl\(\s*\d{1,3}\s*,\s*\d{1,3}%\s*,\s*\d{1,3}%\s*\)$/i';
                if (!preg_match($pattern, $value)) {
                    return ValidationResult::error('Invalid HSL format. Use ' . ($alpha ? 'hsla(h, s%, l%, a)' : 'hsl(h, s%, l%)') . '.');
                }
                break;
        }

        return ValidationResult::success();
    }

    public function defaultValue(array $config): mixed
    {
        return $config['default'] ?? '#000000';
    }

    public function render(string $name, mixed $value, array $config, array $context = []): string
    {
        $id = 'field-' . $this->e($name);
        $format = $config['format'] ?? 'hex';
        $alpha = $config['alpha'] ?? false;
        
        // For non-hex formats, show text input with color preview
        $inputType = $format === 'hex' && !$alpha ? 'color' : 'text';
        $placeholder = match ($format) {
            'hex' => $alpha ? '#RRGGBBAA' : '#RRGGBB',
            'rgb' => $alpha ? 'rgba(255, 0, 0, 0.5)' : 'rgb(255, 0, 0)',
            'rgba' => 'rgba(255, 0, 0, 0.5)',
            'hsl' => $alpha ? 'hsla(0, 100%, 50%, 0.5)' : 'hsl(0, 100%, 50%)',
            default => '#RRGGBB',
        };

        $attrs = [
            'type' => $inputType,
            'id' => $id,
            'name' => 'fields[' . $name . ']',
            'value' => $value ?? $this->defaultValue($config),
            'class' => 'form-control field-input field-color',
            'placeholder' => $config['placeholder'] ?? $placeholder,
            'required' => $config['required'] ?? false,
            'data-field-type' => 'color',
            'data-format' => $format,
        ];

        $input = '<div class="color-input-group">';
        $input .= '<input ' . $this->attributes($attrs) . '>';
        
        // For text inputs, add a color preview swatch
        if ($inputType === 'text') {
            $input .= '<span class="color-preview" style="background-color: ' . $this->e($value ?? '') . '"></span>';
        }
        
        $input .= '</div>';

        return $this->wrapField($name, $input, $config);
    }

    public function javascript(): string
    {
        return <<<'JS'
// Color field - update preview swatch
document.querySelectorAll('[data-field-type="color"]').forEach(function(input) {
    const preview = input.parentElement.querySelector('.color-preview');
    if (preview) {
        input.addEventListener('input', function() {
            preview.style.backgroundColor = this.value;
        });
    }
});
JS;
    }
}
