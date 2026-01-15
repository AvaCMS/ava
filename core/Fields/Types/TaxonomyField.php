<?php

declare(strict_types=1);

namespace Ava\Fields\Types;

use Ava\Fields\AbstractFieldType;
use Ava\Fields\ValidationResult;

/**
 * Taxonomy Field Type
 *
 * Taxonomy term selector with support for multiple terms.
 */
final class TaxonomyField extends AbstractFieldType
{
    public function name(): string
    {
        return 'taxonomy';
    }

    public function label(): string
    {
        return 'Taxonomy';
    }

    public function schema(): array
    {
        return array_merge($this->baseSchema(), [
            'taxonomy' => [
                'type' => 'string',
                'label' => 'Taxonomy',
                'description' => 'The taxonomy to select terms from',
                'required' => true,
            ],
            'multiple' => [
                'type' => 'bool',
                'label' => 'Multiple',
                'description' => 'Allow selecting multiple terms',
                'default' => true,
            ],
            'allowNew' => [
                'type' => 'bool',
                'label' => 'Allow New Terms',
                'description' => 'Allow entering terms not in the registry',
                'default' => true,
            ],
        ]);
    }

    public function validate(mixed $value, array $config): ValidationResult
    {
        $required = $config['required'] ?? false;
        $multiple = $config['multiple'] ?? true;

        // Check required
        $isEmpty = $value === null || $value === '' || (is_array($value) && count($value) === 0);
        if ($required && $isEmpty) {
            return ValidationResult::error('At least one term is required.');
        }

        // Allow empty
        if ($isEmpty) {
            return ValidationResult::success();
        }

        $warnings = [];

        if ($multiple) {
            if (!is_array($value)) {
                // Single value is okay, will be converted
                if (!is_string($value)) {
                    return ValidationResult::error('Terms must be strings.');
                }
                // Check slug format
                if (!preg_match('/^[a-z0-9-]+$/', $value)) {
                    $warnings[] = "Term '{$value}' is not in slug format (lowercase, hyphens only).";
                }
            } else {
                foreach ($value as $term) {
                    if (!is_string($term)) {
                        return ValidationResult::error('Each term must be a string.');
                    }
                    // Check slug format
                    if (!preg_match('/^[a-z0-9-]+$/', $term)) {
                        $warnings[] = "Term '{$term}' is not in slug format (lowercase, hyphens only).";
                    }
                }
            }
        } else {
            if (is_array($value)) {
                return ValidationResult::error('Only one term can be selected.');
            }
            // Check slug format for single value
            if (is_string($value) && !preg_match('/^[a-z0-9-]+$/', $value)) {
                $warnings[] = "Term '{$value}' is not in slug format (lowercase, hyphens only).";
            }
        }

        if (!empty($warnings)) {
            return ValidationResult::warning(implode(' ', $warnings));
        }

        return ValidationResult::success();
    }

    public function toStorage(mixed $value, array $config): mixed
    {
        $multiple = $config['multiple'] ?? true;

        if ($multiple) {
            if (!is_array($value)) {
                if ($value === null || $value === '') {
                    return [];
                }
                // Handle comma-separated string
                if (is_string($value) && str_contains($value, ',')) {
                    $items = array_map('trim', explode(',', $value));
                    return array_values(array_filter($items, fn($v) => $v !== ''));
                }
                return [$value];
            }
            // Filter empty values
            return array_values(array_filter($value, fn($v) => $v !== '' && $v !== null));
        }

        // Single value
        if (is_array($value)) {
            return $value[0] ?? null;
        }
        return $value === '' ? null : $value;
    }

    public function fromStorage(mixed $value, array $config): mixed
    {
        $multiple = $config['multiple'] ?? true;

        if ($multiple) {
            if (!is_array($value)) {
                return $value === null || $value === '' ? [] : [$value];
            }
            return $value;
        }

        if (is_array($value)) {
            return $value[0] ?? null;
        }
        return $value;
    }

    public function defaultValue(array $config): mixed
    {
        $multiple = $config['multiple'] ?? true;
        return $config['default'] ?? ($multiple ? [] : null);
    }

