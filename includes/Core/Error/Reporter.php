<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Error Reporter
 *
 * Provides a robust system for sending error reports with proper WordPress integration,
 * error handling, and logging.
 *
 * @since   3.4.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Core\Error;

use DiviSquad\Utils\Divi;
use RuntimeException;
use Throwable;
use WP_Error;

/**
 * Error Reporter Class
 *
 * Main class for error reporting that delegates specific responsibilities
 * to specialized helper classes.
 *
 * Features:
 * - Centralized error reporting management
 * - Integration with WordPress error handling
 * - Exception handling and logging
 * - QuickSend mechanism for urgent error reports
 *
 * @since   3.4.0
 * @package DiviSquad
 */
class Reporter {
	/**
	 * Error email sender instance
	 *
	 * @since 3.4.0
	 * @var Email_Sender
	 */
	protected Email_Sender $email_sender;

	/**
	 * Error rate limiter instance
	 *
	 * @since 3.4.0
	 * @var Rate_Limiter
	 */
	protected Rate_Limiter $rate_limiter;

	/**
	 * Environment collector instance
	 *
	 * @since 3.4.0
	 * @var Environment_Collector
	 */
	protected Environment_Collector $environment_collector;

	/**
	 * WP_Error instance for error handling
	 *
	 * @since 3.4.0
	 * @var WP_Error
	 */
	protected WP_Error $errors;

	/**
	 * Error report data
	 *
	 * @since 3.4.0
	 * @var array<string, mixed>
	 */
	protected array $data;

	/**
	 * Email sending result
	 *
	 * @since 3.4.0
	 * @var bool
	 */
	protected bool $result = false;

	/**
	 * Required data fields
	 *
	 * @since 3.4.0
	 * @var array<string>
	 */
	protected const REQUIRED_FIELDS = array(
		'error_message',
		'error_code',
		'error_file',
		'error_line',
	);

	/**
	 * Initialize error reporter
	 *
	 * Creates a new error reporter instance with dependencies and sanitized data.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $data Error report data.
	 */
	public function __construct( array $data = array() ) {
		$this->email_sender          = new Email_Sender();
		$this->rate_limiter          = new Rate_Limiter();
		$this->environment_collector = new Environment_Collector();
		$this->errors                = new WP_Error();
		$this->data                  = $this->sanitize_data( $data );
	}

