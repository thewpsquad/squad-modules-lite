<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Notices Manager
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   3.0.0
 */

namespace DiviSquad\Managers;

use DiviSquad\Base\Factories\AdminNotice as AdminNoticeFactory;

/**
 * Notices
 *
 * @package DiviSquad
 * @since   3.0.0
 */
class Notices {

	/**
	 * Load all notices.
	 *
	 * @return void
	 */
	public static function load() {
		$notice = AdminNoticeFactory::get_instance();
		if ( $notice instanceof AdminNoticeFactory ) {
			$notice->add( Notices\Review::class );
			$notice->add( Notices\ProActivation::class );
			$notice->add( Notices\Discount::class );
		}
	}
}
