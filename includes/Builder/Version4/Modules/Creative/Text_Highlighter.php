<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Text Highlighter Module (Divi 4 shortcode).
 *
 * Renders a `.squad-highlight` shell with prefix + highlighted infix annotated
 * by a hand-drawn SVG path + suffix. The SVG stroke-draw animation is driven
 * client-side by `text-highlighter.ts`.
 *
 * @since   4.0.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Creative;

use DiviSquad\Builder\Shared\Modules\Creative\Text_Highlighter\Highlight_Helper;
use DiviSquad\Builder\Version4\Abstracts\Module;
use function absint;
use function esc_html__;
use function sprintf;
use function wp_enqueue_script;

/**
 * Text Highlighter module class.
 *
 * @since 4.0.0
 */
class Text_Highlighter extends Module {

	/**
	 * Initiate Module.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function init(): void {
		$this->name      = esc_html__( 'Text Highlighter', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Text Highlighters', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'text-highlighter.svg' );

		$this->slug             = 'disq_text_highlighter';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.$this->slug";

		$this->child_title_var          = 'highlighted_text';
		$this->child_title_fallback_var = 'admin_label';

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'text'      => esc_html__( 'Text', 'squad-modules-for-divi' ),
					'highlight' => esc_html__( 'Highlight', 'squad-modules-for-divi' ),
					'animation' => esc_html__( 'Animation', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'prefix_text'     => esc_html__( 'Prefix Text', 'squad-modules-for-divi' ),
					'highlight_text'  => esc_html__( 'Highlighted Text', 'squad-modules-for-divi' ),
					'suffix_text'     => esc_html__( 'Suffix Text', 'squad-modules-for-divi' ),
					'highlight_style' => esc_html__( 'Highlight Style', 'squad-modules-for-divi' ),
				),
			),
		);

		$this->advanced_fields = array(
			'fonts'          => array(
				'prefix_text'    => divi_squad()->d4_module_helper->add_font_field(
					'',
					array(
						'font_size'   => array( 'default' => '32px' ),
						'css'         => array( 'main' => "$this->main_css_element .squad-highlight__prefix" ),
						'tab_slug'    => 'advanced',
						'toggle_slug' => 'prefix_text',
					)
				),
				'highlight_text' => divi_squad()->d4_module_helper->add_font_field(
					'',
					array(
						'font_size'   => array( 'default' => '32px' ),
						'css'         => array( 'main' => "$this->main_css_element .squad-highlight__text" ),
						'tab_slug'    => 'advanced',
						'toggle_slug' => 'highlight_text',
					)
				),
				'suffix_text'    => divi_squad()->d4_module_helper->add_font_field(
					'',
					array(
						'font_size'   => array( 'default' => '32px' ),
						'css'         => array( 'main' => "$this->main_css_element .squad-highlight__suffix" ),
						'tab_slug'    => 'advanced',
						'toggle_slug' => 'suffix_text',
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
			'link_options'   => false,
		);
	}

	/**
	 * Get module fields.
	 *
	 * @since 4.0.0
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_fields(): array {
		return array(
			// ── Content tab / Text group ──────────────────────────────────────
			'prefix_text'      => array(
				'label'       => esc_html__( 'Prefix Text', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Text displayed before the highlighted word.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'tab_slug'    => 'general',
				'toggle_slug' => 'text',
			),
			'highlighted_text' => array(
				'label'       => esc_html__( 'Highlighted Text', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'The word(s) that receive the SVG annotation. Leave empty to show prefix + suffix only.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => 'highlighted',
				'tab_slug'    => 'general',
				'toggle_slug' => 'text',
			),
			'suffix_text'      => array(
				'label'       => esc_html__( 'Suffix Text', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Text displayed after the highlighted word.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'tab_slug'    => 'general',
				'toggle_slug' => 'text',
			),
			'heading_tag'      => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Heading Tag', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'HTML tag wrapping the full text line.', 'squad-modules-for-divi' ),
					'options'     => array(
						'h1'   => esc_html__( 'H1', 'squad-modules-for-divi' ),
						'h2'   => esc_html__( 'H2', 'squad-modules-for-divi' ),
						'h3'   => esc_html__( 'H3', 'squad-modules-for-divi' ),
						'h4'   => esc_html__( 'H4', 'squad-modules-for-divi' ),
						'h5'   => esc_html__( 'H5', 'squad-modules-for-divi' ),
						'h6'   => esc_html__( 'H6', 'squad-modules-for-divi' ),
						'p'    => esc_html__( 'Paragraph', 'squad-modules-for-divi' ),
						'span' => esc_html__( 'Span', 'squad-modules-for-divi' ),
						'div'  => esc_html__( 'Div', 'squad-modules-for-divi' ),
					),
					'default'     => 'h2',
					'tab_slug'    => 'general',
					'toggle_slug' => 'text',
				)
			),
			// ── Content tab / Highlight group ────────────────────────────────
			'highlight_type'   => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Highlight Shape', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'The hand-drawn SVG annotation shape.', 'squad-modules-for-divi' ),
					'options'     => array(
						'underline'        => esc_html__( 'Underline', 'squad-modules-for-divi' ),
						'double-underline' => esc_html__( 'Double Underline', 'squad-modules-for-divi' ),
						'circle'           => esc_html__( 'Circle', 'squad-modules-for-divi' ),
						'box'              => esc_html__( 'Box', 'squad-modules-for-divi' ),
						'strikethrough'    => esc_html__( 'Strikethrough', 'squad-modules-for-divi' ),
						'curly-underline'  => esc_html__( 'Curly Underline', 'squad-modules-for-divi' ),
						'cross-off'        => esc_html__( 'Cross Off', 'squad-modules-for-divi' ),
						'bracket'          => esc_html__( 'Bracket', 'squad-modules-for-divi' ),
					),
					'default'     => 'underline',
					'tab_slug'    => 'general',
					'toggle_slug' => 'highlight',
				)
			),
			// ── Content tab / Animation group ────────────────────────────────
			'animate'          => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Animate on Scroll', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Draw the SVG stroke when the element scrolls into view.', 'squad-modules-for-divi' ),
					'default'     => 'on',
					'tab_slug'    => 'general',
					'toggle_slug' => 'animation',
				)
			),
			'anim_duration'    => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Animation Duration (ms)', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'How long the stroke draw takes in milliseconds.', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '100', 'max' => '5000', 'step' => '100' ),
					'default'        => '1200',
					'unitless'       => true,
					'show_if'        => array( 'animate' => 'on' ),
					'tab_slug'       => 'general',
					'toggle_slug'    => 'animation',
				)
			),
			'anim_delay'       => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Animation Delay (ms)', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Delay before the stroke starts drawing.', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '0', 'max' => '3000', 'step' => '100' ),
					'default'        => '0',
					'unitless'       => true,
					'show_if'        => array( 'animate' => 'on' ),
					'tab_slug'       => 'general',
					'toggle_slug'    => 'animation',
				)
			),
			'anim_loop'        => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Loop Animation', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Continuously repeat the draw animation.', 'squad-modules-for-divi' ),
					'default'     => 'off',
					'show_if'     => array( 'animate' => 'on' ),
					'tab_slug'    => 'general',
					'toggle_slug' => 'animation',
				)
			),
			// ── Advanced tab / Highlight Style group ─────────────────────────
			'highlight_color'  => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Highlight Color', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Solid stroke color (used when gradient is off).', 'squad-modules-for-divi' ),
					'default'     => '#6a33d7',
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'highlight_style',
				)
			),
			'use_gradient'     => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Use Gradient Stroke', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Apply a linear gradient to the SVG stroke.', 'squad-modules-for-divi' ),
					'default'     => 'off',
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'highlight_style',
				)
			),
			'gradient_start'   => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Gradient Start Color', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Start color of the stroke gradient.', 'squad-modules-for-divi' ),
					'default'     => '#6a33d7',
					'show_if'     => array( 'use_gradient' => 'on' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'highlight_style',
				)
			),
			'gradient_end'     => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Gradient End Color', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'End color of the stroke gradient.', 'squad-modules-for-divi' ),
					'default'     => '#d433c4',
					'show_if'     => array( 'use_gradient' => 'on' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'highlight_style',
				)
			),
			'gradient_angle'   => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Gradient Angle (deg)', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Rotation of the gradient in degrees.', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '0', 'max' => '360', 'step' => '1' ),
					'default'        => '90',
					'unitless'       => true,
					'show_if'        => array( 'use_gradient' => 'on' ),
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'highlight_style',
				)
			),
			'stroke_width'     => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Stroke Width', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Thickness of the SVG annotation stroke.', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '1', 'max' => '20', 'step' => '1' ),
					'default'        => '3',
					'unitless'       => true,
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'highlight_style',
				)
			),
			'svg_position_y'   => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'SVG Vertical Offset', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Nudge the SVG annotation up or down (px).', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '-20', 'max' => '20', 'step' => '1' ),
					'default'        => '0',
					'unitless'       => true,
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'highlight_style',
				)
			),
			'content_align'    => divi_squad()->d4_module_helper->add_alignment_field(
				esc_html__( 'Content Alignment', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Horizontal alignment of the text line.', 'squad-modules-for-divi' ),
					'type'        => 'text_align',
					'default'     => 'left',
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'highlight_style',
				)
			),
		);
	}

	/**
	 * Render module output.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $attrs       List of attributes.
	 * @param string               $content     Content being processed.
	 * @param string               $render_slug Slug of module being rendered.
	 *
	 * @return string
	 */
	public function render( $attrs, $content, $render_slug ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClassAfterLastUsed
		wp_enqueue_script( 'squad-module-text-highlighter' );

		$type    = (string) $this->prop( 'highlight_type', 'underline' );
		$tag_raw = (string) $this->prop( 'heading_tag', 'h2' );

		$config = array(
			'prefix'          => (string) $this->prop( 'prefix_text', '' ),
			'highlighted'     => (string) $this->prop( 'highlighted_text', 'highlighted' ),
			'suffix'          => (string) $this->prop( 'suffix_text', '' ),
			'heading_tag'     => Highlight_Helper::is_valid_tag( $tag_raw ) ? $tag_raw : 'h2',
			'type'            => Highlight_Helper::is_valid_type( $type ) ? $type : 'underline',
			'animate'         => (string) $this->prop( 'animate', 'on' ),
			'anim_duration'   => (string) absint( $this->prop( 'anim_duration', '1200' ) ),
			'anim_delay'      => (string) absint( $this->prop( 'anim_delay', '0' ) ),
			'anim_loop'       => (string) $this->prop( 'anim_loop', 'off' ),
			'highlight_color' => (string) $this->prop( 'highlight_color', '#6a33d7' ),
			'use_gradient'    => (string) $this->prop( 'use_gradient', 'off' ),
			'gradient_start'  => (string) $this->prop( 'gradient_start', '#6a33d7' ),
			'gradient_end'    => (string) $this->prop( 'gradient_end', '#d433c4' ),
			'gradient_angle'  => (string) absint( $this->prop( 'gradient_angle', '90' ) ),
			'stroke_width'    => (string) absint( $this->prop( 'stroke_width', '3' ) ),
		);

		// Emit vertical-offset CSS for the SVG annotation (field range: -20..20, signed).
		$svg_position_y = (int) $this->prop( 'svg_position_y', '0' );
		if ( 0 !== $svg_position_y ) {
			self::set_style(
				$render_slug,
				array(
					'selector'    => "%%order_class%% .squad-highlight__svg",
					// Use margin-top instead of transform: translateY to avoid conflicting
					// with per-shape transforms (e.g. strikethrough uses translateY(-50%)).
					'declaration' => sprintf( 'margin-top: %dpx;', $svg_position_y ),
				)
			);
		}

		return Highlight_Helper::build_shell( $config );
	}
}
