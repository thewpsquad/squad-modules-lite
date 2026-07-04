<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Assets Manager Class
 *
 * This class handles registration, enqueuing, and management of all assets
 * (scripts and styles) for the plugin, including frontend and admin areas.
 *
 * @since   3.3.0
 * @author  The WP Squad <support@squadmodules.com>
 * @package DiviSquad
 */

namespace DiviSquad\Core;

use _WP_Dependency;
use DiviSquad\Core\Supports\Polyfills\Constant;
use DiviSquad\Core\Traits\Assets\Assets_Core;
use DiviSquad\Core\Traits\Assets\Body_Classes;
use DiviSquad\Core\Traits\Assets\Brand_Assets;
use DiviSquad\Core\Traits\Assets\Localization;
use DiviSquad\Core\Traits\Assets\Management;
use DiviSquad\Core\Traits\Assets\Registration;
use DiviSquad\Utils\Divi;
use DiviSquad\Utils\Helper;
use Throwable;
use WP_Screen;
use WP_Scripts;
use WP_Styles;

/**
 * Core Assets Manager
 *
 * @since 3.3.0
 */
class Assets {
	use Assets_Core;
	use Body_Classes;
	use Brand_Assets;
	use Localization;
	use Management;
	use Registration;

	/**
	 * Initialization flag
	 *
	 * @var bool
	 */
	private bool $initialized = false;

	/**
	 * Initialize the assets manager
	 */
	public function __construct() {
		try {
			$this->init_hooks();
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to initialize assets manager' );
		}
	}

	/**
	 * Initialize WordPress hooks
	 */
	private function init_hooks(): void {
		if ( $this->initialized ) {
			return;
		}

		// Frontend hooks.
		add_action( 'wp_enqueue_scripts', array( $this, 'register_frontend_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ), 20 );
		add_action( 'wp_footer', array( $this, 'localize_scripts' ), 5 );

		// Admin hooks.
		add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ), 20 );
		add_action( 'admin_footer', array( $this, 'localize_scripts' ), 5 );

		// Asset cleanup for plugin pages.
		add_action( 'admin_enqueue_scripts', array( $this, 'clean_third_party_deps' ), Constant::PHP_INT_MAX );
		add_action( 'admin_head', array( $this, 'clean_admin_content_section' ), Constant::PHP_INT_MAX );

		// Body class hooks.
		add_filter( 'divi_squad_body_classes', array( $this, 'add_default_classes' ) );
		add_filter( 'body_class', array( $this, 'filter_body_classes' ) );
		add_filter( 'admin_body_class', array( $this, 'filter_admin_body_classes' ) );

		// Brand assets hooks.
		add_action( 'admin_head', array( $this, 'output_logo_css' ) );
		add_action( 'wp_head', array( $this, 'output_logo_css' ) );

		/**
		 * Fires after assets manager is initialized
		 *
		 * @since 3.3.0
		 *
		 * @param self $assets Current assets manager instance
		 */
		do_action( 'divi_squad_assets_init', $this );

		$this->initialized = true;
	}

	/**
	 * Remove all notices from the squad template pages.
	 *
	 * @return void
	 */
	public function clean_admin_content_section(): void {
		try {
			// Check if the current screen is available.
			if ( ! Helper::is_squad_page() ) {
				return;
			}

			$screen = get_current_screen();

			/**
			 * Filter whether to clean admin notices on squad pages.
			 *
			 * @since 3.3.0
			 *
			 * @param bool      $should_clean Whether to clean admin notices.
			 * @param WP_Screen $screen       Current admin screen.
			 */
			$should_clean = apply_filters( 'divi_squad_should_clean_admin_notices', true, $screen );

			if ( ! $should_clean ) {
				return;
			}

			/**
			 * Fires before admin notices are cleaned.
			 *
			 * @since 3.3.0
			 *
			 * @param WP_Screen $screen Current admin screen.
			 */
			do_action( 'divi_squad_before_clean_admin_notices', $screen );

			remove_all_actions( 'admin_notices' );
			remove_all_actions( 'network_admin_notices' );
			remove_all_actions( 'all_admin_notices' );
			remove_all_actions( 'user_admin_notices' );

			/**
			 * Fires after admin notices are cleaned.
			 *
			 * @since 3.3.0
			 *
			 * @param WP_Screen $screen Current admin screen.
			 */
			do_action( 'divi_squad_after_clean_admin_notices', $screen );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to clean admin content section' );
		}
	}

