<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Gradient Text Module (Divi 5 / Block API).
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
use ET\Builder\Packages\Module\Module as DiviModule;
use ET\Builder\Packages\Module\Options\Css\CssStyle;
use ET\Builder\Packages\Module\Options\Element\ElementClassnames;
use Throwable;
use WP_Block;
use function esc_attr;
use function wp_kses_post;

/**
 * Gradient Text Module class.
 *
 * @since 3.4.0
 */
class Gradient_Text extends Module {

	/**
	 * Relative path to the generated module.json metadata folder.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/gradient-text/';
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
	 * Build the CSS gradient image declaration from the inner content values.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $inner Inner content values.
	 *
	 * @return string
	 */
	private static function build_gradient( array $inner ): string {
		$type      = $inner['gradientType'] ?? 'linear';
		$direction = $inner['gradientDirection'] ?? '90deg';
		$start     = $inner['gradientStart'] ?? '#1f7016';
		$end       = $inner['gradientEnd'] ?? '#29c4a9';

		if ( 'radial' === $type ) {
			return sprintf( 'radial-gradient(circle, %1$s 0%%, %2$s 100%%)', $start, $end );
		}

		return sprintf( 'linear-gradient(%1$s, %2$s 0%%, %3$s 100%%)', $direction, $start, $end );
	}

	/**
	 * Render callback for the Gradient Text module.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $attrs    Block attributes.
	 * @param string               $content  Inner content.
	 * @param WP_Block             $block    Parsed block instance.
	 * @param object               $elements ModuleElements instance.
	 *
	 * @return string Rendered HTML.
	 */
	public static function render_callback( array $attrs, string $content, WP_Block $block, $elements ): string {
		try {
			$inner = $attrs['content']['innerContent']['desktop']['value'] ?? array();
			$text  = $inner['gradientText'] ?? '';

			if ( '' === $text ) {
				return '';
			}

			$tag      = $inner['gradientTextTag'] ?? 'p';
			$gradient = self::build_gradient( $inner );

			$style = sprintf(
				'background-image:%1$s;-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;',
				esc_attr( $gradient )
			);

			$html = sprintf(
				'<div class="gradient-text-wrapper et_pb_with_background"><%2$s class="gradient-text-element" style="%3$s"><span>%1$s</span></%2$s></div>',
				wp_kses_post( $text ),
				wp_kses_post( $tag ),
				esc_attr( $style )
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
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Gradient Text module' );

			return '';
		}
	}
}
