<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Plugin Action Links Asset
 *
 * This class handles the plugin action links in the plugins page.
 *
 * @since   3.3.3
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Core\Admin\Branding;

/**
 * Plugin Action Links Asset
 *
 * @since   3.3.3
 * @package DiviSquad
 */
class Plugin_Action_Links extends Brand_Asset_Base {

	/**
	 * The asset ID.
	 *
	 * @var string
	 */
	protected string $asset_id = 'plugin-action-links';

	/**
	 * Get the asset type.
	 *
	 * @since 3.3.3
	 *
	 * @return string The asset type.
	 */
	public function get_type(): string {
		return 'plugin_action_links';
	}

	/**
	 * Get the asset position.
	 *
	 * @since 3.3.3
	 *
	 * @return string The asset position.
	 */
	public function get_position(): string {
		return 'before';
	}

	/**
	 * Check if the asset is allowed in network admin.
	 *
	 * @since 3.3.3
	 *
	 * @return bool Whether the asset is allowed in network admin.
	 */
	public function is_allowed_in_network(): bool {
		return true;
	}

	/**
	 * Get the plugin action links.
	 *
	 * @since 3.3.3
	 *
	 * @return array<string> The plugin action links.
	 */
	public function get_action_links(): array {
		$manage_modules_url = admin_url( 'admin.php?page=divi_squad_dashboard#/modules' );

		return array(
			sprintf(
				'<a href="%1$s" aria-label="%2$s">%2$s</a>',
				$manage_modules_url,
				esc_html__( 'Manage', 'squad-modules-for-divi' )
			),
		);
	}
}
