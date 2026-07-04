<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Flip Box Module (Divi 5 / Block API).
 *
 * Native Divi 5 implementation of the Flip Box module. Renders the front/back
 * slide structure server-side via the render callback. Flip-animation styling is
 * layered in by the module styles (see styling increment).
 *
 * @since   3.4.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version5\Modules\Content;

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
use function esc_attr;
use function esc_url;
use function implode;
use function wp_kses_post;

/**
 * Flip Box Module class.
 *
 * @since 3.4.0
 */
class Flip_Box extends Module {

	/**
	 * Relative path to the generated module.json metadata folder.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/flip-box/';
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
		$classnames_instance = $args['classnamesInstance'];
		$attrs               = $args['attrs'];

		$classnames_instance->add( 'disq_flip_box' );
		$classnames_instance->add(
			ElementClassnames::classnames(
				array(
					'attrs' => $attrs['module']['decoration'] ?? array(),
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
		$elements = $args['elements'];

		$elements->script_data(
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
					// Flip — per-instance dynamic settings.
					$elements->style(
						array(
							'attrName'   => 'flip',
							'styleProps' => array(
								'advancedStyles' => array(
									array(
										'componentName' => 'divi/common',
										'props'         => array(
											'selector'            => "{$args['orderClass']} .flip-box",
											'attr'                => $attrs['flip']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												$value  = $params['attrValue'] ?? array();
												$height = self::sanitize_css_length( (string) ( $value['customHeight'] ?? '' ) );

												return ( 'on' === ( $value['customHeightEnable'] ?? '' ) && '' !== $height )
													? "height:{$height};"
													: '';
											},
										),
									),
									array(
										'componentName' => 'divi/common',
										'props'         => array(
											'selector'            => "{$args['orderClass']} .flip-box .flip-box-slides .flip-slide",
											'attr'                => $attrs['flip']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												$value        = $params['attrValue'] ?? array();
												$declarations = '';

												$allowed_h = array( 'flex-start', 'center', 'flex-end', 'space-between', 'space-around' );
												$h_align   = (string) ( $value['horizontalAlignment'] ?? '' );
												if ( '' !== $h_align && in_array( $h_align, $allowed_h, true ) ) {
													$declarations .= 'justify-content: ' . $h_align . ';';
												}
												$allowed_v = array( 'flex-start', 'center', 'flex-end', 'stretch' );
												$v_align   = (string) ( $value['verticalAlignment'] ?? '' );
												if ( '' !== $v_align && in_array( $v_align, $allowed_v, true ) ) {
													$declarations .= 'align-items: ' . $v_align . ';';
												}

												return $declarations;
											},
										),
									),
									array(
										'componentName' => 'divi/common',
										'props'         => array(
											'selector'            => "{$args['orderClass']} .flip-box.flip-3d-content-effect .flip-slide .flip-slide-inner",
											'attr'                => $attrs['flip']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												$value = $params['attrValue'] ?? array();

												if ( 'on' !== ( $value['enable3d'] ?? '' ) ) {
													return '';
												}

												$translate_z = preg_replace( '/[^0-9.]/', '', (string) ( $value['translateZ'] ?? '50' ) );
												$scale       = preg_replace( '/[^0-9.]/', '', (string) ( $value['scale'] ?? '0.9' ) );
												$translate_z = '' !== $translate_z ? $translate_z . 'px' : '50px';
												$scale       = '' !== $scale ? $scale : '0.9';

												return "transform: translateZ($translate_z) scale($scale);";
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
	 * Render callback for the Flip Box module.
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
			$flip_html = self::render_flip_box( $attrs );

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
					'classnamesFunction'  => array( self::class, 'module_classnames' ),
					'stylesComponent'     => array( self::class, 'module_styles' ),
					'scriptDataComponent' => array( self::class, 'module_script_data' ),
					'children'            => $style_components . $flip_html,
				)
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Flip Box module' );

			return '';
		}
	}

	/**
	 * Build the flip box markup from Divi 5 attributes.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $attrs Block attributes.
	 *
	 * @return string
	 */
	private static function render_flip_box( array $attrs ): string {
		$flip         = $attrs['flip']['innerContent']['desktop']['value'] ?? array();
		$front        = $attrs['front']['innerContent']['desktop']['value'] ?? array();
		$back         = $attrs['back']['innerContent']['desktop']['value'] ?? array();
		$front_button = $attrs['frontButton']['innerContent']['desktop']['value'] ?? array();
		$back_button  = $attrs['backButton']['innerContent']['desktop']['value'] ?? array();

		$animation_type = $flip['animationType'] ?? 'rotate';
		$direction      = $flip['direction'] ?? 'right';

		$classes = array( 'flip-box', $animation_type, $direction );

		if ( 'rotate' === $animation_type && 'on' === ( $flip['enable3d'] ?? 'off' ) ) {
			$classes[] = 'flip-3d-content-effect';
		}
		if ( 'slide' === $animation_type && 'on' === ( $flip['moveBothSlides'] ?? 'off' ) ) {
			$classes[] = 'flip-slide-move-both';
		}
		if ( 'on' === ( $flip['swapSlide'] ?? 'off' ) ) {
			$classes[] = 'swap-slide';
		}

		$front_slide = self::render_slide( 'front', $front, $front_button );
		$back_slide  = self::render_slide( 'back', $back, $back_button );

		return sprintf(
			'<div class="%1$s"><div class="flip-box-slides">%2$s%3$s</div></div>',
			esc_attr( implode( ' ', $classes ) ),
			$front_slide,
			$back_slide
		);
	}

