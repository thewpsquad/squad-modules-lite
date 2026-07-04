<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Error Reporter
 *
 * High-performance error reporting system with optimized processing pipeline.
 *
 * @since   3.4.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Core\Error;

use RuntimeException;
use Throwable;
use WP_Error;

/**
 * Error_Reporter Class
 *
 * Streamlined error reporting with minimal overhead and maximum reliability.
 *
 * Processing Pipeline:
 * 1. Data validation (O(1))
 * 2. Duplicate detection (O(1))
 * 3. Rate limiting (O(1))
 * 4. Email dispatch (O(1))
 *
 * @since   3.4.0
 * @package DiviSquad
 */
class Error_Reporter {
	/**
	 * Required error data fields
	 */
	private const REQUIRED_FIELDS = array( 'error_message', 'error_file', 'error_line', 'error_code' );

	/**
	 * Component instances
	 */
	private Error_Mailer $mailer;
	private System_Info $system_info;
	private Rate_Limiter $rate_limiter;
	private Duplicate_Filter $duplicate_filter;
	private WP_Error $errors;

	/**
	 * Error data
	 *
	 * @var array<string, mixed>
	 */
	private array $data;

	/**
	 * Initialize error reporter
	 *
	 * @param array<string, mixed> $error_data Error data
	 */
	public function __construct( array $error_data = array() ) {
		$this->mailer           = new Error_Mailer();
		$this->system_info      = new System_Info();
		$this->rate_limiter     = new Rate_Limiter();
		$this->duplicate_filter = new Duplicate_Filter();
		$this->errors           = new WP_Error();

		$this->data = $this->sanitize_data( $error_data );
	}

	/**
	 * Send error report
	 *
	 * Main processing pipeline with fail-fast validation.
	 *
	 * @return bool Success status
	 */
	public function send(): bool {
		try {
			// Fast validation first
			if ( ! $this->validate() ) {
				throw new RuntimeException( 'Invalid error data: ' . implode( ', ', $this->get_error_messages() ) );
			}

			// Prepare complete data with environment
			$this->data['environment'] = $this->system_info->collect();
			$this->data['debug_log']   = $this->get_debug_log();
			$this->data                = $this->process_template_data( $this->data );

			// Apply filters and hooks
			$this->trigger_before_send_hooks();

			// Duplicate check
			if ( $this->duplicate_filter->is_duplicate( $this->data ) ) {
				/**
				 * Filters whether to bypass duplicate error checking.
				 *
				 * This filter allows developers to force sending of error reports even if they
				 * have already been sent within the tracking window. This is useful for critical
				 * errors that should always be reported regardless of duplication.
				 *
				 * @since 3.4.0
				 *
				 * @param bool                 $bypass_duplicate_check Whether to bypass duplicate checking. Default false.
				 * @param array<string, mixed> $error_data             Complete error report data including environment info.
				 */
				if ( ! apply_filters( 'divi_squad_bypass_duplicate_check', false, $this->data ) ) {
					$this->log_duplicate_skip();
					return true; // Successful skip
				}
			}

			// Rate limiting
			if ( ! $this->rate_limiter->can_send() && ! $this->handle_rate_limit_exceeded() ) {
				throw new RuntimeException( 'Rate limit exceeded' );
			}

			// Send email
			$success = $this->mailer->send( $this->data, $this->errors );

			if ( $success ) {
				$this->handle_send_success();
			} else {
				$this->handle_send_failure();
			}

			return $success;

		} catch ( Throwable $e ) {
			return $this->handle_send_exception( $e );
		}
	}

