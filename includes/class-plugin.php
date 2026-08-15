<?php
/**
 * Main plugin bootstrap.
 *
 * @package Maintenance_Tools_WCUS26
 * @since 1.0.0
 */

namespace Maintenance_Tools_WCUS26;

use Maintenance_Tools_WCUS26\Admin\Admin;
use Maintenance_Tools_WCUS26\CLI\Command_Loader;
use Maintenance_Tools_WCUS26\Table\Installer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Boots tables, translations, admin screens, and WP-CLI commands.
 *
 * @since 1.0.0
 */
class Plugin {

	/**
	 * Boot the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init(): void {
		Installer::maybe_install();

		load_plugin_textdomain(
			'maintenance-tools-wcus26',
			false,
			dirname( plugin_basename( MT_WCUS26_FILE ) ) . '/languages'
		);

		if ( is_admin() ) {
			( new Admin() )->init();
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			Command_Loader::register();
		}
	}
}
