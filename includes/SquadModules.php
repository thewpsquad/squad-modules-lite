<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName, WordPress.Files.FileName.NotHyphenatedLowercase

/**
 * Squad Modules Lite
 *
 * @package DiviSquad
 * @author  WP Squad <support@squadmodules.com>
 * @since   1.0.0
 */

namespace DiviSquad;

use DiviSquad\Managers\Emails\ErrorReport;
use DiviSquad\Utils\Polyfills\Constant;
use DiviSquad\Utils\WP;
use function add_action;
use function do_action;
use function plugin_basename;
use function plugin_dir_url;
use function trailingslashit;
use function wp_normalize_path;
use function wp_parse_args;
use const ABSPATH;
use const DIVI_SQUAD__FILE__;

/**
 * Squad Modules class.
 *
 * @package DiviSquad
 * @since   1.0.0
 */
final class SquadModules extends Integrations\Core {

	use Base\Traits\DeprecatedClassLoader;
	use Utils\Singleton;

	/**
	 * Admin menu slug.
	 *
	 * @var string
	 */
	protected $admin_menu_slug = 'divi_squad_dashboard';

	/**
	 * Plugin Constructor.
	 *
	 * @since 1.0.0
	 * @since 3.0.0 Added the plugin initialization on `plugin_loaded` hook.
	 * @since 3.0.0 Added the plugin publisher initialization on `plugin_loaded` hook.
	 */
	private function __construct() {
		/**
		 * Fires before the plugin is initialized.
		 *
		 * @since 3.1.0
		 */
		do_action( 'divi_squad_before_init' );

		$this->init_plugin();
		$this->init_memory();
		$this->register_hooks();

		// Ensure the compatibility with the premium version (older).
		$this->init_deprecated_class_loader();
	}

	/**
	 * Register all necessary hooks.
	 *
	 * @return void
	 */
	private function register_hooks() {
		add_action( 'activate_' . $this->get_basename(), array( $this, 'hook_activation' ) );
		add_action( 'deactivate_' . $this->get_basename(), array( $this, 'hook_deactivation' ) );
		add_action( 'plugins_loaded', array( $this, 'run' ) );
		add_action( 'divi_squad_loaded', array( $this, 'load_deprecated_classes' ), Constant::PHP_INT_MIN ); // @phpstan-ignore-line
		add_action( 'init', array( $this, 'load_additional_components' ) );
		add_action( 'init', array( $this, 'load_text_domain' ) );
	}

	/**
	 * Get the plugin options.
	 *
	 * @return array
	 */
	public function get_options() {
		return $this->options;
	}

	/**
	 * Get a specific option value.
	 *
	 * @param  string $key           The option key.
	 * @param  mixed  $default_value The default value if the option doesn't exist.
	 * @return mixed
	 */
	public function get_option( $key, $default_value = null ) {
		return isset( $this->options[ $key ] ) ? $this->options[ $key ] : $default_value;
	}

	/**
	 * Set a specific option value.
	 *
	 * @param  string $key   The option key.
	 * @param  mixed  $value The option value.
	 * @return void
	 */
	public function set_option( $key, $value ) {
		$this->options[ $key ] = $value;
	}

	/**
	 * Get the plugin version number.
	 *
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Get the plugin version number (dotted).
	 *
	 * @return string
	 */
	public function get_version_dot() {
		return ! empty( $this->options['Version'] ) ? $this->options['Version'] : $this->version;
	}

	/**
	 * Get the plugin base name.
	 *
	 * @return string
	 */
	public function get_basename() {
		return plugin_basename( DIVI_SQUAD__FILE__ );
	}

	/**
	 * Get the plugin directory path.
	 *
	 * @param  string $path The path to append.
	 * @return string
	 */
	public function get_path( $path = '' ) {
		return wp_normalize_path( dirname( DIVI_SQUAD__FILE__ ) . $path );
	}

	/**
	 * Get the plugin template path.
	 *
	 * @return string
	 */
	public function get_template_path() {
		return $this->get_path( '/templates' );
	}

	/**
	 * Get the plugin asset URL.
	 *
	 * @return string
	 */
	public function get_asset_url() {
		return trailingslashit( $this->get_url() . 'build' );
	}

	/**
	 * Get the plugin directory URL.
	 *
	 * @return string
	 */
	public function get_url() {
		return plugin_dir_url( DIVI_SQUAD__FILE__ );
	}

	/**
	 * Get the plugin icon path.
	 *
	 * @return string
	 */
	public function get_icon_path() {
		return trailingslashit( $this->get_path() ) . 'build/admin/icons';
	}

	/**
	 * Retrieve the WordPress root path.
	 *
	 * @return string
	 */
	public function get_wp_path() {
		return trailingslashit( ABSPATH );
	}

	/**
	 * Retrieve the plugin basename of the premium version.
	 *
	 * @return string
	 */
	public function get_pro_basename() {
		// Premium plugin path.
		return 'squad-modules-pro-for-divi/squad-modules-pro-for-divi.php';
	}

	/**
	 * Check if the pro version is activated.
	 *
	 * @return bool
	 */
	public function is_pro_activated() {
		static $pro_is_installed = null;

		if ( null === $pro_is_installed ) {
			$pro_is_installed = WP::is_plugin_active( $this->get_pro_basename() );
		}

		return $pro_is_installed;
	}

	/**
	 * Initialize the memory.
	 *
	 * @return void
	 */
	public function init_memory() {
		$this->container['memory'] = new Base\Memory( $this->opt_prefix );
	}