	/**
	 * Validate error data
	 *
	 * @return bool Is valid
	 */
	private function validate(): bool {
		/**
		 * Filters the required fields for error report validation.
		 *
		 * This filter allows developers to customize which fields are considered required
		 * before an error report can be sent. By default, error message, file, line, and
		 * code are required.
		 *
		 * @since 3.4.0
		 *
		 * @param array<string> $required_fields List of required field names. Default: error_message, error_file, error_line, error_code.
		 */
		$required_fields = apply_filters( 'divi_squad_required_error_fields', self::REQUIRED_FIELDS );

		foreach ( $required_fields as $field ) {
			if ( empty( $this->data[ $field ] ) ) {
				$this->errors->add( $field, "Required field '{$field}' is missing" );
			}
		}

		return ! $this->errors->has_errors();
	}

	/**
	 * Sanitize input data
	 *
	 * @param array<string, mixed> $data Raw data
	 *
	 * @return array<string, mixed> Sanitized data
	 */
	private function sanitize_data( array $data ): array {
		$sanitized = array();

		foreach ( $data as $key => $value ) {
			if ( is_string( $value ) ) {
				$sanitized[ $key ] = sanitize_text_field( $value );
			} elseif ( is_array( $value ) ) {
				$sanitized[ $key ] = $this->sanitize_data( $value );
			} else {
				$sanitized[ $key ] = $value;
			}
		}

		return $sanitized;
	}

	/**
	 * Process template data
	 *
	 * @param array<string, mixed> $data Raw error data
	 *
	 * @return array<string, mixed> Processed data
	 */
	private function process_template_data( array $data ): array {
		// Add standard fields
		$data['site_url']        = home_url();
		$data['site_name']       = get_bloginfo( 'name' );
		$data['timestamp']       = current_time( 'mysql' );
		$data['error_reference'] = substr( md5( uniqid( (string) wp_rand(), true ) ), 0, 8 );

		// Determine error severity
		$data['severity'] = $this->determine_severity( $data );

		// Process file path
		$data['relative_file_path'] = $this->get_relative_path( $data['error_file'] ?? '' );

		/**
		 * Filters the processed error report data before sending.
		 *
		 * This filter allows developers to modify the complete error report data after all
		 * standard processing has been completed. This includes adding custom fields,
		 * modifying existing data, or removing sensitive information.
		 *
		 * @since 3.4.0
		 *
		 * @param array<string, mixed> $data Complete processed error report data including:
		 *                                   - site_url: Home URL of the site
		 *                                   - site_name: Site title
		 *                                   - timestamp: Current MySQL timestamp
		 *                                   - error_reference: Unique 8-character reference ID
		 *                                   - severity: Error severity level (high/medium/low)
		 *                                   - relative_file_path: File path relative to plugin directory
		 *                                   - environment: Complete system environment data
		 *                                   - debug_log: WordPress debug log content
		 */
		return apply_filters( 'divi_squad_processed_error_data', $data );
	}

	/**
	 * Determine error severity
	 *
	 * @param array<string, mixed> $data Error data
	 *
	 * @return string Severity level
	 */
	private function determine_severity( array $data ): string {
		$message = strtolower( $data['error_message'] ?? '' );

		if ( str_contains( $message, 'fatal' ) || str_contains( $message, 'critical' ) ) {
			return 'high';
		}

		if ( str_contains( $message, 'warning' ) ) {
			return 'medium';
		}

		if ( str_contains( $message, 'notice' ) ) {
			return 'low';
		}

		return 'medium';
	}

	/**
	 * Get relative file path
	 *
	 * @param string $file_path Full file path
	 *
	 * @return string Relative path
	 */
	private function get_relative_path( string $file_path ): string {
		$plugin_path = WP_PLUGIN_DIR . '/squad-modules-for-divi/';

		if ( str_starts_with( $file_path, $plugin_path ) ) {
			return substr( $file_path, strlen( $plugin_path ) );
		}

		return $file_path;
	}

