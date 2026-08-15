<?php
/**
 * WP_List_Table for CLI logs.
 *
 * @package Maintenance_Tools_WCUS26
 * @since 1.0.0
 */

namespace Maintenance_Tools_WCUS26\Admin;

use Maintenance_Tools_WCUS26\Table\Logs_Table;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin list table for `cli_logs` rows.
 *
 * @since 1.0.0
 */
class Logs_List_Table extends Abstract_List_Table {

	/**
	 * {@inheritdoc}
	 */
	protected function get_table_class(): string {
		return Logs_Table::class;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function get_filter_label(): string {
		return __( 'All commands', 'maintenance-tools-wcus26' );
	}

	/**
	 * {@inheritdoc}
	 */
	protected function get_singular(): string {
		return 'log';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function get_plural(): string {
		return 'logs';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function get_default_orderby(): string {
		return 'id';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function get_default_order(): string {
		return 'ASC';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_columns(): array {
		return array(
			'id'           => __( 'ID', 'maintenance-tools-wcus26' ),
			'created_at'   => __( 'Created at', 'maintenance-tools-wcus26' ),
			'command_name' => __( 'Command', 'maintenance-tools-wcus26' ),
			'content'      => __( 'Content', 'maintenance-tools-wcus26' ),
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function no_items(): void {
		esc_html_e( 'No logs found.', 'maintenance-tools-wcus26' );
	}

	/**
	 * Render log content as a single escaped line.
	 *
	 * Tabs are shown as " | " so TSV output stays readable in the table.
	 *
	 * @since 1.0.0
	 *
	 * @param object $item Row object.
	 * @return string Escaped cell markup.
	 */
	protected function render_content_column( object $item ): string {
		return esc_html( str_replace( "\t", ' | ', (string) $item->content ) );
	}
}
