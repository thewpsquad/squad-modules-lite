<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Image Carousel Module Class which extend the Divi Builder Module Class.
 *
 * This class provides an interactive image carousel with Swiper.js, optional
 * lightbox, and per-slide caption/button support.
 *
 * @since   4.0.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Media;

use DiviSquad\Builder\Version4\Abstracts\Module;
use function absint;
use function esc_attr;
use function esc_attr__;
use function esc_html__;
use function wp_enqueue_script;
use function wp_enqueue_style;
use function wp_json_encode;

/**
 * Image Carousel Module Class.
 *
 * @since 4.0.0
 */
class Image_Carousel extends Module {

	/**
	 * Initiate Module.
	 *
	 * @since 4.0.0
	 * @return void
	 */
	public function init(): void {
		$this->name      = esc_html__( 'Image Carousel', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Image Carousels', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'image-carousel.svg' );

		$this->slug             = 'disq_image_carousel';
		$this->child_slug       = 'disq_image_carousel_item';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.{$this->slug}";

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'carousel_settings'   => esc_html__( 'Carousel Settings', 'squad-modules-for-divi' ),
					'autoplay_settings'   => esc_html__( 'Autoplay', 'squad-modules-for-divi' ),
					'navigation_settings' => esc_html__( 'Navigation', 'squad-modules-for-divi' ),
					'lightbox_settings'   => esc_html__( 'Lightbox', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'wrapper'      => esc_html__( 'Wrapper', 'squad-modules-for-divi' ),
					'slide'        => esc_html__( 'Slide', 'squad-modules-for-divi' ),
					'arrow'        => esc_html__( 'Arrows', 'squad-modules-for-divi' ),
					'dots'         => esc_html__( 'Dots', 'squad-modules-for-divi' ),
					'progress_bar' => esc_html__( 'Progress Bar', 'squad-modules-for-divi' ),
				),
			),
		);

		$this->advanced_fields = array(
			'background'     => divi_squad()->d4_module_helper->selectors_background( $this->main_css_element ),
			'borders'        => array(
				'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
				'slide'   => array(
					'css'         => array(
						'main' => "{$this->main_css_element} .disq_image_carousel_item",
					),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'slide',
				),
				'arrow'   => array(
					'css'         => array(
						'main' => "{$this->main_css_element} .squad-image-carousel__arrow",
					),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'arrow',
				),
			),
			'box_shadow'     => array(
				'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			),
			'margin_padding' => divi_squad()->d4_module_helper->selectors_margin_padding( $this->main_css_element ),
			'max_width'      => divi_squad()->d4_module_helper->selectors_max_width( $this->main_css_element ),
			'height'         => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			'fonts'          => false,
			'image_icon'     => false,
			'text'           => false,
			'button'         => false,
			'filters'        => false,
		);

		$this->custom_css_fields = array(
			'carousel' => array(
				'label'    => esc_html__( 'Carousel', 'squad-modules-for-divi' ),
				'selector' => '.squad-image-carousel',
			),
			'arrow'    => array(
				'label'    => esc_html__( 'Arrow', 'squad-modules-for-divi' ),
				'selector' => '.squad-image-carousel__arrow',
			),
			'dots'     => array(
				'label'    => esc_html__( 'Dots', 'squad-modules-for-divi' ),
				'selector' => '.squad-image-carousel__dots',
			),
		);
	}