	/**
	 * Remove all third party dependencies from the squad template pages.
	 *
	 * @return void
	 */
	public function clean_third_party_deps(): void {
		try {
			global $wp_scripts, $wp_styles;

			if ( ! Helper::is_squad_page() ) {
				return;
			}

			/**
			 * Filter whether to clean third party dependencies on squad pages.
			 *
			 * @since 3.3.0
			 *
			 * @param bool $should_clean Whether to clean third party dependencies.
			 */
			$should_clean = apply_filters( 'divi_squad_should_clean_dependencies', true );

			if ( ! $should_clean ) {
				return;
			}

			/**
			 * Fires before third party dependencies are cleaned.
			 *
			 * @since 3.3.0
			 *
			 * @param WP_Scripts $wp_scripts WordPress scripts registry.
			 * @param WP_Styles  $wp_styles  WordPress styles registry.
			 */
			do_action( 'divi_squad_before_clean_dependencies', $wp_scripts, $wp_styles );

			// Dequeue the scripts and styles of the current page that are not required.
			$this->remove_unnecessary_dependencies( $wp_scripts );
			$this->remove_unnecessary_dependencies( $wp_styles );

			/**
			 * Fires after third party dependencies are cleaned.
			 *
			 * @since 3.3.0
			 *
			 * @param WP_Scripts $wp_scripts WordPress scripts registry.
			 * @param WP_Styles  $wp_styles  WordPress styles registry.
			 */
			do_action( 'divi_squad_after_clean_dependencies', $wp_scripts, $wp_styles );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to clean third party dependencies' );
		}
	}

	/**
	 * Remove unnecessary styles from the current page.
	 *
	 * @param WP_Scripts|WP_Styles $root The Core class of dependencies.
	 *
	 * @return void
	 */
	public function remove_unnecessary_dependencies( $root ): void {
		try {
			// Get site url.
			$site_url = home_url( '/' );

			// Get the dependencies of the squad asset handles.
			$scripts_deps = $this->get_squad_dependencies( $root->registered );

			// Allowed plugin paths.
			$allowed_plugin_defaults = array( 'squad-modules', 'divi-builder', 'query-monitor', 'wp-console' );

			/**
			 * Filter the list of allowed plugin paths that won't be cleaned up.
			 *
			 * @since 3.3.0
			 *
			 * @param array                $allowed_plugin_paths The allowed plugin paths.
			 * @param WP_Scripts|WP_Styles $root                 The Core class of dependencies.
			 *
			 * @return array
			 */
			$allowed_plugin_paths = apply_filters( 'divi_squad_dependencies_cleaning_allowed_plugin_paths', $allowed_plugin_defaults, $root );

			/**
			 * Remove all dependencies of the current page that are not required.
			 *
			 * @see https://developer.wordpress.org/reference/classes/wp_styles/
			 * @see https://developer.wordpress.org/reference/classes/wp_scripts/
			 */
			foreach ( $root->registered as $dependency ) {
				if ( ! $dependency instanceof _WP_Dependency || false === $dependency->src ) {
					continue;
				}

				// Check if the dependency should be dequeued and removed.
				$should_remove = ! in_array( $dependency->handle, $scripts_deps, true ) && strpos( $dependency->src, $site_url ) !== false;

				// Check allowed plugin paths.
				foreach ( $allowed_plugin_paths as $plugin_path ) {
					if ( strpos( $dependency->src, "wp-content/plugins/$plugin_path" ) !== false ) {
						$should_remove = false;
						break;
					}
				}

				// Check if the dependency is from allowed divi or divi-based theme.
				if ( strpos( $dependency->src, 'wp-content/themes/' ) !== false && Divi::is_any_divi_theme_active() ) {
					$should_remove = false;
				}

				/**
				 * Filter whether a specific dependency should be removed.
				 *
				 * @since 3.3.0
				 *
				 * @param bool                 $should_remove Whether to remove the dependency.
				 * @param _WP_Dependency       $dependency    The dependency.
				 * @param array                $scripts_deps  List of dependencies needed by squad scripts.
				 * @param WP_Scripts|WP_Styles $root          The Core class of dependencies.
				 *
				 * @return bool
				 */
				$should_remove = apply_filters( 'divi_squad_should_remove_dependency', $should_remove, $dependency, $scripts_deps, $root );

				// Dequeue and remove the dependency if it should be removed.
				if ( $should_remove ) {
					$root->dequeue( $dependency->handle );
					$root->remove( $dependency->handle );
				}
			}
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to remove unnecessary dependencies' );
		}
	}

