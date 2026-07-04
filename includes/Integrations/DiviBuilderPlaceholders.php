<?php // phpcs:ignore WordPress.Files.FileName
/**
 * The DiviBackend integration helper for Divi Builder
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   1.0.0
 */

namespace DiviSquad\Integrations;

use DiviSquad\Base\DiviBuilder\Placeholder;
use DiviSquad\Utils\Helper;
use function et_fb_process_shortcode;
use function et_fb_remove_site_url_protocol;
use function wp_json_encode;

/**
 * Define integration helper functionalities for this plugin.
 *
 * @package DiviSquad
 * @since   1.0.0
 */
final class DiviBuilderPlaceholders extends Placeholder {

	/**
	 * The constructor.
	 *
	 * @since 3.3.0
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 3.3.0
	 *
	 * @return void
	 */
	public function init_hooks(): void {
		add_filter( 'et_fb_backend_helpers', array( $this, 'static_asset_definitions' ), 11 );
		add_filter( 'et_fb_get_asset_helpers', array( $this, 'asset_definitions' ), 11 );
	}

	/**
	 * Generate a shortcode for a module with default values.
	 *
	 * @since 3.3.0
	 *
	 * @param string               $module_slug The module slug.
	 * @param array<string, mixed> $attributes  The attributes for the module.
	 *
	 * @return string Generated shortcode.
	 */
	private function generate_module_shortcode( string $module_slug, array $attributes = array() ): string {
		// Format attributes for shortcode.
		$attributes_string = Helper::implode_assoc_array( $attributes );

		return sprintf( '[%s %s][/%s]', $module_slug, $attributes_string, $module_slug );
	}

	/**
	 * Used to update the content of the cached definitions js file.
	 *
	 * @param string $content The content to update.
	 *
	 * @return string
	 */
	public function asset_definitions( $content ): string {
		$definitions = $this->static_asset_definitions();

		/**
		 * Filter the JavaScript definitions before they are encoded.
		 *
		 * @since 3.3.0
		 *
		 * @param array  $definitions The module definitions.
		 * @param string $content     The original content.
		 */
		$definitions = apply_filters( 'divi_squad_asset_definitions_before_encode', $definitions, $content );

		return $content . sprintf(
			';window.DISQBuilderBackend=%1$s; if(window.jQuery) {jQuery.extend(true, window.ETBuilderBackend, window.DISQBuilderBackend);}',
			et_fb_remove_site_url_protocol( (string) wp_json_encode( $definitions ) )
		);
	}

	/**
	 * Get divider configuration.
	 *
	 * @since 3.3.0
	 *
	 * @param array<string, mixed> $defaults The default values.
	 *
	 * @return array<string, string> Divider configuration.
	 */
	private function get_divider_config( array $defaults ): array {
		$config = array(
			'divider_type'                       => 'icon',
			'divider_icon'                       => $defaults['icon']['check'],
			'divider_icon_type'                  => 'icon',
			'divider_style'                      => 'solid',
			'divider_position'                   => 'center',
			'divider_weight'                     => '2px',
			'divider_max_width'                  => '100%',
			'divider_border_radius'              => '0px',
			'divider_element_placement'          => 'center',
			'divider_element_gap'                => '10px',
			'divider_custom_size'                => 'off',
			'use_divider_custom_color'           => 'off',
			'multiple_divider'                   => 'off',
			'multiple_divider_no'                => '2',
			'multiple_divider_gap'               => '10px',
			'divider_icon_color'                 => et_builder_accent_color(),
			'divider_icon_size'                  => '32px',
			'divider_icon_text'                  => _x( 'Divider Text', 'Modules dummy content', 'squad-modules-for-divi' ),
			'divider_icon_text_tag'              => 'h2',
			'divider_icon_text_font_size'        => '16px',
			'divider_icon_text_letter_spacing'   => '0px',
			'divider_icon_text_line_height'      => '1.7em',
			'divider_icon_lottie_src_type'       => 'local',
			'divider_icon_lottie_trigger_method' => 'freeze-click',
			'divider_icon_lottie_loop'           => 'off',
			'divider_icon_lottie_speed'          => '1',
			'divider_icon_lottie_direction'      => '1',
			'divider_image_width'                => '32px',
			'divider_image_height'               => '32px',
			'wrapper_background_color'           => '#ffffff',
			'wrapper_margin'                     => '0px|0px|0px|0px',
			'wrapper_padding'                    => '10px|15px|10px|15px',
		);

		/**
		 * Filter divider configuration.
		 *
		 * @since 3.3.0
		 *
		 * @param array $config   Divider configuration.
		 * @param array $defaults Default values array.
		 */
		return apply_filters( 'divi_squad_divider_config', $config, $defaults );
	}

	/**
	 * Get image gallery configuration.
	 *
	 * @since 3.3.0
	 *
	 * @param array<string, mixed> $defaults The default values.
	 *
	 * @return array<string, string> Image gallery configuration.
	 */
	private function get_image_gallery_config( array $defaults ): array {
		$config = array(
			// Gallery Settings.
			'orientation'         => 'landscape',
			'gallery_order_by'    => 'default',
			'images_quantity'     => 'default',
			'gallery_image_count' => '4',
			'columns_count'       => '4',
			'images_inner_gap'    => '10px',
			'show_in_lightbox'    => 'off',
			'hover_icon'          => $defaults['icon']['zoom'],
			'zoom_icon_color'     => '#ffffff',
			'hover_overlay_color' => 'rgba(0, 0, 0, 0.5)',
		);

		/**
		 * Filter image gallery configuration.
		 *
		 * @since 3.3.0
		 *
		 * @param array $config   Image gallery configuration.
		 * @param array $defaults Default values array.
		 */
		return apply_filters( 'divi_squad_image_gallery_config', $config, $defaults );
	}

	/**
	 * Get image mask configuration.
	 *
	 * @since 3.3.0
	 *
	 * @param array<string, mixed> $defaults The default values.
	 *
	 * @return array<string, string> Image mask configuration.
	 */
	private function get_image_mask_config( array $defaults ): array {
		$config = array(
			// Image Settings.
			'image'                     => $defaults['image']['landscape'],
			'alt'                       => _x( 'Image Mask', 'Modules dummy content', 'squad-modules-for-divi' ),

			// Mask Settings.
			'mask_shape_image'          => 'shapes-01',
			'mask_shape_rotate'         => '0deg',
			'mask_shape_scale_x'        => '1',
			'mask_shape_scale_y'        => '1',
			'mask_shape_flip'           => '',

			// Image Associated Settings.
			'image_width'               => '100%',
			'image_height'              => '100%',
			'image_horizontal_position' => '0',
			'image_vertical_position'   => '0',
		);

		/**
		 * Filter image mask configuration.
		 *
		 * @since 3.3.0
		 *
		 * @param array $config   Image mask configuration.
		 * @param array $defaults Default values array.
		 */
		return apply_filters( 'divi_squad_image_mask_config', $config, $defaults );
	}

