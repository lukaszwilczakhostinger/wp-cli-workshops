<?php
/**
 * Creates a report row and appends CSV (or other) chunks while a command runs.
 *
 * Typical flow for a posts command: create the report before the loop,
 * then append a CSV chunk after each batch (not after every post).
 *
 * ## EXAMPLE
 *
 *     use Maintenance_Tools_WCUS26\Table\Report;
 *
 *     class Foo_Command extends Abstract_Posts_Command {
 *         private ?Report $report = null;
 *
 *         protected function before_run(): void {
 *             $this->report = Report::create( 'foo', "ID,title,url\n" );
 *         }
 *
 *         // After each batch of posts:
 *         $this->report->append(
 *             Report::to_csv(
 *                 array(
 *                     array( '12', 'Lisbon', 'https://example.com/lisbon' ),
 *                     array( '34', 'Rome', 'https://example.com/rome' ),
 *                 )
 *             )
 *         );
 *     }
 *
 * `to_csv()` does not write a header — pass the header once in `create()`.
 *
 * @package Maintenance_Tools_WCUS26
 * @since 1.0.0
 */

namespace Maintenance_Tools_WCUS26\Table;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Writer for a single reports-table row.
 *
 * @since 1.0.0
 */
class Report {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $id   Row ID in the reports table.
	 * @param string $name Report name stored in report_name.
	 */
	private function __construct(
		private int $id,
		private string $name
	) {
	}

	/**
	 * Insert a new report and return a writer for it.
	 *
	 * @since 1.0.0
	 *
	 * @param string $report_name Report identifier, e.g. list-posts.
	 * @param string $content     Optional initial content (CSV header + newline).
	 * @return self Report writer for the new row.
	 */
	public static function create( string $report_name, string $content = '' ): self {
		$id = Reports_Table::add_report( $report_name, $content );

		return new self( $id, $report_name );
	}

	/**
	 * Append text to this report's content field (SQL CONCAT).
	 *
	 * @since 1.0.0
	 *
	 * @param string $content Chunk to add, usually CSV rows.
	 * @return bool True on success, false on failure.
	 */
	public function append( string $content ): bool {
		if ( $this->id < 1 || '' === $content ) {
			return false;
		}

		return Reports_Table::append_content( $this->id, $content );
	}

	/**
	 * Build a CSV fragment from rows, without a header line.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, array<int, string>> $rows Data rows.
	 * @return string CSV fragment without a header line.
	 */
	public static function to_csv( array $rows ): string {
		if ( empty( $rows ) ) {
			return '';
		}

		$handle = fopen( 'php://temp', 'r+' );
		if ( false === $handle ) {
			return '';
		}

		foreach ( $rows as $row ) {
			fputcsv( $handle, $row );
		}

		rewind( $handle );
		$csv = stream_get_contents( $handle );
		fclose( $handle );

		return is_string( $csv ) ? $csv : '';
	}

	/**
	 * Report row ID.
	 *
	 * @since 1.0.0
	 *
	 * @return int Row ID.
	 */
	public function get_id(): int {
		return $this->id;
	}

	/**
	 * Stored report name.
	 *
	 * @since 1.0.0
	 *
	 * @return string Report name.
	 */
	public function get_name(): string {
		return $this->name;
	}
}
