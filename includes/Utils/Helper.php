<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName, WordPress.Files.FileName.NotHyphenatedLowercase

/**
 * Generic helper class for utility.
 *
 * @package DiviSquad
 * @author  WP Squad <support@squadmodules.com>
 * @since   1.0.0
 */

namespace DiviSquad\Utils;

use function divi_squad;
use function get_shortcode_regex;

/**
 * Helper class.
 *
 * @package DiviSquad
 * @since   1.0.0
 */
class Helper {

	/**
	 * Fix slash issue for Windows os
	 *
	 * @param string $path Full path for fixing.
	 *
	 * @return string
	 */
	public static function fix_slash( $path ) {
		// define slash into individual variables.
		$backslash = '\\';
		$slash     = '/';

		// return fixed string.
		if ( PHP_OS === 'WINNT' ) {
			return str_replace( $slash, $backslash, $path );
		}

		return $path;
	}

	/**
	 * Implode array like html attributes.
	 *
	 * @param array $array_data The associate array data.
	 *
	 * @return string
	 */
	public static function implode_assoc_array( $array_data ) {
		$processed_array = array();

		foreach ( $array_data as $key => $value ) {
			// Skip if key is empty
			if ( empty( $key ) ) {
				continue;
			}

			// Handle different value types
			if ( is_object( $value ) ) {
				if ( $value instanceof \WP_Error ) {
					// Skip WP_Error objects
					continue;
				}
				// Handle other objects by using their string representation if possible
				if ( method_exists( $value, '__toString' ) ) {
					$processed_value = (string) $value;
				} else {
					continue;
				}
			} elseif ( is_array( $value ) ) {
				// Convert array to JSON string
				$processed_value = wp_json_encode( $value );
			} elseif ( is_bool( $value ) ) {
				// Convert boolean to string
				$processed_value = $value ? 'true' : 'false';
			} elseif ( is_null( $value ) ) {
				// Skip null values
				continue;
			} else {
				// Handle strings and numbers - don't check if it's a string
				$processed_value = $value;
			}

			// Add to processed array
			$processed_array[] = sprintf(
				'%s="%s"',
				esc_attr( $key ),
				esc_attr( $processed_value )
			);
		}

		return implode( ' ', $processed_array );
	}

	/**
	 * Verify the current screen is a squad page or not.
	 *
	 * @param string $page_id The page id.
	 *
	 * @return bool
	 */
	public static function is_squad_page( $page_id = '' ) {
		$plugin_slug = divi_squad()->get_admin_menu_slug();

		// Get the current screen id if not provided.
		if ( empty( $page_id ) ) {
			if ( is_admin() || ! function_exists( '\get_current_screen' ) ) {
				return false;
			}

			$screen = \get_current_screen();

			return $screen instanceof \WP_Screen && strpos( $screen->id, $plugin_slug ) !== false;
		}

		return strpos( $page_id, $plugin_slug ) !== false;
	}
}
