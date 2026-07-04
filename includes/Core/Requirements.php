<?php
/**
 * Requirements class.
 *
 * This file contains the Requirements class which handles the management
 * of Divi requirements for Squad Modules.
 *
 * @since   3.2.0
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Core;

use DiviSquad\Utils\Divi;
use DiviSquad\Utils\Helper;
use DiviSquad\Core\Supports\Media\Image;
use DiviSquad\Core\Supports\Polyfills\Constant;
use Exception;
use Throwable;
use WP_Screen;

/**
 * Class Requirements
 *
 * Handles the management of Divi requirements for Squad Modules.
 *
 * @since   3.2.0
 * @package DiviSquad
 */
class Requirements {

	/**
	 * Required Divi version.
	 *
	 * @var mixed
	 */
	private $required_version;

	/**
	 * Check if all Divi requirements are fulfilled.
	 *
	 * Performs a comprehensive check of:
	 * 1. Divi/Extra theme or Divi Builder plugin installation and activation
	 * 2. Version compatibility for Divi/Extra theme or Divi Builder plugin
	 *
	 * @since  3.2.0
	 * @access public
	 * @return bool True if all requirements are met, false otherwise.
	 */
	public function is_fulfilled(): bool {
		try {
			// Initialize requirements if not already set
			if ( ! isset( $this->required_version ) ) {
				$this->required_version = divi_squad()->get_option( 'RequiresDIVI', '4.14.0' );
			}

			// 1. Check Divi Installation Status
			$is_theme_installed  = Divi::is_any_divi_theme_installed();
			$is_plugin_installed = Divi::is_divi_builder_plugin_installed();

			if ( ! $is_theme_installed && ! $is_plugin_installed ) {
				throw new Exception( esc_html__( 'Divi theme or Divi Builder plugin is not installed.', 'squad-modules-for-divi' ) );
			}

			// 2. Check Divi Activation Status
			$is_theme_active  = Divi::is_any_divi_theme_active();
			$is_plugin_active = Divi::is_divi_builder_plugin_active();

			if ( ! $is_theme_active && ! $is_plugin_active ) {
				throw new Exception( esc_html__( 'Divi theme or Divi Builder plugin is not activated.', 'squad-modules-for-divi' ) );
			}

			// 3. Check Divi Theme Version (if active)
			if ( $is_theme_active && defined( 'ET_BUILDER_VERSION' ) ) {
				$meets_version = version_compare( (string) ET_BUILDER_VERSION, $this->required_version, '>=' );
				if ( ! $meets_version ) {
					throw new Exception( esc_html__( 'Divi theme version is less than required.', 'squad-modules-for-divi' ) );
				}
			}

			// 4. Check Divi Builder Plugin Version (if active)
			if ( $is_plugin_active && defined( 'ET_BUILDER_PLUGIN_VERSION' ) ) {
				$meets_version = version_compare( (string) ET_BUILDER_PLUGIN_VERSION, $this->required_version, '>=' );
				if ( ! $meets_version ) {
					throw new Exception( esc_html__( 'Divi Builder plugin version is less than required.', 'squad-modules-for-divi' ) );
				}
			}

			/**
			 * Filter the final requirements validation status.
			 *
			 * Allows other components to add their own validation checks
			 * or override the final validation result.
			 *
			 * @since 3.2.0
			 * @param bool         $is_valid     True if all requirements are met.
			 * @param Requirements $requirements Current Requirements instance.
			 */
			return apply_filters('divi_squad_is_builder_meet', true, $this );
		} catch ( Throwable $e ) {
			return false;
		}
	}

	/**
	 * Register the admin page.
	 *
	 * @since 3.2.0
	 *
	 * @return void
	 */
	public function register_pre_loaded_admin_page(): void {
		// Initialize requirements if not already set
		if ( ! isset( $this->required_version ) ) {
			$this->required_version = divi_squad()->get_option( 'RequiresDIVI', '4.14.0' );
		}

		add_action( 'admin_menu', array( $this, 'register_admin_page' ) );
		add_action( 'admin_head', array( $this, 'clean_admin_content_section' ), Constant::PHP_INT_MAX );
		add_action( 'divi_squad_menu_badges', array( $this, 'add_badges' ) );
	}

