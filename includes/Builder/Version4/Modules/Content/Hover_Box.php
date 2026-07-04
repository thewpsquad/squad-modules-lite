<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Hover Box Module Class which extends the Divi Builder Module Class.
 *
 * An image card that reveals a content overlay (icon, title, text, button) on
 * hover, with an animated reveal and an optional image hover effect.
 * Server-rendered; CSS-only interactions (no frontend JS).
 *
 * @since   4.2.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Content;

use DiviSquad\Builder\Shared\Modules\Content\Hover_Box\Hoverbox_Helper;
use DiviSquad\Builder\Version4\Abstracts\Module;
use DiviSquad\Utils\Divi;
use function absint;
use function esc_html__;
use function et_pb_get_extended_font_icon_value;
use function sprintf;
use function wp_kses_post;

/**
 * Hover Box Module class.
 *
 * @since 4.2.0
 */
class Hover_Box extends Module {

	/**
	 * Initiate Module.
	 *
	 * @since 4.2.0
	 *
	 * @return void
	 */
	public function init(): void {
		$this->name      = esc_html__( 'Hover Box', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Hover Boxes', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'hover-box.svg' );

		$this->slug             = 'disq_hover_box';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.$this->slug";

		$this->child_title_var          = 'admin_label';
		$this->child_title_fallback_var = 'admin_label';

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'image_element'   => esc_html__( 'Image', 'squad-modules-for-divi' ),
					'persistent'      => esc_html__( 'Persistent Layer', 'squad-modules-for-divi' ),
					'icon_element'    => esc_html__( 'Icon', 'squad-modules-for-divi' ),
					'content_element' => esc_html__( 'Content', 'squad-modules-for-divi' ),
					'button_element'  => esc_html__( 'Button', 'squad-modules-for-divi' ),
					'overlay_effect'  => esc_html__( 'Overlay & Effect', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'box_layout'      => esc_html__( 'Box Layout', 'squad-modules-for-divi' ),
					'overlay_design'  => esc_html__( 'Overlay', 'squad-modules-for-divi' ),
					'icon_design'     => esc_html__( 'Icon', 'squad-modules-for-divi' ),
					'persistent_text' => esc_html__( 'Persistent Title', 'squad-modules-for-divi' ),
					'title_text'      => esc_html__( 'Overlay Title', 'squad-modules-for-divi' ),
					'content_text'    => esc_html__( 'Overlay Text', 'squad-modules-for-divi' ),
					'button_element'  => esc_html__( 'Button', 'squad-modules-for-divi' ),
				),
			),
		);