	/**
	 * Process data for error report template
	 *
	 * Applies filters and processes data specifically for use in the error report
	 * email template, ensuring all necessary variables are prepared correctly.
	 *
	 * @since 3.4.1
	 *
	 * @param array<string, mixed> $data Raw error data.
	 *
	 * @return array<string, mixed> Processed template data.
	 */
	public function process_template_data( array $data ): array {
		try {
			// Start with base error and environment data
			$template_data = $data;

			// Ensure environment data is included
			if ( '' === $template_data['environment'] ) {
				$template_data['environment'] = $this->environment_collector->get_environment_info();
			}

			// Add site information if not present
			if ( ! isset( $template_data['site_url'] ) ) {
				$template_data['site_url'] = site_url();
			}

			if ( ! isset( $template_data['site_name'] ) ) {
				$template_data['site_name'] = get_bloginfo( 'name' );
			}

			if ( ! isset( $template_data['timestamp'] ) ) {
				$template_data['timestamp'] = current_time( 'mysql' );
			}

			if ( ! isset( $template_data['charset'] ) ) {
				$template_data['charset'] = get_bloginfo( 'charset' );
			}

			// Process severity class based on error code or message
			$severity_class = 'medium'; // Default
			$error_code     = $template_data['error_code'] ?? null;
			$error_message  = $template_data['error_message'] ?? '';

			// Determine severity class from error code or message content
			if ( isset( $error_code ) ) {
				// Check numeric error codes first
				if ( is_numeric( $error_code ) ) {
					$severity_class = $error_code >= 500 ? 'high' : 'medium';
				} elseif ( stripos( $error_message, 'fatal' ) !== false || stripos( $error_message, 'critical' ) !== false ) {
					$severity_class = 'high';
				} elseif ( stripos( $error_message, 'warning' ) !== false ) {
					$severity_class = 'medium';
				} elseif ( stripos( $error_message, 'notice' ) !== false ) {
					$severity_class = 'low';
				}
			}

			/**
			 * Filter the severity classification of the error.
			 *
			 * @since 3.4.1
			 *
			 * @param string $severity_class Determined severity class (high/medium/low).
			 * @param mixed  $error_code     The error code.
			 * @param string $error_message  The error message.
			 */
			$severity_class = apply_filters(
				'divi_squad_error_severity_class',
				$severity_class,
				$error_code,
				$error_message
			);

			// Determine error type based on file path
			$error_type = 'Unknown Error';
			$error_file = $template_data['error_file'] ?? '';

			if ( '' !== $error_file ) {
				$error_types = array(
					'Requirements.php'        => 'Requirements Error',
					'includes/Core'           => 'Core Component Error',
					'includes/Modules'        => 'Module Error',
					'includes/Builder'        => 'Module Error',
					'includes/Settings'       => 'Settings Error',
					'includes/Utils'          => 'Utility Error',
					'includes/Utils/Divi.php' => 'Divi Detection Error',
				);

				foreach ( $error_types as $path_fragment => $type ) {
					if ( false !== strpos( $error_file, $path_fragment ) ) {
						$error_type = $type;
						break;
					}
				}
			}

			/**
			 * Filter the error type categorization.
			 *
			 * @since 3.4.1
			 *
			 * @param string $error_type Categorized error type.
			 * @param string $error_file File where the error occurred.
			 */
			$error_type = apply_filters( 'divi_squad_error_type', $error_type, $error_file );

			// Add processed values to the template data
			$template_data['severity_class'] = $severity_class;
			$template_data['error_type']     = $error_type;

			// Process file path information
			if ( ! isset( $template_data['relative_file_path'] ) && '' !== ( $error_file ) ) {
				$plugin_path = WP_PLUGIN_DIR . '/squad-modules-for-divi/';
				if ( strpos( $error_file, $plugin_path ) === 0 ) {
					$template_data['relative_file_path'] = substr( $error_file, strlen( $plugin_path ) );
				} else {
					$template_data['relative_file_path'] = $error_file;
				}
			}

			// Process Divi environment information if available
			$environment = $template_data['environment'] ?? array();
			$extra_data  = $template_data['extra_data'] ?? array();

			// Get Divi version with fallbacks
			$divi_version = 'Unknown';
			if ( isset( $environment['divi_version'] ) ) {
				$divi_version = $environment['divi_version'];
			} elseif ( isset( $extra_data['status_details']['theme_version'] ) ) {
				$divi_version = $extra_data['status_details']['theme_version'];
			} elseif ( isset( $extra_data['status_details']['plugin_version'] ) ) {
				$divi_version = $extra_data['status_details']['plugin_version'];
			}

			// Extract quick reference versions
			$template_data['client_wp_version'] = $environment['wp_version'] ?? 'Unknown';
			$template_data['php_version']       = $environment['php_version'] ?? 'Unknown';
			$template_data['plugin_version']    = $environment['plugin_version'] ?? 'Unknown';
			$template_data['divi_version']      = $divi_version;

			// Prepare formatted timestamp
			if ( ! isset( $template_data['formatted_timestamp'] ) && isset( $template_data['timestamp'] ) ) {
				$template_data['formatted_timestamp'] = wp_date(
					'Y-m-d H:i:s e',
					(int) strtotime( $template_data['timestamp'] )
				);
			}

			// Prepare Divi theme information
			$divi_theme_info = array(
				'version'          => $divi_version,
				'mode'             => $environment['divi_mode'] ?? 'Unknown',
				'theme_name'       => $environment['active_theme_name'] ?? 'Unknown',
				'is_child_theme'   => $environment['is_child_theme'] ?? 'Unknown',
				'parent_theme'     => $environment['parent_theme_name'] ?? 'N/A',
				'detection_method' => $environment['divi_detection_method'] ?? 'Unknown',
			);

			/**
			 * Filter the Divi environment information.
			 *
			 * @since 3.4.1
			 *
			 * @param array<string, mixed> $divi_theme_info Divi environment information.
			 * @param array<string, mixed> $environment     Complete environment data.
			 */
			$divi_theme_info = apply_filters( 'divi_squad_error_divi_info', $divi_theme_info, $environment );

			// Add Divi theme info to template data
			$template_data['divi_theme_info'] = $divi_theme_info;

			// Generate unique error reference ID if not already present
			if ( ! isset( $template_data['error_reference'] ) ) {
				$site_url   = $template_data['site_url'] ?? site_url();
				$error_line = $template_data['error_line'] ?? '0';
				$timestamp  = $template_data['timestamp'] ?? current_time( 'mysql' );

				$template_data['error_reference'] = substr(
					md5( $site_url . $error_file . $error_line . $timestamp ),
					0,
					8
				);
			}

			/**
			 * Action hook fired immediately before template data filtering.
			 *
			 * This hook allows modules and extensions to perform additional operations
			 * before the error report template data is finalized.
			 *
			 * @since 3.4.1
			 *
			 * @param array<string, mixed> $template_data  The template data being processed.
			 * @param string               $severity_class The error severity class.
			 * @param string               $error_type     The error type.
			 */
			do_action( 'divi_squad_error_report_template_data_pre', $template_data, $severity_class, $error_type );

			/**
			 * Filter the complete set of data variables available in the error report template.
			 *
			 * @since 3.4.1
			 *
			 * @param array<string, mixed> $template_data  All template variables.
			 * @param string               $severity_class The determined severity class.
			 * @param string               $error_type     The categorized error type.
			 */
			return apply_filters(
				'divi_squad_error_report_template_data',
				$template_data,
				$severity_class,
				$error_type
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to process template data', false );

			return $data;
		}
	}

	/**
	 * Send error report with rate limiting and validation
	 *
	 * Processes the error report data, applies rate limiting, validates the data,
	 * and sends the email if all checks pass.
	 *
	 * @since 3.4.1
	 *
	 * @return bool Success status.
	 */
	public function send(): bool {
		try {
			/**
			 * Action triggered before sending an error report.
			 *
			 * @since 3.4.0
			 *
			 * @param array    $data   Error report data.
			 * @param WP_Error $errors Current error collection.
			 */
			do_action( 'divi_squad_before_send_error_report', $this->data, $this->errors );

			// Validate rate limit.
			if ( ! $this->rate_limiter->check_rate_limit() ) {
				// Check if this is a critical error that should bypass rate limiting
				$is_critical = isset( $this->data['is_critical'] ) && true === $this->data['is_critical'];

				/**
				 * Filter whether to force reset the rate limit for this error.
				 *
				 * @since 3.4.0
				 *
				 * @param bool                 $force_reset Whether to force reset the rate limit.
				 * @param array<string, mixed> $data        Error report data.
				 */
				$force_reset = apply_filters( 'divi_squad_force_reset_rate_limit', $is_critical, $this->data );

				if ( $force_reset ) {
					// Reset the rate limit for critical errors
					$this->rate_limiter->reset_rate_limit();
				} else {
					throw new RuntimeException(
						esc_html__( 'Error report rate limit exceeded. Please try again later.', 'squad-modules-for-divi' )
					);
				}
			}

			// Validate data.
			if ( ! $this->validate_data() ) {
				$errors = $this->get_error_messages();
				throw new RuntimeException(
					sprintf(
					/* translators: %s: Error messages */
						esc_html__( 'Error report validation failed: %s', 'squad-modules-for-divi' ),
						implode( ', ', $errors )
					)
				);
			}

			// Add environment info to data
			$this->data['environment'] = $this->environment_collector->get_environment_info();

			// Process data for the email template with all filters applied
			$template_data = $this->process_template_data( $this->data );

			// Send email with processed template data
			$this->result = $this->email_sender->send_email( $template_data, $this->errors );

			// Increment rate limit counter on success.
			if ( $this->result ) {
				$this->rate_limiter->increment_rate_limit();

				/**
				 * Action triggered after successfully sending an error report.
				 *
				 * @since 3.4.0
				 *
				 * @param array<string, mixed> $data Error report data.
				 */
				do_action( 'divi_squad_error_report_sent', $this->data );
			} else {
				/**
				 * Action triggered when error report sending fails.
				 *
				 * @since 3.4.0
				 *
				 * @param array<string, mixed> $data   Error report data.
				 * @param WP_Error             $errors Current error collection.
				 */
				do_action( 'divi_squad_error_report_failed', $this->data, $this->errors );
			}

			return $this->result;

		} catch ( Throwable $e ) {
			$this->errors->add( 'send_failed', $e->getMessage() );

			// Log the error
			divi_squad()->log( 'WARNING', $e->getMessage(), 'Error report' );

			/**
			 * Action triggered when an exception occurs while sending an error report.
			 *
			 * @since 3.4.0
			 *
			 * @param Throwable $e      The exception that occurred.
			 * @param array     $data   Error report data.
			 * @param WP_Error  $errors Current error collection.
			 */
			do_action( 'divi_squad_error_report_exception', $e, $this->data, $this->errors );

			return false;
		}
	}

	/**
	 * Validate required data fields
	 *
	 * Ensures all required fields are present in the error report data.
	 *
	 * @since 3.4.0
	 *
	 * @return bool Validation result.
	 */
	protected function validate_data(): bool {
		/**
		 * Filter the required fields for error reports.
		 *
		 * @since 3.4.0
		 *
		 * @param array<string> $required_fields List of required field names.
		 */
		$required_fields = apply_filters( 'divi_squad_error_report_required_fields', self::REQUIRED_FIELDS );

		foreach ( $required_fields as $field ) {
			if ( ! isset( $this->data[ $field ] ) || '' === $this->data[ $field ] ) {
				$this->errors->add(
					$field,
					sprintf(
					/* translators: %s: Field name */
						esc_html__( '%s is required for error reporting.', 'squad-modules-for-divi' ),
						ucfirst( str_replace( '_', ' ', $field ) )
					)
				);
			}
		}

		$is_valid = ! $this->errors->has_errors();

		/**
		 * Filter the validation result.
		 *
		 * @since 3.4.0
		 *
		 * @param bool     $is_valid Whether the data is valid.
		 * @param array    $data     Error report data.
		 * @param WP_Error $errors   Current error collection.
		 */
		return apply_filters( 'divi_squad_error_report_validation_result', $is_valid, $this->data, $this->errors );
	}

	/**
	 * Sanitize input data
	 *
	 * Recursively sanitizes all string values in the error report data.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $data Raw input data.
	 *
	 * @return array<string, mixed> Sanitized data.
	 */
	protected function sanitize_data( array $data ): array {
		$sanitized = array();

		/**
		 * Filter the string sanitization function used for error report data.
		 *
		 * @since 3.4.0
		 *
		 * @param callable $sanitize_function The function used to sanitize string values. Default 'sanitize_text_field'.
		 */
		$sanitize_function = apply_filters( 'divi_squad_error_report_sanitize_function', 'sanitize_text_field' );

		foreach ( $data as $key => $value ) {
			if ( is_string( $value ) ) {
				$sanitized[ $key ] = call_user_func( $sanitize_function, $value );
			} elseif ( is_array( $value ) ) {
				$sanitized[ $key ] = $this->sanitize_data( $value );
			} else {
				$sanitized[ $key ] = $value;
			}
		}

		/**
		 * Filter the sanitized error report data.
		 *
		 * @since 3.4.0
		 *
		 * @param array<string, mixed> $sanitized Sanitized data.
		 * @param array<string, mixed> $data      Raw input data.
		 */
		return apply_filters( 'divi_squad_error_report_sanitized_data', $sanitized, $data );
	}

	/**
	 * Get formatted error messages
	 *
	 * Collects all error messages from the WP_Error object and formats them.
	 *
	 * @since 3.4.0
	 *
	 * @return array<string> Formatted error messages.
	 */
	protected function get_error_messages(): array {
		$messages = array();
		$codes    = $this->errors->get_error_codes();

		foreach ( $codes as $code ) {
			$code_messages = $this->errors->get_error_messages( $code );
			if ( count( $code_messages ) > 0 ) {
				foreach ( $code_messages as $message ) {
					if ( is_string( $message ) && '' !== $message ) {
						$messages[] = $message;
					}
				}
			}
		}

		/**
		 * Filter the formatted error messages.
		 *
		 * @since 3.4.0
		 *
		 * @param array<string> $messages Formatted error messages.
		 * @param WP_Error      $errors   The WP_Error object containing the errors.
		 */
		return apply_filters( 'divi_squad_error_report_formatted_messages', array_unique( $messages ), $this->errors );
	}

	/**
	 * Get error object
	 *
	 * @since 3.4.0
	 *
	 * @return WP_Error Error object.
	 */
	public function get_errors(): WP_Error {
		return $this->errors;
	}

	/**
	 * Get send result
	 *
	 * @since 3.4.0
	 *
	 * @return bool Result.
	 */
	public function get_result(): bool {
		return $this->result;
	}

	/**
	 * Send error report quickly
	 *
	 * Static helper method to quickly send an error report from an exception.
	 *
	 * @since 3.4.0
	 *
	 * @param Throwable            $throwable       Error/Exception object.
	 * @param array<string, mixed> $additional_data Additional context.
	 *
	 * @return bool Success status.
	 */
	public static function quick_send( Throwable $throwable, array $additional_data = array() ): bool {
		try {
			$error_data = array(
				'error_message' => $throwable->getMessage(),
				'error_code'    => $throwable->getCode(),
				'error_file'    => $throwable->getFile(),
				'error_line'    => $throwable->getLine(),
				'stack_trace'   => $throwable->getTraceAsString(),
				'debug_log'     => '',
				'request_data'  => array(
					'method' => isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '',
					'uri'    => isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '',
					'ip'     => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
				),
			);

			/**
			 * Filter whether to include debug log in error reports.
			 *
			 * @since 3.4.0
			 *
			 * @param bool $include_debug_log Whether to include debug log in error reports. Default true.
			 */
			$include_debug_log = apply_filters( 'divi_squad_error_report_include_debug_log', true );

			if ( $include_debug_log ) {
				$error_data['debug_log'] = Log_Reader::get_debug_log();
			}

			// Add Divi version info if applicable.
			$divi_version = Divi::get_builder_version();

			if ( '0.0.0' !== $divi_version ) {
				$error_data['divi_context'] = array(
					'version'          => $divi_version,
					'is_theme_active'  => class_exists( Divi::class ) && Divi::is_any_divi_theme_active(),
					'is_plugin_active' => class_exists( Divi::class ) && Divi::is_divi_builder_plugin_active(),
				);
			}

			if ( count( $additional_data ) > 0 ) {
				$error_data = array_merge( $error_data, $additional_data );
			}

			/**
			 * Filter the error data before quick sending an error report.
			 *
			 * @since 3.4.0
			 *
			 * @param array<string, mixed> $error_data      Error data to be sent.
			 * @param Throwable            $throwable       The exception that triggered the report.
			 * @param array<string, mixed> $additional_data Additional context data provided.
			 */
			$error_data = apply_filters( 'divi_squad_quick_error_report_data', $error_data, $throwable, $additional_data );

			return ( new self( $error_data ) )->send();
		} catch ( Throwable $e ) {
			// Last resort error handling
			divi_squad()->log_error( $e, 'Failed to send quick error report', false );

			return false;
		}
	}

	/**
	 * Static helper to reset the error reporting rate limit
	 *
	 * @since 3.4.0
	 *
	 * @return bool Success status
	 */
	public static function force_reset_rate_limit(): bool {
		try {
			return ( new Rate_Limiter() )->reset_rate_limit();
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Static error rate limit reset failed', false );

			return false;
		}
	}
}
