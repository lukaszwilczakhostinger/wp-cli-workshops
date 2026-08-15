<?php
/**
 * Base helper for plugin custom tables.
 *
 * @package Maintenance_Tools_WCUS26
 * @since 1.0.0
 */

namespace Maintenance_Tools_WCUS26\Table;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared CRUD and listing helpers for plugin custom tables.
 *
 * @since 1.0.0
 */
abstract class Abstract_Table {

	/**
	 * Table name suffix after $wpdb->prefix.
	 *
	 * @since 1.0.0
	 *
	 * @return string Suffix without the WordPress table prefix.
	 */
	abstract public static function get_suffix(): string;

	/**
	 * CREATE TABLE body used by dbDelta (columns + keys, no CREATE wrapper).
	 *
	 * @since 1.0.0
	 *
	 * @return string Column and key definitions.
	 */
	abstract protected static function get_schema(): string;

	/**
	 * Column used by the admin filter dropdown.
	 *
	 * @since 1.0.0
	 *
	 * @return string Column name.
	 */
	abstract public static function get_filter_column(): string;

	/**
	 * Full table name with WordPress prefix.
	 *
	 * @since 1.0.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @return string Prefixed table name.
	 */
	public static function get_name(): string {
		global $wpdb;

		return $wpdb->prefix . static::get_suffix();
	}

	/**
	 * Create or upgrade the table.
	 *
	 * @since 1.0.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @return void
	 */
	public static function create_table(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$table           = static::get_name();
		$schema          = static::get_schema();

		$sql = "CREATE TABLE {$table} (
			{$schema}
		) {$charset_collate};";

		dbDelta( $sql );
	}

	/**
	 * Insert a row and return the new ID.
	 *
	 * @since 1.0.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param array<string, mixed> $data Column => value pairs.
	 * @return int Inserted row ID, or 0 on failure.
	 */
	public static function insert( array $data ): int {
		global $wpdb;

		if ( empty( $data['created_at'] ) ) {
			$data['created_at'] = current_time( 'mysql' );
		}

		$inserted = $wpdb->insert( static::get_name(), $data );

		if ( false === $inserted ) {
			return 0;
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Fetch a single row by ID.
	 *
	 * @since 1.0.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param int $id Row ID.
	 * @return object|null Row object, or null when not found.
	 */
	public static function get( int $id ): ?object {
		global $wpdb;

		$table = static::get_name();
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id )
		);

		return is_object( $row ) ? $row : null;
	}

	/**
	 * Paginated list of rows.
	 *
	 * @since 1.0.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param array<string, mixed> $args {
	 *     Query arguments.
	 *
	 *     @type int    $paged        Page number. Default 1.
	 *     @type int    $per_page     Rows per page. Default 20.
	 *     @type string $orderby      Sort column. Default created_at.
	 *     @type string $order        ASC or DESC. Default DESC.
	 *     @type string $filter_value Optional filter column value.
	 * }
	 * @return array{items: array<int, object>, total: int} Rows and total count.
	 */
	public static function query_items( array $args = array() ): array {
		global $wpdb;

		$args = wp_parse_args(
			$args,
			array(
				'paged'        => 1,
				'per_page'     => 20,
				'orderby'      => 'created_at',
				'order'        => 'DESC',
				'filter_value' => '',
			)
		);

		$table         = static::get_name();
		$filter_column = static::get_filter_column();
		$orderby       = static::sanitize_orderby( (string) $args['orderby'] );
		$order         = 'ASC' === strtoupper( (string) $args['order'] ) ? 'ASC' : 'DESC';
		$per_page      = max( 1, (int) $args['per_page'] );
		$paged         = max( 1, (int) $args['paged'] );
		$offset        = ( $paged - 1 ) * $per_page;
		$filter_value  = sanitize_text_field( (string) $args['filter_value'] );

		$where  = 'WHERE 1=1';
		$params = array();

		if ( '' !== $filter_value ) {
			$where   .= " AND `{$filter_column}` = %s";
			$params[] = $filter_value;
		}

		$count_sql = "SELECT COUNT(*) FROM {$table} {$where}";
		$list_sql  = "SELECT * FROM {$table} {$where}"
			. " ORDER BY `{$orderby}` {$order} LIMIT %d OFFSET %d";

		if ( $params ) {
			$total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) );
			$items = $wpdb->get_results(
				$wpdb->prepare(
					$list_sql,
					array_merge( $params, array( $per_page, $offset ) )
				)
			);
		} else {
			$total = (int) $wpdb->get_var( $count_sql );
			$items = $wpdb->get_results(
				$wpdb->prepare( $list_sql, $per_page, $offset )
			);
		}

		return array(
			'items' => is_array( $items ) ? $items : array(),
			'total' => $total,
		);
	}

	/**
	 * Distinct values for the filter dropdown.
	 *
	 * @since 1.0.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @return array<int, string> Unique non-empty filter column values.
	 */
	public static function get_filter_values(): array {
		global $wpdb;

		$table  = static::get_name();
		$column = static::get_filter_column();
		$values = $wpdb->get_col(
			"SELECT DISTINCT `{$column}` FROM {$table}"
			. " WHERE `{$column}` <> '' ORDER BY `{$column}` ASC"
		);

		return is_array( $values ) ? $values : array();
	}

	/**
	 * Restrict orderby to known columns.
	 *
	 * @since 1.0.0
	 *
	 * @param string $orderby Requested orderby column.
	 * @return string Safe column name.
	 */
	protected static function sanitize_orderby( string $orderby ): string {
		$allowed = array( 'id', 'created_at', static::get_filter_column() );

		return in_array( $orderby, $allowed, true ) ? $orderby : 'created_at';
	}
}
