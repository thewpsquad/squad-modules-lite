<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Image Reveal helper.
 *
 * Pure-PHP trigger/style/direction/easing allowlists, an easing mapper, a
 * viewport-threshold clamp, and a link-rel builder shared by the Divi 4 and
 * Divi 5 Image Reveal render paths. No Divi dependency — boots cleanly under
 * PHPUnit (unlike the module abstracts, which hit the Patchwork esc_js
 * DefinedTooEarly blocker).
 *
 * @since   4.1.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Shared\Modules\Creative\Image_Reveal;

use function esc_attr;
use function esc_url;
use function implode;
use function in_array;
use function is_numeric;
use function max;
use function min;
use function sprintf;
use function trim;

/**
 * Image Reveal helper.
 *
 * @since 4.1.0
 */
final class Reveal_Helper {

	/**
	 * Whether a trigger token is in the allowlist.
	 *
	 * Allowlist: scroll, hover.
	 *
	 * @since 4.1.0
	 *
	 * @param string $trigger Trigger token.
	 *
	 * @return bool
	 */
	public static function is_valid_trigger( string $trigger ): bool {
		return in_array( $trigger, array( 'scroll', 'hover' ), true );
	}

	/**
	 * Whether a reveal-style token is in the allowlist.
	 *
	 * Allowlist: overlay, clip.
	 *
	 * @since 4.1.0
	 *
	 * @param string $style Style token.
	 *
	 * @return bool
	 */
	public static function is_valid_style( string $style ): bool {
		return in_array( $style, array( 'overlay', 'clip' ), true );
	}

	/**
	 * Whether a direction token is in the allowlist.
	 *
	 * Allowlist: ltr, rtl, ttb, btt.
	 *
	 * @since 4.1.0
	 *
	 * @param string $direction Direction token.
	 *
	 * @return bool
	 */
	public static function is_valid_direction( string $direction ): bool {
		return in_array( $direction, array( 'ltr', 'rtl', 'ttb', 'btt' ), true );
	}

	/**
	 * Whether an easing token is in the allowlist.
	 *
	 * Allowlist: linear, ease, ease-in, ease-out, ease-in-out, smooth.
	 *
	 * @since 4.1.0
	 *
	 * @param string $easing Easing token.
	 *
	 * @return bool
	 */
	public static function is_valid_easing( string $easing ): bool {
		return in_array( $easing, array( 'linear', 'ease', 'ease-in', 'ease-out', 'ease-in-out', 'smooth' ), true );
	}

	/**
	 * Map an easing token to its CSS timing-function value.
	 *
	 * The five native CSS keywords map to themselves; `smooth` maps to a gentle
	 * symmetric cubic-bezier; any unknown token falls back to `ease-in-out`.
	 *
	 * @since 4.1.0
	 *
	 * @param string $easing Easing token.
	 *
	 * @return string CSS timing-function value.
	 */
	public static function easing_value( string $easing ): string {
		$map = array(
			'linear'      => 'linear',
			'ease'        => 'ease',
			'ease-in'     => 'ease-in',
			'ease-out'    => 'ease-out',
			'ease-in-out' => 'ease-in-out',
			'smooth'      => 'cubic-bezier(0.45, 0, 0.55, 1)',
		);

		return $map[ $easing ] ?? 'ease-in-out';
	}

	/**
	 * Clamp a raw viewport-threshold value into the 1..100 range.
	 *
	 * Non-numeric / empty / null input falls back to 50. Numeric input is cast
	 * to int then clamped. (Explicit is_numeric guard — never `intval(...) ||`,
	 * which would wrongly treat a legitimate small value path.)
	 *
	 * @since 4.1.0
	 *
	 * @param mixed $raw Raw value.
	 *
	 * @return int Threshold in 1..100.
	 */
	public static function clamp_threshold( $raw ): int {
		if ( ! is_numeric( $raw ) ) {
			return 50;
		}

		return (int) max( 1, min( 100, (int) $raw ) );
	}

	/**
	 * Build the rel attribute value for the optional link wrapper.
	 *
	 * @since 4.1.0
	 *
	 * @param string $new_window Whether the link opens in a new window ('on'/'off').
	 *
	 * @return string|null `noopener noreferrer` when new window, otherwise null.
	 */
	public static function build_rel( string $new_window ): ?string {
		return 'on' === $new_window ? 'noopener noreferrer' : null;
	}

	/**
	 * Build the `.squad-image-reveal` shell shared by the Divi 4 and Divi 5 renders.
	 *
	 * Returns '' when the image URL is empty. Overlay span emitted only for the
	 * overlay style; link wrapper + target/rel only when a link URL is present;
	 * all tokens validated against the allowlist before entering markup.
	 *
	 * @since 4.1.0
	 *
	 * @param array<string, mixed> $config Resolved config.
	 *
	 * @return string
	 */
	public static function build_shell( array $config ): string {
		$image = trim( (string) ( $config['image'] ?? '' ) );
		if ( '' === $image ) {
			return '';
		}

		$style   = self::is_valid_style( (string) ( $config['style'] ?? 'overlay' ) ) ? (string) $config['style'] : 'overlay';
		$dir     = self::is_valid_direction( (string) ( $config['direction'] ?? 'ltr' ) ) ? (string) $config['direction'] : 'ltr';
		$trigger = self::is_valid_trigger( (string) ( $config['trigger'] ?? 'scroll' ) ) ? (string) $config['trigger'] : 'scroll';
		$zoom_on = 'on' === (string) ( $config['zoom'] ?? 'off' );

		$classes = array(
			'squad-image-reveal',
			"squad-image-reveal--$style",
			"squad-image-reveal--$dir",
			"squad-image-reveal--trigger-$trigger",
		);
		if ( $zoom_on ) {
			$classes[] = 'squad-image-reveal--zoom';
		}

		$alt   = (string) ( $config['alt'] ?? '' );
		$title = (string) ( $config['title'] ?? '' );

		$img = sprintf(
			'<img class="squad-image-reveal__img" src="%1$s" alt="%2$s"%3$s />',
			esc_url( $image ),
			esc_attr( $alt ),
			'' !== $title ? sprintf( ' title="%s"', esc_attr( $title ) ) : ''
		);

		$overlay = 'overlay' === $style ? '<span class="squad-image-reveal__overlay"></span>' : '';

		$frame = sprintf( '<span class="squad-image-reveal__frame">%s%s</span>', $img, $overlay );

		$link_url = trim( (string) ( $config['link_url'] ?? '' ) );
		if ( '' !== $link_url ) {
			$new_window = (string) ( $config['link_new_window'] ?? 'off' );
			$rel        = self::build_rel( $new_window );
			$inner      = sprintf(
				'<a href="%1$s"%2$s%3$s>%4$s</a>',
				esc_url( $link_url ),
				'on' === $new_window ? ' target="_blank"' : '',
				null !== $rel ? sprintf( ' rel="%s"', esc_attr( $rel ) ) : '',
				$frame
			);
		} else {
			$inner = $frame;
		}

		$threshold = self::clamp_threshold( $config['threshold'] ?? 50 );

		return sprintf(
			'<figure class="%1$s" data-trigger="%2$s" data-threshold="%3$s">%4$s</figure>',
			esc_attr( implode( ' ', $classes ) ),
			esc_attr( $trigger ),
			esc_attr( (string) $threshold ),
			$inner
		);
	}
}
