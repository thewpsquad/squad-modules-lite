<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Brand Assets Trait
 *
 * @since   3.3.0
 * @package DiviSquad
 */

namespace DiviSquad\Core\Traits\Assets;

use DiviSquad\Utils\Divi as DiviUtil;
use Throwable;

/**
 * Brand Assets Management Trait
 *
 * @since   3.3.0
 * @package DiviSquad
 */
trait Brand_Assets {

	/**
	 * Output logo CSS for admin and builder areas
	 */
	public function output_logo_css(): void {
		try {
			$is_admin          = is_admin();
			$is_visual_builder = DiviUtil::is_fb_enabled() || DiviUtil::is_bfb_enabled() || DiviUtil::is_tb_admin_screen();

			if ( ! $is_admin && ! $is_visual_builder ) {
				return;
			}

			$logo_selectors = array();

			/**
			 * Add logo selectors for admin and builder areas
			 *
			 * @since 3.3.0
			 *
			 * @param bool $is_admin Whether in admin area
			 */
			if ( $is_admin && apply_filters( 'divi_squad_admin_menu_icon', false ) ) {
				$logo_selectors[] = '#toplevel_page_divi_squad_dashboard div.wp-menu-image:before';
				$logo_selectors[] = '#toplevel_page_divi_squad_dashboard div.wp-menu-image img';
			}

			/**
			 * Add logo selectors for visual builder
			 *
			 * @since 3.3.0
			 *
			 * @param bool $is_visual_builder Whether in visual builder
			 */
			if ( $is_visual_builder && apply_filters( 'divi_squad_module_folder_icon', true ) ) {
				$logo_selectors[] = '.et-fb-settings-options-tab.et-fb-modules-list ul li.et_fb_divi_squad_modules.et_pb_folder';
				$logo_selectors[] = '.et-fb-settings-options-tab.et-fb-modules-list ul li.et_fb_divi_squad_modules:before';
			}

			if ( count( $logo_selectors ) < 1 ) {
				return;
			}

			/**
			 * Filter logo selectors for CSS
			 *
			 * @param array<string> $logo_selectors    CSS selectors for logo
			 * @param bool          $is_admin          Whether in admin area
			 * @param bool          $is_visual_builder Whether in visual builder
			 */
			$logo_selectors = apply_filters( 'divi_squad_logo_selectors', $logo_selectors, $is_admin, $is_visual_builder );

			$squad_image = divi_squad()->get_asset_url( '/admin/images/logos/divi-squad-d-default.svg' );

			/**
			 * Filter logo image URL
			 *
			 * @param string $squad_image Logo image URL
			 */
			$squad_image = apply_filters( 'divi_squad_logo_image', $squad_image );

			printf(
				'<style id="divi_squad_admin_assets_backend">%s</style>',
				sprintf(
					'%s { --squad-brand-logo: url("%s"); }',
					esc_attr( implode( ', ', array_unique( $logo_selectors ) ) ),
					esc_url( $squad_image, array( 'http', 'https', 'data' ) )
				)
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to output logo CSS' );
		}
	}
}
