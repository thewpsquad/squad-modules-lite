<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Custom Field Collection Interface
 *
 * Defines the contract for custom field collection implementations.
 *
 * @since   3.1.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Utils\Elements\Custom_Fields;

/**
 * Custom Field Collection Interface
 *
 * This interface establishes the contract for all classes that collect and manage
 * custom fields from various sources (WordPress, ACF, etc).
 *
 * @since   3.1.0
 * @package DiviSquad
 */
interface CollectionInterface {

	/**
	 * Determines if this collection processor is eligible to run.
	 *
	 * For example, ACF fields processor would return false if ACF plugin is not active.
	 *
	 * @since 3.1.0
	 *
	 * @return bool Whether this collection processor can be used.
	 */
	public function is_eligible(): bool;

	/**
	 * Retrieves and formats a list of available custom fields.
	 *
	 * @since 3.1.0
	 *
	 * @return array<string, array<string, string>> An array where keys are post types and values are arrays
	 *                                              where keys are field identifiers and values are human-readable names.
	 */
	public function get_formatted_fields(): array;

	/**
	 * Retrieves all custom fields for a specific post.
	 *
	 * @since 3.1.0
	 *
	 * @param int $post_id The ID of the post.
	 *
	 * @return array<string, mixed> An array of custom fields, where keys are field names and values are field values.
	 */
	public function get_fields( int $post_id ): array;

	/**
	 * Determines if a post has a specific custom field.
	 *
	 * @since 3.1.0
	 *
	 * @param int    $post_id   The ID of the post to check.
	 * @param string $field_key The key of the custom field to check for.
	 *
	 * @return bool True if the custom field exists, false otherwise.
	 */
	public function has_field( int $post_id, string $field_key ): bool;

	/**
	 * Retrieves a specific custom field by post ID and field key.
	 *
	 * @since 3.1.0
	 *
	 * @param int    $post_id       The ID of the post to retrieve the custom field for.
	 * @param string $field_key     The key of the custom field to retrieve.
	 * @param mixed  $default_value The default value to return if the field is not found.
	 *
	 * @return mixed The value of the custom field, or the default value if not found.
	 */
	public function get_field_value( int $post_id, string $field_key, $default_value = null );

	/**
	 * Retrieves a field value with advanced options for filtering and processing.
	 *
	 * @since 3.1.0
	 *
	 * @param int    $post_id  The ID of the post.
	 * @param string $meta_key The meta key to retrieve.
	 * @param array  $options  Additional options for retrieving the meta value such as:
	 *                         - 'single' (bool): Whether to return a single value.
	 *                         - 'default' (mixed): Default value if field not found.
	 *                         - 'sanitize_callback' (callable): Function to sanitize the value.
	 *
	 * @return mixed The meta value if successful, default value if not found.
	 */
	public function get_field_value_advanced( int $post_id, string $meta_key, array $options = array() );

	/**
	 * Retrieves a list of available field types with their human-readable names.
	 *
	 * Used for dropdowns in the admin UI to select field types.
	 *
	 * @since 3.1.0
	 *
	 * @return array<string, string> Array where keys are field type identifiers and values are human-readable names.
	 */
	public function get_formatted_fields_types(): array;

	/**
	 * Gets the list of post types supported by this collection.
	 *
	 * @since 3.1.0
	 *
	 * @return string[] Array of post type names.
	 */
	public function get_supported_post_types(): array;
}
