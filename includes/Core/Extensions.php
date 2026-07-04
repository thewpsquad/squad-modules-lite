<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Extensions Manager Class
 *
 * Handles registration, management, and loading of all plugin extensions
 * that enhance functionality beyond standard Divi modules.
 *
 * @since   3.3.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Core;

use DiviSquad\Extensions\Extension;
use DiviSquad\Utils\WP as WPUtil;
use Throwable;

/**
 * Core Extensions Manager
 *
 * @since 3.3.0
 */
class Extensions {

	/**
	 * Store all registered extensions.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $registered_extensions = array();

	/**
	 * Store all active extensions.
	 *
	 * @var array<string>
	 */
	private array $active_extensions = array();

	/**
	 * Store all inactive extensions.
	 *
	 * @var array<string>
	 */
	private array $inactive_extensions = array();

	/**
	 * Initialization flag.
	 *
	 * @var bool
	 */
	private bool $initialized = false;

	/**
	 * Memory instance for extension state persistence.
	 *
	 * @var Memory
	 */
	private Memory $memory;

	/**
	 * Constants for memory storage keys
	 */
	public const ACTIVE_EXTENSIONS_KEY   = 'active_extensions';
	public const INACTIVE_EXTENSIONS_KEY = 'inactive_extensions';
	public const EXTENSION_VERSION_KEY   = 'extension_version';

	/**
	 * Initialize the extensions manager
	 */
	public function __construct() {
		$this->memory = divi_squad()->memory;
	}

	/**
	 * Initialize the extension manager+
	 */
	public function init(): void {
		try {
			if ( $this->initialized ) {
				return;
			}

			// Load extension data
			$this->load_extension_data();

			// Register hooks
			$this->register_hooks();

			$this->initialized = true;

			/**
			 * Fires after extensions manager is initialized
			 *
			 * @since 3.3.0
			 *
			 * @param self $extensions Current extensions manager instance
			 */
			do_action( 'divi_squad_extensions_init', $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to initialize extensions manager' );
		}
	}

	/**
	 * Register WordPress hooks
	 */
	private function register_hooks(): void {
		// Hook to load extensions
		add_action( 'wp_loaded', array( $this, 'load_extensions' ), 999 );
	}

	/**
	 * Load extension data from storage
	 */
	private function load_extension_data(): void {
		try {
			// Load the registered extensions list
			$this->registered_extensions = $this->get_registered_list();

			// Retrieve stored active extensions
			$this->active_extensions = (array) $this->memory->get( 'active_extensions', array() );

			// Retrieve stored inactive extensions
			$this->inactive_extensions = (array) $this->memory->get( 'inactive_extensions', array() );

			// If no active extensions stored yet, use defaults
			if ( count( $this->active_extensions ) === 0 ) {
				$this->active_extensions = array_column( $this->get_default_registries(), 'name' );
			}

			/**
			 * Filter active extensions after loading
			 *
			 * @since 3.3.0
			 *
			 * @param array<string> $active_extensions List of active extension names
			 * @param self          $extensions        Current extensions manager instance
			 */
			$this->active_extensions = apply_filters( 'divi_squad_active_extensions', $this->active_extensions, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to load extension data' );
		}
	}

	/**
	 * Get list of all available extensions
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_registered_list(): array {
		try {
			/**
			 * Filter registered extensions list
			 *
			 * @since 3.3.0
			 *
			 * @param array<string, array<string, mixed>> $extensions         List of extension data
			 * @param self                                $extensions_manager Current extensions manager instance
			 */
			return apply_filters( 'divi_squad_registered_extensions', array(), $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to get registered extensions list' );

			return array();
		}
	}

	/**
	 * Get default active extensions
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_default_registries(): array {
		try {
			return $this->filter_extensions(
				function ( $extension ) {
					return $extension['is_default_active'];
				}
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to get default extensions registries' );

			return array();
		}
	}

	/**
	 * Get all active extensions
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_active_registries(): array {
		try {
			return $this->filter_extensions(
				function ( $extension ) {
					return in_array( $extension['name'], $this->active_extensions, true );
				}
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to get active extensions registries' );

			return array();
		}
	}

	/**
	 * Get all inactive extensions
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_inactive_registries(): array {
		try {
			return $this->filter_extensions(
				function ( $extension ) {
					return ! $extension['is_default_active'];
				}
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to get inactive extensions registries' );

			return array();
		}
	}

	/**
	 * Filter extensions based on callback
	 *
	 * @param callable $callback Function to filter extensions
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function filter_extensions( callable $callback ): array {
		try {
			return array_filter(
				$this->registered_extensions,
				static function ( $extension ) use ( $callback ) {
					return $callback( $extension );
				}
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to filter extensions' );

			return array();
		}
	}

	/**
	 * Check if an extension is active
	 *
	 * @param string $extension_name Extension name
	 *
	 * @return bool Whether the extension is active
	 */
	public function is_extension_active( string $extension_name ): bool {
		try {
			return in_array( $extension_name, $this->active_extensions, true );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, sprintf( 'Failed to check if extension is active: %s', $extension_name ) );

			return false;
		}
	}

