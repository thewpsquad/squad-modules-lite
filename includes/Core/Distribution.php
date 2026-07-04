<?php // phpcs:ignore WordPress.Files.FileName

/**
 * The publisher connection class
 *
 * @since   1.0.0
 * @author  The WP Squad <support@squadmodules.com>
 * @package DiviSquad
 */

namespace DiviSquad\Core;

use DiviSquad\Core\Supports\Polyfills\Constant;
use DiviSquad\Core\Supports\Polyfills\Str;
use DiviSquad\SquadModules;
use Freemius;
use Throwable;
use function add_action;
use function apply_filters;
use function esc_html__;
use function fs_dynamic_init;
use function load_template;

/**
 * Distribution SDK integration class.
 *
 * @since   1.0.0
 * @package DiviSquad
 */
class Distribution {

	/**
	 * Store and retrieve the instance of publisher SDK
	 *
	 * @var Freemius
	 */
	private Freemius $fs;

	/**
	 * Whether the Distribution is initialized.
	 *
	 * @var bool
	 */
	private bool $is_initialized = false;

	/**
	 * Integration Constructor
	 */
	public function __construct() {
		// Initialize the Freemius SDK if possible.
		add_action( 'int', array( $this, 'initialize' ), 1 );
	}

	/**
	 * Initialize the Freemius SDK
	 *
	 * @return void Whether initialization was successful
	 */
	public function initialize(): void {
		try {
			// Include publisher SDK.
			$sdk_path = $this->get_sdk_start_file_path();
			if ( ! file_exists( $sdk_path ) ) {
				return;
			}

			require_once $sdk_path;

			/**
			 * Filter the Freemius SDK initialization arguments.
			 *
			 * @since 1.0.0
			 *
			 * @param array $fs_init_args The Freemius SDK initialization arguments.
			 *
			 * @return array The filtered Freemius SDK initialization arguments.
			 */
			$fs_init_args = apply_filters(
				'divi_squad_fs_init_args',
				array(
					'id'                  => '14784',
					'slug'                => 'squad-modules-for-divi',
					'premium_slug'        => 'squad-modules-pro-for-divi',
					'type'                => 'plugin',
					'public_key'          => 'pk_016b4bcadcf416ffec072540ef065',
					'is_premium'          => false,
					'premium_suffix'      => esc_html__( 'Pro', 'squad-modules-for-divi' ),
					'has_premium_version' => true,
					'has_addons'          => false,
					'has_paid_plans'      => true,
					'is_org_compliant'    => true,
					'has_affiliation'     => 'selected',
					'menu'                => array(
						'slug'       => 'divi_squad_dashboard',
						'first-path' => 'admin.php?page=divi_squad_dashboard',
						'contact'    => false,
					),
					'permission'          => array(
						'enable_anonymous'      => true,
						'anonymous_mode'        => 'skip',
						'is_anonymous'          => true,
						'is_pending_activation' => false,
						'is_disconnected'       => false,
					),
					'parallel_activation' => array(
						'enabled'                  => true,
						'premium_version_basename' => divi_squad()->get_pro_basename(),
					),
				)
			);

			// Create publisher SDK instance.
			$this->fs = fs_dynamic_init( $fs_init_args );

			// Set initialized flag.
			$this->is_initialized = true;

			// Set global reference for backward compatibility.
			global $divi_squad_fs;
			if ( ! isset( $divi_squad_fs ) ) {
				$divi_squad_fs = $this->fs;
			}

			// Initialize hooks and filters.
			$this->init_hooks();

			return;
		} catch ( Throwable $e ) {
			// Log error but don't throw - we'll handle initialization failure gracefully.
			divi_squad()->log_error( $e, 'Distribution initialization failed', false );

			return;
		}
	}

