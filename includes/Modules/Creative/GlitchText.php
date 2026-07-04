<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Glitch Text Module Class which extend the Divi Builder Module Class.
 *
 * This class provides glitch text adding functionalities in the visual builder.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   1.2.2
 */

namespace DiviSquad\Modules\Creative;

use DiviSquad\Base\DiviBuilder\Module;
use DiviSquad\Base\DiviBuilder\Utils;
use DiviSquad\Utils\Helper;
use function esc_html;
use function esc_html__;
use function wp_kses_post;

/**
 * Glitch Text Module Class.
 *
 * @package DiviSquad
 * @since   1.2.2
 */
class GlitchText extends Module {

	/**
	 * Initiate Module.
	 * Set the module name on init.
	 *
	 * @return void
	 * @since 1.2.2
	 */
	public function init(): void {
		$this->name      = esc_html__( 'Glitch Text', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Glitch Texts', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'glitch-text.svg' );

		$this->slug             = 'disq_glitch_text';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.$this->slug";

		$this->child_title_var          = 'glitch_text';
		$this->child_title_fallback_var = 'admin_label';

		// Declare settings modal toggles for the module.
		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'main_content'    => esc_html__( 'Main Content', 'squad-modules-for-divi' ),
					'glitch_settings' => esc_html__( 'Glitch Options', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'glitch_text' => esc_html__( 'Glitch Text', 'squad-modules-for-divi' ),
				),
			),
		);

