<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Logo Carousel Module Class which extend the Divi Builder Module Class.
 *
 * Swiper-based logo carousel with configurable hover effects, optional per-logo
 * links, and responsive slides-per-view. No lightbox dependency.
 *
 * @since   4.0.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Media;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use DiviSquad\Builder\Version4\Abstracts\Module;
use function absint;
use function esc_attr;
use function esc_attr__;
use function esc_html__;
use function wp_enqueue_script;
use function wp_enqueue_style;
use function wp_json_encode;

/**
 * Logo Carousel Module Class.
 *
 * @since 4.0.0
 */
class Logo_Carousel extends Module {

	/**
	 * Initiate Module.
	 *
	 * @since 4.0.0
	 * @return void
	 */
	public function init(): void {
		$this->name      = esc_html__( 'Logo Carousel', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Logo Carousels', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'logo-carousel.svg' );

		$this->slug             = 'disq_logo_carousel';
		$this->child_slug       = 'disq_logo_carousel_item';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.{$this->slug}";

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'carousel_settings'   => esc_html__( 'Carousel Settings', 'squad-modules-for-divi' ),
					'logo_settings'       => esc_html__( 'Logo Sizing', 'squad-modules-for-divi' ),
					'hover_settings'      => esc_html__( 'Hover Effect', 'squad-modules-for-divi' ),
					'autoplay_settings'   => esc_html__( 'Autoplay', 'squad-modules-for-divi' ),
					'navigation_settings' => esc_html__( 'Navigation', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'wrapper' => esc_html__( 'Wrapper', 'squad-modules-for-divi' ),
					'slide'   => esc_html__( 'Slide', 'squad-modules-for-divi' ),
					'arrow'   => esc_html__( 'Arrows', 'squad-modules-for-divi' ),
					'dots'    => esc_html__( 'Dots', 'squad-modules-for-divi' ),
				),
			),
		);

