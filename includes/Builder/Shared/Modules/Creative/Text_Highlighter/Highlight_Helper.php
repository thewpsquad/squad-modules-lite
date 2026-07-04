<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Text Highlighter helper.
 *
 * Pure-PHP SVG shape registry + allowlists shared by the Divi 4 and Divi 5
 * Text Highlighter render paths. No Divi dependency — boots cleanly under
 * PHPUnit (unlike the module abstracts, which hit the Patchwork esc_js
 * DefinedTooEarly blocker).
 *
 * @since   4.0.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Shared\Modules\Creative\Text_Highlighter;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use function absint;
use function cos;
use function deg2rad;
use function esc_attr;
use function esc_html;
use function implode;
use function in_array;
use function preg_replace;
use function round;
use function sanitize_key;
use function sin;
use function sprintf;
use function str_replace;
use function trim;
use function wp_unique_id;

/**
 * Text Highlighter helper.
 *
 * @since 4.0.0
 */
final class Highlight_Helper {

	/**
	 * Return the full SVG shape registry (8 shapes).
	 *
	 * Each entry is `{ viewBox: string, path: string }` — a single hand-drawn-style
	 * SVG path designed for `preserveAspectRatio="none"` stretch across the word.
	 * All paths are fill=none strokeable paths so pathLength="1" draws cleanly.
	 *
	 * @since 4.0.0
	 *
	 * @return array<string, array{viewBox: string, path: string}>
	 */
	public static function get_shapes(): array {
		return array(
			'underline'        => array(
				'viewBox' => '0 0 100 10',
				'path'    => 'M 2 7 C 15 4 30 9 50 6 C 70 3 85 8 98 5',
			),
			'double-underline' => array(
				'viewBox' => '0 0 100 14',
				'path'    => 'M 2 5 C 20 3 50 7 98 4 M 2 10 C 20 8 50 12 98 9',
			),
			'circle'           => array(
				'viewBox' => '0 0 110 40',
				'path'    => 'M 55 3 C 80 1 108 8 107 20 C 106 32 82 38 55 37 C 28 38 2 32 3 20 C 4 8 30 1 55 3 Z',
			),
			'box'              => array(
				'viewBox' => '0 0 100 30',
				'path'    => 'M 3 3 C 30 1 70 2 97 4 C 99 10 99 20 97 27 C 70 29 30 28 3 27 C 1 20 1 10 3 3 Z',
			),
			'strikethrough'    => array(
				'viewBox' => '0 0 100 10',
				'path'    => 'M 1 5 C 20 3 50 7 99 5',
			),
			'curly-underline'  => array(
				'viewBox' => '0 0 100 12',
				'path'    => 'M 1 8 C 8 4 16 12 24 8 C 32 4 40 12 48 8 C 56 4 64 12 72 8 C 80 4 88 12 96 8',
			),
			'cross-off'        => array(
				'viewBox' => '0 0 100 30',
				'path'    => 'M 3 3 C 30 8 70 22 97 27 M 97 3 C 70 8 30 22 3 27',
			),
			'bracket'          => array(
				'viewBox' => '0 0 100 30',
				'path'    => 'M 15 3 C 8 3 3 5 3 15 C 3 25 8 27 15 27 M 85 3 C 92 3 97 5 97 15 C 97 25 92 27 85 27',
			),
		);
	}

	/**
	 * Return one shape by slug; falls back to `underline` for unknown slugs.
	 *
	 * @since 4.0.0
	 *
	 * @param string $type Shape slug.
	 *
	 * @return array{viewBox: string, path: string}
	 */
	public static function get_shape( string $type ): array {
		$shapes = self::get_shapes();

		return $shapes[ $type ] ?? $shapes['underline'];
	}

	/**
	 * Whether a highlight type slug is in the allowlist.
	 *
	 * @since 4.0.0
	 *
	 * @param string $type Highlight type slug.
	 *
	 * @return bool
	 */
	public static function is_valid_type( string $type ): bool {
		return in_array( $type, array( 'underline', 'double-underline', 'circle', 'box', 'strikethrough', 'curly-underline', 'cross-off', 'bracket' ), true );
	}

