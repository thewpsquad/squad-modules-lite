<?php
/**
 * The Core class.
 *
 * @since      1.0.0
 * @deprecated 3.3.0
 * @package    DiviSquad
 * @author     The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Base;

use function add_action;
use function apply_filters;
use function load_plugin_textdomain;
use function wp_json_encode;

/**
 * The Base class for Core
 *
 * @since      1.0.0
 * @deprecated 3.3.0
 * @package    DiviSquad
 */
abstract class Core {

	/**
	 * The plugin admin menu slug.
	 *
	 * @var string
	 */
	protected string $admin_menu_slug = '';

	/**
	 * The plugin options.
	 *
	 * @var array
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
	 * The plugin option prefix
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected string $opt_prefix;

	/**
	 * The Script handle the text domain will be attached to.
	 *
	 * @var string
	 */
	protected string $localize_handle;

	/**
	 * The full file path to the directory containing translation files.
	 *
	 * @var string
	 */
	protected string $localize_path;

	/**
	 * List of containers
	 *
	 * @var array
	 */
	protected array $container = array();

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
	 * Get the plugin name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Get the plugin text domain.
	 *
	 * @return string
	 */
	public function get_textdomain(): string {
		return $this->textdomain;
	}

	/**
	 * The full file path to the directory containing translation files.
	 *
	 * @return string
	 */
	public function get_localize_path(): string {
		return $this->localize_path;
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

	public function get_admin_menu_position() {
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
	 * Get the plugin option prefix.
	 *
	 * @return string
	 */
	public function get_option_prefix(): string {
		return $this->opt_prefix;
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
	 * Load css variables in the admin panel.
	 *
	 * @return void
	 */
	public function wp_hook_enqueue_admin_scripts() {}

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
	public function wp_hook_enqueue_localize_data() {}

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
	public function localize_script( string $object_name, array $l10n ): string {
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
	protected function get_plugin_data( string $plugin_file ): array {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			$plugin_path = ABSPATH . '/wp-admin/includes/plugin.php';

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
	public function __isset( string $key ) {
		return isset( $this->container[ $key ] );
	}

	/**
	 * Set the plugin options.
	 *
	 * @param string $key The key to set.
	 *
	 * @return mixed
	 */
	public function __get( string $key ) {
		if ( array_key_exists( $key, $this->container ) ) {
			return $this->container[ $key ];
		}

		return new \stdClass();
	}

	/**
	 * Set the plugin options.
	 *
	 * @param string $key   The key to set.
	 * @param mixed  $value The value to set.
	 *
	 * @return void
	 */
	public function __set( string $key, $value ) {
		$this->container[ $key ] = $value;
	}
}
