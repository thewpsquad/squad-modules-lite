<?php // phpcs:ignore WordPress.Files.FileName


/**
 * Divi helper class for common functions.
 *
 * This utility class provides helper methods to check various Divi states,
 * handle icon processing, manage asset loading, and verify Divi theme/plugin status.
 * It centralizes detection of Divi environment to avoid duplicate code across the plugin.
 *
 * @since   1.0.0
 * @since   3.4.0 Added get_defined_divi_constants method
 * @package DiviSquad
 */

namespace DiviSquad\Utils;

use Throwable;
use WP_Theme;
use function add_filter;
use function get_option;
use function wp_get_theme;
use function wp_get_themes;

/**
 * Divi utility class.
 *
 * Provides helper methods for Divi theme and builder detection,
 * icon handling, asset management, and compatibility checks.
 *
 * @since   1.0.0
 * @package DiviSquad
 */
class Divi {
	/**
	 * Check if the Divi theme builder is enabled.
	 *
	 * @since  1.0.0
	 * @return boolean True if Divi Builder BFB mode is enabled, false otherwise.
	 */
	public static function is_bfb_enabled(): bool {
		return function_exists( '\et_builder_bfb_enabled' ) && \et_builder_bfb_enabled();
	}

	/**
	 * Check if Theme Builder is Used on the page.
	 *
	 * @since  1.0.0
	 * @return boolean True if Theme Builder is used on the current page, false otherwise.
	 */
	public static function is_theme_builder_used(): bool {
		return function_exists( '\et_fb_is_theme_builder_used_on_page' ) && \et_fb_is_theme_builder_used_on_page();
	}

	/**
	 * Check if the current screen is the Theme Builder administration screen.
	 *
	 * @since  1.0.0
	 * @return boolean True if current screen is Theme Builder admin screen, false otherwise.
	 */
	public static function is_tb_admin_screen(): bool {
		return function_exists( '\et_builder_is_tb_admin_screen' ) && \et_builder_is_tb_admin_screen();
	}

	/**
	 * Check if Divi Builder 5 is enabled.
	 *
	 * @since  1.0.0
	 * @return boolean True if D5 is enabled and FB is active, false otherwise.
	 */
	public static function is_d5_enabled(): bool {
		return function_exists( '\et_builder_d5_enabled' ) && \et_builder_d5_enabled() && static::is_fb_enabled();
	}

	/**
	 * Check if Divi visual builder is enabled.
	 *
	 * @since  1.0.0
	 * @return boolean True if Visual Builder (FB) is enabled, false otherwise.
	 */
	public static function is_fb_enabled(): bool {
		return function_exists( '\et_core_is_fb_enabled' ) && \et_core_is_fb_enabled();
	}

	/**
	 * Collect icon type from Divi formatted value.
	 *
	 * @since  1.0.0
	 *
	 * @param string $icon_value Divi formatted value for Icon.
	 *
	 * @return string Returns 'fa' for Font Awesome icons or 'divi' for Divi icons.
	 */
	public static function get_icon_type( string $icon_value ): string {
		return '' !== $icon_value && strpos( $icon_value, '||fa||' ) === 0 ? 'fa' : 'divi';
	}

	/**
	 * Determine icon font weight
	 *
	 * @since  1.0.0
	 *
	 * @param string $icon_value Divi formatted value for Icon.
	 *
	 * @return string The font weight value, defaults to '400'.
	 */
	public static function get_icon_font_weight( string $icon_value ): string {
		if ( '' !== $icon_value ) {
			$icon_data = explode( '|', $icon_value );

			return array_pop( $icon_data );
		}

		return '400';
	}

	/**
	 * Get unicode icon data
	 *
	 * Converts Divi icon format to CSS-compatible unicode format.
	 *
	 * @since  1.0.0
	 *
	 * @param string $icon_value Icon font value.
	 *
	 * @return string Icon data in CSS unicode format or empty string.
	 */
	public static function get_icon_data_to_unicode( string $icon_value ): string {
		if ( '' !== $icon_value ) {
			$icon_all_data = explode( '||', $icon_value );
			$icon_data     = array_shift( $icon_all_data );

			return str_replace( array( ';', '&#x' ), array( '', '\\' ), $icon_data );
		}

		return '';
	}

	/**
	 * Add Icons CSS into the divi asset list when the Dynamic CSS option is turn on in current installation
	 *
	 * @since  1.0.0
	 *
	 * @param array<string, array<string, string>> $global_list The existed global asset list.
	 *
	 * @return array<string, array<string, string>> Modified global asset list with icon CSS added.
	 *
	 * @filter et_global_assets_list Adds Divi icon assets to the global assets list.
	 * @filter et_late_global_assets_list Adds Divi icon assets to the late-loaded global assets list.
	 */
	public static function global_assets_list( array $global_list = array() ): array {
		if ( ! function_exists( 'et_get_dynamic_assets_path' ) ) {
			return $global_list;
		}

		$assets_prefix = et_get_dynamic_assets_path();

		$assets_list = array(
			'et_icons_all' => array(
				'css' => "$assets_prefix/css/icons_all.css",
			),
		);

		return array_merge( $global_list, $assets_list );
	}

