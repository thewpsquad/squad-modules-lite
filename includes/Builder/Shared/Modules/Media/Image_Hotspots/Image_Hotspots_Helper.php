<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Image Hotspots helper.
 *
 * Pure-PHP markup builder shared by the Divi 4 and Divi 5 Image Hotspots render
 * paths. It emits the `.squad-hotspots` canvas (an image with an absolutely
 * positioned pin layer) and each `.squad-hotspots__pin` so output is
 * byte-identical across builders. A tiny vanilla frontend engine
 * (`image-hotspots.ts`) reads the `data-trigger` attribute to toggle tooltips
 * on hover/click and to keep keyboard focus working. It has NO Divi dependency,
 * so it boots cleanly under PHPUnit (unlike the module abstracts).
 *
 * @since   4.3.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Shared\Modules\Media\Image_Hotspots;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use function esc_attr;
use function esc_html;
use function esc_html__;
use function esc_url;
use function floatval;
use function in_array;
use function max;
use function min;
use function sprintf;
use function wp_kses_post;

/**
 * Image Hotspots helper.
 *
 * @since 4.3.0
 */
final class Image_Hotspots_Helper {

	/**
	 * Allowed trigger tokens (first = fallback).
	 *
	 * @since 4.3.0
	 *
	 * @var array<int, string>
	 */
	public const TRIGGERS = array( 'hover', 'click' );

	/**
	 * Allowed marker-type tokens (last = fallback).
	 *
	 * @since 4.3.0
	 *
	 * @var array<int, string>
	 */
	public const MARKER_TYPES = array( 'icon', 'number', 'dot' );

	/**
	 * Allowed tooltip-position tokens (first = fallback).
	 *
	 * @since 4.3.0
	 *
	 * @var array<int, string>
	 */
	public const TOOLTIP_POSITIONS = array( 'top', 'bottom', 'left', 'right' );

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
	 * Clamp a percent value into the 0..100 range.
	 *
	 * The value is cast through `floatval()` then bounded, so no raw user value
	 * ever reaches the inline `style` attribute — only a numeric percentage.
	 *
	 * @since 4.3.0
	 *
	 * @param mixed $value Raw position value.
	 *
	 * @return float
	 */
	public static function clamp_percent( $value ): float {
		return max( 0.0, min( 100.0, floatval( $value ) ) );
	}

	/**
	 * Build the `.squad-hotspots` canvas shell shared with the Divi 5 render.
	 *
	 * @since 4.3.0
	 *
	 * @param array<string, mixed> $config    Resolved config: image, imageAlt, trigger.
	 * @param string               $pins_html Rendered pin HTML.
	 *
	 * @return string
	 */
	public static function build_canvas( array $config, string $pins_html ): string {
		$trigger = self::validate( (string) ( $config['trigger'] ?? 'hover' ), self::TRIGGERS );
		$image   = esc_url( (string) ( $config['image'] ?? '' ) );
		$alt     = (string) ( $config['imageAlt'] ?? '' );

		if ( '' === $image ) {
			$media = sprintf(
				'<div class="squad-hotspots__placeholder">%s</div>',
				esc_html__( 'Select an image to place hotspots on.', 'squad-modules-for-divi' )
			);
		} else {
			$media = sprintf(
				'<img class="squad-hotspots__image" src="%1$s" alt="%2$s" loading="lazy" />',
				$image,
				esc_attr( $alt )
			);
		}

		return sprintf(
			'<div class="squad-hotspots" data-trigger="%1$s">%2$s<div class="squad-hotspots__pins">%3$s</div></div>',
			esc_attr( $trigger ),
			$media,
			$pins_html
		);
	}

	/**
	 * Build a single `.squad-hotspots__pin` (marker + tooltip).
	 *
	 * @since 4.3.0
	 *
	 * @param array<string, mixed> $pin Pin config: posX, posY, markerType, icon,
	 *                                  number, title, content, tooltipPosition.
	 *
	 * @return string
	 */
	public static function build_pin( array $pin ): string {
		$position = self::validate( (string) ( $pin['tooltipPosition'] ?? 'top' ), self::TOOLTIP_POSITIONS );
		$pos_x    = self::clamp_percent( $pin['posX'] ?? 0 );
		$pos_y    = self::clamp_percent( $pin['posY'] ?? 0 );

		$title   = (string) ( $pin['title'] ?? '' );
		$content = (string) ( $pin['content'] ?? '' );
		$aria    = '' !== $title ? $title : esc_html__( 'Hotspot', 'squad-modules-for-divi' );

		$marker = self::build_marker( $pin );

		$tooltip_inner = '';
		if ( '' !== $title ) {
			$tooltip_inner .= sprintf(
				'<span class="squad-hotspots__tooltip-title">%s</span>',
				esc_html( $title )
			);
		}
		if ( '' !== $content ) {
			$tooltip_inner .= sprintf(
				'<div class="squad-hotspots__tooltip-content">%s</div>',
				wp_kses_post( $content )
			);
		}

		return sprintf(
			'<div class="squad-hotspots__pin squad-hotspots__pin--%1$s" style="left:%2$s%%;top:%3$s%%" tabindex="0" role="button" aria-label="%4$s">'
			. '<span class="squad-hotspots__marker squad-hotspots__marker--%5$s">%6$s</span>'
			. '<div class="squad-hotspots__tooltip" role="tooltip">%7$s</div>'
			. '</div>',
			esc_attr( $position ),
			esc_attr( (string) $pos_x ),
			esc_attr( (string) $pos_y ),
			esc_attr( $aria ),
			esc_attr( self::validate( (string) ( $pin['markerType'] ?? 'dot' ), self::MARKER_TYPES ) ),
			$marker,
			$tooltip_inner
		);
	}

	/**
	 * Build the marker contents based on the pin's marker type.
	 *
	 * @since 4.3.0
	 *
	 * @param array<string, mixed> $pin Pin config.
	 *
	 * @return string
	 */
	private static function build_marker( array $pin ): string {
		$type = self::validate( (string) ( $pin['markerType'] ?? 'dot' ), self::MARKER_TYPES );

		switch ( $type ) {
			case 'icon':
				$icon = (string) ( $pin['icon'] ?? '' );
				if ( '' === $icon ) {
					break;
				}

				return sprintf(
					'<span class="et-pb-icon">%s</span>',
					esc_html( $icon )
				);

			case 'number':
				$number = (string) ( $pin['number'] ?? '' );
				if ( '' === $number ) {
					break;
				}

				return esc_html( $number );
		}

		// `dot`/default and empty fallbacks render an empty span (CSS draws the dot).
		return '';
	}
}
