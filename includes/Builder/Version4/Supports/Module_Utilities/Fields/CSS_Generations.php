<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Builder Utils Fields ProcessorTrait
 *
 * @since   1.5.0
 * @author  The WP Squad <support@squadmodules.com>
 * @package DiviSquad
 */

namespace DiviSquad\Builder\Version4\Supports\Module_Utilities\Fields;

use DiviSquad\Builder\Version4\Abstracts\Module_Utility;
use ET_Builder_Element;
use function apply_filters;
use function do_action;
use function esc_html;
use function et_builder_get_element_style_css;
use function et_pb_get_responsive_status;
use function et_pb_hover_options;
use function et_pb_responsive_options;
use function wp_parse_args;

/**
 * Processor Trait
 *
 * @since   1.5.0
 * @since   3.3.3 Migrate to the new structure.
 * @package DiviSquad
 */
class CSS_Generations extends Module_Utility {

	/**
	 * Process styles for width fields in the module.
	 *
	 * @since 1.0.0
	 *
	 * @param array $options Options of current width.
	 *
	 * @return void
	 */
	public function generate_additional_styles( array $options = array() ): void {
		// Initiate default values for current options.
		$default = array(
			'field'          => '',
			'selector'       => '',
			'type'           => '',
			'hover_selector' => '',
			'important'      => true,
		);

		/**
		 * Filter the default options for generate_additional_styles.
		 *
		 * @since 3.0.0
		 *
		 * @param array $default The default options.
		 * @param array $options The options passed to the function.
		 */
		$default = apply_filters( 'divi_squad_generate_additional_styles_defaults', $default, $options );

		$options = wp_parse_args( $options, $default );

		/**
		 * Filter the options after merging with defaults.
		 *
		 * @since 3.0.0
		 *
		 * @param array $options The merged options.
		 */
		$options = (array) apply_filters( 'divi_squad_generate_additional_styles_options', $options );

		$css_property      = $options['css_property'] ?? $options['type'];
		$additional_css    = isset( $options['important'] ) ? ' !important;' : '';
		$qualified_name    = $options['field'];
		$last_modified_key = sprintf( '%1$s_last_edited', $qualified_name );
		$css_prop          = divi_squad()->d4_module_helper->field_to_css_prop( $css_property );

		// Get width value from hover key.
		$hover       = et_pb_hover_options();
		$width_hover = $hover->get_value( $qualified_name, $this->module->props, '' );

		// Get responsive values for the current property.
		$responsive_values = $this->collect_prop_value_responsive( $options, $qualified_name, $last_modified_key );

		// Extract responsive values.
		[ $value_default, $value_last_edited, $value_responsive_values ] = $responsive_values;

		/**
		 * Filter the responsive values before processing.
		 *
		 * @since 3.0.0
		 *
		 * @param array  $responsive_data The responsive data.
		 * @param string $qualified_name  The field name.
		 * @param array  $options         The options.
		 */
		$responsive_data = apply_filters(
			'divi_squad_generate_additional_styles_responsive_data',
			array(
				'value_default'           => $value_default,
				'value_last_edited'       => $value_last_edited,
				'value_responsive_values' => $value_responsive_values,
			),
			$qualified_name,
			$options
		);

		$value_default           = $responsive_data['value_default'];
		$value_last_edited       = $responsive_data['value_last_edited'];
		$value_responsive_values = $responsive_data['value_responsive_values'];

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
			$style_declaration = sprintf( '%1$s: %2$s;', $css_prop, esc_html( $value_default ) );

			/**
			 * Filter the style declaration for the default (non-responsive) state.
			 *
			 * @since 3.0.0
			 *
			 * @param string $style_declaration The CSS declaration.
			 * @param string $css_prop          The CSS property.
			 * @param string $value_default     The default value.
			 * @param array  $options           The options.
			 */
			$style_declaration = apply_filters(
				'divi_squad_generate_additional_styles_declaration',
				$style_declaration,
				$css_prop,
				$value_default,
				$options
			);

			ET_Builder_Element::set_style(
				$this->module->slug,
				array(
					'selector'    => $options['selector'],
					'declaration' => $style_declaration,
				)
			);
		}

