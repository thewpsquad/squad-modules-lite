<?php // phpcs:ignore WordPress.Files.FileName

/**
 * System Information Collector
 *
 * Efficiently collects system environment data for error reports.
 *
 * @since   3.4.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Core\Error;

use DiviSquad\Utils\Divi;
use DiviSquad\Utils\WP;
use Throwable;
use WP_Theme;

/**
 * System_Info Class
 *
 * Optimized system information collection with caching.
 *
 * @since   3.4.0
 * @package DiviSquad
 */
class System_Info {
	/**
	 * Cached environment data
	 *
	 * @var array<string, mixed>|null
	 */
	private static ?array $env_cache = null;

	/**
	 * Get system environment info
	 *
	 * @return array<string, mixed> Environment data
	 */
	public function collect(): array {
		if ( null !== self::$env_cache ) {
			return self::$env_cache;
		}

		try {
			self::$env_cache = $this->build_environment_data();
			return self::$env_cache;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Environment collection failed', false );
			return $this->get_minimal_environment();
		}
	}

	/**
	 * Build complete environment data
	 *
	 * @return array<string, mixed> Environment info
	 */
	private function build_environment_data(): array {
		$env = array(
			// Core versions
			'php_version'    => PHP_VERSION,
			'wp_version'     => get_bloginfo( 'version' ),
			'plugin_version' => divi_squad()->get_version_dot(),
			'memory_limit'   => ini_get( 'memory_limit' ),
			'is_multisite'   => is_multisite(),

			// Divi info
			'divi_version'   => Divi::get_builder_version(),
			'divi_mode'      => Divi::get_builder_mode(),

			// Theme and plugins
			'active_theme'   => $this->get_theme_info(),
			'active_plugins' => $this->get_plugins_summary(),
		);

		// Add Divi detection details
		$env = array_merge( $env, $this->get_divi_detection_info() );

		return apply_filters( 'divi_squad_system_info', $env );
	}

	/**
	 * Get theme information
	 *
	 * @return array<string, mixed> Theme info
	 */
	private function get_theme_info(): array {
		$theme = wp_get_theme();

		if ( ! $theme instanceof WP_Theme ) {
			return array(
				'name'    => 'Unknown',
				'version' => '0.0.0',
			);
		}

		$info = array(
			'name'    => $theme->get( 'Name' ),
			'version' => $theme->get( 'Version' ),
			'author'  => $theme->get( 'Author' ),
		);

		$parent = $theme->parent();
		if ( $parent instanceof WP_Theme ) {
			$info['parent'] = array(
				'name'    => $parent->get( 'Name' ),
				'version' => $parent->get( 'Version' ),
			);
		}

		return $info;
	}

	/**
	 * Get plugins summary
	 *
	 * @return string Formatted plugin list
	 */
	private function get_plugins_summary(): string {
		try {
			$active_plugins = WP::get_active_plugins();
			if ( 0 === count( $active_plugins ) ) {
				return 'No active plugins';
			}

			$plugin_names = array();
			foreach ( $active_plugins as $plugin ) {
				if ( isset( $plugin['name'], $plugin['version'] ) ) {
					$plugin_names[] = sprintf( '%s (%s)', $plugin['name'], $plugin['version'] );
				}
			}

			return implode( ', ', array_slice( $plugin_names, 0, 10 ) ) .
					( count( $plugin_names ) > 10 ? ' ...' : '' );

		} catch ( Throwable $e ) {
			return 'Plugin collection failed: ' . $e->getMessage();
		}
	}

	/**
	 * Get Divi detection information
	 *
	 * @return array<string, mixed> Divi detection data
	 */
	private function get_divi_detection_info(): array {
		try {
			$detection = array(
				'divi_detected'    => false,
				'detection_method' => 'None',
				'divi_constants'   => array(),
				'divi_functions'   => array(),
			);

			// Check constants
			$constants = Divi::get_defined_divi_constants();
			if ( 0 !== count( $constants ) ) {
				$detection['divi_detected']    = true;
				$detection['detection_method'] = 'Constants';
				$detection['divi_constants']   = $constants;
			}

			// Check functions
			$functions           = array( 'et_setup_theme', 'et_pb_is_pagebuilder_used', 'et_core_is_fb_enabled' );
			$available_functions = Divi::get_available_divi_functions( $functions );

			if ( 0 !== count( $available_functions ) ) {
				$detection['divi_detected']  = true;
				$detection['divi_functions'] = $available_functions;

				if ( 'None' === $detection['detection_method'] ) {
					$detection['detection_method'] = 'Functions';
				}
			}

			return $detection;

		} catch ( Throwable $e ) {
			return array(
				'divi_detected'    => false,
				'detection_method' => 'Error: ' . $e->getMessage(),
			);
		}
	}

	/**
	 * Get minimal environment on failure
	 *
	 * @return array<string, mixed> Minimal env data
	 */
	private function get_minimal_environment(): array {
		return array(
			'php_version' => PHP_VERSION,
			'wp_version'  => get_bloginfo( 'version' ),
			'error'       => 'Full environment collection failed',
		);
	}

	/**
	 * Clear environment cache
	 */
	public static function clear_cache(): void {
		self::$env_cache = null;
	}
}
