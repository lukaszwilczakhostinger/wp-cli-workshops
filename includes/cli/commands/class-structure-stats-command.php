<?php
/**
 * Print per-post content structure stats.
 *
 * ## EXAMPLES
 *
 *     wp wcus26 structure-stats
 *     wp wcus26 structure-stats --post-status=any --limit=50
 *
 * @since 1.0.0
 *
 * @package Maintenance_Tools_WCUS26
 */

namespace Maintenance_Tools_WCUS26\CLI\Commands;

use Maintenance_Tools_WCUS26\CLI\Abstract_Structure_Stats_Command;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP-CLI command: print per-post structure stats as TSV.
 *
 * @since 1.0.0
 */
class Structure_Stats_Command extends Abstract_Structure_Stats_Command {

	/**
	 * Whether the column header has been printed.
	 *
	 * @since 1.0.0
	 *
	 * @var bool
	 */
	private bool $header_printed = false;

	/**
	 * {@inheritdoc}
	 *
	 * @since 1.0.0
	 */
	public static function get_name(): string {
		return 'structure-stats';
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since 1.0.0
	 */
	public static function get_shortdesc(): string {
		return __(
			'Print structure stats for each post (paragraphs, headings, images, links).',
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

    wp wcus26 structure-stats
    wp wcus26 structure-stats --post-type=post --limit=50 --save-logs
HELP;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since 1.0.0
	 */
	protected function process_post( \WP_Post $post ): void {
		if ( ! $this->header_printed ) {
			$this->log( implode( "\t", self::COLUMNS ) );
			$this->header_printed = true;
		}

		$this->log( implode( "\t", $this->get_structure_row( $post ) ) );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since 1.0.0
	 */
	protected function use_progress_bar(): bool {
		return false;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since 1.0.0
	 */
	protected function print_summary(): void {
		$this->success(
			sprintf(
				/* translators: %d: number of posts */
				__( 'Listed structure stats for %d post(s).', 'maintenance-tools-wcus26' ),
				$this->visited
			)
		);
	}
}
