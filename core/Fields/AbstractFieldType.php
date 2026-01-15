<?php

declare(strict_types=1);

namespace Ava\Fields;

/**
 * Abstract Field Type
 *
 * Base class with common functionality for all field types.
 */
abstract class AbstractFieldType implements FieldType
{
    /**
     * {@inheritdoc}
     */
    public function toStorage(mixed $value, array $config): mixed
    {
        // Most fields store values as-is
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function fromStorage(mixed $value, array $config): mixed
    {
        // Most fields read values as-is
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function defaultValue(array $config): mixed
    {
        return $config['default'] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function javascript(): string
    {
        return '';
    }

    /**
     * Get the base schema common to all field types.
     */
    protected function baseSchema(): array
    {
        return [
            'label' => [
                'type' => 'string',
                'label' => 'Label',
                'description' => 'Display label for the field',
            ],
            'description' => [
                'type' => 'string',
                'label' => 'Description',
                'description' => 'Help text shown below the field',
            ],
            'required' => [
                'type' => 'bool',
                'label' => 'Required',
                'description' => 'Whether this field must have a value',
                'default' => false,
            ],
            'placeholder' => [
                'type' => 'string',
                'label' => 'Placeholder',
                'description' => 'Placeholder text for the input',
            ],
            'default' => [
                'type' => 'mixed',
                'label' => 'Default Value',
                'description' => 'Default value when creating new content',
            ],
        ];
    }

    /**
     * Escape HTML for safe output.
     */
    protected function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Render wrapper with label and description.
     */
    protected function wrapField(string $name, string $input, array $config): string
    {
        $label = $config['label'] ?? ucfirst(str_replace(['_', '-'], ' ', $name));
        $description = $config['description'] ?? null;
        $required = $config['required'] ?? false;
        $id = 'field-' . $this->e($name);

        $html = '<div class="field-group" data-field="' . $this->e($name) . '" data-type="' . $this->e($this->name()) . '">';
        $html .= '<label class="field-label" for="' . $id . '">';
        $html .= $this->e($label);
        if ($required) {
            $html .= ' <span class="field-required">*</span>';
        }
        $html .= '</label>';
        $html .= $input;
        if ($description) {
            $html .= '<p class="field-hint">' . $this->e($description) . '</p>';
        }
        $html .= '<p class="field-error" hidden></p>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Build HTML attributes string from array.
     */
    protected function attributes(array $attrs): string
    {
        $parts = [];
        foreach ($attrs as $key => $value) {
            if ($value === true) {
                $parts[] = $this->e($key);
            } elseif ($value !== false && $value !== null) {
                $parts[] = $this->e($key) . '="' . $this->e($value) . '"';
            }
        }
        return implode(' ', $parts);
    }
}
