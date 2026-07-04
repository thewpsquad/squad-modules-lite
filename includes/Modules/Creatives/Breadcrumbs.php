<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Breadcrumb Module Class which extend the Divi Builder Module Class.
 *
 * This class provides breadcrumbs adding functionalities in the visual builder.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   1.4.0
 */

namespace DiviSquad\Modules\Creatives;

use DiviSquad\Base\DiviBuilder\Module;
use DiviSquad\Base\DiviBuilder\Utils;
use DiviSquad\Utils\Divi;
use DiviSquad\Utils\Helper;
use function esc_attr;
use function esc_html;
use function esc_html__;
use function et_pb_process_font_icon;

/**
 * Breadcrumbs Module Class.
 *
 * @package DiviSquad
 * @since   1.4.0
 */
class Breadcrumbs extends Module {

	/**
	 * Initiate Module.
	 * Set the module name on init.
	 *
	 * @return void
	 * @since 1.4.0
	 */
	public function init() {
		$this->name      = esc_html__( 'Breadcrumbs', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Breadcrumbs', 'squad-modules-for-divi' );
		$this->icon_path = Helper::fix_slash( divi_squad()->get_icon_path() . '/breadcrumbs.svg' );

		$this->slug             = 'disq_breadcrumbs';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.$this->slug";

		$this->child_title_var          = 'title';
		$this->child_title_fallback_var = 'admin_label';

		// Connect with utils.
		$this->squad_utils = Utils::connect( $this );

		// Declare settings modal toggles for the module.
		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'main_content' => esc_html__( 'Breadcrumbs', 'squad-modules-for-divi' ),
					'schema'       => esc_html__( 'Schema', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'breadcrumbs'  => esc_html__( 'Text Colors', 'squad-modules-for-divi' ),
					'content_text' => esc_html__( 'Text', 'squad-modules-for-divi' ),
				),
			),
		);

