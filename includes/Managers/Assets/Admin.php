<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Admin Asset Management for DiviSquad.
 *
 * This file contains the Admin class which handles the registration and enqueuing
 * of scripts and styles for the DiviSquad plugin's admin area.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   3.0.0
 */

namespace DiviSquad\Managers\Assets;

use DiviSquad\Base\Factories\AdminMenu as AdminMenuFactory;
use DiviSquad\Base\Factories\PluginAsset\Asset;
use DiviSquad\Base\Factories\RestRoute as RestRouteFactory;
use DiviSquad\Managers\Notices\Discount;
use DiviSquad\Utils\Asset as AssetUtil;
use DiviSquad\Utils\Divi as DiviUtil;
use DiviSquad\Utils\Helper as HelperUtil;
use DiviSquad\Utils\WP as WpUtil;
use Exception;
use Freemius;

/**
 * Admin class for managing admin-related assets and localization.
 *
 * This class is responsible for registering and enqueuing scripts and styles
 * for the DiviSquad plugin's admin area, as well as preparing localized data
 * for use in JavaScript.
 *
 * @since   3.0.0
 * @package DiviSquad
 */
class Admin extends Asset {

	/**
	 * Enqueue scripts, styles, and other assets in the WordPress admin area.
	 *
	 * This method is the main entry point for enqueueing admin-specific assets.
	 * It checks if the current context is admin and delegates to specific methods
	 * for enqueueing scripts and styles.
	 *
	 * @since 3.0.0
	 *
	 * @param string $type        The type of the script. Default is 'frontend'.
	 * @param string $hook_suffix The hook suffix for the current admin page.
	 */
	public function enqueue_scripts( $type = 'frontend', $hook_suffix = '' ) {
		if ( 'admin' !== $type ) {
			return;
		}

		/**
		 * Fires before admin scripts are enqueued.
		 *
		 * @since 3.0.0
		 *
		 * @param string $hook_suffix The current admin page hook suffix.
		 */
		do_action( 'divi_squad_before_enqueue_admin_scripts', $hook_suffix );

		try {
			$this->enqueue_admin_scripts( $hook_suffix );
		} catch ( Exception $e ) {
			divi_squad()->log_error( $e, 'Error enqueuing admin scripts.' );
		}

		/**
		 * Fires after admin scripts are enqueued.
		 *
		 * @since 3.0.0
		 *
		 * @param string $hook_suffix The current admin page hook suffix.
		 */
		do_action( 'divi_squad_after_enqueue_admin_scripts', $hook_suffix );
	}

	/**
	 * Localize script data for use in JavaScript.
	 *
	 * This method prepares data to be localized and made available to JavaScript
	 * in the admin area. It combines common data with admin-specific data.
	 *
	 * @since 3.0.0
	 *
	 * @param string       $type The type of the localize data. Default is 'raw'.
	 * @param string|array $data The data to localize.
	 * @return string|array The localized data.
	 * @throws Exception
	 */
	public function get_localize_data( $type = 'raw', $data = array() ) {
		if ( 'raw' === $type ) {
			$data = $this->get_common_localize_data( $data );
			$data = $this->get_admin_localize_data( $data );
		}

		/**
		 * Filters the admin localized script data.
		 *
		 * @since 3.0.0
		 *
		 * @param string|array $data The localized data.
		 * @param string       $type The type of the localize data.
		 */
		return apply_filters( 'divi_squad_admin_localize_script_data', $data, $type );
	}

	/**
	 * Enqueue the plugin's scripts and styles files in the WordPress admin area.
	 *
	 * This method handles the enqueuing of both common admin assets and
	 * Squad-specific assets when on a Squad admin page.
	 *
	 * @since 3.0.0
	 *
	 * @param string $hook_suffix Hook suffix for the current admin page.
	 */
	protected function enqueue_admin_scripts( string $hook_suffix ) {
		$this->enqueue_common_admin_assets();

		if ( HelperUtil::is_squad_page( $hook_suffix ) ) {
			$this->enqueue_squad_page_assets();
		}

		/**
		 * Fires after admin-specific scripts are enqueued.
		 *
		 * @since 3.0.0
		 *
		 * @param string $hook_suffix The current admin page hook suffix.
		 */
		do_action( 'divi_squad_after_enqueue_admin_specific_scripts', $hook_suffix );
	}

