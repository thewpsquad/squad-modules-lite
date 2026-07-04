<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Step Flow Module (Divi 4 shortcode).
 *
 * Parent module accepting Step Flow Item children. Renders a `.squad-step-flow`
 * container in vertical/horizontal orientation with a connector line between
 * steps and optional scroll-triggered reveal. Pure CSS layout + a tiny
 * IntersectionObserver (`step-flow.ts`). Independent from the Timeline module —
 * no shared helper class — per
 * docs/superpowers/specs/2026-07-03-step-flow-module-design.md §2.
 *
 * @since   4.4.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Content;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use DiviSquad\Builder\Version4\Abstracts\Module;
use function absint;
use function esc_attr;
use function esc_html__;
use function in_array;
use function max;
use function min;
use function wp_enqueue_script;

/**
 * Step Flow Module class.
 *
 * @since 4.4.0
 */
class Step_Flow extends Module {

	/**
	 * Allowed orientation tokens (first = fallback).
	 *
	 * @since 4.4.0
	 *
	 * @var array<int, string>
	 */
	public const ORIENTATIONS = array( 'vertical', 'horizontal' );

	/**
	 * Allowed connector line style tokens (first = fallback).
	 *
	 * @since 4.4.0
	 *
	 * @var array<int, string>
	 */
	public const CONNECTOR_STYLES = array( 'solid', 'dashed', 'dotted' );

	/**
	 * Initiate Module.
	 *
	 * @since 4.4.0
	 * @return void
	 */
	public function init(): void {
		$this->name      = esc_html__( 'Step Flow', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Step Flows', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'step-flow.svg' );

		$this->slug             = 'disq_step_flow';
		$this->child_slug       = 'disq_step_flow_item';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.{$this->slug}";

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'layout_settings' => esc_html__( 'Layout', 'squad-modules-for-divi' ),
					'reveal_settings' => esc_html__( 'Scroll Reveal', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'connector' => esc_html__( 'Connector', 'squad-modules-for-divi' ),
				),
			),
		);

