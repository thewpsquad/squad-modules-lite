<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Plugin Branding
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   3.0.0
 */

namespace DiviSquad\Base\Factories;

use DiviSquad\Base\Factories\FactoryBase\Factory;
use DiviSquad\Utils\Helper;
use DiviSquad\Core\Supports\Polyfills\Constant;
use DiviSquad\Core\Traits\Singleton;
use function get_current_screen;

/**
 * Class Plugin Branding
 *
 * @package DiviSquad
 * @since   3.0.0
 */
final class BrandAsset extends Factory {

	use Singleton;

	/**
	 * Store all branding assets.
	 *
	 * @var array<string, BrandAsset\AssetInterface[]>
	 */
	private static array $registries = array(
		'plugin_action_links' => array(),
		'plugin_row_actions'  => array(),
		'admin_footer_text'   => array(),
	);

	/**
	 * Initialize the hooks.
	 *
	 * @return void
	 */
	protected function init_hooks() {
		add_filter( 'plugin_action_links', array( $this, 'add_plugin_actions' ), 0, 2 );
		add_filter( 'network_admin_plugin_action_links', array( $this, 'add_plugin_actions' ), 0, 2 );
		add_filter( 'plugin_row_meta', array( $this, 'add_plugin_row_actions' ), 0, 2 );
		add_filter( 'admin_footer_text', array( $this, 'add_plugin_footer_text' ), Constant::PHP_INT_MAX );
		add_filter( 'update_footer', array( $this, 'update_plugin_footer_text' ), Constant::PHP_INT_MAX );
	}

	/**
	 * Add a new item to the list of items.
	 *
	 * @param string $class_name The class name of the banding asset. Must implement the BrandingAssetInterface.
	 *
	 * @see BrandAsset\AssetInterface interface.
	 * @return void
	 */
	public function add( $class_name ) {
		if ( ! class_exists( $class_name ) ) {
			return;
		}

		$asset = new $class_name();
		if ( ! $asset instanceof BrandAsset\AssetInterface ) {
			return;
		}

		// Store all branding assets.
		if ( array_key_exists( $asset->get_type(), self::$registries ) ) {
			self::$registries[ $asset->get_type() ][] = $asset;
		}
	}

	/**
	 * Add some link to plugin action links.
	 *
	 * @param string[] $actions     An array of plugin action links. By default, this can include 'activate', 'deactivate', and 'delete'.
	 * @param string   $plugin_file Path to the plugin file relative to the plugins' directory.
	 *
	 * @return array All action links for plugin.
	 */
	public function add_plugin_actions( array $actions, string $plugin_file ): array {
		if ( ! empty( self::$registries['plugin_action_links'] ) ) {
			/**
			 * Filters the allowed positions for the plugin action links.
			 *
			 * @param array $allowed_positions The allowed positions for the plugin action links.
			 *
			 * @return array
			 * @since 3.0.0
			 */
			$allowed_positions = \apply_filters( 'divi_squad_branding_plugin_actions_allowed_positions', array( 'before', 'after' ) );

			foreach ( self::$registries['plugin_action_links'] as $asset ) {
				if ( ! $asset instanceof BrandAsset\Asset ) {
					continue;
				}

				if ( $plugin_file !== $asset->get_plugin_base() ) {
					continue;
				}

				if ( ! $asset->is_allow_network() && is_network_admin() ) {
					continue;
				}

				if ( ! in_array( $asset->get_position(), $allowed_positions, true ) ) {
					continue;
				}

				if ( 'before' === $asset->get_position() ) {
					$actions = array_merge( $asset->get_action_links(), $actions );
				}

				if ( 'after' === $asset->get_position() ) {
					$actions = array_merge( $actions, $asset->get_action_links() );
				}
			}
		}

		return $actions;
	}

