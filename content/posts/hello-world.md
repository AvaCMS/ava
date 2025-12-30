---
id: 01JGMK0000POST0000000000001
title: Hello World
slug: hello-world
status: published
date: 2024-12-28
excerpt: A sample blog post demonstrating Ava's content structure. Replace or delete it to get started.
category:
  - getting-started
tag:
  - welcome
  - example
---

Welcome to your new Ava site!

This is a sample blog post to show you how content works. Each post is a Markdown file in `content/posts/` with YAML frontmatter at the top.

## Writing Content

Ava uses standard Markdown, so you can write **bold**, *italic*, and create [links](/) just like you'd expect.

### Code Examples

Syntax highlighting works automatically:

```php
// Query recent posts in your templates
$posts = $ava->query('post')->limit(5)->get();
```

### Lists and Structure

- Posts live in `content/posts/`
- Pages live in `content/pages/`
- Custom content types are configured in `app/config/content_types.php`

## Built-in Shortcodes

Insert dynamic content anywhere:

- **Current year:** [year]
- **Site name:** [site_name]

## Next Steps

1. Edit this post or delete it
2. Create your own content in `content/posts/`
3. Customize your theme in `themes/default/`
4. Read the [documentation](https://ava.addy.zone) for more

Happy publishing!
