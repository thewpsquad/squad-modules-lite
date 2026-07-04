<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Before After Image Slider Module Class which extend the Divi Builder Module Class.
 *
 * This class provides comprehension image adding functionalities for comparable slider in the visual builder.
 *
 * @since   1.0.0
 * @author  The WP Squad <support@squadmodules.com>
 * @package DiviSquad
 */

namespace DiviSquad\Builder\Version4\Modules\Media;

use DiviSquad\Builder\Version4\Abstracts\Module;
use function esc_attr__;
use function esc_html__;
use function et_builder_i18n;
use function et_pb_background_options;
use function et_pb_media_options;
use function et_pb_multi_view_options;
use function sanitize_text_field;
use function wp_enqueue_script;
use function wp_json_encode;
use function wp_kses_post;

/**
 * Before After Image Slider Module Class.
 *
 * @since   1.0.0
 * @package DiviSquad
 */
class BeforeAfterImageSlider extends Module {
	/**
	 * Initiate Module.
	 * Set the module name on init.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init(): void {
		$this->name      = esc_html__( 'Before After Image Slider', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Before After Image Sliders', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'before-after-image-slider.svg' );

		$this->slug             = 'disq_bai_slider';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.$this->slug";

		$this->child_title_var          = 'title';
		$this->child_title_fallback_var = 'admin_label';

		// Connect with utils.
		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		// Declare settings modal toggles for the module.
		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'main_content'        => array(
						'title'             => esc_html__( 'Images', 'squad-modules-for-divi' ),
						'tabbed_subtoggles' => true,
						'sub_toggles'       => array(
							'before' => array( 'name' => esc_html__( 'Before', 'squad-modules-for-divi' ) ),
							'after'  => array( 'name' => esc_html__( 'After', 'squad-modules-for-divi' ) ),
						),
					),
					'comparable_settings' => esc_html__( 'Compare Options', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'before_image_filter'  => esc_html__( 'Before Image Filter', 'squad-modules-for-divi' ),
					'before_label_element' => esc_html__( 'Before Label', 'squad-modules-for-divi' ),
					'before_label_text'    => esc_html__( 'Before Label Text', 'squad-modules-for-divi' ),
					'after_image_filter'   => esc_html__( 'After Image Filter', 'squad-modules-for-divi' ),
					'after_label_element'  => esc_html__( 'After Label', 'squad-modules-for-divi' ),
					'after_label_text'     => esc_html__( 'After Label Text', 'squad-modules-for-divi' ),
				),
			),
		);

		// Declare advanced fields for the module.
		$this->advanced_fields = array(
			'fonts'          => array(
				'before_label_text' => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Label', 'squad-modules-for-divi' ),
					array(
						'font_size'   => array(
							'default' => '16px',
						),
						'text_shadow' => array(
							'show_if' => array(
								'image_label__enable' => 'on',
							),
						),
						'css'         => array(
							'main'  => "$this->main_css_element div .compare-images.icv .icv__label.icv__label-before",
							'hover' => "$this->main_css_element div .compare-images.icv:hover .icv__label.icv__label-before",
						),
					)
				),
				'after_label_text'  => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Label', 'squad-modules-for-divi' ),
					array(
						'font_size'   => array(
							'default' => '16px',
						),
						'text_shadow' => array(
							'show_if' => array(
								'image_label__enable' => 'on',
							),
						),
						'css'         => array(
							'main'  => "$this->main_css_element div .compare-images.icv .icv__label.icv__label-after",
							'hover' => "$this->main_css_element div .compare-images.icv:hover .icv__label.icv__label-after",
						),
					)
				),
			),
			'background'     => divi_squad()->d4_module_helper->selectors_background( $this->main_css_element ),
			'filters'        => array(
				'child_filters_target' => divi_squad()->d4_module_helper->add_filters_field(
					array(
						'toggle_slug' => 'before_image_filter',
						'css'         => array(
							'main'  => "$this->main_css_element div .compare-images.icv .icv__img.icv__img-b",
							'hover' => "$this->main_css_element div .compare-images.icv:hover .icv__img.icv__img-b",
						),
					)
				),
				'css'                  => array(
					'main'  => "$this->main_css_element div .compare-images.icv .icv__img.icv__img-a",
					'hover' => "$this->main_css_element div .compare-images.icv:hover .icv__img.icv__img-a",
				),
				'tab_slug'             => 'advanced',
				'toggle_slug'          => 'after_image_filter',
			),
			'borders'        => array(
				'default'              => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
				'before_label_element' => array(
					'label_prefix'    => esc_html__( 'Label', 'squad-modules-for-divi' ),
					'css'             => array(
						'main' => array(
							'border_radii'        => "$this->main_css_element div .compare-images.icv .icv__label.icv__label-before",
							'border_radii_hover'  => "$this->main_css_element div .compare-images.icv:hover .icv__label.icv__label-before",
							'border_styles'       => "$this->main_css_element div .compare-images.icv .icv__label.icv__label-before",
							'border_styles_hover' => "$this->main_css_element div .compare-images.icv:hover .icv__label.icv__label-before",
						),
					),
					'depends_on'      => array( 'image_label__enable' ),
					'depends_show_if' => 'on',
					'tab_slug'        => 'advanced',
					'toggle_slug'     => 'before_label_element',
				),
				'after_label_element'  => array(
					'label_prefix'    => esc_html__( 'Label', 'squad-modules-for-divi' ),
					'css'             => array(
						'main' => array(
							'border_radii'        => "$this->main_css_element div .compare-images.icv .icv__label.icv__label-after",
							'border_radii_hover'  => "$this->main_css_element div .compare-images.icv:hover .icv__label.icv__label-after",
							'border_styles'       => "$this->main_css_element div .compare-images.icv .icv__label.icv__label-after",
							'border_styles_hover' => "$this->main_css_element div .compare-images.icv:hover .icv__label.icv__label-after",
						),
					),
					'depends_on'      => array( 'image_label__enable' ),
					'depends_show_if' => 'on',
					'tab_slug'        => 'advanced',
					'toggle_slug'     => 'after_label_element',
				),
			),
			'box_shadow'     => array(
				'default'              => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
				'before_label_element' => array(
					'label'             => esc_html__( 'Label Box Shadow', 'squad-modules-for-divi' ),
					'option_category'   => 'layout',
					'css'               => array(
						'main'  => "$this->main_css_element div .compare-images.icv .icv__label.icv__label-before",
						'hover' => "$this->main_css_element div .compare-images.icv:hover .icv__label.icv__label-before",
					),
					'default_on_fronts' => array(
						'color'    => 'rgba(0,0,0,0.3)',
						'position' => 'outer',
					),
					'depends_on'        => array( 'image_label__enable' ),
					'depends_show_if'   => 'on',
					'tab_slug'          => 'advanced',
					'toggle_slug'       => 'before_label_element',
				),
				'after_label_element'  => array(
					'label'             => esc_html__( 'Label Box Shadow', 'squad-modules-for-divi' ),
					'option_category'   => 'layout',
					'css'               => array(
						'main'  => "$this->main_css_element div .compare-images.icv .icv__label.icv__label-after",
						'hover' => "$this->main_css_element div .compare-images.icv:hover .icv__label.icv__label-after",
					),
					'default_on_fronts' => array(
						'color'    => 'rgba(0,0,0,0.3)',
						'position' => 'outer',
					),
					'depends_on'        => array( 'image_label__enable' ),
					'depends_show_if'   => 'on',
					'tab_slug'          => 'advanced',
					'toggle_slug'       => 'after_label_element',
				),
			),
			'margin_padding' => divi_squad()->d4_module_helper->selectors_margin_padding( $this->main_css_element ),
			'width'          => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			'max_width'      => divi_squad()->d4_module_helper->selectors_max_width( $this->main_css_element ),
			'height'         => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			'image_icon'     => false,
			'text'           => false,
			'button'         => false,
		);

		// Declare custom css fields for the module.
		$this->custom_css_fields = array(
			'before_label' => array(
				'label'    => esc_html__( 'Before Label', 'squad-modules-for-divi' ),
				'selector' => 'div .compare-images.icv .icv__label.icv__label-before',
			),
			'after_label'  => array(
				'label'    => esc_html__( 'After Label', 'squad-modules-for-divi' ),
				'selector' => 'div .compare-images.icv .icv__label.icv__label-after',
			),
		);
	}

	/**
	 * Declare general fields for the module
	 *
	 * @since 1.0.0
	 * @return array<string, array<string, string>> List of fields.
	 */
	public function get_fields(): array {
		// Image fields definitions.
		$image_fields = array_merge(
			$this->squad_get_image_fields( 'before' ),
			$this->squad_get_image_fields( 'after' )
		);

		$settings_fields = array(
			'image_label__enable'               => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Show Label', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can choose whether or not use label for image.', 'squad-modules-for-divi' ),
					'default'          => 'off',
					'default_on_front' => 'off',
					'affects'          => array(
						'image_label_hover__enable',
						'before_label_text',
						'before_label_text_font',
						'before_label_text_text_color',
						'before_label_text_text_align',
						'before_label_text_font_size',
						'before_label_text_letter_spacing',
						'before_label_text_line_height',
						'before_label_background_color',
						'before_label_margin',
						'before_label_padding',
						'after_label_text',
						'after_label_text_font',
						'after_label_text_text_color',
						'after_label_text_text_align',
						'after_label_text_font_size',
						'after_label_text_letter_spacing',
						'after_label_text_line_height',
						'after_label_background_color',
						'after_label_margin',
						'after_label_padding',
					),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'comparable_settings',
				)
			),
			'image_label_hover__enable'         => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Show Label On Hover', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can choose whether or not show the label on hover effect.', 'squad-modules-for-divi' ),
					'default'          => 'off',
					'default_on_front' => 'off',
					'depends_show_if'  => 'on',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'comparable_settings',
				)
			),
			'slide_direction_mode'              => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Direction', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Slide image to the vertical or horizontal.', 'squad-modules-for-divi' ),
					'options'          => array(
						'horizontal' => esc_html__( 'Horizontal', 'squad-modules-for-divi' ),
						'vertical'   => esc_html__( 'Vertical', 'squad-modules-for-divi' ),
					),
					'default'          => 'horizontal',
					'default_on_front' => 'horizontal',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'comparable_settings',
				)
			),
			'slide_trigger_type'                => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Movement Trigger', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Move slide image with hover or click.', 'squad-modules-for-divi' ),
					'options'          => array(
						'click' => esc_html__( 'Drag', 'squad-modules-for-divi' ),
						'hover' => esc_html__( 'Hover', 'squad-modules-for-divi' ),
					),
					'default'          => 'click',
					'default_on_front' => 'click',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'comparable_settings',
				)
			),
			'slide_control_color'               => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Control Color', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can define a custom color for your slide control.', 'squad-modules-for-divi' ),
					'default'          => '#FFFFFF',
					'default_on_front' => '#FFFFFF',
					'mobile_options'   => false,
					'sticky'           => false,
					'hover'            => false,
					'tab_slug'         => 'general',
					'toggle_slug'      => 'comparable_settings',
				)
			),
			'slide_control_start_point'         => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Control Starting Point', 'squad-modules-for-divi' ),
				array(
					'description'       => esc_html__( 'Increase the order number to position the item lower.', 'squad-modules-for-divi' ),
					'range_settings'    => array(
						'min_limit' => '1',
						'min'       => '1',
						'max_limit' => '100',
						'max'       => '100',
						'step'      => '1',
					),
					'default'           => 25,
					'default_on_front'  => 25,
					'number_validation' => true,
					'fixed_range'       => true,
					'unitless'          => true,
					'mobile_options'    => false,
					'responsive'        => false,
					'hover'             => false,
					'tab_slug'          => 'general',
					'toggle_slug'       => 'comparable_settings',
				)
			),
			'slide_control_shadow__enable'      => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Show Control Shadow', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can choose whether or not show shadow for slide control.', 'squad-modules-for-divi' ),
					'default'          => 'off',
					'default_on_front' => 'off',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'comparable_settings',
				)
			),
			'slide_control_circle__enable'      => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Enable Circle Control', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can choose whether or not circle slide control.', 'squad-modules-for-divi' ),
					'default'          => 'off',
					'default_on_front' => 'off',
					'affects'          => array(
						'slide_control_circle_blur__enable',
					),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'comparable_settings',
				)
			),
			'slide_control_circle_blur__enable' => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Enable Circle Control Blur', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can choose whether or not blur for circle slide control.', 'squad-modules-for-divi' ),
					'default'          => 'off',
					'default_on_front' => 'off',
					'depends_show_if'  => 'on',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'comparable_settings',
				)
			),
			'slide_control_smoothing__enable'   => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Enable Control Smoothness', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can choose whether or not smoothness for slide control.', 'squad-modules-for-divi' ),
					'default'          => 'off',
					'default_on_front' => 'off',
					'affects'          => array(
						'slide_control_smoothing_amount',
					),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'comparable_settings',
				)
			),
			'slide_control_smoothing_amount'    => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Control Smoothing Amount', 'squad-modules-for-divi' ),
				array(
					'description'       => esc_html__( 'Increase the slide control smoothing.', 'squad-modules-for-divi' ),
					'range_settings'    => array(
						'min_limit' => '1',
						'min'       => '1',
						'max_limit' => '100',
						'max'       => '100',
						'step'      => '1',
					),
					'number_validation' => true,
					'fixed_range'       => true,
					'unitless'          => true,
					'mobile_options'    => false,
					'responsive'        => false,
					'hover'             => false,
					'depends_show_if'   => 'on',
					'tab_slug'          => 'general',
					'toggle_slug'       => 'comparable_settings',
				)
			),
		);

		// Fields definitions.
		return array_merge(
			$image_fields,
			$settings_fields
		);
	}

	/**
	 * Get image and associated fields.
	 *
	 * @param string $image_type The current image name.
	 *
	 * @return array<string, array<string, string>> List of fields.
	 */
	private function squad_get_image_fields( string $image_type ): array {
		// Image fields definitions.
		$image_fields_all = array(
			"{$image_type}_image" => array(
				'label'              => et_builder_i18n( 'Image' ),
				'description'        => esc_html__( 'Upload an image to display.', 'squad-modules-for-divi' ),
				'type'               => 'upload',
				'option_category'    => 'basic_option',
				'upload_button_text' => et_builder_i18n( 'Upload an image' ),
				'choose_text'        => esc_attr__( 'Choose an Image', 'squad-modules-for-divi' ),
				'update_text'        => esc_attr__( 'Set As Image', 'squad-modules-for-divi' ),
				'tab_slug'           => 'general',
				'toggle_slug'        => 'main_content',
				'sub_toggle'         => $image_type,
				'dynamic_content'    => 'image',
			),
			"{$image_type}_alt"   => array(
				'label'           => esc_html__( 'Image Alt Text', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'Define the HTML ALT text for your image here.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'depends_show_if' => 'image',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'main_content',
				'sub_toggle'      => $image_type,
				'dynamic_content' => 'text',
			),
			"{$image_type}_label" => array(
				'label'           => esc_html__( 'Image Label Text', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'The label of your image will appear in with image.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'main_content',
				'sub_toggle'      => $image_type,
				'dynamic_content' => 'text',
			),
		);

		// background fields definitions.
		$label_background_fields = $this->squad_utils->field_definitions->add_background_field(
			array(
				'label'           => esc_html__( 'Label Background', 'squad-modules-for-divi' ),
				'base_name'       => "{$image_type}_label_background",
				'context'         => "{$image_type}_label_background_color",
				'depends_show_if' => 'on',
				'tab_slug'        => 'advanced',
				'toggle_slug'     => "{$image_type}_label_element",
			)
		);

		// label associated fields definitions.
		$label_associate_fields = array(
			"{$image_type}_label_margin"  => divi_squad()->d4_module_helper->add_margin_padding_field(
				esc_html__( 'Label Margin', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Here you can define a custom margin size for the before label.', 'squad-modules-for-divi' ),
					'type'            => 'custom_margin',
					'range_settings'  => array(
						'min_limit' => '1',
						'min'       => '1',
						'max_limit' => '100',
						'max'       => '100',
						'step'      => '1',
					),
					'depends_show_if' => 'on',
					'tab_slug'        => 'advanced',
					'toggle_slug'     => "{$image_type}_label_element",
				)
			),
			"{$image_type}_label_padding" => divi_squad()->d4_module_helper->add_margin_padding_field(
				esc_html__( 'Label Padding', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Here you can define a custom padding size.', 'squad-modules-for-divi' ),
					'type'           => 'custom_padding',
					'range_settings' => array(
						'min_limit' => '1',
						'min'       => '1',
						'max_limit' => '100',
						'max'       => '100',
						'step'      => '1',
					),
					'tab_slug'       => 'advanced',
					'toggle_slug'    => "{$image_type}_label_element",
				)
			),
		);

		return array_merge(
			$image_fields_all,
			$label_background_fields,
			$label_associate_fields
		);
	}

	/**
	 * Get CSS fields transition.
	 *
	 * Add form field options group and background image on the field list.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array<string, string>> List of fields.
	 */
	public function get_transition_fields_css_props(): array {
		$fields = parent::get_transition_fields_css_props();

		// before label styles.
		$fields['before_label_background_color'] = array( 'background' => "$this->main_css_element div .compare-images.icv .icv__label.icv__label-before" );
		$fields['before_label_margin']           = array( 'margin' => "$this->main_css_element div .compare-images.icv .icv__label.icv__label-before" );
		$fields['before_label_padding']          = array( 'padding' => "$this->main_css_element div .compare-images.icv .icv__label.icv__label-before" );
		divi_squad()->d4_module_helper->fix_fonts_transition( $fields, 'before_label_text', "$this->main_css_element div .compare-images.icv .icv__label.icv__label-before" );
		divi_squad()->d4_module_helper->fix_border_transition( $fields, 'before_label_element', "$this->main_css_element div .compare-images.icv .icv__label.icv__label-before" );
		divi_squad()->d4_module_helper->fix_box_shadow_transition( $fields, 'before_label_element', "$this->main_css_element div .compare-images.icv .icv__label.icv__label-before" );

		// after label styles.
		$fields['after_label_background_color'] = array( 'background' => "$this->main_css_element div .compare-images.icv .icv__label.icv__label-after" );
		$fields['after_label_margin']           = array( 'margin' => "$this->main_css_element div .compare-images.icv .icv__label.icv__label-after" );
		$fields['after_label_padding']          = array( 'padding' => "$this->main_css_element div .compare-images.icv .icv__label.icv__label-after" );
		divi_squad()->d4_module_helper->fix_fonts_transition( $fields, 'after_label_text', "$this->main_css_element div .compare-images.icv .icv__label.icv__label-after" );
		divi_squad()->d4_module_helper->fix_border_transition( $fields, 'after_label_element', "$this->main_css_element div .compare-images.icv .icv__label.icv__label-after" );
		divi_squad()->d4_module_helper->fix_box_shadow_transition( $fields, 'after_label_element', "$this->main_css_element div .compare-images.icv .icv__label.icv__label-after" );

		// Default styles.
		$fields['background_layout'] = array( 'color' => $this->main_css_element );

		return $fields;
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
		$multi_view   = et_pb_multi_view_options( $this );
		$before_label = $multi_view->render_element(
			array(
				'content'        => '{{before_label}}',
				'hover_selector' => "$this->main_css_element div .compare-images.icv",
			)
		);
		$after_label  = $multi_view->render_element(
			array(
				'content'        => '{{after_label}}',
				'hover_selector' => "$this->main_css_element div .compare-images.icv",
			)
		);

		$settings = array(
			'controlColor'    => $this->prop( 'slide_control_color', '#FFFFFF' ),
			'controlShadow'   => 'on' === $this->prop( 'slide_control_shadow__enable', 'off' ),
			'addCircle'       => 'on' === $this->prop( 'slide_control_circle__enable', 'off' ),
			'addCircleBlur'   => 'on' === $this->prop( 'slide_control_circle_blur__enable', 'off' ),
			'showLabels'      => 'on' === $this->prop( 'image_label__enable', 'off' ),
			'labelOptions'    => array(
				'before'  => sanitize_text_field( $before_label ),
				'after'   => sanitize_text_field( $after_label ),
				'onHover' => 'on' === $this->prop( 'image_label_hover__enable', 'off' ),
			),
			'smoothing'       => 'on' === $this->prop( 'slide_control_smoothing__enable', 'off' ),
			'smoothingAmount' => absint( $this->prop( 'slide_control_smoothing_amount', 100 ) ),
			'hoverStart'      => 'hover' === $this->prop( 'slide_trigger_type', 'drag' ),
			'verticalMode'    => 'vertical' === $this->prop( 'slide_direction_mode', 'horizontal' ),
			'startingPoint'   => absint( $this->prop( 'slide_control_start_point', 25 ) ),
		);

		// Load image loader.
		$image = divi_squad()->load_image( '/build/admin/images/placeholders' );

		$default_image_url = $image->get_image( 'landscape.svg', 'svg' );
		$default_class     = 'squad-image et_pb_image_wrap';
		$empty_notice      = '';

		if ( is_wp_error( $default_image_url ) ) {
			$default_image_url = '';
		}

		// Generate fallback image for before and after.
		$default_before_image = sprintf( '<img alt="" src="%1$s" class="%2$s"/>', $default_image_url, $default_class );
		$default_after_image  = sprintf( '<img alt="" src="%1$s" class="%2$s" style="%3$s;"/>', $default_image_url, $default_class, 'filter: brightness(60%)' );

		// Verify and set actual and fallback image for before and after.
		$before_image = '' !== $this->prop( 'before_image', '' ) ? $this->squad_render_image( 'before' ) : $default_before_image;
		$after_image  = '' !== $this->prop( 'after_image', '' ) ? $this->squad_render_image( 'after' ) : $default_after_image;

		if ( '' === $this->prop( 'before_image', '' ) && '' === $this->prop( 'after_image', '' ) ) {
			$empty_notice = sprintf(
				'<div class="squad-notice" style="margin-bottom: 20px;">%s</div>',
				esc_html__( 'Add before and after images for comprehension. You are see a preview.', 'squad-modules-for-divi' )
			);
		}

		// Process styles for module output.
		$this->squad_generate_all_styles( $attrs );

		// Images: Add CSS Filters and Mix Blend Mode rules.
		$this->generate_css_filters( $this->slug, '', "$this->main_css_element div .compare-images.icv .icv__img.icv__img-a" );
		$this->generate_css_filters( $this->slug, 'child_', "$this->main_css_element div .compare-images.icv .icv__wrapper" );

		wp_enqueue_script( 'squad-module-ba-image-slider' );

		return sprintf(
			'%1$s<div class="compare-images" data-setting="%4$s">%2$s%3$s</div>',
			wp_kses_post( $empty_notice ),
			wp_kses_post( $before_image ),
			wp_kses_post( $after_image ),
			esc_attr( (string) wp_json_encode( $settings ) )
		);
	}

	/**
	 * Render image.
	 *
	 * @param string $image_type The image type.
	 *
	 * @return string
	 */
	private function squad_render_image( string $image_type ): string {
		$multi_view = et_pb_multi_view_options( $this );
		$alt_text   = $this->_esc_attr( "{$image_type}_alt" );

		$image_classes          = 'squad-image et_pb_image_wrap';
		$image_attachment_class = et_pb_media_options()->get_image_attachment_class( $this->props, "'{$image_type}_image' " );
		if ( '' !== $image_attachment_class ) {
			$image_classes .= " $image_attachment_class";
		}

		return $multi_view->render_element(
			array(
				'tag'            => 'img',
				'attrs'          => array(
					'src'   => "{{{$image_type}_image}}",
					'class' => $image_classes,
					'alt'   => $alt_text,
				),
				'required'       => "{$image_type}_image",
				'hover_selector' => "$this->main_css_element div .compare-images.icv",
			)
		);
	}

	/**
	 * Process styles for module output.
	 *
	 * @param array<string, string> $attrs List of unprocessed attributes.
	 *
	 * @return void
	 */
	private function squad_generate_all_styles( array $attrs ): void {
		// Fixed: the custom background doesn't work at frontend.
		$this->props = array_merge( $attrs, $this->props );

		// background with default, responsive, hover.
		et_pb_background_options()->get_background_style(
			array(
				'base_prop_name'         => 'before_label_background',
				'props'                  => $this->props,
				'selector'               => "$this->main_css_element div .compare-images.icv .icv__label.icv__label-before",
				'selector_hover'         => "$this->main_css_element div .compare-images.icv:hover .icv__label.icv__label-before",
				'selector_sticky'        => "$this->main_css_element div .compare-images.icv .icv__label.icv__label-before",
				'function_name'          => $this->slug,
				'important'              => ' !important',
				'use_background_video'   => false,
				'use_background_pattern' => false,
				'use_background_mask'    => false,
				'prop_name_aliases'      => array(
					'use_before_label_background_color_gradient' => 'before_label_background_use_color_gradient',
					'before_label_background'                    => 'before_label_background_color',
				),
			)
		);
		et_pb_background_options()->get_background_style(
			array(
				'base_prop_name'         => 'after_label_background',
				'props'                  => $this->props,
				'selector'               => "$this->main_css_element div .compare-images.icv .icv__label.icv__label-after",
				'selector_hover'         => "$this->main_css_element div .compare-images.icv:hover .icv__label.icv__label-after",
				'selector_sticky'        => "$this->main_css_element div .compare-images.icv .icv__label.icv__label-after",
				'function_name'          => $this->slug,
				'important'              => ' !important',
				'use_background_video'   => false,
				'use_background_pattern' => false,
				'use_background_mask'    => false,
				'prop_name_aliases'      => array(
					'use_after_label_background_color_gradient' => 'after_label_background_use_color_gradient',
					'after_label_background'                    => 'after_label_background_color',
				),
			)
		);

		// margin and padding with default, responsive, hover.
		$this->squad_utils->field_css_generations->generate_margin_padding_styles(
			array(
				'field'          => 'before_label_margin',
				'selector'       => "$this->main_css_element div .compare-images.icv .icv__label.icv__label-before",
				'hover_selector' => "$this->main_css_element div .compare-images.icv:hover .icv__label.icv__label-before",
				'css_property'   => 'margin',
				'type'           => 'margin',
			)
		);
		$this->squad_utils->field_css_generations->generate_margin_padding_styles(
			array(
				'field'          => 'before_label_padding',
				'selector'       => "$this->main_css_element div .compare-images.icv .icv__label.icv__label-before",
				'hover_selector' => "$this->main_css_element div .compare-images.icv:hover .icv__label.icv__label-before",
				'css_property'   => 'padding',
				'type'           => 'padding',
			)
		);
		$this->squad_utils->field_css_generations->generate_margin_padding_styles(
			array(
				'field'          => 'after_label_margin',
				'selector'       => "$this->main_css_element div .compare-images.icv .icv__label.icv__label-after",
				'hover_selector' => "$this->main_css_element div .compare-images.icv:hover .icv__label.icv__label-after",
				'css_property'   => 'margin',
				'type'           => 'margin',
			)
		);
		$this->squad_utils->field_css_generations->generate_margin_padding_styles(
			array(
				'field'          => 'after_label_padding',
				'selector'       => "$this->main_css_element div .compare-images.icv .icv__label.icv__label-after",
				'hover_selector' => "$this->main_css_element div .compare-images.icv:hover .icv__label.icv__label-after",
				'css_property'   => 'padding',
				'type'           => 'padding',
			)
		);
	}
}
