<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Builder Form Utils Helper Class
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   3.1.0
 */

namespace DiviSquad\Base\DiviBuilder\Utils\Elements\Forms;

/**
 * Abstract class for form processing.
 *
 * @package DiviSquad
 * @since   3.1.0
 */
abstract class Form implements FormInterface {

	/**
	 * Get the ID of a form.
	 *
	 * @param mixed $form Form object.
	 * @return int Form ID
	 */
	abstract protected function get_form_id( $form ): int;

	/**
	 * Get the title of a form.
	 *
	 * @param mixed $form Form object.
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

			// Hash the ID to prevent conflicts.
			$hash_id               = hash( 'sha256', (string) $id );
			$processed[ $hash_id ] = 'title' === $collection ? $title : $id;
		}
		return $processed;
	}
}
