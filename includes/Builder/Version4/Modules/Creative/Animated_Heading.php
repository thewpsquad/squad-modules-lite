<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Animated Heading Module (Divi 4 shortcode).
 *
 * Renders a `.squad-anim-heading` shell: prefix + a rotating set of words +
 * suffix. The cycle is driven client-side by `animated-heading.ts` (toggles
 * `.is-active`); CSS owns the transitions. No anime.js.
 *
 * @since   4.1.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Creative;

use DiviSquad\Builder\Shared\Modules\Creative\Animated_Heading\Heading_Helper;
use DiviSquad\Builder\Version4\Abstracts\Module;
use function absint;
use function esc_html__;
use function in_array;
use function sprintf;
use function trim;
use function wp_enqueue_script;

/**
 * Animated Heading module class (Divi 4).
 *
 * @since 4.1.0
 */
class Animated_Heading extends Module {

	public function init(): void {
		$this->name      = esc_html__( 'Animated Heading', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Animated Headings', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'animated-heading.svg' );

		$this->slug             = 'disq_animated_heading';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.$this->slug";

		$this->child_title_var          = 'rotating_text';
		$this->child_title_fallback_var = 'admin_label';

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'text'      => esc_html__( 'Text', 'squad-modules-for-divi' ),
					'animation' => esc_html__( 'Animation', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'prefix_text'   => esc_html__( 'Prefix Text', 'squad-modules-for-divi' ),
					'rotating_text' => esc_html__( 'Rotating Text', 'squad-modules-for-divi' ),
					'suffix_text'   => esc_html__( 'Suffix Text', 'squad-modules-for-divi' ),
					'heading_style' => esc_html__( 'Heading Style', 'squad-modules-for-divi' ),
				),
			),
		);

