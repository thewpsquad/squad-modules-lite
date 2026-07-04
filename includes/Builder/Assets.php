<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Module Assets Manager
 *
 * Handles registration and enqueuing of module-specific assets using the unified asset system.
 *
 * @since   3.3.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder;

use DiviSquad\Core\Assets as Assets_Manager;
use DiviSquad\Core\Contracts\Hookable;
use DiviSquad\Utils\Divi as DiviUtil;
use Throwable;

/**
 * Module Assets Manager
 *
 * @since 3.3.0
 */
class Assets implements Hookable {
	/**
	 * Initialize the modules asset manager
	 */
	public function __construct() {
		$this->register_hooks();
	}

	/**
	 * Initialize hooks
	 */
	public function register_hooks(): void {
		// Register assets.
		add_action( 'divi_squad_register_frontend_assets', array( $this, 'register' ) );
		add_action( 'divi_squad_enqueue_frontend_assets', array( $this, 'enqueue' ) );

		// Register Divi Builder assets.
		add_action( 'divi_squad_enqueue_frontend_assets', array( $this, 'enqueue_builder' ) );
	}

	/**
	 * Register module assets
	 *
	 * @param Assets_Manager $assets Assets manager instance.
	 */
	public function register( Assets_Manager $assets ): void {
		try {
			// Register all vendor scripts first.
			$this->register_magnific_popup( $assets );
			$this->register_vendor_scripts( $assets );
			$this->register_vendor_styles( $assets );

			// Register module scripts and styles.
			$this->register_module_scripts( $assets );
			$this->register_module_styles( $assets );

			/**
			 * Fires after module assets are registered
			 *
			 * @param Assets_Manager $assets Assets manager instance
			 */
			do_action( 'divi_squad_module_assets_registered', $assets );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to register module assets' );
		}
	}

	/**
	 * Enqueue frontend assets
	 *
	 * @param Assets_Manager $assets Assets manager instance.
	 */
	public function enqueue( Assets_Manager $assets ): void {
		try {
			// Always enqueue common styles.
			$this->enqueue_common_styles( $assets );

			/**
			 * Fires after module assets are enqueued
			 *
			 * @param Assets_Manager $assets Assets manager instance
			 */
			do_action( 'divi_squad_module_assets_enqueued', $assets );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to enqueue module assets' );
		}
	}

	/**
	 * Enqueue builder assets
	 *
	 * @param Assets_Manager $assets Assets manager instance.
	 */
	public function enqueue_builder( Assets_Manager $assets ): void {
		if ( ! DiviUtil::is_fb_enabled() ) {
			return;
		}

		try {
			// Enqueue form plugin styles.
			$this->enqueue_form_styles();

			// Enqueue module-specific styles.
			$assets->enqueue_script( 'magnific-popup' );
			$assets->enqueue_style( 'magnific-popup' );

			// Enqueue vendor scripts.
			$assets->enqueue_script( 'vendor-lottie' );
			$assets->enqueue_script( 'vendor-typed' );
			$assets->enqueue_script( 'vendor-light-gallery' );
			$assets->enqueue_script( 'vendor-images-loaded' );
			$assets->enqueue_script( 'vendor-scrolling-text' );

			// Enqueue vendor styles.
			$assets->enqueue_style( 'vendor-light-gallery' );

			/**
			 * Fires after builder assets are enqueued
			 *
			 * @param Assets_Manager $assets Assets manager instance
			 */
			do_action( 'divi_squad_builder_assets_enqueued', $assets );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to enqueue builder assets' );
		}
	}

	/**
	 * Register vendor scripts
	 *
	 * @param Assets_Manager $assets Assets manager instance.
	 */
	private function register_vendor_scripts( Assets_Manager $assets ): void {
		$vendor_scripts = array(
			'lottie'         => array(
				'file' => 'lottie',
				'path' => 'vendor',
				'deps' => array( 'jquery' ),
			),
			'typed'          => array(
				'file' => 'typed.umd',
				'path' => 'vendor',
				'deps' => array( 'jquery' ),
			),
			'light-gallery'  => array(
				'file'     => 'lightgallery',
				'dev_file' => 'lightgallery.umd',
				'path'     => 'vendor',
				'deps'     => array( 'jquery' ),
			),
			'images-loaded'  => array(
				'file' => 'imagesloaded.pkgd',
				'path' => 'vendor',
				'deps' => array( 'jquery' ),
			),
			'scrolling-text' => array(
				'file' => 'jquery.marquee',
				'path' => 'vendor',
				'deps' => array( 'jquery' ),
			),
		);

		/**
		 * Filter vendor script configurations
		 *
		 * @param array         $vendor_scripts Script configurations
		 * @param Assets_Manager $assets         Assets manager instance.
		 */
		$vendor_scripts = apply_filters( 'divi_squad_vendor_scripts', $vendor_scripts, $assets );

		foreach ( $vendor_scripts as $handle => $config ) {
			$assets->register_script( "vendor-$handle", $config );
		}
	}

