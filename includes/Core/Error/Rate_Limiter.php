<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Rate Limiter
 *
 * Efficient rate limiting for error reports with sliding window algorithm.
 *
 * @since   3.4.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Core\Error;

use Throwable;

/**
 * Rate_Limiter Class
 *
 * Optimized rate limiting with minimal database operations.
 *
 * @since   3.4.0
 * @package DiviSquad
 */
class Rate_Limiter {
	/**
	 * Transient key for rate limiting
	 */
	private const RATE_KEY = 'squad_error_rate';

	/**
	 * Rate limit window (10 minutes)
	 */
	private const WINDOW_SECONDS = 600;

	/**
	 * Maximum reports per window
	 */
	private const MAX_REPORTS = 5;

	/**
	 * Site-specific rate key cache
	 *
	 * @var string|null
	 */
	private static ?string $rate_key_cache = null;

	/**
	 * Check if rate limit allows new report
	 *
	 * Time Complexity: O(1)
	 *
	 * @return bool Can send report
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

			return $current_count < $max_reports;

		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Rate limit check failed', false );
			return true; // Allow on error for safety
		}
	}

	/**
	 * Increment rate limit counter
	 *
	 * Time Complexity: O(1)
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

			set_transient( $key, $current + 1, $window );

		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Rate limit increment failed', false );
		}
	}

	/**
	 * Get current report count
	 *
	 * @return int Current count
	 */
	private function get_current_count(): int {
		return (int) get_transient( $this->get_rate_key() );
	}

	/**
	 * Get site-specific rate limiting key
	 *
	 * @return string Rate key
	 */
	private function get_rate_key(): string {
		if ( null === self::$rate_key_cache ) {
			$site_id = get_current_blog_id();

			if ( ! function_exists( 'wp_hash' ) ) {
				require_once ABSPATH . WPINC . '/pluggable.php';
			}

			self::$rate_key_cache = self::RATE_KEY . '_' . wp_hash( (string) $site_id );
		}

		return self::$rate_key_cache;
	}

	/**
	 * Reset rate limit (admin/testing)
	 *
	 * @return bool Success
	 */
	public function reset(): bool {
		try {
			return delete_transient( $this->get_rate_key() );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Rate limit reset failed', false );
			return false;
		}
	}

	/**
	 * Get remaining reports in current window
	 *
	 * @return int Remaining count
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

		return max( 0, $max_reports - $current );
	}

	/**
	 * Get window expiration time
	 *
	 * @return int Expiration timestamp
	 */
	public function get_window_expires(): int {
		$key = $this->get_rate_key();

		// Get transient timeout (WordPress internal)
		global $wpdb;
		$timeout_option = '_transient_timeout_' . $key;
		$timeout        = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
				$timeout_option
			)
		);

		return $timeout ? (int) $timeout : 0;
	}
}
