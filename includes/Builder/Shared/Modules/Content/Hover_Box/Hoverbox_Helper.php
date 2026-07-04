<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Hover Box helper.
 *
 * Pure-PHP fx/animation/valign allowlists and rel-attribute builder shared by
 * the Divi 4 and Divi 5 Hover Box render paths. No Divi dependency — boots
 * cleanly under PHPUnit (unlike the module abstracts).
 *
 * @since   4.2.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Shared\Modules\Content\Hover_Box;

use function esc_attr;
use function esc_html;
use function esc_url;
use function implode;
use function in_array;
use function preg_match;
use function sprintf;
use function trim;
use function wp_kses_post;

/**
 * Hover Box helper.
 *
 * @since 4.2.0
 */
final class Hoverbox_Helper {

	/**
	 * Whether an image hover-effect token is in the allowlist.
	 *
	 * Allowlist: none, zoom-in, zoom-out, grayscale, blur, rotate.
	 *
	 * @since 4.2.0
	 *
	 * @param string $fx Image hover effect token.
	 *
	 * @return bool
	 */
	public static function is_valid_fx( string $fx ): bool {
		return in_array( $fx, array( 'none', 'zoom-in', 'zoom-out', 'grayscale', 'blur', 'rotate' ), true );
	}

	/**
	 * Whether an overlay animation token is in the allowlist.
	 *
	 * Allowlist: fade, slide-up, slide-down, slide-left, slide-right, zoom.
	 *
	 * @since 4.2.0
	 *
	 * @param string $anim Overlay animation token.
	 *
	 * @return bool
	 */
	public static function is_valid_anim( string $anim ): bool {
		return in_array( $anim, array( 'fade', 'slide-up', 'slide-down', 'slide-left', 'slide-right', 'zoom' ), true );
	}

	/**
	 * Whether a content vertical-alignment token is in the allowlist.
	 *
	 * Allowlist: top, center, bottom.
	 *
	 * @since 4.2.0
	 *
	 * @param string $valign Vertical alignment token.
	 *
	 * @return bool
	 */
	public static function is_valid_valign( string $valign ): bool {
		return in_array( $valign, array( 'top', 'center', 'bottom' ), true );
	}

	/**
	 * Map a valign token to a CSS `align-items` / `justify-content` value.
	 *
	 * - top    → flex-start
	 * - center → center
	 * - bottom → flex-end
	 * - unknown → center (safe fallback)
	 *
	 * @since 4.2.0
	 *
	 * @param string $valign Vertical alignment token.
	 *
	 * @return string CSS flex value.
	 */
	public static function valign_value( string $valign ): string {
		$map = array(
			'top'    => 'flex-start',
			'center' => 'center',
			'bottom' => 'flex-end',
		);

		return $map[ $valign ] ?? 'center';
	}

	/**
	 * Build the rel attribute value for the overlay button link.
	 *
	 * Returns `noopener noreferrer` when `$new_window` is true (prevents
	 * tab-napping on target="_blank"). Returns empty string otherwise.
	 * No raw user-supplied rel tokens — this is the only rel source.
	 *
	 * @since 4.2.0
	 *
	 * @param bool $new_window Whether the button opens in a new tab.
	 *
	 * @return string Space-joined rel tokens, or '' when no tokens apply.
	 */
	public static function build_rel( bool $new_window ): string {
		if ( ! $new_window ) {
			return '';
		}

		return implode( ' ', array( 'noopener', 'noreferrer' ) );
	}

