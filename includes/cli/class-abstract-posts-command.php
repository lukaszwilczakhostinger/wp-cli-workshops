<?php
/**
 * Abstract WP-CLI command that iterates over posts in batches.
 *
 * Shared flags: --post-type, --post-status, --limit, --offset, --batch-size,
 * --sleep, --dry-run, --skip-all-hooks, --skip-hooks, plus any extra WP_Query
 * args (e.g. --category_in=1,2).
 *
 * Example:
 *
 *     namespace Maintenance_Tools_WCUS26\CLI\Commands;
 *
 *     class Recalc_Command extends \Maintenance_Tools_WCUS26\CLI\Abstract_Posts_Command {
 *         public static function get_name(): string {
 *             return 'recalc';
 *         }
 *
 *         public static function get_shortdesc(): string {
 *             return 'Recalculate a value on matching posts.';
 *         }
 *
 *         protected function process_post( \WP_Post $post ): void {
 *             if ( $this->is_dry_run() ) {
 *                 $this->log( sprintf( 'Would update #%d %s', $post->ID, $post->post_title ) );
 *                 return;
 *             }
 *
 *             // Perform the update.
 *         }
 *     }
 *
 * @since 1.0.0
 *
 * @package Maintenance_Tools_WCUS26
 */

namespace Maintenance_Tools_WCUS26\CLI;

use Maintenance_Tools_WCUS26\CLI\Helpers\Hook_Skipper;
use Maintenance_Tools_WCUS26\CLI\Helpers\Query_Arg_Mapper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base class for commands that walk posts in batches.
 *
 * @since 1.0.0
 */
abstract class Abstract_Posts_Command extends Abstract_Command {

	/**
	 * Default number of posts fetched per query.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	public const DEFAULT_BATCH_SIZE = 10;

	/**
	 * Posts visited in this run.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected int $visited = 0;

	/**
	 * Posts updated (or that would be updated in dry-run).
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected int $updated = 0;

	/**
	 * Posts skipped on purpose.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected int $skipped = 0;

	/**
	 * Posts that failed while processing.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected int $failed = 0;

	/**
	 * Query argument mapper.
	 *
	 * @since 1.0.0
	 *
	 * @var Query_Arg_Mapper|null
	 */
	private ?Query_Arg_Mapper $query_arg_mapper = null;

	/**
	 * Hook skipper for --skip-all-hooks / --skip-hooks.
	 *
	 * @since 1.0.0
	 *
	 * @var Hook_Skipper|null
	 */
	private ?Hook_Skipper $hook_skipper = null;

	/**
	 * Handle a single post. Called once per matching post.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_Post $post Post being processed.
	 * @return void
	 */
	abstract protected function process_post( \WP_Post $post ): void;

