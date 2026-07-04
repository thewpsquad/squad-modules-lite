<?php // phpcs:ignore WordPress.Files.FileName

/**
 * REST API Routes for What's New (Changelog)
 *
 * This file contains the Changelog class which handles REST API endpoints
 * for retrieving changelog information in Divi Squad.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   1.0.0
 */

namespace DiviSquad\Rest_API_Routes\Version1\Whats_New;

use DiviSquad\Rest_API_Routes\Base_Route;
use WP_Error;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Changelog REST API Handler
 *
 * Manages REST API endpoints for retrieving changelog information.
 *
 * @package DiviSquad
 * @since   1.0.0
 */
class Changelog extends Base_Route {

	/**
	 * Get available routes for the Changelog API.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	public function get_routes(): array {
		return array(
			'/whats-new' => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_changelog_data' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
				),
			),
		);
	}

	/**
	 * Check if the current user has admin permissions.
	 *
	 * @since 3.1.4
	 *
	 * @return bool|WP_Error True if the request has admin access, WP_Error object otherwise.
	 */
	public function check_admin_permissions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				esc_html__( 'You do not have permissions to access this endpoint.', 'squad-modules-for-divi' ),
				array( 'status' => 403 )
			);
		}
		return true;
	}

	/**
	 * Retrieve the changelog file data.
	 *
	 * @since 1.0.0
	 *
	 * @return WP_REST_Response|WP_Error Response object or WP_Error object.
	 */
	public function get_changelog_data() {
		$content = $this->get_changelog_content();

		if ( is_wp_error( $content ) ) {
			return $content;
		}

		return rest_ensure_response(
			array(
				'code'   => 'success',
				'readme' => $content,
			)
		);
	}

	/**
	 * Get the changelog content.
	 *
	 * @since 3.1.4
	 *
	 * @return WP_Error|string Changelog content or WP_Error on failure.
	 */
	private function get_changelog_content() {
		$changelog_file = divi_squad()->get_path( '/changelog.txt' );

		if ( ! divi_squad()->get_wp_fs()->exists( $changelog_file ) ) {
			return new WP_Error(
				'rest_file_not_found',
				esc_html__( 'Changelog file not found.', 'squad-modules-for-divi' ),
				array( 'status' => 404 )
			);
		}

		$content = divi_squad()->get_wp_fs()->get_contents( $changelog_file );

		if ( ( '' === $content ) || ( false === $content ) ) {
			return new WP_Error(
				'rest_file_empty',
				esc_html__( 'Changelog file is empty.', 'squad-modules-for-divi' ),
				array( 'status' => 204 )
			);
		}

		return $content;
	}
}
