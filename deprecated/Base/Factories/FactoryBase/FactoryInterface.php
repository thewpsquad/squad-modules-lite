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
 * Factory Interface
 *
 * @since      3.0.0
 * @deprecated 3.3.3
 * @package    DiviSquad
 */
interface FactoryInterface {

	/**
	 * Add a new item to the list of items.
	 *
	 * @param string $class_name The class name of the item to add.
	 *
	 * @return void
	 */
	public function add( $class_name );
}
