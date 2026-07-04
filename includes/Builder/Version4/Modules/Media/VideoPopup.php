<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Video Popup Module Class which extend the Divi Builder Module Class.
 *
 * This class provides video popup adding functionalities in the visual builder.
 *
 * @since   1.4.1
 * @author  The WP Squad <support@squadmodules.com>
 * @package DiviSquad
 */

namespace DiviSquad\Builder\Version4\Modules\Media;

use DiviSquad\Builder\Version4\Abstracts\Module;
use DiviSquad\Core\Supports\Polyfills\Str;
use function esc_attr__;
use function esc_html__;
use function et_builder_accent_color;
use function et_pb_background_options;
use function is_wp_error;
use function str_replace;
use function wp_enqueue_script;

/**
 * The Drop Cap Module Class.
 *
 * @since   1.4.1
 * @package DiviSquad
 */
class VideoPopup extends Module {

	/**
	 * Initiate Module.
	 * Set the module name on init.
	 *
	 * @since 1.4.1
	 * @return void
	 */
	public function init(): void {
		$this->name      = esc_html__( 'Video Popup', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Video Popups', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'video-popup.svg' );

		$this->slug             = 'disq_video_popup';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.$this->slug";

		$this->child_title_var          = 'admin_label';
		$this->child_title_fallback_var = 'admin_label';

		// Connect with utils.
		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		// Declare settings modal toggles for the module.
		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'content'  => esc_html__( 'Content', 'squad-modules-for-divi' ),
					'settings' => esc_html__( 'Settings', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'image' => esc_html__( 'Image', 'squad-modules-for-divi' ),
					'icon'  => esc_html__( 'Icon', 'squad-modules-for-divi' ),
					'text'  => esc_html__( 'Text', 'squad-modules-for-divi' ),
					'popup' => esc_html__( 'Popup', 'squad-modules-for-divi' ),
				),
			),
		);

