# Releasing Ava

This guide is for maintainers who are releasing a new version of Ava.

## Versioning

We use date-based versioning: `YY.MM.Patch`.
- `25.12.1` = First release of December 2025.

## How to Release

1. **Update Version:** Change `AVA_VERSION` in `bootstrap.php`.
2. **Test:** Run `./ava lint` and `./ava rebuild` to make sure everything is solid.
3. **Tag:** Create a git tag (e.g., `v25.12.1`).
4. **Push:** Push the tag to GitHub.
5. **Release:** Create a new Release on GitHub using that tag.

That's it! The update system will see the new tag and offer it to users.

- 🐛 Fixed issue description

## Breaking Changes

- ⚠️ Description of breaking change and migration steps

## New Bundled Plugins

- `plugin-name` — Brief description (not activated by default)
```

### Emoji Reference

| Emoji | Use for |
|-------|---------|
| ✨ | New features |
| 🔧 | Improvements/enhancements |
| 🐛 | Bug fixes |
| ⚠️ | Breaking changes |
| 📚 | Documentation |
| 🚀 | Performance |
| 🔒 | Security |

## What's Included in Releases

GitHub's zipball automatically includes everything in the repo. The updater only applies specific directories (see `core/Updater.php`).

**Updated by updater:**
- `core/`
- `docs/`
- `bin/`
- `themes/default/`
- `plugins/{bundled}/`
- `bootstrap.php`
- `composer.json`
- `public/index.php`
- `public/assets/admin.css`

**Never included/updated:**
- `content/` (should be in `.gitignore` anyway for demo content)
- `app/config/users.php` (gitignored)
- `storage/` (gitignored except structure)
- `vendor/` (gitignored)
- `.env` (gitignored)

## Testing the Update Flow

Before releasing, test the update mechanism:

1. Create a test installation
2. Set it to an older version in `bootstrap.php`
3. Create a test release on GitHub
4. Run `php bin/ava update:check`
5. Run `php bin/ava update:apply`
6. Verify files were updated correctly
7. Verify user files were preserved

## Hotfix Releases

For urgent fixes within the same month:

1. Just increment MICRO: `25.12.1` → `25.12.2`
2. Follow the normal release process
3. Note in changelog that it's a hotfix

## Pre-release / Beta

Not officially supported, but you could use:
- `25.12.1-beta.1`
- `25.12.1-rc.1`

The version comparison should still work, but these would be considered "less than" the final release.

## Repository Settings

Ensure the GitHub repository has:

- **Releases** enabled
- **Public** visibility (for API access without auth)
- Tags following the `v{VERSION}` format

## Troubleshooting

### Users can't fetch updates

- Ensure releases are published (not draft)
- Ensure repository is public
- Check GitHub API status

### Version comparison issues

The updater uses PHP's `version_compare()`. CalVer format works correctly:
- `25.12.1` < `25.12.2` ✓
- `25.12.9` < `25.12.10` ✓
- `25.12.99` < `26.01.1` ✓
