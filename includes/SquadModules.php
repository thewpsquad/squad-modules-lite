<?php // phpcs:disable WordPress.Files.FileName, WordPress.PHP.DevelopmentFunctions

/**
 * Squad Modules Core Class
 *
 * This is the main plugin class that handles initialization, component loading,
 * error handling, and core functionality for the Squad Modules plugin.
 *
 * @since       1.0.0
 *
 * @author      The WP Squad <support@squadmodules.com>
 * @copyright   2023-2024 The WP Squad (https://thewpsquad.com/)
 * @license     GPL-3.0-only
 * @link        https://squadmodules.com
 * @package     DiviSquad
 */

namespace DiviSquad;

use DiviSquad\Core\Cache;
use DiviSquad\Core\Memory;
use DiviSquad\Core\Requirements;
use DiviSquad\Core\Publisher;
use DiviSquad\Core\Supports\Polyfills\Constant;
use Freemius;
use Freemius_Exception;
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
 * @property Core\Cache                    $cache          Cache manager.
 * @property Core\Memory                   $memory         Memory manager.
 * @property Core\Requirements             $requirements   Requirements checker.
 * @property Core\Supports\Site_Health     $site_health    Site health manager.
 * @property Managers\Features\Modules     $modules        Module manager.
 * @property Managers\Features\Extensions  $extensions     Extension manger.
 */
final class SquadModules extends Integrations\Core {

