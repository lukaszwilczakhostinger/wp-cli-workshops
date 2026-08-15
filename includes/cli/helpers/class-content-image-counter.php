<?php
/**
 * Counts images embedded in post content, including Gutenberg blocks.
 *
 * Handles core image/gallery/cover/media-text blocks, Spectra
 * `uagb/image-gallery` (self-closing, no `<img>` HTML), and classic markup.
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
 * Counts images in Gutenberg blocks and classic HTML.
 *
 * @since 1.0.0
 */
class Content_Image_Counter {

	/**
	 * Count images in raw post content.
	 *
	 * @since 1.0.0
	 *
	 * @param string $content Raw post_content.
	 * @return int Number of images.
	 */
	public function count( string $content ): int {
		if ( '' === trim( $content ) ) {
			return 0;
		}

		if ( ! has_blocks( $content ) ) {
			return $this->count_html_images( $content );
		}

		return $this->count_in_blocks( parse_blocks( $content ) );
	}

	/**
	 * Recursively count images in parsed blocks.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, array<string, mixed>> $blocks Parsed blocks.
	 * @return int Image count.
	 */
	private function count_in_blocks( array $blocks ): int {
		$count = 0;

		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}

			$count += $this->count_in_block( $block );
		}

		return $count;
	}

	/**
	 * Count images in a single parsed block.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $block A single parsed block.
	 * @return int Image count.
	 */
	private function count_in_block( array $block ): int {
		$name  = $block['blockName'] ?? null;
		$attrs = is_array( $block['attrs'] ?? null ) ? $block['attrs'] : array();
		$inner = is_array( $block['innerBlocks'] ?? null ) ? $block['innerBlocks'] : array();
		$html  = is_string( $block['innerHTML'] ?? null ) ? $block['innerHTML'] : '';

		$count = $this->count_from_block_attrs( (string) $name, $attrs );

		if ( 'core/gallery' === (string) $name && ! empty( $inner ) ) {
			$count = 0;
		}

		if ( ! empty( $inner ) ) {
			return $count + $this->count_in_blocks( $inner );
		}

		// Leaf blocks: avoid double-counting `<img>` already represented by the block itself.
		if ( 0 === $count ) {
			$count += $this->count_html_images( $html );
		}

		return $count;
	}

	/**
	 * Count images declared in block attributes (no HTML inner content required).
	 *
	 * @since 1.0.0
	 *
	 * @param string               $name  Block name, e.g. core/image.
	 * @param array<string, mixed> $attrs Block attributes.
	 * @return int Image count.
	 */
	private function count_from_block_attrs( string $name, array $attrs ): int {
		if ( '' === $name ) {
			return 0;
		}

		if (
			in_array(
				$name,
				array( 'core/image', 'core/post-featured-image', 'uagb/image' ),
				true
			)
		) {
			return 1;
		}

		if (
			'core/gallery' === $name
			&& ! empty( $attrs['ids'] )
			&& is_array( $attrs['ids'] )
		) {
			return count( $attrs['ids'] );
		}

		if (
			'core/media-text' === $name
			&& 'video' !== ( $attrs['mediaType'] ?? 'image' )
		) {
			if ( ! empty( $attrs['mediaId'] ) || ! empty( $attrs['mediaUrl'] ) ) {
				return 1;
			}
		}

		if (
			'core/cover' === $name
			&& 'video' !== ( $attrs['backgroundType'] ?? 'image' )
		) {
			if ( ! empty( $attrs['id'] ) || ! empty( $attrs['url'] ) ) {
				return 1;
			}
		}

		if ( 'uagb/image-gallery' === $name ) {
			if (
				! empty( $attrs['mediaIDs'] )
				&& is_array( $attrs['mediaIDs'] )
			) {
				return count( $attrs['mediaIDs'] );
			}

			if (
				! empty( $attrs['mediaGallery'] )
				&& is_array( $attrs['mediaGallery'] )
			) {
				return count( $attrs['mediaGallery'] );
			}
		}

		return 0;
	}

	/**
	 * Count `<img>` tags and `[gallery ids]` shortcodes in HTML/classic content.
	 *
	 * @since 1.0.0
	 *
	 * @param string $html HTML fragment.
	 * @return int Image count.
	 */
	private function count_html_images( string $html ): int {
		if ( '' === trim( $html ) ) {
			return 0;
		}

		$images = preg_match_all( '/<img\b/i', $html, $matches );
		$count  = false === $images ? 0 : $images;

		if (
			has_shortcode( $html, 'gallery' )
			&& preg_match_all( '/\[gallery([^\]]*)\]/i', $html, $galleries )
		) {
			foreach ( $galleries[1] as $attr_string ) {
				$atts = shortcode_parse_atts( $attr_string );
				if ( empty( $atts['ids'] ) ) {
					continue;
				}

				$ids    = array_filter(
					array_map( 'trim', explode( ',', (string) $atts['ids'] ) )
				);
				$count += count( $ids );
			}
		}

		return $count;
	}
}
