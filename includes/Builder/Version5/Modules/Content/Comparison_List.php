<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Comparison List Module (Divi 5 / Block API).
 *
 * Native Divi 5 parent module. Accepts Comparison List Item child blocks and
 * wraps them in the same `.squad-comparison-list` shell emitted by the Divi 4
 * module, so output is identical across builders. Each child owns only its
 * `data-status` attribute, label, and nested content (see
 * `Comparison_List_Item`) — this parent owns the three status icons
 * (included / excluded / neutral) and their colors, and paints the correct
 * glyph onto every row via CSS attribute selectors keyed off `data-status`,
 * plus responsive column count, row gap, divider, and zebra-striping CSS.
 *
 * @since   4.4.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version5\Modules\Content;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

if ( ! class_exists( 'ET\Builder\Packages\Module\Module' ) ) {
	return;
}

use DiviSquad\Builder\Version5\Abstracts\Module;
use DiviSquad\Utils\Divi;
use ET\Builder\FrontEnd\Module\Style;
use ET\Builder\Packages\Module\Layout\Components\ModuleElements\ModuleElements;
use ET\Builder\Packages\Module\Module as DiviModule;
use ET\Builder\Packages\Module\Options\Css\CssStyle;
use ET\Builder\Packages\Module\Options\Element\ElementClassnames;
use Throwable;
use WP_Block;
use function absint;
use function esc_attr;
use function esc_html__;
use function et_pb_get_extended_font_icon_value;
use function in_array;
use function max;
use function sprintf;
use function wp_json_encode;

/**
 * Comparison List parent module class.
 *
 * @since 4.4.0
 */
class Comparison_List extends Module {

	/**
	 * Allowed row status values (must match `Comparison_List_Item::STATUSES`).
	 *
	 * @since 4.4.0
	 *
	 * @var array<int, string>
	 */
	private const STATUSES = array( 'included', 'excluded', 'neutral' );

	/**
	 * Allowed row divider line style tokens (first = fallback).
	 *
	 * @since 4.4.0
	 *
	 * @var array<int, string>
	 */
	private const DIVIDER_STYLES = array( 'solid', 'dashed', 'dotted' );

	/**
	 * Allowed icon position tokens (first = fallback).
	 *
	 * @since 4.4.0
	 *
	 * @var array<int, string>
	 */
	private const ICON_POSITIONS = array( 'left', 'right' );

	/**
	 * Default (ETModules extended-icon value) for the included status: a
	 * check mark.
	 *
	 * @since 4.4.0
	 *
	 * @var string
	 */
	private const DEFAULT_INCLUDED_ICON = '&#x4e;||divi||400';

	/**
	 * Default (ETModules extended-icon value) for the excluded status: a
	 * cross / times.
	 *
	 * @since 4.4.0
	 *
	 * @var string
	 */
	private const DEFAULT_EXCLUDED_ICON = '&#x4d;||divi||400';

	/**
	 * Default (ETModules extended-icon value) for the neutral status: a
	 * minus / dash.
	 *
	 * @since 4.4.0
	 *
	 * @var string
	 */
	private const DEFAULT_NEUTRAL_ICON = '&#x4b;||divi||400';

