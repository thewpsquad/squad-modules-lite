<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Requirements class.
 *
 * Handles system requirements validation for Squad Modules,
 * ensuring compatibility with Divi theme or Divi Builder plugin.
 * Centralizes requirements checking logic and provides detailed error reporting.
 *
 * @since   3.2.0
 * @since   3.4.0 Improved error handling and constant detection
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Core;

use DiviSquad\Core\Assets as Assets_Manager;
use DiviSquad\Core\Contracts\Hookable;
use DiviSquad\Core\Supports\Polyfills\Constant;
use DiviSquad\Utils\Divi;
use DiviSquad\Utils\Helper;
use DiviSquad\Utils\Helper as HelperUtil;
use RuntimeException;
use Throwable;

/**
 * Class Requirements
 *
 * Manages Divi requirements validation and displays appropriate
 * notifications when requirements are not met.
 *
 * @since   3.2.0
 * @package DiviSquad
 */
class Requirements implements Hookable {
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
	 * Initialize hooks and filters.
	 *
	 * @since 3.3.0
	 */
	public function __construct() {
		$this->register_hooks();
	}

	/**
	 * Register hooks and filters for the requirements template.
	 *
	 * @since  3.4.0
	 * @access protected
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		// Use the new check_requirements method on init instead of is_fulfilled directly.
		add_action( 'init', array( $this, 'check_requirements' ) );

		// Add hooks for theme/plugin activation events.
		add_action( 'activated_plugin', array( $this, 'check_requirements_on_plugin_activation' ) );
		add_action( 'after_switch_theme', array( $this, 'check_requirements_on_theme_activation' ) );

		add_action( 'divi_squad_after_register_admin_assets', array( $this, 'register_assets' ) );
		add_action( 'divi_squad_after_enqueue_admin_assets', array( $this, 'enqueue_assets' ) );

		// Register action hooks for the requirements template.
		add_action( 'divi_squad_after_minimum_requirements', array( $this, 'add_extended_requirements_info' ) );
		add_action( 'divi_squad_debug_info_items', array( $this, 'add_debug_info_items' ) );
		add_action( 'divi_squad_after_standard_sections', array( $this, 'add_custom_sections' ), 10, 3 );

		// Register filters for requirements template.
		add_filter( 'divi_squad_required_divi_version', array( $this, 'filter_divi_required_version' ) );
		add_filter( 'divi_squad_plugin_life_type', array( $this, 'filter_plugin_life_type' ) );
		add_filter( 'divi_squad_render_status_badge', array( $this, 'filter_render_status_badge' ), 10, 2 );
		add_filter( 'divi_squad_minimum_requirements', array( $this, 'filter_minimum_requirements' ), 10, 2 );
		add_filter( 'divi_squad_requirement_rows_config', array( $this, 'filter_requirement_rows_config' ), 10, 3 );
	}

	/**
	 * Check all Divi requirements and take appropriate actions.
	 *
	 * This method checks if requirements are met and logs failures if needed.
	 * It centralizes requirement checking to avoid duplication.
	 *
	 * @since  3.4.0
	 * @access public
	 *
	 * @return void
	 */
	public function check_requirements(): void {
		// Check if requirements are fulfilled.
		$is_fulfilled = $this->is_fulfilled();

		// If requirements are not fulfilled, log the failure.
		if ( ! $is_fulfilled ) {
			$this->log_requirement_failure();
		} else {
			// If requirements are now fulfilled, but we had a previous failure,
			// clean up the failure flags.
			$requirements_failed = (bool) get_option( 'divi_squad_requirements_failed', false );
			if ( $requirements_failed ) {
				delete_option( 'divi_squad_requirements_failed' );
				delete_option( 'divi_squad_requirements_context' );
				delete_option( 'divi_squad_requirements_data' );

				// Log the successful resolution.
				divi_squad()->log_info(
					'Requirements now fulfilled',
					'Requirements_Resolved',
					array( 'previous_failure' => true )
				);
			}
		}
	}

	/**
	 * Check requirements when a plugin is activated.
	 *
	 * @since  3.4.0
	 * @access public
	 *
	 * @param string $plugin Path to the plugin file relative to the plugins directory.
	 *
	 * @return void
	 */
	public function check_requirements_on_plugin_activation( string $plugin ): void {
		// Check if we have a previously failed requirement.
		$requirements_failed = (bool) get_option( 'divi_squad_requirements_failed', false );

		if ( ! $requirements_failed ) {
			return;
		}

		// Check if the activated plugin is Divi Builder.
		if ( false !== strpos( $plugin, 'divi-builder' ) ) {
			// Reset status cache to force a fresh check.
			$this->status = array();

			// Check if requirements are now met.
			if ( $this->is_fulfilled() ) {
				// Requirements are now met, clean up.
				delete_option( 'divi_squad_requirements_failed' );
				delete_option( 'divi_squad_requirements_context' );
				delete_option( 'divi_squad_requirements_data' );

				// Log the successful resolution.
				divi_squad()->log_info(
					'Requirements now fulfilled after plugin activation: ' . $plugin,
					'Requirements_Resolved',
					array( 'activated_plugin' => $plugin )
				);
			}
		}
	}

