<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Pluggable Trait
 *
 * @since   1.0.0
 * @package DiviSquad
 */

namespace DiviSquad\Core\Traits\Plugin;

use DiviSquad\Core\Supports\Media\Image;
use RuntimeException;
use Throwable;

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
			$plugin_path = divi_squad()->get_wp_path( 'wp-admin/includes/plugin.php' );

			if ( $this->get_wp_fs()->exists( $plugin_path ) ) {
				require_once $plugin_path;
			} else {
				throw new RuntimeException( "The 'wp-admin/includes/plugin.php' file loading failed. Cannot retrieve plugin data." );
			}
		}

		return get_plugin_data( $plugin_file, false, false );
	}

	/**
	 * Get the instance of Image.
	 *
	 * @param string $path The path to append to the plugin directory.
	 *
	 * @return Image
	 */
	public function load_image( string $path ): Image {
		return new Image( $this->get_path( $path ) );
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
	 * Get the plugin version number (hyphenated).
	 *
	 * @since  3.3.3
	 * @access public
	 *
	 * @return string
	 */
	public function get_version_hyphen(): string {
		return str_replace( '.', '-', $this->get_version_dot() );
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
	 * @param string $path Relative path.
	 *
	 * @return string
	 */
	public function get_wp_path( string $path = '' ): string {
		return trailingslashit( ABSPATH ) . $path;
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
			 * @param string $template_path The template path.
			 * @param string $path          The path to append.
			 * @param self   $plugin        The plugin instance.
			 */
			return apply_filters( 'divi_squad_template_path', $this->get_path( '/templates/' . $path ), $path, $this );
		} catch ( Throwable $e ) {
			$this->log_error( $e, 'Failed to get template path' );

			return $this->get_path( '/templates/' . $path );
		}
	}

	/**
	 * Verify existence of the template path
	 *
	 * @since  3.3.3
	 * @access public
	 *
	 * @param string $template The template name.
	 *
	 * @return bool
	 */
	public function is_template_exists( string $template ): bool {
		return $this->get_wp_fs()->exists( $this->get_template_path( $template ) );
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
			 * @param string $icon_path The icon path.
			 * @param string $path      The path to append.
			 * @param self   $plugin    The plugin instance.
			 */
			return apply_filters( 'divi_squad_icon_path', $this->get_path( '/build/admin/icons/' . $path ), $path, $this );
		} catch ( Throwable $e ) {
			$this->log_error( $e, 'Failed to get icon path' );

			return $this->get_path( '/build/admin/icons/' . $path );
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
			 * @param string $url    The plugin asset URL.
			 * @param string $path   The path to append.
			 * @param self   $plugin The plugin instance.
			 */
			return apply_filters( 'divi_squad_asset_url', $this->get_url( 'build/' . $path ), $path, $this );
		} catch ( Throwable $e ) {
			$this->log_error( $e, 'Failed to get asset URL' );

			return $this->get_url( 'build/' . $path );
		}
	}

	/**
	 * Get the plugin asset path.
	 *
	 * @since  3.4.0
	 * @access public
	 *
	 * @param string $path The path to append.
	 *
	 * @return string
	 */
	public function get_asset_path( string $path = '' ): string {
		try {
			/**
			 * Filter the plugin asset path.
			 *
			 * @since 3.4.0
			 *
			 * @param string $url    The plugin asset URL.
			 * @param string $path   The path to append.
			 * @param self   $plugin The plugin instance.
			 */
			return apply_filters( 'divi_squad_asset_path', $this->get_path( 'build/' . $path ), $path, $this );
		} catch ( Throwable $e ) {
			$this->log_error( $e, 'Failed to get asset path' );

			return $this->get_path( 'build/' . $path );
		}
	}

	/**
	 * Verify existence of the plugin asset
	 *
	 * @since  3.4.0
	 * @access public
	 *
	 * @param string $path The asset path
	 *
	 * @return bool
	 */
	public function is_asset_exists( string $path = '' ): bool {
		return $this->get_wp_fs()->exists( $this->get_asset_path( $path ) );
	}
}
