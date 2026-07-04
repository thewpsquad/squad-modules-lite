<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Gradient Text Module Class which extend the Divi Builder Module Class.
 *
 * This class provides gradient text adding functionalities in the visual builder.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   1.2.2
 */

namespace DiviSquad\Modules\Creatives;

use DiviSquad\Base\DiviBuilder\Module;
use DiviSquad\Base\DiviBuilder\Utils;
use DiviSquad\Utils\Helper;
use function esc_html__;
use function et_pb_background_options;
use function wp_kses_post;

/**
 * Gradient Text Module Class.
 *
 * @package DiviSquad
 * @since   1.2.6
 */
class GradientText extends Module {

	/**
	 * Initiate Module.
	 * Set the module name on init.
	 *
	 * @return void
	 * @since 1.2.6
	 */
	public function init() {
		$this->name      = esc_html__( 'Gradient Text', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Gradient Texts', 'squad-modules-for-divi' );
		$this->icon_path = Helper::fix_slash( divi_squad()->get_icon_path() . '/gradient-text.svg' );

		$this->slug             = 'disq_gradient_text';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.$this->slug";

		$this->child_title_var          = 'gradient_text';
		$this->child_title_fallback_var = 'admin_label';

		// Connect with utils.
		$this->squad_utils = Utils::connect( $this );

		// Declare settings modal toggles for the module.
		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'main_content' => esc_html__( 'Main Content', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'gradient'      => esc_html__( 'Gradient', 'squad-modules-for-divi' ),
					'gradient_text' => esc_html__( 'Gradient Text', 'squad-modules-for-divi' ),
				),
			),
		);

		// Declare advanced fields for the module.
		$this->advanced_fields = array(
			'fonts'          => array(
				'gradient_text' => Utils::add_font_field(
					'',
					array(
						'font_size'       => array(
							'default' => '40px',
						),
						'hide_text_color' => true,
						'line_height'     => array(
							'default'        => '1.2em',
							'range_settings' => array(
								'min'  => '1',
								'max'  => '3',
								'step' => '.1',
							),
						),
						'important'       => 'all',
						'css'             => array(
							'main'  => "$this->main_css_element div .gradient-text-wrapper .gradient-text-element",
							'hover' => "$this->main_css_element div .gradient-text-wrapper:hover .gradient-text-element",
						),
						'tab_slug'        => 'advanced',
						'toggle_slug'     => 'gradient_text',
					)
				),
			),
			'background'     => Utils::selectors_background( $this->main_css_element ),
			'borders'        => array( 'default' => Utils::selectors_default( $this->main_css_element ) ),
			'box_shadow'     => array( 'default' => Utils::selectors_default( $this->main_css_element ) ),
			'margin_padding' => Utils::selectors_margin_padding( $this->main_css_element ),
			'max_width'      => Utils::selectors_max_width( $this->main_css_element ),
			'height'         => Utils::selectors_default( $this->main_css_element ),
			'image_icon'     => false,
			'text'           => false,
			'button'         => false,
			'filters'        => false,
		);

		// Declare custom css fields for the module.
		$this->custom_css_fields = array(
			'gradient_text' => array(
				'label'    => esc_html__( 'Gradient Text', 'squad-modules-for-divi' ),
				'selector' => 'div .gradient-text-wrapper .gradient-text-element',
			),
		);
	}

	/**
	 * Declare general fields for the module
	 *
	 * @return array[]
	 * @since 1.0.0
	 */
	public function get_fields() {
		// Text fields definitions.
		$text_fields = array(
			'gradient_text'     => array(
				'label'           => esc_html__( 'Gradient Text', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'The text will appear in with your gradient texts.', 'squad-modules-for-divi' ),
				'type'            => 'options_list',
				'option_category' => 'basic_option',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'main_content',
			),
			'gradient_text_tag' => Utils::add_select_box_field(
				esc_html__( 'Gradient Text Tag', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Choose a tag to display with your gradient text.', 'squad-modules-for-divi' ),
					'options'          => Utils::get_html_tag_elements(),
					'default_on_front' => 'p',
					'default'          => 'p',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'main_content',
				)
			),
		);

		// Gradient settings.
		$gradient_styles = $this->squad_utils->add_background_gradient_field(
			array(
				'label'       => esc_html__( 'Gradient Colors', 'squad-modules-for-divi' ),
				'base_name'   => 'text_gradient',
				'context'     => 'text_gradient_color',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'gradient',
			)
		);

		// remove unneeded fields.
		unset( $gradient_styles['text_gradient_color']['background_fields']['text_gradient_color_gradient_overlays_image'] );

		// Set default color.
		$gradient_styles['text_gradient_color']['background_fields']['text_gradient_use_color_gradient']['default']   = 'on';
		$gradient_styles['text_gradient_color']['background_fields']['text_gradient_color_gradient_stops']['default'] = '#1f7016 0%|#29c4a9 100%';

		return array_merge( $text_fields, $gradient_styles );
	}

	/**
	 * Get CSS fields transition.
	 *
	 * Add form field options group and background image on the field list.
	 *
	 * @since 1.0.0
	 */
	public function get_transition_fields_css_props() {
		$fields = parent::get_transition_fields_css_props();

		// Default styles.
		$fields['background_layout'] = array( 'color' => $this->main_css_element );

		return $fields;
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
		if ( ! empty( $this->prop( 'gradient_text', array() ) ) ) {
			$gradient_text_tag  = $this->prop( 'gradient_text_tag', 'p' );
			$gradient_texts     = Utils::decode_json_data( $this->prop( 'gradient_text', array() ) );
			$gradient_text_html = '';

			if ( count( $gradient_texts ) > 0 ) {
				foreach ( $gradient_texts as $gradient_text ) {
					$gradient_text_html .= "<span>{$gradient_text['value']}</span> <br/>";
				}
			}

			$this->squad_generate_additional_styles( $attrs );

			return sprintf(
				'<div class="gradient-text-wrapper et_pb_with_background"><%2$s class="gradient-text-element">%1$s</%2$s></div>',
				wp_kses_post( $gradient_text_html ),
				wp_kses_post( $gradient_text_tag )
			);
		}

		return '';
	}

	/**
	 * Renders additional styles for the module output.
	 *
	 * @param array $attrs List of attributes.
	 */
	private function squad_generate_additional_styles( $attrs ) {
		// Fixed: the custom background doesn't work at frontend.
		$this->props = array_merge( $attrs, $this->props );

		// the typed text background with default, responsive, hover.
		et_pb_background_options()->get_background_style(
			array(
				'base_prop_name'         => 'text_gradient',
				'props'                  => $this->props,
				'selector'               => "$this->main_css_element div .gradient-text-wrapper .gradient-text-element",
				'selector_hover'         => "$this->main_css_element div .gradient-text-wrapper .gradient-text-element:hover",
				'selector_sticky'        => "$this->main_css_element div .gradient-text-wrapper .gradient-text-element",
				'function_name'          => $this->slug,
				'important'              => ' !important',
				'use_background_video'   => false,
				'use_background_pattern' => false,
				'use_background_mask'    => false,
				'prop_name_aliases'      => array(
					'use_text_gradient_color_gradient' => 'text_gradient_use_color_gradient',
					'text_gradient'                    => 'text_gradient_color',
				),
			)
		);
	}
}
