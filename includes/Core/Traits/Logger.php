<?php // phpcs:disable WordPress.Files.FileName, WordPress.PHP.DevelopmentFunctions

namespace DiviSquad\Core\Traits;

use DiviSquad\Managers\Emails\ErrorReport;
use Throwable;

/**
 * Logger Trait
 *
 * Provides common logging functionality for WordPress plugins.
 *
 * @since      3.2.0
 * @package    DiviSquad
 * @subpackage Base\Traits
 */
trait Logger {

	/**
	 * Plugin identifier for log messages.
	 *
	 * @since 3.2.0
	 * @var string
	 */
	protected string $log_identifier = 'Squad Modules';

	/**
	 * Set the log identifier for this instance.
	 *
	 * @since 3.2.0
	 * @access public
	 *
	 * @param string $identifier The identifier to use in log messages.
	 * @return void
	 */
	public function set_log_identifier( string $identifier ): void {
		$this->log_identifier = $identifier;
	}

	/**
	 * Format and write a log message.
	 *
	 * @since 3.2.0
	 * @access protected
	 *
	 * @param string $level   Log level (ERROR, DEBUG, etc.).
	 * @param mixed  $message Message to log.
	 * @param string $context Context identifier.
	 * @param array  $data    Additional data to log.
	 * @return void
	 */
	protected function write_log( string $level, $message, string $context = 'General', array $data = array() ): void {
		$log_message = sprintf(
			'[%s] [%s]: [%s] %s',
			$this->log_identifier,
			$level,
			$context,
			is_string( $message ) ? $message : print_r( $message, true )
		);

		if ( ! empty( $data ) ) {
			$log_message .= "\nData: " . wp_json_encode( $data, JSON_PRETTY_PRINT );
		}

		error_log( $log_message );
	}

	/**
	 * Format error details for logging.
	 *
	 * @since 3.2.0
	 * @access protected
	 *
	 * @param Throwable $error   Error object to format.
	 * @return string
	 */
	protected function format_error_message( Throwable $error ): string {
		return sprintf(
			'%s in %s on line %d',
			$error->getMessage(),
			$error->getFile(),
			$error->getLine()
		);
	}

	/**
	 * Add debug backtrace to log message if debug mode is enabled.
	 *
	 * @since 3.2.0
	 * @access protected
	 *
	 * @param Throwable $error Error object with stack trace.
	 * @return void
	 */
	protected function log_debug_trace( Throwable $error ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$this->write_log( 'DEBUG', "Stack trace:\n" . $error->getTraceAsString() );
		}
	}

	/**
	 * Log a deprecated notice.
	 *
	 * @since 3.2.0
	 * @access public
	 *
	 * @param string $feature     The deprecated feature.
	 * @param string $version     Version since deprecation.
	 * @param string $replacement Replacement feature if any.
	 * @return void
	 */
	public function log_deprecated( string $feature, string $version, string $replacement = '' ): void {
		$message = sprintf(
			'%s has been deprecated since version %s.',
			$feature,
			$version
		);

		if ( $replacement ) {
			$message .= sprintf( ' Use %s instead.', $replacement );
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			trigger_error(
				esc_html( $message ),
				E_USER_DEPRECATED
			);
		}

		$this->log_debug( $message, 'Deprecated' );
	}

	/**
	 * Log an error message.
	 *
	 * @since 3.2.0
	 * @access public
	 *
	 * @param Throwable $error       The error that occurred.
	 * @param string    $context     Error context description.
	 * @param bool      $send_report Whether to send an error report.
	 * @param array     $extra_data  Additional data to include.
	 * @return void
	 */
	public function log_error( Throwable $error, string $context, bool $send_report = true, array $extra_data = array() ): void {
		$error_message = $this->format_error_message( $error );
		$this->write_log( 'ERROR', $error_message, $context, $extra_data );
		$this->log_debug_trace( $error );

		if ( $send_report ) {
			$this->send_error_report( $error, $context, $extra_data );
		}

		/**
		 * Fires after an error has been logged.
		 *
		 * @since 3.2.0
		 *
		 * @param Throwable $error      The error that occurred.
		 * @param string    $context    The error context.
		 * @param array     $extra_data The error data including environment info.
		 */
		do_action( 'divi_squad_after_error_logged', $error, $context, $extra_data );
	}

	/**
	 * Log a debug message.
	 *
	 * @since 3.2.0
	 * @access public
	 *
	 * @param mixed  $message Debug message to log.
	 * @param string $context Context identifier.
	 * @param array  $data    Additional debug data.
	 * @return void
	 */
	public function log_debug( $message, string $context = 'General', array $data = array() ): void {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		$this->write_log( 'DEBUG', $message, $context, $data );
	}

	/**
	 * Log an informational message.
	 *
	 * @since 3.2.0
	 * @access public
	 *
	 * @param mixed  $message Informational message to log.
	 * @param string $context Context identifier.
	 * @param array  $data    Additional data.
	 * @return void
	 */
	protected function log_info( $message, string $context = 'General', array $data = array() ): void {
		$this->write_log( 'INFO', $message, $context, $data );
	}

	/**
	 * Log a warning message.
	 *
	 * @since 3.2.0
	 * @access public
	 *
	 * @param mixed  $message Warning message to log.
	 * @param string $context Context identifier.
	 * @param array  $data    Additional data.
	 * @return void
	 */
	protected function log_warning( $message, string $context = 'General', array $data = array() ): void {
		$this->write_log( 'WARNING', $message, $context, $data );
	}

	/**
	 * Log a notice message.
	 *
	 * @since 3.2.0
	 * @access public
	 *
	 * @param mixed  $message Notice message to log.
	 * @param string $context Context identifier.
	 * @param array  $data    Additional data.
	 * @return void
	 */
	protected function log_notice( $message, string $context = 'General', array $data = array() ): void {
		$this->write_log( 'NOTICE', $message, $context, $data );
	}

	/**
	 * Log an error message.
	 *
	 * @since 3.2.0
	 * @access public
	 *
	 * @param mixed  $message Error message to log.
	 * @param string $context Context identifier.
	 * @param array  $data    Additional data.
	 * @return void
	 */
	protected function log_critical( $message, string $context = 'General', array $data = array() ): void {
		$this->write_log( 'CRITICAL', $message, $context, $data );
	}

	/**
	 * Send an error report.
	 *
	 * @since 3.2.0
	 * @access protected
	 *
	 * @param Throwable $error      The error that occurred.
	 * @param string    $context    Error context description.
	 * @param array     $extra_data Additional data to include.
	 * @return void
	 */
	protected function send_error_report( Throwable $error, string $context, array $extra_data = array() ): void {
		try {
			// Send error report.
			ErrorReport::quick_send(
				$error,
				array(
					'additional_info' => $context,
					'extra_data'      => $extra_data,
				)
			);
		} catch ( Throwable $e ) {
			$this->write_log( 'ERROR', 'Error sending error report: ' . $e->getMessage(), $context, $extra_data );
		}
	}
}