	use Core\Traits\DeprecatedClassLoader;
	use Core\Traits\DetectPluginLife;
	use Core\Traits\Logger;
	use Core\Traits\Singleton;
	use Core\Traits\UseWPFilesystem;

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
	private ?Freemius $publisher = null;

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
	 * @since  3.1.0 Added error handling and logging
	 * @since  3.0.0 Added plugin initialization on 'plugin_loaded' hook
	 * @since  3.0.0 Added publisher initialization on 'plugin_loaded' hook
	 * @since  1.0.0 Initial implementation
	 */
	private function __construct() {
		try {
			/**
			 * Fires before the plugin constructor is initialized.
			 *
			 * This action allows executing code before the plugin constructor is initialized.
			 *
			 * @since 1.0.0
			 */
			do_action( 'divi_squad_before_construct' );

			$this->set_log_identifier( 'Squad Modules' );
			$this->load_initials();
			$this->register_hooks();
		} catch ( Throwable $e ) {
			$this->log_error( $e, 'Constructor initialization failed' );
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

		// Register activation and deactivation hooks
		add_action( "activate_$plugin_basename", array( $this, 'hook_activation' ) );
		add_action( "deactivate_$plugin_basename", array( $this, 'hook_deactivation' ) );

		// Register plugin hooks
		add_action( 'divi_squad_after_requirements_validation', array( $this, 'init_publisher' ), Constant::PHP_INT_MIN );
		add_action( 'init', array( $this, 'run' ), Constant::PHP_INT_MIN );

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
	public function hook_activation() {
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
				// Clean the Divi Builder old cache from the current installation.
				$cache_path = wp_normalize_path( WP_CONTENT_DIR ) . 'et-cache';
				$can_write  = $this->get_wp_fs()->is_writable( $cache_path ) && ! $this->get_wp_fs()->is_file( $cache_path );

				if ( $can_write && $this->get_wp_fs()->exists( $cache_path ) ) {
					$this->get_wp_fs()->rmdir( $cache_path );

					// Store the status.
					$this->memory->set( 'is_cache_deleted', true );
				}
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
	public function hook_deactivation() {
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
	 * @throws RuntimeException If plugin data cannot be retrieved
	 */
	public function init_plugin_data(): void {
		// Get the plugin data
		$options = $this->get_plugin_data( DIVI_SQUAD_PLUGIN_FILE );

		// Set basic plugin properties
		$this->textdomain = $options['TextDomain'] ?? 'squad-modules-for-divi';
		$this->version    = $options['Version'] ?? '1.0.0';
		$this->name       = $this->textdomain;
		$this->options    = wp_parse_args( $options, array( 'RequiresDIVI' => '4.14.0' ) );

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
		$this->container['requirements'] = new Requirements();
		$this->container['memory']       = new Memory();
		$this->container['cache']        = new Cache();
	}

	/**
	 * Get the list of deprecated classes and their configurations.
	 *
	 * @since  3.0.0
	 * @access protected
	 *
	 * @return array
	 */
	protected function get_deprecated_classes_list(): array {
		return array(
			\DiviSquad\Admin\Assets::class                 => array(),
			\DiviSquad\Admin\Plugin\AdminFooterText::class => array(),
			\DiviSquad\Admin\Plugin\ActionLinks::class     => array(),
			\DiviSquad\Admin\Plugin\RowMeta::class         => array(),
			\DiviSquad\Base\Memory::class                  => array(),
			\DiviSquad\Base\DiviBuilder\IntegrationAPIBase::class => array(),
			\DiviSquad\Base\DiviBuilder\IntegrationAPI::class => array(),
			\DiviSquad\Base\DiviBuilder\Utils\UtilsInterface::class => array(),
			\DiviSquad\Base\Factories\AdminMenu\MenuCore::class => array(),
			\DiviSquad\Base\Factories\BrandAsset\BrandAsset::class => array(),
			\DiviSquad\Base\Factories\BrandAsset\BrandAssetInterface::class => array(),
			\DiviSquad\Base\Factories\PluginAsset\PluginAsset::class => array(),
			\DiviSquad\Base\Factories\PluginAsset\PluginAssetInterface::class => array(),
			\DiviSquad\Integrations\Admin::class           => array(),
			\DiviSquad\Integrations\WP::class              => array(),
			\DiviSquad\Managers\Assets::class              => array(),
			\DiviSquad\Managers\Extensions::class          => array(),
			\DiviSquad\Managers\Modules::class             => array(),
			\DiviSquad\Base\DiviBuilder\DiviSquad_Module::class => array(
				'action' => array(
					'name'     => 'divi_extensions_init',
					'priority' => 9,
				),
			),
			\DiviSquad\Modules\PostGridChild\PostGridChild::class => array(
				'action' => array(
					'name'     => 'divi_extensions_init',
					'priority' => 9,
				),
			),
			\DiviSquad\Utils\Media\Filesystem::class       => array(),
			\DiviSquad\Utils\Polyfills\Str::class          => array(),
			\DiviSquad\Utils\Singleton::class              => array(),
		);
	}

	/**
	 * Initialize the publisher.
	 *
	 * This method initializes the publisher instance if the current request is in the admin area
	 * and the `Publisher` class is available.
	 *
	 * @since 3.2.0
	 * @access public
	 *
	 * @return void
	 */
	public function init_publisher(): void {
		try {
			$publisher = $this->get_publisher();

			// Set the plugin ID and key
			$publisher->set_basename( false, DIVI_SQUAD_PLUGIN_FILE );

			/**
			 * Fires after the publisher is initialized.
			 *
			 * @since 3.2.0
			 *
			 * @param SquadModules $plugin    Current plugin instance.
			 * @param Freemius     $publisher The Freemius instance.
			 */
			do_action( 'divi_squad_after_publisher_init', $this, $publisher );
		} catch ( Throwable $e ) {
			$this->log_error( $e, 'Publisher initialization failed' );
		}
	}

	/**
	 * Get the Freemius instance.
	 *
	 * @return Freemius
	 * @throws Freemius_Exception If the publisher cannot be initialized
	 */
	public function get_publisher(): Freemius {
		global $divi_squad_fs;

		// Return cached instance if available
		if ( $this->publisher instanceof Freemius ) {
			return $this->publisher;
		}

		// Initialize publisher if needed
		if ( ! isset( $divi_squad_fs ) && class_exists( Publisher::class ) ) {
			$publisher     = new Publisher( $this );
			$divi_squad_fs = $publisher->get_fs();

			/**
			 * Fires after the Freemius instance is set up.
			 *
			 * @since 3.2.0
			 *
			 * @param Freemius $divi_squad_fs The Freemius instance.
			 */
			do_action( 'divi_squad_after_fs_init', $divi_squad_fs );

			// Cache the instance
			$this->publisher = $divi_squad_fs;
		}

		return $divi_squad_fs;
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

			$this->load_text_domain();
			$this->load_plugin_assets();

			/**
			 * Fires before the plugin's system requirements are validated.
			 *
			 * This action allows executing code before the plugin's requirements check is performed.
			 *
			 * @since 3.2.3
			 *
			 * @param bool         $is_met Whether the plugin's requirements are met. Default is false.
			 * @param SquadModules $plugin Current plugin instance. Use this to access plugin properties and methods.
			 */
			if ( ! apply_filters( 'divi_squad_requirements_is_met', $this->requirements->is_fulfilled(), $this ) ) {
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
			 * @see \DiviSquad\Core\Requirements::is_fulfilled() For the requirements validation logic
			 *
			 * @param SquadModules $plugin Current plugin instance. Use this to access plugin properties and methods.
			 */
			do_action( 'divi_squad_after_requirements_validation', $this );

			$this->init_containers();
			$this->load_components();
			$this->localize_scripts_data();
			$this->load_additional_components();

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
	 * Load the local text domain.
	 *
	 * @return void
	 */
	public function load_text_domain() {
		load_plugin_textdomain( $this->textdomain );
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
		$this->load_global_assets();
		Managers\PluginAssets::load();

		/**
		 * Fires after the plugin prerequisite components are loaded.
		 *
		 * @since 3.2.0
		 *
		 * @param SquadModules $instance The SquadModules instance.
		 */
		do_action( 'divi_squad_load_plugin_assets', $this );
	}

	/**
	 * Initialize the plugin with required components.
	 *
	 * @return void
	 */
	protected function init_containers() {
		$this->container['site_health'] = new Core\Supports\Site_Health();
		$this->container['modules']     = new Managers\Features\Modules();
		$this->container['extensions']  = new Managers\Features\Extensions();

		/**
		 * Fires after the plugin containers are initialized.
		 *
		 * @since 3.2.0
		 *
		 * @param array        $container The plugin container.
		 * @param SquadModules $plugin    The SquadModules instance.
		 */
		$this->container = apply_filters( 'divi_squad_init_containers', $this->container, $this );
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
		$this->load_admin();

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
	 * Load Additional Plugin Components
	 *
	 * Loads components that require WordPress initialization first.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return void
	 * @throws Throwable If an error occurs while loading additional components
	 */
	public function load_additional_components(): void {
		Base\DiviBuilder\Utils\Elements\CustomFields::init();

		/**
		 * Fires after additional components are loaded.
		 *
		 * @since 3.1.0
		 *
		 * @param SquadModules $instance The SquadModules instance.
		 */
		do_action( 'divi_squad_load_additional_components', $this );
	}

	/**
	 * Load all extensions.
	 *
	 * @return void
	 */
	protected function load_extensions() {
		// Load all extensions.
		$this->extensions->load_extensions( realpath( dirname( __DIR__ ) ) );
	}

	/**
	 * The admin interface asset and others.
	 *
	 * @return void
	 */
	protected function load_admin() {
		\DiviSquad\Managers\Ajax::load();
		\DiviSquad\Managers\RestRoutes::load();

		if ( is_admin() ) {
			\DiviSquad\Managers\Branding::load();
			\DiviSquad\Managers\Menus::load();
			\DiviSquad\Managers\Notices::load();
		}
	}

	/**
	 * Get the plugin options.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return array
	 */
	public function get_options(): array {
		return $this->options;
	}

	/**
	 * Get a specific option value.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param string $key           The option key.
	 * @param mixed  $default_value The default value if the option doesn't exist.
	 *
	 * @return mixed
	 */
	public function get_option( string $key, $default_value = null ) {
		return $this->options[ $key ] ?? $default_value;
	}

	/**
	 * Set a specific option value.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param string $key   The option key.
	 * @param mixed  $value The option value.
	 *
	 * @return void
	 */
	public function set_option( string $key, $value ): void {
		$this->options[ $key ] = $value;
	}

	/**
	 * Get the plugin version number.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return string
	 */
	public function get_version(): string {
		return $this->version;
	}

	/**
	 * Get the plugin version number (dotted).
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return string
	 */
	public function get_version_dot(): string {
		return $this->get_option( 'Version', '1.0.0' );
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
		return wp_normalize_path( dirname( DIVI_SQUAD_PLUGIN_FILE ) . $path );
	}

	/**
	 * Get the plugin template path.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return string
	 */
	public function get_template_path(): string {
		return $this->get_path( '/templates' );
	}

	/**
	 * Get the plugin asset URL.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return string
	 */
	public function get_asset_url(): string {
		return trailingslashit( $this->get_url() . 'build' );
	}

	/**
	 * Get the plugin directory URL.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return string
	 */
	public function get_url(): string {
		return plugin_dir_url( DIVI_SQUAD_PLUGIN_FILE );
	}

	/**
	 * Get the plugin icon path.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return string
	 */
	public function get_icon_path(): string {
		return trailingslashit( $this->get_path() ) . 'build/admin/icons';
	}

	/**
	 * Retrieve the WordPress root path.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return string
	 */
	public function get_wp_path(): string {
		return trailingslashit( ABSPATH );
	}
}