	/**
	 * Relative path to the generated module.json metadata folder.
	 *
	 * @since 4.4.0
	 *
	 * @return string
	 */
	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/comparison-list/';
	}

	/**
	 * Add the module classnames.
	 *
	 * @since 4.4.0
	 *
	 * @param array<string, mixed> $args Classnames arguments.
	 *
	 * @return void
	 */
	public static function module_classnames( array $args ): void {
		$args['classnamesInstance']->add( 'disq_comparison_list' );
		$args['classnamesInstance']->add(
			ElementClassnames::classnames(
				array( 'attrs' => $args['attrs']['module']['decoration'] ?? array() )
			)
		);
	}

	/**
	 * Assign the module's frontend script data.
	 *
	 * @since 4.4.0
	 *
	 * @param array<string, mixed> $args Script data arguments.
	 *
	 * @return void
	 */
	public static function module_script_data( array $args ): void {
		$args['elements']->script_data( array( 'attrName' => 'module' ) );
	}

	/**
	 * Register the module style declarations.
	 *
	 * @since 4.4.0
	 *
	 * @param array<string, mixed> $args Style arguments provided by Divi.
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
						array( 'selector' => $args['orderClass'], 'attr' => $attrs['css'] ?? array() )
					),
				),
			)
		);
	}

	/**
	 * Render callback for the Comparison List module.
	 *
	 * @since 4.4.0
	 *
	 * @param array<string, mixed> $attrs                 Block attributes.
	 * @param string               $child_modules_content Rendered child HTML.
	 * @param WP_Block             $block                 Parsed block instance.
	 * @param ModuleElements       $elements              ModuleElements instance.
	 *
	 * @return string Rendered HTML.
	 */
	public static function render_callback( array $attrs, string $child_modules_content, WP_Block $block, $elements ): string {
		try {
			if ( '' === trim( $child_modules_content ) ) {
				return sprintf(
					'<div class="squad-notice">%s</div>',
					esc_html__( 'Add at least one Comparison List Item.', 'squad-modules-for-divi' )
				);
			}

			// Parent settings are packed under the `comparisonList.innerContent`
			// group (same convention as Data Table's `dataTable.innerContent`).
			$inner        = $attrs['comparisonList']['innerContent']['desktop']['value'] ?? array();
			$inner_tablet = $attrs['comparisonList']['innerContent']['tablet']['value'] ?? array();
			$inner_phone  = $attrs['comparisonList']['innerContent']['phone']['value'] ?? array();

			$icon_position = (string) ( $inner['iconPosition'] ?? 'left' );
			$icon_position = in_array( $icon_position, self::ICON_POSITIONS, true ) ? $icon_position : 'left';

			$divider = 'off' === (string) ( $inner['rowDivider'] ?? 'on' ) ? 'off' : 'on';

			$uid        = self::get_instance_uid( $block );
			$inline_css = self::get_layout_css( $inner, $inner_tablet, $inner_phone, $uid )
				. self::get_row_css( $inner, $uid )
				. self::get_icon_css( $inner, $uid );

			$comparison_list_html = ( '' !== $inline_css ? sprintf( '<style>%s</style>', $inline_css ) : '' )
				. sprintf(
					'<div class="%1$s squad-comparison-list squad-comparison-list--icon-%2$s" data-divider="%3$s">%4$s</div>',
					esc_attr( $uid ),
					esc_attr( $icon_position ),
					esc_attr( $divider ),
					$child_modules_content
				);

			$style_components = $elements instanceof ModuleElements
				? (string) $elements->style_components( array( 'attrName' => 'module' ) )
				: '';

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
					'children'            => $style_components . $comparison_list_html,
				)
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Comparison List module' );

			return '';
		}
	}

	/**
	 * Build a stable per-instance uid for scoping layout/icon CSS selectors.
	 *
	 * @since 4.4.0
	 *
	 * @param WP_Block $block The parsed block.
	 *
	 * @return string
	 */
	protected static function get_instance_uid( WP_Block $block ): string {
		$raw = (string) ( $block->parsed_block['id'] ?? '' );
		$uid = preg_replace( '/[^a-z0-9]/', '', strtolower( $raw ) );

		return '' !== $uid
			? 'squad-cl-' . $uid
			: 'squad-cl-' . substr( md5( $raw . wp_json_encode( $block->parsed_block['orderIndex'] ?? 0 ) ), 0, 10 );
	}

	/**
	 * Generate scoped responsive column count, row gap, and icon size custom
	 * properties for this instance. These are consumed by the static CSS as
	 * `var( --squad-cl-columns )`, etc., rather than being hardcoded here, so
	 * the same variable names can be shared with the Divi 4 output.
	 *
	 * @since 4.4.0
	 *
	 * @param array<string, mixed> $inner        Packed `comparisonList.innerContent` desktop values.
	 * @param array<string, mixed> $inner_tablet Packed `comparisonList.innerContent` tablet values.
	 * @param array<string, mixed> $inner_phone  Packed `comparisonList.innerContent` phone values.
	 * @param string                $uid          Per-instance identifier.
	 *
	 * @return string Raw CSS (no <style> tags).
	 */
	protected static function get_layout_css( array $inner, array $inner_tablet, array $inner_phone, string $uid ): string {
		$cols_desktop = max( 1, absint( $inner['columns'] ?? 1 ) );

		$row_gap = self::sanitize_css_length( (string) ( $inner['rowGap'] ?? '12px' ), '12px' );

		$icon_size = self::sanitize_css_length( (string) ( $inner['iconSize'] ?? '20px' ), '20px' );

		$css = sprintf(
			'.%1$s.squad-comparison-list{--squad-cl-columns:%2$d;--squad-cl-row-gap:%3$s;--squad-cl-icon-size:%4$s}',
			$uid,
			$cols_desktop,
			$row_gap,
			$icon_size
		);

		if ( isset( $inner_tablet['columns'] ) ) {
			$cols_tablet = max( 1, absint( $inner_tablet['columns'] ) );
			$css        .= sprintf(
				'@media(max-width:980px){.%1$s.squad-comparison-list{--squad-cl-columns:%2$d}}',
				$uid,
				$cols_tablet
			);
		}

		if ( isset( $inner_phone['columns'] ) ) {
			$cols_phone = max( 1, absint( $inner_phone['columns'] ) );
			$css       .= sprintf(
				'@media(max-width:767px){.%1$s.squad-comparison-list{--squad-cl-columns:%2$d}}',
				$uid,
				$cols_phone
			);
		}

		return $css;
	}

	/**
	 * Generate scoped row background, zebra-stripe, and divider custom
	 * properties for this instance.
	 *
	 * @since 4.4.0
	 *
	 * @param array<string, mixed> $inner Packed `comparisonList.innerContent` desktop values.
	 * @param string               $uid   Per-instance identifier.
	 *
	 * @return string Raw CSS (no <style> tags).
	 */
	protected static function get_row_css( array $inner, string $uid ): string {
		$declarations = array();

		$bg = self::sanitize_css_background( (string) ( $inner['rowBackground'] ?? '' ) );
		if ( '' !== $bg ) {
			$declarations[] = "--squad-cl-row-bg:{$bg}";
		}

		$bg_alt = self::sanitize_css_background( (string) ( $inner['rowBackgroundAlt'] ?? '' ) );
		if ( '' !== $bg_alt ) {
			$declarations[] = "--squad-cl-row-bg-alt:{$bg_alt}";
		}

		if ( 'off' !== (string) ( $inner['rowDivider'] ?? 'on' ) ) {
			$divider_color = self::sanitize_css_background( (string) ( $inner['rowDividerColor'] ?? '#e5e7eb' ) );
			if ( '' === $divider_color ) {
				$divider_color = '#e5e7eb';
			}
			$declarations[] = "--squad-cl-row-divider-color:{$divider_color}";

			$divider_width   = self::sanitize_css_length( (string) ( $inner['rowDividerWidth'] ?? '1px' ), '1px' );
			$declarations[] = "--squad-cl-row-divider-width:{$divider_width}";

			$divider_style = (string) ( $inner['rowDividerStyle'] ?? 'solid' );
			$divider_style = in_array( $divider_style, self::DIVIDER_STYLES, true ) ? $divider_style : 'solid';
			$declarations[] = "--squad-cl-row-divider-style:{$divider_style}";
		}

		if ( array() === $declarations ) {
			return '';
		}

		return sprintf( '.%1$s.squad-comparison-list{%2$s}', $uid, implode( ';', $declarations ) );
	}

	/**
	 * Generate the per-status icon glyph and color CSS for all three states.
	 *
	 * @since 4.4.0
	 *
	 * @param array<string, mixed> $inner Packed `comparisonList.innerContent` desktop values.
	 * @param string               $uid   Per-instance identifier.
	 *
	 * @return string Raw CSS (no <style> tags).
	 */
	protected static function get_icon_css( array $inner, string $uid ): string {
		return self::get_row_icon_css( $inner, $uid, 'included', 'includedIcon', 'includedIconColor', self::DEFAULT_INCLUDED_ICON, '#2ecc71' )
			. self::get_row_icon_css( $inner, $uid, 'excluded', 'excludedIcon', 'excludedIconColor', self::DEFAULT_EXCLUDED_ICON, '#e74c3c' )
			. self::get_row_icon_css( $inner, $uid, 'neutral', 'neutralIcon', 'neutralIconColor', self::DEFAULT_NEUTRAL_ICON, '#9aa0a6' );
	}

	/**
	 * Build the CSS glyph + color custom property for one comparison-list
	 * status.
	 *
	 * `Comparison_List_Item` deliberately renders an empty, `aria-hidden`
	 * icon span and tags its row with `data-status` (see that class's
	 * `render_callback()`) rather than printing the glyph itself — this
	 * parent owns the icon/color configuration for all three states and
	 * paints every row sharing a status identically via the
	 * `[data-status="…"]` attribute selector, so no HTML string-parsing of
	 * the already-rendered child content is required.
	 *
	 * The author picks each state's icon with the native Divi icon picker
	 * (`type => 'select_icon'`). The raw extended-icon value is resolved to a
	 * glyph exactly as the Divi 4 parent (and `Inline_Content_Item`) do —
	 * `Divi::inject_fa_icons()` (loads the relevant icon font) then
	 * `et_pb_get_extended_font_icon_value()`. Because the resolved glyph is
	 * interpolated into a CSS `content` string (not HTML text, so
	 * `esc_html()` can't apply) it is first run through
	 * `sanitize_css_background()` to strip any character that could break
	 * out of the declaration — the raw picker value is never written to CSS
	 * unvalidated.
	 *
	 * Named `get_row_icon_css()` rather than `render_image()`/`render_button()`
	 * to avoid colliding with `ET_Builder_Element`'s reserved method names.
	 *
	 * @since 4.4.0
	 *
	 * @param array<string, mixed> $inner         Packed `comparisonList.innerContent` desktop values.
	 * @param string               $uid           Per-instance identifier.
	 * @param string               $status        One of self::STATUSES.
	 * @param string               $icon_field    Attr key holding the extended-icon value.
	 * @param string               $color_field   Attr key holding the icon color.
	 * @param string               $default_icon  Fallback extended-icon value.
	 * @param string               $default_color Fallback icon color.
	 *
	 * @return string Raw CSS (no <style> tags).
	 */
	protected static function get_row_icon_css( array $inner, string $uid, string $status, string $icon_field, string $color_field, string $default_icon, string $default_color ): string {
		if ( ! in_array( $status, self::STATUSES, true ) ) {
			return '';
		}

		$css = '';

		$icon_raw = (string) ( $inner[ $icon_field ] ?? $default_icon );
		if ( '' === $icon_raw ) {
			$icon_raw = $default_icon;
		}

		Divi::inject_fa_icons( $icon_raw );
		$icon_glyph = (string) et_pb_get_extended_font_icon_value( $icon_raw, true );

		// Strip any CSS-breakout characters before interpolating the
		// resolved glyph into a `content` declaration.
		$icon_glyph = self::sanitize_css_background( $icon_glyph );
		if ( '' !== $icon_glyph ) {
			$css .= sprintf(
				'.%1$s .squad-comparison-list__item[data-status="%2$s"] .squad-comparison-list__icon::before{content:"%3$s";font-family:"ETModules"}',
				$uid,
				$status,
				$icon_glyph
			);
		}

		$color = self::sanitize_css_background( (string) ( $inner[ $color_field ] ?? $default_color ) );
		if ( '' === $color ) {
			$color = $default_color;
		}

		$css .= sprintf(
			'.%1$s .squad-comparison-list__item[data-status="%2$s"]{--squad-cl-icon-color:%3$s}',
			$uid,
			$status,
			$color
		);

		return $css;
	}
}
