<?php // phpcs:disable WordPress.Files.FileName

/**
 * Abstract Child Module Class
 *
 * Base implementation for child modules in the DiviSquad Builder.
 *
 * @since   3.3.3
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Abstracts\Module;

use DiviSquad\Builder\Version4\Abstracts\Module;

/**
 * Abstract Child Module
 *
 * Base implementation for child modules. Child modules are components that
 * are designed to be used within parent modules, creating nested structures
 * in the Divi Builder interface.
 *
 * @since 3.3.3
 */
abstract class Child_Module extends Module {

	/**
	 * Initialize the child module
	 *
	 * Sets the module type as 'child' and initializes the parent module.
	 * Provides action hooks for before and after initialization.
	 *
	 * @since 3.3.3
	 */
	public function __construct() {
		/**
		 * Action triggered before child module initialization.
		 *
		 * @since 3.3.3
		 *
		 * @param Child_Module $module The child module instance.
		 */
		do_action( 'divi_squad_child_module_before_init', $this );

		// Set module type.
		$this->type = 'child';

		/**
		 * Filter the module type for child modules.
		 *
		 * @since 3.3.3
		 *
		 * @param string       $type   The module type.
		 * @param Child_Module $module The child module instance.
		 */
		$this->type = apply_filters( 'divi_squad_child_module_type', $this->type, $this );

		parent::__construct();

		/**
		 * Action triggered after child module initialization.
		 *
		 * @since 3.3.3
		 *
		 * @param Child_Module $module The child module instance.
		 */
		do_action( 'divi_squad_child_module_after_init', $this );
	}
}
