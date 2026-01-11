# Releasing Ava

This guide is for maintainers who are releasing a new version of Ava.

## Versioning

We use [Semantic Versioning](https://semver.org/): `MAJOR.MINOR.PATCH`
- `1.0.0` = Initial stable release
- `1.0.1` = Patch release with bug fixes
- `1.1.0` = Minor release with new features
- `2.0.0` = Major release with breaking changes

## How to Release

1. **Update Version:** Change `AVA_VERSION` in `bootstrap.php`.
2. **Test:** Run `./ava lint` and `./ava rebuild` to make sure everything is solid.
3. **Release Tests:** Run `./ava test --release` to verify release readiness.
4. **Tag:** Create a git tag (e.g., `v1.0.0`).
5. **Push:** Push the tag to GitHub.
6. **Release:** Create a new Release on GitHub using that tag.

That's it! The update system will see the new tag and offer it to users.

## Release Tests

The `--release` flag runs additional tests that verify the project is ready for public release:

```bash
./ava test --release
```

**What it checks:**

| Category | Checks |
|----------|--------|
| Security | users.php gitignored, .env gitignored, storage/cache gitignored |
| Config defaults | debug disabled, admin disabled, theme = "default" |
| Admin settings | path = "/admin", theme = "cyan" |
| CLI settings | theme = "cyan" |
| Site identity | name = "My Ava Site", base_url contains "localhost", timezone = "UTC", locale = "en_GB" |
| Version | SemVer format, version higher than current GitHub release |
| Structure | default theme exists, example content exists, no users.php file |
| Documentation | README.md, LICENSE, docs/ exist |
| Dependencies | composer.json valid, vendor/ exists |

These tests live in `tests/Release/` and are skipped during normal test runs. They help ensure you haven't accidentally left development settings in place.

## Changelog Format

Include a changelog in the release notes following this format:

```markdown
## What's New

- ✨ New feature description
- 🔧 Improvement description
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
- `plugins/{bundled}/` (sitemap, feed, redirects)
- `bootstrap.php`
- `composer.json`
- `public/index.php`
- `public/assets/admin.css`

**Never updated:**
- `content/` — User content
- `themes/` — All themes, including default (user may have customised)
- `app/config/` — User configuration
- `app/config/users.php` — Admin users (gitignored)
- `storage/` — Cache and logs (gitignored except structure)
- `vendor/` — Dependencies (gitignored)
- `.env` — Environment config (gitignored)

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

For urgent bug fixes:

1. Increment PATCH: `1.0.0` → `1.0.1`
2. Follow the normal release process
3. Note in changelog that it's a hotfix

## Pre-release / Beta

Not officially supported, but you could use:
- `1.0.0-beta.1`
- `1.0.0-rc.1`

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

The updater uses PHP's `version_compare()`. SemVer format works correctly:
- `1.0.0` < `1.0.1` ✓
- `1.0.9` < `1.0.10` ✓
- `1.9.9` < `2.0.0` ✓
