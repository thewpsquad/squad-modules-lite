<?php // phpcs:ignore WordPress.Files.FileName

/**
 * RouteInterface for Divi Squad REST API
 *
 * This file contains the RouteInterface which defines the contract
 * for all Route classes in the Divi Squad plugin's REST API.
 *
 * @since      2.0.0
 * @deprecated 3.3.0
 * @package    DiviSquad
 * @author     The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Base\Factories\RestRoute;

/**
 * Interface for the Route class.
 *
 * @since      2.0.0
 * @deprecated 3.3.0
 * @package    DiviSquad
 */
interface RouteInterface {

	/**
	 * The route namespace
	 *
	 * @return string
	 */
	public function get_namespace();

	/**
	 * The route name
	 *
	 * @return string
	 */
	public function get_name();

	/**
	 * Available routes for current Rest Route
	 *
	 * @return array
	 */
	public function get_routes();
}
