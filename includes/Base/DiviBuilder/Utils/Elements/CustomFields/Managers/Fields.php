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

namespace DiviSquad\Base\DiviBuilder\Utils\Elements\CustomFields\Managers;

use DiviSquad\Base\DiviBuilder\Utils\Database\DatabaseUtils;
use DiviSquad\Base\DiviBuilder\Utils\Elements\CustomFields\Manager;
use DiviSquad\Base\DiviBuilder\Utils\Elements\CustomFields\Traits\TablePopulationTrait;
use DiviSquad\Utils\Divi;
use DiviSquad\Core\Supports\Polyfills\Constant;

/**
 * Fields Class
 *
 * Manages custom fields across different post types in WordPress.
 *
 * @since   3.1.1
 * @package DiviSquad
 */
class Fields extends Manager {
	use TablePopulationTrait;

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
	 * @var   array
	 */
	protected array $tracked_post_types;

	/**
	 * Instance of the CustomFieldsUpgrader class.
	 *
	 * @since 3.1.1
	 * @var   Upgraders
	 */
	private Upgraders $upgrader;

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
	 * @param array $post_types Array of post types to track custom fields for.
	 */
	public function __construct( array $post_types = array( 'post' ) ) {
		$this->tracked_post_types = $post_types;
		$this->upgrader           = new Upgraders();

		parent::__construct( 'divi-squad-custom_fields', 'custom_field_keys' );
	}

	/**
	 * Initialize the manager
	 *
	 * @since 3.1.1
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
		if ( ( is_admin() || Divi::is_fb_enabled() ) ) {
			add_action( 'shutdown', array( $this, 'populate_summary_table' ), 0 );
		}
		add_action( 'shutdown', array( $this, 'run_upgrades' ), 0 );
	}

	/**
	 * Get data from the manager.
	 *
	 * @since  3.1.1
	 *
	 * @param array $args Optional. Arguments to modify the query.
	 *
	 * @return array The retrieved data.
	 */
	public function get_data( $args = array() ): array {
		$defaults = array(
			'post_type' => 'post',
			'limit'     => 30,
		);
		$args     = wp_parse_args( $args, $defaults );

		$cache_key = 'divi_squad_custom_field_keys_' . md5( $args['post_type'] . $args['limit'] );

		return $this->get_cached_data( $cache_key, array( $this, 'get_custom_field_keys' ), $args );
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

		return DatabaseUtils::verify_and_create_table(
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
	}

	/**
	 * Check table version and update if needed.
	 *
	 * @since  3.1.1
	 */
	public function check_table_version(): void {
		$installed_version = divi_squad()->memory->get( 'custom_fields_table_version' );

		if ( $installed_version !== $this->table_version ) {
			divi_squad()->memory->set( 'custom_fields_table_version', $this->table_version );
		}
	}

	/**
	 * Run database upgrades.
	 *
	 * @since  3.1.1
	 */
	public function run_upgrades(): void {
		$this->upgrader->run_upgrades( $this->table_name );
	}

	/**
	 * Update summary table for added/updated postmeta.
	 *
	 * @since  3.1.1
	 *
	 * @param int    $meta_id   Metadata ID.
	 * @param int    $object_id Object ID.
	 * @param string $meta_key  Meta key.
	 */
	public function update_summary( int $meta_id, int $object_id, string $meta_key ): void {
		global $wpdb;

		if ( ! $this->is_table_verified() || 0 === strpos( $meta_key, '_' ) ) {
			return;
		}

		$post_type = get_post_type( $object_id );
		if ( ! in_array( $post_type, $this->tracked_post_types, true ) ) {
			return;
		}

		$cache_key              = 'divi_squad_has_underscore_version_' . md5( $meta_key );
		$has_underscore_version = wp_cache_get( $cache_key, 'divi-squad-custom_fields' );

		if ( false === $has_underscore_version ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$has_underscore_version = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s",
					'_' . $meta_key
				)
			);
			wp_cache_set( $cache_key, $has_underscore_version, 'divi-squad-custom_fields', HOUR_IN_SECONDS );
		}

		if ( $has_underscore_version ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->delete(
				$this->table_name,
				array(
					'meta_key'  => $meta_key,
					'post_type' => $post_type,
				)
			);
		} else {
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

		wp_cache_delete( 'custom_field_keys_' . md5( $post_type . '30' ), 'divi-squad-custom_fields' );
	}

	/**
	 * Delete from summary table when postmeta is deleted.
	 *
	 * @since  3.1.1
	 *
	 * @param array  $meta_ids  Meta IDs being deleted.
	 * @param int    $object_id Object ID.
	 * @param string $meta_key  Meta key.
	 */
	public function delete_from_summary( array $meta_ids, int $object_id, string $meta_key ): void {
		global $wpdb;

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

		wp_cache_delete( 'custom_field_keys_' . md5( $post_type . '30' ), 'divi-squad-custom_fields' );
	}

	/**
	 * Get custom field keys.
	 *
	 * @since  3.1.1
	 *
	 * @param string $post_type Post type.
	 * @param int    $limit     Results limit.
	 *
	 * @return array
	 */
	public function get_custom_field_keys( string $post_type = 'post', int $limit = 30 ): array {
		global $wpdb;

		if ( ! $this->is_table_verified() ) {
			return array();
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT meta_key FROM {$this->table_name} WHERE post_type = %s ORDER BY meta_key LIMIT %d",
				$post_type,
				$limit
			)
		);
	}
}
