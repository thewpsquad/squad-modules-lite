<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Modules Asset Management for DiviSquad.
 *
 * This file contains the Modules class which handles the registration and enqueuing
 * of scripts and styles for the DiviSquad plugin's modules, both in the frontend
 * and in the Divi Builder.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   3.0.0
 */

namespace DiviSquad\Managers\Assets;

use DiviSquad\Base\Factories\PluginAsset\Asset;
use DiviSquad\Utils\Asset as AssetUtil;
use DiviSquad\Utils\Divi as DiviUtil;
use Throwable;

/**
 * Modules class for managing module-related assets.
 *
 * This class is responsible for registering and enqueuing scripts and styles
 * for DiviSquad modules, both in the frontend and in the Divi Builder.
 *
 * @since 3.0.0
 * @package DiviSquad
 */
class Modules extends Asset {

	/**
	 * Enqueue scripts and styles for modules.
	 *
	 * This method is the main entry point for enqueueing module-specific assets.
	 * It checks if the current context is frontend and delegates to specific methods
	 * for enqueueing scripts and styles.
	 *
	 * @since 3.0.0
	 *
	 * @param string $type        The type of the script. Default is 'frontend'.
	 * @param string $hook_suffix The hook suffix for the current admin page.
	 */
	public function enqueue_scripts( $type = 'frontend', $hook_suffix = '' ) {
		if ( 'frontend' !== $type ) {
			return;
		}

		/**
		 * Fires before frontend scripts are enqueued.
		 *
		 * @since 3.0.0
		 *
		 * @param string $hook_suffix The current admin page hook suffix.
		 */
		do_action( 'divi_squad_before_enqueue_frontend_scripts', $hook_suffix );

		$this->enqueue_frontend_scripts();
		$this->enqueue_builder_scripts();

		/**
		 * Fires after frontend scripts are enqueued.
		 *
		 * @since 3.0.0
		 *
		 * @param string $hook_suffix The current admin page hook suffix.
		 */
		do_action( 'divi_squad_after_enqueue_frontend_scripts', $hook_suffix );
	}

	/**
	 * Get localized script data for modules.
	 *
	 * This method prepares data to be localized and made available to JavaScript
	 * for use with modules.
	 *
	 * @since 3.0.0
	 *
	 * @param string       $type The type of the localize data. Default is 'raw'.
	 * @param string|array $data The data to localize.
	 * @return string|array
	 */
	public function get_localize_data( $type = 'raw', $data = array() ) {
		if ( 'output' === $type && DiviUtil::is_fb_enabled() ) {
			$localize = apply_filters( 'divi_squad_assets_builder_backend_extra_data', array() );
			$data    .= sprintf( 'window.DISQBuilderLocalize = %s;', wp_json_encode( $localize ) );
		}

		/**
		 * Filters the localized script data for modules.
		 *
		 * @since 3.0.0
		 *
		 * @param string|array $data The localized data.
		 * @param string       $type The type of the localize data.
		 */
		return apply_filters( 'divi_squad_localize_script_data', $data, $type );
	}

