<?php
/**
 * DiviSquad Admin Notice REST API Integration
 *
 * This file registers the REST API endpoints needed for the React-based
 * admin notice system to function. It provides endpoints for fetching notices
 * and managing notice actions.
 *
 * @since      3.3.3
 * @package    DiviSquad
 * @subpackage DiviSquad\Rest_API_Routes\Version1\Notices
 * @author     The WP Squad <support@squadmodules.com>
 * @license    GPL-2.0+
 * @link       https://wpsquad.com
 */

namespace DiviSquad\Rest_API_Routes\Version1\Notices;

use DiviSquad\Rest_API_Routes\Base_Route;
use Throwable;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Admin Notices REST API Handler
 *
 * Provides endpoints for fetching and managing admin notices for the React notice component.
 * This class handles all REST API interactions related to admin notices including:
 * - Retrieving active notices
 * - Managing review notices
 * - Handling pro activation notices
 * - Processing discount notices
 *
 * @since   3.4.0
 * @package DiviSquad
 */
class Admin_Notices extends Base_Route {

	/**
	 * Get available routes for the Admin Notices API.
	 *
	 * Registers all the REST API endpoints for admin notices including endpoints
	 * for retrieving notices and managing notice actions like closing, postponing,
	 * or acknowledging notices.
	 *
	 * @since  3.3.3
	 * @access public
	 *
	 * @return array<string, array<int, array<string, mixed>>> Routes configuration array with endpoint paths and their handlers.
	 */
	public function get_routes(): array {
		$routes = array(
			'/admin/notices'               => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_admin_notices' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
					'args'                => array(
						'scope' => array(
							'required'          => false,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => function ( $param ) {
								return in_array( $param, array( 'global', 'dashboard', 'settings' ), true );
							},
							'description'       => 'Scope of notices to retrieve',
							'default'           => 'global',
						),
					),
				),
			),
			'/notice/review/done'          => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'mark_review_done' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
				),
			),
			'/notice/review/next-week'     => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'set_review_reminder' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
				),
			),
			'/notice/review/close'         => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'increment_close_count' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
				),
			),
			'/notice/pro-activation/close' => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'close_activation_notice' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
				),
			),
			'/notice/discount/close'       => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'mark_discount_notice_done' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
				),
			),
		);

		/**
		 * Filters the admin notices routes configuration.
		 *
		 * Allows developers to modify the registered REST API routes for admin notices.
		 * This filter can be used to add, remove, or modify the routes and their configurations.
		 *
		 * @since 3.3.3
		 *
		 * @param array         $routes         The routes configuration array.
		 * @param Admin_Notices $route_instance The current Admin_Notices API instance.
		 */
		return apply_filters( 'divi_squad_rest_admin_notices_routes', $routes, $this );
	}

	/**
	 * Check if the current user has admin permissions.
	 *
	 * Verifies that the user has the necessary capabilities to access notice endpoints.
	 * By default, checks for 'manage_options' capability but can be filtered.
	 *
	 * @since  3.3.3
	 * @access public
	 *
	 * @return bool|WP_Error True if the request has admin access, WP_Error object otherwise.
	 */
	public function check_admin_permissions() {
		try {
			/**
			 * Filters the capability required to access admin notices endpoints.
			 *
			 * Allows developers to customize the capability required for accessing admin notice endpoints.
			 *
			 * @since 3.3.3
			 *
			 * @param string        $capability     The capability name. Default is 'manage_options'.
			 * @param Admin_Notices $route_instance The current Admin_Notices API instance.
			 */
			$capability = apply_filters( 'divi_squad_rest_admin_notices_capability', 'manage_options', $this );

			if ( ! current_user_can( $capability ) ) {
				/**
				 * Filters the error message when a user doesn't have permission to access admin notices.
				 *
				 * Allows customizing the error message shown to users without proper permissions.
				 *
				 * @since 3.3.3
				 *
				 * @param string        $error_message  The error message.
				 * @param string        $capability     The capability that was checked.
				 * @param Admin_Notices $route_instance The current Admin_Notices API instance.
				 */
				$error_message = apply_filters(
					'divi_squad_rest_admin_notices_permission_error_message',
					esc_html__( 'You do not have permissions to perform this action.', 'squad-modules-for-divi' ),
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
			 * Fires after a successful permission check for admin notices access.
			 *
			 * Executed when a user with appropriate permissions accesses the admin notices endpoints.
			 *
			 * @since 3.3.3
			 *
			 * @param Admin_Notices $route_instance The current Admin_Notices API instance.
			 */
			do_action( 'divi_squad_rest_admin_notices_permission_check_passed', $this );

			return true;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Error checking admin notices permissions' );

			/**
			 * Filters the error response when permission checking fails.
			 *
			 * Allows customizing the error response when an exception occurs during permission checking.
			 *
			 * @since 3.3.3
			 *
			 * @param WP_Error      $error          The error object.
			 * @param Throwable     $e              The exception that was thrown.
			 * @param Admin_Notices $route_instance The current Admin_Notices API instance.
			 */
			return apply_filters(
				'divi_squad_rest_admin_notices_permission_error_response',
				new WP_Error(
					'rest_permission_check_error',
					esc_html__( 'An error occurred while checking permissions.', 'squad-modules-for-divi' ),
					array( 'status' => 500 )
				),
				$e,
				$this
			);
		}
	}

	/**
	 * Get all admin notices for the current user.
	 *
	 * Retrieves all the notices that should be displayed to the current user,
	 * filtered by the requested scope. Handles initialization of notice classes
	 * and processes their render conditions.
	 *
	 * @since  3.3.3
	 * @access public
	 *
	 * @param WP_REST_Request $request Full details about the request including scope parameter.
	 *
	 * @return WP_REST_Response|WP_Error Response object with notices data or WP_Error object.
	 */
	public function get_admin_notices( WP_REST_Request $request ) {
		try {
			$scope = '' !== $request->get_param( 'scope' ) ? $request->get_param( 'scope' ) : 'global';

			/**
			 * Fires before fetching admin notices.
			 *
			 * Allows executing code before retrieving notices for the current user.
			 *
			 * @since 3.3.3
			 *
			 * @param string        $scope          The notice scope being requested.
			 * @param Admin_Notices $route_instance The current Admin_Notices API instance.
			 */
			do_action( 'divi_squad_rest_before_get_admin_notices', $scope, $this );

			// Initialize the notice manager
			divi_squad()->admin_notice->setup_notice_classes();

			$notice_instances = divi_squad()->admin_notice->get_notice_instances();
			$notices          = array();

			foreach ( $notice_instances as $notice ) {
				// Skip notices that don't match the requested scope
				if ( 'global' !== $scope && method_exists( $notice, 'get_scopes' ) && ! in_array( $scope, $notice->get_scopes(), true ) ) {
					continue;
				}

				$notice_args               = divi_squad()->admin_notice->resolve_icon_params( $notice );
				$notice_args['can_render'] = $notice->can_render_it();
				$notice_args['notice_id']  = $notice->get_notice_id();
				$notices[]                 = $notice_args;
			}

			/**
			 * Filters the admin notices data.
			 *
			 * Allows modifying the notice data before sending it to the client.
			 *
			 * @since 3.3.3
			 *
			 * @param array         $notices        The notice data.
			 * @param string        $scope          The notice scope.
			 * @param Admin_Notices $route_instance The current Admin_Notices API instance.
			 */
			$notices = apply_filters( 'divi_squad_rest_admin_notices_data', $notices, $scope, $this );

			/**
			 * Fires after fetching admin notices.
			 *
			 * Executed after notices have been retrieved and filtered.
			 *
			 * @since 3.3.3
			 *
			 * @param array         $notices        The notice data.
			 * @param string        $scope          The notice scope.
			 * @param Admin_Notices $route_instance The current Admin_Notices API instance.
			 */
			do_action( 'divi_squad_rest_after_get_admin_notices', $notices, $scope, $this );

			return rest_ensure_response( $notices );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Error fetching admin notices' );

			/**
			 * Filters the error response when fetching admin notices fails.
			 *
			 * Allows customizing the error response when an exception occurs during notice retrieval.
			 *
			 * @since 3.3.3
			 *
			 * @param WP_Error      $error          The error object.
			 * @param Throwable     $e              The exception that was thrown.
			 * @param Admin_Notices $route_instance The current Admin_Notices API instance.
			 */
			return apply_filters(
				'divi_squad_rest_admin_notices_error_response',
				new WP_Error(
					'rest_admin_notices_error',
					esc_html__( 'An error occurred while fetching admin notices.', 'squad-modules-for-divi' ),
					array( 'status' => 500 )
				),
				$e,
				$this
			);
		}
	}

	/**
	 * Mark the review notice as done.
	 *
	 * Sets the review flag to true indicating the user has completed the review action.
	 * This permanently dismisses the review notice.
	 *
	 * @since  3.3.3
	 * @access public
	 *
	 * @return WP_REST_Response|WP_Error Response object with success message or WP_Error object.
	 */
	public function mark_review_done() {
		try {
			/**
			 * Fires before marking the review notice as done.
			 *
			 * Allows executing code before recording that the review has been completed.
			 *
			 * @since 3.3.3
			 *
			 * @param Admin_Notices $route_instance The current Admin_Notices API instance.
			 */
			do_action( 'divi_squad_rest_before_mark_review_done', $this );

			$is_review_done = divi_squad()->memory->get( 'review_flag', false );

			/**
			 * Filters whether the review is already completed.
			 *
			 * Allows checking if the review is already marked as done before processing the request.
			 *
			 * @since 3.3.3
			 *
			 * @param bool          $is_review_done Whether the review is already completed.
			 * @param Admin_Notices $route_instance The current Admin_Notices API instance.
			 */
			$is_review_done = apply_filters( 'divi_squad_rest_review_is_completed', $is_review_done, $this );

			if ( true === $is_review_done ) {
				/**
				 * Filters the error message when the review is not available.
				 *
				 * Allows customizing the error message when the review has already been completed.
				 *
				 * @since 3.3.3
				 *
				 * @param string        $error_message  The error message.
				 * @param Admin_Notices $route_instance The current Admin_Notices API instance.
				 */
				$error_message = apply_filters(
					'divi_squad_rest_review_unavailable_message',
					esc_html__( 'The review is not available.', 'squad-modules-for-divi' ),
					$this
				);

				return new WP_Error(
					'rest_review_unavailable',
					$error_message,
					array( 'status' => 403 )
				);
			}

			// Mark the review as done
			divi_squad()->memory->set( 'review_flag', true );
			divi_squad()->memory->set( 'review_status', 'received' );

			/**
			 * Fires after marking the review notice as done.
			 *
			 * Executed after successfully recording that the review has been completed.
			 *
			 * @since 3.3.3
			 *
			 * @param Admin_Notices $route_instance The current Admin_Notices API instance.
			 */
			do_action( 'divi_squad_rest_after_mark_review_done', $this );

			/**
			 * Filters the response data after successfully marking the review as done.
			 *
			 * Allows customizing the success response when a review is marked as completed.
			 *
			 * @since 3.3.3
			 *
			 * @param array         $response_data  The response data.
			 * @param Admin_Notices $route_instance The current Admin_Notices API instance.
			 */
			$response_data = apply_filters(
				'divi_squad_rest_review_done_success_response',
				array(
					'code'    => 'success',
					'message' => 'received',
				),
				$this
			);

			return rest_ensure_response( $response_data );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Error marking review as done' );

			/**
			 * Fires when an error occurs during marking the review as done.
			 *
			 * Executed when an exception is thrown while marking a review as done.
			 *
			 * @since 3.3.3
			 *
			 * @param Throwable     $e              The exception that was thrown.
			 * @param Admin_Notices $route_instance The current Admin_Notices API instance.
			 */
			do_action( 'divi_squad_rest_review_mark_done_error', $e, $this );

			/**
			 * Filters the error response when marking the review as done fails.
			 *
			 * Allows customizing the error response when an exception occurs.
			 *
			 * @since 3.3.3
			 *
			 * @param WP_Error      $error          The error object.
			 * @param Throwable     $e              The exception that was thrown.
			 * @param Admin_Notices $route_instance The current Admin_Notices API instance.
			 */
			return apply_filters(
				'divi_squad_rest_review_error_response',
				new WP_Error(
					'rest_review_error',
					esc_html__( 'An error occurred while marking the review as done.', 'squad-modules-for-divi' ),
					array( 'status' => 500 )
				),
				$e,
				$this
			);
		}
	}

	/**
	 * Set a reminder for the review notice.
	 *
	 * Delays showing the review notice again for a specified number of days.
	 * Default is 7 days but can be customized via filter.
	 *
	 * @since  3.3.3
	 * @access public
	 *
	 * @return WP_REST_Response|WP_Error Response object with success message and time or WP_Error object.
	 */
	public function set_review_reminder() {
		try {
			/**
			 * Fires before setting a review reminder.
			 *
			 * Allows executing code before setting up a reminder for the review notice.
			 *
			 * @since 3.3.3
			 *
			 * @param Admin_Notices $route_instance The current Admin_Notices API instance.
			 */
			do_action( 'divi_squad_rest_before_set_review_reminder', $this );

			/**
			 * Filters the number of days to wait before showing the review notice again.
			 *
			 * Allows customizing the delay period for the review notice.
			 *
			 * @since 3.3.3
			 *
			 * @param int           $reminder_delay The number of days to wait. Default is 7.
			 * @param Admin_Notices $route_instance The current Admin_Notices API instance.
			 */
			$reminder_delay = apply_filters( 'divi_squad_rest_review_reminder_delay', 7, $this );

			$next_time = time() + ( $reminder_delay * DAY_IN_SECONDS );
			divi_squad()->memory->set( 'next_review_time', $next_time );

			/**
			 * Fires after setting a review reminder.
			 *
			 * Executed after successfully setting up a reminder for the review notice.
			 *
			 * @since 3.3.3
			 *
			 * @param int           $next_time      The timestamp of the next review reminder.
			 * @param int           $reminder_delay The number of days to wait.
			 * @param Admin_Notices $route_instance The current Admin_Notices API instance.
			 */
			do_action( 'divi_squad_rest_after_set_review_reminder', $next_time, $reminder_delay, $this );

			/**
			 * Filters the response data after successfully setting a review reminder.
			 *
			 * Allows customizing the success response when a review reminder is set.
			 *
			 * @since 3.3.3
			 *
			 * @param array         $response_data  The response data.
			 * @param int           $next_time      The timestamp of the next review reminder.
			 * @param Admin_Notices $route_instance The current Admin_Notices API instance.
			 */
			$response_data = apply_filters(
				'divi_squad_rest_review_reminder_success_response',
				array(
					'code'    => 'success',
					'message' => 'ok',
					'time'    => $next_time,
				),
				$next_time,
				$this
			);

			return rest_ensure_response( $response_data );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Error setting review reminder' );

			/**
			 * Fires when an error occurs during setting a review reminder.
			 *
			 * Executed when an exception is thrown while setting a review reminder.
			 *
			 * @since 3.3.3
			 *
			 * @param Throwable     $e              The exception that was thrown.
			 * @param Admin_Notices $route_instance The current Admin_Notices API instance.
			 */
			do_action( 'divi_squad_rest_review_set_reminder_error', $e, $this );

			/**
			 * Filters the error response when setting a review reminder fails.
			 *
			 * Allows customizing the error response when an exception occurs.
			 *
			 * @since 3.3.3
			 *
			 * @param WP_Error      $error          The error object.
			 * @param Throwable     $e              The exception that was thrown.
			 * @param Admin_Notices $route_instance The current Admin_Notices API instance.
			 */
			return apply_filters(
				'divi_squad_rest_review_reminder_error_response',
				new WP_Error(
					'rest_review_reminder_error',
					esc_html__( 'An error occurred while setting the review reminder.', 'squad-modules-for-divi' ),
					array( 'status' => 500 )
				),
				$e,
				$this
			);
		}
	}

	/**
	 * Increment the count of review notice closures.
	 *
	 * Tracks how many times a user has closed the review notice without taking action.
	 * This helps determine if the notice should be shown again or permanently dismissed.
	 *
	 * @since  3.3.3
	 * @access public
	 *
	 * @return WP_REST_Response Response object with the updated count.
	 */
	public function increment_close_count(): WP_REST_Response {
		try {
			/**
			 * Fires before incrementing the notice close count.
			 *
			 * Allows executing code before increasing the notice closure counter.
			 *
			 * @since 3.3.3
			 *
			 * @param Admin_Notices $route_instance The current Admin_Notices API instance.
			 */
			do_action( 'divi_squad_rest_before_increment_close_count', $this );

			$notice_close_count = divi_squad()->memory->get( 'notice_close_count', 0 );

			/**
			 * Filters the current notice close count before incrementing.
			 *
			 * Allows modifying the close count before it's incremented.
			 *
			 * @since 3.3.3
			 *
			 * @param int           $notice_close_count The current count.
			 * @param Admin_Notices $route_instance     The current Admin_Notices API instance.
			 */
			$notice_close_count = apply_filters( 'divi_squad_rest_review_close_count', $notice_close_count, $this );

			$new_count = absint( $notice_close_count ) + 1;
			divi_squad()->memory->set( 'notice_close_count', $new_count );

			/**
			 * Fires after incrementing the notice close count.
			 *
			 * Executed after successfully incrementing the notice closure counter.
			 *
			 * @since 3.3.3
			 *
			 * @param int           $new_count      The updated count.
			 * @param int           $old_count      The previous count.
			 * @param Admin_Notices $route_instance The current Admin_Notices API instance.
			 */
			do_action( 'divi_squad_rest_after_increment_close_count', $new_count, $notice_close_count, $this );

			/**
			 * Filters the response data after successfully incrementing the notice close count.
			 *
			 * Allows customizing the success response when the close count is incremented.
			 *
			 * @since 3.3.3
			 *
			 * @param array         $response_data  The response data.
			 * @param int           $new_count      The updated count.
			 * @param Admin_Notices $route_instance The current Admin_Notices API instance.
			 */
			$response_data = apply_filters(
				'divi_squad_rest_review_close_count_success_response',
				array(
					'code'    => 'success',
					'message' => 'closed',
					'count'   => $new_count,
				),
				$new_count,
				$this
			);

			return rest_ensure_response( $response_data );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Error incrementing review notice close count' );

			/**
			 * Fires when an error occurs during incrementing the notice close count.
			 *
			 * Executed when an exception is thrown while incrementing the close count.
			 *
			 * @since 3.3.3
			 *
			 * @param Throwable     $e              The exception that was thrown.
			 * @param Admin_Notices $route_instance The current Admin_Notices API instance.
			 */
			do_action( 'divi_squad_rest_review_increment_close_error', $e, $this );

			/**
			 * Filters the response data when incrementing the notice close count fails.
			 *
			 * Allows customizing the error response when an exception occurs.
			 *
			 * @since 3.3.3
			 *
			 * @param array         $response_data  The fallback response data.
			 * @param Throwable     $e              The exception that was thrown.
			 * @param Admin_Notices $route_instance The current Admin_Notices API instance.
			 */
			$response_data = apply_filters(
				'divi_squad_rest_review_close_count_error_response',
				array(
					'code'    => 'error',
					'message' => 'Error incrementing close count',
					'count'   => divi_squad()->memory->get( 'notice_close_count', 0 ),
				),
				$e,
				$this
			);

			return rest_ensure_response( $response_data );
		}
	}

	/**
	 * Close the pro activation notice.
	 *
	 * Permanently dismisses the pro activation notice by setting a flag in the database.
	 * This prevents the notice from appearing again for the current user. The method
	 * checks if the notice is already closed before proceeding and returns an error
	 * if it is.
	 *
	 * @since  3.3.3
	 * @access public
	 *
	 * @return WP_REST_Response|WP_Error Response object with success message or WP_Error object.
	 * @throws Throwable When there's an error closing the activation notice.
	 */
	public function close_activation_notice() {
		try {
			/**
			 * Fires before closing the pro activation notice.
			 *
			 * Allows executing code before dismissing the pro activation notice.
			 *
			 * @since 3.3.3
			 *
			 * @param Admin_Notices $route_instance The current Admin_Notices API instance.
			 */
			do_action( 'divi_squad_rest_before_close_activation_notice', $this );

			$is_notice_closed = divi_squad()->memory->get( 'pro_activation_notice_close', false );

			/**
			 * Filters whether the pro-activation notice is already closed.
			 *
			 * Allows checking if the notice is already dismissed before processing the request.
			 *
			 * @since 3.3.3
			 *
			 * @param bool          $is_notice_closed Whether the notice is already closed.
			 * @param Admin_Notices $route_instance   The current Admin_Notices API instance.
			 */
			$is_notice_closed = apply_filters( 'divi_squad_rest_pro_activation_notice_is_closed', $is_notice_closed, $this );

			if ( true === $is_notice_closed ) {
				/**
				 * Filters the error message when the pro-activation notice is not available.
				 *
				 * Allows customizing the error message when the notice has already been dismissed.
				 *
				 * @since 3.3.3
				 *
				 * @param string        $error_message  The error message.
				 * @param Admin_Notices $route_instance The current Admin_Notices API instance.
				 */
				$error_message = apply_filters(
					'divi_squad_rest_pro_activation_notice_unavailable_message',
					esc_html__( 'The notice is not available.', 'squad-modules-for-divi' ),
					$this
				);

				return new WP_Error(
					'rest_notice_unavailable',
					$error_message,
					array( 'status' => 403 )
				);
			}

			// Mark the notice as closed
			divi_squad()->memory->set( 'pro_activation_notice_close', true );

			/**
			 * Fires after closing the pro-activation notice.
			 *
			 * Executed after successfully dismissing the pro activation notice.
			 *
			 * @since 3.3.3
			 *
			 * @param Admin_Notices $route_instance The current Admin_Notices API instance.
			 */
			do_action( 'divi_squad_rest_after_close_activation_notice', $this );

			/**
			 * Filters the response data after successfully closing the pro-activation notice.
			 *
			 * Allows customizing the success response when a notice is dismissed.
			 *
			 * @since 3.3.3
			 *
			 * @param array         $response_data  The response data.
			 * @param Admin_Notices $route_instance The current Admin_Notices API instance.
			 */
			$response_data = apply_filters(
				'divi_squad_rest_pro_activation_notice_success_response',
				array(
					'code'    => 'success',
					'message' => 'closed',
				),
				$this
			);

			return rest_ensure_response( $response_data );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Error closing pro activation notice' );

			/**
			 * Fires when an error occurs during closing the pro activation notice.
			 *
			 * Executed when an exception is thrown while dismissing the pro activation notice.
			 *
			 * @since 3.3.3
			 *
			 * @param Throwable     $e              The exception that was thrown.
			 * @param Admin_Notices $route_instance The current Admin_Notices API instance.
			 */
			do_action( 'divi_squad_rest_pro_activation_notice_close_error', $e, $this );

			/**
			 * Filters the error response when closing the pro activation notice fails.
			 *
			 * Allows customizing the error response when an exception occurs.
			 *
			 * @since 3.3.3
			 *
			 * @param WP_Error      $error          The error object.
			 * @param Throwable     $e              The exception that was thrown.
			 * @param Admin_Notices $route_instance The current Admin_Notices API instance.
			 */
			return apply_filters(
				'divi_squad_rest_pro_activation_notice_error_response',
				new WP_Error(
					'rest_notice_error',
					esc_html__( 'An error occurred while closing the activation notice.', 'squad-modules-for-divi' ),
					array( 'status' => 500 )
				),
				$e,
				$this
			);
		}
	}

	/**
	 * Mark the discount notice as done.
	 *
	 * Sets the discount notice flag to true indicating the user has acknowledged the discount offer.
	 * This method also records the acknowledgment status as 'received' for analytics purposes.
	 * The method checks if the notice is already closed before proceeding and returns an
	 * appropriate error if it is.
	 *
	 * @since  3.3.3
	 * @access public
	 *
	 * @return WP_REST_Response|WP_Error Response object with success message or WP_Error object.
	 * @throws Throwable When there's an error marking the discount notice as done.
	 */
	public function mark_discount_notice_done() {
		try {
			/**
			 * Fires before marking the discount notice as done.
			 *
			 * Allows executing code before recording that the discount notice has been acknowledged.
			 *
			 * @since 3.3.3
			 *
			 * @param Admin_Notices $route_instance The current Admin_Notices API instance.
			 */
			do_action( 'divi_squad_rest_before_mark_discount_done', $this );

			$is_notice_closed = divi_squad()->memory->get( 'beta_campaign_notice_close', false );

			/**
			 * Filters whether the discount notice is already closed.
			 *
			 * Allows checking if the notice is already dismissed before processing the request.
			 *
			 * @since 3.3.3
			 *
			 * @param bool          $is_notice_closed Whether the notice is already closed.
			 * @param Admin_Notices $route_instance   The current Admin_Notices API instance.
			 */
			$is_notice_closed = apply_filters( 'divi_squad_rest_discount_notice_is_closed', $is_notice_closed, $this );

			if ( true === $is_notice_closed ) {
				/**
				 * Filters the error message when the discount notice is not available.
				 *
				 * Allows customizing the error message when the discount notice has already been dismissed.
				 *
				 * @since 3.3.3
				 *
				 * @param string        $error_message  The error message.
				 * @param Admin_Notices $route_instance The current Admin_Notices API instance.
				 */
				$error_message = apply_filters(
					'divi_squad_rest_discount_notice_unavailable_message',
					esc_html__( 'The discount is not available.', 'squad-modules-for-divi' ),
					$this
				);

				return new WP_Error(
					'rest_discount_unavailable',
					$error_message,
					array( 'status' => 403 )
				);
			}

			// Mark the notice as done
			divi_squad()->memory->set( 'beta_campaign_notice_close', true );
			divi_squad()->memory->set( 'beta_campaign_status', 'received' );

			/**
			 * Fires after marking the discount notice as done.
			 *
			 * Executed after successfully recording that the discount notice has been acknowledged.
			 *
			 * @since 3.3.3
			 *
			 * @param Admin_Notices $route_instance The current Admin_Notices API instance.
			 */
			do_action( 'divi_squad_rest_after_mark_discount_done', $this );

			/**
			 * Filters the response data after successfully marking the discount notice as done.
			 *
			 * Allows customizing the success response when a discount notice is marked as received.
			 *
			 * @since 3.3.3
			 *
			 * @param array         $response_data  The response data.
			 * @param Admin_Notices $route_instance The current Admin_Notices API instance.
			 */
			$response_data = apply_filters(
				'divi_squad_rest_discount_notice_success_response',
				array(
					'code'    => 'success',
					'message' => 'received',
				),
				$this
			);

			return rest_ensure_response( $response_data );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Error marking discount notice as done' );

			/**
			 * Fires when an error occurs during marking the discount notice as done.
			 *
			 * Executed when an exception is thrown while marking the discount notice as done.
			 *
			 * @since 3.3.3
			 *
			 * @param Throwable     $e              The exception that was thrown.
			 * @param Admin_Notices $route_instance The current Admin_Notices API instance.
			 */
			do_action( 'divi_squad_rest_discount_notice_mark_done_error', $e, $this );

			/**
			 * Filters the error response when marking the discount notice as done fails.
			 *
			 * Allows customizing the error response when an exception occurs.
			 *
			 * @since 3.3.3
			 *
			 * @param WP_Error      $error          The error object.
			 * @param Throwable     $e              The exception that was thrown.
			 * @param Admin_Notices $route_instance The current Admin_Notices API instance.
			 */
			return apply_filters(
				'divi_squad_rest_discount_notice_error_response',
				new WP_Error(
					'rest_discount_error',
					esc_html__( 'An error occurred while marking the discount notice as done.', 'squad-modules-for-divi' ),
					array( 'status' => 500 )
				),
				$e,
				$this
			);
		}
	}
}
