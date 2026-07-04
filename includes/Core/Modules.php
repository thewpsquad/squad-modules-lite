<?php // phpcs:ignore WordPress.Files.FileName
/**
 * Modules Manager Class
 *
 * Handles registration, management, and loading of all Divi modules
 * for the plugin, supporting both Divi 4 and Divi 5 architectures.
 *
 * @since   3.3.0
 * @author  The WP Squad <support@squadmodules.com>
 * @package DiviSquad\Core
 */

namespace DiviSquad\Core;

use DiviSquad\Base\DiviBuilder\Module;
use DiviSquad\Utils\WP as WPUtil;
use Throwable;
use ET\Builder\Framework\DependencyManagement\DependencyTree;

/**
 * Core Modules Manager
 *
 * @since 3.3.0
 */
class Modules {

	/**
	 * Store all registered modules.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $registered_modules = array();

	/**
	 * Store all active modules.
	 *
	 * @var array<string>
	 */
	private array $active_modules = array();

	/**
	 * Store all inactive modules.
	 *
	 * @var array<string>
	 */
	private array $inactive_modules = array();

	/**
	 * Current builder type (D4 or D5).
	 *
	 * @var string
	 */
	private string $builder_type = 'D4';

	/**
	 * Initialization flag.
	 *
	 * @var bool
	 */
	private bool $initialized = false;

	/**
	 * Memory instance for module state persistence.
	 *
	 * @var Memory
	 */
	private Memory $memory;

	/**
	 * Initialize the modules manager
	 */
	public function __construct() {
		$this->memory = divi_squad()->memory;
	}

	/**
	 * Initialize the module manager
	 */
	public function init(): void {
		if ( $this->initialized ) {
			return;
		}

		// Determine builder type
		$this->detect_builder_type();

		// Load module data
		$this->load_module_data();

		// Register hooks
		$this->register_hooks();

		$this->initialized = true;

		/**
		 * Fires after modules manager is initialized
		 *
		 * @since 3.3.0
		 *
		 * @param self $modules Current modules manager instance
		 */
		do_action( 'divi_squad_modules_init', $this );
	}

	/**
	 * Detect the current Divi Builder type
	 */
	private function detect_builder_type(): void {
		// Detect if we're using Divi 5 by checking for the dependency tree class
		if ( class_exists( DependencyTree::class ) ) {
			$this->builder_type = 'D5';
		} else {
			$this->builder_type = 'D4';
		}

		/**
		 * Filter the detected builder type
		 *
		 * @since 3.3.0
		 *
		 * @param string $builder_type The detected builder type (D4 or D5)
		 */
		$this->builder_type = apply_filters( 'divi_squad_builder_type', $this->builder_type );
	}

	/**
	 * Register WordPress hooks
	 */
	private function register_hooks(): void {
		add_action( 'et_builder_ready', array( $this, 'load_divi4_modules' ), 9 );
		add_action( 'divi_module_library_modules_dependency_tree', array( $this, 'load_divi5_modules' ), 9 );
	}

	/**
	 * Load module data from storage
	 */
	private function load_module_data(): void {
		try {
			// Load the registered modules list
			$this->registered_modules = $this->get_registered_list();

			// Retrieve stored active modules
			$this->active_modules = (array) $this->memory->get( 'active_modules', array() );

			// Retrieve stored inactive modules
			$this->inactive_modules = (array) $this->memory->get( 'inactive_modules', array() );

			// If no active modules stored yet, use defaults
			if ( 0 === count( $this->active_modules ) ) {
				$this->active_modules = array_column( $this->get_default_registries(), 'name' );
			}

			/**
			 * Filter active modules after loading
			 *
			 * @since 3.3.0
			 *
			 * @param array<string> $active_modules List of active module names
			 * @param self         $modules_manager Modules manager instance
			 */
			$this->active_modules = apply_filters( 'divi_squad_active_modules', $this->active_modules, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to load module data' );
		}
	}

	/**
	 * Get list of all available modules
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_registered_list(): array {
		/**
		 * Filter registered modules list
		 *
		 * @since 3.3.0
		 *
		 * @param array<string, array<string, mixed>> $modules         List of module data
		 * @param self                                $modules_manager Modules manager instance
		 */
		return apply_filters( 'divi_squad_registered_modules', array(), $this );
	}

