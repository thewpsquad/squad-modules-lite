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
 * Factory Interface
 *
 * @package DiviSquad
 * @since   3.0.0
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
