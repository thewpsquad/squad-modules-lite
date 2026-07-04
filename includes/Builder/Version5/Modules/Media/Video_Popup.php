<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Video Popup Module (Divi 5 / Block API).
 *
 * Provides a magnific-popup powered video popup. The frontend markup,
 * wrapper classes and data-attributes are kept identical to the Divi 4
 * module so the existing frontend CSS/JS (`magnific-popup` +
 * `squad-module-video-popup`) applies unchanged.
 *
 * @since   3.4.0
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
use DiviSquad\Core\Supports\Polyfills\Str;
use ET\Builder\FrontEnd\Module\Style;
use ET\Builder\Packages\Module\Layout\Components\ModuleElements\ModuleElements;
use ET\Builder\Packages\Module\Module as DiviModule;
use ET\Builder\Packages\Module\Options\Css\CssStyle;
use ET\Builder\Packages\Module\Options\Element\ElementClassnames;
use Throwable;
use WP_Block;
use function esc_attr;
use function esc_url;
use function in_array;
use function is_wp_error;
use function sprintf;
use function str_replace;
use function wp_enqueue_script;
use function wp_enqueue_style;
use function wp_kses_post;

/**
 * Video Popup Module class.
 *
 * @since 3.4.0
 */
class Video_Popup extends Module {

	/**
	 * Relative path to the generated module.json metadata folder.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/video-popup/';
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
		$args['classnamesInstance']->add( 'disq_video_popup' );
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
								'disabledOn'     => array(
									'disabledModuleVisibility' => $settings['disabledModuleVisibility'] ?? null,
								),
								'advancedStyles' => array(
									// ── Icon / text alignment ──────────────────────────────
									array(
										'componentName' => 'divi/common',
										'props'         => array(
											'selector'            => "{$args['orderClass']} .video-popup-trigger",
											'attr'                => $attrs['popup']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												$v       = $params['attrValue'] ?? array();
												$align   = (string) ( $v['iconAlignment'] ?? '' );
												$allowed = array( 'flex-start', 'center', 'flex-end' );

												return in_array( $align, $allowed, true ) ? "justify-content:{$align};" : '';
											},
										),
									),
									// ── Spacing between icon and text ──────────────────────
									array(
										'componentName' => 'divi/common',
										'props'         => array(
											'selector'            => "{$args['orderClass']} .video-popup .video-popup-icon",
											'attr'                => $attrs['popup']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												$v       = $params['attrValue'] ?? array();
												$spacing = trim( (string) ( $v['iconSpacing'] ?? '' ) );

												return 1 === preg_match( '/^\d+(\.\d+)?(px|em|rem|%)$/', $spacing ) ? "margin-right:{$spacing};" : '';
											},
										),
									),
									// ── Animated wave behind the icon ──────────────────────
									array(
										'componentName' => 'divi/common',
										'props'         => array(
											'selector'            => "{$args['orderClass']} .video-popup .video-popup-icon:after",
											'attr'                => $attrs['popup']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												$v = $params['attrValue'] ?? array();
												if ( 'on' !== ( $v['useAnimation'] ?? 'off' ) ) {
													return '';
												}
												$bg = self::sanitize_css_background( (string) ( $v['waveBg'] ?? '' ) );
												if ( '' === $bg ) {
													return '';
												}

												return sprintf(
													'content:"";box-shadow:0 0 0 15px %1$s,0 0 0 30px %1$s,0 0 0 45px %1$s;',
													$bg
												);
											},
										),
									),
								),
							),
						)
					),
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
	 * Render callback for the Video Popup module.
	 *
	 * Outputs the same wrapper classes and data-attributes as the Divi 4
	 * module so the existing `magnific-popup` styles and the
	 * `squad-module-video-popup` frontend script work without changes.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $attrs    Block attributes.
	 * @param string               $content  Inner content.
	 * @param WP_Block             $block    Parsed block instance.
	 * @param ModuleElements       $elements ModuleElements instance.
	 *
	 * @return string Rendered HTML.
	 */
	public static function render_callback( array $attrs, string $content, WP_Block $block, $elements ): string {
		try {
			$inner = $attrs['popup']['innerContent']['desktop']['value'] ?? array();

			$use_overlay = $inner['useOverlay'] ?? 'off';
			$image       = self::resolve_upload_url( $inner['image'] ?? '' );
			$image_alt   = $inner['alt'] ?? '';
			$type        = $inner['type'] ?? 'yt';
			$video_link  = $inner['videoLink'] ?? '';
			$video       = self::resolve_upload_url( $inner['video'] ?? '' );

			// Enqueue the frontend dependencies (magnific-popup + module script).
			wp_enqueue_style( 'magnific-popup' );
			wp_enqueue_script( 'squad-module-video-popup' );

			// Order number used to key the inline modal markup.
			$order_number = $block->parsed_block['orderIndex'] ?? 0;
			$data_modal   = 'video' === $type
				? sprintf( 'data-mfp-src="#squad-vp-modal-video-popup-%1$s"', esc_attr( (string) $order_number ) )
				: '';

			// Inline modal markup for locally uploaded videos.
			$inline_modal = '';
			if ( 'video' === $type ) {
				$inline_modal = sprintf(
					'<div class="mfp-hide squad-vp-modal" id="squad-vp-modal-video-popup-%1$s" data-order="%1$s"><div class="video-wrap"><video controls><source type="video/mp4" src="%2$s"></video></div></div>',
					esc_attr( (string) $order_number ),
					esc_url( $video )
				);
			}

			// Optional overlay image figure.
			$img_overlay = '';
			if ( 'on' === $use_overlay ) {
				$img_overlay = sprintf(
					'<div class="video-popup-figure"><img src="%1$s" alt="%2$s"/></div>',
					esc_url( $image ),
					esc_attr( $image_alt )
				);
			}

			// Normalize youtu.be short links.
			if ( Str::contains( $video_link, 'youtu.be' ) ) {
				$video_link = str_replace( 'youtu.be/', 'youtube.com/watch?v=', $video_link );
			}

			$html = sprintf(
				'<div class="video-popup"> %5$s <div class="video-popup-wrap"> <a class="video-popup-trigger popup-%6$s" data-order="%4$s" data-type="%6$s" href="%3$s" %7$s>%1$s</a></div>%2$s</div>',
				self::render_trigger( $inner ),
				$img_overlay,
				esc_url( $video_link ),
				esc_attr( (string) $order_number ),
				$inline_modal,
				esc_attr( $type ),
				$data_modal
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
					'classnamesFunction'  => array( self::class, 'module_classnames' ),
					'stylesComponent'     => array( self::class, 'module_styles' ),
					'scriptDataComponent' => array( self::class, 'module_script_data' ),
					'children'            => $elements->style_components( array( 'attrName' => 'module' ) ) . $html,
				)
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Video Popup module' );

			return '';
		}
	}

