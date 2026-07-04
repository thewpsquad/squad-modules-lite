<?php

namespace DiviSquad\Core\Traits;

use DiviSquad\SquadModules;
use RuntimeException;
use Throwable;

/**
 * Enhanced DeprecatedClassLoader Trait
 *
 * Provides improved handling of deprecated classes with better performance,
 * lazy loading, and more robust error handling.
 *
 * @since 3.2.0
 * @package DiviSquad
 */
trait DeprecatedClassLoader {
	/**
	 * Deprecated classes configuration cache
	 * Using WeakMap to allow garbage collection of unused class configs
	 *
	 * @since 3.2.0
	 *
	 * @var array
	 */
	private array $deprecated_classes_cache;

	/**
	 * Loaded class status tracking
	 *
	 * @since 3.2.0
	 *
	 * @var array<string, bool>
	 */
	private array $loaded_classes = array();

	/**
	 * Initialize the deprecated class loader with improved caching and validation
	 *
	 * @since 3.2.0
	 *
	 * @return void
	 * @throws RuntimeException If initialization fails
	 */
	public function init_deprecated_class_loader(): void {
		if ( ! defined( 'DIVI_SQUAD_PLUGIN_FILE' ) ) {
			return;
		}

		try {
			$this->deprecated_classes_cache = array();
			$this->load_deprecated_class_configs();
		} catch ( Throwable $e ) {
			$this->log_error( $e, 'Failed to initialize deprecated class loader' );
			throw new RuntimeException( 'Deprecated class loader initialization failed', 0, $e );
		}
	}

	/**
	 * Load and validate deprecated class configurations
	 *
	 * @since 3.2.0
	 *
	 * @return void
	 */
	private function load_deprecated_class_configs(): void {
		$base_classes = $this->get_deprecated_classes_list();

		/**
		 * Filters the list of deprecated classes before validation and registration.
		 *
		 * Allows modification of the deprecated classes configuration before they are processed.
		 * This can be used to add, remove, or modify deprecated class configurations.
		 *
		 * @since 3.2.0
		 *
		 * @param array         $base_classes Array of deprecated class configurations. Key is the full class name, value is the configuration array.
		 * @param SquadModules $this         Current plugin instance.
		 */
		$filtered_classes = apply_filters( 'divi_squad_deprecated_classes', $base_classes, $this );

		foreach ( $filtered_classes as $class_name => $config ) {
			if ( $this->validate_class_config( $class_name, $config ) ) {
				$this->register_deprecated_class( $class_name, $config );
			}
		}
	}

	/**
	 * Validate deprecated class configuration
	 *
	 * @since 3.2.0
	 *
	 * @param string $class_name Class name to validate
	 * @param array  $config     Configuration to validate
	 *
	 * @return bool Whether configuration is valid
	 */
	private function validate_class_config( string $class_name, array $config ): bool {
		if ( class_exists( $class_name ) || interface_exists( $class_name ) ) {
			return false;
		}

		$file_path = $this->get_deprecated_class_path( $class_name );
		if ( ! file_exists( $file_path ) ) {
			$this->log_error(
				new RuntimeException( "Deprecated class file not found: $file_path" ),
				'Class validation failed'
			);
			return false;
		}

		if ( isset( $config['action'] ) ) {
			if ( ! isset( $config['action']['name'] ) || ! is_string( $config['action']['name'] ) ) {
				$this->log_error(
					new RuntimeException( "Invalid action configuration for class: $class_name" ),
					'Hook validation failed'
				);
				return false;
			}
		}

		return true;
	}

	/**
	 * Register a deprecated class for lazy loading
	 *
	 * @since 3.2.0
	 *
	 * @param string $class_name Full class name
	 * @param array  $config     Class configuration
	 *
	 * @return void
	 */
	private function register_deprecated_class( string $class_name, array $config ): void {
		$file_path = $this->get_deprecated_class_path( $class_name );

		$this->deprecated_classes_cache[ $class_name ] = array(
			'file_path' => $file_path,
			'config'    => $this->normalize_class_config( $config ),
			'loaded'    => false,
		);

		if ( ! isset( $config['action'] ) ) {
			spl_autoload_register(
				function ( $class ) use ( $class_name, $file_path ) {
					if ( $class === $class_name && ! isset( $this->loaded_classes[ $class_name ] ) ) {
						$this->load_class_file( $class_name, $file_path );
					}
				}
			);
			return;
		}

		$this->schedule_class_loading( $class_name, $config );
	}

