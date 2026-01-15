<?php
/**
 * Content Edit View - Focused Field Editor
 * 
 * A modern, focused editor that uses the field system for structured editing.
 * Features:
 * - Distraction-free content editing
 * - Resizable content area
 * - Collapsible settings panels
 * - Media picker integration
 * - Real-time validation
 */

$taxonomiesForType = $typeConfig['taxonomies'] ?? [];
$usesDate = in_array($typeConfig['sorting'] ?? '', ['date_desc', 'date_asc'], true);
$usesOrder = ($typeConfig['sorting'] ?? '') === 'manual';
$typeLabel = rtrim($typeConfig['label'] ?? ucfirst($type), 's');

// Get current values from item
$currentTitle = $item->title();
$currentSlug = $item->slug();
$currentStatus = $item->status();
$currentDate = $item->date()?->format('Y-m-d\TH:i') ?? date('Y-m-d\TH:i');
$currentUpdated = $item->updated()?->format('Y-m-d\TH:i') ?? '';
$currentId = $item->id() ?? '';
$currentBody = $item->rawContent();
$currentFilename = pathinfo(basename($item->filePath()), PATHINFO_FILENAME);
$currentOrder = $item->order();

// Per-item assets
$cssAssets = $item->css();
$jsAssets = $item->js();

// Generate preview URL
$baseUrl = rtrim($site['url'] ?? '', '/');
$urlType = $typeConfig['url']['type'] ?? 'pattern';
if ($urlType === 'hierarchical') {
    $previewUrl = $baseUrl . '/' . ltrim($currentSlug, '/');
} else {
    $pattern = $typeConfig['url']['pattern'] ?? '/{slug}';
    $previewUrl = $baseUrl . str_replace('{slug}', $currentSlug, $pattern);
}
$previewUrl .= '?preview=1';

// Check for success message
$saved = isset($_GET['saved']) || isset($successMessage);
$successMsg = $successMessage ?? 'Changes saved successfully.';

// Context for field renderer
$context = [
    'csrf' => $csrf,
    'admin_url' => $admin_url,
    'taxonomyConfig' => $taxonomyConfig,
    'availableTerms' => $availableTerms,
];
?>