	/**
	 * Add Font Awesome CSS into the divi asset list when the Dynamic CSS option is turn on in current installation
	 *
	 * @since  1.0.0
	 *
	 * @param array<string, array<string, string>> $global_list The existed global asset list.
	 *
	 * @return array<string, array<string, string>> Modified global asset list with Font Awesome CSS added.
	 *
	 * @filter et_global_assets_list Adds Font Awesome assets to the global assets list.
	 * @filter et_late_global_assets_list Adds Font Awesome assets to the late-loaded global assets list.
	 */
	public static function global_fa_assets_list( array $global_list = array() ): array {
		if ( ! function_exists( 'et_get_dynamic_assets_path' ) ) {
			return $global_list;
		}

		$assets_prefix = et_get_dynamic_assets_path();

		$assets_list = array(
			'et_icons_fa' => array(
				'css' => "$assets_prefix/css/icons_fa_all.css",
			),
		);

		return array_merge( $global_list, $assets_list );
	}

	/**
	 * Add Font Awesome CSS support manually when the Dynamic CSS option is turn on in current installation.
	 *
	 * Ensures proper font icon support by conditionally adding required CSS files.
	 *
	 * @since  1.0.0
	 *
	 * @param string $icon_data The icon value.
	 *
	 * @return void
	 *
	 * @uses   add_filter() Hooks into asset list filters to add necessary icon assets.
	 */
	public static function inject_fa_icons( string $icon_data ): void {
		if ( function_exists( 'et_use_dynamic_icons' ) && 'on' === et_use_dynamic_icons() ) {
			add_filter( 'et_global_assets_list', array( static::class, 'global_assets_list' ) );
			add_filter( 'et_late_global_assets_list', array( static::class, 'global_assets_list' ) );

			if ( function_exists( 'et_pb_maybe_fa_font_icon' ) && et_pb_maybe_fa_font_icon( $icon_data ) ) {
				add_filter( 'et_global_assets_list', array( static::class, 'global_fa_assets_list' ) );
				add_filter( 'et_late_global_assets_list', array( static::class, 'global_fa_assets_list' ) );
			}
		}
	}

	/**
	 * Returns boolean if any Divi theme is installed in the current WordPress installation.
	 *
	 * This method detects Divi/Extra themes using multiple strategies:
	 * 1. Direct theme name matching
	 * 2. Parent theme detection
	 * 3. Directory structure analysis
	 * 4. Code signature detection
	 *
	 * @since  1.0.0
	 * @since  3.3.3 Added support for customized Divi theme detection
	 * @access public
	 *
	 * @return boolean True if either Divi or Extra theme is installed, false otherwise.
	 */
	public static function is_any_divi_theme_installed(): bool {
		$wp_installed_themes = wp_get_themes();
		$base_themes         = static::modules_allowed_theme();

		/**
		 * Filter the detection strategies used for installed Divi themes.
		 *
		 * @since 3.3.3
		 *
		 * @param array $strategies Array of strategy names to use.
		 */
		$use_strategies = apply_filters(
			'divi_squad_theme_detection_strategies',
			array(
				'name_match',
				'parent_theme',
				'directory_structure',
				'code_signatures',
			)
		);

		foreach ( $wp_installed_themes as $theme ) {
			// Strategy 1: Check direct name match.
			if ( in_array( 'name_match', $use_strategies, true ) && in_array( $theme->get( 'Name' ), $base_themes, true ) ) {
				/**
				 * Filter whether a theme detected by name should be considered a Divi theme.
				 *
				 * @since 3.3.3
				 *
				 * @param bool     $is_divi_theme Whether this is a Divi theme.
				 * @param WP_Theme $theme         The theme being checked.
				 */
				$is_divi_theme = apply_filters( 'divi_squad_is_divi_theme_by_name', true, $theme );
				if ( $is_divi_theme ) {
					return true;
				}
			}

			// Strategy 2: Check for Divi/Extra as a parent theme.
			if ( in_array( 'parent_theme', $use_strategies, true ) ) {
				$parent_theme = $theme->parent();
				if ( $parent_theme instanceof WP_Theme && in_array( $parent_theme->get( 'Name' ), $base_themes, true ) ) {
					/**
					 * Filter whether a child theme of Divi should be considered a Divi theme.
					 *
					 * @since 3.3.3
					 *
					 * @param bool     $is_divi_child Whether this is a Divi child theme.
					 * @param WP_Theme $theme         The theme being checked.
					 * @param WP_Theme $parent_theme  The parent theme.
					 */
					$is_divi_child = apply_filters( 'divi_squad_is_divi_child_theme', true, $theme, $parent_theme );
					if ( $is_divi_child ) {
						return true;
					}
				}
			}

			// Strategy 3: Check for customized Divi/Extra themes by examining the template file structure.
			if ( in_array( 'directory_structure', $use_strategies, true ) ) {
				$theme_dir         = $theme->get_stylesheet_directory();
				$directory_markers = array(
					'includes/builder',
					'epanel',
					'core/functions.php',
					'includes/builder/feature/dynamic-assets',
				);

				/**
				 * Filter the directory markers used to identify Divi themes.
				 *
				 * @since 3.3.3
				 *
				 * @param array    $directory_markers Array of directory/file paths relative to the theme root.
				 * @param WP_Theme $theme             The theme being checked.
				 */
				$directory_markers = apply_filters( 'divi_squad_divi_directory_markers', $directory_markers, $theme );

				$found_markers = static::get_divi_directory_markers( $theme_dir, $directory_markers );

				/**
				 * Filter whether a theme detected by the directory structure should be considered a Divi theme.
				 *
				 * @since 3.3.3
				 *
				 * @param bool     $is_divi_structure Whether this has a Divi directory structure.
				 * @param WP_Theme $theme             The theme being checked.
				 * @param array    $found_markers     Directory markers found.
				 * @param int      $marker_count      Number of directory markers found.
				 */
				if ( apply_filters( 'divi_squad_is_divi_by_structure', ( count( $found_markers ) >= 2 ), $theme, $found_markers, count( $found_markers ) ) ) {
					return true;
				}
			}

			// Strategy 4: Check for code signatures in functions.php.
			if ( in_array( 'code_signatures', $use_strategies, true ) && file_exists( trailingslashit( $theme->get_stylesheet_directory() ) . 'functions.php' ) ) {

				$functions_content = file_get_contents( trailingslashit( $theme->get_stylesheet_directory() ) . 'functions.php' );
				$code_signatures   = array(
					'et_setup_theme',
					'et_divi_',
					'et_extra_',
					'et_builder_',
					'ET_BUILDER_VERSION',
					'ET_CORE_VERSION',
				);

				/**
				 * Filter the code signatures used to identify Divi themes.
				 *
				 * @since 3.3.3
				 *
				 * @param array    $code_signatures Array of code signature strings to look for.
				 * @param WP_Theme $theme           The theme being checked.
				 */
				$code_signatures = apply_filters( 'divi_squad_divi_code_signatures', $code_signatures, $theme );

				foreach ( $code_signatures as $signature ) {
					if ( false !== $functions_content && strpos( $functions_content, $signature ) !== false ) {
						/**
						 * Filter whether a theme containing a specific code signature should be considered a Divi theme.
						 *
						 * @since 3.3.3
						 *
						 * @param bool     $is_divi_code Whether this is a Divi theme based on code.
						 * @param WP_Theme $theme        The theme being checked.
						 * @param string   $signature    The matched code signature.
						 */
						if ( apply_filters( 'divi_squad_is_divi_by_code', true, $theme, $signature ) ) {
							return true;
						}
					}
				}
			}
		}

		/**
		 * Filter the final result of the Divi theme installation check.
		 *
		 * @since 3.3.3
		 *
		 * @param bool $is_installed Whether any Divi theme is installed.
		 */
		return apply_filters( 'divi_squad_is_divi_theme_installed', false );
	}

