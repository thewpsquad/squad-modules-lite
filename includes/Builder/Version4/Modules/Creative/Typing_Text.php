<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Typing Text Module Class which extend the Divi Builder Module Class.
 *
 * This class provides typing text adding functionalities for a text element in the visual builder.
 *
 * @since   1.0.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Creative;

use DiviSquad\Builder\Version4\Abstracts\Module;
use DiviSquad\Utils\Divi;
use function esc_html__;
use function et_pb_background_options;
use function et_pb_multi_view_options;
use function wp_enqueue_script;
use function wp_kses_post;

/**
 * Typing Text Module Class.
 *
 * @since   1.0.0
 * @package DiviSquad
 */
class Typing_Text extends Module {
	/**
	 * Initiate Module.
	 * Set the module name on init.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init(): void {
		$this->name      = esc_html__( 'Typing Text', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Typing Texts', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'typing-text.svg' );

		$this->slug             = 'disq_typing_text';
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
					'main_content'    => esc_html__( 'Content', 'squad-modules-for-divi' ),
					'typing_settings' => esc_html__( 'Typing Settings', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'wrapper'              => esc_html__( 'Wrapper', 'squad-modules-for-divi' ),
					'prefix_element'       => esc_html__( 'Before', 'squad-modules-for-divi' ),
					'prefix_text'          => esc_html__( 'Before Text', 'squad-modules-for-divi' ),
					'typed_element'        => esc_html__( 'Typed', 'squad-modules-for-divi' ),
					'typed_text'           => esc_html__( 'Typed Text', 'squad-modules-for-divi' ),
					'typed_cursor_element' => esc_html__( 'Typed Cursor', 'squad-modules-for-divi' ),
					'suffix_element'       => esc_html__( 'After', 'squad-modules-for-divi' ),
					'suffix_text'          => esc_html__( 'After Text', 'squad-modules-for-divi' ),
				),
			),
		);

		// Declare advanced fields for the module.
		$this->advanced_fields = array(
			'fonts'          => array(
				'prefix_text' => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Before', 'squad-modules-for-divi' ),
					array(
						'text_align'  => array(
							'show_if' => array(
								'prefix_display_type' => 'block',
							),
						),
						'css'         => array(
							'main'  => "$this->main_css_element div .text-elements .text-item.prefix-element",
							'hover' => "$this->main_css_element div .text-elements:hover .text-item.prefix-element",
						),
						'tab_slug'    => 'advanced',
						'toggle_slug' => 'prefix_text',
					)
				),
				'typed_text'  => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Typed', 'squad-modules-for-divi' ),
					array(
						'text_align'  => array(
							'show_if' => array(
								'typed_display_type' => 'block',
							),
						),
						'css'         => array(
							'main'  => "$this->main_css_element div .text-elements .text-item.typing-element",
							'hover' => "$this->main_css_element div .text-elements:hover .text-item.typing-element",
						),
						'tab_slug'    => 'advanced',
						'toggle_slug' => 'typed_text',
					)
				),
				'suffix_text' => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'After', 'squad-modules-for-divi' ),
					array(
						'text_align'  => array(
							'show_if' => array(
								'suffix_display_type' => 'block',
							),
						),
						'css'         => array(
							'main'  => "$this->main_css_element div .text-elements .text-item.suffix-element",
							'hover' => "$this->main_css_element div .text-elements:hover .text-item.suffix-element",
						),
						'tab_slug'    => 'advanced',
						'toggle_slug' => 'suffix_text',
					)
				),
			),
			'background'     => divi_squad()->d4_module_helper->selectors_background( $this->main_css_element ),
			'borders'        => array(
				'default'        => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
				'wrapper'        => array(
					'label_prefix' => esc_html__( 'Wrapper', 'squad-modules-for-divi' ),
					'css'          => array(
						'main' => array(
							'border_radii'        => "$this->main_css_element div .text-elements",
							'border_radii_hover'  => "$this->main_css_element div .text-elements:hover",
							'border_styles'       => "$this->main_css_element div .text-elements",
							'border_styles_hover' => "$this->main_css_element div .text-elements:hover",
						),
					),
					'tab_slug'     => 'advanced',
					'toggle_slug'  => 'wrapper',
				),
				'prefix_element' => array(
					'label_prefix' => esc_html__( 'Before', 'squad-modules-for-divi' ),
					'css'          => array(
						'main' => array(
							'border_radii'        => "$this->main_css_element div .text-elements .text-item.prefix-element",
							'border_radii_hover'  => "$this->main_css_element div .text-elements:hover .text-item.prefix-element",
							'border_styles'       => "$this->main_css_element div .text-elements .text-item.prefix-element",
							'border_styles_hover' => "$this->main_css_element div .text-elements:hover .text-item.prefix-element",
						),
					),
					'tab_slug'     => 'advanced',
					'toggle_slug'  => 'prefix_element',
				),
				'typed_element'  => array(
					'label_prefix' => esc_html__( 'Typed', 'squad-modules-for-divi' ),
					'css'          => array(
						'main' => array(
							'border_radii'        => "$this->main_css_element div .text-elements .text-item.typing-element",
							'border_radii_hover'  => "$this->main_css_element div .text-elements:hover .text-item.typing-element",
							'border_styles'       => "$this->main_css_element div .text-elements .text-item.typing-element",
							'border_styles_hover' => "$this->main_css_element div .text-elements:hover .text-item.typing-element",
						),
					),
					'tab_slug'     => 'advanced',
					'toggle_slug'  => 'typed_element',
				),
				'suffix_element' => array(
					'label_prefix' => esc_html__( 'After', 'squad-modules-for-divi' ),
					'css'          => array(
						'main' => array(
							'border_radii'        => "$this->main_css_element div .text-elements .text-item.suffix-element",
							'border_radii_hover'  => "$this->main_css_element div .text-elements:hover .text-item.suffix-element",
							'border_styles'       => "$this->main_css_element div .text-elements .text-item.suffix-element",
							'border_styles_hover' => "$this->main_css_element div .text-elements:hover .text-item.suffix-element",
						),
					),
					'tab_slug'     => 'advanced',
					'toggle_slug'  => 'suffix_element',
				),
			),
			'box_shadow'     => array(
				'default'        => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
				'wrapper'        => array(
					'label'             => esc_html__( 'Wrapper Box Shadow', 'squad-modules-for-divi' ),
					'option_category'   => 'layout',
					'css'               => array(
						'main'  => "$this->main_css_element div .text-elements",
						'hover' => "$this->main_css_element div .text-elements:hover",
					),
					'default_on_fronts' => array(
						'color'    => 'rgba(0,0,0,0.3)',
						'position' => 'outer',
					),
					'tab_slug'          => 'advanced',
					'toggle_slug'       => 'wrapper',
				),
				'prefix_element' => array(
					'label'             => esc_html__( 'Before Box Shadow', 'squad-modules-for-divi' ),
					'option_category'   => 'layout',
					'css'               => array(
						'main'  => "$this->main_css_element div .text-elements .text-item.prefix-element",
						'hover' => "$this->main_css_element div .text-elements:hover .text-item.prefix-element",
					),
					'default_on_fronts' => array(
						'color'    => 'rgba(0,0,0,0.3)',
						'position' => 'outer',
					),
					'tab_slug'          => 'advanced',
					'toggle_slug'       => 'prefix_element',
				),
				'typed_element'  => array(
					'label'             => esc_html__( 'Typed Box Shadow', 'squad-modules-for-divi' ),
					'option_category'   => 'layout',
					'css'               => array(
						'main'  => "$this->main_css_element div .text-elements .text-item.typing-element",
						'hover' => "$this->main_css_element div .text-elements:hover .text-item.typing-element",
					),
					'default_on_fronts' => array(
						'color'    => 'rgba(0,0,0,0.3)',
						'position' => 'outer',
					),
					'tab_slug'          => 'advanced',
					'toggle_slug'       => 'typed_element',
				),
				'suffix_element' => array(
					'label'             => esc_html__( 'After Box Shadow', 'squad-modules-for-divi' ),
					'option_category'   => 'layout',
					'css'               => array(
						'main'  => "$this->main_css_element div .text-elements .text-item.suffix-element",
						'hover' => "$this->main_css_element div .text-elements:hover .text-item.suffix-element",
					),
					'default_on_fronts' => array(
						'color'    => 'rgba(0,0,0,0.3)',
						'position' => 'outer',
					),
					'tab_slug'          => 'advanced',
					'toggle_slug'       => 'suffix_element',
				),
			),
			'margin_padding' => divi_squad()->d4_module_helper->selectors_margin_padding( $this->main_css_element ),
			'max_width'      => divi_squad()->d4_module_helper->selectors_max_width( $this->main_css_element ),
			'height'         => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			'image_icon'     => false,
			'text'           => false,
			'button'         => false,
			'filters'        => false,
		);

		// Declare custom css fields for the module.
		$this->custom_css_fields = array(
			'prefix'             => array(
				'label'    => esc_html__( 'Before', 'squad-modules-for-divi' ),
				'selector' => 'div .text-elements .prefix-element',
			),
			'typed_element'      => array(
				'label'    => esc_html__( 'Typed', 'squad-modules-for-divi' ),
				'selector' => 'div .text-elements .typing-element',
			),
			'suffix'             => array(
				'label'    => esc_html__( 'After', 'squad-modules-for-divi' ),
				'selector' => 'div .text-elements .suffix-element',
			),
			'wrapper'            => array(
				'label'    => esc_html__( 'Wrapper', 'squad-modules-for-divi' ),
				'selector' => 'div .text-elements',
			),
			'custom_cursor_icon' => array(
				'label'    => esc_html__( 'Cursor Icon', 'squad-modules-for-divi' ),
				'selector' => 'div .text-elements .typing-element .typed-cursor',
			),
		);
	}

	/**
	 * Declare general fields for the module
	 *
	 * @since 1.0.0
	 * @return array[]
	 */
	public function get_fields(): array {
		// Text fields definitions.
		$text_fields = array(
			'prefix_text'      => array(
				'label'           => esc_html__( 'Before Text', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'The before text will appear in with your texts.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'main_content',
				'dynamic_content' => 'text',
				'hover'           => 'tabs',
				'mobile_options'  => true,
			),
			'typing_text'      => array(
				'label'           => esc_html__( 'Typing Text', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'The typing text will appear in with your texts.', 'squad-modules-for-divi' ),
				'type'            => 'options_list',
				'option_category' => 'basic_option',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'main_content',
			),
			'suffix_text'      => array(
				'label'           => esc_html__( 'After Text', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'The after text will appear in with your texts.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'main_content',
				'dynamic_content' => 'text',
				'hover'           => 'tabs',
				'mobile_options'  => true,
			),
			'text_element_tag' => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Container Tag', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Choose a tag to display with your texts.', 'squad-modules-for-divi' ),
					'options'          => divi_squad()->d4_module_helper->get_html_tag_elements(),
					'default_on_front' => 'h2',
					'default'          => 'h2',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'main_content',
				)
			),
		);

