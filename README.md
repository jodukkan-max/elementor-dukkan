# Elementor Dukkan

A privately maintained build of [Elementor](https://elementor.com) 4.1.1 with the upsell,
telemetry and remote-API surface removed.

**Not affiliated with, endorsed by, or supported by Elementor Ltd.** This is a modified
version redistributed under the GPLv3, as that licence permits. All original work is by
Elementor.com.

## What this is

The plugin directory is still `elementor/`, and every PHP namespace, the `ELEMENTOR_*`
constants and the `elementor` text domain are untouched. Third-party addons that check
`did_action( 'elementor/loaded' )`, `class_exists( '\Elementor\Plugin' )` or
`version_compare( ELEMENTOR_VERSION, ... )` behave exactly as they do on stock Elementor.

Only the plugin's display name, its update source, and the features listed below differ.

## Updates

Updates come from this repository's GitHub releases, not from wordpress.org.

This matters because the plugin directory name `elementor` collides with the wordpress.org
plugin of the same name. Without intervention WordPress would offer stock Elementor as an
update and overwrite every change listed below. The `Update URI` header in `elementor.php`
tells WordPress to ignore the wordpress.org API for this plugin, and
`includes/dukkan-updater.php` supplies releases from here instead.

Two further paths that could have reinstalled stock Elementor are disabled: the beta
tester channel and the Tools > Version Control rollback.

### Cutting a release

1. Bump `Version:` in the `elementor.php` header **and** the `ELEMENTOR_DUKKAN_VERSION`
   constant just below it. They must match.
2. Commit.
3. `git tag v1.0.1 && git push origin v1.0.1`

The release workflow verifies the tag matches the header, builds `elementor.zip` with
`elementor/` as its single top-level directory, checks that layout, and attaches it to the
release. Sites pick the update up within 12 hours, or immediately via
**Dashboard > Updates > Check Again**.

### Versioning

Two version numbers exist deliberately:

| Constant | Value | Purpose |
|---|---|---|
| `Version:` header / `ELEMENTOR_DUKKAN_VERSION` | `1.0.0` | This fork's release number. The only thing compared against release tags. |
| `ELEMENTOR_VERSION` | `4.1.1` | Frozen at the upstream base, so addon compatibility checks keep their answers and no database migration ever fires. |

So wp-admin reports 1.0.0 while Elementor's System Info reports 4.1.1. That is expected.

## Installing

Download `elementor.zip` from the [latest release](../../releases/latest) and upload it via
**Plugins > Add New > Upload Plugin**. If a stock Elementor is already installed, this
replaces it in place; your content, templates and kits are stored in the database and are
not affected.

## What was changed

| # | Change |
|---|--------|
| 1 | Pro widget marketing list removed |
| 2 | Go Pro box, sticky upgrade bar and Globals upsell box removed |
| 3 | Promotions module and its teaser admin menus removed |
| 4 | Fake locked Advanced-tab controls removed |
| 5 | Editor upgrade notice bar removed |
| 6 | Ally Accessibility integration widget removed |
| 7 | "Grid" item removed from the editor Layout panel |
| 8 | Usage tracking forced off |
| 9 | 16 unused modules dropped from the registry |
| 10 | Remote info API disabled |
| 11 | All Elementor admin notices disabled |
| 12 | WP Dashboard "Elementor Overview" widget removed |
| 13 | 278 dead asset files deleted, 39.7 MB |
| 14 | 403 more files deleted, 11.7 MB |
| 15 | Seven remaining remote endpoints disabled |
| 16 | Rebranded, GitHub releases as the update source, wordpress.org overwrite blocked |

Detailed notes, including the reasoning and the verification done for each change, live in
`ELEMENTOR-PATCH-NOTES.md` outside this repository.

### What you lose

The remote template catalog, the Kit Library, Elementor account connection, cloud templates
and cloud kits, the notification centre, the Home screen, checklist thumbnails, version
rollback and the beta channel.

### What still works

The editor, the frontend, your own saved templates, template JSON import and export, and
every third-party addon.

## Requirements

- WordPress 6.6+
- PHP 7.4+

## Licence

GPLv3, inherited from Elementor. See [LICENSE](LICENSE).
