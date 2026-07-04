<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Social Share Module (Divi 5 / Block API).
 *
 * Parent module owning the share target, layout, header and global button style.
 *
 * @since   4.0.0
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
use ET\Builder\Packages\Module\Module as DiviModule;
use ET\Builder\Packages\Module\Options\Css\CssStyle;
use ET\Builder\Packages\Module\Options\Element\ElementClassnames;
use Throwable;
use WP_Block;
use function esc_attr;
use function esc_html;
use function esc_html__;
use function esc_url_raw;
use function get_bloginfo;
use function get_permalink;
use function get_the_excerpt;
use function get_the_title;
use function home_url;
use function in_array;
use function is_singular;
use function sanitize_text_field;
use function sanitize_textarea_field;
use function wp_enqueue_script;

/**
 * Social Share parent module class.
 *
 * @since 4.0.0
 */
class Social_Share extends Module {

	/**
	 * Resolved share target shared with child render passes.
	 *
	 * @var array{url: string, title: string, desc: string}
	 */
	public static $share_target = array( 'url' => '', 'title' => '', 'desc' => '' );

	/**
	 * Resolved button context shared with child render passes.
	 *
	 * @var array<string, string>
	 */
	public static $button_context = array( 'style' => 'icon', 'enable_popup' => 'on' );

	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/social-share/';
	}

	public static function module_classnames( array $args ): void {
		$args['classnamesInstance']->add( 'disq_social_share' );
		$args['classnamesInstance']->add(
			ElementClassnames::classnames(
				array( 'attrs' => $args['attrs']['module']['decoration'] ?? array() )
			)
		);
	}

	public static function module_script_data( array $args ): void {
		$args['elements']->script_data( array( 'attrName' => 'module' ) );
	}

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

	public static function render_callback( array $attrs, string $child_modules_content, WP_Block $block, $elements ): string {
		try {
			if ( '' === trim( $child_modules_content ) ) {
				return sprintf(
					'<div class="squad-notice">%s</div>',
					esc_html__( 'Add at least one Social Share Item.', 'squad-modules-for-divi' )
				);
			}

			$inner = $attrs['shareSettings']['innerContent']['desktop']['value'] ?? array();

			$enable_popup = 'off' === ( $inner['enablePopup'] ?? 'on' ) ? 'off' : 'on';
			$style        = 'icon_text' === ( $inner['buttonStyle'] ?? 'icon' ) ? 'icon_text' : 'icon';

			if ( 'on' === $enable_popup ) {
				wp_enqueue_script( 'squad-module-social-share' );
			}

			self::$share_target   = self::resolve_share_target( $inner );
			self::$button_context = array( 'style' => $style, 'enable_popup' => $enable_popup );

			$uid = self::get_instance_uid( $block );

			$orientation = 'stacked' === ( $inner['orientation'] ?? 'inline' ) ? 'stacked' : 'inline';
			$shape       = (string) ( $inner['buttonShape'] ?? 'rounded' );
			$shape       = in_array( $shape, array( 'square', 'rounded', 'circle' ), true ) ? $shape : 'rounded';
			$hover       = (string) ( $inner['hoverEffect'] ?? 'fill' );
			$hover       = in_array( $hover, array( 'none', 'fill', 'lift', 'scale' ), true ) ? $hover : 'fill';

			$header_html = '';
			if ( 'on' === ( $inner['headerShow'] ?? 'off' ) ) {
				$title    = sanitize_text_field( (string) ( $inner['headerTitle'] ?? '' ) );
				$subtitle = sanitize_text_field( (string) ( $inner['headerSubtitle'] ?? '' ) );
				$head     = '';
				if ( '' !== $title ) {
					$head .= sprintf( '<h3 class="squad-social-share__header-title">%s</h3>', esc_html( $title ) );
				}
				if ( '' !== $subtitle ) {
					$head .= sprintf( '<p class="squad-social-share__header-subtitle">%s</p>', esc_html( $subtitle ) );
				}
				if ( '' !== $head ) {
					$header_html = sprintf( '<div class="squad-social-share__header">%s</div>', $head );
				}
			}

			$inline_css = self::get_layout_css( $inner, $uid );

			$wrapper_html = sprintf(
				'%1$s<div class="squad-social-share squad-social-share--%2$s squad-social-share--shape-%3$s squad-social-share--hover-%4$s squad-social-share--style-%5$s">%6$s<div class="squad-social-share__list">%7$s</div></div>',
				'' !== $inline_css ? sprintf( '<style>%s</style>', $inline_css ) : '',
				esc_attr( $orientation ),
				esc_attr( $shape ),
				esc_attr( $hover ),
				esc_attr( $style ),
				$header_html,
				$child_modules_content
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
					'children'            => $elements->style_components( array( 'attrName' => 'module' ) ) . $wrapper_html,
				)
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Social Share module' );

			return '';
		}
	}

	/**
	 * Resolve the share target from the parent inner-content attrs.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $inner Parent inner content.
	 *
	 * @return array{url: string, title: string, desc: string}
	 */
	public static function resolve_share_target( array $inner ): array {
		$source = 'custom' === ( $inner['shareSource'] ?? 'current' ) ? 'custom' : 'current';

		$current_url   = is_singular() ? (string) get_permalink() : home_url( '/' );
		$current_title = is_singular() ? (string) get_the_title() : (string) get_bloginfo( 'name' );
		$current_desc  = is_singular() ? (string) get_the_excerpt() : '';

		if ( 'custom' === $source ) {
			$url   = esc_url_raw( (string) ( $inner['customUrl'] ?? '' ) );
			$title = sanitize_text_field( (string) ( $inner['customTitle'] ?? '' ) );
			$desc  = sanitize_textarea_field( (string) ( $inner['customDesc'] ?? '' ) );

			if ( '' === $url ) {
				$url = esc_url_raw( $current_url );
			}
			if ( '' === $title ) {
				$title = $current_title;
			}
			if ( '' === $desc ) {
				$desc = $current_desc;
			}

			return array( 'url' => $url, 'title' => $title, 'desc' => $desc );
		}

		return array(
			'url'   => esc_url_raw( $current_url ),
			'title' => $current_title,
			'desc'  => $current_desc,
		);
	}

	protected static function get_instance_uid( WP_Block $block ): string {
		$raw = (string) ( $block->parsed_block['id'] ?? '' );
		$uid = preg_replace( '/[^a-z0-9]/', '', strtolower( $raw ) );

		return '' !== $uid
			? 'squad-ss-' . $uid
			: 'squad-ss-' . substr( md5( $raw ), 0, 10 );
	}

	protected static function get_layout_css( array $inner, string $uid ): string {
		$gap     = self::sanitize_css_length( (string) ( $inner['itemGap'] ?? '10px' ) );
		$columns = max( 1, min( 8, (int) ( $inner['columns'] ?? 4 ) ) );

		$css = ".{$uid} .squad-social-share--inline .squad-social-share__list{grid-template-columns:repeat({$columns},minmax(0,max-content))}";
		if ( '' !== $gap ) {
			$css .= ".{$uid} .squad-social-share__list{gap:{$gap}}";
		}

		// icon_size → font-size on .squad-social-share__icon.
		$icon_size = self::sanitize_css_length( (string) ( $inner['iconSize'] ?? '18px' ) );
		if ( '' !== $icon_size ) {
			$css .= ".{$uid} .squad-social-share__icon{font-size:{$icon_size}}";
		}

		// icon_color → color on .squad-social-share__btn.
		$icon_color = self::sanitize_css_background( (string) ( $inner['iconColor'] ?? '#ffffff' ) );
		if ( '' !== $icon_color ) {
			$css .= ".{$uid} .squad-social-share__btn{color:{$icon_color}}";
		}

		// button_bg → background-color on .squad-social-share__btn (only when non-empty; child brand color is the default).
		$button_bg = self::sanitize_css_background( (string) ( $inner['buttonBg'] ?? '' ) );
		if ( '' !== $button_bg ) {
			$css .= ".{$uid} .squad-social-share__btn{background-color:{$button_bg}}";
		}

		// button_padding → padding on .squad-social-share__btn.
		$button_padding = self::sanitize_css_length( (string) ( $inner['buttonPadding'] ?? '12px' ) );
		if ( '' !== $button_padding ) {
			$css .= ".{$uid} .squad-social-share__btn{padding:{$button_padding}}";
		}

		return $css;
	}

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

	/**
	 * Sanitize a CSS background/color value (hex, rgba, gradient, etc.).
	 *
	 * Strips characters that could break out of the CSS declaration context
	 * (`{ } ; < > \ " '`), so a user-supplied color-picker value cannot inject
	 * arbitrary CSS. Allows rgba(), gradients, and plain hex values.
	 *
	 * @since 4.0.0
	 *
	 * @param string $value Raw value.
	 *
	 * @return string Sanitized value (may be empty).
	 */
	private static function sanitize_css_background( string $value ): string {
		$value = trim( $value );
		if ( '' === $value ) {
			return '';
		}
		return (string) preg_replace( '/[{};<>\\\\"\']/', '', $value );
	}
}
