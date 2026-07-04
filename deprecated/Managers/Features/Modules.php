<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Module Manager
 *
 * @since      1.0.0
 * @deprecated 3.3.0
 * @package    DiviSquad
 * @author     The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Managers\Features;

use DiviSquad\Base\Factories\SquadFeatures as ManagerBase;
use DiviSquad\Core\Memory;
use DiviSquad\Utils\WP;
use Throwable;
use function apply_filters;
use function wp_array_slice_assoc;

/**
 * Module Manager class
 *
 * @since      1.0.0
 * @deprecated 3.3.0
 * @package    DiviSquad
 */
class Modules extends ManagerBase {

	/**
	 * Get all modules including extra modules.
	 *
	 * @return array[]
	 */
	public function get_all_modules_with_locked(): array {
		return $this->get_registered_list();
	}

	/**
	 *  Get available modules.
	 *
	 * @return array[]
	 */
	public function get_registered_list() {
		return array();
	}

	/**
	 *  Get inactive modules.
	 *
	 * @return array<string, mixed>
	 */
	public function get_inactive_registries(): array {
		return $this->get_filtered_registries(
			$this->get_registered_list(),
			function ( $module ) {
				return $this->verify_module_type( $module ) && ! $module['is_default_active'];
			}
		);
	}

	/**
	 * Get active modules.
	 *
	 * @return array<string, mixed>
	 */
	public function get_active_registries(): array {
		$active_modules = $this->get_active_modules();

		return $this->get_filtered_registries(
			$this->get_registered_list(),
			function ( $module ) use ( $active_modules ) {
				return $this->verify_module_type( $module ) && in_array( $module['name'], $active_modules, true );
			}
		);
	}

	/**
	 * Get active modules.
	 *
	 * @return array<string>
	 */
	public function get_active_modules(): array {
		return (array) divi_squad()->memory->get( 'active_modules' );
	}

	/**
	 * Get default modules.
	 *
	 * @param string $module_name The module name.
	 *
	 * @return bool
	 */
	public function is_module_active( string $module_name ): bool {
		$active_modules = array_column( $this->get_active_registries(), 'name' );

		return in_array( $module_name, $active_modules, true );
	}

	/**
	 * Check if the module is active by class name.
	 *
	 * @param string $module_classname The module class name.
	 *
	 * @return bool
	 */
	public function is_module_active_by_classname( string $module_classname ): bool {
		$active_module_classes = array_map(
			function ( $module ) {
				return $module['classes']['root_class'];
			},
			$this->get_active_registries()
		);

		return in_array( $module_classname, $active_module_classes, true );
	}

	/**
	 * Load enabled modules for Divi Builder from defined directory.
	 *
	 * @param string                                                    $path            The defined directory.
	 * @param \ET\Builder\Framework\DependencyManagement\DependencyTree $dependency_tree `DependencyTree` class is used as a utility to manage loading classes in a meaningful manner.
	 *
	 * @return void
	 */
	public function load_modules( string $path, ?object $dependency_tree = null ) {}

