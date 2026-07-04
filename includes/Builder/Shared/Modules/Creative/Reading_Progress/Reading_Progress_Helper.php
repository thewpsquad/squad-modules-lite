<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Reading Progress helper.
 *
 * Pure-PHP shell builder shared by the Divi 4 and Divi 5 Reading Progress render
 * paths. It emits a fixed scroll-progress indicator — a top/bottom bar OR a corner
 * circular ring — carrying its target selector and behaviour flags as data
 * attributes, so the frontend engine (`reading-progress.ts`) just wires the scroll
 * listener. It has NO Divi dependency, so it boots cleanly under PHPUnit (unlike
 * the module abstracts).
 *
 * @since   4.3.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Shared\Modules\Creative\Reading_Progress;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use function esc_attr;
use function in_array;
use function max;
use function min;
use function preg_match;
use function sprintf;
use function trim;

/**
 * Reading Progress helper.
 *
 * @since 4.3.0
 */
final class Reading_Progress_Helper {

	/**
	 * Circle circumference for the circular ring (2·π·r, r = 20).
	 *
	 * Used for both `stroke-dasharray` and the initial `stroke-dashoffset`.
	 *
	 * @since 4.3.0
	 *
	 * @var string
	 */
	public const CIRCUMFERENCE = '125.66';

	/**
	 * Valid bar styles.
	 *
	 * @since 4.3.0
	 *
	 * @var array<int, string>
	 */
	public const STYLES = array( 'bar', 'circular' );

	/**
	 * Valid positions for the `bar` style.
	 *
	 * @since 4.3.0
	 *
	 * @var array<int, string>
	 */
	public const BAR_POSITIONS = array( 'top', 'bottom' );

	/**
	 * Valid positions (corners) for the `circular` style.
	 *
	 * @since 4.3.0
	 *
	 * @var array<int, string>
	 */
	public const CIRCULAR_POSITIONS = array( 'bottom-right', 'bottom-left', 'top-right', 'top-left' );

	/**
	 * Build the `.squad-reading-progress` shell shared with the Divi 5 render.
	 *
	 * Emits either a fixed top/bottom bar or a corner circular ring with an
	 * accessible `role="progressbar"` + ARIA value attributes. The frontend
	 * engine reads `data-target` / `data-hide-complete` and updates the fill.
	 *
	 * @since 4.3.0
	 *
	 * @param array<string, mixed> $raw Resolved config. Keys: barStyle, position,
	 *                                  targetSelector, barColor, useGradient,
	 *                                  gradientEnd, barHeight (int), hideOnComplete,
	 *                                  showPercent.
	 *
	 * @return string
	 */
	public static function build_shell( array $raw ): string {
		$bar_style = (string) ( $raw['barStyle'] ?? 'bar' );
		if ( ! in_array( $bar_style, self::STYLES, true ) ) {
			$bar_style = 'bar';
		}

		$position        = (string) ( $raw['position'] ?? '' );
		$target_selector = trim( (string) ( $raw['targetSelector'] ?? '' ) );
		$bar_color       = self::sanitize_color( (string) ( $raw['barColor'] ?? '' ) );
		$use_gradient    = 'on' === (string) ( $raw['useGradient'] ?? 'off' );
		$gradient_end    = self::sanitize_color( (string) ( $raw['gradientEnd'] ?? '' ) );
		$bar_height      = (int) max( 2, min( 12, (int) ( $raw['barHeight'] ?? 4 ) ) );
		$hide_complete   = 'on' === (string) ( $raw['hideOnComplete'] ?? 'off' ) ? 'on' : 'off';
		$show_percent    = 'on' === (string) ( $raw['showPercent'] ?? 'off' );

		if ( 'circular' === $bar_style ) {
			return self::build_circular( $position, $target_selector, $bar_color, $hide_complete, $show_percent );
		}

		return self::build_bar( $position, $target_selector, $bar_color, $use_gradient, $gradient_end, $bar_height, $hide_complete );
	}