	/**
	 * Get lottie configuration.
	 *
	 * @since 3.3.0
	 *
	 * @param array<string, mixed> $defaults The default values.
	 *
	 * @return array<string, string> Lottie configuration.
	 */
	private function get_lottie_config( array $defaults ): array {
		$config = array(
			// Lottie Fields.
			'lottie_src_type'        => 'remote',
			'lottie_src_remote'      => 'https://lottie.host/c404786e-2d84-4239-a092-5fa55366d5a7/DRPRrsgJH4.json',
			'lottie_src_upload'      => '',

			// Animation Fields.
			'lottie_trigger_method'  => 'freeze-click',
			'lottie_mouseout_action' => 'no_action',
			'lottie_click_action'    => 'no_action',
			'lottie_scroll'          => 'row',
			'lottie_play_on_hover'   => 'off',
			'lottie_loop'            => 'off',
			'lottie_loop_no_times'   => '0',
			'lottie_delay'           => '0',
			'lottie_speed'           => '1',
			'lottie_mode'            => 'normal',
			'lottie_direction'       => '1',
			'lottie_renderer'        => 'svg',

			// Associated Fields.
			'lottie_color'           => '',
			'lottie_width'           => '',
			'lottie_height'          => '',
		);

		/**
		 * Filter lottie configuration.
		 *
		 * @since 3.3.0
		 *
		 * @param array $config   Lottie configuration.
		 * @param array $defaults Default values array.
		 */
		return apply_filters( 'divi_squad_lottie_config', $config, $defaults );
	}

	/**
	 * Get video popup configuration.
	 *
	 * @since 3.3.0
	 *
	 * @param array<string, mixed> $defaults The default values.
	 *
	 * @return array<string, string> Video popup configuration.
	 */
	private function get_video_popup_config( array $defaults ): array {
		$config = array(
			// Content Settings.
			'use_overlay'         => 'on',
			'image'               => $defaults['image']['landscape'],
			'alt'                 => _x( 'Video Thumbnail', 'Modules dummy content', 'squad-modules-for-divi' ),
			'trigger_element'     => 'icon',
			'icon'                => '1',
			'text'                => _x( 'Play', 'Modules dummy content', 'squad-modules-for-divi' ),
			'type'                => 'yt',
			'video_link'          => 'https://www.youtube.com/watch?v=T-Oe01_J62c',
			'video'               => '',

			// Icon Settings.
			'icon_color'          => '#ffffff',
			'icon_bg'             => 'rgba(0, 0, 0, 0.8)',
			'icon_size'           => '32px',
			'icon_opacity'        => '1',
			'icon_height'         => '80px',
			'icon_width'          => '80px',
			'icon_radius'         => '50%',
			'icon_spacing'        => '10px',
			'icon_alignment'      => 'center',

			// Text Settings.
			'use_text_box'        => 'off',
			'text_bg'             => 'rgba(0, 0, 0, 0.8)',
			'text_padding'        => '10px|15px|10px|15px',
			'text_margin'         => '0px|0px|0px|0px',
			'text_radius'         => '3px',

			// Popup Settings.
			'popup_width'         => '100%',
			'popup_max_width'     => '1080px',
			'popup_height'        => 'auto',
			'popup_padding'       => '0px',
			'popup_margin'        => '0px',
			'popup_radius'        => '0px',
			'popup_bg'            => '#000000',
			'popup_overlay_color' => 'rgba(0, 0, 0, 0.8)',

			// Image Settings.
			'img_height'          => 'auto',
			'img_width'           => '100%',
		);

		/**
		 * Filter video popup configuration.
		 *
		 * @since 3.3.0
		 *
		 * @param array $config   Video popup configuration.
		 * @param array $defaults Default values array.
		 */
		return apply_filters( 'divi_squad_video_popup_config', $config, $defaults );
	}

	/**
	 * Get dual button configuration.
	 *
	 * @since 3.3.0
	 *
	 * @param array<string, mixed> $defaults The default values.
	 *
	 * @return array<string, string> Dual button configuration.
	 */
	private function get_dual_button_config( array $defaults ): array {
		$config = array(
			// Left Button Settings.
			'left_button_text'              => $defaults['button'],
			'left_button_icon'              => $defaults['icon']['check'],
			'left_button_icon_placement'    => 'right',
			'left_button_icon_on_hover'     => 'off',
			'left_button_url'               => '',
			'left_button_url_new_window'    => 'off',
			'left_button_background_color'  => et_builder_accent_color(),
			'left_button_text_color'        => '#ffffff',
			'left_button_border_radius'     => '3px',
			'left_button_border_width'      => '2px',
			'left_button_border_color'      => et_builder_accent_color(),
			'left_button_border_style'      => 'solid',

			// Right Button Settings.
			'right_button_text'             => $defaults['button_two'],
			'right_button_icon'             => $defaults['icon']['arrow'],
			'right_button_icon_placement'   => 'right',
			'right_button_icon_on_hover'    => 'off',
			'right_button_url'              => '',
			'right_button_url_new_window'   => 'off',
			'right_button_background_color' => et_builder_accent_color(),
			'right_button_text_color'       => '#ffffff',
			'right_button_border_radius'    => '3px',
			'right_button_border_width'     => '2px',
			'right_button_border_color'     => et_builder_accent_color(),
			'right_button_border_style'     => 'solid',

			// Separator Settings.
			'separator_text'                => '',
			'separator_icon__enable'        => 'off',
			'separator_icon'                => '',
			'separator_icon_placement'      => 'center',
			'separator_icon_on_hover'       => 'off',
			'separator_custom_width'        => 'off',
			'separator_background_color'    => '#ffffff',
			'separator_margin'              => '0px|0px|0px|0px',
			'separator_padding'             => '10px|15px|10px|15px',

			// Layout Settings.
			'wrapper_horizontal_alignment'  => 'left',
			'wrapper_elements_layout'       => 'row',
			'wrapper_elements_gap'          => '10px',
			'buttons_horizontal_alignment'  => 'left',
			'wrapper_background_color'      => '#ffffff',
			'wrapper_margin'                => '0px|0px|0px|0px',
			'wrapper_padding'               => '10px|15px|10px|15px',
		);

		/**
		 * Filter dual button configuration.
		 *
		 * @since 3.3.0
		 *
		 * @param array $config   Dual button configuration.
		 * @param array $defaults Default values array.
		 */
		return apply_filters( 'divi_squad_dual_button_config', $config, $defaults );
	}

