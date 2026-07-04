<?php
/**
 * WP helper class for WordPress functions.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   1.2.2
 */

namespace DiviSquad\Utils;

use function get_option;
use function get_plugins;
use function get_site_option;
use function is_multisite;
use function wp_localize_script;
use function wp_set_script_translations;
use const WP_HOME; // @phpstan-ignore-line constant.notFound
use const WP_SITEURL; // @phpstan-ignore-line constant.notFound

/**
 * WP Helper class.
 *
 * @package DiviSquad
 * @since   1.2.2
 */
class WP {

	/**
	 * Detect if the current site is running in a WordPress Playground environment.
	 *
	 * @return bool True if the site is running in a WordPress Playground, false otherwise.
	 */
	public static function is_playground(): bool {
		// Check if WP_HOME or WP_SITEURL contains "playground.wordpress.net".
		if ( defined( 'WP_HOME' ) && strpos( WP_HOME, 'playground.wordpress.net' ) !== false ) {
			return true;
		}

		if ( defined( 'WP_SITEURL' ) && strpos( WP_SITEURL, 'playground.wordpress.net' ) !== false ) {
			return true;
		}

		// If none of the checks passed, it's not a WordPress Playground.
		return false;
	}

	/**
	 * Determines whether a plugin is active.
	 *
	 * Only plugins installed in the plugins/ folder can be active.
	 *
	 * Plugins in the mu-plugins/ folder can't be "activated," so this function will
	 * return false for those plugins.
	 *
	 * For more information on this and similar theme functions, check out
	 * the {@link https://developer.wordpress.org/themes/basics/conditional-tags/
	 * Conditional Tags} article in the Theme Developer Handbook.
	 *
	 * @since 2.5.0
	 *
	 * @param string $plugin Path to the plugin file relative to the plugins' directory.
	 *
	 * @return bool True, if in the active plugins list. False, not in the list.
	 */
	public static function is_plugin_active( string $plugin ): bool {
		return in_array( $plugin, (array) get_option( 'active_plugins', array() ), true ) || self::is_plugin_active_for_network( $plugin );
	}

	/**
	 * Determines whether the plugin is active for the entire network.
	 *
	 * Only plugins installed in the plugins/ folder can be active.
	 *
	 * Plugins in the mu-plugins/ folder can't be "activated," so this function will
	 * return false for those plugins.
	 *
	 * For more information on this and similar theme functions, check out
	 * the {@link https://developer.wordpress.org/themes/basics/conditional-tags/
	 * Conditional Tags} article in the Theme Developer Handbook.
	 *
	 * @since 3.0.0
	 *
	 * @param string $plugin Path to the plugin file relative to the plugins' directory.
	 *
	 * @return bool True if active for the network, otherwise false.
	 */
	public static function is_plugin_active_for_network( string $plugin ): bool {
		static $plugins;

		if ( ! is_multisite() ) {
			return false;
		}

		if ( isset( $plugins ) ) {
			return isset( $plugins[ $plugin ] );
		}

		// Get the active plugins list.
		$plugins = get_site_option( 'active_sitewide_plugins' );

		return isset( $plugins[ $plugin ] );
	}

	/**
	 * Get the active plugins name and versions.
	 *
	 * @return array<array<string, string>>
	 */
	public static function get_active_plugins(): array {
		static $plugins_list;

		if ( isset( $plugins_list ) ) {
			return $plugins_list;
		}

		$active_plugins = self::get_active_plugins_info();
		$plugins_list   = array();

		foreach ( $active_plugins as $active_plugin ) {
			$plugins_list[] = array(
				'name'    => $active_plugin['Name'],
				'slug'    => $active_plugin['Slug'],
				'version' => $active_plugin['Version'],
			);
		}

		return $plugins_list;
	}

	/**
	 * Get the active plugins' information.
	 *
	 * @return array<array<string, string>>
	 */
	public static function get_active_plugins_info(): array {
		static $all_active_plugins;

		if ( isset( $all_active_plugins ) ) {
			return $all_active_plugins;
		}

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once divi_squad()->get_wp_path() . 'wp-admin/includes/plugin.php';
		}

		$all_plugins        = get_plugins();
		$active_plugins     = get_option( 'active_plugins', array() );
		$all_active_plugins = array();

		/**
		 * Filters the list of active plugins.
		 *
		 * @param array $active_plugins The list of active plugins.
		 */
		foreach ( $all_plugins as $plugin => $plugin_info ) {
			if ( self::is_plugin_active( $plugin ) || in_array( $plugin, $active_plugins, true ) ) {
				$plugin_info['Slug']  = $plugin;
				$all_active_plugins[] = $plugin_info;
			}
		}

		return $all_active_plugins;
	}

	/**
	 * Sets translated strings for a script.
	 *
	 * Works only if the script has already been registered.
	 *
	 * @param string $handle The Script handle the textdomain will be attached to.
	 * @param string $domain Optional. Text domain. Default 'default'.
	 * @param string $path   Optional. The full file path to the directory containing translation files.
	 *
	 * @return bool True if the text domain was successfully localized, false otherwise.
	 */
	public static function set_script_translations( string $handle, string $domain = '', string $path = '' ): bool {
		// Check if script is registered.
		if ( ! wp_script_is( $handle, 'registered' ) ) {
			return false;
		}

		// Set defaults if empty.
		$domain = '' !== $domain ? $domain : divi_squad()->get_name();
		$path   = '' !== $path ? $path : divi_squad()->get_languages_path();

		// Basic path validation.
		if ( '' !== $path && ! is_dir( $path ) ) {
			return false;
		}

		return wp_set_script_translations( $handle, $domain, $path );
	}

	/**
	 * Localizes a script.
	 *
	 * Works only if the script has already been registered.
	 *
	 * @param string               $handle      The Script handle the data will be attached to.
	 * @param string               $object_name Name for the JavaScript object. Passed directly, so it should be qualified JS variable.
	 * @param array<string, mixed> $l10n        Data to be localized.
	 *
	 * @return bool True if the script was successfully localized, false otherwise.
	 */
	public static function localize_script( string $handle, string $object_name, array $l10n ): bool {
		return wp_localize_script( $handle, $object_name, $l10n );
	}
}
