<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Image Mask Module (Divi 5 / Block API).
 *
 * Native Divi 5 implementation of the Image Mask module. Renders a complex SVG
 * with a `<defs><mask>` definition and a masked `<image>` server-side via the
 * render callback, exactly mirroring the Divi 4 markup so existing frontend CSS
 * applies. Supports the full set of 40 mask shapes, a custom uploaded mask
 * shape, secondary mask shapes, a border layer (solid or gradient), two
 * decoration layers, custom viewBox settings and image positioning/sizing.
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

use DiviSquad\Builder\Shared\Supports\Module_Utilities\Masking\Decorations;
use DiviSquad\Builder\Shared\Supports\Module_Utilities\Masking\Shapes;
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
use function explode;
use function in_array;
use function is_array;
use function preg_replace;
use function sprintf;
use function wp_kses;

/**
 * Image Mask Module class.
 *
 * @since 3.4.0
 */
class Image_Mask extends Module {

	/**
	 * Relative path to the generated module.json metadata folder.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/image-mask/';
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
		$args['classnamesInstance']->add( 'disq_image_mask' );
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
	 * Render callback for the Image Mask module.
	 *
	 * Generates the complete SVG markup (defs/mask + masked image, border layer,
	 * decoration layers) replicating the Divi 4 module's output.
	 *
	 * @since 3.4.0
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
			$image       = $attrs['image']['innerContent']['desktop']['value'] ?? array();
			$mask        = $attrs['maskShape']['innerContent']['desktop']['value'] ?? array();
			$border      = $attrs['borderLayer']['innerContent']['desktop']['value'] ?? array();
			$decoration1 = $attrs['decoration1']['innerContent']['desktop']['value'] ?? array();
			$decoration2 = $attrs['decoration2']['innerContent']['desktop']['value'] ?? array();
			$viewbox     = $attrs['viewbox']['innerContent']['desktop']['value'] ?? array();

			$image       = is_array( $image ) ? $image : array();
			$mask        = is_array( $mask ) ? $mask : array();
			$border      = is_array( $border ) ? $border : array();
			$decoration1 = is_array( $decoration1 ) ? $decoration1 : array();
			$decoration2 = is_array( $decoration2 ) ? $decoration2 : array();
			$viewbox     = is_array( $viewbox ) ? $viewbox : array();

			$unique_id = (string) ( $block->parsed_block['id'] ?? 'image-mask' );
			$unique_id = (string) preg_replace( '/[^a-zA-Z0-9_-]/', '-', $unique_id );

			$svg = self::build_svg(
				$unique_id,
				$image,
				$mask,
				$border,
				$decoration1,
				$decoration2,
				$viewbox
			);

			if ( '' === $svg ) {
				return '';
			}

			$html = sprintf( '<div class="image-elements et_pb_with_background">%s</div>', $svg );

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
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Image Mask module' );

			return '';
		}
	}

	/**
	 * Build the complete SVG markup for the image mask.
	 *
	 * @since 3.4.0
	 *
	 * @param string               $unique_id   Unique identifier used for mask/gradient ids.
	 * @param array<string, mixed> $image       Image group values.
	 * @param array<string, mixed> $mask        Mask shape group values.
	 * @param array<string, mixed> $border      Border layer group values.
	 * @param array<string, mixed> $decoration1 Decoration layer 1 group values.
	 * @param array<string, mixed> $decoration2 Decoration layer 2 group values.
	 * @param array<string, mixed> $viewbox     ViewBox group values.
	 *
	 * @return string
	 */
	protected static function build_svg(
		string $unique_id,
		array $image,
		array $mask,
		array $border,
		array $decoration1,
		array $decoration2,
		array $viewbox
	): string {
		$image_src = self::resolve_upload_url( $image['image'] ?? '' );
		$alt_text  = (string) ( $image['alt'] ?? '' );
		$alt_text  = preg_replace( '/<[^>]*>?/', '', $alt_text );

		$viewbox_width  = (string) ( $viewbox['viewboxWidth'] ?? '1000' );
		$viewbox_height = (string) ( $viewbox['viewboxHeight'] ?? '1000' );

		// Resolve the mask shape (custom uploaded image takes precedence when enabled).
		$mask_shape = self::get_mask_shape( $unique_id, $mask );
		if ( '' === $mask_shape ) {
			return '';
		}

		// Mask transformations.
		$rotate_deg     = preg_replace( '/[^0-9.]/', '', (string) ( $mask['maskShapeRotate'] ?? '0' ) );
		$scale_x        = preg_replace( '/[^0-9.\-]/', '', (string) ( $mask['maskShapeScaleX'] ?? '1' ) );
		$scale_y        = preg_replace( '/[^0-9.\-]/', '', (string) ( $mask['maskShapeScaleY'] ?? '1' ) );
		$mask_transform = sprintf(
			'rotate(%sdeg) scale(%s, %s)',
			'' !== $rotate_deg ? $rotate_deg : '0',
			'' !== $scale_x ? $scale_x : '1',
			'' !== $scale_y ? $scale_y : '1'
		);

		$mask_flips = explode( '|', (string) ( $mask['maskShapeFlip'] ?? '' ) );
		if ( in_array( 'horizontal', $mask_flips, true ) ) {
			$mask_transform .= ' scale(-1, 1)';
		}
		if ( in_array( 'vertical', $mask_flips, true ) ) {
			$mask_transform .= ' scale(1, -1)';
		}

		// Image transformations.
		$image_transform = sprintf(
			'matrix(1 0 0 1 %s %s)',
			(string) ( $image['imageHorizontalPosition'] ?? '0' ),
			(string) ( $image['imageVerticalPosition'] ?? '0' )
		);

		// ViewBox settings.
		if ( 'on' === ( $viewbox['enableCustomViewbox'] ?? 'off' ) ) {
			$viewbox_value = sprintf(
				'%s %s %s %s',
				(string) ( $viewbox['viewboxMinX'] ?? '0' ),
				(string) ( $viewbox['viewboxMinY'] ?? '0' ),
				$viewbox_width,
				$viewbox_height
			);
		} else {
			$viewbox_value  = '0 0 1000 1000';
			$viewbox_width  = '1000';
			$viewbox_height = '1000';
		}

		// Border layer (Layer 1).
		$layer_1_enabled    = 'on' === ( $border['layer1Enable'] ?? 'on' );
		$layer_1_background = '';
		$layer_one_gradient = '';
		$gradient_id        = 'gradient-' . $unique_id;
		if ( $layer_1_enabled ) {
			$background_type = $border['layer1BackgroundType'] ?? 'gradient';
			if ( 'gradient' === $background_type ) {
				$layer_one_gradient = sprintf(
					'<linearGradient id="%3$s" x1="0%%" y1="0%%" x2="100%%" y2="0%%"><stop offset="0%%" style="stop-color: %1$s;stop-opacity: 1" /><stop offset="100%%" style="stop-color: %2$s;stop-opacity: 1" /></linearGradient>',
					esc_attr( (string) ( $border['layer1GradientColorStart'] ?? '#ff5733' ) ),
					esc_attr( (string) ( $border['layer1GradientColorEnd'] ?? '#33c4ff' ) ),
					esc_attr( $gradient_id )
				);
				$layer_1_background = sprintf(
					'<rect x="0" y="0" width="%s" height="%s" class="st1" fill="url(#%s)"/>',
					esc_attr( $viewbox_width ),
					esc_attr( $viewbox_height ),
					esc_attr( $gradient_id )
				);
			} else {
				$layer_1_background = sprintf(
					'<rect x="0" y="0" width="%s" height="%s" class="st1" fill="%s"/>',
					esc_attr( $viewbox_width ),
					esc_attr( $viewbox_height ),
					esc_attr( (string) ( $border['layer1BackgroundColor'] ?? '#ffffff' ) )
				);
			}
		}

		// Decoration layers (Layer 2 and Layer 3).
		$bottom_layers   = '';
		$top_layers      = '';
		$decoration_util = new Decorations();
		$inline_styles   = '';
		$layer_map       = array(
			2 => $decoration1,
			3 => $decoration2,
		);
		foreach ( $layer_map as $layer => $values ) {
			$element = $values['decorationElement'] ?? 'none';
			if ( 'on' === ( $values['layerEnable'] ?? 'off' ) && 'none' !== $element ) {
				$decoration_class = "s0{$layer}";
				$default_color    = 2 === $layer ? '#ff0000' : '#00ff00';

				$inline_styles .= sprintf(
					'%%order_class%% .%s{fill: %s;}',
					$decoration_class,
					self::sanitize_css_background( (string) ( $values['layerBackgroundColor'] ?? $default_color ) )
				);

				$decoration_transform = sprintf(
					'translate(%s, %s) scale(%s) rotate(%s)',
					(string) ( $values['layerHorz'] ?? ( 2 === $layer ? '25%' : '-15%' ) ),
					(string) ( $values['layerVert'] ?? ( 2 === $layer ? '-25%' : '30%' ) ),
					(string) ( $values['layerScale'] ?? ( 2 === $layer ? '1' : '0.8' ) ),
					(string) ( $values['layerRotate'] ?? ( 2 === $layer ? '30deg' : '45deg' ) )
				);
				$decoration_svg       = $decoration_util->get_decoration( (string) $element, $decoration_class );
				$decoration_group     = sprintf(
					'<g transform="%s">%s</g>',
					esc_attr( $decoration_transform ),
					$decoration_svg
				);

				$above = $values['layerAboveImage'] ?? ( 3 === $layer ? 'on' : 'off' );
				if ( 'on' === $above ) {
					$top_layers .= $decoration_group;
				} else {
					$bottom_layers .= $decoration_group;
				}
			}
		}

		// Mask body: custom uploaded image or the SVG shape paths.
		$custom_mask_shape_url = self::resolve_upload_url( $mask['customMaskShapeImage'] ?? '' );
		if ( 'on' === ( $mask['customMaskShapeEnable'] ?? 'off' ) && '' !== $custom_mask_shape_url ) {
			$mask_body = sprintf(
				'<image href="%s" width="%s" height="%s" preserveAspectRatio="none"/>',
				esc_url( $custom_mask_shape_url ),
				esc_attr( $viewbox_width ),
				esc_attr( $viewbox_height )
			);
		} else {
			$mask_body = $mask_shape;
		}

		$markup = sprintf(
			'<svg
				width="100%%"
				height="100%%"
				style="overflow:visible"
				xmlns="http://www.w3.org/2000/svg"
				xmlns:xlink="http://www.w3.org/1999/xlink"
				viewBox="%1$s"
				aria-labelledby="alt-text-%2$s"
				role="img"
			>
				<title id="alt-text-%2$s">%3$s</title>
				<defs>
					%4$s
					<mask id="%2$s">
						<g style="transform: %5$s; transform-origin: center center;">%6$s</g>
					</mask>
				</defs>
				%7$s
				%8$s
				<g style="mask: url(#%2$s)">
					<image
						href="%9$s"
						width="%10$s"
						height="%11$s"
						transform="%12$s"
						preserveAspectRatio="none"
						style="overflow:visible"
					/>
				</g>
				%13$s
			</svg>',
			esc_attr( $viewbox_value ),
			esc_attr( $unique_id ),
			esc_html( (string) $alt_text ),
			$layer_one_gradient,
			esc_attr( $mask_transform ),
			$mask_body,
			$layer_1_enabled ? $layer_1_background : '',
			$bottom_layers,
			esc_url( $image_src ),
			esc_attr( (string) ( $image['imageWidth'] ?? '100%' ) ),
			esc_attr( (string) ( $image['imageHeight'] ?? '100%' ) ),
			esc_attr( $image_transform ),
			$top_layers
		);

		if ( '' !== $inline_styles ) {
			$markup .= sprintf( '<style>%s</style>', $inline_styles );
		}

		return $markup;
	}