	/**
	 * Returns boolean if any Divi theme is active in the current WordPress installation.
	 *
	 * This method detects active Divi/Extra themes using multiple strategies:
	 * 1. The current theme name is matching
	 * 2. Parent theme detection
	 * 3. Divi constants detection
	 * 4. Directory structure analysis
	 * 5. Function existence checking
	 *
	 * @since  1.0.0
	 * @since  3.3.3 Added support for customized and child Divi theme detection
	 * @access public
	 *
	 * @return boolean True if either Divi or Extra theme is active, false otherwise.
	 */
	public static function is_any_divi_theme_active(): bool {
		$current_theme = wp_get_theme();
		if ( ! $current_theme instanceof WP_Theme ) {
			return false;
		}

		$base_themes = static::modules_allowed_theme();

		/**
		 * Filter the detection strategies used for active Divi themes.
		 *
		 * @since 3.3.3
		 *
		 * @param array $strategies Array of strategy names to use.
		 */
		$use_strategies = apply_filters(
			'divi_squad_active_theme_detection_strategies',
			array(
				'theme_name',
				'parent_theme',
				'constants',
				'functions',
				'directory_structure',
			)
		);

		// Strategy 1: Check if the current theme is directly Divi or Extra.
		if ( in_array( 'theme_name', $use_strategies, true ) && in_array( $current_theme->get( 'Name' ), $base_themes, true ) ) {
			/**
			 * Filter whether a theme detected by name should be considered an active Divi theme.
			 *
			 * @since 3.3.3
			 *
			 * @param bool     $is_active_divi Whether this is an active Divi theme.
			 * @param WP_Theme $current_theme  The active theme.
			 */
			$is_active_divi = apply_filters( 'divi_squad_is_active_divi_by_name', true, $current_theme );
			if ( $is_active_divi ) {
				return true;
			}
		}

		// Strategy 2: Check if the theme's template (parent) is Divi or Extra.
		if ( in_array( 'parent_theme', $use_strategies, true ) && in_array( $current_theme->get_template(), $base_themes, true ) ) {
			/**
			 * Filter whether a child theme of Divi should be considered an active Divi theme.
			 *
			 * @since 3.3.3
			 *
			 * @param bool     $is_active_divi_child Whether this is an active Divi child theme.
			 * @param WP_Theme $current_theme        The active theme.
			 */
			$is_active_divi_child = apply_filters( 'divi_squad_is_active_divi_child', true, $current_theme );
			if ( $is_active_divi_child ) {
				return true;
			}
		}

		// Strategy 3: Check for Divi/Extra framework presence through constants.
		if ( in_array( 'constants', $use_strategies, true ) ) {
			return static::has_divi_constants();
		}

		// Strategy 4: Check for Divi/Extra framework presence through functions.
		if ( in_array( 'functions', $use_strategies, true ) ) {
			$divi_functions = array(
				'et_setup_theme',
				'et_divi_fonts_url',
				'et_pb_is_pagebuilder_used',
				'et_builder_get_fonts',
			);

			/**
			 * Filter the functions used to identify active Divi themes.
			 *
			 * @since 3.3.3
			 *
			 * @param array $divi_functions Array of Divi function names to check.
			 */
			$divi_functions = apply_filters( 'divi_squad_divi_functions', $divi_functions );

			$available_functions = static::get_available_divi_functions( $divi_functions );

			/**
			 * Filter whether a theme with defined Divi functions should be considered an active Divi theme.
			 *
			 * @since 3.3.3
			 * @since 3.4.0 Updated to pass all available functions
			 *
			 * @param bool  $is_active_divi_function Whether this is an active Divi theme based on functions.
			 * @param array $available_functions     The functions that were found.
			 */
			if ( apply_filters( 'divi_squad_is_active_divi_by_function', count( $available_functions ) > 0, $available_functions ) ) {
				return true;
			}
		}

		// Strategy 5: Check theme template structure for Divi/Extra signatures.
		if ( in_array( 'directory_structure', $use_strategies, true ) ) {
			$template_dir      = $current_theme->get_template_directory();
			$directory_markers = array(
				'includes/builder',
				'epanel',
				'core',
				'includes/builder/feature',
				'includes/builder/frontend-builder',
			);

			/**
			 * Filter the directory markers used to identify active Divi themes.
			 *
			 * @since 3.3.3
			 *
			 * @param array  $directory_markers Array of directory paths relative to the theme root.
			 * @param string $template_dir      The template directory path.
			 */
			$directory_markers = apply_filters( 'divi_squad_active_divi_directory_markers', $directory_markers, $template_dir );

			$found_markers = static::get_divi_directory_markers( $template_dir, $directory_markers );

			/**
			 * Filter whether a theme detected by the directory structure should be considered an active Divi theme.
			 *
			 * @since 3.3.3
			 *
			 * @param bool   $is_active_divi_structure Whether this has an active Divi directory structure.
			 * @param string $template_dir             The template directory path.
			 * @param array  $found_markers            Directory markers found.
			 * @param int    $marker_count             Number of directory markers found.
			 */
			if ( apply_filters( 'divi_squad_is_active_divi_by_structure', ( count( $found_markers ) >= 2 ), $template_dir, $found_markers, count( $found_markers ) ) ) {
				return true;
			}
		}

		/**
		 * Filter the final result of the active Divi theme check.
		 *
		 * @since 3.3.3
		 *
		 * @param bool     $is_active_divi Whether a Divi theme is active.
		 * @param WP_Theme $current_theme  The active theme.
		 */
		return apply_filters( 'divi_squad_is_divi_theme_active', false, $current_theme );
	}