<div class="fe-container" id="focused-editor-container">
    <!-- Top Bar -->
    <header class="fe-header">
        <div class="fe-header-left">
            <a href="<?= htmlspecialchars($admin_url) ?>/content/<?= htmlspecialchars($type) ?>" class="fe-back-btn" title="Back to list">
                <span class="material-symbols-rounded">arrow_back</span>
            </a>
            <div class="fe-breadcrumb">
                <span class="fe-breadcrumb-type"><?= htmlspecialchars($typeLabel) ?></span>
                <span class="fe-breadcrumb-sep">›</span>
                <span class="fe-breadcrumb-title" id="breadcrumb-title"><?= htmlspecialchars($currentTitle ?: 'Untitled') ?></span>
            </div>
        </div>
        
        <div class="fe-header-center">
            <div class="fe-mode-toggle">
                <a href="<?= htmlspecialchars($admin_url) ?>/content/<?= htmlspecialchars($type) ?>/edit?file=<?= htmlspecialchars($fileParam) ?>&mode=raw" 
                   class="fe-mode-btn">
                    <span class="material-symbols-rounded">code</span>
                    Raw File
                </a>
                <span class="fe-mode-btn fe-mode-active">
                    <span class="material-symbols-rounded">view_compact</span>
                    Field Editor
                </span>
            </div>
        </div>
        
        <div class="fe-header-right">
            <a href="<?= htmlspecialchars($previewUrl) ?>" target="_blank" class="btn btn-secondary btn-sm">
                <span class="material-symbols-rounded">visibility</span>
                Preview
            </a>
            <button type="submit" form="focused-editor-form" class="btn btn-primary btn-sm">
                <span class="material-symbols-rounded">save</span>
                Save
            </button>
        </div>
    </header>

    <?php if ($saved): ?>
    <div class="fe-alert fe-alert-success">
        <span class="material-symbols-rounded">check_circle</span>
        <span><?= htmlspecialchars($successMsg) ?></span>
        <button type="button" class="fe-alert-close" onclick="this.parentElement.remove()">
            <span class="material-symbols-rounded">close</span>
        </button>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="fe-alert fe-alert-error">
        <span class="material-symbols-rounded">error</span>
        <span><?= htmlspecialchars($error) ?></span>
    </div>
    <?php endif; ?>

    <?php if (!empty($securityWarnings)): ?>
    <div class="fe-alert fe-alert-warning">
        <span class="material-symbols-rounded">warning</span>
        <span><strong>Content blocked:</strong> <?= htmlspecialchars($securityWarnings[0]) ?></span>
    </div>
    <?php endif; ?>

    <form method="POST" action="<?= htmlspecialchars($admin_url) ?>/content/<?= htmlspecialchars($type) ?>/edit?file=<?= htmlspecialchars($fileParam) ?>&mode=focused" 
          class="fe-form" id="focused-editor-form">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="_file_mtime" value="<?= htmlspecialchars((string) ($fileMtime ?? 0)) ?>">
        <input type="hidden" name="_editor_mode" value="focused">

        <div class="fe-main">
            <!-- Title Field - Prominent -->
            <div class="fe-title-field">
                <input type="text" name="fields[title]" id="field-title" class="fe-title-input" 
                       value="<?= htmlspecialchars($currentTitle) ?>" placeholder="Enter title..." 
                       required autofocus>
            </div>

            <!-- Content Editor -->
            <div class="fe-editor" id="content-editor">
                <div class="fe-editor-toolbar">
                    <div class="fe-toolbar-group">
                        <button type="button" class="fe-tool-btn" onclick="insertMarkdown('**', '**')" title="Bold (Ctrl+B)">
                            <span class="material-symbols-rounded">format_bold</span>
                        </button>
                        <button type="button" class="fe-tool-btn" onclick="insertMarkdown('*', '*')" title="Italic (Ctrl+I)">
                            <span class="material-symbols-rounded">format_italic</span>
                        </button>
                        <button type="button" class="fe-tool-btn" onclick="insertMarkdown('~~', '~~')" title="Strikethrough">
                            <span class="material-symbols-rounded">strikethrough_s</span>
                        </button>
                    </div>
                    <div class="fe-toolbar-sep"></div>
                    <div class="fe-toolbar-group">
                        <button type="button" class="fe-tool-btn" onclick="insertHeading()" title="Heading">
                            <span class="material-symbols-rounded">title</span>
                        </button>
                        <button type="button" class="fe-tool-btn" onclick="insertMarkdown('[', '](url)')" title="Link (Ctrl+K)">
                            <span class="material-symbols-rounded">link</span>
                        </button>
                        <button type="button" class="fe-tool-btn" onclick="insertMarkdown('`', '`')" title="Inline Code">
                            <span class="material-symbols-rounded">code</span>
                        </button>
                    </div>
                    <div class="fe-toolbar-sep"></div>
                    <div class="fe-toolbar-group">
                        <button type="button" class="fe-tool-btn" onclick="insertMarkdown('- ', '')" title="Bullet List">
                            <span class="material-symbols-rounded">format_list_bulleted</span>
                        </button>
                        <button type="button" class="fe-tool-btn" onclick="insertMarkdown('1. ', '')" title="Numbered List">
                            <span class="material-symbols-rounded">format_list_numbered</span>
                        </button>
                        <button type="button" class="fe-tool-btn" onclick="insertMarkdown('> ', '')" title="Quote">
                            <span class="material-symbols-rounded">format_quote</span>
                        </button>
                    </div>
                    <div class="fe-toolbar-sep"></div>
                    <div class="fe-toolbar-group">
                        <button type="button" class="fe-tool-btn" onclick="openMediaPicker('body')" title="Insert Image">
                            <span class="material-symbols-rounded">image</span>
                        </button>
                        <button type="button" class="fe-tool-btn" onclick="insertCodeBlock()" title="Code Block">
                            <span class="material-symbols-rounded">data_object</span>
                        </button>
                        <button type="button" class="fe-tool-btn" onclick="insertHorizontalRule()" title="Horizontal Rule">
                            <span class="material-symbols-rounded">horizontal_rule</span>
                        </button>
                    </div>
                    <div class="fe-toolbar-spacer"></div>
                    <div class="fe-toolbar-group">
                        <button type="button" class="fe-tool-btn" id="expand-editor-btn" onclick="toggleExpandedEditor()" title="Expand Editor">
                            <span class="material-symbols-rounded">open_in_full</span>
                        </button>
                    </div>
                </div>
                <div class="fe-editor-body">
                    <textarea id="field-body" name="fields[body]" class="fe-body-textarea" 
                              placeholder="Write your content in Markdown..."><?= htmlspecialchars($currentBody) ?></textarea>
                </div>
                <div class="fe-editor-resize" id="editor-resize-handle"></div>
            </div>

            <!-- Excerpt -->
            <div class="fe-field-row">
                <label class="fe-label" for="field-excerpt">
                    <span class="material-symbols-rounded">short_text</span>
                    Excerpt
                </label>
                <textarea id="field-excerpt" name="fields[excerpt]" class="fe-textarea" rows="2" 
                          placeholder="Brief summary for listings..."><?= htmlspecialchars($item->excerpt() ?? '') ?></textarea>
                <span class="fe-hint">Used in listings and meta descriptions.</span>
            </div>

            <!-- Custom Fields Panel -->
            <?php if (isset($fieldRenderer)): ?>
            <div class="fe-custom-fields">
                <?= $fieldRenderer->renderFields($item->frontmatter(), $type, $context) ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Bottom Settings Panel -->
        <div class="fe-settings">
            <div class="fe-settings-tabs">
                <button type="button" class="fe-tab active" data-panel="publishing">
                    <span class="material-symbols-rounded">publish</span>
                    Publishing
                </button>
                <button type="button" class="fe-tab" data-panel="url">
                    <span class="material-symbols-rounded">link</span>
                    URL
                </button>
                <?php if (!empty($taxonomiesForType)): ?>
                <button type="button" class="fe-tab" data-panel="taxonomies">
                    <span class="material-symbols-rounded">label</span>
                    Taxonomies
                </button>
                <?php endif; ?>
                <button type="button" class="fe-tab" data-panel="seo">
                    <span class="material-symbols-rounded">search</span>
                    SEO
                </button>
                <button type="button" class="fe-tab" data-panel="assets">
                    <span class="material-symbols-rounded">code</span>
                    Assets
                </button>
                <button type="button" class="fe-tab" data-panel="advanced">
                    <span class="material-symbols-rounded">tune</span>
                    Advanced
                </button>
            </div>

            <div class="fe-settings-panels">
                <!-- Publishing Panel -->
                <div class="fe-panel active" id="panel-publishing">
                    <div class="fe-panel-grid">
                        <div class="fe-field">
                            <label class="fe-label">Status</label>
                            <div class="fe-status-group">
                                <label class="fe-status-btn <?= $currentStatus === 'draft' ? 'active' : '' ?>">
                                    <input type="radio" name="fields[status]" value="draft" <?= $currentStatus === 'draft' ? 'checked' : '' ?>>
                                    <span class="material-symbols-rounded">edit</span>
                                    <span>Draft</span>
                                </label>
                                <label class="fe-status-btn <?= $currentStatus === 'published' ? 'active' : '' ?>">
                                    <input type="radio" name="fields[status]" value="published" <?= $currentStatus === 'published' ? 'checked' : '' ?>>
                                    <span class="material-symbols-rounded">public</span>
                                    <span>Published</span>
                                </label>
                                <label class="fe-status-btn <?= $currentStatus === 'unlisted' ? 'active' : '' ?>">
                                    <input type="radio" name="fields[status]" value="unlisted" <?= $currentStatus === 'unlisted' ? 'checked' : '' ?>>
                                    <span class="material-symbols-rounded">visibility_off</span>
                                    <span>Unlisted</span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="fe-field">
                            <label class="fe-label" for="field-date">Published Date</label>
                            <input type="datetime-local" id="field-date" name="fields[date]" class="fe-input" 
                                   value="<?= htmlspecialchars($currentDate) ?>">
                        </div>
                        
                        <div class="fe-field">
                            <label class="fe-label" for="field-updated">Updated Date</label>
                            <input type="datetime-local" id="field-updated" name="fields[updated]" class="fe-input" 
                                   value="<?= htmlspecialchars($currentUpdated) ?>" 
                                   placeholder="Defaults to published date">
                        </div>
                        
                        <?php if ($usesOrder): ?>
                        <div class="fe-field">
                            <label class="fe-label" for="field-order">Order</label>
                            <input type="number" id="field-order" name="fields[order]" class="fe-input" 
                                   value="<?= htmlspecialchars((string)$currentOrder) ?>" min="0" step="1">
                            <span class="fe-hint">Lower numbers appear first (for manual sorting).</span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="fe-field">
                            <label class="fe-label" for="field-id">Content ID</label>
                            <div class="fe-input-group">
                                <input type="text" id="field-id" name="fields[id]" class="fe-input fe-mono" 
                                       value="<?= htmlspecialchars($currentId) ?>" placeholder="Optional ULID">
                                <button type="button" class="fe-input-btn" onclick="generateId()" title="Generate ID">
                                    <span class="material-symbols-rounded">refresh</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- URL Panel -->
                <div class="fe-panel" id="panel-url">
                    <div class="fe-panel-grid">
                        <div class="fe-field">
                            <label class="fe-label" for="field-slug">URL Slug</label>
                            <input type="text" id="field-slug" name="fields[slug]" class="fe-input" 
                                   value="<?= htmlspecialchars($currentSlug) ?>" required 
                                   pattern="[a-z0-9-/]+" title="Lowercase letters, numbers, hyphens, and slashes">
                            <span class="fe-hint">
                                <button type="button" class="fe-link-btn" onclick="generateSlugFromTitle()">
                                    <span class="material-symbols-rounded">auto_fix</span>
                                    Generate from title
                                </button>
                            </span>
                        </div>
                        
                        <div class="fe-field">
                            <label class="fe-label" for="field-filename">Filename</label>
                            <div class="fe-input-suffix">
                                <input type="text" id="field-filename" name="filename" class="fe-input" 
                                       value="<?= htmlspecialchars($currentFilename) ?>" 
                                       pattern="[a-z0-9-]+" title="Lowercase letters, numbers, and hyphens">
                                <span class="fe-suffix">.md</span>
                            </div>
                        </div>
                        
                        <div class="fe-field">
                            <label class="fe-label">Redirect From</label>
                            <div class="fe-array-field" id="redirect-from-array">
                                <?php foreach ($item->redirectFrom() as $redirect): ?>
                                <div class="fe-array-item">
                                    <input type="text" name="fields[redirect_from][]" class="fe-input" 
                                           value="<?= htmlspecialchars($redirect) ?>" placeholder="/old-url">
                                    <button type="button" class="fe-array-remove" onclick="removeArrayItem(this)">
                                        <span class="material-symbols-rounded">close</span>
                                    </button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="fe-link-btn" onclick="addArrayItem('redirect-from-array', 'redirect_from', '/old-url')">
                                <span class="material-symbols-rounded">add</span>
                                Add redirect
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Taxonomies Panel -->
                <?php if (!empty($taxonomiesForType)): ?>
                <div class="fe-panel" id="panel-taxonomies">
                    <div class="fe-panel-grid">
                        <?php foreach ($taxonomiesForType as $taxName): 
                            $taxConfig = $taxonomyConfig[$taxName] ?? [];
                            $taxLabel = $taxConfig['label'] ?? ucfirst($taxName);
                            $terms = $availableTerms[$taxName] ?? [];
                            $itemTerms = $item->terms($taxName);
                        ?>
                        <div class="fe-field fe-field-wide">
                            <label class="fe-label"><?= htmlspecialchars($taxLabel) ?></label>
                            <?php if (!empty($terms)): ?>
                            <div class="fe-multiselect" data-taxonomy="<?= htmlspecialchars($taxName) ?>">
                                <div class="fe-multiselect-search">
                                    <span class="material-symbols-rounded">search</span>
                                    <input type="text" class="fe-multiselect-input" placeholder="Search <?= htmlspecialchars(strtolower($taxLabel)) ?>...">
                                </div>
                                <div class="fe-multiselect-options">
                                    <?php foreach ($terms as $termSlug => $termData): 
                                        $isChecked = in_array($termSlug, $itemTerms, true);
                                    ?>
                                    <label class="fe-multiselect-option <?= $isChecked ? 'selected' : '' ?>" data-slug="<?= htmlspecialchars($termSlug) ?>">
                                        <input type="checkbox" name="fields[<?= htmlspecialchars($taxName) ?>][]" 
                                               value="<?= htmlspecialchars($termSlug) ?>"
                                               <?= $isChecked ? 'checked' : '' ?>>
                                        <span class="fe-check">
                                            <span class="material-symbols-rounded">check</span>
                                        </span>
                                        <span class="fe-term-name"><?= htmlspecialchars($termData['name'] ?? $termSlug) ?></span>
                                        <?php if (isset($termData['count'])): ?>
                                        <span class="fe-term-count"><?= (int)$termData['count'] ?></span>
                                        <?php endif; ?>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                                <div class="fe-multiselect-selected">
                                    <?php foreach ($itemTerms as $term): ?>
                                    <span class="fe-tag" data-term="<?= htmlspecialchars($term) ?>">
                                        <?= htmlspecialchars($terms[$term]['name'] ?? $term) ?>
                                        <button type="button" class="fe-tag-remove" onclick="removeTermTag(this, '<?= htmlspecialchars($taxName) ?>', '<?= htmlspecialchars($term) ?>')">×</button>
                                    </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php else: ?>
                            <input type="text" class="fe-input" name="fields[<?= htmlspecialchars($taxName) ?>]" 
                                   placeholder="term1, term2, term3" 
                                   value="<?= htmlspecialchars(implode(', ', $itemTerms)) ?>">
                            <span class="fe-hint">Comma-separated terms.</span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- SEO Panel -->
                <div class="fe-panel" id="panel-seo">
                    <div class="fe-panel-grid">
                        <div class="fe-field">
                            <label class="fe-label" for="field-meta-title">
                                Meta Title
                                <span class="fe-char-count" data-max="60">0/60</span>
                            </label>
                            <input type="text" id="field-meta-title" name="fields[meta_title]" class="fe-input" 
                                   value="<?= htmlspecialchars($item->metaTitle() ?? '') ?>" 
                                   placeholder="Override title for search engines" maxlength="70">
                        </div>

                        <div class="fe-field">
                            <label class="fe-label" for="field-meta-desc">
                                Meta Description
                                <span class="fe-char-count" data-max="160">0/160</span>
                            </label>
                            <textarea id="field-meta-desc" name="fields[meta_description]" class="fe-textarea" 
                                      rows="2" placeholder="Description for search results" maxlength="200"><?= htmlspecialchars($item->metaDescription() ?? '') ?></textarea>
                        </div>

                        <div class="fe-field">
                            <label class="fe-label" for="field-canonical">Canonical URL</label>
                            <input type="url" id="field-canonical" name="fields[canonical]" class="fe-input" 
                                   value="<?= htmlspecialchars($item->canonical() ?? '') ?>" 
                                   placeholder="https://example.com/original-article">
                            <span class="fe-hint">Use when content exists elsewhere or to specify the preferred URL.</span>
                        </div>

                        <div class="fe-field">
                            <label class="fe-label" for="field-og-image">Social Image</label>
                            <div class="fe-input-group">
                                <input type="text" id="field-og-image" name="fields[og_image]" class="fe-input" 
                                       value="<?= htmlspecialchars($item->ogImage() ?? '') ?>" 
                                       placeholder="@media:social.jpg">
                                <button type="button" class="fe-input-btn" onclick="openMediaPicker('og_image')" title="Browse Media">
                                    <span class="material-symbols-rounded">folder</span>
                                </button>
                            </div>
                        </div>

                        <div class="fe-field">
                            <label class="fe-checkbox">
                                <input type="checkbox" name="fields[noindex]" value="1" 
                                       <?= $item->noindex() ? 'checked' : '' ?>>
                                <span class="fe-check-box">
                                    <span class="material-symbols-rounded">check</span>
                                </span>
                                <span>Hide from search engines (noindex)</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Assets Panel -->
                <div class="fe-panel" id="panel-assets">
                    <div class="fe-panel-grid">
                        <div class="fe-field fe-field-wide">
                            <label class="fe-label">
                                <span class="material-symbols-rounded">css</span>
                                CSS Files
                            </label>
                            <div class="fe-array-field" id="css-assets-array">
                                <?php foreach ($cssAssets as $css): ?>
                                <div class="fe-array-item">
                                    <input type="text" name="fields[assets_css][]" class="fe-input" 
                                           value="<?= htmlspecialchars($css) ?>" placeholder="@media:css/custom.css">
                                    <button type="button" class="fe-input-btn" onclick="openMediaPicker('css', this.previousElementSibling)">
                                        <span class="material-symbols-rounded">folder</span>
                                    </button>
                                    <button type="button" class="fe-array-remove" onclick="removeArrayItem(this)">
                                        <span class="material-symbols-rounded">close</span>
                                    </button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="fe-link-btn" onclick="addAssetItem('css-assets-array', 'assets_css', '@media:css/')">
                                <span class="material-symbols-rounded">add</span>
                                Add CSS file
                            </button>
                        </div>

                        <div class="fe-field fe-field-wide">
                            <label class="fe-label">
                                <span class="material-symbols-rounded">javascript</span>
                                JavaScript Files
                            </label>
                            <div class="fe-array-field" id="js-assets-array">
                                <?php foreach ($jsAssets as $js): ?>
                                <div class="fe-array-item">
                                    <input type="text" name="fields[assets_js][]" class="fe-input" 
                                           value="<?= htmlspecialchars($js) ?>" placeholder="@media:js/script.js">
                                    <button type="button" class="fe-input-btn" onclick="openMediaPicker('js', this.previousElementSibling)">
                                        <span class="material-symbols-rounded">folder</span>
                                    </button>
                                    <button type="button" class="fe-array-remove" onclick="removeArrayItem(this)">
                                        <span class="material-symbols-rounded">close</span>
                                    </button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="fe-link-btn" onclick="addAssetItem('js-assets-array', 'assets_js', '@media:js/')">
                                <span class="material-symbols-rounded">add</span>
                                Add JavaScript file
                            </button>
                        </div>
                        
                        <div class="fe-field fe-field-wide">
                            <span class="fe-hint">
                                <span class="material-symbols-rounded">info</span>
                                Per-item assets load only on this page. Use <code>@media:</code> for files in the media folder.
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Advanced Panel -->
                <div class="fe-panel" id="panel-advanced">
                    <div class="fe-panel-grid">
                        <div class="fe-field">
                            <label class="fe-label" for="field-template">Template</label>
                            <select id="field-template" name="fields[template]" class="fe-input">
                                <option value="">— Default Template —</option>
                                <?php 
                                $themePath = $app->path('app/themes/' . $app->config('theme', 'default') . '/templates');
                                if (is_dir($themePath)):
                                    foreach (glob($themePath . '/*.php') as $tplFile):
                                        $tplName = basename($tplFile);
                                        $selected = ($item->template() === $tplName) ? 'selected' : '';
                                ?>
                                <option value="<?= htmlspecialchars($tplName) ?>" <?= $selected ?>><?= htmlspecialchars($tplName) ?></option>
                                <?php 
                                    endforeach;
                                endif;
                                ?>
                            </select>
                        </div>

                        <div class="fe-field">
                            <label class="fe-checkbox">
                                <input type="checkbox" name="fields[cache]" value="1" 
                                       <?= ($item->get('cache') ?? true) ? 'checked' : '' ?>>
                                <span class="fe-check-box">
                                    <span class="material-symbols-rounded">check</span>
                                </span>
                                <span>Enable page caching</span>
                            </label>
                        </div>
                        
                        <div class="fe-field fe-field-wide fe-danger-zone">
                            <label class="fe-label">Danger Zone</label>
                            <a href="<?= htmlspecialchars($admin_url) ?>/content/<?= htmlspecialchars($type) ?>/<?= htmlspecialchars($item->slug()) ?>/delete" 
                               class="btn btn-danger-outline btn-sm">
                                <span class="material-symbols-rounded">delete</span>
                                Delete this <?= htmlspecialchars(strtolower($typeLabel)) ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Media Picker Modal -->
