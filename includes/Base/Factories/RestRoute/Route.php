<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Abstract Route Class for Divi Squad REST API
 *
 * This file contains the abstract Route class which provides a base
 * implementation for all specific Route classes in the Divi Squad
 * plugin's REST API.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   2.0.0
 */

namespace DiviSquad\Base\Factories\RestRoute;

use function divi_squad;

/**
 * Abstract class representing the Route.
 *
 * @package DiviSquad
 * @since   2.0.0
 */
abstract class Route implements RouteInterface {

	/**
	 * API Version
	 *
	 * @var string
	 */
	protected $version = 'v1';

	/**
	 * The route namespace
	 *
	 * @return string
	 */
	public function get_namespace() {
		return sprintf( '%1$s/%2$s', $this->get_name(), $this->get_version() );
	}

	/**
	 * The route name
	 *
	 * @return string
	 */
	public function get_name() {
		return divi_squad()->get_name();
	}

	/**
	 * The route name
	 *
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Available routes for current Rest Route
	 *
	 * @return array
	 */
	public function get_routes() {
		return array();
	}
}
