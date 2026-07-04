<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Builder Utils Base Class
 *
 * @since      1.5.0
 * @deprecated 3.3.3
 * @package    DiviSquad
 * @author     The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Base\DiviBuilder\Utils;

use DiviSquad\Base\DiviBuilder\Module;
use DiviSquad\Base\DiviBuilder\Utils\Elements\Breadcrumbs;
use DiviSquad\Base\DiviBuilder\Utils\Elements\Divider;
use DiviSquad\Base\DiviBuilder\Utils\Elements\MaskShape;
use DiviSquad\Base\DiviBuilder\Utils\Fields\CompatibilityTrait;
use DiviSquad\Base\DiviBuilder\Utils\Fields\DefinitionTrait;
use DiviSquad\Base\DiviBuilder\Utils\Fields\ProcessorTrait;
use function esc_html;

/**
 * Utils Base class
 *
 * @since      2.0.0
 * @deprecated 3.3.3
 * @package    DiviSquad
 * @property-read Divider     $divider     Divider Element utility.
 * @property-read Breadcrumbs $breadcrumbs Breadcrumbs Element utility.
 * @property-read MaskShape   $mask_shape  Mask Shape Element utility.
 */
abstract class Base {
	use CommonTrait;
	use FieldsTrait;
	use CompatibilityTrait;
	use DefinitionTrait;
	use ProcessorTrait;
	use DeprecationsTrait;

	/**
	 * The instance of Squad Module.
	 *
	 * @var Module
	 */
	protected Module $element;

	/**
	 * Container for dynamic properties.
	 *
	 * @var array<string, mixed>
	 */
	protected array $container = array();

	/**
	 * Utility class mapping.
	 *
	 * @var array<string, class-string>
	 */
	protected array $utility_class_map = array(
		'divider'     => Elements\Divider::class,
		'breadcrumbs' => \DiviSquad\Builder\Version4\Supports\Module_Utilities\Breadcrumbs::class,
		'mask_shape'  => \DiviSquad\Builder\Version4\Supports\Module_Utilities\Mask_Shape::class,
	);

	/**
	 * Initialize the Utils class.
	 *
	 * @param Module|null $element The module instance.
	 */
	public function __construct( ?Module $element = null ) {
		if ( null === $element ) {
			return;
		}

		$this->element = $element;
	}

	/**
	 * Lazy load a utility.
	 *
	 * @param string $name The utility name.
	 *
	 * @return mixed The utility instance.
	 */
	protected function lazy_load_utility( string $name ) {
		if ( ! isset( $this->container[ $name ] ) && isset( $this->utility_class_map[ $name ] ) ) {
			// Get the utility class.
			$class = $this->utility_class_map[ $name ];

			// Create a new instance of the utility.
			$this->container[ $name ] = new $class( $this->element );
		}

		return $this->container[ $name ] ?? null;
	}

	/**
	 * Magic method to get dynamic property values.
	 *
	 * Retrieves a dynamic property value, either from the utility class map or the container.
	 * This method is called when accessing undefined properties.
	 *
	 * @param string $name The property name.
	 *
	 * @return mixed The property value.
	 */
	public function __get( string $name ) {
		$utility = $this->lazy_load_utility( $name );
		if ( null !== $utility ) {
			return $utility;
		}

		/* @var mixed $name The value of the dynamic property, which can be of any type */
		return property_exists( $this, $name ) ? $this->$name : null; // @phpstan-ignore property.dynamicName
	}

	/**
	 * Set the dynamic property value.
	 *
	 * Sets a dynamic property value in the container, preventing direct modification of utility classes.
	 *
	 * @param string $name  The property name.
	 * @param mixed  $value The property value.
	 *
	 * @throws \InvalidArgumentException If attempting to set a utility class directly.
	 */
	public function __set( string $name, $value ): void {
		if ( isset( $this->utility_class_map[ $name ] ) ) {
			throw new \InvalidArgumentException( sprintf( 'Cannot set utility class %s directly.', esc_html( $name ) ) );
		}
		$this->container[ $name ] = $value;
	}

	/**
	 * Check if a dynamic property exists.
	 *
	 * @param string $name The property name.
	 *
	 * @return bool
	 */
	public function __isset( string $name ): bool {
		return isset( $this->container[ $name ] ) || isset( $this->utility_class_map[ $name ] ) || property_exists( $this, $name );
	}

	/**
	 * Unset a dynamic property.
	 *
	 * @param string $name The property name.
	 */
	public function __unset( string $name ): void {
		unset( $this->container[ $name ] );
	}

	/**
	 * Get the module instance.
	 *
	 * @return Module
	 */
	public function get_element(): Module {
		return $this->element;
	}

	/**
	 * Add a new utility to the class map.
	 *
	 * Adds a new utility class to the class map, ensuring the class exists before adding.
	 *
	 * @param string       $name          The name of the utility.
	 * @param class-string $utility_class The full class name of the utility.
	 *
	 * @throws \InvalidArgumentException If the utility class does not exist.
	 */
	protected function add_utility_to_class_map( string $name, string $utility_class ): void {
		if ( ! class_exists( $utility_class ) ) {
			throw new \InvalidArgumentException( sprintf( 'Utility class %s does not exist.', esc_html( $utility_class ) ) );
		}
		$this->utility_class_map[ $name ] = $utility_class;
	}

	/**
	 * Remove a utility from the class map and container.
	 *
	 * @param string $name The name of the utility.
	 */
	protected function remove_utility( string $name ): void {
		unset( $this->utility_class_map[ $name ], $this->container[ $name ] );
	}

	/**
	 * Check if a utility exists in the class map.
	 *
	 * @param string $name The name of the utility.
	 *
	 * @return bool
	 */
	public function has_utility( string $name ): bool {
		return isset( $this->utility_class_map[ $name ] );
	}

	/**
	 * Get all utility names.
	 *
	 * @return array<int, string>
	 */
	public function get_all_utility_names(): array {
		return array_keys( $this->utility_class_map );
	}
}