<div class="fe-modal" id="media-picker-modal" style="display: none;">
    <div class="fe-modal-backdrop" onclick="closeMediaPicker()"></div>
    <div class="fe-modal-content">
        <div class="fe-modal-header">
            <h3>
                <span class="material-symbols-rounded">folder_open</span>
                Select Media
            </h3>
            <button type="button" class="fe-modal-close" onclick="closeMediaPicker()">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>
        <div class="fe-modal-body">
            <div class="fe-media-toolbar">
                <div class="fe-media-search">
                    <span class="material-symbols-rounded">search</span>
                    <input type="text" id="media-search" placeholder="Search files..." oninput="filterMedia()">
                </div>
                <select id="media-folder" class="fe-input" onchange="loadMediaFolder()">
                    <option value="">All Files</option>
                </select>
            </div>
            <div class="fe-media-grid" id="media-grid">
                <div class="fe-media-loading">
                    <span class="material-symbols-rounded spin">sync</span>
                    Loading media...
                </div>
            </div>
        </div>
        <div class="fe-modal-footer">
            <span class="fe-selected-file" id="selected-file-info"></span>
            <button type="button" class="btn btn-secondary" onclick="closeMediaPicker()">Cancel</button>
            <button type="button" class="btn btn-primary" id="media-insert-btn" onclick="insertSelectedMedia()" disabled>Insert</button>
        </div>
    </div>
