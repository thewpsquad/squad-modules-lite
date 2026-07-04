<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Branding Manager
 *
 * This class handles the registration and management of all WordPress branding elements
 * for the Divi Squad plugin. It provides a comprehensive system for customizing the
 * plugin's appearance and integrating with WordPress admin interfaces.
 *
 * @since   3.3.3
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Core\Admin;

use DiviSquad\Core\Admin\Branding\Brand_Asset_Base;
use DiviSquad\Core\Admin\Branding\Brand_Asset_Interface;
use DiviSquad\Core\Admin\Branding\Plugin_Action_Links;
use DiviSquad\Core\Admin\Branding\Plugin_Admin_Footer_Text;
use DiviSquad\Core\Admin\Branding\Plugin_Row_Meta;
use Throwable;
use function add_action;
use function add_filter;
use function apply_filters;
use function do_action;
use function is_network_admin;

/**
 * Branding Manager class.
 *
 * @since   3.3.3
 * @package DiviSquad
 */
class Branding {

	/**
	 * Registered branding assets.
	 *
	 * @var array<string, array<Brand_Asset_Interface>>
	 */
	protected array $assets = array(
		'plugin_action_links' => array(),
		'plugin_row_actions'  => array(),
		'admin_footer_text'   => array(),
		'custom_css'          => array(),
		'menu_icon'           => array(),
	);

