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
 * @property Core\Requirements                       $requirements           Requirements checker.
 * @property Core\Memory                             $memory                 Memory manager.
 * @property Core\Cache                              $cache                  Cache manager.
 * @property Core\Assets                             $assets                 Assets Manager
 * @property Core\Distribution                       $distribution           Distribution manager.
 * @property Core\Supports\Site_Health               $site_health            Site health manager.
 * @property Core\Admin\Menu                         $admin_menu             Admin menu manager.
 * @property Core\Admin\Notice                       $admin_notice           Admin notice manager
 * @property Core\Admin\Branding                     $branding               Branding manager.
 * @property Core\Modules                            $modules                Module manager.
 * @property Core\Extensions                         $extensions             Extension manger.
 * @property Core\RestRoutes                         $rest_routes            REST API manager.
 * @property Builder\Utils\Elements\Custom_Fields    $custom_fields_element  Custom fields manager.
 * @property Builder\Utils\Elements\Forms            $forms_element           Forms manager.
 * @property Builder\Version4\Supports\Module_Helper $d4_module_helper       Module helper for Divi 4.
 */
final class SquadModules {

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
	 * Plugin Constructor
	 *
	 * Initializes the plugin by:
	 * - Setting up core plugin properties
	 * - Initializing memory management
	 * - Registering WordPress hooks
	 * - Loading deprecated class compatibility
	 *
	 * @since  3.2.0 Added system requirements check
	 * @since  1.0.0 Initial implementation
	 */
	private function __construct() {
		try {
			$this->set_log_identifier( 'Squad Modules' );
			$this->load_initials();
			$this->register_hooks();
		} catch ( Throwable $e ) {
			$this->log_error( $e, 'Constructor initialization failed', false );
		}
	}

	/**
	 * Register Core WordPress Hooks
	 *
	 * Sets up all necessary WordPress action and filter hooks for the plugin.
	 *
	 * @since  3.2.0 Added notice style hooks
	 * @since  1.0.0
	 * @access private
	 *
	 * @return void
	 */
	private function register_hooks(): void {
		$plugin_basename = $this->get_basename();

		// Register activation and deactivation hooks.
		add_action( "activate_$plugin_basename", array( $this, 'hook_activation' ) );
		add_action( "deactivate_$plugin_basename", array( $this, 'hook_deactivation' ) );

		// Register plugin hooks.
		// Note: init_publisher hook is no longer needed since publisher is initialized in prerequisites.
		add_action( 'init', array( $this, 'run' ) );

		/**
		 * Fires after plugin hooks are registered.
		 *
		 * @since 1.0.0
		 *
		 * @param SquadModules $instance The SquadModules instance.
		 */
		do_action( 'divi_squad_register_hooks', $this );
	}

