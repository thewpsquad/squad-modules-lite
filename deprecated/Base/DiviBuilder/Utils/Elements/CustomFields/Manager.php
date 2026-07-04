<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Manager Class
 *
 * This file contains the AbstractManager class which provides a base
 * implementation for all manager classes in the DiviSquad plugin.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   3.1.1
 * @deprecated 3.3.0
 */

namespace DiviSquad\Base\DiviBuilder\Utils\Elements\CustomFields;

/**
 * Class Manager
 *
 * Provides a base implementation for manager classes in the DiviSquad plugin.
 *
 * @package DiviSquad
 * @since   3.1.1
 * @deprecated 3.3.0
 */
abstract class Manager implements ManagerInterface {

	/**
	 * Cache group for this manager.
	 *
	 * @var string
	 */
	protected $cache_group;

	/**
	 * Cache key prefix for this manager.
	 *
	 * @var string
	 */
	protected $cache_key_prefix;

	/**
	 * Constructor.
	 *
	 * @since 3.1.1
	 *
	 * @param string $cache_group The cache group for this manager.
	 * @param string $cache_key_prefix The cache key prefix for this manager.
	 */
	public function __construct( $cache_group, $cache_key_prefix ) {
		$this->cache_group      = $cache_group;
		$this->cache_key_prefix = $cache_key_prefix;
		$this->init();
	}

	/**
	 * Get data from the cache or generate it if not cached.
	 *
	 * @since 3.1.1
	 *
	 * @param string   $key        The cache key.
	 * @param callable $callback   The function to generate the data if not cached.
	 * @param int      $expiration Optional. The expiration time of the cached data in seconds. Default 3600.
	 * @return mixed The cached or generated data.
	 */
	protected function get_cached_data( $key, $callback, $args = array(), $expiration = 3600 ) {
		$cache_key = $this->cache_key_prefix . '_' . $key;
		$data      = wp_cache_get( $cache_key, $this->cache_group );

		if ( false === $data ) {
			$data = call_user_func_array( $callback, $args );
			wp_cache_set( $cache_key, $data, $this->cache_group, $expiration );
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
	public function clear_cache() {
		wp_cache_delete( $this->cache_key_prefix, $this->cache_group );
	}
}
