<?php // phpcs:ignore WordPress.Files.FileName
/**
 * Trait for handling deprecated methods and properties.
 *
 *  This trait provides functionality to manage and trigger warnings for deprecated
 *  methods and properties in a flexible and dynamic manner.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   3.1.0
 */

namespace DiviSquad\Base\DiviBuilder\Utils;

use BadMethodCallException;
use InvalidArgumentException;
use WP_Exception;
use function apply_filters;
use function wp_trigger_error;
use function esc_html;
use function do_action;

/**
 * Deprecated Methods And Properties Trait
 *
 * @package DiviSquad
 * @since   3.1.0
 */
trait DeprecationsTrait {
	/**
	 * The default deprecated version.
	 *
	 * @var string
	 */
	private string $deprecated_version = '3.1.0';

	/**
	 * Array of deprecated properties.
	 *
	 * @var array<string, array{version: string, message: string, value: mixed}>
	 */
	private array $deprecated_properties = array(
		'squad_divider_defaults'     => array(
			'version' => '3.1.0',
			'message' => 'Use the property $divider_defaults instead of.',
			'value'   => array(
				'divider_style'    => 'solid',
				'divider_position' => 'bottom',
				'divider_weight'   => '2px',
			),
		),
		'squad_divider_show_options' => array(
			'version' => '3.1.0',
			'message' => 'Use the property $divider_show_options instead of.',
			'value'   => array(
				'off' => 'No',
				'on'  => 'Yes',
			),
		),
	);

	/**
	 * Array of deprecated methods.
	 *
	 * @var array<string, array{version: string, message: string}>
	 */
	private array $deprecated_methods = array(
		'get_hansel_and_gretel'        => array(
			'version' => '3.1.0',
			'message' => 'Use the method $this->squad_utils->breadcrumbs->get_hansel_and_gretel() instead of $this->squad_utils->get_hansel_and_gretel()',
		),
		'get_divider_defaults'         => array(
			'version' => '3.1.0',
			'message' => 'Use the method $this->squad_utils->divider->get_defaults() instead of $this->squad_utils->get_divider_defaults()',
		),
		'get_divider_default'          => array(
			'version' => '3.1.0',
			'message' => 'Use the method $this->squad_utils->divider->get_default() instead of $this->squad_utils->get_divider_default()',
		),
		'get_divider_show_options'     => array(
			'version' => '3.1.0',
			'message' => 'Use the method $this->squad_utils->divider->get_show_options() instead of $this->squad_utils->get_divider_show_options()',
		),
		'initiate_the_divider_element' => array(
			'version' => '3.1.0',
			'message' => 'Use the method $this->squad_utils->divider->initiate_element() instead of $this->squad_utils->initiate_the_divider_element()',
		),
		'get_divider_element_fields'   => array(
			'version' => '3.1.0',
			'message' => 'Use the method $this->squad_utils->divider->get_fields() instead of $this->squad_utils->get_divider_element_fields()',
		),
		'get_divider_field_options'    => array(
			'version' => '3.1.0',
			'message' => 'Use the method $this->squad_utils->divider->get_field_options() instead of $this->squad_utils->get_divider_field_options()',
		),
		'get_mask_shape'               => array(
			'version' => '3.1.0',
			'message' => 'Use the method $this->squad_utils->mask_shape->get_shape() instead of $this->squad_utils->get_mask_shape()',
		),
	);