	/**
	 * Generate the trigger (icon and/or text) markup.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $inner Inner content values.
	 *
	 * @return string
	 */
	protected static function render_trigger( array $inner ): string {
		$icon_output_html = '';
		$trigger_element  = $inner['triggerElement'] ?? 'icon';

		// Generate the SVG icon.
		if ( in_array( $trigger_element, array( 'icon', 'icon_text' ), true ) ) {
			$image = divi_squad()->load_image( '/build/admin/images/icons' );

			if ( $image->is_path_validated() ) {
				$images = array(
					'1' => 'arrow-outline.svg',
					'2' => 'arrow-filled.svg',
					'3' => 'arrow-triangle.svg',
					'4' => 'arrow-circle.svg',
					'5' => 'arrow-circle-triangle.svg',
					'6' => 'arrow-rectangle-round.svg',
				);

				$svg_image_id  = (string) ( $inner['icon'] ?? '1' );
				$svg_image_id  = isset( $images[ $svg_image_id ] ) ? $svg_image_id : '1';
				$svg_image_raw = $image->get_image( $images[ $svg_image_id ], 'svg', false );

				if ( ! is_wp_error( $svg_image_raw ) ) {
					$icon_output_html = sprintf( '<span class="video-popup-icon">%1$s</span>', $svg_image_raw );
				}
			}
		}

		// Generate the text.
		if ( in_array( $trigger_element, array( 'text', 'icon_text' ), true ) ) {
			$icon_output_html .= sprintf(
				'<span class="video-popup-text">%1$s</span>',
				wp_kses_post( $inner['text'] ?? 'Play' )
			);
		}

		return $icon_output_html;
	}
}
