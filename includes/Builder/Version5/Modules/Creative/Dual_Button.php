<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Dual Button Module (Divi 5 / Block API).
 *
 * @since   3.4.0
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
use function wp_kses_post;

/**
 * Dual Button Module class.
 *
 * @since 3.4.0
 */
class Dual_Button extends Module {

	/**
	 * Relative path to the generated module.json metadata folder.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/dual-button/';
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
		$args['classnamesInstance']->add( 'disq_dual_button' );
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
	 * Render a single button element markup.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, string> $inner   The inner content values.
	 * @param string                $element The element key (left_button|right_button).
	 *
	 * @return string Rendered button HTML.
	 */
	private static function render_button( array $inner, string $element ): string {
		$camel  = 'left_button' === $element ? 'leftButton' : 'rightButton';
		$text   = $inner["{$camel}Text"] ?? '';
		$url    = $inner["{$camel}Url"] ?? '';
		$target = ( $inner["{$camel}UrlNewWindow"] ?? 'off' ) === 'on' ? '_blank' : '_self';
		$icon   = $inner["{$camel}Icon"] ?? '';

		if ( '' === $text ) {
			return '';
		}

		$icon_char    = self::resolve_icon( is_array( $icon ) ? $icon : array() );
		$icon_element = '';
		if ( '' !== $icon_char ) {
			$icon_element = sprintf(
				'<span class="squad-icon-wrapper"><span class="icon-element"><span class="et-pb-icon squad-%1$s-icon">%2$s</span></span></span>',
				esc_attr( $element ),
				esc_html( $icon_char )
			);
		}

		return sprintf(
			'<a class="squad-button button-element %1$s et_pb_with_background" href="%2$s" target="%3$s"><span class="button-text">%4$s</span>%5$s</a>',
			esc_attr( $element ),
			esc_url( $url ),
			esc_attr( $target ),
			wp_kses_post( $text ),
			$icon_element
		);
	}

	/**
	 * Render the separator element markup.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, string> $inner The inner content values.
	 *
	 * @return string Rendered separator HTML.
	 */
	private static function render_separator( array $inner ): string {
		$is_icon = ( $inner['separatorIconEnable'] ?? 'off' ) === 'on';

		if ( $is_icon ) {
			$icon_char = self::resolve_icon( $inner['separatorIcon'] ?? array() );
			if ( '' === $icon_char ) {
				return '';
			}

			$inner_html = sprintf(
				'<span class="squad-icon-wrapper"><span class="icon-element"><span class="et-pb-icon squad-separator-icon">%1$s</span></span></span>',
				esc_html( $icon_char )
			);
		} else {
			$text = $inner['separatorText'] ?? '';
			if ( '' === $text ) {
				return '';
			}

			$inner_html = sprintf(
				'<span class="separator-text">%1$s</span>',
				wp_kses_post( $text )
			);
		}

		return sprintf(
			'<span class="squad-separator separator-element et_pb_with_background">%1$s</span>',
			$inner_html
		);
	}

	/**
	 * Render callback for the Dual Button module.
	 *
	 * @since 3.4.0
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
			$inner = $attrs['content']['innerContent']['desktop']['value'] ?? array();

			$left_button  = self::render_button( $inner, 'left_button' );
			$right_button = self::render_button( $inner, 'right_button' );
			$separator    = self::render_separator( $inner );

			$html = sprintf(
				'<div class="elements et_pb_with_background">%1$s%2$s%3$s</div>',
				$left_button,
				$separator,
				$right_button
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
					'classnamesFunction'  => array( self::class, 'module_classnames' ),
					'stylesComponent'     => array( self::class, 'module_styles' ),
					'scriptDataComponent' => array( self::class, 'module_script_data' ),
					'children'            => $style_components . $html,
				)
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Dual Button module' );

			return '';
		}
	}
}