	/**
	 * Set the activation hook.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function hook_activation(): void {
		try {
			// Store the previous version if it's different from the current version.
			$version_value = (string) $this->memory->get( 'version', $this->get_version_dot() );
			if ( $this->get_version_dot() !== $version_value ) {
				$this->memory->set( 'previous_version', $version_value );
			}

			// Set plugin activation time and version.
			$this->memory->set( 'version', $this->get_version_dot() );
			$this->memory->set( 'activation_time', time() );

			/**
			 * Clean the Divi Builder cache on plugin activation.
			 *
			 * @since 3.2.0
			 *
			 * @param bool $can_clean_cache Whether to clean the cache on activation. Default is true.
			 */
			$can_clean_cache  = (bool) apply_filters( 'divi_squad_clean_cache_on_activation', true );
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
			 * @param SquadModules $plugin The plugin instance.
			 */
			do_action( 'divi_squad_after_activation', $this );
		} catch ( Throwable $e ) {
			$this->log_error( $e, 'activation' );
		}
	}

	/**
	 * Set the deactivation hook.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function hook_deactivation(): void {
		try {
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
			$this->log_error( $e, 'deactivation' );
		}
	}

	/**
	 * Load Initial Components
	 *
	 * This method is called after the plugin is initialized and sets up additional components.
	 *
	 * @since  3.2.0
	 * @access public
	 *
	 * @return void
	 */
	public function load_initials(): void {
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
	}

	/**
	 * Initialize Plugin Settings
	 *
	 * Sets up core plugin properties and configuration options.
	 *
	 * @since  1.0.0
	 * @access private
	 *
	 * @return void
	 * @throws RuntimeException If plugin data cannot be retrieved.
	 */
	public function init_plugin_data(): void {
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
		 *
		 * @param array $options Plugin options.
		 */
		$this->options = apply_filters( 'divi_squad_options', $this->options );

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
	}

	/**
	 * Initialize Prerequisites
	 *
	 * Sets up the core prerequisites for the plugin, including requirements and memory management.
	 *
	 * @since  3.2.0
	 * @access public
	 *
	 * @return void
	 */
	public function init_prerequisites(): void {
		$this->container['distribution'] = new Core\Distribution();
		$this->container['requirements'] = new Core\Requirements();
		$this->container['memory']       = new Core\Memory();
		$this->container['cache']        = new Core\Cache();
		$this->container['assets']       = new Core\Assets();

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
		return array(
			\DiviSquad\Admin\Assets::class                                                             => array(),
			\DiviSquad\Admin\Plugin\AdminFooterText::class                                             => array(),
			\DiviSquad\Admin\Plugin\ActionLinks::class                                                 => array(),
			\DiviSquad\Admin\Plugin\RowMeta::class                                                     => array(),
			\DiviSquad\Base\Core::class                                                                => array(),
			\DiviSquad\Base\Memory::class                                                              => array(),
			\DiviSquad\Base\DiviBuilder\IntegrationAPIBase::class                                      => array(),
			\DiviSquad\Base\DiviBuilder\IntegrationAPI::class                                          => array(),
			\DiviSquad\Base\DiviBuilder\Integration::class                                             => array(),
			\DiviSquad\Base\DiviBuilder\Integration\ShortcodeAPI::class                                => array(),
			\DiviSquad\Base\DiviBuilder\Module::class                                                  => array(
				'action' => array(
					'name'     => 'divi_extensions_init',
					'priority' => 9,
				),
			),
			\DiviSquad\Base\DiviBuilder\DiviSquad_Module::class                                        => array(
				'action' => array(
					'name'     => 'divi_extensions_init',
					'priority' => 9,
				),
			),
			\DiviSquad\Base\DiviBuilder\Module\FormStyler::class                                       => array(
				'action' => array(
					'name'     => 'divi_extensions_init',
					'priority' => 9,
				),
			),
			\DiviSquad\Base\DiviBuilder\Placeholder::class                                             => array(),
			\DiviSquad\Base\DiviBuilder\Utils\Database\DatabaseUtils::class                            => array(
				'action' => array(
					'name'     => 'init',
					'priority' => 9,
				),
			),
			\DiviSquad\Base\DiviBuilder\Utils\Elements\CustomFields::class                             => array(
				'action' => array(
					'name'     => 'init',
					'priority' => 9,
				),
			),
			\DiviSquad\Base\DiviBuilder\Utils\Elements\CustomFields\Traits\TablePopulationTrait::class => array(
				'action' => array(
					'name'     => 'init',
					'priority' => 9,
				),
			),
			\DiviSquad\Base\DiviBuilder\Utils\Elements\CustomFields\DefinitionInterface::class         => array(
				'action' => array(
					'name'     => 'init',
					'priority' => 9,
				),
			),
			\DiviSquad\Base\DiviBuilder\Utils\Elements\CustomFields\Definition::class                  => array(
				'action' => array(
					'name'     => 'init',
					'priority' => 9,
				),
			),
			\DiviSquad\Base\DiviBuilder\Utils\Elements\CustomFields\Definitions\Advanced::class        => array(
				'action' => array(
					'name'     => 'init',
					'priority' => 9,
				),
			),
			\DiviSquad\Base\DiviBuilder\Utils\Elements\CustomFields\Definitions\WordPress::class       => array(
				'action' => array(
					'name'     => 'init',
					'priority' => 9,
				),
			),
			\DiviSquad\Base\DiviBuilder\Utils\Elements\CustomFields\ManagerInterface::class            => array(
				'action' => array(
					'name'     => 'init',
					'priority' => 9,
				),
			),
			\DiviSquad\Base\DiviBuilder\Utils\Elements\CustomFields\Manager::class                     => array(
				'action' => array(
					'name'     => 'init',
					'priority' => 9,
				),
			),
			\DiviSquad\Base\DiviBuilder\Utils\Elements\CustomFields\Managers\Fields::class             => array(
				'action' => array(
					'name'     => 'init',
					'priority' => 9,
				),
			),
			\DiviSquad\Base\DiviBuilder\Utils\Elements\CustomFields\Managers\Upgraders::class          => array(
				'action' => array(
					'name'     => 'init',
					'priority' => 9,
				),
			),
			\DiviSquad\Base\DiviBuilder\Utils\Elements\CustomFields\ProcessorInterface::class          => array(
				'action' => array(
					'name'     => 'init',
					'priority' => 9,
				),
			),
			\DiviSquad\Base\DiviBuilder\Utils\Elements\CustomFields\Processor::class                   => array(
				'action' => array(
					'name'     => 'init',
					'priority' => 9,
				),
			),
			\DiviSquad\Base\DiviBuilder\Utils\Elements\CustomFields\Processors\Advanced::class         => array(
				'action' => array(
					'name'     => 'init',
					'priority' => 9,
				),
			),
			\DiviSquad\Base\DiviBuilder\Utils\Elements\CustomFields\Processors\WordPress::class        => array(
				'action' => array(
					'name'     => 'init',
					'priority' => 9,
				),
			),
			\DiviSquad\Base\DiviBuilder\Utils\Elements\Breadcrumbs::class                              => array(
				'action' => array(
					'name'     => 'init',
					'priority' => 9,
				),
			),
			\DiviSquad\Base\DiviBuilder\Utils\Elements\Divider::class                                  => array(
				'action' => array(
					'name'     => 'init',
					'priority' => 9,
				),
			),
			\DiviSquad\Base\DiviBuilder\Utils\Elements\MaskShape::class                                => array(
				'action' => array(
					'name'     => 'init',
					'priority' => 9,
				),
			),
			\DiviSquad\Base\DiviBuilder\Utils\Fields\CompatibilityTrait::class                         => array(),
			\DiviSquad\Base\DiviBuilder\Utils\Fields\DefinitionTrait::class                            => array(),
			\DiviSquad\Base\DiviBuilder\Utils\Fields\ProcessorTrait::class                             => array(),
			\DiviSquad\Base\DiviBuilder\Utils\UtilsInterface::class                                    => array(),
			\DiviSquad\Base\DiviBuilder\Utils\CommonTrait::class                                       => array(),
			\DiviSquad\Base\DiviBuilder\Utils\DeprecationsTrait::class                                 => array(),
			\DiviSquad\Base\DiviBuilder\Utils\FieldsTrait::class                                       => array(),
			\DiviSquad\Base\DiviBuilder\Utils\Base::class                                              => array(
				'action' => array(
					'name'     => 'divi_extensions_init',
					'priority' => 9,
				),
			),
			\DiviSquad\Base\DiviBuilder\Utils::class                                                   => array(
				'action' => array(
					'name'     => 'divi_extensions_init',
					'priority' => 9,
				),
			),
			\DiviSquad\Base\Factories\FactoryBase\FactoryInterface::class                              => array(),
			\DiviSquad\Base\Factories\FactoryBase\Factory::class                                       => array(),
			\DiviSquad\Base\Factories\AdminMenu\MenuInterface::class                                   => array(),
			\DiviSquad\Base\Factories\AdminMenu\Menu::class                                            => array(),
			\DiviSquad\Base\Factories\AdminMenu\MenuCore::class                                        => array(),
			\DiviSquad\Base\Factories\AdminMenu::class                                                 => array(),
			\DiviSquad\Base\Factories\AdminNotice\NoticeInterface::class                               => array(),
			\DiviSquad\Base\Factories\AdminNotice\Notice::class                                        => array(),
			\DiviSquad\Base\Factories\AdminNotice::class                                               => array(),
			\DiviSquad\Base\Factories\BrandAsset\AssetInterface::class                                 => array(),
			\DiviSquad\Base\Factories\BrandAsset\Asset::class                                          => array(),
			\DiviSquad\Base\Factories\BrandAsset\BrandAssetInterface::class                            => array(),
			\DiviSquad\Base\Factories\BrandAsset\BrandAsset::class                                     => array(),
			\DiviSquad\Base\Factories\BrandAsset::class                                                => array(),
			\DiviSquad\Base\Factories\PluginAsset\AssetInterface::class                                => array(),
			\DiviSquad\Base\Factories\PluginAsset\Asset::class                                         => array(),
			\DiviSquad\Base\Factories\PluginAsset\PluginAssetInterface::class                          => array(),
			\DiviSquad\Base\Factories\PluginAsset\PluginAsset::class                                   => array(),
			\DiviSquad\Base\Factories\PluginAsset::class                                               => array(),
			\DiviSquad\Base\Factories\RestRoute\RouteInterface::class                                  => array(),
			\DiviSquad\Base\Factories\RestRoute\Route::class                                           => array(),
			\DiviSquad\Base\Factories\RestRoute::class                                                 => array(),
			\DiviSquad\Base\Factories\SquadFeatures::class                                             => array(),
			\DiviSquad\Integrations\Admin::class                                                       => array(),
			\DiviSquad\Integrations\Core::class                                                        => array(),
			\DiviSquad\Integrations\WP::class                                                          => array(),
			\DiviSquad\Managers\Assets::class                                                          => array(),
			\DiviSquad\Managers\Emails\ErrorReport::class                                              => array(),
			\DiviSquad\Managers\Extensions::class                                                      => array(),
			\DiviSquad\Managers\Features\Extensions::class                                             => array(),
			\DiviSquad\Managers\Features\Modules::class                                                => array(),
			\DiviSquad\Managers\Modules::class                                                         => array(),
			\DiviSquad\Modules\PostGridChild::class                                                    => array(
				'action' => array(
					'name'     => 'divi_extensions_init',
					'priority' => 10,
				),
			),
			\DiviSquad\Modules\PostGridChild\PostGridChild::class                                      => array(
				'action' => array(
					'name'     => 'divi_extensions_init',
					'priority' => 10,
				),
			),
			\DiviSquad\Utils\Media\Filesystem::class                                                   => array(),
			\DiviSquad\Utils\Polyfills\Str::class                                                      => array(),
			\DiviSquad\Utils\Asset::class                                                              => array(),
			\DiviSquad\Utils\Singleton::class                                                          => array(),
		);
	}

	/**
	 * Get the Freemius instance.
	 *
	 * @return Freemius
	 * @throws RuntimeException If the publisher is not initialized properly.
	 * @throws Throwable If an error occurs while getting the distributor instance.
	 */
	public function get_distributor(): Freemius {
		try {
			// Return cached instance if available.
			if ( $this->distributor instanceof Freemius ) {
				return $this->distributor;
			}

			// Get the publisher from the container.
			if ( ! isset( $this->container['distribution'] ) || ! ( $this->container['distribution'] instanceof Core\Distribution ) ) {
				throw new RuntimeException( 'Distribution is not initialized properly.' );
			}

			$distribution = $this->container['distribution'];

			// Initialize global Freemius instance if needed.
			if ( ! isset( $this->distributor ) ) {
				$divi_squad_fs = $distribution->get_fs();

				/**
				 * Fires after the Freemius instance is set up.
				 *
				 * @since 3.2.0
				 *
				 * @param Freemius $divi_squad_fs The Freemius instance.
				 */
				do_action( 'divi_squad_after_fs_init', $divi_squad_fs );

				// Cache the instance.
				$this->distributor = $divi_squad_fs;
			}

			return $this->distributor;
		} catch ( Throwable $e ) {
			$this->log_error( $e, 'Failed to get distributor instance' );
			throw $e; // Re-throw to maintain the original behavior since this is a critical operation.
		}
	}

	/**
	 * Run Plugin Initialization
	 *
	 * Bootstraps the plugin by loading components and firing initialization hooks.
	 *
	 * @since  1.0.0
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
			 *
			 * @param bool         $is_met Whether the plugin's requirements are met. Default is false.
			 * @param SquadModules $plugin Current plugin instance. Use this to access plugin properties and methods.
			 */
			$requirements_is_met = apply_filters( 'divi_squad_requirements_is_met', $this->requirements->is_fulfilled(), $this );
			if ( ! $requirements_is_met && is_admin() ) {
				$this->requirements->log_requirement_failure();
				$this->requirements->register_pre_loaded_admin_page();

				return;
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
			 * @param bool         $is_met Whether the plugin's requirements are met. Default is true.
			 * @param SquadModules $plugin Current plugin instance. Use this to access plugin properties and methods.
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
	 * Loads all core plugin prerequisite components.
	 *
	 * @since  3.2.0
	 * @access private
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
	 * @return void
	 */
	protected function load_containers(): void {
		try {
			$this->container['modules']     = new Core\Modules();
			$this->container['extensions']  = new Core\Extensions();
			$this->container['rest_routes'] = new Core\RestRoutes();

			// Load classes for the builder.
			$this->container['custom_fields_element'] = new Builder\Utils\Elements\Custom_Fields();
			$this->container['forms_element']         = new Builder\Utils\Elements\Forms();
			$this->container['d4_module_helper']      = new Builder\Version4\Supports\Module_Helper();

			// Load the custom fields in the separate key.
			$this->container['custom_fields'] = $this->container['custom_fields_element'];

			if ( is_admin() ) {
				$this->container['site_health']  = new Core\Supports\Site_Health();
				$this->container['admin_menu']   = new Core\Admin\Menu();
				$this->container['admin_notice'] = new Core\Admin\Notice();
				$this->container['branding']     = new Core\Admin\Branding();
			}

			/**
			 * Fires after the plugin containers are initialized.
			 *
			 * @since 3.2.0
			 *
			 * @param array        $container The plugin container.
			 * @param SquadModules $plugin    The SquadModules instance.
			 */
			$this->container = apply_filters( 'divi_squad_init_containers', $this->container, $this );
		} catch ( Throwable $e ) {
			$this->log_error( $e, 'Failed to load containers' );
		}
	}

	/**
	 * Load Plugin Components
	 *
	 * Loads all core plugin components.
	 *
	 * @since  1.0.0
	 * @access private
	 *
	 * @return void
	 */
	protected function load_components(): void {
		$this->load_extensions();
		$this->load_modules_for_builder();
		$this->load_rest_apis();
		$this->load_admin_components();

		/**
		 * Fires after the plugin components are loaded.
		 *
		 * @since 3.1.0
		 *
		 * @param SquadModules $instance The SquadModules instance.
		 */
		do_action( 'divi_squad_load_components', $this );
	}

	/**
	 * Load all extensions.
	 *
	 * @return void
	 */
	protected function load_extensions(): void {
		try {
			// Load Extensions.
			new Integrations\Extensions();

			// Load the extensions.
			$this->extensions->init();

			/**
			 * Fires after the extensions are loaded.
			 *
			 * @since 3.3.0
			 *
			 * @param SquadModules $instance The SquadModules instance.
			 */
			do_action( 'divi_squad_load_extensions', $this );
		} catch ( Throwable $e ) {
			$this->log_error( $e, 'Failed to load extensions' );
		}
	}

	/**
	 * Load the divi custom modules for the divi builder.
	 *
	 * @return void
	 */
	protected function load_modules_for_builder(): void {
		try {
			// Load the settings migration.
			Settings\Migration::init();

			// Load Placeholder, Modules, etc.
			new Integrations\DiviBuilderPlaceholders();
			new Integrations\DiviBuilder();

			// Load the modules.
			$this->modules->init();

			/**
			 * Fires after the modules are loaded for the builder.
			 *
			 * @since 3.3.0
			 *
			 * @param SquadModules $instance The SquadModules instance.
			 */
			do_action( 'divi_squad_load_modules_for_builder', $this );
		} catch ( Throwable $e ) {
			$this->log_error( $e, 'Failed to load modules for builder' );
		}
	}

	/**
	 * The admin interface asset and others.
	 *
	 * @return void
	 */
	protected function load_rest_apis(): void {
		try {
			// Load the REST APIs.
			$this->rest_routes->init();

			/**
			 * Fires after the REST APIs are loaded.
			 *
			 * @since 3.3.0
			 *
			 * @param SquadModules $instance The SquadModules instance.
			 */
			do_action( 'divi_squad_load_rest_apis', $this );
		} catch ( Throwable $e ) {
			$this->log_error( $e, 'Failed to load REST APIs' );
		}
	}

	/**
	 * The admin interface asset and others.
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
			 *
			 * @param SquadModules $instance The SquadModules instance.
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
	 * @since  1.0.0
	 * @access public
	 *
	 * @return void
	 */
	protected function load_addons(): void {
		try {
			$this->custom_fields_element->init();

			/**
			 * Fires after addon are loaded.
			 *
			 * @since 3.1.0
			 *
			 * @param SquadModules $instance The SquadModules instance.
			 */
			do_action( 'divi_squad_load_addons', $this );
		} catch ( Throwable $e ) {
			$this->log_error( $e, 'Failed to load addons' );
		}
	}

	/**
	 * Get the plugin admin menu slug.
	 *
	 * @return string
	 */
	public function get_admin_menu_slug(): string {
		/**
		 * Filter the plugin admin menu slug.
		 *
		 * @since 1.0.0
		 *
		 * @param string $admin_menu_slug The plugin admin menu slug.
		 */
		return apply_filters( 'divi_squad_admin_main_menu_slug', $this->admin_menu_slug );
	}

	/**
	 * Get the plugin admin menu position.
	 *
	 * @return int
	 */
	public function get_admin_menu_position(): int {
		/**
		 * Filter the plugin admin menu position.
		 *
		 * @since 1.0.0
		 *
		 * @param int $admin_menu_position The plugin admin menu position.
		 */
		return apply_filters( 'divi_squad_admin_menu_position', 101 );
	}

	/**
	 * Get the plugin base name.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return string
	 */
	public function get_basename(): string {
		return plugin_basename( DIVI_SQUAD_PLUGIN_FILE );
	}

	/**
	 * Get the plugin directory path.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param string $path The path to append.
	 *
	 * @return string
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
	 * Get the plugin template path.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param string $path The path to append.
	 *
	 * @return string
	 */
	public function get_template_path( string $path = '' ): string {
		try {
			/**
			 * Filter the plugin template path.
			 *
			 * @since 3.2.3
			 *
			 * @param string       $template_path The template path.
			 * @param string       $path          The path to append.
			 * @param SquadModules $plugin        The plugin instance.
			 */
			return apply_filters( 'divi_squad_template_path', $this->get_path( '/templates/' . $path ), $path, $this );
		} catch ( Throwable $e ) {
			$this->log_error( $e, 'Failed to get template path' );

			return $this->get_path( '/templates/' . $path );
		}
	}

	/**
	 * Get the plugin icon path.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param string $path The path to append.
	 *
	 * @return string
	 */
	public function get_icon_path( string $path = '' ): string {
		try {
			/**
			 * Filter the plugin icon path.
			 *
			 * @since 3.2.3
			 *
			 * @param string       $icon_path The icon path.
			 * @param string       $path      The path to append.
			 * @param SquadModules $plugin    The plugin instance.
			 */
			return apply_filters( 'divi_squad_icon_path', $this->get_path( '/build/admin/icons/' . $path ), $path, $this );
		} catch ( Throwable $e ) {
			$this->log_error( $e, 'Failed to get icon path' );

			return $this->get_path( '/build/admin/icons/' . $path );
		}
	}

	/**
	 * Get the plugin directory URL.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param string $path The path to append.
	 *
	 * @return string
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
			 *
			 * @param string       $url    The plugin URL.
			 * @param string       $path   The path to append.
			 * @param SquadModules $plugin The plugin instance.
			 */
			return apply_filters( 'divi_squad_plugin_url', $url, $path, $this );
		} catch ( Throwable $e ) {
			$this->log_error( $e, 'Failed to get plugin URL' );
			$base_url = plugin_dir_url( DIVI_SQUAD_PLUGIN_FILE );

			return '' !== $path ? trailingslashit( $base_url ) . ltrim( $path, '/' ) : $base_url;
		}
	}

	/**
	 * Get the plugin asset URL.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param string $path The path to append.
	 *
	 * @return string
	 */
	public function get_asset_url( string $path = '' ): string {
		try {
			/**
			 * Filter the plugin asset URL.
			 *
			 * @since 3.2.3
			 *
			 * @param string       $url    The plugin asset URL.
			 * @param string       $path   The path to append.
			 * @param SquadModules $plugin The plugin instance.
			 */
			return apply_filters( 'divi_squad_asset_url', $this->get_url( 'build/' . $path ), $path, $this );
		} catch ( Throwable $e ) {
			$this->log_error( $e, 'Failed to get asset URL' );

			return $this->get_url( 'build/' . $path );
		}
	}
}
