<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Base Route Class for Divi Squad REST API
 *
 * This file contains the base Route class which provides a foundation
 * for all REST API route handlers in the Divi Squad plugin.
 *
 * @since   3.3.0
 * @author  The WP Squad <support@squadmodules.com>
 * @package DiviSquad
 */

namespace DiviSquad\Rest_API_Routes;

use Throwable;
use WP_Error;
use WP_REST_Response;

/**
 * Abstract class for REST route handlers.
 *
 * @since   3.3.0
 * @package DiviSquad
 */
abstract class Base_Route {

	/**
	 * API Version
	 *
	 * @var string
	 */
	protected string $version = 'v1';

	/**
	 * Get the route namespace.
	 *
	 * @since 3.3.0
	 *
	 * @return string
	 */
	public function get_namespace(): string {
		/**
		 * Filter the route namespace.
		 *
		 * @since 3.3.0
		 *
		 * @param string     $namespace The route namespace.
		 * @param string     $version   The route version.
		 * @param Base_Route $route     The route instance.
		 */
		return apply_filters(
			'divi_squad_rest_namespace',
			sprintf( '%1$s/%2$s', $this->get_name(), $this->get_version() ),
			$this->version,
			$this
		);
	}

	/**
	 * Get the route name.
	 *
	 * @since 3.3.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		/**
		 * Filter the route name.
		 *
		 * @since 3.3.0
		 *
		 * @param string     $name  The route name.
		 * @param Base_Route $route The route instance.
		 */
		return apply_filters(
			'divi_squad_rest_name',
			divi_squad()->get_name(),
			$this
		);
	}

	/**
	 * Get the route version.
	 *
	 * @since 3.3.0
	 *
	 * @return string
	 */
	public function get_version(): string {
		/**
		 * Filter the route version.
		 *
		 * @since 3.3.0
		 *
		 * @param string     $version The route version.
		 * @param Base_Route $route   The route instance.
		 */
		return apply_filters(
			'divi_squad_rest_version',
			$this->version,
			$this
		);
	}

	/**
	 * Get available routes.
	 *
	 * @since 3.3.0
	 *
	 * @return array<string, array<int, array<string, mixed>>> The available routes.
	 */
	abstract public function get_routes(): array;

	/**
	 * Register routes with WordPress.
	 *
	 * @since 3.3.0
	 *
	 * @return void
	 */
	public function register(): void {
		$namespace = $this->get_namespace();
		$routes    = $this->get_routes();

		/**
		 * Action fired before registering routes.
		 *
		 * @since 3.3.0
		 *
		 * @param string     $namespace The route namespace.
		 * @param array      $routes    The routes to register.
		 * @param Base_Route $route     The route instance.
		 */
		do_action( 'divi_squad_before_register_routes', $namespace, $routes, $this );

		foreach ( $routes as $route => $handlers ) {
			$handlers = $this->ensure_permission_callback( $handlers );

			/**
			 * Filter the route handlers before registration.
			 *
			 * @since 3.3.0
			 *
			 * @param array      $handlers  The route handlers.
			 * @param string     $route     The route path.
			 * @param string     $namespace The route namespace.
			 * @param Base_Route $instance  The route instance.
			 */
			$handlers = apply_filters( 'divi_squad_rest_route_handlers', $handlers, $route, $namespace, $this );

			register_rest_route( $namespace, $route, $handlers );
		}

		/**
		 * Action fired after registering routes.
		 *
		 * @since 3.3.0
		 *
		 * @param string     $namespace The route namespace.
		 * @param array      $routes    The registered routes.
		 * @param Base_Route $route     The route instance.
		 */
		do_action( 'divi_squad_after_register_routes', $namespace, $routes, $this );
	}

	/**
	 * Ensure each route has a permission callback.
	 *
	 * @since 3.3.0
	 *
	 * @param array<int, array<string, mixed>> $handlers The route handlers.
	 *
	 * @return array<int, array<string, mixed>> The updated route handlers.
	 */
	protected function ensure_permission_callback( array $handlers ): array {
		foreach ( $handlers as $key => $handler ) {
			if ( is_array( $handler ) && ! isset( $handler['permission_callback'] ) ) {
				$handlers[ $key ]['permission_callback'] = static function () {
					return current_user_can( 'manage_options' );
				};
			}
		}

		return $handlers;
	}

	/**
	 * Check if the current user has admin permissions.
	 *
	 * @since 3.3.0
	 *
	 * @return bool|WP_Error True if the request has admin access, WP_Error object otherwise.
	 */
	public function check_admin_permissions() {
		/**
		 * Filter the admin permission check.
		 *
		 * @since 3.3.0
		 *
		 * @param bool       $has_permission Whether the user has admin permissions.
		 * @param Base_Route $route          The route instance.
		 */
		$has_permission = apply_filters( 'divi_squad_rest_admin_permissions', current_user_can( 'manage_options' ), $this );

		if ( ! $has_permission ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permissions to perform this action.', 'squad-modules-for-divi' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Create a success response.
	 *
	 * @since 3.3.0
	 *
	 * @param mixed  $data    The response data.
	 * @param string $message The success message.
	 * @param int    $status  The HTTP status code.
	 *
	 * @return WP_REST_Response The formatted success response.
	 */
	protected function success_response( $data = null, string $message = '', int $status = 200 ): WP_REST_Response {
		$response = array(
			'code' => 'success',
		);

		if ( '' !== $message ) {
			$response['message'] = $message;
		}

		if ( null !== $data ) {
			$response['data'] = $data;
		}

		/**
		 * Filter the success response.
		 *
		 * @since 3.3.0
		 *
		 * @param array      $response The success response.
		 * @param mixed      $data     The response data.
		 * @param string     $message  The success message.
		 * @param int        $status   The HTTP status code.
		 * @param Base_Route $route    The route instance.
		 */
		$response = apply_filters( 'divi_squad_rest_success_response', $response, $data, $message, $status, $this );

		return new WP_REST_Response( $response, $status );
	}

	/**
	 * Create an error response.
	 *
	 * @since 3.3.0
	 *
	 * @param string               $code    The error code.
	 * @param string               $message The error message.
	 * @param int                  $status  The HTTP status code.
	 * @param array<string, mixed> $data    Additional error data.
	 *
	 * @return WP_Error The formatted error response.
	 */
	protected function error_response( string $code, string $message, int $status = 400, array $data = array() ): WP_Error {
		$error_data = array_merge( array( 'status' => $status ), $data );

		/**
		 * Filter the error response.
		 *
		 * @since 3.3.0
		 *
		 * @param array      $error_data The error data.
		 * @param string     $code       The error code.
		 * @param string     $message    The error message.
		 * @param int        $status     The HTTP status code.
		 * @param Base_Route $route      The route instance.
		 */
		$error_data = apply_filters( 'divi_squad_rest_error_data', $error_data, $code, $message, $status, $this );

		return new WP_Error( $code, $message, $error_data );
	}

	/**
	 * Log an error and return an error response.
	 *
	 * @since 3.3.0
	 *
	 * @param Throwable $exception The exception that occurred.
	 * @param string    $context   The context in which the error occurred.
	 * @param string    $message   The error message to return to the client.
	 * @param int       $status    The HTTP status code.
	 *
	 * @return WP_Error The formatted error response.
	 */
	protected function handle_exception( Throwable $exception, string $context, string $message = '', int $status = 500 ): WP_Error {
		// Log the exception
		divi_squad()->log_error( $exception, $context );

		// Use a generic message if none provided
		if ( '' === $message ) {
			$message = __( 'An unexpected error occurred.', 'squad-modules-for-divi' );
		}

		return $this->error_response( 'server_error', $message, $status );
	}
}
