<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Abstract Collection Class for Custom Fields
 *
 * @since   3.1.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Utils\Elements\Custom_Fields;

use DiviSquad\Core\Supports\Polyfills\Str;

/**
 * Abstract Collection Class
 *
 * Provides a base implementation for custom field collection classes
 * with common utilities for field processing and filtering.
 *
 * @since   3.1.0
 * @package DiviSquad
 */
abstract class Collection implements CollectionInterface {

	/**
	 * Supported post types.
	 *
	 * @var string[]
	 */
	protected array $post_types = array();

	/**
	 * Blacklisted keys that should be excluded from custom fields.
	 *
	 * @var string[]
	 */
	protected array $blacklisted_keys = array();

	/**
	 * Suffixes that should be excluded from custom fields.
	 *
	 * @var string[]
	 */
	protected array $excluded_suffixes = array();

	/**
	 * Prefixes that should be excluded from custom fields.
	 *
	 * @var string[]
	 */
	protected array $excluded_prefixes = array();

	/**
	 * Available custom fields.
	 *
	 * @var array<string, array<string, string>>
	 */
	protected array $fields = array();

	/**
	 * Available custom fields with their values.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	protected array $custom_fields = array();

	/**
	 * Cache instance for better performance.
	 *
	 * @var array<string, mixed>
	 */
	protected array $cache = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Initialize the collection.
	 *
	 * @return void
	 */
	protected function init(): void {
		// Child classes can override this to perform initialization.
	}

	/**
	 * Format a field name by replacing underscores and hyphens with spaces.
	 *
	 * @param string $field_key The field key to format.
	 *
	 * @return string The formatted field name.
	 */
	protected function format_field_name( string $field_key ): string {
		$formatted = str_replace( array( '_', '-' ), ' ', $field_key );

		/**
		 * Filter the formatted field name.
		 *
		 * @since 3.1.0
		 *
		 * @param string $formatted The formatted field name.
		 * @param string $field_key The original field key.
		 */
		return apply_filters( 'divi_squad_formatted_field_name', ucwords( $formatted ), $field_key );
	}

	/**
	 * Get the value of a selected post meta key for a specific post, with additional options.
	 *
	 * @param int    $post_id  The ID of the post.
	 * @param string $meta_key The meta key to retrieve.
	 * @param array  $options  Additional options for retrieving the meta value.
	 *
	 * @return mixed The meta value if successful, default value if not found.
	 */
	public function get_field_value_advanced( int $post_id, string $meta_key, array $options = array() ) {
		$default_options = array(
			'single'            => true,
			'default'           => null,
			'sanitize_callback' => null,
			'filter'            => true,
		);

		$options = array_merge( $default_options, $options );

		$value = $this->get_field_value( $post_id, $meta_key, $options['default'] );

		if ( is_callable( $options['sanitize_callback'] ) ) {
			$value = call_user_func( $options['sanitize_callback'], $value );
		}

		if ( $options['filter'] ) {
			/**
			 * Filter the field value.
			 *
			 * @since 3.1.0
			 *
			 * @param mixed  $value    The field value.
			 * @param int    $post_id  The post ID.
			 * @param string $meta_key The meta key.
			 * @param array  $options  The options used to retrieve the value.
			 */
			$value = apply_filters( 'divi_squad_field_value', $value, $post_id, $meta_key, $options );
		}

		return $value;
	}

	/**
	 * Get supported post types.
	 *
	 * @return string[] Array of post type names.
	 */
	public function get_supported_post_types(): array {
		/**
		 * Filter the supported post types for this collection.
		 *
		 * @since 3.1.0
		 *
		 * @param string[] $post_types The supported post types.
		 */
		return apply_filters( 'divi_squad_collection_post_types', $this->post_types );
	}

	/**
	 * Collect custom fields types and generate a formatted array.
	 *
	 * @return array<string, string> Array of field types and their labels.
	 */
	public function get_formatted_fields_types(): array {
		return array();
	}

	/**
	 * Check if a field should be included based on various criteria.
	 *
	 * @param string $field_key The field key to check.
	 *
	 * @return bool Whether the field should be included.
	 */
	protected function should_include_field( string $field_key ): bool {
		if ( empty( $field_key ) ) {
			return false;
		}

		// Check if field is in blacklist.
		$blacklisted_keys = $this->get_blacklisted_keys();
		if ( in_array( $field_key, $blacklisted_keys, true ) ) {
			return false;
		}

		// Check if field has excluded suffix.
		$excluded_suffixes = $this->get_excluded_suffixes();
		foreach ( $excluded_suffixes as $suffix ) {
			if ( Str::ends_with( $field_key, $suffix ) ) {
				return false;
			}
		}

		// Check if field has excluded prefix.
		$excluded_prefixes = $this->get_excluded_prefixes();
		$all_prefixes      = array_merge( ...array_values( $excluded_prefixes ) );
		foreach ( $all_prefixes as $prefix ) {
			if ( Str::starts_with( $field_key, $prefix ) ) {
				return false;
			}
		}

		/**
		 * Final filter to determine if a field should be included.
		 *
		 * @since 3.1.0
		 *
		 * @param bool   $include   Whether to include the field.
		 * @param string $field_key The field key.
		 */
		return apply_filters( 'divi_squad_should_include_field', true, $field_key );
	}

	/**
	 * Get the blacklisted keys.
	 *
	 * @return string[] Array of blacklisted keys.
	 */
	protected function get_blacklisted_keys(): array {
		/**
		 * Filter the blacklisted keys.
		 *
		 * @since 3.1.0
		 *
		 * @param string[] $blacklisted_keys The blacklisted keys.
		 */
		return apply_filters( 'divi_squad_blacklisted_keys', $this->blacklisted_keys );
	}

	/**
	 * Get the excluded suffixes.
	 *
	 * @return string[] Array of excluded suffixes.
	 */
	protected function get_excluded_suffixes(): array {
		/**
		 * Filter the excluded suffixes.
		 *
		 * @since 3.1.0
		 *
		 * @param string[] $excluded_suffixes The excluded suffixes.
		 */
		return apply_filters( 'divi_squad_excluded_suffixes', $this->excluded_suffixes );
	}

	/**
	 * Get the excluded prefixes.
	 *
	 * @return array<string, array<string>> Array of excluded prefixes.
	 */
	protected function get_excluded_prefixes(): array {
		/**
		 * Filter the excluded prefixes.
		 *
		 * @since 3.1.0
		 *
		 * @param array<string, array<string>> $excluded_prefixes The excluded prefixes.
		 */
		return apply_filters( 'divi_squad_excluded_prefixes', $this->excluded_prefixes );
	}

	/**
	 * Clear the cache for this collection.
	 *
	 * @return void
	 */
	public function clear_cache(): void {
		$this->cache         = array();
		$this->fields        = array();
		$this->custom_fields = array();
	}
}
