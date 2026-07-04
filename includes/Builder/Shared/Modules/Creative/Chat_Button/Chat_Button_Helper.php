<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Chat Button helper.
 *
 * Pure-PHP markup builder shared by the Divi 4 and Divi 5 Chat Button render
 * paths. It emits the floating `.squad-chat-button` launcher (toggle + greeting
 * + expandable panel) and the per-channel deep-link `<a>` markup so output is
 * byte-identical across builders. GDPR-friendly: every channel is a pure deep
 * link (wa.me / t.me / m.me / tel: / mailto: / custom URL) with NO third-party
 * script. A tiny frontend engine (`chat-button.ts`) reads the `data-schedule`
 * attributes for optional online-hours handling. It has NO Divi dependency, so
 * it boots cleanly under PHPUnit (unlike the module abstracts).
 *
 * @since   4.3.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Shared\Modules\Creative\Chat_Button;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use function esc_attr;
use function esc_html;
use function esc_html__;
use function esc_url;
use function in_array;
use function is_email;
use function ltrim;
use function preg_replace;
use function rawurlencode;
use function sprintf;
use function substr;
use function trim;

/**
 * Chat Button helper.
 *
 * @since 4.3.0
 */
final class Chat_Button_Helper {

	/**
	 * Allowed widget position tokens (first = fallback).
	 *
	 * @since 4.3.0
	 *
	 * @var array<int, string>
	 */
	public const POSITIONS = array( 'bottom-right', 'bottom-left', 'top-right', 'top-left' );

	/**
	 * Allowed channel type tokens (first = fallback).
	 *
	 * @since 4.3.0
	 *
	 * @var array<int, string>
	 */
	public const CHANNEL_TYPES = array( 'whatsapp', 'telegram', 'messenger', 'phone', 'email', 'custom' );

	/**
	 * Allowed URL protocols for channel deep links.
	 *
	 * @since 4.3.0
	 *
	 * @var array<int, string>
	 */
	public const LINK_PROTOCOLS = array( 'https', 'http', 'tel', 'mailto' );

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
	 * Build the floating `.squad-chat-button` launcher shared with both builders.
	 *
	 * @since 4.3.0
	 *
	 * @param array<string, mixed> $config        Resolved config: position, toggleIcon,
	 *                                            toggleLabel, headerTitle, greeting,
	 *                                            scheduleEnabled, scheduleStart, scheduleEnd.
	 * @param string               $channels_html Rendered child channel HTML.
	 *
	 * @return string
	 */
	public static function build_widget( array $config, string $channels_html ): string {
		$position = self::validate( (string) ( $config['position'] ?? 'bottom-right' ), self::POSITIONS );

		$toggle_icon  = (string) ( $config['toggleIcon'] ?? '' );
		$toggle_label = (string) ( $config['toggleLabel'] ?? '' );
		if ( '' === trim( $toggle_label ) ) {
			$toggle_label = esc_html__( 'Open chat', 'squad-modules-for-divi' );
		}

		$header_title = (string) ( $config['headerTitle'] ?? '' );
		$greeting     = (string) ( $config['greeting'] ?? '' );

		$schedule_enabled = 'on' === (string) ( $config['scheduleEnabled'] ?? 'off' ) ? 'on' : 'off';

		$schedule_start = (int) ( $config['scheduleStart'] ?? 9 );
		$schedule_end   = (int) ( $config['scheduleEnd'] ?? 17 );
		$schedule_start = self::clamp_hour( $schedule_start );
		$schedule_end   = self::clamp_hour( $schedule_end );

		$panel_inner = '';
		if ( '' !== trim( $header_title ) ) {
			$panel_inner .= sprintf( '<div class="squad-chat-button__header">%s</div>', esc_html( $header_title ) );
		}
		if ( '' !== trim( $greeting ) ) {
			$panel_inner .= sprintf( '<div class="squad-chat-button__greeting">%s</div>', esc_html( $greeting ) );
		}
		$panel_inner .= sprintf( '<div class="squad-chat-button__channels">%s</div>', $channels_html );

		$toggle_glyph = '' !== trim( $toggle_icon )
			? sprintf( '<span class="squad-chat-button__toggle-icon et-pb-icon">%s</span>', esc_html( $toggle_icon ) )
			: self::default_toggle_svg();

		return sprintf(
			'<div class="squad-chat-button squad-chat-button--%1$s" data-schedule="%2$s" data-start="%3$s" data-end="%4$s">'
			. '<div class="squad-chat-button__panel" hidden>%5$s</div>'
			. '<button type="button" class="squad-chat-button__toggle" aria-expanded="false" aria-label="%6$s">%7$s</button>'
			. '</div>',
			esc_attr( $position ),
			esc_attr( $schedule_enabled ),
			esc_attr( (string) $schedule_start ),
			esc_attr( (string) $schedule_end ),
			$panel_inner,
			esc_attr( $toggle_label ),
			$toggle_glyph
		);
	}