	/**
	 * Get typing text configuration.
	 *
	 * @since 3.3.0
	 *
	 * @param array<string, mixed> $defaults The default values.
	 *
	 * @return array<string, string> Typing text configuration.
	 */
	private function get_typing_text_config( array $defaults ): array {
		$typing_text_default_text = array(
			array(
				'value'   => _x( 'Typing Text', 'Modules dummy content', 'squad-modules-for-divi' ),
				'checked' => 0,
				'dragID'  => - 1,
			),
			array(
				'value'   => _x( 'Typing Text 2', 'Modules dummy content', 'squad-modules-for-divi' ),
				'checked' => 0,
				'dragID'  => - 1,
			),
			array(
				'value'   => _x( 'Typing Text 3', 'Modules dummy content', 'squad-modules-for-divi' ),
				'checked' => 0,
				'dragID'  => - 1,
			),
		);

		$config = array(
			// Text Content.
			'prefix_text'                  => _x( 'Your', 'Modules dummy content', 'squad-modules-for-divi' ),
			'typing_text'                  => wp_json_encode( $typing_text_default_text ),
			'suffix_text'                  => _x( 'Goes Here', 'Modules dummy content', 'squad-modules-for-divi' ),
			'text_element_tag'             => 'h2',

			// Animation Settings.
			'typing_speed'                 => '100',
			'typing_start_delay'           => '0',
			'typing_back_speed'            => '50',
			'typing_back_delay'            => '2000',
			'typing_shuffle__enable'       => 'off',
			'typing_fade_out__enable'      => 'off',
			'typing_fade_out_delay'        => '500',
			'typing_loop__enable'          => 'off',

			// Cursor Settings.
			'typing_cursor__enable'        => 'on',
			'typing_cursor_character'      => '|',
			'custom_cursor_icon__enable'   => 'off',
			'typing_cursor_icon'           => '',
			'remove_cursor_on_end__enable' => 'off',

			// Layout Settings.
			'horizontal_alignment'         => 'left',
			'text_gap'                     => '10px',
			'wrapper_background_color'     => '#ffffff',
			'wrapper_margin'               => '0px|0px|0px|0px',
			'wrapper_padding'              => '10px|15px|10px|15px',
			'prefix_margin'                => '0px|0px|0px|0px',
			'prefix_padding'               => '0px|0px|0px|0px',
			'typed_margin'                 => '0px|0px|0px|0px',
			'typed_padding'                => '0px|0px|0px|0px',
		);

		/**
		 * Filter typing text configuration.
		 *
		 * @since 3.3.0
		 *
		 * @param array $config   Typing text configuration.
		 * @param array $defaults Default values array.
		 */
		return apply_filters( 'divi_squad_typing_text_config', $config, $defaults );
	}

	/**
	 * Get post grid configuration.
	 *
	 * @since 3.3.0
	 *
	 * @param array<string, mixed> $defaults The default values.
	 *
	 * @return array<string, string> Post grid configuration.
	 */
	private function get_post_grid_config( array $defaults ): array {
		$post_grid_child_defaults = $this->get_post_grid_child_defaults( $defaults );

		$post_icons_common = array(
			'element_icon_text_gap'             => '10px',
			'element_icon_placement'            => 'row',
			'element_icon_horizontal_alignment' => 'left',
			'element_icon_vertical_alignment'   => 'center',
		);

		$post_grid_elements = array(
			array( 'element' => 'featured_image' ),
			array(
				'element'           => 'title',
				'element_title_tag' => 'h2',
			),
			array( 'element' => 'content' ),
			array(
				'element'           => 'date',
				'element_icon'      => '&#xe023;||divi||400',
				'element_date_type' => 'modified',
			),
			array(
				'element'      => 'read_more',
				'element_icon' => '&#x35;||divi||400',
			),
		);

		/**
		 * Filter post grid elements configuration.
		 *
		 * @since 3.3.0
		 *
		 * @param array $post_grid_elements Array of post grid elements.
		 */
		$post_grid_elements = apply_filters( 'divi_squad_post_grid_elements', $post_grid_elements );

		$post_grid_shortcodes = array();
		foreach ( $post_grid_elements as $element_config ) {
			$attributes = array_merge(
				$post_grid_child_defaults,
				$element_config,
				isset( $element_config['element'] ) && in_array( $element_config['element'], array( 'date', 'read_more' ), true ) ? $post_icons_common : array(),
			);

			$post_grid_shortcodes[] = $this->generate_module_shortcode( 'disq_post_grid_child', $attributes );
		}

		$post_grid_child_shortcodes = implode( '', $post_grid_shortcodes );

		$config = array(
			'content'                            => et_fb_process_shortcode( $post_grid_child_shortcodes ),
			'list_number_of_columns_last_edited' => 'on|desktop',
			'list_number_of_columns'             => '3',
			'list_number_of_columns_tablet'      => '2',
			'list_number_of_columns_phone'       => '1',
			'list_item_gap'                      => '20px',
			'list_post_display_by'               => 'recent',
			'list_post_order_by'                 => 'date',
			'list_post_order'                    => 'ASC',
			'list_post_count'                    => '10',
			'list_post_offset'                   => '0',
			'list_post_ignore_sticky_posts'      => 'off',
			'list_post_include_categories'       => '',
			'list_post_include_tags'             => '',
			'inherit_current_loop'               => 'off',
			'post_text_orientation'              => 'left',
			'post_wrapper_background_color'      => '#ffffff',
			'post_wrapper_margin'                => '0px|0px|0px|0px',
			'post_wrapper_padding'               => '10px|15px|10px|15px',
			'element_wrapper_background_color'   => '#ffffff',
			'element_wrapper_margin'             => '0px|0px|0px|0px',
			'element_wrapper_padding'            => '10px|15px|10px|15px',
			'element_text_orientation'           => 'left',
			'pagination__enable'                 => 'on',
			'pagination_numbers__enable'         => 'on',
			'pagination_old_entries_text'        => _x( 'Older', 'Modules dummy content', 'squad-modules-for-divi' ),
			'pagination_next_entries_text'       => _x( 'Next', 'Modules dummy content', 'squad-modules-for-divi' ),
			'pagination_old_entries_icon'        => '&#x3c;||divi||400',
			'pagination_next_entries_icon'       => '&#x3d;||divi||400',
			'pagination_horizontal_alignment'    => 'center',
			'load_more__enable'                  => 'off',
			'load_more_button_text'              => _x( 'Load More', 'Modules dummy content', 'squad-modules-for-divi' ),
			'load_more_button_icon'              => '&#x35;||divi||400',
			'load_more_button_icon_placement'    => 'right',
			'load_more_button_icon_on_hover'     => 'off',
			'load_more_spinner_show'             => 'off',
		);

		/**
		 * Filter post grid configuration.
		 *
		 * @since 3.3.0
		 *
		 * @param array $config   Post grid configuration.
		 * @param array $defaults Default values array.
		 */
		return apply_filters( 'divi_squad_post_grid_config', $config, $defaults );
	}

