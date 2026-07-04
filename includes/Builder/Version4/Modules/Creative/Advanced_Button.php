<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Advanced Button Module Class which extends the Divi Builder Module Class.
 *
 * This class provides a rich single button with optional sub-text, icon
 * placement, icon-on-hover reveal, and hover-effect presets — server-rendered
 * with CSS-only interactions (no frontend JS).
 *
 * @since   4.1.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Creative;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use DiviSquad\Builder\Shared\Modules\Creative\Advanced_Button\Button_Helper;
use DiviSquad\Builder\Version4\Abstracts\Module;
use DiviSquad\Utils\Divi;
use function esc_html__;
use function et_pb_get_extended_font_icon_value;
use function sprintf;
use function wp_kses_post;

/**
 * Advanced Button Module class.
 *
 * @since 4.1.0
 */
class Advanced_Button extends Module {

	/**
	 * Initiate Module.
	 * Set the module name on init.
	 *
	 * @since 4.1.0
	 *
	 * @return void
	 */
	public function init(): void {
		$this->name      = esc_html__( 'Advanced Button', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Advanced Buttons', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'advanced-button.svg' );

		$this->slug             = 'disq_advanced_button';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.$this->slug";

		$this->child_title_var          = 'admin_label';
		$this->child_title_fallback_var = 'admin_label';

		// Connect with utils.
		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		// Declare settings modal toggles for the module.
		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'button_element' => esc_html__( 'Button', 'squad-modules-for-divi' ),
					'icon_element'   => esc_html__( 'Icon', 'squad-modules-for-divi' ),
					'link_options'   => esc_html__( 'Link', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'button_element' => esc_html__( 'Button', 'squad-modules-for-divi' ),
					'sub_text'       => esc_html__( 'Sub Text', 'squad-modules-for-divi' ),
					'icon_element'   => esc_html__( 'Icon', 'squad-modules-for-divi' ),
					'layout'         => esc_html__( 'Layout', 'squad-modules-for-divi' ),
				),
			),
		);

		// Declare advanced fields for the module.
		$this->advanced_fields = array(
			'fonts'          => array(
				'sub_text' => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Sub Text', 'squad-modules-for-divi' ),
					array(
						'font_size'   => array( 'default' => '13px' ),
						'css'         => array(
							'main'  => "$this->main_css_element .squad-advanced-button__link .squad-advanced-button__sub",
							'hover' => "$this->main_css_element .squad-advanced-button__link:hover .squad-advanced-button__sub",
						),
						'tab_slug'    => 'advanced',
						'toggle_slug' => 'sub_text',
					)
				),
			),
			'background'     => divi_squad()->d4_module_helper->selectors_background( $this->main_css_element ),
			'borders'        => array(
				'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			),
			'box_shadow'     => array(
				'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			),
			'margin_padding' => divi_squad()->d4_module_helper->selectors_margin_padding( $this->main_css_element ),
			'max_width'      => divi_squad()->d4_module_helper->selectors_max_width( $this->main_css_element ),
			'height'         => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			'image_icon'     => false,
			'text'           => false,
			'button'         => array(
				'button' => array(
					'label'         => esc_html__( 'Button', 'squad-modules-for-divi' ),
					'css'           => array(
						'main'         => "$this->main_css_element .squad-advanced-button__link.et_pb_button",
						'hover'        => "$this->main_css_element .squad-advanced-button__link.et_pb_button:hover",
						'limited_main' => "$this->main_css_element .squad-advanced-button__link.et_pb_button",
					),
					'use_alignment' => false,
					'box_shadow'    => array( 'css' => array( 'main' => "$this->main_css_element .squad-advanced-button__link.et_pb_button" ) ),
					'tab_slug'      => 'advanced',
					'toggle_slug'   => 'button_element',
				),
			),
			'filters'        => false,
			'link_options'   => false,
		);

		// Declare custom css fields for the module.
		$this->custom_css_fields = array(
			'button_link' => array(
				'label'    => esc_html__( 'Button Link', 'squad-modules-for-divi' ),
				'selector' => '.squad-advanced-button__link',
			),
			'button_icon' => array(
				'label'    => esc_html__( 'Button Icon', 'squad-modules-for-divi' ),
				'selector' => '.squad-advanced-button__icon',
			),
			'button_text' => array(
				'label'    => esc_html__( 'Button Text', 'squad-modules-for-divi' ),
				'selector' => '.squad-advanced-button__text',
			),
			'button_sub'  => array(
				'label'    => esc_html__( 'Sub Text', 'squad-modules-for-divi' ),
				'selector' => '.squad-advanced-button__sub',
			),
		);
	}

