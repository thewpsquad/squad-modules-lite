<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Requirements main class.
 *
 * Coordinates the different aspects of requirements checking through composition.
 *
 * @since   3.2.0
 * @since   3.4.0 Improved error handling and constant detection
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Core\Requirements;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use DiviSquad\Core\Contracts\Hookable;
use Throwable;

/**
 * Class Requirements
 *
 * Manages Divi requirements validation and displays appropriate
 * notifications when requirements are not met.
 *
 * @since   3.2.0
 * @package DiviSquad
 */
class Requirements implements Hookable {
	/**
	 * Status checker instance.
	 *
	 * @since 3.5.0
	 *
	 * @var Status_Checker
	 */
	private Status_Checker $status_checker;

	/**
	 * Assets manager instance.
	 *
	 * @since 3.5.0
	 *
	 * @var Assets
	 */
	private Assets $assets_manager;

	/**
	 * Admin page instance.
	 *
	 * @since 3.5.0
	 *
	 * @var Admin_Page
	 */
	private Admin_Page $admin_page;

	/**
	 * Status reporter instance.
	 *
	 * @since 3.5.0
	 *
	 * @var Status_Reporter
	 */
	private Status_Reporter $status_reporter;

	/**
	 * Error logger instance.
	 *
	 * @since 3.5.0
	 *
	 * @var Error_Logger
	 */
	private Error_Logger $error_logger;

	/**
	 * Constructor.
	 *
	 * Initialize hooks and filters.
	 *
	 * @since 3.3.0
	 * @since 3.5.0 Refactored to use composition
	 */
	public function __construct() {
		// Initialize dependencies.
		$this->status_checker  = new Status_Checker();
		$this->assets_manager  = new Assets();
		$this->admin_page      = new Admin_Page( $this->status_checker );
		$this->status_reporter = new Status_Reporter( $this->status_checker );
		$this->error_logger    = new Error_Logger();

		$this->register_hooks();
	}

	/**
	 * Register hooks and filters.
	 *
	 * @since  3.4.0
	 * @access public
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		// Use the new check_requirements method on init instead of is_fulfilled directly..
		add_action( 'init', array( $this, 'check_requirements' ) );

		// Add hooks for theme/plugin activation events..
		add_action( 'activated_plugin', array( $this, 'check_requirements_on_plugin_activation' ) );
		add_action( 'after_switch_theme', array( $this, 'check_requirements_on_theme_activation' ) );

		$this->assets_manager->register_hooks();
		$this->admin_page->register_hooks();
		$this->status_reporter->register_hooks();
	}

	/**
	 * Check all Divi requirements and take appropriate actions.
	 *
	 * This method checks if requirements are met and logs failures if needed.
	 * It centralizes requirement checking to avoid duplication.
	 *
	 * @since  3.4.0
	 * @access public
	 *
	 * @return void
	 */
	public function check_requirements(): void {
		// Check if requirements are fulfilled..
		$is_fulfilled = $this->is_fulfilled();

		// If requirements are not fulfilled, log the failure..
		if ( ! $is_fulfilled ) {
			// Throttle: only re-log if we haven't logged in the last hour..
			if ( false === get_transient( 'divi_squad_req_logged' ) ) {
				$this->error_logger->log_requirement_failure( $this->status_checker );
				set_transient( 'divi_squad_req_logged', 1, HOUR_IN_SECONDS );
			}
		} else {
			// If requirements are now fulfilled, but we had a previous failure,.
			// clean up the failure flags..
			$requirements_failed = (bool) get_option( 'divi_squad_requirements_failed', false );
			if ( $requirements_failed ) {
				delete_option( 'divi_squad_requirements_failed' );
				delete_option( 'divi_squad_requirements_context' );
				delete_option( 'divi_squad_requirements_data' );
				delete_transient( 'divi_squad_req_logged' );

				// Log the successful resolution..
				divi_squad()->log_info(
					'Requirements now fulfilled',
					'Requirements_Resolved',
					array( 'previous_failure' => true )
				);
			}
		}
	}

