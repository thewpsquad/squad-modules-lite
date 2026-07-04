<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Abstract Form Styler Module (Divi 5 / Block API).
 *
 * Shared base for the native Divi 5 form-styler modules (Contact Form 7, WP Forms,
 * Gravity Forms, Ninja Forms, Fluent Forms, Forminator). Concrete modules only declare
 * their form type, plugin-active check, the embedded-form HTML, and their per-plugin CSS
 * selector map; this base provides the common render flow, form-id resolution, and the
 * style generation that drives full Divi 4 styling parity through Divi 5's style engine.
 *
 * Styling is declarative: each module's `module.json` defines a style group per target
 * (form wrapper, fields, labels, placeholder, checkbox/radio, submit button, error and
 * success messages, plus optional form title/description) whose `selector` points at the
 * embedded form's markup and whose decoration settings (background, border, font, spacing,
 * box-shadow) Divi renders automatically. {@see self::module_styles()} simply emits each
 * present group through `$elements->style()`.
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
if ( ! class_exists( 'ET\Builder\Packages\Module\Module' ) ) {
	return;
}

use ET\Builder\FrontEnd\Module\Style;
use ET\Builder\Packages\Module\Layout\Components\ModuleElements\ModuleElements;
use ET\Builder\Packages\Module\Module as DiviModule;
use ET\Builder\Packages\Module\Options\Css\CssStyle;
use ET\Builder\Packages\Module\Options\Element\ElementClassnames;
use Throwable;
use WP_Block;
use function esc_html__;
use function et_core_is_fb_enabled;

/**
 * Abstract Form Styler module class for Divi 5.
 *
 * @since 3.4.0
 */
abstract class Form_Styler extends Module {

	/**
	 * The Squad forms-element type key for the concrete module (e.g. `cf7`).
	 *
	 * Used to resolve the selected form id and to look the form up via
	 * {@see \DiviSquad\Builder\Utils\Elements\Forms::get_forms_by()}.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	abstract protected static function get_form_type(): string;

	/**
	 * Whether the third-party form plugin this module styles is active.
	 *
	 * @since 3.4.0
	 *
	 * @return bool
	 */
	abstract protected static function is_form_plugin_active(): bool;

	/**
	 * Render the embedded third-party form's HTML for the given (raw) form id.
	 *
	 * @since 3.4.0
	 *
	 * @param string               $form_id Raw form id resolved from the picker value.
	 * @param array<string, mixed> $inner   The module's form-group inner-content values.
	 *
	 * @return string
	 */
	abstract protected static function get_form_html( string $form_id, array $inner ): string;

	/**
	 * The style group attribute names (beyond `module`) this module emits.
	 *
	 * Each name corresponds to a `module.json` attribute whose `selector` targets the
	 * embedded form's markup. Concrete modules may override to add/remove groups (e.g.
	 * Gravity Forms adds `formTitle`/`formDescription`).
	 *
	 * @since 3.4.0
	 *
	 * @return array<int, string>
	 */
	protected static function get_style_group_attr_names(): array {
		return array(
			'formWrapper',
			'field',
			'fieldLabel',
			'fieldPlaceholder',
			'checkboxRadio',
			'submitButton',
			'messageError',
			'messageSuccess',
		);
	}

	/**
	 * Resolve the raw form id from the picker value.
	 *
	 * The picker stores the forms-element key (which may be a hashed key, matching the
	 * Divi 4 form styler); this maps it back to the raw id the plugin's embed expects.
	 *
	 * @since 3.4.0
	 *
	 * @param string $picker_value The stored form picker value.
	 *
	 * @return string Raw form id, or empty string when unresolved.
	 */
	protected static function resolve_form_id( string $picker_value ): string {
		if ( '' === $picker_value ) {
			return '';
		}

		try {
			$map = (array) divi_squad()->forms_element->get_forms_by( static::get_form_type(), 'id' );

			if ( isset( $map[ $picker_value ] ) ) {
				return (string) $map[ $picker_value ];
			}
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, sprintf( 'Failed to resolve %s form id', static::get_form_type() ) );
		}

