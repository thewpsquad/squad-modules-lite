<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Admin Menu Manager
 *
 * This class handles the registration and management of all WordPress admin menus
 * and submenus for the Divi Squad plugin. It provides a comprehensive system for
 * registering, filtering, and displaying menus throughout the WordPress admin.
 *
 * @since   3.3.3
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Core\Admin;

use DiviSquad\Core\Supports\Polyfills\Str;
use DiviSquad\Utils\Helper as HelperUtil;
use Throwable;
use WP_Screen;
use function add_action;
use function add_filter;
use function add_menu_page;
use function admin_url;
use function apply_filters;
use function count;
use function do_action;
use function esc_html__;
use function is_wp_error;
use function load_template;
use function printf;
use function sanitize_html_class;
use function trim;

/**
 * Admin Menu Manager class.
 *
 * @since   3.3.3
 * @package DiviSquad
 */
class Menu {

	/**
	 * Registered menu handlers.
	 *
	 * @var array{name: string, title: string, capability: string, slug: string, view: callable, icon: string, position: int}
	 */
	protected array $main_menu;

	/**
	 * Registered submenu handlers.
	 *
	 * @var array<array<string, string>>
	 */
	protected array $submenus;

	/**
	 * Constructor for the Admin Menu Manager.
	 *
	 * @since 3.3.3
	 */
	public function __construct() {
		// Initialize the main menu and submenus.
		$this->main_menu = array(
			'name'       => '',
			'title'      => '',
			'capability' => 'manage_options',
			'slug'       => '',
			'view'       => array( $this, 'render_dashboard_template' ),
			'icon'       => '',
			'position'   => 0,
		);
		$this->submenus  = array();

		/**
		 * Action fired when Menu instance is constructed.
		 *
		 * Use this hook to perform actions during Menu instantiation.
		 *
		 * @since 3.3.3
		 *
		 * @param Menu $manager The Admin Menu Manager instance.
		 */
		do_action( 'divi_squad_menu_constructed', $this );
	}

