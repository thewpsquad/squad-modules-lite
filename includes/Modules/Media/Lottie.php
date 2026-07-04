<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Lottie Image Module Class which extend the Divi Builder Module Class.
 *
 * This class provides item adding functionalities for Lottie Image in the visual builder.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   1.0.0
 */

namespace DiviSquad\Modules\Media;

use DiviSquad\Base\DiviBuilder\Module;
use DiviSquad\Base\DiviBuilder\Utils;
use DiviSquad\Utils\Helper;
use ET_Builder_Module_Helper_MultiViewOptions;
use function esc_attr__;
use function esc_html__;
use function et_pb_multi_view_options;
use function wp_enqueue_script;
use function wp_json_encode;
use function wp_kses_post;

/**
 * Lottie Image Module Class.
 *
 * @package DiviSquad
 * @since   1.0.0
 */
class Lottie extends Module {
	/**
	 * Initiate Module.
	 * Set the module name on init.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function init(): void {
		$this->name      = esc_html__( 'Lottie Image', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Lottie Images', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'lottie.svg' );

		$this->slug             = 'disq_lottie';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.$this->slug";

		$this->child_title_var          = 'name';
		$this->child_title_fallback_var = 'admin_label';

		// Declare settings modal toggles for the module.
		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'lottie_image'     => esc_html__( 'Lottie', 'squad-modules-for-divi' ),
					'lottie_animation' => esc_html__( 'Animation Options', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'lottie_image' => esc_html__( 'Lottie', 'squad-modules-for-divi' ),
				),
			),
		);

		// Declare advanced fields for the module.
		$this->advanced_fields = array(
			'background'     => Utils::selectors_background( $this->main_css_element ),
			'borders'        => array( 'default' => Utils::selectors_default( $this->main_css_element ) ),
			'box_shadow'     => array( 'default' => Utils::selectors_default( $this->main_css_element ) ),
			'margin_padding' => Utils::selectors_margin_padding( $this->main_css_element ),
			'max_width'      => Utils::selectors_max_width( $this->main_css_element ),
			'height'         => Utils::selectors_default( $this->main_css_element ),
			'image_icon'     => false,
			'filters'        => false,
			'fonts'          => false,
			'text'           => false,
			'button'         => false,
		);

		// Declare custom css fields for the module.
		$this->custom_css_fields = array(
			'lottie-wrapper'   => array(
				'label'    => esc_html__( 'Lottie Wrapper', 'squad-modules-for-divi' ),
				'selector' => '.squad-lottie-wrapper',
			),
			'lottie-container' => array(
				'label'    => esc_html__( 'Lottie Container', 'squad-modules-for-divi' ),
				'selector' => '.lottie-player-container',
			),
		);
	}

	/**
	 * Declare general fields for the module
	 *
	 * @return array[]
	 * @since 1.0.0
	 */
	public function get_fields(): array {
		// All field types.
		$lottie_fields            = array(
			'lottie_src_type'   => Utils::add_select_box_field(
				esc_html__( 'Source Type', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Choose a source type to display from your lottie.', 'squad-modules-for-divi' ),
					'options'          => array(
						'remote' => esc_html__( 'External URL', 'squad-modules-for-divi' ),
						'local'  => esc_html__( 'Upload', 'squad-modules-for-divi' ),
					),
					'default_on_front' => 'remote',
					'affects'          => array(
						'lottie_src_upload',
						'lottie_src_remote',
					),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'lottie_image',
				)
			),
			'lottie_src_remote' => array(
				'label'           => et_builder_i18n( 'External URL' ),
				'description'     => esc_html__( 'The title of your list item will appear in bold below your list item image.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'depends_show_if' => 'remote',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'lottie_image',
				'dynamic_content' => 'url',
			),
			'lottie_src_upload' => array(
				'label'              => esc_html__( 'Upload a Lottie json', 'squad-modules-for-divi' ),
				'type'               => 'upload',
				'option_category'    => 'basic_option',
				'description'        => esc_html__( 'A json file is chosen for lottie.', 'squad-modules-for-divi' ),
				'upload_button_text' => esc_attr__( 'Upload a lottie json file', 'squad-modules-for-divi' ),
				'choose_text'        => esc_attr__( 'Choose a lottie json file', 'squad-modules-for-divi' ),
				'update_text'        => esc_attr__( 'Set As a lottie json', 'squad-modules-for-divi' ),
				'data_type'          => 'json',
				'depends_show_if'    => 'local',
				'tab_slug'           => 'general',
				'toggle_slug'        => 'lottie_image',
			),
		);
		$lottie_animation_fields  = array(
			'lottie_trigger_method'  => Utils::add_select_box_field(
				esc_html__( 'Animation Interaction', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can choose interactivity with your Lottie animation.', 'squad-modules-for-divi' ),
					'options'          => array(
						'hover'        => esc_html__( 'Play on hover/mouse over', 'squad-modules-for-divi' ),
						'click'        => esc_html__( 'Play on click', 'squad-modules-for-divi' ),
						'scroll'       => esc_html__( 'Play on scroll', 'squad-modules-for-divi' ),
						'play-on-show' => esc_html__( 'Play when container is visible', 'squad-modules-for-divi' ),
						'freeze-click' => esc_html__( 'Freeze on click', 'squad-modules-for-divi' ),
						'none'         => esc_html__( 'None', 'squad-modules-for-divi' ),
					),
					'default_on_front' => 'freeze-click',
					'depends_show_if'  => 'lottie',
					'affects'          => array(
						'lottie_mouseout_action',
						'lottie_click_action',
						'lottie_scroll',
						'lottie_play_on_hover',
						'lottie_loop',
						'lottie_delay',
					),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'lottie_animation',
				)
			),
			'lottie_mouseout_action' => Utils::add_select_box_field(
				esc_html__( 'On Mouseout Action', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can choose an action when mouse out with your Lottie animation.', 'squad-modules-for-divi' ),
					'type'             => 'select',
					'option_category'  => 'layout',
					'options'          => array(
						'no_action' => esc_html__( 'No action', 'squad-modules-for-divi' ),
						'reverse'   => esc_html__( 'Reverse', 'squad-modules-for-divi' ),
					),
					'default_on_front' => 'no_action',
					'depends_show_if'  => 'hover',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'lottie_animation',
				)
			),
			'lottie_click_action'    => Utils::add_select_box_field(
				esc_html__( 'On Click Action', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can choose an action when click with your Lottie animation.', 'squad-modules-for-divi' ),
					'options'          => array(
						'no_action' => esc_html__( 'No action', 'squad-modules-for-divi' ),
						'lock'      => esc_html__( 'Lock animation', 'squad-modules-for-divi' ),
					),
					'default_on_front' => 'no_action',
					'depends_show_if'  => 'hover',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'lottie_animation',
				)
			),
			'lottie_scroll'          => Utils::add_select_box_field(
				esc_html__( 'Relative To', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can choose a relation when scroll event with your Lottie animation.', 'squad-modules-for-divi' ),
					'options'          => array(
						'page' => esc_html__( 'Entire page', 'squad-modules-for-divi' ),
						'row'  => esc_html__( 'Within this section/row', 'squad-modules-for-divi' ),
					),
					'default_on_front' => 'row',
					'depends_show_if'  => 'scroll',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'lottie_animation',
				)
			),
			'lottie_play_on_hover'   => Utils::add_yes_no_field(
				esc_html__( 'Play On Hover', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can choose whether or not your Lottie will animate on hover.', 'squad-modules-for-divi' ),
					'default_on_front' => 'off',
					'depends_show_if'  => 'freeze-click',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'lottie_animation',
				)
			),
			'lottie_loop'            => Utils::add_yes_no_field(
				esc_html__( 'Loop', 'squad-modules-for-divi' ),
				array(
					'description'         => esc_html__( 'Here you can choose whether or not your Lottie will animate in loop.', 'squad-modules-for-divi' ),
					'default_on_front'    => 'off',
					'depends_show_if_not' => array( 'scroll' ),
					'affects'             => array(
						'lottie_loop_no_times',
					),
					'tab_slug'            => 'general',
					'toggle_slug'         => 'lottie_animation',
				)
			),
			'lottie_loop_no_times'   => Utils::add_range_field(
				esc_html__( 'Amount Of Loops', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'This option is only available if Yes is selected for Loop. Enter the number of times you wish to have the animation loop before stopping.', 'squad-modules-for-divi' ),
					'range_settings'   => array(
						'min_limit' => '0',
						'min'       => '0',
						'max_limit' => '100',
						'max'       => '100',
						'step'      => '1',
					),
					'validate_unit'    => false,
					'unitless'         => true,
					'default_on_front' => '0',
					'depends_show_if'  => 'on',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'lottie_animation',
				),
				array(
					'use_hover'      => false,
					'mobile_options' => false,
				)
			),
			'lottie_delay'           => Utils::add_range_field(
				esc_html__( 'Delay', 'squad-modules-for-divi' ),
				array(
					'description'         => esc_html__( 'Delay the lottie animation (in ms).', 'squad-modules-for-divi' ),
					'validate_unit'       => false,
					'unitless'            => true,
					'range_settings'      => array(
						'min_limit' => '0',
						'min'       => '0',
						'max_limit' => '8000',
						'max'       => '8000',
						'step'      => '1',
					),
					'default_on_front'    => '0',
					'depends_show_if_not' => array( 'scroll' ),
					'tab_slug'            => 'general',
					'toggle_slug'         => 'lottie_animation',
				),
				array(
					'use_hover'      => false,
					'mobile_options' => false,
				)
			),
			'lottie_speed'           => Utils::add_range_field(
				esc_html__( 'Animation Speed', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'The speed of the animation.', 'squad-modules-for-divi' ),
					'validate_unit'    => false,
					'unitless'         => true,
					'range_settings'   => array(
						'min_limit' => '0.1',
						'min'       => '0.1',
						'max_limit' => '2.5',
						'max'       => '2.5',
						'step'      => '0.1',
					),
					'default'          => '1',
					'default_on_front' => '1',
					'depends_show_if'  => 'lottie',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'lottie_animation',
				),
				array(
					'use_hover'      => false,
					'mobile_options' => false,
				)
			),
			'lottie_mode'            => Utils::add_select_box_field(
				esc_html__( 'Play Mode', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can choose play mode for your Lottie animation.', 'squad-modules-for-divi' ),
					'options'          => array(
						'normal' => esc_html__( 'Normal', 'squad-modules-for-divi' ),
						'bounce' => esc_html__( 'Reverse on complete', 'squad-modules-for-divi' ),
					),
					'default_on_front' => 'normal',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'lottie_animation',
				)
			),
			'lottie_direction'       => Utils::add_select_box_field(
				esc_html__( 'Direction', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can choose play direction for your Lottie animation.', 'squad-modules-for-divi' ),
					'options'          => array(
						'1'  => esc_html__( 'Normal', 'squad-modules-for-divi' ),
						'-1' => esc_html__( 'Reverse', 'squad-modules-for-divi' ),
					),
					'default_on_front' => '1',
					'depends_show_if'  => 'lottie',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'lottie_animation',
				)
			),
			'lottie_renderer'        => Utils::add_select_box_field(
				esc_html__( 'Render', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can choose renderer for your Lottie animation.', 'squad-modules-for-divi' ),
					'options'          => array(
						'svg'    => esc_html__( 'SVG', 'squad-modules-for-divi' ),
						'canvas' => esc_html__( 'Canvas', 'squad-modules-for-divi' ),
					),
					'default_on_front' => 'svg',
					'depends_show_if'  => 'lottie',
					'affects'          => array(
						'lottie_mode',
					),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'lottie_animation',
				)
			),
		);
		$lottie_associated_fields = array(
			'lottie_color'  => Utils::add_color_field(
				esc_html__( 'Lottie Color', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Here you can define a custom color for lottie image.', 'squad-modules-for-divi' ),
					'depends_show_if' => 'lottie',
					'tab_slug'        => 'advanced',
					'toggle_slug'     => 'lottie_image',
				)
			),
			'lottie_width'  => Utils::add_range_field(
				esc_html__( 'Lottie Width', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Here you can choose lottie width.', 'squad-modules-for-divi' ),
					'range_settings'  => array(
						'min_limit' => '1',
						'min'       => '1',
						'max_limit' => '200',
						'max'       => '200',
						'step'      => '1',
					),
					'default_unit'    => 'px',
					'hover'           => false,
					'depends_show_if' => 'lottie',
					'tab_slug'        => 'advanced',
					'toggle_slug'     => 'lottie_image',
				)
			),
			'lottie_height' => Utils::add_range_field(
				esc_html__( 'Lottie Height', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Here you can choose lottie height.', 'squad-modules-for-divi' ),
					'range_settings'  => array(
						'min_limit' => '1',
						'min'       => '1',
						'max_limit' => '200',
						'max'       => '200',
						'step'      => '1',
					),
					'default_unit'    => 'px',
					'depends_show_if' => 'lottie',
					'hover'           => false,
					'tab_slug'        => 'advanced',
					'toggle_slug'     => 'lottie_image',
				)
			),
		);

