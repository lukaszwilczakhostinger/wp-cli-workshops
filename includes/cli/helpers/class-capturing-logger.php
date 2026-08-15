<?php
/**
 * WP-CLI logger decorator that records visible output for the logs table.
 *
 * @package Maintenance_Tools_WCUS26
 * @since 1.0.0
 */

namespace Maintenance_Tools_WCUS26\CLI\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wraps the active WP-CLI logger and forwards each visible line to a callback.
 *
 * @since 1.0.0
 */
class Capturing_Logger {

	/**
	 * Original WP-CLI logger.
	 *
	 * @since 1.0.0
	 * @var object
	 */
	private object $inner;

	/**
	 * Callback that receives one visible line.
	 *
	 * @since 1.0.0
	 * @var callable
	 */
	private $capture;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param object   $inner   Existing logger.
	 * @param callable $capture Receiver for captured lines.
	 */
	public function __construct( object $inner, callable $capture ) {
		$this->inner   = $inner;
		$this->capture = $capture;
	}

	/**
	 * Capture and forward an info line.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message Message.
	 * @return mixed Inner logger return value.
	 */
	public function info( $message ) {
		$this->record( (string) $message );
		return $this->inner->info( $message );
	}

	/**
	 * Capture and forward a log line.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message Message.
	 * @return mixed Inner logger return value.
	 */
	public function log( $message ) {
		return $this->info( $message );
	}

	/**
	 * Capture and forward a success line.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message Message.
	 * @return mixed Inner logger return value.
	 */
	public function success( $message ) {
		$this->record( 'Success: ' . $message );
		return $this->inner->success( $message );
	}

	/**
	 * Capture and forward a warning line.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message Message.
	 * @return mixed Inner logger return value.
	 */
	public function warning( $message ) {
		$this->record( 'Warning: ' . $message );
		return $this->inner->warning( $message );
	}

	/**
	 * Capture and forward an error line.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message Message.
	 * @return mixed Inner logger return value.
	 */
	public function error( $message ) {
		$this->record( 'Error: ' . $message );
		return $this->inner->error( $message );
	}

	/**
	 * Forward anything else (debug, halt, color helpers, …) to the inner logger.
	 *
	 * @since 1.0.0
	 *
	 * @param string            $name Method name.
	 * @param array<int, mixed> $args Arguments.
	 * @return mixed Inner logger return value.
	 */
	public function __call( string $name, array $args ) {
		return $this->inner->{$name}( ...$args );
	}

	/**
	 * Send one captured line to the callback.
	 *
	 * @since 1.0.0
	 *
	 * @param string $line Captured line.
	 * @return void
	 */
	private function record( string $line ): void {
		( $this->capture )( $line );
	}
}
