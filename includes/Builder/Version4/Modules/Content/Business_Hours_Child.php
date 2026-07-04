<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Business Hours Day Module Class which extend the Divi Builder Module Class.
 *
 * This class provides day adding functionalities for the parent module in the visual builder.
 *
 * @since   1.0.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Content;

use DiviSquad\Builder\Version4\Abstracts\Module;
use function et_builder_get_text_orientation_options;
use function et_builder_i18n;
use function et_pb_background_options;
use function et_pb_multi_view_options;

/**
 * Business Hours Day Module Class.
 *
 * @since   1.0.0
 * @package DiviSquad
 */
class Business_Hours_Child extends Module {
	/**
	 * Initiate Module.
	 * Set the module name on init.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init(): void {
		$this->name   = esc_html__( 'Business Day', 'squad-modules-for-divi' );
		$this->plural = esc_html__( 'Business Days', 'squad-modules-for-divi' );

		$this->slug             = 'disq_business_day';
		$this->type             = 'child';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.$this->slug";

		$this->child_title_var          = 'day';
		$this->child_title_fallback_var = 'admin_label';

		// Connect with utils.
		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );
		$this->squad_utils->divider->initiate_element();

		// Declare settings modal toggles for the module.
		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'day_n_time_content' => esc_html__( 'Day & Time', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'wrapper'           => esc_html__( 'Wrapper', 'squad-modules-for-divi' ),
					'separator_element' => esc_html__( 'Separator', 'squad-modules-for-divi' ),
					'day_n_time_text'   => array(
						'title'             => esc_html__( 'Day & Time Text', 'squad-modules-for-divi' ),
						'tabbed_subtoggles' => true,
						'sub_toggles'       => array(
							'day'  => array(
								'name' => esc_html__( 'Day', 'squad-modules-for-divi' ),
							),
							'time' => array(
								'name' => esc_html__( 'Time', 'squad-modules-for-divi' ),
							),
						),
					),
				),
			),
		);

		// Declare advanced fields for the module.
		$this->advanced_fields = array(
			'fonts'          => array(
				'day_text'  => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Day', 'squad-modules-for-divi' ),
					array(
						'font_size'   => array(
							'default' => '16px',
						),
						'font_weight' => array(
							'default' => '400',
						),
						'css'         => array(
							'main'  => "$this->main_css_element .day-elements .day-name-text",
							'hover' => "$this->main_css_element .day-elements:hover .day-name-text",
						),
						'tab_slug'    => 'advanced',
						'toggle_slug' => 'day_n_time_text',
						'sub_toggle'  => 'day',
					)
				),
				'time_text' => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Time', 'squad-modules-for-divi' ),
					array(
						'font_size'   => array(
							'default' => '16px',
						),
						'font_weight' => array(
							'default' => '400',
						),
						'css'         => array(
							'main'  => "$this->main_css_element .day-elements .day-element-time",
							'hover' => "$this->main_css_element .day-elements:hover .day-element-time",
						),
						'tab_slug'    => 'advanced',
						'toggle_slug' => 'day_n_time_text',
						'sub_toggle'  => 'time',
					)
				),
			),
			'background'     => divi_squad()->d4_module_helper->selectors_background( $this->main_css_element ),
			'borders'        => array(
				'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
				'wrapper' => array(
					'label_prefix' => et_builder_i18n( 'Wrapper' ),
					'css'          => array(
						'main' => array(
							'border_radii'        => "$this->main_css_element div .day-elements",
							'border_radii_hover'  => "$this->main_css_element div .day-elements:hover",
							'border_styles'       => "$this->main_css_element div .day-elements",
							'border_styles_hover' => "$this->main_css_element div .day-elements:hover",
						),
					),
					'tab_slug'     => 'advanced',
					'toggle_slug'  => 'wrapper',
				),
			),
			'box_shadow'     => array(
				'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
				'wrapper' => array(
					'label'             => esc_html__( 'Wrapper Box Shadow', 'squad-modules-for-divi' ),
					'option_category'   => 'layout',
					'css'               => array(
						'main'  => "$this->main_css_element div .day-elements",
						'hover' => "$this->main_css_element div .day-elements:hover",
					),
					'default_on_fronts' => array(
						'color'    => 'rgba(0,0,0,0.3)',
						'position' => 'outer',
					),
					'tab_slug'          => 'advanced',
					'toggle_slug'       => 'wrapper',
				),
			),
			'margin_padding' => divi_squad()->d4_module_helper->selectors_margin_padding( $this->main_css_element ),
			'max_width'      => divi_squad()->d4_module_helper->selectors_max_width( $this->main_css_element ),
			'height'         => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			'image_icon'     => false,
			'text'           => false,
			'button'         => false,
		);

		// Declare custom css fields for the module.
		$this->custom_css_fields = array(
			'day'     => array(
				'label'    => esc_html__( 'Day', 'squad-modules-for-divi' ),
				'selector' => 'div .day-elements .day-element.day-name-text',
			),
			'time'    => array(
				'label'    => esc_html__( 'Time', 'squad-modules-for-divi' ),
				'selector' => 'div .day-elements .day-element.day-time-text',
			),
			'wrapper' => array(
				'label'    => esc_html__( 'Wrapper', 'squad-modules-for-divi' ),
				'selector' => 'div .day-elements',
			),
		);
	}

	/**
	 * Declare general fields for the module
	 *
	 * @since 1.0.0
	 * @return array[]
	 */
	public function get_fields(): array {
		// Text fields definitions.
		$text_fields    = array(
			'day'               => array(
				'label'           => esc_html__( 'Day', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'The day name will appear in with your day element.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'day_n_time_content',
				'dynamic_content' => 'text',
				'hover'           => 'tabs',
				'mobile_options'  => true,
			),
			'time'              => array(
				'label'           => esc_html__( 'Time', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'The time text will appear in with your time element.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'depends_show_if' => 'off',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'day_n_time_content',
				'dynamic_content' => 'text',
				'hover'           => 'tabs',
				'mobile_options'  => true,
			),
			'dual_time__enable' => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Add Start And Time', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can choose whether or not show the start and end time.', 'squad-modules-for-divi' ),
					'default'          => 'off',
					'default_on_front' => 'off',
					'depends_show_if'  => 'off',
					'affects'          => array(
						'time',
						'start_time',
						'end_time',
						'time_separator',
					),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'day_n_time_content',
				)
			),
			'start_time'        => array(
				'label'           => esc_html__( 'Start Time', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'The start time text will appear in with your time element.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'depends_show_if' => 'on',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'day_n_time_content',
				'dynamic_content' => 'text',
				'hover'           => 'tabs',
				'mobile_options'  => true,
			),
			'end_time'          => array(
				'label'           => esc_html__( 'End Time', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'The end time text will appear in with your time element.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'depends_show_if' => 'on',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'day_n_time_content',
				'dynamic_content' => 'text',
				'hover'           => 'tabs',
				'mobile_options'  => true,
			),
			'time_separator'    => array(
				'label'           => esc_html__( 'Time Separator', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'Here you can set the separator between the start time and end time.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'depends_show_if' => 'on',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'day_n_time_content',
				'dynamic_content' => 'text',
				'hover'           => 'tabs',
				'mobile_options'  => true,
			),
			'off_day__enable'   => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Mark as Off Day', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can choose whether or not mark as a off day.', 'squad-modules-for-divi' ),
					'default'          => 'off',
					'default_on_front' => 'off',
					'affects'          => array(
						'off_day_label',
						'dual_time__enable',
					),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'day_n_time_content',
				)
			),
			'off_day_label'     => array(
				'label'           => esc_html__( 'Off Day Label', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'The title of your list item will appear in with your list item.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'depends_show_if' => 'on',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'day_n_time_content',
				'dynamic_content' => 'text',
				'hover'           => 'tabs',
				'mobile_options'  => true,
			),
		);
		$divider_fields = $this->squad_utils->divider->get_fields(
			array(
				'label'            => esc_html__( 'Show Text Separator', 'squad-modules-for-divi' ),
				'description'      => esc_html__( 'Here you can choose whether or not show the separator for day text.', 'squad-modules-for-divi' ),
				'type'             => 'skip',
				'default'          => 'on',
				'default_on_front' => 'on',
				'tab_slug'         => 'general',
				'toggle_slug'      => 'day_n_time_content',
				'toggle_slug_adv'  => 'separator_element',
			)
		);

		// Item wrapper fields definitions.
		$wrapper_background_fields = $this->squad_utils->field_definitions->add_background_field(
			array(
				'label'       => esc_html__( 'Wrapper Background', 'squad-modules-for-divi' ),
				'base_name'   => 'wrapper_background',
				'context'     => 'wrapper_background_color',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'wrapper',
			)
		);
		$wrapper_fields            = array(
			'wrapper_text_orientation' => divi_squad()->d4_module_helper->add_alignment_field(
				esc_html__( 'Text Alignment', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'This controls how your text is aligned within the module.', 'squad-modules-for-divi' ),
					'type'        => 'text_align',
					'options'     => et_builder_get_text_orientation_options(
						array( 'justified' ),
						array( 'justify' => 'Justified' )
					),
					'default'     => 'left',
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'wrapper',
				)
			),
			'wrapper_margin'           => divi_squad()->d4_module_helper->add_margin_padding_field(
				esc_html__( 'Wrapper Margin', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__(
						'Here you can define a custom margin size for the wrapper.',
						'squad-modules-for-divi'
					),
					'type'        => 'custom_margin',
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'wrapper',
				)
			),
			'wrapper_padding'          => divi_squad()->d4_module_helper->add_margin_padding_field(
				esc_html__( 'Wrapper Padding', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__(
						'Here you can define a custom padding size.',
						'squad-modules-for-divi'
					),
					'type'        => 'custom_padding',
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'wrapper',
				)
			),
		);

