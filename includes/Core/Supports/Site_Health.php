<?php // phpcs:ignore WordPress.Files.FileName

/**
 * DiviSquad Site Health Integration
 *
 * This file contains the Site_Health class which adds Squad Modules and Divi
 * information to the WordPress Site Health report, including comprehensive
 * diagnostics for troubleshooting installation issues.
 *
 * @since      3.1.0
 * @since      3.4.0 Updated to use centralized Divi constant detection
 * @since      3.4.4 Updated to use the unified Divi detection system
 * @package    DiviSquad
 * @author     The WP Squad <support@squadmodules.com>
 * @license    GPL-3.0-only
 * @link       https://squadmodules.com
 */

namespace DiviSquad\Core\Supports;

use DiviSquad\Core\Extensions;
use DiviSquad\Core\Requirements\Requirements;
use DiviSquad\Core\Supports\Utils\Date_Time;
use DiviSquad\Utils\Divi;
use Throwable;
use WP_Theme;

/**
 * Site Health Info Manager.
 *
 * Adds Squad Modules and Divi information to the WordPress Site Health report.
 * Includes detailed Divi detection information to support troubleshooting.
 * This class provides comprehensive diagnostic information for support teams and site admins.
 *
 * @since   3.1.0
 * @since   3.3.3 Added enhanced Divi theme detection information
 * @since   3.4.4 Updated to use centralized Divi environment information
 * @package DiviSquad\Core\Supports
 */
class Site_Health {

