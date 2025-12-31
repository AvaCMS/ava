# Updates

Keeping Ava up to date is easy. We release updates regularly with new features and bug fixes.

!> **Always ensure you have a good backup before attempting to update.** Ava is in fairly early development and while we hope the updater will continue to seamlessly carry you through future versions, breaking changes may occur. See [Backup Strategies](#backup-strategies) below.

## How to Update

The easiest way is using the [CLI](cli.md):

```bash
# 1. Check for updates
./ava update:check

# 2. Apply the update
./ava update:apply
```

The updater will ask you to confirm that you have a backup before proceeding.

Ava will download the latest version from GitHub and update your core files.

## Backup Strategies

Because Ava is a flat-file CMS, backing up is incredibly simple. You don't need to dump databases or export complex configurations. You just need to copy files.

<div class="beginner-box">

## What Should I Back Up?

The most important folders to back up are:

- **`content/`** — All your pages, posts, and media
- **`app/config/`** — Your site settings
- **`themes/`** — Your customised themes

Everything else (like `core/`, `vendor/`, `storage/cache/`) can be regenerated or re-downloaded.

### The 3-2-1 Rule

For important data, consider the [**3-2-1 Backup Rule**](https://www.backblaze.com/blog/the-3-2-1-backup-strategy/):

- **3 Copies:** Your live site plus at least two backups
- **2 Different Places:** Store them on different types of storage (e.g., cloud + local)
- **1 Off-Site:** Keep at least one copy somewhere other than your server

</div>

Here are some backup approaches, from simplest to most automated.

### 1. Download a Copy (Simple)

Just download your files and keep them safe somewhere.

**How:**
- Use SFTP to download your site folder
- Or use your host's file manager to create and download a ZIP
- Or via command line: `zip -r backup-$(date +%Y-%m-%d).zip .`

**Pros:** Quick, works anywhere, no setup required.
**Cons:** Manual effort, easy to forget, stored on same server until you download it.

### 2. Git Repository

If you're already using Git, your remote repository (GitHub, GitLab, etc.) is a natural backup.

**How:** Commit your changes and push to a remote repository.

```bash
git add .
git commit -m "Backup before update"
git push origin main
```

**Pros:** Automatic history of every change, off-site storage, easy to roll back.
**Cons:** Requires Git knowledge, you need to remember to commit and push.

### 3. Cloud Sync (Set and Forget)

For production sites, consider automated sync to cloud storage.

**Options:**
- Use tools like `rclone` or `rsync` to sync folders
- Many hosts offer automated backups (check your control panel)
- Cloud services like Dropbox, Google Drive, or S3 can sync automatically

**Example with rclone:**
```bash
rclone sync ./content remote:my-ava-backups/content
rclone sync ./app remote:my-ava-backups/app
```

**Pros:** Automatic, protects against server failure.
**Cons:** Requires initial setup, may have small storage costs.

### Which Should I Choose?

| Approach | Best For |
|----------|----------|
| **Download a copy** | Occasional backups, before updates |
| **Git repository** | Developers, version tracking, collaboration |
| **Cloud sync** | Production sites, automated protection |

Many people combine approaches—Git for development history, plus periodic manual downloads before big changes.

## What Gets Updated?

The updater updates the core system files only. It's designed to leave your content and configuration alone.

**Updated:**
- `core/` — The Ava engine
- `bin/` — CLI tools
- `docs/` — Documentation
- `public/index.php` — Entry point
- `public/assets/admin.css` — Admin styles
- `bootstrap.php` — Bootstrap file
- `composer.json` — Dependencies

**Bundled plugins** (like `sitemap`, `feed`, `redirects`) are also updated, but new plugins aren't automatically activated.

**Not touched:**
- `content/` — Your pages, posts, and media
- `app/config/` — Your settings
- `themes/` — Your themes (including the default theme)
- `plugins/` — Your custom plugins (non-bundled)
- `storage/` — Cache, logs, temp files

!> **Important:** While the updater is designed to preserve your files, things can go wrong—especially during early development. Always have a backup before updating. If an update fails midway, you can restore from backup and try again, or do a [manual update](#manual-updates).

## Version Numbers

We use a simple date-based versioning system (like `25.12.1` for December 2025). This makes it easy to see how old your version is at a glance.

This scheme:
- Tells you roughly when a release was made
- Avoids semantic versioning debates
- Always increases (newer = higher)
- Allows unlimited releases per month

## Manual Updates

If you prefer not to use the built-in updater:

1. Download the latest release from GitHub
2. Extract and copy the files listed in "What Gets Updated"
3. Run `php bin/ava rebuild` to rebuild the content index
4. Run `composer install` if `composer.json` changed

## Troubleshooting

### "Could not fetch release info from GitHub"

- Check your internet connection
- GitHub API may be rate-limited (60 requests/hour for unauthenticated)
- Try again in a few minutes

### Update fails mid-way

If an update fails partway through:

1. Restore from your backup (this is why backups are essential!)
2. Or try running the update again
3. Or do a [manual update](#manual-updates)

Your content and configuration are in separate directories from core files, so they're less likely to be affected—but with any file operations, there's always some risk.

### After updating, site shows errors

1. Run `composer install` to update dependencies
2. Run `php bin/ava rebuild` to rebuild the content index
3. Check the changelog for breaking changes


### After updating, custom files are missing

1. If you modified core files (not recommended), your changes may have been overwritten. Restore from backup.
2. Custom themes, plugins and custom files in the public folder should be unaffected, but always double-check. Set-ups can be inconsistent across different sites and servers and the updater may not cover every edge case.
3. Restore from backup if necessary.

---

## Need Help?

Updates not working? Something broken? Join the [Discord community](https://discord.gg/Z7bF9YeK)—we're happy to help troubleshoot and get you back on track.
