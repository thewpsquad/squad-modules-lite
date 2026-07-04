<?php // phpcs:ignore WordPress.Files.FileName

/**
 * RestRoute Factory Class
 *
 * This class manages the registration and handling of REST API routes
 * for the Divi Squad plugin.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   2.0.0
 */

namespace DiviSquad\Base\Factories;

use DiviSquad\Base\Factories\FactoryBase\Factory;
use DiviSquad\Base\Factories\RestRoute\RouteInterface;
use DiviSquad\Core\Traits\Singleton;

/**
 * Class RestRoute
 *
 * @package DiviSquad
 * @since   2.0.0
 */
final class RestRoute extends Factory {

	use Singleton;

	/**
	 * Store all registered routes
	 *
	 * @var array
	 */
	private static array $registries = array();

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	protected function init_hooks() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ), 0 );
	}

	/**
	 * Add a new route to the list of routes.
	 *
	 * @param string $class_name The class name of the route to add. Must implement RouteInterface.
	 * @return void
	 */
	public function add( $class_name ) {
		if ( ! class_exists( $class_name ) ) {
			return;
		}

		$route = new $class_name();
		if ( ! $route instanceof RouteInterface ) {
			return;
		}

		$product_name   = $route->get_name();
		$rest_namespace = $route->get_namespace();
		$rest_routes    = $route->get_routes();

		if ( ! isset( self::$registries[ $product_name ] ) ) {
			self::$registries[ $product_name ] = array(
				'namespace' => $rest_namespace,
				'routes'    => array(),
			);
		}

		foreach ( $rest_routes as $route => $args ) {
			self::$registries[ $product_name ]['routes'][ $route ] = $args;
		}
	}

	/**
	 * Get the namespace for a given product name.
	 *
	 * @param string $name Current product name.
	 *
	 * @return string
	 */
	public function get_namespace( string $name ): string {
		return self::$registries[ $name ]['namespace'] ?? '';
	}

	/**
	 * Register all routes for our endpoints.
	 *
	 * @return void
	 */
	public function register_routes() {
		foreach ( self::$registries as $router ) {
			$namespace = $router['namespace'];
			$routes    = $router['routes'];

			foreach ( $routes as $route => $args ) {
				$args = $this->ensure_permission_callback( $args );
				register_rest_route( $namespace, $route, $args );
			}
		}
	}

	/**
	 * Ensure each route has a permission callback.
	 *
	 * @param array $args Route arguments.
	 *
	 * @return array
	 */
	private function ensure_permission_callback( array $args ): array {
		foreach ( $args as $key => $single_route ) {
			if ( is_array( $single_route ) && ! isset( $single_route['permission_callback'] ) ) {
				$args[ $key ]['permission_callback'] = 'is_user_logged_in';
			}
		}
		return $args;
	}

	/**
	 * Get all registered routes for a given product name.
	 *
	 * @param string $name Current product name.
	 *
	 * @return array
	 */
	public function get_registered_routes( string $name ): array {
		$results = array();
		$routes  = self::$registries[ $name ]['routes'] ?? array();

		foreach ( $routes as $route => $args ) {
			$route_name             = $this->format_route_name( $route );
			$results[ $route_name ] = array(
				'root'    => $route,
				'methods' => array_column( $args, 'methods' ),
			);
		}

		return $results;
	}

	/**
	 * Format the route name for readability.
	 *
	 * @param string $route Original route string.
	 *
	 * @return string
	 */
	private function format_route_name( string $route ): string {
		$route_parts = explode( '/', str_replace( array( '_', '-' ), '/', $route ) );
		$route_parts = array_map( 'ucfirst', $route_parts );
		return implode( '', $route_parts );
	}
}