	/**
	 * Initialize hooks and filters
	 *
	 * @return void
	 */
	protected function init_hooks(): void {
		if ( ! $this->is_initialized ) {
			return;
		}

		// Set SDK to work in anonymous mode automatically.
		$this->fs->skip_connection();

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
		$this->fs->add_filter( 'enable_cpt_advanced_menu_logic', '__return_true' );
		$this->fs->add_filter( 'hide_account_tabs', '__return_true' );
		$this->fs->add_filter( 'deactivate_on_activation', '__return_false' );
		$this->fs->add_filter( 'show_deactivation_subscription_cancellation', '__return_false' );
		$this->fs->add_filter( 'is_submenu_visible', array( $this, 'fs_hook_is_submenu_visible' ), 10, 2 );
		$this->fs->add_filter( 'show_admin_notice', array( $this, 'fs_hook_show_admin_notice' ), 10, 2 );
		$this->fs->add_filter( 'plugin_icon', array( $this, 'fs_hook_plugin_icon' ) );
		$this->fs->add_filter( 'plugin_title', array( $this, 'fs_hook_plugin_title' ) );
		$this->fs->add_filter( 'plugin_version', array( $this, 'fs_hook_plugin_version' ) );
		$this->fs->add_filter( 'support_forum_url', array( $this, 'fs_hook_support_forum_url' ) );

		// Override the default templates.
		$this->fs->add_filter( '/forms/affiliation.php', array( $this, 'fs_hook_get_account_template' ) );
		$this->fs->add_filter( 'templates/account.php', array( $this, 'fs_hook_get_account_template' ) );
		$this->fs->add_filter( 'templates/connect.php', array( $this, 'fs_hook_get_default_template' ) );
		$this->fs->add_filter( 'templates/checkout.php', array( $this, 'fs_hook_get_default_template' ) );
		$this->fs->add_filter( 'templates/pricing.php', array( $this, 'fs_hook_get_default_template' ) );

		// Enqueue the plugin's scripts and styles files in the WordPress admin area.
		add_action( 'divi_squad_register_admin_assets', array( $this, 'register_scripts' ) );
		add_action( 'divi_squad_enqueue_admin_assets', array( $this, 'enqueue_scripts' ) );

		// Add filter to hide menu items when requirements aren't met.
		add_filter( 'divi_squad_publisher_is_submenu_visible', array( $this, 'maybe_disable_menu_items' ) );

		// Update the admin menu title.
		add_action( 'admin_menu', array( $this, 'wp_hook_update_admin_menu_title' ), Constant::PHP_INT_MAX );

		/**
		 * Initialize the plugin.
		 */
		do_action( 'divi_squad_publisher_init', $this );
	}

	/**
	 * Retrieve the instance of Freemius SDK
	 *
	 * @return Freemius The instance of Freemius SDK or null if not initialized.
	 */
	public function get_fs(): Freemius {
		if ( ! $this->is_initialized() ) {
			$this->initialize();
		}

		return $this->fs;
	}

	/**
	 * Check if the Distribution is properly initialized.
	 *
	 * @return bool Whether the Distribution is initialized.
	 */
	public function is_initialized(): bool {
		return $this->is_initialized;
	}

