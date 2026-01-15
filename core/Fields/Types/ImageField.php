<?php

declare(strict_types=1);

namespace Ava\Fields\Types;

use Ava\Fields\AbstractFieldType;
use Ava\Fields\ValidationResult;

/**
 * Image Field Type
 *
 * Image picker/uploader that references images in the public folder.
 * Extends file field with image-specific validation and preview.
 */
final class ImageField extends AbstractFieldType
{
    /** @var array<string> Allowed image extensions */
    private const ALLOWED_EXTENSIONS = ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.svg', '.avif'];

    public function name(): string
    {
        return 'image';
    }

    public function label(): string
    {
        return 'Image';
    }

    public function schema(): array
    {
        return array_merge($this->baseSchema(), [
            'allowedTypes' => [
                'type' => 'array',
                'label' => 'Allowed Types',
                'description' => 'Allowed image extensions (default: jpg, png, gif, webp, svg, avif)',
                'default' => self::ALLOWED_EXTENSIONS,
            ],
            'basePath' => [
                'type' => 'string',
                'label' => 'Base Path',
                'description' => 'Base path within public folder (default: /media)',
                'default' => '/media',
            ],
            'showPreview' => [
                'type' => 'bool',
                'label' => 'Show Preview',
                'description' => 'Show image preview in editor',
                'default' => true,
            ],
        ]);
    }

    public function validate(mixed $value, array $config): ValidationResult
    {
        if (!is_string($value)) {
            return ValidationResult::error('Value must be an image path.');
        }

        if ($value === '') {
            return ValidationResult::success();
        }

        // Validate path doesn't contain traversal
        if (str_contains($value, '..')) {
            return ValidationResult::error('Invalid image path.');
        }

        // Validate extension
        $ext = strtolower(pathinfo($value, PATHINFO_EXTENSION));
        $allowed = $config['allowedTypes'] ?? self::ALLOWED_EXTENSIONS;
        
        // Normalize allowed types (remove dots if present, then check)
        $normalizedAllowed = array_map(fn($e) => ltrim($e, '.'), $allowed);
        
        if (!in_array($ext, $normalizedAllowed, true)) {
            return ValidationResult::error('Image type not allowed. Accepted: ' . implode(', ', $normalizedAllowed));
        }

        return ValidationResult::success();
    }

    public function toStorage(mixed $value, array $config): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Convert /media/ paths to @media: alias
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
        $showPreview = $config['showPreview'] ?? true;
        $allowed = $config['allowedTypes'] ?? self::ALLOWED_EXTENSIONS;
        $displayValue = $this->fromStorage($value, $config);
        
        $attrs = [
            'type' => 'text',
            'id' => $id,
            'name' => 'fields[' . $name . ']',
            'value' => $displayValue,
            'class' => 'form-control field-input field-image-path',
            'placeholder' => $config['placeholder'] ?? $basePath . '/image.jpg',
            'required' => $config['required'] ?? false,
            'data-field-type' => 'image',
            'data-allowed' => implode(',', $allowed),
            'data-base-path' => $basePath,
        ];

        $input = '<div class="image-input-group">';
        
        // Image preview
        if ($showPreview) {
            $input .= '<div class="image-preview" id="' . $this->e($id) . '-preview">';
            if ($displayValue) {
                $input .= '<img src="' . $this->e($displayValue) . '" alt="Preview">';
            } else {
                $input .= '<div class="image-placeholder"><span class="material-symbols-rounded">image</span></div>';
            }
            $input .= '</div>';
        }
        
        $input .= '<div class="image-input-controls">';
        $input .= '<input ' . $this->attributes($attrs) . '>';
        $input .= '<button type="button" class="btn btn-secondary btn-sm image-browse-btn" data-target="' . $this->e($id) . '">';
        $input .= '<span class="material-symbols-rounded">add_photo_alternate</span> Browse';
        $input .= '</button>';
        if ($displayValue) {
            $input .= '<button type="button" class="btn btn-danger-outline btn-sm image-clear-btn" data-target="' . $this->e($id) . '">';
            $input .= '<span class="material-symbols-rounded">close</span>';
            $input .= '</button>';
        }
        $input .= '</div>';
        $input .= '</div>';

        return $this->wrapField($name, $input, $config);
    }

    public function javascript(): string
    {
        return <<<'JS'
// Image field - browse and preview
document.querySelectorAll('.image-browse-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const targetId = this.dataset.target;
        const input = document.getElementById(targetId);
        const allowed = input.dataset.allowed || '.jpg,.png,.gif,.webp';
        const basePath = input.dataset.basePath || '/media';
        
        if (typeof openMediaPicker === 'function') {
            openMediaPicker({
                accept: allowed,
                basePath: basePath,
                type: 'image',
                onSelect: function(path) {
                    input.value = path;
                    updateImagePreview(targetId, path);
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        } else {
            input.focus();
        }
    });
});

document.querySelectorAll('.image-clear-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const targetId = this.dataset.target;
        const input = document.getElementById(targetId);
        input.value = '';
        updateImagePreview(targetId, '');
        input.dispatchEvent(new Event('change', { bubbles: true }));
    });
});

document.querySelectorAll('[data-field-type="image"]').forEach(function(input) {
    input.addEventListener('change', function() {
        updateImagePreview(this.id, this.value);
    });
});

function updateImagePreview(targetId, path) {
    const preview = document.getElementById(targetId + '-preview');
    if (preview) {
        if (path) {
            preview.innerHTML = '<img src="' + path + '" alt="Preview">';
        } else {
            preview.innerHTML = '<div class="image-placeholder"><span class="material-symbols-rounded">image</span></div>';
        }
    }
}
JS;
    }
}
