<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Copy Extension REST API
 *
 * This file contains the Copy class which handles REST API endpoints
 * for the Copy extension of Divi Squad.
 *
 * @since   3.0.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Rest_API_Routes\Version1\Extensions;

use DiviSquad\Extensions\WordPress\Copy as CopyExtension;
use DiviSquad\Rest_API_Routes\Base_Route;
use DiviSquad\Utils\Sanitization;
use Throwable;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Copy Extension REST API Handler
 *
 * Manages REST API endpoints for the Copy extension, including
 * functionality to duplicate posts.
 *
 * @since   3.0.0
 * @package DiviSquad
 */
class Copy extends Base_Route {

	/**
	 * Get available routes for the Copy Extension API.
	 *
	 * Registers REST API endpoints for post duplication functionality.
	 *
	 * @since 3.0.0
	 * @return array<string, array<int, array<string, list<$this|string>|string>>>
	 */
	public function get_routes(): array {
		$routes = array(
			'/extension/copy/duplicate-post' => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'duplicate_posts' ),
					'permission_callback' => array( $this, 'check_duplicate_permissions' ),
				),
			),
		);

		/**
		 * Filter the Copy extension REST API routes.
		 *
		 * Allows developers to modify the available routes for the Copy extension API.
		 *
		 * @since 3.4.1
		 *
		 * @param array<string, array<int, array<string, list<$this|string>|string>>> $routes         Default routes configuration.
		 * @param self                                                                $route_instance The current Copy API instance.
		 */
		return apply_filters( 'divi_squad_rest_copy_extension_rest_routes', $routes, $this );
	}

	/**
	 * Check if the current user has permissions to duplicate posts.
	 *
	 * Validates user capabilities to ensure they have proper access to duplicate posts.
	 *
	 * @since 3.0.0
	 * @return bool|WP_Error True if the request has duplication access, WP_Error object otherwise.
	 */
	public function check_duplicate_permissions() {
		/**
		 * Filter the capability required to duplicate posts.
		 *
		 * @since 3.4.1
		 *
		 * @param string $capability     The capability required to duplicate posts.
		 * @param self   $route_instance The current Copy API instance.
		 */
		$required_capability = apply_filters( 'divi_squad_rest_copy_duplicate_capability', 'edit_posts', $this );

		if ( ! current_user_can( $required_capability ) ) {
			/**
			 * Filters the error message when a user doesn't have permission to duplicate posts.
			 *
			 * @since 3.4.1
			 *
			 * @param string $error_message  The error message.
			 * @param string $capability     The capability that was checked.
			 * @param self   $route_instance The current Copy API instance.
			 */
			$error_message = apply_filters(
				'divi_squad_rest_copy_permission_error_message',
				esc_html__( 'You do not have permissions to duplicate posts.', 'squad-modules-for-divi' ),
				$required_capability,
				$this
			);

			return new WP_Error(
				'rest_forbidden',
				$error_message,
				array( 'status' => 403 )
			);
		}

		/**
		 * Fires after a successful permission check for post-duplication.
		 *
		 * @since 3.4.1
		 *
		 * @param self $route_instance The current Copy API instance.
		 */
		do_action( 'divi_squad_rest_copy_permission_check_passed', $this );

		return true;
	}

	/**
	 * Duplicate posts based on the provided options.
	 *
	 * Processes REST API requests to duplicate posts with the given parameters.
	 *
	 * @since 3.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function duplicate_posts( WP_REST_Request $request ) {
		$params = $request->get_json_params();

		/**
		 * Fires before validating post-duplication parameters.
		 *
		 * @since 3.4.1
		 *
		 * @param array           $params         The request parameters.
		 * @param WP_REST_Request $request        The REST request object.
		 * @param self            $route_instance The current Copy API instance.
		 */
		do_action( 'divi_squad_rest_copy_before_validate_params', $params, $request, $this );

		if ( count( $params ) < 1 ) {
			/**
			 * Filters the error message when no parameters are provided.
			 *
			 * @since 3.4.1
			 *
			 * @param string          $error_message  The error message.
			 * @param array           $params         The request parameters.
			 * @param WP_REST_Request $request        The REST request object.
			 * @param self            $route_instance The current Copy API instance.
			 */
			$error_message = apply_filters(
				'divi_squad_rest_copy_no_params_error_message',
				esc_html__( 'No data provided.', 'squad-modules-for-divi' ),
				$params,
				$request,
				$this
			);

			return new WP_Error(
				'rest_no_body_params',
				$error_message,
				array( 'status' => 400 )
			);
		}

		try {
			// Sanitize the parameters.
			$options = array_map( array( Sanitization::class, 'sanitize_array' ), wp_unslash( $params ) );

			/**
			 * Filters the duplication options before processing.
			 *
			 * @since 3.4.1
			 *
			 * @param array           $options        The sanitized duplication options.
			 * @param array           $params         The original request parameters.
			 * @param WP_REST_Request $request        The REST request object.
			 * @param self            $route_instance The current Copy API instance.
			 */
			$options = apply_filters( 'divi_squad_rest_copy_duplication_options', $options, $params, $request, $this );

			/**
			 * Fires before duplicating posts.
			 *
			 * @since 3.4.1
			 *
			 * @param array           $options        The duplication options.
			 * @param WP_REST_Request $request        The REST request object.
			 * @param self            $route_instance The current Copy API instance.
			 */
			do_action( 'divi_squad_rest_copy_before_duplicate', $options, $request, $this );

			// Execute the duplication process.
			CopyExtension::duplicate_the_post( $options );

			/**
			 * Fires after duplicating posts.
			 *
			 * @since 3.4.1
			 *
			 * @param bool            $duplicate_result The duplication result.
			 * @param array           $options          The duplication options.
			 * @param WP_REST_Request $request          The REST request object.
			 * @param self            $route_instance   The current Copy API instance.
			 */
			do_action( 'divi_squad_rest_copy_after_duplicate', true, $options, $request, $this );

			/**
			 * Filters the success message after post duplication.
			 *
			 * @since 3.4.1
			 *
			 * @param string          $success_message  The success message.
			 * @param array           $options          The duplication options.
			 * @param mixed           $duplicate_result The duplication result.
			 * @param WP_REST_Request $request          The REST request object.
			 * @param self            $route_instance   The current Copy API instance.
			 */
			$success_message = apply_filters(
				'divi_squad_rest_copy_success_message',
				esc_html__( 'Post(s) duplicated successfully.', 'squad-modules-for-divi' ),
				$options,
				true,
				$request,
				$this
			);

			/**
			 * Filters the REST response data after successful duplication.
			 *
			 * @since 3.4.1
			 *
			 * @param array           $response_data  The response data.
			 * @param array           $options        The duplication options.
			 * @param WP_REST_Request $request        The REST request object.
			 * @param self            $route_instance The current Copy API instance.
			 */
			$response_data = apply_filters(
				'divi_squad_rest_copy_success_response',
				array(
					'success' => true,
					'message' => $success_message,
				),
				$options,
				$request,
				$this
			);

			return new WP_REST_Response( $response_data, 200 );
		} catch ( Throwable $e ) {
			/**
			 * Fires when an error occurs during post-duplication.
			 *
			 * @since 3.4.1
			 *
			 * @param Throwable       $e              The exception that was thrown.
			 * @param array           $params         The request parameters.
			 * @param WP_REST_Request $request        The REST request object.
			 * @param self            $route_instance The current Copy API instance.
			 */
			do_action( 'divi_squad_rest_copy_duplication_error', $e, $params, $request, $this );

			divi_squad()->log_error( $e, sprintf( 'Post duplication error: %s', $e->getMessage() ) );

			/**
			 * Filters the error response when post-duplication fails.
			 *
			 * @since 3.4.1
			 *
			 * @param WP_Error        $error          The error object.
			 * @param Throwable       $e              The exception that was thrown.
			 * @param array           $params         The request parameters.
			 * @param WP_REST_Request $request        The REST request object.
			 * @param self            $route_instance The current Copy API instance.
			 */
			return apply_filters(
				'divi_squad_rest_copy_error_response',
				new WP_Error(
					'rest_error',
					$e->getMessage(),
					array( 'status' => 400 )
				),
				$e,
				$params,
				$request,
				$this
			);
		}
	}
}
