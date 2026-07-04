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
	 * @return array<array<int|string>|string>|string
	 */
	public static function sanitize_array( $value ) {
		if ( is_array( $value ) ) {
			return array_map( // @phpstan-ignore-line return.type
				static function ( $item ) {
					return is_array( $item ) ? self::sanitize_array( $item ) : sanitize_text_field( $item );
				},
				$value
			);
		}

		return is_string( $value ) ? sanitize_text_field( $value ) : $value;
	}
}
