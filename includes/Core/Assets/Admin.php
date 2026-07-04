<?php
/**
 * Admin AssetsCore Manager
 *
 * Handles registration and enqueuing of admin-specific assets using the new unified asset system.
 *
 * @since   3.3.0
 * @author  The WP Squad <support@squadmodules.com>
 * @package DiviSquad
 */

namespace DiviSquad\Core\Assets;

use DiviSquad\Base\Factories\AdminMenu as AdminMenuFactory;
use DiviSquad\Core\Assets;
use DiviSquad\Utils\WP as WPUtil;
use DiviSquad\Utils\Helper as HelperUtil;
use DiviSquad\Utils\Divi as DiviUtil;
use Throwable;

/**
 * Admin AssetsCore Manager
 *
 * Handles registration and enqueuing of admin-specific assets using the new unified asset system.
 *
 * @since 3.3.0
 */
final class Admin {

	/**
	 * Initialize the assets manager.
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize admin assets
	 */
	public function init_hooks(): void {
		// Register and enqueue admin assets.
		add_action( 'divi_squad_register_admin_assets', array( $this, 'register' ) );
		add_action( 'divi_squad_enqueue_admin_assets', array( $this, 'enqueue' ) );

		// Add localize data.
		add_filter( 'divi_squad_global_localize_data', array( $this, 'add_localize_data' ) );
	}

	/**
	 * Register admin assets
	 *
	 * @param Assets $assets AssetsCore manager instance.
	 */
	public function register( Assets $assets ): void {
		try {
			// Register common admin scripts.
			$assets->register_script(
				'admin-common',
				array(
					'file' => 'common',
					'path' => 'admin',
				)
			);

			$assets->register_style(
				'admin-common',
				array(
					'file' => 'common',
					'path' => 'admin',
					'ext'  => 'css',
				)
			);

			$assets->register_script(
				'admin-app',
				array(
					'file' => 'app',
					'path' => 'admin',
				)
			);

			$assets->register_style(
				'admin-app',
				array(
					'file' => 'app',
					'path' => 'admin',
					'ext'  => 'css',
				)
			);

			/**
			 * Fires after admin assets are registered
			 *
			 * @param Assets $assets AssetsCore manager instance
			 */
			do_action( 'divi_squad_after_register_admin_assets', $assets );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to register admin assets' );
		}
	}

	/**
	 * Enqueue admin assets
	 *
	 * @param Assets $assets AssetsCore manager instance.
	 */
	public function enqueue( Assets $assets ): void {
		try {
			// Always enqueue common assets.
			$assets->enqueue_script( 'admin-common' );
			$assets->enqueue_style( 'admin-common' );

			// Squad page specific assets.
			if ( HelperUtil::is_squad_page() ) {
				$assets->enqueue_script( 'admin-app' );
				$assets->enqueue_style( 'admin-app' );
			}

			/**
			 * Fires after admin assets are enqueued
			 *
			 * @param Assets $assets AssetsCore manager instance
			 */
			do_action( 'divi_squad_after_enqueue_admin_assets', $assets );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to enqueue admin assets' );
		}
	}

	/**
	 * Add localization data to scripts
	 *
	 * @param array<string, mixed> $global_data The global localized data.
	 *
	 * @return array<string, mixed>
	 */
	public function add_localize_data( array $global_data ): array {
		try {
			// Add common admin data.
			if ( is_admin() || ( DiviUtil::is_fb_enabled() && is_user_logged_in() ) ) {
				$global_data = array_merge( $global_data, $this->get_common_localize_data() );
			}

			// Add squad page specific data.
			if ( HelperUtil::is_squad_page() ) {
				$global_data = array_merge( $global_data, $this->get_admin_localize_data() );
			}
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to add localization data' );
		} finally {
			return $global_data;
		}
	}