	/**
	 * Build the `.squad-hoverbox` shell shared with the Divi 5 render.
	 *
	 * SECURITY CONTRACT:
	 * - $image_src → esc_url; $image_alt → esc_attr.
	 * - $persistent_title, $title, $button_text → esc_html.
	 * - $content_body → wp_kses_post.
	 * - $button_url → esc_url.
	 * - $icon_html → caller passes pre-escaped markup via wp_kses_post.
	 * - $image_hover_fx, $overlay_animation, $content_valign → validated against allowlists before call.
	 * - rel → built ONLY from self::build_rel (no raw user rel).
	 *
	 * Modifier classes on the INNER .squad-hoverbox div, NOT the ET wrapper.
	 *
	 * @since 4.2.0
	 *
	 * @param string $image_src         Image URL (empty = no <img>).
	 * @param string $image_alt         Alt text for image.
	 * @param string $image_hover_fx    Pre-validated fx token (none|zoom-in|zoom-out|grayscale|blur|rotate).
	 * @param string $persistent_title  Always-visible title (empty = omit layer).
	 * @param bool   $use_icon          Whether an icon should be shown.
	 * @param string $icon_html         Pre-rendered icon markup (empty = omit).
	 * @param string $title             Overlay title (empty = omit).
	 * @param string $content_body      Overlay body text (empty = omit).
	 * @param string $button_text       Button label (empty = omit button unless url present).
	 * @param string $button_url        Button URL (empty = non-link span).
	 * @param bool   $button_new_window Open button link in new tab.
	 * @param string $overlay_animation Pre-validated animation token.
	 * @param string $content_valign    Pre-validated valign token.
	 *
	 * @return string Rendered HTML.
	 */
	public static function build_shell(
		string $image_src,
		string $image_alt,
		string $image_hover_fx,
		string $persistent_title,
		bool $use_icon,
		string $icon_html,
		string $title,
		string $content_body,
		string $button_text,
		string $button_url,
		bool $button_new_window,
		string $overlay_animation,
		string $content_valign
	): string {
		// ── Modifier classes on the INNER div ─────────────────────────────────
		// IMPORTANT: modifiers on .squad-hoverbox (inner div), NOT the ET wrapper
		// .disq_hover_box. SCSS rules: .squad-hoverbox.squad-hoverbox--fx-zoom-in { … }
		$inner_classes = array(
			'squad-hoverbox',
			'squad-hoverbox--fx-' . $image_hover_fx,
			'squad-hoverbox--anim-' . $overlay_animation,
			'squad-hoverbox--valign-' . $content_valign,
		);

		// ── Image (only when non-empty) ───────────────────────────────────────
		$image_html = '';
		if ( '' !== $image_src ) {
			$image_html = sprintf(
				'<img class="squad-hoverbox__image" src="%s" alt="%s" />',
				esc_url( $image_src ),
				esc_attr( $image_alt )
			);
		}

		// ── Persistent layer (only when title non-empty) ──────────────────────
		$persistent_html = '';
		if ( '' !== $persistent_title ) {
			$persistent_html = sprintf(
				'<div class="squad-hoverbox__persistent"><span class="squad-hoverbox__p-title">%s</span></div>',
				esc_html( $persistent_title )
			);
		}

		// ── Icon (only when use_icon + icon_html non-empty) ───────────────────
		$overlay_icon_html = ( $use_icon && '' !== $icon_html ) ? $icon_html : '';

		// ── Overlay title (only when non-empty) ───────────────────────────────
		$title_html = '';
		if ( '' !== $title ) {
			$title_html = sprintf( '<h3 class="squad-hoverbox__title">%s</h3>', esc_html( $title ) );
		}

		// ── Overlay content (only when non-empty) ─────────────────────────────
		$content_html = '';
		if ( '' !== $content_body ) {
			$content_html = sprintf( '<div class="squad-hoverbox__text">%s</div>', wp_kses_post( $content_body ) );
		}

		// ── Button (omit when both text and url are empty) ────────────────────
		$button_html = '';
		if ( '' !== $button_text || '' !== $button_url ) {
			$rel = self::build_rel( $button_new_window );
			if ( '' !== $button_url ) {
				$btn_attrs = sprintf( 'href="%s"', esc_url( $button_url ) );
				if ( $button_new_window ) {
					$btn_attrs .= ' target="_blank"';
				}
				if ( '' !== $rel ) {
					$btn_attrs .= sprintf( ' rel="%s"', esc_attr( $rel ) );
				}
				$button_html = sprintf(
					'<a class="squad-hoverbox__button et_pb_button" %s>%s</a>',
					$btn_attrs,
					esc_html( $button_text )
				);
			} else {
				$button_html = sprintf(
					'<span class="squad-hoverbox__button et_pb_button">%s</span>',
					esc_html( $button_text )
				);
			}
		}

		// ── Overlay content wrapper ───────────────────────────────────────────
		$overlay_content = sprintf(
			'<div class="squad-hoverbox__overlay"><div class="squad-hoverbox__content">%s%s%s%s</div></div>',
			$overlay_icon_html,
			$title_html,
			$content_html,
			$button_html
		);

		return sprintf(
			'<div class="%s">%s%s%s</div>',
			esc_attr( implode( ' ', $inner_classes ) ),
			$image_html,
			$persistent_html,
			$overlay_content
		);
	}

	/**
	 * Sanitize a CSS background/color value.
	 *
	 * Strips characters that could break out of the CSS declaration context
	 * (`{ } ; < > \ " '`). NEVER use esc_attr on a CSS value.
	 *
	 * @since 4.2.0
	 *
	 * @param string $value Raw value.
	 *
	 * @return string Sanitized value (may be empty).
	 */
	public static function sanitize_css_background( string $value ): string {
		$value = trim( $value );
		if ( '' === $value ) {
			return '';
		}
		// Return empty string if any dangerous characters are found — safer than stripping.
		if ( 1 === preg_match( '/[{};<>\\\\"\']/', $value ) ) {
			return '';
		}

		return $value;
	}

	/**
	 * Validate a CSS length value.
	 *
	 * Accepts a positive number followed by a valid CSS unit. Returns '' if
	 * the value does not match — making the declaration a no-op rather than
	 * injecting unsafe content into the stylesheet.
	 *
	 * @since 4.2.0
	 *
	 * @param string $value Raw value.
	 *
	 * @return string Validated value, or '' if invalid.
	 */
	public static function sanitize_css_length( string $value ): string {
		$value = trim( $value );
		if ( '' === $value ) {
			return '';
		}
		if ( 1 === preg_match( '/^\d+(\.\d+)?(px|em|rem|%|vh|vw|vmin|vmax|ch|ex|cm|mm|pt|pc)$/', $value ) ) {
			return $value;
		}

		return '';
	}
}
