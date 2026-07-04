<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Builder Utils Fields DefinitionTrait
 *
 * @since   1.5.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Supports\Module_Helpers\Fields;

use function apply_filters;
use function array_merge;
use function esc_html__;
use function et_builder_i18n;
use function wp_parse_args;

/**
 * Fields Definition Trait
 *
 * @since   1.5.0
 * @package DiviSquad
 */
trait Definition_Trait {

	/**
	 * Adds a filter field configuration for Divi modules.
	 *
	 * @since 1.0.0
	 *
	 * @see   Divi/includes/builder/module/woocommerce/CartProducts.php:217
	 * @see   Divi/includes/builder/module/Blurb.php:144
	 *
	 * @see   Divi/includes/builder/class-et-builder-element.php:7607]
	 *
	 * @param array<string, mixed> $options The options for the filter field.
	 *
	 * @return array The filter configuration array.
	 */
	public function add_filters_field( array $options ): array {
		$defaults = array(
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
		);

		/**
		 * Filter the filters field default configuration options.
		 *
		 * @since 3.0.0
		 *
		 * @param array $defaults The default configuration options.
		 * @param array $options  The options passed to the function.
		 */
		$defaults = apply_filters( 'divi_squad_filters_field_defaults', $defaults, $options );

		$options = wp_parse_args( $options, $defaults );

		/**
		 * Filter the filters field configuration after applying the defaults.
		 *
		 * @since 3.0.0
		 *
		 * @param array $options The processed configuration options.
		 */
		return apply_filters( 'divi_squad_filters_field', $options );
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
	public function add_border_field( string $label, array $args = array() ): array {
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

		/**
		 * Filter the border field default configuration options.
		 *
		 * @since 3.0.0
		 *
		 * @param array  $defaults The default configuration options.
		 * @param string $label    The field label.
		 * @param array  $args     Additional arguments.
		 */
		$defaults = apply_filters( 'divi_squad_border_field_defaults', $defaults, $label, $args );

		$args = wp_parse_args( $args, $defaults );

		$field = array(
			'label_prefix' => '' !== $args['label_prefix'] ? $args['label_prefix'] : $label,
			'css'          => $args['css'],
			'defaults'     => $args['defaults'],
			'tab_slug'     => $args['tab_slug'],
			'toggle_slug'  => $args['toggle_slug'],
		);

		/**
		 * Filter the border field configuration after applying the defaults.
		 *
		 * @since 3.0.0
		 *
		 * @param array  $field The processed field configuration.
		 * @param string $label The field label.
		 * @param array  $args  Additional arguments.
		 */
		return apply_filters( 'divi_squad_border_field', $field, $label, $args );
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
	public function add_box_shadow_field( string $label, array $args = array() ): array {
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

		/**
		 * Filter the box shadow field default configuration options.
		 *
		 * @since 3.0.0
		 *
		 * @param array  $defaults The default configuration options.
		 * @param string $label    The field label.
		 * @param array  $args     Additional arguments.
		 */
		$defaults = apply_filters( 'divi_squad_box_shadow_field_defaults', $defaults, $label, $args );

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

		/**
		 * Filter the box shadow field configuration after applying the defaults.
		 *
		 * @since 3.0.0
		 *
		 * @param array  $field The processed field configuration.
		 * @param string $label The field label.
		 * @param array  $args  Additional arguments.
		 */
		return apply_filters( 'divi_squad_box_shadow_field', $field, $label, $args );
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
	public function add_yes_no_field( string $label, array $properties = array() ): array {
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

		/**
		 * Filter the yes/no field default configuration options.
		 *
		 * @since 3.0.0
		 *
		 * @param array  $defaults   The default configuration options.
		 * @param string $label      The field label.
		 * @param array  $properties Additional properties.
		 */
		$defaults = apply_filters( 'divi_squad_yes_no_field_defaults', $defaults, $label, $properties );

		$properties = wp_parse_args( $properties, $defaults );

		/**
		 * Filter the yes/no field configuration after applying the defaults.
		 *
		 * @since 3.0.0
		 *
		 * @param array  $properties The processed configuration options.
		 * @param string $label      The field label.
		 */
		return apply_filters( 'divi_squad_yes_no_field', $properties, $label );
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
	public function add_color_field( string $label, array $properties = array() ): array {
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

		/**
		 * Filter the color field default configuration options.
		 *
		 * @since 3.0.0
		 *
		 * @param array  $defaults   The default configuration options.
		 * @param string $label      The field label.
		 * @param array  $properties Additional properties.
		 */
		$defaults = apply_filters( 'divi_squad_color_field_defaults', $defaults, $label, $properties );

		$properties = wp_parse_args( $properties, $defaults );

		/**
		 * Filter the color field configuration after applying the defaults.
		 *
		 * @since 3.0.0
		 *
		 * @param array  $properties The processed configuration options.
		 * @param string $label      The field label.
		 */
		return apply_filters( 'divi_squad_color_field', $properties, $label );
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
	public function add_select_box_field( string $label, array $properties = array() ): array {
		$defaults = array(
			'label'           => $label,
			'type'            => 'select',
			'option_category' => 'configuration',
			'default'         => '',
		);

		/**
		 * Filter the select box field default configuration options.
		 *
		 * @since 3.0.0
		 *
		 * @param array  $defaults   The default configuration options.
		 * @param string $label      The field label.
		 * @param array  $properties Additional properties.
		 */
		$defaults = apply_filters( 'divi_squad_select_box_field_defaults', $defaults, $label, $properties );

		$properties = wp_parse_args( $properties, $defaults );

		/**
		 * Filter the select box field configuration after applying the defaults.
		 *
		 * @since 3.0.0
		 *
		 * @param array  $properties The processed configuration options.
		 * @param string $label      The field label.
		 */
		return apply_filters( 'divi_squad_select_box_field', $properties, $label );
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
	public function add_placement_field( string $label, array $properties = array() ): array {
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
			'hover'            => 'tabs',
			'sticky'           => true,
		);

		/**
		 * Filter the placement field default configuration options.
		 *
		 * @since 3.0.0
		 *
		 * @param array  $defaults   The default configuration options.
		 * @param string $label      The field label.
		 * @param array  $properties Additional properties.
		 */
		$defaults = apply_filters( 'divi_squad_placement_field_defaults', $defaults, $label, $properties );

		$properties = wp_parse_args( $properties, $defaults );

		/**
		 * Filter the placement field configuration after applying the defaults.
		 *
		 * @since 3.0.0
		 *
		 * @param array  $properties The processed configuration options.
		 * @param string $label      The field label.
		 */
		return apply_filters( 'divi_squad_placement_field', $properties, $label );
	}

	/**
	 * Adds a alignment field configuration for Divi modules.
	 *
	 * @since 1.0.0
	 *
	 * @param string $label      The field label.
	 * @param array  $properties Additional properties for the field.
	 *
	 * @return array The alignment field configuration array.
	 */
	public function add_alignment_field( string $label, array $properties = array() ): array {
		$defaults = array(
			'label'            => $label,
			'description'      => esc_html__( 'Choose the alignment.', 'squad-modules-for-divi' ),
			'type'             => 'align',
			'option_category'  => 'layout',
			'options'          => et_builder_get_text_orientation_options( array( 'justified' ) ),
			'default'          => 'left',
			'default_on_front' => '',
			'mobile_options'   => true,
			'sticky'           => true,
			'hover'            => 'tabs',
		);

		/**
		 * Filter the alignment field default configuration options.
		 *
		 * @since 3.0.0
		 *
		 * @param array  $defaults   The default configuration options.
		 * @param string $label      The field label.
		 * @param array  $properties Additional properties.
		 */
		$defaults = apply_filters( 'divi_squad_alignment_field_defaults', $defaults, $label, $properties );

		$properties = wp_parse_args( $properties, $defaults );

		/**
		 * Filter the alignment field configuration after applying the defaults.
		 *
		 * @since 3.0.0
		 *
		 * @param array  $properties The processed configuration options.
		 * @param string $label      The field label.
		 */
		return apply_filters( 'divi_squad_alignment_field', $properties, $label );
	}

	/**
	 * Adds transition fields for a module.
	 *
	 * @since 1.0.0
	 *
	 * @param array $options The options for transition fields.
	 *
	 * @return array The transition field configuration array.
	 */
	public function add_transition_fields( array $options = array() ): array {
		$defaults = array(
			'title_prefix'   => '',
			'base_attr_name' => 'hover',
			'tab_slug'       => 'custom_css',
			'toggle_slug'    => 'hover_transitions',
			'sub_toggle'     => null,
			'priority'       => 120,
		);

		/**
		 * Filter the transition fields default configuration options.
		 *
		 * @since 3.0.0
		 *
		 * @param array $defaults The default configuration options.
		 * @param array $options  The options passed to the function.
		 */
		$defaults = apply_filters( 'divi_squad_transition_fields_defaults', $defaults, $options );

		$options = wp_parse_args( $options, $defaults );

		$base_attr = $options['base_attr_name'];

		// Transition duration field.
		$duration_fields = array(
			"{$base_attr}_transition_duration" => array(
				'label'            => esc_html__( 'Transition Duration', 'squad-modules-for-divi' ),
				'description'      => esc_html__( 'This setting determines the transition duration.', 'squad-modules-for-divi' ),
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
				'tab_slug'         => $options['tab_slug'],
				'toggle_slug'      => $options['toggle_slug'],
				'responsive'       => true,
			),
		);

		/**
		 * Filter the transition duration fields.
		 *
		 * @since 3.0.0
		 *
		 * @param array  $duration_fields The transition duration fields.
		 * @param string $suffix          The suffix for field names.
		 * @param array  $options         The processed options.
		 */
		$duration_fields = apply_filters( 'divi_squad_transition_duration_fields', $duration_fields, $base_attr, $options );

		// Transition delay field.
		$delay_fields = array(
			"{$base_attr}_transition_delay" => array(
				'label'            => esc_html__( 'Transition Delay', 'squad-modules-for-divi' ),
				'description'      => esc_html__( 'This setting determines the transition delay.', 'squad-modules-for-divi' ),
				'type'             => 'range',
				'option_category'  => 'layout',
				'range_settings'   => array(
					'min'  => 0,
					'max'  => 2000,
					'step' => 50,
				),
				'default'          => '0ms',
				'default_on_front' => '0ms',
				'validate_unit'    => true,
				'fixed_unit'       => 'ms',
				'fixed_range'      => true,
				'tab_slug'         => $options['tab_slug'],
				'toggle_slug'      => $options['toggle_slug'],
				'responsive'       => true,
			),
		);

		/**
		 * Filter the transition delay fields.
		 *
		 * @since 3.0.0
		 *
		 * @param array  $delay_fields The transition delay fields.
		 * @param string $suffix       The suffix for field names.
		 * @param array  $options      The processed options.
		 */
		$delay_fields = apply_filters( 'divi_squad_transition_delay_fields', $delay_fields, $base_attr, $options );

		// Transition timing function field.
		$easing_fields = array(
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
				'tab_slug'         => $options['tab_slug'],
				'toggle_slug'      => $options['toggle_slug'],
				'responsive'       => true,
			),
		);

		/**
		 * Filter the transition easing fields.
		 *
		 * @since 3.0.0
		 *
		 * @param array  $easing_fields The transition easing fields.
		 * @param string $suffix        The suffix for field names.
		 * @param array  $options       The processed options.
		 */
		$easing_fields = apply_filters( 'divi_squad_transition_easing_fields', $easing_fields, $base_attr, $options );

		// All transition fields.
		$transition_fields = array_merge(
			$duration_fields,
			$delay_fields,
			$easing_fields
		);

		/**
		 * Filter all transition fields.
		 *
		 * @since 3.0.0
		 *
		 * @param array $transition_fields The complete transition fields configuration.
		 * @param array $options           The processed options.
		 */
		return apply_filters( 'divi_squad_transition_fields', $transition_fields, $options );
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
	public function add_range_field( string $label, array $properties = array(), array $conditions = array() ): array {
		$defaults = array(
			'label'           => $label,
			'type'            => 'range',
			'option_category' => 'configuration',
			'range_settings'  => array(
				'min_limit' => '0',
				'min'       => '0',
				'max'       => '100',
				'step'      => '1',
			),
			'default'         => '0px',
			'allow_empty'     => true,
			'allowed_units'   => et_builder_get_acceptable_css_string_values(),
			'allowed_values'  => et_builder_get_acceptable_css_string_values(),
			'validate_unit'   => true,
			'default_unit'    => 'px',
			'mobile_options'  => true,
			'sticky'          => true,
			'hover'           => 'tabs',
		);

		/**
		 * Filter the range field default configuration options.
		 *
		 * @since 3.0.0
		 *
		 * @param array  $defaults   The default configuration options.
		 * @param string $label      The field label.
		 * @param array  $properties Additional properties.
		 * @param array  $conditions Additional conditions.
		 */
		$defaults = apply_filters( 'divi_squad_range_field_defaults', $defaults, $label, $properties, $conditions );

		$properties = wp_parse_args( $properties, $defaults );

		// Merge in any additional conditions
		if ( ! empty( $conditions ) ) {
			foreach ( $conditions as $key => $value ) {
				$properties[ $key ] = $value;
			}
		}

		/**
		 * Filter the range field configuration after applying the defaults and conditions.
		 *
		 * @since 3.0.0
		 *
		 * @param array  $properties The processed configuration options.
		 * @param string $label      The field label.
		 * @param array  $conditions Additional conditions.
		 */
		return apply_filters( 'divi_squad_range_field', $properties, $label, $conditions );
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
	public function add_font_field( string $label, array $properties = array() ): array {
		$defaults = array(
			'label'           => $label,
			'css'             => array(),
			'font_size'       => array(),
			'line_height'     => array(),
			'letter_spacing'  => array(),
			'tab_slug'        => 'advanced',
			'toggle_slug'     => 'text',
			'sub_toggle'      => null,
			'hide_text_align' => false,
			'show_if'         => null,
			'show_if_not'     => null,
		);

		/**
		 * Filter the font field default configuration options.
		 *
		 * @since 3.0.0
		 *
		 * @param array  $defaults   The default configuration options.
		 * @param string $label      The field label.
		 * @param array  $properties Additional properties.
		 */
		$defaults = apply_filters( 'divi_squad_font_field_defaults', $defaults, $label, $properties );

		$properties = wp_parse_args( $properties, $defaults );

		/**
		 * Filter the properties after merging with defaults.
		 *
		 * @since 3.0.0
		 *
		 * @param array  $properties The merged properties.
		 * @param string $label      The field label.
		 */
		$properties = (array) apply_filters( 'divi_squad_font_field_properties', $properties, $label );

		// Determine the text align settings
		$text_align_settings = array();
		if ( ! $properties['hide_text_align'] ) {
			$text_align_settings = array(
				'options' => array(
					'left'    => et_builder_i18n( 'Left' ),
					'center'  => et_builder_i18n( 'Center' ),
					'right'   => et_builder_i18n( 'Right' ),
					'justify' => et_builder_i18n( 'Justify' ),
				),
			);

			/**
			 * Filter the text align settings.
			 *
			 * @since 3.0.0
			 *
			 * @param array  $text_align_settings The text align settings.
			 * @param string $label               The field label.
			 * @param array  $properties          The field properties.
			 */
			$text_align_settings = apply_filters(
				'divi_squad_font_field_text_align_settings',
				$text_align_settings,
				$label,
				$properties
			);
		}

		// Build the field configuration
		$field = array(
			'label'              => $properties['label'],
			'tab_slug'           => $properties['tab_slug'],
			'toggle_slug'        => $properties['toggle_slug'],
			'font_size'          => $properties['font_size'],
			'line_height'        => $properties['line_height'],
			'letter_spacing'     => $properties['letter_spacing'],
			'css'                => $properties['css'],
			'use_text_alignment' => ! $properties['hide_text_align'],
			'text_align'         => $text_align_settings,
		);

		// Add conditional sub_toggle if provided
		if ( ! empty( $properties['sub_toggle'] ) ) {
			$field['sub_toggle'] = $properties['sub_toggle'];
		}

		// Add conditional show_if if provided
		if ( ! empty( $properties['show_if'] ) ) {
			$field['show_if'] = $properties['show_if'];
		}

		// Add conditional show_if_not if provided
		if ( ! empty( $properties['show_if_not'] ) ) {
			$field['show_if_not'] = $properties['show_if_not'];
		}

		/**
		 * Filter the final font field configuration.
		 *
		 * @since 3.0.0
		 *
		 * @param array  $field      The final field configuration.
		 * @param string $label      The field label.
		 * @param array  $properties The original properties.
		 */
		return apply_filters( 'divi_squad_font_field', $field, $label, $properties );
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
	public function add_margin_padding_field( string $label, array $properties = array() ): array {
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

		/**
		 * Filter the margin/padding field default configuration options.
		 *
		 * @since 3.0.0
		 *
		 * @param array  $defaults   The default configuration options.
		 * @param string $label      The field label.
		 * @param array  $properties Additional properties.
		 */
		$defaults = apply_filters( 'divi_squad_margin_padding_field_defaults', $defaults, $label, $properties );

		$properties = wp_parse_args( $properties, $defaults );

		/**
		 * Filter the margin/padding field configuration after applying the defaults.
		 *
		 * @since 3.0.0
		 *
		 * @param array  $properties The processed configuration options.
		 * @param string $label      The field label.
		 */
		return apply_filters( 'divi_squad_margin_padding_field', $properties, $label );
	}
}
