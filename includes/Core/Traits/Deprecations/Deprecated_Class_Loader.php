<?php
/**
 * DeprecatedClassLoader Trait
 *
 * Provides improved handling of deprecated classes with better performance,
 * lazy loading, and more robust error handling.
 *
 * @since   3.2.0
 * @package DiviSquad
 */

namespace DiviSquad\Core\Traits\Deprecations;

use RuntimeException;
use Throwable;

/**
 * Enhanced DeprecatedClassLoader Trait
 *
 * Provides improved handling of deprecated classes with better performance,
 * lazy loading, and more robust error handling.
 *
 * @since   3.2.0
 * @package DiviSquad
 */
trait Deprecated_Class_Loader {
	/**
	 * Class configuration cache with metadata
	 *
	 * @since 3.2.0
	 * @var array<string, array{
	 *     file_path: string,
	 *     config: array{
	 *         priority: int,
	 *         condition: callable|null,
	 *         callbacks: array{before: callable|null, after: callable|null},
	 *         action?: array{name: string, priority?: int}
	 *     },
	 *     loaded: bool
	 * }>
	 */
	private array $class_config_cache = array();

	/**
	 * Initialize the deprecated class loader
	 *
	 * @since 3.2.0
	 *
	 * @throws RuntimeException If initialization fails.
	 */
	public function init_deprecated_class_loader(): void {
		if ( ! defined( 'DIVI_SQUAD_PLUGIN_FILE' ) ) {
			return;
		}

		try {
			/**
			 * Fires before deprecated class loader initialization.
			 *
			 * @since 3.3.0
			 *
			 * @param static $instance The deprecated class loader instance.
			 */
			do_action( 'divi_squad_before_deprecated_class_loader_init', $this );

			$this->class_config_cache = array();
			$this->load_class_configurations();

			/**
			 * Fires after deprecated class loader initialization.
			 *
			 * @since 3.3.0
			 *
			 * @param static $instance The deprecated class loader instance.
			 */
			do_action( 'divi_squad_after_deprecated_class_loader_init', $this );
		} catch ( Throwable $e ) {
			$this->log_error(
				$e,
				'Failed to initialize deprecated class loader',
				true
			);
		}
	}

	/**
	 * Load and process class configurations
	 *
	 * @since 3.2.0
	 */
	private function load_class_configurations(): void {
		$base_configs = $this->get_deprecated_classes_list();

		/**
		 * Filters the list of deprecated classes before validation and registration.
		 *
		 * Allows modification of the deprecated classes configuration before they are processed.
		 * This can be used to add, remove, or modify deprecated class configurations.
		 *
		 * @since 3.2.0
		 *
		 * @param array<string, array<string,mixed>> $base_configs Array of deprecated class configurations.
		 * @param static                             $instance     Current instance.
		 */
		$filtered_configs = apply_filters( 'divi_squad_deprecated_classes', $base_configs, $this );

		foreach ( $filtered_configs as $relative_path => $config ) {
			try {
				// If the file path is relative, make it absolute.
				$file_path = $this->get_path( "/deprecated/$relative_path" );

				/**
				 * Fires before registering a deprecated class configuration.
				 *
				 * @since 3.3.0
				 *
				 * @param string              $file_path File path of the deprecated class.
				 * @param array<string,mixed> $config    Configuration for the deprecated class.
				 * @param static              $instance  The deprecated class loader instance.
				 */
				do_action( 'divi_squad_before_register_deprecated_class', $file_path, $config, $this );

				$this->register_class_configuration( $file_path, $config );

				/**
				 * Fires after registering a deprecated class configuration.
				 *
				 * @since 3.3.0
				 *
				 * @param string              $file_path File path of the deprecated class.
				 * @param array<string,mixed> $config    Configuration for the deprecated class.
				 * @param static              $instance  The deprecated class loader instance.
				 */
				do_action( 'divi_squad_after_register_deprecated_class', $file_path, $config, $this );
			} catch ( Throwable $e ) {
				$this->log_error(
					$e,
					sprintf( 'Failed to register deprecated class: %s', $file_path ?? '' )
				);
				continue;
			}
		}
	}

