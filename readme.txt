=== Elementor Dukkan ===

Contributors: jodukkan-max
Requires at least: 6.6
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

A privately maintained build of Elementor with the upsell, telemetry and remote-API
surface removed.

== Description ==

This plugin is NOT distributed through wordpress.org and is NOT affiliated with,
endorsed by, or supported by Elementor Ltd. It is a modified version of Elementor
4.1.1, redistributed under the GPLv3 as that licence permits. All original work is
by Elementor.com.

Updates are served from GitHub releases at:
https://github.com/jodukkan-max/elementor-dukkan

The `Update URI` header in elementor.php prevents the wordpress.org API from
answering update checks for this plugin, which would otherwise replace it with
stock Elementor.

See README.md in the repository for the full list of modifications.

== Changelog ==

= 1.0.0 =
Initial release. Based on Elementor 4.1.1 with 16 sets of modifications: upsell
removal, telemetry disabled, remote APIs closed, roughly 51 MB of dead files
deleted, and GitHub releases wired up as the update source.
