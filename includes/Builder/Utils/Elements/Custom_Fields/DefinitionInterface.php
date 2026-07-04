<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Defining custom field operations.
 *
 * This interface provides methods for retrieving various types of custom fields
 * and their associated properties for UI definition.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   3.1.0
 */

namespace DiviSquad\Builder\Utils\Elements\Custom_Fields;

/**
 * Interface for defining custom field operations.
 *
 * This interface provides methods for retrieving various types of custom fields
 * and their associated properties for UI rendering in the Divi Builder.
 *
 * @package DiviSquad
 * @since   3.1.0
 */
interface DefinitionInterface {

	/**
	 * Get common fields that are applicable across different post types.
	 *
	 * These fields will be available for all supported post types regardless
	 * of their individual configuration.
	 *
	 * @since 3.1.0
	 *
	 * @return array<string, array<string, mixed>> An array of common custom field definitions structured for Divi.
	 */
	public function get_common_fields(): array;

	/**
	 * Get an array of fields to display when no custom fields are available.
	 *
	 * These fields will be shown when the collection returns no fields.
	 *
	 * @since 3.1.0
	 *
	 * @return array<string, array<string, mixed>> An array of empty-state field definitions.
	 */
	public function get_empty_fields(): array;

	/**
	 * Get default fields for a specific post type.
	 *
	 * These fields are tailored to a specific post type based on available options.
	 *
	 * @since 3.1.0
	 *
	 * @param string $post_type The post type for which to retrieve default fields.
	 * @param array  $options   Additional options to customize the returned fields.
	 *
	 * @return array<string, array<string, mixed>> An array of default custom field definitions for the specified post type.
	 */
	public function get_default_fields( string $post_type, array $options ): array;

	/**
	 * Get associated fields that are related to specific field types.
	 *
	 * These fields provide additional configuration options for specific field types.
	 *
	 * @since 3.1.0
	 *
	 * @param array<string, string> $fields_types Mapping of field type identifiers to their human-readable names.
	 *
	 * @return array<string, array<string, mixed>> An array of associated custom field definitions.
	 */
	public function get_associated_fields( array $fields_types = array() ): array;

	/**
	 * Get fields to display when the field type is not eligible.
	 *
	 * For example, when ACF plugin is not active, these fields would explain that to the user.
	 *
	 * @since 3.1.0
	 *
	 * @return array<string, array<string, mixed>> An array of custom field definitions for ineligible state.
	 */
	public function get_not_eligible_fields(): array;

	/**
	 * Determines if this definition processor is eligible to provide field definitions.
	 *
	 * @since 3.1.0
	 *
	 * @return bool Whether this definition processor can be used.
	 */
	public function is_eligible(): bool;
}
