<?php
/**
 * Squad Modules Lite - Advanced Modules for Divi Builder
 *
 * This is the main plugin file that bootstraps the entire plugin functionality.
 * It handles version checks, requirements validation, and plugin initialization.
 *
 * @package     DiviSquad
 * @link        https://squadmodules.com/
 * @author      The WP Squad <support@squadmodules.com>
 * @copyright   2023-2025 The WP Squad (https://thewpsquad.com/)
 * @license     GPL-3.0-only
 *
 * @wordpress-plugin
 * Plugin Name:         Squad Modules Lite
 * Plugin URI:          https://squadmodules.com/
 * Description:         The Essential Divi plugin, offering 25+ stunning free modules like Advanced Divider, Flip box, and more.
 * Version:             3.3.5
 * Requires at least:   6.0
 * Requires PHP:        7.4
 * Author:              The WP Squad
 * Author URI:          https://squadmodules.com/
 * License:             GPL-3.0-only
 * License URI:         https://www.gnu.org/licenses/gpl-3.0.en.html
 * Text Domain:         squad-modules-for-divi
 * Domain Path:         /languages
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct access forbidden.' );
}

// Define plugin constants.
if ( ! defined( 'DIVI_SQUAD_PLUGIN_FILE' ) ) {
	define( 'DIVI_SQUAD_PLUGIN_FILE', __FILE__ );
}

if ( ! file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	return;
}

// Load the Composer autoloader.
require_once __DIR__ . '/vendor/autoload.php';

/**
 * Retrieves the main plugin instance.
 *
 * Provides a singleton instance to manage the plugin's state and functionality.
 *
 * @since 3.2.4
 *
 * @return DiviSquad\SquadModules Returns the main plugin instance.
 * @throws RuntimeException If the plugin instance cannot be created.
 */
function divi_squad(): DiviSquad\SquadModules {
	return DiviSquad\SquadModules::get_instance();
}

if ( ! function_exists( 'divi_squad_fs' ) ) {
	/**
	 * Retrieves the Freemius SDK instance.
	 *
	 * Returns the singleton instance of the Freemius SDK integration, which
	 * manages licensing, analytics, and deployment features for the plugin.
	 * Note that the function expects the Freemius instance to be available;
	 * otherwise, an Exception is thrown.
	 *
	 * @since 1.0.0
	 * @since 3.3.0 Updated to use get_publisher() which now returns the instance from the container
	 *
	 * @return Freemius The Freemius SDK instance for handling licensing and analytics.
	 * @throws Exception If the Freemius SDK is not available.
	 */
	function divi_squad_fs(): Freemius {
		return divi_squad()->get_distributor();
	}
}

// take off!
divi_squad();
