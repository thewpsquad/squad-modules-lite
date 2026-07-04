<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Custom Fields Query Manager
 *
 * This file contains the Fields class which handles the management
 * of custom fields in WordPress, including tracking, updating, and retrieving
 * custom field information across different post types.
 *
 * @since   3.1.0
 * @author  The WP Squad <support@squadmodules.com>
 * @package DiviSquad
 */

namespace DiviSquad\Builder\Utils\Elements\Custom_Fields\Managers;

use DiviSquad\Builder\Utils\Database\Database_Utils;
use DiviSquad\Builder\Utils\Elements\Custom_Fields\Manager;
use DiviSquad\Builder\Utils\Elements\Custom_Fields\Managers\Upgrades;
use DiviSquad\Builder\Utils\Elements\Custom_Fields\Traits\Table_Population_Trait;
use DiviSquad\Utils\Divi;
use DiviSquad\Core\Supports\Polyfills\Constant;

/**
 * Fields Class
 *
 * Manages custom fields across different post types in WordPress.
 * Handles field data storage, retrieval, and synchronization with metadata.
 *
 * @since   3.1.1
 * @package DiviSquad
 */
class Fields extends Manager {
	use Table_Population_Trait;

	/**
	 * The name of the summary table in the database.
	 *
	 * @since 3.1.1
	 * @var   string
	 */
	protected string $table_name;

	/**
	 * Array of post types to track custom fields for.
	 *
	 * @since 3.1.1
	 * @var   array<string>
	 */
	protected array $tracked_post_types;

	/**
	 * Instance of the CustomFieldsUpgrader class.
	 *
	 * @since 3.1.1
	 * @var   Upgrades
	 */
	private Upgrades $upgrades;

	/**
	 * Version of the current table structure.
	 *
	 * @since 3.1.1
	 * @var   string
	 */
	private string $table_version = '1.0';

	/**
	 * Constructor.
	 *
	 * @since  3.1.1
	 *
	 * @param array<string> $post_types Array of post types to track custom fields for.
	 */
	public function __construct( array $post_types = array( 'post' ) ) {
		$this->tracked_post_types = $post_types;
		$this->upgrades           = new Upgrades();

		parent::__construct( 'divi-squad-custom_fields', 'custom_field_keys' );
	}

	/**
	 * Initialize the manager
	 *
	 * Sets up tables, hooks, and actions for custom field tracking.
	 *
	 * @since 3.1.1
	 * @return void
	 */
	public function init(): void {
		global $wpdb;
		$this->table_name = "{$wpdb->prefix}divi_squad_custom_fields";

		// Initialize with optimal batch size.
		$this->batch_size = $this->validate_batch_size( $this->get_optimal_batch_size() );

		// Register hooks for table management.
		add_action( 'wp_loaded', array( $this, 'is_table_verified' ) );
		add_action( 'wp_loaded', array( $this, 'check_table_version' ) );

		// Register hooks for custom field management.
		add_action( 'added_post_meta', array( $this, 'update_summary' ), Constant::PHP_INT_MAX, 3 );
		add_action( 'updated_post_meta', array( $this, 'update_summary' ), Constant::PHP_INT_MAX, 3 );
		add_action( 'deleted_post_meta', array( $this, 'delete_from_summary' ), Constant::PHP_INT_MAX, 3 );

		// Population and upgrade hooks.
		if ( is_admin() || Divi::is_fb_enabled() ) {
			add_action( 'shutdown', array( $this, 'populate_summary_table' ), 0 );
		}
		add_action( 'shutdown', array( $this, 'run_upgrades' ), 0 );

		/**
		 * Action fired after the Fields manager is initialized.
		 *
		 * @since 3.1.1
		 *
		 * @param Fields $fields The Fields manager instance.
		 */
		do_action( 'divi_squad_custom_fields_manager_initialized', $this );
	}

