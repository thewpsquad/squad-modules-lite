<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Plugin Row Meta Asset
 *
 * This class handles the plugin row meta links in the plugins page.
 *
 * @since   3.3.3
 * @author  The WP Squad <support@squadmodules.com>
 * @package DiviSquad\Core\Admin\Branding\Assets
 */

namespace DiviSquad\Core\Admin\Branding;

use DiviSquad\Core\Supports\Links;

/**
 * Plugin Row Meta Asset
 *
 * @since   3.3.3
 * @package DiviSquad\Core\Admin\Branding\Assets
 */
class Plugin_Row_Meta extends Brand_Asset_Base {

	/**
	 * The asset ID.
	 *
	 * @var string
	 */
	protected string $asset_id = 'plugin-row-meta';

	/**
	 * Get the asset type.
	 *
	 * @since 3.3.3
	 *
	 * @return string The asset type.
	 */
	public function get_type(): string {
		return 'plugin_row_actions';
	}

	/**
	 * Get the asset position.
	 *
	 * @since 3.3.3
	 *
	 * @return string The asset position.
	 */
	public function get_position(): string {
		return 'after';
	}

	/**
	 * Get the plugin row meta links.
	 *
	 * @since 3.3.3
	 *
	 * @return array<string> The plugin row meta links.
	 * @throws \Freemius_Exception If the Freemius SDK is not initialized.
	 */
	public function get_row_meta(): array {
		$links = array();

		// Add the rating link to the plugin row meta
		$links[] = sprintf(
			'<a href="%1$s" target="_blank" aria-label="%2$s">%2$s</a>',
			esc_url( Links::RATTING_URL ),
			esc_html__( 'Rate The Plugin', 'squad-modules-for-divi' )
		);

		// Add the support link to the plugin row meta
		$links[] = sprintf(
			'<a href="%1$s?utm_campaign=wporg&utm_source=wp_plugin_dashboard&utm_medium=rowmeta" target="_blank" aria-label="%2$s">%2$s</a>',
			esc_url( Links::SUPPORT_URL ),
			esc_html__( 'Support', 'squad-modules-for-divi' )
		);

		// Add the documentation link to the plugin row meta
		$links[] = sprintf(
			'<a href="%1$s?utm_campaign=wporg&utm_source=wp_plugin_dashboard&utm_medium=rowmeta" target="_blank" aria-label="%2$s">%2$s</a>',
			esc_url( Links::HOME_URL ),
			esc_html__( 'Documentation', 'squad-modules-for-divi' )
		);

		// Add the pricing link to the plugin row meta (only for free plans)
		if ( divi_squad_fs()->is_free_plan() ) {
			$links[] = sprintf(
				'<a href="%1$s?utm_campaign=wporg&utm_source=wp_plugin_dashboard&utm_medium=rowmeta" target="_blank" aria-label="%2$s">%2$s</a>',
				esc_url( Links::PRICING_URL ),
				esc_html__( 'Pricing', 'squad-modules-for-divi' )
			);
		}

		return $links;
	}
}
