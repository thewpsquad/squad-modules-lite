<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Post Grid Load More REST API
 *
 * This file contains the PostGrid class which handles REST API endpoints
 * for the Post Grid module of Divi Squad.
 *
 * @since   3.0.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Rest_API_Routes\Version1\Modules;

use DiviSquad\Builder\Version4\Modules\Post_Grid as Post_Grid_Module;
use DiviSquad\Core\Supports\Polyfills\Str;
use DiviSquad\Rest_API_Routes\Base_Route;
use DiviSquad\Utils\Sanitization;
use Throwable;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Post Grid REST API Handler
 *
 * Manages REST API endpoints for the Post Grid module, including
 * functionality to load more posts.
 *
 * @since   3.0.0
 * @package DiviSquad
 */
class Post_Grid extends Base_Route {

	/**
	 * Get available routes for the Post Grid Module API.
	 *
	 * Registers REST API endpoints for the post grid functionality.
	 *
	 * @since 3.0.0
	 *
	 * @return array<string, array<int, array<string, list<$this|string>|string>>>
	 */
	public function get_routes(): array {
		$routes = array(
			'/module/post-grid/load-more' => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'load_more_posts' ),
					'permission_callback' => array( $this, 'check_load_more_permissions' ),
				),
			),
		);

		/**
		 * Filter the Post Grid module REST API routes.
		 *
		 * Allows developers to modify the available routes for the Post Grid module API.
		 *
		 * @since 3.4.1
		 *
		 * @param array<string, array<int, array<string, list<$this|string>|string>>> $routes         Default routes configuration.
		 * @param self                                                                $route_instance The current Post Grid API instance.
		 */
		return apply_filters( 'divi_squad_rest_post_grid_module_rest_routes', $routes, $this );
	}

	/**
	 * Check if the current request has permission to load more posts.
	 *
	 * @since 3.4.1
	 * @return bool|WP_Error True if the request has access, WP_Error object otherwise.
	 */
	public function check_load_more_permissions() {
		/**
		 * Filter whether to allow post loading for any user.
		 *
		 * @since 3.4.1
		 *
		 * @param bool $allow_any      Whether to allow unauthenticated users to load posts.
		 * @param self $route_instance The current Post Grid API instance.
		 */
		$allow_any = apply_filters( 'divi_squad_rest_post_grid_allow_any_user', true, $this );

		// By default, allow anyone to load more posts since this is public content
		if ( $allow_any ) {
			/**
			 * Fires after a successful permission check for post grid loading.
			 *
			 * @since 3.4.1
			 *
			 * @param self $route_instance The current Post Grid API instance.
			 */
			do_action( 'divi_squad_rest_post_grid_permission_check_passed', $this );

			return true;
		}

		// If restricted to logged-in users, check user status
		if ( ! is_user_logged_in() ) {
			/**
			 * Filters the error message when an unauthenticated user isn't allowed to load posts.
			 *
			 * @since 3.4.1
			 *
			 * @param string $error_message  The error message.
			 * @param self   $route_instance The current Post Grid API instance.
			 */
			$error_message = apply_filters(
				'divi_squad_rest_post_grid_permission_error_message',
				esc_html__( 'You must be logged in to load more posts.', 'squad-modules-for-divi' ),
				$this
			);

			return new WP_Error(
				'rest_forbidden',
				$error_message,
				array( 'status' => 403 )
			);
		}

		/**
		 * Fires after a successful permission check for post grid loading.
		 *
		 * @since 3.4.1
		 *
		 * @param self $route_instance The current Post Grid API instance.
		 */
		do_action( 'divi_squad_rest_post_grid_permission_check_passed', $this );

		return true;
	}

	/**
	 * Load more posts for the Post Grid module.
	 *
	 * Processes REST API requests to load additional posts with the given parameters.
	 *
	 * @since 3.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function load_more_posts( WP_REST_Request $request ) {
		try {
			$params = $request->get_json_params();

			/**
			 * Fires before validating post grid load more parameters.
			 *
			 * @since 3.4.1
			 *
			 * @param array           $params         The request parameters.
			 * @param WP_REST_Request $request        The REST request object.
			 * @param self            $route_instance The current Post Grid API instance.
			 */
			do_action( 'divi_squad_rest_post_grid_before_validate_params', $params, $request, $this );

			if ( ! isset( $params['query_args'], $params['content'] ) ) {
				/**
				 * Filters the error message when invalid parameters are provided.
				 *
				 * @since 3.4.1
				 *
				 * @param string          $error_message  The error message.
				 * @param array           $params         The request parameters.
				 * @param WP_REST_Request $request        The REST request object.
				 * @param self            $route_instance The current Post Grid API instance.
				 */
				$error_message = apply_filters(
					'divi_squad_rest_post_grid_invalid_params_error_message',
					esc_html__( 'Invalid or missing parameters.', 'squad-modules-for-divi' ),
					$params,
					$request,
					$this
				);

				return new WP_Error(
					'rest_invalid_params',
					$error_message,
					array( 'status' => 400 )
				);
			}

			$query_params = $this->sanitize_and_prepare_query_params( $params['query_args'] );
			$content      = wp_kses_post( $params['content'] );

			/**
			 * Filters the query parameters before fetching posts.
			 *
			 * @since 3.4.1
			 *
			 * @param array           $query_params   The sanitized query parameters.
			 * @param array           $params         The original request parameters.
			 * @param WP_REST_Request $request        The REST request object.
			 * @param self            $route_instance The current Post Grid API instance.
			 */
			$query_params = apply_filters( 'divi_squad_rest_post_grid_query_params', $query_params, $params, $request, $this );

			/**
			 * Filters the content template before fetching posts.
			 *
			 * @since 3.4.1
			 *
			 * @param string          $content        The sanitized content template.
			 * @param array           $params         The original request parameters.
			 * @param WP_REST_Request $request        The REST request object.
			 * @param self            $route_instance The current Post Grid API instance.
			 */
			$content = apply_filters( 'divi_squad_rest_post_grid_content_template', $content, $params, $request, $this );

			/**
			 * Fires before loading more posts.
			 *
			 * @since 3.4.1
			 *
			 * @param array           $query_params   The query parameters.
			 * @param string          $content        The content template.
			 * @param WP_REST_Request $request        The REST request object.
			 * @param self            $route_instance The current Post Grid API instance.
			 */
			do_action( 'divi_squad_rest_post_grid_before_load_more', $query_params, $content, $request, $this );

			// Initialize the module
			$post_grid_module = new Post_Grid_Module();
			$post_grid_module->squad_init_custom_hooks();

			// Get the posts HTML
			$posts = Post_Grid_Module::squad_get_posts_html( $query_params, $content );

			/**
			 * Fires after loading more posts.
			 *
			 * @since 3.4.1
			 *
			 * @param string          $posts          The HTML for the loaded posts.
			 * @param array           $query_params   The query parameters.
			 * @param string          $content        The content template.
			 * @param WP_REST_Request $request        The REST request object.
			 * @param self            $route_instance The current Post Grid API instance.
			 */
			do_action( 'divi_squad_rest_post_grid_after_load_more', $posts, $query_params, $content, $request, $this );

			if ( '' === $posts ) {
				/**
				 * Filters the error message when no posts are found.
				 *
				 * @since 3.4.1
				 *
				 * @param string          $error_message  The error message.
				 * @param array           $query_params   The query parameters.
				 * @param WP_REST_Request $request        The REST request object.
				 * @param self            $route_instance The current Post Grid API instance.
				 */
				$error_message = apply_filters(
					'divi_squad_rest_post_grid_no_posts_error_message',
					esc_html__( 'No posts found.', 'squad-modules-for-divi' ),
					$query_params,
					$request,
					$this
				);

				return new WP_Error(
					'rest_no_data',
					$error_message,
					array( 'status' => 404 )
				);
			}

			// Process content for response
			$html_content = Str::remove_new_lines_and_tabs( $posts );

			/**
			 * Filters the REST response data after successfully loading posts.
			 *
			 * @since 3.4.1
			 *
			 * @param array           $response_data  The response data.
			 * @param string          $posts          The HTML for the loaded posts.
			 * @param array           $query_params   The query parameters.
			 * @param WP_REST_Request $request        The REST request object.
			 * @param self            $route_instance The current Post Grid API instance.
			 */
			$response_data = apply_filters(
				'divi_squad_rest_post_grid_success_response',
				array(
					'type'   => 'success',
					'offset' => $query_params['list_post_offset'],
					'html'   => $html_content,
				),
				$posts,
				$query_params,
				$request,
				$this
			);

			return rest_ensure_response( $response_data );
		} catch ( Throwable $e ) {
			// Log error
			divi_squad()->log_error( $e, sprintf( 'Post grid load more error: %s', $e->getMessage() ) );

			/**
			 * Fires when an error occurs during post loading.
			 *
			 * @since 3.4.1
			 *
			 * @param Throwable       $e              The exception that was thrown.
			 * @param array           $params         The request parameters.
			 * @param WP_REST_Request $request        The REST request object.
			 * @param self            $route_instance The current Post Grid API instance.
			 */
			do_action( 'divi_squad_rest_post_grid_load_error', $e, $request->get_json_params(), $request, $this );

			/**
			 * Filters the error response when post loading fails.
			 *
			 * @since 3.4.1
			 *
			 * @param WP_Error        $error          The error object.
			 * @param Throwable       $e              The exception that was thrown.
			 * @param WP_REST_Request $request        The REST request object.
			 * @param self            $route_instance The current Post Grid API instance.
			 */
			return apply_filters(
				'divi_squad_rest_post_grid_error_response',
				new WP_Error(
					'rest_error',
					$e->getMessage(),
					array( 'status' => 500 )
				),
				$e,
				$request,
				$this
			);
		}
	}

	/**
	 * Sanitize and prepare query parameters.
	 *
	 * @since 3.0.0
	 *
	 * @param array<string, string> $query_args Query arguments.
	 *
	 * @return array<string, array<array<int|string>|string>|int|string> Sanitized query arguments.
	 */
	private function sanitize_and_prepare_query_params( array $query_args ): array {
		$query_params = array_map( array( Sanitization::class, 'sanitize_array' ), wp_unslash( $query_args ) );

		$posts_per_page    = isset( $query_params['posts_per_page'] ) ? absint( $query_params['posts_per_page'] ) : 10;
		$post_query_offset = isset( $query_params['offset'] ) ? absint( $query_params['offset'] ) : $posts_per_page;

		$query_params['list_post_offset'] = $post_query_offset;
		$query_params['is_rest_query']    = 'on';

		/**
		 * Filters the prepared query parameters.
		 *
		 * @since 3.4.1
		 *
		 * @param array<string, mixed>  $query_params   The prepared query parameters.
		 * @param array<string, string> $query_args     The original query arguments.
		 * @param self                  $route_instance The current Post Grid API instance.
		 */
		return apply_filters( 'divi_squad_rest_post_grid_prepared_query_params', $query_params, $query_args, $this );
	}
}
