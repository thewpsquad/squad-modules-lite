<?php

namespace DiviSquad\Builder\Version4\Contracts;

/**
 * Module Interface
 *
 * Defines the contract for Divi Builder modules.
 *
 * @since 3.3.3
 */
interface Module_Interface {

	/**
	 * Initialize module
	 *
	 * @return void
	 */
	public function init();

	/**
	 * Declare general fields for the module
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array<string, array<int|string, string>|bool|string>>
	 */
	public function get_fields();

	/**
	 * Generates the module's HTML output based on {@see self::$props}. This method should be
	 * overridden in module classes.
	 *
	 * @since 3.1 Renamed from `shortcode_callback()` to `render()`.
	 * @since 1.0
	 *
	 * @param array<string, string> $attrs       List of unprocessed attributes.
	 * @param string                $content     Content being processed.
	 * @param string                $render_slug Slug of module that is used for rendering output.
	 *
	 * @return string The module's HTML output.
	 */
	public function render( array $attrs, string $content, string $render_slug );
}
