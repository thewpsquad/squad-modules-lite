<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Skill Bar Module (Divi 5 / Block API).
 *
 * Parent module holding an optional title and animated child skill bars.
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
use function esc_html;
use function esc_html__;
use function in_array;
use function wp_enqueue_script;

/**
 * Skill Bar parent module class.
 *
 * @since 4.0.0
 */
class Skill_Bar extends Module {

	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/skill-bar/';
	}

	public static function module_classnames( array $args ): void {
		$args['classnamesInstance']->add( 'disq_skill_bar' );
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
					esc_html__( 'Add at least one Skill Bar Item.', 'squad-modules-for-divi' )
				);
			}

			wp_enqueue_script( 'squad-module-skill-bar' );

			$inner = $attrs['bars']['innerContent']['desktop']['value'] ?? array();
			$uid   = self::get_instance_uid( $block );

			$title      = (string) ( $inner['title'] ?? '' );
			$title_html = '';
			if ( '' !== $title ) {
				$level      = (string) ( $inner['titleLevel'] ?? 'h3' );
				$level      = in_array( $level, array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ), true ) ? $level : 'h3';
				$title_html = sprintf( '<%1$s class="squad-skill-bar__title">%2$s</%1$s>', $level, esc_html( $title ) );
			}

			$inline_css = self::get_spacing_css( $inner, $uid );

			$grid_html = sprintf(
				'%1$s%2$s<div class="squad-skill-bar">%3$s</div>',
				'' !== $inline_css ? sprintf( '<style>%s</style>', $inline_css ) : '',
				$title_html,
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
					'children'            => $elements->style_components( array( 'attrName' => 'module' ) ) . $grid_html,
				)
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Skill Bar module' );

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

	protected static function get_spacing_css( array $inner, string $uid ): string {
		$bar_gap   = self::sanitize_css_length( (string) ( $inner['barSpacing'] ?? '20px' ) );
		$title_gap = self::sanitize_css_length( (string) ( $inner['titleSpacing'] ?? '10px' ) );

		$css = '';
		if ( '' !== $bar_gap ) {
			$css .= ".{$uid} .squad-skill-bar .disq_skill_bar_item{margin-bottom:{$bar_gap}}";
		}
		if ( '' !== $title_gap ) {
			$css .= ".{$uid} .squad-skill-bar__title{margin-bottom:{$title_gap}}";
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
}
