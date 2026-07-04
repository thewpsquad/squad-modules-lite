<?php // phpcs:ignore WordPress.Files.FileName

/**
 * REST Routes Manager
 *
 * This file contains the RestRoutes class which handles the registration and
 * initialization of all REST API routes for the Divi Squad plugin.
 *
 * @since   3.3.0
 * @author  The WP Squad <support@squadmodules.com>
 * @package DiviSquad
 */

namespace DiviSquad\Core;

use DiviSquad\Rest_API_Routes\Base_Route;
use DiviSquad\Rest_API_Routes\Version1\Extensions as Extensions_Version1;
use DiviSquad\Rest_API_Routes\Version1\Extensions\Copy;
use DiviSquad\Rest_API_Routes\Version1\Modules as Modules_Version1;
use DiviSquad\Rest_API_Routes\Version1\Modules\PostGrid;
use DiviSquad\Rest_API_Routes\Version1\Notices\Discount;
use DiviSquad\Rest_API_Routes\Version1\Notices\ProActivation;
use DiviSquad\Rest_API_Routes\Version1\Notices\Review;
use DiviSquad\Rest_API_Routes\Version1\Whats_New\Changelog;
use DiviSquad\Rest_API_Routes\Version2\Extensions as Extensions_Version2;
use DiviSquad\Rest_API_Routes\Version2\Modules as Modules_Version2;
use Throwable;

/**
 * REST Routes Manager class.
 *
 * @since   3.3.0
 * @package DiviSquad
 */
class RestRoutes {

	/**
	 * Registered route handlers.
	 *
	 * @var array<Base_Route>
	 */
	protected array $routes = array();

	/**
	 * Initialize the REST Routes Manager.
	 *
	 * @since 3.3.0
	 *
	 * @return void
	 */
	public function init(): void {
		try {
			// Register action to initialize routes.
			add_action( 'rest_api_init', array( $this, 'register_routes' ) );

			// Register default route handlers.
			$this->register_default_routes();

			/**
			 * Action to register additional route handlers.
			 *
			 * @since 3.3.0
			 *
			 * @param RestRoutes $manager The REST Routes Manager instance.
			 */
			do_action( 'divi_squad_register_rest_routes', $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'REST Routes Manager initialization' );
		}
	}

	/**
	 * Register default route handlers.
	 *
	 * @since 3.3.0
	 *
	 * @return void
	 */
	protected function register_default_routes(): void {
		// Core functionality routes.
		$this->register_route_class( Modules_Version1::class );
		$this->register_route_class( Extensions_Version1::class );
		$this->register_route_class( Modules_Version2::class );
		$this->register_route_class( Extensions_Version2::class );

		// What's new routes.
		$this->register_route_class( Changelog::class );

		// Notice routes.
		$this->register_route_class( Discount::class );
		$this->register_route_class( Review::class );
		$this->register_route_class( ProActivation::class );

		// Module-specific routes.
		$this->register_route_class( PostGrid::class );

		// Extension-specific routes.
		$this->register_route_class( Copy::class );
	}

