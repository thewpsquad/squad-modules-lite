<?php // phpcs:ignore WordPress.Files.FileName

/**
 * The Core class for Divi Squad.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   1.0.0
 */

namespace DiviSquad\Integrations;

use function add_action;
use function add_filter;
use function divi_squad;
use function is_admin;

/**
 * Divi Squad Core Class.
 *
 * @package DiviSquad
 * @since   1.0.0
 */
abstract class Core extends \DiviSquad\Base\Core {

	/**
	 * Load the divi custom modules for the divi builder.
	 *
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
	 * @return void
	 */
	public function hook_migrate_builder_settings() {
		if ( class_exists( \DiviSquad\Managers\Migrations::class ) ) {
			\DiviSquad\Managers\Migrations::init();
		}
	}

	/**
	 *  Load the extensions.
	 *
	 * @return void
	 */
	public function hook_initialize_builder_extension() {
		if ( class_exists( DiviBuilder::class ) ) {
			new DiviBuilder( $this->name, divi_squad()->get_path(), divi_squad()->get_url() );
		}
	}

	/**
	 * Used to update the content of the cached definitions js file.
	 *
	 * @return void
	 */
	public function hook_initialize_builder_asset_definitions() {
		if ( function_exists( 'et_fb_process_shortcode' ) && class_exists( DiviBuilderBackend::class ) ) {
			$helpers = new DiviBuilderBackend();
			add_filter( 'et_fb_backend_helpers', array( $helpers, 'static_asset_definitions' ), 11 );
			add_filter( 'et_fb_get_asset_helpers', array( $helpers, 'asset_definitions' ), 11 );
		}
	}
}
