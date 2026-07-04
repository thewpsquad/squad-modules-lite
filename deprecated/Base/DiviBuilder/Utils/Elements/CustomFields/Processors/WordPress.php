<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Custom Fields (WordPress) element.
 *
 * @since      3.1.0
 * @deprecated 3.3.0
 * @package    DiviSquad
 * @author     The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Base\DiviBuilder\Utils\Elements\CustomFields\Processors;

use DiviSquad\Base\DiviBuilder\Utils\Elements\CustomFields;
use DiviSquad\Base\DiviBuilder\Utils\Elements\CustomFields\Processor;
use DiviSquad\Core\Supports\Polyfills\Str;
use function apply_filters;
use function get_metadata;
use function metadata_exists;

/**
 * Custom Fields (WordPress) element Class
 *
 * @since      3.1.0
 * @deprecated 3.3.0
 * @package    DiviSquad
 */
class WordPress extends Processor {

	/**
	 * Blacklisted keys
	 *
	 * @var array Blacklisted keys that should be excluded from custom fields.
	 */
	protected $blacklisted_keys = array(
		'_edit_lock',
		'_edit_last',
		'_thumbnail_id',
		'_wp_page_template',
		'_wp_old_slug',
		'_wp_trash_meta_time',
		'_wp_trash_meta_status',
	);

	/**
	 * Suffixes
	 *
	 * @var array Suffixes that should be excluded from custom fields.
	 */
	protected $excluded_suffixes = array( 'active', 'enabled', 'disabled', 'hidden', 'flag' );

	/**
	 * Prefixes
	 *
	 * @var array Prefixes that should be excluded from custom fields.
	 */
	protected $excluded_prefixes = array(
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
	 * Available custom formated fields
	 *
	 * @var array
	 */
	protected $formatted_fields = array();

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
		return true;
	}

	/**
	 * Collect custom fields and generate a formatted array.
	 *
	 * @return array An array where keys are original field names and values are formatted field names.
	 * @throws \Exception If the post type is not supported.
	 */
	public function get_formatted_fields() {
		if ( count( $this->formatted_fields ) === 0 ) {
			$fields = $this->get_available_fields();

			foreach ( $fields as $post_type => $keys ) {
				if ( ! in_array( $post_type, $this->get_supported_post_types(), true ) ) {
					continue;
				}

				$this->formatted_fields[ $post_type ] = array();

				foreach ( $keys as $key ) {
					if ( ! $this->should_include_field( $key ) ) {
						continue;
					}

					$this->formatted_fields[ $post_type ][ $key ] = ucwords( $this->format_field_name( $key ) );
				}
			}
		}

		return $this->formatted_fields;
	}

	/**
	 * Get all custom fields for a specific post.
	 *
	 * @param int $post_id The ID of the post.
	 *
	 * @return array An array of custom fields, where keys are field names and values are field values.
	 * @throws \Exception If the post type is not supported.
	 */
	public function get_fields( $post_id ) {
		if ( $post_id <= 0 ) {
			return array();
		}

		if ( isset( $this->custom_fields[ $post_id ] ) ) {
			return $this->custom_fields[ $post_id ];
		}

		// Initialize the custom fields array.
		$this->custom_fields[ $post_id ] = array();

		$custom_field_values = $this->get_available_field_values( $post_id );
		foreach ( $custom_field_values as $metadata ) {
			if ( empty( $metadata ) ) {
				continue;
			}

			if ( ! $this->should_include_field( $metadata->meta_key ) ) {
				continue;
			}

			$this->custom_fields[ $post_id ][ $metadata->meta_key ] = $metadata->meta_value;
		}

		return $this->custom_fields[ $post_id ];
	}

	/**
	 * Check if a post has a specific custom field.
	 *
	 * @param int    $post_id   The ID of the post to check.
	 * @param string $field_key The key of the custom field to check for.
	 *
	 * @return bool True if the custom field exists, false otherwise.
	 */
	public function has_field( $post_id, $field_key ) {
		return $post_id > 0 && metadata_exists( 'post', $post_id, $field_key );
	}