	/**
	 * {@inheritdoc}
	 *
	 * @since 1.0.0
	 */
	protected function get_base_synopsis(): array {
		return array(
			array(
				'type'        => 'assoc',
				'name'        => 'post-type',
				'description' => 'Post type(s) to query. Comma-separated for multiple types.',
				'optional'    => true,
				'default'     => 'post',
			),
			array(
				'type'        => 'assoc',
				'name'        => 'post-status',
				'description' => 'Post status(es) to query. Comma-separated for multiple statuses.',
				'optional'    => true,
				'default'     => 'publish',
			),
			array(
				'type'        => 'assoc',
				'name'        => 'limit',
				'description' => 'Maximum number of posts to process. 0 means no limit.',
				'optional'    => true,
				'default'     => '0',
			),
			array(
				'type'        => 'assoc',
				'name'        => 'offset',
				'description' => 'Number of matching posts to skip before processing.',
				'optional'    => true,
				'default'     => '0',
			),
			array(
				'type'        => 'assoc',
				'name'        => 'batch-size',
				'description' => 'Number of posts to fetch per batch.',
				'optional'    => true,
				'default'     => (string) self::DEFAULT_BATCH_SIZE,
			),
			array(
				'type'        => 'assoc',
				'name'        => 'sleep',
				'description' => 'Seconds to wait after each post.'
					. ' Use decimals for sub-second delays (e.g. 0.5).',
				'optional'    => true,
				'default'     => '0',
			),
			array(
				'type'        => 'flag',
				'name'        => 'dry-run',
				'description' => 'Preview the run without writing changes.',
				'optional'    => true,
			),
			array(
				'type'        => 'flag',
				'name'        => 'skip-all-hooks',
				'description' => 'Disable all actions and filters while processing each post.',
				'optional'    => true,
			),
			array(
				'type'        => 'assoc',
				'name'        => 'skip-hooks',
				'description' => 'Comma-separated hook names to disable while processing each post.',
				'optional'    => true,
			),
			array(
				'type'        => 'generic',
				'optional'    => true,
				'repeating'   => true,
				'name'        => 'field',
				'description' => 'Additional WP_Query arguments, e.g.'
					. ' --category_in=1,2 --tag_slug_in=europe --author=3.',
			),
		);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since 1.0.0
	 */
	protected function run( array $args, array $assoc_args ): void {
		$this->iterate_posts();
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since 1.0.0
	 */
	protected function after_run(): void {
		if ( $this->visited > 0 || $this->failed > 0 ) {
			$this->print_summary();
		}
	}

	/**
	 * Walk matching posts in batches.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function iterate_posts(): void {
		$batch_size = max( 1, (int) $this->get_flag( 'batch-size', self::DEFAULT_BATCH_SIZE ) );
		$limit      = max( 0, (int) $this->get_flag( 'limit', 0 ) );
		$offset     = max( 0, (int) $this->get_flag( 'offset', 0 ) );

		$remaining      = $limit > 0 ? $limit : null;
		$current_offset = $offset;
		$total          = $this->count_matching_posts( $offset, $limit );
		$progress       = null;

		$this->debug( 'Matched posts: ' . $total );
		$this->debug( 'Query args: ' . wp_json_encode( $this->build_query_args() ) );

		if ( $this->use_progress_bar() && $total > 0 ) {
			$progress = \WP_CLI\Utils\make_progress_bar( $this->get_progress_label(), $total );
		}

		if ( 0 === $total ) {
			$this->warning( __( 'No posts matched the query.', 'maintenance-tools-wcus26' ) );
			return;
		}

		do {
			$this_batch = null === $remaining ? $batch_size : min( $batch_size, $remaining );

			$query = new \WP_Query( $this->build_batch_query_args( $current_offset, $this_batch ) );

			if ( ! $query->have_posts() ) {
				break;
			}

			$batch_posts = array();

			foreach ( $query->posts as $post ) {
				if ( ! $post instanceof \WP_Post ) {
					$post = get_post( $post );
				}

				if ( ! $post instanceof \WP_Post ) {
					$this->failed++;
					continue;
				}

				$this->begin_hook_skip();

				try {
					$this->process_post( $post );
					$batch_posts[] = $post;
				} catch ( \Throwable $exception ) {
					$this->failed++;
					$this->warning(
						sprintf(
							/* translators: 1: post ID, 2: error message */
							__( 'Post #%1$d failed: %2$s', 'maintenance-tools-wcus26' ),
							$post->ID,
							$exception->getMessage()
						)
					);
				} finally {
					$this->end_hook_skip();
				}

				$this->visited++;

				if ( $progress ) {
					$progress->tick();
				}

				if ( $this->visited < $total ) {
					$this->maybe_sleep();
				}
			}

			$found           = count( $query->posts );
			$current_offset += $found;

			if ( null !== $remaining ) {
				$remaining -= $found;
			}

			$this->after_batch( $batch_posts );
			$this->free_memory();

			$more = $found === $this_batch && ( null === $remaining || $remaining > 0 );
		} while ( $more );

		if ( $progress ) {
			$progress->finish();
		}
	}

	/**
	 * Default WP_Query arguments, before CLI mapping.
	 *
	 * Override to change defaults for a specific command.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	protected function get_default_query_args(): array {
		return array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'orderby'             => 'ID',
			'order'               => 'ASC',
			'ignore_sticky_posts' => true,
		);
	}

	/**
	 * Build the base WP_Query args from defaults + mapped CLI flags.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	protected function build_query_args(): array {
		$mapped = $this->get_query_arg_mapper()->to_query_args(
			$this->assoc_args,
			$this->get_extra_reserved_args()
		);

		$query_args = array_merge( $this->get_default_query_args(), $mapped );

		return $this->filter_query_args( $query_args );
	}

	/**
	 * Last chance for a subclass to adjust WP_Query args.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $query_args Query arguments.
	 * @return array<string, mixed>
	 */
	protected function filter_query_args( array $query_args ): array {
		return $query_args;
	}

	/**
	 * Extra CLI keys that must not be forwarded to WP_Query.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string>
	 */
	protected function get_extra_reserved_args(): array {
		return array();
	}

	/**
	 * Query args for a single batch.
	 *
	 * @since 1.0.0
	 *
	 * @param int $offset     Current offset.
	 * @param int $batch_size Number of posts to fetch.
	 * @return array<string, mixed>
	 */
	protected function build_batch_query_args( int $offset, int $batch_size ): array {
		return array_merge(
			$this->build_query_args(),
			array(
				'posts_per_page' => $batch_size,
				'offset'         => $offset,
				'no_found_rows'  => true,
			)
		);
	}

	/**
	 * Count posts that will be processed, honouring offset and limit.
	 *
	 * @since 1.0.0
	 *
	 * @param int $offset Number of posts skipped.
	 * @param int $limit  Max posts to process, or 0 for no limit.
	 * @return int
	 */
	protected function count_matching_posts( int $offset, int $limit ): int {
		$count_query = new \WP_Query(
			array_merge(
				$this->build_query_args(),
				array(
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'no_found_rows'  => false,
				)
			)
		);

		$total = max( 0, (int) $count_query->found_posts - $offset );

		if ( $limit > 0 ) {
			$total = min( $total, $limit );
		}

		return $total;
	}

	/**
	 * Label used by the WP-CLI progress bar.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	protected function get_progress_label(): string {
		return __( 'Processing posts', 'maintenance-tools-wcus26' );
	}

	/**
	 * Whether to show a progress bar.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	protected function use_progress_bar(): bool {
		return true;
	}

	/**
	 * Called after a batch of posts has been processed.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, \WP_Post> $posts Posts processed in this batch.
	 * @return void
	 */
	protected function after_batch( array $posts ): void {
	}

	/**
	 * Print a short run summary.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function print_summary(): void {
		if ( $this->is_dry_run() ) {
			$this->success(
				sprintf(
					/* translators: 1: visited, 2: updated, 3: skipped, 4: failed */
					__(
						'Dry run complete. Visited: %1$d; would update: %2$d; skipped: %3$d; failed: %4$d.',
						'maintenance-tools-wcus26'
					),
					$this->visited,
					$this->updated,
					$this->skipped,
					$this->failed
				)
			);
			return;
		}

		$this->success(
			sprintf(
				/* translators: 1: visited, 2: updated, 3: skipped, 4: failed */
				__(
					'Finished. Visited: %1$d; updated: %2$d; skipped: %3$d; failed: %4$d.',
					'maintenance-tools-wcus26'
				),
				$this->visited,
				$this->updated,
				$this->skipped,
				$this->failed
			)
		);
	}

	/**
	 * Pause between posts when --sleep is set.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function maybe_sleep(): void {
		$seconds = (float) $this->get_flag( 'sleep', 0 );

		if ( $seconds <= 0 ) {
			return;
		}

		$this->debug( sprintf( 'Sleeping for %s second(s).', $seconds ) );
		usleep( (int) round( $seconds * 1000000 ) );
	}

	/**
	 * Free object cache between batches during long runs.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function free_memory(): void {
		if (
			class_exists( '\WP_CLI\Utils' )
			&& method_exists( '\WP_CLI\Utils', 'wp_clear_object_cache' )
		) {
			\WP_CLI\Utils::wp_clear_object_cache();
			return;
		}

		wp_cache_flush();
	}

	/**
	 * Lazy-load the query argument mapper.
	 *
	 * @since 1.0.0
	 *
	 * @return Query_Arg_Mapper Mapper instance.
	 */
	protected function get_query_arg_mapper(): Query_Arg_Mapper {
		if ( ! $this->query_arg_mapper instanceof Query_Arg_Mapper ) {
			$this->query_arg_mapper = new Query_Arg_Mapper();
		}

		return $this->query_arg_mapper;
	}

	/**
	 * Disable hooks for the current post when skip flags are set.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function begin_hook_skip(): void {
		$skip_all = (bool) $this->get_flag( 'skip-all-hooks', false );
		$hooks    = $this->parse_skip_hooks();

		if ( ! $skip_all && empty( $hooks ) ) {
			return;
		}

		$this->debug(
			$skip_all
				? 'Skipping all hooks for this post.'
				: 'Skipping hooks: ' . implode( ', ', $hooks )
		);

		$this->get_hook_skipper()->start( $skip_all, $hooks );
	}

	/**
	 * Restore hooks after process_post().
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function end_hook_skip(): void {
		$this->get_hook_skipper()->stop();
	}

	/**
	 * Parse --skip-hooks into a list of hook names.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string> Hook names.
	 */
	private function parse_skip_hooks(): array {
		$raw = $this->get_flag( 'skip-hooks', '' );

		if ( ! is_scalar( $raw ) ) {
			return array();
		}

		$items = array_map( 'trim', explode( ',', (string) $raw ) );

		return array_values(
			array_filter(
				$items,
				static function ( $item ) {
					return '' !== $item;
				}
			)
		);
	}

	/**
	 * Lazy-load the hook skipper.
	 *
	 * @since 1.0.0
	 *
	 * @return Hook_Skipper Skipper instance.
	 */
	private function get_hook_skipper(): Hook_Skipper {
		if ( ! $this->hook_skipper instanceof Hook_Skipper ) {
			$this->hook_skipper = new Hook_Skipper();
		}

		return $this->hook_skipper;
	}
}
