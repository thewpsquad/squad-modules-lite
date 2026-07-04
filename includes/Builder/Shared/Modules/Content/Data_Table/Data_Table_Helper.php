<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Data Table helper.
 *
 * Pure-PHP markup builder shared by the Divi 4 and Divi 5 Data Table render
 * paths. It emits a semantic `<table>` (the `.squad-data-table` shell) carrying
 * the `data-highlight-col` + `data-sortable` config the frontend engine
 * (`data-table.ts`) reads to fill responsive `data-label`s, apply per-cell
 * highlighting, and wire optional client-side column sorting. It has NO Divi
 * dependency, so it boots cleanly under PHPUnit (unlike the module abstracts).
 *
 * @since   4.3.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Shared\Modules\Content\Data_Table;

use function esc_attr;
use function esc_html;
use function explode;
use function implode;
use function in_array;
use function rtrim;
use function sprintf;
use function str_replace;

/**
 * Data Table helper.
 *
 * @since 4.3.0
 */
final class Data_Table_Helper {

	/**
	 * Allowed responsive modes.
	 *
	 * @since 4.3.0
	 *
	 * @var array<int, string>
	 */
	public const RESPONSIVE_MODES = array( 'stack', 'scroll' );

	/**
	 * Whether a responsive mode token is in the allowlist.
	 *
	 * @since 4.3.0
	 *
	 * @param string $mode stack|scroll.
	 *
	 * @return bool
	 */
	public static function is_valid_responsive( string $mode ): bool {
		return in_array( $mode, self::RESPONSIVE_MODES, true );
	}

	/**
	 * Normalize an on/off flag token, falling back to a default on invalid input.
	 *
	 * @since 4.3.0
	 *
	 * @param string $value   Raw flag value.
	 * @param string $default off|on fallback when the value is not a known token.
	 *
	 * @return string `on` or `off`.
	 */
	public static function normalize_flag( string $value, string $default = 'off' ): string {
		if ( in_array( $value, array( 'on', 'off' ), true ) ) {
			return $value;
		}

		return 'on' === $default ? 'on' : 'off';
	}

	/**
	 * Split a textarea value into a line array.
	 *
	 * Preserves empty cells (so positional mapping to headers stays intact) and
	 * trims a trailing carriage return on each line. The whole value is right
	 * trimmed first so a trailing newline does not create a spurious empty line.
	 *
	 * @since 4.3.0
	 *
	 * @param string $value Raw textarea value.
	 *
	 * @return array<int, string>
	 */
	public static function split_lines( string $value ): array {
		$value = rtrim( $value, "\r\n" );
		if ( '' === $value ) {
			return array();
		}

		$lines = explode( "\n", str_replace( "\r\n", "\n", $value ) );

		$out = array();
		foreach ( $lines as $line ) {
			$out[] = rtrim( $line, "\r" );
		}

		return $out;
	}

	/**
	 * Build the full `.squad-data-table` shell shared with the Divi 4 / Divi 5 renders.
	 *
	 * Emits the wrapper (carrying the responsive modifier + `data-sortable`),
	 * an optional ribbon, and the `<table>` with a `<thead>` header row built
	 * from `$config['headers']`. The `<tbody>` receives the pre-rendered child
	 * row HTML (`$rows_html`). The `<table>` carries `data-highlight-col` (the
	 * 0-based highlight column index, or empty for none) so the frontend script
	 * can apply per-cell highlighting and `data-label`s.
	 *
	 * @since 4.3.0
	 *
	 * @param array<string, mixed> $config    Resolved config: headers (array<string>),
	 *                                         responsive (stack|scroll), highlightColumn
	 *                                         (int 0-based, -1 = none), sticky (on|off),
	 *                                         striped (on|off), sortable (on|off),
	 *                                         ribbon (string).
	 * @param string               $rows_html Concatenated child `<tr>` HTML.
	 *
	 * @return string
	 */
	public static function build_table( array $config, string $rows_html ): string {
		$headers     = isset( $config['headers'] ) && is_array( $config['headers'] ) ? $config['headers'] : array();
		$responsive  = self::is_valid_responsive( (string) ( $config['responsive'] ?? 'stack' ) ) ? (string) $config['responsive'] : 'stack';
		$sticky      = self::normalize_flag( (string) ( $config['sticky'] ?? 'off' ), 'off' );
		$striped     = self::normalize_flag( (string) ( $config['striped'] ?? 'off' ), 'off' );
		$sortable    = self::normalize_flag( (string) ( $config['sortable'] ?? 'off' ), 'off' );
		$ribbon      = (string) ( $config['ribbon'] ?? '' );
		$highlight   = (int) ( $config['highlightColumn'] ?? -1 );
		$highlight_a = $highlight >= 0 ? (string) $highlight : '';

		// Header cells.
		$thead = '';
		foreach ( $headers as $index => $header ) {
			$is_highlight = $highlight >= 0 && (int) $index === $highlight;
			$thead       .= sprintf(
				'<th scope="col" class="%1$s">%2$s</th>',
				esc_attr( 'squad-data-table__th' . ( $is_highlight ? ' is-highlight' : '' ) ),
				esc_html( (string) $header )
			);
		}

		// Table modifier classes.
		$table_classes = 'squad-data-table';
		if ( 'on' === $striped ) {
			$table_classes .= ' squad-data-table--striped';
		}
		if ( 'on' === $sticky ) {
			$table_classes .= ' squad-data-table--sticky';
		}

		$ribbon_html = '' !== $ribbon
			? sprintf( '<span class="squad-data-table__ribbon">%s</span>', esc_html( $ribbon ) )
			: '';

		return sprintf(
			'<div class="%1$s" data-sortable="%2$s">%3$s<table class="%4$s" data-highlight-col="%5$s"><thead><tr>%6$s</tr></thead><tbody>%7$s</tbody></table></div>',
			esc_attr( implode( ' ', array( 'squad-data-table__wrapper', 'squad-data-table--' . $responsive ) ) ),
			esc_attr( $sortable ),
			$ribbon_html,
			esc_attr( $table_classes ),
			esc_attr( $highlight_a ),
			$thead,
			$rows_html
		);
	}

	/**
	 * Build a single `<tr>` data row.
	 *
	 * Header-independent by design: the row emits one `<td>` per cell with no
	 * `data-label` and no per-cell highlight class — the frontend script
	 * (`data-table.ts`) fills `data-label` from the header row and applies the
	 * `is-highlight` class to the highlighted column using the table's
	 * `data-highlight-col` attribute.
	 *
	 * @since 4.3.0
	 *
	 * @param array<string, mixed> $row Row config: cells (array<string>),
	 *                                  highlight (on|off).
	 *
	 * @return string
	 */
	public static function build_row( array $row ): string {
		$cells     = isset( $row['cells'] ) && is_array( $row['cells'] ) ? $row['cells'] : array();
		$highlight = self::normalize_flag( (string) ( $row['highlight'] ?? 'off' ), 'off' );

		$cells_html = '';
		foreach ( $cells as $cell ) {
			$cells_html .= sprintf(
				'<td class="squad-data-table__td">%s</td>',
				esc_html( (string) $cell )
			);
		}

		$row_class = 'squad-data-table__row';
		if ( 'on' === $highlight ) {
			$row_class .= ' is-highlight';
		}

		return sprintf(
			'<tr class="%1$s">%2$s</tr>',
			esc_attr( $row_class ),
			$cells_html
		);
	}
}
