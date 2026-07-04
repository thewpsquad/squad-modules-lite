<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Admin Footer Text Asset
 *
 * This class handles the admin footer text on plugin pages.
 *
 * @since   3.3.3
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Core\Admin\Branding;

use DiviSquad\Core\Supports\Links;
use DiviSquad\Utils\Helper;
use Freemius_Exception;

/**
 * Admin Footer Text Asset
 *
 * @since   3.3.3
 * @package DiviSquad
 */
class Plugin_Admin_Footer_Text extends Brand_Asset_Base {

	/**
	 * The asset ID.
	 *
	 * @var string
	 */
	protected string $asset_id = 'admin-footer-text';

	/**
	 * Get the asset type.
	 *
	 * @since 3.3.3
	 *
	 * @return string The asset type.
	 */
	public function get_type(): string {
		return 'admin_footer_text';
	}

	/**
	 * Get the asset position.
	 *
	 * @since 3.3.3
	 *
	 * @return string The asset position.
	 */
	public function get_position(): string {
		return 'replace';
	}

	/**
	 * Check if the asset can be applied.
	 *
	 * @since 3.3.3
	 *
	 * @return bool Whether the asset can be applied.
	 */
	public function can_apply(): bool {
		return Helper::is_squad_page();
	}

	/**
	 * Get the admin footer text.
	 *
	 * @since 3.3.3
	 *
	 * @return string The admin footer text.
	 * @throws Freemius_Exception If the Freemius SDK is not initialized.
	 */
	public function get_admin_footer_text(): string {
		$footer_text = '';

		// Add support url
		if ( divi_squad_fs()->is_free_plan() ) {
			$footer_text .= sprintf(
				'<a target="_blank" href="%2$s">%1$s</a> | ',
				esc_html__( 'Contact Support', 'squad-modules-for-divi' ),
				esc_url( Links::SUPPORT_URL )
			);
		}

		// Add rating to the plugin
		$footer_text .= str_replace(
			array( '[stars]', '[wp.org]' ),
			array(
				sprintf( '<a target="_blank" href="%1$s">&#9733;&#9733;&#9733;&#9733;&#9733;</a>', esc_url( Links::RATTING_URL ) ),
				sprintf( '<a target="_blank" href="%1$s">%2$s</a>', esc_url( Links::WP_ORG_URL ), esc_html__( 'WordPress.org', 'squad-modules-for-divi' ) ),
			),
			esc_html__( 'Add your [stars] on [wp.org] to spread the love.', 'squad-modules-for-divi' )
		);

		return $footer_text;
	}

	/**
	 * Get the update footer text.
	 *
	 * @since 3.3.3
	 *
	 * @return string The update footer text.
	 * @throws Freemius_Exception If the Freemius SDK is not initialized.
	 */
	public function get_update_footer_text(): string {
		$content = '';

		// Add sponsor url
		if ( divi_squad_fs()->is_free_plan() ) {
			$donate_text  = esc_html__( 'Donate', 'squad-modules-for-divi' );
			$donate_style = 'margin-right: 5px; text-decoration: none';

			// Generate the sponsored link text
			$sponsored_link_text = sprintf(
				'<span class="dashicons dashicons-money-alt"></span> <span style="text-decoration: underline; text-underline-offset: 4px;">%1$s</span>',
				esc_html( $donate_text )
			);

			// Add the sponsored link
			$content .= sprintf(
				'<a class="divi-squad-link-footer divi-squad-sponsor-link" href="%2$s" title="%4$s" target="_blank" style="%3$s">%1$s</a> | ',
				wp_kses_post( $sponsored_link_text ),
				esc_url( Links::HOME_URL . '?utm_campaign=wporg&utm_source=wp_plugin_dashboard&utm_medium=rowmeta' ),
				esc_attr( $donate_style ),
				esc_html( $donate_text )
			);
		}

		// Generate the translation link text
		$translation_link_text = sprintf(
			'<span class="dashicons dashicons-translation"></span> <span style="text-decoration: underline; text-underline-offset: 4px;">%1$s</span>',
			esc_html__( 'Translate', 'squad-modules-for-divi' )
		);

		// Add translate link
		$content .= sprintf(
			'<a class="divi-squad-link-footer divi-squad-translations-link" href="%2$s" title="%3$s" target="_blank" style="margin-left: 5px; margin-right: 5px; text-decoration: none">%1$s</a> | ',
			wp_kses_post( $translation_link_text ),
			esc_url( Links::TRANSLATE_URL . '?utm_campaign=wporg&utm_source=wp_plugin_dashboard&utm_medium=rowmeta' ),
			esc_attr__( 'Help us with Translations for the Squad Modules project', 'squad-modules-for-divi' )
		);

		// Generate the version link text
		$version_link_text = sprintf(
			'<span style="text-decoration: underline; text-underline-offset: 4px;">%1$s: %2$s</span>',
			esc_html__( 'Version', 'squad-modules-for-divi' ),
			esc_attr( divi_squad()->get_version_dot() )
		);

		// Add version number
		$content .= sprintf(
			'<a class="divi-squad-link-footer divi-squad-version-link" href="%2$s%4$s" title="%3$s" target="_blank" style="margin-left: 5px; text-decoration: none">%1$s</a>',
			wp_kses_post( $version_link_text ),
			esc_url( admin_url( 'admin.php?page=divi_squad_dashboard#/whats-new/' ) ),
			esc_attr__( 'View what changed in this version', 'squad-modules-for-divi' ),
			esc_attr( divi_squad()->get_version_dot() )
		);

		return $content;
	}
}
