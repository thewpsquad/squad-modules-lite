<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Logo Grid Item (child) Module (Divi 5 / Block API).
 *
 * @since   4.0.0
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
use function esc_url;

/**
 * Logo Grid Item (child) module class.
 *
 * @since 4.0.0
 */
class Logo_Grid_Item extends Module {

	/**
	 * @since 4.0.0
	 * @return string
	 */
	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/logo-grid-item/';
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
		$args['classnamesInstance']->add( 'disq_logo_grid_item' );
		$args['classnamesInstance']->add( 'squad-logo-grid__item' );
		$args['classnamesInstance']->add(
			ElementClassnames::classnames(
				array(
					'attrs' => $args['attrs']['module']['decoration'] ?? array(),
				)
			)
		);
	}

	/**
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $args Script data arguments.
	 *
	 * @return void
	 */
	public static function module_script_data( array $args ): void {
		$args['elements']->script_data(
			array(
				'attrName' => 'module',
			)
		);
	}

	/**
	 * @since 4.0.0
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
					$elements->style( array( 'attrName' => 'image' ) ),
					CssStyle::style(
						array(
							'selector' => $args['orderClass'],
							'attr'     => $attrs['css'] ?? array(),
						)
					),
				),
			)
		);
	}

	/**
	 * Render callback for the Logo Grid Item (child) module.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $attrs    Block attributes.
	 * @param string               $content  Inner content.
	 * @param WP_Block             $block    Parsed block instance.
	 * @param object               $elements ModuleElements instance.
	 *
	 * @return string Rendered HTML.
	 */
	public static function render_callback( array $attrs, string $content, WP_Block $block, $elements ): string {
		try {
			$item      = $attrs['slideItem']['innerContent']['desktop']['value'] ?? array();
			$image_url = esc_url( self::resolve_upload_url( $item['image'] ?? '' ) );
			$logo_link = esc_url( $item['logoLink'] ?? '' );
			$target    = $item['logoLinkTarget'] ?? '_self';
			$target    = in_array( $target, array( '_self', '_blank' ), true ) ? $target : '_self';

			$item_html = self::render_logo( $image_url, $item['imageAlt'] ?? '', $logo_link, $target );

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
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Logo Grid Item module' );

			return '';
		}
	}

	/**
	 * Render the logo image with optional link wrapper.
	 *
	 * @since 4.0.0
	 *
	 * @param string $image_url URL of the logo image.
	 * @param string $alt       Alt text.
	 * @param string $logo_link URL to link to (empty = no link).
	 * @param string $target    Link target (_self or _blank).
	 *
	 * @return string
	 */
	private static function render_logo( string $image_url, string $alt, string $logo_link, string $target ): string {
		if ( '' === $image_url ) {
			return '';
		}

		$img = sprintf(
			'<img class="squad-logo-grid__logo" src="%1$s" alt="%2$s" loading="lazy" />',
			$image_url,
			esc_attr( $alt )
		);

		if ( '' !== $logo_link ) {
			$rel = '_blank' === $target ? ' rel="noopener noreferrer"' : '';

			return sprintf(
				'<a href="%1$s" target="%2$s"%3$s>%4$s</a>',
				$logo_link,
				esc_attr( $target ),
				$rel,
				$img
			);
		}

		return $img;
	}
}
