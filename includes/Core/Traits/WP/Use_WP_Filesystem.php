<?php // phpcs:ignore WordPress.Files.FileName

/**
 * The Filesystem class.
 *
 * @since   3.2.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Core\Traits\WP;

use WP_Filesystem_Base;

/**
 * The Filesystem trait
 *
 * @since   3.2.0
 * @package DiviSquad
 */
trait Use_WP_Filesystem {

	/**
	 * Get the filesystem.
	 *
	 * @access protected
	 * @return WP_Filesystem_Base
	 */
	public function get_wp_fs(): WP_Filesystem_Base {
		return self::get_wp_filesystem();
	}

	/**
	 * Get the WordPress filesystem instance (static version).
	 *
	 * This static method provides access to the WordPress filesystem API without
	 * requiring an instance of the class. It ensures the filesystem is properly
	 * initialized before returning it.
	 *
	 * @since 3.4.0
	 *
	 * @return WP_Filesystem_Base The WordPress filesystem instance.
	 */
	public static function get_wp_filesystem(): WP_Filesystem_Base {
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
