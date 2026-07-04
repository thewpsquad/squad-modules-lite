<?php // phpcs:ignore WordPress.Files.FileName

/**
 * The Interface for Extension.
 *
 * @since   3.4.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Extensions\Contracts;

/**
 * Extension Interface.
 *
 * @since   3.4.0
 * @package DiviSquad
 */
interface Extension_Interface {

	/**
	 * Get the extension name.
	 *
	 * @return string
	 */
	public function get_name(): string;

	/**
	 * Load the extension.
	 *
	 * @return void
	 */
	public function load(): void;
}
