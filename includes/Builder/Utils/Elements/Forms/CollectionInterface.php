<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Form Interface
 *
 * Interface for form processors.
 *
 * @since   3.1.0
 * @author  The WP Squad <support@squadmodules.com>
 * @package DiviSquad
 */

namespace DiviSquad\Builder\Utils\Elements\Forms;

/**
 * Form Interface
 *
 * Interface for form processors.
 *
 * @since   3.1.0
 * @package DiviSquad
 */
interface CollectionInterface {

	/**
	 * Get forms of a specific type.
	 *
	 * @param string $collection Either 'id' or 'title'.
	 *
	 * @return array<string, string> Associative array of form IDs or titles
	 */
	public function get_forms( string $collection ): array;
}
