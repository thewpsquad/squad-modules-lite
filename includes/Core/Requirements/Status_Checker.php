<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Status checker class.
 *
 * Handles checking the Divi theme and plugin requirements.
 *
 * @since   3.5.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Core\Requirements;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use DiviSquad\Utils\Divi;
use RuntimeException;
use Throwable;

/**
 * Class Status_Checker
 *
 * Checks if Divi requirements are met.
 *
 * @since   3.5.0
 * @package DiviSquad
 */
class Status_Checker {
	/**
	 * Required Divi version.
	 *
	 * @since 3.2.0
	 *
	 * @var string
	 */
	private string $required_version;

	/**
	 * Cached requirements status.
	 *
	 * @since 3.3.0
	 *
	 * @var array<string, boolean|string>
	 */
	private array $status = array();

	/**
	 * Error message from the last check.
	 *
	 * @since 3.3.0
	 *
	 * @var string
	 */
	private string $last_error = '';

	/**
	 * Constructor.
	 *
	 * @since 3.5.0
	 */
	public function __construct() {
		$this->required_version = divi_squad()->get_option( 'RequiresDIVI', '4.14.0' );
	}

	/**
	 * Reset the status cache.
	 *
	 * @since 3.5.0
	 *
	 * @return void
	 */
	public function reset_cache(): void {
		$this->status = array();
	}

	/**
	 * Check if all Divi requirements are fulfilled.
	 *
	 * Performs a comprehensive check of:
	 * 1. Divi/Extra theme or Divi Builder plugin installation and activation
	 * 2. Version compatibility for Divi/Extra theme or Divi Builder plugin
	 *
	 * @since  3.2.0
	 * @access public
	 *
	 * @throws \RuntimeException When a requirement check fails (caught internally).
	 * @return bool True if all requirements are met, false otherwise.
	 */
	public function is_fulfilled(): bool {
		try {
			// Return a cached result if available.
			if ( isset( $this->status['is_fulfilled'] ) ) {
				return (bool) $this->status['is_fulfilled'];
			}

			// Check installation status.
			if ( ! $this->is_divi_installed() ) {
				throw new RuntimeException( esc_html__( 'Divi theme or Divi Builder plugin is not installed', 'squad-modules-for-divi' ) );
			}

			// Check activation status.
			if ( ! $this->is_divi_active() ) {
				throw new RuntimeException( esc_html__( 'Divi theme or Divi Builder plugin is not activated', 'squad-modules-for-divi' ) );
			}

			// Check version compatibility.
			if ( ! $this->meets_version_requirements() ) {
				throw new RuntimeException( esc_html__( 'Divi version is less than required', 'squad-modules-for-divi' ) );
			}

			/**
			 * Filter the final requirements validation status.
			 *
			 * @since 3.2.0
			 *
			 * @param bool           $is_valid       True if all requirements are met.
			 * @param Status_Checker $status_checker Current Status_Checker instance.
			 */
			$is_fulfilled = (bool) apply_filters( 'divi_squad_is_builder_meet', true, $this );

			// Cache the result.
			$this->status['is_fulfilled'] = $is_fulfilled;

			return $is_fulfilled;
		} catch ( Throwable $e ) {
			$this->last_error = $e->getMessage();

			$this->status['is_fulfilled'] = false;

			return false;
		}
	}

	/**
	 * Check if Divi theme or Divi Builder plugin is installed.
	 *
	 * @since  3.3.0
	 * @access protected
	 *
	 * @return bool True if either Divi theme or Divi Builder plugin is installed.
	 */
	protected function is_divi_installed(): bool {
		if ( ! isset( $this->status['is_installed'] ) ) {
			$is_theme_installed  = Divi::is_any_divi_theme_installed();
			$is_plugin_installed = Divi::is_divi_builder_plugin_installed();

			$this->status['is_installed']        = $is_theme_installed || $is_plugin_installed;
			$this->status['is_theme_installed']  = $is_theme_installed;
			$this->status['is_plugin_installed'] = $is_plugin_installed;

			// Seed defaults so get_status() always exposes both version keys.
			$this->status['theme_version']  = '0.0.0';
			$this->status['plugin_version'] = '0.0.0';

			if ( $is_theme_installed ) {
				$is_theme_active               = Divi::is_any_divi_theme_active();
				$this->status['theme_version'] = $is_theme_active ? Divi::get_builder_version() : '0.0.0';
			}

			if ( $is_plugin_installed ) {
				$is_plugin_active               = Divi::is_divi_builder_plugin_active();
				$this->status['plugin_version'] = $is_plugin_active ? Divi::get_builder_version() : '0.0.0';
			}

			return $is_theme_installed || $is_plugin_installed;
		}

		return true === $this->status['is_installed'];
	}