	/**
	 * Register a class configuration for lazy loading
	 *
	 * @since 3.2.0
	 *
	 * @param string              $file_path File path for the class.
	 * @param array<string,mixed> $config    Class configuration.
	 */
	private function register_class_configuration( string $file_path, array $config ): void {
		try {
			$normalized_config = $this->normalize_config( $config );

			$this->class_config_cache[ $file_path ] = array(
				'file_path' => $file_path,
				'config'    => $normalized_config,
				'loaded'    => false,
			);

			if ( ! isset( $config['action'] ) ) {
				$this->load_class_file( $file_path );

				return;
			}

			$this->setup_class_loading_hook( $file_path, $config );
		} catch ( Throwable $e ) {
			$this->log_error(
				$e,
				sprintf( 'Error registering class configuration: %s', $file_path )
			);
		}
	}

	/**
	 * Normalize class configuration with defaults and filters
	 *
	 * @since 3.2.0
	 *
	 * @param array<string,mixed> $config Raw configuration.
	 *
	 * @return array<string,mixed> Normalized configuration
	 */
	private function normalize_config( array $config ): array {
		$default_config = array(
			'priority'  => 10,
			'condition' => null,
			'callbacks' => array(
				'before' => null,
				'after'  => null,
			),
		);

		/**
		 * Filters the default configuration for deprecated classes.
		 *
		 * @since 3.3.0
		 *
		 * @param array<string,mixed> $default_config Default configuration array.
		 */
		$default_config = apply_filters( 'divi_squad_deprecated_class_default_config', $default_config );

		return array_merge( $default_config, $config );
	}

	/**
	 * Setup WordPress hook for class loading
	 *
	 * @since 3.2.0
	 *
	 * @param string              $file_path Class file path.
	 * @param array<string,mixed> $config    Loading configuration.
	 */
	private function setup_class_loading_hook( string $file_path, array $config ): void {
		try {
			$priority = $this->determine_loading_priority( $file_path, $config );

			add_action(
				$config['action']['name'],
				function () use ( $file_path ) {
					try {
						/**
						 * Fires just before a scheduled class is loaded via hook.
						 *
						 * @since 3.3.0
						 *
						 * @param string $file_path File path of the scheduled class.
						 * @param static $instance  The deprecated class loader instance.
						 */
						do_action( 'divi_squad_before_load_scheduled_class', $file_path, $this );

						$this->load_scheduled_class( $file_path );

						/**
						 * Fires after a scheduled class has been loaded via hook.
						 *
						 * @since 3.3.0
						 *
						 * @param string $file_path File path of the scheduled class.
						 * @param static $instance  The deprecated class loader instance.
						 */
						do_action( 'divi_squad_after_load_scheduled_class', $file_path, $this );
					} catch ( Throwable $e ) {
						$this->log_error(
							$e,
							sprintf( 'Error in scheduled class loading hook for: %s', $file_path )
						);
					}
				},
				$priority
			);
		} catch ( Throwable $e ) {
			$this->log_error(
				$e,
				sprintf( 'Failed to setup loading hook for: %s', $file_path )
			);
		}
	}

	/**
	 * Determine loading priority for class with filter support
	 *
	 * @since 3.2.0
	 *
	 * @param string              $file_path Class file path.
	 * @param array<string,mixed> $config    Loading configuration.
	 *
	 * @return int Priority value
	 */
	private function determine_loading_priority( string $file_path, array $config ): int {
		$default_priority = $config['action']['priority'] ?? 10;

		/**
		 * Filters the priority for loading a deprecated class.
		 *
		 * Allows modification of the loading priority for deprecated classes that are
		 * loaded via WordPress hooks.
		 *
		 * @since 3.2.0
		 *
		 * @param int                 $priority  The priority for the action hook (default: 10).
		 * @param string              $file_path File path of the deprecated class.
		 * @param array<string,mixed> $config    Complete class configuration.
		 */
		return (int) apply_filters(
			'divi_squad_deprecated_class_loading_priority',
			$default_priority,
			$file_path,
			$config
		);
	}