	/**
	 * Define module fields.
	 *
	 * @since 4.0.0
	 * @return array<string, array<string, mixed>>
	 */
	public function get_fields(): array {
		return array(
			// Carousel Settings.
			'effect'               => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Carousel Effect', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Choose the slide transition effect.', 'squad-modules-for-divi' ),
					'options'     => array(
						'slide'     => esc_html__( 'Slide', 'squad-modules-for-divi' ),
						'fade'      => esc_html__( 'Fade', 'squad-modules-for-divi' ),
						'coverflow' => esc_html__( 'Coverflow', 'squad-modules-for-divi' ),
					),
					'default'     => 'slide',
					'tab_slug'    => 'general',
					'toggle_slug' => 'carousel_settings',
				)
			),
			'slides_per_view'      => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Slides Per View', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Number of slides visible at once.', 'squad-modules-for-divi' ),
					'range_settings' => array(
						'min'       => '1',
						'max'       => '6',
						'step'      => '1',
						'min_limit' => '1',
					),
					'default'        => '1',
					'unitless'       => true,
					'mobile_options' => true,
					'responsive'     => true,
					'hover'          => false,
					'tab_slug'       => 'general',
					'toggle_slug'    => 'carousel_settings',
				)
			),
			'space_between'        => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Space Between Slides', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Gap between slides in pixels.', 'squad-modules-for-divi' ),
					'range_settings' => array(
						'min'  => '0',
						'max'  => '200',
						'step' => '1',
					),
					'default'        => '20px',
					'mobile_options' => true,
					'responsive'     => true,
					'hover'          => false,
					'tab_slug'       => 'general',
					'toggle_slug'    => 'carousel_settings',
				)
			),
			'loop'                 => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Loop', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Loop slides continuously.', 'squad-modules-for-divi' ),
					'default'     => 'on',
					'tab_slug'    => 'general',
					'toggle_slug' => 'carousel_settings',
				)
			),
			'centered_slides'      => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Centered Slides', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Center the active slide.', 'squad-modules-for-divi' ),
					'default'     => 'off',
					'tab_slug'    => 'general',
					'toggle_slug' => 'carousel_settings',
				)
			),
			'slide_height'         => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Slide Height', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Fixed slide height (leave empty for natural image height).', 'squad-modules-for-divi' ),
					'range_settings' => array(
						'min'  => '100',
						'max'  => '1000',
						'step' => '1',
					),
					'default'        => '',
					'mobile_options' => false,
					'hover'          => false,
					'tab_slug'       => 'general',
					'toggle_slug'    => 'carousel_settings',
				)
			),
			// Autoplay Settings.
			'enable_autoplay'      => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Enable Autoplay', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Automatically advance slides.', 'squad-modules-for-divi' ),
					'default'     => 'off',
					'affects'     => array( 'autoplay_delay', 'autoplay_speed', 'pause_on_hover', 'pause_on_interaction' ),
					'tab_slug'    => 'general',
					'toggle_slug' => 'autoplay_settings',
				)
			),
			'autoplay_delay'       => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Autoplay Delay (ms)', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Milliseconds between slides.', 'squad-modules-for-divi' ),
					'range_settings'  => array(
						'min'  => '500',
						'max'  => '10000',
						'step' => '100',
					),
					'default'         => '3000',
					'unitless'        => true,
					'mobile_options'  => false,
					'hover'           => false,
					'depends_show_if' => 'on',
					'tab_slug'        => 'general',
					'toggle_slug'     => 'autoplay_settings',
				)
			),
			'autoplay_speed'       => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Transition Speed (ms)', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Slide transition duration in milliseconds.', 'squad-modules-for-divi' ),
					'range_settings'  => array(
						'min'  => '100',
						'max'  => '3000',
						'step' => '50',
					),
					'default'         => '500',
					'unitless'        => true,
					'mobile_options'  => false,
					'hover'           => false,
					'depends_show_if' => 'on',
					'tab_slug'        => 'general',
					'toggle_slug'     => 'autoplay_settings',
				)
			),
			'pause_on_hover'       => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Pause on Hover', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Pause autoplay when the cursor is over the carousel.', 'squad-modules-for-divi' ),
					'default'         => 'on',
					'depends_show_if' => 'on',
					'tab_slug'        => 'general',
					'toggle_slug'     => 'autoplay_settings',
				)
			),
			'pause_on_interaction' => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Pause on Interaction', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Pause autoplay after user interaction.', 'squad-modules-for-divi' ),
					'default'         => 'on',
					'depends_show_if' => 'on',
					'tab_slug'        => 'general',
					'toggle_slug'     => 'autoplay_settings',
				)
			),
			// Navigation Settings.
			'show_arrows'          => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Show Arrows', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Display previous/next arrow buttons.', 'squad-modules-for-divi' ),
					'default'     => 'on',
					'tab_slug'    => 'general',
					'toggle_slug' => 'navigation_settings',
				)
			),
			'show_dots'            => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Show Dots', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Display pagination dots.', 'squad-modules-for-divi' ),
					'default'     => 'on',
					'tab_slug'    => 'general',
					'toggle_slug' => 'navigation_settings',
				)
			),
			'show_progress'        => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Show Progress Bar', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Display a progress bar below the carousel.', 'squad-modules-for-divi' ),
					'default'     => 'off',
					'tab_slug'    => 'general',
					'toggle_slug' => 'navigation_settings',
				)
			),
			// Lightbox.
			'enable_lightbox'      => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Enable Lightbox', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Open images in a full-screen lightbox on click.', 'squad-modules-for-divi' ),
					'default'     => 'off',
					'tab_slug'    => 'general',
					'toggle_slug' => 'lightbox_settings',
				)
			),
		);
	}

	/**
	 * Render the module HTML.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $attrs       Module attributes.
	 * @param string               $content     Rendered child modules HTML.
	 * @param string               $render_slug Module render slug.
	 *
	 * @return string
	 */
	public function render( $attrs, $content, $render_slug ): string {
		$order_class     = (string) self::get_module_order_class( $render_slug );
		$enable_lightbox = $this->prop( 'enable_lightbox', 'off' );

		wp_enqueue_style( 'squad-vendor-swiper' );

		if ( 'on' === $enable_lightbox ) {
			wp_enqueue_script( 'squad-vendor-light-gallery' );
			wp_enqueue_style( 'squad-vendor-light-gallery' );
		}

		wp_enqueue_script( 'squad-module-image-carousel' );

		$slide_height = (string) $this->prop( 'slide_height', '' );
		if ( '' !== $slide_height ) {
			$height_val = absint( $slide_height );
			if ( $height_val > 0 ) {
				self::set_style(
					$render_slug,
					array(
						'selector'    => '%%order_class%% .disq_image_carousel_item',
						'declaration' => "height: {$height_val}px; overflow: hidden;",
					)
				);
			}
		}

		return sprintf(
			'<div class="squad-image-carousel swiper" data-swiper-options=\'%1$s\' data-lightbox="%2$s">
				<div class="squad-image-carousel__wrapper swiper-wrapper">%3$s</div>
				%4$s
			</div>',
			esc_attr( $this->get_swiper_options( $order_class ) ),
			esc_attr( $enable_lightbox ),
			$content,
			$this->render_controls( $order_class )
		);
	}

	/**
	 * Build Swiper JSON options string.
	 *
	 * @since 4.0.0
	 *
	 * @param string $order_class Resolved order class for scoping selectors.
	 *
	 * @return string JSON-encoded options.
	 */
	public function get_swiper_options( string $order_class ): string {
		$effect = (string) $this->prop( 'effect', 'slide' );
		$effect = in_array( $effect, array( 'slide', 'fade', 'coverflow' ), true ) ? $effect : 'slide';

		$spv_desk = max( 1, absint( $this->prop( 'slides_per_view', '1' ) ) );

		$spv_tab_raw = (string) $this->prop( 'slides_per_view_tablet', '' );
		$spv_tab     = max( 1, '' !== $spv_tab_raw ? absint( $spv_tab_raw ) : $spv_desk );

		$spv_mob_raw = (string) $this->prop( 'slides_per_view_phone', '' );
		$spv_mob     = max( 1, '' !== $spv_mob_raw ? absint( $spv_mob_raw ) : 1 );

		if ( 'fade' === $effect ) {
			$spv_desk = $spv_tab = $spv_mob = 1;
		}

		$gap   = absint( $this->prop( 'space_between', '20px' ) );
		$speed = max( 100, absint( $this->prop( 'autoplay_speed', '500' ) ) );
		$loop  = 'on' === $this->prop( 'loop', 'on' );

		$options = array(
			'effect'         => $effect,
			'slidesPerView'  => $spv_mob,
			'spaceBetween'   => $gap,
			'speed'          => $speed,
			'loop'           => $loop,
			'centeredSlides' => 'on' === $this->prop( 'centered_slides', 'off' ),
			'wrapperClass'   => 'squad-image-carousel__wrapper',
			'slideClass'     => 'disq_image_carousel_item',
			'breakpoints'    => array(
				'768'  => array( 'slidesPerView' => $spv_tab, 'spaceBetween' => $gap ),
				'1024' => array( 'slidesPerView' => $spv_desk, 'spaceBetween' => $gap ),
			),
		);

		if ( 'on' === $this->prop( 'show_arrows', 'on' ) ) {
			$options['navigation'] = array(
				'prevEl' => ".{$order_class} .squad-image-carousel__arrow--prev",
				'nextEl' => ".{$order_class} .squad-image-carousel__arrow--next",
			);
		}

		if ( 'on' === $this->prop( 'show_dots', 'on' ) ) {
			$options['pagination'] = array(
				'el'             => ".{$order_class} .squad-image-carousel__dots",
				'clickable'      => true,
				'dynamicBullets' => true,
			);
		}

		if ( 'on' === $this->prop( 'enable_autoplay', 'off' ) ) {
			$options['autoplay'] = array(
				'delay'                => max( 500, absint( $this->prop( 'autoplay_delay', '3000' ) ) ),
				'pauseOnMouseEnter'    => 'on' === $this->prop( 'pause_on_hover', 'on' ),
				'disableOnInteraction' => 'on' === $this->prop( 'pause_on_interaction', 'on' ),
			);
		}

		return (string) wp_json_encode( $options );
	}

	/**
	 * Render arrow + pagination controls.
	 *
	 * @since 4.0.0
	 *
	 * @param string $order_class Resolved order class.
	 *
	 * @return string
	 */
	public function render_controls( string $order_class ): string {
		return $this->render_arrows() . $this->render_dots() . $this->render_progress();
	}

	/**
	 * Render arrow buttons.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	public function render_arrows(): string {
		if ( 'on' !== $this->prop( 'show_arrows', 'on' ) ) {
			return '';
		}

		return '<button class="squad-image-carousel__arrow squad-image-carousel__arrow--prev" aria-label="' . esc_attr__( 'Previous slide', 'squad-modules-for-divi' ) . '"></button>'
		       . '<button class="squad-image-carousel__arrow squad-image-carousel__arrow--next" aria-label="' . esc_attr__( 'Next slide', 'squad-modules-for-divi' ) . '"></button>';
	}

	/**
	 * Render pagination dots container.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	public function render_dots(): string {
		if ( 'on' !== $this->prop( 'show_dots', 'on' ) ) {
			return '';
		}

		return '<div class="squad-image-carousel__dots"></div>';
	}

	/**
	 * Render progress bar.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	public function render_progress(): string {
		if ( 'on' !== $this->prop( 'show_progress', 'off' ) ) {
			return '';
		}

		return '<div class="squad-image-carousel__progress"><div class="squad-image-carousel__progress-bar"></div></div>';
	}
}
