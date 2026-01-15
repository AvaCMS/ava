# Field Validation System

Ava CMS includes a powerful field validation system that allows you to define typed fields in your content type configuration. These fields are validated by the linter and exposed in the admin panel's focused editor with appropriate front-end validation.

## Overview

Fields provide:
- **Schema definition** in `content_types.php`
- **Linter validation** via `./ava lint`
- **Admin UI rendering** with type-appropriate inputs
- **JavaScript validation** for real-time feedback
- **Storage conversion** to/from frontmatter values

## Configuration

Define fields in your content type configuration:

```php
// app/config/content_types.php
return [
    'post' => [
        'label' => 'Posts',
        'content_dir' => 'posts',
        // ...
        'fields' => [
            'author' => [
                'type' => 'text',
                'label' => 'Author Name',
                'required' => true,
                'minLength' => 2,
                'maxLength' => 100,
            ],
            'featured' => [
                'type' => 'checkbox',
                'label' => 'Featured Post',
                'description' => 'Show this post on the homepage',
            ],
            'publish_date' => [
                'type' => 'date',
                'label' => 'Publish Date',
                'required' => true,
            ],
            'featured_image' => [
                'type' => 'image',
                'label' => 'Featured Image',
                'description' => 'Main image for this post',
                'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
            ],
        ],
    ],
];
```

## Common Field Options

All field types support these options:

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `type` | string | required | Field type identifier |
| `label` | string | field name | Human-readable label |
| `description` | string | null | Help text shown below the field |
| `required` | bool | false | Whether a value is required |
| `default` | mixed | varies | Default value if none provided |
| `group` | string | null | Group name for organizing fields in the editor |

## Field Types

### text

Single-line text input.

```php
'title' => [
    'type' => 'text',
    'label' => 'Title',
    'required' => true,
    'minLength' => 5,
    'maxLength' => 200,
    'pattern' => '^[A-Z].*',  // Must start with uppercase
    'patternMessage' => 'Title must start with a capital letter',
    'placeholder' => 'Enter title...',
],
```

**Options:**
| Option | Type | Description |
|--------|------|-------------|
| `minLength` | int | Minimum character count |
| `maxLength` | int | Maximum character count |
| `pattern` | string | Regex pattern (without delimiters) |
| `patternMessage` | string | Custom error message for pattern failure |
| `placeholder` | string | Placeholder text |

---

### textarea

Multi-line text input.

```php
'excerpt' => [
    'type' => 'textarea',
    'label' => 'Excerpt',
    'description' => 'A short summary for listings',
    'minLength' => 50,
    'maxLength' => 500,
    'rows' => 4,
],
```

**Options:**
| Option | Type | Description |
|--------|------|-------------|
| `minLength` | int | Minimum character count |
| `maxLength` | int | Maximum character count |
| `rows` | int | Number of visible rows (default: 5) |
| `placeholder` | string | Placeholder text |

---

### number

Numeric input with optional constraints.

```php
'price' => [
    'type' => 'number',
    'label' => 'Price',
    'required' => true,
    'min' => 0,
    'max' => 10000,
    'step' => 0.01,
    'numberType' => 'float',
],
```

**Options:**
| Option | Type | Description |
|--------|------|-------------|
| `min` | number | Minimum value |
| `max` | number | Maximum value |
| `step` | number | Step increment (default: 1) |
| `numberType` | string | `'int'` or `'float'` (default: `'float'`) |

---

### checkbox

Boolean toggle field.

```php
'featured' => [
    'type' => 'checkbox',
    'label' => 'Featured',
    'description' => 'Feature this item on the homepage',
    'default' => false,
],
```

**Storage:** Stores as `true` or `false` in frontmatter.

---

### select

Dropdown selection field.

```php
'difficulty' => [
    'type' => 'select',
    'label' => 'Difficulty Level',
    'required' => true,
    'options' => [
        'beginner' => 'Beginner',
        'intermediate' => 'Intermediate',
        'advanced' => 'Advanced',
    ],
    'multiple' => false,
],
```

**Options:**
| Option | Type | Description |
|--------|------|-------------|
| `options` | array | Key-value pairs of options |
| `multiple` | bool | Allow multiple selections (default: false) |

**Note:** For multiple selections, values are stored as an array in frontmatter.

---

### date

Date or datetime picker.

```php
'publish_date' => [
    'type' => 'date',
    'label' => 'Publish Date',
    'required' => true,
    'includeTime' => true,
    'min' => '2020-01-01',
    'max' => '2030-12-31',
],
```

**Options:**
| Option | Type | Description |
|--------|------|-------------|
| `includeTime` | bool | Include time picker (default: false) |
| `min` | string | Minimum date (YYYY-MM-DD format) |
| `max` | string | Maximum date (YYYY-MM-DD format) |

**Storage:** Stored as ISO 8601 format (`YYYY-MM-DD` or `YYYY-MM-DDTHH:MM:SS`).

---

### color

