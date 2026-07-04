<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Memory management class for WordPress options
 *
 * Provides a caching layer and advanced features for WordPress options management.
 *
 * @since   2.0.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Core;

use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Class Memory
 *
 * Manages plugin settings with caching capabilities to reduce database queries.
 *
 * @since   2.0.0
 * @package DiviSquad
 */
class Memory {

	/**
	 * Stored option data.
	 *
	 * @since 2.0.0
	 * @var array
	 */
	private array $data = array();

	/**
	 * WordPress option name.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	private string $option_name;

	/**
	 * Cache group name.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	private string $cache_group;

	/**
	 * Data modification status.
	 *
	 * @since 2.0.0
	 * @var bool
	 */
	private bool $is_modified = false;

	/**
	 * Batch operations queue.
	 *
	 * @since 2.0.0
	 * @var array
	 */
	private array $batch_queue = array();

	/**
	 * Initialize the Memory class.
	 *
	 * @since 2.0.0
	 *
	 * @param string $prefix Optional. Plugin prefix for option naming. Default 'divi-squad'.
	 */
	public function __construct( string $prefix = 'divi-squad' ) {
		if ( '' === $prefix ) {
			throw new InvalidArgumentException( 'Prefix cannot be empty.' );
		}

		$this->cache_group = sanitize_key( $prefix );
		$this->option_name = sanitize_key( "{$prefix}_settings" );

		$this->load_data_from_storage();

		// Sync data on shutdown.
		add_action( 'shutdown', array( $this, 'sync_data' ), 0 );
	}

	/**
	 * Initialize data from cache or database.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private function load_data_from_storage(): void {
		try {
			if ( $this->all() !== array() ) {
				return;
			}

			$cached_data = wp_cache_get( $this->option_name, $this->cache_group );
			if ( false !== $cached_data ) {
				$this->data = (array) $cached_data;

				return;
			}

			$this->data = get_option( $this->option_name, array() );
			wp_cache_set( $this->option_name, $this->data, $this->cache_group );

			$this->maybe_migrate_legacy_options();
		} catch ( Throwable $e ) {
			// Log the error but initialize with empty data to prevent critical failure
			divi_squad()->log_error( $e, 'Failed to load data from storage' );
			$this->data = array();
		}
	}

	/**
	 * Migrate legacy options if they exist.
	 *
	 * @since 3.2.0
	 *
	 * @return void
	 */
	private function maybe_migrate_legacy_options(): void {
		// Early return if already migrated
		if ( (bool) $this->get( 'migrated_legacy_options', false ) ) {
			return;
		}

		try {
			// Start atomic operation
			if ( function_exists( 'wp_cache_get_last_changed' ) ) {
				wp_cache_get_last_changed( 'options' );
			}

			/**
			 * Filters the legacy options to migrate.
			 *
			 * @since 3.2.0
			 *
			 * @param array $legacy_options Array of legacy option names.
			 */
			$legacy_options = apply_filters( 'divi_squad_legacy_memory_options', array( 'disq-settings', 'disq_settings' ) );

			$migration_log        = array();
			$migration_successful = true;

			foreach ( $legacy_options as $legacy_option ) {
				$legacy_data = get_option( $legacy_option );

				if ( false === $legacy_data ) {
					$migration_log[ $legacy_option ] = 'No data found';
					continue;
				}

				// Verify legacy data structure
				if ( ! is_array( $legacy_data ) ) {
					$migration_log[ $legacy_option ] = 'Invalid data structure';
					$migration_successful            = false;
					continue;
				}

				// Merge data
				$this->data        = array_merge_recursive( $this->data, $legacy_data );
				$this->is_modified = true;

				// Delete old option only after successful merge
				if ( delete_option( $legacy_option ) ) {
					$migration_log[ $legacy_option ] = 'Successfully migrated and cleaned';
				} else {
					$migration_log[ $legacy_option ] = 'Migration successful but cleanup failed';
					$migration_successful            = false;
				}
			}

			// Set migration flag only if everything was successful
			if ( $migration_successful ) {
				$this->set( 'migrated_legacy_options', true );
				$this->set( 'migration_timestamp', current_time( 'mysql' ) );
				$this->set( 'migration_log', $migration_log );
			}

			// Sync changes immediately
			if ( $this->is_modified ) {
				$this->sync_data();
			}
		} catch ( Throwable $e ) {
			// Log error and set migration as failed
			$this->set( 'migration_error', $e->getMessage() );
			$this->set( 'migration_status', 'failed' );
			$this->sync_data();
		}
	}

	/**
	 * Get all stored options.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public function all(): array {
		return $this->data;
	}

	/**
	 * Get the count of stored options.
	 *
	 * @since 2.0.0
	 *
	 * @return int
	 */
	public function count(): int {
		return count( $this->data );
	}

	/**
	 * Check if a field exists.
	 *
	 * @since 2.0.0
	 *
	 * @param string $field Field key.
	 *
	 * @return bool
	 */
	public function has( string $field ): bool {
		return isset( $this->data[ $field ] );
	}

	/**
	 * Get a field value or default.
	 *
	 * @since 2.0.0
	 *
	 * @param string $field   Field key.
	 * @param mixed  $default Default value.
	 *
	 * @return mixed
	 */
	public function get( string $field, $default = null ) {
		return $this->has( $field ) ? $this->data[ $field ] : $default;
	}

