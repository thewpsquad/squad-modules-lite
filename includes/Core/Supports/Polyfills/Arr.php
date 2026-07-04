<?php
/**
 * Array Helper class for utility
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   1.2.3
 */

namespace DiviSquad\Core\Supports\Polyfills;

/**
 * Array Helper class.
 *
 * @package DiviSquad
 * @since   1.2.3
 */
class Arr {

	/**
	 * Polyfill for array_key_first() function added in PHP 7.3.
	 *
	 * Get the first key of the given array without affecting the internal array pointer.
	 *
	 * @param mixed $a An array.
	 *
	 * @return string|int|null The first key of array if the array is not empty; `null` otherwise.
	 */
	public static function key_first( $a ) {
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
	}

	/**
	 * Polyfill for `array_key_last()` function added in PHP 7.3.
	 *
	 * Get the last key of the given array without affecting the internal array pointer.
	 *
	 * @param mixed $a An array.
	 *
	 * @return string|int|null The last key of array if the array is not empty; `null` otherwise.
	 */
	public static function key_last( $a ) {
		if ( empty( $a ) ) {
			return null;
		}

		if ( ! is_array( $a ) ) {
			return $a;
		}

		if ( function_exists( '\array_key_last' ) ) {
			return array_key_last( $a );
		}

		end( $a );

		return key( $a );
	}

	/**
	 * Check current array data is a list
	 *
	 * @param array $a The array data.
	 *
	 * @return bool
	 */
	public static function is_list( array $a ): bool {
		if ( function_exists( '\array_is_list' ) ) {
			return array_is_list( $a );
		}

		if ( array() === $a || array_values( $a ) === $a ) {
			return true;
		}

		$next_key = - 1;

		foreach ( $a as $k => $v ) {
			if ( ++$next_key !== $k ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Simple function to sort an array by a specific key. Maintains index association.
	 *
	 * <code>
	 * print_r(array_sort($people, 'age', SORT_DESC)); // Sort by oldest first
	 * print_r(array_sort($people, 'surname', SORT_ASC)); // Sort by surname
	 * </code>
	 *
	 * @param array  $array_data The input array.
	 * @param string $on         The column number.
	 * @param int    $order      The optional parameter flags may be used to modify the sorting behavior using these values.
	 *
	 * @return array
	 */
	public static function sort( array $array_data, string $on, int $order = SORT_ASC ): array {
		$new_array      = array();
		$sortable_array = array();

		/*
		 *
		 * Sorting type flags:
		 * <ol>
		 * <li> <strong> SORT_ASC </strong> - sort in ascending order. </li>
		 * <li> <strong> SORT_DESC </strong> - sort in descending order.. </li>
		 * <li> <strong> SORT_REGULAR </strong> - compare items normally; the details are described in the comparison operators section. </li>
		 * <li> <strong> SORT_NUMERIC </strong> - compare items numerically. </li>
		 * <li> <strong> SORT_STRING </strong> - compare items as strings. </li>
		 * <li> <strong> SORT_LOCALE_STRING </strong> - compare items as strings, based on the current locale. It uses the locale, which can be changed using setlocale(). </li>
		 * <li> <strong> SORT_NATURAL </strong> - compare items as strings using "natural ordering" like natsort(). </li>
		 * <li> <strong> SORT_FLAG_CASE </strong> - can be combined (bitwise OR) with SORT_STRING or SORT_NATURAL to sort strings case-insensitively. </li>
		 * </ol>
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
			}

			foreach ( $sortable_array as $k => $v ) {
				if ( is_int( $k ) ) {
					$new_array[] = $array_data[ $k ];
				} else {
					$new_array[ $k ] = $array_data[ $k ];
				}
			}
		}

		return $new_array;
	}
}
