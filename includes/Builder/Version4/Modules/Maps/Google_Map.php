<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Google Map Module Class which extend the Divi Builder Module Class.
 *
 * This class provides item adding functionalities for Google Map in the visual builder.
 *
 * @since   1.4.7
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Maps;

use DiviSquad\Builder\Version4\Abstracts\Module;
use Throwable;
use function absint;
use function add_query_arg;
use function apply_filters;
use function do_action;
use function esc_attr;
use function esc_html__;
use function esc_url;
use function et_pb_get_google_api_key;
use function get_locale;
use function wp_enqueue_script;

/**
 * Google Map Module Class.
 *
 * @since   1.4.7
 * @package DiviSquad
 */
class Google_Map extends Module {

	/**
	 * Initiate Module.
	 * Set the module name on init.
	 *
	 * @since 1.4.7
	 * @return void
	 */
	public function init(): void {
		$this->name      = esc_html__( 'Google Embed Map', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Google Embed Maps', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'google-map.svg' );

		$this->slug             = 'disq_embed_google_map';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.$this->slug";

		// Connect with utils.
		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		// Declare settings modal toggles for the module.
		$this->settings_modal_toggles = array(
			'general' => array(
				'toggles' => array(
					'main_content' => esc_html__( 'Map Configuration', 'squad-modules-for-divi' ),
				),
			),
		);

		// Declare advanced fields for the module.
		$this->advanced_fields = array(
			'background'     => divi_squad()->d4_module_helper->selectors_background( $this->main_css_element ),
			'borders'        => array( 'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ) ),
			'box_shadow'     => array( 'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ) ),
			'margin_padding' => divi_squad()->d4_module_helper->selectors_margin_padding( $this->main_css_element ),
			'max_width'      => divi_squad()->d4_module_helper->selectors_max_width( $this->main_css_element ),
			'height'         => array_merge(
				divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
				array(
					'options' => array(
						'height' => array(
							'default'        => '320px',
							'default_tablet' => '320px',
							'default_phone'  => '320px',
						),
					),
				)
			),
			'image_icon'     => false,
			'filters'        => false,
			'fonts'          => false,
			'text'           => false,
			'button'         => false,
		);

		// Declare custom css fields for the module.
		$this->custom_css_fields = array(
			'iframe' => array(
				'label'    => esc_html__( 'iFrame', 'squad-modules-for-divi' ),
				'selector' => 'iframe',
			),
		);
	}

	/**
	 * Declare general fields for the module.
	 *
	 * @since 1.4.7
	 * @return array<string, array<string, array<int|string, string>|bool|string>>
	 */
	public function get_fields(): array {
		return array(
			'google_maps_script_notice' => array(
				'type'        => 'warning',
				'value'       => true,
				'display_if'  => true,
				'message'     => esc_html__( 'Google Embed Map API is not required. However, if you encounter any issues with the Embed Google Map, please consider using Google Embed Map API for stability in the future.', 'squad-modules-for-divi' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'main_content',
			),
			'google_api_key'            => array(
				'label'                  => esc_html__( 'Google API Key', 'squad-modules-for-divi' ),
				'description'            => esc_html__( 'The module uses the Google Maps API and requires a valid Google API Key to function. Before using the map module, please make sure you have added your API key inside the Divi Theme Options panel.', 'squad-modules-for-divi' ),
				'type'                   => 'text',
				'option_category'        => 'basic_option',
				'attributes'             => 'readonly',
				'additional_button_type' => 'change_google_api_key',
				'additional_button'      => esc_html__( 'Change API Key', 'squad-modules-for-divi' ),
				'class'                  => array( 'et_pb_google_api_key', 'et-pb-helper-field' ),
				'tab_slug'               => 'general',
				'toggle_slug'            => 'main_content',
			),
			'address'                   => array(
				'label'            => esc_html__( 'Address', 'squad-modules-for-divi' ),
				'description'      => esc_html__( 'Enter the address for the embed Google Map.', 'squad-modules-for-divi' ),
				'type'             => 'text',
				'option_category'  => 'basic_option',
				'default_on_front' => '1233 Howard St Apt 3A San Francisco, CA 94103-2775',
				'tab_slug'         => 'general',
				'toggle_slug'      => 'main_content',
				'dynamic_content'  => 'text',
			),
			'zoom'                      => array(
				'label'            => esc_html__( 'Zoom', 'squad-modules-for-divi' ),
				'type'             => 'range',
				'option_category'  => 'layout',
				'range_settings'   => array(
					'min'  => '1',
					'max'  => '22',
					'step' => '1',
				),
				'default_unit'     => '',
				'default'          => '10',
				'default_on_front' => '10',
				'unitless'         => true,
				'allow_empty'      => false,
				'tab_slug'         => 'general',
				'toggle_slug'      => 'main_content',
			),
		);
	}

	/**
	 * Renders the module output.
	 *
	 * @param array<string, string> $attrs       List of attributes.
	 * @param string                $content     Content being processed.
	 * @param string                $render_slug Slug of module that is used for rendering output.
	 *
	 * @return string
	 */
	public function render( $attrs, $content, $render_slug ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClassAfterLastUsed
		try {
			wp_enqueue_script( 'squad-module-google-map' );

			$address = $this->props['address'] ?? '';
			$zoom    = $this->props['zoom'] ?? 10;

			/**
			 * Filter the Google Map address before rendering.
			 *
			 * @since 1.4.7
			 *
			 * @param string     $address The map address.
			 * @param array      $attrs   Module attributes.
			 * @param Google_Map $module  Current module instance.
			 */
			$address = apply_filters( 'divi_squad_google_map_address', $address, $attrs, $this );

			/**
			 * Filter the Google Map zoom level before rendering.
			 *
			 * @since 1.4.7
			 *
			 * @param int|string $zoom   The map zoom level.
			 * @param array      $attrs  Module attributes.
			 * @param Google_Map $module Current module instance.
			 */
			$zoom = (int) apply_filters( 'divi_squad_google_map_zoom', $zoom, $attrs, $this );

			$api_key_exists = function_exists( 'et_pb_get_google_api_key' ) && et_pb_get_google_api_key();

			/**
			 * Filter whether to use Google Maps API key.
			 *
			 * @since 1.4.7
			 *
			 * @param bool       $api_key_exists Whether API key exists and should be used.
			 * @param Google_Map $module         Current module instance.
			 */
			$api_key_exists = apply_filters( 'divi_squad_google_map_use_api_key', $api_key_exists, $this );

			// Build iframe with proper fallbacks.
			$iframe_html = '<iframe frameborder="0" scrolling="no" marginheight="0" marginwidth="0" ';

			if ( $api_key_exists ) {
				// Add API parameters.
				$src_url = add_query_arg(
					array(
						'key'      => et_pb_get_google_api_key(),
						'q'        => $address,
						'zoom'     => absint( $zoom ),
						'language' => get_locale(),
					),
					'https://www.google.com/maps/embed/v1/place'
				);

				/**
				 * Filter the Google Maps embed URL with API key.
				 *
				 * @since 1.4.7
				 *
				 * @param string     $src_url The Google Maps embed URL.
				 * @param string     $address The map address.
				 * @param int        $zoom    The map zoom level.
				 * @param Google_Map $module  Current module instance.
				 */
				$src_url = apply_filters( 'divi_squad_google_map_api_url', $src_url, $address, $zoom, $this );
			} else {
				// Use basic Google Maps embed without API key.
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

				/**
				 * Filter the Google Maps embed fallback URL without API key.
				 *
				 * @since 1.4.7
				 *
				 * @param string     $src_url The Google Maps embed URL.
				 * @param string     $address The map address.
				 * @param int        $zoom    The map zoom level.
				 * @param Google_Map $module  Current module instance.
				 */
				$src_url = apply_filters( 'divi_squad_google_map_fallback_url', $src_url, $address, $zoom, $this );
			}

			$iframe_html .= 'src="' . esc_url( $src_url ) . '" ';
			$iframe_html .= ' aria-label="' . esc_attr( $address ) . '"></iframe>';

			/**
			 * Filter the Google Map iframe HTML.
			 *
			 * @since 1.4.7
			 *
			 * @param string     $iframe_html The complete iframe HTML.
			 * @param string     $src_url     The Google Maps embed URL.
			 * @param string     $address     The map address.
			 * @param int        $zoom        The map zoom level.
			 * @param Google_Map $module      Current module instance.
			 */
			$iframe_html = apply_filters( 'divi_squad_google_map_iframe_html', $iframe_html, $src_url, $address, $zoom, $this );

			/**
			 * Fires after rendering the Google Map.
			 *
			 * @since 1.4.7
			 *
			 * @param string     $iframe_html The rendered HTML.
			 * @param array      $attrs       Module attributes.
			 * @param Google_Map $module      Current module instance.
			 */
			do_action( 'divi_squad_after_google_map_render', $iframe_html, $attrs, $this );

			return $iframe_html;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to render GoogleMap module' );

			/**
			 * Filter the Google Map error message when rendering fails.
			 *
			 * @since 1.4.7
			 *
			 * @param string     $error_message The error message to display.
			 * @param Throwable  $e             The exception that was caught.
			 * @param Google_Map $module        Current module instance.
			 */
			return apply_filters(
				'divi_squad_google_map_error_message',
				'<div class="disq-error-message">' . esc_html__( 'Unable to load map. Please try again later.', 'squad-modules-for-divi' ) . '</div>',
				$e,
				$this
			);
		}
	}
}