		return array_merge(
			$lottie_fields,
			$lottie_animation_fields,
			$lottie_associated_fields
		);
	}

	/**
	 * Get CSS fields transition.
	 *
	 * Add form field options group and background image on the field list.
	 *
	 * @since 1.0.0
	 */
	public function get_transition_fields_css_props() {
		$fields                  = parent::get_transition_fields_css_props();
		$fields['lottie_color']  = array( 'fill' => "$this->main_css_element .squad-lottie-wrapper .lottie-image svg path" );
		$fields['lottie_width']  = array( 'width' => "$this->main_css_element .squad-lottie-wrapper .lottie-image" );
		$fields['lottie_height'] = array( 'height' => "$this->main_css_element .squad-lottie-wrapper .lottie-image" );

		return $fields;
	}

	/**
	 * Renders the module output.
	 *
	 * @param array  $attrs       List of attributes.
	 * @param string $content     Content being processed.
	 * @param string $render_slug Slug of module that is used for rendering output.
	 *
	 * @return string
	 */
	public function render( $attrs, $content, $render_slug ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClassAfterLastUsed
		wp_enqueue_script( 'squad-module-lottie' );

		return sprintf(
			'<div class="squad-lottie-wrapper">%1$s</div>',
			wp_kses_post( $this->squad_render_lottie() )
		);
	}

	/**
	 * Render item lottie image
	 *
	 * @return string
	 */
	private function squad_render_lottie(): string {
		if ( '' === $this->props['lottie_src_type'] || ( '' === $this->props['lottie_src_upload'] && '' === $this->props['lottie_src_remote'] ) ) {
			return '';
		}

		$multi_view = et_pb_multi_view_options( $this );

		$lottie_image_classes = array( 'squad-lottie-player', 'lottie-player-container' );

		$lottie_type     = $this->props['lottie_src_type'] ?? '';
		$lottie_src_prop = 'local' === $lottie_type ? '{{lottie_src_upload}}' : '{{lottie_src_remote}}';

		// Set background color for Icon.
		$this->generate_styles(
			array(
				'base_attr_name' => 'lottie_color',
				'selector'       => "$this->main_css_element .squad-lottie-wrapper .squad-lottie-player svg path",
				'css_property'   => 'fill',
				'render_slug'    => $this->slug,
				'type'           => 'color',
				'important'      => true,
			)
		);
		// Set width for Image.
		$this->generate_styles(
			array(
				'base_attr_name' => 'lottie_width',
				'selector'       => "$this->main_css_element .squad-lottie-wrapper .squad-lottie-player",
				'css_property'   => 'width',
				'render_slug'    => $this->slug,
				'type'           => 'range',
				'important'      => true,
			)
		);
		// Set height for Image.
		$this->generate_styles(
			array(
				'base_attr_name' => 'lottie_height',
				'selector'       => "$this->main_css_element .squad-lottie-wrapper .squad-lottie-player",
				'css_property'   => 'height',
				'render_slug'    => $this->slug,
				'type'           => 'range',
				'important'      => true,
			)
		);

		$module_references = array(
			'lottie_trigger_method'  => $this->prop( 'divider_icon_lottie_trigger_method', 'freeze-click' ),
			'lottie_mouseout_action' => $this->prop( 'divider_icon_lottie_mouseout_action', 'no_action' ),
			'lottie_click_action'    => $this->prop( 'divider_icon_lottie_click_action', 'no_action' ),
			'lottie_scroll'          => $this->prop( 'divider_icon_lottie_scroll', 'row' ),
			'lottie_play_on_hover'   => $this->prop( 'divider_icon_lottie_play_on_hover', 'off' ),
			'lottie_loop'            => $this->prop( 'divider_icon_lottie_loop', 'off' ),
			'lottie_loop_no_times'   => $this->prop( 'divider_icon_lottie_loop_no_times', '0' ),
			'lottie_delay'           => $this->prop( 'divider_icon_lottie_delay', '0' ),
			'lottie_speed'           => $this->prop( 'divider_icon_lottie_speed', '1' ),
			'lottie_mode'            => $this->prop( 'divider_icon_lottie_mode', 'normal' ),
			'lottie_direction'       => $this->prop( 'divider_icon_lottie_direction', '1' ),
			'lottie_renderer'        => $this->prop( 'divider_icon_lottie_renderer', 'svg' ),
		);

		return $multi_view->render_element(
			array(
				'tag'   => 'div',
				'attrs' => array(
					'style'        => 'margin: 0px auto; outline: none; overflow: hidden;',
					'class'        => implode( ' ', $lottie_image_classes ),
					'data-src'     => $lottie_src_prop,
					'data-options' => wp_json_encode(
						array(
							'fieldPrefix'     => '',
							'moduleReference' => $module_references,
						)
					),
				),
			)
		);
	}
}
