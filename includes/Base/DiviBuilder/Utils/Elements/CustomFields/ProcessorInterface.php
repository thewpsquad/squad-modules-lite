<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Custom Field
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   3.1.0
 */

namespace DiviSquad\Base\DiviBuilder\Utils\Elements\CustomFields;

/**
 * Custom Field interface
 *
 * @package DiviSquad
 * @since   3.1.0
 */
interface ProcessorInterface {

	/**
	 * Inform that the processor is eligible or not.
	 *
	 * @return bool
	 */
	public function is_eligible();

	/**
	 * Collect custom fields and generate a formatted array.
	 *
	 * @return array An array where keys are original field names and values are formatted field names.
	 */
	public function get_formatted_fields();

	/**
	 * Get all custom fields for a specific post.
	 *
	 * @param int $post_id The ID of the post.
	 * @return array An array of custom fields, where keys are field names and values are field values.
	 */
	public function get_fields( $post_id );

	/**
	 * Check if a post has a specific custom field.
	 *
	 * @param int    $post_id The ID of the post to check.
	 * @param string $field_key The key of the custom field to check for.
	 * @return bool True if the custom field exists, false otherwise.
	 */
	public function has_field( $post_id, $field_key );

	/**
	 * Get a specific custom field by post ID and field key.
	 *
	 * @param int    $post_id The ID of the post to retrieve the custom field for.
	 * @param string $field_key The key of the custom field to retrieve.
	 * @param mixed  $default_value The default value to return if the field is not found.
	 * @return mixed The value of the custom field, or the default value if not found.
	 */
	public function get_field_value( $post_id, $field_key, $default_value = null );

	/**
	 * Get the value of a selected post meta key for a specific post, with additional options.
	 *
	 * @param int    $post_id The ID of the post.
	 * @param string $meta_key The meta key to retrieve.
	 * @param array  $options Additional options for retrieving the meta value.
	 * @return mixed The meta value if successful, default value if not found.
	 */
	public function get_field_value_advanced( $post_id, $meta_key, array $options = array() );
}
