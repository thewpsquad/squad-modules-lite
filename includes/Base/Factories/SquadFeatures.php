<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Feature Management class
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   2.0.0
 */

namespace DiviSquad\Base\Factories;

use DiviSquad\Core\Supports\Polyfills\Str;
use function sort;

/**
 * Feature Management class
 *
 * @package DiviSquad
 * @since   2.0.0
 */
abstract class SquadFeatures {

	/**
	 * Get the type of Divi Builder, default is: D4. Available opinions are: D4, D5.
	 *
	 * @var string
	 */
	protected string $builder_type = 'D4';

	/**
	 * Retrieve the list of registered.
	 *
	 * @return array[]
	 */
	abstract public function get_registered_list();

	/**
	 * Retrieve the list of inactive registered.
	 *
	 * @return array
	 */
	abstract public function get_inactive_registries(): array;

	/**
	 * Retrieve the list of default active registered.
	 *
	 * @return array
	 */
	abstract public function get_default_registries(): array;

	/**
	 * Retrieve details by the registered name.
	 *
	 * @param array  $registries The array list of available registries.
	 * @param string $name       The name of the current registry.
	 *
	 * @return array
	 */
	protected function get_details_by_name( array $registries, string $name ): array {
		$details = array();
		foreach ( $registries as $registry ) {
			if ( isset( $registry['name'] ) && $name === $registry['name'] ) {
				$details[] = $registry;
				break;
			}
		}

		return $details;
	}

	/**
	 * Retrieve the filtered list of registered.
	 *
	 * @param array         $registered The list of registered.
	 * @param callable|null $callback   The callback function to filter the current registriy.
	 *
	 * @return array
	 */
	protected function get_filtered_registries( array $registered, callable $callback ): array {
		$filtered_registries = array();

		foreach ( $registered as $registry ) {
			$filtered = $callback( $registry );
			if ( is_bool( $filtered ) && $filtered ) {
				$filtered_registries[] = $registry;
			}
		}

		return $filtered_registries;
	}

	/**
	 * Verify third party plugin requirements for current registry.
	 *
	 * @param array $registry_info  Current registry information.
	 * @param array $active_plugins Active plugin lists from current installation.
	 *
	 * @return bool
	 */
	protected function verify_requirements( array $registry_info, array $active_plugins ): bool {
		// Verify if the registry has no requirements.
		if ( empty( $registry_info['required'] ) ) {
			return true;
		}

		// Verify if the registry has required plugins.
		if ( ! empty( $registry_info['required']['plugin'] ) ) {
			$required_plugins = $registry_info['required']['plugin'];

			// Verify for the single requirements.
			if ( is_string( $required_plugins ) ) {
				$verified_plugins = array();

				// Verify optional plugins when available.
				if ( Str::contains( $required_plugins, '|' ) ) {
					$verified_plugins = explode( '|', $required_plugins );
				} else {
					$verified_plugins[] = $required_plugins;
				}

				// Collect all active plugins that are required by current plugin.
				$activated_plugins = array();
				foreach ( $verified_plugins as $plugin ) {
					if ( in_array( $plugin, $active_plugins, true ) ) {
						$activated_plugins[] = $plugin;
					}
				}

				// Verify that any requireds plugin is/are available.
				return ! empty( $activated_plugins );
			}

			// Verify for the multiple requirements.
			if ( is_array( $required_plugins ) ) {
				$dependencies_plugins = array();
				foreach ( $required_plugins as $plugin ) {
					if ( in_array( $plugin, $active_plugins, true ) ) {
						$dependencies_plugins[] = $plugin;
					}
				}

				// Short all plugin lists.
				sort( $required_plugins );
				sort( $dependencies_plugins );

				// Verify that all required are available.
				return $required_plugins === $dependencies_plugins;

			}
		}

		return false;
	}

	/**
	 * Load the module class.
	 *
	 * @param array       $registered The available modules list.
	 * @param array       $defaults   The default activated registries list.
	 * @param mixed       $activate   The user-defined activated registries list.
	 * @param array       $inactivate The user-defined inactivated registries list.
	 * @param string|null $version    Current version of the plugin.
	 *
	 * @return array
	 */
	protected function get_verified_registries( array $registered, array $defaults, $activate, array $inactivate, ?string $version ): array {
		$verified = array();
		$names    = array();

		// If activate is null, set it to defaults.
		if ( is_null( $activate ) ) {
			$activate = $defaults;
		}

		// Extract names from the activate list.
		foreach ( $activate as $active ) {
			if ( is_string( $active ) ) {
				$names[] = $active;
			} elseif ( is_array( $active ) && ! empty( $active['name'] ) ) {
				$names[] = $active['name'];
			}
		}

		// Verify default active registries.
		foreach ( $defaults as $default ) {
			if ( $default['release_version'] === $version &&
				! in_array( $default['name'], $names, true ) &&
				! in_array( $default['name'], $inactivate, true ) ) {
				$verified[] = $default;
			}
		}

		// Get registry details that user activates.
		foreach ( $registered as $module ) {
			if ( in_array( $module['name'], $names, true ) ) {
				$verified[] = $module;
			}
		}

		// Collect all activated registries and ensure uniqueness.
		return array_unique( $verified, SORT_REGULAR );
	}
}