	/**
	 * Build a single channel node (deep-link `<a>`, or disabled `<span>`).
	 *
	 * @since 4.3.0
	 *
	 * @param array<string, mixed> $channel Channel config: type, identifier,
	 *                                      message, label, color.
	 *
	 * @return string
	 */
	public static function build_channel( array $channel ): string {
		$type       = self::validate( (string) ( $channel['type'] ?? 'custom' ), self::CHANNEL_TYPES );
		$identifier = (string) ( $channel['identifier'] ?? '' );
		$message    = (string) ( $channel['message'] ?? '' );

		$deep_link = self::build_deep_link( $type, $identifier, $message );

		$inner = self::build_channel_inner( $channel );

		$color_style = '';
		$color       = self::sanitize_color( (string) ( $channel['color'] ?? '' ) );
		if ( '' !== $color ) {
			$color_style = sprintf( ' style="--squad-channel-color:%s"', esc_attr( $color ) );
		}

		// No valid deep link → render a disabled <span> (never an empty link).
		if ( '' === $deep_link ) {
			return sprintf(
				'<span class="squad-chat-button__channel squad-chat-button__channel--%1$s is-disabled"%2$s>%3$s</span>',
				esc_attr( $type ),
				$color_style,
				$inner
			);
		}

		return sprintf(
			'<a class="squad-chat-button__channel squad-chat-button__channel--%1$s" href="%2$s" target="_blank" rel="noopener noreferrer"%3$s>%4$s</a>',
			esc_attr( $type ),
			$deep_link,
			$color_style,
			$inner
		);
	}

	/**
	 * Build the inner (icon + label) markup for a channel.
	 *
	 * @since 4.3.0
	 *
	 * @param array<string, mixed> $channel Channel config: type, label.
	 *
	 * @return string
	 */
	public static function build_channel_inner( array $channel ): string {
		$type  = self::validate( (string) ( $channel['type'] ?? 'custom' ), self::CHANNEL_TYPES );
		$label = (string) ( $channel['label'] ?? '' );
		if ( '' === trim( $label ) ) {
			$label = $type;
		}

		return sprintf(
			'<span class="squad-chat-button__channel-icon">%1$s</span><span class="squad-chat-button__channel-label">%2$s</span>',
			self::channel_icon_svg( $type ),
			esc_html( $label )
		);
	}

	/**
	 * Build the deep-link URL for a channel by type.
	 *
	 * Returns '' when the identifier is empty or invalid for the type, in which
	 * case the channel is rendered as a disabled span by {@see build_channel()}.
	 *
	 * @since 4.3.0
	 *
	 * @param string $type       Channel type token.
	 * @param string $identifier Raw identifier (phone / username / email / URL).
	 * @param string $message    Optional prefilled message (whatsapp / email only).
	 *
	 * @return string Escaped URL, or empty string.
	 */
	public static function build_deep_link( string $type, string $identifier, string $message ): string {
		$type       = self::validate( $type, self::CHANNEL_TYPES );
		$identifier = trim( $identifier );
		$message    = trim( $message );

		if ( '' === $identifier ) {
			return '';
		}

		switch ( $type ) {
			case 'whatsapp':
				$digits = (string) preg_replace( '/\D+/', '', $identifier );
				if ( '' === $digits ) {
					return '';
				}
				$url = 'https://wa.me/' . $digits;
				if ( '' !== $message ) {
					$url .= '?text=' . rawurlencode( $message );
				}

				return esc_url( $url, self::LINK_PROTOCOLS );

			case 'telegram':
				$username = ltrim( $identifier, '@' );
				if ( '' === $username ) {
					return '';
				}

				return esc_url( 'https://t.me/' . $username, self::LINK_PROTOCOLS );

			case 'messenger':
				$username = ltrim( $identifier, '@' );
				if ( '' === $username ) {
					return '';
				}

				return esc_url( 'https://m.me/' . $username, self::LINK_PROTOCOLS );

			case 'phone':
				$has_plus = '+' === substr( $identifier, 0, 1 );
				$digits   = (string) preg_replace( '/\D+/', '', $identifier );
				if ( '' === $digits ) {
					return '';
				}

				return esc_url( 'tel:' . ( $has_plus ? '+' : '' ) . $digits, self::LINK_PROTOCOLS );

			case 'email':
				if ( ! is_email( $identifier ) ) {
					return '';
				}
				$url = 'mailto:' . $identifier;
				if ( '' !== $message ) {
					$url .= '?body=' . rawurlencode( $message );
				}

				return esc_url( $url, self::LINK_PROTOCOLS );

			case 'custom':
			default:
				return esc_url( $identifier, self::LINK_PROTOCOLS );
		}
	}

