<?php // phpcs:ignore WordPress.Files.FileName
/**
 * The plugin row-meta management class for the plugin dashboard at admin area.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   1.0.0
 */

namespace DiviSquad\Managers\Branding;

use DiviSquad\Base\Factories\BrandAsset\Asset;
use DiviSquad\Core\Supports\Links;
use function esc_html__;
use function esc_url;

/**
 * Plugin Row Meta class.
 *
 * @package DiviSquad
 * @since   1.0.0
 */
class PluginRowActions extends Asset {

	/**
	 * The branding type.
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'plugin_row_actions';
	}

	/**
	 * The branding position.
	 *
	 * @return string
	 */
	public function get_position(): string {
		return 'after';
	}

	/**
	 * The plugin row meta actions.
	 *
	 * @return  array
	 * @throws \Exception When the Freemius SDK is not loaded.
	 */
	public function get_row_actions(): array {
		$links = array();

		// Add the rating link to the plugin row meta.
		$links[] = sprintf(
			'<a href="%1$s" target="_blank" aria-label="%2$s">%2$s</a>',
			esc_url( Links::RATTING_URL ),
			esc_html__( 'Rate The Plugin', 'squad-modules-for-divi' )
		);

		// Add the support, documentation, and pricing links to the plugin row meta.
		$links[] = sprintf(
			'<a href="%1$s?utm_campaign=wporg&utm_source=wp_plugin_dashboard&utm_medium=rowmeta" target="_blank" aria-label="%2$s">%2$s</a>',
			esc_url( Links::SUPPORT_URL ),
			esc_html__( 'Support', 'squad-modules-for-divi' )
		);

		// Add the documentation link to the plugin row meta.
		$links[] = sprintf(
			'<a href="%1$s?utm_campaign=wporg&utm_source=wp_plugin_dashboard&utm_medium=rowmeta" target="_blank" aria-label="%2$s">%2$s</a>',
			esc_url( Links::HOME_URL ),
			esc_html__( 'Documentation', 'squad-modules-for-divi' )
		);

		// Add the pricing link to the plugin row meta.
		if ( divi_squad_fs() instanceof \Freemius && divi_squad_fs()->is_free_plan() ) {
			$links[] = sprintf(
				'<a href="%1$s?utm_campaign=wporg&utm_source=wp_plugin_dashboard&utm_medium=rowmeta" target="_blank" aria-label="%2$s">%2$s</a>',
				esc_url( Links::PRICING_URL ),
				esc_html__( 'Pricing', 'squad-modules-for-divi' )
			);
		}

		return $links;
	}
}
