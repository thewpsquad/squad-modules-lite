<?php // phpcs:ignore WordPress.Files.FileName

/**
 * REST API Routes for Modules
 *
 * This file contains the Modules class which handles REST API endpoints
 * for managing Divi Squad modules.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   1.0.0
 */

namespace DiviSquad\Rest_API_Routes\Version1;

use DiviSquad\Emails\ErrorReport;
use DiviSquad\Rest_API_Routes\Base_Route;
use Exception;
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
class Modules extends Base_Route {

	/**
	 * Key for active modules in memory.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public const ACTIVE_MODULES_KEY = 'active_modules';

	/**
	 * Key for inactive modules in memory.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public const INACTIVE_MODULES_KEY = 'inactive_modules';

	/**
	 * Key for active module version in memory.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public const ACTIVE_MODULE_VERSION_KEY = 'active_module_version';

	/**
	 * Get available routes for the Modules API.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array<int, array<string, list<$this|string>|string>>>
	 */
	public function get_routes(): array {
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
	 *
	 * @return bool|WP_Error True if the request has admin access, WP_Error object otherwise.
	 */
	public function check_admin_permissions() {
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
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response Response containing all registered modules.
	 */
	public function get_modules( WP_REST_Request $request ): WP_REST_Response {
		$modules = divi_squad()->modules->get_all_modules();
		$modules = array_map( array( $this, 'format_module' ), $modules );

		return rest_ensure_response( array_values( $modules ) );
	}

	/**
	 * Get active modules list.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response Response containing active modules.
	 */
	public function get_active_modules( WP_REST_Request $request ): WP_REST_Response {
		$active_modules = $this->get_module_names( static::ACTIVE_MODULES_KEY );

		return rest_ensure_response( $active_modules );
	}

	/**
	 * Get inactive modules list.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response Response containing inactive modules.
	 */
	public function get_inactive_modules( WP_REST_Request $request ): WP_REST_Response {
		$inactive_modules = $this->get_module_names( static::INACTIVE_MODULES_KEY );

		return rest_ensure_response( $inactive_modules );
	}

	/**
	 * Get module names from memory.
	 *
	 * Retrieves either active or inactive module names from the plugin's memory.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key The key to retrieve from memory ('active_modules' or 'inactive_modules').
	 *
	 * @return array<string> List of module names.
	 */
	protected function get_module_names( string $key ): array {
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
	 *
	 * @param string $key The key to determine which default modules to retrieve.
	 *
	 * @return array<string, mixed> List of default modules.
	 */
	private function get_default_modules( string $key ): array {
		return static::ACTIVE_MODULES_KEY === $key
			? divi_squad()->modules->get_default_registries()
			: divi_squad()->modules->get_inactive_registries();
	}

	/**
	 * Update active modules list.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response on success, WP_Error on failure.
	 */
	public function update_active_modules( WP_REST_Request $request ) {
		try {
			$active_modules = $request->get_json_params();

			// Validate input
			if ( ! is_array( $active_modules ) ) {
				return new WP_Error(
					'invalid_data',
					esc_html__( 'Invalid data format. Expected array of module names.', 'squad-modules-for-divi' ),
					array( 'status' => 400 )
				);
			}

			// Sanitize and validate module names
			$active_modules   = array_values( array_map( 'sanitize_text_field', $active_modules ) );
			$all_module_names = array_column( divi_squad()->modules->get_registered_list(), 'name' );
			$invalid_modules  = array_diff( $active_modules, $all_module_names );

			if ( count( $invalid_modules ) > 0 ) {
				$error_message = sprintf(
				/* translators: %s: comma-separated list of invalid module names */
					esc_html__( 'Invalid module names provided: %s', 'squad-modules-for-divi' ),
					implode( ', ', $invalid_modules )
				);

				// Send an error report.
				divi_squad()->log_error(
					new Exception( $error_message ),
					'An error message from lite modules rest api.'
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
		} catch ( Exception $e ) {
			divi_squad()->log_error( $e, 'Failed to update active modules' );

			return new WP_Error(
				'update_failed',
				__( 'Failed to update active modules. Please try again.', 'squad-modules-for-divi' ),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Update module memory with active and inactive modules.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string> $active_modules   List of active modules.
	 * @param array<string> $inactive_modules List of inactive modules.
	 *
	 * @return void
	 */
	protected function update_module_memory( array $active_modules, array $inactive_modules ): void {
		divi_squad()->memory->set( static::ACTIVE_MODULES_KEY, $active_modules );
		divi_squad()->memory->set( static::INACTIVE_MODULES_KEY, $inactive_modules );
		divi_squad()->memory->set( static::ACTIVE_MODULE_VERSION_KEY, divi_squad()->get_version() );
	}

	/**
	 * Format a single module's data.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $module Module data to format.
	 *
	 * @return array<string, mixed> Formatted module data.
	 */
	protected function format_module( array $module ): array {
		return array(
			'name'               => $module['name'] ?? '',
			'label'              => $module['label'] ?? '',
			'description'        => $module['description'] ?? '',
			'release_version'    => $module['release_version'] ?? '',
			'last_modified'      => $module['last_modified'] ?? array(),
			'is_default_active'  => $module['is_default_active'] ?? false,
			'is_premium_feature' => $module['is_premium_feature'] ?? false,
			'type'               => $module['type'] ?? '',
			'settings_route'     => $module['settings_route'] ?? '',
			'required'           => $module['required'] ?? array(),
			'category'           => $module['category'] ?? '',
			'category_title'     => $module['category_title'] ?? '',
		);
	}
}
