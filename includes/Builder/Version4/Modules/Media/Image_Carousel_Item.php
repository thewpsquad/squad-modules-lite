<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Image Carousel Item (child) Module Class.
 *
 * Represents a single slide in the Image Carousel parent module.
 *
 * @since   4.0.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Media;

use DiviSquad\Builder\Version4\Abstracts\Module\Child_Module;
use function esc_attr;
use function esc_attr__;
use function esc_html;
use function esc_html__;
use function esc_url;

/**
 * Image Carousel Item (child) Module Class.
 *
 * @since 4.0.0
 */
class Image_Carousel_Item extends Child_Module {

	/**
	 * Initiate Module.
	 *
	 * @since 4.0.0
	 * @return void
	 */
	public function init(): void {
		$this->name   = esc_html__( 'Image Carousel Item', 'squad-modules-for-divi' );
		$this->plural = esc_html__( 'Image Carousel Items', 'squad-modules-for-divi' );

		$this->slug             = 'disq_image_carousel_item';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.{$this->slug}";

		$this->child_title_var          = 'caption_text';
		$this->child_title_fallback_var = 'admin_label';

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'image_settings'   => esc_html__( 'Image', 'squad-modules-for-divi' ),
					'caption_settings' => esc_html__( 'Caption', 'squad-modules-for-divi' ),
					'button_settings'  => esc_html__( 'Button', 'squad-modules-for-divi' ),
					'overlay_settings' => esc_html__( 'Overlay', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'image'   => esc_html__( 'Image', 'squad-modules-for-divi' ),
					'overlay' => esc_html__( 'Overlay', 'squad-modules-for-divi' ),
					'caption' => esc_html__( 'Caption', 'squad-modules-for-divi' ),
					'button'  => esc_html__( 'Button', 'squad-modules-for-divi' ),
				),
			),
		);

		$this->advanced_fields = array(
			'background'     => array(
				'overlay' => array(
					'css'         => array(
						'main' => "{$this->main_css_element} .squad-image-carousel__overlay",
					),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'overlay',
				),
			),
			'borders'        => array(
				'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
				'image'   => array(
					'css'         => array(
						'main' => "{$this->main_css_element} .squad-image-carousel__image",
					),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'image',
				),
				'button'  => array(
					'css'         => array(
						'main' => "{$this->main_css_element} .squad-image-carousel__button",
					),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'button',
				),
			),
			'box_shadow'     => array(
				'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
				'image'   => array(
					'css'         => array(
						'main' => "{$this->main_css_element} .squad-image-carousel__image",
					),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'image',
				),
			),
			'margin_padding' => divi_squad()->d4_module_helper->selectors_margin_padding( $this->main_css_element ),
			'fonts'          => array(
				'caption' => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Caption', 'squad-modules-for-divi' ),
					array(
						'css'         => array(
							'main'  => "{$this->main_css_element} .squad-image-carousel__caption",
							'hover' => "{$this->main_css_element}:hover .squad-image-carousel__caption",
						),
						'tab_slug'    => 'advanced',
						'toggle_slug' => 'caption',
					)
				),
				'button'  => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Button', 'squad-modules-for-divi' ),
					array(
						'css'         => array(
							'main'  => "{$this->main_css_element} .squad-image-carousel__button",
							'hover' => "{$this->main_css_element}:hover .squad-image-carousel__button",
						),
						'tab_slug'    => 'advanced',
						'toggle_slug' => 'button',
					)
				),
			),
			'image_icon'     => false,
			'text'           => false,
			'button'         => false,
			'filters'        => false,
		);
	}

	/**
	 * Define module fields.
	 *
	 * @since 4.0.0
	 * @return array<string, mixed>
	 */
	public function get_fields(): array {
		return array(
			// Image.
			'image'          => divi_squad()->d4_module_helper->add_media_upload_field(
				esc_html__( 'Image', 'squad-modules-for-divi' ),
				array(
					'description'  => esc_html__( 'Upload or select the slide image.', 'squad-modules-for-divi' ),
					'tab_slug'     => 'general',
					'toggle_slug'  => 'image_settings',
				)
			),
			'image_alt'      => array(
				'label'       => esc_html__( 'Image Alt Text', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Alternative text for the slide image.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'tab_slug'    => 'general',
				'toggle_slug' => 'image_settings',
			),
			// Caption.
			'show_caption'   => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Show Caption', 'squad-modules-for-divi' ),
				array(
					'description'  => esc_html__( 'Display a caption over the slide image.', 'squad-modules-for-divi' ),
					'default'      => 'off',
					'affects'      => array( 'caption_text' ),
					'tab_slug'     => 'general',
					'toggle_slug'  => 'caption_settings',
				)
			),
			'caption_text'   => array(
				'label'           => esc_html__( 'Caption', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'The caption text displayed on the slide.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'default'         => '',
				'depends_show_if' => 'on',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'caption_settings',
			),
			// Button.
			'show_button'    => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Show Button', 'squad-modules-for-divi' ),
				array(
					'description'  => esc_html__( 'Display a call-to-action button on the slide.', 'squad-modules-for-divi' ),
					'default'      => 'off',
					'affects'      => array( 'button_text', 'button_url', 'button_target' ),
					'tab_slug'     => 'general',
					'toggle_slug'  => 'button_settings',
				)
			),
			'button_text'    => array(
				'label'           => esc_html__( 'Button Text', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'The label on the button.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'default'         => esc_html__( 'Learn More', 'squad-modules-for-divi' ),
				'depends_show_if' => 'on',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'button_settings',
			),
			'button_url'     => array(
				'label'           => esc_html__( 'Button URL', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'The destination URL for the button.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'default'         => '#',
				'depends_show_if' => 'on',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'button_settings',
			),
			'button_target'  => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Button Target', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Open the button link in the same tab or a new one.', 'squad-modules-for-divi' ),
					'options'         => array(
						'_self'  => esc_html__( 'Same Window', 'squad-modules-for-divi' ),
						'_blank' => esc_html__( 'New Window', 'squad-modules-for-divi' ),
					),
					'default'         => '_self',
					'depends_show_if' => 'on',
					'tab_slug'        => 'general',
					'toggle_slug'     => 'button_settings',
				)
			),
			// Overlay.
			'show_overlay'   => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Show Overlay', 'squad-modules-for-divi' ),
				array(
					'description'  => esc_html__( 'Show a color overlay on the slide image.', 'squad-modules-for-divi' ),
					'default'      => 'off',
					'tab_slug'     => 'general',
					'toggle_slug'  => 'overlay_settings',
				)
			),
		);
	}

	/**
	 * Render the slide HTML.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $attrs       Module attributes.
	 * @param string               $content     Inner content (unused for child modules).
	 * @param string               $render_slug Module render slug.
	 *
	 * @return string
	 */
	public function render( $attrs, $content, $render_slug ): string {
		$image_url = esc_url( $this->prop( 'image', '' ) );

		$data_src = ! empty( $image_url )
			? sprintf( ' data-src="%s"', $image_url )
			: '';

		return sprintf(
			'<div class="squad-image-carousel__item"%s>%s%s</div>',
			$data_src,
			$this->render_carousel_image( $image_url ),
			$this->render_overlay()
		);
	}

	/**
	 * Render the slide image.
	 *
	 * Named `render_carousel_image` (not `render_image`) to avoid colliding with
	 * the incompatible parent signature
	 * `ET_Builder_Element::render_image( $image_props, $image_attrs_raw = array(), $echo = true, $disable_responsive = false )`.
	 *
	 * @since 4.0.0
	 *
	 * @param string $image_url The image URL.
	 *
	 * @return string
	 */
	public function render_carousel_image( string $image_url ): string {
		if ( empty( $image_url ) ) {
			return '';
		}

		return sprintf(
			'<img class="squad-image-carousel__image" src="%1$s" alt="%2$s" loading="lazy" />',
			$image_url,
			esc_attr( $this->prop( 'image_alt', '' ) )
		);
	}

	/**
	 * Render the overlay, caption, and button.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	public function render_overlay(): string {
		$show_overlay = 'on' === $this->prop( 'show_overlay', 'off' );
		$show_caption = 'on' === $this->prop( 'show_caption', 'off' );
		$show_button  = 'on' === $this->prop( 'show_button', 'off' );

		if ( ! $show_overlay && ! $show_caption && ! $show_button ) {
			return '';
		}

		return sprintf(
			'<div class="squad-image-carousel__overlay">%s%s</div>',
			$this->render_caption(),
			$this->render_cta_button()
		);
	}

	/**
	 * Render the caption element.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	public function render_caption(): string {
		if ( 'on' !== $this->prop( 'show_caption', 'off' ) ) {
			return '';
		}

		$caption = $this->prop( 'caption_text', '' );
		if ( empty( $caption ) ) {
			return '';
		}

		return sprintf(
			'<div class="squad-image-carousel__caption">%s</div>',
			esc_html( $caption )
		);
	}

	/**
	 * Render the call-to-action button/link element.
	 *
	 * Named `render_cta_button` (not `render_button`) to avoid colliding with
	 * the incompatible parent signature `ET_Builder_Element::render_button( $args = array() )`.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	public function render_cta_button(): string {
		if ( 'on' !== $this->prop( 'show_button', 'off' ) ) {
			return '';
		}

		$text   = $this->prop( 'button_text', esc_html__( 'Learn More', 'squad-modules-for-divi' ) );
		$url    = esc_url( $this->prop( 'button_url', '#' ) );
		$target = $this->prop( 'button_target', '_self' );
		$target = in_array( $target, array( '_self', '_blank' ), true ) ? $target : '_self';

		return sprintf(
			'<a class="squad-image-carousel__button" href="%1$s" target="%2$s">%3$s</a>',
			$url,
			esc_attr( $target ),
			esc_html( $text )
		);
	}
}
