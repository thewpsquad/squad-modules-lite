<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Table of Contents Module (Divi 5 / Block API).
 *
 * Renders the same thin `.squad-toc` config shell as the Divi 4 module; the
 * heading list is built client-side by `table-of-contents.ts`.
 *
 * @since   4.0.0
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

use DiviSquad\Builder\Shared\Modules\Creative\Table_Of_Contents\Toc_Helper;
use DiviSquad\Builder\Version5\Abstracts\Module;
use ET\Builder\FrontEnd\Module\Style;
use ET\Builder\Packages\Module\Layout\Components\ModuleElements\ModuleElements;
use ET\Builder\Packages\Module\Module as DiviModule;
use ET\Builder\Packages\Module\Options\Css\CssStyle;
use ET\Builder\Packages\Module\Options\Element\ElementClassnames;
use Throwable;
use WP_Block;
use function absint;
use function esc_html__;
use function implode;
use function in_array;
use function max;
use function min;
use function sanitize_text_field;
use function wp_enqueue_script;

/**
 * Table of Contents module class.
 *
 * @since 4.0.0
 */
class Table_Of_Contents extends Module {

	/**
	 * Relative path to the generated module.json metadata folder.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/table-of-contents/';
	}

	/**
	 * Add the module classnames.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $args Classnames arguments.
	 *
	 * @return void
	 */
	public static function module_classnames( array $args ): void {
		$args['classnamesInstance']->add( 'disq_table_of_contents' );
		$args['classnamesInstance']->add(
			ElementClassnames::classnames(
				array( 'attrs' => $args['attrs']['module']['decoration'] ?? array() )
			)
		);
	}

	/**
	 * Assign the module's frontend script data.
	 *
	 * @since 4.0.0
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
	 * @since 4.0.0
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
									array(
										'componentName' => 'divi/common',
										'props'         => array(
											'selector'            => "{$args['orderClass']} .squad-toc__link.is-active",
											'attr'                => $attrs['colors']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												$value = $params['attrValue'] ?? array();

												$color = self::sanitize_css_background( (string) ( $value['activeLinkColor'] ?? '' ) );

												return '' !== $color ? 'color: ' . $color . ';' : '';
											},
										),
									),
									array(
										'componentName' => 'divi/common',
										'props'         => array(
											'selector'            => "{$args['orderClass']} .squad-toc__item::marker",
											'attr'                => $attrs['colors']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												$value = $params['attrValue'] ?? array();

												$color = self::sanitize_css_background( (string) ( $value['markerColor'] ?? '' ) );

												return '' !== $color ? 'color: ' . $color . ';' : '';
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
	 * Render callback for the Table of Contents module.
	 *
	 * @since 4.0.0
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
			wp_enqueue_script( 'squad-module-table-of-contents' );

			$inner = $attrs['tocSettings']['innerContent']['desktop']['value'] ?? array();

			$show_title  = 'off' === ( $inner['showTitle'] ?? 'on' ) ? 'off' : 'on';
			$collapsible = 'on' === ( $inner['collapsible'] ?? 'off' ) && 'on' === $show_title;

			$props = array();
			for ( $n = 1; $n <= 6; $n ++ ) {
				$props[ 'include_h' . $n ] = ( $inner[ 'includeH' . $n ] ?? '' );
			}
			$levels = Toc_Helper::selected_levels( $props );

			$list = (string) ( $inner['listStyle'] ?? 'unordered' );
			$list = in_array( $list, array( 'ordered', 'unordered', 'none' ), true ) ? $list : 'unordered';

			$config = array(
				'selector'      => sanitize_text_field( (string) ( $inner['contentSelector'] ?? '' ) ),
				'levels'        => implode( ',', $levels ),
				'list'          => $list,
				'smooth'        => 'off' === ( $inner['smoothScroll'] ?? 'on' ) ? '0' : '1',
				'offset'        => (string) absint( $inner['scrollOffset'] ?? 0 ),
				'spy'           => 'off' === ( $inner['scrollSpy'] ?? 'on' ) ? '0' : '1',
				'min'           => (string) max( 1, min( 20, absint( $inner['minHeadings'] ?? 1 ) ) ),
				'collapsed'     => ( $collapsible && 'on' === ( $inner['defaultCollapsed'] ?? 'off' ) ) ? '1' : '0',
				'sticky_offset' => (string) absint( $inner['stickyOffset'] ?? 0 ),
			);

			$sticky = 'on' === ( $inner['sticky'] ?? 'off' );

			$shell = Toc_Helper::build_shell(
				$config,
				$list,
				$sticky,
				$collapsible,
				$show_title,
				(string) ( $inner['title'] ?? esc_html__( 'Table of Contents', 'squad-modules-for-divi' ) ),
				(string) ( $inner['titleTag'] ?? 'h3' )
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
					'children'            => $style_components . $shell,
				)
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Table of Contents module' );

			return '';
		}
	}
}
