<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Divider Module (Divi 5 / Block API).
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
use function wp_enqueue_script;
use function wp_json_encode;
use function wp_kses_post;

/**
 * Divider Module class.
 *
 * @since 3.4.0
 */
class Divider extends Module {

	/**
	 * Relative path to the generated module.json metadata folder.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/divider/';
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
		$args['classnamesInstance']->add( 'disq_divider' );
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
	 * Render callback for the Divider module.
	 *
	 * Mirrors the Divi 4 markup so the existing frontend CSS/JS applies:
	 * `.divider-elements > .divider-item.divider-element.left + .divider-icon-wrapper + .divider-item.divider-element.right`.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $attrs    Block attributes.
	 * @param string               $content  Inner content.
	 * @param WP_Block             $block    Parsed block instance.
	 * @param ModuleElements       $elements ModuleElements instance.
	 *
	 * @return string Rendered HTML.
	 */
	public static function render_callback( array $attrs, string $content, WP_Block $block, $elements ): string {
		try {
			$inner = $attrs['content']['innerContent']['desktop']['value'] ?? array();

			$divider_type      = $inner['dividerType'] ?? 'icon';
			$divider_icon_type = $inner['dividerIconType'] ?? 'icon';
			$divider_position  = $inner['dividerPosition'] ?? 'center';

			// JS-driven only when the icon type is lottie.
			if ( 'lottie' === $divider_icon_type ) {
				// Load the lottie library for lottie image in the frontend.
				wp_enqueue_script( 'squad-module-lottie' );
				// Load the module scripts for frontend rendering.
				wp_enqueue_script( 'squad-module-divider' );
			}

			$html = self::squad_render_divider( $inner, $divider_type, $divider_icon_type, $divider_position );

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
					'children'            => $elements->style_components( array( 'attrName' => 'module' ) ) . $html,
				)
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Divider module' );

			return '';
		}
	}

	/**
	 * Render the divider wrapper markup.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $inner             Inner content values.
	 * @param string               $divider_type      Divider type (none|text|icon).
	 * @param string               $divider_icon_type Icon type (none|icon|image|lottie).
	 * @param string               $divider_position  Line position class.
	 *
	 * @return string
	 */
	protected static function squad_render_divider( array $inner, string $divider_type, string $divider_icon_type, string $divider_position ): string {
		// Map the stored flex value to the alignment class the stylesheet uses
		// (`.left` hides the left line → icon at start; `.right` → icon at end).
		$position_map      = array(
			'flex-start' => 'left',
			'center'     => 'center',
			'flex-end'   => 'right',
		);
		$position_class    = $position_map[ $divider_position ] ?? 'center';

		$wrapper_classes = array(
			'divider-elements',
			'et_pb_with_background',
			$position_class,
			$divider_type,
			'solid',
		);
		$wrapper_classes = array_map( 'sanitize_html_class', $wrapper_classes );

		$divider_left  = array( 'divider-item', 'divider-element', 'left' );
		$divider_right = array( 'divider-item', 'divider-element', 'right' );

		$icon = 'none' !== $divider_type
			? self::squad_render_divider_icon( $inner, $divider_type, $divider_icon_type )
			: '';

		$content = sprintf(
			'<span class="%1$s"><hr/></span>%3$s<span class="%2$s"><hr/></span>',
			esc_attr( implode( ' ', $divider_left ) ),
			esc_attr( implode( ' ', $divider_right ) ),
			$icon
		);

		return sprintf(
			'<div class="%2$s">%1$s</div>',
			$content,
			esc_attr( implode( ' ', $wrapper_classes ) )
		);
	}

	/**
	 * Render the divider icon wrapper.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $inner             Inner content values.
	 * @param string               $divider_type      Divider type (none|text|icon).
	 * @param string               $divider_icon_type Icon type (none|icon|image|lottie).
	 *
	 * @return string
	 */
	protected static function squad_render_divider_icon( array $inner, string $divider_type, string $divider_icon_type ): string {
		$element = '';

		if ( 'text' === $divider_type ) {
			$text = $inner['dividerIconText'] ?? '';

			$element = sprintf( '<span class="divider-icon-text">%1$s</span>', wp_kses_post( $text ) );
		} elseif ( 'icon' === $divider_type ) {
			switch ( $divider_icon_type ) {
				case 'icon':
					$icon_char = self::resolve_icon( $inner['dividerIcon'] ?? array() );
					if ( '' !== $icon_char ) {
						$element = sprintf(
							'<span class="et-pb-icon divider-icon">%s</span>',
							esc_html( $icon_char )
						);
					}
					break;

				case 'image':
					$image = self::resolve_upload_url( $inner['dividerImage'] ?? '' );
					$alt   = $inner['dividerImageAlt'] ?? '';

					if ( '' !== $image ) {
						$element = sprintf(
							'<img class="divider-image et_pb_image_wrap" src="%1$s" alt="%2$s" />',
							esc_url( $image ),
							esc_attr( $alt )
						);
					}
					break;

				case 'lottie':
					$element = self::squad_render_divider_icon_lottie( $inner );
					break;
			}
		}

		return sprintf(
			'<span class="divider-icon-wrapper"><span class="icon-element">%1$s</span></span>',
			$element
		);
	}

	/**
	 * Render the divider lottie player markup.
	 *
	 * Outputs the same data-attributes the Divi 4 frontend script reads.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $inner Inner content values.
	 *
	 * @return string
	 */
	protected static function squad_render_divider_icon_lottie( array $inner ): string {
		$lottie_type = $inner['lottieSrcType'] ?? 'remote';
		$lottie_src  = 'local' === $lottie_type
			? ( $inner['lottieSrcUpload'] ?? '' )
			: ( $inner['lottieSrcRemote'] ?? '' );

		$module_references = array(
			'lottie_trigger_method'  => 'freeze-click',
			'lottie_mouseout_action' => 'no_action',
			'lottie_click_action'    => 'no_action',
			'lottie_scroll'          => 'row',
			'lottie_play_on_hover'   => 'off',
			'lottie_loop'            => 'off',
			'lottie_loop_no_times'   => '0',
			'lottie_delay'           => '0',
			'lottie_speed'           => '1',
			'lottie_mode'            => 'normal',
			'lottie_direction'       => '1',
			'lottie_renderer'        => 'svg',
		);

		$options = wp_json_encode(
			array(
				'fieldPrefix'     => '',
				'moduleReference' => $module_references,
			)
		);

		return sprintf(
			'<span class="squad-lottie-player lottie-player-container" data-src="%1$s" data-options=\'%2$s\'></span>',
			esc_url( $lottie_src ),
			esc_attr( (string) $options )
		);
	}
}
