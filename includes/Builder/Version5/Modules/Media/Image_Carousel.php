<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Image Carousel Module (Divi 5 / Block API).
 *
 * Native Divi 5 parent module. Accepts child Image Carousel Item blocks,
 * renders them inside a Swiper carousel with optional lightGallery lightbox.
 *
 * @since   4.0.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version5\Modules\Media;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

if ( ! class_exists( 'ET\Builder\Packages\Module\Module' ) ) {
	return;
}

use DiviSquad\Builder\Version5\Abstracts\Module;
use ET\Builder\FrontEnd\Module\Style;
use ET\Builder\Packages\Module\Layout\Components\ModuleElements\ModuleElements;
use ET\Builder\Packages\Module\Module as DiviModule;
use ET\Builder\Packages\Module\Options\Css\CssStyle;
use ET\Builder\Packages\Module\Options\Element\ElementClassnames;
use Throwable;
use WP_Block;
use function absint;
use function esc_attr;
use function esc_attr__;
use function esc_html__;
use function wp_enqueue_script;
use function wp_enqueue_style;
use function wp_json_encode;

/**
 * Image Carousel parent module class.
 *
 * @since 4.0.0
 */
class Image_Carousel extends Module {

	/**
	 * Relative path to the generated module.json metadata folder.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/image-carousel/';
	}

	/**
	 * Add the module classnames.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $args Classnames arguments.
	 *
	 * @return void
	 */
	public static function module_classnames( array $args ): void {
		$args['classnamesInstance']->add( 'disq_image_carousel' );
		$args['classnamesInstance']->add(
			ElementClassnames::classnames(
				array(
					'attrs' => $args['attrs']['module']['decoration'] ?? array(),
				)
			)
		);
	}

	/**
	 * Assign the module's frontend script data.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $args Script data arguments.
	 *
	 * @return void
	 */
	public static function module_script_data( array $args ): void {
		$args['elements']->script_data(
			array(
				'attrName' => 'module',
			)
		);
	}

	/**
	 * Register the module style declarations.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $args Style arguments.
	 *
	 * @return void
	 */
	public static function module_styles( array $args ): void {
		$attrs    = $args['attrs'] ?? array();
		$elements = $args['elements'];
		$settings = $args['settings'] ?? array();

		Style::add(
			array(
				'id'            => $args['id'],
				'name'          => $args['name'],
				'orderIndex'    => $args['orderIndex'],
				'storeInstance' => $args['storeInstance'],
				'styles'        => array(
					$elements->style(
						array(
							'attrName'   => 'module',
							'styleProps' => array(
								'disabledOn' => array(
									'disabledModuleVisibility' => $settings['disabledModuleVisibility'] ?? null,
								),
							),
						)
					),
					$elements->style( array( 'attrName' => 'slide' ) ),
					$elements->style( array( 'attrName' => 'arrow' ) ),
					$elements->style( array( 'attrName' => 'dots' ) ),
					$elements->style( array( 'attrName' => 'progressBar' ) ),
					CssStyle::style(
						array(
							'selector' => $args['orderClass'],
							'attr'     => $attrs['css'] ?? array(),
						)
					),
				),
			)
		);
	}

