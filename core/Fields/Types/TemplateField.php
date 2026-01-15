<?php

declare(strict_types=1);

namespace Ava\Fields\Types;

use Ava\Fields\AbstractFieldType;
use Ava\Fields\ValidationResult;

/**
 * Template Field Type
 *
 * Template file selector based on available theme templates.
 */
final class TemplateField extends AbstractFieldType
{
    public function name(): string
    {
        return 'template';
    }

    public function label(): string
    {
        return 'Template';
    }

    public function schema(): array
    {
        return array_merge($this->baseSchema(), [
            'defaultTemplate' => [
                'type' => 'string',
                'label' => 'Default Template',
                'description' => 'The default template to use if none is selected',
            ],
        ]);
    }

    public function validate(mixed $value, array $config): ValidationResult
    {
        if ($value !== null && !is_string($value)) {
            return ValidationResult::error('Template must be a string.');
        }

        // Empty is allowed (use default template)
        if ($value === null || $value === '') {
            return ValidationResult::success();
        }

        // Basic security check - no path traversal
        if (str_contains($value, '..') || str_contains($value, '/')) {
            return ValidationResult::error('Invalid template name.');
        }

        // Must end with .php
        if (!str_ends_with($value, '.php')) {
            return ValidationResult::error('Template must be a .php file.');
        }

        // Actual template existence is validated at the application level
        return ValidationResult::success();
    }

    public function toStorage(mixed $value, array $config): mixed
    {
        return $value === '' ? null : $value;
    }

    public function render(string $name, mixed $value, array $config, array $context = []): string
    {
        $id = 'field-' . $this->e($name);
        
        // Templates should be passed via context
        $templates = $context['templates'] ?? [];
        $defaultTemplate = $config['defaultTemplate'] ?? null;
        
        $attrs = [
            'id' => $id,
            'name' => 'fields[' . $name . ']',
            'class' => 'form-control field-input',
            'data-field-type' => 'template',
        ];

        $input = '<select ' . $this->attributes($attrs) . '>';
        $input .= '<option value="">— Default Template —</option>';
        
        foreach ($templates as $template) {
            $selected = $value === $template;
            $isDefault = $template === $defaultTemplate;
            $label = $template . ($isDefault ? ' (default)' : '');
            
            $input .= '<option value="' . $this->e($template) . '"' . ($selected ? ' selected' : '') . '>';
            $input .= $this->e($label);
            $input .= '</option>';
        }
        
        $input .= '</select>';

        return $this->wrapField($name, $input, $config);
    }
}
