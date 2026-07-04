<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Site Health Info.
 *
 * @since   3.1.0
 * @author  The WP Squad <support@squadmodules.com>
 * @package DiviSquad
 */

namespace DiviSquad\Core\Supports;

use DiviSquad\Core\Extensions;
use DiviSquad\Core\Requirements;
use DiviSquad\Core\Supports\Utils\Date_Time;
use DiviSquad\Utils\Divi;
use Throwable;
use WP_Theme;

/**
 * Site Health Info Manager.
 *
 * Adds Squad Modules and Divi information to the WordPress Site Health report.
 * Includes detailed Divi detection information to support troubleshooting.
 *
 * @since   3.1.0
 * @since   3.3.3 Added enhanced Divi theme detection information
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */
class Site_Health {

	/**
	 * Site_Health constructor.
	 */
	public function __construct() {
		add_filter( 'debug_information', array( $this, 'add_info_section' ) );

		/**
		 * Action triggered after Site_Health is initialized.
		 *
		 * @since 3.3.3
		 *
		 * @param Site_Health $site_health The Site_Health instance.
		 */
		do_action( 'divi_squad_site_health_init', $this );
	}

	/**
	 * Add section to Info tab.
	 *
	 * @since 3.1.0
	 *
	 * @param array<string, mixed> $debug_info Array with debug information.
	 *
	 * @return array<string, mixed>
	 */
	public function add_info_section( array $debug_info ): array {
		try {
			$section = array(
				'label'       => esc_html__( 'Divi Squad', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'The Divi Squad plugin stores some data in the database and interacts with Divi theme/plugin.', 'squad-modules-for-divi' ),
				'fields'      => $this->get_info_fields(),
			);

			/**
			 * Filter the Divi Squad debug information.
			 *
			 * @since 3.1.0
			 *
			 * @param array $section The Divi Squad debug information.
			 */
			$debug_info['divi-squad'] = apply_filters( 'divi_squad_debug_information', $section );

			// Prepare DiviSquad requirements section.
			$divi_squad_requirements_section = array(
				'label'       => esc_html__( 'Divi Squad System Requirements', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Detailed information about Divi environment detection and status.', 'squad-modules-for-divi' ),
				'fields'      => $this->get_divi_detection_fields(),
			);

			/**
			 * Filter the DiviSquad requirements section.
			 *
			 * @since 3.3.3
			 *
			 * @param array $divi_squad_requirements_section The DiviSquad requirements section.
			 */
			$debug_info['divi-squad-requirements'] = apply_filters( 'divi_squad_requirements_section', $divi_squad_requirements_section );

			/**
			 * Action triggered after Site Health sections are added.
			 *
			 * @since 3.3.3
			 *
			 * @param array<string, mixed> $debug_info The debug info with our sections added.
			 */
			do_action( 'divi_squad_after_add_site_health_sections', $debug_info );

			return $debug_info;
		} catch ( Throwable $e ) {
			// Log the error.
			divi_squad()->log_error(
				$e,
				'Failed to add Site Health sections',
				false,
				array( 'function' => __METHOD__ )
			);

			// Return unmodified debug info in case of an error to avoid breaking Site Health.
			return $debug_info;
		}
	}

	/**
	 * Get info fields for the Site Health section.
	 *
	 * @return array<string, array<string, string>>
	 */
	private function get_info_fields(): array {
		try {
			$activated_time = divi_squad()->memory->get( 'activation_time' );
			$installed_date = Date_Time::datetime_format( $activated_time, '', true );

			$fields = array(
				'version-core'      => array(
					'label' => esc_html__( 'Core Version', 'squad-modules-for-divi' ),
					'value' => divi_squad()->get_version_dot(),
				),
				'install-date-core' => array(
					'label' => esc_html__( 'Core installed date', 'squad-modules-for-divi' ),
					'value' => $installed_date,
				),
				'divi-version'      => array(
					'label' => esc_html__( 'Divi Version', 'squad-modules-for-divi' ),
					'value' => '' !== Divi::get_builder_version() ? Divi::get_builder_version() : esc_html__( 'Not detected', 'squad-modules-for-divi' ),
				),
				'divi-mode'         => array(
					'label' => esc_html__( 'Divi Mode', 'squad-modules-for-divi' ),
					'value' => ucfirst( Divi::get_builder_mode() ),
				),
				'requirements-met'  => array(
					'label' => esc_html__( 'Requirements Status', 'squad-modules-for-divi' ),
					'value' => $this->get_requirements_status(),
				),
			);

			/**
			 * Filter the core info fields before adding extension-specific fields.
			 *
			 * @since 3.3.3
			 *
			 * @param array<string, array<string, string>> $fields The core info fields.
			 */
			$fields = apply_filters( 'divi_squad_core_info_fields', $fields );

			// Get extension fields if available.
			$extension_fields = $this->get_extension_fields();
			if ( count( $extension_fields ) > 0 ) {
				$fields = array_merge( $fields, $extension_fields );
			}

			/**
			 * Filter the Divi Squad site health info fields.
			 *
			 * @since 3.2.0
			 *
			 * @param array<string, array<string, string>> $fields The Divi Squad site health info fields.
			 */
			return apply_filters( 'divi_squad_site_health_info_fields', $fields );
		} catch ( Throwable $e ) {
			// Log the error.
			divi_squad()->log_error(
				$e,
				'Failed to get Site Health info fields',
				false,
				array( 'function' => __METHOD__ )
			);

			// Return a minimal set of fields in case of error.
			return array(
				'error-occurred' => array(
					'label' => esc_html__( 'Error Occurred', 'squad-modules-for-divi' ),
					'value' => esc_html__( 'An error occurred while gathering plugin information. Please check the logs.', 'squad-modules-for-divi' ),
				),
			);
		}
	}

	/**
	 * Get extension-specific fields for the Site Health section.
	 *
	 * @since 3.3.3
	 *
	 * @return array<string, array<string, string>>
	 */
	private function get_extension_fields(): array {
		$extension_fields = array();

		try {
			// Check if the extensions exists.
			if ( divi_squad()->extensions instanceof Extensions ) {
				$extension_statuses = array();

				// Get active extensions.
				$active_extensions = divi_squad()->extensions->get_active_registries();

				if ( count( $active_extensions ) > 0 ) {
					foreach ( $active_extensions as $extension ) {
						$extension_statuses[] = $extension['name'] . ': ' . esc_html__( 'Active', 'squad-modules-for-divi' );
					}
				}

				// Add extensions field if we have any data.
				if ( count( $extension_statuses ) > 0 ) {
					$extension_fields['active-extensions'] = array(
						'label' => esc_html__( 'Active Extensions', 'squad-modules-for-divi' ),
						'value' => implode( ', ', $extension_statuses ),
					);
				}
			}

			/**
			 * Filter the extension fields for Site Health.
			 *
			 * @since 3.3.3
			 *
			 * @param array<string, array<string, string>> $extension_fields The extension fields.
			 */
			return apply_filters( 'divi_squad_extension_info_fields', $extension_fields );
		} catch ( Throwable $e ) {
			// Log the error but don't stop execution.
			divi_squad()->log_error(
				$e,
				'Failed to get extension fields for Site Health',
				false,
				array( 'function' => __METHOD__ )
			);

			return array();
		}
	}

	/**
	 * Get detailed Divi detection fields for the Site Health section.
	 *
	 * @since 3.3.3
	 *
	 * @return array<string, array<string, string>>
	 */
	private function get_divi_detection_fields(): array {
		try {
			$current_theme = wp_get_theme();

			// Basic theme information.
			$fields = array(
				'active-theme' => array(
					'label' => esc_html__( 'Active Theme', 'squad-modules-for-divi' ),
					'value' => $current_theme->get( 'Name' ) . ' ' . $current_theme->get( 'Version' ),
				),
			);

			// Add theme author details.
			$theme_author = $current_theme->get( 'Author' );
			if ( '' !== $theme_author ) {
				$fields['theme-author'] = array(
					'label' => esc_html__( 'Theme Author', 'squad-modules-for-divi' ),
					'value' => $theme_author,
				);
			}

			// Check if it's a child theme.
			if ( $current_theme->parent() instanceof WP_Theme ) {
				$fields['parent-theme'] = array(
					'label' => esc_html__( 'Parent Theme', 'squad-modules-for-divi' ),
					'value' => $current_theme->parent()->get( 'Name' ) . ' ' . $current_theme->parent()->get( 'Version' ),
				);

				$fields['is-divi-child'] = array(
					'label' => esc_html__( 'Is Divi Child Theme', 'squad-modules-for-divi' ),
					'value' => in_array( $current_theme->parent()->get( 'Name' ), array( 'Divi', 'Extra' ), true )
						? esc_html__( 'Yes', 'squad-modules-for-divi' )
						: esc_html__( 'No', 'squad-modules-for-divi' ),
				);
			}

			// Divi theme detection results.
			$fields['is-divi-theme-installed'] = array(
				'label' => esc_html__( 'Divi Theme Installed', 'squad-modules-for-divi' ),
				'value' => Divi::is_any_divi_theme_installed()
					? esc_html__( 'Yes', 'squad-modules-for-divi' )
					: esc_html__( 'No', 'squad-modules-for-divi' ),
			);

			$fields['is-divi-theme-active'] = array(
				'label' => esc_html__( 'Divi Theme Active', 'squad-modules-for-divi' ),
				'value' => Divi::is_any_divi_theme_active()
					? esc_html__( 'Yes', 'squad-modules-for-divi' )
					: esc_html__( 'No', 'squad-modules-for-divi' ),
			);

			// Divi plugin detection results.
			$fields['is-divi-plugin-installed'] = array(
				'label' => esc_html__( 'Divi Builder Plugin Installed', 'squad-modules-for-divi' ),
				'value' => Divi::is_divi_builder_plugin_installed()
					? esc_html__( 'Yes', 'squad-modules-for-divi' )
					: esc_html__( 'No', 'squad-modules-for-divi' ),
			);

			$fields['is-divi-plugin-active'] = array(
				'label' => esc_html__( 'Divi Builder Plugin Active', 'squad-modules-for-divi' ),
				'value' => Divi::is_divi_builder_plugin_active()
					? esc_html__( 'Yes', 'squad-modules-for-divi' )
					: esc_html__( 'No', 'squad-modules-for-divi' ),
			);

			// Allowed themes check.
			$fields['allowed-themes'] = array(
				'label' => esc_html__( 'Allowed Themes', 'squad-modules-for-divi' ),
				'value' => implode( ', ', Divi::modules_allowed_theme() ),
			);

			// Is allowed theme activated.
			$fields['is-allowed-theme-active'] = array(
				'label' => esc_html__( 'Is Allowed Theme Active', 'squad-modules-for-divi' ),
				'value' => Divi::is_allowed_theme_activated()
					? esc_html__( 'Yes', 'squad-modules-for-divi' )
					: esc_html__( 'No', 'squad-modules-for-divi' ),
			);

			/**
			 * Filter the basic Divi detection fields before adding technical details.
			 *
			 * @since 3.3.3
			 *
			 * @param array<string, array<string, string>> $fields        The basic Divi detection fields.
			 * @param WP_Theme                             $current_theme The current theme.
			 */
			$fields = apply_filters( 'divi_squad_basic_divi_detection_fields', $fields, $current_theme );

			// Add technical details if allowed.
			if ( $this->should_include_technical_details() ) {
				$fields = array_merge( $fields, $this->get_technical_divi_details( $current_theme ) );
			}

			/**
			 * Filter the Divi detection Site Health fields.
			 *
			 * @since 3.3.3
			 *
			 * @param array<string, array<string, string>> $fields The Divi detection Site Health fields.
			 */
			return apply_filters( 'divi_squad_divi_detection_fields', $fields );
		} catch ( Throwable $e ) {
			// Log the error.
			divi_squad()->log_error(
				$e,
				'Failed to get Divi detection fields for Site Health',
				false,
				array( 'function' => __METHOD__ )
			);

			// Return a minimal set of fields in case of error.
			return array(
				'detection-error' => array(
					'label' => esc_html__( 'Detection Error', 'squad-modules-for-divi' ),
					'value' => esc_html__( 'An error occurred while gathering Divi detection information. Please check the logs.', 'squad-modules-for-divi' ),
				),
			);
		}
	}

	/**
	 * Get technical Divi detection details.
	 *
	 * @since 3.3.3
	 *
	 * @param WP_Theme $current_theme The current theme.
	 *
	 * @return array<string, array<string, string>> Technical Divi detection details.
	 */
	private function get_technical_divi_details( WP_Theme $current_theme ): array {
		$technical_fields = array();

		try {
			// Divi constants.
			$divi_constants = array(
				'ET_CORE_VERSION',
				'ET_BUILDER_VERSION',
				'ET_BUILDER_THEME',
				'ET_BUILDER_PLUGIN_VERSION',
				'ET_BUILDER_PLUGIN_DIR',
				'ET_BUILDER_PLUGIN_URI',
				'ET_BUILDER_DIR',
				'ET_BUILDER_URI',
				'ET_BUILDER_LAYOUT_POST_TYPE',
			);

			/**
			 * Filter the Divi constants to check in Site Health.
			 *
			 * @since 3.3.3
			 *
			 * @param array<string> $divi_constants The Divi constants to check.
			 */
			$divi_constants = apply_filters( 'divi_squad_divi_constants_to_check', $divi_constants );

			$defined_constants = array();
			foreach ( $divi_constants as $constant ) {
				if ( defined( $constant ) ) {
					$defined_constants[] = $constant . ': ' . constant( $constant );
				}
			}

			$technical_fields['divi-constants'] = array(
				'label' => esc_html__( 'Divi Constants', 'squad-modules-for-divi' ),
				'value' => count( $defined_constants ) > 0
					? implode( ', ', $defined_constants )
					: esc_html__( 'None detected', 'squad-modules-for-divi' ),
			);

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
			 * @since 3.3.3
			 *
			 * @param array<string> $divi_functions The Divi functions to check.
			 */
			$divi_functions = apply_filters( 'divi_squad_divi_functions_to_check', $divi_functions );

			$available_functions = array();
			foreach ( $divi_functions as $function ) {
				if ( is_callable( $function ) ) {
					$available_functions[] = $function;
				}
			}

			$technical_fields['divi-functions'] = array(
				'label' => esc_html__( 'Divi Functions', 'squad-modules-for-divi' ),
				'value' => count( $available_functions ) > 0
					? implode( ', ', $available_functions )
					: esc_html__( 'None detected', 'squad-modules-for-divi' ),
			);

			// Directory structure analysis.
			$theme_dir         = $current_theme->get_stylesheet_directory();
			$directory_markers = array(
				'includes/builder',
				'epanel',
				'core',
				'includes/builder/feature',
				'includes/builder/frontend-builder',
			);

			/**
			 * Filter the directory markers to check for Divi theme detection.
			 *
			 * @since 3.3.3
			 *
			 * @param array<string> $directory_markers The directory markers to check.
			 * @param string        $theme_dir         The theme directory path.
			 */
			$directory_markers = apply_filters( 'divi_squad_divi_directory_markers', $directory_markers, $theme_dir );

			$found_markers = array();
			foreach ( $directory_markers as $marker ) {
				$path = trailingslashit( $theme_dir ) . $marker;
				if ( file_exists( $path ) ) {
					$found_markers[] = $marker;
				}
			}

			$technical_fields['divi-directory-markers'] = array(
				'label' => esc_html__( 'Divi Directory Markers', 'squad-modules-for-divi' ),
				'value' => count( $found_markers ) > 0
					? implode( ', ', $found_markers )
					: esc_html__( 'None detected', 'squad-modules-for-divi' ),
			);

			// Detection method used.
			$technical_fields['divi-detection-method'] = array(
				'label' => esc_html__( 'Primary Detection Method', 'squad-modules-for-divi' ),
				'value' => $this->determine_detection_method(),
			);

			// Dynamic CSS enabled.
			$technical_fields['dynamic-css-enabled'] = array(
				'label' => esc_html__( 'Dynamic CSS Enabled', 'squad-modules-for-divi' ),
				'value' => Divi::is_dynamic_css_enable()
					? esc_html__( 'Yes', 'squad-modules-for-divi' )
					: esc_html__( 'No', 'squad-modules-for-divi' ),
			);

			// Add requirements details.
			if ( divi_squad()->requirements instanceof Requirements ) {
				try {
					$status_details                           = divi_squad()->requirements->get_status();
					$technical_fields['requirements-details'] = array(
						'label' => esc_html__( 'Requirements Details', 'squad-modules-for-divi' ),
						'value' => '<pre>' . esc_html( (string) wp_json_encode( $status_details, JSON_PRETTY_PRINT ) ) . '</pre>',
					);
				} catch ( Throwable $e ) {
					$technical_fields['requirements-error'] = array(
						'label' => esc_html__( 'Requirements Error', 'squad-modules-for-divi' ),
						'value' => esc_html( $e->getMessage() ),
					);
				}
			}

			/**
			 * Filter the technical Divi detection fields.
			 *
			 * @since 3.3.3
			 *
			 * @param array<string, array<string, string>> $technical_fields The technical Divi detection fields.
			 * @param WP_Theme                             $current_theme    The current theme.
			 */
			return apply_filters( 'divi_squad_technical_divi_detection_fields', $technical_fields, $current_theme );
		} catch ( Throwable $e ) {
			// Log the error.
			divi_squad()->log_error(
				$e,
				'Failed to get technical Divi details for Site Health',
				false,
				array( 'function' => __METHOD__ )
			);

			return array(
				'technical-details-error' => array(
					'label' => esc_html__( 'Technical Details Error', 'squad-modules-for-divi' ),
					'value' => esc_html__( 'An error occurred while gathering technical Divi detection details. Please check the logs.', 'squad-modules-for-divi' ),
				),
			);
		}
	}

	/**
	 * Determine whether to include technical details in the Site Health report.
	 *
	 * @since 3.3.3
	 *
	 * @return bool Whether to include technical details.
	 */
	private function should_include_technical_details(): bool {
		/**
		 * Filter whether to include technical details in the Site Health report.
		 *
		 * @since 3.3.3
		 *
		 * @param bool $include_technical_details Whether to include technical details.
		 */
		return apply_filters( 'divi_squad_include_technical_details', true );
	}

	/**
	 * Get requirements status for display.
	 *
	 * @since 3.3.3
	 *
	 * @return string
	 */
	private function get_requirements_status(): string {
		try {
			if ( divi_squad()->requirements instanceof Requirements ) {
				$is_fulfilled = divi_squad()->requirements->is_fulfilled();
				if ( $is_fulfilled ) {
					return esc_html__( 'All requirements met ✅', 'squad-modules-for-divi' );
				}

				$error = divi_squad()->requirements->get_last_error();

				return esc_html__( 'Requirements not met ⚠️: ', 'squad-modules-for-divi' ) . esc_html( $error );
			}

			return esc_html__( 'Requirements status unknown', 'squad-modules-for-divi' );
		} catch ( Throwable $e ) {
			// Log the error.
			divi_squad()->log_error(
				$e,
				'Failed to get requirements status for Site Health',
				false,
				array( 'function' => __METHOD__ )
			);

			return esc_html__( 'Error checking requirements: ', 'squad-modules-for-divi' ) . esc_html( $e->getMessage() );
		}
	}

	/**
	 * Determine the primary detection method used for Divi.
	 *
	 * Identifies which method was successfully used to detect Divi presence.
	 *
	 * @since 3.3.3
	 *
	 * @return string The detection method used.
	 */
	private function determine_detection_method(): string {
		try {
			// Define detection strategies with their check functions.
			$detection_strategies = array(
				'plugin'       => array(
					'label' => esc_html__( 'Divi Builder Plugin', 'squad-modules-for-divi' ),
					'check' => function () {
						return Divi::is_divi_builder_plugin_active();
					},
				),
				'direct_theme' => array(
					'label' => esc_html__( 'Direct Theme Name Match', 'squad-modules-for-divi' ),
					'check' => function () {
						$current_theme = wp_get_theme();

						return in_array( $current_theme->get( 'Name' ), array( 'Divi', 'Extra' ), true );
					},
				),
				'child_theme'  => array(
					'label' => esc_html__( 'Child Theme of Divi/Extra', 'squad-modules-for-divi' ),
					'check' => function () {
						$current_theme = wp_get_theme();

						return $current_theme->parent() instanceof WP_Theme &&
							   in_array( $current_theme->parent()->get( 'Name' ), Divi::modules_allowed_theme(), true );
					},
				),
				'constants'    => array(
					'label' => esc_html__( 'Divi Constants', 'squad-modules-for-divi' ),
					'check' => function () {
						$divi_constants = array( 'ET_CORE_VERSION', 'ET_BUILDER_VERSION', 'ET_BUILDER_THEME' );
						foreach ( $divi_constants as $constant ) {
							if ( defined( $constant ) ) {
								return true;
							}
						}

						return false;
					},
				),
				'functions'    => array(
					'label' => esc_html__( 'Divi Functions', 'squad-modules-for-divi' ),
					'check' => function () {
						$key_functions = array( 'et_setup_theme', 'et_divi_fonts_url', 'et_pb_is_pagebuilder_used' );
						foreach ( $key_functions as $function ) {
							if ( is_callable( $function ) ) {
								return true;
							}
						}

						return false;
					},
				),
				'directory'    => array(
					'label' => esc_html__( 'Directory Structure', 'squad-modules-for-divi' ),
					'check' => function () {
						$current_theme = wp_get_theme();
						if ( $current_theme instanceof WP_Theme ) {
							$theme_dir = $current_theme->get_stylesheet_directory();

							return file_exists( $theme_dir . '/includes/builder' ) &&
								   ( file_exists( $theme_dir . '/epanel' ) || file_exists( $theme_dir . '/core' ) );
						}

						return false;
					},
				),
			);

			/**
			 * Filter the detection strategies used to determine the primary detection method.
			 *
			 * @since 3.3.3
			 *
			 * @param array<string, array> $detection_strategies The detection strategies.
			 */
			$detection_strategies = apply_filters( 'divi_squad_detection_strategies', $detection_strategies );

			// Find first successful strategy.
			foreach ( $detection_strategies as $strategy ) {
				if ( $strategy['check']() ) {
					return $strategy['label'];
				}
			}

			return esc_html__( 'No detection method succeeded', 'squad-modules-for-divi' );
		} catch ( Throwable $e ) {
			// Log the error.
			divi_squad()->log_error(
				$e,
				'Failed to determine detection method for Site Health',
				false,
				array( 'function' => __METHOD__ )
			);

			return esc_html__( 'Error determining detection method', 'squad-modules-for-divi' );
		}
	}
}
