<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Manager Interface
 *
 * This file contains the ManagerInterface which defines the contract
 * for all manager classes in the DiviSquad plugin.
 *
 * @since   3.1.1
 * @author  The WP Squad <support@squadmodules.com>
 * @package DiviSquad
 */

namespace DiviSquad\Builder\Utils\Elements\Custom_Fields;

/**
 * Interface ManagerInterface
 *
 * Defines the contract for manager classes in the DiviSquad plugin.
 * Managers handle database operations, data retrieval, and caching.
 *
 * @since   3.1.1
 * @package DiviSquad
 */
interface ManagerInterface {

	/**
	 * Initialize the manager.
	 *
	 * This method should set up any necessary hooks, tables, and initial configurations.
	 *
	 * @since 3.1.1
	 *
	 * @return void
	 */
	public function init(): void;

	/**
	 * Get data from the manager.
	 *
	 * This method should retrieve the main data that the manager is responsible for.
	 *
	 * @since 3.1.1
	 *
	 * @param array<string, mixed> $args Optional. Arguments to modify the query.
	 *
	 * @return array<mixed> The retrieved data.
	 */
	public function get_data( array $args = array() ): array;

	/**
	 * Clear the cache for this manager.
	 *
	 * This method should clear any cached data that the manager maintains.
	 *
	 * @since 3.1.1
	 *
	 * @return void
	 */
	public function clear_cache(): void;

	/**
	 * Get the cache group for this manager.
	 *
	 * @since 3.1.1
	 *
	 * @return string The cache group identifier.
	 */
	public function get_cache_group(): string;

	/**
	 * Get the cache key prefix for this manager.
	 *
	 * @since 3.1.1
	 *
	 * @return string The cache key prefix.
	 */
	public function get_cache_key_prefix(): string;

	/**
	 * Refresh stale data in the database.
	 *
	 * This method should handle any necessary database maintenance operations.
	 *
	 * @since 3.1.1
	 *
	 * @param bool $force Whether to force a refresh regardless of staleness.
	 *
	 * @return bool Whether the refresh was successful.
	 */
	public function refresh_data( bool $force = false ): bool;
}
