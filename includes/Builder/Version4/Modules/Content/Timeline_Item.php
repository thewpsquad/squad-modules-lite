<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Timeline Item (child) Module (Divi 4 shortcode).
 *
 * Represents a single event in the Timeline parent module: a marker
 * (icon/image/number/dot) paired with a card holding a date, title, content,
 * and an optional link.
 *
 * @since   4.3.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Content;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use DiviSquad\Builder\Shared\Modules\Content\Timeline\Timeline_Helper;
use DiviSquad\Builder\Version4\Abstracts\Module\Child_Module;
use DiviSquad\Utils\Divi;
use function esc_html__;
use function et_pb_get_extended_font_icon_value;

/**
 * Timeline Item (child) Module class.
 *
 * @since 4.3.0
 */
class Timeline_Item extends Child_Module {

	/**
	 * Initiate Module.
	 *
	 * @since 4.3.0
	 * @return void
	 */
	public function init(): void {
		$this->name   = esc_html__( 'Timeline Item', 'squad-modules-for-divi' );
		$this->plural = esc_html__( 'Timeline Items', 'squad-modules-for-divi' );

		$this->slug             = 'disq_timeline_item';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.{$this->slug}";

		$this->child_title_var          = 'title';
		$this->child_title_fallback_var = 'admin_label';

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		$this->settings_modal_toggles = array(
			'general' => array(
				'toggles' => array(
					'content_settings' => esc_html__( 'Content', 'squad-modules-for-divi' ),
					'marker_settings'  => esc_html__( 'Marker', 'squad-modules-for-divi' ),
					'link_settings'    => esc_html__( 'Link', 'squad-modules-for-divi' ),
				),
			),
		);

		$this->advanced_fields = array(
			'background'     => false,
			'borders'        => array(
				'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
				'card'    => array(
					'css'         => array(
						'main' => "{$this->main_css_element} .squad-timeline__card",
					),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'card',
				),
			),
			'box_shadow'     => array(
				'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			),
			'margin_padding' => divi_squad()->d4_module_helper->selectors_margin_padding( $this->main_css_element ),
			'fonts'          => false,
			'image_icon'     => false,
			'text'           => false,
			'button'         => false,
			'filters'        => false,
		);
	}

	/**
	 * Define module fields.
	 *
	 * @since 4.3.0
	 * @return array<string, array<string, mixed>>
	 */
	public function get_fields(): array {
		return array(
			'title'            => array(
				'label'       => esc_html__( 'Title', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Heading shown on the event card.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'tab_slug'    => 'general',
				'toggle_slug' => 'content_settings',
			),
			'date_label'       => array(
				'label'       => esc_html__( 'Date / Label', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Short date or label shown above the title.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'tab_slug'    => 'general',
				'toggle_slug' => 'content_settings',
			),
			'content'          => array(
				'label'           => esc_html__( 'Content', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'Body text for the event card.', 'squad-modules-for-divi' ),
				'type'            => 'tiny_mce',
				'option_category' => 'basic_option',
				'default'         => '',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'content_settings',
			),
			'marker_type'      => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Marker Type', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'What to display in the timeline marker.', 'squad-modules-for-divi' ),
					'options'     => array(
						'icon'   => esc_html__( 'Icon', 'squad-modules-for-divi' ),
						'image'  => esc_html__( 'Image', 'squad-modules-for-divi' ),
						'number' => esc_html__( 'Number', 'squad-modules-for-divi' ),
						'dot'    => esc_html__( 'Dot', 'squad-modules-for-divi' ),
					),
					'default'     => 'dot',
					'affects'     => array( 'icon', 'image', 'image_alt', 'marker_number' ),
					'tab_slug'    => 'general',
					'toggle_slug' => 'marker_settings',
				)
			),
			'icon'             => array(
				'label'            => esc_html__( 'Icon', 'squad-modules-for-divi' ),
				'description'      => esc_html__( 'Pick an icon for the marker.', 'squad-modules-for-divi' ),
				'type'             => 'select_icon',
				'option_category'  => 'basic_option',
				'class'            => array( 'et-pb-font-icon' ),
				'default_on_front' => '',
				'depends_show_if'  => 'icon',
				'tab_slug'         => 'general',
				'toggle_slug'      => 'marker_settings',
			),
			'image'            => divi_squad()->d4_module_helper->add_media_upload_field(
				esc_html__( 'Image', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Upload or select an image for the marker.', 'squad-modules-for-divi' ),
					'depends_show_if' => 'image',
					'tab_slug'        => 'general',
					'toggle_slug'     => 'marker_settings',
				)
			),
			'image_alt'        => array(
				'label'           => esc_html__( 'Image Alt Text', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'Alternative text for the marker image.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'default'         => '',
				'depends_show_if' => 'image',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'marker_settings',
			),
			'marker_number'    => array(
				'label'           => esc_html__( 'Marker Number', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'Number or short text shown inside the marker.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'default'         => '',
				'depends_show_if' => 'number',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'marker_settings',
			),
			'item_link'        => array(
				'label'       => esc_html__( 'Link URL', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'URL the card links to. Leave empty for no link.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'tab_slug'    => 'general',
				'toggle_slug' => 'link_settings',
			),
			'item_link_target' => divi_squad()->d4_module_helper->add_select_box_field(
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
		);
	}

	/**
	 * Render the timeline item HTML.
	 *
	 * @since 4.3.0
	 *
	 * @param array<array-key, mixed> $attrs       Module attributes.
	 * @param string                  $content     Inner content (unused).
	 * @param string                  $render_slug Module render slug.
	 *
	 * @return string
	 */
	public function render( $attrs, $content, $render_slug ): string {
		$icon_glyph = '';
		$icon_raw   = (string) $this->prop( 'icon', '' );
		if ( '' !== $icon_raw ) {
			Divi::inject_fa_icons( $icon_raw );
			$icon_glyph = (string) et_pb_get_extended_font_icon_value( $icon_raw, true );
		}

		$item = array(
			'title'      => (string) $this->prop( 'title', '' ),
			'date'       => (string) $this->prop( 'date_label', '' ),
			'content'    => (string) $this->prop( 'content', '' ),
			'markerType' => (string) $this->prop( 'marker_type', 'dot' ),
			'icon'       => $icon_glyph,
			'image'      => (string) $this->prop( 'image', '' ),
			'imageAlt'   => (string) $this->prop( 'image_alt', '' ),
			'number'     => (string) $this->prop( 'marker_number', '' ),
			'link'       => (string) $this->prop( 'item_link', '' ),
			'target'     => (string) $this->prop( 'item_link_target', '_self' ),
		);

		return Timeline_Helper::build_item( $item );
	}
}
