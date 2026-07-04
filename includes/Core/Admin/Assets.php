<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Admin Assets Manager
 *
 * Handles registration and enqueuing of admin-specific assets using the new unified asset system.
 *
 * @since   3.3.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Core\Admin;

use DiviSquad\Core\Assets as Assets_Manager;
use DiviSquad\Core\Contracts\Hookable;
use DiviSquad\Utils\Divi as DiviUtil;
use DiviSquad\Utils\Helper as HelperUtil;
use DiviSquad\Utils\WP as WPUtil;
use Throwable;

/**
 * Admin Assets Manager
 *
 * Handles registration and enqueuing of admin-specific assets using the new unified asset system.
 *
 * @since   3.3.0
 * @package DiviSquad
 */
class Assets implements Hookable {

	/**
	 * Initialize the assets manager.
	 */
	public function __construct() {
		$this->register_hooks();
	}

	/**
	 * Initialize admin assets
	 */
	public function register_hooks(): void {
		// Register and enqueue admin assets.
		add_action( 'divi_squad_register_admin_assets', array( $this, 'register' ) );
		add_action( 'divi_squad_enqueue_admin_assets', array( $this, 'enqueue' ) );

		// Add localize data.
		add_filter( 'divi_squad_global_localize_data', array( $this, 'add_localize_data' ) );
	}

	/**
	 * Register admin assets
	 *
	 * @param Assets_Manager $assets Assets Manager instance.
	 */
	public function register( Assets_Manager $assets ): void {
		try {
			$assets->register_style(
				'admin-menu',
				array(
					'file' => 'menu',
					'path' => 'admin',
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
				)
			);

			/**
			 * Fires after admin assets are registered
			 *
			 * @param Assets_Manager $assets Assets Manager instance
			 */
			do_action( 'divi_squad_after_register_admin_assets', $assets );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to register admin assets' );
		}
	}

