---
id: 01JGMK0000ABOUT00000000001
title: About Ava
slug: about
status: published
---

# About Ava CMS

Ava is a **flat-file content management system** for people who love working with files and clean code. It's built for personal sites, blogs, portfolios, and small content sites where simplicity matters.

## Philosophy

### Your Files Are the Source of Truth

Your content lives in Markdown files. Back them up however you like—copy to a folder, sync to the cloud, or use version control. Your data is always portable and yours to control.

### The Filesystem is Trusted

Files are files. Folders are folders. Save `hello.md` in `content/posts/`, and it becomes `/blog/hello` on your site.

### The CMS Gets Out of Your Way

No WYSIWYG fighting your HTML. No media library when your OS has a file browser. Ava assumes you know what you're doing and trusts you to do it.

### Performance is a Feature

Ava uses **two-layer caching** that makes most requests complete in under a millisecond:

1. **Page cache** — Full HTML pages cached as files
2. **Content index** — Parsed metadata cached as binary data

Your site is fast without CDNs or optimization plugins.

## Who is Ava For?

Ava is perfect if you:

- Write content in **Markdown** with your favorite editor
- Want full control over your **PHP templates** and HTML
- Need a site that's **fast by default**
- Want something **simple** you can understand in an afternoon
- Value **clarity** over complexity

## Who is Ava NOT For?

Ava might not be right if you:

- Need a visual page builder or drag-and-drop editor
- Want a WordPress-like admin for non-technical users
- Need complex user management or memberships
- Require e-commerce out of the box

## Technical Stack

- **PHP 8.3+** — Modern PHP with typed properties
- **League CommonMark** — Standards-compliant Markdown
- **Symfony YAML** — Configuration and frontmatter
- **igbinary** (optional) — Fast binary serialization

No frameworks. The entire core is under 3,000 lines of readable code.

## Get Involved

- **Documentation**: [ava.addy.zone](https://ava.addy.zone)
- **Source Code**: [github.com/adamgreenough/ava](https://github.com/adamgreenough/ava)
- **Discord**: [discord.gg/Z7bF9YeK](https://discord.gg/Z7bF9YeK)

---

**Now go build something!** Start by editing this page or check out the [homepage](/) for next steps. 🚀
