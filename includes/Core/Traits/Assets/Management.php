<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Asset Management Trait
 *
 * Handles querying and management of registered assets.
 *
 * @since   3.3.0
 * @package DiviSquad
 */

namespace DiviSquad\Core\Traits\Assets;

/**
 * Asset Management Trait
 *
 * @since 3.3.0
 */
trait Management {

	/**
	 * Check if a script is registered
	 *
	 * @param string $handle Script identifier.
	 */
	protected function is_script_registered( string $handle ): bool {
		/**
		 * Filters the handle before checking if a script is registered.
		 *
		 * @since 3.4.0
		 *
		 * @param string $handle Script identifier.
		 */
		$handle = (string) apply_filters( 'divi_squad_check_script_registered_handle', $handle );

		$is_registered = isset( $this->registered_scripts[ $handle ] );

		/**
		 * Filters whether a script is considered registered.
		 *
		 * @since 3.4.0
		 *
		 * @param bool   $is_registered Whether the script is registered.
		 * @param string $handle        Script identifier.
		 */
		return apply_filters( 'divi_squad_is_script_registered', $is_registered, $handle );
	}

	/**
	 * Check if a style is registered
	 *
	 * @param string $handle Style identifier.
	 */
	protected function is_style_registered( string $handle ): bool {
		/**
		 * Filters the handle before checking if a style is registered.
		 *
		 * @since 3.4.0
		 *
		 * @param string $handle Style identifier.
		 */
		$handle = (string) apply_filters( 'divi_squad_check_style_registered_handle', $handle );

		$is_registered = isset( $this->registered_styles[ $handle ] );

		/**
		 * Filters whether a style is considered registered.
		 *
		 * @since 3.4.0
		 *
		 * @param bool   $is_registered Whether the style is registered.
		 * @param string $handle        Style identifier.
		 */
		return apply_filters( 'divi_squad_is_style_registered', $is_registered, $handle );
	}

	/**
	 * Get all registered scripts
	 *
	 * @return array<string, array{handle: string, data: array{path: string, version: string, dependencies: array<string>}}>
	 */
	protected function get_registered_scripts(): array {
		/**
		 * Filters all registered scripts.
		 *
		 * @since 3.4.0
		 *
		 * @param array $registered_scripts All registered scripts.
		 */
		return apply_filters( 'divi_squad_registered_scripts', $this->registered_scripts );
	}

	/**
	 * Get all registered styles
	 *
	 * @return array<string, array{handle: string, data: array{path: string, version: string, dependencies: array<string>}, media: string}>
	 */
	protected function get_registered_styles(): array {
		/**
		 * Filters all registered styles.
		 *
		 * @since 3.4.0
		 *
		 * @param array $registered_styles All registered styles.
		 */
		return apply_filters( 'divi_squad_registered_styles', $this->registered_styles );
	}

	/**
	 * Get a registered script's data
	 *
	 * @param string $handle Script identifier.
	 *
	 * @return array{handle: string, data: array{path: string, version: string, dependencies: array<string>}}|null
	 */
	protected function get_script_data( string $handle ): ?array {
		/**
		 * Filters the handle before retrieving script data.
		 *
		 * @since 3.4.0
		 *
		 * @param string $handle Script identifier.
		 */
		$handle = (string) apply_filters( 'divi_squad_get_script_data_handle', $handle );

		/**
		 * Fires before retrieving script data.
		 *
		 * @since 3.4.0
		 *
		 * @param string $handle Script identifier.
		 */
		do_action( 'divi_squad_before_get_script_data', $handle );

		$script_data = $this->registered_scripts[ $handle ] ?? null;

		/**
		 * Filters the retrieved script data.
		 *
		 * @since 3.4.0
		 *
		 * @param array|null $script_data Script data or null if not found.
		 * @param string     $handle      Script identifier.
		 */
		return apply_filters( 'divi_squad_script_data', $script_data, $handle );
	}

