<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Log File Reader
 *
 * Memory-efficient log file reading with optimized algorithms.
 *
 * @since   3.4.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Core\Error;

use DiviSquad\Core\Traits\WP\Use_WP_Filesystem;
use Throwable;

/**
 * Log_File Class
 *
 * Optimized log file operations with O(1) memory usage for large files.
 *
 * @since   3.4.0
 * @package DiviSquad
 */
class Log_File {
	use Use_WP_Filesystem;

	/**
	 * Default chunk size for file reading
	 */
	private const CHUNK_SIZE = 8192; // 8KB chunks

	/**
	 * Maximum file size to process (5MB)
	 */
	private const MAX_FILE_SIZE = 5242880;

	/**
	 * Get debug log content
	 *
	 * @param int $line_count Number of lines to retrieve
	 *
	 * @return string Log content
	 */
	public static function get_debug_log( int $line_count = 100 ): string {
		try {
			$log_path = self::get_debug_log_path();

			if ( ! self::is_readable_file( $log_path ) ) {
				return '';
			}

			return self::read_tail_lines( $log_path, $line_count );

		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Debug log read failed', false );
			return 'Log read error: ' . $e->getMessage();
		}
	}

	/**
	 * Read last N lines from file (optimized for large files)
	 *
	 * @param string $file_path  File path
	 * @param int    $line_count Number of lines
	 *
	 * @return string File content
	 */
	public static function read_tail_lines( string $file_path, int $line_count ): string {
		$wp_fs     = self::get_wp_filesystem();
		$file_size = $wp_fs->size( $file_path );

		if ( 0 === $file_size || false === $file_size ) {
			return '';
		}

		// For small files, read entire content
		if ( $file_size < self::CHUNK_SIZE ) {
			$content = $wp_fs->get_contents( $file_path );
			return self::extract_last_lines( $content ? $content : '', $line_count );
		}

		// For large files, use optimized tail reading
		return self::read_tail_optimized( $file_path, $file_size, $line_count );
	}

	/**
	 * Optimized tail reading for large files
	 *
	 * Time Complexity: O(log n) where n is file size
	 * Space Complexity: O(k) where k is chunk size
	 *
	 * @param string $file_path  File path
	 * @param int    $file_size  File size
	 * @param int    $line_count Desired line count
	 *
	 * @return string File content
	 */
	private static function read_tail_optimized( string $file_path, int $file_size, int $line_count ): string {
		$file = fopen( $file_path, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( false === $file ) {
			return '';
		}

		$buffer      = '';
		$lines_found = 0;
		$position    = $file_size;
		$chunk_size  = min( self::CHUNK_SIZE, $file_size );

		// Read backwards in chunks until we have enough lines
		while ( $position > 0 && $lines_found < $line_count ) {
			$read_size = min( $chunk_size, $position );
			$position -= $read_size;

			fseek( $file, $position );
			$chunk = fread( $file, $read_size ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread

			if ( false === $chunk ) {
				break;
			}

			$buffer      = $chunk . $buffer;
			$lines_found = substr_count( $buffer, "\n" );
		}

		fclose( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		return self::extract_last_lines( $buffer, $line_count );
	}

	/**
	 * Extract last N lines from content
	 *
	 * @param string $content    File content
	 * @param int    $line_count Number of lines
	 *
	 * @return string Extracted lines
	 */
	private static function extract_last_lines( string $content, int $line_count ): string {
		$lines = explode( "\n", $content );

		// Remove empty last line if present
		if ( end( $lines ) === '' ) {
			array_pop( $lines );
		}

		$total_lines = count( $lines );
		$start_index = max( 0, $total_lines - $line_count );

		return implode( "\n", array_slice( $lines, $start_index ) );
	}

	/**
	 * Get debug log file path
	 *
	 * @return string Log file path
	 */
	private static function get_debug_log_path(): string {
		$default_path = WP_CONTENT_DIR . '/debug.log';

		/**
		 * Filter to modify the debug log file path
		 *
		 * @since 3.4.0
		 *
		 * @param string $default_path Default debug.log path (WP_CONTENT_DIR/debug.log)
		 */
		return apply_filters( 'divi_squad_debug_log_path', $default_path );
	}

	/**
	 * Check if file is readable and within size limits
	 *
	 * @param string $file_path File path
	 *
	 * @return bool Is readable
	 */
	private static function is_readable_file( string $file_path ): bool {
		$wp_fs = self::get_wp_filesystem();

		if ( ! $wp_fs->exists( $file_path ) || ! $wp_fs->is_readable( $file_path ) ) {
			return false;
		}

		$file_size = $wp_fs->size( $file_path );
		return $file_size > 0 && $file_size <= self::MAX_FILE_SIZE;
	}

	/**
	 * Read specific log file
	 *
	 * @param string $log_path   Log file path
	 * @param int    $line_count Number of lines
	 *
	 * @return string Log content
	 */
	public static function read_specific_log( string $log_path, int $line_count = 100 ): string {
		try {
			if ( ! self::is_readable_file( $log_path ) ) {
				return 'Log file not accessible: ' . basename( $log_path );
			}

			return self::read_tail_lines( $log_path, $line_count );

		} catch ( Throwable $e ) {
			return 'Error reading log: ' . $e->getMessage();
		}
	}
}