		$this->advanced_fields = array(
			'background'     => divi_squad()->d4_module_helper->selectors_background( $this->main_css_element ),
			'borders'        => array(
				'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
				'slide'   => array(
					'css'         => array(
						'main' => "{$this->main_css_element} .disq_logo_carousel_item",
					),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'slide',
				),
				'arrow'   => array(
					'css'         => array(
						'main' => "{$this->main_css_element} .squad-logo-carousel__arrow",
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
				'selector' => '.squad-logo-carousel',
			),
			'arrow'    => array(
				'label'    => esc_html__( 'Arrow', 'squad-modules-for-divi' ),
				'selector' => '.squad-logo-carousel__arrow',
			),
			'dots'     => array(
				'label'    => esc_html__( 'Dots', 'squad-modules-for-divi' ),
				'selector' => '.squad-logo-carousel__pagination',
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
			'slides_per_view'  => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Slides Per View', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Number of logos visible at once.', 'squad-modules-for-divi' ),
					'range_settings' => array(
						'min'       => '1',
						'max'       => '6',
						'step'      => '1',
						'min_limit' => '1',
					),
					'default'        => '4',
					'unitless'       => true,
					'mobile_options' => true,
					'responsive'     => true,
					'hover'          => false,
					'tab_slug'       => 'general',
					'toggle_slug'    => 'carousel_settings',
				)
			),
			'space_between'    => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Space Between Logos', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Gap between logo slides in pixels.', 'squad-modules-for-divi' ),
					'range_settings' => array(
						'min'  => '0',
						'max'  => '100',
						'step' => '1',
					),
					'default'        => '30',
					'unitless'       => true,
					'mobile_options' => false,
					'hover'          => false,
					'tab_slug'       => 'general',
					'toggle_slug'    => 'carousel_settings',
				)
			),
			'loop'             => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Loop', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Loop slides continuously.', 'squad-modules-for-divi' ),
					'default'     => 'on',
					'tab_slug'    => 'general',
					'toggle_slug' => 'carousel_settings',
				)
			),
			'transition_speed' => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Transition Speed (ms)', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Slide animation duration in milliseconds.', 'squad-modules-for-divi' ),
					'range_settings' => array(
						'min'  => '100',
						'max'  => '2000',
						'step' => '50',
					),
					'default'        => '500',
					'unitless'       => true,
					'mobile_options' => false,
					'hover'          => false,
					'tab_slug'       => 'general',
					'toggle_slug'    => 'carousel_settings',
				)
			),
			// Logo Sizing.
			'logo_max_width'   => array(
				'label'       => esc_html__( 'Logo Max Width', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Maximum width of each logo image (e.g. 160px).', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '160px',
				'tab_slug'    => 'general',
				'toggle_slug' => 'logo_settings',
			),
			'logo_max_height'  => array(
				'label'       => esc_html__( 'Logo Max Height', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Maximum height of each logo image (e.g. 80px).', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '80px',
				'tab_slug'    => 'general',
				'toggle_slug' => 'logo_settings',
			),
			// Hover Effect.
			'hover_effect'     => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Hover Effect', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Effect applied to logos on hover.', 'squad-modules-for-divi' ),
					'options'     => array(
						'grayscale' => esc_html__( 'Grayscale to Color', 'squad-modules-for-divi' ),
						'opacity'   => esc_html__( 'Opacity', 'squad-modules-for-divi' ),
						'zoom'      => esc_html__( 'Zoom', 'squad-modules-for-divi' ),
						'none'      => esc_html__( 'None', 'squad-modules-for-divi' ),
					),
					'default'     => 'grayscale',
					'affects'     => array( 'hover_opacity' ),
					'tab_slug'    => 'general',
					'toggle_slug' => 'hover_settings',
				)
			),
			'hover_opacity'    => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Resting Opacity', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Logo opacity at rest (applies when Hover Effect is Opacity).', 'squad-modules-for-divi' ),
					'range_settings'  => array(
						'min'  => '0',
						'max'  => '1',
						'step' => '0.05',
					),
					'default'         => '0.5',
					'unitless'        => true,
					'mobile_options'  => false,
					'hover'           => false,
					'depends_show_if' => 'opacity',
					'tab_slug'        => 'general',
					'toggle_slug'     => 'hover_settings',
				)
			),
			// Autoplay.
			'autoplay'         => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Enable Autoplay', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Automatically advance slides.', 'squad-modules-for-divi' ),
					'default'     => 'off',
					'affects'     => array( 'autoplay_speed' ),
					'tab_slug'    => 'general',
					'toggle_slug' => 'autoplay_settings',
				)
			),
			'autoplay_speed'   => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Autoplay Delay (ms)', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Milliseconds between auto-advances.', 'squad-modules-for-divi' ),
					'range_settings'  => array(
						'min'  => '1000',
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
			// Navigation.
			'show_arrows'      => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Show Arrows', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Display previous/next arrow buttons.', 'squad-modules-for-divi' ),
					'default'     => 'on',
					'tab_slug'    => 'general',
					'toggle_slug' => 'navigation_settings',
				)
			),
			'show_dots'        => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Show Dots', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Display pagination dots.', 'squad-modules-for-divi' ),
					'default'     => 'off',
					'tab_slug'    => 'general',
					'toggle_slug' => 'navigation_settings',
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
		$order_class = (string) self::get_module_order_class( $render_slug );

		wp_enqueue_style( 'squad-vendor-swiper' );
		wp_enqueue_script( 'squad-module-logo-carousel' );

		$this->apply_hover_css( $render_slug );
		$this->apply_logo_sizing_css( $render_slug );

		return sprintf(
			'<div class="squad-logo-carousel swiper" data-swiper-options=\'%1$s\'>
				<div class="swiper-wrapper squad-logo-carousel__wrapper">%2$s</div>
				%3$s
			</div>',
			esc_attr( $this->get_swiper_options( $order_class ) ),
			$content,
			$this->render_arrows() . $this->render_dots( $order_class )
		);
	}

	/**
	 * Apply hover effect CSS via set_style.
	 *
	 * @since 4.0.0
	 *
	 * @param string $render_slug Module render slug.
	 *
	 * @return void
	 */
	protected function apply_hover_css( string $render_slug ): void {
		$effect = $this->prop( 'hover_effect', 'grayscale' );
		$logo   = '%%order_class%% .disq_logo_carousel_item .squad-logo-carousel__logo';
		$hover  = '%%order_class%% .disq_logo_carousel_item:hover .squad-logo-carousel__logo';

		switch ( $effect ) {
			case 'grayscale':
				self::set_style( $render_slug, array(
					'selector'    => $logo,
					'declaration' => 'filter: grayscale(100%); transition: filter 0.3s ease;',
				) );
				self::set_style( $render_slug, array(
					'selector'    => $hover,
					'declaration' => 'filter: grayscale(0%);',
				) );
				break;

			case 'opacity':
				$opacity = max( 0.0, min( 1.0, (float) $this->prop( 'hover_opacity', '0.5' ) ) );
				self::set_style( $render_slug, array(
					'selector'    => $logo,
					'declaration' => "opacity: {$opacity}; transition: opacity 0.3s ease;",
				) );
				self::set_style( $render_slug, array(
					'selector'    => $hover,
					'declaration' => 'opacity: 1;',
				) );
				break;

			case 'zoom':
				self::set_style( $render_slug, array(
					'selector'    => $logo,
					'declaration' => 'transition: transform 0.3s ease;',
				) );
				self::set_style( $render_slug, array(
					'selector'    => $hover,
					'declaration' => 'transform: scale(1.05);',
				) );
				break;

			case 'none':
				// No effect — intentional.
				break;
		}
	}

	/**
	 * Apply logo max-width / max-height CSS.
	 *
	 * @since 4.0.0
	 *
	 * @param string $render_slug Module render slug.
	 *
	 * @return void
	 */
	protected function apply_logo_sizing_css( string $render_slug ): void {
		$max_width  = self::sanitize_css_length( (string) $this->prop( 'logo_max_width', '160px' ) );
		$max_height = self::sanitize_css_length( (string) $this->prop( 'logo_max_height', '80px' ) );

		if ( '' === $max_width && '' === $max_height ) {
			return;
		}

		$declaration = '';
		if ( '' !== $max_width ) {
			$declaration .= "max-width: {$max_width}; ";
		}
		if ( '' !== $max_height ) {
			$declaration .= "max-height: {$max_height}; ";
		}

		self::set_style( $render_slug, array(
			'selector'    => '%%order_class%% .squad-logo-carousel__logo',
			'declaration' => rtrim( $declaration ),
		) );
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
		$spv_desk    = max( 1, absint( $this->prop( 'slides_per_view', '4' ) ) );
		$spv_tab_raw = $this->prop( 'slides_per_view_tablet', '' );
		$spv_tab     = max( 1, '' !== $spv_tab_raw ? absint( $spv_tab_raw ) : $spv_desk );
		$spv_mob_raw = $this->prop( 'slides_per_view_phone', '' );
		$spv_mob     = max( 1, '' !== $spv_mob_raw ? absint( $spv_mob_raw ) : 2 );

		$gap   = max( 0, absint( $this->prop( 'space_between', '30' ) ) );
		$speed = max( 100, absint( $this->prop( 'transition_speed', '500' ) ) );
		$loop  = 'on' === $this->prop( 'loop', 'on' );

		$options = array(
			'slidesPerView' => $spv_mob,
			'spaceBetween'  => $gap,
			'speed'         => $speed,
			'loop'          => $loop,
			'wrapperClass'  => 'squad-logo-carousel__wrapper',
			'slideClass'    => 'disq_logo_carousel_item',
			'breakpoints'   => array(
				'768'  => array( 'slidesPerView' => $spv_tab, 'spaceBetween' => $gap ),
				'1024' => array( 'slidesPerView' => $spv_desk, 'spaceBetween' => $gap ),
			),
		);

		if ( 'on' === $this->prop( 'show_arrows', 'on' ) ) {
			$options['navigation'] = array(
				'prevEl' => ".{$order_class} .squad-logo-carousel__arrow--prev",
				'nextEl' => ".{$order_class} .squad-logo-carousel__arrow--next",
			);
		}

		if ( 'on' === $this->prop( 'show_dots', 'off' ) ) {
			$options['pagination'] = array(
				'el'        => ".{$order_class} .squad-logo-carousel__pagination",
				'clickable' => true,
			);
		}

		if ( 'on' === $this->prop( 'autoplay', 'off' ) ) {
			$options['autoplay'] = array(
				'delay'             => max( 500, absint( $this->prop( 'autoplay_speed', '3000' ) ) ),
				'pauseOnMouseEnter' => true,
			);
		}

		return (string) wp_json_encode( $options );
	}

	/**
	 * Render prev/next arrow buttons.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	public function render_arrows(): string {
		if ( 'on' !== $this->prop( 'show_arrows', 'on' ) ) {
			return '';
		}

		return '<button class="squad-logo-carousel__arrow squad-logo-carousel__arrow--prev" aria-label="' . esc_attr__( 'Previous slide', 'squad-modules-for-divi' ) . '"></button>'
		       . '<button class="squad-logo-carousel__arrow squad-logo-carousel__arrow--next" aria-label="' . esc_attr__( 'Next slide', 'squad-modules-for-divi' ) . '"></button>';
	}

	/**
	 * Render pagination dots container.
	 *
	 * @since 4.0.0
	 *
	 * @param string $order_class Resolved order class.
	 *
	 * @return string
	 */
	public function render_dots( string $order_class ): string {
		if ( 'on' !== $this->prop( 'show_dots', 'off' ) ) {
			return '';
		}

		return '<div class="squad-logo-carousel__pagination"></div>';
	}
}
