# Updates

Keeping Ava up to date is easy. We release updates regularly with new features and bug fixes.

## How to Update

The easiest way is using the CLI:

```bash
# 1. Check for updates
./ava update:check

# 2. Apply the update
./ava update:apply
```

Ava will download the latest version from GitHub and update your core files.

## What Gets Updated?

Don't worry, the update process is safe. It **only** updates the core system files.

**It will NEVER touch:**
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
3. Run `php bin/ava rebuild` to rebuild the cache
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
2. Run `php bin/ava rebuild` to clear caches
3. Check the changelog for breaking changes
