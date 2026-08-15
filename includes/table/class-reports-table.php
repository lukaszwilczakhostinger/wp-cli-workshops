<?php
/**
 * Custom table for generated WP-CLI reports (CSV stored in content).
 *
 * Physical name: {$wpdb->prefix}cli_reports (e.g. wp_cli_reports).
 *
 * @package Maintenance_Tools_WCUS26
 * @since 1.0.0
 */

namespace Maintenance_Tools_WCUS26\Table;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Storage for generated CSV reports.
 *
 * @since 1.0.0
 */
class Reports_Table extends Abstract_Table {

	/**
	 * {@inheritdoc}
	 */
	public static function get_suffix(): string {
		return 'cli_reports';
	}

	/**
	 * {@inheritdoc}
	 */
	public static function get_filter_column(): string {
		return 'report_name';
	}

	/**
	 * {@inheritdoc}
	 */
	protected static function get_schema(): string {
		return '
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			created_at datetime NOT NULL,
			report_name varchar(191) NOT NULL,
			content longtext NOT NULL,
			PRIMARY KEY  (id),
			KEY report_name (report_name),
			KEY created_at (created_at)
		';
	}

	/**
	 * Store a CSV report.
	 *
	 * @since 1.0.0
	 *
	 * @param string $report_name Report identifier, e.g. list-posts.
	 * @param string $csv         CSV contents.
	 * @return int Inserted row ID, or 0 on failure.
	 */
	public static function add_report( string $report_name, string $csv = '' ): int {
		return static::insert(
			array(
				'report_name' => $report_name,
				'content'     => $csv,
			)
		);
	}

	/**
	 * Concatenate a chunk onto an existing report's content.
	 *
	 * @since 1.0.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param int    $id    Report ID.
	 * @param string $chunk Text to append (usually CSV rows).
	 * @return bool True on success, false on failure.
	 */
	public static function append_content( int $id, string $chunk ): bool {
		global $wpdb;

		if ( $id < 1 || '' === $chunk ) {
			return false;
		}

		$table = static::get_name();
		$sql   = $wpdb->prepare(
			"UPDATE {$table} SET content = CONCAT(content, %s) WHERE id = %d",
			$chunk,
			$id
		);

		return false !== $wpdb->query( $sql );
	}

	/**
	 * Delete a single report.
	 *
	 * @since 1.0.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param int $id Report ID.
	 * @return bool True when a row was deleted.
	 */
	public static function delete( int $id ): bool {
		global $wpdb;

		if ( $id < 1 ) {
			return false;
		}

		$deleted = $wpdb->delete( static::get_name(), array( 'id' => $id ), array( '%d' ) );

		return false !== $deleted && $deleted > 0;
	}
}
