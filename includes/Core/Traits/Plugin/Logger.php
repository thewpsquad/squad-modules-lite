<?php // phpcs:disable WordPress.Files.FileName, WordPress.PHP.DevelopmentFunctions

namespace DiviSquad\Core\Traits\Plugin;

use DiviSquad\Emails\ErrorReport;
use Throwable;

/**
 * Logger Trait
 *
 * Provides a comprehensive logging system for WordPress plugins with support for
 * different log levels, error reporting, debug information, and WordPress hooks
 * for extensibility.
 *
 * Features:
 * - Multiple log levels (ERROR, WARNING, INFO, DEBUG, NOTICE, CRITICAL)
 * - Integration with WordPress error logging
 * - Optional error reporting via email
 * - Stack trace logging for errors
 * - Customizable log identifier
 * - WordPress hooks for extending logging functionality
 *
 * @since      3.2.0
 * @package    DiviSquad\Core\Traits\Plugin
 * @author     The WP Squad <support@squadmodules.com>
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
	 * Whether to suppress all log messages.
	 *
	 * @since 4.0.0
	 * @var bool
	 */
	protected bool $suppress_logs = false;

	/**
	 * Log levels with their priority values.
	 *
	 * Lower numbers indicate higher priority.
	 *
	 * @since 4.0.0
	 * @var array<string, int>
	 */
	protected static array $log_levels = array(
		'EMERGENCY' => 0,
		'ALERT'     => 1,
		'CRITICAL'  => 2,
		'ERROR'     => 3,
		'WARNING'   => 4,
		'NOTICE'    => 5,
		'INFO'      => 6,
		'DEBUG'     => 7,
	);

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
	 * Enable or disable logging.
	 *
	 * @since 4.0.0
	 * @access public
	 *
	 * @param bool $suppress Whether to suppress all logs.
	 * @return void
	 */
	public function set_suppress_logs( bool $suppress ): void {
		$this->suppress_logs = $suppress;
	}

	/**
	 * Format and write a log message.
	 *
	 * Formats a log message with the appropriate prefix and writes it to the
	 * WordPress error log. Also applies filters to allow customization of
	 * log handling.
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
		// Don't log if suppressed
		if ( $this->suppress_logs ) {
			return;
		}

		// Don't log debug messages unless WP_DEBUG is true
		if ( 'DEBUG' === $level && ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) ) {
			return;
		}

		// Format the message
		$formatted_message = $this->format_log_message( $level, $message, $context, $data );

		/**
		 * Filter whether to proceed with logging the message.
		 *
		 * @since 4.0.0
		 *
		 * @param bool   $do_log     Whether to log this message.
		 * @param string $level      Log level.
		 * @param mixed  $message    The message being logged.
		 * @param string $context    The context identifier.
		 * @param array  $data       Additional data for the log.
		 * @param string $formatted  The formatted log message.
		 */
		$do_log = apply_filters(
			'divi_squad_do_log',
			true,
			$level,
			$message,
			$context,
			$data,
			$formatted_message
		);

		if ( ! $do_log ) {
			return;
		}

		/**
		 * Action triggered before a message is logged.
		 *
		 * @since 4.0.0
		 *
		 * @param string $level      Log level.
		 * @param mixed  $message    The message being logged.
		 * @param string $context    The context identifier.
		 * @param array  $data       Additional data for the log.
		 * @param string $formatted  The formatted log message.
		 */
		do_action(
			'divi_squad_before_log',
			$level,
			$message,
			$context,
			$data,
			$formatted_message
		);

		// Actually write to the error log
		error_log( $formatted_message );

		/**
		 * Action triggered after a message is logged.
		 *
		 * @since 4.0.0
		 *
		 * @param string $level      Log level.
		 * @param mixed  $message    The message being logged.
		 * @param string $context    The context identifier.
		 * @param array  $data       Additional data for the log.
		 * @param string $formatted  The formatted log message.
		 */
		do_action(
			'divi_squad_after_log',
			$level,
			$message,
			$context,
			$data,
			$formatted_message
		);
	}

	/**
	 * Format a log message.
	 *
	 * Creates a standardized format for log messages including the
	 * log identifier, level, context, and message.
	 *
	 * @since 4.0.0
	 * @access protected
	 *
	 * @param string $level   Log level (ERROR, DEBUG, etc.).
	 * @param mixed  $message The message to format.
	 * @param string $context The context identifier.
	 * @param array  $data    Additional data to include.
	 * @return string The formatted log message.
	 */
	protected function format_log_message( string $level, $message, string $context, array $data ): string {
		$log_message = sprintf(
			'[%s] [%s]: [%s] %s',
			$this->log_identifier,
			$level,
			$context,
			is_string( $message ) ? $message : wp_json_encode( $message )
		);

		if ( ! empty( $data ) ) {
			$log_message .= "\nData: " . wp_json_encode( $data, JSON_PRETTY_PRINT );
		}

		/**
		 * Filter the formatted log message.
		 *
		 * @since 4.0.0
		 *
		 * @param string $log_message The formatted log message.
		 * @param string $level       Log level.
		 * @param mixed  $message     The original message.
		 * @param string $context     The context identifier.
		 * @param array  $data        Additional data.
		 */
		return apply_filters(
			'divi_squad_formatted_log_message',
			$log_message,
			$level,
			$message,
			$context,
			$data
		);
	}

	/**
	 * Format error details for logging.
	 *
	 * Creates a standardized string representation of an error or exception.
	 *
	 * @since 3.2.0
	 * @access protected
	 *
	 * @param Throwable $error Error object to format.
	 * @return string Formatted error message.
	 */
	protected function format_error_message( Throwable $error ): string {
		$message = sprintf(
			'%s in %s on line %d',
			$error->getMessage(),
			$error->getFile(),
			$error->getLine()
		);

		/**
		 * Filter the formatted error message.
		 *
		 * @since 4.0.0
		 *
		 * @param string    $message The formatted error message.
		 * @param Throwable $error   The error object.
		 */
		return apply_filters( 'divi_squad_formatted_error_message', $message, $error );
	}

	/**
	 * Add debug backtrace to log message if debug mode is enabled.
	 *
	 * Logs the stack trace of an error when WordPress debug mode is enabled.
	 *
	 * @since 3.2.0
	 * @access protected
	 *
	 * @param Throwable $error Error object with stack trace.
	 * @return void
	 */
	protected function log_debug_trace( Throwable $error ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$trace = $error->getTraceAsString();

			/**
			 * Filter the stack trace before logging.
			 *
			 * @since 4.0.0
			 *
			 * @param string    $trace The stack trace.
			 * @param Throwable $error The error object.
			 */
			$trace = apply_filters( 'divi_squad_debug_trace', $trace, $error );

			$this->write_log( 'DEBUG', "Stack trace:\n" . $trace );
		}
	}

	/**
	 * Log a deprecated notice.
	 *
	 * Records a message about deprecated functionality including the version
	 * when it was deprecated and any replacement functionality.
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

		// Trigger a deprecated notice in debug mode
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			trigger_error(
				esc_html( $message ),
				E_USER_DEPRECATED
			);
		}

		// Also log the deprecation notice
		$data = array(
			'version'     => $version,
			'replacement' => $replacement,
		);

		/**
		 * Action triggered when logging a deprecated feature.
		 *
		 * @since 4.0.0
		 *
		 * @param string $feature     The deprecated feature.
		 * @param string $version     Version since deprecation.
		 * @param string $replacement Replacement feature if any.
		 * @param string $message     The formatted deprecation message.
		 */
		do_action( 'divi_squad_log_deprecated', $feature, $version, $replacement, $message );

		$this->log_debug( $message, 'Deprecated', $data );
	}

	/**
	 * Log an error message.
	 *
	 * Records an error message including details from the error object
	 * and optional additional data. Can also send an error report email.
	 *
	 * @since 3.2.0
	 * @access public
	 *
	 * @param Throwable $error      The error that occurred.
	 * @param string    $context    Error context description.
	 * @param bool      $report     Whether to send an error report.
	 * @param array     $extra_data Additional data to include.
	 *
	 * @return void
	 */
	public function log_error( Throwable $error, string $context, bool $report = true, array $extra_data = array() ): void {
		$error_message = $this->format_error_message( $error );

		/**
		 * Filter whether to log this error.
		 *
		 * @since 4.0.0
		 *
		 * @param bool      $log_error  Whether to log this error.
		 * @param Throwable $error      The error object.
		 * @param string    $context    The error context.
		 * @param array     $extra_data Additional data.
		 */
		$log_error = apply_filters( 'divi_squad_log_error', true, $error, $context, $extra_data );

		if ( ! $log_error ) {
			return;
		}

		$this->write_log( 'ERROR', $error_message, $context, $extra_data );
		$this->log_debug_trace( $error );

		// Send report if requested
		if ( $report ) {
			/**
			 * Filter whether to send an error report email.
			 *
			 * @since 4.0.0
			 *
			 * @param bool      $send_report Whether to send an error report.
			 * @param Throwable $error       The error object.
			 * @param string    $context     The error context.
			 * @param array     $extra_data  Additional data.
			 */
			$send_report = apply_filters( 'divi_squad_send_error_report', true, $error, $context, $extra_data );

			if ( $send_report ) {
				$this->send_error_report( $error, $context, $extra_data );
			}
		}

		/**
		 * Action triggered after an error has been logged.
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
	 * Records a debug message only when WordPress debug mode is enabled.
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

		/**
		 * Filter whether to log this debug message.
		 *
		 * @since 4.0.0
		 *
		 * @param bool   $log_debug Whether to log this debug message.
		 * @param mixed  $message   The debug message.
		 * @param string $context   The context identifier.
		 * @param array  $data      Additional data.
		 */
		$log_debug = apply_filters( 'divi_squad_log_debug', true, $message, $context, $data );

		if ( $log_debug ) {
			$this->write_log( 'DEBUG', $message, $context, $data );
		}
	}

	/**
	 * Log an informational message.
	 *
	 * Records an informational message for general status updates.
	 *
	 * @since 3.2.0
	 * @access public
	 *
	 * @param mixed  $message Informational message to log.
	 * @param string $context Context identifier.
	 * @param array  $data    Additional data.
	 * @return void
	 */
	public function log_info( $message, string $context = 'General', array $data = array() ): void {
		/**
		 * Filter whether to log this info message.
		 *
		 * @since 4.0.0
		 *
		 * @param bool   $log_info Whether to log this info message.
		 * @param mixed  $message  The info message.
		 * @param string $context  The context identifier.
		 * @param array  $data     Additional data.
		 */
		$log_info = apply_filters( 'divi_squad_log_info', true, $message, $context, $data );

		if ( $log_info ) {
			$this->write_log( 'INFO', $message, $context, $data );
		}
	}

	/**
	 * Log a warning message.
	 *
	 * Records a warning message for potential issues that don't prevent
	 * normal operation.
	 *
	 * @since 3.2.0
	 * @access public
	 *
	 * @param mixed  $message Warning message to log.
	 * @param string $context Context identifier.
	 * @param array  $data    Additional data.
	 * @return void
	 */
	public function log_warning( $message, string $context = 'General', array $data = array() ): void {
		/**
		 * Filter whether to log this warning message.
		 *
		 * @since 4.0.0
		 *
		 * @param bool   $log_warning Whether to log this warning message.
		 * @param mixed  $message     The warning message.
		 * @param string $context     The context identifier.
		 * @param array  $data        Additional data.
		 */
		$log_warning = apply_filters( 'divi_squad_log_warning', true, $message, $context, $data );

		if ( $log_warning ) {
			$this->write_log( 'WARNING', $message, $context, $data );
		}
	}

	/**
	 * Log a notice message.
	 *
	 * Records a notice for normal but significant events.
	 *
	 * @since 3.2.0
	 * @access public
	 *
	 * @param mixed  $message Notice message to log.
	 * @param string $context Context identifier.
	 * @param array  $data    Additional data.
	 * @return void
	 */
	public function log_notice( $message, string $context = 'General', array $data = array() ): void {
		/**
		 * Filter whether to log this notice message.
		 *
		 * @since 4.0.0
		 *
		 * @param bool   $log_notice Whether to log this notice message.
		 * @param mixed  $message    The notice message.
		 * @param string $context    The context identifier.
		 * @param array  $data       Additional data.
		 */
		$log_notice = apply_filters( 'divi_squad_log_notice', true, $message, $context, $data );

		if ( $log_notice ) {
			$this->write_log( 'NOTICE', $message, $context, $data );
		}
	}

	/**
	 * Log a critical message.
	 *
	 * Records a critical message for severe issues that require
	 * immediate attention.
	 *
	 * @since 3.2.0
	 * @access public
	 *
	 * @param mixed  $message Critical message to log.
	 * @param string $context Context identifier.
	 * @param array  $data    Additional data.
	 * @return void
	 */
	public function log_critical( $message, string $context = 'General', array $data = array() ): void {
		/**
		 * Filter whether to log this critical message.
		 *
		 * @since 4.0.0
		 *
		 * @param bool   $log_critical Whether to log this critical message.
		 * @param mixed  $message      The critical message.
		 * @param string $context      The context identifier.
		 * @param array  $data         Additional data.
		 */
		$log_critical = apply_filters( 'divi_squad_log_critical', true, $message, $context, $data );

		if ( $log_critical ) {
			$this->write_log( 'CRITICAL', $message, $context, $data );
		}
	}

	/**
	 * Log an emergency message.
	 *
	 * Records an emergency message for the most severe issues that
	 * require immediate action.
	 *
	 * @since 4.0.0
	 * @access public
	 *
	 * @param mixed  $message Emergency message to log.
	 * @param string $context Context identifier.
	 * @param array  $data    Additional data.
	 * @return void
	 */
	public function log_emergency( $message, string $context = 'General', array $data = array() ): void {
		/**
		 * Filter whether to log this emergency message.
		 *
		 * @since 4.0.0
		 *
		 * @param bool   $log_emergency Whether to log this emergency message.
		 * @param mixed  $message       The emergency message.
		 * @param string $context       The context identifier.
		 * @param array  $data          Additional data.
		 */
		$log_emergency = apply_filters( 'divi_squad_log_emergency', true, $message, $context, $data );

		if ( $log_emergency ) {
			$this->write_log( 'EMERGENCY', $message, $context, $data );
		}
	}

	/**
	 * Log an alert message.
	 *
	 * Records an alert message for issues that require immediate action
	 * but are not as severe as emergencies.
	 *
	 * @since 4.0.0
	 * @access public
	 *
	 * @param mixed  $message Alert message to log.
	 * @param string $context Context identifier.
	 * @param array  $data    Additional data.
	 * @return void
	 */
	public function log_alert( $message, string $context = 'General', array $data = array() ): void {
		/**
		 * Filter whether to log this alert message.
		 *
		 * @since 4.0.0
		 *
		 * @param bool   $log_alert Whether to log this alert message.
		 * @param mixed  $message   The alert message.
		 * @param string $context   The context identifier.
		 * @param array  $data      Additional data.
		 */
		$log_alert = apply_filters( 'divi_squad_log_alert', true, $message, $context, $data );

		if ( $log_alert ) {
			$this->write_log( 'ALERT', $message, $context, $data );
		}
	}

	/**
	 * Log a message at any level.
	 *
	 * Generic logging method that allows specifying the log level.
	 *
	 * @since 4.0.0
	 * @access public
	 *
	 * @param string $level   Log level (ERROR, DEBUG, etc.).
	 * @param mixed  $message Message to log.
	 * @param string $context Context identifier.
	 * @param array  $data    Additional data.
	 * @return void
	 */
	public function log( string $level, $message, string $context = 'General', array $data = array() ): void {
		// Ensure level is uppercase for consistency
		$level = strtoupper( $level );

		// Validate log level
		if ( ! isset( self::$log_levels[ $level ] ) ) {
			$level = 'INFO';
		}

		/**
		 * Filter whether to log this message.
		 *
		 * @since 4.0.0
		 *
		 * @param bool   $do_log  Whether to log this message.
		 * @param string $level   Log level.
		 * @param mixed  $message The message.
		 * @param string $context The context identifier.
		 * @param array  $data    Additional data.
		 */
		$do_log = apply_filters( 'divi_squad_log_message', true, $level, $message, $context, $data );

		if ( $do_log ) {
			$this->write_log( $level, $message, $context, $data );
		}
	}

	/**
	 * Send an error report.
	 *
	 * Sends an error report email with details about the error.
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
			/**
			 * Action triggered before sending an error report.
			 *
			 * @since 4.0.0
			 *
			 * @param Throwable $error      The error that occurred.
			 * @param string    $context    The error context.
			 * @param array     $extra_data Additional data.
			 */
			do_action( 'divi_squad_before_send_error_report', $error, $context, $extra_data );

			// Send error report.
			$result = ErrorReport::quick_send(
				$error,
				array(
					'additional_info' => $context,
					'extra_data'      => $extra_data,
				)
			);

			/**
			 * Action triggered after sending an error report.
			 *
			 * @since 4.0.0
			 *
			 * @param bool      $result     Whether the error report was sent successfully.
			 * @param Throwable $error      The error that occurred.
			 * @param string    $context    The error context.
			 * @param array     $extra_data Additional data.
			 */
			do_action( 'divi_squad_after_send_error_report', $result, $error, $context, $extra_data );

		} catch ( Throwable $e ) {
			$this->write_log( 'ERROR', 'Error sending error report: ' . $e->getMessage(), $context, $extra_data );

			/**
			 * Action triggered when sending an error report fails.
			 *
			 * @since 4.0.0
			 *
			 * @param Throwable $e          The exception that occurred while sending the report.
			 * @param Throwable $error      The original error.
			 * @param string    $context    The error context.
			 * @param array     $extra_data Additional data.
			 */
			do_action( 'divi_squad_error_report_failed', $e, $error, $context, $extra_data );
		}
	}
}
