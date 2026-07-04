<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Advanced Button helper.
 *
 * Pure-PHP rel-attribute builder and placement/hover allowlists shared by the
 * Divi 4 and Divi 5 Advanced Button render paths. No Divi dependency — boots
 * cleanly under PHPUnit (unlike the module abstracts).
 *
 * @since   4.1.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Shared\Modules\Creative\Advanced_Button;

use function esc_attr;
use function esc_html;
use function esc_url;
use function implode;
use function in_array;
use function preg_replace;
use function sprintf;
use function trim;

/**
 * Advanced Button helper.
 *
 * @since 4.1.0
 */
final class Button_Helper {

    /**
     * Build the rel attribute value for the button link.
     *
     * Returns a space-joined string of rel tokens:
     * - `noopener noreferrer` are added only when `$new_window` is true (prevents
     *   tab-napping on target="_blank").
     * - `nofollow` is appended when `$nofollow` is true.
     * - Returns empty string when neither applies (caller omits the attribute).
     *
     * Order: `noopener noreferrer nofollow`.
     *
     * @since 4.1.0
     *
     * @param bool $new_window Whether the link opens in a new window/tab.
     * @param bool $nofollow   Whether to append nofollow.
     *
     * @return string Space-joined rel tokens, or '' when no tokens apply.
     */
    public static function build_rel( bool $new_window, bool $nofollow ): string {
        $tokens = array();

        if ( $new_window ) {
            $tokens[] = 'noopener';
            $tokens[] = 'noreferrer';
        }

        if ( $nofollow ) {
            $tokens[] = 'nofollow';
        }

        return implode( ' ', $tokens );
    }

    /**
     * Whether an icon placement token is in the allowlist.
     *
     * Allowlist: left, right, top, bottom.
     *
     * @since 4.1.0
     *
     * @param string $placement Icon placement token.
     *
     * @return bool
     */
    public static function is_valid_placement( string $placement ): bool {
        return in_array( $placement, array( 'left', 'right', 'top', 'bottom' ), true );
    }

    /**
     * Whether a hover effect token is in the allowlist.
     *
     * Allowlist: none, bg-slide, lift, scale, border.
     *
     * @since 4.1.0
     *
     * @param string $hover Hover effect token.
     *
     * @return bool
     */
    public static function is_valid_hover( string $hover ): bool {
        return in_array( $hover, array( 'none', 'bg-slide', 'lift', 'scale', 'border' ), true );
    }

    /**
     * Build the `.squad-advanced-button` shell shared with the Divi 5 render.
     *
     * Emits a link (`<a>`) variant when `$button_url` is non-empty, or a `<span>`
     * non-link variant when URL is empty — both carry `et_pb_button` so Divi's
     * native button styling applies. The `rel` attribute is emitted ONLY when
     * non-empty and ONLY on the link variant. `target="_blank"` is paired with
     * `noopener noreferrer` via self::build_rel.
     *
     * SECURITY CONTRACT:
     * - `$button_text` / `$sub_text` → esc_html before output.
     * - `$button_url` → esc_url before output.
     * - `$rel` → built ONLY from the boolean flags via self::build_rel.
     * - `$icon_html` → caller passes pre-escaped markup via wp_kses_post.
     * - `$icon_placement` / `$hover_effect` → validated against allowlists before call.
     *
     * @since 4.1.0
     *
     * @param string $button_text    Primary button label (will be esc_html'd).
     * @param string $sub_text       Sub-text (empty = omitted). Will be esc_html'd.
     * @param string $button_url     URL (empty = non-link span variant).
     * @param bool   $url_new_window Open in new tab.
     * @param bool   $add_nofollow   Append nofollow to rel.
     * @param bool   $use_icon       Whether an icon is shown.
     * @param string $icon_html      Pre-rendered icon markup (empty when no icon).
     * @param string $icon_placement left|right|top|bottom (pre-validated).
     * @param bool   $icon_on_hover  Whether icon is hidden until hover.
     * @param string $hover_effect   none|bg-slide|lift|scale|border (pre-validated).
     *
     * @return string Rendered HTML.
     */
    public static function build_shell(
        string $button_text,
        string $sub_text,
        string $button_url,
        bool $url_new_window,
        bool $add_nofollow,
        bool $use_icon,
        string $icon_html,
        string $icon_placement,
        bool $icon_on_hover,
        string $hover_effect
    ): string {
        // ── Modifier classes on the INNER div (not the ET wrapper) ───────────
        // IMPORTANT: modifiers MUST be on .squad-advanced-button (the inner div),
        // NOT on the outer %%order_class%%.disq_advanced_button ET wrapper.
        // SCSS rules use `.squad-advanced-button.squad-advanced-button--icon-left`
        // descendant selectors — NOT `&.squad-advanced-button--*` which would
        // incorrectly target the ET wrapper class.
        $inner_classes = array( 'squad-advanced-button' );
        if ( $use_icon ) {
            $inner_classes[] = 'squad-advanced-button--icon-' . $icon_placement;
            if ( $icon_on_hover ) {
                $inner_classes[] = 'squad-advanced-button--icon-hover';
            }
        }
        if ( 'none' !== $hover_effect ) {
            $inner_classes[] = 'squad-advanced-button--hover-' . $hover_effect;
        }

        // ── Inner content ────────────────────────────────────────────────────
        $text_html    = sprintf( '<span class="squad-advanced-button__text">%s</span>', esc_html( $button_text ) );
        $sub_html     = '' !== $sub_text
            ? sprintf( '<span class="squad-advanced-button__sub">%s</span>', esc_html( $sub_text ) )
            : '';
        $text_wrap    = sprintf( '<span class="squad-advanced-button__text-wrap">%s%s</span>', $text_html, $sub_html );
        $inner_markup = ( $use_icon && '' !== $icon_html )
            ? $icon_html . $text_wrap
            : $text_wrap;

        // ── Link vs. non-link variant ────────────────────────────────────────
        if ( '' !== $button_url ) {
            $rel   = self::build_rel( $url_new_window, $add_nofollow );
            $attrs = sprintf( 'href="%s"', esc_url( $button_url ) );
            if ( $url_new_window ) {
                $attrs .= ' target="_blank"';
            }
            if ( '' !== $rel ) {
                $attrs .= sprintf( ' rel="%s"', esc_attr( $rel ) );
            }
            $link_html = sprintf(
                '<a class="squad-advanced-button__link et_pb_button" %s>%s</a>',
                $attrs,
                $inner_markup
            );
        } else {
            // Non-link variant: <span> carries the same classes but no href/target/rel.
            $link_html = sprintf(
                '<span class="squad-advanced-button__link et_pb_button">%s</span>',
                $inner_markup
            );
        }

        return sprintf(
            '<div class="%s">%s</div>',
            esc_attr( implode( ' ', $inner_classes ) ),
            $link_html
        );
    }

    /**
     * Sanitize a CSS background/color value.
     *
     * Strips characters that could break out of the CSS declaration context
     * (`{ } ; < > \ " '`). NEVER use esc_attr on a CSS value.
     *
     * @since 4.1.0
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
        return (string) preg_replace( '/[{};<>\\\\"\']/', '', $value );
    }
}
