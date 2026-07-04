<?php // phpcs:ignore WordPress.Files.FileName
/**
 * Sanitization helper class for sanitizing values.
 *
 * @since   1.0.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Utils;

use function sanitize_text_field;

/**
 * Sanitization class.
 *
 * @since   1.0.0
 * @package DiviSquad
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
	 * Recursively sanitizes arrays while preserving scalar types. Strings are
	 * passed through sanitize_text_field(); integers, floats, booleans and null
	 * are returned unchanged so their type and value survive sanitization.
	 *
	 * @param mixed $value Value.
	 *
	 * @return mixed Sanitized value with original scalar types preserved.
	 * @link https://github.com/WordPress/WordPress-Coding-Standards/wiki/Sanitizing-array-input-data
	 */
	public static function sanitize_array( $value ) {
		if ( is_array( $value ) ) {
			return array_map(
				static function ( $item ) {
					return self::sanitize_array( $item );
				},
				$value
			);
		}

		return self::sanitize_value( $value );
	}

	/**
	 * Sanitize a single scalar value according to its type.
	 *
	 * Strings are trimmed and passed through sanitize_text_field(). Integers,
	 * floats, booleans, null and any other non-string scalars are returned
	 * as-is, since they carry no markup and are already type-safe.
	 *
	 * @param mixed $value Scalar value to sanitize.
	 *
	 * @return mixed Sanitized value with its original type preserved.
	 */
	public static function sanitize_value( $value ) {
		if ( is_string( $value ) ) {
			return sanitize_text_field( trim( $value ) );
		}

		return $value;
	}
}