	/**
	 * Register vendor styles
	 *
	 * @param Assets_Manager $assets Assets manager instance.
	 */
	private function register_vendor_styles( Assets_Manager $assets ): void {
		$vendor_styles = array(
			'light-gallery' => array(
				'file' => 'lightgallery',
				'path' => 'vendor',
			),
		);

		/**
		 * Filter vendor style configurations
		 *
		 * @param array         $vendor_styles Style configurations
		 * @param Assets_Manager $assets        Assets manager instance.
		 */
		$vendor_styles = apply_filters( 'divi_squad_vendor_styles', $vendor_styles, $assets );

		foreach ( $vendor_styles as $handle => $config ) {
			if ( ! wp_style_is( $handle, 'registered' ) ) {
				$assets->register_style( "vendor-$handle", $config );
			}
		}
	}

	/**
	 * Register Magnific Popup
	 *
	 * @param Assets_Manager $assets Assets manager instance.
	 */
	private function register_magnific_popup( Assets_Manager $assets ): void {
		$storage_path = get_template_directory_uri();
		if ( defined( 'ET_BUILDER_PLUGIN_URI' ) && DiviUtil::is_divi_builder_plugin_active() ) {
			$storage_path = ET_BUILDER_PLUGIN_URI;
		}

		// Register magnific-popup script.
		if ( ! wp_script_is( 'magnific-popup', 'registered' ) ) {
			$assets->register_script(
				'magnific-popup',
				array(
					'file'     => 'includes/builder/feature/dynamic-assets/assets/js/magnific-popup',
					'path'     => $storage_path,
					'deps'     => array( 'jquery' ),
					'external' => true,
				)
			);
		}

		// Register magnific-popup style.
		if ( ! wp_style_is( 'magnific-popup', 'registered' ) ) {
			$assets->register_style(
				'magnific-popup',
				array(
					'file'     => 'includes/builder/feature/dynamic-assets/assets/css/magnific_popup',
					'path'     => $storage_path,
					'deps'     => array( 'dashicons' ),
					'external' => true,
				)
			);
		}
	}

	/**
	 * Register module-specific scripts
	 *
	 * @param Assets_Manager $assets Assets manager instance.
	 */
	private function register_module_scripts( Assets_Manager $assets ): void {
		$configs = array(
			'divider'         => array(),
			'ba-image-slider' => array(
				'deps' => array( 'squad-vendor-images-loaded' ),
			),
			'gallery'         => array(
				'deps' => array(),
			),
			'scrolling-text'  => array(
				'deps' => array( 'squad-vendor-scrolling-text' ),
			),
			'lottie'          => array(
				'deps' => array( 'squad-vendor-lottie' ),
			),
			'typing-text'     => array(
				'deps' => array( 'squad-vendor-typed' ),
			),
			'video-popup'     => array(
				'deps' => array( 'magnific-popup' ),
			),
			'post-grid'       => array(
				'deps' => array( 'wp-api-fetch' ),
			),
		);

		/**
		 * Filter module configurations
		 *
		 * @param array         $configs Module configurations
		 * @param Assets_Manager $assets  Assets manager instance.
		 */
		$module_configs = apply_filters( 'divi_squad_module_assets_configs', $configs, $assets );

		foreach ( $module_configs as $module => $config ) {
			$script_config = array(
				'file' => "modules/$module-bundle",
				'path' => 'divi-builder-4',
				'deps' => array( 'jquery' ),
			);

			/**
			 * Merge module-specific configurations with default configurations
			 *
			 * @var array{ file: string, path?: string, prod_file?: string, dev_file?: string, pattern?: string, ext?: string, deps?: array<string> } $script_config
			 */
			$script_config = wp_parse_args( $script_config, $config );

			$assets->register_script( "module-$module", $script_config );

			// Register corresponding style if exists.
			$style_deps = $config['style_deps'] ?? array();

			// Remove dependencies that are not needed for styles.
			unset( $config['deps'], $config['style_deps'] );

			$style_config = array(
				'file' => "modules/$module",
				'path' => 'divi-builder-4',
				'deps' => $style_deps,
				'ext'  => 'css',
			);

			/**
			 * Merge module-specific configurations with default configurations
			 *
			 * @var array{ file: string, path?: string, prod_file?: string, dev_file?: string, pattern?: string, ext?: string, deps?: array<string> } $style_config
			 */
			$style_config = wp_parse_args( $style_config, $config );

			if ( $assets->is_asset_path_exist( $style_config ) ) {
				$assets->register_style( "module-$module", $style_config );
			}
		}
	}