	/**
	 * Resolve the SVG mask shape content.
	 *
	 * Returns the (kses-sanitized) SVG paths for the selected shape. When a
	 * custom mask shape upload is enabled the shape paths are still resolved so
	 * the mask is never empty; the actual `<image>` body is swapped in by the
	 * caller.
	 *
	 * @since 3.4.0
	 *
	 * @param string               $unique_id Unique identifier (unused, reserved).
	 * @param array<string, mixed> $mask      Mask shape group values.
	 *
	 * @return string
	 */
	protected static function get_mask_shape( string $unique_id, array $mask ): string {
		$image_shape          = (string) ( $mask['maskShapeImage'] ?? 'shape-01' );
		$mask_shape_secondary = (string) ( $mask['maskShapeSecondary'] ?? 'off' );

		$shapes_util = new Shapes();
		$mask_shape  = $shapes_util->get_shape( $image_shape, $mask_shape_secondary );

		if ( '' !== $mask_shape ) {
			$mask_shape = wp_kses(
				$mask_shape,
				array(
					'path'    => array(
						'd'      => array(),
						'class'  => array(),
						'fill'   => array(),
						'stroke' => array(),
					),
					'g'       => array(
						'class'     => array(),
						'transform' => array(),
					),
					'circle'  => array(
						'cx'    => array(),
						'cy'    => array(),
						'r'     => array(),
						'class' => array(),
						'fill'  => array(),
					),
					'rect'    => array(
						'x'      => array(),
						'y'      => array(),
						'width'  => array(),
						'height' => array(),
						'class'  => array(),
						'fill'   => array(),
					),
					'polygon' => array(
						'points' => array(),
						'class'  => array(),
						'fill'   => array(),
					),
					'ellipse' => array(
						'cx'    => array(),
						'cy'    => array(),
						'rx'    => array(),
						'ry'    => array(),
						'class' => array(),
						'fill'  => array(),
					),
				)
			);
		}

		return (string) $mask_shape;
	}}
