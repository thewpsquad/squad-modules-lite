<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Breadcrumb Module Class which extend the Divi Builder Module Class.
 *
 * This class provides breadcrumbs adding functionalities in the visual builder.
 *
 * @since   1.4.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Creative;

use DiviSquad\Builder\Version4\Abstracts\Module;
use DiviSquad\Utils\Divi;
use function esc_attr;
use function esc_html;
use function esc_html__;
use function et_pb_process_font_icon;

/**
 * Breadcrumbs Module Class.
 *
 * @since   1.4.0
 * @package DiviSquad
 */
class Breadcrumbs extends Module {
	/**
	 * Initiate Module.
	 * Set the module name and other properties on init.
	 *
	 * @since 1.4.0
	 *
	 * @return void
	 */
	public function init(): void {
		$this->name      = esc_html__( 'Breadcrumbs', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Breadcrumbs', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'breadcrumbs.svg' );

		$this->slug             = 'disq_breadcrumbs';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.$this->slug";

		$this->child_title_var          = 'title';
		$this->child_title_fallback_var = 'admin_label';

		// Connect with utils.
		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		// Settings modal toggles.
		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'main_content' => esc_html__( 'Text Content', 'squad-modules-for-divi' ),
					'icon'         => esc_html__( 'Icons', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'breadcrumbs'  => esc_html__( 'Colors', 'squad-modules-for-divi' ),
					'content_text' => esc_html__( 'Typography', 'squad-modules-for-divi' ),
				),
			),
		);

