<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Plugin Life Detection Trait
 *
 * This file contains the trait that handles plugin version detection, environment checks,
 * and lifecycle management for Divi Squad plugins. It provides methods to determine if
 * the plugin is running in development, freemium, or premium mode.
 *
 * @since      3.2.0
 * @package    DiviSquad
 * @subpackage DiviSquad\Core\Traits\Plugin
 * @author     The WP Squad <support@squadmodules.com>
 * @license    GPL-2.0+
 * @link       https://wpsquad.com
 */

namespace DiviSquad\Core\Traits\Plugin;

use DiviSquad\Utils\WP;
use Throwable;

/**
 * Trait Detect_Plugin_Life
 *
 * Handles plugin version detection and environment checks for Divi Squad plugins.
 * Supports both development and release environments with extensible filter hooks.
 * This trait helps determine whether the plugin is running in development mode,
 * as a free plugin, or as a premium plugin.
 *
 * @since   3.2.0
 * @package DiviSquad
 */
trait Detect_Plugin_Life {
	/**
	 * Cached production environment status
	 *
	 * Stores the result of environment detection to avoid redundant checks.
	 *
	 * @since 3.2.0
	 * @var   bool|null
	 */
	private static ?bool $cached_prod_status = null;

	/**
	 * Cached pro installation status
	 *
	 * Stores whether the premium version is installed to avoid redundant checks.
	 *
	 * @since 3.3.3
	 * @var   bool|null
	 */
	private static ?bool $cached_pro_installed = null;

	/**
	 * Cached premium activation status
	 *
	 * Stores whether the premium version is activated to avoid redundant checks.
	 *
	 * @since 3.2.0
	 * @var   bool|null
	 */
	private static ?bool $cached_pro_activated = null;

	/**
	 * Cached plugin life type
	 *
	 * Stores the determined plugin life type to avoid redundant checks.
	 *
	 * @since 3.2.0
	 * @var   string|null
	 */
	private static ?string $cached_life_type = null;

	/**
	 * Check if the current environment is production.
	 *
	 * Handles both dev structure (/squad-modules-for-divi) and
	 * release structure (/includes). This is the inverse of is_dev().
	 *
	 * @since      3.2.0
	 * @deprecated 3.2.0 Use $this->is_prod() instead
	 *
	 * @return bool Returns true if running in production environment, false otherwise.
	 */
	public function is_prod(): bool {
		try {
			return ! $this->is_dev();
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Error checking if environment is production' );

			return true; // Default to production for safety
		}
	}

	/**
	 * Get the plugin basename of the premium version.
	 *
	 * Handles different path structures between dev and release environments
	 * and returns the proper basename for the premium plugin.
	 *
	 * @since 3.2.0
	 *
	 * @return string Returns the pro plugin basename.
	 */
	public function get_pro_basename(): string {
		try {
			$basename = 'squad-modules-pro-for-divi/squad-modules-pro-for-divi.php';

			/**
			 * Filters the basename of the pro version plugin.
			 *
			 * Allows modification of the premium plugin's basename based on environment.
			 *
			 * @since 3.2.0
			 *
			 * @param string $basename The premium plugin basename.
			 * @param bool   $is_prod  Whether running in production environment.
			 */
			return apply_filters( 'divi_squad_pro_plugin_basename', $basename, ! $this->is_dev() );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Error getting pro plugin basename' );

			return 'squad-modules-pro-for-divi/squad-modules-pro-for-divi.php'; // Return default basename
		}
	}

	/**
	 * Check if the premium version is activated.
	 *
	 * Determines whether the premium version of the plugin is active
	 * on the site. Uses caching to avoid repetitive checks.
	 *
	 * @since 3.2.0
	 *
	 * @return bool Returns true if premium version is active, false otherwise.
	 */
	public function is_pro_activated(): bool {
		try {
			if ( null === self::$cached_pro_activated ) {
				$is_active = WP::is_plugin_active( $this->get_pro_basename() );

				/**
				 * Filters whether the premium version is considered activated.
				 *
				 * Allows external modification of the premium version activation status.
				 *
				 * @since 3.2.0
				 *
				 * @param bool   $is_active Current premium version activation status.
				 * @param string $basename  The premium plugin basename.
				 */
				self::$cached_pro_activated = apply_filters( 'divi_squad_is_pro_activated', $is_active, $this->get_pro_basename() );
			}

			return (bool) self::$cached_pro_activated;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Error checking if premium version is activated' );

			return false; // Default to not activated for safety
		}
	}

