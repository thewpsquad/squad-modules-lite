<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Factory Interface
 *
 * @since      3.0.0
 * @deprecated 3.3.3
 * @package    DiviSquad
 * @author     The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Base\Factories\FactoryBase;

/**
 * Interface FactoryInterface
 *
 * @since      3.0.0
 * @deprecated 3.3.3
 * @package    DiviSquad
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
