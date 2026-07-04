<?php // phpcs:ignore WordPress.Files.FileName

/**
 * REST API Routes for Extensions
 *
 * This file contains the Extensions class which handles REST API endpoints
 * for managing Divi Squad extensions.
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
 * Extensions REST API Route Handler
 *
 * Manages REST API endpoints for Divi Squad extensions, including
 * retrieving available, active, and inactive extensions, as well as
 * updating the list of active extensions.
 *
 * @since   1.0.0
 * @package DiviSquad
 */
class Extensions extends Base_Route {

	/**
	 * Key for active extensions in memory.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public const ACTIVE_EXTENSIONS_KEY = 'active_extensions';

	/**
	 * Key for inactive extensions in memory.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public const INACTIVE_EXTENSIONS_KEY = 'inactive_extensions';

	/**
	 * Key for active extension version in memory.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public const ACTIVE_EXTENSION_VERSION_KEY = 'active_extension_version';

	/**
	 * Get available routes for the Extensions API.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array<int, array<string, list<$this|string>|string>>>
	 */
	public function get_routes(): array {
		$routes = array(
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

		/**
		 * Filters the extension routes configuration.
		 *
		 * @since 1.0.0
		 *
		 * @param array $routes         The routes configuration array.
		 * @param self  $route_instance The current Extensions API instance.
		 */
		return apply_filters( 'divi_squad_rest_extension_routes', $routes, $this );
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
			 * Filters the capability required to access extension endpoints.
			 *
			 * @since 1.0.0
			 *
			 * @param string $capability     The capability name.
			 * @param self   $route_instance The current Extensions API instance.
			 */
			$capability = apply_filters( 'divi_squad_rest_extension_capability', 'manage_options', $this );

			if ( ! current_user_can( $capability ) ) {
				/**
				 * Filters the error message when a user doesn't have permission to access the extensions.
				 *
				 * @since 1.0.0
				 *
				 * @param string $error_message  The error message.
				 * @param string $capability     The capability that was checked.
				 * @param self   $route_instance The current Extensions API instance.
				 */
				$error_message = apply_filters(
					'divi_squad_rest_extension_permission_error_message',
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
			 * Fires after a successful permission check for extensions access.
			 *
			 * @since 1.0.0
			 *
			 * @param self $route_instance The current Extensions API instance.
			 */
			do_action( 'divi_squad_rest_extension_permission_check_passed', $this );

			return true;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Error checking extension permissions' );

			/**
			 * Filters the error response when permission checking fails.
			 *
			 * @since 1.0.0
			 *
			 * @param WP_Error  $error          The error object.
			 * @param Throwable $e              The exception that was thrown.
			 * @param self      $route_instance The current Extensions API instance.
			 */
			return apply_filters(
				'divi_squad_rest_extension_permission_error_response',
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
	 * Get all registered extensions.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response Response containing all registered extensions.
	 */
	public function get_extensions( WP_REST_Request $request ): WP_REST_Response {
		try {
			/**
			 * Fires before retrieving all extensions.
			 *
			 * @since 1.0.0
			 *
			 * @param WP_REST_Request $request        The request object.
			 * @param self            $route_instance The current Extensions API instance.
			 */
			do_action( 'divi_squad_rest_before_get_extensions', $request, $this );

			$extensions = divi_squad()->extensions->get_registered_list();
			$extensions = array_map( array( $this, 'format_extension' ), $extensions );

			/**
			 * Filters the extensions list before returning the response.
			 *
			 * @since 1.0.0
			 *
			 * @param array           $extensions     The formatted extensions.
			 * @param WP_REST_Request $request        The request object.
			 * @param self            $route_instance The current Extensions API instance.
			 */
			$extensions = apply_filters( 'divi_squad_rest_extensions_list', $extensions, $request, $this );

			$response = rest_ensure_response( array_values( $extensions ) );

			/**
			 * Fires after retrieving all extensions.
			 *
			 * @since 1.0.0
			 *
			 * @param WP_REST_Response $response       The response object.
			 * @param array            $extensions     The formatted extensions.
			 * @param WP_REST_Request  $request        The request object.
			 * @param self             $route_instance The current Extensions API instance.
			 */
			do_action( 'divi_squad_rest_after_get_extensions', $response, $extensions, $request, $this );

			return $response;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to get extensions list' );

			/**
			 * Fires when an error occurs during retrieving all extensions.
			 *
			 * @since 1.0.0
			 *
			 * @param Throwable       $e              The exception that was thrown.
			 * @param WP_REST_Request $request        The request object.
			 * @param self            $route_instance The current Extensions API instance.
			 */
			do_action( 'divi_squad_rest_get_extensions_error', $e, $request, $this );

			// Return empty array to avoid breaking the frontend
			return rest_ensure_response( array() );
		}
	}

	/**
	 * Get active extensions list.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response Response containing active extensions.
	 */
	public function get_active_extensions( WP_REST_Request $request ): WP_REST_Response {
		try {
			/**
			 * Fires before retrieving active extensions.
			 *
			 * @since 1.0.0
			 *
			 * @param WP_REST_Request $request        The request object.
			 * @param self            $route_instance The current Extensions API instance.
			 */
			do_action( 'divi_squad_rest_before_get_active_extensions', $request, $this );

			$active_extensions = $this->get_extension_names( static::ACTIVE_EXTENSIONS_KEY );

			/**
			 * Filters the active extensions list before returning the response.
			 *
			 * @since 1.0.0
			 *
			 * @param array           $active_extensions The active extensions.
			 * @param WP_REST_Request $request           The request object.
			 * @param self            $route_instance    The current Extensions API instance.
			 */
			$active_extensions = apply_filters( 'divi_squad_rest_active_extensions_list', $active_extensions, $request, $this );

			$response = rest_ensure_response( $active_extensions );

			/**
			 * Fires after retrieving active extensions.
			 *
			 * @since 1.0.0
			 *
			 * @param WP_REST_Response $response          The response object.
			 * @param array            $active_extensions The active extensions.
			 * @param WP_REST_Request  $request           The request object.
			 * @param self             $route_instance    The current Extensions API instance.
			 */
			do_action( 'divi_squad_rest_after_get_active_extensions', $response, $active_extensions, $request, $this );

			return $response;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to get active extensions list' );

			/**
			 * Fires when an error occurs during retrieving active extensions.
			 *
			 * @since 1.0.0
			 *
			 * @param Throwable       $e              The exception that was thrown.
			 * @param WP_REST_Request $request        The request object.
			 * @param self            $route_instance The current Extensions API instance.
			 */
			do_action( 'divi_squad_rest_get_active_extensions_error', $e, $request, $this );

			// Return empty array to avoid breaking the frontend
			return rest_ensure_response( array() );
		}
	}

	/**
	 * Get inactive extensions list.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response Response containing inactive extensions.
	 */
	public function get_inactive_extensions( WP_REST_Request $request ): WP_REST_Response {
		try {
			/**
			 * Fires before retrieving inactive extensions.
			 *
			 * @since 1.0.0
			 *
			 * @param WP_REST_Request $request        The request object.
			 * @param self            $route_instance The current Extensions API instance.
			 */
			do_action( 'divi_squad_rest_before_get_inactive_extensions', $request, $this );

			$inactive_extensions = $this->get_extension_names( static::INACTIVE_EXTENSIONS_KEY );

			/**
			 * Filters the inactive extensions list before returning the response.
			 *
			 * @since 1.0.0
			 *
			 * @param array           $inactive_extensions The inactive extensions.
			 * @param WP_REST_Request $request             The request object.
			 * @param self            $route_instance      The current Extensions API instance.
			 */
			$inactive_extensions = apply_filters( 'divi_squad_rest_inactive_extensions_list', $inactive_extensions, $request, $this );

			$response = rest_ensure_response( $inactive_extensions );

			/**
			 * Fires after retrieving inactive extensions.
			 *
			 * @since 1.0.0
			 *
			 * @param WP_REST_Response $response            The response object.
			 * @param array            $inactive_extensions The inactive extensions.
			 * @param WP_REST_Request  $request             The request object.
			 * @param self             $route_instance      The current Extensions API instance.
			 */
			do_action( 'divi_squad_rest_after_get_inactive_extensions', $response, $inactive_extensions, $request, $this );

			return $response;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to get inactive extensions list' );

			/**
			 * Fires when an error occurs during retrieving inactive extensions.
			 *
			 * @since 1.0.0
			 *
			 * @param Throwable       $e              The exception that was thrown.
			 * @param WP_REST_Request $request        The request object.
			 * @param self            $route_instance The current Extensions API instance.
			 */
			do_action( 'divi_squad_rest_get_inactive_extensions_error', $e, $request, $this );

			// Return empty array to avoid breaking the frontend
			return rest_ensure_response( array() );
		}
	}

	/**
	 * Get extension names from memory.
	 *
	 * Retrieves either active or inactive extension names from the plugin's memory.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key The key to retrieve from memory ('active_extensions' or 'inactive_extensions').
	 *
	 * @return array<string> List of extension names.
	 */
	protected function get_extension_names( string $key ): array {
		try {
			/**
			 * Fires before retrieving extension names from memory.
			 *
			 * @since 1.0.0
			 *
			 * @param string $key            The memory key.
			 * @param self   $route_instance The current Extensions API instance.
			 */
			do_action( 'divi_squad_rest_before_get_extension_names', $key, $this );

			$current = divi_squad()->memory->get( $key );
			if ( ! is_array( $current ) ) {
				$defaults = $this->get_default_extensions( $key );
				$current  = array_column( $defaults, 'name' );
			}

			$extension_names = array_values( $current );

			/**
			 * Filters the extension names retrieved from memory.
			 *
			 * @since 1.0.0
			 *
			 * @param array  $extension_names The extension names.
			 * @param string $key             The memory key.
			 * @param self   $route_instance  The current Extensions API instance.
			 */
			$extension_names = apply_filters( 'divi_squad_rest_extension_names', $extension_names, $key, $this );

			/**
			 * Fires after retrieving extension names from memory.
			 *
			 * @since 1.0.0
			 *
			 * @param array  $extension_names The extension names.
			 * @param string $key             The memory key.
			 * @param self   $route_instance  The current Extensions API instance.
			 */
			do_action( 'divi_squad_rest_after_get_extension_names', $extension_names, $key, $this );

			return $extension_names;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to get extension names from memory for key: ' . $key );

			/**
			 * Fires when an error occurs during retrieving extension names from memory.
			 *
			 * @since 1.0.0
			 *
			 * @param Throwable $e              The exception that was thrown.
			 * @param string    $key            The memory key.
			 * @param self      $route_instance The current Extensions API instance.
			 */
			do_action( 'divi_squad_rest_get_extension_names_error', $e, $key, $this );

			// Return empty array to avoid further errors
			return array();
		}
	}

	/**
	 * Get default extensions based on the provided key.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key The key to determine which default extensions to retrieve.
	 *
	 * @return array<string, mixed> List of default extensions.
	 */
	private function get_default_extensions( string $key ): array {
		try {
			$defaults = static::ACTIVE_EXTENSIONS_KEY === $key
				? divi_squad()->extensions->get_default_registries()
				: divi_squad()->extensions->get_inactive_registries();

			/**
			 * Filters the default extensions based on the provided key.
			 *
			 * @since 1.0.0
			 *
			 * @param array  $defaults       The default extensions.
			 * @param string $key            The memory key.
			 * @param self   $route_instance The current Extensions API instance.
			 */
			return apply_filters( 'divi_squad_rest_default_extensions', $defaults, $key, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to get default extensions for key: ' . $key );

			/**
			 * Fires when an error occurs during retrieving default extensions.
			 *
			 * @since 1.0.0
			 *
			 * @param Throwable $e              The exception that was thrown.
			 * @param string    $key            The memory key.
			 * @param self      $route_instance The current Extensions API instance.
			 */
			do_action( 'divi_squad_rest_get_default_extensions_error', $e, $key, $this );

			// Return empty array to avoid further errors
			return array();
		}
	}

	/**
	 * Update active extensions list.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response on success, WP_Error on failure.
	 */
	public function update_active_extensions( WP_REST_Request $request ) {
		try {
			/**
			 * Fires before updating active extensions.
			 *
			 * @since 1.0.0
			 *
			 * @param WP_REST_Request $request        The request object.
			 * @param self            $route_instance The current Extensions API instance.
			 */
			do_action( 'divi_squad_rest_before_update_active_extensions', $request, $this );

			$active_extensions = $request->get_json_params();

			if ( ! is_array( $active_extensions ) ) {
				return new WP_Error(
					'invalid_data',
					esc_html__( 'Invalid data format. Expected array of extension names.', 'squad-modules-for-divi' ),
					array( 'status' => 400 )
				);
			}

			$active_extensions   = array_values( array_map( 'sanitize_text_field', $active_extensions ) );
			$all_extension_names = array_column( divi_squad()->extensions->get_registered_list(), 'name' );
			$invalid_extensions  = array_diff( $active_extensions, $all_extension_names );

			if ( count( $invalid_extensions ) > 0 ) {
				$error_message = sprintf(
				/* translators: %s: comma-separated list of invalid extension names */
					esc_html__( 'Invalid extension names provided: %s', 'squad-modules-for-divi' ),
					implode( ', ', $invalid_extensions )
				);

				// Send an error report.
				divi_squad()->log_error(
					new Exception( $error_message ),
					'An error message from lite extensions rest api.'
				);

				// Send error message to the frontend.
				return new WP_Error(
					'invalid_extension',
					$error_message,
					array( 'status' => 400 )
				);
			}

			/**
			 * Filters the active extensions list before updating.
			 *
			 * @since 1.0.0
			 *
			 * @param array           $active_extensions   The active extensions.
			 * @param array           $all_extension_names All extension names.
			 * @param WP_REST_Request $request             The request object.
			 * @param self            $route_instance      The current Extensions API instance.
			 */
			$active_extensions = apply_filters(
				'divi_squad_rest_update_active_extensions',
				$active_extensions,
				$all_extension_names,
				$request,
				$this
			);

			$inactive_extensions = array_values( array_diff( $all_extension_names, $active_extensions ) );

			/**
			 * Filters the inactive extensions list before updating.
			 *
			 * @since 1.0.0
			 *
			 * @param array           $inactive_extensions The inactive extensions.
			 * @param array           $active_extensions   The active extensions.
			 * @param WP_REST_Request $request             The request object.
			 * @param self            $route_instance      The current Extensions API instance.
			 */
			$inactive_extensions = apply_filters(
				'divi_squad_rest_update_inactive_extensions',
				$inactive_extensions,
				$active_extensions,
				$request,
				$this
			);

			$this->update_extension_memory( $active_extensions, $inactive_extensions );

			$response_data = array(
				'code'    => 'success',
				'message' => __( 'The list of active extensions has been updated.', 'squad-modules-for-divi' ),
			);

			/**
			 * Filters the response data after updating extensions.
			 *
			 * @since 1.0.0
			 *
			 * @param array           $response_data       The response data.
			 * @param array           $active_extensions   The active extensions.
			 * @param array           $inactive_extensions The inactive extensions.
			 * @param WP_REST_Request $request             The request object.
			 * @param self            $route_instance      The current Extensions API instance.
			 */
			$response_data = apply_filters(
				'divi_squad_rest_update_extensions_response',
				$response_data,
				$active_extensions,
				$inactive_extensions,
				$request,
				$this
			);

			$response = rest_ensure_response( $response_data );

			/**
			 * Fires after updating active extensions.
			 *
			 * @since 1.0.0
			 *
			 * @param WP_REST_Response $response            The response object.
			 * @param array            $active_extensions   The active extensions.
			 * @param array            $inactive_extensions The inactive extensions.
			 * @param WP_REST_Request  $request             The request object.
			 * @param self             $route_instance      The current Extensions API instance.
			 */
			do_action(
				'divi_squad_rest_after_update_active_extensions',
				$response,
				$active_extensions,
				$inactive_extensions,
				$request,
				$this
			);

			return $response;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to update active extensions' );

			/**
			 * Fires when an error occurs during updating active extensions.
			 *
			 * @since 1.0.0
			 *
			 * @param Throwable       $e              The exception that was thrown.
			 * @param WP_REST_Request $request        The request object.
			 * @param self            $route_instance The current Extensions API instance.
			 */
			do_action( 'divi_squad_rest_update_active_extensions_error', $e, $request, $this );

			/**
			 * Filters the error response when updating active extensions fails.
			 *
			 * @since 1.0.0
			 *
			 * @param WP_Error        $error          The error object.
			 * @param Throwable       $e              The exception that was thrown.
			 * @param WP_REST_Request $request        The request object.
			 * @param self            $route_instance The current Extensions API instance.
			 */
			return apply_filters(
				'divi_squad_rest_update_extensions_error_response',
				new WP_Error(
					'update_failed',
					__( 'Failed to update active extensions. Please try again.', 'squad-modules-for-divi' ),
					array( 'status' => 500 )
				),
				$e,
				$request,
				$this
			);
		}
	}

	/**
	 * Update extension memory with active and inactive extensions.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string> $active_extensions   List of active extensions.
	 * @param array<string> $inactive_extensions List of inactive extensions.
	 *
	 * @return void
	 */
	protected function update_extension_memory( array $active_extensions, array $inactive_extensions ): void {
		try {
			/**
			 * Fires before updating extension memory.
			 *
			 * @since 1.0.0
			 *
			 * @param array $active_extensions   The active extensions.
			 * @param array $inactive_extensions The inactive extensions.
			 * @param self  $route_instance      The current Extensions API instance.
			 */
			do_action( 'divi_squad_rest_before_update_extension_memory', $active_extensions, $inactive_extensions, $this );

			divi_squad()->memory->set( static::ACTIVE_EXTENSIONS_KEY, $active_extensions );
			divi_squad()->memory->set( static::INACTIVE_EXTENSIONS_KEY, $inactive_extensions );
			divi_squad()->memory->set( static::ACTIVE_EXTENSION_VERSION_KEY, divi_squad()->get_version() );

			/**
			 * Fires after updating extension memory.
			 *
			 * @since 1.0.0
			 *
			 * @param array $active_extensions   The active extensions.
			 * @param array $inactive_extensions The inactive extensions.
			 * @param self  $route_instance      The current Extensions API instance.
			 */
			do_action( 'divi_squad_rest_after_update_extension_memory', $active_extensions, $inactive_extensions, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to update extension memory' );

			/**
			 * Fires when an error occurs during updating extension memory.
			 *
			 * @since 1.0.0
			 *
			 * @param Throwable $e                   The exception that was thrown.
			 * @param array     $active_extensions   The active extensions.
			 * @param array     $inactive_extensions The inactive extensions.
			 * @param self      $route_instance      The current Extensions API instance.
			 */
			do_action( 'divi_squad_rest_update_extension_memory_error', $e, $active_extensions, $inactive_extensions, $this );
		}
	}

	/**
	 * Format a single extension's data.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $extension Extension data to format.
	 *
	 * @return array<string, mixed> Formatted extension data.
	 */
	private function format_extension( array $extension ): array {
		try {
			$formatted_data = array(
				'name'               => $extension['name'] ?? '',
				'label'              => $extension['label'] ?? '',
				'description'        => $extension['description'] ?? '',
				'release_version'    => $extension['release_version'] ?? '',
				'last_modified'      => $extension['last_modified'] ?? array(),
				'is_default_active'  => $extension['is_default_active'] ?? false,
				'is_premium_feature' => $extension['is_premium_feature'] ?? false,
				'category'           => $extension['category'] ?? '',
				'category_title'     => $extension['category_title'] ?? '',
			);

			/**
			 * Filters the formatted extension data.
			 *
			 * @since 1.0.0
			 *
			 * @param array $formatted_data The formatted extension data.
			 * @param array $extension      The raw extension data.
			 * @param self  $route_instance The current Extensions API instance.
			 */
			return apply_filters( 'divi_squad_rest_format_extension', $formatted_data, $extension, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to format extension data' );

			/**
			 * Fires when an error occurs during formatting extension data.
			 *
			 * @since 1.0.0
			 *
			 * @param Throwable $e              The exception that was thrown.
			 * @param array     $extension      The raw extension data.
			 * @param self      $route_instance The current Extensions API instance.
			 */
			do_action( 'divi_squad_rest_format_extension_error', $e, $extension, $this );

			// Return basic data to avoid further errors
			return array(
				'name'  => $extension['name'] ?? '',
				'label' => $extension['label'] ?? '',
			);
		}
	}
}