<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Logo Grid Module (Divi 5 / Block API).
 *
 * Static CSS Grid of logos with hover effects and optional per-logo links.
 * No JavaScript dependency.
 *
 * @since   4.0.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version5\Modules\Media;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

if ( ! class_exists( 'ET\Builder\Packages\Module\Module' ) ) {
	return;
}

use DiviSquad\Builder\Version5\Abstracts\Module;
use ET\Builder\FrontEnd\Module\Style;
use ET\Builder\Packages\Module\Module as DiviModule;
use ET\Builder\Packages\Module\Options\Css\CssStyle;
use ET\Builder\Packages\Module\Options\Element\ElementClassnames;
use Throwable;
use WP_Block;
use function absint;
use function esc_html__;
use function wp_json_encode;

/**
 * Logo Grid parent module class.
 *
 * @since 4.0.0
 */
class Logo_Grid extends Module {

	/**
	 * @since 4.0.0
	 * @return string
	 */
	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/logo-grid/';
	}

	/**
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $args Classnames arguments.
	 *
	 * @return void
	 */
	public static function module_classnames( array $args ): void {
		$args['classnamesInstance']->add( 'disq_logo_grid' );
		$args['classnamesInstance']->add(
			ElementClassnames::classnames(
				array(
					'attrs' => $args['attrs']['module']['decoration'] ?? array(),
				)
			)
		);
	}

	/**
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $args Script data arguments.
	 *
	 * @return void
	 */
	public static function module_script_data( array $args ): void {
		$args['elements']->script_data(
			array(
				'attrName' => 'module',
			)
		);
	}

	/**
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $args Style arguments.
	 *
	 * @return void
	 */
	public static function module_styles( array $args ): void {
		$attrs    = $args['attrs'] ?? array();
		$elements = $args['elements'];
		$settings = $args['settings'] ?? array();

		Style::add(
			array(
				'id'            => $args['id'],
				'name'          => $args['name'],
				'orderIndex'    => $args['orderIndex'],
				'storeInstance' => $args['storeInstance'],
				'styles'        => array(
					$elements->style(
						array(
							'attrName'   => 'module',
							'styleProps' => array(
								'disabledOn' => array(
									'disabledModuleVisibility' => $settings['disabledModuleVisibility'] ?? null,
								),
							),
						)
					),
					CssStyle::style(
						array(
							'selector' => $args['orderClass'],
							'attr'     => $attrs['css'] ?? array(),
						)
					),
				),
			)
		);
	}

	/**
	 * Render callback for the Logo Grid module.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $attrs                 Block attributes.
	 * @param string               $child_modules_content Rendered child HTML.
	 * @param WP_Block             $block                 Parsed block instance.
	 * @param object               $elements              ModuleElements instance.
	 *
	 * @return string Rendered HTML.
	 */
	public static function render_callback( array $attrs, string $child_modules_content, WP_Block $block, $elements ): string {
		try {
			if ( '' === trim( $child_modules_content ) ) {
				return sprintf(
					'<div class="squad-notice">%s</div>',
					esc_html__( 'Add at least one Logo Grid Item.', 'squad-modules-for-divi' )
				);
			}

			$inner = $attrs['grid']['innerContent']['desktop']['value'] ?? array();
			$uid   = self::get_instance_uid( $block );

			$inline_css = self::get_grid_css( $inner, $uid )
				. self::get_hover_css( $inner, $uid )
				. self::get_sizing_css( $inner, $uid );

			$grid_html = sprintf(
				'%1$s<div class="squad-logo-grid">%2$s</div>',
				'' !== $inline_css ? sprintf( '<style>%s</style>', $inline_css ) : '',
				$child_modules_content
			);

			return DiviModule::render(
				array(
					'orderIndex'          => $block->parsed_block['orderIndex'],
					'storeInstance'       => $block->parsed_block['storeInstance'],
					'attrs'               => $attrs,
					'elements'            => $elements,
					'id'                  => $block->parsed_block['id'],
					'name'                => $block->block_type->name,
					'moduleCategory'      => $block->block_type->category,
					'classnamesFunction'  => array( static::class, 'module_classnames' ),
					'stylesComponent'     => array( static::class, 'module_styles' ),
					'scriptDataComponent' => array( static::class, 'module_script_data' ),
					'children'            => $elements->style_components( array( 'attrName' => 'module' ) ) . $grid_html,
				)
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Logo Grid module' );

			return '';
		}
	}

	/**
	 * Build stable per-instance uid for scoping CSS selectors.
	 *
	 * @since 4.0.0
	 *
	 * @param WP_Block $block The parsed block.
	 *
	 * @return string e.g. "squad-lg-a1b2c3d4e5"
	 */
	protected static function get_instance_uid( WP_Block $block ): string {
		$raw = (string) ( $block->parsed_block['id'] ?? '' );
		$uid = preg_replace( '/[^a-z0-9]/', '', strtolower( $raw ) );

		return '' !== $uid
			? 'squad-lg-' . $uid
			: 'squad-lg-' . substr( md5( $raw . wp_json_encode( $block->parsed_block['orderIndex'] ?? 0 ) ), 0, 10 );
	}

	/**
	 * Generate scoped CSS grid layout for this instance.
	 *
	 * Emits desktop, tablet (≤980px), and phone (≤767px) breakpoints.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $inner Grid innerContent values.
	 * @param string               $uid   Per-instance identifier.
	 *
	 * @return string Raw CSS (no <style> tags).
	 */
	protected static function get_grid_css( array $inner, string $uid ): string {
		$cols_desk = max( 1, absint( $inner['columns'] ?? 4 ) );
		$cols_tab  = max( 1, absint( $inner['columnsTablet'] ?? $cols_desk ) );
		$cols_mob  = max( 1, absint( $inner['columnsPhone'] ?? 2 ) );
		$gap       = max( 0, absint( $inner['gap'] ?? 30 ) );

		$sel = '.' . $uid . ' .squad-logo-grid';

		return "{$sel}{display:grid;grid-template-columns:repeat({$cols_desk},1fr);gap:{$gap}px}"
			. "@media(max-width:980px){{$sel}{grid-template-columns:repeat({$cols_tab},1fr)}}"
			. "@media(max-width:767px){{$sel}{grid-template-columns:repeat({$cols_mob},1fr)}}";
	}

	/**
	 * Generate scoped hover-effect CSS for this instance.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $inner Grid innerContent values.
	 * @param string               $uid   Per-instance identifier.
	 *
	 * @return string Raw CSS (no <style> tags).
	 */
	protected static function get_hover_css( array $inner, string $uid ): string {
		$effect = (string) ( $inner['hoverEffect'] ?? 'grayscale' );
		$uid_sel = '.' . $uid;
		$logo    = "{$uid_sel} .squad-logo-grid__logo";
		$hover   = "{$uid_sel} .squad-logo-grid__item:hover .squad-logo-grid__logo";

		switch ( $effect ) {
			case 'grayscale':
				return "{$logo}{filter:grayscale(100%);transition:filter .3s ease}"
					. "{$hover}{filter:grayscale(0%)}";

			case 'opacity':
				$opacity = max( 0.0, min( 1.0, (float) ( $inner['hoverOpacity'] ?? '0.5' ) ) );
				return "{$logo}{opacity:{$opacity};transition:opacity .3s ease}"
					. "{$hover}{opacity:1}";

			case 'zoom':
				return "{$logo}{transition:transform .3s ease}"
					. "{$hover}{transform:scale(1.1)}";

			default:
				return '';
		}
	}

	/**
	 * Generate scoped logo sizing CSS for this instance.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $inner Grid innerContent values.
	 * @param string               $uid   Per-instance identifier.
	 *
	 * @return string Raw CSS (no <style> tags).
	 */
	protected static function get_sizing_css( array $inner, string $uid ): string {
		$max_width  = self::sanitize_css_length( (string) ( $inner['logoMaxWidth'] ?? '160px' ) );
		$max_height = self::sanitize_css_length( (string) ( $inner['logoMaxHeight'] ?? '80px' ) );

		if ( '' === $max_width && '' === $max_height ) {
			return '';
		}

		$decl = '';
		if ( '' !== $max_width ) {
			$decl .= "max-width:{$max_width};";
		}
		if ( '' !== $max_height ) {
			$decl .= "max-height:{$max_height};";
		}

		return ".{$uid} .squad-logo-grid__logo{{$decl}}";
	}

	/**
	 * Validate a CSS length value against an allowlist of units.
	 *
	 * @since 4.0.0
	 *
	 * @param string $value Raw value.
	 *
	 * @return string Sanitized value or empty string.
	 */
	private static function sanitize_css_length( string $value ): string {
		$value = trim( $value );
		if ( '' === $value ) {
			return '';
		}
		if ( preg_match( '/^\d+(\.\d+)?(px|em|rem|%|vh|vw|vmin|vmax|ch|ex|cm|mm|pt|pc)$/', $value ) ) {
			return $value;
		}
		return '';
	}
}
