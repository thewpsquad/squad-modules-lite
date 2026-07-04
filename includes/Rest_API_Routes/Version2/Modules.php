<?php // phpcs:ignore WordPress.Files.FileName

/**
 * REST API Routes for Modules (v2)
 *
 * This file contains the v2 Modules class which handles enhanced REST API endpoints
 * for managing Divi Squad modules with additional capabilities.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   3.3.0
 */

namespace DiviSquad\Rest_API_Routes\Version2;

use DiviSquad\Rest_API_Routes\Version1\Modules as Modules_V1;
use DiviSquad\Utils\WP;
use Throwable;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Modules REST API v2 Route Handler
 *
 * Enhanced REST API endpoints for Divi Squad modules, including
 * retrieving module details by category, bulk operations, and
 * individual module management.
 *
 * @package DiviSquad
 * @since   3.3.0
 */
class Modules extends Modules_V1 {

	/**
	 * Version identifier for API endpoints.
	 *
	 * @since 3.3.0
	 * @var string
	 */
	protected string $version = 'v2';

	/**
	 * Get available routes for the Modules API v2.
	 *
	 * @since 3.3.0
	 *
	 * @return array<string, array<int, array<string, list<$this|string>|string>>>
	 */
	public function get_routes(): array {
		$base_routes = parent::get_routes();

		// Remove the 'update modules' endpoint from the '/modules/active' route only
		if ( isset( $base_routes['/modules/active'] ) ) {
			$base_routes['/modules/active'] = array_filter(
				$base_routes['/modules/active'],
				static function ( $route_config ) {
					return WP_REST_Server::CREATABLE !== $route_config['methods'];
				}
			);
		}

		$v2_routes = array(
			'/modules/enable-batch'            => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'enable_modules_batch' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
				),
			),
			'/modules/disable-batch'           => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'disable_modules_batch' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
				),
			),
			'/modules/reset'                   => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'reset_modules' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
				),
			),
			'/modules/categories'              => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_module_categories' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
				),
			),
			'/modules/category/(?P<id>[\w-]+)' => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_modules_by_category' ),
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
			'/modules/(?P<id>[\w-]+)'          => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_module' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
					'args'                => array(
						'id' => array(
							'required'          => true,
							'type'              => 'string',
							'description'       => esc_html__( 'Module identifier', 'squad-modules-for-divi' ),
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'toggle_module' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
					'args'                => array(
						'id'     => array(
							'required'          => true,
							'type'              => 'string',
							'description'       => esc_html__( 'Module identifier', 'squad-modules-for-divi' ),
							'sanitize_callback' => 'sanitize_text_field',
						),
						'active' => array(
							'required'    => true,
							'type'        => 'boolean',
							'description' => esc_html__( 'Whether the module should be active', 'squad-modules-for-divi' ),
						),
					),
				),
			),
		);

		/**
		 * Filter the v2 module routes.
		 *
		 * @since 3.3.0
		 *
		 * @param array $v2_routes   The v2 routes.
		 * @param array $base_routes The base v1 routes.
		 * @param self  $instance    The current instance.
		 */
		$v2_routes = apply_filters( 'divi_squad_rest_v2_module_routes', $v2_routes, $base_routes, $this );

		return array_merge( $base_routes, $v2_routes );
	}

	/**
	 * Get all module categories.
	 *
	 * @since 3.3.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response Response containing all module categories.
	 */
	public function get_module_categories( WP_REST_Request $request ): WP_REST_Response {
		try {
			/**
			 * Action fired before retrieving module categories.
			 *
			 * @since 3.3.0
			 *
			 * @param WP_REST_Request $request The request object.
			 */
			do_action( 'divi_squad_before_get_module_categories', $request );

			$categories = divi_squad()->modules->get_module_categories();

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
			$formatted_categories = apply_filters( 'divi_squad_rest_module_categories', $formatted_categories, $categories, $request );

			$response = rest_ensure_response( $formatted_categories );

			/**
			 * Action fired after retrieving module categories.
			 *
			 * @since 3.3.0
			 *
			 * @param WP_REST_Response $response  The response object.
			 * @param array            $categories The raw categories.
			 * @param WP_REST_Request  $request   The request object.
			 */
			do_action( 'divi_squad_after_get_module_categories', $response, $categories, $request );

			return $response;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to get module categories' );

			return new WP_REST_Response(
				array(
					'code'    => 'error',
					'message' => __( 'Failed to get module categories.', 'squad-modules-for-divi' ),
				),
				500
			);
		}
	}

	/**
	 * Get modules by category.
	 *
	 * @since 3.3.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response on success, WP_Error on failure.
	 */
	public function get_modules_by_category( WP_REST_Request $request ) {
		try {
			$category = $request->get_param( 'id' );

			/**
			 * Action fired before retrieving modules by category.
			 *
			 * @since 3.3.0
			 *
			 * @param string          $category The category ID.
			 * @param WP_REST_Request $request  The request object.
			 */
			do_action( 'divi_squad_before_get_modules_by_category', $category, $request );

			$all_modules      = divi_squad()->modules->get_all_modules();
			$category_modules = array();

			foreach ( $all_modules as $module ) {
				if ( isset( $module['category'] ) && $module['category'] === $category ) {
					$category_modules[] = $this->prepare_module_for_response( $module );
				}
			}

			/**
			 * Filter modules by category before returning the response.
			 *
			 * @since 3.3.0
			 *
			 * @param array           $category_modules Modules in the category.
			 * @param string          $category         The category ID.
			 * @param array           $all_modules      All available modules.
			 * @param WP_REST_Request $request          The request object.
			 */
			$category_modules = apply_filters( 'divi_squad_rest_modules_by_category', $category_modules, $category, $all_modules, $request );

			$response = rest_ensure_response( $category_modules );

			/**
			 * Action fired after retrieving modules by category.
			 *
			 * @since 3.3.0
			 *
			 * @param WP_REST_Response $response    The response object.
			 * @param string           $category    The category ID.
			 * @param array            $all_modules All available modules.
			 * @param WP_REST_Request  $request     The request object.
			 */
			do_action( 'divi_squad_after_get_modules_by_category', $response, $category, $all_modules, $request );

			return $response;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to get modules by category' );

			return new WP_Error(
				'modules_fetch_failed',
				__( 'Failed to get modules by category.', 'squad-modules-for-divi' ),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Get a single module by ID.
	 *
	 * @since 3.3.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response on success, WP_Error on failure.
	 */
	public function get_module( WP_REST_Request $request ) {
		try {
			$module_id = $request->get_param( 'id' );

			/**
			 * Action fired before retrieving a single module.
			 *
			 * @since 3.3.0
			 *
			 * @param string          $module_id The module ID.
			 * @param WP_REST_Request $request   The request object.
			 */
			do_action( 'divi_squad_before_get_module', $module_id, $request );

			$module_info = divi_squad()->modules->get_module_info( $module_id );

			if ( null === $module_info ) {
				return new WP_Error(
					'module_not_found',
					sprintf(
					/* translators: %s: module id */
						__( 'Module "%s" not found.', 'squad-modules-for-divi' ),
						$module_id
					),
					array( 'status' => 404 )
				);
			}

			$response_data = $this->prepare_module_for_response( $module_info );

			/**
			 * Filter the module data before returning the response.
			 *
			 * @since 3.3.0
			 *
			 * @param array           $response_data The formatted module data.
			 * @param array           $module_info   The raw module information.
			 * @param string          $module_id     The module ID.
			 * @param WP_REST_Request $request       The request object.
			 */
			$response_data = apply_filters( 'divi_squad_rest_module_data', $response_data, $module_info, $module_id, $request );

			$response = rest_ensure_response( $response_data );

			/**
			 * Action fired after retrieving a single module.
			 *
			 * @since 3.3.0
			 *
			 * @param WP_REST_Response $response    The response object.
			 * @param array            $module_info The module information.
			 * @param string           $module_id   The module ID.
			 * @param WP_REST_Request  $request     The request object.
			 */
			do_action( 'divi_squad_after_get_module', $response, $module_info, $module_id, $request );

			return $response;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to get module details' );

			return new WP_Error(
				'module_fetch_failed',
				__( 'Failed to get module details.', 'squad-modules-for-divi' ),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Toggle a module's active state.
	 *
	 * @since 3.3.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response on success, WP_Error on failure.
	 */
	public function toggle_module( WP_REST_Request $request ) {
		try {
			$module_id   = $request->get_param( 'id' );
			$module_data = $request->get_json_params();
			$active      = isset( $module_data['active'] ) ? (bool) $module_data['active'] : null;

			if ( null === $active ) {
				return new WP_Error(
					'missing_active_parameter',
					__( 'The "active" parameter is required.', 'squad-modules-for-divi' ),
					array( 'status' => 400 )
				);
			}

			/**
			 * Action fired before toggling a module's state.
			 *
			 * @since 3.3.0
			 *
			 * @param string          $module_id The module ID.
			 * @param bool            $active    Whether to activate (true) or deactivate (false).
			 * @param WP_REST_Request $request   The request object.
			 */
			do_action( 'divi_squad_before_toggle_module', $module_id, $active, $request );

			$module_info = divi_squad()->modules->get_module_info( $module_id );

			if ( null === $module_info ) {
				return new WP_Error(
					'module_not_found',
					sprintf(
					/* translators: %s: module id */
						__( 'Module "%s" not found.', 'squad-modules-for-divi' ),
						$module_id
					),
					array( 'status' => 404 )
				);
			}

			// Get current active modules
			$active_modules = $this->get_module_names( static::ACTIVE_MODULES_KEY );

			if ( $active ) {
				// Add to active modules if not already present
				if ( ! in_array( $module_id, $active_modules, true ) ) {
					$active_modules[] = $module_id;
				}
			} else {
				// Remove from active modules
				$active_modules = array_diff( $active_modules, array( $module_id ) );
			}

			// Get all module names
			$all_module_names = array_column( divi_squad()->modules->get_registered_list(), 'name' );
			$inactive_modules = array_values( array_diff( $all_module_names, $active_modules ) );

			/**
			 * Filter the active modules lists before updating memory.
			 *
			 * @since 3.3.0
			 *
			 * @param array           $active_modules   The active modules list.
			 * @param array           $inactive_modules The inactive modules list.
			 * @param string          $module_id        The module being toggled.
			 * @param bool            $active           Whether activating (true) or deactivating (false).
			 * @param WP_REST_Request $request          The request object.
			 */
			$active_modules = apply_filters( 'divi_squad_toggle_module_active_list', $active_modules, $inactive_modules, $module_id, $active, $request );

			/**
			 * Filter the inactive modules list before updating memory.
			 *
			 * @since 3.3.0
			 *
			 * @param array           $inactive_modules The inactive modules list.
			 * @param array           $active_modules   The active modules list.
			 * @param string          $module_id        The module being toggled.
			 * @param bool            $active           Whether activating (true) or deactivating (false).
			 * @param WP_REST_Request $request          The request object.
			 */
			$inactive_modules = apply_filters( 'divi_squad_toggle_module_inactive_list', $inactive_modules, $active_modules, $module_id, $active, $request );

			// Update storage
			$this->update_module_memory( $active_modules, $inactive_modules );

			$response_data = array(
				'code'    => 'success',
				'message' => $active
					? __( 'Module activated successfully.', 'squad-modules-for-divi' )
					: __( 'Module deactivated successfully.', 'squad-modules-for-divi' ),
				'active'  => $active,
				'module'  => $module_id,
			);

			/**
			 * Filter the response data before returning.
			 *
			 * @since 3.3.0
			 *
			 * @param array           $response_data    The response data.
			 * @param string          $module_id        The module ID.
			 * @param bool            $active           Whether activating (true) or deactivating (false).
			 * @param array           $active_modules   The active modules list.
			 * @param array           $inactive_modules The inactive modules list.
			 * @param WP_REST_Request $request          The request object.
			 */
			$response_data = apply_filters( 'divi_squad_toggle_module_response', $response_data, $module_id, $active, $active_modules, $inactive_modules, $request );

			$response = rest_ensure_response( $response_data );

			/**
			 * Action fired after toggling a module's state.
			 *
			 * @since 3.3.0
			 *
			 * @param WP_REST_Response $response         The response object.
			 * @param string           $module_id        The module ID.
			 * @param bool             $active           Whether activating (true) or deactivating (false).
			 * @param array            $active_modules   The active modules list.
			 * @param array            $inactive_modules The inactive modules list.
			 * @param WP_REST_Request  $request          The request object.
			 */
			do_action( 'divi_squad_after_toggle_module', $response, $module_id, $active, $active_modules, $inactive_modules, $request );

			return $response;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to toggle module state' );

			return new WP_Error(
				'module_toggle_failed',
				__( 'Failed to toggle module state.', 'squad-modules-for-divi' ),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Reset modules to default state.
	 *
	 * @since 3.3.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response Response on success, WP_Error on failure.
	 */
	public function reset_modules( WP_REST_Request $request ): WP_REST_Response {
		try {
			/**
			 * Action fired before resetting modules to default state.
			 *
			 * @since 3.3.0
			 *
			 * @param WP_REST_Request $request The request object.
			 */
			do_action( 'divi_squad_before_reset_modules', $request );

			$success = divi_squad()->modules->reset_to_default();

			if ( ! $success ) {
				return new WP_REST_Response(
					array(
						'code'    => 'error',
						'message' => __( 'Failed to reset modules to default state.', 'squad-modules-for-divi' ),
					),
					500
				);
			}

			$response_data = array(
				'code'           => 'success',
				'message'        => __( 'Modules have been reset to default state.', 'squad-modules-for-divi' ),
				'active_modules' => $this->get_module_names( static::ACTIVE_MODULES_KEY ),
			);

			/**
			 * Filter the response data after resetting modules.
			 *
			 * @since 3.3.0
			 *
			 * @param array           $response_data The response data.
			 * @param WP_REST_Request $request       The request object.
			 */
			$response_data = apply_filters( 'divi_squad_reset_modules_response', $response_data, $request );

			$response = rest_ensure_response( $response_data );

			/**
			 * Action fired after resetting modules to default state.
			 *
			 * @since 3.3.0
			 *
			 * @param WP_REST_Response $response The response object.
			 * @param WP_REST_Request  $request  The request object.
			 */
			do_action( 'divi_squad_after_reset_modules', $response, $request );

			return $response;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to reset modules' );

			return new WP_REST_Response(
				array(
					'code'    => 'error',
					'message' => __( 'Failed to reset modules.', 'squad-modules-for-divi' ),
				),
				500
			);
		}
	}

	/**
	 * Enable a batch of modules.
	 *
	 * @since 3.3.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response on success, WP_Error on failure.
	 */
	public function enable_modules_batch( WP_REST_Request $request ) {
		try {
			$module_ids = $request->get_json_params();

			if ( ! is_array( $module_ids ) || count( $module_ids ) === 0 ) {
				return new WP_Error(
					'invalid_module_ids',
					__( 'Invalid module IDs provided. Expected array of module identifiers.', 'squad-modules-for-divi' ),
					array( 'status' => 400 )
				);
			}

			/**
			 * Action fired before enabling a batch of modules.
			 *
			 * @since 3.3.0
			 *
			 * @param array           $module_ids The module IDs to enable.
			 * @param WP_REST_Request $request    The request object.
			 */
			do_action( 'divi_squad_before_enable_modules_batch', $module_ids, $request );

			// Validate module IDs
			$all_module_names = array_column( divi_squad()->modules->get_registered_list(), 'name' );
			$invalid_modules  = array_diff( $module_ids, $all_module_names );

			if ( count( $invalid_modules ) > 0 ) {
				return new WP_Error(
					'invalid_module_ids',
					sprintf(
					/* translators: %s: comma-separated list of invalid module names */
						__( 'Invalid module names provided: %s', 'squad-modules-for-divi' ),
						implode( ', ', $invalid_modules )
					),
					array( 'status' => 400 )
				);
			}

			// Add new modules to active list
			$active_modules = array();
			foreach ( $module_ids as $module_id ) {
				if ( in_array( $module_id, $all_module_names, true ) ) {
					$active_modules[] = $module_id;
				}
			}

			// Get inactive modules
			$inactive_modules = array_values( array_diff( $all_module_names, $active_modules ) );

			// Update storage
			$this->update_module_memory( $active_modules, $inactive_modules );

			$response_data = array(
				'code'           => 'success',
				'message'        => __( 'Modules enabled successfully.', 'squad-modules-for-divi' ),
				'enabled'        => $module_ids,
				'active_modules' => $active_modules,
			);

			/**
			 * Filter the response data.
			 *
			 * @since 3.3.0
			 *
			 * @param array           $response_data    The response data.
			 * @param array           $module_ids       The module IDs that were enabled.
			 * @param array           $active_modules   The updated active modules list.
			 * @param array           $inactive_modules The updated inactive modules list.
			 * @param WP_REST_Request $request          The request object.
			 */
			$response_data = apply_filters( 'divi_squad_enable_modules_batch_response', $response_data, $module_ids, $active_modules, $inactive_modules, $request );

			$response = rest_ensure_response( $response_data );

			/**
			 * Action fired after enabling a batch of modules.
			 *
			 * @since 3.3.0
			 *
			 * @param WP_REST_Response $response         The response object.
			 * @param array            $module_ids       The module IDs that were enabled.
			 * @param array            $active_modules   The updated active modules list.
			 * @param array            $inactive_modules The updated inactive modules list.
			 * @param WP_REST_Request  $request          The request object.
			 */
			do_action( 'divi_squad_after_enable_modules_batch', $response, $module_ids, $active_modules, $inactive_modules, $request );

			return $response;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to enable modules batch' );

			return new WP_Error(
				'enable_modules_failed',
				__( 'Failed to enable modules.', 'squad-modules-for-divi' ),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Disable a batch of modules.
	 *
	 * @since 3.3.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response on success, WP_Error on failure.
	 */
	public function disable_modules_batch( WP_REST_Request $request ) {
		try {
			$module_ids = $request->get_json_params();

			if ( ! is_array( $module_ids ) || count( $module_ids ) === 0 ) {
				return new WP_Error(
					'invalid_module_ids',
					__( 'Invalid module IDs provided. Expected array of module identifiers.', 'squad-modules-for-divi' ),
					array( 'status' => 400 )
				);
			}

			/**
			 * Action fired before disabling a batch of modules.
			 *
			 * @since 3.3.0
			 *
			 * @param array           $module_ids The module IDs to disable.
			 * @param WP_REST_Request $request    The request object.
			 */
			do_action( 'divi_squad_before_disable_modules_batch', $module_ids, $request );

			// Validate module IDs
			$all_module_names = array_column( divi_squad()->modules->get_registered_list(), 'name' );
			$invalid_modules  = array_diff( $module_ids, $all_module_names );

			if ( count( $invalid_modules ) > 0 ) {
				return new WP_Error(
					'invalid_module_ids',
					sprintf(
					/* translators: %s: comma-separated list of invalid module names */
						__( 'Invalid module names provided: %s', 'squad-modules-for-divi' ),
						implode( ', ', $invalid_modules )
					),
					array( 'status' => 400 )
				);
			}

			// Get current active modules
			$active_modules = $this->get_module_names( static::ACTIVE_MODULES_KEY );

			// Remove modules from active list
			$active_modules = array_values( array_diff( $active_modules, $module_ids ) );

			// Get inactive modules
			$inactive_modules = array_values( array_diff( $all_module_names, $active_modules ) );

			// Update storage
			$this->update_module_memory( $active_modules, $inactive_modules );

			$response_data = array(
				'code'           => 'success',
				'message'        => __( 'Modules disabled successfully.', 'squad-modules-for-divi' ),
				'disabled'       => $module_ids,
				'active_modules' => $active_modules,
			);

			/**
			 * Filter the response data.
			 *
			 * @since 3.3.0
			 *
			 * @param array           $response_data    The response data.
			 * @param array           $module_ids       The module IDs that were disabled.
			 * @param array           $active_modules   The updated active modules list.
			 * @param array           $inactive_modules The updated inactive modules list.
			 * @param WP_REST_Request $request          The request object.
			 */
			$response_data = apply_filters( 'divi_squad_disable_modules_batch_response', $response_data, $module_ids, $active_modules, $inactive_modules, $request );

			$response = rest_ensure_response( $response_data );

			/**
			 * Action fired after disabling a batch of modules.
			 *
			 * @since 3.3.0
			 *
			 * @param WP_REST_Response $response         The response object.
			 * @param array            $module_ids       The module IDs that were disabled.
			 * @param array            $active_modules   The updated active modules list.
			 * @param array            $inactive_modules The updated inactive modules list.
			 * @param WP_REST_Request  $request          The request object.
			 */
			do_action( 'divi_squad_after_disable_modules_batch', $response, $module_ids, $active_modules, $inactive_modules, $request );

			return $response;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to disable modules batch' );

			return new WP_Error(
				'disable_modules_failed',
				__( 'Failed to disable modules.', 'squad-modules-for-divi' ),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Get all registered modules (with enhanced data).
	 *
	 * @since 3.3.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response Response containing all registered modules.
	 */
	public function get_modules( WP_REST_Request $request ): WP_REST_Response {
		try {
			/**
			 * Action fired before retrieving all modules.
			 *
			 * @since 3.3.0
			 *
			 * @param WP_REST_Request|null $request The request object.
			 */
			do_action( 'divi_squad_before_get_modules_v2', $request );

			$modules = divi_squad()->modules->get_all_modules();

			// Get detailed module information if requested
			$detailed = isset( $request['detailed'] ) && filter_var( $request['detailed'], FILTER_VALIDATE_BOOLEAN );

			if ( $detailed ) {
				$formatted_modules = array();
				foreach ( $modules as $module ) {
					$formatted_modules[] = $this->prepare_module_for_response( $module );
				}

				/**
				 * Filter the detailed modules list.
				 *
				 * @since 3.3.0
				 *
				 * @param array                $formatted_modules The formatted modules.
				 * @param array                $modules           The raw modules data.
				 * @param WP_REST_Request|null $request           The request object.
				 * @param self                 $instance          The current instance.
				 */
				$formatted_modules = apply_filters( 'divi_squad_detailed_modules', $formatted_modules, $modules, $request, $this );

				$response = rest_ensure_response( array( 'modules' => $formatted_modules ) );
			} else {
				$modules = array_map( array( $this, 'format_module' ), $modules );

				/**
				 * Filter the modules list.
				 *
				 * @since 3.3.0
				 *
				 * @param array                $modules  The modules list.
				 * @param WP_REST_Request|null $request  The request object.
				 * @param self                 $instance The current instance.
				 */
				$modules = apply_filters( 'divi_squad_modules_list', $modules, $request, $this );

				$response = rest_ensure_response( array( 'modules' => $modules ) );
			}

			// Add version information to response
			$response->add_link( 'version', rest_url( $this->get_namespace() ), array( 'version' => $this->version ) );
			$response->add_link( 'collection', rest_url( $this->get_namespace() . '/modules' ) );

			/**
			 * Action fired after retrieving all modules.
			 *
			 * @since 3.3.0
			 *
			 * @param WP_REST_Response      $response The response object.
			 * @param array                 $modules  The modules data.
			 * @param WP_REST_Request|null  $request  The request object.
			 */
			do_action( 'divi_squad_after_get_modules_v2', $response, $modules, $request );

			return $response;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to get modules' );

			return new WP_REST_Response(
				array(
					'code'    => 'error',
					'message' => __( 'Failed to get modules.', 'squad-modules-for-divi' ),
				),
				500
			);
		}
	}

	/**
	 * Override parent method for getting active modules with enhancements.
	 *
	 * @since 3.3.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response Response containing active modules.
	 */
	public function get_active_modules( WP_REST_Request $request ): WP_REST_Response {
		try {
			/**
			 * Action fired before retrieving active modules.
			 *
			 * @since 3.3.0
			 *
			 * @param WP_REST_Request|null $request The request object.
			 */
			do_action( 'divi_squad_before_get_active_modules_v2', $request );

			$active_modules = $this->get_module_names( static::ACTIVE_MODULES_KEY );

			// Get detailed module information if requested
			$detailed = isset( $request['detailed'] ) && filter_var( $request['detailed'], FILTER_VALIDATE_BOOLEAN );

			if ( $detailed ) {
				$formatted_modules = array();
				foreach ( $active_modules as $module_name ) {
					$module_info = divi_squad()->modules->get_module_info( $module_name );
					if ( null !== $module_info ) {
						$formatted_modules[] = $this->prepare_module_for_response( $module_info );
					}
				}

				/**
				 * Filter the detailed active modules.
				 *
				 * @since 3.3.0
				 *
				 * @param array                $formatted_modules The formatted modules.
				 * @param array                $active_modules    The active module names.
				 * @param WP_REST_Request|null $request           The request object.
				 * @param self                 $instance          The current instance.
				 */
				$formatted_modules = apply_filters( 'divi_squad_detailed_active_modules', $formatted_modules, $active_modules, $request, $this );

				$response = rest_ensure_response( $formatted_modules );
			} else {
				/**
				 * Filter the active module names.
				 *
				 * @since 3.3.0
				 *
				 * @param array                $active_modules The active module names.
				 * @param WP_REST_Request|null $request        The request object.
				 * @param self                 $instance       The current instance.
				 */
				$active_modules = apply_filters( 'divi_squad_active_module_names', $active_modules, $request, $this );

				$response = rest_ensure_response( $active_modules );
			}

			/**
			 * Action fired after retrieving active modules.
			 *
			 * @since 3.3.0
			 *
			 * @param WP_REST_Response      $response       The response object.
			 * @param array                 $active_modules The active module names.
			 * @param WP_REST_Request|null  $request        The request object.
			 */
			do_action( 'divi_squad_after_get_active_modules_v2', $response, $active_modules, $request );

			return $response;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to get active modules' );

			return new WP_REST_Response(
				array(
					'code'    => 'error',
					'message' => __( 'Failed to get active modules.', 'squad-modules-for-divi' ),
				),
				500
			);
		}
	}

	/**
	 * Override parent method for getting inactive modules with enhancements.
	 *
	 * @since 3.3.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response Response containing inactive modules.
	 */
	public function get_inactive_modules( WP_REST_Request $request ): WP_REST_Response {
		try {
			/**
			 * Action fired before retrieving inactive modules.
			 *
			 * @since 3.3.0
			 *
			 * @param WP_REST_Request|null $request The request object.
			 */
			do_action( 'divi_squad_before_get_inactive_modules_v2', $request );

			$inactive_modules = $this->get_module_names( static::INACTIVE_MODULES_KEY );

			// Get detailed module information if requested
			$detailed = isset( $request['detailed'] ) && filter_var( $request['detailed'], FILTER_VALIDATE_BOOLEAN );

			if ( $detailed ) {
				$formatted_modules = array();
				foreach ( $inactive_modules as $module_name ) {
					$module_info = divi_squad()->modules->get_module_info( $module_name );
					if ( null !== $module_info ) {
						$formatted_modules[] = $this->prepare_module_for_response( $module_info );
					}
				}

				/**
				 * Filter the detailed inactive modules.
				 *
				 * @since 3.3.0
				 *
				 * @param array                $formatted_modules The formatted modules.
				 * @param array                $inactive_modules  The inactive module names.
				 * @param WP_REST_Request|null $request           The request object.
				 * @param self                 $instance          The current instance.
				 */
				$formatted_modules = apply_filters( 'divi_squad_detailed_inactive_modules', $formatted_modules, $inactive_modules, $request, $this );

				$response = rest_ensure_response( $formatted_modules );
			} else {
				/**
				 * Filter the inactive module names.
				 *
				 * @since 3.3.0
				 *
				 * @param array                $inactive_modules The inactive module names.
				 * @param WP_REST_Request|null $request          The request object.
				 * @param self                 $instance         The current instance.
				 */
				$inactive_modules = apply_filters( 'divi_squad_inactive_module_names', $inactive_modules, $request, $this );

				$response = rest_ensure_response( $inactive_modules );
			}

			/**
			 * Action fired after retrieving inactive modules.
			 *
			 * @since 3.3.0
			 *
			 * @param WP_REST_Response      $response         The response object.
			 * @param array                 $inactive_modules The inactive module names.
			 * @param WP_REST_Request|null  $request          The request object.
			 */
			do_action( 'divi_squad_after_get_inactive_modules_v2', $response, $inactive_modules, $request );

			return $response;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to get inactive modules' );

			return new WP_REST_Response(
				array(
					'code'    => 'error',
					'message' => __( 'Failed to get inactive modules.', 'squad-modules-for-divi' ),
				),
				500
			);
		}
	}

	/**
	 * Prepare a module for the REST response.
	 *
	 * @since 3.3.0
	 *
	 * @param array<string, mixed> $module Module data to format.
	 *
	 * @return array<string, mixed> Formatted module data.
	 */
	protected function prepare_module_for_response( array $module ): array {
		$is_active = divi_squad()->modules->is_module_active( $module['name'] ?? '' );

		// Format module data with enhanced information
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
			'is_active'          => $is_active,
			'has_settings'       => isset( $module['settings_route'] ) && (bool) $module['settings_route'],
			'dependencies'       => $this->get_module_dependencies( $module ),
			'version'            => $this->version,
		);

		/**
		 * Filter the module data for REST response.
		 *
		 * @since 3.3.0
		 *
		 * @param array $formatted_data The formatted module data.
		 * @param array $module         The raw module data.
		 * @param self  $instance       The current instance.
		 */
		return apply_filters( 'divi_squad_prepare_module_for_response', $formatted_data, $module, $this );
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
			'count' => $this->count_modules_in_category( $id ),
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
		return apply_filters( 'divi_squad_prepare_category_for_response', $formatted_data, $id, $title, $this );
	}

	/**
	 * Count modules in a category.
	 *
	 * @since 3.3.0
	 *
	 * @param string $category_id Category ID.
	 *
	 * @return int Number of modules in the category.
	 */
	protected function count_modules_in_category( string $category_id ): int {
		$all_modules = divi_squad()->modules->get_all_modules();
		$count       = 0;

		foreach ( $all_modules as $module ) {
			if ( isset( $module['category'] ) && $module['category'] === $category_id ) {
				++$count;
			}
		}

		return $count;
	}

	/**
	 * Get module dependencies.
	 *
	 * @since 3.3.0
	 *
	 * @param array<string, mixed> $module Module data.
	 *
	 * @return array<string, array<string, mixed>> Module dependencies.
	 */
	protected function get_module_dependencies( array $module ): array {
		$dependencies = array();

		if ( isset( $module['required']['plugin'] ) && is_array( $module['required'] ) ) {
			// Process plugin requirements
			$plugin_deps = $module['required']['plugin'];
			if ( is_string( $plugin_deps ) ) {
				// Single plugin or alternatives
				$plugin_deps = strpos( $plugin_deps, '|' ) !== false ? explode( '|', $plugin_deps ) : array( $plugin_deps );

				foreach ( $plugin_deps as $plugin ) {
					$dependencies['plugins'][] = array(
						'slug'     => $plugin,
						'name'     => $this->get_plugin_name_by_slug( $plugin ),
						'required' => true,
						'active'   => WP::is_plugin_active( $plugin ),
					);
				}
			} elseif ( is_array( $plugin_deps ) ) {
				// Multiple required plugins
				foreach ( $plugin_deps as $plugin ) {
					$dependencies['plugins'][] = array(
						'slug'     => $plugin,
						'name'     => $this->get_plugin_name_by_slug( $plugin ),
						'required' => true,
						'active'   => WP::is_plugin_active( $plugin ),
					);
				}
			}
		}

		/**
		 * Filter the module dependencies.
		 *
		 * @since 3.3.0
		 *
		 * @param array $dependencies  The dependencies.
		 * @param array $module        The module data.
		 * @param self  $instance      The current instance.
		 */
		return apply_filters( 'divi_squad_module_dependencies', $dependencies, $module, $this );
	}

	/**
	 * Get plugin name by slug.
	 *
	 * @since 3.3.0
	 *
	 * @param string $plugin_slug Plugin slug.
	 *
	 * @return string Plugin name.
	 */
	protected function get_plugin_name_by_slug( string $plugin_slug ): string {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php'; // @phpstan-ignore-line
		}

		$plugins = get_plugins();

		if ( isset( $plugins[ $plugin_slug ] ) ) {
			return $plugins[ $plugin_slug ]['Name'] ?? basename( $plugin_slug, '.php' );
		}

		// Common plugin names for better display
		$known_plugins = array(
			'contact-form-7/wp-contact-form-7.php' => 'Contact Form 7',
			'wpforms-lite/wpforms.php'             => 'WPForms Lite',
			'wpforms/wpforms.php'                  => 'WPForms Pro',
			'gravityforms/gravityforms.php'        => 'Gravity Forms',
			'ninja-forms/ninja-forms.php'          => 'Ninja Forms',
			'forminator/forminator.php'            => 'Forminator',
			'fluentform/fluentform.php'            => 'Fluent Forms',
		);

		/**
		 * Filter the known plugin names.
		 *
		 * @since 3.3.0
		 *
		 * @param array  $known_plugins The known plugin names.
		 * @param string $plugin_slug   The plugin slug being looked up.
		 * @param self   $instance      The current instance.
		 */
		$known_plugins = apply_filters( 'divi_squad_known_plugin_names', $known_plugins, $plugin_slug, $this );

		if ( isset( $known_plugins[ $plugin_slug ] ) ) {
			return $known_plugins[ $plugin_slug ];
		}

		// Return a formatted name
		$name = basename( $plugin_slug, '.php' );
		$name = str_replace( array( '-', '_' ), ' ', $name );

		/**
		 * Filter the formatted plugin name.
		 *
		 * @since 3.3.0
		 *
		 * @param string $name          The formatted plugin name.
		 * @param string $plugin_slug   The plugin slug.
		 * @param self   $instance      The current instance.
		 */
		return apply_filters( 'divi_squad_formatted_plugin_name', ucwords( $name ), $plugin_slug, $this );
	}
}
