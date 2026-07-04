<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Number Counter helper.
 *
 * Pure-PHP number formatting + easing/separator allowlists shared by the Divi 4
 * and Divi 5 Number Counter render paths. It mirrors the frontend JS
 * (`number-counter.ts`) `formatNumber` exactly so it can be the unit-tested
 * contract. It has NO Divi dependency, so it boots cleanly under PHPUnit
 * (unlike the module abstracts).
 *
 * @since   4.0.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Shared\Modules\Creative\Number_Counter;

use function abs;
use function absint;
use function esc_attr;
use function esc_html;
use function explode;
use function implode;
use function in_array;
use function max;
use function min;
use function number_format;
use function sprintf;
use function strlen;
use function strrev;
use function substr;

/**
 * Number Counter helper.
 *
 * @since 4.0.0
 */
final class Counter_Helper {

	/**
	 * Format a numeric value into the displayed string.
	 *
	 * Fixes the value to `$decimals` places, groups the integer part in 3s with
	 * `$thousands_sep`, and joins the fraction with `$decimal_sep`. Negative
	 * values keep their sign on the digits; a value that rounds to zero is
	 * never rendered with a sign. Mirrors the JS `formatNumber` exactly.
	 *
	 * @since 4.0.0
	 *
	 * @param float  $value        Raw numeric value.
	 * @param int    $decimals     Decimal places (assumed already clamped 0–4).
	 * @param string $thousands_sep Thousands separator char (may be empty).
	 * @param string $decimal_sep  Decimal separator char.
	 *
	 * @return string
	 */
	public static function format_number( float $value, int $decimals, string $thousands_sep, string $decimal_sep ): string {
		// Round and stringify to a fixed number of decimals with a dot separator,
		// then split so grouping/joining is done manually (matches JS toFixed()).
		$fixed = number_format( abs( $value ), $decimals, '.', '' );

		$parts    = explode( '.', $fixed );
		$integer  = $parts[0];
		$fraction = $parts[1] ?? '';

		// Group the integer part into 3s from the right.
		if ( '' !== $thousands_sep ) {
			$reversed = strrev( $integer );
			$chunks   = array();
			for ( $i = 0, $len = strlen( $reversed ); $i < $len; $i += 3 ) {
				$chunks[] = substr( $reversed, $i, 3 );
			}
			$integer = strrev( implode( strrev( $thousands_sep ), $chunks ) );
		}

		$result = $integer;
		if ( $decimals > 0 ) {
			$result .= $decimal_sep . $fraction;
		}

		// Only prefix '-' when the formatted result is non-zero.
		$is_zero = (float) $fixed === 0.0;
		if ( $value < 0 && ! $is_zero ) {
			$result = '-' . $result;
		}

		return $result;
	}

	/**
	 * Whether an easing token is in the allowlist.
	 *
	 * @since 4.0.0
	 *
	 * @param string $easing Easing token.
	 *
	 * @return bool
	 */
	public static function is_valid_easing( string $easing ): bool {
		return in_array( $easing, array( 'linear', 'ease-in', 'ease-out', 'ease-in-out' ), true );
	}

	/**
	 * Map a thousands-separator key to its character.
	 *
	 * @since 4.0.0
	 *
	 * @param string $key none|comma|dot|space.
	 *
	 * @return string
	 */
	public static function separator_char( string $key ): string {
		switch ( $key ) {
			case 'none':
				return '';
			case 'dot':
				return '.';
			case 'space':
				return ' ';
			case 'comma':
			default:
				return ',';
		}
	}

	/**
	 * Map a decimal-separator key to its character.
	 *
	 * @since 4.0.0
	 *
	 * @param string $key dot|comma.
	 *
	 * @return string
	 */
	public static function decimal_char( string $key ): string {
		return 'comma' === $key ? ',' : '.';
	}

	/**
	 * Build the `.squad-counter` shell shared with the Divi 5 render.
	 *
	 * Emits the formatted END value (no-JS/SEO sees the real final number) plus
	 * the data-* config the frontend engine reads.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, string> $config         Resolved data-* config (start/end/duration/easing/separator/decimal_sep/decimals).
	 * @param string                $use_media      none|icon|image.
	 * @param string                $media_position above|left|right.
	 * @param string                $title          Title text.
	 * @param string                $title_position above|below.
	 * @param string                $prefix         Prefix text.
	 * @param string                $suffix         Suffix text.
	 * @param string                $media_html     Pre-rendered, escaped media markup (may be empty).
	 *
	 * @return string
	 */
	public static function build_shell( array $config, string $use_media, string $media_position, string $title, string $title_position, string $prefix, string $suffix, string $media_html ): string {
		$has_media      = in_array( $use_media, array( 'icon', 'image' ), true ) && '' !== $media_html;
		$media_position = in_array( $media_position, array( 'above', 'left', 'right' ), true ) ? $media_position : 'above';
		$title_position = in_array( $title_position, array( 'above', 'below' ), true ) ? $title_position : 'below';

		$decimals = max( 0, min( 4, absint( $config['decimals'] ) ) );

		$classes = array( 'squad-counter' );
		if ( $has_media ) {
			$classes[] = 'squad-counter--icon-' . $media_position;
		}
		$classes[] = 'squad-counter--title-' . $title_position;

		// Formatted END value for no-JS / SEO.
		$end_formatted = self::format_number(
			(float) $config['end'],
			$decimals,
			$config['separator'],
			$config['decimal_sep']
		);

		$prefix_html = '' !== $prefix ? sprintf( '<span class="squad-counter__prefix">%s</span>', esc_html( $prefix ) ) : '';
		$suffix_html = '' !== $suffix ? sprintf( '<span class="squad-counter__suffix">%s</span>', esc_html( $suffix ) ) : '';
		$title_html  = '' !== $title ? sprintf( '<div class="squad-counter__title">%s</div>', esc_html( $title ) ) : '';

		$number_wrap = sprintf(
			'<div class="squad-counter__number-wrap">%1$s<span class="squad-counter__number">%2$s</span>%3$s</div>',
			$prefix_html,
			esc_html( $end_formatted ),
			$suffix_html
		);

		// Title above/below ordering.
		$body = 'above' === $title_position ? $title_html . $number_wrap : $number_wrap . $title_html;

		return sprintf(
			'<div class="%1$s" data-start="%2$s" data-end="%3$s" data-duration="%4$s" data-easing="%5$s" data-separator="%6$s" data-decimal-sep="%7$s" data-decimals="%8$s">%9$s%10$s</div>',
			esc_attr( implode( ' ', $classes ) ),
			esc_attr( $config['start'] ),
			esc_attr( $config['end'] ),
			esc_attr( $config['duration'] ),
			esc_attr( $config['easing'] ),
			esc_attr( $config['separator'] ),
			esc_attr( $config['decimal_sep'] ),
			esc_attr( (string) $decimals ),
			$has_media ? $media_html : '',
			$body
		);
	}
}
