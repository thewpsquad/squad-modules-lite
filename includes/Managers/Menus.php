<?php // phpcs:ignore WordPress.Files.FileName
/**
 * The Menu manager.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   3.0.0
 */

namespace DiviSquad\Managers;

use DiviSquad\Base\Factories\AdminMenu as AdminMenuFactory;

/**
 * The Menu management class.
 *
 * @package DiviSquad
 * @since   3.0.0
 */
class Menus {

	/**
	 * Load all menus.
	 *
	 * @return void
	 */
	public static function load() {
		$menu = AdminMenuFactory::get_instance();
		if ( $menu instanceof AdminMenuFactory ) {
			$menu->add( Menus\AdminMenu::class );
		}
	}
}
