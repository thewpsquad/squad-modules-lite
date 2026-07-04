<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Builder Utils Fields CompatibilityTrait
 *
 * @since   1.5.0
 * @author  The WP Squad <support@squadmodules.com>
 * @package DiviSquad
 */

namespace DiviSquad\Builder\Version4\Supports\Module_Helpers\Fields;

use function apply_filters;

/**
 * Compatibility Trait
 *
 * Handles compatibility for migrating files from previous plugin structures.
 *
 * @since   1.5.0
 * @since   3.3.3 Migrate to the new structure.
 * @package DiviSquad
 */
trait Compatibility_Trait {
	/**
	 * Fix border transition issues.
	 *
	 * @param array  $fields   The defined fields list.
	 * @param string $slug     The slug of the current module.
	 * @param string $selector The css selector.
	 *
	 * @return array
	 */
	public function fix_border_transition( array &$fields, string $slug, string $selector ): array {
		$border_fields = array(
			// all.
			'border_radii_' . $slug        => array( 'border-radius' => $selector ),
			'border_width_all_' . $slug    => array( 'border-width' => $selector ),
			'border_color_all_' . $slug    => array( 'border-color' => $selector ),
			'border_style_all_' . $slug    => array( 'border-style' => $selector ),

			// right.
			'border_width_right_' . $slug  => array( 'border-right-width' => $selector ),
			'border_color_right_' . $slug  => array( 'border-right-color' => $selector ),
			'border_style_right_' . $slug  => array( 'border-right-style' => $selector ),

			// left.
			'border_width_left_' . $slug   => array( 'border-left-width' => $selector ),
			'border_color_left_' . $slug   => array( 'border-left-color' => $selector ),
			'border_style_left_' . $slug   => array( 'border-left-style' => $selector ),

			// top.
			'border_width_top_' . $slug    => array( 'border-left-width' => $selector ),
			'border_color_top_' . $slug    => array( 'border-top-color' => $selector ),
			'border_style_top_' . $slug    => array( 'border-top-style' => $selector ),

			// bottom.
			'border_width_bottom_' . $slug => array( 'border-left-width' => $selector ),
			'border_color_bottom_' . $slug => array( 'border-bottom-color' => $selector ),
			'border_style_bottom_' . $slug => array( 'border-bottom-style' => $selector ),
		);

		/**
		 * Filter the border transition fields.
		 *
		 * @since 3.0.0
		 *
		 * @param array  $border_fields The border transition fields.
		 * @param string $slug          The slug of the current module.
		 * @param string $selector      The CSS selector to apply transitions to.
		 */
		$border_fields = apply_filters( 'divi_squad_border_transition_fields', $border_fields, $slug, $selector );

		foreach ( $border_fields as $field => $properties ) {
			$fields[ $field ] = $properties;
		}

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
	public function fix_fonts_transition( array &$fields, string $slug, string $selector ): array {
		$font_fields = array(
			$slug . '_font_size'      => array( 'font-size' => $selector ),
			$slug . '_text_color'     => array( 'color' => $selector ),
			$slug . '_letter_spacing' => array( 'letter-spacing' => $selector ),
			$slug . '_line_height'    => array( 'line-height' => $selector ),
		);

		/**
		 * Filter the font transition fields.
		 *
		 * @since 3.0.0
		 *
		 * @param array  $font_fields The font transition fields.
		 * @param string $slug        The slug of the current module.
		 * @param string $selector    The CSS selector to apply transitions to.
		 */
		$font_fields = apply_filters( 'divi_squad_font_transition_fields', $font_fields, $slug, $selector );

		foreach ( $font_fields as $field => $properties ) {
			$fields[ $field ] = $properties;
		}

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
	public function fix_box_shadow_transition( array &$fields, string $slug, string $selector ): array {
		$shadow_fields = array(
			'box_shadow_color_' . $slug      => array( 'box-shadow' => $selector ),
			'box_shadow_blur_' . $slug       => array( 'box-shadow' => $selector ),
			'box_shadow_spread_' . $slug     => array( 'box-shadow' => $selector ),
			'box_shadow_horizontal_' . $slug => array( 'box-shadow' => $selector ),
			'box_shadow_vertical_' . $slug   => array( 'box-shadow' => $selector ),
		);

		/**
		 * Filter the box shadow transition fields.
		 *
		 * @since 3.0.0
		 *
		 * @param array  $shadow_fields The box shadow transition fields.
		 * @param string $slug          The slug of the current module.
		 * @param string $selector      The CSS selector to apply transitions to.
		 */
		$shadow_fields = apply_filters( 'divi_squad_box_shadow_transition_fields', $shadow_fields, $slug, $selector );

		foreach ( $shadow_fields as $field => $properties ) {
			$fields[ $field ] = $properties;
		}

		return $fields;
	}
}
