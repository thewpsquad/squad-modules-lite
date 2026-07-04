<?php // phpcs:ignore WordPress.Files.FileName
/**
 * Requirements class.
 *
 * Handles system requirements validation for Squad Modules,
 * ensuring compatibility with Divi theme or Divi Builder plugin.
 *
 * @since   3.2.0
 * @package DiviSquad\Core
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Core;

use DiviSquad\SquadModules;
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
 * Manages Divi requirements validation and displays appropriate
 * notifications when requirements are not met.
 *
 * @since   3.2.0
 * @package DiviSquad\Core
 */
class Requirements {

	/**
	 * The plugin instance.
	 *
	 * @var SquadModules The plugin instance.
	 */
	private SquadModules $plugin;

	/**
	 * Required Divi version.
	 *
	 * @since 3.2.0
	 * @var string
	 */
	private string $required_version;

	/**
	 * Cached requirements status.
	 *
	 * @since 3.3.0
	 * @var array<string, boolean|string>
	 */
	private array $status = array();

	/**
	 * Error message from the last check.
	 *
	 * @since 3.3.0
	 * @var string
	 */
	private string $last_error = '';

	/**
	 * Constructor.
	 *
	 * Initialize hooks and filters.
	 *
	 * @since 3.3.0
	 *
	 * @param SquadModules $plugin The plugin instance.
	 */
	public function __construct( SquadModules $plugin ) {
		$this->plugin = $plugin;

		// Set the required version.
		$this->required_version = $this->plugin->get_option( 'RequiresDIVI', '4.14.0' );
	}

	/**
	 * Check if all Divi requirements are fulfilled.
	 *
	 * Performs a comprehensive check of:
	 * 1. Divi/Extra theme or Divi Builder plugin installation and activation
	 * 2. Version compatibility for Divi/Extra theme or Divi Builder plugin
	 *
	 * @since  3.2.0
	 * @access public
	 *
	 * @return bool True if all requirements are met, false otherwise.
	 */
	public function is_fulfilled(): bool {
		try {
			// Return cached result if available.
			if ( isset( $this->status['is_fulfilled'] ) ) {
				return (bool) $this->status['is_fulfilled'];
			}

			// Check installation status.
			if ( ! $this->is_divi_installed() ) {
				throw new \RuntimeException( esc_html__( 'Divi theme or Divi Builder plugin is not installed', 'squad-modules-for-divi' ) );
			}

			// Check activation status.
			if ( ! $this->is_divi_active() ) {
				throw new \RuntimeException( esc_html__( 'Divi theme or Divi Builder plugin is not activated', 'squad-modules-for-divi' ) );
			}

			// Check version compatibility.
			if ( ! $this->meets_version_requirements() ) {
				throw new \RuntimeException( esc_html__( 'Divi version is less than required', 'squad-modules-for-divi' ) );
			}

			/**
			 * Filter the final requirements validation status.
			 *
			 * @since 3.2.0
			 *
			 * @param bool         $is_valid     True if all requirements are met.
			 * @param Requirements $requirements Current Requirements instance.
			 */
			$is_fulfilled = apply_filters( 'divi_squad_is_builder_meet', true, $this );

			// Cache the result.
			$this->status['is_fulfilled'] = $is_fulfilled;

			return $is_fulfilled;
		} catch ( Throwable $e ) {
			$this->last_error = $e->getMessage();

			$this->status['is_fulfilled'] = false;
			return false;
		}
	}

	/**
	 * Check if Divi theme or Divi Builder plugin is installed.
	 *
	 * @since 3.3.0
	 * @access protected
	 *
	 * @return bool True if either Divi theme or Divi Builder plugin is installed.
	 */
	protected function is_divi_installed(): bool {
		if ( ! isset( $this->status['is_installed'] ) ) {
			$is_theme_installed  = Divi::is_any_divi_theme_installed();
			$is_plugin_installed = Divi::is_divi_builder_plugin_installed();

			$this->status['is_installed']        = $is_theme_installed || $is_plugin_installed;
			$this->status['is_theme_installed']  = $is_theme_installed;
			$this->status['is_plugin_installed'] = $is_plugin_installed;

			if ( $is_theme_installed ) {
				$this->status['theme_version'] = defined( 'ET_CORE_VERSION' ) ? ET_CORE_VERSION : '0.0.0';
			}

			if ( $is_plugin_installed ) {
				$this->status['plugin_version'] = defined( 'ET_BUILDER_PLUGIN_VERSION' ) ? ET_BUILDER_PLUGIN_VERSION : '0.0.0';
			}
		}

		return $this->status['is_installed']; // @phpstan-ignore-line
	}

