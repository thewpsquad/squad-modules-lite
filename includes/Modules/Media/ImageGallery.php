<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Image Gallery Module Class which extend the Divi Builder Module Class.
 *
 * This class provides a gallery adding functionalities for image in the visual builder.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   1.2.0
 */

namespace DiviSquad\Modules\Media;

use DiviSquad\Base\DiviBuilder\Module;
use DiviSquad\Base\DiviBuilder\Utils;
use DiviSquad\Utils\Helper;
use ET_Builder_Module_Helper_Overlay as OverlayHelper;
use WP_Post;
use function _wp_get_image_size_from_meta;
use function apply_filters;
use function esc_attr;
use function esc_html__;
use function et_builder_i18n;
use function et_builder_is_loading_data;
use function et_pb_background_layout_options;
use function et_pb_media_options;
use function get_permalink;
use function get_post_meta;
use function get_posts;
use function wp_array_slice_assoc;
use function wp_enqueue_script;
use function wp_get_attachment_image_src;
use function wp_get_attachment_metadata;
use function wp_json_encode;
use function wp_kses_post;
use function wp_parse_args;

/**
 * Image Gallery Module Class.
 *
 * @package DiviSquad
 * @since   1.2.0
 */
class ImageGallery extends Module {
	/**
	 * Initiate Module.
	 * Set the module name on init.
	 *
	 * @return void
	 * @since 1.2.0
	 */
	public function init(): void {
		$this->name      = esc_html__( 'Image Gallery', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Image Galleries', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'image-gallery.svg' );

		$this->slug             = 'disq_image_gallery';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.$this->slug";

		$this->child_title_var          = 'title';
		$this->child_title_fallback_var = 'admin_label';

		// Declare settings modal toggles for the module.
		$this->settings_modal_toggles = array(
			'general'    => array(
				'toggles' => array(
					'main_content'     => esc_html__( 'Images', 'squad-modules-for-divi' ),
					'gallery_settings' => esc_html__( 'Gallery Options', 'squad-modules-for-divi' ),
				),
			),
			'advanced'   => array(
				'toggles' => array(
					'overlay' => et_builder_i18n( 'Overlay' ),
					'image'   => esc_html__( 'Image', 'squad-modules-for-divi' ),
					'text'    => array(
						'title'    => et_builder_i18n( 'Text' ),
						'priority' => 49,
					),
				),
			),
			'custom_css' => array(
				'toggles' => array(
					'animation' => array(
						'title'    => esc_html__( 'Animation', 'squad-modules-for-divi' ),
						'priority' => 90,
					),
				),
			),
		);

		// Declare advanced fields for the module.
		$this->advanced_fields = array(
			'background'     => Utils::selectors_background( $this->main_css_element ),
			'borders'        => array(
				'default' => Utils::selectors_default( $this->main_css_element ),
				'image'   => array(
					'label_prefix' => et_builder_i18n( 'Image' ),
					'css'          => array(
						'main' => array(
							'border_radii'        => "$this->main_css_element div .gallery-images img",
							'border_radii_hover'  => "$this->main_css_element div .gallery-images img:hover",
							'border_styles'       => "$this->main_css_element div .gallery-images img",
							'border_styles_hover' => "$this->main_css_element div .gallery-images img:hover",
						),
					),
					'tab_slug'     => 'advanced',
					'toggle_slug'  => 'image',
				),
			),
			'box_shadow'     => array(
				'default' => Utils::selectors_default( $this->main_css_element ),
				'image'   => array(
					'label'             => esc_html__( 'Image Box Shadow', 'squad-modules-for-divi' ),
					'option_category'   => 'layout',
					'tab_slug'          => 'advanced',
					'toggle_slug'       => 'image',
					'css'               => array(
						'main'  => "$this->main_css_element div .gallery-images img",
						'hover' => "$this->main_css_element div .gallery-images img:hover",
					),
					'default_on_fronts' => array(
						'color'    => '',
						'position' => '',
					),
				),
			),
			'margin_padding' => Utils::selectors_margin_padding( $this->main_css_element ),
			'max_width'      => Utils::selectors_max_width( $this->main_css_element ),
			'height'         => Utils::selectors_default( $this->main_css_element ),
			'scroll_effects' => array(
				'grid_support' => 'yes',
			),
			'filters'        => array(
				'default'              => Utils::selectors_default( $this->main_css_element ),
				'child_filters_target' => Utils::add_filters_field(
					array(
						'toggle_slug' => 'image',
						'css'         => array(
							'main'  => "$this->main_css_element div .gallery-images img",
							'hover' => "$this->main_css_element div .gallery-images img:hover",
						),
					)
				),
			),
			'link_options'   => false,
			'fonts'          => false,
			'text'           => false,
			'button'         => false,
		);

		// Declare custom css fields for the module.
		$this->custom_css_fields = array(
			'image' => array(
				'label'    => esc_html__( 'Images', 'squad-modules-for-divi' ),
				'selector' => 'div .gallery-images img',
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
		// Image fields definitions.
		$image_fields = array(
			'gallery_ids' => array(
				'label'            => esc_html__( 'Images', 'squad-modules-for-divi' ),
				'description'      => esc_html__( 'Choose the images that you would like to appear in the image gallery.', 'squad-modules-for-divi' ),
				'type'             => 'upload-gallery',
				'option_category'  => 'basic_option',
				'computed_affects' => array(
					'__gallery',
				),
				'tab_slug'         => 'general',
				'toggle_slug'      => 'main_content',
			),
			'__gallery'   => array(
				'type'                => 'computed',
				'computed_callback'   => array( self::class, 'get_gallery' ),
				'computed_depends_on' => array(
					'gallery_ids',
					'orientation',
					'gallery_order_by',
				),
			),
		);

		// Gallery settings fields definitions.
		$gallery_settings_fields = array(
			'orientation'         => Utils::add_select_box_field(
				esc_html__( 'Orientation', 'squad-modules-for-divi' ),
				array(
					'description'      => sprintf(
						'%1$s<br><small><em><strong>%2$s:</strong> %3$s <a href="//wordpress.org/plugins/force-regenerate-thumbnails" target="_blank">%4$s</a>.</em></small>',
						esc_html__( 'Choose the orientation of the gallery thumbnails.', 'squad-modules-for-divi' ),
						esc_html__( 'Note', 'squad-modules-for-divi' ),
						esc_html__( 'If this option appears to have no effect, you might need to', 'squad-modules-for-divi' ),
						esc_html__( 'regenerate your thumbnails', 'squad-modules-for-divi' )
					),
					'options_category' => 'configuration',
					'options'          => array(
						'landscape' => esc_html__( 'Landscape', 'squad-modules-for-divi' ),
						'portrait'  => esc_html__( 'Portrait', 'squad-modules-for-divi' ),
					),
					'default_on_front' => 'landscape',
					'computed_affects' => array(
						'__gallery',
					),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'gallery_settings',
				)
			),
			'gallery_order_by'    => Utils::add_select_box_field(
				esc_html__( 'Image Order', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Select an ordering method for the gallery. This controls which gallery items appear first in the list.', 'squad-modules-for-divi' ),
					'type'             => et_builder_is_loading_data( 'bb' ) ? 'hidden' : 'select',
					'class'            => array( 'et-pb-gallery-ids-field' ),
					'options'          => array(
						'default' => et_builder_i18n( 'Default' ),
						'rand'    => esc_html__( 'Random', 'squad-modules-for-divi' ),
					),
					'default'          => 'default',
					'computed_affects' => array(
						'__gallery',
					),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'gallery_settings',
				)
			),
			'images_quantity'     => Utils::add_select_box_field(
				esc_html__( 'Image Quantity', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Select how much images are shown in the gallery, by default show all.', 'squad-modules-for-divi' ),
					'type'             => et_builder_is_loading_data( 'bb' ) ? 'hidden' : 'select',
					'options'          => array(
						'default' => et_builder_i18n( 'Default' ),
						'custom'  => esc_html__( 'Custom', 'squad-modules-for-divi' ),
					),
					'default'          => 'default',
					'affects'          => array(
						'gallery_image_count',
					),
					'computed_affects' => array(
						'__gallery',
					),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'gallery_settings',
				)
			),
			'gallery_image_count' => Utils::add_range_field(
				esc_html__( 'Image Count', 'squad-modules-for-divi' ),
				array(
					'description'       => esc_html__( 'Define the number of images that should be displayed per page.', 'squad-modules-for-divi' ),
					'type'              => et_builder_is_loading_data( 'bb' ) ? 'hidden' : 'range',
					'range_settings'    => array(
						'min'       => '1',
						'step'      => '1',
						'min_limit' => '1',
					),
					'default'           => 4,
					'number_validation' => true,
					'fixed_range'       => true,
					'unitless'          => true,
					'hover'             => false,
					'mobile_options'    => false,
					'responsive'        => false,
					'depends_show_if'   => 'custom',
					'computed_affects'  => array(
						'__gallery',
					),
					'tab_slug'          => 'general',
					'toggle_slug'       => 'gallery_settings',
				)
			),
			'columns_count'       => Utils::add_range_field(
				esc_html__( 'Image Columns Count', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Define the number of columns that should be displayed per page.', 'squad-modules-for-divi' ),
					'type'           => et_builder_is_loading_data( 'bb' ) ? 'hidden' : 'range',
					'range_settings' => array(
						'min'       => '3',
						'step'      => '1',
						'min_limit' => '3',
					),
					'default'        => 4,
					'unitless'       => true,
					'hover'          => false,
					'mobile_options' => false,
					'responsive'     => false,
					'tab_slug'       => 'general',
					'toggle_slug'    => 'gallery_settings',
				)
			),
			'images_inner_gap'    => Utils::add_range_field(
				esc_html__( 'Images Gap', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can choose gap between images.', 'squad-modules-for-divi' ),
					'type'             => et_builder_is_loading_data( 'bb' ) ? 'hidden' : 'range',
					'range_settings'   => array(
						'min'  => '1',
						'max'  => '100',
						'step' => '1',
					),
					'default_on_front' => '10px',
					'default_unit'     => 'px',
					'hover'            => false,
					'tab_slug'         => 'general',
					'toggle_slug'      => 'gallery_settings',
				)
			),
			'show_in_lightbox'    => Utils::add_yes_no_field(
				esc_html__( 'Open in Lightbox', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Here you can choose whether or not the image should open in Lightbox. Note: if you select to open the image in Lightbox, url options below will be ignored.', 'squad-modules-for-divi' ),
					'default'     => 'off',
					'affects'     => array(
						'lightbox_notice',
						'zoom_icon_color',
						'hover_overlay_color',
						'hover_icon',
					),
					'tab_slug'    => 'general',
					'toggle_slug' => 'gallery_settings',
				)
			),
			'lightbox_notice'     => array(
				'label'           => '',
				'type'            => 'warning',
				'option_category' => 'configuration',
				'value'           => true,
				'display_if'      => true,
				'message'         => esc_html__( 'The lightbox feature will be work in the frontend.', 'squad-modules-for-divi' ),
				'depends_show_if' => 'on',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'gallery_settings',
				'bb_support'      => false,
			),
		);

		$overlay_fields = array(
			'zoom_icon_color'     => Utils::add_color_field(
				esc_html__( 'Overlay Icon Color', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Here you can define a custom color for the zoom icon.', 'squad-modules-for-divi' ),
					'depends_show_if' => 'on',
					'tab_slug'        => 'advanced',
					'toggle_slug'     => 'overlay',
				)
			),
			'hover_overlay_color' => Utils::add_color_field(
				esc_html__( 'Overlay Background Color', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Here you can define a custom color for the overlay', 'squad-modules-for-divi' ),
					'depends_show_if' => 'on',
					'tab_slug'        => 'advanced',
					'toggle_slug'     => 'overlay',
				)
			),
			'hover_icon'          => array(
				'label'           => esc_html__( 'Overlay Icon', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'Here you can define a custom icon for the overlay', 'squad-modules-for-divi' ),
				'type'            => 'select_icon',
				'option_category' => 'configuration',
				'class'           => array( 'et-pb-font-icon' ),
				'depends_show_if' => 'on',
				'tab_slug'        => 'advanced',
				'toggle_slug'     => 'overlay',
				'mobile_options'  => false,
				'sticky'          => false,
			),
		);

		return array_merge(
			$image_fields,
			$gallery_settings_fields,
			$overlay_fields
		);
	}

	/**
	 * Get CSS fields transition.
	 *
	 * Add form field options group and background image on the field list.
	 *
	 * @since 1.4.8
	 */
	public function get_transition_fields_css_props() {
		$fields = parent::get_transition_fields_css_props();

		// image styles.
		Utils::fix_border_transition( $fields, 'image', "$this->main_css_element div .gallery-images img" );
		Utils::fix_box_shadow_transition( $fields, 'image', "$this->main_css_element div .gallery-images img" );

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
		// Show a notice message in the frontend if the list item is empty.
		if ( empty( $this->prop( 'gallery_ids', '' ) ) ) {
			return sprintf(
				'<div class="squad-notice">%s</div>',
				esc_html__( 'Add one or more image(s).', 'squad-modules-for-divi' )
			);
		}

		// Enqueue scripts and styles.
		if ( 'on' === $this->prop( 'show_in_lightbox', 'off' ) ) {
			wp_enqueue_script( 'squad-vendor-images-loaded' );
			wp_enqueue_script( 'squad-vendor-light-gallery' );
		}

		wp_enqueue_script( 'squad-module-gallery' );

		// Generate styles.
		self::set_style(
			$this->slug,
			array(
				'selector'    => "$this->main_css_element .gallery-images",
				'declaration' => sprintf(
					'--squad-module-gallery-columns: %1$s;',
					esc_attr( $this->prop( 'columns_count', '4' ) )
				),
			)
		);
		self::set_style(
			$this->slug,
			array(
				'selector'    => "$this->main_css_element .gallery-images",
				'declaration' => sprintf(
					'--squad-module-gallery-gap: %1$s;',
					esc_attr( $this->prop( 'images_inner_gap', '10px' ) )
				),
			)
		);

		if ( 'on' === $this->prop( 'show_in_lightbox', 'off' ) ) {
			$this->generate_styles(
				array(
					'hover'          => false,
					'utility_arg'    => 'icon_font_family',
					'render_slug'    => $this->slug,
					'base_attr_name' => 'hover_icon',
					'important'      => true,
					'selector'       => "$this->main_css_element .gallery-images .gallery-image .et_overlay:before",
					'processor'      => array(
						'ET_Builder_Module_Helper_Style_Processor',
						'process_extended_icon',
					),
				)
			);
			$this->generate_styles(
				array(
					'hover'          => false,
					'base_attr_name' => 'zoom_icon_color',
					'selector'       => "$this->main_css_element .gallery-images .gallery-image .et_overlay:before",
					'css_property'   => 'color',
					'render_slug'    => $this->slug,
					'important'      => true,
					'type'           => 'color',
				)
			);
			$this->generate_styles(
				array(
					'hover'          => false,
					'base_attr_name' => 'hover_overlay_color',
					'selector'       => "$this->main_css_element .gallery-images .gallery-image .et_overlay",
					'css_property'   => array( 'background-color', 'border-color' ),
					'render_slug'    => $this->slug,
					'type'           => 'color',
				)
			);
		}

		// Images: Add CSS Filters and Mix Blend Mode rules (if set).
		$this->generate_css_filters( $render_slug, 'child_', "$this->main_css_element div .gallery-images img" );

		return $this->get_gallery_html( array_merge( $attrs, $this->props ) );
	}

	/**
	 * Get attachment html data for gallery module
	 *
	 * @param array $args Gallery Options.
	 *
	 * @return string|null Attachments data
	 */
	public function get_gallery_html( array $args = array() ): ?string {
		// Get gallery item data.
		$attachments = self::get_gallery( $args );

		if ( empty( $attachments ) ) {
			return '';
		}

		// Retrieve the gallery settings.
		$gallery_settings = wp_array_slice_assoc( $args, array( 'show_in_lightbox' ) );

		/**
		 * Filter the gallery plugins.
		 *
		 * @since 1.2.0
		 *
		 * @param array $gallery_plugins The gallery plugins.
		 *
		 * @return array
		 */
		$gallery_plugins = apply_filters( 'divi_squad_module_gallery_plugins', array( 'fullscreen', 'thumbnail' ) );

		// Gallery options.
		$gallery_options = array(
			'speed'   => 500,
			'plugins' => $gallery_plugins,
		);
		$images_quantity = $this->prop( 'images_quantity', 'default' );
		$image_count     = $this->prop( 'gallery_image_count', 4 );

		// Background layout class names.
		$background_layout_class_names = et_pb_background_layout_options()->get_background_layout_class( $this->props );
		$this->add_classname( $background_layout_class_names );

		ob_start();

		print sprintf(
			'<div class="gallery-images" data-setting=\'%1$s\'>',
			wp_json_encode( array_merge( $gallery_options, $gallery_settings ) )
		);

		$this->render_gallery_items( $attachments, $images_quantity, $image_count );

		print '</div>';

		return ob_get_clean();
	}

	/**
	 * Get attachment data for gallery module
	 *
	 * @param array $args             Gallery Options.
	 * @param array $conditional_tags Additional conditionals tags.
	 * @param array $current_page     Current page.
	 *
	 * @return array|WP_Post[] Attachments data
	 */
	public static function get_gallery( array $args = array(), array $conditional_tags = array(), array $current_page = array() ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClassAfterLastUsed
		$attachments = array();

		$defaults = array(
			'gallery_ids'      => array(),
			'gallery_order_by' => '',
			'orientation'      => 'landscape',
		);

		$args = wp_parse_args( $args, $defaults );

		$attachments_args = array(
			'include'        => $args['gallery_ids'],
			'post_status'    => 'inherit',
			'post_type'      => 'attachment',
			'post_mime_type' => 'image',
			'order'          => 'ASC',
			'orderby'        => 'post__in',
		);

		if ( 'rand' === $args['gallery_order_by'] ) {
			$attachments_args['orderby'] = 'rand';
		}

		$width  = 400;
		$height = ( 'landscape' === $args['orientation'] ) ? 284 : 516;

		$_attachments = get_posts( $attachments_args );

		foreach ( $_attachments as $key => $attachment ) {
			// Collect original image url.
			$image_src_full = wp_get_attachment_image_src( $attachment->ID, 'full' );
			$image_src_full = array_shift( $image_src_full );

			// Collect custom image url.
			$image_src_custom = wp_get_attachment_image_src( $attachment->ID, array( $width, $height ) );
			$image_src_custom = array_shift( $image_src_custom );

			// Collect image sizes.
			$image_meta    = wp_get_attachment_metadata( $attachment->ID );
			$image_size_lg = _wp_get_image_size_from_meta( 'full', $image_meta );
			$image_size_lg = implode( '-', $image_size_lg );

			$attachments[ $key ]                    = $attachment;
			$attachments[ $key ]->image_title       = $attachment->post_title;
			$attachments[ $key ]->image_caption     = $attachment->post_excerpt;
			$attachments[ $key ]->image_description = $attachment->post_content;
			$attachments[ $key ]->image_href        = get_permalink( $attachment );
			$attachments[ $key ]->image_src_full    = $image_src_full;
			$attachments[ $key ]->image_src_thumb   = $image_src_custom;
			$attachments[ $key ]->image_alt_text    = get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true );

			$attachments[ $key ]->lg_size = $image_size_lg;
		}

		return $attachments;
	}

	/**
	 * Renders gallery items.
	 *
	 * @param array|WP_Post $attachments     Array of attachment objects.
	 * @param string        $images_quantity Quantity of images to display.
	 * @param int           $image_count     Count of images per page.
	 */
	public function render_gallery_items( $attachments, string $images_quantity, int $image_count ): void {
		/**
		 * Loop through each attachment and render the image.
		 *
		 * @var int            $image_index The image index.
		 * @var WP_Post|object $attachment {
		 *     @property int    $ID                The attachment ID.
		 *     @property string $post_excerpt      The attachment excerpt.
		 *     @property string $image_title       The attachment title.
		 *     @property string $image_caption     The attachment caption.
		 *     @property string $image_description The attachment description.
		 *     @property string $image_href        The attachment URL.
		 *     @property string $image_src_full    The full-size image URL.
		 *     @property string $image_src_thumb   The thumbnail image URL.
		 *     @property string $image_alt_text    The image alt text.
		 *     @property string $lg_size           The large image size.
		 * }
		 *
		 * @see WP_Post
		 * @see wp_prepare_attachment_for_js
		 */
		foreach ( $attachments as $image_index => $attachment ) {
			$image_attrs = array(
				'alt'    => $attachment->image_alt_text,
				'style'  => ( 'custom' === $images_quantity && absint( $image_count ) < ( $image_index + 1 ) ) ? 'none' : '',
				'class'  => 'squad-image ' . et_pb_media_options()->get_image_attachment_class( array(), '', $attachment->ID ),
				'srcset' => $attachment->image_src_full . ' 479w, ' . $attachment->image_src_thumb . ' 480w',
				'sizes'  => '(max-width:479px) 479px, 100vw',
			);

			printf(
				'<a class="gallery-item" title="" href="%1$s" data-lg-size="%3$s" data-pinterest-text="%2$s" data-tweet-text="%2$s" data-src="%1$s" data-sub-html=""><div class="gallery-image">',
				esc_url( $attachment->image_src_full ),
				esc_attr( $attachment->post_excerpt ),
				esc_attr( $attachment->lg_size )
			);

			$this->render_image( $attachment->image_src_thumb, $image_attrs );

			if ( 'on' === $this->prop( 'show_in_lightbox', 'off' ) ) {
				echo wp_kses_post( OverlayHelper::render( array( 'icon' => $this->props['hover_icon'] ) ) );
			}

			echo '</div></a>';
		}
	}
}
