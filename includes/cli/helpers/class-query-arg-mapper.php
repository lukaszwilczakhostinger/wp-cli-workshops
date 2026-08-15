<?php
/**
 * Maps WP-CLI assoc args onto WP_Query arguments.
 *
 * Converts kebab-case / single-underscore CLI names to WP_Query keys
 * (category_in → category__in) and splits comma-separated values into arrays.
 *
 * @since 1.0.0
 *
 * @package Maintenance_Tools_WCUS26
 */

namespace Maintenance_Tools_WCUS26\CLI\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Maps CLI flags onto WP_Query arguments.
 *
 * @since 1.0.0
 */
class Query_Arg_Mapper {

	/**
	 * CLI flags that control the command, not WP_Query.
	 *
	 * @since 1.0.0
	 *
	 * @var array<int, string>
	 */
	private const RESERVED = array(
		'dry-run',
		'dry_run',
		'limit',
		'offset',
		'batch-size',
		'batch_size',
		'sleep',
		'save-logs',
		'save_logs',
		'skip-all-hooks',
		'skip_all_hooks',
		'skip-hooks',
		'skip_hooks',
		'yes',
	);

	/**
	 * Pagination keys that the posts iterator owns.
	 *
	 * @since 1.0.0
	 *
	 * @var array<int, string>
	 */
	private const BLOCKED_QUERY_KEYS = array(
		'posts_per_page',
		'numberposts',
		'paged',
		'offset',
		'nopaging',
		'no_found_rows',
	);

	/**
	 * Explicit CLI name → WP_Query key map.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, string>
	 */
	private const EXPLICIT_MAP = array(
		'post-type'             => 'post_type',
		'post_type'             => 'post_type',
		'post-status'           => 'post_status',
		'post_status'           => 'post_status',
		'post-parent'           => 'post_parent',
		'post_parent'           => 'post_parent',
		'post-parent-in'        => 'post_parent__in',
		'post_parent_in'        => 'post_parent__in',
		'post-parent-not-in'    => 'post_parent__not_in',
		'post_parent_not_in'    => 'post_parent__not_in',
		'post-in'               => 'post__in',
		'post_in'               => 'post__in',
		'post-not-in'           => 'post__not_in',
		'post_not_in'           => 'post__not_in',
		'post-name-in'          => 'post_name__in',
		'post_name_in'          => 'post_name__in',
		'order-by'              => 'orderby',
		'order_by'              => 'orderby',
		'category-in'           => 'category__in',
		'category_in'           => 'category__in',
		'category-not-in'       => 'category__not_in',
		'category_not_in'       => 'category__not_in',
		'category-and'          => 'category__and',
		'category_and'          => 'category__and',
		'category-name'         => 'category_name',
		'tag-in'                => 'tag__in',
		'tag_in'                => 'tag__in',
		'tag-not-in'            => 'tag__not_in',
		'tag_not_in'            => 'tag__not_in',
		'tag-and'               => 'tag__and',
		'tag_and'               => 'tag__and',
		'tag-slug-in'           => 'tag_slug__in',
		'tag_slug_in'           => 'tag_slug__in',
		'tag-slug-and'          => 'tag_slug__and',
		'tag_slug_and'          => 'tag_slug__and',
		'tag-id'                => 'tag_id',
		'author-in'             => 'author__in',
		'author_in'             => 'author__in',
		'author-not-in'         => 'author__not_in',
		'author_not_in'         => 'author__not_in',
		'author-name'           => 'author_name',
		'has-password'          => 'has_password',
		'post-password'         => 'post_password',
		'ignore-sticky-posts'   => 'ignore_sticky_posts',
		'ignore_sticky_posts'   => 'ignore_sticky_posts',
		'meta-key'              => 'meta_key',
		'meta-value'            => 'meta_value',
		'meta-compare'          => 'meta_compare',
		'meta-type'             => 'meta_type',
		'meta-value-num'        => 'meta_value_num',
	);

	/**
	 * Query keys that should always be arrays of integers.
	 *
	 * @since 1.0.0
	 *
	 * @var array<int, string>
	 */
	private const INTEGER_LIST_KEYS = array(
		'category__in',
		'category__not_in',
		'category__and',
		'tag__in',
		'tag__not_in',
		'tag__and',
		'post__in',
		'post__not_in',
		'author__in',
		'author__not_in',
		'post_parent__in',
		'post_parent__not_in',
	);

