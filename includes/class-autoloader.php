<?php
/**
 * PSR-4 style autoloader for plugin classes.
 *
 * Maps Maintenance_Tools_WCUS26\Foo_Bar to includes/class-foo-bar.php,
 * including sub-namespaces as lowercase directories.
 *
 * @package Maintenance_Tools_WCUS26
 * @since 1.0.0
 */

namespace Maintenance_Tools_WCUS26;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads plugin classes from the includes directory.
 *
 * @since 1.0.0
 */
class Autoloader {

	/**
	 * Namespace prefix owned by this plugin.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private const PREFIX = 'Maintenance_Tools_WCUS26\\';

	/**
	 * Register the autoloader.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function register(): void {
		spl_autoload_register( array( self::class, 'load' ) );
	}

	/**
	 * Load a class file if it belongs to this plugin.
	 *
	 * @since 1.0.0
	 *
	 * @param string $class Fully qualified class name.
	 * @return void
	 */
	public static function load( string $class ): void {
		if ( ! str_starts_with( $class, self::PREFIX ) ) {
			return;
		}

		$relative = substr( $class, strlen( self::PREFIX ) );
		$parts    = explode( '\\', $relative );
		$class    = array_pop( $parts );

		$subpath = '';
		if ( ! empty( $parts ) ) {
			$subpath = strtolower( implode( DIRECTORY_SEPARATOR, $parts ) ) . DIRECTORY_SEPARATOR;
		}

		$filename = 'class-' . str_replace( '_', '-', strtolower( $class ) ) . '.php';
		$path     = MT_WCUS26_DIR . 'includes/' . $subpath . $filename;

		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}
}