		$this->advanced_fields = array(
			'background'     => divi_squad()->d4_module_helper->selectors_background( $this->main_css_element ),
			'borders'        => array( 'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ) ),
			'box_shadow'     => array( 'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ) ),
			'margin_padding' => divi_squad()->d4_module_helper->selectors_margin_padding( $this->main_css_element ),
			'fonts'          => false,
			'image_icon'     => false,
			'text'           => false,
			'button'         => false,
			'filters'        => false,
		);

		$this->custom_css_fields = array(
			'step_flow' => array(
				'label'    => esc_html__( 'Step Flow', 'squad-modules-for-divi' ),
				'selector' => '.squad-step-flow',
			),
		);
	}

	/**
	 * Define module fields.
	 *
	 * @since 4.4.0
	 * @return array<string, array<string, mixed>>
	 */
	public function get_fields(): array {
		return array(
			'orientation'          => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Orientation', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Lay the steps out vertically or horizontally.', 'squad-modules-for-divi' ),
					'options'     => array(
						'vertical'   => esc_html__( 'Vertical', 'squad-modules-for-divi' ),
						'horizontal' => esc_html__( 'Horizontal', 'squad-modules-for-divi' ),
					),
					'default'     => 'vertical',
					'tab_slug'    => 'general',
					'toggle_slug' => 'layout_settings',
				)
			),
			'reveal_on_scroll'     => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Reveal On Scroll', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Animate steps into view as they scroll onto the screen.', 'squad-modules-for-divi' ),
					'default'     => 'on',
					'tab_slug'    => 'general',
					'toggle_slug' => 'reveal_settings',
				)
			),
			'reveal_delay'         => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Reveal Delay (ms)', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Per-step stagger delay when revealing on scroll.', 'squad-modules-for-divi' ),
					'range_settings' => array(
						'min'  => '0',
						'max'  => '2000',
						'step' => '10',
					),
					'default'        => '100',
					'unitless'       => true,
					'mobile_options' => false,
					'hover'          => false,
					'tab_slug'       => 'general',
					'toggle_slug'    => 'reveal_settings',
				)
			),
			'connector_line_style' => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Connector Line Style', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Line style of the connector between steps.', 'squad-modules-for-divi' ),
					'options'     => array(
						'solid'  => esc_html__( 'Solid', 'squad-modules-for-divi' ),
						'dashed' => esc_html__( 'Dashed', 'squad-modules-for-divi' ),
						'dotted' => esc_html__( 'Dotted', 'squad-modules-for-divi' ),
					),
					'default'     => 'solid',
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'connector',
				)
			),
			'connector_color'      => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Connector Color', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Color of the connector line between steps.', 'squad-modules-for-divi' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'connector',
				)
			),
			'connector_width'      => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Connector Width', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Thickness of the connector line.', 'squad-modules-for-divi' ),
					'range_settings' => array(
						'min'  => '1',
						'max'  => '12',
						'step' => '1',
					),
					'default'        => '2px',
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'connector',
				)
			),
			'show_arrow'           => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Show Arrow', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Render an arrowhead at the end of each connector segment.', 'squad-modules-for-divi' ),
					'default'     => 'off',
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'connector',
				)
			),
		);
	}

	/**
	 * Render the module HTML.
	 *
	 * @since 4.4.0
	 *
	 * @param array<string, mixed> $attrs       Module attributes.
	 * @param string               $content     Rendered child modules HTML.
	 * @param string               $render_slug Module render slug.
	 *
	 * @return string
	 */
	public function render( $attrs, $content, $render_slug ): string {
		wp_enqueue_script( 'squad-module-step-flow' );

		$orientation = (string) $this->prop( 'orientation', 'vertical' );
		$orientation = in_array( $orientation, self::ORIENTATIONS, true ) ? $orientation : 'vertical';

		$reveal = 'on' === (string) $this->prop( 'reveal_on_scroll', 'on' ) ? 'on' : 'off';
		$delay  = max( 0, min( 2000, absint( $this->prop( 'reveal_delay', '100' ) ) ) );
		$arrow  = 'on' === (string) $this->prop( 'show_arrow', 'off' ) ? 'on' : 'off';

		$this->apply_connector_css( $render_slug );

		return sprintf(
			'<div class="squad-step-flow squad-step-flow--%1$s" data-reveal="%2$s" data-delay="%3$s" data-arrow="%4$s">%5$s</div>',
			esc_attr( $orientation ),
			esc_attr( $reveal ),
			esc_attr( (string) $delay ),
			esc_attr( $arrow ),
			$content
		);
	}

	/**
	 * Apply connector line style/color/width CSS via set_style.
	 *
	 * @since 4.4.0
	 *
	 * @param string $render_slug Module render slug.
	 *
	 * @return void
	 */
	public function apply_connector_css( string $render_slug ): void {
		$style = (string) $this->prop( 'connector_line_style', 'solid' );
		$style = in_array( $style, self::CONNECTOR_STYLES, true ) ? $style : 'solid';

		self::set_style(
			$render_slug,
			array(
				'selector'    => '%%order_class%%',
				'declaration' => sprintf( '--squad-sf-connector-style: %s;', esc_attr( $style ) ),
			)
		);

		$color = self::sanitize_css_background( (string) $this->prop( 'connector_color', '' ) );
		if ( '' !== $color ) {
			self::set_style(
				$render_slug,
				array(
					'selector'    => '%%order_class%%',
					'declaration' => sprintf( '--squad-sf-connector-color: %s;', esc_attr( $color ) ),
				)
			);
		}

		$width = self::sanitize_css_length( (string) $this->prop( 'connector_width', '2px' ) );
		if ( '' !== $width ) {
			self::set_style(
				$render_slug,
				array(
					'selector'    => '%%order_class%%',
					'declaration' => sprintf( '--squad-sf-connector-width: %s;', esc_attr( $width ) ),
				)
			);
		}
	}
}