	/**
	 * Query keys that are a single integer.
	 *
	 * @since 1.0.0
	 *
	 * @var array<int, string>
	 */
	private const INTEGER_KEYS = array(
		'p',
		'page_id',
		'author',
		'cat',
		'tag_id',
		'post_parent',
		'w',
		'year',
		'monthnum',
		'day',
		'hour',
		'minute',
		'second',
		'm',
	);

	/**
	 * Query keys that should become arrays when comma-separated.
	 *
	 * @since 1.0.0
	 *
	 * @var array<int, string>
	 */
	private const STRING_LIST_KEYS = array(
		'post_type',
		'post_status',
		'post_name__in',
		'tag_slug__in',
		'tag_slug__and',
		'name',
	);

	/**
	 * Convert WP-CLI assoc args to WP_Query args.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $assoc_args     Arguments from WP-CLI.
	 * @param array<int, string>   $extra_reserved Additional CLI-only keys to skip.
	 * @return array<string, mixed> Mapped WP_Query arguments.
	 */
	public function to_query_args( array $assoc_args, array $extra_reserved = array() ): array {
		$reserved   = array_merge( self::RESERVED, $extra_reserved );
		$query_args = array();

		foreach ( $assoc_args as $cli_name => $value ) {
			if ( in_array( $cli_name, $reserved, true ) ) {
				continue;
			}

			$query_key = $this->map_key( (string) $cli_name );

			if ( in_array( $query_key, self::BLOCKED_QUERY_KEYS, true ) ) {
				continue;
			}

			$query_args[ $query_key ] = $this->normalize_value( $query_key, $value );
		}

		/**
		 * Filters mapped WP_Query arguments.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed> $query_args Mapped query arguments.
		 * @param array<string, mixed> $assoc_args Original CLI arguments.
		 */
		return (array) apply_filters( 'mt_wcus26_query_args', $query_args, $assoc_args );
	}

	/**
	 * Map a CLI argument name to a WP_Query key.
	 *
	 * @since 1.0.0
	 *
	 * @param string $cli_name Argument name as passed to WP-CLI.
	 * @return string WP_Query argument name.
	 */
	public function map_key( string $cli_name ): string {
		$normalized = str_replace( '-', '_', $cli_name );

		if ( isset( self::EXPLICIT_MAP[ $cli_name ] ) ) {
			return self::EXPLICIT_MAP[ $cli_name ];
		}

		if ( isset( self::EXPLICIT_MAP[ $normalized ] ) ) {
			return self::EXPLICIT_MAP[ $normalized ];
		}

		if ( preg_match( '/^(.+)_(in|not_in|and)$/', $normalized, $matches ) ) {
			return $matches[1] . '__' . $matches[2];
		}

		return $normalized;
	}

	/**
	 * Normalize a CLI value for WP_Query.
	 *
	 * @since 1.0.0
	 *
	 * @param string $query_key WP_Query argument name.
	 * @param mixed  $value     Raw CLI value.
	 * @return mixed Normalized value for WP_Query.
	 */
	private function normalize_value( string $query_key, $value ) {
		if ( is_bool( $value ) || is_array( $value ) ) {
			return $value;
		}

		$string_value = is_scalar( $value ) ? trim( (string) $value ) : $value;

		if ( in_array( $query_key, self::STRING_LIST_KEYS, true ) ) {
			$items = $this->split_list( $string_value );

			if (
				str_ends_with( $query_key, '__in' )
				|| str_ends_with( $query_key, '__not_in' )
				|| str_ends_with( $query_key, '__and' )
			) {
				return $items;
			}

			return count( $items ) > 1 ? $items : ( $items[0] ?? $string_value );
		}

		if (
			in_array( $query_key, self::INTEGER_LIST_KEYS, true )
			|| str_ends_with( $query_key, '__in' )
			|| str_ends_with( $query_key, '__not_in' )
			|| str_ends_with( $query_key, '__and' )
		) {
			$items = $this->split_list( $string_value );

			return array_map( 'intval', $items );
		}

		if ( in_array( $query_key, self::INTEGER_KEYS, true ) && is_numeric( $string_value ) ) {
			return (int) $string_value;
		}

		if (
			in_array( $string_value, array( 'true', 'false' ), true )
			&& str_starts_with( $query_key, 'has_' )
		) {
			return 'true' === $string_value;
		}

		return $string_value;
	}

	/**
	 * Split a comma-separated CLI value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Raw value.
	 * @return array<int, string> Non-empty trimmed items.
	 */
	private function split_list( $value ): array {
		$items = array_map( 'trim', explode( ',', (string) $value ) );

		return array_values(
			array_filter(
				$items,
				static function ( $item ) {
					return '' !== $item;
				}
			)
		);
	}
}
