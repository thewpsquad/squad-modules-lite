<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Formidable Forms Collection
 *
 * Handles the retrieval and processing of Formidable Forms.
 *
 * @since   3.1.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Utils\Elements\Forms\Collections;

use DiviSquad\Builder\Utils\Elements\Forms\Collection;

/**
 * Formidable Forms Collection
 *
 * Handles the retrieval and processing of Formidable Forms.
 *
 * @since   3.1.0
 * @package DiviSquad
 */
class Formidable extends Collection {

	/**
	 * Get Formidable Forms.
	 *
	 * @param string $collection The type of data to collect ('id' or 'title').
	 *
	 * @return array<string, string> An array of Formidable Forms data.
	 */
	public function get_forms( string $collection ): array {
		// Check if Formidable Forms is active.
		if ( ! class_exists( 'FrmForm' ) ) {
			return array();
		}

		// Get all Formidable Forms.
		$forms = \FrmForm::get_published_forms();

		if ( ! is_array( $forms ) || 0 === count( $forms ) ) {
			return array();
		}

		return $this->process_form_data( $forms, $collection );
	}

	/**
	 * Get the ID of a Formidable Form.
	 *
	 * @param mixed $form The form object.
	 *
	 * @return int The form ID.
	 */
	protected function get_form_id( $form ): int {
		if ( is_object( $form ) && property_exists( $form, 'id' ) ) {
			return (int) $form->id;
		}

		return 0;
	}

	/**
	 * Get the title of a Formidable Form.
	 *
	 * @param mixed $form The form object.
	 *
	 * @return string The form title.
	 */
	protected function get_form_title( $form ): string {
		if ( is_object( $form ) && property_exists( $form, 'name' ) ) {
			return (string) $form->name;
		}

		return '';
	}
}
