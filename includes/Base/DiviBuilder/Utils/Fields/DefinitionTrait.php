<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Builder Utils Helper Class which helps all module classes.
 *
 * This file contains the DefinitionTrait which provides utility methods for
 * creating various field definitions used in Divi Builder modules.
 *
 * @since   1.0.0
 * @author  The WP Squad <support@squadmodules.com>
 * @package DiviSquad
 */

namespace DiviSquad\Base\DiviBuilder\Utils\Fields;

use ET_Global_Settings;

/**
 * Field Definition Trait.
 *
 * This trait provides utility methods for creating standardized field definitions
 * used across Divi Builder modules.
 *
 * @since   1.0.0
 * @package DiviSquad
 */
trait DefinitionTrait {

	/**
	 * Adds a filter field configuration for Divi modules.
	 *
	 * @see Divi/includes/builder/class-et-builder-element.php:7607]
	 * @see Divi/includes/builder/module/woocommerce/CartProducts.php:217
	 * @see Divi/includes/builder/module/Blurb.php:144
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $options The options for the filter field.
	 *
	 * @return array The filter configuration array.
	 */
	public static function add_filters_field( array $options ): array {
		return wp_parse_args(
			$options,
			array(
				'label'            => et_builder_i18n( 'Image' ),
				'type'             => 'filters',
				'option_category'  => 'layout',
				'tab_slug'         => 'advanced',
				'toggle_slug'      => '',
				'css'              => '',
				'default_on_front' => '',
				'show_if'          => null,
				'show_if_not'      => null,
				'depends_on'       => null,
			)
		);
	}

	/**
	 * Adds a border field configuration for Divi modules.
	 *
	 * @since 3.2.0
	 *
	 * @param string $label The label for the border field.
	 * @param array  $args  Optional. An array of arguments to customize the field.
	 *
	 * @return array Configured border field array for use in Divi modules.
	 */
	public static function add_border_field( string $label, array $args = array() ): array {
		$defaults = array(
			'label_prefix' => '',
			'css'          => array(
				'main' => array(
					'border_radii'  => '%%order_class%%',
					'border_styles' => '%%order_class%%',
				),
			),
			'defaults'     => array(
				'border_radii'  => 'on||||',
				'border_styles' => array(
					'width' => '0px',
					'color' => '#333333',
					'style' => 'solid',
				),
			),
			'tab_slug'     => 'advanced',
			'toggle_slug'  => 'border',
		);

		$args = wp_parse_args( $args, $defaults );

		return array(
			'label_prefix' => '' !== $args['label_prefix'] ? $args['label_prefix'] : $label,
			'css'          => $args['css'],
			'defaults'     => $args['defaults'],
			'tab_slug'     => $args['tab_slug'],
			'toggle_slug'  => $args['toggle_slug'],
		);
	}

	/**
	 * Adds a box shadow field configuration for Divi modules.
	 *
	 * @since 1.0.0
	 * @since 3.2.0 Improved flexibility and alignment with Divi standards.
	 *
	 * @param string $label The label for the box shadow field.
	 * @param array  $args  Optional. An array of arguments to customize the field.
	 *
	 * @return array Configured box shadow field array for use in Divi modules.
	 */
	public static function add_box_shadow_field( string $label, array $args = array() ): array {
		$defaults = array(
			'label'             => $label,
			'option_category'   => 'layout',
			'tab_slug'          => 'advanced',
			'toggle_slug'       => 'box_shadow',
			'css'               => array(
				'main'  => '%%order_class%%',
				'hover' => '%%order_class%%:hover',
			),
			'default_on_fronts' => array(
				'color'    => '',
				'position' => '',
			),
			'show_if'           => true,
			'show_if_not'       => null,
		);

		$args = wp_parse_args( $args, $defaults );

		$field = array(
			'label'            => $args['label'],
			'option_category'  => $args['option_category'],
			'tab_slug'         => $args['tab_slug'],
			'toggle_slug'      => $args['toggle_slug'],
			'type'             => 'box_shadow',
			'css'              => $args['css'],
			'default_on_front' => $args['default_on_fronts'],
		);

		if ( true !== $args['show_if'] ) {
			$field['show_if'] = $args['show_if'];
		}

		if ( null !== $args['show_if_not'] ) {
			$field['show_if_not'] = $args['show_if_not'];
		}

		return $field;
	}

	/**
	 * Adds a yes/no field configuration for Divi modules.
	 *
	 * @since 1.0.0
	 *
	 * @param string $label      The field label.
	 * @param array  $properties Additional properties for the field.
	 *
	 * @return array The yes/no field configuration array.
	 */
	public static function add_yes_no_field( string $label, array $properties = array() ): array {
		$defaults = array(
			'label'            => $label,
			'type'             => 'yes_no_button',
			'option_category'  => 'configuration',
			'options'          => array(
				'off' => esc_html__( 'No', 'squad-modules-for-divi' ),
				'on'  => esc_html__( 'Yes', 'squad-modules-for-divi' ),
			),
			'default_on_front' => 'off',
		);

		return wp_parse_args( $properties, $defaults );
	}

