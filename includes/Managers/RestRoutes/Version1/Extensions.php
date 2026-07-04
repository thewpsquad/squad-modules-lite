<?php // phpcs:ignore WordPress.Files.FileName

/**
 * REST API Routes for Extensions
 *
 * This file contains the Extensions class which handles REST API endpoints
 * for managing Divi Squad extensions.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
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
 * Extensions REST API Route Handler
 *
 * Manages REST API endpoints for Divi Squad extensions, including
 * retrieving available, active, and inactive extensions, as well as
 * updating the list of active extensions.
 *
 * @package DiviSquad
 * @since   1.0.0
 */
class Extensions extends Route {

	/**
	 * Key for active extensions in memory.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const ACTIVE_EXTENSIONS_KEY = 'active_extensions';

	/**
	 * Key for inactive extensions in memory.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const INACTIVE_EXTENSIONS_KEY = 'inactive_extensions';

	/**
	 * Key for active extension version in memory.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const ACTIVE_EXTENSION_VERSION_KEY = 'active_extension_version';

	/**
	 * Get available routes for the Extensions API.
	 *
	 * @since 1.0.0
	 * @return array Available routes for the Extensions API.
	 */
	public function get_routes() {
		return array(
			'/extensions'          => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_extensions' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
				),
			),
			'/extensions/active'   => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_active_extensions' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'update_active_extensions' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
				),
			),
			'/extensions/inactive' => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_inactive_extensions' ),
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
	 * Get all registered extensions.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response Response containing all registered extensions.
	 */
	public function get_extensions( $request ) {
		return rest_ensure_response( divi_squad()->extensions->get_registered_list() );
	}

	/**
	 * Get active extensions list.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response Response containing active extensions.
	 */
	public function get_active_extensions( $request ) {
		return rest_ensure_response( $this->get_extension_names( self::ACTIVE_EXTENSIONS_KEY ) );
	}

	/**
	 * Get inactive extensions list.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response Response containing inactive extensions.
	 */
	public function get_inactive_extensions( $request ) {
		return rest_ensure_response( $this->get_extension_names( self::INACTIVE_EXTENSIONS_KEY ) );
	}

	/**
	 * Get extension names from memory.
	 *
	 * Retrieves either active or inactive extension names from the plugin's memory.
	 *
	 * @since 1.0.0
	 * @param string $key The key to retrieve from memory ('active_extensions' or 'inactive_extensions').
	 * @return array List of extension names.
	 */
	private function get_extension_names( $key ) {
		$current = divi_squad()->memory->get( $key );
		if ( ! is_array( $current ) ) {
			$defaults = $this->get_default_extensions( $key );
			$current  = array_column( $defaults, 'name' );
		}
		return array_values( $current );
	}

	/**
	 * Get default extensions based on the provided key.
	 *
	 * @since 1.0.0
	 * @param string $key The key to determine which default extensions to retrieve.
	 * @return array Default extensions.
	 */
	private function get_default_extensions( $key ) {
		return self::ACTIVE_EXTENSIONS_KEY === $key
			? divi_squad()->extensions->get_default_registries()
			: divi_squad()->extensions->get_inactive_registries();
	}

	/**
	 * Update active extensions list.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response on success, WP_Error on failure.
	 */
	public function update_active_extensions( $request ) {
		$active_extensions = $request->get_json_params();

		if ( ! is_array( $active_extensions ) ) {
			$error_message = esc_html__( 'Invalid parameter: active_extensions must be an array of strings.', 'squad-modules-for-divi' );

			// Send an error report.
			ErrorReport::quick_send(
				new \Exception( $error_message ),
				array(
					'additional_info' => 'An error message from lite extensions rest api.',
				)
			);

			// Send error message to the frontend.
			return new WP_Error(
				'invalid_parameter',
				$error_message,
				array( 'status' => 400 )
			);
		}

		$active_extensions   = array_values( array_map( 'sanitize_text_field', $active_extensions ) );
		$all_extension_names = array_column( divi_squad()->extensions->get_registered_list(), 'name' );
		$invalid_extensions  = array_diff( $active_extensions, $all_extension_names );

		if ( ! empty( $invalid_extensions ) ) {
			$error_message = sprintf(
			/* translators: %s: comma-separated list of invalid module names */
				esc_html__( 'Invalid extension names provided: %s', 'squad-modules-for-divi' ),
				implode( ', ', $invalid_extensions )
			);

			// Send an error report.
			ErrorReport::quick_send(
				new \Exception( $error_message ),
				array(
					'additional_info' => 'An error message from lite extensions rest api.',
				)
			);

			// Send error message to the frontend.
			return new WP_Error(
				'invalid_module',
				sprintf(
				/* translators: %s: comma-separated list of invalid module names */
					esc_html__( 'Invalid module names provided: %s', 'squad-modules-for-divi' ),
					implode( ', ', $invalid_extensions )
				),
				array( 'status' => 400 )
			);
		}

		$inactive_extensions = array_values( array_diff( $all_extension_names, $active_extensions ) );

		$this->update_extension_memory( $active_extensions, $inactive_extensions );

		return rest_ensure_response(
			array(
				'code'    => 'success',
				'message' => __( 'The list of active extensions has been updated.', 'squad-modules-for-divi' ),
			)
		);
	}

	/**
	 * Update extension memory with active and inactive extensions.
	 *
	 * @since 1.0.0
	 * @param array $active_extensions List of active extensions.
	 * @param array $inactive_extensions List of inactive extensions.
	 */
	private function update_extension_memory( $active_extensions, $inactive_extensions ) {
		$memory = divi_squad()->memory;
		$memory->set( self::ACTIVE_EXTENSIONS_KEY, $active_extensions );
		$memory->set( self::INACTIVE_EXTENSIONS_KEY, $inactive_extensions );
		$memory->set( self::ACTIVE_EXTENSION_VERSION_KEY, divi_squad()->get_version() );
	}
}