		// clean unnecessary fields.
		if ( array_key_exists( 'divider_position', $divider_fields ) ) {
			unset( $divider_fields['divider_position'] );
		}

		// update some fields.
		$divider_fields['divider_color']['default']           = '';
		$divider_fields['divider_color']['default_on_front']  = '';
		$divider_fields['divider_style']['default']           = '';
		$divider_fields['divider_style']['default_on_front']  = '';
		$divider_fields['divider_weight']['default']          = '';
		$divider_fields['divider_weight']['default_on_front'] = '';

		return array_merge(
			$text_fields,
			$divider_fields,
			$wrapper_background_fields,
			$wrapper_fields,
			divi_squad()->d4_module_helper->get_general_fields()
		);
	}

	/**
	 * Get CSS fields transition.
	 *
	 * Add form field options group and background image on the field list.
	 *
	 * @since 1.0.0
	 */
	public function get_transition_fields_css_props(): array {
		$fields = parent::get_transition_fields_css_props();

		// Item wrapper styles.
		$fields['wrapper_background_color'] = array( 'background' => "$this->main_css_element div .day-elements" );
		$fields['wrapper_margin']           = array( 'margin' => "$this->main_css_element div .day-elements" );
		$fields['wrapper_padding']          = array( 'padding' => "$this->main_css_element div .day-elements" );
		divi_squad()->d4_module_helper->fix_border_transition( $fields, 'wrapper', "$this->main_css_element div .day-elements" );
		divi_squad()->d4_module_helper->fix_box_shadow_transition( $fields, 'wrapper', "$this->main_css_element div .day-elements" );

		// divider styles.
		$fields['divider_color']  = array( 'border-top-color' => "$this->main_css_element div .day-elements .day-element.day-element-divider:before" );
		$fields['divider_weight'] = array(
			'border-top-width' => "$this->main_css_element div .day-elements .day-element.day-element-divider:before",
			'height'           => "$this->main_css_element div .day-elements .day-element.day-element-divider:before",
		);

		// Default styles.
		$fields['background_layout'] = array( 'color' => $this->main_css_element );

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
		$multi_view = et_pb_multi_view_options( $this );
		$divider    = null;

		if ( 'on' === $this->prop( 'show_divider', 'off' ) ) {
			$divider = '<span class="day-element day-element-divider"></span>';
		}

		$day_name_text = $multi_view->render_element(
			array(
				'tag'     => 'span',
				'content' => '{{day}}',
				'attrs'   => array(
					'class' => 'day-element day-name-text',
				),
			)
		);

		$this->squad_generate_all_styles( $attrs );
		$this->squad_generate_divider_styles();

		return sprintf(
			'<div class="day-elements et_pb_with_background">%1$s%2$s%3$s</div>',
			wp_kses_post( $day_name_text ?? '' ),
			wp_kses_post( $divider ),
			wp_kses_post( $this->squad_render_day_time_text() ?? '' )
		);
	}

	/**
	 * Process styles for module output.
	 *
	 * @param array $attrs List of unprocessed attributes.
	 *
	 * @return void
	 */
	private function squad_generate_all_styles( array $attrs ): void {
		// Fixed: the custom background doesn't work at frontend.
		$this->props = array_merge( $attrs, $this->props );

		$wrapper = 'wrapper_background';

		// wrapper style
		// wrapper background with default, responsive, hover.
		et_pb_background_options()->get_background_style(
			array(
				'base_prop_name'         => $wrapper,
				'props'                  => $this->props,
				'selector'               => "$this->main_css_element .day-elements",
				'selector_hover'         => "$this->main_css_element .day-elements:hover",
				'selector_sticky'        => "$this->main_css_element .day-elements",
				'function_name'          => $this->slug,
				'important'              => ' !important',
				'use_background_video'   => false,
				'use_background_pattern' => false,
				'use_background_mask'    => false,
				'prop_name_aliases'      => array(
					"use_{$wrapper}_color_gradient" => "{$wrapper}_use_color_gradient",
					$wrapper                        => "{$wrapper}_color",
				),
			)
		);
		// text align with default, responsive, hover.
		$this->generate_styles(
			array(
				'base_attr_name' => 'wrapper_text_orientation',
				'selector'       => "$this->main_css_element .day-elements",
				'hover_selector' => "$this->main_css_element .day-elements:hover",
				'css_property'   => 'text-align',
				'render_slug'    => $this->slug,
				'type'           => 'align',
			)
		);
		// wrapper margin with default, responsive, hover.
		$this->squad_utils->field_css_generations->generate_margin_padding_styles(
			array(
				'field'          => 'wrapper_margin',
				'selector'       => "$this->main_css_element .day-elements",
				'hover_selector' => "$this->main_css_element .day-elements:hover",
				'css_property'   => 'margin',
				'type'           => 'margin',
			)
		);
		// wrapper padding with default, responsive, hover.
		$this->squad_utils->field_css_generations->generate_margin_padding_styles(
			array(
				'field'          => 'wrapper_padding',
				'selector'       => "$this->main_css_element .day-elements",
				'hover_selector' => "$this->main_css_element .day-elements:hover",
				'css_property'   => 'padding',
				'type'           => 'padding',
			)
		);
	}

	/**
	 * Generate styles for divider
	 *
	 * @return void
	 */
	private function squad_generate_divider_styles(): void {
		$this->squad_utils->field_css_generations->generate_divider_styles(
			array(
				'selector'  => "$this->main_css_element div .day-elements .day-element.day-element-divider:before",
				'important' => true,
			)
		);
	}

	/**
	 * Render day name
	 *
	 * @return string
	 */
	private function squad_render_day_time_text(): string {
		$multi_view = et_pb_multi_view_options( $this );

		// Show start time and end time when enabled it.
		if ( 'on' === $this->prop( 'dual_time__enable', 'off' ) ) {
			$start_time     = $multi_view->render_element( array( 'content' => '{{start_time}}' ) );
			$end_time       = $multi_view->render_element( array( 'content' => '{{end_time}}' ) );
			$time_separator = $multi_view->render_element( array( 'content' => '{{time_separator}}' ) );

			return sprintf(
				'<span class="day-element day-element-time">%1$s%2$s%3$s</span>',
				wp_kses_post( $start_time ?? '' ),
				wp_kses_post( $time_separator ?? '' ),
				wp_kses_post( $end_time ?? '' )
			);
		}

		// Show off day label when enabled it.
		if ( 'on' === $this->prop( 'off_day__enable', 'off' ) ) {
			$off_day_label = $multi_view->render_element( array( 'content' => '{{off_day_label}}' ) );

			return sprintf(
				'<span class="day-element day-element-time">%1$s</span>',
				wp_kses_post( $off_day_label )
			);
		}

		return $multi_view->render_element(
			array(
				'content' => '{{time}}',
				'attrs'   => array(
					'class' => 'day-element day-element-time',
				),
			)
		);
	}
}
