<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Class AdminMenu
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   2.0.0
 */

namespace DiviSquad\Base\Factories;

use DiviSquad\Base\Factories\FactoryBase\Factory;
use DiviSquad\Core\Traits\Singleton;

/**
 * Class AdminMenu
 *
 * @package DiviSquad
 * @since   2.0.0
 */
final class AdminMenu extends Factory {

	use Singleton;

	/**
	 * Store all registry
	 *
	 * @var AdminMenu\MenuInterface[]
	 */
	private static array $registries = array();

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	protected function init_hooks() {
		// Load all main menus and submenus for admin.
		add_action( 'admin_menu', array( $this, 'create_admin_menus' ), 0 );
		add_filter( 'admin_body_class', array( $this, 'add_body_classes' ), 0 );
	}

	/**
	 * Add a new menu to the list of menus.
	 *
	 * @param string $class_name The class name of the menu to add to the list. The class must implement the MenuInterface.
	 *
	 * @see AdminMenu\MenuInterface interface.
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
	 * Enqueue scripts and styles files in the WordPress admin area.
	 *
	 * @return void
	 */
	public function create_admin_menus() {
		global $submenu;

		foreach ( self::$registries as $menu ) {
			// Collect all options for the main menu.
			$main_menu = $menu->get_main_menu();
			if ( count( $main_menu ) > 0 ) {
				// Register the main menu.
				add_menu_page(
					$main_menu['name'],
					$main_menu['title'],
					$main_menu['capability'],
					$main_menu['slug'],
					$main_menu['view'],
					$main_menu['icon'],
					$main_menu['position']
				);
			}

			$all_submenus = $menu->get_sub_menus();
			if ( count( $all_submenus ) > 0 ) {
				$main_menu_slug = $menu->get_main_menu_slug();
				if ( ! isset( $submenu[ $main_menu_slug ] ) ) {
					$submenu[ $main_menu_slug ] = array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				}

				// Update all submenus to the global submenu list.
				foreach ( $all_submenus as $current_submenu ) {
					[ $name, $url, $permission, $page_title ] = array_pad( $current_submenu, 4, null );

					// Update the submenu list.
					$submenu[ $main_menu_slug ][] = array( $name, $permission, $url, $page_title ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				}
			}
		}
	}

	/**
	 * Filters the CSS classes for the body tag in the admin.
	 *
	 * @since 1.0.4
	 *
	 * @param string $classes Space-separated list of CSS classes.
	 *
	 * @return string
	 */
	public function add_body_classes( string $classes ): string {
		foreach ( self::$registries as $menu ) {
			$classes .= ' ' . $menu->get_body_classes();
		}

		return $classes;
	}

	/**
	 * Registered all menus.
	 *
	 * @return array
	 */
	public function get_registered_submenus(): array {
		global $submenu;

		// Set initial value.
		$submenus = array();

		foreach ( self::$registries as $menu ) {
			$main_menu_slug = $menu->get_main_menu_slug();
			if ( isset( $submenu[ $main_menu_slug ] ) && count( $submenu[ $main_menu_slug ] ) > 0 ) {
				foreach ( $submenu[ $main_menu_slug ] as $current_submenu ) {
					if ( ! in_array( $current_submenu, $submenus, true ) ) {
						$submenus[] = $current_submenu;
					}
				}
			}
		}

		return $submenus;
	}
}
