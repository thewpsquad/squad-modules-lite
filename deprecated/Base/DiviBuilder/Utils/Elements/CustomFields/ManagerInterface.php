<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Manager Interface
 *
 * This file contains the ManagerInterface which defines the contract
 * for all manager classes in the DiviSquad plugin.
 *
 * @since      3.1.1
 * @deprecated 3.3.0
 * @package    DiviSquad
 * @author     The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Base\DiviBuilder\Utils\Elements\CustomFields;

/**
 * Interface ManagerInterface
 *
 * Defines the contract for manager classes in the DiviSquad plugin.
 *
 * @since      3.1.1
 * @deprecated 3.3.0
 * @package    DiviSquad
 */
interface ManagerInterface {

	/**
	 * Initialize the manager.
	 *
	 * This method should set up any necessary hooks or initial configurations.
	 *
	 * @since 3.1.1
	 *
	 * @return void
	 */
	public function init();

	/**
	 * Get data from the manager.
	 *
	 * This method should retrieve the main data that the manager is responsible for.
	 *
	 * @since 3.1.1
	 *
	 * @param array $args Optional. Arguments to modify the query.
	 *
	 * @return array The retrieved data.
	 */
	public function get_data( $args = array() );

	/**
	 * Clear the cache for this manager.
	 *
	 * This method should clear any cached data that the manager maintains.
	 *
	 * @since 3.1.1
	 *
	 * @return void
	 */
	public function clear_cache();
}