	/**
	 * Set a field value.
	 *
	 * Only updates the field if the new value is different.
	 * Marks the data as modified if updated.
	 *
	 * @since 2.0.0
	 *
	 * @param string $field Field key.
	 * @param mixed  $value Field value.
	 *
	 * @return bool True if field was updated.
	 */
	public function set( string $field, $value ): bool { // phpcs:ignore Universal.NamingConventions
		// Check if field exists and if value is unchanged.
		$exists = $this->has( $field );
		if ( $exists && $this->data[ $field ] === $value ) {
			return false;
		}

		// Update the field.
		$this->data[ $field ] = $value;
		$this->is_modified    = true;

		return true;
	}

	/**
	 * Update a field if it exists.
	 *
	 * @since 2.0.0
	 *
	 * @param string $field Field key.
	 * @param mixed  $value Field value.
	 *
	 * @return bool
	 */
	public function update( string $field, $value ): bool { // phpcs:ignore Universal.NamingConventions
		if ( ! $this->has( $field ) ) {
			return false;
		}

		return $this->set( $field, $value );
	}

	/**
	 * Delete a field if it exists.
	 *
	 * @since 2.0.0
	 *
	 * @param string $field Field key.
	 *
	 * @return bool
	 */
	public function delete( string $field ): bool {
		if ( ! $this->has( $field ) ) {
			return false;
		}

		unset( $this->data[ $field ] );
		$this->is_modified = true;

		return true;
	}

	/**
	 * Set multiple fields at once.
	 *
	 * @since 2.0.0
	 *
	 * @param array $fields Key-value pairs of fields to set.
	 *
	 * @return bool
	 */
	public function set_many( array $fields ): bool {
		$updated = false;

		foreach ( $fields as $field => $value ) {
			$updated = $this->set( $field, $value ) || $updated;
		}

		return $updated;
	}

	/**
	 * Add a value to an array field.
	 *
	 * @since 2.0.0
	 *
	 * @param string $field Field key.
	 * @param mixed  $value Value to add.
	 *
	 * @return void
	 * @throws RuntimeException If field is not an array.
	 */
	public function add_to_array( string $field, $value ): void {
		if ( ! isset( $this->data[ $field ] ) ) {
			$this->data[ $field ] = array();
		}

		if ( ! is_array( $this->data[ $field ] ) ) {
			throw new RuntimeException(
				sprintf(
				/* translators: %s: field name */
					esc_html__( 'Field %s must be an array.', 'divi-squad' ),
					esc_html( $field )
				)
			);
		}

		$this->data[ $field ][] = $value;
		$this->is_modified      = true;
	}

	/**
	 * Remove a value from an array field.
	 *
	 * @since 2.0.0
	 *
	 * @param string $field Field key.
	 * @param mixed  $value Value to remove.
	 *
	 * @return bool True if value was removed.
	 * @throws RuntimeException If field is not an array.
	 */
	public function remove_from_array( string $field, $value ): bool {
		if ( ! isset( $this->data[ $field ] ) || ! is_array( $this->data[ $field ] ) ) {
			throw new RuntimeException(
				sprintf(
				/* translators: %s: field name */
					esc_html__( 'Field %s must be an array.', 'divi-squad' ),
					esc_html( $field )
				)
			);
		}

		$key = array_search( $value, $this->data[ $field ], true );
		if ( false !== $key ) {
			unset( $this->data[ $field ][ $key ] );
			$this->is_modified = true;

			return true;
		}

		return false;
	}

	/**
	 * Queue a batch operation.
	 *
	 * @since 2.0.0
	 *
	 * @param string $operation Operation type ('set', 'update', 'delete').
	 * @param string $field     Field key.
	 * @param mixed  $value     Optional. Value for set/update operations.
	 *
	 * @return void
	 */
	public function queue_batch_operation( string $operation, string $field, $value = null ): void {
		$this->batch_queue[] = array(
			'operation' => $operation,
			'field'     => $field,
			'value'     => $value,
		);
	}

	/**
	 * Execute all queued batch operations.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function execute_batch(): void {
		foreach ( $this->batch_queue as $operation ) {
			switch ( $operation['operation'] ) {
				case 'set':
					$this->set( $operation['field'], $operation['value'] );
					break;
				case 'update':
					$this->update( $operation['field'], $operation['value'] );
					break;
				case 'delete':
					$this->delete( $operation['field'] );
					break;
			}
		}
		$this->batch_queue = array();
	}

	/**
	 * Sync modified data to the database.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function sync_data(): void {
		try {
			if ( ! $this->is_modified ) {
				return;
			}

			// Clear cache and update option.
			wp_cache_delete( $this->option_name, $this->cache_group );

			// Update option and cache.
			update_option( $this->option_name, $this->data );
			wp_cache_set( $this->option_name, $this->data, $this->cache_group );

			// Reset modification status.
			$this->is_modified = false;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to sync memory data to database' );
			// Keep the is_modified flag true so we can try again later
		}
	}

	/**
	 * Clear all stored data.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function clear_all(): void {
		$this->data        = array();
		$this->is_modified = true;

		wp_cache_delete( $this->option_name, $this->cache_group );
	}
}