	/**
	 * Magic method to handle deprecated property access.
	 *
	 * @param string $name The property name.
	 *
	 * @return mixed The value of the deprecated property.
	 * @throws InvalidArgumentException|WP_Exception If the property does not exist.
	 */
	public function __get( string $name ) {
		/**
		 * Filters the list of deprecated properties.
		 *
		 * @since 3.1.0
		 *
		 * @param array $deprecated_properties Array of deprecated property names and their configurations.
		 */
		$deprecated_properties = (array) apply_filters( 'divi_squad_deprecated_properties', $this->deprecated_properties );

		if ( array_key_exists( $name, $deprecated_properties ) ) {
			$deprecated_info    = $deprecated_properties[ $name ];
			$deprecated_version = $deprecated_info['version'] ?? $this->deprecated_version;

			/**
			 * Filters the deprecated version for a specific property.
			 *
			 * @since 3.1.0
			 *
			 * @param string $deprecated_version The deprecated version.
			 * @param string $name              The property name.
			 */
			$deprecated_version = (string) apply_filters( 'divi_squad_deprecated_property_version', $deprecated_version, $name );

			/**
			 * Fires before triggering a deprecated property warning.
			 *
			 * @since 3.1.0
			 *
			 * @param string $name              The property name.
			 * @param string $deprecated_version The deprecated version.
			 * @param array  $deprecated_info    The deprecated property information.
			 */
			do_action( 'divi_squad_before_deprecated_property_warning', $name, $deprecated_version, $deprecated_info );

			$this->trigger_deprecated_warning( $name, $deprecated_version, $deprecated_info['message'], 'property' );

			/**
			 * Filters the value of a deprecated property before returning it.
			 *
			 * @since 3.1.0
			 *
			 * @param mixed  $value             The property value.
			 * @param string $name              The property name.
			 * @param array  $deprecated_info    The deprecated property information.
			 */
			return apply_filters( 'divi_squad_deprecated_property_value', $deprecated_info['value'], $name, $deprecated_info );
		}

		if ( property_exists( $this, $name ) ) {
			return $this->$name;
		}

		return null;
	}

	/**
	 * Magic method to handle deprecated method calls.
	 *
	 * Handles calls to deprecated methods by routing them to their new implementations
	 * or throwing appropriate exceptions if the method doesn't exist.
	 *
	 * @param string                   $name      The method name.
	 * @param array<int|string, mixed> $arguments The method arguments.
	 *
	 * @return mixed The result of the method call.
	 * @throws InvalidArgumentException|WP_Exception If the method does not exist or there's an error processing the call.
	 */
	public function __call( string $name, array $arguments ) {
		/**
		 * Filters the list of deprecated methods.
		 *
		 * @since 3.1.0
		 *
		 * @param array $deprecated_methods Array of deprecated method names and their configurations.
		 */
		$deprecated_methods = (array) apply_filters( 'divi_squad_deprecated_methods', $this->deprecated_methods );

		if ( array_key_exists( $name, $deprecated_methods ) ) {
			$deprecated_info    = $deprecated_methods[ $name ];
			$deprecated_version = $deprecated_info['version'] ?? $this->deprecated_version;

			/**
			 * Filters the deprecated version for a specific method.
			 *
			 * @since 3.1.0
			 *
			 * @param string $deprecated_version The deprecated version.
			 * @param string $name              The method name.
			 */
			$deprecated_version = (string) apply_filters( 'divi_squad_deprecated_method_version', $deprecated_version, $name );

			/**
			 * Fires before triggering a deprecated method warning.
			 *
			 * @since 3.1.0
			 *
			 * @param string $name              The method name.
			 * @param string $deprecated_version The deprecated version.
			 * @param array  $arguments         The method arguments.
			 * @param array  $deprecated_info    The deprecated method information.
			 */
			do_action( 'divi_squad_before_deprecated_method_warning', $name, $deprecated_version, $arguments, $deprecated_info );

			$this->trigger_deprecated_warning( $name, $deprecated_version, $deprecated_info['message'], 'method' );

			/**
			 * Filters whether to handle the deprecated method call.
			 *
			 * @since 3.1.0
			 *
			 * @param bool   $handle_call       Whether to handle the deprecated method call.
			 * @param string $name              The method name.
			 * @param array  $arguments         The method arguments.
			 * @param array  $deprecated_info    The deprecated method information.
			 */
			if ( (bool) apply_filters( 'divi_squad_handle_deprecated_method_call', true, $name, $arguments, $deprecated_info ) ) {
				return $this->handle_deprecated_utility_method( $name, $arguments );
			}
		}

		if ( method_exists( $this, $name ) ) {
			/** @var callable $callback */
			$callback = array( $this, $name );
			return call_user_func_array( $callback, $arguments );
		}

		throw new InvalidArgumentException( sprintf( 'Method %s does not exist.', esc_html( $name ) ) );
	}

