<?php // phpcs:ignore WordPress.Files.FileName
/**
 * Pluggable Trait
 *
 * @package DiviSquad\Core\Traits\Plugin
 * @since   1.0.0
 */

namespace DiviSquad\Core\Traits\Plugin;

use RuntimeException;

/**
 * Pluggable Trait
 *
 * This trait provides methods to manage plugin data, options, and versioning.
 *
 * @since   1.0.0
 */
trait Pluggable {

	/**
	 * Resolve the plugin data.
	 *
	 * @param string $plugin_file Absolute path to the main plugin file.
	 *
	 * @return array<string, bool|string> Parses the plugin contents to retrieve plugin's metadata.
	 * @throws RuntimeException If the plugin file does not exist or the function cannot be included.
	 */
	public function get_plugin_data( string $plugin_file ): array {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			$plugin_path = $this->get_wp_path() . 'wp-admin/includes/plugin.php';

			if ( $this->get_wp_fs()->exists( $plugin_path ) ) {
				require_once $plugin_path;
			} else {
				throw new RuntimeException( "The 'wp-admin/includes/plugin.php' file loading failed. Cannot retrieve plugin data." );
			}
		}

		return get_plugin_data( $plugin_file, false, false );
	}

	/**
	 * Get the plugin options.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return array<string, string>
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
	 * @param string              $key           The option key.
	 * @param string|integer|bool $default_value The default value if the option doesn't exist.
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
		return $this->is_dev() ? (string) time() : $this->version;
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
	 * Get the plugin languages path.
	 *
	 * @since  3.2.3
	 * @access public
	 *
	 * @return string
	 */
	public function get_languages_path(): string {
		return $this->get_path( '/languages' );
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