		if ( isset( $options['hover'] ) && '' !== $width_hover ) {
			$hover_declaration = sprintf( '%1$s:%2$s %3$s', $css_prop, $width_hover, $additional_css );

			/**
			 * Filter the hover style declaration.
			 *
			 * @since 3.0.0
			 *
			 * @param string $hover_declaration The hover CSS declaration.
			 * @param string $css_prop          The CSS property.
			 * @param string $width_hover       The hover value.
			 * @param array  $options           The options.
			 */
			$hover_declaration = apply_filters(
				'divi_squad_generate_additional_styles_hover_declaration',
				$hover_declaration,
				$css_prop,
				$width_hover,
				$options
			);

			$hover_style = array(
				'selector'    => $options['hover_selector'],
				'declaration' => $hover_declaration,
			);

			ET_Builder_Element::set_style( $this->module->slug, $hover_style );
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
		$props       = $this->module->props;
		$last_edited = ! empty( $props[ $last_modified_key ] ) ? $props[ $last_modified_key ] : '';

		/**
		 * Filter the props and last_edited value before processing.
		 *
		 * @since 3.0.0
		 *
		 * @param array  $data              The props data.
		 * @param string $qualified_name    The field name.
		 * @param string $last_modified_key The last modified key.
		 * @param array  $options           The options.
		 */
		$props_data = apply_filters(
			'divi_squad_collect_prop_value_responsive_props',
			array(
				'props'       => $props,
				'last_edited' => $last_edited,
			),
			$qualified_name,
			$last_modified_key,
			$options
		);

		$props       = $props_data['props'];
		$last_edited = $props_data['last_edited'];

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

		/**
		 * Filter the responsive values before returning.
		 *
		 * @since 3.0.0
		 *
		 * @param array  $result            The result data.
		 * @param string $qualified_name    The field name.
		 * @param string $last_modified_key The last modified key.
		 * @param array  $options           The options.
		 */
		$result = apply_filters(
			'divi_squad_collect_prop_value_responsive_result',
			array(
				'value_default'     => $value_default,
				'last_edited'       => $last_edited,
				'responsive_values' => $responsive_values,
			),
			$qualified_name,
			$last_modified_key,
			$options
		);

		return array( $result['value_default'], $result['last_edited'], $result['responsive_values'] );
	}

