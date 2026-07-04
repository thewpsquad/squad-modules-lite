<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * SureForms Collection
 *
 * Handles the retrieval and processing of SureForms forms.
 *
 * @since   3.4.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Utils\Elements\Forms\Collections;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use DiviSquad\Builder\Utils\Elements\Forms\Collection;
use WP_Post;
use function get_posts;

/**
 * SureForms Collection
 *
 * @since 3.4.0
 */
class Sure_Forms extends Collection {

	/**
	 * Get SureForms forms.
	 *
	 * @param string $collection The type of data to collect ('id' or 'title').
	 *
	 * @return array<string, string> An array of SureForms forms data.
	 */
	public function get_forms( string $collection ): array {
		// Check if SureForms is active.
		if ( ! defined( 'SRFM_VER' ) ) {
			return array();
		}

		$forms = get_posts(
			array(
				'post_type'        => 'sureforms_form',
				'post_status'      => 'publish',
				'numberposts'      => - 1,
				'suppress_filters' => false,
			)
		);

		if ( 0 === count( $forms ) ) {
			return array();
		}

		return $this->process_form_data( $forms, $collection );
	}

	/**
	 * Get the ID of a SureForms form.
	 *
	 * @param mixed $form The form object.
	 *
	 * @return int The form ID.
	 */
	protected function get_form_id( $form ): int {
		if ( ! $form instanceof WP_Post ) {
			return 0;
		}

		return $form->ID;
	}

	/**
	 * Get the title of a SureForms form.
	 *
	 * @param mixed $form The form object.
	 *
	 * @return string The form title.
	 */
	protected function get_form_title( $form ): string {
		if ( ! $form instanceof WP_Post ) {
			return '';
		}

		return $form->post_title;
	}
}
