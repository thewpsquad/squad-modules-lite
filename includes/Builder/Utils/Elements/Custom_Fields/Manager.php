<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Manager Abstract Class
 *
 * This file contains the Manager abstract class which provides a base
 * implementation for all manager classes in the DiviSquad plugin.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   3.1.1
 */

namespace DiviSquad\Builder\Utils\Elements\Custom_Fields;

/**
 * Class Manager
 *
 * Provides a base implementation for manager classes in the DiviSquad plugin.
 * Managers handle database operations, data retrieval, and caching.
 *
 * @package DiviSquad
 * @since   3.1.1
 */
abstract class Manager implements ManagerInterface {

	/**
	 * Cache group for this manager.
	 *
	 * @var string
	 */
	protected string $cache_group;

	/**
	 * Cache key prefix for this manager.
	 *
	 * @var string
	 */
	protected string $cache_key_prefix;

	/**
	 * Default expiration time for cached data in seconds.
	 *
	 * @var int
	 */
	protected int $cache_expiration = 3600; // 1 hour.

	/**
	 * Last refresh timestamp.
	 *
	 * @var int
	 */
	protected int $last_refresh = 0;

	/**
	 * Constructor.
	 *
	 * @since 3.1.1
	 *
	 * @param string $cache_group      The cache group for this manager.
	 * @param string $cache_key_prefix The cache key prefix for this manager.
	 */
	public function __construct( string $cache_group, string $cache_key_prefix ) {
		$this->cache_group      = $cache_group;
		$this->cache_key_prefix = $cache_key_prefix;

		/**
		 * Filter the cache expiration time.
		 *
		 * @since 3.1.1
		 *
		 * @param int    $cache_expiration The cache expiration time in seconds.
		 * @param string $cache_group      The cache group.
		 * @param string $cache_key_prefix The cache key prefix.
		 */
		$this->cache_expiration = apply_filters(
			'divi_squad_manager_cache_expiration',
			$this->cache_expiration,
			$this->cache_group,
			$this->cache_key_prefix
		);

		$this->last_refresh = (int) get_option( 'divi_squad_' . $this->cache_key_prefix . '_last_refresh', 0 );

		$this->init();
	}

	/**
	 * Get data from the cache or generate it if not cached.
	 *
	 * @since 3.1.1
	 *
	 * @param string   $key        The cache key.
	 * @param callable $callback   The function to generate the data if not cached.
	 * @param array    $args       Arguments to pass to the callback.
	 * @param int|null $expiration Optional. The expiration time of the cached data in seconds.
	 *                             Default is the manager's cache_expiration.
	 * @return mixed The cached or generated data.
	 */
	protected function get_cached_data( string $key, callable $callback, array $args = array(), ?int $expiration = null ) {
		$cache_key = $this->cache_key_prefix . '_' . $key;
		$data      = wp_cache_get( $cache_key, $this->cache_group );

		if ( false === $data ) {
			$data = call_user_func_array( $callback, $args );

			$expiration = $expiration ?? $this->cache_expiration;

			wp_cache_set( $cache_key, $data, $this->cache_group, $expiration );

			/**
			 * Action fired when data is generated and cached.
			 *
			 * @since 3.1.1
			 *
			 * @param string $cache_key  The cache key.
			 * @param mixed  $data       The generated data.
			 * @param string $cache_group The cache group.
			 * @param int    $expiration The cache expiration time in seconds.
			 */
			do_action(
				'divi_squad_manager_data_cached',
				$cache_key,
				$data,
				$this->cache_group,
				$expiration
			);
		}

		return $data;
	}

