<?php
/**
 * Shared row builder for structure-stats commands.
 *
 * @package Maintenance_Tools_WCUS26
 * @since 1.0.0
 */

namespace Maintenance_Tools_WCUS26\CLI;

use Maintenance_Tools_WCUS26\CLI\Helpers\Post_Structure_Analyzer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base class for commands that print or save per-post structure stats.
 *
 * @since 1.0.0
 */
abstract class Abstract_Structure_Stats_Command extends Abstract_Posts_Command {

	/**
	 * CSV / table column keys.
	 *
	 * @since 1.0.0
	 * @var array<int, string>
	 */
	protected const COLUMNS = array(
		'id',
		'title',
		'paragraphs',
		'headings',
		'images',
		'internal_links',
		'external_links',
	);

	/**
	 * Content analyzer used to build stats rows.
	 *
	 * @since 1.0.0
	 * @var Post_Structure_Analyzer|null
	 */
	private ?Post_Structure_Analyzer $analyzer = null;

	/**
	 * Build one stats row for a post.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_Post $post Post object.
	 * @return array<int, string> Values in COLUMNS order, all strings.
	 */
	protected function get_structure_row( \WP_Post $post ): array {
		$stats = $this->get_analyzer()->analyze( $post->post_content );

		return array(
			(string) $post->ID,
			$post->post_title,
			(string) $stats['paragraphs'],
			(string) $stats['headings'],
			(string) $stats['images'],
			(string) $stats['internal_links'],
			(string) $stats['external_links'],
		);
	}

	/**
	 * Lazy-load the shared structure analyzer.
	 *
	 * @since 1.0.0
	 *
	 * @return Post_Structure_Analyzer Analyzer instance.
	 */
	protected function get_analyzer(): Post_Structure_Analyzer {
		if ( ! $this->analyzer instanceof Post_Structure_Analyzer ) {
			$this->analyzer = new Post_Structure_Analyzer();
		}

		return $this->analyzer;
	}
}
