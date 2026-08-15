<?php
/**
 * WP_List_Table for CLI reports.
 *
 * @package Maintenance_Tools_WCUS26
 * @since 1.0.0
 */

namespace Maintenance_Tools_WCUS26\Admin;

use Maintenance_Tools_WCUS26\Table\Reports_Table;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin list table for `cli_reports` rows.
 *
 * @since 1.0.0
 */
class Reports_List_Table extends Abstract_List_Table {

	/**
	 * {@inheritdoc}
	 */
	protected function get_table_class(): string {
		return Reports_Table::class;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function get_filter_label(): string {
		return __( 'All report names', 'maintenance-tools-wcus26' );
	}

	/**
	 * {@inheritdoc}
	 */
	protected function get_singular(): string {
		return 'report';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function get_plural(): string {
		return 'reports';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_columns(): array {
		return array(
			'id'          => __( 'ID', 'maintenance-tools-wcus26' ),
			'created_at'  => __( 'Created at', 'maintenance-tools-wcus26' ),
			'report_name' => __( 'Report name', 'maintenance-tools-wcus26' ),
			'content'     => __( 'Content', 'maintenance-tools-wcus26' ),
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function no_items(): void {
		esc_html_e( 'No reports found.', 'maintenance-tools-wcus26' );
	}

	/**
	 * Render download and remove actions instead of raw CSV.
	 *
	 * @since 1.0.0
	 *
	 * @param object $item Row object.
	 * @return string HTML for the content column.
	 */
	protected function render_content_column( object $item ): string {
		$id = (int) $item->id;

		$download_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=mt_wcus26_download_report&report_id=' . $id ),
			'mt_wcus26_download_report_' . $id
		);

		$remove_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=mt_wcus26_remove_report&report_id=' . $id ),
			'mt_wcus26_remove_report_' . $id
		);

		return sprintf(
			'<a class="button button-small" href="%1$s">%2$s</a> '
			. '<a class="button button-small" href="%3$s" '
			. 'onclick="return confirm(\'%4$s\');">%5$s</a>',
			esc_url( $download_url ),
			esc_html__( 'Download', 'maintenance-tools-wcus26' ),
			esc_url( $remove_url ),
			esc_js( __( 'Remove this report?', 'maintenance-tools-wcus26' ) ),
			esc_html__( 'Remove report', 'maintenance-tools-wcus26' )
		);
	}
}