	/**
	 * Check requirements when a theme is activated.
	 *
	 * @since  3.4.0
	 * @access public
	 *
	 * @param string $theme_name Name of the theme.
	 *
	 * @return void
	 */
	public function check_requirements_on_theme_activation( string $theme_name ): void {
		// Check if we have a previously failed requirement.
		$requirements_failed = (bool) get_option( 'divi_squad_requirements_failed', false );

		if ( ! $requirements_failed ) {
			return;
		}

		// Check if the activated theme is Divi or Extra.
		if ( false !== stripos( $theme_name, 'divi' ) || false !== stripos( $theme_name, 'extra' ) ) {
			// Reset status cache to force a fresh check.
			$this->status = array();

			// Check if requirements are now met.
			if ( $this->is_fulfilled() ) {
				// Requirements are now met, clean up.
				delete_option( 'divi_squad_requirements_failed' );
				delete_option( 'divi_squad_requirements_context' );
				delete_option( 'divi_squad_requirements_data' );

				// Log the successful resolution.
				divi_squad()->log_info(
					'Requirements now fulfilled after theme activation: ' . $theme_name,
					'Requirements_Resolved',
					array( 'activated_theme' => $theme_name )
				);
			}
		}
	}

	/**
	 * Register requirements assets
	 *
	 * @param Assets_Manager $assets Assets Manager instance.
	 *
	 * @return void
	 */
	public function register_assets( Assets_Manager $assets ): void {
		try {
			$assets->register_style(
				'plugin-requirements',
				array(
					'file' => 'requirements',
					'path' => 'admin',
				)
			);

			/**
			 * Fires after requirements assets are registered
			 *
			 * @param Assets_Manager $assets Assets Manager instance
			 */
			do_action( 'divi_squad_after_register_requirements_assets', $assets );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to register admin notices assets' );
		}
	}

	/**
	 * Enqueue requirements assets
	 *
	 * @param Assets_Manager $assets Assets Manager instance.
	 *
	 * @return void
	 */
	public function enqueue_assets( Assets_Manager $assets ): void {
		try {
			if ( HelperUtil::is_squad_page() ) {
				$assets->enqueue_style( 'plugin-requirements' );
			}

			/**
			 * Fires after requirements assets are enqueued
			 *
			 * @param Assets_Manager $assets Assets Manager instance
			 */
			do_action( 'divi_squad_after_enqueue_requirements_assets', $assets );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to enqueue requirements assets' );
		}
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
	 * @return bool True if all requirements are met, false otherwise.
	 */
	public function is_fulfilled(): bool {
		try {
			$this->required_version = divi_squad()->get_option( 'RequiresDIVI', '4.14.0' );

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
			 * @param bool         $is_valid     True if all requirements are met.
			 * @param Requirements $requirements Current Requirements instance.
			 */
			$is_fulfilled = apply_filters( 'divi_squad_is_builder_meet', true, $this );

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

			if ( $is_theme_installed ) {
				$this->status['theme_version'] = Divi::get_builder_version();
			}

			if ( $is_plugin_installed ) {
				$this->status['plugin_version'] = Divi::get_builder_version();
			}
		}

		return $this->status['is_installed']; // @phpstan-ignore-line
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
		}

		return $this->status['is_active']; // @phpstan-ignore-line
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
			if ( ( $this->status['is_theme_active'] ?? false ) && '0.0.0' !== ( $this->status['theme_version'] ?? '0.0.0' ) ) { // @phpstan-ignore-line
				$meets_version = version_compare( (string) ( $this->status['theme_version'] ?? '0.0.0' ), $this->required_version, '>=' );
			}

			// Check Divi Builder plugin version (if active).
			if ( ! $meets_version && ( $this->status['is_plugin_active'] ?? false ) && '0.0.0' !== ( $this->status['plugin_version'] ?? '0.0.0' ) ) { // @phpstan-ignore-line
				$meets_version = version_compare( (string) ( $this->status['plugin_version'] ?? '0.0.0' ), $this->required_version, '>=' );
			}

			$this->status['meets_version'] = $meets_version;
		}