		$this->advanced_fields = array(
			'fonts'          => array(
				'persistent_text' => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Persistent Title', 'squad-modules-for-divi' ),
					array(
						'font_size'   => array( 'default' => '16px' ),
						'css'         => array(
							'main'  => "$this->main_css_element .squad-hoverbox__p-title",
							'hover' => "$this->main_css_element:hover .squad-hoverbox__p-title",
						),
						'tab_slug'    => 'advanced',
						'toggle_slug' => 'persistent_text',
					)
				),
				'title_text'      => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Overlay Title', 'squad-modules-for-divi' ),
					array(
						'font_size'   => array( 'default' => '22px' ),
						'css'         => array(
							'main'  => "$this->main_css_element .squad-hoverbox__title",
							'hover' => "$this->main_css_element:hover .squad-hoverbox__title",
						),
						'tab_slug'    => 'advanced',
						'toggle_slug' => 'title_text',
					)
				),
				'content_text'    => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Overlay Text', 'squad-modules-for-divi' ),
					array(
						'font_weight' => array( 'default' => '400' ),
						'font_size'   => array( 'default' => '14px' ),
						'line_height' => array( 'default' => '1.6' ),
						'css'         => array(
							'main'  => "$this->main_css_element .squad-hoverbox__text",
							'hover' => "$this->main_css_element:hover .squad-hoverbox__text",
						),
						'tab_slug'    => 'advanced',
						'toggle_slug' => 'content_text',
					)
				),
			),
			'background'     => divi_squad()->d4_module_helper->selectors_background( $this->main_css_element ),
			'borders'        => array(
				'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			),
			'box_shadow'     => array(
				'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			),
			'margin_padding' => divi_squad()->d4_module_helper->selectors_margin_padding( $this->main_css_element ),
			'max_width'      => divi_squad()->d4_module_helper->selectors_max_width( $this->main_css_element ),
			'height'         => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			'image_icon'     => false,
			'text'           => false,
			'filters'        => false,
			'link_options'   => false,
			'button'         => array(
				'button' => array(
					'label'         => esc_html__( 'Button', 'squad-modules-for-divi' ),
					'css'           => array(
						'main'         => "$this->main_css_element .squad-hoverbox__button.et_pb_button",
						'hover'        => "$this->main_css_element .squad-hoverbox__button.et_pb_button:hover",
						'limited_main' => "$this->main_css_element .squad-hoverbox__button.et_pb_button",
					),
					'use_alignment' => false,
					'box_shadow'    => array( 'css' => array( 'main' => "$this->main_css_element .squad-hoverbox__button.et_pb_button" ) ),
					'tab_slug'      => 'advanced',
					'toggle_slug'   => 'button_element',
				),
			),
		);

		$this->custom_css_fields = array(
			'hoverbox_image'   => array(
				'label'    => esc_html__( 'Image', 'squad-modules-for-divi' ),
				'selector' => '.squad-hoverbox__image',
			),
			'hoverbox_overlay' => array(
				'label'    => esc_html__( 'Overlay', 'squad-modules-for-divi' ),
				'selector' => '.squad-hoverbox__overlay',
			),
			'hoverbox_button'  => array(
				'label'    => esc_html__( 'Button', 'squad-modules-for-divi' ),
				'selector' => '.squad-hoverbox__button',
			),
		);
	}

	/**
	 * Declare general fields for the module.
	 *
	 * @since 4.2.0
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_fields(): array {
		// Image group.
		$image_fields = array(
			'image'          => array(
				'label'              => esc_html__( 'Image', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Upload an image for the hover box background. Leave empty to show the background color.', 'squad-modules-for-divi' ),
				'type'               => 'upload',
				'option_category'    => 'basic_option',
				'upload_button_text' => esc_attr__( 'Upload an image', 'squad-modules-for-divi' ),
				'choose_text'        => esc_attr__( 'Choose a Hover Box Image', 'squad-modules-for-divi' ),
				'update_text'        => esc_attr__( 'Set As Hover Box Image', 'squad-modules-for-divi' ),
				'dynamic_content'    => 'image',
				'tab_slug'           => 'general',
				'toggle_slug'        => 'image_element',
			),
			'image_alt'      => array(
				'label'           => esc_html__( 'Image Alt Text', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'Provide alt text for the image for accessibility.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => '',
				'dynamic_content' => 'text',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'image_element',
			),
			'image_hover_fx' => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Image Hover Effect', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'CSS-only effect applied to the image when hovered.', 'squad-modules-for-divi' ),
					'options'          => array(
						'none'      => esc_html__( 'None', 'squad-modules-for-divi' ),
						'zoom-in'   => esc_html__( 'Zoom In', 'squad-modules-for-divi' ),
						'zoom-out'  => esc_html__( 'Zoom Out', 'squad-modules-for-divi' ),
						'grayscale' => esc_html__( 'Grayscale', 'squad-modules-for-divi' ),
						'blur'      => esc_html__( 'Blur', 'squad-modules-for-divi' ),
						'rotate'    => esc_html__( 'Rotate', 'squad-modules-for-divi' ),
					),
					'default'          => 'zoom-in',
					'default_on_front' => 'zoom-in',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'image_element',
				)
			),
		);

		// Persistent layer group.
		$persistent_fields = array(
			'persistent_title' => array(
				'label'           => esc_html__( 'Persistent Title', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'Always-visible text over the image. Leave empty to hide this layer.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => '',
				'dynamic_content' => 'text',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'persistent',
			),
		);

		// Icon group.
		$icon_fields = array(
			'use_icon' => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Show Icon', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Show an icon in the overlay.', 'squad-modules-for-divi' ),
					'default_on_front' => 'off',
					'affects'          => array( 'icon', 'icon_color', 'icon_size' ),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'icon_element',
				)
			),
			'icon'     => array(
				'label'            => esc_html__( 'Choose an Icon', 'squad-modules-for-divi' ),
				'description'      => esc_html__( 'Pick the icon to display in the overlay.', 'squad-modules-for-divi' ),
				'type'             => 'select_icon',
				'option_category'  => 'basic_option',
				'class'            => array( 'et-pb-font-icon' ),
				'default_on_front' => '&#x4e;||divi||400',
				'show_if'          => array( 'use_icon' => 'on' ),
				'tab_slug'         => 'general',
				'toggle_slug'      => 'icon_element',
				'hover'            => 'tabs',
				'mobile_options'   => true,
			),
		);

		// Content group.
		$content_fields = array(
			'title'   => array(
				'label'           => esc_html__( 'Overlay Title', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'Title shown in the overlay.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => esc_html__( 'Hover Title', 'squad-modules-for-divi' ),
				'dynamic_content' => 'text',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'content_element',
			),
			'content' => array(
				'label'           => esc_html__( 'Overlay Text', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'Body text shown in the overlay. Allows safe inline HTML.', 'squad-modules-for-divi' ),
				'type'            => 'tiny_mce',
				'option_category' => 'basic_option',
				'default'         => '',
				'dynamic_content' => 'text',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'content_element',
			),
		);

		// Button group.
		$button_fields = array(
			'button_text'       => array(
				'label'           => esc_html__( 'Button Text', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'Label for the overlay button. Leave empty to hide the button.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => '',
				'dynamic_content' => 'text',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'button_element',
			),
			'button_url'        => array(
				'label'           => esc_html__( 'Button URL', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'Destination URL. Leave empty for a non-link button.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => '',
				'dynamic_content' => 'url',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'button_element',
			),
			'button_new_window' => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Open in New Tab', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Open the button link in a new browser tab.', 'squad-modules-for-divi' ),
					'default_on_front' => 'off',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'button_element',
				)
			),
		);

		// Overlay & effect group.
		$overlay_fields = array(
			'overlay_animation' => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Content Animation', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'How the overlay content enters when hovered.', 'squad-modules-for-divi' ),
					'options'          => array(
						'fade'        => esc_html__( 'Fade', 'squad-modules-for-divi' ),
						'slide-up'    => esc_html__( 'Slide Up', 'squad-modules-for-divi' ),
						'slide-down'  => esc_html__( 'Slide Down', 'squad-modules-for-divi' ),
						'slide-left'  => esc_html__( 'Slide Left', 'squad-modules-for-divi' ),
						'slide-right' => esc_html__( 'Slide Right', 'squad-modules-for-divi' ),
						'zoom'        => esc_html__( 'Zoom', 'squad-modules-for-divi' ),
					),
					'default'          => 'fade',
					'default_on_front' => 'fade',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'overlay_effect',
				)
			),
			'content_valign'    => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Content Vertical Alignment', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Vertical position of overlay content.', 'squad-modules-for-divi' ),
					'options'          => array(
						'top'    => esc_html__( 'Top', 'squad-modules-for-divi' ),
						'center' => esc_html__( 'Center', 'squad-modules-for-divi' ),
						'bottom' => esc_html__( 'Bottom', 'squad-modules-for-divi' ),
					),
					'default'          => 'center',
					'default_on_front' => 'center',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'overlay_effect',
				)
			),
		);

		// Design tab fields.
		$design_fields = array(
			'box_height'      => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Box Height', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Height of the hover box.', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '100', 'max' => '800', 'step' => '1' ),
					'default'        => '300px',
					'default_unit'   => 'px',
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'box_layout',
				)
			),
			'overlay_bg'      => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Overlay Background', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Background color of the hover overlay.', 'squad-modules-for-divi' ),
					'default'     => 'rgba(0,0,0,0.6)',
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'overlay_design',
				)
			),
			'overlay_opacity' => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Overlay Opacity', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Opacity of the overlay at full-hover state (0–100).', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '0', 'max' => '100', 'step' => '1' ),
					'default'        => '100',
					'unitless'       => true,
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'overlay_design',
				)
			),
			'icon_color'      => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Icon Color', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Color of the overlay icon.', 'squad-modules-for-divi' ),
					'show_if'     => array( 'use_icon' => 'on' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'icon_design',
				)
			),
			'icon_size'       => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Icon Size', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Size of the overlay icon.', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '8', 'max' => '100', 'step' => '1' ),
					'default'        => '32px',
					'show_if'        => array( 'use_icon' => 'on' ),
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'icon_design',
				)
			),
			'text_alignment'  => divi_squad()->d4_module_helper->add_alignment_field(
				esc_html__( 'Content Alignment', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Horizontal alignment of overlay content.', 'squad-modules-for-divi' ),
					'type'             => 'text_align',
					'default'          => 'center',
					'default_on_front' => 'center',
					'tab_slug'         => 'advanced',
					'toggle_slug'      => 'box_layout',
				)
			),
		);

		return array_merge(
			$image_fields,
			$persistent_fields,
			$icon_fields,
			$content_fields,
			$button_fields,
			$overlay_fields,
			$design_fields
		);
	}

	/**
	 * Get CSS fields transition.
	 *
	 * @since 4.2.0
	 *
	 * @return array<string, array<string, string>>
	 */
	public function get_transition_fields_css_props(): array {
		$fields = parent::get_transition_fields_css_props();

		$fields['icon_color'] = array( 'color' => "$this->main_css_element .squad-hoverbox__icon .et-pb-icon" );
		$fields['icon_size']  = array( 'font-size' => "$this->main_css_element .squad-hoverbox__icon .et-pb-icon" );

		divi_squad()->d4_module_helper->fix_fonts_transition( $fields, 'persistent_text', "$this->main_css_element .squad-hoverbox__p-title" );
		divi_squad()->d4_module_helper->fix_fonts_transition( $fields, 'title_text', "$this->main_css_element .squad-hoverbox__title" );
		divi_squad()->d4_module_helper->fix_fonts_transition( $fields, 'content_text', "$this->main_css_element .squad-hoverbox__text" );

		return $fields;
	}

	/**
	 * Render the module output.
	 *
	 * @since 4.2.0
	 *
	 * @param array<array-key, mixed> $attrs       List of attributes.
	 * @param string                  $content     Content being processed.
	 * @param string                  $render_slug Slug of module that is used for rendering output.
	 *
	 * @return string
	 */
	public function render( $attrs, $content, $render_slug ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClassAfterLastUsed
		$this->props = array_merge( $attrs, $this->props );

		$image_src         = (string) $this->prop( 'image', '' );
		$image_alt         = (string) $this->prop( 'image_alt', '' );
		$image_hover_fx    = (string) $this->prop( 'image_hover_fx', 'zoom-in' );
		$persistent_title  = (string) $this->prop( 'persistent_title', '' );
		$use_icon          = 'on' === $this->prop( 'use_icon', 'off' );
		$title             = (string) $this->prop( 'title', '' );
		$content_body      = (string) $this->prop( 'content', '' );
		$button_text       = (string) $this->prop( 'button_text', '' );
		$button_url        = (string) $this->prop( 'button_url', '' );
		$button_new_window = 'on' === $this->prop( 'button_new_window', 'off' );
		$overlay_animation = (string) $this->prop( 'overlay_animation', 'fade' );
		$content_valign    = (string) $this->prop( 'content_valign', 'center' );
		$overlay_bg        = (string) $this->prop( 'overlay_bg', 'rgba(0,0,0,0.6)' );
		$overlay_opacity   = absint( $this->prop( 'overlay_opacity', '100' ) );

		// Validate allowlisted values — fall back to defaults on invalid input.
		if ( ! Hoverbox_Helper::is_valid_fx( $image_hover_fx ) ) {
			$image_hover_fx = 'zoom-in';
		}
		if ( ! Hoverbox_Helper::is_valid_anim( $overlay_animation ) ) {
			$overlay_animation = 'fade';
		}
		if ( ! Hoverbox_Helper::is_valid_valign( $content_valign ) ) {
			$content_valign = 'center';
		}

		// Icon markup.
		$icon_html = '';
		if ( $use_icon ) {
			$icon_raw = (string) $this->prop( 'icon', '' );
			if ( '' !== $icon_raw ) {
				Divi::inject_fa_icons( $icon_raw );
				$icon_value = et_pb_get_extended_font_icon_value( $icon_raw, true );

				$this->generate_styles(
					array(
						'base_attr_name' => 'icon_color',
						'selector'       => "$this->main_css_element .squad-hoverbox__icon .et-pb-icon",
						'hover_selector' => "$this->main_css_element:hover .squad-hoverbox__icon .et-pb-icon",
						'css_property'   => 'color',
						'render_slug'    => $this->slug,
						'type'           => 'color',
						'important'      => true,
					)
				);
				$this->generate_styles(
					array(
						'utility_arg'    => 'icon_font_family',
						'render_slug'    => $this->slug,
						'base_attr_name' => 'icon',
						'important'      => true,
						'selector'       => "$this->main_css_element .squad-hoverbox__icon .et-pb-icon",
						'processor'      => array(
							'ET_Builder_Module_Helper_Style_Processor',
							'process_extended_icon',
						),
					)
				);
				$this->generate_styles(
					array(
						'base_attr_name' => 'icon_size',
						'selector'       => "$this->main_css_element .squad-hoverbox__icon .et-pb-icon",
						'hover_selector' => "$this->main_css_element:hover .squad-hoverbox__icon .et-pb-icon",
						'css_property'   => 'font-size',
						'render_slug'    => $this->slug,
						'type'           => 'range',
						'important'      => true,
					)
				);

				$icon_html = sprintf(
					'<span class="squad-hoverbox__icon" aria-hidden="true"><span class="et-pb-icon">%s</span></span>',
					wp_kses_post( $icon_value )
				);
			}
		}

		// Dynamic CSS: box height.
		$box_height = (string) $this->prop( 'box_height', '300px' );
		$box_height = Hoverbox_Helper::sanitize_css_length( $box_height );
		if ( '' !== $box_height ) {
			$this->generate_styles(
				array(
					'base_attr_name' => 'box_height',
					'selector'       => "$this->main_css_element .squad-hoverbox",
					'css_property'   => 'height',
					'render_slug'    => $this->slug,
					'type'           => 'range',
				)
			);
		}

		// Dynamic CSS: text alignment.
		$this->generate_styles(
			array(
				'base_attr_name' => 'text_alignment',
				'selector'       => "$this->main_css_element .squad-hoverbox__content",
				'css_property'   => 'text-align',
				'render_slug'    => $this->slug,
				'type'           => 'align',
			)
		);

		// Dynamic CSS: overlay background (inline via sanitize_css_background — never esc_attr).
		$sanitized_overlay_bg = Hoverbox_Helper::sanitize_css_background( $overlay_bg );
		if ( '' !== $sanitized_overlay_bg ) {
			ET_Builder_Element::set_style(
				$render_slug,
				array(
					'selector'    => "$this->main_css_element .squad-hoverbox__overlay",
					'declaration' => sprintf( 'background: %s;', $sanitized_overlay_bg ),
				)
			);
		}

		// Dynamic CSS: overlay opacity on hover state (clamped 0–100 → 0–1).
		$opacity_decimal = (float) min( 1.0, max( 0.0, $overlay_opacity / 100 ) );
		ET_Builder_Element::set_style(
			$render_slug,
			array(
				'selector'    => "$this->main_css_element .squad-hoverbox:hover .squad-hoverbox__overlay",
				'declaration' => sprintf( 'opacity: %s;', number_format( $opacity_decimal, 2, '.', '' ) ),
			)
		);

		return Hoverbox_Helper::build_shell(
			$image_src,
			$image_alt,
			$image_hover_fx,
			$persistent_title,
			$use_icon,
			$icon_html,
			$title,
			$content_body,
			$button_text,
			$button_url,
			$button_new_window,
			$overlay_animation,
			$content_valign
		);
	}

}
