<?php // phpcs:ignore WordPress.Files.FileName

/**
 * The base class for Extension.
 *
 * @since   1.2.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Extensions\Abstracts;

use DiviSquad\Core\Memory;
use DiviSquad\Extensions\Contracts\Extension_Interface;

/**
 * Extension class.
 *
 * @since   1.2.0
 * @package DiviSquad
 */
abstract class Base_Extension implements Extension_Interface {
	/** The instance of memory.
	 *
	 * @var Memory
	 */
	protected Memory $memory;

	/**
	 * The list of inactive extensions.
	 *
	 * @var array<string, string>
	 */
	protected array $inactivates;

	/**
	 * The name list of extensions.
	 *
	 * @var array<string>
	 */
	protected array $name_lists;

	/**
	 * The constructor class.
	 */
	public function __construct() {
		$this->memory      = divi_squad()->memory;
		$this->inactivates = $this->memory->get( 'inactive_extensions', array() );
		$this->name_lists  = array_column( $this->inactivates, 'name' );

		// Verify the current extension, is in the allowed list.
		if ( ! in_array( $this->get_name(), $this->name_lists, true ) ) {
			$this->load();
		}
	}
}
