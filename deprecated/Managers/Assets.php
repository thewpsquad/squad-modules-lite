<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Assets Manager
 *
 * @since       1.0.0
 * @deprecated  3.0.0 marked as deprecated.
 * @package     DiviSquad
 * @author      The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Managers;

/**
 * Assets Class
 *
 * @since       1.0.0
 * @deprecated  3.0.0 marked as deprecated.
 * @package     DiviSquad
 */
class Assets {

	/**
	 * Enqueue scripts for frontend.
	 *
	 * @deprecated 3.0.0 marked as deprecated.
	 * @return void
	 */
	public function enqueue_scripts() {}

	/**
	 * Enqueue scripts for builder.
	 *
	 * @deprecated 3.0.0 marked as deprecated.
	 * @return void
	 */
	public function enqueue_scripts_vb() {}

	/**
	 * Load requires asset extra in the visual builder by default.
	 *
	 * @deprecated 3.0.0 marked as deprecated.
	 *
	 * @param string $output Exist output.
	 *
	 * @return string
	 */
	public function wp_localize_script_data( $output ) {
		return $output;
	}
}
