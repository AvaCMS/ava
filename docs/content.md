# Writing Content

Content in Ava is just text. You write in Markdown, which is a simple way to format text, and save it as a file. There's no database to manage—your files are your content.

## The Basics

Every piece of content is a `.md` file with two parts:

1. **💌 Frontmatter** — Metadata about the content (like title, date, status) at the top. Think of it like the address on an envelope.
2. **📝 Body** — The actual content, written in Markdown.

```markdown
---
title: My First Post
slug: my-first-post
status: published
date: 2024-12-28
---

# Hello World

This is my first post. I can use **bold**, *italics*, and [links](https://example.com).
```

## Organizing Your Files

Content lives in the `content/` folder. You can organize it however you like, but typically it looks like this:

```
content/
├── pages/           # Standard pages like About or Contact
│   ├── index.md     # Your homepage
│   ├── about.md     # /about
│   └── services/
│       └── web.md   # /services/web
├── posts/           # Blog posts
│   └── hello.md     # /blog/hello
└── _taxonomies/     # Categories and Tags
    ├── category.yml
    └── tag.yml
```

## Frontmatter Guide

Frontmatter is just a list of settings for your page. It goes between two lines of `---`.

### Essential Fields

| Field | What it does | Example |
|-------|-------------|---------|
| `title` | The name of your page or post. | `"My Post Title"` |
| `slug` | The URL-friendly name. If you leave this out, Ava makes one for you! | `"my-post-title"` |
| `status` | Controls visibility. Use `draft` while writing. | `draft`, `published` |

### Useful Extras

| Field | What it does | Example |
|-------|-------------|---------|
| `date` | When this was published. | `2024-12-28` |
| `excerpt` | A short summary for lists and search results. | `"A brief intro..."` |
| `template` | Use a specific layout for this page. | `"custom-post"` |

### SEO Superpowers

Ava handles the technical SEO stuff for you, but you can override it:

| Field | Description |
|-------|-------------|
| `meta_title` | Custom title for search engines (defaults to your Title). |
| `meta_description` | Description for search results. |
| `noindex` | Set to `true` to hide this page from search engines. |
| `og_image` | Image to show when shared on social media. |

### Organizing with Taxonomies

You can tag and categorize your content easily:

```yaml
category:
  - tutorials
  - php
tag:
  - getting-started
```

## Redirects

When you move or rename content, set up redirects in the new file:

```yaml
redirect_from:
  - /old-url
  - /another-old-url
```

Requests to the old URLs will 301 redirect to the new location.

## Per-Item Assets

Load CSS or JS only on specific pages:

```yaml
assets:
  css:
    - "@uploads:2024/custom-post.css"
  js:
    - "@assets:interactive-chart.js"
```

## Path Aliases

Use aliases instead of hard-coded URLs. These are configured in `ava.php` and expanded at render time:

| Alias | Default Expansion |
|-------|-------------------|
| `@media:` | `/media/` |
| `@uploads:` | `/media/uploads/` |
| `@assets:` | `/assets/` |

Use in your Markdown:

```markdown
![Hero image](@uploads:2024/hero.jpg)

[Download PDF](@media:docs/guide.pdf)
```

This makes it easy to change asset locations later without updating every content file.

## Shortcodes

Embed dynamic content using shortcodes:

```markdown
Current year: [year]

Site name: [site_name]

[button url="/contact"]Contact Us[/button]

[snippet name="cta" heading="Join Us"]
```

See [Shortcodes](shortcodes.md) for the full reference.

## Content Status

| Status | Visibility |
|--------|------------|
| `draft` | Hidden from site. Viewable with preview token. |
| `published` | Visible to everyone. |
| `private` | Hidden from listings. Accessible via direct URL with preview token. |

## Creating Content

### Via CLI (Recommended)

```bash
# Create a page
./ava make page "About Us"

# Create a post
./ava make post "Hello World"
```

This creates a properly formatted file with:
- Generated ULID
- Slugified filename
- Date (for dated types)
- Draft status

### Manually

Create a `.md` file in the appropriate directory:

```bash
# content/posts/my-new-post.md
```

Add frontmatter and content, then save. If cache mode is `auto`, the site updates immediately.

## Validation

Run the linter to check all content:

```bash
./ava lint
```

This catches:
- Invalid YAML syntax
- Missing required fields
- Invalid status values
- Malformed slugs
- Duplicate slugs or IDs