		// The settings definitions for typing effect texts.
		$typing_effects = array(
			'typing_speed'                 => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Typing Speed (ms)', 'squad-modules-for-divi' ),
				array(
					'description'       => esc_html__( 'Here you can choose how much speed in the typing text.', 'squad-modules-for-divi' ),
					'type'              => 'range',
					'range_settings'    => array(
						'min'       => '0',
						'max'       => '1000',
						'step'      => '1',
						'min_limit' => '0',
					),
					'default_on_front'  => 100,
					'default'           => 100,
					'number_validation' => true,
					'fixed_range'       => true,
					'unitless'          => true,
					'hover'             => false,
					'mobile_options'    => false,
					'responsive'        => false,
					'tab_slug'          => 'general',
					'toggle_slug'       => 'typing_settings',
				)
			),
			'typing_start_delay'           => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Start Delay (ms)', 'squad-modules-for-divi' ),
				array(
					'description'       => esc_html__( 'Here you  can choose how much delay to start the typing text.', 'squad-modules-for-divi' ),
					'type'              => 'range',
					'range_settings'    => array(
						'min'       => '0',
						'max'       => '1000',
						'step'      => '1',
						'min_limit' => '0',
					),
					'number_validation' => true,
					'fixed_range'       => true,
					'unitless'          => true,
					'hover'             => false,
					'mobile_options'    => false,
					'responsive'        => false,
					'tab_slug'          => 'general',
					'toggle_slug'       => 'typing_settings',
				)
			),
			'typing_back_speed'            => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Delete Speed (ms)', 'squad-modules-for-divi' ),
				array(
					'description'       => esc_html__( 'Here you  can choose how much speed to delete the typing text.', 'squad-modules-for-divi' ),
					'type'              => 'range',
					'range_settings'    => array(
						'min'       => '0',
						'max'       => '1000',
						'step'      => '1',
						'min_limit' => '0',
					),
					'default_on_front'  => 50,
					'default'           => 50,
					'number_validation' => true,
					'fixed_range'       => true,
					'unitless'          => true,
					'hover'             => false,
					'mobile_options'    => false,
					'responsive'        => false,
					'depends_show_if'   => 'off',
					'tab_slug'          => 'general',
					'toggle_slug'       => 'typing_settings',
				)
			),
			'typing_back_delay'            => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Delete Delay (ms)', 'squad-modules-for-divi' ),
				array(
					'description'       => esc_html__( 'Here you  can choose how much delay to delete the typing text.', 'squad-modules-for-divi' ),
					'type'              => 'range',
					'range_settings'    => array(
						'min'       => '0',
						'max'       => '1000',
						'step'      => '1',
						'min_limit' => '0',
					),
					'default_on_front'  => 500,
					'default'           => 500,
					'number_validation' => true,
					'fixed_range'       => true,
					'unitless'          => true,
					'hover'             => false,
					'mobile_options'    => false,
					'responsive'        => false,
					'depends_show_if'   => 'off',
					'tab_slug'          => 'general',
					'toggle_slug'       => 'typing_settings',
				)
			),
			'typing_loop__enable'          => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Enable Loop', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Here you can choose whether or not enable loop for the typing effect.', 'squad-modules-for-divi' ),
					'default'     => 'off',
					'tab_slug'    => 'general',
					'toggle_slug' => 'typing_settings',
				)
			),
			'typing_shuffle__enable'       => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Enable Shuffle', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Here you can choose whether or not enable shuffle the string effect.', 'squad-modules-for-divi' ),
					'default'     => 'off',
					'tab_slug'    => 'general',
					'toggle_slug' => 'typing_settings',
				)
			),
			'typing_fade_out__enable'      => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Enable Fade Out', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Here you can choose whether or not enable fade out instead of backspace effect.', 'squad-modules-for-divi' ),
					'default'     => 'off',
					'affects'     => array(
						'typing_back_speed',
						'typing_back_delay',
						'typing_fade_out_delay',
					),
					'tab_slug'    => 'general',
					'toggle_slug' => 'typing_settings',
				)
			),
			'typing_fade_out_delay'        => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Fade Out Delay (ms)', 'squad-modules-for-divi' ),
				array(
					'description'       => esc_html__( 'Here you  can choose how much delay to fade out the typing text.', 'squad-modules-for-divi' ),
					'type'              => 'range',
					'range_settings'    => array(
						'min'       => '0',
						'max'       => '1000',
						'step'      => '1',
						'min_limit' => '0',
					),
					'number_validation' => true,
					'fixed_range'       => true,
					'unitless'          => true,
					'hover'             => false,
					'mobile_options'    => false,
					'responsive'        => false,
					'depends_show_if'   => 'on',
					'tab_slug'          => 'general',
					'toggle_slug'       => 'typing_settings',
				)
			),
			'typing_cursor__enable'        => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Show Cursor', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can choose whether or not show the cursor for the typing effect.', 'squad-modules-for-divi' ),
					'default'          => 'on',
					'default_on_front' => 'on',
					'affects'          => array(
						'custom_cursor_icon__enable',
						'custom_cursor_icon_color',
						'custom_cursor_icon_size',
						'custom_cursor_icon_text_gap',
						'remove_cursor_on_end__enable',
					),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'typing_settings',
				)
			),
			'typing_cursor_character'      => array(
				'label'            => esc_html__( 'Cursor Character', 'squad-modules-for-divi' ),
				'description'      => esc_html__( 'The cursor character will appear in the cursor pointer.', 'squad-modules-for-divi' ),
				'type'             => 'text',
				'option_category'  => 'basic_option',
				'default_on_front' => '|',
				'default'          => '|',
				'depends_show_if'  => 'off',
				'tab_slug'         => 'general',
				'toggle_slug'      => 'typing_settings',
			),
			'custom_cursor_icon__enable'   => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Enable Cursor Icon', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Here you can choose whether or not show the icon with cursor for the typing effect.', 'squad-modules-for-divi' ),
					'default'         => 'off',
					'depends_show_if' => 'on',
					'affects'         => array(
						'typing_cursor_character',
						'typing_cursor_icon',
					),
					'tab_slug'        => 'general',
					'toggle_slug'     => 'typing_settings',
				)
			),
			'typing_cursor_icon'           => array(
				'label'            => esc_html__( 'Choose an icon', 'squad-modules-for-divi' ),
				'description'      => esc_html__( 'The cursor character will appear in the cursor pointer.', 'squad-modules-for-divi' ),
				'type'             => 'select_icon',
				'option_category'  => 'basic_option',
				'class'            => array( 'et-pb-font-icon' ),
				'default'          => '&#x4e;||divi||400',
				'default_on_front' => '&#x4e;||divi||400',
				'depends_show_if'  => 'on',
				'tab_slug'         => 'general',
				'toggle_slug'      => 'typing_settings',
			),
			'remove_cursor_on_end__enable' => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Remove Cursor After Completed', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Here you can choose whether or not remove the cursor after completed the typing effect.', 'squad-modules-for-divi' ),
					'default'         => 'off',
					'depends_show_if' => 'on',
					'show_if_not'     => array(
						'typing_loop__enable' => 'on',
					),
					'tab_slug'        => 'general',
					'toggle_slug'     => 'typing_settings',
				)
			),
		);

		// The field definition for typed cursor.
		$typed_cursor = array(
			'custom_cursor_icon_color'    => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Icon Color', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Here you can define a custom color for your cursor icon.', 'squad-modules-for-divi' ),
					'depends_show_if' => 'on',
					'tab_slug'        => 'advanced',
					'toggle_slug'     => 'typed_cursor_element',
				)
			),
			'custom_cursor_icon_size'     => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Icon Size', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Here you can choose cursor icon size.', 'squad-modules-for-divi' ),
					'range_settings'  => array(
						'min'  => '1',
						'max'  => '200',
						'step' => '1',
					),
					'default_unit'    => 'px',
					'depends_show_if' => 'on',
					'tab_slug'        => 'advanced',
					'toggle_slug'     => 'typed_cursor_element',
					'hover'           => false,
				)
			),
			'custom_cursor_icon_text_gap' => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Gap Between Cursor Icon and Text', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Here you can choose gap between icon and text.', 'squad-modules-for-divi' ),
					'range_settings'  => array(
						'min'  => '1',
						'max'  => '200',
						'step' => '1',
					),
					'default_unit'    => 'px',
					'depends_show_if' => 'on',
					'tab_slug'        => 'advanced',
					'toggle_slug'     => 'typed_cursor_element',
					'mobile_options'  => true,
					'hover'           => false,
				)
			),
		);

		// wrapper fields definitions.
		$wrapper_background_fields = $this->squad_utils->field_definitions->add_background_field(
			array(
				'label'       => esc_html__( 'Wrapper Background', 'squad-modules-for-divi' ),
				'base_name'   => 'wrapper_background',
				'context'     => 'wrapper_background_color',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'wrapper',
			)
		);
		$text_associated_fields    = array(
			'horizontal_alignment' => divi_squad()->d4_module_helper->add_alignment_field(
				esc_html__( 'Alignment', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Align icon to the left, right or center.', 'squad-modules-for-divi' ),
					'default_on_front' => 'left',
					'tab_slug'         => 'advanced',
					'toggle_slug'      => 'wrapper',
				)
			),
			'text_gap'             => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Gap Between Texts', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Here you can choose gap between texts.', 'squad-modules-for-divi' ),
					'range_settings' => array(
						'min'       => '0',
						'max'       => '200',
						'step'      => '1',
						'min_limit' => '0',
						'max_limit' => '200',
					),
					'default'        => '10px',
					'default_unit'   => 'px',
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'wrapper',
					'mobile_options' => true,
				),
				array( 'use_hover' => false )
			),
		);
		$wrapper_fields            = array(
			'wrapper_margin'  => divi_squad()->d4_module_helper->add_margin_padding_field(
				esc_html__( 'Wrapper Margin', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Here you can define a custom margin size for the wrapper.', 'squad-modules-for-divi' ),
					'type'        => 'custom_margin',
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'wrapper',
				)
			),
			'wrapper_padding' => divi_squad()->d4_module_helper->add_margin_padding_field(
				esc_html__( 'Wrapper Padding', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__(
						'Here you can define a custom padding size.',
						'squad-modules-for-divi'
					),
					'type'        => 'custom_padding',
					'default'     => '10px|15px|10px|15px|false|false',
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'wrapper',
				)
			),
		);

		// Text associate fields definitions.
		$prefix_background_fields = $this->squad_utils->field_definitions->add_background_field(
			array(
				'label'       => esc_html__( 'Before Background', 'squad-modules-for-divi' ),
				'base_name'   => 'prefix_background',
				'context'     => 'prefix_background_color',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'prefix_element',
			)
		);
		$typed_background_fields  = $this->squad_utils->field_definitions->add_background_field(
			array(
				'label'       => esc_html__( 'Typed Background', 'squad-modules-for-divi' ),
				'base_name'   => 'typed_background',
				'context'     => 'typed_background_color',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'typed_element',
			)
		);
		$suffix_background_fields = $this->squad_utils->field_definitions->add_background_field(
			array(
				'label'       => esc_html__( 'After Background', 'squad-modules-for-divi' ),
				'base_name'   => 'suffix_background',
				'context'     => 'suffix_background_color',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'suffix_element',
			)
		);

		$prefix_text_clip = divi_squad()->d4_module_helper->get_text_clip_fields(
			array(
				'base_attr_name' => 'prefix',
				'tab_slug'       => 'advanced',
				'toggle_slug'    => 'prefix_element',
			)
		);
		$typed_text_clip  = divi_squad()->d4_module_helper->get_text_clip_fields(
			array(
				'base_attr_name' => 'typed',
				'tab_slug'       => 'advanced',
				'toggle_slug'    => 'typed_element',
			)
		);
		$suffix_text_clip = divi_squad()->d4_module_helper->get_text_clip_fields(
			array(
				'base_attr_name' => 'suffix',
				'tab_slug'       => 'advanced',
				'toggle_slug'    => 'suffix_element',
			)
		);

		// Display type field definitions.
		$display_type_fields = array(
			'prefix_display_type' => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Display Type', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'The display CSS property sets whether an element is treated as a block or inline box.', 'squad-modules-for-divi' ),
					'options'        => array(
						''             => esc_html__( 'Default', 'squad-modules-for-divi' ),
						'block'        => esc_html__( 'Block', 'squad-modules-for-divi' ),
						'inline-block' => esc_html__( 'Inline', 'squad-modules-for-divi' ),
					),
					'mobile_options' => true,
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'prefix_element',
				)
			),
			'typed_display_type'  => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Display Type', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'The display CSS property sets whether an element is treated as a block or inline box.', 'squad-modules-for-divi' ),
					'options'        => array(
						''             => esc_html__( 'Default', 'squad-modules-for-divi' ),
						'block'        => esc_html__( 'Block', 'squad-modules-for-divi' ),
						'inline-block' => esc_html__( 'Inline', 'squad-modules-for-divi' ),
					),
					'mobile_options' => true,
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'typed_element',
				)
			),
			'suffix_display_type' => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Display Type', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'The display CSS property sets whether an element is treated as a block or inline box.', 'squad-modules-for-divi' ),
					'options'        => array(
						''             => esc_html__( 'Default', 'squad-modules-for-divi' ),
						'block'        => esc_html__( 'Block', 'squad-modules-for-divi' ),
						'inline-block' => esc_html__( 'Inline', 'squad-modules-for-divi' ),
					),
					'mobile_options' => true,
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'suffix_element',
				)
			),
		);

		// Text associate fields definitions.
		$text_associated_margin_padding_fields = array(
			'prefix_margin'  => divi_squad()->d4_module_helper->add_margin_padding_field(
				esc_html__( 'Before Margin', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Here you can define a custom margin size for the before text.', 'squad-modules-for-divi' ),
					'type'           => 'custom_margin',
					'range_settings' => array(
						'min'  => '1',
						'max'  => '100',
						'step' => '1',
					),
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'prefix_element',
				)
			),
			'prefix_padding' => divi_squad()->d4_module_helper->add_margin_padding_field(
				esc_html__( 'Before Padding', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Here you can define a custom padding size.', 'squad-modules-for-divi' ),
					'type'           => 'custom_padding',
					'range_settings' => array(
						'min'  => '1',
						'max'  => '100',
						'step' => '1',
					),
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'prefix_element',
				)
			),
			'typed_margin'   => divi_squad()->d4_module_helper->add_margin_padding_field(
				esc_html__( 'Typed Margin', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Here you can define a custom margin size for the typed text.', 'squad-modules-for-divi' ),
					'type'           => 'custom_margin',
					'range_settings' => array(
						'min'  => '1',
						'max'  => '100',
						'step' => '1',
					),
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'typed_element',
				)
			),
			'typed_padding'  => divi_squad()->d4_module_helper->add_margin_padding_field(
				esc_html__( 'Typed Padding', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Here you can define a custom padding size.', 'squad-modules-for-divi' ),
					'type'           => 'custom_padding',
					'range_settings' => array(
						'min'  => '1',
						'max'  => '100',
						'step' => '1',
					),
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'typed_element',
				)
			),
			'suffix_margin'  => divi_squad()->d4_module_helper->add_margin_padding_field(
				esc_html__( 'After Margin', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__(
						'Here you can define a custom margin size for the after text.',
						'squad-modules-for-divi'
					),
					'type'           => 'custom_margin',
					'range_settings' => array(
						'min'  => '1',
						'max'  => '100',
						'step' => '1',
					),
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'suffix_element',
				)
			),
			'suffix_padding' => divi_squad()->d4_module_helper->add_margin_padding_field(
				esc_html__( 'After Padding', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Here you can define a custom padding size.', 'squad-modules-for-divi' ),
					'type'           => 'custom_padding',
					'range_settings' => array(
						'min'  => '1',
						'max'  => '100',
						'step' => '1',
					),
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'suffix_element',
				)
			),
		);

		return array_merge(
			$text_fields,
			$typing_effects,
			$typed_cursor,
			$wrapper_background_fields,
			$text_associated_fields,
			$wrapper_fields,
			$prefix_background_fields,
			$typed_background_fields,
			$suffix_background_fields,
			$display_type_fields,
			$prefix_text_clip,
			$typed_text_clip,
			$suffix_text_clip,
			$text_associated_margin_padding_fields
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
		$fields['wrapper_background_color'] = array( 'background' => "$this->main_css_element div .text-elements" );
		$fields['wrapper_margin']           = array( 'margin' => "$this->main_css_element div .text-elements" );
		$fields['wrapper_padding']          = array( 'padding' => "$this->main_css_element div .text-elements" );
		divi_squad()->d4_module_helper->fix_border_transition( $fields, 'wrapper', "$this->main_css_element div .text-elements" );
		divi_squad()->d4_module_helper->fix_box_shadow_transition( $fields, 'wrapper', "$this->main_css_element div .text-elements" );

		// prefix styles.
		$fields['prefix_background_color'] = array( 'background' => "$this->main_css_element div .text-elements .text-item.prefix-element" );
		$fields['prefix_margin']           = array( 'margin' => "$this->main_css_element div .text-elements .text-item.prefix-element" );
		$fields['prefix_padding']          = array( 'padding' => "$this->main_css_element div .text-elements .text-item.prefix-element" );
		divi_squad()->d4_module_helper->fix_fonts_transition( $fields, 'prefix_text', "$this->main_css_element div .text-elements .text-item.prefix-element" );
		divi_squad()->d4_module_helper->fix_border_transition( $fields, 'prefix_element', "$this->main_css_element div .text-elements .text-item.prefix-element" );
		divi_squad()->d4_module_helper->fix_box_shadow_transition( $fields, 'prefix_element', "$this->main_css_element div .text-elements .text-item.prefix-element" );

		// typed styles.
		$fields['typed_background_color']   = array( 'background' => "$this->main_css_element div .text-elements .text-item.typing-element" );
		$fields['custom_cursor_icon_color'] = array( 'color' => "$this->main_css_element div .text-elements .typing-element .typed-cursor" );
		$fields['typed_margin']             = array( 'margin' => "$this->main_css_element div .text-elements .text-item.typing-element" );
		$fields['typed_padding']            = array( 'padding' => "$this->main_css_element div .text-elements .text-item.typing-element" );
		divi_squad()->d4_module_helper->fix_fonts_transition( $fields, 'typed_text', "$this->main_css_element div .text-elements .text-item.typing-element" );
		divi_squad()->d4_module_helper->fix_border_transition( $fields, 'typed_element', "$this->main_css_element div .text-elements .text-item.typing-element" );
		divi_squad()->d4_module_helper->fix_box_shadow_transition( $fields, 'typed_element', "$this->main_css_element div .text-elements .text-item.typing-element" );

		// suffix styles.
		$fields['suffix_background_color'] = array( 'background' => "$this->main_css_element div .text-elements .text-item.suffix-element" );
		$fields['suffix_margin']           = array( 'margin' => "$this->main_css_element div .text-elements .text-item.suffix-element" );
		$fields['suffix_padding']          = array( 'padding' => "$this->main_css_element div .text-elements .text-item.suffix-element" );
		divi_squad()->d4_module_helper->fix_fonts_transition( $fields, 'suffix_text', "$this->main_css_element div .text-elements .text-item.suffix-element" );
		divi_squad()->d4_module_helper->fix_border_transition( $fields, 'suffix_element', "$this->main_css_element div .text-elements .text-item.suffix-element" );
		divi_squad()->d4_module_helper->fix_box_shadow_transition( $fields, 'suffix_element', "$this->main_css_element div .text-elements .text-item.suffix-element" );

		// Default styles.
		$fields['background_layout'] = array( 'color' => "$this->main_css_element div .text-elements .text-item" );

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
		$prefix_content = $this->render_prefix_text( $attrs );
		$typed_content  = $this->render_typed_text( $attrs );
		$suffix_content = $this->render_suffix_text( $attrs );

		if ( empty( $prefix_content ) && empty( $typed_content ) && empty( $suffix_content ) ) {
			return '';
		}

		$this->generate_additional_styles( $attrs );

		wp_enqueue_script( 'squad-module-typing-text' );

		$level = $this->prop( 'text_element_tag', 'h2' );

		return sprintf(
			'<div class="text-elements et_pb_with_background"><%4$s class="text-container">%1$s%2$s%3$s</%4$s></div>',
			wp_kses_post( $prefix_content ),
			wp_kses_post( $typed_content ),
			wp_kses_post( $suffix_content ),
			wp_kses_post( $level )
		);
	}

	/**
	 * Render prefix.
	 *
	 * @param array $attrs List of unprocessed attributes.
	 *
	 * @return string
	 */
	protected function render_prefix_text( array $attrs ): string {
		if ( '' === $this->prop( 'prefix_text' ) ) {
			return '';
		}

		$multi_view = et_pb_multi_view_options( $this );

		// Fixed: the custom background doesn't work at frontend.
		$this->props = array_merge( $attrs, $this->props );

		// the prefix background with default, responsive, hover.
		et_pb_background_options()->get_background_style(
			array(
				'base_prop_name'         => 'prefix_background',
				'props'                  => $this->props,
				'selector'               => "$this->main_css_element div .text-elements .text-item.prefix-element",
				'selector_hover'         => "$this->main_css_element div .text-elements:hover .text-item.prefix-element",
				'selector_sticky'        => "$this->main_css_element div .text-elements .text-item.prefix-element",
				'function_name'          => $this->slug,
				'important'              => ' !important',
				'use_background_video'   => false,
				'use_background_pattern' => false,
				'use_background_mask'    => false,
				'prop_name_aliases'      => array(
					'use_prefix_background_color_gradient' => 'prefix_background_use_color_gradient',
					'prefix_background'                    => 'prefix_background_color',
				),
			)
		);

		// prefix margin and padding with default, responsive, hover.
		$this->squad_utils->field_css_generations->generate_margin_padding_styles(
			array(
				'field'          => 'prefix_margin',
				'selector'       => "$this->main_css_element div .text-elements .text-item.prefix-element",
				'hover_selector' => "$this->main_css_element div .text-elements:hover .text-item.prefix-element",
				'css_property'   => 'margin',
				'type'           => 'margin',
			)
		);
		$this->squad_utils->field_css_generations->generate_margin_padding_styles(
			array(
				'field'          => 'prefix_padding',
				'selector'       => "$this->main_css_element div .text-elements .text-item.prefix-element",
				'hover_selector' => "$this->main_css_element div .text-elements:hover .text-item.prefix-element",
				'css_property'   => 'padding',
				'type'           => 'padding',
			)
		);

		// Add display type support.
		$this->generate_styles(
			array(
				'base_attr_name' => 'prefix_display_type',
				'selector'       => "$this->main_css_element div .text-elements .text-item.prefix-element",
				'css_property'   => 'display',
				'render_slug'    => $this->slug,
				'type'           => 'display',
			)
		);

		$this->squad_utils->field_css_generations->generate_text_clip_styles(
			array(
				'base_attr_name' => 'prefix',
				'selector'       => "$this->main_css_element div .text-elements .text-item.prefix-element",
				'hover'          => "$this->main_css_element div .text-elements:hover .text-item.prefix-element",
			)
		);

		return $multi_view->render_element(
			array(
				'tag'            => 'span',
				'attrs'          => array(
					'class' => 'text-item prefix-element et_pb_with_background',
				),
				'content'        => '{{prefix_text}}',
				'hover_selector' => "$this->main_css_element div .text-elements",
			)
		);
	}

	/**
	 * Render typed text.
	 *
	 * @param array $attrs List of unprocessed attributes.
	 *
	 * @return string
	 */
	protected function render_typed_text( array $attrs ): string {
		if ( '' === $this->prop( 'typed_text' ) ) {
			return '';
		}

		// Fixed: the custom background doesn't work at frontend.
		$this->props = array_merge( $attrs, $this->props );

		// the typed text background with default, responsive, hover.
		et_pb_background_options()->get_background_style(
			array(
				'base_prop_name'         => 'typed_background',
				'props'                  => $this->props,
				'selector'               => "$this->main_css_element div .text-elements .text-item.typing-element",
				'selector_hover'         => "$this->main_css_element div .text-elements:hover .text-item.typing-element",
				'selector_sticky'        => "$this->main_css_element div .text-elements .text-item.typing-element",
				'function_name'          => $this->slug,
				'important'              => ' !important',
				'use_background_video'   => false,
				'use_background_pattern' => false,
				'use_background_mask'    => false,
				'prop_name_aliases'      => array(
					'use_typed_background_color_gradient' => 'typed_background_use_color_gradient',
					'typed_background'                    => 'typed_background_color',
				),
			)
		);

		// the typed text margin and padding with default, responsive, hover.
		$this->squad_utils->field_css_generations->generate_margin_padding_styles(
			array(
				'field'          => 'typed_margin',
				'selector'       => "$this->main_css_element div .text-elements .text-item.typing-element",
				'hover_selector' => "$this->main_css_element div .text-elements:hover .text-item.typing-element",
				'css_property'   => 'margin',
				'type'           => 'margin',
			)
		);
		$this->squad_utils->field_css_generations->generate_margin_padding_styles(
			array(
				'field'          => 'typed_padding',
				'selector'       => "$this->main_css_element div .text-elements .text-item.typing-element",
				'hover_selector' => "$this->main_css_element div .text-elements:hover .text-item.typing-element",
				'css_property'   => 'padding',
				'type'           => 'padding',
			)
		);

		// Add display type support.
		$this->generate_styles(
			array(
				'base_attr_name' => 'typed_display_type',
				'selector'       => "$this->main_css_element div .text-elements .text-item.typing-element",
				'css_property'   => 'display',
				'render_slug'    => $this->slug,
				'type'           => 'display',
			)
		);

		$this->squad_utils->field_css_generations->generate_text_clip_styles(
			array(
				'base_attr_name' => 'typed',
				'selector'       => "$this->main_css_element div .text-elements .text-item.typing-element",
				'hover'          => "$this->main_css_element div .text-elements:hover .text-item.typing-element",
			)
		);

		// working with custom cursor icon.
		if ( 'on' === $this->prop( 'typing_cursor__enable', 'off' ) ) {
			$this->generate_styles(
				array(
					'base_attr_name' => 'custom_cursor_icon_color',
					'selector'       => "$this->main_css_element div .text-elements .typing-element .typed-cursor",
					'hover_selector' => "$this->main_css_element div .text-elements:hover .typing-element .typed-cursor",
					'css_property'   => 'color',
					'render_slug'    => $this->slug,
					'type'           => 'color',
					'important'      => true,
				)
			);
			$this->generate_styles(
				array(
					'base_attr_name' => 'custom_cursor_icon_size',
					'selector'       => "$this->main_css_element div .text-elements .typing-element .typed-cursor",
					'hover_selector' => "$this->main_css_element div .text-elements:hover .typing-element .typed-cursor",
					'css_property'   => 'font-size',
					'render_slug'    => $this->slug,
					'type'           => 'range',
					'important'      => true,
				)
			);
			$this->generate_styles(
				array(
					'base_attr_name' => 'custom_cursor_icon_text_gap',
					'selector'       => "$this->main_css_element div .text-elements .typing-element .typed-cursor",
					'css_property'   => 'margin-left',
					'render_slug'    => $this->slug,
					'type'           => 'gap',
					'important'      => true,
				)
			);

			// working with custom cursor icon.
			if ( 'on' === $this->prop( 'custom_cursor_icon__enable' ) ) {
				// Load font Awesome css for frontend.
				Divi::inject_fa_icons( $this->props['typing_cursor_icon'] );

				$this->generate_styles(
					array(
						'utility_arg'    => 'icon_font_family',
						'render_slug'    => $this->slug,
						'base_attr_name' => 'typing_cursor_icon',
						'important'      => true,
						'selector'       => "$this->main_css_element div .text-elements .typing-element .typed-cursor",
						'processor'      => array(
							'ET_Builder_Module_Helper_Style_Processor',
							'process_extended_icon',
						),
					)
				);
			}
		}

		$typed_options = wp_json_encode(
			array(
				'typeSpeed'    => absint( $this->prop( 'typing_speed' ) ),
				'startDelay'   => absint( $this->prop( 'typing_start_delay' ) ),
				'backSpeed'    => absint( $this->prop( 'typing_back_speed' ) ),
				'backDelay'    => absint( $this->prop( 'typing_back_delay' ) ),
				'fadeOutDelay' => absint( $this->prop( 'typing_fade_out_delay' ) ),
				'shuffle'      => $this->prop( 'typing_shuffle__enable', 'off' ),
				'fadeOut'      => $this->prop( 'typing_fade_out__enable', 'off' ),
				'loop'         => $this->prop( 'typing_loop__enable', 'off' ),
				'showCursor'   => $this->prop( 'typing_cursor__enable', 'on' ),
			)
		);

		$cursor_icon = '|';
		if ( 'on' === $this->prop( 'typing_cursor__enable' ) ) {
			if ( 'on' === $this->prop( 'custom_cursor_icon__enable' ) ) {
				$cursor_icon = et_pb_get_extended_font_icon_value( $this->prop( 'typing_cursor_icon' ) );
			} else {
				$cursor_icon = $this->prop( 'typing_cursor_character' );
			}
		}

		$typed_extra_options = wp_json_encode(
			array(
				'strings'       => $this->prop( 'typing_text' ),
				'remove_cursor' => $this->prop( 'remove_cursor_on_end__enable' ),
				'cursorChar'    => $cursor_icon,
			)
		);

		return sprintf(
			'<span class="text-item typing-element et_pb_with_background" data-typed-options="%1$s" data-typed-extra-options="%2$s"><span class="typed-text"></span></span>',
			esc_attr( $typed_options ),
			esc_attr( $typed_extra_options )
		);
	}

	/**
	 * Render suffix.
	 *
	 * @param array $attrs List of unprocessed attributes.
	 *
	 * @return string
	 */
	protected function render_suffix_text( array $attrs ): string {
		if ( '' === $this->prop( 'suffix_text' ) ) {
			return '';
		}

		$multi_view = et_pb_multi_view_options( $this );

		// Fixed: the custom background doesn't work at frontend.
		$this->props = array_merge( $attrs, $this->props );

		// the suffix background with default, responsive, hover.
		et_pb_background_options()->get_background_style(
			array(
				'base_prop_name'         => 'suffix_background',
				'props'                  => $this->props,
				'selector'               => "$this->main_css_element div .text-elements .text-item.suffix-element",
				'selector_hover'         => "$this->main_css_element div .text-elements:hover .text-item.suffix-element",
				'selector_sticky'        => "$this->main_css_element div .text-elements .text-item.suffix-element",
				'function_name'          => $this->slug,
				'important'              => ' !important',
				'use_background_video'   => false,
				'use_background_pattern' => false,
				'use_background_mask'    => false,
				'prop_name_aliases'      => array(
					'use_suffix_background_color_gradient' => 'suffix_background_use_color_gradient',
					'suffix_background'                    => 'suffix_background_color',
				),
			)
		);

		// the suffix margin and padding with default, responsive, hover.
		$this->squad_utils->field_css_generations->generate_margin_padding_styles(
			array(
				'field'          => 'suffix_margin',
				'selector'       => "$this->main_css_element div .text-elements .text-item.suffix-element",
				'hover_selector' => "$this->main_css_element div .text-elements:hover .text-item.suffix-element",
				'css_property'   => 'margin',
				'type'           => 'margin',
			)
		);
		$this->squad_utils->field_css_generations->generate_margin_padding_styles(
			array(
				'field'          => 'suffix_padding',
				'selector'       => "$this->main_css_element div .text-elements .text-item.suffix-element",
				'hover_selector' => "$this->main_css_element div .text-elements:hover .text-item.suffix-element",
				'css_property'   => 'padding',
				'type'           => 'padding',
			)
		);

		// Add display type support.
		$this->generate_styles(
			array(
				'base_attr_name' => 'suffix_display_type',
				'selector'       => "$this->main_css_element div .text-elements .text-item.suffix-element",
				'css_property'   => 'display',
				'render_slug'    => $this->slug,
				'type'           => 'display',
			)
		);

		$this->squad_utils->field_css_generations->generate_text_clip_styles(
			array(
				'base_attr_name' => 'suffix',
				'selector'       => "$this->main_css_element div .text-elements .text-item.suffix-element",
				'hover'          => "$this->main_css_element div .text-elements:hover .text-item.suffix-element",
			)
		);

		return $multi_view->render_element(
			array(
				'tag'            => 'span',
				'attrs'          => array(
					'class' => 'text-item suffix-element et_pb_with_background',
				),
				'content'        => '{{suffix_text}}',
				'hover_selector' => "$this->main_css_element div .text-elements",
			)
		);
	}

	/**
	 * Renders additional styles for the module output.
	 *
	 * @param array $attrs List of attributes.
	 *
	 * @return void
	 */
	protected function generate_additional_styles( array $attrs ): void {
		// Fixed: the custom background doesn't work at frontend.
		$this->props = array_merge( $attrs, $this->props );

		// wrapper background with default, responsive, hover.
		et_pb_background_options()->get_background_style(
			array(
				'base_prop_name'         => 'wrapper_background',
				'props'                  => $this->props,
				'selector'               => "$this->main_css_element div .text-elements",
				'selector_hover'         => "$this->main_css_element div .text-elements:hover",
				'selector_sticky'        => "$this->main_css_element div .text-elements",
				'function_name'          => $this->slug,
				'use_background_video'   => false,
				'use_background_pattern' => false,
				'use_background_mask'    => false,
				'prop_name_aliases'      => array(
					'use_wrapper_background_color_gradient' => 'wrapper_background_use_color_gradient',
					'wrapper_background'                    => 'wrapper_background_color',
				),
			)
		);

		// wrapper margin with default, responsive, hover.
		$this->squad_utils->field_css_generations->generate_margin_padding_styles(
			array(
				'field'          => 'wrapper_margin',
				'selector'       => "$this->main_css_element div .text-elements",
				'hover_selector' => "$this->main_css_element div .text-elements:hover",
				'css_property'   => 'margin',
				'type'           => 'margin',
			)
		);
		// wrapper padding with default, responsive, hover.
		$this->squad_utils->field_css_generations->generate_margin_padding_styles(
			array(
				'field'          => 'wrapper_padding',
				'selector'       => "$this->main_css_element div .text-elements",
				'hover_selector' => "$this->main_css_element div .text-elements:hover",
				'css_property'   => 'padding',
				'type'           => 'padding',
			)
		);

		$this->generate_styles(
			array(
				'base_attr_name' => 'horizontal_alignment',
				'selector'       => "$this->main_css_element div .text-elements",
				'css_property'   => 'justify-content',
				'render_slug'    => $this->slug,
				'type'           => 'align',
			)
		);
		$this->generate_styles(
			array(
				'base_attr_name' => 'text_gap',
				'selector'       => "$this->main_css_element div .text-elements .text-container .text-item:first-child:after, $this->main_css_element div .text-elements .text-container .text-item:last-child:before",
				'css_property'   => 'width',
				'render_slug'    => $this->slug,
				'type'           => 'range',
			)
		);
	}
}
