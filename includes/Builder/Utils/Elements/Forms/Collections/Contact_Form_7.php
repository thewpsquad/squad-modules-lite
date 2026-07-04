<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Contact Form 7 Collection
 *
 * Handles the retrieval and processing of Contact Form 7 forms.
 *
 * @since   3.1.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Utils\Elements\Forms\Collections;

use DiviSquad\Builder\Utils\Elements\Forms\Collection;
use WPCF7_ContactForm;

/**
 * Contact Form 7 Collection
 *
 * Handles the retrieval and processing of Contact Form 7 forms.
 *
 * @since   3.1.0
 * @package DiviSquad
 */
class Contact_Form_7 extends Collection {

	/**
	 * Get Contact Form 7 forms.
	 *
	 * @param string $collection The type of data to collect ('id' or 'title').
	 *
	 * @return array An array of Contact Form 7 data.
	 */
	public function get_forms( string $collection ): array {
		if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
			return array();
		}

		// Get all Contact Form 7 forms.
		$forms = WPCF7_ContactForm::find();

		if ( empty( $forms ) ) {
			return array();
		}

		return $this->process_form_data( $forms, $collection );
	}

	/**
	 * Get the ID of a Contact Form 7 form.
	 *
	 * @param WPCF7_ContactForm $form The form object.
	 *
	 * @return int The form ID.
	 */
	protected function get_form_id( $form ): int {
		return $form->id();
	}

	/**
	 * Get the title of a Contact Form 7 form.
	 *
	 * @param WPCF7_ContactForm $form The form object.
	 *
	 * @return string The form title.
	 */
	protected function get_form_title( $form ): string {
		return $form->title();
	}
}
