<?php

declare(strict_types=1);

namespace Ava\Fields\Types;

use Ava\Fields\AbstractFieldType;
use Ava\Fields\ValidationResult;

/**
 * Content Field Type
 *
 * Reference to another piece of content on the site.
 */
final class ContentField extends AbstractFieldType
{
    public function name(): string
    {
        return 'content';
    }

    public function label(): string
    {
        return 'Content Reference';
    }

    public function schema(): array
    {
        return array_merge($this->baseSchema(), [
            'contentType' => [
                'type' => 'string',
                'label' => 'Content Type',
                'description' => 'The type of content to reference (e.g., post, page)',
                'required' => true,
            ],
            'multiple' => [
                'type' => 'bool',
                'label' => 'Multiple',
                'description' => 'Allow selecting multiple content items',
                'default' => false,
            ],
            'displayField' => [
                'type' => 'string',
                'label' => 'Display Field',
                'description' => 'Field to show in dropdown (default: title)',
                'default' => 'title',
            ],
            'valueField' => [
                'type' => 'string',
                'label' => 'Value Field',
                'description' => 'Field to store (default: slug)',
                'default' => 'slug',
            ],
        ]);
    }

    public function validate(mixed $value, array $config): ValidationResult
    {
        $multiple = $config['multiple'] ?? false;

        if ($multiple) {
            if (!is_array($value)) {
                return ValidationResult::error('Value must be an array of content references.');
            }
            foreach ($value as $v) {
                if (!is_string($v) || $v === '') {
                    return ValidationResult::error('Each reference must be a non-empty string.');
                }
            }
        } else {
            if (!is_string($value)) {
                return ValidationResult::error('Value must be a content reference string.');
            }
        }

        // Note: Actual existence validation should be done at a higher level
        // where we have access to the content repository

        return ValidationResult::success();
    }

    public function toStorage(mixed $value, array $config): mixed
    {
        $multiple = $config['multiple'] ?? false;

        if ($multiple) {
            if (!is_array($value)) {
                return [];
            }
            return array_values(array_filter($value, fn($v) => is_string($v) && $v !== ''));
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

    public function render(string $name, mixed $value, array $config, array $context = []): string
    {
        $id = 'field-' . $this->e($name);
        $contentType = $config['contentType'] ?? 'post';
        $multiple = $config['multiple'] ?? false;
        $displayField = $config['displayField'] ?? 'title';
        $valueField = $config['valueField'] ?? 'slug';
        
        // The actual content items should be passed via context
        $contentItems = $context['contentItems'][$contentType] ?? [];
        $selectedValues = $multiple ? (array) ($value ?? []) : [$value];
        
        $attrs = [
            'id' => $id,
            'name' => 'fields[' . $name . ']' . ($multiple ? '[]' : ''),
            'class' => 'form-control field-input field-content-ref',
            'required' => $config['required'] ?? false,
            'multiple' => $multiple,
            'data-field-type' => 'content',
            'data-content-type' => $contentType,
        ];

        $input = '<select ' . $this->attributes($attrs) . '>';
        
        if (!$multiple) {
            $input .= '<option value="">— Select ' . $this->e(ucfirst($contentType)) . ' —</option>';
        }
        
        foreach ($contentItems as $item) {
            $itemValue = $item[$valueField] ?? $item['slug'] ?? '';
            $itemLabel = $item[$displayField] ?? $item['title'] ?? $itemValue;
            $selected = in_array((string) $itemValue, array_map('strval', $selectedValues), true);
            
            $input .= '<option value="' . $this->e($itemValue) . '"' . ($selected ? ' selected' : '') . '>';
            $input .= $this->e($itemLabel);
            $input .= '</option>';
        }
        
        $input .= '</select>';

        if ($multiple) {
            $input .= '<p class="field-hint-inline">Hold Ctrl/Cmd to select multiple</p>';
        }

        return $this->wrapField($name, $input, $config);
    }
}