    public function render(string $name, mixed $value, array $config, array $context = []): string
    {
        $id = 'field-' . $this->e($name);
        $taxonomy = $config['taxonomy'] ?? 'category';
        $multiple = $config['multiple'] ?? true;
        $allowNew = $config['allowNew'] ?? true;
        
        // Terms should be passed via context
        $availableTerms = $context['availableTerms'][$taxonomy] ?? [];
        $selectedValues = $this->fromStorage($value, $config);
        if (!is_array($selectedValues)) {
            $selectedValues = [$selectedValues];
        }
        $selectedValues = array_filter($selectedValues, fn($v) => $v !== null && $v !== '');
        
        $attrs = [
            'id' => $id,
            'name' => 'fields[' . $name . ']' . ($multiple ? '[]' : ''),
            'class' => 'form-control field-input field-taxonomy',
            'multiple' => $multiple,
            'data-field-type' => 'taxonomy',
            'data-taxonomy' => $taxonomy,
            'data-allow-new' => $allowNew ? 'true' : 'false',
        ];

        $input = '<select ' . $this->attributes($attrs) . '>';
        
        if (!$multiple) {
            $input .= '<option value="">— Select —</option>';
        }
        
        foreach ($availableTerms as $slug => $termData) {
            $termName = is_array($termData) ? ($termData['name'] ?? $slug) : $termData;
            $selected = in_array((string) $slug, array_map('strval', $selectedValues), true);
            
            $input .= '<option value="' . $this->e($slug) . '"' . ($selected ? ' selected' : '') . '>';
            $input .= $this->e($termName);
            $input .= '</option>';
        }
        
        // Add selected values that aren't in the available terms (for allowNew)
        foreach ($selectedValues as $selectedValue) {
            if (!isset($availableTerms[$selectedValue])) {
                $input .= '<option value="' . $this->e($selectedValue) . '" selected>';
                $input .= $this->e($selectedValue);
                $input .= '</option>';
            }
        }
        
        $input .= '</select>';

        if ($multiple) {
            $input .= '<p class="field-hint-inline">Hold Ctrl/Cmd to select multiple</p>';
        }

        if ($allowNew && !empty($availableTerms)) {
            $input .= '<p class="field-hint-inline">Or enter terms as comma-separated values below:</p>';
            $input .= '<input type="text" class="form-control mt-2 taxonomy-custom-input" ';
            $input .= 'data-target="' . $this->e($id) . '" placeholder="term1, term2, term3" ';
            $input .= 'value="' . $this->e(implode(', ', $selectedValues)) . '">';
        } elseif ($allowNew && empty($availableTerms)) {
            // No predefined terms, show only text input
            $input = '<input type="text" id="' . $this->e($id) . '" ';
            $input .= 'name="fields[' . $this->e($name) . ']" ';
            $input .= 'class="form-control field-input" ';
            $input .= 'placeholder="term1, term2, term3" ';
            $input .= 'value="' . $this->e(implode(', ', $selectedValues)) . '">';
        }

        return $this->wrapField($name, $input, $config);
    }

    public function javascript(): string
    {
        return <<<'JS'
// Taxonomy field - sync custom input with select
document.querySelectorAll('.taxonomy-custom-input').forEach(function(input) {
    input.addEventListener('change', function() {
        const targetId = this.dataset.target;
        const select = document.getElementById(targetId);
        if (!select) return;
        
        // Parse comma-separated values
        const values = this.value.split(',').map(v => v.trim()).filter(v => v);
        
        // Update select options
        Array.from(select.options).forEach(function(opt) {
            opt.selected = values.includes(opt.value);
        });
        
        // Add new options for values not in select
        values.forEach(function(val) {
            if (!Array.from(select.options).some(opt => opt.value === val)) {
                const option = document.createElement('option');
                option.value = val;
                option.textContent = val;
                option.selected = true;
                select.appendChild(option);
            }
        });
    });
});

// Sync select changes back to custom input
document.querySelectorAll('.field-taxonomy').forEach(function(select) {
    select.addEventListener('change', function() {
        const customInput = document.querySelector('.taxonomy-custom-input[data-target="' + this.id + '"]');
        if (customInput) {
            const selectedValues = Array.from(this.selectedOptions).map(opt => opt.value);
            customInput.value = selectedValues.join(', ');
        }
    });
});
JS;
    }
}
