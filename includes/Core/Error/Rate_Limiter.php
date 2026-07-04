<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Error Rate Limiter
 *
 * Handles rate limiting for error reports to prevent flooding of reports
 * from a single site.
 *
 * @since   3.4.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Core\Error;

use Throwable;

/**
 * Error Rate Limiter Class
 *
 * Manages rate limiting for error reports to prevent excessive
 * error reporting from a single site.
 *
 * Features:
 * - Configurable rate limiting window
 * - Customizable maximum reports per window
 * - WordPress hooks for extensibility
 *
 * @since   3.4.0
 * @package DiviSquad
 */
class Rate_Limiter {
	/**
	 * Rate limit key prefix
	 *
	 * @since 3.4.0
	 * @var string
	 */
	protected const RATE_LIMIT_KEY = 'squad_error_report_';

	/**
	 * Rate limit window in seconds (15 minutes)
	 *
	 * @since 3.4.0
	 * @var int
	 */
	protected const RATE_LIMIT_WINDOW = 600;

	/**
	 * Maximum reports per window
	 *
	 * @since 3.4.0
	 * @var int
	 */
	protected const MAX_REPORTS = 10;

	/**
	 * Check if rate limit is exceeded
	 *
	 * Determines if the current site has exceeded the maximum number of error reports
	 * within the rate limit window.
	 *
	 * @since 3.4.0
	 *
	 * @return bool Whether sending is allowed.
	 */
	public function check_rate_limit(): bool {
		try {
			/**
			 * Filter whether to apply rate limiting to error reports.
			 *
			 * @since 3.4.0
			 *
			 * @param bool $apply_rate_limiting Whether to apply rate limiting.
			 */
			$apply_rate_limiting = apply_filters( 'divi_squad_error_report_apply_rate_limiting', true );

			if ( ! $apply_rate_limiting ) {
				return true;
			}

			$count = $this->get_current_count();

			/**
			 * Filter the maximum number of error reports allowed within the rate limit window.
			 *
			 * @since 3.4.0
			 *
			 * @param int $max_reports Maximum number of reports.
			 */
			$max_reports = apply_filters( 'divi_squad_error_report_max_reports', self::MAX_REPORTS );

			return $count < $max_reports;
		} catch ( Throwable $e ) {
			// Log the error but allow sending in case of failure
			divi_squad()->log_error( $e, 'Error checking rate limit', false );

			return true;
		}
	}

	/**
	 * Increment rate limit counter
	 *
	 * Increases the counter for the number of error reports sent within the current window.
	 *
	 * @since 3.4.0
	 *
	 * @return void
	 */
	public function increment_rate_limit(): void {
		try {
			$key   = $this->get_rate_limit_key();
			$count = $this->get_current_count();

			/**
			 * Filter the rate limit window duration in seconds.
			 *
			 * @since 3.4.0
			 *
			 * @param int $window_duration Window duration in seconds.
			 */
			$window_duration = apply_filters( 'divi_squad_error_report_rate_limit_window', self::RATE_LIMIT_WINDOW );

			if ( 0 === $count ) {
				set_transient( $key, 1, $window_duration );
			} else {
				set_transient( $key, $count + 1, $window_duration );
			}
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Error incrementing rate limit', false );
		}
	}

	/**
	 * Get rate limit key for current site
	 *
	 * Generates a unique key for storing the rate limit counter for the current site.
	 *
	 * @since 3.4.0
	 *
	 * @return string Rate limit key.
	 */
	protected function get_rate_limit_key(): string {
		$site_id = get_current_blog_id();

		if ( ! function_exists( '\wp_hash' ) ) {
			require_once ABSPATH . WPINC . '/pluggable.php';
		}

		return self::RATE_LIMIT_KEY . \wp_hash( (string) $site_id );
	}

	/**
	 * Reset rate limit counter
	 *
	 * Resets the rate limit counter for the current site.
	 * Useful for testing or manual intervention.
	 *
	 * @since 3.4.0
	 *
	 * @return bool Success status.
	 */
	public function reset_rate_limit(): bool {
		try {
			$key = $this->get_rate_limit_key();

			return delete_transient( $key );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Error resetting rate limit', false );

			return false;
		}
	}

	/**
	 * Get current rate limit count
	 *
	 * Returns the current count of error reports sent within the window.
	 *
	 * @since 3.4.0
	 *
	 * @return int Current count.
	 */
	public function get_current_count(): int {
		try {
			return (int) get_transient( $this->get_rate_limit_key() );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Error getting current rate limit count', false );

			return 0;
		}
	}
}
