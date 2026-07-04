<?php
/**
 * Divi helper class for common functions.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   1.0.0
 */

namespace DiviSquad\Utils;

use function add_filter;
use function et_get_dynamic_assets_path;
use function et_pb_maybe_fa_font_icon;
use function et_use_dynamic_icons;
use function get_option;
use function get_template;
use function wp_get_theme;
use function wp_get_themes;

/**
 * Divi class.
 *
 * @package DiviSquad
 * @since   1.0.0
 */
class Divi {
	/**
	 * Check if Divi theme builder is enabled.
	 *
	 * @return boolean
	 */
	public static function is_bfb_enabled(): bool {
		return function_exists( '\et_builder_bfb_enabled' ) && \et_builder_bfb_enabled();
	}

	/**
	 * Check if Theme Builder is Used on the page.
	 *
	 * @return boolean
	 */
	public static function is_theme_builder_used(): bool {
		return function_exists( '\et_fb_is_theme_builder_used_on_page' ) && \et_fb_is_theme_builder_used_on_page();
	}

	/**
	 * Check if the current screen is the Theme Builder administration screen.
	 *
	 * @return boolean
	 */
	public static function is_tb_admin_screen(): bool {
		return function_exists( '\et_builder_is_tb_admin_screen' ) && \et_builder_is_tb_admin_screen();
	}

	/**
	 * Check if Divi Builder 5 is enabled.
	 *
	 * @return boolean
	 */
	public static function is_d5_enabled(): bool {
		return function_exists( '\et_builder_d5_enabled' ) && \et_builder_d5_enabled() && self::is_fb_enabled();
	}

	/**
	 * Check if Divi visual builder is enabled.
	 *
	 * @return boolean
	 */
	public static function is_fb_enabled(): bool {
		return function_exists( '\et_core_is_fb_enabled' ) && \et_core_is_fb_enabled();
	}

	/**
	 * Collect icon type from Divi formatted value.
	 *
	 * @param string $icon_value Divi formatted value for Icon.
	 *
	 * @return string
	 */
	public static function get_icon_type( string $icon_value ): string {
		return '' !== $icon_value && strpos( $icon_value, '||fa||' ) === 0 ? 'fa' : 'divi';
	}

	/**
	 * Determine icon font weight
	 *
	 * @param string $icon_value Divi formatted value for Icon.
	 *
	 * @return string
	 */
	public static function get_icon_font_weight( string $icon_value ): string {
		if ( '' !== $icon_value ) {
			$icon_data = explode( $icon_value, '|' );

			return array_pop( $icon_data );
		}

		return '400';
	}

	/**
	 * Get unicode icon data
	 *
	 * @param string $icon_value Icon font value.
	 *
	 * @return string Icon data
	 */
	public static function get_icon_data_to_unicode( string $icon_value ): string {
		if ( '' !== $icon_value ) {
			$icon_all_data = explode( '||', $icon_value );
			$icon_data     = array_shift( $icon_all_data );
			$icon_data     = str_replace( ';', '', $icon_data );

			return str_replace( '&#x', '\\', $icon_data );
		} else {
			return '';
		}
	}

	/**
	 * Add Icons css into the divi asset list when the Dynamic CSS option is turn on in current installation
	 *
	 * @param array $global_list The existed global asset list.
	 *
	 * @return array
	 */
	public static function global_assets_list( array $global_list = array() ): array {
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
	 * @param array $global_list The existed global asset list.
	 *
	 * @return array
	 */
	public static function global_fa_assets_list( array $global_list = array() ): array {
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
	 * @param string $icon_data The icon value.
	 *
	 * @return void
	 */
	public static function inject_fa_icons( string $icon_data ) {
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
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public static function is_any_divi_theme_installed(): bool {
		$wp_installed_themes     = array_keys( wp_get_themes() );
		$is_divi_theme_installed = in_array( 'Divi', $wp_installed_themes, true );
		$is_divi_extra_installed = in_array( 'Extra', $wp_installed_themes, true );

		return ( $is_divi_theme_installed || $is_divi_extra_installed );
	}

	/**
	 * Returns boolean if any Divi theme active in the current WordPress installation
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public static function is_any_divi_theme_active(): bool {
		return in_array( get_template(), array( 'Divi', 'Extra' ), true );
	}

	/**
	 * Returns boolean if the Divi Builder Plugin is installed in the current WordPress installation
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public static function is_divi_builder_plugin_installed(): bool {
		return file_exists( WP_CONTENT_DIR . '/plugins/divi-builder/divi-builder.php' );
	}

	/**
	 * Returns boolean if the Divi Builder Plugin active in the current WordPress installation
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public static function is_divi_builder_plugin_active(): bool {
		return WP::is_plugin_active( 'divi-builder/divi-builder.php' );
	}

	/**
	 * Returns boolean if any Divi theme is installed active in the current WordPress installation
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public static function is_allowed_theme_activated(): bool {
		return in_array( esc_html( get_template() ), self::modules_allowed_theme(), true );
	}

	/**
	 * Return the allowed theme list for Divi Utils support
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public static function modules_allowed_theme(): array {
		return array( 'Divi', 'Extra' );
	}
}