	/**
	 * Get a registered style's data
	 *
	 * @param string $handle Style identifier.
	 *
	 * @return array{handle: string, data: array{path: string, version: string, dependencies: array<string>}, media: string}|null
	 */
	protected function get_style_data( string $handle ): ?array {
		/**
		 * Filters the handle before retrieving style data.
		 *
		 * @since 3.4.0
		 *
		 * @param string $handle Style identifier.
		 */
		$handle = (string) apply_filters( 'divi_squad_get_style_data_handle', $handle );

		/**
		 * Fires before retrieving style data.
		 *
		 * @since 3.4.0
		 *
		 * @param string $handle Style identifier.
		 */
		do_action( 'divi_squad_before_get_style_data', $handle );

		$style_data = $this->registered_styles[ $handle ] ?? null;

		/**
		 * Filters the retrieved style data.
		 *
		 * @since 3.4.0
		 *
		 * @param array|null $style_data Style data or null if not found.
		 * @param string     $handle     Style identifier.
		 */
		return apply_filters( 'divi_squad_style_data', $style_data, $handle );
	}

	/**
	 * Check if a script is enqueued
	 *
	 * @param string $handle Script identifier.
	 */
	protected function is_script_enqueued( string $handle ): bool {
		/**
		 * Filters the handle before checking if a script is enqueued.
		 *
		 * @since 3.4.0
		 *
		 * @param string $handle Script identifier.
		 */
		$handle = (string) apply_filters( 'divi_squad_check_script_enqueued_handle', $handle );

		if ( ! isset( $this->registered_scripts[ $handle ] ) ) {
			/**
			 * Fires when checking if an unregistered script is enqueued.
			 *
			 * @since 3.4.0
			 *
			 * @param string $handle Script identifier.
			 */
			do_action( 'divi_squad_check_unregistered_script_enqueued', $handle );

			return false;
		}

		$full_handle = $this->registered_scripts[ $handle ]['handle'];
		$is_enqueued = wp_script_is( $full_handle, 'enqueued' );

		/**
		 * Filters whether a script is considered enqueued.
		 *
		 * @since 3.4.0
		 *
		 * @param bool   $is_enqueued Whether the script is enqueued.
		 * @param string $handle      Script identifier.
		 * @param string $full_handle Full script handle with prefix.
		 */
		return apply_filters( 'divi_squad_is_script_enqueued', $is_enqueued, $handle, $full_handle );
	}

	/**
	 * Check if a style is enqueued
	 *
	 * @param string $handle Style identifier.
	 */
	protected function is_style_enqueued( string $handle ): bool {
		/**
		 * Filters the handle before checking if a style is enqueued.
		 *
		 * @since 3.4.0
		 *
		 * @param string $handle Style identifier.
		 */
		$handle = (string) apply_filters( 'divi_squad_check_style_enqueued_handle', $handle );

		if ( ! isset( $this->registered_styles[ $handle ] ) ) {
			/**
			 * Fires when checking if an unregistered style is enqueued.
			 *
			 * @since 3.4.0
			 *
			 * @param string $handle Style identifier.
			 */
			do_action( 'divi_squad_check_unregistered_style_enqueued', $handle );

			return false;
		}

		$full_handle = $this->registered_styles[ $handle ]['handle'];
		$is_enqueued = wp_style_is( $full_handle, 'enqueued' );

		/**
		 * Filters whether a style is considered enqueued.
		 *
		 * @since 3.4.0
		 *
		 * @param bool   $is_enqueued Whether the style is enqueued.
		 * @param string $handle      Style identifier.
		 * @param string $full_handle Full style handle with prefix.
		 */
		return apply_filters( 'divi_squad_is_style_enqueued', $is_enqueued, $handle, $full_handle );
	}

