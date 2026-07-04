<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Custom Fields Base
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   3.1.0
 */

namespace DiviSquad\Base\DiviBuilder\Utils\Elements\CustomFields;

/**
 * Custom Fields Base Class
 *
 * @package DiviSquad
 * @since   3.1.0
 */
abstract class Processor implements ProcessorInterface {

	/**
	 * Supported post types.
	 *
	 * @var array
	 */
	protected $post_types = array();

	/**
	 * Blacklisted keys
	 *
	 * @var array Blacklisted keys that should be excluded from custom fields.
	 */
	protected $blacklisted_keys = array();

	/**
	 * Custom fields suffixes
	 *
	 * @var array Suffixes that should be excluded from custom fields.
	 */
	protected $excluded_suffixes = array();

	/**
	 * Custom fields prefixes
	 *
	 * @var array Prefixes that should be excluded from custom fields.
	 */
	protected $excluded_prefixes = array();

	/**
	 * Available custom fields.
	 *
	 * @var array List of available custom fields.
	 */
	protected $fields = array();

	/**
	 * Available custom fields with its value.
	 *
	 * @var array List of available custom fields.
	 */
	protected $custom_fields = array();

	/**
	 * Check if a field should be included based on various criteria.
	 *
	 * @param string $field_key The field key to check.
	 * @return bool Whether the field should be included.
	 */
	abstract protected function should_include_field( $field_key );

	/**
	 * Get the supported post types for the processor.
	 *
	 * @return array|string[]
	 */
	abstract protected function get_supported_post_types();

	/**
	 * Format a field name by replacing underscores and hyphens with spaces.
	 *
	 * @param string $field_key The field key to format.
	 * @return string The formatted field name.
	 */
	protected function format_field_name( $field_key ) {
		return str_replace( array( '_', '-' ), ' ', $field_key );
	}

	/**
	 * Get the value of a selected post meta key for a specific post, with additional options.
	 *
	 * @param int    $post_id The ID of the post.
	 * @param string $meta_key The meta key to retrieve.
	 * @param array  $options Additional options for retrieving the meta value.
	 * @return mixed The meta value if successful, default value if not found.
	 */
	public function get_field_value_advanced( $post_id, $meta_key, array $options = array() ) {
		$default_options = array(
			'single'            => true,
			'default'           => null,
			'sanitize_callback' => null,
		);

		$options = array_merge( $default_options, $options );

		$value = $this->get_field_value( $post_id, $meta_key, $options['default'] );

		if ( is_callable( $options['sanitize_callback'] ) ) {
			$value = call_user_func( $options['sanitize_callback'], $value );
		}

		return $value;
	}

	/**
	 * Collect custom fields types and generate a formatted array.
	 *
	 * @return array
	 */
	public function get_formatted_fields_types() {
		return array();
	}
}
