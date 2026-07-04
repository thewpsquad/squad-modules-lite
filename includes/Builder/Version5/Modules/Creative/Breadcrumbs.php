<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Breadcrumbs Module (Divi 5 / Block API).
 *
 * Native Divi 5 implementation of the Breadcrumbs module. The breadcrumb trail
 * is built server-side from the current page/post context via the render
 * callback, reusing the Divi 4 breadcrumbs helper so the generated markup stays
 * identical to the Divi 4 module.
 *
 * @since   3.4.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version5\Modules\Creative;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

// Bail when the Divi 5 framework is not present (e.g. running under Divi 4).
if ( ! class_exists( 'ET\Builder\Packages\Module\Module' ) ) {
	return;
}

use DiviSquad\Builder\Shared\Supports\Module_Utilities\Breadcrumbs as Breadcrumbs_Utility;
use DiviSquad\Builder\Version5\Abstracts\Module;
use DiviSquad\Utils\Divi;
use ET\Builder\FrontEnd\Module\Style;
use ET\Builder\Packages\IconLibrary\IconFont\Utils as IconFontUtils;
use ET\Builder\Packages\Module\Layout\Components\ModuleElements\ModuleElements;
use ET\Builder\Packages\Module\Module as DiviModule;
use ET\Builder\Packages\Module\Options\Css\CssStyle;
use ET\Builder\Packages\Module\Options\Element\ElementClassnames;
use Throwable;
use WP_Block;
use function esc_attr;
use function esc_html;
use function esc_html__;

/**
 * Breadcrumbs Module class.
 *
 * @since 3.4.0
 */
class Breadcrumbs extends Module {

	/**
	 * Relative path to the generated module.json metadata folder.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/breadcrumbs/';
	}

	/**
	 * Add the module classnames.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $args Classnames arguments.
	 *
	 * @return void
	 */
	public static function module_classnames( array $args ): void {
		$args['classnamesInstance']->add( 'disq_breadcrumbs' );
		$args['classnamesInstance']->add(
			ElementClassnames::classnames(
				array(
					'attrs' => $args['attrs']['module']['decoration'] ?? array(),
				)
			)
		);
	}

	/**
	 * Assign the module's frontend script data.
	 *
	 * @since 3.4.0
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
	 * Register the module style declarations.
	 *
	 * @since 3.4.0
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
								'disabledOn'     => array(
									'disabledModuleVisibility' => $settings['disabledModuleVisibility'] ?? null,
								),
								'advancedStyles' => array(
									array(
										'componentName' => 'divi/common',
										'props'         => array(
											'selector'            => "{$args['orderClass']} .breadcrumbs .breadcrumb-list a",
											'attr'                => $attrs['colors']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												$value = $params['attrValue'] ?? array();

												$color = self::sanitize_css_background( (string) ( $value['linkColor'] ?? '' ) );
															return '' !== $color ? 'color: ' . $color . ';' : '';
											},
										),
									),
									array(
										'componentName' => 'divi/common',
										'props'         => array(
											'selector'            => "{$args['orderClass']} .breadcrumbs .separator",
											'attr'                => $attrs['colors']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												$value = $params['attrValue'] ?? array();

												$color = self::sanitize_css_background( (string) ( $value['separatorColor'] ?? '' ) );
															return '' !== $color ? 'color: ' . $color . ';' : '';
											},
										),
									),
									array(
										'componentName' => 'divi/common',
										'props'         => array(
											'selector'            => "{$args['orderClass']} .breadcrumbs .current",
											'attr'                => $attrs['colors']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												$value = $params['attrValue'] ?? array();

												$color = self::sanitize_css_background( (string) ( $value['currentTextColor'] ?? '' ) );
															return '' !== $color ? 'color: ' . $color . ';' : '';
											},
										),
									),
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
	 * Render callback for the Breadcrumbs module.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $attrs    Block attributes saved by the Visual Builder.
	 * @param string               $content  Inner (child) block content.
	 * @param WP_Block             $block    Parsed block instance.
	 * @param object               $elements ModuleElements instance.
	 *
	 * @return string Rendered HTML.
	 */
	public static function render_callback( array $attrs, string $content, WP_Block $block, $elements ): string {
		try {
			$breadcrumbs_html = self::render_breadcrumbs( $attrs );

			$style_components = $elements instanceof ModuleElements
				? $elements->style_components( array( 'attrName' => 'module' ) )
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
					'classnamesFunction'  => array( self::class, 'module_classnames' ),
					'stylesComponent'     => array( self::class, 'module_styles' ),
					'scriptDataComponent' => array( self::class, 'module_script_data' ),
					'children'            => $style_components . $breadcrumbs_html,
				)
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Breadcrumbs module' );

			return '';
		}
	}

	/**
	 * Build the breadcrumbs markup from the current page/post context.
	 *
	 * Mirrors the Divi 4 module render: reuses the shared breadcrumbs helper to
	 * generate the trail and wraps it in the same `.breadcrumbs` container.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $attrs Block attributes.
	 *
	 * @return string
	 */
	private static function render_breadcrumbs( array $attrs ): string {
		$content   = $attrs['content']['innerContent']['desktop']['value'] ?? array();
		$separator = $attrs['separator']['innerContent']['desktop']['value'] ?? array();

		$home_text   = '' !== ( $content['homeText'] ?? '' ) ? $content['homeText'] : esc_html__( 'Home', 'squad-modules-for-divi' );
		$before_text = $content['beforeText'] ?? '';

		// Decode the separator icon to its unicode glyph.
		$icon_attr = $separator['icon'] ?? array();
		$delimiter = self::process_icon( $icon_attr );
		if ( '' === $delimiter ) {
			$delimiter = '&#x39;';
		}

		// Divi icon fallback support.
		Divi::inject_fa_icons( '&#x39;||divi||400' );

		// Generate the breadcrumb trail from the current context.
		$breadcrumbs_utility = new Breadcrumbs_Utility();
		$breadcrumbs         = $breadcrumbs_utility->get_hansel_and_gretel(
			esc_html( $home_text ),
			esc_html( $before_text ),
			esc_html( $delimiter )
		);

		return sprintf(
			'<div class="breadcrumbs"> %1$s</div>',
			$breadcrumbs
		);
	}

	/**
	 * Decode a Divi 5 icon-picker value into its unicode glyph.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed>|string $icon Icon attribute value.
	 *
	 * @return string
	 */
	private static function process_icon( $icon ): string {
		if ( ! is_array( $icon ) || array() === $icon ) {
			return '';
		}

		if ( ! class_exists( IconFontUtils::class ) ) {
			return '';
		}

		try {
			$processed = IconFontUtils::process_font_icon( $icon );
		} catch ( Throwable $e ) {
			return '';
		}

		return is_string( $processed ) ? $processed : '';
	}

}
