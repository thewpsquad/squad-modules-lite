<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Builder Utils Helper Class which help to the all module class.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   1.0.0
 */

namespace DiviSquad\Base\DiviBuilder\Utils\Fields;

use ET_Builder_Element;
use function esc_html;
use function et_builder_get_element_style_css;
use function et_pb_get_responsive_status;
use function et_pb_hover_options;
use function et_pb_responsive_options;
use function wp_parse_args;

/**
 * Field Processor class.
 *
 * @package DiviSquad
 * @since   1.0.0
 */
trait ProcessorTrait {

	/**
	 * Process styles for width fields in the module.
	 *
	 * @since 1.0.0
	 *
	 * @param array $options Options of current width.
	 *
	 * @return void
	 */
	public function generate_additional_styles( array $options = array() ) {
		// Initiate default values for current options.
		$default = array(
			'field'          => '',
			'selector'       => '',
			'type'           => '',
			'hover_selector' => '',
			'important'      => true,
		);
		$options = wp_parse_args( $options, $default );

		$css_property      = ! empty( $options['css_property'] ) ? $options['css_property'] : $options['type'];
		$additional_css    = isset( $options['important'] ) ? ' !important;' : '';
		$qualified_name    = $options['field'];
		$last_modified_key = sprintf( '%1$s_last_edited', $qualified_name );
		$css_prop          = self::field_to_css_prop( $css_property );

		// Get width value from hover key.
		$hover       = et_pb_hover_options();
		$width_hover = $hover->get_value( $qualified_name, $this->element->props, '' );

		// Get responsive values for the current property.
		$responsive_values = $this->collect_prop_value_responsive( $options, $qualified_name, $last_modified_key );

		// Extract responsive values.
		list( $value_default, $value_last_edited, $value_responsive_values ) = $responsive_values;

		if ( et_pb_get_responsive_status( $value_last_edited ) && '' !== implode( '', $value_responsive_values ) ) {
			$this->process_responsive_styles(
				array(
					'responsive_values' => $value_responsive_values,
					'selector'          => $options['selector'],
					'type'              => $options['type'],
					'css_property'      => $css_property,
					'important'         => $options['important'],
				)
			);
		} else {
			ET_Builder_Element::set_style(
				$this->element->slug,
				array(
					'selector'    => $options['selector'],
					'declaration' => sprintf( '%1$s: %2$s;', $css_prop, esc_html( $value_default ) ),
				)
			);
		}

		if ( isset( $options['hover'] ) && '' !== $width_hover ) {
			$hover_style = array(
				'selector'    => $options['hover_selector'],
				'declaration' => sprintf( '%1$s:%2$s %3$s', $css_prop, $width_hover, $additional_css ),
			);

			ET_Builder_Element::set_style( $this->element->slug, $hover_style );
		}
	}

	/**
	 * Collect any props value from mapping values.
	 *
	 * @param array  $options           The option array data.
	 * @param string $qualified_name    The current field name.
	 * @param string $last_modified_key The last modified key.
	 *
	 * @return array
	 */
	public function collect_prop_value_responsive( array $options, string $qualified_name, string $last_modified_key ): array {
		$props       = $this->element->props;
		$last_edited = ! empty( $props[ $last_modified_key ] ) ? $props[ $last_modified_key ] : '';

		if ( ! empty( $options['mapping_values'] ) ) {
			$responsive_values = et_pb_responsive_options()->get_property_values( $props, $qualified_name );

			if ( is_callable( $options['mapping_values'] ) ) {
				$raw_value     = ! empty( $props[ $qualified_name ] ) ? $props[ $qualified_name ] : '';
				$value_default = $options['mapping_values']( $raw_value );

				foreach ( $responsive_values as $device => $value ) {
					if ( ! empty( $value ) ) {
						$responsive_values[ $device ] = $options['mapping_values']( $value );
					}
				}
			} else {
				$value_default  = '';
				$mapping_values = $options['mapping_values'];
				if ( isset( $props[ $qualified_name ], $mapping_values[ $props[ $qualified_name ] ] ) ) {
					$value_default = $mapping_values[ $props[ $qualified_name ] ];
				}

				foreach ( $responsive_values as $device => $value ) {
					if ( ! empty( $mapping_values[ $value ] ) ) {
						$responsive_values[ $device ] = $mapping_values[ $value ];
					}
				}
			}
		} else {
			$value_default     = ! empty( $props[ $qualified_name ] ) ? $props[ $qualified_name ] : '';
			$responsive_values = et_pb_responsive_options()->get_property_values( $props, $qualified_name );
		}

		return array( $value_default, $last_edited, $responsive_values );
	}