	/**
	 * Trigger a deprecated warning.
	 *
	 * Triggers a WordPress deprecation warning with the specified message.
	 *
	 * @param string $name    The name of the deprecated element.
	 * @param string $version The version since deprecation.
	 * @param string $message The deprecation message.
	 * @param string $type    The type of the deprecated element ('property' or 'method').
	 *
	 * @return void
	 * @throws WP_Exception If the error cannot be triggered.
	 */
	private function trigger_deprecated_warning( string $name, string $version, string $message, string $type ): void {
		/**
		 * Filters the deprecation warning message.
		 *
		 * @since 3.1.0
		 *
		 * @param string $message The deprecation warning message.
		 * @param string $name    The name of the deprecated element.
		 * @param string $version The version since deprecation.
		 * @param string $type    The type of the deprecated element.
		 */
		$message = (string) apply_filters( 'divi_squad_deprecated_warning_message', $message, $name, $version, $type );

		$full_message = sprintf( 'The %s $%s is deprecated since version %s. %s', $type, $name, $version, $message );

		/**
		 * Filters whether to trigger the deprecation warning.
		 *
		 * @since 3.1.0
		 *
		 * @param bool   $trigger_warning Whether to trigger the deprecation warning.
		 * @param string $name           The name of the deprecated element.
		 * @param string $version        The version since deprecation.
		 * @param string $type           The type of the deprecated element.
		 */
		if ( (bool) apply_filters( 'divi_squad_trigger_deprecated_warning', true, $name, $version, $type ) ) {
			wp_trigger_error( '', $full_message, E_USER_DEPRECATED );
		}

		/**
		 * Fires after triggering a deprecation warning.
		 *
		 * @since 3.1.0
		 *
		 * @param string $name           The name of the deprecated element.
		 * @param string $version        The version since deprecation.
		 * @param string $type           The type of the deprecated element.
		 * @param string $full_message   The full deprecation warning message.
		 */
		do_action( 'divi_squad_after_deprecated_warning', $name, $version, $type, $full_message );
	}

	/**
	 * Handle calls to deprecated utility methods.
	 *
	 * Routes deprecated method calls to their new implementations in utility classes.
	 *
	 * @param string                   $name      The name of the deprecated method.
	 * @param array<int|string, mixed> $arguments The arguments passed to the method.
	 *
	 * @return mixed The result of the method call.
	 * @throws BadMethodCallException If the deprecated method is not implemented.
	 */
	private function handle_deprecated_utility_method( string $name, array $arguments ) {
		$method_map = array(
			'get_hansel_and_gretel'        => array( 'breadcrumbs', 'get_hansel_and_gretel' ),
			'get_divider_defaults'         => array( 'divider', 'get_defaults' ),
			'get_divider_default'          => array( 'divider', 'get_default' ),
			'get_divider_show_options'     => array( 'divider', 'get_show_options' ),
			'initiate_the_divider_element' => array( 'divider', 'initiate_element' ),
			'get_divider_element_fields'   => array( 'divider', 'get_fields' ),
			'get_divider_field_options'    => array( 'divider', 'get_field_options' ),
			'get_mask_shape'               => array( 'mask_shape', 'get_shape' ),
		);

		/**
		 * Filters the deprecated method map.
		 *
		 * @since 3.1.0
		 *
		 * @param array $method_map Array of deprecated method names and their configurations.
		 */
		$method_map = (array) apply_filters( 'divi_squad_deprecated_method_map', $method_map );

		if ( isset( $method_map[ $name ] ) && $this->element->squad_utils instanceof Base ) {
			[$utility, $method] = $method_map[ $name ];

			/**
			 * Filters whether to handle the deprecated utility method call.
			 *
			 * @since 3.1.0
			 *
			 * @param bool   $handle_call Whether to handle the deprecated utility method call.
			 * @param string $name        The method name.
			 * @param string $utility     The utility class name.
			 * @param string $method      The method name in the utility class.
			 * @param array  $arguments   The method arguments.
			 */
			$should_handle =(bool) apply_filters( 'divi_squad_handle_deprecated_utility_method_call', true, $name, $utility, $method, $arguments );
			if ( $should_handle && isset( $this->element->squad_utils->$utility ) && method_exists( $this->element->squad_utils->$utility, $method ) ) {
				/** @var callable $callback */
				$callback = array( $this->element->squad_utils->$utility, $method );
				return call_user_func_array( $callback, $arguments );
			}
		}

		throw new BadMethodCallException( sprintf( 'Deprecated method %s is not implemented.', esc_html( $name ) ) );
	}

