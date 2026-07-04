<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Integration API Base
 *
 * @since      1.0.0
 * @deprecated 3.3.0
 * @package    DiviSquad
 * @author     The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Base\DiviBuilder;

/**
 * Integration API Base Class.
 *
 * @since      1.0.0
 * @deprecated 3.3.0
 * @package    DiviSquad
 */
abstract class Integration {

	/**
	 * The plugin name.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected string $name = '';

	/**
	 * Absolute path to the plugin's directory.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected string $plugin_dir = '';

	/**
	 * The plugin's directory URL.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected string $plugin_dir_url = '';

	/**
	 * The plugin's version
	 *
	 * @since 1.0.0
	 *
	 * @var string The plugin's version
	 */
	protected string $version = '';

	/**
	 * The asset build for the plugin
	 *
	 * @since 1.0.0
	 *
	 * @var string The plugin's version
	 */
	protected string $build_path = 'build/divi-builder-4/';

	/**
	 * Constructor.
	 *
	 * @param string $name           The plugin's WP Plugin name.
	 * @param string $plugin_dir     Absolute path to the plugin's directory.
	 * @param string $plugin_dir_url The plugin's directory URL.
	 */
	public function __construct( string $name, string $plugin_dir, string $plugin_dir_url ) {
		// Set required variables as per definition.
		$this->name           = $name;
		$this->plugin_dir     = $plugin_dir;
		$this->plugin_dir_url = $plugin_dir_url;

		$this->initialize();
	}

	/**
	 * Performs initialization tasks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	abstract public function initialize();

	/**
	 * Get the plugin version number
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	abstract public function get_version();
}
