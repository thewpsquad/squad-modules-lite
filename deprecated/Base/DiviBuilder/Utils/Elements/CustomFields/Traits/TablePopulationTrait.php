<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Table Population Trait
 *
 * Provides functionality for populating custom fields tables in large databases.
 * Focuses on minimal memory usage and maximum query performance.
 *
 * @since      3.1.0
 * @subpackage Divi_Builder
 * @package    DiviSquad
 * @deprecated 3.3.0
 */

namespace DiviSquad\Base\DiviBuilder\Utils\Elements\CustomFields\Traits;

use Throwable;

/**
 * Table Population Trait
 *
 * This trait provides methods for efficiently populating and managing
 * custom fields tables in WordPress, with a focus on performance
 * and memory optimization.
 *
 * @since 3.1.0
 * @deprecated 3.3.0
 */
trait TablePopulationTrait {

	/**
	 * Batch size for processing records.
	 * Adjust based on server capabilities.
	 *
	 * @since 3.1.0
	 * @var   int
	 */
	protected int $batch_size = 5000;

	/**
	 * Maximum execution time for each batch (seconds).
	 *
	 * @since 3.1.0
	 * @var   int
	 */
	protected int $max_batch_time = 20;

	/**
	 * Tracked post types for custom fields.
	 *
	 * @var bool
	 */
	protected bool $is_table_exists = false;

	/**
	 * Populate the summary table efficiently.
	 *
	 * @since  3.1.0
	 * @return void
	 */
	public function populate_summary_table() {
		if ( ! $this->is_table_exists() ) {
			return;
		}

		if ( ! $this->needs_population() ) {
			return;
		}

		$cache_key = 'divi_squad_custom_fields_populated';

		// Check if population is already complete.
		if ( wp_cache_get( $cache_key ) ) {
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
	 *
	 * @return bool True if table exists.
	 */
	protected function is_table_exists(): bool {
		global $wpdb;

		// Check if table exists.
		if ( ! $this->is_table_exists ) {
			$table_exists = $wpdb->get_var( $wpdb->prepare( 'SELECT * FROM information_schema.tables WHERE table_schema = %s AND table_name = %s limit 1;', DB_NAME, $this->table_name, ) );

			// Update property.
			$this->is_table_exists = (bool) $table_exists;
		}
		return $this->is_table_exists;
	}

	/**
	 * Process a batch of records.
	 *
	 * @since  3.1.0
	 *
	 * @param int $last_id Last processed ID.
	 *
	 * @return int Next last ID or 0 if complete.
	 */
	protected function process_batch( int $last_id ): int {
		global $wpdb;

		$post_types = $this->prepare_post_types();
		$start_time = microtime( true );

		divi_squad()->log_debug(
			sprintf(
				'Processing batch: last_id=%d, post_types=%s',
				$last_id,
				$post_types
			)
		);

		try {
			// Main query using INNER JOIN and EXISTS optimization.
			// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders
			$result = $wpdb->query(
				$wpdb->prepare(
					"INSERT INTO {$this->table_name} (meta_key, post_type)
                SELECT DISTINCT pm.meta_key, p.post_type
                FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                WHERE 1=1
                    AND pm.meta_id > %d
                    AND pm.meta_key NOT LIKE '\_%%'
                    AND p.post_type IN ({$post_types})
                    AND NOT EXISTS (
                        SELECT 1
                        FROM {$wpdb->postmeta} hidden
                        WHERE hidden.meta_key = CONCAT('_', pm.meta_key)
                        AND hidden.post_id = pm.post_id
                    )
                    AND NOT EXISTS (
                        SELECT 1
                        FROM {$this->table_name} cf
                        WHERE cf.meta_key = pm.meta_key
                        AND cf.post_type = p.post_type
                    )
                GROUP BY pm.meta_key, p.post_type
                LIMIT %d
                ON DUPLICATE KEY UPDATE last_updated = CURRENT_TIMESTAMP",
					$last_id,
					$this->batch_size
				)
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders

			if ( false === $result ) {
				divi_squad()->log_debug( 'Batch processing query failed: ' . $wpdb->last_error );

				return 0;
			}

			// Get the last processed ID using a more efficient query.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$next_id = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT MIN(meta_id)
                FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                WHERE pm.meta_id > %d
                    AND pm.meta_key NOT LIKE '\_%%'
                    AND p.post_type IN ({$post_types})
                    AND NOT EXISTS (
                        SELECT 1
                        FROM {$this->table_name} cf
                        WHERE cf.meta_key = pm.meta_key
                        AND cf.post_type = p.post_type
                    )",
					$last_id
				)
			);

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
				function ( $type ) use ( $wpdb ) {
					return $wpdb->prepare( '%s', $type );
				},
				$this->tracked_post_types
			)
		);
	}

	/**
	 * Get meta keys that exist in postmeta but not in the collection table.
	 * Optimized query using INNER JOIN and NOT IN clause.
	 *
	 * @since  3.1.0
	 * @return array Array of missing meta keys with their post types.
	 */
	public function get_missing_meta_keys(): array {
		global $wpdb;

		$post_types = $this->prepare_post_types();
		$cache_key  = sprintf( 'divi_squad_missing_meta_keys_%s', md5( $post_types ) );

		// Try to get from cache first.
		$cached_results = wp_cache_get( $cache_key, 'divi-squad-custom_fields' );
		if ( false !== $cached_results ) {
			return $cached_results;
		}

		try {
			// Main query using INNER JOIN and subquery optimization.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT
                    pm.meta_key,
                    p.post_type,
                    COUNT(*) as occurrence_count
                FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                WHERE 1=1
                    AND pm.meta_key NOT LIKE '\_%%'
                    AND p.post_type IN ({$post_types})
                    AND NOT EXISTS (
                        SELECT 1
                        FROM {$this->table_name} cf
                        WHERE cf.meta_key = pm.meta_key
                        AND cf.post_type = p.post_type
                    )
                GROUP BY pm.meta_key, p.post_type
                ORDER BY occurrence_count DESC
                LIMIT 1000"
				),
				ARRAY_A
			);

			if ( ! is_array( $results ) ) {
				divi_squad()->log_debug( 'No missing meta keys found or query failed' );

				return array();
			}

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
					implode( ', ', $this->tracked_post_types )
				)
			);

			return array();
		}
	}

	/**
	 * Get meta keys count by post type.
	 * Helper method to analyze meta keys distribution.
	 *
	 * @since  3.1.0
	 * @return array Array of meta key counts by post type.
	 */
	public function get_meta_keys_count_by_post_type(): array {
		global $wpdb;

		$post_types = $this->prepare_post_types();

		// phpcs:ignore WordPress.DB
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
                p.post_type,
                COUNT(DISTINCT pm.meta_key) as unique_keys,
                COUNT(pm.meta_key) as total_keys
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE p.post_type IN ({$post_types}) AND pm.meta_key NOT LIKE '\_%%'
            GROUP BY p.post_type
            ORDER BY unique_keys DESC"
			),
			ARRAY_A
		);
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
		$optimal_size     = floor( ( $available_memory * 0.8 ) / 2048 );

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
