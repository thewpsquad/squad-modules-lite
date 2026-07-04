<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Text Effects Module Class which extend the Divi Builder Module Class.
 *
 * This class provides text-effect (image mask, stroke, animated gradient)
 * adding functionalities in the visual builder.
 *
 * @since   4.4.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Creative;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use DiviSquad\Builder\Version4\Abstracts\Module;
use function esc_attr;
use function esc_html;
use function esc_html__;
use function esc_url;
use function et_pb_background_options;
use function in_array;
use function preg_replace;
use function sprintf;

/**
 * Text Effects Module Class.
 *
 * @since   4.4.0
 * @package DiviSquad
 */
class Text_Effects extends Module {

	/**
	 * The allowed HTML tags for the text element.
	 *
	 * @var string[]
	 */
	private const ALLOWED_TAGS = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span', 'div' );

	/**
	 * The allowed style types.
	 *
	 * @var string[]
	 */
	private const ALLOWED_STYLE_TYPES = array( 'image_mask', 'stroke', 'gradient_animated' );

	/**
	 * Initiate Module.
	 * Set the module name on init.
	 *
	 * @since 4.4.0
	 * @return void
	 */
	public function init(): void {
		$this->name      = esc_html__( 'Text Effects', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Text Effects', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'text-effects.svg' );

		$this->slug             = 'disq_text_effects';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.$this->slug";

		$this->child_title_var          = 'text_content';
		$this->child_title_fallback_var = 'admin_label';

		// Connect with utils.
		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		// Declare settings modal toggles for the module.
		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'main_content' => esc_html__( 'Main Content', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'style_image_mask' => esc_html__( 'Image Mask', 'squad-modules-for-divi' ),
					'style_stroke'     => esc_html__( 'Stroke', 'squad-modules-for-divi' ),
					'style_gradient'   => esc_html__( 'Animated Gradient', 'squad-modules-for-divi' ),
					'text_effects'     => esc_html__( 'Text', 'squad-modules-for-divi' ),
				),
			),
		);

