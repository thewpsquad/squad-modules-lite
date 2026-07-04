<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Class AdminMenu - Factory adapter
 *
 * This class acts as a compatibility layer between the old menu factory system
 * and the new Core\Admin\AdminMenu class.
 *
 * @since      3.3.0
 * @deprecated 3.3.3
 * @package    DiviSquad
 * @author     The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Base\Factories;

use DiviSquad\Base\Factories\FactoryBase\Factory;
use DiviSquad\Core\Traits\Singleton;

/**
 * Class AdminMenu
 *
 * @since      2.0.0
 * @deprecated 3.3.3
 * @package    DiviSquad
 */
final class AdminMenu extends Factory {

	use Singleton;

	/**
	 * Store all registry
	 *
	 * @var \DiviSquad\Base\Factories\AdminMenu\MenuInterface[]
	 */
	private static array $registries = array();

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	protected function init_hooks() {
		// Listen to filters from the new AdminMenu class
		add_filter( 'divi_squad_submenus', array( $this, 'add_legacy_submenus' ) );
		add_filter( 'divi_squad_admin_body_classes', array( $this, 'add_legacy_body_classes' ) );
	}

	/**
	 * Add a new menu to the list of menus.
	 *
	 * @param string $class_name The class name of the menu to add to the list.
	 *
	 * @return void
	 */
	public function add( $class_name ) {
		if ( ! class_exists( $class_name ) ) {
			return;
		}

		$menu = new $class_name();
		if ( ! $menu instanceof AdminMenu\MenuInterface ) {
			return;
		}

		self::$registries[] = $menu;
	}

	/**
	 * Add legacy submenus.
	 *
	 * @param array $existing The submenus from the new AdminMenu class.
	 *
	 * @return array The modified submenus.
	 */
	public function add_legacy_submenus( array $existing ): array {
		foreach ( self::$registries as $menu ) {
			$submenus = $menu->get_sub_menus();

			foreach ( $submenus as $submenu ) {
				if ( is_array( $submenu ) && count( $submenu ) >= 3 ) {
					[ $name, $url, $permission, $page_title ] = array_pad( $submenu, 4, null );

					// Add the submenu to the existing array
					$existing[] = array(
						'name'       => $name,
						'url'        => $url,
						'capability' => $permission,
						'page_title' => $page_title,
					);
				}
			}
		}

		return $existing;
	}

	/**
	 * Add legacy body classes.
	 *
	 * @param string $classes The current body classes.
	 *
	 * @return string The modified body classes.
	 */
	public function add_legacy_body_classes( string $classes ): string {
		foreach ( self::$registries as $menu ) {
			$body_classes = $menu->get_body_classes();
			if ( ! empty( $body_classes ) ) {
				$classes .= ' ' . $body_classes . ' ';
			}
		}

		return $classes;
	}

	/**
	 * Get registered submenus (compatibility method).
	 *
	 * @return array
	 */
	public function get_registered_submenus(): array {
		global $submenu;

		// Set initial value.
		$submenus  = array();
		$menu_slug = divi_squad()->get_admin_menu_slug();

		// Get submenus from the global $submenu array
		if ( isset( $submenu[ $menu_slug ] ) && is_array( $submenu[ $menu_slug ] ) ) {
			foreach ( $submenu[ $menu_slug ] as $current_submenu ) {
				if ( ! in_array( $current_submenu, $submenus, true ) ) {
					$submenus[] = $current_submenu;
				}
			}
		}

		return $submenus;
	}
}
