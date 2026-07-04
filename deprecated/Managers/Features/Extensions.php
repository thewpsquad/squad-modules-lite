<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Extension Manager
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   1.0.0
 * @deprecated 3.3.0
 */

namespace DiviSquad\Managers\Features;

use DiviSquad\Base\Factories\SquadFeatures as ManagerBase;
use DiviSquad\Core\Memory;
use Throwable;

/**
 * Extension Manager class
 *
 * @package DiviSquad
 * @since   1.0.0
 * @deprecated 3.3.0
 */
class Extensions extends ManagerBase {

	/**
	 * Get available extensions.
	 *
	 * @return array[]
	 */
	public function get_registered_list() {
		return array();
	}

	/**
	 * Get default active extensions.
	 *
	 * @return array
	 */
	public function get_default_registries(): array {
		return $this->get_filtered_registries(
			$this->get_registered_list(),
			function ( $extension ) {
				return $extension['is_default_active'];
			}
		);
	}

	/**
	 * Get inactive extensions.
	 *
	 * @return array
	 */
	public function get_inactive_registries(): array {
		return $this->get_filtered_registries(
			$this->get_registered_list(),
			function ( $extension ) {
				return ! $extension['is_default_active'];
			}
		);
	}

	/**
	 * Load enabled extensions
	 *
	 * @param string $path The defined directory.
	 *
	 * @return void
	 */
	public function load_extensions( string $path ) {}

	/**
	 * Load enabled extensions
	 *
	 * @param string $path   The defined directory.
	 * @param Memory $memory The instance of Memory class.
	 *
	 * @return void
	 */
	protected function load_extensions_files( string $path, Memory $memory ) {
		try {
			// Retrieve total active extensions and current version from the memory.
			$current_version     = $memory->get( 'version' );
			$active_extensions   = $memory->get( 'active_extensions' );
			$inactive_extensions = $memory->get( 'inactive_extensions', array() );

			// Get all registered and default extensions.
			$features = $this->get_registered_list();
			$defaults = $this->get_default_registries();

			// Get verified active modules.
			$activated = $this->get_verified_registries( $features, $defaults, $active_extensions, $inactive_extensions, $current_version );

			foreach ( $activated as $extension ) {
				if ( isset( $extension['classes']['root_class'] ) ) {
					new $extension['classes']['root_class']();
				}
			}
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Extension loader' );
		}
	}
}
