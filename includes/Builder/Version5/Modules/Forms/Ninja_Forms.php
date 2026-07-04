<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Ninja Forms Styler Module (Divi 5 / Block API).
 *
 * Native Divi 5 form-styler for Ninja Forms. Embeds the selected Ninja Forms form via its
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
 * Ninja Forms Styler module class.
 *
 * @since 3.4.0
 */
class Ninja_Forms extends Form_Styler {

	/**
	 * Relative path to the generated module.json metadata folder.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/ninja-forms/';
	}

	protected static function get_root_classname(): string {
		return 'disq_form_styler_ninja_forms';
	}

	/**
	 * The Squad forms-element type key.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	protected static function get_form_type(): string {
		return 'ninja_forms';
	}

	/**
	 * Whether Ninja Forms is active.
	 *
	 * @since 3.4.0
	 *
	 * @return bool
	 */
	protected static function is_form_plugin_active(): bool {
		return function_exists( 'Ninja_Forms' );
	}

	/**
	 * Render the Ninja Forms form HTML for the given form id.
	 *
	 * @since 3.4.0
	 *
	 * @param string               $form_id Raw Ninja Forms form id.
	 * @param array<string, mixed> $inner   Form-group inner-content values.
	 *
	 * @return string
	 */
	protected static function get_form_html( string $form_id, array $inner ): string {
		/**
		 * Filter the Ninja Forms shortcode used to embed the selected form.
		 *
		 * @since 3.4.0
		 *
		 * @param string $shortcode The Ninja Forms shortcode.
		 * @param string $form_id   The selected form id.
		 */
		$shortcode = apply_filters(
			'divi_squad_ninja_forms_form_shortcode',
			sprintf( '[ninja_form id="%s"]', esc_attr( $form_id ) ),
			$form_id
		);

		return do_shortcode( $shortcode );
	}
}