	/**
	 * Get a specific custom field by post ID and field key.
	 *
	 * @param int    $post_id       The ID of the post to retrieve the custom field for.
	 * @param string $field_key     The key of the custom field to retrieve.
	 * @param mixed  $default_value The default value to return if the field is not found.
	 *
	 * @return mixed The value of the custom field, or the default value if not found.
	 */
	public function get_field_value( $post_id, $field_key, $default_value = null ) {
		if ( $post_id <= 0 ) {
			return $default_value;
		}

		$value = get_metadata( 'post', $post_id, $field_key, true );

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
	 * Check if a field should be included based on various criteria.
	 *
	 * @param string $field_key The field key to check.
	 *
	 * @return bool Whether the field should be included.
	 */
	protected function should_include_field( $field_key ) {
		if ( empty( $field_key ) ) {
			return false;
		}

		/**
		 * Filters the list of blacklisted custom field keys.
		 *
		 * @since 3.1.0
		 *
		 * @param array $blacklisted_keys Array of custom field keys to be excluded.
		 */
		$blacklisted_keys = apply_filters( 'divi_squad_custom_fields_blacklist', $this->blacklisted_keys );
		if ( in_array( $field_key, $blacklisted_keys, true ) ) {
			return false;
		}

		/**
		 * Filters the list of excluded suffixes for custom field keys.
		 *
		 * @since 3.1.0
		 *
		 * @param array $excluded_suffixes Array of suffixes to exclude from custom field keys.
		 */
		$excluded_suffixes = apply_filters( 'divi_squad_custom_fields_excluded_suffixes', $this->excluded_suffixes );
		foreach ( $excluded_suffixes as $suffix ) {
			if ( Str::ends_with( $field_key, $suffix ) ) {
				return false;
			}
		}

		/**
		 * Filters the list of excluded prefixes for custom field keys.
		 *
		 * @since 3.1.0
		 *
		 * @param array $excluded_prefixes Array of prefixes to exclude from custom field keys.
		 */
		$excluded_prefixes = apply_filters( 'divi_squad_custom_fields_excluded_prefixes', $this->excluded_prefixes );
		foreach ( $excluded_prefixes as $prefixes ) {
			foreach ( $prefixes as $prefix ) {
				if ( Str::starts_with( $field_key, $prefix ) ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Collect available custom fields from the postmeta table.
	 *
	 * @return array An array of unique custom field keys.
	 * @throws \Exception If the post type is not supported.
	 */
	protected function get_available_fields() {
		if ( empty( $this->fields ) ) {
			/**
			 * Filters the number of custom fields to retrieve.
			 *
			 * @since 3.1.0
			 *
			 * @param int $limit Number of custom fields to retrieve. Default 30.
			 */
			$limit = apply_filters( 'postmeta_form_limit', 30 ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

			$post_types = $this->get_supported_post_types();
			foreach ( $post_types as $post_type ) {
				$this->fields[ $post_type ] = CustomFields::get_fields_manager()->get_data(
					array(
						'post_type' => $post_type,
						'limit'     => $limit,
					)
				);
			}
		}

		return $this->fields;
	}

	/**
	 * Collect available custom field values from the postmeta table for specific post.
	 *
	 * @param int $post_id The ID of the post.
	 *
	 * @return array An array of unique custom field values.
	 * @throws \Exception If the post type is not supported.
	 */
	protected function get_available_field_values( $post_id ) {
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

		$cache_key     = 'divi_squad_available_field_values_' . $post_id;
		$cached_values = wp_cache_get( $cache_key, 'divi_squad_custom_fields' );

		if ( false !== $cached_values ) {
			$this->field_values[ $post_id ] = $cached_values;

			return $this->field_values[ $post_id ];
		}

		$custom_fields                  = $this->get_available_fields();
		$supported_post_types           = $this->get_supported_post_types();
		$this->field_values[ $post_id ] = array();

		foreach ( $custom_fields as $post_type => $keys ) {
			if ( ! in_array( $post_type, $supported_post_types, true ) ) {
				continue;
			}

			$meta_keys                      = $keys;
			$this->field_values[ $post_id ] = array_merge(
				$this->field_values[ $post_id ],
				$this->get_post_meta_values( $post_id, $meta_keys, $limit )
			);
		}

		wp_cache_set( $cache_key, $this->field_values[ $post_id ], 'divi_squad_custom_fields', 3600 ); // Cache for 1 hour

		return $this->field_values[ $post_id ];
	}

	/**
	 * Get post meta values for given keys.
	 *
	 * @param int   $post_id   The ID of the post.
	 * @param array $meta_keys Array of meta keys to retrieve.
	 * @param int   $limit     Maximum number of results to return.
	 *
	 * @return array An array of post meta values.
	 */
	private function get_post_meta_values( $post_id, $meta_keys, $limit ) {
		$values = array();
		foreach ( $meta_keys as $key ) {
			$meta_values = get_post_meta( $post_id, $key, false );
			if ( ! empty( $meta_values ) ) {
				foreach ( $meta_values as $value ) {
					$values[] = (object) array(
						'meta_key'   => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
						'meta_value' => $value, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					);
					if ( count( $values ) >= $limit ) {
						break 2;
					}
				}
			}
		}

		return $values;
	}
}
