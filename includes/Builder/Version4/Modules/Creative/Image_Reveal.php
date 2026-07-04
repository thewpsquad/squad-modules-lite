<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Image Reveal Module (Divi 4 shortcode).
 *
 * Renders a `.squad-image-reveal` figure containing the image (optionally
 * link-wrapped) and — for the overlay style — a color overlay layer. The reveal
 * is driven client-side by `image-reveal.ts` (adds `.is-armed` then
 * `.is-revealed`); CSS owns the transitions. Server output is visible by default
 * so the module degrades gracefully with no JS / reduced motion.
 *
 * @since   4.1.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Creative;

use DiviSquad\Builder\Shared\Modules\Creative\Image_Reveal\Reveal_Helper;
use DiviSquad\Builder\Version4\Abstracts\Module;
use function absint;
use function esc_html__;
use function preg_replace;
use function sprintf;
use function trim;
use function wp_enqueue_script;

/**
 * Image Reveal module class (Divi 4).
 *
 * @since 4.1.0
 */
class Image_Reveal extends Module {

	public function init(): void {
		$this->name      = esc_html__( 'Image Reveal', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Image Reveals', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'image-reveal.svg' );

		$this->slug             = 'disq_image_reveal';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.$this->slug";

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'image'  => esc_html__( 'Image', 'squad-modules-for-divi' ),
					'link'   => esc_html__( 'Link', 'squad-modules-for-divi' ),
					'reveal' => esc_html__( 'Reveal', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'reveal_style' => esc_html__( 'Reveal Style', 'squad-modules-for-divi' ),
					'hover'        => esc_html__( 'Hover Zoom', 'squad-modules-for-divi' ),
				),
			),
		);

