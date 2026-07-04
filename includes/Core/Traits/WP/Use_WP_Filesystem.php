<?php
/**
 * The Filesystem class.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   3.2.0
 */


namespace DiviSquad\Core\Traits\WP;

use WP_Filesystem_Base;

/**
 * The Filesystem trait
 *
 * @package DiviSquad
 * @since   3.2.0
 */
trait Use_WP_Filesystem {

	/**
	 * Get the filesystem.
	 *
	 * @access protected
	 * @return WP_Filesystem_Base
	 */
	public function get_wp_fs(): WP_Filesystem_Base {
		global $wp_filesystem;

		// If the filesystem has not been instantiated yet, do it here.
		if ( ! $wp_filesystem ) {
			if ( ! function_exists( 'WP_Filesystem' ) ) {
				require_once wp_normalize_path( ABSPATH . 'wp-admin/includes/file.php' ); // phpcs:ignore WPThemeReview.CoreFunctionality.FileInclude.FileIncludeFound
			}
			WP_Filesystem();
		}

		return $wp_filesystem;
	}
}