	/**
	 * Initialize the Admin Menu Manager.
	 *
	 * This method sets up all necessary hooks for the menu system to function.
	 *
	 * @since 3.3.3
	 *
	 * @return void
	 */
	public function init(): void {
		try {
			/**
			 * Action fired before menu manager initialization.
			 *
			 * Use this hook to perform actions before the menu system is initialized.
			 *
			 * @since 3.3.3
			 *
			 * @param Menu $manager The Admin Menu Manager instance.
			 */
			do_action( 'divi_squad_before_menu_init', $this );

			// Register action to initialize menus.
			add_action( 'admin_menu', array( $this, 'register_menus' ), 0 );
			add_filter( 'admin_body_class', array( $this, 'add_body_classes' ), 0 );

			// Add these hook registrations.
			add_action( 'divi_squad_menu_badges', array( $this, 'add_badges' ) );
			add_action( 'divi_squad_menu_list_html', array( $this, 'add_menu_list_html' ) );

			// Register default menus.
			$this->register_default_menu();

			/**
			 * Action to register additional menu handlers.
			 *
			 * @since 3.3.3
			 *
			 * @param Menu $manager The Admin Menu Manager instance.
			 */
			do_action( 'divi_squad_register_admin_menus', $this );

			/**
			 * Action fired after menu manager is fully initialized.
			 *
			 * Use this hook to perform actions after the menu system is fully set up.
			 *
			 * @since 3.3.3
			 *
			 * @param Menu $manager The Admin Menu Manager instance.
			 */
			do_action( 'divi_squad_after_menu_init', $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Admin Menu Manager initialization' );
		}
	}

	/**
	 * Register default menu and submenu handlers.
	 *
	 * This method initializes the default menu structure for the plugin admin interface.
	 *
	 * @since 3.3.3
	 *
	 * @return void
	 */
	protected function register_default_menu(): void {
		try {
			/**
			 * Action before registering default menus.
			 *
			 * Fires before the default menu and submenu items are registered.
			 *
			 * @since 3.3.3
			 *
			 * @param Menu $manager The Admin Menu Manager instance.
			 */
			do_action( 'divi_squad_before_register_default_menu', $this );

			// Define the main menu.
			$this->register_main_menu(
				array(
					'name'       => esc_html__( 'Divi Squad', 'squad-modules-for-divi' ),
					'title'      => esc_html__( 'Divi Squad', 'squad-modules-for-divi' ),
					'capability' => 'manage_options',
					'slug'       => divi_squad()->get_admin_menu_slug(),
					'view'       => array( $this, 'render_dashboard_template' ),
					'icon'       => $this->get_menu_icon(),
					'position'   => divi_squad()->get_admin_menu_position(),
				)
			);

			// Define default submenus.
			$this->register_submenu(
				array(
					'name'       => esc_html__( 'Dashboard', 'squad-modules-for-divi' ),
					'url'        => sprintf( '%s#/', admin_url( 'admin.php?page=' . divi_squad()->get_admin_menu_slug() ) ),
					'capability' => 'manage_options',
					'page_title' => esc_html__( 'Divi Squad ‹ Dashboard', 'squad-modules-for-divi' ),
				)
			);
			$this->register_submenu(
				array(
					'name'       => esc_html__( 'Modules', 'squad-modules-for-divi' ),
					'url'        => sprintf( '%s#/modules', admin_url( 'admin.php?page=' . divi_squad()->get_admin_menu_slug() ) ),
					'capability' => 'manage_options',
					'page_title' => esc_html__( 'Divi Squad ‹ Modules', 'squad-modules-for-divi' ),
				)
			);
			$this->register_submenu(
				array(
					'name'       => esc_html__( 'Extensions', 'squad-modules-for-divi' ),
					'url'        => sprintf( '%s#/extensions', admin_url( 'admin.php?page=' . divi_squad()->get_admin_menu_slug() ) ),
					'capability' => 'manage_options',
					'page_title' => esc_html__( 'Divi Squad ‹ Extensions', 'squad-modules-for-divi' ),
				)
			);
			$this->register_submenu(
				array(
					'name'       => esc_html__( "What's New", 'squad-modules-for-divi' ),
					'url'        => sprintf( '%1$s#/whats-new/%2$s', admin_url( 'admin.php?page=' . divi_squad()->get_admin_menu_slug() ), divi_squad()->get_version_dot() ),
					'capability' => 'manage_options',
					'page_title' => esc_html__( 'Divi Squad ‹ What\'s New', 'squad-modules-for-divi' ),
				)
			);

			/**
			 * Action after registering default menus.
			 *
			 * Fires after the default menu and submenu items are registered.
			 *
			 * @since 3.3.3
			 *
			 * @param array{name: string, title: string, capability: string, slug: string, view: callable, icon: string, position: int} $main_menu The main menu configuration.
			 * @param array<array<string, string>>                                                                                      $submenus  The registered submenus.
			 * @param Menu                                                                                                              $manager   The Admin Menu Manager instance.
			 */
			do_action( 'divi_squad_after_register_default_menu', $this->main_menu, $this->submenus, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to register default menus' );
		}
	}

	/**
	 * Register main menu with WordPress.
	 *
	 * This method configures the plugin's main admin menu.
	 *
	 * @since 3.3.3
	 *
	 * @param array{name: string, title: string, capability: string, slug: string, view: callable, icon: string, position: int} $menu_config The menu configuration.
	 *
	 * @return bool Whether the menu was registered successfully.
	 */
	public function register_main_menu( array $menu_config ): bool {
		try {
			/**
			 * Filter the main menu configuration before storing.
			 *
			 * @since 3.3.3
			 *
			 * @param array{name: string, title: string, capability: string, slug: string, view: callable, icon: string, position: int} $menu_config The menu configuration.
			 * @param Menu                                                                                                              $manager     The Admin Menu Manager instance.
			 */
			$menu_config = (array) apply_filters( 'divi_squad_before_store_main_menu', $menu_config, $this );

			// Store the menu configuration.
			$this->main_menu = $menu_config;

			/**
			 * Action fired after storing main menu configuration.
			 *
			 * @since 3.3.3
			 *
			 * @param array{name: string, title: string, capability: string, slug: string, view: callable, icon: string, position: int} $menu_config The menu configuration.
			 * @param Menu                                                                                                              $manager     The Admin Menu Manager instance.
			 */
			do_action( 'divi_squad_main_menu_stored', $this->main_menu, $this );

			return true;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to register main menu' );

			return false;
		}
	}

	/**
	 * Register a submenu with WordPress.
	 *
	 * This method adds a submenu item to the plugin's admin menu.
	 *
	 * @since 3.3.3
	 *
	 * @param array<string, string> $submenu_config The submenu configuration.
	 *
	 * @return bool Whether the submenu was registered successfully.
	 */
	public function register_submenu( array $submenu_config ): bool {
		try {
			/**
			 * Filter to modify submenu configuration before registration.
			 *
			 * Allows developers to modify a submenu configuration before it's
			 * added to the registered submenus array.
			 *
			 * @since 3.3.3
			 *
			 * @param array<string, string> $submenu_config The submenu configuration.
			 * @param Menu                  $manager        The Admin Menu Manager instance.
			 */
			$submenu_config = (array) apply_filters( 'divi_squad_submenu_config', $submenu_config, $this );

			/**
			 * Filter to conditionally skip submenu registration.
			 *
			 * Allows developers to conditionally skip registering a submenu
			 * based on custom logic.
			 *
			 * @since 3.3.3
			 *
			 * @param bool                  $skip_submenu   Whether to skip registering the submenu. Default false.
			 * @param array<string, string> $submenu_config The submenu configuration.
			 * @param Menu                  $manager        The Admin Menu Manager instance.
			 */
			$skip_submenu = apply_filters( 'divi_squad_skip_submenu', false, $submenu_config, $this );
			if ( $skip_submenu ) {
				return false;
			}

			/**
			 * Action before submenu is registered.
			 *
			 * @since 3.3.3
			 *
			 * @param array<string, string> $submenu_config The submenu configuration.
			 * @param Menu                  $manager        The Admin Menu Manager instance.
			 */
			do_action( 'divi_squad_before_submenu_registered', $submenu_config, $this );

			// Add the submenu to the registered submenus.
			$this->submenus[] = $submenu_config;

			/**
			 * Action fired after registering a submenu.
			 *
			 * @since 3.3.3
			 *
			 * @param array<string, string> $submenu_config The submenu configuration.
			 * @param Menu                  $manager        The Admin Menu Manager instance.
			 */
			do_action( 'divi_squad_submenu_registered', $submenu_config, $this );

			return true;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to register submenu' );

			return false;
		}
	}

	/**
	 * Register all menus with WordPress.
	 *
	 * This method handles the actual registration of menus with WordPress admin.
	 *
	 * @since 3.3.3
	 *
	 * @return void
	 */
	public function register_menus(): void {
		try {
			global $submenu;

			/**
			 * Filter the registered menus before registering them with WordPress.
			 *
			 * @since 3.3.3
			 *
			 * @param array{name: string, title: string, capability: string, slug: string, view: callable, icon: string, position: int} $main_menu The main menu configuration.
			 * @param Menu                                                                                                              $manager   The Admin Menu Manager instance.
			 */
			$this->main_menu = apply_filters( 'divi_squad_main_menu', $this->main_menu, $this );

			/**
			 * Filter the registered submenus before registering them with WordPress.
			 *
			 * @since 3.3.3
			 *
			 * @param array<array<string, string>> $submenus The registered submenus.
			 * @param Menu                         $manager  The Admin Menu Manager instance.
			 */
			$this->submenus = apply_filters( 'divi_squad_submenus', $this->submenus, $this );

			/**
			 * Action fired before registering menus with WordPress.
			 *
			 * @since 3.3.3
			 *
			 * @param array{name: string, title: string, capability: string, slug: string, view: callable, icon: string, position: int} $main_menu The main menu configuration.
			 * @param array<array<string, string>>                                                                                      $submenus  The registered submenus.
			 * @param Menu                                                                                                              $manager   The Admin Menu Manager instance.
			 */
			do_action( 'divi_squad_before_register_menus', $this->main_menu, $this->submenus, $this );

			// Register the main menu with WordPress.
			if ( count( $this->main_menu ) > 0 ) {
				$menu_page = add_menu_page(
					$this->main_menu['name'],
					$this->main_menu['title'],
					$this->main_menu['capability'],
					$this->main_menu['slug'],
					$this->main_menu['view'],
					$this->main_menu['icon'],
					$this->main_menu['position']
				);

				/**
				 * Action fired after the main menu page is registered.
				 *
				 * @since 3.3.3
				 *
				 * @param string                                                                                                            $menu_page The hook suffix returned by add_menu_page.
				 * @param array{name: string, title: string, capability: string, slug: string, view: callable, icon: string, position: int} $main_menu The main menu configuration.
				 * @param Menu                                                                                                              $manager   The Admin Menu Manager instance.
				 */
				do_action( 'divi_squad_main_menu_registered', $menu_page, $this->main_menu, $this );
			}

			// Register each submenu with WordPress.
			$menu_slug = divi_squad()->get_admin_menu_slug();
			if ( ! isset( $submenu[ $menu_slug ] ) ) {
				$submenu[ $menu_slug ] = array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			}

			/**
			 * Action before submenu items are registered with WordPress.
			 *
			 * @since 3.3.3
			 *
			 * @param array<array<string, string>> $submenus  The registered submenus.
			 * @param string                       $menu_slug The parent menu slug.
			 * @param Menu                         $manager   The Admin Menu Manager instance.
			 */
			do_action( 'divi_squad_before_register_submenu_items', $this->submenus, $menu_slug, $this );

			foreach ( $this->submenus as $current_submenu ) {
				// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
				$submenu[ $menu_slug ][] = array(
					$current_submenu['name'],
					$current_submenu['capability'],
					$current_submenu['url'],
					$current_submenu['page_title'] ?? $current_submenu['name'],
				);
				// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited

				/**
				 * Action after individual submenu item is registered.
				 *
				 * @since 3.3.3
				 *
				 * @param array<string, string> $current_submenu The submenu configuration.
				 * @param string                $menu_slug       The parent menu slug.
				 * @param Menu                  $manager         The Admin Menu Manager instance.
				 */
				do_action( 'divi_squad_submenu_item_registered', $current_submenu, $menu_slug, $this );
			}

			/**
			 * Action fired after registering menus with WordPress.
			 *
			 * @since 3.3.3
			 *
			 * @param array{name: string, title: string, capability: string, slug: string, view: callable, icon: string, position: int} $main_menu The main menu configuration.
			 * @param array<array<string, string>>                                                                                      $submenus  The registered submenus.
			 * @param Menu                                                                                                              $manager   The Admin Menu Manager instance.
			 */
			do_action( 'divi_squad_after_register_menus', $this->main_menu, $this->submenus, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to register menus with WordPress' );
		}
	}

	/**
	 * Get the menu icon for the main menu.
	 *
	 * This method retrieves the SVG icon or falls back to a dashicon.
	 *
	 * @since 3.3.3
	 *
	 * @return string The menu icon URL or dashicon name.
	 */
	protected function get_menu_icon(): string {
		try {
			/**
			 * Filter to override the menu icon path.
			 *
			 * @since 3.3.3
			 *
			 * @param string|null $icon_path The custom icon path. Return null to use default behavior.
			 * @param Menu        $manager   The Admin Menu Manager instance.
			 */
			$custom_icon = apply_filters( 'divi_squad_menu_icon_path', null, $this );

			if ( null !== $custom_icon ) {
				return $custom_icon;
			}

			// Load the image class.
			$image = divi_squad()->load_image( '/build/admin/images/logos' );

			// Get the menu icon.
			$menu_icon = $image->get_image( 'divi-squad-d-menu.svg', 'svg' );
			if ( is_wp_error( $menu_icon ) ) {
				$menu_icon = 'dashicons-warning';
			}

			/**
			 * Filter the final menu icon.
			 *
			 * @since 3.3.3
			 *
			 * @param string $menu_icon The menu icon URL or dashicon name.
			 * @param Menu   $manager   The Admin Menu Manager instance.
			 */
			return apply_filters( 'divi_squad_menu_icon', $menu_icon, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to get menu icon' );

			return 'dashicons-warning';
		}
	}

	/**
	 * Render the dashboard template.
	 *
	 * This method loads the admin dashboard template.
	 *
	 * @since 3.3.3
	 *
	 * @return void
	 */
	public function render_dashboard_template(): void {
		$template_path = divi_squad()->get_template_path( 'admin/dashboard.php' );

		if ( ! divi_squad()->is_template_exists( 'admin/dashboard.php' ) ) {
			/**
			 * Action fired when the dashboard template is missing.
			 *
			 * @since 3.3.3
			 *
			 * @param string $template_path The missing template path.
			 * @param Menu   $manager       The Admin Menu Manager instance.
			 */
			do_action( 'divi_squad_dashboard_template_missing', $template_path, $this );

			return;
		}

		/**
		 * Action before dashboard template is rendered.
		 *
		 * @since 3.3.3
		 *
		 * @param string $template_path The template path.
		 * @param Menu   $manager       The Admin Menu Manager instance.
		 */
		do_action( 'divi_squad_before_dashboard_template', $template_path, $this );

		load_template( $template_path );

		/**
		 * Action after dashboard template is rendered.
		 *
		 * @since 3.3.3
		 *
		 * @param string $template_path The template path that was used.
		 * @param Menu   $manager       The Admin Menu Manager instance.
		 */
		do_action( 'divi_squad_after_dashboard_template', $template_path, $this );
	}

	/**
	 * Add body classes for the admin pages.
	 *
	 * This method adds CSS classes to the admin body tag for proper styling of admin pages.
	 *
	 * @since 3.3.3
	 *
	 * @param string $classes Current body classes.
	 *
	 * @return string Modified body classes.
	 */
	public function add_body_classes( string $classes ): string {
		try {
			$classes = trim( $classes );

			// Only add classes on Squad pages.
			if ( HelperUtil::is_squad_page() ) {
				/**
				 * Filter for the base admin body class.
				 *
				 * @since 3.3.3
				 *
				 * @param string $base_class The base CSS class for admin pages.
				 * @param Menu   $manager    The Admin Menu Manager instance.
				 */
				$base_class = apply_filters( 'divi_squad_admin_base_class', 'divi-squad-admin', $this );

				// Add base class if not empty.
				if ( '' !== $base_class ) {
					$classes .= ' ' . $base_class;
				}

				// Add plugin life type class.
				$life_type = divi_squad()->get_plugin_life_type();
				if ( '' !== $life_type ) {
					$classes .= ' divi-squad-' . sanitize_html_class( $life_type ) . ' ';
				}

				/**
				 * Filter for additional admin body classes.
				 *
				 * @since 3.3.3
				 *
				 * @param array<string> $extra_classes Additional body classes.
				 * @param string        $life_type     The plugin life type.
				 * @param Menu          $manager       The Admin Menu Manager instance.
				 */
				$extra_classes = apply_filters( 'divi_squad_admin_extra_classes', array(), $life_type, $this );

				// Add extra classes.
				if ( is_array( $extra_classes ) && count( $extra_classes ) > 0 ) {
					$classes .= ' ' . implode( ' ', $extra_classes ) . ' ';
				}
			}

			/**
			 * Filter the admin body classes.
			 *
			 * @since 3.3.3
			 *
			 * @param string $classes The body classes.
			 * @param Menu   $manager The Admin Menu Manager instance.
			 */
			return apply_filters( 'divi_squad_admin_body_classes', $classes, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to add admin body classes' );

			return $classes;
		}
	}

	/**
	 * Add badges to the requirements page.
	 *
	 * This method adds version and edition badges to the admin interface.
	 *
	 * @since 3.3.3
	 *
	 * @param string $plugin_life_type The plugin life type.
	 *
	 * @return void
	 */
	public function add_badges( string $plugin_life_type ): void {
		try {
			/**
			 * Action fired before menu badges are added.
			 *
			 * @since 3.3.3
			 *
			 * @param string $plugin_life_type The plugin life type.
			 * @param Menu   $manager          The Admin Menu Manager instance.
			 */
			do_action( 'divi_squad_before_menu_badges', $plugin_life_type, $this );

			/**
			 * Filter whether to display nightly badge.
			 *
			 * @since 3.3.3
			 *
			 * @param bool   $show_nightly     Whether to show the nightly badge.
			 * @param string $plugin_life_type The plugin life type.
			 * @param Menu   $manager          The Admin Menu Manager instance.
			 */
			$show_nightly = apply_filters( 'divi_squad_show_nightly_badge', 'nightly' === $plugin_life_type, $plugin_life_type, $this );

			// Add the nightly badge.
			if ( $show_nightly ) {
				/**
				 * Filter the nightly badge text.
				 *
				 * @since 3.3.3
				 *
				 * @param string $badge_name The badge name text.
				 * @param Menu   $manager    The Admin Menu Manager instance.
				 */
				$badge_name = apply_filters( 'divi_squad_nightly_badge_name', esc_html__( 'Nightly', 'squad-modules-for-divi' ), $this );

				/**
				 * Filter the nightly badge version text.
				 *
				 * @since 3.3.3
				 *
				 * @param string $badge_version The badge version text.
				 * @param Menu   $manager       The Admin Menu Manager instance.
				 */
				$badge_version = apply_filters( 'divi_squad_nightly_badge_version', esc_html__( 'current', 'squad-modules-for-divi' ), $this );

				printf(
					'<li class="nightly-badge"><span class="badge-name">%s</span><span class="badge-version">%s</span></li>',
					esc_html( $badge_name ),
					esc_html( $badge_version )
				);
			}

			/**
			 * Filter whether to display stable lite badge.
			 *
			 * @since 3.3.3
			 *
			 * @param bool   $show_stable      Whether to show the stable lite badge.
			 * @param string $plugin_life_type The plugin life type.
			 * @param Menu   $manager          The Admin Menu Manager instance.
			 */
			$show_stable = apply_filters( 'divi_squad_show_stable_badge', 'stable' === $plugin_life_type, $plugin_life_type, $this );

			// Add the stable lite badge.
			if ( $show_stable ) {
				/**
				 * Filter the stable badge text.
				 *
				 * @since 3.3.3
				 *
				 * @param string $badge_name The badge name text.
				 * @param Menu   $manager    The Admin Menu Manager instance.
				 */
				$badge_name = apply_filters( 'divi_squad_stable_badge_name', esc_html__( 'Lite', 'squad-modules-for-divi' ), $this );

				/**
				 * Filter the stable badge version text.
				 *
				 * @since 3.3.3
				 *
				 * @param string $badge_version The badge version text.
				 * @param Menu   $manager       The Admin Menu Manager instance.
				 */
				$badge_version = apply_filters( 'divi_squad_stable_badge_version', esc_html( divi_squad()->get_version() ), $this );

				printf(
					'<li class="stable-lite-badge"><span class="badge-name">%s</span><span class="badge-version">%s</span></li>',
					esc_html( $badge_name ),
					esc_html( $badge_version )
				);
			}

			/**
			 * Action fired after menu badges are added.
			 *
			 * @since 3.3.3
			 *
			 * @param string $plugin_life_type The plugin life type.
			 * @param Menu   $manager          The Admin Menu Manager instance.
			 */
			do_action( 'divi_squad_after_menu_badges', $plugin_life_type, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to add menu badges' );
		}
	}

	/**
	 * Add the menu list HTML.
	 *
	 * This method outputs the HTML for menu items in the admin interface.
	 * It handles both hash-based (SPA) links and standard links, with appropriate
	 * active state management.
	 *
	 * @since 3.3.3
	 *
	 * @return void
	 */
	public function add_menu_list_html(): void {
		$screen = get_current_screen();
		if ( ! $screen instanceof WP_Screen ) {
			return;
		}

		$screen_id = $screen->id;
		$submenus  = $this->get_global_submenus();

		if ( count( $submenus ) < 1 ) {
			return;
		}

		/**
		 * Action before menu list HTML is output.
		 *
		 * @since 3.3.3
		 *
		 * @param array<array<string, string>> $submenus  The submenus to display.
		 * @param string                       $screen_id The current screen ID.
		 * @param Menu                         $manager   The Admin Menu Manager instance.
		 */
		do_action( 'divi_squad_before_menu_list_html', $submenus, $screen_id, $this );

		/**
		 * Filter whether to output the menu list as an unordered list.
		 *
		 * @since 3.3.3
		 *
		 * @param bool                         $use_ul    Whether to wrap menu items in a <ul> tag.
		 * @param array<array<string, string>> $submenus  The submenus to display.
		 * @param string                       $screen_id The current screen ID.
		 * @param Menu                         $manager   The Admin Menu Manager instance.
		 */
		$use_ul = apply_filters( 'divi_squad_menu_use_unordered_list', true, $submenus, $screen_id, $this );

		/**
		 * Filter the wrapper element classes for the menu list.
		 *
		 * @since 3.3.3
		 *
		 * @param string                       $wrapper_classes The CSS classes for the wrapper element.
		 * @param array<array<string, string>> $submenus        The submenus to display.
		 * @param string                       $screen_id       The current screen ID.
		 * @param Menu                         $manager         The Admin Menu Manager instance.
		 */
		$wrapper_classes = apply_filters( 'divi_squad_menu_wrapper_classes', 'divi-squad-menu-list', $submenus, $screen_id, $this );

		// Open the wrapper element if using a UL.
		if ( $use_ul ) {
			printf(
				'<ul class="%s">',
				esc_attr( $wrapper_classes )
			);
		}

		// Menu item counter for unique IDs if needed.
		$item_counter = 0;

		/**
		 * Filter the menu items to sort or reorder them before display.
		 *
		 * @since 3.3.3
		 *
		 * @param array<array<string, string>> $submenus  The submenus to display.
		 * @param string                       $screen_id The current screen ID.
		 * @param Menu                         $manager   The Admin Menu Manager instance.
		 */
		$submenus = apply_filters( 'divi_squad_menu_items_before_display', $submenus, $screen_id, $this );

		foreach ( $submenus as $submenu ) {
			++$item_counter;

			// Extract menu information.
			$menu_name       = $submenu['name'];
			$menu_url        = $submenu['url'];
			$menu_capability = $submenu['capability'] ?? 'manage_options';

			// Skip menu items the user doesn't have access to.
			if ( ! current_user_can( $menu_capability ) ) {
				continue;
			}

			/**
			 * Filter menu item name before display.
			 *
			 * @since 3.3.3
			 *
			 * @param string                $menu_name The menu item name.
			 * @param array<string, string> $submenu   The full submenu item data.
			 * @param string                $screen_id The current screen ID.
			 * @param int                   $counter   The menu item counter.
			 * @param Menu                  $manager   The Admin Menu Manager instance.
			 */
			$menu_name = apply_filters( 'divi_squad_menu_item_name', $menu_name, $submenu, $screen_id, $item_counter, $this );

			/**
			 * Filter menu item URL before display.
			 *
			 * @since 3.3.3
			 *
			 * @param string                $menu_url  The menu item URL.
			 * @param array<string, string> $submenu   The full submenu item data.
			 * @param string                $screen_id The current screen ID.
			 * @param int                   $counter   The menu item counter.
			 * @param Menu                  $manager   The Admin Menu Manager instance.
			 */
			$menu_url = apply_filters( 'divi_squad_menu_item_url', $menu_url, $submenu, $screen_id, $item_counter, $this );

			// Check if it's a hash URL (contains #).
			$is_hash_url = Str::contains( $menu_url, 'divi_squad_dashboard#' ) || Str::contains( $menu_url, '#/' );

			/**
			 * Filter whether an item is considered a hash URL (SPA navigation).
			 *
			 * @since 3.3.3
			 *
			 * @param bool                  $is_hash_url Whether the item uses hash URL navigation.
			 * @param string                $menu_url    The menu item URL.
			 * @param array<string, string> $submenu     The full submenu item data.
			 * @param Menu                  $manager     The Admin Menu Manager instance.
			 */
			$is_hash_url = apply_filters( 'divi_squad_menu_is_hash_url', $is_hash_url, $menu_url, $submenu, $this );

			if ( $is_hash_url ) {
				printf(
					'<li class="menu-items"><a aria-current="page" href="%1$s">%2$s</a></li>',
					esc_url( $menu_url ),
					wp_kses_post( $menu_name )
				);
			} else {
				$active_menu_class = ( "divi-squad_page_$menu_url" === $screen_id ) ? 'active' : '';
				printf(
					'<li class="menu-item %1$s"><a aria-current="page" class="%1$s" href="%2$s">%3$s</a></li>',
					esc_attr( $active_menu_class ),
					esc_url( admin_url( "admin.php?page=$menu_url" ) ),
					wp_kses_post( $menu_name )
				);
			}
		}

		/**
		 * Action to output additional menu items.
		 *
		 * @since 3.3.3
		 *
		 * @param string $screen_id The current screen ID.
		 * @param int    $counter   The current menu item count.
		 * @param Menu   $manager   The Admin Menu Manager instance.
		 */
		do_action( 'divi_squad_menu_additional_items', $screen_id, $item_counter, $this );

		// Close the wrapper element if using a UL.
		if ( $use_ul ) {
			echo '</ul>';
		}

		/**
		 * Action after menu list HTML is output.
		 *
		 * @since 3.3.3
		 *
		 * @param array<array<string, string>> $submenus  The submenus that were displayed.
		 * @param string                       $screen_id The current screen ID.
		 * @param Menu                         $manager   The Admin Menu Manager instance.
		 */
		do_action( 'divi_squad_after_menu_list_html', $submenus, $screen_id, $this );
	}

	/**
	 * Get submenus from WordPress global array.
	 *
	 * @since 3.3.3
	 *
	 * @return array<array<string, string>> The registered submenus from WordPress global.
	 */
	public function get_global_submenus(): array {
		global $submenu;

		$menu_slug       = divi_squad()->get_admin_menu_slug();
		$global_submenus = array();

		// Check if our menu exists in the global submenu array.
		if ( isset( $submenu[ $menu_slug ] ) && is_array( $submenu[ $menu_slug ] ) ) {
			foreach ( $submenu[ $menu_slug ] as $sub ) {
				// Ensure array has expected structure.
				if ( is_array( $sub ) && count( $sub ) >= 3 ) {
					$global_submenus[] = array(
						'name'       => $sub[0],
						'capability' => $sub[1],
						'url'        => $sub[2],
						'page_title' => $sub[3] ?? $sub[0],
					);
				}
			}
		}

		/**
		 * Filter the WordPress global submenus.
		 *
		 * @since 3.3.3
		 *
		 * @param array<array<string, string>> $global_submenus The global submenus.
		 * @param Menu                         $manager         The Admin Menu Manager instance.
		 */
		return apply_filters( 'divi_squad_global_submenus', $global_submenus, $this );
	}

	/**
	 * Get all menu items as formatted for localization.
	 *
	 * @since 3.3.3
	 *
	 * @return array<array<string, string>> The menu items formatted for localization.
	 */
	public function get_menu_items_for_localization(): array {
		$menu_items = array();
		$submenus   = $this->get_global_submenus();

		foreach ( $submenus as $submenu ) {
			$menu_items[] = array(
				'menuTitle'  => $submenu['name'],
				'menuSlug'   => $submenu['url'],
				'capability' => $submenu['capability'],
				'pageTitle'  => $submenu['page_title'],
			);
		}

		/**
		 * Filter the menu items for localization.
		 *
		 * @since 3.3.3
		 *
		 * @param array<array<string, string>> $menu_items The menu items.
		 * @param Menu                         $manager    The Admin Menu Manager instance.
		 */
		return apply_filters( 'divi_squad_menu_items_for_localization', $menu_items, $this );
	}
}
