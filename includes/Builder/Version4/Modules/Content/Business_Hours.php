<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Business Hours Module Class which extend the Divi Builder Module Class.
 *
 * This class provides listed working hours day adding functionalities in the visual builder.
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
 * Business Hours Module Class.
 *
 * @since   1.0.0
 * @package DiviSquad
 */
class Business_Hours extends Module {
	/**
	 * Initiate Module.
	 * Set the module name on init.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init(): void {
		$this->name      = esc_html__( 'Business Hours', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Business Hours', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'business-hours.svg' );

		$this->slug             = 'disq_business_hours';
		$this->child_slug       = 'disq_business_day';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.$this->slug";

		// Connect with utils.
		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );
		$this->squad_utils->divider->initiate_element();

		// Declare settings modal toggles for the module.
		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'title_content'    => esc_html__( 'Title', 'squad-modules-for-divi' ),
					'general_settings' => esc_html__( 'General Options', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'day_wrapper'       => esc_html__( 'Day & Time Wrapper', 'squad-modules-for-divi' ),
					'title_element'     => esc_html__( 'Title', 'squad-modules-for-divi' ),
					'title_text'        => esc_html__( 'Title Text', 'squad-modules-for-divi' ),
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
				'title_text' => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Title', 'squad-modules-for-divi' ),
					array(
						'font_size'   => array(
							'default' => '26px',
						),
						'line_height' => array(
							'default' => '1.2em',
						),
						'css'         => array(
							'main'  => "$this->main_css_element .squad-bh-elements .bh-element.bh-title-wrapper .bh-title-text",
							'hover' => "$this->main_css_element .squad-bh-elements .bh-element.bh-title-wrapper:hover .bh-title-text",
						),
						'tab_slug'    => 'advanced',
						'toggle_slug' => 'title_text',
					)
				),
				'day_text'   => divi_squad()->d4_module_helper->add_font_field(
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
				'time_text'  => divi_squad()->d4_module_helper->add_font_field(
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
				'default'       => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
				'day_wrapper'   => array(
					'label_prefix' => esc_html__( 'Wrapper', 'squad-modules-for-divi' ),
					'css'          => array(
						'main' => array(
							'border_radii'        => "$this->main_css_element .day-elements",
							'border_radii_hover'  => "$this->main_css_element .day-elements:hover",
							'border_styles'       => "$this->main_css_element .day-elements",
							'border_styles_hover' => "$this->main_css_element .day-elements:hover",
						),
					),
					'tab_slug'     => 'advanced',
					'toggle_slug'  => 'day_wrapper',
				),
				'title_element' => array(
					'label_prefix' => et_builder_i18n( 'Title' ),
					'css'          => array(
						'main' => array(
							'border_radii'        => "$this->main_css_element .squad-bh-elements .bh-element.bh-title-wrapper",
							'border_radii_hover'  => "$this->main_css_element .squad-bh-elements .bh-element.bh-title-wrapper:hover",
							'border_styles'       => "$this->main_css_element .squad-bh-elements .bh-element.bh-title-wrapper",
							'border_styles_hover' => "$this->main_css_element .squad-bh-elements .bh-element.bh-title-wrapper:hover",
						),
					),
					'tab_slug'     => 'advanced',
					'toggle_slug'  => 'title_element',
				),
			),
			'box_shadow'     => array(
				'default'       => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
				'day_wrapper'   => array(
					'label'             => esc_html__( 'Wrapper Box Shadow', 'squad-modules-for-divi' ),
					'option_category'   => 'layout',
					'css'               => array(
						'main'  => "$this->main_css_element .day-elements",
						'hover' => "$this->main_css_element .day-elements:hover",
					),
					'default_on_fronts' => array(
						'color'    => 'rgba(0,0,0,0.3)',
						'position' => 'outer',
					),
					'tab_slug'          => 'advanced',
					'toggle_slug'       => 'day_wrapper',
				),
				'title_element' => array(
					'label'             => esc_html__( 'Title Box Shadow', 'squad-modules-for-divi' ),
					'option_category'   => 'layout',
					'css'               => array(
						'main'  => "$this->main_css_element .squad-bh-elements .bh-element.bh-title-wrapper",
						'hover' => "$this->main_css_element .squad-bh-elements .bh-element.bh-title-wrapper:hover",
					),
					'default_on_fronts' => array(
						'color'    => 'rgba(0,0,0,0.3)',
						'position' => 'outer',
					),
					'tab_slug'          => 'advanced',
					'toggle_slug'       => 'title_element',
				),
			),
			'margin_padding' => divi_squad()->d4_module_helper->selectors_margin_padding( $this->main_css_element ),
			'max_width'      => divi_squad()->d4_module_helper->selectors_max_width( $this->main_css_element ),
			'height'         => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			'image_icon'     => false,
			'text'           => false,
			'button'         => false,
			'link_options'   => false,
		);

		// Declare custom css fields for the module.
		$this->custom_css_fields = array(
			'title'   => array(
				'label'    => esc_html__( 'Title', 'squad-modules-for-divi' ),
				'selector' => '.bh-element.bh-title-wrapper .bh-title-text',
			),
			'day'     => array(
				'label'    => esc_html__( 'Day', 'squad-modules-for-divi' ),
				'selector' => '.day-elements .day-name-text',
			),
			'time'    => array(
				'label'    => esc_html__( 'Time', 'squad-modules-for-divi' ),
				'selector' => '.day-elements .day-time-text',
			),
			'wrapper' => array(
				'label'    => esc_html__( 'Day Wrapper', 'squad-modules-for-divi' ),
				'selector' => '.day-elements',
			),
		);
	}

	/**
	 * Return an added new item(module) text.
	 *
	 * @return string
	 */
	public function add_new_child_text(): string {
		return esc_html__( 'Add New Business Day', 'squad-modules-for-divi' );
	}

