<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Social Share Item (child) Module (Divi 5 / Block API).
 *
 * @since   4.0.0
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

use DiviSquad\Builder\Shared\Modules\Content\Social_Share\Networks;
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
use function esc_html__;
use function esc_url;
use function sanitize_text_field;
use function sprintf;

/**
 * Social Share Item (child) module class.
 *
 * @since 4.0.0
 */
class Social_Share_Item extends Module {

	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/social-share-item/';
	}

	/**
	 * Add CSS classnames to the module wrapper.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $args Classnames arguments provided by Divi.
	 *
	 * @return void
	 */
	public static function module_classnames( array $args ): void {
		$args['classnamesInstance']->add( 'squad-social-share__item' );
		$args['classnamesInstance']->add(
			ElementClassnames::classnames(
				array( 'attrs' => $args['attrs']['module']['decoration'] ?? array() )
			)
		);
	}

	/**
	 * Register the module's script data.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $args Script data arguments provided by Divi.
	 *
	 * @return void
	 */
	public static function module_script_data( array $args ): void {
		$args['elements']->script_data( array( 'attrName' => 'module' ) );
	}

	/**
	 * Register the module's styles.
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

	public static function render_callback( array $attrs, string $content, WP_Block $block, $elements ): string {
		try {
			$item    = $attrs['itemSettings']['innerContent']['desktop']['value'] ?? array();
			$network = (string) ( $item['network'] ?? 'facebook' );

			if ( ! Networks::is_valid( $network ) ) {
				return '';
			}

			$meta = Networks::get_network( $network );
			if ( null === $meta ) {
				return '';
			}

			$target = Social_Share::$share_target;
			$ctx    = Social_Share::$button_context;

			$share_title = '' !== $target['title'] ? $target['title'] : $target['desc'];
			$href        = Networks::build_share_url( $network, $target['url'], $share_title );
			if ( '' === $href ) {
				return '';
			}

			$is_email = Networks::is_email( $network );
			$style    = 'icon_text' === ( $ctx['style'] ?? 'icon' ) ? 'icon_text' : 'icon';

			$label = sanitize_text_field( (string) ( $item['customLabel'] ?? '' ) );
			if ( '' === $label ) {
				$label = $meta['label'];
			}

			$uid        = self::get_instance_uid( $block );
			$inline_css = self::get_color_css( $item, $meta['color'], $uid );

			$link_attrs = sprintf( 'href="%s"', esc_url( $href ) );
			if ( ! $is_email ) {
				$link_attrs .= ' target="_blank" rel="noopener noreferrer nofollow"';
				if ( 'on' === ( $ctx['enable_popup'] ?? 'on' ) ) {
					$link_attrs .= ' data-squad-share="popup"';
				}
			}

			$style_components = $elements instanceof ModuleElements
				? (string) $elements->style_components( array( 'attrName' => 'module' ) )
				: '';

			$icon_html  = sprintf( '<span class="squad-social-share__icon squad-social-share__icon--%s" aria-hidden="true"></span>', esc_attr( $network ) );
			$label_html = 'icon_text' === $style
				? sprintf( '<span class="squad-social-share__label">%s</span>', esc_html( $label ) )
				: '';

			$btn_html = sprintf(
				'%1$s<a class="squad-social-share__btn squad-social-share__btn--%2$s" %3$s aria-label="%4$s">%5$s%6$s</a>',
				'' !== $inline_css ? sprintf( '<style>%s</style>', $inline_css ) : '',
				esc_attr( $network ),
				$link_attrs,
				/* translators: %s: network label */
				esc_attr( sprintf( esc_html__( 'Share on %s', 'squad-modules-for-divi' ), $meta['label'] ) ),
				$icon_html,
				$label_html
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
					'children'            => $style_components . $btn_html,
				)
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Social Share Item module' );

			return '';
		}
	}

	protected static function get_instance_uid( WP_Block $block ): string {
		$raw = (string) ( $block->parsed_block['id'] ?? '' );
		$uid = preg_replace( '/[^a-z0-9]/', '', strtolower( $raw ) );

		return '' !== $uid
			? 'squad-ss-' . $uid
			: 'squad-ss-' . substr( md5( $raw ), 0, 10 );
	}

	/**
	 * Build the per-item inline color CSS.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $item        Item settings (network, color overrides, etc.).
	 * @param string               $brand_color Default brand color from the Networks registry.
	 * @param string               $uid         Unique instance identifier used in the selector.
	 *
	 * @return string Inline CSS rule (may be empty).
	 */
	protected static function get_color_css( array $item, string $brand_color, string $uid ): string {
		$use_custom = 'on' === ( $item['useCustomColors'] ?? 'off' );

		$bg = $brand_color; // trusted hardcoded hex from Networks registry — no sanitize needed.
		if ( $use_custom ) {
			$override = self::sanitize_css_background( (string) ( $item['bgColorOverride'] ?? '' ) );
			if ( '' !== $override ) {
				$bg = $override;
			}
		}

		$decl = '';
		if ( '' !== $bg ) {
			$decl .= 'background-color:' . $bg . ';';
		}
		if ( $use_custom ) {
			$icon = self::sanitize_css_background( (string) ( $item['iconColorOverride'] ?? '' ) );
			if ( '' !== $icon ) {
				$decl .= 'color:' . $icon . ';';
			}
		}

		return '' !== $decl ? ".{$uid} .squad-social-share__btn{{$decl}}" : '';
	}
}
