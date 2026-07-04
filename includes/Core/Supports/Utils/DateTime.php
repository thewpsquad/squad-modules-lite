<?php
/**
 * Helper functions to work with dates, time and timezones.
 *
 * @package DiviSquad
 * @author The WP Squad <support@squadmodules.com>
 * @since 3.1.0
 */

namespace DiviSquad\Core\Supports\Utils;

/**
 * Helper functions to work with dates, time and timezones.
 *
 * @package DiviSquad
 * @since 3.1.0
 */
class DateTime {

	/**
	 * Return date and time formatted as expected.
	 *
	 * @since 1.6.3
	 *
	 * @param string|int $date       Date to format.
	 * @param string     $format     Optional. Format for the date and time.
	 * @param bool       $gmt_offset Optional. GTM offset.
	 *
	 * @return string
	 */
	public static function datetime_format( $date, string $format = '', bool $gmt_offset = false ): string {
		if ( is_numeric( $date ) ) {
			$date = (int) $date;
		}

		if ( is_string( $date ) ) {
			$date = strtotime( $date );
		}

		if ( $gmt_offset ) {
			$date += (int) ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
		}

		if ( '' === $format ) {
			return sprintf( /* translators: %1$s - formatted date, %2$s - formatted time. */
				__( '%1$s at %2$s', 'squad-modules-for-divi' ),
				date_i18n( get_option( 'date_format' ), $date ),
				date_i18n( get_option( 'time_format' ), $date )
			);
		}

		return date_i18n( $format, $date );
	}
}
