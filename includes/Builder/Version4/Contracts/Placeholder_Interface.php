<?php // phpcs:ignore WordPress.Files.FileName

/**
 * The Placeholder Interface for Divi Builder
 *
 * @since   1.0.0
 * @author  The WP Squad <support@squadmodules.com>
 * @package DiviSquad
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

	/**
	 * Filters backend data passed to the Visual Builder.
	 * This function is used to add static helpers whose content rarely changes.
	 * eg: google fonts, module default, and so on.
	 *
	 * @param array<string, array<string, mixed>> $exists Existing definitions.
	 *
	 * @return array<string, array<string, mixed>> Updated definitions.
	 */
	public function static_asset_definitions( array $exists = array() ): array;

	/**
	 * Used to update the content of the cached definitions js file.
	 *
	 * @param string $content content.
	 *
	 * @return string
	 */
	public function asset_definitions( string $content ): string;
}
