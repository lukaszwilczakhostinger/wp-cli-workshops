<?php
/**
 * Abstract WP-CLI command skeleton.
 *
 * Extend this class for commands that do not iterate over posts.
 * For post loops, extend Abstract_Posts_Command instead.
 *
 * Example:
 *
 *     namespace Maintenance_Tools_WCUS26\CLI\Commands;
 *
 *     class Ping_Command extends \Maintenance_Tools_WCUS26\CLI\Abstract_Command {
 *         public static function get_name(): string {
 *             return 'ping';
 *         }
 *
 *         public static function get_shortdesc(): string {
 *             return 'Confirm that WCUS26 CLI commands are loaded.';
 *         }
 *
 *         protected function run( array $args, array $assoc_args ): void {
 *             $this->success( 'pong' );
 *         }
 *     }
 *
 * Drop the file in includes/cli/commands/class-ping-command.php and it will
 * be registered automatically as `wp wcus26 ping`.
 *
 * @since 1.0.0
 *
 * @package Maintenance_Tools_WCUS26
 */

namespace Maintenance_Tools_WCUS26\CLI;

use Maintenance_Tools_WCUS26\CLI\Helpers\Capturing_Logger;
use Maintenance_Tools_WCUS26\Table\Logs_Table;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base class for `wp wcus26` subcommands.
 *
 * @since 1.0.0
 */
abstract class Abstract_Command extends \WP_CLI_Command {

	/**
	 * Positional arguments passed to the command.
	 *
	 * @since 1.0.0
	 *
	 * @var array<int, string>
	 */
	protected array $args = array();

	/**
	 * Associative arguments passed to the command.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, mixed>
	 */
	protected array $assoc_args = array();

	/**
	 * Logger that was active before capturing started.
	 *
	 * @since 1.0.0
	 *
	 * @var object|null
	 */
	private ?object $previous_logger = null;

	/**
	 * WP-CLI subcommand name, e.g. "list-posts".
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	abstract public static function get_name(): string;

	/**
	 * Short description shown in `wp help`.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	abstract public static function get_shortdesc(): string;

	/**
	 * Command body. Implement this in each concrete command.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string>   $args       Positional arguments.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 * @return void
	 */
	abstract protected function run( array $args, array $assoc_args ): void;

	/**
	 * Parent command namespace.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function get_namespace(): string {
		return 'wcus26';
	}

	/**
	 * Full command string registered with WP-CLI.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function get_full_command(): string {
		return static::get_namespace() . ' ' . static::get_name();
	}

	/**
	 * Longer help text shown in `wp help <command>`.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function get_longdesc(): string {
		return '';
	}

	/**
	 * When the command should be registered.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	protected static function get_when(): string {
		return 'after_wp_load';
	}

	/**
	 * Register this command with WP-CLI.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function register(): void {
		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
			return;
		}

		$instance = new static();

		\WP_CLI::add_command(
			static::get_full_command(),
			static::class,
			array(
				'shortdesc' => static::get_shortdesc(),
				'longdesc'  => static::get_longdesc(),
				'synopsis'  => $instance->get_synopsis(),
				'when'      => static::get_when(),
			)
		);
	}

	/**
	 * WP-CLI entry point.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string>   $args       Positional arguments.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 * @return void
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		$this->args       = $args;
		$this->assoc_args = $assoc_args;

		if ( $this->should_save_logs() ) {
			$this->start_cli_capture();
		}

		try {
			$this->before_run();
			$this->run( $args, $assoc_args );
			$this->after_run();
		} finally {
			$this->stop_cli_capture();
		}
	}

	/**
	 * Full command synopsis.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array<string, mixed>>
	 */
	protected function get_synopsis(): array {
		return array_merge(
			$this->get_common_synopsis(),
			$this->get_base_synopsis(),
			$this->get_extra_synopsis()
		);
	}

	/**
	 * Flags available on every command.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array<string, mixed>>
	 */
	protected function get_common_synopsis(): array {
		return array(
			array(
				'type'        => 'flag',
				'name'        => 'save-logs',
				'description' => 'Save each output line to the logs table.',
				'optional'    => true,
			),
		);
	}

	/**
	 * Synopsis shared by this command family. Override in intermediate classes.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array<string, mixed>>
	 */
	protected function get_base_synopsis(): array {
		return array();
	}