		// Declare advanced fields for the module.
		$this->advanced_fields = array(
			'fonts'          => array(
				'text_effects' => divi_squad()->d4_module_helper->add_font_field(
					'',
					array(
						'font_size'       => array(
							'default' => '40px',
						),
						'hide_text_color' => true,
						'line_height'     => array(
							'default'        => '1.2em',
							'range_settings' => array(
								'min'  => '1',
								'max'  => '3',
								'step' => '.1',
							),
						),
						'important'       => 'all',
						'css'             => array(
							'main'  => "$this->main_css_element div .text-effects-wrapper .text-effects-element",
							'hover' => "$this->main_css_element div .text-effects-wrapper:hover .text-effects-element",
						),
						'tab_slug'        => 'advanced',
						'toggle_slug'     => 'text_effects',
					)
				),
			),
			'background'     => divi_squad()->d4_module_helper->selectors_background( $this->main_css_element ),
			'borders'        => array( 'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ) ),
			'box_shadow'     => array( 'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ) ),
			'margin_padding' => divi_squad()->d4_module_helper->selectors_margin_padding( $this->main_css_element ),
			'max_width'      => divi_squad()->d4_module_helper->selectors_max_width( $this->main_css_element ),
			'height'         => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			'image_icon'     => false,
			'text'           => false,
			'button'         => false,
			'filters'        => false,
		);

		// Declare custom css fields for the module.
		$this->custom_css_fields = array(
			'text_effects' => array(
				'label'    => esc_html__( 'Text', 'squad-modules-for-divi' ),
				'selector' => 'div .text-effects-wrapper .text-effects-element',
			),
		);
	}

	/**
	 * Declare general fields for the module
	 *
	 * @since 4.4.0
	 * @return array<string, array<string, mixed>>
	 */
	public function get_fields(): array {
		// Content-tab fields.
		$text_fields = array(
			'text_content' => array(
				'label'           => esc_html__( 'Text', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'The headline text that will display the selected effect.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'main_content',
				'dynamic_content' => 'text',
			),
			'text_tag'     => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Text Tag', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Choose a tag to display with your text.', 'squad-modules-for-divi' ),
					'options'          => divi_squad()->d4_module_helper->get_html_tag_elements(),
					'default_on_front' => 'h2',
					'default'          => 'h2',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'main_content',
				)
			),
			'style_type'   => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Effect Style', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Choose which text effect to apply.', 'squad-modules-for-divi' ),
					'options'          => array(
						'image_mask'        => esc_html__( 'Image Mask', 'squad-modules-for-divi' ),
						'stroke'            => esc_html__( 'Stroke', 'squad-modules-for-divi' ),
						'gradient_animated' => esc_html__( 'Animated Gradient', 'squad-modules-for-divi' ),
					),
					'default_on_front' => 'image_mask',
					'default'          => 'image_mask',
					'affects'          => array(
						'mask_image',
						'stroke_width',
						'stroke_color',
						'fill_color',
						'text_gradient_color',
						'animation_speed',
						'pause_on_hover',
					),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'main_content',
				)
			),
		);

		// Design-tab fields, conditional on style_type via depends_show_if (same
		// dependency wiring pattern as Glitch_Text's glitch_text_effect field).
		$image_mask_fields = array(
			'mask_image' => divi_squad()->d4_module_helper->add_media_upload_field(
				esc_html__( 'Mask Image', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Upload an image to fill the text glyphs.', 'squad-modules-for-divi' ),
					'depends_show_if' => 'image_mask',
					'tab_slug'        => 'advanced',
					'toggle_slug'     => 'style_image_mask',
				)
			),
		);

		$stroke_fields = array(
			'stroke_width' => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Stroke Width', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Set the outline thickness for the text stroke.', 'squad-modules-for-divi' ),
					'range_settings'  => array(
						'min_limit' => '0',
						'min'       => '0',
						'max'       => '20',
						'step'      => '1',
					),
					'default'         => '2px',
					'default_unit'    => 'px',
					'depends_show_if' => 'stroke',
					'tab_slug'        => 'advanced',
					'toggle_slug'     => 'style_stroke',
				)
			),
			'stroke_color' => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Stroke Color', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Pick a color for the text stroke outline.', 'squad-modules-for-divi' ),
					'default'         => '#2ea3f2',
					'depends_show_if' => 'stroke',
					'tab_slug'        => 'advanced',
					'toggle_slug'     => 'style_stroke',
				)
			),
			'fill_color'   => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Fill Color', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Optional fill color inside the stroke outline. Leave empty for a hollow/transparent fill.', 'squad-modules-for-divi' ),
					'default'         => '',
					'depends_show_if' => 'stroke',
					'tab_slug'        => 'advanced',
					'toggle_slug'     => 'style_stroke',
				)
			),
		);

		// Gradient settings — reuses the shared gradient-stop field helper,
		// same shape as Gradient_Text's text_gradient_color.
		$gradient_styles = $this->squad_utils->field_definitions->add_background_gradient_field(
			array(
				'label'           => esc_html__( 'Gradient Colors', 'squad-modules-for-divi' ),
				'base_name'       => 'text_gradient',
				'context'         => 'text_gradient_color',
				'depends_show_if' => 'gradient_animated',
				'tab_slug'        => 'advanced',
				'toggle_slug'     => 'style_gradient',
			)
		);

		// remove unneeded fields.
		unset( $gradient_styles['text_gradient_color']['background_fields']['text_gradient_color_gradient_overlays_image'] );

		// Set default color.
		$gradient_styles['text_gradient_color']['background_fields']['text_gradient_use_color_gradient']['default']   = 'on';
		$gradient_styles['text_gradient_color']['background_fields']['text_gradient_color_gradient_stops']['default'] = '#1f7016 0%|#29c4a9 100%';

		$gradient_animation_fields = array(
			'animation_speed' => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Animation Speed', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Duration of one full gradient cycle, in seconds.', 'squad-modules-for-divi' ),
					'range_settings'  => array(
						'min_limit' => '0.5',
						'min'       => '0.5',
						'max'       => '20',
						'step'      => '0.5',
					),
					'default'         => '4s',
					'default_unit'    => 's',
					'allowed_units'   => array( 's' ),
					'depends_show_if' => 'gradient_animated',
					'tab_slug'        => 'advanced',
					'toggle_slug'     => 'style_gradient',
				)
			),
			'pause_on_hover'  => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Pause On Hover', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Pause the gradient animation while the visitor hovers over the text.', 'squad-modules-for-divi' ),
					'default_on_front' => 'off',
					'depends_show_if'  => 'gradient_animated',
					'tab_slug'         => 'advanced',
					'toggle_slug'      => 'style_gradient',
				)
			),
		);

		return array_merge( $text_fields, $image_mask_fields, $stroke_fields, $gradient_styles, $gradient_animation_fields );
	}

	/**
	 * Get CSS fields transition.
	 *
	 * @since 4.4.0
	 * @return array<string, array<string, string>>
	 */
	public function get_transition_fields_css_props(): array {
		$fields = parent::get_transition_fields_css_props();

		$fields['background_layout'] = array( 'color' => $this->main_css_element );

		return $fields;
	}

	/**
	 * Renders the module output.
	 *
	 * @param array<string, mixed> $attrs       List of attributes.
	 * @param string               $content     Content being processed.
	 * @param string               $render_slug Slug of module that is used for rendering output.
	 *
	 * @return string
	 */
	public function render( $attrs, $content, $render_slug ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClassAfterLastUsed
		$text_content = $this->prop( 'text_content', '' );
		if ( '' === $text_content ) {
			return '';
		}

		$text_tag = (string) $this->prop( 'text_tag', 'h2' );
		if ( ! in_array( $text_tag, self::ALLOWED_TAGS, true ) ) {
			$text_tag = 'h2';
		}

		$style_type = (string) $this->prop( 'style_type', 'image_mask' );
		if ( ! in_array( $style_type, self::ALLOWED_STYLE_TYPES, true ) ) {
			$style_type = 'image_mask';
		}

		$wrapper_classes = array( 'text-effects-wrapper', "text-effects-style--$style_type", 'et_pb_with_background' );

		if ( 'gradient_animated' === $style_type ) {
			$this->squad_generate_gradient_styles( $attrs );

			if ( 'on' === $this->prop( 'pause_on_hover', 'off' ) ) {
				$wrapper_classes[] = 'tfx-pause-hover';
			}
		}

		$style_attr = $this->squad_generate_style_attr( $style_type );

		return sprintf(
			'<div class="%1$s"><%2$s class="text-effects-element" style="%3$s">%4$s</%2$s></div>',
			esc_attr( implode( ' ', $wrapper_classes ) ),
			esc_attr( $text_tag ),
			esc_attr( $style_attr ),
			esc_html( $text_content )
		);
	}

	/**
	 * Builds the inline `style` attribute value (CSS custom properties) for the
	 * active style_type. All dynamic values are sanitized before interpolation.
	 *
	 * @since 4.4.0
	 *
	 * @param string $style_type The active style type.
	 *
	 * @return string
	 */
	private function squad_generate_style_attr( string $style_type ): string {
		if ( 'image_mask' === $style_type ) {
			$mask_image = (string) $this->prop( 'mask_image', '' );
			if ( '' === $mask_image ) {
				return '';
			}

			return sprintf( '--tfx-mask-image:url(%s);', esc_url( $mask_image ) );
		}

		if ( 'stroke' === $style_type ) {
			$stroke_width   = self::sanitize_css_length( (string) $this->prop( 'stroke_width', '2px' ), '2px' );
			$stroke_color   = self::sanitize_css_background( (string) $this->prop( 'stroke_color', '#2ea3f2' ) );
			$fill_color_raw = (string) $this->prop( 'fill_color', '' );
			$fill_color     = '' !== $fill_color_raw ? self::sanitize_css_background( $fill_color_raw ) : 'transparent';

			return sprintf(
				'--tfx-stroke-width:%1$s;--tfx-stroke-color:%2$s;--tfx-fill-color:%3$s;',
				$stroke_width,
				$stroke_color,
				$fill_color
			);
		}

		// gradient_animated: only the animation duration is a CSS var; the
		// gradient background image itself is written to the page stylesheet
		// by squad_generate_gradient_styles() via et_pb_background_options(),
		// same mechanism as Gradient_Text.
		$speed_raw = (string) $this->prop( 'animation_speed', '4s' );

		// sanitize_css_length()'s unit whitelist doesn't include seconds, so
		// animation_speed is sanitized locally: strip everything but digits/dot.
		$speed_value = (float) preg_replace( '/[^0-9.]/', '', $speed_raw );
		$speed_value = $speed_value > 0 ? $speed_value : 4.0;

		return sprintf( '--tfx-speed:%ss;', $speed_value );
	}

	/**
	 * Renders the gradient background styles for the module output, reusing
	 * the same et_pb_background_options() mechanism as Gradient_Text.
	 *
	 * @since 4.4.0
	 *
	 * @param array<string, mixed> $attrs List of attributes.
	 */
	private function squad_generate_gradient_styles( array $attrs ): void {
		// Fixed: the custom background doesn't work at frontend.
		$this->props = array_merge( $attrs, $this->props );

		et_pb_background_options()->get_background_style(
			array(
				'base_prop_name'         => 'text_gradient',
				'props'                  => $this->props,
				'selector'               => "$this->main_css_element div .text-effects-wrapper .text-effects-element",
				'selector_hover'         => "$this->main_css_element div .text-effects-wrapper .text-effects-element:hover",
				'selector_sticky'        => "$this->main_css_element div .text-effects-wrapper .text-effects-element",
				'function_name'          => $this->slug,
				'important'              => ' !important',
				'use_background_video'   => false,
				'use_background_pattern' => false,
				'use_background_mask'    => false,
				'prop_name_aliases'      => array(
					'use_text_gradient_color_gradient' => 'text_gradient_use_color_gradient',
					'text_gradient'                    => 'text_gradient_color',
				),
			)
		);
	}
}