	/**
	 * Set the default deprecated version.
	 *
	 * @param string $version The new deprecated version.
	 *
	 * @return void
	 */
	public function set_deprecated_version( string $version ): void {
		/**
		 * Filters the default deprecated version before setting it.
		 *
		 * @since 3.1.0
		 *
		 * @param string $version The new deprecated version.
		 */
		$this->deprecated_version = (string) apply_filters( 'divi_squad_set_deprecated_version', $version );

		/**
		 * Fires after setting the default deprecated version.
		 *
		 * @since 3.1.0
		 *
		 * @param string $version The new deprecated version.
		 */
		do_action( 'divi_squad_after_set_deprecated_version', $this->deprecated_version );
	}

	/**
	 * Add a new deprecated property.
	 *
	 * @param string $name    The property name.
	 * @param string $version The version since deprecation.
	 * @param string $message The deprecation message.
	 * @param mixed  $value   The default value of the deprecated property.
	 *
	 * @return void
	 */
	public function add_deprecated_property( string $name, string $version, string $message, $value ): void {
		/**
		 * Filters the deprecated property data before adding it.
		 *
		 * @since 3.1.0
		 *
		 * @param array  $property_data The deprecated property data.
		 * @param string $name         The property name.
		 * @param string $version      The version since deprecation.
		 * @param string $message      The deprecation message.
		 * @param mixed  $value        The default value.
		 */
		$property_data = (array) apply_filters(
			'divi_squad_add_deprecated_property_data',
			array(
				'version' => $version,
				'message' => $message,
				'value'   => $value,
			),
			$name,
			$version,
			$message,
			$value
		);

		$this->deprecated_properties[ $name ] = $property_data;

		/**
		 * Fires after adding a new deprecated property.
		 *
		 * @since 3.1.0
		 *
		 * @param string $name           The property name.
		 * @param array  $property_data  The deprecated property data.
		 */
		do_action( 'divi_squad_after_add_deprecated_property', $name, $property_data );
	}

	/**
	 * Add a new deprecated method.
	 *
	 * @param string $name    The method name.
	 * @param string $version The version since deprecation.
	 * @param string $message The deprecation message.
	 *
	 * @return void
	 */
	public function add_deprecated_method( string $name, string $version, string $message ): void {
		/**
		 * Filters the deprecated method data before adding it.
		 *
		 * @since 3.1.0
		 *
		 * @param array  $method_data The deprecated method data.
		 * @param string $name        The method name.
		 * @param string $version     The version since deprecation.
		 * @param string $message     The deprecation message.
		 */
		$method_data = (array) apply_filters(
			'divi_squad_add_deprecated_method_data',
			array(
				'version' => $version,
				'message' => $message,
			),
			$name,
			$version,
			$message
		);

		$this->deprecated_methods[ $name ] = $method_data;

		/**
		 * Fires after adding a new deprecated method.
		 *
		 * @since 3.1.0
		 *
		 * @param string $name        The method name.
		 * @param array  $method_data The deprecated method data.
		 */
		do_action( 'divi_squad_after_add_deprecated_method', $name, $method_data );
	}
}
