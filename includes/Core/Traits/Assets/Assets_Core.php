<?php
/**
 * Asset Management Trait
 *
 * Provides core functionality for handling asset dependencies, versions,
 * and file resolution based on environment mode.
 *
 * @since   3.3.0
 * @author  The WP Squad <support@squadmodules.com>
 * @package DiviSquad
 */

namespace DiviSquad\Core\Traits\Assets;

use DiviSquad\Core\Supports\Polyfills\Str;
use RuntimeException;
use Throwable;

/**
 * Asset Management Trait
 *
 * @since 3.3.0
 */
trait Assets_Core {

	/**
	 * Process asset configuration and return normalized data
	 *
	 * @param array{ file: string, path?: string, prod_file?: string, dev_file?: string, pattern?: string, ext?: string, deps?: array<string> } $config Asset configuration.
	 * @param array<string>                                                                                                                     $default_deps Default dependencies.
	 *
	 * @return array{ path: string, version: string, dependencies: array<string> } Processed asset data
	 * @throws RuntimeException If asset configuration is invalid.
	 */
	public function process_asset_config( array $config, array $default_deps = array() ): array {
		try {
			if ( ! isset( $config['file'] ) ) {
				throw new RuntimeException( 'Asset configuration must include a file name' );
			}

			// Merge provided deps with defaults.
			$deps = array_merge( $default_deps, $config['deps'] ?? array() );

			// Build file path and url.
			$path_url   = $this->resolve_asset_path( $config );
			$path_local = $this->resolve_asset_path( $config, true );

			// Get version info.
			$version = $this->resolve_asset_version( $path_local );

			// Process dependencies.
			$dependencies = $this->resolve_dependencies( $deps, $path_local );

			return array(
				'url'          => $path_url,
				'version'      => $version,
				'dependencies' => $dependencies,
			);
		} catch ( Throwable $e ) {
			throw new RuntimeException(
				sprintf( 'Failed to process asset config: %s', esc_html( $e->getMessage() ) ),
				0
			);
		}
	}

	/**
	 * Resolve the asset file path based on environment and configuration
	 *
	 * @param array{ file: string, path?: string, prod_file?: string, dev_file?: string, pattern?: string, ext?: string, deps?: array<string> } $config Asset configuration.
	 * @param bool                                                                                                                              $is_local Whether to resolve local path.
	 *
	 * @return string Resolved asset path
	 * @throws RuntimeException If path cannot be resolved.
	 */
	public function resolve_asset_path( array $config, bool $is_local = false, bool $return_path = false ): string {
		$external    = $config['external'] ?? false;
		$pattern     = $config['pattern'] ?? '[path_prefix]/[file].[ext]';
		$path_prefix = $config['path'] ?? 'divi-builder-4';
		$extension   = $config['ext'] ?? 'js';

		// Determine the correct file to use based on environment.
		$file = $this->resolve_environment_file( $config );

		if ( ! $external ) {
			$pattern      = 'build/' . $pattern;
			$path_prefix .= 'js' === $extension ? '/scripts' : '/styles';
		}

		/**
		 * Filters the asset path pattern.
		 *
		 * @since 3.3.0
		 *
		 * @param string $pattern    The asset path pattern.
		 * @param string $file       The file name.
		 * @param string $extension  The file extension.
		 */
		$path_pattern = apply_filters( 'divi_squad_asset_path_pattern', $pattern, $file, $extension );

		// Build the initial path.
		$path = str_replace(
			array( '[path_prefix]', '[file]', '[ext]' ),
			array( $path_prefix, $file, $extension ),
			$path_pattern
		);

		// Normalize path.
		$path = $this->normalize_path( $path );

		// Check for minified version in production.
		if ( $this->should_use_minified( $path, $extension ) ) {
			$minified_path = $this->get_minified_path( $path, $extension );
			if ( $this->is_valid_asset_path( $minified_path ) ) {
				$path = $minified_path;
			}
		}

		// Return the path if requested.
		if ( $external || $return_path ) {
			return $path;
		}

		if ( $is_local ) {
			return divi_squad()->get_path( ltrim( $path, '/' ) );
		}

		return divi_squad()->get_url( ltrim( $path, '/' ) );
	}

