<?php // phpcs:ignore WordPress.Files.FileName

/**
 * The admin menu management class.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   1.0.0
 */

namespace DiviSquad\Managers\Menus;

use DiviSquad\Base\Factories\AdminMenu\Menu;
use DiviSquad\Core\Supports\Media\Image;
use DiviSquad\Core\Supports\Polyfills\Str;
use function admin_url;
use function divi_squad;
use function esc_html__;
use function load_template;

/**
 * Menu class
 *
 * @package DiviSquad
 * @since   2.0.0
 */
class AdminMenu extends Menu {

	/**
	 * Details about the Main Menu.
	 *
	 * @return  array Details about the Main Menu.
	 */
	public function get_main_menu(): array {
		// Load the image class.
		$image = new Image( divi_squad()->get_path( '/build/admin/images/logos' ) );

		// Get the menu icon.
		$menu_icon = $image->get_image( 'divi-squad-d-menu.svg', 'svg' );
		if ( is_wp_error( $menu_icon ) ) {
			$menu_icon = 'dashicons-warning';
		}

		return array(
			'name'       => esc_html__( 'Divi Squad', 'squad-modules-for-divi' ),
			'title'      => esc_html__( 'Divi Squad', 'squad-modules-for-divi' ),
			'capability' => $this->get_permission(),
			'slug'       => $this->get_main_menu_slug(),
			'view'       => array( $this, 'get_template' ),
			'icon'       => $menu_icon,
			'position'   => $this->get_main_menu_position(),
		);
	}

	/**
	 * Details about the Sub Menu.
	 *
	 * @return  array Details about the Sub Menu.
	 */
	public function get_sub_menus(): array {
		$version    = divi_squad()->get_version_dot();
		$menu_slug  = $this->get_main_menu_slug();
		$menu_base  = admin_url( 'admin.php?page=' . $menu_slug );
		$permission = $this->get_permission();

		return array(
			array(
				esc_html__( 'Dashboard', 'squad-modules-for-divi' ),
				sprintf( '%s#/', $menu_base ),
				$permission,
			),
			array(
				esc_html__( 'Modules', 'squad-modules-for-divi' ),
				sprintf( '%s#/modules', $menu_base ),
				$permission,
			),
			array(
				esc_html__( 'Extensions', 'squad-modules-for-divi' ),
				sprintf( '%s#/extensions', $menu_base ),
				$permission,
			),
			array(
				esc_html__( "What's New", 'squad-modules-for-divi' ),
				sprintf( '%1$s#/whats-new/%2$s', $menu_base, $version ),
				$permission,
			),
		);
	}

	/**
	 * Load template file for admin pages.
	 *
	 * @return  void
	 */
	public function get_template() {
		$template_path = sprintf( '%1$s/admin/dashboard.php', divi_squad()->get_template_path() );

		if ( file_exists( $template_path ) ) {
			load_template( $template_path );
		}
	}

	/**
	 * Add the CSS classes for the body tag in the admin.
	 *
	 * @return string
	 */
	public function get_body_classes(): string {
		global $current_screen;

		// Set default classes.
		$classes = '';

		// Collect the version number.
		$version = divi_squad()->get_version();
		$version = str_replace( '.', '_', $version );

		// Add divi squad version class in the body.
		$classes .= sprintf( ' divi_squad_free_v%s', $version );

		// Add specific class to detect the current page.
		if ( isset( $current_screen ) && Str::contains( $current_screen->id, 'divi_squad' ) ) {
			$classes .= ' divi_squad_plugin_page';
		}

		return $classes;
	}
}
