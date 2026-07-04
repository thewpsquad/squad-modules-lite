<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Integration API
 *
 * @since      1.0.0
 * @deprecated 3.3.0
 * @package    DiviSquad
 * @author     The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Base\DiviBuilder\Integration;

use DiviSquad\Base\DiviBuilder\Integration;
use DiviSquad\Utils\Asset as AssetUtil;
use DiviSquad\Utils\Divi as DiviUtil;
use DiviSquad\Utils\WP as WPUtil;
use function add_action;
use function et_builder_enabled_for_post;
use function get_the_ID;

/**
 * Integration API Class.
 *
 * @since      1.0.0
 * @deprecated 3.3.0
 * @package    DiviSquad
 */
abstract class ShortcodeAPI extends Integration {

	/**
	 * Loads custom modules when the builder is ready.
	 */
	abstract public function hook_et_builder_ready();

	/**
	 * Performs initialization tasks.
	 */
	public function initialize() {
		// Loads custom modules when the builder is ready.
		add_action( 'et_builder_ready', array( $this, 'hook_et_builder_ready' ), 9 );

		// Load all assets in the admin and frontend area.
		add_action( 'wp_enqueue_scripts', array( $this, 'wp_hook_enqueue_scripts' ), 0 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_hook_enqueue_scripts' ), 0 );
	}

	/**
	 * Enqueues the plugin's scripts and styles for the admin area.
	 */
	public function admin_hook_enqueue_scripts() {
		if ( DiviUtil::is_bfb_enabled() || DiviUtil::is_tb_admin_screen() ) {
			$this->enqueue_backend_styles();
		}

		/**
		 * Fires after the admin scripts are enqueued.
		 *
		 * @since 3.1.4
		 *
		 * @param string $plugin_name The plugin name.
		 */
		do_action( 'divi_squad_shortcode_enqueue_admin_scripts_after', $this->name );
	}

	/**
	 * Enqueues minified (production) or non-minified (hot reloaded) backend styles.
	 */
	public function enqueue_backend_styles() {
		$file_path = "{$this->build_path}/styles/backend-style.css";

		// Ensure backend style CSS file exists.
		if ( file_exists( "$this->plugin_dir/$file_path" ) ) {
			$style_asset_path = AssetUtil::module_asset_path( 'backend-style', array( 'ext' => 'css' ) );
			AssetUtil::enqueue_style( "$this->name-backend", $style_asset_path, array(), 'all', true );
		}

		/**
		 * Fires after the backend styles are enqueued.
		 *
		 * @since 3.1.4
		 *
		 * @param string $plugin_name The plugin name.
		 */
		do_action( 'divi_squad_shortcode_enqueue_backend_styles_after', $this->name );
	}

	/**
	 * Enqueues the plugin's scripts and styles.
	 */
	public function wp_hook_enqueue_scripts(): void {
		// Enqueues non-minified, hot reloaded javascript bundles. (Builder).
		if ( DiviUtil::is_fb_enabled() ) {
			$script_asset_path = AssetUtil::module_asset_path( 'builder-bundle' );
			AssetUtil::enqueue_script( "$this->name-builder", $script_asset_path, array(), true );

			// Load script translations.
			WPUtil::set_script_translations( "$this->name-builder" );
		}

		// Enqueues styles for divi builder including theme and plugin.
		if ( ( DiviUtil::is_tb_admin_screen() || DiviUtil::is_theme_builder_used() ) || ( is_singular() && et_builder_enabled_for_post( get_the_ID() ) ) ) {
			$style_handle_name = DiviUtil::is_fb_enabled() ? "$this->name-builder" : $this->name;
			$style_asset_name  = defined( 'ET_BUILDER_PLUGIN_ACTIVE' ) && ! DiviUtil::is_fb_enabled() ? 'builder-style-dbp' : 'builder-style';
			$style_asset_path  = AssetUtil::module_asset_path( $style_asset_name, array( 'ext' => 'css' ) );
			AssetUtil::enqueue_style( $style_handle_name, $style_asset_path, array(), 'all', true );
		}

		if ( DiviUtil::is_fb_enabled() && ! DiviUtil::is_bfb_enabled() ) {
			$this->enqueue_backend_styles();
		}

		/**
		 * Fires after the frontend scripts are enqueued.
		 *
		 * @since 3.1.4
		 *
		 * @param string $plugin_name The plugin name.
		 */
		do_action( 'divi_squad_shortcode_enqueue_scripts_after', $this->name );
	}
}
