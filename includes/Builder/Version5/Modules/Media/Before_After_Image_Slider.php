<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Before After Image Slider Module (Divi 5 / Block API).
 *
 * Native Divi 5 implementation of the Before After Image Slider module. Renders
 * the `.compare-images` container with the before/after images server-side via
 * the render callback and enqueues the same Image Compare Viewer powered
 * frontend script used by the Divi 4 module so behavior stays consistent across
 * builders.
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
use ET\Builder\Packages\Module\Module as DiviModule;
use ET\Builder\Packages\Module\Options\Css\CssStyle;
use ET\Builder\Packages\Module\Options\Element\ElementClassnames;
use Throwable;
use WP_Block;
use function absint;
use function esc_attr;
use function esc_url;
use function is_wp_error;
use function sanitize_text_field;
use function sprintf;
use function wp_enqueue_script;
use function wp_json_encode;
use function wp_kses_post;

/**
 * Before After Image Slider Module class.
 *
 * @since 3.4.0
 */
class Before_After_Image_Slider extends Module {

	/**
	 * Relative path to the generated module.json metadata folder.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/before-after-image-slider/';
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
	 * Render callback for the Before After Image Slider module.
	 *
	 * @since 3.4.0
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
			// Load the Image Compare Viewer powered frontend script.
			wp_enqueue_script( 'squad-module-ba-image-slider' );

			$slider_html = self::render_slider( $attrs );

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
					'children'            => $elements->style_components( array( 'attrName' => 'module' ) ) . $slider_html,
				)
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Before After Image Slider module' );

			return '';
		}
	}

	/**
	 * Build the `.compare-images` container HTML from Divi 5 attributes.
	 *
	 * Produces the exact wrapper classes and `data-setting` JSON the Divi 4
	 * frontend script (`squad-module-ba-image-slider`) reads, so existing
	 * frontend CSS/JS applies unchanged.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $attrs Block attributes.
	 *
	 * @return string
	 */
	private static function render_slider( array $attrs ): string {
		$images  = $attrs['images']['innerContent']['desktop']['value'] ?? array();
		$compare = $attrs['compare']['innerContent']['desktop']['value'] ?? array();

		$before_label = sanitize_text_field( (string) ( $images['beforeLabel'] ?? '' ) );
		$after_label  = sanitize_text_field( (string) ( $images['afterLabel'] ?? '' ) );

		$settings = array(
			'controlColor'    => (string) ( $compare['slideControlColor'] ?? '#FFFFFF' ),
			'controlShadow'   => 'on' === ( $compare['slideControlShadowEnable'] ?? 'off' ),
			'addCircle'       => 'on' === ( $compare['slideControlCircleEnable'] ?? 'off' ),
			'addCircleBlur'   => 'on' === ( $compare['slideControlCircleBlurEnable'] ?? 'off' ),
			'showLabels'      => 'on' === ( $compare['imageLabelEnable'] ?? 'off' ),
			'labelOptions'    => array(
				'before'  => $before_label,
				'after'   => $after_label,
				'onHover' => 'on' === ( $compare['imageLabelHoverEnable'] ?? 'off' ),
			),
			'smoothing'       => 'on' === ( $compare['slideControlSmoothingEnable'] ?? 'off' ),
			'smoothingAmount' => absint( $compare['slideControlSmoothingAmount'] ?? 100 ),
			'hoverStart'      => 'hover' === ( $compare['slideTriggerType'] ?? 'click' ),
			'verticalMode'    => 'vertical' === ( $compare['slideDirectionMode'] ?? 'horizontal' ),
			'startingPoint'   => absint( $compare['slideControlStartPoint'] ?? 25 ),
		);

		$default_image_url = self::get_placeholder_image_url();

		// Verify and set actual or fallback image for before and after.
		$before_image_url = self::resolve_upload_url( $images['beforeImage'] ?? '' );
		$after_image_url  = self::resolve_upload_url( $images['afterImage'] ?? '' );

		$before_image = self::render_image(
			'' !== $before_image_url ? $before_image_url : $default_image_url,
			(string) ( $images['beforeAlt'] ?? '' ),
			''
		);
		$after_image  = self::render_image(
			'' !== $after_image_url ? $after_image_url : $default_image_url,
			(string) ( $images['afterAlt'] ?? '' ),
			'' === $after_image_url ? 'filter: brightness(60%)' : ''
		);

		return sprintf(
			'<div class="compare-images" data-setting="%3$s">%1$s%2$s</div>',
			$before_image,
			$after_image,
			esc_attr( (string) wp_json_encode( $settings ) )
		);
	}

	/**
	 * Render a single before/after image element.
	 *
	 * @since 3.4.0
	 *
	 * @param string $src   Image URL.
	 * @param string $alt   Image alt text.
	 * @param string $style Optional inline style.
	 *
	 * @return string
	 */
	private static function render_image( string $src, string $alt, string $style = '' ): string {
		if ( '' === $src ) {
			return '';
		}

		if ( '' !== $style ) {
			return sprintf(
				'<img alt="%2$s" src="%1$s" class="squad-image et_pb_image_wrap" style="%3$s"/>',
				esc_url( $src ),
				esc_attr( $alt ),
				esc_attr( $style )
			);
		}

		return sprintf(
			'<img alt="%2$s" src="%1$s" class="squad-image et_pb_image_wrap"/>',
			esc_url( $src ),
			esc_attr( $alt )
		);
	}

	/**
	 * Resolve the placeholder image URL used as a fallback when no image is set.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	private static function get_placeholder_image_url(): string {
		$image = divi_squad()->load_image( '/build/admin/images/placeholders' );
		$url   = $image->get_image( 'landscape.svg', 'svg' );

		if ( is_wp_error( $url ) ) {
			return '';
		}

		return (string) $url;
	}
}
