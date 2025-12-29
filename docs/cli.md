# CLI Reference

Ava includes a command-line interface for managing your site. Run commands from your project root:

```bash
./ava <command> [options]
```

## Quick Reference

| Command | Description |
|---------|-------------|
| `status` | Show site overview and health |
| `rebuild` | Force cache rebuild |
| `lint` | Validate content files |
| `make <type> "Title"` | Create new content |
| `prefix <add\|remove> [type]` | Toggle date prefixes on filenames |
| `user:add` | Create admin user |
| `user:password` | Update user password |
| `user:remove` | Remove admin user |
| `user:list` | List all users |
| `update:check` | Check for updates |
| `update:apply` | Apply available update |
| `pages:stats` | Page cache statistics |
| `pages:clear` | Clear page cache |
| `stress:generate` | Generate test content |
| `stress:clean` | Remove test content |

---

## Site Management

### status

Shows a quick overview of your site's health:

```bash
./ava status
```

```
=== Ava CMS Status ===

Site: My Site
URL:  https://example.com

Cache:
  Status: ✓ Fresh
  Mode:   auto
  Built:  2024-12-28 14:30:00

Content:
  page: 5 total (5 published, 0 drafts)
  post: 42 total (38 published, 4 drafts)

Taxonomies:
  category: 8 terms
  tag: 23 terms
```

### rebuild

Force the cache to rebuild:

```bash
./ava rebuild
```

Use this after deploying new content in production, or if something looks stuck.

### lint

Validate all content files for common problems:

```bash
./ava lint
```

Checks for:

| Check | What it means |
|-------|---------------|
| YAML syntax | Frontmatter must parse correctly |
| Required fields | `title`, `slug`, `status` are present |
| Status values | Must be `draft`, `published`, or `private` |
| Slug format | Lowercase, alphanumeric, hyphens only |
| Duplicate slugs | Within the same content type |
| Duplicate IDs | Across all content |

---

## Content Creation

### make

Create new content with proper scaffolding:

```bash
./ava make <type> "Title"
```

Examples:

```bash
# Create a page
./ava make page "About Us"
# → content/pages/about-us.md

# Create a blog post
./ava make post "Hello World"
# → content/posts/hello-world.md

# Create custom type content
./ava make recipe "Chocolate Cake"
# → content/recipes/chocolate-cake.md
```

The generated file includes auto-generated ID, slug, and appropriate frontmatter for the content type.

Run without arguments to see available types:

```bash
./ava make
# Available types:
#   page - Pages
#   post - Posts
```

### prefix

Toggle date prefixes on content filenames:

```bash
./ava prefix <add|remove> [type]
```

Examples:

```bash
# Add date prefix to all posts
./ava prefix add post
# → hello-world.md becomes 2024-12-28-hello-world.md

# Remove date prefix from posts
./ava prefix remove post
```

This reads the `date` field from frontmatter. Run `ava rebuild` after to update the cache.

---

## User Management

Manage admin dashboard users. Users are stored in `app/config/users.php`.

### user:add

Create a new admin user:

```bash
./ava user:add <email> <password> [name]
```

Example:

```bash
./ava user:add admin@example.com secretpass "Admin User"
```

### user:password

Update an existing user's password:

```bash
./ava user:password <email> <new-password>
```

### user:remove

Remove a user:

```bash
./ava user:remove <email>
```

### user:list

List all configured users:

```bash
./ava user:list
```

---

## Updates

### update:check

Check for available Ava updates:

```bash
./ava update:check
```

Results are cached for 1 hour. Force a fresh check:

```bash
./ava update:check --force
```

### update:apply

Download and apply the latest update:

```bash
./ava update:apply
```

The updater will:
1. Show what will be updated
2. Ask for confirmation
3. Download the release
4. Apply updates to core files
5. Rebuild the cache

Skip confirmation with `-y`:

```bash
./ava update:apply -y
```

See [Updates](updates.md) for details on what gets updated and preserved.

---

## Page Cache

Commands for managing the on-demand HTML page cache.

### pages:stats

View page cache statistics:

```bash
./ava pages:stats
```

```
=== Page Cache Stats ===

Status:  ✓ Enabled
TTL:     Forever (until cleared)

Cached:  42 page(s)
Size:    1.2 MB
Oldest:  2024-12-28 10:00:00
Newest:  2024-12-28 14:30:00
```

### pages:clear

Clear cached pages:

```bash
# Clear all cached pages (with confirmation)
./ava pages:clear

# Clear pages matching a URL pattern
./ava pages:clear /blog/*
./ava pages:clear /products/*
```

The page cache is also automatically cleared when:
- You run `./ava rebuild`
- Content changes (in `cache.mode = 'auto'`)

See [Configuration](configuration.md#page-cache) for setup options.

---

## Stress Testing

Commands for testing performance with large amounts of content.

### stress:generate

Generate dummy content for stress testing:

```bash
./ava stress:generate <type> <count>
```

Examples:

```bash
# Generate 100 test posts
./ava stress:generate post 100

# Generate 1000 test posts
./ava stress:generate post 1000

# Generate 10000 test posts (max)
./ava stress:generate post 10000
```

Generated content includes:
- Random lorem ipsum titles and content
- Random dates (within last 2 years for dated types)
- Random taxonomy terms from configured taxonomies
- 80% published, 20% draft status
- Files prefixed with `_dummy-` for easy identification

### stress:clean

Remove all generated test content:

```bash
./ava stress:clean <type>
```

This finds and deletes all files matching `_dummy-*.md` in the content directory, then rebuilds the cache.

---

## Exit Codes

| Code | Meaning |
|------|---------|
| 0 | Success |
| 1 | Error (invalid arguments, validation failures, etc.) |

---

## Common Workflows

### Development

```bash
# Start dev server
php -S localhost:8000 -t public

# Cache rebuilds automatically when files change
# (when cache.mode is 'auto')
```

### Production Deploy

```bash
# In production, set cache.mode to 'never'
# Then rebuild after deploy:
./ava rebuild
```

### Content Validation

```bash
# Before committing content changes:
./ava lint

# If errors found, fix and re-run
```

### Performance Testing

```bash
# Generate test content
./ava stress:generate post 1000

# Check status (should be fast!)
./ava status

# Clean up when done
./ava stress:clean post
```
