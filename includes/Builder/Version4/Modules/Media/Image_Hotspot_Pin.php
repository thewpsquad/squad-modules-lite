<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Image Hotspot Pin (child) Module (Divi 4 shortcode).
 *
 * Represents a single positioned pin in the Image Hotspots parent module: a
 * marker (dot/icon/number) placed by X/Y percent, paired with a tooltip
 * (title + content) revealed on hover or click.
 *
 * @since   4.3.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Media;

use DiviSquad\Builder\Shared\Modules\Media\Image_Hotspots\Image_Hotspots_Helper;
use DiviSquad\Builder\Version4\Abstracts\Module\Child_Module;
use DiviSquad\Utils\Divi;
use function esc_html__;
use function et_pb_get_extended_font_icon_value;

/**
 * Image Hotspot Pin (child) Module class.
 *
 * @since 4.3.0
 */
class Image_Hotspot_Pin extends Child_Module {

	/**
	 * Initiate Module.
	 *
	 * @since 4.3.0
	 * @return void
	 */
	public function init(): void {
		$this->name   = esc_html__( 'Image Hotspot Pin', 'squad-modules-for-divi' );
		$this->plural = esc_html__( 'Image Hotspot Pins', 'squad-modules-for-divi' );

		$this->slug             = 'disq_image_hotspot_pin';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.{$this->slug}";

		$this->child_title_var          = 'tooltip_title';
		$this->child_title_fallback_var = 'admin_label';

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		$this->settings_modal_toggles = array(
			'general' => array(
				'toggles' => array(
					'position_settings' => esc_html__( 'Position', 'squad-modules-for-divi' ),
					'marker_settings'   => esc_html__( 'Marker', 'squad-modules-for-divi' ),
					'tooltip_settings'  => esc_html__( 'Tooltip', 'squad-modules-for-divi' ),
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
			'pos_x'            => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Position X (%)', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Horizontal position of the pin as a percentage of the image width.', 'squad-modules-for-divi' ),
					'range_settings' => array(
						'min'  => '0',
						'max'  => '100',
						'step' => '1',
					),
					'default'        => '50',
					'unitless'       => true,
					'mobile_options' => false,
					'hover'          => false,
					'tab_slug'       => 'general',
					'toggle_slug'    => 'position_settings',
				)
			),
			'pos_y'            => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Position Y (%)', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Vertical position of the pin as a percentage of the image height.', 'squad-modules-for-divi' ),
					'range_settings' => array(
						'min'  => '0',
						'max'  => '100',
						'step' => '1',
					),
					'default'        => '50',
					'unitless'       => true,
					'mobile_options' => false,
					'hover'          => false,
					'tab_slug'       => 'general',
					'toggle_slug'    => 'position_settings',
				)
			),
			'marker_type'      => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Marker Type', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'What to display inside the hotspot marker.', 'squad-modules-for-divi' ),
					'options'     => array(
						'dot'    => esc_html__( 'Dot', 'squad-modules-for-divi' ),
						'icon'   => esc_html__( 'Icon', 'squad-modules-for-divi' ),
						'number' => esc_html__( 'Number', 'squad-modules-for-divi' ),
					),
					'default'     => 'dot',
					'affects'     => array( 'icon', 'number' ),
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
			'number'           => array(
				'label'           => esc_html__( 'Number', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'Number or short text shown inside the marker.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'default'         => '',
				'depends_show_if' => 'number',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'marker_settings',
			),
			'tooltip_title'    => array(
				'label'       => esc_html__( 'Tooltip Title', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Heading shown in the tooltip.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'tab_slug'    => 'general',
				'toggle_slug' => 'tooltip_settings',
			),
			'tooltip_content'  => array(
				'label'           => esc_html__( 'Tooltip Content', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'Body text shown in the tooltip.', 'squad-modules-for-divi' ),
				'type'            => 'tiny_mce',
				'option_category' => 'basic_option',
				'default'         => '',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'tooltip_settings',
			),
			'tooltip_position' => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Tooltip Position', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Where the tooltip appears relative to the pin.', 'squad-modules-for-divi' ),
					'options'     => array(
						'top'    => esc_html__( 'Top', 'squad-modules-for-divi' ),
						'bottom' => esc_html__( 'Bottom', 'squad-modules-for-divi' ),
						'left'   => esc_html__( 'Left', 'squad-modules-for-divi' ),
						'right'  => esc_html__( 'Right', 'squad-modules-for-divi' ),
					),
					'default'     => 'top',
					'tab_slug'    => 'general',
					'toggle_slug' => 'tooltip_settings',
				)
			),
		);
	}

	/**
	 * Render the hotspot pin HTML.
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

		$pin = array(
			'posX'            => (string) $this->prop( 'pos_x', '50' ),
			'posY'            => (string) $this->prop( 'pos_y', '50' ),
			'markerType'      => (string) $this->prop( 'marker_type', 'dot' ),
			'icon'            => $icon_glyph,
			'number'          => (string) $this->prop( 'number', '' ),
			'title'           => (string) $this->prop( 'tooltip_title', '' ),
			'content'         => (string) $this->prop( 'tooltip_content', '' ),
			'tooltipPosition' => (string) $this->prop( 'tooltip_position', 'top' ),
		);

		return Image_Hotspots_Helper::build_pin( $pin );
	}
}