</div>

<script>
// =============================================================================
// Editor Core
// =============================================================================
const bodyEditor = document.getElementById('field-body');
const titleInput = document.getElementById('field-title');
const breadcrumbTitle = document.getElementById('breadcrumb-title');

// Update breadcrumb as title changes
titleInput.addEventListener('input', function() {
    breadcrumbTitle.textContent = this.value || 'Untitled';
});

// Toolbar functions
function insertMarkdown(before, after) {
    const start = bodyEditor.selectionStart;
    const end = bodyEditor.selectionEnd;
    const text = bodyEditor.value;
    const selected = text.substring(start, end);
    
    bodyEditor.value = text.substring(0, start) + before + selected + after + text.substring(end);
    
    if (selected) {
        bodyEditor.selectionStart = start;
        bodyEditor.selectionEnd = start + before.length + selected.length + after.length;
    } else {
        bodyEditor.selectionStart = bodyEditor.selectionEnd = start + before.length;
    }
    
    bodyEditor.focus();
}

function insertHeading() {
    const start = bodyEditor.selectionStart;
    const text = bodyEditor.value;
    const lineStart = text.lastIndexOf('\n', start - 1) + 1;
    const lineText = text.substring(lineStart, start);
    
    const match = lineText.match(/^(#{0,5})/);
    const currentLevel = match ? match[1].length : 0;
    const newLevel = currentLevel >= 6 ? 0 : currentLevel + 1;
    
    const before = text.substring(0, lineStart);
    const after = text.substring(lineStart).replace(/^#{0,6}\s*/, '');
    const prefix = newLevel > 0 ? '#'.repeat(newLevel) + ' ' : '';
    
    bodyEditor.value = before + prefix + after;
    bodyEditor.selectionStart = bodyEditor.selectionEnd = lineStart + prefix.length;
    bodyEditor.focus();
}

function insertCodeBlock() {
    const start = bodyEditor.selectionStart;
    const text = bodyEditor.value;
    const selected = text.substring(start, bodyEditor.selectionEnd);
    
    const codeBlock = '\n```\n' + selected + '\n```\n';
    bodyEditor.value = text.substring(0, start) + codeBlock + text.substring(bodyEditor.selectionEnd);
    bodyEditor.selectionStart = start + 4;
    bodyEditor.selectionEnd = start + 4 + selected.length;
    bodyEditor.focus();
}

function insertHorizontalRule() {
    const start = bodyEditor.selectionStart;
    const text = bodyEditor.value;
    bodyEditor.value = text.substring(0, start) + '\n---\n' + text.substring(start);
    bodyEditor.selectionStart = bodyEditor.selectionEnd = start + 5;
    bodyEditor.focus();
}

// =============================================================================
// Editor Resize
// =============================================================================
const editorContainer = document.getElementById('content-editor');
const resizeHandle = document.getElementById('editor-resize-handle');
let isResizing = false;
let startY, startHeight;

resizeHandle.addEventListener('mousedown', function(e) {
    isResizing = true;
    startY = e.clientY;
    startHeight = editorContainer.offsetHeight;
    document.body.style.cursor = 'ns-resize';
    document.body.style.userSelect = 'none';
});

document.addEventListener('mousemove', function(e) {
    if (!isResizing) return;
    const deltaY = e.clientY - startY;
    const newHeight = Math.max(200, Math.min(startHeight + deltaY, window.innerHeight - 200));
    editorContainer.style.height = newHeight + 'px';
});

document.addEventListener('mouseup', function() {
    if (isResizing) {
        isResizing = false;
        document.body.style.cursor = '';
        document.body.style.userSelect = '';
    }
});

// =============================================================================
// Expanded Editor Mode
// =============================================================================
let isExpanded = false;

function toggleExpandedEditor() {
    const container = document.getElementById('focused-editor-container');
    const btn = document.getElementById('expand-editor-btn');
    
    isExpanded = !isExpanded;
    container.classList.toggle('fe-expanded', isExpanded);
    btn.querySelector('.material-symbols-rounded').textContent = isExpanded ? 'close_fullscreen' : 'open_in_full';
    
    if (isExpanded) {
        bodyEditor.focus();
    }
}

// Escape to exit expanded mode
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && isExpanded) {
        toggleExpandedEditor();
    }
});

