<?php
/**
 * Error logger class for requirements.
 *
 * Handles logging errors related to requirements.
 *
 * @since   3.5.0
 * @package DiviSquad\Core\Requirements
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Core\Requirements;

use RuntimeException;
use Throwable;

/**
 * Class Error_Logger
 *
 * Manages error logging for requirements.
 *
 * @since   3.5.0
 * @package DiviSquad\Core\Requirements
 */
class Error_Logger {
	/**
	 * Log a requirement failure with detailed information.
	 *
	 * Records failed requirements with comprehensive context data for debugging
	 * and reports major compatibility issues to the error reporting system.
	 *
	 * @since  3.4.0
	 * @since  3.5.0 Refactored to use Status_Checker
	 * @access public
	 *
	 * @param Status_Checker $status_checker Status checker instance.
	 *
	 * @return void
	 */
	public function log_requirement_failure( Status_Checker $status_checker ): void {
		try {
			// Skip logging for special WordPress request types.
			if ( $this->is_special_request_type() ) {
				return;
			}

			// Get the current status..
			$status = $status_checker->get_status();

			// Build comprehensive extra data for debugging..
			$extra_data = array(
				'status_details'    => $status,
				'error_message'     => $status_checker->get_last_error(),
				'site_url'          => home_url(),
				'wordpress_version' => get_bloginfo( 'version' ),
				'php_version'       => PHP_VERSION,
				'server_software'   => sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ?? '' ) ),
				'required_version'  => $status_checker->get_required_version(),
				'is_multisite'      => is_multisite() ? 'Yes' : 'No',
			);

			// Include theme information if available..
			if ( function_exists( 'wp_get_theme' ) ) {
				$theme                      = wp_get_theme();
				$extra_data['active_theme'] = array(
					'name'    => $theme->get( 'Name' ),
					'version' => $theme->get( 'Version' ),
					'author'  => $theme->get( 'Author' ),
				);
			}

