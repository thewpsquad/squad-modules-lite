<?php // phpcs:ignore WordPress.Files.FileName

/**
 * The plugin branding manager.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   3.0.0
 */

namespace DiviSquad\Managers;

use DiviSquad\Base\Factories\BrandAsset as BrandingFactory;

/**
 * The plugin branding management class.
 *
 * @package DiviSquad
 * @since   3.0.0
 */
class Branding {

	/**
	 * Load all the branding.
	 *
	 * @return void
	 */
	public static function load() {
		$branding = BrandingFactory::get_instance();
		if ( $branding instanceof BrandingFactory ) {
			$branding->add( Branding\AdminFooterText::class );
			$branding->add( Branding\PluginActionLinks::class );
			$branding->add( Branding\PluginRowActions::class );
		}
	}
}