	/**
	 * Enqueue admin assets
	 *
	 * @param Assets_Manager $assets Assets Manager instance.
	 */
	public function enqueue( Assets_Manager $assets ): void {
		try {
			// Always enqueue common assets.
			$assets->enqueue_style( 'admin-menu' );

			// Squad page specific assets.
			if ( HelperUtil::is_squad_page() ) {
				$assets->enqueue_script( 'admin-app' );
				$assets->enqueue_style( 'admin-app' );
			}

			/**
			 * Fires after admin assets are enqueued
			 *
			 * @param Assets_Manager $assets Assets Manager instance
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
				$global_data = array_merge_recursive( $global_data, $this->get_common_localize_data() );
			}

			// Add squad page specific data.
			if ( HelperUtil::is_squad_page() ) {
				$global_data = array_merge_recursive( $global_data, $this->get_admin_localize_data() );
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

		// Get all routes.
		$all_routes = divi_squad()->rest_routes->get_routes();

		// Group routes by version.
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

		// Add routes by version to the REST routes array.
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
				'premium'  => $this->get_premium_status(),
				'links'    => $this->get_admin_links(),
				'versions' => $this->get_versions(),
				'checkout' => $this->get_checkout_config(),
				'plugins'  => WPUtil::get_active_plugins(),
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
	 * Get premium status information
	 *
	 * @return array<string, mixed>
	 */
	private function get_premium_status(): array {
		try {
			$fs           = divi_squad_fs();
			$is_paying    = $fs->is_paying();
			$is_free_plan = $fs->is_free_plan();

			$plan_title = 'free';
			$plan       = $fs->get_plan();
			if ( is_object( $plan ) && isset( $plan->title ) && '' !== (string) $plan->title ) {
				$plan_title = (string) $plan->title;
			}

			$status = array(
				'is_installed' => divi_squad()->is_pro_installed(),
				'is_active'    => divi_squad()->is_pro_activated(),
				'has_license'  => $fs->has_active_valid_license(),
				'in_trial'     => $fs->is_trial(),
				'is_paying'    => $is_paying,
				'is_trial'     => $fs->is_trial(),
				'is_free_plan' => $is_free_plan,
				'plan_title'   => $plan_title,
				'license_key'  => '',
				'sites_used'   => 0,
				'sites_total'  => 0,
				'expiration'   => '',
			);

			// Only surface license/site/expiry detail when this is a paying build.
			// A free WP.org build is gated out of these branches entirely.
			if ( $is_paying && $fs->can_use_premium_code() ) {
				$license = method_exists( $fs, '_get_license' ) ? $fs->_get_license() : null;
				if ( is_object( $license ) ) {
					$secret = isset( $license->secret_key ) ? (string) $license->secret_key : '';
					if ( '' !== $secret ) {
						$status['license_key'] = $this->mask_license_key( $secret );
					}

					if ( isset( $license->activated ) ) {
						$status['sites_used'] = (int) $license->activated;
					}
					if ( isset( $license->quota ) ) {
						$status['sites_total'] = (int) $license->quota;
					}
					if ( isset( $license->expiration ) && '' !== (string) $license->expiration ) {
						$status['expiration'] = (string) $license->expiration;
					}
				}
			}

			/**
			 * Filter premium status data
			 *
			 * @param array<string, mixed> $status Premium status information
			 */
			return apply_filters( 'divi_squad_premium_status', $status );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to get premium status' );

			return array(
				'is_active'    => false,
				'is_installed' => false,
				'has_license'  => false,
				'in_trial'     => false,
				'is_paying'    => false,
				'is_trial'     => false,
				'is_free_plan' => true,
				'plan_title'   => 'free',
				'license_key'  => '',
				'sites_used'   => 0,
				'sites_total'  => 0,
				'expiration'   => '',
			);
		}
	}

	/**
	 * Mask a license key for display, keeping only the last 4 characters.
	 *
	 * @param string $key Raw license key.
	 *
	 * @return string Masked key, e.g. "••••••••••••cdef".
	 */
	private function mask_license_key( string $key ): string {
		$length = strlen( $key );
		if ( $length <= 4 ) {
			return str_repeat( '•', $length );
		}

		return str_repeat( '•', $length - 4 ) . substr( $key, - 4 );
	}

	/**
	 * Get admin links
	 *
	 * @return array<string, string>
	 */
	private function get_admin_links(): array {
		try {
			// Marketing/brand links carry a UTM so admin referrals are attributable.
			$utm = '?utm_source=lite&utm_medium=plugin_admin&utm_campaign=admin_links';

			// Brand links consumed by the React shell (what's-new, footer/account).
			$links = array(
				'documentation' => 'https://docs.squadmodules.com/' . $utm,
				'changelog'     => 'https://squadmodules.com/changelog' . $utm,
			);

			// Add Freemius-driven links in their own guard so an SDK change to any
			// single method can't discard the static links above (which is exactly
			// what happened when get_affiliation_url() was removed from the SDK).
			try {
				$fs = divi_squad_fs();

				// Account / upgrade / pricing routes are surfaced so the React shell
				// can route into Freemius flows for both free and pro builds.
				$links['account'] = $fs->get_account_url();
				$links['upgrade'] = $fs->get_upgrade_url();
				$links['pricing'] = $fs->pricing_url();
			} catch ( Throwable $fs_error ) {
				divi_squad()->log_error( $fs_error, 'Failed to resolve Freemius admin links' );
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
	 * Get the plugin version for the app bar / hero.
	 *
	 * @return array<string, string>
	 */
	private function get_versions(): array {
		try {
			$versions = array(
				'plugin' => divi_squad()->get_version_dot(),
			);

			/**
			 * Filter the versions exposed to the admin app.
			 *
			 * @param array<string, string> $versions Plugin version.
			 */
			return apply_filters( 'divi_squad_admin_versions', $versions );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to get admin versions' );

			return array(
				'plugin' => '',
			);
		}
	}

	/**
	 * Get Freemius checkout configuration for FS.Checkout.
	 *
	 * Exposes only the publishable Freemius identifiers (plugin_id + public_key)
	 * plus graceful-degradation fallback URLs. Plan IDs and prices come from the
	 * Freemius dashboard product config and are intentionally left empty here.
	 *
	 * @return array<string, mixed>
	 */
	private function get_checkout_config(): array {
		try {
			$fs = divi_squad_fs();

			// get_id()/get_public_key() are the publishable Freemius identifiers; the
			// surrounding try/catch returns the known fallback id if the SDK lacks them.
			$plugin_id  = (int) $fs->get_id();
			$public_key = (string) $fs->get_public_key();

			$config = array(
				'plugin_id'   => $plugin_id,
				'public_key'  => $public_key,
				// TODO-from-product-config: plan IDs live in the Freemius dashboard,
				// not in this repo. Until wired, FS.Checkout falls back to upgrade_url.
				'plan_ids'    => array(),
				'upgrade_url' => $fs->get_upgrade_url(),
				'pricing_url' => $fs->pricing_url(),
			);

			/**
			 * Filter the Freemius checkout config exposed to the admin app.
			 *
			 * Use this filter to inject `plan_ids` from product config when available.
			 *
			 * @param array<string, mixed> $config Checkout configuration.
			 */
			return apply_filters( 'divi_squad_admin_checkout_config', $config );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to get checkout config' );

			return array(
				'plugin_id'   => 14784,
				'public_key'  => '',
				'plan_ids'    => array(),
				'upgrade_url' => '',
				'pricing_url' => '',
			);
		}
	}
}
