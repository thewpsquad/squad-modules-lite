<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName, WordPress.Files.FileName.NotHyphenatedLowercase

/**
 * Squad Modules Lite
 *
 * @package     DiviSquad
 * @author      WP Squad <support@squadmodules.com>
 * @copyright   2023-2024 WP Squad (https://thewpsquad.com/)
 *
 * @wordpress-plugin
 * Plugin Name:         Squad Modules Lite
 * Plugin URI:          https://squadmodules.com/
 * Description:         The Advanced Divi plugin you install after Divi or Extra Theme!
 * Version:             3.1.8
 * Requires at least:   5.0.0
 * Requires PHP:        5.6.40
 * Author:              WP Squad
 * Author URI:          https://squadmodules.com/
 * License:             GPL-3.0-only
 * License URI:         https://www.gnu.org/licenses/gpl-3.0.en.html
 * Text Domain:         squad-modules-for-divi
 * Domain Path:         /languages
 *
 * Copyright 2023-2024 WP Squad (https://thewpsquad.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

/**
 * Autoload function for non-deprecated classes.
 *
 * @param string $class_name Class name.
 *
 * @return void
 */
spl_autoload_register(
	static function ( $class_name ) {
		// Bail out if the class name doesn't start with our prefix.
		if ( 0 !== strpos( $class_name, 'DiviSquad\\' ) ) {
			return;
		}

		// Replace the namespace separator with the path prefix and the directory separator.
		$class_path = str_replace( 'DiviSquad\\', '', $class_name );
		$valid_path = str_replace( array( '\\', '//' ), DIRECTORY_SEPARATOR, $class_path );

		// Add the includes directory and the .php extension.
		$valid_path = 'includes/' . $valid_path;
		$class_file = realpath( __DIR__ . "/$valid_path.php" );
		if ( file_exists( $class_file ) ) {
			require_once $class_file;
		}
	}
);

if ( ! class_exists( DiviSquad\SquadModules::class ) ) {
	return;
}

// Define the core constants.
define( 'DIVI_SQUAD__FILE__', __FILE__ );

try {

	/**
	 * Helper function to get the Divi Squad Plugin instance.
	 *
	 * @return DiviSquad\SquadModules
	 */
	function divi_squad() {
		return DiviSquad\SquadModules::get_instance();
	}

	if ( is_admin() ) {
		// Special logic for premium only plugin
		if ( function_exists( 'divi_squad_fs' ) ) {
			// Declare the plugin as premium only.
			divi_squad_fs()->set_basename( false, __FILE__ );
		} else {
			/**
			 * Initialize Freemius SDK.
			 *
			 * @return bool|\Freemius
			 * @throws Exception If the SDK cannot be initialized.
			 */
			function divi_squad_fs() {
				global $divi_squad_fs;

				if ( ! isset( $divi_squad_fs ) ) {
					$squad_publisher = new DiviSquad\Integrations\Publisher();
					$divi_squad_fs   = $squad_publisher->get_fs();
				}

				return $divi_squad_fs;
			}

			// Init Freemius.
			divi_squad_fs();

			/**
			 * Fires after the Freemius SDK is loaded.
			 *
			 * @since 3.0.0
			 */
			do_action( 'divi_squad_fs_loaded' );
		}
	}

	/**
	 * Fires before the plugin is loaded.
	 *
	 * @since 3.1.0
	 */
	do_action( 'divi_squad_before_loaded' );

	// Load the plugin.
	divi_squad();
} catch ( Exception $e ) {
	// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.PHP.DevelopmentFunctions.error_log_error_log
	error_log( 'SQUAD ERROR: ' . $e->getMessage() );
	// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.PHP.DevelopmentFunctions.error_log_error_log

	// Send an error report.
	DiviSquad\Managers\Emails\ErrorReport::quick_send(
		$e,
		array(
			'additional_info' => 'An error message from plugin bootstrap file.',
		)
	);
}
