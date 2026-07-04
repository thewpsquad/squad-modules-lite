<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName, WordPress.Files.FileName.NotHyphenatedLowercase

/**
 * REST API Routes for Modules
 *
 * This file contains the Modules class which handles REST API endpoints
 * for managing Divi Squad modules.
 *
 * @package DiviSquad
 * @author  WP Squad <support@squadmodules.com>
 * @since   1.0.0
 */

namespace DiviSquad\Managers\RestRoutes\Version1;

use DiviSquad\Base\Factories\RestRoute\Route;
use DiviSquad\Managers\Emails\ErrorReport;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Modules REST API Route Handler
 *
 * Manages REST API endpoints for Divi Squad modules, including
 * retrieving available, active, and inactive modules, as well as
 * updating the list of active modules.
 *
 * @package DiviSquad
 * @since   1.0.0
 */
class Modules extends Route {

	/**
	 * Key for active modules in memory.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const ACTIVE_MODULES_KEY = 'active_modules';

	/**
	 * Key for inactive modules in memory.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const INACTIVE_MODULES_KEY = 'inactive_modules';

	/**
	 * Key for active module version in memory.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const ACTIVE_MODULE_VERSION_KEY = 'active_module_version';

	/**
	 * Get available routes for the Modules API.
	 *
	 * @since 1.0.0
	 * @return array Available routes for the Modules API.
	 */
	public function get_routes() {
		return array(
			'/modules'          => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_modules' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
				),
			),
			'/modules/active'   => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_active_modules' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'update_active_modules' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
				),
			),
			'/modules/inactive' => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_inactive_modules' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
				),
			),
		);
	}

	/**
	 * Check if the current user has admin permissions.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has admin access, WP_Error object otherwise.
	 */
	public function check_admin_permissions( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permissions to perform this action.', 'squad-modules-for-divi' ),
				array( 'status' => 403 )
			);
		}
		return true;
	}

	/**
	 * Get all registered modules.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response Response containing all registered modules.
	 */
	public function get_modules( $request ) {
		$modules = divi_squad()->modules->get_all_modules_with_locked();
		$modules = array_map( array( $this, 'format_module' ), $modules );
		return rest_ensure_response( $modules );
	}

	/**
	 * Get active modules list.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response Response containing active modules.
	 */
	public function get_active_modules( $request ) {
		return rest_ensure_response( $this->get_module_names( self::ACTIVE_MODULES_KEY ) );
	}

	/**
	 * Get inactive modules list.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response Response containing inactive modules.
	 */
	public function get_inactive_modules( $request ) {
		return rest_ensure_response( $this->get_module_names( self::INACTIVE_MODULES_KEY ) );
	}

	/**
	 * Get module names from memory.
	 *
	 * Retrieves either active or inactive module names from the plugin's memory.
	 *
	 * @since 1.0.0
	 * @param string $key The key to retrieve from memory ('active_modules' or 'inactive_modules').
	 * @return array List of module names.
	 */
	private function get_module_names( $key ) {
		$current = divi_squad()->memory->get( $key );
		if ( ! is_array( $current ) ) {
			$defaults = $this->get_default_modules( $key );
			$current  = array_column( $defaults, 'name' );
		}
		return array_values( $current );
	}

	/**
	 * Get default modules based on the provided key.
	 *
	 * @since 1.0.0
	 * @param string $key The key to determine which default modules to retrieve.
	 * @return array Default modules.
	 */
	private function get_default_modules( $key ) {
		return self::ACTIVE_MODULES_KEY === $key
			? divi_squad()->modules->get_default_registries()
			: divi_squad()->modules->get_inactive_registries();
	}

	/**
	 * Update active modules list.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response on success, WP_Error on failure.
	 */
	public function update_active_modules( $request ) {
		$active_modules = $request->get_json_params();

		if ( ! is_array( $active_modules ) ) {
			$error_message = esc_html__( 'Invalid parameter: active_modules must be an array of strings.', 'squad-modules-for-divi' );

			// Send an error report.
			ErrorReport::quick_send(
				new \Exception( $error_message ),
				array(
					'additional_info' => 'An error message from lite modules rest api.',
				)
			);

			// Send error message to the frontend.
			return new WP_Error(
				'invalid_parameter',
				$error_message,
				array( 'status' => 400 )
			);
		}

		$active_modules   = array_values( array_map( 'sanitize_text_field', $active_modules ) );
		$all_module_names = array_column( divi_squad()->modules->get_registered_list(), 'name' );
		$invalid_modules  = array_diff( $active_modules, $all_module_names );

		if ( ! empty( $invalid_modules ) ) {
			$error_message = sprintf(
			/* translators: %s: comma-separated list of invalid module names */
				esc_html__( 'Invalid module names provided: %s', 'squad-modules-for-divi' ),
				implode( ', ', $invalid_modules )
			);

			// Send an error report.
			ErrorReport::quick_send(
				new \Exception( $error_message ),
				array(
					'additional_info' => 'An error message from lite modules rest api.',
				)
			);

			// Send error message to the frontend.
			return new WP_Error(
				'invalid_module',
				$error_message,
				array( 'status' => 400 )
			);
		}

		$inactive_modules = array_values( array_diff( $all_module_names, $active_modules ) );

		$this->update_module_memory( $active_modules, $inactive_modules );

		return rest_ensure_response(
			array(
				'code'    => 'success',
				'message' => __( 'The list of active modules has been updated.', 'squad-modules-for-divi' ),
			)
		);
	}

	/**
	 * Update module memory with active and inactive modules.
	 *
	 * @since 1.0.0
	 * @param array $active_modules List of active modules.
	 * @param array $inactive_modules List of inactive modules.
	 */
	private function update_module_memory( $active_modules, $inactive_modules ) {
		$memory = divi_squad()->memory;
		$memory->set( self::ACTIVE_MODULES_KEY, $active_modules );
		$memory->set( self::INACTIVE_MODULES_KEY, $inactive_modules );
		$memory->set( self::ACTIVE_MODULE_VERSION_KEY, divi_squad()->get_version() );
	}

	/**
	 * Format a single module's data.
	 *
	 * @since 1.0.0
	 * @param array $module Raw module data.
	 * @return array Formatted module data.
	 */
	private function format_module( $module ) {
		return array(
			'name'               => isset( $module['name'] ) ? $module['name'] : '',
			'label'              => isset( $module['label'] ) ? $module['label'] : '',
			'description'        => isset( $module['description'] ) ? $module['description'] : '',
			'release_version'    => isset( $module['release_version'] ) ? $module['release_version'] : '',
			'last_modified'      => isset( $module['last_modified'] ) ? $module['last_modified'] : array(),
			'is_default_active'  => isset( $module['is_default_active'] ) ? $module['is_default_active'] : false,
			'is_premium_feature' => isset( $module['is_premium_feature'] ) ? $module['is_premium_feature'] : false,
			'type'               => isset( $module['type'] ) ? $module['type'] : '',
			'settings_route'     => isset( $module['settings_route'] ) ? $module['settings_route'] : '',
			'required'           => isset( $module['required'] ) ? $module['required'] : array(),
			'category'           => isset( $module['category'] ) ? $module['category'] : '',
			'category_title'     => isset( $module['category_title'] ) ? $module['category_title'] : '',
		);
	}
}