	/**
	 * Process styles for responsive in the module.
	 *
	 * @param array $options The options property for processing styles.
	 *
	 * @return void
	 */
	public function process_responsive_styles( array $options ) {
		$defaults = array(
			'responsive_values' => array(
				'desktop' => '',
				'tablet'  => '',
				'phone'   => '',
			),
			'selector'          => '',
			'type'              => '',
			'css_property'      => '',
			'important'         => true,
		);

		$all_options = wp_parse_args( $options, $defaults );
		$css_prop    = self::field_to_css_prop( $all_options['css_property'] );

		foreach ( $all_options['responsive_values'] as $device => $current_value ) {
			if ( empty( $current_value ) ) {
				continue;
			}

			// Get a valid value. Previously, it only works for range control value and run.
			// et_builder_process_range_value function directly.
			$valid_value = $current_value;
			if ( ( 'margin' === $all_options['type'] ) || ( 'padding' === $all_options['type'] ) ) {
				$declaration = et_builder_get_element_style_css( esc_html( $valid_value ), $css_prop, $options['important'] );
			} else {
				$declaration = sprintf(
					'%1$s:%2$s %3$s',
					$css_prop,
					esc_html( $current_value ),
					$all_options['important'] ? ' !important;' : ';'
				);
			}

			if ( '' === $declaration ) {
				continue;
			}

			$style = array(
				'selector'    => $options['selector'],
				'declaration' => $declaration,
			);

			if ( 'desktop_only' === $device ) {
				$style['media_query'] = ET_Builder_Element::get_media_query( 'min_width_981' );
			} elseif ( 'desktop' !== $device ) {
				$current_media_query  = 'tablet' === $device ? 'max_width_980' : 'max_width_767';
				$style['media_query'] = ET_Builder_Element::get_media_query( $current_media_query );
			}

			ET_Builder_Element::set_style( $this->element->slug, $style );
		}
	}

	/**
	 * Set actual position for icon or image in show on hover effect for the current element with default, responsive and hover.
	 *
	 * @since 1.0.0
	 *
	 * @param array $options Options of current width.
	 *
	 * @return void
	 */
	public function generate_show_icon_on_hover_styles( array $options = array() ) {
		$additional_css = '';
		$default_units  = array( '%', 'em', 'rem', 'px', 'cm', 'mm', 'in', 'pt', 'pc', 'ex', 'vh', 'vw' );

		$default_options = array(
			'props'          => array(),
			'field'          => '',
			'trigger'        => '',
			'selector'       => '',
			'hover'          => '',
			'type'           => '',
			'depends_on'     => array(),
			'defaults'       => array(),
			'mapping_values' => array(),
			'important'      => false,
		);
		$options         = wp_parse_args( $options, $default_options );

		// default Unit for margin replacement.
		$default_unit_value = isset( $options['defaults']['unit_value'] ) ? absint( $options['defaults']['unit_value'] ) : 4;

		$module_props   = ! empty( $options['props'] ) ? $options['props'] : $this->element->props;
		$allowed_units  = ! empty( $options['allowed_units'] ) ? $options['allowed_units'] : $default_units;
		$property_type  = ! empty( $options['css_property'] ) ? $options['css_property'] : $options['type'];
		$hover_selector = ! empty( $options['hover'] ) ? $options['hover'] : "{$options['selector']}:hover";

		$css_prop = self::field_to_css_prop( $property_type );

		// Append !important tag.
		if ( isset( $options['important'] ) && $options['important'] ) {
			$additional_css = ' !important';
		}

		// Collect all values from the current module and parent module, if this is a child module.
		$icon_width_values = self::get_icon_hover_effect_prop_width(
			$module_props,
			array(
				'trigger'    => $options['trigger'],
				'depends_on' => $options['depends_on'],
				'defaults'   => $options['defaults'],
			)
		);

		// set styles in responsive mode.
		foreach ( $icon_width_values as $device => $current_value ) {
			if ( empty( $current_value ) ) {
				continue;
			}

			// field suffix for icon placement.
			$field_suffix = 'desktop' !== $device ? "_$device" : '';

			// generate css value with icon placement and icon width.
			$css_value = self::hover_effect_generate_css(
				$module_props,
				array(
					'qualified_name'     => $options['field'] . $field_suffix,
					'mapping_values'     => $options['mapping_values'],
					'allowed_units'      => $allowed_units,
					'default_width'      => $current_value,
					'default_unit_value' => $default_unit_value,
				)
			);

			$style = array(
				'selector'    => $options['selector'],
				'declaration' => sprintf( '%1$s:%2$s %3$s;', $css_prop, esc_html( $css_value ), $additional_css ),
			);

			if ( 'desktop' !== $device ) {
				$current_media_query  = 'tablet' === $device ? 'max_width_980' : 'max_width_767';
				$style['media_query'] = ET_Builder_Element::get_media_query( $current_media_query );
			}

			ET_Builder_Element::set_style( $this->element->slug, $style );
		}

		ET_Builder_Element::set_style(
			$this->element->slug,
			array(
				'selector'    => $options['selector'],
				'declaration' => 'opacity: 0;',
			)
		);
		ET_Builder_Element::set_style(
			$this->element->slug,
			array(
				'selector'    => $hover_selector,
				'declaration' => 'opacity: 1;margin: 0 0 0 0 !important;',
			)
		);
	}

