<?php
/**
 * Save per-post content structure stats as a CSV report.
 *
 * Appends one CSV chunk per batch, not per post.
 *
 * ## EXAMPLES
 *
 *     wp wcus26 structure-stats-report
 *     wp wcus26 structure-stats-report --post-status=any --batch-size=10
 *
 * @since 1.0.0
 *
 * @package Maintenance_Tools_WCUS26
 */

namespace Maintenance_Tools_WCUS26\CLI\Commands;

use Maintenance_Tools_WCUS26\CLI\Abstract_Structure_Stats_Command;
use Maintenance_Tools_WCUS26\Table\Report;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP-CLI command: save per-post structure stats as a CSV report.
 *
 * @since 1.0.0
 */
class Structure_Stats_Report_Command extends Abstract_Structure_Stats_Command {

	/**
	 * Report writer for the current run.
	 *
	 * @since 1.0.0
	 *
	 * @var Report|null
	 */
	private ?Report $report = null;

	/**
	 * Rows collected for the current batch.
	 *
	 * @since 1.0.0
	 *
	 * @var array<int, array<int, string>>
	 */
	private array $batch_rows = array();

	/**
	 * {@inheritdoc}
	 *
	 * @since 1.0.0
	 */
	public static function get_name(): string {
		return 'structure-stats-report';
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since 1.0.0
	 */
	public static function get_shortdesc(): string {
		return __(
			'Save structure stats for each post as a CSV report.',
			'maintenance-tools-wcus26'
		);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since 1.0.0
	 */
	public static function get_longdesc(): string {
		return <<<'HELP'
## EXAMPLES

    wp wcus26 structure-stats-report
    wp wcus26 structure-stats-report --limit=50 --batch-size=10
HELP;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since 1.0.0
	 */
	protected function before_run(): void {
		$this->report = Report::create(
			__( 'Post structure analysis', 'maintenance-tools-wcus26' ),
			implode( ',', self::COLUMNS ) . "\n"
		);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since 1.0.0
	 */
	protected function process_post( \WP_Post $post ): void {
		$this->batch_rows[] = $this->get_structure_row( $post );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, \WP_Post> $posts Posts processed in this batch.
	 */
	protected function after_batch( array $posts ): void {
		if ( ! $this->report instanceof Report || empty( $this->batch_rows ) ) {
			return;
		}

		$this->report->append( Report::to_csv( $this->batch_rows ) );
		$this->batch_rows = array();
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since 1.0.0
	 */
	protected function print_summary(): void {
		$report_id = $this->report instanceof Report ? $this->report->get_id() : 0;

		$this->success(
			sprintf(
				/* translators: 1: visited posts, 2: report ID */
				__(
					'Finished. Visited: %1$d. Report #%2$d saved. Download it from Maintenance → Reports.',
					'maintenance-tools-wcus26'
				),
				$this->visited,
				$report_id
			)
		);
	}
}