	/**
	 * Load the module class.
	 *
	 * @param string                                                    $path            The module class path.
	 * @param Memory                                                    $memory          The instance of Memory class.
	 * @param \ET\Builder\Framework\DependencyManagement\DependencyTree $dependency_tree `DependencyTree` class is used as a utility to manage loading classes in a meaningful manner.
	 *
	 * @return void
	 */
	protected function load_module_files( string $path, Memory $memory, $dependency_tree = '' ): void {
		try {
			// Retrieve total active modules and current version from the memory.
			$current_version  = $memory->get( 'version' );
			$active_modules   = $memory->get( 'active_modules' );
			$inactive_modules = $memory->get( 'inactive_modules', array() );

			// Get all registered and default modules.
			$features = array_map( array( $this, 'custom_array_slice' ), $this->get_registered_list() );
			$defaults = array_map( array( $this, 'custom_array_slice' ), $this->get_default_registries() );

			// Filter and verify all active modules.
			$available = $this->get_filtered_registries( $features, array( $this, 'verify_module_type' ) );
			$activated = $this->get_verified_registries( $available, $defaults, $active_modules, $inactive_modules, $current_version );

			// Collect all active plugins from the current installation.
			$active_plugins = array_column( WP::get_active_plugins(), 'slug' );

			foreach ( $activated as $activated_module ) {
				/**
				 * Load modules from the class path.
				 *
				 * @since 2.1.2
				 */
				if ( ! empty( $activated_module['classes']['root_class'] ) && class_exists( $activated_module['classes']['root_class'] ) ) {
					if ( $this->verify_requirements( $activated_module, $active_plugins ) ) {
						$this->load_module_if_exists( $activated_module, 'name', $dependency_tree );
						$this->load_module_if_exists( $activated_module, 'child_name', $dependency_tree );
						$this->load_module_if_exists( $activated_module, 'full_width_name', $dependency_tree );
						$this->load_module_if_exists( $activated_module, 'full_width_child_name', $dependency_tree );
					}
				} else {
					$module_path_root = 'D5' === $this->builder_type ? 'Block' : '';
					$module_path_full = sprintf( '%1$s/%2$sModules/%3$s/%3$s.php', $path, $module_path_root, $activated_module['name'] );

					if ( $this->verify_requirements( $activated_module, $active_plugins ) && file_exists( $module_path_full ) ) {
						$module_names = array_filter(
							array(
								! empty( $activated_module['name'] ) ? $activated_module['name'] : null,
								! empty( $activated_module['child_name'] ) ? $activated_module['child_name'] : null,
								! empty( $activated_module['full_width_name'] ) ? $activated_module['full_width_name'] : null,
								! empty( $activated_module['full_width_child_name'] ) ? $activated_module['full_width_child_name'] : null,
							)
						);

						foreach ( $module_names as $module_name ) {
							$this->require_module_path( $path, $module_name, $dependency_tree );
						}
					}
				}
			}
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Module loader' );
		}
	}

	/**
	 * Filter list of modules with specific keys.
	 *
	 * @param array $input_array Running module configuration.
	 *
	 * @return array
	 */
	public function custom_array_slice( array $input_array ): array {
		// Filtered module columns.
		$defaults = array( 'classes', 'name', 'child_name', 'full_width_name', 'full_width_child_name', 'type', 'is_default_active', 'release_version' );

		/**
		 * Filter the module configuration array slice.
		 *
		 * @since 2.1.2
		 *
		 * @param array $module_columns The module columns.
		 */
		$module_columns = apply_filters( 'divi_squad_features_module_configuration_array_slice', $defaults );

		return wp_array_slice_assoc( $input_array, $module_columns );
	}

	/**
	 *  Get default active modules.
	 *
	 * @return array
	 */
	public function get_default_registries(): array {
		return $this->get_filtered_registries(
			$this->get_registered_list(),
			function ( $module ) {
				return $this->verify_module_type( $module ) && $module['is_default_active'];
			}
		);
	}

	/**
	 * Check the current module type.
	 *
	 * @param array $module The array of current module.
	 *
	 * @return bool
	 */
	protected function verify_module_type( array $module ): bool {
		$single        = isset( $module['type'] ) && is_string( $module['type'] ) && $this->builder_type === $module['type'];
		$compatibility = isset( $module['type'] ) && is_array( $module['type'] ) && in_array( $this->builder_type, $module['type'], true );

		return ( $single || $compatibility );
	}

	/**
	 * Verify the requirements of the module.
	 *
	 * @param array                                                     $activated_module The module.
	 * @param string                                                    $module_key       The module name key.
	 * @param \ET\Builder\Framework\DependencyManagement\DependencyTree $dependency_tree  `DependencyTree` class is used as a utility to manage loading classes in a meaningful manner.
	 *
	 * @return void
	 */
	private function load_module_if_exists( array $activated_module, string $module_key, $dependency_tree = '' ): void {
		if ( ! isset( $activated_module[ $module_key ] ) ) {
			return;
		}

		$this->require_module_class( $module_key, $activated_module, $dependency_tree );
	}

