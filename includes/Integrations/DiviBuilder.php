<?php // phpcs:ignore WordPress.Files.FileName
/**
 * The main class for Divi Squad.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   1.0.0
 */

namespace DiviSquad\Integrations;

use DiviSquad\Base\DiviBuilder\Integration\ShortcodeAPI;

/**
 * Divi Squad Class.
 *
 * @package DiviSquad
 * @since   1.0.0
 */
class DiviBuilder extends ShortcodeAPI {

	/**
	 * Get the plugin version number
	 *
	 * @return string
	 */
	public function get_version(): string {
		return divi_squad()->get_version();
	}

	/**
	 * Loads custom modules when the builder is ready.
	 *
	 * @since 1.0.0
	 */
	public function hook_et_builder_ready() {
		divi_squad()->modules->load_modules( dirname( __DIR__ ) );
	}
}
