<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Assets Manager
 *
 * @package     DiviSquad
 * @author      The WP Squad <support@squadmodules.com>
 * @since       1.0.0
 * @deprecated  3.0.0 marked as deprecated.
 */

namespace DiviSquad\Managers;

/**
 * Assets Class
 *
 * @package     DiviSquad
 * @since       1.0.0
 * @deprecated 3.0.0 marked as deprecated.
 */
class Assets {

	/**
	 * Enqueue scripts for frontend.
	 *
	 * @return void
	 * @deprecated 3.0.0 marked as deprecated.
	 */
	public function enqueue_scripts() {}

	/**
	 * Enqueue scripts for builder.
	 *
	 * @return void
	 * @deprecated 3.0.0 marked as deprecated.
	 */
	public function enqueue_scripts_vb() {}

	/**
	 * Load requires asset extra in the visual builder by default.
	 *
	 * @param string $output Exist output.
	 *
	 * @return string
	 * @deprecated 3.0.0 marked as deprecated.
	 */
	public function wp_localize_script_data( $output ) {
		return $output;
	}
}
