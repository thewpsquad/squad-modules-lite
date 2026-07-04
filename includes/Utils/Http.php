<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Http helper class for handling HTTP requests.
 *
 * @since   1.0.0
 * @author  The WP Squad <support@squadmodules.com>
 * @package DiviSquad
 */

namespace DiviSquad\Utils;

/**
 * Http helper class.
 *
 * @since   1.0.0
 * @package DiviSquad
 */
class Http {

	/**
	 * Check if the server is localhost.
	 *
	 * @return bool
	 */
	public static function is_localhost(): bool {
		$server_name = sanitize_key( $_SERVER['SERVER_NAME'] ) ?? ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		return in_array( $server_name, array( 'localhost', '127.0.0.1' ), true ) ||
			   strpos( $server_name, '.local' ) !== false ||
			   strpos( $server_name, '.test' ) !== false ||
			   strpos( $server_name, '192.168' ) !== false;
	}
}
