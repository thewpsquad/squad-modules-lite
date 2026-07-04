<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Modules Manager Class
 *
 * Handles registration, management, and loading of all Divi modules
 * for the plugin, supporting both Divi 4 and Divi 5 architectures.
 *
 * @since   3.3.0
 * @author  The WP Squad <support@squadmodules.com>
 * @package DiviSquad
 */

namespace DiviSquad\Core;

use DiviSquad\Utils\WP as WPUtil;
use ET\Builder\Framework\DependencyManagement\DependencyTree;
use ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface;
use ET_Builder_Module;
use Throwable;

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
		try {
			if ( $this->initialized ) {
				return;
			}

			/**
			 * Filter whether to continue with module initialization
			 *
			 * Allows developers to conditionally block module initialization
			 * on specific pages or under certain conditions.
			 *
			 * @since 3.4.0
			 *
			 * @param bool $should_continue Whether to continue with initialization
			 * @param self $modules_manager Modules manager instance
			 */
			$should_continue = apply_filters( 'divi_squad_module_init_before', true, $this );

			if ( ! $should_continue ) {
				return;
			}

			/**
			 * Filter whether to initialize the modules manager.
			 *
			 * @since 3.3.3
			 *
			 * @param bool $should_init Whether to initialize the manager.
			 * @param self $modules     Current modules manager instance.
			 */
			if ( ! apply_filters( 'divi_squad_modules_should_init', true, $this ) ) {
				return;
			}

			// Determine builder type.
			$this->detect_builder_type();

			// Load module data.
			$this->load_module_data();

			// Register hooks.
			$this->register_hooks();

			$this->initialized = true;

			/**
			 * Fires after modules manager is initialized.
			 *
			 * @since 3.3.0
			 *
			 * @param self $modules Current modules manager instance.
			 */
			do_action( 'divi_squad_modules_init_complete', $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to initialize modules manager' );
		}
	}

	/**
	 * Detect the current Divi Builder type
	 */
	private function detect_builder_type(): void {
		try {
			// Detect if we're using Divi 5 by checking for the dependency tree class.
			if ( class_exists( DependencyTree::class ) ) {
				$this->builder_type = 'D5';
			} else {
				$this->builder_type = 'D4';
			}

			/**
			 * Filter the detected builder type.
			 *
			 * @since 3.3.0
			 *
			 * @param string $builder_type The detected builder type (D4 or D5).
			 * @param self   $modules      Current modules manager instance.
			 */
			$this->builder_type = apply_filters( 'divi_squad_modules_builder_type', $this->builder_type, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to detect Divi builder type' );
			// Default to D4 for safety if detection fails.
			$this->builder_type = 'D4';
		}
	}

	/**
	 * Register WordPress hooks
	 */
	private function register_hooks(): void {
		/**
		 * Filter the hooks to register.
		 *
		 * @since 3.3.3
		 *
		 * @param array<string, array<string, mixed>> $hooks   List of hooks to register.
		 * @param self                                $modules Current modules manager instance.
		 */
		$hooks = apply_filters(
			'divi_squad_modules_hooks_to_register',
			array(
				'et_builder_ready'                            => array(
					'callback' => array( $this, 'load_divi4_modules' ),
					'priority' => 9,
				),
				'divi_module_library_modules_dependency_tree' => array(
					'callback' => array( $this, 'load_divi5_modules' ),
					'priority' => 9,
				),
			),
			$this
		);

		foreach ( $hooks as $hook => $args ) {
			if ( ! isset( $args['callback'] ) || ! is_callable( $args['callback'] ) ) {
				continue;
			}

			add_action( $hook, $args['callback'], $args['priority'] ?? 10 );
		}

		/**
		 * Fires after hooks are registered.
		 *
		 * @since 3.3.3
		 *
		 * @param self $modules Current modules manager instance.
		 */
		do_action( 'divi_squad_modules_hooks_registered', $this );
	}

	/**
	 * Load module data from storage
	 */
	private function load_module_data(): void {
		try {
			/**
			 * Filter whether to load module data.
			 *
			 * @since 3.3.3
			 *
			 * @param bool $should_load Whether to load module data.
			 * @param self $modules     Current modules manager instance.
			 */
			if ( ! apply_filters( 'divi_squad_modules_data_should_load', true, $this ) ) {
				return;
			}

			// Load the registered modules list.
			$this->registered_modules = $this->get_registered_list();

			// Retrieve stored active modules.
			$this->active_modules = (array) $this->memory->get( 'active_modules', array() );

			// Retrieve stored inactive modules.
			$this->inactive_modules = (array) $this->memory->get( 'inactive_modules', array() );

			// If no active modules stored yet, use defaults.
			if ( 0 === count( $this->active_modules ) ) {
				$this->active_modules = array_column( $this->get_default_registries(), 'name' );
			}

			/**
			 * Filter active modules after loading.
			 *
			 * @since 3.3.0
			 *
			 * @param array<string> $active_modules  List of active module names.
			 * @param self          $modules_manager Modules manager instance.
			 */
			$this->active_modules = apply_filters( 'divi_squad_modules_active_list', $this->active_modules, $this );

			/**
			 * Fires after module data is loaded.
			 *
			 * @since 3.3.3
			 *
			 * @param self $modules Current modules manager instance.
			 */
			do_action( 'divi_squad_modules_data_loaded', $this );
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
		try {
			/**
			 * Filter registered modules list
			 *
			 * @since 3.3.0
			 *
			 * @param array<string, array<string, mixed>> $modules         List of module data.
			 * @param self                                $modules_manager Modules manager instance.
			 */
			return apply_filters( 'divi_squad_modules_registered_list', array(), $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to get registered modules list' );

			return array();
		}
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
		 * @param array<string, array<string, mixed>> $modules         List of module data.
		 * @param self                                $modules_manager Modules manager instance.
		 */
		return apply_filters( 'divi_squad_modules_premium_list', array(), $this );
	}

	/**
	 * Get default active modules
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_default_registries(): array {
		try {
			return $this->filter_modules(
				function ( $module ) {
					return $this->verify_module_type( $module ) && $module['is_default_active'];
				}
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to get default module registries' );

			return array();
		}
	}

	/**
	 * Get all active modules
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_active_registries(): array {
		try {
			return $this->filter_modules(
				function ( $module ) {
					return $this->verify_module_type( $module ) && in_array( $module['name'], $this->active_modules, true );
				}
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to get active module registries' );

			return array();
		}
	}

	/**
	 * Get all inactive modules
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_inactive_registries(): array {
		try {
			return $this->filter_modules(
				function ( $module ) {
					return $this->verify_module_type( $module ) && ! $module['is_default_active'];
				}
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to get inactive module registries' );

			return array();
		}
	}

	/**
	 * Filter modules based on callback
	 *
	 * @param callable $callback Function to filter modules.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function filter_modules( callable $callback ): array {
		try {
			return array_filter(
				$this->registered_modules,
				static function ( $module ) use ( $callback ) {
					return $callback( $module );
				}
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to filter modules' );

			return array();
		}
	}

	/**
	 * Load modules for Divi 4 or Divi 5
	 *
	 * @deprecated 3.3.0
	 *
	 * @param string|object $dependency_tree DependencyTree instance.
	 *
	 * @param string        $path            Modules directory path.
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
			do_action( 'divi_squad_modules_divi4_modules_loaded', $active_modules, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to load Divi 4 modules' );
		}
	}

	/**
	 * Load modules for Divi 5
	 *
	 * @param DependencyTree $dependency_tree DependencyTree instance.
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
			do_action( 'divi_squad_modules_divi5_modules_loaded', $active_modules, $dependency_tree, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to load Divi 5 modules' );
		}
	}

	/**
	 * Load module classes
	 *
	 * @param array<string, mixed> $module Module configuration.
	 */
	private function load_module_classes( array $module ): void {
		try {
			if ( ! isset( $module['classes']['root_class'] ) ) {
				return;
			}

			/**
			 * Filter whether to load module classes.
			 *
			 * @since 3.3.3
			 *
			 * @param bool                 $should_load Whether to load the module classes.
			 * @param array<string, mixed> $module      Module configuration.
			 * @param self                 $modules     Current modules manager instance.
			 */
			if ( ! apply_filters( 'divi_squad_modules_classes_should_load', true, $module, $this ) ) {
				return;
			}

			$this->load_main_module_class( $module );
			$this->load_child_module_class( $module );
			$this->load_full_width_module_class( $module );
			$this->load_child_full_width_module_class( $module );

			/**
			 * Fires after all module classes are loaded.
			 *
			 * @since 3.3.3
			 *
			 * @param array<string, mixed> $module  Module configuration.
			 * @param self                 $modules Current modules manager instance.
			 */
			do_action( 'divi_squad_modules_classes_loaded', $module, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to load module classes' );
		}
	}

	/**
	 * Load the main module class
	 *
	 * @param array<string, mixed> $module Module configuration.
	 */
	private function load_main_module_class( array $module ): void {
		try {
			$main_class = $module['classes']['root_class'];

			/**
			 * Filter the main module class name before loading.
			 *
			 * @since 3.3.3
			 *
			 * @param string               $class_name The class name to load.
			 * @param array<string, mixed> $module     Module configuration.
			 * @param self                 $modules    Current modules manager instance.
			 */
			$main_class = apply_filters( 'divi_squad_modules_main_class_name', $main_class, $module, $this );

			if ( $this->is_valid_module_class( $main_class ) ) {
				$main_module = new $main_class();
				$this->initialize_module_hooks( $main_module );

				/**
				 * Fires after the main module class is loaded.
				 *
				 * @since 3.3.3
				 *
				 * @param object               $main_module The main module instance.
				 * @param array<string, mixed> $module      Module configuration.
				 * @param self                 $modules     Current modules manager instance.
				 */
				do_action( 'divi_squad_modules_main_loaded', $main_module, $module, $this );
			}
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to load main module class' );
		}
	}

	/**
	 * Load the child module class
	 *
	 * @param array<string, mixed> $module Module configuration.
	 */
	private function load_child_module_class( array $module ): void {
		try {
			if ( ! isset( $module['classes']['child_class'] ) ) {
				return;
			}

			$child_class = $module['classes']['child_class'];

			/**
			 * Filter the child module class name before loading.
			 *
			 * @since 3.3.3
			 *
			 * @param string               $class_name The class name to load.
			 * @param array<string, mixed> $module     Module configuration.
			 * @param self                 $modules    Current modules manager instance.
			 */
			$child_class = apply_filters( 'divi_squad_modules_child_class_name', $child_class, $module, $this );

			if ( $this->is_valid_module_class( $child_class ) ) {
				$child_module = new $child_class();
				$this->initialize_module_hooks( $child_module );

				/**
				 * Fires after the child module class is loaded.
				 *
				 * @since 3.3.3
				 *
				 * @param object               $child_module The child module instance.
				 * @param array<string, mixed> $module       Module configuration.
				 * @param self                 $modules      Current modules manager instance.
				 */
				do_action( 'divi_squad_modules_child_loaded', $child_module, $module, $this );
			}
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to load child module class' );
		}
	}

	/**
	 * Load the full width module class
	 *
	 * @param array<string, mixed> $module Module configuration.
	 */
	private function load_full_width_module_class( array $module ): void {
		try {
			if ( ! isset( $module['classes']['full_width_class'] ) ) {
				return;
			}

			$full_width_class = $module['classes']['full_width_class'];

			/**
			 * Filter the full width module class name before loading.
			 *
			 * @since 3.3.3
			 *
			 * @param string               $class_name The class name to load.
			 * @param array<string, mixed> $module     Module configuration.
			 * @param self                 $modules    Current modules manager instance.
			 */
			$full_width_class = apply_filters( 'divi_squad_modules_full_width_class_name', $full_width_class, $module, $this );

			if ( $this->is_valid_module_class( $full_width_class ) ) {
				$full_width_module = new $full_width_class();
				$this->initialize_module_hooks( $full_width_module );

				/**
				 * Fires after the full width module class is loaded.
				 *
				 * @since 3.3.3
				 *
				 * @param object               $full_width_module The full width module instance.
				 * @param array<string, mixed> $module            Module configuration.
				 * @param self                 $modules           Current modules manager instance.
				 */
				do_action( 'divi_squad_modules_full_width_loaded', $full_width_module, $module, $this );
			}
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to load full width module class' );
		}
	}

	/**
	 * Load the child full width module class
	 *
	 * @param array<string, mixed> $module Module configuration.
	 */
	private function load_child_full_width_module_class( array $module ): void {
		try {
			if ( ! isset( $module['classes']['child_full_width_class'] ) ) {
				return;
			}

			$child_full_width_class = $module['classes']['child_full_width_class'];

			/**
			 * Filter the child full width module class name before loading.
			 *
			 * @since 3.3.3
			 *
			 * @param string               $class_name The class name to load.
			 * @param array<string, mixed> $module     Module configuration.
			 * @param self                 $modules    Current modules manager instance.
			 */
			$child_full_width_class = apply_filters( 'divi_squad_modules_child_full_width_class_name', $child_full_width_class, $module, $this );

			if ( $this->is_valid_module_class( $child_full_width_class ) ) {
				$full_width_child_module = new $child_full_width_class();
				$this->initialize_module_hooks( $full_width_child_module );

				/**
				 * Fires after the child full width module class is loaded.
				 *
				 * @since 3.3.3
				 *
				 * @param object               $full_width_child_module The child full width module instance.
				 * @param array<string, mixed> $module                  Module configuration.
				 * @param self                 $modules                 Current modules manager instance.
				 */
				do_action( 'divi_squad_modules_child_full_width_loaded', $full_width_child_module, $module, $this );
			}
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to load child full width module class' );
		}
	}

	/**
	 * Check if a class is a valid module class
	 *
	 * @param string $class_name The class name to check.
	 *
	 * @return bool Whether the class is valid.
	 */
	private function is_valid_module_class( string $class_name ): bool {
		/**
		 * Filter whether a class is a valid module class.
		 *
		 * @since 3.3.3
		 *
		 * @param bool   $is_valid   Whether the class is valid.
		 * @param string $class_name The class name to check.
		 * @param self   $modules    Current modules manager instance.
		 */
		return apply_filters(
			'divi_squad_modules_class_is_valid',
			class_exists( $class_name ) && is_subclass_of( $class_name, ET_Builder_Module::class ),
			$class_name,
			$this
		);
	}

	/**
	 * Initialize module hooks if available
	 *
	 * @param object $module The module instance.
	 */
	private function initialize_module_hooks( object $module ): void {
		try {
			/**
			 * Filter whether to initialize module hooks.
			 *
			 * @since 3.3.3
			 *
			 * @param bool   $should_init Whether to initialize hooks.
			 * @param object $module      The module instance.
			 * @param self   $modules     Current modules manager instance.
			 */
			if ( ! apply_filters( 'divi_squad_modules_hooks_should_init', true, $module, $this ) ) {
				return;
			}

			if ( method_exists( $module, 'squad_init_custom_hooks' ) ) {
				$module->squad_init_custom_hooks();

				/**
				 * Fires after module hooks are initialized.
				 *
				 * @since 3.3.3
				 *
				 * @param object $module  The module instance.
				 * @param self   $modules Current modules manager instance.
				 */
				do_action( 'divi_squad_modules_hooks_initialized', $module, $this );
			}
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to initialize module hooks' );
		}
	}

	/**
	 * Load a Divi 5 module
	 *
	 * @param array<string, mixed> $module          Module configuration.
	 * @param DependencyTree       $dependency_tree DependencyTree instance.
	 */
	private function load_divi5_module( array $module, DependencyTree $dependency_tree ): void {
		try {
			if ( ! isset( $module['classes'] ) ) {
				return;
			}

			/**
			 * Filter whether to load Divi 5 module.
			 *
			 * @since 3.3.3
			 *
			 * @param bool                 $should_load     Whether to load the Divi 5 module.
			 * @param array<string, mixed> $module          Module configuration.
			 * @param DependencyTree       $dependency_tree DependencyTree instance.
			 * @param self                 $modules         Current modules manager instance.
			 */
			if ( ! apply_filters( 'divi_squad_modules_divi5_should_load', true, $module, $dependency_tree, $this ) ) {
				return;
			}

			// Check for Divi 5 specific class.
			$class_key = 'root_block_class';
			if ( isset( $module['classes'][ $class_key ] ) && class_exists( $module['classes'][ $class_key ] ) ) {
				$block_class = $module['classes'][ $class_key ];

				/**
				 * Filter the Divi 5 block class name.
				 *
				 * @since 3.3.3
				 *
				 * @param string               $block_class     The block class name.
				 * @param array<string, mixed> $module          Module configuration.
				 * @param DependencyTree       $dependency_tree DependencyTree instance.
				 * @param self                 $modules         Current modules manager instance.
				 */
				$block_class = apply_filters( 'divi_squad_modules_divi5_block_class_name', $block_class, $module, $dependency_tree, $this );

				$implements = class_implements( $block_class );
				if ( ! $implements ) {
					return;
				}

				// Verify class implements DependencyInterface
				if ( in_array( DependencyInterface::class, class_implements( $block_class ), true ) ) {
					$block_instance = new $block_class();
					$dependency_tree->add_dependency( $block_instance ); // @phpstan-ignore-line

					/**
					 * Fires after Divi 5 module is added to dependency tree.
					 *
					 * @since 3.3.3
					 *
					 * @param object               $block_instance  The block instance.
					 * @param array<string, mixed> $module          Module configuration.
					 * @param DependencyTree       $dependency_tree DependencyTree instance.
					 * @param self                 $modules         Current modules manager instance.
					 */
					do_action( 'divi_squad_modules_divi5_loaded', $block_instance, $module, $dependency_tree, $this );
				}
			}
		} catch ( Throwable $e ) {
			$module_name = $module['name'] ?? 'unknown';
			divi_squad()->log_error( $e, sprintf( 'Failed to load Divi 5 module: %s', $module_name ) );
		}
	}

	/**
	 * Check if a module is active
	 *
	 * @param string $module_name Module name.
	 *
	 * @return bool Whether the module is active.
	 */
	public function is_module_active( string $module_name ): bool {
		/**
		 * Filter whether a module is active.
		 *
		 * @since 3.3.3
		 *
		 * @param bool   $is_active   Whether the module is active.
		 * @param string $module_name Module name.
		 * @param self   $modules     Current modules manager instance.
		 */
		return apply_filters(
			'divi_squad_modules_is_active',
			in_array( $module_name, $this->active_modules, true ),
			$module_name,
			$this
		);
	}

	/**
	 * Check if a module is active by class name
	 *
	 * @param string $class_name Module class name.
	 *
	 * @return bool Whether the module is active.
	 */
	public function is_module_active_by_class( string $class_name ): bool {
		/**
		 * Filter whether a module is active by class name.
		 *
		 * @since 3.3.3
		 *
		 * @param bool   $is_active  Whether the module is active.
		 * @param string $class_name Module class name.
		 * @param self   $modules    Current modules manager instance.
		 */
		return apply_filters(
			'divi_squad_modules_is_active_by_class',
			$this->check_module_active_by_class( $class_name ),
			$class_name,
			$this
		);
	}

	/**
	 * Check if a module is active by class name
	 *
	 * @param string $class_name Module class name.
	 *
	 * @return bool Whether the module is active.
	 */
	private function check_module_active_by_class( string $class_name ): bool {
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
	 * @param array<string, mixed> $module Module configuration.
	 *
	 * @return bool Whether the module is compatible.
	 */
	protected function verify_module_type( array $module ): bool {
		/**
		 * Filter whether a module is compatible with current builder type.
		 *
		 * @since 3.3.3
		 *
		 * @param bool                 $is_compatible Whether the module is compatible.
		 * @param array<string, mixed> $module        Module configuration.
		 * @param self                 $modules       Current modules manager instance.
		 */
		return apply_filters(
			'divi_squad_modules_type_is_compatible',
			$this->check_module_type_compatibility( $module ),
			$module,
			$this
		);
	}

	/**
	 * Check module type compatibility
	 *
	 * @param array<string, mixed> $module Module configuration.
	 *
	 * @return bool Whether the module is compatible.
	 */
	private function check_module_type_compatibility( array $module ): bool {
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
	 * @param array<string, mixed> $module         Module configuration.
	 * @param array<string>        $active_plugins List of active plugin slugs.
	 *
	 * @return bool Whether requirements are met.
	 */
	protected function verify_requirements( array $module, array $active_plugins ): bool {
		/**
		 * Filter whether a module meets all requirements.
		 *
		 * @since 3.3.3
		 *
		 * @param bool                 $meets_requirements Whether requirements are met.
		 * @param array<string, mixed> $module             Module configuration.
		 * @param array<string>        $active_plugins     List of active plugin slugs.
		 * @param self                 $modules            Current modules manager instance.
		 */
		return apply_filters(
			'divi_squad_modules_requirements_met',
			$this->check_module_requirements( $module, $active_plugins ),
			$module,
			$active_plugins,
			$this
		);
	}

	/**
	 * Check module requirements
	 *
	 * @param array<string, mixed> $module         Module configuration.
	 * @param array<string>        $active_plugins List of active plugin slugs.
	 *
	 * @return bool Whether requirements are met.
	 */
	private function check_module_requirements( array $module, array $active_plugins ): bool {
		// If no requirements, module is valid.
		if ( ! isset( $module['required'] ) ) {
			return true;
		}

		// Check plugin requirements.
		if ( isset( $module['required']['plugin'] ) ) {
			$required_plugins = $module['required']['plugin'];

			// Single plugin requirement.
			if ( is_string( $required_plugins ) ) {
				// Check for multiple options (plugin1|plugin2).
				if ( strpos( $required_plugins, '|' ) !== false ) {
					$plugin_options = explode( '|', $required_plugins );

					// At least one plugin must be active.
					foreach ( $plugin_options as $plugin ) {
						if ( in_array( $plugin, $active_plugins, true ) ) {
							return true;
						}
					}

					return false;
				}

				// Single plugin must be active.
				return in_array( $required_plugins, $active_plugins, true );
			}

			// Multiple required plugins (all must be active).
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
		/**
		 * Filter all available modules.
		 *
		 * @since 3.3.3
		 *
		 * @param array<string, array<string, mixed>> $modules         List of all modules.
		 * @param self                                $modules_manager Current modules manager instance.
		 */
		return apply_filters(
			'divi_squad_modules_all_list',
			array_merge(
				$this->registered_modules,
				$this->get_premium_modules()
			),
			$this
		);
	}

	/**
	 * Enable a module
	 *
	 * @param string $module_name Module name.
	 *
	 * @return bool Whether the module was enabled.
	 */
	public function enable_module( string $module_name ): bool {
		try {
			if ( $this->is_module_active( $module_name ) ) {
				return true;
			}

			if ( ! isset( $this->registered_modules[ $module_name ] ) ) {
				return false;
			}

			/**
			 * Filter whether to enable a module.
			 *
			 * @since 3.3.3
			 *
			 * @param bool   $should_enable Whether to enable the module.
			 * @param string $module_name   Module name.
			 * @param self   $modules       Current modules manager instance.
			 */
			if ( ! apply_filters( 'divi_squad_modules_should_enable', true, $module_name, $this ) ) {
				return false;
			}

			$this->active_modules[] = $module_name;
			$this->memory->set( 'active_modules', $this->active_modules );

			/**
			 * Fires after a module is enabled
			 *
			 * @since 3.4.0
			 *
			 * @param string               $module_name The name of the enabled module
			 * @param array<string, mixed> $module_data The module configuration data
			 * @param self                 $modules     Current modules manager instance
			 */
			do_action( 'divi_squad_module_enabled', $module_name, $this->registered_modules[ $module_name ] ?? array(), $this );

			return true;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, sprintf( 'Failed to enable module: %s', $module_name ) );

			return false;
		}
	}

	/**
	 * Disable a module
	 *
	 * @param string $module_name Module name.
	 *
	 * @return bool Whether the module was disabled.
	 */
	public function disable_module( string $module_name ): bool {
		try {
			if ( ! $this->is_module_active( $module_name ) ) {
				return true;
			}

			/**
			 * Filter whether to disable a module.
			 *
			 * @since 3.3.3
			 *
			 * @param bool   $should_disable Whether to disable the module.
			 * @param string $module_name    Module name.
			 * @param self   $modules        Current modules manager instance.
			 */
			if ( ! apply_filters( 'divi_squad_modules_should_disable', true, $module_name, $this ) ) {
				return false;
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

			/**
			 * Fires after a module is disabled
			 *
			 * @since 3.4.0
			 *
			 * @param string               $module_name The name of the disabled module
			 * @param array<string, mixed> $module_data The module configuration data
			 * @param self                 $modules     Current modules manager instance
			 */
			do_action( 'divi_squad_module_disabled', $module_name, $this->registered_modules[ $module_name ] ?? array(), $this );

			return true;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, sprintf( 'Failed to disable module: %s', $module_name ) );

			return false;
		}
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
	 * @param string $module_name Module name.
	 *
	 * @return array<string, mixed>|null Module data or null if not found.
	 */
	public function get_module_info( string $module_name ): ?array {
		/**
		 * Filter module info before returning.
		 *
		 * @since 3.3.3
		 *
		 * @param array<string, mixed>|null $module_info Module data or null.
		 * @param string                    $module_name Module name.
		 * @param self                      $modules     Current modules manager instance.
		 */
		return apply_filters(
			'divi_squad_modules_info_data',
			$this->registered_modules[ $module_name ] ?? null,
			$module_name,
			$this
		);
	}

	/**
	 * Get all module categories
	 *
	 * Categories are compiled based on module configuration.
	 * Premium modules are flagged based on activation status.
	 *
	 * @return array<string, string> List of module categories.
	 */
	public function get_module_categories(): array {
		$categories = array();

		foreach ( $this->registered_modules as $module ) {
			if ( isset( $module['category'], $module['category_title'] ) ) {
				$categories[ $module['category'] ] = $module['category_title'];
			}
		}

		// Add premium category if needed.
		if ( ! divi_squad()->is_pro_activated() ) {
			$categories['premium-modules'] = esc_html__( 'Premium Modules', 'squad-modules-for-divi' );
		}

		/**
		 * Filter module categories.
		 *
		 * @since 3.3.3
		 *
		 * @param array<string, string> $categories Categories with their titles.
		 * @param self                  $modules    Current modules manager instance.
		 */
		return apply_filters( 'divi_squad_modules_categories_list', $categories, $this );
	}

	/**
	 * Reset modules to default state
	 *
	 * @return bool Success status.
	 */
	public function reset_to_default(): bool {
		try {
			/**
			 * Filter whether to reset modules to default state.
			 *
			 * @since 3.3.3
			 *
			 * @param bool $should_reset Whether to reset modules.
			 * @param self $modules      Current modules manager instance.
			 */
			if ( ! apply_filters( 'divi_squad_modules_should_reset', true, $this ) ) {
				return false;
			}

			$default_modules      = $this->get_default_registries();
			$this->active_modules = array_column( $default_modules, 'name' );

			$all_module_names       = array_column( $this->registered_modules, 'name' );
			$this->inactive_modules = array_values( array_diff( $all_module_names, $this->active_modules ) );

			$this->memory->set( 'active_modules', $this->active_modules );
			$this->memory->set( 'inactive_modules', $this->inactive_modules );
			$this->memory->set( 'active_module_version', divi_squad()->get_version() );

			/**
			 * Fires after modules are reset to default state
			 *
			 * @since 3.4.0
			 *
			 * @param array<string> $active_modules   List of active module names after reset
			 * @param array<string> $inactive_modules List of inactive module names after reset
			 * @param self          $modules          Current modules manager instance
			 */
			do_action( 'divi_squad_modules_reset', $this->active_modules, $this->inactive_modules, $this );

			return true;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to reset modules to default' );

			return false;
		}
	}
}
