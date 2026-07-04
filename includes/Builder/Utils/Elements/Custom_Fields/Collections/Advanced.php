<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Advanced Custom Fields Processor
 *
 * This file contains the Advanced class which implements custom field collection
 * functionality for Advanced Custom Fields (ACF) plugin integration.
 *
 * @since   3.1.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Utils\Elements\Custom_Fields\Collections;

use DiviSquad\Builder\Utils\Elements\Custom_Fields\Collection;
use Throwable;
use function get_post_meta;
use function get_post_type;
use function wp_get_attachment_image;

/**
 * Advanced Custom Fields Processor Class
 *
 * Implements custom field collection for ACF fields with specialized handling
 * for different field types (text, image, url, etc).
 *
 * @since   3.1.0
 * @package DiviSquad
 */
class Advanced extends Collection {

	/**
	 * Supported post types.
	 *
	 * @var array<string>
	 */
	protected array $post_types = array( 'post' );

	/**
	 * Blacklisted keys that should be excluded from custom fields.
	 *
	 * @var array<string>
	 */
	protected array $blacklisted_keys = array();

	/**
	 * Suffixes that should be excluded from custom fields.
	 *
	 * @var array<string>
	 */
	protected array $excluded_suffixes = array();

	/**
	 * Prefixes that should be excluded from custom fields.
	 *
	 * @var array<string>
	 */
	protected array $excluded_prefixes = array();

	/**
	 * Supported fields types from advanced custom fields.
	 *
	 * @var array<string>
	 */
	protected array $supported_field_types = array(
		'text',
		'number',
		'textarea',
		'range',
		'email',
		'url',
		'image',
		'select',
		'date_picker',
		'wysiwyg',
		'file',
		'gallery',
		'color_picker',
		'checkbox',
		'radio',
	);

	/**
	 * Available ACF field groups organized by post type.
	 *
	 * @var array<string, array>
	 */
	protected array $field_groups = array();

	/**
	 * Available ACF field data organized by group key.
	 *
	 * @var array<string, array>
	 */
	protected array $fields_data = array();

	/**
	 * Available custom field values organized by post ID.
	 *
	 * @var array<int, array>
	 */
	protected array $field_values = array();

	/**
	 * Initialize the collection.
	 *
	 * @since 3.1.0
	 *
	 * @return void
	 */
	protected function init(): void {
		/**
		 * Filter the supported field types for ACF integration.
		 *
		 * @since 3.1.0
		 *
		 * @param array<string> $supported_field_types The supported field types.
		 */
		$this->supported_field_types = apply_filters(
			'divi_squad_acf_supported_field_types',
			$this->supported_field_types
		);
	}

	/**
	 * Inform that the processor is eligible or not.
	 *
	 * @since 3.1.0
	 *
	 * @return bool Whether ACF plugin is available.
	 */
	public function is_eligible(): bool {
		$acf_available = function_exists( 'acf' ) || class_exists( 'ACF' );

		/**
		 * Filter whether the ACF processor is eligible to run.
		 *
		 * @since 3.1.0
		 *
		 * @param bool $acf_available Whether ACF is available.
		 */
		return apply_filters( 'divi_squad_acf_processor_eligible', $acf_available );
	}