	/**
	 * Get debug log content
	 *
	 * @return string Debug log
	 */
	private function get_debug_log(): string {
		/**
		 * Filters whether to include WordPress debug log in error reports.
		 *
		 * This filter allows developers to control whether the WordPress debug.log file
		 * content should be included in error reports. The debug log can provide valuable
		 * context for debugging but may contain sensitive information.
		 *
		 * @since 3.4.0
		 *
		 * @param bool $include_debug_log Whether to include debug log content. Default true.
		 */
		if ( ! apply_filters( 'divi_squad_include_debug_log', true ) ) {
			return '';
		}

		/**
		 * Filters the number of debug log lines to include in error reports.
		 *
		 * This filter controls how many lines from the end of the WordPress debug.log
		 * file should be included in error reports. More lines provide more context
		 * but increase email size.
		 *
		 * @since 3.4.0
		 *
		 * @param int $line_count Number of lines to include from debug log. Default 50.
		 */
		$line_count = apply_filters( 'divi_squad_debug_log_lines', 50 );
		return Log_File::get_debug_log( $line_count );
	}

	/**
	 * Handle rate limit exceeded
	 *
	 * @return bool Whether to continue
	 */
	private function handle_rate_limit_exceeded(): bool {
		$is_critical = $this->data['is_critical'] ?? false;

		/**
		 * Filters whether to force reset the rate limit for this error report.
		 *
		 * This filter allows developers to bypass rate limiting for specific error types.
		 * By default, only errors marked as 'is_critical' will bypass rate limiting.
		 * Use this filter to define custom logic for critical errors.
		 *
		 * @since 3.4.0
		 *
		 * @param bool                 $force_reset Whether to force reset rate limit. Default is the value of 'is_critical' flag.
		 * @param array<string, mixed> $error_data  Complete error report data.
		 */
		$force_reset = apply_filters( 'divi_squad_force_reset_rate_limit', $is_critical, $this->data );

		if ( $force_reset ) {
			$this->rate_limiter->reset();
			return true;
		}

		return false;
	}

	/**
	 * Handle successful send
	 */
	private function handle_send_success(): void {
		$this->rate_limiter->increment();
		$this->duplicate_filter->mark_reported( $this->data );

		/**
		 * Fires after an error report has been successfully sent.
		 *
		 * This action allows developers to perform additional operations after a successful
		 * error report, such as logging to custom systems, updating database records,
		 * or sending notifications.
		 *
		 * @since 3.4.0
		 *
		 * @param array<string, mixed> $error_data Complete error report data that was sent.
		 */
		do_action( 'divi_squad_error_reported', $this->data );
		divi_squad()->log( 'INFO', 'Error report sent successfully', 'Error_Reporter' );
	}

	/**
	 * Handle send failure
	 */
	private function handle_send_failure(): void {
		/**
		 * Fires when an error report fails to send.
		 *
		 * This action allows developers to handle error report failures, such as
		 * attempting alternative sending methods, logging failures, or alerting
		 * administrators about email delivery issues.
		 *
		 * @since 3.4.0
		 *
		 * @param array<string, mixed> $error_data Complete error report data that failed to send.
		 * @param WP_Error             $errors     WP_Error object containing failure details.
		 */
		do_action( 'divi_squad_error_report_failed', $this->data, $this->errors );
		divi_squad()->log( 'WARNING', 'Error report failed to send', 'Error_Reporter' );
	}

	/**
	 * Handle send exception
	 *
	 * @param Throwable $e Exception
	 *
	 * @return bool Always false
	 */
	private function handle_send_exception( Throwable $e ): bool {
		$this->errors->add( 'send_exception', $e->getMessage() );
		divi_squad()->log_error( $e, 'Error reporting exception', false );

		/**
		 * Fires when an exception occurs during error report processing.
		 *
		 * This action is triggered when an unexpected exception occurs during the error
		 * reporting process. This is different from a normal send failure and indicates
		 * a more serious issue with the error reporting system itself.
		 *
		 * @since 3.4.0
		 *
		 * @param Throwable            $exception  The exception that occurred during processing.
		 * @param array<string, mixed> $error_data Complete error report data being processed.
		 * @param WP_Error             $errors     WP_Error object containing any accumulated errors.
		 */
		do_action( 'divi_squad_error_report_exception', $e, $this->data, $this->errors );

		return false;
	}