	/**
	 * Get common localized data
	 *
	 * @return array<string, mixed>
	 */
	private function get_common_localize_data(): array {
		$rest_routes = array();

		if ( ! divi_squad()->requirements->is_fulfilled() ) {
			return $rest_routes;
		}

		// Get all routes
		$all_routes = divi_squad()->rest_routes->get_routes();

		// Group routes by version
		$routes_by_version = array();

		foreach ( $all_routes as $route ) {
			$version   = $route->get_version();
			$namespace = $route->get_namespace();

			if ( ! isset( $routes_by_version[ $version ] ) ) {
				$routes_by_version[ $version ] = array(
					'namespace' => $namespace,
					'routes'    => array(),
				);
			}

			$route_paths = $route->get_routes();

			foreach ( $route_paths as $path => $handlers ) {
				$route_name = divi_squad()->rest_routes->format_route_name( $path );
				$methods    = array();

				foreach ( $handlers as $handler ) {
					if ( isset( $handler['methods'] ) ) {
						$methods[] = $handler['methods'];
					}
				}

				$routes_by_version[ $version ]['routes'][ $route_name ] = array(
					'root'    => $path,
					'methods' => $methods,
				);
			}
		}

		// Add routes by version to the REST routes array
		foreach ( $routes_by_version as $version => $version_data ) {
			$rest_routes[ "rest_api_{$version}" ] = array(
				'route'     => get_rest_url(),
				'namespace' => $version_data['namespace'],
				'routes'    => $version_data['routes'],
				'version'   => $version,
			);
		}

		/**
		 * Filter common admin localized data
		 *
		 * @param array $data Localized data
		 */
		return apply_filters( 'divi_squad_common_admin_localize_data', $rest_routes );
	}

	/**
	 * Get admin-specific localized data
	 *
	 * @return array<string, mixed>
	 */
	private function get_admin_localize_data(): array {
		if ( ! HelperUtil::is_squad_page() ) {
			return array();
		}

		try {
			$data = array(
				'admin_menus' => $this->get_admin_menus(),
				'premium'     => $this->get_premium_status(),
				'links'       => $this->get_admin_links(),
				'l10n'        => $this->get_localized_strings(),
				'plugins'     => WPUtil::get_active_plugins(),
				'notices'     => array(
					'has_welcome' => true,
				),
			);

			/**
			 * Filter admin-specific localized data
			 *
			 * @param array $data Localized data
			 */
			return apply_filters( 'divi_squad_admin_specific_localize_data', $data );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to get admin localize data' );
			return array();
		}
	}

	/**
	 * Get registered admin menus
	 *
	 * @return array<string, mixed>
	 */
	private function get_admin_menus(): array {
		try {
			$menu_register = AdminMenuFactory::get_instance();

			if ( ! $menu_register instanceof AdminMenuFactory ) {
				return array();
			}

			$submenus = $menu_register->get_registered_submenus();

			$submenus = array_map(
				static function ( $submenu ) {
					[ $menu_title, $capability, $page_slug, $page_title ] = array_pad( $submenu, 4, null );

					return array(
						'menuTitle'  => $menu_title,
						'menuSlug'   => $page_slug,
						'capability' => $capability,
						'pageTitle'  => $page_title ?? $menu_title,
					);
				},
				$submenus
			);

			/**
			 * Filter registered admin submenus
			 *
			 * @param array<string, mixed> $submenus Registered submenus
			 */
			return apply_filters( 'divi_squad_admin_submenus', $submenus );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to get admin menus' );
			return array();
		}
	}

	/**
	 * Get premium status information
	 *
	 * @return array<string, bool>
	 */
	private function get_premium_status(): array {
		try {
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$installed_plugins = array_keys( get_plugins() );
			$pro_basename      = divi_squad()->get_pro_basename();
			$fs                = divi_squad_fs();

			$status = array(
				'is_active'    => divi_squad()->is_pro_activated(),
				'is_installed' => in_array( $pro_basename, $installed_plugins, true ),
				'has_license'  => $fs->has_active_valid_license(),
				'in_trial'     => $fs->is_trial(),
			);

			/**
			 * Filter premium status data
			 *
			 * @param array<string, bool> $status Premium status information
			 */
			return apply_filters( 'divi_squad_premium_status', $status );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to get premium status' );
			return array(
				'is_active'    => false,
				'is_installed' => false,
				'has_license'  => false,
				'in_trial'     => false,
			);
		}
	}

