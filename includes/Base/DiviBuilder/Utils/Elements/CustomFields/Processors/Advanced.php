<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Custom Fields (Advanced Custom Field) element.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   3.1.0
 */

namespace DiviSquad\Base\DiviBuilder\Utils\Elements\CustomFields\Processors;

use DiviSquad\Base\DiviBuilder\Utils\Elements\CustomFields;
use DiviSquad\Base\DiviBuilder\Utils\Elements\CustomFields\Processor;
use DiviSquad\Core\Supports\Polyfills\Str;
use function get_post_meta;
use function get_post_type;

/**
 * Custom Fields (Advanced Custom Field) element Class
 *
 * @package DiviSquad
 * @since   3.1.0
 */
class Advanced extends Processor {

	/**
	 * Supported post types.
	 *
	 * @var array<string>
	 */
	protected $post_types = array( 'post' );

	/**
	 * Blacklisted keys that should be excluded from custom fields.
	 *
	 * @var array<string>
	 */
	protected $blacklisted_keys = array();

	/**
	 * Suffixes that should be excluded from custom fields.
	 *
	 * @var array<string>
	 */
	protected $excluded_suffixes = array();

	/**
	 * Prefixes that should be excluded from custom fields.
	 *
	 * @var array<string>
	 */
	protected $excluded_prefixes = array();

	/**
	 * Supported fields types from advanced custom fields.
	 *
	 * @var array<string>
	 */
	protected $supported_field_types = array(
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
	);

	/**
	 * Available custom fields group
	 *
	 * @var array
	 */
	protected $field_groups = array();

	/**
	 * Available custom fields
	 *
	 * @var array
	 */
	protected $fields_data = array();

	/**
	 * Available custom field values
	 *
	 * @var array
	 */
	protected $field_values = array();

	/**
	 * Inform that the processor is eligible or not.
	 *
	 * @return bool
	 */
	public function is_eligible() {
		return function_exists( 'acf' ) || class_exists( 'ACF' );
	}

	/**
	 * Collect available custom field values from the postmeta table for specific post.
	 *
	 * @param int $post_id The ID of the post.
	 *
	 * @return array An array of unique custom field values.
	 */
	public function get_available_field_values( $post_id ) {
		if ( isset( $this->field_values[ $post_id ] ) ) {
			return $this->field_values[ $post_id ];
		}

		/**
		 * Filters the number of custom fields to retrieve.
		 *
		 * @since 3.1.0
		 *
		 * @param int $limit Number of custom fields to retrieve. Default 30.
		 */
		$limit = apply_filters( 'divi_squad_postmeta_form_limit', 30 );

		$cache_key     = 'divi_squad_advanced_field_values_' . $post_id;
		$cached_values = wp_cache_get( $cache_key, 'divi_squad_custom_fields' );

		if ( false !== $cached_values ) {
			$this->field_values[ $post_id ] = $cached_values;
			return $this->field_values[ $post_id ];
		}

		$custom_fields                  = $this->get_formatted_fields();
		$this->field_values[ $post_id ] = array();

		foreach ( $custom_fields as $post_type => $acf_fields ) {
			if ( 'post' !== $post_type ) {
				continue;
			}

			$acf_field_keys                 = array_keys( $acf_fields );
			$this->field_values[ $post_id ] = $this->get_post_meta_values( $post_id, $acf_field_keys, $limit );
		}

		wp_cache_set( $cache_key, $this->field_values[ $post_id ], 'divi_squad_custom_fields', 3600 ); // Cache for 1 hour.

		return $this->field_values[ $post_id ];
	}

