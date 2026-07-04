<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Post Element (Post Grid Child) Module (Divi 5 / Block API).
 *
 * Native Divi 5 child module for {@see Post_Grid}. A Post Element does not render directly:
 * it is a per-post template. Its render callback emits a base64-encoded configuration marker
 * (`<!--squad-pg-element:…-->`) inside its module wrapper; the parent Post Grid replaces that
 * marker, once per queried post, with the rendered element for that post's data.
 *
 * @since   3.4.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version5\Modules\DynamicContent;

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
use function wp_json_encode;

/**
 * Post Element module class.
 *
 * @since 3.4.0
 */
class Post_Grid_Child extends Module {

	/**
	 * Relative path to the generated module.json metadata folder.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/post-grid-child/';
	}

	/**
	 * Add the module classnames.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $args Classnames arguments.
	 *
	 * @return void
	 */
	public static function module_classnames( array $args ): void {
		$args['classnamesInstance']->add(
			ElementClassnames::classnames(
				array(
					'attrs' => $args['attrs']['module']['decoration'] ?? array(),
				)
			)
		);
	}

	/**
	 * Assign the module's frontend script data.
	 *
	 * @since 3.4.0
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
	 * Register the module style declarations.
	 *
	 * @since 3.4.0
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
					$elements->style( array( 'attrName' => 'elementText' ) ),
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
	 * Render callback for a Post Element.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $attrs    Block attributes.
	 * @param string               $content  Inner content.
	 * @param WP_Block             $block    Parsed block instance.
	 * @param object               $elements ModuleElements instance.
	 *
	 * @return string Rendered HTML (the per-post config marker, wrapped by the module).
	 */
	public static function render_callback( array $attrs, string $content, WP_Block $block, $elements ): string {
		try {
			$inner = $attrs['element']['innerContent']['desktop']['value'] ?? array();

			$config = array(
				'element'                   => $inner['element'] ?? 'none',
				'elementTitleTag'           => $inner['elementTitleTag'] ?? 'span',
				'linkToPost'                => $inner['linkToPost'] ?? 'off',
				'elementExcerpt'            => $inner['elementExcerpt'] ?? 'off',
				'elementContentLength'      => $inner['elementContentLength'] ?? 'off',
				'elementContentLengthValue' => $inner['elementContentLengthValue'] ?? '20',
				'elementAuthorNameType'     => $inner['elementAuthorNameType'] ?? 'nickname',
				'linkToAuthor'              => $inner['linkToAuthor'] ?? 'off',
				'elementDateType'           => $inner['elementDateType'] ?? 'publish',
				'elementDateFormat'         => $inner['elementDateFormat'] ?? '',
				'elementReadMoreText'       => $inner['elementReadMoreText'] ?? '',
				'linkToCategories'          => $inner['linkToCategories'] ?? 'off',
				'linkToTags'                => $inner['linkToTags'] ?? 'off',
				'linkToGravatar'            => $inner['linkToGravatar'] ?? 'off',
				'elementCustomText'         => $inner['elementCustomText'] ?? '',
				'elementCategoriesSepa'     => $inner['elementCategoriesSepa'] ?? '',
				'elementTagsSepa'           => $inner['elementTagsSepa'] ?? '',
				'elementIcon'               => $inner['elementIcon'] ?? '',
				'elementIconPlacement'      => $inner['elementIconPlacement'] ?? 'left',
				'elementGravatarSize'       => $inner['elementGravatarSize'] ?? '40',
				'elementCommentBefore'      => $inner['elementCommentBefore'] ?? '',
				'elementCommentAfter'       => $inner['elementCommentAfter'] ?? '',
				'elementCustomFieldName'    => $inner['elementCustomFieldName'] ?? '',
				'elementCustomFieldBefore'  => $inner['elementCustomFieldBefore'] ?? '',
				'elementCustomFieldAfter'   => $inner['elementCustomFieldAfter'] ?? '',
				'elementAcfName'            => $inner['elementAcfName'] ?? '',
				'elementAcfBefore'          => $inner['elementAcfBefore'] ?? '',
				'elementAcfAfter'           => $inner['elementAcfAfter'] ?? '',
			);

			$marker = sprintf(
				'<!--squad-pg-element:%s-->',
				base64_encode( (string) wp_json_encode( $config ) )
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
					'children'            => $elements->style_components( array( 'attrName' => 'module' ) ) . $marker,
				)
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Post Element module' );

			return '';
		}
	}
}
