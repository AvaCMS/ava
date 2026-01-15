# Documentation Team Prompt: Field Validation System

## Overview

A new **Field Validation System** has been added to Ava CMS. This feature allows content type configuration to define typed fields that are validated by the linter and rendered as structured inputs in the admin panel.

This documentation request covers a major new feature that needs to be added to the public documentation at https://ava.addy.zone/docs.

---

## What to Document

### 1. New Documentation Page: Field Validation

Create a new page at `/docs/fields` (or similar) covering:

#### A. Introduction
- What the field system is and why it's useful
- How it connects content types → linter → admin UI
- Benefits: type safety, validation, better editing experience

#### B. Configuration Reference
- Where to define fields (`content_types.php` in the `fields` key)
- Common options all fields share (type, label, description, required, default, group)

#### C. Field Types Reference

Document each field type with:
- Description and use case
- Available options (with types and defaults)
- Storage format (how it appears in frontmatter YAML)
- Example configuration

**Field types to document:**
1. `text` - Single-line text with length/pattern validation
2. `textarea` - Multi-line text with length validation
3. `number` - Numeric with min/max/step and int/float types
4. `checkbox` - Boolean toggle
5. `select` - Dropdown with options, supports multiple
6. `date` - Date/datetime picker with min/max constraints
7. `color` - Color picker (hex, rgb, rgba formats)
8. `file` - File picker with extension validation
9. `image` - Image picker with extension validation
10. `gallery` - Multiple image picker with min/max items
11. `array` - Dynamic list, optionally key-value pairs
12. `content` - Content reference picker
13. `status` - Built-in status selector (draft/published/unlisted)
14. `template` - Template file selector
15. `taxonomy` - Taxonomy term selector

#### D. Linter Integration
- How to run linting: `./ava lint`
- What field validation errors look like
- How field validation complements existing frontmatter validation

#### E. Admin Focused Editor
- How to access (click "Field Editor" button)
- Features: grouped fields, real-time validation, keyboard shortcuts
- Screenshot recommendations

#### F. Custom Field Types
- How to create custom field types
- The FieldType interface
- Registration via FieldRegistry
- Example custom field implementation

#### G. Best Practices
- Choosing appropriate types
- Using groups to organize fields
- Setting sensible defaults
- Writing helpful descriptions

---

### 2. Updates to Existing Pages

#### Content Types Page (`/docs/content-types`)
Add a section or mention of the new `fields` configuration option. Example:

```php
'post' => [
    'label' => 'Posts',
    'content_dir' => 'posts',
    // ... existing options ...
    'fields' => [
        'author' => [
            'type' => 'text',
            'label' => 'Author',
            'required' => true,
        ],
        // See /docs/fields for complete reference
    ],
],
```

#### CLI Page (`/docs/cli`)
Mention that `./ava lint` now validates defined fields:
- Field validation errors show field name and constraint that failed
- Example output showing a field validation error

#### Admin Page (`/docs/admin`)
- Mention the focused field editor as an alternative to raw file editing
- Note that fields defined in content types render as structured inputs
- Brief mention of real-time JavaScript validation

#### AI Reference Page (`/docs/ai-reference`)
Add a condensed section about fields:
- Quick reference table of field types and key options
- Link to full documentation
- Example minimal field configuration

---

### 3. Code Examples for Documentation

#### Minimal Configuration
```php
'fields' => [
    'author' => [
        'type' => 'text',
        'label' => 'Author',
        'required' => true,
    ],
],
```

#### Complete Blog Post Example
```php
'fields' => [
    'author' => [
        'type' => 'text',
        'label' => 'Author',
        'required' => true,
        'group' => 'Meta',
    ],
    'featured' => [
        'type' => 'checkbox',
        'label' => 'Featured Post',
        'group' => 'Meta',
    ],
    'featured_image' => [
        'type' => 'image',
        'label' => 'Featured Image',
        'extensions' => ['jpg', 'png', 'webp'],
        'required' => true,
        'group' => 'Media',
    ],
    'category' => [
        'type' => 'taxonomy',
        'taxonomy' => 'category',
        'label' => 'Categories',
        'multiple' => true,
        'group' => 'Taxonomy',
    ],
],
```

#### Linter Output Example
```
Linting content files...

  ✗ content/posts/2024-01-15-new-post.md
    Line 5: Field 'author' is required but missing.
    Line 8: Field 'price' must be at least 0.
    Line 12: Field 'featured_image' must be a valid image file.

Found 3 errors in 1 file.
```

---

### 4. Screenshot Recommendations

For the Admin/Fields documentation:
1. Content type config with fields defined in code editor
2. Focused editor view showing field groups
3. Status toggle buttons in focused editor
4. Validation error message in focused editor
5. Linter output with field errors in terminal

---

### 5. Reference Implementation

The complete technical reference is available at:
- Source: [docs/fields.md](docs/fields.md) in the repository
- Implementation: [core/Fields/](core/Fields/) namespace

Key source files:
- `core/Fields/FieldRegistry.php` - Type registry
- `core/Fields/FieldType.php` - Interface
- `core/Fields/Field.php` - Configured field wrapper
- `core/Fields/ValidationResult.php` - Validation result
- `core/Fields/FieldValidator.php` - Linter integration
- `core/Fields/FieldRenderer.php` - Admin rendering
- `core/Fields/Types/*.php` - 15 field type implementations
- `core/Admin/views/content/content-edit-focused.php` - Focused editor view

---

### 6. Suggested Navigation Structure

```
Docs
├── Getting Started
├── Configuration
│   ├── Main Settings
│   ├── Content Types      ← Add 'fields' section
│   └── Taxonomies
├── Content
│   └── ...
├── Fields (NEW)           ← New page
│   ├── Configuration
│   ├── Field Types
│   ├── Linter Integration
│   ├── Focused Editor
│   └── Custom Fields
├── CLI                    ← Mention field validation
├── Admin                  ← Mention focused editor
├── Theming
└── ...
```

---

### 7. Key Messages

When documenting, emphasize:

1. **Optional but powerful** - Fields are opt-in. Existing content types work without fields.

2. **Validation at all levels** - Fields validate in the linter AND in the admin UI.

3. **Type-appropriate inputs** - Each field type renders as the most suitable HTML input.

4. **Extensible** - Developers can create custom field types.

5. **Non-destructive** - Field definitions don't modify existing content files.

---

### 8. Questions for Documentation Team

1. Should fields have their own top-level nav item, or be nested under Content Types?
2. Do we want video tutorials for the focused editor?
3. Should we include migration guidance for sites adding fields to existing content types?
4. What level of detail for custom field type development?

---

## Priority

This is a **high-priority** documentation update as the field system is a major new feature that significantly improves the content editing experience.

Suggested timeline: Include in next documentation release.