	/**
	 * Get post meta values for given keys.
	 *
	 * @param int   $post_id        The ID of the post.
	 * @param array $acf_field_keys Array of ACF field keys to retrieve.
	 * @param int   $limit          Maximum number of results to return.
	 *
	 * @return array An array of post meta values.
	 */
	private function get_post_meta_values( $post_id, $acf_field_keys, $limit ) {
		$values = array();
		foreach ( $acf_field_keys as $key ) {
			$meta_value = get_post_meta( $post_id, $key, true );
			if ( ! empty( $meta_value ) ) {
				$values[] = (object) array(
					'meta_key'   => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_value' => $meta_value, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				);
				if ( count( $values ) >= $limit ) {
					break;
				}
			}
		}
		return $values;
	}

	/**
	 * Collect custom fields and generate a formatted array.
	 *
	 * @return array
	 */
	public function get_formatted_fields() {
		if ( ! function_exists( 'acf' ) ) {
			return array();
		}

		$post_types = $this->get_supported_post_types();
		foreach ( $post_types as $post_type ) {
			if ( isset( $this->field_groups[ $post_type ] ) ) {
				continue;
			}

			$this->field_groups[ $post_type ] = \acf_get_field_groups( array( 'post_type' => $post_type ) );

			foreach ( $this->field_groups[ $post_type ] as $group ) {
				if ( isset( $this->fields_data[ $group['key'] ] ) ) {
					continue;
				}

				$this->fields_data[ $group['key'] ] = \acf_get_fields( $group['key'] );

				foreach ( $this->fields_data[ $group['key'] ] as $acf_field ) {
					if ( ! in_array( $acf_field['type'], $this->get_supported_field_types(), true ) ) {
						continue;
					}

					$this->fields[ $post_type ][ $acf_field['name'] ] = $acf_field['label'];
				}
			}
		}

		return $this->fields;
	}

	/**
	 * Collect custom fields types and generate a formatted array.
	 *
	 * @return array
	 */
	public function get_formatted_fields_types() {
		$keys             = $this->get_supported_field_types();
		$formatted_fields = array();

		foreach ( $keys as $key ) {
			$formatted_fields[ $key ] = ucwords( $this->format_field_name( $key ) );
		}

		return $formatted_fields;
	}

	/**
	 * Get all custom fields for a specific post.
	 *
	 * @param int $post_id The ID of the post.
	 * @return array An array of custom fields, where keys are field names and values are field values.
	 */
	public function get_fields( $post_id ) {
		if ( $post_id <= 0 ) {
			return array();
		}

		$post_type = get_post_type( $post_id );
		if ( ! in_array( $post_type, $this->get_supported_post_types(), true ) ) {
			return array();
		}

		if ( isset( $this->custom_fields[ $post_id ] ) ) {
			return $this->custom_fields[ $post_id ];
		}

		// Initialize custom fields array.
		$this->custom_fields[ $post_id ] = array();

		$custom_field_values = $this->get_available_field_values( $post_id );
		foreach ( $custom_field_values as $metadata ) {
			if ( empty( $metadata ) ) {
				continue;
			}

			foreach ( $metadata as $meta ) {
				if ( ! $this->should_include_field( $meta->meta_key ) ) {
					continue;
				}

				$this->custom_fields[ $post_id ][ $meta->meta_key ] = $meta->meta_value;

				// Get image source.
				foreach ( $this->fields_data as $acf_fields ) {
					foreach ( $acf_fields as $acf_field ) {
						if ( 'image' !== $acf_field['type'] ) {
							continue;
						}

						if ( $meta->meta_key !== $acf_field['name'] ) {
							continue;
						}

						$this->custom_fields[ $post_id ][ $meta->meta_key ] = wp_get_attachment_image( $meta->meta_value, 'full' );
					}
				}
			}
		}

		return $this->custom_fields[ $post_id ];
	}

	/**
	 * Check if a post has a specific custom field.
	 *
	 * @param int    $post_id The ID of the post to check.
	 * @param string $field_key The key of the custom field to check for.
	 * @return bool True if the custom field exists, false otherwise.
	 */
	public function has_field( $post_id, $field_key ) {
		if ( $post_id <= 0 ) {
			return false;
		}

		$post_type = get_post_type( $post_id );
		if ( ! in_array( $post_type, $this->get_supported_post_types(), true ) ) {
			return false;
		}

		return metadata_exists( $post_type, $post_id, $field_key );
	}

	/**
	 * Get a specific custom field by post ID and field key.
	 *
	 * @param int    $post_id The ID of the post to retrieve the custom field for.
	 * @param string $field_key The key of the custom field to retrieve.
	 * @param mixed  $default_value The default value to return if the field is not found.
	 * @return mixed The value of the custom field, or the default value if not found.
	 */
	public function get_field_value( $post_id, $field_key, $default_value = null ) {
		if ( $post_id <= 0 ) {
			return $default_value;
		}

		$post_type = get_post_type( $post_id );
		if ( ! in_array( $post_type, $this->get_supported_post_types(), true ) ) {
			return $default_value;
		}

		$value = get_post_meta( $post_id, $field_key, true );
		return '' !== $value ? $value : $default_value;
	}

	/**
	 * Get the supported post types for the processor.
	 *
	 * @return array|string[]
	 */
	protected function get_supported_post_types() {
		return CustomFields::get_supported_post_types();
	}

	/**
	 * Get supported field types.
	 *
	 * @return array|string[]
	 */
	protected function get_supported_field_types() {
		return $this->supported_field_types;
	}

	/**
	 * Check if a field should be included based on various criteria.
	 *
	 * @param string $field_key The field key to check.
	 * @return bool Whether the field should be included.
	 */
	protected function should_include_field( $field_key ) {
		if ( empty( $field_key ) ) {
			return false;
		}

		/**
		 * Filters the list of blacklisted advanced custom field keys.
		 *
		 * @since 3.1.0
		 *
		 * @param array $blacklisted_keys Array of advanced custom field keys to be excluded.
		 */
		$blacklisted_keys = apply_filters( 'divi_squad_advanced_custom_fields_blacklist', $this->blacklisted_keys );
		if ( in_array( $field_key, $blacklisted_keys, true ) ) {
			return false;
		}

		/**
		 * Filters the list of excluded suffixes for advanced custom field keys.
		 *
		 * @since 3.1.0
		 *
		 * @param array $excluded_suffixes Array of suffixes to exclude from advanced custom field keys.
		 */
		$excluded_suffixes = apply_filters( 'divi_squad_advanced_custom_fields_excluded_suffixes', $this->excluded_suffixes );
		foreach ( $excluded_suffixes as $suffix ) {
			if ( Str::ends_with( $field_key, $suffix ) ) {
				return false;
			}
		}

		/**
		 * Filters the list of excluded prefixes for advanced custom field keys.
		 *
		 * @since 3.1.0
		 *
		 * @param array $excluded_prefixes Array of prefixes to exclude from advanced custom field keys.
		 */
		$excluded_prefixes = apply_filters( 'divi_squad_advanced_custom_fields_excluded_prefixes', $this->excluded_prefixes );
		foreach ( $excluded_prefixes as $prefix ) {
			if ( Str::starts_with( $field_key, $prefix ) ) {
				return false;
			}
		}

		return true;
	}
}
