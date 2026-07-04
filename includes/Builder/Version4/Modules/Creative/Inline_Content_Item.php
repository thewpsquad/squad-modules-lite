<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Inline Content Item (child) Module Class.
 *
 * A single inline element within the Inline Content parent module.
 * Supports five content types: text, icon, image, button, divider.
 * No frontend JS.
 *
 * @since   4.1.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Creative;

use DiviSquad\Builder\Version4\Abstracts\Module\Child_Module;
use DiviSquad\Builder\Shared\Modules\Creative\Inline_Content\Inline_Helper;
use DiviSquad\Utils\Divi;
use function esc_attr;
use function esc_html;
use function esc_html__;
use function esc_url;
use function et_pb_get_extended_font_icon_value;
use function sprintf;
use function wp_kses_post;

/**
 * Inline Content Item (child) module class.
 *
 * @since 4.1.0
 */
class Inline_Content_Item extends Child_Module {

	public function init(): void {
		$this->name   = esc_html__( 'Inline Content Item', 'squad-modules-for-divi' );
		$this->plural = esc_html__( 'Inline Content Items', 'squad-modules-for-divi' );

		$this->slug             = 'disq_inline_content_item';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.{$this->slug}";

		$this->child_title_var          = 'content_type';
		$this->child_title_fallback_var = 'admin_label';

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'item_content' => esc_html__( 'Item', 'squad-modules-for-divi' ),
					'link_options' => esc_html__( 'Link', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'text_style'    => esc_html__( 'Text', 'squad-modules-for-divi' ),
					'icon_style'    => esc_html__( 'Icon', 'squad-modules-for-divi' ),
					'image_style'   => esc_html__( 'Image', 'squad-modules-for-divi' ),
					'divider_style' => esc_html__( 'Divider', 'squad-modules-for-divi' ),
				),
			),
		);

		$this->advanced_fields = array(
			'background'     => false,
			'borders'        => array(
				'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			),
			'box_shadow'     => array(
				'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			),
			'margin_padding' => divi_squad()->d4_module_helper->selectors_margin_padding( $this->main_css_element ),
			'fonts'          => array(
				'text_font' => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Text', 'squad-modules-for-divi' ),
					array(
						'font_size'   => array( 'default' => '16px' ),
						'css'         => array( 'main' => "{$this->main_css_element} .squad-inline__text" ),
						'tab_slug'    => 'advanced',
						'toggle_slug' => 'text_style',
					)
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
			// ── Content type ─────────────────────────────────────────────────
			'content_type'      => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Content Type', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'What this item renders.', 'squad-modules-for-divi' ),
					'options'     => array(
						'text'    => esc_html__( 'Text', 'squad-modules-for-divi' ),
						'icon'    => esc_html__( 'Icon', 'squad-modules-for-divi' ),
						'image'   => esc_html__( 'Image', 'squad-modules-for-divi' ),
						'button'  => esc_html__( 'Button', 'squad-modules-for-divi' ),
						'divider' => esc_html__( 'Divider', 'squad-modules-for-divi' ),
					),
					'default'     => 'text',
					'tab_slug'    => 'general',
					'toggle_slug' => 'item_content',
				)
			),
			// ── Per-type content fields ───────────────────────────────────────
			'text'              => array(
				'label'       => esc_html__( 'Text', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'show_if'     => array( 'content_type' => 'text' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'item_content',
			),
			'icon'              => array(
				'label'       => esc_html__( 'Icon', 'squad-modules-for-divi' ),
				'type'        => 'select_icon',
				'default'     => '',
				'show_if'     => array( 'content_type' => 'icon' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'item_content',
			),
			'image'             => array(
				'label'       => esc_html__( 'Image', 'squad-modules-for-divi' ),
				'type'        => 'upload',
				'default'     => '',
				'show_if'     => array( 'content_type' => 'image' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'item_content',
			),
			'image_alt'         => array(
				'label'       => esc_html__( 'Image Alt', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'show_if'     => array( 'content_type' => 'image' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'item_content',
			),
			'button_text'       => array(
				'label'       => esc_html__( 'Button Text', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'show_if'     => array( 'content_type' => 'button' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'item_content',
			),
			'button_url'        => array(
				'label'       => esc_html__( 'Button URL', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'show_if'     => array( 'content_type' => 'button' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'item_content',
			),
			'button_new_window' => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Open Button in New Tab', 'squad-modules-for-divi' ),
				array(
					'default'     => 'off',
					'show_if'     => array( 'content_type' => 'button' ),
					'tab_slug'    => 'general',
					'toggle_slug' => 'item_content',
				)
			),
			'divider_style'     => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Divider Style', 'squad-modules-for-divi' ),
				array(
					'options'     => array(
						'line' => esc_html__( 'Line', 'squad-modules-for-divi' ),
						'dot'  => esc_html__( 'Dot', 'squad-modules-for-divi' ),
					),
					'default'     => 'line',
					'show_if'     => array( 'content_type' => 'divider' ),
					'tab_slug'    => 'general',
					'toggle_slug' => 'item_content',
				)
			),
			// ── Link wrap ────────────────────────────────────────────────────
			'use_link'          => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Enable Link', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Wrap text, icon, or image in a link. No-op for button/divider.', 'squad-modules-for-divi' ),
					'default'     => 'off',
					'tab_slug'    => 'general',
					'toggle_slug' => 'link_options',
				)
			),
			'link_url'          => array(
				'label'       => esc_html__( 'Link URL', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'show_if'     => array( 'use_link' => 'on' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'link_options',
			),
			'link_new_window'   => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Open Link in New Tab', 'squad-modules-for-divi' ),
				array(
					'default'     => 'off',
					'show_if'     => array( 'use_link' => 'on' ),
					'tab_slug'    => 'general',
					'toggle_slug' => 'link_options',
				)
			),
			// ── Design fields (icon) ─────────────────────────────────────────
			'icon_color'        => array(
				'label'       => esc_html__( 'Icon Color', 'squad-modules-for-divi' ),
				'type'        => 'color-alpha',
				'default'     => '',
				'show_if'     => array( 'content_type' => 'icon' ),
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'icon_style',
			),
			'icon_size'         => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Icon Size', 'squad-modules-for-divi' ),
				array(
					'range_settings' => array( 'min' => '8', 'max' => '96', 'step' => '1' ),
					'default'        => '24px',
					'mobile_options' => true,
					'show_if'        => array( 'content_type' => 'icon' ),
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'icon_style',
				)
			),
			// ── Design fields (image) ────────────────────────────────────────
			'image_width'       => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Image Width', 'squad-modules-for-divi' ),
				array(
					'range_settings' => array( 'min' => '10', 'max' => '500', 'step' => '1' ),
					'default'        => '',
					'mobile_options' => true,
					'show_if'        => array( 'content_type' => 'image' ),
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'image_style',
				)
			),
			'image_height'      => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Image Height', 'squad-modules-for-divi' ),
				array(
					'range_settings' => array( 'min' => '10', 'max' => '500', 'step' => '1' ),
					'default'        => '',
					'mobile_options' => true,
					'show_if'        => array( 'content_type' => 'image' ),
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'image_style',
				)
			),
			'image_radius'      => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Image Border Radius', 'squad-modules-for-divi' ),
				array(
					'range_settings' => array( 'min' => '0', 'max' => '100', 'step' => '1' ),
					'default'        => '',
					'mobile_options' => false,
					'show_if'        => array( 'content_type' => 'image' ),
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'image_style',
				)
			),
			// ── Design fields (divider) ──────────────────────────────────────
			'divider_length'    => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Divider Length', 'squad-modules-for-divi' ),
				array(
					'range_settings' => array( 'min' => '1', 'max' => '200', 'step' => '1' ),
					'default'        => '1px',
					'mobile_options' => false,
					'show_if'        => array( 'content_type' => 'divider' ),
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'divider_style',
				)
			),
			'divider_thickness' => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Divider Thickness', 'squad-modules-for-divi' ),
				array(
					'range_settings' => array( 'min' => '1', 'max' => '20', 'step' => '1' ),
					'default'        => '1px',
					'mobile_options' => false,
					'show_if'        => array( 'content_type' => 'divider' ),
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'divider_style',
				)
			),
			'divider_color'     => array(
				'label'       => esc_html__( 'Divider Color', 'squad-modules-for-divi' ),
				'type'        => 'color-alpha',
				'default'     => '',
				'show_if'     => array( 'content_type' => 'divider' ),
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'divider_style',
			),
		);
	}

	public function render( $attrs, $content, $render_slug ): string {
		$raw_type = (string) $this->prop( 'content_type', 'text' );

		// Validate type against allowlist — never raw into class names.
		$type = Inline_Helper::is_valid_type( $raw_type ) ? $raw_type : 'text';

		$inner_html = $this->render_type( $type, $render_slug );

		// Empty-content guard: skip the child entirely when required content is empty.
		if ( '' === $inner_html ) {
			return '';
		}

		return sprintf(
			'<span class="squad-inline__item squad-inline__item--%s">%s</span>',
			esc_attr( $type ),
			$inner_html
		);
	}

	/**
	 * Render the inner HTML for the given content type.
	 *
	 * Returns '' when the required content for the type is empty (triggers
	 * the empty-child skip in render()).
	 *
	 * @since 4.1.0
	 *
	 * @param string $type        Validated content type.
	 * @param string $render_slug Module render slug (for CSS injection).
	 *
	 * @return string Inner HTML, or '' to skip the child.
	 */
	protected function render_type( string $type, string $render_slug ): string {
		switch ( $type ) {
			case 'text':
				return $this->render_text();
			case 'icon':
				return $this->render_icon();
			case 'image':
				return $this->render_inline_image( $render_slug );
			case 'button':
				return $this->render_inline_button();
			case 'divider':
				return $this->render_divider( $render_slug );
			default:
				return '';
		}
	}

	/**
	 * Render the text type.
	 *
	 * @return string HTML, or '' when text is empty.
	 */
	protected function render_text(): string {
		$text = (string) $this->prop( 'text', '' );
		if ( '' === $text ) {
			return '';
		}

		$span = sprintf( '<span class="squad-inline__text">%s</span>', esc_html( $text ) );

		return $this->maybe_wrap_link( $span );
	}

	/**
	 * Render the icon type.
	 *
	 * Uses wp_kses_post() for the icon glyph character (matches Dual_Button).
	 *
	 * @return string HTML, or '' when icon is empty.
	 */
	protected function render_icon(): string {
		$icon_raw = (string) $this->prop( 'icon', '' );
		if ( '' === $icon_raw ) {
			return '';
		}

		Divi::inject_fa_icons( $icon_raw );

		$icon_value = et_pb_get_extended_font_icon_value( $icon_raw, true );
		$this->apply_icon_css( 'disq_inline_content_item' );

		$span = sprintf(
			'<span class="squad-inline__icon"><span class="et-pb-icon">%s</span></span>',
			wp_kses_post( $icon_value )
		);

		return $this->maybe_wrap_link( $span );
	}

	/**
	 * Render the image type.
	 *
	 * @param string $render_slug Module render slug for CSS.
	 *
	 * @return string HTML, or '' when image src is empty.
	 */
	protected function render_inline_image( string $render_slug ): string {
		$src = (string) $this->prop( 'image', '' );
		if ( '' === $src ) {
			return '';
		}

		$alt = (string) $this->prop( 'image_alt', '' );
		$this->apply_image_css( $render_slug );

		$img = sprintf(
			'<img class="squad-inline__image" src="%s" alt="%s">',
			esc_url( $src ),
			esc_attr( $alt )
		);

		return $this->maybe_wrap_link( $img );
	}

	/**
	 * Render the button type.
	 *
	 * Uses et_pb_button style: `<a class="squad-inline__button et_pb_button">`.
	 * Empty button_text AND empty button_url → skip.
	 *
	 * @return string HTML, or '' when both button_text and button_url are empty.
	 */
	protected function render_inline_button(): string {
		$button_text = (string) $this->prop( 'button_text', '' );
		$button_url  = (string) $this->prop( 'button_url', '' );

		// Skip when both are empty.
		if ( '' === $button_text && '' === $button_url ) {
			return '';
		}

		$new_window = 'on' === $this->prop( 'button_new_window', 'off' );
		$rel        = Inline_Helper::build_rel( $new_window );

		$attrs = sprintf( 'href="%s"', esc_url( $button_url ) );
		if ( $new_window ) {
			$attrs .= ' target="_blank"';
		}
		if ( '' !== $rel ) {
			$attrs .= sprintf( ' rel="%s"', esc_attr( $rel ) );
		}

		return sprintf(
			'<a class="squad-inline__button et_pb_button" %s>%s</a>',
			$attrs,
			esc_html( $button_text )
		);
	}

	/**
	 * Render the divider type.
	 *
	 * Never wrapped in a link. The divider_style is validated against its
	 * allowlist before going into the class name.
	 *
	 * @param string $render_slug Module render slug for CSS.
	 *
	 * @return string HTML (always returns a span — dividers are never empty-skipped).
	 */
	protected function render_divider( string $render_slug ): string {
		$raw_style     = (string) $this->prop( 'divider_style', 'line' );
		$divider_style = in_array( $raw_style, array( 'line', 'dot' ), true ) ? $raw_style : 'line';
		$this->apply_divider_css( $render_slug );

		return sprintf(
			'<span class="squad-inline__divider squad-inline__divider--%s"></span>',
			esc_attr( $divider_style )
		);
	}

	/**
	 * Optionally wrap $inner in an <a> tag when use_link=on and link_url non-empty.
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
		$rel        = Inline_Helper::build_rel( $new_window );

		$attrs = sprintf( 'href="%s"', esc_url( $link_url ) );
		if ( $new_window ) {
			$attrs .= ' target="_blank"';
		}
		if ( '' !== $rel ) {
			$attrs .= sprintf( ' rel="%s"', esc_attr( $rel ) );
		}

		return sprintf( '<a %s>%s</a>', $attrs, $inner );
	}

	/**
	 * Emit icon color and size CSS.
	 *
	 * @since 4.1.0
	 *
	 * @param string $render_slug Module render slug.
	 *
	 * @return void
	 */
	protected function apply_icon_css( string $render_slug ): void {
		$icon_sel = '%%order_class%% .squad-inline__icon .et-pb-icon';

		$color = self::sanitize_css_background( (string) $this->prop( 'icon_color', '' ) );
		if ( '' !== $color ) {
			self::set_style( $render_slug, array( 'selector' => $icon_sel, 'declaration' => "color: {$color};" ) );
		}

		$size = self::sanitize_css_length( (string) $this->prop( 'icon_size', '' ) );
		if ( '' !== $size ) {
			self::set_style( $render_slug, array( 'selector' => $icon_sel, 'declaration' => "font-size: {$size};" ) );
		}
	}

	/**
	 * Emit image width, height, and border-radius CSS.
	 *
	 * @since 4.1.0
	 *
	 * @param string $render_slug Module render slug.
	 *
	 * @return void
	 */
	protected function apply_image_css( string $render_slug ): void {
		$img_sel = '%%order_class%% .squad-inline__image';

		$width = self::sanitize_css_length( (string) $this->prop( 'image_width', '' ) );
		if ( '' !== $width ) {
			self::set_style( $render_slug, array( 'selector' => $img_sel, 'declaration' => "width: {$width};" ) );
		}

		$height = self::sanitize_css_length( (string) $this->prop( 'image_height', '' ) );
		if ( '' !== $height ) {
			self::set_style( $render_slug, array( 'selector' => $img_sel, 'declaration' => "height: {$height};" ) );
		}

		$radius = self::sanitize_css_length( (string) $this->prop( 'image_radius', '' ) );
		if ( '' !== $radius ) {
			self::set_style( $render_slug, array( 'selector' => $img_sel, 'declaration' => "border-radius: {$radius};" ) );
		}
	}

	/**
	 * Emit divider length, thickness, and color CSS.
	 *
	 * @since 4.1.0
	 *
	 * @param string $render_slug Module render slug.
	 *
	 * @return void
	 */
	protected function apply_divider_css( string $render_slug ): void {
		$div_sel = '%%order_class%% .squad-inline__divider';

		$length = self::sanitize_css_length( (string) $this->prop( 'divider_length', '1px' ) );
		if ( '' !== $length ) {
			self::set_style( $render_slug, array( 'selector' => $div_sel, 'declaration' => "height: {$length};" ) );
		}

		$thickness = self::sanitize_css_length( (string) $this->prop( 'divider_thickness', '1px' ) );
		if ( '' !== $thickness ) {
			self::set_style( $render_slug, array( 'selector' => "{$div_sel}.squad-inline__divider--line", 'declaration' => "border-top-width: {$thickness};" ) );
		}

		$color = self::sanitize_css_background( (string) $this->prop( 'divider_color', '' ) );
		if ( '' !== $color ) {
			self::set_style( $render_slug, array( 'selector' => $div_sel, 'declaration' => "background-color: {$color}; border-color: {$color};" ) );
		}
	}

	/**
	 * Sanitize a CSS length value.
	 *
	 * @since 4.1.0
	 *
	 * @param string $value Raw value.
	 *
	 * @return string Sanitized value (may be empty).
	 */
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

	/**
	 * Sanitize a CSS background/color value.
	 *
	 * Strips characters that could break out of the CSS declaration context
	 * (`{ } ; < > \ " '`).
	 *
	 * @since 4.1.0
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
}
