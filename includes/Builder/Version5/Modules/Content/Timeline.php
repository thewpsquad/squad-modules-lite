<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Timeline Module (Divi 5 / Block API).
 *
 * Native Divi 5 parent module. Accepts Timeline Item child blocks and wraps
 * them in the same `.squad-timeline` track emitted by the Divi 4 module, so
 * output is identical across builders. Pure CSS layout + a tiny
 * IntersectionObserver (`timeline.ts`). No external carousel/lib dependency.
 *
 * @since   4.3.0
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

use DiviSquad\Builder\Shared\Modules\Content\Timeline\Timeline_Helper;
use DiviSquad\Builder\Version5\Abstracts\Module;
use ET\Builder\FrontEnd\Module\Style;
use ET\Builder\Packages\Module\Layout\Components\ModuleElements\ModuleElements;
use ET\Builder\Packages\Module\Module as DiviModule;
use ET\Builder\Packages\Module\Options\Css\CssStyle;
use ET\Builder\Packages\Module\Options\Element\ElementClassnames;
use Throwable;
use WP_Block;
use function absint;
use function esc_attr;
use function esc_html__;
use function max;
use function min;
use function wp_enqueue_script;
use function wp_json_encode;

/**
 * Timeline parent module class.
 *
 * @since 4.3.0
 */
class Timeline extends Module {

	/**
	 * Relative path to the generated module.json metadata folder.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/timeline/';
	}

	/**
	 * Add the module classnames.
	 *
	 * @since 4.3.0
	 *
	 * @param array<string, mixed> $args Classnames arguments.
	 *
	 * @return void
	 */
	public static function module_classnames( array $args ): void {
		$args['classnamesInstance']->add( 'disq_timeline' );
		$args['classnamesInstance']->add(
			ElementClassnames::classnames(
				array( 'attrs' => $args['attrs']['module']['decoration'] ?? array() )
			)
		);
	}

	/**
	 * Assign the module's frontend script data.
	 *
	 * @since 4.3.0
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
	 * @since 4.3.0
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
	 * Render callback for the Timeline module.
	 *
	 * @since 4.3.0
	 *
	 * @param array<string, mixed> $attrs                 Block attributes.
	 * @param string               $child_modules_content Rendered child HTML.
	 * @param WP_Block             $block                 Parsed block instance.
	 * @param ModuleElements       $elements              ModuleElements instance.
	 *
	 * @return string Rendered HTML.
	 */
	public static function render_callback( array $attrs, string $child_modules_content, WP_Block $block, $elements ): string {
		try {
			if ( '' === trim( $child_modules_content ) ) {
				return sprintf(
					'<div class="squad-notice">%s</div>',
					esc_html__( 'Add at least one Timeline Item.', 'squad-modules-for-divi' )
				);
			}

			wp_enqueue_script( 'squad-module-timeline' );

			// Parent settings are packed under the `timeline.innerContent` group
			// (same convention as Logo_Carousel's `carousel.innerContent`).
			$inner = $attrs['timeline']['innerContent']['desktop']['value'] ?? array();

			$config = array(
				'orientation' => (string) ( $inner['orientation'] ?? 'vertical' ),
				'layout'      => (string) ( $inner['layout'] ?? 'alternating' ),
				'reveal'      => 'off' === (string) ( $inner['revealOnScroll'] ?? 'on' ) ? 'off' : 'on',
				'stagger'     => max( 0, min( 600, absint( $inner['revealStagger'] ?? 120 ) ) ),
			);

			$uid        = self::get_instance_uid( $block );
			$inline_css = self::get_color_css( $inner, $uid );
			$track_html = Timeline_Helper::build_track( $config, $child_modules_content );

			$timeline_html = ( '' !== $inline_css ? sprintf( '<style>%s</style>', $inline_css ) : '' )
				. sprintf( '<div class="%s">%s</div>', esc_attr( $uid ), $track_html );

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
					'children'            => $style_components . $timeline_html,
				)
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Timeline module' );

			return '';
		}
	}

	/**
	 * Build a stable per-instance uid for scoping color CSS selectors.
	 *
	 * @since 4.3.0
	 *
	 * @param WP_Block $block The parsed block.
	 *
	 * @return string
	 */
	protected static function get_instance_uid( WP_Block $block ): string {
		$raw = (string) ( $block->parsed_block['id'] ?? '' );
		$uid = preg_replace( '/[^a-z0-9]/', '', strtolower( $raw ) );

		return '' !== $uid
			? 'squad-tl-' . $uid
			: 'squad-tl-' . substr( md5( $raw . wp_json_encode( $block->parsed_block['orderIndex'] ?? 0 ) ), 0, 10 );
	}

	/**
	 * Generate scoped line / marker color CSS for this instance.
	 *
	 * @since 4.3.0
	 *
	 * @param array<string, mixed> $inner Packed `timeline.innerContent` desktop values.
	 * @param string               $uid   Per-instance identifier.
	 *
	 * @return string Raw CSS (no <style> tags).
	 */
	protected static function get_color_css( array $inner, string $uid ): string {
		$css = '';

		$line_color = self::sanitize_css_background( (string) ( $inner['lineColor'] ?? '' ) );
		if ( '' !== $line_color ) {
			$css .= ".{$uid} .squad-timeline__line{background-color:{$line_color}}";
		}

		$marker_color = self::sanitize_css_background( (string) ( $inner['markerColor'] ?? '' ) );
		if ( '' !== $marker_color ) {
			$css .= ".{$uid} .squad-timeline__marker{color:{$marker_color};background-color:{$marker_color}}";
			$css .= ".{$uid} .squad-timeline__dot,.{$uid} .squad-timeline__number,.{$uid} .squad-timeline__icon{color:{$marker_color}}";
		}

		return $css;
	}
}