	/**
	 * Enqueue common admin assets.
	 *
	 * This method enqueues scripts and styles that are common to all admin pages.
	 *
	 * @since 3.0.0
	 */
	protected function enqueue_common_admin_assets() {
		AssetUtil::enqueue_script( 'admin-common', AssetUtil::admin_asset_path( 'admin-common' ), array( 'jquery', 'wp-api-fetch' ) );
		AssetUtil::enqueue_style( 'admin-common', AssetUtil::admin_asset_path( 'admin-common', array( 'ext' => 'css' ) ) );
	}

	/**
	 * Enqueue assets specific to Squad pages.
	 *
	 * This method enqueues scripts and styles that are specific to Squad admin pages.
	 *
	 * @since 3.0.0
	 */
	protected function enqueue_squad_page_assets() {
		$admin_deps = array( 'lodash', 'react', 'react-dom', 'react-jsx-runtime', 'wp-api-fetch', 'wp-components', 'wp-dom-ready', 'wp-element', 'wp-i18n' );

		AssetUtil::enqueue_style( 'admin-components', AssetUtil::admin_asset_path( 'admin-components', array( 'ext' => 'css' ) ) );
		AssetUtil::enqueue_script( 'admin', AssetUtil::admin_asset_path( 'admin' ), $admin_deps );
		AssetUtil::enqueue_style( 'admin', AssetUtil::admin_asset_path( 'admin', array( 'ext' => 'css' ) ) );

		WpUtil::set_script_translations( 'squad-admin', divi_squad()->get_name() );
	}

	/**
	 * Get common localize data for admin area.
	 *
	 * This method prepares common data to be localized for use in JavaScript,
	 * including AJAX URL, asset URL, and REST API routes.
	 *
	 * @since 3.0.0
	 *
	 * @param array $existing_data Existing extra data.
	 *
	 * @return array Combined localized data.
	 */
	protected function get_common_localize_data( array $existing_data ): array {
		if ( ! is_admin() && ! ( DiviUtil::is_fb_enabled() && is_user_logged_in() ) ) {
			return $existing_data;
		}

		$product_name = divi_squad()->get_name();

		$rest_register     = RestRouteFactory::get_instance();
		$admin_rest_routes = array();

		if ( $rest_register instanceof RestRouteFactory ) {
			$admin_rest_routes = array(
				'rest_api_wp' => array(
					'route'     => get_rest_url(),
					'namespace' => $rest_register->get_namespace( $product_name ),
					'routes'    => $rest_register->get_registered_routes( $product_name ),
				),
			);
		}

		$defaults = array(
			'ajax_url'   => admin_url( 'admin-ajax.php' ),
			'assets_url' => divi_squad()->get_asset_url(),
		);

		$data = array_merge_recursive( $defaults, $existing_data, $admin_rest_routes );

		/**
		 * Filters the common admin localized data.
		 *
		 * @since 3.0.0
		 *
		 * @param array $data The common localized data.
		 */
		return apply_filters( 'divi_squad_common_admin_localize_data', $data );
	}