	/**
	 * Get the dependencies of the squad scripts.
	 *
	 * @param _WP_Dependency[] $registered The registered scripts.
	 *
	 * @return array<string>
	 */
	public function get_squad_dependencies( array $registered ): array {
		// Store the dependencies of the squad dependencies.
		$dependencies = array();

		/**
		 * Filter the prefixes considered as "squad" dependency prefixes.
		 *
		 * @since 3.3.0
		 *
		 * @param array $prefixes List of prefixes to consider as squad dependencies.
		 *
		 * @return array
		 */
		$prefixes = apply_filters( 'divi_squad_dependency_prefixes', array( 'squad-' ) );

		// Get the dependencies of the squad asset handles.
		foreach ( $registered as $dependency ) {
			$is_squad_handle = false;

			if ( isset( $dependency->handle ) && ! empty( $dependency->deps ) ) {
				// Check if the handle starts with any of the squad prefixes.
				foreach ( $prefixes as $prefix ) {
					if ( strpos( $dependency->handle, $prefix ) === 0 ) {
						$is_squad_handle = true;
						break;
					}
				}

				if ( $is_squad_handle ) {
					foreach ( $dependency->deps as $dep ) {
						if ( ! in_array( $dep, $dependencies, true ) ) {
							$dependencies[] = $dep;
						}
					}
				}
			}
		}

		/**
		 * Filter the final list of dependencies needed by squad scripts.
		 *
		 * @since 3.3.0
		 *
		 * @param array            $dependencies List of dependencies.
		 * @param _WP_Dependency[] $registered   The registered scripts.
		 *
		 * @return array
		 */
		return apply_filters( 'divi_squad_script_dependencies', $dependencies, $registered );
	}

	/**
	 * Add default body classes
	 *
	 * @param array<string> $classes Current body classes.
	 *
	 * @return array<string> Modified body classes
	 */
	public function add_default_classes( array $classes ): array {
		try {
			// Add plugin-specific classes.
			$classes[] = divi_squad()->get_plugin_life_type();
			$classes[] = sprintf( 'core-v%s', divi_squad()->get_version_hyphen() );

			// Add environment class.
			if ( divi_squad()->is_dev() ) {
				$classes[] = 'dev-mode';
			}

			// Add context classes.
			if ( is_admin() ) {
				$classes[] = 'admin';
			}

			if ( class_exists( 'ET_Builder_Element' ) ) {
				$classes[] = 'has-divi-builder';
			}

			// Add plugin page class.
			if ( Helper::is_squad_page() ) {
				$classes[] = 'plugin-page';
			}

			return array_unique( $classes );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to add default body classes' );

			return $classes; // Return original classes as fallback.
		}
	}

	/**
	 * Register a script with WordPress
	 *
	 * @param string                                                                                                                            $handle Script identifier.
	 * @param array{ file: string, path?: string, prod_file?: string, dev_file?: string, pattern?: string, ext?: string, deps?: array<string> } $config Asset configuration.
	 * @param array{in_footer?: bool, strategy?: 'defer'|'async'|null}                                                                          $args   Additional arguments.
	 *
	 * @return bool Whether registration was successful
	 */
	public function register_script( string $handle, array $config, array $args = array() ): bool {
		return $this->register_wp_script( $handle, $config, $args );
	}

	/**
	 * Register a stylesheet with WordPress
	 *
	 * @param string                                                                                                                            $handle Script identifier.
	 * @param array{ file: string, path?: string, prod_file?: string, dev_file?: string, pattern?: string, ext?: string, deps?: array<string> } $config Asset configuration.
	 * @param string                                                                                                                            $media  Media type.
	 *
	 * @return bool Whether registration was successful
	 */
	public function register_style( string $handle, array $config, string $media = 'all' ): bool {
		return $this->register_wp_style( $handle, $config, $media );
	}

