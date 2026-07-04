<?php // phpcs:ignore WordPress.Files.FileName

/**
 * REST API Routes for Welcome 60% Discount Notice
 *
 * This file contains the Discount class which handles REST API endpoints
 * for managing the Welcome 60% Discount Notice in Divi Squad.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   3.0.0
 */

namespace DiviSquad\Managers\RestRoutes\Version1\Notices;

use DiviSquad\Base\Factories\RestRoute\Route;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Discount Notice REST API Handler
 *
 * Manages REST API endpoints for the Welcome 60% Discount Notice,
 * including functionality to mark the notice as done.
 *
 * @package DiviSquad
 * @since   3.0.0
 */
class Discount extends Route {

	/**
	 * Get available routes for the Discount Notice API.
	 *
	 * @return array Available routes for the Discount Notice API.
	 */
	public function get_routes() {
		return array(
			'/notice/discount/done' => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'mark_discount_notice_done' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
				),
			),
		);
	}

	/**
	 * Check if the current user has admin permissions.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has admin access, WP_Error object otherwise.
	 */
	public function check_admin_permissions( $request ) {
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
	 * Mark the discount notice as done.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function mark_discount_notice_done( $request ) {
		if ( divi_squad()->memory->get( 'beta_campaign_notice_close', false ) ) {
			return new WP_Error(
				'rest_discount_unavailable',
				esc_html__( 'The discount is not available.', 'squad-modules-for-divi' ),
				array( 'status' => 403 )
			);
		}

		divi_squad()->memory->set( 'beta_campaign_notice_close', true );
		divi_squad()->memory->set( 'beta_campaign_status', 'received' );

		return rest_ensure_response(
			array(
				'code'    => 'success',
				'message' => 'received',
			)
		);
	}
}