	/**
	 * Collect available custom field values from the postmeta table for specific post.
	 *
	 * @since 3.1.0
	 *
	 * @param int $post_id The ID of the post.
	 *
	 * @return array<object> An array of custom field value objects.
	 */
	public function get_available_field_values( int $post_id ): array {
		// Return cached values if available
		if ( isset( $this->field_values[ $post_id ] ) ) {
			return $this->field_values[ $post_id ];
		}

		// Generate cache key
		$cache_key = 'divi_squad_acf_field_values_' . $post_id;

		// Try to get from cache
		$cached_values = wp_cache_get( $cache_key, 'divi_squad_custom_fields' );
		if ( false !== $cached_values ) {
			$this->field_values[ $post_id ] = $cached_values;

			return $this->field_values[ $post_id ];
		}

		/**
		 * Filter the number of ACF fields to retrieve.
		 *
		 * @since 3.1.0
		 *
		 * @param int $limit   Maximum number of fields to retrieve. Default 30.
		 * @param int $post_id The post ID.
		 */
		$limit = apply_filters( 'divi_squad_acf_fields_limit', 30, $post_id );

		// Get all custom fields
		$custom_fields                  = $this->get_formatted_fields();
		$this->field_values[ $post_id ] = array();

		// Get values for the current post type
		$post_type = get_post_type( $post_id );
		if ( isset( $custom_fields[ $post_type ] ) ) {
			$acf_field_keys                 = array_keys( $custom_fields[ $post_type ] );
			$this->field_values[ $post_id ] = $this->get_post_meta_values( $post_id, $acf_field_keys, $limit );
		}

		// Cache for 1 hour
		wp_cache_set( $cache_key, $this->field_values[ $post_id ], 'divi_squad_custom_fields', HOUR_IN_SECONDS );

		/**
		 * Action after retrieving available field values.
		 *
		 * @since 3.1.0
		 *
		 * @param array $field_values The retrieved field values.
		 * @param int   $post_id      The post ID.
		 */
		do_action( 'divi_squad_acf_field_values_retrieved', $this->field_values[ $post_id ], $post_id );

		return $this->field_values[ $post_id ];
	}

	/**
	 * Get post meta values for given keys.
	 *
	 * @since 3.1.0
	 *
	 * @param int           $post_id        The ID of the post.
	 * @param array<string> $acf_field_keys Array of ACF field keys to retrieve.
	 * @param int           $limit          Maximum number of results to return.
	 *
	 * @return array<object> An array of post meta value objects.
	 */
	private function get_post_meta_values( int $post_id, array $acf_field_keys, int $limit ): array {
		$values = array();

		// First try to use ACF functions if available
		if ( function_exists( 'get_field' ) ) {
			foreach ( $acf_field_keys as $key ) {
				try {
					$field_object = get_field_object( $key, $post_id );
					$meta_value   = get_field( $key, $post_id );

					// Only process non-empty values
					if ( ! empty( $meta_value ) ) {
						$field_type = $field_object['type'] ?? 'text';

						// Process the value based on field type
						$processed_value = $this->process_field_value( $meta_value, $field_type, $field_object );

						$values[] = (object) array(
							'meta_key'   => $key,
							'meta_value' => $processed_value,
							'field_type' => $field_type,
						);

						// Stop if we've reached the limit
						if ( count( $values ) >= $limit ) {
							break;
						}
					}
				} catch ( Throwable $e ) {
					// Fallback to regular post meta
					$meta_value = get_post_meta( $post_id, $key, true );
					if ( ! empty( $meta_value ) ) {
						$values[] = (object) array(
							'meta_key'   => $key,
							'meta_value' => $meta_value,
						);

						if ( count( $values ) >= $limit ) {
							break;
						}
					}
				}
			}
		} else {
			// Fallback to regular post meta
			foreach ( $acf_field_keys as $key ) {
				$meta_value = get_post_meta( $post_id, $key, true );
				if ( ! empty( $meta_value ) ) {
					$values[] = (object) array(
						'meta_key'   => $key,
						'meta_value' => $meta_value,
					);

					if ( count( $values ) >= $limit ) {
						break;
					}
				}
			}
		}

		/**
		 * Filter the retrieved post meta values.
		 *
		 * @since 3.1.0
		 *
		 * @param array $values         The retrieved values.
		 * @param int   $post_id        The post ID.
		 * @param array $acf_field_keys The field keys that were queried.
		 */
		return apply_filters( 'divi_squad_acf_post_meta_values', $values, $post_id, $acf_field_keys );
	}

