<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Image Hotspots Module (Divi 5 / Block API).
 *
 * Native Divi 5 parent module. Accepts Image Hotspot Pin child blocks and wraps
 * them in the same `.squad-hotspots` canvas emitted by the Divi 4 module, so
 * output is identical across builders. Pure CSS layout + a tiny vanilla frontend
 * engine (`image-hotspots.ts`). No external lib dependency.
 *
 * @since   4.3.0
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

use DiviSquad\Builder\Shared\Modules\Media\Image_Hotspots\Image_Hotspots_Helper;
use DiviSquad\Builder\Version5\Abstracts\Module;
use ET\Builder\FrontEnd\Module\Style;
use ET\Builder\Packages\Module\Layout\Components\ModuleElements\ModuleElements;
use ET\Builder\Packages\Module\Module as DiviModule;
use ET\Builder\Packages\Module\Options\Css\CssStyle;
use ET\Builder\Packages\Module\Options\Element\ElementClassnames;
use Throwable;
use WP_Block;
use function esc_attr;
use function wp_enqueue_script;
use function wp_json_encode;

/**
 * Image Hotspots parent module class.
 *
 * @since 4.3.0
 */
class Image_Hotspots extends Module {

	/**
	 * Relative path to the generated module.json metadata folder.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/image-hotspots/';
	}

	/**
	 * Add the module classnames.
	 *
	 * @since 4.3.0
	 *
	 * @param array<string, mixed> $args Classnames arguments.
	 *
	 * @return void
	 */
	public static function module_classnames( array $args ): void {
		$args['classnamesInstance']->add( 'disq_image_hotspots' );
		$args['classnamesInstance']->add(
			ElementClassnames::classnames(
				array( 'attrs' => $args['attrs']['module']['decoration'] ?? array() )
			)
		);
	}

	/**
	 * Assign the module's frontend script data.
	 *
	 * @since 4.3.0
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
	 * @since 4.3.0
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
	 * Render callback for the Image Hotspots module.
	 *
	 * @since 4.3.0
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
			wp_enqueue_script( 'squad-module-image-hotspots' );

			// Parent settings are packed under the `imageHotspots.innerContent` group
			// (same convention as Logo_Carousel's `carousel.innerContent`).
			$inner = $attrs['imageHotspots']['innerContent']['desktop']['value'] ?? array();

			$config = array(
				'image'    => self::resolve_upload_url( $inner['image'] ?? '' ),
				'imageAlt' => (string) ( $inner['imageAlt'] ?? '' ),
				'trigger'  => (string) ( $inner['trigger'] ?? 'hover' ),
			);

			$uid         = self::get_instance_uid( $block );
			$inline_css  = self::get_color_css( $inner, $uid );
			$canvas_html = Image_Hotspots_Helper::build_canvas( $config, $child_modules_content );

			$hotspots_html = ( '' !== $inline_css ? sprintf( '<style>%s</style>', $inline_css ) : '' )
				. sprintf( '<div class="%s">%s</div>', esc_attr( $uid ), $canvas_html );

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
					'children'            => $style_components . $hotspots_html,
				)
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Image Hotspots module' );

			return '';
		}
	}

	/**
	 * Build a stable per-instance uid for scoping color CSS selectors.
	 *
	 * @since 4.3.0
	 *
	 * @param WP_Block $block The parsed block.
	 *
	 * @return string
	 */
	protected static function get_instance_uid( WP_Block $block ): string {
		$raw = (string) ( $block->parsed_block['id'] ?? '' );
		$uid = preg_replace( '/[^a-z0-9]/', '', strtolower( $raw ) );

		return '' !== $uid
			? 'squad-hs-' . $uid
			: 'squad-hs-' . substr( md5( $raw . wp_json_encode( $block->parsed_block['orderIndex'] ?? 0 ) ), 0, 10 );
	}

	/**
	 * Generate scoped pin (marker) color CSS for this instance.
	 *
	 * @since 4.3.0
	 *
	 * @param array<string, mixed> $inner Packed `imageHotspots.innerContent` desktop values.
	 * @param string               $uid   Per-instance identifier.
	 *
	 * @return string Raw CSS (no <style> tags).
	 */
	protected static function get_color_css( array $inner, string $uid ): string {
		$css = '';

		$pin_color = self::sanitize_css_background( (string) ( $inner['pinColor'] ?? '' ) );
		if ( '' !== $pin_color ) {
			$css .= ".{$uid} .squad-hotspots__marker{background-color:{$pin_color}}";
		}

		return $css;
	}
}
