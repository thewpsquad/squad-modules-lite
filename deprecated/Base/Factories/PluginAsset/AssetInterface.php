<?php // phpcs:ignore WordPress.Files.FileName

/**
 * The Asset Interface.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   3.0.0
 * @deprecated 3.3.0
 */

namespace DiviSquad\Base\Factories\PluginAsset;

/**
 * The Asset Interface.
 *
 * @package DiviSquad
 * @since   3.0.0
 * @deprecated 3.3.0
 */
interface AssetInterface {

	/**
	 * Enqueue scripts, styles, and other assets in the WordPress frontend and admin area.
	 *
	 * @param string $type        The type of the script. Default is 'frontend'.
	 * @param string $hook_suffix The hook suffix for the current admin page.
	 *
	 * @return void
	 */
	public function enqueue_scripts( string $type = 'frontend', $hook_suffix = '' );

	/**
	 * Localize script data.
	 *
	 * @param string       $type The type of the localize data. Default is 'raw'. Accepts 'raw' or 'output'.
	 * @param string|array $data The data to localize.
	 *
	 * @return string|array
	 */
	public function get_localize_data( string $type = 'raw', $data = array() );
}
