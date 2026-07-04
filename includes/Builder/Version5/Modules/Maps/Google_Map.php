<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Google Map Module (Divi 5 / Block API).
 *
 * Native Divi 5 implementation of the Google Embed Map module. Renders the
 * embed iframe server-side via the render callback, reusing the same extension
 * filters as the Divi 4 module so behavior stays consistent across builders.
 *
 * @since   3.4.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version5\Modules\Maps;

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
use function absint;
use function add_query_arg;
use function apply_filters;
use function esc_attr;
use function esc_url;
use function get_locale;

/**
 * Google Map Module class.
 *
 * @since 3.4.0
 */
class Google_Map extends Module {

	/**
	 * Relative path to the generated module.json metadata folder.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/google-map/';
	}

	/**
	 * Add the module classnames.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $args               {
	 *                                                 Arguments.
	 *
	 * @type object                $classnamesInstance Classnames instance.
	 * @type array<string, mixed>  $attrs              Module attributes.
	 *                                                 }
	 *
	 * @return void
	 */
	public static function module_classnames( array $args ): void {
		$classnames_instance = $args['classnamesInstance'];
		$attrs               = $args['attrs'];

		$classnames_instance->add( 'disq_embed_google_map' );
		$classnames_instance->add(
			ElementClassnames::classnames(
				array(
					'attrs' => $attrs['module']['decoration'] ?? array(),
				)
			)
		);
	}

	/**
	 * Assign the module's frontend script data.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $args     {
	 *                                       Arguments.
	 *
	 * @type ModuleElements        $elements ModuleElements instance.
	 *                                       }
	 *
	 * @return void
	 */
	public static function module_script_data( array $args ): void {
		$elements = $args['elements'];

		$elements->script_data(
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
					// Module.
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
					// Module - Custom CSS.
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
	 * Render callback for the Google Map module.
	 *
	 * @since 3.4.0
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
			$map_html = self::render_map( $attrs );

			$style_components = $elements instanceof ModuleElements
				? $elements->style_components( array( 'attrName' => 'module' ) )
				: '';

			return DiviModule::render(
				array(
					// FE only.
					'orderIndex'          => $block->parsed_block['orderIndex'],
					'storeInstance'       => $block->parsed_block['storeInstance'],

					// VB equivalent.
					'attrs'               => $attrs,
					'elements'            => $elements,
					'id'                  => $block->parsed_block['id'],
					'name'                => $block->block_type->name,
					'moduleCategory'      => $block->block_type->category,
					'classnamesFunction'  => array( self::class, 'module_classnames' ),
					'stylesComponent'     => array( self::class, 'module_styles' ),
					'scriptDataComponent' => array( self::class, 'module_script_data' ),
					'children'            => $style_components . $map_html,
				)
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Google Map module' );

			return '';
		}
	}

	/**
	 * Build the Google Map embed iframe HTML from Divi 5 attributes.
	 *
	 * Reuses the same filters as the Divi 4 module so address/zoom/URL/markup
	 * remain extensible and consistent. The module instance argument passed to
	 * those filters is `null` in the Divi 5 context (there is no D4 module object).
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $attrs Block attributes.
	 *
	 * @return string
	 */
	private static function render_map( array $attrs ): string {
		$inner          = $attrs['map']['innerContent']['desktop']['value'] ?? array();
		$address        = $inner['address'] ?? '';
		$zoom           = isset( $inner['zoom'] ) ? (int) $inner['zoom'] : 10;
		$module_api_key = $inner['googleApiKey'] ?? '';

		/** This filter is documented in includes/Builder/Version4/Modules/Maps/Google_Map.php */
		$address = apply_filters( 'divi_squad_google_map_address', $address, $attrs, null );

		/** This filter is documented in includes/Builder/Version4/Modules/Maps/Google_Map.php */
		$zoom = (int) apply_filters( 'divi_squad_google_map_zoom', $zoom, $attrs, null );

		$divi_api_key = function_exists( 'et_pb_get_google_api_key' ) ? (string) et_pb_get_google_api_key() : '';
		$api_key      = '' !== $module_api_key ? $module_api_key : $divi_api_key;

		/** This filter is documented in includes/Builder/Version4/Modules/Maps/Google_Map.php */
		$api_key_exists = (bool) apply_filters( 'divi_squad_google_map_use_api_key', '' !== $api_key, null );

		if ( $api_key_exists ) {
			$src_url = add_query_arg(
				array(
					'key'      => $api_key,
					'q'        => $address,
					'zoom'     => absint( $zoom ),
					'language' => get_locale(),
				),
				'https://www.google.com/maps/embed/v1/place'
			);

			/** This filter is documented in includes/Builder/Version4/Modules/Maps/Google_Map.php */
			$src_url = apply_filters( 'divi_squad_google_map_api_url', $src_url, $address, $zoom, null );
		} else {
			$src_url = add_query_arg(
				array(
					'q'      => $address,
					't'      => 'm',
					'z'      => absint( $zoom ),
					'output' => 'embed',
					'iwloc'  => 'near',
					'hl'     => get_locale(),
				),
				'https://maps.google.com/maps'
			);

			/** This filter is documented in includes/Builder/Version4/Modules/Maps/Google_Map.php */
			$src_url = apply_filters( 'divi_squad_google_map_fallback_url', $src_url, $address, $zoom, null );
		}

		$iframe_html = '<iframe frameborder="0" scrolling="no" marginheight="0" marginwidth="0" ';
		$iframe_html .= 'src="' . esc_url( $src_url ) . '" ';
		$iframe_html .= 'aria-label="' . esc_attr( $address ) . '"></iframe>';

		/** This filter is documented in includes/Builder/Version4/Modules/Maps/Google_Map.php */
		return (string) apply_filters( 'divi_squad_google_map_iframe_html', $iframe_html, $src_url, $address, $zoom, null );
	}
}
