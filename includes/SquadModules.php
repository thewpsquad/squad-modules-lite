<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Squad Modules Core Class
 *
 * This is the main plugin class that handles initialization, component loading,
 * error handling, and core functionality for the Squad Modules plugin.
 *
 * @since       1.0.0
 * @author      The WP Squad <support@squadmodules.com>
 * @copyright   2023-2025 The WP Squad (https://thewpsquad.com/)
 * @license     GPL-3.0-only
 * @link        https://squadmodules.com
 * @package     DiviSquad
 */

namespace DiviSquad;

use Freemius;
use RuntimeException;
use Throwable;

/**
 * Main Squad Modules Plugin Class
 *
 * This class handles the core functionality of the Squad Modules plugin, including:
 * - Plugin initialization and bootstrapping
 * - Component loading and management
 * - Error logging and reporting
 * - Version management
 * - Path and URL handling
 * - Pro version compatibility
 *
 * @since   1.0.0
 * @package DiviSquad
 *
 * @property Core\Distribution                       $distribution               Distribution manager.
 * @property Core\Requirements                       $requirements               Requirements checker.
 * @property Core\Memory                             $memory                     Memory manager.
 * @property Core\Cache                              $cache                      Cache manager.
 * @property Core\Assets                             $assets                     Assets Manager
 * @property Core\Error\Reporter                     $error_reporter             Error Reporter manager.
 * @property Core\Supports\Site_Health               $site_health                Site health manager.
 * @property Core\Admin\Menu                         $admin_menu                 Admin menu manager.
 * @property Core\Admin\Notice                       $admin_notice               Admin notice manager
 * @property Core\Admin\Branding                     $branding                   Branding manager.
 * @property Core\Modules                            $modules                    Module manager.
 * @property Core\Extensions                         $extensions                 Extension manger.
 * @property Core\Rest_Routes                        $rest_routes                REST API manager.
 * @property Builder\Utils\Elements\Custom_Fields    $custom_fields_element      Custom fields manager.
 * @property Builder\Utils\Elements\Forms            $forms_element              Forms manager.
 * @property Builder\Version4\Supports\Module_Helper $d4_module_helper           Module helper for Divi 4.
 */
final class SquadModules implements Core\Contracts\Hookable {

	use Core\Traits\Chainable_Container;
	use Core\Traits\Deprecations\Deprecated_Class_Loader;
	use Core\Traits\Plugin\Detect_Plugin_Life;
	use Core\Traits\Plugin\Logger;
	use Core\Traits\Plugin\Pluggable;
	use Core\Traits\Singleton;
	use Core\Traits\WP\Use_WP_Filesystem;

	/**
	 * The plugin options.
	 *
	 * @var array<string, string>
	 */
	protected array $options = array();

	/**
	 * The Plugin name.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected string $name;

	/**
	 * The Plugin Text Domain.
	 *
	 * @var string
	 */
	protected string $textdomain;

	/**
	 * The Plugin Version.
	 *
	 * @since 1.4.5
	 *
	 * @var string
	 */
	protected string $version;

	/**
	 * Admin menu slug used for the plugin's dashboard page.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected string $admin_menu_slug = 'divi_squad_dashboard';

	/**
	 * Freemius publisher instance.
	 *
	 * @since 3.2.0
	 * @var Freemius|null
	 */
	private ?Freemius $distributor = null;

	/**
	 * Initialization status tracking.
	 *
	 * Prevents circular dependencies and infinite loops during initialization.
	 *
	 * @since 3.4.0
	 * @var bool
	 */
	private static bool $is_initializing = false;

	/**
	 * Plugin Constructor
	 *
	 * Initializes the plugin by:
	 * - Setting up core plugin properties
	 * - Initializing memory management
	 * - Registering WordPress hooks
	 * - Loading deprecated class compatibility
	 *
	 * @since  3.4.0 Added circular dependency prevention
	 * @since  3.2.0 Added system requirements check
	 * @since  1.0.0 Initial implementation
	 */
	private function __construct() {
		try {
			// Set basic logger identifier immediately for early error logging
			$this->set_log_identifier( 'Squad Modules' );

			/**
			 * Action fired before plugin constructor initialization begins.
			 *
			 * @since 3.4.0
			 */
			do_action( 'divi_squad_before_constructor' );

			// Initialize core plugin components
			$this->load_initials();
			$this->register_hooks();

			/**
			 * Action fired after plugin constructor initialization completes.
			 *
			 * @since 3.4.0
			 *
			 * @param SquadModules $plugin The plugin instance.
			 */
			do_action( 'divi_squad_after_constructor', $this );
		} catch ( Throwable $e ) {
			$this->log_error( $e, 'Constructor initialization failed', false );
		}
	}

	/**
	 * Register Core WordPress Hooks
	 *
	 * Sets up all necessary WordPress action and filter hooks for the plugin.
	 *
	 * @since  3.4.0 Added improved error handling
	 * @since  3.2.0 Added notice style hooks
	 * @since  1.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		try {
			$plugin_basename = $this->get_basename();

			// Register activation and deactivation hooks.
			add_action( "activate_$plugin_basename", array( $this, 'hook_activation' ) );
			add_action( "deactivate_$plugin_basename", array( $this, 'hook_deactivation' ) );

			// Register main plugin hooks.
			add_action( 'init', array( $this, 'run' ) );

			/**
			 * Fires after plugin hooks are registered.
			 *
			 * @since 1.0.0
			 * @since 3.4.0 Added more context
			 *
			 * @param SquadModules $instance The SquadModules instance.
			 * @param string       $basename The plugin basename.
			 */
			do_action( 'divi_squad_register_hooks', $this, $plugin_basename );
		} catch ( Throwable $e ) {
			$this->log_error( $e, 'Failed to register hooks', false );
		}
	}

	/**
	 * Set the activation hook.
	 *
	 * Handles plugin activation tasks such as version tracking and cache management.
	 *
	 * @since  3.4.0 Added improved error handling and enhanced documentation
	 * @since  1.0.0 Initial implementation
	 * @access public
	 *
	 * @return void
	 */
	public function hook_activation(): void {
		try {
			/**
			 * Fires before plugin activation tasks are executed.
			 *
			 * @since 3.4.0
			 *
			 * @param SquadModules $plugin The plugin instance.
			 */
			do_action( 'divi_squad_before_activation', $this );

			// Store the previous version if it's different from the current version.
			$version_value = (string) $this->memory->get( 'version', $this->get_version_dot() );
			if ( $this->get_version_dot() !== $version_value ) {
				$this->memory->set( 'previous_version', $version_value );
			}

			// Set plugin activation time and version.
			$this->memory->set( 'version', $this->get_version_dot() );
			$this->memory->set( 'activation_time', time() );

			/**
			 * Filter whether to clean the Divi Builder cache on plugin activation.
			 *
			 * @since 3.2.0
			 * @since 3.4.0 Added plugin instance parameter
			 *
			 * @param bool         $can_clean_cache Whether to clean the cache on activation. Default is true.
			 * @param SquadModules $plugin          The plugin instance.
			 */
			$can_clean_cache  = (bool) apply_filters( 'divi_squad_clean_cache_on_activation', true, $this );
			$is_cache_deleted = (bool) $this->memory->get( 'is_cache_deleted', false );

			if ( ! $is_cache_deleted && $can_clean_cache ) {
				// Force the legacy backend builder to reload its template cache.
				// This ensures that custom modules are available for use right away.
				if ( function_exists( 'et_pb_force_regenerate_templates' ) ) {
					\et_pb_force_regenerate_templates();
				}

				// Store the status.
				$this->memory->set( 'is_cache_deleted', true );
			}

			/**
			 * Fires after the plugin is activated.
			 *
			 * @since 1.0.0
			 *
			 * @param SquadModules $plugin The plugin instance.
			 */
			do_action( 'divi_squad_after_activation', $this );
		} catch ( Throwable $e ) {
			$this->log_error( $e, 'activation', false );
		}
	}

	/**
	 * Set the deactivation hook.
	 *
	 * Handles plugin deactivation tasks such as tracking deactivation time.
	 *
	 * @since  3.4.0 Added improved error handling
	 * @since  1.0.0 Initial implementation
	 * @access public
	 *
	 * @return void
	 */
	public function hook_deactivation(): void {
		try {
			/**
			 * Fires before plugin deactivation tasks are executed.
			 *
			 * @since 3.4.0
			 *
			 * @param SquadModules $plugin The plugin instance.
			 */
			do_action( 'divi_squad_before_deactivation', $this );

			// Set plugin deactivation time and version.
			$this->memory->set( 'version', $this->get_version_dot() );
			$this->memory->set( 'deactivation_time', time() );

			/**
			 * Fires after the plugin is deactivated.
			 *
			 * @param SquadModules $plugin The plugin instance.
			 */
			do_action( 'divi_squad_after_deactivation', $this );
		} catch ( Throwable $e ) {
			$this->log_error( $e, 'deactivation', false );
		}
	}

	/**
	 * Load Initial Components
	 *
	 * This method is called after the plugin is initialized and sets up additional components.
	 * Uses static flag to prevent circular dependencies.
	 *
	 * @since  3.4.0 Added circular dependency prevention
	 * @since  3.2.0 Initial implementation
	 * @access public
	 *
	 * @return void
	 */
	public function load_initials(): void {
		// Prevent circular dependencies and infinite recursion
		if ( self::$is_initializing ) {
			return;
		}

		self::$is_initializing = true;

		try {
			/**
			 * Fires before initial components are loaded.
			 *
			 * @since 3.4.0
			 *
			 * @param SquadModules $plugin Current plugin instance.
			 */
			do_action( 'divi_squad_before_load_initials', $this );

			$this->init_plugin_data();
			$this->init_prerequisites();
			$this->init_deprecated_class_loader();

			/**
			 * Fires after the plugin is initialized.
			 *
			 * This action allows executing code after the plugin is fully initialized.
			 *
			 * @since 3.2.0
			 *
			 * @param SquadModules $plugin Current plugin instance.
			 */
			do_action( 'divi_squad_loaded_initials', $this );
		} catch ( Throwable $e ) {
			$this->log_error( $e, 'Error loading initial components', false );
		} finally {
			self::$is_initializing = false;
		}
	}

	/**
	 * Initialize Plugin Settings
	 *
	 * Sets up core plugin properties and configuration options.
	 *
	 * @since  3.4.0 Added improved error handling and hooks
	 * @since  1.0.0 Initial implementation
	 * @access public
	 *
	 * @return void
	 * @throws RuntimeException If plugin data cannot be retrieved.
	 */
	public function init_plugin_data(): void {
		try {
			// Get the plugin data.
			$options = $this->get_plugin_data( DIVI_SQUAD_PLUGIN_FILE );

			// Set basic plugin properties.
			$this->name       = 'squad-modules-for-divi';
			$this->textdomain = $options['TextDomain'] ?? $this->name; // @phpstan-ignore assign.propertyType
			$this->version    = $options['Version'] ?? '1.0.0'; // @phpstan-ignore assign.propertyType
			$this->options    = wp_parse_args( $options, array( 'RequiresDIVI' => '4.14.0' ) );

			/**
			 * Filters the plugin options.
			 *
			 * @since 3.2.3
			 * @since 3.4.0 Added plugin instance parameter
			 *
			 * @param array        $options Plugin options.
			 * @param SquadModules $plugin  Current plugin instance.
			 */
			$this->options = apply_filters( 'divi_squad_options', $this->options, $this );

			/**
			 * Fires after the plugin data is initialized.
			 *
			 * This action allows executing code after the plugin data has been set up.
			 *
			 * @since 3.2.0
			 *
			 * @param SquadModules $plugin Current plugin instance.
			 */
			do_action( 'divi_squad_after_plugin_data', $this );
		} catch ( Throwable $e ) {
			$this->log_error( $e, 'Failed to initialize plugin data' );
		}
	}

	/**
	 * Initialize Prerequisites
	 *
	 * Sets up the core prerequisites for the plugin, including requirements and memory management.
	 * Uses safe initialization to prevent circular dependencies.
	 *
	 * @since  3.4.0 Added safe initialization for error reporter
	 * @since  3.2.0 Initial implementation
	 * @access public
	 *
	 * @return void
	 */
	public function init_prerequisites(): void {
		try {
			/**
			 * Fires before prerequisites are initialized.
			 *
			 * @since 3.4.0
			 *
			 * @param SquadModules $plugin Current plugin instance.
			 */
			do_action( 'divi_squad_before_prerequisites_init', $this );

			$this->container['distribution'] = new Core\Distribution();
			$this->container['requirements'] = new Core\Requirements();
			$this->container['memory']       = new Core\Memory();
			$this->container['cache']        = new Core\Cache();
			$this->container['assets']       = new Core\Assets();

			// Error reporting system - initialize with safe mode to prevent circular dependencies
			$this->container['error_reporter'] = new Core\Error\Reporter( array() );

			/**
			 * Fires after the plugin prerequisites are initialized.
			 *
			 * This action allows executing code after the plugin prerequisites have been set up.
			 *
			 * @since 3.2.0
			 *
			 * @param SquadModules $plugin Current plugin instance.
			 */
			do_action( 'divi_squad_after_prerequisites_init', $this );
		} catch ( Throwable $e ) {
			$this->log_error( $e, 'Failed to initialize prerequisites' );
		}
	}

	/**
	 * Get the list of deprecated classes and their configurations.
	 *
	 * @since  3.0.0
	 * @access protected
	 *
	 * @return array<string, array<string, array<string, mixed>>>
	 */
	protected function get_deprecated_classes_list(): array {
		$builder_load_config = array(
			'action' => array(
				'name'     => 'divi_extensions_init',
				'priority' => 9,
			),
		);

		$core_load_config = array(
			'action' => array(
				'name'     => 'init',
				'priority' => 9,
			),
		);

		return array(
			'Admin/Assets.php'                             => array(),
			'Admin/Plugin/AdminFooterText.php'             => array(),
			'Admin/Plugin/ActionLinks.php'                 => array(),
			'Admin/Plugin/RowMeta.php'                     => array(),
			'Base/Core.php'                                => array(),
			'Base/Memory.php'                              => array(),
			'Base/DiviBuilder/Integration.php'             => $builder_load_config,
			'Base/DiviBuilder/IntegrationAPIBase.php'      => $builder_load_config,
			'Base/DiviBuilder/Integration/ShortcodeAPI.php' => $builder_load_config,
			'Base/DiviBuilder/IntegrationAPI.php'          => $builder_load_config,
			'Base/DiviBuilder/Module.php'                  => $builder_load_config,
			'Base/DiviBuilder/DiviSquad_Module.php'        => $builder_load_config,
			'Base/DiviBuilder/Module/FormStyler.php'       => $builder_load_config,
			'Base/DiviBuilder/Placeholder.php'             => array(),
			'Base/DiviBuilder/Utils/Database/DatabaseUtils.php' => $core_load_config,
			'Base/DiviBuilder/Utils/Elements/CustomFields.php' => $core_load_config,
			'Base/DiviBuilder/Utils/Elements/CustomFields/Traits/TablePopulationTrait.php' => $core_load_config,
			'Base/DiviBuilder/Utils/Elements/CustomFields/DefinitionInterface.php' => $core_load_config,
			'Base/DiviBuilder/Utils/Elements/CustomFields/Definition.php' => $core_load_config,
			'Base/DiviBuilder/Utils/Elements/CustomFields/Definitions/Advanced.php' => $core_load_config,
			'Base/DiviBuilder/Utils/Elements/CustomFields/Definitions/WordPress.php' => $core_load_config,
			'Base/DiviBuilder/Utils/Elements/CustomFields/ManagerInterface.php' => $core_load_config,
			'Base/DiviBuilder/Utils/Elements/CustomFields/Manager.php' => $core_load_config,
			'Base/DiviBuilder/Utils/Elements/CustomFields/Managers/Fields.php' => $core_load_config,
			'Base/DiviBuilder/Utils/Elements/CustomFields/Managers/Upgraders.php' => $core_load_config,
			'Base/DiviBuilder/Utils/Elements/CustomFields/ProcessorInterface.php' => $core_load_config,
			'Base/DiviBuilder/Utils/Elements/CustomFields/Processor.php' => $core_load_config,
			'Base/DiviBuilder/Utils/Elements/CustomFields/Processors/Advanced.php' => $core_load_config,
			'Base/DiviBuilder/Utils/Elements/CustomFields/Processors/WordPress.php' => $core_load_config,
			'Base/DiviBuilder/Utils/Elements/Breadcrumbs.php' => array(),
			'Base/DiviBuilder/Utils/Elements/Divider.php'  => array(),
			'Base/DiviBuilder/Utils/Elements/MaskShape.php' => array(),
			'Base/DiviBuilder/Utils/Fields/CompatibilityTrait.php' => array(),
			'Base/DiviBuilder/Utils/Fields/DefinitionTrait.php' => array(),
			'Base/DiviBuilder/Utils/Fields/ProcessorTrait.php' => array(),
			'Base/DiviBuilder/Utils/UtilsInterface.php'    => array(),
			'Base/DiviBuilder/Utils/CommonTrait.php'       => array(),
			'Base/DiviBuilder/Utils/DeprecationsTrait.php' => array(),
			'Base/DiviBuilder/Utils/FieldsTrait.php'       => array(),
			'Base/DiviBuilder/Utils/Base.php'              => array(),
			'Base/DiviBuilder/Utils.php'                   => array(),
			'Base/Factories/FactoryBase/FactoryInterface.php' => array(),
			'Base/Factories/FactoryBase/Factory.php'       => array(),
			'Base/Factories/AdminMenu/MenuInterface.php'   => array(),
			'Base/Factories/AdminMenu/Menu.php'            => array(),
			'Base/Factories/AdminMenu/MenuCore.php'        => array(),
			'Base/Factories/AdminMenu.php'                 => array(),
			'Base/Factories/AdminNotice/NoticeInterface.php' => array(),
			'Base/Factories/AdminNotice/Notice.php'        => array(),
			'Base/Factories/AdminNotice.php'               => array(),
			'Base/Factories/BrandAsset/AssetInterface.php' => array(),
			'Base/Factories/BrandAsset/Asset.php'          => array(),
			'Base/Factories/BrandAsset/BrandAssetInterface.php' => array(),
			'Base/Factories/BrandAsset/BrandAsset.php'     => array(),
			'Base/Factories/BrandAsset.php'                => array(),
			'Base/Factories/PluginAsset/AssetInterface.php' => array(),
			'Base/Factories/PluginAsset/Asset.php'         => array(),
			'Base/Factories/PluginAsset/PluginAssetInterface.php' => array(),
			'Base/Factories/PluginAsset/PluginAsset.php'   => array(),
			'Base/Factories/PluginAsset.php'               => array(),
			'Base/Factories/RestRoute/RouteInterface.php'  => array(),
			'Base/Factories/RestRoute/Route.php'           => array(),
			'Base/Factories/RestRoute.php'                 => array(),
			'Base/Factories/SquadFeatures.php'             => array(),
			'Integrations/Admin.php'                       => array(),
			'Integrations/Core.php'                        => array(),
			'Integrations/WP.php'                          => array(),
			'Managers/Assets.php'                          => array(),
			'Managers/Emails/ErrorReport.php'              => array(),
			'Modules/PostGridChild.php'                    => $builder_load_config,
			'Modules/PostGridChild/PostGridChild.php'      => $builder_load_config,
			'Managers/Features/Extensions.php'             => array(),
			'Managers/Features/Modules.php'                => array(),
			'Managers/Extensions.php'                      => array(),
			'Managers/Modules.php'                         => array(),
			'Utils/Media/Filesystem.php'                   => array(),
			'Utils/Polyfills/Str.php'                      => array(),
			'Utils/Asset.php'                              => array(),
			'Utils/Singleton.php'                          => array(),
		);
	}

	/**
	 * Get the Freemius instance.
	 *
	 * Provides the Freemius SDK instance for licensing, analytics, and deployment features.
	 * Includes robust error handling to prevent distribution-related issues.
	 *
	 * @since  3.4.0 Added improved error handling and caching
	 * @since  3.2.0 Initial implementation
	 *
	 * @return Freemius
	 */
	public function get_distributor(): Freemius {
		// Return a cached instance if available.
		if ( $this->distributor instanceof Freemius ) {
			return $this->distributor;
		}

		// Get the publisher from the container.
		if ( ! isset( $this->container['distribution'] ) || ! ( $this->container['distribution'] instanceof Core\Distribution ) ) {
			throw new RuntimeException( 'Distribution is not initialized properly.' );
		}

		$distribution = $this->container['distribution'];

		// Initialize a global Freemius instance if needed.
		if ( ! isset( $this->distributor ) ) {
			$divi_squad_fs = $distribution->get_fs();

			/**
			 * Fires after the Freemius instance is set up.
			 *
			 * @since 3.2.0
			 * @since 3.4.0 Added plugin instance parameter
			 *
			 * @param Freemius     $divi_squad_fs The Freemius instance.
			 * @param SquadModules $plugin        The plugin instance.
			 */
			do_action( 'divi_squad_after_fs_init', $divi_squad_fs, $this );

			// Cache the instance.
			$this->distributor = $divi_squad_fs;
		}

		return $this->distributor;
	}

	/**
	 * Run Plugin Initialization
	 *
	 * Bootstraps the plugin by loading components and firing initialization hooks.
	 * Implements comprehensive error handling for each initialization phase.
	 *
	 * @since  3.4.0 Added improved error handling and component-specific try-catch blocks
	 * @since  1.0.0 Initial implementation
	 * @access public
	 *
	 * @return void
	 */
	public function run(): void {
		try {
			/**
			 * Fires before the plugin is prepared for loading.
			 *
			 * This action allows executing code before the plugin is fully loaded.
			 * It can be used to perform tasks that need to be done before the plugin is completely initialized.
			 *
			 * @since 3.2.0
			 *
			 * @param SquadModules $plugin Current plugin instance.
			 */
			do_action( 'divi_squad_preparing_loading', $this );

			// Load the plugin assets.
			$this->load_plugin_assets();

			/**
			 * Fires before the plugin's system requirements are validated.
			 *
			 * This action allows executing code before the plugin's requirements check is performed.
			 *
			 * @since 3.2.3
			 * @since 3.3.0 From now, we show the notice to admin, ensure all are functional in the frontend if they are used.
			 * @since 3.4.0 Added more descriptive documentation
			 *
			 * @param bool         $is_met Whether the plugin's requirements are met. Default is false.
			 * @param SquadModules $plugin Current plugin instance.
			 */
			$requirements_is_met = apply_filters( 'divi_squad_requirements_is_met', $this->requirements->is_fulfilled(), $this );

			// Only block admin functionality, allow frontend to continue with limited features.
			if ( ! $requirements_is_met ) {
				if ( is_admin() ) {
					$this->requirements->register_pre_loaded_admin_page();

					/**
					 * Fires when requirements are not met in admin area.
					 *
					 * @since 3.4.0
					 *
					 * @param SquadModules $plugin Current plugin instance.
					 */
					do_action( 'divi_squad_requirements_not_met_admin', $this );

					return;
				}

				/**
				 * Fires when requirements are not met but execution continues.
				 *
				 * This typically happens on the frontend where we still want to
				 * provide limited functionality.
				 *
				 * @since 3.4.0
				 *
				 * @param SquadModules $plugin Current plugin instance.
				 */
				do_action( 'divi_squad_requirements_not_met_continue', $this );
			}

			/**
			 * Fires after the plugin's system requirements have been validated.
			 *
			 * This action allows executing code after the plugin's requirements check has completed
			 * and before the plugin begins loading its core components.
			 *
			 * @since 3.2.0
			 * @see   Core\Requirements::is_fulfilled() For the requirements validation logic
			 *
			 * @param bool         $is_met Whether the plugin's requirements are met.
			 * @param SquadModules $plugin Current plugin instance.
			 */
			do_action( 'divi_squad_after_requirements_validation', $requirements_is_met, $this );

			$this->load_containers();
			$this->load_addons();
			$this->load_components();

			/**
			 * Fires after the plugin is fully loaded.
			 *
			 * This action hook allows developers to execute custom code after all plugin components
			 * have been initialized and the plugin is fully loaded. It can be used to perform tasks
			 * that need to be done after the plugin is completely initialized.
			 *
			 * @since 1.0.0
			 *
			 * @param SquadModules $plugin The current instance of the SquadModules plugin.
			 */
			do_action( 'divi_squad_loaded', $this );
		} catch ( Throwable $e ) {
			$this->log_error( $e, 'Plugin runner failed' );
		}
	}

	/**
	 * Load Plugin Prerequisite Components
	 *
	 * Loads all core plugin prerequisite components with proper error handling.
	 *
	 * @since  3.4.0 Added improved error handling
	 * @since  3.2.0 Initial implementation
	 * @access protected
	 *
	 * @return void
	 */
	protected function load_plugin_assets(): void {
		try {
			if ( is_admin() ) {
				new Core\Admin\Assets();
			}

			new Builder\Assets();

			/**
			 * Fires after the plugin prerequisite components are loaded.
			 *
			 * @since 3.2.0
			 *
			 * @param SquadModules $instance The SquadModules instance.
			 */
			do_action( 'divi_squad_load_plugin_assets', $this );
		} catch ( Throwable $e ) {
			$this->log_error( $e, 'Failed to load plugin assets' );
		}
	}

	/**
	 * Initialize the plugin with required components.
	 *
	 * Loads all main container components with proper error handling for each component.
	 *
	 * @since  3.4.0 Added improved error handling per component
	 * @since  3.2.0 Initial implementation
	 * @access protected
	 *
	 * @return void
	 */
	protected function load_containers(): void {
		try {
			$this->container['modules']     = new Core\Modules();
			$this->container['extensions']  = new Core\Extensions();
			$this->container['rest_routes'] = new Core\Rest_Routes();

			// Load classes for the builder.
			$this->container['custom_fields_element'] = new Builder\Utils\Elements\Custom_Fields();
			$this->container['forms_element']         = new Builder\Utils\Elements\Forms();
			$this->container['d4_module_helper']      = new Builder\Version4\Supports\Module_Helper();

			// Load the custom fields in the separate key.
			$this->container['custom_fields'] = $this->container['custom_fields_element'];

			// Load admin-specific components.
			if ( is_admin() ) {
				$this->container['site_health']  = new Core\Supports\Site_Health();
				$this->container['admin_menu']   = new Core\Admin\Menu();
				$this->container['admin_notice'] = new Core\Admin\Notice();
				$this->container['branding']     = new Core\Admin\Branding();
			}

			/**
			 * Filters the plugin containers after initialization.
			 *
			 * @since 3.2.0
			 * @since 3.4.0 Added more context in documentation
			 *
			 * @param array        $container The plugin container array with initialized components.
			 * @param SquadModules $plugin    The SquadModules instance.
			 */
			$this->container = apply_filters( 'divi_squad_init_containers', $this->container, $this );

			/**
			 * Fires after containers are loaded.
			 *
			 * @since 3.4.0
			 *
			 * @param SquadModules $plugin    The plugin instance.
			 * @param array        $container The loaded container array.
			 */
			do_action( 'divi_squad_after_load_containers', $this, $this->container );
		} catch ( Throwable $e ) {
			$this->log_error( $e, 'Failed to load containers' );
		}
	}

	/**
	 * Load Plugin Components
	 *
	 * Loads all core plugin components with component-specific error handling.
	 *
	 * @since  3.4.0 Added improved error handling per component
	 * @since  1.0.0 Initial implementation
	 * @access protected
	 *
	 * @return void
	 */
	protected function load_components(): void {
		try {
			$this->load_extensions();
			$this->load_modules_for_builder();
			$this->load_rest_apis();
			$this->load_admin_components();

			/**
			 * Fires after the plugin components are loaded.
			 *
			 * @since 3.1.0
			 * @since 3.4.0 Added more descriptive documentation
			 *
			 * @param SquadModules $instance The SquadModules instance with all components loaded.
			 */
			do_action( 'divi_squad_load_components', $this );
		} catch ( Throwable $e ) {
			$this->log_error( $e, 'Failed to load components' );
		}
	}

	/**
	 * Load all extensions.
	 *
	 * Initializes and loads all plugin extensions with proper error handling.
	 *
	 * @since  3.4.0 Added improved error handling
	 * @since  3.3.0 Added action hook
	 * @access protected
	 *
	 * @return void
	 */
	protected function load_extensions(): void {
		try {
			// Load Extension integrations
			new Integrations\Extensions();

			// Initialize all extensions
			$this->extensions->init();

			/**
			 * Fires after the extensions are loaded.
			 *
			 * @since 3.3.0
			 * @since 3.4.0 Added more descriptive documentation
			 *
			 * @param SquadModules $instance The SquadModules instance with extensions loaded.
			 */
			do_action( 'divi_squad_load_extensions', $this );
		} catch ( Throwable $e ) {
			$this->log_error( $e, 'Failed to load extensions' );
		}
	}

	/**
	 * Load the divi custom modules for the divi builder.
	 *
	 * Initializes and loads all Divi Builder modules with proper error handling.
	 *
	 * @since  3.4.0 Added improved error handling
	 * @since  3.3.0 Added action hook
	 * @access protected
	 *
	 * @return void
	 */
	protected function load_modules_for_builder(): void {
		try {
			// Load the settings migration
			Settings\Migration::init();

			// Load Builder integrations
			new Integrations\Builder_Placeholders();
			new Integrations\Builder();

			// Initialize all modules
			$this->modules->init();

			/**
			 * Fires after the modules are loaded for the builder.
			 *
			 * @since 3.3.0
			 * @since 3.4.0 Added more descriptive documentation
			 *
			 * @param SquadModules $instance The SquadModules instance with builder modules loaded.
			 */
			do_action( 'divi_squad_load_modules_for_builder', $this );
		} catch ( Throwable $e ) {
			$this->log_error( $e, 'Failed to load modules for builder' );
		}
	}

	/**
	 * The admin interface asset and others.
	 *
	 * Initializes and loads the REST API components with proper error handling.
	 *
	 * @since  3.4.0 Added improved error handling
	 * @since  3.3.0 Added action hook
	 * @access protected
	 *
	 * @return void
	 */
	protected function load_rest_apis(): void {
		try {
			// Initialize REST routes
			$this->rest_routes->init();

			/**
			 * Fires after the REST APIs are loaded.
			 *
			 * @since 3.3.0
			 * @since 3.4.0 Added more descriptive documentation
			 *
			 * @param SquadModules $instance The SquadModules instance with REST APIs loaded.
			 */
			do_action( 'divi_squad_load_rest_apis', $this );
		} catch ( Throwable $e ) {
			$this->log_error( $e, 'Failed to load REST APIs' );
		}
	}

	/**
	 * The admin interface asset and others.
	 *
	 * Initializes and loads the admin interface components with proper error handling.
	 *
	 * @since  3.4.0 Added improved error handling
	 * @since  3.3.0 Added action hook
	 * @access protected
	 *
	 * @return void
	 */
	protected function load_admin_components(): void {
		if ( ! is_admin() ) {
			return;
		}

		try {
			$this->admin_menu->init();
			$this->admin_notice->init();
			$this->branding->init();

			/**
			 * Fires after the admin components are loaded.
			 *
			 * @since 3.3.0
			 * @since 3.4.0 Added more descriptive documentation
			 *
			 * @param SquadModules $instance The SquadModules instance with admin components loaded.
			 */
			do_action( 'divi_squad_load_admin_components', $this );
		} catch ( Throwable $e ) {
			$this->log_error( $e, 'Failed to load admin components' );
		}
	}

	/**
	 * Load Addon
	 *
	 * Loads components that require WordPress initialization first.
	 *
	 * @since  3.4.0 Added improved error handling
	 * @since  1.0.0 Initial implementation
	 * @access protected
	 *
	 * @return void
	 */
	protected function load_addons(): void {
		try {
			// Initialize custom fields element
			$this->custom_fields_element->init();

			/**
			 * Fires after addon are loaded.
			 *
			 * @since 3.1.0
			 * @since 3.4.0 Added more descriptive documentation
			 *
			 * @param SquadModules $instance The SquadModules instance with addons loaded.
			 */
			do_action( 'divi_squad_load_addons', $this );
		} catch ( Throwable $e ) {
			$this->log_error( $e, 'Failed to load addons' );
		}
	}

	/**
	 * Get the plugin admin menu slug.
	 *
	 * Retrieves the admin menu slug for the plugin with filter support.
	 *
	 * @since  3.4.0 Added improved error handling
	 * @since  1.0.0 Initial implementation
	 * @access public
	 *
	 * @return string The plugin admin menu slug
	 */
	public function get_admin_menu_slug(): string {
		try {
			/**
			 * Filter the plugin admin menu slug.
			 *
			 * @since 1.0.0
			 * @since 3.4.0 Added plugin instance parameter
			 *
			 * @param string       $admin_menu_slug The plugin admin menu slug.
			 * @param SquadModules $plugin          The plugin instance.
			 */
			return apply_filters( 'divi_squad_admin_main_menu_slug', $this->admin_menu_slug, $this );
		} catch ( Throwable $e ) {
			$this->log_error( $e, 'Error getting admin menu slug' );

			return $this->admin_menu_slug;
		}
	}

	/**
	 * Get the plugin admin menu position.
	 *
	 * Retrieves the admin menu position for the plugin with filter support.
	 *
	 * @since  3.4.0 Added improved error handling
	 * @since  1.0.0 Initial implementation
	 * @access public
	 *
	 * @return int The plugin admin menu position
	 */
	public function get_admin_menu_position(): int {
		try {
			/**
			 * Filter the plugin admin menu position.
			 *
			 * @since 1.0.0
			 * @since 3.4.0 Added plugin instance parameter
			 *
			 * @param int          $admin_menu_position The plugin admin menu position.
			 * @param SquadModules $plugin              The plugin instance.
			 */
			return apply_filters( 'divi_squad_admin_menu_position', 101, $this );
		} catch ( Throwable $e ) {
			$this->log_error( $e, 'Error getting admin menu position' );

			return 101;
		}
	}

	/**
	 * Get the plugin base name.
	 *
	 * Retrieves the plugin's basename with proper error handling.
	 *
	 * @since  1.0.0 Initial implementation
	 * @access public
	 *
	 * @return string The plugin basename
	 */
	public function get_basename(): string {
		return plugin_basename( DIVI_SQUAD_PLUGIN_FILE );
	}

	/**
	 * Get the plugin directory path.
	 *
	 * Retrieves a path within the plugin directory with proper error handling and normalization.
	 *
	 * @since  3.4.0 Added improved error handling and path normalization
	 * @since  1.0.0 Initial implementation
	 * @access public
	 *
	 * @param string $path The path to append.
	 *
	 * @return string The full plugin path
	 */
	public function get_path( string $path = '' ): string {
		try {
			// Normalize and clean the base plugin directory path.
			$base_path = wp_normalize_path( dirname( DIVI_SQUAD_PLUGIN_FILE ) );

			// Clean and normalize the requested path.
			$path = wp_normalize_path( $path );
			$path = ltrim( $path, '/' );

			// Combine paths using proper directory separator.
			$full_path = '' !== $path ? path_join( $base_path, $path ) : $base_path;

			/**
			 * Filter the plugin path.
			 *
			 * @since 3.2.3
			 *
			 * @param string       $full_path Absolute path within plugin directory
			 * @param string       $path      Relative path that was requested
			 * @param SquadModules $plugin    The plugin instance
			 */
			return apply_filters( 'divi_squad_plugin_path', $full_path, $path, $this );
		} catch ( Throwable $e ) {
			$this->log_error( $e, 'Failed to get plugin path' );

			return dirname( DIVI_SQUAD_PLUGIN_FILE ) . '/' . ltrim( $path, '/' );
		}
	}

	/**
	 * Get the plugin directory URL.
	 *
	 * Retrieves a URL within the plugin directory with proper error handling.
	 *
	 * @since  3.4.0 Added improved error handling
	 * @since  1.0.0 Initial implementation
	 * @access public
	 *
	 * @param string $path The path to append.
	 *
	 * @return string The full plugin URL
	 */
	public function get_url( string $path = '' ): string {
		try {
			$base_url = plugin_dir_url( DIVI_SQUAD_PLUGIN_FILE );
			$path     = ltrim( $path, '/' );
			$url      = '' !== $path ? trailingslashit( $base_url ) . $path : $base_url;

			/**
			 * Filter the plugin URL.
			 *
			 * @since 3.2.3
			 * @since 3.4.0 Added more descriptive documentation
			 *
			 * @param string       $url    The plugin URL.
			 * @param string       $path   The path to append.
			 * @param SquadModules $plugin The plugin instance.
			 */
			return apply_filters( 'divi_squad_plugin_url', $url, $path, $this );
		} catch ( Throwable $e ) {
			$this->log_error( $e, 'Failed to get plugin URL' );

			$base_url = plugin_dir_url( DIVI_SQUAD_PLUGIN_FILE );
			$url      = trailingslashit( $base_url );

			return '' !== $path ? $url . ltrim( $path, '/' ) : $base_url;
		}
	}
}