	/**
	 * Render a single flip slide (front or back).
	 *
	 * @since 3.4.0
	 *
	 * @param string               $side    Slide side ('front' | 'back').
	 * @param array<string, mixed> $content Slide content values.
	 * @param array<string, mixed> $button  Slide button values.
	 *
	 * @return string
	 */
	private static function render_slide( string $side, array $content, array $button ): string {
		$icon_type = $content['iconType'] ?? 'none';
		$icon_html = '';

		$image_raw = $content['image'] ?? '';
		$image_src = self::resolve_upload_url( $image_raw );
		$image_alt = is_array( $image_raw ) ? ( $image_raw['alt'] ?? $image_raw['titleText'] ?? '' ) : ( $content['imageAlt'] ?? '' );

		if ( 'image' === $icon_type && '' !== $image_src ) {
			$icon_html = sprintf(
				'<div class="squad-icon-wrapper"><div class="slide-icon-element"><img src="%1$s" alt="%2$s" class="slide-icon-image slide-%3$s-icon-image et_pb_image_wrap" /></div></div>',
				esc_url( $image_src ),
				esc_attr( $image_alt ),
				esc_attr( $side )
			);
		} elseif ( 'text' === $icon_type && '' !== ( $content['iconText'] ?? '' ) ) {
			$icon_html = sprintf(
				'<div class="squad-icon-wrapper"><div class="slide-icon-element"><span class="slide-icon-text">%1$s</span></div></div>',
				wp_kses_post( $content['iconText'] )
			);
		} elseif ( 'icon' === $icon_type && '' !== ( $content['icon'] ?? '' ) ) {
			$icon_char = self::resolve_icon( $content['icon'] );
			if ( '' !== $icon_char ) {
				$icon_html = sprintf(
					'<div class="squad-icon-wrapper"><div class="slide-icon-element"><span class="et-pb-icon slide-icon">%1$s</span></div></div>',
					esc_html( $icon_char )
				);
			}
		}

		$elements = '';

		if ( '' !== ( $content['title'] ?? '' ) ) {
			$elements .= sprintf(
				'<div class="slide-element slide-%1$s-element slide-title-wrapper"><h3 class="slide-title-text">%2$s</h3></div>',
				esc_attr( $side ),
				wp_kses_post( $content['title'] )
			);
		}
		if ( '' !== ( $content['subTitle'] ?? '' ) ) {
			$elements .= sprintf(
				'<div class="slide-element slide-%1$s-element slide-subtitle-wrapper"><div class="slide-sub-title-text">%2$s</div></div>',
				esc_attr( $side ),
				wp_kses_post( $content['subTitle'] )
			);
		}
		if ( '' !== ( $content['content'] ?? '' ) ) {
			$elements .= sprintf(
				'<div class="slide-element slide-%1$s-element slide-content-wrapper"><div class="slide-content-text">%2$s</div></div>',
				esc_attr( $side ),
				wp_kses_post( $content['content'] )
			);
		}
		if ( '' !== ( $button['text'] ?? '' ) ) {
			$btn_icon_char = self::resolve_icon( $button['icon'] ?? array() );
			$btn_icon_html = '' !== $btn_icon_char
				? sprintf( '<span class="et-pb-icon squad-button-icon">%s</span>', esc_html( $btn_icon_char ) )
				: '';
			$target        = 'on' === ( $button['urlTarget'] ?? '' ) ? ' target="_blank" rel="noopener noreferrer"' : '';
			$elements     .= sprintf(
				'<div class="slide-element slide-%1$s-element slide-button-wrapper"><a class="squad-button" href="%2$s"%5$s>%3$s%4$s</a></div>',
				esc_attr( $side ),
				esc_url( $button['url'] ?? '#' ),
				wp_kses_post( $button['text'] ),
				$btn_icon_html,
				$target
			);
		}

		return sprintf(
			'<div class="flip-slide %1$s-slide et_pb_with_background"><div class="flip-slide-inner">%2$s<div class="flip-slide-container slide-%1$s-elements-container">%3$s</div></div></div>',
			esc_attr( $side ),
			$icon_html,
			$elements
		);
	}

}
