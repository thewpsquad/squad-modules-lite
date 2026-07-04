<?php // phpcs:ignore WordPress.Files.FileName
/**
 * Generic helper class for utility.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   1.0.0
 */

namespace DiviSquad\Utils;

use WP_Screen;
use function get_current_screen;
use function get_shortcode_regex;

/**
 * Helper class.
 *
 * @package DiviSquad
 * @since   1.0.0
 */
class Helper {

	/**
	 * Remove quotes from string.
	 *
	 * @param array<string>|string $value The array value in string format.
	 *
	 * @return array|string|string[] The clean array.
	 */
	public static function remove_quotes( $value ) {
		$result = str_replace( '"', '', $value );

		/**
		 * Filters the result of removing quotes from a string.
		 *
		 * @since 3.0.0
		 *
		 * @param array|string|string[] $result The string with quotes removed.
		 * @param array<string>|string  $value  The original value.
		 */
		return apply_filters( 'divi_squad_remove_quotes', $result, $value );
	}

	/**
	 * Collect shortcode tags from html content.
	 *
	 * @param string $content The HTML content.
	 *
	 * @return array<string> The shortcode tags list.
	 */
	public static function collect_all_shortcode_tags( string $content ): array {
		$result = array();
		if ( false !== preg_match_all( '@\[([^<>&/\[\]\x00-\x20=]++)@', $content, $matches ) ) {
			$result = array_unique( $matches[1] );
		}

		/**
		 * Filters the collected shortcode tags from HTML content.
		 *
		 * @since 3.0.0
		 *
		 * @param array<string> $result  The shortcode tags list.
		 * @param string        $content The original HTML content.
		 */
		return apply_filters( 'divi_squad_collect_all_shortcode_tags', $result, $content );
	}

	/**
	 * Collect the shortcode list with tag and attributes from content.
	 *
	 * @param string $content The HTML content.
	 *
	 * @return array<array<string>> The shortcode list with tag and attributes.
	 */
	public static function collect_all_shortcodes( string $content ): array {
		$result = array();
		$reg    = get_shortcode_regex();
		if ( false !== preg_match_all( '/' . $reg . '/', $content, $matches, PREG_SET_ORDER ) ) {
			$result = array_map(
				static fn( $v ) => array_values( array_filter( $v, static fn( $item ) => '' !== $item ) ),
				$matches
			);
		}

		/**
		 * Filters the collected shortcodes with tags and attributes.
		 *
		 * @since 3.0.0
		 *
		 * @param array<array<string>> $result  The shortcode list.
		 * @param string               $content The original HTML content.
		 */
		return apply_filters( 'divi_squad_collect_all_shortcodes', $result, $content );
	}

	/**
	 * Fix slash issue for Windows os
	 *
	 * @param string $path Full path for fixing.
	 *
	 * @return string
	 */
	public static function fix_slash( string $path ): string {
		// define slash into individual variables.
		$backslash = '\\';
		$slash     = '/';

		// return fixed string.
		$result = $path;
		if ( PHP_OS === 'WINNT' ) {
			$result = str_replace( $slash, $backslash, $path );
		}

		/**
		 * Filters the path after fixing slash issues for different operating systems.
		 *
		 * @since 3.0.0
		 *
		 * @param string $result The fixed path.
		 * @param string $path   The original path.
		 */
		return apply_filters( 'divi_squad_fix_slash', $result, $path );
	}

	/**
	 * Implode array like html attributes.
	 *
	 * @param array<string, mixed> $array_data The associate array data.
	 *
	 * @return string
	 */
	public static function implode_assoc_array( array $array_data ): string {
		$processed_array = array();

		foreach ( $array_data as $key => $value ) {
			// Skip if key is empty.
			if ( '' === $key ) {
				continue;
			}

			// Handle different value types.
			if ( is_object( $value ) ) {
				if ( $value instanceof \WP_Error ) {
					// Skip WP_Error objects.
					continue;
				}
				// Handle other objects by using their string representation if possible.
				if ( method_exists( $value, '__toString' ) ) {
					$processed_value = (string) $value;
				} else {
					continue;
				}
			} elseif ( is_array( $value ) ) {
				// Convert array to JSON string.
				$processed_value = wp_json_encode( $value );
			} elseif ( is_bool( $value ) ) {
				// Convert boolean to string.
				$processed_value = $value ? 'true' : 'false';
			} elseif ( is_null( $value ) ) {
				// Skip null values.
				continue;
			} else {
				// Handle strings and numbers - don't check if it's a string.
				$processed_value = $value;
			}

			// Add to processed array.
			$processed_array[] = sprintf(
				'%s="%s"',
				esc_attr( $key ),
				esc_attr( $processed_value )
			);
		}

		$result = implode( ' ', $processed_array );

		/**
		 * Filters the result of imploding an associative array as HTML attributes.
		 *
		 * @since 3.0.0
		 *
		 * @param string              $result     The imploded string.
		 * @param array<string>       $processed_array The processed array before imploding.
		 * @param array<string, mixed> $array_data The original array data.
		 */
		return apply_filters( 'divi_squad_implode_assoc_array', $result, $processed_array, $array_data );
	}

