<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Table Population Trait
 *
 * Provides functionality for populating custom fields tables in large databases.
 * Focuses on minimal memory usage and maximum query performance.
 *
 * @since   3.1.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Utils\Elements\Custom_Fields\Traits;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use Throwable;
use WP_Query;

/**
 * Table Population Trait
 *
 * This trait provides methods for efficiently populating and managing
 * custom fields tables in WordPress, with a focus on performance
 * and memory optimization.
 *
 * @since   3.1.0
 * @package DiviSquad
 */
trait Table_Population_Trait {

	/**
	 * Batch size for processing records.
	 * Adjust based on server capabilities.
	 *
	 * @since 3.1.0
	 * @var   int
	 */
	public int $batch_size = 5000;

	/**
	 * Maximum execution time for each batch (seconds).
	 *
	 * @since 3.1.0
	 * @var   int
	 */
	public int $max_batch_time = 20;

	/**
	 * Tracked post types for custom fields.
	 *
	 * @var bool
	 */
	public bool $is_table_exists = false;

	/**
	 * Populate the summary table efficiently.
	 *
	 * @since  3.1.0
	 * @return void
	 */
	public function populate_summary_table(): void {
		if ( ! $this->is_table_exists() ) {
			return;
		}

		if ( ! $this->needs_population() ) {
			return;
		}

		$cache_key = 'divi_squad_custom_fields_populated';

		// Check if population is already complete.
		if ( false !== wp_cache_get( $cache_key ) ) {
			return;
		}

		// Get last processed ID.
		$last_id = (int) divi_squad()->memory->get( 'last_processed_id', 0 );

		try {
			$start_time = time();

			do {
				// Process batch and get last ID.
				$last_id = $this->process_batch( $last_id );

				// Update last processed ID.
				divi_squad()->memory->update( 'last_processed_id', $last_id );

				// Check if we've exceeded the time limit.
				if ( ( time() - $start_time ) >= $this->max_batch_time ) {
					divi_squad()->log_debug( 'Batch processing time limit reached' );
					break;
				}
			} while ( $last_id > 0 );

			// If processing is complete, set cache.
			if ( 0 === $last_id ) {
				wp_cache_set( $cache_key, true, '', HOUR_IN_SECONDS );
				divi_squad()->memory->delete( 'last_processed_id' );
				divi_squad()->log_debug( 'Table population completed successfully' );
			}
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Custom Fields Population Error' );
		}
	}

	/**
	 * Verify if the custom fields table exists.
	 *
	 * @since  3.2.0
	 * @return bool True if table exists.
	 */
	public function is_table_exists(): bool {
		global $wpdb;

		// Check if table exists.
		if ( ! $this->is_table_exists ) {
			$table_exists = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT * FROM information_schema.tables WHERE table_schema = %s AND table_name = %s LIMIT 1;',
					DB_NAME,
					$this->table_name
				)
			);

