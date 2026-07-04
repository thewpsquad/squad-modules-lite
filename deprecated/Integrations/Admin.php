<?php // phpcs:ignore WordPress.Files.FileName
/**
 * The WordPress connection class
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   1.0.0
 * @deprecated 3.0.0 marked as deprecated.
 */

namespace DiviSquad\Integrations;

use DiviSquad\Admin as SquadAdmin;

/**
 * Admin Class.
 *
 * @package DiviSquad
 * @since   1.0.0
 * @deprecated 3.0.0 marked as deprecated.
 */
class Admin {

	/**
	 * Get the instance of the current class.
	 *
	 * @return void
	 * @deprecated 3.0.0 marked as deprecated.
	 */
	public static function load() {}

	/**
	 * Fires when enqueuing scripts for all admin pages.
	 *
	 * @param \DiviSquad\Admin\Assets $admin_asset The instance of Admin asset class.
	 *
	 * @return void
	 * @since 1.2.0
	 * @deprecated 3.0.0 marked as deprecated.
	 */
	protected static function register_admin_scripts( $admin_asset ) {
		add_action( 'admin_enqueue_scripts', array( $admin_asset, 'wp_hook_enqueue_plugin_admin_asset' ) );
		add_action( 'admin_enqueue_scripts', array( $admin_asset, 'wp_hook_enqueue_extra_admin_asset' ) );
	}

	/**
	 * Include all actions links for the plugin.
	 *
	 * @param SquadAdmin\Plugin\ActionLinks $action_links The instance of Plugin action links class.
	 *
	 * @return void
	 * @since 1.2.0
	 * @deprecated 3.0.0 marked as deprecated.
	 */
	protected static function register_plugin_action_links( $action_links ) {
		if ( method_exists( $action_links, 'get_plugin_base' ) ) {
			$plugin_base    = $action_links->get_plugin_base();
			$callback_array = array( $action_links, 'add_plugin_action_links' );
			add_filter( 'plugin_action_links_' . $plugin_base, $callback_array );
		}
	}

	/**
	 * Include all row metas for the plugin.
	 *
	 * @param SquadAdmin\Plugin\RowMeta $row_meta The instance of the Plugin row meta.
	 *
	 * @return void
	 * @since 1.2.0
	 * @deprecated 3.0.0 marked as deprecated.
	 */
	protected static function register_plugin_row_meta( $row_meta ) {
		add_filter( 'plugin_row_meta', array( $row_meta, 'add_plugin_row_meta' ), 10, 2 );
	}

	/**
	 * Include admin footer text for the plugin.
	 *
	 * @param SquadAdmin\Plugin\AdminFooterText $footer_text The instance of the Plugin row meta.
	 *
	 * @return void
	 * @since 1.2.0
	 * @deprecated 3.0.0 marked as deprecated.
	 */
	protected static function register_plugin_footer_text( $footer_text ) {
		add_filter( 'admin_footer_text', array( $footer_text, 'add_plugin_footer_text' ) );
	}

	/**
	 * Include update footer text for the plugin at admin area.
	 *
	 * @param SquadAdmin\Plugin\AdminFooterText $footer_text The instance of the Plugin row meta.
	 *
	 * @return void
	 * @since 1.4.8
	 * @deprecated 3.0.0 marked as deprecated.
	 */
	protected static function register_update_footer_text( $footer_text ) {
		add_filter( 'update_footer', array( $footer_text, 'add_update_footer_text' ), 15 );
	}
}
