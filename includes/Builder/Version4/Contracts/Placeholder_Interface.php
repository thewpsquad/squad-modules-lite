<?php // phpcs:ignore WordPress.Files.FileName

/**
 * The Placeholder Interface for Divi Builder
 *
 * @since   1.0.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Contracts;

/**
 * Interface for the Placeholder class.
 *
 * Defines the contract for placeholder content providers that
 * supply standardized content for Divi modules in the builder interface.
 * Implementations should ensure consistent appearance across modules.
 *
 * @since   1.0.0
 * @package DiviSquad
 */
interface Placeholder_Interface {

	/**
	 * Get the defaults data for modules.
	 *
	 * Provides a comprehensive set of placeholder content
	 * for various module types and elements, using Elegant Icon Font.
	 *
	 * @return array<string, mixed> Array of placeholder content
	 */
	public function get_modules_defaults(): array;
}