		// Declare advanced fields for the module.
		$this->advanced_fields = array(
			'fonts'          => array(
				'glitch_text' => Utils::add_font_field(
					'',
					array(
						'font_size'   => array(
							'default' => '20px',
						),
						'css'         => array(
							'main'  => "$this->main_css_element div .glitch-text-wrapper .glitch-text-element",
							'hover' => "$this->main_css_element div .glitch-text-wrapper:hover .glitch-text-element",
						),
						'tab_slug'    => 'advanced',
						'toggle_slug' => 'glitch_text',
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
			'glitch_text' => array(
				'label'    => esc_html__( 'Glitch Text', 'squad-modules-for-divi' ),
				'selector' => 'div .glitch-text-wrapper .glitch-text-element',
			),
		);
	}

	/**
	 * Declare general fields for the module
	 *
	 * @return array[]
	 * @since 1.0.0
	 */
	public function get_fields(): array {
		// Text fields definitions.
		$text_fields = array(
			'glitch_text'     => array(
				'label'           => esc_html__( 'Glitch Text', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'The text will appear in with your glitch text.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'main_content',
				'dynamic_content' => 'text',
			),
			'glitch_text_tag' => Utils::add_select_box_field(
				esc_html__( 'Glitch Text Tag', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Choose a tag to display with your glitch text.', 'squad-modules-for-divi' ),
					'options'          => Utils::get_html_tag_elements(),
					'default_on_front' => 'p',
					'default'          => 'p',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'main_content',
				)
			),
		);

		// General settings.
		$general_settings = array(
			'glitch_text_effect'           => Utils::add_select_box_field(
				esc_html__( 'Glitch Effect', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Choose a effect to display with your glitch text.', 'squad-modules-for-divi' ),
					'options'          => array(
						'one'   => esc_html__( 'Effect 01', 'squad-modules-for-divi' ),
						'two'   => esc_html__( 'Effect 02', 'squad-modules-for-divi' ),
						'three' => esc_html__( 'Effect 03', 'squad-modules-for-divi' ),
						'four'  => esc_html__( 'Effect 04', 'squad-modules-for-divi' ),
						'five'  => esc_html__( 'Effect 05', 'squad-modules-for-divi' ),
					),
					'default_on_front' => 'one',
					'default'          => 'one',
					'affects'          => array(
						'glitch_color_primary_one',
						'glitch_color_secondary_one',
						'glitch_color_primary_two',
						'glitch_color_secondary_two',
						'glitch_color_primary_three',
						'glitch_color_secondary_three',
						'glitch_color_primary_four',
						'glitch_color_secondary_four',
						'glitch_color_primary_five',
						'glitch_color_secondary_five',
					),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'glitch_settings',
				)
			),
			'glitch_color_primary_one'     => Utils::add_color_field(
				esc_html__( 'Primary Color', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Here you can define a custom color for your glitch text.', 'squad-modules-for-divi' ),
					'depends_show_if' => 'one',
					'tab_slug'        => 'general',
					'toggle_slug'     => 'glitch_settings',
				)
			),
			'glitch_color_secondary_one'   => Utils::add_color_field(
				esc_html__( 'Secondary Color', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Here you can define a custom color for your glitch text.', 'squad-modules-for-divi' ),
					'depends_show_if' => 'one',
					'tab_slug'        => 'general',
					'toggle_slug'     => 'glitch_settings',
				)
			),
			'glitch_color_primary_two'     => Utils::add_color_field(
				esc_html__( 'Primary Color', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Here you can define a custom color for your glitch text.', 'squad-modules-for-divi' ),
					'depends_show_if' => 'two',
					'tab_slug'        => 'general',
					'toggle_slug'     => 'glitch_settings',
				)
			),
			'glitch_color_secondary_two'   => Utils::add_color_field(
				esc_html__( 'Secondary Color', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Here you can define a custom color for your glitch text.', 'squad-modules-for-divi' ),
					'depends_show_if' => 'two',
					'tab_slug'        => 'general',
					'toggle_slug'     => 'glitch_settings',
				)
			),
			'glitch_color_primary_three'   => Utils::add_color_field(
				esc_html__( 'Primary Color', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Here you can define a custom color for your glitch text.', 'squad-modules-for-divi' ),
					'depends_show_if' => 'three',
					'tab_slug'        => 'general',
					'toggle_slug'     => 'glitch_settings',
				)
			),
			'glitch_color_secondary_three' => Utils::add_color_field(
				esc_html__( 'Secondary Color', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Here you can define a custom color for your glitch text.', 'squad-modules-for-divi' ),
					'depends_show_if' => 'three',
					'tab_slug'        => 'general',
					'toggle_slug'     => 'glitch_settings',
				)
			),
			'glitch_color_primary_four'    => Utils::add_color_field(
				esc_html__( 'Primary Color', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Here you can define a custom color for your glitch text.', 'squad-modules-for-divi' ),
					'depends_show_if' => 'four',
					'tab_slug'        => 'general',
					'toggle_slug'     => 'glitch_settings',
				)
			),
			'glitch_color_secondary_four'  => Utils::add_color_field(
				esc_html__( 'Secondary Color', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Here you can define a custom color for your glitch text.', 'squad-modules-for-divi' ),
					'depends_show_if' => 'four',
					'tab_slug'        => 'general',
					'toggle_slug'     => 'glitch_settings',
				)
			),
			'glitch_color_primary_five'    => Utils::add_color_field(
				esc_html__( 'Primary Color', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Here you can define a custom color for your glitch text.', 'squad-modules-for-divi' ),
					'depends_show_if' => 'five',
					'tab_slug'        => 'general',
					'toggle_slug'     => 'glitch_settings',
				)
			),
			'glitch_color_secondary_five'  => Utils::add_color_field(
				esc_html__( 'Secondary Color', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Here you can define a custom color for your glitch text.', 'squad-modules-for-divi' ),
					'depends_show_if' => 'five',
					'tab_slug'        => 'general',
					'toggle_slug'     => 'glitch_settings',
				)
			),
		);

		return array_merge( $text_fields, $general_settings );
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
	public function render( $attrs, $content, $render_slug ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClassAfterLastUsed
		if ( ! empty( $this->prop( 'glitch_text', '' ) ) ) {
			$glitch_text_effect = $this->prop( 'glitch_text_effect', 'one' );
			$glitch_text_tag    = $this->prop( 'glitch_text_tag', 'p' );
			$glitch_text        = esc_html( $this->prop( 'glitch_text', '' ) );

			// Effect 3: Wrap text with span tag.
			if ( 'three' === $glitch_text_effect ) {
				$glitch_text = "<span>$glitch_text</span>";
			}

			// Effect 5: Wrap text with span tags.
			if ( 'five' === $glitch_text_effect ) {
				$glitch_text = "<span style='--squad-gte-index: 0;'>$glitch_text</span><span style='--squad-gte-index: 1;'>$glitch_text</span><span style='--squad-gte-index: 2;'>$glitch_text</span>";
			}

			$this->squad_generate_additional_styles( $attrs );

			return sprintf(
				'<div class="glitch-text-wrapper et_pb_with_background %3$s"><%4$s class="glitch-text-element" data-text="%2$s">%1$s</%4$s></div>',
				wp_kses_post( $glitch_text ),
				esc_html( $this->prop( 'glitch_text', '' ) ),
				wp_kses_post( $glitch_text_effect ),
				wp_kses_post( $glitch_text_tag )
			);
		}

		return '';
	}

	/**
	 * Renders additional styles for the module output.
	 *
	 * @param array $attrs List of attributes.
	 */
	private function squad_generate_additional_styles( array $attrs ): void {
		// Fixed: the custom background doesn't work at frontend.
		$this->props = array_merge( $attrs, $this->props );
		$text_effect = $this->prop( 'glitch_text_effect', 'one' );

		// Collect colors.
		$color_primary_attribute   = "glitch_color_primary_$text_effect";
		$color_secondary_attribute = "glitch_color_secondary_$text_effect";

		if ( 'one' === $text_effect ) {
			$primary_color   = $this->prop( $color_primary_attribute, '#FF0000FF' );
			$secondary_color = $this->prop( $color_secondary_attribute, '#0000FFFF' );
		}

		if ( 'two' === $text_effect ) {
			$primary_color   = $this->prop( $color_primary_attribute, '#f0f' );
			$secondary_color = $this->prop( $color_secondary_attribute, '#0ff' );
		}

		if ( in_array( $text_effect, array( 'three', 'four' ), true ) ) {
			$primary_color   = $this->prop( $color_primary_attribute, '#0000FFFF' );
			$secondary_color = $this->prop( $color_secondary_attribute, '#FF0000FF' );
		}

		if ( 'five' === $text_effect ) {
			$primary_color   = $this->prop( $color_primary_attribute, '#FF0000FF' );
			$secondary_color = $this->prop( $color_secondary_attribute, '#0000FFFF' );
		}

		if ( ! empty( $primary_color ) && ! empty( $secondary_color ) ) {
			self::set_style(
				$this->slug,
				array(
					'selector'    => "$this->main_css_element div .glitch-text-wrapper.$text_effect .glitch-text-element",
					'declaration' => "--squad-gt-color-primary: $primary_color; --squad-gt-color-secondary:$secondary_color;",
				)
			);
		}
	}
}