	/**
	 * Extra synopsis entries for a concrete command.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array<string, mixed>>
	 */
	protected function get_extra_synopsis(): array {
		return array();
	}

	/**
	 * Hook executed before run().
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function before_run(): void {
	}

	/**
	 * Hook executed after run().
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function after_run(): void {
	}

	/**
	 * Read an associative argument or flag.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key     Argument name.
	 * @param mixed  $default Default value when the flag is missing.
	 * @return mixed Flag value, or the default.
	 */
	protected function get_flag( string $key, $default = null ) {
		return \WP_CLI\Utils\get_flag_value( $this->assoc_args, $key, $default );
	}

	/**
	 * Whether the command is running in dry-run mode.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	protected function is_dry_run(): bool {
		return (bool) $this->get_flag( 'dry-run', false );
	}

	/**
	 * Whether CLI output should be written to the logs table.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	protected function should_save_logs(): bool {
		return (bool) $this->get_flag( 'save-logs', false );
	}

	/**
	 * Print a log line.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message Message to print.
	 * @return void
	 */
	protected function log( string $message ): void {
		\WP_CLI::log( $message );
	}

	/**
	 * Print a raw line.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message Message to print.
	 * @return void
	 */
	protected function line( string $message = '' ): void {
		\WP_CLI::line( $message );
	}

	/**
	 * Print a success message.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message Success message.
	 * @return void
	 */
	protected function success( string $message ): void {
		\WP_CLI::success( $message );
	}

	/**
	 * Print a warning.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message Warning message.
	 * @return void
	 */
	protected function warning( string $message ): void {
		\WP_CLI::warning( $message );
	}

	/**
	 * Print an error and optionally halt.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message Error message.
	 * @param bool   $exit    Whether to halt execution.
	 * @return void
	 */
	protected function error( string $message, bool $exit = true ): void {
		\WP_CLI::error( $message, $exit );
	}

	/**
	 * Print a debug message (visible with --debug).
	 *
	 * @since 1.0.0
	 *
	 * @param string $message Debug message (visible with --debug).
	 * @param string $group   Debug group.
	 * @return void
	 */
	protected function debug( string $message, string $group = 'wcus26' ): void {
		\WP_CLI::debug( $message, $group );
	}

	/**
	 * Ask for confirmation before continuing.
	 *
	 * @since 1.0.0
	 *
	 * @param string $question Confirmation question.
	 * @return void
	 */
	protected function confirm( string $question ): void {
		\WP_CLI::confirm( $question, $this->assoc_args );
	}

	/**
	 * Stop log capture and halt WP-CLI.
	 *
	 * @since 1.0.0
	 *
	 * @param int $code Exit code.
	 * @return void
	 */
	protected function halt( int $code ): void {
		$this->stop_cli_capture();
		\WP_CLI::halt( $code );
	}

	/**
	 * Persist one visible CLI line as its own logs table row.
	 *
	 * @since 1.0.0
	 *
	 * @param string $line Output line without trailing newline.
	 * @return void
	 */
	public function append_cli_output( string $line ): void {
		foreach ( preg_split( '/\r\n|\r|\n/', $line ) as $single_line ) {
			if ( '' === $single_line ) {
				continue;
			}

			Logs_Table::add_log( static::get_full_command(), $single_line );
		}
	}

	/**
	 * Start recording visible WP-CLI output.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function start_cli_capture(): void {
		$this->previous_logger = null;

		if (
			! method_exists( '\WP_CLI', 'get_logger' )
			|| ! method_exists( '\WP_CLI', 'set_logger' )
		) {
			return;
		}

		$logger = \WP_CLI::get_logger();
		if ( ! is_object( $logger ) ) {
			return;
		}

		$this->previous_logger = $logger;

		\WP_CLI::set_logger(
			new Capturing_Logger(
				$this->previous_logger,
				array( $this, 'append_cli_output' )
			)
		);
	}

	/**
	 * Restore the original WP-CLI logger.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function stop_cli_capture(): void {
		if ( ! $this->previous_logger ) {
			return;
		}

		\WP_CLI::set_logger( $this->previous_logger );
		$this->previous_logger = null;
	}
}
