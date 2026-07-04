<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Builder Utils Class
 *
 * @since      1.5.0
 * @deprecated 3.3.3
 * @package    DiviSquad
 * @author     The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Base\DiviBuilder;

if ( ! class_exists( '\ET_Builder_Module' ) ) {
	return;
}

/**
 * Builder Utils Class
 *
 * @since      1.5.0
 * @deprecated 3.3.3
 * @package    DiviSquad
 */
final class Utils extends Utils\Base {

	/**
	 * Connect with non-static public functions.
	 *
	 * @param Module $element The instance of ET Builder Element (Squad Module).
	 *
	 * @return Utils
	 */
	public static function connect( Module $element ): Utils {
		return new self( $element );
	}
}
