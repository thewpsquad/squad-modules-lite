<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Table of Contents helper.
 *
 * Pure-PHP slug/dedupe/level/tag logic shared by the Divi 4 and Divi 5 Table of
 * Contents render paths. It mirrors the frontend JS (`table-of-contents.ts`)
 * logic so it can be the unit-tested contract. It has NO Divi dependency, so it
 * boots cleanly under PHPUnit (unlike the module abstracts).
 *
 * @since   4.0.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Shared\Modules\Creative\Table_Of_Contents;

use function absint;
use function esc_attr;
use function esc_attr__;
use function esc_html;
use function implode;
use function in_array;
use function preg_replace;
use function sprintf;
use function strip_tags;
use function strtolower;
use function trim;
use function uniqid;

/**
 * Table of Contents helper.
 *
 * @since 4.0.0
 */
final class Toc_Helper {

	/**
	 * Slugify a heading's text into an anchor-id-safe string.
	 *
	 * Lowercases, strips tags, turns every run of non-alphanumeric ASCII into a
	 * single dash, trims leading/trailing dashes, and falls back to `section`
	 * for empty results.
	 *
	 * @since 4.0.0
	 *
	 * @param string $text Raw heading text (may contain HTML).
	 *
	 * @return string
	 */
	public static function slugify( string $text ): string {
		$text = strip_tags( $text );
		$text = strtolower( $text );
		$text = (string) preg_replace( '/[^a-z0-9]+/', '-', $text );
		$text = trim( $text, '-' );

		return '' !== $text ? $text : 'section';
	}

	/**
	 * Ensure an id is unique against a running set of seen ids.
	 *
	 * Returns `$base` the first time, then `$base-2`, `$base-3`, … on collision.
	 * Records the returned id in `$seen` by reference.
	 *
	 * @since 4.0.0
	 *
	 * @param string             $base Base slug.
	 * @param array<int, string> $seen Seen ids (passed by reference).
	 *
	 * @return string
	 */
	public static function dedupe_id( string $base, array &$seen ): string {
		if ( ! in_array( $base, $seen, true ) ) {
			$seen[] = $base;

			return $base;
		}

		$i = 2;
		do {
			$candidate = $base . '-' . $i;
			$i++;
		} while ( in_array( $candidate, $seen, true ) );

		$seen[] = $candidate;

		return $candidate;
	}

	/**
	 * Resolve the selected heading levels from the include_h1..h6 props.
	 *
	 * Reads `include_h1`..`include_h6` (`'on'`/`'off'`) into an ordered int list
	 * (e.g. `[2, 3, 4]`). When nothing is selected, defaults to `[2, 3, 4]`.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $props Module props.
	 *
	 * @return array<int, int>
	 */
	public static function selected_levels( array $props ): array {
		$levels = array();
		for ( $n = 1; $n <= 6; $n++ ) {
			if ( 'on' === ( $props[ 'include_h' . $n ] ?? '' ) ) {
				$levels[] = $n;
			}
		}

		return array() !== $levels ? $levels : array( 2, 3, 4 );
	}

	/**
	 * Whether a tag is a valid heading tag (h1–h6, lowercase).
	 *
	 * @since 4.0.0
	 *
	 * @param string $tag Tag name.
	 *
	 * @return bool
	 */
	public static function is_valid_tag( string $tag ): bool {
		return in_array( $tag, array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ), true );
	}

	/**
	 * Build the `.squad-toc` config shell shared with the Divi 5 render.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, string> $config      Resolved data-* config.
	 * @param string                $list        ordered|unordered|none.
	 * @param bool                  $sticky      Whether sticky is enabled.
	 * @param bool                  $collapsible Whether collapsible is enabled.
	 * @param string                $show_title  on|off.
	 * @param string                $title       Title text.
	 * @param string                $title_tag   Title tag (validated).
	 *
	 * @return string
	 */
	public static function build_shell( array $config, string $list, bool $sticky, bool $collapsible, string $show_title, string $title, string $title_tag ): string {
		$uid     = uniqid();
		$body_id = 'squad-toc-body-' . esc_attr( $uid );

		$classes = array( 'squad-toc' );
		if ( $sticky ) {
			$classes[] = 'squad-toc--sticky';
		}
		if ( $collapsible ) {
			$classes[] = 'squad-toc--collapsible';
		}
		$classes[] = 'squad-toc--list-' . $list;

		$style_attr = $sticky
			? sprintf( ' style="--squad-toc-sticky-offset:%dpx"', absint( $config['sticky_offset'] ) )
			: '';

		$title_html = '';
		if ( 'on' === $show_title ) {
			$title_text = esc_html( $title );
			$tag        = self::is_valid_tag( $title_tag ) ? $title_tag : 'h3';
			if ( $collapsible ) {
				$expanded   = '1' === $config['collapsed'] ? 'false' : 'true';
				$title_html = sprintf(
					'<button type="button" class="squad-toc__title" aria-expanded="%1$s" aria-controls="%2$s">%3$s</button>',
					esc_attr( $expanded ),
					esc_attr( $body_id ),
					$title_text
				);
			} else {
				$title_html = sprintf( '<%1$s class="squad-toc__title">%2$s</%1$s>', $tag, $title_text );
			}
		}

		return sprintf(
			'<div class="%1$s"%2$s data-selector="%3$s" data-levels="%4$s" data-list="%5$s" data-smooth="%6$s" data-offset="%7$s" data-spy="%8$s" data-min="%9$s" data-collapsed="%10$s" data-sticky-offset="%11$s">%12$s<nav class="squad-toc__body" id="%13$s" aria-label="%14$s"></nav></div>',
			esc_attr( implode( ' ', $classes ) ),
			$style_attr,
			esc_attr( $config['selector'] ),
			esc_attr( $config['levels'] ),
			esc_attr( $config['list'] ),
			esc_attr( $config['smooth'] ),
			esc_attr( $config['offset'] ),
			esc_attr( $config['spy'] ),
			esc_attr( $config['min'] ),
			esc_attr( $config['collapsed'] ),
			esc_attr( $config['sticky_offset'] ),
			$title_html,
			esc_attr( $body_id ),
			esc_attr__( 'Table of contents', 'squad-modules-for-divi' )
		);
	}
}
