<?php
/**
 * Cache class
 *
 * @since   3.2.0
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Core;

use Throwable;

/**
 * Cache Class
 *
 * Handles all caching operations using WordPress Object Cache.
 *
 * @since   3.2.0
 * @package DiviSquad
 */
class Cache {

	/**
	 * Whether we're using an external object cache.
	 *
	 * @var bool
	 */
	private bool $using_external_cache;

	/**
	 * Cache statistics for debugging.
	 *
	 * @var array
	 */
	private array $stats;

	/**
	 * Constructor.
	 */
	public function __construct() {
		try {
			$this->using_external_cache = (bool) wp_using_ext_object_cache();
			$this->stats                = array(
				'hits'    => 0,
				'misses'  => 0,
				'writes'  => 0,
				'deletes' => 0,
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to initialize cache' );
			$this->using_external_cache = false;
			$this->stats                = array(
				'hits'    => 0,
				'misses'  => 0,
				'writes'  => 0,
				'deletes' => 0,
			);
		}
	}

	/**
	 * Get cache value.
	 *
	 * @param string    $key   Cache key.
	 * @param string    $group Optional. Cache group.
	 * @param bool      $force Optional. Force refresh.
	 * @param bool|null &$found Optional. Whether key was found.
	 *
	 * @return mixed|false The cache contents on success, false on failure.
	 */
	public function get( string $key, string $group = 'divi-squad', bool $force = false, ?bool &$found = false ) {
		try {
			$key   = sanitize_key( $key );
			$group = sanitize_key( $group );

			/**
			 * Filters whether to bypass cache get.
			 *
			 * @param bool   $bypass Whether to bypass cache get.
			 * @param string $key    Cache key.
			 * @param string $group  Cache group.
			 */
			if ( apply_filters( 'divi_squad_bypass_cache_get', false, $key, $group ) ) {
				$found = false;

				return false;
			}

			$value = wp_cache_get( $key, $group, $force, $found );

			if ( $found ) {
				++$this->stats['hits'];
			} else {
				++$this->stats['misses'];
			}

			return $value;
		} catch ( Throwable $e ) {
			// Format error message using native WordPress functions.
			$error_message = 'Failed to get cache for key: ' . $key . ' in group: ' . $group;
			divi_squad()->log_error( $e, $error_message );
			$found = false;

			return false;
		}
	}

	/**
	 * Set cache value.
	 *
	 * @param string $key    Cache key.
	 * @param mixed  $value  Value to cache.
	 * @param string $group  Optional. Cache group.
	 * @param int    $expiry Optional. Expiration time in seconds.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function set( string $key, $value, string $group = 'divi-squad', int $expiry = 3600 ): bool {
		try {
			$key   = sanitize_key( $key );
			$group = sanitize_key( $group );

			/**
			 * Filters whether to bypass cache set.
			 *
			 * @param bool   $bypass Whether to bypass cache set.
			 * @param string $key    Cache key.
			 * @param mixed  $value  Value to cache.
			 * @param string $group  Cache group.
			 */
			if ( apply_filters( 'divi_squad_bypass_cache_set', false, $key, $value, $group ) ) {
				return false;
			}

			$result = wp_cache_set( $key, $value, $group, $expiry );

			if ( $result ) {
				++$this->stats['writes'];
			}

			return $result;
		} catch ( Throwable $e ) {
			// Format error message using concatenation.
			$error_message = 'Failed to set cache for key: ' . $key . ' in group: ' . $group;
			divi_squad()->log_error( $e, $error_message );

			return false;
		}
	}

	/**
	 * Delete cache value.
	 *
	 * @param string $key   Cache key.
	 * @param string $group Optional. Cache group.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function delete( string $key, string $group = 'divi-squad' ): bool {
		try {
			$key   = sanitize_key( $key );
			$group = sanitize_key( $group );

			/**
			 * Filters whether to bypass cache delete.
			 *
			 * @param bool   $bypass Whether to bypass cache delete.
			 * @param string $key    Cache key.
			 * @param string $group  Cache group.
			 */
			if ( apply_filters( 'divi_squad_bypass_cache_delete', false, $key, $group ) ) {
				return false;
			}

			$result = wp_cache_delete( $key, $group );

			if ( $result ) {
				++$this->stats['deletes'];
			}

			return $result;
		} catch ( Throwable $e ) {
			$error_message = 'Failed to delete cache for key: ' . $key . ' in group: ' . $group;
			divi_squad()->log_error( $e, $error_message );

			return false;
		}
	}

	/**
	 * Get cache statistics.
	 *
	 * @return array Cache statistics.
	 */
	public function get_stats(): array {
		return $this->stats;
	}

	/**
	 * Reset cache statistics.
	 *
	 * @return void
	 */
	public function reset_stats() {
		$this->stats = array(
			'hits'    => 0,
			'misses'  => 0,
			'writes'  => 0,
			'deletes' => 0,
		);
	}

	/**
	 * Check if using external object cache.
	 *
	 * @return bool True if using external cache, false otherwise.
	 */
	public function is_using_external_cache(): bool {
		return $this->using_external_cache;
	}
}
