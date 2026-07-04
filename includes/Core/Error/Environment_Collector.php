<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Error Environment Collector
 *
 * Collects environment information for error reports including WordPress,
 * PHP, theme, and plugin details. Helps with debugging by providing
 * comprehensive system information for support tickets.
 *
 * @since   3.4.0
 * @since   3.4.4 Updated to use unified Divi detection system
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Core\Error;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use DiviSquad\Utils\Divi;
use DiviSquad\Utils\WP;
use Throwable;
use WP_Theme;

/**
 * Error Environment Collector Class
 *
 * Collects detailed environment information for error reports.
 *
 * Features:
 * - PHP environment details
 * - WordPress configuration information
 * - Active plugins and themes
 * - Divi framework detection and analysis
 *
 * @since   3.4.0
 * @since   3.4.4 Updated to use unified Divi detection
 * @package DiviSquad
 */
class Environment_Collector {
	/**
	 * Get environment information
	 *
	 * Collects information about the WordPress environment for debugging.
	 *
	 * @since 3.4.0
	 *
	 * @return array<string, mixed> Environment information.
	 */
	public function get_environment_info(): array {
		try {
			// Basic environment information..
			$environment = array(
				'php_version'      => PHP_VERSION,
				'wp_version'       => get_bloginfo( 'version' ),
				'plugin_version'   => divi_squad()->get_version_dot(),
				'divi_version'     => Divi::get_builder_version(),
				'divi_mode'        => Divi::get_builder_mode(),
				'memory_limit'     => ini_get( 'memory_limit' ),
				'is_multisite'     => is_multisite() ? 'Yes' : 'No',
				'active_plugins'   => $this->get_active_plugins_list(),
				'installed_themes' => $this->get_installed_themes_list(),
			);

			// Get current theme information..
			$current_theme = wp_get_theme();
			if ( $current_theme instanceof WP_Theme ) {
				$environment['active_theme_name']    = $current_theme->get( 'Name' );
				$environment['active_theme_version'] = $current_theme->get( 'Version' );
				$environment['active_theme_author']  = $current_theme->get( 'Author' );

				// Check if it's a child theme..
				$parent_theme = $current_theme->parent();
				if ( $parent_theme instanceof WP_Theme ) {
					$environment['is_child_theme']       = 'Yes';
					$environment['parent_theme_name']    = $parent_theme->get( 'Name' );
					$environment['parent_theme_version'] = $parent_theme->get( 'Version' );
					$environment['parent_theme_author']  = $parent_theme->get( 'Author' );
				} else {
					$environment['is_child_theme'] = 'No';
				}
			}

			// Collect detailed Divi detection information from unified utility..
			$environment = $this->add_divi_detection_info( $environment );

			/**
			 * Filter the environment information included in error reports.
			 *
			 * @since 3.4.0
			 *
			 * @param array<string, mixed> $environment Environment information.
			 */
			return apply_filters( 'divi_squad_error_report_environment_info', $environment );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Error collecting environment information', false );

			return array(
				'error_collecting_environment' => $e->getMessage(),
				'php_version'                  => PHP_VERSION,
				'wp_version'                   => get_bloginfo( 'version' ),
			);
		}
	}

	/**
	 * Add Divi detection information to environment data
	 *
	 * Updated to use the centralized Divi utility class for consistent detection
	 * throughout the plugin.
	 *
	 * @since  3.4.0
	 * @since  3.4.4 Updated to use unified Divi detection
	 * @access protected
	 *
	 * @param array<string, mixed> $environment Base environment information.
	 *
	 * @return array<string, mixed> Enhanced environment with Divi detection info.
	 */
	protected function add_divi_detection_info( array $environment ): array {
		try {
			// Use centralized Divi detection instead of duplicating logic.
			$divi_info = Divi::get_divi_environment_info();

			// Add all relevant Divi info to the environment array.
			$environment['divi_version']          = $divi_info['version'];
			$environment['divi_mode']             = $divi_info['builder_mode'];
			$environment['divi_detection_method'] = $divi_info['version_detection_method'];

			// Include theme information.
			if ( isset( $divi_info['theme_name'] ) ) {
				$environment['active_theme_name'] = $divi_info['theme_name'];
			}

			// Add child theme info if applicable.
			$is_child_theme                = true === $divi_info['is_child_theme'];
			$environment['is_child_theme'] = $is_child_theme ? 'Yes' : 'No';
			if ( $is_child_theme && is_string( $divi_info['parent_theme_name'] ) && '' !== $divi_info['parent_theme_name'] ) {
				$environment['parent_theme_name'] = $divi_info['parent_theme_name'];
			}

			// Add modification status.
			$environment['divi_modified'] = $divi_info['is_modified'];

			// Add constants information if available.
			if ( is_array( $divi_info['defined_constants'] ) && array() !== $divi_info['defined_constants'] ) {
				$environment['divi_constants'] = $divi_info['defined_constants'];
			}

			// Add framework source information.
			$environment['divi_framework_source'] = $divi_info['framework_source'];

			return $environment;
		} catch ( Throwable $e ) {
			divi_squad()->log_error(
				$e,
				'Error adding Divi detection info',
				false,
				array(
					'function'    => __METHOD__,
					'environment' => array_keys( $environment ),
				)
			);
			$environment['divi_detection_error'] = $e->getMessage();

			return $environment;
		}
	}