	/**
	 * Load a scheduled class with condition checking
	 *
	 * @since 3.2.0
	 *
	 * @param string $file_path Class to load.
	 */
	private function load_scheduled_class( string $file_path ): void {
		try {
			if ( ! isset( $this->class_config_cache[ $file_path ] ) ) {
				return;
			}

			$cache = $this->class_config_cache[ $file_path ];

			if ( ! $this->should_load_class( $cache['config'], $file_path ) ) {
				/**
				 * Fires when a class load is skipped due to condition check.
				 *
				 * @since 3.3.0
				 *
				 * @param string              $file_path File path of the skipped class.
				 * @param array<string,mixed> $config    Configuration that caused the skip.
				 * @param static              $instance  The deprecated class loader instance.
				 */
				do_action( 'divi_squad_deprecated_class_load_skipped', $file_path, $cache['config'], $this );

				return;
			}

			$this->execute_loading_sequence( $cache, $file_path );
		} catch ( Throwable $e ) {
			$this->log_error(
				$e,
				sprintf( 'Error loading scheduled class: %s', $file_path )
			);
		}
	}

	/**
	 * Check if class should be loaded
	 *
	 * @since 3.2.0
	 *
	 * @param array<string,mixed> $config    Class configuration.
	 * @param string              $file_path Class file path.
	 *
	 * @return bool
	 */
	private function should_load_class( array $config, string $file_path ): bool {
		try {
			$should_load = ! $config['condition'] || $this->evaluate_condition( $config['condition'], $file_path );

			/**
			 * Filters whether a deprecated class should be loaded.
			 *
			 * @since 3.3.0
			 *
			 * @param bool                $should_load Whether the class should be loaded.
			 * @param string              $file_path   File path of the class.
			 * @param array<string,mixed> $config      Class configuration.
			 * @param static              $instance    The deprecated class loader instance.
			 */
			return apply_filters( 'divi_squad_should_load_deprecated_class', $should_load, $file_path, $config, $this );
		} catch ( Throwable $e ) {
			$this->log_error(
				$e,
				sprintf( 'Error evaluating if class should load: %s', $file_path )
			);

			return false; // Default to not loading on error.
		}
	}

	/**
	 * Execute the class loading sequence
	 *
	 * @since 3.2.0
	 *
	 * @param array<string, mixed> $cache     Cache entry for class.
	 * @param string               $file_path Class file path.
	 */
	private function execute_loading_sequence( array $cache, string $file_path ): void {
		try {
			$this->execute_callback( $cache['config']['callbacks']['before'] ?? null, $file_path );

			if ( $this->load_class_file( $cache['file_path'] ) ) {
				$this->execute_callback( $cache['config']['callbacks']['after'] ?? null, $file_path );
			}
		} catch ( Throwable $e ) {
			$this->log_error(
				$e,
				sprintf( 'Error executing loading sequence for: %s', $file_path )
			);
		}
	}