	/**
	 * Returns boolean if the Divi Builder Plugin is installed in the current WordPress installation.
	 *
	 * Checks for the existence of the Divi Builder plugin file using both direct file checking
	 * and WordPress filesystem API when available.
	 *
	 * @since  1.0.0
	 * @since  3.3.3 Added multiple detection methods and hooks
	 * @access public
	 *
	 * @return boolean True if the Divi Builder plugin is installed, false otherwise.
	 */
	public static function is_divi_builder_plugin_installed(): bool {
		$plugin_main_file = 'divi-builder/divi-builder.php';

		/**
		 * Filter the plugin main file path to check.
		 *
		 * @since 3.3.3
		 *
		 * @param string $plugin_main_file The plugin main file path relative to the plugin's directory.
		 */
		$plugin_main_file = apply_filters( 'divi_squad_divi_builder_plugin_file', $plugin_main_file );

		// Method 1: Use filesystem API if available.
		$is_installed = divi_squad()->get_wp_fs()->exists( WP_CONTENT_DIR . '/plugins/' . $plugin_main_file );

		// Method 3: Check alternative locations.
		if ( ! $is_installed ) {
			// Check the must-use plugins directory.
			$mu_plugin_path = WPMU_PLUGIN_DIR . '/' . basename( $plugin_main_file );
			if ( file_exists( $mu_plugin_path ) ) {
				$is_installed = true;
			}
		}

		/**
		 * Filter whether the Divi Builder plugin is installed.
		 *
		 * @since 3.3.3
		 *
		 * @param bool   $is_installed     Whether the plugin is installed.
		 * @param string $plugin_main_file The plugin main file path.
		 */
		return apply_filters( 'divi_squad_is_divi_builder_installed', $is_installed, $plugin_main_file );
	}