	/**
	 * Check if the premium version is installed.
	 *
	 * Determines whether the premium version of the plugin is installed
	 * on the site, regardless of activation status. Uses caching to avoid
	 * repetitive checks.
	 *
	 * @since 3.3.3
	 *
	 * @return bool Returns true if premium version is installed, false otherwise.
	 */
	public function is_pro_installed(): bool {
		try {
			if ( null === self::$cached_pro_installed ) {
				if ( ! function_exists( '\get_plugins' ) ) {
					require_once $this->get_wp_path( 'wp-admin/includes/plugin.php' );
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
				 * @param bool   $is_installed Current premium version installation status.
				 * @param string $basename     The premium plugin basename.
				 */
				self::$cached_pro_installed = apply_filters( 'divi_squad_is_pro_installed', $is_installed, $pro_basename );
			}

			return (bool) self::$cached_pro_installed;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Error checking if premium version is installed' );

			return false; // Default to not installed for safety
		}
	}

	/**
	 * Check if running in development environment.
	 *
	 * Development environment is identified by presence of development-specific
	 * files and directories such as node_modules and tests.
	 *
	 * @since 3.2.0
	 *
	 * @return bool Returns true if in development environment, false otherwise.
	 */
	public function is_dev(): bool {
		try {
			if ( null === self::$cached_prod_status ) {
				$is_dev = $this->get_wp_fs()->exists( $this->get_path( '/node_modules' ) ) && $this->get_wp_fs()->exists( $this->get_path( '/tests' ) );

				/**
				 * Filters whether the current environment is considered development.
				 *
				 * Allows external modification of the development environment detection.
				 *
				 * @since 3.2.0
				 *
				 * @param bool   $is_dev Current development status based on file existence.
				 * @param string $path   Base path of the plugin being checked.
				 */
				self::$cached_prod_status = ! apply_filters( 'divi_squad_is_development_environment', $is_dev, $this->get_path() );
			}

			return ! self::$cached_prod_status;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Error checking if environment is development' );

			return false; // Default to production for safety
		}
	}

	/**
	 * Get the plugin life type.
	 *
	 * Determines environment type based on filesystem structure and premium
	 * activation status. Returns one of three values: 'development', 'freemium',
	 * or 'premium'.
	 *
	 * @since 3.2.0
	 *
	 * @return string Returns 'development', 'freemium', or 'premium'.
	 */
	public function get_plugin_life_type(): string {
		try {
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
				 * @param string $type   Current plugin life type ('development', 'freemium', or 'premium').
				 * @param bool   $is_pro Whether premium version is activated.
				 * @param bool   $is_dev Whether in development environment.
				 */
				self::$cached_life_type = apply_filters( 'divi_squad_plugin_life_type', $type, $this->is_pro_activated(), $this->is_dev() );
			}

			return (string) self::$cached_life_type;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Error determining plugin life type' );

			return 'freemium'; // Default to freemium for safety
		}
	}

	/**
	 * Check if current installation matches a specific version.
	 *
	 * Compares the current plugin life type with the specified version
	 * to determine if they match.
	 *
	 * @since 3.2.0
	 *
	 * @param string $version Version to check ('freemium', 'premium', 'development').
	 *
	 * @return bool True if running specified version, false otherwise.
	 */
	public function is_version( string $version ): bool {
		try {
			$matches = $this->get_plugin_life_type() === $version;

			/**
			 * Filters whether the current version matches the specified version type.
			 *
			 * Allows external modification of version matching logic.
			 *
			 * @since 3.2.0
			 *
			 * @param bool   $matches   Whether the versions match.
			 * @param string $version   Version being checked against.
			 * @param string $life_type Current plugin life type.
			 */
			return (bool) apply_filters( 'divi_squad_is_version_match', $matches, $version, $this->get_plugin_life_type() );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, sprintf( 'Error checking if plugin version matches "%s"', $version ) );

			return false; // Default to not matching for safety
		}
	}

	/**
	 * Reset cached status values.
	 *
	 * Clears all cached status values, forcing fresh detection on next check.
	 * Useful for testing or when plugin statuses might have changed.
	 *
	 * @since 3.3.3
	 *
	 * @return void
	 */
	public function reset_cached_statuses(): void {
		try {
			self::$cached_prod_status   = null;
			self::$cached_pro_installed = null;
			self::$cached_pro_activated = null;
			self::$cached_life_type     = null;

			/**
			 * Fires after cached statuses have been reset.
			 *
			 * Allows for additional actions after resetting cached values.
			 *
			 * @since 3.3.3
			 */
			do_action( 'divi_squad_plugin_life_reset_cache' );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Error resetting cached plugin life statuses' );
		}
	}
}