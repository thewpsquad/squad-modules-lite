<?php
/**
 * REST API Routes for Plugin Review Notice
 *
 * This file contains the Review class which handles REST API endpoints
 * for managing the Plugin Review Notice in Divi Squad.
 *
 * @since   1.0.0
 * @author  The WP Squad <support@squadmodules.com>
 * @package DiviSquad\Managers\RestRoutes\Version1\Notices
 */

namespace DiviSquad\Rest_API_Routes\Version1\Notices;

use DiviSquad\Rest_API_Routes\Base_Route;
use WP_Error;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Review Notice REST API Handler
 *
 * Manages REST API endpoints for the Plugin Review Notice,
 * including functionality to handle various user interactions.
 *
 * @since   1.0.0
 * @package DiviSquad\Managers\RestRoutes\Version1\Notices
 */
class Review extends Base_Route {

	/**
	 * Number of days to wait before showing the notice again.
	 *
	 * @var int
	 */
	protected int $reminder_delay = 7;

	/**
	 * Get available routes for the Review Notice API.
	 *
	 * @return array<string, array<int, array<string, list<$this|string>|string>>>
	 */
	public function get_routes(): array {
		return array(
			'/notice/review/done'        => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'mark_review_done' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
				),
			),
			'/notice/review/next-week'   => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'set_review_reminder' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
				),
			),
			'/notice/review/close-count' => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'increment_close_count' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
				),
			),
			'/notice/review/ask-support' => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'increment_support_request_count' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
				),
			),
		);
	}

	/**
	 * Check if the current user has admin permissions.
	 *
	 * @return bool|WP_Error True if the request has admin access, WP_Error object otherwise.
	 */
	public function check_admin_permissions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				esc_html__( 'You do not have permissions to perform this action.', 'squad-modules-for-divi' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Mark the review as completed.
	 *
	 * @return WP_REST_Response|WP_Error Response object or WP_Error object.
	 */
	public function mark_review_done() {
		if ( true === divi_squad()->memory->get( 'review_flag', false ) ) {
			return new WP_Error(
				'rest_review_unavailable',
				esc_html__( 'The review is not available.', 'squad-modules-for-divi' ),
				array( 'status' => 403 )
			);
		}

		divi_squad()->memory->set( 'review_flag', true );
		divi_squad()->memory->set( 'review_status', 'received' );

		return rest_ensure_response(
			array(
				'code'    => 'success',
				'message' => 'received',
			)
		);
	}

	/**
	 * Set a reminder for the review.
	 *
	 * @return WP_REST_Response|WP_Error Response object or WP_Error object.
	 */
	public function set_review_reminder() {
		if ( '' !== divi_squad()->memory->get( 'next_review_time' ) ) {
			return new WP_Error(
				'rest_review_unavailable',
				esc_html__( 'The review reminder is already set.', 'squad-modules-for-divi' ),
				array( 'status' => 403 )
			);
		}

		$next_time = time() + ( $this->reminder_delay * DAY_IN_SECONDS );
		divi_squad()->memory->set( 'next_review_time', $next_time );

		return rest_ensure_response(
			array(
				'code'    => 'success',
				'message' => 'ok',
				'time'    => $next_time,
			)
		);
	}

	/**
	 * Increment the count of review notice closures.
	 *
	 * @return WP_REST_Response Response object with the updated count.
	 */
	public function increment_close_count(): WP_REST_Response {
		$notice_close_count = divi_squad()->memory->get( 'notice_close_count', 0 );
		divi_squad()->memory->set( 'notice_close_count', absint( $notice_close_count ) + 1 );

		return rest_ensure_response(
			array(
				'code'    => 'success',
				'message' => 'closed',
				'count'   => divi_squad()->memory->get( 'notice_close_count' ),
			)
		);
	}

	/**
	 * Increment the count of support requests from the review notice.
	 *
	 * @return WP_REST_Response Response object with the updated count.
	 */
	public function increment_support_request_count(): WP_REST_Response {
		$ask_support_count = divi_squad()->memory->get( 'ask_support_count', 0 );
		divi_squad()->memory->set( 'ask_support_count', absint( $ask_support_count ) + 1 );

		return rest_ensure_response(
			array(
				'code'    => 'success',
				'message' => 'redirect',
				'count'   => divi_squad()->memory->get( 'ask_support_count' ),
			)
		);
	}
}
