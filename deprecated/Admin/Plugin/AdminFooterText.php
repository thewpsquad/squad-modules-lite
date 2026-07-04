<?php // phpcs:ignore WordPress.Files.FileName
/**
 * The plugin admin footer text management class for the plugin dashboard at admin area.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   1.0.0
 * @deprecated 3.0.0 marked as deprecated.
 */

namespace DiviSquad\Admin\Plugin;

/**
 * Plugin Admin Footer Text class.
 *
 * @package DiviSquad
 * @since   1.0.0
 * @deprecated 3.0.0 marked as deprecated.
 */
class AdminFooterText {

	/**
	 * Filters the "Thank you" text displayed in the admin footer.
	 *
	 * @param string $footer_text The content that will be printed.
	 *
	 * @return  string
	 * @since 1.3.2
	 * @deprecated 3.0.0 marked as deprecated.
	 */
	public function add_plugin_footer_text( $footer_text ) {
		return $footer_text;
	}

	/**
	 * Filters the version/update text displayed in the admin footer.
	 *
	 * @param string $content The content that will be printed.
	 *
	 * @return  string
	 * @since 1.4.8
	 * @deprecated 3.0.0 marked as deprecated.
	 */
	public function add_update_footer_text( $content ) {
		return $content;
	}
}