	/**
	 * Get post grid child defaults.
	 *
	 * @since 3.3.0
	 *
	 * @param array<string, mixed> $defaults The default values.
	 *
	 * @return array<string, string> Post grid child defaults.
	 */
	private function get_post_grid_child_defaults( array $defaults ): array {
		return array(
			'element_image_fullwidth__enable'      => 'off',
			'element'                              => 'title',
			'element_title_tag'                    => 'h2',
			'element_title_font_size'              => '20px',
			'element_title_line_height'            => '1.2em',
			'element_title_text_color'             => '#333333',
			'element_title_background_color'       => '#ffffff',
			'element_title_margin'                 => '0px|0px|0px|0px',
			'element_title_padding'                => '0px|0px|0px|0px',
			'element_content'                      => _x( 'Your post content goes here. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.', 'Modules dummy content', 'squad-modules-for-divi' ),
			'element_content_font_size'            => '16px',
			'element_content_line_height'          => '1.7em',
			'element_content_text_color'           => '#666666',
			'element_content_background_color'     => '#ffffff',
			'element_content_margin'               => '0px|0px|0px|0px',
			'element_content_padding'              => '0px|0px|0px|0px',
			'element_excerpt__enable'              => 'off',
			'element_ex_con_length__enable'        => 'on',
			'element_ex_con_length'                => '30',
			'element_author_name_type'             => 'nickname',
			'element_author_font_size'             => '14px',
			'element_author_line_height'           => '1.7em',
			'element_author_text_color'            => '#666666',
			'element_author_background_color'      => '#ffffff',
			'element_author_margin'                => '0px|0px|0px|0px',
			'element_author_padding'               => '0px|0px|0px|0px',
			'element_author_icon'                  => '&#xe08a;||divi||400',
			'element_author_icon_color'            => et_builder_accent_color(),
			'element_author_icon_size'             => '16px',
			'element_author_icon_placement'        => 'left',
			'element_date_font_size'               => '14px',
			'element_date_line_height'             => '1.7em',
			'element_date_text_color'              => '#666666',
			'element_date_background_color'        => '#ffffff',
			'element_date_margin'                  => '0px|0px|0px|0px',
			'element_date_padding'                 => '0px|0px|0px|0px',
			'element_date_icon'                    => '&#xe023;||divi||400',
			'element_date_icon_color'              => et_builder_accent_color(),
			'element_date_icon_size'               => '16px',
			'element_date_icon_placement'          => 'left',
			'element_date_type'                    => 'modified',
			'element_read_more_text'               => _x( 'Read More', 'Modules dummy content', 'squad-modules-for-divi' ),
			'element_read_more_font_size'          => '14px',
			'element_read_more_line_height'        => '1.7em',
			'element_read_more_text_color'         => '#666666',
			'element_read_more_background_color'   => '#ffffff',
			'element_read_more_margin'             => '0px|0px|0px|0px',
			'element_read_more_padding'            => '0px|0px|0px|0px',
			'element_read_more_icon'               => '&#x35;||divi||400',
			'element_read_more_icon_color'         => et_builder_accent_color(),
			'element_read_more_icon_size'          => '16px',
			'element_read_more_icon_placement'     => 'right',
			'element_comments_before'              => _x( 'Comments', 'Modules dummy content', 'squad-modules-for-divi' ),
			'element_comments_font_size'           => '14px',
			'element_comments_line_height'         => '1.7em',
			'element_comments_text_color'          => '#666666',
			'element_comments_background_color'    => '#ffffff',
			'element_comments_margin'              => '0px|0px|0px|0px',
			'element_comments_padding'             => '0px|0px|0px|0px',
			'element_comments_icon'                => '&#xe065;||divi||400',
			'element_comments_icon_color'          => et_builder_accent_color(),
			'element_comments_icon_size'           => '16px',
			'element_comments_icon_placement'      => 'left',
			'element_categories_sepa'              => ',',
			'element_categories_font_size'         => '14px',
			'element_categories_line_height'       => '1.7em',
			'element_categories_text_color'        => '#666666',
			'element_categories_background_color'  => '#ffffff',
			'element_categories_margin'            => '0px|0px|0px|0px',
			'element_categories_padding'           => '0px|0px|0px|0px',
			'element_categories_icon'              => '&#xe07d;||divi||400',
			'element_categories_icon_color'        => et_builder_accent_color(),
			'element_categories_icon_size'         => '16px',
			'element_categories_icon_placement'    => 'left',
			'element_tags_sepa'                    => ',',
			'element_tags_font_size'               => '14px',
			'element_tags_line_height'             => '1.7em',
			'element_tags_text_color'              => '#666666',
			'element_tags_background_color'        => '#ffffff',
			'element_tags_margin'                  => '0px|0px|0px|0px',
			'element_tags_padding'                 => '0px|0px|0px|0px',
			'element_tags_icon'                    => '&#xe07c;||divi||400',
			'element_tags_icon_color'              => et_builder_accent_color(),
			'element_tags_icon_size'               => '16px',
			'element_tags_icon_placement'          => 'left',
			'element_custom_text'                  => _x( 'Custom Text', 'Modules dummy content', 'squad-modules-for-divi' ),
			'element_custom_text_font_size'        => '14px',
			'element_custom_text_line_height'      => '1.7em',
			'element_custom_text_text_color'       => '#666666',
			'element_custom_text_background_color' => '#ffffff',
			'element_custom_text_margin'           => '0px|0px|0px|0px',
			'element_custom_text_padding'          => '0px|0px|0px|0px',
			'element_custom_text_icon'             => '&#xe065;||divi||400',
			'element_custom_text_icon_color'       => et_builder_accent_color(),
			'element_custom_text_icon_size'        => '16px',
			'element_custom_text_icon_placement'   => 'left',
			'element_outside__enable'              => 'off',
			'element_icon_text_gap'                => '10px',
			'element_icon_placement'               => 'row',
			'element_icon_horizontal_alignment'    => 'left',
			'element_icon_vertical_alignment'      => 'center',
		);
	}