	/**
	 * Process a field value based on its type.
	 *
	 * @since 3.1.0
	 *
	 * @param mixed  $value        The field value.
	 * @param string $field_type   The field type.
	 * @param array  $field_object The complete field object (optional).
	 *
	 * @return mixed The processed value.
	 */
	private function process_field_value( $value, string $field_type, array $field_object = array() ) {
		switch ( $field_type ) {
			case 'image':
				// Return full image HTML if it's an image field
				if ( is_numeric( $value ) ) {
					return wp_get_attachment_image( $value, 'full' );
				}

				// If it's an array with ID, use that
				if ( is_array( $value ) && isset( $value['ID'] ) ) {
					return wp_get_attachment_image( $value['ID'], 'full' );
				}
				break;

			case 'file':
				// Return file URL if it's a file field
				if ( is_numeric( $value ) ) {
					return wp_get_attachment_url( $value );
				}

				// If it's an array with URL, use that
				if ( is_array( $value ) && isset( $value['url'] ) ) {
					return $value['url'];
				}
				break;

			case 'gallery':
				// Return array of image IDs for gallery
				if ( is_array( $value ) ) {
					$images = array();
					foreach ( $value as $item ) {
						if ( is_numeric( $item ) ) {
							$images[] = wp_get_attachment_image( $item, 'full' );
						} elseif ( is_array( $item ) && isset( $item['ID'] ) ) {
							$images[] = wp_get_attachment_image( $item['ID'], 'full' );
						}
					}

					return implode( ' ', $images );
				}
				break;

			case 'select':
			case 'checkbox':
			case 'radio':
				// Format array values for select/checkbox
				if ( is_array( $value ) ) {
					return implode( ', ', $value );
				}
				break;
		}

		// Return the original value for other field types
		return $value;
	}

	/**
	 * Collect custom fields and generate a formatted array.
	 *
	 * @since 3.1.0
	 *
	 * @return array<string, array<string, string>> Array of formatted fields by post type.
	 */
	public function get_formatted_fields(): array {
		// If ACF isn't available, return empty array
		if ( ! function_exists( 'acf' ) ) {
			return array();
		}

		// If fields are already cached, return them
		if ( ! empty( $this->fields ) ) {
			return $this->fields;
		}

		// Get post types to process
		$post_types = $this->get_supported_post_types();

		// Initialize fields array if empty
		if ( empty( $this->fields ) ) {
			$this->fields = array();
		}

		// Process each post type
		foreach ( $post_types as $post_type ) {
			// Skip if already processed
			if ( isset( $this->fields[ $post_type ] ) ) {
				continue;
			}

			// Initialize post type fields
			$this->fields[ $post_type ] = array();

			try {
				// Get ACF field groups for this post type
				$this->field_groups[ $post_type ] = \acf_get_field_groups( array( 'post_type' => $post_type ) );

				// Process each field group
				foreach ( $this->field_groups[ $post_type ] as $group ) {
					// Skip if already processed
					if ( isset( $this->fields_data[ $group['key'] ] ) ) {
						continue;
					}

					// Get fields for this group
					$this->fields_data[ $group['key'] ] = \acf_get_fields( $group['key'] );

					// Process each field
					foreach ( $this->fields_data[ $group['key'] ] as $acf_field ) {
						// Skip unsupported field types
						if ( ! in_array( $acf_field['type'], $this->get_supported_field_types(), true ) ) {
							continue;
						}

						// Skip fields that should be excluded
						if ( ! $this->should_include_field( $acf_field['name'] ) ) {
							continue;
						}

						// Add field to formatted fields
						$this->fields[ $post_type ][ $acf_field['name'] ] = $acf_field['label'];
					}
				}
			} catch ( Throwable $e ) {
				// Log error but continue processing
				divi_squad()->log_error( $e, "Error processing ACF fields for post type: $post_type" );
			}
		}

		/**
		 * Filter the formatted fields.
		 *
		 * @since 3.1.0
		 *
		 * @param array $fields     The formatted fields.
		 * @param array $post_types The post types that were processed.
		 */
		return apply_filters( 'divi_squad_acf_formatted_fields', $this->fields, $post_types );
	}

