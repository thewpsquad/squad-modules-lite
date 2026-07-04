<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Factory Interface
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   3.0.0
 */

namespace DiviSquad\Base\Factories\FactoryBase;

/**
 * Interface FactoryInterface
 *
 * @package DiviSquad
 * @since   3.0.0
 */
abstract class Factory implements FactoryInterface {

	/**
	 * Init hooks for the factory.
	 *
	 * @return void
	 */
	abstract protected function init_hooks();

	/**
	 * Add a new item to the list of items.
	 *
	 * @param string $class_name The class name of the item to add to the list.
	 *
	 * @return void
	 */
	abstract public function add( $class_name );
}
