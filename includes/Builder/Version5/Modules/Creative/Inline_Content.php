<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Inline Content Module (Divi 5 / Block API).
 *
 * Parent module for a row of mixed inline items. CSS-only; no frontend JS.
 *
 * @since   4.1.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version5\Modules\Creative;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

if ( ! class_exists( 'ET\Builder\Packages\Module\Module' ) ) {
	return;
}

use DiviSquad\Builder\Shared\Modules\Creative\Inline_Content\Inline_Helper;
use DiviSquad\Builder\Version5\Abstracts\Module;
use ET\Builder\FrontEnd\Module\Style;
use ET\Builder\Packages\Module\Layout\Components\ModuleElements\ModuleElements;
use ET\Builder\Packages\Module\Module as DiviModule;
use ET\Builder\Packages\Module\Options\Css\CssStyle;
use ET\Builder\Packages\Module\Options\Element\ElementClassnames;
use Throwable;
use WP_Block;
use function esc_attr;
use function esc_html__;
use function sprintf;
use function trim;

/**
 * Inline Content parent module class (Divi 5).
 *
 * @since 4.1.0
 */
class Inline_Content extends Module {

	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/inline-content/';
	}

	/**
	 * Apply module classnames.
	 *
	 * @since 4.1.0
	 *
	 * @param array<string, mixed> $args Classnames arguments provided by Divi.
	 *
	 * @return void
	 */
	public static function module_classnames( array $args ): void {
		$args['classnamesInstance']->add( 'disq_inline_content' );
		$args['classnamesInstance']->add(
			ElementClassnames::classnames(
				array( 'attrs' => $args['attrs']['module']['decoration'] ?? array() )
			)
		);
	}

	/**
	 * Register module script data.
	 *
	 * @since 4.1.0
	 *
	 * @param array<string, mixed> $args Script data arguments provided by Divi.
	 *
	 * @return void
	 */
	public static function module_script_data( array $args ): void {
		$args['elements']->script_data( array( 'attrName' => 'module' ) );
	}

	/**
	 * Apply module styles.
	 *
	 * @since 4.1.0
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
											'selector'            => "{$args['orderClass']} .squad-inline",
											'attr'                => $attrs['layoutSettings']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												$value   = $params['attrValue'] ?? array();
												$col_gap = self::sanitize_css_length( (string) ( $value['columnGap'] ?? '' ) );

												return '' !== $col_gap ? "column-gap:{$col_gap};" : '';
											},
										),
									),
									array(
										'componentName' => 'divi/common',
										'props'         => array(
											'selector'            => "{$args['orderClass']} .squad-inline",
											'attr'                => $attrs['layoutSettings']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												$value   = $params['attrValue'] ?? array();
												$row_gap = self::sanitize_css_length( (string) ( $value['rowGap'] ?? '' ) );

												return '' !== $row_gap ? "row-gap:{$row_gap};" : '';
											},
										),
									),
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
	 * Render callback for the Inline Content (parent) module.
	 *
	 * Reads camelCase keys from `layoutSettings.innerContent.desktop.value.*`.
	 * These MUST match the subNames in module.json-source.ts (see the attr
	 * contract table at the top of this plan). No frontend JS; no script entry.
	 *
	 * @since 4.1.0
	 *
	 * @param array<string, mixed> $attrs                 Block attributes.
	 * @param string               $child_modules_content Rendered children HTML.
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
					esc_html__( 'Add at least one Inline Content Item.', 'squad-modules-for-divi' )
				);
			}

			// camelCase keys — MUST match subNames in module.json-source.ts.
			$inner = $attrs['layoutSettings']['innerContent']['desktop']['value'] ?? array();

			$raw_align  = (string) ( $inner['contentAlignment'] ?? 'left' );
			$raw_valign = (string) ( $inner['verticalAlign'] ?? 'center' );

			// Validate against allowlists — never inject raw values into class names.
			$align  = Inline_Helper::is_valid_align( $raw_align ) ? $raw_align : 'left';
			$valign = Inline_Helper::is_valid_valign( $raw_valign ) ? $raw_valign : 'center';

			$wrapper_html = sprintf(
				'<div class="squad-inline squad-inline--align-%1$s squad-inline--valign-%2$s">%3$s</div>',
				esc_attr( $align ),
				esc_attr( $valign ),
				$child_modules_content
			);

			$style_components = $elements instanceof ModuleElements
				? ( $elements->style_components( array( 'attrName' => 'module' ) ) ?? '' )
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
					'children'            => $style_components . $wrapper_html,
				)
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Inline Content module' );

			return '';
		}
	}
}
