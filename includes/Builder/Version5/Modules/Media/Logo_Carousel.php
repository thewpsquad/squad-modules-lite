<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Logo Carousel Module (Divi 5 / Block API).
 *
 * Native Divi 5 parent module. Accepts Logo Carousel Item child blocks,
 * renders them inside a Swiper carousel with configurable hover effects
 * and optional per-logo links. No lightGallery dependency.
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
 * Logo Carousel parent module class.
 *
 * @since 4.0.0
 */
class Logo_Carousel extends Module {

	/**
	 * Relative path to the generated module.json metadata folder.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/logo-carousel/';
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
		$args['classnamesInstance']->add( 'disq_logo_carousel' );
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
	 * Render callback for the Logo Carousel module.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $attrs                 Block attributes.
	 * @param string               $child_modules_content Rendered child HTML.
	 * @param WP_Block             $block                 Parsed block instance.
	 * @param object               $elements              ModuleElements instance.
	 *
	 * @return string Rendered HTML.
	 */
	public static function render_callback( array $attrs, string $child_modules_content, WP_Block $block, $elements ): string {
		try {
			if ( '' === trim( $child_modules_content ) ) {
				return sprintf(
					'<div class="squad-notice">%s</div>',
					esc_html__( 'Add at least one Logo Carousel Item.', 'squad-modules-for-divi' )
				);
			}

			$inner = $attrs['carousel']['innerContent']['desktop']['value'] ?? array();

			wp_enqueue_style( 'squad-vendor-swiper' );
			wp_enqueue_script( 'squad-module-logo-carousel' );

			$uid = self::get_instance_uid( $block );

			$inline_css = self::get_hover_css( $inner, $uid ) . self::get_sizing_css( $inner, $uid );

			$carousel_html = sprintf(
				'%1$s<div class="squad-logo-carousel swiper" data-swiper-options=\'%2$s\'>
					<div class="swiper-wrapper squad-logo-carousel__wrapper">%3$s</div>
					%4$s
				</div>',
				'' !== $inline_css ? sprintf( '<style>%s</style>', $inline_css ) : '',
				esc_attr( (string) wp_json_encode( self::build_swiper_options( $inner, $uid ) ) ),
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
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Logo Carousel module' );

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
	 * @return string e.g. "squad-lc-a1b2c3d4e5"
	 */
	protected static function get_instance_uid( WP_Block $block ): string {
		$raw = (string) ( $block->parsed_block['id'] ?? '' );
		$uid = preg_replace( '/[^a-z0-9]/', '', strtolower( $raw ) );

		return '' !== $uid
			? 'squad-lc-' . $uid
			: 'squad-lc-' . substr( md5( $raw . wp_json_encode( $block->parsed_block['orderIndex'] ?? 0 ) ), 0, 10 );
	}

	/**
	 * Build the Swiper options array.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $inner Carousel innerContent values.
	 * @param string               $uid   Per-instance identifier.
	 *
	 * @return array<string, mixed>
	 */
	protected static function build_swiper_options( array $inner, string $uid ): array {
		$spv_desk = max( 1, absint( $inner['slidesPerView'] ?? 4 ) );
		$spv_tab  = max( 1, absint( $inner['slidesPerViewTablet'] ?? $spv_desk ) );
		$spv_mob  = max( 1, absint( $inner['slidesPerViewPhone'] ?? 2 ) );

		$gap   = max( 0, absint( $inner['spaceBetween'] ?? 30 ) );
		$speed = max( 100, absint( $inner['transitionSpeed'] ?? 500 ) );
		$loop  = 'on' === ( $inner['loop'] ?? 'on' );

		$options = array(
			'slidesPerView' => $spv_mob,
			'spaceBetween'  => $gap,
			'speed'         => $speed,
			'loop'          => $loop,
			'breakpoints'   => array(
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

		if ( 'on' === ( $inner['showDots'] ?? 'off' ) ) {
			$options['pagination'] = array(
				'el'        => ".{$uid}-pagination",
				'clickable' => true,
			);
		}

		if ( 'on' === ( $inner['autoplay'] ?? 'off' ) ) {
			$options['autoplay'] = array(
				'delay'             => max( 500, absint( $inner['autoplaySpeed'] ?? 3000 ) ),
				'pauseOnMouseEnter' => true,
			);
		}

		return $options;
	}

	/**
	 * Render navigation controls (arrows, dots).
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
				'<button class="squad-logo-carousel__arrow squad-logo-carousel__arrow--prev %1$s-prev" aria-label="%2$s"></button>'
				. '<button class="squad-logo-carousel__arrow squad-logo-carousel__arrow--next %1$s-next" aria-label="%3$s"></button>',
				esc_attr( $uid ),
				esc_attr__( 'Previous slide', 'squad-modules-for-divi' ),
				esc_attr__( 'Next slide', 'squad-modules-for-divi' )
			);
		}

		if ( 'on' === ( $inner['showDots'] ?? 'off' ) ) {
			$html .= sprintf(
				'<div class="squad-logo-carousel__pagination %s-pagination"></div>',
				esc_attr( $uid )
			);
		}

		return $html;
	}

	/**
	 * Generate scoped hover-effect CSS for this instance.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $inner Carousel innerContent values.
	 * @param string               $uid   Per-instance identifier.
	 *
	 * @return string Raw CSS (no <style> tags).
	 */
	protected static function get_hover_css( array $inner, string $uid ): string {
		$effect  = (string) ( $inner['hoverEffect'] ?? 'grayscale' );
		$uid_sel = '.' . $uid;
		$logo    = "{$uid_sel} .squad-logo-carousel__logo";
		$hover   = "{$uid_sel} .squad-logo-carousel__slide:hover .squad-logo-carousel__logo";

		switch ( $effect ) {
			case 'grayscale':
				return "{$logo}{filter:grayscale(100%);transition:filter .3s ease}"
					. "{$hover}{filter:grayscale(0%)}";

			case 'opacity':
				$opacity = max( 0.0, min( 1.0, (float) ( $inner['hoverOpacity'] ?? '0.5' ) ) );
				return "{$logo}{opacity:{$opacity};transition:opacity .3s ease}"
					. "{$hover}{opacity:1}";

			case 'zoom':
				return "{$logo}{transition:transform .3s ease}"
					. "{$hover}{transform:scale(1.05)}";

			default:
				return '';
		}
	}

	/**
	 * Generate scoped logo sizing CSS for this instance.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $inner Carousel innerContent values.
	 * @param string               $uid   Per-instance identifier.
	 *
	 * @return string Raw CSS (no <style> tags).
	 */
	protected static function get_sizing_css( array $inner, string $uid ): string {
		$max_width  = self::sanitize_css_length( (string) ( $inner['logoMaxWidth'] ?? '160px' ) );
		$max_height = self::sanitize_css_length( (string) ( $inner['logoMaxHeight'] ?? '80px' ) );

		if ( '' === $max_width && '' === $max_height ) {
			return '';
		}

		$decl = '';
		if ( '' !== $max_width ) {
			$decl .= "max-width:{$max_width};";
		}
		if ( '' !== $max_height ) {
			$decl .= "max-height:{$max_height};";
		}

		return ".{$uid} .squad-logo-carousel__logo{{$decl}}";
	}

	/**
	 * Validate a CSS length value against an allowlist of units.
	 *
	 * @since 4.0.0
	 *
	 * @param string $value Raw value.
	 *
	 * @return string Sanitized value or empty string.
	 */
	private static function sanitize_css_length( string $value ): string {
		$value = trim( $value );
		if ( '' === $value ) {
			return '';
		}
		if ( preg_match( '/^\d+(\.\d+)?(px|em|rem|%|vh|vw|vmin|vmax|ch|ex|cm|mm|pt|pc)$/', $value ) ) {
			return $value;
		}
		return '';
	}
}