	/**
	 * Returns boolean if the Divi Builder Plugin is active in the current WordPress installation.
	 *
	 * Uses multiple detection strategies including
	 * 1. WordPress plugin API
	 * 2. Divi Builder constants
	 * 3. Plugin-specific function existence
	 *
	 * @since  1.0.0
	 * @since  3.3.3 Added multiple detection methods and hooks
	 * @access public
	 *
	 * @return boolean True if the Divi Builder plugin is active, false otherwise.
	 */
	public static function is_divi_builder_plugin_active(): bool {
		$plugin_main_file = 'divi-builder/divi-builder.php';

		/**
		 * Filter the plugin main file path to check.
		 *
		 * @since 3.3.3
		 *
		 * @param string $plugin_main_file The plugin main file path relative to the plugins' directory.
		 */
		$plugin_main_file = apply_filters( 'divi_squad_divi_builder_plugin_active_file', $plugin_main_file );

		/**
		 * Filter the detection strategies used for the active Divi Builder plugin.
		 *
		 * @since 3.3.3
		 *
		 * @param array $strategies Array of strategy names to use.
		 */
		$use_strategies = apply_filters(
			'divi_squad_divi_plugin_detection_strategies',
			array(
				'plugin_api',
				'constants',
				'functions',
			)
		);

		// Strategy 1: Use WordPress plugin API if available.
		if ( in_array( 'plugin_api', $use_strategies, true ) ) {
			if ( ! function_exists( 'is_plugin_active' ) && defined( 'ABSPATH' ) && file_exists( divi_squad()->get_wp_path() . 'wp-admin/includes/plugin.php' ) ) {
				include_once divi_squad()->get_wp_path( 'wp-admin/includes/plugin.php' );
			}

			if ( function_exists( 'is_plugin_active' ) && is_plugin_active( $plugin_main_file ) ) {
				/**
				 * Filter whether the Divi Builder plugin is active based on plugin API.
				 *
				 * @since 3.3.3
				 *
				 * @param bool   $is_active_plugin_api Whether the plugin is active according to plugin API.
				 * @param string $plugin_main_file     The plugin main file path.
				 */
				$is_active_plugin_api = apply_filters( 'divi_squad_is_divi_plugin_active_by_api', true, $plugin_main_file );
				if ( $is_active_plugin_api ) {
					return true;
				}
			}
		}

		// Strategy 2: Check for Divi Builder plugin constants.
		if ( in_array( 'constants', $use_strategies, true ) ) {
			$plugin_constants = array(
				'ET_BUILDER_PLUGIN_VERSION',
				'ET_BUILDER_PLUGIN_DIR',
				'ET_BUILDER_PLUGIN_URI',
			);

			/**
			 * Filter the constants used to identify the active Divi Builder plugin.
			 *
			 * @since 3.3.3
			 *
			 * @param array $plugin_constants Array of plugin constant names to check.
			 */
			$plugin_constants = apply_filters( 'divi_squad_divi_plugin_constants', $plugin_constants );

			foreach ( $plugin_constants as $constant ) {
				if ( defined( $constant ) ) {
					/**
					 * Filter whether plugin with defined constant should be considered an active Divi Builder plugin.
					 *
					 * @since 3.3.3
					 *
					 * @param bool   $is_active_constant Whether this is an active plugin based on constants.
					 * @param string $constant           The constant that was found.
					 */
					$is_active_constant = apply_filters( 'divi_squad_is_divi_plugin_active_by_constant', true, $constant );
					if ( $is_active_constant ) {
						return true;
					}
				}
			}
		}

		// Strategy 3: Check for Divi Builder plugin-specific functions.
		if ( in_array( 'functions', $use_strategies, true ) ) {
			// Unique functions that exist only in the Divi Builder plugin.
			$plugin_functions = array(
				'et_divi_builder_init_plugin',
				'et_builder_set_plugin_activated_flag',
			);

			/**
			 * Filter the functions used to identify the active Divi Builder plugin.
			 *
			 * @since 3.3.3
			 *
			 * @param array $plugin_functions Array of plugin function names to check.
			 */
			$plugin_functions = apply_filters( 'divi_squad_divi_plugin_functions', $plugin_functions );

			$available_functions = static::get_available_divi_functions( $plugin_functions );

			/**
			 * Filter whether plugin with defined function should be considered an active Divi Builder plugin.
			 *
			 * @since 3.3.3
			 * @since 3.4.0 Updated to pass all available functions
			 *
			 * @param bool  $is_active_function  Whether this is an active plugin based on functions.
			 * @param array $available_functions The functions that were found.
			 */
			if ( apply_filters( 'divi_squad_is_divi_plugin_active_by_function', count( $available_functions ) > 0, $available_functions ) ) {
				return true;
			}
		}

		/**
		 * Filter the final result of the active Divi Builder plugin check.
		 *
		 * @since 3.3.3
		 *
		 * @param bool $is_active_plugin Whether the Divi Builder plugin is active.
		 */
		return apply_filters( 'divi_squad_is_divi_builder_plugin_active', false );
	}

	/**
	 * Returns boolean if any Divi theme is installed active in the current WordPress installation.
	 *
	 * Unified check to determine if either a Divi/Extra theme is active or
	 * the Divi Builder plugin is active.
	 *
	 * @since  1.0.0
	 * @since  3.3.3 Enhanced with filters and improved detection
	 * @access public
	 *
	 * @return boolean True if an allowed theme or the builder plugin is active, false otherwise.
	 */
	public static function is_allowed_theme_activated(): bool {
		$is_theme_active  = static::is_any_divi_theme_active();
		$is_plugin_active = static::is_divi_builder_plugin_active();

		/**
		 * Filter the combined result of Divi theme or plugin activation check.
		 *
		 * @since 3.3.3
		 *
		 * @param bool $is_activated     Whether a Divi theme or plugin is active.
		 * @param bool $is_theme_active  Whether a Divi theme is active.
		 * @param bool $is_plugin_active Whether the Divi Builder plugin is active.
		 */
		return apply_filters(
			'divi_squad_is_allowed_theme_activated',
			( $is_theme_active || $is_plugin_active ),
			$is_theme_active,
			$is_plugin_active
		);
	}

