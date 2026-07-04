<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Advanced Button Module (Divi 5 / Block API).
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

use DiviSquad\Builder\Shared\Modules\Creative\Advanced_Button\Button_Helper;
use DiviSquad\Builder\Version5\Abstracts\Module;
use DiviSquad\Utils\Divi;
use ET\Builder\FrontEnd\Module\Style;
use ET\Builder\Packages\Module\Layout\Components\ModuleElements\ModuleElements;
use ET\Builder\Packages\Module\Module as DiviModule;
use ET\Builder\Packages\Module\Options\Css\CssStyle;
use ET\Builder\Packages\Module\Options\Element\ElementClassnames;
use Throwable;
use WP_Block;
use function sprintf;
use function trim;
use function wp_kses_post;

/**
 * Advanced Button Module class (Divi 5).
 *
 * @since 4.1.0
 */
class Advanced_Button extends Module {

	/**
	 * Relative path to the generated module.json metadata folder.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/advanced-button/';
	}

	/**
	 * Add the module classnames.
	 *
	 * @since 4.1.0
	 *
	 * @param array<string, mixed> $args Classnames arguments.
	 *
	 * @return void
	 */
	public static function module_classnames( array $args ): void {
		$args['classnamesInstance']->add( 'disq_advanced_button' );
		$args['classnamesInstance']->add(
			ElementClassnames::classnames(
				array( 'attrs' => $args['attrs']['module']['decoration'] ?? array() )
			)
		);
	}

	/**
	 * Assign the module's frontend script data.
	 *
	 * @since 4.1.0
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
									// Icon color.
									array(
										'componentName' => 'divi/common',
										'props'         => array(
											'selector'            => "{$args['orderClass']} .squad-advanced-button__icon .et-pb-icon",
											'attr'                => $attrs['iconDesign']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												$value = $params['attrValue'] ?? array();
												$color = Button_Helper::sanitize_css_background( (string) ( $value['iconColor'] ?? '' ) );

												return '' !== $color ? 'color: ' . $color . ';' : '';
											},
										),
									),
									// Icon size.
									array(
										'componentName' => 'divi/common',
										'props'         => array(
											'selector'            => "{$args['orderClass']} .squad-advanced-button__icon .et-pb-icon",
											'attr'                => $attrs['iconDesign']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												$value = $params['attrValue'] ?? array();
												$size  = self::sanitize_css_length( (string) ( $value['iconSize'] ?? '' ) );

												return '' !== $size ? 'font-size: ' . $size . ';' : '';
											},
										),
									),
									// Icon spacing (gap on the link).
									array(
										'componentName' => 'divi/common',
										'props'         => array(
											'selector'            => "{$args['orderClass']} .squad-advanced-button__link",
											'attr'                => $attrs['iconDesign']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												$value   = $params['attrValue'] ?? array();
												$spacing = self::sanitize_css_length( (string) ( $value['iconSpacing'] ?? '' ) );

												return '' !== $spacing ? 'gap: ' . $spacing . ';' : '';
											},
										),
									),
									// Content alignment (justify-content on the module wrapper).
									array(
										'componentName' => 'divi/common',
										'props'         => array(
											'selector'            => $args['orderClass'],
											'attr'                => $attrs['buttonSettings']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												$value     = $params['attrValue'] ?? array();
												$map       = array(
													'left'   => 'flex-start',
													'center' => 'center',
													'right'  => 'flex-end',
												);
												$alignment = (string) ( $value['contentAlignment'] ?? 'left' );
												$css_value = $map[ $alignment ] ?? 'flex-start';

												return 'justify-content: ' . $css_value . ';';
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
	 * Render callback for the Advanced Button module.
	 *
	 * Reads `buttonSettings.innerContent.desktop.value.*` — the camelCase keys
	 * MUST match the subNames declared in `module.json-source.ts`. See the attr
	 * contract table in the plan header.
	 *
	 * @since 4.1.0
	 *
	 * @param array<string, mixed> $attrs    Block attributes saved by the Visual Builder.
	 * @param string               $content  Inner (child) block content.
	 * @param WP_Block             $block    Parsed block instance.
	 * @param ModuleElements       $elements ModuleElements instance.
	 *
	 * @return string Rendered HTML.
	 */
	public static function render_callback( array $attrs, string $content, WP_Block $block, $elements ): string {
		try {
			$inner = $attrs['buttonSettings']['innerContent']['desktop']['value'] ?? array();

			$button_text    = (string) ( $inner['buttonText'] ?? 'Click Here' );
			$sub_text       = (string) ( $inner['subText'] ?? '' );
			$button_url     = (string) ( $inner['buttonUrl'] ?? '' );
			$url_new_window = 'on' === (string) ( $inner['urlNewWindow'] ?? 'off' );
			$add_nofollow   = 'on' === (string) ( $inner['addNofollow'] ?? 'off' );
			$use_icon       = 'on' === (string) ( $inner['useIcon'] ?? 'off' );
			$icon_placement = (string) ( $inner['iconPlacement'] ?? 'left' );
			$icon_on_hover  = 'on' === (string) ( $inner['iconOnHover'] ?? 'off' );
			$hover_effect   = (string) ( $inner['hoverEffect'] ?? 'none' );

			// Validate allowlisted values.
			if ( ! Button_Helper::is_valid_placement( $icon_placement ) ) {
				$icon_placement = 'left';
			}
			if ( ! Button_Helper::is_valid_hover( $hover_effect ) ) {
				$hover_effect = 'none';
			}

			// Render icon markup.
			$icon_html = '';
			if ( $use_icon ) {
				$icon_char = self::resolve_icon( $inner['icon'] ?? array() );
				if ( '' !== $icon_char ) {
					$icon_html = sprintf(
						'<span class="squad-advanced-button__icon"><span class="et-pb-icon">%s</span></span>',
						esc_html( $icon_char )
					);
				}
			}

			$shell = Button_Helper::build_shell(
				$button_text,
				$sub_text,
				$button_url,
				$url_new_window,
				$add_nofollow,
				$use_icon,
				$icon_html,
				$icon_placement,
				$icon_on_hover,
				$hover_effect
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
					'children'            => $elements->style_components( array( 'attrName' => 'module' ) ) . $shell,
				)
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Advanced Button module' );

			return '';
		}
	}
}