	/**
	 * Add some link to plugin row actions.
	 *
	 * @param string[] $actions     An array of plugin row actions. By default, this can include 'activate', 'deactivate', and 'delete'.
	 * @param string   $plugin_file Path to the plugin file relative to the plugins' directory.
	 *
	 * @return array All row actions for plugin.
	 */
	public function add_plugin_row_actions( array $actions, string $plugin_file ): array {
		if ( ! empty( self::$registries['plugin_row_actions'] ) ) {
			/**
			 * Filters the allowed positions for the plugin row actions.
			 *
			 * @param array $allowed_positions The allowed positions for the plugin row actions.
			 *
			 * @return array
			 * @since 3.0.0
			 */
			$allowed_positions = apply_filters( 'divi_squad_branding_plugin_row_actions_allowed_positions', array( 'before', 'after' ) );

			foreach ( self::$registries['plugin_row_actions'] as $asset ) {
				if ( ! $asset instanceof BrandAsset\Asset ) {
					continue;
				}

				if ( $plugin_file !== $asset->get_plugin_base() ) {
					continue;
				}

				if ( ! in_array( $asset->get_position(), $allowed_positions, true ) ) {
					continue;
				}

				if ( 'before' === $asset->get_position() ) {
					$actions = array_merge( $asset->get_row_actions(), $actions );
				}

				if ( 'after' === $asset->get_position() ) {
					$actions = array_merge( $actions, $asset->get_row_actions() );
				}
			}
		}

		return $actions;
	}

	/**
	 * Add some text to plugin footer text.
	 *
	 * @param string $text The text to be displayed in the footer.
	 *
	 * @return string The text to be displayed in the footer.
	 */
	public function add_plugin_footer_text( string $text ): string {
		if ( ! empty( self::$registries['admin_footer_text'] ) ) {

			$screen = get_current_screen();

			/**
			 * Filters the allowed positions for the plugin footer text.
			 *
			 * @param array $allowed_positions The allowed positions for the plugin footer text.
			 *
			 * @return array
			 * @since 3.0.0
			 */
			$allowed_positions = apply_filters( 'divi_squad_branding_admin_footer_text_allowed_positions', array( 'before', 'after', 'replace' ) );
			foreach ( self::$registries['admin_footer_text'] as $asset ) {
				if ( ! $asset instanceof BrandAsset\Asset ) {
					continue;
				}

				if ( ! Helper::is_squad_page( $screen->id ) ) {
					continue;
				}

				if ( ! in_array( $asset->get_position(), $allowed_positions, true ) ) {
					continue;
				}

				if ( 'before' === $asset->get_position() ) {
					$text = $asset->get_plugin_footer_text() . $text;
				}

				if ( 'after' === $asset->get_position() ) {
					$text = $text . $asset->get_plugin_footer_text();
				}

				if ( 'replace' === $asset->get_position() ) {
					$text = $asset->get_plugin_footer_text();
				}
			}
		}

		return $text;
	}

	/**
	 * Add some text to plugin update footer text.
	 *
	 * @param string $content The content that will be printed.
	 *
	 * @return string The content that will be printed.
	 */
	public function update_plugin_footer_text( string $content ): string {
		if ( ! empty( self::$registries['admin_footer_text'] ) ) {

			$screen = get_current_screen();

			/**
			 * Filters the allowed positions for the plugin update footer text.
			 *
			 * @param array $allowed_positions The allowed positions for the plugin update footer text.
			 *
			 * @return array
			 * @since 3.0.0
			 */
			$allowed_positions = apply_filters( 'divi_squad_branding_update_footer_text_allowed_positions', array( 'before', 'after', 'replace' ) );
			foreach ( self::$registries['admin_footer_text'] as $asset ) {
				if ( ! $asset instanceof BrandAsset\Asset ) {
					continue;
				}

				if ( ! Helper::is_squad_page( $screen->id ) ) {
					continue;
				}

				if ( ! in_array( $asset->get_position(), $allowed_positions, true ) ) {
					continue;
				}

				if ( 'before' === $asset->get_position() ) {
					$content = $asset->get_update_footer_text() . $content;
				}

				if ( 'after' === $asset->get_position() ) {
					$content = $content . $asset->get_update_footer_text();
				}

				if ( 'replace' === $asset->get_position() ) {
					$content = $asset->get_update_footer_text();
				}
			}
		}

		return $content;
	}
}
