<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Abstract Route Class for Divi Squad REST API
 *
 * This file contains the abstract Route class which provides a base
 * implementation for all specific Route classes in the Divi Squad
 * plugin's REST API.
 *
 * @since      2.0.0
 * @deprecated 3.3.0
 * @package    DiviSquad
 * @author     The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Base\Factories\RestRoute;

/**
 * Abstract class representing the Route.
 *
 * @since      2.0.0
 * @deprecated 3.3.0
 * @package    DiviSquad
 */
abstract class Route implements RouteInterface {

	/**
	 * API Version
	 *
	 * @var string
	 */
	protected string $version = 'v1';

	/**
	 * The route namespace
	 *
	 * @return string
	 */
	public function get_namespace(): string {
		return sprintf( '%1$s/%2$s', $this->get_name(), $this->get_version() );
	}

	/**
	 * The route name
	 *
	 * @return string
	 */
	public function get_name(): string {
		return divi_squad()->get_name();
	}

	/**
	 * The route name
	 *
	 * @return string
	 */
	public function get_version(): string {
		return $this->version;
	}

	/**
	 * Available routes for current Rest Route
	 *
	 * @return array<string, array<int, array<string, list<$this|string>|string>>>
	 */
	public function get_routes(): array {
		return array();
	}
}
