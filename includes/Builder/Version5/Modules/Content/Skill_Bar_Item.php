<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Skill Bar Item (child) Module (Divi 5 / Block API).
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
use function absint;
use function esc_attr;
use function esc_html;

/**
 * Skill Bar Item (child) module class.
 *
 * @since 4.0.0
 */
class Skill_Bar_Item extends Module {

	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/skill-bar-item/';
	}

	public static function module_classnames( array $args ): void {
		$args['classnamesInstance']->add( 'squad-skill-bar__item' );
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

	public static function render_callback( array $attrs, string $content, WP_Block $block, $elements ): string {
		try {
			$item       = $attrs['slideItem']['innerContent']['desktop']['value'] ?? array();
			$level      = max( 0, min( 100, absint( $item['level'] ?? 70 ) ) );
			$use_name   = 'off' !== ( $item['useName'] ?? 'on' );
			$hide_level = 'on' === ( $item['hideLevel'] ?? 'off' );
			$placement  = 'outside' === ( $item['textPlacement'] ?? 'inside' ) ? 'outside' : 'inside';
			$uid        = self::get_instance_uid( $block );

			$name_html  = $use_name ? sprintf( '<span class="squad-skill-bar__name">%s</span>', esc_html( $item['name'] ?? '' ) ) : '';
			$level_html = ! $hide_level ? sprintf( '<span class="squad-skill-bar__level">%d%%</span>', $level ) : '';
			$text_html  = ( '' !== $name_html || '' !== $level_html )
				? sprintf( '<div class="squad-skill-bar__text squad-skill-bar__text--%1$s">%2$s%3$s</div>', $placement, $name_html, $level_html )
				: '';

			$inline_css = self::get_bar_css( $item, $uid );

			$bar_html = sprintf(
				'%1$s<div class="squad-skill-bar__wrapper"><div class="squad-skill-bar__fill" style="--squad-sb-level:%2$d%%">%3$s</div></div>',
				'' !== $inline_css ? sprintf( '<style>%s</style>', $inline_css ) : '',
				$level,
				$text_html
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
					'children'            => $elements->style_components( array( 'attrName' => 'module' ) ) . $bar_html,
				)
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Skill Bar Item module' );

			return '';
		}
	}

	protected static function get_instance_uid( WP_Block $block ): string {
		$raw = (string) ( $block->parsed_block['id'] ?? '' );
		$uid = preg_replace( '/[^a-z0-9]/', '', strtolower( $raw ) );

		return '' !== $uid
			? 'squad-sb-' . $uid
			: 'squad-sb-' . substr( md5( $raw ), 0, 10 );
	}

	protected static function get_bar_css( array $item, string $uid ): string {
		$sel_wrap = ".{$uid} .squad-skill-bar__wrapper";
		$sel_fill = ".{$uid} .squad-skill-bar__fill";

		$height = self::sanitize_css_length( (string) ( $item['barHeight'] ?? '30px' ) );
		$radius = self::sanitize_css_length( (string) ( $item['barRadius'] ?? '40px' ) );

		$track_bg = self::sanitize_css_background( (string) ( $item['trackGradient'] ?? '' ) );
		if ( '' === $track_bg ) {
			$track_bg = self::sanitize_css_background( (string) ( $item['trackColor'] ?? '#dddddd' ) );
		}
		$fill_bg = self::sanitize_css_background( (string) ( $item['fillGradient'] ?? '' ) );
		if ( '' === $fill_bg ) {
			$fill_bg = self::sanitize_css_background( (string) ( $item['fillColor'] ?? '#5E2EFF' ) );
		}

		$wrap_decl = '';
		if ( '' !== $height ) {
			$wrap_decl .= "height:{$height};";
		}
		if ( '' !== $radius ) {
			$wrap_decl .= "border-radius:{$radius};";
		}
		if ( '' !== $track_bg ) {
			$wrap_decl .= 'background:' . esc_attr( $track_bg ) . ';';
		}

		$css = '';
		if ( '' !== $wrap_decl ) {
			$css .= "{$sel_wrap}{{$wrap_decl}}";
		}
		if ( '' !== $fill_bg ) {
			$css .= "{$sel_fill}{background:" . esc_attr( $fill_bg ) . ';}';
		}

		return $css;
	}

	/**
	 * Sanitize a CSS background value (color or gradient).
	 *
	 * Strips characters that could break out of the CSS declaration/block
	 * context (`{ } ; < > \` and quotes), so a user-supplied gradient/color
	 * field cannot inject arbitrary CSS into the scoped <style> block.
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