	/**
	 * Get data from the manager.
	 *
	 * Retrieves custom field keys based on post type and limit.
	 *
	 * @since  3.1.1
	 *
	 * @param array<string, mixed> $args Optional. Arguments to modify the query.
	 *                                   'post_type' - The post type to get fields for. Default 'post'.
	 *                                   'limit' - Maximum number of fields to return. Default 30.
	 *
	 * @return array<string> The retrieved custom field keys.
	 */
	public function get_data( array $args = array() ): array {
		$defaults = array(
			'post_type' => 'post',
			'limit'     => 30,
		);
		$args     = wp_parse_args( $args, $defaults );

		/**
		 * Filter the arguments for getting custom field data.
		 *
		 * @since 3.1.1
		 * @param array<string, mixed> $args The arguments for getting data.
		 */
		$args = (array) apply_filters( 'divi_squad_custom_fields_get_data_args', $args );

		$cache_key = 'custom_field_keys_' . md5( $args['post_type'] . $args['limit'] );
		$this->track_cache_key( $cache_key );

		return $this->get_cached_data( $cache_key, array( $this, 'get_custom_field_keys' ), array( $args['post_type'], $args['limit'] ) );
	}

	/**
	 * Verify and create table if needed.
	 *
	 * @since  3.1.1
	 * @return bool True if table exists and is valid.
	 */
	public function is_table_verified(): bool {
		if ( ! is_admin() && ! Divi::is_fb_enabled() ) {
			return false;
		}

		if ( $this->is_table_exists() ) {
			return true;
		}

		$result = Database_Utils::verify_and_create_table(
			$this->table_name,
			array(
				'id'           => array(
					'type'           => 'bigint',
					'length'         => 20,
					'unsigned'       => true,
					'nullable'       => false,
					'primary'        => true,
					'auto_increment' => true,
				),
				'meta_key'     => array(
					'type'     => 'varchar',
					'length'   => 255,
					'nullable' => false,
					'index'    => true,
				),
				'post_type'    => array(
					'type'     => 'varchar',
					'length'   => 20,
					'nullable' => false,
					'index'    => true,
				),
				'last_updated' => array(
					'type'      => 'timestamp',
					'nullable'  => false,
					'default'   => 'CURRENT_TIMESTAMP',
					'on_update' => 'CURRENT_TIMESTAMP',
				),
			)
		);

		/**
		 * Action fired after verifying or creating the custom fields table.
		 *
		 * @since 3.1.1
		 *
		 * @param string $table_name The name of the table.
		 * @param bool   $result     Whether the table exists and is valid.
		 */
		do_action( 'divi_squad_custom_fields_table_verified', $this->table_name, $result );

		return $result;
	}

	/**
	 * Check table version and update if needed.
	 *
	 * @since  3.1.1
	 * @return void
	 */
	public function check_table_version(): void {
		$installed_version = divi_squad()->memory->get( 'custom_fields_table_version' );

		if ( $installed_version !== $this->table_version ) {
			divi_squad()->memory->set( 'custom_fields_table_version', $this->table_version );

			/**
			 * Action fired when the custom fields table version is updated.
			 *
			 * @since 3.1.1
			 *
			 * @param string $installed_version The previous version.
			 * @param string $table_version     The new version.
			 */
			do_action( 'divi_squad_custom_fields_table_version_updated', $installed_version, $this->table_version );
		}
	}

	/**
	 * Run database upgrades.
	 *
	 * @since  3.1.1
	 * @return void
	 */
	public function run_upgrades(): void {
		// $this->upgrades->run_upgrades( $this->table_name );
	}

