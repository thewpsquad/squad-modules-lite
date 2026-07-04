<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Timeline helper.
 *
 * Pure-PHP markup builder shared by the Divi 4 and Divi 5 Timeline render
 * paths. It emits the `.squad-timeline` track and `.squad-timeline__item`
 * markup so output is byte-identical across builders. A tiny IntersectionObserver
 * frontend engine (`timeline.ts`) reads the `data-reveal` / `data-stagger`
 * attributes to animate items into view. It has NO Divi dependency, so it boots
 * cleanly under PHPUnit (unlike the module abstracts).
 *
 * @since   4.3.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Shared\Modules\Content\Timeline;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use function esc_attr;
use function esc_html;
use function esc_url;
use function in_array;
use function sprintf;
use function wp_kses_post;

/**
 * Timeline helper.
 *
 * @since 4.3.0
 */
final class Timeline_Helper {

	/**
	 * Allowed orientation tokens (first = fallback).
	 *
	 * @since 4.3.0
	 *
	 * @var array<int, string>
	 */
	public const ORIENTATIONS = array( 'vertical', 'horizontal' );

	/**
	 * Allowed layout tokens (first = fallback).
	 *
	 * @since 4.3.0
	 *
	 * @var array<int, string>
	 */
	public const LAYOUTS = array( 'alternating', 'left', 'right' );

	/**
	 * Allowed reveal tokens (first = fallback).
	 *
	 * @since 4.3.0
	 *
	 * @var array<int, string>
	 */
	public const REVEALS = array( 'on', 'off' );

	/**
	 * Allowed marker-type tokens (last = fallback).
	 *
	 * @since 4.3.0
	 *
	 * @var array<int, string>
	 */
	public const MARKER_TYPES = array( 'icon', 'image', 'number', 'dot' );

	/**
	 * Validate a token against an allowlist, falling back to the first entry.
	 *
	 * @since 4.3.0
	 *
	 * @param string             $value     The candidate token.
	 * @param array<int, string> $allowlist The allowlist (index 0 = fallback).
	 *
	 * @return string
	 */
	public static function validate( string $value, array $allowlist ): string {
		return in_array( $value, $allowlist, true ) ? $value : (string) ( $allowlist[0] ?? '' );
	}

	/**
	 * Build the `.squad-timeline` track shell shared with the Divi 5 render.
	 *
	 * @since 4.3.0
	 *
	 * @param array<string, mixed> $config        Resolved config: orientation,
	 *                                            layout, reveal, stagger.
	 * @param string               $children_html Rendered child items HTML.
	 *
	 * @return string
	 */
	public static function build_track( array $config, string $children_html ): string {
		$orientation = self::validate( (string) ( $config['orientation'] ?? 'vertical' ), self::ORIENTATIONS );
		$layout      = self::validate( (string) ( $config['layout'] ?? 'alternating' ), self::LAYOUTS );
		$reveal      = self::validate( (string) ( $config['reveal'] ?? 'on' ), self::REVEALS );
		$stagger     = (int) ( $config['stagger'] ?? 120 );

		if ( $stagger < 0 ) {
			$stagger = 0;
		}
		if ( $stagger > 600 ) {
			$stagger = 600;
		}

		return sprintf(
			'<div class="squad-timeline squad-timeline--%1$s squad-timeline--%2$s" data-reveal="%3$s" data-stagger="%4$s">'
			. '<div class="squad-timeline__line" aria-hidden="true"></div>'
			. '<div class="squad-timeline__track">%5$s</div>'
			. '</div>',
			esc_attr( $orientation ),
			esc_attr( $layout ),
			esc_attr( $reveal ),
			esc_attr( (string) $stagger ),
			$children_html
		);
	}

	/**
	 * Build a full timeline item (outer node + inner) for the Divi 4 render.
	 *
	 * @since 4.3.0
	 *
	 * @param array<string, mixed> $item Item config (see build_item_inner()).
	 *
	 * @return string
	 */
	public static function build_item( array $item ): string {
		return sprintf(
			'<div class="squad-timeline__item">%s</div>',
			self::build_item_inner( $item )
		);
	}

	/**
	 * Build the inner (marker + card) markup for a timeline item.
	 *
	 * Used directly by the Divi 5 child, whose DiviModule wrapper IS the
	 * `.squad-timeline__item` node (the wrapper adds that class).
	 *
	 * @since 4.3.0
	 *
	 * @param array<string, mixed> $item Item config: title, date, content,
	 *                                   markerType, icon, image, imageAlt,
	 *                                   number, link, target.
	 *
	 * @return string
	 */
	public static function build_item_inner( array $item ): string {
		$marker = self::build_marker( $item );

		$date    = (string) ( $item['date'] ?? '' );
		$title   = (string) ( $item['title'] ?? '' );
		$content = (string) ( $item['content'] ?? '' );

		$card_inner = '';
		if ( '' !== $date ) {
			$card_inner .= sprintf( '<span class="squad-timeline__date">%s</span>', esc_html( $date ) );
		}
		if ( '' !== $title ) {
			$card_inner .= sprintf( '<h3 class="squad-timeline__title">%s</h3>', esc_html( $title ) );
		}
		if ( '' !== $content ) {
			$card_inner .= sprintf( '<div class="squad-timeline__content">%s</div>', wp_kses_post( $content ) );
		}

		$card = sprintf( '<div class="squad-timeline__card">%s</div>', $card_inner );

		$link = esc_url( (string) ( $item['link'] ?? '' ) );
		if ( '' !== $link ) {
			$target = (string) ( $item['target'] ?? '_self' );
			$target = in_array( $target, array( '_self', '_blank' ), true ) ? $target : '_self';
			$rel    = '_blank' === $target ? ' rel="noopener noreferrer"' : '';

			$card = sprintf(
				'<a class="squad-timeline__card" href="%1$s" target="%2$s"%3$s>%4$s</a>',
				$link,
				esc_attr( $target ),
				$rel,
				$card_inner
			);
		}

		return sprintf(
			'<div class="squad-timeline__marker">%1$s</div>%2$s',
			$marker,
			$card
		);
	}

	/**
	 * Build the marker markup for an item based on its marker type.
	 *
	 * @since 4.3.0
	 *
	 * @param array<string, mixed> $item Item config.
	 *
	 * @return string
	 */
	private static function build_marker( array $item ): string {
		$type = self::validate( (string) ( $item['markerType'] ?? 'dot' ), self::MARKER_TYPES );

		switch ( $type ) {
			case 'icon':
				$icon = (string) ( $item['icon'] ?? '' );
				if ( '' === $icon ) {
					break;
				}

				return sprintf(
					'<span class="squad-timeline__icon et-pb-icon">%s</span>',
					esc_html( $icon )
				);

			case 'image':
				$image = esc_url( (string) ( $item['image'] ?? '' ) );
				if ( '' === $image ) {
					break;
				}

				return sprintf(
					'<img class="squad-timeline__image" src="%1$s" alt="%2$s" loading="lazy" />',
					$image,
					esc_attr( (string) ( $item['imageAlt'] ?? '' ) )
				);

			case 'number':
				$number = (string) ( $item['number'] ?? '' );
				if ( '' === $number ) {
					break;
				}

				return sprintf(
					'<span class="squad-timeline__number">%s</span>',
					esc_html( $number )
				);
		}

		// `dot`/default and empty fallbacks.
		return '<span class="squad-timeline__dot"></span>';
	}
}
