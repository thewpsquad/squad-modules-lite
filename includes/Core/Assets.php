<?php
/**
 * Assets Manager Class
 *
 * This class handles registration, enqueuing, and management of all assets
 * (scripts and styles) for the plugin, including frontend and admin areas.
 *
 * @since   3.3.0
 * @author  The WP Squad <support@squadmodules.com>
 * @package DiviSquad\Core
 */

namespace DiviSquad\Core;

use DiviSquad\Core\Traits\Assets\Assets_Core;
use DiviSquad\Core\Traits\Assets\Body_Classes;
use DiviSquad\Core\Traits\Assets\Brand_Assets;
use DiviSquad\Core\Traits\Assets\Localization;
use DiviSquad\Core\Traits\Assets\Management;
use DiviSquad\Core\Traits\Assets\Registration;
use DiviSquad\Utils\Helper as HelperUtil;
use Throwable;

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
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks
	 */
	private function init_hooks(): void {
		if ( $this->initialized ) {
			return;
		}

		// Frontend hooks
		add_action( 'wp_enqueue_scripts', array( $this, 'register_frontend_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ), 20 );
		add_action( 'wp_footer', array( $this, 'localize_scripts' ), 5 );

		// Admin hooks
		add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ), 20 );
		add_action( 'admin_footer', array( $this, 'localize_scripts' ), 5 );

		// Body class hooks
		add_filter( 'divi_squad_body_classes', array( $this, 'add_default_classes' ) );
		add_filter( 'body_class', array( $this, 'filter_body_classes' ) );
		add_filter( 'admin_body_class', array( $this, 'filter_admin_body_classes' ) );

		// Brand assets hooks
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
	 * Add default body classes
	 *
	 * @param array<string> $classes Current body classes
	 *
	 * @return array<string> Modified body classes
	 */
	public function add_default_classes( array $classes ): array {
		// Add version class
		$version   = str_replace( '.', '-', divi_squad()->get_version_dot() );
		$classes[] = 'core-v' . $version;

		// Add environment class
		if ( divi_squad()->is_dev() ) {
			$classes[] = 'dev-mode';
		}

		// Add context classes
		if ( is_admin() ) {
			$classes[] = 'admin';
		}

		if ( class_exists( 'ET_Builder_Module' ) ) {
			$classes[] = 'has-divi';
		}

		if ( class_exists( 'ET_Builder_Element' ) ) {
			$classes[] = 'has-divi-builder';
		}

		// Add plugin page class
		if ( HelperUtil::is_squad_page() ) {
			$classes[] = 'plugin-page';
		}

		return array_unique( $classes );
	}

	/**
	 * Register a script with WordPress
	 *
	 * @param string                                                                                                                            $handle Script identifier.
	 * @param array{ file: string, path?: string, prod_file?: string, dev_file?: string, pattern?: string, ext?: string, deps?: array<string> } $config Asset configuration.
	 * @param array{in_footer?: bool, strategy?: 'defer'|'async'|null}                                                                          $args Additional arguments.
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
	 * @param array<string, mixed> $data Optional data to localize.
	 *
	 * @return bool Whether enqueuing was successful
	 */
	public function enqueue_script( string $handle, array $data = array() ): bool {
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
		 * @param array  $data       Data to localize.
		 * @param self   $assets     Assets manager instance.
		 */
		do_action( 'divi_squad_script_enqueued', $handle, $full_handle, $data, $this );

		return true;
	}

	/**
	 * Enqueue a registered stylesheet
	 *
	 * @param string $handle Style identifier
	 *
	 * @return bool Whether enqueuing was successful
	 */
	public function enqueue_style( string $handle ): bool {
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
		 * @param string $handle Style identifier.
		 * @param string $full_handle Full handle name.
		 * @param self   $assets Assets manager instance.
		 */
		do_action( 'divi_squad_style_enqueued', $handle, $full_handle, $this );

		return true;
	}

	/**
	 * Deregister a script
	 *
	 * @param string $handle Script identifier
	 *
	 * @return bool Whether deregistration was successful
	 */
	public function deregister_script( string $handle ): bool {
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
	}

	/**
	 * Deregister a style
	 *
	 * @param string $handle Style identifier
	 *
	 * @return bool Whether deregistration was successful
	 */
	public function deregister_style( string $handle ): bool {
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
	 * @param string $handle Script identifier
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
	 * @param string $handle Style identifier
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
