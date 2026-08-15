<?php
/**
 * Admin screens: Maintenance → Reports and Maintenance → Logs.
 *
 * @since 1.0.0
 *
 * @package Maintenance_Tools_WCUS26
 */

namespace Maintenance_Tools_WCUS26\Admin;

use Maintenance_Tools_WCUS26\Table\Logs_Table;
use Maintenance_Tools_WCUS26\Table\Reports_Table;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers admin menus and report/log actions.
 *
 * @since 1.0.0
 */
class Admin {

	/**
	 * Reports submenu slug.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public const PAGE_REPORTS = 'mt-wcus26-reports';

	/**
	 * Logs submenu slug.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public const PAGE_LOGS = 'mt-wcus26-logs';

	/**
	 * Hook admin actions.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_mt_wcus26_download_report', array( $this, 'download_report' ) );
		add_action( 'admin_post_mt_wcus26_remove_report', array( $this, 'remove_report' ) );
		add_action( 'admin_post_mt_wcus26_clear_logs', array( $this, 'clear_logs' ) );
	}

	/**
	 * Register the Maintenance menu and subpages.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_menu(): void {
		$capability = 'manage_options';

		add_menu_page(
			__( 'Maintenance', 'maintenance-tools-wcus26' ),
			__( 'Maintenance', 'maintenance-tools-wcus26' ),
			$capability,
			self::PAGE_REPORTS,
			array( $this, 'render_reports_page' ),
			'dashicons-hammer',
			80
		);

		add_submenu_page(
			self::PAGE_REPORTS,
			__( 'Reports', 'maintenance-tools-wcus26' ),
			__( 'Reports', 'maintenance-tools-wcus26' ),
			$capability,
			self::PAGE_REPORTS,
			array( $this, 'render_reports_page' )
		);

		add_submenu_page(
			self::PAGE_REPORTS,
			__( 'Logs', 'maintenance-tools-wcus26' ),
			__( 'Logs', 'maintenance-tools-wcus26' ),
			$capability,
			self::PAGE_LOGS,
			array( $this, 'render_logs_page' )
		);
	}

	/**
	 * Enqueue inline styles for plugin admin screens.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

		if ( ! in_array( $page, array( self::PAGE_REPORTS, self::PAGE_LOGS ), true ) ) {
			return;
		}

		$css  = '.mt-wcus26-log{';
		$css .= 'max-height:220px;overflow:auto;margin:0;padding:8px;';
		$css .= 'background:#f6f7f7;border:1px solid #dcdcde;';
		$css .= 'white-space:pre-wrap;word-break:break-word;';
		$css .= 'font-size:12px;line-height:1.45;}';

		if ( self::PAGE_LOGS === $page ) {
			$css .= '.mt-wcus26-logs .wp-list-table{table-layout:auto;}';
			$css .= '.mt-wcus26-logs .column-id,';
			$css .= '.mt-wcus26-logs .column-created_at,';
			$css .= '.mt-wcus26-logs .column-command_name{';
			$css .= 'width:1%;white-space:nowrap;text-align:left;}';
			$css .= '.mt-wcus26-logs .column-content{';
			$css .= 'width:99%;text-align:left;word-break:break-word;}';
			$css .= '.mt-wcus26-clear-logs{';
			$css .= 'display:inline-block;margin:0 0 0 8px;vertical-align:middle;}';
		}

		wp_add_inline_style( 'wp-admin', $css );
	}

	/**
	 * Render the Reports submenu page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function render_reports_page(): void {
		if ( isset( $_GET['report_removed'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>';
			echo esc_html__( 'Report removed.', 'maintenance-tools-wcus26' );
			echo '</p></div>';
		}

		$this->render_list_page(
			__( 'Reports', 'maintenance-tools-wcus26' ),
			self::PAGE_REPORTS,
			new Reports_List_Table()
		);
	}

	/**
	 * Render the Logs submenu page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function render_logs_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( isset( $_GET['logs_cleared'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>';
			echo esc_html__( 'Logs cleared.', 'maintenance-tools-wcus26' );
			echo '</p></div>';
		}

		$table = new Logs_List_Table();
		$table->prepare_items();

		$clear_url = admin_url( 'admin-post.php' );
		$confirm   = esc_js( __( 'Delete all log records?', 'maintenance-tools-wcus26' ) );

		echo '<div class="wrap mt-wcus26-logs">';
		echo '<h1 class="wp-heading-inline">';
		echo esc_html__( 'Logs', 'maintenance-tools-wcus26' );
		echo '</h1>';
		echo '<form class="mt-wcus26-clear-logs" method="post"';
		echo ' action="' . esc_url( $clear_url ) . '"';
		echo ' onsubmit="return confirm(\'' . $confirm . '\');">';
		echo '<input type="hidden" name="action" value="mt_wcus26_clear_logs" />';
		wp_nonce_field( 'mt_wcus26_clear_logs' );
		submit_button( __( 'Clear logs', 'maintenance-tools-wcus26' ), 'delete', 'submit', false );
		echo '</form>';
		echo '<hr class="wp-header-end" />';
		echo '<form method="get">';
		echo '<input type="hidden" name="page" value="' . esc_attr( self::PAGE_LOGS ) . '" />';
		$table->display();
		echo '</form>';
		echo '</div>';
	}

	/**
	 * Delete all rows from the logs table.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function clear_logs(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__(
					'Sorry, you are not allowed to clear logs.',
					'maintenance-tools-wcus26'
				)
			);
		}

		check_admin_referer( 'mt_wcus26_clear_logs' );
		Logs_Table::delete_all();

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'         => self::PAGE_LOGS,
					'logs_cleared' => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Stream a stored CSV report as a file download.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function download_report(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__(
					'Sorry, you are not allowed to download this report.',
					'maintenance-tools-wcus26'
				)
			);
		}

		$report_id = isset( $_GET['report_id'] ) ? absint( $_GET['report_id'] ) : 0;
		check_admin_referer( 'mt_wcus26_download_report_' . $report_id );

		$report = Reports_Table::get( $report_id );
		if ( ! $report ) {
			wp_die( esc_html__( 'Report not found.', 'maintenance-tools-wcus26' ) );
		}

		$filename = sanitize_file_name( $report->report_name . '-' . $report->id . '.csv' );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Raw CSV download.
		echo $report->content;
		exit;
	}

	/**
	 * Delete a single report row.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function remove_report(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__(
					'Sorry, you are not allowed to remove this report.',
					'maintenance-tools-wcus26'
				)
			);
		}

		$report_id = isset( $_GET['report_id'] ) ? absint( $_GET['report_id'] ) : 0;
		check_admin_referer( 'mt_wcus26_remove_report_' . $report_id );

		Reports_Table::delete( $report_id );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'           => self::PAGE_REPORTS,
					'report_removed' => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Shared wrap + list table markup.
	 *
	 * @since 1.0.0
	 *
	 * @param string              $title Page title.
	 * @param string              $page  Query page slug.
	 * @param Abstract_List_Table $table List table instance.
	 * @return void
	 */
	private function render_list_page(
		string $title,
		string $page,
		Abstract_List_Table $table
	): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$table->prepare_items();

		echo '<div class="wrap' . ( self::PAGE_LOGS === $page ? ' mt-wcus26-logs' : '' ) . '">';
		echo '<h1>' . esc_html( $title ) . '</h1>';
		echo '<form method="get">';
		echo '<input type="hidden" name="page" value="' . esc_attr( $page ) . '" />';
		$table->display();
		echo '</form>';
		echo '</div>';
	}
}