	/**
	 * Check requirements when a plugin is activated.
	 *
	 * @since  3.4.0
	 * @access public
	 *
	 * @param string $plugin Path to the plugin file relative to the plugins directory.
	 *
	 * @return void
	 */
	public function check_requirements_on_plugin_activation( string $plugin ): void {
		// Check if we have a previously failed requirement..
		$requirements_failed = (bool) get_option( 'divi_squad_requirements_failed', false );

		if ( ! $requirements_failed ) {
			return;
		}

		/**
		 * Filter the list of plugin slugs that should trigger a requirements re-check on activation.
		 *
		 * @since 3.5.0
		 *
		 * @param array<string> $divi_plugin_slugs List of slug fragments to match against.
		 */
		$divi_plugin_slugs = apply_filters(
			'divi_squad_divi_plugin_slugs',
			array( 'divi-builder', 'divi', 'extra' )
		);

		$is_divi_plugin = false;
		foreach ( $divi_plugin_slugs as $slug ) {
			if ( false !== stripos( $plugin, $slug ) ) {
				$is_divi_plugin = true;
				break;
			}
		}

		if ( $is_divi_plugin ) {
			// Reset status cache to force a fresh check..
			$this->status_checker->reset_cache();

			// Check if requirements are now met..
			if ( $this->is_fulfilled() ) {
				// Requirements are now met, clean up..
				delete_option( 'divi_squad_requirements_failed' );
				delete_option( 'divi_squad_requirements_context' );
				delete_option( 'divi_squad_requirements_data' );

				// Log the successful resolution..
				divi_squad()->log_info(
					'Requirements now fulfilled after plugin activation: ' . $plugin,
					'Requirements_Resolved',
					array( 'activated_plugin' => $plugin )
				);
			}
		}
	}

	/**
	 * Check requirements when a theme is activated.
	 *
	 * @since  3.4.0
	 * @access public
	 *
	 * @param string $theme_name Name of the theme.
	 *
	 * @return void
	 */
	public function check_requirements_on_theme_activation( string $theme_name ): void {
		// Check if we have a previously failed requirement..
		$requirements_failed = (bool) get_option( 'divi_squad_requirements_failed', false );

		if ( ! $requirements_failed ) {
			return;
		}

		// Check if the activated theme is Divi or Extra..
		if ( false !== stripos( $theme_name, 'divi' ) || false !== stripos( $theme_name, 'extra' ) ) {
			// Reset status cache to force a fresh check..
			$this->status_checker->reset_cache();

			// Check if requirements are now met..
			if ( $this->is_fulfilled() ) {
				// Requirements are now met, clean up..
				delete_option( 'divi_squad_requirements_failed' );
				delete_option( 'divi_squad_requirements_context' );
				delete_option( 'divi_squad_requirements_data' );

				// Log the successful resolution..
				divi_squad()->log_info(
					'Requirements now fulfilled after theme activation: ' . $theme_name,
					'Requirements_Resolved',
					array( 'activated_theme' => $theme_name )
				);
			}
		}
	}

	/**
	 * Check if all Divi requirements are fulfilled.
	 *
	 * @since  3.2.0
	 * @since  3.5.0 Refactored to use Status_Checker
	 * @access public
	 *
	 * @return bool True if all requirements are met, false otherwise.
	 */
	public function is_fulfilled(): bool {
		try {
			return $this->status_checker->is_fulfilled();
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Requirements_Check_Failed' );

			return false;
		}
	}

	/**
	 * Get system requirements status details.
	 *
	 * @since  3.3.0
	 * @access public
	 *
	 * @return array<string, mixed> An array of requirements status details.
	 */
	public function get_status(): array {
		return $this->status_checker->get_status();
	}

	/**
	 * Get the last error message from requirements check.
	 *
	 * @since  3.3.0
	 * @access public
	 *
	 * @return string The last error message.
	 */
	public function get_last_error(): string {
		return $this->status_checker->get_last_error();
	}

	/**
	 * Register the admin page for requirements.
	 *
	 * @since  3.2.0
	 * @access public
	 *
	 * @return void
	 */
	public function register_pre_loaded_admin_page(): void {
		$this->admin_page->register_pre_loaded_admin_page();
	}
}
