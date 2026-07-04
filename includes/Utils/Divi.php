<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Divi helper class for common functions.
 *
 * This utility class provides helper methods to check various Divi states,
 * handle icon processing, manage asset loading, and verify Divi theme/plugin status.
 *
 * @since   1.0.0
 * @author  The WP Squad <support@squadmodules.com>
 * @package DiviSquad
 */

namespace DiviSquad\Utils;

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
	 * Check if Divi theme builder is enabled.
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
		return function_exists( '\et_builder_d5_enabled' ) && \et_builder_d5_enabled() && self::is_fb_enabled();
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
	 * Add Icons css into the divi asset list when the Dynamic CSS option is turn on in current installation
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
				'css' => "{$assets_prefix}/css/icons_all.css",
			),
		);

		return array_merge( $global_list, $assets_list );
	}

	/**
	 * Add Font Awesome css into the divi asset list when the Dynamic CSS option is turn on in current installation
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
				'css' => "{$assets_prefix}/css/icons_fa_all.css",
			),
		);

		return array_merge( $global_list, $assets_list );
	}

	/**
	 * Add Font Awesome css support manually when the Dynamic CSS option is turn on in current installation.
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
			add_filter( 'et_global_assets_list', array( self::class, 'global_assets_list' ) );
			add_filter( 'et_late_global_assets_list', array( self::class, 'global_assets_list' ) );

			if ( function_exists( 'et_pb_maybe_fa_font_icon' ) && et_pb_maybe_fa_font_icon( $icon_data ) ) {
				add_filter( 'et_global_assets_list', array( self::class, 'global_fa_assets_list' ) );
				add_filter( 'et_late_global_assets_list', array( self::class, 'global_fa_assets_list' ) );
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
		$base_themes         = self::modules_allowed_theme();

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

			// Strategy 2: Check for Divi/Extra as parent theme.
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
			if ( in_array( 'directory_structure', $use_strategies, true ) &&
				 file_exists( trailingslashit( $theme->get_stylesheet_directory() ) . 'includes/builder' ) ) {

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
				 * @param array    $directory_markers Array of directory/file paths relative to theme root.
				 * @param WP_Theme $theme             The theme being checked.
				 */
				$directory_markers = apply_filters( 'divi_squad_divi_directory_markers', $directory_markers, $theme );

				$marker_count = 0;
				foreach ( $directory_markers as $marker ) {
					if ( file_exists( trailingslashit( $theme->get_stylesheet_directory() ) . $marker ) ) {
						++$marker_count;
					}
				}

				// If we find at least 2 markers, it's likely a Divi theme.
				if ( $marker_count >= 2 ) {
					/**
					 * Filter whether a theme detected by directory structure should be considered a Divi theme.
					 *
					 * @since 3.3.3
					 *
					 * @param bool     $is_divi_structure Whether this has Divi directory structure.
					 * @param WP_Theme $theme             The theme being checked.
					 * @param int      $marker_count      Number of directory markers found.
					 */
					$is_divi_structure = apply_filters( 'divi_squad_is_divi_by_structure', true, $theme, $marker_count );
					if ( $is_divi_structure ) {
						return true;
					}
				}
			}

			// Strategy 4: Check for code signatures in functions.php.
			if ( in_array( 'code_signatures', $use_strategies, true ) &&
				 file_exists( trailingslashit( $theme->get_stylesheet_directory() ) . 'functions.php' ) ) {

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
						$is_divi_code = apply_filters( 'divi_squad_is_divi_by_code', true, $theme, $signature );
						if ( $is_divi_code ) {
							return true;
						}
					}
				}
			}
		}

		/**
		 * Filter the final result of Divi theme installation check.
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
	 * 1. Current theme name matching
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

		$base_themes = self::modules_allowed_theme();

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

		// Strategy 1: Check if current theme is directly Divi or Extra.
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
			$divi_constants = array(
				'ET_CORE_VERSION',
				'ET_BUILDER_THEME',
				'ET_BUILDER_VERSION',
				'ET_BUILDER_DIR',
				'ET_BUILDER_URI',
			);

			/**
			 * Filter the constants used to identify active Divi themes.
			 *
			 * @since 3.3.3
			 *
			 * @param array $divi_constants Array of Divi constant names to check.
			 */
			$divi_constants = apply_filters( 'divi_squad_divi_constants', $divi_constants );

			foreach ( $divi_constants as $constant ) {
				if ( defined( $constant ) ) {
					/**
					 * Filter whether a theme with defined Divi constants should be considered an active Divi theme.
					 *
					 * @since 3.3.3
					 *
					 * @param bool   $is_active_divi_constant Whether this is an active Divi theme based on constants.
					 * @param string $constant                The constant that was found.
					 */
					$is_active_divi_constant = apply_filters( 'divi_squad_is_active_divi_by_constant', true, $constant );
					if ( $is_active_divi_constant ) {
						return true;
					}
				}
			}
		}

		// Strategy 4: Check for Divi/Extra framework presence through functions.
		if ( in_array( 'functions', $use_strategies, true ) ) {
			$divi_functions = array(
				'et_setup_theme',
				'et_divi_fonts_url',
				'et_pb_is_pagebuilder_used',
				'et_core_is_fb_enabled',
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

			foreach ( $divi_functions as $function ) {
				if ( function_exists( $function ) ) {
					/**
					 * Filter whether a theme with defined Divi functions should be considered an active Divi theme.
					 *
					 * @since 3.3.3
					 *
					 * @param bool     $is_active_divi_function Whether this is an active Divi theme based on functions.
					 * @param callable $function                The function that was found.
					 */
					$is_active_divi_function = apply_filters( 'divi_squad_is_active_divi_by_function', true, $function );
					if ( $is_active_divi_function ) {
						return true;
					}
				}
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
			 * @param array  $directory_markers Array of directory paths relative to theme root.
			 * @param string $template_dir      The template directory path.
			 */
			$directory_markers = apply_filters( 'divi_squad_active_divi_directory_markers', $directory_markers, $template_dir );

			$marker_count = 0;
			foreach ( $directory_markers as $marker ) {
				if ( file_exists( trailingslashit( $template_dir ) . $marker ) ) {
					++$marker_count;
				}
			}

			// If we find at least 2 markers, it's likely a Divi theme.
			if ( $marker_count >= 2 ) {
				/**
				 * Filter whether a theme detected by directory structure should be considered an active Divi theme.
				 *
				 * @since 3.3.3
				 *
				 * @param bool   $is_active_divi_structure Whether this has an active Divi directory structure.
				 * @param string $template_dir             The template directory path.
				 * @param int    $marker_count             Number of directory markers found.
				 */
				$is_active_divi_structure = apply_filters( 'divi_squad_is_active_divi_by_structure', true, $template_dir, $marker_count );
				if ( $is_active_divi_structure ) {
					return true;
				}
			}
		}

		/**
		 * Filter the final result of active Divi theme check.
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
	 * @return boolean True if Divi Builder plugin is installed, false otherwise.
	 */
	public static function is_divi_builder_plugin_installed(): bool {
		$plugin_main_file = 'divi-builder/divi-builder.php';

		/**
		 * Filter the plugin main file path to check.
		 *
		 * @since 3.3.3
		 *
		 * @param string $plugin_main_file The plugin main file path relative to plugins directory.
		 */
		$plugin_main_file = apply_filters( 'divi_squad_divi_builder_plugin_file', $plugin_main_file );

		$plugin_path = WP_CONTENT_DIR . '/plugins/' . $plugin_main_file;

		// Method 1: Use filesystem API if available.
		if ( function_exists( 'divi_squad' ) && is_callable( array( 'divi_squad', 'get_wp_fs' ) ) ) {
			$is_installed = divi_squad()->get_wp_fs()->exists( $plugin_path );
		} else {
			$is_installed = file_exists( $plugin_path );
		}

		// Method 3: Check alternative locations.
		if ( ! $is_installed ) {
			// Check must-use plugins directory.
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
	 * Uses multiple detection strategies including:
	 * 1. WordPress plugin API
	 * 2. Divi Builder constants
	 * 3. Plugin-specific function existence
	 *
	 * @since  1.0.0
	 * @since  3.3.3 Added multiple detection methods and hooks
	 * @access public
	 *
	 * @return boolean True if Divi Builder plugin is active, false otherwise.
	 */
	public static function is_divi_builder_plugin_active(): bool {
		$plugin_main_file = 'divi-builder/divi-builder.php';

		/**
		 * Filter the plugin main file path to check.
		 *
		 * @since 3.3.3
		 *
		 * @param string $plugin_main_file The plugin main file path relative to plugins directory.
		 */
		$plugin_main_file = apply_filters( 'divi_squad_divi_builder_plugin_active_file', $plugin_main_file );

		/**
		 * Filter the detection strategies used for active Divi Builder plugin.
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
			if ( ! function_exists( 'is_plugin_active' ) && defined( 'ABSPATH' ) && file_exists( ABSPATH . 'wp-admin/includes/plugin.php' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
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
			 * Filter the constants used to identify active Divi Builder plugin.
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
			 * Filter the functions used to identify active Divi Builder plugin.
			 *
			 * @since 3.3.3
			 *
			 * @param array $plugin_functions Array of plugin function names to check.
			 */
			$plugin_functions = apply_filters( 'divi_squad_divi_plugin_functions', $plugin_functions );

			foreach ( $plugin_functions as $function ) {
				if ( function_exists( $function ) ) {
					/**
					 * Filter whether plugin with defined function should be considered an active Divi Builder plugin.
					 *
					 * @since 3.3.3
					 *
					 * @param bool     $is_active_function Whether this is an active plugin based on functions.
					 * @param callable $function           The function that was found.
					 */
					$is_active_function = apply_filters( 'divi_squad_is_divi_plugin_active_by_function', true, $function );
					if ( $is_active_function ) {
						return true;
					}
				}
			}
		}

		/**
		 * Filter the final result of active Divi Builder plugin check.
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
		$is_theme_active  = self::is_any_divi_theme_active();
		$is_plugin_active = self::is_divi_builder_plugin_active();

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
		if ( self::is_divi_builder_plugin_active() ) {
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