		// Advanced fields configuration.
		$this->advanced_fields = array(
			'fonts'          => array(
				'content_text' => divi_squad()->d4_module_helper->add_font_field(
					'',
					array(
						'font_size'       => array(
							'default' => '14px',
						),
						'line_height'     => array(
							'default' => '1.7em',
						),
						'letter_spacing'  => array(
							'default' => '0px',
						),
						'hide_text_align' => true,
						'css'             => array(
							'main'  => "$this->main_css_element div",
							'hover' => "$this->main_css_element div:hover",
						),
						'tab_slug'        => 'advanced',
						'toggle_slug'     => 'content_text',
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

		// Custom CSS fields.
		$this->custom_css_fields = array(
			'home_text'       => array(
				'label'    => esc_html__( 'Home Text', 'squad-modules-for-divi' ),
				'selector' => 'div .home',
			),
			'breadcrumb_list' => array(
				'label'    => esc_html__( 'Breadcrumb List', 'squad-modules-for-divi' ),
				'selector' => '.breadcrumb-list',
			),
			'before_text'     => array(
				'label'    => esc_html__( 'Before Text', 'squad-modules-for-divi' ),
				'selector' => 'div .before',
			),
			'current_text'    => array(
				'label'    => esc_html__( 'Current', 'squad-modules-for-divi' ),
				'selector' => 'div .before-text, .before-icon',
			),
			'current_item'    => array(
				'label'    => esc_html__( 'Current Page', 'squad-modules-for-divi' ),
				'selector' => '.current',
			),
		);
	}

	/**
	 * Get fields for the module
	 *
	 * @since 1.4.0
	 *
	 * @return array<string, array<string, array<int|string, string>|bool|string>>
	 */
	public function get_fields(): array {
		return array(
			// Text Content.
			'home_text'          => array(
				'label'           => esc_html__( 'Home Text', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'The default Home text in the Breadcrumbs', 'squad-modules-for-divi' ),
				'default'         => esc_html__( 'Home', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'main_content',
				'dynamic_content' => 'text',
			),
			'before_text'        => array(
				'label'           => esc_html__( 'Before Text', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'Text to display before breadcrumbs', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'main_content',
				'dynamic_content' => 'text',
			),

			// Icons.
			'font_icon'          => array(
				'label'           => esc_html__( 'Separator Icon', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'Choose an icon to separate breadcrumb items', 'squad-modules-for-divi' ),
				'type'            => 'select_icon',
				'option_category' => 'basic_option',
				'renderer'        => 'select_icon',
				'class'           => array( 'et-pb-font-icon' ),
				'tab_slug'        => 'general',
				'toggle_slug'     => 'icon',
				'default'         => '9||divi||400',
			),
			'before_icon'        => array(
				'label'           => esc_html__( 'Before Icon', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'Icon to display before breadcrumbs', 'squad-modules-for-divi' ),
				'type'            => 'select_icon',
				'renderer'        => 'select_icon',
				'option_category' => 'basic_option',
				'class'           => array( 'et-pb-font-icon' ),
				'tab_slug'        => 'general',
				'toggle_slug'     => 'icon',
				'show_if'         => array( 'use_before_icon' => 'on' ),
			),

			// Colors.
			'link_color'         => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Link Color', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Here you can define a custom color for the links in the breadcrumbs.', 'squad-modules-for-divi' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'breadcrumbs',
				)
			),
			'separator_color'    => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Separator Color', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Here you can define a custom color for the separator in the breadcrumbs.', 'squad-modules-for-divi' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'breadcrumbs',
				)
			),
			'current_text_color' => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Current Page Color', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Here you can define a custom color for the current text in the breadcrumbs.', 'squad-modules-for-divi' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'breadcrumbs',
				)
			),
		);
	}

	/**
	 * Get CSS transition fields
	 *
	 * @since 1.4.0
	 *
	 * @return array
	 */
	public function get_transition_fields_css_props(): array {
		$fields = parent::get_transition_fields_css_props();

		$fields['link_color']         = array( 'color' => "$this->main_css_element div a" );
		$fields['separator_color']    = array( 'color' => "$this->main_css_element div .separator" );
		$fields['current_text_color'] = array( 'color' => "$this->main_css_element div .current" );
		divi_squad()->d4_module_helper->fix_fonts_transition( $fields, 'content_text', "$this->main_css_element div" );

		// Default styles.
		$fields['background_layout'] = array( 'color' => "$this->main_css_element div" );

		return $fields;
	}

	/**
	 * Render module output
	 *
	 * @since 1.4.0
	 *
	 * @param array  $attrs       List of attributes.
	 * @param string $content     Content being processed.
	 * @param string $render_slug Slug of module being rendered.
	 *
	 * @return string
	 */
	public function render( $attrs, $content, $render_slug ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClassAfterLastUsed
		$before_html = '';
		if ( '' !== $this->prop( 'before_icon', '' ) ) {
			$before_icon = esc_attr( et_pb_process_font_icon( $this->prop( 'before_icon', '' ) ) );
			$before_html = sprintf( '<span class="before-icon et-pb-icon">%1$s</span>', $before_icon );
		}

		// Generating the Breadcrumbs.
		$breadcrumbs = $this->squad_utils->breadcrumbs->get_hansel_and_gretel(
			esc_html( $this->props['home_text'] ),
			esc_html( $this->props['before_text'] ),
			esc_attr( et_pb_process_font_icon( $this->prop( 'font_icon', '%%24%%' ) ) )
		);

		// Divi icon fallback support.
		Divi::inject_fa_icons( $this->prop( 'font_icon', '&#x39;||divi||400' ) );
		Divi::inject_fa_icons( $this->prop( 'before_icon', '&#x24;||divi||400' ) );

		// Generate styles.
		$this->squad_generate_additional_styles( $attrs );

		return sprintf(
			'<div class="breadcrumbs">%2$s %1$s</div>',
			$breadcrumbs,
			$before_html
		);
	}

	/**
	 * Generate module styles
	 *
	 * @since  1.4.0
	 * @access protected
	 *
	 * @param array $attrs Module attributes.
	 *
	 * @return void
	 */
	protected function squad_generate_additional_styles( array $attrs ) {
		// Merge attributes with props.
		$this->props = array_merge( $attrs, $this->props );

		// Font Icon Styles.
		$this->generate_styles(
			array(
				'utility_arg'    => 'icon_font_family',
				'base_attr_name' => 'font_icon',
				'render_slug'    => $this->slug,
				'important'      => true,
				'selector'       => "$this->main_css_element div .breadcrumbs .separator.et-pb-icon",
				'processor'      => array(
					'ET_Builder_Module_Helper_Style_Processor',
					'process_extended_icon',
				),
			)
		);
		$this->generate_styles(
			array(
				'utility_arg'    => 'icon_font_family',
				'base_attr_name' => 'before_icon',
				'render_slug'    => $this->slug,
				'important'      => true,
				'selector'       => "$this->main_css_element div .breadcrumbs .before-icon.et-pb-icon",
				'processor'      => array(
					'ET_Builder_Module_Helper_Style_Processor',
					'process_extended_icon',
				),
			)
		);

		// Color styles.
		$color_fields = array(
			'link_color'         => 'div .breadcrumbs .breadcrumb-list a',
			'separator_color'    => 'div .breadcrumbs .separator',
			'current_text_color' => 'div .breadcrumbs .current',
		);

		foreach ( $color_fields as $field => $selector ) {
			$this->generate_styles(
				array(
					'base_attr_name' => $field,
					'selector'       => "$this->main_css_element $selector",
					'css_property'   => 'color',
					'render_slug'    => $this->slug,
					'type'           => 'color',
				)
			);
		}
	}
}