	/**
	 * Get asset version
	 *
	 * @param string $handle Asset identifier.
	 */
	protected function get_asset_version( string $handle ): ?string {
		/**
		 * Filters the handle before retrieving asset version.
		 *
		 * @since 3.4.0
		 *
		 * @param string $handle Asset identifier.
		 */
		$handle = (string) apply_filters( 'divi_squad_get_asset_version_handle', $handle );

		/**
		 * Fires before retrieving asset version.
		 *
		 * @since 3.4.0
		 *
		 * @param string $handle Asset identifier.
		 */
		do_action( 'divi_squad_before_get_asset_version', $handle );

		$version = null;

		if ( isset( $this->registered_scripts[ $handle ] ) ) {
			$version = $this->registered_scripts[ $handle ]['data']['version'];
		} elseif ( isset( $this->registered_styles[ $handle ] ) ) {
			$version = $this->registered_styles[ $handle ]['data']['version'];
		}

		/**
		 * Filters the retrieved asset version.
		 *
		 * @since 3.4.0
		 *
		 * @param string|null $version The asset version or null if not found.
		 * @param string      $handle  Asset identifier.
		 * @param array       $scripts Registered scripts.
		 * @param array       $styles  Registered styles.
		 */
		return apply_filters( 'divi_squad_asset_version', $version, $handle, $this->registered_scripts, $this->registered_styles );
	}

	/**
	 * Get asset dependencies
	 *
	 * @param string $handle Asset identifier.
	 *
	 * @return array<string>
	 */
	protected function get_asset_dependencies( string $handle ): array {
		/**
		 * Filters the handle before retrieving asset dependencies.
		 *
		 * @since 3.4.0
		 *
		 * @param string $handle Asset identifier.
		 */
		$handle = (string) apply_filters( 'divi_squad_get_asset_dependencies_handle', $handle );

		/**
		 * Fires before retrieving asset dependencies.
		 *
		 * @since 3.4.0
		 *
		 * @param string $handle Asset identifier.
		 */
		do_action( 'divi_squad_before_get_asset_dependencies', $handle );

		$dependencies = array();

		if ( isset( $this->registered_scripts[ $handle ] ) ) {
			$dependencies = $this->registered_scripts[ $handle ]['data']['dependencies'];
		} elseif ( isset( $this->registered_styles[ $handle ] ) ) {
			$dependencies = $this->registered_styles[ $handle ]['data']['dependencies'];
		}

		/**
		 * Filters the retrieved asset dependencies.
		 *
		 * @since 3.4.0
		 *
		 * @param array  $dependencies The asset dependencies.
		 * @param string $handle       Asset identifier.
		 * @param array  $scripts      Registered scripts.
		 * @param array  $styles       Registered styles.
		 */
		return apply_filters( 'divi_squad_asset_dependencies', $dependencies, $handle, $this->registered_scripts, $this->registered_styles );
	}

	/**
	 * Get asset URL
	 *
	 * @param string $handle Asset identifier.
	 */
	protected function get_asset_url( string $handle ): ?string {
		/**
		 * Filters the handle before retrieving asset URL.
		 *
		 * @since 3.4.0
		 *
		 * @param string $handle Asset identifier.
		 */
		$handle = (string) apply_filters( 'divi_squad_get_asset_url_handle', $handle );

		/**
		 * Fires before retrieving asset URL.
		 *
		 * @since 3.4.0
		 *
		 * @param string $handle Asset identifier.
		 */
		do_action( 'divi_squad_before_get_asset_url', $handle );

		$url = null;

		if ( isset( $this->registered_scripts[ $handle ] ) ) {
			$url = $this->registered_scripts[ $handle ]['data']['url'] ?? null;
		} elseif ( isset( $this->registered_styles[ $handle ] ) ) {
			$url = $this->registered_styles[ $handle ]['data']['url'] ?? null;
		}

		/**
		 * Filters the retrieved asset URL.
		 *
		 * @since 3.4.0
		 *
		 * @param string|null $url     The asset URL or null if not found.
		 * @param string      $handle  Asset identifier.
		 * @param array       $scripts Registered scripts.
		 * @param array       $styles  Registered styles.
		 */
		return apply_filters( 'divi_squad_asset_url', $url, $handle, $this->registered_scripts, $this->registered_styles );
	}
}