// =============================================================================
// Tabs
// =============================================================================
document.querySelectorAll('.fe-tab').forEach(function(tab) {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.fe-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.fe-panel').forEach(p => p.classList.remove('active'));
        
        this.classList.add('active');
        const panelId = 'panel-' + this.dataset.panel;
        document.getElementById(panelId)?.classList.add('active');
    });
});

// =============================================================================
// Status Buttons
// =============================================================================
document.querySelectorAll('.fe-status-btn input').forEach(function(radio) {
    radio.addEventListener('change', function() {
        document.querySelectorAll('.fe-status-btn').forEach(b => b.classList.remove('active'));
        this.closest('.fe-status-btn').classList.add('active');
    });
});

// =============================================================================
// Multiselect for Taxonomies
// =============================================================================
document.querySelectorAll('.fe-multiselect').forEach(function(multiselect) {
    const searchInput = multiselect.querySelector('.fe-multiselect-input');
    const options = multiselect.querySelectorAll('.fe-multiselect-option');
    const selectedContainer = multiselect.querySelector('.fe-multiselect-selected');
    const taxonomy = multiselect.dataset.taxonomy;
    
    // Search filter
    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        options.forEach(function(opt) {
            const text = opt.querySelector('.fe-term-name').textContent.toLowerCase();
            const slug = opt.dataset.slug.toLowerCase();
            opt.style.display = (text.includes(query) || slug.includes(query)) ? '' : 'none';
        });
    });
    
    // Handle option clicks
    options.forEach(function(opt) {
        const checkbox = opt.querySelector('input[type="checkbox"]');
        
        opt.addEventListener('click', function(e) {
            if (e.target.type !== 'checkbox') {
                checkbox.checked = !checkbox.checked;
                checkbox.dispatchEvent(new Event('change'));
            }
        });
        
        checkbox.addEventListener('change', function() {
            opt.classList.toggle('selected', this.checked);
            updateSelectedTags(multiselect);
        });
    });
    
    function updateSelectedTags(ms) {
        const selected = ms.querySelector('.fe-multiselect-selected');
        selected.innerHTML = '';
        
        ms.querySelectorAll('.fe-multiselect-option input:checked').forEach(function(cb) {
            const opt = cb.closest('.fe-multiselect-option');
            const name = opt.querySelector('.fe-term-name').textContent;
            const slug = opt.dataset.slug;
            const tax = ms.dataset.taxonomy;
            
            const tag = document.createElement('span');
            tag.className = 'fe-tag';
            tag.dataset.term = slug;
            tag.innerHTML = name + '<button type="button" class="fe-tag-remove" onclick="removeTermTag(this, \'' + tax + '\', \'' + slug + '\')">×</button>';
            selected.appendChild(tag);
        });
    }
});

