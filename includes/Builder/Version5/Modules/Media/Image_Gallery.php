<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Image Gallery Module (Divi 5 / Block API).
 *
 * Native Divi 5 implementation of the Image Gallery module. Renders the gallery
 * markup server-side via the render callback, producing the exact same wrapper
 * classes and data-attributes as the Divi 4 module so the existing frontend
 * CSS/JS (lightGallery + imagesLoaded) continues to apply.
 *
 * @since   3.4.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version5\Modules\Media;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

// Bail when the Divi 5 framework is not present (e.g. running under Divi 4).
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
use WP_Post;
use function _wp_get_image_size_from_meta;
use function absint;
use function apply_filters;
use function esc_attr;
use function esc_url;
use function get_permalink;
use function get_post_meta;
use function get_posts;
use function implode;
use function wp_enqueue_script;
use function wp_enqueue_style;
use function wp_get_attachment_image_src;
use function wp_get_attachment_metadata;
use function wp_json_encode;

/**
 * Gallery attachment value object.
 *
 * Lightweight typed container for the per-image data the gallery renderer needs.
 * Divi 4 mutated dynamic properties directly onto the {@see WP_Post} objects
 * returned by {@see get_posts()}; the Divi 5 renderer collects the same values
 * into this strongly-typed object so the markup is byte-identical while the
 * property accesses remain statically analysable.
 *
 * @since 3.4.0
 */
class Gallery_Image {

	/**
	 * The attachment ID.
	 *
	 * @since 3.4.0
	 *
	 * @var int
	 */
	public int $ID = 0;

	/**
	 * The attachment excerpt (used as caption/pinterest/tweet text).
	 *
	 * @since 3.4.0
	 *
	 * @var string
	 */
	public string $post_excerpt = '';

	/**
	 * The attachment title.
	 *
	 * @since 3.4.0
	 *
	 * @var string
	 */
	public string $image_title = '';

	/**
	 * The attachment caption.
	 *
	 * @since 3.4.0
	 *
	 * @var string
	 */
	public string $image_caption = '';

	/**
	 * The attachment description.
	 *
	 * @since 3.4.0
	 *
	 * @var string
	 */
	public string $image_description = '';

	/**
	 * The attachment permalink.
	 *
	 * @since 3.4.0
	 *
	 * @var string
	 */
	public string $image_href = '';

	/**
	 * The full-size image URL.
	 *
	 * @since 3.4.0
	 *
	 * @var string
	 */
	public string $image_src_full = '';

	/**
	 * The thumbnail image URL.
	 *
	 * @since 3.4.0
	 *
	 * @var string
	 */
	public string $image_src_thumb = '';

	/**
	 * The image alt text.
	 *
	 * @since 3.4.0
	 *
	 * @var string
	 */
	public string $image_alt_text = '';

	/**
	 * The large (lightGallery) image size string.
	 *
	 * @since 3.4.0
	 *
	 * @var string
	 */
	public string $lg_size = '';
}

/**
 * Image Gallery Module class.
 *
 * @since 3.4.0
 */
class Image_Gallery extends Module {

