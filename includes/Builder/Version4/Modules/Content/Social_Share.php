<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Social Share Module Class.
 *
 * Parent module owning the share target, layout, header and global button style.
 *
 * @since   4.0.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Content;

use DiviSquad\Builder\Version4\Abstracts\Module;
use function esc_html;
use function esc_html__;
use function esc_url_raw;
use function get_bloginfo;
use function get_permalink;
use function get_the_excerpt;
use function get_the_title;
use function home_url;
use function in_array;
use function is_singular;
use function sanitize_text_field;
use function sanitize_textarea_field;
use function wp_enqueue_script;

/**
 * Social Share Module Class.
 *
 * @since 4.0.0
 */
class Social_Share extends Module {

	/**
	 * Resolved share target shared with child render passes.
	 *
	 * @var array{url: string, title: string, desc: string}
	 */
	public static $share_target = array(
		'url'   => '',
		'title' => '',
		'desc'  => '',
	);

	/**
	 * Resolved global button style shared with child render passes.
	 *
	 * @var array<string, string>
	 */
	public static $button_context = array(
		'style'        => 'icon',
		'enable_popup' => 'on',
	);

	public function init(): void {
		$this->name      = esc_html__( 'Social Share', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Social Shares', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'social-share.svg' );

		$this->slug             = 'disq_social_share';
		$this->child_slug       = 'disq_social_share_child';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.{$this->slug}";

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'share_target' => esc_html__( 'Share Target', 'squad-modules-for-divi' ),
					'header'       => esc_html__( 'Header', 'squad-modules-for-divi' ),
					'button'       => esc_html__( 'Button', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'layout'      => esc_html__( 'Layout', 'squad-modules-for-divi' ),
					'button_box'  => esc_html__( 'Button', 'squad-modules-for-divi' ),
					'label_text'  => esc_html__( 'Label Text', 'squad-modules-for-divi' ),
					'header_text' => esc_html__( 'Header Text', 'squad-modules-for-divi' ),
				),
			),
		);