	/**
	 * Whether an HTML tag is in the allowed heading-tag allowlist.
	 *
	 * Allowed: h1, h2, h3, h4, h5, h6, p, span, div.
	 *
	 * @since 4.0.0
	 *
	 * @param string $tag Tag name (lowercase).
	 *
	 * @return bool
	 */
	public static function is_valid_tag( string $tag ): bool {
		return in_array( $tag, array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span', 'div' ), true );
	}

	/**
	 * Build the `.squad-highlight` shell shared with the Divi 5 render.
	 *
	 * - `prefix` / `suffix` spans omitted when empty.
	 * - `__mark` + SVG omitted entirely when `highlighted` is empty.
	 * - `--animate` class added only when animate=on.
	 * - `--loop` class added only when animate=on AND anim_loop=on.
	 * - When animate=off the SVG is still rendered (fully drawn, static via CSS).
	 * - Gradient `<defs>` injected inside `<svg>` when use_gradient=on.
	 * - SVG viewBox and path come ONLY from self::get_shape().
	 * - Colors are sanitized via sanitize_css_background(); tag validated against allowlist.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, string> $config Resolved config.
	 *
	 * @return string
	 */
	public static function build_shell( array $config ): string {
		$tag          = self::is_valid_tag( $config['heading_tag'] ?? 'h2' ) ? $config['heading_tag'] : 'h2';
		$type         = self::is_valid_type( $config['type'] ?? 'underline' ) ? $config['type'] : 'underline';
		$animate      = ( 'on' === ( $config['animate'] ?? 'on' ) );
		$loop         = $animate && ( 'on' === ( $config['anim_loop'] ?? 'off' ) );
		$use_gradient = ( 'on' === ( $config['use_gradient'] ?? 'off' ) );

		// Build modifier classes on the inner heading element.
		$classes = array( 'squad-highlight', "squad-highlight--$type" );
		if ( $animate ) {
			$classes[] = 'squad-highlight--animate';
		}
		if ( $loop ) {
			$classes[] = 'squad-highlight--loop';
		}

		// Sanitize colors.
		$highlight_color = self::sanitize_css_background( $config['highlight_color'] ?? '#6a33d7' );
		$gradient_start  = self::sanitize_css_background( $config['gradient_start'] ?? '#6a33d7' );
		$gradient_end    = self::sanitize_css_background( $config['gradient_end'] ?? '#d433c4' );
		$gradient_angle  = absint( $config['gradient_angle'] ?? '90' );
		$stroke_width    = absint( $config['stroke_width'] ?? '3' );

		// Prefix / suffix spans — omit when empty.
		$prefix      = esc_html( $config['prefix'] ?? '' );
		$suffix      = esc_html( $config['suffix'] ?? '' );
		$highlighted = esc_html( $config['highlighted'] ?? '' );

		$prefix_html = '' !== $prefix ? sprintf( '<span class="squad-highlight__prefix">%s</span>', $prefix ) : '';
		$suffix_html = '' !== $suffix ? sprintf( '<span class="squad-highlight__suffix">%s</span>', $suffix ) : '';

		// Mark + SVG: omit entirely when highlighted_text is empty.
		$mark_html = '';
		if ( '' !== $highlighted ) {
			$shape    = self::get_shape( $type );
			$view_box = esc_attr( $shape['viewBox'] );
			$path_d   = esc_attr( $shape['path'] );

			// Gradient defs + stroke source.
			$defs_html   = '';
			$stroke_attr = 'currentColor';

			if ( $use_gradient ) {
				$uid         = str_replace( '_', '-', sanitize_key( wp_unique_id( 'squad-hl-grad-' ) ) );
				$angle_x2    = round( cos( deg2rad( $gradient_angle ) ), 4 );
				$angle_y2    = round( sin( deg2rad( $gradient_angle ) ), 4 );
				$defs_html   = sprintf(
					'<defs><linearGradient id="%1$s" x1="0" y1="0" x2="%2$s" y2="%3$s" gradientUnits="userSpaceOnUse">'
					. '<stop offset="0%%" stop-color="%4$s"/>'
					. '<stop offset="100%%" stop-color="%5$s"/>'
					. '</linearGradient></defs>',
					esc_attr( $uid ),
					esc_attr( (string) $angle_x2 ),
					esc_attr( (string) $angle_y2 ),
					esc_attr( $gradient_start ),
					esc_attr( $gradient_end )
				);
				$stroke_attr = esc_attr( "url(#$uid)" );
			}

			$data_animate  = $animate ? '1' : '0';
			$data_loop     = $loop ? '1' : '0';
			$data_duration = esc_attr( $config['anim_duration'] ?? '1200' );
			$data_delay    = esc_attr( $config['anim_delay'] ?? '0' );

			$mark_html = sprintf(
				'<span class="squad-highlight__mark" data-duration="%1$s" data-delay="%2$s" data-loop="%3$s" data-animate="%4$s">'
				. '<span class="squad-highlight__text">%5$s</span>'
				. '<svg class="squad-highlight__svg" viewBox="%6$s" preserveAspectRatio="none" aria-hidden="true" focusable="false">'
				. '%7$s'
				. '<path d="%8$s" pathLength="1" fill="none" stroke="%9$s" stroke-width="%10$s" stroke-linecap="round"/>'
				. '</svg>'
				. '</span>',
				$data_duration,
				$data_delay,
				esc_attr( $data_loop ),
				esc_attr( $data_animate ),
				$highlighted,
				$view_box,
				$defs_html,
				$path_d,
				$stroke_attr,
				esc_attr( (string) $stroke_width )
			);
		}

		return sprintf(
			'<%1$s class="%2$s">%3$s%4$s%5$s</%1$s>',
			$tag,
			esc_attr( implode( ' ', $classes ) ),
			$prefix_html,
			$mark_html,
			$suffix_html
		);
	}

	/**
	 * Sanitize a CSS color/background value.
	 *
	 * Strips characters that could break out of a CSS declaration context.
	 * NEVER use esc_attr on a CSS value.
	 *
	 * @since 4.0.0
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
