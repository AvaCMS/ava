<?php
/**
 * Taxonomy Term Create View
 * 
 * Available variables:
 * - $taxonomy: Taxonomy slug
 * - $config: Taxonomy configuration
 * - $knownCustomFields: Array of custom field names used in other terms
 * - $error: Error message (if any)
 * - $csrf: CSRF token
 * - $site: Site configuration
 * - $admin_url: Admin base URL
 */
?>

<div class="content-layout">
    <div class="card content-main">
        <div class="card-header">
            <span class="card-title">
                <span class="material-symbols-rounded">add</span>
                New Term
            </span>
        </div>
        <div class="card-body">
            <form method="POST" action="<?= htmlspecialchars($admin_url) ?>/taxonomy/<?= htmlspecialchars($taxonomy) ?>/create">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                
                <div class="form-group">
                    <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" id="name" name="name" class="form-control" required
                           placeholder="e.g., Getting Started"
                           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                    <p class="form-hint">The display name for this term.</p>
                </div>

                <div class="form-group">
                    <label for="slug" class="form-label">Slug</label>
                    <input type="text" id="slug" name="slug" class="form-control" 
                           pattern="[a-z0-9\x2D]+"
                           placeholder="e.g., getting-started (auto-generated if empty)"
                           value="<?= htmlspecialchars($_POST['slug'] ?? '') ?>">
                    <p class="form-hint">URL-safe identifier. Lowercase letters, numbers, and hyphens only.</p>
                </div>

                <div class="form-group">
                    <label for="description" class="form-label">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="3"
                              placeholder="Optional description for this term..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>

                <!-- Custom Fields Section -->
                <div class="form-group">
                    <label class="form-label">Custom Fields</label>
                    <p class="form-hint mb-2">Add any additional metadata for this term.</p>
                    
                    <div class="term-custom-fields-container" id="custom-fields-container">
                        <?php 
                        $customFields = $_POST['custom_fields'] ?? [];
                        $index = 0;
                        foreach ($customFields as $field):
                            $key = $field['key'] ?? '';
                            $value = $field['value'] ?? '';
                            if ($key !== '' || $value !== ''):
                        ?>
                        <div class="term-custom-field-row" data-index="<?= $index ?>">
                            <input type="text" 
                                   name="custom_fields[<?= $index ?>][key]" 
                                   class="form-control term-field-key" 
                                   placeholder="Field name (e.g., icon)"
                                   pattern="[a-z][a-z0-9_]*"
                                   value="<?= htmlspecialchars($key) ?>"
                                   list="known-fields">
                            <span class="term-field-separator">:</span>
                            <input type="text" 
                                   name="custom_fields[<?= $index ?>][value]" 
                                   class="form-control term-field-value" 
                                   placeholder="Value"
                                   value="<?= htmlspecialchars($value) ?>">
                            <button type="button" class="btn-icon term-field-remove" title="Remove field">
                                <span class="material-symbols-rounded">close</span>
                            </button>
                        </div>
                        <?php 
                            $index++;
                            endif;
                        endforeach; 
                        ?>
                    </div>
                    
                    <button type="button" class="btn btn-secondary btn-sm mt-2" id="add-custom-field">
                        <span class="material-symbols-rounded">add</span> Add Field
                    </button>
                    
                    <?php if (!empty($knownCustomFields)): ?>
                    <datalist id="known-fields">
                        <?php foreach ($knownCustomFields as $fieldName): ?>
                        <option value="<?= htmlspecialchars($fieldName) ?>">
                        <?php endforeach; ?>
                    </datalist>
                    <?php endif; ?>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <span class="material-symbols-rounded">add</span>
                        Create Term
                    </button>
                    <a href="<?= htmlspecialchars($admin_url) ?>/taxonomy/<?= htmlspecialchars($taxonomy) ?>" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="config-sidebar">
        <div class="card">
            <div class="card-header">
                <span class="card-title">
                    <span class="material-symbols-rounded">info</span>
                    About Terms
                </span>
            </div>
            <div class="card-body">
                <p class="text-sm text-secondary">
                    Terms are stored in YAML files at:<br>
                    <code class="text-xs">content/_taxonomies/<?= htmlspecialchars($taxonomy) ?>.yml</code>
                </p>
                <p class="text-sm text-secondary mt-3">
                    After creating a term, you can assign content to it by adding the term to your content's frontmatter.
                </p>
            </div>
        </div>
        
        <?php if (!empty($knownCustomFields)): ?>
        <div class="card">
            <div class="card-header">
                <span class="card-title">
                    <span class="material-symbols-rounded">data_object</span>
                    Known Fields
                </span>
            </div>
            <div class="card-body">
                <p class="text-sm text-secondary mb-2">Fields used by other terms in this taxonomy:</p>
                <div class="known-fields-list">
                    <?php foreach ($knownCustomFields as $fieldName): ?>
                    <code class="known-field-tag"><?= htmlspecialchars($fieldName) ?></code>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <span class="card-title">
                    <span class="material-symbols-rounded">code</span>
                    Using in Themes
                </span>
            </div>
            <div class="card-body">
                <p class="text-sm text-secondary mb-2">Access custom fields in your templates:</p>
                <pre class="code-hint"><code>&lt;?php
\$terms = \$ava->terms('<?= htmlspecialchars($taxonomy) ?>');
foreach (\$terms as \$term) {
    echo \$term['name'];
    echo \$term['icon'] ?? '';
}
?&gt;</code></pre>
                <p class="text-sm text-secondary mt-2">On taxonomy archive pages, the current term is available via <code class="text-xs">\$tax['term']</code>.</p>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-generate slug from name
document.getElementById('name').addEventListener('input', function() {
    const slugField = document.getElementById('slug');
    if (!slugField.value) {
        slugField.placeholder = this.value
            .toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/[\s_]+/g, '-')
            .replace(/-+/g, '-')
            .trim() || 'auto-generated';
    }
});

// Custom fields management
(function() {
    const container = document.getElementById('custom-fields-container');
    const addBtn = document.getElementById('add-custom-field');
    let fieldIndex = container.querySelectorAll('.term-custom-field-row').length;
    
    function createFieldRow(index, key = '', value = '') {
        const row = document.createElement('div');
        row.className = 'term-custom-field-row';
        row.dataset.index = index;
        row.innerHTML = `
            <input type="text" 
                   name="custom_fields[${index}][key]" 
                   class="form-control term-field-key" 
                   placeholder="Field name (e.g., icon)"
                   pattern="[a-z][a-z0-9_]*"
                   value="${escapeHtml(key)}"
                   list="known-fields">
            <span class="term-field-separator">:</span>
            <input type="text" 
                   name="custom_fields[${index}][value]" 
                   class="form-control term-field-value" 
                   placeholder="Value"
                   value="${escapeHtml(value)}">
            <button type="button" class="btn-icon term-field-remove" title="Remove field">
                <span class="material-symbols-rounded">close</span>
            </button>
        `;
        return row;
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function initRemoveBtn(btn) {
        btn.addEventListener('click', function() {
            this.closest('.term-custom-field-row').remove();
        });
    }
    
    // Init existing remove buttons
    container.querySelectorAll('.term-field-remove').forEach(initRemoveBtn);
    
    // Add new field
    addBtn.addEventListener('click', function() {
        const row = createFieldRow(fieldIndex++);
        container.appendChild(row);
        initRemoveBtn(row.querySelector('.term-field-remove'));
        row.querySelector('.term-field-key').focus();
    });
})();
</script>
