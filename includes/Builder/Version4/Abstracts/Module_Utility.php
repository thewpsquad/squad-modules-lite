<?php // phpcs:ignore WordPress.Files.FileName
/**
 * Abstract Module Utility Base Class
 *
 * This abstract class provides the foundation for all module utility classes.
 * It standardizes the module utility structure and provides common functionality
 * that is shared across different utility classes.
 *
 * @since   1.5.0
 * @package DiviSquad
 */

namespace DiviSquad\Builder\Version4\Abstracts;

/**
 * Abstract Module Utility Base Class
 *
 * This abstract class provides the foundation for all module utility classes.
 * It standardizes the module utility structure and provides common functionality
 * that is shared across different utility classes.
 *
 * @since   1.5.0
 * @package DiviSquad
 */
abstract class Module_Utility {

	/**
	 * The instance of ET Builder Element.
	 *
	 * @var Module
	 */
	protected Module $module;

	/**
	 * Constructor.
	 *
	 * @param Module $module The instance of ET Builder Element.
	 */
	public function __construct( Module $module ) {
		$this->module = $module;

		// Initialize the utility if needed.
		$this->initialize();

		// If the utility has an initiate_element method, call it.
		$this->initiate_element();
	}

	/**
	 * Initialize the utility.
	 *
	 * This method is called automatically by the constructor if it exists.
	 * Override this method in child classes to set up initial values or configurations.
	 *
	 * @return void
	 */
	protected function initialize(): void {
		// Default implementation does nothing.
	}

	/**
	 * Initiate element-specific settings.
	 *
	 * This method is called by the Module_Utility class when the utility is first accessed.
	 * Override this method in child classes if you need to initialize anything after construction.
	 *
	 * @return void
	 */
	public function initiate_element(): void {
		// Default implementation does nothing.
	}

	/**
	 * Get the module instance.
	 *
	 * @return Module
	 */
	public function get_module(): Module {
		return $this->module;
	}

	/**
	 * Get a property from the module.
	 *
	 * @param string $property The property name.
	 * @param mixed  $default  The default value if property is not found.
	 *
	 * @return mixed
	 */
	protected function get_module_property( string $property, $default = '' ) {
		return property_exists( $this->module, $property ) ? $this->module->$property : $default; // @phpstan-ignore-line
	}

	/**
	 * Get a property from the module props array.
	 *
	 * @param string $property The property name.
	 * @param mixed  $default  The default value if property is not found.
	 *
	 * @return mixed
	 */
	protected function get_prop( string $property, $default = '' ) {
		return $this->module->props[ $property ] ?? $default;
	}
}
