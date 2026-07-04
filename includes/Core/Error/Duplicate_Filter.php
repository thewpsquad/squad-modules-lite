<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Duplicate Filter
 *
 * High-performance duplicate error detection using optimized hashing.
 *
 * This module provides memory-efficient tracking of reported errors to prevent duplicate
 * submissions to error logging services. It uses a combination of in-memory caching and
 * persistent WordPress options for storage, with automatic cleanup of expired entries.
 *
 * @since   3.4.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Core\Error;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use Throwable;

/**
 * Duplicate_Filter Class
 *
 * Memory-efficient duplicate detection with automatic cleanup.
 *
 * Time Complexity: O(1) for all operations
 * Space Complexity: O(n) where n is number of unique errors
 *
 * This class handles the detection and marking of duplicate errors within a configurable
 * tracking window. It generates unique signatures for errors based on key attributes and
 * stores timestamps to enforce deduplication.
 *
 * @since   3.4.0
 * @package DiviSquad
 */
class Duplicate_Filter {
	/**
	 * Storage key for tracked errors
	 *
	 * @since 3.4.0
	 */
	private const STORAGE_KEY = 'divi_squad_tracked_errors';

	/**
	 * Default tracking duration (7 days)
	 *
	 * @since 3.4.0
	 */
	private const TRACK_DURATION = 604800; // 7 * 24 * 60 * 60.

	/**
	 * Maximum tracked errors before cleanup
	 *
	 * @since 3.4.0
	 */
	private const MAX_TRACKED = 1000;

	/**
	 * In-memory cache for this request
	 *
	 * @since 3.4.0
	 * @var array<string, int>|null
	 */
	private static ?array $cache = null;

	/**
	 * Check if error is duplicate
	 *
	 * Determines whether the provided error data matches a previously tracked error within
	 * the active tracking duration. This prevents redundant error reports.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $error_data Associative array containing error details such as message, file, line, code, and environment.
	 *
	 * @return bool True if the error is a duplicate, false otherwise.
	 */
	public function is_duplicate( array $error_data ): bool {
		try {
			$signature = $this->generate_signature( $error_data );
			$tracked   = $this->get_tracked_errors();

			if ( isset( $tracked[ $signature ] ) ) {
				$timestamp = $tracked[ $signature ];
				$duration  = $this->get_track_duration();

				// Check if tracking period is still active.
				$is_duplicate = ( time() - $timestamp ) < $duration;

				/**
				 * Filter the duplicate status determination.
				 *
				 * Allows overriding the duplicate check result for custom logic.
				 *
				 * @since 3.4.0
				 *
				 * @param bool                 $is_duplicate Whether the error is considered a duplicate.
				 * @param string               $signature    The generated error signature.
				 * @param array<string, mixed> $error_data   The original error data.
				 */
				return apply_filters( 'divi_squad_error_is_duplicate', $is_duplicate, $signature, $error_data );
			}

			return false;

		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Duplicate check failed', false );

			return false; // Allow reporting on error.
		}
	}

	/**
	 * Mark error as reported
	 *
	 * Records the current timestamp for the error signature in storage, marking it as reported.
	 * Performs cleanup if the maximum number of tracked errors is exceeded.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $error_data Associative array containing error details.
	 *
	 * @return bool True on successful storage, false on failure.
	 */
	public function mark_reported( array $error_data ): bool {
		try {
			$signature = $this->generate_signature( $error_data );
			$tracked   = $this->get_tracked_errors();

			// Add new entry with current timestamp.
			$tracked[ $signature ] = time();

			// Cleanup if too many entries.
			if ( count( $tracked ) > self::MAX_TRACKED ) {
				$tracked = $this->cleanup_old_entries( $tracked );
			}

			// Update storage and cache.
			self::$cache = $tracked;
			$success     = update_option( self::STORAGE_KEY, $tracked, false );

			/**
			 * Action fired after marking an error as reported.
			 *
			 * Allows plugins to hook into the reporting process, e.g., for additional logging.
			 *
			 * @since 3.4.0
			 *
			 * @param string               $signature  The error signature.
			 * @param array<string, mixed> $error_data The original error data.
			 * @param bool                 $success    Whether the storage update succeeded.
			 */
			do_action( 'divi_squad_error_marked_reported', $signature, $error_data, $success );

			return $success;

		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Mark reported failed', false );

			return false;
		}
	}