	/**
	 * Collect icon prop width event if responsive mode.
	 *
	 * @since 1.0.0
	 *
	 * @param array $options Options of current width.
	 *
	 * @param array $props   List of attributes.
	 *
	 * @return array
	 */
	private static function get_icon_hover_effect_prop_width( $props, array $options = array() ): array {
		$defaults      = array(
			'icon'   => '',
			'image'  => '',
			'lottie' => '',
			'text'   => '',
		);
		$results       = array(
			'desktop' => '',
			'tablet'  => '',
			'phone'   => '',
		);
		$devices       = array_keys( $results );
		$allowed_props = array_keys( $defaults );

		// Initiate default values for current options.
		$default_options = array(
			'trigger'    => '',
			'depends_on' => $defaults,
			'defaults'   => $defaults,
		);
		$options         = wp_parse_args( $options, $default_options );

		$icon_depend_prop   = $options['depends_on'];
		$icon_trigger_prop  = $options['trigger'];
		$icon_trigger_value = $props[ $icon_trigger_prop ];

		if ( in_array( $icon_trigger_value, $allowed_props, true ) ) {
			foreach ( $devices as $current_device ) {
				$field_suffix = 'desktop' !== $current_device ? "_$current_device" : '';

				if ( isset( $icon_depend_prop[ $icon_trigger_value ] ) ) {
					$modified_prop = $icon_depend_prop[ $icon_trigger_value ] . $field_suffix;

					if ( isset( $props[ $modified_prop ] ) ) {
						if ( '' !== $props[ $modified_prop ] ) {
							$results[ $current_device ] = $props[ $modified_prop ];
						} elseif ( isset( $options['defaults'][ $icon_trigger_value ] ) ) {
							$results[ $current_device ] = $options['defaults'][ $icon_trigger_value ];
						} else {
							$results[ $current_device ] = '';
						}
					}
				}
			}
		}

		return $results;
	}

