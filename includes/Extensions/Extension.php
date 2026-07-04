<?php // phpcs:ignore WordPress.Files.FileName

/**
 * The base class for Extension.
 *
 * @since   1.2.0
 * @author  The WP Squad <support@squadmodules.com>
 * @package DiviSquad
 */

namespace DiviSquad\Extensions;

use DiviSquad\Core\Memory;

/**
 * Extension class.
 *
 * @since   1.2.0
 * @package DiviSquad
 */
abstract class Extension {

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

	/**
	 * Get the extension name.
	 *
	 * @return string
	 */
	abstract protected function get_name(): string;

	/**
	 * Load the extension.
	 *
	 * @return void
	 */
	abstract protected function load(): void;
}
