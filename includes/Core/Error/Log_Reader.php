<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Error Log Reader
 *
 * Provides utilities for reading WordPress debug logs in a memory efficient
 * way for inclusion in error reports.
 *
 * @since   3.4.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Core\Error;

use Throwable;

/**
 * Error Log Reader Class
 *
 * Provides methods for efficiently reading and processing WordPress debug logs.
 *
 * Features:
 * - Memory-efficient log reading
 * - Customizable log line count
 * - Support for different log file paths
 *
 * @since   3.4.0
 * @package DiviSquad
 */
class Log_Reader {
	/**
	 * Get the debug log
	 *
	 * Retrieves the last 100 lines of the WordPress debug log file.
	 * Uses a memory efficient approach to avoid loading the entire file.
	 *
	 * @since 3.4.0
	 *
	 * @return string The last 100 lines of the debug log or an empty string if the log is not accessible.
	 */
	public static function get_debug_log(): string {
		try {
			if ( static::debug_log_exists() ) {
				/**
				 * Filter the number of debug log lines to include in error reports.
				 *
				 * @since 3.4.0
				 *
				 * @param int $line_count Number of lines to include. Default 100.
				 */
				$line_count = apply_filters( 'divi_squad_error_report_debug_log_lines', 200 );

				$log_file = static::get_debug_log_file();

				// Use a memory efficient approach to read only the last N lines
				return self::read_last_lines( $log_file, $line_count );
			}

			return '';
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Error reading debug log' );

			return 'Error reading debug log: ' . $e->getMessage();
		}
	}

	/**
	 * Read the last N lines from a file in a memory efficient way
	 *
	 * @since 3.4.0
	 *
	 * @param string $file_path  Path to the file.
	 * @param int    $line_count Number of lines to read from end of file.
	 *
	 * @return string The last N lines of the file.
	 */
	public static function read_last_lines( string $file_path, int $line_count = 200 ): string {
		try {
			// Open the file for reading (binary mode to handle different line endings)
			$file = fopen( $file_path, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
			if ( false === $file ) {
				return '';
			}

			// Jump to end of file
			$file_size = filesize( $file_path );
			if ( 0 === $file_size ) {
				fclose( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

				return '';
			}

			/**
			 * Filter the chunk size used when reading files.
			 *
			 * @since 3.4.0
			 *
			 * @param int    $chunk_size Default chunk size in bytes (4KB).
			 * @param string $file_path  Path to the file being read.
			 */
			$chunk_size = apply_filters( 'divi_squad_error_report_file_chunk_size', 4096, $file_path );

			// Check for very large files and apply a safety limit
			$max_read_size = min( $file_size, 1024 * 1024 * 5 ); // 5MB max read
			$chunk_size    = min( $chunk_size, $max_read_size ); // Read 4KB chunks or less
			$buffer        = '';
			$line_counter  = 0;

			// Start reading from the end moving backwards
			$position = $file_size;

			// Read in chunks from the end of the file
			while ( $position > 0 && $line_counter < $line_count ) {
				$read_size = min( $chunk_size, $position );
				$position -= $read_size;

				// Move the file pointer to position
				fseek( $file, $position );

				// Read the chunk
				$chunk = fread( $file, $read_size ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
				if ( false === $chunk ) {
					break;
				}

				// Prepend the chunk to our buffer
				$buffer = $chunk . $buffer;

				// Count newlines in the current buffer
				$line_counter = substr_count( $buffer, "\n" );
			}

			// Close the file
			fclose( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

			// If we have more lines than needed, trim from the beginning
			if ( $line_counter > $line_count ) {
				$lines = explode( "\n", $buffer );

				// Keep only the last $line_count lines (plus one more for possible empty last line)
				$lines = array_slice( $lines, - ( $line_count + 1 ) );

				// If the last element is empty (common with files ending in newline), remove it
				if ( end( $lines ) === '' ) {
					array_pop( $lines );
				}

				$buffer = implode( "\n", $lines );
			}

			return $buffer;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Error reading file lines' );

			return 'Error reading file: ' . $e->getMessage();
		}
	}

	/**
	 * Get a specific error log file
	 *
	 * Allows reading a specific error log file instead of the default WordPress debug log.
	 *
	 * @since 3.4.0
	 *
	 * @param string $log_path   Path to the log file to read.
	 * @param int    $line_count Number of lines to read from end of file.
	 *
	 * @return string The last N lines of the specified log file.
	 */
	public static function get_specific_log( string $log_path, int $line_count = 200 ): string {
		try {
			if ( ! divi_squad()->get_wp_fs()->exists( $log_path ) || ! divi_squad()->get_wp_fs()->is_readable( $log_path ) ) {
				return 'Log file not found or not readable: ' . esc_html( $log_path );
			}

			return self::read_last_lines( $log_path, $line_count );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Error reading specific log file' );

			return 'Error reading log file: ' . $e->getMessage();
		}
	}

	/**
	 * Check if debug log exists
	 *
	 * Checks if the WordPress debug log file exists and is readable.
	 *
	 * @since 3.4.0
	 *
	 * @return bool True if the debug log exists and is readable.
	 */
	public static function debug_log_exists(): bool {
		try {
			$log_file = static::get_debug_log_file();

			return file_exists( $log_file ) && is_readable( $log_file );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Error checking debug log existence' );

			return false;
		}
	}

	/**
	 * Get debug log file.
	 *
	 * @return string
	 */
	public static function get_debug_log_file(): string {
		$log_file = WP_CONTENT_DIR . '/debug.log';

		/**
		 * Filter the debug log file path.
		 *
		 * @since 3.4.0
		 *
		 * @param string $log_file Path to the debug log file.
		 */
		return apply_filters( 'divi_squad_error_report_debug_log_path', $log_file );
	}
}
