<?php // phpcs:ignore WordPress.Files.FileName

/**
 * REST API Routes for Extensions (v2)
 *
 * This file contains the v2 Extensions class which handles enhanced REST API endpoints
 * for managing Divi Squad extensions with additional capabilities.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   3.3.0
 */

namespace DiviSquad\Rest_API_Routes\Version2;

use DiviSquad\Rest_API_Routes\Version1\Extensions as Extensions_V1;
use Exception;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Extensions REST API v2 Route Handler
 *
 * Enhanced REST API endpoints for Divi Squad extensions, including
 * retrieving extension details by category, bulk operations, and
 * individual extension management.
 *
 * @package DiviSquad
 * @since   3.3.0
 */
class Extensions extends Extensions_V1 {
	/**
	 * Version identifier for API endpoints.
	 *
	 * @since 3.3.0
	 * @var string
	 */
	protected string $version = 'v2';

	/**
	 * Get available routes for the Extensions API v2.
	 *
	 * @since 3.3.0
	 *
	 * @return array<string, array<int, array<string, list<$this|string>|string>>>
	 */
	public function get_routes(): array {
		$base_routes = parent::get_routes();

		// Remove the 'update modules' endpoint from the '/modules/active' route only
		if ( isset( $base_routes['/extensions/active'] ) ) {
			$base_routes['/extensions/active'] = array_filter(
				$base_routes['/extensions/active'],
				static function ( $route_config ) {
					return WP_REST_Server::CREATABLE !== $route_config['methods'];
				}
			);
		}

		$v2_routes = array(
			'/extensions/enable-batch'            => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'enable_extensions_batch' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
				),
			),
			'/extensions/disable-batch'           => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'disable_extensions_batch' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
				),
			),
			'/extensions/reset'                   => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'reset_extensions' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
				),
			),
			'/extensions/categories'              => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_extension_categories' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
				),
			),
			'/extensions/category/(?P<id>[\w-]+)' => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_extensions_by_category' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
					'args'                => array(
						'id' => array(
							'required'          => true,
							'type'              => 'string',
							'description'       => esc_html__( 'Category identifier', 'squad-modules-for-divi' ),
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			),
			'/extensions/(?P<id>[\w-]+)'          => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_extension' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
					'args'                => array(
						'id' => array(
							'required'          => true,
							'type'              => 'string',
							'description'       => esc_html__( 'Extension identifier', 'squad-modules-for-divi' ),
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'toggle_extension' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
					'args'                => array(
						'id'     => array(
							'required'          => true,
							'type'              => 'string',
							'description'       => esc_html__( 'Extension identifier', 'squad-modules-for-divi' ),
							'sanitize_callback' => 'sanitize_text_field',
						),
						'active' => array(
							'required'    => true,
							'type'        => 'boolean',
							'description' => esc_html__( 'Whether the extension should be active', 'squad-modules-for-divi' ),
						),
					),
				),
			),
		);

		/**
		 * Filter the v2 extension routes.
		 *
		 * @since 3.3.0
		 *
		 * @param array $v2_routes   The v2 routes.
		 * @param array $base_routes The base v1 routes.
		 * @param self  $instance    The current instance.
		 */
		$v2_routes = apply_filters( 'divi_squad_rest_v2_extension_routes', $v2_routes, $base_routes, $this );

		return array_merge( $base_routes, $v2_routes );
	}

	/**
	 * Get all extension categories.
	 *
	 * @since 3.3.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response Response containing all extension categories.
	 */
	public function get_extension_categories( WP_REST_Request $request ): WP_REST_Response {
		try {
			/**
			 * Action fired before retrieving extension categories.
			 *
			 * @since 3.3.0
			 *
			 * @param WP_REST_Request $request The request object.
			 */
			do_action( 'divi_squad_before_get_extension_categories', $request );

			$categories = divi_squad()->extensions->get_extension_categories();

			$formatted_categories = array();
			foreach ( $categories as $id => $title ) {
				$formatted_categories[] = $this->prepare_category_for_response( $id, $title );
			}

			/**
			 * Filter the formatted categories before returning the response.
			 *
			 * @since 3.3.0
			 *
			 * @param array           $formatted_categories The formatted categories.
			 * @param array           $categories           The raw categories.
			 * @param WP_REST_Request $request              The request object.
			 */
			$formatted_categories = apply_filters( 'divi_squad_rest_extension_categories', $formatted_categories, $categories, $request );

			$response = rest_ensure_response( $formatted_categories );

			/**
			 * Action fired after retrieving extension categories.
			 *
			 * @since 3.3.0
			 *
			 * @param WP_REST_Response $response   The response object.
			 * @param array            $categories The raw categories.
			 * @param WP_REST_Request  $request    The request object.
			 */
			do_action( 'divi_squad_after_get_extension_categories', $response, $categories, $request );

			return $response;
		} catch ( Exception $e ) {
			divi_squad()->log_error( $e, 'Failed to get extension categories' );

			return new WP_REST_Response(
				array(
					'code'    => 'error',
					'message' => __( 'Failed to get extension categories.', 'squad-modules-for-divi' ),
				),
				500
			);
		}
	}

	/**
	 * Get extensions by category.
	 *
	 * @since 3.3.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response on success, WP_Error on failure.
	 */
	public function get_extensions_by_category( WP_REST_Request $request ) {
		try {
			$category = $request->get_param( 'id' );

			/**
			 * Action fired before retrieving extensions by category.
			 *
			 * @since 3.3.0
			 *
			 * @param string          $category The category ID.
			 * @param WP_REST_Request $request  The request object.
			 */
			do_action( 'divi_squad_before_get_extensions_by_category', $category, $request );

			$all_extensions      = divi_squad()->extensions->get_registered_list();
			$category_extensions = array();

			foreach ( $all_extensions as $extension ) {
				if ( isset( $extension['category'] ) && $extension['category'] === $category ) {
					$category_extensions[] = $this->prepare_extension_for_response( $extension );
				}
			}

			/**
			 * Filter extensions by category before returning the response.
			 *
			 * @since 3.3.0
			 *
			 * @param array           $category_extensions Extensions in the category.
			 * @param string          $category            The category ID.
			 * @param array           $all_extensions      All available extensions.
			 * @param WP_REST_Request $request             The request object.
			 */
			$category_extensions = apply_filters( 'divi_squad_rest_extensions_by_category', $category_extensions, $category, $all_extensions, $request );

			$response = rest_ensure_response( $category_extensions );

			/**
			 * Action fired after retrieving extensions by category.
			 *
			 * @since 3.3.0
			 *
			 * @param WP_REST_Response $response       The response object.
			 * @param string           $category       The category ID.
			 * @param array            $all_extensions All available extensions.
			 * @param WP_REST_Request  $request        The request object.
			 */
			do_action( 'divi_squad_after_get_extensions_by_category', $response, $category, $all_extensions, $request );

			return $response;
		} catch ( Exception $e ) {
			divi_squad()->log_error( $e, 'Failed to get extensions by category' );

			return new WP_Error(
				'extensions_fetch_failed',
				__( 'Failed to get extensions by category.', 'squad-modules-for-divi' ),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Get a single extension by ID.
	 *
	 * @since 3.3.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response on success, WP_Error on failure.
	 */
	public function get_extension( WP_REST_Request $request ) {
		try {
			$extension_id = $request->get_param( 'id' );

			/**
			 * Action fired before retrieving a single extension.
			 *
			 * @since 3.3.0
			 *
			 * @param string          $extension_id The extension ID.
			 * @param WP_REST_Request $request      The request object.
			 */
			do_action( 'divi_squad_before_get_extension', $extension_id, $request );

			$extension_info = divi_squad()->extensions->get_extension_info( $extension_id );

			if ( null === $extension_info ) {
				return new WP_Error(
					'extension_not_found',
					sprintf(
					/* translators: %s: extension id */
						__( 'Extension "%s" not found.', 'squad-modules-for-divi' ),
						$extension_id
					),
					array( 'status' => 404 )
				);
			}

			$response_data = $this->prepare_extension_for_response( $extension_info );

			/**
			 * Filter the extension data before returning the response.
			 *
			 * @since 3.3.0
			 *
			 * @param array           $response_data  The formatted extension data.
			 * @param array           $extension_info The raw extension information.
			 * @param string          $extension_id   The extension ID.
			 * @param WP_REST_Request $request        The request object.
			 */
			$response_data = apply_filters( 'divi_squad_rest_extension_data', $response_data, $extension_info, $extension_id, $request );

			$response = rest_ensure_response( $response_data );

			/**
			 * Action fired after retrieving a single extension.
			 *
			 * @since 3.3.0
			 *
			 * @param WP_REST_Response $response       The response object.
			 * @param array            $extension_info The extension information.
			 * @param string           $extension_id   The extension ID.
			 * @param WP_REST_Request  $request        The request object.
			 */
			do_action( 'divi_squad_after_get_extension', $response, $extension_info, $extension_id, $request );

			return $response;
		} catch ( Exception $e ) {
			divi_squad()->log_error( $e, 'Failed to get extension details' );

			return new WP_Error(
				'extension_fetch_failed',
				__( 'Failed to get extension details.', 'squad-modules-for-divi' ),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Toggle an extension's active state.
	 *
	 * @since 3.3.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response on success, WP_Error on failure.
	 */
	public function toggle_extension( WP_REST_Request $request ) {
		try {
			$extension_id   = $request->get_param( 'id' );
			$extension_data = $request->get_json_params();
			$active         = isset( $extension_data['active'] ) ? (bool) $extension_data['active'] : null;

			if ( null === $active ) {
				return new WP_Error(
					'missing_active_parameter',
					__( 'The "active" parameter is required.', 'squad-modules-for-divi' ),
					array( 'status' => 400 )
				);
			}

			/**
			 * Action fired before toggling an extension's state.
			 *
			 * @since 3.3.0
			 *
			 * @param string          $extension_id The extension ID.
			 * @param bool            $active       Whether to activate (true) or deactivate (false).
			 * @param WP_REST_Request $request      The request object.
			 */
			do_action( 'divi_squad_before_toggle_extension', $extension_id, $active, $request );

			$extension_info = divi_squad()->extensions->get_extension_info( $extension_id );

			if ( null === $extension_info ) {
				return new WP_Error(
					'extension_not_found',
					sprintf(
					/* translators: %s: extension id */
						__( 'Extension "%s" not found.', 'squad-modules-for-divi' ),
						$extension_id
					),
					array( 'status' => 404 )
				);
			}

			// Get current active extensions
			$active_extensions = $this->get_extension_names( static::ACTIVE_EXTENSIONS_KEY );

			if ( $active ) {
				// Add to active extensions if not already present
				if ( ! in_array( $extension_id, $active_extensions, true ) ) {
					$active_extensions[] = $extension_id;
				}
			} else {
				// Remove from active extensions
				$active_extensions = array_diff( $active_extensions, array( $extension_id ) );
			}

			// Get all extension names
			$all_extension_names = array_column( divi_squad()->extensions->get_registered_list(), 'name' );
			$inactive_extensions = array_values( array_diff( $all_extension_names, $active_extensions ) );

			/**
			 * Filter the active extensions lists before updating memory.
			 *
			 * @since 3.3.0
			 *
			 * @param array           $active_extensions   The active extensions list.
			 * @param array           $inactive_extensions The inactive extensions list.
			 * @param string          $extension_id        The extension being toggled.
			 * @param bool            $active              Whether activating (true) or deactivating (false).
			 * @param WP_REST_Request $request             The request object.
			 */
			$active_extensions = apply_filters( 'divi_squad_toggle_extension_active_list', $active_extensions, $inactive_extensions, $extension_id, $active, $request );

			/**
			 * Filter the inactive extensions list before updating memory.
			 *
			 * @since 3.3.0
			 *
			 * @param array           $inactive_extensions The inactive extensions list.
			 * @param array           $active_extensions   The active extensions list.
			 * @param string          $extension_id        The extension being toggled.
			 * @param bool            $active              Whether activating (true) or deactivating (false).
			 * @param WP_REST_Request $request             The request object.
			 */
			$inactive_extensions = apply_filters( 'divi_squad_toggle_extension_inactive_list', $inactive_extensions, $active_extensions, $extension_id, $active, $request );

			// Update storage
			$this->update_extension_memory( $active_extensions, $inactive_extensions );

			$response_data = array(
				'code'      => 'success',
				'message'   => $active
					? __( 'Extension activated successfully.', 'squad-modules-for-divi' )
					: __( 'Extension deactivated successfully.', 'squad-modules-for-divi' ),
				'active'    => $active,
				'extension' => $extension_id,
			);

			/**
			 * Filter the response data before returning.
			 *
			 * @since 3.3.0
			 *
			 * @param array           $response_data       The response data.
			 * @param string          $extension_id        The extension ID.
			 * @param bool            $active              Whether activating (true) or deactivating (false).
			 * @param array           $active_extensions   The active extensions list.
			 * @param array           $inactive_extensions The inactive extensions list.
			 * @param WP_REST_Request $request             The request object.
			 */
			$response_data = apply_filters( 'divi_squad_toggle_extension_response', $response_data, $extension_id, $active, $active_extensions, $inactive_extensions, $request );

			$response = rest_ensure_response( $response_data );

			/**
			 * Action fired after toggling an extension's state.
			 *
			 * @since 3.3.0
			 *
			 * @param WP_REST_Response $response            The response object.
			 * @param string           $extension_id        The extension ID.
			 * @param bool             $active              Whether activating (true) or deactivating (false).
			 * @param array            $active_extensions   The active extensions list.
			 * @param array            $inactive_extensions The inactive extensions list.
			 * @param WP_REST_Request  $request             The request object.
			 */
			do_action( 'divi_squad_after_toggle_extension', $response, $extension_id, $active, $active_extensions, $inactive_extensions, $request );

			return $response;
		} catch ( Exception $e ) {
			divi_squad()->log_error( $e, 'Failed to toggle extension state' );

			return new WP_Error(
				'extension_toggle_failed',
				__( 'Failed to toggle extension state.', 'squad-modules-for-divi' ),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Reset extensions to default state.
	 *
	 * @since 3.3.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response Response on success, WP_Error on failure.
	 */
	public function reset_extensions( WP_REST_Request $request ): WP_REST_Response {
		try {
			/**
			 * Action fired before resetting extensions to default state.
			 *
			 * @since 3.3.0
			 *
			 * @param WP_REST_Request $request The request object.
			 */
			do_action( 'divi_squad_before_reset_extensions', $request );

			$success = divi_squad()->extensions->reset_to_default();

			if ( ! $success ) {
				return new WP_REST_Response(
					array(
						'code'    => 'error',
						'message' => __( 'Failed to reset extensions to default state.', 'squad-modules-for-divi' ),
					),
					500
				);
			}

			$response_data = array(
				'code'              => 'success',
				'message'           => __( 'Extensions have been reset to default state.', 'squad-modules-for-divi' ),
				'active_extensions' => $this->get_extension_names( static::ACTIVE_EXTENSIONS_KEY ),
			);

			/**
			 * Filter the response data after resetting extensions.
			 *
			 * @since 3.3.0
			 *
			 * @param array           $response_data The response data.
			 * @param WP_REST_Request $request       The request object.
			 */
			$response_data = apply_filters( 'divi_squad_reset_extensions_response', $response_data, $request );

			$response = rest_ensure_response( $response_data );

			/**
			 * Action fired after resetting extensions to default state.
			 *
			 * @since 3.3.0
			 *
			 * @param WP_REST_Response $response The response object.
			 * @param WP_REST_Request  $request  The request object.
			 */
			do_action( 'divi_squad_after_reset_extensions', $response, $request );

			return $response;
		} catch ( Exception $e ) {
			divi_squad()->log_error( $e, 'Failed to reset extensions' );

			return new WP_REST_Response(
				array(
					'code'    => 'error',
					'message' => __( 'Failed to reset extensions.', 'squad-modules-for-divi' ),
				),
				500
			);
		}
	}

	/**
	 * Enable a batch of extensions.
	 *
	 * @since 3.3.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response on success, WP_Error on failure.
	 */
	public function enable_extensions_batch( WP_REST_Request $request ) {
		try {
			$extension_ids = $request->get_json_params();

			if ( ! is_array( $extension_ids ) || count( $extension_ids ) === 0 ) {
				return new WP_Error(
					'invalid_extension_ids',
					__( 'Invalid extension IDs provided. Expected array of extension identifiers.', 'squad-modules-for-divi' ),
					array( 'status' => 400 )
				);
			}

			/**
			 * Action fired before enabling a batch of extensions.
			 *
			 * @since 3.3.0
			 *
			 * @param array           $extension_ids The extension IDs to enable.
			 * @param WP_REST_Request $request       The request object.
			 */
			do_action( 'divi_squad_before_enable_extensions_batch', $extension_ids, $request );

			// Validate extension IDs
			$all_extension_names = array_column( divi_squad()->extensions->get_registered_list(), 'name' );
			$invalid_extensions  = array_diff( $extension_ids, $all_extension_names );

			if ( count( $invalid_extensions ) > 0 ) {
				return new WP_Error(
					'invalid_extension_ids',
					sprintf(
					/* translators: %s: comma-separated list of invalid extension names */
						__( 'Invalid extension names provided: %s', 'squad-modules-for-divi' ),
						implode( ', ', $invalid_extensions )
					),
					array( 'status' => 400 )
				);
			}

			// Get current active extensions
			$active_extensions = $this->get_extension_names( static::ACTIVE_EXTENSIONS_KEY );

			// Add new extensions to active list
			foreach ( $extension_ids as $extension_id ) {
				if ( ! in_array( $extension_id, $active_extensions, true ) ) {
					$active_extensions[] = $extension_id;
				}
			}

			// Get inactive extensions
			$inactive_extensions = array_values( array_diff( $all_extension_names, $active_extensions ) );

			// Update storage
			$this->update_extension_memory( $active_extensions, $inactive_extensions );

			$response_data = array(
				'code'              => 'success',
				'message'           => __( 'Extensions enabled successfully.', 'squad-modules-for-divi' ),
				'enabled'           => $extension_ids,
				'active_extensions' => $active_extensions,
			);

			/**
			 * Filter the response data.
			 *
			 * @since 3.3.0
			 *
			 * @param array           $response_data       The response data.
			 * @param array           $extension_ids       The extension IDs that were enabled.
			 * @param array           $active_extensions   The updated active extensions list.
			 * @param array           $inactive_extensions The updated inactive extensions list.
			 * @param WP_REST_Request $request             The request object.
			 */
			$response_data = apply_filters( 'divi_squad_enable_extensions_batch_response', $response_data, $extension_ids, $active_extensions, $inactive_extensions, $request );

			$response = rest_ensure_response( $response_data );

			/**
			 * Action fired after enabling a batch of extensions.
			 *
			 * @since 3.3.0
			 *
			 * @param WP_REST_Response $response            The response object.
			 * @param array            $extension_ids       The extension IDs that were enabled.
			 * @param array            $active_extensions   The updated active extensions list.
			 * @param array            $inactive_extensions The updated inactive extensions list.
			 * @param WP_REST_Request  $request             The request object.
			 */
			do_action( 'divi_squad_after_enable_extensions_batch', $response, $extension_ids, $active_extensions, $inactive_extensions, $request );

			return $response;
		} catch ( Exception $e ) {
			divi_squad()->log_error( $e, 'Failed to enable extensions batch' );

			return new WP_Error(
				'enable_extensions_failed',
				__( 'Failed to enable extensions.', 'squad-modules-for-divi' ),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Disable a batch of extensions.
	 *
	 * @since 3.3.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response on success, WP_Error on failure.
	 */
	public function disable_extensions_batch( WP_REST_Request $request ) {
		try {
			$extension_ids = $request->get_json_params();

			if ( ! is_array( $extension_ids ) || count( $extension_ids ) === 0 ) {
				return new WP_Error(
					'invalid_extension_ids',
					__( 'Invalid extension IDs provided. Expected array of extension identifiers.', 'squad-modules-for-divi' ),
					array( 'status' => 400 )
				);
			}

			/**
			 * Action fired before disabling a batch of extensions.
			 *
			 * @since 3.3.0
			 *
			 * @param array           $extension_ids The extension IDs to disable.
			 * @param WP_REST_Request $request       The request object.
			 */
			do_action( 'divi_squad_before_disable_extensions_batch', $extension_ids, $request );

			// Validate extension IDs
			$all_extension_names = array_column( divi_squad()->extensions->get_registered_list(), 'name' );
			$invalid_extensions  = array_diff( $extension_ids, $all_extension_names );

			if ( count( $invalid_extensions ) > 0 ) {
				return new WP_Error(
					'invalid_extension_ids',
					sprintf(
					/* translators: %s: comma-separated list of invalid extension names */
						__( 'Invalid extension names provided: %s', 'squad-modules-for-divi' ),
						implode( ', ', $invalid_extensions )
					),
					array( 'status' => 400 )
				);
			}

			// Get current active extensions
			$active_extensions = $this->get_extension_names( static::ACTIVE_EXTENSIONS_KEY );

			// Remove extensions from active list
			$active_extensions = array_values( array_diff( $active_extensions, $extension_ids ) );

			// Get inactive extensions
			$inactive_extensions = array_values( array_diff( $all_extension_names, $active_extensions ) );

			// Update storage
			$this->update_extension_memory( $active_extensions, $inactive_extensions );

			$response_data = array(
				'code'              => 'success',
				'message'           => __( 'Extensions disabled successfully.', 'squad-modules-for-divi' ),
				'disabled'          => $extension_ids,
				'active_extensions' => $active_extensions,
			);

			/**
			 * Filter the response data.
			 *
			 * @since 3.3.0
			 *
			 * @param array           $response_data       The response data.
			 * @param array           $extension_ids       The extension IDs that were disabled.
			 * @param array           $active_extensions   The updated active extensions list.
			 * @param array           $inactive_extensions The updated inactive extensions list.
			 * @param WP_REST_Request $request             The request object.
			 */
			$response_data = apply_filters( 'divi_squad_disable_extensions_batch_response', $response_data, $extension_ids, $active_extensions, $inactive_extensions, $request );

			$response = rest_ensure_response( $response_data );

			/**
			 * Action fired after disabling a batch of extensions.
			 *
			 * @since 3.3.0
			 *
			 * @param WP_REST_Response $response            The response object.
			 * @param array            $extension_ids       The extension IDs that were disabled.
			 * @param array            $active_extensions   The updated active extensions list.
			 * @param array            $inactive_extensions The updated inactive extensions list.
			 * @param WP_REST_Request  $request             The request object.
			 */
			do_action( 'divi_squad_after_disable_extensions_batch', $response, $extension_ids, $active_extensions, $inactive_extensions, $request );

			return $response;
		} catch ( Exception $e ) {
			divi_squad()->log_error( $e, 'Failed to disable extensions batch' );

			return new WP_Error(
				'disable_extensions_failed',
				__( 'Failed to disable extensions.', 'squad-modules-for-divi' ),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Get all registered extensions (with enhanced data).
	 *
	 * @since 3.3.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response Response containing all registered extensions.
	 */
	public function get_extensions( WP_REST_Request $request ): WP_REST_Response {
		try {
			/**
			 * Action fired before retrieving all extensions.
			 *
			 * @since 3.3.0
			 *
			 * @param WP_REST_Request $request The request object.
			 */
			do_action( 'divi_squad_before_get_extensions_v2', $request );

			$extensions = divi_squad()->extensions->get_registered_list();

			$formatted_extensions = array();
			foreach ( $extensions as $extension ) {
				$formatted_extensions[] = $this->prepare_extension_for_response( $extension );
			}

			/**
			 * Filter the formatted extensions before response.
			 *
			 * @since 3.3.0
			 *
			 * @param array           $formatted_extensions The formatted extensions.
			 * @param array           $extensions           The raw extensions data.
			 * @param WP_REST_Request $request              The request object.
			 */
			$formatted_extensions = apply_filters( 'divi_squad_rest_extensions_v2', $formatted_extensions, $extensions, $request );

			$response = rest_ensure_response( array( 'extensions' => $formatted_extensions ) );
			$response->add_link( 'version', rest_url( $this->get_name() . $this->version ), array( 'version' => $this->version ) );

			/**
			 * Action fired after retrieving all extensions.
			 *
			 * @since 3.3.0
			 *
			 * @param WP_REST_Response      $response   The response object.
			 * @param array                 $extensions The raw extensions data.
			 * @param WP_REST_Request  $request    The request object.
			 */
			do_action( 'divi_squad_after_get_extensions_v2', $response, $extensions, $request );

			return $response;
		} catch ( Exception $e ) {
			divi_squad()->log_error( $e, 'Failed to get extensions' );

			return new WP_REST_Response(
				array(
					'code'    => 'error',
					'message' => __( 'Failed to get extensions.', 'squad-modules-for-divi' ),
				),
				500
			);
		}
	}

	/**
	 * Override parent method for getting active extensions with enhancements.
	 *
	 * @since 3.3.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response Response containing active extensions.
	 */
	public function get_active_extensions( WP_REST_Request $request ): WP_REST_Response {
		try {
			/**
			 * Action fired before retrieving active extensions.
			 *
			 * @since 3.3.0
			 *
			 * @param WP_REST_Request $request The request object.
			 */
			do_action( 'divi_squad_before_get_active_extensions_v2', $request );

			$active_extensions = $this->get_extension_names( static::ACTIVE_EXTENSIONS_KEY );

			// Get detailed extension information if requested
			$detailed = isset( $request['detailed'] ) && filter_var( $request['detailed'], FILTER_VALIDATE_BOOLEAN );

			if ( $detailed ) {
				$formatted_extensions = array();
				foreach ( $active_extensions as $extension_name ) {
					$extension_info = divi_squad()->extensions->get_extension_info( $extension_name );
					if ( null !== $extension_info ) {
						$formatted_extensions[] = $this->prepare_extension_for_response( $extension_info );
					}
				}

				/**
				 * Filter the detailed active extensions.
				 *
				 * @since 3.3.0
				 *
				 * @param array           $formatted_extensions The formatted extensions.
				 * @param array           $active_extensions    The active extension names.
				 * @param WP_REST_Request $request              The request object.
				 * @param self            $instance             The current instance.
				 */
				$formatted_extensions = apply_filters( 'divi_squad_detailed_active_extensions', $formatted_extensions, $active_extensions, $request, $this );

				$response = rest_ensure_response( $formatted_extensions );
			} else {
				/**
				 * Filter the active extension names.
				 *
				 * @since 3.3.0
				 *
				 * @param array           $active_extensions The active extension names.
				 * @param WP_REST_Request $request           The request object.
				 * @param self            $instance          The current instance.
				 */
				$active_extensions = apply_filters( 'divi_squad_active_extension_names', $active_extensions, $request, $this );

				$response = rest_ensure_response( $active_extensions );
			}

			/**
			 * Action fired after retrieving active extensions.
			 *
			 * @since 3.3.0
			 *
			 * @param WP_REST_Response $response          The response object.
			 * @param array            $active_extensions The active extension names.
			 * @param WP_REST_Request  $request           The request object.
			 */
			do_action( 'divi_squad_after_get_active_extensions_v2', $response, $active_extensions, $request );

			return $response;
		} catch ( Exception $e ) {
			divi_squad()->log_error( $e, 'Failed to get active extensions' );

			return new WP_REST_Response(
				array(
					'code'    => 'error',
					'message' => __( 'Failed to get active extensions.', 'squad-modules-for-divi' ),
				),
				500
			);
		}
	}

	/**
	 * Override parent method for getting inactive extensions with enhancements.
	 *
	 * @since 3.3.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response Response containing inactive extensions.
	 */
	public function get_inactive_extensions( WP_REST_Request $request ): WP_REST_Response {
		try {
			/**
			 * Action fired before retrieving inactive extensions.
			 *
			 * @since 3.3.0
			 *
			 * @param WP_REST_Request $request The request object.
			 */
			do_action( 'divi_squad_before_get_inactive_extensions_v2', $request );

			$inactive_extensions = $this->get_extension_names( static::INACTIVE_EXTENSIONS_KEY );

			// Get detailed extension information if requested
			$detailed = isset( $request['detailed'] ) && filter_var( $request['detailed'], FILTER_VALIDATE_BOOLEAN );

			if ( $detailed ) {
				$formatted_extensions = array();
				foreach ( $inactive_extensions as $extension_name ) {
					$extension_info = divi_squad()->extensions->get_extension_info( $extension_name );
					if ( null !== $extension_info ) {
						$formatted_extensions[] = $this->prepare_extension_for_response( $extension_info );
					}
				}

				/**
				 * Filter the detailed inactive extensions.
				 *
				 * @since 3.3.0
				 *
				 * @param array           $formatted_extensions The formatted extensions.
				 * @param array           $inactive_extensions  The inactive extension names.
				 * @param WP_REST_Request $request              The request object.
				 * @param self            $instance             The current instance.
				 */
				$formatted_extensions = apply_filters( 'divi_squad_detailed_inactive_extensions', $formatted_extensions, $inactive_extensions, $request, $this );

				$response = rest_ensure_response( $formatted_extensions );
			} else {
				/**
				 * Filter the inactive extension names.
				 *
				 * @since 3.3.0
				 *
				 * @param array           $inactive_extensions The inactive extension names.
				 * @param WP_REST_Request $request             The request object.
				 * @param self            $instance            The current instance.
				 */
				$inactive_extensions = apply_filters( 'divi_squad_inactive_extension_names', $inactive_extensions, $request, $this );

				$response = rest_ensure_response( $inactive_extensions );
			}

			/**
			 * Action fired after retrieving inactive extensions.
			 *
			 * @since 3.3.0
			 *
			 * @param WP_REST_Response $response            The response object.
			 * @param array            $inactive_extensions The inactive extension names.
			 * @param WP_REST_Request  $request             The request object.
			 */
			do_action( 'divi_squad_after_get_inactive_extensions_v2', $response, $inactive_extensions, $request );

			return $response;
		} catch ( Exception $e ) {
			divi_squad()->log_error( $e, 'Failed to get inactive extensions' );

			return new WP_REST_Response(
				array(
					'code'    => 'error',
					'message' => __( 'Failed to get inactive extensions.', 'squad-modules-for-divi' ),
				),
				500
			);
		}
	}

	/**
	 * Prepare an extension for the REST response.
	 *
	 * @since 3.3.0
	 *
	 * @param array<string, mixed> $extension Extension data to format.
	 *
	 * @return array<string, mixed> Formatted extension data.
	 */
	protected function prepare_extension_for_response( array $extension ): array {
		$is_active = divi_squad()->extensions->is_extension_active( $extension['name'] ?? '' );

		// Format extension data with enhanced information
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
			'is_active'          => $is_active,
			'version'            => $this->version,
		);

		/**
		 * Filter the extension data for REST response.
		 *
		 * @since 3.3.0
		 *
		 * @param array $formatted_data The formatted extension data.
		 * @param array $extension      The raw extension data.
		 * @param self  $instance       The current instance.
		 */
		return apply_filters( 'divi_squad_prepare_extension_for_response', $formatted_data, $extension, $this );
	}

	/**
	 * Prepare a category for the REST response.
	 *
	 * @since 3.3.0
	 *
	 * @param string $id    Category ID.
	 * @param string $title Category title.
	 *
	 * @return array<string, string> Formatted category data.
	 */
	protected function prepare_category_for_response( string $id, string $title ): array {
		$formatted_data = array(
			'id'    => $id,
			'title' => $title,
			'count' => $this->count_extensions_in_category( $id ),
		);

		/**
		 * Filter the category data for REST response.
		 *
		 * @since 3.3.0
		 *
		 * @param array  $formatted_data The formatted category data.
		 * @param string $id             The category ID.
		 * @param string $title          The category title.
		 * @param self   $instance       The current instance.
		 */
		return apply_filters( 'divi_squad_prepare_extension_category_for_response', $formatted_data, $id, $title, $this );
	}

	/**
	 * Count extensions in a category.
	 *
	 * @since 3.3.0
	 *
	 * @param string $category_id Category ID.
	 * @return int Number of extensions in the category.
	 */
	protected function count_extensions_in_category( string $category_id ): int {
		$all_extensions = divi_squad()->extensions->get_registered_list();
		$count          = 0;

		foreach ( $all_extensions as $extension ) {
			if ( isset( $extension['category'] ) && $extension['category'] === $category_id ) {
				++$count;
			}
		}

		return $count;
	}
}
