<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Floating Image (child) Module Class.
 *
 * A single absolutely-positioned image that bobs forever via a CSS keyframe
 * loop. No frontend JS.
 *
 * @since   4.1.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Creative;

use DiviSquad\Builder\Version4\Abstracts\Module\Child_Module;
use DiviSquad\Builder\Shared\Modules\Creative\Floating_Images\Float_Helper;
use function esc_attr;
use function esc_html__;
use function esc_url;
use function max;
use function min;
use function sprintf;

/**
 * Floating Image (child) module class.
 *
 * @since 4.1.0
 */
class Floating_Image extends Child_Module {

	public function init(): void {
		$this->name   = esc_html__( 'Floating Image', 'squad-modules-for-divi' );
		$this->plural = esc_html__( 'Floating Images', 'squad-modules-for-divi' );

		$this->slug             = 'disq_floating_image';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.{$this->slug}";

		$this->child_title_var          = 'admin_label';
		$this->child_title_fallback_var = 'admin_label';

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'image'     => esc_html__( 'Image', 'squad-modules-for-divi' ),
					'position'  => esc_html__( 'Position', 'squad-modules-for-divi' ),
					'animation' => esc_html__( 'Animation', 'squad-modules-for-divi' ),
					'link'      => esc_html__( 'Link', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'image_style' => esc_html__( 'Image', 'squad-modules-for-divi' ),
				),
			),
		);

		$this->advanced_fields = array(
			'background'     => false,
			'borders'        => array(
				'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
				'image'   => array(
					'css'         => array(
						'main' => array(
							'border_radii'  => "{$this->main_css_element} .squad-floating__image",
							'border_styles' => "{$this->main_css_element} .squad-floating__image",
						),
					),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'image_style',
				),
			),
			'box_shadow'     => array(
				'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
				'image'   => array(
					'css'         => array( 'main' => "{$this->main_css_element} .squad-floating__image" ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'image_style',
				),
			),
			'margin_padding' => divi_squad()->d4_module_helper->selectors_margin_padding( $this->main_css_element ),
			'fonts'          => false,
			'image_icon'     => false,
			'text'           => false,
			'button'         => false,
			'filters'        => false,
		);
	}

	public function get_fields(): array {
		return array(
			'image'               => array(
				'label'           => esc_html__( 'Image', 'squad-modules-for-divi' ),
				'type'            => 'upload',
				'option_category' => 'basic_option',
				'default'         => '',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'image',
				'dynamic_content' => 'image',
			),
			'image_alt'           => array(
				'label'       => esc_html__( 'Image Alt Text', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'tab_slug'    => 'general',
				'toggle_slug' => 'image',
			),
			'motion_type'         => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Float Motion', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'How the image floats.', 'squad-modules-for-divi' ),
					'options'     => array(
						'up-down'    => esc_html__( 'Up / Down', 'squad-modules-for-divi' ),
						'left-right' => esc_html__( 'Left / Right', 'squad-modules-for-divi' ),
						'diagonal'   => esc_html__( 'Diagonal', 'squad-modules-for-divi' ),
						'rotate'     => esc_html__( 'Rotate / Sway', 'squad-modules-for-divi' ),
					),
					'default'     => 'up-down',
					'tab_slug'    => 'general',
					'toggle_slug' => 'animation',
				)
			),
			'horizontal_position' => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Horizontal Position', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Horizontal offset from the container left.', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '0', 'max' => '100', 'step' => '1' ),
					'default'        => '0%',
					'default_unit'   => '%',
					'allowed_units'  => array( '%', 'px', 'em' ),
					'mobile_options' => true,
					'tab_slug'       => 'general',
					'toggle_slug'    => 'position',
				)
			),
			'vertical_position'   => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Vertical Position', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Vertical offset from the container top.', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '0', 'max' => '100', 'step' => '1' ),
					'default'        => '0%',
					'default_unit'   => '%',
					'allowed_units'  => array( '%', 'px', 'em' ),
					'mobile_options' => true,
					'tab_slug'       => 'general',
					'toggle_slug'    => 'position',
				)
			),
			'float_distance'      => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Float Distance', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'How far the image travels each cycle.', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '0', 'max' => '200', 'step' => '1' ),
					'default'        => '20px',
					'default_unit'   => 'px',
					'allowed_units'  => array( 'px', '%' ),
					'show_if_not'    => array( 'motion_type' => 'rotate' ),
					'tab_slug'       => 'general',
					'toggle_slug'    => 'animation',
				)
			),
			'rotate_angle'        => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Rotate Angle (deg)', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Sway angle in degrees (may be negative).', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '-90', 'max' => '90', 'step' => '1' ),
					'default'        => '8',
					'unitless'       => true,
					'show_if'        => array( 'motion_type' => 'rotate' ),
					'tab_slug'       => 'general',
					'toggle_slug'    => 'animation',
				)
			),
			'duration'            => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Duration (ms)', 'squad-modules-for-divi' ),
				array(
					'range_settings' => array( 'min' => '100', 'max' => '20000', 'step' => '50' ),
					'default'        => '4000',
					'unitless'       => true,
					'tab_slug'       => 'general',
					'toggle_slug'    => 'animation',
				)
			),
			'delay'               => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Delay (ms)', 'squad-modules-for-divi' ),
				array(
					'range_settings' => array( 'min' => '0', 'max' => '5000', 'step' => '50' ),
					'default'        => '0',
					'unitless'       => true,
					'tab_slug'       => 'general',
					'toggle_slug'    => 'animation',
				)
			),
			'easing'              => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Easing', 'squad-modules-for-divi' ),
				array(
					'options'     => array(
						'linear'      => esc_html__( 'Linear', 'squad-modules-for-divi' ),
						'ease'        => esc_html__( 'Ease', 'squad-modules-for-divi' ),
						'ease-in'     => esc_html__( 'Ease In', 'squad-modules-for-divi' ),
						'ease-out'    => esc_html__( 'Ease Out', 'squad-modules-for-divi' ),
						'ease-in-out' => esc_html__( 'Ease In Out', 'squad-modules-for-divi' ),
						'smooth'      => esc_html__( 'Smooth', 'squad-modules-for-divi' ),
					),
					'default'     => 'ease-in-out',
					'tab_slug'    => 'general',
					'toggle_slug' => 'animation',
				)
			),
			'use_link'            => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Enable Link', 'squad-modules-for-divi' ),
				array(
					'default'     => 'off',
					'tab_slug'    => 'general',
					'toggle_slug' => 'link',
				)
			),
			'link_url'            => array(
				'label'       => esc_html__( 'Link URL', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'show_if'     => array( 'use_link' => 'on' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'link',
			),
			'link_new_window'     => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Open Link in New Tab', 'squad-modules-for-divi' ),
				array(
					'default'     => 'off',
					'show_if'     => array( 'use_link' => 'on' ),
					'tab_slug'    => 'general',
					'toggle_slug' => 'link',
				)
			),
			'image_max_width'     => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Image Max Width', 'squad-modules-for-divi' ),
				array(
					'range_settings' => array( 'min' => '0', 'max' => '1000', 'step' => '1' ),
					'default'        => '',
					'mobile_options' => true,
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'image_style',
				)
			),
			'image_max_height'    => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Image Max Height', 'squad-modules-for-divi' ),
				array(
					'range_settings' => array( 'min' => '0', 'max' => '1000', 'step' => '1' ),
					'default'        => '',
					'mobile_options' => true,
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'image_style',
				)
			),
		);
	}

	public function render( $attrs, $content, $render_slug ): string {
		$src = (string) $this->prop( 'image', '' );
		if ( '' === $src ) {
			return '';
		}

		$raw_motion = (string) $this->prop( 'motion_type', 'up-down' );
		$motion     = Float_Helper::is_valid_motion( $raw_motion ) ? $raw_motion : 'up-down';

		$this->apply_float_css( $render_slug, $motion );
		$this->apply_image_size_css( $render_slug );

		$alt = (string) $this->prop( 'image_alt', '' );
		$img = sprintf(
			'<img class="squad-floating__image" src="%s" alt="%s">',
			esc_url( $src ),
			esc_attr( $alt )
		);

		$inner = $this->maybe_wrap_link( $img );

		return sprintf(
			'<div class="squad-floating__item squad-floating__item--%s">%s</div>',
			esc_attr( $motion ),
			$inner
		);
	}

	/**
	 * Emit the position + animation CSS custom properties on the item.
	 *
	 * @since 4.1.0
	 *
	 * @param string $render_slug Module render slug.
	 * @param string $motion      Validated motion token.
	 *
	 * @return void
	 */
	protected function apply_float_css( string $render_slug, string $motion ): void {
		$sel = '%%order_class%% .squad-floating__item';

		$left   = self::sanitize_css_length( (string) $this->prop( 'horizontal_position', '0%' ) );
		$top    = self::sanitize_css_length( (string) $this->prop( 'vertical_position', '0%' ) );
		$dist   = self::sanitize_css_length( (string) $this->prop( 'float_distance', '20px' ) );
		$dur    = (int) abs( (int) $this->prop( 'duration', '4000' ) );
		$delay  = (int) abs( (int) $this->prop( 'delay', '0' ) );
		$rotate = (int) max( -90, min( 90, (int) $this->prop( 'rotate_angle', '8' ) ) );

		$raw_easing = (string) $this->prop( 'easing', 'ease-in-out' );
		$easing     = Float_Helper::is_valid_easing( $raw_easing )
			? Float_Helper::easing_value( $raw_easing )
			: 'ease-in-out';

		$decls  = '';
		$decls .= '' !== $left ? "--squad-float-left: {$left};" : '';
		$decls .= '' !== $top ? "--squad-float-top: {$top};" : '';
		$decls .= "--squad-float-duration: {$dur}ms;";
		$decls .= "--squad-float-delay: {$delay}ms;";
		$decls .= "--squad-float-easing: {$easing};";

		if ( 'rotate' === $motion ) {
			$decls .= "--squad-float-rotate: {$rotate}deg;";
		} elseif ( 'diagonal' === $motion ) {
			$d      = '' !== $dist ? $dist : '20px';
			$decls .= "--squad-float-dist-x: {$d}; --squad-float-dist-y: {$d};";
		} else {
			$d      = '' !== $dist ? $dist : '20px';
			$decls .= "--squad-float-dist: {$d};";
		}

		self::set_style( $render_slug, array( 'selector' => $sel, 'declaration' => $decls ) );
	}

	/**
	 * Emit image max-width / max-height CSS.
	 *
	 * @since 4.1.0
	 *
	 * @param string $render_slug Module render slug.
	 *
	 * @return void
	 */
	protected function apply_image_size_css( string $render_slug ): void {
		$sel = '%%order_class%% .squad-floating__image';

		$w = self::sanitize_css_length( (string) $this->prop( 'image_max_width', '' ) );
		if ( '' !== $w ) {
			self::set_style( $render_slug, array( 'selector' => $sel, 'declaration' => "max-width: {$w};" ) );
		}

		$h = self::sanitize_css_length( (string) $this->prop( 'image_max_height', '' ) );
		if ( '' !== $h ) {
			self::set_style( $render_slug, array( 'selector' => $sel, 'declaration' => "max-height: {$h};" ) );
		}
	}

	/**
	 * Optionally wrap $inner in an <a> tag.
	 *
	 * @param string $inner Inner HTML to wrap.
	 *
	 * @return string Wrapped or unchanged HTML.
	 */
	protected function maybe_wrap_link( string $inner ): string {
		if ( 'on' !== $this->prop( 'use_link', 'off' ) ) {
			return $inner;
		}

		$link_url = (string) $this->prop( 'link_url', '' );
		if ( '' === $link_url ) {
			return $inner;
		}

		$new_window = 'on' === $this->prop( 'link_new_window', 'off' );
		$rel        = Float_Helper::build_rel( $new_window );

		$attrs = sprintf( 'class="squad-floating__link" href="%s"', esc_url( $link_url ) );
		if ( $new_window ) {
			$attrs .= ' target="_blank"';
		}
		if ( '' !== $rel ) {
			$attrs .= sprintf( ' rel="%s"', esc_attr( $rel ) );
		}

		return sprintf( '<a %s>%s</a>', $attrs, $inner );
	}

	/** Sanitize a CSS length value. */
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
