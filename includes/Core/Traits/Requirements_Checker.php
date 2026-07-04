<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Requirements Checker trait for verifying plugin dependencies.
 *
 * @since   3.4.5
 * @package DiviSquad
 */

namespace DiviSquad\Core\Traits;

/**
 * Requirements Checker trait.
 *
 * Provides functionality to verify plugin requirements for modules and extensions.
 * This trait centralizes the duplicate requirements checking logic that was previously
 * present in both Modules and Extensions classes.
 *
 * @since   3.4.5
 * @package DiviSquad
 */
trait Requirements_Checker {

	/**
	 * Verify plugin requirements for an item (module or extension)
	 *
	 * @since 3.4.5
	 *
	 * @param array<string, mixed> $item           Item configuration (module or extension).
	 * @param array<string>        $active_plugins List of active plugin slugs.
	 *
	 * @return bool Whether requirements are met.
	 */
	protected function check_plugin_requirements( array $item, array $active_plugins ): bool {
		// If no requirements, item is valid.
		if ( ! isset( $item['required'] ) ) {
			return true;
		}

		// Check plugin requirements.
		if ( ! isset( $item['required']['plugin'] ) ) {
			return true;
		}

		$required_plugins = $item['required']['plugin'];

		// Single plugin requirement.
		if ( is_string( $required_plugins ) ) {
			return $this->check_string_plugin_requirement( $required_plugins, $active_plugins );
		}

		// Multiple required plugins (all must be active).
		if ( is_array( $required_plugins ) ) {
			return $this->check_array_plugin_requirements( $required_plugins, $active_plugins );
		}

		return false;
	}

	/**
	 * Check a string plugin requirement
	 *
	 * Handles both single plugin requirements and multiple options (plugin1|plugin2).
	 *
	 * @since 3.4.5
	 *
	 * @param string        $required_plugins The required plugin(s) string.
	 * @param array<string> $active_plugins   List of active plugin slugs.
	 *
	 * @return bool Whether requirements are met.
	 */
	protected function check_string_plugin_requirement( string $required_plugins, array $active_plugins ): bool {
		// Check for multiple options (plugin1|plugin2) - at least one must be active.
		if ( strpos( $required_plugins, '|' ) !== false ) {
			$plugin_options = explode( '|', $required_plugins );

			foreach ( $plugin_options as $plugin ) {
				if ( in_array( trim( $plugin ), $active_plugins, true ) ) {
					return true;
				}
			}

			return false;
		}

		// Single plugin must be active.
		return in_array( $required_plugins, $active_plugins, true );
	}

	/**
	 * Check array plugin requirements
	 *
	 * All plugins in the array must be active.
	 *
	 * @since 3.4.5
	 *
	 * @param array<string> $required_plugins Array of required plugin slugs.
	 * @param array<string> $active_plugins   List of active plugin slugs.
	 *
	 * @return bool Whether requirements are met.
	 */
	protected function check_array_plugin_requirements( array $required_plugins, array $active_plugins ): bool {
		foreach ( $required_plugins as $plugin ) {
			if ( ! in_array( $plugin, $active_plugins, true ) ) {
				return false;
			}
		}

		return true;
	}
}
