<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Image Mask Module Class which extend the Divi Builder Module Class.
 *
 * This class provides mask adding functionalities for image in the visual builder.
 *
 * @since   1.0.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Media;

use DiviSquad\Builder\Version4\Abstracts\Module;
use function apply_filters;
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
					'image'         => esc_html__( 'Image', 'squad-modules-for-divi' ),
					'mask_settings' => esc_html__( 'Mask Options', 'squad-modules-for-divi' ),
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
	 * Declare general fields for the module
	 *
	 * @since 1.0.0
	 * @return array[]
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
					'default'          => 'shapes-01',
					'default_on_front' => 'shapes-01',
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
			$image_associated_fields
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
	public function render( $attrs, $content, $render_slug ): string {
		$image_src = $this->prop( 'image' );
		$alt_text  = $this->_esc_attr( 'alt' );
		$unique_id = self::get_module_order_class( $this->slug );

		$image_shape          = $this->prop( 'mask_shape_image', 'shape-01' );
		$mask_shape_secondary = $this->prop( 'mask_shape_secondary', 'off' );
		$mask_shape           = $this->squad_utils->mask_shape->get_shape( $image_shape, $mask_shape_secondary );

		// Log error if mask shape is empty
		if ( '' === $mask_shape ) {
			return '';
		}

		$mask_option_transform = sprintf(
			'rotate(%1$s) scale(%2$s, %3$s)',
			$this->prop( 'mask_shape_rotate', '0deg' ),
			$this->prop( 'mask_shape_scale_x', '1' ),
			$this->prop( 'mask_shape_scale_y', '1' )
		);

		$mask_shape_flips = explode( '|', $this->prop( 'mask_shape_flip', '' ) );
		if ( in_array( 'horizontal', $mask_shape_flips, true ) ) {
			$mask_option_transform .= ' scale(-1, 1)';
		}
		if ( in_array( 'vertical', $mask_shape_flips, true ) ) { // Fixed logic: Check for 'vertical' instead of !in_array
			$mask_option_transform .= ' scale(1, -1)';
		}

		$image_transform = sprintf(
			'matrix(1 0 0 1 %1$s %2$s)',
			$this->prop( 'image_horizontal_position', '0' ),
			$this->prop( 'image_vertical_position', '0' )
		);

		// Ensure image source is valid
		if ( '' === $image_src ) {
			return '';
		}

		return sprintf(
			'<div class="image-elements et_pb_with_background">
            <svg width="100%" height="100%" style="overflow:visible" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000" aria-labelledby="alt-text-%2$s" role="img">
                <title id="alt-text-%2$s">%1$s</title>
                <defs>
                    <mask id="%2$s" fill="#fff">
                        <g style="transform: %3$s; transform-origin: center center;">%4$s</g>
                    </mask>
                </defs>
                <g style="mask: url(#%2$s)">
                    <image href="%5$s" width="%6$s" height="%7$s" transform="%8$s" preserveAspectRatio="none" style="overflow:visible"/>
                </g>
            </svg>
        </div>',
			$alt_text,
			$unique_id,
			$mask_option_transform,
			$mask_shape,
			$image_src,
			$this->prop( 'image_width', '100%' ),
			$this->prop( 'image_height', '100%' ),
			$image_transform
		);
	}
}
