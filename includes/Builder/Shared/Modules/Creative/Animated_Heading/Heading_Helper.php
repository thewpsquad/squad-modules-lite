<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Animated Heading helper.
 *
 * Pure-PHP effect/granularity/tag/easing allowlists, an easing mapper, and the
 * rotating-words parser shared by the Divi 4 and Divi 5 Animated Heading render
 * paths. No Divi dependency — boots cleanly under PHPUnit (unlike the module
 * abstracts, which hit the Patchwork esc_js DefinedTooEarly blocker).
 *
 * @since   4.1.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Shared\Modules\Creative\Animated_Heading;

use function count;
use function esc_attr;
use function esc_html;
use function implode;
use function in_array;
use function mb_str_split;
use function preg_split;
use function sprintf;
use function trim;

/**
 * Animated Heading helper.
 *
 * @since 4.1.0
 */
final class Heading_Helper {

	/**
	 * Whether an effect token is in the allowlist.
	 *
	 * Allowlist: fade, slide, scale, flip.
	 *
	 * @since 4.1.0
	 *
	 * @param string $effect Effect token.
	 *
	 * @return bool
	 */
	public static function is_valid_effect( string $effect ): bool {
		return in_array( $effect, array( 'fade', 'slide', 'scale', 'flip' ), true );
	}

	/**
	 * Whether a granularity token is in the allowlist.
	 *
	 * Allowlist: word, letter.
	 *
	 * @since 4.1.0
	 *
	 * @param string $granularity Granularity token.
	 *
	 * @return bool
	 */
	public static function is_valid_granularity( string $granularity ): bool {
		return in_array( $granularity, array( 'word', 'letter' ), true );
	}

	/**
	 * Whether an HTML tag is in the allowed heading-tag allowlist.
	 *
	 * Allowed: h1, h2, h3, h4, h5, h6, p, span, div.
	 *
	 * @since 4.1.0
	 *
	 * @param string $tag Tag name (lowercase).
	 *
	 * @return bool
	 */
	public static function is_valid_tag( string $tag ): bool {
		return in_array( $tag, array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span', 'div' ), true );
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
	 * Parse the raw rotating-text textarea into a clean list of words.
	 *
	 * Splits on any newline form (CRLF, CR, LF), trims each line, drops empty
	 * lines, and re-indexes. Returns [] for empty/whitespace-only input.
	 *
	 * @since 4.1.0
	 *
	 * @param string $raw Raw textarea value.
	 *
	 * @return array<int, string>
	 */
	public static function parse_words( string $raw ): array {
		$lines = preg_split( '/\r\n|\r|\n/', $raw );
		if ( false === $lines ) {
			return array();
		}

		$words = array();
		foreach ( $lines as $line ) {
			$trimmed = trim( $line );
			if ( '' !== $trimmed ) {
				$words[] = $trimmed;
			}
		}

		return $words;
	}

	/**
	 * Build the `.squad-anim-heading` shell shared with the Divi 5 render.
	 *
	 * - prefix / suffix spans omitted when empty.
	 * - rotator omitted entirely when there are no words after parsing.
	 * - first word carries `is-active` (no-JS / first paint shows it).
	 * - letter granularity splits each word into `__char` spans carrying a
	 *   `--squad-ah-char` index (a literal space becomes a non-breaking space).
	 * - effect + granularity modifier classes live on the inner heading element.
	 * - tag validated against the allowlist.
	 *
	 * @since 4.1.0
	 *
	 * @param array<string, string> $config Resolved config.
	 *
	 * @return string
	 */
	public static function build_shell( array $config ): string {
		$tag    = self::is_valid_tag( $config['heading_tag'] ?? 'h2' ) ? $config['heading_tag'] : 'h2';
		$gran   = self::is_valid_granularity( $config['granularity'] ?? 'word' ) ? $config['granularity'] : 'word';
		$effect = self::is_valid_effect( $config['effect'] ?? 'fade' ) ? $config['effect'] : 'fade';

		$classes = array( 'squad-anim-heading', "squad-anim-heading--$effect", "squad-anim-heading--$gran" );

		$prefix = esc_html( $config['prefix'] ?? '' );
		$suffix = esc_html( $config['suffix'] ?? '' );

		$prefix_html = '' !== $prefix ? sprintf( '<span class="squad-anim-heading__prefix">%s</span>', $prefix ) : '';
		$suffix_html = '' !== $suffix ? sprintf( '<span class="squad-anim-heading__suffix">%s</span>', $suffix ) : '';

		$words = self::parse_words( $config['rotating'] ?? '' );

		$rotator_html = '';
		if ( count( $words ) > 0 ) {
			$words_html = '';
			foreach ( $words as $i => $word ) {
				$active     = 0 === $i ? ' is-active' : '';
				$inner      = ( 'letter' === $gran ) ? self::build_letters( $word ) : esc_html( $word );
				$words_html .= sprintf( '<span class="squad-anim-heading__word%s">%s</span>', $active, $inner );
			}

			$rotator_html = sprintf(
				'<span class="squad-anim-heading__rotator" data-duration="%1$s" data-delay="%2$s" data-count="%3$s">%4$s</span>',
				esc_attr( $config['duration'] ?? '600' ),
				esc_attr( $config['rotation_delay'] ?? '1500' ),
				esc_attr( (string) count( $words ) ),
				$words_html
			);
		}

		return sprintf(
			'<%1$s class="%2$s">%3$s%4$s%5$s</%1$s>',
			$tag,
			esc_attr( implode( ' ', $classes ) ),
			$prefix_html,
			$rotator_html,
			$suffix_html
		);
	}

	/**
	 * Split a word into per-character spans for letter granularity.
	 *
	 * Each char carries a 0-based `--squad-ah-char` index (consumed by the CSS
	 * stagger). A literal space renders as a non-breaking space so spacing is
	 * preserved. mb-safe.
	 *
	 * @since 4.1.0
	 *
	 * @param string $word Single word/phrase.
	 *
	 * @return string
	 */
	public static function build_letters( string $word ): string {
		$chars = mb_str_split( $word );
		$out   = '';
		foreach ( $chars as $i => $char ) {
			$display = ' ' === $char ? '&nbsp;' : esc_html( $char );
			$out     .= sprintf(
				'<span class="squad-anim-heading__char" style="--squad-ah-char:%d">%s</span>',
				$i,
				$display
			);
		}

		return $out;
	}
}
