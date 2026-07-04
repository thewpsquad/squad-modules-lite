<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Gravity Forms Collection
 *
 * Handles the retrieval and processing of Gravity Forms.
 *
 * @since   3.1.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Utils\Elements\Forms\Collections;

use DiviSquad\Builder\Utils\Elements\Forms\Collection;

/**
 * Gravity Forms Collection
 *
 * Handles the retrieval and processing of Gravity Forms.
 *
 * @since   3.1.0
 * @package DiviSquad
 */
class GravityForms extends Collection {

	/**
	 * Get Gravity Forms.
	 *
	 * @param string $collection The type of data to collect ('id' or 'title').
	 *
	 * @return array An array of Gravity Forms data.
	 */
	public function get_forms( string $collection ): array {
		// Check if Gravity Forms is active
		if ( ! class_exists( 'GFAPI' ) ) {
			return array();
		}

		// Get all Gravity Forms
		$forms = \GFAPI::get_forms();

		if ( empty( $forms ) ) {
			return array();
		}

		return $this->process_form_data( $forms, $collection );
	}

	/**
	 * Get the ID of a Gravity Form.
	 *
	 * @param array $form The form array.
	 *
	 * @return int The form ID.
	 */
	protected function get_form_id( $form ): int {
		return (int) $form['id'];
	}

	/**
	 * Get the title of a Gravity Form.
	 *
	 * @param array $form The form array.
	 *
	 * @return string The form title.
	 */
	protected function get_form_title( $form ): string {
		return $form['title'];
	}
}
