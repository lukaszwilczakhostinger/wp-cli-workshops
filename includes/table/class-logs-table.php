<?php
/**
 * Custom table for WP-CLI command output logs.
 *
 * Physical name: {$wpdb->prefix}cli_logs (e.g. wp_cli_logs).
 *
 * @package Maintenance_Tools_WCUS26
 * @since 1.0.0
 */

namespace Maintenance_Tools_WCUS26\Table;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Storage for one CLI output line per row.
 *
 * @since 1.0.0
 */
class Logs_Table extends Abstract_Table {

	/**
	 * {@inheritdoc}
	 */
	public static function get_suffix(): string {
		return 'cli_logs';
	}

	/**
	 * {@inheritdoc}
	 */
	public static function get_filter_column(): string {
		return 'command_name';
	}

	/**
	 * {@inheritdoc}
	 */
	protected static function get_schema(): string {
		return '
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			created_at datetime NOT NULL,
			command_name varchar(191) NOT NULL,
			content varchar(1000) NOT NULL,
			PRIMARY KEY  (id),
			KEY command_name (command_name),
			KEY created_at (created_at)
		';
	}

	/**
	 * Store captured command output.
	 *
	 * @since 1.0.0
	 *
	 * @param string $command_name Full command, e.g. wcus26 list-posts.
	 * @param string $content      Single CLI output line.
	 * @return int Inserted row ID, or 0 on failure.
	 */
	public static function add_log( string $command_name, string $content ): int {
		return static::insert(
			array(
				'command_name' => $command_name,
				'content'      => substr( $content, 0, 1000 ),
			)
		);
	}

	/**
	 * Delete all log rows.
	 *
	 * @since 1.0.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @return void
	 */
	public static function delete_all(): void {
		global $wpdb;

		$wpdb->query( 'DELETE FROM ' . static::get_name() );
	}
}
