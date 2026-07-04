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
				'admin_menus' => $this->get_admin_menus(),
				'premium'     => $this->get_premium_status(),
				'links'       => $this->get_admin_links(),
				'versions'    => $this->get_versions(),
				'checkout'    => $this->get_checkout_config(),
				'affiliate'   => $this->get_affiliate_data(),
				'tracking'    => $this->get_tracking_data(),
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
			if ( ! divi_squad()->requirements->is_fulfilled() ) {
				return array();
			}

			// Collect registered submenus.
			$submenus = divi_squad()->admin_menu->get_menu_items_for_localization();

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

		return str_repeat( '•', $length - 4 ) . substr( $key, -4 );
	}

	/**
	 * Get admin links
	 *
	 * @return array<string, string>
	 */
	private function get_admin_links(): array {
		try {
			$base_dashboard_url = admin_url( 'admin.php?page=divi_squad' );

			// Marketing/brand links carry a UTM so admin referrals are attributable.
			$utm        = '?utm_source=lite&utm_medium=plugin_admin&utm_campaign=admin_links';
			$wporg_slug = 'squad-modules-for-divi';

			$links = array(
				// In-app routes (hash router).
				'dashboard'     => $base_dashboard_url . '#/',
				'modules'       => $base_dashboard_url . '#/modules',
				'extensions'    => $base_dashboard_url . '#/extensions',
				'whats_new'     => $base_dashboard_url . '#/whats-new',
				'settings'      => $base_dashboard_url . '#/settings',

				// External brand + community + WordPress.org links consumed by the
				// React shell (dashboard quick actions, footer, what's-new, account).
				'docs'          => 'https://docs.squadmodules.com/' . $utm,
				'documentation' => 'https://docs.squadmodules.com/' . $utm,
				'changelog'     => 'https://squadmodules.com/changelog' . $utm,
				'support'       => "https://wordpress.org/support/plugin/{$wporg_slug}/",
				'review'        => "https://wordpress.org/support/plugin/{$wporg_slug}/reviews/#new-post",
				'community'     => 'https://www.facebook.com/groups/squadmodules/',
				'donate'        => 'https://squadmodules.com/' . $utm,
				'translate'     => "https://translate.wordpress.org/projects/wp-plugins/{$wporg_slug}/",
			);

			// Add Freemius-driven links in their own guard so an SDK change to any
			// single method can't discard the static links above (which is exactly
			// what happened when get_affiliation_url() was removed from the SDK).
			try {
				$fs = divi_squad_fs();

				// Account / upgrade / pricing routes are always surfaced so the React
				// shell can route into Freemius flows for both free and pro builds.
				$account_url         = $fs->get_account_url();
				$links['my_account'] = $account_url;
				$links['account']    = $account_url;
				$links['upgrade']    = $fs->get_upgrade_url();
				$links['pricing']    = $fs->pricing_url();

				// Affiliation page (available regardless of plan when the program is on).
				$links['affiliation'] = $fs->_get_admin_page_url( 'affiliation' );
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
	 * Affiliate program data for the Affiliation screen.
	 *
	 * The Freemius SDK only exposes the program TERMS (commission, cookie window,
	 * recurring flag) and the current user's application STATUS — it has no
	 * affiliate performance metrics (clicks/referrals/earnings live solely in the
	 * external Freemius affiliate dashboard), so none are localized here.
	 *
	 * Terms are public program data but the SDK only auto-fetches them on its own
	 * affiliation page; we fetch once via the plugin/bundle API scope (mirroring
	 * Freemius's private fetch_affiliate_terms) and cache the result for a day.
	 *
	 * @return array<string, mixed>
	 */
	private function get_affiliate_data(): array {
		$data = array(
			'has_program'   => false,
			'commission'    => '',
			'cookie_days'   => null,
			'has_renewals'  => false,
			'status'        => null,
			'is_active'     => false,
			'dashboard_url' => 'https://users.freemius.com/login',
		);

		try {
			$fs = divi_squad_fs();

			$data['has_program'] = (bool) $fs->has_affiliate_program();
			if ( ! $data['has_program'] ) {
				return $data;
			}

			$terms = $this->get_affiliate_terms( $fs );
			if ( is_object( $terms ) ) {
				$data['commission']   = $terms->get_formatted_commission();
				$data['cookie_days']  = $terms->cookie_days;
				$data['has_renewals'] = (bool) $terms->has_renewals_commission();
			}

			// Per-user application status (pending/active/rejected/suspended).
			$affiliate = $this->get_affiliate_record( $fs, $terms );
			if ( is_object( $affiliate ) ) {
				$data['status']    = $affiliate->status;
				$data['is_active'] = (bool) $affiliate->is_active();
			}
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to resolve affiliate data' );
		}

		return $data;
	}

	/**
	 * Resolve the current user's affiliate record (with status), cached for an
	 * hour. Mirrors the SDK's private fetch_affiliate_and_custom_terms user-scope
	 * call so the status is available outside the Freemius affiliation page. Only
	 * runs for registered (opted-in) users; anonymous installs have no record.
	 *
	 * @param mixed                   $fs    The Freemius instance.
	 * @param \FS_AffiliateTerms|null $terms The resolved affiliate terms.
	 *
	 * @return \FS_Affiliate|null
	 */
	private function get_affiliate_record( $fs, $terms ) {
		// Cheap getter first — populated when the SDK already loaded it.
		$affiliate = $fs->get_affiliate();
		if ( is_object( $affiliate ) ) {
			return $affiliate;
		}

		if ( ! $fs->is_registered( true ) || ! is_object( $terms ) ) {
			return null;
		}

		$cache_key = 'divi_squad_affiliate_record';
		$cached    = get_transient( $cache_key );
		if ( $cached instanceof \FS_Affiliate ) {
			return $cached;
		}
		if ( 'none' === $cached ) {
			return null;
		}

		try {
			$result = $fs->get_api_user_scope()->get(
				sprintf( '/plugins/%s/aff/%s/affiliates.json', $fs->get_id(), $terms->id ),
				false
			);

			if ( is_object( $result ) && isset( $result->affiliates ) && is_array( $result->affiliates ) && array() !== $result->affiliates ) {
				$affiliate = new \FS_Affiliate( $result->affiliates[0] );
				set_transient( $cache_key, $affiliate, HOUR_IN_SECONDS );

				return $affiliate;
			}

			// Remember "no record" briefly so we don't refetch every load.
			set_transient( $cache_key, 'none', HOUR_IN_SECONDS );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to fetch affiliate record' );
		}

		return null;
	}

	/**
	 * Resolve the affiliate terms entity (cached), mirroring the SDK's private
	 * fetch_affiliate_terms so the data is available outside the Freemius
	 * affiliation page.
	 *
	 * @param mixed $fs The Freemius instance.
	 *
	 * @return \FS_AffiliateTerms|null
	 */
	private function get_affiliate_terms( $fs ) {
		// Cheap getter first — populated when already loaded this request.
		$terms = $fs->get_affiliate_terms();
		if ( is_object( $terms ) ) {
			return $terms;
		}

		$cache_key = 'divi_squad_affiliate_terms';
		$cached    = get_transient( $cache_key );
		if ( $cached instanceof \FS_AffiliateTerms ) {
			return $cached;
		}

		try {
			// Plugin scope reliably returns the affiliate terms; the bundle scope
			// can fatal when no bundle is configured, so avoid it here.
			$api = $fs->get_api_plugin_scope();
			$raw = $api->get( '/aff.json?type=affiliation', false );

			if ( is_object( $raw ) && isset( $raw->id ) && ! isset( $raw->error ) ) {
				$terms = new \FS_AffiliateTerms( $raw );
				set_transient( $cache_key, $terms, DAY_IN_SECONDS );

				return $terms;
			}
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to fetch affiliate terms' );
		}

		return null;
	}

	/**
	 * Anonymous usage tracking state for the Account screen opt-in toggle.
	 *
	 * @return array<string, bool>
	 */
	private function get_tracking_data(): array {
		try {
			$fs = divi_squad_fs();

			return array(
				// Whether the SDK is currently sending anonymous usage data.
				'allowed'    => (bool) $fs->is_tracking_allowed(),
				// The toggle only has effect once the user has opted in (registered).
				'can_toggle' => (bool) $fs->is_registered( true ),
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to resolve tracking state' );

			return array(
				'allowed'    => false,
				'can_toggle' => false,
			);
		}
	}

	/**
	 * Get plugin + detected Divi version for the app bar / hero.
	 *
	 * @return array<string, string>
	 */
	private function get_versions(): array {
		try {
			$versions = array(
				'plugin' => divi_squad()->get_version(),
				'divi'   => DiviUtil::get_builder_version(),
			);

			/**
			 * Filter the versions exposed to the admin app.
			 *
			 * @param array<string, string> $versions Plugin and Divi versions.
			 */
			return apply_filters( 'divi_squad_admin_versions', $versions );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to get admin versions' );

			return array(
				'plugin' => '',
				'divi'   => '',
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