function removeTermTag(btn, taxonomy, termSlug) {
    const multiselect = document.querySelector('.fe-multiselect[data-taxonomy="' + taxonomy + '"]');
    const option = multiselect.querySelector('.fe-multiselect-option[data-slug="' + termSlug + '"]');
    const checkbox = option.querySelector('input[type="checkbox"]');
    
    checkbox.checked = false;
    option.classList.remove('selected');
    btn.closest('.fe-tag').remove();
}

// =============================================================================
// Character Counts
// =============================================================================
document.querySelectorAll('.fe-char-count').forEach(function(counter) {
    const max = parseInt(counter.dataset.max);
    const field = counter.closest('.fe-field').querySelector('input, textarea');
    
    function update() {
        const len = field.value.length;
        counter.textContent = len + '/' + max;
        counter.classList.toggle('over', len > max);
    }
    
    field.addEventListener('input', update);
    update();
});

// =============================================================================
// Slug & ID Generation
// =============================================================================
function generateSlugFromTitle() {
    const title = document.getElementById('field-title').value;
    const slug = title.toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/[\s_]+/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '');
    document.getElementById('field-slug').value = slug;
}

function generateId() {
    const t = Date.now().toString(32).toUpperCase().padStart(10, '0');
    const chars = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
    let r = '';
    for (let i = 0; i < 16; i++) {
        r += chars[Math.floor(Math.random() * 32)];
    }
    document.getElementById('field-id').value = t + r;
}

