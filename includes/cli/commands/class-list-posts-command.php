<?php
/**
 * List posts line by line: ID, title, category, image count, and URL.
 *
 * ## EXAMPLES
 *
 *     wp wcus26 list-posts
 *     wp wcus26 list-posts --post-status=any
 *     wp wcus26 list-posts --category_in=3,5 --limit=50
 *
 * @since 1.0.0
 *
 * @package Maintenance_Tools_WCUS26
 */

namespace Maintenance_Tools_WCUS26\CLI\Commands;

use Maintenance_Tools_WCUS26\CLI\Abstract_Posts_Command;
use Maintenance_Tools_WCUS26\CLI\Helpers\Content_Image_Counter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP-CLI command: list posts as TSV (ID, title, category, images, URL).
 *
 * @since 1.0.0
 */
class List_Posts_Command extends Abstract_Posts_Command {

	/**
	 * Image counter for Gutenberg and classic content.
	 *
	 * @since 1.0.0
	 *
	 * @var Content_Image_Counter|null
	 */
	private ?Content_Image_Counter $image_counter = null;

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
		return 'list-posts';
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since 1.0.0
	 */
	public static function get_shortdesc(): string {
		return __(
			'List posts with ID, title, category, image count, and URL.',
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

    wp wcus26 list-posts
    wp wcus26 list-posts --post-status=any --sleep=1
    wp wcus26 list-posts --category_in=3,5 --limit=50
HELP;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since 1.0.0
	 */
	protected function process_post( \WP_Post $post ): void {
		$this->print_header_once();

		$this->log(
			implode(
				"\t",
				array(
					(string) $post->ID,
					$post->post_title,
					$this->get_category_list( $post ),
					(string) $this->get_image_counter()->count( $post->post_content ),
					(string) get_permalink( $post ),
				)
			)
		);
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
				/* translators: %d: number of posts listed */
				__( 'Listed %d post(s).', 'maintenance-tools-wcus26' ),
				$this->visited
			)
		);
	}

	/**
	 * Print the TSV header before the first post.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function print_header_once(): void {
		if ( $this->header_printed ) {
			return;
		}

		$this->log( implode( "\t", array( 'ID', 'title', 'category', 'images', 'url' ) ) );
		$this->header_printed = true;
	}

	/**
	 * Comma-separated category names for a post.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_Post $post Post object.
	 * @return string Comma-separated category names, or empty string.
	 */
	private function get_category_list( \WP_Post $post ): string {
		$terms = get_the_terms( $post, 'category' );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return '';
		}

		return implode( ', ', wp_list_pluck( $terms, 'name' ) );
	}

	/**
	 * Lazy-load the image counter.
	 *
	 * @since 1.0.0
	 *
	 * @return Content_Image_Counter Counter instance.
	 */
	private function get_image_counter(): Content_Image_Counter {
		if ( ! $this->image_counter instanceof Content_Image_Counter ) {
			$this->image_counter = new Content_Image_Counter();
		}

		return $this->image_counter;
	}
}