	/**
	 * Get admin links
	 *
	 * @return array<string, string>
	 */
	private function get_admin_links(): array {
		try {
			$base_dashboard_url = admin_url( 'admin.php?page=divi_squad_dashboard' );

			$links = array(
				'dashboard'  => $base_dashboard_url . '#/',
				'modules'    => $base_dashboard_url . '#/modules',
				'extensions' => $base_dashboard_url . '#/extensions',
				'whats_new'  => $base_dashboard_url . '#/whats-new',
				'settings'   => $base_dashboard_url . '#/settings',
				'support'    => $base_dashboard_url . '#/support',
				'docs'       => 'https://docs.squadmodules.com/',
			);

			// Add Freemius-specific links if available.
			if ( ! divi_squad_fs()->can_use_premium_code() ) {
				$links['my_account'] = divi_squad_fs()->get_account_url();
				$links['upgrade']    = divi_squad_fs()->get_upgrade_url();
				$links['pricing']    = divi_squad_fs()->pricing_url();
			}

			/**
			 * Filter admin navigation links
			 *
			 * @param array<string, string> $links Admin navigation links
			 */
			return apply_filters( 'divi_squad_admin_links', $links );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to get admin links' );
			return array(
				'site_url' => home_url( '/' ),
				'plugins'  => admin_url( 'plugins.php' ),
			);
		}
	}

	/**
	 * Get localized strings
	 *
	 * @return array<string, string>
	 */
	private function get_localized_strings(): array {
		$strings = array(
			// Navigation.
			'dashboard'            => esc_html__( 'Dashboard', 'squad-modules-for-divi' ),
			'modules'              => esc_html__( 'Modules', 'squad-modules-for-divi' ),
			'extensions'           => esc_html__( 'Extensions', 'squad-modules-for-divi' ),
			'whats_new'            => esc_html__( "What's New", 'squad-modules-for-divi' ),
			'settings'             => esc_html__( 'Settings', 'squad-modules-for-divi' ),
			'support'              => esc_html__( 'Support', 'squad-modules-for-divi' ),
			'documentation'        => esc_html__( 'Documentation', 'squad-modules-for-divi' ),

			// Actions.
			'upgrade'              => esc_html__( 'Upgrade to Pro', 'squad-modules-for-divi' ),
			'upgrade_now'          => esc_html__( 'Upgrade Now', 'squad-modules-for-divi' ),
			'learn_more'           => esc_html__( 'Learn More', 'squad-modules-for-divi' ),
			'view_demo'            => esc_html__( 'View Demo', 'squad-modules-for-divi' ),
			'get_help'             => esc_html__( 'Get Help', 'squad-modules-for-divi' ),

			// Status Messages.
			'saving'               => esc_html__( 'Saving...', 'squad-modules-for-divi' ),
			'saved'                => esc_html__( 'Saved!', 'squad-modules-for-divi' ),
			'save_error'           => esc_html__( 'Error saving settings', 'squad-modules-for-divi' ),
			'loading'              => esc_html__( 'Loading...', 'squad-modules-for-divi' ),
			'processing'           => esc_html__( 'Processing...', 'squad-modules-for-divi' ),

			// Success Messages.
			'activation_success'   => esc_html__( 'Plugin activated successfully!', 'squad-modules-for-divi' ),
			'deactivation_success' => esc_html__( 'Plugin deactivated successfully!', 'squad-modules-for-divi' ),
			'settings_saved'       => esc_html__( 'Settings saved successfully!', 'squad-modules-for-divi' ),

			// Error Messages.
			'error_occurred'       => esc_html__( 'An error occurred', 'squad-modules-for-divi' ),
			'missing_required'     => esc_html__( 'Please fill in all required fields', 'squad-modules-for-divi' ),
			'invalid_data'         => esc_html__( 'Invalid data provided', 'squad-modules-for-divi' ),
			'network_error'        => esc_html__( 'Network error occurred', 'squad-modules-for-divi' ),

			// Confirmations.
			'confirm_delete'       => esc_html__( 'Are you sure you want to delete this?', 'squad-modules-for-divi' ),
			'confirm_reset'        => esc_html__( 'Are you sure you want to reset settings?', 'squad-modules-for-divi' ),
			'changes_lost'         => esc_html__( 'Changes will be lost if you leave this page', 'squad-modules-for-divi' ),

			// Misc.
			'pro_feature'          => esc_html__( 'Pro Feature', 'squad-modules-for-divi' ),
			'beta_feature'         => esc_html__( 'Beta Feature', 'squad-modules-for-divi' ),
			'coming_soon'          => esc_html__( 'Coming Soon', 'squad-modules-for-divi' ),
		);

		/**
		 * Filter localized strings
		 *
		 * @param array<string, string> $strings Localized strings
		 */
		return apply_filters( 'divi_squad_admin_strings', $strings );
	}
}