	/**
	 * Get business hours configuration.
	 *
	 * @since 3.3.0
	 *
	 * @param array<string, mixed> $defaults The default values.
	 *
	 * @return array<string, string> Business hours configuration.
	 */
	private function get_business_hours_config( array $defaults ): array {
		$business_day_shortcodes       = $this->generate_business_day_shortcodes();
		$business_day_child_shortcodes = implode( '', $business_day_shortcodes );

		$config = array(
			'content'                      => et_fb_process_shortcode( $business_day_child_shortcodes ),
			'title'                        => _x( 'Business Hours', 'Modules dummy content', 'squad-modules-for-divi' ),
			'title_tag'                    => 'h2',
			'title__enable'                => 'on',
			'day_elements_gap'             => '10px',
			'wrapper_gap'                  => '30px',
			'show_divider'                 => 'on',
			'divider_color'                => '#e5e5e5',
			'divider_weight'               => '1px',
			'divider_style'                => 'solid',
			'divider_position'             => 'top',
			'day_wrapper_background_color' => '#ffffff',
			'day_wrapper_padding'          => '10px|15px|10px|15px',
			'day_wrapper_margin'           => '0px|0px|0px|0px',
			'day_wrapper_text_orientation' => 'left',
			'title_background_color'       => '#ffffff',
			'title_padding'                => '4px|8px|4px|8px',
			'title_margin'                 => '0px|0px|0px|0px',
		);

		/**
		 * Filter business hours configuration.
		 *
		 * @since 3.3.0
		 *
		 * @param array $config   Business hours configuration.
		 * @param array $defaults Default values array.
		 */
		return apply_filters( 'divi_squad_business_hours_config', $config, $defaults );
	}

	/**
	 * Generate business day shortcodes.
	 *
	 * @since 3.3.0
	 *
	 * @return array<int, string> Array of business day shortcodes.
	 */
	private function generate_business_day_shortcodes(): array {
		$days_of_week = array(
			_x( 'Sun Day', 'Modules dummy content', 'squad-modules-for-divi' ) => _x( '10AM - 5PM', 'Modules dummy content', 'squad-modules-for-divi' ),
			_x( 'Mon Day', 'Modules dummy content', 'squad-modules-for-divi' ) => _x( '10AM - 5PM', 'Modules dummy content', 'squad-modules-for-divi' ),
			_x( 'Tue Day', 'Modules dummy content', 'squad-modules-for-divi' ) => _x( '10AM - 5PM', 'Modules dummy content', 'squad-modules-for-divi' ),
			_x( 'Wed Day', 'Modules dummy content', 'squad-modules-for-divi' ) => _x( '10AM - 5PM', 'Modules dummy content', 'squad-modules-for-divi' ),
			_x( 'Thu Day', 'Modules dummy content', 'squad-modules-for-divi' ) => _x( '10AM - 5PM', 'Modules dummy content', 'squad-modules-for-divi' ),
			_x( 'Fri Day', 'Modules dummy content', 'squad-modules-for-divi' ) => _x( 'Closed', 'Modules dummy content', 'squad-modules-for-divi' ),
			_x( 'Sat Day', 'Modules dummy content', 'squad-modules-for-divi' ) => _x( 'Closed', 'Modules dummy content', 'squad-modules-for-divi' ),
		);

		/**
		 * Filter business days of week.
		 *
		 * @since 3.3.0
		 *
		 * @param array $days_of_week Array of business days and times.
		 */
		$days_of_week = apply_filters( 'divi_squad_business_days_of_week', $days_of_week );

		$shortcodes = array();
		foreach ( $days_of_week as $day => $time ) {
			$shortcodes[] = $this->generate_module_shortcode(
				'disq_business_day',
				array(
					'day'          => $day,
					'time'         => $time,
					'show_divider' => 'on',
				)
			);
		}

		return $shortcodes;
	}

	/**
	 * Get business day configuration.
	 *
	 * @since 3.3.0
	 *
	 * @param array<string, mixed> $defaults The default values.
	 *
	 * @return array<string, string> Business day configuration.
	 */
	private function get_business_day_config( array $defaults ): array {
		$config = array(
			// Day Settings.
			'day'                      => _x( 'Sun Day', 'Modules dummy content', 'squad-modules-for-divi' ),
			'time'                     => _x( '10AM - 5PM', 'Modules dummy content', 'squad-modules-for-divi' ),
			'show_divider'             => 'on',

			// Time Settings.
			'dual_time__enable'        => 'off',
			'start_time'               => '',
			'end_time'                 => '',
			'time_separator'           => '',
			'off_day__enable'          => 'off',
			'off_day_label'            => '',

			// Wrapper Settings.
			'wrapper_background_color' => '#ffffff',
			'wrapper_text_orientation' => 'left',
			'wrapper_margin'           => '0px|0px|0px|0px',
			'wrapper_padding'          => '10px|15px|10px|15px',

			// Divider Settings.
			'divider_color'            => '#e5e5e5',
			'divider_weight'           => '1px',
			'divider_style'            => 'solid',
			'divider_position'         => 'top',

			// Font Settings.
			'day_text_font_size'       => '16px',
			'day_text_font_weight'     => '400',
			'time_text_font_size'      => '16px',
			'time_text_font_weight'    => '400',
		);

		/**
		 * Filter business day configuration.
		 *
		 * @since 3.3.0
		 *
		 * @param array $config   Business day configuration.
		 * @param array $defaults Default values array.
		 */
		return apply_filters( 'divi_squad_business_day_config', $config, $defaults );
	}

	/**
	 * Get before after slider configuration.
	 *
	 * @since 3.3.0
	 *
	 * @param array<string, mixed> $defaults The default values.
	 *
	 * @return array<string, string> Before after slider configuration.
	 */
	private function get_before_after_slider_config( array $defaults ): array {
		$config = array(
			// Image Settings.
			'before_image'                      => '',
			'before_alt'                        => _x( 'Before Image', 'Modules dummy content', 'squad-modules-for-divi' ),
			'before_label'                      => _x( 'Before', 'Modules dummy content', 'squad-modules-for-divi' ),
			'after_image'                       => '',
			'after_alt'                         => _x( 'After Image', 'Modules dummy content', 'squad-modules-for-divi' ),
			'after_label'                       => _x( 'After', 'Modules dummy content', 'squad-modules-for-divi' ),

			// Compare Options.
			'image_label__enable'               => 'off',
			'image_label_hover__enable'         => 'off',
			'slide_direction_mode'              => 'horizontal',
			'slide_trigger_type'                => 'click',
			'slide_control_color'               => '#000',
			'slide_control_start_point'         => '25',
			'slide_control_shadow__enable'      => 'off',
			'slide_control_circle__enable'      => 'off',
			'slide_control_circle_blur__enable' => 'off',
			'slide_control_smoothing__enable'   => 'off',
			'slide_control_smoothing_amount'    => '100',

			// Label Settings.
			'before_label_background_color'     => '#000',
			'before_label_margin'               => '0px|0px|0px|0px',
			'before_label_padding'              => '4px|8px|4px|8px',
			'after_label_background_color'      => '#000',
			'after_label_margin'                => '0px|0px|0px|0px',
			'after_label_padding'               => '4px|8px|4px|8px',

			// Font Settings.
			'before_label_text_font_size'       => '16px',
			'after_label_text_font_size'        => '16px',
		);

		/**
		 * Filter before after slider configuration.
		 *
		 * @since 3.3.0
		 *
		 * @param array $config   Before after slider configuration.
		 * @param array $defaults Default values array.
		 */
		return apply_filters( 'divi_squad_before_after_slider_config', $config, $defaults );
	}