	/**
	 * Initialize the Branding Manager.
	 *
	 * This method sets up all necessary hooks for the branding system to function.
	 *
	 * @since 3.3.3
	 *
	 * @return void
	 */
	public function init(): void {
		try {
			add_action( 'wp_loaded', array( $this, 'setup_branding_assets' ) );

			// Register actions to apply branding.
			add_filter( 'plugin_action_links', array( $this, 'add_plugin_action_links' ), 10, 2 );
			add_filter( 'network_admin_plugin_action_links', array( $this, 'add_plugin_action_links' ), 10, 2 );
			add_filter( 'plugin_row_meta', array( $this, 'add_plugin_row_meta' ), 10, 2 );
			add_action( 'admin_head', array( $this, 'output_custom_css' ) );

			add_filter( 'admin_footer_text', array( $this, 'filter_admin_footer_text' ), PHP_INT_MAX );
			add_filter( 'update_footer', array( $this, 'filter_update_footer_text' ), PHP_INT_MAX );

			/**
			 * Action fired after branding manager is fully initialized.
			 *
			 * Use this hook to perform actions after the branding system is fully set up.
			 *
			 * @since 3.3.3
			 *
			 * @param Branding $manager The Branding Manager instance.
			 */
			do_action( 'divi_squad_after_branding_init', $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Branding Manager initialization' );
		}
	}

	/**
	 * Setup branding assets.
	 *
	 * This method initializes all registered branding asset classes and validates
	 * them before adding them to the assets collection.
	 *
	 * @since 3.3.3
	 *
	 * @return void
	 */
	public function setup_branding_assets(): void {
		try {
			// Register default branding assets.
			$this->register_default_assets();

			/**
			 * Action to register additional branding assets.
			 *
			 * This hook allows plugins to register their own branding assets.
			 *
			 * @since 3.3.3
			 *
			 * @param Branding $manager The Branding Manager instance.
			 */
			do_action( 'divi_squad_register_branding_assets', $this );

			/**
			 * Action after all branding assets are registered.
			 *
			 * This hook fires after all branding assets have been registered and processed.
			 *
			 * @since 3.3.3
			 *
			 * @param array<string, array<Brand_Asset_Interface>> $assets  The registered branding assets.
			 * @param Branding                                    $manager The Branding Manager instance.
			 */
			do_action( 'divi_squad_after_register_branding_assets', $this->assets, $this );

			/**
			 * Filter to modify the final collection of branding assets.
			 *
			 * Allows developers to add, remove, or modify the final collection
			 * of branding assets that will be applied.
			 *
			 * @since 3.3.3
			 *
			 * @param array<string, array<Brand_Asset_Interface>> $assets  The registered branding assets.
			 * @param Branding                                    $manager The Branding Manager instance.
			 */
			$this->assets = apply_filters( 'divi_squad_branding_assets', $this->assets, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to setup branding assets' );
		}
	}

	/**
	 * Register default branding assets.
	 *
	 * This method registers the core branding asset classes provided by the plugin.
	 *
	 * @since 3.3.3
	 *
	 * @return void
	 */
	protected function register_default_assets(): void {
		try {
			// Define default asset classes.
			$default_asset_classes = array(
				Plugin_Action_Links::class,
				Plugin_Row_Meta::class,
				Plugin_Admin_Footer_Text::class,
			);

			/**
			 * Filter to modify the default branding asset classes.
			 *
			 * This filter allows developers to add, remove, or modify the classes
			 * that will be instantiated and registered as default branding assets.
			 *
			 * @since 3.3.3
			 *
			 * @param array<string> $asset_classes Array of branding asset class names.
			 * @param Branding      $manager       The Branding Manager instance.
			 */
			$asset_classes = apply_filters( 'divi_squad_default_branding_asset_classes', $default_asset_classes, $this );

			/**
			 * Action before processing default branding asset classes.
			 *
			 * Fires before individual asset classes are processed and validated.
			 *
			 * @since 3.3.3
			 *
			 * @param array<string> $asset_classes Array of asset class names to be processed.
			 * @param Branding      $manager       The Branding Manager instance.
			 */
			do_action( 'divi_squad_before_process_default_branding_assets', $asset_classes, $this );

			foreach ( $asset_classes as $class_name ) {
				$this->register_asset_class( $class_name );
			}

			/**
			 * Action after registering default branding assets.
			 *
			 * @since 3.3.3
			 *
			 * @param Branding $branding_manager The Branding Manager instance.
			 */
			do_action( 'divi_squad_after_register_default_branding_assets', $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to register default branding assets' );
		}
	}

	/**
	 * Register a branding asset class.
	 *
	 * This method instantiates and registers a branding asset class if it implements
	 * the required interface and passes validation.
	 *
	 * @since 3.3.3
	 *
	 * @param string $class_name The fully qualified class name of the branding asset.
	 *
	 * @return bool Whether the asset was registered successfully.
	 */
	public function register_asset_class( string $class_name ): bool {
		try {
			// Verify that the class exists.
			if ( ! class_exists( $class_name ) ) {
				divi_squad()->log_debug( sprintf( 'Branding asset class %s does not exist.', $class_name ) );

				return false;
			}

			/**
			 * Filter to conditionally skip asset class instantiation.
			 *
			 * Allows developers to conditionally skip instantiating an asset class
			 * based on custom logic before the class is even instantiated.
			 *
			 * @since 3.3.3
			 *
			 * @param bool     $skip_asset Whether to skip the asset class. Default false.
			 * @param string   $class_name The asset class name.
			 * @param Branding $manager    The Branding Manager instance.
			 */
			$skip_asset = apply_filters( 'divi_squad_skip_branding_asset_class', false, $class_name, $this );
			if ( $skip_asset ) {
				return false;
			}

			// Instantiate the class.
			$asset = new $class_name();

			// Verify that the class implements Brand_Asset_Interface.
			if ( ! $asset instanceof Brand_Asset_Interface ) {
				divi_squad()->log_debug( sprintf( 'Branding asset class %s must implement Brand_Asset_Interface.', $class_name ) );

				return false;
			}

			/**
			 * Action when a branding asset is successfully instantiated.
			 *
			 * @since 3.3.3
			 *
			 * @param Brand_Asset_Interface $asset      The asset instance.
			 * @param string                $class_name The asset class name.
			 * @param Branding              $manager    The Branding Manager instance.
			 */
			do_action( 'divi_squad_branding_asset_instantiated', $asset, $class_name, $this );

			// Register the asset instance.
			return $this->register_asset_instance( $asset );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, sprintf( 'Failed to register branding asset class %s', $class_name ) );

			return false;
		}
	}

	/**
	 * Register a branding asset instance.
	 *
	 * This method adds a pre-instantiated branding asset to the assets collection
	 * after validating its type.
	 *
	 * @since 3.3.3
	 *
	 * @param Brand_Asset_Interface $asset The branding asset instance.
	 *
	 * @return bool Whether the asset was registered successfully.
	 */
	public function register_asset_instance( Brand_Asset_Interface $asset ): bool {
		try {
			$type = $asset->get_type();

			// Check if this type is supported.
			if ( ! isset( $this->assets[ $type ] ) ) {
				/**
				 * Filter to allow new asset types.
				 *
				 * @since 3.3.3
				 *
				 * @param bool     $allow_new_type Whether to allow new asset types. Default false.
				 * @param string   $type           The new asset type.
				 * @param Branding $manager        The Branding Manager instance.
				 */
				$allow_new_type = apply_filters( 'divi_squad_allow_new_branding_asset_type', false, $type, $this );

				if ( $allow_new_type ) {
					$this->assets[ $type ] = array();
				} else {
					divi_squad()->log_debug( sprintf( 'Branding asset type %s is not supported.', $type ) );

					return false;
				}
			}

			// Add the asset to the registered assets.
			$this->assets[ $type ][] = $asset;

			/**
			 * Action fired after registering a branding asset instance.
			 *
			 * @since 3.3.3
			 *
			 * @param Brand_Asset_Interface $asset   The branding asset instance.
			 * @param string                $type    The asset type.
			 * @param Branding              $manager The Branding Manager instance.
			 */
			do_action( 'divi_squad_branding_asset_registered', $asset, $type, $this );

			return true;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, sprintf( 'Failed to register branding asset instance of class %s', get_class( $asset ) ) );

			return false;
		}
	}

	/**
	 * Add plugin action links.
	 *
	 * This method filters the action links displayed for plugins in the WordPress
	 * plugins list, adding custom links defined by registered branding assets.
	 *
	 * @since 3.3.3
	 *
	 * @param array<string> $actions     An array of plugin action links.
	 * @param string        $plugin_file Path to the plugin file relative to the plugins directory.
	 *
	 * @return array<string> Modified plugin action links.
	 */
	public function add_plugin_action_links( array $actions, string $plugin_file ): array {
		try {
			if ( count( $this->assets['plugin_action_links'] ) === 0 ) {
				return $actions;
			}

			/**
			 * Filter the allowed positions for plugin action links.
			 *
			 * @since 3.3.3
			 *
			 * @param array $positions The allowed positions.
			 */
			$allowed_positions = apply_filters( 'divi_squad_branding_plugin_action_links_positions', array( 'before', 'after' ) );

			/**
			 * Action before plugin action links are modified.
			 *
			 * @since 3.3.3
			 *
			 * @param array    $actions     The current action links.
			 * @param string   $plugin_file The plugin file path.
			 * @param array    $assets      The registered plugin action link assets.
			 * @param Branding $manager     The Branding Manager instance.
			 */
			do_action( 'divi_squad_before_plugin_action_links', $actions, $plugin_file, $this->assets['plugin_action_links'], $this );

			foreach ( $this->assets['plugin_action_links'] as $asset ) {
				if ( ! $asset instanceof Brand_Asset_Base ) {
					continue;
				}

				if ( $plugin_file !== $asset->get_plugin_base() ) {
					continue;
				}

				if ( ! $asset->is_allowed_in_network() && is_network_admin() ) {
					continue;
				}

				if ( ! in_array( $asset->get_position(), $allowed_positions, true ) ) {
					continue;
				}

				if ( ! $asset->can_apply() ) {
					continue;
				}

				$asset_links = $asset->get_action_links();

				/**
				 * Filter the action links for a specific asset.
				 *
				 * @since 3.3.3
				 *
				 * @param array                 $asset_links Action links from the asset.
				 * @param Brand_Asset_Interface $asset       The asset instance.
				 * @param string                $plugin_file The plugin file path.
				 * @param Branding              $manager     The Branding Manager instance.
				 */
				$asset_links = apply_filters( 'divi_squad_branding_asset_action_links', $asset_links, $asset, $plugin_file, $this );

				if ( 'before' === $asset->get_position() ) {
					$actions = array_merge( $asset_links, $actions );
				}

				if ( 'after' === $asset->get_position() ) {
					$actions = array_merge( $actions, $asset_links );
				}
			}

			/**
			 * Filter the final plugin action links.
			 *
			 * @since 3.3.3
			 *
			 * @param array    $actions     The modified action links.
			 * @param string   $plugin_file The plugin file path.
			 * @param Branding $manager     The Branding Manager instance.
			 */
			return apply_filters( 'divi_squad_plugin_action_links_result', $actions, $plugin_file, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to add plugin action links' );

			return $actions;
		}
	}

	/**
	 * Add plugin row meta links.
	 *
	 * This method filters the row meta links displayed for plugins in the WordPress
	 * plugins list, adding custom meta defined by registered branding assets.
	 *
	 * @since 3.3.3
	 *
	 * @param array<string> $meta        An array of the plugin's metadata.
	 * @param string        $plugin_file Path to the plugin file relative to the plugins directory.
	 *
	 * @return array<string> Modified plugin row meta.
	 */
	public function add_plugin_row_meta( array $meta, string $plugin_file ): array {
		try {
			if ( count( $this->assets['plugin_row_actions'] ) === 0 ) {
				return $meta;
			}

			/**
			 * Filter the allowed positions for plugin row meta.
			 *
			 * @since 3.3.3
			 *
			 * @param array $positions The allowed positions.
			 */
			$allowed_positions = apply_filters( 'divi_squad_branding_plugin_row_meta_positions', array( 'before', 'after' ) );

			/**
			 * Action before plugin row meta are modified.
			 *
			 * @since 3.3.3
			 *
			 * @param array    $meta        The current meta.
			 * @param string   $plugin_file The plugin file path.
			 * @param array    $assets      The registered plugin row action assets.
			 * @param Branding $manager     The Branding Manager instance.
			 */
			do_action( 'divi_squad_before_plugin_row_meta', $meta, $plugin_file, $this->assets['plugin_row_actions'], $this );

			foreach ( $this->assets['plugin_row_actions'] as $asset ) {
				if ( ! $asset instanceof Brand_Asset_Base ) {
					continue;
				}

				if ( $plugin_file !== $asset->get_plugin_base() ) {
					continue;
				}

				if ( ! in_array( $asset->get_position(), $allowed_positions, true ) ) {
					continue;
				}

				if ( ! $asset->can_apply() ) {
					continue;
				}

				$asset_meta = $asset->get_row_meta();

				/**
				 * Filter the row meta for a specific asset.
				 *
				 * @since 3.3.3
				 *
				 * @param array                 $asset_meta  Row meta from the asset.
				 * @param Brand_Asset_Interface $asset       The asset instance.
				 * @param string                $plugin_file The plugin file path.
				 * @param Branding              $manager     The Branding Manager instance.
				 */
				$asset_meta = apply_filters( 'divi_squad_branding_asset_row_meta', $asset_meta, $asset, $plugin_file, $this );

				if ( 'before' === $asset->get_position() ) {
					$meta = array_merge( $asset_meta, $meta );
				}

				if ( 'after' === $asset->get_position() ) {
					$meta = array_merge( $meta, $asset_meta );
				}
			}

			/**
			 * Filter the final plugin row meta.
			 *
			 * @since 3.3.3
			 *
			 * @param array    $meta        The modified meta.
			 * @param string   $plugin_file The plugin file path.
			 * @param Branding $manager     The Branding Manager instance.
			 */
			return apply_filters( 'divi_squad_plugin_row_meta_result', $meta, $plugin_file, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to add plugin row meta' );

			return $meta;
		}
	}

	/**
	 * Filter admin footer text.
	 *
	 * This method filters the admin footer text in the WordPress admin, replacing
	 * or modifying it based on registered branding assets.
	 *
	 * @since 3.3.3
	 *
	 * @param string $text The admin footer text.
	 *
	 * @return string Modified admin footer text.
	 */
	public function filter_admin_footer_text( $text ): string {
		try {
			if ( count( $this->assets['admin_footer_text'] ) === 0 ) {
				return $text;
			}

			/**
			 * Filter the allowed positions for admin footer text.
			 *
			 * @since 3.3.3
			 *
			 * @param array $positions The allowed positions.
			 */
			$allowed_positions = apply_filters( 'divi_squad_branding_admin_footer_text_positions', array( 'before', 'after', 'replace' ) );

			/**
			 * Action before admin footer text is modified.
			 *
			 * @since 3.3.3
			 *
			 * @param string   $text    The current footer text.
			 * @param array    $assets  The registered admin footer text assets.
			 * @param Branding $manager The Branding Manager instance.
			 */
			do_action( 'divi_squad_before_admin_footer_text', $text, $this->assets['admin_footer_text'], $this );

			$original_text = $text;

			foreach ( $this->assets['admin_footer_text'] as $asset ) {
				if ( ! $asset instanceof Brand_Asset_Base ) {
					continue;
				}

				if ( ! in_array( $asset->get_position(), $allowed_positions, true ) ) {
					continue;
				}

				if ( ! $asset->can_apply() ) {
					continue;
				}

				$footer_text = $asset->get_admin_footer_text();

				/**
				 * Filter the admin footer text for a specific asset.
				 *
				 * @since 3.3.3
				 *
				 * @param string                $footer_text Footer text from the asset.
				 * @param Brand_Asset_Interface $asset       The asset instance.
				 * @param string                $text        The current footer text.
				 * @param Branding              $manager     The Branding Manager instance.
				 */
				$footer_text = (string) apply_filters( 'divi_squad_branding_asset_footer_text', $footer_text, $asset, $text, $this );

				if ( 'before' === $asset->get_position() ) {
					$text = $footer_text . $text;
				}

				if ( 'after' === $asset->get_position() ) {
					$text .= $footer_text;
				}

				if ( 'replace' === $asset->get_position() ) {
					$text = $footer_text;
				}
			}

			/**
			 * Filter the final admin footer text.
			 *
			 * @since 3.3.3
			 *
			 * @param string   $text          The modified footer text.
			 * @param string   $original_text The original footer text.
			 * @param Branding $manager       The Branding Manager instance.
			 */
			return apply_filters( 'divi_squad_admin_footer_text_result', $text, $original_text, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to filter admin footer text' );

			return $text;
		}
	}

	/**
	 * Filter update footer text.
	 *
	 * This method filters the update footer text in the WordPress admin, replacing
	 * or modifying it based on registered branding assets.
	 *
	 * @since 3.3.3
	 *
	 * @param string $text The update footer text.
	 *
	 * @return string Modified update footer text.
	 */
	public function filter_update_footer_text( $text ): string {
		try {
			if ( count( $this->assets['admin_footer_text'] ) === 0 ) {
				return $text;
			}

			/**
			 * Filter the allowed positions for update footer text.
			 *
			 * @since 3.3.3
			 *
			 * @param array $positions The allowed positions.
			 */
			$allowed_positions = apply_filters( 'divi_squad_branding_update_footer_text_positions', array( 'before', 'after', 'replace' ) );

			/**
			 * Action before update footer text is modified.
			 *
			 * @since 3.3.3
			 *
			 * @param string   $text    The current update footer text.
			 * @param array    $assets  The registered admin footer text assets.
			 * @param Branding $manager The Branding Manager instance.
			 */
			do_action( 'divi_squad_before_update_footer_text', $text, $this->assets['admin_footer_text'], $this );

			$original_text = $text;

			foreach ( $this->assets['admin_footer_text'] as $asset ) {
				if ( ! $asset instanceof Brand_Asset_Base ) {
					continue;
				}

				if ( ! in_array( $asset->get_position(), $allowed_positions, true ) ) {
					continue;
				}

				if ( ! $asset->can_apply() ) {
					continue;
				}

				$update_text = $asset->get_update_footer_text();

				/**
				 * Filter the update footer text for a specific asset.
				 *
				 * @since 3.3.3
				 *
				 * @param string                $update_text Update text from the asset.
				 * @param Brand_Asset_Interface $asset       The asset instance.
				 * @param string                $text        The current update text.
				 * @param Branding              $manager     The Branding Manager instance.
				 */
				$update_text = (string) apply_filters( 'divi_squad_branding_asset_update_text', $update_text, $asset, $text, $this );

				if ( 'before' === $asset->get_position() ) {
					$text = $update_text . $text;
				}

				if ( 'after' === $asset->get_position() ) {
					$text .= $update_text;
				}

				if ( 'replace' === $asset->get_position() ) {
					$text = $update_text;
				}
			}

			/**
			 * Filter the final update footer text.
			 *
			 * @since 3.3.3
			 *
			 * @param string   $text          The modified update text.
			 * @param string   $original_text The original update text.
			 * @param Branding $manager       The Branding Manager instance.
			 */
			return apply_filters( 'divi_squad_update_footer_text_result', $text, $original_text, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to filter update footer text' );

			return $text;
		}
	}

	/**
	 * Output custom CSS for branding.
	 *
	 * This method outputs custom CSS in the WordPress admin head based on
	 * registered branding assets.
	 *
	 * @since 3.3.3
	 *
	 * @return void
	 */
	public function output_custom_css(): void {
		try {
			if ( count( $this->assets['custom_css'] ) === 0 ) {
				return;
			}

			$css = '';

			/**
			 * Action before custom CSS is output.
			 *
			 * @since 3.3.3
			 *
			 * @param array    $assets  The registered custom CSS assets.
			 * @param Branding $manager The Branding Manager instance.
			 */
			do_action( 'divi_squad_before_output_custom_css', $this->assets['custom_css'], $this );

			foreach ( $this->assets['custom_css'] as $asset ) {
				if ( ! $asset instanceof Brand_Asset_Base ) {
					continue;
				}

				if ( ! $asset->can_apply() ) {
					continue;
				}

				$asset_css = $asset->get_custom_css();

				/**
				 * Filter the custom CSS for a specific asset.
				 *
				 * @since 3.3.3
				 *
				 * @param string                $asset_css Custom CSS from the asset.
				 * @param Brand_Asset_Interface $asset     The asset instance.
				 * @param Branding              $manager   The Branding Manager instance.
				 */
				$asset_css = apply_filters( 'divi_squad_branding_asset_custom_css', $asset_css, $asset, $this );

				$css .= $asset_css;
			}

			/**
			 * Filter the final custom CSS.
			 *
			 * @since 3.3.3
			 *
			 * @param string   $css     The combined custom CSS.
			 * @param Branding $manager The Branding Manager instance.
			 */
			$css = apply_filters( 'divi_squad_custom_css_result', $css, $this );

			if ( '' !== $css ) {
				echo '<style type="text/css">' . $css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			/**
			 * Action after custom CSS is output.
			 *
			 * @since 3.3.3
			 *
			 * @param string   $css     The CSS that was output.
			 * @param Branding $manager The Branding Manager instance.
			 */
			do_action( 'divi_squad_after_output_custom_css', $css, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to output custom CSS' );
		}
	}

	/**
	 * Get all registered branding assets.
	 *
	 * @since 3.3.3
	 *
	 * @return array<string, array<Brand_Asset_Interface>> The registered branding assets.
	 */
	public function get_assets(): array {
		return $this->assets;
	}

	/**
	 * Get registered branding assets by type.
	 *
	 * @since 3.3.3
	 *
	 * @param string $type The asset type.
	 *
	 * @return array<Brand_Asset_Interface> The registered branding assets for the specified type.
	 */
	public function get_assets_by_type( string $type ): array {
		return $this->assets[ $type ] ?? array();
	}
}