	/**
	 * Adds a color field configuration for Divi modules.
	 *
	 * @since 1.0.0
	 *
	 * @param string $label      The field label.
	 * @param array  $properties Additional properties for the field.
	 *
	 * @return array The color field configuration array.
	 */
	public static function add_color_field( string $label, array $properties = array() ): array {
		$defaults = array(
			'label'           => $label,
			'type'            => 'color-alpha',
			'option_category' => 'configuration',
			'custom_color'    => true,
			'field_template'  => 'color',
			'mobile_options'  => true,
			'sticky'          => true,
			'hover'           => 'tabs',
		);

		return wp_parse_args( $properties, $defaults );
	}

	/**
	 * Adds a select box field configuration for Divi modules.
	 *
	 * @since 1.0.0
	 *
	 * @param string $label      The field label.
	 * @param array  $properties Additional properties for the field.
	 *
	 * @return array The select box field configuration array.
	 */
	public static function add_select_box_field( string $label, array $properties = array() ): array {
		$defaults = array(
			'label'            => $label,
			'description'      => esc_html__( 'Select an option.', 'squad-modules-for-divi' ),
			'type'             => 'select',
			'option_category'  => 'layout',
			'options'          => array(
				'none' => esc_html__( 'Select one', 'squad-modules-for-divi' ),
			),
			'default_on_front' => '',
		);

		return wp_parse_args( $properties, $defaults );
	}

	/**
	 * Adds a placement field configuration for Divi modules.
	 *
	 * @since 1.0.0
	 *
	 * @param string $label      The field label.
	 * @param array  $properties Additional properties for the field.
	 *
	 * @return array The placement field configuration array.
	 */
	public static function add_placement_field( string $label, array $properties = array() ): array {
		$child_image_icon_placement = array(
			'column'      => et_builder_i18n( 'Top' ),
			'row'         => et_builder_i18n( 'Left' ),
			'row-reverse' => et_builder_i18n( 'Right' ),
		);

		$child_default_placement = is_rtl() ? 'row-reverse' : 'row';

		$defaults = array(
			'label'            => $label,
			'description'      => esc_html__( 'Choose the placement.', 'squad-modules-for-divi' ),
			'type'             => 'select',
			'option_category'  => 'layout',
			'options'          => $child_image_icon_placement,
			'default_on_front' => $child_default_placement,
			'mobile_options'   => true,
			'sticky'           => true,
		);

		return wp_parse_args( $properties, $defaults );
	}

	/**
	 * Adds an alignment field configuration for Divi modules.
	 *
	 * @since 1.0.0
	 *
	 * @param string $label      The field label.
	 * @param array  $properties Additional properties for the field.
	 *
	 * @return array The alignment field configuration array.
	 */
	public static function add_alignment_field( string $label, array $properties = array() ): array {
		$defaults = array(
			'label'           => $label,
			'description'     => esc_html__( 'Choose the alignment.', 'squad-modules-for-divi' ),
			'type'            => 'align',
			'option_category' => 'layout',
			'options'         => et_builder_get_text_orientation_options( array( 'justified' ) ),
			'default'         => 'left',
			'mobile_options'  => true,
			'sticky'          => true,
		);

		return wp_parse_args( $properties, $defaults );
	}

