<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Interface for the Branding.
 *
 * @since      3.0.0
 * @deprecated 3.3.3
 * @package    DiviSquad
 * @author     The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Base\Factories\BrandAsset;

/**
 * Branding Asset Interface.
 *
 * @since      3.0.0
 * @deprecated 3.3.3
 * @package    DiviSquad
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
