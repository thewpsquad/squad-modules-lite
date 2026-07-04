<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Google Map Module Class which extend the Divi Builder Module Class.
 *
 * This class provides item adding functionalities for Google Map in the visual builder.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   1.4.7
 */

namespace DiviSquad\Modules\Maps;

use DiviSquad\Base\DiviBuilder\Module;
use DiviSquad\Base\DiviBuilder\Utils;
use DiviSquad\Utils\Helper;
use function esc_attr;
use function esc_attr__;
use function esc_html__;
use function et_pb_get_google_api_key;
use function get_locale;
use function rawurlencode;
use function wp_enqueue_script;

/**
 * Google Map Module Class.
 *
 * @package DiviSquad
 * @since   1.4.7
 */
class GoogleMap extends Module {

	/**
	 * Initiate Module.
	 * Set the module name on init.
	 *
	 * @return void
	 * @since 1.4.7
	 */
	public function init() {
		$this->name      = esc_html__( 'Google Embed Map', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Google Embed Maps', 'squad-modules-for-divi' );
		$this->icon_path = Helper::fix_slash( divi_squad()->get_icon_path() . '/google-map.svg' );

		$this->slug             = 'disq_embed_google_map';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.$this->slug";

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
			'background'     => Utils::selectors_background( $this->main_css_element ),
			'borders'        => array( 'default' => Utils::selectors_default( $this->main_css_element ) ),
			'box_shadow'     => array( 'default' => Utils::selectors_default( $this->main_css_element ) ),
			'margin_padding' => Utils::selectors_margin_padding( $this->main_css_element ),
			'max_width'      => Utils::selectors_max_width( $this->main_css_element ),
			'height'         => array_merge(
				Utils::selectors_default( $this->main_css_element ),
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
	 * Declare general fields for the module
	 *
	 * @return array[]
	 * @since 1.4.7
	 */
	public function get_fields() {
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
				'description'            => sprintf(
					// translators: %1$s: opening anchor tag, %2$s: closing anchor tag.
					esc_html__( 'The module uses the Google Maps API and requires a valid Google API Key to function. Before using the map module, please make sure you have added your API key inside the Divi Theme Options panel. Learn more about how to create your Google API Key %1$shere%2$s.', 'squad-modules-for-divi' ),
					sprintf( '<a href="%1$s" target="_blank">', esc_url( 'http://www.elegantthemes.com/gallery/divi/documentation/map/#gmaps-api-key' ) ),
					'</a>'
				),
				'type'                   => 'text',
				'option_category'        => 'basic_option',
				'attributes'             => 'readonly',
				'additional_button_type' => 'change_google_api_key',
				'additional_button'      => sprintf(
					' <a href="%2$s" target="_blank" class="et_pb_update_google_key button" data-empty_text="%3$s">%1$s</a>',
					esc_html__( 'Change API Key', 'squad-modules-for-divi' ),
					esc_url( et_pb_get_options_page_link() ),
					esc_attr__( 'Add Your API Key', 'squad-modules-for-divi' )
				),
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
	 * @param array  $attrs       List of attributes.
	 * @param string $content     Content being processed.
	 * @param string $render_slug Slug of module that is used for rendering output.
	 *
	 * @return string
	 */
	public function render( $attrs, $content, $render_slug ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClassAfterLastUsed
		$address = $this->props['address'];
		$zoom    = $this->props['zoom'];

		if ( et_pb_get_google_api_key() ) {
			$output = sprintf(
				'<iframe frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="https://www.google.com/maps/embed/v1/place?key=%5$s&amp;q=%1$s&amp;zoom=%2$s&amp;language=%4$s" aria-label="%3$s"></iframe>',
				rawurlencode( $address ),
				absint( $zoom ),
				esc_attr( $address ),
				esc_attr( get_locale() ),
				esc_attr( et_pb_get_google_api_key() )
			);
		} else {
			$output = sprintf(
				'<iframe frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="https://maps.google.com/maps?q=%1$s&amp;t=m&amp;z=%2$s&amp;output=embed&amp;iwloc=near&hl=%4$s" aria-label="%3$s"></iframe>',
				rawurlencode( $address ),
				absint( $zoom ),
				esc_attr( $address ),
				esc_attr( get_locale() )
			);
		}

		wp_enqueue_script( 'squad-module-google-map' );

		return $output;
	}
}