	/**
	 * Load a class file with pre/post hooks and enhanced validation
	 *
	 * @since 3.2.0
	 *
	 * @param string $file_path File path to load.
	 *
	 * @return bool Success status
	 */
	private function load_class_file( string $file_path ): bool {
		try {
			// Check if file has already been loaded
			if ( isset( $this->class_config_cache[ $file_path ]['loaded'] ) && $this->class_config_cache[ $file_path ]['loaded'] ) {
				return true;
			}

			// Validate file path
			if ( '' === $file_path ) {
				throw new RuntimeException( 'Empty file path provided' );
			}

			// Ensure file exists
			if ( ! $this->get_wp_fs()->exists( $file_path ) ) {
				throw new RuntimeException( sprintf( 'File does not exist: %s', $file_path ) );
			}

			// Check if file is readable
			if ( ! $this->get_wp_fs()->is_readable( $file_path ) ) {
				throw new RuntimeException( sprintf( 'File is not readable: %s', $file_path ) );
			}

			// Ensure we have the array structure ready for the 'loaded' flag
			if ( ! isset( $this->class_config_cache[ $file_path ] ) ) {
				$this->class_config_cache[ $file_path ] = array(
					'file_path' => $file_path,
					'config'    => $this->normalize_config( array() ),
					'loaded'    => false,
				);
			}

			/**
			 * Fires before loading a deprecated class file.
			 *
			 * @since 3.3.0
			 *
			 * @param string $file_path File path being loaded.
			 * @param static $instance  The deprecated class loader instance.
			 */
			do_action( 'divi_squad_before_load_deprecated_class', $file_path, $this );

			// Set a memory limit checkpoint if needed
			$memory_before = memory_get_usage();

			// Include the file
			require_once $file_path;

			// Check for memory issues
			$memory_after = memory_get_usage();
			$memory_used  = $memory_after - $memory_before;

			if ( $memory_used > 5 * 1024 * 1024 ) { // 5MB threshold, adjust as needed
				// Log a warning but don't fail the load
				$this->log_warning(
					sprintf(
						'High memory usage (%s MB) when loading: %s',
						round( $memory_used / ( 1024 * 1024 ), 2 ),
						$file_path
					)
				);
			}

			// Mark as loaded
			$this->class_config_cache[ $file_path ]['loaded'] = true;

			/**
			 * Fires after successfully loading a deprecated class file.
			 *
			 * @since 3.3.0
			 *
			 * @param string $file_path File path that was loaded.
			 * @param static $instance  The deprecated class loader instance.
			 */
			do_action( 'divi_squad_after_load_deprecated_class', $file_path, $this );

			return true;
		} catch ( Throwable $e ) {
			$this->log_error( $e, 'Deprecated class loading issue' );

			/**
			 * Fires when a deprecated class file fails to load.
			 *
			 * @since 3.3.0
			 *
			 * @param string    $file_path File path that failed to load.
			 * @param Throwable $e         The exception that occurred.
			 * @param static    $instance  The deprecated class loader instance.
			 */
			do_action( 'divi_squad_deprecated_class_load_failed', $file_path, $e, $this );

			return false;
		}
	}

	/**
	 * Evaluate a loading condition
	 *
	 * @since 3.2.0
	 *
	 * @param callable|array $condition Condition to evaluate.
	 * @param string         $file_path File path to evaluate condition for.
	 *
	 * @return bool Whether condition passes
	 */
	private function evaluate_condition( $condition, string $file_path ): bool {
		try {
			$result = true;

			if ( is_callable( $condition ) ) {
				$result = (bool) $condition( $file_path );
			} elseif ( isset( $condition['callback'] ) && is_callable( $condition['callback'] ) ) {
				$args = $condition['args'] ?? array( $file_path );

				$result = (bool) call_user_func_array( $condition['callback'], $args );
			}

			/**
			 * Filters the result of a condition evaluation for loading a deprecated class.
			 *
			 * @since 3.3.0
			 *
			 * @param bool                          $result    Result of the condition evaluation.
			 * @param callable|array<string,string> $condition The condition that was evaluated.
			 * @param string                        $file_path File path for which the condition was evaluated.
			 * @param static                        $instance  The deprecated class loader instance.
			 */
			return apply_filters( 'divi_squad_deprecated_class_condition_result', $result, $condition, $file_path, $this );
		} catch ( Throwable $e ) {
			$this->log_error(
				$e,
				sprintf( 'Error evaluating condition for: %s', $file_path )
			);

			return false;
		}
	}

