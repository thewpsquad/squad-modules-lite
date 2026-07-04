<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Timeline Module (Divi 4 shortcode).
 *
 * Parent module accepting Timeline Item children. Renders a `.squad-timeline`
 * track in vertical/horizontal orientation with alternating/one-side layouts
 * and optional scroll-triggered reveal. Pure CSS layout + a tiny
 * IntersectionObserver (`timeline.ts`). No external carousel/lib dependency.
 *
 * @since   4.3.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Content;

use DiviSquad\Builder\Shared\Modules\Content\Timeline\Timeline_Helper;
use DiviSquad\Builder\Version4\Abstracts\Module;
use function absint;
use function esc_attr;
use function esc_html__;
use function max;
use function min;
use function wp_enqueue_script;

/**
 * Timeline Module class.
 *
 * @since 4.3.0
 */
class Timeline extends Module {

	/**
	 * Initiate Module.
	 *
	 * @since 4.3.0
	 * @return void
	 */
	public function init(): void {
		$this->name      = esc_html__( 'Timeline', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Timelines', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'timeline.svg' );

		$this->slug             = 'disq_timeline';
		$this->child_slug       = 'disq_timeline_item';
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
					'line'   => esc_html__( 'Line', 'squad-modules-for-divi' ),
					'marker' => esc_html__( 'Marker', 'squad-modules-for-divi' ),
					'item'   => esc_html__( 'Item', 'squad-modules-for-divi' ),
				),
			),
		);

		$this->advanced_fields = array(
			'background'     => divi_squad()->d4_module_helper->selectors_background( $this->main_css_element ),
			'borders'        => array(
				'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
				'item'    => array(
					'css'         => array(
						'main' => "{$this->main_css_element} .squad-timeline__card",
					),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'item',
				),
			),
			'box_shadow'     => array(
				'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			),
			'margin_padding' => divi_squad()->d4_module_helper->selectors_margin_padding( $this->main_css_element ),
			'max_width'      => divi_squad()->d4_module_helper->selectors_max_width( $this->main_css_element ),
			'height'         => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			'fonts'          => false,
			'image_icon'     => false,
			'text'           => false,
			'button'         => false,
			'filters'        => false,
		);

		$this->custom_css_fields = array(
			'timeline' => array(
				'label'    => esc_html__( 'Timeline', 'squad-modules-for-divi' ),
				'selector' => '.squad-timeline',
			),
			'line'     => array(
				'label'    => esc_html__( 'Line', 'squad-modules-for-divi' ),
				'selector' => '.squad-timeline__line',
			),
			'marker'   => array(
				'label'    => esc_html__( 'Marker', 'squad-modules-for-divi' ),
				'selector' => '.squad-timeline__marker',
			),
			'card'     => array(
				'label'    => esc_html__( 'Card', 'squad-modules-for-divi' ),
				'selector' => '.squad-timeline__card',
			),
		);
	}

	/**
	 * Define module fields.
	 *
	 * @since 4.3.0
	 * @return array<string, array<string, mixed>>
	 */
	public function get_fields(): array {
		return array(
			// Layout.
			'orientation'      => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Orientation', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Lay the timeline out vertically or horizontally.', 'squad-modules-for-divi' ),
					'options'     => array(
						'vertical'   => esc_html__( 'Vertical', 'squad-modules-for-divi' ),
						'horizontal' => esc_html__( 'Horizontal', 'squad-modules-for-divi' ),
					),
					'default'     => 'vertical',
					'tab_slug'    => 'general',
					'toggle_slug' => 'layout_settings',
				)
			),
			'layout'           => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Item Layout', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Alternate items side to side, or push them all to one side.', 'squad-modules-for-divi' ),
					'options'     => array(
						'alternating' => esc_html__( 'Alternating', 'squad-modules-for-divi' ),
						'left'        => esc_html__( 'Left', 'squad-modules-for-divi' ),
						'right'       => esc_html__( 'Right', 'squad-modules-for-divi' ),
					),
					'default'     => 'alternating',
					'tab_slug'    => 'general',
					'toggle_slug' => 'layout_settings',
				)
			),
			// Scroll Reveal.
			'reveal_on_scroll' => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Reveal On Scroll', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Animate items into view as they scroll onto the screen.', 'squad-modules-for-divi' ),
					'default'     => 'on',
					'tab_slug'    => 'general',
					'toggle_slug' => 'reveal_settings',
				)
			),
			'reveal_stagger'   => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Reveal Stagger (ms)', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Delay added per item when revealing on scroll.', 'squad-modules-for-divi' ),
					'range_settings' => array(
						'min'  => '0',
						'max'  => '600',
						'step' => '10',
					),
					'default'        => '120',
					'unitless'       => true,
					'mobile_options' => false,
					'hover'          => false,
					'tab_slug'       => 'general',
					'toggle_slug'    => 'reveal_settings',
				)
			),
			// Advanced colors.
			'line_color'       => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Line Color', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Color of the timeline connector line.', 'squad-modules-for-divi' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'line',
				)
			),
			'marker_color'     => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Marker Color', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Color of the item markers (dots, numbers, icons).', 'squad-modules-for-divi' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'marker',
				)
			),
		);
	}

	/**
	 * Render the module HTML.
	 *
	 * @since 4.3.0
	 *
	 * @param array<string, mixed> $attrs       Module attributes.
	 * @param string               $content     Rendered child modules HTML.
	 * @param string               $render_slug Module render slug.
	 *
	 * @return string
	 */
	public function render( $attrs, $content, $render_slug ): string {
		wp_enqueue_script( 'squad-module-timeline' );

		$config = array(
			'orientation' => (string) $this->prop( 'orientation', 'vertical' ),
			'layout'      => (string) $this->prop( 'layout', 'alternating' ),
			'reveal'      => 'on' === (string) $this->prop( 'reveal_on_scroll', 'on' ) ? 'on' : 'off',
			'stagger'     => max( 0, min( 600, absint( $this->prop( 'reveal_stagger', '120' ) ) ) ),
		);

		$this->apply_color_styles( $render_slug );

		return Timeline_Helper::build_track( $config, $content );
	}

	/**
	 * Apply line / marker color CSS via set_style.
	 *
	 * @since 4.3.0
	 *
	 * @param string $render_slug Module render slug.
	 *
	 * @return void
	 */
	protected function apply_color_styles( string $render_slug ): void {
		$line_color = self::sanitize_css_background( (string) $this->prop( 'line_color', '' ) );
		if ( '' !== $line_color ) {
			self::set_style(
				$render_slug,
				array(
					'selector'    => '%%order_class%% .squad-timeline__line',
					'declaration' => sprintf( 'background-color: %s;', esc_attr( $line_color ) ),
				)
			);
		}

		$marker_color = self::sanitize_css_background( (string) $this->prop( 'marker_color', '' ) );
		if ( '' !== $marker_color ) {
			self::set_style(
				$render_slug,
				array(
					'selector'    => '%%order_class%% .squad-timeline__marker',
					'declaration' => sprintf( 'color: %1$s; background-color: %1$s;', esc_attr( $marker_color ) ),
				)
			);
			self::set_style(
				$render_slug,
				array(
					'selector'    => '%%order_class%% .squad-timeline__dot, %%order_class%% .squad-timeline__number, %%order_class%% .squad-timeline__icon',
					'declaration' => sprintf( 'color: %s;', esc_attr( $marker_color ) ),
				)
			);
		}
	}
}