	/**
	 * Process styles for responsive in the module.
	 *
	 * @param array $options The options property for processing styles.
	 *
	 * @return void
	 */
	public function process_responsive_styles( array $options ): void {
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

		/**
		 * Filter the default options for process_responsive_styles.
		 *
		 * @since 3.0.0
		 *
		 * @param array $defaults The default options.
		 * @param array $options  The options passed to the function.
		 */
		$defaults = apply_filters( 'divi_squad_process_responsive_styles_defaults', $defaults, $options );

		$all_options = wp_parse_args( $options, $defaults );

		/**
		 * Filter the options after merging with defaults.
		 *
		 * @since 3.0.0
		 *
		 * @param array $all_options The merged options.
		 */
		$all_options = apply_filters( 'divi_squad_process_responsive_styles_options', $all_options );

		$css_prop = divi_squad()->d4_module_helper->field_to_css_prop( $all_options['css_property'] );

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

			/**
			 * Filter the responsive style declaration for each device.
			 *
			 * @since 3.0.0
			 *
			 * @param string $declaration   The CSS declaration.
			 * @param string $device        The current device (desktop, tablet, phone).
			 * @param string $css_prop      The CSS property.
			 * @param string $current_value The current value.
			 * @param array  $all_options   The options.
			 */
			$declaration = apply_filters(
				'divi_squad_process_responsive_styles_declaration',
				$declaration,
				$device,
				$css_prop,
				$current_value,
				$all_options
			);

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

			/**
			 * Filter the final style array before it's applied.
			 *
			 * @since 3.0.0
			 *
			 * @param array  $style       The style array.
			 * @param string $device      The current device.
			 * @param array  $all_options The options.
			 */
			$style = apply_filters( 'divi_squad_process_responsive_styles_style', $style, $device, $all_options );

			ET_Builder_Element::set_style( $this->module->slug, $style );
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
	public function generate_show_icon_on_hover_styles( array $options = array() ): void {
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

		/**
		 * Filter the default options for generate_show_icon_on_hover_styles.
		 *
		 * @since 3.0.0
		 *
		 * @param array $default_options The default options.
		 * @param array $options         The options passed to the function.
		 */
		$default_options = apply_filters( 'divi_squad_show_icon_hover_defaults', $default_options, $options );

		$options = wp_parse_args( $options, $default_options );

		/**
		 * Filter the options after merging with defaults.
		 *
		 * @since 3.0.0
		 *
		 * @param array $options The merged options.
		 */
		$options = (array) apply_filters( 'divi_squad_show_icon_hover_options', $options );

		// default Unit for margin replacement.
		$default_unit_value = isset( $options['defaults']['unit_value'] ) ? absint( $options['defaults']['unit_value'] ) : 4;

		$module_props   = $options['props'] ?? $this->module->props;
		$allowed_units  = $options['allowed_units'] ?? $default_units;
		$property_type  = $options['css_property'] ?? $options['type'];
		$hover_selector = $options['hover'] ?? "{$options['selector']}:hover";

		/**
		 * Filter the base properties for icon hover styles.
		 *
		 * @since 3.0.0
		 *
		 * @param array $properties The property values.
		 * @param array $options    The options.
		 */
		$properties = apply_filters(
			'divi_squad_show_icon_hover_properties',
			array(
				'module_props'       => $module_props,
				'allowed_units'      => $allowed_units,
				'property_type'      => $property_type,
				'hover_selector'     => $hover_selector,
				'default_unit_value' => $default_unit_value,
			),
			$options
		);

		$module_props       = $properties['module_props'];
		$allowed_units      = $properties['allowed_units'];
		$property_type      = $properties['property_type'];
		$hover_selector     = $properties['hover_selector'];
		$default_unit_value = $properties['default_unit_value'];

		$css_prop = divi_squad()->d4_module_helper->field_to_css_prop( $property_type );

		// Append !important tag.
		if ( isset( $options['important'] ) && $options['important'] ) {
			$additional_css = ' !important';
		}

		// Collect all values from the current module and parent module, if this is a child module.
		$icon_width_values = $this->get_icon_hover_effect_prop_width(
			$module_props,
			array(
				'trigger'    => $options['trigger'],
				'depends_on' => $options['depends_on'],
				'defaults'   => $options['defaults'],
			)
		);

		/**
		 * Filter the icon width values.
		 *
		 * @since 3.0.0
		 *
		 * @param array $icon_width_values The icon width values.
		 * @param array $module_props      The module properties.
		 * @param array $options           The options.
		 */
		$icon_width_values = apply_filters( 'divi_squad_show_icon_hover_width_values', $icon_width_values, $module_props, $options );

		// set styles in responsive mode.
		foreach ( $icon_width_values as $device => $current_value ) {
			if ( empty( $current_value ) ) {
				continue;
			}

			// field suffix for icon placement.
			$field_suffix = 'desktop' !== $device ? "_$device" : '';

			// generate css value with icon placement and icon width.
			$css_value = $this->hover_effect_generate_css(
				$module_props,
				array(
					'qualified_name'     => $options['field'] . $field_suffix,
					'mapping_values'     => $options['mapping_values'],
					'allowed_units'      => $allowed_units,
					'default_width'      => $current_value,
					'default_unit_value' => $default_unit_value,
				)
			);

			/**
			 * Filter the CSS value for each device.
			 *
			 * @since 3.0.0
			 *
			 * @param string $css_value     The CSS value.
			 * @param string $device        The current device.
			 * @param array  $options       The options.
			 * @param string $current_value The current value.
			 */
			$css_value = apply_filters( 'divi_squad_show_icon_hover_css_value', $css_value, $device, $options, $current_value );

			$style = array(
				'selector'    => $options['selector'],
				'declaration' => sprintf( '%1$s:%2$s %3$s;', $css_prop, esc_html( $css_value ), $additional_css ),
			);

			if ( 'desktop' !== $device ) {
				$current_media_query  = 'tablet' === $device ? 'max_width_980' : 'max_width_767';
				$style['media_query'] = ET_Builder_Element::get_media_query( $current_media_query );
			}

			/**
			 * Filter the style for each device.
			 *
			 * @since 3.0.0
			 *
			 * @param array  $style     The style array.
			 * @param string $device    The current device.
			 * @param array  $options   The options.
			 * @param string $css_value The CSS value.
			 */
			$style = apply_filters( 'divi_squad_show_icon_hover_style', $style, $device, $options, $css_value );

			ET_Builder_Element::set_style( $this->module->slug, $style );
		}

		// Set visibility styles
		$visibility_styles = array(
			array(
				'selector'    => $options['selector'],
				'declaration' => 'opacity: 0;',
			),
			array(
				'selector'    => $hover_selector,
				'declaration' => 'opacity: 1;margin: 0 0 0 0 !important;',
			),
		);

		/**
		 * Filter the visibility styles.
		 *
		 * @since 3.0.0
		 *
		 * @param array $visibility_styles The visibility styles.
		 * @param array $options           The options.
		 */
		$visibility_styles = apply_filters( 'divi_squad_show_icon_hover_visibility_styles', $visibility_styles, $options );

		foreach ( $visibility_styles as $style ) {
			ET_Builder_Element::set_style( $this->module->slug, $style );
		}
	}

	/**
	 * Check in current module and retrieve necessary values for hover effects.
	 *
	 * @since 1.0.0
	 *
	 * @param array $props   Current module properties.
	 * @param array $options Option for toggle fields.
	 *
	 * @return array
	 */
	public function get_icon_hover_effect_prop_width( array $props, array $options = array() ): array {
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

		/**
		 * Filter the default values.
		 *
		 * @since 3.0.0
		 *
		 * @param array $defaults The default values.
		 * @param array $props    The module properties.
		 * @param array $options  The original options.
		 */
		$defaults = apply_filters( 'divi_squad_icon_hover_effect_defaults', $defaults, $props, $options );

		/**
		 * Filter the initial results.
		 *
		 * @since 3.0.0
		 *
		 * @param array $results The initial results array.
		 * @param array $props   The module properties.
		 * @param array $options The original options.
		 */
		$results = apply_filters( 'divi_squad_icon_hover_effect_results', $results, $props, $options );

		/**
		 * Filter the devices.
		 *
		 * @since 3.0.0
		 *
		 * @param array $devices The available devices.
		 * @param array $props   The module properties.
		 * @param array $options The original options.
		 */
		$devices = apply_filters( 'divi_squad_icon_hover_effect_devices', $devices, $props, $options );

		/**
		 * Filter the allowed props.
		 *
		 * @since 3.0.0
		 *
		 * @param array $allowed_props The allowed properties.
		 * @param array $props         The module properties.
		 * @param array $options       The original options.
		 */
		$allowed_props = apply_filters( 'divi_squad_icon_hover_effect_allowed_props', $allowed_props, $props, $options );

		// Initiate default values for current options.
		$default_options = array(
			'trigger'    => '',
			'depends_on' => $defaults,
			'defaults'   => $defaults,
		);

		/**
		 * Filter the default options.
		 *
		 * @since 3.0.0
		 *
		 * @param array $default_options The default options.
		 * @param array $props           The module properties.
		 * @param array $options         The original options.
		 */
		$default_options = apply_filters( 'divi_squad_icon_hover_effect_default_options', $default_options, $props, $options );

		$options = wp_parse_args( $options, $default_options );

		/**
		 * Filter the merged options.
		 *
		 * @since 3.0.0
		 *
		 * @param array $options The merged options.
		 * @param array $props   The module properties.
		 */
		$options = (array) apply_filters( 'divi_squad_icon_hover_effect_options', $options, $props );

		$icon_depend_prop   = $options['depends_on'];
		$icon_trigger_prop  = $options['trigger'];
		$icon_trigger_value = $props[ $icon_trigger_prop ];

		/**
		 * Filter the trigger value.
		 *
		 * @since 3.0.0
		 *
		 * @param string $icon_trigger_value The trigger value.
		 * @param string $icon_trigger_prop  The trigger property.
		 * @param array  $props              The module properties.
		 * @param array  $options            The options.
		 */
		$icon_trigger_value = apply_filters( 'divi_squad_icon_hover_effect_trigger_value', $icon_trigger_value, $icon_trigger_prop, $props, $options );

		if ( in_array( $icon_trigger_value, $allowed_props, true ) ) {
			foreach ( $devices as $current_device ) {
				$field_suffix = 'desktop' !== $current_device ? "_$current_device" : '';

				if ( isset( $icon_depend_prop[ $icon_trigger_value ] ) ) {
					$modified_prop = $icon_depend_prop[ $icon_trigger_value ] . $field_suffix;

					/**
					 * Filter the modified property.
					 *
					 * @since 3.0.0
					 *
					 * @param string $modified_prop      The modified property.
					 * @param string $icon_trigger_value The trigger value.
					 * @param string $current_device     The current device.
					 * @param array  $icon_depend_prop   The dependency properties.
					 * @param array  $options            The options.
					 */
					$modified_prop = apply_filters( 'divi_squad_icon_hover_effect_modified_prop', $modified_prop, $icon_trigger_value, $current_device, $icon_depend_prop, $options );

					if ( isset( $props[ $modified_prop ] ) ) {
						if ( '' !== $props[ $modified_prop ] ) {
							$results[ $current_device ] = $props[ $modified_prop ];
						} elseif ( isset( $options['defaults'][ $icon_trigger_value ] ) ) {
							$results[ $current_device ] = $options['defaults'][ $icon_trigger_value ];
						} else {
							$results[ $current_device ] = '';
						}

						/**
						 * Filter the result for a specific device.
						 *
						 * @since 3.0.0
						 *
						 * @param string $result             The result value.
						 * @param string $current_device     The current device.
						 * @param string $modified_prop      The modified property.
						 * @param string $icon_trigger_value The trigger value.
						 * @param array  $props              The module properties.
						 * @param array  $options            The options.
						 */
						$results[ $current_device ] = apply_filters( 'divi_squad_icon_hover_effect_device_result', $results[ $current_device ], $current_device, $modified_prop, $icon_trigger_value, $props, $options );
					}
				}
			}
		}

		/**
		 * Filter the final results.
		 *
		 * @since 3.0.0
		 *
		 * @param array  $results            The results array.
		 * @param string $icon_trigger_value The trigger value.
		 * @param array  $props              The module properties.
		 * @param array  $options            The options.
		 */
		return apply_filters( 'divi_squad_icon_hover_effect_results_final', $results, $icon_trigger_value, $props, $options );
	}

	/**
	 * Generate CSS from hover effects.
	 *
	 * @since 1.0.0
	 *
	 * @param array $props   Map of properties to parse.
	 * @param array $options Field options.
	 *
	 * @return string
	 */
	protected function hover_effect_generate_css( array $props, array $options = array() ): string {
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

		/**
		 * Filter the default options for hover_effect_generate_css.
		 *
		 * @since 3.0.0
		 *
		 * @param array $default_options The default options.
		 * @param array $options         The options passed to the function.
		 * @param array $props           The properties array.
		 */
		$default_options = apply_filters( 'divi_squad_hover_effect_css_defaults', $default_options, $options, $props );

		$options = wp_parse_args( $options, $default_options );

		/**
		 * Filter the options after merging with defaults.
		 *
		 * @since 3.0.0
		 *
		 * @param array $options The merged options.
		 * @param array $props   The properties array.
		 */
		$options = (array) apply_filters( 'divi_squad_hover_effect_css_options', $options, $props );

		// Collect placement value.
		if ( $options['manual'] ) {
			$default_value = $options['manual_value'];
		} else {
			$default_value = $props[ $options['qualified_name'] ];
		}

		/**
		 * Filter the default value.
		 *
		 * @since 3.0.0
		 *
		 * @param string $default_value The default value.
		 * @param array  $options       The options.
		 * @param array  $props         The properties array.
		 */
		$default_value = apply_filters( 'divi_squad_hover_effect_css_default_value', $default_value, $options, $props );

		// Generate actual value.
		$field_value          = $this->collect_prop_mapping_value( $options, $default_value );
		$clean_default_value  = str_replace( $options['allowed_units'], '', $options['default_width'] );
		$increased_value_data = absint( $clean_default_value ) + absint( $options['default_unit_value'] );

		/**
		 * Filter the processed values.
		 *
		 * @since 3.0.0
		 *
		 * @param array $processed_values The processed values.
		 * @param array $options          The options.
		 * @param array $props            The properties array.
		 */
		$processed_values = apply_filters(
			'divi_squad_hover_effect_css_processed_values',
			array(
				'field_value'          => $field_value,
				'clean_default_value'  => $clean_default_value,
				'increased_value_data' => $increased_value_data,
			),
			$options,
			$props
		);

		$field_value          = $processed_values['field_value'];
		$increased_value_data = $processed_values['increased_value_data'];

		// Return actual value.
		$result = str_replace( '#', (string) $increased_value_data, $field_value );

		/**
		 * Filter the final result.
		 *
		 * @since 3.0.0
		 *
		 * @param string $result  The resulting CSS value.
		 * @param array  $options The options.
		 * @param array  $props   The properties array.
		 */
		return apply_filters( 'divi_squad_hover_effect_css_result', $result, $options, $props );
	}

	/**
	 * Collect any props value from mapping values.
	 *
	 * @param array  $options       The option array data.
	 * @param string $current_value The current field value.
	 *
	 * @return mixed
	 */
	public function collect_prop_mapping_value( array $options, string $current_value ) {
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
	public function generate_margin_padding_styles( array $options = array() ): void {
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

		/**
		 * Filter the default options for generate_margin_padding_styles.
		 *
		 * @since 3.0.0
		 *
		 * @param array $default The default options.
		 * @param array $options The options passed to the function.
		 */
		$default = apply_filters( 'divi_squad_generate_margin_padding_styles_defaults', $default, $options );

		$options = wp_parse_args( $options, $default );

		/**
		 * Filter the options after merging with defaults.
		 *
		 * @since 3.0.0
		 *
		 * @param array $options The merged options.
		 */
		$options = (array) apply_filters( 'divi_squad_generate_margin_padding_styles_options', $options );

		// Generate qualified name.
		$qualified_name    = $options['field'];
		$last_modified_key = sprintf( '%1$s_last_edited', $qualified_name );

		// Collect all values from props.
		$value_default     = $this->module->props[ $qualified_name ] ?? '';
		$value_last_edited = $this->module->props[ $last_modified_key ] ?? '';

		$value_responsive_values = et_pb_responsive_options()->get_property_values( $this->module->props, $qualified_name );

		/**
		 * Filter the collected values before processing.
		 *
		 * @since 3.0.0
		 *
		 * @param array  $values         The collected values.
		 * @param string $qualified_name The field name.
		 * @param array  $options        The options.
		 */
		$values = apply_filters(
			'divi_squad_generate_margin_padding_styles_values',
			array(
				'default'           => $value_default,
				'last_edited'       => $value_last_edited,
				'responsive_values' => $value_responsive_values,
			),
			$qualified_name,
			$options
		);

		$value_default           = $values['default'];
		$value_last_edited       = $values['last_edited'];
		$value_responsive_values = $values['responsive_values'];

		// Collect additional values.
		// Get an instance of "ET_Builder_Module_Hover_Options".
		$hover                = et_pb_hover_options();
		$margin_padding_hover = $hover->get_value( $qualified_name, $this->module->props, '' );

		/**
		 * Filter the hover value.
		 *
		 * @since 3.0.0
		 *
		 * @param string $margin_padding_hover The hover value.
		 * @param string $qualified_name       The field name.
		 * @param array  $options              The options.
		 */
		$margin_padding_hover = apply_filters(
			'divi_squad_generate_margin_padding_styles_hover',
			$margin_padding_hover,
			$qualified_name,
			$options
		);

		$css_property = ! empty( $options['css_property'] ) ? $options['css_property'] : $options['type'];
		$css_prop     = divi_squad()->d4_module_helper->field_to_css_prop( $css_property );

		// Set size for button icon or image with font-size and width style in responsive mode.
		if ( et_pb_get_responsive_status( $value_last_edited ) && '' !== \implode( '', $value_responsive_values ) ) {
			$collected_responsive_values = array_map(
				function ( $current_value ) use ( $options ) {
					return $this->collect_prop_mapping_value( $options, $current_value );
				},
				$value_responsive_values
			);

			/**
			 * Filter the collected responsive values.
			 *
			 * @since 3.0.0
			 *
			 * @param array $collected_responsive_values The collected responsive values.
			 * @param array $value_responsive_values     The original responsive values.
			 * @param array $options                     The options.
			 */
			$collected_responsive_values = apply_filters(
				'divi_squad_generate_margin_padding_styles_responsive_values',
				$collected_responsive_values,
				$value_responsive_values,
				$options
			);

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
			$mapped_value = $this->collect_prop_mapping_value( $options, $value_default );

			/**
			 * Filter the mapped default value.
			 *
			 * @since 3.0.0
			 *
			 * @param string $mapped_value  The mapped value.
			 * @param string $value_default The original value.
			 * @param array  $options       The options.
			 */
			$mapped_value = apply_filters(
				'divi_squad_generate_margin_padding_styles_mapped_value',
				$mapped_value,
				$value_default,
				$options
			);

			$declaration = et_builder_get_element_style_css(
				esc_html( $mapped_value ),
				$css_prop,
				$options['important']
			);

			/**
			 * Filter the CSS declaration for the default state.
			 *
			 * @since 3.0.0
			 *
			 * @param string $declaration  The CSS declaration.
			 * @param string $css_prop     The CSS property.
			 * @param string $mapped_value The mapped value.
			 * @param array  $options      The options.
			 */
			$declaration = apply_filters(
				'divi_squad_generate_margin_padding_styles_declaration',
				$declaration,
				$css_prop,
				$mapped_value,
				$options
			);

			ET_Builder_Element::set_style(
				$this->module->slug,
				array(
					'selector'    => $options['selector'],
					'declaration' => $declaration,
				)
			);
		}

		// Hover style.
		$hover_selector = $options['selector_hover'] ?? $options['hover_selector'] ?? $options['hover'];
		if ( isset( $hover_selector ) && '' !== $margin_padding_hover ) {
			$mapped_hover_value = $this->collect_prop_mapping_value( $options, $margin_padding_hover );

			/**
			 * Filter the mapped hover value.
			 *
			 * @since 3.0.0
			 *
			 * @param string $mapped_hover_value   The mapped hover value.
			 * @param string $margin_padding_hover The original hover value.
			 * @param array  $options              The options.
			 */
			$mapped_hover_value = apply_filters(
				'divi_squad_generate_margin_padding_styles_mapped_hover_value',
				$mapped_hover_value,
				$margin_padding_hover,
				$options
			);

			$hover_declaration = et_builder_get_element_style_css(
				esc_html( $mapped_hover_value ),
				$css_prop,
				$options['important']
			);

			/**
			 * Filter the CSS declaration for the hover state.
			 *
			 * @since 3.0.0
			 *
			 * @param string $hover_declaration  The hover CSS declaration.
			 * @param string $css_prop           The CSS property.
			 * @param string $mapped_hover_value The mapped hover value.
			 * @param array  $options            The options.
			 */
			$hover_declaration = apply_filters(
				'divi_squad_generate_margin_padding_styles_hover_declaration',
				$hover_declaration,
				$css_prop,
				$mapped_hover_value,
				$options
			);

			$hover_style = array(
				'selector'    => $hover_selector,
				'declaration' => $hover_declaration,
			);

			/**
			 * Filter the hover style before setting it.
			 *
			 * @since 3.0.0
			 *
			 * @param array  $hover_style    The hover style array.
			 * @param string $hover_selector The hover selector.
			 * @param array  $options        The options.
			 */
			$hover_style = apply_filters(
				'divi_squad_generate_margin_padding_styles_hover_style',
				$hover_style,
				$hover_selector,
				$options
			);

			ET_Builder_Element::set_style( $this->module->slug, $hover_style );
		}
	}

	/**
	 * Process Text Clip styles.
	 *
	 * @param array $options The additional options for processing text clip features.
	 *
	 * @return void
	 */
	public function generate_text_clip_styles( array $options = array() ): void {
		$default = array(
			'base_attr_name' => '',
			'selector'       => '',
			'hover'          => '',
			'alignment'      => false,
			'important'      => true,
		);

		/**
		 * Filter the default options for generate_text_clip_styles.
		 *
		 * @since 3.0.0
		 *
		 * @param array $default The default options.
		 * @param array $options The options passed to the function.
		 */
		$default = apply_filters( 'divi_squad_generate_text_clip_styles_defaults', $default, $options );

		$options = wp_parse_args( $options, $default );

		/**
		 * Filter the options after merging with defaults.
		 *
		 * @since 3.0.0
		 *
		 * @param array $options The merged options.
		 */
		$options = (array) apply_filters( 'divi_squad_generate_text_clip_styles_options', $options );

		$text_clip_enabled = 'on' === $this->module->prop( $options['base_attr_name'] . '_clip__enable', 'off' );

		/**
		 * Filter whether text clip is enabled.
		 *
		 * @since 3.0.0
		 *
		 * @param bool   $text_clip_enabled Whether text clip is enabled.
		 * @param array  $options           The options.
		 * @param object $module            The current module.
		 */
		$text_clip_enabled = apply_filters(
			'divi_squad_text_clip_enabled',
			$text_clip_enabled,
			$options,
			$this->module
		);

		if ( $text_clip_enabled ) {
			// Prepare the text fill color options
			$fill_color_options = array(
				'base_attr_name' => $options['base_attr_name'] . '_fill_color',
				'selector'       => $options['selector'],
				'selector_hover' => $options['hover'],
				'render_slug'    => $this->module->slug,
				'css_property'   => '-webkit-text-fill-color',
				'type'           => 'color',
				'important'      => true,
			);

			/**
			 * Filter the fill color options.
			 *
			 * @since 3.0.0
			 *
			 * @param array $fill_color_options The fill color options.
			 * @param array $options            The original options.
			 */
			$fill_color_options = apply_filters(
				'divi_squad_text_clip_fill_color_options',
				$fill_color_options,
				$options
			);

			$this->module->generate_styles( $fill_color_options );

			// Prepare the stroke color options
			$stroke_color_options = array(
				'base_attr_name' => $options['base_attr_name'] . '_stroke_color',
				'selector'       => $options['selector'],
				'selector_hover' => $options['hover'],
				'render_slug'    => $this->module->slug,
				'css_property'   => '-webkit-text-stroke-color',
				'type'           => 'color',
				'important'      => true,
			);

			/**
			 * Filter the stroke color options.
			 *
			 * @since 3.0.0
			 *
			 * @param array $stroke_color_options The stroke color options.
			 * @param array $options              The original options.
			 */
			$stroke_color_options = apply_filters(
				'divi_squad_text_clip_stroke_color_options',
				$stroke_color_options,
				$options
			);

			$this->module->generate_styles( $stroke_color_options );

			// Prepare the stroke width options
			$stroke_width_options = array(
				'base_attr_name' => $options['base_attr_name'] . '_stroke_width',
				'selector'       => $options['selector'],
				'selector_hover' => $options['hover'],
				'render_slug'    => $this->module->slug,
				'css_property'   => '-webkit-text-stroke-width',
				'type'           => 'input',
				'important'      => true,
			);

			/**
			 * Filter the stroke width options.
			 *
			 * @since 3.0.0
			 *
			 * @param array $stroke_width_options The stroke width options.
			 * @param array $options              The original options.
			 */
			$stroke_width_options = apply_filters(
				'divi_squad_text_clip_stroke_width_options',
				$stroke_width_options,
				$options
			);

			$this->module->generate_styles( $stroke_width_options );

			// Check if background clip is enabled
			$bg_clip_enabled = 'on' === $this->module->prop( $options['base_attr_name'] . '_bg_clip__enable', 'off' );

			/**
			 * Filter whether background clip is enabled.
			 *
			 * @since 3.0.0
			 *
			 * @param bool   $bg_clip_enabled Whether background clip is enabled.
			 * @param array  $options         The options.
			 * @param object $module          The current module.
			 */
			$bg_clip_enabled = apply_filters(
				'divi_squad_bg_clip_enabled',
				$bg_clip_enabled,
				$options,
				$this->module
			);

			if ( $bg_clip_enabled ) {
				$bg_clip_style = array(
					'selector'    => $options['selector'],
					'declaration' => '-webkit-background-clip: text;',
				);

				/**
				 * Filter the background clip style.
				 *
				 * @since 3.0.0
				 *
				 * @param array $bg_clip_style The background clip style.
				 * @param array $options       The options.
				 */
				$bg_clip_style = apply_filters(
					'divi_squad_bg_clip_style',
					$bg_clip_style,
					$options
				);

				ET_Builder_Element::set_style( $this->module->slug, $bg_clip_style );
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
	public function generate_divider_styles( array $options = array() ): void {
		$default = array(
			'selector'  => '',
			'hover'     => '',
			'important' => true,
		);

		/**
		 * Filter the default options for generate_divider_styles.
		 *
		 * @since 3.0.0
		 *
		 * @param array $default The default options.
		 * @param array $options The options passed to the function.
		 */
		$default = apply_filters( 'divi_squad_generate_divider_styles_defaults', $default, $options );

		$options = wp_parse_args( $options, $default );

		/**
		 * Filter the options after merging with defaults.
		 *
		 * @since 3.0.0
		 *
		 * @param array $options The merged options.
		 */
		$options = (array) apply_filters( 'divi_squad_generate_divider_styles_options', $options );

		// Prepare the divider color options
		$color_options = array(
			'base_attr_name' => 'divider_color',
			'css_property'   => 'border-top-color',
			'type'           => 'color',
			'selector'       => $options['selector'],
			'important'      => $options['important'],
			'render_slug'    => $this->module->slug,
		);

		/**
		 * Filter the divider color options.
		 *
		 * @since 3.0.0
		 *
		 * @param array $color_options The color options.
		 * @param array $options       The original options.
		 */
		$color_options = apply_filters( 'divi_squad_divider_color_options', $color_options, $options );

		$this->module->generate_styles( $color_options );

		// Prepare the divider style options
		$style_options = array(
			'base_attr_name' => 'divider_style',
			'css_property'   => 'border-top-style',
			'type'           => 'style',
			'selector'       => $options['selector'],
			'important'      => $options['important'],
			'render_slug'    => $this->module->slug,
		);

		/**
		 * Filter the divider style options.
		 *
		 * @since 3.0.0
		 *
		 * @param array $style_options The style options.
		 * @param array $options       The original options.
		 */
		$style_options = apply_filters( 'divi_squad_divider_style_options', $style_options, $options );

		$this->module->generate_styles( $style_options );

		// Prepare the divider weight options
		$weight_options = array(
			'base_attr_name' => 'divider_weight',
			'css_property'   => 'border-top-width',
			'type'           => 'input',
			'selector'       => $options['selector'],
			'important'      => $options['important'],
			'render_slug'    => $this->module->slug,
		);

		/**
		 * Filter the divider weight options.
		 *
		 * @since 3.0.0
		 *
		 * @param array $weight_options The weight options.
		 * @param array $options        The original options.
		 */
		$weight_options = apply_filters( 'divi_squad_divider_weight_options', $weight_options, $options );

		$this->module->generate_styles( $weight_options );

		// Prepare the divider max width options
		$max_width_options = array(
			'base_attr_name' => 'divider_max_width',
			'css_property'   => 'max-width',
			'type'           => 'input',
			'selector'       => $options['selector'],
			'important'      => $options['important'],
			'render_slug'    => $this->module->slug,
		);

		/**
		 * Filter the divider max width options.
		 *
		 * @since 3.0.0
		 *
		 * @param array $max_width_options The max width options.
		 * @param array $options           The original options.
		 */
		$max_width_options = apply_filters( 'divi_squad_divider_max_width_options', $max_width_options, $options );

		$this->module->generate_styles( $max_width_options );

		// Prepare the divider border radius options
		$border_radius_options = array(
			'base_attr_name' => 'divider_border_radius',
			'css_property'   => 'border-radius',
			'type'           => 'input',
			'selector'       => $options['selector'],
			'important'      => $options['important'],
			'render_slug'    => $this->module->slug,
		);

		/**
		 * Filter the divider border radius options.
		 *
		 * @since 3.0.0
		 *
		 * @param array $border_radius_options The border radius options.
		 * @param array $options               The original options.
		 */
		$border_radius_options = apply_filters( 'divi_squad_divider_border_radius_options', $border_radius_options, $options );

		$this->module->generate_styles( $border_radius_options );

		/**
		 * Action hook that fires after generating all divider styles.
		 *
		 * @since 3.0.0
		 *
		 * @param array  $options The options.
		 * @param object $module  The current module.
		 */
		do_action( 'divi_squad_after_generate_divider_styles', $options, $this->module );
	}
}