Color picker with hex output.

```php
'accent_color' => [
    'type' => 'color',
    'label' => 'Accent Color',
    'default' => '#3b82f6',
],
```

**Validation:** Accepts hex colors (`#RGB`, `#RRGGBB`, `#RRGGBBAA`) and rgb/rgba formats.

**Storage:** Stored as hex string (e.g., `#3b82f6`).

---

### file

File picker from media library.

```php
'download' => [
    'type' => 'file',
    'label' => 'Downloadable File',
    'extensions' => ['pdf', 'zip', 'docx'],
    'required' => true,
],
```

**Options:**
| Option | Type | Description |
|--------|------|-------------|
| `extensions` | array | Allowed file extensions |
| `maxSize` | int | Maximum file size in bytes |

**Storage:** Stores the file path as a string (e.g., `@media:documents/file.pdf`).

---

### image

Image picker with extension validation.

```php
'featured_image' => [
    'type' => 'image',
    'label' => 'Featured Image',
    'description' => 'Recommended: 1200×630 pixels',
    'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
    'required' => true,
],
```

**Options:**
| Option | Type | Description |
|--------|------|-------------|
| `extensions` | array | Allowed extensions (default: `['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif']`) |
| `maxSize` | int | Maximum file size in bytes |

**Storage:** Stores the image path as a string (e.g., `@media:images/hero.jpg`).

---

### gallery

Multiple image picker.

```php
'photo_gallery' => [
    'type' => 'gallery',
    'label' => 'Photo Gallery',
    'minItems' => 3,
    'maxItems' => 20,
    'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
],
```

**Options:**
| Option | Type | Description |
|--------|------|-------------|
| `minItems` | int | Minimum number of images |
| `maxItems` | int | Maximum number of images |
| `extensions` | array | Allowed extensions |

**Storage:** Stores as an array of image paths.

```yaml
photo_gallery:
  - "@media:gallery/photo1.jpg"
  - "@media:gallery/photo2.jpg"
```

---

### array

Dynamic list field for arrays of values.

```php
'ingredients' => [
    'type' => 'array',
    'label' => 'Ingredients',
    'description' => 'List the ingredients',
    'minItems' => 1,
    'maxItems' => 50,
    'keyValue' => false,  // Simple list
],
```

**Options:**
| Option | Type | Description |
|--------|------|-------------|
| `minItems` | int | Minimum number of items |
| `maxItems` | int | Maximum number of items |
| `keyValue` | bool | Enable key-value pairs (default: false) |

**Storage (simple list):**
```yaml
ingredients:
  - "2 cups flour"
  - "1 tsp salt"
  - "1 cup water"
```

**Storage (key-value):**
```yaml
metadata:
  author: "John Doe"
  version: "1.0"
```

---

### content

Content reference picker for linking to other content items.

```php
'related_posts' => [
    'type' => 'content',
    'label' => 'Related Posts',
    'contentType' => 'post',
    'multiple' => true,
    'maxItems' => 5,
],
```

**Options:**
| Option | Type | Description |
|--------|------|-------------|
| `contentType` | string | Content type to select from |
| `multiple` | bool | Allow multiple selections |
| `maxItems` | int | Maximum selections (when multiple) |

**Storage:** Stores content slugs as string or array.

---

## Built-in System Fields

Ava includes built-in field types for common frontmatter options. These can be used in your field definitions:

### status

Content status selector.

```php
'status' => [
    'type' => 'status',
    'label' => 'Publication Status',
],
```

**Options:** `draft`, `published`, `unlisted`

---

### template

Template file selector.

```php
'template' => [
    'type' => 'template',
    'label' => 'Page Template',
    'defaultTemplate' => 'page.php',
],
```

Renders a dropdown of available templates from your theme.

---

### taxonomy

Taxonomy term selector.

```php
'category' => [
    'type' => 'taxonomy',
    'taxonomy' => 'category',
    'label' => 'Categories',
    'multiple' => true,
    'allowNew' => true,
],
```

**Options:**
| Option | Type | Description |
|--------|------|-------------|
| `taxonomy` | string | Taxonomy name from `taxonomies.php` |
| `multiple` | bool | Allow multiple terms (default: true) |
| `allowNew` | bool | Allow creating new terms (default: true) |

---

## Linter Integration

The field validation system integrates with the content linter. Run:

```bash
./ava lint
```

Field validation errors appear alongside standard frontmatter validation:

```
Linting content files...

  ✗ content/posts/2024-01-15-new-post.md
    Line 5: Field 'author' is required but missing.
    Line 8: Field 'price' must be at least 0.

Found 2 errors in 1 file.
```

---

## Focused Editor

The admin panel includes a focused field editor that renders structured inputs for your defined fields. Access it by clicking "Field Editor" when editing content.

Features:
- **Grouped fields** organized into collapsible panels
- **Real-time validation** with JavaScript
- **Status quick-toggle** buttons
- **SEO preview** panel
- **Keyboard shortcuts** (Ctrl+S to save)

