<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Star Rating Module (Divi 5 / Block API).
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
use function esc_html;
use function number_format;
use function wp_parse_args;

/**
 * Star Rating Module class.
 *
 * @since 3.4.0
 */
class Star_Rating extends Module {

	/**
	 * Relative path to the generated module.json metadata folder.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/star-rating/';
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
		$args['classnamesInstance']->add( 'disq_star_rating' );
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
					// Custom stars color (mirrors the D4 `.star-rating i:before` color).
					$elements->style(
						array(
							'attrName'   => 'content',
							'styleProps' => array(
								'advancedStyles' => array(
									array(
										'componentName' => 'divi/common',
										'props'         => array(
											'selector'            => "{$args['orderClass']} .star-rating, {$args['orderClass']} .star-rating i, {$args['orderClass']} .star-rating i:before",
											'attr'                => $attrs['content']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												$value = $params['attrValue'] ?? array();
												$color = $value['starsColor'] ?? '';

												return ( '' !== $color && '#f0ad4e' !== $color )
													? sprintf( 'color: %s;', self::sanitize_css_background( $color ) )
													: '';
											},
										),
									),
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
	 * Generate html markup for the stars.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $args List of attributes.
	 *
	 * @return string
	 */
	public static function get_star_rating( array $args = array() ): string {
		$defaults = array(
			'rating_scale' => 5,
			'rating'       => 5.0,
			'show_number'  => 'off',
		);

		$args = wp_parse_args( $args, $defaults );

		$int_rating = (int) $args['rating'];
		$precision  = ( (float) $args['rating'] ) - $int_rating;
		$output     = '';

		for ( $stars = 1; $stars <= (int) $args['rating_scale']; $stars ++ ) {
			if ( $stars <= $int_rating ) {
				$output .= '<i class="star-full">☆</i>';
			} elseif ( $int_rating + 1 === $stars && $precision > 0 ) {
				// Partial star with precision using CSS custom property.
				$decimal = number_format( $precision * 100, 0, '', '' );
				$output  .= sprintf(
					'<i class="star-precision" style="--squad-star-rating-precision: %1$s">☆</i>',
					esc_attr( $decimal )
				);
			} else {
				// Empty star.
				$output .= '<i class="star-empty">☆</i>';
			}
		}

		if ( 'on' === $args['show_number'] ) {
			$output .= ' <span class="star-rating-text">(<span>' . esc_html( (string) $args['rating'] ) . '</span>/<span>' . esc_html( (string) $args['rating_scale'] ) . '</span>)</span>';
		}

		return $output;
	}

	/**
	 * Render callback for the Star Rating module.
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
			$inner = $attrs['content']['innerContent']['desktop']['value'] ?? array();

			$rating_scale   = (int) ( $inner['ratingScale'] ?? 5 );
			$rating         = (float) ( $inner['rating'] ?? 5 );
			$title          = $inner['title'] ?? '';
			$title_position = $inner['titlePosition'] ?? 'left';
			$show_number    = $inner['showNumber'] ?? 'off';

			// Clamp the rating to the chosen scale.
			if ( $rating > $rating_scale ) {
				$rating = (float) $rating_scale;
			}

			// Determine display type from the title position.
			$display_type = ( 'top' === $title_position || 'bottom' === $title_position ) ? 'block' : 'inline-block';

			// Collect rating title markup.
			$title_html = '';
			if ( '' !== $title ) {
				$title_html = sprintf(
					'<h2 class="star-rating-title et_pb_module_header"><span>%1$s</span></h2>',
					esc_html( $title )
				);
			}

			// Collect stars markup.
			$stars_output = self::get_star_rating(
				array(
					'rating_scale' => $rating_scale,
					'rating'       => $rating,
					'show_number'  => $show_number,
				)
			);

			$title_before = ( 'left' === $title_position || 'top' === $title_position );

			$position_output = sprintf(
				'%1$s<div class="star-rating" title="%2$s/%3$s">%4$s</div>%5$s',
				$title_before ? $title_html : '',
				esc_attr( (string) $rating ),
				esc_attr( (string) $rating_scale ),
				$stars_output,
				$title_before ? '' : $title_html
			);

			$html = sprintf(
				'<div class="star-rating-wrapper d-type-%2$s star-title-position-%3$s">%1$s</div>',
				$position_output,
				esc_attr( $display_type ),
				esc_attr( $title_position )
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
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Star Rating module' );

			return '';
		}
	}

}
