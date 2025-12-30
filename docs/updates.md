# Updates

Keeping Ava up to date is easy. We release updates regularly with new features and bug fixes.

!> **Always ensure you have a good backup before attempting to update.** Ava is in fairly early development and while we hope the updater will continue to seamlessly carry you through future versions, breaking changes may occur. See [Backup Strategies](#backup-strategies) below.

## How to Update

The easiest way is using the [CLI](https://ava-dev.addy.zone/docs/#/cli?id=updates):

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

### The 3-2-1 Rule

For your website data, we recommend following the industry-standard [**3-2-1 Backup Rule**](https://www.backblaze.com/blog/the-3-2-1-backup-strategy/):

- **3 Copies of Data:** Keep your original live site plus at least two backups.
- **2 Different Media:** Store them on different types of storage (e.g., cloud storage and your local computer).
- **1 Off-Site:** Keep at least one copy in a different physical location (e.g., GitHub, S3, or Dropbox) to protect against server failure, computer data loss or data center issues.

Here are three recommended strategies, from simplest to most robust.

### 1. The "Zip It Up" (Simple)

The quickest way to backup your site is to create a zip archive of the entire folder. Many web hosting control panels let you create backup zips, or you can use the command line like so:

```bash
# Create a backup with today's date
zip -r backup-$(date +%Y-%m-%d).zip .
```

!> Ensure you download the zip, verify it is complete, and store in a safe place off your server.

**Pros:** Fast, easy, captures everything.
**Cons:** Generated on the same server (not safe if the server fails). Requires manual effort.

### 2. Git Repository (Highly Recommended)

Since your content is just text files, Git is the perfect backup tool. It gives you a complete history of every change you've ever made.

1. Initialize a repository: `git init`
2. Add your files: `git add .`
3. Commit: `git commit -m "Backup before update"`
4. **Crucial:** Push to a remote provider like GitHub, GitLab, or Bitbucket.

```bash
git push origin main
```

**Pros:** Version history, off-site storage, easy to collaborate.
**Cons:** Requires basic Git knowledge.

### 3. Automated Off-Site Backups (Robust)

For production sites, you should automate backups to a separate storage service (like AWS S3, DigitalOcean Spaces, or Dropbox).

You can use a simple script with `rclone` or `rsync` to copy your `content/` and `app/` folders nightly.

```bash
# Example: Sync content to an S3 bucket
aws s3 sync ./content s3://my-ava-backups/content
```

**Pros:** Set and forget, protects against server failure.
**Cons:** Requires setup and potentially small storage costs.

## What Gets Updated?

The updater attempts to **only** updates the core system files.

**It should avoid:**
- Your `content/` folder.
- Your `app/config/` settings.
- Your custom themes or plugins.

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

Your content and configuration are safe. The update only modifies core files. You can:

1. Try running the update again
2. Manually download and extract the release
3. Check file permissions on the `core/` directory

### After updating, site shows errors

1. Run `composer install` to update dependencies
2. Run `php bin/ava rebuild` to rebuild the content index
3. Check the changelog for breaking changes
