<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Gravity Forms Collection
 *
 * Handles the retrieval and processing of Gravity Forms.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since 3.1.0
 */

namespace DiviSquad\Base\DiviBuilder\Utils\Elements\Forms\Processors;

use DiviSquad\Base\DiviBuilder\Utils\Elements\Forms\Form;

/**
 * Gravity Forms Collection
 *
 * Handles the retrieval and processing of Gravity Forms.
 *
 * @package DiviSquad
 * @since   3.1.0
 */
class GravityForms extends Form {

	/**
	 * Get Gravity Forms.
	 *
	 * @param string $collection The type of data to collect ('id' or 'title').
	 *
	 * @return array An array of Gravity Forms data.
	 */
	public function get_forms( string $collection ): array {
		if ( ! class_exists( '\RGFormsModel' ) ) {
			return array();
		}

		// Get all Gravity Forms.
		$forms = \RGFormsModel::get_forms( null, 'title' );

		if ( empty( $forms ) ) {
			return array();
		}

		return $this->process_form_data( $forms, $collection );
	}

	/**
	 * Get the ID of a Gravity Form.
	 *
	 * @param object $form The form object.
	 *
	 * @return int The form ID.
	 */
	protected function get_form_id( $form ): int {
		return $form->id;
	}

	/**
	 * Get the title of a Gravity Form.
	 *
	 * @param object $form The form object.
	 *
	 * @return string The form title.
	 */
	protected function get_form_title( $form ): string {
		return $form->title;
	}
}