	/**
	 * Collect the value of any props for Icon on hover effect.
	 *
	 * @since 1.0.0
	 *
	 * @param array $options Options of current width.
	 *
	 * @param array $props   List of attributes.
	 *
	 * @return string
	 */
	private static function hover_effect_generate_css( array $props, array $options = array() ): string {
		// Initiate default values for current options.
		$default_options = array(
			'qualified_name'     => '',
			'mapping_values'     => array(),
			'allowed_units'      => array(),
			'default_width'      => '',
			'default_unit_value' => '',
			'manual'             => false,
			'manual_value'       => '',
		);
		$options         = wp_parse_args( $options, $default_options );

		// Collect placement value.
		if ( $options['manual'] ) {
			$default_value = $options['manual_value'];
		} else {
			$default_value = $props[ $options['qualified_name'] ];
		}

		// Generate actual value.
		$field_value          = self::collect_prop_mapping_value( $options, $default_value );
		$clean_default_value  = str_replace( $options['allowed_units'], '', $options['default_width'] );
		$increased_value_data = absint( $clean_default_value ) + absint( $options['default_unit_value'] );

		// Return actual value.
		return str_replace( '#', (string) $increased_value_data, $field_value );
	}

	/**
	 * Collect any props value from mapping values.
	 *
	 * @param array  $options       The option array data.
	 * @param string $current_value The current field value.
	 *
	 * @return mixed
	 */
	public static function collect_prop_mapping_value( array $options, string $current_value ) {
		if ( ! empty( $options['mapping_values'] ) ) {
			if ( is_callable( $options['mapping_values'] ) ) {
				return $options['mapping_values']( $current_value );
			}

			return ! empty( $options['mapping_values'][ $current_value ] ) ? $options['mapping_values'][ $current_value ] : '';
		}

		return $current_value;
	}

	/**
	 * Process styles for margin and padding fields in the module.
	 *
	 * @since 1.0.0
	 *
	 * @param array $options Options of current width.
	 *
	 * @return void
	 */
	public function generate_margin_padding_styles( array $options = array() ) {
		// Initiate default values for current options.
		$default = array(
			'field'          => '',
			'selector'       => '',
			'type'           => '',
			'css_property'   => '',
			'hover'          => '',
			'hover_selector' => '',
			'selector_hover' => '',
			'important'      => true,
		);
		$options = wp_parse_args( $options, $default );

		// Generate qualified name.
		$qualified_name    = $options['field'];
		$last_modified_key = sprintf( '%1$s_last_edited', $qualified_name );

		// Collect all values from props.
		$value_default     = isset( $this->element->props[ $qualified_name ] ) ? $this->element->props[ $qualified_name ] : '';
		$value_last_edited = isset( $this->element->props[ $last_modified_key ] ) ? $this->element->props[ $last_modified_key ] : '';

		$value_responsive_values = et_pb_responsive_options()->get_property_values( $this->element->props, $qualified_name );

		// Collect additional values.
		// Get an instance of "ET_Builder_Module_Hover_Options".
		$hover                = et_pb_hover_options();
		$margin_padding_hover = $hover->get_value( $qualified_name, $this->element->props, '' );

		$css_property = ! empty( $options['css_property'] ) ? $options['css_property'] : $options['type'];
		$css_prop     = self::field_to_css_prop( $css_property );

		// Set size for button icon or image with font-size and width style in responsive mode.
		if ( et_pb_get_responsive_status( $value_last_edited ) && '' !== implode( '', $value_responsive_values ) ) {
			$collected_responsive_values = array();
			foreach ( $value_responsive_values as $key => $current_value ) {
				$collected_responsive_values[ $key ] = self::collect_prop_mapping_value( $options, $current_value );
			}

			// set styles in responsive mode.
			$this->process_responsive_styles(
				array(
					'responsive_values' => $collected_responsive_values,
					'selector'          => $options['selector'],
					'type'              => $options['type'],
					'css_property'      => $options['css_property'],
					'important'         => $options['important'],
				)
			);
		} else {
			// Set the default size for button icon or image with font-size and width style.
			ET_Builder_Element::set_style(
				$this->element->slug,
				array(
					'selector'    => $options['selector'],
					'declaration' => et_builder_get_element_style_css(
						esc_html( self::collect_prop_mapping_value( $options, $value_default ) ),
						$css_prop,
						$options['important']
					),
				)
			);
		}

		// Hover style.
		$hover_selector = ! empty( $options['selector_hover'] ) ? $options['selector_hover'] : ( ! empty( $options['hover_selector'] ) ? $options['hover_selector'] : $options['hover'] );
		if ( isset( $hover_selector ) && '' !== $margin_padding_hover ) {
			$hover_style = array(
				'selector'    => $hover_selector,
				'declaration' => et_builder_get_element_style_css(
					esc_html( self::collect_prop_mapping_value( $options, $margin_padding_hover ) ),
					$css_prop,
					$options['important']
				),
			);
			ET_Builder_Element::set_style( $this->element->slug, $hover_style );
		}
	}

