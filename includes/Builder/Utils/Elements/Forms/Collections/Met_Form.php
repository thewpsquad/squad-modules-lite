<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * MetForm Collection
 *
 * Handles the retrieval and processing of MetForm forms.
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
use function get_posts;

/**
 * MetForm Collection
 *
 * @since 3.4.0
 */
class Met_Form extends Collection {

	/**
	 * Get MetForm forms.
	 *
	 * @param string $collection The type of data to collect ('id' or 'title').
	 *
	 * @return array<string, string> An array of MetForm forms data.
	 */
	public function get_forms( string $collection ): array {
		// Check if MetForm is active.
		if ( ! class_exists( 'MetForm\Plugin' ) ) {
			return array();
		}

		$forms = get_posts(
			array(
				'post_type'        => 'metform-form',
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
	 * Get the ID of a MetForm form.
	 *
	 * @param mixed $form The form post object.
	 *
	 * @return int The form ID.
	 */
	protected function get_form_id( $form ): int {
		return is_object( $form ) && isset( $form->ID ) ? (int) $form->ID : 0;
	}

	/**
	 * Get the title of a MetForm form.
	 *
	 * @param mixed $form The form post object.
	 *
	 * @return string The form title.
	 */
	protected function get_form_title( $form ): string {
		return is_object( $form ) && isset( $form->post_title ) ? (string) $form->post_title : '';
	}
}
