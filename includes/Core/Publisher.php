<?php
/**
 * The Freemius connection class
 *
 * @since   1.0.0
 * @author  The WP Squad <support@squadmodules.com>
 * @package DiviSquad
 */

namespace DiviSquad\Core;

use _WP_Dependency;
use DiviSquad\SquadModules;
use DiviSquad\Utils\Asset;
use DiviSquad\Utils\Helper;
use DiviSquad\Core\Supports\Polyfills\Constant;
use DiviSquad\Core\Supports\Polyfills\Str;
use DiviSquad\Utils\WP as WpUtils;
use Freemius;
use Freemius_Exception;
use WP_Screen;
use WP_Scripts;
use WP_Styles;
use function add_action;
use function apply_filters;
use function esc_html__;
use function fs_dynamic_init;
use function get_plugin_data;
use function load_template;
use function remove_all_actions;

/**
 * Freemius SDK integration class.
 *
 * @since   1.0.0
 * @package DiviSquad
 */
final class Publisher {

	/**
	 * Store and retrieve the instance of Freemius SDK
	 *
	 * @var Freemius The instance of Freemius SDK.
	 */
	private Freemius $fs;

	/**
	 * The plugin instance.
	 *
	 * @var SquadModules The plugin instance.
	 */
	private SquadModules $plugin;

	/**
	 * Integration Constructor
	 *
	 * @throws Freemius_Exception Thrown when an API call returns an exception.
	 */
	public function __construct( SquadModules $plugin ) {
		$this->plugin = $plugin;

		// Include Freemius SDK.
		require_once $this->get_sdk_start_file_path();

		// Create Freemius SDK instance.
		$this->fs = fs_dynamic_init(
			array(
				'id'                  => 14784,
				'slug'                => 'squad-modules-for-divi',
				'premium_slug'        => 'squad-modules-pro-for-divi',
				'type'                => 'plugin',
				'public_key'          => 'pk_016b4bcadcf416ffec072540ef065',
				'is_premium'          => $this->plugin->is_pro_activated(),
				'premium_suffix'      => esc_html__( 'Pro', 'squad-modules-pro-for-divi' ),
				'has_premium_version' => true,
				'has_addons'          => false,
				'has_paid_plans'      => true,
				'has_affiliation'     => 'selected',
				'menu'                => array(
					'slug'        => 'divi_squad_dashboard',
					'first-path'  => 'admin.php?page=divi_squad_dashboard',
					'affiliation' => ! WpUtils::is_playground(),
				),
				'permission'          => array(
					'enable_anonymous' => true,
					'anonymous_mode'   => true,
				),
				'parallel_activation' => array(
					'enabled'                  => true,
					'premium_version_basename' => $this->plugin->get_pro_basename(),
				),
			)
		);

		// Initialize hooks and filters
		$this->init_hooks();
	}

	/**
	 * Initialize hooks and filters
	 */
	protected function init_hooks() {
		// Update some features.
		$this->fs->override_i18n(
			array(
				'hey'                         => esc_html__( 'Hey', 'squad-modules-for-divi' ),
				'yee-haw'                     => esc_html__( 'Hello Friend', 'squad-modules-for-divi' ),
				'skip'                        => esc_html__( 'Not today', 'squad-modules-for-divi' ),
				'opt-in-connect'              => esc_html__( "Yes - I'm in!", 'squad-modules-for-divi' ),
				'install-update-now'          => esc_html__( 'Update Now', 'squad-modules-for-divi' ),
				/* translators: %s: Plan title */
				'activate-x-features'         => esc_html__( 'Activate %s', 'squad-modules-for-divi' ),
				/* translators: %s The plugin name, example: Squad Modules Lite */
				'plugin-x-activation-message' => esc_html__( '%s was successfully activated.', 'squad-modules-for-divi' ),
				/* translators: %s The module type */
				'premium-activated-message'   => esc_html__( 'Premium %s was successfully activated.', 'squad-modules-for-divi' ),
				/* translators: %1$s: Product title; %2$s: Plan title; %3$s: Activation link */
				'activate-premium-version'    => esc_html__( ' The paid plugin of %1$s is already installed. Please activate it to start benefiting the %2$s plugin. %3$s', 'squad-modules-for-divi' ),
			)
		);
		$this->fs->add_filter( 'hide_account_tabs', '__return_true' );
		$this->fs->add_filter( 'deactivate_on_activation', '__return_false' );
		$this->fs->add_filter( 'show_deactivation_subscription_cancellation', '__return_false' );
		$this->fs->add_filter( 'is_submenu_visible', array( $this, 'fs_hook_is_submenu_visible' ), 10, 2 );
		$this->fs->add_filter( 'show_admin_notice', array( $this, 'fs_hook_show_admin_notice' ), 10, 2 );
		$this->fs->add_filter( 'plugin_icon', array( $this, 'fs_hook_plugin_icon' ) );
		$this->fs->add_filter( 'plugin_title', array( $this, 'fs_hook_plugin_title' ) );
		$this->fs->add_filter( 'plugin_version', array( $this, 'fs_hook_plugin_version' ) );
		$this->fs->add_filter( 'templates/connect.php', array( $this, 'fs_hook_get_default_template' ) );
		$this->fs->add_filter( 'templates/checkout.php', array( $this, 'fs_hook_get_default_template' ) );
		$this->fs->add_filter( 'templates/pricing.php', array( $this, 'fs_hook_get_default_template' ) );
		$this->fs->add_filter( 'templates/account.php', array( $this, 'fs_hook_get_account_template' ) );
		$this->fs->add_filter( '/forms/affiliation.php', array( $this, 'fs_hook_get_account_template' ) );

		// Enqueue the plugin's scripts and styles files in the WordPress admin area.
		add_action( 'admin_enqueue_scripts', array( $this, 'wp_hook_enqueue_scripts' ) );

		// Clean the third party dependencies from the squad template pages.
		add_action( 'admin_enqueue_scripts', array( $this, 'wp_hook_clean_third_party_deps' ), Constant::PHP_INT_MAX );
		add_action( 'admin_head', array( $this, 'wp_hook_clean_admin_content_section' ), Constant::PHP_INT_MAX );
	}

