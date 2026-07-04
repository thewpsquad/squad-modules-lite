<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Image Accordion Item (child) Module (Divi 5 / Block API).
 *
 * A single accordion panel: a background image, a persistent label, and
 * arbitrary nested Divi module content shown only while the panel is active.
 * The parent module tracks which panel is active and marks it server-side.
 *
 * @since   4.4.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version5\Modules\Media;

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
use function sprintf;

/**
 * Image Accordion Item (child) module class.
 *
 * @since 4.4.0
 */
class Image_Accordion_Item extends Module {

	/**
	 * Placeholder token substituted by Image_Accordion::mark_active_panel()
	 * with this panel's 0-indexed position among its rendered siblings.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	public const INDEX_TOKEN = '%%SQUAD_IA_INDEX%%';

	/**
	 * @since 4.4.0
	 * @return string
	 */
	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/image-accordion-item/';
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
		$args['classnamesInstance']->add( 'disq_image_accordion_item' );
		$args['classnamesInstance']->add(
			ElementClassnames::classnames(
				array( 'attrs' => $args['attrs']['module']['decoration'] ?? array() )
			)
		);
	}

	/**
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
	 * Render the accordion panel on the frontend.
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
			$item      = $attrs['slideItem']['innerContent']['desktop']['value'] ?? array();
			$image_url = esc_url( self::resolve_upload_url( $item['image'] ?? '' ) );

			if ( '' === $image_url ) {
				return '';
			}

			$label = esc_html( (string) ( $item['label'] ?? '' ) );

			$panel_html = sprintf(
				'<div class="squad-image-accordion__panel" data-index="%1$s" style="background-image:url(%2$s)" tabindex="0" role="button" aria-expanded="false"><span class="squad-image-accordion__label">%3$s</span><div class="squad-image-accordion__overlay"><div class="squad-image-accordion__content">%4$s</div></div></div>',
				esc_attr( self::INDEX_TOKEN ),
				$image_url,
				$label,
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
					'children'            => $style_components . $panel_html,
				)
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Image Accordion Item module' );

			return '';
		}
	}
}