	/**
	 * Get post reading time configuration.
	 *
	 * @since 3.3.0
	 *
	 * @param array<string, mixed> $defaults The default values.
	 *
	 * @return array<string, string> Post reading time configuration.
	 */
	private function get_post_reading_time_config( array $defaults ): array {
		$config = array(
			'time_prefix_text'           => _x( 'Reading Time', 'Modules dummy content', 'squad-modules-for-divi' ),
			'time_suffix_text'           => _x( 'minutes', 'Modules dummy content', 'squad-modules-for-divi' ),
			'time_suffix_text_singular'  => _x( 'minute', 'Modules dummy content', 'squad-modules-for-divi' ),
			'time_text_tag'              => 'div',
			'words_per_minute'           => 250,
			'calculate_comments__enable' => 'off',
			'calculate_images__enable'   => 'off',
			'calculate_images_count'     => 4,
			'time_background_color'      => '#ffffff',
			'time_margin'                => '0px|0px|0px|0px',
			'time_padding'               => '10px|25px|10px|25px',
			'show_divider'               => 'off',
			'divider_position'           => 'bottom',
			'divider_color'              => '#e5e5e5',
			'divider_weight'             => '1px',
			'divider_style'              => 'solid',
		);

		/**
		 * Filter post reading time configuration.
		 *
		 * @since 3.3.0
		 *
		 * @param array $config   Post reading time configuration.
		 * @param array $defaults Default values array.
		 */
		return apply_filters( 'divi_squad_post_reading_time_config', $config, $defaults );
	}

	/**
	 * Get glitch text configuration.
	 *
	 * @since 3.3.0
	 *
	 * @param array<string, mixed> $defaults The default values.
	 *
	 * @return array<string, string> Glitch text configuration.
	 */
	private function get_glitch_text_config( array $defaults ): array {
		$config = array(
			'glitch_text'                  => _x( 'Your Glitch Text Here', 'Modules dummy content', 'squad-modules-for-divi' ),
			'glitch_text_tag'              => 'p',
			'glitch_text_effect'           => 'one',
			'glitch_color_primary_one'     => '#FF0000FF',
			'glitch_color_secondary_one'   => '#0000FFFF',
			'glitch_color_primary_two'     => '#f0f',
			'glitch_color_secondary_two'   => '#0ff',
			'glitch_color_primary_three'   => '#0000FFFF',
			'glitch_color_secondary_three' => '#FF0000FF',
			'glitch_color_primary_four'    => '#0000FFFF',
			'glitch_color_secondary_four'  => '#FF0000FF',
			'glitch_color_primary_five'    => '#FF0000FF',
			'glitch_color_secondary_five'  => '#0000FFFF',
			'glitch_text_font_size'        => '20px',
			'glitch_text_font_weight'      => '400',
			'glitch_text_line_height'      => '1.7em',
			'glitch_text_text_color'       => '#666666',
			'wrapper_background_color'     => '#ffffff',
			'wrapper_margin'               => '0px|0px|0px|0px',
			'wrapper_padding'              => '10px|15px|10px|15px',
		);

		/**
		 * Filter glitch text configuration.
		 *
		 * @since 3.3.0
		 *
		 * @param array $config   Glitch text configuration.
		 * @param array $defaults Default values array.
		 */
		return apply_filters( 'divi_squad_glitch_text_config', $config, $defaults );
	}

	/**
	 * Get gradient text configuration.
	 *
	 * @since 3.3.0
	 *
	 * @param array<string, mixed> $defaults The default values.
	 *
	 * @return array<string, string> Gradient text configuration.
	 */
	private function get_gradient_text_config( array $defaults ): array {
		$gradient_text_default_text = array(
			array(
				'value'   => _x( 'Your Gradient Text Here', 'Modules dummy content', 'squad-modules-for-divi' ),
				'checked' => 0,
				'dragID'  => - 1,
			),
		);

		$config = array(
			'gradient_text'                               => wp_json_encode( $gradient_text_default_text ),
			'gradient_text_tag'                           => 'p',
			'text_gradient_use_color_gradient'            => 'on',
			'text_gradient_color_gradient_stops'          => '#1f7016 0%|#29c4a9 100%',
			'text_gradient_color_gradient_type'           => 'linear',
			'text_gradient_color_gradient_direction'      => '180deg',
			'text_gradient_color_gradient_direction_radial' => 'center',
			'text_gradient_color_gradient_unit'           => '%',
			'text_gradient_color_gradient_overlays_image' => 'off',
			'text_gradient_color_gradient_repeat'         => 'off',
			'text_gradient_color_gradient_start_position' => '0%',
			'text_gradient_color_gradient_end_position'   => '100%',
			'gradient_text_font_size'                     => '40px',
			'gradient_text_font_weight'                   => '400',
			'gradient_text_line_height'                   => '1.2em',
			'gradient_text_text_color'                    => '#666666',
			'wrapper_background_color'                    => '#ffffff',
			'wrapper_margin'                              => '0px|0px|0px|0px',
			'wrapper_padding'                             => '10px|15px|10px|15px',
		);

		/**
		 * Filter gradient text configuration.
		 *
		 * @since 3.3.0
		 *
		 * @param array $config   Gradient text configuration.
		 * @param array $defaults Default values array.
		 */
		return apply_filters( 'divi_squad_gradient_text_config', $config, $defaults );
	}