	/**
	 * Schedule class loading via WordPress hook
	 *
	 * @since 3.2.0
	 *
	 * @param string $class_name Class to schedule
	 * @param array  $config     Loading configuration
	 *
	 * @return void
	 */
	private function schedule_class_loading( string $class_name, array $config ): void {
		/**
		 * Filters the priority for loading a deprecated class.
		 *
		 * Allows modification of the loading priority for deprecated classes that are
		 * loaded via WordPress hooks.
		 *
		 * @since 3.2.0
		 *
		 * @param int    $priority   The priority for the action hook (default: 10).
		 * @param string $class_name The full class name being loaded.
		 */
		$priority = apply_filters(
			'divi_squad_deprecated_class_loading_priority',
			$config['action']['priority'] ?? 10,
			$class_name
		);

		add_action(
			$config['action']['name'],
			function () use ( $class_name ) {
				$this->load_scheduled_class( $class_name );
			},
			$priority
		);
	}

	/**
	 * Normalize class configuration with defaults
	 *
	 * @since 3.2.0
	 *
	 * @param array $config Raw configuration
	 *
	 * @return array Normalized configuration
	 */
	private function normalize_class_config( array $config ): array {
		return array_merge(
			array(
				'priority'  => 10,
				'condition' => null,
				'callbacks' => array(
					'before' => null,
					'after'  => null,
				),
			),
			$config
		);
	}

	/**
	 * Load a class file with proper error handling
	 *
	 * @since 3.2.0
	 *
	 * @param string $class_name Class to load
	 * @param string $file_path  File path to load
	 *
	 * @return bool Whether loading succeeded
	 */
	private function load_class_file( string $class_name, string $file_path ): bool {
		try {
			if ( isset( $this->loaded_classes[ $class_name ] ) ) {
				return true;
			}

			require_once $file_path;
			$this->loaded_classes[ $class_name ] = true;

			return true;
		} catch ( Throwable $e ) {
			$this->log_error( $e, "Failed to load deprecated class: $class_name" );
			return false;
		}
	}

	/**
	 * Load a scheduled class with condition checking
	 *
	 * @since 3.2.0
	 *
	 * @param string $class_name Class to load
	 *
	 * @return void
	 */
	private function load_scheduled_class( string $class_name ): void {
		if ( ! isset( $this->deprecated_classes_cache[ $class_name ] ) ) {
			return;
		}

		$cache  = $this->deprecated_classes_cache[ $class_name ];
		$config = $cache['config'];

		if ( $config['condition'] && ! $this->evaluate_condition( $config['condition'], $class_name ) ) {
			return;
		}

		$this->execute_callback( $config['callbacks']['before'] ?? null, $class_name );
		$this->load_class_file( $class_name, $cache['file_path'] );
		$this->execute_callback( $config['callbacks']['after'] ?? null, $class_name );
	}

	/**
	 * Evaluate a loading condition
	 *
	 * @since 3.2.0
	 * @param callable|array $condition Condition to evaluate
	 * @param string         $class_name Context class name
	 * @return bool Whether condition passes
	 */
	private function evaluate_condition( $condition, string $class_name ): bool {
		if ( is_callable( $condition ) ) {
			return (bool) $condition( $class_name );
		}

		if ( isset( $condition['callback'] ) && is_callable( $condition['callback'] ) ) {
			$args = $condition['args'] ?? array( $class_name );
			return (bool) call_user_func_array( $condition['callback'], $args );
		}

		return true;
	}

	/**
	 * Execute a callback if valid
	 *
	 * @since 3.2.0
	 *
	 * @param callable|null $callback   Callback to execute
	 * @param string        $class_name Context class name
	 *
	 * @return void
	 */
	private function execute_callback( ?callable $callback, string $class_name ): void {
		if ( is_callable( $callback ) ) {
			$callback( $class_name );
		}
	}

	/**
	 * Get the file path for a deprecated class.
	 *
	 * @since 3.1.1
	 *
	 * @param string $class_name The full class name.
	 *
	 * @return string Absolute path to the deprecated class file
	 */
	protected function get_deprecated_class_path( string $class_name ): string {
		$relative_path = str_replace(
			array( 'DiviSquad\\', '\\' ),
			array( '', DIRECTORY_SEPARATOR ),
			$class_name
		);

		return $this->get_path( "/deprecated/$relative_path.php" );
	}
}