	/**
	 * Adds transition fields configuration for Divi modules.
	 *
	 * @since 1.0.0
	 *
	 * @param array $options Additional options for the transition fields.
	 *
	 * @return array The transition fields configuration array.
	 */
	public static function add_transition_fields( array $options = array() ): array {
		$defaults = array(
			'title_prefix'   => '',
			'base_attr_name' => 'hover',
			'tab_slug'       => 'custom_css',
			'toggle_slug'    => 'hover_transitions',
			'sub_toggle'     => null,
			'priority'       => 120,
		);
		$config   = wp_parse_args( $options, $defaults );

		$base_attr  = $config['base_attr_name'];
		$tab        = $config['tab_slug'];
		$toggle     = $config['toggle_slug'];
		$sub_toggle = $config['sub_toggle'];

		return array(
			"{$base_attr}_transition_duration"    => array(
				'label'            => esc_html__( 'Transition Duration', 'squad-modules-for-divi' ),
				'description'      => esc_html__( 'This controls the transition duration of the animation.', 'squad-modules-for-divi' ),
				'type'             => 'range',
				'option_category'  => 'layout',
				'range_settings'   => array(
					'min'  => 0,
					'max'  => 2000,
					'step' => 50,
				),
				'default'          => '1000ms',
				'default_on_front' => '1000ms',
				'validate_unit'    => true,
				'fixed_unit'       => 'ms',
				'fixed_range'      => true,
				'tab_slug'         => $tab,
				'toggle_slug'      => $toggle,
				'sub_toggle'       => $sub_toggle,
				'mobile_options'   => true,
			),
			"{$base_attr}_transition_delay"       => array(
				'label'            => esc_html__( 'Transition Delay', 'squad-modules-for-divi' ),
				'description'      => esc_html__( 'This controls the transition delay of the animation.', 'squad-modules-for-divi' ),
				'type'             => 'range',
				'option_category'  => 'layout',
				'range_settings'   => array(
					'min'  => 0,
					'max'  => 300,
					'step' => 50,
				),
				'default'          => '0ms',
				'default_on_front' => '0ms',
				'validate_unit'    => true,
				'fixed_unit'       => 'ms',
				'fixed_range'      => true,
				'tab_slug'         => $tab,
				'toggle_slug'      => $toggle,
				'sub_toggle'       => $sub_toggle,
				'mobile_options'   => true,
			),
			"{$base_attr}_transition_speed_curve" => array(
				'label'            => esc_html__( 'Transition Speed Curve', 'squad-modules-for-divi' ),
				'description'      => esc_html__( 'This controls the transition speed curve of the animation.', 'squad-modules-for-divi' ),
				'type'             => 'select',
				'option_category'  => 'layout',
				'options'          => array(
					'ease-in-out' => et_builder_i18n( 'Ease-In-Out' ),
					'ease'        => et_builder_i18n( 'Ease' ),
					'ease-in'     => et_builder_i18n( 'Ease-In' ),
					'ease-out'    => et_builder_i18n( 'Ease-Out' ),
					'linear'      => et_builder_i18n( 'Linear' ),
				),
				'default'          => 'ease',
				'default_on_front' => 'ease',
				'tab_slug'         => $tab,
				'toggle_slug'      => $toggle,
				'sub_toggle'       => $sub_toggle,
				'mobile_options'   => true,
			),
		);
	}

	/**
	 * Adds a range field configuration for Divi modules.
	 *
	 * @since 1.0.0
	 *
	 * @param string $label      The field label.
	 * @param array  $properties Additional properties for the field.
	 * @param array  $conditions Additional conditions for the field.
	 *
	 * @return array The range field configuration array.
	 */
	public static function add_range_field( string $label, array $properties = array(), array $conditions = array() ): array {
		$field_options = array(
			'label'           => $label,
			'type'            => 'range',
			'range_settings'  => array(
				'min'       => '0',
				'min_limit' => '0',
				'max'       => '100',
				'step'      => '1',
			),
			'option_category' => 'layout',
			'allow_empty'     => true,
			'allowed_units'   => et_builder_get_acceptable_css_string_values(),
			'allowed_values'  => et_builder_get_acceptable_css_string_values(),
			'validate_unit'   => true,
			'hover'           => 'tabs',
			'mobile_options'  => true,
			'responsive'      => true,
			'sticky'          => true,
		);

		$field_options = wp_parse_args( $properties, $field_options );

		if ( isset( $conditions['use_hover'] ) && false === $conditions['use_hover'] ) {
			unset( $field_options['hover'] );
		}

		if ( isset( $conditions['mobile_options'] ) && false === $conditions['mobile_options'] ) {
			unset( $field_options['mobile_options'] );
		}

		return $field_options;
	}

	/**
	 * Adds a font field configuration for Divi modules.
	 *
	 * @since 1.0.0
	 *
	 * @param string $label      The field label.
	 * @param array  $properties Additional properties for the field.
	 *
	 * @return array The font field configuration array.
	 */
	public static function add_font_field( string $label, array $properties = array() ): array {
		$defaults = array(
			'label'          => $label,
			'font_size'      => array( 'default' => '16px' ),
			'line_height'    => array( 'default' => '1.7em' ),
			'letter_spacing' => array( 'default' => '0px' ),
			'font_weight'    => array( 'default' => '400' ),
			'tab_slug'       => 'advanced',
			'toggle_slug'    => 'text',
			'sub_toggle'     => 'font',
			'mobile_options' => true,
			'responsive'     => true,
			'sticky'         => true,
		);

		return wp_parse_args( $properties, $defaults );
	}

