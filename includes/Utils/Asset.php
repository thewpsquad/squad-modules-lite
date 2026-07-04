<?php
/**
 * Asset loading helper class for enqueuing scripts and styles.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   1.0.0
 */

namespace DiviSquad\Utils;

use DiviSquad\Core\Supports\Polyfills\Str;
use function divi_squad;
use function wp_enqueue_script;
use function wp_enqueue_style;
use function wp_parse_args;
use function wp_register_style;
use function wp_script_is;

/**
 * Utils class.
 *
 * @package DiviSquad
 * @since   1.0.0
 */
class Asset {

	/**
	 * Get the version
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function get_the_version() {
		return divi_squad()->get_version();
	}

	/**
	 * Resolve the resource root path.
	 *
	 * @return string
	 * @phpstan-return string|null
	 */
	public static function root_path() {
		return divi_squad()->get_path();
	}

	/**
	 * Resolve the resource root uri.
	 *
	 * @return string
	 */
	public static function root_path_uri() {
		return divi_squad()->get_url();
	}

	/**
	 * Get current mode is production or not
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function is_production_mode(): bool {
		return strpos( static::get_the_version(), '.' );
	}

	/**
	 * Validate the relative path.
	 *
	 * @param string $relative_path The path string for validation.
	 *
	 * @return string
	 */
	public static function validate_relative_path( string $relative_path ): string {
		if ( Str::starts_with( $relative_path, './' ) ) {
			$relative_path = str_replace( './', '/', $relative_path );
		}

		if ( ! Str::starts_with( $relative_path, '/' ) ) {
			$relative_path = sprintf( '/%1$s', $relative_path );
		}

		return $relative_path;
	}

	/**
	 * Resolve the resource path.
	 *
	 * @param string $relative_path The current path string.
	 *
	 * @return string
	 */
	public static function resolve_file_path( string $relative_path ): string {
		return sprintf( '%1$s/%2$s', static::root_path(), static::validate_relative_path( $relative_path ) );
	}

	/**
	 * Resolve the resource uri.
	 *
	 * @param string $relative_path The current path string.
	 *
	 * @return string
	 */
	public static function resolve_file_uri( string $relative_path ): string {
		return sprintf( '%1$s%2$s', static::root_path_uri(), static::validate_relative_path( $relative_path ) );
	}