	/**
	 * Get the publisher start file path.
	 *
	 * @return string
	 */
	private function get_sdk_start_file_path(): string {
		return divi_squad()->get_path( '/freemius/start.php' );
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
		try {
			if ( ! $this->is_initialized ) {
				return $is_visible;
			}

			// Set default visibility for specific menu items.
			if ( 'support' === $menu_id ) {
				$is_visible = $this->fs->is_free_plan();
			}

			/**
			 * Filter whether the submenu item should be visible or not.
			 * This allows external code to override visibility for any menu item.
			 *
			 * @since 3.2.3
			 *
			 * @param bool   $is_visible The visibility value for this menu item.
			 * @param string $menu_id    The ID of the submenu item.
			 *
			 * @return bool If true, the menu item should be visible.
			 */
			return apply_filters( 'divi_squad_publisher_is_submenu_visible', $is_visible, $menu_id );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, sprintf( 'Failed to determine submenu visibility for menu ID: %s', $menu_id ) );

			return false; // Default to not showing when error occurs.
		}
	}

	/**
	 * Disables specific menu items when requirements aren't fulfilled.
	 *
	 * @param bool $is_visible The current visibility status of the menu item.
	 *
	 * @return bool Updated visibility status.
	 */
	public function maybe_disable_menu_items( bool $is_visible ): bool {
		// If we're already hiding the item, don't override that decision.
		if ( ! $is_visible ) {
			return false;
		}

		// Get requirements instance and check if fulfilled.
		return divi_squad()->requirements->is_fulfilled();
	}

	/**
	 * Update plugin icon url for opt-in screen,.
	 *
	 * @return string The src url of plugin icon.
	 */
	public function fs_hook_plugin_icon(): string {
		$default_icon = divi_squad()->get_path( '/build/admin/images/logos/divi-squad-default.png' );

		/**
		 * Filter the plugin icon url for opt-in screen.
		 *
		 * @since 3.2.3
		 *
		 * @param string $default_icon The default icon url.
		 *
		 * @return string The src url of plugin icon.
		 */
		return apply_filters( 'divi_squad_publisher_plugin_icon', $default_icon );
	}

	/**
	 * Get the account template for Freemius screens.
	 *
	 * @param string $content The template content.
	 *
	 * @return string|false
	 */
	public function fs_hook_get_account_template( string $content ) {
		try {
			ob_start();

			$template = divi_squad()->get_template_path( 'admin/publisher/account.php' );

			/**
			 * Filter the account template path.
			 *
			 * @since 3.3.0
			 *
			 * @param string $template The template path.
			 */
			$template = apply_filters( 'divi_squad_publisher_account_template', $template );

			// Load the template.
			load_template( $template, true, $content );

			return ob_get_clean();
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to load account template content' );

			return $content; // Return original content as fallback.
		}
	}

	/**
	 * Get the default template for Freemius screens.
	 *
	 * @param string $content The template content.
	 *
	 * @return string|false
	 */
	public function fs_hook_get_default_template( string $content ) {
		try {
			ob_start();

			$template = divi_squad()->get_template_path( 'admin/publisher/default.php' );

			/**
			 * Filter the default template path.
			 *
			 * @since 3.3.0
			 *
			 * @param string $template The template path.
			 */
			$template = apply_filters( 'divi_squad_publisher_default_template', $template );

			// Load the template.
			load_template( $template, true, $content );

			return ob_get_clean();
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to load default template content' );

			return $content; // Return original content as fallback.
		}
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
		$plugin_id = divi_squad()->get_name();

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
		/**
		 * Filter the plugin title based on free and pro plugin.
		 *
		 * @since 3.2.3
		 *
		 * @param string       $title  The plugin title.
		 * @param Freemius     $fs     The instance of Freemius SDK.
		 * @param SquadModules $plugin The plugin instance.
		 *
		 * @return string The activated plugin title between free and pro
		 */
		return apply_filters( 'divi_squad_publisher_plugin_title', $title, $this->fs, divi_squad() );
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
		/**
		 * Filter the plugin version based on free and pro plugin.
		 *
		 * @since 3.2.3
		 *
		 * @param string       $version The plugin version.
		 * @param Freemius     $fs      The instance of Freemius SDK.
		 * @param SquadModules $plugin  The plugin instance.
		 *
		 * @return string The activated plugin title between free and pro
		 */
		return apply_filters( 'divi_squad_publisher_plugin_version', $version, $this->fs, divi_squad() );
	}

	/**
	 * Modify the support forum url based on free and pro plugin
	 *
	 * @since  3.3.0
	 *
	 * @param string $url The support forum url.
	 *
	 * @return string The activated plugin title between free and pro
	 */
	public function fs_hook_support_forum_url( string $url ): string {
		/**
		 * Filter the support forum url.
		 *
		 * @since 3.3.0
		 *
		 * @param string       $url    The support forum url.
		 * @param Freemius     $fs     The instance of Freemius SDK.
		 * @param SquadModules $plugin The plugin instance.
		 *
		 * @return string The activated plugin title between free and pro
		 */
		return apply_filters( 'divi_squad_publisher_support_forum_url', $url, $this->fs, divi_squad() );
	}

	/**
	 * Register the plugin's scripts and styles files in the WordPress admin area.
	 *
	 * @param Assets $assets The assets manager instance.
	 *
	 * @return void
	 */
	public function register_scripts( Assets $assets ): void {
		$assets->register_style(
			'publisher',
			array(
				'file' => 'publisher',
				'path' => 'admin',
				'deps' => array(),
				'ext'  => 'css',
			)
		);
	}

	/**
	 * Enqueue the plugin's scripts and styles files in the WordPress admin area.
	 *
	 * @param Assets $assets The assets manager instance.
	 *
	 * @return void
	 */
	public function enqueue_scripts( Assets $assets ): void {
		$assets->add_body_class( 'publisher' );
		$assets->enqueue_style( 'publisher' );
	}

	/**
	 * Update the admin menu title.
	 *
	 * @since 3.3.0
	 *
	 * @return void
	 */
	public function wp_hook_update_admin_menu_title(): void {
		try {
			global $submenu;

			if ( ! is_array( $submenu ) || ! isset( $submenu['divi_squad_dashboard'] ) ) {
				return;
			}

			foreach ( $submenu as $parent => $sub ) {
				// Check if the parent is the desired one.
				if ( 'divi_squad_dashboard' !== $parent ) {
					continue;
				}

				foreach ( $sub as $index => $data ) {
					if ( isset( $data[2], $data[3] ) && Str::starts_with( $data[2], 'divi_squad_dashboard-' ) ) {
						if ( 'divi_squad_dashboard-affiliation' === $data[2] ) {
							$submenu[ $parent ][ $index ][3] = sprintf( // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
								'%s ‹ %s',
								esc_html__( 'Divi Squad', 'squad-modules-for-divi' ),
								esc_html__( 'Affiliation', 'squad-modules-for-divi' )
							);
						}

						if ( 'divi_squad_dashboard-account' === $data[2] ) {
							$submenu[ $parent ][ $index ][3] = sprintf( // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
								'%s ‹ %s',
								esc_html__( 'Divi Squad', 'squad-modules-for-divi' ),
								esc_html__( 'Account', 'squad-modules-for-divi' )
							);
						}

						if ( 'divi_squad_dashboard-wp-support-forum' === $data[2] ) {
							$submenu[ $parent ][ $index ][3] = sprintf( // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
								'%s ‹ %s',
								esc_html__( 'Divi Squad', 'squad-modules-for-divi' ),
								esc_html__( 'Support Forum', 'squad-modules-for-divi' )
							);
						}

						if ( 'divi_squad_dashboard-pricing' === $data[2] ) {
							$submenu[ $parent ][ $index ][3] = sprintf( // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
								'%s ‹ %s',
								esc_html__( 'Divi Squad', 'squad-modules-for-divi' ),
								esc_html__( 'Pricing', 'squad-modules-for-divi' )
							);
						}

						/**
						 * Filter the admin menu title.
						 *
						 * @since 3.3.0
						 *
						 * @param string $title   The admin menu title.
						 * @param array  $data    The submenu data.
						 * @param int    $index   The index of the submenu.
						 * @param string $parent  The parent menu slug.
						 * @param array  $submenu The submenu array.
						 */
						do_action( 'divi_squad_dashboard_update_admin_menu_title', $submenu[ $parent ][ $index ], $data, $index, $parent, $submenu );
					}
				}

				/**
				 * Filter the admin menu title.
				 *
				 * @since 3.3.0
				 *
				 * @param array $data The submenu data.
				 */
				do_action( 'divi_squad_dashboard_update_admin_menu_title_after', $submenu[ $parent ] );
			}
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to update admin menu titles' );
		}
	}
}
