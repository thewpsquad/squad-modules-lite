<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Abstract Module Class (Divi 5 / Block API)
 *
 * Base implementation for all Divi 5 block modules in the DiviSquad Builder.
 * Provides the shared dependency-tree boilerplate: every concrete module only
 * declares its built `module.json` metadata folder and a render callback.
 *
 * @since   3.4.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version5\Abstracts;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

// Bail when the Divi 5 framework is not present (e.g. running under Divi 4).
if ( ! interface_exists( 'ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface' ) ) {
	return;
}

use ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface;
use ET\Builder\Packages\ModuleLibrary\ModuleRegistration;

/**
 * Abstract Module class for Divi 5.
 *
 * Implements {@see DependencyInterface} so instances can be added to Divi 5's
 * `DependencyTree`. The tree calls {@see Module::load()}, which schedules
 * {@see Module::register_module()} on `init` to register the block with Divi's
 * module library using the module's `module.json` metadata.
 *
 * @since 3.4.0
 *
 * @see DependencyInterface
 */
abstract class Module implements DependencyInterface {

	/**
	 * Relative path (from the plugin root) to the built `module.json` metadata folder.
	 *
	 * The folder is produced by the Divi 5 (block) webpack build and lives under
	 * `build/divi-builder-5/modules-json/<module>/`. Concrete modules return the
	 * path with leading and trailing slashes, e.g. `/build/divi-builder-5/modules-json/google-map/`.
	 *
	 * @since 3.4.0
	 *
	 * @return string Relative metadata folder path.
	 */
	abstract protected static function get_metadata_folder_path(): string;

	/**
	 * Render the module's frontend HTML.
	 *
	 * Receives the resolved Divi 5 block attributes and renders the markup that is
	 * output on the frontend. Signature matches the callback Divi invokes via
	 * {@see ModuleRegistration::register_module()}.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $attrs    Block attributes.
	 * @param string               $content  Inner (child) block content.
	 * @param \WP_Block            $block    Parsed block instance.
	 * @param object               $elements Divi 5 ModuleElements helper for rendering/styling.
	 *
	 * @return string Rendered HTML.
	 */
	abstract public static function render_callback( array $attrs, string $content, \WP_Block $block, $elements ): string;

	/**
	 * Register the module with Divi 5's module library.
	 *
	 * Resolves the absolute metadata folder from the plugin root and passes the
	 * module's render callback to {@see ModuleRegistration::register_module()}.
	 *
	 * @since 3.4.0
	 *
	 * @return void
	 */
	public static function register_module(): void {
		$metadata_folder = divi_squad()->get_path( static::get_metadata_folder_path() );

		ModuleRegistration::register_module(
			$metadata_folder,
			array(
				'render_callback' => array( static::class, 'render_callback' ),
			)
		);
	}

	/**
	 * Load the module.
	 *
	 * Called by Divi 5's `DependencyTree` while it loads its dependencies
	 * (`Modules::initialize()` → `DependencyTree::load_dependencies()`). Divi's own
	 * core modules (e.g. `BlurbModule::load()`) register synchronously here, so we
	 * do the same: the dependency tree is loaded at/after `init` has already fired,
	 * which means deferring registration to the `init` action would miss it entirely
	 * on the frontend (the block would then render nothing). Registering immediately
	 * mirrors Divi core and ensures the render callback is available on both the
	 * Visual Builder and the frontend.
	 *
	 * @since 3.4.0
	 *
	 * @return void
	 */
	public function load(): void {
		static::register_module();
	}

	/**
	 * Resolve a URL from a Divi 5 `divi/upload` (or image) field value.
	 *
	 * Divi 5 stores upload/image field values as an object — e.g.
	 * `array( 'src' => '…', 'id' => '…', 'alt' => '…', 'width' => '…', 'height' => '…' )` —
	 * not as a plain URL string. Render callbacks that treat the value as a string
	 * therefore emit "Array" (and a PHP notice). This helper accepts either shape and
	 * always returns the URL string: the `src` key when given the object, the value
	 * itself when given a string (e.g. a remote URL text field), or an empty string.
	 *
	 * @since 3.4.0
	 *
	 * @param mixed $value Upload field value (object array or string).
	 *
	 * @return string Resolved URL, or empty string when none.
	 */
	protected static function resolve_upload_url( $value ): string {
		if ( is_array( $value ) ) {
			return (string) ( $value['src'] ?? $value['url'] ?? '' );
		}

		return (string) $value;
	}
}
