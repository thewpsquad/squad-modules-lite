<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Glitch Text Module (Divi 5 / Block API).
 *
 * @since   3.4.0
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

use DiviSquad\Builder\Version5\Abstracts\Module;
use ET\Builder\FrontEnd\Module\Style;
use ET\Builder\Packages\Module\Layout\Components\ModuleElements\ModuleElements;
use ET\Builder\Packages\Module\Module as DiviModule;
use ET\Builder\Packages\Module\Options\Css\CssStyle;
use ET\Builder\Packages\Module\Options\Element\ElementClassnames;
use Throwable;
use WP_Block;
use function esc_attr;
use function esc_html;
use function in_array;
use function sprintf;
use function wp_kses_post;

/**
 * Glitch Text Module class.
 *
 * @since 3.4.0
 */
class Glitch_Text extends Module {

	/**
	 * Relative path to the generated module.json metadata folder.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/glitch-text/';
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
		$args['classnamesInstance']->add( 'disq_glitch_text' );
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
								'disabledOn' => array(
									'disabledModuleVisibility' => $settings['disabledModuleVisibility'] ?? null,
								),
							),
						)
					),
					// Glitch — per-instance color custom properties for the active effect.
					$elements->style(
						array(
							'attrName'   => 'content',
							'styleProps' => array(
								'advancedStyles' => array(
									array(
										'componentName' => 'divi/common',
										'props'         => array(
											'selector'            => "{$args['orderClass']} .glitch-text-wrapper .glitch-text-element",
											'attr'                => $attrs['content']['innerContent'] ?? array(),
											'declarationFunction' => static function ( array $params ): string {
												$value        = $params['attrValue'] ?? array();
												$value        = is_array( $value ) ? $value : array();
												$declarations = '';

												$primary_color = isset( $value['primaryColor'] ) ? (string) $value['primaryColor'] : '';
												if ( '' !== $primary_color ) {
													$declarations .= '--squad-gt-color-primary: ' . self::sanitize_css_background( $primary_color ) . ';';
												}

												$secondary_color = isset( $value['secondaryColor'] ) ? (string) $value['secondaryColor'] : '';
												if ( '' !== $secondary_color ) {
													$declarations .= '--squad-gt-color-secondary: ' . self::sanitize_css_background( $secondary_color ) . ';';
												}

												return $declarations;
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
	 * Render callback for the Glitch Text module.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $attrs    Block attributes.
	 * @param string               $content  Inner content.
	 * @param WP_Block             $block    Parsed block instance.
	 * @param ModuleElements       $elements ModuleElements instance.
	 *
	 * @return string Rendered HTML.
	 */
	public static function render_callback( array $attrs, string $content, WP_Block $block, $elements ): string {
		try {
			$inner = $attrs['content']['innerContent']['desktop']['value'] ?? array();

			$glitch_text = $inner['glitchText'] ?? '';

			if ( '' === $glitch_text ) {
				return '';
			}

			$glitch_effect = $inner['glitchEffect'] ?? 'one';
			$glitch_tag    = $inner['glitchTag'] ?? 'p';

			// Restrict the tag to a safe allowlist.
			if ( ! in_array( $glitch_tag, array( 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'span', 'div' ), true ) ) {
				$glitch_tag = 'p';
			}

			$text_node = esc_html( $glitch_text );

			// Effect 3: Wrap text with span tag.
			if ( 'three' === $glitch_effect ) {
				$text_node = "<span>$text_node</span>";
			}

			// Effect 5: Wrap text with span tags.
			if ( 'five' === $glitch_effect ) {
				$text_node = "<span style='--squad-gte-index: 0;'>$text_node</span><span style='--squad-gte-index: 1;'>$text_node</span><span style='--squad-gte-index: 2;'>$text_node</span>";
			}

			$html = sprintf(
				'<div class="glitch-text-wrapper et_pb_with_background %3$s"><%4$s class="glitch-text-element" data-text="%2$s">%1$s</%4$s></div>',
				wp_kses_post( $text_node ),
				esc_attr( $glitch_text ),
				esc_attr( $glitch_effect ),
				esc_attr( $glitch_tag )
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
					'classnamesFunction'  => array( self::class, 'module_classnames' ),
					'stylesComponent'     => array( self::class, 'module_styles' ),
					'scriptDataComponent' => array( self::class, 'module_script_data' ),
					'children'            => $elements->style_components( array( 'attrName' => 'module' ) ) . $html,
				)
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Glitch Text module' );

			return '';
		}
	}

}