	/**
	 * Process Text Clip styles.
	 *
	 * @param array $options The additional options for processing text clip features.
	 *
	 * @return void
	 */
	public function generate_text_clip_styles( array $options = array() ) {
		$default = array(
			'base_attr_name' => '',
			'selector'       => '',
			'hover'          => '',
			'alignment'      => false,
			'important'      => true,
		);
		$options = wp_parse_args( $options, $default );

		if ( 'on' === $this->element->prop( $options['base_attr_name'] . '_clip__enable', 'off' ) ) {
			$this->element->generate_styles(
				array(
					'base_attr_name' => $options['base_attr_name'] . '_fill_color',
					'selector'       => $options['selector'],
					'selector_hover' => $options['hover'],
					'render_slug'    => $this->element->slug,
					'css_property'   => '-webkit-text-fill-color',
					'type'           => 'color',
					'important'      => true,
				)
			);
			$this->element->generate_styles(
				array(
					'base_attr_name' => $options['base_attr_name'] . '_stroke_color',
					'selector'       => $options['selector'],
					'selector_hover' => $options['hover'],
					'render_slug'    => $this->element->slug,
					'css_property'   => '-webkit-text-stroke-color',
					'type'           => 'color',
					'important'      => true,
				)
			);
			$this->element->generate_styles(
				array(
					'base_attr_name' => $options['base_attr_name'] . '_stroke_width',
					'selector'       => $options['selector'],
					'selector_hover' => $options['hover'],
					'render_slug'    => $this->element->slug,
					'css_property'   => '-webkit-text-stroke-width',
					'type'           => 'input',
					'important'      => true,
				)
			);

			if ( 'on' === $this->element->prop( $options['base_attr_name'] . '_bg_clip__enable', 'off' ) ) {
				ET_Builder_Element::set_style(
					$this->element->slug,
					array(
						'selector'    => $options['selector'],
						'declaration' => '-webkit-background-clip: text;',
					)
				);
			}
		}
	}

	/**
	 * Process divider styles.
	 *
	 * @param array $options The additional options for processing divider features.
	 *
	 * @return void
	 */
	public function generate_divider_styles( array $options = array() ) {
		$default = array(
			'selector'  => '',
			'hover'     => '',
			'important' => true,
		);
		$options = wp_parse_args( $options, $default );

		$this->element->generate_styles(
			array(
				'base_attr_name' => 'divider_color',
				'css_property'   => 'border-top-color',
				'type'           => 'color',
				'selector'       => $options['selector'],
				'important'      => $options['important'],
				'render_slug'    => $this->element->slug,
			)
		);
		$this->element->generate_styles(
			array(
				'base_attr_name' => 'divider_style',
				'css_property'   => 'border-top-style',
				'type'           => 'style',
				'selector'       => $options['selector'],
				'important'      => $options['important'],
				'render_slug'    => $this->element->slug,
			)
		);
		$this->element->generate_styles(
			array(
				'base_attr_name' => 'divider_weight',
				'css_property'   => 'border-top-width',
				'type'           => 'input',
				'selector'       => $options['selector'],
				'important'      => $options['important'],
				'render_slug'    => $this->element->slug,
			)
		);
		$this->element->generate_styles(
			array(
				'base_attr_name' => 'divider_max_width',
				'css_property'   => 'max-width',
				'type'           => 'input',
				'selector'       => $options['selector'],
				'important'      => $options['important'],
				'render_slug'    => $this->element->slug,
			)
		);
		$this->element->generate_styles(
			array(
				'base_attr_name' => 'divider_border_radius',
				'css_property'   => 'border-radius',
				'type'           => 'input',
				'selector'       => $options['selector'],
				'important'      => $options['important'],
				'render_slug'    => $this->element->slug,
			)
		);
	}
}