	/**
	 * Enqueue a registered script
	 *
	 * @param string               $handle Script identifier.
	 * @param array<string, mixed> $data   Optional data to localize.
	 *
	 * @return bool Whether enqueuing was successful
	 */
	public function enqueue_script( string $handle, array $data = array() ): bool {
		try {
			if ( ! isset( $this->registered_scripts[ $handle ] ) ) {
				return false;
			}

			$full_handle = $this->get_script_full_handle( $handle );
			wp_enqueue_script( $full_handle );

			if ( count( $data ) > 0 ) {
				$localize_handle = $data['handle'] ?? $handle;
				unset( $data['handle'] );

				$this->add_localize_data( $localize_handle, $data );
			}

			/**
			 * Fires after a script is enqueued
			 *
			 * @since 3.3.0
			 *
			 * @param string $handle      Script identifier.
			 * @param string $full_handle Full handle name.
			 * @param array  $data        Data to localize.
			 * @param self   $assets      Assets manager instance.
			 */
			do_action( 'divi_squad_script_enqueued', $handle, $full_handle, $data, $this );

			return true;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, sprintf( 'Failed to enqueue script: %s', $handle ) );

			return false;
		}
	}

	/**
	 * Enqueue a registered stylesheet
	 *
	 * @param string $handle Style identifier.
	 *
	 * @return bool Whether enqueuing was successful
	 */
	public function enqueue_style( string $handle ): bool {
		try {
			if ( ! isset( $this->registered_styles[ $handle ] ) ) {
				return false;
			}

			$full_handle = $this->get_style_full_handle( $handle );
			wp_enqueue_style( $full_handle );

			/**
			 * Fires after a style is enqueued
			 *
			 * @since 3.3.0
			 *
			 * @param string $handle      Style identifier.
			 * @param string $full_handle Full handle name.
			 * @param self   $assets      Assets manager instance.
			 */
			do_action( 'divi_squad_style_enqueued', $handle, $full_handle, $this );

			return true;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, sprintf( 'Failed to enqueue style: %s', $handle ) );

			return false;
		}
	}

	/**
	 * Deregister a script
	 *
	 * @param string $handle Script identifier.
	 *
	 * @return bool Whether deregistration was successful
	 */
	public function deregister_script( string $handle ): bool {
		try {
			if ( ! isset( $this->registered_scripts[ $handle ] ) ) {
				return false;
			}

			$full_handle = $this->get_script_full_handle( $handle );
			wp_deregister_script( $full_handle );

			unset( $this->registered_scripts[ $handle ] );

			/**
			 * Fires after a script is deregistered
			 *
			 * @since 3.3.0
			 *
			 * @param string $handle      Script identifier.
			 * @param string $full_handle Full handle name.
			 * @param self   $assets      Assets manager instance.
			 */
			do_action( 'divi_squad_script_deregistered', $handle, $full_handle, $this );

			return true;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, sprintf( 'Failed to deregister script: %s', $handle ) );

			return false;
		}
	}

	/**
	 * Deregister a style
	 *
	 * @param string $handle Style identifier.
	 *
	 * @return bool Whether deregistration was successful
	 */
	public function deregister_style( string $handle ): bool {
		try {
			if ( ! isset( $this->registered_styles[ $handle ] ) ) {
				return false;
			}

			$full_handle = $this->get_style_full_handle( $handle );
			wp_deregister_style( $full_handle );

			unset( $this->registered_styles[ $handle ] );

			/**
			 * Fires after a style is deregistered
			 *
			 * @since 3.3.0
			 *
			 * @param string $handle      Style identifier.
			 * @param string $full_handle Full handle name.
			 * @param self   $assets      Assets manager instance.
			 */
			do_action( 'divi_squad_style_deregistered', $handle, $full_handle, $this );

			return true;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, sprintf( 'Failed to deregister style: %s', $handle ) );

			return false;
		}
	}

	/**
	 * Register frontend assets
	 */
	public function register_frontend_assets(): void {
		try {
			/**
			 * Register frontend assets
			 *
			 * @since 3.3.0
			 *
			 * @param self $assets Assets manager instance
			 */
			do_action( 'divi_squad_register_frontend_assets', $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to register frontend assets' );
		}
	}

	/**
	 * Register admin assets
	 */
	public function register_admin_assets(): void {
		try {
			/**
			 * Register admin assets
			 *
			 * @since 3.3.0
			 *
			 * @param self $assets Assets manager instance
			 */
			do_action( 'divi_squad_register_admin_assets', $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to register admin assets' );
		}
	}

	/**
	 * Enqueue frontend assets
	 */
	public function enqueue_frontend_assets(): void {
		try {
			/**
			 * Enqueue frontend assets
			 *
			 * @since 3.3.0
			 *
			 * @param self $assets Assets manager instance
			 */
			do_action( 'divi_squad_enqueue_frontend_assets', $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to enqueue frontend assets' );
		}
	}

	/**
	 * Enqueue admin assets
	 */
	public function enqueue_admin_assets(): void {
		try {
			/**
			 * Enqueue admin assets
			 *
			 * @since 3.3.0
			 *
			 * @param self $assets Assets manager instance
			 */
			do_action( 'divi_squad_enqueue_admin_assets', $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to enqueue admin assets' );
		}
	}

	/**
	 * Localize scripts
	 */
	public function localize_scripts(): void {
		try {
			/**
			 * Localize scripts
			 *
			 * @since 3.3.0
			 *
			 * @param self $assets Assets manager instance
			 */
			do_action( 'divi_squad_localize_scripts', $this );

			$this->output_localized_data();
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to localize scripts' );
		}
	}

	/**
	 * Get the full handle for a script
	 *
	 * @param string $handle Script identifier.
	 *
	 * @return string Full handle including prefix
	 */
	public function get_script_full_handle( string $handle ): string {
		if ( isset( $this->registered_scripts[ $handle ] ) ) {
			return $this->registered_scripts[ $handle ]['handle'];
		}

		return $this->get_prefixed_handle( $handle );
	}

	/**
	 * Get the full handle for a style
	 *
	 * @param string $handle Style identifier.
	 *
	 * @return string Full handle including prefix
	 */
	public function get_style_full_handle( string $handle ): string {
		if ( isset( $this->registered_styles[ $handle ] ) ) {
			return $this->registered_styles[ $handle ]['handle'];
		}

		return $this->get_prefixed_handle( $handle );
	}
}
