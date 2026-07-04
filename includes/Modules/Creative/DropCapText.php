<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Drop Cap Module Class which extend the Divi Builder Module Class.
 *
 * This class provides drop cap adding functionalities in the visual builder.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   1.4.0
 */

namespace DiviSquad\Modules\Creative;

use DiviSquad\Base\DiviBuilder\Module;
use DiviSquad\Base\DiviBuilder\Utils;
use DiviSquad\Utils\Helper;
use function esc_html__;
use function et_builder_i18n;
use function et_pb_background_options;
use function sanitize_text_field;
use function wp_kses_post;

/**
 * The Drop Cap Module Class.
 *
 * @package DiviSquad
 * @since   1.4.0
 */
class DropCapText extends Module {

	/**
	 * Initiate Module.
	 * Set the module name on init.
	 *
	 * @return void
	 * @since 1.4.0
	 */
	public function init(): void {
		$this->name      = esc_html__( 'Drop Cap Text', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Drop Cap Texts', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'drop-cap-text.svg' );

		$this->slug             = 'disq_drop_cap_text';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.$this->slug";

		$this->child_title_var          = 'admin_label';
		$this->child_title_fallback_var = 'admin_label';

		// Connect with utils.
		$this->squad_utils = Utils::connect( $this );

		// Declare settings modal toggles for the module.
		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'main_content' => esc_html__( 'Content', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'drop_cap_element' => esc_html__( 'Drop Cap Letter', 'squad-modules-for-divi' ),
					'drop_cap_letter'  => esc_html__( 'Drop Cap Letter Text', 'squad-modules-for-divi' ),
					'content'          => esc_html__( 'Content Text', 'squad-modules-for-divi' ),
				),
			),
		);

