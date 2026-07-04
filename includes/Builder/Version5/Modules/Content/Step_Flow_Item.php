<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Step Flow Item (child) Module (Divi 5 / Block API).
 *
 * Native Divi 5 child module for Step Flow. Renders the marker + content
 * inner markup directly (no shared helper with Timeline); the outer
 * DiviModule::render wrapper receives `disq_step_flow_item` and
 * `squad-step-flow__step` via module_classnames(), so the wrapper itself
 * becomes the step node (flat structure, unlike the V4/D4 nested render).
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
use function esc_attr;
use function esc_html;
use function esc_url;
use function in_array;
use function sprintf;
use function wp_kses_post;

/**
 * Step Flow Item (child) module class.
 *
 * @since 4.4.0
 */
class Step_Flow_Item extends Module {

	/**
	 * Allowed marker-type tokens (first = fallback).
	 *
	 * @since 4.4.0
	 *
	 * @var array<int, string>
	 */
	private const MARKER_TYPES = array( 'number', 'icon', 'image' );

	/**
	 * Relative path to the generated module.json metadata folder.
	 *
	 * @since 4.4.0
	 *
	 * @return string
	 */
	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/step-flow-item/';
	}

	/**
	 * Add the module classnames.
	 *
	 * Adds `disq_step_flow_item` and `squad-step-flow__step` so the
	 * DiviModule::render outer wrapper becomes the step node directly.
	 *
	 * @since 4.4.0
	 *
	 * @param array<string, mixed> $args Classnames arguments.
	 *
	 * @return void
	 */
	public static function module_classnames( array $args ): void {
		$args['classnamesInstance']->add( 'disq_step_flow_item' );
		$args['classnamesInstance']->add( 'squad-step-flow__step' );
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
	 * @param array<string, mixed> $args Style arguments.
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
	 * Render callback for the Step Flow Item (child) module.
	 *
	 * @since 4.4.0
	 *
	 * @param array<string, mixed> $attrs    Block attributes.
	 * @param string               $content  Inner content (unused).
	 * @param WP_Block             $block    Parsed block instance.
	 * @param object               $elements ModuleElements instance.
	 *
	 * @return string Rendered HTML.
	 */
	public static function render_callback( array $attrs, string $content, WP_Block $block, $elements ): string {
		try {
			$item = $attrs['slideItem']['innerContent']['desktop']['value'] ?? array();

			$marker_type = (string) ( $item['markerType'] ?? 'number' );
			$marker_type = in_array( $marker_type, self::MARKER_TYPES, true ) ? $marker_type : 'number';

			$marker = self::build_marker( $marker_type, $item );

			$label       = (string) ( $item['label'] ?? '' );
			$title       = (string) ( $item['title'] ?? '' );
			$description = (string) ( $item['description'] ?? '' );

			$content_inner = '';
			if ( '' !== $label ) {
				$content_inner .= sprintf( '<span class="squad-step-flow__label">%s</span>', esc_html( $label ) );
			}
			if ( '' !== $title ) {
				$content_inner .= sprintf( '<h4 class="squad-step-flow__title">%s</h4>', esc_html( $title ) );
			}
			if ( '' !== $description ) {
				$content_inner .= sprintf( '<div class="squad-step-flow__description">%s</div>', wp_kses_post( $description ) );
			}

			$content_html = sprintf( '<div class="squad-step-flow__content">%s</div>', $content_inner );

			$inner = sprintf(
				'<div class="squad-step-flow__marker" aria-hidden="true">%1$s</div>%2$s',
				$marker,
				$content_html
			);

			$link = esc_url( (string) ( $item['linkUrl'] ?? '' ) );
			if ( '' !== $link ) {
				$target = (string) ( $item['linkTarget'] ?? '_self' );
				$target = in_array( $target, array( '_self', '_blank' ), true ) ? $target : '_self';
				$rel    = '_blank' === $target ? ' rel="noopener noreferrer"' : '';

				$item_html = sprintf(
					'<a class="squad-step-flow__link" href="%1$s" target="%2$s"%3$s>%4$s</a>',
					$link,
					esc_attr( $target ),
					$rel,
					$inner
				);
			} else {
				$item_html = $inner;
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
					'children'            => $style_components . $item_html,
				)
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Step Flow Item module' );

			return '';
		}
	}

	/**
	 * Build the marker inner markup for a given marker type.
	 *
	 * @since 4.4.0
	 *
	 * @param string               $type Validated marker type (number|icon|image).
	 * @param array<string, mixed> $item Packed `slideItem.innerContent` desktop values.
	 *
	 * @return string
	 */
	private static function build_marker( string $type, array $item ): string {
		if ( 'icon' === $type ) {
			$icon = self::resolve_icon( $item['markerIcon'] ?? array() );
			if ( '' === $icon ) {
				return '<span class="squad-step-flow__number squad-step-flow__number--auto"></span>';
			}

			return sprintf( '<span class="squad-step-flow__icon et-pb-icon">%s</span>', esc_html( $icon ) );
		}

		if ( 'image' === $type ) {
			$image = esc_url( self::resolve_upload_url( $item['markerImage'] ?? '' ) );
			if ( '' === $image ) {
				return '<span class="squad-step-flow__number squad-step-flow__number--auto"></span>';
			}

			return sprintf(
				'<img class="squad-step-flow__marker-image" src="%1$s" alt="%2$s" loading="lazy" />',
				$image,
				esc_attr( (string) ( $item['markerImageAlt'] ?? '' ) )
			);
		}

		$override = (string) ( $item['markerNumberOverride'] ?? '' );
		if ( '' !== $override ) {
			return sprintf( '<span class="squad-step-flow__number">%s</span>', esc_html( $override ) );
		}

		return '<span class="squad-step-flow__number squad-step-flow__number--auto"></span>';
	}
}