	/**
	 * Get admin-specific localize data.
	 *
	 * This method prepares admin-specific data to be localized for use in JavaScript,
	 * including version information, admin menus, premium status, links, and more.
	 *
	 * @since 3.0.0
	 *
	 * @param array $existing_data Existing extra data.
	 *
	 * @return array Combined localized data.
	 * @throws Exception
	 */
	protected function get_admin_localize_data( array $existing_data ): array {
		if ( ! $this->is_valid_squad_page() ) {
			return $existing_data;
		}

		$admin_localize = array(
			'version_wp_current' => divi_squad()->get_version(),
			'version_wp_real'    => divi_squad()->get_version_dot(),
			'admin_menus'        => $this->get_admin_menus(),
			'premium'            => $this->get_premium_status(),
			'links'              => $this->get_admin_links(),
			'l10n'               => $this->get_localized_strings(),
			'plugins'            => WpUtil::get_active_plugins(),
			'notices'            => array(
				'has_welcome' => ( new Discount() )->can_render_it(),
			),
		);

		$data = array_merge_recursive( $existing_data, $admin_localize );

		/**
		 * Filters the admin-specific localized data.
		 *
		 * @since 3.0.0
		 *
		 * @param array $data The admin-specific localized data.
		 */
		return apply_filters( 'divi_squad_admin_specific_localize_data', $data );
	}

	/**
	 * Check if the current page is a valid Squad page.
	 *
	 * This method determines if the current admin page is a Squad-specific page.
	 *
	 * @since 3.0.0
	 *
	 * @return bool True if it's a valid Squad page, false otherwise.
	 */
	protected function is_valid_squad_page(): bool {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$screen = get_current_screen();
		return $screen instanceof \WP_Screen && HelperUtil::is_squad_page( $screen->id );
	}

	/**
	 * Get registered admin menus.
	 *
	 * This method retrieves the registered admin submenus for the Squad plugin.
	 *
	 * @since 3.0.0
	 *
	 * @return array An array of registered admin submenus.
	 */
	protected function get_admin_menus(): array {
		$menu_register = AdminMenuFactory::get_instance();
		return $menu_register instanceof AdminMenuFactory ? $menu_register->get_registered_submenus() : array();
	}

	/**
	 * Get premium status information.
	 *
	 * This method checks and returns information about the premium status of the plugin.
	 *
	 * @since 3.0.0
	 *
	 * @return array An array containing premium status information.
	 * @throws Exception
	 */
	protected function get_premium_status(): array {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$installed_plugins = array_keys( get_plugins() );
		$pro_basename      = divi_squad()->get_pro_basename();

		return array(
			'is_active'    => divi_squad_fs() instanceof Freemius && divi_squad_fs()->can_use_premium_code(),
			'is_installed' => in_array( $pro_basename, $installed_plugins, true ),
		);
	}

	/**
	 * Get admin links.
	 *
	 * This method prepares an array of important admin links for the Squad plugin.
	 *
	 * @since 3.0.0
	 *
	 * @return array An array of admin links.
	 * @throws Exception
	 */
	protected function get_admin_links(): array {
		return array(
			'site_url'   => home_url( '/' ),
			'my_account' => divi_squad_fs() instanceof Freemius ? divi_squad_fs()->get_account_url() : '',
			'plugins'    => admin_url( 'plugins.php' ),
			'dashboard'  => admin_url( 'admin.php?page=divi_squad_dashboard#/' ),
			'modules'    => admin_url( 'admin.php?page=divi_squad_dashboard#/modules' ),
			'extensions' => admin_url( 'admin.php?page=divi_squad_dashboard#/extensions' ),
			'whats_new'  => admin_url( 'admin.php?page=divi_squad_dashboard#/whats-new' ),
		);
	}

	/**
	 * Get localized strings.
	 *
	 * This method prepares an array of localized strings for use in JavaScript.
	 *
	 * @since 3.0.0
	 *
	 * @return array An array of localized strings.
	 */
	protected function get_localized_strings(): array {
		return array(
			'dashboard'  => esc_html__( 'Dashboard', 'squad-modules-for-divi' ),
			'modules'    => esc_html__( 'Modules', 'squad-modules-for-divi' ),
			'extensions' => esc_html__( 'Extension', 'squad-modules-for-divi' ),
			'whats_new'  => esc_html__( 'What\'s New', 'squad-modules-for-divi' ),
		);
	}
}
