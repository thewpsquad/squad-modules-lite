<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Builder Form Utils Helper Class
 *
 * @since   3.1.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Utils\Elements\Forms;

/**
 * Abstract class for form processing.
 *
 * @since   3.1.0
 * @package DiviSquad
 */
abstract class Collection implements CollectionInterface {

	/**
	 * Get the ID of a form.
	 *
	 * @param mixed $form Form object.
	 *
	 * @return int Form ID
	 */
	abstract protected function get_form_id( $form ): int;

	/**
	 * Get the title of a form.
	 *
	 * @param mixed $form Form object.
	 *
	 * @return string Form title
	 */
	abstract protected function get_form_title( $form ): string;

	/**
	 * Process form data into a consistent format.
	 *
	 * @param array  $forms      Array of form objects.
	 * @param string $collection Either 'id' or 'title'.
	 *
	 * @return array Processed form data
	 */
	protected function process_form_data( array $forms, string $collection ): array {
		$processed = array();
		foreach ( $forms as $form ) {
			// Get the form ID and title.
			$id    = $this->get_form_id( $form );
			$title = $this->get_form_title( $form );

			// Create a unique ID using the form id.
			$processed[ 'form_' . $id ] = 'title' === $collection ? $title : $id;
		}

		return $processed;
	}
}
