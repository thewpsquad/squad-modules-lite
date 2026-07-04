<?php
/**
 * Sanitization helper class for sanitizing values.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   1.0.0
 */

namespace DiviSquad\Utils;

use function sanitize_text_field;

/**
 * Sanitization class.
 *
 * @package DiviSquad
 * @since   1.0.0
 */
class Sanitization {
	/**
	 * Sanitize int value.
	 *
	 * @param int|mixed $value Value.
	 *
	 * @return int
	 */
	public static function sanitize_int( $value ): int {
		return absint( $value );
	}

	/**
	 * Sanitize array value
	 *
	 * @param mixed $value Value.
	 *
	 * @link https://github.com/WordPress/WordPress-Coding-Standards/wiki/Sanitizing-array-input-data
	 *
	 * @return array|string
	 */
	public static function sanitize_array( $value ) {
		if ( is_array( $value ) ) {
			return array_map( array( self::class, 'sanitize_array' ), $value );
		}

		return is_scalar( $value ) ? sanitize_text_field( $value ) : $value;
	}
}
