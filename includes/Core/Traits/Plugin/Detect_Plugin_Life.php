<?php // phpcs:ignore WordPress.Files.FileName

namespace DiviSquad\Core\Traits\Plugin;

use DiviSquad\Utils\WP;

/**
 * Trait DetectPluginLife
 *
 * Handles plugin version detection and environment checks for Divi Squad plugins.
 * Supports both development and release environments with extensible filter hooks.
 *
 * @since   3.2.0
 * @package DiviSquad
 */
trait Detect_Plugin_Life {
	/**
	 * Cached production environment status
	 *
	 * @since 3.2.0
	 * @var bool|null
	 */
	private static ?bool $cached_prod_status = null;

	/**
	 * Cached pro installation status
	 *
	 * @since 3.3.3
	 * @var bool|null
	 */
	private static ?bool $cached_pro_installed = null;

	/**
	 * Cached premium installation status
	 *
	 * @since 3.2.0
	 * @var bool|null
	 */
	private static ?bool $cached_pro_activated = null;

	/**
	 * Cached plugin life type
	 *
	 * @since 3.2.0
	 * @var string|null
	 */
	private static ?string $cached_life_type = null;

	/**
	 * Check if the current environment is production.
	 * Handles both dev structure (/squad-modules-for-divi) and release structure (/includes).
	 *
	 * @since      3.2.0
	 * @deprecated 3.2.0 Use $this->is_prod() instead
	 *
	 * @return bool Returns true if running in production environment
	 */
	public function is_prod(): bool {
		return ! $this->is_dev();
	}

	/**
	 * Get the plugin basename of the premium version.
	 * Handles different path structures between dev and release environments.
	 *
	 * @since 3.2.0
	 *
	 * @return string Returns the pro plugin basename
	 */
	public function get_pro_basename(): string {
		$basename = 'squad-modules-pro-for-divi/squad-modules-pro-for-divi.php';

		/**
		 * Filters the basename of the pro version plugin.
		 *
		 * Allows modification of the premium plugin's basename based on environment.
		 *
		 * @since 3.2.0
		 *
		 * @param string $basename The premium plugin basename
		 * @param bool   $is_prod  Whether running in production environment
		 */
		return apply_filters( 'divi_squad_pro_plugin_basename', $basename, ! $this->is_dev() );
	}

	/**
	 * Check if the premium version is activated.
	 *
	 * @since 3.2.0
	 *
	 * @return bool Returns true if premium version is active, null if status unknown
	 */
	public function is_pro_activated(): bool {
		if ( null === self::$cached_pro_activated ) {
			$is_active = WP::is_plugin_active( $this->get_pro_basename() );

			/**
			 * Filters whether the premium version is considered activated.
			 *
			 * Allows external modification of the premium version activation status.
			 *
			 * @since 3.2.0
			 *
			 * @param bool   $is_active Current premium version activation status
			 * @param string $basename  The premium plugin basename
			 */
			self::$cached_pro_activated = apply_filters( 'divi_squad_is_pro_activated', $is_active, $this->get_pro_basename() );
		}

		return (bool) self::$cached_pro_activated;
	}

	/**
	 * Check if the premium version is installed.
	 *
	 * @since 3.3.3
	 *
	 * @return bool Returns true if premium version is installed, null if status unknown
	 */
	public function is_pro_installed(): bool {
		if ( null === self::$cached_pro_installed ) {
			if ( ! function_exists( '\get_plugins' ) ) {
				require_once divi_squad()->get_wp_path() . 'wp-admin/includes/plugin.php';
			}

			// Collect basename of all installed and the pro plugin.
			$installed_plugins = array_keys( \get_plugins() );
			$pro_basename      = $this->get_pro_basename();

			$is_installed = in_array( $pro_basename, $installed_plugins, true );

			/**
			 * Filters whether the premium version is considered installed.
			 *
			 * Allows external modification of the premium version installation status.
			 *
			 * @since 3.3.3
			 *
			 * @param bool   $is_installed Current premium version installation status
			 * @param string $basename     The premium plugin basename
			 */
			self::$cached_pro_installed = apply_filters( 'divi_squad_is_pro_installed', $is_installed, $pro_basename );
		}

		return (bool) self::$cached_pro_installed;
	}

	/**
	 * Check if running in development environment.
	 * Development environment is identified by presence of development-specific files and directories.
	 *
	 * @since 3.2.0
	 *
	 * @return bool Returns true if in development environment
	 */
	public function is_dev(): bool {
		$is_dev = $this->get_wp_fs()->exists( $this->get_path( '/node_modules' ) ) && $this->get_wp_fs()->exists( $this->get_path( '/tests' ) );

		/**
		 * Filters whether the current environment is considered development.
		 *
		 * Allows external modification of the development environment detection.
		 *
		 * @since 3.2.0
		 *
		 * @param bool   $is_dev Current development status based on file existence
		 * @param string $path   Base path of the plugin being checked
		 */
		return (bool) apply_filters( 'divi_squad_is_development_environment', $is_dev, $this->get_path() );
	}

	/**
	 * Get the plugin life type.
	 * Determines environment type based on filesystem structure and premium activation status.
	 *
	 * @since 3.2.0
	 *
	 * @return string Returns 'development', 'freemium', or 'premium'
	 */
	public function get_plugin_life_type(): string {
		if ( null === self::$cached_life_type ) {
			if ( $this->is_pro_activated() ) {
				$type = 'premium';
			} elseif ( $this->is_dev() ) {
				$type = 'development';
			} else {
				$type = 'freemium';
			}

			/**
			 * Filters the determined plugin life type.
			 *
			 * Allows modification of the plugin's life type based on custom conditions.
			 *
			 * @since 3.2.0
			 *
			 * @param string $type   Current plugin life type ('development', 'freemium', or 'premium')
			 * @param bool   $is_pro Whether premium version is activated
			 * @param bool   $is_dev Whether in development environment
			 */
			self::$cached_life_type = apply_filters( 'divi_squad_plugin_life_type', $type, $this->is_pro_activated(), $this->is_dev() );
		}

		return (string) self::$cached_life_type;
	}

	/**
	 * Check if current installation matches a specific version.
	 *
	 * @since 3.2.0
	 *
	 * @param string $version Version to check ('freemium', 'premium', 'development').
	 *
	 * @return bool True if running specified version
	 */
	public function is_version( string $version ): bool {
		$matches = $this->get_plugin_life_type() === $version;

		/**
		 * Filters whether the current version matches the specified version type.
		 *
		 * Allows external modification of version matching logic.
		 *
		 * @since 3.2.0
		 *
		 * @param bool   $matches   Whether the versions match
		 * @param string $version   Version being checked against
		 * @param string $life_type Current plugin life type
		 */
		return (bool) apply_filters( 'divi_squad_is_version_match', $matches, $version, $this->get_plugin_life_type() );
	}
}
