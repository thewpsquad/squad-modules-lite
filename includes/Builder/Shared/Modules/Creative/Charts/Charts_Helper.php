<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Charts helper.
 *
 * Pure-PHP shell builder shared by the Divi 4 and Divi 5 Charts render paths.
 * It assembles the Chart.js v4 config SERVER-SIDE and emits the `.squad-charts`
 * shell carrying that config in a `data-config` attribute, so the frontend
 * engine (`charts.ts`) just parses + instantiates `new Chart()`. It has NO Divi
 * dependency, so it boots cleanly under PHPUnit (unlike the module abstracts).
 *
 * @since   4.3.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Shared\Modules\Creative\Charts;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use function array_filter;
use function array_map;
use function count;
use function esc_attr;
use function explode;
use function floatval;
use function in_array;
use function preg_split;
use function sprintf;
use function strpos;
use function substr;
use function trim;
use function wp_json_encode;

/**
 * Charts helper.
 *
 * @since 4.3.0
 */
final class Charts_Helper {

	/**
	 * The supported Chart.js chart types.
	 *
	 * @since 4.3.0
	 *
	 * @var array<int, string>
	 */
	public const TYPES = array( 'bar', 'line', 'pie', 'doughnut' );

	/**
	 * Default color palette used when the colors textarea is empty.
	 *
	 * @since 4.3.0
	 *
	 * @var array<int, string>
	 */
	public const PALETTE = array( '#5E2EFF', '#22C55E', '#F59E0B', '#EF4444', '#3B82F6', '#EC4899', '#14B8A6', '#A855F7' );

	/**
	 * Whether a chart type token is in the allowlist.
	 *
	 * @since 4.3.0
	 *
	 * @param string $chart_type bar|line|pie|doughnut.
	 *
	 * @return bool
	 */
	public static function is_valid_type( string $chart_type ): bool {
		return in_array( $chart_type, self::TYPES, true );
	}

	/**
	 * Split a textarea value into trimmed, non-empty lines.
	 *
	 * @since 4.3.0
	 *
	 * @param string $value Raw textarea value.
	 *
	 * @return array<int, string>
	 */
	public static function split_lines( string $value ): array {
		$lines = preg_split( '/\r\n|\r|\n/', $value );
		if ( false === $lines ) {
			return array();
		}

		$lines = array_map( 'trim', $lines );

		return array_values(
			array_filter(
				$lines,
				static function ( $line ) {
					return '' !== $line;
				}
			)
		);
	}