		// The picker value may already be the raw id.
		return $picker_value;
	}

	/**
	 * Return the root CSS classname for this form styler module.
	 *
	 * Concrete modules override this to return their specific `disq_form_styler_*` slug
	 * so that SCSS rules scoped under that class are applied to the module wrapper.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	protected static function get_root_classname(): string {
		return '';
	}

	/**
	 * Add the module classnames.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $args Classnames arguments.
	 *
	 * @return void
	 */
	public static function module_classnames( array $args ): void {
		$root = static::get_root_classname();
		if ( '' !== $root ) {
			$args['classnamesInstance']->add( $root );
		}
		$args['classnamesInstance']->add(
			ElementClassnames::classnames(
				array(
					'attrs' => $args['attrs']['module']['decoration'] ?? array(),
				)
			)
		);
	}

	/**
	 * Assign the module's frontend script data.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $args Script data arguments.
	 *
	 * @return void
	 */
	public static function module_script_data( array $args ): void {
		$args['elements']->script_data(
			array(
				'attrName' => 'module',
			)
		);
	}

	/**
	 * Register the module style declarations.
	 *
	 * Emits the standard module styles plus one style block per form style group declared
	 * by {@see self::get_style_group_attr_names()}. Each group's CSS (background, border,
	 * font, spacing, box-shadow) is generated by Divi from the group's `module.json`
	 * decoration settings and `selector`.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $args Style arguments provided by Divi.
	 *
	 * @return void
	 */
	public static function module_styles( array $args ): void {
		$attrs    = $args['attrs'] ?? array();
		$elements = $args['elements'];
		$settings = $args['settings'] ?? array();

		$styles = array(
			// Module.
			$elements->style(
				array(
					'attrName'   => 'module',
					'styleProps' => array(
						'disabledOn' => array(
							'disabledModuleVisibility' => $settings['disabledModuleVisibility'] ?? null,
						),
					),
				)
			),
		);

		// Each form style group (only those present in attrs render any CSS).
		foreach ( static::get_style_group_attr_names() as $attr_name ) {
			$styles[] = $elements->style( array( 'attrName' => $attr_name ) );
		}

		// Module - Custom CSS.
		$styles[] = CssStyle::style(
			array(
				'selector' => $args['orderClass'],
				'attr'     => $attrs['css'] ?? array(),
			)
		);

		Style::add(
			array(
				'id'            => $args['id'],
				'name'          => $args['name'],
				'orderIndex'    => $args['orderIndex'],
				'storeInstance' => $args['storeInstance'],
				'styles'        => $styles,
			)
		);
	}

	/**
	 * Render callback for a form-styler module.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $attrs    Block attributes.
	 * @param string               $content  Inner content.
	 * @param WP_Block             $block    Parsed block instance.
	 * @param ModuleElements       $elements ModuleElements instance.
	 *
	 * @return string Rendered HTML.
	 */
	public static function render_callback( array $attrs, string $content, WP_Block $block, $elements ): string {
		try {
			$is_vb = function_exists( 'et_core_is_fb_enabled' ) && et_core_is_fb_enabled();

			// Bail with a builder notice when the form plugin is not active.
			if ( ! static::is_form_plugin_active() ) {
				return $is_vb
					? static::render_notice( esc_html__( 'The required form plugin is not active.', 'squad-modules-for-divi' ) )
					: '';
			}

			$inner        = $attrs['forms']['innerContent']['desktop']['value'] ?? array();
			$picker_value = (string) ( $inner['formId'] ?? '' );
			$form_id      = static::resolve_form_id( $picker_value );

			if ( '' === $form_id ) {
				return $is_vb
					? static::render_notice( esc_html__( 'Please select a form to display.', 'squad-modules-for-divi' ) )
					: '';
			}

			$form_html = static::get_form_html( $form_id, $inner );

			if ( '' === $form_html ) {
				return $is_vb
					? static::render_notice( esc_html__( 'The selected form could not be rendered.', 'squad-modules-for-divi' ) )
					: '';
			}

			// Optional dummy success/error messages so those groups can be styled in the VB.
			if ( $is_vb && 'on' === ( $inner['formMessages'] ?? 'off' ) ) {
				$form_html .= static::render_dummy_messages();
			}

			$style_components = $elements instanceof ModuleElements
				? (string) $elements->style_components( array( 'attrName' => 'module' ) )
				: '';

			return DiviModule::render(
				array(
					// FE only.
					'orderIndex'          => $block->parsed_block['orderIndex'],
					'storeInstance'       => $block->parsed_block['storeInstance'],

					// VB equivalent.
					'attrs'               => $attrs,
					'elements'            => $elements,
					'id'                  => $block->parsed_block['id'],
					'name'                => $block->block_type->name,
					'moduleCategory'      => $block->block_type->category,
					'classnamesFunction'  => array( static::class, 'module_classnames' ),
					'stylesComponent'     => array( static::class, 'module_styles' ),
					'scriptDataComponent' => array( static::class, 'module_script_data' ),
					'children'            => $style_components . $form_html,
				)
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, sprintf( 'Failed to render Divi 5 %s form styler module', static::get_form_type() ) );

			return '';
		}
	}

	/**
	 * Render a builder notice block.
	 *
	 * @since 3.4.0
	 *
	 * @param string $message The message to show.
	 *
	 * @return string
	 */
	protected static function render_notice( string $message ): string {
		return sprintf(
			'<div class="squad-notice squad-form-styler-notice">%s</div>',
			esc_html( $message )
		);
	}

	/**
	 * Render dummy error + success message blocks for the VB preview.
	 *
	 * Concrete modules can override to emit their plugin's exact message markup so the
	 * Error/Success style groups apply. The default emits generic blocks.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	protected static function render_dummy_messages(): string {
		return sprintf(
			'<div class="squad-form-dummy-message squad-form-error">%1$s</div><div class="squad-form-dummy-message squad-form-success">%2$s</div>',
			esc_html__( 'Sample error message.', 'squad-modules-for-divi' ),
			esc_html__( 'Sample success message.', 'squad-modules-for-divi' )
		);
	}
}