	/**
	 * Trigger before-send hooks
	 */
	private function trigger_before_send_hooks(): void {
		/**
		 * Fires before an error report is processed and sent.
		 *
		 * This action allows developers to modify error data, perform validation,
		 * or execute custom logic before the error report goes through the normal
		 * processing pipeline (duplicate checking, rate limiting, email sending).
		 *
		 * @since 3.4.0
		 *
		 * @param array<string, mixed> $error_data Error report data about to be processed.
		 * @param WP_Error             $errors     WP_Error object for collecting any validation errors.
		 */
		do_action( 'divi_squad_before_error_report', $this->data, $this->errors );
	}

	/**
	 * Log duplicate skip
	 */
	private function log_duplicate_skip(): void {
		$message = 'Duplicate error skipped: ' . ( $this->data['error_message'] ?? 'Unknown' );
		divi_squad()->log( 'INFO', $message, 'Error_Reporter' );

		/**
		 * Fires when a duplicate error report is skipped.
		 *
		 * This action is triggered when an error report is identified as a duplicate
		 * and is not sent to avoid spam. This allows developers to track duplicate
		 * errors for analysis or implement custom handling.
		 *
		 * @since 3.4.0
		 *
		 * @param array<string, mixed> $error_data Complete error report data that was skipped.
		 */
		do_action( 'divi_squad_duplicate_error_skipped', $this->data );
	}

	/**
	 * Get error messages
	 *
	 * @return array<string> Error messages
	 */
	private function get_error_messages(): array {
		$messages = array();

		foreach ( $this->errors->get_error_codes() as $code ) {
			$messages = array_merge( $messages, $this->errors->get_error_messages( $code ) );
		}

		return $messages;
	}

	/**
	 * Quick send from throwable
	 *
	 * @param Throwable            $throwable Error/Exception
	 * @param array<string, mixed> $context   Additional context
	 *
	 * @return bool Success
	 */
	public static function quick_send( Throwable $throwable, array $context = array() ): bool {
		try {
			$error_data = array(
				'error_message' => $throwable->getMessage(),
				'error_code'    => $throwable->getCode(),
				'error_file'    => $throwable->getFile(),
				'error_line'    => $throwable->getLine(),
				'stack_trace'   => $throwable->getTraceAsString(),
				'request_uri'   => $_SERVER['REQUEST_URI'] ?? '',
				'user_agent'    => $_SERVER['HTTP_USER_AGENT'] ?? '',
			);

			if ( array() !== $context ) {
				$error_data['extra_context'] = $context;
			}

			return ( new self( $error_data ) )->send();

		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Quick send failed', false );
			return false;
		}
	}

	/**
	 * Get errors
	 *
	 * @return WP_Error Errors
	 */
	public function get_errors(): WP_Error {
		return $this->errors;
	}

	// Static utility methods

	/**
	 * Clear all tracked errors
	 *
	 * @return bool Success
	 */
	public static function clear_tracked_errors(): bool {
		return ( new Duplicate_Filter() )->clear_all();
	}

	/**
	 * Reset rate limit
	 *
	 * @return bool Success
	 */
	public static function reset_rate_limit(): bool {
		return ( new Rate_Limiter() )->reset();
	}

	/**
	 * Get error statistics
	 *
	 * @return array<string, mixed> Statistics
	 */
	public static function get_stats(): array {
		$duplicate_filter = new Duplicate_Filter();
		$rate_limiter     = new Rate_Limiter();

		return array(
			'tracked_errors'       => $duplicate_filter->get_count(),
			'rate_limit_remaining' => $rate_limiter->get_remaining(),
			'rate_window_expires'  => $rate_limiter->get_window_expires(),
		);
	}
}
