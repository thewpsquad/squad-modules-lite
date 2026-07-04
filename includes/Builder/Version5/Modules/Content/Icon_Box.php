<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Icon Box Module (Divi 5 / Block API).
 *
 * A lightweight blurb: an icon, image or Lottie animation paired with a title,
 * content, optional badge and an optional whole-box link.
 *
 * @since   4.2.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version5\Modules\Content;

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
use function esc_url;
use function in_array;
use function preg_replace;
use function substr;
use function wp_enqueue_script;
use function wp_kses_post;
use function wpautop;

/**
 * Icon Box module class.
 *
 * @since 4.2.0
 */
class Icon_Box extends Module {

	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/icon-box/';
	}

	/**
	 * Add module-specific classnames.
	 *
	 * @param array<string, mixed> $args Classnames arguments.
	 *
	 * @return void
	 */
	public static function module_classnames( array $args ): void {
		$args['classnamesInstance']->add( 'disq_icon_box' );
		$args['classnamesInstance']->add(
			ElementClassnames::classnames(
				array( 'attrs' => $args['attrs']['module']['decoration'] ?? array() )
			)
		);
	}

	/**
	 * Set module script data.
	 *
	 * @param array<string, mixed> $args Script data arguments.
	 *
	 * @return void
	 */
	public static function module_script_data( array $args ): void {
		$args['elements']->script_data( array( 'attrName' => 'module' ) );
	}

	/**
	 * Add module styles.
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
					CssStyle::style(
						array( 'selector' => $args['orderClass'], 'attr' => $attrs['css'] ?? array() )
					),
				),
			)
		);
	}

	/**
	 * Render the Icon Box module on the frontend.
	 *
	 * @param array<string, mixed> $attrs    Block attributes.
	 * @param string               $content  Inner content (unused).
	 * @param WP_Block             $block    Parsed block instance.
	 * @param ModuleElements       $elements ModuleElements instance.
	 *
	 * @return string Rendered HTML.
	 */
	public static function render_callback( array $attrs, string $content, WP_Block $block, $elements ): string {
		try {
			$inner = $attrs['iconBox']['innerContent']['desktop']['value'] ?? array();
			$uid   = self::get_instance_uid( $block );

			$placement = (string) ( $inner['iconPlacement'] ?? 'top' );
			$placement = in_array( $placement, array( 'top', 'left', 'right' ), true ) ? $placement : 'top';
			$alignment = (string) ( $inner['alignment'] ?? 'center' );
			$alignment = in_array( $alignment, array( 'left', 'center', 'right' ), true ) ? $alignment : 'center';

			$element_html = self::build_element( $inner );

			$title = (string) ( $inner['title'] ?? '' );
			$level = (string) ( $inner['titleLevel'] ?? 'h3' );
			$level = in_array( $level, array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ), true ) ? $level : 'h3';

			$title_html = '' !== $title ? sprintf( '<%1$s class="squad-icon-box__title">%2$s</%1$s>', $level, esc_html( $title ) ) : '';
			$body       = (string) ( $inner['content'] ?? '' );
			$body_html  = '' !== $body ? sprintf( '<div class="squad-icon-box__text">%s</div>', wpautop( wp_kses_post( $body ) ) ) : '';

			$badge_html = '';
			if ( 'off' !== ( $inner['useBadge'] ?? 'off' ) ) {
				$badge      = (string) ( $inner['badgeText'] ?? '' );
				$badge_html = '' !== $badge ? sprintf( '<span class="squad-icon-box__badge">%s</span>', esc_html( $badge ) ) : '';
			}

			$icon_wrap    = '' !== $element_html ? sprintf( '<div class="squad-icon-box__icon">%s</div>', $element_html ) : '';
			$content_wrap = sprintf( '<div class="squad-icon-box__content">%1$s%2$s%3$s</div>', $badge_html, $title_html, $body_html );

			$inline_css = self::get_box_css( $inner, $uid );

			$box = sprintf(
				'%1$s<div class="squad-icon-box squad-icon-box--placement-%2$s squad-icon-box--align-%3$s %4$s">%5$s%6$s</div>',
				'' !== $inline_css ? sprintf( '<style>%s</style>', $inline_css ) : '',
				esc_attr( $placement ),
				esc_attr( $alignment ),
				esc_attr( $uid ),
				$icon_wrap,
				$content_wrap
			);

			$link_url = (string) ( $inner['linkUrl'] ?? '' );
			if ( '' !== $link_url ) {
				$new_tab = 'on' === ( $inner['linkTarget'] ?? 'off' );
				$box     = sprintf(
					'<a class="squad-icon-box__link" href="%1$s"%2$s>%3$s</a>',
					esc_url( $link_url ),
					$new_tab ? ' target="_blank" rel="noopener noreferrer"' : '',
					$box
				);
			}

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
					'children'            => $style_components . $box,
				)
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Icon Box module' );

			return '';
		}
	}

	/**
	 * Build the icon / image / lottie element markup.
	 *
	 * @since 4.2.0
	 *
	 * @param array<string, mixed> $inner The module's inner content values.
	 *
	 * @return string
	 */
	protected static function build_element( array $inner ): string {
		$type = (string) ( $inner['elementType'] ?? 'icon' );

		if ( 'image' === $type ) {
			$image = (string) ( $inner['image'] ?? '' );
			if ( '' === $image ) {
				return '';
			}

			return sprintf(
				'<span class="squad-icon-box__image"><img src="%1$s" alt="%2$s" loading="lazy" /></span>',
				esc_url( $image ),
				esc_attr( (string) ( $inner['imageAlt'] ?? '' ) )
			);
		}

		if ( 'lottie' === $type ) {
			$src = (string) ( $inner['lottieSrc'] ?? '' );
			if ( '' === $src ) {
				return '';
			}

			wp_enqueue_script( 'squad-module-icon-box' );
			$loop = 'off' !== ( $inner['lottieLoop'] ?? 'on' );

			return sprintf(
				'<span class="squad-icon-box__lottie"><div class="squad-lottie-player lottie-player-container" data-src="%1$s" data-loop="%2$s" data-autoplay="true"></div></span>',
				esc_url( $src ),
				$loop ? 'true' : 'false'
			);
		}

		if ( 'icon' === $type ) {
			$icon_char = self::resolve_icon( $inner['icon'] ?? array() );

			return '' !== $icon_char ? sprintf( '<span class="et-pb-icon">%s</span>', esc_html( $icon_char ) ) : '';
		}

		return '';
	}

	protected static function get_instance_uid( WP_Block $block ): string {
		$raw = (string) ( $block->parsed_block['id'] ?? '' );
		$uid = preg_replace( '/[^a-z0-9]/', '', strtolower( $raw ) );

		return ( null !== $uid && '' !== $uid )
			? 'squad-ib-' . $uid
			: 'squad-ib-' . substr( md5( $raw ), 0, 10 );
	}

	/**
	 * Build the scoped icon color/size/background CSS.
	 *
	 * @since 4.2.0
	 *
	 * @param array<string, mixed> $inner Icon box inner values.
	 * @param string               $uid   Unique scoping class for this instance.
	 *
	 * @return string Scoped CSS (may be empty).
	 */
	protected static function get_box_css( array $inner, string $uid ): string {
		$css = '';

		if ( 'icon' === ( $inner['elementType'] ?? 'icon' ) ) {
			$icon_color = self::sanitize_css_background( (string) ( $inner['iconColor'] ?? '#5E2EFF' ) );
			$icon_size  = self::sanitize_css_length( (string) ( $inner['iconSize'] ?? '48px' ) );

			$decl = '';
			if ( '' !== $icon_color ) {
				$decl .= 'color:' . esc_attr( $icon_color ) . ';';
			}
			if ( '' !== $icon_size ) {
				$decl .= 'font-size:' . $icon_size . ';line-height:' . $icon_size . ';';
			}
			if ( '' !== $decl ) {
				$css .= ".{$uid} .squad-icon-box__icon .et-pb-icon{{$decl}}";
			}
		}

		$icon_bg = self::sanitize_css_background( (string) ( $inner['iconBgColor'] ?? '' ) );
		if ( '' !== $icon_bg ) {
			$css .= ".{$uid} .squad-icon-box__icon{background:" . esc_attr( $icon_bg ) . ';}';
		}

		return $css;
	}
}
