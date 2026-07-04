<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Forminator Forms Collection
 *
 * Handles the retrieval and processing of Forminator Forms.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   3.1.0
 */

namespace DiviSquad\Base\DiviBuilder\Utils\Elements\Forms\Processors;

use DiviSquad\Base\DiviBuilder\Utils\Elements\Forms\Form;
use Forminator_API;
use Forminator_Base_Form_Model;

/**
 * Forminator Forms Collection
 *
 * Handles the retrieval and processing of Forminator Forms.
 *
 * @package DiviSquad
 * @since 3.1.0
 */
class Forminator extends Form {

	/**
	 * Get Forminator Forms.
	 *
	 * @param string $collection The type of data to collect ('id' or 'title').
	 *
	 * @return array An array of Forminator Forms data.
	 */
	public function get_forms( string $collection ): array {
		if ( ! class_exists( '\Forminator_API' ) ) {
			return array();
		}

		// Get all Forminator Forms.
		$forms = Forminator_API::get_forms( null, 1, 999, Forminator_Base_Form_Model::STATUS_PUBLISH );

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
		return $form->id;
	}

	/**
	 * Get the title of a Forminator Form.
	 *
	 * @param object $form The form object.
	 *
	 * @return string The form title.
	 */
	protected function get_form_title( $form ): string {
		return \forminator_get_form_name( $form->id );
	}
}
