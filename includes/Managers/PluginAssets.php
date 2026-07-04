<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Assets Manager
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   1.0.0
 */

namespace DiviSquad\Managers;

use DiviSquad\Base\Factories\PluginAsset as AssetFactory;

/**
 * Handles loading and registration of plugin assets.
 *
 * @package DiviSquad
 * @since   1.0.0
 */
class PluginAssets {

	/**
	 * Load all the branding.
	 *
	 * @return void
	 */
	public static function load() {
		$asset = AssetFactory::get_instance();
		if ( $asset instanceof AssetFactory ) {
			$asset->add( Assets\Admin::class );
			$asset->add( Assets\Modules::class );
		}
	}
}