		return (bool) $this->status['meets_version'];
	}

	/**
	 * Filter for the required Divi version.
	 *
	 * @since  3.4.0
	 * @access public
	 *
	 * @param string $version The Divi version requirement.
	 *
	 * @return string The filtered version requirement.
	 */
	public function filter_divi_required_version( string $version ): string {
		return $this->required_version ?? $version;
	}

	/**
	 * Filter for the plugin life type.
	 *
	 * @since  3.4.0
	 * @access public
	 *
	 * @param string $life_type The plugin life type.
	 *
	 * @return string
	 */
	public function filter_plugin_life_type( string $life_type ): string {
		return divi_squad()->is_dev() ? 'nightly' : $life_type;
	}

	/**
	 * Filter for the plugin slug.
	 *
	 * @since  3.4.0
	 * @access public
	 *
	 * @param string $status_badge_text Existing status badge.
	 * @param bool   $is_met            Whether the requirement is met.
	 *
	 * @return string
	 */
	public function filter_render_status_badge( string $status_badge_text, bool $is_met ): string {
		if ( '' === $status_badge_text ) {
			$status_badge_text = $is_met ? esc_html__( 'OK', 'squad-modules-for-divi' ) : esc_html__( 'FAILED', 'squad-modules-for-divi' );
		}

		$badge_class = $is_met ? 'success' : 'error';
		$icon_class  = $is_met ? 'dashicons-yes-alt' : 'dashicons-warning';

		return sprintf(
			'<span class="status-badge %1$s"><span class="dashicons %2$s"></span>%3$s</span>',
			esc_attr( $badge_class ),
			esc_attr( $icon_class ),
			esc_html( $status_badge_text )
		);
	}

	/**
	 * Filter the requirement rows configuration.
	 *
	 * @since  3.4.0
	 * @access public
	 *
	 * @param array<array<string, mixed>> $rows             The default requirement rows configuration.
	 * @param array<string, mixed>        $status           The current status values.
	 * @param string                      $required_version The required Divi version.
	 *
	 * @return array<array<string, mixed>> Filtered requirement rows configuration.
	 */
	public function filter_requirement_rows_config( array $rows, array $status, string $required_version ): array {
		// Divi Theme Installed row.
		$rows[] = array(
			'id'        => 'theme_installed',
			'label'     => __( 'Divi Theme Installed', 'squad-modules-for-divi' ),
			'value'     => $status['is_theme_installed'] ?? false,
			'show'      => true,
			'condition' => true,
		);
		// Divi Builder Plugin Installed row.
		$rows[] = array(
			'id'        => 'plugin_installed',
			'label'     => __( 'Divi Builder Plugin Installed', 'squad-modules-for-divi' ),
			'value'     => $status['is_plugin_installed'] ?? false,
			'show'      => true,
			'condition' => true,
		);
		// Divi Theme Activated row.
		$rows[] = array(
			'id'        => 'theme_active',
			'label'     => __( 'Divi Theme Activated', 'squad-modules-for-divi' ),
			'value'     => $status['is_theme_active'] ?? false,
			'show'      => true,
			'condition' => $status['is_theme_installed'] ?? false,
		);
		// Divi Builder Plugin Activated row.
		$rows[] = array(
			'id'        => 'plugin_active',
			'label'     => __( 'Divi Builder Plugin Activated', 'squad-modules-for-divi' ),
			'value'     => $status['is_plugin_active'] ?? false,
			'show'      => true,
			'condition' => $status['is_plugin_installed'] ?? false,
		);
		// Divi Theme Version row.
		$rows[] = array(
			'id'           => 'theme_version',
			'label'        => __( 'Divi Theme Version', 'squad-modules-for-divi' ),
			'value'        => version_compare( $status['theme_version'] ?? '0.0.0', $required_version, '>=' ),
			'show'         => true,
			'condition'    => $status['is_theme_active'] ?? false,
			'subtitle'     => sprintf(
			/* translators: %s is the required version */
				__( 'Required: %s or higher', 'squad-modules-for-divi' ),
				$required_version
			),
			'version_info' => $status['theme_version'] ?? esc_html__( 'Unknown', 'squad-modules-for-divi' ),
		);
		// Divi Builder Plugin Version row.
		$rows[] = array(
			'id'           => 'plugin_version',
			'label'        => __( 'Divi Builder Plugin Version', 'squad-modules-for-divi' ),
			'value'        => version_compare( $status['plugin_version'] ?? '0.0.0', $required_version, '>=' ),
			'show'         => true,
			'condition'    => $status['is_plugin_active'] ?? false,
			'subtitle'     => sprintf(
			/* translators: %s is the required version */
				__( 'Required: %s or higher', 'squad-modules-for-divi' ),
				$required_version
			),
			'version_info' => $status['plugin_version'] ?? 'Unknown',
		);

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// Add an informational row about debug mode.
			$rows[] = array(
				'id'           => 'wp_debug',
				'label'        => __( 'WordPress Debug Mode', 'squad-modules-for-divi' ),
				'value'        => true,
				'show'         => true,
				'condition'    => true,
				'subtitle'     => __( 'Development environment detected', 'squad-modules-for-divi' ),
				'custom_badge' => sprintf(
					'<span class="status-badge info"><span class="dashicons dashicons-info"></span> %s</span>',
					esc_html__( 'Enabled', 'squad-modules-for-divi' )
				),
			);
		}

		return $rows;
	}

	/**
	 * Filter the minimum requirements list.
	 *
	 * @since  3.4.0
	 * @access public
	 *
	 * @param array<array<string, string>> $requirements     Array of requirement items.
	 * @param string                       $required_version The required Divi version.
	 *
	 * @return array<array<string, string>> Filtered array of requirement items.
	 */
	public function filter_minimum_requirements( array $requirements, string $required_version ): array {
		$requirements[] = array(
			'name'  => __( 'Divi Theme/Builder:', 'squad-modules-for-divi' ),
			// Translators: %s is the required version.
			'value' => sprintf( __( 'Version %s or higher', 'squad-modules-for-divi' ), $required_version ),
		);
		$requirements[] = array(
			'name'  => __( 'WordPress:', 'squad-modules-for-divi' ),
			// Translators: %s is the required version.
			'value' => sprintf( __( 'Version %s or higher', 'squad-modules-for-divi' ), apply_filters( 'divi_squad_required_wp_version', '6.0' ) ),
		);
		$requirements[] = array(
			'name'  => __( 'PHP:', 'squad-modules-for-divi' ),
			// Translators: %s is the required version.
			'value' => sprintf( __( 'Version %s or higher', 'squad-modules-for-divi' ), apply_filters( 'divi_squad_required_php_version', '7.4' ) ),
		);

		// Add server requirements if appropriate.
		$php_memory_limit = ini_get( 'memory_limit' );
		$required_memory  = '128M';

		// Only add memory requirement if it's a concern.
		if ( $this->convert_memory_to_bytes( $php_memory_limit ) < $this->convert_memory_to_bytes( $required_memory ) ) {
			$requirements[] = array(
				'name'  => __( 'PHP Memory Limit:', 'squad-modules-for-divi' ),
				'value' => sprintf(
				// Translators: %s is the recommended memory limit.
					__( 'Recommended: %1$s or higher (Current: %2$s)', 'squad-modules-for-divi' ),
					$required_memory,
					$php_memory_limit
				),
			);
		}

		// Check for max execution time if it's too low.
		$max_execution_time = ini_get( 'max_execution_time' );
		if ( '0' !== $max_execution_time && (int) $max_execution_time < 30 ) {
			$requirements[] = array(
				'name'  => __( 'PHP Max Execution Time:', 'squad-modules-for-divi' ),
				'value' => sprintf(
				// Translators: %s is the recommended max execution time.
					__( 'Recommended: 30 seconds or higher (Current: %s seconds)', 'squad-modules-for-divi' ),
					$max_execution_time
				),
			);
		}

		return $requirements;
	}

	/**
	 * Add extended requirements information after the minimum requirements list.
	 *
	 * Displays additional recommended hosting environment information when the
	 * 'divi_squad_show_hosting_recommendations' filter returns true.
	 *
	 * @since  3.4.0
	 * @access public
	 *
	 * @return void
	 */
	public function add_extended_requirements_info(): void {
		try {
			// For example, we could add a note about recommended hosting environments.
			$show_hosting_info = apply_filters( 'divi_squad_show_hosting_recommendations', false );

			if ( $show_hosting_info ) {
				echo '<div class="extended-requirements-info">';
				printf( '<h3>%s</h3>', esc_html__( 'Recommended Hosting Environment', 'squad-modules-for-divi' ) );
				printf( '<p>%s</p>', esc_html__( 'For optimal performance with Squad Modules and Divi, we recommend:', 'squad-modules-for-divi' ) );
				echo '<ul>';
				printf( '<li>%s</li>', esc_html__( 'PHP 8.0 or higher', 'squad-modules-for-divi' ) );
				printf( '<li>%s</li>', esc_html__( 'MySQL 5.7 or MariaDB 10.3 or higher', 'squad-modules-for-divi' ) );
				printf( '<li>%s</li>', esc_html__( 'PHP Memory limit of 256M or higher', 'squad-modules-for-divi' ) );
				printf( '<li>%s</li>', esc_html__( 'PHP max_execution_time of 60 seconds or higher', 'squad-modules-for-divi' ) );
				echo '</ul>';
				echo '</div>';
			}

			/**
			 * Fires after extended requirements information is displayed.
			 *
			 * @since 3.4.0
			 */
			do_action( 'divi_squad_after_extended_requirements_info' );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Extended_Requirements_Display_Error' );
		}
	}

	/**
	 * Add custom sections to the requirement page.
	 *
	 * Adds a troubleshooting section to help users resolve requirement issues
	 * when requirements are not met and the 'divi_squad_show_troubleshooting_section'
	 * filter returns true.
	 *
	 * @since  3.4.0
	 * @access public
	 *
	 * @param array<string, mixed> $status           The current status values.
	 * @param string               $required_version The required Divi version.
	 * @param bool                 $is_fulfilled     Whether all requirements are met.
	 *
	 * @return void
	 */
	public function add_custom_sections( array $status, string $required_version, bool $is_fulfilled ): void {
		try {
			// For example, add a troubleshooting section if requirements aren't met.
			if ( ! $is_fulfilled && apply_filters( 'divi_squad_show_troubleshooting_section', true ) ) {
				echo '<div class="requirements-section">';
				printf( '<h3>%s</h3>', esc_html__( 'Troubleshooting', 'squad-modules-for-divi' ) );
				printf( '<p>%s</p>', esc_html__( 'Having trouble meeting the requirements? Here are some common solutions:', 'squad-modules-for-divi' ) );

				echo '<div class="troubleshooting-tips">';
				printf( '<h3>%s</h3>', esc_html__( 'Common Issues', 'squad-modules-for-divi' ) );
				echo '<ul>';
				printf(
					'<li class="tip-item"><strong>%s</strong> %s</li>',
					esc_html__( 'Divi not detecting:', 'squad-modules-for-divi' ),
					esc_html__( 'Make sure you\'re using an official Elegant Themes version of Divi.', 'squad-modules-for-divi' )
				);
				printf(
					'<li class="tip-item"><strong>%s</strong> %s</li>',
					esc_html__( 'Version conflicts:', 'squad-modules-for-divi' ),
					esc_html__( 'Clear your browser cache and WordPress cache after updating Divi.', 'squad-modules-for-divi' )
				);
				printf(
					'<li class="tip-item"><strong>%s</strong> %s</li>',
					esc_html__( 'Activation issues:', 'squad-modules-for-divi' ),
					esc_html__( 'Temporarily deactivate other plugins to check for conflicts.', 'squad-modules-for-divi' )
				);
				echo '</ul>';
				echo '</div>';

				echo '</div>';
			}

			/**
			 * Fires after custom sections are added to the requirements page.
			 *
			 * @since 3.4.0
			 *
			 * @param array<string, mixed> $status           The current status values.
			 * @param string               $required_version The required Divi version.
			 * @param bool                 $is_fulfilled     Whether all requirements are met.
			 */
			do_action( 'divi_squad_after_custom_sections', $status, $required_version, $is_fulfilled );
		} catch ( Throwable $e ) {
			divi_squad()->log_error(
				$e,
				'Custom_Sections_Display_Error',
				true,
				array(
					'is_fulfilled'     => $is_fulfilled,
					'required_version' => $required_version,
				)
			);
		}
	}

	/**
	 * Add additional debug information items to the requirements page.
	 *
	 * Displays server information, MySQL version, PHP settings, and theme details
	 * to help with troubleshooting and support.
	 *
	 * @since  3.4.0
	 * @access public
	 *
	 * @return void
	 */
	public function add_debug_info_items(): void {
		try {
			// Server software info.
			printf(
				'<li><strong>%s</strong> %s</li>',
				esc_html__( 'Server Software:', 'squad-modules-for-divi' ),
				esc_html( sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ) ) )
			);

			// MySQL version.
			global $wpdb;
			$mysql_version = $wpdb->db_version();

			printf(
				'<li><strong>%s</strong> %s</li>',
				esc_html__( 'MySQL Version:', 'squad-modules-for-divi' ),
				esc_html( $mysql_version )
			);

			// Max execution time.
			printf(
				'<li><strong>%s</strong> %s</li>',
				esc_html__( 'PHP Max Execution Time:', 'squad-modules-for-divi' ),
				esc_html( ini_get( 'max_execution_time' ) . 's' )
			);

			// Active theme.
			$theme = wp_get_theme();
			printf(
				'<li><strong>%s</strong> %s</li>',
				esc_html__( 'Active Theme:', 'squad-modules-for-divi' ),
				esc_html( $theme->get( 'Name' ) . ' ' . $theme->get( 'Version' ) )
			);

			/**
			 * Fires after debug information items are added to the requirements page.
			 *
			 * Allows for adding additional debug information items.
			 *
			 * @since 3.4.0
			 */
			do_action( 'divi_squad_after_debug_info_items' );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Debug_Info_Display_Error' );
		}
	}

	/**
	 * Convert a PHP memory value to bytes.
	 *
	 * @since  3.4.0
	 * @access protected
	 *
	 * @param string $memory_value Memory value (e.g., '128M').
	 *
	 * @return int Memory value in bytes.
	 */
	protected function convert_memory_to_bytes( string $memory_value ): int {
		$memory_value = trim( $memory_value );
		$last         = strtolower( $memory_value[ strlen( $memory_value ) - 1 ] );
		$value        = (int) $memory_value;

		switch ( $last ) {
			case 'm':
			case 'g':
				$value *= 1024;
				break;
			case 'k':
				$value *= 1024;
		}

		return $value;
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
	 * Register the admin page.
	 *
	 * @since  3.2.0
	 * @access public
	 *
	 * @return void
	 */
	public function register_pre_loaded_admin_page(): void {
		add_action( 'admin_menu', array( $this, 'register_admin_page' ) );
		add_action( 'admin_head', array( $this, 'clean_admin_content_section' ), Constant::PHP_INT_MAX );
		add_action( 'divi_squad_menu_badges', array( $this, 'add_badges' ) );
	}

	/**
	 * Register the admin page.
	 *
	 * @since  3.2.0
	 * @access public
	 *
	 * @return void
	 */
	public function register_admin_page(): void {
		// Load the image class.
		$image = divi_squad()->load_image( '/build/admin/images/logos' );

		// Get the menu icon.
		$menu_icon = $image->get_image( 'divi-squad-d-menu.svg', 'svg' );
		if ( is_wp_error( $menu_icon ) ) {
			$menu_icon = 'dashicons-warning';
		}

		$page_slug  = divi_squad()->get_admin_menu_slug();
		$capability = 'manage_options';

		// Register the admin page.
		add_menu_page(
			__( 'Divi Squad', 'squad-modules-for-divi' ),
			__( 'Divi Squad', 'squad-modules-for-divi' ),
			$capability,
			$page_slug,
			'', // @phpstan-ignore-line
			$menu_icon,
			divi_squad()->get_admin_menu_position()
		);

		// Register the admin page.
		add_submenu_page(
			$page_slug,
			__( 'Requirements', 'squad-modules-for-divi' ),
			__( 'Requirements', 'squad-modules-for-divi' ),
			$capability,
			$page_slug,
			array( $this, 'render_admin_page' ),
		);
	}

	/**
	 * Remove all notices from the squad template pages.
	 *
	 * @since  3.2.0
	 * @access public
	 *
	 * @return void
	 */
	public function clean_admin_content_section(): void {
		// Check if the current screen is available.
		if ( Helper::is_squad_page() ) {
			remove_all_actions( 'admin_notices' );
			remove_all_actions( 'network_admin_notices' );
			remove_all_actions( 'all_admin_notices' );
			remove_all_actions( 'user_admin_notices' );
		}
	}

	/**
	 * Render the admin page.
	 *
	 * @since  3.2.0
	 * @access public
	 *
	 * @return void
	 */
	public function render_admin_page(): void {
		// Force requirements check.
		$this->status = array();
		$this->is_fulfilled();

		// Get notice content.
		$args = array(
			'content'          => $this->get_notice_content(),
			'status'           => $this->status,
			'required_version' => $this->required_version,
		);

		if ( divi_squad()->is_template_exists( 'admin/requirements.php' ) ) {
			load_template( divi_squad()->get_template_path( 'admin/requirements.php' ), false, $args );
		}
	}

	/**
	 * Add badges to the requirements page.
	 *
	 * @since  3.2.3
	 * @access public
	 *
	 * @param string $plugin_life_type The plugin life type.
	 *
	 * @return void
	 */
	public function add_badges( string $plugin_life_type ): void {
		// Add the nightly badge.
		if ( 'nightly' === $plugin_life_type ) {
			printf(
				'<li class="nightly-badge"><span class="badge-name">%s</span><span class="badge-version">%s</span></li>',
				esc_html__( 'Nightly', 'squad-modules-for-divi' ),
				esc_html__( 'current', 'squad-modules-for-divi' )
			);
		}

		// Add the stable lite badge.
		if ( 'stable' === $plugin_life_type ) {
			printf(
				'<li class="stable-lite-badge"><span class="badge-name">%s</span><span class="badge-version">%s</span></li>',
				esc_html__( 'Lite', 'squad-modules-for-divi' ),
				esc_html( divi_squad()->get_version_dot() )
			);
		}
	}

	/**
	 * Get notice content based on Divi status
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @return string
	 */
	protected function get_notice_content(): string {
		// Not installed.
		if ( ! $this->is_divi_installed() ) {
			return $this->render_notice_banner(
				'error',
				__( 'Divi Not Installed', 'squad-modules-for-divi' ),
				__( 'Squad Modules requires either the Divi/Extra theme or Divi Builder plugin to be installed and activated. Please install Divi to use Squad Modules.', 'squad-modules-for-divi' ),
				array(
					'url'  => 'https://www.elegantthemes.com/gallery/divi/',
					'text' => __( 'Get Divi', 'squad-modules-for-divi' ),
					'icon' => 'external',
				)
			);
		}

		// Theme is active but outdated.
		if ( ( $this->status['is_theme_active'] ?? false ) && version_compare( $this->status['theme_version'] ?? '0.0.0', $this->required_version, '<' ) ) {

			return $this->render_notice_banner(
				'warning',
				__( 'Divi Update Required', 'squad-modules-for-divi' ),
				sprintf(
				/* translators: %1$s: Required Divi version, %2$s: Current Divi version */
					__( 'Squad Modules requires Divi version %1$s or higher. Your current Divi version is %2$s. Please update to continue using Squad Modules.', 'squad-modules-for-divi' ),
					$this->required_version,
					$this->status['theme_version']
				),
				array(
					'url'  => admin_url( 'themes.php' ),
					'text' => __( 'Update Divi', 'squad-modules-for-divi' ),
					'icon' => 'update',
				)
			);
		}

		// Plugin is active but outdated.
		if ( ( $this->status['is_plugin_active'] ?? false ) && version_compare( $this->status['plugin_version'] ?? '0.0.0', $this->required_version, '<' ) ) {

			return $this->render_notice_banner(
				'warning',
				__( 'Divi Builder Update Required', 'squad-modules-for-divi' ),
				sprintf(
				/* translators: %1$s: Required Divi version, %2$s: Current Divi version */
					__( 'Squad Modules requires Divi Builder version %1$s or higher. Your current Divi Builder version is %2$s. Please update to continue using Squad Modules.', 'squad-modules-for-divi' ),
					$this->required_version,
					$this->status['plugin_version']
				),
				array(
					'url'  => admin_url( 'plugins.php' ),
					'text' => __( 'Update Divi Builder', 'squad-modules-for-divi' ),
					'icon' => 'update',
				)
			);
		}

		// Theme is installed but not active.
		if ( ( $this->status['is_theme_installed'] ?? false ) && ! ( $this->status['is_theme_active'] ?? false ) ) {
			return $this->render_notice_banner(
				'error',
				__( 'Divi Theme Not Activated', 'squad-modules-for-divi' ),
				__( 'Squad Modules has detected that you have Divi theme installed but not activated. Please activate the Divi theme to use Squad Modules.', 'squad-modules-for-divi' ),
				array(
					'url'  => admin_url( 'themes.php' ),
					'text' => __( 'Activate Divi Theme', 'squad-modules-for-divi' ),
					'icon' => 'admin-appearance',
				)
			);
		}

		// Plugin is installed but not active.
		if ( ( $this->status['is_plugin_installed'] ?? false ) && ! ( $this->status['is_plugin_active'] ?? false ) ) {
			return $this->render_notice_banner(
				'error',
				__( 'Divi Builder Plugin Not Activated', 'squad-modules-for-divi' ),
				__( 'Squad Modules has detected that you have the Divi Builder plugin installed but not activated. Please activate the Divi Builder plugin to use Squad Modules.', 'squad-modules-for-divi' ),
				array(
					'url'  => admin_url( 'plugins.php' ),
					'text' => __( 'Activate Divi Builder', 'squad-modules-for-divi' ),
					'icon' => 'admin-plugins',
				)
			);
		}

		// All requirements are met.
		return $this->render_notice_banner(
			'success',
			__( 'Divi Requirements Met', 'squad-modules-for-divi' ),
			__( 'Your Divi installation meets all the requirements for Squad Modules. You can start using the modules now!', 'squad-modules-for-divi' ),
			array(
				'url'  => admin_url( 'admin.php?page=' . divi_squad()->get_admin_menu_slug() ),
				'text' => __( 'Go to Dashboard', 'squad-modules-for-divi' ),
				'icon' => 'admin-home',
			)
		);
	}

	/**
	 * Renders a standardized notice banner.
	 *
	 * @since  3.3.0
	 * @access protected
	 *
	 * @param string                                         $type    Notice type: 'error', 'warning', 'success'.
	 * @param string                                         $title   Notice title.
	 * @param string                                         $message Notice message.
	 * @param array{url: string, text: string, icon: string} $action  Optional. Action button details.
	 *
	 * @return string The HTML for the notice banner.
	 */
	protected function render_notice_banner( string $type, string $title, string $message, array $action ): string {
		$output = sprintf(
			'<div class="notice divi-squad-banner divi-squad-%s-banner">
				<div class="divi-squad-banner-content">
					<h3>%s</h3>
					<p>%s</p>',
			esc_attr( $type ),
			esc_html( $title ),
			esc_html( $message )
		);

		// Add an action button if provided.
		if ( count( $action ) > 0 ) {
			$output .= sprintf(
				'<div class="divi-squad-notice-action">
					<div class="divi-squad-notice-action-left">
						<a href="%s" %s class="button-primary divi-squad-notice-action-button">
							<span class="dashicons dashicons-%s"></span>
							<p>%s</p>
						</a>
					</div>
				</div>',
				esc_url( $action['url'] ),
				strpos( $action['url'], 'http' ) === 0 ? 'target="_blank"' : '',
				esc_attr( $action['icon'] ),
				esc_html( $action['text'] )
			);
		}

		$output .= '</div></div>';

		return $output;
	}

	/**
	 * Log a requirement failure with detailed information.
	 *
	 * Records failed requirements with comprehensive context data for debugging
	 * and reports major compatibility issues to the error reporting system.
	 *
	 * @since  3.4.0
	 * @access public
	 *
	 * @param bool $report_error Whether to send an error report for this failure. Default true.
	 *
	 * @return void
	 */
	public function log_requirement_failure( bool $report_error = true ): void {
		try {
			// Skip logging for special WordPress request types
			if ( $this->is_special_request_type() ) {
				return;
			}

			// Get the current status.
			$status = $this->get_status();

			// Build comprehensive extra data for debugging.
			$extra_data = array(
				'status_details'    => $status,
				'error_message'     => $this->get_last_error(),
				'site_url'          => home_url(),
				'wordpress_version' => get_bloginfo( 'version' ),
				'php_version'       => PHP_VERSION,
				'server_software'   => sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ?? '' ) ),
				'required_version'  => $this->required_version,
				'is_multisite'      => is_multisite() ? 'Yes' : 'No',
			);

			// Include theme information if available.
			if ( function_exists( 'wp_get_theme' ) ) {
				$theme                      = wp_get_theme();
				$extra_data['active_theme'] = array(
					'name'    => $theme->get( 'Name' ),
					'version' => $theme->get( 'Version' ),
					'author'  => $theme->get( 'Author' ),
				);
			}

			// Determine the specific failure type with detailed descriptions.
			if ( ! (bool) ( $status['is_installed'] ?? false ) ) {
				$requirement = 'Divi Installation';
				$current     = 'Not installed';
				$expected    = 'Divi theme or Divi Builder plugin must be installed';
				$context     = 'Missing Divi';
			} elseif ( ! (bool) ( $status['is_active'] ?? false ) ) {
				// Provide specific context for which component is inactive.
				if ( (bool) ( $status['is_theme_installed'] ?? false ) ) {
					$requirement = 'Divi Theme Activation';
					$current     = 'Divi theme is installed but not activated';
					$expected    = 'Divi theme must be activated';
					$context     = 'Inactive Divi Theme';
				} else {
					$requirement = 'Divi Builder Plugin Activation';
					$current     = 'Divi Builder plugin is installed but not activated';
					$expected    = 'Divi Builder plugin must be activated';
					$context     = 'Inactive Divi Plugin';
				}
			} elseif ( ! (bool) ( $status['meets_version'] ?? true ) ) {
				if ( isset( $status['theme_version'] ) ) {
					$current_version = 'Theme version: ' . $status['theme_version'];
				} else {
					$current_version = ( isset( $status['plugin_version'] ) ? 'Plugin version: ' . $status['plugin_version'] : 'Unknown version' );
				}

				$requirement = 'Divi Version Compatibility';
				$current     = $current_version;
				$expected    = 'Version ' . $this->required_version . ' or higher';
				$context     = 'Outdated Divi Version';
			} else {
				$requirement = 'Unknown Requirement';
				$current     = 'Validation failed for unknown reason';
				$expected    = 'All requirements should be met';
				$context     = 'General Requirements Failure';
			}

			/**
			 * Filters whether to log the requirement failure.
			 *
			 * @since 3.2.0
			 * @since 3.4.0 Added $context parameter
			 *
			 * @param bool         $should_log   Whether to log the failure.
			 * @param string       $requirement  The failed requirement.
			 * @param string       $current      The current value.
			 * @param string       $expected     The expected value.
			 * @param array        $extra_data   Additional data.
			 * @param string       $context      Context identifier for the failure.
			 * @param Requirements $requirements Instance of this class.
			 */
			$should_log = apply_filters(
				'divi_squad_should_log_requirement_failure',
				true,
				$requirement,
				$current,
				$expected,
				$extra_data,
				$context,
				$this
			);

			if ( ! $should_log ) {
				return;
			}

			// Store requirement failure status in options.
			update_option( 'divi_squad_requirements_failed', true, false );
			update_option( 'divi_squad_requirements_context', $context, false );
			update_option( 'divi_squad_requirements_data', $extra_data, false );

			// Prepare a detailed message for logging.
			$log_message = sprintf(
				'Requirements check failed: %s. Current: %s. Expected: %s.',
				$requirement,
				$current,
				$expected
			);

			if ( $report_error ) {
				// Log using the error method for critical requirements failures.
				divi_squad()->log_error(
					new RuntimeException( $log_message, 500 ),
					$context
				);
			} else {
				// Log the failure with proper context.
				divi_squad()->log_warning( $log_message, $context );
			}

			/**
			 * Action triggered after logging a requirement failure.
			 *
			 * @since 3.2.0
			 * @since 3.4.0 Added $context parameter and $report_error parameter
			 *
			 * @param string       $requirement  The failed requirement.
			 * @param string       $current      The current value.
			 * @param string       $expected     The expected value.
			 * @param array        $extra_data   Additional data.
			 * @param string       $context      Context identifier for the failure.
			 * @param bool         $report_error Whether an error report was sent.
			 * @param Requirements $requirements Instance of this class.
			 */
			do_action(
				'divi_squad_after_log_requirement_failure',
				$requirement,
				$current,
				$expected,
				$extra_data,
				$context,
				$report_error,
				$this
			);
		} catch ( Throwable $e ) {
			// Ensure any errors in the logging process are caught and recorded.
			divi_squad()->log_error(
				$e,
				'Requirements_Logging_Error',
				false, // Don't report this meta-error to avoid loops.
				array(
					'original_error' => $this->get_last_error(),
					'status'         => $this->status,
				)
			);
		}
	}

	/**
	 * Determines if the current request is a special WordPress request type
	 * that should be excluded from requirements logging.
	 *
	 * Detects AJAX, REST API, cron jobs, and XML-RPC requests using multiple methods
	 * for maximum reliability.
	 *
	 * @since  3.4.0
	 * @access protected
	 *
	 * @return bool True if the current request is a special request type, false otherwise.
	 */
	protected function is_special_request_type(): bool {
		// Define flags for various request types
		$is_ajax_request = false;
		$is_rest_request = false;
		$is_cron_job     = false;
		$is_xml_request  = false;

		// Method 1: Standard WordPress function checks
		if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
			$is_ajax_request = true;
		}

		if ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) {
			$is_rest_request = true;
		}

		if ( function_exists( 'wp_is_xml_request' ) && wp_is_xml_request() ) {
			$is_xml_request = true;
		}

		if ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) {
			$is_cron_job = true;
		}

		if ( function_exists( 'wp_is_rest_request' ) && function_exists( 'rest_get_url_prefix' ) ) {
			$rest_prefix = rest_get_url_prefix();
			$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

			// Check if the request URI contains the REST API prefix
			if ( ( '' !== $request_uri ) && strpos( $request_uri, '/' . $rest_prefix . '/' ) !== false ) {
				$is_rest_request = true;
			}
		}

		// Method 2: Direct script filename check for admin-ajax.php
		if ( isset( $_SERVER['SCRIPT_FILENAME'] ) ) {
			$script_filename = sanitize_text_field( wp_unslash( $_SERVER['SCRIPT_FILENAME'] ) );
			if ( strpos( $script_filename, 'admin-ajax.php' ) !== false || basename( $script_filename ) === 'admin-ajax.php' ) {
				$is_ajax_request = true;
			}

			if ( strpos( $script_filename, 'wp-cron.php' ) !== false || basename( $script_filename ) === 'wp-cron.php' ) {
				$is_cron_job = true;
			}
		}

		// Method 3: Check for AJAX through request parameters and headers
		if ( isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) === 'xmlhttprequest' ) {
			$is_ajax_request = true;
		}

		// Make sure the request target is admin-ajax.php.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_REQUEST['action'] ) && '' !== $_REQUEST['action'] ) {
			// Most WordPress AJAX calls include an action parameter
			$is_ajax_request = true;
		}

		// Method 4: Check for specific AJAX constants
		if ( defined( 'DOING_AJAX' ) && \DOING_AJAX ) {
			$is_ajax_request = true;
		}

		if ( defined( 'XMLRPC_REQUEST' ) && \XMLRPC_REQUEST ) {
			$is_xml_request = true;
		}

		if ( defined( 'DOING_CRON' ) && \DOING_CRON ) {
			$is_cron_job = true;
		}

		// Return true if any of the special request types are detected
		return $is_rest_request || $is_ajax_request || $is_cron_job || $is_xml_request;
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
		// Force a check if not already done.
		if ( count( $this->status ) === 0 ) {
			$this->is_fulfilled();
		}

		return $this->status;
	}
}