	/**
	 * Process asset path and version data.
	 *
	 * @param array $path         The asset relative path with options.
	 * @param array $dependencies The asset dependencies.
	 *
	 * @return array
	 */
	public static function process_asset_path_data( array $path, array $dependencies ): array {
		$full_path   = '';
		$pattern     = ! empty( $path['pattern'] ) ? $path['pattern'] : 'build/[path_prefix]/[file].[ext]';
		$path_prefix = ! empty( $path['path'] ) ? $path['path'] : 'divi-builder-4';
		$extension   = ! empty( $path['ext'] ) ? $path['ext'] : 'js';

		// Update path.
		$path_prefix .= 'js' === $extension ? '/scripts' : '/styles';

		if ( empty( $path['file'] ) ) {
			return array(
				'path'         => '',
				'version'      => '',
				'dependencies' => '',
			);
		}

		// Default file for development and production.
		$the_file = $path['file'];

		// Load alternative production file when found.
		if ( ! empty( $path['prod_file'] ) && static::is_production_mode() ) {
			$the_file = $path['prod_file'];
		}

		// Load alternative development file when found.
		if ( ! empty( $path['dev_file'] ) && ! static::is_production_mode() ) {
			$the_file = $path['dev_file'];
		}

		// The validated path of default file.
		$path_clean    = str_replace( array( '[path_prefix]', '[file]', '[ext]' ), array( $path_prefix, $the_file, $extension ), $pattern );
		$path_validate = static::validate_relative_path( $path_clean );
		$version       = static::get_the_version();

		if ( in_array( $extension, array( 'js', 'css' ), true ) ) {
			// Check for the minified version in the server on production mode.
			$minified_asset_file     = str_replace( array( ".$extension" ), array( ".min.$extension" ), $path_validate );
			$is_minified_asset_file  = Str::ends_with( $path_validate, ".min.$extension" );
			$is_minified_asset_found = file_exists( static::resolve_file_path( $minified_asset_file ) );
			if ( ! $is_minified_asset_file && $is_minified_asset_found && Str::ends_with( $path_validate, ".$extension" ) ) {
				$path_validate = $minified_asset_file;
			}

			// Load the version and dependencies data for javascript file.
			$new_dependencies = array();
			if ( 'js' === $extension ) {
				// Verify that the current file is a minified and located in the current physical device.
				if ( Str::ends_with( $path_validate, ".min.$extension" ) && file_exists( static::resolve_file_path( $path_validate ) ) ) {
					$minified_version_file = str_replace( array( ".min.$extension" ), array( '.min.asset.php' ), $path_validate );
					if ( file_exists( static::resolve_file_path( $minified_version_file ) ) ) {
						$minified_asset   = include static::resolve_file_path( $minified_version_file );
						$version          = ! empty( $minified_asset['version'] ) ? $minified_asset['version'] : $version;
						$new_dependencies = ! empty( $minified_asset['dependencies'] ) ? $minified_asset['dependencies'] : array();
					}
				}

				// Verify that the current file is non-minified and located in the current physical device.
				if ( Str::ends_with( $path_validate, ".$extension" ) && file_exists( static::resolve_file_path( $path_validate ) ) ) {
					$main_version_file = str_replace( array( ".$extension" ), array( '.asset.php' ), $path_validate );
					if ( Str::ends_with( $main_version_file, '.asset.php' ) && file_exists( static::resolve_file_path( $main_version_file ) ) ) {
						$main_asset       = include static::resolve_file_path( $main_version_file );
						$version          = ! empty( $main_asset['version'] ) ? $main_asset['version'] : $version;
						$new_dependencies = ! empty( $main_asset['dependencies'] ) ? $main_asset['dependencies'] : array();
					}
				}
			}

			$dependencies = array_merge( $new_dependencies, $dependencies );
		}

		// Collect actual path for the current asset file.
		$plugin_url_root = untrailingslashit( static::root_path_uri() );
		$full_path       = "{$plugin_url_root}{$path_validate}";

		// Clean the dependencies if is not empty.
		$dependencies = array_unique( array_filter( $dependencies ) );

		// Enqueue the JSX Runtime script for WordPress 6.6 and above.
		if ( ! empty( $dependencies ) && in_array( 'react-jsx-runtime', $dependencies, true ) ) {
			if ( ! wp_script_is( 'react-jsx-runtime', 'registered' ) ) {
				$dependency_index = array_search( 'react-jsx-runtime', $dependencies, true );
				unset( $dependencies[ $dependency_index ] );

				$jsx_runtime = static::asset_path( 'react-jsx-runtime', array( 'path' => 'compat' ) );
				static::enqueue_script( 'react-jsx-runtime', $jsx_runtime, array( 'react' ) );
			}
		}

		/**
		 * Action hook to add more dependencies for the asset.
		 *
		 * @since 3.1.4
		 *
		 * @param array  $dependencies The asset dependencies.
		 * @param string $path         The asset path.
		 * @param string $full_path    The full asset path.
		 */
		do_action( 'divi_squad_asset_dependencies', $dependencies, $path, $full_path );

		/**
		 * Filter the asset dependencies.
		 *
		 * @since 3.1.4
		 *
		 * @param array  $dependencies The asset dependencies.
		 * @param string $path         The asset path.
		 * @param string $full_path    The full asset path.
		 */
		$dependencies = apply_filters( 'divi_squad_asset_dependencies', $dependencies, $path, $full_path );

		$asset_data = array(
			'path'         => $full_path,
			'version'      => $version,
			'dependencies' => $dependencies,
		);

		/**
		 * Filter the asset data.
		 *
		 * @since 3.1.4
		 *
		 * @param array  $asset_data The asset data.
		 * @param string $path       The asset path.
		 * @param string $full_path  The full asset path.
		 */
		return apply_filters( 'divi_squad_asset_data', $asset_data, $path, $full_path );
	}