	/**
	 * Enqueue frontend scripts for modules.
	 *
	 * This method handles the registration and enqueuing of scripts
	 * needed for DiviSquad modules in the frontend.
	 *
	 * @since 3.0.0
	 */
	protected function enqueue_frontend_scripts() {
		try {
			$this->register_vendor_scripts();
			$this->register_module_scripts();

			/**
			 * Fires after frontend scripts are registered.
			 *
			 * @since 3.0.0
			 */
			do_action( 'divi_squad_after_register_frontend_scripts' );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'An error occurred while registering frontend scripts.' );
		}
	}

	/**
	 * Register vendor scripts used by modules.
	 *
	 * This method registers third-party scripts that are used by DiviSquad modules.
	 *
	 * @since 3.0.0
	 */
	protected function register_vendor_scripts() {
		$core_asset_deps = array( 'jquery' );

		$vendor_scripts = array(
			'vendor-lottie'         => 'lottie',
			'vendor-typed'          => 'typed.umd',
			'vendor-light-gallery'  => array( 'lightgallery.umd', array( 'prod_file' => 'lightgallery' ) ),
			'vendor-images-loaded'  => 'imagesloaded.pkgd',
			'vendor-scrolling-text' => 'jquery.marquee',
		);

		/**
		 * Filters the vendor scripts to be registered.
		 *
		 * @since 3.0.0
		 *
		 * @param array $vendor_scripts An array of vendor scripts.
		 */
		$vendor_scripts = apply_filters( 'divi_squad_vendor_scripts', $vendor_scripts );

		foreach ( $vendor_scripts as $handle => $script ) {
			$path = is_array( $script ) ? $script[0] : $script;
			$args = is_array( $script ) ? $script[1] : array();
			AssetUtil::register_script( $handle, AssetUtil::vendor_asset_path( $path, $args ), $core_asset_deps );
		}

		$this->register_magnific_popup();
	}

	/**
	 * Register module-specific scripts.
	 *
	 * This method registers scripts that are specific to individual DiviSquad modules.
	 *
	 * @since 3.0.0
	 */
	protected function register_module_scripts() {
		$modules_configs = array(
			'divider'         => array(),
			'ba-image-slider' => array(),
			'accordion'       => array(),
			'gallery'         => array(),
			'scrolling-text'  => array(),
			'lottie'          => array( 'squad-vendor-lottie' ),
			'typing-text'     => array( 'squad-vendor-typed' ),
			'video-popup'     => array( 'magnific-popup' ),
			'post-grid'       => array( 'wp-api-fetch' ),
		);

		/**
		 * Filters the module scripts to be registered.
		 *
		 * @since 3.0.0
		 *
		 * @param array $modules_configs An array of module scripts and their dependencies.
		 */
		$modules_configs = apply_filters( 'divi_squad_module_scripts', $modules_configs );

		foreach ( $modules_configs as $module => $configs ) {
			$script_path = AssetUtil::module_asset_path( "modules/{$module}-bundle" );
			$script_deps = array_merge( array( 'jquery' ), $configs );

			AssetUtil::register_script( "module-{$module}", $script_path, $script_deps );
		}
	}

	/**
	 * Enqueue scripts for the Divi Builder.
	 *
	 * This method handles the enqueuing of scripts needed for DiviSquad modules
	 * when used within the Divi Builder.
	 *
	 * @since 3.0.0
	 */
	protected function enqueue_builder_scripts() {
		if ( ! DiviUtil::is_fb_enabled() ) {
			return;
		}

		try {
			$this->enqueue_vendor_scripts();
			$this->enqueue_form_styles();
			$this->enqueue_magnific_popup();

			/**
			 * Fires after Divi Builder scripts are enqueued.
			 *
			 * @since 3.0.0
			 */
			do_action( 'divi_squad_after_enqueue_builder_scripts' );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'An error occurred while enqueuing builder scripts.' );
		}
	}

	/**
	 * Enqueue vendor scripts for the Divi Builder.
	 *
	 * This method enqueues third-party scripts needed in the Divi Builder context.
	 *
	 * @since 3.0.0
	 */
	protected function enqueue_vendor_scripts() {
		$vendor_scripts = array(
			'squad-vendor-typed',
			'squad-vendor-imagesloaded',
			'squad-vendor-lightgallery',
			'squad-vendor-scrolling-text',
			'squad-module-video-popup',
		);

		foreach ( $vendor_scripts as $script ) {
			wp_enqueue_script( $script );
		}
	}

	/**
	 * Enqueue form styles for the Divi Builder.
	 *
	 * This method enqueues styles for various form plugins when used within the Divi Builder.
	 *
	 * @since 3.0.0
	 */
	protected function enqueue_form_styles() {
		$this->enqueue_contact_form7_style();
		$this->enqueue_wpforms_style();
		$this->enqueue_gravity_forms_style();
		$this->enqueue_ninja_forms_style();
		$this->enqueue_fluent_forms_style();
		// $this->enqueue_formidable_forms_style();
		$this->enqueue_forminator_forms_style();
	}

	/**
	 * Enqueue Contact Form 7 styles.
	 *
	 * @since 3.0.0
	 */
	protected function enqueue_contact_form7_style() {
		if ( class_exists( 'WPCF7' ) ) {
			wp_enqueue_style( 'contact-form-7' );
		}
	}

	/**
	 * Enqueue WPForms styles.
	 *
	 * @since 3.0.0
	 */
	protected function enqueue_wpforms_style() {
		if ( ! function_exists( 'wpforms' ) ) {
			return;
		}

		$min         = wpforms_get_min_suffix();
		$wp_forms_re = wpforms_get_render_engine();
		$disable_css = absint( wpforms_setting( 'disable-css', '1' ) );
		$style_name  = 1 === $disable_css ? 'full' : 'base';

		if ( ! defined( 'WPFORMS_PLUGIN_URL' ) || ! defined( 'WPFORMS_VERSION' ) ) {
			return;
		}

		$style_handle = "wpforms-{$wp_forms_re}-{$style_name}";
		if ( ! wp_style_is( $style_handle, 'registered' ) ) {
			$style_path = WPFORMS_PLUGIN_URL . "assets/css/frontend/{$wp_forms_re}/wpforms-{$style_name}{$min}.css";

			wp_register_style( $style_handle, $style_path, array(), WPFORMS_VERSION );
		}

		wp_enqueue_style( $style_handle );
	}

	/**
	 * Enqueue Gravity Forms styles.
	 *
	 * @since 3.0.0
	 */
	protected function enqueue_gravity_forms_style() {
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
			'gform_theme_components' => "assets/css/dist/theme-components{$min}.css",
			'gform_theme_ie11'       => "assets/css/dist/theme-ie11{$min}.css",
			'gform_basic'            => "assets/css/dist/basic{$min}.css",
			'gform_theme'            => "assets/css/dist/theme{$min}.css",
		);

		foreach ( $styles as $handle => $path ) {
			if ( ! wp_style_is( $handle, 'registered' ) ) {
				wp_register_style( $handle, "{$base_url}/{$path}", array(), $version );
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
	 * @param string $min      Minification suffix
	 */
	protected function register_and_enqueue_gf_modern_styles( string $base_url, string $version, string $min ) {
		$styles = array(
			'gravity_forms_theme_reset'      => "assets/css/dist/gravity-forms-theme-reset{$min}.css",
			'gravity_forms_theme_foundation' => "assets/css/dist/gravity-forms-theme-foundation{$min}.css",
			'gravity_forms_theme_framework'  => "assets/css/dist/gravity-forms-theme-framework{$min}.css",
			'gravity_forms_orbital_theme'    => "assets/css/dist/gravity-forms-orbital-theme{$min}.css",
		);

		foreach ( $styles as $handle => $path ) {
			if ( ! wp_style_is( $handle, 'registered' ) ) {
				wp_register_style( $handle, "{$base_url}/{$path}", array(), $version );
			}
		}

		wp_enqueue_style( 'gravity_forms_theme_reset' );
		wp_enqueue_style( 'gravity_forms_theme_foundation' );
		wp_enqueue_style( 'gravity_forms_theme_framework' );
		wp_enqueue_style( 'gravity_forms_orbital_theme' );
	}

	/**
	 * Enqueue Ninja Forms styles.
	 *
	 * @since 3.0.0
	 */
	protected function enqueue_ninja_forms_style() {
		if ( ! function_exists( 'Ninja_Forms' ) ) {
			return;
		}

		$ver     = \Ninja_Forms::VERSION;
		$css_dir = \Ninja_Forms::$url . 'assets/css/';

		$style = \Ninja_Forms()->get_setting( 'opinionated_styles' );
		switch ( $style ) {
			case 'light':
			case 'dark':
				wp_enqueue_style( 'nf-display', "{$css_dir}display-opinions-{$style}.css", array( 'dashicons' ), $ver );
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
	 * Enqueue Fluent Forms styles.
	 *
	 * @since 3.0.0
	 */
	protected function enqueue_fluent_forms_style() {
		if ( ! function_exists( 'wpFluentForm' ) ) {
			return;
		}

		$rtl_suffix          = is_rtl() ? '-rtl' : '';
		$fluent_form_css     = fluentFormMix( "css/fluent-forms-public{$rtl_suffix}.css" );
		$fluent_form_def_css = fluentFormMix( "css/fluentform-public-default{$rtl_suffix}.css" );

		wp_enqueue_style( 'fluent-form-styles', $fluent_form_css, array(), \FLUENTFORM_VERSION );
		wp_enqueue_style( 'fluentform-public-default', $fluent_form_def_css, array(), \FLUENTFORM_VERSION );
	}

	/**
	 * Enqueue Formidable Forms styles.
	 *
	 * @since 3.2.0
	 */
	protected function enqueue_forminator_forms_style() {
		if ( ! class_exists( '\Forminator_API' ) ) {
			return;
		}

		\Forminator_Assets_Enqueue::fui_enqueue_style( 'forminator-ui-icons', forminator_plugin_url() . 'assets/forminator-ui/css/forminator-icons.min.css', array(), \FORMINATOR_VERSION );
		\Forminator_Assets_Enqueue::fui_enqueue_style( 'forminator-ui-utilities', forminator_plugin_url() . 'assets/forminator-ui/css/src/forminator-utilities.min.css', array(), \FORMINATOR_VERSION );
		\Forminator_Assets_Enqueue::fui_enqueue_style( 'forminator-ui-grid-open', forminator_plugin_url() . 'assets/forminator-ui/css/src/grid/forminator-grid.open.min.css', array(), \FORMINATOR_VERSION );
		\Forminator_Assets_Enqueue::fui_enqueue_style( 'forminator-ui-grid-enclosed', forminator_plugin_url() . 'assets/forminator-ui/css/src/grid/forminator-grid.enclosed.min.css', array(), \FORMINATOR_VERSION );
		\Forminator_Assets_Enqueue::fui_enqueue_style( 'forminator-ui-basic', forminator_plugin_url() . 'assets/forminator-ui/css/forminator-base.min.css', array(), \FORMINATOR_VERSION );
		\Forminator_Assets_Enqueue::fui_enqueue_style( 'forminator-ui', forminator_plugin_url() . 'assets/forminator-ui/css/src/forminator-ui.min.css', array(), \FORMINATOR_VERSION );
	}

	/**
	 * Register Magnific Popup script.
	 *
	 * @since 3.0.0
	 */
	protected function register_magnific_popup() {
		$script_path  = '/includes/builder/feature/dynamic-assets/assets/js/magnific-popup.js';
		$template_dir = get_template_directory();
		$template_uri = get_template_directory_uri();

		if ( ! wp_script_is( 'magnific-popup', 'registered' ) && file_exists( $template_dir . $script_path ) ) {
			wp_register_script( 'magnific-popup', $template_uri . $script_path, array( 'jquery' ), divi_squad()->get_version(), true );
		}
	}

	/**
	 * Enqueue Magnific Popup script.
	 *
	 * @since 3.0.0
	 */
	protected function enqueue_magnific_popup() {
		if ( wp_script_is( 'magnific-popup', 'registered' ) ) {
			wp_enqueue_script( 'magnific-popup' );
		}
	}
}
