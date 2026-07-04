<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Module Assets Manager
 *
 * Handles registration and enqueuing of module-specific assets using the unified asset system.
 *
 * @since   3.3.0
 * @author  The WP Squad <support@squadmodules.com>
 * @package DiviSquad
 */

namespace DiviSquad\Builder;

use DiviSquad\Core\Assets as AssetsManager;
use DiviSquad\Utils\Divi as DiviUtil;
use Throwable;

/**
 * Module Assets Manager
 *
 * @since 3.3.0
 */
class Assets {

	/**
	 * Module configurations
	 *
	 * @var array<string, mixed>
	 */
	private array $module_configs = array();

	/**
	 * Initialize the modules asset manager
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks(): void {
		// Register assets.
		add_action( 'divi_squad_register_frontend_assets', array( $this, 'register' ) );
		add_action( 'divi_squad_enqueue_frontend_assets', array( $this, 'enqueue' ) );

		// Register Divi Builder assets.
		add_action( 'divi_squad_enqueue_frontend_assets', array( $this, 'enqueue_builder' ) );

		// Add module configs filter.
		add_filter( 'divi_squad_module_configs', array( $this, 'filter_module_configs' ) );
	}

	/**
	 * Register module assets
	 *
	 * @param AssetsManager $assets Assets manager instance.
	 */
	public function register( AssetsManager $assets ): void {
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
			 * @param AssetsManager $assets  Assets manager instance
			 * @param array         $configs Module configurations
			 */
			do_action( 'divi_squad_module_assets_registered', $assets, $this->module_configs );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to register module assets' );
		}
	}

	/**
	 * Enqueue frontend assets
	 *
	 * @param AssetsManager $assets Assets manager instance.
	 */
	public function enqueue( AssetsManager $assets ): void {
		try {
			// Always enqueue common styles.
			$this->enqueue_common_styles( $assets );

			/**
			 * Fires after module assets are enqueued
			 *
			 * @param AssetsManager $assets Assets manager instance
			 */
			do_action( 'divi_squad_module_assets_enqueued', $assets );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to enqueue module assets' );
		}
	}

	/**
	 * Enqueue builder assets
	 *
	 * @param AssetsManager $assets Assets manager instance.
	 */
	public function enqueue_builder( AssetsManager $assets ): void {
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
			 * @param AssetsManager $assets Assets manager instance
			 */
			do_action( 'divi_squad_builder_assets_enqueued', $assets );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to enqueue builder assets' );
		}
	}

	/**
	 * Register vendor scripts
	 *
	 * @param AssetsManager $assets Assets manager instance.
	 */
	private function register_vendor_scripts( AssetsManager $assets ): void {
		$vendor_scripts = array(
			'vendor-lottie'         => array(
				'file' => 'lottie',
				'path' => 'vendor',
				'deps' => array( 'jquery' ),
			),
			'vendor-typed'          => array(
				'file' => 'typed.umd',
				'path' => 'vendor',
				'deps' => array( 'jquery' ),
			),
			'vendor-light-gallery'  => array(
				'file'     => 'lightgallery',
				'dev_file' => 'lightgallery.umd',
				'path'     => 'vendor',
				'deps'     => array( 'jquery' ),
			),
			'vendor-images-loaded'  => array(
				'file' => 'imagesloaded.pkgd',
				'path' => 'vendor',
				'deps' => array( 'jquery' ),
			),
			'vendor-scrolling-text' => array(
				'file' => 'jquery.marquee',
				'path' => 'vendor',
				'deps' => array( 'jquery' ),
			),
		);

		/**
		 * Filter vendor script configurations
		 *
		 * @param array $vendor_scripts Script configurations
		 */
		$vendor_scripts = apply_filters( 'divi_squad_vendor_scripts', $vendor_scripts );

		foreach ( $vendor_scripts as $handle => $config ) {
			$assets->register_script( $handle, $config );
		}
	}

	/**
	 * Register vendor styles
	 *
	 * @param AssetsManager $assets Assets manager instance.
	 */
	private function register_vendor_styles( AssetsManager $assets ): void {
		$vendor_styles = array(
			'vendor-light-gallery' => array(
				'file' => 'lightgallery',
				'path' => 'vendor',
				'ext'  => 'css',
			),
		);

		/**
		 * Filter vendor style configurations
		 *
		 * @param array $vendor_styles Style configurations
		 */
		$vendor_styles = apply_filters( 'divi_squad_vendor_styles', $vendor_styles );

		foreach ( $vendor_styles as $handle => $config ) {
			if ( ! wp_style_is( $handle, 'registered' ) ) {
				$assets->register_style( $handle, $config );
			}
		}
	}

	/**
	 * Register Magnific Popup
	 *
	 * @param AssetsManager $assets Assets manager instance.
	 */
	private function register_magnific_popup( AssetsManager $assets ): void {
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
					'ext'      => 'css',
					'deps'     => array( 'dashicons' ),
					'external' => true,
				)
			);
		}
	}

	/**
	 * Register module-specific scripts
	 *
	 * @param AssetsManager $assets Assets manager instance.
	 */
	private function register_module_scripts( AssetsManager $assets ): void {
		$this->module_configs = $this->get_module_assets_configs();

		foreach ( $this->module_configs as $module => $config ) {
			$script_config = array(
				'file' => "modules/$module-bundle",
				'path' => 'divi-builder-4',
				'deps' => wp_parse_args( $config['deps'] ?? array(), array( 'jquery' ) ),
			);

			$assets->register_script( "module-$module", $script_config );

			// Register corresponding style if exists.
			$style_path = array(
				'file' => "modules/$module",
				'path' => 'divi-builder-4',
				'ext'  => 'css',
			);

			if ( $assets->is_asset_path_exist( $style_path ) ) {
				$assets->register_style( "module-$module", $style_path );
			}
		}
	}

	/**
	 * Register module-specific styles
	 *
	 * @param AssetsManager $assets Assets manager instance.
	 */
	public function register_module_styles( AssetsManager $assets ): void {
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
	}

	/**
	 * Register Gravity Forms styles
	 */
	private function enqueue_gravity_styles(): void {
		if ( ! function_exists( 'gravity_form' ) ) {
			return;
		}

		$base_url = \GFCommon::get_base_url();
		$version  = \GFForms::$version;
		$dev_mode = defined( 'GF_SCRIPT_DEBUG' ) && \GF_SCRIPT_DEBUG;
		$min      = $dev_mode ? '' : '.min';

		$this->register_and_enqueue_gf_legacy_styles( $base_url, $version, $min );
		$this->register_and_enqueue_gf_modern_styles( $base_url, $version, $min );
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
	protected function register_and_enqueue_gf_legacy_styles( string $base_url, string $version, string $min ) {
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
	protected function register_and_enqueue_gf_modern_styles( string $base_url, string $version, string $min ) {
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

		$ver     = \Ninja_Forms::VERSION;
		$css_dir = \Ninja_Forms::$url . 'assets/css/';

		$style = \Ninja_Forms()->get_setting( 'opinionated_styles' );
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
	}

	/**
	 * Register Fluent Forms styles
	 */
	private function register_fluent_styles(): void {
		if ( ! function_exists( 'wpFluentForm' ) ) {
			return;
		}

		$rtl_suffix          = is_rtl() ? '-rtl' : '';
		$fluent_form_css     = \fluentFormMix( "css/fluent-forms-public$rtl_suffix.css" );
		$fluent_form_def_css = \fluentFormMix( "css/fluentform-public-default$rtl_suffix.css" );

		wp_enqueue_style( 'fluent-form-styles', $fluent_form_css, array(), \FLUENTFORM_VERSION );
		wp_enqueue_style( 'fluentform-public-default', $fluent_form_def_css, array(), \FLUENTFORM_VERSION );
	}

	/**
	 * Register Forminator styles
	 */
	private function register_forminator_styles(): void {
		if ( ! class_exists( '\Forminator_API' ) ) {
			return;
		}

		\Forminator_Assets_Enqueue::fui_enqueue_style( 'forminator-ui-icons', \forminator_plugin_url() . 'assets/forminator-ui/css/forminator-icons.min.css', array(), \FORMINATOR_VERSION );
		\Forminator_Assets_Enqueue::fui_enqueue_style( 'forminator-ui-utilities', \forminator_plugin_url() . 'assets/forminator-ui/css/src/forminator-utilities.min.css', array(), \FORMINATOR_VERSION );
		\Forminator_Assets_Enqueue::fui_enqueue_style( 'forminator-ui-grid-open', \forminator_plugin_url() . 'assets/forminator-ui/css/src/grid/forminator-grid.open.min.css', array(), \FORMINATOR_VERSION );
		\Forminator_Assets_Enqueue::fui_enqueue_style( 'forminator-ui-grid-enclosed', \forminator_plugin_url() . 'assets/forminator-ui/css/src/grid/forminator-grid.enclosed.min.css', array(), \FORMINATOR_VERSION );
		\Forminator_Assets_Enqueue::fui_enqueue_style( 'forminator-ui-basic', \forminator_plugin_url() . 'assets/forminator-ui/css/forminator-base.min.css', array(), \FORMINATOR_VERSION );
		\Forminator_Assets_Enqueue::fui_enqueue_style( 'forminator-ui', \forminator_plugin_url() . 'assets/forminator-ui/css/src/forminator-ui.min.css', array(), \FORMINATOR_VERSION );
	}

	/**
	 * Enqueue common styles
	 *
	 * @param AssetsManager $assets Assets manager instance.
	 */
	private function enqueue_common_styles( AssetsManager $assets ): void {
		// $assets->enqueue_style( 'handle' );
	}

	/**
	 * Get module configurations
	 *
	 * @return array
	 */
	private function get_module_assets_configs(): array {
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
		 * @param array $configs Module configurations
		 */
		return apply_filters( 'divi_squad_module_assets_configs', $configs );
	}

	/**
	 * Filter module configurations
	 *
	 * @param array $configs Existing configurations.
	 *
	 * @return array
	 */
	public function filter_module_configs( array $configs ): array {
		return array_merge( $configs, $this->module_configs );
	}
}
