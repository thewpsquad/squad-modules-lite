<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Rate Limiter
 *
 * Efficient rate limiting for error reports with sliding window algorithm.
 *
 * This module implements a transient-based rate limiter to prevent excessive error
 * reporting, utilizing WordPress transients for lightweight, time-bound counters.
 * It supports customization of limits and durations through filters for flexible deployment.
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
 * Rate_Limiter Class
 *
 * Optimized rate limiting with minimal database operations.
 *
 * This class enforces rate limits on error reports using a sliding window approach,
 * ensuring site stability while allowing critical errors to be reported. All operations
 * are designed for O(1) time complexity, leveraging transients for storage.
 *
 * @since   3.4.0
 * @package DiviSquad
 */
class Rate_Limiter {
	/**
	 * Transient key for rate limiting
	 *
	 * @since 3.4.0
	 */
	private const RATE_KEY = 'divi_squad_error_rate';

	/**
	 * Rate limit window (10 minutes)
	 *
	 * @since 3.4.0
	 */
	private const WINDOW_SECONDS = 600;

	/**
	 * Maximum reports per window
	 *
	 * @since 3.4.0
	 */
	private const MAX_REPORTS = 5;

	/**
	 * Site-specific rate key cache, keyed by blog ID.
	 *
	 * Keyed per blog so switch_to_blog() within a request never returns a stale
	 * key for the wrong site.
	 *
	 * @since 3.4.0
	 * @var array<int, string>
	 */
	private static array $rate_key_cache = array();

	/**
	 * Check if rate limit allows new report
	 *
	 * Performs a quick check against the current count within the rate limit window.
	 * Returns true if a new report can be sent, false if the limit is exceeded.
	 *
	 * Time Complexity: O(1)
	 *
	 * @since 3.4.0
	 *
	 * @return bool True if a report can be sent, false if rate limited.
	 */
	public function can_send(): bool {
		/**
		 * Filters whether rate limiting should be applied to error reports.
		 *
		 * This filter allows developers to completely disable rate limiting for error reports.
		 * This should be used with caution as it may lead to email flooding.
		 *
		 * @since 3.4.0
		 *
		 * @param bool $apply_rate_limiting Whether to apply rate limiting. Default true.
		 */
		if ( ! apply_filters( 'divi_squad_apply_rate_limiting', true ) ) {
			return true;
		}

		try {
			$current_count = $this->get_current_count();

			/**
			 * Filters the maximum number of error reports allowed per rate limit window.
			 *
			 * This filter controls how many error reports can be sent within the rate limit
			 * window before additional reports are blocked. Higher values allow more reports
			 * but may increase email volume.
			 *
			 * @since 3.4.0
			 *
			 * @param int $max_reports Maximum number of reports per window. Default 5.
			 */
			$max_reports = apply_filters( 'divi_squad_max_reports_per_window', self::MAX_REPORTS );

			$can_send = $current_count < $max_reports;

			/**
			 * Filters the result of the rate limit check.
			 *
			 * Allows overriding the rate limit decision based on custom logic, such as
			 * error severity or user roles.
			 *
			 * @since 3.4.0
			 *
			 * @param bool $can_send      Whether a report can be sent. Default based on count vs. max.
			 * @param int  $current_count Current number of reports in the window.
			 * @param int  $max_reports   Maximum allowed reports per window.
			 */
			return apply_filters( 'divi_squad_rate_limit_can_send', $can_send, $current_count, $max_reports );

		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Rate limit check failed', false );

			return true; // Allow on error for safety.
		}
	}

	/**
	 * Increment rate limit counter
	 *
	 * Updates the transient with the incremented count, setting the expiration to the window duration.
	 *
	 * Time Complexity: O(1)
	 *
	 * @since 3.4.0
	 */
	public function increment(): void {
		try {
			$key     = $this->get_rate_key();
			$current = $this->get_current_count();

			/**
			 * Filters the rate limit window duration in seconds.
			 *
			 * This filter controls how long the rate limit window lasts. After this duration,
			 * the counter resets and new reports are allowed. Shorter windows mean more frequent
			 * resets but potentially more email bursts.
			 *
			 * @since 3.4.0
			 *
			 * @param int $window_duration Window duration in seconds. Default 600 (10 minutes).
			 */
			$window = apply_filters( 'divi_squad_rate_limit_window', self::WINDOW_SECONDS );

			$new_count = $current + 1;
			set_transient( $key, $new_count, $window );

			/**
			 * Action fired after incrementing the rate limit counter.
			 *
			 * Allows plugins to log the increment or perform related tasks.
			 *
			 * @since 3.4.0
			 *
			 * @param int $new_count      The new count after increment.
			 * @param int $previous_count The previous count before increment.
			 * @param int $window         The current window duration in seconds.
			 */
			do_action( 'divi_squad_rate_limit_incremented', $new_count, $current, $window );

		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Rate limit increment failed', false );
		}
	}