	/**
	 * Get scrolling text configuration.
	 *
	 * @since 3.3.0
	 *
	 * @param array<string, mixed> $defaults The default values.
	 *
	 * @return array<string, string> Scrolling text configuration.
	 */
	private function get_scrolling_text_config( array $defaults ): array {
		$config = array(
			'scrolling_text'                => _x( 'Your Scrolling Text Here', 'Modules dummy content', 'squad-modules-for-divi' ),
			'text_element_tag'              => 'h2',
			'scrolling_direction'           => 'left',
			'scrolling_speed'               => '7500',
			'repeat_text__enable'           => 'off',
			'outline_text__enable'          => 'off',
			'pause_on_hover__enable'        => 'off',
			'scrolling_text_font_size'      => '20px',
			'scrolling_text_line_height'    => '1.2em',
			'scrolling_text_letter_spacing' => '0px',
			'scrolling_text_text_color'     => '#666666',
			'wrapper_background_color'      => '#ffffff',
			'wrapper_margin'                => '0px|0px|0px|0px',
			'wrapper_padding'               => '10px|15px|10px|15px',
		);

		/**
		 * Filter scrolling text configuration.
		 *
		 * @since 3.3.0
		 *
		 * @param array $config   Scrolling text configuration.
		 * @param array $defaults Default values array.
		 */
		return apply_filters( 'divi_squad_scrolling_text_config', $config, $defaults );
	}

	/**
	 * Get breadcrumbs configuration.
	 *
	 * @since 3.3.0
	 *
	 * @param array<string, mixed> $defaults The default values.
	 *
	 * @return array<string, string> Breadcrumbs configuration.
	 */
	private function get_breadcrumbs_config( array $defaults ): array {
		$config = array(
			'home_text'                   => _x( 'Home', 'Modules dummy content', 'squad-modules-for-divi' ),
			'before_text'                 => _x( 'You are here:', 'Modules dummy content', 'squad-modules-for-divi' ),
			'font_icon'                   => '9||divi||400',
			'before_icon'                 => '',
			'link_color'                  => '',
			'separator_color'             => '',
			'current_text_color'          => '',
			'content_text_font_size'      => '14px',
			'content_text_line_height'    => '1.7em',
			'content_text_letter_spacing' => '0px',
		);

		/**
		 * Filter breadcrumbs configuration.
		 *
		 * @since 3.3.0
		 *
		 * @param array $config   Breadcrumbs configuration.
		 * @param array $defaults Default values array.
		 */
		return apply_filters( 'divi_squad_breadcrumbs_config', $config, $defaults );
	}

	/**
	 * Get drop cap text configuration.
	 *
	 * @since 3.3.0
	 *
	 * @param array<string, mixed> $defaults The default values.
	 *
	 * @return array<string, string> Drop cap text configuration.
	 */
	private function get_drop_cap_text_config( array $defaults ): array {
		$config = array(
			'drop_cap_letter'                  => _x( 'Y', 'Modules dummy content', 'squad-modules-for-divi' ),
			'body_content'                     => str_replace( '<p>Y', '<p> ', $defaults['body'] ),
			'drop_cap_letter_font_size'        => '35px',
			'drop_cap_letter_line_height'      => '1',
			'drop_cap_letter_font_weight'      => '400',
			'drop_cap_letter_letter_spacing'   => '0px',
			'drop_cap_letter_text_color'       => '#333333',
			'content_font_size'                => '16px',
			'content_line_height'              => '1.7',
			'content_font_weight'              => '400',
			'content_letter_spacing'           => '0px',
			'content_text_color'               => '#333333',
			'drop_cap_letter_background_color' => '#ffffff',
			'drop_cap_letter_margin'           => '0px|0px|0px|0px',
			'drop_cap_letter_padding'          => '0px|0px|0px|0px',
			'wrapper_background_color'         => '#ffffff',
			'wrapper_margin'                   => '0px|0px|0px|0px',
			'wrapper_padding'                  => '10px|15px|10px|15px',
		);

		/**
		 * Filter drop cap text configuration.
		 *
		 * @since 3.3.0
		 *
		 * @param array $config   Drop cap text configuration.
		 * @param array $defaults Default values array.
		 */
		return apply_filters( 'divi_squad_drop_cap_text_config', $config, $defaults );
	}

	/**
	 * Get star rating configuration.
	 *
	 * @since 3.3.0
	 *
	 * @param array<string, mixed> $defaults The default values.
	 *
	 * @return array<string, string> Star rating configuration.
	 */
	private function get_star_rating_config( array $defaults ): array {
		$config = array(
			'rating_scale'                 => '5',
			'rating_upto_5'                => '5',
			'rating_upto_10'               => '10',
			'title'                        => _x( 'Your Rating Title', 'Modules dummy content', 'squad-modules-for-divi' ),
			'text_element_tag'             => 'h2',
			'stars_display_type'           => 'inline-block',
			'title_inline_position'        => 'left',
			'title_stacked_position'       => 'bottom',
			'title_gap'                    => '7px',
			'show_number'                  => 'off',
			'star_alignment'               => 'left',
			'stars_size'                   => '14px',
			'stars_gap'                    => '0px',
			'stars_color'                  => '#f0ad4e',
			'stars_schema_markup'          => 'off',
			'stars_schema_author'          => '',
			'stars_schema_product_name'    => '',
			'stars_schema_num_of_rating'   => '',
			'header_font_size'             => '18px',
			'header_line_height'           => '1em',
			'header_letter_spacing'        => '0px',
			'rating_number_font_size'      => '14px',
			'rating_number_line_height'    => '1em',
			'rating_number_letter_spacing' => '0px',
			'wrapper_background_color'     => '#ffffff',
			'wrapper_margin'               => '0px|0px|0px|0px',
			'wrapper_padding'              => '10px|15px|10px|15px',
		);

		/**
		 * Filter star rating configuration.
		 *
		 * @since 3.3.0
		 *
		 * @param array $config   Star rating configuration.
		 * @param array $defaults Default values array.
		 */
		return apply_filters( 'divi_squad_star_rating_config', $config, $defaults );
	}

