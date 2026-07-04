<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Interface for the Branding.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   3.0.0
 */

namespace DiviSquad\Base\Factories\BrandAsset;

/**
 * Branding Asset Interface.
 *
 * @package DiviSquad
 * @since   3.0.0
 */
interface AssetInterface {

	/**
	 * The branding asset type.
	 *
	 * @return string
	 */
	public function get_type();

	/**
	 * The branding asset position.
	 *
	 * @return string
	 */
	public function get_position();
}