	/**
	 * Determine which file to use based on environment mode
	 *
	 * @param array{ file: string, path?: string, prod_file?: string, dev_file?: string, pattern?: string, ext?: string, deps?: array<string> } $config Asset configuration.
	 *
	 * @return string File name to use
	 */
	protected function resolve_environment_file( array $config ): string {
		$is_dev = divi_squad()->is_dev();

		if ( ! $is_dev && ! empty( $config['prod_file'] ) ) {
			return $config['prod_file'];
		}

		if ( $is_dev && ! empty( $config['dev_file'] ) ) {
			return $config['dev_file'];
		}

		return $config['file'];
	}

	/**
	 * Resolve asset version
	 *
	 * @param string $path Asset path.
	 *
	 * @return string Asset version
	 */
	protected function resolve_asset_version( string $path ): string {
		$version = divi_squad()->get_version();

		// Check for version file.
		$version_file = $this->get_version_file_path( $path );
		if ( divi_squad()->get_wp_fs()->exists( $version_file ) ) {
			$version_data = include $version_file;
			if ( isset( $version_data['version'] ) ) {
				$version = $version_data['version'];
			}
		}

		return $version;
	}

	/**
	 * Resolve and validate dependencies
	 *
	 * @param array<string> $deps List of dependencies.
	 * @param string        $path Asset path.
	 *
	 * @return array<string> Processed dependencies
	 */
	protected function resolve_dependencies( array $deps, string $path ): array {
		// Remove empty dependencies.
		$deps = array_filter( $deps );

		// Add version file dependencies if available.
		$version_file = $this->get_version_file_path( $path );

		if ( divi_squad()->get_wp_fs()->exists( $version_file ) ) {
			$version_data = include $version_file;
			if ( isset( $version_data['dependencies'] ) ) {
				$deps = array_merge( $deps, $version_data['dependencies'] );
			}
		}

		// Handle React JSX Runtime dependency.
		if ( in_array( 'react-jsx-runtime', $deps, true ) ) {
			$deps = $this->handle_jsx_runtime_dependency( $deps );
		}

		return array_unique( $deps );
	}

	/**
	 * Normalize asset path
	 *
	 * @param string $path Asset path.
	 *
	 * @return string Normalized path
	 */
	protected function normalize_path( string $path ): string {
		return wp_normalize_path( str_replace( './', '/', $path ) );
	}

	/**
	 * Check if minified version should be used
	 *
	 * @param string $path Asset path.
	 * @param string $extension File extension.
	 *
	 * @return bool True if should use minified version
	 */
	protected function should_use_minified( string $path, string $extension ): bool {
		return ! divi_squad()->is_dev()
				&& in_array( $extension, array( 'js', 'css' ), true )
				&& ! Str::ends_with( $path, ".min.{$extension}" );
	}

	/**
	 * Get minified version path
	 *
	 * @param string $path Asset path
	 * @param string $extension File extension
	 *
	 * @return string Minified file path
	 */
	protected function get_minified_path( string $path, string $extension ): string {
		return str_replace( ".{$extension}", ".min.{$extension}", $path );
	}

	/**
	 * Get version file path
	 *
	 * @param string $path Asset path.
	 *
	 * @return string Version file path
	 */
	protected function get_version_file_path( string $path ): string {
		if ( Str::ends_with( $path, '.min.js' ) ) {
			return str_replace( '.min.js', '.min.asset.php', $path );
		}
		return str_replace( array( '.js', '.css' ), '.asset.php', $path );
	}

	/**
	 * Handle React JSX Runtime dependency
	 *
	 * @param array<string> $deps Dependencies array.
	 *
	 * @return array<string> Modified dependencies
	 */
	protected function handle_jsx_runtime_dependency( array $deps ): array {
		if ( ! wp_script_is( 'react-jsx-runtime', 'registered' ) ) {
			$index = array_search( 'react-jsx-runtime', $deps, true );
			if ( false !== $index ) {
				unset( $deps[ $index ] );
			}
		}
		return array_values( $deps );
	}

	/**
	 * Validate asset path
	 *
	 * @param string $path Asset path.
	 *
	 * @return bool True if path is valid
	 */
	protected function is_valid_asset_path( string $path ): bool {
		return ! empty( $path ) && divi_squad()->get_wp_fs()->exists( divi_squad()->get_path( ltrim( $path, '/' ) ) );
	}

	/**
	 * Check if an asset file exists based on configuration
	 *
	 * @param array{ file: string, path?: string, prod_file?: string, dev_file?: string, pattern?: string, ext?: string, deps?: array<string> } $config Asset configuration.
	 *
	 * @return bool True if asset file exists
	 */
	public function is_asset_path_exist( array $config ): bool {
		return $this->is_valid_asset_path( $this->resolve_asset_path( $config, false, true ) );
	}
}
