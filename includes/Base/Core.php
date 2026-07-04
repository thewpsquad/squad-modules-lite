<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName, WordPress.Files.FileName.NotHyphenatedLowercase

/**
 * The Core class.
 *
 * @package DiviSquad
 * @author  WP Squad <support@squadmodules.com>
 * @since   1.0.0
 */

namespace DiviSquad\Base;

use DiviSquad\Utils\Divi;
use DiviSquad\Utils\Media\Filesystem;
use DiviSquad\Utils\Media\Image;
use function add_action;
use function apply_filters;
use function divi_squad;
use function esc_attr;
use function esc_url;
use function is_admin;
use function is_multisite;
use function is_wp_error;
use function load_plugin_textdomain;
use function wp_json_encode;
use function wp_kses_data;

/**
 * The Base class for Core
 *
 * @package DiviSquad
 * @since   1.0.0
 */
abstract class Core extends Filesystem {

	/**
	 * The plugin admin menu slug.
	 *
	 * @var string
	 */
	protected $admin_menu_slug = '';

	/**
	 * The plugin options.
	 *
	 * @var array
	 */
	protected $options = array();

	/**
	 * The Plugin name.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * The Plugin Text Domain.
	 *
	 * @var string
	 */
	protected $textdomain;

	/**
	 * The Plugin Version.
	 *
	 * @since 1.4.5
	 *
	 * @var string
	 */
	protected $version;

	/**
	 * The plugin option prefix
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $opt_prefix;

	/**
	 * The Script handle the text domain will be attached to.
	 *
	 * @var string
	 */
	protected $localize_handle;

	/**
	 * The full file path to the directory containing translation files.
	 *
	 * @var string
	 */
	protected $localize_path;

	/**
	 * List of containers
	 *
	 * @var array
	 */
	protected $container = array();

	/**
	 * Initialize the plugin with required components.
	 *
	 * @param array $options Options.
	 *
	 * @return void
	 */
	abstract protected function init( $options = array() );

	/**
	 * Get the plugin options.
	 *
	 * @return array
	 */
	abstract public function get_options();

	/**
	 * Get the plugin version.
	 *
	 * @return string
	 */
	abstract public function get_version();

	/**
	 * Get the plugin version (doted).
	 *
	 * @return string
	 */
	abstract public function get_version_dot();

	/**
	 * Set the activation hook.
	 *
	 * @return void
	 */
	abstract public function hook_activation();

	/**
	 * Load all assets.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	protected function load_assets() {}

	/**
	 * Load all extensions.
	 *
	 * @return void
	 */
	abstract protected function load_extensions();

	/**
	 * Load all divi modules.
	 *
	 * @return void
	 */
	abstract protected function load_modules_for_builder();

	/**
	 * Get the plugin name.
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Get the plugin text domain.
	 *
	 * @return string
	 */
	public function get_textdomain() {
		return $this->textdomain;
	}

	/**
	 * The full file path to the directory containing translation files.
	 *
	 * @return string
	 */
	public function get_localize_path() {
		return $this->localize_path;
	}