	/**
	 * Site_Health constructor.
	 *
	 * Initializes the Site Health integration by registering filters and actions
	 * to inject DiviSquad information into the WordPress Site Health report.
	 *
	 * @since 3.1.0
	 */
	public function __construct() {
		add_filter( 'debug_information', array( $this, 'add_info_section' ) );

		/**
		 * Action triggered after Site_Health is initialized.
		 *
		 * Allows for additional setup or integration steps after the Site_Health
		 * component has been initialized.
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
	 * Adds DiviSquad and Divi diagnostics information to the WordPress
	 * Site Health info tab. Includes general plugin information and
	 * detailed Divi detection results.
	 *
	 * @since  3.1.0
	 *
	 * @param array<string, mixed> $debug_info Array with debug information.
	 *
	 * @return array<string, mixed> Modified debug information array with DiviSquad info.
	 *
	 * @throws Throwable When an error occurs during section creation (handled internally).
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
			 * Allows modification of the Divi Squad section in the Site Health info tab.
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
			 * Allows modification of the Divi requirements section in the Site Health info tab.
			 *
			 * @since 3.3.3
			 *
			 * @param array $divi_squad_requirements_section The DiviSquad requirements section.
			 */
			$debug_info['divi-squad-requirements'] = apply_filters( 'divi_squad_requirements_section', $divi_squad_requirements_section );

			/**
			 * Action triggered after Site Health sections are added.
			 *
			 * Allows for additional processing after DiviSquad sections have been added to Site Health.
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
	 * Generates an array of fields with plugin information for the
	 * DiviSquad section in the Site Health report.
	 *
	 * @since  3.1.0
	 * @since  3.4.4 Updated to use unified Divi environment info
	 * @access private
	 *
	 * @return array<string, array<string, string>> Array of field information for Site Health.
	 *
	 * @throws Throwable When an error occurs during field generation (handled internally).
	 */
	private function get_info_fields(): array {
		try {
			$activated_time = divi_squad()->memory->get( 'activation_time' );
			$installed_date = Date_Time::datetime_format( $activated_time, '', true );

			// Get centralized Divi environment info
			$divi_info = Divi::get_divi_environment_info();

			$fields = array(
				'version-core'          => array(
					'label' => esc_html__( 'Core Version', 'squad-modules-for-divi' ),
					'value' => divi_squad()->get_version_dot(),
				),
				'install-date-core'     => array(
					'label' => esc_html__( 'Core installed date', 'squad-modules-for-divi' ),
					'value' => $installed_date,
				),
				'divi-version'          => array(
					'label' => esc_html__( 'Divi Version', 'squad-modules-for-divi' ),
					'value' => '0.0.0' !== $divi_info['version'] ? $divi_info['version'] : esc_html__( 'Not detected', 'squad-modules-for-divi' ),
				),
				'divi-detection-method' => array(
					'label' => esc_html__( 'Divi Detection Method', 'squad-modules-for-divi' ),
					'value' => $divi_info['version_detection_method'],
				),
				'divi-mode'             => array(
					'label' => esc_html__( 'Divi Mode', 'squad-modules-for-divi' ),
					'value' => ucfirst( $divi_info['builder_mode'] ),
				),
				'requirements-met'      => array(
					'label' => esc_html__( 'Requirements Status', 'squad-modules-for-divi' ),
					'value' => $this->get_requirements_status(),
				),
			);

			/**
			 * Filter the core info fields before adding extension-specific fields.
			 *
			 * Allows modification of the core plugin information fields before extension fields are added.
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
			 * Allows modification of all fields in the DiviSquad section of Site Health.
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
	 * Collects information about active DiviSquad extensions for display
	 * in the Site Health report.
	 *
	 * @since  3.3.3
	 * @access private
	 *
	 * @return array<string, array<string, string>> Array of extension fields for Site Health.
	 *
	 * @throws Throwable When an error occurs during extension field generation (handled internally).
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
			 * Allows modification of the extension-specific information in the Site Health report.
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
	 * Collects comprehensive information about the Divi environment
	 * for troubleshooting purposes. Now uses the centralized Divi
	 * environment information from the unified Divi utility.
	 *
	 * @since  3.3.3
	 * @since  3.4.4 Updated to use centralized Divi environment info
	 * @access private
	 *
	 * @return array<string, array<string, string>> Array of Divi detection fields.
	 *
	 * @throws Throwable When an error occurs during Divi detection field generation (handled internally).
	 */
	private function get_divi_detection_fields(): array {
		try {
			$current_theme = wp_get_theme();

			// Get comprehensive Divi environment info from the unified utility
			$divi_info = Divi::get_divi_environment_info();

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

			// Use unified Divi environment info for detection results
			$fields['divi-version'] = array(
				'label' => esc_html__( 'Detected Divi Version', 'squad-modules-for-divi' ),
				'value' => '0.0.0' !== $divi_info['version'] ? $divi_info['version'] : esc_html__( 'Not detected', 'squad-modules-for-divi' ),
			);

			$fields['version-detection-method'] = array(
				'label' => esc_html__( 'Version Detection Method', 'squad-modules-for-divi' ),
				'value' => $divi_info['version_detection_method'],
			);

			$fields['is-divi-theme-installed'] = array(
				'label' => esc_html__( 'Divi Theme Installed', 'squad-modules-for-divi' ),
				'value' => (bool) $divi_info['theme_active']
					? esc_html__( 'Yes', 'squad-modules-for-divi' )
					: esc_html__( 'No', 'squad-modules-for-divi' ),
			);

			$fields['is-divi-plugin-active'] = array(
				'label' => esc_html__( 'Divi Builder Plugin Active', 'squad-modules-for-divi' ),
				'value' => (bool) $divi_info['plugin_active']
					? esc_html__( 'Yes', 'squad-modules-for-divi' )
					: esc_html__( 'No', 'squad-modules-for-divi' ),
			);

			$fields['framework-source'] = array(
				'label' => esc_html__( 'Divi Framework Source', 'squad-modules-for-divi' ),
				'value' => $divi_info['framework_source'],
			);

			// Add theme type detection info
			$fields['theme-type'] = array(
				'label' => esc_html__( 'Theme Type', 'squad-modules-for-divi' ),
				'value' => $this->get_theme_type_description( $divi_info ),
			);

			// Allowed themes check.
			$fields['allowed-themes'] = array(
				'label' => esc_html__( 'Allowed Themes', 'squad-modules-for-divi' ),
				'value' => implode( ', ', Divi::modules_allowed_theme() ),
			);

			// Is allowed theme activated.
			$fields['is-allowed-theme-active'] = array(
				'label' => esc_html__( 'Is Allowed Theme Active', 'squad-modules-for-divi' ),
				'value' => $divi_info['theme_active'] || $divi_info['plugin_active']
					? esc_html__( 'Yes', 'squad-modules-for-divi' )
					: esc_html__( 'No', 'squad-modules-for-divi' ),
			);

			// Add requirements check
			$fields['meets-requirements'] = array(
				'label' => esc_html__( 'Meets Version Requirements', 'squad-modules-for-divi' ),
				'value' => $divi_info['meets_requirements']
					? esc_html__( 'Yes', 'squad-modules-for-divi' )
					: esc_html__( 'No', 'squad-modules-for-divi' ),
			);

			/**
			 * Filter the basic Divi detection fields before adding technical details.
			 *
			 * Allows modification of the basic Divi information before technical details are added.
			 *
			 * @since 3.3.3
			 *
			 * @param array<string, array<string, string>> $fields        The basic Divi detection fields.
			 * @param WP_Theme                             $current_theme The current theme.
			 */
			$fields = apply_filters( 'divi_squad_basic_divi_detection_fields', $fields, $current_theme );

			// Add technical details if allowed.
			if ( $this->should_include_technical_details() ) {
				$fields = array_merge( $fields, $this->get_technical_divi_details( $divi_info ) );
			}

			/**
			 * Filter the Divi detection Site Health fields.
			 *
			 * Allows modification of all Divi detection fields in the Site Health report.
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
	 * Get a description of the Divi theme type based on environment info.
	 *
	 * @since  3.4.4
	 * @access private
	 *
	 * @param array<string, mixed> $divi_info Divi environment information.
	 * @return string Description of the theme type.
	 */
	private function get_theme_type_description( array $divi_info ): string {
		if ( $divi_info['is_direct_match'] ) {
			return esc_html__( 'Official Divi/Extra Theme', 'squad-modules-for-divi' );
		}

		if ( $divi_info['is_child_theme'] ) {
			return sprintf(
				// translators: %s is the parent theme name.
				esc_html__( 'Child Theme of %s', 'squad-modules-for-divi' ),
				$divi_info['parent_theme_name']
			);
		}

		if ( (bool) $divi_info['is_modified'] ) {
			return esc_html__( 'Modified/Customized Divi Theme', 'squad-modules-for-divi' );
		}

		if ( (bool) $divi_info['plugin_active'] ) {
			return esc_html__( 'Non-Divi Theme with Divi Builder Plugin', 'squad-modules-for-divi' );
		}

		return esc_html__( 'Unknown Theme Type', 'squad-modules-for-divi' );
	}

	/**
	 * Get technical Divi detection details.
	 *
	 * Collects advanced technical information about the Divi environment
	 * for in-depth troubleshooting purposes.
	 *
	 * @since  3.3.3
	 * @since  3.4.4 Updated to use centralized Divi environment info
	 * @access private
	 *
	 * @param array<string, mixed> $divi_info The Divi environment information.
	 *
	 * @return array<string, array<string, string>> Technical Divi detection details.
	 *
	 * @throws Throwable When an error occurs during technical details collection (handled internally).
	 */
	private function get_technical_divi_details( array $divi_info ): array {
		$technical_fields = array();

		try {
			// Divi constants from unified info
			if ( isset( $divi_info['defined_constants'] ) && is_array( $divi_info['defined_constants'] ) ) {
				$constants_values = array();

				foreach ( $divi_info['defined_constants'] as $constant ) {
					if ( defined( $constant ) ) {
						$constants_values[] = $constant . ': ' . constant( $constant );
					}
				}

				$technical_fields['divi-constants'] = array(
					'label' => esc_html__( 'Divi Constants', 'squad-modules-for-divi' ),
					'value' => count( $constants_values ) > 0
						? implode( ', ', $constants_values )
						: esc_html__( 'None detected', 'squad-modules-for-divi' ),
				);
			}

			// Divi functions from unified info
			if ( isset( $divi_info['available_functions'] ) && is_array( $divi_info['available_functions'] ) ) {
				$technical_fields['divi-functions'] = array(
					'label' => esc_html__( 'Divi Functions', 'squad-modules-for-divi' ),
					'value' => count( $divi_info['available_functions'] ) > 0
						? implode( ', ', $divi_info['available_functions'] )
						: esc_html__( 'None detected', 'squad-modules-for-divi' ),
				);
			}

			// Dynamic CSS enabled.
			$technical_fields['dynamic-css-enabled'] = array(
				'label' => esc_html__( 'Dynamic CSS Enabled', 'squad-modules-for-divi' ),
				'value' => Divi::is_dynamic_css_enable() ? esc_html__( 'Yes', 'squad-modules-for-divi' ) : esc_html__( 'No', 'squad-modules-for-divi' ),
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

			// Add full Divi environment info for advanced debugging
			$technical_fields['full-divi-environment'] = array(
				'label' => esc_html__( 'Full Divi Environment Info', 'squad-modules-for-divi' ),
				'value' => '<pre>' . esc_html( (string) wp_json_encode( $divi_info, JSON_PRETTY_PRINT ) ) . '</pre>',
			);

			/**
			 * Filter the technical Divi detection fields.
			 *
			 * Allows modification of the technical Divi details in the Site Health report.
			 *
			 * @since 3.3.3
			 * @since 3.4.4 Added Divi info parameter
			 *
			 * @param array<string, array<string, string>> $technical_fields The technical Divi detection fields.
			 * @param array<string, mixed>                 $divi_info        The Divi environment information.
			 */
			return apply_filters( 'divi_squad_technical_divi_detection_fields', $technical_fields, $divi_info );
		} catch ( Throwable $e ) {
			// Log the error with detailed context information.
			divi_squad()->log_error(
				$e,
				'Failed to get technical Divi details for Site Health',
				false,
				array(
					'function' => __METHOD__,
				)
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
	 * Controls whether advanced diagnostic information should be included
	 * in the report, which can be filtered by developers.
	 *
	 * @since  3.3.3
	 * @access private
	 *
	 * @return bool Whether to include technical details.
	 */
	private function should_include_technical_details(): bool {
		try {
			/**
			 * Filter whether to include technical details in the Site Health report.
			 *
			 * Allows developers to control whether technical details are included.
			 *
			 * @since 3.3.3
			 *
			 * @param bool $include_technical_details Whether to include technical details.
			 */
			return apply_filters( 'divi_squad_include_technical_details', true );
		} catch ( Throwable $e ) {
			// Log the error.
			divi_squad()->log_error(
				$e,
				'Error checking if technical details should be included',
				false,
				array( 'function' => __METHOD__ )
			);

			// Default to showing technical details if there's an error.
			return true;
		}
	}

	/**
	 * Get requirements status for display.
	 *
	 * Checks if all system requirements are met and formats the status
	 * for display in the Site Health report.
	 *
	 * @since  3.3.3
	 * @access private
	 *
	 * @return string Formatted requirements status message.
	 *
	 * @throws Throwable When an error occurs during status check (handled internally).
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
}
