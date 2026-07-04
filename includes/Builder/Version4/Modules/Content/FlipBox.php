<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Flip Box Module Class which extend the Divi Builder Module Class.
 *
 * This class provides content adding functionalities for Flip Box in the visual builder.
 *
 * @since   1.0.0
 * @author  The WP Squad <support@squadmodules.com>
 * @package DiviSquad
 */

namespace DiviSquad\Builder\Version4\Modules\Content;

use DiviSquad\Builder\Version4\Abstracts\Module;
use DiviSquad\Utils\Divi;
use ET_Builder_Module_Helper_MultiViewOptions;
use function apply_filters;
use function esc_attr__;
use function esc_html__;
use function et_builder_get_text_orientation_options;
use function et_pb_background_options;
use function et_pb_get_extended_font_icon_value;
use function et_pb_media_options;
use function et_pb_multi_view_options;
use function wp_kses_post;

/**
 * Flip Box Module Class.
 *
 * @since   1.0.0
 * @package DiviSquad
 */
class FlipBox extends Module {
	/**
	 * Initiate Module.
	 * Set the module name on init.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init(): void {
		$this->name      = esc_html__( 'Flip Box', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Flip Boxes', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'flip-box.svg' );

		$this->slug             = 'disq_flip_box';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.$this->slug";

		$this->child_title_var          = 'title';
		$this->child_title_fallback_var = 'admin_label';

		// Connect with utils.
		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		$sub_toggles_list = array(
			'front_side' => array(
				'name' => esc_html__( 'Front', 'squad-modules-for-divi' ),
			),
			'back_side'  => array(
				'name' => esc_html__( 'Back', 'squad-modules-for-divi' ),
			),
		);

		// Declare settings modal toggles for the module.
		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'main_content'         => array(
						'title'             => esc_html__( 'Content', 'squad-modules-for-divi' ),
						'tabbed_subtoggles' => true,
						'sub_toggles'       => $sub_toggles_list,
					),
					'image_icon'           => array(
						'title'             => esc_html__( 'Image & Icon', 'squad-modules-for-divi' ),
						'tabbed_subtoggles' => true,
						'sub_toggles'       => $sub_toggles_list,
					),
					'front_button_element' => esc_html__( 'Front Button', 'squad-modules-for-divi' ),
					'back_button_element'  => esc_html__( 'Back Button', 'squad-modules-for-divi' ),
					'flip_settings'        => esc_html__( 'Flip Settings', 'squad-modules-for-divi' ),
					'fip_animations'       => esc_html__( 'Flip Animation', 'squad-modules-for-divi' ),
					'item_order_element'   => array(
						'title'             => esc_html__( 'Item Order', 'squad-modules-for-divi' ),
						'tabbed_subtoggles' => true,
						'sub_toggles'       => $sub_toggles_list,
					),
					'item_z_index_element' => array(
						'title'             => esc_html__( 'Item Z Index', 'squad-modules-for-divi' ),
						'tabbed_subtoggles' => true,
						'sub_toggles'       => $sub_toggles_list,
					),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'front_wrapper'         => esc_html__( 'Front Wrapper', 'squad-modules-for-divi' ),
					'back_wrapper'          => esc_html__( 'Back Wrapper', 'squad-modules-for-divi' ),
					'image_icon'            => array(
						'title'             => esc_html__( 'Image & Icon', 'squad-modules-for-divi' ),
						'tabbed_subtoggles' => true,
						'sub_toggles'       => $sub_toggles_list,
					),
					'icon_text'             => array(
						'title'             => esc_html__( 'Icon Text', 'squad-modules-for-divi' ),
						'tabbed_subtoggles' => true,
						'sub_toggles'       => $sub_toggles_list,
					),
					'title_text'            => array(
						'title'             => esc_html__( 'Title Text', 'squad-modules-for-divi' ),
						'tabbed_subtoggles' => true,
						'sub_toggles'       => $sub_toggles_list,
					),
					'sub_title_text'        => array(
						'title'             => esc_html__( 'Sub Title Text', 'squad-modules-for-divi' ),
						'tabbed_subtoggles' => true,
						'sub_toggles'       => $sub_toggles_list,
					),
					'front_content_text'    => array(
						'title'             => esc_html__( 'Front Body Text', 'squad-modules-for-divi' ),
						'tabbed_subtoggles' => true,
						'sub_toggles'       => divi_squad()->d4_module_helper->get_block_elements(),
					),
					'back_content_text'     => array(
						'title'             => esc_html__( 'Back Body Text', 'squad-modules-for-divi' ),
						'tabbed_subtoggles' => true,
						'sub_toggles'       => divi_squad()->d4_module_helper->get_block_elements(),
					),
					'front_content_heading' => array(
						'title'             => esc_html__( 'Front Body Heading Text', 'squad-modules-for-divi' ),
						'tabbed_subtoggles' => true,
						'sub_toggles'       => divi_squad()->d4_module_helper->get_heading_elements(),
					),
					'back_content_heading'  => array(
						'title'             => esc_html__( 'Back Body Heading Text', 'squad-modules-for-divi' ),
						'tabbed_subtoggles' => true,
						'sub_toggles'       => divi_squad()->d4_module_helper->get_heading_elements(),
					),
					'front_button_element'  => esc_html__( 'Front Button', 'squad-modules-for-divi' ),
					'back_button_element'   => esc_html__( 'Back Button', 'squad-modules-for-divi' ),
					'button_text'           => array(
						'title'             => esc_html__( 'Button Text', 'squad-modules-for-divi' ),
						'tabbed_subtoggles' => true,
						'sub_toggles'       => $sub_toggles_list,
					),
					'custom_margin_padding' => array(
						'title'             => esc_html__( 'Custom Spacing', 'squad-modules-for-divi' ),
						'priority'          => 90,
						'tabbed_subtoggles' => true,
						'sub_toggles'       => $sub_toggles_list,
					),
				),
			),
		);

		// Declare advanced fields for the module.
		$this->advanced_fields = array(
			'fonts'          => array(
				'front_icon_text'         => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Icon', 'squad-modules-for-divi' ),
					array(
						'text_color'      => array(
							'hover' => false,
						),
						'font_size'       => array(
							'default' => '16px',
							'hover'   => false,
						),
						'letter_spacing'  => array(
							'hover' => false,
						),
						'line_height'     => array(
							'hover' => false,
						),
						'text_shadow'     => array(
							'hover'   => false,
							'show_if' => array(
								'front_icon_type' => 'text',
							),
						),
						'hide_text_align' => true,
						'css'             => array(
							'main'  => "$this->main_css_element div .flip-box-slides .front-slide .squad-icon-wrapper .slide-icon-text",
							'hover' => "$this->main_css_element div .flip-box-slides .front-slide:hover .squad-icon-wrapper .slide-icon-text",
						),
						'depends_show_if' => 'text',
						'tab_slug'        => 'advanced',
						'toggle_slug'     => 'icon_text',
						'sub_toggle'      => 'front_side',
					)
				),
				'back_icon_text'          => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Icon', 'squad-modules-for-divi' ),
					array(
						'text_color'      => array(
							'hover' => false,
						),
						'font_size'       => array(
							'default' => '16px',
							'hover'   => false,
						),
						'letter_spacing'  => array(
							'hover' => false,
						),
						'line_height'     => array(
							'hover' => false,
						),
						'text_shadow'     => array(
							'hover'   => false,
							'show_if' => array(
								'back_icon_type' => 'text',
							),
						),
						'hide_text_align' => true,
						'css'             => array(
							'main'  => "$this->main_css_element div .flip-box-slides .back-slide .squad-icon-wrapper .slide-icon-text",
							'hover' => "$this->main_css_element div .flip-box-slides .back-slide:hover .squad-icon-wrapper .slide-icon-text",
						),
						'depends_show_if' => 'text',
						'tab_slug'        => 'advanced',
						'toggle_slug'     => 'icon_text',
						'sub_toggle'      => 'back_side',
					)
				),
				'front_title_text'        => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Title', 'squad-modules-for-divi' ),
					array(
						'text_color'     => array(
							'hover' => false,
						),
						'font_size'      => array(
							'default' => '26px',
							'hover'   => false,
						),
						'letter_spacing' => array(
							'hover' => false,
						),
						'line_height'    => array(
							'hover' => false,
						),
						'text_shadow'    => array(
							'hover' => false,
						),
						'css'            => array(
							'main'  => "$this->main_css_element div .flip-box-slides .front-slide .slide-title-text",
							'hover' => "$this->main_css_element div .flip-box-slides .front-slide:hover .slide-title-text",
						),
						'tab_slug'       => 'advanced',
						'toggle_slug'    => 'title_text',
						'sub_toggle'     => 'front_side',
					)
				),
				'back_title_text'         => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Title', 'squad-modules-for-divi' ),
					array(
						'text_color'     => array(
							'hover' => false,
						),
						'font_size'      => array(
							'default' => '26px',
							'hover'   => false,
						),
						'letter_spacing' => array(
							'hover' => false,
						),
						'line_height'    => array(
							'hover' => false,
						),
						'text_shadow'    => array(
							'hover' => false,
						),
						'css'            => array(
							'main'  => "$this->main_css_element div .flip-box-slides .back-slide .slide-title-text",
							'hover' => "$this->main_css_element div .flip-box-slides .back-slide:hover .slide-title-text",
						),
						'tab_slug'       => 'advanced',
						'toggle_slug'    => 'title_text',
						'sub_toggle'     => 'back_side',
					)
				),
				'front_sub_title_text'    => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Sub Title', 'squad-modules-for-divi' ),
					array(
						'text_color'     => array(
							'hover' => false,
						),
						'font_size'      => array(
							'default' => '16px',
							'hover'   => false,
						),
						'letter_spacing' => array(
							'hover' => false,
						),
						'line_height'    => array(
							'hover' => false,
						),
						'text_shadow'    => array(
							'hover' => false,
						),
						'css'            => array(
							'main'  => "$this->main_css_element div .flip-box-slides .front-slide .slide-sub-title-text",
							'hover' => "$this->main_css_element div .flip-box-slides .front-slide:hover .slide-sub-title-text",
						),
						'tab_slug'       => 'advanced',
						'toggle_slug'    => 'sub_title_text',
						'sub_toggle'     => 'front_side',
					)
				),
				'back_sub_title_text'     => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Sub Title', 'squad-modules-for-divi' ),
					array(
						'text_color'     => array(
							'hover' => false,
						),
						'font_size'      => array(
							'default' => '16px',
							'hover'   => false,
						),
						'letter_spacing' => array(
							'hover' => false,
						),
						'line_height'    => array(
							'hover' => false,
						),
						'text_shadow'    => array(
							'hover' => false,
						),
						'css'            => array(
							'main'  => "$this->main_css_element div .flip-box-slides .back-slide .slide-sub-title-text",
							'hover' => "$this->main_css_element div .flip-box-slides .back-slide:hover .slide-sub-title-text",
						),
						'tab_slug'       => 'advanced',
						'toggle_slug'    => 'sub_title_text',
						'sub_toggle'     => 'back_side',
					)
				),
				'front_content_text'      => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Body', 'squad-modules-for-divi' ),
					array(
						'font_weight'    => array(
							'default' => '400',
						),
						'text_color'     => array(
							'hover' => false,
						),
						'font_size'      => array(
							'default' => '16px',
							'hover'   => false,
						),
						'letter_spacing' => array(
							'hover' => false,
						),
						'line_height'    => array(
							'default' => '1.7',
							'hover'   => false,
						),
						'text_shadow'    => array(
							'hover' => false,
						),
						'css'            => array(
							'main'  => implode(
								', ',
								array(
									"$this->main_css_element div .flip-box-slides .front-slide .slide-content-text",
									"$this->main_css_element div .flip-box-slides .front-slide .slide-content-text p",
								)
							),
							'hover' => implode(
								', ',
								array(
									"$this->main_css_element div .flip-box-slides .front-slide:hover .slide-content-text",
									"$this->main_css_element div .flip-box-slides .front-slide:hover .slide-content-text p",
								)
							),
						),
						'block_elements' => array(
							'tabbed_subtoggles' => true,
							'bb_icons_support'  => true,
							'css'               => array(
								'main'  => "$this->main_css_element div .flip-box-slides .front-slide .slide-content-text",
								'hover' => "$this->main_css_element div .flip-box-slides .front-slide:hover .slide-content-text",
							),
						),
						'tab_slug'       => 'advanced',
						'toggle_slug'    => 'front_content_text',
					)
				),
				'back_content_text'       => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Body', 'squad-modules-for-divi' ),
					array(
						'font_weight'    => array(
							'default' => '400',
						),
						'text_color'     => array(
							'hover' => false,
						),
						'font_size'      => array(
							'default' => '16px',
							'hover'   => false,
						),
						'letter_spacing' => array(
							'hover' => false,
						),
						'line_height'    => array(
							'default' => '1.7',
							'hover'   => false,
						),
						'text_shadow'    => array(
							'hover' => false,
						),
						'css'            => array(
							'main'  => implode(
								', ',
								array(
									"$this->main_css_element div .flip-box-slides .back-slide .slide-content-text",
									"$this->main_css_element div .flip-box-slides .back-slide .slide-content-text p",
								)
							),
							'hover' => implode(
								', ',
								array(
									"$this->main_css_element div .flip-box-slides .back-slide:hover .slide-content-text",
									"$this->main_css_element div .flip-box-slides .back-slide:hover .slide-content-text p",
								)
							),
						),
						'block_elements' => array(
							'tabbed_subtoggles' => true,
							'bb_icons_support'  => true,
							'css'               => array(
								'main'  => "$this->main_css_element div .flip-box .flip-box-slides .back-slide .slide-content-text",
								'hover' => "$this->main_css_element div .flip-box .flip-box-slides .back-slide:hover .slide-content-text",
							),
						),
						'tab_slug'       => 'advanced',
						'toggle_slug'    => 'back_content_text',
					)
				),
				'front_content_heading_1' => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Heading 1', 'squad-modules-for-divi' ),
					array(
						'text_color'     => array(
							'hover' => false,
						),
						'font_size'      => array(
							'default' => absint( et_get_option( 'body_header_size', '30' ) ) . 'px',
							'hover'   => false,
						),
						'letter_spacing' => array(
							'hover' => false,
						),
						'line_height'    => array(
							'hover' => false,
						),
						'text_shadow'    => array(
							'hover' => false,
						),
						'css'            => array(
							'main'  => "$this->main_css_element div .flip-box-slides .front-slide .slide-content-text h1",
							'hover' => "$this->main_css_element div .flip-box-slides .front-slide:hover .slide-content-text h1",
						),
						'tab_slug'       => 'advanced',
						'toggle_slug'    => 'front_content_heading',
						'sub_toggle'     => 'h1',
					)
				),
				'back_content_heading_1'  => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Heading 1', 'squad-modules-for-divi' ),
					array(
						'text_color'     => array(
							'hover' => false,
						),
						'font_size'      => array(
							'default' => absint( et_get_option( 'body_header_size', '30' ) ) . 'px',
							'hover'   => false,
						),
						'letter_spacing' => array(
							'hover' => false,
						),
						'line_height'    => array(
							'hover' => false,
						),
						'text_shadow'    => array(
							'hover' => false,
						),
						'css'            => array(
							'main'  => "$this->main_css_element div .flip-box-slides .back-slide .slide-content-text h1",
							'hover' => "$this->main_css_element div .flip-box-slides .back-slide:hover .slide-content-text h1",
						),
						'tab_slug'       => 'advanced',
						'toggle_slug'    => 'back_content_heading',
						'sub_toggle'     => 'h1',
					)
				),
				'front_content_heading_2' => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Heading 2', 'squad-modules-for-divi' ),
					array(
						'text_color'     => array(
							'hover' => false,
						),
						'font_size'      => array(
							'default' => '26px',
							'hover'   => false,
						),
						'letter_spacing' => array(
							'hover' => false,
						),
						'line_height'    => array(
							'hover' => false,
						),
						'text_shadow'    => array(
							'hover' => false,
						),
						'css'            => array(
							'main'  => "$this->main_css_element div .flip-box-slides .front-slide .slide-content-text h2",
							'hover' => "$this->main_css_element div .flip-box-slides .front-slide:hover .slide-content-text h2",
						),
						'tab_slug'       => 'advanced',
						'toggle_slug'    => 'front_content_heading',
						'sub_toggle'     => 'h2',
					)
				),
				'back_content_heading_2'  => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Heading 2', 'squad-modules-for-divi' ),
					array(
						'text_color'     => array(
							'hover' => false,
						),
						'font_size'      => array(
							'default' => '26px',
							'hover'   => false,
						),
						'letter_spacing' => array(
							'hover' => false,
						),
						'line_height'    => array(
							'hover' => false,
						),
						'text_shadow'    => array(
							'hover' => false,
						),
						'css'            => array(
							'main'  => "$this->main_css_element div .flip-box-slides .back-slide .slide-content-text h2",
							'hover' => "$this->main_css_element div .flip-box-slides .back-slide:hover .slide-content-text h2",
						),
						'tab_slug'       => 'advanced',
						'toggle_slug'    => 'back_content_heading',
						'sub_toggle'     => 'h2',
					)
				),
				'front_content_heading_3' => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Heading 3', 'squad-modules-for-divi' ),
					array(
						'text_color'     => array(
							'hover' => false,
						),
						'font_size'      => array(
							'default' => '22px',
							'hover'   => false,
						),
						'letter_spacing' => array(
							'hover' => false,
						),
						'line_height'    => array(
							'hover' => false,
						),
						'text_shadow'    => array(
							'hover' => false,
						),
						'css'            => array(
							'main'  => "$this->main_css_element div .flip-box-slides .front-slide .slide-content-text h3",
							'hover' => "$this->main_css_element div .flip-box-slides .front-slide:hover .slide-content-text h3",
						),
						'tab_slug'       => 'advanced',
						'toggle_slug'    => 'front_content_heading',
						'sub_toggle'     => 'h3',
					)
				),
				'back_content_heading_3'  => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Heading 3', 'squad-modules-for-divi' ),
					array(
						'text_color'     => array(
							'hover' => false,
						),
						'font_size'      => array(
							'default' => '22px',
							'hover'   => false,
						),
						'letter_spacing' => array(
							'hover' => false,
						),
						'line_height'    => array(
							'hover' => false,
						),
						'text_shadow'    => array(
							'hover' => false,
						),
						'css'            => array(
							'main'  => "$this->main_css_element div .flip-box-slides .back-slide .slide-content-text h3",
							'hover' => "$this->main_css_element div .flip-box-slides .back-slide:hover .slide-content-text h3",
						),
						'tab_slug'       => 'advanced',
						'toggle_slug'    => 'back_content_heading',
						'sub_toggle'     => 'h3',
					)
				),
				'front_content_heading_4' => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Heading 4', 'squad-modules-for-divi' ),
					array(
						'text_color'     => array(
							'hover' => false,
						),
						'font_size'      => array(
							'default' => '18px',
							'hover'   => false,
						),
						'letter_spacing' => array(
							'hover' => false,
						),
						'line_height'    => array(
							'hover' => false,
						),
						'text_shadow'    => array(
							'hover' => false,
						),
						'css'            => array(
							'main'  => "$this->main_css_element div .flip-box-slides .front-slide .slide-content-text h4",
							'hover' => "$this->main_css_element div .flip-box-slides .front-slide:hover .slide-content-text h4",
						),
						'tab_slug'       => 'advanced',
						'toggle_slug'    => 'front_content_heading',
						'sub_toggle'     => 'h4',
					)
				),
				'back_content_heading_4'  => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Heading 4', 'squad-modules-for-divi' ),
					array(
						'text_color'     => array(
							'hover' => false,
						),
						'font_size'      => array(
							'default' => '18px',
							'hover'   => false,
						),
						'letter_spacing' => array(
							'hover' => false,
						),
						'line_height'    => array(
							'hover' => false,
						),
						'text_shadow'    => array(
							'hover' => false,
						),
						'css'            => array(
							'main'  => "$this->main_css_element div .flip-box-slides .back-slide .slide-content-text h4",
							'hover' => "$this->main_css_element div .flip-box-slides .back-slide:hover .slide-content-text h4",
						),
						'tab_slug'       => 'advanced',
						'toggle_slug'    => 'back_content_heading',
						'sub_toggle'     => 'h4',
					)
				),
				'front_content_heading_5' => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Heading 5', 'squad-modules-for-divi' ),
					array(
						'text_color'     => array(
							'hover' => false,
						),
						'font_size'      => array(
							'default' => '16px',
							'hover'   => false,
						),
						'letter_spacing' => array(
							'hover' => false,
						),
						'line_height'    => array(
							'hover' => false,
						),
						'text_shadow'    => array(
							'hover' => false,
						),
						'css'            => array(
							'main'  => "$this->main_css_element div .flip-box-slides .front-slide .slide-content-text h5",
							'hover' => "$this->main_css_element div .flip-box-slides .front-slide:hover .slide-content-text h5",
						),
						'tab_slug'       => 'advanced',
						'toggle_slug'    => 'front_content_heading',
						'sub_toggle'     => 'h5',
					)
				),
				'back_content_heading_5'  => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Heading 5', 'squad-modules-for-divi' ),
					array(
						'text_color'     => array(
							'hover' => false,
						),
						'font_size'      => array(
							'default' => '16px',
							'hover'   => false,
						),
						'letter_spacing' => array(
							'hover' => false,
						),
						'line_height'    => array(
							'hover' => false,
						),
						'text_shadow'    => array(
							'hover' => false,
						),
						'css'            => array(
							'main'  => "$this->main_css_element div .flip-box-slides .back-slide .slide-content-text h5",
							'hover' => "$this->main_css_element div .flip-box-slides .back-slide:hover .slide-content-text h5",
						),
						'tab_slug'       => 'advanced',
						'toggle_slug'    => 'back_content_heading',
						'sub_toggle'     => 'h5',
					)
				),
				'front_content_heading_6' => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Heading 6', 'squad-modules-for-divi' ),
					array(
						'text_color'     => array(
							'hover' => false,
						),
						'font_size'      => array(
							'default' => '14px',
							'hover'   => false,
						),
						'letter_spacing' => array(
							'hover' => false,
						),
						'line_height'    => array(
							'hover' => false,
						),
						'text_shadow'    => array(
							'hover' => false,
						),
						'css'            => array(
							'main'  => "$this->main_css_element div .flip-box-slides .front-slide .slide-content-text h6",
							'hover' => "$this->main_css_element div .flip-box-slides .front-slide:hover .slide-content-text h6",
						),
						'tab_slug'       => 'advanced',
						'toggle_slug'    => 'front_content_heading',
						'sub_toggle'     => 'h6',
					)
				),
				'back_content_heading_6'  => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Heading 6', 'squad-modules-for-divi' ),
					array(
						'text_color'     => array(
							'hover' => false,
						),
						'font_size'      => array(
							'default' => '14px',
							'hover'   => false,
						),
						'letter_spacing' => array(
							'hover' => false,
						),
						'line_height'    => array(
							'hover' => false,
						),
						'text_shadow'    => array(
							'hover' => false,
						),
						'css'            => array(
							'main'  => "$this->main_css_element div .flip-box-slides .back-slide .slide-content-text h6",
							'hover' => "$this->main_css_element div .flip-box-slides .back-slide:hover .slide-content-text h6",
						),
						'tab_slug'       => 'advanced',
						'toggle_slug'    => 'back_content_heading',
						'sub_toggle'     => 'h6',
					)
				),
				'front_button_text'       => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Button', 'squad-modules-for-divi' ),
					array(
						'text_color'      => array(
							'hover' => false,
						),
						'font_size'       => array(
							'default' => '20px',
							'hover'   => false,
						),
						'letter_spacing'  => array(
							'hover' => false,
						),
						'line_height'     => array(
							'hover' => false,
						),
						'text_shadow'     => array(
							'hover'   => false,
							'show_if' => array(
								'front_button__enable' => 'on',
							),
						),
						'hide_text_align' => true,
						'css'             => array(
							'main'  => "$this->main_css_element div .flip-box-slides .front-slide .squad-slide-button",
							'hover' => "$this->main_css_element div .flip-box-slides .front-slide .squad-slide-button:hover",
						),
						'tab_slug'        => 'advanced',
						'toggle_slug'     => 'button_text',
						'sub_toggle'      => 'front_side',
					)
				),
				'back_button_text'        => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Button', 'squad-modules-for-divi' ),
					array(
						'font_weight'     => array(
							'default' => '400',
						),
						'font_size'       => array(
							'default' => '20px',
						),
						'text_shadow'     => array(
							'show_if' => array(
								'back_button__enable' => 'on',
							),
						),
						'hide_text_align' => true,
						'css'             => array(
							'main'  => "$this->main_css_element div .flip-box-slides .back-slide .squad-slide-button",
							'hover' => "$this->main_css_element div .flip-box-slides .back-slide .squad-slide-button:hover",
						),
						'tab_slug'        => 'advanced',
						'toggle_slug'     => 'button_text',
						'sub_toggle'      => 'back_side',
					)
				),
			),
			'borders'        => array(
				'default'              => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
				'front_wrapper'        => array(
					'label_prefix' => esc_html__( 'Wrapper', 'squad-modules-for-divi' ),
					'css'          => array(
						'main' => array(
							'border_radii'        => "$this->main_css_element div .flip-box-slides .front-slide",
							'border_radii_hover'  => "$this->main_css_element div .flip-box-slides .front-slide:hover",
							'border_styles'       => "$this->main_css_element div .flip-box-slides .front-slide",
							'border_styles_hover' => "$this->main_css_element div .flip-box-slides .front-slide:hover",
						),
					),
					'tab_slug'     => 'advanced',
					'toggle_slug'  => 'front_wrapper',
				),
				'back_wrapper'         => array(
					'label_prefix' => esc_html__( 'Wrapper', 'squad-modules-for-divi' ),
					'css'          => array(
						'main' => array(
							'border_radii'        => "$this->main_css_element div .flip-box-slides .back-slide",
							'border_radii_hover'  => "$this->main_css_element div .flip-box-slides .back-slide:hover",
							'border_styles'       => "$this->main_css_element div .flip-box-slides .back-slide",
							'border_styles_hover' => "$this->main_css_element div .flip-box-slides .back-slide:hover",
						),
					),
					'tab_slug'     => 'advanced',
					'toggle_slug'  => 'back_wrapper',
				),
				'front_image_icon'     => array(
					'label_prefix'        => et_builder_i18n( 'Icon' ),
					'css'                 => array(
						'main' => array(
							'border_radii'        => "$this->main_css_element div .flip-box-slides .front-slide .squad-icon-wrapper .slide-icon-image",
							'border_radii_hover'  => "$this->main_css_element div .flip-box-slides .front-slide:hover .squad-icon-wrapper .slide-icon-image",
							'border_styles'       => "$this->main_css_element div .flip-box-slides .front-slide .squad-icon-wrapper .slide-icon-image",
							'border_styles_hover' => "$this->main_css_element div .flip-box-slides .front-slide:hover .squad-icon-wrapper .slide-icon-image",
						),
					),
					'hover'               => false,
					'depends_on'          => array( 'front_icon_type' ),
					'depends_show_if_not' => array( 'none' ),
					'tab_slug'            => 'advanced',
					'toggle_slug'         => 'image_icon',
					'sub_toggle'          => 'front_side',
				),
				'back_image_icon'      => array(
					'label_prefix'        => et_builder_i18n( 'Icon' ),
					'css'                 => array(
						'main' => array(
							'border_radii'        => "$this->main_css_element div .flip-box-slides .back-slide .squad-icon-wrapper .slide-icon-image",
							'border_radii_hover'  => "$this->main_css_element div .flip-box-slides .back-slide:hover .squad-icon-wrapper .slide-icon-image",
							'border_styles'       => "$this->main_css_element div .flip-box-slides .back-slide .squad-icon-wrapper .slide-icon-image",
							'border_styles_hover' => "$this->main_css_element div .flip-box-slides .back-slide:hover .squad-icon-wrapper .slide-icon-image",
						),
					),
					'hover'               => false,
					'depends_on'          => array( 'back_icon_type' ),
					'depends_show_if_not' => array( 'none' ),
					'tab_slug'            => 'advanced',
					'toggle_slug'         => 'image_icon',
					'sub_toggle'          => 'back_side',
				),
				'front_button_element' => array(
					'label_prefix'    => esc_html__( 'Button', 'squad-modules-for-divi' ),
					'css'             => array(
						'main' => array(
							'border_radii'        => "$this->main_css_element div .flip-box-slides .back-slide .squad-slide-button",
							'border_radii_hover'  => "$this->main_css_element div .flip-box-slides .back-slide .squad-slide-button:hover",
							'border_styles'       => "$this->main_css_element div .flip-box-slides .back-slide .squad-slide-button",
							'border_styles_hover' => "$this->main_css_element div .flip-box-slides .back-slide .squad-slide-button:hover",
						),
					),
					'defaults'        => array(
						'border_radii'  => 'on|3px|3px|3px|3px',
						'border_styles' => array(
							'width' => '2px|2px|2px|2px',
							'color' => et_builder_accent_color(),
							'style' => 'solid',
						),
					),
					'hover'           => false,
					'depends_on'      => array( 'front_button__enable' ),
					'depends_show_if' => 'on',
					'tab_slug'        => 'advanced',
					'toggle_slug'     => 'front_button_element',
				),
				'back_button_element'  => array(
					'label_prefix'    => esc_html__( 'Button', 'squad-modules-for-divi' ),
					'css'             => array(
						'main' => array(
							'border_radii'        => "$this->main_css_element div .flip-box-slides .back-slide .squad-slide-button",
							'border_radii_hover'  => "$this->main_css_element div .flip-box-slides .back-slide .squad-slide-button:hover",
							'border_styles'       => "$this->main_css_element div .flip-box-slides .back-slide .squad-slide-button",
							'border_styles_hover' => "$this->main_css_element div .flip-box-slides .back-slide .squad-slide-button:hover",
						),
					),
					'defaults'        => array(
						'border_radii'  => 'on|3px|3px|3px|3px',
						'border_styles' => array(
							'width' => '2px|2px|2px|2px',
							'color' => et_builder_accent_color(),
							'style' => 'solid',
						),
					),
					'depends_on'      => array( 'back_button__enable' ),
					'depends_show_if' => 'on',
					'tab_slug'        => 'advanced',
					'toggle_slug'     => 'back_button_element',
				),
			),
			'box_shadow'     => array(
				'default'              => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
				'front_wrapper'        => array(
					'label'             => esc_html__( 'Wrapper Box Shadow', 'squad-modules-for-divi' ),
					'option_category'   => 'layout',
					'css'               => array(
						'main'  => "$this->main_css_element div .flip-box-slides .front-slide",
						'hover' => "$this->main_css_element div .flip-box-slides .front-slide:hover",
					),
					'default_on_fronts' => array(
						'color'    => 'rgba(0,0,0,0.3)',
						'position' => 'outer',
					),
					'hover'             => false,
					'tab_slug'          => 'advanced',
					'toggle_slug'       => 'front_wrapper',
				),
				'back_wrapper'         => array(
					'label'             => esc_html__( 'Wrapper Box Shadow', 'squad-modules-for-divi' ),
					'option_category'   => 'layout',
					'css'               => array(
						'main'  => "$this->main_css_element div .flip-box-slides .back-slide",
						'hover' => "$this->main_css_element div .flip-box-slides .back-slide:hover",
					),
					'default_on_fronts' => array(
						'color'    => 'rgba(0,0,0,0.3)',
						'position' => 'outer',
					),
					'hover'             => false,
					'tab_slug'          => 'advanced',
					'toggle_slug'       => 'back_wrapper',
				),
				'front_button_element' => array(
					'label'             => esc_html__( 'Button Box Shadow', 'squad-modules-for-divi' ),
					'option_category'   => 'layout',
					'css'               => array(
						'main'  => "$this->main_css_element div .flip-box-slides .front-slide .squad-slide-button",
						'hover' => "$this->main_css_element div .flip-box-slides .front-slide .squad-slide-button:hover",
					),
					'default_on_fronts' => array(
						'color'    => 'rgba(0,0,0,0.3)',
						'position' => 'outer',
					),
					'hover'             => false,
					'depends_on'        => array( 'front_button__enable' ),
					'depends_show_if'   => 'on',
					'tab_slug'          => 'advanced',
					'toggle_slug'       => 'front_button_element',
				),
				'back_button_element'  => array(
					'label'             => esc_html__( 'Button Box Shadow', 'squad-modules-for-divi' ),
					'option_category'   => 'layout',
					'css'               => array(
						'main'  => "$this->main_css_element div .flip-box-slides .back-slide .squad-slide-button",
						'hover' => "$this->main_css_element div .flip-box-slides .back-slide .squad-slide-button:hover",
					),
					'default_on_fronts' => array(
						'color'    => 'rgba(0,0,0,0.3)',
						'position' => 'outer',
					),
					'depends_on'        => array( 'back_button__enable' ),
					'depends_show_if'   => 'on',
					'tab_slug'          => 'advanced',
					'toggle_slug'       => 'back_button_element',
				),
			),
			'margin_padding' => divi_squad()->d4_module_helper->selectors_margin_padding( $this->main_css_element ),
			'max_width'      => divi_squad()->d4_module_helper->selectors_max_width( $this->main_css_element ),
			'height'         => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			'link_options'   => false,
			'background'     => false,
			'image_icon'     => false,
			'text'           => false,
			'button'         => false,
			'filters'        => false,
		);

		// Declare custom css fields for the module.
		$this->custom_css_fields = array(
			'front_button' => array(
				'label'    => esc_html__( 'Front Button', 'squad-modules-for-divi' ),
				'selector' => 'div .flip-box .flip-box-slides .front-slide .squad-slide-button',
			),
			'back_button'  => array(
				'label'    => esc_html__( 'Back Button', 'squad-modules-for-divi' ),
				'selector' => 'div .flip-box .flip-box-slides .back-slide .squad-slide-button',
			),
			'wrapper'      => array(
				'label'    => esc_html__( 'Wrapper', 'squad-modules-for-divi' ),
				'selector' => 'div .flip-box .flip-box-slides',
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
		$front_text_fields = $this->squad_get_side_text_fields( 'front' );
		$back_text_fields  = $this->squad_get_side_text_fields( 'back' );
		$text_fields       = array_merge( $front_text_fields, $back_text_fields );

		// Icon & Image fields with associated definitions.
		$front_icon_images_fields = $this->squad_get_side_icon_images_fields( 'front' );
		$back_icon_images_fields  = $this->squad_get_side_icon_images_fields( 'back' );
		$icon_images_fields       = array_merge( $front_icon_images_fields, $back_icon_images_fields );

		// Button fields definitions.
		$front_button = $this->squad_utils->field_definitions->get_button_fields(
			array(
				'base_attr_name'       => 'front_button',
				'toggle_slug'          => 'front_button_element',
				'depends_show_if'      => 'on',
				'fields_before_margin' => array(
					'front_button_horizontal_alignment' => divi_squad()->d4_module_helper->add_alignment_field(
						esc_html__( 'Button Alignment', 'squad-modules-for-divi' ),
						array(
							'description'      => esc_html__( 'Align icon to the left, right or center.', 'squad-modules-for-divi' ),
							'type'             => 'align',
							'default'          => 'left',
							'default_on_front' => 'left',
							'depends_show_if'  => 'on',
							'tab_slug'         => 'advanced',
							'toggle_slug'      => 'front_button_element',
						)
					),
				),
			)
		);
		$back_button  = $this->squad_utils->field_definitions->get_button_fields(
			array(
				'base_attr_name'       => 'back_button',
				'toggle_slug'          => 'back_button_element',
				'fields_before_margin' => array(
					'back_button_horizontal_alignment' => divi_squad()->d4_module_helper->add_alignment_field(
						esc_html__( 'Button Alignment', 'squad-modules-for-divi' ),
						array(
							'description'      => esc_html__( 'Align icon to the left, right or center.', 'squad-modules-for-divi' ),
							'type'             => 'align',
							'default'          => 'left',
							'default_on_front' => 'left',
							'depends_show_if'  => 'on',
							'tab_slug'         => 'advanced',
							'toggle_slug'      => 'back_button_element',
						)
					),
				),
			)
		);

		// The warning fields for buttons.
		$button_warning_fields = array(
			'front_button_typo_notice_front' => array(
				'type'        => 'disq_custom_warning',
				'message'     => esc_html__( 'You need to enable front back button from content > show the button in the content tab to see typogprahy fields.', 'squad-modules-for-divi' ),
				'show_if'     => array(
					'back_button__enable' => 'on',
				),
				'show_if_not' => array(
					'front_button__enable' => 'on',
				),
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'button_text',
				'sub_toggle'  => 'front_side',
			),
			'back_button_typo_notice_back'   => array(
				'type'        => 'disq_custom_warning',
				'message'     => esc_html__( 'You need to enable the back button from content > show the button in the content tab to see typogprahy fields.', 'squad-modules-for-divi' ),
				'show_if'     => array(
					'front_button__enable' => 'on',
				),
				'show_if_not' => array(
					'back_button__enable' => 'on',
				),
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'button_text',
				'sub_toggle'  => 'back_side',
			),
		);

		// The settings fields definitions.
		$flip_settings_fields = array(
			'flip_custom_height__enable' => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Set Custom Height', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can choose whether or not use custom height for flip box.', 'squad-modules-for-divi' ),
					'default'          => 'off',
					'default_on_front' => 'off',
					'affects'          => array(
						'flip_custom_height',
					),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'flip_settings',
				)
			),
			'flip_custom_height'         => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Custom Height', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can choose the custom height for flip box.', 'squad-modules-for-divi' ),
					'range_settings'   => array(
						'min_limit' => '1',
						'min'       => '1',
						'max_limit' => '1000',
						'max'       => '1000',
						'step'      => '1',
					),
					'default'          => '300px',
					'default_on_front' => '300px',
					'default_unit'     => 'px',
					'depends_show_if'  => 'on',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'flip_settings',
				)
			),
			'flip_elements_hr_alignment' => divi_squad()->d4_module_helper->add_alignment_field(
				esc_html__( 'Elements Horizontal Alignment', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Align icon to the left, right or center.', 'squad-modules-for-divi' ),
					'default'          => 'center',
					'default_on_front' => 'center',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'flip_settings',
				)
			),
			'flip_elements_vr_alignment' => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Elements Vertical Alignment', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Align icon to the top, bottom or center.', 'squad-modules-for-divi' ),
					'options'          => array(
						'flex-start' => esc_html__( 'Top', 'squad-modules-for-divi' ),
						'center'     => esc_html__( 'Center', 'squad-modules-for-divi' ),
						'flex-end'   => esc_html__( 'Bottom', 'squad-modules-for-divi' ),
					),
					'default'          => 'center',
					'default_on_front' => 'center',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'flip_settings',
					'mobile_options'   => true,
				)
			),
			'flip_swap_slide__enable'    => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Show The Back First', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can choose whether or not the back side view first mode, by default "first side".', 'squad-modules-for-divi' ),
					'default'          => 'off',
					'default_on_front' => 'off',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'flip_settings',
				)
			),
		);

		/**
		 * The flip animations fields definitions.
		 *
		 * @since 1.0.0
		 *
		 * @param array $flip_animation_types The flip animation types.
		 */
		$flip_animation_types   = apply_filters(
			'divi_squad_flip_animation_types',
			array(
				'rotate'  => esc_html__( 'Rotate', 'squad-modules-for-divi' ),
				'zoom-in' => esc_html__( 'Zoom In', 'squad-modules-for-divi' ),
				'fade'    => esc_html__( 'Fade', 'squad-modules-for-divi' ),
			)
		);
		$flip_direction_lr      = array(
			'left'  => esc_html__( 'Right', 'squad-modules-for-divi' ),
			'right' => esc_html__( 'Left', 'squad-modules-for-divi' ),
		);
		$flip_direction_bt      = array(
			'bottom' => esc_html__( 'Up', 'squad-modules-for-divi' ),
			'top'    => esc_html__( 'Down', 'squad-modules-for-divi' ),
		);
		$flip_direction_c       = array(
			'center' => esc_html__( 'Center', 'squad-modules-for-divi' ),
		);
		$flip_animations_fields = array(
			'flip_animation_type'          => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Animation Type', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Pick an animation type to enable animations for this element. Once enabled, you will be able to change your animation type further.', 'squad-modules-for-divi' ),
					'options'          => $flip_animation_types,
					'default'          => 'rotate',
					'default_on_front' => 'rotate',
					'affects'          => array(
						'flip_animation_d_lr',
						'flip_animation_d_bt',
						'flip_animation_d_lrbt',
						'flip_animation_d_clrbt',
						'flip_3d_effect__enable',
						'flip_move_both_slide__enable',
					),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'fip_animations',
				)
			),
			'flip_animation_d_lrbt'        => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Animation Direction', 'squad-modules-for-divi' ),
				array(
					'description'         => esc_html__( 'Pick from up to four different animation directions, each of which will adjust the starting and ending position of your animated slide.', 'squad-modules-for-divi' ),
					'options'             => array_merge(
						$flip_direction_lr,
						$flip_direction_bt
					),
					'default'             => 'left',
					'default_on_front'    => 'left',
					'depends_show_if_not' => array(
						'zoom-in',
						'zoom-out',
						'fade',
						'open',
						'diagonal',
						'bounce',
						'fold',
					),
					'tab_slug'            => 'general',
					'toggle_slug'         => 'fip_animations',
				)
			),
			'flip_animation_d_lr'          => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Animation Direction', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Pick from up to two different animation directions, each of which will adjust the starting and ending position of your animated slide.', 'squad-modules-for-divi' ),
					'options'          => $flip_direction_lr,
					'default'          => 'left',
					'default_on_front' => 'left',
					'depends_show_if'  => 'diagonal',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'fip_animations',
				)
			),
			'flip_animation_d_bt'          => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Animation Direction', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Pick from up to two different animation directions, each of which will adjust the starting and ending position of your animated slide.', 'squad-modules-for-divi' ),
					'options'          => $flip_direction_bt,
					'default'          => 'bottom',
					'default_on_front' => 'bottom',
					'depends_show_if'  => 'open',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'fip_animations',
				)
			),
			'flip_animation_d_clrbt'       => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Animation Direction', 'squad-modules-for-divi' ),
				array(
					'description'         => esc_html__( 'Pick from up to five different animation directions, each of which will adjust the starting and ending position of your animated slide.', 'squad-modules-for-divi' ),
					'options'             => array_merge(
						$flip_direction_c,
						$flip_direction_lr,
						$flip_direction_bt
					),
					'default'             => 'center',
					'default_on_front'    => 'center',
					'depends_show_if_not' => array( 'fade', 'open', 'rotate', 'slide', 'diagonal' ),
					'tab_slug'            => 'general',
					'toggle_slug'         => 'fip_animations',
				)
			),
			'flip_3d_effect__enable'       => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Use 3D Content Effect', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can choose whether or not use 3D Content Effect for flip box.', 'squad-modules-for-divi' ),
					'default'          => 'off',
					'default_on_front' => 'off',
					'depends_show_if'  => 'rotate',
					'affects'          => array(
						'flip_translate_z',
						'flip_scale',
					),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'fip_animations',
				)
			),
			'flip_move_both_slide__enable' => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Move Both Side', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can choose whether or not move both slide in slide Effect for flip box.', 'squad-modules-for-divi' ),
					'default'          => 'off',
					'default_on_front' => 'off',
					'depends_show_if'  => 'slide',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'fip_animations',
				)
			),
			'flip_translate_z'             => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Translate Z', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can choose how much the translate z on 3d effect.', 'squad-modules-for-divi' ),
					'range_settings'   => array(
						'min_limit' => '1',
						'min'       => '1',
						'max_limit' => '1000',
						'max'       => '1000',
						'step'      => '1',
					),
					'default'          => '50px',
					'default_on_front' => '50px',
					'default_unit'     => 'px',
					'depends_show_if'  => 'on',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'fip_animations',
				)
			),
			'flip_scale'                   => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Scale', 'squad-modules-for-divi' ),
				array(
					'description'       => esc_html__( 'Here you can choose how much the scale on 3d effect.', 'squad-modules-for-divi' ),
					'range_settings'    => array(
						'min_limit' => '1',
						'min'       => '1',
						'max_limit' => '100',
						'max'       => '100',
						'step'      => '1',
					),
					'default'           => '0.9',
					'default_on_front'  => '0.9',
					'number_validation' => true,
					'fixed_range'       => true,
					'unitless'          => true,
					'depends_show_if'   => 'on',
					'tab_slug'          => 'general',
					'toggle_slug'       => 'fip_animations',
				)
			),
		);

		// The animations fields definitions.
		$flip_transitions_fields = divi_squad()->d4_module_helper->add_transition_fields(
			array(
				'base_attr_name' => 'flip',
				'tab_slug'       => 'general',
				'toggle_slug'    => 'fip_animations',
			)
		);

		// The background fields definitions.
		$front_wrapper_background_fields = $this->squad_utils->field_definitions->add_background_field(
			array(
				'label'       => esc_html__( 'Wrapper Background', 'squad-modules-for-divi' ),
				'base_name'   => 'front_wrapper_background',
				'context'     => 'front_wrapper_background_color',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'front_wrapper',
			)
		);
		$back_wrapper_background_fields  = $this->squad_utils->field_definitions->add_background_field(
			array(
				'label'       => esc_html__( 'Wrapper Background', 'squad-modules-for-divi' ),
				'base_name'   => 'back_wrapper_background',
				'context'     => 'back_wrapper_background_color',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'back_wrapper',
			)
		);

		// side associated fields definitions.
		$front_side_assoc_fields = $this->squad_get_side_associate_fields( 'front' );
		$back_side_assoc_fields  = $this->squad_get_side_associate_fields( 'back' );
		$side_assoc_fields       = array_merge( $front_side_assoc_fields, $back_side_assoc_fields );

		// order fields definitions.
		$front_order_fields = $this->squad_get_side_order_fields( 'front' );
		$back_order_fields  = $this->squad_get_side_order_fields( 'back' );
		$order_fields       = array_merge( $front_order_fields, $back_order_fields );

		// Z-Index fields definitions.
		$front_z_index_fields = $this->squad_get_side_z_index_fields( 'front' );
		$back_z_index_fields  = $this->squad_get_side_z_index_fields( 'back' );
		$z_index_fields       = array_merge( $front_z_index_fields, $back_z_index_fields );

		// Clean hover features from buttons.
		$button_cleanable_common_features = array(
			'button_text',
			'button_icon',
			'button_image',
		);
		$button_cleanable_features        = array(
			'button_text',
			'button_icon',
			'button_image',
			'button_background_color',
			'button_icon_color',
			'button_icon_size',
			'button_image_width',
			'button_image_height',
			'button_width',
			'button_icon_margin',
			'button_margin',
			'button_padding',
		);
		foreach ( $button_cleanable_common_features as $button_common_feature ) {
			$front_button[ "front_$button_common_feature" ]['hover'] = false;
			$back_button[ "back_$button_common_feature" ]['hover']   = false;
		}
		foreach ( $button_cleanable_features as $button_cleanable_feature ) {
			$front_button[ "front_$button_cleanable_feature" ]['hover'] = false;
		}

		// Extra features from buttons which are generating hover effects.
		$button_removable_features = array(
			'button_hover_animation__enable',
			'button_hover_animation_type',
			'button_icon_on_hover',
			'button_icon_hover_move_icon',
		);

		foreach ( $button_removable_features as $button_removable_feature ) {
			$front_button_feature = 'front_' . $button_removable_feature;
			if ( array_key_exists( $front_button_feature, $front_button ) ) {
				unset( $front_button[ $front_button_feature ] );
			}
		}

		// Clean hover features from all backgrounds.
		$front_wrapper_background_fields['front_wrapper_background_color']['hover'] = false;
		$back_wrapper_background_fields['back_wrapper_background_color']['hover']   = false;

		return array_merge(
			$text_fields,
			$icon_images_fields,
			$front_button,
			$back_button,
			$button_warning_fields,
			$flip_settings_fields,
			$flip_animations_fields,
			$flip_transitions_fields,
			// $flip_builder_preview_fields,
			$front_wrapper_background_fields,
			$back_wrapper_background_fields,
			$side_assoc_fields,
			$order_fields,
			$z_index_fields
		);
	}

	/**
	 * Get all text related fields for both sides.
	 *
	 * @param string $side The current slide name.
	 *
	 * @return array Text related fields.
	 */
	private function squad_get_side_text_fields( string $side ): array {
		return array(
			"{$side}_title"          => array(
				'label'           => esc_html__( 'Title', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'The text of your title will appear in with your current side of flip box.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'main_content',
				'sub_toggle'      => "{$side}_side",
				'dynamic_content' => 'text',
				'mobile_options'  => true,
			),
			"{$side}_sub_title"      => array(
				'label'           => esc_html__( 'Sub Title', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'The text of your sub title will appear in with your current side of flip box.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'main_content',
				'sub_toggle'      => "{$side}_side",
				'dynamic_content' => 'text',
				'mobile_options'  => true,
			),
			"{$side}_content"        => array(
				'label'           => esc_html__( 'Body', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'Input the main text content for your current side of flip box here.', 'squad-modules-for-divi' ),
				'type'            => 'tiny_mce',
				'option_category' => 'basic_option',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'main_content',
				'sub_toggle'      => "{$side}_side",
				'dynamic_content' => 'text',
				'mobile_options'  => true,
			),
			"{$side}_title_tag"      => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Title Tag', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Choose a tag to display with your title.', 'squad-modules-for-divi' ),
					'options'          => divi_squad()->d4_module_helper->get_html_tag_elements(),
					'default_on_front' => 'h2',
					'default'          => 'h2',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'main_content',
					'sub_toggle'       => "{$side}_side",
				)
			),
			"{$side}_sub_title_tag"  => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Sub Title Tag', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Choose a tag to display with your sub title.', 'squad-modules-for-divi' ),
					'options'          => divi_squad()->d4_module_helper->get_html_tag_elements(),
					'default_on_front' => 'h5',
					'default'          => 'h5',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'main_content',
					'sub_toggle'       => "{$side}_side",
				)
			),
			"{$side}_button__enable" => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Show Button', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can choose whether or not show the button.', 'squad-modules-for-divi' ),
					'default'          => 'off',
					'default_on_front' => 'off',
					'affects'          => array(
						"{$side}_button_text",
						"{$side}_button_text_font",
						"{$side}_button_text_text_color",
						"{$side}_button_text_text_align",
						"{$side}_button_text_font_size",
						"{$side}_button_text_letter_spacing",
						"{$side}_button_text_line_height",
						"{$side}_button_icon_type",
						"{$side}_button_background_color",
						"{$side}_button_custom_width",
						"{$side}_button_horizontal_alignment",
						"{$side}_button_margin",
						"{$side}_button_padding",
						"{$side}_button_url",
						"{$side}_button_url_new_window",
					),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'main_content',
					'sub_toggle'       => "{$side}_side",
				)
			),
		);
	}

	/**
	 * Get all icons and image related fields for both sides.
	 *
	 * @param string $side The current slide name.
	 *
	 * @return array icons and image related fields.
	 */
	private function squad_get_side_icon_images_fields( string $side ): array {
		// Icon & Image fields definitions.
		$general_fields = array(
			"{$side}_icon_type" => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Icon Type', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Choose an icon type to display with your current side of flip box.', 'squad-modules-for-divi' ),
					'options'          => array(
						'icon'  => esc_html__( 'Icon', 'squad-modules-for-divi' ),
						'image' => et_builder_i18n( 'Image' ),
						'text'  => et_builder_i18n( 'Text' ),
						'none'  => esc_html__( 'None', 'squad-modules-for-divi' ),
					),
					'default_on_front' => 'image',
					'affects'          => array(
						"{$side}_icon",
						"{$side}_image",
						"{$side}_icon_text",
						"{$side}_icon_text_font",
						"{$side}_icon_text_text_color",
						"{$side}_icon_text_font_size",
						"{$side}_icon_text_letter_spacing",
						"{$side}_icon_text_line_height",
						"{$side}_icon_color",
						"{$side}_icon_size",
						"{$side}_image_force_full_width",
						"{$side}_image_height",
						"{$side}_alt",
						"{$side}_image_icon_background_color",
						"{$side}_content_outside_container",
						"{$side}_icon_margin",
						"{$side}_icon_padding",
					),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'image_icon',
					'sub_toggle'       => "{$side}_side",
				)
			),
			"{$side}_icon"      => array(
				'label'            => esc_html__( 'Choose an icon', 'squad-modules-for-divi' ),
				'description'      => esc_html__( 'Choose an icon to display with your current side of flip box.', 'squad-modules-for-divi' ),
				'type'             => 'select_icon',
				'option_category'  => 'basic_option',
				'class'            => array( 'et-pb-font-icon' ),
				'default_on_front' => '&#x4e;||divi||400',
				'depends_show_if'  => 'icon',
				'tab_slug'         => 'general',
				'toggle_slug'      => 'image_icon',
				'sub_toggle'       => "{$side}_side",
				'mobile_options'   => true,
			),
			"{$side}_image"     => array(
				'label'              => et_builder_i18n( 'Image' ),
				'description'        => esc_html__( 'Upload an image to display at the top of your current side of flip box.', 'squad-modules-for-divi' ),
				'type'               => 'upload',
				'option_category'    => 'basic_option',
				'upload_button_text' => et_builder_i18n( 'Upload an image' ),
				'choose_text'        => esc_attr__( 'Choose an Image', 'squad-modules-for-divi' ),
				'update_text'        => esc_attr__( 'Set As Image', 'squad-modules-for-divi' ),
				'depends_show_if'    => 'image',
				'tab_slug'           => 'general',
				'toggle_slug'        => 'image_icon',
				'sub_toggle'         => "{$side}_side",
				'dynamic_content'    => 'image',
				'mobile_options'     => true,
			),
			"{$side}_alt"       => array(
				'label'           => esc_html__( 'Image Alt Text', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'Define the HTML ALT text for your image here.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'depends_show_if' => 'image',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'image_icon',
				'sub_toggle'      => "{$side}_side",
				'dynamic_content' => 'text',
			),
			"{$side}_icon_text" => array(
				'label'           => et_builder_i18n( 'Text' ),
				'description'     => esc_html__( 'The text as icon will appear in your current side of flip box.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'depends_show_if' => 'text',
				'dynamic_content' => 'text',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'image_icon',
				'sub_toggle'      => "{$side}_side",
			),
		);

		// Icon & Image associate fields definitions.
		$associated_fields = array(
			"{$side}_icon_color"                  => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Icon Color', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Here you can define a custom color for your icon.', 'squad-modules-for-divi' ),
					'depends_show_if' => 'icon',
					'tab_slug'        => 'advanced',
					'toggle_slug'     => 'image_icon',
					'sub_toggle'      => "{$side}_side",
				)
			),
			"{$side}_image_icon_background_color" => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Icon Background Color', 'squad-modules-for-divi' ),
				array(
					'description'         => esc_html__( 'Here you can define a custom background color.', 'squad-modules-for-divi' ),
					'depends_show_if_not' => array( 'none', 'image', 'lottie' ),
					'tab_slug'            => 'advanced',
					'toggle_slug'         => 'image_icon',
					'sub_toggle'          => "{$side}_side",
				)
			),
			"{$side}_icon_size"                   => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Icon Size', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can choose icon size.', 'squad-modules-for-divi' ),
					'range_settings'   => array(
						'min_limit' => '1',
						'min'       => '1',
						'max_limit' => '200',
						'max'       => '200',
						'step'      => '1',
					),
					'default'          => '96px',
					'default_on_front' => '96px',
					'default_unit'     => 'px',
					'depends_show_if'  => 'icon',
					'tab_slug'         => 'advanced',
					'toggle_slug'      => 'image_icon',
					'sub_toggle'       => "{$side}_side",
				)
			),
			"{$side}_image_force_full_width"      => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Image Force Full-Width', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can choose whether or not your full width.', 'squad-modules-for-divi' ),
					'default_on_front' => 'off',
					'depends_show_if'  => 'image',
					'affects'          => array(
						"{$side}_image_width",
					),
					'tab_slug'         => 'advanced',
					'toggle_slug'      => 'image_icon',
					'sub_toggle'       => "{$side}_side",
				)
			),
			"{$side}_image_width"                 => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Image Width', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Here you can choose image width.', 'squad-modules-for-divi' ),
					'range_settings'  => array(
						'min_limit' => '1',
						'min'       => '1',
						'max_limit' => '200',
						'max'       => '200',
						'step'      => '1',
					),
					'allow_empty'     => true,
					'default_unit'    => 'px',
					'depends_show_if' => 'off',
					'tab_slug'        => 'advanced',
					'toggle_slug'     => 'image_icon',
					'sub_toggle'      => "{$side}_side",
				)
			),
			"{$side}_image_height"                => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Image Height', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Here you can choose image height.', 'squad-modules-for-divi' ),
					'range_settings'  => array(
						'min_limit' => '1',
						'min'       => '1',
						'max_limit' => '200',
						'max'       => '200',
						'step'      => '1',
					),
					'allow_empty'     => true,
					'default_unit'    => 'px',
					'depends_show_if' => 'image',
					'tab_slug'        => 'advanced',
					'toggle_slug'     => 'image_icon',
					'sub_toggle'      => "{$side}_side",
				)
			),
			"{$side}_icon_default_alignment"      => divi_squad()->d4_module_helper->add_alignment_field(
				esc_html__( 'Icon Horizontal Alignment', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Align icon to the left, right or center.', 'squad-modules-for-divi' ),
					'default_on_front' => 'left',
					'show_if_not'      => array(
						"{$side}_content_outside_container" => 'on',
					),
					'tab_slug'         => 'advanced',
					'toggle_slug'      => 'image_icon',
					'sub_toggle'       => "{$side}_side",
				)
			),
			"{$side}_content_outside_container"   => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Show Icon Outside The Container', 'squad-modules-for-divi' ),
				array(
					'description'         => esc_html__( 'Here you can choose whether or not your content will display outside the wrapper.', 'squad-modules-for-divi' ),
					'default_on_front'    => 'off',
					'depends_show_if_not' => array( 'none' ),
					'affects'             => array(
						"{$side}_icon_text_gap",
						"{$side}_icon_placement",
					),
					'tab_slug'            => 'advanced',
					'toggle_slug'         => 'image_icon',
					'sub_toggle'          => "{$side}_side",
				)
			),
			"{$side}_icon_text_gap"               => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Gap Between Icon/Image and Text', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can choose gap between icon and text.', 'squad-modules-for-divi' ),
					'range_settings'   => array(
						'min_limit' => '1',
						'min'       => '1',
						'max_limit' => '200',
						'max'       => '200',
						'step'      => '1',
					),
					'default'          => '10px',
					'default_on_front' => '10px',
					'default_unit'     => 'px',
					'depends_show_if'  => 'on',
					'tab_slug'         => 'advanced',
					'toggle_slug'      => 'image_icon',
					'sub_toggle'       => "{$side}_side",
					'mobile_options'   => true,
					'hover'            => false,
				)
			),
			"{$side}_icon_wrapper_width"          => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Icon Wrapper Width', 'squad-modules-for-divi' ),
				array(
					'description'         => esc_html__( 'Here you can choose icon wrapper width.', 'squad-modules-for-divi' ),
					'range_settings'      => array(
						'min_limit' => '1',
						'min'       => '1',
						'max_limit' => '100',
						'max'       => '100',
						'step'      => '1',
					),
					'allow_empty'         => true,
					'default'             => '200px',
					'default_on_front'    => '200px',
					'default_unit'        => 'px',
					'depends_show_if_not' => array( 'column' ),
					'tab_slug'            => 'advanced',
					'toggle_slug'         => 'image_icon',
					'sub_toggle'          => "{$side}_side",
					'hover'               => false,
				)
			),
			"{$side}_icon_placement"              => divi_squad()->d4_module_helper->add_placement_field(
				esc_html__( 'Icon Placement', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can choose where to place the icon.', 'squad-modules-for-divi' ),
					'options'          => array(
						'column'      => et_builder_i18n( 'Default' ),
						'row'         => et_builder_i18n( 'Left' ),
						'row-reverse' => et_builder_i18n( 'Right' ),
					),
					'default'          => 'column',
					'default_on_front' => 'column',
					'depends_show_if'  => 'on',
					'affects'          => array(
						"{$side}_icon_wrapper_width",
						"{$side}_icon_horizontal_alignment",
						"{$side}_icon_vertical_alignment",
					),
					'tab_slug'         => 'advanced',
					'toggle_slug'      => 'image_icon',
					'sub_toggle'       => "{$side}_side",
				)
			),
			"{$side}_icon_horizontal_alignment"   => divi_squad()->d4_module_helper->add_alignment_field(
				esc_html__( 'Icon Horizontal Alignment', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Align icon to the left, right or center.', 'squad-modules-for-divi' ),
					'default_on_front' => 'left',
					'depends_show_if'  => 'column',
					'tab_slug'         => 'advanced',
					'toggle_slug'      => 'image_icon',
					'sub_toggle'       => "{$side}_side",
				)
			),
			"{$side}_icon_vertical_alignment"     => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Icon Vertical Placement', 'squad-modules-for-divi' ),
				array(
					'description'         => esc_html__( 'Here you can choose where to place the icon.', 'squad-modules-for-divi' ),
					'options'             => array(
						'flex-start' => esc_html__( 'Top', 'squad-modules-for-divi' ),
						'center'     => esc_html__( 'Center', 'squad-modules-for-divi' ),
						'flex-end'   => esc_html__( 'Bottom', 'squad-modules-for-divi' ),
					),
					'default'             => 'flex-start',
					'default_on_front'    => 'flex-start',
					'depends_show_if_not' => array( 'column' ),
					'tab_slug'            => 'advanced',
					'toggle_slug'         => 'image_icon',
					'sub_toggle'          => "{$side}_side",
					'mobile_options'      => true,
				)
			),
			"{$side}_icon_wrapper_margin"         => divi_squad()->d4_module_helper->add_margin_padding_field(
				esc_html__( 'Icon Wrapper Margin', 'squad-modules-for-divi' ),
				array(
					'description'         => esc_html__( 'Here you can define a custom margin size for the icon wrapper.', 'squad-modules-for-divi' ),
					'type'                => 'custom_margin',
					'depends_show_if_not' => array( 'none' ),
					'tab_slug'            => 'advanced',
					'toggle_slug'         => 'image_icon',
					'sub_toggle'          => "{$side}_side",
				)
			),
			"{$side}_icon_wrapper_padding"        => divi_squad()->d4_module_helper->add_margin_padding_field(
				esc_html__( 'Icon Wrapper Padding', 'squad-modules-for-divi' ),
				array(
					'description'         => esc_html__( 'Here you can define a custom padding size.', 'squad-modules-for-divi' ),
					'type'                => 'custom_padding',
					'depends_show_if_not' => array( 'none' ),
					'tab_slug'            => 'advanced',
					'toggle_slug'         => 'image_icon',
					'sub_toggle'          => "{$side}_side",
				)
			),
			"{$side}_icon_margin"                 => divi_squad()->d4_module_helper->add_margin_padding_field(
				esc_html__( 'Icon Margin', 'squad-modules-for-divi' ),
				array(
					'description'         => esc_html__( 'Here you can define a custom margin size for the icon.', 'squad-modules-for-divi' ),
					'type'                => 'custom_margin',
					'depends_show_if_not' => array( 'none' ),
					'tab_slug'            => 'advanced',
					'toggle_slug'         => 'image_icon',
					'sub_toggle'          => "{$side}_side",
				)
			),
			"{$side}_icon_padding"                => divi_squad()->d4_module_helper->add_margin_padding_field(
				esc_html__( 'Icon Padding', 'squad-modules-for-divi' ),
				array(
					'description'         => esc_html__( 'Here you can define a custom padding size.', 'squad-modules-for-divi' ),
					'type'                => 'custom_padding',
					'depends_show_if_not' => array( 'none' ),
					'tab_slug'            => 'advanced',
					'toggle_slug'         => 'image_icon',
					'sub_toggle'          => "{$side}_side",
				)
			),
		);

		return array_merge( $general_fields, $associated_fields );
	}

	/**
	 * Get all text related fields for both sides.
	 *
	 * @param string $side The current slide name.
	 *
	 * @return array Text related fields.
	 */
	private function squad_get_side_associate_fields( string $side ): array {
		$wrapper_fields = array(
			"{$side}_text_orientation"    => divi_squad()->d4_module_helper->add_alignment_field(
				esc_html__( 'Text Alignment', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'This controls how your text is aligned within the module.', 'squad-modules-for-divi' ),
					'type'        => 'text_align',
					'options'     => et_builder_get_text_orientation_options(
						array( 'justified' ),
						array( 'justify' => 'Justified' )
					),
					'default'     => '',
					'tab_slug'    => 'advanced',
					'toggle_slug' => "{$side}_wrapper",
				)
			),
			"{$side}_icon_item_inner_gap" => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Gap Between Elements', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can choose gap between icon and text.', 'squad-modules-for-divi' ),
					'range_settings'   => array(
						'min_limit' => '1',
						'min'       => '1',
						'max_limit' => '200',
						'max'       => '200',
						'step'      => '1',
					),
					'default_unit'     => 'px',
					'default'          => '10px',
					'default_on_front' => '10px',
					'depends_show_if'  => 'on',
					'tab_slug'         => 'advanced',
					'toggle_slug'      => "{$side}_wrapper",
					'mobile_options'   => true,
					'hover'            => false,
				)
			),
			"{$side}_wrapper_margin"      => divi_squad()->d4_module_helper->add_margin_padding_field(
				esc_html__( 'Wrapper Margin', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Here you can define a custom margin size for the wrapper.', 'squad-modules-for-divi' ),
					'type'        => 'custom_margin',
					'hover'       => false,
					'tab_slug'    => 'advanced',
					'toggle_slug' => "{$side}_wrapper",
				)
			),
			"{$side}_wrapper_padding"     => divi_squad()->d4_module_helper->add_margin_padding_field(
				esc_html__( 'Wrapper Padding', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can define a custom padding size.', 'squad-modules-for-divi' ),
					'type'             => 'custom_padding',
					'default'          => '10px|15px|10px|15px|false|false',
					'default_on_front' => '10px|15px|10px|15px|false|false',
					'hover'            => false,
					'tab_slug'         => 'advanced',
					'toggle_slug'      => "{$side}_wrapper",
				)
			),
		);

		// Icon & Image associate fields definitions.
		$text_associated_fields = array(
			"{$side}_title_margin"      => divi_squad()->d4_module_helper->add_margin_padding_field(
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
					'hover'          => false,
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'custom_margin_padding',
					'sub_toggle'     => "{$side}_side",
				)
			),
			"{$side}_title_padding"     => divi_squad()->d4_module_helper->add_margin_padding_field(
				esc_html__( 'Title Padding', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Here you can define a custom padding size.', 'squad-modules-for-divi' ),
					'type'           => 'custom_padding',
					'range_settings' => array(
						'min_limit' => '1',
						'min'       => '1',
						'max_limit' => '100',
						'max'       => '100',
						'step'      => '1',
					),
					'hover'          => false,
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'custom_margin_padding',
					'sub_toggle'     => "{$side}_side",
				)
			),
			"{$side}_sub_title_margin"  => divi_squad()->d4_module_helper->add_margin_padding_field(
				esc_html__( 'Sub Title Margin', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Here you can define a custom margin size for the sub title.', 'squad-modules-for-divi' ),
					'type'           => 'custom_margin',
					'range_settings' => array(
						'min_limit' => '1',
						'min'       => '1',
						'max_limit' => '100',
						'max'       => '100',
						'step'      => '1',
					),
					'hover'          => false,
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'custom_margin_padding',
					'sub_toggle'     => "{$side}_side",
				)
			),
			"{$side}_sub_title_padding" => divi_squad()->d4_module_helper->add_margin_padding_field(
				esc_html__( 'Sub Title Padding', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Here you can define a custom padding size.', 'squad-modules-for-divi' ),
					'type'           => 'custom_padding',
					'range_settings' => array(
						'min_limit' => '1',
						'min'       => '1',
						'max_limit' => '100',
						'max'       => '100',
						'step'      => '1',
					),
					'hover'          => false,
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'custom_margin_padding',
					'sub_toggle'     => "{$side}_side",
				)
			),
			"{$side}_content_margin"    => divi_squad()->d4_module_helper->add_margin_padding_field(
				esc_html__( 'Body Margin', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Here you can define a custom margin size for the content.', 'squad-modules-for-divi' ),
					'type'           => 'custom_margin',
					'range_settings' => array(
						'min_limit' => '1',
						'min'       => '1',
						'max_limit' => '100',
						'max'       => '100',
						'step'      => '1',
					),
					'hover'          => false,
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'custom_margin_padding',
					'sub_toggle'     => "{$side}_side",
				)
			),
			"{$side}_content_padding"   => divi_squad()->d4_module_helper->add_margin_padding_field(
				esc_html__( 'Body Padding', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Here you can define a custom padding size.', 'squad-modules-for-divi' ),
					'type'           => 'custom_padding',
					'range_settings' => array(
						'min_limit' => '1',
						'min'       => '1',
						'max_limit' => '100',
						'max'       => '100',
						'step'      => '1',
					),
					'hover'          => false,
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'custom_margin_padding',
					'sub_toggle'     => "{$side}_side",
				)
			),
		);

		// URL fields definitions.
		$url_fields = array(
			"{$side}_button_url"            => array(
				'label'           => esc_html__( 'Button Link URL', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'If you would like to make your button link, input your destination URL here.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'depends_show_if' => 'on',
				'tab_slug'        => 'general',
				'toggle_slug'     => "{$side}_button_element",
				'dynamic_content' => 'url',
			),
			"{$side}_button_url_new_window" => array(
				'label'            => esc_html__( 'Button Link Target', 'squad-modules-for-divi' ),
				'description'      => esc_html__( 'Here you can choose whether or not your link opens in a new window', 'squad-modules-for-divi' ),
				'type'             => 'select',
				'option_category'  => 'configuration',
				'options'          => array(
					'off' => esc_html__( 'In The Same Window', 'squad-modules-for-divi' ),
					'on'  => esc_html__( 'In The New Tab', 'squad-modules-for-divi' ),
				),
				'default_on_front' => 'off',
				'depends_show_if'  => 'on',
				'tab_slug'         => 'general',
				'toggle_slug'      => "{$side}_button_element",
			),
		);

		return array_merge( $wrapper_fields, $text_associated_fields, $url_fields );
	}

	/**
	 * Get all order fields for both sides.
	 *
	 * @param string $side The current slide name.
	 *
	 * @return array order fields.
	 */
	private function squad_get_side_order_fields( string $side ): array {
		return array(
			"{$side}_icon_order"      => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Image/Icon Order', 'squad-modules-for-divi' ),
				array(
					'description'       => esc_html__( 'Increase the order number to position the item lower.', 'squad-modules-for-divi' ),
					'range_settings'    => array(
						'min_limit' => '1',
						'min'       => '1',
						'max_limit' => '15',
						'max'       => '15',
						'step'      => '1',
					),
					'number_validation' => true,
					'fixed_range'       => true,
					'unitless'          => true,
					'mobile_options'    => false,
					'responsive'        => false,
					'hover'             => false,
					'tab_slug'          => 'general',
					'toggle_slug'       => 'item_order_element',
					'sub_toggle'        => "{$side}_side",
				)
			),
			"{$side}_title_order"     => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Title Order', 'squad-modules-for-divi' ),
				array(
					'description'       => esc_html__( 'Increase the order number to position the item lower.', 'squad-modules-for-divi' ),
					'range_settings'    => array(
						'min_limit' => '1',
						'min'       => '1',
						'max_limit' => '15',
						'max'       => '15',
						'step'      => '1',
					),
					'number_validation' => true,
					'fixed_range'       => true,
					'unitless'          => true,
					'mobile_options'    => false,
					'responsive'        => false,
					'hover'             => false,
					'tab_slug'          => 'general',
					'toggle_slug'       => 'item_order_element',
					'sub_toggle'        => "{$side}_side",
				)
			),
			"{$side}_sub_title_order" => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Sub Title Order', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Increase the order number to position the item lower.', 'squad-modules-for-divi' ),
					'range_settings' => array(
						'min_limit' => '1',
						'min'       => '1',
						'max_limit' => '15',
						'max'       => '15',
						'step'      => '1',
					),

					'number_validation' => true,
					'fixed_range'       => true,
					'unitless'          => true,
					'mobile_options'    => false,
					'responsive'        => false,
					'hover'             => false,
					'tab_slug'          => 'general',
					'toggle_slug'       => 'item_order_element',
					'sub_toggle'        => "{$side}_side",
				)
			),
			"{$side}_body_order"      => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Body Order', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Increase the order number to position the item lower.', 'squad-modules-for-divi' ),
					'range_settings' => array(
						'min_limit' => '1',
						'min'       => '1',
						'max_limit' => '15',
						'max'       => '15',
						'step'      => '1',
					),

					'number_validation' => true,
					'fixed_range'       => true,
					'unitless'          => true,
					'mobile_options'    => false,
					'responsive'        => false,
					'hover'             => false,
					'tab_slug'          => 'general',
					'toggle_slug'       => 'item_order_element',
					'sub_toggle'        => "{$side}_side",
				)
			),
			"{$side}_button_order"    => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Button Order', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Increase the order number to position the item lower.', 'squad-modules-for-divi' ),
					'range_settings' => array(
						'min_limit' => '1',
						'min'       => '1',
						'max_limit' => '15',
						'max'       => '15',
						'step'      => '1',
					),

					'number_validation' => true,
					'fixed_range'       => true,
					'unitless'          => true,
					'mobile_options'    => false,
					'responsive'        => false,
					'hover'             => false,
					'tab_slug'          => 'general',
					'toggle_slug'       => 'item_order_element',
					'sub_toggle'        => "{$side}_side",
				)
			),
		);
	}

	/**
	 * Get all z index fields for both sides.
	 *
	 * @param string $side The current slide name.
	 *
	 * @return array z index fields.
	 */
	private function squad_get_side_z_index_fields( string $side ): array {
		return array(
			"{$side}_icon_z_index"      => divi_squad()->d4_module_helper->add_z_index_field(
				array(
					'label_prefix' => esc_html__( 'Icon/Image', 'squad-modules-for-divi' ),
					'tab_slug'     => 'general',
					'toggle_slug'  => 'item_z_index_element',
					'sub_toggle'   => "{$side}_side",
				)
			),
			"{$side}_title_z_index"     => divi_squad()->d4_module_helper->add_z_index_field(
				array(
					'label_prefix' => esc_html__( 'Title', 'squad-modules-for-divi' ),
					'tab_slug'     => 'general',
					'toggle_slug'  => 'item_z_index_element',
					'sub_toggle'   => "{$side}_side",
				)
			),
			"{$side}_sub_title_z_index" => divi_squad()->d4_module_helper->add_z_index_field(
				array(
					'label_prefix' => esc_html__( 'Sub Title', 'squad-modules-for-divi' ),
					'tab_slug'     => 'general',
					'toggle_slug'  => 'item_z_index_element',
					'sub_toggle'   => "{$side}_side",
				)
			),
			"{$side}_body_z_index"      => divi_squad()->d4_module_helper->add_z_index_field(
				array(
					'label_prefix' => esc_html__( 'Body', 'squad-modules-for-divi' ),
					'tab_slug'     => 'general',
					'toggle_slug'  => 'item_z_index_element',
					'sub_toggle'   => "{$side}_side",
				)
			),
			"{$side}_button_z_index"    => divi_squad()->d4_module_helper->add_z_index_field(
				array(
					'label_prefix' => esc_html__( 'Button', 'squad-modules-for-divi' ),
					'tab_slug'     => 'general',
					'toggle_slug'  => 'item_z_index_element',
					'sub_toggle'   => "{$side}_side",
				)
			),
		);
	}

	/**
	 * Filter multi view value.
	 *
	 * @since 3.27.1
	 *
	 * @see   ET_Builder_Module_Helper_MultiViewOptions::filter_value
	 *
	 * @param mixed $raw_value Props raw value.
	 * @param mixed $args      Arguments.
	 *
	 * @return mixed
	 */
	public function multi_view_filter_value( $raw_value, $args ) {
		$name = isset( $args['name'] ) ? $args['name'] : '';

		// process font.
		$icon_fields = array(
			'front_icon',
			'back_icon',
			'front_button_icon',
			'back_button_icon',
		);
		if ( $raw_value && in_array( $name, $icon_fields, true ) ) {
			return et_pb_get_extended_font_icon_value( $raw_value, true );
		}

		$rich_content_fields = array(
			'front_content',
			'back_content',
		);

		if ( $raw_value && in_array( $name, $rich_content_fields, true ) ) {
			$raw_value = preg_replace( '/^[\w]?<\/p>/smi', '', $raw_value );
			$raw_value = preg_replace( '/<p>$/smi', '', $raw_value );
		}

		// process others: fields, image, title.
		return $raw_value;
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
		$animation_type   = $this->prop( 'flip_animation_type', 'rotate' );
		$flip_3d_effect   = $this->prop( 'flip_3d_effect__enable', 'off' );
		$flip_move_both   = $this->prop( 'flip_move_both_slide__enable', 'off' );
		$flip_swap_slide  = $this->prop( 'flip_swap_slide__enable', 'off' );
		$flip_box_classes = array( 'flip-box', $animation_type );

		if ( 'diagonal' === $animation_type ) {
			$flip_box_classes[] = $this->prop( 'flip_animation_d_lr', 'right' );
		}
		if ( 'open' === $animation_type ) {
			$flip_box_classes[] = $this->prop( 'flip_animation_d_bt', 'bottom' );
		}
		if ( in_array( $animation_type, array( 'rotate', 'slide' ), true ) ) {
			$flip_box_classes[] = $this->prop( 'flip_animation_d_lrbt', 'right' );
		}
		if ( in_array( $animation_type, array( 'bounce', 'zoom-in', 'zoom-out', 'fold' ), true ) ) {
			$flip_box_classes[] = $this->prop( 'flip_animation_d_clrbt', 'center' );
		}
		if ( 'rotate' === $animation_type && 'on' === $flip_3d_effect ) {
			$flip_box_classes[] = 'flip-3d-content-effect';
		}
		if ( 'slide' === $animation_type && 'on' === $flip_move_both ) {
			$flip_box_classes[] = 'flip-slide-move-both';
		}
		if ( 'on' === $flip_swap_slide ) {
			$flip_box_classes[] = 'swap-slide';
		}

		$this->squad_generate_animation_styles();
		$this->squad_generate_additional_styles( 'front', $attrs );
		$this->squad_generate_additional_styles( 'back', $attrs );

		$front_slide = sprintf(
			'<div class="flip-slide front-slide et_pb_with_background"><div class="flip-slide-inner">%1$s%2$s</div></div>',
			wp_kses_post( $this->squad_render_slide_icons( 'front', $attrs ) ),
			wp_kses_post( $this->squad_render_slide_elements( 'front', $attrs ) )
		);
		$back_slide  = sprintf(
			'<div class="flip-slide back-slide et_pb_with_background"><div class="flip-slide-inner">%1$s%2$s</div></div>',
			wp_kses_post( $this->squad_render_slide_icons( 'back', $attrs ) ),
			wp_kses_post( $this->squad_render_slide_elements( 'back', $attrs ) )
		);

		return sprintf(
			'<div class="%1$s"><div class="flip-box-slides">%2$s%3$s</div></div>',
			esc_attr( implode( ' ', $flip_box_classes ) ),
			wp_kses_post( $front_slide ),
			wp_kses_post( $back_slide )
		);
	}

	/**
	 * Renders animation styles for the module output.
	 */
	private function squad_generate_animation_styles(): void {
		$animation_type = $this->prop( 'flip_animation_type', 'rotate' );

		// Working with flip settings.
		if ( 'on' === $this->prop( 'flip_custom_height__enable', 'off' ) ) {
			$this->generate_styles(
				array(
					'base_attr_name' => 'flip_custom_height',
					'selector'       => "$this->main_css_element div .flip-box",
					'css_property'   => 'height',
					'render_slug'    => $this->slug,
					'type'           => 'range',
				)
			);
		}

		$this->generate_styles(
			array(
				'base_attr_name' => 'flip_elements_hr_alignment',
				'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .flip-slide",
				'css_property'   => 'justify-content',
				'render_slug'    => $this->slug,
				'type'           => 'align',
			)
		);
		$this->generate_styles(
			array(
				'base_attr_name' => 'flip_elements_vr_alignment',
				'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .flip-slide",
				'css_property'   => 'align-items',
				'render_slug'    => $this->slug,
				'type'           => 'align',
			)
		);
		// End of working with flip settings.

		// Working with flip animations.
		if ( 'rotate' === $animation_type && 'on' === $this->prop( 'flip_3d_effect__enable', 'off' ) ) {
			$flip_translate_z = $this->prop( 'flip_translate_z', '50px' );
			$flip_scale       = $this->prop( 'flip_scale', '.9' );

			self::set_style(
				$this->slug,
				array(
					'selector'    => "$this->main_css_element div .flip-box.flip-3d-content-effect .flip-slide .flip-slide-inner",
					'declaration' => "transform: translateZ($flip_translate_z) scale($flip_scale);",
				)
			);
		}

		$flip_transition_selector = "$this->main_css_element div .flip-box .flip-box-slides";
		if ( in_array( $animation_type, array( 'fade', 'zoom', 'slide', 'open' ), true ) ) {
			$flip_transition_selector .= ' .flip-slide';
		}
		self::set_style(
			$this->slug,
			array(
				'selector'    => $flip_transition_selector,
				'declaration' => 'transform-style: preserve-3d;transition-property: all;',
			)
		);

		$this->generate_styles(
			array(
				'base_attr_name' => 'flip_transition_delay',
				'selector'       => $flip_transition_selector,
				'css_property'   => 'transition-delay',
				'render_slug'    => $this->slug,
				'type'           => 'range',
			)
		);
		$this->generate_styles(
			array(
				'base_attr_name' => 'flip_transition_duration',
				'selector'       => $flip_transition_selector,
				'css_property'   => 'transition-duration',
				'render_slug'    => $this->slug,
				'type'           => 'range',
			)
		);
		$this->generate_styles(
			array(
				'base_attr_name' => 'flip_transition_speed_curve',
				'selector'       => $flip_transition_selector,
				'css_property'   => 'transition-timing-function',
				'render_slug'    => $this->slug,
				'type'           => 'string',
			)
		);
		// End of working with flip animations.
	}

	/**
	 * Renders additional styles for the module output.
	 *
	 * @param string $slide_type The slide type.
	 * @param array  $attrs      List of attributes.
	 */
	private function squad_generate_additional_styles( string $slide_type, array $attrs ): void {
		// Fixed: the custom background doesn't work at frontend.
		$this->props = array_merge( $attrs, $this->props );

		// wrapper background with default, responsive, hover.
		et_pb_background_options()->get_background_style(
			array(
				'base_prop_name'         => "{$slide_type}_wrapper_background",
				'props'                  => $this->props,
				'selector'               => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide",
				'selector_hover'         => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide:hover",
				'selector_sticky'        => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide",
				'function_name'          => $this->slug,
				'important'              => ' !important',
				'use_background_video'   => false,
				'use_background_pattern' => false,
				'use_background_mask'    => false,
				'prop_name_aliases'      => array(
					"use_{$slide_type}_wrapper_background_color_gradient" => "{$slide_type}_wrapper_background_use_color_gradient",
					"{$slide_type}_wrapper_background"                    => "{$slide_type}_wrapper_background_color",
				),
			)
		);

		// content text aligns with default, responsive, hover.
		$this->generate_styles(
			array(
				'base_attr_name' => "{$slide_type}_text_orientation",
				'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide",
				'hover_selector' => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide:hover",
				'css_property'   => 'text-align',
				'render_slug'    => $this->slug,
				'type'           => 'align',
			)
		);
		// wrapper margin with default, responsive, hover.
		$this->squad_utils->field_css_generations->generate_margin_padding_styles(
			array(
				'field'          => "{$slide_type}_wrapper_margin",
				'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide",
				'hover_selector' => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide:hover",
				'css_property'   => 'margin',
				'type'           => 'margin',
			)
		);
		// wrapper padding with default, responsive, hover.
		$this->squad_utils->field_css_generations->generate_margin_padding_styles(
			array(
				'field'          => "{$slide_type}_wrapper_padding",
				'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide",
				'hover_selector' => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide:hover",
				'css_property'   => 'padding',
				'type'           => 'padding',
			)
		);

		// order system.
		$this->generate_styles(
			array(
				'base_attr_name' => "{$slide_type}_icon_order",
				'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .slide-element.squad-icon-wrapper",
				'css_property'   => 'order',
				'render_slug'    => $this->slug,
				'type'           => 'input',
			)
		);
		$this->generate_styles(
			array(
				'base_attr_name' => "{$slide_type}_title_order",
				'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .slide-element.slide-title-wrapper",
				'css_property'   => 'order',
				'render_slug'    => $this->slug,
				'type'           => 'input',
			)
		);
		$this->generate_styles(
			array(
				'base_attr_name' => "{$slide_type}_sub_title_order",
				'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .slide-element.slide-sub-title-wrapper",
				'css_property'   => 'order',
				'render_slug'    => $this->slug,
				'type'           => 'input',
			)
		);
		$this->generate_styles(
			array(
				'base_attr_name' => "{$slide_type}_body_order",
				'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .slide-element.slide-content-wrapper",
				'css_property'   => 'order',
				'render_slug'    => $this->slug,
				'type'           => 'input',
			)
		);
		$this->generate_styles(
			array(
				'base_attr_name' => "{$slide_type}_button_order",
				'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .slide-element.slide-button-wrapper",
				'css_property'   => 'order',
				'render_slug'    => $this->slug,
				'type'           => 'input',
			)
		);

		// z index system.
		$this->generate_styles(
			array(
				'base_attr_name' => "{$slide_type}_icon_z_index",
				'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .slide-element.squad-icon-wrapper",
				'css_property'   => 'z-index',
				'render_slug'    => $this->slug,
				'type'           => 'input',
			)
		);
		$this->generate_styles(
			array(
				'base_attr_name' => "{$slide_type}_title_z_index",
				'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .slide-element.slide-title-wrapper",
				'css_property'   => 'z-index',
				'render_slug'    => $this->slug,
				'type'           => 'input',
			)
		);
		$this->generate_styles(
			array(
				'base_attr_name' => "{$slide_type}_sub_title_z_index",
				'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .slide-element.slide-sub-title-wrapper",
				'css_property'   => 'z-index',
				'render_slug'    => $this->slug,
				'type'           => 'input',
			)
		);
		$this->generate_styles(
			array(
				'base_attr_name' => "{$slide_type}_body_z_index",
				'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .slide-element.slide-content-wrapper",
				'css_property'   => 'z-index',
				'render_slug'    => $this->slug,
				'type'           => 'input',
			)
		);
		$this->generate_styles(
			array(
				'base_attr_name' => "{$slide_type}_button_z_index",
				'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .slide-element.slide-button-wrapper",
				'css_property'   => 'z-index',
				'render_slug'    => $this->slug,
				'type'           => 'input',
			)
		);
	}

	/**
	 * Render the icon which on is active
	 *
	 * @param string $slide_type The slide type.
	 * @param array  $attrs      List of unprocessed attributes.
	 *
	 * @return string
	 */
	private function squad_render_slide_icons( string $slide_type, array $attrs ): string {
		if ( 'none' !== $this->props[ "{$slide_type}_icon_type" ] ) {

			// Fixed: a custom background doesn't work at frontend.
			$this->props     = array_merge( $attrs, $this->props );
			$multi_view      = et_pb_multi_view_options( $this );
			$icon_element    = null;
			$wrapper_classes = array(
				'slide-element',
				"slide-$slide_type-element",
				'slide-icon-element',
				"slide-$slide_type-icon-element",
				'squad-icon-wrapper',
			);

			if ( 'text' === $this->props[ "{$slide_type}_icon_type" ] ) {
				if ( empty( $this->props[ "{$slide_type}_icon_text" ] ) ) {
					return '';
				}

				$icon_element = $multi_view->render_element(
					array(
						'content'        => "{{{$slide_type}_icon_text}}",
						'attrs'          => array(
							'class' => "slide-icon-text slide-$slide_type-icon-text",
						),
						'hover_selector' => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide",
					)
				);
			}

			if ( 'icon' === $this->props[ "{$slide_type}_icon_type" ] ) {
				if ( empty( $this->props[ "{$slide_type}_icon" ] ) ) {
					return '';
				}

				$icon_classes = array( 'et-pb-icon', 'slide-font-icon', "slide-$slide_type-icon" );

				// Load font Awesome css for frontend.
				Divi::inject_fa_icons( $this->props[ "{$slide_type}_icon" ] );

				$icon_element = $multi_view->render_element(
					array(
						'content'        => "{{{$slide_type}_icon}}",
						'attrs'          => array(
							'class' => implode( ' ', $icon_classes ),
						),
						'hover_selector' => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide",
					)
				);

				// Set font family for Icon.
				$this->generate_styles(
					array(
						'utility_arg'    => 'icon_font_family',
						'render_slug'    => $this->slug,
						'base_attr_name' => "{$slide_type}_icon",
						'important'      => true,
						'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .slide-element.slide-icon-element .et-pb-icon",
						'processor'      => array(
							'ET_Builder_Module_Helper_Style_Processor',
							'process_extended_icon',
						),
					)
				);

				// Set color for Icon.
				$this->generate_styles(
					array(
						'base_attr_name' => "{$slide_type}_icon_color",
						'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .slide-element.slide-icon-element .et-pb-icon",
						'hover_selector' => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide:hover .slide-element.slide-icon-element .et-pb-icon",
						'css_property'   => 'color',
						'render_slug'    => $this->slug,
						'type'           => 'color',
						'important'      => true,
					)
				);

				// Set size for Icon.
				$this->generate_styles(
					array(
						'base_attr_name' => "{$slide_type}_icon_size",
						'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .slide-element.slide-icon-element .et-pb-icon",
						'hover_selector' => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide:hover .slide-element.slide-icon-element .et-pb-icon",
						'css_property'   => 'font-size',
						'render_slug'    => $this->slug,
						'type'           => 'range',
						'important'      => true,
					)
				);
				$this->generate_styles(
					array(
						'base_attr_name' => "{$slide_type}_icon_size",
						'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .slide-element.slide-icon-element",
						'hover_selector' => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide:hover .slide-element.slide-icon-element",
						'css_property'   => 'min-width',
						'render_slug'    => $this->slug,
						'type'           => 'range',
						'important'      => true,
					)
				);
			}

			if ( 'image' === $this->props[ "{$slide_type}_icon_type" ] ) {
				if ( empty( $this->props[ "{$slide_type}_image" ] ) ) {
					return '';
				}

				$alt_text = $this->_esc_attr( "{$slide_type}_alt" );

				$image_classes   = array( 'slide-icon-image', "slide-$slide_type-icon-image", 'et_pb_image_wrap' );
				$image_classes[] = esc_attr( et_pb_media_options()->get_image_attachment_class( $this->props, 'image' ) );

				$icon_element = $multi_view->render_element(
					array(
						'tag'            => 'img',
						'attrs'          => array(
							'src'   => "{{{$slide_type}_image}}",
							'class' => implode( ' ', $image_classes ),
							'alt'   => $alt_text,
						),
						'required'       => "{$slide_type}_image",
						'hover_selector' => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide",
					)
				);

				// Set icon background color.
				$this->generate_styles(
					array(
						'base_attr_name' => "{$slide_type}_image_icon_background_color",
						'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .slide-element.slide-icon-element",
						'hover_selector' => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide:hover .slide-element.slide-icon-element",
						'css_property'   => 'background-color',
						'render_slug'    => $this->slug,
						'type'           => 'color',
						'important'      => true,
					)
				);

				// Set width for Image.
				if ( 'on' === $this->prop( "{$slide_type}_image_force_full_width", 'off' ) ) {
					self::set_style(
						$this->slug,
						array(
							'selector'    => implode(
								', ',
								array(
									"$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .slide-element.slide-icon-element",
									"$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .slide-element.slide-icon-element img",
								)
							),
							'declaration' => 'width: 100% !important; max-width:100% !important;',
						)
					);
				} else {
					$this->generate_styles(
						array(
							'base_attr_name' => "{$slide_type}_image_width",
							'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .slide-element.slide-icon-element img",
							'hover_selector' => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide:hover .slide-element.slide-icon-element img",
							'css_property'   => 'width',
							'render_slug'    => $this->slug,
							'type'           => 'range',
							'important'      => true,
						)
					);
					$this->generate_styles(
						array(
							'base_attr_name' => "{$slide_type}_image_width",
							'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .slide-element.slide-icon-element",
							'hover_selector' => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide:hover .slide-element.slide-icon-element",
							'css_property'   => 'min-width',
							'render_slug'    => $this->slug,
							'type'           => 'range',
							'important'      => true,
						)
					);
				}

				// Set height for Image.
				$this->generate_styles(
					array(
						'base_attr_name' => "{$slide_type}_image_height",
						'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .slide-element.slide-icon-element img",
						'hover_selector' => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide:hover .slide-element.slide-icon-element img",
						'css_property'   => 'height',
						'render_slug'    => $this->slug,
						'type'           => 'range',
						'important'      => true,
					)
				);
			}

			// Icon wrapper margin with default, responsive, hover.
			$this->squad_utils->field_css_generations->generate_margin_padding_styles(
				array(
					'field'          => "{$slide_type}_icon_wrapper_margin",
					'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .slide-element.slide-icon-element",
					'hover_selector' => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide:hover .slide-element.slide-icon-element",
					'css_property'   => 'margin',
					'type'           => 'margin',
					'important'      => true,
				)
			);
			$this->squad_utils->field_css_generations->generate_margin_padding_styles(
				array(
					'field'          => "{$slide_type}_icon_wrapper_padding",
					'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .slide-element.slide-icon-element",
					'hover_selector' => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide:hover .slide-element.slide-icon-element",
					'css_property'   => 'padding',
					'type'           => 'padding',
					'important'      => true,
				)
			);
			$this->squad_utils->field_css_generations->generate_margin_padding_styles(
				array(
					'field'          => "{$slide_type}_icon_margin",
					'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .slide-element.slide-icon-element",
					'hover_selector' => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide:hover .slide-element.slide-icon-element",
					'css_property'   => 'margin',
					'type'           => 'margin',
					'important'      => true,
				)
			);
			$this->squad_utils->field_css_generations->generate_margin_padding_styles(
				array(
					'field'          => "{$slide_type}_icon_padding",
					'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .slide-element.slide-icon-element",
					'hover_selector' => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide:hover .slide-element.slide-icon-element",
					'css_property'   => 'padding',
					'type'           => 'padding',
					'important'      => true,
				)
			);

			// Set icon background color.
			if ( 'image' !== $this->props[ "{$slide_type}_icon_type" ] ) {
				$this->generate_styles(
					array(
						'base_attr_name' => "{$slide_type}_image_icon_background_color",
						'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .slide-element.slide-icon-element",
						'hover_selector' => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide:hover .slide-element.slide-icon-element",
						'css_property'   => 'background-color',
						'render_slug'    => $this->slug,
						'type'           => 'color',
						'important'      => true,
					)
				);
			}

			if ( 'on' === $this->props[ "{$slide_type}_content_outside_container" ] ) {
				$this->generate_styles(
					array(
						'base_attr_name' => "{$slide_type}_icon_item_inner_gap",
						'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .flip-slide-container slide-$slide_type-elements-container",
						'css_property'   => 'gap',
						'render_slug'    => $this->slug,
						'type'           => 'gap',
						'important'      => true,
					)
				);
				$this->generate_styles(
					array(
						'base_attr_name' => "{$slide_type}_icon_horizontal_alignment",
						'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .flip-slide-outer-container slide-$slide_type-elements-outer-container",
						'css_property'   => 'justify-content',
						'render_slug'    => $this->slug,
						'type'           => 'align',
						'important'      => true,
					)
				);
				$this->generate_styles(
					array(
						'base_attr_name' => "{$slide_type}_icon_text_gap",
						'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide",
						'css_property'   => 'gap',
						'render_slug'    => $this->slug,
						'type'           => 'gap',
						'important'      => true,
					)
				);

				// Icon placement with default, responsive, hover.
				$this->generate_styles(
					array(
						'base_attr_name' => "{$slide_type}_icon_placement",
						'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide",
						'css_property'   => 'flex-direction',
						'render_slug'    => $this->slug,
						'type'           => 'align',
						'important'      => true,
					)
				);

				// working with icon styles.
				$placement    = "{$slide_type}_icon_placement";
				$en_placement = array( 'row', 'row-reverse' );
				$is_desktop   = in_array( $this->prop( $placement ), $en_placement, true );
				$is_tablet    = in_array( $this->prop( "{$placement}_tablet" ), $en_placement, true );
				$is_phone     = in_array( $this->prop( "{$placement}_phone" ), $en_placement, true );
				if ( $is_desktop || $is_tablet || $is_phone ) {
					$this->generate_styles(
						array(
							'base_attr_name' => "{$slide_type}_icon_vertical_alignment",
							'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .slide-element.slide-icon-element",
							'css_property'   => 'align-items',
							'render_slug'    => $this->slug,
							'type'           => 'align',
							'important'      => true,
						)
					);
					$this->generate_styles(
						array(
							'base_attr_name' => "{$slide_type}_icon_wrapper_width",
							'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .flip-slide-outer-container slide-$slide_type-elements-outer-container",
							'css_property'   => 'width',
							'render_slug'    => $this->slug,
							'type'           => 'input',
							'important'      => true,
						)
					);
				} else {
					$this->generate_styles(
						array(
							'base_attr_name' => "{$slide_type}_icon_horizontal_alignment",
							'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .squad-icon-wrapper",
							'css_property'   => 'text-align',
							'render_slug'    => $this->slug,
							'type'           => 'align',
							'important'      => true,
						)
					);
				}
			} else {
				$this->generate_styles(
					array(
						'base_attr_name' => "{$slide_type}_icon_item_inner_gap",
						'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .flip-slide-inner",
						'css_property'   => 'gap',
						'render_slug'    => $this->slug,
						'type'           => 'gap',
						'important'      => true,
					)
				);
				$this->generate_styles(
					array(
						'base_attr_name' => "{$slide_type}_icon_default_alignment",
						'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .slide-element.slide-icon-element",
						'css_property'   => 'justify-content',
						'render_slug'    => $this->slug,
						'type'           => 'align',
						'important'      => true,
					)
				);
			}

			return sprintf(
				'<span class="%1$s"><span class="icon-element">%2$s</span></span>',
				implode( ' ', $wrapper_classes ),
				wp_kses_post( $icon_element )
			);
		}

		return '';
	}

	/**
	 * Render all text elements for slide with dynamic and multiview support for Flip Box.
	 *
	 * @param string $slide_type The slide type.
	 * @param array  $attrs      List of unprocessed attributes.
	 *
	 * @return string
	 */
	private function squad_render_slide_elements( string $slide_type, array $attrs ): string {
		$multi_view = et_pb_multi_view_options( $this );

		$title_text_element     = null;
		$sub_title_text_element = null;
		$body_text_element      = null;

		$title_text     = $multi_view->render_element(
			array(
				'content'        => "{{{$slide_type}_title}}",
				'hover_selector' => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide",
			)
		);
		$sub_title_text = $multi_view->render_element(
			array(
				'content'        => "{{{$slide_type}_sub_title}}",
				'hover_selector' => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide",
			)
		);
		$body_text      = $multi_view->render_element(
			array(
				'content'        => "{{{$slide_type}_content}}",
				'hover_selector' => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide",
			)
		);

		if ( '' !== $title_text ) {
			// title margin with default, responsive, hover.
			$this->squad_utils->field_css_generations->generate_margin_padding_styles(
				array(
					'field'        => "{$slide_type}_title_margin",
					'selector'     => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .slide-title-text",
					'css_property' => 'margin',
					'type'         => 'margin',
				)
			);
			$this->squad_utils->field_css_generations->generate_margin_padding_styles(
				array(
					'field'        => "{$slide_type}_title_padding",
					'selector'     => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .slide-title-text",
					'css_property' => 'padding',
					'type'         => 'padding',
				)
			);

			$title_text_element = sprintf(
				'<div class="slide-element slide-%3$s-element slide-title-wrapper"><%1$s class="slide-title-text">%2$s</%1$s></div>',
				wp_kses_post( $this->prop( "{$slide_type}_title_tag", 'h2' ) ),
				wp_kses_post( $title_text ),
				$slide_type
			);
		}

		if ( '' !== $sub_title_text ) {
			// The subtitle margin with default, responsive, hover.
			$this->squad_utils->field_css_generations->generate_margin_padding_styles(
				array(
					'field'        => "{$slide_type}_sub_title_margin",
					'selector'     => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .slide-sub-title-text",
					'css_property' => 'margin',
					'type'         => 'margin',
				)
			);
			$this->squad_utils->field_css_generations->generate_margin_padding_styles(
				array(
					'field'        => "{$slide_type}_sub_title_padding",
					'selector'     => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .slide-sub-title-text",
					'css_property' => 'padding',
					'type'         => 'padding',
				)
			);

			$sub_title_text_element = sprintf(
				'<div class="slide-element slide-%3$s-element slide-title-wrapper"><%1$s class="slide-sub-title-text">%2$s</%1$s></div>',
				wp_kses_post( $this->prop( "{$slide_type}_sub_title_tag", 'h2' ) ),
				wp_kses_post( $sub_title_text ),
				$slide_type
			);
		}

		if ( '' !== $body_text ) {
			// content margin with default, responsive, hover.
			$this->squad_utils->field_css_generations->generate_margin_padding_styles(
				array(
					'field'        => "{$slide_type}_content_margin",
					'selector'     => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .slide-content-text",
					'css_property' => 'margin',
					'type'         => 'margin',
				)
			);
			$this->squad_utils->field_css_generations->generate_margin_padding_styles(
				array(
					'field'        => "{$slide_type}_content_padding",
					'selector'     => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .slide-content-text",
					'css_property' => 'padding',
					'type'         => 'padding',
				)
			);

			$sub_title_text_element = sprintf(
				'<div class="slide-element slide-%2$s-element slide-content-wrapper"><span class="slide-content-text">%1$s</span></div>',
				wp_kses_post( $body_text ),
				$slide_type
			);
		}

		$button_text_element = ( 'on' === $this->prop( "{$slide_type}_button__enable", 'off' ) ) ? $this->squad_render_button_text( $slide_type, $attrs ) : null;

		if ( 'on' === $this->prop( "{$slide_type}_content_outside_container", 'off' ) ) {
			return sprintf(
				'<div class="flip-slide-container slide-%5$s-elements-container">%1$s%2$s%3$s%4$s</div>',
				$title_text_element,
				$sub_title_text_element,
				$body_text_element,
				$button_text_element,
				$slide_type
			);
		}

		return sprintf(
			'%1$s%2$s%3$s%4$s',
			$title_text_element,
			$sub_title_text_element,
			$body_text_element,
			$button_text_element
		);
	}

	/**
	 * Render button text with icon.
	 *
	 * @param string $slide_type The slide type.
	 * @param array  $attrs      List of unprocessed attributes.
	 *
	 * @return string
	 */
	private function squad_render_button_text( string $slide_type, array $attrs ): string {
		$multi_view = et_pb_multi_view_options( $this );

		// title url and its target.
		$button_url    = $this->prop( "{$slide_type}_button_url", '' );
		$button_target = $this->prop( "{$slide_type}_button_url_new_window", 'off' );

		$button_tag   = '' !== $button_url ? 'a' : 'span';
		$button_attrs = array();

		if ( 'a' === $button_tag ) {
			$button_attrs['href'] = $button_url;

			if ( 'on' === $button_target ) {
				$button_attrs['target'] = '_blank';
			} else {
				$button_attrs['target'] = '_self';
			}
		}

		$button_text = $multi_view->render_element(
			array(
				'tag'            => $button_tag,
				'content'        => "{{{$slide_type}_button_text}}",
				'attrs'          => $button_attrs,
				'hover_selector' => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide",
			)
		);

		if ( '' !== $button_text ) {
			// Fixed: the custom background doesn't work at frontend.
			$this->props = array_merge( $attrs, $this->props );

			$icon_elements      = '';
			$icon_wrapper_class = array( 'squad-icon-wrapper' );
			$button_classes     = array( 'squad-slide-button', 'et_pb_with_background' );

			if ( 'on' === $this->prop( "{$slide_type}_button_hover_animation__enable", 'off' ) ) {
				$button_classes[] = $this->prop( "{$slide_type}_button_hover_animation_type", 'fill' );
			}

			// button background with default, responsive, hover.
			et_pb_background_options()->get_background_style(
				array(
					'base_prop_name'         => "{$slide_type}_button_background",
					'props'                  => $this->props,
					'selector'               => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .squad-slide-button",
					'selector_hover'         => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .squad-slide-button:hover",
					'selector_sticky'        => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .squad-slide-button",
					'function_name'          => $this->slug,
					'important'              => ' !important',
					'use_background_video'   => false,
					'use_background_pattern' => false,
					'use_background_mask'    => false,
					'prop_name_aliases'      => array(
						"use_{$slide_type}_button_background_color_gradient" => "{$slide_type}_button_background_use_color_gradient",
						"{$slide_type}_button_background"                    => "{$slide_type}_button_background_color",
					),
				)
			);

			$this->generate_styles(
				array(
					'base_attr_name' => "{$slide_type}_button_horizontal_alignment",
					'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .slide-element.slide-$slide_type-button-wrapper",
					'css_property'   => 'justify-content',
					'render_slug'    => $this->slug,
					'type'           => 'align',
					'important'      => true,
				)
			);
			$this->generate_styles(
				array(
					'base_attr_name' => "{$slide_type}_button_elements_alignment",
					'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .squad-slide-button",
					'hover_selector' => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .squad-slide-button:hover",
					'css_property'   => 'justify-content',
					'render_slug'    => $this->slug,
					'type'           => 'align',
					'important'      => true,
				)
			);
			$this->generate_styles(
				array(
					'base_attr_name' => "{$slide_type}_button_width",
					'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .squad-slide-button",
					'hover_selector' => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .squad-slide-button:hover",
					'css_property'   => 'width',
					'render_slug'    => $this->slug,
					'type'           => 'input',
					'important'      => true,
				)
			);

			$this->generate_styles(
				array(
					'base_attr_name' => "{$slide_type}_button_icon_placement",
					'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .squad-slide-button",
					'hover_selector' => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .squad-slide-button:hover",
					'css_property'   => 'flex-direction',
					'render_slug'    => $this->slug,
					'type'           => 'align',
					'important'      => true,
				)
			);
			$this->generate_styles(
				array(
					'base_attr_name' => "{$slide_type}_button_icon_gap",
					'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .squad-slide-button",
					'hover_selector' => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .squad-slide-button:hover",
					'css_property'   => 'gap',
					'render_slug'    => $this->slug,
					'type'           => 'input',
					'important'      => true,
				)
			);

			// button margin with default, responsive, hover.
			$this->squad_utils->field_css_generations->generate_margin_padding_styles(
				array(
					'field'          => "{$slide_type}_button_icon_margin",
					'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .squad-slide-button .icon-element",
					'hover_selector' => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .squad-slide-button .icon-element:hover",
					'css_property'   => 'margin',
					'type'           => 'margin',
				)
			);
			$this->squad_utils->field_css_generations->generate_margin_padding_styles(
				array(
					'field'          => "{$slide_type}_button_margin",
					'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .squad-slide-button",
					'hover_selector' => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .squad-slide-button:hover",
					'css_property'   => 'margin',
					'type'           => 'margin',
				)
			);
			$this->squad_utils->field_css_generations->generate_margin_padding_styles(
				array(
					'field'          => "{$slide_type}_button_padding",
					'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .squad-slide-button",
					'hover_selector' => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .squad-slide-button:hover",
					'css_property'   => 'padding',
					'type'           => 'padding',
				)
			);

			$font_icon_element = $this->squad_render_button_font_icon( $slide_type );
			$image_element     = $this->squad_render_button_icon_image( $slide_type );

			if ( ( 'none' !== $this->props[ "{$slide_type}_button_icon_type" ] ) && ( ! empty( $font_icon_element ) || ! empty( $image_element ) ) ) {
				if ( ( 'on' === $this->prop( "{$slide_type}_button_icon_on_hover", 'off' ) ) ) {
					$icon_wrapper_class[] = 'show-on-hover';

					$mapping_values = array(
						'inherit'     => '0 0 0 0',
						'column'      => '0 0 -#px 0',
						'row'         => '0 -#px 0 0',
						'row-reverse' => '0 0 0 -#px',
					);

					if ( 'on' === $this->prop( "{$slide_type}_button_icon_hover_move_icon", 'off' ) ) {
						$mapping_values = array(
							'inherit'     => '0 0 0 0',
							'column'      => '#px 0 -#px 0',
							'row'         => '0 -#px 0 #px',
							'row-reverse' => '0 #px 0 -#px',
						);
					}

					// set icon placement for button image with default, hover, and responsive.
					$this->squad_utils->field_css_generations->generate_show_icon_on_hover_styles(
						array(
							'field'          => "{$slide_type}_button_icon_placement",
							'trigger'        => "{$slide_type}_button_icon_type",
							'depends_on'     => array(
								'icon'  => "{$slide_type}_button_icon_size",
								'image' => "{$slide_type}_button_image_width",
							),
							'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .squad-slide-button .squad-icon-wrapper.show-on-hover",
							'hover'          => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .squad-slide-button:hover .squad-icon-wrapper.show-on-hover",
							'css_property'   => 'margin',
							'type'           => 'margin',
							'mapping_values' => $mapping_values,
							'defaults'       => array(
								'icon'  => '40px',
								'image' => '40px',
								'field' => 'row',
							),
						)
					);
				}

				$icon_elements = sprintf(
					'<span class="%1$s"><span class="icon-element">%2$s%3$s</span></span>',
					implode( ' ', $icon_wrapper_class ),
					wp_kses_post( $font_icon_element ),
					wp_kses_post( $image_element )
				);
			}

			return sprintf(
				'<div class="slide-element slide-%4$s-element slide-%4$s-button-wrapper"><div class="%3$s">%1$s%2$s</div></div>',
				wp_kses_post( $button_text ),
				wp_kses_post( $icon_elements ),
				wp_kses_post( implode( ' ', $button_classes ) ),
				$slide_type
			);
		}

		return '';
	}

	/**
	 * Render button icon.
	 *
	 * @param string $slide_type The slide type.
	 *
	 * @return string
	 */
	private function squad_render_button_font_icon( string $slide_type ): string {
		if ( 'icon' === $this->props[ "{$slide_type}_button_icon_type" ] ) {
			$multi_view   = et_pb_multi_view_options( $this );
			$icon_classes = array( 'et-pb-icon', "squad-{$slide_type}_button-icon" );

			// Load font Awesome css for frontend.
			Divi::inject_fa_icons( $this->props[ "{$slide_type}_button_icon" ] );

			$this->generate_styles(
				array(
					'utility_arg'    => 'icon_font_family',
					'render_slug'    => $this->slug,
					'base_attr_name' => "{$slide_type}_button_icon",
					'important'      => true,
					'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .squad-slide-button .et-pb-icon",
					'processor'      => array(
						'ET_Builder_Module_Helper_Style_Processor',
						'process_extended_icon',
					),
				)
			);
			$this->generate_styles(
				array(
					'base_attr_name' => "{$slide_type}_button_icon_color",
					'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .squad-slide-button .et-pb-icon",
					'hover_selector' => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .squad-slide-button:hover .et-pb-icon",
					'css_property'   => 'color',
					'render_slug'    => $this->slug,
					'type'           => 'color',
					'important'      => true,
				)
			);
			$this->generate_styles(
				array(
					'base_attr_name' => "{$slide_type}_button_icon_size",
					'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .squad-slide-button .et-pb-icon",
					'hover_selector' => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .squad-slide-button:hover .et-pb-icon",
					'css_property'   => 'font-size',
					'render_slug'    => $this->slug,
					'type'           => 'range',
					'important'      => true,
				)
			);

			return $multi_view->render_element(
				array(
					'content'        => "{{{$slide_type}_button_icon}}",
					'attrs'          => array(
						'class' => implode( ' ', $icon_classes ),
					),
					'hover_selector' => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .squad-slide-button",
				)
			);
		}

		return '';
	}

	/**
	 * Render button image.
	 *
	 * @param string $slide_type The slide type.
	 *
	 * @return string
	 */
	private function squad_render_button_icon_image( string $slide_type ): string {
		if ( 'icon' === $this->props[ "{$slide_type}_button_icon_type" ] ) {
			$multi_view             = et_pb_multi_view_options( $this );
			$image_classes          = array( "squad-{$slide_type}_button-image", 'et_pb_image_wrap' );
			$image_attachment_class = et_pb_media_options()->get_image_attachment_class( $this->props, "{$slide_type}_button_image" );

			if ( ! empty( $image_attachment_class ) ) {
				$image_classes[] = esc_attr( $image_attachment_class );
			}

			// Set width for Image.
			$this->generate_styles(
				array(
					'base_attr_name' => "{$slide_type}_button_image_width",
					'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .squad-slide-button .squad-icon-wrapper img",
					'hover_selector' => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .squad-slide-button:hover .squad-icon-wrapper img",
					'css_property'   => 'width',
					'render_slug'    => $this->slug,
					'type'           => 'range',
					'important'      => true,
				)
			);
			// Set height for Image.
			$this->generate_styles(
				array(
					'base_attr_name' => "{$slide_type}_button_image_height",
					'selector'       => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .squad-slide-button .squad-icon-wrapper img",
					'hover_selector' => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .squad-slide-button:hover .squad-icon-wrapper img",
					'css_property'   => 'height',
					'render_slug'    => $this->slug,
					'type'           => 'range',
					'important'      => true,
				)
			);

			return $multi_view->render_element(
				array(
					'tag'            => 'img',
					'attrs'          => array(
						'src'   => "{{{$slide_type}_button_image}}",
						'class' => implode( ' ', $image_classes ),
						'alt'   => '',
					),
					'required'       => "{$slide_type}_button_image",
					'hover_selector' => "$this->main_css_element div .flip-box .flip-box-slides .$slide_type-slide .squad-slide-button",
				)
			);
		}

		return '';
	}
}