	/**
	 * Generate error signature
	 *
	 * Creates a compact, collision-resistant hash from error characteristics.
	 * The signature is derived from core error attributes and optionally the plugin version.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $error_data Associative array containing error details.
	 *
	 * @return string 16-character hash representing the unique error signature.
	 */
	private function generate_signature( array $error_data ): string {
		// Core signature components for uniqueness.
		$components = array(
			$error_data['error_message'] ?? '',
			$error_data['error_file'] ?? '',
			$error_data['error_line'] ?? '',
			$error_data['error_code'] ?? '',
		);

		// Add plugin version for version-specific tracking.
		if ( isset( $error_data['environment']['plugin_version'] ) ) {
			$components[] = $error_data['environment']['plugin_version'];
		}

		/**
		 * Filter the components used for error signature generation.
		 *
		 * Allows customization of which attributes contribute to the signature.
		 *
		 * @since 3.4.0
		 *
		 * @param array<int|string, mixed> $components Array of signature components.
		 * @param array<string, mixed>     $error_data The original error data.
		 */
		$components = (array) apply_filters( 'divi_squad_error_signature_components', $components, $error_data );

		$signature_data = implode( '|', $components );

		// Use hash function if available (faster), otherwise fallback to md5.
		if ( function_exists( 'hash' ) ) {
			return substr( hash( 'crc32b', $signature_data ), 0, 16 );
		}

		return substr( md5( $signature_data ), 0, 16 );
	}

	/**
	 * Get tracked errors (with caching)
	 *
	 * Retrieves the array of tracked error signatures and timestamps, applying cleanup for
	 * expired entries and utilizing in-memory caching for the current request.
	 *
	 * @since 3.4.0
	 *
	 * @return array<string, int> Associative array where keys are signatures and values are timestamps.
	 */
	private function get_tracked_errors(): array {
		if ( null !== self::$cache ) {
			return self::$cache;
		}

		$tracked = get_option( self::STORAGE_KEY, array() );

		if ( ! is_array( $tracked ) ) {
			$tracked = array();
		}

		// Cleanup expired entries on load.
		$tracked     = $this->cleanup_old_entries( $tracked );
		self::$cache = $tracked;

		return $tracked;
	}

	/**
	 * Cleanup expired entries
	 *
	 * Filters out tracked errors whose timestamps exceed the tracking duration, updating
	 * storage only if entries were removed.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, int> $tracked Associative array of tracked errors.
	 *
	 * @return array<string, int> Cleaned array of active tracked errors.
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

		// Update storage if we removed entries.
		if ( count( $cleaned ) !== count( $tracked ) ) {
			update_option( self::STORAGE_KEY, $cleaned, false );
		}

		return $cleaned;
	}

	/**
	 * Get tracking duration
	 *
	 * Retrieves the duration in seconds for which errors should be tracked as duplicates.
	 * This value can be customized via the 'divi_squad_error_track_duration' filter.
	 *
	 * @since 3.4.0
	 *
	 * @return int Duration in seconds.
	 */
	private function get_track_duration(): int {
		/**
		 * Filter the error tracking duration.
		 *
		 * Allows adjustment of the time window for duplicate detection.
		 *
		 * @since 3.4.0
		 *
		 * @param int $duration Default duration in seconds (604800 for 7 days).
		 */
		return apply_filters( 'divi_squad_error_track_duration', self::TRACK_DURATION );
	}

	/**
	 * Clear all tracked errors
	 *
	 * Removes all stored error tracking data and clears the in-memory cache.
	 *
	 * @since 3.4.0
	 *
	 * @return bool True on successful deletion, false on failure.
	 */
	public function clear_all(): bool {
		try {
			self::$cache = null;
			$success     = delete_option( self::STORAGE_KEY );

			/**
			 * Action fired after clearing all tracked errors.
			 *
			 * Allows plugins to respond to the reset, e.g., for logging or notifications.
			 *
			 * @since 3.4.0
			 *
			 * @param bool $success Whether the option deletion succeeded.
			 */
			do_action( 'divi_squad_error_tracked_cleared', $success );

			return $success;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Clear tracked errors failed', false );

			return false;
		}
	}

	/**
	 * Get tracked error count
	 *
	 * Returns the number of currently active (non-expired) tracked errors.
	 *
	 * @since 3.4.0
	 *
	 * @return int Number of tracked errors.
	 */
	public function get_count(): int {
		try {
			return count( $this->get_tracked_errors() );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Get tracked count failed', false );

			return 0;
		}
	}

	/**
	 * Force clear cache (for testing)
	 *
	 * Clears the in-memory cache to force reloading from storage. Intended for unit testing.
	 *
	 * @since 3.4.0
	 */
	public static function clear_cache(): void {
		self::$cache = null;
	}
}
