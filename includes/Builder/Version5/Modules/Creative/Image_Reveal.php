<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Image Reveal Module (Divi 5 / Block API).
 *
 * Renders the same `.squad-image-reveal` figure as the Divi 4 module; the reveal
 * is driven client-side by `image-reveal.ts`.
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

use DiviSquad\Builder\Shared\Modules\Creative\Image_Reveal\Reveal_Helper;
use DiviSquad\Builder\Version5\Abstracts\Module;
use ET\Builder\FrontEnd\Module\Style;
use ET\Builder\Packages\Module\Layout\Components\ModuleElements\ModuleElements;
use ET\Builder\Packages\Module\Module as DiviModule;
use ET\Builder\Packages\Module\Options\Css\CssStyle;
use ET\Builder\Packages\Module\Options\Element\ElementClassnames;
use Throwable;
use WP_Block;
use function absint;
use function preg_replace;
use function sprintf;
use function trim;
use function wp_enqueue_script;

/**
 * Image Reveal module class (Divi 5).
 *
 * @since 4.1.0
 */
class Image_Reveal extends Module {

	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/image-reveal/';
	}

	/**
	 * Add the module classnames.
	 *
	 * @param array<string, mixed> $args Classnames arguments.
	 *
	 * @return void
	 */
	public static function module_classnames( array $args ): void {
		$args['classnamesInstance']->add( 'disq_image_reveal' );
		$args['classnamesInstance']->add(
			ElementClassnames::classnames(
				array( 'attrs' => $args['attrs']['module']['decoration'] ?? array() )
			)
		);
	}

	/**
	 * Assign the module's frontend script data.
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
									// ── reveal timing vars (revealSettings group) ─────────────
									array(
										'componentName' => 'divi/common',
										'props'         => array(
											'selector'            => "{$args['orderClass']} .squad-image-reveal",
											'attr'                => $attrs['revealSettings']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												$v        = $params['attrValue'] ?? array();
												$duration = absint( $v['duration'] ?? 600 );
												$delay    = absint( $v['delay'] ?? 0 );
												$raw_ease = (string) ( $v['easing'] ?? 'ease-in-out' );
												$easing   = Reveal_Helper::is_valid_easing( $raw_ease )
													? Reveal_Helper::easing_value( $raw_ease )
													: 'ease-in-out';

												return sprintf(
													'--squad-ir-duration:%dms;--squad-ir-delay:%dms;--squad-ir-easing:%s;',
													$duration,
													$delay,
													$easing
												);
											},
										),
									),
									// ── hover-zoom scale var (revealStyleGroup group) ─────────
									array(
										'componentName' => 'divi/common',
										'props'         => array(
											'selector'            => "{$args['orderClass']} .squad-image-reveal",
											'attr'                => $attrs['revealStyleGroup']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												$v    = $params['attrValue'] ?? array();
												$zoom = self::sanitize_scale( (string) ( $v['zoomScale'] ?? '1.1' ) );

												return sprintf( '--squad-ir-zoom:%s;', $zoom );
											},
										),
									),
									// ── overlay color (revealStyleGroup group) ────────────────
									array(
										'componentName' => 'divi/common',
										'props'         => array(
											'selector'            => "{$args['orderClass']} .squad-image-reveal__overlay",
											'attr'                => $attrs['revealStyleGroup']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												$v     = $params['attrValue'] ?? array();
												$color = self::sanitize_css_background( (string) ( $v['revealColor'] ?? '' ) );

												return '' !== $color ? "background:{$color};" : '';
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
	 * Render callback for the Image Reveal module (Divi 5).
	 *
	 * Reads camelCase attrs from `revealSettings.innerContent` and calls the
	 * shared V4 build_shell(). Per-instance CSS is emitted by module_styles,
	 * scoped by orderClass.
	 *
	 * @since 4.1.0
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
			$inner = $attrs['revealSettings']['innerContent']['desktop']['value'] ?? array();

			$image = self::resolve_upload_url( $inner['image'] ?? '' );
			if ( '' === $image ) {
				return '';
			}

			wp_enqueue_script( 'squad-module-image-reveal' );

			// hoverZoom lives in the design group (revealStyleGroup), not revealSettings.
			$style_v = $attrs['revealStyleGroup']['innerContent']['desktop']['value'] ?? array();

			$trigger = (string) ( $inner['trigger'] ?? 'scroll' );
			$style   = (string) ( $inner['revealStyle'] ?? 'overlay' );
			$dir     = (string) ( $inner['direction'] ?? 'ltr' );

			$config = array(
				'image'           => $image,
				'alt'             => (string) ( $inner['alt'] ?? '' ),
				'title'           => (string) ( $inner['titleText'] ?? '' ),
				'link_url'        => (string) ( $inner['linkUrl'] ?? '' ),
				'link_new_window' => (string) ( $inner['linkNewWindow'] ?? 'off' ),
				'trigger'         => Reveal_Helper::is_valid_trigger( $trigger ) ? $trigger : 'scroll',
				'style'           => Reveal_Helper::is_valid_style( $style ) ? $style : 'overlay',
				'direction'       => Reveal_Helper::is_valid_direction( $dir ) ? $dir : 'ltr',
				'threshold'       => Reveal_Helper::clamp_threshold( $inner['threshold'] ?? 50 ),
				'zoom'            => (string) ( $style_v['hoverZoom'] ?? 'off' ),
			);

			$shell = Reveal_Helper::build_shell( $config );

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
					'children'            => $style_components . $shell,
				)
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Image Reveal module' );

			return '';
		}
	}

	/** Sanitize a numeric scale value for CSS (digits + single dot only). */
	private static function sanitize_scale( string $value ): string {
		$clean = (string) preg_replace( '/[^0-9.]/', '', trim( $value ) );

		return '' !== $clean ? $clean : '1.1';
	}
}
