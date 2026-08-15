<?php
/**
 * Plugin Name:       Maintenance Tools WCUS26
 * Description:       WP-CLI maintenance tools with reusable command classes for bulk post operations.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Łukasz Wilczak
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       maintenance-tools-wcus26
 * Domain Path:       /languages
 *
 * @since 1.0.0
 *
 * @package Maintenance_Tools_WCUS26
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin version.
 *
 * @since 1.0.0
 */
define( 'MT_WCUS26_VERSION', '1.0.0' );

/**
 * Absolute path to the main plugin file.
 *
 * @since 1.0.0
 */
define( 'MT_WCUS26_FILE', __FILE__ );

/**
 * Absolute path to the plugin directory, with trailing slash.
 *
 * @since 1.0.0
 */
define( 'MT_WCUS26_DIR', plugin_dir_path( __FILE__ ) );

/**
 * URL to the plugin directory, with trailing slash.
 *
 * @since 1.0.0
 */
define( 'MT_WCUS26_URL', plugin_dir_url( __FILE__ ) );

require_once MT_WCUS26_DIR . 'includes/class-autoloader.php';

\Maintenance_Tools_WCUS26\Autoloader::register();

register_activation_hook(
	MT_WCUS26_FILE,
	array( \Maintenance_Tools_WCUS26\Table\Installer::class, 'install' )
);

add_action(
	'plugins_loaded',
	static function () {
		( new \Maintenance_Tools_WCUS26\Plugin() )->init();
	}
);
