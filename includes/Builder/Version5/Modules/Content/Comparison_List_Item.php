<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Comparison List Item (child) Module (Divi 5 / Block API).
 *
 * Native Divi 5 child module for Comparison List. Renders a single feature
 * row: a state (included / excluded / neutral) exposed as `data-status`, an
 * empty `aria-hidden` icon span, an escaped title, and any nested Divi
 * module content. The parent module owns the three status icons + colors
 * and injects the icon glyph for each state via CSS `::before` keyed off
 * `data-status` — this child never renders the glyph itself.
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
use function in_array;
use function sprintf;

/**
 * Comparison List Item (child) module class.
 *
 * @since 4.4.0
 */
class Comparison_List_Item extends Module {

	/**
	 * Allowed row status values (must match `Comparison_List_Item::STATUSES` in D4).
	 *
	 * @since 4.4.0
	 *
	 * @var array<int, string>
	 */
	private const STATUSES = array( 'included', 'excluded', 'neutral' );

	/**
	 * Relative path to the generated module.json metadata folder.
	 *
	 * @since 4.4.0
	 *
	 * @return string
	 */
	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/comparison-list-item/';
	}

	/**
	 * Add the module classnames.
	 *
	 * Adds `disq_comparison_list_item` so the DiviModule::render outer
	 * wrapper carries the same root class the D4 child uses as its
	 * `main_css_element`.
	 *
	 * @since 4.4.0
	 *
	 * @param array<string, mixed> $args Classnames arguments.
	 *
	 * @return void
	 */
	public static function module_classnames( array $args ): void {
		$args['classnamesInstance']->add( 'disq_comparison_list_item' );
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
	 * Render callback for the Comparison List Item (child) module.
	 *
	 * The status icon glyph itself is NOT rendered here — the parent module
	 * owns the per-state icon/color configuration and targets rows by the
	 * `data-status` attribute set below. This child only contributes the
	 * state, the escaped title, and any nested Divi module content.
	 *
	 * @since 4.4.0
	 *
	 * @param array<string, mixed> $attrs                 Block attributes.
	 * @param string               $child_modules_content Rendered nested Divi module content.
	 * @param WP_Block             $block                 Parsed block instance.
	 * @param ModuleElements       $elements              ModuleElements instance.
	 *
	 * @return string Rendered HTML.
	 */
	public static function render_callback( array $attrs, string $child_modules_content, WP_Block $block, $elements ): string {
		try {
			$item = $attrs['slideItem']['innerContent']['desktop']['value'] ?? array();

			$status = (string) ( $item['status'] ?? 'included' );
			$status = in_array( $status, self::STATUSES, true ) ? $status : 'included';

			$title = esc_html( (string) ( $item['title'] ?? '' ) );

			$item_html = sprintf(
				'<div class="squad-comparison-list__item" data-status="%1$s"><span class="squad-comparison-list__icon" aria-hidden="true"></span><div class="squad-comparison-list__body"><span class="squad-comparison-list__title">%2$s</span><div class="squad-comparison-list__content">%3$s</div></div></div>',
				esc_attr( $status ),
				$title,
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
					'children'            => $style_components . $item_html,
				)
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Comparison List Item module' );

			return '';
		}
	}
}