	/**
	 * Parse "Name: v1,v2,v3" lines into Chart.js datasets.
	 *
	 * Each non-empty line becomes one dataset; numeric values are cast via
	 * floatval and blank cells become null. For bar/line a single cycled color
	 * is applied per dataset; for pie/doughnut the first dataset is colored
	 * per-slice (an array of per-label colors).
	 *
	 * @since 4.3.0
	 *
	 * @param string             $value        Raw datasets textarea value.
	 * @param array<int, string> $colors       Resolved color list (falls back to palette).
	 * @param string             $chart_type   bar|line|pie|doughnut.
	 * @param int                $border_width Dataset border width.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function parse_datasets( string $value, array $colors, string $chart_type, int $border_width ): array {
		if ( count( $colors ) === 0 ) {
			$colors = self::PALETTE;
		}

		$is_circular = in_array( $chart_type, array( 'pie', 'doughnut' ), true );
		$lines       = self::split_lines( $value );
		$datasets    = array();

		foreach ( $lines as $index => $line ) {
			$label  = '';
			$values = $line;

			$colon = strpos( $line, ':' );
			if ( false !== $colon ) {
				$label  = trim( substr( $line, 0, $colon ) );
				$values = substr( $line, $colon + 1 );
			}

			$cells = array_map( 'trim', explode( ',', $values ) );
			$data  = array();
			foreach ( $cells as $cell ) {
				$data[] = '' === $cell ? null : floatval( $cell );
			}

			if ( $is_circular ) {
				// First dataset is colored per-slice; subsequent datasets cycle a single color.
				$slice_colors = array();
				foreach ( $data as $cell_index => $unused ) {
					$slice_colors[] = $colors[ $cell_index % count( $colors ) ];
				}

				$datasets[] = array(
					'label'           => $label,
					'data'            => $data,
					'backgroundColor' => $slice_colors,
					'borderColor'     => '#ffffff',
					'borderWidth'     => $border_width,
				);
			} else {
				$color = $colors[ $index % count( $colors ) ];

				$datasets[] = array(
					'label'           => $label,
					'data'            => $data,
					'backgroundColor' => $color,
					'borderColor'     => $color,
					'borderWidth'     => $border_width,
				);
			}
		}

		return $datasets;
	}

	/**
	 * Build the `.squad-charts` shell shared with the Divi 5 render.
	 *
	 * Assembles the Chart.js v4 config (type/data/options) server-side, encodes
	 * it into a single-quoted `data-config` attribute, and emits the canvas
	 * wrapper with an accessible `role="img"` + `aria-label`.
	 *
	 * @since 4.3.0
	 *
	 * @param array<string, mixed> $raw Resolved config. Keys (strings unless noted):
	 *                                  chartType, labels, datasets, colors, showLegend,
	 *                                  showGrid, beginAtZero, animateOnScroll,
	 *                                  chartHeight (int), title, borderWidth (int).
	 *
	 * @return string
	 */
	public static function build_shell( array $raw ): string {
		$chart_type = (string) ( $raw['chartType'] ?? 'bar' );
		if ( ! self::is_valid_type( $chart_type ) ) {
			$chart_type = 'bar';
		}

		$show_legend  = 'on' === (string) ( $raw['showLegend'] ?? 'on' );
		$show_grid    = 'on' === (string) ( $raw['showGrid'] ?? 'on' );
		$begin_zero   = 'on' === (string) ( $raw['beginAtZero'] ?? 'on' );
		$animate      = 'on' === (string) ( $raw['animateOnScroll'] ?? 'on' );
		$chart_height = (int) ( $raw['chartHeight'] ?? 300 );
		$title        = (string) ( $raw['title'] ?? '' );
		$border_width = (int) ( $raw['borderWidth'] ?? 2 );

		$labels   = self::split_lines( (string) ( $raw['labels'] ?? '' ) );
		$colors   = self::split_lines( (string) ( $raw['colors'] ?? '' ) );
		$datasets = self::parse_datasets( (string) ( $raw['datasets'] ?? '' ), $colors, $chart_type, $border_width );

		$options = array(
			'responsive'          => true,
			'maintainAspectRatio' => false,
			'plugins'             => array(
				'legend' => array( 'display' => $show_legend ),
				'title'  => array(
					'display' => '' !== $title,
					'text'    => $title,
				),
			),
		);

		if ( ! in_array( $chart_type, array( 'pie', 'doughnut' ), true ) ) {
			$options['scales'] = array(
				'y' => array(
					'beginAtZero' => $begin_zero,
					'grid'        => array( 'display' => $show_grid ),
				),
				'x' => array(
					'grid' => array( 'display' => $show_grid ),
				),
			);
		}

		$config = array(
			'type'    => $chart_type,
			'data'    => array(
				'labels'   => $labels,
				'datasets' => $datasets,
			),
			'options' => $options,
		);

		$aria_label = '' !== $title ? $title : sprintf( '%s chart', $chart_type );

		return sprintf(
			'<div class="squad-charts" data-config=\'%1$s\' data-animate="%2$s"><div class="squad-charts__canvas-wrap" style="position:relative;height:%3$dpx"><canvas class="squad-charts__canvas" role="img" aria-label="%4$s"></canvas></div></div>',
			esc_attr( (string) wp_json_encode( $config ) ),
			$animate ? 'on' : 'off',
			$chart_height,
			esc_attr( $aria_label )
		);
	}
}
