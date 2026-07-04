<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Defining custom field operations.
 *
 * This interface provides methods for retrieving various types of custom fields
 * and their associated properties.
 *
 * @since      3.1.0
 * @deprecated 3.3.0
 * @package    DiviSquad
 * @author     The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Base\DiviBuilder\Utils\Elements\CustomFields;

/**
 * Interface for defining custom field operations.
 *
 * This interface provides methods for retrieving various types of custom fields
 * and their associated properties.
 *
 * @since      3.1.0
 * @deprecated 3.3.0
 * @package    DiviSquad
 */
interface DefinitionInterface {

	/**
	 * Get common fields that are applicable across different post types.
	 *
	 * This method should return an array of custom fields that are commonly used
	 * and applicable to multiple or all post types.
	 *
	 * @return array An array of common custom field definitions.
	 */
	public function get_common_fields();

	/**
	 * Get an array of empty fields.
	 *
	 * This method should return an array of custom fields that are considered "empty"
	 * or have no default value.
	 *
	 * @return array An array of empty custom field definitions.
	 */
	public function get_empty_fields();

	/**
	 * Get default fields for a specific post type.
	 *
	 * This method should return an array of default custom fields for the given post type,
	 * taking into account any provided options.
	 *
	 * @param string $post_type The post type for which to retrieve default fields.
	 * @param array  $options   Additional options to customize the returned fields.
	 *
	 * @return array An array of default custom field definitions for the specified post type.
	 */
	public function get_default_fields( $post_type, $options );

	/**
	 * Get associated fields.
	 *
	 * This method should return an array of custom fields that are associated
	 * with the current context or implementation.
	 *
	 * @param array $fields_types Collect custom fields types.
	 *
	 * @return array An array of associated custom field definitions.
	 */
	public function get_associated_fields( $fields_types = array() );

	/**
	 * Get fields that are not eligible.
	 *
	 * This method should return an array of custom fields that are considered
	 * not eligible for use in the current context or implementation.
	 *
	 * @return array An array of custom field definitions that are not eligible.
	 */
	public function get_not_eligible_fields();
}