	/**
	 * Register the admin page.
	 *
	 * @since 3.2.0
	 *
	 * @return void
	 */
	public function register_admin_page(): void {
		// Load the image class.
		$image = new Image( divi_squad()->get_path( '/build/admin/images/logos' ) );

		// Get the menu icon.
		$menu_icon = $image->get_image( 'divi-squad-d-white.svg', 'svg' );
		if ( is_wp_error( $menu_icon ) ) {
			$menu_icon = 'dashicons-warning';
		}

		$page_slug  = divi_squad()->get_admin_menu_slug();
		$capability = 'manage_options';

		// Register the admin page.
		add_menu_page(
			__( 'Divi Squad', 'squad-modules-for-divi' ),
			__( 'Divi Squad', 'squad-modules-for-divi' ),
			$capability,
			$page_slug,
			'',
			$menu_icon,
			divi_squad()->get_admin_menu_position()
		);

		// Register the admin page.
		add_submenu_page(
			$page_slug,
			__( 'Requirements', 'squad-modules-for-divi' ),
			__( 'Requirements', 'squad-modules-for-divi' ),
			$capability,
			$page_slug,
			array( $this, 'render_admin_page' ),
		);
	}

	/**
	 * Remove all notices from the squad template pages.
	 *
	 * @since 3.2.0
	 *
	 * @return void
	 */
	public function clean_admin_content_section(): void {
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
	 * Render the admin page.
	 *
	 * @since 3.2.0
	 *
	 * @return void
	 */
	public function render_admin_page(): void {
		$template_path = sprintf( '%1$s/admin/requirements.php', divi_squad()->get_template_path() );

		// Get notice content
		$args = $this->get_notice_content();

		if ( file_exists( $template_path ) ) {
			load_template( $template_path, false, $args );
		}
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
				esc_html__( "current", "squad-modules-for-divi" )
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
	 * Get notice content based on Divi status
	 *
	 * @since 3.2.0
	 *
	 * @return string
	 */
	protected function get_notice_content(): string {
		// Case 1: Neither Divi theme nor Divi Builder plugin is installed
		if ( ! Divi::is_any_divi_theme_installed() && ! Divi::is_divi_builder_plugin_installed() ) {
			return sprintf(
				'<div class="notice divi-squad-banner divi-squad-error-banner">
                <div class="divi-squad-banner-content">
                    <h3>%s</h3>
                    <p>%s</p>
                    <div class="divi-squad-notice-action">
                        <div class="divi-squad-notice-action-left">
                            <a href="%s" target="_blank" class="button-primary divi-squad-notice-action-button">
                                <span class="dashicons dashicons-external"></span>
                                <p>%s</p>
                            </a>
                        </div>
                    </div>
                </div>
            </div>',
				esc_html__( 'Divi Not Installed', 'squad-modules-for-divi' ),
				esc_html__( 'Squad Modules requires either the Divi/Extra theme or Divi Builder plugin to be installed and activated. Please install Divi to use Squad Modules.', 'squad-modules-for-divi' ),
				esc_url( 'https://www.elegantthemes.com/gallery/divi/' ),
				esc_html__( 'Get Divi', 'squad-modules-for-divi' )
			);
		}

		// Case 2: Divi theme is installed but version is less than required
		if ( Divi::is_any_divi_theme_active() && defined( 'ET_BUILDER_VERSION' ) &&
			version_compare( (string) ET_BUILDER_VERSION, $this->required_version, '<' ) ) {
			return sprintf(
				'<div class="notice divi-squad-banner divi-squad-warning-banner">
                <div class="divi-squad-banner-content">
                    <h3>%s</h3>
                    <p>%s</p>
                    <p>%s</p>
                    <div class="divi-squad-notice-action">
                        <div class="divi-squad-notice-action-left">
                            <a href="%s" target="_blank" class="button-primary divi-squad-notice-action-button">
                                <span class="dashicons dashicons-update"></span>
                                <p>%s</p>
                            </a>
                        </div>
                    </div>
                </div>
            </div>',
				esc_html__( 'Divi Update Required', 'squad-modules-for-divi' ),
				sprintf(
				// translators: %s: Required Divi version
					esc_html__( 'Squad Modules requires Divi version %s or higher.', 'squad-modules-for-divi' ),
					$this->required_version
				),
				sprintf(
				// translators: %s: Current Divi version
					esc_html__( 'Your current Divi version is %s. Please update to continue using Squad Modules.', 'squad-modules-for-divi' ),
					ET_BUILDER_VERSION
				),
				esc_url( admin_url( 'themes.php' ) ),
				esc_html__( 'Update Divi', 'squad-modules-for-divi' )
			);
		}

		// Case 3: Divi Builder plugin is installed but version is less than required
		if ( Divi::is_divi_builder_plugin_active() && defined( 'ET_BUILDER_PLUGIN_VERSION' ) &&
			version_compare( (string) ET_BUILDER_PLUGIN_VERSION, $this->required_version, '<' ) ) {
			return sprintf(
				'<div class="notice divi-squad-banner divi-squad-warning-banner">
                <div class="divi-squad-banner-content">
                    <h3>%s</h3>
                    <p>%s</p>
                    <p>%s</p>
                    <div class="divi-squad-notice-action">
                        <div class="divi-squad-notice-action-left">
                            <a href="%s" target="_blank" class="button-primary divi-squad-notice-action-button">
                                <span class="dashicons dashicons-update"></span>
                                <p>%s</p>
                            </a>
                        </div>
                    </div>
                </div>
            </div>',
				esc_html__( 'Divi Builder Update Required', 'squad-modules-for-divi' ),
				sprintf(
				// translators: %s: Required Divi version
					esc_html__( 'Squad Modules requires Divi Builder version %s or higher.', 'squad-modules-for-divi' ),
					$this->required_version
				),
				sprintf(
				// translators: %s: Current Divi version
					esc_html__( 'Your current Divi Builder version is %s. Please update to continue using Squad Modules.', 'squad-modules-for-divi' ),
					ET_BUILDER_PLUGIN_VERSION
				),
				esc_url( admin_url( 'plugins.php' ) ),
				esc_html__( 'Update Divi Builder', 'squad-modules-for-divi' )
			);
		}

		// Case 4a: Divi theme is installed but not active
		if ( Divi::is_any_divi_theme_installed() && ! Divi::is_any_divi_theme_active() ) {
			return sprintf(
				'<div class="notice divi-squad-banner divi-squad-error-banner">
                <div class="divi-squad-banner-content">
                    <h3>%s</h3>
                    <p>%s</p>
                    <div class="divi-squad-notice-action">
                        <div class="divi-squad-notice-action-left">
                            <a href="%s" class="button-primary divi-squad-notice-action-button">
                                <span class="dashicons dashicons-admin-appearance"></span>
                                <p>%s</p>
                            </a>
                        </div>
                    </div>
                </div>
            </div>',
				esc_html__( 'Divi Theme Not Activated', 'squad-modules-for-divi' ),
				esc_html__( 'Squad Modules has detected that you have Divi theme installed but not activated. Please activate the Divi theme to use Squad Modules.', 'squad-modules-for-divi' ),
				esc_url( admin_url( 'themes.php' ) ),
				esc_html__( 'Activate Divi Theme', 'squad-modules-for-divi' )
			);
		}

		// Case 4b: Divi Builder plugin is installed but not active
		if ( Divi::is_divi_builder_plugin_installed() && ! Divi::is_divi_builder_plugin_active() ) {
			return sprintf(
				'<div class="notice divi-squad-banner divi-squad-error-banner">
                <div class="divi-squad-banner-content">
                    <h3>%s</h3>
                    <p>%s</p>
                    <div class="divi-squad-notice-action">
                        <div class="divi-squad-notice-action-left">
                            <a href="%s" class="button-primary divi-squad-notice-action-button">
                                <span class="dashicons dashicons-admin-plugins"></span>
                                <p>%s</p>
                            </a>
                        </div>
                    </div>
                </div>
            </div>',
				esc_html__( 'Divi Builder Plugin Not Activated', 'squad-modules-for-divi' ),
				esc_html__( 'Squad Modules has detected that you have the Divi Builder plugin installed but not activated. Please activate the Divi Builder plugin to use Squad Modules.', 'squad-modules-for-divi' ),
				esc_url( admin_url( 'plugins.php' ) ),
				esc_html__( 'Activate Divi Builder', 'squad-modules-for-divi' )
			);
		}

		// Case 5: All requirements are met
		return sprintf(
			'<div class="notice divi-squad-banner divi-squad-success-banner">
            <div class="divi-squad-banner-content">
                <h3>%s</h3>
                <p>%s</p>
            </div>
        </div>',
			esc_html__( 'Divi Requirements Met', 'squad-modules-for-divi' ),
			esc_html__( 'Your Divi installation meets all the requirements for Squad Modules. You can start using the modules now!', 'squad-modules-for-divi' )
		);
	}
}
