<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Gravity Forms Styler Module (Divi 5 / Block API).
 *
 * Native Divi 5 form-styler for Gravity Forms. Embeds the selected Gravity form via the
 * `gravity_form()` function and lets Divi style the form, fields, labels, placeholder,
 * checkbox/radio, submit button and messages through the declarative style groups in
 * `module.json`.
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
use function gravity_form;
use function ob_get_clean;
use function ob_start;

/**
 * Gravity Forms Styler module class.
 *
 * @since 3.4.0
 */
class Gravity_Forms extends Form_Styler {

	/**
	 * Relative path to the generated module.json metadata folder.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/gravity-forms/';
	}

	protected static function get_root_classname(): string {
		return 'disq_form_styler_gravity_forms';
	}

	/**
	 * The Squad forms-element type key.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	protected static function get_form_type(): string {
		return 'gravity_forms';
	}

	/**
	 * Whether Gravity Forms is active.
	 *
	 * @since 3.4.0
	 *
	 * @return bool
	 */
	protected static function is_form_plugin_active(): bool {
		return function_exists( 'gravity_form' );
	}

	/**
	 * Render the Gravity Forms form HTML for the given form id.
	 *
	 * Gravity Forms is embedded through its `gravity_form()` function rather than a
	 * shortcode. Output is buffered so the returned markup can be wrapped by the module.
	 *
	 * @since 3.4.0
	 *
	 * @param string               $form_id Raw Gravity Forms form id.
	 * @param array<string, mixed> $inner   Form-group inner-content values.
	 *
	 * @return string
	 */
	protected static function get_form_html( string $form_id, array $inner ): string {
		if ( ! function_exists( 'gravity_form' ) ) {
			return '';
		}

		$display_title       = 'on' === ( $inner['formTitle'] ?? 'off' );
		$display_description = 'on' === ( $inner['formDescription'] ?? 'off' );
		$use_ajax            = 'on' === ( $inner['formAjax'] ?? 'off' );

		ob_start();
		gravity_form( $form_id, $display_title, $display_description, false, null, $use_ajax, 0, true );

		return (string) ob_get_clean();
	}
}
