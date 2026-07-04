<?php // phpcs:ignore WordPress.Files.FileName

/**
 * WordPress Custom Fields Processor
 *
 * This file contains the WordPress class which implements custom field
 * collection functionality for standard WordPress post meta.
 *
 * @since   3.1.0
 * @author  The WP Squad <support@squadmodules.com>
 * @package DiviSquad
 */

namespace DiviSquad\Builder\Utils\Elements\Custom_Fields\Collections;

use DiviSquad\Builder\Utils\Elements\Custom_Fields\Collection;
use Throwable;
use function apply_filters;
use function get_metadata;
use function get_post_meta;
use function metadata_exists;

/**
 * WordPress Custom Fields Processor Class
 *
 * Implements custom field collection for standard WordPress post meta fields
 * with filtering capabilities to exclude system and plugin fields.
 *
 * @since   3.1.0
 * @package DiviSquad
 */
class WordPress extends Collection {

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
	protected array $blacklisted_keys = array(
		'_edit_lock',
		'_edit_last',
		'_thumbnail_id',
		'_wp_page_template',
		'_wp_old_slug',
		'_wp_trash_meta_time',
		'_wp_trash_meta_status',
	);

	/**
	 * Suffixes that should be excluded from custom fields.
	 *
	 * @var array<string>
	 */
	protected array $excluded_suffixes = array(
		'active',
		'enabled',
		'disabled',
		'hidden',
		'flag',
	);

	/**
	 * Prefixes that should be excluded from custom fields.
	 *
	 * @var array<string, array<string>>
	 */
	protected array $excluded_prefixes = array(
		'wp'     => array(
			'_wp_',
			'wp_',
			'_oembed_',
		),
		'divi'   => array(
			'et_',
		),
		'yoast'  => array(
			'_yoast_',
			'yoast_',
			'_wpseo_',
		),
		'others' => array(
			'_aioseop_',
			'_elementor_',
			'rank_math_',
			'_acf_',
			'_wc_',
			'_transient_',
			'_site_transient_',
			'_menu_item_',
		),
	);

	/**
	 * Available custom formatted fields by post type.
	 *
	 * @var array<string, array<string, string>>
	 */
	protected array $formatted_fields = array();

	/**
	 * Available custom field values by post ID.
	 *
	 * @var array<int, array<object>>
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
		 * Filter the blacklisted keys.
		 *
		 * @since 3.1.0
		 *
		 * @param array<string> $blacklisted_keys The blacklisted keys.
		 */
		$this->blacklisted_keys = apply_filters(
			'divi_squad_wp_custom_fields_blacklist_init',
			$this->blacklisted_keys
		);

		/**
		 * Filter the excluded suffixes.
		 *
		 * @since 3.1.0
		 *
		 * @param array<string> $excluded_suffixes The excluded suffixes.
		 */
		$this->excluded_suffixes = apply_filters(
			'divi_squad_wp_custom_fields_excluded_suffixes_init',
			$this->excluded_suffixes
		);