	/**
	 * Relative path to the generated module.json metadata folder.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/image-gallery/';
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
		$args['classnamesInstance']->add( 'disq_image_gallery' );
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
					// Module.
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
					// Module - Custom CSS.
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
	 * Render callback for the Image Gallery module.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $attrs    Block attributes saved by the Visual Builder.
	 * @param string               $content  Inner (child) block content.
	 * @param WP_Block             $block    Parsed block instance.
	 * @param object               $elements ModuleElements instance.
	 *
	 * @return string Rendered HTML.
	 */
	public static function render_callback( array $attrs, string $content, WP_Block $block, $elements ): string {
		try {
			$inner = $attrs['gallery']['innerContent']['desktop']['value'] ?? array();

			if ( 0 === count( self::normalize_gallery_ids( $inner['galleryIds'] ?? '' ) ) ) {
				return '';
			}

			$show_in_lightbox = $inner['showInLightbox'] ?? 'off';

			// Enqueue scripts and styles (mirrors the Divi 4 module).
			if ( 'on' === $show_in_lightbox ) {
				wp_enqueue_script( 'squad-vendor-images-loaded' );
				wp_enqueue_script( 'squad-vendor-light-gallery' );
				wp_enqueue_style( 'squad-vendor-light-gallery' );
			}

			wp_enqueue_script( 'squad-module-gallery' );

			$gallery_html = self::render_gallery( $inner );

			if ( '' === $gallery_html ) {
				return '';
			}

			$style_components = $elements instanceof ModuleElements
				? (string) $elements->style_components( array( 'attrName' => 'module' ) )
				: '';

			return DiviModule::render(
				array(
					// FE only.
					'orderIndex'          => $block->parsed_block['orderIndex'],
					'storeInstance'       => $block->parsed_block['storeInstance'],

					// VB equivalent.
					'attrs'               => $attrs,
					'elements'            => $elements,
					'id'                  => $block->parsed_block['id'],
					'name'                => $block->block_type->name,
					'moduleCategory'      => $block->block_type->category,
					'classnamesFunction'  => array( self::class, 'module_classnames' ),
					'stylesComponent'     => array( self::class, 'module_styles' ),
					'scriptDataComponent' => array( self::class, 'module_script_data' ),
					'children'            => $style_components . $gallery_html,
				)
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Image Gallery module' );

			return '';
		}
	}

	/**
	 * Build the gallery markup from Divi 5 attributes.
	 *
	 * Produces the same `.gallery-images[data-setting]` wrapper and
	 * `a.gallery-item` markup as the Divi 4 module so the frontend script and
	 * CSS continue to apply.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $inner Gallery inner-content values.
	 *
	 * @return string
	 */
	private static function render_gallery( array $inner ): string {
		$attachments = self::get_gallery( $inner );

		if ( 0 === count( $attachments ) ) {
			return '';
		}

		$show_in_lightbox = $inner['showInLightbox'] ?? 'off';
		$images_quantity  = $inner['imagesQuantity'] ?? 'default';
		$image_count      = absint( $inner['galleryImageCount'] ?? 4 );
		$columns_count    = esc_attr( (string) ( $inner['columnsCount'] ?? '4' ) );
		$images_inner_gap = esc_attr( (string) ( $inner['imagesInnerGap'] ?? '10px' ) );
		$hover_icon       = self::resolve_icon( $inner['hoverIcon'] ?? array() );

		/**
		 * Filter the gallery plugins.
		 *
		 * @since 1.2.0
		 *
		 * @param array $gallery_plugins The gallery plugins.
		 */
		$gallery_plugins = apply_filters( 'divi_squad_module_gallery_plugins', array( 'fullscreen', 'thumbnail' ) );

		// Gallery options (consumed by the frontend lightGallery script).
		$gallery_options = array(
			'speed'            => 500,
			'plugins'          => $gallery_plugins,
			'show_in_lightbox' => $show_in_lightbox,
		);

		$inline_style = sprintf(
			'--squad-module-gallery-columns: %1$s; --squad-module-gallery-gap: %2$s;',
			$columns_count,
			$images_inner_gap
		);

		$items_html = '';
		foreach ( $attachments as $image_index => $attachment ) {
			$items_html .= self::render_gallery_item(
				$attachment,
				$image_index,
				$images_quantity,
				$image_count,
				$show_in_lightbox,
				$hover_icon
			);
		}

		return sprintf(
			'<div class="gallery-images" style="%1$s" data-setting=\'%2$s\'>%3$s</div>',
			esc_attr( $inline_style ),
			esc_attr( (string) wp_json_encode( $gallery_options ) ),
			$items_html
		);
	}

	/**
	 * Render a single gallery item.
	 *
	 * @since 3.4.0
	 *
	 * @param Gallery_Image $attachment       Prepared attachment object.
	 * @param int           $image_index      The image index.
	 * @param string        $images_quantity  Quantity mode (`default` or `custom`).
	 * @param int           $image_count      Count of images to display when custom.
	 * @param string        $show_in_lightbox Whether the lightbox is enabled (`on`/`off`).
	 * @param string        $hover_icon       The overlay hover icon value.
	 *
	 * @return string
	 */
	private static function render_gallery_item( Gallery_Image $attachment, int $image_index, string $images_quantity, int $image_count, string $show_in_lightbox, string $hover_icon ): string {
		$style = ( 'custom' === $images_quantity && $image_count < ( $image_index + 1 ) ) ? 'none' : '';

		$image_html = sprintf(
			'<img class="squad-image" src="%1$s" alt="%2$s" srcset="%3$s %4$s" sizes="%5$s"%6$s />',
			esc_url( $attachment->image_src_thumb ),
			esc_attr( $attachment->image_alt_text ),
			esc_url( $attachment->image_src_full ) . ' 479w, ' . esc_url( $attachment->image_src_thumb ) . ' 480w',
			'',
			esc_attr( '(max-width:479px) 479px, 100vw' ),
			'' !== $style ? ' style="display:' . esc_attr( $style ) . '"' : ''
		);

		$overlay_html = '';
		if ( 'on' === $show_in_lightbox ) {
			$overlay_html = sprintf(
				'<span class="et_overlay%1$s"%2$s></span>',
				'' !== $hover_icon ? ' et_pb_inline_icon' : '',
				'' !== $hover_icon ? ' data-icon="' . esc_attr( $hover_icon ) . '"' : ''
			);
		}

		return sprintf(
			'<a class="gallery-item" title="" href="%1$s" data-lg-size="%3$s" data-pinterest-text="%2$s" data-tweet-text="%2$s" data-src="%1$s" data-sub-html=""><div class="gallery-image">%4$s%5$s</div></a>',
			esc_url( $attachment->image_src_full ),
			esc_attr( $attachment->post_excerpt ),
			esc_attr( $attachment->lg_size ),
			$image_html,
			$overlay_html
		);
	}

	/**
	 * Normalize the gallery image identifiers into a list of attachment IDs.
	 *
	 * The `divi/upload-gallery` field may store its value in several shapes: a
	 * comma-separated string of IDs (`"1,2,3"`), a flat array of IDs
	 * (`array( 1, 2, 3 )`), or an array of attachment objects
	 * (`array( array( 'id' => 1, 'src' => '…' ), … )`). This accepts any of them and
	 * returns a clean list of positive integer attachment IDs.
	 *
	 * @since 3.4.0
	 *
	 * @param mixed $raw The raw `galleryIds` field value.
	 *
	 * @return array<int, int> List of attachment IDs.
	 */
	private static function normalize_gallery_ids( $raw ): array {
		if ( is_array( $raw ) ) {
			$ids = array_map(
				static function ( $item ) {
					if ( is_array( $item ) ) {
						return absint( $item['id'] ?? 0 );
					}

					return absint( $item );
				},
				$raw
			);
		} else {
			$ids = array_map( 'absint', array_map( 'trim', explode( ',', (string) $raw ) ) );
		}

		return array_values(
			array_filter(
				$ids,
				static fn( int $id ): bool => $id > 0
			)
		);
	}

	/**
	 * Get attachment data for the gallery module.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $inner Gallery inner-content values.
	 *
	 * @return array<int, Gallery_Image> Prepared attachment objects.
	 */
	private static function get_gallery( array $inner ): array {
		$attachments = array();

		$gallery_ids = self::normalize_gallery_ids( $inner['galleryIds'] ?? '' );

		if ( 0 === count( $gallery_ids ) ) {
			return $attachments;
		}

		$order_by    = $inner['galleryOrderBy'] ?? 'default';
		$orientation = $inner['orientation'] ?? 'landscape';

		$attachments_args = array(
			'include'        => $gallery_ids,
			'post_status'    => 'inherit',
			'post_type'      => 'attachment',
			'post_mime_type' => 'image',
			'order'          => 'ASC',
			'orderby'        => 'post__in',
		);

		if ( 'rand' === $order_by ) {
			$attachments_args['orderby'] = 'rand';
		}

		$width  = 400;
		$height = ( 'landscape' === $orientation ) ? 284 : 516;

		$_attachments = get_posts( $attachments_args );

		foreach ( $_attachments as $key => $attachment ) {
			// Collect original image url.
			$image_src_full = wp_get_attachment_image_src( $attachment->ID, 'full' );
			$image_src_full = is_array( $image_src_full ) ? (string) array_shift( $image_src_full ) : '';

			// Collect custom image url.
			$image_src_custom = wp_get_attachment_image_src( $attachment->ID, array( $width, $height ) );
			$image_src_custom = is_array( $image_src_custom ) ? (string) array_shift( $image_src_custom ) : '';

			// Collect image sizes.
			$image_meta    = wp_get_attachment_metadata( $attachment->ID );
			$image_size_lg = is_array( $image_meta ) ? _wp_get_image_size_from_meta( 'full', $image_meta ) : false;
			$image_size_lg = is_array( $image_size_lg ) ? implode( '-', $image_size_lg ) : '';

			$image_alt_text = get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true );

			$image = new Gallery_Image();

			$image->ID                = $attachment->ID;
			$image->post_excerpt      = $attachment->post_excerpt;
			$image->image_title       = $attachment->post_title;
			$image->image_caption     = $attachment->post_excerpt;
			$image->image_description = $attachment->post_content;
			$image->image_href        = (string) get_permalink( $attachment );
			$image->image_src_full    = $image_src_full;
			$image->image_src_thumb   = $image_src_custom;
			$image->image_alt_text    = is_string( $image_alt_text ) ? $image_alt_text : '';
			$image->lg_size           = $image_size_lg;

			$attachments[ $key ] = $image;
		}

		return $attachments;
	}
}
