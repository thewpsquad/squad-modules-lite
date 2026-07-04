<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Step Flow Item (child) Module (Divi 4 shortcode).
 *
 * Represents a single step in the Step Flow parent module: a marker
 * (number/icon/image) paired with a label, title, description, and an
 * optional link. Fully independent from the Timeline module — no shared
 * helper class — per docs/superpowers/specs/2026-07-03-step-flow-module-design.md §2.
 *
 * @since   4.4.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Content;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use DiviSquad\Builder\Version4\Abstracts\Module\Child_Module;
use DiviSquad\Utils\Divi;
use function esc_attr;
use function esc_html;
use function esc_html__;
use function esc_url;
use function et_pb_get_extended_font_icon_value;
use function in_array;
use function wp_kses_post;

/**
 * Step Flow Item (child) Module class.
 *
 * @since 4.4.0
 */
class Step_Flow_Item extends Child_Module {

	/**
	 * Allowed marker-type tokens (first = fallback).
	 *
	 * @since 4.4.0
	 *
	 * @var array<int, string>
	 */
	public const MARKER_TYPES = array( 'number', 'icon', 'image' );

	/**
	 * Initiate Module.
	 *
	 * @since 4.4.0
	 * @return void
	 */
	public function init(): void {
		$this->name   = esc_html__( 'Step Flow Item', 'squad-modules-for-divi' );
		$this->plural = esc_html__( 'Step Flow Items', 'squad-modules-for-divi' );

		$this->slug             = 'disq_step_flow_item';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.{$this->slug}";

		$this->child_title_var          = 'title';
		$this->child_title_fallback_var = 'admin_label';

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'content_settings' => esc_html__( 'Content', 'squad-modules-for-divi' ),
					'marker_settings'  => esc_html__( 'Marker', 'squad-modules-for-divi' ),
					'link_settings'    => esc_html__( 'Link', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'marker'      => esc_html__( 'Marker', 'squad-modules-for-divi' ),
					'title'       => esc_html__( 'Title', 'squad-modules-for-divi' ),
					'description' => esc_html__( 'Description', 'squad-modules-for-divi' ),
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
				'title'       => array(
					'label'        => esc_html__( 'Title', 'squad-modules-for-divi' ),
					'css'          => array( 'main' => "{$this->main_css_element} .squad-step-flow__title" ),
					'tab_slug'     => 'advanced',
					'toggle_slug'  => 'title',
					'header_level' => array( 'default' => 'h4' ),
					'font_size'    => array( 'default' => '18px' ),
					'line_height'  => array( 'default' => '1.3em' ),
				),
				'description' => array(
					'label'       => esc_html__( 'Description', 'squad-modules-for-divi' ),
					'css'         => array( 'main' => "{$this->main_css_element} .squad-step-flow__description" ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'description',
					'font_size'   => array( 'default' => '14px' ),
					'line_height' => array( 'default' => '1.6em' ),
				),
			),
			'image_icon'     => false,
			'text'           => false,
			'button'         => false,
			'filters'        => false,
		);
	}

