<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Advanced Video Module (Divi 5 / Block API).
 *
 * Renders the same `.squad-video` shell as the Divi 4 module; lightbox + sticky
 * behaviour is driven by `advanced-video.ts`.
 *
 * @since   4.1.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version5\Modules\Creative;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

if ( ! class_exists( 'ET\Builder\Packages\Module\Module' ) ) {
	return;
}

use DiviSquad\Builder\Shared\Modules\Creative\Advanced_Video\Video_Helper;
use DiviSquad\Builder\Version5\Abstracts\Module;
use ET\Builder\FrontEnd\Module\Style;
use ET\Builder\Packages\Module\Layout\Components\ModuleElements\ModuleElements;
use ET\Builder\Packages\Module\Module as DiviModule;
use ET\Builder\Packages\Module\Options\Css\CssStyle;
use ET\Builder\Packages\Module\Options\Element\ElementClassnames;
use Throwable;
use WP_Block;
use function absint;
use function sprintf;
use function trim;
use function wp_enqueue_script;
use function wp_enqueue_style;

/**
 * Advanced Video module class (Divi 5).
 *
 * @since 4.1.0
 */
class Advanced_Video extends Module {

	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/advanced-video/';
	}

	/**
	 * Add the module classnames.
	 *
	 * @since 4.1.0
	 *
	 * @param array<string, mixed> $args Classnames arguments.
	 *
	 * @return void
	 */
	public static function module_classnames( array $args ): void {
		$args['classnamesInstance']->add( 'disq_advanced_video' );
		$args['classnamesInstance']->add(
			ElementClassnames::classnames(
				array( 'attrs' => $args['attrs']['module']['decoration'] ?? array() )
			)
		);
	}

	/**
	 * Assign the module's frontend script data.
	 *
	 * @since 4.1.0
	 *
	 * @param array<string, mixed> $args Script data arguments.
	 *
	 * @return void
	 */
	public static function module_script_data( array $args ): void {
		$args['elements']->script_data( array( 'attrName' => 'module' ) );
	}

	/**
	 * Register the module style declarations.
	 *
	 * @since 4.1.0
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
									// ── aspect ratio (videoSettings) ──────────────────────────
									array(
										'componentName' => 'divi/common',
										'props'         => array(
											'selector'            => "{$args['orderClass']} .squad-video__frame",
											'attr'                => $attrs['videoSettings']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												$v      = $params['attrValue'] ?? array();
												$aspect = (string) ( $v['aspectRatio'] ?? '16-9' );

												return sprintf( 'aspect-ratio:%s;', Video_Helper::aspect_value( $aspect ) );
											},
										),
									),
									// ── play size + sticky width (videoSettings) ──────────────
									array(
										'componentName' => 'divi/common',
										'props'         => array(
											'selector'            => "{$args['orderClass']} .squad-video",
											'attr'                => $attrs['videoSettings']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												$v     = $params['attrValue'] ?? array();
												$width = absint( $v['stickyWidth'] ?? 400 );

												return sprintf( '--squad-av-sticky-width:%dpx;', $width );
											},
										),
									),
									// ── play/overlay/frame colors + size (videoStyle) ─────────
									array(
										'componentName' => 'divi/common',
										'props'         => array(
											'selector'            => "{$args['orderClass']} .squad-video",
											'attr'                => $attrs['videoStyle']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												$v    = $params['attrValue'] ?? array();
												$size = absint( $v['playSize'] ?? 68 );
												$decl = sprintf( '--squad-av-play-size:%dpx;', $size );
												$pc   = self::sanitize_css_background( (string) ( $v['playColor'] ?? '' ) );
												$pb   = self::sanitize_css_background( (string) ( $v['playBg'] ?? '' ) );
												$ov   = self::sanitize_css_background( (string) ( $v['overlayColor'] ?? '' ) );
												$fb   = self::sanitize_css_background( (string) ( $v['frameBg'] ?? '' ) );
												if ( '' !== $pc ) {
													$decl .= "--squad-av-play-color:{$pc};";
												}
												if ( '' !== $pb ) {
													$decl .= "--squad-av-play-bg:{$pb};";
												}
												if ( '' !== $ov ) {
													$decl .= "--squad-av-overlay:{$ov};";
												}
												if ( '' !== $fb ) {
													$decl .= "--squad-av-frame-bg:{$fb};";
												}
												$shadow = 'off' === (string) ( $v['stickyShadow'] ?? 'on' ) ? 'none' : '0 10px 30px rgba(0,0,0,0.35)';
												$decl   .= "--squad-av-sticky-shadow:{$shadow};";

												return $decl;
											},
										),
									),
								),
							),
						)
					),
					CssStyle::style(
						array( 'selector' => $args['orderClass'], 'attr' => $attrs['css'] ?? array() )
					),
				),
			)
		);
	}

	/**
	 * Render callback (Divi 5). Reads camelCase attrs, maps to the snake_case
	 * config resolve_config() expects, and calls the shared V4 build_shell().
	 *
	 * @since 4.1.0
	 *
	 * @param array<string, mixed> $attrs    Block attributes.
	 * @param string               $content  Inner content.
	 * @param WP_Block             $block    Parsed block.
	 * @param object               $elements ModuleElements instance.
	 *
	 * @return string
	 */
	public static function render_callback( array $attrs, string $content, WP_Block $block, $elements ): string {
		try {
			$inner = $attrs['videoSettings']['innerContent']['desktop']['value'] ?? array();

			$config = Video_Helper::resolve_config(
				array(
					'source'          => (string) ( $inner['source'] ?? 'youtube' ),
					'video_url'       => (string) ( $inner['videoUrl'] ?? '' ),
					'video_file'      => (string) ( $inner['videoFile'] ?? '' ),
					'display'         => (string) ( $inner['display'] ?? 'inline' ),
					'aspect_ratio'    => (string) ( $inner['aspectRatio'] ?? '16-9' ),
					'use_poster'      => (string) ( $inner['usePoster'] ?? 'on' ),
					'poster_image'    => (string) ( $inner['posterImage'] ?? '' ),
					'poster_alt'      => (string) ( $inner['posterAlt'] ?? '' ),
					'play_icon'       => (string) ( $inner['playIcon'] ?? 'circle' ),
					'autoplay'        => (string) ( $inner['autoplay'] ?? 'off' ),
					'muted'           => (string) ( $inner['muted'] ?? 'off' ),
					'loop'            => (string) ( $inner['loop'] ?? 'off' ),
					'controls'        => (string) ( $inner['controls'] ?? 'on' ),
					'start_time'      => $inner['startTime'] ?? 0,
					'end_time'        => $inner['endTime'] ?? 0,
					'sticky'          => (string) ( $inner['sticky'] ?? 'off' ),
					'sticky_position' => (string) ( $inner['stickyPosition'] ?? 'bottom-right' ),
					'sticky_mobile'   => (string) ( $inner['stickyMobile'] ?? 'off' ),
				)
			);

			if ( '' === $config['embed'] && '' === $config['file'] ) {
				return '';
			}

			wp_enqueue_style( 'magnific-popup' );
			wp_enqueue_script( 'squad-module-advanced-video' );

			$shell = Video_Helper::build_shell( $config );

			$style_components = $elements instanceof ModuleElements
				? (string) $elements->style_components( array( 'attrName' => 'module' ) )
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
					'children'            => $style_components . $shell,
				)
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Advanced Video module' );

			return '';
		}
	}
}