		$this->advanced_fields = array(
			'fonts'          => array(
				'prefix_text'   => divi_squad()->d4_module_helper->add_font_field(
					'',
					array(
						'font_size'   => array( 'default' => '32px' ),
						'css'         => array( 'main' => "$this->main_css_element .squad-anim-heading__prefix" ),
						'tab_slug'    => 'advanced',
						'toggle_slug' => 'prefix_text',
					)
				),
				'rotating_text' => divi_squad()->d4_module_helper->add_font_field(
					'',
					array(
						'font_size'   => array( 'default' => '32px' ),
						'css'         => array( 'main' => "$this->main_css_element .squad-anim-heading__rotator" ),
						'tab_slug'    => 'advanced',
						'toggle_slug' => 'rotating_text',
					)
				),
				'suffix_text'   => divi_squad()->d4_module_helper->add_font_field(
					'',
					array(
						'font_size'   => array( 'default' => '32px' ),
						'css'         => array( 'main' => "$this->main_css_element .squad-anim-heading__suffix" ),
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
	 * Declare general fields for the module.
	 *
	 * @since 4.1.0
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_fields(): array {
		return array(
			// ── Content tab / Text group ──────────────────────────────────────
			'prefix_text'    => array(
				'label'       => esc_html__( 'Prefix Text', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Text shown before the rotating word.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'tab_slug'    => 'general',
				'toggle_slug' => 'text',
			),
			'rotating_text'  => array(
				'label'       => esc_html__( 'Rotating Words', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'One word or phrase per line. Each is shown in turn.', 'squad-modules-for-divi' ),
				'type'        => 'textarea',
				'default'     => "Fast\nSimple\nPowerful",
				'tab_slug'    => 'general',
				'toggle_slug' => 'text',
			),
			'suffix_text'    => array(
				'label'       => esc_html__( 'Suffix Text', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Text shown after the rotating word.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'tab_slug'    => 'general',
				'toggle_slug' => 'text',
			),
			'heading_tag'    => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Heading Tag', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'HTML tag wrapping the full line.', 'squad-modules-for-divi' ),
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
			// ── Content tab / Animation group ────────────────────────────────
			'granularity'    => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Granularity', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Animate the whole word, or each letter.', 'squad-modules-for-divi' ),
					'options'     => array(
						'word'   => esc_html__( 'Word', 'squad-modules-for-divi' ),
						'letter' => esc_html__( 'Letter', 'squad-modules-for-divi' ),
					),
					'default'     => 'word',
					'tab_slug'    => 'general',
					'toggle_slug' => 'animation',
				)
			),
			'effect'         => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Effect', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Transition used as each word enters.', 'squad-modules-for-divi' ),
					'options'     => array(
						'fade'  => esc_html__( 'Fade', 'squad-modules-for-divi' ),
						'slide' => esc_html__( 'Slide', 'squad-modules-for-divi' ),
						'scale' => esc_html__( 'Scale', 'squad-modules-for-divi' ),
						'flip'  => esc_html__( 'Flip', 'squad-modules-for-divi' ),
					),
					'default'     => 'fade',
					'tab_slug'    => 'general',
					'toggle_slug' => 'animation',
				)
			),
			'duration'       => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Transition Duration (ms)', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'How long each in/out transition takes.', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '100', 'max' => '3000', 'step' => '50' ),
					'default'        => '600',
					'unitless'       => true,
					'tab_slug'       => 'general',
					'toggle_slug'    => 'animation',
				)
			),
			'rotation_delay' => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Hold Delay (ms)', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'How long each word is held before the next.', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '200', 'max' => '10000', 'step' => '100' ),
					'default'        => '1500',
					'unitless'       => true,
					'tab_slug'       => 'general',
					'toggle_slug'    => 'animation',
				)
			),
			'easing'         => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Easing', 'squad-modules-for-divi' ),
				array(
					'options'     => array(
						'linear'      => esc_html__( 'Linear', 'squad-modules-for-divi' ),
						'ease'        => esc_html__( 'Ease', 'squad-modules-for-divi' ),
						'ease-in'     => esc_html__( 'Ease In', 'squad-modules-for-divi' ),
						'ease-out'    => esc_html__( 'Ease Out', 'squad-modules-for-divi' ),
						'ease-in-out' => esc_html__( 'Ease In Out', 'squad-modules-for-divi' ),
						'smooth'      => esc_html__( 'Smooth', 'squad-modules-for-divi' ),
					),
					'default'     => 'ease-in-out',
					'tab_slug'    => 'general',
					'toggle_slug' => 'animation',
				)
			),
			'letter_stagger' => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Letter Stagger (ms)', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Per-character delay (letter granularity only).', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '0', 'max' => '300', 'step' => '5' ),
					'default'        => '40',
					'unitless'       => true,
					'show_if'        => array( 'granularity' => 'letter' ),
					'tab_slug'       => 'general',
					'toggle_slug'    => 'animation',
				)
			),
			// ── Advanced tab / Heading Style group ───────────────────────────
			'rotating_color' => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Rotating Word Color', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Color applied to the rotating word(s).', 'squad-modules-for-divi' ),
					'default'     => '',
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'heading_style',
				)
			),
			'content_align'  => divi_squad()->d4_module_helper->add_alignment_field(
				esc_html__( 'Content Alignment', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Horizontal alignment of the line.', 'squad-modules-for-divi' ),
					'type'        => 'text_align',
					'default'     => 'left',
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'heading_style',
				)
			),
		);
	}

	/**
	 * Render module output.
	 *
	 * @since 4.1.0
	 *
	 * @param array<string, mixed> $attrs       List of attributes.
	 * @param string               $content     Content being processed.
	 * @param string               $render_slug Slug of module that is used for rendering output.
	 *
	 * @return string
	 */
	public function render( $attrs, $content, $render_slug ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClassAfterLastUsed
		wp_enqueue_script( 'squad-module-animated-heading' );

		$tag_raw = (string) $this->prop( 'heading_tag', 'h2' );
		$gran    = (string) $this->prop( 'granularity', 'word' );
		$effect  = (string) $this->prop( 'effect', 'fade' );

		$config = array(
			'prefix'         => (string) $this->prop( 'prefix_text', '' ),
			'rotating'       => (string) $this->prop( 'rotating_text', "Fast\nSimple\nPowerful" ),
			'suffix'         => (string) $this->prop( 'suffix_text', '' ),
			'heading_tag'    => Heading_Helper::is_valid_tag( $tag_raw ) ? $tag_raw : 'h2',
			'granularity'    => Heading_Helper::is_valid_granularity( $gran ) ? $gran : 'word',
			'effect'         => Heading_Helper::is_valid_effect( $effect ) ? $effect : 'fade',
			'duration'       => (string) absint( $this->prop( 'duration', '600' ) ),
			'rotation_delay' => (string) absint( $this->prop( 'rotation_delay', '1500' ) ),
		);

		$this->apply_css( $render_slug );

		return Heading_Helper::build_shell( $config );
	}

	/**
	 * Emit per-instance CSS: transition duration/easing, letter stagger,
	 * rotating-word color, line text-align — all scoped by %%order_class%%.
	 *
	 * @since 4.1.0
	 *
	 * @param string $render_slug Module render slug.
	 *
	 * @return void
	 */
	protected function apply_css( string $render_slug ): void {
		$root = '%%order_class%% .squad-anim-heading';

		$duration = absint( $this->prop( 'duration', '600' ) );
		$raw_ease = (string) $this->prop( 'easing', 'ease-in-out' );
		$easing   = Heading_Helper::is_valid_easing( $raw_ease ) ? Heading_Helper::easing_value( $raw_ease ) : 'ease-in-out';
		$stagger  = absint( $this->prop( 'letter_stagger', '40' ) );

		self::set_style(
			$render_slug,
			array(
				'selector'    => $root,
				'declaration' => sprintf( '--squad-ah-duration: %dms; --squad-ah-easing: %s; --squad-ah-stagger: %dms;', $duration, $easing, $stagger ),
			)
		);

		$color = self::sanitize_css_background( (string) $this->prop( 'rotating_color', '' ) );
		if ( '' !== $color ) {
			self::set_style(
				$render_slug,
				array(
					'selector'    => '%%order_class%% .squad-anim-heading__word',
					'declaration' => sprintf( 'color: %s;', $color ),
				)
			);
		}

		$align   = (string) $this->prop( 'content_align', 'left' );
		$allowed = array( 'left', 'center', 'right' );
		if ( in_array( $align, $allowed, true ) ) {
			self::set_style(
				$render_slug,
				array(
					'selector'    => $root,
					'declaration' => sprintf( 'text-align: %s;', $align ),
				)
			);
		}
	}

}