	/**
	 * Collect custom fields types and generate a formatted array.
	 *
	 * @since 3.1.0
	 *
	 * @return array<string, string> Associative array of field type IDs and labels.
	 */
	public function get_formatted_fields_types(): array {
		$formatted_fields = array();

		foreach ( $this->get_supported_field_types() as $key ) {
			$formatted_fields[ $key ] = ucwords( $this->format_field_name( $key ) );
		}

		/**
		 * Filter the formatted field types.
		 *
		 * @since 3.1.0
		 *
		 * @param array $formatted_fields The formatted field types.
		 */
		return apply_filters( 'divi_squad_acf_formatted_field_types', $formatted_fields );
	}

	/**
	 * Get all custom fields for a specific post.
	 *
	 * @since 3.1.0
	 *
	 * @param int $post_id The ID of the post.
	 *
	 * @return array<string, mixed> An array of custom fields with their values.
	 */
	public function get_fields( int $post_id ): array {
		// Return early if invalid post ID
		if ( $post_id <= 0 ) {
			return array();
		}

		// Check if post type is supported
		$post_type = get_post_type( $post_id );
		if ( ! in_array( $post_type, $this->get_supported_post_types(), true ) ) {
			return array();
		}

		// Return cached fields if available
		if ( isset( $this->custom_fields[ $post_id ] ) ) {
			return $this->custom_fields[ $post_id ];
		}

		// Initialize custom fields array
		$this->custom_fields[ $post_id ] = array();

		// Process available field values
		$custom_field_values = $this->get_available_field_values( $post_id );

		foreach ( $custom_field_values as $metadata ) {
			if ( empty( $metadata ) ) {
				continue;
			}

			// Skip fields that should be excluded
			if ( ! $this->should_include_field( $metadata->meta_key ) ) {
				continue;
			}

			// Add field to custom fields
			$this->custom_fields[ $post_id ][ $metadata->meta_key ] = $metadata->meta_value;

			// Special handling for image fields
			if ( isset( $metadata->field_type ) && 'image' === $metadata->field_type ) {
				// Add image URLs for image fields
				if ( is_numeric( $metadata->meta_value ) ) {
					$image_url                                                       = wp_get_attachment_url( $metadata->meta_value );
					$this->custom_fields[ $post_id ][ $metadata->meta_key . '_url' ] = $image_url;
				}
			}
		}

		/**
		 * Filter the retrieved fields for a post.
		 *
		 * @since 3.1.0
		 *
		 * @param array $custom_fields The retrieved fields.
		 * @param int   $post_id       The post ID.
		 */
		return apply_filters( 'divi_squad_acf_post_fields', $this->custom_fields[ $post_id ], $post_id );
	}

	/**
	 * Check if a post has a specific custom field.
	 *
	 * @since 3.1.0
	 *
	 * @param int    $post_id   The ID of the post to check.
	 * @param string $field_key The key of the custom field to check for.
	 *
	 * @return bool True if the custom field exists, false otherwise.
	 */
	public function has_field( int $post_id, string $field_key ): bool {
		// Return early if invalid parameters
		if ( $post_id <= 0 || empty( $field_key ) ) {
			return false;
		}

		// Check if post type is supported
		$post_type = get_post_type( $post_id );
		if ( ! in_array( $post_type, $this->get_supported_post_types(), true ) ) {
			return false;
		}

		// Check if field exists using ACF function if available
		if ( function_exists( '\get_field' ) ) {
			$value = \get_field( $field_key, $post_id );

			return null !== $value && '' !== $value;
		}

		// Fallback to regular post meta
		return metadata_exists( 'post', $post_id, $field_key );
	}