---

## Field Groups

Organize fields into logical groups:

```php
'fields' => [
    'author' => [
        'type' => 'text',
        'label' => 'Author',
        'group' => 'Meta',
    ],
    'publish_date' => [
        'type' => 'date',
        'label' => 'Publish Date',
        'group' => 'Meta',
    ],
    'featured_image' => [
        'type' => 'image',
        'label' => 'Featured Image',
        'group' => 'Media',
    ],
],
```

Groups are rendered as collapsible panels in the focused editor.

---

## Custom Field Types

Register custom field types via the FieldRegistry:

```php
// In theme.php or plugin.php
use Ava\Fields\FieldRegistry;
use Ava\Fields\FieldType;
use Ava\Fields\ValidationResult;

$registry = new FieldRegistry();

$registry->register(new class implements FieldType {
    public function name(): string { return 'rating'; }
    public function label(): string { return 'Rating'; }
    
    public function schema(): array {
        return [
            'min' => ['type' => 'int', 'default' => 1],
            'max' => ['type' => 'int', 'default' => 5],
        ];
    }
    
    public function validate(mixed $value, array $config): ValidationResult {
        if (!is_numeric($value)) {
            return ValidationResult::error('Rating must be a number.');
        }
        
        $min = $config['min'] ?? 1;
        $max = $config['max'] ?? 5;
        
        if ($value < $min || $value > $max) {
            return ValidationResult::error("Rating must be between {$min} and {$max}.");
        }
        
        return ValidationResult::success();
    }
    
    public function toStorage(mixed $value, array $config): mixed {
        return (int) $value;
    }
    
    public function fromStorage(mixed $value, array $config): mixed {
        return (int) $value;
    }
    
    public function defaultValue(array $config): mixed {
        return $config['default'] ?? null;
    }
    
    public function render(string $name, mixed $value, array $config, array $context = []): string {
        $min = $config['min'] ?? 1;
        $max = $config['max'] ?? 5;
        
        $input = '<input type="range" name="fields[' . $name . ']" ';
        $input .= 'min="' . $min . '" max="' . $max . '" ';
        $input .= 'value="' . ($value ?? $min) . '">';
        
        return $input;
    }
    
    public function javascript(): string {
        return '';
    }
});
```

---

## Best Practices

1. **Use appropriate types** - Choose the most specific field type for your data.

2. **Always set labels** - Make fields user-friendly with clear labels and descriptions.

3. **Validate with constraints** - Use `min`, `max`, `minLength`, `maxLength` to prevent bad data.

4. **Group related fields** - Use the `group` option to organize fields logically.

5. **Consider required carefully** - Only mark fields required if they're truly necessary.

6. **Provide defaults** - Set sensible defaults to streamline content creation.

7. **Use descriptions** - Help content editors understand what each field is for.

---

## Example: Blog Post Fields

```php
'post' => [
    // ... other config
    'fields' => [
        // Content metadata
        'author' => [
            'type' => 'text',
            'label' => 'Author',
            'required' => true,
            'group' => 'Meta',
        ],
        'publish_date' => [
            'type' => 'date',
            'label' => 'Publish Date',
            'required' => true,
            'includeTime' => true,
            'group' => 'Meta',
        ],
        'featured' => [
            'type' => 'checkbox',
            'label' => 'Featured Post',
            'description' => 'Show on homepage carousel',
            'group' => 'Meta',
        ],
        
        // Media
        'featured_image' => [
            'type' => 'image',
            'label' => 'Featured Image',
            'description' => '1200×630 recommended for social sharing',
            'required' => true,
            'group' => 'Media',
        ],
        'gallery' => [
            'type' => 'gallery',
            'label' => 'Photo Gallery',
            'maxItems' => 10,
            'group' => 'Media',
        ],
        
        // Taxonomy
        'category' => [
            'type' => 'taxonomy',
            'taxonomy' => 'category',
            'label' => 'Categories',
            'required' => true,
            'group' => 'Classification',
        ],
        'tag' => [
            'type' => 'taxonomy',
            'taxonomy' => 'tag',
            'label' => 'Tags',
            'group' => 'Classification',
        ],
        
        // Related content
        'related_posts' => [
            'type' => 'content',
            'label' => 'Related Posts',
            'contentType' => 'post',
            'multiple' => true,
            'maxItems' => 3,
            'group' => 'Related',
        ],
    ],
],
```

---

## Accessing Field Values in Templates

Field values are stored in frontmatter and accessible via the Item API:

```php
// In templates
$author = $content->get('author');
$featured = $content->get('featured');
$image = $content->get('featured_image');
$categories = $content->terms('category');

// Check if field exists
if ($content->has('author')) {
    echo "By " . htmlspecialchars($content->get('author'));
}
```

---

## Changelog

### Version 1.0.0

- Initial release
- 15 built-in field types
- Linter integration
- Focused editor UI
- Custom field type registration