		// Declare advanced fields for the module.
		$this->advanced_fields = array(
			'fonts'          => array(
				'content_text' => Utils::add_font_field(
					'',
					array(
						'font_size'       => array(
							'default' => '14px',
						),
						'line_height'     => array(
							'default' => '1.7em',
						),
						'letter_spacing'  => array(
							'default' => '0px',
						),
						'hide_text_align' => true,
						'css'             => array(
							'main'  => "$this->main_css_element div",
							'hover' => "$this->main_css_element div:hover",
						),
						'tab_slug'        => 'advanced',
						'toggle_slug'     => 'content_text',
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
			'link_options'   => false,
		);

		// Declare custom css fields for the module.
		$this->custom_css_fields = array(
			'home_text'    => array(
				'label'    => esc_html__( 'Home Text', 'squad-modules-for-divi' ),
				'selector' => 'div .home',
			),
			'before_text'  => array(
				'label'    => esc_html__( 'Before Text', 'squad-modules-for-divi' ),
				'selector' => 'div .before',
			),
			'current_text' => array(
				'label'    => esc_html__( 'Current', 'squad-modules-for-divi' ),
				'selector' => 'div .current',
			),
		);
	}

	/**
	 * Declare general fields for the module
	 *
	 * @return array[]
	 * @since 1.4.0
	 */
	public function get_fields() {
		// Text fields definitions.
		$text_fields = array(
			'home_text'   => array(
				'label'           => esc_html__( 'Home Text', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'The default Home text in the Breadcrumbs', 'squad-modules-for-divi' ),
				'default'         => esc_html__( 'Home', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'main_content',
				'dynamic_content' => 'text',
			),
			'before_text' => array(
				'label'           => esc_html__( 'Before Text', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'The text before the Breadcrumbs', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'main_content',
				'dynamic_content' => 'text',
			),
		);

		// Icon fields definitions.
		$icon_fields = array(
			'font_icon'       => array(
				'label'           => esc_html__( 'Separator Icon', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'Choose the icon for the separator.', 'squad-modules-for-divi' ),
				'type'            => 'select_icon',
				'option_category' => 'basic_option',
				'renderer'        => 'select_icon',
				'class'           => array( 'et-pb-font-icon' ),
				'tab_slug'        => 'general',
				'toggle_slug'     => 'icon',
			),
			'use_before_icon' => Utils::add_yes_no_field(
				esc_html__( 'Add Before Icon', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can choose whether or not add the icon for before text.', 'squad-modules-for-divi' ),
					'default_on_front' => 'off',
					'affects'          => array(
						'before_icon',
					),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'icon',
				)
			),
			'before_icon'     => array(
				'label'           => esc_html__( 'Separator Icon', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'Choose the icon for the separator.', 'squad-modules-for-divi' ),
				'type'            => 'select_icon',
				'renderer'        => 'select_icon',
				'option_category' => 'basic_option',
				'class'           => array( 'et-pb-font-icon' ),
				'depends_show_if' => 'on',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'icon',
			),
		);

		// Color fields definitions.
		$color_fields = array(
			'link_color'         => Utils::add_color_field(
				esc_html__( 'Link Color', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Here you can define a custom color for the links in the breadcrumbs.', 'squad-modules-for-divi' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'breadcrumbs',
				)
			),
			'separator_color'    => Utils::add_color_field(
				esc_html__( 'Separator Color', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Here you can define a custom color for the separator in the breadcrumbs.', 'squad-modules-for-divi' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'breadcrumbs',
				)
			),
			'current_text_color' => Utils::add_color_field(
				esc_html__( 'Current Text Color', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Here you can define a custom color for the current text in the breadcrumbs.', 'squad-modules-for-divi' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'breadcrumbs',
				)
			),
		);

		return array_merge( $text_fields, $icon_fields, $color_fields );
	}

	/**
	 * Get CSS fields transition.
	 *
	 * Add form field options group and background image on the field list.
	 *
	 * @since 1.4.0
	 */
	public function get_transition_fields_css_props() {
		$fields = parent::get_transition_fields_css_props();

		$fields['link_color']         = array( 'color' => "$this->main_css_element div a" );
		$fields['separator_color']    = array( 'color' => "$this->main_css_element div .separator" );
		$fields['current_text_color'] = array( 'color' => "$this->main_css_element div .current" );
		Utils::fix_fonts_transition( $fields, 'content_text', "$this->main_css_element div" );

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
	public function render( $attrs, $content, $render_slug ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClassAfterLastUsed
		$before_html = '';
		if ( '' !== $this->prop( 'before_icon', '' ) ) {
			$before_icon = esc_attr( et_pb_process_font_icon( $this->prop( 'before_icon', '' ) ) );
			$before_html = sprintf( '<span class="before-icon et-pb-icon">%1$s</span>', $before_icon );
		}

		// Generating the Breadcrumbs.
		$breadcrumbs = $this->squad_utils->breadcrumbs->get_hansel_and_gretel(
			esc_html( $this->props['home_text'] ),
			esc_html( $this->props['before_text'] ),
			esc_attr( et_pb_process_font_icon( $this->prop( 'font_icon', '%%24%%' ) ) )
		);

		// Divi icon fallback support.
		Divi::inject_fa_icons( $this->prop( 'font_icon', '&#x39;||divi||400' ) );
		Divi::inject_fa_icons( $this->prop( 'before_icon', '&#x24;||divi||400' ) );

		// Generate additional styles for frontend.
		$this->generate_additional_styles( $attrs );

		return sprintf(
			'<div class="breadcrumbs">%2$s %1$s</div>',
			$breadcrumbs,
			$before_html
		);
	}

	/**
	 * Renders additional styles for the module output.
	 *
	 * @param array $attrs List of attributes.
	 *
	 * @return void
	 */
	private function generate_additional_styles( $attrs ) {
		// Fixed: the custom props don't work at frontend.
		$this->props = array_merge( $attrs, $this->props );

		// Font Icon Styles.
		$this->generate_styles(
			array(
				'utility_arg'    => 'icon_font_family',
				'base_attr_name' => 'font_icon',
				'render_slug'    => $this->slug,
				'important'      => true,
				'selector'       => "$this->main_css_element div .separator",
				'processor'      => array(
					'ET_Builder_Module_Helper_Style_Processor',
					'process_extended_icon',
				),
			)
		);
		$this->generate_styles(
			array(
				'utility_arg'    => 'icon_font_family',
				'base_attr_name' => 'before_icon',
				'render_slug'    => $this->slug,
				'important'      => true,
				'selector'       => "$this->main_css_element div .before-icon",
				'processor'      => array(
					'ET_Builder_Module_Helper_Style_Processor',
					'process_extended_icon',
				),
			)
		);

		$this->generate_styles(
			array(
				'base_attr_name' => 'current_text_color',
				'selector'       => "$this->main_css_element div .breadcrumbs .current",
				'css_property'   => 'color',
				'render_slug'    => $this->slug,
				'type'           => 'string',
			)
		);
		$this->generate_styles(
			array(
				'base_attr_name' => 'link_color',
				'selector'       => "$this->main_css_element div .breadcrumbs a",
				'hover_selector' => "$this->main_css_element div .breadcrumbs a:hover",
				'css_property'   => 'color',
				'render_slug'    => $this->slug,
				'type'           => 'string',
			)
		);
		$this->generate_styles(
			array(
				'base_attr_name' => 'separator_color',
				'selector'       => "$this->main_css_element div .breadcrumbs .separator",
				'css_property'   => 'color',
				'render_slug'    => $this->slug,
				'type'           => 'string',
			)
		);
	}
}
