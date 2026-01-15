<?php

declare(strict_types=1);

namespace Ava\Fields\Types;

use Ava\Fields\AbstractFieldType;
use Ava\Fields\ValidationResult;

/**
 * Gallery Field Type
 *
 * Multiple image picker for galleries.
 */
final class GalleryField extends AbstractFieldType
{
    /** @var array<string> Allowed image extensions */
    private const ALLOWED_EXTENSIONS = ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.svg', '.avif'];

    public function name(): string
    {
        return 'gallery';
    }

    public function label(): string
    {
        return 'Gallery';
    }

    public function schema(): array
    {
        return array_merge($this->baseSchema(), [
            'allowedTypes' => [
                'type' => 'array',
                'label' => 'Allowed Types',
                'description' => 'Allowed image extensions',
                'default' => self::ALLOWED_EXTENSIONS,
            ],
            'basePath' => [
                'type' => 'string',
                'label' => 'Base Path',
                'description' => 'Base path within public folder (default: /media)',
                'default' => '/media',
            ],
            'minItems' => [
                'type' => 'int',
                'label' => 'Minimum Images',
                'description' => 'Minimum number of images required',
            ],
            'maxItems' => [
                'type' => 'int',
                'label' => 'Maximum Images',
                'description' => 'Maximum number of images allowed',
            ],
        ]);
    }

    public function validate(mixed $value, array $config): ValidationResult
    {
        if (!is_array($value)) {
            return ValidationResult::error('Value must be an array of image paths.');
        }

        $allowed = $config['allowedTypes'] ?? self::ALLOWED_EXTENSIONS;
        $count = count($value);

        if (isset($config['minItems']) && $count < $config['minItems']) {
            return ValidationResult::error("At least {$config['minItems']} image(s) required.");
        }

        if (isset($config['maxItems']) && $count > $config['maxItems']) {
            return ValidationResult::error("No more than {$config['maxItems']} image(s) allowed.");
        }

        foreach ($value as $index => $path) {
            if (!is_string($path)) {
                return ValidationResult::error("Item " . ($index + 1) . " must be an image path.");
            }

            if ($path === '') {
                continue;
            }

            if (str_contains($path, '..')) {
                return ValidationResult::error("Item " . ($index + 1) . " has an invalid path.");
            }

            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $normalizedAllowed = array_map(fn($e) => ltrim($e, '.'), $allowed);
            if (!in_array($ext, $normalizedAllowed, true)) {
                return ValidationResult::error("Item " . ($index + 1) . ": type not allowed. Accepted: " . implode(', ', $normalizedAllowed));
            }
        }

        return ValidationResult::success();
    }

    public function toStorage(mixed $value, array $config): mixed
    {
        if (!is_array($value)) {
            return [];
        }

        // Filter empty values and convert to @media: aliases
        return array_values(array_map(function($path) {
            if ($path === null || $path === '') {
                return null;
            }
            if (str_starts_with($path, '/media/')) {
                return '@media:' . substr($path, 7);
            }
            return $path;
        }, array_filter($value, fn($v) => $v !== null && $v !== '')));
    }

    public function fromStorage(mixed $value, array $config): mixed
    {
        if (!is_array($value)) {
            return $value === null ? [] : [$value];
        }

        // Expand @media: aliases
        return array_map(function($path) {
            if (is_string($path) && str_starts_with($path, '@media:')) {
                return '/media/' . substr($path, 7);
            }
            return $path;
        }, $value);
    }

    public function defaultValue(array $config): mixed
    {
        return $config['default'] ?? [];
    }

    public function render(string $name, mixed $value, array $config, array $context = []): string
    {
        $id = 'field-' . $this->e($name);
        $basePath = $config['basePath'] ?? '/media';
        $allowed = $config['allowedTypes'] ?? self::ALLOWED_EXTENSIONS;
        $images = $this->fromStorage($value ?? [], $config);
        $maxItems = $config['maxItems'] ?? null;
        
        $input = '<div class="gallery-input-group" id="' . $this->e($id) . '-container" ';
        $input .= 'data-field="' . $this->e($name) . '" ';
        $input .= 'data-base-path="' . $this->e($basePath) . '" ';
        $input .= 'data-allowed="' . $this->e(implode(',', $allowed)) . '"';
        if ($maxItems) {
            $input .= ' data-max-items="' . $maxItems . '"';
        }
        $input .= '>';
        
        // Image grid
        $input .= '<div class="gallery-grid" id="' . $this->e($id) . '-grid">';
        foreach ($images as $index => $imagePath) {
            $input .= $this->renderGalleryItem($name, $index, $imagePath);
        }
        $input .= '</div>';
        
        // Add button
        $input .= '<button type="button" class="btn btn-secondary btn-sm gallery-add-btn" ';
        $input .= 'data-target="' . $this->e($id) . '"';
        if ($maxItems && count($images) >= $maxItems) {
            $input .= ' disabled';
        }
        $input .= '>';
        $input .= '<span class="material-symbols-rounded">add_photo_alternate</span> Add Image';
        $input .= '</button>';
        
        $input .= '</div>';

        return $this->wrapField($name, $input, $config);
    }

