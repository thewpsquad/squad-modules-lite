<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Inline Content helper.
 *
 * Pure-PHP type/align/valign allowlists and rel-attribute builder shared by
 * the Divi 4 and Divi 5 Inline Content render paths. No Divi dependency —
 * boots cleanly under PHPUnit (unlike the module abstracts).
 *
 * @since   4.1.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Shared\Modules\Creative\Inline_Content;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use function implode;
use function in_array;

/**
 * Inline Content helper.
 *
 * @since 4.1.0
 */
final class Inline_Helper {

	/**
	 * Whether a content type token is in the allowlist.
	 *
	 * Allowlist: text, icon, image, button, divider.
	 *
	 * @since 4.1.0
	 *
	 * @param string $type Content type token.
	 *
	 * @return bool
	 */
	public static function is_valid_type( string $type ): bool {
		return in_array( $type, array( 'text', 'icon', 'image', 'button', 'divider' ), true );
	}

	/**
	 * Whether a horizontal alignment token is in the allowlist.
	 *
	 * Allowlist: left, center, right, between.
	 *
	 * @since 4.1.0
	 *
	 * @param string $align Alignment token.
	 *
	 * @return bool
	 */
	public static function is_valid_align( string $align ): bool {
		return in_array( $align, array( 'left', 'center', 'right', 'between' ), true );
	}

	/**
	 * Whether a vertical alignment token is in the allowlist.
	 *
	 * Allowlist: top, center, baseline, bottom.
	 *
	 * @since 4.1.0
	 *
	 * @param string $valign Vertical alignment token.
	 *
	 * @return bool
	 */
	public static function is_valid_valign( string $valign ): bool {
		return in_array( $valign, array( 'top', 'center', 'baseline', 'bottom' ), true );
	}

	/**
	 * Map a content_alignment value to its CSS justify-content equivalent.
	 *
	 * | align   | CSS value       |
	 * |---------|-----------------|
	 * | left    | flex-start      |
	 * | center  | center          |
	 * | right   | flex-end        |
	 * | between | space-between   |
	 * | unknown | flex-start      |
	 *
	 * @since 4.1.0
	 *
	 * @param string $align Alignment token.
	 *
	 * @return string CSS justify-content value.
	 */
	public static function align_value( string $align ): string {
		$map = array(
			'left'    => 'flex-start',
			'center'  => 'center',
			'right'   => 'flex-end',
			'between' => 'space-between',
		);

		return $map[ $align ] ?? 'flex-start';
	}

	/**
	 * Map a vertical_align value to its CSS align-items equivalent.
	 *
	 * | valign   | CSS value   |
	 * |----------|-------------|
	 * | top      | flex-start  |
	 * | center   | center      |
	 * | baseline | baseline    |
	 * | bottom   | flex-end    |
	 * | unknown  | center      |
	 *
	 * @since 4.1.0
	 *
	 * @param string $valign Vertical alignment token.
	 *
	 * @return string CSS align-items value.
	 */
	public static function valign_value( string $valign ): string {
		$map = array(
			'top'      => 'flex-start',
			'center'   => 'center',
			'baseline' => 'baseline',
			'bottom'   => 'flex-end',
		);

		return $map[ $valign ] ?? 'center';
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
