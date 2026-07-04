<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Text Highlighter Module (Divi 5 / Block API).
 *
 * Renders the same `.squad-highlight` shell as the Divi 4 module; the SVG
 * stroke-draw animation is driven client-side by `text-highlighter.ts`.
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

use DiviSquad\Builder\Version5\Abstracts\Module;
use DiviSquad\Builder\Shared\Modules\Creative\Text_Highlighter\Highlight_Helper;
use ET\Builder\FrontEnd\Module\Style;
use ET\Builder\Packages\Module\Module as DiviModule;
use ET\Builder\Packages\Module\Options\Css\CssStyle;
use ET\Builder\Packages\Module\Options\Element\ElementClassnames;
use Throwable;
use WP_Block;
use function absint;
use function esc_attr;
use function preg_replace;
use function sprintf;
use function str_replace;
use function trim;
use function wp_enqueue_script;

/**
 * Text Highlighter module class (Divi 5).
 *
 * @since 4.0.0
 */
class Text_Highlighter extends Module {

	/**
	 * Relative path to the generated module.json metadata folder.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/text-highlighter/';
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
		$args['classnamesInstance']->add( 'disq_text_highlighter' );
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
											'selector'            => "{$args['orderClass']} .squad-highlight__svg",
											'attr'                => $attrs['highlightStyle']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												$value = $params['attrValue'] ?? array();
												$color = self::sanitize_css_background( (string) ( $value['highlightColor'] ?? '' ) );
												return '' !== $color ? 'color: ' . $color . ';' : '';
											},
										),
									),
									array(
										'componentName' => 'divi/common',
										'props'         => array(
											'selector'            => "{$args['orderClass']} .squad-highlight__svg path",
											'attr'                => $attrs['highlightStyle']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												$value        = $params['attrValue'] ?? array();
												$stroke_width = absint( $value['strokeWidth'] ?? 3 );
												return $stroke_width > 0 ? 'stroke-width: ' . $stroke_width . ';' : '';
											},
										),
									),
									array(
										'componentName' => 'divi/common',
										'props'         => array(
											'selector'            => "{$args['orderClass']} .squad-highlight__svg",
											'attr'                => $attrs['highlightStyle']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												// Use margin-top instead of transform: translateY to avoid
												// conflicting with per-shape transforms (e.g. strikethrough
												// uses translateY(-50%)). margin-top composes with any
												// transform on an absolutely-positioned SVG element.
												$value          = $params['attrValue'] ?? array();
												$svg_position_y = (int) ( $value['svgPositionY'] ?? 0 );
												return 0 !== $svg_position_y
													? sprintf( 'margin-top: %dpx;', $svg_position_y )
													: '';
											},
										),
									),
									array(
										'componentName' => 'divi/common',
										'props'         => array(
											'selector'            => "{$args['orderClass']} .squad-highlight",
											'attr'                => $attrs['highlightStyle']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												$value   = $params['attrValue'] ?? array();
												$align   = (string) ( $value['contentAlign'] ?? '' );
												$allowed = array( 'left', 'center', 'right' );
												return in_array( $align, $allowed, true )
													? 'text-align: ' . $align . ';'
													: '';
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
	 * Render callback for the Text Highlighter module (Divi 5).
	 *
	 * Reads camelCase attrs from the module.json `highlightSettings.innerContent`
	 * group and calls the shared V4 build_shell().
	 *
	 * camelCase attr → PHP key contract:
	 *   prefixText      → $inner['prefixText']
	 *   highlightedText → $inner['highlightedText']
	 *   suffixText      → $inner['suffixText']
	 *   headingTag      → $inner['headingTag']
	 *   highlightType   → $inner['highlightType']
	 *   animate         → $inner['animate']
	 *   animDuration    → $inner['animDuration']
	 *   animDelay       → $inner['animDelay']
	 *   animLoop        → $inner['animLoop']
	 *   useGradient     → $inner['useGradient']
	 *   gradientStart   → $inner['gradientStart']
	 *   gradientEnd     → $inner['gradientEnd']
	 *   gradientAngle   → $inner['gradientAngle']
	 *   strokeWidth     → $style['strokeWidth']  (style group)
	 *   svgPositionY    → $style['svgPositionY'] (style group, CSS via module_styles)
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $attrs    Block attributes.
	 * @param string               $content  Inner block content.
	 * @param WP_Block             $block    Parsed block instance.
	 * @param object               $elements ModuleElements instance.
	 *
	 * @return string Rendered HTML.
	 */
	public static function render_callback( array $attrs, string $content, WP_Block $block, $elements ): string {
		try {
			wp_enqueue_script( 'squad-module-text-highlighter' );

			$inner = $attrs['highlightSettings']['innerContent']['desktop']['value'] ?? array();
			$style = $attrs['highlightStyle']['innerContent']['desktop']['value'] ?? array();

			$type    = (string) ( $inner['highlightType'] ?? 'underline' );
			$tag_raw = (string) ( $inner['headingTag'] ?? 'h2' );

			$config = array(
				'prefix'          => (string) ( $inner['prefixText'] ?? '' ),
				'highlighted'     => (string) ( $inner['highlightedText'] ?? 'highlighted' ),
				'suffix'          => (string) ( $inner['suffixText'] ?? '' ),
				'heading_tag'     => Highlight_Helper::is_valid_tag( $tag_raw ) ? $tag_raw : 'h2',
				'type'            => Highlight_Helper::is_valid_type( $type ) ? $type : 'underline',
				'animate'         => (string) ( $inner['animate'] ?? 'on' ),
				'anim_duration'   => (string) absint( $inner['animDuration'] ?? 1200 ),
				'anim_delay'      => (string) absint( $inner['animDelay'] ?? 0 ),
				'anim_loop'       => (string) ( $inner['animLoop'] ?? 'off' ),
				'highlight_color' => (string) ( $style['highlightColor'] ?? '#6a33d7' ),
				'use_gradient'    => (string) ( $inner['useGradient'] ?? 'off' ),
				'gradient_start'  => (string) ( $inner['gradientStart'] ?? '#6a33d7' ),
				'gradient_end'    => (string) ( $inner['gradientEnd'] ?? '#d433c4' ),
				'gradient_angle'  => (string) absint( $inner['gradientAngle'] ?? 90 ),
				'stroke_width'    => (string) absint( $style['strokeWidth'] ?? 3 ),
			);

			$shell = Highlight_Helper::build_shell( $config );

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
					'children'            => $elements->style_components( array( 'attrName' => 'module' ) ) . $shell,
				)
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Text Highlighter module' );

			return '';
		}
	}

	/**
	 * Sanitize a CSS color/background value.
	 *
	 * @since 4.0.0
	 *
	 * @param string $value Raw value.
	 *
	 * @return string
	 */
	private static function sanitize_css_background( string $value ): string {
		$value = trim( $value );
		if ( '' === $value ) {
			return '';
		}
		return (string) preg_replace( '/[{};<>\\\\"\']/', '', $value );
	}
}