	/**
	 * Check if an extension is active by class name
	 *
	 * @param string $class_name Extension class name
	 *
	 * @return bool Whether the extension is active
	 */
	public function is_extension_active_by_class( string $class_name ): bool {
		try {
			foreach ( $this->registered_extensions as $extension ) {
				if (
					'' !== ( $extension['classes']['root_class'] ) &&
					$extension['classes']['root_class'] === $class_name &&
					$this->is_extension_active( $extension['name'] )
				) {
					return true;
				}
			}

			return false;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, sprintf( 'Failed to check if extension is active by class: %s', $class_name ) );

			return false;
		}
	}

	/**
	 * Load enabled extensions
	 */
	public function load_extensions(): void {
		try {
			if ( ! class_exists( Extension::class ) ) {
				return;
			}

			$active_extensions = $this->get_active_registries();
			$active_plugins    = array_column( WPUtil::get_active_plugins(), 'slug' );

			/**
			 * Fires before extensions are loaded
			 *
			 * This hook allows developers to perform actions before extensions are loaded,
			 * such as registering custom extension dependencies or modifying the environment.
			 *
			 * @since 3.4.0
			 *
			 * @param array<string>                       $active_plugins    List of active plugin slugs
			 * @param self                                $extensions        Extensions manager instance
			 *
			 * @param array<string, array<string, mixed>> $active_extensions List of active extension data
			 */
			do_action( 'divi_squad_before_extensions_load', $active_extensions, $active_plugins, $this );
			foreach ( $active_extensions as $extension ) {
				if ( ! $this->verify_requirements( $extension, $active_plugins ) ) {
					continue;
				}

				$this->load_extension_class( $extension );
			}

			/**
			 * Fires after extensions are loaded
			 *
			 * @since 3.3.0
			 *
			 * @param array<string, array<string, mixed>> $active_extensions List of active extension data
			 * @param self                                $extensions        Extensions manager instance
			 */
			do_action( 'divi_squad_extensions_loaded', $active_extensions, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to load extensions' );
		}
	}

	/**
	 * Load extension class
	 *
	 * @param array<string, mixed> $extension Extension configuration
	 */
	private function load_extension_class( array $extension ): void {
		try {
			// Skip if no root class defined
			if ( ! isset( $extension['classes']['root_class'] ) ) {
				return;
			}

			$class_name = $extension['classes']['root_class'];

			// Skip if class doesn't exist
			if ( ! class_exists( $class_name ) ) {
				return;
			}

			// Initialize the extension
			new $class_name();
		} catch ( Throwable $e ) {
			$extension_name = $extension['name'] ?? 'unknown';
			divi_squad()->log_error( $e, sprintf( 'Failed to load extension class: %s', $extension_name ) );
		}
	}

	/**
	 * Verify plugin requirements for an extension
	 *
	 * @param array<string, mixed> $extension      Extension configuration
	 * @param array<string>        $active_plugins List of active plugin slugs
	 *
	 * @return bool Whether requirements are met
	 */
	protected function verify_requirements( array $extension, array $active_plugins ): bool {
		try {
			// If no requirements, extension is valid
			if ( ! isset( $extension['required'] ) ) {
				return true;
			}

			// Check plugin requirements
			if ( isset( $extension['required']['plugin'] ) ) {
				$required_plugins = $extension['required']['plugin'];

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
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to verify extension requirements' );

			return false;
		}
	}

	/**
	 * Enable an extension
	 *
	 * @param string $extension_name Extension name
	 *
	 * @return bool Whether the extension was enabled
	 */
	public function enable_extension( string $extension_name ): bool {
		try {
			if ( $this->is_extension_active( $extension_name ) ) {
				return true;
			}

			if ( ! isset( $this->registered_extensions[ $extension_name ] ) ) {
				return false;
			}

			$this->active_extensions[] = $extension_name;
			$this->memory->set( self::ACTIVE_EXTENSIONS_KEY, $this->active_extensions );

			/**
			 * Add an action hook for when an extension is enabled
			 *
			 * @since 3.4.0
			 *
			 * @param array  $extension_data The extension configuration data
			 * @param self   $instance       The Extensions manager instance
			 *
			 * @param string $extension_name The name of the extension being enabled
			 */
			do_action( 'divi_squad_extension_enabled', $extension_name, $this->registered_extensions[ $extension_name ] ?? array(), $this );

			return true;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, sprintf( 'Failed to enable extension: %s', $extension_name ) );

			return false;
		}
	}

	/**
	 * Disable an extension
	 *
	 * @param string $extension_name Extension name
	 *
	 * @return bool Whether the extension was disabled
	 */
	public function disable_extension( string $extension_name ): bool {
		try {
			if ( ! $this->is_extension_active( $extension_name ) ) {
				return true;
			}

			$index = array_search( $extension_name, $this->active_extensions, true );

			if ( false === $index ) {
				return false;
			}

			array_splice( $this->active_extensions, (int) $index, 1 );

			if ( ! in_array( $extension_name, $this->inactive_extensions, true ) ) {
				$this->inactive_extensions[] = $extension_name;
			}

			$this->memory->set( self::ACTIVE_EXTENSIONS_KEY, $this->active_extensions );
			$this->memory->set( self::INACTIVE_EXTENSIONS_KEY, $this->inactive_extensions );

			return true;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, sprintf( 'Failed to disable extension: %s', $extension_name ) );

			return false;
		}
	}

	/**
	 * Get extension info by name
	 *
	 * @param string $extension_name Extension name
	 *
	 * @return array<string, mixed>|null Extension data or null if not found
	 */
	public function get_extension_info( string $extension_name ): ?array {
		return $this->registered_extensions[ $extension_name ] ?? null;
	}

	/**
	 * Get extension categories
	 *
	 * @return array<string, string> Categories with their titles
	 */
	public function get_extension_categories(): array {
		$categories = array();

		foreach ( $this->registered_extensions as $extension ) {
			if ( isset( $extension['category'], $extension['category_title'] ) ) {
				$categories[ $extension['category'] ] = $extension['category_title'];
			}
		}

		return $categories;
	}

	/**
	 * Reset extensions to default state
	 *
	 * @return bool Success status
	 */
	public function reset_to_default(): bool {
		try {
			$default_extensions      = $this->get_default_registries();
			$this->active_extensions = array_column( $default_extensions, 'name' );

			$all_extension_names       = array_column( $this->registered_extensions, 'name' );
			$this->inactive_extensions = array_values( array_diff( $all_extension_names, $this->active_extensions ) );

			$this->memory->set( self::ACTIVE_EXTENSIONS_KEY, $this->active_extensions );
			$this->memory->set( self::INACTIVE_EXTENSIONS_KEY, $this->inactive_extensions );
			$this->memory->set( self::EXTENSION_VERSION_KEY, divi_squad()->get_version() );

			return true;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to reset extensions to default' );

			return false;
		}
	}

	/**
	 * Check if an extension class exists and is loadable
	 *
	 * @param string $extension_name Extension name
	 *
	 * @return bool Whether the extension class exists and can be loaded
	 */
	public function extension_class_exists( string $extension_name ): bool {
		try {
			$extension = $this->get_extension_info( $extension_name );

			if ( null === $extension || ! isset( $extension['classes']['root_class'] ) ) {
				return false;
			}

			return class_exists( $extension['classes']['root_class'] );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, sprintf( 'Failed to check if extension class exists: %s', $extension_name ) );

			return false;
		}
	}

	/**
	 * Filter a specific type of extension registries
	 *
	 * Allows developers to modify extension registries before they're used,
	 * such as adding custom data or modifying configuration.
	 *
	 * @since 3.4.0
	 *
	 * @param string                              $type       Registry type ('active', 'inactive', 'default')
	 *
	 * @param array<string, array<string, mixed>> $registries The extension registries
	 *
	 * @return array<string, array<string, mixed>> Modified registries
	 */
	private function filter_extension_registries( array $registries, string $type ): array {
		try {
			$result = $this->filter_extensions(
				function ( $extension ) use ( $registries ) {
					return in_array( $extension['name'], $registries, true );
				}
			);

			/**
			 * Filter a specific type of extension registries
			 *
			 * Allows developers to modify extension registries before they're used,
			 * such as adding custom data or modifying configuration.
			 *
			 * @since 3.4.0
			 *
			 * @param string                              $type       Registry type ('active', 'inactive', 'default')
			 * @param self                                $instance   Extensions manager instance
			 *
			 * @param array<string, array<string, mixed>> $registries The extension registries
			 *
			 * @return array<string, array<string, mixed>> Modified registries
			 */
			return apply_filters( "divi_squad_{$type}_extension_registries", $result, $type, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, sprintf( 'Failed to filter %s extension registries', $type ) );

			return array();
		}
	}
}