			// Determine the specific failure type with detailed descriptions..
			if ( ! (bool) ( $status['is_installed'] ?? false ) ) {
				$requirement = 'Divi Installation';
				$current     = 'Not installed';
				$expected    = 'Divi theme or Divi Builder plugin must be installed';
				$context     = 'Missing Divi';
			} elseif ( ! (bool) ( $status['is_active'] ?? false ) ) {
				// Provide specific context for which component is inactive..
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
			} elseif ( ! (bool) ( $status['meets_version'] ?? false ) ) {
				if ( isset( $status['theme_version'] ) ) {
					$current_version = 'Theme version: ' . $status['theme_version'];
				} else {
					$current_version = ( isset( $status['plugin_version'] ) ? 'Plugin version: ' . $status['plugin_version'] : 'Unknown version' );
				}

				$requirement = 'Divi Version Compatibility';
				$current     = $current_version;
				$expected    = 'Version ' . $status_checker->get_required_version() . ' or higher';
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
			 * @since 3.4.0 Added $context parameter
			 * @since 3.5.0 Updated to use Status_Checker
			 *
			 * @param bool           $should_log     Whether to log the failure.
			 * @param string         $requirement    The failed requirement.
			 * @param string         $current        The current value.
			 * @param string         $expected       The expected value.
			 * @param array          $extra_data     Additional data.
			 * @param string         $context        Context identifier for the failure.
			 * @param Status_Checker $status_checker Status checker instance.
			 */
			$should_log = apply_filters(
				'divi_squad_should_log_requirement_failure',
				true,
				$requirement,
				$current,
				$expected,
				$extra_data,
				$context,
				$status_checker
			);

			if ( ! $should_log ) {
				return;
			}

			// Store requirement failure status in options..
			update_option( 'divi_squad_requirements_failed', true, false );
			update_option( 'divi_squad_requirements_context', $context, false );
			update_option( 'divi_squad_requirements_data', $extra_data, false );

			// Prepare a detailed message for logging..
			$log_message = sprintf(
				'Requirements check failed: %s. Current: %s. Expected: %s.',
				$requirement,
				$current,
				$expected
			);

			// Log using the error method for critical requirements failures..
			divi_squad()->log_error(
				new RuntimeException( $log_message, 500 ),
				$context
			);

			/**
			 * Action triggered after logging a requirement failure.
			 *
			 * @since 3.2.0
			 * @since 3.4.0 Added $context parameter and $report_error parameter
			 * @since 3.5.0 Updated to use Status_Checker
			 *
			 * @param string         $requirement    The failed requirement.
			 * @param string         $current        The current value.
			 * @param string         $expected       The expected value.
			 * @param array          $extra_data     Additional data.
			 * @param string         $context        Context identifier for the failure.
			 * @param bool           $report_error   Whether an error report was sent. Always true.
			 * @param Status_Checker $status_checker Status checker instance.
			 */
			do_action(
				'divi_squad_after_log_requirement_failure',
				$requirement,
				$current,
				$expected,
				$extra_data,
				$context,
				true,
				$status_checker
			);
		} catch ( Throwable $e ) {
			// Ensure any errors in the logging process are caught and recorded..
			divi_squad()->log_error(
				$e,
				'Requirements_Logging_Error',
				false, // Don't report this meta-error to avoid loops.
				array(
					'original_error' => $status_checker->get_last_error(),
				)
			);
		}
	}

	/**
	 * Determines if the current request is a special WordPress request type
	 * that should be excluded from requirements logging.
	 *
	 * Detects AJAX, REST API, cron jobs, and XML-RPC requests using multiple methods
	 * for maximum reliability.
	 *
	 * @since  3.4.0
	 * @access protected
	 *
	 * @return bool True if the current request is a special request type, false otherwise.
	 */
	protected function is_special_request_type(): bool {
		// Define flags for various request types.
		$is_ajax_request = false;
		$is_rest_request = false;
		$is_cron_job     = false;
		$is_xml_request  = false;

		// Method 1: Standard WordPress function checks.
		if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
			$is_ajax_request = true;
		}

		if ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) {
			$is_rest_request = true;
		}

		if ( function_exists( 'wp_is_xml_request' ) && wp_is_xml_request() ) {
			$is_xml_request = true;
		}

		if ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) {
			$is_cron_job = true;
		}

		if ( function_exists( 'wp_is_rest_request' ) && function_exists( 'rest_get_url_prefix' ) ) {
			$rest_prefix = rest_get_url_prefix();
			$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

			// Check if the request URI contains the REST API prefix.
			if ( ( '' !== $request_uri ) && strpos( $request_uri, '/' . $rest_prefix . '/' ) !== false ) {
				$is_rest_request = true;
			}
		}

		// Method 2: Direct script filename check for admin-ajax.php.
		if ( isset( $_SERVER['SCRIPT_FILENAME'] ) ) {
			$script_filename = sanitize_text_field( wp_unslash( $_SERVER['SCRIPT_FILENAME'] ) );
			if ( strpos( $script_filename, 'admin-ajax.php' ) !== false || basename( $script_filename ) === 'admin-ajax.php' ) {
				$is_ajax_request = true;
			}

			if ( strpos( $script_filename, 'wp-cron.php' ) !== false || basename( $script_filename ) === 'wp-cron.php' ) {
				$is_cron_job = true;
			}
		}

		// Method 3: Check for AJAX through request parameters and headers.
		if ( isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REQUESTED_WITH'] ) ) ) === 'xmlhttprequest' ) {
			$is_ajax_request = true;
		}

		// Method 4: Check for specific AJAX constants.
		if ( defined( 'DOING_AJAX' ) && \DOING_AJAX ) {
			$is_ajax_request = true;
		}

		if ( defined( 'XMLRPC_REQUEST' ) && \XMLRPC_REQUEST ) {
			$is_xml_request = true;
		}

		if ( defined( 'DOING_CRON' ) && \DOING_CRON ) {
			$is_cron_job = true;
		}

		// Return true if any of the special request types are detected.
		return $is_rest_request || $is_ajax_request || $is_cron_job || $is_xml_request;
	}
}
