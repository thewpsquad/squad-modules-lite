<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Reading Progress Module (Divi 4 shortcode).
 *
 * Renders a fixed scroll-progress indicator — a top/bottom bar or a corner
 * circular ring — via the shared `Reading_Progress_Helper`. The progress is
 * tracked client-side by `reading-progress.ts`.
 *
 * @since   4.3.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Creative;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use DiviSquad\Builder\Shared\Modules\Creative\Reading_Progress\Reading_Progress_Helper;
use DiviSquad\Builder\Version4\Abstracts\Module;
use function esc_html__;
use function max;
use function min;
use function wp_enqueue_script;

/**
 * Reading Progress Module class.
 *
 * @since 4.3.0
 */
class Reading_Progress extends Module {

	/**
	 * Initiate Module.
	 *
	 * @since 4.3.0
	 *
	 * @return void
	 */
	public function init(): void {
		$this->name      = esc_html__( 'Reading Progress', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Reading Progress', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'reading-progress.svg' );

		$this->slug             = 'disq_reading_progress';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.$this->slug";

		$this->child_title_var          = 'title';
		$this->child_title_fallback_var = 'admin_label';

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		$this->settings_modal_toggles = array(
			'general' => array(
				'toggles' => array(
					'indicator' => esc_html__( 'Indicator', 'squad-modules-for-divi' ),
					'style'     => esc_html__( 'Style', 'squad-modules-for-divi' ),
					'options'   => esc_html__( 'Options', 'squad-modules-for-divi' ),
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
			// Indicator.
			'bar_style'        => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Indicator Style', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Choose a top/bottom progress bar or a corner circular ring.', 'squad-modules-for-divi' ),
					'options'     => array(
						'bar'      => esc_html__( 'Bar', 'squad-modules-for-divi' ),
						'circular' => esc_html__( 'Circular', 'squad-modules-for-divi' ),
					),
					'default'     => 'bar',
					'tab_slug'    => 'general',
					'toggle_slug' => 'indicator',
				)
			),
			'position'         => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Position', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Where the indicator is fixed. Bars use top/bottom; circular rings use a corner.', 'squad-modules-for-divi' ),
					'options'     => array(
						'top'          => esc_html__( 'Top (bar)', 'squad-modules-for-divi' ),
						'bottom'       => esc_html__( 'Bottom (bar)', 'squad-modules-for-divi' ),
						'bottom-right' => esc_html__( 'Bottom Right (circular)', 'squad-modules-for-divi' ),
						'bottom-left'  => esc_html__( 'Bottom Left (circular)', 'squad-modules-for-divi' ),
						'top-right'    => esc_html__( 'Top Right (circular)', 'squad-modules-for-divi' ),
						'top-left'     => esc_html__( 'Top Left (circular)', 'squad-modules-for-divi' ),
					),
					'default'     => 'top',
					'tab_slug'    => 'general',
					'toggle_slug' => 'indicator',
				)
			),
			'target_selector'  => array(
				'label'       => esc_html__( 'Target Selector', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'CSS selector of the content area to track. Leave empty to track the whole page.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'tab_slug'    => 'general',
				'toggle_slug' => 'indicator',
			),

			// Style.
			'bar_color'        => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Bar Color', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Fill color of the progress indicator. Leave empty to use the accent color.', 'squad-modules-for-divi' ),
					'default'     => '',
					'tab_slug'    => 'general',
					'toggle_slug' => 'style',
				)
			),
			'use_gradient'     => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Use Gradient', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Fill the bar with a gradient (bar style only).', 'squad-modules-for-divi' ),
					'default_on_front' => 'off',
					'default'          => 'off',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'style',
				)
			),
			'gradient_end'     => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Gradient End Color', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Second color for the gradient fill.', 'squad-modules-for-divi' ),
					'default'     => '',
					'tab_slug'    => 'general',
					'toggle_slug' => 'style',
					'show_if'     => array( 'use_gradient' => 'on' ),
				)
			),
			'bar_height'       => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Bar Thickness', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Thickness of the progress bar, in pixels (bar style only).', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '2', 'max' => '12', 'step' => '1' ),
					'default'        => '4',
					'unitless'       => true,
					'tab_slug'       => 'general',
					'toggle_slug'    => 'style',
				)
			),

			// Options.
			'hide_on_complete' => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Hide On Complete', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Fade the indicator out once the page is fully read.', 'squad-modules-for-divi' ),
					'default_on_front' => 'off',
					'default'          => 'off',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'options',
				)
			),
			'show_percent'     => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Show Percent', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Show the percentage inside the circular ring (circular style only).', 'squad-modules-for-divi' ),
					'default_on_front' => 'off',
					'default'          => 'off',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'options',
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
		wp_enqueue_script( 'squad-module-reading-progress' );

		$raw = array(
			'barStyle'       => (string) $this->prop( 'bar_style', 'bar' ),
			'position'       => (string) $this->prop( 'position', 'top' ),
			'targetSelector' => (string) $this->prop( 'target_selector', '' ),
			'barColor'       => (string) $this->prop( 'bar_color', '' ),
			'useGradient'    => (string) $this->prop( 'use_gradient', 'off' ),
			'gradientEnd'    => (string) $this->prop( 'gradient_end', '' ),
			'barHeight'      => (int) max( 2, min( 12, (int) $this->prop( 'bar_height', '4' ) ) ),
			'hideOnComplete' => (string) $this->prop( 'hide_on_complete', 'off' ),
			'showPercent'    => (string) $this->prop( 'show_percent', 'off' ),
		);

		return Reading_Progress_Helper::build_shell( $raw );
	}
}
