<?php
/**
 * Temporarily disables WordPress actions and filters.
 *
 * Used around `process_post()` so WP_Query still runs with hooks intact.
 *
 * @since 1.0.0
 *
 * @package Maintenance_Tools_WCUS26
 */

namespace Maintenance_Tools_WCUS26\CLI\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Saves and restores `$wp_filter` for a post-processing window.
 *
 * @since 1.0.0
 */
class Hook_Skipper {

	/**
	 * Saved hook objects, keyed by hook name.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, mixed>
	 */
	private array $saved = array();

	/**
	 * Current skip mode: none, all, or named.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private string $mode = 'none';

	/**
	 * Disable hooks until stop() is called.
	 *
	 * @since 1.0.0
	 *
	 * @param bool               $skip_all   Whether to disable every hook.
	 * @param array<int, string> $hook_names Hook names to disable when $skip_all is false.
	 * @return void
	 */
	public function start( bool $skip_all, array $hook_names = array() ): void {
		$this->stop();

		global $wp_filter;

		if ( ! is_array( $wp_filter ) ) {
			return;
		}

		if ( $skip_all ) {
			$this->saved = $wp_filter;
			$this->mode  = 'all';
			$wp_filter   = array();
			return;
		}

		foreach ( $hook_names as $hook_name ) {
			if ( '' === $hook_name || ! isset( $wp_filter[ $hook_name ] ) ) {
				continue;
			}

			$this->saved[ $hook_name ] = $wp_filter[ $hook_name ];
			unset( $wp_filter[ $hook_name ] );
		}

		if ( ! empty( $this->saved ) ) {
			$this->mode = 'named';
		}
	}

	/**
	 * Restore hooks saved by start().
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function stop(): void {
		if ( 'none' === $this->mode ) {
			return;
		}

		global $wp_filter;

		if ( ! is_array( $wp_filter ) ) {
			$wp_filter = array();
		}

		if ( 'all' === $this->mode ) {
			$wp_filter = $this->saved;
		} else {
			foreach ( $this->saved as $hook_name => $hook ) {
				$wp_filter[ $hook_name ] = $hook;
			}
		}

		$this->saved = array();
		$this->mode  = 'none';
	}
}
