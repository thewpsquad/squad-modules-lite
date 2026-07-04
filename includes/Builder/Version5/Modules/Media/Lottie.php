<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Lottie Image Module (Divi 5 / Block API).
 *
 * Native Divi 5 implementation of the Lottie Image module. Renders the
 * `.squad-lottie-wrapper > .squad-lottie-player.lottie-player-container` markup
 * server-side via the render callback and enqueues the existing D4 lottie-web
 * driven frontend script so the animation works on the frontend.
 *
 * @since   3.4.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version5\Modules\Media;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

// Bail when the Divi 5 framework is not present (e.g. running under Divi 4).
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

/**
 * Lottie Image Module class.
 *
 * @since 3.4.0
 */
class Lottie extends Module {

	/**
	 * Relative path to the generated module.json metadata folder.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/lottie/';
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
		$args['classnamesInstance']->add( 'disq_lottie' );
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
					// Module.
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
					// Lottie player — color / width / height applied as inline declarations.
					$elements->style(
						array(
							'attrName'   => 'lottie',
							'styleProps' => array(
								'advancedStyles' => array(
									array(
										'componentName' => 'divi/common',
										'props'         => array(
											'selector'            => "{$args['orderClass']} .squad-lottie-wrapper .squad-lottie-player svg path",
											'attr'                => $attrs['lottie']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												$value = $params['attrValue'] ?? array();
												$color = $value['color'] ?? '';

												$color = self::sanitize_css_background( $color );
														return '' !== $color ? sprintf( 'fill: %s !important;', $color ) : '';
											},
										),
									),
									array(
										'componentName' => 'divi/common',
										'props'         => array(
											'selector'            => "{$args['orderClass']} .squad-lottie-wrapper .squad-lottie-player",
											'attr'                => $attrs['lottie']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												$value       = $params['attrValue'] ?? array();
												$declaration = '';

												if ( isset( $value['width'] ) && '' !== $value['width'] ) {
													$w = self::sanitize_css_length( (string) $value['width'] );
													if ( '' !== $w ) {
														$declaration .= sprintf( 'width: %s !important;', $w );
													}
												}
												if ( isset( $value['height'] ) && '' !== $value['height'] ) {
													$h = self::sanitize_css_length( (string) $value['height'] );
													if ( '' !== $h ) {
														$declaration .= sprintf( 'height: %s !important;', $h );
													}
												}

												return $declaration;
											},
										),
									),
								),
							),
						)
					),
					// Module - Custom CSS.
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
	 * Render callback for the Lottie Image module.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $attrs    Block attributes saved by the Visual Builder.
	 * @param string               $content  Inner (child) block content.
	 * @param WP_Block             $block    Parsed block instance.
	 * @param ModuleElements       $elements ModuleElements instance.
	 *
	 * @return string Rendered HTML.
	 */
	public static function render_callback( array $attrs, string $content, WP_Block $block, $elements ): string {
		try {
			$lottie_html = self::render_lottie( $attrs );

			if ( '' === $lottie_html ) {
				return '';
			}

			// Enqueue the lottie-web driven frontend script.
			wp_enqueue_script( 'squad-module-lottie' );

			$html = sprintf( '<div class="squad-lottie-wrapper">%1$s</div>', $lottie_html );

			return DiviModule::render(
				array(
					// FE only.
					'orderIndex'          => $block->parsed_block['orderIndex'],
					'storeInstance'       => $block->parsed_block['storeInstance'],

					// VB equivalent.
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
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Lottie Image module' );

			return '';
		}
	}

	/**
	 * Build the Lottie player HTML from Divi 5 attributes.
	 *
	 * Outputs the `.squad-lottie-player.lottie-player-container` element with the
	 * same `data-src` and `data-options` payload the D4 frontend script reads.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $attrs Block attributes.
	 *
	 * @return string
	 */
	private static function render_lottie( array $attrs ): string {
		$inner = $attrs['lottie']['innerContent']['desktop']['value'] ?? array();

		$src_type   = $inner['srcType'] ?? 'remote';
		$src_remote = (string) ( $inner['srcRemote'] ?? '' );
		$src_upload = self::resolve_upload_url( $inner['srcUpload'] ?? '' );

		if ( '' === $src_type || ( '' === $src_upload && '' === $src_remote ) ) {
			return '';
		}

		$lottie_src = 'local' === $src_type ? $src_upload : $src_remote;

		$module_references = array(
			'lottie_trigger_method'  => $inner['triggerMethod'] ?? 'freeze-click',
			'lottie_mouseout_action' => $inner['mouseoutAction'] ?? 'no_action',
			'lottie_click_action'    => $inner['clickAction'] ?? 'no_action',
			'lottie_scroll'          => $inner['scroll'] ?? 'row',
			'lottie_play_on_hover'   => $inner['playOnHover'] ?? 'off',
			'lottie_loop'            => $inner['loop'] ?? 'off',
			'lottie_loop_no_times'   => $inner['loopNoTimes'] ?? '0',
			'lottie_delay'           => $inner['delay'] ?? '0',
			'lottie_speed'           => $inner['speed'] ?? '1',
			'lottie_mode'            => $inner['mode'] ?? 'normal',
			'lottie_direction'       => $inner['direction'] ?? '1',
			'lottie_renderer'        => $inner['renderer'] ?? 'svg',
		);

		$data_options = wp_json_encode(
			array(
				'fieldPrefix'     => '',
				'moduleReference' => $module_references,
			)
		);

		return sprintf(
			'<div class="squad-lottie-player lottie-player-container" style="margin: 0px auto; outline: none; overflow: hidden;" data-src="%1$s" data-options="%2$s"></div>',
			esc_url( $lottie_src ),
			esc_attr( (string) $data_options )
		);
	}

}