	/**
	 * Register module-specific styles
	 *
	 * @param Assets_Manager $assets Assets manager instance.
	 */
	public function register_module_styles( Assets_Manager $assets ): void {
		// Register module-specific styles.
	}

	/**
	 * Enqueue form styles
	 */
	private function enqueue_form_styles(): void {
		$this->enqueue_cf7_styles();
		$this->enqueue_wpforms_styles();
		$this->enqueue_gravity_styles();
		$this->register_ninja_styles();
		$this->register_fluent_styles();
		$this->register_forminator_styles();
	}

	// Form plugin style registration methods.

	/**
	 * Register Contact Form 7 styles
	 */
	private function enqueue_cf7_styles(): void {
		if ( ! class_exists( 'WPCF7' ) ) {
			return;
		}

		wp_enqueue_style( 'contact-form-7' );
	}

	/**
	 * Register WPForms styles
	 */
	private function enqueue_wpforms_styles(): void {
		if ( ! function_exists( 'wpforms' ) ) {
			return;
		}

		try {
			$min         = \wpforms_get_min_suffix();
			$wp_forms_re = \wpforms_get_render_engine();
			$disable_css = absint( \wpforms_setting( 'disable-css', '1' ) );
			$style_name  = 1 === $disable_css ? 'full' : 'base';

			if ( ! defined( 'WPFORMS_PLUGIN_URL' ) || ! defined( 'WPFORMS_VERSION' ) ) {
				return;
			}

			$style_handle = "wpforms-$wp_forms_re-$style_name";
			if ( ! wp_style_is( $style_handle, 'registered' ) ) {
				$style_path = \WPFORMS_PLUGIN_URL . "assets/css/frontend/$wp_forms_re/wpforms-$style_name$min.css";

				wp_register_style( $style_handle, $style_path, array(), \WPFORMS_VERSION );
			}

			wp_enqueue_style( $style_handle );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to enqueue WPForms styles' );
		}
	}

	/**
	 * Register Gravity Forms styles
	 */
	private function enqueue_gravity_styles(): void {
		if ( ! function_exists( 'gravity_form' ) ) {
			return;
		}

		try {
			$base_url = \GFCommon::get_base_url();
			$version  = \GFForms::$version;
			$dev_mode = defined( 'GF_SCRIPT_DEBUG' ) && \GF_SCRIPT_DEBUG;
			$min      = $dev_mode ? '' : '.min';

			$this->register_and_enqueue_gf_legacy_styles( $base_url, $version, $min );
			$this->register_and_enqueue_gf_modern_styles( $base_url, $version, $min );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to enqueue Gravity Forms styles' );
		}
	}

	/**
	 * Register and enqueue legacy Gravity Forms styles.
	 *
	 * @since 3.0.0
	 *
	 * @param string $base_url Base URL for Gravity Forms.
	 * @param string $version  Gravity Forms version.
	 * @param string $min      Minification suffix.
	 */
	protected function register_and_enqueue_gf_legacy_styles( string $base_url, string $version, string $min ): void {
		$styles = array(
			'gform_theme_components' => "assets/css/dist/theme-components$min.css",
			'gform_theme_ie11'       => "assets/css/dist/theme-ie11$min.css",
			'gform_basic'            => "assets/css/dist/basic$min.css",
			'gform_theme'            => "assets/css/dist/theme$min.css",
		);

		foreach ( $styles as $handle => $path ) {
			if ( ! wp_style_is( $handle, 'registered' ) ) {
				wp_register_style( $handle, "$base_url/$path", array(), $version );
			}
		}

		wp_enqueue_style( 'gform_basic' );
		wp_enqueue_style( 'gform_theme' );
	}

	/**
	 * Register and enqueue modern Gravity Forms styles.
	 *
	 * @since 3.0.0
	 *
	 * @param string $base_url Base URL for Gravity Forms.
	 * @param string $version  Gravity Forms version.
	 * @param string $min      Minification suffix.
	 */
	protected function register_and_enqueue_gf_modern_styles( string $base_url, string $version, string $min ): void {
		$styles = array(
			'gravity_forms_theme_reset'      => "assets/css/dist/gravity-forms-theme-reset$min.css",
			'gravity_forms_theme_foundation' => "assets/css/dist/gravity-forms-theme-foundation$min.css",
			'gravity_forms_theme_framework'  => "assets/css/dist/gravity-forms-theme-framework$min.css",
			'gravity_forms_orbital_theme'    => "assets/css/dist/gravity-forms-orbital-theme$min.css",
		);

		foreach ( $styles as $handle => $path ) {
			if ( ! wp_style_is( $handle, 'registered' ) ) {
				wp_register_style( $handle, "$base_url/$path", array(), $version );
			}
		}

		wp_enqueue_style( 'gravity_forms_theme_reset' );
		wp_enqueue_style( 'gravity_forms_theme_foundation' );
		wp_enqueue_style( 'gravity_forms_theme_framework' );
		wp_enqueue_style( 'gravity_forms_orbital_theme' );
	}

