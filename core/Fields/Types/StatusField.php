<?php

declare(strict_types=1);

namespace Ava\Fields\Types;

use Ava\Fields\AbstractFieldType;
use Ava\Fields\ValidationResult;

/**
 * Status Field Type
 *
 * Content status selector (draft, published, unlisted).
 */
final class StatusField extends AbstractFieldType
{
    private const STATUSES = [
        'draft' => 'Draft',
        'published' => 'Published',
        'unlisted' => 'Unlisted',
    ];

    public function name(): string
    {
        return 'status';
    }

    public function label(): string
    {
        return 'Status';
    }

    public function schema(): array
    {
        return $this->baseSchema();
    }

    public function validate(mixed $value, array $config): ValidationResult
    {
        if (!is_string($value)) {
            return ValidationResult::error('Status must be a string.');
        }

        if (!isset(self::STATUSES[$value])) {
            return ValidationResult::error('Invalid status. Must be draft, published, or unlisted.');
        }

        return ValidationResult::success();
    }

    public function toStorage(mixed $value, array $config): mixed
    {
        // Default to draft if empty
        if ($value === null || $value === '') {
            return 'draft';
        }
        return $value;
    }

    public function defaultValue(array $config): mixed
    {
        return $config['default'] ?? 'draft';
    }

    public function render(string $name, mixed $value, array $config, array $context = []): string
    {
        $id = 'field-' . $this->e($name);
        $currentValue = $value ?? $this->defaultValue($config);
        
        $input = '<div class="status-toggle-group" id="' . $this->e($id) . '">';
        
        foreach (self::STATUSES as $statusValue => $statusLabel) {
            $checked = $currentValue === $statusValue;
            $input .= '<label class="status-toggle' . ($checked ? ' active' : '') . '" data-status="' . $this->e($statusValue) . '">';
            $input .= '<input type="radio" name="fields[' . $this->e($name) . ']" value="' . $this->e($statusValue) . '"';
            $input .= $checked ? ' checked' : '';
            $input .= '>';
            $input .= '<span class="status-label">' . $this->e($statusLabel) . '</span>';
            $input .= '</label>';
        }
        
        $input .= '</div>';

        return $this->wrapField($name, $input, $config);
    }

    public function javascript(): string
    {
        return <<<'JS'
// Status field - toggle active class
document.querySelectorAll('.status-toggle-group').forEach(function(group) {
    group.querySelectorAll('input[type="radio"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            group.querySelectorAll('.status-toggle').forEach(function(label) {
                label.classList.remove('active');
            });
            this.closest('.status-toggle').classList.add('active');
        });
    });
});
JS;
    }
}