	/**
	 * Declare general fields for the module.
	 *
	 * @since 4.1.0
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_fields(): array {
		// Content tab — button group.
		$button_fields = array(
			'button_text' => array(
				'label'           => esc_html__( 'Button Text', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'The primary label displayed on the button.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => esc_html__( 'Click Here', 'squad-modules-for-divi' ),
				'tab_slug'        => 'general',
				'toggle_slug'     => 'button_element',
				'dynamic_content' => 'text',
				'mobile_options'  => true,
				'hover'           => 'tabs',
			),
			'sub_text'    => array(
				'label'           => esc_html__( 'Sub Text', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'Optional second line of text below the button label.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => '',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'button_element',
				'dynamic_content' => 'text',
				'mobile_options'  => true,
				'hover'           => 'tabs',
			),
		);

		// Content tab — link group.
		$link_fields = array(
			'button_url'     => array(
				'label'           => esc_html__( 'Button URL', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'The destination URL when the button is clicked. Leave empty for a non-link button.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => '',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'link_options',
				'dynamic_content' => 'url',
			),
			'url_new_window' => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Open in New Tab', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Open the link in a new browser tab.', 'squad-modules-for-divi' ),
					'default_on_front' => 'off',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'link_options',
				)
			),
			'add_nofollow'   => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Add Nofollow', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Append rel="nofollow" to the link.', 'squad-modules-for-divi' ),
					'default_on_front' => 'off',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'link_options',
				)
			),
		);

		// Content tab — icon group.
		$icon_fields = array(
			'use_icon'       => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Use Icon', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Show an icon alongside the button text.', 'squad-modules-for-divi' ),
					'default_on_front' => 'off',
					'affects'          => array(
						'icon',
						'icon_placement',
						'icon_on_hover',
						'icon_color',
						'icon_size',
						'icon_spacing',
					),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'icon_element',
				)
			),
			'icon'           => array(
				'label'            => esc_html__( 'Choose an Icon', 'squad-modules-for-divi' ),
				'description'      => esc_html__( 'Pick the icon to display alongside the button.', 'squad-modules-for-divi' ),
				'type'             => 'select_icon',
				'option_category'  => 'basic_option',
				'class'            => array( 'et-pb-font-icon' ),
				'default_on_front' => '&#x4e;||divi||400',
				'show_if'          => array( 'use_icon' => 'on' ),
				'tab_slug'         => 'general',
				'toggle_slug'      => 'icon_element',
				'hover'            => 'tabs',
				'mobile_options'   => true,
			),
			'icon_placement' => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Icon Placement', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Where the icon is placed relative to the button text.', 'squad-modules-for-divi' ),
					'options'          => array(
						'left'   => esc_html__( 'Left', 'squad-modules-for-divi' ),
						'right'  => esc_html__( 'Right', 'squad-modules-for-divi' ),
						'top'    => esc_html__( 'Top', 'squad-modules-for-divi' ),
						'bottom' => esc_html__( 'Bottom', 'squad-modules-for-divi' ),
					),
					'default'          => 'left',
					'default_on_front' => 'left',
					'show_if'          => array( 'use_icon' => 'on' ),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'icon_element',
				)
			),
			'icon_on_hover'  => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Reveal Icon on Hover', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Hide the icon until the button is hovered.', 'squad-modules-for-divi' ),
					'default_on_front' => 'off',
					'show_if'          => array( 'use_icon' => 'on' ),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'icon_element',
				)
			),
			'hover_effect'   => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Hover Effect', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'CSS-only visual effect applied when the button is hovered.', 'squad-modules-for-divi' ),
					'options'          => array(
						'none'     => esc_html__( 'None', 'squad-modules-for-divi' ),
						'bg-slide' => esc_html__( 'Background Slide', 'squad-modules-for-divi' ),
						'lift'     => esc_html__( 'Lift', 'squad-modules-for-divi' ),
						'scale'    => esc_html__( 'Scale', 'squad-modules-for-divi' ),
						'border'   => esc_html__( 'Border', 'squad-modules-for-divi' ),
					),
					'default'          => 'none',
					'default_on_front' => 'none',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'button_element',
				)
			),
		);

		// Design tab — icon design group.
		$icon_design_fields = array(
			'icon_color'        => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Icon Color', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Color of the button icon.', 'squad-modules-for-divi' ),
					'show_if'     => array( 'use_icon' => 'on' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'icon_element',
				)
			),
			'icon_size'         => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Icon Size', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Size of the button icon.', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '8', 'max' => '100', 'step' => '1' ),
					'default'        => '16px',
					'show_if'        => array( 'use_icon' => 'on' ),
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'icon_element',
				)
			),
			'icon_spacing'      => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Icon Spacing', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Gap between the icon and the button text.', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '0', 'max' => '80', 'step' => '1' ),
					'default'        => '8px',
					'show_if'        => array( 'use_icon' => 'on' ),
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'icon_element',
				)
			),
			'content_alignment' => divi_squad()->d4_module_helper->add_alignment_field(
				esc_html__( 'Content Alignment', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Horizontal alignment of the button within its container.', 'squad-modules-for-divi' ),
					'type'             => 'text_align',
					'default'          => 'left',
					'default_on_front' => 'left',
					'tab_slug'         => 'advanced',
					'toggle_slug'      => 'layout',
				)
			),
		);

		return array_merge(
			$button_fields,
			$link_fields,
			$icon_fields,
			$icon_design_fields
		);
	}

	/**
	 * Get CSS fields transition.
	 *
	 * @since 4.1.0
	 *
	 * @return array<string, array<string, string>>
	 */
	public function get_transition_fields_css_props(): array {
		$fields = parent::get_transition_fields_css_props();

		$fields['icon_color']   = array( 'color' => "$this->main_css_element .squad-advanced-button__icon .et-pb-icon" );
		$fields['icon_size']    = array( 'font-size' => "$this->main_css_element .squad-advanced-button__icon .et-pb-icon" );
		$fields['icon_spacing'] = array( 'gap' => "$this->main_css_element .squad-advanced-button__link" );
		divi_squad()->d4_module_helper->fix_fonts_transition( $fields, 'sub_text', "$this->main_css_element .squad-advanced-button__sub" );

		return $fields;
	}

