<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Contact Form 7 Processor
 *
 * Handles the retrieval and processing of Fluent Forms.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   3.1.0
 */

namespace DiviSquad\Base\DiviBuilder\Utils\Elements\Forms\Processors;

use DiviSquad\Base\DiviBuilder\Utils\Elements\Forms\Form;
use WP_Post;

/**
 * Class for handling Contact Form 7 forms.
 *
 * @package DiviSquad
 * @since   3.1.0
 */
class ContactForm7 extends Form {

	/**
	 * Get Contact Form 7 forms.
	 *
	 * @param string $collection Either 'id' or 'title'.
	 *
	 * @return array Associative array of CF7 form IDs or titles
	 */
	public function get_forms( string $collection ): array {
		if ( ! class_exists( 'WPCF7' ) ) {
			return array();
		}

		// Get all CF7 forms.
		$forms = get_posts(
			array(
				'post_type'      => 'wpcf7_contact_form',
				'posts_per_page' => -1,
			)
		);

		if ( empty( $forms ) ) {
			return array();
		}

		return $this->process_form_data( $forms, $collection );
	}

	/**
	 * Get the ID of a CF7 form.
	 *
	 * @param WP_Post $form CF7 form object.
	 *
	 * @return int Form ID
	 */
	protected function get_form_id( $form ): int {
		return $form->ID;
	}

	/**
	 * Get the title of a CF7 form.
	 *
	 * @param WP_Post $form CF7 form object.
	 *
	 * @return string Form title
	 */
	protected function get_form_title( $form ): string {
		return $form->post_title;
	}
}