	/**
	 * Check if Divi theme or Divi Builder plugin is active.
	 *
	 * @since 3.3.0
	 * @access protected
	 *
	 * @return bool True if either Divi theme or Divi Builder plugin is active.
	 */
	protected function is_divi_active(): bool {
		if ( ! isset( $this->status['is_active'] ) ) {
			$is_theme_active  = Divi::is_any_divi_theme_active();
			$is_plugin_active = Divi::is_divi_builder_plugin_active();

			$this->status['is_active']        = $is_theme_active || $is_plugin_active;
			$this->status['is_theme_active']  = $is_theme_active;
			$this->status['is_plugin_active'] = $is_plugin_active;
		}

		return $this->status['is_active']; // @phpstan-ignore-line
	}

	/**
	 * Check if the active Divi version meets the minimum requirements.
	 *
	 * @since 3.3.0
	 * @access protected
	 *
	 * @return bool True if version requirements are met.
	 */
	protected function meets_version_requirements(): bool {
		if ( ! isset( $this->status['meets_version'] ) ) {
			$meets_version = false;

			// Check Divi theme version (if active).
			if ( ( $this->status['is_theme_active'] ?? false ) && defined( 'ET_CORE_VERSION' ) ) { // @phpstan-ignore-line
				$meets_version                 = version_compare( (string) ET_CORE_VERSION, $this->required_version, '>=' );
				$this->status['theme_version'] = ET_CORE_VERSION;
			}

			// Check Divi Builder plugin version (if active).
			if ( ! $meets_version && ( $this->status['is_plugin_active'] ?? false ) && defined( 'ET_BUILDER_PLUGIN_VERSION' ) ) { // @phpstan-ignore-line
				$meets_version                  = version_compare( (string) ET_BUILDER_PLUGIN_VERSION, $this->required_version, '>=' );
				$this->status['plugin_version'] = ET_BUILDER_PLUGIN_VERSION;
			}

			$this->status['meets_version'] = $meets_version;
		}

		return (bool) $this->status['meets_version'];
	}

	/**
	 * Get the last error message from requirements check.
	 *
	 * @since 3.3.0
	 * @access public
	 *
	 * @return string The last error message.
	 */
	public function get_last_error(): string {
		return $this->last_error;
	}

	/**
	 * Register the admin page.
	 *
	 * @since 3.2.0
	 * @access public
	 *
	 * @return void
	 */
	public function register_pre_loaded_admin_page(): void {
		add_action( 'admin_menu', array( $this, 'register_admin_page' ) );
		add_action( 'admin_head', array( $this, 'clean_admin_content_section' ), Constant::PHP_INT_MAX );
		add_action( 'divi_squad_menu_badges', array( $this, 'add_badges' ) );
	}