	/**
	 * Get active themes
	 *
	 * Retrieves a formatted list of active themes for debugging.
	 *
	 * @since 3.4.0
	 * @return string Comma-separated list of active themes.
	 */
	protected function get_installed_themes_list(): string {
		try {
			$wp_themes = wp_get_themes();

			/**
			 * Filter the list of installed themes included in error reports.
			 *
			 * @since 3.4.0
			 *
			 * @param array<string, WP_Theme> $wp_themes List of installed themes.
			 */
			$wp_themes = apply_filters( 'divi_squad_error_report_installed_themes', $wp_themes );

			$installed_themes = array();
			foreach ( $wp_themes as $theme ) {
				// Check if the theme is Divi-based..
				$is_divi_based = false;
				$name          = $theme->get( 'Name' );

				if ( in_array( $name, array( 'Divi', 'Extra' ), true ) ) {
					$is_divi_based = true;
				}

				// Check for Divi as parent..
				$parent = $theme->parent();
				if ( $parent instanceof WP_Theme && in_array( $parent->get( 'Name' ), array( 'Divi', 'Extra' ), true ) ) {
					$is_divi_based = true;
				}

				// Check for other Divi markers..
				if ( ! $is_divi_based ) {
					$theme_dir = $theme->get_stylesheet_directory();
					if ( divi_squad()->get_wp_fs()->exists( $theme_dir . '/includes/builder' ) &&
						 ( divi_squad()->get_wp_fs()->exists( $theme_dir . '/epanel' ) || divi_squad()->get_wp_fs()->exists( $theme_dir . '/core' ) ) ) {
						$is_divi_based = true;
					}
				}

				$theme_info = sprintf(
					'%s (%s)%s',
					$name,
					$theme->get( 'Version' ),
					$is_divi_based ? ' [Divi-based]' : ''
				);

				$installed_themes[] = $theme_info;
			}

			return implode( ', ', $installed_themes );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Error getting installed themes list', false );

			return 'Error collecting themes: ' . $e->getMessage();
		}
	}

	/**
	 * Get active plugins
	 *
	 * Retrieves a formatted list of active plugins for debugging.
	 *
	 * @since 3.4.0
	 * @return string Comma-separated list of active plugins.
	 */
	protected function get_active_plugins_list(): string {
		try {
			$active_plugins = WP::get_active_plugins();

			/**
			 * Filter the list of active plugins included in error reports.
			 *
			 * @since 3.4.0
			 *
			 * @param array<array<string, string>> $active_plugins List of active plugins.
			 */
			$active_plugins = apply_filters( 'divi_squad_error_report_active_plugins', $active_plugins );

			if ( 0 === count( $active_plugins ) ) {
				return '';
			}

			foreach ( $active_plugins as $key => $plugin ) {
				if ( ! isset( $plugin['name'], $plugin['version'] ) ) {
					unset( $active_plugins[ $key ] );
				}

				$active_plugins[ $key ]['name'] = sprintf(
					'%s (%s)',
					$plugin['name'],
					$plugin['version']
				);
			}

			return implode( ', ', array_column( $active_plugins, 'name' ) );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Error getting active plugins list', false );

			return 'Error collecting plugins: ' . $e->getMessage();
		}
	}
}
