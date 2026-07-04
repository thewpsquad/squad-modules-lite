<?php
/**
 * Asset Management Trait
 *
 * Handles querying and management of registered assets.
 *
 * @since   3.3.0
 * @package DiviSquad\Core\Traits
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
		return isset( $this->registered_scripts[ $handle ] );
	}

	/**
	 * Check if a style is registered
	 *
	 * @param string $handle Style identifier.
	 */
	protected function is_style_registered( string $handle ): bool {
		return isset( $this->registered_styles[ $handle ] );
	}

	/**
	 * Get all registered scripts
	 *
	 * @return array<string, array{handle: string, data: array{path: string, version: string, dependencies: array<string>}}>
	 */
	protected function get_registered_scripts(): array {
		return $this->registered_scripts;
	}

	/**
	 * Get all registered styles
	 *
	 * @return array<string, array{handle: string, data: array{path: string, version: string, dependencies: array<string>}, media: string}>
	 */
	protected function get_registered_styles(): array {
		return $this->registered_styles;
	}

	/**
	 * Get a registered script's data
	 *
	 * @param string $handle Script identifier.
	 * @return array{handle: string, data: array{path: string, version: string, dependencies: array<string>}}|null
	 */
	protected function get_script_data( string $handle ): ?array {
		return $this->registered_scripts[ $handle ] ?? null;
	}

	/**
	 * Get a registered style's data
	 *
	 * @param string $handle Style identifier.
	 * @return array{handle: string, data: array{path: string, version: string, dependencies: array<string>}, media: string}|null
	 */
	protected function get_style_data( string $handle ): ?array {
		return $this->registered_styles[ $handle ] ?? null;
	}

	/**
	 * Check if a script is enqueued
	 *
	 * @param string $handle Script identifier.
	 */
	protected function is_script_enqueued( string $handle ): bool {
		if ( ! isset( $this->registered_scripts[ $handle ] ) ) {
			return false;
		}

		return wp_script_is( $this->registered_scripts[ $handle ]['handle'], 'enqueued' );
	}

	/**
	 * Check if a style is enqueued
	 *
	 * @param string $handle Style identifier.
	 */
	protected function is_style_enqueued( string $handle ): bool {
		if ( ! isset( $this->registered_styles[ $handle ] ) ) {
			return false;
		}

		return wp_style_is( $this->registered_styles[ $handle ]['handle'], 'enqueued' );
	}

	/**
	 * Get asset version
	 *
	 * @param string $handle Asset identifier.
	 */
	protected function get_asset_version( string $handle ): ?string {
		if ( isset( $this->registered_scripts[ $handle ] ) ) {
			return $this->registered_scripts[ $handle ]['data']['version'];
		}

		if ( isset( $this->registered_styles[ $handle ] ) ) {
			return $this->registered_styles[ $handle ]['data']['version'];
		}

		return null;
	}

	/**
	 * Get asset dependencies
	 *
	 * @param string $handle Asset identifier.
	 * @return array<string>
	 */
	protected function get_asset_dependencies( string $handle ): array {
		if ( isset( $this->registered_scripts[ $handle ] ) ) {
			return $this->registered_scripts[ $handle ]['data']['dependencies'];
		}

		if ( isset( $this->registered_styles[ $handle ] ) ) {
			return $this->registered_styles[ $handle ]['data']['dependencies'];
		}

		return array();
	}

	/**
	 * Get asset URL
	 *
	 * @param string $handle Asset identifier.
	 */
	protected function get_asset_url( string $handle ): ?string {
		if ( isset( $this->registered_scripts[ $handle ] ) ) {
			return $this->registered_scripts[ $handle ]['data']['path'];
		}

		if ( isset( $this->registered_styles[ $handle ] ) ) {
			return $this->registered_styles[ $handle ]['data']['path'];
		}

		return null;
	}
}