	/**
	 * Build the top/bottom bar markup.
	 *
	 * @since 4.3.0
	 *
	 * @param string $position        Raw position value (validated to top|bottom).
	 * @param string $target_selector CSS selector for the tracked area.
	 * @param string $bar_color       Sanitized fill color (may be empty).
	 * @param bool   $use_gradient    Whether to use a linear gradient fill.
	 * @param string $gradient_end    Sanitized gradient end color (may be empty).
	 * @param int    $bar_height      Bar thickness in pixels (2–12).
	 * @param string $hide_complete   on|off — fade out when complete.
	 *
	 * @return string
	 */
	private static function build_bar( string $position, string $target_selector, string $bar_color, bool $use_gradient, string $gradient_end, int $bar_height, string $hide_complete ): string {
		if ( ! in_array( $position, self::BAR_POSITIONS, true ) ) {
			$position = 'top';
		}

		$fill_style = '';
		if ( $use_gradient && '' !== $bar_color && '' !== $gradient_end ) {
			$fill_style = sprintf( 'background:linear-gradient(90deg,%1$s,%2$s);', $bar_color, $gradient_end );
		} elseif ( '' !== $bar_color ) {
			$fill_style = sprintf( 'background:%s;', $bar_color );
		}

		return sprintf(
			'<div class="squad-reading-progress squad-reading-progress--bar squad-reading-progress--%1$s" data-target="%2$s" data-hide-complete="%3$s" role="progressbar" aria-label="Reading progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" style="height:%4$dpx"><div class="squad-reading-progress__bar"%5$s></div></div>',
			esc_attr( $position ),
			esc_attr( $target_selector ),
			esc_attr( $hide_complete ),
			$bar_height,
			'' !== $fill_style ? sprintf( ' style="%s"', esc_attr( $fill_style ) ) : ''
		);
	}

	/**
	 * Build the corner circular ring markup.
	 *
	 * The wrapper is a fixed 48px box; `barHeight` does not affect the ring.
	 * Gradient is intentionally NOT applied to the SVG stroke — the ring always
	 * uses the solid `barColor` only.
	 *
	 * @since 4.3.0
	 *
	 * @param string $position        Raw position value (validated to a corner).
	 * @param string $target_selector CSS selector for the tracked area.
	 * @param string $bar_color       Sanitized stroke color (may be empty).
	 * @param string $hide_complete   on|off — fade out when complete.
	 * @param bool   $show_percent    Whether to render the percent label.
	 *
	 * @return string
	 */
	private static function build_circular( string $position, string $target_selector, string $bar_color, string $hide_complete, bool $show_percent ): string {
		if ( ! in_array( $position, self::CIRCULAR_POSITIONS, true ) ) {
			$position = 'bottom-right';
		}

		$stroke_attr = '' !== $bar_color ? sprintf( ' stroke="%s"', esc_attr( $bar_color ) ) : '';

		$percent = $show_percent
			? '<span class="squad-reading-progress__percent">0%</span>'
			: '<span class="squad-reading-progress__percent" hidden>0%</span>';

		return sprintf(
			'<div class="squad-reading-progress squad-reading-progress--circular squad-reading-progress--%1$s" data-target="%2$s" data-hide-complete="%3$s" role="progressbar" aria-label="Reading progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" style="width:48px;height:48px"><svg class="squad-reading-progress__ring" viewBox="0 0 44 44" aria-hidden="true"><circle class="squad-reading-progress__track" cx="22" cy="22" r="20" fill="none" stroke-width="4"></circle><circle class="squad-reading-progress__fill" cx="22" cy="22" r="20" fill="none" stroke-width="4"%4$s stroke-dasharray="%5$s" stroke-dashoffset="%5$s" transform="rotate(-90 22 22)"></circle></svg>%6$s</div>',
			esc_attr( $position ),
			esc_attr( $target_selector ),
			esc_attr( $hide_complete ),
			$stroke_attr,
			self::CIRCUMFERENCE,
			$percent
		);
	}

	/**
	 * Sanitize a user-supplied color before interpolating it into inline CSS.
	 *
	 * Allowlists `#hex` (3/4/6/8), `rgb()` / `rgba()`, and `var(--token)`. Any
	 * value that does not match is dropped (returns an empty string) so raw user
	 * input never reaches an inline `style` attribute.
	 *
	 * @since 4.3.0
	 *
	 * @param string $color Raw color value.
	 *
	 * @return string Sanitized color, or '' when not allowlisted.
	 */
	private static function sanitize_color( string $color ): string {
		$color = trim( $color );
		if ( '' === $color ) {
			return '';
		}

		// Hex: #rgb, #rgba, #rrggbb, #rrggbbaa.
		if ( 1 === preg_match( '/^#([0-9a-fA-F]{3,4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $color ) ) {
			return $color;
		}

		// rgb() / rgba().
		if ( 1 === preg_match( '/^rgba?\(\s*[0-9.%,\s]+\)$/', $color ) ) {
			return $color;
		}

		// CSS custom property: var(--token) with an optional fallback.
		if ( 1 === preg_match( '/^var\(\s*--[A-Za-z0-9_-]+\s*(,[^()]*)?\)$/', $color ) ) {
			return $color;
		}

		return '';
	}
}