	/**
	 * Execute a callback if valid
	 *
	 * @since 3.2.0
	 *
	 * @param callable|null $callback  Callback to execute.
	 * @param string        $file_path File path context.
	 */
	private function execute_callback( ?callable $callback, string $file_path ): void {
		if ( ! is_callable( $callback ) ) {
			return;
		}

		try {
			/**
			 * Fires before executing a callback for a deprecated class.
			 *
			 * @since 3.3.0
			 *
			 * @param callable $callback  The callback about to be executed.
			 * @param string   $file_path File path for context.
			 * @param static   $instance  The deprecated class loader instance.
			 */
			do_action( 'divi_squad_before_deprecated_class_callback', $callback, $file_path, $this );

			$callback( $file_path );

			/**
			 * Fires after executing a callback for a deprecated class.
			 *
			 * @since 3.3.0
			 *
			 * @param callable $callback  The callback that was executed.
			 * @param string   $file_path File path for context.
			 * @param static   $instance  The deprecated class loader instance.
			 */
			do_action( 'divi_squad_after_deprecated_class_callback', $callback, $file_path, $this );
		} catch ( Throwable $e ) {
			$this->log_error(
				$e,
				sprintf( 'Error executing callback for: %s', $file_path )
			);
		}
	}

	/**
	 * Get information about loaded deprecated classes
	 *
	 * @since 3.3.0
	 * @return array Information about loaded classes
	 */
	public function get_loaded_classes_info(): array {
		try {
			$info = array();

			foreach ( $this->class_config_cache as $file_path => $data ) {
				if ( $data['loaded'] ) {
					$info[ $file_path ] = array(
						'file_path' => $file_path,
						'loaded'    => true,
						'config'    => $data['config'],
					);
				}
			}

			/**
			 * Filters the information about loaded deprecated classes.
			 *
			 * @since 3.3.0
			 *
			 * @param array  $info     Information about loaded classes.
			 * @param static $instance The deprecated class loader instance.
			 */
			return apply_filters( 'divi_squad_loaded_deprecated_classes_info', $info, $this );
		} catch ( Throwable $e ) {
			$this->log_error(
				$e,
				'Error retrieving loaded classes info'
			);

			return array();
		}
	}

	/**
	 * Check if a specific class is registered
	 *
	 * @since 3.3.0
	 *
	 * @param string $file_path File path to check.
	 *
	 * @return bool Whether the class is registered
	 */
	public function is_class_registered( string $file_path ): bool {
		try {
			$is_registered = isset( $this->class_config_cache[ $file_path ] );

			/**
			 * Filters whether a deprecated class is registered.
			 *
			 * @since 3.3.0
			 *
			 * @param bool   $is_registered Whether the class is registered.
			 * @param string $file_path     File path to check.
			 * @param static $instance      The deprecated class loader instance.
			 */
			return apply_filters( 'divi_squad_is_deprecated_class_registered', $is_registered, $file_path, $this );
		} catch ( Throwable $e ) {
			$this->log_error(
				$e,
				sprintf( 'Error checking if class is registered: %s', $file_path )
			);

			return false;
		}
	}

	/**
	 * Check if a specific class is loaded
	 *
	 * @since 3.3.0
	 *
	 * @param string $file_path File path to check.
	 *
	 * @return bool Whether the class is loaded
	 */
	public function is_class_loaded( string $file_path ): bool {
		try {
			$is_loaded = isset( $this->class_config_cache[ $file_path ] ) && $this->class_config_cache[ $file_path ]['loaded'];

			/**
			 * Filters whether a deprecated class is loaded.
			 *
			 * @since 3.3.0
			 *
			 * @param bool   $is_loaded Whether the class is loaded.
			 * @param string $file_path File path to check.
			 * @param static $instance  The deprecated class loader instance.
			 */
			return apply_filters( 'divi_squad_is_deprecated_class_loaded', $is_loaded, $file_path, $this );
		} catch ( Throwable $e ) {
			$this->log_error(
				$e,
				sprintf( 'Error checking if class is loaded: %s', $file_path )
			);

			return false;
		}
	}
}
