<?php
/**
 * Divi helper class for common functions.
 *
 * This utility class provides helper methods to check various Divi states,
 * handle icon processing, manage asset loading, and verify Divi theme/plugin status.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   1.0.0
 */

namespace DiviSquad\Utils;

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
 * @package DiviSquad
 * @since   1.0.0
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
	 * @param  string $icon_value Divi formatted value for Icon.
	 * @return string Returns 'fa' for Font Awesome icons or 'divi' for Divi icons.
	 */
	public static function get_icon_type( string $icon_value ): string {
		return '' !== $icon_value && strpos( $icon_value, '||fa||' ) === 0 ? 'fa' : 'divi';
	}

	/**
	 * Determine icon font weight
	 *
	 * @since  1.0.0
	 * @param  string $icon_value Divi formatted value for Icon.
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
	 * @param  string $icon_value Icon font value.
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
	 * @param  array<string, array<string, string>> $global_list The existed global asset list.
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
	 * @param  array<string, array<string, string>> $global_list The existed global asset list.
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
	 * @param  string $icon_data The icon value.
	 * @return void
	 *
	 * @uses add_filter() Hooks into asset list filters to add necessary icon assets.
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
	 * Returns boolean if any Divi theme installed in the current WordPress installation
	 *
	 * @since  1.0.0
	 * @return boolean True if either Divi or Extra theme is installed, false otherwise.
	 */
	public static function is_any_divi_theme_installed(): bool {
		$wp_installed_themes = array_keys( wp_get_themes() );

		// Check for Divi or Extra theme
		return (
			in_array( 'Divi', $wp_installed_themes, true ) ||
			in_array( 'Extra', $wp_installed_themes, true )
		);
	}

	/**
	 * Returns boolean if any Divi theme active in the current WordPress installation
	 *
	 * @since  1.0.0
	 * @return boolean True if either Divi or Extra theme is active, false otherwise.
	 */
	public static function is_any_divi_theme_active(): bool {
		$current_theme = wp_get_theme();
		if ( ! $current_theme instanceof \WP_Theme ) {
			return false;
		}

		$template = $current_theme->get_template();
		return in_array( $template, array( 'Divi', 'Extra' ), true );
	}

	/**
	 * Returns boolean if the Divi Builder Plugin is installed in the current WordPress installation
	 *
	 * @since  1.0.0
	 * @return boolean True if Divi Builder plugin is installed, false otherwise.
	 */
	public static function is_divi_builder_plugin_installed(): bool {
		// Check if the function exists first
		if ( ! function_exists( 'divi_squad' ) || ! is_callable( array( divi_squad(), 'get_wp_fs' ) ) ) {
			return file_exists( WP_CONTENT_DIR . '/plugins/divi-builder/divi-builder.php' );
		}

		return divi_squad()->get_wp_fs()->exists( WP_CONTENT_DIR . '/plugins/divi-builder/divi-builder.php' );
	}

	/**
	 * Returns boolean if the Divi Builder Plugin active in the current WordPress installation
	 *
	 * @since  1.0.0
	 * @return boolean True if Divi Builder plugin is active, false otherwise.
	 */
	public static function is_divi_builder_plugin_active(): bool {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			return false;
		}

		return is_plugin_active( 'divi-builder/divi-builder.php' );
	}

	/**
	 * Returns boolean if any Divi theme is installed active in the current WordPress installation
	 *
	 * @since  1.0.0
	 * @return boolean True if an allowed theme is active, false otherwise.
	 */
	public static function is_allowed_theme_activated(): bool {
		$current_theme = wp_get_theme();
		if ( ! $current_theme instanceof \WP_Theme ) {
			return false;
		}

		return in_array( $current_theme->get_template(), self::modules_allowed_theme(), true );
	}

	/**
	 * Return the allowed theme list for Divi Utils support
	 *
	 * @since  1.0.0
	 * @return array<string> Array of allowed theme names (Divi and Extra).
	 */
	public static function modules_allowed_theme(): array {
		return array( 'Divi', 'Extra' );
	}

	/**
	 * Returns boolean if the Dynamic CSS feature is turn on for Divi Builder in the current WordPress installation
	 *
	 * Checks various theme and plugin options to determine if dynamic CSS is enabled.
	 *
	 * @since  1.0.0
	 * @return boolean True if Dynamic CSS is enabled, false otherwise.
	 */
	public static function is_dynamic_css_enable(): bool {
		$divi_builder_dynamic_css = 'on';
		$current_theme            = wp_get_theme();

		if ( ! $current_theme ) {
			return false;
		}

		if ( 'Divi' === $current_theme->get( 'Name' ) ) {
			$config_options = get_option( 'et_divi', array() );

			if ( isset( $config_options['divi_dynamic_css'] ) && 'false' === $config_options['divi_dynamic_css'] ) {
				$divi_builder_dynamic_css = 'off';
			}
		}

		if ( 'Extra' === $current_theme->get( 'Name' ) ) {
			$config_options = get_option( 'et_extra', array() );

			if ( isset( $config_options['extra_dynamic_css'] ) && 'false' === $config_options['extra_dynamic_css'] ) {
				$divi_builder_dynamic_css = 'off';
			}
		}

		if ( self::is_divi_builder_plugin_active() ) {
			$config_options = get_option( 'et_pb_builder_options', array() );

			if ( isset( $config_options['performance_main_dynamic_css'] ) && 'false' === $config_options['performance_main_dynamic_css'] ) {
				$divi_builder_dynamic_css = 'off';
			}
		}

		return 'on' === $divi_builder_dynamic_css;
	}

	/**
	 * Get the current Divi Builder version
	 *
	 * @since  1.0.0
	 * @return string The current Divi Builder version or empty string if unavailable.
	 */
	public static function get_builder_version(): string {
		return function_exists( '\et_get_theme_version' ) ? \et_get_theme_version() : '';
	}

	/**
	 * Get the current Divi Builder mode (plugin or theme)
	 *
	 * @since  1.0.0
	 * @return string Returns 'plugin' if Divi Builder plugin is active, otherwise 'theme'.
	 */
	public static function get_builder_mode(): string {
		return static::is_divi_builder_plugin_active() ? 'plugin' : 'theme';
	}

	/**
	 * Determines whether styles should be loaded based on current context.
	 *
	 * Checks if we're in the Divi Frontend Builder, using Theme Builder,
	 * or viewing a singular post with the builder enabled. The result can
	 * be filtered by plugins and themes.
	 *
	 * @since 3.3.0
	 *
	 * @return boolean True if styles should be loaded, false otherwise.
	 */
	public static function should_load_style(): bool {
		// Load if in Frontend Builder or Theme Builder
		if ( static::is_fb_enabled() || static::is_theme_builder_used() ) {
			$should_load = true;
		} elseif ( function_exists( 'et_builder_enabled_for_post' ) && is_singular() ) {
			$post_id = get_the_ID();
			if ( false !== $post_id && et_builder_enabled_for_post( $post_id ) ) {
				$should_load = true;
			} else {
				$should_load = false;
			}
		} else {
			$should_load = false;
		}

		/**
		 * Filters whether Divi Squad styles should be loaded.
		 *
		 * @since 3.3.0
		 *
		 * @param boolean $should_load Whether styles should be loaded.
		 */
		return apply_filters( 'divi_squad_should_load_style', $should_load );
	}
}
