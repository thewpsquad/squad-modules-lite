<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Countdown Timer helper.
 *
 * Pure-PHP shell builder + mode/separator/timezone allowlists shared by the
 * Divi 4 and Divi 5 Countdown Timer render paths. It emits the `.squad-countdown`
 * shell carrying the data-* config the frontend engine (`countdown-timer.ts`)
 * reads to tick the timer client-side. It has NO Divi dependency, so it boots
 * cleanly under PHPUnit (unlike the module abstracts).
 *
 * @since   4.3.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Shared\Modules\Creative\Countdown_Timer;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use function esc_attr;
use function esc_html;
use function esc_url;
use function implode;
use function in_array;
use function sprintf;

/**
 * Countdown Timer helper.
 *
 * @since 4.3.0
 */
final class Countdown_Helper {

	/**
	 * The countdown units, in display order.
	 *
	 * @since 4.3.0
	 *
	 * @var array<int, string>
	 */
	public const UNITS = array( 'days', 'hours', 'minutes', 'seconds' );

	/**
	 * Whether a countdown mode token is in the allowlist.
	 *
	 * @since 4.3.0
	 *
	 * @param string $mode fixed|evergreen.
	 *
	 * @return bool
	 */
	public static function is_valid_mode( string $mode ): bool {
		return in_array( $mode, array( 'fixed', 'evergreen' ), true );
	}

	/**
	 * Whether a separator token is in the allowlist.
	 *
	 * @since 4.3.0
	 *
	 * @param string $separator colon|none|slash.
	 *
	 * @return bool
	 */
	public static function is_valid_separator( string $separator ): bool {
		return in_array( $separator, array( 'colon', 'none', 'slash' ), true );
	}

	/**
	 * Map a separator key to its character.
	 *
	 * @since 4.3.0
	 *
	 * @param string $key colon|none|slash.
	 *
	 * @return string
	 */
	public static function separator_char( string $key ): string {
		switch ( $key ) {
			case 'none':
				return '';
			case 'slash':
				return '/';
			case 'colon':
			default:
				return ':';
		}
	}

	/**
	 * Sanitize a timezone token to the allowlist.
	 *
	 * @since 4.3.0
	 *
	 * @param string $timezone site|visitor.
	 *
	 * @return string
	 */
	public static function sanitize_timezone( string $timezone ): string {
		return in_array( $timezone, array( 'site', 'visitor' ), true ) ? $timezone : 'site';
	}

	/**
	 * Sanitize an on-expiry token to the allowlist.
	 *
	 * @since 4.3.0
	 *
	 * @param string $on_expiry message|hide|redirect.
	 *
	 * @return string
	 */
	public static function sanitize_on_expiry( string $on_expiry ): string {
		return in_array( $on_expiry, array( 'message', 'hide', 'redirect' ), true ) ? $on_expiry : 'message';
	}

	/**
	 * Build the `.squad-countdown` shell shared with the Divi 5 render.
	 *
	 * Emits the data-* config the frontend engine reads plus one
	 * `.squad-countdown__unit` per ENABLED unit (with number + label), optional
	 * separators, and a trailing hidden message when `on_expiry` is `message`.
	 *
	 * @since 4.3.0
	 *
	 * @param array<string, mixed> $config Resolved config: mode, target, duration,
	 *                                     timezone, on_expiry, message, redirect,
	 *                                     separator (key).
	 * @param array<string, mixed> $units  Per-unit config keyed by unit name, each
	 *                                     with `enabled` (bool) and `label` (string).
	 *
	 * @return string
	 */
	public static function build_shell( array $config, array $units ): string {
		$mode      = self::is_valid_mode( (string) ( $config['mode'] ?? 'fixed' ) ) ? (string) $config['mode'] : 'fixed';
		$timezone  = self::sanitize_timezone( (string) ( $config['timezone'] ?? 'site' ) );
		$on_expiry = self::sanitize_on_expiry( (string) ( $config['on_expiry'] ?? 'message' ) );
		$sep_key   = self::is_valid_separator( (string) ( $config['separator'] ?? 'colon' ) ) ? (string) $config['separator'] : 'colon';
		$sep_char  = self::separator_char( $sep_key );

		$target   = (string) ( $config['target'] ?? '' );
		$duration = (string) ( $config['duration'] ?? '0' );
		$redirect = 'redirect' === $on_expiry ? esc_url( (string) ( $config['redirect'] ?? '' ) ) : '';

		// Build the enabled units markup.
		$units_html  = '';
		$first       = true;
		$has_visible = false;
		foreach ( self::UNITS as $unit ) {
			$unit_conf = $units[ $unit ] ?? array();
			if ( false === ( $unit_conf['enabled'] ?? false ) ) {
				continue;
			}

			// Insert a separator before every unit except the first.
			if ( ! $first && '' !== $sep_char ) {
				$units_html .= sprintf(
					'<span class="squad-countdown__separator">%s</span>',
					esc_html( $sep_char )
				);
			}
			$first       = false;
			$has_visible = true;

			$label = (string) ( $unit_conf['label'] ?? '' );

			$units_html .= sprintf(
				'<div class="squad-countdown__unit squad-countdown__unit--%1$s"><span class="squad-countdown__number" data-unit="%1$s">00</span><span class="squad-countdown__label">%2$s</span></div>',
				esc_attr( $unit ),
				esc_html( $label )
			);
		}

		// Avoid a leading separator if nothing visible (defensive).
		if ( ! $has_visible ) {
			$units_html = '';
		}

		$message_html = '';
		if ( 'message' === $on_expiry ) {
			$message_html = sprintf(
				'<div class="squad-countdown__message" hidden>%s</div>',
				esc_html( (string) ( $config['message'] ?? '' ) )
			);
		}

		return sprintf(
			'<div class="%1$s" data-mode="%2$s" data-target="%3$s" data-duration="%4$s" data-timezone="%5$s" data-on-expiry="%6$s" data-redirect="%7$s"><div class="squad-countdown__units">%8$s</div>%9$s</div>',
			esc_attr( implode( ' ', array( 'squad-countdown' ) ) ),
			esc_attr( $mode ),
			esc_attr( $target ),
			esc_attr( $duration ),
			esc_attr( $timezone ),
			esc_attr( $on_expiry ),
			esc_attr( $redirect ),
			$units_html,
			$message_html
		);
	}
}
