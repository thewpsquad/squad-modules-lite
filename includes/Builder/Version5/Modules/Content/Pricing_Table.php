<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Pricing Table Module (Divi 5 / Block API).
 *
 * Parent module that lays out a responsive grid of child pricing-plan cards.
 *
 * @since   4.2.0
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
use function esc_html__;
use function max;
use function min;
use function preg_replace;
use function substr;
use function trim;

/**
 * Pricing Table parent module class.
 *
 * @since 4.2.0
 */
class Pricing_Table extends Module {

	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/pricing-table/';
	}

	/**
	 * Add module-specific classnames.
	 *
	 * @param array<string, mixed> $args Classnames arguments.
	 *
	 * @return void
	 */
	public static function module_classnames( array $args ): void {
		$args['classnamesInstance']->add( 'disq_pricing_table' );
		$args['classnamesInstance']->add(
			ElementClassnames::classnames(
				array( 'attrs' => $args['attrs']['module']['decoration'] ?? array() )
			)
		);
	}

	/**
	 * Set module script data.
	 *
	 * @param array<string, mixed> $args Script data arguments.
	 *
	 * @return void
	 */
	public static function module_script_data( array $args ): void {
		$args['elements']->script_data( array( 'attrName' => 'module' ) );
	}

	/**
	 * Add module styles.
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
	 * Render the Pricing Table module on the frontend.
	 *
	 * @param array<string, mixed> $attrs                 Block attributes.
	 * @param string               $child_modules_content Inner (child) block content.
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
					esc_html__( 'Add at least one Pricing Plan.', 'squad-modules-for-divi' )
				);
			}

			$inner = $attrs['plans']['innerContent']['desktop']['value'] ?? array();
			$uid   = self::get_instance_uid( $block );

			$inline_css = self::get_grid_css( $inner, $uid );

			$grid_html = sprintf(
				'%1$s<div class="squad-pricing-tables squad-pricing-tables--grid %2$s">%3$s</div>',
				'' !== $inline_css ? sprintf( '<style>%s</style>', $inline_css ) : '',
				esc_attr( $uid ),
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
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Pricing Table module' );

			return '';
		}
	}

	protected static function get_instance_uid( WP_Block $block ): string {
		$raw = (string) ( $block->parsed_block['id'] ?? '' );
		$uid = preg_replace( '/[^a-z0-9]/', '', strtolower( $raw ) );

		return ( null !== $uid && '' !== $uid )
			? 'squad-pt-' . $uid
			: 'squad-pt-' . substr( md5( $raw ), 0, 10 );
	}

	/**
	 * Build the responsive grid CSS for the plans wrapper.
	 *
	 * @param array<string, mixed> $inner The module's content inner values.
	 * @param string               $uid   Unique instance identifier used as the CSS scope.
	 *
	 * @return string The generated CSS, or an empty string when nothing is set.
	 */
	protected static function get_grid_css( array $inner, string $uid ): string {
		$columns = max( 1, min( 4, (int) ( $inner['columns'] ?? 3 ) ) );
		$col_gap = self::sanitize_css_length( (string) ( $inner['columnGap'] ?? '24px' ) );
		$row_gap = self::sanitize_css_length( (string) ( $inner['rowGap'] ?? '24px' ) );

		$col_value = '' !== $col_gap ? $col_gap : '24px';
		$row_value = '' !== $row_gap ? $row_gap : '24px';

		$css  = ".{$uid}.squad-pricing-tables--grid{display:grid;grid-template-columns:repeat({$columns},minmax(0,1fr));gap:{$row_value} {$col_value};}";
		$css .= "@media(max-width:980px){.{$uid}.squad-pricing-tables--grid{grid-template-columns:repeat(2,minmax(0,1fr));}}";
		$css .= "@media(max-width:767px){.{$uid}.squad-pricing-tables--grid{grid-template-columns:1fr;}}";

		return $css;
	}
}