	/**
	 * Check if the current theme is a direct match to one of the allowed Divi themes.
	 *
	 * Determines whether the active theme directly matches one of the
	 * allowed Divi themes (Divi or Extra by default) by name comparison.
	 *
	 * @since  3.4.1
	 * @access public
	 *
	 * @return boolean True if the current theme name directly matches an allowed theme, false otherwise.
	 */
	public static function is_direct_theme_match(): bool {
		try {
			$current_theme = wp_get_theme();

			$is_direct_match = in_array( $current_theme->get( 'Name' ), static::modules_allowed_theme(), true );

			/**
			 * Filter whether the current theme is a direct match to Divi or Extra.
			 *
			 * @since 3.4.0
			 *
			 * @param bool     $is_direct_match Whether the current theme directly matches Divi/Extra.
			 * @param WP_Theme $current_theme   The current theme object.
			 */
			return apply_filters( 'divi_squad_is_direct_theme_match', $is_direct_match, $current_theme );
		} catch ( Throwable $e ) {
			divi_squad()->log_error(
				$e,
				'Error checking if current theme is a direct Divi theme match',
				false,
				array( 'function' => __METHOD__ )
			);

			return false; // Default to false for safety
		}
	}

	/**
	 * Check if the current theme is a child theme of Divi or Extra.
	 *
	 * Determines whether the active theme is a child theme of one of the
	 * allowed Divi themes (Divi or Extra by default).
	 *
	 * @since  3.3.3
	 * @access public
	 *
	 * @return boolean True if the current theme is a child theme of Divi or Extra, false otherwise.
	 */
	public static function is_divi_child_theme(): bool {
		try {
			$current_theme = wp_get_theme();

			$is_child_theme = $current_theme->parent() instanceof WP_Theme && in_array( $current_theme->parent()->get( 'Name' ), static::modules_allowed_theme(), true );

			/**
			 * Filter whether the current theme is a child theme of Divi or Extra.
			 *
			 * @since 3.3.3
			 *
			 * @param bool     $is_child_theme Whether the current theme is a child theme of Divi/Extra.
			 * @param WP_Theme $current_theme  The current theme object.
			 */
			return apply_filters( 'divi_squad_is_divi_child_theme', $is_child_theme, $current_theme );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Error checking if current theme is a Divi child theme' );

			return false; // Default to false for safety
		}
	}

	/**
	 * Check if any Divi constants are defined in the environment.
	 *
	 * Checks for the presence of key Divi-specific constants that indicate
	 * Divi framework is loaded and available in the environment.
	 *
	 * @since  3.3.3
	 * @access public
	 *
	 * @param array<string> $constants Optional. Specific constants to check. Defaults to common Divi constants.
	 *
	 * @return bool True if any of the specified Divi constants are defined, false otherwise.
	 */
	public static function has_divi_constants( array $constants = array() ): bool {
		try {
			$defined_constants = static::get_defined_divi_constants( $constants );

			return count( $defined_constants ) > 0;
		} catch ( Throwable $e ) {
			divi_squad()->log_error(
				$e,
				'Error checking for Divi constants',
				false,
				array(
					'function'            => __METHOD__,
					'constants_requested' => $constants,
				)
			);

			return false; // Default to false for safety
		}
	}

