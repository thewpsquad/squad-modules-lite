<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Skill Bar Module Class.
 *
 * Parent module holding an optional title and animated child skill bars.
 *
 * @since   4.0.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Content;

use DiviSquad\Builder\Version4\Abstracts\Module;
use function esc_html;
use function esc_html__;
use function in_array;
use function wp_enqueue_script;

/**
 * Skill Bar Module Class.
 *
 * @since 4.0.0
 */
class Skill_Bar extends Module {

	public function init(): void {
		$this->name      = esc_html__( 'Skill Bar', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Skill Bars', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'skill-bar.svg' );

		$this->slug             = 'disq_skill_bar';
		$this->child_slug       = 'disq_skill_bar_item';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.{$this->slug}";

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'title_content' => esc_html__( 'Title', 'squad-modules-for-divi' ),
					'layout'        => esc_html__( 'Layout', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'title' => esc_html__( 'Title Text', 'squad-modules-for-divi' ),
				),
			),
		);

		$this->advanced_fields = array(
			'background'     => divi_squad()->d4_module_helper->selectors_background( $this->main_css_element ),
			'borders'        => array(
				'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			),
			'box_shadow'     => array(
				'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			),
			'margin_padding' => divi_squad()->d4_module_helper->selectors_margin_padding( $this->main_css_element ),
			'fonts'          => array(
				'title' => array(
					'label'        => esc_html__( 'Title', 'squad-modules-for-divi' ),
					'css'          => array( 'main' => "{$this->main_css_element} .squad-skill-bar__title" ),
					'tab_slug'     => 'advanced',
					'toggle_slug'  => 'title',
					'header_level' => array( 'default' => 'h3' ),
					'font_size'    => array( 'default' => '30px' ),
					'line_height'  => array( 'default' => '1em' ),
				),
			),
			'image_icon'     => false,
			'text'           => false,
			'button'         => false,
			'filters'        => false,
		);
	}

	public function get_fields(): array {
		return array(
			'title'         => array(
				'label'       => esc_html__( 'Title', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Optional heading shown above the bars.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'tab_slug'    => 'general',
				'toggle_slug' => 'title_content',
			),
			'title_level'   => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Title Heading Level', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'HTML heading tag for the title.', 'squad-modules-for-divi' ),
					'options'     => array(
						'h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'h5' => 'H5', 'h6' => 'H6',
					),
					'default'     => 'h3',
					'tab_slug'    => 'general',
					'toggle_slug' => 'title_content',
				)
			),
			'bar_spacing'   => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Spacing Between Bars', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Vertical gap between bars.', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '0', 'max' => '100', 'step' => '1' ),
					'default'        => '20px',
					'mobile_options' => true,
					'tab_slug'       => 'general',
					'toggle_slug'    => 'layout',
				)
			),
			'title_spacing' => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Title Spacing Bottom', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Gap below the title.', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '0', 'max' => '200', 'step' => '1' ),
					'default'        => '10px',
					'mobile_options' => true,
					'tab_slug'       => 'general',
					'toggle_slug'    => 'layout',
				)
			),
		);
	}

	public function render( $attrs, $content, $render_slug ): string {
		wp_enqueue_script( 'squad-module-skill-bar' );

		$this->apply_spacing_css( $render_slug );

		$title       = $this->prop( 'title', '' );
		$title_html  = '';
		if ( '' !== $title ) {
			$level = $this->prop( 'title_level', 'h3' );
			$level = in_array( $level, array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ), true ) ? $level : 'h3';
			$title_html = sprintf( '<%1$s class="squad-skill-bar__title">%2$s</%1$s>', $level, esc_html( $title ) );
		}

		return sprintf(
			'%1$s<div class="squad-skill-bar">%2$s</div>',
			$title_html,
			$content
		);
	}

	public function apply_spacing_css( string $render_slug ): void {
		$this->squad_utils->field_css_generations->generate_additional_styles(
			array(
				'field'        => 'bar_spacing',
				'selector'     => '%%order_class%% .disq_skill_bar_item',
				'css_property' => 'margin-bottom',
				'type'         => 'default',
				'render_slug'  => $render_slug,
				'important'    => true,
			)
		);
		$this->squad_utils->field_css_generations->generate_additional_styles(
			array(
				'field'        => 'title_spacing',
				'selector'     => '%%order_class%% .squad-skill-bar__title',
				'css_property' => 'margin-bottom',
				'type'         => 'default',
				'render_slug'  => $render_slug,
			)
		);
	}
}