	/**
	 * Load the module class.
	 *
	 * @since 2.1.2
	 *
	 * @param array                                                     $module          The module.
	 * @param \ET\Builder\Framework\DependencyManagement\DependencyTree $dependency_tree `DependencyTree` class is used as a utility to manage loading classes in a meaningful manner.
	 *
	 * @param string                                                    $module_key      The module specification key.
	 *
	 * @return void
	 */
	protected function require_module_class( string $module_key = 'name', array $module = array(), $dependency_tree = '' ) {
		// Replace `name` from the module key string if include underscore or not.
		$module_key   = str_replace( array( '_', 'name' ), '', $module_key );
		$module_class = empty( $module_key ) ? 'root' : $module_key;

		/**
		 * Load the module class for divi builder 5.
		 * Ref: https://stackoverflow.com/a/20169918
		 */
		if ( isset( $module['classes']["{$module_class}_block_class"] ) && class_exists( $module['classes']["{$module_class}_block_class"] ) ) {
			// Verify the block module class.
			if ( ! class_exists( $module['classes']["{$module_class}_block_class"] ) ) {
				divi_squad()->log_debug( "Block module class does not exist for {$module_class}." );

				return;
			}

			// Verify the dependency tree class.
			if ( ! class_exists( '\ET\Builder\Framework\DependencyManagement\DependencyTree' ) ) {
				divi_squad()->log_debug( 'DependencyTree class does not exist.' );

				return;
			}

			// Verify the dependency interface class.
			if ( ! class_exists( '\ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface' ) ) {
				divi_squad()->log_debug( 'DependencyInterface class does not exist.' );

				return;
			}

			if ( $dependency_tree instanceof \ET\Builder\Framework\DependencyManagement\DependencyTree && method_exists( $dependency_tree, 'add_dependency' ) ) {
				$block_module_class = $module['classes']["{$module_class}_block_class"];
				$class_interfaces   = class_implements( $block_module_class );
				$core_interface     = \ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface::class;
				if ( $class_interfaces && in_array( $core_interface, $class_interfaces, true ) ) {
					$dependency_tree->add_dependency( new $block_module_class() );
				}
			}
		}

		/**
		 * Load the module class for divi builder 4.
		 * Ref: https://stackoverflow.com/a/20169918
		 */
		if ( isset( $module['classes']["{$module_class}_class"] ) && class_exists( $module['classes']["{$module_class}_class"] ) ) {
			// Verify the module class.
			if ( ! class_exists( $module['classes']["{$module_class}_class"] ) ) {
				divi_squad()->log_debug( "Module class does not exist for {$module_class}." );

				return;
			}

			// Create an instance of the module class.
			$squad_module = new $module['classes']["{$module_class}_class"]();

			// Initialize custom hooks.
			if ( method_exists( $squad_module, 'squad_init_custom_hooks' ) ) {
				$squad_module->squad_init_custom_hooks();
			}
		}
	}

	/**
	 * Load the module class from path.
	 *
	 * @param string                                                    $path            The module class path.
	 * @param string                                                    $module          The module name.
	 * @param \ET\Builder\Framework\DependencyManagement\DependencyTree $dependency_tree `DependencyTree` class is used as a utility to manage loading classes in a meaningful manner.
	 *
	 * @return void
	 */
	protected function require_module_path( string $path, string $module, $dependency_tree = '' ): void {
		if ( 'D5' === $this->builder_type ) {
			$module_path = sprintf( '%1$s/%2$s/%2$s.php', $path, $module );
			if ( file_exists( $module_path ) ) {
				divi_squad()->log_debug( "Module path does not exist for {$module}." );

				return;
			}

			// Verify the dependency tree class.
			if ( ! class_exists( '\ET\Builder\Framework\DependencyManagement\DependencyTree' ) ) {
				divi_squad()->log_debug( 'DependencyTree class does not exist.' );

				return;
			}

			// Verify the dependency tree class.
			if ( $dependency_tree instanceof \ET\Builder\Framework\DependencyManagement\DependencyTree ) {
				$module_instance = require $module_path;
				$dependency_tree->add_dependency( $module_instance );
			}
		} else {
			$module_path = sprintf( '%1$s/Modules/%2$s/%2$s.php', $path, $module );
			if ( file_exists( $module_path ) ) {
				divi_squad()->log_debug( "Module path does not exist for {$module}." );

				return;
			}

			require_once $module_path;
		}
	}
}
