<?php
/**
 * Discovers and registers WP-CLI commands.
 *
 * Any class in includes/cli/commands/ that extends Abstract_Command
 * is registered automatically.
 *
 * @package Maintenance_Tools_WCUS26
 * @since 1.0.0
 */

namespace Maintenance_Tools_WCUS26\CLI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers `wp wcus26` and every concrete command class.
 *
 * @since 1.0.0
 */
class Command_Loader {

	/**
	 * Register the parent command and every concrete command class.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function register(): void {
		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
			return;
		}

		\WP_CLI::add_command(
			'wcus26',
			Root_Command::class,
			array(
				'shortdesc' => __( 'Maintenance tools for WCUS26.', 'maintenance-tools-wcus26' ),
			)
		);

		$commands = self::discover_commands();

		/**
		 * Filters the list of WP-CLI command classes to register.
		 *
		 * @since 1.0.0
		 *
		 * @param array<int, class-string<Abstract_Command>> $commands Command class names.
		 */
		$commands = apply_filters( 'mt_wcus26_cli_commands', $commands );

		foreach ( $commands as $class ) {
			if (
				! class_exists( $class )
				|| ! is_subclass_of( $class, Abstract_Command::class )
			) {
				continue;
			}

			$reflection = new \ReflectionClass( $class );
			if ( $reflection->isAbstract() ) {
				continue;
			}

			$class::register();
		}
	}

	/**
	 * Find command classes in includes/cli/commands/.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, class-string<Abstract_Command>> Fully qualified class names.
	 */
	private static function discover_commands(): array {
		$directory = MT_WCUS26_DIR . 'includes/cli/commands';
		$files     = glob( $directory . '/class-*-command.php' );

		if ( ! $files ) {
			return array();
		}

		$commands = array();

		foreach ( $files as $file ) {
			$class = self::class_from_file( $file );

			if ( $class ) {
				$commands[] = $class;
			}
		}

		return $commands;
	}

	/**
	 * Convert a command file path to its fully qualified class name.
	 *
	 * @since 1.0.0
	 *
	 * @param string $file Absolute path to a command file.
	 * @return class-string<Abstract_Command>|null Class name, or null when the filename is invalid.
	 */
	private static function class_from_file( string $file ): ?string {
		$base = basename( $file, '.php' );

		if (
			! str_starts_with( $base, 'class-' )
			|| ! str_ends_with( $base, '-command' )
		) {
			return null;
		}

		$base  = preg_replace( '/^class-/', '', $base );
		$class = str_replace( ' ', '_', ucwords( str_replace( '-', ' ', $base ) ) );

		return 'Maintenance_Tools_WCUS26\\CLI\\Commands\\' . $class;
	}
}