			// Update property.
			$this->is_table_exists = (bool) $table_exists;
		}

		return $this->is_table_exists;
	}

	/**
	 * Process a batch of records using WP_Meta_Query.
	 *
	 * @since  3.1.0
	 *
	 * @param int $last_id Last processed meta ID.
	 *
	 * @return int Next last ID or 0 if complete.
	 */
	protected function process_batch( int $last_id ): int {
		global $wpdb;

		$post_types = $this->tracked_post_types;
		$start_time = microtime( true );
		$chunk_size = $this->validate_batch_size( $this->get_optimal_batch_size() );

		divi_squad()->log_debug(
			sprintf(
				'Processing batch: last_id=%d, post_types=%s, chunk_size=%d',
				$last_id,
				implode( ',', $post_types ),
				$chunk_size
			)
		);

		try {
			// Initialize meta query to exclude hidden meta keys and filter by post types.
			$meta_query = array(
				'relation' => 'AND',
				array(
					'key'     => 'meta_key',
					'compare' => 'NOT LIKE',
					'value'   => '_%',
				),
			);

			// Build WP_Query to fetch posts with meta keys.
			$args = array(
				'post_type'      => $post_types,
				'posts_per_page' => $chunk_size,
				'meta_query'     => $meta_query,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_key'       => '',
				'post_status'    => 'any',
			);

			// If last_id is provided, filter meta_id greater than last_id.
			if ( $last_id > 0 ) {
				$args['meta_query'][] = array(
					'key'     => 'meta_id',
					'value'   => $last_id,
					'compare' => '>',
					'type'    => 'NUMERIC',
				);
			}

			$query    = new WP_Query( $args );
			$post_ids = $query->posts;

			if ( 0 === count( $post_ids ) ) {
				divi_squad()->log_debug( 'No posts found for batch processing' );

				return 0;
			}

			// Process posts in smaller chunks to optimize memory.
			$meta_keys_to_insert = array();
			$next_id             = 0;

			foreach ( array_chunk( $post_ids, 100 ) as $chunk ) {
				foreach ( $chunk as $post ) {
					$post_id = $post instanceof \WP_Post ? $post->ID : (int) $post;

					$post_type = get_post_type( $post_id );
					if ( false === $post_type ) {
						continue;
					}

					// Get post meta for the current post.
					$meta_data = get_post_meta( $post_id, '', true );
					if ( ! is_array( $meta_data ) ) {
						continue;
					}

					foreach ( $meta_data as $meta_key => $meta_values ) {
						$meta_key = (string) $meta_key;
						if ( strpos( $meta_key, '_' ) === 0 ) {
							continue; // Skip hidden meta keys.
						}

						// Check if meta key already exists in custom table.
						$exists = $wpdb->get_var(
							$wpdb->prepare(
								"SELECT 1 FROM {$this->table_name} WHERE meta_key = %s AND post_type = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $this->table_name is an internal identifier ({$wpdb->prefix}divi_squad_custom_fields), not user input.
								$meta_key,
								$post_type
							)
						);

						if ( null === $exists ) {
							$meta_keys_to_insert[] = array(
								'meta_key'  => $meta_key,
								'post_type' => $post_type,
							);
						}
					}
				}

				// Insert collected meta keys into custom table.
				if ( count( $meta_keys_to_insert ) > 0 ) {
					$values       = array();
					$placeholders = array();

					foreach ( $meta_keys_to_insert as $item ) {
						$placeholders[] = '(%s, %s)';
						$values[]       = $item['meta_key'];
						$values[]       = $item['post_type'];
					}

					$query = "INSERT INTO {$this->table_name} (meta_key, post_type) VALUES "; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $this->table_name is an internal identifier, not user input.
					$query .= implode( ',', $placeholders );
					$query .= ' ON DUPLICATE KEY UPDATE last_updated = CURRENT_TIMESTAMP';

					// $query is built from a fixed template plus %s placeholders; values are bound via prepare().
					$result = $wpdb->query( $wpdb->prepare( $query, $values ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Placeholders bound via prepare() above.

					if ( false === $result ) {
						divi_squad()->log_debug( 'Batch insert failed: ' . $wpdb->last_error );

						return 0;
					}
				}

				$meta_keys_to_insert = array(); // Clear for next chunk.
			}

			// Get the next meta_id for the next batch.
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.LikeWildcardsInQuery -- Table names are internal identifiers; the LIKE '_%%' pattern is a fixed literal, not user input.
			$next_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT MIN(meta_id)
                FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                WHERE pm.meta_id > %d
                    AND pm.meta_key NOT LIKE '_%%'
                    AND p.post_type IN (" . implode( ',', array_fill( 0, count( $post_types ), '%s' ) ) . ")
                    AND NOT EXISTS (
                        SELECT 1
                        FROM {$this->table_name} cf
                        WHERE cf.meta_key = pm.meta_key
                        AND cf.post_type = p.post_type
                    )",
					array_merge( array( $last_id ), $post_types )
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.LikeWildcardsInQuery

			$next_id = (int) $next_id;

			$execution_time = microtime( true ) - $start_time;
			divi_squad()->log_debug(
				sprintf(
					'Processed batch: last_id=%d, next_id=%d, time=%.2f seconds',
					$last_id,
					$next_id,
					$execution_time
				)
			);

			return $next_id;

		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Error processing batch' );

			return 0;
		}
	}

	/**
	 * Check if table needs to be populated.
	 *
	 * @since  3.1.0
	 * @return bool True if table needs population.
	 */
	protected function needs_population(): bool {
		$missing_keys = $this->get_missing_meta_keys();
		$needs_pop    = count( $missing_keys ) > 0;

		if ( $needs_pop ) {
			divi_squad()->log_debug(
				sprintf( 'Table needs population. Missing keys count: %d', count( $missing_keys ) )
			);
		}

		return $needs_pop;
	}

	/**
	 * Prepare post types for SQL query.
	 *
	 * @since  3.1.0
	 * @return string SQL-ready post types string.
	 */
	protected function prepare_post_types(): string {
		global $wpdb;

		return implode(
			',',
			array_map(
				static function ( $type ) use ( $wpdb ) {
					return $wpdb->prepare( '%s', $type );
				},
				$this->tracked_post_types
			)
		);
	}

	/**
	 * Get meta keys that exist in postmeta but not in the collection table using WP_Meta_Query.
	 *
	 * @since  3.1.0
	 * @return array<int, array{meta_key: string, post_type: string, occurrence_count: int}> Array of missing meta keys with their post types and occurrence counts.
	 */
	public function get_missing_meta_keys(): array {
		global $wpdb;

		$post_types  = $this->tracked_post_types;
		$cache_key   = sprintf( 'divi_squad_missing_meta_keys_%s', md5( implode( ',', $post_types ) ) );
		$chunk_size  = $this->validate_batch_size( $this->get_optimal_batch_size() );
		$max_results = 1000; // Limit to 1000 results as per original query.

		// Try to get from cache first.
		$cached_results = wp_cache_get( $cache_key, 'divi-squad-custom_fields' );
		if ( is_array( $cached_results ) ) {
			$normalized = array();
			foreach ( $cached_results as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}

				$normalized[] = array(
					'meta_key'         => (string) ( $row['meta_key'] ?? '' ),
					'post_type'        => (string) ( $row['post_type'] ?? '' ),
					'occurrence_count' => (int) ( $row['occurrence_count'] ?? 0 ),
				);
			}

			return $normalized;
		}

		try {
			// Initialize results array to store meta keys, post types, and counts.
			$results         = array();
			$meta_key_counts = array();

			// Meta query to exclude hidden meta keys.
			$meta_query = array(
				'relation' => 'AND',
				array(
					'key'     => 'meta_key',
					'compare' => 'NOT LIKE',
					'value'   => '_%',
				),
			);

			// WP_Query arguments.
			$args = array(
				'post_type'      => $post_types,
				'posts_per_page' => $chunk_size,
				'meta_query'     => $meta_query,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'post_status'    => 'any',
			);

			$query    = new WP_Query( $args );
			$post_ids = $query->posts;

			// Process posts in chunks until no more posts or max_results reached.
			// phpcs:ignore Squiz.PHP.DisallowSizeFunctionsInLoops.Found -- $results grows inside the loop; a cached count would go stale. count() on a small in-memory array is negligible.
			while ( count( $post_ids ) > 0 && count( $results ) < $max_results ) {
				foreach ( array_chunk( $post_ids, 100 ) as $chunk ) {
					foreach ( $chunk as $post ) {
						$post_id   = $post instanceof \WP_Post ? $post->ID : (int) $post;
						$post_type = get_post_type( $post_id );
						if ( ! in_array( $post_type, $post_types, true ) ) {
							continue;
						}

						// Get all meta keys for the post.
						$meta_data = get_post_meta( $post_id, '', true );
						if ( ! is_array( $meta_data ) ) {
							continue;
						}

						foreach ( $meta_data as $meta_key => $meta_values ) {
							$meta_key = (string) $meta_key;
							if ( strpos( $meta_key, '_' ) === 0 ) {
								continue; // Skip hidden meta keys.
							}

							// Check if meta key already exists in custom table.
							$exists = $wpdb->get_var(
								$wpdb->prepare(
									"SELECT 1 FROM {$this->table_name} WHERE meta_key = %s AND post_type = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $this->table_name is an internal identifier, not user input.
									$meta_key,
									$post_type
								)
							);

							if ( $exists ) {
								continue; // Skip if already in custom table.
							}

							// Track occurrence count.
							$key = $meta_key . '|' . $post_type;
							if ( ! isset( $meta_key_counts[ $key ] ) ) {
								$meta_key_counts[ $key ] = array(
									'meta_key'         => $meta_key,
									'post_type'        => $post_type,
									'occurrence_count' => 0,
								);
							}
							++$meta_key_counts[ $key ]['occurrence_count'];
						}
					}

					// Add to results if under max_results.
					foreach ( $meta_key_counts as $key => $data ) {
						if ( count( $results ) >= $max_results ) {
							break;
						}
						if ( ! in_array( $data, $results, true ) ) {
							$results[] = $data;
						}
					}
				}

				// Fetch next batch.
				$args['paged'] = isset( $args['paged'] ) ? $args['paged'] + 1 : 2;
				$query         = new WP_Query( $args );
				$post_ids      = $query->posts;
			}

			// Sort by occurrence_count DESC.
			usort(
				$results,
				static function ( $a, $b ) {
					return $b['occurrence_count'] - $a['occurrence_count'];
				}
			);

			// Cache the results for 5 minutes.
			wp_cache_set(
				$cache_key,
				$results,
				'divi-squad-custom_fields',
				5 * MINUTE_IN_SECONDS
			);

			return $results;

		} catch ( Throwable $e ) {
			divi_squad()->log_error(
				$e,
				sprintf(
					'Error getting missing meta keys for post types: %s',
					implode( ', ', $post_types )
				)
			);

			return array();
		}
	}

	/**
	 * Get meta keys count by post type using WP_Meta_Query.
	 *
	 * @since  3.1.0
	 * @return array<int, array{post_type: string, unique_keys: int, total_keys: int}> Array of meta key counts by post type.
	 */
	public function get_meta_keys_count_by_post_type(): array {
		$post_types = $this->tracked_post_types;
		$cache_key  = sprintf( 'divi_squad_meta_keys_count_%s', md5( implode( ',', $post_types ) ) );
		$chunk_size = $this->validate_batch_size( $this->get_optimal_batch_size() );

		// Try to get from cache first.
		$cached_results = wp_cache_get( $cache_key, 'divi-squad-custom_fields' );
		if ( is_array( $cached_results ) ) {
			$normalized = array();
			foreach ( $cached_results as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}

				$normalized[] = array(
					'post_type'   => (string) ( $row['post_type'] ?? '' ),
					'unique_keys' => (int) ( $row['unique_keys'] ?? 0 ),
					'total_keys'  => (int) ( $row['total_keys'] ?? 0 ),
				);
			}

			return $normalized;
		}

		try {
			// Initialize results array.
			$results = array();
			foreach ( $post_types as $post_type ) {
				$results[ $post_type ] = array(
					'post_type'   => $post_type,
					'unique_keys' => 0,
					'total_keys'  => 0,
				);
			}

			// Meta query to exclude hidden meta keys.
			$meta_query = array(
				'relation' => 'AND',
				array(
					'key'     => 'meta_key',
					'compare' => 'NOT LIKE',
					'value'   => '_%',
				),
			);

			// WP_Query arguments.
			$args = array(
				'post_type'      => $post_types,
				'posts_per_page' => $chunk_size,
				'meta_query'     => $meta_query,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'post_status'    => 'any',
			);

			$query    = new WP_Query( $args );
			$post_ids = $query->posts;

			// Process posts in chunks.
			// phpcs:ignore Squiz.PHP.DisallowSizeFunctionsInLoops.Found -- $post_ids is repopulated from a paged WP_Query at the end of each iteration; count() reflects the new page.
			while ( count( $post_ids ) > 0 ) {
				foreach ( array_chunk( $post_ids, 100 ) as $chunk ) {
					foreach ( $chunk as $post ) {
						$post_id   = $post instanceof \WP_Post ? $post->ID : (int) $post;
						$post_type = get_post_type( $post_id );
						if ( false === $post_type || ! isset( $results[ $post_type ] ) ) {
							continue;
						}

						// Get all meta keys for the post.
						$meta_data = get_post_meta( $post_id, '', true );
						if ( ! is_array( $meta_data ) ) {
							continue;
						}

						// Filter out hidden meta keys.
						$visible_keys = array_filter(
							array_keys( $meta_data ),
							static function ( $key ): bool {
								return strpos( (string) $key, '_' ) !== 0;
							}
						);

						// Update counts.
						$results[ $post_type ]['total_keys']  += count( $visible_keys );
						$results[ $post_type ]['unique_keys'] = count( array_unique( $visible_keys ) );
					}
				}

				// Fetch next batch.
				$args['paged'] = isset( $args['paged'] ) ? $args['paged'] + 1 : 2;
				$query         = new WP_Query( $args );
				$post_ids      = $query->posts;
			}

			// Format results as array.
			$formatted_results = array_values( $results );

			// Cache the results for 5 minutes.
			wp_cache_set(
				$cache_key,
				$formatted_results,
				'divi-squad-custom_fields',
				5 * MINUTE_IN_SECONDS
			);

			return $formatted_results;

		} catch ( Throwable $e ) {
			divi_squad()->log_error(
				$e,
				sprintf(
					'Error getting meta keys count for post types: %s',
					implode( ', ', $post_types )
				)
			);

			return array();
		}
	}

	/**
	 * Get the optimal batch size based on server resources.
	 *
	 * @since  3.1.0
	 * @return int Optimal batch size.
	 */
	protected function get_optimal_batch_size(): int {
		$memory_limit = wp_convert_hr_to_bytes( ini_get( 'memory_limit' ) );
		$base_memory  = memory_get_usage();

		// Estimate 2KB per record and aim to use no more than 80% of available memory.
		$available_memory = $memory_limit - $base_memory;
		$optimal_size     = (int) floor( ( $available_memory * 0.8 ) / 2048 );

		// Cap at maximum batch size and ensure minimum of 1000.
		return min( max( 1000, $optimal_size ), $this->batch_size );
	}

	/**
	 * Validate batch size is within acceptable limits.
	 *
	 * @since  3.1.0
	 *
	 * @param int $size Batch size to validate.
	 *
	 * @return int Validated batch size.
	 */
	protected function validate_batch_size( int $size ): int {
		$min_size = 1000;
		$max_size = 10000;

		$validated = max( $min_size, min( $size, $max_size ) );

		if ( $validated !== $size ) {
			divi_squad()->log_debug(
				sprintf(
					'Batch size adjusted from %d to %d (min: %d, max: %d)',
					$size,
					$validated,
					$min_size,
					$max_size
				)
			);
		}

		return $validated;
	}
}