	/**
	 * Declare general fields for the module
	 *
	 * @since 1.0.0
	 * @return array<string, array<string, array<int|string, string>|bool|string>>
	 */
	public function get_fields(): array {
		$text_fields      = array(
			'title'     => array(
				'label'           => et_builder_i18n( 'Title' ),
				'description'     => esc_html__( 'The title text will appear before at your business hours.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'title_content',
				'dynamic_content' => 'text',
				'hover'           => 'tabs',
				'mobile_options'  => true,
			),
			'title_tag' => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Title Tag', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Choose a tag to display with your title.', 'squad-modules-for-divi' ),
					'options'          => divi_squad()->d4_module_helper->get_html_tag_elements(),
					'default'          => 'h2',
					'default_on_front' => 'h2',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'title_content',
				)
			),
		);
		$general_settings = array(
			'title__enable'    => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Show Title', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can choose whether or not show the title text.', 'squad-modules-for-divi' ),
					'default'          => 'on',
					'default_on_front' => 'on',
					'affects'          => array(
						'wrapper_gap',
					),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'general_settings',
				)
			),
			'day_elements_gap' => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Gap Between Days', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can choose the gap between days.', 'squad-modules-for-divi' ),
					'type'             => 'range',
					'range_settings'   => array(
						'min_limit' => '0',
						'min'       => '0',
						'max_limit' => '100',
						'max'       => '100',
						'step'      => '1',
					),
					'default'          => '10px',
					'default_on_front' => '10px',
					'default_unit'     => 'px',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'general_settings',
				),
				array( 'use_hover' => false )
			),
			'wrapper_gap'      => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Gap between Title and Day Wrapper', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Adjust the gap between the title and the day wrapper.', 'squad-modules-for-divi' ),
					'range_settings'   => array(
						'min_limit' => '0',
						'min'       => '0',
						'max_limit' => '1100',
						'max'       => '1100',
						'step'      => '1',
					),
					'allow_empty'      => true,
					'default_unit'     => 'px',
					'default'          => '30px',
					'default_on_front' => '30px',
					'hover'            => false,
					'depends_show_if'  => 'on',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'general_settings',
				)
			),
		);
		$divider_fields   = $this->squad_utils->divider->get_fields(
			array(
				'label'       => esc_html__( 'Show Text Separator', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Here you can choose whether or not show the separator between day and time text.', 'squad-modules-for-divi' ),
				'toggle_slug' => 'separator_element',
			)
		);

		$wrapper_background_fields = $this->squad_utils->field_definitions->add_background_field(
			array(
				'label'       => esc_html__( 'Wrapper Background', 'squad-modules-for-divi' ),
				'base_name'   => 'day_wrapper_background',
				'context'     => 'day_wrapper_background_color',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'day_wrapper',
			)
		);
		$title_background_fields   = $this->squad_utils->field_definitions->add_background_field(
			array(
				'label'       => esc_html__( 'Title Background', 'squad-modules-for-divi' ),
				'base_name'   => 'title_background',
				'context'     => 'title_background_color',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'title_element',
			)
		);
		$wrapper_associated_fields = array(
			'day_text_width'               => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Day Text Width', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Adjust the width of the day text.', 'squad-modules-for-divi' ),
					'range_settings' => array(
						'min_limit' => '0',
						'min'       => '0',
						'max_limit' => '1100',
						'max'       => '1100',
						'step'      => '1',
					),
					'allow_empty'    => true,
					'default_unit'   => 'px',
					'hover'          => false,
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'day_wrapper',
				)
			),
			'time_text_width'              => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Time Text Width', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Adjust the width of the time text.', 'squad-modules-for-divi' ),
					'range_settings' => array(
						'min_limit' => '0',
						'min'       => '0',
						'max_limit' => '1100',
						'max'       => '1100',
						'step'      => '1',
					),
					'allow_empty'    => true,
					'default_unit'   => 'px',
					'hover'          => false,
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'day_wrapper',
				)
			),
			'day_wrapper_text_orientation' => divi_squad()->d4_module_helper->add_alignment_field(
				esc_html__( 'Text Alignment', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'This controls how your text is aligned within the module.', 'squad-modules-for-divi' ),
					'type'             => 'text_align',
					'options'          => et_builder_get_text_orientation_options(
						array( 'justified' ),
						array( 'justify' => 'Justified' )
					),
					'default'          => 'left',
					'default_on_front' => 'left',
					'tab_slug'         => 'advanced',
					'toggle_slug'      => 'day_wrapper',
				)
			),
			'day_wrapper_margin'           => divi_squad()->d4_module_helper->add_margin_padding_field(
				esc_html__( 'Wrapper Margin', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Here you can define a custom margin size for the wrapper.', 'squad-modules-for-divi' ),
					'type'        => 'custom_margin',
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'day_wrapper',
				)
			),
			'day_wrapper_padding'          => divi_squad()->d4_module_helper->add_margin_padding_field(
				esc_html__( 'Wrapper Padding', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can define a custom padding size.', 'squad-modules-for-divi' ),
					'type'             => 'custom_padding',
					'default'          => '10px|15px|10px|15px',
					'default_on_front' => '10px|15px|10px|15px',
					'tab_slug'         => 'advanced',
					'toggle_slug'      => 'day_wrapper',
				)
			),
		);
		$text_associated_fields    = array(
			'title_margin'  => divi_squad()->d4_module_helper->add_margin_padding_field(
				esc_html__( 'Title Margin', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Here you can define a custom margin size for the title.', 'squad-modules-for-divi' ),
					'type'           => 'custom_margin',
					'range_settings' => array(
						'min_limit' => '1',
						'min'       => '1',
						'max_limit' => '100',
						'max'       => '100',
						'step'      => '1',
					),
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'title_element',
				)
			),
			'title_padding' => divi_squad()->d4_module_helper->add_margin_padding_field(
				esc_html__( 'Title Padding', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can define a custom padding size.', 'squad-modules-for-divi' ),
					'type'             => 'custom_padding',
					'range_settings'   => array(
						'min_limit' => '1',
						'min'       => '1',
						'max_limit' => '100',
						'max'       => '100',
						'step'      => '1',
					),
					'default'          => '4px|8px|4px|8px',
					'default_on_front' => '4px|8px|4px|8px',
					'tab_slug'         => 'advanced',
					'toggle_slug'      => 'title_element',
				)
			),
		);

		// clean unnecessary fields.
		unset( $divider_fields['divider_position'] );

		return array_merge(
			$text_fields,
			$general_settings,
			$wrapper_background_fields,
			$wrapper_associated_fields,
			$divider_fields,
			$title_background_fields,
			$text_associated_fields
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

		// wrapper styles.
		$fields['day_wrapper_background_color'] = array( 'background' => "$this->main_css_element .day-elements" );
		$fields['day_wrapper_margin']           = array( 'margin' => "$this->main_css_element .day-elements" );
		$fields['day_wrapper_padding']          = array( 'padding' => "$this->main_css_element .day-elements" );
		divi_squad()->d4_module_helper->fix_border_transition( $fields, 'item_wrapper', "$this->main_css_element .day-elements" );
		divi_squad()->d4_module_helper->fix_box_shadow_transition( $fields, 'item_wrapper', "$this->main_css_element .day-elements" );

		// title styles.
		$fields['title_background_color'] = array( 'background' => "$this->main_css_element .squad-bh-elements .bh-element.bh-title-wrapper" );
		$fields['title_margin']           = array( 'margin' => "$this->main_css_element .squad-bh-elements .bh-element.bh-title-wrapper" );
		$fields['title_padding']          = array( 'padding' => "$this->main_css_element .squad-bh-elements .bh-element.bh-title-wrapper" );
		divi_squad()->d4_module_helper->fix_fonts_transition( $fields, 'title_text', "$this->main_css_element .squad-bh-elements .bh-element.bh-title-wrapper .bh-title-text" );
		divi_squad()->d4_module_helper->fix_border_transition( $fields, 'title_element', "$this->main_css_element .squad-bh-elements .bh-element.bh-title-wrapper" );
		divi_squad()->d4_module_helper->fix_box_shadow_transition( $fields, 'title_element', "$this->main_css_element .squad-bh-elements .bh-element.bh-title-wrapper" );

		// divider styles.
		$fields['divider_color']  = array( 'border-top-color' => "$this->main_css_element .day-element.day-element-divider:before" );
		$fields['divider_weight'] = array(
			'border-top-width' => "$this->main_css_element .day-element.day-element-divider:before",
			'height'           => "$this->main_css_element .day-element.day-element-divider:before",
		);

		// Default styles.
		$fields['background_layout'] = array( 'color' => $this->main_css_element );

		return $fields;
	}

	/**
	 * Render module output
	 *
	 * @since 1.0.0
	 *
	 * @param array  $attrs       List of unprocessed attributes.
	 * @param string $content     Content being processed.
	 * @param string $render_slug Slug of module that is used for rendering output.
	 *
	 * @return string module's rendered output
	 */
	public function render( $attrs, $content, $render_slug ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClassAfterLastUsed
		// Show a notice message in the frontend if the list item is empty.
		$content_warning  = sprintf( '<div class="squad-notice">%s</div>', esc_html__( 'Add one or more business day.', 'squad-modules-for-divi' ) );
		$title_verified   = 'on' === $this->prop( 'title__enable', 'off' ) ? $this->squad_render_title_text() : null;
		$content_verified = '' === $this->content ? $content_warning : $this->content;

		// Process styles for module output.
		$this->squad_generate_all_styles( $attrs );
		$this->squad_generate_divider_styles();

		return sprintf(
			'<div class="squad-bh-elements"> %1$s <div class="squad-list-container business-days"> %2$s </div> </div>',
			$title_verified,
			$content_verified
		);
	}

	/**
	 * Render title
	 *
	 * @return string
	 */
	protected function squad_render_title_text(): string {
		$multi_view = et_pb_multi_view_options( $this );

		// title tag, by default is h4.
		$title_tag = $this->props['title_tag'] ?? 'h2';

		$title_text = $multi_view->render_element(
			array(
				'tag'     => $title_tag,
				'content' => '{{title}}',
				'attrs'   => array(
					'class' => 'bh-title-text',
				),
			)
		);

		return sprintf(
			'<div class="bh-element bh-title-wrapper">%1$s</div>',
			wp_kses_post( $title_text )
		);
	}

	/**
	 * Process styles for module output.
	 *
	 * @param array $attrs List of unprocessed attributes.
	 *
	 * @return void
	 */
	protected function squad_generate_all_styles( array $attrs ): void {
		// Fixed: the custom background doesn't work at frontend.
		$this->props = array_merge( $attrs, $this->props );

		$day_wrapper = 'day_wrapper_background';
		$title_bg    = 'title_background';

		// List gap with default, responsive.
		$this->squad_utils->field_css_generations->generate_additional_styles(
			array(
				'field'        => 'day_elements_gap',
				'selector'     => "$this->main_css_element div .squad-list-container",
				'css_property' => 'gap',
				'type'         => 'grid',
			)
		);

		if ( 'on' === $this->prop( 'title__enable', 'on' ) ) {
			$this->squad_utils->field_css_generations->generate_additional_styles(
				array(
					'field'        => 'wrapper_gap',
					'selector'     => "$this->main_css_element .squad-bh-elements",
					'css_property' => 'gap',
					'type'         => 'grid',
				)
			);
		}

		// wrapper style.
		// wrapper background with default, responsive, hover.
		et_pb_background_options()->get_background_style(
			array(
				'base_prop_name'         => $day_wrapper,
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
					"use_{$day_wrapper}_color_gradient" => "{$day_wrapper}_use_color_gradient",
					$day_wrapper                        => "{$day_wrapper}_color",
				),
			)
		);
		// text align with default, responsive, hover.
		$this->generate_styles(
			array(
				'base_attr_name' => 'day_text_width',
				'selector'       => "$this->main_css_element .day-elements .day-name-text",
				'css_property'   => 'width',
				'render_slug'    => $this->slug,
				'type'           => 'range',
			)
		);
		// text align with default, responsive, hover.
		$this->generate_styles(
			array(
				'base_attr_name' => 'time_text_width',
				'selector'       => "$this->main_css_element .day-elements .day-element-time",
				'css_property'   => 'width',
				'render_slug'    => $this->slug,
				'type'           => 'range',
			)
		);
		// text align with default, responsive, hover.
		$this->generate_styles(
			array(
				'base_attr_name' => 'day_wrapper_text_orientation',
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
				'field'          => 'day_wrapper_margin',
				'selector'       => "$this->main_css_element .day-elements",
				'hover_selector' => "$this->main_css_element .day-elements:hover",
				'css_property'   => 'margin',
				'type'           => 'margin',
			)
		);
		// wrapper padding with default, responsive, hover.
		$this->squad_utils->field_css_generations->generate_margin_padding_styles(
			array(
				'field'          => 'day_wrapper_padding',
				'selector'       => "$this->main_css_element .day-elements",
				'hover_selector' => "$this->main_css_element .day-elements:hover",
				'css_property'   => 'padding',
				'type'           => 'padding',
			)
		);

		// title styles
		// title background with default, responsive, hover.
		et_pb_background_options()->get_background_style(
			array(
				'base_prop_name'         => $title_bg,
				'props'                  => $this->props,
				'selector'               => "$this->main_css_element .squad-bh-elements .bh-element.bh-title-wrapper",
				'selector_hover'         => "$this->main_css_element .squad-bh-elements:hover .bh-element.bh-title-wrapper",
				'selector_sticky'        => "$this->main_css_element .squad-bh-elements .bh-element.bh-title-wrapper",
				'function_name'          => $this->slug,
				'important'              => ' !important',
				'use_background_video'   => false,
				'use_background_pattern' => false,
				'use_background_mask'    => false,
				'prop_name_aliases'      => array(
					"use_{$title_bg}_color_gradient" => "{$title_bg}_use_color_gradient",
					$title_bg                        => "{$title_bg}_color",
				),
			)
		);
		// title margin with default, responsive, hover.
		$this->squad_utils->field_css_generations->generate_margin_padding_styles(
			array(
				'field'          => 'title_margin',
				'selector'       => "$this->main_css_element .squad-bh-elements .bh-element.bh-title-wrapper",
				'hover_selector' => "$this->main_css_element .squad-bh-elements:hover .bh-element.bh-title-wrapper",
				'css_property'   => 'margin',
				'type'           => 'margin',
			)
		);
		// title padding with default, responsive, hover.
		$this->squad_utils->field_css_generations->generate_margin_padding_styles(
			array(
				'field'          => 'title_padding',
				'selector'       => "$this->main_css_element .squad-bh-elements .bh-element.bh-title-wrapper",
				'hover_selector' => "$this->main_css_element .squad-bh-elements:hover .bh-element.bh-title-wrapper",
				'css_property'   => 'padding',
				'type'           => 'padding',
			)
		);
	}

	/**
	 * Generate styles for divider
	 */
	protected function squad_generate_divider_styles(): void {
		if ( 'on' === $this->prop( 'show_divider', 'off' ) ) {
			$this->squad_utils->field_css_generations->generate_divider_styles(
				array(
					'selector'  => "$this->main_css_element .day-elements .day-element-divider:before",
					'important' => true,
				)
			);
		} else {
			self::set_style(
				$this->slug,
				array(
					'selector'    => "$this->main_css_element .day-element.day-element-divider",
					'declaration' => 'display: none !important;',
				)
			);
		}
	}
}
