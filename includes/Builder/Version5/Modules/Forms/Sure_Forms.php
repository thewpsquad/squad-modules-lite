<?php // phpcs:ignore WordPress.Files.FileName

/**
 * SureForms Styler Module (Divi 5 / Block API).
 *
 * Native Divi 5 form-styler for SureForms. Embeds the selected SureForms form via its
 * shortcode and lets Divi style the form, fields, labels, placeholder, checkbox/radio,
 * submit button and messages through the declarative style groups in `module.json`.
 *
 * @since   3.4.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version5\Modules\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

if ( ! class_exists( 'ET\Builder\Packages\Module\Module' ) ) {
	return;
}

use DiviSquad\Builder\Version5\Abstracts\Form_Styler;
use function do_shortcode;

/**
 * SureForms Styler module class.
 *
 * @since 3.4.0
 */
class Sure_Forms extends Form_Styler {

	/**
	 * Relative path to the generated module.json metadata folder.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/sureforms/';
	}

	protected static function get_root_classname(): string {
		return 'disq_form_styler_sureforms';
	}

	/**
	 * The Squad forms-element type key.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	protected static function get_form_type(): string {
		return 'sureforms';
	}

	/**
	 * Whether SureForms is active.
	 *
	 * @since 3.4.0
	 *
	 * @return bool
	 */
	protected static function is_form_plugin_active(): bool {
		return defined( 'SRFM_VER' );
	}

	/**
	 * Render the SureForms form HTML for the given form id.
	 *
	 * @since 3.4.0
	 *
	 * @param string               $form_id Raw SureForms form id.
	 * @param array<string, mixed> $inner   Form-group inner-content values.
	 *
	 * @return string
	 */
	protected static function get_form_html( string $form_id, array $inner ): string {
		/**
		 * Filter the SureForms shortcode used to embed the selected form.
		 *
		 * @since 3.4.0
		 *
		 * @param string $shortcode The SureForms shortcode.
		 * @param string $form_id   The selected form id.
		 */
		$shortcode = apply_filters(
			'divi_squad_sureforms_form_shortcode',
			sprintf( '[sureforms id="%s"]', esc_attr( $form_id ) ),
			$form_id
		);

		return do_shortcode( $shortcode );
	}
}
