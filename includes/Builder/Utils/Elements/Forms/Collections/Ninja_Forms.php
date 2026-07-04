<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Ninja Forms Collection
 *
 * Handles the retrieval and processing of Ninja Forms.
 *
 * @since   3.1.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Utils\Elements\Forms\Collections;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use DiviSquad\Builder\Utils\Elements\Forms\Collection;

/**
 * Ninja Forms Collection
 *
 * Handles the retrieval and processing of Ninja Forms.
 *
 * @since   3.1.0
 * @package DiviSquad
 */
class Ninja_Forms extends Collection {

	/**
	 * Get Ninja Forms.
	 *
	 * @param string $collection The type of data to collect ('id' or 'title').
	 *
	 * @return array<string, string> An array of Ninja Forms data.
	 */
	public function get_forms( string $collection ): array {
		// Check if Ninja Forms is active.
		if ( ! function_exists( 'Ninja_Forms' ) ) {
			return array();
		}

		// Get all Ninja Forms.
		$forms = \Ninja_Forms()->form()->get_forms();
		if ( ! is_array( $forms ) || count( $forms ) === 0 ) {
			return array();
		}

		return $this->process_form_data( $forms, $collection );
	}

	/**
	 * Get the ID of a Ninja Form.
	 *
	 * @param mixed $form The form object.
	 *
	 * @return int The form ID.
	 */
	protected function get_form_id( $form ): int {
		if ( is_object( $form ) && method_exists( $form, 'get_id' ) ) {
			return (int) $form->get_id();
		}

		return 0;
	}

	/**
	 * Get the title of a Ninja Form.
	 *
	 * @param mixed $form The form object.
	 *
	 * @return string The form title.
	 */
	protected function get_form_title( $form ): string {
		if ( is_object( $form ) && method_exists( $form, 'get_setting' ) ) {
			return (string) $form->get_setting( 'title' );
		}

		return '';
	}
}
