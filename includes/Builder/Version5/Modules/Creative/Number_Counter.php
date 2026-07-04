<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Number Counter Module (Divi 5 / Block API).
 *
 * Renders the same `.squad-counter` shell as the Divi 4 module; the count-up is
 * run client-side by `number-counter.ts`.
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
use DiviSquad\Builder\Shared\Modules\Creative\Number_Counter\Counter_Helper;
use ET\Builder\FrontEnd\Module\Style;
use ET\Builder\Packages\Module\Module as DiviModule;
use ET\Builder\Packages\Module\Options\Css\CssStyle;
use ET\Builder\Packages\Module\Options\Element\ElementClassnames;
use Throwable;
use WP_Block;
use function absint;
use function esc_attr;
use function esc_url;
use function et_pb_get_extended_font_icon_value;
use function in_array;
use function max;
use function min;
use function preg_replace;
use function sprintf;
use function trim;
use function wp_enqueue_script;

/**
 * Number Counter module class.
 *
 * @since 4.0.0
 */
class Number_Counter extends Module {

	/**
	 * Relative path to the generated module.json metadata folder.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/number-counter/';
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
		$args['classnamesInstance']->add( 'disq_number_counter' );
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
											'selector'            => "{$args['orderClass']} .squad-counter__number",
											'attr'                => $attrs['colors']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												$value = $params['attrValue'] ?? array();
												$color = self::sanitize_css_background( (string) ( $value['numberColor'] ?? '' ) );
												return '' !== $color ? 'color: ' . $color . ';' : '';
											},
										),
									),
									array(
										'componentName' => 'divi/common',
										'props'         => array(
											'selector'            => "{$args['orderClass']} .squad-counter__icon",
											'attr'                => $attrs['colors']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												$value = $params['attrValue'] ?? array();
												$color = self::sanitize_css_background( (string) ( $value['iconColor'] ?? '' ) );
												return '' !== $color ? 'color: ' . $color . ';' : '';
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
	 * Render callback for the Number Counter module.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $attrs    Block attributes saved by the Visual Builder.
	 * @param string               $content  Inner (child) block content.
	 * @param WP_Block             $block    Parsed block instance.
	 * @param object               $elements ModuleElements instance.
	 *
	 * @return string Rendered HTML.
	 */
	public static function render_callback( array $attrs, string $content, WP_Block $block, $elements ): string {
		try {
			wp_enqueue_script( 'squad-module-number-counter' );

			$inner = $attrs['counterSettings']['innerContent']['desktop']['value'] ?? array();

			$use_media  = (string) ( $inner['useMedia'] ?? 'none' );
			$media_html = '';
			if ( 'icon' === $use_media ) {
				$icon = (string) ( $inner['icon'] ?? '' );
				if ( '' !== $icon ) {
					$media_html = sprintf(
						'<span class="squad-counter__icon et-pb-icon">%1$s</span>',
						esc_attr( et_pb_get_extended_font_icon_value( $icon, true ) )
					);
				}
			} elseif ( 'image' === $use_media ) {
				$image = (string) ( $inner['image'] ?? '' );
				if ( '' !== $image ) {
					$media_html = sprintf( '<img class="squad-counter__icon" src="%1$s" alt="" />', esc_url( $image ) );
				}
			}

			$easing = (string) ( $inner['easing'] ?? 'ease-out' );

			$config = array(
				'start'       => (string) (float) ( $inner['startNumber'] ?? 0 ),
				'end'         => (string) (float) ( $inner['endNumber'] ?? 100 ),
				'duration'    => (string) max( 100, min( 10000, absint( $inner['duration'] ?? 2000 ) ) ),
				'easing'      => Counter_Helper::is_valid_easing( $easing ) ? $easing : 'ease-out',
				'separator'   => Counter_Helper::separator_char( (string) ( $inner['thousandsSeparator'] ?? 'comma' ) ),
				'decimal_sep' => Counter_Helper::decimal_char( (string) ( $inner['decimalSeparator'] ?? 'dot' ) ),
				'decimals'    => (string) max( 0, min( 4, absint( $inner['decimalPlaces'] ?? 0 ) ) ),
			);

			$shell = Counter_Helper::build_shell(
				$config,
				$use_media,
				(string) ( $inner['mediaPosition'] ?? 'above' ),
				(string) ( $inner['title'] ?? '' ),
				(string) ( $inner['titlePosition'] ?? 'below' ),
				(string) ( $inner['numberPrefix'] ?? '' ),
				(string) ( $inner['numberSuffix'] ?? '' ),
				$media_html
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
					'children'            => $elements->style_components( array( 'attrName' => 'module' ) ) . $shell,
				)
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Number Counter module' );

			return '';
		}
	}

	/**
	 * Sanitize a CSS background/color value (hex, rgba, gradient, etc.).
	 *
	 * Strips characters that could break out of the CSS declaration context
	 * (`{ } ; < > \ " '`). NEVER use esc_attr on a CSS value.
	 *
	 * @since 4.0.0
	 *
	 * @param string $value Raw value.
	 *
	 * @return string Sanitized value (may be empty).
	 */
	private static function sanitize_css_background( string $value ): string {
		$value = trim( $value );
		if ( '' === $value ) {
			return '';
		}
		return (string) preg_replace( '/[{};<>\\\\"\']/', '', $value );
	}
}
