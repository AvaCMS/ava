<p align="center">
<picture>
  <source media="(prefers-color-scheme: dark)" srcset="https://ava.addy.zone/media/dark.png">
  <source media="(prefers-color-scheme: light)" srcset="https://ava.addy.zone/media/light.png">
  <img alt="Fallback image description" src="https://ava.addy.zone/media/light.png">
</picture>
</p>

<p align="center">
  <strong>A fast, flexible, file-based CMS.</strong><br>
</p>

---

# Ava CMS

[![Release](https://img.shields.io/github/v/release/avacms/ava)](https://github.com/avacms/ava/releases)
[![Issues](https://img.shields.io/github/issues/avacms/ava)](https://github.com/avacms/ava/issues)
[![Stars](https://img.shields.io/github/stars/avacms/ava)](https://github.com/avacms/ava/stargazers)
[![Code size](https://img.shields.io/github/languages/code-size/avacms/ava)](https://github.com/avacms/ava)
[![Discord](https://img.shields.io/discord/1028357262189801563)](https://discord.gg/fZwW4jBVh5)

Ava is a modern flat-file CMS built for developers and content creators who want simplicity without sacrificing power. Your content lives as **Markdown files** on disk. Your theme is just **PHP & HTML**. Your configuration is a simple array.

There is **no database** to manage, **no complex build pipeline** to configure, and **no vendor lock-in**. If you can write Markdown and edit files, you can build a site with Ava.

### ✨ Key Features

*   **📂 Flat-File Architecture**: Your content is portable, version-controllable, and human-readable.
*   **⚡ Blazing Fast**: Heavy caching layer ensures your site loads instantly.
*   **🔌 Zero-Database**: No MySQL, PostgreSQL, or SQLite connection to robustly manage.
*   **🛠️ Powerful CLI**: A friendly command-line tool for managing your site, clearing cache, and more.
*   **🎛️ Admin Dashboard**: Optional built-in admin panel for quick edits and content management on the go.
*   **🎨 Flexible Theming**: Use standard PHP templates. No new templating language to learn.
*   **🧩 Plugin System**: Extend functionality with hooks and bundled plugins (Sitemap, Redirects, etc.).

## 📸 Screenshots

![Ava CMS screenshots](https://ava.addy.zone/media/screenshots.png)

## 🛠️ Requirements

*   **PHP 8.3** or higher
*   **Composer**

That's it. Ava runs on almost any shared hosting, VPS, or local machine that supports modern PHP.

## 🏁 Quick Start

### 1. Installation

Clone the repository and install dependencies:

```bash
<<<<<<< HEAD
# 1. Download from GitHub releases (or git clone)
#    https://github.com/avacms/ava/releases

# 2. Install dependencies
=======
git clone https://github.com/ava-cms/ava.git my-site
cd my-site
>>>>>>> 035ee8b639e389b817b0a8cd0a778c4063972490
composer install
```

### 2. Run Locally

Start the built-in PHP development server:

```bash
./ava start
# OR simply
php -S localhost:8000 -t public
```

Visit `http://localhost:8000` to see your new Ava site!

### 3. Create Content

Add a new page by creating a Markdown file in `content/pages/`:

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

Detailed documentation is available at **[ava.addy.zone](https://ava.addy.zone/)**.

*   [**Getting Started**](https://ava.addy.zone/docs)
*   [**Configuration**](https://ava.addy.zone/docs/configuration)
*   [**Theming Guide**](https://ava.addy.zone/docs/theming)
*   [**CLI Reference**](https://ava.addy.zone/docs/cli)
*   [**Plugin Development**](https://ava.addy.zone/docs/creating-plugins)

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

## 🤝 Contributing

Ava is still fairly early and moving quickly, so I'm not looking for undiscussed pull requests or additional contributors just yet.

That said, I'd genuinely love your feedback:

- If you run into a bug, get stuck, or have a "this could be nicer" moment, please [open an issue](https://github.com/ava-cms/ava/issues).
- Feature requests, ideas, and suggestions are very welcome.

If you prefer a more conversational place to ask questions and share ideas, join the [Discord community](https://discord.gg/fZwW4jBVh5).

<<<<<<< HEAD
## Performance

Ava is designed to be fast by default, whether you have 100 posts or 100,000.

- **Instant Publishing:** No build step. Edit a file, hit refresh, and it's live.
- **Smart Caching:** A tiered caching system ensures your most popular pages load instantly.
- **Scalable Backends:** Start with the default Array backend for raw speed, or switch to SQLite for constant memory usage at scale.
- **Static Speed:** Enable full page caching to serve static HTML files, bypassing the application entirely for most visitors.

[See full benchmarks and scaling guide →](https://ava.addy.zone/docs/performance)

## Contributing

Ava is moving quickly, so I'm not accepting undiscussed pull requests right now. The best way to help:

- [Open an issue](https://github.com/avacms/ava/issues) — bugs, ideas, questions all welcome
- [Join the Discord](https://discord.gg/fZwW4jBVh5) — chat and support

## License

MIT — free and open source. See [LICENSE](LICENSE).
=======
## 📄 License
>>>>>>> 035ee8b639e389b817b0a8cd0a778c4063972490

Ava is open-source software licensed under the [MIT license](LICENSE).