		$this->advanced_fields = array(
			'background'     => divi_squad()->d4_module_helper->selectors_background( $this->main_css_element ),
			'borders'        => array(
				'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			),
			'box_shadow'     => array(
				'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			),
			'margin_padding' => divi_squad()->d4_module_helper->selectors_margin_padding( $this->main_css_element ),
			'fonts'          => array(
				'label'           => array(
					'label'       => esc_html__( 'Label', 'squad-modules-for-divi' ),
					'css'         => array( 'main' => "{$this->main_css_element} .squad-social-share__label" ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'label_text',
					'font_size'   => array( 'default' => '14px' ),
					'line_height' => array( 'default' => '1em' ),
				),
				'header_title'    => array(
					'label'        => esc_html__( 'Header Title', 'squad-modules-for-divi' ),
					'css'          => array( 'main' => "{$this->main_css_element} .squad-social-share__header-title" ),
					'tab_slug'     => 'advanced',
					'toggle_slug'  => 'header_text',
					'header_level' => array( 'default' => 'h3' ),
					'font_size'    => array( 'default' => '22px' ),
					'line_height'  => array( 'default' => '1.2em' ),
				),
				'header_subtitle' => array(
					'label'       => esc_html__( 'Header Subtitle', 'squad-modules-for-divi' ),
					'css'         => array( 'main' => "{$this->main_css_element} .squad-social-share__header-subtitle" ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'header_text',
					'font_size'   => array( 'default' => '14px' ),
					'line_height' => array( 'default' => '1.4em' ),
				),
			),
			'image_icon'     => false,
			'text'           => false,
			'button'         => false,
			'filters'        => false,
		);
	}

	/**
	 * Declare general fields for the module.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_fields(): array {
		return array(
			'share_source'    => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Share Source', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Share the current page or a custom URL.', 'squad-modules-for-divi' ),
					'options'     => array(
						'current' => esc_html__( 'Current Page', 'squad-modules-for-divi' ),
						'custom'  => esc_html__( 'Custom URL', 'squad-modules-for-divi' ),
					),
					'default'     => 'current',
					'tab_slug'    => 'general',
					'toggle_slug' => 'share_target',
				)
			),
			'custom_url'      => array(
				'label'       => esc_html__( 'Custom URL', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'The URL to share. Falls back to the current page when empty.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'show_if'     => array( 'share_source' => 'custom' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'share_target',
			),
			'custom_title'    => array(
				'label'       => esc_html__( 'Custom Title', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Title/text passed to networks that support it.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'show_if'     => array( 'share_source' => 'custom' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'share_target',
			),
			'custom_desc'     => array(
				'label'       => esc_html__( 'Custom Description', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Description passed to networks that support it.', 'squad-modules-for-divi' ),
				'type'        => 'textarea',
				'default'     => '',
				'show_if'     => array( 'share_source' => 'custom' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'share_target',
			),
			'header_show'     => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Show Header', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Show a title/subtitle above the buttons.', 'squad-modules-for-divi' ),
					'default'     => 'off',
					'tab_slug'    => 'general',
					'toggle_slug' => 'header',
				)
			),
			'header_title'    => array(
				'label'       => esc_html__( 'Header Title', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'The header title text.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => esc_html__( 'Share this', 'squad-modules-for-divi' ),
				'show_if'     => array( 'header_show' => 'on' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'header',
			),
			'header_subtitle' => array(
				'label'       => esc_html__( 'Header Subtitle', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'The header subtitle text.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'show_if'     => array( 'header_show' => 'on' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'header',
			),
			'button_style'    => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Button Style', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Show icon only, or icon plus label.', 'squad-modules-for-divi' ),
					'options'     => array(
						'icon'      => esc_html__( 'Icon Only', 'squad-modules-for-divi' ),
						'icon_text' => esc_html__( 'Icon + Text', 'squad-modules-for-divi' ),
					),
					'default'     => 'icon',
					'tab_slug'    => 'general',
					'toggle_slug' => 'button',
				)
			),
			'enable_popup'    => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Open in Popup', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Open the share dialog in a centered popup window.', 'squad-modules-for-divi' ),
					'default_on_front' => 'on',
					'default'          => 'on',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'button',
				)
			),
			'orientation'     => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Orientation', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Lay buttons out horizontally or vertically.', 'squad-modules-for-divi' ),
					'options'     => array(
						'inline'  => esc_html__( 'Inline (Horizontal)', 'squad-modules-for-divi' ),
						'stacked' => esc_html__( 'Stacked (Vertical)', 'squad-modules-for-divi' ),
					),
					'default'     => 'inline',
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'layout',
				)
			),
			'columns'         => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Columns', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Number of columns when buttons wrap.', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '1', 'max' => '8', 'step' => '1' ),
					'default'        => '4',
					'unitless'       => true,
					'mobile_options' => true,
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'layout',
				)
			),
			'item_gap'        => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Gap Between Buttons', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Spacing between buttons.', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '0', 'max' => '100', 'step' => '1' ),
					'default'        => '10px',
					'mobile_options' => true,
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'layout',
				)
			),
			'button_shape'    => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Button Shape', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Corner style of each button.', 'squad-modules-for-divi' ),
					'options'     => array(
						'square'  => esc_html__( 'Square', 'squad-modules-for-divi' ),
						'rounded' => esc_html__( 'Rounded', 'squad-modules-for-divi' ),
						'circle'  => esc_html__( 'Circle', 'squad-modules-for-divi' ),
					),
					'default'     => 'rounded',
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'button_box',
				)
			),
			'hover_effect'    => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Hover Effect', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Animation on hover.', 'squad-modules-for-divi' ),
					'options'     => array(
						'none'  => esc_html__( 'None', 'squad-modules-for-divi' ),
						'fill'  => esc_html__( 'Fill', 'squad-modules-for-divi' ),
						'lift'  => esc_html__( 'Lift', 'squad-modules-for-divi' ),
						'scale' => esc_html__( 'Scale', 'squad-modules-for-divi' ),
					),
					'default'     => 'fill',
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'button_box',
				)
			),
			'icon_size'       => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Icon Size', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Size of the network icon.', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '8', 'max' => '64', 'step' => '1' ),
					'default'        => '18px',
					'mobile_options' => true,
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'button_box',
				)
			),
			'icon_color'      => array(
				'label'       => esc_html__( 'Icon Color', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Color of the icon/label.', 'squad-modules-for-divi' ),
				'type'        => 'color-alpha',
				'default'     => '#ffffff',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'button_box',
			),
			'button_bg'       => array(
				'label'       => esc_html__( 'Button Background', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Override the per-network brand background.', 'squad-modules-for-divi' ),
				'type'        => 'color-alpha',
				'default'     => '',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'button_box',
			),
			'button_padding'  => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Button Padding', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Inner padding of each button.', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '0', 'max' => '60', 'step' => '1' ),
					'default'        => '12px',
					'mobile_options' => true,
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'button_box',
				)
			),
		);
	}

	/**
	 * Render module output.
	 *
	 * @param array<string, mixed> $attrs       List of unprocessed attributes.
	 * @param string               $content     Content being processed.
	 * @param string               $render_slug Slug of module that is used for rendering output.
	 *
	 * @return string
	 */
	public function render( $attrs, $content, $render_slug ): string {
		$enable_popup = 'off' === $this->prop( 'enable_popup', 'on' ) ? 'off' : 'on';
		$style        = 'icon_text' === $this->prop( 'button_style', 'icon' ) ? 'icon_text' : 'icon';

		if ( 'on' === $enable_popup ) {
			wp_enqueue_script( 'squad-module-social-share' );
		}

		// Resolve the share target once and expose it to children.
		self::$share_target   = $this->resolve_share_target();
		self::$button_context = array(
			'style'        => $style,
			'enable_popup' => $enable_popup,
		);

		$this->apply_layout_css( $render_slug );

		$orientation = 'stacked' === $this->prop( 'orientation', 'inline' ) ? 'stacked' : 'inline';
		$shape       = $this->prop( 'button_shape', 'rounded' );
		$shape       = in_array( $shape, array( 'square', 'rounded', 'circle' ), true ) ? $shape : 'rounded';
		$hover       = $this->prop( 'hover_effect', 'fill' );
		$hover       = in_array( $hover, array( 'none', 'fill', 'lift', 'scale' ), true ) ? $hover : 'fill';

		$header_html = '';
		if ( 'on' === $this->prop( 'header_show', 'off' ) ) {
			$title    = sanitize_text_field( $this->prop( 'header_title', '' ) );
			$subtitle = sanitize_text_field( $this->prop( 'header_subtitle', '' ) );
			$inner    = '';
			if ( '' !== $title ) {
				$inner .= sprintf( '<h3 class="squad-social-share__header-title">%s</h3>', esc_html( $title ) );
			}
			if ( '' !== $subtitle ) {
				$inner .= sprintf( '<p class="squad-social-share__header-subtitle">%s</p>', esc_html( $subtitle ) );
			}
			if ( '' !== $inner ) {
				$header_html = sprintf( '<div class="squad-social-share__header">%s</div>', $inner );
			}
		}

		return sprintf(
			'<div class="squad-social-share squad-social-share--%1$s squad-social-share--shape-%2$s squad-social-share--hover-%3$s squad-social-share--style-%4$s">%5$s<div class="squad-social-share__list">%6$s</div></div>',
			esc_attr( $orientation ),
			esc_attr( $shape ),
			esc_attr( $hover ),
			esc_attr( $style ),
			$header_html,
			$content
		);
	}

	/**
	 * Resolve the share target {url, title, desc} from the parent fields.
	 *
	 * @since 4.0.0
	 *
	 * @return array{url: string, title: string, desc: string}
	 */
	public function resolve_share_target(): array {
		$source = 'custom' === $this->prop( 'share_source', 'current' ) ? 'custom' : 'current';

		$current_url   = is_singular() ? (string) get_permalink() : home_url( '/' );
		$current_title = is_singular() ? (string) get_the_title() : (string) get_bloginfo( 'name' );
		$current_desc  = is_singular() ? (string) get_the_excerpt() : '';

		if ( 'custom' === $source ) {
			$url   = esc_url_raw( (string) $this->prop( 'custom_url', '' ) );
			$title = sanitize_text_field( (string) $this->prop( 'custom_title', '' ) );
			$desc  = sanitize_textarea_field( (string) $this->prop( 'custom_desc', '' ) );

			// Fall back to current resolution so the link is never empty.
			if ( '' === $url ) {
				$url = esc_url_raw( $current_url );
			}
			if ( '' === $title ) {
				$title = $current_title;
			}
			if ( '' === $desc ) {
				$desc = $current_desc;
			}

			return array( 'url' => $url, 'title' => $title, 'desc' => $desc );
		}

		return array(
			'url'   => esc_url_raw( $current_url ),
			'title' => $current_title,
			'desc'  => $current_desc,
		);
	}

	public function apply_layout_css( string $render_slug ): void {
		$gap_sel     = '%%order_class%% .squad-social-share__list';
		$icon_sel    = '%%order_class%% .squad-social-share__icon';
		$padding_sel = '%%order_class%% .squad-social-share__btn';

		// item_gap — desktop / tablet / phone.
		$gap = self::sanitize_css_length( (string) $this->prop( 'item_gap', '10px' ) );
		if ( '' !== $gap ) {
			self::set_style( $render_slug, array( 'selector' => $gap_sel, 'declaration' => "gap: {$gap};" ) );
		}
		$gap_tablet = self::sanitize_css_length( (string) $this->prop( 'item_gap_tablet', '' ) );
		if ( '' !== $gap_tablet ) {
			self::set_style( $render_slug, array( 'selector' => $gap_sel, 'declaration' => "gap: {$gap_tablet};", 'media_query' => self::get_media_query( 'max_width_980' ) ) );
		}
		$gap_phone = self::sanitize_css_length( (string) $this->prop( 'item_gap_phone', '' ) );
		if ( '' !== $gap_phone ) {
			self::set_style( $render_slug, array( 'selector' => $gap_sel, 'declaration' => "gap: {$gap_phone};", 'media_query' => self::get_media_query( 'max_width_767' ) ) );
		}

		// icon_size — desktop / tablet / phone.
		$icon_size = self::sanitize_css_length( (string) $this->prop( 'icon_size', '18px' ) );
		if ( '' !== $icon_size ) {
			self::set_style( $render_slug, array( 'selector' => $icon_sel, 'declaration' => "font-size: {$icon_size};" ) );
		}
		$icon_size_tablet = self::sanitize_css_length( (string) $this->prop( 'icon_size_tablet', '' ) );
		if ( '' !== $icon_size_tablet ) {
			self::set_style( $render_slug, array( 'selector' => $icon_sel, 'declaration' => "font-size: {$icon_size_tablet};", 'media_query' => self::get_media_query( 'max_width_980' ) ) );
		}
		$icon_size_phone = self::sanitize_css_length( (string) $this->prop( 'icon_size_phone', '' ) );
		if ( '' !== $icon_size_phone ) {
			self::set_style( $render_slug, array( 'selector' => $icon_sel, 'declaration' => "font-size: {$icon_size_phone};", 'media_query' => self::get_media_query( 'max_width_767' ) ) );
		}

		// button_padding — desktop / tablet / phone.
		$btn_padding = self::sanitize_css_length( (string) $this->prop( 'button_padding', '12px' ) );
		if ( '' !== $btn_padding ) {
			self::set_style( $render_slug, array( 'selector' => $padding_sel, 'declaration' => "padding: {$btn_padding};" ) );
		}
		$btn_padding_tablet = self::sanitize_css_length( (string) $this->prop( 'button_padding_tablet', '' ) );
		if ( '' !== $btn_padding_tablet ) {
			self::set_style( $render_slug, array( 'selector' => $padding_sel, 'declaration' => "padding: {$btn_padding_tablet};", 'media_query' => self::get_media_query( 'max_width_980' ) ) );
		}
		$btn_padding_phone = self::sanitize_css_length( (string) $this->prop( 'button_padding_phone', '' ) );
		if ( '' !== $btn_padding_phone ) {
			self::set_style( $render_slug, array( 'selector' => $padding_sel, 'declaration' => "padding: {$btn_padding_phone};", 'media_query' => self::get_media_query( 'max_width_767' ) ) );
		}

		$icon_color = self::sanitize_css_background( (string) $this->prop( 'icon_color', '#ffffff' ) );
		if ( '' !== $icon_color ) {
			self::set_style(
				$render_slug,
				array(
					'selector'    => '%%order_class%% .squad-social-share__btn',
					'declaration' => sprintf( 'color: %s;', $icon_color ),
				)
			);
		}
		$button_bg = self::sanitize_css_background( (string) $this->prop( 'button_bg', '' ) );
		if ( '' !== $button_bg ) {
			self::set_style(
				$render_slug,
				array(
					'selector'    => '%%order_class%% .squad-social-share__btn',
					'declaration' => sprintf( 'background-color: %s;', $button_bg ),
				)
			);
		}

		$columns = absint( $this->prop( 'columns', '4' ) );
		$columns = max( 1, min( 8, $columns ) );
		self::set_style(
			$render_slug,
			array(
				'selector'    => '%%order_class%% .squad-social-share--inline .squad-social-share__list',
				'declaration' => sprintf( 'grid-template-columns: repeat(%d, minmax(0, max-content));', $columns ),
			)
		);
	}
}
