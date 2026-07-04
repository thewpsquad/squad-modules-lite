<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Icon Box Module Class.
 *
 * A lightweight blurb: an icon, image or Lottie animation paired with a title,
 * content, optional badge and an optional whole-box link, with top/left/right
 * layout presets.
 *
 * @since   4.2.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Content;

use DiviSquad\Builder\Version4\Abstracts\Module;
use DiviSquad\Utils\Divi;
use function esc_attr;
use function esc_html;
use function esc_html__;
use function esc_url;
use function et_pb_get_extended_font_icon_value;
use function in_array;
use function wp_enqueue_script;
use function wp_kses_post;
use function wpautop;

/**
 * Icon Box Module Class.
 *
 * @since 4.2.0
 */
class Icon_Box extends Module {

	public function init(): void {
		$this->name      = esc_html__( 'Icon Box', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Icon Boxes', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'icon-box.svg' );

		$this->slug             = 'disq_icon_box';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.{$this->slug}";

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'element' => esc_html__( 'Icon / Image', 'squad-modules-for-divi' ),
					'content' => esc_html__( 'Content', 'squad-modules-for-divi' ),
					'badge'   => esc_html__( 'Badge', 'squad-modules-for-divi' ),
					'link'    => esc_html__( 'Link', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'layout'  => esc_html__( 'Layout', 'squad-modules-for-divi' ),
					'icon'    => esc_html__( 'Icon', 'squad-modules-for-divi' ),
					'title'   => esc_html__( 'Title Text', 'squad-modules-for-divi' ),
					'body'    => esc_html__( 'Body Text', 'squad-modules-for-divi' ),
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
				'title' => array(
					'label'        => esc_html__( 'Title', 'squad-modules-for-divi' ),
					'css'          => array( 'main' => "{$this->main_css_element} .squad-icon-box__title" ),
					'tab_slug'     => 'advanced',
					'toggle_slug'  => 'title',
					'header_level' => array( 'default' => 'h3' ),
					'font_size'    => array( 'default' => '22px' ),
					'line_height'  => array( 'default' => '1.3em' ),
				),
				'body'  => array(
					'label'       => esc_html__( 'Body', 'squad-modules-for-divi' ),
					'css'         => array( 'main' => "{$this->main_css_element} .squad-icon-box__text" ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'body',
					'font_size'   => array( 'default' => '15px' ),
					'line_height' => array( 'default' => '1.7em' ),
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
	 * @since 4.2.0
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_fields(): array {
		return array(
			'element_type'   => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Element Type', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Show an icon, an image or a Lottie animation.', 'squad-modules-for-divi' ),
					'options'          => array(
						'icon'   => esc_html__( 'Icon', 'squad-modules-for-divi' ),
						'image'  => esc_html__( 'Image', 'squad-modules-for-divi' ),
						'lottie' => esc_html__( 'Lottie', 'squad-modules-for-divi' ),
						'none'   => esc_html__( 'None', 'squad-modules-for-divi' ),
					),
					'default'          => 'icon',
					'default_on_front' => 'icon',
					'affects'          => array( 'icon', 'icon_color', 'icon_size', 'icon_bg_color', 'image', 'image_alt', 'lottie_src', 'lottie_loop' ),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'element',
				)
			),
			'icon'           => array(
				'label'            => esc_html__( 'Choose an Icon', 'squad-modules-for-divi' ),
				'description'      => esc_html__( 'Pick an icon to display.', 'squad-modules-for-divi' ),
				'type'             => 'select_icon',
				'option_category'  => 'basic_option',
				'class'            => array( 'et-pb-font-icon' ),
				'default_on_front' => '&#x4e;||divi||400',
				'depends_show_if'  => 'icon',
				'tab_slug'         => 'general',
				'toggle_slug'      => 'element',
			),
			'image'          => array(
				'label'              => esc_html__( 'Image', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Upload an image to display.', 'squad-modules-for-divi' ),
				'type'               => 'upload',
				'option_category'    => 'basic_option',
				'upload_button_text' => esc_attr__( 'Upload an image', 'squad-modules-for-divi' ),
				'choose_text'        => esc_attr__( 'Choose an image', 'squad-modules-for-divi' ),
				'update_text'        => esc_attr__( 'Set as image', 'squad-modules-for-divi' ),
				'depends_show_if'    => 'image',
				'tab_slug'           => 'general',
				'toggle_slug'        => 'element',
			),
			'image_alt'      => array(
				'label'           => esc_html__( 'Image Alt Text', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'Alt text for the image.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'default'         => '',
				'depends_show_if' => 'image',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'element',
			),
			'lottie_src'     => array(
				'label'              => esc_html__( 'Lottie JSON', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Upload or paste the URL of a Lottie .json animation.', 'squad-modules-for-divi' ),
				'type'               => 'upload',
				'option_category'    => 'basic_option',
				'data_type'          => 'json',
				'upload_button_text' => esc_attr__( 'Upload a Lottie JSON', 'squad-modules-for-divi' ),
				'choose_text'        => esc_attr__( 'Choose a Lottie JSON', 'squad-modules-for-divi' ),
				'update_text'        => esc_attr__( 'Set as Lottie JSON', 'squad-modules-for-divi' ),
				'depends_show_if'    => 'lottie',
				'tab_slug'           => 'general',
				'toggle_slug'        => 'element',
			),
			'lottie_loop'    => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Loop Animation', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Play the Lottie animation on a loop.', 'squad-modules-for-divi' ),
					'default_on_front' => 'on',
					'default'          => 'on',
					'depends_show_if'  => 'lottie',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'element',
				)
			),
			'title'          => array(
				'label'       => esc_html__( 'Title', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'The heading text.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => esc_html__( 'Your Feature', 'squad-modules-for-divi' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'content',
			),
			'title_level'    => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Title Heading Level', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'HTML heading tag for the title.', 'squad-modules-for-divi' ),
					'options'     => array(
						'h1' => 'H1',
						'h2' => 'H2',
						'h3' => 'H3',
						'h4' => 'H4',
						'h5' => 'H5',
						'h6' => 'H6',
					),
					'default'     => 'h3',
					'tab_slug'    => 'general',
					'toggle_slug' => 'content',
				)
			),
			'content'        => array(
				'label'       => esc_html__( 'Body', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'The description text.', 'squad-modules-for-divi' ),
				'type'        => 'textarea',
				'default'     => esc_html__( 'Describe this feature in a sentence or two.', 'squad-modules-for-divi' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'content',
			),
			'use_badge'      => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Show Badge', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Display a small badge above the title.', 'squad-modules-for-divi' ),
					'default'     => 'off',
					'affects'     => array( 'badge_text', 'badge_color' ),
					'tab_slug'    => 'general',
					'toggle_slug' => 'badge',
				)
			),
			'badge_text'     => array(
				'label'           => esc_html__( 'Badge Text', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'The badge label, e.g. New.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'default'         => esc_html__( 'New', 'squad-modules-for-divi' ),
				'depends_show_if' => 'on',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'badge',
			),
			'link_url'       => array(
				'label'       => esc_html__( 'Box Link URL', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Make the whole box clickable. Leave empty to disable.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'tab_slug'    => 'general',
				'toggle_slug' => 'link',
			),
			'link_target'    => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Open in New Tab', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Open the box link in a new tab.', 'squad-modules-for-divi' ),
					'default'     => 'off',
					'tab_slug'    => 'general',
					'toggle_slug' => 'link',
				)
			),
			'icon_placement' => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Icon Placement', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Where the icon sits relative to the content.', 'squad-modules-for-divi' ),
					'options'          => array(
						'top'   => esc_html__( 'Top', 'squad-modules-for-divi' ),
						'left'  => esc_html__( 'Left', 'squad-modules-for-divi' ),
						'right' => esc_html__( 'Right', 'squad-modules-for-divi' ),
					),
					'default'          => 'top',
					'default_on_front' => 'top',
					'tab_slug'         => 'advanced',
					'toggle_slug'      => 'layout',
				)
			),
			'alignment'      => divi_squad()->d4_module_helper->add_alignment_field(
				esc_html__( 'Content Alignment', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Align the box content.', 'squad-modules-for-divi' ),
					'default'          => 'center',
					'default_on_front' => 'center',
					'tab_slug'         => 'advanced',
					'toggle_slug'      => 'layout',
				)
			),
			'icon_color'     => array(
				'label'           => esc_html__( 'Icon Color', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'Color of the font icon.', 'squad-modules-for-divi' ),
				'type'            => 'color-alpha',
				'default'         => '#5E2EFF',
				'depends_show_if' => 'icon',
				'tab_slug'        => 'advanced',
				'toggle_slug'     => 'icon',
			),
			'icon_bg_color'  => array(
				'label'           => esc_html__( 'Icon Background Color', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'Background color behind the icon.', 'squad-modules-for-divi' ),
				'type'            => 'color-alpha',
				'default'         => '',
				'depends_show_if' => 'icon',
				'tab_slug'        => 'advanced',
				'toggle_slug'     => 'icon',
			),
			'icon_size'      => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Icon Size', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Size of the font icon.', 'squad-modules-for-divi' ),
					'range_settings'  => array( 'min' => '0', 'max' => '200', 'step' => '1' ),
					'default'         => '48px',
					'depends_show_if' => 'icon',
					'tab_slug'        => 'advanced',
					'toggle_slug'     => 'icon',
				)
			),
		);
	}

	/**
	 * Render module output.
	 *
	 * @since 4.2.0
	 *
	 * @param array<string, mixed> $attrs       List of unprocessed attributes.
	 * @param string               $content     Content being processed.
	 * @param string               $render_slug Slug of module that is used for rendering output.
	 *
	 * @return string
	 */
	public function render( $attrs, $content, $render_slug ): string {
		$this->apply_box_css( $render_slug );

		$placement = (string) $this->prop( 'icon_placement', 'top' );
		$placement = in_array( $placement, array( 'top', 'left', 'right' ), true ) ? $placement : 'top';
		$alignment = (string) $this->prop( 'alignment', 'center' );
		$alignment = in_array( $alignment, array( 'left', 'center', 'right' ), true ) ? $alignment : 'center';

		$element_html = $this->build_element( $render_slug );

		$title = (string) $this->prop( 'title', '' );
		$level = (string) $this->prop( 'title_level', 'h3' );
		$level = in_array( $level, array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ), true ) ? $level : 'h3';

		$title_html = '' !== $title ? sprintf( '<%1$s class="squad-icon-box__title">%2$s</%1$s>', $level, esc_html( $title ) ) : '';
		$body       = (string) $this->prop( 'content', '' );
		$body_html  = '' !== $body ? sprintf( '<div class="squad-icon-box__text">%s</div>', wpautop( wp_kses_post( $body ) ) ) : '';
		$badge_html = $this->build_badge();

		$icon_wrap    = '' !== $element_html ? sprintf( '<div class="squad-icon-box__icon">%s</div>', $element_html ) : '';
		$content_wrap = sprintf( '<div class="squad-icon-box__content">%1$s%2$s%3$s</div>', $badge_html, $title_html, $body_html );

		$box = sprintf(
			'<div class="squad-icon-box squad-icon-box--placement-%1$s squad-icon-box--align-%2$s">%3$s%4$s</div>',
			esc_attr( $placement ),
			esc_attr( $alignment ),
			$icon_wrap,
			$content_wrap
		);

		$link_url = (string) $this->prop( 'link_url', '' );
		if ( '' !== $link_url ) {
			$new_tab = 'on' === $this->prop( 'link_target', 'off' );
			$box     = sprintf(
				'<a class="squad-icon-box__link" href="%1$s"%2$s>%3$s</a>',
				esc_url( $link_url ),
				$new_tab ? ' target="_blank" rel="noopener noreferrer"' : '',
				$box
			);
		}

		return $box;
	}

	/**
	 * Build the icon / image / lottie element markup.
	 *
	 * @since 4.2.0
	 *
	 * @param string $render_slug Slug of module that is used for rendering output.
	 *
	 * @return string
	 */
	protected function build_element( string $render_slug ): string {
		$type = (string) $this->prop( 'element_type', 'icon' );

		if ( 'image' === $type ) {
			$image = (string) $this->prop( 'image', '' );
			if ( '' === $image ) {
				return '';
			}

			return sprintf(
				'<span class="squad-icon-box__image"><img src="%1$s" alt="%2$s" loading="lazy" /></span>',
				esc_url( $image ),
				esc_attr( (string) $this->prop( 'image_alt', '' ) )
			);
		}

		if ( 'lottie' === $type ) {
			$src = (string) $this->prop( 'lottie_src', '' );
			if ( '' === $src ) {
				return '';
			}

			wp_enqueue_script( 'squad-module-icon-box' );
			$loop = 'on' === $this->prop( 'lottie_loop', 'on' );

			return sprintf(
				'<span class="squad-icon-box__lottie"><div class="squad-lottie-player lottie-player-container" data-src="%1$s" data-loop="%2$s" data-autoplay="true"></div></span>',
				esc_url( $src ),
				$loop ? 'true' : 'false'
			);
		}

		if ( 'icon' === $type ) {
			$icon_raw = (string) $this->prop( 'icon', '' );
			if ( '' === $icon_raw ) {
				return '';
			}

			Divi::inject_fa_icons( $icon_raw );
			$icon_value = et_pb_get_extended_font_icon_value( $icon_raw, true );

			$this->generate_styles(
				array(
					'utility_arg'    => 'icon_font_family',
					'render_slug'    => $render_slug,
					'base_attr_name' => 'icon',
					'important'      => true,
					'selector'       => "{$this->main_css_element} .squad-icon-box__icon .et-pb-icon",
					'processor'      => array(
						'ET_Builder_Module_Helper_Style_Processor',
						'process_extended_icon',
					),
				)
			);

			return sprintf( '<span class="et-pb-icon">%s</span>', esc_html( $icon_value ) );
		}

		return '';
	}

	/**
	 * Build the badge markup.
	 *
	 * @since 4.2.0
	 *
	 * @return string
	 */
	protected function build_badge(): string {
		if ( 'on' !== $this->prop( 'use_badge', 'off' ) ) {
			return '';
		}

		$badge = (string) $this->prop( 'badge_text', '' );

		return '' !== $badge ? sprintf( '<span class="squad-icon-box__badge">%s</span>', esc_html( $badge ) ) : '';
	}

	/**
	 * Emit icon color/size/background and placement CSS.
	 *
	 * @since 4.2.0
	 *
	 * @param string $render_slug Slug of module that is used for rendering output.
	 *
	 * @return void
	 */
	public function apply_box_css( string $render_slug ): void {
		$icon_sel = '%%order_class%% .squad-icon-box__icon .et-pb-icon';

		$icon_color = self::sanitize_css_background( (string) $this->prop( 'icon_color', '#5E2EFF' ) );
		if ( '' !== $icon_color ) {
			self::set_style( $render_slug, array( 'selector' => $icon_sel, 'declaration' => sprintf( 'color: %s;', esc_attr( $icon_color ) ) ) );
		}

		$icon_size = self::sanitize_css_length( (string) $this->prop( 'icon_size', '48px' ) );
		if ( '' !== $icon_size ) {
			self::set_style( $render_slug, array( 'selector' => $icon_sel, 'declaration' => sprintf( 'font-size: %1$s; line-height: %1$s;', $icon_size ) ) );
		}

		$icon_bg = self::sanitize_css_background( (string) $this->prop( 'icon_bg_color', '' ) );
		if ( '' !== $icon_bg ) {
			self::set_style(
				$render_slug,
				array(
					'selector'    => '%%order_class%% .squad-icon-box__icon',
					'declaration' => sprintf( 'background: %s;', esc_attr( $icon_bg ) ),
				)
			);
		}
	}
}