	/**
	 * Register a route handler class.
	 *
	 * @since 3.3.0
	 *
	 * @param string $class_name The fully qualified class name of the route handler.
	 *
	 * @return bool Whether the route handler was registered successfully.
	 */
	public function register_route_class( string $class_name ): bool {
		try {
			// Verify that the class exists.
			if ( ! class_exists( $class_name ) ) {
				divi_squad()->log_debug( sprintf( 'Route class %s does not exist.', $class_name ) );

				return false;
			}

			// Instantiate the class.
			$route = new $class_name();

			// Verify that the class extends Base_Route.
			if ( ! $route instanceof Base_Route ) {
				divi_squad()->log_debug( sprintf( 'Route class %s must extend Base_Route.', $class_name ) );

				return false;
			}

			// Add the route to the registered routes.
			$this->routes[] = $route;

			/**
			 * Action fired after registering a route handler class.
			 *
			 * @since 3.3.0
			 *
			 * @param string     $class_name The class name of the registered route.
			 * @param Base_Route $route      The route instance.
			 * @param RestRoutes $manager    The REST Routes Manager instance.
			 */
			do_action( 'divi_squad_route_registered', $class_name, $route, $this );

			return true;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, sprintf( 'Failed to register route class %s', $class_name ) );

			return false;
		}
	}

	/**
	 * Register a route handler instance.
	 *
	 * @since 3.3.0
	 *
	 * @param Base_Route $route The route handler instance.
	 *
	 * @return bool Whether the route handler was registered successfully.
	 */
	public function register_route( Base_Route $route ): bool {
		try {
			// Add the route to the registered routes.
			$this->routes[] = $route;

			/**
			 * Action fired after registering a route handler instance.
			 *
			 * @since 3.3.0
			 *
			 * @param Base_Route $route   The route instance.
			 * @param RestRoutes $manager The REST Routes Manager instance.
			 */
			do_action( 'divi_squad_route_instance_registered', $route, $this );

			return true;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, sprintf( 'Failed to register route instance of class %s', get_class( $route ) ) );

			return false;
		}
	}

	/**
	 * Register all routes with WordPress.
	 *
	 * @since 3.3.0
	 *
	 * @return void
	 */
	public function register_routes(): void {
		try {
			/**
			 * Filter the registered routes before registering them with WordPress.
			 *
			 * @since 3.3.0
			 *
			 * @param array<Base_Route> $routes  The registered routes.
			 * @param RestRoutes        $manager The REST Routes Manager instance.
			 */
			$this->routes = apply_filters( 'divi_squad_rest_routes', $this->routes, $this );

			/**
			 * Action fired before registering routes with WordPress.
			 *
			 * @since 3.3.0
			 *
			 * @param array<Base_Route> $routes  The registered routes.
			 * @param RestRoutes        $manager The REST Routes Manager instance.
			 */
			do_action( 'divi_squad_before_register_rest_routes', $this->routes, $this );

			// Register each route with WordPress.
			foreach ( $this->routes as $route ) {
				$route->register();
			}

			/**
			 * Action fired after registering routes with WordPress.
			 *
			 * @since 3.3.0
			 *
			 * @param array<Base_Route> $routes  The registered routes.
			 * @param RestRoutes        $manager The REST Routes Manager instance.
			 */
			do_action( 'divi_squad_after_register_rest_routes', $this->routes, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to register REST routes' );
		}
	}

	/**
	 * Get all registered routes.
	 *
	 * @since 3.3.0
	 *
	 * @return array<Base_Route> The registered routes.
	 */
	public function get_routes(): array {
		return $this->routes;
	}

	/**
	 * Get registered routes by version.
	 *
	 * @since 3.3.0
	 *
	 * @param string $version The version to filter by.
	 *
	 * @return array<Base_Route> The registered routes for the specified version.
	 */
	public function get_routes_by_version( string $version ): array {
		return array_filter(
			$this->routes,
			static function ( $route ) use ( $version ) {
				return $version === $route->get_version();
			}
		);
	}

	/**
	 * Get registered routes by namespace.
	 *
	 * @since 3.3.0
	 *
	 * @param string $route_namespace The namespace to filter by.
	 *
	 * @return array<Base_Route> The registered routes for the specified namespace.
	 */
	public function get_routes_by_namespace( string $route_namespace ): array {
		return array_filter(
			$this->routes,
			static function ( $route ) use ( $route_namespace ) {
				return $route_namespace === $route->get_namespace();
			}
		);
	}

	/**
	 * Format the route name for readability.
	 *
	 * @param string $route Original route string.
	 *
	 * @return string
	 */
	public function format_route_name( string $route ): string {
		$route_parts = explode( '/', str_replace( array( '_', '-' ), '/', $route ) );
		$route_parts = array_filter( $route_parts, static fn( $part ): bool => ! empty( $part ) );
		$route_parts = array_map( 'ucfirst', $route_parts );

		return implode( '', $route_parts );
	}
}