	/**
	 * Define module fields.
	 *
	 * @since 4.4.0
	 * @return array<string, array<string, mixed>>
	 */
	public function get_fields(): array {
		return array(
			'marker_type'             => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Marker Type', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'What to display in the step marker.', 'squad-modules-for-divi' ),
					'options'     => array(
						'number' => esc_html__( 'Number', 'squad-modules-for-divi' ),
						'icon'   => esc_html__( 'Icon', 'squad-modules-for-divi' ),
						'image'  => esc_html__( 'Image', 'squad-modules-for-divi' ),
					),
					'default'     => 'number',
					'affects'     => array( 'marker_number_override', 'marker_icon', 'marker_image', 'marker_image_alt' ),
					'tab_slug'    => 'general',
					'toggle_slug' => 'marker_settings',
				)
			),
			'marker_number_override' => array(
				'label'           => esc_html__( 'Marker Number Override', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'Leave empty to auto-number this step by its position.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'default'         => '',
				'depends_show_if' => 'number',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'marker_settings',
			),
			'marker_icon'             => array(
				'label'            => esc_html__( 'Marker Icon', 'squad-modules-for-divi' ),
				'description'      => esc_html__( 'Pick an icon for the marker.', 'squad-modules-for-divi' ),
				'type'             => 'select_icon',
				'option_category'  => 'basic_option',
				'class'            => array( 'et-pb-font-icon' ),
				'default_on_front' => '',
				'depends_show_if'  => 'icon',
				'tab_slug'         => 'general',
				'toggle_slug'      => 'marker_settings',
			),
			'marker_image'            => divi_squad()->d4_module_helper->add_media_upload_field(
				esc_html__( 'Marker Image', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Upload or select an image for the marker.', 'squad-modules-for-divi' ),
					'depends_show_if' => 'image',
					'tab_slug'        => 'general',
					'toggle_slug'     => 'marker_settings',
				)
			),
			'marker_image_alt'        => array(
				'label'           => esc_html__( 'Marker Image Alt Text', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'Alternative text for the marker image.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'default'         => '',
				'depends_show_if' => 'image',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'marker_settings',
			),
			'title'                   => array(
				'label'       => esc_html__( 'Title', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Heading shown for this step.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'tab_slug'    => 'general',
				'toggle_slug' => 'content_settings',
			),
			'description'             => array(
				'label'           => esc_html__( 'Description', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'Body text for this step.', 'squad-modules-for-divi' ),
				'type'            => 'tiny_mce',
				'option_category' => 'basic_option',
				'default'         => '',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'content_settings',
			),
			'label'                   => array(
				'label'       => esc_html__( 'Label', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Optional short tag shown above the title, e.g. "Week 1".', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'tab_slug'    => 'general',
				'toggle_slug' => 'content_settings',
			),
			'link_url'                => array(
				'label'       => esc_html__( 'Link URL', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Optional URL for a per-step call to action. Leave empty for no link.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'tab_slug'    => 'general',
				'toggle_slug' => 'link_settings',
			),
			'link_target'             => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Link Target', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Open the link in the same tab or a new tab.', 'squad-modules-for-divi' ),
					'options'     => array(
						'_self'  => esc_html__( 'Same Window', 'squad-modules-for-divi' ),
						'_blank' => esc_html__( 'New Window', 'squad-modules-for-divi' ),
					),
					'default'     => '_self',
					'tab_slug'    => 'general',
					'toggle_slug' => 'link_settings',
				)
			),
			'marker_size'             => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Marker Size', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Diameter of the step marker.', 'squad-modules-for-divi' ),
					'range_settings' => array(
						'min'  => '24',
						'max'  => '96',
						'step' => '1',
					),
					'default'        => '40px',
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'marker',
				)
			),
			'marker_color'            => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Marker Icon/Number Color', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Color of the marker glyph.', 'squad-modules-for-divi' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'marker',
				)
			),
			'marker_bg_color'         => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Marker Background Color', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Background color of the marker.', 'squad-modules-for-divi' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'marker',
				)
			),
		);
	}

	/**
	 * Render the step flow item HTML.
	 *
	 * @since 4.4.0
	 *
	 * @param array<array-key, mixed> $attrs       Module attributes.
	 * @param string                  $content     Inner content (unused).
	 * @param string                  $render_slug Module render slug.
	 *
	 * @return string
	 */
	public function render( $attrs, $content, $render_slug ): string {
		$this->apply_marker_css( $render_slug );

		$marker_type = (string) $this->prop( 'marker_type', 'number' );
		$marker_type = in_array( $marker_type, self::MARKER_TYPES, true ) ? $marker_type : 'number';

		$marker = $this->build_marker( $marker_type );

		$label       = (string) $this->prop( 'label', '' );
		$title       = (string) $this->prop( 'title', '' );
		$description = (string) $this->prop( 'description', '' );

		$content_inner = '';
		if ( '' !== $label ) {
			$content_inner .= sprintf( '<span class="squad-step-flow__label">%s</span>', esc_html( $label ) );
		}
		if ( '' !== $title ) {
			$content_inner .= sprintf( '<h4 class="squad-step-flow__title">%s</h4>', esc_html( $title ) );
		}
		if ( '' !== $description ) {
			$content_inner .= sprintf( '<div class="squad-step-flow__description">%s</div>', wp_kses_post( $description ) );
		}

		$content_html = sprintf( '<div class="squad-step-flow__content">%s</div>', $content_inner );

		$inner = sprintf(
			'<div class="squad-step-flow__marker" aria-hidden="true">%1$s</div>%2$s',
			$marker,
			$content_html
		);

		$link = esc_url( (string) $this->prop( 'link_url', '' ) );
		if ( '' !== $link ) {
			$target = (string) $this->prop( 'link_target', '_self' );
			$target = in_array( $target, array( '_self', '_blank' ), true ) ? $target : '_self';
			$rel    = '_blank' === $target ? ' rel="noopener noreferrer"' : '';

			return sprintf(
				'<div class="squad-step-flow__step" data-index="0"><a class="squad-step-flow__link" href="%1$s" target="%2$s"%3$s>%4$s</a></div>',
				$link,
				esc_attr( $target ),
				$rel,
				$inner
			);
		}

		return sprintf( '<div class="squad-step-flow__step" data-index="0">%s</div>', $inner );
	}

	/**
	 * Build the marker inner markup for a given marker type.
	 *
	 * @since 4.4.0
	 *
	 * @param string $type Validated marker type (number|icon|image).
	 *
	 * @return string
	 */
	private function build_marker( string $type ): string {
		if ( 'icon' === $type ) {
			$icon_raw = (string) $this->prop( 'marker_icon', '' );
			if ( '' === $icon_raw ) {
				return '<span class="squad-step-flow__number squad-step-flow__number--auto"></span>';
			}

			Divi::inject_fa_icons( $icon_raw );
			$icon_glyph = (string) et_pb_get_extended_font_icon_value( $icon_raw, true );

			return sprintf( '<span class="squad-step-flow__icon et-pb-icon">%s</span>', esc_html( $icon_glyph ) );
		}

		if ( 'image' === $type ) {
			$image = esc_url( (string) $this->prop( 'marker_image', '' ) );
			if ( '' === $image ) {
				return '<span class="squad-step-flow__number squad-step-flow__number--auto"></span>';
			}

			return sprintf(
				'<img class="squad-step-flow__marker-image" src="%1$s" alt="%2$s" loading="lazy" />',
				$image,
				esc_attr( (string) $this->prop( 'marker_image_alt', '' ) )
			);
		}

		// number (default/fallback).
		$override = (string) $this->prop( 'marker_number_override', '' );
		if ( '' !== $override ) {
			return sprintf( '<span class="squad-step-flow__number">%s</span>', esc_html( $override ) );
		}

		// Empty: CSS counter (see Task 15) fills the visible number via ::before.
		return '<span class="squad-step-flow__number squad-step-flow__number--auto"></span>';
	}

	/**
	 * Apply marker size/color CSS via set_style.
	 *
	 * @since 4.4.0
	 *
	 * @param string $render_slug Module render slug.
	 *
	 * @return void
	 */
	public function apply_marker_css( string $render_slug ): void {
		$size = self::sanitize_css_length( (string) $this->prop( 'marker_size', '40px' ) );
		if ( '' !== $size ) {
			self::set_style(
				$render_slug,
				array(
					'selector'    => '%%order_class%% .squad-step-flow__marker',
					'declaration' => sprintf( 'width: %1$s; height: %1$s;', esc_attr( $size ) ),
				)
			);
		}

		$color = self::sanitize_css_background( (string) $this->prop( 'marker_color', '' ) );
		if ( '' !== $color ) {
			self::set_style(
				$render_slug,
				array(
					'selector'    => '%%order_class%% .squad-step-flow__marker',
					'declaration' => sprintf( 'color: %s;', esc_attr( $color ) ),
				)
			);
		}

		$bg = self::sanitize_css_background( (string) $this->prop( 'marker_bg_color', '' ) );
		if ( '' !== $bg ) {
			self::set_style(
				$render_slug,
				array(
					'selector'    => '%%order_class%% .squad-step-flow__marker',
					'declaration' => sprintf( 'background-color: %s;', esc_attr( $bg ) ),
				)
			);
		}
	}
}