    private function renderGalleryItem(string $name, int $index, string $path): string
    {
        $html = '<div class="gallery-item" data-index="' . $index . '">';
        $html .= '<input type="hidden" name="fields[' . $this->e($name) . '][]" value="' . $this->e($path) . '">';
        $html .= '<img src="' . $this->e($path) . '" alt="Image ' . ($index + 1) . '">';
        $html .= '<div class="gallery-item-actions">';
        $html .= '<button type="button" class="gallery-item-remove" title="Remove"><span class="material-symbols-rounded">close</span></button>';
        $html .= '<button type="button" class="gallery-item-move-up" title="Move Up"><span class="material-symbols-rounded">arrow_upward</span></button>';
        $html .= '<button type="button" class="gallery-item-move-down" title="Move Down"><span class="material-symbols-rounded">arrow_downward</span></button>';
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }

    public function javascript(): string
    {
        return <<<'JS'
// Gallery field - add, remove, reorder images
document.querySelectorAll('.gallery-add-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const targetId = this.dataset.target;
        const container = document.getElementById(targetId + '-container');
        const grid = document.getElementById(targetId + '-grid');
        const fieldName = container.dataset.field;
        const allowed = container.dataset.allowed || '.jpg,.png,.gif,.webp';
        const basePath = container.dataset.basePath || '/media';
        const maxItems = parseInt(container.dataset.maxItems) || Infinity;
        
        const currentCount = grid.querySelectorAll('.gallery-item').length;
        if (currentCount >= maxItems) {
            return;
        }
        
        if (typeof openMediaPicker === 'function') {
            openMediaPicker({
                accept: allowed,
                basePath: basePath,
                type: 'image',
                onSelect: function(path) {
                    const index = grid.querySelectorAll('.gallery-item').length;
                    const item = document.createElement('div');
                    item.className = 'gallery-item';
                    item.dataset.index = index;
                    item.innerHTML = '<input type="hidden" name="fields[' + fieldName + '][]" value="' + path + '">' +
                        '<img src="' + path + '" alt="Image">' +
                        '<div class="gallery-item-actions">' +
                        '<button type="button" class="gallery-item-remove" title="Remove"><span class="material-symbols-rounded">close</span></button>' +
                        '<button type="button" class="gallery-item-move-up" title="Move Up"><span class="material-symbols-rounded">arrow_upward</span></button>' +
                        '<button type="button" class="gallery-item-move-down" title="Move Down"><span class="material-symbols-rounded">arrow_downward</span></button>' +
                        '</div>';
                    grid.appendChild(item);
                    initGalleryItemButtons(item);
                    
                    // Disable add button if max reached
                    if (grid.querySelectorAll('.gallery-item').length >= maxItems) {
                        btn.disabled = true;
                    }
                }
            });
        }
    });
});

function initGalleryItemButtons(item) {
    item.querySelector('.gallery-item-remove').addEventListener('click', function() {
        const grid = item.closest('.gallery-grid');
        const container = item.closest('.gallery-input-group');
        item.remove();
        // Re-enable add button
        const addBtn = container.querySelector('.gallery-add-btn');
        const maxItems = parseInt(container.dataset.maxItems) || Infinity;
        if (grid.querySelectorAll('.gallery-item').length < maxItems) {
            addBtn.disabled = false;
        }
    });
    
    item.querySelector('.gallery-item-move-up').addEventListener('click', function() {
        const prev = item.previousElementSibling;
        if (prev) {
            item.parentNode.insertBefore(item, prev);
        }
    });
    
    item.querySelector('.gallery-item-move-down').addEventListener('click', function() {
        const next = item.nextElementSibling;
        if (next) {
            item.parentNode.insertBefore(next, item);
        }
    });
}

// Init existing items
document.querySelectorAll('.gallery-item').forEach(initGalleryItemButtons);
JS;
    }
}
