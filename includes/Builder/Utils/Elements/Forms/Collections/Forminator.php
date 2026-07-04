<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Forminator Forms Collection
 *
 * Handles the retrieval and processing of Forminator Forms.
 *
 * @since   3.1.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Utils\Elements\Forms\Collections;

use DiviSquad\Builder\Utils\Elements\Forms\Collection;

/**
 * Forminator Forms Collection
 *
 * Handles the retrieval and processing of Forminator Forms.
 *
 * @since   3.1.0
 * @package DiviSquad
 */
class Forminator extends Collection {

	/**
	 * Get Forminator Forms.
	 *
	 * @param string $collection The type of data to collect ('id' or 'title').
	 *
	 * @return array An array of Forminator Forms data.
	 */
	public function get_forms( string $collection ): array {
		// Check if Forminator is active
		if ( ! class_exists( 'Forminator_API' ) ) {
			return array();
		}

		// Get all Forminator Forms
		$forms = \Forminator_API::get_forms();

		if ( empty( $forms ) ) {
			return array();
		}

		return $this->process_form_data( $forms, $collection );
	}

	/**
	 * Get the ID of a Forminator Form.
	 *
	 * @param object $form The form object.
	 *
	 * @return int The form ID.
	 */
	protected function get_form_id( $form ): int {
		return (int) $form->id;
	}

	/**
	 * Get the title of a Forminator Form.
	 *
	 * @param object $form The form object.
	 *
	 * @return string The form title.
	 */
	protected function get_form_title( $form ): string {
		return $form->name;
	}
}