	/**
	 * Register Ninja Forms styles
	 */
	private function register_ninja_styles(): void {
		if ( ! function_exists( 'Ninja_Forms' ) ) {
			return;
		}

		try {
			$ver     = \Ninja_Forms::VERSION;
			$css_dir = \Ninja_Forms::$url . 'assets/css/';

			$style = (string) \Ninja_Forms()->get_setting( 'opinionated_styles' );
			switch ( $style ) {
				case 'light':
				case 'dark':
					wp_enqueue_style( 'nf-display', "{$css_dir}display-opinions-$style.css", array( 'dashicons' ), $ver );
					wp_enqueue_style( 'nf-font-awesome', "{$css_dir}font-awesome.min.css", array(), $ver );
					break;
				default:
					wp_enqueue_style( 'nf-display', "{$css_dir}display-structure.css", array( 'dashicons' ), $ver );
			}

			wp_enqueue_style( 'jBox', "{$css_dir}jBox.css", array(), $ver );
			wp_enqueue_style( 'rating', "{$css_dir}rating.css", array(), $ver );
			wp_enqueue_style( 'nf-flatpickr', "{$css_dir}flatpickr.css", array(), $ver );

			wp_enqueue_media();
			wp_enqueue_style( 'summernote', "{$css_dir}summernote.css", array(), $ver );
			wp_enqueue_style( 'codemirror', "{$css_dir}codemirror.css", array(), $ver );
			wp_enqueue_style( 'codemirror-monokai', "{$css_dir}monokai-theme.css", array(), $ver );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to enqueue Ninja Forms styles' );
		}
	}

	/**
	 * Register Fluent Forms styles
	 */
	private function register_fluent_styles(): void {
		if ( ! function_exists( 'wpFluentForm' ) ) {
			return;
		}

		try {
			$rtl_suffix          = is_rtl() ? '-rtl' : '';
			$fluent_form_css     = \fluentFormMix( "css/fluent-forms-public$rtl_suffix.css" );
			$fluent_form_def_css = \fluentFormMix( "css/fluentform-public-default$rtl_suffix.css" );

			wp_enqueue_style( 'fluent-form-styles', $fluent_form_css, array(), \FLUENTFORM_VERSION );
			wp_enqueue_style( 'fluentform-public-default', $fluent_form_def_css, array(), \FLUENTFORM_VERSION );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to enqueue Fluent Forms styles' );
		}
	}

	/**
	 * Register Forminator styles
	 */
	private function register_forminator_styles(): void {
		if ( ! class_exists( \Forminator_API::class ) ) {
			return;
		}

		try {
			// Get the Forminator asset URL.
			$asset_url = \forminator_plugin_url() . 'assets/forminator-ui/css/';

			// Enqueue Forminator styles.
			\Forminator_Assets_Enqueue::fui_enqueue_style( 'forminator-ui-icons', $asset_url . 'forminator-icons.min.css' );
			\Forminator_Assets_Enqueue::fui_enqueue_style( 'forminator-ui-utilities', $asset_url . 'src/forminator-utilities.min.css' );
			\Forminator_Assets_Enqueue::fui_enqueue_style( 'forminator-ui-grid-open', $asset_url . 'src/grid/forminator-grid.open.min.css' );
			\Forminator_Assets_Enqueue::fui_enqueue_style( 'forminator-ui-grid-enclosed', $asset_url . 'src/grid/forminator-grid.enclosed.min.css' );
			\Forminator_Assets_Enqueue::fui_enqueue_style( 'forminator-ui-basic', $asset_url . 'forminator-base.min.css' );
			\Forminator_Assets_Enqueue::fui_enqueue_style( 'forminator-ui', $asset_url . 'src/forminator-ui.min.css' );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to enqueue Forminator styles' );
		}
	}

	/**
	 * Enqueue common styles
	 *
	 * @param Assets_Manager $assets Assets manager instance.
	 */
	private function enqueue_common_styles( Assets_Manager $assets ): void {
		// $assets->enqueue_style( 'handle' );
	}
}