	/**
	 * Clear the cache for this manager.
	 *
	 * @since 3.1.1
	 *
	 * @return void
	 */
	public function clear_cache(): void {
		// Otherwise do a more targeted approach based on what we know
		wp_cache_delete( $this->cache_key_prefix, $this->cache_group );

		// Also try to find and delete any specific keys
		$cache_keys = wp_cache_get( $this->cache_key_prefix . '_keys', $this->cache_group );
		if ( is_array( $cache_keys ) ) {
			foreach ( $cache_keys as $key ) {
				wp_cache_delete( $key, $this->cache_group );
			}

			wp_cache_delete( $this->cache_key_prefix . '_keys', $this->cache_group );
		}

		/**
		 * Action fired when the cache is cleared.
		 *
		 * @since 3.1.1
		 *
		 * @param string $cache_key_prefix The cache key prefix.
		 * @param string $cache_group      The cache group.
		 */
		do_action( 'divi_squad_manager_cache_cleared', $this->cache_key_prefix, $this->cache_group );
	}

	/**
	 * Get the cache group for this manager.
	 *
	 * @since 3.1.1
	 *
	 * @return string The cache group identifier.
	 */
	public function get_cache_group(): string {
		return $this->cache_group;
	}

	/**
	 * Get the cache key prefix for this manager.
	 *
	 * @since 3.1.1
	 *
	 * @return string The cache key prefix.
	 */
	public function get_cache_key_prefix(): string {
		return $this->cache_key_prefix;
	}

	/**
	 * Refresh stale data in the database.
	 *
	 * @since 3.1.1
	 *
	 * @param bool $force Whether to force a refresh regardless of staleness.
	 * @return bool Whether the refresh was successful.
	 */
	public function refresh_data( bool $force = false ): bool {
		$now              = time();
		$refresh_interval = $this->get_refresh_interval();

		// Only refresh if forced or if it's been long enough since the last refresh
		if ( $force || ( $now - $this->last_refresh ) > $refresh_interval ) {
			$result = $this->do_refresh_data();

			if ( $result ) {
				$this->last_refresh = $now;
				update_option( 'divi_squad_' . $this->cache_key_prefix . '_last_refresh', $now );

				// Clear cache after refresh
				$this->clear_cache();

				/**
				 * Action fired when data is refreshed.
				 *
				 * @since 3.1.1
				 *
				 * @param string $cache_key_prefix The cache key prefix.
				 * @param bool   $force            Whether the refresh was forced.
				 */
				do_action( 'divi_squad_manager_data_refreshed', $this->cache_key_prefix, $force );
			}

			return $result;
		}

		return false;
	}

	/**
	 * Get the refresh interval in seconds.
	 *
	 * @since 3.1.1
	 *
	 * @return int The refresh interval in seconds.
	 */
	protected function get_refresh_interval(): int {
		/**
		 * Filter the refresh interval.
		 *
		 * @since 3.1.1
		 *
		 * @param int    $refresh_interval The refresh interval in seconds. Default 24 hours.
		 * @param string $cache_key_prefix The cache key prefix.
		 * @param string $cache_group      The cache group.
		 */
		return apply_filters(
			'divi_squad_manager_refresh_interval',
			DAY_IN_SECONDS,
			$this->cache_key_prefix,
			$this->cache_group
		);
	}

	/**
	 * Perform the actual data refresh operations.
	 *
	 * Child classes should override this method to implement their specific refresh logic.
	 *
	 * @since 3.1.1
	 *
	 * @return bool Whether the refresh was successful.
	 */
	protected function do_refresh_data(): bool {
		// Default implementation does nothing
		return true;
	}

	/**
	 * Track a cache key for potential bulk deletion.
	 *
	 * @since 3.1.1
	 *
	 * @param string $key The cache key to track.
	 * @return void
	 */
	protected function track_cache_key( string $key ): void {
		$full_key   = $this->cache_key_prefix . '_' . $key;
		$cache_keys = wp_cache_get( $this->cache_key_prefix . '_keys', $this->cache_group );

		if ( ! is_array( $cache_keys ) ) {
			$cache_keys = array();
		}

		if ( ! in_array( $full_key, $cache_keys, true ) ) {
			$cache_keys[] = $full_key;
			wp_cache_set( $this->cache_key_prefix . '_keys', $cache_keys, $this->cache_group, $this->cache_expiration );
		}
	}
}
