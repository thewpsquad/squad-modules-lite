<?php // phpcs:ignore WordPress.Files.FileName

/**
 * WPForms Processor
 *
 * Handles the retrieval and processing of WPForms.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   3.1.0
 */

namespace DiviSquad\Base\DiviBuilder\Utils\Elements\Forms\Processors;

use DiviSquad\Base\DiviBuilder\Utils\Elements\Forms\Form;
use WP_Post;

/**
 * WPForms Processor
 *
 * Handles the retrieval and processing of WPForms.
 *
 * @package DiviSquad
 * @since   3.1.0
 */
class WPForms extends Form {

	/**
	 * Get WPForms.
	 *
	 * @param string $collection The type of data to collect ('id' or 'title').
	 *
	 * @return array An array of WPForms data.
	 */
	public function get_forms( string $collection ): array {
		if ( ! function_exists( '\wpforms' ) ) {
			return array();
		}

		// Get all WPForms.
		$forms = \wpforms()->form->get(
			'',
			array(
				'orderby' => 'id',
				'order'   => 'DESC',
			)
		);

		if ( empty( $forms ) ) {
			return array();
		}

		return $this->process_form_data( $forms, $collection );
	}

	/**
	 * Get the ID of a WPForm.
	 *
	 * @param WP_Post $form The form post object.
	 *
	 * @return int The form ID.
	 */
	protected function get_form_id( $form ): int {
		return $form->ID;
	}

	/**
	 * Get the title of a WPForm.
	 *
	 * @param WP_Post $form The form post object.
	 *
	 * @return string The form title.
	 */
	protected function get_form_title( $form ): string {
		return $form->post_title;
	}
}
