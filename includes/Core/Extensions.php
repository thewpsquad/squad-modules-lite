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

use DiviSquad\Core\Contracts\Hookable;
use DiviSquad\Extensions\Abstracts\Base_Extension;
use DiviSquad\Utils\WP as WPUtil;
use Throwable;

/**
 * Core Extensions Manager
 *
 * @since   3.3.0
 * @package DiviSquad
 */
class Extensions implements Hookable {

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

		$this->register_hooks();
	}

	/**
	 * Initialize the extension manager
	 *
	 * @since 3.3.0
	 *
	 * @return void
	 */
	public function init(): void {
		try {
			if ( $this->initialized ) {
				return;
			}

			/**
			 * Filter whether to initialize the extensions manager.
			 *
			 * @since 3.4.1
			 *
			 * @param bool $should_init Whether to initialize the manager.
			 * @param self $extensions  Current extensions manager instance.
			 */
			$should_init = apply_filters( 'divi_squad_extensions_should_init', true, $this );

			if ( ! $should_init ) {
				return;
			}

			// Load extension data.
			$this->load_extension_data();

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
	 *
	 * Implements the Hookable interface by registering all necessary
	 * WordPress hooks for extension initialization and loading.
	 *
	 * @since 3.3.0
	 * @return void
	 */
	public function register_hooks(): void {
		try {
			add_action( 'wp_loaded', array( $this, 'load_extensions' ), 999 );

			/**
			 * Fires after hooks are registered in the Extensions Manager.
			 *
			 * @since 3.4.1
			 *
			 * @param self $extensions Current extensions manager instance.
			 */
			do_action( 'divi_squad_extensions_hooks_registered', $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to register extension hooks' );
		}
	}

	/**
	 * Load extension data from storage
	 *
	 * @since 3.3.0
	 *
	 * @return void
	 */
	private function load_extension_data(): void {
		try {
			/**
			 * Filter whether to load extension data.
			 *
			 * @since 3.4.1
			 *
			 * @param bool $should_load Whether to load extension data.
			 * @param self $extensions  Current extensions manager instance.
			 */
			$should_load = apply_filters( 'divi_squad_extensions_data_should_load', true, $this );

			if ( ! $should_load ) {
				return;
			}

			// Load the registered extensions list.
			$this->registered_extensions = $this->get_registered_list();

			// Retrieve stored active extensions.
			$this->active_extensions = (array) $this->memory->get( 'active_extensions', array() );

			// Retrieve stored inactive extensions.
			$this->inactive_extensions = (array) $this->memory->get( 'inactive_extensions', array() );

			// If no active extensions stored yet, use defaults.
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

			/**
			 * Fires after extension data is loaded.
			 *
			 * @since 3.4.1
			 *
			 * @param self $extensions Current extensions manager instance.
			 */
			do_action( 'divi_squad_extensions_data_loaded', $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to load extension data' );
		}
	}

	/**
	 * Get list of all available extensions
	 *
	 * @since 3.3.0
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
	 * @since 3.3.0
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_default_registries(): array {
		try {
			$default_registries = $this->filter_extensions(
				function ( $extension ) {
					return $extension['is_default_active'];
				}
			);

			/**
			 * Filter default extension registries.
			 *
			 * @since 3.4.1
			 *
			 * @param array<string, array<string, mixed>> $default_registries List of default extension data.
			 * @param self                                $extensions         Current extensions manager instance.
			 */
			return apply_filters( 'divi_squad_default_extension_registries', $default_registries, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to get default extensions registries' );

			return array();
		}
	}

	/**
	 * Get all active extensions
	 *
	 * @since 3.3.0
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_active_registries(): array {
		try {
			$active_registries = $this->filter_extensions(
				function ( $extension ) {
					return in_array( $extension['name'], $this->active_extensions, true );
				}
			);

			/**
			 * Filter active extension registries.
			 *
			 * @since 3.4.1
			 *
			 * @param array<string, array<string, mixed>> $active_registries List of active extension data.
			 * @param self                                $extensions        Current extensions manager instance.
			 */
			return apply_filters( 'divi_squad_active_extension_registries', $active_registries, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to get active extensions registries' );

			return array();
		}
	}

	/**
	 * Get all inactive extensions
	 *
	 * @since 3.3.0
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_inactive_registries(): array {
		try {
			$inactive_registries = $this->filter_extensions(
				function ( $extension ) {
					return ! $extension['is_default_active'];
				}
			);

			/**
			 * Filter inactive extension registries.
			 *
			 * @since 3.4.1
			 *
			 * @param array<string, array<string, mixed>> $inactive_registries List of inactive extension data.
			 * @param self                                $extensions          Current extensions manager instance.
			 */
			return apply_filters( 'divi_squad_inactive_extension_registries', $inactive_registries, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to get inactive extensions registries' );

			return array();
		}
	}

	/**
	 * Filter extensions based on callback
	 *
	 * @since 3.3.0
	 *
	 * @param callable $callback Function to filter extensions.
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
	 * @since 3.3.0
	 *
	 * @param string $extension_name Extension name.
	 *
	 * @return bool Whether the extension is active
	 */
	public function is_extension_active( string $extension_name ): bool {
		try {
			$is_active = in_array( $extension_name, $this->active_extensions, true );

			/**
			 * Filter whether an extension is active.
			 *
			 * @since 3.4.1
			 *
			 * @param bool   $is_active      Whether the extension is active.
			 * @param string $extension_name Extension name.
			 * @param self   $extensions     Current extensions manager instance.
			 */
			return apply_filters( 'divi_squad_extension_is_active', $is_active, $extension_name, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, sprintf( 'Failed to check if extension is active: %s', $extension_name ) );

			return false;
		}
	}

	/**
	 * Check if an extension is active by class name
	 *
	 * @since 3.3.0
	 *
	 * @param string $class_name Extension class name.
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

			/**
			 * Filter whether an extension is active by class name.
			 *
			 * @since 3.4.1
			 *
			 * @param bool   $is_active  Whether the extension is active.
			 * @param string $class_name Extension class name.
			 * @param self   $extensions Current extensions manager instance.
			 */
			return apply_filters( 'divi_squad_extension_is_active_by_class', false, $class_name, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, sprintf( 'Failed to check if extension is active by class: %s', $class_name ) );

			return false;
		}
	}

	/**
	 * Load enabled extensions
	 *
	 * @since 3.3.0
	 *
	 * @return void
	 */
	public function load_extensions(): void {
		try {
			/**
			 * Filter whether to load extensions.
			 *
			 * @since 3.4.1
			 *
			 * @param bool $should_load Whether to load extensions.
			 * @param self $extensions  Current extensions manager instance.
			 */
			$should_load = apply_filters( 'divi_squad_extensions_should_load', true, $this );

			if ( ! $should_load || ! class_exists( Base_Extension::class ) ) {
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
			 * @param array<string, array<string, mixed>> $active_extensions List of active extension data
			 * @param array<string>                       $active_plugins    List of active plugin slugs
			 * @param self                                $extensions        Extensions manager instance
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
	 * @since 3.3.0
	 *
	 * @param array<string, mixed> $extension Extension configuration.
	 *
	 * @return void
	 */
	private function load_extension_class( array $extension ): void {
		try {
			// Skip if no root class defined.
			if ( ! isset( $extension['classes']['root_class'] ) ) {
				return;
			}

			$class_name = $extension['classes']['root_class'];

			/**
			 * Filter the extension class name before loading.
			 *
			 * @since 3.4.1
			 *
			 * @param string               $class_name Extension class name.
			 * @param array<string, mixed> $extension  Extension configuration.
			 * @param self                 $extensions Current extensions manager instance.
			 */
			$class_name = apply_filters( 'divi_squad_extension_class_name', $class_name, $extension, $this );

			// Skip if class doesn't exist.
			if ( ! class_exists( $class_name ) ) {
				return;
			}

			/**
			 * Fires before an extension class is loaded.
			 *
			 * @since 3.4.1
			 *
			 * @param string               $class_name Extension class name.
			 * @param array<string, mixed> $extension  Extension configuration.
			 * @param self                 $extensions Current extensions manager instance.
			 */
			do_action( 'divi_squad_before_extension_class_load', $class_name, $extension, $this );

			// Initialize the extension.
			$extension_instance = new $class_name();

			/**
			 * Fires after an extension class is loaded.
			 *
			 * @since 3.4.1
			 *
			 * @param object               $extension_instance Extension instance.
			 * @param array<string, mixed> $extension          Extension configuration.
			 * @param self                 $extensions         Current extensions manager instance.
			 */
			do_action( 'divi_squad_extension_class_loaded', $extension_instance, $extension, $this );
		} catch ( Throwable $e ) {
			$extension_name = $extension['name'] ?? 'unknown';
			divi_squad()->log_error( $e, sprintf( 'Failed to load extension class: %s', $extension_name ) );
		}
	}

	/**
	 * Verify plugin requirements for an extension
	 *
	 * @since 3.3.0
	 *
	 * @param array<string, mixed> $extension      Extension configuration.
	 * @param array<string>        $active_plugins List of active plugin slugs.
	 *
	 * @return bool Whether requirements are met
	 */
	protected function verify_requirements( array $extension, array $active_plugins ): bool {
		try {
			$requirements_met = true;

			// If no requirements, extension is valid.
			if ( ! isset( $extension['required'] ) ) {
				return $requirements_met;
			}

			// Check plugin requirements.
			if ( isset( $extension['required']['plugin'] ) ) {
				$required_plugins = $extension['required']['plugin'];

				// Single plugin requirement.
				if ( is_string( $required_plugins ) ) {
					// Check for multiple options (plugin1|plugin2).
					if ( strpos( $required_plugins, '|' ) !== false ) {
						$plugin_options = explode( '|', $required_plugins );

						// At least one plugin must be active.
						$requirements_met = false;
						foreach ( $plugin_options as $plugin ) {
							if ( in_array( $plugin, $active_plugins, true ) ) {
								$requirements_met = true;
								break;
							}
						}
					} else {
						// Single plugin must be active.
						$requirements_met = in_array( $required_plugins, $active_plugins, true );
					}
				}

				// Multiple required plugins (all must be active).
				if ( is_array( $required_plugins ) ) {
					foreach ( $required_plugins as $plugin ) {
						if ( ! in_array( $plugin, $active_plugins, true ) ) {
							$requirements_met = false;
							break;
						}
					}
				}
			}

			/**
			 * Filter whether an extension meets requirements.
			 *
			 * @since 3.4.1
			 *
			 * @param bool                 $requirements_met Whether requirements are met.
			 * @param array<string, mixed> $extension        Extension configuration.
			 * @param array<string>        $active_plugins   List of active plugin slugs.
			 * @param self                 $extensions       Current extensions manager instance.
			 */
			return apply_filters( 'divi_squad_extension_requirements_met', $requirements_met, $extension, $active_plugins, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to verify extension requirements' );

			return false;
		}
	}

	/**
	 * Enable an extension
	 *
	 * @since 3.3.0
	 *
	 * @param string $extension_name Extension name.
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

			/**
			 * Filter whether to enable an extension.
			 *
			 * @since 3.4.1
			 *
			 * @param bool   $should_enable  Whether to enable the extension.
			 * @param string $extension_name Extension name.
			 * @param self   $extensions     Current extensions manager instance.
			 */
			$should_enable = apply_filters( 'divi_squad_extension_should_enable', true, $extension_name, $this );

			if ( ! $should_enable ) {
				return false;
			}

			$this->active_extensions[] = $extension_name;
			$this->memory->set( self::ACTIVE_EXTENSIONS_KEY, $this->active_extensions );

			$extension_data = $this->registered_extensions[ $extension_name ] ?? array();

			/**
			 * Fires when an extension is enabled
			 *
			 * @since 3.4.0
			 *
			 * @param string               $extension_name Extension name
			 * @param array<string, mixed> $extension_data Extension configuration
			 * @param self                 $extensions     Extensions manager instance
			 */
			do_action( 'divi_squad_extension_enabled', $extension_name, $extension_data, $this );

			return true;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, sprintf( 'Failed to enable extension: %s', $extension_name ) );

			return false;
		}
	}

	/**
	 * Disable an extension
	 *
	 * @since 3.3.0
	 *
	 * @param string $extension_name Extension name.
	 *
	 * @return bool Whether the extension was disabled
	 */
	public function disable_extension( string $extension_name ): bool {
		try {
			if ( ! $this->is_extension_active( $extension_name ) ) {
				return true;
			}

			/**
			 * Filter whether to disable an extension.
			 *
			 * @since 3.4.1
			 *
			 * @param bool   $should_disable Whether to disable the extension.
			 * @param string $extension_name Extension name.
			 * @param self   $extensions     Current extensions manager instance.
			 */
			$should_disable = apply_filters( 'divi_squad_extension_should_disable', true, $extension_name, $this );

			if ( ! $should_disable ) {
				return false;
			}

			$index = array_search( $extension_name, $this->active_extensions, true );

			if ( false === $index ) {
				return false;
			}

			$extension_data = $this->registered_extensions[ $extension_name ] ?? array();

			array_splice( $this->active_extensions, (int) $index, 1 );

			if ( ! in_array( $extension_name, $this->inactive_extensions, true ) ) {
				$this->inactive_extensions[] = $extension_name;
			}

			$this->memory->set( self::ACTIVE_EXTENSIONS_KEY, $this->active_extensions );
			$this->memory->set( self::INACTIVE_EXTENSIONS_KEY, $this->inactive_extensions );

			/**
			 * Fires when an extension is disabled
			 *
			 * @since 3.4.1
			 *
			 * @param string               $extension_name Extension name
			 * @param array<string, mixed> $extension_data Extension configuration
			 * @param self                 $extensions     Extensions manager instance
			 */
			do_action( 'divi_squad_extension_disabled', $extension_name, $extension_data, $this );

			return true;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, sprintf( 'Failed to disable extension: %s', $extension_name ) );

			return false;
		}
	}

	/**
	 * Get extension info by name
	 *
	 * @since 3.3.0
	 *
	 * @param string $extension_name Extension name.
	 *
	 * @return array<string, mixed>|null Extension data or null if not found
	 */
	public function get_extension_info( string $extension_name ): ?array {
		$extension_info = $this->registered_extensions[ $extension_name ] ?? null;

		/**
		 * Filter extension info data.
		 *
		 * @since 3.4.1
		 *
		 * @param array<string, mixed>|null $extension_info Extension data or null.
		 * @param string                    $extension_name Extension name.
		 * @param self                      $extensions     Current extensions manager instance.
		 */
		return apply_filters( 'divi_squad_extension_info', $extension_info, $extension_name, $this );
	}

	/**
	 * Get extension categories
	 *
	 * @since 3.3.0
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

		/**
		 * Filter extension categories.
		 *
		 * @since 3.4.1
		 *
		 * @param array<string, string> $categories Categories with their titles.
		 * @param self                  $extensions Current extensions manager instance.
		 */
		return apply_filters( 'divi_squad_extension_categories', $categories, $this );
	}

	/**
	 * Reset extensions to default state
	 *
	 * @since 3.3.0
	 * @return bool Success status
	 */
	public function reset_to_default(): bool {
		try {
			/**
			 * Filter whether to reset extensions to default.
			 *
			 * @since 3.4.1
			 *
			 * @param bool $should_reset Whether to reset extensions.
			 * @param self $extensions   Current extensions manager instance.
			 */
			$should_reset = apply_filters( 'divi_squad_extensions_should_reset', true, $this );

			if ( ! $should_reset ) {
				return false;
			}

			$default_extensions      = $this->get_default_registries();
			$this->active_extensions = array_column( $default_extensions, 'name' );

			$all_extension_names       = array_column( $this->registered_extensions, 'name' );
			$this->inactive_extensions = array_values( array_diff( $all_extension_names, $this->active_extensions ) );

			$this->memory->set( self::ACTIVE_EXTENSIONS_KEY, $this->active_extensions );
			$this->memory->set( self::INACTIVE_EXTENSIONS_KEY, $this->inactive_extensions );
			$this->memory->set( self::EXTENSION_VERSION_KEY, divi_squad()->get_version() );

			/**
			 * Fires after extensions are reset to default
			 *
			 * @since 3.4.1
			 *
			 * @param array<string> $active_extensions   List of active extension names
			 * @param array<string> $inactive_extensions List of inactive extension names
			 * @param self          $extensions          Extensions manager instance
			 */
			do_action( 'divi_squad_extensions_reset', $this->active_extensions, $this->inactive_extensions, $this );

			return true;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to reset extensions to default' );

			return false;
		}
	}

	/**
	 * Check if an extension class exists and is loadable
	 *
	 * @since 3.3.0
	 *
	 * @param string $extension_name Extension name.
	 *
	 * @return bool Whether the extension class exists and can be loaded
	 */
	public function extension_class_exists( string $extension_name ): bool {
		try {
			$extension = $this->get_extension_info( $extension_name );

			if ( null === $extension || ! isset( $extension['classes']['root_class'] ) ) {
				return false;
			}

			$class_exists = class_exists( $extension['classes']['root_class'] );

			/**
			 * Filter whether an extension class exists.
			 *
			 * @since 3.4.1
			 *
			 * @param bool   $class_exists   Whether the class exists.
			 * @param string $extension_name Extension name.
			 * @param self   $extensions     Current extensions manager instance.
			 */
			return apply_filters( 'divi_squad_extension_class_exists', $class_exists, $extension_name, $this );
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
	 * @param array<string, array<string, mixed>> $registries The extension registries.
	 * @param string                              $type       Registry type ('active', 'inactive', 'default').
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
			 * @param array<string, array<string, mixed>> $registries The extension registries
			 * @param string                              $type       Registry type ('active', 'inactive', 'default')
			 * @param self                                $instance   Extensions manager instance
			 *
			 * @return array<string, array<string, mixed>> Modified registries
			 */
			return apply_filters( "divi_squad_{$type}_extension_registries", $result, $type, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, sprintf( 'Failed to filter %s extension registries', $type ) );

			return array();
		}
	}

	/**
	 * Retrieve all extension data including active state
	 *
	 * @since 3.4.1
	 *
	 * @return array<string, array<string, mixed>> All extensions with their active state
	 */
	public function get_all_extensions_data(): array {
		try {
			$extensions_data = array();

			foreach ( $this->registered_extensions as $name => $extension ) {
				$extensions_data[ $name ]              = $extension;
				$extensions_data[ $name ]['is_active'] = $this->is_extension_active( $name );
			}

			/**
			 * Filter all extensions data.
			 *
			 * @since 3.4.1
			 *
			 * @param array<string, array<string, mixed>> $extensions_data All extensions data.
			 * @param self                                $extensions      Current extensions manager instance.
			 */
			return apply_filters( 'divi_squad_all_extensions_data', $extensions_data, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to get all extensions data' );

			return array();
		}
	}

	/**
	 * Check if the extensions manager is initialized
	 *
	 * @since 3.4.1
	 *
	 * @return bool Whether the extensions manager is initialized
	 */
	public function is_initialized(): bool {
		return $this->initialized;
	}

	/**
	 * Set active extensions programmatically
	 *
	 * @since 3.4.1
	 *
	 * @param array<string> $extensions List of extension names to set as active.
	 *
	 * @return bool Success status
	 */
	public function set_active_extensions( array $extensions ): bool {
		try {
			/**
			 * Filter extensions to be set as active.
			 *
			 * @since 3.4.1
			 *
			 * @param array<string> $extensions List of extension names.
			 * @param self          $manager    Current extensions manager instance.
			 */
			$extensions = (array) apply_filters( 'divi_squad_set_active_extensions', $extensions, $this );

			// Validate extensions exist.
			$valid_extensions = array();
			foreach ( $extensions as $extension_name ) {
				if ( isset( $this->registered_extensions[ $extension_name ] ) ) {
					$valid_extensions[] = $extension_name;
				}
			}

			$this->active_extensions = $valid_extensions;
			$this->memory->set( self::ACTIVE_EXTENSIONS_KEY, $this->active_extensions );

			// Update inactive extensions.
			$all_extension_names       = array_column( $this->registered_extensions, 'name' );
			$this->inactive_extensions = array_values( array_diff( $all_extension_names, $this->active_extensions ) );
			$this->memory->set( self::INACTIVE_EXTENSIONS_KEY, $this->inactive_extensions );

			/**
			 * Fires after active extensions are set
			 *
			 * @since 3.4.1
			 *
			 * @param array<string> $active_extensions   New active extensions
			 * @param array<string> $inactive_extensions New inactive extensions
			 * @param self          $extensions          Extensions manager instance
			 */
			do_action( 'divi_squad_active_extensions_set', $this->active_extensions, $this->inactive_extensions, $this );

			return true;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to set active extensions' );

			return false;
		}
	}
}