// =============================================================================
// Array Fields
// =============================================================================
function addArrayItem(containerId, fieldName, placeholder) {
    const container = document.getElementById(containerId);
    const div = document.createElement('div');
    div.className = 'fe-array-item';
    div.innerHTML = `
        <input type="text" name="fields[${fieldName}][]" class="fe-input" placeholder="${placeholder}">
        <button type="button" class="fe-array-remove" onclick="removeArrayItem(this)">
            <span class="material-symbols-rounded">close</span>
        </button>
    `;
    container.appendChild(div);
    div.querySelector('input').focus();
}

function addAssetItem(containerId, fieldName, placeholder) {
    const container = document.getElementById(containerId);
    const div = document.createElement('div');
    div.className = 'fe-array-item';
    div.innerHTML = `
        <input type="text" name="fields[${fieldName}][]" class="fe-input" placeholder="${placeholder}">
        <button type="button" class="fe-input-btn" onclick="openMediaPicker('asset', this.previousElementSibling)">
            <span class="material-symbols-rounded">folder</span>
        </button>
        <button type="button" class="fe-array-remove" onclick="removeArrayItem(this)">
            <span class="material-symbols-rounded">close</span>
        </button>
    `;
    container.appendChild(div);
    div.querySelector('input').focus();
}

function removeArrayItem(btn) {
    btn.closest('.fe-array-item').remove();
}

// =============================================================================
// Media Picker
// =============================================================================
let mediaPickerTarget = null;
let mediaPickerMode = 'body';
let selectedMediaFile = null;
let allMediaFiles = [];

function openMediaPicker(mode, targetInput) {
    mediaPickerMode = mode;
    mediaPickerTarget = targetInput || null;
    selectedMediaFile = null;
    
    document.getElementById('selected-file-info').textContent = '';
    document.getElementById('media-insert-btn').disabled = true;
    document.getElementById('media-picker-modal').style.display = 'flex';
    
    loadMediaFiles();
}

