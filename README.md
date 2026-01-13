<p align="center">
<picture>
  <source media="(prefers-color-scheme: dark)" srcset="https://ava.addy.zone/media/dark.png">
  <source media="(prefers-color-scheme: light)" srcset="https://ava.addy.zone/media/light.png">
  <img alt="Ava CMS" src="https://ava.addy.zone/media/light.png">
</picture>
</p>

<p align="center">
  <strong>A fast, flexible, file-based CMS built with modern PHP.</strong><br>
</p>

---

# Ava CMS

[![Release](https://img.shields.io/github/v/release/AvaCMS/ava)](https://github.com/AvaCMS/ava/releases)
[![Issues](https://img.shields.io/github/issues/AvaCMS/ava)](https://github.com/AvaCMS/ava/issues)
[![Stars](https://img.shields.io/github/stars/AvaCMS/ava)](https://github.com/AvaCMS/ava/stargazers)
[![Code size](https://img.shields.io/github/languages/code-size/AvaCMS/ava)](https://github.com/AvaCMS/ava)
[![License](https://img.shields.io/github/license/AvaCMS/ava)](https://github.com/AvaCMS/ava/blob/main/LICENSE)
[![Discord](https://img.shields.io/discord/1028357262189801563)](https://discord.gg/fZwW4jBVh5)

**Links:** [Docs](https://ava.addy.zone/) · [Releases](https://github.com/AvaCMS/ava/releases) · [Issues](https://github.com/AvaCMS/ava/issues) · [Discord](https://discord.gg/fZwW4jBVh5)

**Explore:** [Themes](https://ava.addy.zone/themes) · [Plugins](https://ava.addy.zone/plugins) · [Showcase](https://ava.addy.zone/showcase)

Build content in Markdown. Render with plain PHP. No database required.

Ava is a modern flat‑file CMS for developers and content creators who want simplicity without giving up control. Your content lives as **Markdown files** on disk, your theme is **PHP + HTML**, and your configuration is a small set of PHP arrays.

## Why Ava

- **Markdown & HTML**: Write fast in Markdown, drop into HTML when you want full control.
- **Instant feedback**: No manual build step or deploy queue — edit a file, refresh, see it live.
- **Design freedom**: Standard HTML/CSS templates, with PHP only where you need dynamic data.
- **Model anything**: Content types + taxonomies make blogs, portfolios, docs, and catalogs feel natural.
- **Dev-friendly**: CLI, hooks, and plugins keep advanced features clean and optional.
- **Scale when you need**: Start with flat files, switch backends (like SQLite) for huge sites.

## ✨ Highlights

- **Markdown + front matter** content on disk
- **Content types & taxonomies** for structured sites
- **Built-in CLI** for common tasks (including cache management)
- **Bundled plugins** like sitemap, redirects, and feeds
- **Pragmatic defaults** with plenty of escape hatches
- **No database by default**, with optional backends for scale (including SQLite)

### How it works

1. **Write** — Create Markdown files in `content/`.
2. **Index** — Ava automatically scans your files and builds a fast index.
3. **Render** — Your theme turns content into HTML.

## 📸 Screenshots

![Ava CMS screenshots](https://ava.addy.zone/media/screenshots.png)

## 🏁 Quick Start

### Requirements

- **PHP 8.3+**
- **Composer**

That’s it — Ava is designed to run happily on shared hosting, a VPS, or locally.

### 1) Install

**Option A: Download a release**

- Grab the latest release from https://github.com/AvaCMS/ava/releases
- Unzip it into a new folder

**Option B: Clone from GitHub**

```bash
git clone https://github.com/AvaCMS/ava.git my-site
cd my-site
composer install
```

If you downloaded a release zip, just run:

```bash
composer install
```

### 2) Run locally

Start the built-in PHP development server:

```bash
./ava start
# or
php -S localhost:8000 -t public
```

Visit `http://localhost:8000`.

### 3) Create content

Add a new page by creating a Markdown file in `content/pages/`.

**File:** `content/pages/hello.md`

```markdown
---
title: Hello World
slug: hello-world
status: published
---

# Welcome to Ava!

This is my first page. It's just a text file.
```

Visit `http://localhost:8000/hello-world` to see it live.

## 📚 Documentation

Documentation lives at **https://ava.addy.zone/**.

- [Getting Started](https://ava.addy.zone/docs)
- [Configuration](https://ava.addy.zone/docs/configuration)
- [Theming](https://ava.addy.zone/docs/theming)
- [CLI](https://ava.addy.zone/docs/cli)
- [Plugin Development](https://ava.addy.zone/docs/creating-plugins)

## 🔌 Plugins

Ava includes a simple hook-based plugin system. A few plugins are bundled in this repo (like sitemap, redirects, and a feed plugin) so you can see the pattern and ship common features quickly.

Browse community plugins at https://ava.addy.zone/plugins

## 🏗️ Project Structure

Here is what an Ava project looks like:

```text
my-site/
├── app/
│   └── config/          # Site configuration (ava.php, content types, etc.)
├── content/
│   ├── pages/           # Your Markdown content
│   └── ...
├── themes/              # PHP themes
├── plugins/             # Site plugins
├── public/              # Web root (assets, index.php)
├── storage/             # Cache and logs
├── vendor/              # Composer dependencies
└── ava                  # CLI tool
```

## ⚡ Performance

Ava is designed to be fast by default, whether you have 100 pages or 100,000:

- **No manual build step**: publish instantly (indexing is automatic).
- **Tiered caching**: avoid repeating expensive work on every request.
- **Page caching** (optional): serve cached HTML to bypass PHP for most visitors.

See https://ava.addy.zone/docs/performance

## 🤝 Contributing & Community

Feedback is the most helpful thing right now.

- Bugs, questions, and ideas: https://github.com/AvaCMS/ava/issues
- Chat & support: https://discord.gg/fZwW4jBVh5
- Community themes: https://ava.addy.zone/themes
- Community plugins: https://ava.addy.zone/plugins
- Sites built with Ava: https://ava.addy.zone/showcase

If you’d like to contribute core code, open an issue first so we can agree on approach and scope.

## 📄 License

Ava is open-source software licensed under the [MIT license](LICENSE).