	/**
	 * Retrieve the instance of Freemius SDK
	 *
	 * @return Freemius The instance of Freemius SDK.
	 */
	public function get_fs(): Freemius {
		return $this->fs;
	}

	/**
	 * Get the status of Freemius sdk is installed or not.
	 *
	 * @return bool
	 */
	public function is_installed(): bool {
		return file_exists( $this->get_sdk_start_file_path() );
	}

	/**
	 * Get the Freemius start file path.
	 *
	 * @return string|bool
	 */
	private function get_sdk_start_file_path() {
		return realpath( $this->plugin->get_path( "/freemius/start.php" ) );
	}

	/**
	 * Show the contact submenu item only when the user has a valid non-expired license.
	 *
	 * @param bool   $is_visible The filtered value. Whether the submenu item should be visible or not.
	 * @param string $menu_id    The ID of the submenu item.
	 *
	 * @return bool If true, the menu item should be visible.
	 */
	public function fs_hook_is_submenu_visible( bool $is_visible, string $menu_id ): bool {
		if ( 'support' === $menu_id ) {
			return $this->fs->is_free_plan();
		}

		if ( 'contact' !== $menu_id ) {
			return $is_visible;
		}

		return false;
	}

	/**
	 * Update plugin icon url for opt-in screen,.
	 *
	 * @return string The src url of plugin icon.
	 */
	public function fs_hook_plugin_icon(): string {
		return $this->plugin->get_path( '/build/admin/images/logos/divi-squad-default.png' );
	}

	/**
	 * Get the account template path.
	 *
	 * @param array|string $content The template content.
	 *
	 * @return string
	 */
	public function fs_hook_get_account_template( $content ): string {
		ob_start();

		$account_template_path = sprintf( '%1$s/admin/publisher/account.php', $this->plugin->get_template_path() );
		load_template( $account_template_path, true, $content );

		return ob_get_clean();
	}

	/**
	 * Get the account template path.
	 *
	 * @param array|string $content The template content.
	 *
	 * @return string
	 */
	public function fs_hook_get_default_template( $content ): string {
		ob_start();

		$account_template_path = sprintf( '%1$s/admin/publisher/default.php', $this->plugin->get_template_path() );
		load_template( $account_template_path, true, $content );

		return ob_get_clean();
	}

	/**
	 * Control the visibility of admin notices.
	 *
	 * @param string $module_unique_affix Module's unique affix.
	 * @param mixed  $value               The value on which the filters hooked to `$tag` are applied on.
	 *
	 * @return bool The filtered value after all hooked functions are applied to it.
	 * @since  2.0.0
	 */
	public function fs_hook_show_admin_notice( string $module_unique_affix, $value ): bool {
		$notice_type = $value['type'] ?? '';
		$notice_id   = $value['id'] ?? '';
		$manager_id  = $value['manager_id'] ?? '';

		// Plugin id.
		$plugin_id = divi_squad()->get_textdomain();

		return ! ( ( 'update-nag' === $notice_type && $plugin_id === $manager_id ) || ( 'success' === $notice_type && 'plan_upgraded' === $notice_id ) );
	}

	/**
	 * Modify the plugin title based on free and pro plugin
	 *
	 * @since  2.0.0
	 *
	 * @param string $title The plugin title.
	 *
	 * @return string The activated plugin title between free and pro
	 */
	public function fs_hook_plugin_title( string $title ): string {
		if ( $this->fs->can_use_premium_code() && $this->plugin->is_pro_activated() ) {
			return esc_html__( 'Squad Modules Pro', 'squad-modules-for-divi' );
		}

		return $title;
	}