	/**
	 * Get premium modules (locked in free version)
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_premium_modules(): array {
		if ( divi_squad()->is_pro_activated() ) {
			return array();
		}

		/**
		 * Filter premium modules list
		 *
		 * @since 3.3.0
		 *
		 * @param array<string, array<string, mixed>> $modules         List of module data
		 * @param self                                $modules_manager Modules manager instance
		 */
		return apply_filters( 'divi_squad_premium_modules', array(), $this );
	}

	/**
	 * Get default active modules
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_default_registries(): array {
		return $this->filter_modules(
			function ( $module ) {
				return $this->verify_module_type( $module ) && $module['is_default_active'];
			}
		);
	}

	/**
	 * Get all active modules
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_active_registries(): array {
		return $this->filter_modules(
			function ( $module ) {
				return $this->verify_module_type( $module ) && in_array( $module['name'], $this->active_modules, true );
			}
		);
	}

	/**
	 * Get all inactive modules
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_inactive_registries(): array {
		return $this->filter_modules(
			function ( $module ) {
				return $this->verify_module_type( $module ) && ! $module['is_default_active'];
			}
		);
	}

	/**
	 * Filter modules based on callback
	 *
	 * @param callable $callback Function to filter modules
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function filter_modules( callable $callback ): array {
		return array_filter(
			$this->registered_modules,
			static function ( $module ) use ( $callback ) {
				return $callback( $module );
			}
		);
	}

	/**
	 * Load modules for Divi 4 or Divi 5
	 *
	 * @deprecated 3.3.0
	 *
	 * @param string        $path            Modules directory path
	 * @param string|object $dependency_tree DependencyTree instance
	 *
	 * @return void
	 */
	public function load_modules( string $path, $dependency_tree = '' ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		_deprecated_function( __METHOD__, '3.3.0', 'divi_squad()->modules->load_divi4_modules() or divi_squad()->modules->load_divi5_modules()' );

		if ( 'D4' === $this->builder_type ) {
			$this->load_divi4_modules();
		}
	}

	/**
	 * Load modules for Divi 4
	 */
	public function load_divi4_modules(): void {
		try {
			$active_modules = $this->get_active_registries();
			$active_plugins = array_column( WPUtil::get_active_plugins(), 'slug' );

			foreach ( $active_modules as $module ) {
				if ( ! $this->verify_requirements( $module, $active_plugins ) ) {
					continue;
				}

				$this->load_module_classes( $module );
			}

			/**
			 * Fires after Divi 4 modules are loaded
			 *
			 * @since 3.3.0
			 *
			 * @param array<string, array<string, mixed>> $active_modules List of active module data
			 * @param self                                $modules        Modules manager instance
			 */
			do_action( 'divi_squad_divi4_modules_loaded', $active_modules, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to load Divi 4 modules' );
		}
	}

	/**
	 * Load modules for Divi 5
	 *
	 * @param DependencyTree $dependency_tree DependencyTree instance
	 */
	public function load_divi5_modules( DependencyTree $dependency_tree ): void {
		try {
			$active_modules = $this->get_active_registries();
			$active_plugins = array_column( WPUtil::get_active_plugins(), 'slug' );

			foreach ( $active_modules as $module ) {
				if ( ! $this->verify_requirements( $module, $active_plugins ) ) {
					continue;
				}

				$this->load_divi5_module( $module, $dependency_tree );
			}

			/**
			 * Fires after Divi 5 modules are loaded
			 *
			 * @since 3.3.0
			 *
			 * @param array<string, array<string, mixed>> $active_modules  List of active module data
			 * @param DependencyTree                      $dependency_tree DependencyTree instance
			 * @param self                                $modules         Modules manager instance
			 */
			do_action( 'divi_squad_divi5_modules_loaded', $active_modules, $dependency_tree, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to load Divi 5 modules' );
		}
	}

	/**
	 * Load module classes
	 *
	 * @param array<string, mixed> $module Module configuration
	 */
	private function load_module_classes( array $module ): void {
		if ( ! isset( $module['classes']['root_class'] ) ) {
			return;
		}

		// Load main module class
		$main_class = $module['classes']['root_class'];
		if ( class_exists( $main_class ) && is_subclass_of( $main_class, Module::class ) ) {
			$main_module = new $main_class();

			if ( method_exists( $main_module, 'squad_init_custom_hooks' ) ) {
				$main_module->squad_init_custom_hooks();
			}
		}

		// Load child module class if exists
		if ( isset( $module['classes']['child_class'] ) ) {
			$child_class = $module['classes']['child_class'];
			if ( class_exists( $child_class ) && is_subclass_of( $child_class, Module::class ) ) {
				$child_module = new $child_class();

				if ( method_exists( $child_module, 'squad_init_custom_hooks' ) ) {
					$child_module->squad_init_custom_hooks();
				}
			}
		}

		// Load full width module class if exists
		if ( isset( $module['classes']['full_width_class'] ) ) {
			$full_width_class = $module['classes']['full_width_class'];
			if ( class_exists( $full_width_class ) && is_subclass_of( $full_width_class, Module::class ) ) {
				$full_width_module = new $full_width_class();

				if ( method_exists( $full_width_module, 'squad_init_custom_hooks' ) ) {
					$full_width_module->squad_init_custom_hooks();
				}
			}
		}

		// Load child full width module class if exists
		if ( isset( $module['classes']['child_full_width_class'] ) ) {
			$child_full_width_class = $module['classes']['child_full_width_class'];
			if ( class_exists( $child_full_width_class ) && is_subclass_of( $child_full_width_class, Module::class ) ) {
				$full_width_child_module = new $child_full_width_class();

				if ( method_exists( $full_width_child_module, 'squad_init_custom_hooks' ) ) {
					$full_width_child_module->squad_init_custom_hooks();
				}
			}
		}
	}

	/**
	 * Load a Divi 5 module
	 *
	 * @param array<string, mixed> $module          Module configuration
	 * @param DependencyTree       $dependency_tree DependencyTree instance
	 */
	private function load_divi5_module( array $module, DependencyTree $dependency_tree ): void {
		if ( ! isset( $module['classes'] ) ) {
			return;
		}

		// Check for Divi 5 specific class
		$class_key = 'root_block_class';
		if ( isset( $module['classes'][ $class_key ] ) && class_exists( $module['classes'][ $class_key ] ) ) {
			$block_class = $module['classes'][ $class_key ];

			// Verify class implements DependencyInterface
			if ( in_array( '\ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface', class_implements( $block_class ), true ) ) {
				$dependency_tree->add_dependency( new $block_class() ); // @phpstan-ignore-line
			}
		}
	}

	/**
	 * Check if a module is active
	 *
	 * @param string $module_name Module name
	 * @return bool Whether the module is active
	 */
	public function is_module_active( string $module_name ): bool {
		return in_array( $module_name, $this->active_modules, true );
	}

	/**
	 * Check if a module is active by class name
	 *
	 * @param string $class_name Module class name
	 * @return bool Whether the module is active
	 */
	public function is_module_active_by_class( string $class_name ): bool {
		foreach ( $this->registered_modules as $module ) {
			if (
				isset( $module['classes']['root_class'] ) &&
				$module['classes']['root_class'] === $class_name &&
				$this->is_module_active( $module['name'] )
			) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Verify module compatibility with current builder type
	 *
	 * @param array<string, mixed> $module Module configuration
	 * @return bool Whether the module is compatible
	 */
	protected function verify_module_type( array $module ): bool {
		if ( ! isset( $module['type'] ) ) {
			return false;
		}

		if ( is_string( $module['type'] ) ) {
			return $module['type'] === $this->builder_type;
		}

		if ( is_array( $module['type'] ) ) {
			return in_array( $this->builder_type, $module['type'], true );
		}

		return false;
	}

	/**
	 * Verify plugin requirements for a module
	 *
	 * @param array<string, mixed> $module Module configuration
	 * @param array<string> $active_plugins List of active plugin slugs
	 * @return bool Whether requirements are met
	 */
	protected function verify_requirements( array $module, array $active_plugins ): bool {
		// If no requirements, module is valid
		if ( ! isset( $module['required'] ) ) {
			return true;
		}

		// Check plugin requirements
		if ( isset( $module['required']['plugin'] ) ) {
			$required_plugins = $module['required']['plugin'];

			// Single plugin requirement
			if ( is_string( $required_plugins ) ) {
				// Check for multiple options (plugin1|plugin2)
				if ( strpos( $required_plugins, '|' ) !== false ) {
					$plugin_options = explode( '|', $required_plugins );

					// At least one plugin must be active
					foreach ( $plugin_options as $plugin ) {
						if ( in_array( $plugin, $active_plugins, true ) ) {
							return true;
						}
					}

					return false;
				}

				// Single plugin must be active
				return in_array( $required_plugins, $active_plugins, true );
			}

			// Multiple required plugins (all must be active)
			if ( is_array( $required_plugins ) ) {
				foreach ( $required_plugins as $plugin ) {
					if ( ! in_array( $plugin, $active_plugins, true ) ) {
						return false;
					}
				}

				return true;
			}
		}

		return false;
	}

	/**
	 * Get all modules (including premium ones)
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_all_modules(): array {
		return array_merge(
			$this->registered_modules,
			$this->get_premium_modules()
		);
	}

	/**
	 * Enable a module
	 *
	 * @param string $module_name Module name
	 * @return bool Whether the module was enabled
	 */
	public function enable_module( string $module_name ): bool {
		if ( $this->is_module_active( $module_name ) ) {
			return true;
		}

		if ( ! isset( $this->registered_modules[ $module_name ] ) ) {
			return false;
		}

		$this->active_modules[] = $module_name;
		$this->memory->set( 'active_modules', $this->active_modules );

		return true;
	}

	/**
	 * Disable a module
	 *
	 * @param string $module_name Module name
	 * @return bool Whether the module was disabled
	 */
	public function disable_module( string $module_name ): bool {
		if ( ! $this->is_module_active( $module_name ) ) {
			return true;
		}

		$index = array_search( $module_name, $this->active_modules, true );

		if ( false === $index ) {
			return false;
		}

		array_splice( $this->active_modules, (int) $index, 1 );

		if ( ! in_array( $module_name, $this->inactive_modules, true ) ) {
			$this->inactive_modules[] = $module_name;
		}

		$this->memory->set( 'active_modules', $this->active_modules );
		$this->memory->set( 'inactive_modules', $this->inactive_modules );

		return true;
	}

	/**
	 * Get the builder type
	 *
	 * @return string Current builder type (D4 or D5)
	 */
	public function get_builder_type(): string {
		return $this->builder_type;
	}

	/**
	 * Get module info by name
	 *
	 * @param string $module_name Module name
	 * @return array<string, mixed>|null Module data or null if not found
	 */
	public function get_module_info( string $module_name ): ?array {
		return $this->registered_modules[ $module_name ] ?? null;
	}

	/**
	 * Get module categories
	 *
	 * @return array<string, string> Categories with their titles
	 */
	public function get_module_categories(): array {
		$categories = array();

		foreach ( $this->registered_modules as $module ) {
			if ( isset( $module['category'], $module['category_title'] ) ) {
				$categories[ $module['category'] ] = $module['category_title'];
			}
		}

		// Add premium category if needed
		if ( ! divi_squad()->is_pro_activated() ) {
			$categories['premium-modules'] = esc_html__( 'Premium Modules', 'squad-modules-for-divi' );
		}

		return $categories;
	}

	/**
	 * Reset modules to default state
	 *
	 * @return bool Success status
	 */
	public function reset_to_default(): bool {
		try {
			$default_modules      = $this->get_default_registries();
			$this->active_modules = array_column( $default_modules, 'name' );

			$all_module_names       = array_column( $this->registered_modules, 'name' );
			$this->inactive_modules = array_values( array_diff( $all_module_names, $this->active_modules ) );

			$this->memory->set( 'active_modules', $this->active_modules );
			$this->memory->set( 'inactive_modules', $this->inactive_modules );
			$this->memory->set( 'active_module_version', divi_squad()->get_version() );

			return true;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to reset modules to default' );
			return false;
		}
	}
}
