<?php // phpcs:ignore WordPress.Files.FileName

/**
 * The plugin admin footer text management class for the plugin dashboard at admin area.
 *
 * @since      1.0.0
 * @deprecated 3.0.0 marked as deprecated.
 * @package    DiviSquad
 * @author     The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Admin\Plugin;

/**
 * Plugin Admin Footer Text class.
 *
 * @since      1.0.0
 * @deprecated 3.0.0 marked as deprecated.
 * @package    DiviSquad
 */
class AdminFooterText {

	/**
	 * Filters the "Thank you" text displayed in the admin footer.
	 *
	 * @since      1.3.2
	 * @deprecated 3.0.0 marked as deprecated.
	 *
	 * @param string $footer_text The content that will be printed.
	 *
	 * @return  string
	 */
	public function add_plugin_footer_text( $footer_text ) {
		return $footer_text;
	}

	/**
	 * Filters the version/update text displayed in the admin footer.
	 *
	 * @since      1.4.8
	 * @deprecated 3.0.0 marked as deprecated.
	 *
	 * @param string $content The content that will be printed.
	 *
	 * @return  string
	 */
	public function add_update_footer_text( $content ) {
		return $content;
	}
}
