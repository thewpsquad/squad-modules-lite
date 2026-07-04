<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Branding Asset Interface
 *
 * This interface defines the required methods for all branding assets.
 *
 * @since   3.3.3
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Core\Admin\Branding;

/**
 * Branding Asset Interface
 *
 * @since   3.3.3
 * @package DiviSquad
 */
interface Brand_Asset_Interface {

	/**
	 * Get the asset type.
	 *
	 * @since 3.3.3
	 *
	 * @return string The asset type.
	 */
	public function get_type(): string;

	/**
	 * Get the asset position.
	 *
	 * @since 3.3.3
	 *
	 * @return string The asset position (before, after, replace).
	 */
	public function get_position(): string;

	/**
	 * Get the asset ID.
	 *
	 * @since 3.3.3
	 *
	 * @return string The asset ID.
	 */
	public function get_asset_id(): string;

	/**
	 * Check if the asset can be applied.
	 *
	 * @since 3.3.3
	 *
	 * @return bool Whether the asset can be applied.
	 */
	public function can_apply(): bool;
}
