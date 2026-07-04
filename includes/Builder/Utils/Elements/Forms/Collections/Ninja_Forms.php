<?php // phpcs:ignore WordPress.Files.FileName

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
	 * @return array An array of Ninja Forms data.
	 */
	public function get_forms( string $collection ): array {
		// Check if Ninja Forms is active
		if ( ! function_exists( 'Ninja_Forms' ) ) {
			return array();
		}

		// Get all Ninja Forms
		$forms = \Ninja_Forms()->form()->get_forms();
		if ( ! is_array( $forms ) || count( $forms ) === 0 ) {
			return array();
		}

		return $this->process_form_data( $forms, $collection );
	}

	/**
	 * Get the ID of a Ninja Form.
	 *
	 * @param object $form The form object.
	 *
	 * @return int The form ID.
	 */
	protected function get_form_id( $form ): int {
		return (int) $form->get_id();
	}

	/**
	 * Get the title of a Ninja Form.
	 *
	 * @param object $form The form object.
	 *
	 * @return string The form title.
	 */
	protected function get_form_title( $form ): string {
		return $form->get_setting( 'title' );
	}
}
