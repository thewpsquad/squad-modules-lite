<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Scrolling Text Module (Divi 5 / Block API).
 *
 * Native Divi 5 implementation of the Scrolling Text module. Renders the
 * jQuery-marquee driven markup server-side via the render callback and enqueues
 * the existing D4 frontend script so the scrolling effect works on the frontend.
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
use function wp_enqueue_script;

/**
 * Scrolling Text Module class.
 *
 * @since 3.4.0
 */
class Scrolling_Text extends Module {

	/**
	 * Relative path to the generated module.json metadata folder.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/scrolling-text/';
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
		$args['classnamesInstance']->add( 'disq_scrolling_text' );
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
					// Scrolling element — optional outline text effect.
					$elements->style(
						array(
							'attrName'   => 'content',
							'styleProps' => array(
								'advancedStyles' => array(
									array(
										'componentName' => 'divi/common',
										'props'         => array(
											'selector'            => "{$args['orderClass']} .text-elements .scrolling-element",
											'attr'                => $attrs['content']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												$value = $params['attrValue'] ?? array();

												return ( 'on' === ( $value['outlineText'] ?? '' ) )
													? '-webkit-text-stroke-width: 1px; -webkit-text-stroke-color: inherit; -webkit-text-fill-color: transparent;'
													: '';
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
	 * Render callback for the Scrolling Text module.
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
			$inner          = $attrs['content']['innerContent']['desktop']['value'] ?? array();
			$scrolling_text = $inner['scrollingText'] ?? '';

			if ( '' === $scrolling_text ) {
				return '';
			}

			$allowed_tags = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span', 'div' );
			$text_tag     = $inner['textTag'] ?? 'h2';
			if ( ! in_array( $text_tag, $allowed_tags, true ) ) {
				$text_tag = 'h2';
			}

			// Enqueue the jQuery-marquee driven frontend scripts.
			wp_enqueue_script( 'squad-vendor-scrolling-text' );
			wp_enqueue_script( 'squad-module-scrolling-text' );

			$html = sprintf(
				'<div class="text-elements et_pb_with_background"><%1$s class="scrolling-element" data-scroll-direction="%3$s" data-scroll-speed="%4$s" data-repeat-text="%5$s" data-scroll-pause="%6$s">%2$s</%1$s></div>',
				esc_attr( $text_tag ),
				esc_html( $scrolling_text ),
				esc_attr( $inner['direction'] ?? 'left' ),
				esc_attr( (string) ( $inner['speed'] ?? '' ) ),
				esc_attr( $inner['repeatText'] ?? 'off' ),
				esc_attr( $inner['pauseOnHover'] ?? 'off' )
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
					'classnamesFunction'  => array( self::class, 'module_classnames' ),
					'stylesComponent'     => array( self::class, 'module_styles' ),
					'scriptDataComponent' => array( self::class, 'module_script_data' ),
					'children'            => $style_components . $html,
				)
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Scrolling Text module' );

			return '';
		}
	}
}
