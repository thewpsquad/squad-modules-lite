<?php
/**
 * Memory management class for WordPress options
 *
 * Provides a caching layer and advanced features for WordPress options management.
 *
 * @since   2.0.0
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Core;

use Exception;
use InvalidArgumentException;
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
		if ( $this->get_all() !== array() ) {
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

			// Get legacy options to migrate
			$legacy_options       = apply_filters( 'divi_squad_legacy_memory_options', array( 'disq-settings', 'disq_settings' ) );
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
	public function get_all(): array {
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
	 * @param string $field Field key to check.
	 *
	 * @return bool
	 */
	public function has( string $field ): bool {
		return array_key_exists( $field, $this->data );
	}

	/**
	 * Get a field value.
	 *
	 * @since 2.0.0
	 *
	 * @param string $field   Field key.
	 * @param mixed  $default Optional. Default value if field doesn't exist.
	 *
	 * @return mixed
	 */
	public function get( string $field, $default = null ) { // phpcs:ignore Universal.NamingConventions
		return $this->has( $field ) ? $this->data[ $field ] : $default;
	}

	/**
	 * Set a field value.
	 *
	 * @since 2.0.0
	 *
	 * @param string $field Field key.
	 * @param mixed  $value Field value.
	 *
	 * @return void
	 */
	public function set( string $field, $value ): void {
		if ( ! isset( $this->data[ $field ] ) || $this->data[ $field ] !== $value ) {
			$this->data[ $field ] = $value;
			$this->is_modified    = true;
		}
	}

	/**
	 * Update an existing field.
	 *
	 * @since 2.0.0
	 *
	 * @param string $field Field key.
	 * @param mixed  $value New value.
	 *
	 * @return bool True if updated, false if field doesn't exist.
	 */
	public function update( string $field, $value ): bool {
		if ( $this->has( $field ) ) {
			$this->set( $field, $value );

			return true;
		}

		return false;
	}

	/**
	 * Delete a field.
	 *
	 * @since 2.0.0
	 *
	 * @param string $field Field key.
	 *
	 * @return bool True if deleted, false if field doesn't exist.
	 */
	public function delete( string $field ): bool {
		if ( $this->has( $field ) ) {
			unset( $this->data[ $field ] );
			$this->is_modified = true;

			return true;
		}

		return false;
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
	 * @throws Exception If field is not an array.
	 */
	public function add_to_array( string $field, $value ): void {
		if ( ! isset( $this->data[ $field ] ) ) {
			$this->data[ $field ] = array();
		}

		if ( ! is_array( $this->data[ $field ] ) ) {
			throw new Exception(
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
	 * @throws Exception If field is not an array.
	 */
	public function remove_from_array( string $field, $value ): bool {
		if ( ! isset( $this->data[ $field ] ) || ! is_array( $this->data[ $field ] ) ) {
			throw new Exception(
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
	 * @param string $operation Operation type ('set', 'update', 'delete').
	 * @param string $field Field key.
	 * @param mixed  $value Optional. Value for set/update operations.
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
