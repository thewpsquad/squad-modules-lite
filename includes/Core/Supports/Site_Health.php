<?php
/**
 * Site Health Info.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   3.1.0
 */

namespace DiviSquad\Core\Supports;

use DiviSquad\Core\Supports\Utils\Date_Time;

/**
 * Site Health Info Manager.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   3.1.0
 */
class Site_Health {

	/**
	 * Site_Health constructor.
	 */
	public function __construct() {
		add_filter( 'debug_information', array( $this, 'add_info_section' ) );
	}

	/**
	 * Add section to Info tab.
	 *
	 * @param array $debug_info Array of all information.
	 *
	 * @return array Array with added info section.
	 * @since 3.1.0
	 */
	public function add_info_section( array $debug_info ): array {
		$section = array(
			'label'       => esc_html__( 'Divi Squad', 'squad-modules-for-divi' ),
			'description' => esc_html__( 'The Divi Squad plugin stores some data in the database.', 'squad-modules-for-divi' ),
			'fields'      => $this->get_info_fields(),
		);

		/**
		 * Filter the Divi Squad debug information.
		 *
		 * @since 3.1.0
		 *
		 * @param array $section The Divi Squad debug information.
		 */
		$debug_info['divi-squad'] = apply_filters( 'divi_squad_debug_information', $section );

		return $debug_info;
	}

	/**
	 * Get info fields for the Site Health section.
	 *
	 * @return array
	 */
	private function get_info_fields(): array {
		$activated_time = divi_squad()->memory->get( 'activation_time' );
		$installed_date = Date_Time::datetime_format( $activated_time, '', true );

		$fields = array(
			'version-core'      => array(
				'label' => esc_html__( 'Core Version', 'squad-modules-for-divi' ),
				'value' => divi_squad()->get_version_dot(),
			),
			'install-date-core' => array(
				'label' => esc_html__( 'Core installed date', 'squad-modules-for-divi' ),
				'value' => $installed_date,
			),
		);

		/**
		 * Filter the Divi Squad site health info fields.
		 *
		 * @since 3.2.0
		 *
		 * @param array $fields The Divi Squad site health info fields.
		 */
		return apply_filters( 'divi_squad_site_health_info_fields', $fields );
	}
}