	/**
	 * Get a specific custom field by post ID and field key.
	 *
	 * @since 3.1.0
	 *
	 * @param int    $post_id       The ID of the post to retrieve the custom field for.
	 * @param string $field_key     The key of the custom field to retrieve.
	 * @param mixed  $default_value The default value to return if the field is not found.
	 *
	 * @return mixed The value of the custom field, or the default value if not found.
	 */
	public function get_field_value( int $post_id, string $field_key, $default_value = null ) {
		// Return early if invalid parameters
		if ( $post_id <= 0 || empty( $field_key ) ) {
			return $default_value;
		}

		// Check if post type is supported
		$post_type = get_post_type( $post_id );
		if ( ! in_array( $post_type, $this->get_supported_post_types(), true ) ) {
			return $default_value;
		}

		// Get field value using ACF function if available
		if ( function_exists( 'get_field' ) ) {
			$value = get_field( $field_key, $post_id );

			// Process value based on field type
			if ( null !== $value && '' !== $value ) {
				try {
					$field_object = get_field_object( $field_key, $post_id );
					if ( $field_object && isset( $field_object['type'] ) ) {
						$value = $this->process_field_value( $value, $field_object['type'], $field_object );
					}
				} catch ( Throwable $e ) {
					// Ignore errors and use the original value
				}

				return $value;
			}
		}

		// Fallback to regular post meta
		$value = get_post_meta( $post_id, $field_key, true );

		return '' !== $value ? $value : $default_value;
	}

	/**
	 * Get supported field types.
	 *
	 * @since 3.1.0
	 *
	 * @return array<string> Array of supported field types.
	 */
	protected function get_supported_field_types(): array {
		return $this->supported_field_types;
	}

	/**
	 * Check if a field should be included based on various criteria.
	 *
	 * @since 3.1.0
	 *
	 * @param string $field_key The field key to check.
	 *
	 * @return bool Whether the field should be included.
	 */
	protected function should_include_field( string $field_key ): bool {
		// Call parent implementation first
		if ( ! parent::should_include_field( $field_key ) ) {
			return false;
		}

		/**
		 * Final filter specific to ACF fields.
		 *
		 * @since 3.1.0
		 *
		 * @param bool   $include   Whether to include the field.
		 * @param string $field_key The field key.
		 */
		return apply_filters( 'divi_squad_acf_should_include_field', true, $field_key );
	}

	/**
	 * Get the blacklisted keys.
	 *
	 * @since 3.1.0
	 *
	 * @return array<string> Array of blacklisted keys.
	 */
	protected function get_blacklisted_keys(): array {
		$keys = parent::get_blacklisted_keys();

		/**
		 * Filter the blacklisted keys for ACF fields.
		 *
		 * @since 3.1.0
		 *
		 * @param array $keys The blacklisted keys.
		 */
		return apply_filters( 'divi_squad_acf_blacklisted_keys', $keys );
	}

	/**
	 * Get the excluded suffixes.
	 *
	 * @since 3.1.0
	 *
	 * @return array<string> Array of excluded suffixes.
	 */
	protected function get_excluded_suffixes(): array {
		$suffixes = parent::get_excluded_suffixes();

		/**
		 * Filter the excluded suffixes for ACF fields.
		 *
		 * @since 3.1.0
		 *
		 * @param array $suffixes The excluded suffixes.
		 */
		return apply_filters( 'divi_squad_acf_excluded_suffixes', $suffixes );
	}

	/**
	 * Get the excluded prefixes.
	 *
	 * @since 3.1.0
	 *
	 * @return array<string, array<string>> Array of excluded prefixes.
	 */
	protected function get_excluded_prefixes(): array {
		$prefixes = parent::get_excluded_prefixes();

		/**
		 * Filter the excluded prefixes for ACF fields.
		 *
		 * @since 3.1.0
		 *
		 * @param array $prefixes The excluded prefixes.
		 */
		return apply_filters( 'divi_squad_acf_excluded_prefixes', $prefixes );
	}

	/**
	 * Clear the cache for this collection.
	 *
	 * @since 3.1.0
	 *
	 * @return void
	 */
	public function clear_cache(): void {
		parent::clear_cache();

		$this->field_groups = array();
		$this->fields_data  = array();
		$this->field_values = array();

		// Clear any ACF-specific caches
		// $cache_key_pattern = 'divi_squad_acf_field_values_';

		// Delete all cached values that start with our pattern
		wp_cache_flush();
	}
}
