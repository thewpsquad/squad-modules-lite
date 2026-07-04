<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Array Helper class for utility functions.
 *
 * This file contains polyfill implementations for modern PHP array functions
 * and additional utility methods for array manipulation.
 *
 * @since   1.2.3
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Core\Supports\Polyfills;

use Throwable;

/**
 * Array Helper class.
 *
 * Provides utility methods for array manipulation and polyfills for PHP 7.3+
 * array functions to maintain compatibility with older PHP versions.
 *
 * @since   1.2.3
 * @package DiviSquad
 */
class Arr {

	/**
	 * Polyfill for array_key_first() function added in PHP 7.3.
	 *
	 * Get the first key of the given array without affecting the internal array pointer.
	 *
	 * @since 1.2.3
	 *
	 * @param mixed $a An array.
	 *
	 * @return string|int|null The first key of array if the array is not empty; `null` otherwise.
	 */
	public static function key_first( $a ) {
		try {
			if ( empty( $a ) ) {
				return null;
			}

			if ( ! is_array( $a ) ) {
				return $a;
			}

			if ( function_exists( '\array_key_first' ) ) {
				return \array_key_first( $a );
			}

			// Get the first key of the array.
			$keys = array_keys( $a );

			return $keys[0];
		} catch ( Throwable $e ) {

			return null;
		}
	}

	/**
	 * Polyfill for `array_key_last()` function added in PHP 7.3.
	 *
	 * Get the last key of the given array without affecting the internal array pointer.
	 *
	 * @since 1.2.3
	 *
	 * @param mixed $a An array.
	 *
	 * @return string|int|null The last key of array if the array is not empty; `null` otherwise.
	 */
	public static function key_last( $a ) {
		try {
			if ( empty( $a ) ) {
				return null;
			}

			if ( ! is_array( $a ) ) {
				return $a;
			}

			if ( function_exists( '\array_key_last' ) ) {
				return \array_key_last( $a );
			}

			end( $a );

			return key( $a );
		} catch ( Throwable $e ) {

			return null;
		}
	}

	/**
	 * Check if the given array is a list (sequential integer keys starting from 0).
	 *
	 * Polyfill for `array_is_list()` function added in PHP 8.1.
	 *
	 * @since 1.2.3
	 *
	 * @param array $a The array to check.
	 *
	 * @return bool True if the array is a list, false otherwise.
	 */
	public static function is_list( array $a ): bool {
		try {
			if ( function_exists( '\array_is_list' ) ) {
				return \array_is_list( $a );
			}

			if ( array() === $a || array_values( $a ) === $a ) {
				return true;
			}

			$next_key = - 1;

			foreach ( $a as $k => $v ) {
				if ( ++ $next_key !== $k ) {
					return false;
				}
			}

			return true;
		} catch ( Throwable $e ) {

			return false;
		}
	}

	/**
	 * Sort an array by a specific key while maintaining index association.
	 *
	 * This function provides a simple way to sort an array of arrays or objects
	 * by a specific key or property.
	 *
	 * @since 1.2.3
	 *
	 * @param array  $array_data The input array to sort.
	 * @param string $on         The key to sort by.
	 * @param int    $order      Sort order: SORT_ASC (default) or SORT_DESC.
	 *
	 * @return array The sorted array.
	 */
	public static function sort( array $array_data, string $on, int $order = SORT_ASC ): array {
		try {
			$new_array      = array();
			$sortable_array = array();

			/**
			 * Sorting type flags:
			 * - SORT_ASC - sort in ascending order
			 * - SORT_DESC - sort in descending order
			 * - SORT_REGULAR - compare items normally
			 * - SORT_NUMERIC - compare items numerically
			 * - SORT_STRING - compare items as strings
			 * - SORT_LOCALE_STRING - compare items as strings, based on the current locale
			 * - SORT_NATURAL - compare items as strings using "natural ordering"
			 * - SORT_FLAG_CASE - can be combined with SORT_STRING or SORT_NATURAL for case-insensitive sorting
			 */

			if ( count( $array_data ) > 0 ) {
				foreach ( $array_data as $k => $v ) {
					if ( is_array( $v ) ) {
						foreach ( $v as $k2 => $v2 ) {
							if ( $k2 === $on ) {
								$sortable_array[ $k ] = $v2;
							}
						}
					} else {
						$sortable_array[ $k ] = $v;
					}
				}

				switch ( $order ) {
					case SORT_ASC:
						asort( $sortable_array );
						break;
					case SORT_DESC:
						arsort( $sortable_array );
						break;
					default:
						// Default to ascending order if an invalid order is provided.
						asort( $sortable_array );
						break;
				}

				foreach ( $sortable_array as $k => $v ) {
					if ( is_int( $k ) ) {
						$new_array[] = $array_data[ $k ];
					} else {
						$new_array[ $k ] = $array_data[ $k ];
					}
				}
			}

			/**
			 * Filters the array after sorting.
			 *
			 * @since 3.0.0
			 *
			 * @param array  $new_array  The sorted array.
			 * @param array  $array_data The original input array.
			 * @param string $on         The key used for sorting.
			 * @param int    $order      The sort order used.
			 */
			return apply_filters( 'divi_squad_array_sort_result', $new_array, $array_data, $on, $order );
		} catch ( Throwable $e ) {

			return $array_data; // Return original array on error
		}
	}

	/**
	 * Get a value from an array using "dot" notation.
	 *
	 * @since 3.0.0
	 *
	 * @param array  $array   The array to extract from.
	 * @param string $key     The key to extract using dot notation (e.g., 'parent.child.key').
	 * @param mixed  $default The default value to return if the key doesn't exist.
	 *
	 * @return mixed The extracted value or default if not found.
	 */
	public static function get( array $array, string $key, $default = null ) {
		try {
			if ( isset( $array[ $key ] ) ) {
				return $array[ $key ];
			}

			foreach ( explode( '.', $key ) as $segment ) {
				if ( ! is_array( $array ) || ! array_key_exists( $segment, $array ) ) {
					return $default;
				}

				$array = $array[ $segment ];
			}

			return $array;
		} catch ( Throwable $e ) {

			return $default;
		}
	}

	/**
	 * Check if an array has a specific key using "dot" notation.
	 *
	 * @since 3.0.0
	 *
	 * @param array  $array The array to check.
	 * @param string $key   The key to check using dot notation.
	 *
	 * @return bool True if the key exists, false otherwise.
	 */
	public static function has( array $array, string $key ): bool {
		try {
			if ( empty( $array ) || empty( $key ) ) {
				return false;
			}

			if ( array_key_exists( $key, $array ) ) {
				return true;
			}

			foreach ( explode( '.', $key ) as $segment ) {
				if ( ! is_array( $array ) || ! array_key_exists( $segment, $array ) ) {
					return false;
				}

				$array = $array[ $segment ];
			}

			return true;
		} catch ( Throwable $e ) {

			return false;
		}
	}

	/**
	 * Filter an array by key-value pairs.
	 *
	 * @since 3.0.0
	 *
	 * @param array $array  The input array to filter.
	 * @param array $filter An array of key-value pairs to filter by.
	 *
	 * @return array The filtered array.
	 */
	public static function filter_by( array $array, array $filter ): array {
		try {
			return array_filter(
				$array,
				static function ( $item ) use ( $filter ) {
					foreach ( $filter as $key => $value ) {
						if ( ! isset( $item[ $key ] ) || $item[ $key ] !== $value ) {
							return false;
						}
					}

					return true;
				}
			);
		} catch ( Throwable $e ) {
			return $array; // Return original array on error
		}
	}
}