	/**
	 * Clamp an hour value to the 0-23 range.
	 *
	 * @since 4.3.0
	 *
	 * @param int $hour Candidate hour.
	 *
	 * @return int
	 */
	private static function clamp_hour( int $hour ): int {
		if ( $hour < 0 ) {
			return 0;
		}
		if ( $hour > 23 ) {
			return 23;
		}

		return $hour;
	}

	/**
	 * Sanitize a CSS color / custom-property value.
	 *
	 * Strips characters that could break out of an inline `style=""` declaration.
	 *
	 * @since 4.3.0
	 *
	 * @param string $value Raw color value.
	 *
	 * @return string Sanitized value, or empty string.
	 */
	private static function sanitize_color( string $value ): string {
		$value = trim( $value );
		if ( '' === $value ) {
			return '';
		}

		return (string) preg_replace( '/[{};<>"\'\\\\]/', '', $value );
	}

	/**
	 * Built-in default chat-bubble toggle SVG (single-color, currentColor).
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	private static function default_toggle_svg(): string {
		return '<span class="squad-chat-button__toggle-icon" aria-hidden="true">'
			. '<svg viewBox="0 0 24 24" width="24" height="24" fill="none" xmlns="http://www.w3.org/2000/svg">'
			. '<path d="M12 3C7.03 3 3 6.58 3 11c0 2.18.97 4.15 2.56 5.6L4 21l4.6-1.5A10.6 10.6 0 0 0 12 19c4.97 0 9-3.58 9-8s-4.03-8-9-8Z" fill="currentColor"/>'
			. '</svg></span>';
	}

	/**
	 * Brand-neutral inline SVG icon for a channel type (uses currentColor).
	 *
	 * @since 4.3.0
	 *
	 * @param string $type Channel type token.
	 *
	 * @return string
	 */
	private static function channel_icon_svg( string $type ): string {
		$type = self::validate( $type, self::CHANNEL_TYPES );

		switch ( $type ) {
			case 'whatsapp':
				$path = '<path d="M12 3a9 9 0 0 0-7.7 13.6L3 21l4.5-1.2A9 9 0 1 0 12 3Zm4.4 12.3c-.2.5-1 1-1.5 1-.4 0-.9.1-3-1s-3.4-3.4-3.5-3.6c-.2-.2-1-1.3-1-2.5s.6-1.7.8-2c.2-.2.4-.3.6-.3h.5c.1 0 .3 0 .5.4l.7 1.7c0 .2.1.3 0 .5l-.4.5c-.2.2-.3.3-.1.6.1.3.6 1 1.3 1.6.9.8 1.6 1 1.9 1.2.2 0 .4 0 .5-.1l.7-.8c.2-.3.4-.2.6-.1l1.6.8c.3.1.4.2.5.3.1.2.1.7-.1 1.1Z" fill="currentColor"/>';
				break;

			case 'telegram':
				$path = '<path d="M21.9 4.3 18.7 19.4c-.2 1-.9 1.3-1.8.8l-4.9-3.6-2.4 2.3c-.3.3-.5.5-1 .5l.3-4.9L18 6.1c.4-.3-.1-.5-.6-.2L7 12.4l-4.7-1.5c-1-.3-1-1 .2-1.5l18.4-7.1c.9-.3 1.6.2 1 1.9-.0 0 0 0 0 0Z" fill="currentColor"/>';
				break;

			case 'messenger':
				$path = '<path d="M12 3C6.9 3 3 6.8 3 11.9c0 2.7 1.1 5 3 6.6V22l2.9-1.6c.8.2 1.6.3 2.5.3 5.1 0 9-3.8 9-8.9S17.1 3 12 3Zm.9 11.4-2.3-2.4-4.4 2.4 4.8-5.1 2.3 2.4L17.7 9l-4.8 5.4Z" fill="currentColor"/>';
				break;

			case 'phone':
				$path = '<path d="M6.6 10.8a14 14 0 0 0 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1A17 17 0 0 1 3 4c0-.6.5-1 1-1h3.4c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.4 0 .8-.2 1l-2.2 2.2Z" fill="currentColor"/>';
				break;

			case 'email':
				$path = '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2Zm8 7 8-5H4l8 5Zm0 2L4 8v10h16V8l-8 5Z" fill="currentColor"/>';
				break;

			case 'custom':
			default:
				$path = '<path d="M12 3C7 3 3 6.6 3 11c0 2.2 1 4.2 2.6 5.6L4 21l4.6-1.5c1 .3 2.2.5 3.4.5 5 0 9-3.6 9-8s-4-8-9-8Zm-3 9a1.2 1.2 0 1 1 0-2.4A1.2 1.2 0 0 1 9 12Zm3 0a1.2 1.2 0 1 1 0-2.4A1.2 1.2 0 0 1 12 12Zm3 0a1.2 1.2 0 1 1 0-2.4A1.2 1.2 0 0 1 15 12Z" fill="currentColor"/>';
				break;
		}

		return sprintf(
			'<svg viewBox="0 0 24 24" width="20" height="20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">%s</svg>',
			$path
		);
	}
}