	/**
	 * Set the asset path.
	 *
	 * @param string $file    The file name.
	 * @param array  $options The options for current asset file.
	 *
	 * @return array
	 */
	public static function asset_path( string $file, array $options = array() ): array {
		$defaults = array(
			'pattern'   => 'build/[path_prefix]/[file].[ext]',
			'file'      => $file,
			'dev_file'  => '',
			'prod_file' => '',
			'path'      => '',
			'ext'       => 'js',
		);

		return wp_parse_args( $options, $defaults );
	}

	/**
	 * Get the admin asset path.
	 *
	 * @param string $file    The file name.
	 * @param array  $options The options for current asset file.
	 *
	 * @return array
	 */
	public static function admin_asset_path( string $file, array $options = array() ): array {
		$defaults = array(
			'path' => 'admin',
		);

		return self::asset_path( $file, wp_parse_args( $options, $defaults ) );
	}

	/**
	 * Get the module asset path.
	 *
	 * @param string $file    The file name.
	 * @param array  $options The options for current asset file.
	 *
	 * @return array
	 */
	public static function module_asset_path( string $file, array $options = array() ): array {
		$defaults = array(
			'path' => 'divi-builder-4',
		);

		return self::asset_path( $file, wp_parse_args( $options, $defaults ) );
	}

	/**
	 * Get the extensions asset path.
	 *
	 * @param string $file    The file name.
	 * @param array  $options The options for current asset file.
	 *
	 * @return array
	 */
	public static function extension_asset_path( string $file, array $options = array() ): array {
		$defaults = array(
			'path' => 'extensions',
		);

		return self::asset_path( $file, wp_parse_args( $options, $defaults ) );
	}

	/**
	 * Get the vendor asset path.
	 *
	 * @param string $file    The file name.
	 * @param array  $options The options for current asset file.
	 *
	 * @return array
	 */
	public static function vendor_asset_path( string $file, array $options = array() ): array {
		$defaults = array(
			'path' => 'vendor',
		);

		return self::asset_path( $file, wp_parse_args( $options, $defaults ) );
	}

	/**
	 * Enqueue styles (deprecated).
	 *
	 * @since 1.0.0
	 *
	 * @param string $keyword   Name of the stylesheet. Should be unique.
	 * @param array  $path      Relative path of the stylesheet with options for the WordPress root directory.
	 * @param array  $deps      Optional. An array of registered stylesheet handles this stylesheet depends on. Default empty array.
	 * @param string $media     Optional. The media for which this stylesheet has been defined. Default 'all'.
	 * @param bool   $no_prefix Optional. Set the plugin prefix with asset handle name is or not.
	 *
	 * @return void
	 * @deprecated 3.1.0
	 */
	public static function style_enqueue( string $keyword, array $path, array $deps = array(), string $media = 'all', bool $no_prefix = false ) {
		self::enqueue_style( $keyword, $path, $deps, $media, $no_prefix );
	}

	/**
	 * Enqueue javascript (deprecated).
	 *
	 * @since 1.0.0
	 *
	 * @param string $keyword   Name of the javascript. Should be unique.
	 * @param array  $path      Relative path of the javascript with options for the WordPress root directory.
	 * @param array  $deps      Optional. An array of registered javascript handles this stylesheet depends on. Default empty array.
	 * @param bool   $no_prefix Optional. Set the plugin prefix with asset handle name is or not.
	 *
	 * @return void
	 * @deprecated 3.1.0
	 */
	public static function asset_enqueue( string $keyword, array $path, array $deps = array(), bool $no_prefix = false ) {
		self::enqueue_script( $keyword, $path, $deps, $no_prefix );
	}