	/**
	 * Get flip box configuration.
	 *
	 * @since 3.3.0
	 *
	 * @param array<string, mixed> $defaults The default values.
	 *
	 * @return array<string, string> Flip box configuration.
	 */
	private function get_flip_box_config( array $defaults ): array {
		$config = array(
			'front_title'                     => _x( 'Your Front Title Here', 'Modules dummy content', 'squad-modules-for-divi' ),
			'front_title_tag'                 => 'h2',
			'front_sub_title'                 => _x( 'Your Front Sub Title Here', 'Modules dummy content', 'squad-modules-for-divi' ),
			'front_sub_title_tag'             => 'h5',
			'front_content'                   => _x( 'Your front content goes here. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.', 'Modules dummy content', 'squad-modules-for-divi' ),
			'front_button__enable'            => 'off',
			'front_button_text'               => _x( 'Read More', 'Modules dummy content', 'squad-modules-for-divi' ),
			'front_button_url'                => '',
			'front_button_url_new_window'     => 'off',
			'front_icon_type'                 => 'icon',
			'front_icon'                      => '&#x4e;||divi||400',
			'front_icon_text'                 => '',
			'front_icon_color'                => et_builder_accent_color(),
			'front_icon_size'                 => '32px',
			'front_image'                     => '',
			'front_image_force_full_width'    => 'off',
			'front_image_width'               => '100%',
			'front_image_height'              => 'auto',
			'front_icon_default_alignment'    => 'left',
			'front_content_outside_container' => 'off',
			'front_icon_text_gap'             => '10px',
			'front_icon_wrapper_width'        => '100%',
			'front_icon_placement'            => 'column',
			'front_icon_horizontal_alignment' => 'left',
			'front_icon_vertical_alignment'   => 'flex-start',
			'front_text_orientation'          => 'left',
			'front_icon_item_inner_gap'       => '10px',
			'front_wrapper_padding'           => '10px|15px|10px|15px',
			'front_wrapper_background_color'  => '#ffffff',
			'front_icon_order'                => '1',
			'front_title_order'               => '2',
			'front_sub_title_order'           => '3',
			'front_body_order'                => '4',
			'front_button_order'              => '5',
			'back_title'                      => _x( 'Your Back Title Here', 'Modules dummy content', 'squad-modules-for-divi' ),
			'back_title_tag'                  => 'h2',
			'back_sub_title'                  => _x( 'Your Back Sub Title Here', 'Modules dummy content', 'squad-modules-for-divi' ),
			'back_sub_title_tag'              => 'h5',
			'back_content'                    => _x( 'Your back content goes here. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.', 'Modules dummy content', 'squad-modules-for-divi' ),
			'back_button__enable'             => 'off',
			'back_button_text'                => _x( 'Read More', 'Modules dummy content', 'squad-modules-for-divi' ),
			'back_button_url'                 => '',
			'back_button_url_new_window'      => 'off',
			'back_icon_type'                  => 'icon',
			'back_icon'                       => '&#x4e;||divi||400',
			'back_icon_text'                  => '',
			'back_icon_color'                 => et_builder_accent_color(),
			'back_icon_size'                  => '32px',
			'back_image'                      => '',
			'back_image_force_full_width'     => 'off',
			'back_image_width'                => '100%',
			'back_image_height'               => 'auto',
			'back_icon_default_alignment'     => 'left',
			'back_content_outside_container'  => 'off',
			'back_icon_text_gap'              => '10px',
			'back_icon_wrapper_width'         => '100%',
			'back_icon_placement'             => 'column',
			'back_icon_horizontal_alignment'  => 'left',
			'back_icon_vertical_alignment'    => 'flex-start',
			'back_text_orientation'           => 'left',
			'back_icon_item_inner_gap'        => '10px',
			'back_wrapper_padding'            => '10px|15px|10px|15px',
			'back_wrapper_background_color'   => '#ffffff',
			'back_icon_order'                 => '1',
			'back_title_order'                => '2',
			'back_sub_title_order'            => '3',
			'back_body_order'                 => '4',
			'back_button_order'               => '5',
			'flip_custom_height__enable'      => 'off',
			'flip_elements_hr_alignment'      => 'center',
			'flip_elements_vr_alignment'      => 'center',
			'flip_swap_slide__enable'         => 'off',
			'flip_animation_type'             => 'rotate',
			'flip_animation_d_lrbt'           => 'right',
			'flip_animation_d_lr'             => 'left',
			'flip_animation_d_bt'             => 'bottom',
			'flip_animation_d_clrbt'          => 'center',
			'flip_3d_effect__enable'          => 'off',
			'flip_move_both_slide__enable'    => 'off',
			'flip_translate_z'                => '50px',
			'flip_scale'                      => '.9',
			'flip_transition_delay'           => '0ms',
			'flip_transition_duration'        => '1000ms',
			'flip_transition_speed_curve'     => 'ease',
		);

		/**
		 * Filter flip box configuration.
		 *
		 * @since 3.3.0
		 *
		 * @param array $config   Flip box configuration.
		 * @param array $defaults Default values array.
		 */
		return apply_filters( 'divi_squad_flip_box_config', $config, $defaults );
	}

	/**
	 * Filters backend data passed to the Visual Builder.
	 * This function is used to add static helpers whose content rarely changes.
	 * eg: google fonts, module defaults, and so on.
	 *
	 * @param array<string, array<string, mixed>> $exists The existed definitions.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function static_asset_definitions( $exists = array() ): array {
		// Defaults data for modules.
		$defaults = $this->get_modules_defaults();

		$module_definitions = array(
			'defaults' => array(
				'disq_divider'           => $this->get_divider_config( $defaults ),
				'disq_image_gallery'     => $this->get_image_gallery_config( $defaults ),
				'disq_image_mask'        => $this->get_image_mask_config( $defaults ),
				'disq_lottie'            => $this->get_lottie_config( $defaults ),
				'disq_video_popup'       => $this->get_video_popup_config( $defaults ),
				'disq_dual_button'       => $this->get_dual_button_config( $defaults ),
				'disq_typing_text'       => $this->get_typing_text_config( $defaults ),
				'disq_post_grid'         => $this->get_post_grid_config( $defaults ),
				'disq_post_grid_child'   => $this->get_post_grid_child_defaults( $defaults ),
				'disq_business_hours'    => $this->get_business_hours_config( $defaults ),
				'disq_business_day'      => $this->get_business_day_config( $defaults ),
				'disq_bai_slider'        => $this->get_before_after_slider_config( $defaults ),
				'disq_post_reading_time' => $this->get_post_reading_time_config( $defaults ),
				'disq_glitch_text'       => $this->get_glitch_text_config( $defaults ),
				'disq_gradient_text'     => $this->get_gradient_text_config( $defaults ),
				'disq_scrolling_text'    => $this->get_scrolling_text_config( $defaults ),
				'disq_breadcrumbs'       => $this->get_breadcrumbs_config( $defaults ),
				'disq_drop_cap_text'     => $this->get_drop_cap_text_config( $defaults ),
				'disq_star_rating'       => $this->get_star_rating_config( $defaults ),
				'disq_flip_box'          => $this->get_flip_box_config( $defaults ),
				'disq_google_map'        => array(
					'google_api_key' => '',
				),
			),
		);

		$merged_definitions = array_merge_recursive( $exists, $module_definitions );

		/**
		 * Filter the final module definitions before they are returned.
		 *
		 * @since 3.3.0
		 *
		 * @param array $merged_definitions The merged module definitions.
		 * @param array $exists The existing definitions.
		 * @param array $defaults The default values.
		 */
		return apply_filters( 'divi_squad_static_asset_definitions', $merged_definitions, $exists, $defaults );
	}
}
