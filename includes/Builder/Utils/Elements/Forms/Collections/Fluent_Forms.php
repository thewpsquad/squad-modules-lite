<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Fluent Forms Collection
 *
 * Handles the retrieval and processing of Fluent Forms.
 *
 * @since   3.1.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Utils\Elements\Forms\Collections;

use DiviSquad\Builder\Utils\Elements\Forms\Collection;

/**
 * Fluent Forms Collection
 *
 * Handles the retrieval and processing of Fluent Forms.
 *
 * @since   3.1.0
 * @package DiviSquad
 */
class Fluent_Forms extends Collection {

	/**
	 * Get Fluent Forms.
	 *
	 * @param string $collection The type of data to collect ('id' or 'title').
	 *
	 * @return array An array of Fluent Forms data.
	 */
	public function get_forms( string $collection ): array {
		global $wpdb;

		$forms = array();

		// Check if Fluent Forms is active
		if ( ! defined( 'FLUENTFORM' ) ) {
			return $forms;
		}

		// Get all Fluent Forms
		$result = $wpdb->get_results(
			"SELECT id, title FROM {$wpdb->prefix}fluentform_forms"
		);

		if ( empty( $result ) ) {
			return $forms;
		}

		return $this->process_form_data( $result, $collection );
	}

	/**
	 * Get the ID of a Fluent Form.
	 *
	 * @param object $form The form object.
	 *
	 * @return int The form ID.
	 */
	protected function get_form_id( $form ): int {
		return (int) $form->id;
	}

	/**
	 * Get the title of a Fluent Form.
	 *
	 * @param object $form The form object.
	 *
	 * @return string The form title.
	 */
	protected function get_form_title( $form ): string {
		return $form->title;
	}
}
