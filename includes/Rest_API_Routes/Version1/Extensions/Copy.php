<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Copy Extension REST API
 *
 * This file contains the Copy class which handles REST API endpoints
 * for the Copy extension of Divi Squad.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   3.0.0
 */

namespace DiviSquad\Rest_API_Routes\Version1\Extensions;

use DiviSquad\Extensions\Copy as CopyExtension;
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
 * @package DiviSquad
 * @since   3.0.0
 */
class Copy extends Base_Route {

	/**
	 * Get available routes for the Copy Extension API.
	 *
	 * @return array<string, array<int, array<string, list<$this|string>|string>>>
	 */
	public function get_routes(): array {
		return array(
			'/extension/copy/duplicate-post' => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'duplicate_posts' ),
					'permission_callback' => array( $this, 'check_duplicate_permissions' ),
				),
			),
		);
	}

	/**
	 * Check if the current user has permissions to duplicate posts.
	 *
	 * @return bool|WP_Error True if the request has duplication access, WP_Error object otherwise.
	 */
	public function check_duplicate_permissions() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new WP_Error(
				'rest_forbidden',
				esc_html__( 'You do not have permissions to duplicate posts.', 'squad-modules-for-divi' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Duplicate posts based on the provided options.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function duplicate_posts( WP_REST_Request $request ) {
		$params = $request->get_json_params();

		if ( count( $params ) < 1 ) {
			return new WP_Error(
				'rest_no_body_params',
				esc_html__( 'No data provided.', 'squad-modules-for-divi' ),
				array( 'status' => 400 )
			);
		}

		try {
			// Sanitize the parameters.
			$options = array_map( array( Sanitization::class, 'sanitize_array' ), wp_unslash( $params ) );

			CopyExtension::duplicate_the_post( $options );

			return new WP_REST_Response(
				array(
					'success' => true,
					'message' => esc_html__( 'Post(s) duplicated successfully.', 'squad-modules-for-divi' ),
				),
				200
			);
		} catch ( Throwable $e ) {
			return new WP_Error(
				'rest_error',
				$e->getMessage(),
				array( 'status' => 400 )
			);
		}
	}
}
