<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Post Carousel Module (Divi 5 / Block API).
 *
 * Native Divi 5 dynamic-content module. Queries posts server-side and renders the nested
 * "Post Element" (Post_Grid_Child) modules per post inside a Swiper carousel. It reuses the
 * {@see Post_Grid} query + element-rendering engine — each queried post becomes a
 * `swiper-slide` instead of a grid `<li>` — so it inherits every Post Element type, icon,
 * separator and query option for free.
 *
 * @since   3.4.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version5\Modules\Dynamic_Content;

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
use WP_Query;
use function absint;
use function esc_attr;
use function esc_html;
use function esc_html__;
use function wp_enqueue_script;
use function wp_enqueue_style;
use function wp_json_encode;
use function wp_reset_postdata;

/**
 * Post Carousel module class.
 *
 * @since 3.4.0
 */
class Post_Carousel extends Module {

	/**
	 * Relative path to the generated module.json metadata folder.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/post-carousel/';
	}

	/**
	 * Add the module classnames.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $args Classnames arguments.
	 *
	 * @return void
	 */
	public static function module_classnames( array $args ): void {
		$args['classnamesInstance']->add( 'disq_post_carousel' );
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
	 * @since 3.4.0
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
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $args Style arguments provided by Divi.
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
					$elements->style( array( 'attrName' => 'slideItem' ) ),
					$elements->style( array( 'attrName' => 'arrows' ) ),
					$elements->style( array( 'attrName' => 'dots' ) ),
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
	 * Render callback for the Post Carousel module.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $attrs                 Block attributes.
	 * @param string               $child_modules_content Rendered child (Post Element) content with config markers.
	 * @param WP_Block             $block                 Parsed block instance.
	 * @param ModuleElements       $elements              ModuleElements instance.
	 *
	 * @return string Rendered HTML.
	 */
	public static function render_callback( array $attrs, string $child_modules_content, WP_Block $block, $elements ): string {
		try {
			if ( '' === trim( $child_modules_content ) ) {
				return self::render_notice( esc_html__( 'Add at least one Post Element to display.', 'squad-modules-for-divi' ) );
			}

			$inner = $attrs['query']['innerContent']['desktop']['value'] ?? array();
			$query = new WP_Query( Post_Grid::build_query_args( $inner ) );

			if ( ! $query->have_posts() ) {
				wp_reset_postdata();

				return self::render_notice( esc_html__( 'No posts found.', 'squad-modules-for-divi' ) );
			}

			$slides = Post_Grid::render_post_items( $child_modules_content, $query, 'div', 'swiper-slide' );
			wp_reset_postdata();

			// Enqueue Swiper and the carousel init script.
			wp_enqueue_style( 'squad-vendor-swiper' );
			wp_enqueue_script( 'squad-module-post-carousel' );

			$uid = self::get_instance_uid( $block );

			$carousel_html = sprintf(
				'<div class="swiper squad-post-carousel" data-swiper-options=\'%1$s\'><div class="swiper-wrapper">%2$s</div>%3$s</div>',
				esc_attr( (string) wp_json_encode( self::build_swiper_options( $inner, $uid, (int) $query->found_posts ) ) ),
				$slides,
				self::render_controls( $inner, $uid )
			);

			$style_components = $elements instanceof ModuleElements
				? $elements->style_components( array( 'attrName' => 'module' ) )
				: '';

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
					'children'            => $style_components . $carousel_html,
				)
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Post Carousel module' );

			return '';
		}
	}

	/**
	 * Build a stable per-instance identifier for scoping Swiper navigation selectors.
	 *
	 * @since 3.4.0
	 *
	 * @param WP_Block $block The parsed block.
	 *
	 * @return string
	 */
	protected static function get_instance_uid( WP_Block $block ): string {
		$raw = (string) ( $block->parsed_block['id'] ?? '' );
		$uid = preg_replace( '/[^a-z0-9]/', '', strtolower( $raw ) );

		return '' !== $uid ? 'squad-pc-' . $uid : 'squad-pc-' . substr( md5( $raw . wp_json_encode( $block->parsed_block['orderIndex'] ?? 0 ) ), 0, 10 );
	}

