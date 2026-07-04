<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Post Grid Load More REST API
 *
 * This file contains the PostGrid class which handles REST API endpoints
 * for the Post Grid module of Divi Squad.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   3.0.0
 */

namespace DiviSquad\Managers\RestRoutes\Version1\Modules;

use DiviSquad\Base\Factories\RestRoute\Route;
use DiviSquad\Modules;
use DiviSquad\Core\Supports\Polyfills\Str;
use DiviSquad\Utils\Sanitization;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * PostGrid REST API Handler
 *
 * Manages REST API endpoints for the Post Grid module, including
 * functionality to load more posts.
 *
 * @package DiviSquad
 * @since   3.0.0
 */
class PostGrid extends Route {

	/**
	 * Get available routes for the PostGrid Module API.
	 *
	 * @return array Available routes for the PostGrid Module API.
	 */
	public function get_routes() {
		return array(
			'/module/post-grid/load-more' => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'load_more_posts' ),
					'permission_callback' => '__return_true',
				),
			),
		);
	}

	/**
	 * Load more posts for the Post Grid module.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function load_more_posts( $request ) {
		$params = $request->get_json_params();

		if ( empty( $params ) || ! isset( $params['query_args'], $params['content'] ) ) {
			return new WP_Error(
				'rest_invalid_params',
				esc_html__( 'Invalid or missing parameters.', 'squad-modules-for-divi' ),
				array( 'status' => 400 )
			);
		}

		$query_params = $this->sanitize_and_prepare_query_params( $params['query_args'] );
		$content      = wp_kses_post( $params['content'] );

		$post_grid_module = new Modules\PostGrid();
		$post_grid_module->squad_init_custom_hooks();

		$posts = Modules\PostGrid::squad_get_posts_html( $query_params, $content );

		if ( empty( $posts ) ) {
			return new WP_Error(
				'rest_no_data',
				esc_html__( 'No posts found.', 'squad-modules-for-divi' ),
				array( 'status' => 404 )
			);
		}

		return rest_ensure_response(
			array(
				'type'   => 'success',
				'offset' => $query_params['list_post_offset'],
				'html'   => Str::remove_new_lines_and_tabs( $posts ),
			)
		);
	}

	/**
	 * Sanitize and prepare query parameters.
	 *
	 * @param array $query_args Raw query arguments.
	 * @return array Sanitized and prepared query arguments.
	 */
	private function sanitize_and_prepare_query_params( $query_args ) {
		$query_params = array_map( array( Sanitization::class, 'sanitize_array' ), wp_unslash( $query_args ) );

		$posts_per_page    = isset( $query_params['posts_per_page'] ) ? absint( $query_params['posts_per_page'] ) : 10;
		$post_query_offset = isset( $query_params['offset'] ) ? absint( $query_params['offset'] ) : $posts_per_page;

		$query_params['list_post_offset'] = $post_query_offset;
		$query_params['is_rest_query']    = 'on';

		return $query_params;
	}
}
