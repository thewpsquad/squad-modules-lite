<?php // phpcs:ignore WordPress.Files.FileName

/**
 * The admin asset management class.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   1.0.0
 * @deprecated 3.0.0 marked as deprecated.
 */

namespace DiviSquad\Admin;

/**
 * Assets class.
 *
 * @package DiviSquad
 * @since   1.0.0
 * @deprecated 3.0.0 marked as deprecated.
 */
class Assets {

	/**
	 * Get the lis of admin extra asset allowed page for the plugin.
	 *
	 * @return array
	 * @since 1.2.0
	 * @deprecated 3.0.0 marked as deprecated.
	 */
	protected static function get_plugin_extra_asset_allowed_pages() {
		return array();
	}

	/**
	 * Enqueue the plugin's scripts and styles files in the WordPress admin area.
	 *
	 * @param string $hook_suffix Hook suffix for the current admin page.
	 *
	 * @return void
	 * @deprecated 3.0.0 marked as deprecated.
	 */
	public function wp_hook_enqueue_plugin_admin_asset( $hook_suffix ) {}

	/**
	 * Get the lis of admin asset allowed page for the plugin.
	 *
	 * @return array
	 * @since 1.2.0
	 * @deprecated 3.0.0 marked as deprecated.
	 */
	protected static function get_plugin_asset_allowed_pages() {
		return array( 'toplevel_page_divi_squad_dashboard' );
	}

	/**
	 * Enqueue extra scripts and styles files in the WordPress admin area.
	 *
	 * @param string $hook_suffix Hook suffix for the current admin page.
	 *
	 * @return void
	 * @deprecated 3.0.0 marked as deprecated.
	 */
	public function wp_hook_enqueue_extra_admin_asset( $hook_suffix ) {}

	/**
	 * Set localize data for admin area.
	 *
	 * @param array $exists_data Exists extra data.
	 *
	 * @return array
	 * @deprecated 3.0.0 marked as deprecated.
	 */
	public function wp_common_localize_script_data( $exists_data ) {
		return $exists_data;
	}

	/**
	 * Set localize data for admin area.
	 *
	 * @param array $exists_data Exists extra data.
	 *
	 * @return array
	 * @deprecated 3.0.0 marked as deprecated.
	 */
	public function wp_localize_script_data( $exists_data ) {
		return $exists_data;
	}
}
