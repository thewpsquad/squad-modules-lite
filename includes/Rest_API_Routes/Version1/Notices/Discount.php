<?php // phpcs:ignore WordPress.Files.FileName

/**
 * REST API Routes for Welcome 60% Discount Notice
 *
 * This file contains the Discount class which handles REST API endpoints
 * for managing the Welcome 60% Discount Notice in Divi Squad.
 *
 * @since   3.0.0
 * @author  The WP Squad <support@squadmodules.com>
 * @package DiviSquad
 */

namespace DiviSquad\Rest_API_Routes\Version1\Notices;

use DiviSquad\Rest_API_Routes\Base_Route;
use WP_Error;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Discount Notice REST API Handler
 *
 * Manages REST API endpoints for the Welcome 60% Discount Notice,
 * including functionality to mark the notice as done.
 *
 * @since   3.0.0
 * @package DiviSquad
 */
class Discount extends Base_Route {

	/**
	 * Get available routes for the Discount Notice API.
	 *
	 * @return array<string, array<int, array<string, list<$this|string>|string>>>
	 */
	public function get_routes(): array {
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
	 * Mark the discount notice as done.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function mark_discount_notice_done() {
		if ( true === divi_squad()->memory->get( 'beta_campaign_notice_close', false ) ) {
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
