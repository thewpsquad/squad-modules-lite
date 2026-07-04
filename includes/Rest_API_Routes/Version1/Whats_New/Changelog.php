<?php // phpcs:ignore WordPress.Files.FileName

/**
 * REST API Routes for What's New (Changelog)
 *
 * This file contains the Changelog class which handles REST API endpoints
 * for retrieving changelog information in Divi Squad.
 *
 * @since   1.0.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Rest_API_Routes\Version1\Whats_New;

use DiviSquad\Rest_API_Routes\Base_Route;
use Throwable;
use WP_Error;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Changelog REST API Handler
 *
 * Manages REST API endpoints for retrieving changelog information.
 *
 * @since   1.0.0
 * @package DiviSquad
 */
class Changelog extends Base_Route {

	/**
	 * Get available routes for the Changelog API.
	 *
	 * @since  1.0.0
	 *
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	public function get_routes(): array {
		$routes = array(
			'/whats-new' => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_changelog_data' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
				),
			),
		);

		/**
		 * Filters the changelog routes configuration.
		 *
		 * @since 1.0.0
		 *
		 * @param array $routes         The routes configuration array.
		 * @param self  $route_instance The current Changelog API instance.
		 */
		return apply_filters( 'divi_squad_rest_changelog_routes', $routes, $this );
	}

	/**
	 * Check if the current user has admin permissions.
	 *
	 * @since  3.1.4
	 *
	 * @return bool|WP_Error True if the request has admin access, WP_Error object otherwise.
	 */
	public function check_admin_permissions() {
		try {
			/**
			 * Filters the capability required to access changelog endpoints.
			 *
			 * @since 3.1.4
			 *
			 * @param string $capability     The capability name.
			 * @param self   $route_instance The current Changelog API instance.
			 */
			$capability = apply_filters( 'divi_squad_rest_changelog_capability', 'manage_options', $this );

			if ( ! current_user_can( $capability ) ) {
				/**
				 * Filters the error message when a user doesn't have permission to access the changelog.
				 *
				 * @since 3.1.4
				 *
				 * @param string $error_message  The error message.
				 * @param string $capability     The capability that was checked.
				 * @param self   $route_instance The current Changelog API instance.
				 */
				$error_message = apply_filters(
					'divi_squad_rest_changelog_permission_error_message',
					esc_html__( 'You do not have permissions to access this endpoint.', 'squad-modules-for-divi' ),
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
			 * Fires after a successful permission check for changelog access.
			 *
			 * @since 3.1.4
			 *
			 * @param self $route_instance The current Changelog API instance.
			 */
			do_action( 'divi_squad_rest_changelog_permission_check_passed', $this );

			return true;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Error checking changelog permissions' );

			/**
			 * Filters the error response when permission checking fails.
			 *
			 * @since 3.1.4
			 *
			 * @param WP_Error  $error          The error object.
			 * @param Throwable $e              The exception that was thrown.
			 * @param self      $route_instance The current Changelog API instance.
			 */
			return apply_filters(
				'divi_squad_rest_changelog_permission_error_response',
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
	 * Retrieve the changelog file data.
	 *
	 * @since  1.0.0
	 *
	 * @return WP_REST_Response|WP_Error Response object or WP_Error object.
	 */
	public function get_changelog_data() {
		try {
			/**
			 * Fires before retrieving changelog data.
			 *
			 * @since 1.0.0
			 *
			 * @param self $route_instance The current Changelog API instance.
			 */
			do_action( 'divi_squad_rest_changelog_before_get_data', $this );

			$content = $this->get_changelog_content();

			if ( is_wp_error( $content ) ) {
				return $content;
			}

			/**
			 * Filters the changelog content before sending the response.
			 *
			 * @since 1.0.0
			 *
			 * @param string $content        The changelog content.
			 * @param self   $route_instance The current Changelog API instance.
			 */
			$content = apply_filters( 'divi_squad_rest_changelog_content', $content, $this );

			$response_data = array(
				'code'   => 'success',
				'readme' => $content,
			);

			/**
			 * Filters the changelog response data.
			 *
			 * @since 1.0.0
			 *
			 * @param array  $response_data  The response data array.
			 * @param string $content        The changelog content.
			 * @param self   $route_instance The current Changelog API instance.
			 */
			$response_data = apply_filters( 'divi_squad_rest_changelog_response', $response_data, $content, $this );

			/**
			 * Fires after successfully retrieving changelog data.
			 *
			 * @since 1.0.0
			 *
			 * @param array  $response_data  The response data.
			 * @param string $content        The changelog content.
			 * @param self   $route_instance The current Changelog API instance.
			 */
			do_action( 'divi_squad_rest_changelog_after_get_data', $response_data, $content, $this );

			return rest_ensure_response( $response_data );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Error retrieving changelog data' );

			/**
			 * Fires when an error occurs during changelog retrieval.
			 *
			 * @since 1.0.0
			 *
			 * @param Throwable $e              The exception that was thrown.
			 * @param self      $route_instance The current Changelog API instance.
			 */
			do_action( 'divi_squad_rest_changelog_retrieval_error', $e, $this );

			/**
			 * Filters the error response when changelog retrieval fails.
			 *
			 * @since 1.0.0
			 *
			 * @param WP_Error  $error          The error object.
			 * @param Throwable $e              The exception that was thrown.
			 * @param self      $route_instance The current Changelog API instance.
			 */
			return apply_filters(
				'divi_squad_rest_changelog_error_response',
				new WP_Error(
					'rest_changelog_error',
					esc_html__( 'An error occurred while retrieving the changelog.', 'squad-modules-for-divi' ),
					array( 'status' => 500 )
				),
				$e,
				$this
			);
		}
	}

	/**
	 * Get the changelog content.
	 *
	 * @since  3.1.4
	 *
	 * @return WP_Error|string Changelog content or WP_Error on failure.
	 */
	private function get_changelog_content() {
		try {
			/**
			 * Filters the changelog file path.
			 *
			 * @since 3.1.4
			 *
			 * @param string $changelog_file The path to the changelog file.
			 * @param self   $route_instance The current Changelog API instance.
			 */
			$changelog_file = apply_filters( 'divi_squad_rest_changelog_file_path', divi_squad()->get_path( '/changelog.txt' ), $this );

			/**
			 * Fires before checking if the changelog file exists.
			 *
			 * @since 3.1.4
			 *
			 * @param string $changelog_file The path to the changelog file.
			 * @param self   $route_instance The current Changelog API instance.
			 */
			do_action( 'divi_squad_rest_changelog_before_file_check', $changelog_file, $this );

			if ( ! divi_squad()->get_wp_fs()->exists( $changelog_file ) ) {
				/**
				 * Filters the error message when the changelog file is not found.
				 *
				 * @since 3.1.4
				 *
				 * @param string $error_message  The error message.
				 * @param string $changelog_file The path to the changelog file.
				 * @param self   $route_instance The current Changelog API instance.
				 */
				$error_message = apply_filters(
					'divi_squad_rest_changelog_file_not_found_message',
					esc_html__( 'Changelog file not found.', 'squad-modules-for-divi' ),
					$changelog_file,
					$this
				);

				return new WP_Error(
					'rest_file_not_found',
					$error_message,
					array( 'status' => 404 )
				);
			}

			/**
			 * Fires before reading the changelog file contents.
			 *
			 * @since 3.1.4
			 *
			 * @param string $changelog_file The path to the changelog file.
			 * @param self   $route_instance The current Changelog API instance.
			 */
			do_action( 'divi_squad_rest_changelog_before_file_read', $changelog_file, $this );

			$content = divi_squad()->get_wp_fs()->get_contents( $changelog_file );

			if ( ( '' === $content ) || ( false === $content ) ) {
				/**
				 * Filters the error message when the changelog file is empty.
				 *
				 * @since 3.1.4
				 *
				 * @param string $error_message  The error message.
				 * @param string $changelog_file The path to the changelog file.
				 * @param self   $route_instance The current Changelog API instance.
				 */
				$error_message = apply_filters(
					'divi_squad_rest_changelog_file_empty_message',
					esc_html__( 'Changelog file is empty.', 'squad-modules-for-divi' ),
					$changelog_file,
					$this
				);

				return new WP_Error(
					'rest_file_empty',
					$error_message,
					array( 'status' => 204 )
				);
			}

			/**
			 * Fires after successfully reading the changelog file.
			 *
			 * @since 3.1.4
			 *
			 * @param string $content        The changelog content.
			 * @param string $changelog_file The path to the changelog file.
			 * @param self   $route_instance The current Changelog API instance.
			 */
			do_action( 'divi_squad_rest_changelog_after_file_read', $content, $changelog_file, $this );

			/**
			 * Filters the raw changelog content.
			 *
			 * @since 3.1.4
			 *
			 * @param string $content        The changelog content.
			 * @param string $changelog_file The path to the changelog file.
			 * @param self   $route_instance The current Changelog API instance.
			 */
			return apply_filters( 'divi_squad_rest_raw_changelog_content', $content, $changelog_file, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Error reading changelog file' );

			/**
			 * Fires when an error occurs during changelog file reading.
			 *
			 * @since 3.1.4
			 *
			 * @param Throwable $e              The exception that was thrown.
			 * @param self      $route_instance The current Changelog API instance.
			 */
			do_action( 'divi_squad_rest_changelog_file_error', $e, $this );

			/**
			 * Filters the error response when changelog file reading fails.
			 *
			 * @since 3.1.4
			 *
			 * @param WP_Error  $error          The error object.
			 * @param Throwable $e              The exception that was thrown.
			 * @param self      $route_instance The current Changelog API instance.
			 */
			return apply_filters(
				'divi_squad_rest_changelog_file_error_response',
				new WP_Error(
					'rest_file_error',
					esc_html__( 'An error occurred while reading the changelog file.', 'squad-modules-for-divi' ),
					array( 'status' => 500 )
				),
				$e,
				$this
			);
		}
	}
}
