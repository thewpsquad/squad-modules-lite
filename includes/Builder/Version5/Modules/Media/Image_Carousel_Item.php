<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Image Carousel Item (child) Module (Divi 5 / Block API).
 *
 * Native Divi 5 child module for Image Carousel. Renders a single slide with
 * image, optional overlay, caption, and button. The outer DiviModule::render
 * wrapper receives `swiper-slide` + `squad-image-carousel__slide` via
 * module_classnames() so it becomes the Swiper slide element directly.
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
use function esc_attr;
use function esc_html;
use function esc_html__;
use function esc_url;

/**
 * Image Carousel Item (child) module class.
 *
 * @since 4.0.0
 */
class Image_Carousel_Item extends Module {

	/**
	 * Relative path to the generated module.json metadata folder.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/image-carousel-item/';
	}

	/**
	 * Add the module classnames.
	 *
	 * Adds `swiper-slide` and `squad-image-carousel__slide` so the DiviModule::render
	 * outer wrapper becomes the Swiper slide element directly.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $args Classnames arguments.
	 *
	 * @return void
	 */
	public static function module_classnames( array $args ): void {
		$args['classnamesInstance']->add( 'disq_image_carousel_item' );
		$args['classnamesInstance']->add( 'swiper-slide' );
		$args['classnamesInstance']->add( 'squad-image-carousel__slide' );
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
					$elements->style( array( 'attrName' => 'image' ) ),
					$elements->style( array( 'attrName' => 'overlay' ) ),
					$elements->style( array( 'attrName' => 'caption' ) ),
					$elements->style( array( 'attrName' => 'button' ) ),
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
	 * Render callback for the Image Carousel Item (child) module.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $attrs    Block attributes.
	 * @param string               $content  Inner content.
	 * @param WP_Block             $block    Parsed block instance.
	 * @param object               $elements ModuleElements instance.
	 *
	 * @return string Rendered HTML.
	 */
	public static function render_callback( array $attrs, string $content, WP_Block $block, $elements ): string {
		try {
			$item_attrs = $attrs['slideItem']['innerContent']['desktop']['value'] ?? array();
			$image_url  = esc_url( self::resolve_upload_url( $item_attrs['image'] ?? '' ) );

			$style_components = $elements instanceof ModuleElements
				? (string) $elements->style_components( array( 'attrName' => 'module' ) )
				: '';

			$item_html = sprintf(
				'<div class="squad-image-carousel__item"%s>%s%s</div>',
				'' !== $image_url ? sprintf( ' data-src="%s"', $image_url ) : '',
				self::render_image( $attrs ),
				self::render_overlay( $attrs )
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
					'children'            => $style_components . $item_html,
				)
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Image Carousel Item module' );

			return '';
		}
	}

	/**
	 * Render the slide image element.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $attrs Block attributes.
	 *
	 * @return string
	 */
	private static function render_image( array $attrs ): string {
		$item = $attrs['slideItem']['innerContent']['desktop']['value'] ?? array();
		$url  = esc_url( self::resolve_upload_url( $item['image'] ?? '' ) );

		if ( '' === $url ) {
			return '';
		}

		return sprintf(
			'<img class="squad-image-carousel__image" src="%1$s" alt="%2$s" loading="lazy" />',
			$url,
			esc_attr( $item['imageAlt'] ?? '' )
		);
	}

	/**
	 * Render the overlay, caption, and button.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $attrs Block attributes.
	 *
	 * @return string
	 */
	private static function render_overlay( array $attrs ): string {
		$item         = $attrs['slideItem']['innerContent']['desktop']['value'] ?? array();
		$show_overlay = 'on' === ( $item['showOverlay'] ?? 'off' );
		$show_caption = 'on' === ( $item['showCaption'] ?? 'off' );
		$show_button  = 'on' === ( $item['showButton'] ?? 'off' );

		if ( ! $show_overlay && ! $show_caption && ! $show_button ) {
			return '';
		}

		$caption_html = '';
		if ( $show_caption && '' !== ( $item['captionText'] ?? '' ) ) {
			$caption_html = sprintf(
				'<div class="squad-image-carousel__caption">%s</div>',
				esc_html( $item['captionText'] )
			);
		}

		$button_html = '';
		if ( $show_button ) {
			$text   = $item['buttonText'] ?? esc_html__( 'Learn More', 'squad-modules-for-divi' );
			$url    = esc_url( $item['buttonUrl'] ?? '#' );
			$target = $item['buttonTarget'] ?? '_self';
			$target = in_array( $target, array( '_self', '_blank' ), true ) ? $target : '_self';

			$button_html = sprintf(
				'<a class="squad-image-carousel__button" href="%1$s" target="%2$s">%3$s</a>',
				$url,
				esc_attr( $target ),
				esc_html( $text )
			);
		}

		return sprintf(
			'<div class="squad-image-carousel__overlay">%s%s</div>',
			$caption_html,
			$button_html
		);
	}
}