		// Declare advanced fields for the module.
		$this->advanced_fields = array(
			'fonts'          => array(
				'trigger' => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Before', 'squad-modules-for-divi' ),
					array(
						'font_size'       => array(
							'default' => '16px',
						),
						'hide_text_align' => true,
						'css'             => array(
							'main'      => "$this->main_css_element div .video-popup .video-popup-text",
							'hover'     => "$this->main_css_element:hover div .video-popup .video-popup-text",
							'important' => 'all',
						),
						'tab_slug'        => 'advanced',
						'toggle_slug'     => 'text',
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
			'text_shadow'    => false,
			'button'         => false,
			'filters'        => false,
		);

		// Declare custom css fields for the module.
		$this->custom_css_fields = array(
			'image'        => array(
				'label'    => esc_html__( 'Image', 'squad-modules-for-divi' ),
				'selector' => '.video-popup .video-popup-figure img',
			),
			'icon_wrapper' => array(
				'label'    => esc_html__( 'Icon Wrapper', 'squad-modules-for-divi' ),
				'selector' => '.video-popup .video-popup-icon',
			),
			'icon'         => array(
				'label'    => esc_html__( 'Icon', 'squad-modules-for-divi' ),
				'selector' => '.video-popup .video-popup-icon svg',
			),
			'text'         => array(
				'label'    => esc_html__( 'Text', 'squad-modules-for-divi' ),
				'selector' => '.video-popup .video-popup-text',
			),
		);
	}

	/**
	 * Declare general fields for the module
	 *
	 * @since 1.4.1
	 * @return array[]
	 */
	public function get_fields(): array {
		$fields = array(
			'use_overlay'      => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Use Overlay Image', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Here you can choose whether overlay image should be used.', 'squad-modules-for-divi' ),
					'affects'     => array(
						'image',
						'alt',
						'icon_alignment',
						'img_height',
					),
					'tab_slug'    => 'general',
					'toggle_slug' => 'content',
				)
			),
			'image'            => array(
				'label'              => esc_html__( 'Image', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Here you can define placeholder image for the video.', 'squad-modules-for-divi' ),
				'type'               => 'upload',
				'data_type'          => 'image',
				'upload_button_text' => esc_attr__( 'Upload an image', 'squad-modules-for-divi' ),
				'choose_text'        => esc_attr__( 'Choose an Image', 'squad-modules-for-divi' ),
				'update_text'        => esc_attr__( 'Set As Image', 'squad-modules-for-divi' ),
				'depends_show_if'    => 'on',
				'tab_slug'           => 'general',
				'toggle_slug'        => 'content',
			),
			'alt'              => array(
				'label'           => esc_html__( 'Image Alt Text', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'Here you can define the HTML ALT text for your overlay image.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'depends_show_if' => 'on',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'content',
			),
			'trigger_element'  => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Button Element', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can select button element for the video popup.', 'squad-modules-for-divi' ),
					'options'          => array(
						'icon'      => esc_html__( 'Icon', 'squad-modules-for-divi' ),
						'text'      => esc_html__( 'Text', 'squad-modules-for-divi' ),
						'icon_text' => esc_html__( 'Icon & Text', 'squad-modules-for-divi' ),
					),
					'default_on_front' => 'icon',
					'affects'          => array(
						'icon',
						'text',
						'icon_spacing',
						'icon_color',
						'icon_bg',
						'icon_size',
						'icon_opacity',
						'icon_height',
						'icon_width',
						'icon_radius',
						'use_text_box',
					),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'content',
				)
			),
			'icon'             => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Select Play Icon', 'squad-modules-for-divi' ),
				array(
					'description'         => esc_html__( 'Here you can select different type of play icon from the video.', 'squad-modules-for-divi' ),
					'options'             => array(
						'1' => esc_html__( 'Icon 1', 'squad-modules-for-divi' ),
						'2' => esc_html__( 'Icon 2', 'squad-modules-for-divi' ),
						'3' => esc_html__( 'Icon 3', 'squad-modules-for-divi' ),
						'4' => esc_html__( 'Icon 4', 'squad-modules-for-divi' ),
						'5' => esc_html__( 'Icon 5', 'squad-modules-for-divi' ),
						'6' => esc_html__( 'Icon 6', 'squad-modules-for-divi' ),
					),
					'default_on_front'    => '1',
					'depends_show_if_not' => array( 'text' ),
					'tab_slug'            => 'general',
					'toggle_slug'         => 'content',
				)
			),
			'text'             => array(
				'label'               => esc_html__( 'Trigger Text', 'squad-modules-for-divi' ),
				'description'         => esc_html__( 'Define the trigger text for your popup.', 'squad-modules-for-divi' ),
				'type'                => 'text',
				'default'             => 'Play',
				'depends_show_if_not' => array( 'icon' ),
				'tab_slug'            => 'general',
				'toggle_slug'         => 'content',
			),
			'type'             => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Video Type', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Define video type for the popup.', 'squad-modules-for-divi' ),
					'options'          => array(
						'yt'    => esc_html__( 'Youtube', 'squad-modules-for-divi' ),
						'vm'    => esc_html__( 'Vimeo', 'squad-modules-for-divi' ),
						'video' => esc_html__( 'Custom Upload', 'squad-modules-for-divi' ),
					),
					'default_on_front' => 'yt',
					'affects'          => array(
						'video_link',
						'video',
					),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'content',
				)
			),
			'video_link'       => array(
				'label'               => esc_html__( 'Video URL', 'squad-modules-for-divi' ),
				'description'         => esc_html__( 'Type youtube or vimeo video url which you would like to display in the popup.', 'squad-modules-for-divi' ),
				'type'                => 'text',
				'depends_show_if_not' => array( 'video' ),
				'tab_slug'            => 'general',
				'toggle_slug'         => 'content',
			),
			'video'            => array(
				'label'              => esc_html__( 'Video MP4 File', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Upload your desired video in .MP4 format, or type in the URL to the video you would like to display', 'squad-modules-for-divi' ),
				'type'               => 'upload',
				'data_type'          => 'video',
				'upload_button_text' => esc_attr__( 'Upload a video', 'squad-modules-for-divi' ),
				'choose_text'        => esc_attr__( 'Choose a Video MP4 File', 'squad-modules-for-divi' ),
				'update_text'        => esc_attr__( 'Set As Video', 'squad-modules-for-divi' ),
				'depends_show_if'    => 'video',
				'tab_slug'           => 'general',
				'toggle_slug'        => 'content',
			),
			'use_animation'    => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Use Animated Icon', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Use animated wave for your icon. For better experience please set icon background color from icon design toggle.', 'squad-modules-for-divi' ),
					'affects'     => array(
						'wave_bg',
					),
					'tab_slug'    => 'general',
					'toggle_slug' => 'settings',
				)
			),
			'wave_bg'          => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Animated Wave Color', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Here you can define custom color for the animated wave of your icon.', 'squad-modules-for-divi' ),
					'default'         => '#ffffff',
					'depends_show_if' => 'on',
					'tab_slug'        => 'general',
					'toggle_slug'     => 'settings',
				)
			),
			'icon_alignment'   => divi_squad()->d4_module_helper->add_alignment_field(
				esc_html__( 'Icon/Text Alignment', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Align content to the left, right or center.', 'squad-modules-for-divi' ),
					'type'            => 'text_align',
					'options_icon'    => 'module_align',
					'default'         => 'center',
					'depends_show_if' => 'off',
					'tab_slug'        => 'general',
					'toggle_slug'     => 'settings',
					'mobile_options'  => true,
				)
			),
			'icon_spacing'     => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Spacing Between Icon and Text', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Here you can define spacing between icon and text.', 'squad-modules-for-divi' ),
					'range_settings'  => array(
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					),
					'default'         => '20px',
					'default_unit'    => 'px',
					'depends_show_if' => 'icon_text',
					'tab_slug'        => 'general',
					'toggle_slug'     => 'settings',
					'mobile_options'  => true,
				)
			),
			'img_height'       => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Image Height', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Here you can define static height for your image.', 'squad-modules-for-divi' ),
					'default_unit'    => 'px',
					'range_settings'  => array(
						'min'  => 0,
						'max'  => 500,
						'step' => 1,
					),
					'depends_show_if' => 'on',
					'tab_slug'        => 'advanced',
					'toggle_slug'     => 'image',
					'mobile_options'  => true,
				)
			),
			'icon_color'       => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Color', 'squad-modules-for-divi' ),
				array(
					'description'         => esc_html__( 'Here you can define custom color for your icon.', 'squad-modules-for-divi' ),
					'default'             => et_builder_accent_color(),
					'depends_show_if_not' => array( 'text' ),
					'tab_slug'            => 'advanced',
					'toggle_slug'         => 'icon',
					'hover'               => 'tabs',
				)
			),
			'icon_bg'          => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Background', 'squad-modules-for-divi' ),
				array(
					'description'         => esc_html__( 'Here you can define custom background for your icon.', 'squad-modules-for-divi' ),
					'depends_show_if_not' => array( 'text' ),
					'tab_slug'            => 'advanced',
					'toggle_slug'         => 'icon',
					'hover'               => 'tabs',
				)
			),
			'icon_size'        => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Size', 'squad-modules-for-divi' ),
				array(
					'description'         => esc_html__( 'Here you can define custom size for your icon.', 'squad-modules-for-divi' ),
					'range_settings'      => array(
						'min'  => 0,
						'max'  => 200,
						'step' => 1,
					),
					'default'             => '60px',
					'default_unit'        => 'px',
					'depends_show_if_not' => array( 'text' ),
					'tab_slug'            => 'advanced',
					'toggle_slug'         => 'icon',
					'hover'               => 'tabs',
					'mobile_options'      => true,
				)
			),
			'icon_opacity'     => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Opacity', 'squad-modules-for-divi' ),
				array(
					'description'         => esc_html__( 'Define the opacity for the icon. Set the value from 0 - 1. The lower value, the more transparent.', 'squad-modules-for-divi' ),
					'range_settings'      => array(
						'min'  => 0,
						'max'  => 1,
						'step' => .02,
					),
					'default'             => '1',
					'unitless'            => true,
					'depends_show_if_not' => array( 'text' ),
					'tab_slug'            => 'advanced',
					'toggle_slug'         => 'icon',
					'hover'               => 'tabs',
				)
			),
			'icon_height'      => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Height', 'squad-modules-for-divi' ),
				array(
					'description'         => esc_html__( 'Here you can define static height for your icon.', 'squad-modules-for-divi' ),
					'range_settings'      => array(
						'min'  => 0,
						'max'  => 300,
						'step' => 1,
					),
					'default'             => 'initial',
					'default_unit'        => 'px',
					'depends_show_if_not' => array( 'text' ),
					'tab_slug'            => 'advanced',
					'toggle_slug'         => 'icon',
					'mobile_options'      => true,
				)
			),
			'icon_width'       => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Width', 'squad-modules-for-divi' ),
				array(
					'description'         => esc_html__( 'Here you can define static width for your icon.', 'squad-modules-for-divi' ),
					'range_settings'      => array(
						'min'  => 0,
						'max'  => 300,
						'step' => 1,
					),
					'default'             => 'initial',
					'default_unit'        => 'px',
					'depends_show_if_not' => array( 'text' ),
					'tab_slug'            => 'advanced',
					'toggle_slug'         => 'icon',
					'mobile_options'      => true,
				)
			),
			'icon_radius'      => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Border Radius', 'squad-modules-for-divi' ),
				array(
					'description'         => esc_html__( 'Here you can define the radius value for your icon border.', 'squad-modules-for-divi' ),
					'range_settings'      => array(
						'min'  => 0,
						'max'  => 400,
						'step' => 1,
					),
					'default'             => '0px',
					'default_unit'        => 'px',
					'depends_show_if_not' => array( 'text' ),
					'tab_slug'            => 'advanced',
					'toggle_slug'         => 'icon',
				)
			),

			// Popup.
			'popup_bg'         => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Popup Background', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Here you can define custom background color for your popup.', 'squad-modules-for-divi' ),
					'default'     => 'rgba(0,0,0,.8)',
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'popup',
				)
			),
			'close_icon_color' => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Close Icon Color', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Here you can define custom color for your popup close icon.', 'squad-modules-for-divi' ),
					'default'     => '#ffffff',
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'popup',
				)
			),
		);

		$text = array(
			'use_text_box'    => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Use Text Box', 'squad-modules-for-divi' ),
				array(
					'description'         => esc_html__( 'Here you can choose whether overlay image should be used.', 'squad-modules-for-divi' ),
					'depends_show_if_not' => array( 'icon' ),
					'affects'             => array(
						'text_box_height',
						'text_box_width',
						'text_box_bg',
						'text_box_radius',
					),
					'tab_slug'            => 'advanced',
					'toggle_slug'         => 'text',
				)
			),
			'text_box_height' => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Text Box Height', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Here you can define static height for your text box.', 'squad-modules-for-divi' ),
					'range_settings'  => array(
						'min'  => 0,
						'max'  => 300,
						'step' => 1,
					),
					'default'         => '80px',
					'default_unit'    => 'px',
					'depends_show_if' => 'on',
					'tab_slug'        => 'advanced',
					'toggle_slug'     => 'text',
					'mobile_options'  => true,
				)
			),
			'text_box_width'  => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Text Box Width', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Here you can define static width for your text box.', 'squad-modules-for-divi' ),
					'range_settings'  => array(
						'min'  => 0,
						'max'  => 300,
						'step' => 1,
					),
					'default'         => '80px',
					'default_unit'    => 'px',
					'depends_show_if' => 'on',
					'tab_slug'        => 'advanced',
					'toggle_slug'     => 'text',
					'mobile_options'  => true,
				)
			),
			'text_box_bg'     => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Text Box Background', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Here you can define custom background for your text box.', 'squad-modules-for-divi' ),
					'default'         => et_builder_accent_color(),
					'depends_show_if' => 'on',
					'tab_slug'        => 'advanced',
					'toggle_slug'     => 'text',
					'hover'           => 'tabs',
				)
			),
			'text_box_radius' => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Text Box Border Radius', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Here you can define the radius value for your text box border.', 'squad-modules-for-divi' ),
					'range_settings'  => array(
						'min'  => 0,
						'max'  => 400,
						'step' => 1,
					),
					'default'         => '0px',
					'default_unit'    => 'px',
					'depends_show_if' => 'on',
					'tab_slug'        => 'advanced',
					'toggle_slug'     => 'text',
				)
			),
		);

		$img_overlay = $this->squad_utils->field_definitions->add_background_field(
			array(
				'label'       => esc_html__( 'Image Overlay', 'squad-modules-for-divi' ),
				'base_name'   => 'image_overlay_background',
				'context'     => 'image_overlay_background_color',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'image',
			)
		);

		return array_merge( $fields, $img_overlay, $text );
	}

	/**
	 * Get CSS fields transition.
	 *
	 * Add form field options group and background image on the field list.
	 *
	 * @since 1.4.1
	 */
	public function get_transition_fields_css_props() {
		$fields = parent::get_transition_fields_css_props();

		$fields['image_overlay_background_color'] = array( 'background' => "$this->main_css_element div .video-popup-figure:before" );

		$fields['icon_color']   = array( 'fill' => "$this->main_css_element div .video-popup .video-popup-icon svg" );
		$fields['icon_bg']      = array( 'background-color' => "$this->main_css_element div .video-popup .video-popup-icon" );
		$fields['icon_size']    = array( 'width' => "$this->main_css_element div .video-popup .video-popup-icon svg" );
		$fields['icon_opacity'] = array( 'opacity' => "$this->main_css_element div .video-popup .video-popup-icon svg" );
		$fields['text_box_bg']  = array( 'background-color' => "$this->main_css_element div .video-popup .video-popup-text" );

		divi_squad()->d4_module_helper->fix_fonts_transition( $fields, 'trigger', "$this->main_css_element div .video-popup .video-popup-text" );

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
		$inline_modal = '';
		$image        = $this->props['image'];
		$image_alt    = $this->props['alt'];
		$video_link   = $this->props['video_link'];
		$type         = $this->props['type'];
		$video        = $this->props['video'];
		$img_overlay  = '';
		$order_class  = (string) self::get_module_order_class( $render_slug );
		$order_number = str_replace( array( $this->slug, '_' ), '', $order_class );
		$data_modal   = 'video' === $type ? sprintf( 'data-mfp-src="#squad-vp-modal-video-popup-%1$s"', $order_number ) : '';

		// Enqueue scripts.
		wp_enqueue_style( 'magnific-popup' );
		wp_enqueue_script( 'squad-module-video-popup' );

		// Set popup style.
		self::set_style(
			$render_slug,
			array(
				'selector'    => ".squad-vp-modal-open.squad-vp-video-popup-$order_number .mfp-bg",
				'declaration' => sprintf( 'opacity:1!important;background: %1$s!important;', $this->prop( 'popup_bg', '' ) ),
			)
		);
		self::set_style(
			$render_slug,
			array(
				'selector'    => ".squad-vp-modal-open.squad-vp-video-popup-$order_number .mfp-iframe-holder .mfp-close",
				'declaration' => sprintf( 'color: %1$s!important;', $this->prop( 'close_icon_color', '' ) ),
			)
		);

		if ( 'video' === $type ) {
			$inline_modal = sprintf(
				'<div class="mfp-hide squad-vp-modal" id="squad-vp-modal-video-popup-%1$s" data-order="%1$s"><div class="video-wrap"><video controls><source type="video/mp4" src="%2$s"></video></div></div>',
				$order_number,
				$video
			);
		}

		if ( 'on' === $this->prop( 'use_overlay', 'off' ) ) {
			$img_overlay = sprintf( '<div class="video-popup-figure"><img src="%1$s" alt="%2$s"/></div>', $image, $image_alt );
		}

		if ( Str::contains( $video_link, 'youtu.be' ) ) {
			$video_link = str_replace( 'youtu.be/', 'youtube.com/watch?v=', $video_link );
		}

		$this->generate_additional_styles( $attrs );

		return sprintf(
			'<div class="video-popup"> %5$s <div class="video-popup-wrap"> <a class="video-popup-trigger popup-%6$s" data-order="%4$s" data-type="%6$s" href="%3$s" %7$s>%1$s</a></div>%2$s</div>',
			$this->render_trigger(),
			$img_overlay,
			$video_link,
			$order_number,
			$inline_modal,
			$type,
			$data_modal
		);
	}

	/**
	 * Renders additional styles for the module output.
	 *
	 * @param array $attrs List of attributes.
	 *
	 * @return void
	 */
	private function generate_additional_styles( array $attrs ): void {
		// Fixed: the custom background doesn't work at frontend.
		$this->props = array_merge( $attrs, $this->props );

		$this->generate_styles(
			array(
				'base_attr_name' => 'icon_alignment',
				'selector'       => "$this->main_css_element div .video-popup-trigger",
				'hover_selector' => "$this->main_css_element:hover div .video-popup-trigger",
				'css_property'   => 'justify-content',
				'render_slug'    => $this->slug,
				'type'           => 'align',
			)
		);

		if ( 'on' === $this->prop( 'use_overlay', 'on' ) ) {
			et_pb_background_options()->get_background_style(
				array(
					'base_prop_name'         => 'image_overlay_background',
					'props'                  => $this->props,
					'selector'               => "$this->main_css_element div .video-popup-figure:before",
					'selector_hover'         => "$this->main_css_element:hover div .video-popup-figure:before",
					'selector_sticky'        => "$this->main_css_element div .video-popup-figure:before",
					'function_name'          => $this->slug,
					'use_background_video'   => false,
					'use_background_pattern' => false,
					'use_background_mask'    => false,
					'prop_name_aliases'      => array(
						'use_image_overlay_background_color_gradient' => 'image_overlay_background_use_color_gradient',
						'image_overlay_background'                    => 'image_overlay_background_color',
					),
				)
			);

			$this->generate_styles(
				array(
					'base_attr_name' => 'img_height',
					'selector'       => "$this->main_css_element div .video-popup-figure",
					'css_property'   => 'height',
					'render_slug'    => $this->slug,
					'type'           => 'range',
				)
			);

			self::set_style(
				$this->slug,
				array(
					'selector'    => "$this->main_css_element div .video-popup-trigger",
					'declaration' => 'content: ""; position: absolute; top: 0; left: 0; right: 0; bottom: 0; z-index: 99;',
				)
			);
		}

		if ( 'text' !== $this->prop( 'trigger_element', 'icon' ) ) {
			$this->generate_styles(
				array(
					'base_attr_name' => 'icon_color',
					'selector'       => "$this->main_css_element div .video-popup .video-popup-icon svg",
					'hover_selector' => "$this->main_css_element:hover div .video-popup .video-popup-icon svg",
					'css_property'   => 'fill',
					'render_slug'    => $this->slug,
					'type'           => 'color',
				)
			);
			$this->generate_styles(
				array(
					'base_attr_name' => 'icon_bg',
					'selector'       => "$this->main_css_element div .video-popup .video-popup-icon",
					'hover_selector' => "$this->main_css_element:hover div .video-popup .video-popup-icon",
					'css_property'   => 'background-color',
					'render_slug'    => $this->slug,
					'type'           => 'color',
				)
			);
			$this->generate_styles(
				array(
					'base_attr_name' => 'icon_size',
					'selector'       => "$this->main_css_element div .video-popup .video-popup-icon svg",
					'hover_selector' => "$this->main_css_element:hover div .video-popup .video-popup-icon svg",
					'css_property'   => 'width',
					'render_slug'    => $this->slug,
					'type'           => 'range',
				)
			);
			$this->generate_styles(
				array(
					'base_attr_name' => 'icon_opacity',
					'selector'       => "$this->main_css_element div .video-popup-icon .video-popup-icon svg",
					'hover_selector' => "$this->main_css_element:hover div .video-popup-icon .video-popup-icon svg",
					'css_property'   => 'opacity',
					'render_slug'    => $this->slug,
					'type'           => 'number',
				)
			);
			$this->generate_styles(
				array(
					'base_attr_name' => 'icon_width',
					'selector'       => "$this->main_css_element div .video-popup .video-popup-icon",
					'hover_selector' => "$this->main_css_element:hover div .video-popup .video-popup-icon",
					'css_property'   => 'width',
					'render_slug'    => $this->slug,
					'type'           => 'range',
				)
			);
			$this->generate_styles(
				array(
					'base_attr_name' => 'icon_height',
					'selector'       => "$this->main_css_element div .video-popup .video-popup-icon",
					'hover_selector' => "$this->main_css_element:hover div .video-popup .video-popup-icon",
					'css_property'   => 'height',
					'render_slug'    => $this->slug,
					'type'           => 'range',
				)
			);
			$this->generate_styles(
				array(
					'base_attr_name' => 'icon_radius',
					'selector'       => "$this->main_css_element div .video-popup .video-popup-icon",
					'hover_selector' => "$this->main_css_element:hover div .video-popup .video-popup-icon",
					'css_property'   => 'border-radius',
					'render_slug'    => $this->slug,
					'type'           => 'range',
				)
			);
		}

		if ( ( 'icon' !== $this->prop( 'trigger_element', 'icon' ) ) && 'on' === $this->prop( 'use_text_box', 'off' ) ) {
			$this->generate_styles(
				array(
					'base_attr_name' => 'text_box_width',
					'selector'       => "$this->main_css_element div .video-popup .video-popup-text",
					'hover_selector' => "$this->main_css_element:hover div .video-popup .video-popup-text",
					'css_property'   => 'width',
					'render_slug'    => $this->slug,
					'type'           => 'range',
				)
			);
			$this->generate_styles(
				array(
					'base_attr_name' => 'text_box_height',
					'selector'       => "$this->main_css_element div .video-popup .video-popup-text",
					'hover_selector' => "$this->main_css_element:hover div .video-popup .video-popup-text",
					'css_property'   => 'height',
					'render_slug'    => $this->slug,
					'type'           => 'range',
				)
			);
			$this->generate_styles(
				array(
					'base_attr_name' => 'text_box_bg',
					'selector'       => "$this->main_css_element div .video-popup .video-popup-text",
					'hover_selector' => "$this->main_css_element:hover div .video-popup .video-popup-text",
					'css_property'   => 'background-color',
					'render_slug'    => $this->slug,
					'type'           => 'color',
				)
			);
			$this->generate_styles(
				array(
					'base_attr_name' => 'text_box_radius',
					'selector'       => "$this->main_css_element div .video-popup .video-popup-text",
					'hover_selector' => "$this->main_css_element:hover div .video-popup .video-popup-text",
					'css_property'   => 'border-radius',
					'render_slug'    => $this->slug,
					'type'           => 'range',
				)
			);
		}

		if ( 'icon_text' === $this->prop( 'trigger_element', 'icon' ) ) {
			$this->generate_styles(
				array(
					'base_attr_name' => 'icon_spacing',
					'selector'       => "$this->main_css_element div .video-popup-icon",
					'css_property'   => 'margin-right',
					'render_slug'    => $this->slug,
					'type'           => 'range',
				)
			);
		}

		// Animation.
		if ( 'on' === $this->prop( 'use_animation', 'off' ) ) {
			$selector = "$this->main_css_element .video-popup a:after";
			if ( 'icon_text' === $this->prop( 'trigger_element', 'icon' ) ) {
				$selector = "$this->main_css_element div .video-popup .video-popup-icon:after";
			}

			if ( 'icon' !== $this->prop( 'trigger_element', 'icon' ) ) {
				$this->generate_styles(
					array(
						'base_attr_name' => 'text_box_radius',
						'selector'       => $selector,
						'css_property'   => 'border-radius',
						'render_slug'    => $this->slug,
						'type'           => 'range',
					)
				);
			}

			if ( 'text' !== $this->prop( 'trigger_element', 'icon' ) ) {
				$this->generate_styles(
					array(
						'base_attr_name' => 'icon_radius',
						'selector'       => $selector,
						'css_property'   => 'border-radius',
						'render_slug'    => $this->slug,
						'type'           => 'range',
					)
				);
			}

			self::set_style(
				$this->slug,
				array(
					'selector'    => $selector,
					'declaration' => sprintf(
						'content: "";
						-webkit-box-shadow: 0 0 0 15px %1$s, 0 0 0 30px %1$s, 0 0 0 45px %1$s;
						box-shadow: 0 0 0 15px %1$s, 0 0 0 30px %1$s, 0 0 0 45px %1$s;',
						$this->prop( 'wave_bg', '' )
					),
				)
			);
		}
	}

	/**
	 * Generate render trigger.
	 *
	 * @return string
	 */
	protected function render_trigger(): string {
		// Icon output html variable.
		$icon_output_html = '';

		// Trigger element.
		$trigger_element = $this->prop( 'trigger_element', 'icon' );

		// Generate svg icon.
		if ( in_array( $trigger_element, array( 'icon', 'icon_text' ), true ) ) {
			// Load the image loader.
			$image = divi_squad()->load_image( '/build/admin/images/icons' );

			// Check if image is validated.
			if ( ! $image->is_path_validated() ) {
				return $icon_output_html;
			}

			// Define images.
			$images = array(
				'1' => 'arrow-outline.svg',
				'2' => 'arrow-filled.svg',
				'3' => 'arrow-triangle.svg',
				'4' => 'arrow-circle.svg',
				'5' => 'arrow-circle-triangle.svg',
				'6' => 'arrow-rectangle-round.svg',
			);

			$svg_image_id  = $this->prop( 'icon', '1' );
			$svg_image_raw = $image->get_image( $images[ $svg_image_id ], 'svg', false );
			if ( ! is_wp_error( $svg_image_raw ) ) {
				$icon_output_html = sprintf( '<span class="video-popup-icon">%1$s</span>', $svg_image_raw );
			}
		}

		// Generate text.
		if ( in_array( $trigger_element, array( 'text', 'icon_text' ), true ) ) {
			$icon_output_html .= sprintf( '<span class="video-popup-text">%1$s</span>', $this->props['text'] );
		}

		return $icon_output_html;
	}
}
