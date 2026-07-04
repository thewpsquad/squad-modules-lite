<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Error Report Email Handler (Legacy)
 *
 * Backwards compatibility layer for the old Error_Report class.
 * This class maintains the same API as the original class but delegates
 * functionality to the new error reporting system.
 *
 * @since       3.1.7
 * @deprecated  3.3.0 into a compatibility layer
 * @package     DiviSquad
 * @author      The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Managers\Emails;

use BadMethodCallException;
use DiviSquad\Core\Error\Log_File;
use DiviSquad\Core\Error\Error_Reporter;
use RuntimeException;
use Throwable;
use WP_Error;

/**
 * Error Report Email Handler (Legacy)
 *
 * Backwards compatibility layer for the old Error_Report class.
 * Maintains API compatibility while forwarding to new implementation.
 *
 * @since        3.1.7
 * @deprecated   3.3.0 Converted to a compatibility wrapper
 * @package      DiviSquad
 */
class ErrorReport {
	/**
	 * Support email recipient
	 *
	 * @since 3.1.7
	 * @var string
	 */
	protected string $to = 'support@squadmodules.com';

	/**
	 * Error report data
	 *
	 * @since 3.1.7
	 * @var array<string, mixed>
	 */
	protected array $data;

	/**
	 * WP_Error instance for error handling
	 *
	 * @since 3.1.7
	 * @var WP_Error
	 */
	protected WP_Error $errors;

	/**
	 * Email sending result
	 *
	 * @since 3.1.7
	 * @var bool
	 */
	protected bool $result = false;

	/**
	 * Rate limit key prefix
	 *
	 * @since 3.1.7
	 * @var string
	 */
	protected const RATE_LIMIT_KEY = 'squad_error_report_';

	/**
	 * Rate limit window in seconds (15 minutes)
	 *
	 * @since 3.1.7
	 * @var int
	 */
	protected const RATE_LIMIT_WINDOW = 900;

	/**
	 * Maximum reports per window
	 *
	 * @since 3.1.7
	 * @var int
	 */
	protected const MAX_REPORTS = 5;

	/**
	 * Required data fields
	 *
	 * @since 3.1.7
	 * @var array<string>
	 */
	protected const REQUIRED_FIELDS = array(
		'error_message',
		'error_code',
		'error_file',
		'error_line',
	);

	/**
	 * The new ErrorReporter instance that handles the actual work
	 *
	 * @since 3.4.0
	 * @var Reporter
	 */
	protected Reporter $reporter;

	/**
	 * Initialize error report
	 *
	 * Creates a new error report instance with sanitized data.
	 *
	 * @since 3.1.7
	 * @since 3.4.0 Modified to use the new ErrorReporter
	 *
	 * @param array<string, mixed> $data Error report data.
	 */
	public function __construct( array $data = array() ) {
		try {
			// Create the new error reporter
			$this->reporter = new Error_Reporter( $data );

			// Keep the old properties for backward compatibility
			$this->data   = $data;
			$this->errors = new WP_Error();

			/**
			 * Filter the recipient email address for error reports.
			 *
			 * @since 3.1.7
			 *
			 * @param string $to Default recipient email address.
			 */
			$this->to = apply_filters( 'divi_squad_error_report_recipient', $this->to );

			// Log deprecation notice if debug is enabled
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
				trigger_error(
					esc_html__( 'The DiviSquad\Emails\Error_Report class is deprecated. Please use DiviSquad\Core\Error\ErrorReporter instead.', 'squad-modules-for-divi' ),
					E_USER_DEPRECATED
				);
			}
		} catch ( Throwable $e ) {
			// Still create a valid object
			$this->data   = $data;
			$this->errors = new WP_Error();
		}
	}

	/**
	 * Send error report email with rate limiting and validation
	 *
	 * Delegates to the new ErrorReporter class.
	 *
	 * @since 3.1.7
	 * @since 3.4.0 Modified to delegate to ErrorReporter
	 *
	 * @return bool Success status.
	 * @throws RuntimeException If rate limit is exceeded or validation fails.
	 */
	public function send(): bool {
		try {
			// Delegate to new implementation
			$this->result = $this->reporter->send();

			// Get errors from the reporter for backward compatibility
			$this->errors = $this->reporter->get_errors();

			return $this->result;
		} catch ( Throwable $e ) {
			$this->errors->add( 'send_failed', $e->getMessage() );

			throw new RuntimeException( $e->getMessage(), $e->getCode(), $e ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}
	}

	/**
	 * Get error object
	 *
	 * @since 3.1.7
	 *
	 * @return WP_Error Error object.
	 */
	public function get_errors(): WP_Error {
		return $this->errors;
	}

	/**
	 * Get send result
	 *
	 * @since 3.1.7
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
	 * Delegates to the new ErrorReporter class.
	 *
	 * @since 3.1.7
	 * @since 3.4.0 Modified to delegate to ErrorReporter::quickSend()
	 *
	 * @param mixed                $exception       Error/Exception object.
	 * @param array<string, mixed> $additional_data Additional context.
	 *
	 * @return bool Success status.
	 */
	public static function quick_send( $exception, array $additional_data = array() ): bool {
		try {
			return Error_Reporter::quick_send( $exception, $additional_data );
		} catch ( Throwable $e ) {
			return false;
		}
	}

	/**
	 * Get the debug log
	 *
	 * Static helper for getting debug log.
	 * Delegates to the new ErrorLogReader class.
	 *
	 * @since 3.1.7
	 * @since 3.4.0 Modified to delegate to ErrorLogReader::getDebugLog()
	 *
	 * @return string The debug log or an empty string if not accessible.
	 */
	public static function get_debug_log(): string {
		try {
			return Log_File::get_debug_log();
		} catch ( Throwable $e ) {
			return 'Error reading debug log: ' . $e->getMessage();
		}
	}

	/**
	 * Read the last N lines from a file in a memory efficient way
	 *
	 * Delegates to ErrorLogReader class.
	 *
	 * @since 3.1.7
	 * @since 3.4.0 Modified to delegate to ErrorLogReader::readLastLines()
	 *
	 * @param string $file_path  Path to the file.
	 * @param int    $line_count Number of lines to read from end of file.
	 *
	 * @return string The last N lines of the file.
	 */
	public static function read_last_lines( string $file_path, int $line_count = 200 ): string {
		try {
			return Log_File::read_tail_lines( $file_path, $line_count );
		} catch ( Throwable $e ) {
			return 'Error reading file: ' . $e->getMessage();
		}
	}

	/**
	 * Forwards any calls to missing methods to the new ErrorReporter
	 *
	 * This helps maintain backward compatibility with code that might be
	 * calling methods that no longer exist in this class.
	 *
	 * @since 3.4.0
	 *
	 * @param string               $name      Method name.
	 * @param array<string, mixed> $arguments Method arguments.
	 *
	 * @return mixed
	 * @throws BadMethodCallException If the method doesn't exist on ErrorReporter either.
	 */
	public function __call( string $name, array $arguments ) {
		if ( isset( $this->reporter ) && method_exists( $this->reporter, $name ) ) {
			return call_user_func_array( array( $this->reporter, $name ), $arguments );
		}

		throw new BadMethodCallException(
			sprintf(
				'Call to undefined method %s::%s()',
				__CLASS__,
				$name // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			)
		);
	}
}
