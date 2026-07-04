<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Skill Bar Item (child) Module Class.
 *
 * A single animated progress bar within the Skill Bar parent module.
 *
 * @since   4.0.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Content;

use DiviSquad\Builder\Version4\Abstracts\Module\Child_Module;
use function absint;
use function esc_attr;
use function esc_html;
use function esc_html__;

/**
 * Skill Bar Item (child) Module Class.
 *
 * @since 4.0.0
 */
class Skill_Bar_Item extends Child_Module {

	public function init(): void {
		$this->name   = esc_html__( 'Skill Bar Item', 'squad-modules-for-divi' );
		$this->plural = esc_html__( 'Skill Bar Items', 'squad-modules-for-divi' );

		$this->slug             = 'disq_skill_bar_item';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.{$this->slug}";

		$this->child_title_var          = 'name';
		$this->child_title_fallback_var = 'admin_label';

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'bar_content' => esc_html__( 'Bar', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'bar_style' => esc_html__( 'Bar Style', 'squad-modules-for-divi' ),
					'colors'    => esc_html__( 'Colors', 'squad-modules-for-divi' ),
					'name'      => esc_html__( 'Name Text', 'squad-modules-for-divi' ),
					'level'     => esc_html__( 'Level Text', 'squad-modules-for-divi' ),
				),
			),
		);

		$this->advanced_fields = array(
			'background'     => false,
			'borders'        => array(
				'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			),
			'box_shadow'     => array(
				'default' => array(
					'css' => array( 'main' => "{$this->main_css_element} .squad-skill-bar__wrapper" ),
				),
			),
			'margin_padding' => divi_squad()->d4_module_helper->selectors_margin_padding( $this->main_css_element ),
			'fonts'          => array(
				'name'  => array(
					'label'       => esc_html__( 'Name', 'squad-modules-for-divi' ),
					'css'         => array( 'main' => "{$this->main_css_element} .squad-skill-bar__name" ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'name',
					'font_size'   => array( 'default' => '14px' ),
					'line_height' => array( 'default' => '1em' ),
				),
				'level' => array(
					'label'       => esc_html__( 'Level', 'squad-modules-for-divi' ),
					'css'         => array( 'main' => "{$this->main_css_element} .squad-skill-bar__level" ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'level',
					'font_size'   => array( 'default' => '14px' ),
					'line_height' => array( 'default' => '1em' ),
				),
			),
			'image_icon'     => false,
			'text'           => false,
			'button'         => false,
			'filters'        => false,
		);
	}

	public function get_fields(): array {
		return array(
			'use_name'        => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Show Name', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Show the skill name text.', 'squad-modules-for-divi' ),
					'default_on_front' => 'on',
					'default'          => 'on',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'bar_content',
				)
			),
			'name'            => array(
				'label'       => esc_html__( 'Name', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'The skill name text.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => esc_html__( 'Web Design', 'squad-modules-for-divi' ),
				'show_if'     => array( 'use_name' => 'on' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'bar_content',
			),
			'level'           => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Level', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Fill percentage (0–100). This is the animation target.', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '0', 'max' => '100', 'step' => '1' ),
					'default'        => '70',
					'unitless'       => true,
					'tab_slug'       => 'general',
					'toggle_slug'    => 'bar_content',
				)
			),
			'hide_level'      => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Hide Level Text', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Hide the percentage label.', 'squad-modules-for-divi' ),
					'default'     => 'off',
					'tab_slug'    => 'general',
					'toggle_slug' => 'bar_content',
				)
			),
			'text_placement'  => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Text Placement', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Place the name/level text inside or above the bar.', 'squad-modules-for-divi' ),
					'options'     => array(
						'inside'  => esc_html__( 'Inside', 'squad-modules-for-divi' ),
						'outside' => esc_html__( 'Outside', 'squad-modules-for-divi' ),
					),
					'default'     => 'inside',
					'tab_slug'    => 'general',
					'toggle_slug' => 'bar_content',
				)
			),
			'bar_height'      => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Bar Height', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Height of the bar track.', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '0', 'max' => '100', 'step' => '1' ),
					'default'        => '30px',
					'mobile_options' => true,
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'bar_style',
				)
			),
			'bar_radius'      => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Bar Border Radius', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Corner radius of the bar track.', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '0', 'max' => '100', 'step' => '1' ),
					'default'        => '40px',
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'bar_style',
				)
			),
			'track_color'     => array(
				'label'       => esc_html__( 'Track Color', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Background color of the bar track.', 'squad-modules-for-divi' ),
				'type'        => 'color-alpha',
				'default'     => '#dddddd',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'colors',
			),
			'track_gradient'  => array(
				'label'       => esc_html__( 'Track Gradient', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Optional CSS gradient for the track (overrides Track Color).', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'colors',
			),
			'fill_color'      => array(
				'label'       => esc_html__( 'Fill Color', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Background color of the filled portion.', 'squad-modules-for-divi' ),
				'type'        => 'color-alpha',
				'default'     => '#5E2EFF',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'colors',
			),
			'fill_gradient'   => array(
				'label'       => esc_html__( 'Fill Gradient', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Optional CSS gradient for the fill (overrides Fill Color).', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'colors',
			),
		);
	}

	public function render( $attrs, $content, $render_slug ): string {
		$this->apply_bar_css( $render_slug );

		$level      = max( 0, min( 100, absint( $this->prop( 'level', '70' ) ) ) );
		$use_name   = 'on' === $this->prop( 'use_name', 'on' );
		$hide_level = 'on' === $this->prop( 'hide_level', 'off' );
		$placement  = 'outside' === $this->prop( 'text_placement', 'inside' ) ? 'outside' : 'inside';

		$name_html  = $use_name ? sprintf( '<span class="squad-skill-bar__name">%s</span>', esc_html( $this->prop( 'name', '' ) ) ) : '';
		$level_html = ! $hide_level ? sprintf( '<span class="squad-skill-bar__level">%d%%</span>', $level ) : '';
		$text_html  = ( '' !== $name_html || '' !== $level_html )
			? sprintf( '<div class="squad-skill-bar__text squad-skill-bar__text--%1$s">%2$s%3$s</div>', $placement, $name_html, $level_html )
			: '';

		return sprintf(
			'<div class="squad-skill-bar__wrapper"><div class="squad-skill-bar__fill" style="--squad-sb-level:%1$d%%">%2$s</div></div>',
			$level,
			$text_html
		);
	}

	public function apply_bar_css( string $render_slug ): void {
		$height = self::sanitize_css_length( (string) $this->prop( 'bar_height', '30px' ) );
		$radius = self::sanitize_css_length( (string) $this->prop( 'bar_radius', '40px' ) );

		$wrapper = '%%order_class%% .squad-skill-bar__wrapper';
		$fill    = '%%order_class%% .squad-skill-bar__fill';

		if ( '' !== $height ) {
			self::set_style( $render_slug, array( 'selector' => $wrapper, 'declaration' => "height: {$height};" ) );
			$tablet = self::sanitize_css_length( (string) $this->prop( 'bar_height_tablet', '' ) );
			$phone  = self::sanitize_css_length( (string) $this->prop( 'bar_height_phone', '' ) );
			if ( '' !== $tablet ) {
				self::set_style( $render_slug, array( 'selector' => $wrapper, 'declaration' => "height: {$tablet};", 'media_query' => self::get_media_query( 'max_width_980' ) ) );
			}
			if ( '' !== $phone ) {
				self::set_style( $render_slug, array( 'selector' => $wrapper, 'declaration' => "height: {$phone};", 'media_query' => self::get_media_query( 'max_width_767' ) ) );
			}
		}
		if ( '' !== $radius ) {
			self::set_style( $render_slug, array( 'selector' => $wrapper, 'declaration' => "border-radius: {$radius};" ) );
		}

		$track_grad = self::sanitize_css_background( (string) $this->prop( 'track_gradient', '' ) );
		$track_col  = self::sanitize_css_background( (string) $this->prop( 'track_color', '#dddddd' ) );
		$track_bg   = '' !== $track_grad ? $track_grad : $track_col;
		if ( '' !== $track_bg ) {
			self::set_style( $render_slug, array( 'selector' => $wrapper, 'declaration' => sprintf( 'background: %s;', esc_attr( $track_bg ) ) ) );
		}

		$fill_grad = self::sanitize_css_background( (string) $this->prop( 'fill_gradient', '' ) );
		$fill_col  = self::sanitize_css_background( (string) $this->prop( 'fill_color', '#5E2EFF' ) );
		$fill_bg   = '' !== $fill_grad ? $fill_grad : $fill_col;
		if ( '' !== $fill_bg ) {
			self::set_style( $render_slug, array( 'selector' => $fill, 'declaration' => sprintf( 'background: %s;', esc_attr( $fill_bg ) ) ) );
		}
	}

	/**
	 * Sanitize a CSS background value (color or gradient).
	 *
	 * Strips characters that could break out of the CSS declaration context,
	 * so a user-supplied gradient/color field cannot inject arbitrary CSS.
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

	private static function sanitize_css_length( string $value ): string {
		$value = trim( $value );
		if ( '' === $value ) {
			return '';
		}
		if ( preg_match( '/^\d+(\.\d+)?(px|em|rem|%|vh|vw|vmin|vmax|ch|ex|cm|mm|pt|pc)$/', $value ) ) {
			return $value;
		}
		return '';
	}
}
