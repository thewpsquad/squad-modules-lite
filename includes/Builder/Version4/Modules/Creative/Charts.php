<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Charts Module (Divi 4 shortcode).
 *
 * Renders a `.squad-charts` shell carrying a Chart.js v4 config via a
 * `data-config` attribute. The chart is drawn client-side by `charts.ts`.
 *
 * @since   4.3.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Creative;

use DiviSquad\Builder\Shared\Modules\Creative\Charts\Charts_Helper;
use DiviSquad\Builder\Version4\Abstracts\Module;
use function esc_html__;
use function max;
use function min;
use function wp_enqueue_script;

/**
 * Charts Module class.
 *
 * @since 4.3.0
 */
class Charts extends Module {

	/**
	 * Initiate Module.
	 *
	 * @since 4.3.0
	 *
	 * @return void
	 */
	public function init(): void {
		$this->name      = esc_html__( 'Charts', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Charts', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'charts.svg' );

		$this->slug             = 'disq_charts';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.$this->slug";

		$this->child_title_var          = 'title';
		$this->child_title_fallback_var = 'admin_label';

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		$this->settings_modal_toggles = array(
			'general' => array(
				'toggles' => array(
					'chart'   => esc_html__( 'Chart', 'squad-modules-for-divi' ),
					'data'    => esc_html__( 'Data', 'squad-modules-for-divi' ),
					'options' => esc_html__( 'Options', 'squad-modules-for-divi' ),
				),
			),
		);

		$this->advanced_fields = array(
			'background'     => divi_squad()->d4_module_helper->selectors_background( $this->main_css_element ),
			'borders'        => array( 'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ) ),
			'box_shadow'     => array( 'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ) ),
			'margin_padding' => divi_squad()->d4_module_helper->selectors_margin_padding( $this->main_css_element ),
			'max_width'      => divi_squad()->d4_module_helper->selectors_max_width( $this->main_css_element ),
			'height'         => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			'fonts'          => false,
			'image_icon'     => false,
			'text'           => false,
			'button'         => false,
			'filters'        => false,
			'link_options'   => false,
		);
	}

	/**
	 * Get fields for the module.
	 *
	 * @since 4.3.0
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_fields(): array {
		return array(
			// Chart.
			'chart_type'        => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Chart Type', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'The kind of chart to render.', 'squad-modules-for-divi' ),
					'options'     => array(
						'bar'      => esc_html__( 'Bar', 'squad-modules-for-divi' ),
						'line'     => esc_html__( 'Line', 'squad-modules-for-divi' ),
						'pie'      => esc_html__( 'Pie', 'squad-modules-for-divi' ),
						'doughnut' => esc_html__( 'Doughnut', 'squad-modules-for-divi' ),
					),
					'default'     => 'bar',
					'tab_slug'    => 'general',
					'toggle_slug' => 'chart',
				)
			),
			'title'             => array(
				'label'       => esc_html__( 'Chart Title', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Optional title shown above the chart.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'tab_slug'    => 'general',
				'toggle_slug' => 'chart',
			),

			// Data.
			'labels'            => array(
				'label'       => esc_html__( 'Labels', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'One label per line (the X axis / slice labels).', 'squad-modules-for-divi' ),
				'type'        => 'textarea',
				'default'     => "Q1\nQ2\nQ3\nQ4",
				'tab_slug'    => 'general',
				'toggle_slug' => 'data',
			),
			'datasets'          => array(
				'label'       => esc_html__( 'Datasets', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'One dataset per line, formatted as "Name: v1,v2,v3". Blank cells are skipped.', 'squad-modules-for-divi' ),
				'type'        => 'textarea',
				'default'     => 'Sales: 12,19,3,5',
				'tab_slug'    => 'general',
				'toggle_slug' => 'data',
			),
			'colors'            => array(
				'label'       => esc_html__( 'Custom Colors', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Optional — one color per line. Leave blank to use the default palette.', 'squad-modules-for-divi' ),
				'type'        => 'textarea',
				'default'     => '',
				'tab_slug'    => 'general',
				'toggle_slug' => 'data',
			),

			// Options.
			'show_legend'       => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Show Legend', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Display the chart legend.', 'squad-modules-for-divi' ),
					'default_on_front' => 'on',
					'default'          => 'on',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'options',
				)
			),
			'show_grid'         => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Show Grid', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Display the axis grid lines (bar/line only).', 'squad-modules-for-divi' ),
					'default_on_front' => 'on',
					'default'          => 'on',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'options',
				)
			),
			'begin_at_zero'     => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Begin At Zero', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Force the value axis to start at zero (bar/line only).', 'squad-modules-for-divi' ),
					'default_on_front' => 'on',
					'default'          => 'on',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'options',
				)
			),
			'animate_on_scroll' => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Animate On Scroll', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Defer drawing until the chart scrolls into view.', 'squad-modules-for-divi' ),
					'default_on_front' => 'on',
					'default'          => 'on',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'options',
				)
			),
			'chart_height'      => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Chart Height', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Height of the chart canvas, in pixels.', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '150', 'max' => '600', 'step' => '1' ),
					'default'        => '300',
					'unitless'       => true,
					'tab_slug'       => 'general',
					'toggle_slug'    => 'options',
				)
			),
			'border_width'      => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Border Width', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Dataset border width, in pixels.', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '0', 'max' => '6', 'step' => '1' ),
					'default'        => '2',
					'unitless'       => true,
					'tab_slug'       => 'general',
					'toggle_slug'    => 'options',
				)
			),
		);
	}

	/**
	 * Render module output.
	 *
	 * @since 4.3.0
	 *
	 * @param array<array-key, mixed> $attrs       List of attributes.
	 * @param string                  $content     Content being processed.
	 * @param string                  $render_slug Slug of module being rendered.
	 *
	 * @return string
	 */
	public function render( $attrs, $content, $render_slug ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClassAfterLastUsed
		wp_enqueue_script( 'squad-module-charts' );

		$raw = array(
			'chartType'       => (string) $this->prop( 'chart_type', 'bar' ),
			'labels'          => (string) $this->prop( 'labels', '' ),
			'datasets'        => (string) $this->prop( 'datasets', '' ),
			'colors'          => (string) $this->prop( 'colors', '' ),
			'showLegend'      => (string) $this->prop( 'show_legend', 'on' ),
			'showGrid'        => (string) $this->prop( 'show_grid', 'on' ),
			'beginAtZero'     => (string) $this->prop( 'begin_at_zero', 'on' ),
			'animateOnScroll' => (string) $this->prop( 'animate_on_scroll', 'on' ),
			'chartHeight'     => (int) max( 150, min( 600, (int) $this->prop( 'chart_height', '300' ) ) ),
			'title'           => (string) $this->prop( 'title', '' ),
			'borderWidth'     => (int) max( 0, min( 6, (int) $this->prop( 'border_width', '2' ) ) ),
		);

		return Charts_Helper::build_shell( $raw );
	}
}