		$this->advanced_fields = array(
			'background'     => divi_squad()->d4_module_helper->selectors_background( $this->main_css_element ),
			'borders'        => array( 'default' => divi_squad()->d4_module_helper->selectors_default( "$this->main_css_element .squad-image-reveal__frame" ) ),
			'box_shadow'     => array( 'default' => divi_squad()->d4_module_helper->selectors_default( "$this->main_css_element .squad-image-reveal__frame" ) ),
			'margin_padding' => divi_squad()->d4_module_helper->selectors_margin_padding( $this->main_css_element ),
			'max_width'      => divi_squad()->d4_module_helper->selectors_max_width( $this->main_css_element ),
			'height'         => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			'fonts'          => false,
			'text'           => false,
			'button'         => false,
			'image_icon'     => false,
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
			// ── Content tab / Image group ─────────────────────────────────────
			'image'           => array(
				'label'              => esc_html__( 'Image', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'The image to reveal.', 'squad-modules-for-divi' ),
				'type'               => 'upload',
				'option_category'    => 'basic_option',
				'upload_button_text' => esc_html__( 'Upload an image', 'squad-modules-for-divi' ),
				'choose_text'        => esc_html__( 'Choose an Image', 'squad-modules-for-divi' ),
				'update_text'        => esc_html__( 'Set As Image', 'squad-modules-for-divi' ),
				'default'            => '',
				'tab_slug'           => 'general',
				'toggle_slug'        => 'image',
			),
			'alt'             => array(
				'label'       => esc_html__( 'Image Alt Text', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Alternative text for accessibility and SEO.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'tab_slug'    => 'general',
				'toggle_slug' => 'image',
			),
			'title_text'      => array(
				'label'       => esc_html__( 'Image Title', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'The title attribute on the image.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'tab_slug'    => 'general',
				'toggle_slug' => 'image',
			),
			// ── Content tab / Link group ──────────────────────────────────────
			'link_url'        => array(
				'label'       => esc_html__( 'Link URL', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Optional URL to wrap the image in a link.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'tab_slug'    => 'general',
				'toggle_slug' => 'link',
			),
			'link_new_window' => array(
				'label'       => esc_html__( 'Open in New Window', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Open the link in a new browser tab.', 'squad-modules-for-divi' ),
				'type'        => 'yes_no_button',
				'options'     => array(
					'off' => esc_html__( 'No', 'squad-modules-for-divi' ),
					'on'  => esc_html__( 'Yes', 'squad-modules-for-divi' ),
				),
				'default'     => 'off',
				'tab_slug'    => 'general',
				'toggle_slug' => 'link',
			),
			// ── Content tab / Reveal group ────────────────────────────────────
			'trigger'         => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Trigger', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'When the reveal plays.', 'squad-modules-for-divi' ),
					'options'     => array(
						'scroll' => esc_html__( 'On Scroll Into View', 'squad-modules-for-divi' ),
						'hover'  => esc_html__( 'On Hover', 'squad-modules-for-divi' ),
					),
					'default'     => 'scroll',
					'tab_slug'    => 'general',
					'toggle_slug' => 'reveal',
				)
			),
			'reveal_style'    => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Reveal Style', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Color overlay that slides away, or a clip-path wipe of the image.', 'squad-modules-for-divi' ),
					'options'     => array(
						'overlay' => esc_html__( 'Color Overlay', 'squad-modules-for-divi' ),
						'clip'    => esc_html__( 'Clip Wipe', 'squad-modules-for-divi' ),
					),
					'default'     => 'overlay',
					'tab_slug'    => 'general',
					'toggle_slug' => 'reveal',
				)
			),
			'direction'       => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Direction', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Direction the reveal travels.', 'squad-modules-for-divi' ),
					'options'     => array(
						'ltr' => esc_html__( 'Left to Right', 'squad-modules-for-divi' ),
						'rtl' => esc_html__( 'Right to Left', 'squad-modules-for-divi' ),
						'ttb' => esc_html__( 'Top to Bottom', 'squad-modules-for-divi' ),
						'btt' => esc_html__( 'Bottom to Top', 'squad-modules-for-divi' ),
					),
					'default'     => 'ltr',
					'tab_slug'    => 'general',
					'toggle_slug' => 'reveal',
				)
			),
			'duration'        => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Animation Duration (ms)', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'How long the reveal takes.', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '100', 'max' => '4000', 'step' => '50' ),
					'default'        => '600',
					'unitless'       => true,
					'tab_slug'       => 'general',
					'toggle_slug'    => 'reveal',
				)
			),
			'delay'           => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Start Delay (ms)', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Delay before the reveal begins.', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '0', 'max' => '5000', 'step' => '50' ),
					'default'        => '0',
					'unitless'       => true,
					'tab_slug'       => 'general',
					'toggle_slug'    => 'reveal',
				)
			),
			'easing'          => divi_squad()->d4_module_helper->add_select_box_field(
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
					'toggle_slug' => 'reveal',
				)
			),
			'threshold'       => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Viewport Trigger (%)', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'How much of the image must be visible before a scroll reveal fires.', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '1', 'max' => '100', 'step' => '1' ),
					'default'        => '50',
					'unitless'       => true,
					'show_if'        => array( 'trigger' => 'scroll' ),
					'tab_slug'       => 'general',
					'toggle_slug'    => 'reveal',
				)
			),
			// ── Advanced tab / Reveal Style group ─────────────────────────────
			'reveal_color'    => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Reveal Color', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Color of the overlay layer (Color Overlay style only).', 'squad-modules-for-divi' ),
					'default'     => '#000000',
					'show_if'     => array( 'reveal_style' => 'overlay' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'reveal_style',
				)
			),
			// ── Advanced tab / Hover Zoom group ───────────────────────────────
			'hover_zoom'      => array(
				'label'       => esc_html__( 'Enable Hover Zoom', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Scale the image up while hovered.', 'squad-modules-for-divi' ),
				'type'        => 'yes_no_button',
				'options'     => array(
					'off' => esc_html__( 'No', 'squad-modules-for-divi' ),
					'on'  => esc_html__( 'Yes', 'squad-modules-for-divi' ),
				),
				'default'     => 'off',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'hover',
			),
			'zoom_scale'      => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Zoom Scale', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Scale factor applied on hover.', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '1', 'max' => '2', 'step' => '0.05' ),
					'default'        => '1.1',
					'unitless'       => true,
					'show_if'        => array( 'hover_zoom' => 'on' ),
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'hover',
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
		$image = trim( (string) $this->prop( 'image', '' ) );
		if ( '' === $image ) {
			return '';
		}

		wp_enqueue_script( 'squad-module-image-reveal' );

		$trigger = (string) $this->prop( 'trigger', 'scroll' );
		$style   = (string) $this->prop( 'reveal_style', 'overlay' );
		$dir     = (string) $this->prop( 'direction', 'ltr' );

		$config = array(
			'image'           => $image,
			'alt'             => (string) $this->prop( 'alt', '' ),
			'title'           => (string) $this->prop( 'title_text', '' ),
			'link_url'        => (string) $this->prop( 'link_url', '' ),
			'link_new_window' => (string) $this->prop( 'link_new_window', 'off' ),
			'trigger'         => Reveal_Helper::is_valid_trigger( $trigger ) ? $trigger : 'scroll',
			'style'           => Reveal_Helper::is_valid_style( $style ) ? $style : 'overlay',
			'direction'       => Reveal_Helper::is_valid_direction( $dir ) ? $dir : 'ltr',
			'threshold'       => Reveal_Helper::clamp_threshold( $this->prop( 'threshold', '50' ) ),
			'zoom'            => (string) $this->prop( 'hover_zoom', 'off' ),
		);

		$this->apply_css( $render_slug );

		return Reveal_Helper::build_shell( $config );
	}

	/**
	 * Emit per-instance CSS: reveal duration/delay/easing/zoom CSS vars and the
	 * overlay color — all scoped by %%order_class%%.
	 *
	 * @since 4.1.0
	 *
	 * @param string $render_slug Module render slug.
	 *
	 * @return void
	 */
	protected function apply_css( string $render_slug ): void {
		$root = '%%order_class%% .squad-image-reveal';

		$duration = absint( $this->prop( 'duration', '600' ) );
		$delay    = absint( $this->prop( 'delay', '0' ) );
		$raw_ease = (string) $this->prop( 'easing', 'ease-in-out' );
		$easing   = Reveal_Helper::is_valid_easing( $raw_ease ) ? Reveal_Helper::easing_value( $raw_ease ) : 'ease-in-out';
		$zoom     = self::sanitize_scale( (string) $this->prop( 'zoom_scale', '1.1' ) );

		self::set_style(
			$render_slug,
			array(
				'selector'    => $root,
				'declaration' => sprintf(
					'--squad-ir-duration: %dms; --squad-ir-delay: %dms; --squad-ir-easing: %s; --squad-ir-zoom: %s;',
					$duration,
					$delay,
					$easing,
					$zoom
				),
			)
		);

		$style = (string) $this->prop( 'reveal_style', 'overlay' );
		if ( 'overlay' === $style ) {
			$color = self::sanitize_css_background( (string) $this->prop( 'reveal_color', '#000000' ) );
			if ( '' !== $color ) {
				self::set_style(
					$render_slug,
					array(
						'selector'    => '%%order_class%% .squad-image-reveal__overlay',
						'declaration' => sprintf( 'background: %s;', $color ),
					)
				);
			}
		}
	}

	/**
	 * Sanitize a numeric scale value for CSS (digits + single dot only).
	 *
	 * @since 4.1.0
	 *
	 * @param string $value Raw value.
	 *
	 * @return string Sanitized value, or '1.1' when nothing valid remains.
	 */
	private static function sanitize_scale( string $value ): string {
		$clean = (string) preg_replace( '/[^0-9.]/', '', trim( $value ) );

		return '' !== $clean ? $clean : '1.1';
	}

}