	/**
	 * Adds a margin and padding field configuration for Divi modules.
	 *
	 * @since 1.0.0
	 *
	 * @param string $label      The field label.
	 * @param array  $properties Additional properties for the field.
	 *
	 * @return array The margin and padding field configuration array.
	 */
	public static function add_margin_padding_field( string $label, array $properties = array() ): array {
		$defaults = array(
			'label'           => $label,
			'description'     => esc_html__( 'Here you can define custom margin and padding sizes.', 'squad-modules-for-divi' ),
			'type'            => 'custom_margin',
			'option_category' => 'layout',
			'tab_slug'        => 'advanced',
			'toggle_slug'     => 'margin_padding',
			'default_unit'    => 'px',
			'allowed_units'   => array( '%', 'em', 'rem', 'px', 'cm', 'mm', 'in', 'pt', 'pc', 'ex', 'vh', 'vw' ),
			'range_settings'  => array(
				'min'  => '0',
				'max'  => '100',
				'step' => '1',
			),
			'hover'           => 'tabs',
			'mobile_options'  => true,
			'responsive'      => true,
			'sticky'          => true,
		);

		return wp_parse_args( $properties, $defaults );
	}

	/**
	 * Adds a background field configuration for Divi modules.
	 *
	 * @since 1.0.0
	 *
	 * @param array $properties Additional properties for the field.
	 *
	 * @return array The background field configuration array.
	 */
	public function add_background_field( array $properties = array() ): array {
		list( , $base_name, $context, $tab_slug, $toggle_slug ) = self::get_background_field_options( $properties );

		$background_fields = array_merge_recursive(
			$this->element->generate_background_options( $base_name, 'color', $tab_slug, $toggle_slug, $context ),
			$this->element->generate_background_options( $base_name, 'gradient', $tab_slug, $toggle_slug, $context ),
			$this->element->generate_background_options( $base_name, 'image', $tab_slug, $toggle_slug, $context )
		);

		return $this->add_background_fields( $properties, $background_fields );
	}

	/**
	 * Gets background field options for Divi modules.
	 *
	 * @since 1.0.0
	 *
	 * @param array $properties Additional properties for the field.
	 *
	 * @return array Background field options.
	 */
	public static function get_background_field_options( array $properties = array() ): array {
		$label       = isset( $properties['label'] ) ? $properties['label'] : '';
		$base_name   = isset( $properties['base_name'] ) ? $properties['base_name'] : '_background';
		$context     = isset( $properties['context'] ) ? $properties['context'] : '_background_color';
		$tab_slug    = isset( $properties['tab_slug'] ) ? $properties['tab_slug'] : 'advanced';
		$toggle_slug = isset( $properties['toggle_slug'] ) ? $properties['toggle_slug'] : 'background';

		return array( $label, $base_name, $context, $tab_slug, $toggle_slug );
	}

	/**
	 * Adds all background fields for Divi modules.
	 *
	 * @since 1.0.0
	 *
	 * @param array $properties        Additional properties for the field.
	 * @param array $background_fields The additional background fields for the current field.
	 *
	 * @return array Complete background field configuration.
	 */
	protected function add_background_fields( array $properties = array(), array $background_fields = array() ): array {
		list( $label, $base_name, $context, $tab_slug, $toggle_slug ) = self::get_background_field_options( $properties );

		$default_bg_color = ET_Global_Settings::get_value( 'all_buttons_bg_color' );
		$defaults         = array(
			'label'             => $label,
			'description'       => esc_html__( 'Customize the background of this element by adjusting the color, gradient, and image settings.', 'squad-modules-for-divi' ),
			'type'              => 'background-field',
			'base_name'         => $base_name,
			'context'           => $context,
			'option_category'   => 'layout',
			'custom_color'      => true,
			'default'           => $default_bg_color,
			'default_on_front'  => '',
			'tab_slug'          => $tab_slug,
			'toggle_slug'       => $toggle_slug,
			'background_fields' => $background_fields,
			'hover'             => 'tabs',
			'mobile_options'    => true,
			'sticky'            => true,
		);

		$conditions = wp_array_slice_assoc(
			$properties,
			array( 'depends_show_if', 'depends_show_if_not', 'show_if', 'show_if_not' )
		);

		$background_options = array(
			$context => array_merge_recursive( $conditions, $defaults ),
		);

		$background_options[ $context ]['background_fields'][ $context ]['default'] = $default_bg_color;

		return array_merge(
			$background_options,
			$this->element->generate_background_options( $base_name, 'skip', $tab_slug, $toggle_slug, $context )
		);
	}

	/**
	 * Adds a background gradient field configuration for Divi modules.
	 *
	 * @since 1.0.0
	 *
	 * @param array $properties Additional properties for the field.
	 *
	 * @return array The background gradient field configuration array.
	 */
	public function add_background_gradient_field( array $properties = array() ): array {
		list( , $base_name, $context, $tab_slug, $toggle_slug ) = self::get_background_field_options( $properties );

		$background_fields = $this->element->generate_background_options( $base_name, 'gradient', $tab_slug, $toggle_slug, $context );

		return $this->add_background_fields( $properties, $background_fields );
	}
}
