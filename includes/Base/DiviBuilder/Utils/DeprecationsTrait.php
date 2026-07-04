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
	 * @var array
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
	 * @var array
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
		if ( array_key_exists( $name, $this->deprecated_properties ) ) {
			$deprecated_info    = $this->deprecated_properties[ $name ];
			$deprecated_version = $deprecated_info['version'] ?? $this->deprecated_version;
			$this->trigger_deprecated_warning( $name, $deprecated_version, $deprecated_info['message'], 'property' );
			return $deprecated_info['value'];
		}

		if ( property_exists( $this, $name ) ) {
			return $this->$name;
		}

		return null;
	}

	/**
	 * Magic method to handle deprecated method calls.
	 *
	 * @param string $name      The method name.
	 * @param array  $arguments The method arguments.
	 *
	 * @return mixed The result of the method call.
	 * @throws InvalidArgumentException|WP_Exception If the method does not exist.
	 */
	public function __call( string $name, array $arguments ) {
		/**
		 * Filters the list of deprecated methods.
		 *
		 * @since 3.1.0
		 *
		 * @param array $deprecated_methods Array of deprecated method names and their configurations.
		 */
		$deprecated_methods = apply_filters( 'divi_squad_deprecated_methods', $this->deprecated_methods );
		if ( array_key_exists( $name, $deprecated_methods ) ) {
			$deprecated_info    = $deprecated_methods[ $name ];
			$deprecated_version = $deprecated_info['version'] ?? $this->deprecated_version;

			/**
			 * Filters the deprecated version for a specific method.
			 *
			 * @since 3.1.0
			 *
			 * @param string $deprecated_version The deprecated version.
			 */
			$deprecated_version = apply_filters( 'divi_squad_deprecated_version', $deprecated_version );
			$this->trigger_deprecated_warning( $name, $deprecated_version, $deprecated_info['message'], 'method' );

			return $this->handle_deprecated_utility_method( $name, $arguments );
		}

		if ( method_exists( $this, $name ) ) {
			return call_user_func_array( array( $this, $name ), $arguments );
		}

		throw new InvalidArgumentException( sprintf( 'Method %s does not exist.', esc_html( $name ) ) );
	}

	/**
	 * Trigger a deprecated warning.
	 *
	 * @param string $name    The name of the deprecated element.
	 * @param string $version The version since deprecation.
	 * @param string $message The deprecation message.
	 * @param string $type    The type of the deprecated element ('property' or 'method').
	 *
	 * @return void
	 * @throws WP_Exception If the error cannot be triggered.
	 */
	private function trigger_deprecated_warning( string $name, string $version, string $message, string $type ) {
		$full_message = sprintf( 'The %s $%s is deprecated since version %s. %s', $type, $name, $version, $message );
		wp_trigger_error( '', $full_message, E_USER_DEPRECATED );
	}

	/**
	 * Handle calls to deprecated utility methods.
	 *
	 * @param string $name      The name of the deprecated method.
	 * @param array  $arguments The arguments passed to the method.
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
		$method_map = apply_filters( 'divi_squad_deprecated_method_map', $method_map );

		if ( isset( $method_map[ $name ] ) && $this->element->squad_utils instanceof Base ) {
			$utility = $method_map[ $name ][0];
			$method  = $method_map[ $name ][1];
			if ( isset( $this->element->squad_utils->$utility ) && method_exists( $this->element->squad_utils->$utility, $method ) ) {
				return call_user_func_array( array( $this->element->squad_utils->$utility, $method ), $arguments );
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
	public function set_deprecated_version( string $version ) {
		$this->deprecated_version = $version;
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
	public function add_deprecated_property( string $name, string $version, string $message, $value ) {
		$this->deprecated_properties[ $name ] = array(
			'version' => $version,
			'message' => $message,
			'value'   => $value,
		);
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
	public function add_deprecated_method( string $name, string $version, string $message ) {
		$this->deprecated_methods[ $name ] = array(
			'version' => $version,
			'message' => $message,
		);
	}
}