function closeMediaPicker() {
    document.getElementById('media-picker-modal').style.display = 'none';
    mediaPickerTarget = null;
    selectedMediaFile = null;
}

async function loadMediaFiles() {
    const grid = document.getElementById('media-grid');
    grid.innerHTML = '<div class="fe-media-loading"><span class="material-symbols-rounded spin">sync</span> Loading...</div>';
    
    try {
        const folder = document.getElementById('media-folder').value;
        const url = '<?= htmlspecialchars($admin_url) ?>/api/media' + (folder ? '?folder=' + encodeURIComponent(folder) : '');
        
        const response = await fetch(url, {
            headers: { 'Accept': 'application/json' }
        });
        
        if (!response.ok) throw new Error('Failed to load media');
        
        const data = await response.json();
        allMediaFiles = data.files || [];
        
        // Populate folder dropdown
        if (data.folders) {
            const folderSelect = document.getElementById('media-folder');
            folderSelect.innerHTML = '<option value="">All Files</option>';
            data.folders.forEach(f => {
                folderSelect.innerHTML += '<option value="' + f + '">' + f + '</option>';
            });
        }
        
        renderMediaGrid(allMediaFiles);
    } catch (err) {
        grid.innerHTML = '<div class="fe-media-empty"><span class="material-symbols-rounded">error</span> Failed to load media</div>';
    }
}

function loadMediaFolder() {
    loadMediaFiles();
}

function filterMedia() {
    const query = document.getElementById('media-search').value.toLowerCase();
    const filtered = allMediaFiles.filter(f => f.name.toLowerCase().includes(query));
    renderMediaGrid(filtered);
}

function renderMediaGrid(files) {
    const grid = document.getElementById('media-grid');
    
    if (files.length === 0) {
        grid.innerHTML = '<div class="fe-media-empty"><span class="material-symbols-rounded">folder_off</span> No files found</div>';
        return;
    }
    
    grid.innerHTML = files.map(file => {
        const isImage = /\.(jpg|jpeg|png|gif|webp|svg|avif)$/i.test(file.name);
        const thumb = isImage ? file.url : '';
        const icon = isImage ? '' : '<span class="material-symbols-rounded">description</span>';
        
        return `
            <div class="fe-media-item ${isImage ? 'is-image' : ''}" data-path="${file.path}" data-url="${file.url}" onclick="selectMediaFile(this)">
                ${isImage ? '<img src="' + thumb + '" alt="">' : icon}
                <span class="fe-media-name">${file.name}</span>
            </div>
        `;
    }).join('');
}

function selectMediaFile(el) {
    document.querySelectorAll('.fe-media-item.selected').forEach(i => i.classList.remove('selected'));
    el.classList.add('selected');
    
    selectedMediaFile = {
        path: el.dataset.path,
        url: el.dataset.url,
        name: el.querySelector('.fe-media-name').textContent
    };
    
    document.getElementById('selected-file-info').textContent = selectedMediaFile.name;
    document.getElementById('media-insert-btn').disabled = false;
}

function insertSelectedMedia() {
    if (!selectedMediaFile) return;
    
    const path = '@media:' + selectedMediaFile.path.replace(/^\/?(media\/)?/, '');
    
    if (mediaPickerMode === 'body') {
        // Insert as markdown image
        const isImage = /\.(jpg|jpeg|png|gif|webp|svg|avif)$/i.test(selectedMediaFile.name);
        const markdown = isImage 
            ? '![' + selectedMediaFile.name + '](' + path + ')' 
            : '[' + selectedMediaFile.name + '](' + path + ')';
        
        insertMarkdown(markdown, '');
    } else if (mediaPickerTarget) {
        // Insert into target input
        mediaPickerTarget.value = path;
    } else if (mediaPickerMode === 'og_image') {
        document.getElementById('field-og-image').value = path;
    }
    
    closeMediaPicker();
}

// =============================================================================
// Keyboard Shortcuts
// =============================================================================
document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        document.getElementById('focused-editor-form').submit();
    }
    
    if ((e.ctrlKey || e.metaKey) && e.key === 'b' && document.activeElement === bodyEditor) {
        e.preventDefault();
        insertMarkdown('**', '**');
    }
    
    if ((e.ctrlKey || e.metaKey) && e.key === 'i' && document.activeElement === bodyEditor) {
        e.preventDefault();
        insertMarkdown('*', '*');
    }
    
    if ((e.ctrlKey || e.metaKey) && e.key === 'k' && document.activeElement === bodyEditor) {
        e.preventDefault();
        insertMarkdown('[', '](url)');
    }
});

// =============================================================================
// Form Validation
// =============================================================================
document.getElementById('focused-editor-form').addEventListener('submit', function(e) {
    const title = document.getElementById('field-title').value.trim();
    const slug = document.getElementById('field-slug').value.trim();
    
    if (!title) {
        e.preventDefault();
        alert('Title is required.');
        document.getElementById('field-title').focus();
        return;
    }
    
    if (!slug) {
        e.preventDefault();
        alert('Slug is required.');
        document.getElementById('field-slug').focus();
        return;
    }
});
</script>
