<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Builder Utils Helper Class which help to the all module class.
 *
 * @since      1.0.0
 * @deprecated 3.3.3
 * @package    DiviSquad
 * @author     The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Base\DiviBuilder\Utils\Fields;

/**
 * Field Compatibility class
 *
 * @since      1.0.0
 * @deprecated 3.3.3
 * @package    DiviSquad
 * @author     The WP Squad <support@squadmodules.com>
 */
trait CompatibilityTrait {
	/**
	 * Fix border transition issues.
	 *
	 * @param array  $fields   The defined fields list.
	 * @param string $slug     The slug of the current module.
	 * @param string $selector The css selector.
	 *
	 * @return array
	 */
	public static function fix_border_transition( array &$fields, string $slug, string $selector ): array {
		// all.
		$fields[ 'border_radii_' . $slug ]     = array( 'border-radius' => $selector );
		$fields[ 'border_width_all_' . $slug ] = array( 'border-width' => $selector );
		$fields[ 'border_color_all_' . $slug ] = array( 'border-color' => $selector );
		$fields[ 'border_style_all_' . $slug ] = array( 'border-style' => $selector );

		// right.
		$fields[ 'border_width_right_' . $slug ] = array( 'border-right-width' => $selector );
		$fields[ 'border_color_right_' . $slug ] = array( 'border-right-color' => $selector );
		$fields[ 'border_style_right_' . $slug ] = array( 'border-right-style' => $selector );
		// left.
		$fields[ 'border_width_left_' . $slug ] = array( 'border-left-width' => $selector );
		$fields[ 'border_color_left_' . $slug ] = array( 'border-left-color' => $selector );
		$fields[ 'border_style_left_' . $slug ] = array( 'border-left-style' => $selector );
		// top.
		$fields[ 'border_width_top_' . $slug ] = array( 'border-left-width' => $selector );
		$fields[ 'border_color_top_' . $slug ] = array( 'border-top-color' => $selector );
		$fields[ 'border_style_top_' . $slug ] = array( 'border-top-style' => $selector );
		// bottom.
		$fields[ 'border_width_bottom_' . $slug ] = array( 'border-left-width' => $selector );
		$fields[ 'border_color_bottom_' . $slug ] = array( 'border-bottom-color' => $selector );
		$fields[ 'border_style_bottom_' . $slug ] = array( 'border-bottom-style' => $selector );

		return $fields;
	}

	/**
	 * Fix font style transition issues.
	 *
	 * Take all the attributes from divi advanced 'fonts' field and set the transition with given selector.
	 *
	 * @param array  $fields   The defined fields list.
	 * @param string $slug     The slug of the current module.
	 * @param string $selector The css selector.
	 *
	 * @return array $fields
	 */
	public static function fix_fonts_transition( array &$fields, string $slug, string $selector ): array {
		$fields[ $slug . '_font_size' ]      = array( 'font-size' => $selector );
		$fields[ $slug . '_text_color' ]     = array( 'color' => $selector );
		$fields[ $slug . '_letter_spacing' ] = array( 'letter-spacing' => $selector );
		$fields[ $slug . '_line_height' ]    = array( 'line-height' => $selector );

		return $fields;
	}

	/**
	 * Fix box-shadow transition issues.
	 *
	 * @param array  $fields   The defined fields list.
	 * @param string $slug     The slug of the current module.
	 * @param string $selector The css selector.
	 *
	 * @return array
	 */
	public static function fix_box_shadow_transition( array &$fields, string $slug, string $selector ): array {
		$fields[ 'box_shadow_color_' . $slug ]      = array( 'box-shadow' => $selector );
		$fields[ 'box_shadow_blur_' . $slug ]       = array( 'box-shadow' => $selector );
		$fields[ 'box_shadow_spread_' . $slug ]     = array( 'box-shadow' => $selector );
		$fields[ 'box_shadow_horizontal_' . $slug ] = array( 'box-shadow' => $selector );
		$fields[ 'box_shadow_vertical_' . $slug ]   = array( 'box-shadow' => $selector );

		return $fields;
	}
}
