<?php
/**
 * Counts structural elements in post content.
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
 * Counts paragraphs, headings, images, and links in post content.
 *
 * @since 1.0.0
 */
class Post_Structure_Analyzer {

	/**
	 * Image counter used for the images stat.
	 *
	 * @since 1.0.0
	 *
	 * @var Content_Image_Counter
	 */
	private Content_Image_Counter $image_counter;

	/**
	 * Site hosts treated as internal links.
	 *
	 * @since 1.0.0
	 *
	 * @var array<int, string>
	 */
	private array $internal_hosts;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->image_counter  = new Content_Image_Counter();
		$this->internal_hosts = $this->detect_internal_hosts();
	}

	/**
	 * Count structural elements in raw post content.
	 *
	 * @since 1.0.0
	 *
	 * @param string $content Raw post_content.
	 * @return array{
	 *     paragraphs: int,
	 *     headings: int,
	 *     images: int,
	 *     internal_links: int,
	 *     external_links: int
	 * } Counts keyed by element type.
	 */
	public function analyze( string $content ): array {
		$paragraphs = 0;
		$headings   = 0;

		if ( '' !== trim( $content ) ) {
			if ( has_blocks( $content ) ) {
				$counts     = $this->count_in_blocks( parse_blocks( $content ) );
				$paragraphs = $counts['paragraphs'];
				$headings   = $counts['headings'];
			} else {
				$paragraphs = $this->count_tags( $content, '/<p\b/i' );
				$headings   = $this->count_tags( $content, '/<h[1-6]\b/i' );
			}
		}

		$links = $this->count_links( $content );

		return array(
			'paragraphs'     => $paragraphs,
			'headings'       => $headings,
			'images'         => $this->image_counter->count( $content ),
			'internal_links' => $links['internal'],
			'external_links' => $links['external'],
		);
	}

	/**
	 * Count paragraphs and headings in parsed blocks.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, array<string, mixed>> $blocks Parsed blocks.
	 * @return array{paragraphs: int, headings: int} Paragraph and heading counts.
	 */
	private function count_in_blocks( array $blocks ): array {
		$paragraphs = 0;
		$headings   = 0;

		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}

			$counts      = $this->count_in_block( $block );
			$paragraphs += $counts['paragraphs'];
			$headings   += $counts['headings'];
		}

		return array(
			'paragraphs' => $paragraphs,
			'headings'   => $headings,
		);
	}

	/**
	 * Count paragraphs and headings in a single parsed block.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $block A parsed block.
	 * @return array{paragraphs: int, headings: int} Paragraph and heading counts.
	 */
	private function count_in_block( array $block ): array {
		$name  = (string) ( $block['blockName'] ?? '' );
		$inner = is_array( $block['innerBlocks'] ?? null ) ? $block['innerBlocks'] : array();
		$html  = is_string( $block['innerHTML'] ?? null ) ? $block['innerHTML'] : '';

		$paragraphs = ( 'core/paragraph' === $name ) ? 1 : 0;
		$headings   = ( 'core/heading' === $name ) ? 1 : 0;

		if ( ! empty( $inner ) ) {
			$child       = $this->count_in_blocks( $inner );
			$paragraphs += $child['paragraphs'];
			$headings   += $child['headings'];

			return array(
				'paragraphs' => $paragraphs,
				'headings'   => $headings,
			);
		}

		if ( 'core/paragraph' !== $name ) {
			$paragraphs += $this->count_tags( $html, '/<p\b/i' );
		}

		if ( 'core/heading' !== $name ) {
			$headings += $this->count_tags( $html, '/<h[1-6]\b/i' );
		}

		return array(
			'paragraphs' => $paragraphs,
			'headings'   => $headings,
		);
	}

	/**
	 * Count internal and external links in HTML or block markup.
	 *
	 * @since 1.0.0
	 *
	 * @param string $content Raw post_content.
	 * @return array{internal: int, external: int} Link counts.
	 */
	private function count_links( string $content ): array {
		$internal = 0;
		$external = 0;

		$href_pattern = '/<a\s[^>]*href\s*=\s*["\']([^"\']+)["\']/i';

		if ( '' === $content || ! preg_match_all( $href_pattern, $content, $matches ) ) {
			return array(
				'internal' => 0,
				'external' => 0,
			);
		}

		foreach ( $matches[1] as $href ) {
			$type = $this->classify_href( trim( html_entity_decode( $href, ENT_QUOTES ) ) );

			if ( 'internal' === $type ) {
				++$internal;
			} elseif ( 'external' === $type ) {
				++$external;
			}
		}

		return array(
			'internal' => $internal,
			'external' => $external,
		);
	}

	/**
	 * Classify a link href as internal, external, or skip.
	 *
	 * @since 1.0.0
	 *
	 * @param string $href Link target.
	 * @return string One of: internal, external, skip.
	 */
	private function classify_href( string $href ): string {
		if ( '' === $href ) {
			return 'skip';
		}

		$lower = strtolower( $href );

		if (
			str_starts_with( $lower, '#' )
			|| str_starts_with( $lower, 'mailto:' )
			|| str_starts_with( $lower, 'tel:' )
			|| str_starts_with( $lower, 'javascript:' )
		) {
			return 'skip';
		}

		if ( str_starts_with( $href, '//' ) ) {
			$host = strtolower( (string) wp_parse_url( 'https:' . $href, PHP_URL_HOST ) );

			return in_array( $host, $this->internal_hosts, true ) ? 'internal' : 'external';
		}

		if ( ! preg_match( '#^[a-z][a-z0-9+.-]*://#i', $href ) ) {
			return 'internal';
		}

		$host = strtolower( (string) wp_parse_url( $href, PHP_URL_HOST ) );

		if ( '' === $host ) {
			return 'skip';
		}

		return in_array( $host, $this->internal_hosts, true ) ? 'internal' : 'external';
	}

	/**
	 * Hosts treated as internal (home and site URL).
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string> Lowercase hostnames.
	 */
	private function detect_internal_hosts(): array {
		$hosts = array();

		foreach ( array( home_url(), site_url() ) as $url ) {
			$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
			if ( '' !== $host ) {
				$hosts[] = $host;
			}
		}

		return array_values( array_unique( $hosts ) );
	}

	/**
	 * Count HTML tags matching a pattern.
	 *
	 * @since 1.0.0
	 *
	 * @param string $html    HTML fragment.
	 * @param string $pattern Tag regex.
	 * @return int Match count.
	 */
	private function count_tags( string $html, string $pattern ): int {
		if ( '' === $html ) {
			return 0;
		}

		$count = preg_match_all( $pattern, $html );

		return false === $count ? 0 : $count;
	}
}