	/**
	 * Render callback for the Image Carousel module.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $attrs                 Block attributes.
	 * @param string               $child_modules_content Rendered child (Image Carousel Item) HTML.
	 * @param WP_Block             $block                 Parsed block instance.
	 * @param ModuleElements       $elements              ModuleElements instance.
	 *
	 * @return string Rendered HTML.
	 */
	public static function render_callback( array $attrs, string $child_modules_content, WP_Block $block, $elements ): string {
		try {
			if ( '' === trim( $child_modules_content ) ) {
				return sprintf(
					'<div class="squad-notice">%s</div>',
					esc_html__( 'Add at least one Image Carousel Item.', 'squad-modules-for-divi' )
				);
			}

			$inner           = $attrs['carousel']['innerContent']['desktop']['value'] ?? array();
			$enable_lightbox = $inner['enableLightbox'] ?? 'off';

			wp_enqueue_style( 'squad-vendor-swiper' );

			if ( 'on' === $enable_lightbox ) {
				wp_enqueue_script( 'squad-vendor-light-gallery' );
				wp_enqueue_style( 'squad-vendor-light-gallery' );
			}

			wp_enqueue_script( 'squad-module-image-carousel' );

			$uid = self::get_instance_uid( $block );

			$carousel_html = sprintf(
				'<div class="squad-image-carousel swiper" data-swiper-options=\'%1$s\' data-lightbox="%2$s">
					<div class="swiper-wrapper squad-image-carousel__wrapper">%3$s</div>
					%4$s
				</div>',
				esc_attr( (string) wp_json_encode( self::build_swiper_options( $inner, $uid ) ) ),
				esc_attr( $enable_lightbox ),
				$child_modules_content,
				self::render_navigation( $inner, $uid )
			);

			return DiviModule::render(
				array(
					'orderIndex'          => $block->parsed_block['orderIndex'],
					'storeInstance'       => $block->parsed_block['storeInstance'],
					'attrs'               => $attrs,
					'elements'            => $elements,
					'id'                  => $block->parsed_block['id'],
					'name'                => $block->block_type->name,
					'moduleCategory'      => $block->block_type->category,
					'classnamesFunction'  => array( static::class, 'module_classnames' ),
					'stylesComponent'     => array( static::class, 'module_styles' ),
					'scriptDataComponent' => array( static::class, 'module_script_data' ),
					'children'            => $elements->style_components( array( 'attrName' => 'module' ) ) . $carousel_html,
				)
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Image Carousel module' );

			return '';
		}
	}

	/**
	 * Build a stable per-instance uid for scoping Swiper navigation selectors.
	 *
	 * @since 4.0.0
	 *
	 * @param WP_Block $block The parsed block.
	 *
	 * @return string e.g. "squad-ic-a1b2c3d4e5"
	 */
	protected static function get_instance_uid( WP_Block $block ): string {
		$raw = (string) ( $block->parsed_block['id'] ?? '' );
		$uid = preg_replace( '/[^a-z0-9]/', '', strtolower( $raw ) );

		return '' !== $uid
			? 'squad-ic-' . $uid
			: 'squad-ic-' . substr( md5( $raw . wp_json_encode( $block->parsed_block['orderIndex'] ?? 0 ) ), 0, 10 );
	}

	/**
	 * Build the Swiper options array from the carousel innerContent values.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $inner Carousel innerContent values.
	 * @param string               $uid   Per-instance identifier.
	 *
	 * @return array<string, mixed>
	 */
	protected static function build_swiper_options( array $inner, string $uid ): array {
		$effect = (string) ( $inner['effect'] ?? 'slide' );
		$effect = in_array( $effect, array( 'slide', 'fade', 'coverflow' ), true ) ? $effect : 'slide';

		$spv_desk = max( 1, absint( $inner['slidesPerView'] ?? 1 ) );
		$spv_tab  = max( 1, absint( $inner['slidesPerViewTablet'] ?? $spv_desk ) );
		$spv_mob  = max( 1, absint( $inner['slidesPerViewPhone'] ?? 1 ) );

		if ( 'fade' === $effect ) {
			$spv_desk = $spv_tab = $spv_mob = 1;
		}

		$gap   = absint( $inner['spaceBetween'] ?? 20 );
		$speed = max( 100, absint( $inner['autoplaySpeed'] ?? 500 ) );
		$loop  = 'on' === ( $inner['loop'] ?? 'on' );

		$options = array(
			'effect'         => $effect,
			'slidesPerView'  => $spv_mob,
			'spaceBetween'   => $gap,
			'speed'          => $speed,
			'loop'           => $loop,
			'centeredSlides' => 'on' === ( $inner['centeredSlides'] ?? 'off' ),
			'breakpoints'    => array(
				'768'  => array( 'slidesPerView' => $spv_tab, 'spaceBetween' => $gap ),
				'1024' => array( 'slidesPerView' => $spv_desk, 'spaceBetween' => $gap ),
			),
		);

		if ( 'on' === ( $inner['showArrows'] ?? 'on' ) ) {
			$options['navigation'] = array(
				'prevEl' => ".{$uid}-prev",
				'nextEl' => ".{$uid}-next",
			);
		}

		if ( 'on' === ( $inner['showDots'] ?? 'on' ) ) {
			$options['pagination'] = array(
				'el'             => ".{$uid}-pagination",
				'clickable'      => true,
				'dynamicBullets' => true,
			);
		}

		if ( 'on' === ( $inner['enableAutoplay'] ?? 'off' ) ) {
			$options['autoplay'] = array(
				'delay'                => max( 500, absint( $inner['autoplayDelay'] ?? 3000 ) ),
				'pauseOnMouseEnter'    => 'on' === ( $inner['pauseOnHover'] ?? 'on' ),
				'disableOnInteraction' => 'on' === ( $inner['pauseOnInteraction'] ?? 'on' ),
			);
		}

		return $options;
	}

	/**
	 * Render navigation controls (arrows, dots, progress bar).
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $inner Carousel innerContent values.
	 * @param string               $uid   Per-instance identifier.
	 *
	 * @return string
	 */
	protected static function render_navigation( array $inner, string $uid ): string {
		$html = '';

		if ( 'on' === ( $inner['showArrows'] ?? 'on' ) ) {
			$html .= sprintf(
				'<button class="squad-image-carousel__arrow squad-image-carousel__arrow--prev %1$s-prev" aria-label="%2$s"></button>'
				. '<button class="squad-image-carousel__arrow squad-image-carousel__arrow--next %1$s-next" aria-label="%3$s"></button>',
				esc_attr( $uid ),
				esc_attr__( 'Previous slide', 'squad-modules-for-divi' ),
				esc_attr__( 'Next slide', 'squad-modules-for-divi' )
			);
		}

		if ( 'on' === ( $inner['showDots'] ?? 'on' ) ) {
			$html .= sprintf(
				'<div class="squad-image-carousel__dots %s-pagination"></div>',
				esc_attr( $uid )
			);
		}

		if ( 'on' === ( $inner['showProgress'] ?? 'off' ) ) {
			$html .= '<div class="squad-image-carousel__progress"><div class="squad-image-carousel__progress-bar"></div></div>';
		}

		return $html;
	}
}