	/**
	 * Update summary table for added/updated postmeta.
	 *
	 * @since  3.1.1
	 *
	 * @param int    $meta_id   Metadata ID.
	 * @param int    $object_id Object ID.
	 * @param string $meta_key  Meta key.
	 * @return void
	 */
	public function update_summary( int $meta_id, int $object_id, string $meta_key ): void {
		global $wpdb;

		// Skip processing for hidden meta keys or if table doesn't exist
		if ( ! $this->is_table_verified() || 0 === strpos( $meta_key, '_' ) ) {
			return;
		}

		$post_type = get_post_type( $object_id );
		if ( ! in_array( $post_type, $this->tracked_post_types, true ) ) {
			return;
		}

		// Check if the meta key has an underscore version (hidden meta)
		$cache_key              = 'divi_squad_has_underscore_version_' . md5( $meta_key );
		$has_underscore_version = wp_cache_get( $cache_key, $this->cache_group );

		if ( false === $has_underscore_version ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$has_underscore_version = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s",
					'_' . $meta_key
				)
			);
			wp_cache_set( $cache_key, $has_underscore_version, $this->cache_group, HOUR_IN_SECONDS );
		}

		if ( $has_underscore_version ) {
			// If there's a hidden version, remove from tracking
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->delete(
				$this->table_name,
				array(
					'meta_key'  => $meta_key,
					'post_type' => $post_type,
				)
			);
		} else {
			// Otherwise, track it
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->replace(
				$this->table_name,
				array(
					'meta_key'  => $meta_key,
					'post_type' => $post_type,
				),
				array( '%s', '%s' )
			);
		}

		// Clear cache for this post type
		$this->clear_post_type_cache( $post_type );
	}

	/**
	 * Delete from summary table when postmeta is deleted.
	 *
	 * @since  3.1.1
	 *
	 * @param array  $meta_ids  Meta IDs being deleted.
	 * @param int    $object_id Object ID.
	 * @param string $meta_key  Meta key.
	 * @return void
	 */
	public function delete_from_summary( array $meta_ids, int $object_id, string $meta_key ): void {
		global $wpdb;

		// Skip processing for hidden meta keys or if table doesn't exist
		if ( ! $this->is_table_verified() || 0 === strpos( $meta_key, '_' ) ) {
			return;
		}

		$post_type = get_post_type( $object_id );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->delete(
			$this->table_name,
			array(
				'meta_key'  => $meta_key,
				'post_type' => $post_type,
			),
			array( '%s', '%s' )
		);

		// Clear cache for this post type
		$this->clear_post_type_cache( $post_type );
	}

	/**
	 * Get custom field keys for a specific post type.
	 *
	 * @since  3.1.1
	 *
	 * @param string $post_type Post type.
	 * @param int    $limit     Results limit.
	 *
	 * @return array<string> Array of custom field keys.
	 */
	public function get_custom_field_keys( string $post_type = 'post', int $limit = 30 ): array {
		global $wpdb;

		if ( ! $this->is_table_verified() ) {
			return array();
		}

		/**
		 * Filter the limit for retrieving custom field keys.
		 *
		 * @since 3.1.1
		 * @param int    $limit     The maximum number of keys to return.
		 * @param string $post_type The post type to get keys for.
		 */
		$limit = (int) apply_filters( 'divi_squad_custom_field_keys_limit', $limit, $post_type );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$results = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT meta_key FROM {$this->table_name} WHERE post_type = %s ORDER BY meta_key LIMIT %d",
				$post_type,
				$limit
			)
		);

		/**
		 * Filter the retrieved custom field keys.
		 *
		 * @since 3.1.1
		 *
		 * @param array<string> $results   The custom field keys.
		 * @param string        $post_type The post type.
		 * @param int           $limit     The maximum number of keys returned.
		 */
		return apply_filters( 'divi_squad_custom_field_keys', $results, $post_type, $limit );
	}

	/**
	 * Clear cache for a specific post type.
	 *
	 * @since 3.1.1
	 *
	 * @param string $post_type The post type to clear cache for.
	 * @return void
	 */
	protected function clear_post_type_cache( string $post_type ): void {
		wp_cache_delete( 'custom_field_keys_' . md5( $post_type . '30' ), $this->cache_group );

		/**
		 * Action fired when the cache for a post type is cleared.
		 *
		 * @since 3.1.1
		 *
		 * @param string $post_type     The post type.
		 * @param string $cache_group   The cache group.
		 */
		do_action( 'divi_squad_custom_fields_post_type_cache_cleared', $post_type, $this->cache_group );
	}

	/**
	 * Perform the actual data refresh operations.
	 *
	 * Refreshes the custom fields data by scanning for new meta keys.
	 *
	 * @since 3.1.1
	 *
	 * @return bool Whether the refresh was successful.
	 */
	protected function do_refresh_data(): bool {
		if ( ! $this->is_table_verified() ) {
			return false;
		}

		// Force a table population as our refresh mechanism
		$this->populate_summary_table();

		return true;
	}

	/**
	 * Get the tracked post types.
	 *
	 * @since 3.1.1
	 *
	 * @return array<string> The post types being tracked.
	 */
	public function get_tracked_post_types(): array {
		return $this->tracked_post_types;
	}

	/**
	 * Get the table version.
	 *
	 * @since 3.1.1
	 *
	 * @return string The current table version.
	 */
	public function get_table_version(): string {
		return $this->table_version;
	}

	/**
	 * Get the table name.
	 *
	 * @since 3.1.1
	 *
	 * @return string The table name.
	 */
	public function get_table_name(): string {
		return $this->table_name;
	}

	/**
	 * Get the upgrader instance.
	 *
	 * @since 3.1.1
	 *
	 * @return Upgrades The upgrader instance.
	 */
	public function get_upgrades(): Upgrades {
		return $this->upgrades;
	}
}
