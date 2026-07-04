<?php // phpcs:ignore WordPress.Files.FileName

/**
 * REST API Routes for Modules
 *
 * This file contains the Modules class which handles REST API endpoints
 * for managing Divi Squad modules.
 *
 * @since   1.0.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Rest_API_Routes\Version1;

use DiviSquad\Rest_API_Routes\Base_Route;
use Exception;
use Throwable;
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
 * @since   1.0.0
 * @package DiviSquad
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
		$routes = array(
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

		/**
		 * Filters the module routes configuration.
		 *
		 * @since 1.0.0
		 *
		 * @param array $routes         The routes configuration array.
		 * @param self  $route_instance The current Modules API instance.
		 */
		return apply_filters( 'divi_squad_rest_module_routes', $routes, $this );
	}

	/**
	 * Check if the current user has admin permissions.
	 *
	 * @since 1.0.0
	 *
	 * @return bool|WP_Error True if the request has admin access, WP_Error object otherwise.
	 */
	public function check_admin_permissions() {
		try {
			/**
			 * Filters the capability required to access module endpoints.
			 *
			 * @since 1.0.0
			 *
			 * @param string $capability     The capability name.
			 * @param self   $route_instance The current Modules API instance.
			 */
			$capability = apply_filters( 'divi_squad_rest_module_capability', 'manage_options', $this );

			if ( ! current_user_can( $capability ) ) {
				/**
				 * Filters the error message when a user doesn't have permission to access the modules.
				 *
				 * @since 1.0.0
				 *
				 * @param string $error_message  The error message.
				 * @param string $capability     The capability that was checked.
				 * @param self   $route_instance The current Modules API instance.
				 */
				$error_message = apply_filters(
					'divi_squad_rest_module_permission_error_message',
					__( 'You do not have permissions to perform this action.', 'squad-modules-for-divi' ),
					$capability,
					$this
				);

				return new WP_Error(
					'rest_forbidden',
					$error_message,
					array( 'status' => 403 )
				);
			}

			/**
			 * Fires after a successful permission check for modules access.
			 *
			 * @since 1.0.0
			 *
			 * @param self $route_instance The current Modules API instance.
			 */
			do_action( 'divi_squad_rest_module_permission_check_passed', $this );

			return true;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Error checking module permissions' );

			/**
			 * Filters the error response when permission checking fails.
			 *
			 * @since 1.0.0
			 *
			 * @param WP_Error  $error          The error object.
			 * @param Throwable $e              The exception that was thrown.
			 * @param self      $route_instance The current Modules API instance.
			 */
			return apply_filters(
				'divi_squad_rest_module_permission_error_response',
				new WP_Error(
					'rest_permission_check_error',
					__( 'An error occurred while checking permissions.', 'squad-modules-for-divi' ),
					array( 'status' => 500 )
				),
				$e,
				$this
			);
		}
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
		try {
			/**
			 * Fires before retrieving all modules.
			 *
			 * @since 1.0.0
			 *
			 * @param WP_REST_Request $request        The request object.
			 * @param self            $route_instance The current Modules API instance.
			 */
			do_action( 'divi_squad_rest_before_get_modules', $request, $this );

			$modules = divi_squad()->modules->get_all_modules();
			$modules = array_map( array( $this, 'format_module' ), $modules );

			/**
			 * Filters the modules list before returning the response.
			 *
			 * @since 1.0.0
			 *
			 * @param array           $modules        The formatted modules.
			 * @param WP_REST_Request $request        The request object.
			 * @param self            $route_instance The current Modules API instance.
			 */
			$modules = apply_filters( 'divi_squad_rest_modules_list', $modules, $request, $this );

			$response = rest_ensure_response( array_values( $modules ) );

			/**
			 * Fires after retrieving all modules.
			 *
			 * @since 1.0.0
			 *
			 * @param WP_REST_Response $response       The response object.
			 * @param array            $modules        The formatted modules.
			 * @param WP_REST_Request  $request        The request object.
			 * @param self             $route_instance The current Modules API instance.
			 */
			do_action( 'divi_squad_rest_after_get_modules', $response, $modules, $request, $this );

			return $response;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to get modules list' );

			/**
			 * Fires when an error occurs during retrieving all modules.
			 *
			 * @since 1.0.0
			 *
			 * @param Throwable       $e              The exception that was thrown.
			 * @param WP_REST_Request $request        The request object.
			 * @param self            $route_instance The current Modules API instance.
			 */
			do_action( 'divi_squad_rest_get_modules_error', $e, $request, $this );

			// Return empty array to avoid breaking the frontend
			return rest_ensure_response( array() );
		}
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
		try {
			/**
			 * Fires before retrieving active modules.
			 *
			 * @since 1.0.0
			 *
			 * @param WP_REST_Request $request        The request object.
			 * @param self            $route_instance The current Modules API instance.
			 */
			do_action( 'divi_squad_rest_before_get_active_modules', $request, $this );

			$active_modules = $this->get_module_names( static::ACTIVE_MODULES_KEY );

			/**
			 * Filters the active modules list before returning the response.
			 *
			 * @since 1.0.0
			 *
			 * @param array           $active_modules The active modules.
			 * @param WP_REST_Request $request        The request object.
			 * @param self            $route_instance The current Modules API instance.
			 */
			$active_modules = apply_filters( 'divi_squad_rest_active_modules_list', $active_modules, $request, $this );

			$response = rest_ensure_response( $active_modules );

			/**
			 * Fires after retrieving active modules.
			 *
			 * @since 1.0.0
			 *
			 * @param WP_REST_Response $response       The response object.
			 * @param array            $active_modules The active modules.
			 * @param WP_REST_Request  $request        The request object.
			 * @param self             $route_instance The current Modules API instance.
			 */
			do_action( 'divi_squad_rest_after_get_active_modules', $response, $active_modules, $request, $this );

			return $response;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to get active modules list' );

			/**
			 * Fires when an error occurs during retrieving active modules.
			 *
			 * @since 1.0.0
			 *
			 * @param Throwable       $e              The exception that was thrown.
			 * @param WP_REST_Request $request        The request object.
			 * @param self            $route_instance The current Modules API instance.
			 */
			do_action( 'divi_squad_rest_get_active_modules_error', $e, $request, $this );

			// Return empty array to avoid breaking the frontend
			return rest_ensure_response( array() );
		}
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
		try {
			/**
			 * Fires before retrieving inactive modules.
			 *
			 * @since 1.0.0
			 *
			 * @param WP_REST_Request $request        The request object.
			 * @param self            $route_instance The current Modules API instance.
			 */
			do_action( 'divi_squad_rest_before_get_inactive_modules', $request, $this );

			$inactive_modules = $this->get_module_names( static::INACTIVE_MODULES_KEY );

			/**
			 * Filters the inactive modules list before returning the response.
			 *
			 * @since 1.0.0
			 *
			 * @param array           $inactive_modules The inactive modules.
			 * @param WP_REST_Request $request          The request object.
			 * @param self            $route_instance   The current Modules API instance.
			 */
			$inactive_modules = apply_filters( 'divi_squad_rest_inactive_modules_list', $inactive_modules, $request, $this );

			$response = rest_ensure_response( $inactive_modules );

			/**
			 * Fires after retrieving inactive modules.
			 *
			 * @since 1.0.0
			 *
			 * @param WP_REST_Response $response         The response object.
			 * @param array            $inactive_modules The inactive modules.
			 * @param WP_REST_Request  $request          The request object.
			 * @param self             $route_instance   The current Modules API instance.
			 */
			do_action( 'divi_squad_rest_after_get_inactive_modules', $response, $inactive_modules, $request, $this );

			return $response;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to get inactive modules list' );

			/**
			 * Fires when an error occurs during retrieving inactive modules.
			 *
			 * @since 1.0.0
			 *
			 * @param Throwable       $e              The exception that was thrown.
			 * @param WP_REST_Request $request        The request object.
			 * @param self            $route_instance The current Modules API instance.
			 */
			do_action( 'divi_squad_rest_get_inactive_modules_error', $e, $request, $this );

			// Return empty array to avoid breaking the frontend
			return rest_ensure_response( array() );
		}
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
		try {
			/**
			 * Fires before retrieving module names from memory.
			 *
			 * @since 1.0.0
			 *
			 * @param string $key            The memory key.
			 * @param self   $route_instance The current Modules API instance.
			 */
			do_action( 'divi_squad_rest_before_get_module_names', $key, $this );

			$current = divi_squad()->memory->get( $key );
			if ( ! is_array( $current ) ) {
				$defaults = $this->get_default_modules( $key );
				$current  = array_column( $defaults, 'name' );
			}

			$module_names = array_values( $current );

			/**
			 * Filters the module names retrieved from memory.
			 *
			 * @since 1.0.0
			 *
			 * @param array  $module_names   The module names.
			 * @param string $key            The memory key.
			 * @param self   $route_instance The current Modules API instance.
			 */
			$module_names = apply_filters( 'divi_squad_rest_module_names', $module_names, $key, $this );

			/**
			 * Fires after retrieving module names from memory.
			 *
			 * @since 1.0.0
			 *
			 * @param array  $module_names   The module names.
			 * @param string $key            The memory key.
			 * @param self   $route_instance The current Modules API instance.
			 */
			do_action( 'divi_squad_rest_after_get_module_names', $module_names, $key, $this );

			return $module_names;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to get module names from memory for key: ' . $key );

			/**
			 * Fires when an error occurs during retrieving module names from memory.
			 *
			 * @since 1.0.0
			 *
			 * @param Throwable $e              The exception that was thrown.
			 * @param string    $key            The memory key.
			 * @param self      $route_instance The current Modules API instance.
			 */
			do_action( 'divi_squad_rest_get_module_names_error', $e, $key, $this );

			// Return empty array to avoid further errors
			return array();
		}
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
		try {
			$defaults = static::ACTIVE_MODULES_KEY === $key
				? divi_squad()->modules->get_default_registries()
				: divi_squad()->modules->get_inactive_registries();

			/**
			 * Filters the default modules based on the provided key.
			 *
			 * @since 1.0.0
			 *
			 * @param array  $defaults       The default modules.
			 * @param string $key            The memory key.
			 * @param self   $route_instance The current Modules API instance.
			 */
			return apply_filters( 'divi_squad_rest_default_modules', $defaults, $key, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to get default modules for key: ' . $key );

			/**
			 * Fires when an error occurs during retrieving default modules.
			 *
			 * @since 1.0.0
			 *
			 * @param Throwable $e              The exception that was thrown.
			 * @param string    $key            The memory key.
			 * @param self      $route_instance The current Modules API instance.
			 */
			do_action( 'divi_squad_rest_get_default_modules_error', $e, $key, $this );

			// Return empty array to avoid further errors
			return array();
		}
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
			/**
			 * Fires before updating active modules.
			 *
			 * @since 1.0.0
			 *
			 * @param WP_REST_Request $request        The request object.
			 * @param self            $route_instance The current Modules API instance.
			 */
			do_action( 'divi_squad_rest_before_update_active_modules', $request, $this );

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

			/**
			 * Filters the active modules list before updating.
			 *
			 * @since 1.0.0
			 *
			 * @param array           $active_modules   The active modules.
			 * @param array           $all_module_names All module names.
			 * @param WP_REST_Request $request          The request object.
			 * @param self            $route_instance   The current Modules API instance.
			 */
			$active_modules = apply_filters(
				'divi_squad_rest_update_active_modules',
				$active_modules,
				$all_module_names,
				$request,
				$this
			);

			$inactive_modules = array_values( array_diff( $all_module_names, $active_modules ) );

			/**
			 * Filters the inactive modules list before updating.
			 *
			 * @since 1.0.0
			 *
			 * @param array           $inactive_modules The inactive modules.
			 * @param array           $active_modules   The active modules.
			 * @param WP_REST_Request $request          The request object.
			 * @param self            $route_instance   The current Modules API instance.
			 */
			$inactive_modules = apply_filters(
				'divi_squad_rest_update_inactive_modules',
				$inactive_modules,
				$active_modules,
				$request,
				$this
			);

			$this->update_module_memory( $active_modules, $inactive_modules );

			$response_data = array(
				'code'    => 'success',
				'message' => __( 'The list of active modules has been updated.', 'squad-modules-for-divi' ),
			);

			/**
			 * Filters the response data after updating modules.
			 *
			 * @since 1.0.0
			 *
			 * @param array           $response_data    The response data.
			 * @param array           $active_modules   The active modules.
			 * @param array           $inactive_modules The inactive modules.
			 * @param WP_REST_Request $request          The request object.
			 * @param self            $route_instance   The current Modules API instance.
			 */
			$response_data = apply_filters(
				'divi_squad_rest_update_modules_response',
				$response_data,
				$active_modules,
				$inactive_modules,
				$request,
				$this
			);

			$response = rest_ensure_response( $response_data );

			/**
			 * Fires after updating active modules.
			 *
			 * @since 1.0.0
			 *
			 * @param WP_REST_Response $response         The response object.
			 * @param array            $active_modules   The active modules.
			 * @param array            $inactive_modules The inactive modules.
			 * @param WP_REST_Request  $request          The request object.
			 * @param self             $route_instance   The current Modules API instance.
			 */
			do_action(
				'divi_squad_rest_after_update_active_modules',
				$response,
				$active_modules,
				$inactive_modules,
				$request,
				$this
			);

			return $response;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to update active modules' );

			/**
			 * Fires when an error occurs during updating active modules.
			 *
			 * @since 1.0.0
			 *
			 * @param Throwable       $e              The exception that was thrown.
			 * @param WP_REST_Request $request        The request object.
			 * @param self            $route_instance The current Modules API instance.
			 */
			do_action( 'divi_squad_rest_update_active_modules_error', $e, $request, $this );

			/**
			 * Filters the error response when updating active modules fails.
			 *
			 * @since 1.0.0
			 *
			 * @param WP_Error        $error          The error object.
			 * @param Throwable       $e              The exception that was thrown.
			 * @param WP_REST_Request $request        The request object.
			 * @param self            $route_instance The current Modules API instance.
			 */
			return apply_filters(
				'divi_squad_rest_update_modules_error_response',
				new WP_Error(
					'update_failed',
					__( 'Failed to update active modules. Please try again.', 'squad-modules-for-divi' ),
					array( 'status' => 500 )
				),
				$e,
				$request,
				$this
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
		try {
			/**
			 * Fires before updating module memory.
			 *
			 * @since 1.0.0
			 *
			 * @param array $active_modules   The active modules.
			 * @param array $inactive_modules The inactive modules.
			 * @param self  $route_instance   The current Modules API instance.
			 */
			do_action( 'divi_squad_rest_before_update_module_memory', $active_modules, $inactive_modules, $this );

			divi_squad()->memory->set( static::ACTIVE_MODULES_KEY, $active_modules );
			divi_squad()->memory->set( static::INACTIVE_MODULES_KEY, $inactive_modules );
			divi_squad()->memory->set( static::ACTIVE_MODULE_VERSION_KEY, divi_squad()->get_version() );

			/**
			 * Fires after updating module memory.
			 *
			 * @since 1.0.0
			 *
			 * @param array $active_modules   The active modules.
			 * @param array $inactive_modules The inactive modules.
			 * @param self  $route_instance   The current Modules API instance.
			 */
			do_action( 'divi_squad_rest_after_update_module_memory', $active_modules, $inactive_modules, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to update module memory' );

			/**
			 * Fires when an error occurs during updating module memory.
			 *
			 * @since 1.0.0
			 *
			 * @param Throwable $e                The exception that was thrown.
			 * @param array     $active_modules   The active modules.
			 * @param array     $inactive_modules The inactive modules.
			 * @param self      $route_instance   The current Modules API instance.
			 */
			do_action( 'divi_squad_rest_update_module_memory_error', $e, $active_modules, $inactive_modules, $this );
		}
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
		try {
			$formatted_data = array(
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

			/**
			 * Filters the formatted module data.
			 *
			 * @since 1.0.0
			 *
			 * @param array $formatted_data The formatted module data.
			 * @param array $module         The raw module data.
			 * @param self  $route_instance The current Modules API instance.
			 */
			return apply_filters( 'divi_squad_rest_format_module', $formatted_data, $module, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to format module data' );

			/**
			 * Fires when an error occurs during formatting module data.
			 *
			 * @since 1.0.0
			 *
			 * @param Throwable $e              The exception that was thrown.
			 * @param array     $module         The raw module data.
			 * @param self      $route_instance The current Modules API instance.
			 */
			do_action( 'divi_squad_rest_format_module_error', $e, $module, $this );

			// Return basic data to avoid further errors
			return array(
				'name'  => $module['name'] ?? '',
				'label' => $module['label'] ?? '',
			);
		}
	}
}
