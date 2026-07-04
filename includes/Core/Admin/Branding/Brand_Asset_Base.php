<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Base Branding Asset
 *
 * This abstract class implements common functionality for all branding assets.
 *
 * @since   3.3.3
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Core\Admin\Branding;

/**
 * Base Branding Asset
 *
 * @since   3.3.3
 * @package DiviSquad
 */
abstract class Brand_Asset_Base implements Brand_Asset_Interface {

	/**
	 * The asset ID.
	 *
	 * @var string
	 */
	protected string $asset_id = '';

	/**
	 * Get the asset ID.
	 *
	 * @since 3.3.3
	 *
	 * @return string The asset ID.
	 */
	public function get_asset_id(): string {
		return $this->asset_id;
	}

	/**
	 * Get the plugin base.
	 *
	 * @since 3.3.3
	 *
	 * @return string The plugin base.
	 */
	public function get_plugin_base(): string {
		return divi_squad()->get_basename();
	}

	/**
	 * Check if the asset is allowed in network admin.
	 *
	 * @since 3.3.3
	 *
	 * @return bool Whether the asset is allowed in network admin.
	 */
	public function is_allowed_in_network(): bool {
		return false;
	}

	/**
	 * Check if the asset can be applied.
	 *
	 * @since 3.3.3
	 *
	 * @return bool Whether the asset can be applied.
	 */
	public function can_apply(): bool {
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
		return array();
	}

	/**
	 * Get the plugin row meta links.
	 *
	 * @since 3.3.3
	 *
	 * @return array<string> The plugin row meta links.
	 */
	public function get_row_meta(): array {
		return array();
	}

	/**
	 * Get the admin footer text.
	 *
	 * @since 3.3.3
	 *
	 * @return string The admin footer text.
	 */
	public function get_admin_footer_text(): string {
		return '';
	}

	/**
	 * Get the update footer text.
	 *
	 * @since 3.3.3
	 *
	 * @return string The update footer text.
	 */
	public function get_update_footer_text(): string {
		return '';
	}

	/**
	 * Get the plugin menu icon.
	 *
	 * @since 3.3.3
	 *
	 * @return string The plugin menu icon.
	 */
	public function get_menu_icon(): string {
		return '';
	}

	/**
	 * Get the plugin custom CSS.
	 *
	 * @since 3.3.3
	 *
	 * @return string The plugin custom CSS.
	 */
	public function get_custom_css(): string {
		return '';
	}

	/**
	 * Get the asset type.
	 *
	 * @since 3.3.3
	 *
	 * @return string The asset type.
	 */
	abstract public function get_type(): string;

	/**
	 * Get the asset position.
	 *
	 * @since 3.3.3
	 *
	 * @return string The asset position (before, after, replace).
	 */
	abstract public function get_position(): string;
}