	/**
	 * Initialize the plugin.
	 *
	 * @return void
	 */
	public function init_plugin() {
		$options = $this->get_plugin_data( DIVI_SQUAD__FILE__ );
		if ( ! $options ) {
			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'SQUAD ERROR: Failed to retrieve plugin data.' );
			// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.PHP.DevelopmentFunctions.error_log_error_log

			// Send an error report.
			ErrorReport::quick_send(
				new \Exception( 'Failed to retrieve plugin data.' ),
				array(
					'additional_info' => 'An error message from plugin initialization.',
				)
			);
			return;
		}

		$this->opt_prefix    = 'disq';
		$this->textdomain    = ! empty( $options['TextDomain'] ) ? $options['TextDomain'] : 'squad-modules-for-divi';
		$this->version       = ! empty( $options['Version'] ) ? $options['Version'] : '3.0.0';
		$this->name          = $this->textdomain;
		$this->options       = wp_parse_args( $options, array( 'RequiresDIVI' => '4.14.0' ) );
		$this->localize_path = $this->get_path( '/languages' );
	}

	/**
	 * Load the plugin.
	 *
	 * @return void
	 */
	public function run() {
		try {
			$this->init();

			$wp = Integrations\WP::get_instance();
			$wp->set_options( $this->options );

			if ( $wp->let_the_journey_start() ) {
				$this->load_components();
			}

			/**
			 * Fires after the plugin is loaded.
			 *
			 * @since 3.1.0
			 *
			 * @param SquadModules $instance The SquadModules instance.
			 */
			do_action( 'divi_squad_loaded', $this );
		} catch ( \Exception $e ) {
			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'SQUAD ERROR: Error running SquadModules: ' . $e->getMessage() );
			// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.PHP.DevelopmentFunctions.error_log_error_log

			// Send an error report.
			ErrorReport::quick_send(
				$e,
				array(
					'additional_info' => 'An error message from plugin runner.',
				)
			);
		}
	}

	/**
	 * Load all plugin components.
	 *
	 * @return void
	 */
	private function load_components() {
		try {
			$this->load_assets();
			$this->load_global_assets();
			$this->load_extensions();
			$this->load_modules_for_builder();
			$this->load_admin();
			$this->localize_scripts_data();

			/**
			 * Fires after the plugin components are loaded.
			 *
			 * @since 3.1.0
			 *
			 * @param SquadModules $instance The SquadModules instance.
			 */
			do_action( 'divi_squad_load_components', $this );
		} catch ( \Exception $e ) {
			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'SQUAD ERROR: Error loading plugin components: ' . $e->getMessage() );
			// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.PHP.DevelopmentFunctions.error_log_error_log

			// Send an error report.
			ErrorReport::quick_send(
				$e,
				array(
					'additional_info' => 'An error message from plugin components loader.',
				)
			);
		}
	}

	/**
	 * Load additional components after the plugin has been initialized.
	 *
	 * @return void
	 * @throws \Exception If the class file is not found.
	 */
	public function load_additional_components() {
		try {
			Base\DiviBuilder\Utils\Elements\CustomFields::init();

			/**
			 * Fires after the plugin additional components are loaded.
			 *
			 * @since 3.1.0
			 *
			 * @param SquadModules $instance The SquadModules instance.
			 */
			do_action( 'divi_squad_load_additional_components', $this );
		} catch ( \Exception $e ) {
			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'SQUAD ERROR: Error loading additional components: ' . $e->getMessage() );
			// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.PHP.DevelopmentFunctions.error_log_error_log

			// Send an error report.
			ErrorReport::quick_send(
				$e,
				array(
					'additional_info' => 'An error message from additional components loader.',
				)
			);
		}
	}

	/**
	 * Get the list of deprecated classes and their configurations.
	 *
	 * @return array
	 */
	protected function get_deprecated_classes_list() {
		return array(
			'DiviSquad\Admin\Assets'                      => array(),
			'DiviSquad\Admin\Plugin\AdminFooterText'      => array(),
			'DiviSquad\Admin\Plugin\ActionLinks'          => array(),
			'DiviSquad\Admin\Plugin\RowMeta'              => array(),
			'DiviSquad\Base\DiviBuilder\DiviSquad_Module' => array(
				'action' => array(
					'name'     => 'divi_extensions_init',
					'priority' => 9,
				),
			),
			'DiviSquad\Base\DiviBuilder\IntegrationAPIBase' => array(),
			'DiviSquad\Base\DiviBuilder\IntegrationAPI'   => array(),
			'DiviSquad\Base\DiviBuilder\Utils\UtilsInterface' => array(),
			'DiviSquad\Base\Factories\AdminMenu\MenuCore' => array(),
			'DiviSquad\Base\Factories\BrandAsset\BrandAsset' => array(),
			'DiviSquad\Base\Factories\BrandAsset\BrandAssetInterface' => array(),
			'DiviSquad\Base\Factories\PluginAsset\PluginAsset' => array(),
			'DiviSquad\Base\Factories\PluginAsset\PluginAssetInterface' => array(),
			'DiviSquad\Integrations\Admin'                => array(),
			'DiviSquad\Managers\Assets'                   => array(),
			'DiviSquad\Managers\Extensions'               => array(),
			'DiviSquad\Managers\Modules'                  => array(),
			'DiviSquad\Modules\PostGridChild\PostGridChild' => array(
				'action' => array(
					'name'     => 'divi_extensions_init',
					'priority' => 9,
				),
			),
		);
	}
}
