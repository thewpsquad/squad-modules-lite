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
 * @copyright   2023-2024 The WP Squad (https://thewpsquad.com/)
 * @license     GPL-3.0-only
 *
 * @wordpress-plugin
 * Plugin Name:         Squad Modules Lite
 * Plugin URI:          https://squadmodules.com/
 * Description:         The Essential Divi plugin, offering 25+ stunning free modules like Advanced Divider, Flip box, and more.
 * Version:             3.2.1
 * Requires at least:   5.8.0
 * Requires PHP:        7.4
 * Author:              The WP Squad
 * Author URI:          https://squadmodules.com/
 * License:             GPL-3.0-only
 * License URI:         https://www.gnu.org/licenses/gpl-3.0.en.html
 * Text Domain:         squad-modules-for-divi
 * Domain Path:         /languages
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct access forbidden.' );
}

// Define plugin constants
if ( ! defined( 'DIVI_SQUAD_PLUGIN_FILE' ) ) {
	define( 'DIVI_SQUAD_PLUGIN_FILE', __FILE__ );
}

/**
 * Custom autoloader for plugin classes.
 *
 * @param string $class_name Full class name including namespace.
 */
spl_autoload_register(
	function ( $class_name ) {
		// Only handle our namespace
		if ( strpos( $class_name, 'DiviSquad\\' ) !== 0 ) {
			return;
		}

		// Convert namespace to file path
		$class_path = str_replace(
			array( 'DiviSquad\\', '\\' ),
			array( '', DIRECTORY_SEPARATOR ),
			$class_name
		);

		// Build full file path
		$file = plugin_dir_path( __FILE__ ) . 'includes' . DIRECTORY_SEPARATOR . $class_path . '.php';
		$file = realpath( $file );

		// Include file if it exists
		if ( $file && file_exists( $file ) ) {
			require_once $file;
		}
	}
);

/**
 * Get the main plugin instance
 *
 * Returns the singleton instance of the main plugin class. This instance is used
 * throughout the plugin to maintain state and coordinate functionality.
 *
 * @since 1.0.0
 * @return DiviSquad\SquadModules Main plugin instance
 * @throws RuntimeException If plugin instance cannot be created
 */
function divi_squad(): DiviSquad\SquadModules {
	return DiviSquad\SquadModules::get_instance();
}

/**
 * Get Freemius SDK instance
 *
 * Returns the singleton instance of the Freemius SDK integration. This is used
 * for licensing, analytics, and deployment functionality.
 *
 * @since 1.0.0
 * @return Freemius Freemius instance or null if initialization fails
 * @throws Exception If Freemius SDK is not available
 */
function divi_squad_fs(): Freemius {
	return divi_squad()->get_publisher();
}

// take off!
divi_squad();