	/**
	 * Modify the plugin version based on free and pro plugin
	 *
	 * @since  2.0.0
	 *
	 * @param string $version The plugin version.
	 *
	 * @return string The activated plugin title between free and pro
	 */
	public function fs_hook_plugin_version( string $version ): string {
		if ( $this->plugin->is_pro_activated() && $this->fs->can_use_premium_code() ) {
			// Premium plugin basename.
			$pro_basename = $this->plugin->get_pro_basename();

			/**
			 * Retrieve the version of the premium plugin from its data.
			 *
			 * @see https://developer.wordpress.org/reference/functions/get_plugin_data/
			 */
			$path_root   = realpath( dirname( $this->plugin->get_path() ) );
			$plugin_data = get_plugin_data( "$path_root/$pro_basename" );

			return ! empty( $plugin_data['Version'] ) ? $plugin_data['Version'] : $version;
		}

		return $version;
	}

	/**
	 * Remove all notices from the squad template pages.
	 *
	 * @return void
	 */
	public function wp_hook_clean_admin_content_section(): void {
		// Check if the current screen is available.
		if ( ! function_exists( 'get_current_screen' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $screen instanceof WP_Screen && Helper::is_squad_page( $screen->id ) ) {
			remove_all_actions( 'admin_notices' );
			remove_all_actions( 'network_admin_notices' );
			remove_all_actions( 'all_admin_notices' );
			remove_all_actions( 'user_admin_notices' );
		}
	}

	/**
	 * Enqueue the plugin's scripts and styles files in the WordPress admin area.
	 *
	 * @param string $hook_suffix The current admin page.
	 *
	 * @return void
	 */
	public function wp_hook_enqueue_scripts( string $hook_suffix ): void {
		// Load plugin asset in the all admin pages.
		Asset::enqueue_style( 'admin-publisher', Asset::admin_asset_path( 'admin-publisher', array( 'ext' => 'css' ) ) );

		// Load special styles for freemius pages.
		if ( 'plugins.php' === $hook_suffix || Helper::is_squad_page( $hook_suffix ) ) {
			Asset::enqueue_style( 'admin-components', Asset::admin_asset_path( 'admin-components', array( 'ext' => 'css' ) ) );
			Asset::enqueue_style( 'admin', Asset::admin_asset_path( 'admin', array( 'ext' => 'css' ) ) );
		}
	}

	/**
	 * Remove all third party dependencies from the squad template pages.
	 *
	 * @return void
	 */
	public function wp_hook_clean_third_party_deps(): void {
		global $wp_scripts, $wp_styles;

		// Dequeue the scripts and styles of the current page those are not required.
		if ( Helper::is_squad_page() ) {
			$this->remove_unnecessary_dependencies( $wp_scripts );
			$this->remove_unnecessary_dependencies( $wp_styles );
		}
	}

	/**
	 * Remove unnecessary styles from the current page.
	 *
	 * @param WP_Scripts|WP_Styles $root The Core class of dependencies.
	 *
	 * @return void
	 */
	public function remove_unnecessary_dependencies( $root ): void {
		// get site url.
		$site_url = home_url( '/' );

		// Get the dependencies of the squad asset handles.
		$scripts_deps = $this->get_squad_dependencies( $root->registered );

		// Allowed plugin paths.
		$allowed_plugin_defaults = array( 'squad-modules', 'query-monitor', 'wp-console' );

		/**
		 * Allowed plugin paths.
		 *
		 * @param array $allowed_plugin_paths The allowed plugin paths.
		 *
		 * @return array
		 */
		$allowed_plugin_paths = apply_filters( 'divi_squad_dependencies_cleaning_allowed_plugin_paths', $allowed_plugin_defaults );

		/**
		 * Remove all the dependencies of the current page those are not required.
		 *
		 * @see https://developer.wordpress.org/reference/classes/wp_styles/
		 * @see https://developer.wordpress.org/reference/classes/wp_scripts/
		 */
		foreach ( $root->registered as $dependency ) {
			// Check if the dependency should be dequeued and removed.
			$should_remove = ! in_array( $dependency->handle, $scripts_deps, true ) && Str::starts_with( $dependency->src, $site_url );

			// Check allowed plugin paths.
			foreach ( $allowed_plugin_paths as $plugin_path ) {
				if ( Str::contains( $dependency->src, "wp-content/plugins/$plugin_path" ) ) {
					$should_remove = false;
					break;
				}
			}

			// Dequeue and remove the dependency if it should be removed.
			if ( $should_remove ) {
				$root->dequeue( $dependency->handle );
				$root->remove( $dependency->handle );
			}
		}
	}

	/**
	 * Get the dependencies of the squad scripts.
	 *
	 * @param _WP_Dependency[] $registered The registered scripts.
	 *
	 * @return array
	 */
	public function get_squad_dependencies( array $registered ): array {
		// Store the dependencies of the squad dependencies.
		$dependencies = array();

		// Get the dependencies of the squad asset handles.
		foreach ( $registered as $dependency ) {
			if ( Str::starts_with( $dependency->handle, 'squad-' ) && count( $dependency->deps ) > 0 ) {
				foreach ( $dependency->deps as $dep ) {
					if ( ! in_array( $dep, $dependencies, true ) ) {
						$dependencies[] = $dep;
					}
				}
			}
		}

		return $dependencies;
	}
}