	/**
	 * Get defined Divi constants based on a list of constants to check.
	 *
	 * Checks for the existence of specified Divi-related constants and returns
	 * an array of those that are defined. Useful for diagnosing Divi environment
	 * and framework availability.
	 *
	 * @since  3.4.0
	 * @access public
	 *
	 * @param array<string> $constants_to_check List of constants to check. Optional.
	 *
	 * @return array<string> Array of defined constants from the provided list.
	 */
	public static function get_defined_divi_constants( array $constants_to_check = array() ): array {
		try {
			// If no constants are specified, use default list
			if ( 0 === count( $constants_to_check ) ) {
				$constants_to_check = array(
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
			}

			/**
			 * Filter the Divi constants that are checked.
			 *
			 * @since 3.4.0
			 *
			 * @param array<string> $constants_to_check List of Divi constants to check.
			 */
			$constants_to_check = (array) apply_filters( 'divi_squad_divi_constants_check', $constants_to_check );

			$defined_constants = array();

			foreach ( $constants_to_check as $constant ) {
				if ( defined( $constant ) ) {
					$defined_constants[] = $constant;
				}
			}

			return $defined_constants;
		} catch ( Throwable $e ) {
			divi_squad()->log_error(
				$e,
				'Error checking for defined Divi constants',
				false,
				array(
					'function'           => __METHOD__,
					'constants_to_check' => $constants_to_check,
				)
			);

			return array();
		}
	}

	/**
	 * Get available Divi functions based on a list of functions to check.
	 *
	 * Checks for the existence of specified Divi-related functions and returns
	 * an array of those that are available. Useful for diagnosing Divi environment
	 * and framework availability.
	 *
	 * @since  3.4.0
	 * @access public
	 *
	 * @param array<string> $functions_to_check List of functions to check. Optional.
	 *
	 * @return array<string> Array of available functions from the provided list.
	 */
	public static function get_available_divi_functions( array $functions_to_check = array() ): array {
		try {
			// If no functions are specified, use default list
			if ( 0 === count( $functions_to_check ) ) {
				$functions_to_check = array(
					'et_setup_theme',
					'et_divi_fonts_url',
					'et_pb_is_pagebuilder_used',
					'et_core_is_fb_enabled',
					'et_builder_get_fonts',
					'et_builder_bfb_enabled',
					'et_fb_is_theme_builder_used_on_page',
					'et_divi_builder_init_plugin',
					'et_builder_set_plugin_activated_flag',
				);
			}

			/**
			 * Filter the Divi functions that are checked.
			 *
			 * @since 3.4.0
			 *
			 * @param array<string> $functions_to_check List of Divi functions to check.
			 */
			$functions_to_check = (array) apply_filters( 'divi_squad_divi_functions_check', $functions_to_check );

			$available_functions = array();

			foreach ( $functions_to_check as $function ) {
				if ( function_exists( $function ) ) {
					$available_functions[] = $function;
				}
			}

			return $available_functions;
		} catch ( Throwable $e ) {
			divi_squad()->log_error(
				$e,
				'Error checking for available Divi functions',
				false,
				array(
					'function'           => __METHOD__,
					'functions_to_check' => $functions_to_check,
				)
			);

			return array();
		}
	}

	/**
	 * Get matching Divi directory markers in a theme directory.
	 *
	 * Checks a theme directory for the presence of specific Divi-related
	 * directory markers to help identify Divi/Extra themes or modifications.
	 *
	 * @since  3.4.0
	 * @access public
	 *
	 * @param string        $theme_dir         Theme directory path to check.
	 * @param array<string> $directory_markers Optional. Specific directory markers to check. Defaults to common Divi directory markers.
	 *
	 * @return array<string> Array of directory markers found in the theme directory.
	 */
	public static function get_divi_directory_markers( string $theme_dir, array $directory_markers = array() ): array {
		try {
			// If no directory markers are specified, use default list
			if ( count( $directory_markers ) === 0 ) {
				$directory_markers = array(
					'includes/builder',
					'epanel',
					'core',
					'includes/builder/feature',
					'includes/builder/frontend-builder',
				);
			}

			/**
			 * Filter the directory markers used to identify Divi themes.
			 *
			 * @since 3.4.0
			 *
			 * @param array<string> $directory_markers The directory markers to check.
			 * @param string        $theme_dir         The theme directory path.
			 */
			$directory_markers = (array) apply_filters( 'divi_squad_divi_directory_markers_check', $directory_markers, $theme_dir );

			$found_markers = array();

			// Ensure we have a valid directory to check
			if ( '' === $theme_dir || ! is_dir( $theme_dir ) ) {
				return $found_markers;
			}

			// Normalize path with trailing slash
			$theme_dir = trailingslashit( $theme_dir );

			foreach ( $directory_markers as $marker ) {
				$path = $theme_dir . $marker;

				// Use WordPress filesystem API if available, otherwise fallback to file_exists
				if ( divi_squad()->get_wp_fs()->exists( $path ) ) {
					$found_markers[] = $marker;
				}
			}

			/**
			 * Filter the found Divi directory markers.
			 *
			 * @since 3.4.0
			 *
			 * @param array<string> $found_markers     The directory markers found.
			 * @param array<string> $directory_markers The directory markers checked.
			 * @param string        $theme_dir         The theme directory path.
			 */
			return apply_filters(
				'divi_squad_divi_found_directory_markers',
				$found_markers,
				$directory_markers,
				$theme_dir
			);
		} catch ( \Throwable $e ) {
			if ( function_exists( 'divi_squad' ) ) {
				divi_squad()->log_error(
					$e,
					'Error checking for Divi directory markers',
					false,
					array(
						'function'          => __METHOD__,
						'theme_dir'         => $theme_dir,
						'directory_markers' => $directory_markers,
					)
				);
			}

			return array();
		}
	}

	/**
	 * Return the allowed theme list for Divi Utils support.
	 *
	 * Provides the base theme names that are recognized as official Divi themes.
	 * This list can be extended via filters to support additional themes.
	 *
	 * @since  1.0.0
	 * @since  3.3.3 Added filter for extending allowed themes
	 * @access public
	 *
	 * @return array<string> Array of allowed theme names (Divi and Extra).
	 */
	public static function modules_allowed_theme(): array {
		/**
		 * Filter the allowed themes list.
		 *
		 * @since 3.3.3
		 *
		 * @param array<string> $allowed_themes Array of allowed theme names.
		 */
		return apply_filters( 'divi_squad_allowed_themes', array( 'Divi', 'Extra' ) );
	}

	/**
	 * Returns boolean if the Dynamic CSS feature is turned on for Divi Builder.
	 *
	 * Checks various theme and plugin options to determine if dynamic CSS is enabled.
	 * Looks for theme-specific and plugin-specific settings to ensure complete coverage.
	 *
	 * @since  1.0.0
	 * @since  3.3.3 Added filter hook and improved documentation
	 * @access public
	 *
	 * @return boolean True if Dynamic CSS is enabled, false otherwise.
	 */
	public static function is_dynamic_css_enable(): bool {
		$divi_builder_dynamic_css = 'on';
		$current_theme            = wp_get_theme();

		if ( ! $current_theme instanceof WP_Theme ) {
			return false;
		}

		// Check Divi theme setting.
		if ( 'Divi' === $current_theme->get( 'Name' ) ) {
			$config_options = get_option( 'et_divi', array() );

			if ( isset( $config_options['divi_dynamic_css'] ) && 'false' === $config_options['divi_dynamic_css'] ) {
				$divi_builder_dynamic_css = 'off';
			}
		}

		// Check Extra theme setting.
		if ( 'Extra' === $current_theme->get( 'Name' ) ) {
			$config_options = get_option( 'et_extra', array() );

			if ( isset( $config_options['extra_dynamic_css'] ) && 'false' === $config_options['extra_dynamic_css'] ) {
				$divi_builder_dynamic_css = 'off';
			}
		}

		// Check Divi Builder plugin setting.
		if ( static::is_divi_builder_plugin_active() ) {
			$config_options = get_option( 'et_pb_builder_options', array() );

			if ( isset( $config_options['performance_main_dynamic_css'] ) && 'false' === $config_options['performance_main_dynamic_css'] ) {
				$divi_builder_dynamic_css = 'off';
			}
		}

		// Check for child themes where parent is Divi/Extra.
		if ( $current_theme->parent() instanceof WP_Theme && in_array( $current_theme->parent()->get( 'Name' ), array( 'Divi', 'Extra' ), true ) ) {
			// Check parent theme settings.
			$parent_name    = $current_theme->parent()->get( 'Name' );
			$option_key     = ( 'Divi' === $parent_name ) ? 'et_divi' : 'et_extra';
			$css_option_key = ( 'Divi' === $parent_name ) ? 'divi_dynamic_css' : 'extra_dynamic_css';

			$parent_options = get_option( $option_key, array() );

			if ( isset( $parent_options[ $css_option_key ] ) && 'false' === $parent_options[ $css_option_key ] ) {
				$divi_builder_dynamic_css = 'off';
			}
		}

		/**
		 * Filter whether dynamic CSS is enabled for Divi.
		 *
		 * @since 3.3.3
		 *
		 * @param bool     $is_dynamic_css_enabled Whether dynamic CSS is enabled.
		 * @param string   $setting_value          The raw setting value ('on' or 'off').
		 * @param WP_Theme $current_theme          The current theme object.
		 */
		return apply_filters(
			'divi_squad_is_dynamic_css_enabled',
			'on' === $divi_builder_dynamic_css,
			$divi_builder_dynamic_css,
			$current_theme
		);
	}

	/**
	 * Get the current Divi Builder version.
	 *
	 * Checks multiple sources to identify the active Divi version:
	 * - ET_CORE_VERSION constant (theme)
	 * - ET_BUILDER_VERSION constant (builder framework)
	 * - ET_BUILDER_PLUGIN_VERSION constant (plugin)
	 * - et_get_theme_version function (fallback)
	 *
	 * @since  1.0.0
	 * @since  3.3.3 Added multiple version detection methods and filter
	 * @access public
	 *
	 * @return string The current Divi Builder version or empty string if unavailable.
	 */
	public static function get_builder_version(): string {
		$version = '0.0.0';

		// Check constants in order of priority.
		if ( defined( 'ET_CORE_VERSION' ) ) {
			$version = ET_CORE_VERSION;
		} elseif ( defined( 'ET_BUILDER_VERSION' ) ) {
			$version = ET_BUILDER_VERSION;
		} elseif ( defined( 'ET_BUILDER_PLUGIN_VERSION' ) ) {
			$version = ET_BUILDER_PLUGIN_VERSION;
		} elseif ( function_exists( '\et_get_theme_version' ) ) {
			$version = \et_get_theme_version();
		}

		/**
		 * Filter the detected Divi builder version.
		 *
		 * @since 3.3.3
		 *
		 * @param string $version The detected Divi version.
		 */
		return apply_filters( 'divi_squad_builder_version', $version );
	}

	/**
	 * Get the current Divi Builder mode (plugin or theme).
	 *
	 * Determines whether Divi Builder is being used through a plugin
	 * or as part of a theme.
	 *
	 * @since  1.0.0
	 * @since  3.3.3 Added filter hook
	 * @access public
	 *
	 * @return string Returns 'plugin' if Divi Builder plugin is active, otherwise 'theme'.
	 */
	public static function get_builder_mode(): string {
		$mode = static::is_divi_builder_plugin_active() ? 'plugin' : 'theme';

		/**
		 * Filter the detected Divi builder mode.
		 *
		 * @since 3.3.3
		 *
		 * @param string $mode The detected mode ('plugin' or 'theme').
		 */
		return apply_filters( 'divi_squad_builder_mode', $mode );
	}

	/**
	 * Determines whether styles should be loaded based on current context.
	 *
	 * Checks if the current page is:
	 * - Using the Divi Frontend Builder
	 * - Using Theme Builder
	 * - A singular post with the builder enabled
	 *
	 * The result can be filtered by plugins and themes to handle custom scenarios.
	 *
	 * @since  3.3.0
	 * @since  3.3.3 Added more context to filter
	 * @access public
	 *
	 * @return boolean True if styles should be loaded, false otherwise.
	 */
	public static function should_load_style(): bool {
		$context = array(
			'is_fb_enabled'         => static::is_fb_enabled(),
			'is_theme_builder_used' => static::is_theme_builder_used(),
			'is_singular'           => is_singular(),
			'post_id'               => is_singular() ? get_the_ID() : false,
		);

		// Load if in Frontend Builder or Theme Builder
		if ( $context['is_fb_enabled'] || $context['is_theme_builder_used'] ) {
			$should_load = true;
		} elseif ( function_exists( 'et_builder_enabled_for_post' ) && $context['is_singular'] && false !== $context['post_id'] ) {
			$should_load = et_builder_enabled_for_post( $context['post_id'] );
		} else {
			$should_load = false;
		}

		/**
		 * Filters whether Divi Squad styles should be loaded.
		 *
		 * @since 3.3.0
		 * @since 3.3.3 Added $context parameter
		 *
		 * @param boolean $should_load Whether styles should be loaded.
		 * @param array   $context     Contextual information about the current page.
		 */
		return apply_filters( 'divi_squad_should_load_style', $should_load, $context );
	}
}
