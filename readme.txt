=== Maintenance Tools WCUS26 ===
Contributors: lukaszwilczak
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WP-CLI maintenance tools with reusable command classes for bulk post operations.

== Description ==

Registers the `wp wcus26` command family. New commands are PHP classes dropped into `includes/cli/commands/`.

* `Abstract_Command` — skeleton for any WP-CLI command.
* `Abstract_Posts_Command` — iterates posts in batches (default 10) with `--post-type`, `--dry-run`, `--limit`, `--offset`, `--skip-all-hooks`, `--skip-hooks`, and WP_Query argument mapping (`--category_in`, `--tag_slug_in`, …).

== Installation ==

1. Activate the plugin through the Plugins screen, or with `wp plugin activate maintenance-tools-wcus26`.
2. Run `wp help wcus26` to list available commands.

== Changelog ==

= 1.0.0 =
* Initial release.
