<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Floating Image (child) Module (Divi 5 / Block API).
 *
 * A single absolutely-positioned image that bobs forever via a CSS keyframe
 * loop. No frontend JS.
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

use DiviSquad\Builder\Shared\Modules\Creative\Floating_Images\Float_Helper;
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
use function max;
use function min;
use function sprintf;
use function trim;

/**
 * Floating Image (child) module class (Divi 5).
 *
 * @since 4.1.0
 */
class Floating_Image extends Module {

	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/floating-image/';
	}

	/**
	 * Add module classnames.
	 *
	 * @since 4.1.0
	 *
	 * @param array<string, mixed> $args Classnames arguments provided by Divi.
	 *
	 * @return void
	 */
	public static function module_classnames( array $args ): void {
		$args['classnamesInstance']->add( 'squad-floating__item-wrapper' );
		$args['classnamesInstance']->add(
			ElementClassnames::classnames(
				array( 'attrs' => $args['attrs']['module']['decoration'] ?? array() )
			)
		);
	}

	/**
	 * Add module script data.
	 *
	 * @since 4.1.0
	 *
	 * @param array<string, mixed> $args Script data arguments provided by Divi.
	 *
	 * @return void
	 */
	public static function module_script_data( array $args ): void {
		$args['elements']->script_data( array( 'attrName' => 'module' ) );
	}

	/**
	 * Add module styles.
	 *
	 * @since 4.1.0
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
									// ── Position + animation CSS custom properties ───────────
									array(
										'componentName' => 'divi/common',
										'props'         => array(
											'selector'            => "{$args['orderClass']} .squad-floating__item",
											'attr'                => $attrs['itemSettings']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												$v = $params['attrValue'] ?? array();

												return self::float_vars( $v );
											},
										),
									),
									// ── Image: max-width ────────────────────────────────────
									array(
										'componentName' => 'divi/common',
										'props'         => array(
											'selector'            => "{$args['orderClass']} .squad-floating__image",
											'attr'                => $attrs['itemSettings']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												$v = $params['attrValue'] ?? array();
												$w = self::sanitize_css_length( (string) ( $v['imageMaxWidth'] ?? '' ) );

												return '' !== $w ? "max-width:{$w};" : '';
											},
										),
									),
									// ── Image: max-height ───────────────────────────────────
									array(
										'componentName' => 'divi/common',
										'props'         => array(
											'selector'            => "{$args['orderClass']} .squad-floating__image",
											'attr'                => $attrs['itemSettings']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												$v = $params['attrValue'] ?? array();
												$h = self::sanitize_css_length( (string) ( $v['imageMaxHeight'] ?? '' ) );

												return '' !== $h ? "max-height:{$h};" : '';
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
	 * Build the CSS custom-property declaration block for a child's position +
	 * float animation, from validated/sanitized attribute values.
	 *
	 * Shared by Divi 5 (here) — the Divi 4 class mirrors this logic with
	 * set_style(). Always emits left/top/duration/delay/easing; emits the
	 * distance/rotate var(s) appropriate to the validated motion.
	 *
	 * @since 4.1.0
	 *
	 * @param array<string, mixed> $v Deserialized itemSettings inner content.
	 *
	 * @return string CSS declaration string (custom properties).
	 */
	private static function float_vars( array $v ): string {
		$left   = self::sanitize_css_length( (string) ( $v['horizontalPosition'] ?? '0%' ) );
		$top    = self::sanitize_css_length( (string) ( $v['verticalPosition'] ?? '0%' ) );
		$dist   = self::sanitize_css_length( (string) ( $v['floatDistance'] ?? '20px' ) );
		$dur    = self::absint_default( $v['duration'] ?? 4000, 4000 );
		$delay  = self::absint_default( $v['delay'] ?? 0, 0 );
		$rotate = self::clamp_angle( $v['rotateAngle'] ?? 8 );

		$raw_easing = (string) ( $v['easing'] ?? 'ease-in-out' );
		$easing     = Float_Helper::is_valid_easing( $raw_easing )
			? Float_Helper::easing_value( $raw_easing )
			: 'ease-in-out';

		$raw_motion = (string) ( $v['motionType'] ?? 'up-down' );
		$motion     = Float_Helper::is_valid_motion( $raw_motion ) ? $raw_motion : 'up-down';

		$out = '';
		$out .= '' !== $left ? "--squad-float-left:{$left};" : '';
		$out .= '' !== $top ? "--squad-float-top:{$top};" : '';
		$out .= "--squad-float-duration:{$dur}ms;";
		$out .= "--squad-float-delay:{$delay}ms;";
		$out .= "--squad-float-easing:{$easing};";

		if ( 'rotate' === $motion ) {
			$out .= "--squad-float-rotate:{$rotate}deg;";
		} elseif ( 'diagonal' === $motion ) {
			$d   = '' !== $dist ? $dist : '20px';
			$out .= "--squad-float-dist-x:{$d};--squad-float-dist-y:{$d};";
		} else {
			$d   = '' !== $dist ? $dist : '20px';
			$out .= "--squad-float-dist:{$d};";
		}

		return $out;
	}

	/**
	 * Render callback for the Floating Image (child) module.
	 *
	 * Reads camelCase keys from `itemSettings.innerContent.desktop.value.*`.
	 * Empty image → renders nothing (the child is skipped). No frontend JS.
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
			$item = $attrs['itemSettings']['innerContent']['desktop']['value'] ?? array();

			$src = self::resolve_upload_url( $item['image'] ?? '' );
			if ( '' === $src ) {
				return '';
			}

			$raw_motion = (string) ( $item['motionType'] ?? 'up-down' );
			$motion     = Float_Helper::is_valid_motion( $raw_motion ) ? $raw_motion : 'up-down';

			$alt = (string) ( $item['imageAlt'] ?? '' );
			$img = sprintf(
				'<img class="squad-floating__image" src="%s" alt="%s">',
				esc_url( $src ),
				esc_attr( $alt )
			);

			$inner = self::maybe_wrap_link( $img, $item );

			$style_components = $elements instanceof ModuleElements
				? $elements->style_components( array( 'attrName' => 'module' ) )
				: '';

			$item_html = sprintf(
				'<div class="squad-floating__item squad-floating__item--%s">%s</div>',
				esc_attr( $motion ),
				$inner
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
					'children'            => $style_components . $item_html,
				)
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Floating Image module' );

			return '';
		}
	}

	/**
	 * Optionally wrap $inner in an <a> tag when useLink=on and linkUrl non-empty.
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
		$rel        = Float_Helper::build_rel( $new_window );

		$attrs = sprintf( 'class="squad-floating__link" href="%s"', esc_url( $link_url ) );
		if ( $new_window ) {
			$attrs .= ' target="_blank"';
		}
		if ( '' !== $rel ) {
			$attrs .= sprintf( ' rel="%s"', esc_attr( $rel ) );
		}

		return sprintf( '<a %s>%s</a>', $attrs, $inner );
	}

	/**
	 * Absolute-int with a default when the value is empty/non-numeric.
	 *
	 * @since 4.1.0
	 *
	 * @param mixed $value   Raw attribute value.
	 * @param int   $default Fallback when the value is empty or null.
	 *
	 * @return int
	 */
	private static function absint_default( $value, int $default ): int {
		if ( '' === $value || null === $value ) {
			return $default;
		}

		return (int) abs( (int) $value );
	}

	/**
	 * Clamp a signed angle to the [-90, 90] degree range.
	 *
	 * @since 4.1.0
	 *
	 * @param mixed $value Raw angle attribute value.
	 *
	 * @return int
	 */
	private static function clamp_angle( $value ): int {
		return (int) max( - 90, min( 90, (int) $value ) );
	}
}