	/**
	 * Check if Divi theme or Divi Builder plugin is active.
	 *
	 * @since  3.3.0
	 * @access protected
	 *
	 * @return bool True if either Divi theme or Divi Builder plugin is active.
	 */
	protected function is_divi_active(): bool {
		if ( ! isset( $this->status['is_active'] ) ) {
			$is_theme_active  = Divi::is_any_divi_theme_active();
			$is_plugin_active = Divi::is_divi_builder_plugin_active();

			$this->status['is_active']        = $is_theme_active || $is_plugin_active;
			$this->status['is_theme_active']  = $is_theme_active;
			$this->status['is_plugin_active'] = $is_plugin_active;

			return $is_theme_active || $is_plugin_active;
		}

		return true === $this->status['is_active'];
	}

	/**
	 * Check if the active Divi version meets the minimum requirements.
	 *
	 * @since  3.3.0
	 * @access protected
	 *
	 * @return bool True if version requirements are met.
	 */
	protected function meets_version_requirements(): bool {
		if ( ! isset( $this->status['meets_version'] ) ) {
			$meets_version = false;

			// Check Divi theme version (if active).
			if ( true === ( $this->status['is_theme_active'] ?? false ) && '0.0.0' !== ( $this->status['theme_version'] ?? '0.0.0' ) ) {
				$meets_version = true === version_compare( (string) ( $this->status['theme_version'] ?? '0.0.0' ), $this->required_version, '>=' );
			}

			// Check Divi Builder plugin version (if active).
			if ( ! $meets_version && true === ( $this->status['is_plugin_active'] ?? false ) && '0.0.0' !== ( $this->status['plugin_version'] ?? '0.0.0' ) ) {
				$meets_version = true === version_compare( (string) ( $this->status['plugin_version'] ?? '0.0.0' ), $this->required_version, '>=' );
			}

			$this->status['meets_version'] = $meets_version;
		}

		return (bool) $this->status['meets_version'];
	}

	/**
	 * Get the required Divi version.
	 *
	 * @since 3.5.0
	 *
	 * @return string
	 */
	public function get_required_version(): string {
		return $this->required_version;
	}

	/**
	 * Get the last error message from requirements check.
	 *
	 * @since  3.3.0
	 * @access public
	 *
	 * @return string The last error message.
	 */
	public function get_last_error(): string {
		return $this->last_error;
	}

	/**
	 * Ensure status is populated by running the fulfillment check if needed.
	 *
	 * @since 3.5.0
	 *
	 * @return void
	 */
	private function ensure_status_populated(): void {
		if ( array() === $this->status ) {
			$this->is_fulfilled();
		}
	}

	/**
	 * Get system requirements status details.
	 *
	 * @since  3.3.0
	 * @access public
	 *
	 * @return array<string, mixed> An array of requirements status details.
	 */
	public function get_status(): array {
		$this->ensure_status_populated();

		return $this->status;
	}

	/**
	 * Convert a PHP memory value to bytes.
	 *
	 * @since  3.4.0
	 * @access public
	 *
	 * @param string $memory_value Memory value (e.g., '128M').
	 *
	 * @return int Memory value in bytes.
	 */
	public function convert_memory_to_bytes( string $memory_value ): int {
		$memory_value = trim( $memory_value );

		// ini_get() can return false/'' for unset directives; nothing to convert.
		if ( '' === $memory_value ) {
			return 0;
		}

		$last  = strtolower( $memory_value[ strlen( $memory_value ) - 1 ] );
		$value = (int) $memory_value;

		switch ( $last ) {
			case 'g':
				$value *= 1024;
				// Fall through intended.
			case 'm':
				$value *= 1024;
				// Fall through intended.
			case 'k':
				$value *= 1024;
		}

		return $value;
	}
}
