<?php // phpcs:ignore WordPress.Files.FileName
/**
 * Form Interface
 *
 * Interface for form processors.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   3.1.0
 */

namespace DiviSquad\Base\DiviBuilder\Utils\Elements\Forms;

/**
 * Form Interface
 *
 * Interface for form processors.
 *
 * @package DiviSquad
 * @since   3.1.0
 */
interface FormInterface {

	/**
	 * Get forms of a specific type.
	 *
	 * @param string $collection Either 'id' or 'title'.
	 *
	 * @return array Associative array of form IDs or titles
	 */
	public function get_forms( string $collection ): array;
}
