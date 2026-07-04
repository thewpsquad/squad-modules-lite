<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Fluent Forms Styler Module (Divi 5 / Block API).
 *
 * Native Divi 5 form-styler for Fluent Forms. Embeds the selected Fluent Forms form via its
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
 * Fluent Forms Styler module class.
 *
 * @since 3.4.0
 */
class Fluent_Forms extends Form_Styler {

	/**
	 * Relative path to the generated module.json metadata folder.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/fluent-forms/';
	}

	protected static function get_root_classname(): string {
		return 'disq_form_styler_fluent_forms';
	}

	/**
	 * The Squad forms-element type key.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	protected static function get_form_type(): string {
		return 'fluent_forms';
	}

	/**
	 * Whether Fluent Forms is active.
	 *
	 * @since 3.4.0
	 *
	 * @return bool
	 */
	protected static function is_form_plugin_active(): bool {
		return function_exists( 'wpFluentForm' );
	}

	/**
	 * Render the Fluent Forms form HTML for the given form id.
	 *
	 * @since 3.4.0
	 *
	 * @param string               $form_id Raw Fluent Forms form id.
	 * @param array<string, mixed> $inner   Form-group inner-content values.
	 *
	 * @return string
	 */
	protected static function get_form_html( string $form_id, array $inner ): string {
		/**
		 * Filter the Fluent Forms shortcode used to embed the selected form.
		 *
		 * @since 3.4.0
		 *
		 * @param string $shortcode The Fluent Forms shortcode.
		 * @param string $form_id   The selected form id.
		 */
		$shortcode = apply_filters(
			'divi_squad_fluent_forms_form_shortcode',
			sprintf( '[fluentform id="%s"]', esc_attr( $form_id ) ),
			$form_id
		);

		return do_shortcode( $shortcode );
	}
}
