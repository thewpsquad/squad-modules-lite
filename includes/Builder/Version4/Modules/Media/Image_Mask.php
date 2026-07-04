<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Image Mask Module Class which extend the Divi Builder Module Class.
 *
 * This class provides advanced mask adding functionalities for images in the Divi visual builder.
 * It supports multiple mask shapes, secondary masks, border layers, decorative elements,
 * and custom viewBox settings for precise image masking and styling.
 *
 * Features:
 * - 20+ predefined mask shapes with secondary mask support
 * - Border layer with solid color or gradient backgrounds
 * - Two decorative element layers with positioning and styling controls
 * - Custom viewBox settings for SVG coordinate system control
 * - Image transformation controls (positioning, scaling, rotation)
 * - Flip and rotation controls for mask shapes
 *
 * @since   1.0.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Media;

use DiviSquad\Builder\Version4\Abstracts\Module;
use function apply_filters;
use function esc_attr;
use function esc_attr__;
use function esc_html__;
use function et_builder_i18n;

/**
 * Image Mask Module Class.
 *
 * @since   1.0.0
 * @package DiviSquad
 */
class Image_Mask extends Module {
	/**
	 * Initiate Module.
	 * Set the module name on init.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init(): void {
		$this->name      = esc_html__( 'Image Mask', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Image Masks', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'image-mask.svg' );

		$this->slug             = 'disq_image_mask';
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
					'image'            => esc_html__( 'Image', 'squad-modules-for-divi' ),
					'mask_settings'    => esc_html__( 'Mask Options', 'squad-modules-for-divi' ),
					'border_layer'     => esc_html__( 'Border Layer', 'squad-modules-for-divi' ),
					'decoration_1'     => esc_html__( 'Decoration Element 1', 'squad-modules-for-divi' ),
					'decoration_2'     => esc_html__( 'Decoration Element 2', 'squad-modules-for-divi' ),
					'viewbox_settings' => esc_html__( 'ViewBox Settings', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'image' => esc_html__( 'Image', 'squad-modules-for-divi' ),
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
			'height'         => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			'fonts'          => false,
			'image_icon'     => false,
			'text'           => false,
			'button'         => false,
			'filters'        => false,
		);

		// Declare custom css fields for the module.
		$this->custom_css_fields = array(
			'image' => array(
				'label'    => esc_html__( 'Image', 'squad-modules-for-divi' ),
				'selector' => 'div .image-elements .squad-mask-image',
			),
		);
	}

	/**
	 * Declare general fields for the module.
	 *
	 * Defines all the configuration fields available in the module settings,
	 * including image upload, mask shape selection, border layers, decorative
	 * elements, viewBox settings, and advanced image positioning controls.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array<string, mixed>> Array of field definitions for the module.
	 */
	public function get_fields(): array {
		// Image fields definitions.
		$image_fields = array(
			'image' => array(
				'label'              => esc_html__( 'Image', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Upload an image to display at the top.', 'squad-modules-for-divi' ),
				'type'               => 'upload',
				'option_category'    => 'basic_option',
				'upload_button_text' => et_builder_i18n( 'Upload an image' ),
				'choose_text'        => esc_attr__( 'Choose an Image', 'squad-modules-for-divi' ),
				'update_text'        => esc_attr__( 'Set As Image', 'squad-modules-for-divi' ),
				'tab_slug'           => 'general',
				'toggle_slug'        => 'image',
				'dynamic_content'    => 'image',
			),
			'alt'   => array(
				'label'           => esc_html__( 'Image Alt Text', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'Define the HTML ALT text for your image here.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'image',
				'dynamic_content' => 'text',
			),
		);

		/**
		 * Filter the list of shapes available for the image mask module.
		 *
		 * @param array $shapes_list List of shapes available for the image mask module.
		 */
		$shapes_list = apply_filters(
			'divi_squad_image_mask_module_shapes',
			array(
				'shape-01' => esc_html__( 'Mask 01', 'squad-modules-for-divi' ),
				'shape-02' => esc_html__( 'Mask 02', 'squad-modules-for-divi' ),
				'shape-03' => esc_html__( 'Mask 03', 'squad-modules-for-divi' ),
				'shape-04' => esc_html__( 'Mask 04', 'squad-modules-for-divi' ),
				'shape-05' => esc_html__( 'Mask 05', 'squad-modules-for-divi' ),
				'shape-06' => esc_html__( 'Mask 06', 'squad-modules-for-divi' ),
				'shape-07' => esc_html__( 'Mask 07', 'squad-modules-for-divi' ),
				'shape-08' => esc_html__( 'Mask 08', 'squad-modules-for-divi' ),
				'shape-09' => esc_html__( 'Mask 09', 'squad-modules-for-divi' ),
				'shape-10' => esc_html__( 'Mask 10', 'squad-modules-for-divi' ),
				'shape-11' => esc_html__( 'Mask 11', 'squad-modules-for-divi' ),
				'shape-12' => esc_html__( 'Mask 12', 'squad-modules-for-divi' ),
				'shape-13' => esc_html__( 'Mask 13', 'squad-modules-for-divi' ),
				'shape-14' => esc_html__( 'Mask 14', 'squad-modules-for-divi' ),
				'shape-15' => esc_html__( 'Mask 15', 'squad-modules-for-divi' ),
				'shape-16' => esc_html__( 'Mask 16', 'squad-modules-for-divi' ),
				'shape-17' => esc_html__( 'Mask 17', 'squad-modules-for-divi' ),
				'shape-18' => esc_html__( 'Mask 18', 'squad-modules-for-divi' ),
				'shape-19' => esc_html__( 'Mask 19', 'squad-modules-for-divi' ),
				'shape-20' => esc_html__( 'Mask 20', 'squad-modules-for-divi' ),
			)
		);

		/**
		 * Filter the list of pro fields available for the image mask module.
		 *
		 * @param array $mask_settings_pro_fields List of pro fields available for the image mask module.
		 */
		$mask_settings_pro_fields = apply_filters( 'divi_squad_image_mask_module_pro_fields', array() );

		// Mask fields definitions.
		$mask_settings_fields = array(
			'mask_shape_image'     => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Mask Shape', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can choose mask shape for the image.', 'squad-modules-for-divi' ),
					'options'          => $shapes_list,
					'default'          => 'shape-01',
					'default_on_front' => 'shape-01',
					'depends_show_if'  => 'off',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'mask_settings',
				)
			),
			'mask_shape_secondary' => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Secondary Mask Shape', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Enable this option to add a secondary mask shape to the image.', 'squad-modules-for-divi' ),
					'default_on_front' => 'off',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'mask_settings',
				)
			),
			'mask_shape_rotate'    => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Rotate Mask Shape', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can choose mask shape rotation.', 'squad-modules-for-divi' ),
					'range_settings'   => array(
						'min_limit' => '0',
						'min'       => '0',
						'max_limit' => '360',
						'max'       => '360',
						'step'      => '1',
					),
					'fixed_unit'       => 'deg',
					'default'          => '0deg',
					'default_on_front' => '0deg',
					'mobile_options'   => false,
					'hover'            => false,
					'tab_slug'         => 'general',
					'toggle_slug'      => 'mask_settings',
				)
			),
			'mask_shape_scale_x'   => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Mask Shape Width', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can choose mask shape width.', 'squad-modules-for-divi' ),
					'range_settings'   => array(
						'min_limit' => '0',
						'min'       => '0',
						'max_limit' => '2',
						'max'       => '2',
						'step'      => '0.01',
					),
					'unitless'         => true,
					'default'          => '1',
					'default_on_front' => '1',
					'mobile_options'   => false,
					'hover'            => false,
					'tab_slug'         => 'general',
					'toggle_slug'      => 'mask_settings',
				)
			),
			'mask_shape_scale_y'   => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Mask Shape Height', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can choose mask shape height.', 'squad-modules-for-divi' ),
					'range_settings'   => array(
						'min_limit' => '0',
						'min'       => '0',
						'max_limit' => '2',
						'max'       => '2',
						'step'      => '0.01',
					),
					'unitless'         => true,
					'default'          => '1',
					'default_on_front' => '1',
					'mobile_options'   => false,
					'hover'            => false,
					'tab_slug'         => 'general',
					'toggle_slug'      => 'mask_settings',
				)
			),
			'mask_shape_flip'      => array(
				'label'            => esc_html__( 'Flip Mask Shape', 'squad-modules-for-divi' ),
				'description'      => esc_html__( 'Flip the mask horizontally or vertically to change the shape and its direction.', 'squad-modules-for-divi' ),
				'type'             => 'multiple_buttons',
				'option_category'  => 'basic_option',
				'options'          => array(
					'horizontal' => array(
						'title' => esc_html__( 'Horizontal', 'squad-modules-for-divi' ),
						'icon'  => 'flip-horizontally',
					),
					'vertical'   => array(
						'title' => esc_html__( 'Vertical', 'squad-modules-for-divi' ),
						'icon'  => 'flip-vertically',
					),
				),
				'toggleable'       => true,
				'multi_selection'  => true,
				'default'          => '',
				'default_on_front' => '',
				'tab_slug'         => 'general',
				'toggle_slug'      => 'mask_settings',
			),
		);

		// Border layer fields (Layer 1).
		$border_layer_fields = array(
			'layer_1_enable'               => array(
				'label'           => esc_html__( 'Enable Border Layer', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'Enable a border layer behind the image.', 'squad-modules-for-divi' ),
				'type'            => 'yes_no_button',
				'option_category' => 'basic_option',
				'options'         => array(
					'off' => esc_html__( 'No', 'squad-modules-for-divi' ),
					'on'  => esc_html__( 'Yes', 'squad-modules-for-divi' ),
				),
				'default'         => 'on',
				'affects'         => array(
					'layer_1_background_type',
				),
				'tab_slug'        => 'general',
				'toggle_slug'     => 'border_layer',
			),
			'layer_1_background_type'      => array(
				'label'           => esc_html__( 'Background Type', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'Choose between solid color or gradient for the border layer.', 'squad-modules-for-divi' ),
				'type'            => 'select',
				'option_category' => 'basic_option',
				'options'         => array(
					'solid'    => esc_html__( 'Solid Color', 'squad-modules-for-divi' ),
					'gradient' => esc_html__( 'Gradient', 'squad-modules-for-divi' ),
				),
				'default'         => 'gradient',
				'affects'         => array(
					'layer_1_background_color',
					'layer_1_gradient_color_start',
					'layer_1_gradient_color_end',
				),
				'depends_show_if' => 'on',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'border_layer',
			),
			'layer_1_background_color'     => array(
				'label'           => esc_html__( 'Background Color', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'Choose a solid background color for the border layer.', 'squad-modules-for-divi' ),
				'type'            => 'color-alpha',
				'depends_show_if' => 'solid',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'border_layer',
				'default'         => '#ffffff',
			),
			'layer_1_gradient_color_start' => array(
				'label'           => esc_html__( 'Gradient Start Color', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'Choose the starting color for the gradient.', 'squad-modules-for-divi' ),
				'type'            => 'color-alpha',
				'default'         => '#ff5733',
				'depends_show_if' => 'gradient',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'border_layer',
			),
			'layer_1_gradient_color_end'   => array(
				'label'           => esc_html__( 'Gradient End Color', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'Choose the ending color for the gradient.', 'squad-modules-for-divi' ),
				'type'            => 'color-alpha',
				'default'         => '#33c4ff',
				'depends_show_if' => 'gradient',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'border_layer',
			),
		);

		// Decoration layer fields (Layer 2 and Layer 3).
		$decoration_fields = array();
		foreach ( array( 2, 3 ) as $layer ) {
			$current_layer = (string) ( $layer - 1 ); // Adjust layer index for display.

			$decoration_fields["layer_{$layer}_enable"]           = divi_squad()->d4_module_helper->add_yes_no_field(
			// translators: %s is the layer number.
				sprintf( esc_html__( 'Enable Decoration Layer %s', 'squad-modules-for-divi' ), $current_layer ),
				array(
					// translators: %s is the layer number.
					'description'     => sprintf( esc_html__( 'Enable a decorative element for layer %s.', 'squad-modules-for-divi' ), $current_layer ),
					'option_category' => 'basic_option',
					'default'         => 2 === $layer ? 'on' : 'off',
					'affects'         => array(
						"decoration_element_{$layer}",
						"layer_{$layer}_above_image",
						"layer_{$layer}_background_color",
						"layer_{$layer}_horz",
						"layer_{$layer}_vert",
						"layer_{$layer}_scale",
						"layer_{$layer}_rotate",
					),
					'tab_slug'        => 'general',
					'toggle_slug'     => "decoration_{$current_layer}",
				)
			);
			$decoration_fields["decoration_element_{$layer}"]     = divi_squad()->d4_module_helper->add_select_box_field(
			// translators: %s is the layer number.
				sprintf( esc_html__( 'Decoration Element %s', 'squad-modules-for-divi' ), $current_layer ),
				array(
					// translators: %s is the layer number.
					'description'     => sprintf( esc_html__( 'Choose a decorative element for layer %s.', 'squad-modules-for-divi' ), $current_layer ),
					'option_category' => 'basic_option',
					'options'         => array(
						'none'          => esc_html__( 'None', 'squad-modules-for-divi' ),
						'lined-circle'  => esc_html__( 'Lined Circle', 'squad-modules-for-divi' ),
						'dotted-square' => esc_html__( 'Dotted Square', 'squad-modules-for-divi' ),
					),
					'default'         => 2 === $layer ? 'lined-circle' : 'dotted-square',
					'depends_show_if' => 'on',
					'tab_slug'        => 'general',
					'toggle_slug'     => "decoration_{$current_layer}",
				)
			);
			$decoration_fields["layer_{$layer}_above_image"]      = divi_squad()->d4_module_helper->add_yes_no_field(
			// translators: %s is the layer number.
				sprintf( esc_html__( 'Place Decoration Element %s Above Image', 'squad-modules-for-divi' ), $current_layer ),
				array(
					// translators: %s is the layer number.
					'description'     => sprintf( esc_html__( 'Place decoration element %s above the image.', 'squad-modules-for-divi' ), $current_layer ),
					'option_category' => 'basic_option',
					'default'         => 3 === $layer ? 'on' : 'off',
					'depends_show_if' => 'on',
					'tab_slug'        => 'general',
					'toggle_slug'     => "decoration_{$current_layer}",
				)
			);
			$decoration_fields["layer_{$layer}_background_color"] = divi_squad()->d4_module_helper->add_color_field(
			// translators: %s is the layer number.
				sprintf( esc_html__( 'Decoration %s Color', 'squad-modules-for-divi' ), $current_layer ),
				array(
					// translators: %s is the layer number.
					'description'     => sprintf( esc_html__( 'Choose a color for decoration element %s.', 'squad-modules-for-divi' ), $current_layer ),
					'default'         => 2 === $layer ? '#ff0000' : '#00ff00',
					'depends_show_if' => 'on',
					'tab_slug'        => 'general',
					'toggle_slug'     => "decoration_{$current_layer}",
				)
			);
			$decoration_fields["layer_{$layer}_horz"]             = divi_squad()->d4_module_helper->add_range_field(
			// translators: %s is the layer number.
				sprintf( esc_html__( 'Decoration %s Horizontal Position', 'squad-modules-for-divi' ), $current_layer ),
				array(
					// translators: %s is the layer number.
					'description'     => sprintf( esc_html__( 'Adjust the horizontal position of decoration %s.', 'squad-modules-for-divi' ), $current_layer ),
					'range_settings'  => array(
						'min'  => - 100,
						'max'  => 100,
						'step' => 1,
					),
					'default'         => 2 === $layer ? '25' : '-15',
					'default_unit'    => '%',
					'depends_show_if' => 'on',
					'tab_slug'        => 'general',
					'toggle_slug'     => "decoration_{$current_layer}",
				)
			);
			$decoration_fields["layer_{$layer}_vert"]             = divi_squad()->d4_module_helper->add_range_field(
			// translators: %s is the layer number.
				sprintf( esc_html__( 'Decoration %s Vertical Position', 'squad-modules-for-divi' ), $current_layer ),
				array(
					// translators: %s is the layer number.
					'description'     => sprintf( esc_html__( 'Adjust the vertical position of decoration %s.', 'squad-modules-for-divi' ), $current_layer ),
					'range_settings'  => array(
						'min'  => - 100,
						'max'  => 100,
						'step' => 1,
					),
					'default'         => 2 === $layer ? '-25' : '30',
					'default_unit'    => '%',
					'depends_show_if' => 'on',
					'tab_slug'        => 'general',
					'toggle_slug'     => "decoration_{$current_layer}",
				)
			);
			$decoration_fields["layer_{$layer}_scale"]            = divi_squad()->d4_module_helper->add_range_field(
			// translators: %s is the layer number.
				sprintf( esc_html__( 'Decoration %s Scale', 'squad-modules-for-divi' ), $current_layer ),
				array(
					// translators: %s is the layer number.
					'description'     => sprintf( esc_html__( 'Adjust the scale of decoration %s.', 'squad-modules-for-divi' ), $current_layer ),
					'range_settings'  => array(
						'min'  => 0,
						'max'  => 3,
						'step' => 0.01,
					),
					'default'         => 2 === $layer ? '1' : '0.8',
					'unitless'        => true,
					'depends_show_if' => 'on',
					'tab_slug'        => 'general',
					'toggle_slug'     => "decoration_{$current_layer}",
				)
			);
			$decoration_fields["layer_{$layer}_rotate"]           = divi_squad()->d4_module_helper->add_range_field(
			// translators: %s is the layer number.
				sprintf( esc_html__( 'Decoration %s Rotation', 'squad-modules-for-divi' ), $current_layer ),
				array(
					// translators: %s is the layer number.
					'description'     => sprintf( esc_html__( 'Adjust the rotation of decoration %s.', 'squad-modules-for-divi' ), $current_layer ),
					'range_settings'  => array(
						'min'  => 0,
						'max'  => 360,
						'step' => 1,
					),
					'default'         => 2 === $layer ? '30deg' : '45deg',
					'fixed_unit'      => 'deg',
					'depends_show_if' => 'on',
					'tab_slug'        => 'general',
					'toggle_slug'     => "decoration_{$current_layer}",
				)
			);
		}

		// ViewBox settings fields.
		$viewbox_fields = array(
			'enable_custom_viewbox' => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Use Custom ViewBox', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Enable to customize the SVG viewBox.', 'squad-modules-for-divi' ),
					'option_category' => 'basic_option',
					'default'         => 'off',
					'affects'         => array(
						'viewbox_min_x',
						'viewbox_min_y',
						'viewbox_width',
						'viewbox_height',
					),
					'tab_slug'        => 'general',
					'toggle_slug'     => 'viewbox_settings',
				)
			),
			'viewbox_min_x'         => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'ViewBox Min-X', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Adjust the minimum X coordinate of the SVG viewBox.', 'squad-modules-for-divi' ),
					'range_settings'  => array(
						'min'  => - 1000,
						'max'  => 1000,
						'step' => 1,
					),
					'default'         => '0',
					'unitless'        => true,
					'depends_show_if' => 'on',
					'tab_slug'        => 'general',
					'toggle_slug'     => 'viewbox_settings',
				)
			),
			'viewbox_min_y'         => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'ViewBox Min-Y', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Adjust the minimum Y coordinate of the SVG viewBox.', 'squad-modules-for-divi' ),
					'range_settings'  => array(
						'min'  => - 1000,
						'max'  => 1000,
						'step' => 1,
					),
					'default'         => '0',
					'unitless'        => true,
					'depends_show_if' => 'on',
					'tab_slug'        => 'general',
					'toggle_slug'     => 'viewbox_settings',
				)
			),
			'viewbox_width'         => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'ViewBox Width', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Adjust the width of the SVG viewBox.', 'squad-modules-for-divi' ),
					'range_settings'  => array(
						'min'  => 100,
						'max'  => 2000,
						'step' => 1,
					),
					'default'         => '1000',
					'unitless'        => true,
					'depends_show_if' => 'on',
					'tab_slug'        => 'general',
					'toggle_slug'     => 'viewbox_settings',
				)
			),
			'viewbox_height'        => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'ViewBox Height', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Adjust the height of the SVG viewBox.', 'squad-modules-for-divi' ),
					'range_settings'  => array(
						'min'  => 100,
						'max'  => 2000,
						'step' => 1,
					),
					'default'         => '1000',
					'unitless'        => true,
					'depends_show_if' => 'on',
					'tab_slug'        => 'general',
					'toggle_slug'     => 'viewbox_settings',
				)
			),
		);

		$image_associated_fields = array(
			'image_width'               => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Image Width', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can choose image width.', 'squad-modules-for-divi' ),
					'range_settings'   => array(
						'min'  => '1',
						'max'  => '200',
						'step' => '1',
					),
					'allowed_units'    => array( '%', 'em', 'rem', 'px', 'cm', 'mm', 'in', 'pt', 'pc', 'ex', 'vh', 'vw' ),
					'allow_empty'      => true,
					'default_unit'     => '%',
					'default'          => '100%',
					'default_on_front' => '100%',
					'hover'            => false,
					'tab_slug'         => 'advanced',
					'toggle_slug'      => 'image',
				)
			),
			'image_height'              => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Image Height', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Here you can choose image height.', 'squad-modules-for-divi' ),
					'range_settings' => array(
						'min'  => '1',
						'max'  => '200',
						'step' => '1',
					),
					'allowed_units'  => array( '%', 'em', 'rem', 'px', 'cm', 'mm', 'in', 'pt', 'pc', 'ex', 'vh', 'vw' ),
					'allow_empty'    => true,
					'default_unit'   => '%',
					'hover'          => false,
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'image',
				)
			),
			'image_horizontal_position' => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Image Horizontal Position', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Here you can choose image horizontal position.', 'squad-modules-for-divi' ),
					'range_settings' => array(
						'min'  => - 1000,
						'max'  => 1000,
						'step' => 1,
					),
					'default'        => '0',
					'unitless'       => true,
					'hover'          => false,
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'image',
				)
			),
			'image_vertical_position'   => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Image Vertical Position', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Here you can choose image vertical position.', 'squad-modules-for-divi' ),
					'range_settings' => array(
						'min'  => - 1000,
						'max'  => 1000,
						'step' => 1,
					),
					'default'        => '0',
					'unitless'       => true,
					'hover'          => false,
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'image',
				)
			),
		);

		return array_merge(
			$image_fields,
			$mask_settings_pro_fields,
			$mask_settings_fields,
			$border_layer_fields,
			$decoration_fields,
			$viewbox_fields,
			$image_associated_fields
		);
	}

	/**
	 * Renders the module output.
	 *
	 * Generates the complete HTML and SVG markup for the image mask module,
	 * including the masked image, border layers, decorative elements, and all
	 * applied transformations and styling.
	 *
	 * @since 1.0.0
	 *
	 * @param array<array-key, mixed> $attrs       List of attributes.
	 * @param string                  $content     Content being processed.
	 * @param string                  $render_slug Slug of module that is used for rendering output.
	 *
	 * @return string The complete HTML output for the module.
	 */
	public function render( $attrs, $content, $render_slug ): string {
		$unique_id = (string) self::get_module_order_class( $this->slug );
		$image_src = $this->prop( 'image' );
		$alt_text  = $this->_esc_attr( 'alt' );

		$image_shape          = $this->prop( 'mask_shape_image', 'shape-01' );
		$mask_shape_secondary = $this->prop( 'mask_shape_secondary', 'off' );
		$mask_shape           = $this->squad_utils->masking_shapes->get_shape( $image_shape, $mask_shape_secondary );

		/**
		 * Filter the mask shape SVG content before rendering.
		 *
		 * @since 1.0.0
		 *
		 * @param string     $mask_shape The SVG content for the mask shape.
		 * @param array      $attrs      List of module attributes.
		 * @param Image_Mask $module     Current module instance.
		 */
		$mask_shape = (string) apply_filters( 'divi_squad_module_image_mask_shape', $mask_shape, $attrs, $this );

		// Ensure mask shape is properly escaped for SVG output.
		if ( '' !== $mask_shape ) {
			$mask_shape = wp_kses(
				$mask_shape,
				array(
					'path'    => array(
						'd'      => array(),
						'class'  => array(),
						'fill'   => array(),
						'stroke' => array(),
					),
					'g'       => array(
						'class'     => array(),
						'transform' => array(),
					),
					'circle'  => array(
						'cx'    => array(),
						'cy'    => array(),
						'r'     => array(),
						'class' => array(),
						'fill'  => array(),
					),
					'rect'    => array(
						'x'      => array(),
						'y'      => array(),
						'width'  => array(),
						'height' => array(),
						'class'  => array(),
						'fill'   => array(),
					),
					'polygon' => array(
						'points' => array(),
						'class'  => array(),
						'fill'   => array(),
					),
					'ellipse' => array(
						'cx'    => array(),
						'cy'    => array(),
						'rx'    => array(),
						'ry'    => array(),
						'class' => array(),
						'fill'  => array(),
					),
				)
			);
		}

		// Log error if mask shape is empty.
		if ( '' === $mask_shape ) {
			return '';
		}

		// Mask transformations.
		$mask_transform = sprintf(
			'rotate(%s) scale(%s, %s)',
			$this->prop( 'mask_shape_rotate', '0deg' ),
			$this->prop( 'mask_shape_scale_x', '1' ),
			$this->prop( 'mask_shape_scale_y', '1' )
		);
		$mask_flips     = explode( '|', $this->prop( 'mask_shape_flip', '' ) );
		if ( in_array( 'horizontal', $mask_flips, true ) ) {
			$mask_transform .= ' scale(-1, 1)';
		}
		if ( in_array( 'vertical', $mask_flips, true ) ) {
			$mask_transform .= ' scale(1, -1)';
		}

		// Image transformations.
		$image_transform = sprintf(
			'matrix(1 0 0 1 %s %s)',
			$this->prop( 'image_horizontal_position', '0' ),
			$this->prop( 'image_vertical_position', '0' )
		);

		// ViewBox settings.
		if ( $this->prop( 'enable_custom_viewbox', 'off' ) === 'on' ) {
			$viewbox = sprintf(
				'%s %s %s %s',
				$this->prop( 'viewbox_min_x', '0' ),
				$this->prop( 'viewbox_min_y', '0' ),
				$this->prop( 'viewbox_width', '1000' ),
				$this->prop( 'viewbox_height', '1000' )
			);
		} else {
			$viewbox = '0 0 1000 1000';
		}

		// Border layer (Layer 1).
		$layer_1_enabled    = $this->prop( 'layer_1_enable', 'on' ) === 'on';
		$layer_1_background = '';
		$layer_one_gradient = '';
		$gradient_id        = 'gradient-' . $unique_id;
		if ( $layer_1_enabled ) {
			$background_type = $this->prop( 'layer_1_background_type', 'gradient' );
			if ( 'gradient' === $background_type ) {
				$layer_one_gradient = sprintf(
					'<linearGradient id="%3$s" x1="0%%" y1="0%%" x2="100%%" y2="0%%"><stop offset="0%%" style="stop-color: %1$s;stop-opacity: 1" /><stop offset="100%%" style="stop-color: %2$s;stop-opacity: 1" /></linearGradient>',
					esc_attr( $this->prop( 'layer_1_gradient_color_start', '#ff5733' ) ),
					esc_attr( $this->prop( 'layer_1_gradient_color_end', '#33c4ff' ) ),
					esc_attr( $gradient_id )
				);
				$layer_1_background = sprintf(
					'<rect x="0" y="0" width="%s" height="%s" class="st1" fill="url(#%s)"/>',
					esc_attr( $this->prop( 'viewbox_width', '1000' ) ),
					esc_attr( $this->prop( 'viewbox_height', '1000' ) ),
					esc_attr( $gradient_id )
				);
			} else {
				$layer_1_background = sprintf(
					'<rect x="0" y="0" width="%s" height="%s" class="st1" fill="%s"/>',
					esc_attr( $this->prop( 'viewbox_width', '1000' ) ),
					esc_attr( $this->prop( 'viewbox_height', '1000' ) ),
					esc_attr( $this->prop( 'layer_1_background_color', '#ffffff' ) )
				);
			}
		}

		// Decoration layers (Layer 2 and Layer 3).
		$bottom_layers = '';
		$top_layers    = '';
		foreach ( array( 2, 3 ) as $layer ) {
			if ( $this->prop( "layer_{$layer}_enable", 'off' ) === 'on' && $this->prop( "decoration_element_{$layer}", 'none' ) !== 'none' ) {
				$decoration_class = "s0{$layer}";
				$fill_color = self::sanitize_css_background( (string) $this->prop( "layer_{$layer}_background_color", 2 === $layer ? '#ff0000' : '#00ff00' ) );
				if ( '' !== $fill_color ) {
					self::set_style(
						$render_slug,
						array(
							'selector'    => "%%order_class%% .{$decoration_class}",
							'declaration' => sprintf( 'fill: %s;', $fill_color ),
						)
					);
				}
				$horz                 = (float) $this->prop( "layer_{$layer}_horz", 2 === $layer ? '25' : '-15' );
				$vert                 = (float) $this->prop( "layer_{$layer}_vert", 2 === $layer ? '-25' : '30' );
				$scale                = (float) $this->prop( "layer_{$layer}_scale", 2 === $layer ? '1' : '0.8' );
				$rotate               = (float) $this->prop( "layer_{$layer}_rotate", 2 === $layer ? '30' : '45' );
				$decoration_transform = sprintf(
					'translate(%s%%, %s%%) scale(%s) rotate(%sdeg)',
					$horz,
					$vert,
					$scale,
					$rotate
				);
				$decoration_svg       = $this->squad_utils->masking_decorations->get_decoration( $this->prop( "decoration_element_{$layer}", 'none' ), $decoration_class );
				$decoration_group     = sprintf(
					'<g transform="%s">%s</g>',
					esc_attr( $decoration_transform ),
					$decoration_svg
				);
				if ( $this->prop( "layer_{$layer}_above_image", 3 === $layer ? 'on' : 'off' ) === 'on' ) {
					$top_layers .= $decoration_group;
				} else {
					$bottom_layers .= $decoration_group;
				}
			}
		}

		// SVG output.
		return sprintf(
			'<div class="image-elements et_pb_with_background">
				<svg
					width="100%%"
					height="100%%"
					style="overflow:visible"
					xmlns="http://www.w3.org/2000/svg"
					xmlns:xlink="http://www.w3.org/1999/xlink"
					viewBox="%s"
					aria-labelledby="alt-text-%s"
					role="img"
				>
					<title id="alt-text-%s">%s</title>
					<defs>
						%s
						<mask id="%s">
							<g style="transform: %s; transform-origin: center center;">%s</g>
						</mask>
					</defs>
					%s
					%s
					<g style="mask: url(#%s)">
						<image
							href="%s"
							width="%s"
							height="%s"
							transform="%s"
							preserveAspectRatio="none"
							style="overflow:visible"
						/>
					</g>
					%s
				</svg>
			</div>',
			esc_attr( $viewbox ),
			esc_attr( $unique_id ),
			esc_attr( $unique_id ),
			esc_html( $alt_text ),
			$layer_one_gradient,
			esc_attr( $unique_id ),
			esc_attr( $mask_transform ),
			$mask_shape,
			$layer_1_enabled ? $layer_1_background : '',
			$bottom_layers,
			esc_attr( $unique_id ),
			esc_url( $image_src ),
			esc_attr( $this->prop( 'image_width', '100%' ) ),
			esc_attr( $this->prop( 'image_height', '100%' ) ),
			esc_attr( $image_transform ),
			$top_layers
		);
	}

}
