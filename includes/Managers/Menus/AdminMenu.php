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
	 * AdminMenu constructor.
	 */
	public function __construct() {
		$this->register_hooks();
	}

	/**
	 * Register the hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'divi_squad_menu_badges', array( $this, 'add_badges' ) );
		add_action( 'divi_squad_menu_list_html', array( $this, 'add_menu_list_html' ) );
	}

	/**
	 * Add badges to the requirements page.
	 *
	 * @since 3.2.3
	 *
	 * @param string $plugin_life_type The plugin life type.
	 *
	 * @return void
	 */
	public function add_badges( string $plugin_life_type ): void {
		// Add the nightly badge.
		if ( 'nightly' === $plugin_life_type ) {
			printf(
				'<li class="nightly-badge"><span class="badge-name">%s</span><span class="badge-version">%s</span></li>',
				esc_html__( 'Nightly', 'squad-modules-for-divi' ),
				esc_html__( 'current', 'squad-modules-for-divi' )
			);
		}

		// Add the stable lite badge.
		if ( 'stable' === $plugin_life_type ) {
			printf(
				'<li class="stable-lite-badge"><span class="badge-name">%s</span><span class="badge-version">%s</span></li>',
				esc_html__( 'Lite', 'squad-modules-for-divi' ),
				esc_html( divi_squad()->get_version() )
			);
		}
	}

	/**
	 * Add the menu list HTML.
	 *
	 * @return void
	 */
	public function add_menu_list_html(): void {
		$screen = get_current_screen();
		if ( ! $screen instanceof \WP_Screen ) {
			return;
		}

		$screen_id = $screen->id;

		// Collect all registered menus from Menu Manager.
		$register = \DiviSquad\Base\Factories\AdminMenu::get_instance();
		$submenus = $register->get_registered_submenus();

		if ( count( $submenus ) < 1 ) {
			return;
		}

		foreach ( $submenus as $submenu ) :
			if ( ! is_array( $submenu ) || count( $submenu ) < 3 ) {
				continue;
			}

			// Ensure menu items are strings.
			$menu_name = $submenu[0];
			$menu_url  = $submenu[2];

			if ( ! is_string( $menu_name ) || ! is_string( $menu_url ) ) {
				continue;
			}

			if ( ! Str::contains( $menu_url, 'divi_squad_dashboard#' ) ) :
				$active_menu_class = ( "divi-squad_page_{$menu_url}" === $screen_id ) ? 'active' : '';
				$admin_url         = admin_url( "admin.php?page={$menu_url}" );

				printf(
					'<li class="menu-item %s"><a aria-current="page" class="%s" href="%s">%s</a></li>',
					esc_attr( $active_menu_class ),
					esc_attr( $active_menu_class ),
					esc_url( $admin_url ),
					wp_kses_post( $menu_name )
				);
				else :
					printf(
						'<li class="menu-item"><a aria-current="page" href="%s">%s</a></li>',
						esc_url( $menu_url ),
						esc_html( $menu_name )
					);
			endif;
		endforeach;
	}

	/**
	 * Details about the Main Menu.
	 *
	 * @return array<string, int|list<$this|string>|string> Details about the Main Menu.
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
	 * @return array<int, array<int, string>> Details about the Sub Menu.
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
				esc_html__( 'Divi Squad ‹ Dashboard', 'squad-modules-for-divi' ),
			),
			array(
				esc_html__( 'Modules', 'squad-modules-for-divi' ),
				sprintf( '%s#/modules', $menu_base ),
				$permission,
				esc_html__( 'Divi Squad ‹ Modules', 'squad-modules-for-divi' ),
			),
			array(
				esc_html__( 'Extensions', 'squad-modules-for-divi' ),
				sprintf( '%s#/extensions', $menu_base ),
				$permission,
				esc_html__( 'Divi Squad ‹ Extensions', 'squad-modules-for-divi' ),
			),
			array(
				esc_html__( "What's New", 'squad-modules-for-divi' ),
				sprintf( '%1$s#/whats-new/%2$s', $menu_base, $version ),
				$permission,
				esc_html__( 'Divi Squad ‹ What\'s New', 'squad-modules-for-divi' ),
			),
		);
	}

	/**
	 * Load template file for admin pages.
	 *
	 * @return void
	 */
	public function get_template() {
		if ( divi_squad()->get_wp_fs()->exists( divi_squad()->get_template_path( 'admin/dashboard.php' ) ) ) {
			load_template( divi_squad()->get_template_path( 'admin/dashboard.php' ) );
		}
	}

	/**
	 * Add the CSS classes for the body tag in the admin.
	 *
	 * @return string
	 */
	public function get_body_classes(): string {
		return '';
	}
}