	/**
	 * Clean all array values.
	 *
	 * @param string $array_values The array values in string format.
	 *
	 * @return array<string, string> The clean array.
	 */
	public static function clean_array_values( string $array_values ): array {
		$result = array();

		if ( '' !== $array_values ) {
			$key_value_pairs = explode( ' ', $array_values );
			foreach ( $key_value_pairs as $key_value_pair ) {
				[ $key, $value ] = explode( '=', $key_value_pair );
				$result[ $key ]      = $value;
			}
		}

		/**
		 * Filters the result of cleaning array values.
		 *
		 * @since 3.0.0
		 *
		 * @param array<string, string> $result       The cleaned array.
		 * @param string                $array_values The original array values in string format.
		 */
		return apply_filters( 'divi_squad_clean_array_values', $result, $array_values );
	}

	/**
	 * Get Second by days.
	 *
	 * @param int $days Days Number.
	 *
	 * @return int
	 */
	public static function get_second( int $days ): int {
		$result = $days * 24 * 60 * 60;

		/**
		 * Filters the result of converting days to seconds.
		 *
		 * @since 3.0.0
		 *
		 * @param int $result The calculated seconds.
		 * @param int $days   The original number of days.
		 */
		return apply_filters( 'divi_squad_get_second', $result, $days );
	}

	/**
	 * Get days by second.
	 *
	 * @param int $seconds Seconds Number.
	 *
	 * @return int
	 */
	public static function get_days( int $seconds ): int {
		$result = $seconds / 86400;

		/**
		 * Filters the result of converting seconds to days.
		 *
		 * @since 3.0.0
		 *
		 * @param int $result  The calculated days.
		 * @param int $seconds The original number of seconds.
		 */
		return apply_filters( 'divi_squad_get_days', $result, $seconds );
	}

	/**
	 * Verify the current screen is a squad page or not.
	 *
	 * @param string $page_id The page id.
	 *
	 * @return bool
	 */
	public static function is_squad_page( string $page_id = '' ): bool {
		$plugin_slug = divi_squad()->get_admin_menu_slug();

		// Get the current screen id if not provided.
		if ( '' === $page_id ) {
			if ( ! function_exists( '\get_current_screen' ) ) {
				return false;
			}

			$screen = get_current_screen();

			$result = $screen instanceof WP_Screen && strpos( $screen->id, $plugin_slug ) !== false;
		} else {
			$result = strpos( $page_id, $plugin_slug ) !== false;
		}

		/**
		 * Filters whether the current screen is a squad page.
		 *
		 * @since 3.0.0
		 *
		 * @param bool   $result      Whether the current screen is a squad page.
		 * @param string $page_id     The page ID.
		 * @param string $plugin_slug The plugin slug.
		 */
		return apply_filters( 'divi_squad_is_squad_page', $result, $page_id, $plugin_slug );
	}

	/**
	 * Check if the current screen ID is in the array of allowed screen IDs.
	 *
	 * @param array<string> $allowed_screens An array of allowed screen IDs.
	 *
	 * @return bool Whether the current screen is allowed.
	 */
	public static function is_screen_allowed( array $allowed_screens = array() ): bool {
		if ( ! function_exists( '\get_current_screen' ) || 0 === count( $allowed_screens ) ) {
			return false;
		}

		$screen = get_current_screen();

		/**
		 * Filters whether the current screen is allowed.
		 *
		 * @since 3.0.0
		 *
		 * @param bool             $is_allowed      Whether the current screen is allowed.
		 * @param WP_Screen|null   $screen          The current screen.
		 * @param array            $allowed_screens The array of allowed screen IDs.
		 */
		return apply_filters(
			'divi_squad_is_screen_allowed',
			$screen instanceof WP_Screen && in_array( $screen->id, $allowed_screens, true ),
			$screen,
			$allowed_screens
		);
	}
}