	/**
	 * Register the admin page.
	 *
	 * @since 3.2.0
	 * @access public
	 *
	 * @return void
	 */
	public function register_admin_page(): void {
		// Load the image class.
		$image = new Image( $this->plugin->get_path( '/build/admin/images/logos' ) );

		// Get the menu icon.
		$menu_icon = $image->get_image( 'divi-squad-d-white.svg', 'svg' );
		if ( is_wp_error( $menu_icon ) ) {
			$menu_icon = 'dashicons-warning';
		}

		$page_slug  = $this->plugin->get_admin_menu_slug();
		$capability = 'manage_options';

		// Register the admin page.
		add_menu_page(
			__( 'Divi Squad', 'squad-modules-for-divi' ),
			__( 'Divi Squad', 'squad-modules-for-divi' ),
			$capability,
			$page_slug,
			'', // @phpstan-ignore-line
			$menu_icon,
			$this->plugin->get_admin_menu_position()
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
	 * @access public
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
	 * @access public
	 *
	 * @return void
	 * @throws Exception If any requirement is not met.
	 */
	public function render_admin_page(): void {
		$template_path = $this->plugin->get_template_path( 'admin/requirements.php' );

		// Force requirements check.
		$this->status = array();
		$this->is_fulfilled();

		// Get notice content.
		$args = array(
			'content'          => $this->get_notice_content(),
			'status'           => $this->status,
			'required_version' => $this->required_version,
		);

		if ( $this->plugin->get_wp_fs()->exists( $template_path ) ) {
			load_template( $template_path, false, $args );
		}
	}

	/**
	 * Add badges to the requirements page.
	 *
	 * @since 3.2.3
	 * @access public
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
				esc_html( $this->plugin->get_version_dot() )
			);
		}
	}

	/**
	 * Get notice content based on Divi status
	 *
	 * @since 3.2.0
	 * @access protected
	 *
	 * @return string
	 */
	protected function get_notice_content(): string {
		// Not installed.
		if ( ! $this->is_divi_installed() ) {
			return $this->render_notice_banner(
				'error',
				__( 'Divi Not Installed', 'squad-modules-for-divi' ),
				__( 'Squad Modules requires either the Divi/Extra theme or Divi Builder plugin to be installed and activated. Please install Divi to use Squad Modules.', 'squad-modules-for-divi' ),
				array(
					'url'  => 'https://www.elegantthemes.com/gallery/divi/',
					'text' => __( 'Get Divi', 'squad-modules-for-divi' ),
					'icon' => 'external',
				)
			);
		}

		// Theme is active but outdated.
		if ( ( $this->status['is_theme_active'] ?? false ) && isset( $this->status['theme_version'] ) &&
			version_compare( $this->status['theme_version'], $this->required_version, '<' ) ) {

			return $this->render_notice_banner(
				'warning',
				__( 'Divi Update Required', 'squad-modules-for-divi' ),
				sprintf(
				/* translators: %1$s: Required Divi version, %2$s: Current Divi version */
					__( 'Squad Modules requires Divi version %1$s or higher. Your current Divi version is %2$s. Please update to continue using Squad Modules.', 'squad-modules-for-divi' ),
					$this->required_version,
					$this->status['theme_version']
				),
				array(
					'url'  => admin_url( 'themes.php' ),
					'text' => __( 'Update Divi', 'squad-modules-for-divi' ),
					'icon' => 'update',
				)
			);
		}

		// Plugin is active but outdated.
		if ( ( $this->status['is_plugin_active'] ?? false ) && isset( $this->status['plugin_version'] ) &&
			version_compare( $this->status['plugin_version'], $this->required_version, '<' ) ) {

			return $this->render_notice_banner(
				'warning',
				__( 'Divi Builder Update Required', 'squad-modules-for-divi' ),
				sprintf(
				/* translators: %1$s: Required Divi version, %2$s: Current Divi version */
					__( 'Squad Modules requires Divi Builder version %1$s or higher. Your current Divi Builder version is %2$s. Please update to continue using Squad Modules.', 'squad-modules-for-divi' ),
					$this->required_version,
					$this->status['plugin_version']
				),
				array(
					'url'  => admin_url( 'plugins.php' ),
					'text' => __( 'Update Divi Builder', 'squad-modules-for-divi' ),
					'icon' => 'update',
				)
			);
		}

		// Theme is installed but not active.
		if ( ( $this->status['is_theme_installed'] ?? false ) && ! ( $this->status['is_theme_active'] ?? false ) ) {
			return $this->render_notice_banner(
				'error',
				__( 'Divi Theme Not Activated', 'squad-modules-for-divi' ),
				__( 'Squad Modules has detected that you have Divi theme installed but not activated. Please activate the Divi theme to use Squad Modules.', 'squad-modules-for-divi' ),
				array(
					'url'  => admin_url( 'themes.php' ),
					'text' => __( 'Activate Divi Theme', 'squad-modules-for-divi' ),
					'icon' => 'admin-appearance',
				)
			);
		}

		// Plugin is installed but not active.
		if ( ( $this->status['is_plugin_installed'] ?? false ) && ! ( $this->status['is_plugin_active'] ?? false ) ) {
			return $this->render_notice_banner(
				'error',
				__( 'Divi Builder Plugin Not Activated', 'squad-modules-for-divi' ),
				__( 'Squad Modules has detected that you have the Divi Builder plugin installed but not activated. Please activate the Divi Builder plugin to use Squad Modules.', 'squad-modules-for-divi' ),
				array(
					'url'  => admin_url( 'plugins.php' ),
					'text' => __( 'Activate Divi Builder', 'squad-modules-for-divi' ),
					'icon' => 'admin-plugins',
				)
			);
		}

		// All requirements are met.
		return $this->render_notice_banner(
			'success',
			__( 'Divi Requirements Met', 'squad-modules-for-divi' ),
			__( 'Your Divi installation meets all the requirements for Squad Modules. You can start using the modules now!', 'squad-modules-for-divi' ),
			array(
				'url'  => admin_url( 'admin.php?page=' . $this->plugin->get_admin_menu_slug() ),
				'text' => __( 'Go to Dashboard', 'squad-modules-for-divi' ),
				'icon' => 'admin-home',
			)
		);
	}

	/**
	 * Renders a standardized notice banner.
	 *
	 * @since 3.3.0
	 * @access protected
	 *
	 * @param string                                         $type    Notice type: 'error', 'warning', 'success'.
	 * @param string                                         $title   Notice title.
	 * @param string                                         $message Notice message.
	 * @param array{url: string, text: string, icon: string} $action Optional. Action button details.
	 *
	 * @return string The HTML for the notice banner.
	 */
	protected function render_notice_banner( string $type, string $title, string $message, array $action ): string {
		$output = sprintf(
			'<div class="notice divi-squad-banner divi-squad-%s-banner">
				<div class="divi-squad-banner-content">
					<h3>%s</h3>
					<p>%s</p>',
			esc_attr( $type ),
			esc_html( $title ),
			esc_html( $message )
		);

		// Add action button if provided.
		if ( count( $action ) > 0 ) {
			$output .= sprintf(
				'<div class="divi-squad-notice-action">
					<div class="divi-squad-notice-action-left">
						<a href="%s" %s class="button-primary divi-squad-notice-action-button">
							<span class="dashicons dashicons-%s"></span>
							<p>%s</p>
						</a>
					</div>
				</div>',
				esc_url( $action['url'] ),
				strpos( $action['url'], 'http' ) === 0 ? 'target="_blank"' : '',
				esc_attr( $action['icon'] ),
				esc_html( $action['text'] )
			);
		}

		$output .= '</div></div>';

		return $output;
	}

	/**
	 * Log a requirement failure with detailed information.
	 *
	 * Records failed requirements with comprehensive context data for debugging
	 * and reports major compatibility issues to the error reporting system.
	 *
	 * @since 4.0.0
	 * @access public
	 *
	 * @param bool $report_error Whether to send an error report for this failure. Default true.
	 * @return void
	 */
	public function log_requirement_failure( bool $report_error = true ): void {
		try {
			$status = $this->get_status();

			// Build comprehensive extra data for debugging
			$extra_data = array(
				'status_details'    => $status,
				'error_message'     => $this->get_last_error(),
				'site_url'          => home_url(),
				'wordpress_version' => get_bloginfo( 'version' ),
				'php_version'       => PHP_VERSION,
				'server_software'   => $_SERVER['SERVER_SOFTWARE'] ?? '',
				'required_version'  => $this->required_version,
				'is_multisite'      => is_multisite() ? 'Yes' : 'No',
			);

			// Include theme information if available
			if ( function_exists( 'wp_get_theme' ) ) {
				$theme                      = wp_get_theme();
				$extra_data['active_theme'] = array(
					'name'    => $theme->get( 'Name' ),
					'version' => $theme->get( 'Version' ),
					'author'  => $theme->get( 'Author' ),
				);
			}

			// Determine the specific failure type with detailed descriptions
			if ( ! (bool) ( $status['is_installed'] ?? false ) ) {
				$requirement = 'Divi Installation';
				$current     = 'Not installed';
				$expected    = 'Divi theme or Divi Builder plugin must be installed';
				$context     = 'Missing Divi';
			} elseif ( ! (bool) ( $status['is_active'] ?? false ) ) {
				// Provide specific context for which component is inactive
				if ( (bool) ( $status['is_theme_installed'] ?? false ) ) {
					$requirement = 'Divi Theme Activation';
					$current     = 'Divi theme is installed but not activated';
					$expected    = 'Divi theme must be activated';
					$context     = 'Inactive Divi Theme';
				} else {
					$requirement = 'Divi Builder Plugin Activation';
					$current     = 'Divi Builder plugin is installed but not activated';
					$expected    = 'Divi Builder plugin must be activated';
					$context     = 'Inactive Divi Plugin';
				}
			} elseif ( ! (bool) ( $status['meets_version'] ?? true ) ) {
				if ( isset( $status['theme_version'] ) ) {
					$current_version = 'Theme version: ' . $status['theme_version'];
				} else {
					$current_version = ( isset( $status['plugin_version'] ) ? 'Plugin version: ' . $status['plugin_version'] : 'Unknown version' );
				}

				$requirement = 'Divi Version Compatibility';
				$current     = $current_version;
				$expected    = 'Version ' . $this->required_version . ' or higher';
				$context     = 'Outdated Divi Version';
			} else {
				$requirement = 'Unknown Requirement';
				$current     = 'Validation failed for unknown reason';
				$expected    = 'All requirements should be met';
				$context     = 'General Requirements Failure';
			}

			/**
			 * Filters whether to log the requirement failure.
			 *
			 * @since 3.2.0
			 * @since 4.0.0 Added $context parameter
			 *
			 * @param bool         $should_log   Whether to log the failure.
			 * @param string       $requirement  The failed requirement.
			 * @param string       $current      The current value.
			 * @param string       $expected     The expected value.
			 * @param array        $extra_data   Additional data.
			 * @param string       $context      Context identifier for the failure.
			 * @param Requirements $requirements Instance of this class.
			 */
			$should_log = apply_filters(
				'divi_squad_should_log_requirement_failure',
				true,
				$requirement,
				$current,
				$expected,
				$extra_data,
				$context,
				$this
			);

			if ( ! $should_log ) {
				return;
			}

			// Prepare a detailed message for logging
			$log_message = sprintf(
				'Requirements check failed: %s. Current: %s. Expected: %s.',
				$requirement,
				$current,
				$expected
			);

			// Log the failure with proper context
			$this->plugin->log_warning( $log_message, $context, $extra_data );

			// Create exception for reporting if needed
			if ( $report_error ) {
				$exception = new \RuntimeException(
					sprintf(
						'Squad Modules requirements check failed: %s (current: %s, expected: %s)',
						$requirement,
						$current,
						$expected
					),
					500
				);

				// Log using the error method for critical requirements failures
				$this->plugin->log_error(
					$exception,
					$context,
					true,
					$extra_data
				);
			}

			/**
			 * Action triggered after logging a requirement failure.
			 *
			 * @since 3.2.0
			 * @since 4.0.0 Added $context parameter and $report_error parameter
			 *
			 * @param string       $requirement  The failed requirement.
			 * @param string       $current      The current value.
			 * @param string       $expected     The expected value.
			 * @param array        $extra_data   Additional data.
			 * @param string       $context      Context identifier for the failure.
			 * @param bool         $report_error Whether an error report was sent.
			 * @param Requirements $requirements Instance of this class.
			 */
			do_action(
				'divi_squad_after_log_requirement_failure',
				$requirement,
				$current,
				$expected,
				$extra_data,
				$context,
				$report_error,
				$this
			);
		} catch ( Throwable $e ) {
			// Ensure any errors in the logging process are caught and recorded
			$this->plugin->log_error(
				$e,
				'Requirements_Logging_Error',
				false, // Don't report this meta-error to avoid loops
				array(
					'original_error' => $this->get_last_error(),
					'status'         => $this->status,
				)
			);
		}
	}

	/**
	 * Get system requirements status details.
	 *
	 * @since 3.3.0
	 * @access public
	 *
	 * @return array<string, mixed> An array of requirements status details.
	 * @throws Exception If any requirement is not met.
	 */
	public function get_status(): array {
		// Force a check if not already done.
		if ( count( $this->status ) === 0 ) {
			$this->is_fulfilled();
		}

		return $this->status;
	}
}
