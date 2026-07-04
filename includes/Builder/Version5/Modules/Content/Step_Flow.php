<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Step Flow Module (Divi 5 / Block API).
 *
 * Native Divi 5 parent module. Accepts Step Flow Item child blocks and wraps
 * them in the same `.squad-step-flow` shell emitted by the Divi 4 module, so
 * output is identical across builders. Independent from the Timeline module —
 * no shared helper class — per
 * docs/superpowers/specs/2026-07-03-step-flow-module-design.md §2.
 *
 * @since   4.4.0
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
use function absint;
use function esc_attr;
use function esc_html__;
use function in_array;
use function max;
use function min;
use function sprintf;
use function wp_enqueue_script;
use function wp_json_encode;

/**
 * Step Flow parent module class.
 *
 * @since 4.4.0
 */
class Step_Flow extends Module {

	/**
	 * Relative path to the generated module.json metadata folder.
	 *
	 * @since 4.4.0
	 *
	 * @return string
	 */
	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/step-flow/';
	}

	/**
	 * Add the module classnames.
	 *
	 * @since 4.4.0
	 *
	 * @param array<string, mixed> $args Classnames arguments.
	 *
	 * @return void
	 */
	public static function module_classnames( array $args ): void {
		$args['classnamesInstance']->add( 'disq_step_flow' );
		$args['classnamesInstance']->add(
			ElementClassnames::classnames(
				array( 'attrs' => $args['attrs']['module']['decoration'] ?? array() )
			)
		);
	}

	/**
	 * Assign the module's frontend script data.
	 *
	 * @since 4.4.0
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
	 * @since 4.4.0
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
	 * Render callback for the Step Flow module.
	 *
	 * @since 4.4.0
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
					esc_html__( 'Add at least one Step Flow Item.', 'squad-modules-for-divi' )
				);
			}

			wp_enqueue_script( 'squad-module-step-flow' );

			$inner = $attrs['stepFlow']['innerContent']['desktop']['value'] ?? array();

			$orientation = (string) ( $inner['orientation'] ?? 'vertical' );
			$orientation = in_array( $orientation, array( 'vertical', 'horizontal' ), true ) ? $orientation : 'vertical';

			$reveal = 'off' === (string) ( $inner['revealOnScroll'] ?? 'on' ) ? 'off' : 'on';
			$delay  = max( 0, min( 2000, absint( $inner['revealDelay'] ?? 100 ) ) );
			$arrow  = 'on' === (string) ( $inner['showArrow'] ?? 'off' ) ? 'on' : 'off';

			$uid        = self::get_instance_uid( $block );
			$inline_css = self::get_connector_css( $inner, $uid );

			$step_flow_html = ( '' !== $inline_css ? sprintf( '<style>%s</style>', $inline_css ) : '' )
				. sprintf(
					'<div class="%1$s squad-step-flow squad-step-flow--%2$s" data-reveal="%3$s" data-delay="%4$s" data-arrow="%5$s">%6$s</div>',
					esc_attr( $uid ),
					esc_attr( $orientation ),
					esc_attr( $reveal ),
					esc_attr( (string) $delay ),
					esc_attr( $arrow ),
					$child_modules_content
				);

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
					'children'            => $style_components . $step_flow_html,
				)
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Step Flow module' );

			return '';
		}
	}

	/**
	 * Build a stable per-instance uid for scoping connector CSS selectors.
	 *
	 * @since 4.4.0
	 *
	 * @param WP_Block $block The parsed block.
	 *
	 * @return string
	 */
	protected static function get_instance_uid( WP_Block $block ): string {
		$raw = (string) ( $block->parsed_block['id'] ?? '' );
		$uid = preg_replace( '/[^a-z0-9]/', '', strtolower( $raw ) );

		return '' !== $uid
			? 'squad-sf-' . $uid
			: 'squad-sf-' . substr( md5( $raw . wp_json_encode( $block->parsed_block['orderIndex'] ?? 0 ) ), 0, 10 );
	}

	/**
	 * Generate scoped connector line CSS custom properties for this instance.
	 *
	 * @since 4.4.0
	 *
	 * @param array<string, mixed> $inner Packed `stepFlow.innerContent` desktop values.
	 * @param string               $uid   Per-instance identifier.
	 *
	 * @return string Raw CSS (no <style> tags).
	 */
	protected static function get_connector_css( array $inner, string $uid ): string {
		$declarations = array();

		$style = (string) ( $inner['connectorLineStyle'] ?? 'solid' );
		$style = in_array( $style, array( 'solid', 'dashed', 'dotted' ), true ) ? $style : 'solid';
		$declarations[] = "--squad-sf-connector-style:{$style}";

		$color = self::sanitize_css_background( (string) ( $inner['connectorColor'] ?? '' ) );
		if ( '' !== $color ) {
			$declarations[] = "--squad-sf-connector-color:{$color}";
		}

		$width = self::sanitize_css_length( (string) ( $inner['connectorWidth'] ?? '2px' ) );
		if ( '' !== $width ) {
			$declarations[] = "--squad-sf-connector-width:{$width}";
		}

		// The connector-style declaration above is always present, so $declarations
		// is never empty here; emit the rule unconditionally.
		return sprintf( '.%s{%s}', $uid, implode( ';', $declarations ) );
	}
}
