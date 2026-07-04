<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Asset Management Trait
 *
 * Provides core functionality for handling asset dependencies, versions,
 * and file resolution based on environment mode.
 *
 * @since   3.3.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
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
	 * @param array{ file: string, path?: string, prod_file?: string, dev_file?: string, pattern?: string, ext?: string, deps?: array<string> } $config       Asset configuration.
	 * @param array<string>                                                                                                                     $default_deps Default dependencies.
	 *
	 * @return array{ path: string, version: string, dependencies: array<string> } Processed asset data
	 * @throws RuntimeException If asset configuration is invalid.
	 */
	public function process_asset_config( array $config, array $default_deps = array() ): array {
		try {
			/**
			 * Filters the asset configuration before processing.
			 *
			 * @since 3.4.0
			 *
			 * @param array $config       The asset configuration.
			 * @param array $default_deps Default dependencies.
			 */
			$config = (array) apply_filters( 'divi_squad_pre_process_asset_config', $config, $default_deps );

			if ( ! isset( $config['file'] ) ) {
				throw new RuntimeException( 'Asset configuration must include a file name' );
			}

			// Merge provided deps with defaults.
			$deps = array_merge( $default_deps, $config['deps'] ?? array() );

			/**
			 * Filters the merged dependencies before processing.
			 *
			 * @since 3.4.0
			 *
			 * @param array $deps   The merged dependencies.
			 * @param array $config The asset configuration.
			 */
			$deps = apply_filters( 'divi_squad_pre_process_dependencies', $deps, $config );

			// Build file path and url.
			$path_url   = $this->resolve_asset_path( $config );
			$path_local = $this->resolve_asset_path( $config, true );

			/**
			 * Filters the resolved asset URL.
			 *
			 * @since 3.4.0
			 *
			 * @param string $path_url   The resolved asset URL.
			 * @param string $path_local The resolved local path.
			 * @param array  $config     The asset configuration.
			 */
			$path_url = apply_filters( 'divi_squad_resolved_asset_url', $path_url, $path_local, $config );

			/**
			 * Filters the resolved local asset path.
			 *
			 * @since 3.4.0
			 *
			 * @param string $path_local The resolved local path.
			 * @param string $path_url   The resolved asset URL.
			 * @param array  $config     The asset configuration.
			 */
			$path_local = apply_filters( 'divi_squad_resolved_asset_local_path', $path_local, $path_url, $config );

			// Get version info.
			$version = $this->resolve_asset_version( $path_local );

			/**
			 * Filters the resolved asset version.
			 *
			 * @since 3.4.0
			 *
			 * @param string $version    The resolved version.
			 * @param string $path_local The local path to the asset.
			 * @param array  $config     The asset configuration.
			 */
			$version = apply_filters( 'divi_squad_resolved_asset_version', $version, $path_local, $config );

			// Process dependencies.
			$dependencies = $this->resolve_dependencies( $deps, $path_local );

			/**
			 * Filters the resolved dependencies.
			 *
			 * @since 3.4.0
			 *
			 * @param array  $dependencies The resolved dependencies.
			 * @param string $path_local   The local path to the asset.
			 * @param array  $config       The asset configuration.
			 */
			$dependencies = apply_filters( 'divi_squad_resolved_dependencies', $dependencies, $path_local, $config );

			$result = array(
				'url'          => $path_url,
				'path'         => $path_local,
				'version'      => $version,
				'dependencies' => $dependencies,
			);

			/**
			 * Filters the final processed asset data.
			 *
			 * @since 3.4.0
			 *
			 * @param array $result The processed asset data.
			 * @param array $config The original asset configuration.
			 */
			$result = apply_filters( 'divi_squad_processed_asset_data', $result, $config );

			/**
			 * Fires after asset configuration is processed.
			 *
			 * @since 3.4.0
			 *
			 * @param array $result The processed asset data.
			 * @param array $config The original asset configuration.
			 */
			do_action( 'divi_squad_asset_config_processed', $result, $config );

			return $result;
		} catch ( Throwable $e ) {
			/**
			 * Fires when asset configuration processing fails.
			 *
			 * @since 3.4.0
			 *
			 * @param array     $config The asset configuration.
			 * @param Throwable $e      The exception.
			 */
			do_action( 'divi_squad_asset_config_processing_failed', $config, $e );

			throw new RuntimeException(
				sprintf( 'Failed to process asset config: %s', esc_html( $e->getMessage() ) ),
				0
			);
		}
	}

	/**
	 * Resolve the asset file path based on environment and configuration
	 *
	 * @param array{ file: string, path?: string, prod_file?: string, dev_file?: string, pattern?: string, ext?: string, deps?: array<string> } $config      Asset configuration.
	 * @param bool                                                                                                                              $is_local    Whether to resolve local path.
	 * @param bool                                                                                                                              $return_path Whether to return the resolved path.
	 *
	 * @return string Resolved asset path
	 * @throws RuntimeException If path cannot be resolved.
	 */
	public function resolve_asset_path( array $config, bool $is_local = false, bool $return_path = false ): string {
		/**
		 * Filters the asset configuration before resolving the path.
		 *
		 * @since 3.4.0
		 *
		 * @param array $config   The asset configuration.
		 * @param bool  $is_local Whether to resolve local path.
		 */
		$config = (array) apply_filters( 'divi_squad_pre_resolve_asset_path', $config, $is_local );

		$external    = $config['external'] ?? false;
		$pattern     = $config['pattern'] ?? '[path_prefix]/[file].[ext]';
		$path_prefix = $config['path'] ?? 'divi-builder-4';
		$extension   = $config['ext'] ?? 'js';

		/**
		 * Filters the asset path components before processing.
		 *
		 * @since 3.4.0
		 *
		 * @param array{
		 *     external: bool,
		 *     pattern: string,
		 *     path_prefix: string,
		 *     extension: string
		 * }            $components The asset path components.
		 * @param array $config     The asset configuration.
		 */
		$path_components = apply_filters(
			'divi_squad_asset_path_components',
			array(
				'external'    => $external,
				'pattern'     => $pattern,
				'path_prefix' => $path_prefix,
				'extension'   => $extension,
			),
			$config
		);

		$external    = $path_components['external'];
		$pattern     = $path_components['pattern'];
		$path_prefix = $path_components['path_prefix'];
		$extension   = $path_components['extension'];

		// Determine the correct file to use based on environment.
		$file = $this->resolve_environment_file( $config );

		/**
		 * Filters the resolved environment file.
		 *
		 * @since 3.4.0
		 *
		 * @param string $file   The resolved file name.
		 * @param array  $config The asset configuration.
		 */
		$file = apply_filters( 'divi_squad_resolved_environment_file', $file, $config );

		if ( ! $external ) {
			$pattern     = 'build/' . $pattern;
			$path_prefix .= 'js' === $extension ? '/scripts' : '/styles';
		}

		/**
		 * Filters the asset path pattern.
		 *
		 * @since 3.3.0
		 *
		 * @param string $pattern   The asset path pattern.
		 * @param string $file      The file name.
		 * @param string $extension The file extension.
		 */
		$path_pattern = apply_filters( 'divi_squad_asset_path_pattern', $pattern, $file, $extension );

		// Build the initial path.
		$path = str_replace(
			array( '[path_prefix]', '[file]', '[ext]' ),
			array( $path_prefix, $file, $extension ),
			$path_pattern
		);

		/**
		 * Filters the initial path before normalization.
		 *
		 * @since 3.4.0
		 *
		 * @param string $path    The initial path.
		 * @param string $pattern The path pattern.
		 * @param array  $config  The asset configuration.
		 */
		$path = apply_filters( 'divi_squad_initial_asset_path', $path, $path_pattern, $config );

		// Normalize path.
		$path = $this->normalize_path( $path );

		/**
		 * Filters the normalized path.
		 *
		 * @since 3.4.0
		 *
		 * @param string $path   The normalized path.
		 * @param array  $config The asset configuration.
		 */
		$path = apply_filters( 'divi_squad_normalized_asset_path', $path, $config );

		// Check for minified version in production.
		if ( $this->should_use_minified( $path, $extension ) ) {
			$minified_path = $this->get_minified_path( $path, $extension );

			/**
			 * Filters the minified path.
			 *
			 * @since 3.4.0
			 *
			 * @param string $minified_path The minified path.
			 * @param string $path          The original path.
			 * @param string $extension     The file extension.
			 */
			$minified_path = apply_filters( 'divi_squad_minified_asset_path', $minified_path, $path, $extension );

			if ( $this->is_valid_asset_path( $minified_path ) ) {
				$path = $minified_path;

				/**
				 * Fires when a minified asset path is used.
				 *
				 * @since 3.4.0
				 *
				 * @param string $path          The minified path.
				 * @param string $original_path The original path.
				 */
				do_action( 'divi_squad_using_minified_asset', $path, $path );
			}
		}

		// Return the path if requested.
		if ( $external || $return_path ) {
			return $path;
		}

		if ( $is_local ) {
			$result = divi_squad()->get_path( ltrim( $path, '/' ) );

			/**
			 * Filters the resolved local file system path.
			 *
			 * @since 3.4.0
			 *
			 * @param string $result The resolved path.
			 * @param string $path   The normalized path.
			 * @param array  $config The asset configuration.
			 */
			return apply_filters( 'divi_squad_resolved_local_path', $result, $path, $config );
		}

		$result = divi_squad()->get_url( ltrim( $path, '/' ) );

		/**
		 * Filters the resolved URL.
		 *
		 * @since 3.4.0
		 *
		 * @param string $result The resolved URL.
		 * @param string $path   The normalized path.
		 * @param array  $config The asset configuration.
		 */
		return apply_filters( 'divi_squad_resolved_url', $result, $path, $config );
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

		/**
		 * Filters whether the site is in development mode for asset resolution.
		 *
		 * @since 3.4.0
		 *
		 * @param bool  $is_dev Whether the site is in development mode.
		 * @param array $config The asset configuration.
		 */
		$is_dev = apply_filters( 'divi_squad_is_dev_mode_for_assets', $is_dev, $config );

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

		/**
		 * Filters the default asset version.
		 *
		 * @since 3.4.0
		 *
		 * @param string $version The default version.
		 * @param string $path    The asset path.
		 */
		$version = apply_filters( 'divi_squad_default_asset_version', $version, $path );

		// Check for version file.
		$version_file = $this->get_version_file_path( $path );

		/**
		 * Filters the version file path.
		 *
		 * @since 3.4.0
		 *
		 * @param string $version_file The version file path.
		 * @param string $path         The asset path.
		 */
		$version_file = apply_filters( 'divi_squad_version_file_path', $version_file, $path );

		if ( divi_squad()->get_wp_fs()->exists( $version_file ) ) {
			$version_data = include $version_file;

			/**
			 * Filters the version data loaded from the version file.
			 *
			 * @since 3.4.0
			 *
			 * @param array  $version_data The version data.
			 * @param string $version_file The version file path.
			 * @param string $path         The asset path.
			 */
			$version_data = apply_filters( 'divi_squad_version_file_data', $version_data, $version_file, $path );

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

		/**
		 * Filters the dependencies after removing empty values.
		 *
		 * @since 3.4.0
		 *
		 * @param array<string> $deps The filtered dependencies.
		 * @param string        $path The asset path.
		 */
		$deps = (array) apply_filters( 'divi_squad_filtered_dependencies', $deps, $path );

		// Add version file dependencies if available.
		$version_file = $this->get_version_file_path( $path );

		/**
		 * Filters the version file path for dependency resolution.
		 *
		 * @since 3.4.0
		 *
		 * @param string $version_file The version file path.
		 * @param string $path         The asset path.
		 */
		$version_file = apply_filters( 'divi_squad_dependency_version_file_path', $version_file, $path );

		if ( divi_squad()->get_wp_fs()->exists( $version_file ) ) {
			$version_data = include $version_file;

			/**
			 * Filters the version data for dependency resolution.
			 *
			 * @since 3.4.0
			 *
			 * @param array  $version_data The version data.
			 * @param string $version_file The version file path.
			 * @param string $path         The asset path.
			 */
			$version_data = apply_filters( 'divi_squad_dependency_version_data', $version_data, $version_file, $path );

			if ( isset( $version_data['dependencies'] ) ) {
				$deps = array_merge( $deps, $version_data['dependencies'] );

				/**
				 * Fires when dependencies are merged from a version file.
				 *
				 * @since 3.4.0
				 *
				 * @param array  $deps         The merged dependencies.
				 * @param array  $version_data The version data.
				 * @param string $path         The asset path.
				 */
				do_action( 'divi_squad_dependencies_merged_from_version_file', $deps, $version_data, $path );
			}
		}

		// Handle React JSX Runtime dependency.
		if ( in_array( 'react-jsx-runtime', $deps, true ) ) {
			$deps = $this->handle_jsx_runtime_dependency( $deps );

			/**
			 * Fires after handling JSX runtime dependencies.
			 *
			 * @since 3.4.0
			 *
			 * @param array<string> $deps The dependencies after handling JSX runtime.
			 * @param string        $path The asset path.
			 */
			do_action( 'divi_squad_after_jsx_runtime_dependency_handling', $deps, $path );
		}

		$unique_deps = array_unique( $deps );

		/**
		 * Filters the final dependencies after processing.
		 *
		 * @since 3.4.0
		 *
		 * @param array<string> $unique_deps The unique dependencies.
		 * @param array<string> $deps        The original dependencies.
		 * @param string        $path        The asset path.
		 */
		return apply_filters( 'divi_squad_final_dependencies', $unique_deps, $deps, $path );
	}

	/**
	 * Normalize asset path
	 *
	 * @param string $path Asset path.
	 *
	 * @return string Normalized path
	 */
	protected function normalize_path( string $path ): string {
		/**
		 * Filters the path before normalization.
		 *
		 * @since 3.4.0
		 *
		 * @param string $path The path before normalization.
		 */
		$path = (string) apply_filters( 'divi_squad_pre_normalize_path', $path );

		$normalized = wp_normalize_path( str_replace( './', '/', $path ) );

		/**
		 * Filters the normalized path.
		 *
		 * @since 3.4.0
		 *
		 * @param string $normalized The normalized path.
		 * @param string $path       The original path.
		 */
		return apply_filters( 'divi_squad_normalized_path', $normalized, $path );
	}

	/**
	 * Check if minified version should be used
	 *
	 * @param string $path      Asset path.
	 * @param string $extension File extension.
	 *
	 * @return bool True if should use minified version
	 */
	protected function should_use_minified( string $path, string $extension ): bool {
		$is_dev               = divi_squad()->is_dev();
		$valid_extension      = in_array( $extension, array( 'js', 'css' ), true );
		$not_already_minified = ! Str::ends_with( $path, ".min.{$extension}" );

		$should_use = ! $is_dev && $valid_extension && $not_already_minified;

		/**
		 * Filters whether to use a minified version of an asset.
		 *
		 * @since 3.4.0
		 *
		 * @param bool   $should_use Whether to use minified version.
		 * @param string $path       The asset path.
		 * @param string $extension  The file extension.
		 */
		return apply_filters( 'divi_squad_should_use_minified', $should_use, $path, $extension );
	}

	/**
	 * Get minified version path
	 *
	 * @param string $path      Asset path.
	 * @param string $extension File extension.
	 *
	 * @return string Minified file path
	 */
	protected function get_minified_path( string $path, string $extension ): string {
		$minified_path = str_replace( ".{$extension}", ".min.{$extension}", $path );

		/**
		 * Filters the minified file path.
		 *
		 * @since 3.4.0
		 *
		 * @param string $minified_path The minified file path.
		 * @param string $path          The original file path.
		 * @param string $extension     The file extension.
		 */
		return apply_filters( 'divi_squad_get_minified_path', $minified_path, $path, $extension );
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
			$version_file = str_replace( '.min.js', '.min.asset.php', $path );
		} else {
			$version_file = str_replace( array( '.js', '.css' ), '.asset.php', $path );
		}

		/**
		 * Filters the version file path.
		 *
		 * @since 3.4.0
		 *
		 * @param string $version_file The version file path.
		 * @param string $path         The asset path.
		 */
		return apply_filters( 'divi_squad_get_version_file_path', $version_file, $path );
	}

	/**
	 * Handle React JSX Runtime dependency
	 *
	 * @param array<string> $deps Dependencies array.
	 *
	 * @return array<string> Modified dependencies
	 */
	protected function handle_jsx_runtime_dependency( array $deps ): array {
		$has_jsx_runtime = wp_script_is( 'react-jsx-runtime', 'registered' );

		/**
		 * Filters whether the React JSX Runtime is registered.
		 *
		 * @since 3.4.0
		 *
		 * @param bool          $has_jsx_runtime Whether React JSX Runtime is registered.
		 * @param array<string> $deps            The dependencies array.
		 */
		$has_jsx_runtime = apply_filters( 'divi_squad_has_jsx_runtime', $has_jsx_runtime, $deps );

		if ( ! $has_jsx_runtime ) {
			$index = array_search( 'react-jsx-runtime', $deps, true );
			if ( false !== $index ) {
				unset( $deps[ $index ] );

				/**
				 * Fires when React JSX Runtime dependency is removed.
				 *
				 * @since 3.4.0
				 *
				 * @param array<string> $deps  The dependencies array after removal.
				 * @param int           $index The index of the removed dependency.
				 */
				do_action( 'divi_squad_jsx_runtime_dependency_removed', $deps, $index );
			}
		}

		$result = array_values( $deps );

		/**
		 * Filters the dependencies array after handling JSX runtime.
		 *
		 * @since 3.4.0
		 *
		 * @param array<string> $result The resulting dependencies array.
		 * @param array<string> $deps   The original dependencies array.
		 */
		return apply_filters( 'divi_squad_after_jsx_runtime_handling', $result, $deps );
	}

	/**
	 * Validate asset path
	 *
	 * @param string $path Asset path.
	 *
	 * @return bool True if path is valid
	 */
	protected function is_valid_asset_path( string $path ): bool {
		if ( empty( $path ) ) {
			return false;
		}

		$full_path = divi_squad()->get_path( ltrim( $path, '/' ) );
		$exists    = divi_squad()->get_wp_fs()->exists( $full_path );

		/**
		 * Filters whether an asset path is valid.
		 *
		 * @since 3.4.0
		 *
		 * @param bool   $exists    Whether the asset file exists.
		 * @param string $path      The asset path.
		 * @param string $full_path The full file system path.
		 */
		return apply_filters( 'divi_squad_is_valid_asset_path', $exists, $path, $full_path );
	}

	/**
	 * Check if an asset file exists based on configuration
	 *
	 * @param array{ file: string, path?: string, prod_file?: string, dev_file?: string, pattern?: string, ext?: string, deps?: array<string> } $config Asset configuration.
	 *
	 * @return bool True if asset file exists
	 */
	public function is_asset_path_exist( array $config ): bool {
		$path = $this->resolve_asset_path( $config, false, true );

		/**
		 * Filters the resolved asset path before checking existence.
		 *
		 * @since 3.4.0
		 *
		 * @param string $path   The resolved asset path.
		 * @param array  $config The asset configuration.
		 */
		$path = apply_filters( 'divi_squad_check_asset_path', $path, $config );

		$exists = $this->is_valid_asset_path( $path );

		/**
		 * Filters whether an asset exists based on configuration.
		 *
		 * @since 3.4.0
		 *
		 * @param bool   $exists Whether the asset exists.
		 * @param string $path   The resolved asset path.
		 * @param array  $config The asset configuration.
		 */
		return apply_filters( 'divi_squad_asset_path_exists', $exists, $path, $config );
	}
}
