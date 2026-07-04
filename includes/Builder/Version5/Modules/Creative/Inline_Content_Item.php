<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Inline Content Item (child) Module (Divi 5 / Block API).
 *
 * @since   4.1.0
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

use DiviSquad\Builder\Shared\Modules\Creative\Inline_Content\Inline_Helper;
use DiviSquad\Builder\Version5\Abstracts\Module;
use DiviSquad\Utils\Divi;
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
use function et_pb_get_extended_font_icon_value;
use function in_array;
use function sprintf;
use function trim;
use function wp_kses_post;

/**
 * Inline Content Item (child) module class (Divi 5).
 *
 * @since 4.1.0
 */
class Inline_Content_Item extends Module {

	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/inline-content-item/';
	}

	/**
	 * Add CSS classnames to the module wrapper.
	 *
	 * @param array<string, mixed> $args Divi module classnames args.
	 *
	 * @return void
	 */
	public static function module_classnames( array $args ): void {
		$args['classnamesInstance']->add( 'squad-inline__item-wrapper' );
		$args['classnamesInstance']->add(
			ElementClassnames::classnames(
				array( 'attrs' => $args['attrs']['module']['decoration'] ?? array() )
			)
		);
	}

	/**
	 * Register module script data.
	 *
	 * @param array<string, mixed> $args Divi module script data args.
	 *
	 * @return void
	 */
	public static function module_script_data( array $args ): void {
		$args['elements']->script_data( array( 'attrName' => 'module' ) );
	}

	/**
	 * Register module styles.
	 *
	 * @param array<string, mixed> $args Divi module styles args.
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
									// ── Icon: color ─────────────────────────────────────────
									array(
										'componentName' => 'divi/common',
										'props'         => array(
											'selector'            => "{$args['orderClass']} .squad-inline__icon .et-pb-icon",
											'attr'                => $attrs['itemSettings']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												$value = $params['attrValue'] ?? array();
												$color = self::sanitize_css_background( (string) ( $value['iconColor'] ?? '' ) );

												return '' !== $color ? "color:{$color};" : '';
											},
										),
									),
									// ── Icon: size ──────────────────────────────────────────
									array(
										'componentName' => 'divi/common',
										'props'         => array(
											'selector'            => "{$args['orderClass']} .squad-inline__icon .et-pb-icon",
											'attr'                => $attrs['itemSettings']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												$value = $params['attrValue'] ?? array();
												$size  = self::sanitize_css_length( (string) ( $value['iconSize'] ?? '' ) );

												return '' !== $size ? "font-size:{$size};" : '';
											},
										),
									),
									// ── Image: width ────────────────────────────────────────
									array(
										'componentName' => 'divi/common',
										'props'         => array(
											'selector'            => "{$args['orderClass']} .squad-inline__image",
											'attr'                => $attrs['itemSettings']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												$value = $params['attrValue'] ?? array();
												$w     = self::sanitize_css_length( (string) ( $value['imageWidth'] ?? '' ) );

												return '' !== $w ? "width:{$w};" : '';
											},
										),
									),
									// ── Image: height ───────────────────────────────────────
									array(
										'componentName' => 'divi/common',
										'props'         => array(
											'selector'            => "{$args['orderClass']} .squad-inline__image",
											'attr'                => $attrs['itemSettings']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												$value = $params['attrValue'] ?? array();
												$h     = self::sanitize_css_length( (string) ( $value['imageHeight'] ?? '' ) );

												return '' !== $h ? "height:{$h};" : '';
											},
										),
									),
									// ── Image: border-radius ────────────────────────────────
									array(
										'componentName' => 'divi/common',
										'props'         => array(
											'selector'            => "{$args['orderClass']} .squad-inline__image",
											'attr'                => $attrs['itemSettings']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												$value = $params['attrValue'] ?? array();
												$r     = self::sanitize_css_length( (string) ( $value['imageRadius'] ?? '' ) );

												return '' !== $r ? "border-radius:{$r};" : '';
											},
										),
									),
									// ── Divider: height (length) ────────────────────────────
									array(
										'componentName' => 'divi/common',
										'props'         => array(
											'selector'            => "{$args['orderClass']} .squad-inline__divider",
											'attr'                => $attrs['itemSettings']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												$value = $params['attrValue'] ?? array();
												$len   = self::sanitize_css_length( (string) ( $value['dividerLength'] ?? '' ) );

												return '' !== $len ? "height:{$len};" : '';
											},
										),
									),
									// ── Divider line: border-top-width (thickness) ──────────
									array(
										'componentName' => 'divi/common',
										'props'         => array(
											'selector'            => "{$args['orderClass']} .squad-inline__divider.squad-inline__divider--line",
											'attr'                => $attrs['itemSettings']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												$value = $params['attrValue'] ?? array();
												$thick = self::sanitize_css_length( (string) ( $value['dividerThickness'] ?? '' ) );

												return '' !== $thick ? "border-top-width:{$thick};" : '';
											},
										),
									),
									// ── Divider: color (background-color + border-color) ────
									array(
										'componentName' => 'divi/common',
										'props'         => array(
											'selector'            => "{$args['orderClass']} .squad-inline__divider",
											'attr'                => $attrs['itemSettings']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												$value = $params['attrValue'] ?? array();
												$color = self::sanitize_css_background( (string) ( $value['dividerColor'] ?? '' ) );

												return '' !== $color ? "background-color:{$color};border-color:{$color};" : '';
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
	 * Render callback for the Inline Content Item (child) module.
	 *
	 * Reads camelCase keys from `itemSettings.innerContent.desktop.value.*`.
	 * These MUST match the subNames in module.json-source.ts (see the attr
	 * contract table at the top of this plan). No frontend JS.
	 *
	 * @since 4.1.0
	 *
	 * @param array<string, mixed> $attrs    Block attributes.
	 * @param string               $content  Inner block content (unused).
	 * @param WP_Block             $block    Parsed block instance.
	 * @param object               $elements ModuleElements instance.
	 *
	 * @return string Rendered HTML.
	 */
	public static function render_callback( array $attrs, string $content, WP_Block $block, $elements ): string {
		try {
			// camelCase keys — MUST match subNames in module.json-source.ts.
			$item     = $attrs['itemSettings']['innerContent']['desktop']['value'] ?? array();
			$raw_type = (string) ( $item['contentType'] ?? 'text' );

			// Validate against allowlist — never inject raw into class names.
			$type = Inline_Helper::is_valid_type( $raw_type ) ? $raw_type : 'text';

			$inner_html = self::render_type( $type, $item );

			// Empty-content guard: skip the child entirely when required content is empty.
			if ( '' === $inner_html ) {
				return '';
			}

			$item_html = sprintf(
				'<span class="squad-inline__item squad-inline__item--%s">%s</span>',
				esc_attr( $type ),
				$inner_html
			);

			$style_components = $elements instanceof ModuleElements
				? $elements->style_components( array( 'attrName' => 'module' ) )
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
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Inline Content Item module' );

			return '';
		}
	}

	/**
	 * Dispatch rendering to the correct per-type method.
	 *
	 * @since 4.1.0
	 *
	 * @param string               $type Validated content type.
	 * @param array<string, mixed> $item Deserialized itemSettings inner content.
	 *
	 * @return string Inner HTML (empty = skip child).
	 */
	protected static function render_type( string $type, array $item ): string {
		switch ( $type ) {
			case 'text':
				return self::render_text( $item );
			case 'icon':
				return self::render_icon( $item );
			case 'image':
				return self::render_image( $item );
			case 'button':
				return self::render_button( $item );
			case 'divider':
				return self::render_divider( $item );
			default:
				return '';
		}
	}

	/** @param array<string, mixed> $item */
	protected static function render_text( array $item ): string {
		$text = (string) ( $item['text'] ?? '' );
		if ( '' === $text ) {
			return '';
		}
		$span = sprintf( '<span class="squad-inline__text">%s</span>', esc_html( $text ) );

		return self::maybe_wrap_link( $span, $item );
	}

	/** @param array<string, mixed> $item */
	protected static function render_icon( array $item ): string {
		$icon_raw = (string) ( $item['icon'] ?? '' );
		if ( '' === $icon_raw ) {
			return '';
		}

		Divi::inject_fa_icons( $icon_raw );

		$icon_value = et_pb_get_extended_font_icon_value( $icon_raw, true );

		$html = sprintf(
			'<span class="squad-inline__icon"><span class="et-pb-icon">%s</span></span>',
			wp_kses_post( $icon_value )
		);

		return self::maybe_wrap_link( $html, $item );
	}

	/** @param array<string, mixed> $item */
	protected static function render_image( array $item ): string {
		$src = self::resolve_upload_url( $item['image'] ?? '' );
		if ( '' === $src ) {
			return '';
		}

		$alt  = (string) ( $item['imageAlt'] ?? '' );
		$html = sprintf(
			'<img class="squad-inline__image" src="%s" alt="%s">',
			esc_url( $src ),
			esc_attr( $alt )
		);

		return self::maybe_wrap_link( $html, $item );
	}

	/** @param array<string, mixed> $item */
	protected static function render_button( array $item ): string {
		$button_text = (string) ( $item['buttonText'] ?? '' );
		$button_url  = (string) ( $item['buttonUrl'] ?? '' );

		// Skip when both are empty.
		if ( '' === $button_text && '' === $button_url ) {
			return '';
		}

		$new_window = 'on' === (string) ( $item['buttonNewWindow'] ?? 'off' );
		$rel        = Inline_Helper::build_rel( $new_window );

		$link_attrs = sprintf( 'href="%s"', esc_url( $button_url ) );
		if ( $new_window ) {
			$link_attrs .= ' target="_blank"';
		}
		if ( '' !== $rel ) {
			$link_attrs .= sprintf( ' rel="%s"', esc_attr( $rel ) );
		}

		return sprintf(
			'<a class="squad-inline__button et_pb_button" %s>%s</a>',
			$link_attrs,
			esc_html( $button_text )
		);
	}

	/** @param array<string, mixed> $item */
	protected static function render_divider( array $item ): string {
		$raw_style     = (string) ( $item['dividerStyle'] ?? 'line' );
		$divider_style = in_array( $raw_style, array( 'line', 'dot' ), true ) ? $raw_style : 'line';

		return sprintf(
			'<span class="squad-inline__divider squad-inline__divider--%s"></span>',
			esc_attr( $divider_style )
		);
	}

	/**
	 * Optionally wrap $inner in an <a> tag.
	 *
	 * @param string               $inner Inner HTML to wrap.
	 * @param array<string, mixed> $item  Item attrs.
	 *
	 * @return string Wrapped or unchanged HTML.
	 */
	protected static function maybe_wrap_link( string $inner, array $item ): string {
		if ( 'on' !== (string) ( $item['useLink'] ?? 'off' ) ) {
			return $inner;
		}

		$link_url = (string) ( $item['linkUrl'] ?? '' );
		if ( '' === $link_url ) {
			return $inner;
		}

		$new_window = 'on' === (string) ( $item['linkNewWindow'] ?? 'off' );
		$rel        = Inline_Helper::build_rel( $new_window );

		$attrs = sprintf( 'href="%s"', esc_url( $link_url ) );
		if ( $new_window ) {
			$attrs .= ' target="_blank"';
		}
		if ( '' !== $rel ) {
			$attrs .= sprintf( ' rel="%s"', esc_attr( $rel ) );
		}

		return sprintf( '<a %s>%s</a>', $attrs, $inner );
	}
}
