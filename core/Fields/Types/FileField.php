<?php

declare(strict_types=1);

namespace Ava\Fields\Types;

use Ava\Fields\AbstractFieldType;
use Ava\Fields\ValidationResult;

/**
 * File Field Type
 *
 * File picker/uploader that references files in the public folder.
 */
final class FileField extends AbstractFieldType
{
    public function name(): string
    {
        return 'file';
    }

    public function label(): string
    {
        return 'File';
    }

    public function schema(): array
    {
        return array_merge($this->baseSchema(), [
            'accept' => [
                'type' => 'string',
                'label' => 'Accepted Types',
                'description' => 'Comma-separated file extensions or MIME types (e.g., .pdf,.doc,application/pdf)',
            ],
            'basePath' => [
                'type' => 'string',
                'label' => 'Base Path',
                'description' => 'Base path within public folder (default: /media)',
                'default' => '/media',
            ],
        ]);
    }

    public function validate(mixed $value, array $config): ValidationResult
    {
        if (!is_string($value)) {
            return ValidationResult::error('Value must be a file path.');
        }

        // Validate path doesn't contain traversal
        if (str_contains($value, '..')) {
            return ValidationResult::error('Invalid file path.');
        }

        // If accept is specified, validate extension
        if (isset($config['accept']) && $value !== '') {
            $accept = array_map('trim', explode(',', $config['accept']));
            $ext = '.' . strtolower(pathinfo($value, PATHINFO_EXTENSION));
            
            // Filter to just extensions (start with .)
            $acceptedExtensions = array_filter($accept, fn($a) => str_starts_with($a, '.'));
            
            if (!empty($acceptedExtensions) && !in_array($ext, $acceptedExtensions, true)) {
                return ValidationResult::error('File type not allowed. Accepted: ' . implode(', ', $acceptedExtensions));
            }
        }

        return ValidationResult::success();
    }

    public function toStorage(mixed $value, array $config): mixed
    {
        // Store the path, potentially with @media: alias
        if ($value === null || $value === '') {
            return null;
        }

        // If the path starts with /media/, convert to @media: alias
        if (str_starts_with($value, '/media/')) {
            return '@media:' . substr($value, 7);
        }

        return $value;
    }

    public function fromStorage(mixed $value, array $config): mixed
    {
        if ($value === null) {
            return '';
        }

        // Expand @media: alias for editing
        if (is_string($value) && str_starts_with($value, '@media:')) {
            return '/media/' . substr($value, 7);
        }

        return $value;
    }

    public function render(string $name, mixed $value, array $config, array $context = []): string
    {
        $id = 'field-' . $this->e($name);
        $basePath = $config['basePath'] ?? '/media';
        $accept = $config['accept'] ?? '*/*';
        $displayValue = $this->fromStorage($value, $config);
        
        $attrs = [
            'type' => 'text',
            'id' => $id,
            'name' => 'fields[' . $name . ']',
            'value' => $displayValue,
            'class' => 'form-control field-input field-file-path',
            'placeholder' => $config['placeholder'] ?? $basePath . '/filename.ext',
            'required' => $config['required'] ?? false,
            'data-field-type' => 'file',
            'data-accept' => $accept,
            'data-base-path' => $basePath,
        ];

        $input = '<div class="file-input-group">';
        $input .= '<input ' . $this->attributes($attrs) . '>';
        $input .= '<button type="button" class="btn btn-secondary btn-sm file-browse-btn" data-target="' . $this->e($id) . '">';
        $input .= '<span class="material-symbols-rounded">folder_open</span> Browse';
        $input .= '</button>';
        $input .= '</div>';

        // Show current file preview if exists
        if ($displayValue) {
            $input .= '<div class="file-preview">';
            $input .= '<a href="' . $this->e($displayValue) . '" target="_blank" rel="noopener">' . $this->e(basename($displayValue)) . '</a>';
            $input .= '</div>';
        }

        return $this->wrapField($name, $input, $config);
    }

    public function javascript(): string
    {
        return <<<'JS'
// File field - browse button opens media picker modal
document.querySelectorAll('.file-browse-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const targetId = this.dataset.target;
        const input = document.getElementById(targetId);
        const accept = input.dataset.accept || '*/*';
        const basePath = input.dataset.basePath || '/media';
        
        if (typeof openMediaPicker === 'function') {
            openMediaPicker({
                accept: accept,
                basePath: basePath,
                onSelect: function(path) {
                    input.value = path;
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        } else {
            // Fallback: just focus the text input
            input.focus();
        }
    });
});
JS;
    }
}
