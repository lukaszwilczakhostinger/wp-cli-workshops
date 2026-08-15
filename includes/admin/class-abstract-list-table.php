<?php
/**
 * Shared WP_List_Table for plugin custom tables.
 *
 * @package Maintenance_Tools_WCUS26
 * @since 1.0.0
 */

namespace Maintenance_Tools_WCUS26\Admin;

use Maintenance_Tools_WCUS26\Table\Abstract_Table;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Shared WP_List_Table for plugin custom tables.
 *
 * @since 1.0.0
 */
abstract class Abstract_List_Table extends \WP_List_Table {

	/**
	 * Fully qualified table class used by this list.
	 *
	 * @since 1.0.0
	 *
	 * @return class-string<Abstract_Table> Table class name.
	 */
	abstract protected function get_table_class(): string;

	/**
	 * Label for the filter dropdown.
	 *
	 * @since 1.0.0
	 *
	 * @return string Filter dropdown label.
	 */
	abstract protected function get_filter_label(): string;

	/**
	 * Singular item name.
	 *
	 * @since 1.0.0
	 *
	 * @return string Singular item name.
	 */
	abstract protected function get_singular(): string;

	/**
	 * Plural item name.
	 *
	 * @since 1.0.0
	 *
	 * @return string Plural item name.
	 */
	abstract protected function get_plural(): string;

	/**
	 * Default orderby column when the request has none.
	 *
	 * @since 1.0.0
	 *
	 * @return string Column key.
	 */
	protected function get_default_orderby(): string {
		return 'created_at';
	}

	/**
	 * Default order when the request has none.
	 *
	 * @since 1.0.0
	 *
	 * @return string `ASC` or `DESC`.
	 */
	protected function get_default_order(): string {
		return 'DESC';
	}

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $args Unused parent constructor arguments.
	 */
	public function __construct( $args = array() ) {
		parent::__construct(
			array(
				'singular' => $this->get_singular(),
				'plural'   => $this->get_plural(),
				'ajax'     => false,
			)
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function prepare_items(): void {
		$table_class = $this->get_table_class();
		$per_page    = 100;
		$paged       = isset( $_REQUEST['paged'] )
			? max( 1, (int) $_REQUEST['paged'] )
			: 1;
		$orderby     = isset( $_REQUEST['orderby'] )
			? sanitize_key( wp_unslash( $_REQUEST['orderby'] ) )
			: $this->get_default_orderby();
		$order       = isset( $_REQUEST['order'] )
			? sanitize_text_field( wp_unslash( $_REQUEST['order'] ) )
			: $this->get_default_order();

		$result = $table_class::query_items(
			array(
				'paged'        => $paged,
				'per_page'     => $per_page,
				'orderby'      => $orderby,
				'order'        => $order,
				'filter_value' => $this->get_current_filter(),
			)
		);

		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			$this->get_sortable_columns(),
		);
		$this->items           = $result['items'];

		$this->set_pagination_args(
			array(
				'total_items' => $result['total'],
				'per_page'    => $per_page,
			)
		);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function get_sortable_columns(): array {
		$table_class = $this->get_table_class();

		$filter_column = $table_class::get_filter_column();

		return array(
			'id'           => array( 'id', false ),
			'created_at'   => array( 'created_at', true ),
			$filter_column => array( $filter_column, false ),
		);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since 1.0.0
	 *
	 * @param object $item        Row object.
	 * @param string $column_name Column key.
	 * @return string Escaped cell contents.
	 */
	protected function column_default( $item, $column_name ) {
		if ( 'content' === $column_name ) {
			return $this->render_content_column( $item );
		}

		if ( ! isset( $item->{$column_name} ) ) {
			return '';
		}

		return esc_html( (string) $item->{$column_name} );
	}

	/**
	 * Default content column markup.
	 *
	 * @since 1.0.0
	 *
	 * @param object $item Row object.
	 * @return string Escaped HTML for the content cell.
	 */
	protected function render_content_column( object $item ): string {
		return '<pre class="mt-wcus26-log">' . esc_html( (string) $item->content ) . '</pre>';
	}

	/**
	 * Output the filter dropdown above the table.
	 *
	 * @since 1.0.0
	 *
	 * @param string $which Location of the extra table nav markup: 'top' or 'bottom'.
	 * @return void
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}

		$table_class   = $this->get_table_class();
		$filter_column = $table_class::get_filter_column();
		$current       = $this->get_current_filter();
		$values        = $table_class::get_filter_values();

		echo '<div class="alignleft actions">';
		echo '<label class="screen-reader-text" for="mt-wcus26-filter">';
		echo esc_html( $this->get_filter_label() );
		echo '</label>';
		echo '<select name="' . esc_attr( $filter_column ) . '" id="mt-wcus26-filter">';
		echo '<option value="">' . esc_html( $this->get_filter_label() ) . '</option>';

		foreach ( $values as $value ) {
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $value ),
				selected( $current, $value, false ),
				esc_html( $value )
			);
		}

		echo '</select>';
		submit_button( __( 'Filter', 'maintenance-tools-wcus26' ), '', 'filter_action', false );
		echo '</div>';
	}

	/**
	 * Currently selected filter value.
	 *
	 * @since 1.0.0
	 *
	 * @return string Sanitized filter value, or an empty string.
	 */
	protected function get_current_filter(): string {
		$table_class   = $this->get_table_class();
		$filter_column = $table_class::get_filter_column();

		if ( ! isset( $_REQUEST[ $filter_column ] ) ) {
			return '';
		}

		return sanitize_text_field( wp_unslash( $_REQUEST[ $filter_column ] ) );
	}
}
