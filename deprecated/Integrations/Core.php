<?php // phpcs:ignore WordPress.Files.FileName

/**
 * The Core class for Divi Squad.
 *
 * @since      1.0.0
 * @deprecated 3.3.0
 * @package    DiviSquad
 * @author     The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Integrations;

use function add_action;

/**
 * Divi Squad Core Class.
 *
 * @since      1.0.0
 * @deprecated 3.3.0
 * @package    DiviSquad
 */
abstract class Core extends \DiviSquad\Base\Core {

	/**
	 * Load the divi custom modules for the divi builder.
	 *
	 * @deprecated 3.3.0
	 * @return void
	 */
	protected function load_modules_for_builder() {
		// Register all hooks for divi integration.
		add_action( 'wp_loaded', array( $this, 'hook_initialize_builder_asset_definitions' ) );
		add_action( 'divi_extensions_init', array( $this, 'hook_migrate_builder_settings' ) );
		add_action( 'divi_extensions_init', array( $this, 'hook_initialize_builder_extension' ) );

		// Force the legacy backend builder to reload its template cache.
		// This ensures that custom modules are available for use right away.
		if ( function_exists( 'et_pb_force_regenerate_templates' ) ) {
			\et_pb_force_regenerate_templates();
		}
	}

	/**
	 *  Load the settings migration.
	 *
	 * @deprecated 3.3.0
	 * @return void
	 */
	public function hook_migrate_builder_settings() {}

	/**
	 *  Load the extensions.
	 *
	 * @deprecated 3.3.0
	 * @return void
	 */
	public function hook_initialize_builder_extension() {}

	/**
	 * Used to update the content of the cached definitions js file.
	 *
	 * @deprecated 3.3.0
	 * @return void
	 */
	public function hook_initialize_builder_asset_definitions() {}
}
