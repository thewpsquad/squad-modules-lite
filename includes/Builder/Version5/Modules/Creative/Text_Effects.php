<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Text Effects Module (Divi 5 / Block API).
 *
 * @since   4.4.0
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
use function esc_html;
use function esc_url;
use function in_array;
use function sprintf;
use function tag_escape;

/**
 * Text Effects Module class.
 *
 * @since 4.4.0
 */
class Text_Effects extends Module {

	/**
	 * The allowed HTML tags for the text element.
	 *
	 * @var string[]
	 */
	private const ALLOWED_TAGS = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span', 'div' );

	/**
	 * The allowed style types.
	 *
	 * @var string[]
	 */
	private const ALLOWED_STYLE_TYPES = array( 'image_mask', 'stroke', 'gradient_animated' );

	/**
	 * Relative path to the generated module.json metadata folder.
	 *
	 * @since 4.4.0
	 *
	 * @return string
	 */
	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/text-effects/';
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
		$args['classnamesInstance']->add( 'disq_text_effects' );
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
	 * @since 4.4.0
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
	 * @since 4.4.0
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
	 * Build the inline `style` attribute value (CSS custom properties) for the
	 * active style_type. All dynamic values are sanitized before interpolation.
	 *
	 * @since 4.4.0
	 *
	 * @param array<string, mixed> $inner      Inner content values.
	 * @param string                $style_type The active style type.
	 *
	 * @return string
	 */
	private static function build_style( array $inner, string $style_type ): string {
		if ( 'image_mask' === $style_type ) {
			$mask_image = (string) ( $inner['maskImage'] ?? '' );
			if ( '' === $mask_image ) {
				return '';
			}

			return sprintf( '--tfx-mask-image:url(%s);', esc_url( $mask_image ) );
		}

		if ( 'stroke' === $style_type ) {
			$stroke_width   = self::sanitize_css_length( (string) ( $inner['strokeWidth'] ?? '2px' ), '2px' );
			$stroke_color   = self::sanitize_css_background( (string) ( $inner['strokeColor'] ?? '#2ea3f2' ) );
			$fill_color_raw = (string) ( $inner['fillColor'] ?? '' );
			$fill_color     = '' !== $fill_color_raw ? self::sanitize_css_background( $fill_color_raw ) : 'transparent';

			return sprintf(
				'--tfx-stroke-width:%1$s;--tfx-stroke-color:%2$s;--tfx-fill-color:%3$s;',
				$stroke_width,
				$stroke_color,
				$fill_color
			);
		}

		// gradient_animated: only the animation duration is a CSS var here;
		// the gradient background image is appended separately (see build_gradient()).
		$speed_raw = (string) ( $inner['animationSpeed'] ?? '4' );

		// sanitize_css_length()'s unit whitelist doesn't include seconds, so
		// animation_speed is sanitized locally: strip everything but digits/dot.
		$speed_value = (float) preg_replace( '/[^0-9.]/', '', $speed_raw );
		$speed_value = $speed_value > 0 ? $speed_value : 4.0;

		return sprintf( '--tfx-speed:%ss;', $speed_value );
	}

	/**
	 * Build the CSS gradient image declaration from the inner content values.
	 *
	 * @since 4.4.0
	 *
	 * @param array<string, mixed> $inner Inner content values.
	 *
	 * @return string
	 */
	private static function build_gradient( array $inner ): string {
		$type      = $inner['gradientType'] ?? 'linear';
		$direction = self::sanitize_css_background( (string) ( $inner['gradientDirection'] ?? '90deg' ) );
		$start     = self::sanitize_css_background( (string) ( $inner['gradientStart'] ?? '#1f7016' ) );
		$end       = self::sanitize_css_background( (string) ( $inner['gradientEnd'] ?? '#29c4a9' ) );

		if ( 'radial' === $type ) {
			return sprintf( 'radial-gradient(circle, %1$s 0%%, %2$s 100%%)', $start, $end );
		}

		return sprintf( 'linear-gradient(%1$s, %2$s 0%%, %3$s 100%%)', $direction, $start, $end );
	}

	/**
	 * Render callback for the Text Effects module.
	 *
	 * @since 4.4.0
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
			$text  = $inner['textContent'] ?? '';

			if ( '' === $text ) {
				return '';
			}

			$tag = $inner['textTag'] ?? 'h2';
			if ( ! in_array( $tag, self::ALLOWED_TAGS, true ) ) {
				$tag = 'h2';
			}

			$style_type = $inner['styleType'] ?? 'image_mask';
			if ( ! in_array( $style_type, self::ALLOWED_STYLE_TYPES, true ) ) {
				$style_type = 'image_mask';
			}

			$style = self::build_style( $inner, $style_type );

			if ( 'gradient_animated' === $style_type ) {
				$style .= sprintf( 'background-image:%s;', self::build_gradient( $inner ) );
			}

			$wrapper_classes = "text-effects-wrapper text-effects-style--$style_type et_pb_with_background";
			if ( 'gradient_animated' === $style_type && 'on' === ( $inner['pauseOnHover'] ?? 'off' ) ) {
				$wrapper_classes .= ' tfx-pause-hover';
			}

			$html = sprintf(
				'<div class="%1$s"><%2$s class="text-effects-element" style="%3$s">%4$s</%2$s></div>',
				esc_attr( $wrapper_classes ),
				tag_escape( $tag ),
				esc_attr( $style ),
				esc_html( $text )
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
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Text Effects module' );

			return '';
		}
	}
}
