<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Hookable Interface
 *
 * @since   3.4.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Core\Contracts;

/**
 * Hookable Interface
 *
 * This interface defines a contract for classes that need to register WordPress hooks
 * (actions and filters). Implementing classes must provide a method to register
 * their hooks with WordPress.
 *
 * @since   3.4.0
 * @package DiviSquad
 */
interface Hookable {

	/**
	 * Register hooks with WordPress.
	 *
	 * This method should contain all add_action() and add_filter() calls
	 * that connect class methods to WordPress hooks.
	 *
	 * @since 3.4.0
	 *
	 * @return void
	 */
	public function register_hooks(): void;
}
