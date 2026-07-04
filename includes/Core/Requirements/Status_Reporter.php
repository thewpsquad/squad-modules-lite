<?php
/**
 * Status reporter class.
 *
 * Handles reporting of system requirements status.
 *
 * @since   3.5.0
 * @package DiviSquad\Core\Requirements
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Core\Requirements;

use DiviSquad\Core\Contracts\Hookable;

/**
 * Class Status_Reporter
 *
 * Manages reporting of requirements status.
 *
 * @since   3.5.0
 * @package DiviSquad\Core\Requirements
 */
class Status_Reporter implements Hookable {
	/**
	 * Status checker instance.
	 *
	 * @since 3.5.0
	 *
	 * @var Status_Checker
	 */
	private Status_Checker $status_checker;

	/**
	 * Constructor.
	 *
	 * @since 3.5.0
	 *
	 * @param Status_Checker $status_checker Status checker instance.
	 */
	public function __construct( Status_Checker $status_checker ) {
		$this->status_checker = $status_checker;
	}

	/**
	 * Register hooks for the status reporter.
	 *
	 * @since 3.5.0
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		// Register filters for status reporting.
		add_filter( 'divi_squad_system_status', array( $this, 'add_requirements_status' ), 10, 1 );
		add_filter( 'divi_squad_system_info', array( $this, 'add_requirements_info' ), 10, 1 );
	}

	/**
	 * Add requirements status to system status report.
	 *
	 * @since 3.5.0
	 *
	 * @param array $status_data Existing system status data.
	 *
	 * @return array Updated system status data.
	 */
	public function add_requirements_status( array $status_data ): array {
		$status = $this->status_checker->get_status();

		$status_data['divi_requirements'] = array(
			'label'       => __( 'Divi Requirements', 'squad-modules-for-divi' ),
			'status'      => isset( $status['is_fulfilled'] ) && $status['is_fulfilled'] ? 'good' : 'critical',
			'description' => isset( $status['is_fulfilled'] ) && $status['is_fulfilled']
				? __( 'All Divi requirements are met.', 'squad-modules-for-divi' )
				: __( 'Some Divi requirements are not met.', 'squad-modules-for-divi' ) . ' ' . $this->status_checker->get_last_error(),
		);

		return $status_data;
	}

	/**
	 * Add requirements info to system information report.
	 *
	 * @since 3.5.0
	 *
	 * @param array $info_data Existing system info data.
	 *
	 * @return array Updated system info data.
	 */
	public function add_requirements_info( array $info_data ): array {
		$status = $this->status_checker->get_status();

		$info_data['divi_theme_installed'] = array(
			'label' => __( 'Divi Theme Installed', 'squad-modules-for-divi' ),
			'value' => isset( $status['is_theme_installed'] ) && $status['is_theme_installed'] ? __( 'Yes', 'squad-modules-for-divi' ) : __( 'No', 'squad-modules-for-divi' ),
		);

		$info_data['divi_plugin_installed'] = array(
			'label' => __( 'Divi Builder Plugin Installed', 'squad-modules-for-divi' ),
			'value' => isset( $status['is_plugin_installed'] ) && $status['is_plugin_installed'] ? __( 'Yes', 'squad-modules-for-divi' ) : __( 'No', 'squad-modules-for-divi' ),
		);

		$info_data['divi_theme_active'] = array(
			'label' => __( 'Divi Theme Active', 'squad-modules-for-divi' ),
			'value' => isset( $status['is_theme_active'] ) && $status['is_theme_active'] ? __( 'Yes', 'squad-modules-for-divi' ) : __( 'No', 'squad-modules-for-divi' ),
		);

		$info_data['divi_plugin_active'] = array(
			'label' => __( 'Divi Builder Plugin Active', 'squad-modules-for-divi' ),
			'value' => isset( $status['is_plugin_active'] ) && $status['is_plugin_active'] ? __( 'Yes', 'squad-modules-for-divi' ) : __( 'No', 'squad-modules-for-divi' ),
		);

		$info_data['divi_version'] = array(
			'label' => __( 'Divi Version', 'squad-modules-for-divi' ),
			'value' => isset( $status['is_theme_active'] ) && $status['is_theme_active']
				? ( $status['theme_version'] ?? __( 'Unknown', 'squad-modules-for-divi' ) )
				: ( isset( $status['is_plugin_active'] ) && $status['is_plugin_active']
					? ( $status['plugin_version'] ?? __( 'Unknown', 'squad-modules-for-divi' ) )
					: __( 'Not active', 'squad-modules-for-divi' ) ),
		);

		$info_data['required_divi_version'] = array(
			'label' => __( 'Required Divi Version', 'squad-modules-for-divi' ),
			'value' => $this->status_checker->get_required_version(),
		);

		return $info_data;
	}
}
