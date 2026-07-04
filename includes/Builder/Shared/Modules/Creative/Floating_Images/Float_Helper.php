<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Floating Images helper.
 *
 * Pure-PHP motion/easing allowlists, easing + keyframe mappers, and the
 * rel-attribute builder shared by the Divi 4 and Divi 5 Floating Images render
 * paths. No Divi dependency — boots cleanly under PHPUnit (unlike the module
 * abstracts).
 *
 * @since   4.1.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Shared\Modules\Creative\Floating_Images;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use function implode;
use function in_array;

/**
 * Floating Images helper.
 *
 * @since 4.1.0
 */
final class Float_Helper {

	/**
	 * Whether a motion token is in the allowlist.
	 *
	 * Allowlist: up-down, left-right, diagonal, rotate.
	 *
	 * @since 4.1.0
	 *
	 * @param string $motion Motion token.
	 *
	 * @return bool
	 */
	public static function is_valid_motion( string $motion ): bool {
		return in_array( $motion, array( 'up-down', 'left-right', 'diagonal', 'rotate' ), true );
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
	 * Map a motion token to its CSS @keyframes name.
	 *
	 * Canonical motion → keyframe mapping, mirrored by the static SCSS that
	 * binds each `.squad-floating__item--<motion>` modifier to its animation.
	 * Unknown tokens fall back to the up-down keyframe.
	 *
	 * @since 4.1.0
	 *
	 * @param string $motion Motion token.
	 *
	 * @return string Keyframe name.
	 */
	public static function keyframe_name( string $motion ): string {
		$map = array(
			'up-down'    => 'squad-float-up-down',
			'left-right' => 'squad-float-left-right',
			'diagonal'   => 'squad-float-diagonal',
			'rotate'     => 'squad-float-rotate',
		);

		return $map[ $motion ] ?? 'squad-float-up-down';
	}

	/**
	 * Build the rel attribute value for a link.
	 *
	 * Returns `noopener noreferrer` when `$new_window` is true (prevents
	 * tab-napping on target="_blank"). Returns empty string otherwise.
	 *
	 * @since 4.1.0
	 *
	 * @param bool $new_window Whether the link opens in a new tab.
	 *
	 * @return string Space-joined rel tokens, or '' when no tokens apply.
	 */
	public static function build_rel( bool $new_window ): string {
		if ( ! $new_window ) {
			return '';
		}

		return implode( ' ', array( 'noopener', 'noreferrer' ) );
	}
}