	/**
	 * Renders the module output.
	 *
	 * @since 4.1.0
	 *
	 * @param array<string, mixed> $attrs       List of attributes.
	 * @param string               $content     Content being processed.
	 * @param string               $render_slug Slug of module that is used for rendering output.
	 *
	 * @return string
	 */
	public function render( $attrs, $content, $render_slug ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClassAfterLastUsed
		// Fixed: the custom background doesn't work at frontend.
		$this->props = array_merge( $attrs, $this->props );

		$button_text    = (string) $this->prop( 'button_text', esc_html__( 'Click Here', 'squad-modules-for-divi' ) );
		$sub_text       = (string) $this->prop( 'sub_text', '' );
		$button_url     = (string) $this->prop( 'button_url', '' );
		$url_new_window = 'on' === $this->prop( 'url_new_window', 'off' );
		$add_nofollow   = 'on' === $this->prop( 'add_nofollow', 'off' );
		$use_icon       = 'on' === $this->prop( 'use_icon', 'off' );
		$icon_placement = (string) $this->prop( 'icon_placement', 'left' );
		$icon_on_hover  = 'on' === $this->prop( 'icon_on_hover', 'off' );
		$hover_effect   = (string) $this->prop( 'hover_effect', 'none' );

		// Validate allowlisted values — fall back to default on invalid input.
		if ( ! Button_Helper::is_valid_placement( $icon_placement ) ) {
			$icon_placement = 'left';
		}
		if ( ! Button_Helper::is_valid_hover( $hover_effect ) ) {
			$hover_effect = 'none';
		}

		// Render icon markup.
		$icon_html = '';
		if ( $use_icon ) {
			$icon_raw = (string) $this->prop( 'icon', '' );
			if ( '' !== $icon_raw ) {
				Divi::inject_fa_icons( $icon_raw );

				$icon_value = et_pb_get_extended_font_icon_value( $icon_raw, true );

				// Icon color.
				$this->generate_styles(
					array(
						'base_attr_name' => 'icon_color',
						'selector'       => "$this->main_css_element .squad-advanced-button__icon .et-pb-icon",
						'hover_selector' => "$this->main_css_element .squad-advanced-button__link:hover .squad-advanced-button__icon .et-pb-icon",
						'css_property'   => 'color',
						'render_slug'    => $this->slug,
						'type'           => 'color',
						'important'      => true,
					)
				);
				// Icon font family (required by Divi icon rendering).
				$this->generate_styles(
					array(
						'utility_arg'    => 'icon_font_family',
						'render_slug'    => $this->slug,
						'base_attr_name' => 'icon',
						'important'      => true,
						'selector'       => "$this->main_css_element .squad-advanced-button__icon .et-pb-icon",
						'processor'      => array(
							'ET_Builder_Module_Helper_Style_Processor',
							'process_extended_icon',
						),
					)
				);
				// Icon size.
				$this->generate_styles(
					array(
						'base_attr_name' => 'icon_size',
						'selector'       => "$this->main_css_element .squad-advanced-button__icon .et-pb-icon",
						'hover_selector' => "$this->main_css_element .squad-advanced-button__link:hover .squad-advanced-button__icon .et-pb-icon",
						'css_property'   => 'font-size',
						'render_slug'    => $this->slug,
						'type'           => 'range',
						'important'      => true,
					)
				);

				$icon_html = sprintf(
					'<span class="squad-advanced-button__icon"><span class="et-pb-icon">%s</span></span>',
					wp_kses_post( $icon_value )
				);
			}
		}

		// Icon spacing (gap on the link element).
		$this->generate_styles(
			array(
				'base_attr_name' => 'icon_spacing',
				'selector'       => "$this->main_css_element .squad-advanced-button__link",
				'hover_selector' => "$this->main_css_element .squad-advanced-button__link:hover",
				'css_property'   => 'gap',
				'render_slug'    => $this->slug,
				'type'           => 'range',
				'important'      => true,
			)
		);

		// Content alignment.
		$this->generate_styles(
			array(
				'base_attr_name' => 'content_alignment',
				'selector'       => $this->main_css_element,
				'hover_selector' => "$this->main_css_element:hover",
				'css_property'   => 'justify-content',
				'render_slug'    => $this->slug,
				'type'           => 'align',
			)
		);

		return Button_Helper::build_shell(
			$button_text,
			$sub_text,
			$button_url,
			$url_new_window,
			$add_nofollow,
			$use_icon,
			$icon_html,
			$icon_placement,
			$icon_on_hover,
			$hover_effect
		);
	}
}
