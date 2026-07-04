<?php // phpcs:ignore WordPress.Files.FileName

namespace DiviSquad\Builder\Version4\Contracts;

/**
 * Module Interface
 *
 * @since 3.3.0
 */
interface Module_Interface {
	/**
	 * Initialize module
	 *
	 * @since 3.3.0
	 * @return void
	 */
	public function init();

	/**
	 * Get module fields
	 *
	 * @since 3.3.0
	 * @return array<string, mixed>
	 */
	public function get_fields();

	/**
	 * Get advanced fields configuration
	 *
	 * @since 3.3.0
	 * @return array<string, mixed>
	 */
	public function get_advanced_fields_config();

	/**
	 * Get custom CSS fields configuration
	 *
	 * @since 3.3.0
	 * @return array<string, mixed>
	 */
	public function get_custom_css_fields_config();

	/**
	 * Render the module
	 *
	 * Signature must stay compatible with ET_Builder_Element::render() so classes
	 * that both extend ET_Builder_Element and implement this interface do not trip
	 * PHP's incompatible-signature fatal under PHP 8+.
	 *
	 * @since 3.3.0
	 * @param array<string, mixed> $attrs       List of attributes.
	 * @param string               $content     Content being processed.
	 * @param string               $render_slug Slug of the module used for rendering.
	 *
	 * @return string
	 */
	public function render( $attrs, $content, $render_slug );
}
