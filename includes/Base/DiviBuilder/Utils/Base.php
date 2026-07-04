<?php // phpcs:ignore WordPress.Files.FileName
/**
 * Builder Utils Base Class
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   1.5.0
 */

namespace DiviSquad\Base\DiviBuilder\Utils;

use DiviSquad\Base\DiviBuilder\Module;
use DiviSquad\Base\DiviBuilder\Utils;

/**
 * Utils Base class
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   2.0.0
 *
 * @property-read Utils\Elements\Divider     $divider     Divider Element utility.
 * @property-read Utils\Elements\Breadcrumbs $breadcrumbs Breadcrumbs Element utility.
 * @property-read Utils\Elements\MaskShape   $mask_shape  Mask Shape Element utility.
 */
abstract class Base {
	use Utils\CommonTrait;
	use Utils\FieldsTrait;
	use Utils\Fields\CompatibilityTrait;
	use Utils\Fields\DefinitionTrait;
	use Utils\Fields\ProcessorTrait;
	use Utils\DeprecationsTrait;

	/**
	 * The instance of Squad Module.
	 *
	 * @var Module
	 */
	protected Module $element;

	/**
	 * Container for dynamic properties.
	 *
	 * @var array
	 */
	protected array $container = array();

	/**
	 * Utility class mapping.
	 *
	 * @var array
	 */
	protected array $utility_class_map = array(
		'divider'     => Utils\Elements\Divider::class,
		'breadcrumbs' => Utils\Elements\Breadcrumbs::class,
		'mask_shape'  => Utils\Elements\MaskShape::class,
	);

	/**
	 * Initialize the Utils class.
	 *
	 * @param Module $element The module instance.
	 */
	public function __construct( $element = null ) {
		if ( null === $element ) {
			return;
		}

		$this->element = $element;
	}

	/**
	 * Lazy load a utility.
	 *
	 * @param string $name The utility name.
	 * @return mixed The utility instance.
	 */
	protected function lazy_load_utility( $name ) {
		if ( ! isset( $this->container[ $name ] ) && isset( $this->utility_class_map[ $name ] ) ) {
			$class                    = $this->utility_class_map[ $name ];
			$this->container[ $name ] = new $class( $this->element );
		}
		return isset( $this->container[ $name ] ) ? $this->container[ $name ] : null;
	}

	/**
	 * Get the dynamic property value.
	 *
	 * @param string $name The property name.
	 *
	 * @return mixed
	 */
	public function __get( string $name ) {
		$utility = $this->lazy_load_utility( $name );
		if ( null !== $utility ) {
			return $utility;
		}
		return property_exists( $this, $name ) ? $this->$name : null;
	}

	/**
	 * Set the dynamic property value.
	 *
	 * @param string $name The property name.
	 * @param mixed  $value The property value.
	 */
	public function __set( $name, $value ) {
		$this->container[ $name ] = $value;
	}

	/**
	 * Check if a dynamic property exists.
	 *
	 * @param string $name The property name.
	 * @return bool
	 */
	public function __isset( $name ) {
		return isset( $this->container[ $name ] ) || isset( $this->utility_class_map[ $name ] ) || property_exists( $this, $name );
	}

	/**
	 * Unset a dynamic property.
	 *
	 * @param string $name The property name.
	 */
	public function __unset( $name ) {
		unset( $this->container[ $name ] );
	}

	/**
	 * Get the module instance.
	 *
	 * @return Module
	 */
	public function get_element() {
		return $this->element;
	}

	/**
	 * Add a new utility to the class map.
	 *
	 * @param string $name The name of the utility.
	 * @param string $utility_class The full class name of the utility.
	 */
	protected function add_utility_to_class_map( $name, $utility_class ) {
		$this->utility_class_map[ $name ] = $utility_class;
	}

	/**
	 * Remove a utility from the class map and container.
	 *
	 * @param string $name The name of the utility.
	 */
	protected function remove_utility( $name ) {
		unset( $this->utility_class_map[ $name ], $this->container[ $name ] );
	}

	/**
	 * Check if a utility exists in the class map.
	 *
	 * @param string $name The name of the utility.
	 * @return bool
	 */
	public function has_utility( $name ) {
		return isset( $this->utility_class_map[ $name ] );
	}

	/**
	 * Get all utility names.
	 *
	 * @return array
	 */
	public function get_all_utility_names() {
		return array_keys( $this->utility_class_map );
	}
}