	/**
	 * Enqueue javascript.
	 *
	 * @since 1.0.0
	 *
	 * @param string $keyword   Name of the javascript. Should be unique.
	 * @param array  $path      Relative path of the javascript with options for the WordPress root directory.
	 * @param array  $deps      Optional. An array of registered javascript handles this stylesheet depends on. Default empty array.
	 * @param bool   $no_prefix Optional. Set the plugin prefix with asset handle name is or not.
	 *
	 * @return void
	 */
	public static function enqueue_script( string $keyword, array $path, array $deps = array(), bool $no_prefix = false ) {
		$asset_data = self::process_asset_path_data( $path, $deps );
		$handle     = $no_prefix ? $keyword : sprintf( 'squad-%1$s', $keyword );
		$version    = ! empty( $asset_data['version'] ) ? $asset_data['version'] : static::get_the_version();

		// Load script file.
		wp_enqueue_script( $handle, $asset_data['path'], $asset_data['dependencies'], $version, self::footer_arguments( true ) );
	}

	/**
	 * Enqueue styles.
	 *
	 * @since 1.0.0
	 *
	 * @param string $keyword   Name of the stylesheet. Should be unique.
	 * @param array  $path      Relative path of the stylesheet with options for the WordPress root directory.
	 * @param array  $deps      Optional. An array of registered stylesheet handles this stylesheet depends on. Default empty array.
	 * @param string $media     Optional. The media for which this stylesheet has been defined. Default 'all'.
	 * @param bool   $no_prefix Optional. Set the plugin prefix with asset handle name is or not.
	 *
	 * @return void
	 */
	public static function enqueue_style( string $keyword, array $path, array $deps = array(), string $media = 'all', bool $no_prefix = false ) {
		$asset_data = static::process_asset_path_data( $path, $deps );
		$handle     = $no_prefix ? $keyword : sprintf( 'squad-%1$s', $keyword );
		$version    = ! empty( $asset_data['version'] ) ? $asset_data['version'] : static::get_the_version();

		// Load stylesheet file.
		wp_enqueue_style( $handle, $asset_data['path'], $asset_data['dependencies'], $version, $media );
	}

	/**
	 * Register scripts for frontend and builder.
	 *
	 * @param string $handle The handle name.
	 * @param array  $path   The script path url with options.
	 * @param array  $deps   The script dependencies.
	 *
	 * @return void
	 */
	public static function register_script( string $handle, array $path, array $deps = array() ) {
		$asset_data = self::process_asset_path_data( $path, $deps );
		$handle     = sprintf( 'squad-%1$s', $handle );
		$version    = ! empty( $asset_data['version'] ) ? $asset_data['version'] : static::get_the_version();

		wp_register_script( $handle, $asset_data['path'], $asset_data['dependencies'], $version, self::footer_arguments( true ) );
	}

	/**
	 * Enqueue styles.
	 *
	 * @since 1.0.0
	 *
	 * @param string $keyword Name of the stylesheet. Should be unique.
	 * @param array  $path    Relative path of the stylesheet with options for the WordPress root directory.
	 * @param array  $deps    Optional. An array of registered stylesheet handles this stylesheet depends on. Default empty array.
	 * @param string $media   Optional. The media for which this stylesheet has been defined. Default 'all'.
	 *
	 * @return void
	 */
	public static function register_style( string $keyword, array $path, array $deps = array(), string $media = 'all' ) {
		$asset_data = static::process_asset_path_data( $path, $deps );
		$handle     = sprintf( 'squad-%1$s', $keyword );
		$version    = ! empty( $asset_data['version'] ) ? $asset_data['version'] : static::get_the_version();

		// Load stylesheet file.
		wp_register_style( $handle, $asset_data['path'], $asset_data['dependencies'], $version, $media );
	}

	/**
	 * Get available script enqueue footer arguments.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $strategy Optional. If provided, may be either 'defer' or 'async'. Default false.
	 * @param bool $priority Optional. If provided, may be either 'high' or 'low'. Default 'low'.
	 *
	 * @return array
	 */
	public static function footer_arguments( bool $strategy = false, bool $priority = false ): array {
		$footer_arguments = array(
			'in_footer' => true,
		);

		if ( $strategy ) {
			$footer_arguments['strategy'] = 'defer';
		}

		if ( $priority ) {
			$footer_arguments['fetchpriority'] = 'high';
		}

		return $footer_arguments;
	}
}
