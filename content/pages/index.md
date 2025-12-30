---
id: 01JGMK0000HOMEPAGE00000001
title: Home
slug: index
status: published
template: page.php
---

# Welcome to Ava! 👋

You did it! Ava is up and running. This is your homepage, rendered from a simple Markdown file at `content/pages/index.md`.

## What is Ava?

Ava is a **flat-file CMS** for people who love the web. No databases, no build steps—just Markdown files, PHP templates, and fast caching.

**Why you'll love it:**

- **📝 Write in Markdown** — Your content lives in `content/` as plain `.md` files
- **🚀 Blazingly Fast** — Two-layer caching means sub-millisecond page loads
- **🎨 Your HTML** — Templates are plain PHP, no templating language to learn
- **📦 Portable** — Back up with a folder copy, sync to the cloud, or use version control
- **🛠️ Zero Complexity** — No npm, no webpack, no build pipeline

## Quick Start

### Create a Page

Add a new file at `content/pages/about.md`:

```markdown
---
title: About Us
slug: about
status: published
---

# About Us

Welcome to our site!
```

Save it and visit `/about`. That's it!

### Create a Blog Post

Add a file at `content/posts/my-post.md`:

```markdown
---
title: My First Post
slug: my-first-post
date: 2024-12-28
status: published
---

# Hello World

This is my first blog post.
```

Or use the CLI: `./ava make post "My First Post"`

### Customize Your Theme

Templates live in `themes/default/templates/`. They're plain PHP with access to `$page` and the `$ava` helper:

```php
<h1><?= $ava->e($page->title()) ?></h1>
<?= $ava->content($page) ?>
```

## Learn More

- 📚 **[Full Documentation](https://ava.addy.zone)** — Everything you need to know
- 💻 **[GitHub](https://github.com/adamgreenough/ava)** — Source code and issues
- 💬 **[Discord](https://discord.gg/Z7bF9YeK)** — Chat and support

**Now go build something awesome!** Edit this page, create new content, and make the site your own. 🚀