		// Declare advanced fields for the module.
		$this->advanced_fields = array(
			'fonts'          => array(
				'drop_cap_letter' => Utils::add_font_field(
					esc_html__( 'Letter', 'squad-modules-for-divi' ),
					array(
						'font_size'   => array(
							'default' => '35px',
						),
						'line_height' => array(
							'default' => '1',
						),
						'css'         => array(
							'main'  => "$this->main_css_element div .drop-cap-letter",
							'hover' => "$this->main_css_element div .drop-cap-letter:hover",
						),
						'tab_slug'    => 'advanced',
						'toggle_slug' => 'drop_cap_letter',
					)
				),
				'content'         => Utils::add_font_field(
					esc_html__( 'Body', 'squad-modules-for-divi' ),
					array(
						'font_size'   => array(
							'default' => '16px',
						),
						'line_height' => array(
							'default' => '1.7',
						),
						'font_weight' => array(
							'default' => '400',
						),
						'css'         => array(
							'main'  => "$this->main_css_element div .body-text",
							'hover' => "$this->main_css_element div .body-text:hover ",
						),
						'tab_slug'    => 'advanced',
						'toggle_slug' => 'content',
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
			'link_options'   => false,
		);

		// Declare custom css fields for the module.
		$this->custom_css_fields = array(
			'drop_cap_letter' => array(
				'label'    => esc_html__( 'DropCap Text', 'squad-modules-for-divi' ),
				'selector' => 'div .drop-cap-letter',
			),
		);
	}

	/**
	 * Declare general fields for the module
	 *
	 * @return array[]
	 * @since 1.4.0
	 */
	public function get_fields(): array {
		// Text fields definitions.
		$text_fields = array(
			'drop_cap_letter' => array(
				'label'           => esc_html__( 'Drop Cap letter', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => esc_html__( 'Your Title Goes Here', 'squad-modules-for-divi' ),
				'toggle_slug'     => 'main_content',
			),
			'body_content'    => array(
				'label'           => et_builder_i18n( 'Body' ),
				'description'     => esc_html__( 'Input the main text content for your module here.', 'squad-modules-for-divi' ),
				'type'            => 'tiny_mce',
				'option_category' => 'basic_option',
				'toggle_slug'     => 'main_content',
				'dynamic_content' => 'text',
			),
		);

		// Drop cap letter associate fields definitions.
		$drop_cap_letter_background_fields = $this->squad_utils->add_background_field(
			array(
				'label'       => esc_html__( 'Background', 'squad-modules-for-divi' ),
				'base_name'   => 'drop_cap_letter_background',
				'context'     => 'drop_cap_letter_background_color',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'drop_cap_element',
			)
		);
		$drop_cap_letter_associated_fields = array(
			'drop_cap_letter_margin'  => Utils::add_margin_padding_field(
				esc_html__( 'Margin', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Here you can define a custom margin size for the drop cap letter.', 'squad-modules-for-divi' ),
					'type'           => 'custom_margin',
					'range_settings' => array(
						'min'  => '1',
						'max'  => '100',
						'step' => '1',
					),
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'drop_cap_element',
				)
			),
			'drop_cap_letter_padding' => Utils::add_margin_padding_field(
				esc_html__( 'Padding', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Here you can define a custom padding size.', 'squad-modules-for-divi' ),
					'type'           => 'custom_padding',
					'range_settings' => array(
						'min'  => '1',
						'max'  => '100',
						'step' => '1',
					),
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'drop_cap_element',
				)
			),
		);

		return array_merge(
			$text_fields,
			$drop_cap_letter_background_fields,
			$drop_cap_letter_associated_fields
		);
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

		// wrapper styles.
		$fields['drop_cap_letter_background_color'] = array( 'background' => "$this->main_css_element div .drop-cap-letter" );
		$fields['drop_cap_letter_margin']           = array( 'margin' => "$this->main_css_element div .drop-cap-letter" );
		$fields['drop_cap_letter_padding']          = array( 'padding' => "$this->main_css_element div .drop-cap-letter" );
		Utils::fix_fonts_transition( $fields, 'drop_cap_letter', "$this->main_css_element div .drop-cap-letter" );
		Utils::fix_fonts_transition( $fields, 'content', "$this->main_css_element div .body-text" );

		// Default styles.
		$fields['background_layout'] = array( 'color' => "$this->main_css_element div" );

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
		$drop_cap_letter = sanitize_text_field( $this->props['drop_cap_letter'] );
		$body_content    = wp_kses_post( $this->props['body_content'] );

		// Generate additional styles for frontend.
		$this->squad_generate_additional_styles( $attrs );

		return sprintf(
			'<div class="dropcap-text-container"><span class="drop-cap-letter">%1$s</span><span class="body-text">%2$s</span></div>',
			wp_kses_post( $drop_cap_letter ),
			wp_kses_post( $body_content )
		);
	}

	/**
	 * Renders additional styles for the module output.
	 *
	 * @param array $attrs List of attributes.
	 *
	 * @return void
	 */
	private function squad_generate_additional_styles( array $attrs ): void {
		// Fixed: the custom background doesn't work at frontend.
		$this->props = array_merge( $attrs, $this->props );

		$letter_bg = 'drop_cap_letter_background';

		// background with default, responsive, hover.
		et_pb_background_options()->get_background_style(
			array(
				'base_prop_name'         => $letter_bg,
				'props'                  => $this->props,
				'selector'               => "$this->main_css_element div .drop-cap-letter",
				'selector_hover'         => "$this->main_css_element div .drop-cap-letter:hover",
				'selector_sticky'        => "$this->main_css_element div .drop-cap-letter",
				'function_name'          => $this->slug,
				'important'              => ' !important',
				'use_background_video'   => false,
				'use_background_pattern' => false,
				'use_background_mask'    => false,
				'prop_name_aliases'      => array(
					"use{$letter_bg}_color_gradient" => "{$letter_bg}_use_color_gradient",
					$letter_bg                       => "{$letter_bg}_color",
				),
			)
		);

		$this->squad_utils->generate_margin_padding_styles(
			array(
				'field'          => 'drop_cap_letter_margin',
				'selector'       => "$this->main_css_element div .drop-cap-letter",
				'hover_selector' => "$this->main_css_element div .drop-cap-letter:hover",
				'type'           => 'margin',
				'css_property'   => 'margin',
				'important'      => true,
			)
		);
		$this->squad_utils->generate_margin_padding_styles(
			array(
				'field'          => 'drop_cap_letter_padding',
				'selector'       => "$this->main_css_element div .drop-cap-letter",
				'hover_selector' => "$this->main_css_element div .drop-cap-letter:hover",
				'type'           => 'padding',
				'css_property'   => 'padding',
				'important'      => true,
			)
		);
	}
}
