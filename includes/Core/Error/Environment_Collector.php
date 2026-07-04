<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Error Environment Collector
 *
 * Collects environment information for error reports including WordPress,
 * PHP, theme, and plugin details. Helps with debugging by providing
 * comprehensive system information for support tickets.
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
			// Basic environment information.
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

			// Get current theme information.
			$current_theme = wp_get_theme();
			if ( $current_theme instanceof WP_Theme ) {
				$environment['active_theme_name']    = $current_theme->get( 'Name' );
				$environment['active_theme_version'] = $current_theme->get( 'Version' );
				$environment['active_theme_author']  = $current_theme->get( 'Author' );

				// Check if it's a child theme.
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

			// Collect detailed Divi detection information.
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
	 * Collects detailed information about Divi detection methods,
	 * customizations, and theme status to help with debugging.
	 *
	 * @since  3.4.0
	 * @access protected
	 *
	 * @param array<string, mixed> $environment Base environment information.
	 *
	 * @return array<string, mixed> Enhanced environment with Divi detection info.
	 */
	protected function add_divi_detection_info( array $environment ): array {
		try {
			// Set detection method placeholder.
			$detection_method = 'Unknown';

			// Check for Divi constants (very reliable signal).
			$divi_constants = Divi::get_defined_divi_constants();

			if ( count( $divi_constants ) > 0 ) {
				$detection_method              = 'Constants: ' . implode( ', ', $divi_constants );
				$environment['divi_constants'] = $divi_constants;
			}

			// Check for theme modifications.
			$current_theme       = wp_get_theme();
			$is_modified         = false;
			$standard_divi_theme = in_array( $current_theme->get( 'Name' ), array( 'Divi', 'Extra' ), true );
			$is_child_theme      = (bool) $current_theme->parent();

			// If it's not a standard Divi/Extra theme and not a direct child theme, it's likely modified.
			if ( ! $standard_divi_theme && ! $is_child_theme && Divi::is_any_divi_theme_active() ) {
				$is_modified      = true;
				$detection_method = 'Custom theme with Divi framework';
			}

			// Child theme detection.
			if ( $is_child_theme ) {
				$parent = $current_theme->parent();
				if ( $parent instanceof \WP_Theme && in_array( $parent->get( 'Name' ), array( 'Divi', 'Extra' ), true ) ) {
					$detection_method = 'Child theme of ' . $parent->get( 'Name' );
				}
			}

			// Plugin detection.
			if ( Divi::is_divi_builder_plugin_active() ) {
				$detection_method = 'Divi Builder Plugin';

				// Add plugin specific info.
				if ( defined( 'ET_BUILDER_PLUGIN_VERSION' ) ) {
					$environment['plugin_specific_version'] = ET_BUILDER_PLUGIN_VERSION;
				}
			}

			// Check for Divi functions.
			$divi_functions = array(
				'et_setup_theme',
				'et_divi_fonts_url',
				'et_pb_is_pagebuilder_used',
				'et_core_is_fb_enabled',
				'et_builder_get_fonts',
				'et_builder_bfb_enabled',
				'et_fb_is_theme_builder_used_on_page',
			);

			/**
			 * Filter the Divi functions to check in Site Health.
			 *
			 * Allows modification of which Divi functions are checked and reported.
			 *
			 * @since 3.3.3
			 *
			 * @param array<string> $divi_functions The Divi functions to check.
			 */
			$divi_functions = apply_filters( 'divi_squad_error_report_functions_check', $divi_functions );

			// Check for Divi functions.
			$available_functions = Divi::get_available_divi_functions( $divi_functions );

			if ( count( $available_functions ) > 0 ) {
				$environment['divi_functions'] = $available_functions;
				if ( 'Unknown' === $detection_method ) {
					$detection_method = 'Functions: ' . implode( ', ', array_slice( $available_functions, 0, 3 ) );
				}
			}

			// Check for directory structure (least reliable but useful as fallback).
			if ( 'Unknown' === $detection_method && $current_theme instanceof WP_Theme ) {
				$theme_dir = $current_theme->get_stylesheet_directory();

				/**
				 * Filter to Divi directories that are checked for detection
				 *
				 * @since 3.4.0
				 *
				 * @param array<string> $directory_markers List of Divi directories to check.
				 * @param string        $theme_dir         Theme directory path.
				 */
				$directory_markers = apply_filters(
					'divi_squad_error_report_theme_directory_markers',
					array(
						'includes/builder',
						'epanel',
						'core',
						'includes/builder/feature',
						'includes/builder/frontend-builder',
					),
					$theme_dir
				);

				$found_markers = array();
				foreach ( $directory_markers as $marker ) {
					$path = trailingslashit( $theme_dir ) . $marker;
					if ( divi_squad()->get_wp_fs()->exists( $path ) ) {
						$found_markers[] = $marker;
					}
				}

				if ( count( $found_markers ) >= 2 ) {
					$detection_method                      = 'Directory structure: ' . implode( ', ', $found_markers );
					$environment['divi_directory_markers'] = $found_markers;
				}
			}

			// Add detection method and modification status to environment.
			$environment['divi_detection_method'] = $detection_method;
			$environment['divi_modified']         = $is_modified;

			// Include framework source info.
			if ( $is_child_theme && isset( $environment['parent_theme_name'] ) ) {
				$environment['divi_framework_source'] = 'Parent Theme: ' . $environment['parent_theme_name'];
			} elseif ( Divi::is_divi_builder_plugin_active() ) {
				$environment['divi_framework_source'] = 'Divi Builder Plugin';
			} else {
				$environment['divi_framework_source'] = 'Direct Theme';
			}

			// Include divi theme status details.
			try {
				$environment['status_details'] = divi_squad()->requirements->get_status();
			} catch ( Throwable $e ) {
				// If we encounter any errors, just continue without this data.
				$environment['requirements_error'] = $e->getMessage();
			}

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
				// Check if the theme is Divi-based.
				$is_divi_based = false;
				$name          = $theme->get( 'Name' );

				if ( in_array( $name, array( 'Divi', 'Extra' ), true ) ) {
					$is_divi_based = true;
				}

				// Check for Divi as parent.
				$parent = $theme->parent();
				if ( $parent instanceof WP_Theme && in_array( $parent->get( 'Name' ), array( 'Divi', 'Extra' ), true ) ) {
					$is_divi_based = true;
				}

				// Check for other Divi markers.
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