	/**
	 * Get the plugin admin menu slug.
	 *
	 * @return string
	 */
	public function get_admin_menu_slug() {
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
	 * Get the plugin option prefix.
	 *
	 * @return string
	 */
	public function get_option_prefix() {
		return $this->opt_prefix;
	}

	/**
	 * Load the local text domain.
	 *
	 * @return void
	 */
	public function load_text_domain() {
		load_plugin_textdomain( $this->textdomain, false, "{$this->name}/languages" );
	}

	/**
	 * Load css variables in the admin panel.
	 *
	 * @return void
	 */
	public function wp_hook_enqueue_admin_scripts() {
		$logo_css_selector = '';
		$is_maybe_admin    = is_admin();
		$is_visual_builder = Divi::is_fb_enabled() || Divi::is_bfb_enabled() || Divi::is_tb_admin_screen();

		if ( $is_maybe_admin || $is_visual_builder ) {
			if ( $is_maybe_admin ) {
				$logo_css_selector .= '#toplevel_page_divi_squad_dashboard div.wp-menu-image:before';
			}

			if ( '' !== $logo_css_selector && $is_visual_builder ) {
				$logo_css_selector .= ', ';
			}

			if ( $is_visual_builder ) {
				$logo_css_selector .= '.et-fb-settings-options-tab.et-fb-modules-list ul li.et_fb_divi_squad_modules.et_pb_folder';
			}

			// Start style tag and class selector.
			printf( '<style id="divi_squad_admin_assets_backend"> %1$s {', esc_attr( $logo_css_selector ) );

			// Load the image class.
			$image = new Image( divi_squad()->get_path( '/build/admin/images/logos' ) );

			// Get the image.
			$squad_image = $image->get_image( 'divi-squad-d-default.png', 'png' );
			if ( is_wp_error( $squad_image ) ) {
				return;
			}

			// Define all css variables for logos.
			printf( '--squad-brand-logo: url("%s");', esc_url( $squad_image, array( 'data' ) ) );

			// End class selector and style tag.
			print '} </style>';
		}
	}

	/**
	 * The admin interface asset and others.
	 *
	 * @return void
	 */
	protected function load_global_assets() {
		add_action( 'admin_enqueue_scripts', array( $this, 'wp_hook_enqueue_admin_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'wp_hook_enqueue_admin_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'wp_hook_enqueue_scripts' ) );
	}

	/**
	 * Set the localize data.
	 *
	 * @return void
	 */
	public function localize_scripts_data() {
		add_action( 'admin_enqueue_scripts', array( $this, 'wp_hook_enqueue_localize_data' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'wp_hook_enqueue_localize_data' ) );
	}

	/**
	 * Load css variables in the frontend.
	 *
	 * @return void
	 */
	public function wp_hook_enqueue_scripts() {}

	/**
	 * Load the localized data in the frontend and admin panel.
	 *
	 * @return void
	 */
	public function wp_hook_enqueue_localize_data() {
		global $wp_version;

		// Default data for asset backend.
		$assets_backend_data_defaults = array(
			'site_url'   => home_url(),
			'site_type'  => is_multisite() ? 'multi' : 'default',
			'wp_version' => $wp_version,
		);

		/**
		 * Filter the extra data for the backend assets.
		 *
		 * @since 1.0.0
		 *
		 * @param array $assets_backend_data_defaults The default data for the backend assets.
		 *
		 * @return array
		 */
		$assets_backend_data = apply_filters( 'divi_squad_assets_backend_extra_data', $assets_backend_data_defaults );

		// Generate the extra data for the backend assets.
		$assets_backend_extra = sprintf( 'window.DiviSquadExtra = %1$s;', wp_json_encode( $assets_backend_data ) );

		/**
		 * Filter the extra data for the backend assets.
		 *
		 * @since 1.0.0
		 *
		 * @param string $assets_backend_extra The extra data for the backend assets.
		 *
		 * @return string
		 */
		$assets_backend_extra = apply_filters( 'divi_squad_assets_backend_extra', $assets_backend_extra );

		// Generate the localize data for the frontend assets.
		printf(
			'<script type="text/javascript" id="divi_squad_assets_data_frontend"> %s </script>',
			wp_kses_data( $assets_backend_extra )
		);
	}

	/**
	 * Localizes a script.
	 *
	 * Works only if the script has already been registered.
	 *
	 * @param string $object_name Name for the JavaScript object. Passed directly, so it should be qualified JS variable.
	 * @param array  $l10n        The data itself. The data can be either a single or multidimensional array.
	 *
	 * @return string Localizes a script.
	 */
	public function localize_script( $object_name, $l10n ) {
		return sprintf( 'window.%1$s = %2$s;', $object_name, wp_json_encode( $l10n ) );
	}

	/**
	 * Resolve the plugin data.
	 *
	 * @param string $plugin_file Absolute path to the main plugin file.
	 *
	 * @return array
	 * @throws \RuntimeException If the plugin file does not exist or the function cannot be included.
	 */
	protected function get_plugin_data( $plugin_file ) {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			$plugin_path = $this->get_wp_path() . 'wp-admin/includes/plugin.php';

			if ( file_exists( $plugin_path ) ) {
				require_once $plugin_path;
			} else {
				throw new \RuntimeException( "The 'wp-admin/includes/plugin.php' file loading failed. Cannot retrieve plugin data." );
			}
		}

		return get_plugin_data( $plugin_file, false, false );
	}

	/**
	 * Set the plugin options.
	 *
	 * @param string $key The key to set.
	 *
	 * @return bool
	 */
	public function __isset( $key ) {
		return isset( $this->container[ $key ] );
	}

	/**
	 * Set the plugin options.
	 *
	 * @param string $key The key to set.
	 *
	 * @return mixed
	 */
	public function __get( $key ) {
		if ( array_key_exists( $key, $this->container ) ) {
			return $this->container[ $key ];
		}

		return new \stdClass();
	}

	/**
	 * Set the plugin options.
	 *
	 * @param string $key The key to set.
	 * @param mixed  $value The value to set.
	 *
	 * @return void
	 */
	public function __set( $key, $value ) {
		$this->container[ $key ] = $value;
	}
}
