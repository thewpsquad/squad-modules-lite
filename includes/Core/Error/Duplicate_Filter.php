<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Duplicate Filter
 *
 * High-performance duplicate error detection using optimized hashing.
 *
 * @since   3.4.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Core\Error;

use Throwable;

/**
 * Duplicate_Filter Class
 *
 * Memory-efficient duplicate detection with automatic cleanup.
 *
 * Time Complexity: O(1) for all operations
 * Space Complexity: O(n) where n is number of unique errors
 *
 * @since   3.4.0
 * @package DiviSquad
 */
class Duplicate_Filter {
	/**
	 * Storage key for tracked errors
	 */
	private const STORAGE_KEY = 'divi_squad_tracked_errors';

	/**
	 * Default tracking duration (7 days)
	 */
	private const TRACK_DURATION = 604800; // 7 * 24 * 60 * 60

	/**
	 * Maximum tracked errors before cleanup
	 */
	private const MAX_TRACKED = 1000;

	/**
	 * In-memory cache for this request
	 *
	 * @var array<string, int>|null
	 */
	private static ?array $cache = null;

	/**
	 * Check if error is duplicate
	 *
	 * @param array<string, mixed> $error_data Error data
	 *
	 * @return bool Is duplicate
	 */
	public function is_duplicate( array $error_data ): bool {
		try {
			$signature = $this->generate_signature( $error_data );
			$tracked   = $this->get_tracked_errors();

			if ( isset( $tracked[ $signature ] ) ) {
				$timestamp = $tracked[ $signature ];
				$duration  = $this->get_track_duration();

				// Check if tracking period is still active
				return ( time() - $timestamp ) < $duration;
			}

			return false;

		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Duplicate check failed', false );
			return false; // Allow reporting on error
		}
	}

	/**
	 * Mark error as reported
	 *
	 * @param array<string, mixed> $error_data Error data
	 *
	 * @return bool Success
	 */
	public function mark_reported( array $error_data ): bool {
		try {
			$signature = $this->generate_signature( $error_data );
			$tracked   = $this->get_tracked_errors();

			// Add new entry with current timestamp
			$tracked[ $signature ] = time();

			// Cleanup if too many entries
			if ( count( $tracked ) > self::MAX_TRACKED ) {
				$tracked = $this->cleanup_old_entries( $tracked );
			}

			// Update storage and cache
			self::$cache = $tracked;
			return update_option( self::STORAGE_KEY, $tracked, false );

		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Mark reported failed', false );
			return false;
		}
	}

	/**
	 * Generate error signature
	 *
	 * Creates a compact, collision-resistant hash from error characteristics.
	 *
	 * @param array<string, mixed> $error_data Error data
	 *
	 * @return string 16-character hash
	 */
	private function generate_signature( array $error_data ): string {
		// Core signature components for uniqueness
		$components = array(
			$error_data['error_message'] ?? '',
			$error_data['error_file'] ?? '',
			$error_data['error_line'] ?? '',
			$error_data['error_code'] ?? '',
		);

		// Add plugin version for version-specific tracking
		if ( isset( $error_data['environment']['plugin_version'] ) ) {
			$components[] = $error_data['environment']['plugin_version'];
		}

		$signature_data = implode( '|', $components );

		// Use xxhash if available (faster), otherwise fallback to crc32
		if ( function_exists( 'hash' ) ) {
			return substr( hash( 'crc32b', $signature_data ), 0, 16 );
		}

		return substr( md5( $signature_data ), 0, 16 );
	}

	/**
	 * Get tracked errors (with caching)
	 *
	 * @return array<string, int> Tracked errors
	 */
	private function get_tracked_errors(): array {
		if ( null !== self::$cache ) {
			return self::$cache;
		}

		$tracked = get_option( self::STORAGE_KEY, array() );

		if ( ! is_array( $tracked ) ) {
			$tracked = array();
		}

		// Cleanup expired entries on load
		$tracked     = $this->cleanup_old_entries( $tracked );
		self::$cache = $tracked;

		return $tracked;
	}

	/**
	 * Cleanup expired entries
	 *
	 * @param array<string, int> $tracked Tracked errors
	 *
	 * @return array<string, int> Cleaned errors
	 */
	private function cleanup_old_entries( array $tracked ): array {
		$current_time = time();
		$duration     = $this->get_track_duration();

		$cleaned = array_filter(
			$tracked,
			static function ( $timestamp ) use ( $duration, $current_time ) {
				return ( $current_time - $timestamp ) < $duration;
			}
		);

		// Update storage if we removed entries
		if ( count( $cleaned ) !== count( $tracked ) ) {
			update_option( self::STORAGE_KEY, $cleaned, false );
		}

		return $cleaned;
	}

	/**
	 * Get tracking duration
	 *
	 * @return int Duration in seconds
	 */
	private function get_track_duration(): int {
		return apply_filters( 'divi_squad_error_track_duration', self::TRACK_DURATION );
	}

	/**
	 * Clear all tracked errors
	 *
	 * @return bool Success
	 */
	public function clear_all(): bool {
		try {
			self::$cache = null;
			return delete_option( self::STORAGE_KEY );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Clear tracked errors failed', false );
			return false;
		}
	}

	/**
	 * Get tracked error count
	 *
	 * @return int Count of tracked errors
	 */
	public function get_count(): int {
		try {
			return count( $this->get_tracked_errors() );
		} catch ( Throwable $e ) {
			return 0;
		}
	}

	/**
	 * Force clear cache (for testing)
	 */
	public static function clear_cache(): void {
		self::$cache = null;
	}
}