	/**
	 * Get current report count
	 *
	 * Retrieves the current count from the transient.
	 *
	 * @since 3.4.0
	 *
	 * @return int The number of reports in the current window.
	 */
	private function get_current_count(): int {
		$count = get_transient( $this->get_rate_key() );

		/**
		 * Filters the current rate limit count.
		 *
		 * Provides an opportunity to override the retrieved count for testing or custom logic.
		 *
		 * @since 3.4.0
		 *
		 * @param int $count Retrieved transient value, default 0 if unset.
		 */
		$current_count = 0 !== $count ? $count : 0;

		return (int) apply_filters( 'divi_squad_rate_limit_current_count', $current_count );
	}

	/**
	 * Get site-specific rate limiting key
	 *
	 * Generates a hashed, site-specific key for the transient to ensure isolation.
	 *
	 * @since 3.4.0
	 *
	 * @return string The unique rate limiting transient key.
	 */
	private function get_rate_key(): string {
		$site_id = get_current_blog_id();

		if ( ! isset( self::$rate_key_cache[ $site_id ] ) ) {
			if ( ! function_exists( 'wp_hash' ) ) {
				require_once ABSPATH . 'wp-includes/pluggable.php';
			}

			self::$rate_key_cache[ $site_id ] = self::RATE_KEY . '_' . wp_hash( (string) $site_id );
		}

		/**
		 * Filters the rate limiting transient key.
		 *
		 * Allows customization for multi-site or custom key generation.
		 *
		 * @since 3.4.0
		 *
		 * @param string $rate_key Generated key based on site ID.
		 * @param int    $site_id  Current site/blog ID.
		 */
		return apply_filters( 'divi_squad_rate_limit_key', self::$rate_key_cache[ $site_id ], $site_id );
	}

	/**
	 * Reset rate limit (admin/testing)
	 *
	 * Deletes the transient to reset the counter for the current window.
	 *
	 * @since 3.4.0
	 *
	 * @return bool True if the transient was successfully deleted, false otherwise.
	 */
	public function reset(): bool {
		try {
			$success = delete_transient( $this->get_rate_key() );

			if ( $success ) {
				/**
				 * Action fired after resetting the rate limit.
				 *
				 * Enables logging or notifications for rate limit resets.
				 *
				 * @since 3.4.0
				 *
				 * @param string $rate_key The transient key that was reset.
				 */
				do_action( 'divi_squad_rate_limit_reset', $this->get_rate_key() );
			}

			return $success;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Rate limit reset failed', false );

			return false;
		}
	}

	/**
	 * Get remaining reports in current window
	 *
	 * Calculates how many additional reports can be sent before hitting the limit.
	 *
	 * @since 3.4.0
	 *
	 * @return int The number of remaining allowed reports, or 0 if exceeded.
	 */
	public function get_remaining(): int {
		/**
		 * Filters the maximum number of error reports allowed per rate limit window.
		 *
		 * @since 3.4.0
		 *
		 * @param int $max_reports Maximum number of reports per window. Default 5.
		 */
		$max_reports = apply_filters( 'divi_squad_max_reports_per_window', self::MAX_REPORTS );
		$current     = $this->get_current_count();

		$remaining = max( 0, $max_reports - $current );

		/**
		 * Filters the calculated remaining reports.
		 *
		 * Allows adjustment of the remaining count for custom scenarios.
		 *
		 * @since 3.4.0
		 *
		 * @param int $remaining   Calculated remaining reports.
		 * @param int $max_reports Maximum allowed reports.
		 * @param int $current     Current report count.
		 */
		return apply_filters( 'divi_squad_rate_limit_remaining', $remaining, $max_reports, $current );
	}

	/**
	 * Get window expiration time
	 *
	 * Retrieves the Unix timestamp when the current rate limit window expires.
	 *
	 * @since 3.4.0
	 *
	 * @return int The expiration timestamp, or 0 if no active window.
	 */
	public function get_window_expires(): int {
		$key = $this->get_rate_key();

		// Read the transient's timeout option. Note: on sites using a persistent
		// object cache, transient timeouts are not stored as options, so this
		// returns 0 (no retrievable expiry) — acceptable for a soft hint.
		$timeout = get_option( '_transient_timeout_' . $key );
		$expires = is_numeric( $timeout ) ? (int) $timeout : 0;

		/**
		 * Filters the rate limit window expiration timestamp.
		 *
		 * Permits overriding the expiration time for extended or shortened windows.
		 *
		 * @since 3.4.0
		 *
		 * @param int    $expires  Retrieved expiration timestamp.
		 * @param string $rate_key The transient key.
		 */
		return apply_filters( 'divi_squad_rate_limit_expires', $expires, $key );
	}
}