		/**
		 * Filter the excluded prefixes.
		 *
		 * @since 3.1.0
		 *
		 * @param array<string, array<string>> $excluded_prefixes The excluded prefixes.
		 */
		$this->excluded_prefixes = apply_filters(
			'divi_squad_wp_custom_fields_excluded_prefixes_init',
			$this->excluded_prefixes
		);
	}

	/**
	 * Inform that the processor is eligible or not.
	 *
	 * WordPress custom fields are always eligible as they are part of core.
	 *
	 * @since 3.1.0
	 *
	 * @return bool Always returns true for WordPress custom fields.
	 */
	public function is_eligible(): bool {
		return true;
	}

	/**
	 * Collect custom fields and generate a formatted array.
	 *
	 * @since 3.1.0
	 *
	 * @return array<string, array<string, string>> An array where keys are post types and values are arrays
	 *                                              where keys are field identifiers and values are human-readable names.
	 */
	public function get_formatted_fields(): array {
		// Return cached fields if available
		if ( ! empty( $this->formatted_fields ) ) {
			return $this->formatted_fields;
		}

		try {
			$fields = $this->get_available_fields();

			foreach ( $fields as $post_type => $keys ) {
				// Skip unsupported post types
				if ( ! in_array( $post_type, $this->get_supported_post_types(), true ) ) {
					continue;
				}

				// Initialize post type array if needed
				if ( ! isset( $this->formatted_fields[ $post_type ] ) ) {
					$this->formatted_fields[ $post_type ] = array();
				}

				// Process each field key
				foreach ( $keys as $key ) {
					// Skip fields that should be excluded
					if ( ! $this->should_include_field( $key ) ) {
						continue;
					}

					// Format the field name for display
					$this->formatted_fields[ $post_type ][ $key ] = ucwords( $this->format_field_name( $key ) );
				}
			}

			/**
			 * Filter the formatted fields.
			 *
			 * @since 3.1.0
			 *
			 * @param array<string, array<string, string>> $formatted_fields The formatted fields.
			 */
			$this->formatted_fields = apply_filters(
				'divi_squad_wp_formatted_fields',
				$this->formatted_fields
			);

		} catch ( Throwable $e ) {
			// Log error but continue
			divi_squad()->log_error( $e, 'Error formatting WordPress custom fields' );
		}

		return $this->formatted_fields;
	}

	/**
	 * Get all custom fields for a specific post.
	 *
	 * @since 3.1.0
	 *
	 * @param int $post_id The ID of the post.
	 *
	 * @return array<string, mixed> An array of custom fields, where keys are field names and values are field values.
	 */
	public function get_fields( int $post_id ): array {
		// Return early if invalid post ID
		if ( $post_id <= 0 ) {
			return array();
		}

		// Return cached fields if available
		if ( isset( $this->custom_fields[ $post_id ] ) ) {
			return $this->custom_fields[ $post_id ];
		}

		// Initialize the custom fields array
		$this->custom_fields[ $post_id ] = array();

		try {
			// Get all available field values for this post
			$custom_field_values = $this->get_available_field_values( $post_id );

			// Process each field
			foreach ( $custom_field_values as $metadata ) {
				if ( empty( $metadata ) ) {
					continue;
				}

				// Skip fields that should be excluded
				if ( ! $this->should_include_field( $metadata->meta_key ) ) {
					continue;
				}

				// Add to custom fields
				$this->custom_fields[ $post_id ][ $metadata->meta_key ] = $metadata->meta_value;
			}

			/**
			 * Filter the retrieved custom fields.
			 *
			 * @since 3.1.0
			 *
			 * @param array<string, mixed> $custom_fields The custom fields.
			 * @param int                  $post_id       The post ID.
			 */
			$this->custom_fields[ $post_id ] = apply_filters(
				'divi_squad_wp_post_custom_fields',
				$this->custom_fields[ $post_id ],
				$post_id
			);

		} catch ( Throwable $e ) {
			// Log error but continue
			divi_squad()->log_error( $e, "Error retrieving WordPress custom fields for post $post_id" );
		}

		return $this->custom_fields[ $post_id ];
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

		// Check if the field exists in post meta
		$exists = metadata_exists( 'post', $post_id, $field_key );

		/**
		 * Filter whether a post has a specific custom field.
		 *
		 * @since 3.1.0
		 *
		 * @param bool   $exists    Whether the custom field exists.
		 * @param int    $post_id   The post ID.
		 * @param string $field_key The custom field key.
		 */
		return apply_filters( 'divi_squad_wp_has_custom_field', $exists, $post_id, $field_key );
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

		try {
			// Get the value from post meta
			$value = get_metadata( 'post', $post_id, $field_key, true );

			// Return the value if it exists, otherwise the default
			$result = ( '' !== $value ) ? $value : $default_value;

			/**
			 * Filter the retrieved custom field value.
			 *
			 * @since 3.1.0
			 *
			 * @param mixed  $result        The custom field value or default.
			 * @param int    $post_id       The post ID.
			 * @param string $field_key     The custom field key.
			 * @param mixed  $default_value The default value.
			 */
			return apply_filters(
				'divi_squad_wp_custom_field_value',
				$result,
				$post_id,
				$field_key,
				$default_value
			);

		} catch ( Throwable $e ) {
			// Log error and return default value
			divi_squad()->log_error( $e, "Error retrieving WordPress custom field '$field_key' for post $post_id" );

			return $default_value;
		}
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
		// Call parent first - this already checks empty, blacklisted keys, and excluded prefixes/suffixes
		if ( ! parent::should_include_field( $field_key ) ) {
			return false;
		}

		// Skip fields with underscore prefix (hidden WP fields) - WordPress specific check
		if ( str_starts_with( $field_key, '_' ) ) {
			return false;
		}

		/**
		 * Final filter specific to WordPress fields.
		 *
		 * @since 3.1.0
		 *
		 * @param bool   $include   Whether to include the field.
		 * @param string $field_key The field key.
		 */
		return apply_filters( 'divi_squad_wp_should_include_field', true, $field_key );
	}

	/**
	 * Collect available custom fields from the postmeta table.
	 *
	 * @since 3.1.0
	 *
	 * @return array<string, array<string>> An array of custom field keys by post type.
	 */
	protected function get_available_fields(): array {
		// Return cached fields if available
		if ( ! empty( $this->fields ) ) {
			return $this->fields;
		}

		/**
		 * Filters the number of custom fields to retrieve per post type.
		 *
		 * @since 3.1.0
		 *
		 * @param int $limit Number of custom fields to retrieve. Default 30.
		 */
		$limit = apply_filters( 'divi_squad_wp_custom_fields_limit', 30 );

		try {
			// Initialize fields array
			$this->fields = array();

			// Get supported post types
			$post_types = $this->get_supported_post_types();

			// Get fields manager
			$fields_manager = divi_squad()->custom_fields_element->get_manager( 'fields' );

			// Get fields for each post type
			foreach ( $post_types as $post_type ) {
				$this->fields[ $post_type ] = $fields_manager->get_data(
					array(
						'post_type' => $post_type,
						'limit'     => $limit,
					)
				);
			}

			/**
			 * Filter the available fields.
			 *
			 * @since 3.1.0
			 *
			 * @param array<string, array<string>> $fields     The available fields.
			 * @param array<string>                $post_types The post types.
			 * @param int                          $limit      The limit.
			 */
			$this->fields = apply_filters(
				'divi_squad_wp_available_fields',
				$this->fields,
				$post_types,
				$limit
			);

		} catch ( Throwable $e ) {
			// Log error but return empty array
			divi_squad()->log_error( $e, 'Error retrieving available WordPress custom fields' );

			return array();
		}

		return $this->fields;
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
	protected function get_available_field_values( int $post_id ): array {
		// Return cached values if available
		if ( isset( $this->field_values[ $post_id ] ) ) {
			return $this->field_values[ $post_id ];
		}

		// Generate cache key
		$cache_key = 'divi_squad_wp_field_values_' . $post_id;

		// Try to get from cache
		$cached_values = wp_cache_get( $cache_key, 'divi_squad_custom_fields' );
		if ( false !== $cached_values ) {
			$this->field_values[ $post_id ] = $cached_values;

			return $this->field_values[ $post_id ];
		}

		/**
		 * Filters the number of custom field values to retrieve.
		 *
		 * @since 3.1.0
		 *
		 * @param int $limit   Maximum number of values to retrieve. Default 30.
		 * @param int $post_id The post ID.
		 */
		$limit = apply_filters( 'divi_squad_wp_field_values_limit', 30, $post_id );

		try {
			// Get all custom fields
			$custom_fields        = $this->get_available_fields();
			$supported_post_types = $this->get_supported_post_types();

			// Initialize field values array
			$this->field_values[ $post_id ] = array();

			// Process each post type
			foreach ( $custom_fields as $post_type => $keys ) {
				// Skip unsupported post types
				if ( ! in_array( $post_type, $supported_post_types, true ) ) {
					continue;
				}

				// Get post meta values
				$values = $this->get_post_meta_values( $post_id, $keys, $limit );

				// Merge with existing values
				$this->field_values[ $post_id ] = array_merge(
					$this->field_values[ $post_id ],
					$values
				);
			}

			// Cache values for 1 hour
			wp_cache_set(
				$cache_key,
				$this->field_values[ $post_id ],
				'divi_squad_custom_fields',
				HOUR_IN_SECONDS
			);

			/**
			 * Filter the available field values.
			 *
			 * @since 3.1.0
			 *
			 * @param array<object> $field_values The field values.
			 * @param int           $post_id      The post ID.
			 */
			$this->field_values[ $post_id ] = apply_filters(
				'divi_squad_wp_available_field_values',
				$this->field_values[ $post_id ],
				$post_id
			);

		} catch ( Throwable $e ) {
			// Log error but return empty array
			divi_squad()->log_error( $e, "Error retrieving available field values for post $post_id" );

			return array();
		}

		return $this->field_values[ $post_id ];
	}

	/**
	 * Get post meta values for given keys.
	 *
	 * @since 3.1.0
	 *
	 * @param int           $post_id   The ID of the post.
	 * @param array<string> $meta_keys Array of meta keys to retrieve.
	 * @param int           $limit     Maximum number of results to return.
	 *
	 * @return array<object> An array of post meta value objects.
	 */
	private function get_post_meta_values( int $post_id, array $meta_keys, int $limit ): array {
		$values = array();

		try {
			// Process each meta key
			foreach ( $meta_keys as $key ) {
				// Skip invalid keys
				if ( empty( $key ) ) {
					continue;
				}

				// Get meta values for this key
				$meta_values = get_post_meta( $post_id, $key, false );

				// Process each value
				if ( ! empty( $meta_values ) ) {
					foreach ( $meta_values as $value ) {
						// Create metadata object
						$values[] = (object) array(
							'meta_key'   => $key,
							'meta_value' => $value,
						);

						// Stop if we've reached the limit
						if ( count( $values ) >= $limit ) {
							break 2;
						}
					}
				}
			}
		} catch ( Throwable $e ) {
			// Log error but continue
			divi_squad()->log_error( $e, "Error retrieving post meta values for post $post_id" );
		}

		/**
		 * Filter the post meta values.
		 *
		 * @since 3.1.0
		 *
		 * @param array<object> $values    The post meta values.
		 * @param int           $post_id   The post ID.
		 * @param array<string> $meta_keys The meta keys.
		 * @param int           $limit     The limit.
		 */
		return apply_filters(
			'divi_squad_wp_post_meta_values',
			$values,
			$post_id,
			$meta_keys,
			$limit
		);
	}

	/**
	 * Format a field value based on its type or key pattern.
	 *
	 * @since 3.1.0
	 *
	 * @param mixed  $value     The field value to format.
	 * @param string $field_key The field key.
	 *
	 * @return mixed The formatted value.
	 */
	protected function format_field_value( $value, string $field_key ) {
		// Handle serialized data
		if ( is_string( $value ) && is_serialized( $value ) ) {
			$unserialized = maybe_unserialize( $value );

			// If it's an array, format it nicely
			if ( is_array( $unserialized ) ) {
				return implode(
					', ',
					array_map(
						function ( $item ) {
							return is_scalar( $item ) ? (string) $item : 'complex value';
						},
						$unserialized
					)
				);
			}

			return $unserialized;
		}

		// Handle date fields
		if ( preg_match( '/date|time/i', $field_key ) && is_string( $value ) && strtotime( $value ) ) {
			return date_i18n( get_option( 'date_format' ), strtotime( $value ) );
		}

		/**
		 * Filter the formatted field value.
		 *
		 * @since 3.1.0
		 *
		 * @param mixed  $value     The field value.
		 * @param string $field_key The field key.
		 */
		return apply_filters( 'divi_squad_wp_format_field_value', $value, $field_key );
	}

	/**
	 * Clear cache for a specific post ID.
	 *
	 * @since 3.1.0
	 *
	 * @param int $post_id The post ID to clear cache for.
	 *
	 * @return void
	 */
	public function clear_post_cache( int $post_id ): void {
		// Clear from internal cache
		if ( isset( $this->field_values[ $post_id ] ) ) {
			unset( $this->field_values[ $post_id ] );
		}

		if ( isset( $this->custom_fields[ $post_id ] ) ) {
			unset( $this->custom_fields[ $post_id ] );
		}

		// Clear from WordPress object cache
		$cache_key = 'divi_squad_wp_field_values_' . $post_id;
		wp_cache_delete( $cache_key, 'divi_squad_custom_fields' );

		/**
		 * Action fired when the cache for a post is cleared.
		 *
		 * @since 3.1.0
		 *
		 * @param int $post_id The post ID.
		 */
		do_action( 'divi_squad_wp_post_cache_cleared', $post_id );
	}

	/**
	 * Clear the cache for all WordPress custom fields.
	 *
	 * @since 3.1.0
	 *
	 * @return void
	 */
	public function clear_cache(): void {
		// Call parent implementation
		parent::clear_cache();

		// Clear additional properties
		$this->formatted_fields = array();
		$this->field_values     = array();

		/**
		 * Action fired when the cache is cleared.
		 *
		 * @since 3.1.0
		 */
		do_action( 'divi_squad_wp_cache_cleared' );
	}
}