	/**
	 * Build the Swiper option object from the query inner-content values.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $inner      Query inner-content values.
	 * @param string               $uid        Per-instance identifier.
	 * @param int                  $post_count Number of posts in the query (for loop guarding).
	 *
	 * @return array<string, mixed>
	 */
	protected static function build_swiper_options( array $inner, string $uid, int $post_count = 0 ): array {
		$slides_desktop = max( 1, absint( $inner['carouselSlidesDesktop'] ?? 3 ) );
		$slides_tablet  = max( 1, absint( $inner['carouselSlidesTablet'] ?? 2 ) );
		$slides_phone   = max( 1, absint( $inner['carouselSlidesPhone'] ?? 1 ) );
		$gap            = absint( $inner['carouselGap'] ?? 30 );
		$speed          = absint( $inner['carouselSpeed'] ?? 500 );
		$speed          = $speed > 0 ? $speed : 500;

		$effect = (string) ( $inner['carouselEffect'] ?? 'slide' );
		$effect = in_array( $effect, array( 'slide', 'fade', 'coverflow' ), true ) ? $effect : 'slide';

		// Swiper's fade effect only supports a single slide per view — clamp every breakpoint.
		if ( 'fade' === $effect ) {
			$slides_desktop = 1;
			$slides_tablet  = 1;
			$slides_phone   = 1;
		}

		// Swiper loop needs more slides than are shown; disable it when too few posts exist.
		$max_slides = max( $slides_desktop, $slides_tablet, $slides_phone );
		$loop       = 'on' === ( $inner['carouselLoop'] ?? 'off' );
		if ( $loop && $post_count > 0 && $post_count <= $max_slides ) {
			$loop = false;
		}

		$options = array(
			'slidesPerView'  => $slides_phone,
			'spaceBetween'   => $gap,
			'speed'          => $speed,
			'effect'         => $effect,
			'centeredSlides' => 'on' === ( $inner['carouselCentered'] ?? 'off' ),
			'loop'           => $loop,
			'breakpoints'    => array(
				'768'  => array(
					'slidesPerView' => $slides_tablet,
					'spaceBetween'  => $gap,
				),
				'1024' => array(
					'slidesPerView' => $slides_desktop,
					'spaceBetween'  => $gap,
				),
			),
		);

		if ( 'on' === ( $inner['carouselArrows'] ?? 'on' ) ) {
			$options['navigation'] = array(
				'nextEl' => ".{$uid}-next",
				'prevEl' => ".{$uid}-prev",
			);
		}

		if ( 'on' === ( $inner['carouselDots'] ?? 'on' ) ) {
			$options['pagination'] = array(
				'el'             => ".{$uid}-pagination",
				'clickable'      => true,
				'dynamicBullets' => true,
			);
		}

		if ( 'on' === ( $inner['carouselAutoplay'] ?? 'off' ) ) {
			$options['autoplay'] = array(
				'delay'                => absint( $inner['carouselAutoplayDelay'] ?? 3000 ),
				'pauseOnMouseEnter'    => 'on' === ( $inner['carouselPauseOnHover'] ?? 'on' ),
				'disableOnInteraction' => false,
			);
		}

		return $options;
	}

	/**
	 * Render the arrow + pagination control markup.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $inner Query inner-content values.
	 * @param string               $uid   Per-instance identifier.
	 *
	 * @return string
	 */
	protected static function render_controls( array $inner, string $uid ): string {
		$html = '';

		if ( 'on' === ( $inner['carouselArrows'] ?? 'on' ) ) {
			$html .= sprintf(
				'<div class="swiper-button-prev %1$s-prev"></div><div class="swiper-button-next %1$s-next"></div>',
				esc_attr( $uid )
			);
		}

		if ( 'on' === ( $inner['carouselDots'] ?? 'on' ) ) {
			$html .= sprintf( '<div class="swiper-pagination %1$s-pagination"></div>', esc_attr( $uid ) );
		}

		return $html;
	}

	/**
	 * Render a builder notice block.
	 *
	 * @since 3.4.0
	 *
	 * @param string $message The message.
	 *
	 * @return string
	 */
	protected static function render_notice( string $message ): string {
		return sprintf( '<div class="squad-notice squad-post-carousel-notice">%s</div>', esc_html( $message ) );
	}
}
