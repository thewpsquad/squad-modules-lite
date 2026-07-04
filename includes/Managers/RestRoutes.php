<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Rest API Routes Manager
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   1.0.0
 */

namespace DiviSquad\Managers;

use DiviSquad\Base\Factories\RestRoute as RestRouteFactory;

/**
 * Rest API Routes
 *
 * @package DiviSquad
 * @since   1.0.0
 */
class RestRoutes {

	/**
	 * Load rest route on init time.
	 *
	 * @return void
	 */
	public static function load() {
		// Register all rest api routes.
		$rest_api = RestRouteFactory::get_instance();
		if ( $rest_api instanceof RestRouteFactory ) {
			// Load rest routes for core features.
			$rest_api->add( RestRoutes\Version1\Modules::class );
			$rest_api->add( RestRoutes\Version1\Extensions::class );

			// Load rest routes for what's new for the plugin.
			$rest_api->add( RestRoutes\Version1\WhatsNew\Changelog::class );

			// Load rest routes for notices.
			$rest_api->add( RestRoutes\Version1\Notices\Discount::class );
			$rest_api->add( RestRoutes\Version1\Notices\Review::class );
			$rest_api->add( RestRoutes\Version1\Notices\ProActivation::class );

			// Load rest routes for modules.
			$rest_api->add( RestRoutes\Version1\Modules\PostGrid::class );

			// Load rest routes for extensions.
			$rest_api->add( RestRoutes\Version1\Extensions\Copy::class );
		}
	}
}
