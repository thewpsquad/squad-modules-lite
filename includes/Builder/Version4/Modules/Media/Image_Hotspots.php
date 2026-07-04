<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Image Hotspots Module (Divi 4 shortcode).
 *
 * Parent module accepting Image Hotspot Pin children. Renders a
 * `.squad-hotspots` canvas (an image with an absolutely positioned pin layer);
 * each pin shows a tooltip on hover or click. Pure CSS layout + a tiny vanilla
 * frontend engine (`image-hotspots.ts`). No external lib dependency.
 *
 * @since   4.3.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Media;

use DiviSquad\Builder\Shared\Modules\Media\Image_Hotspots\Image_Hotspots_Helper;
use DiviSquad\Builder\Version4\Abstracts\Module;
use function esc_attr;
use function esc_html__;
use function wp_enqueue_script;

/**
 * Image Hotspots Module class.
 *
 * @since 4.3.0
 */
class Image_Hotspots extends Module {

	/**
	 * Initiate Module.
	 *
	 * @since 4.3.0
	 * @return void
	 */
	public function init(): void {
		$this->name      = esc_html__( 'Image Hotspots', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Image Hotspots', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'image-hotspots.svg' );

		$this->slug             = 'disq_image_hotspots';
		$this->child_slug       = 'disq_image_hotspot_pin';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.{$this->slug}";

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'image_settings'   => esc_html__( 'Image', 'squad-modules-for-divi' ),
					'trigger_settings' => esc_html__( 'Behavior', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'marker' => esc_html__( 'Marker', 'squad-modules-for-divi' ),
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
			'max_width'      => divi_squad()->d4_module_helper->selectors_max_width( $this->main_css_element ),
			'height'         => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			'fonts'          => false,
			'image_icon'     => false,
			'text'           => false,
			'button'         => false,
			'filters'        => false,
		);

		$this->custom_css_fields = array(
			'hotspots' => array(
				'label'    => esc_html__( 'Hotspots', 'squad-modules-for-divi' ),
				'selector' => '.squad-hotspots',
			),
			'marker'   => array(
				'label'    => esc_html__( 'Marker', 'squad-modules-for-divi' ),
				'selector' => '.squad-hotspots__marker',
			),
			'tooltip'  => array(
				'label'    => esc_html__( 'Tooltip', 'squad-modules-for-divi' ),
				'selector' => '.squad-hotspots__tooltip',
			),
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
			'image'     => divi_squad()->d4_module_helper->add_media_upload_field(
				esc_html__( 'Image', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Upload or select the image to place hotspots on.', 'squad-modules-for-divi' ),
					'tab_slug'    => 'general',
					'toggle_slug' => 'image_settings',
				)
			),
			'image_alt' => array(
				'label'       => esc_html__( 'Image Alt Text', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Alternative text for the image.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'tab_slug'    => 'general',
				'toggle_slug' => 'image_settings',
			),
			'trigger'   => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Trigger', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Show tooltips on hover or on click.', 'squad-modules-for-divi' ),
					'options'     => array(
						'hover' => esc_html__( 'Hover', 'squad-modules-for-divi' ),
						'click' => esc_html__( 'Click', 'squad-modules-for-divi' ),
					),
					'default'     => 'hover',
					'tab_slug'    => 'general',
					'toggle_slug' => 'trigger_settings',
				)
			),
			'pin_color' => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Pin Color', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Color of the hotspot markers.', 'squad-modules-for-divi' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'marker',
				)
			),
		);
	}

	/**
	 * Render the module HTML.
	 *
	 * @since 4.3.0
	 *
	 * @param array<string, mixed> $attrs       Module attributes.
	 * @param string               $content     Rendered child modules HTML.
	 * @param string               $render_slug Module render slug.
	 *
	 * @return string
	 */
	public function render( $attrs, $content, $render_slug ): string {
		wp_enqueue_script( 'squad-module-image-hotspots' );

		$config = array(
			'image'    => (string) $this->prop( 'image', '' ),
			'imageAlt' => (string) $this->prop( 'image_alt', '' ),
			'trigger'  => (string) $this->prop( 'trigger', 'hover' ),
		);

		$this->apply_color_styles( $render_slug );

		return Image_Hotspots_Helper::build_canvas( $config, (string) $content );
	}

	/**
	 * Apply pin (marker) color CSS via set_style.
	 *
	 * @since 4.3.0
	 *
	 * @param string $render_slug Module render slug.
	 *
	 * @return void
	 */
	protected function apply_color_styles( string $render_slug ): void {
		$pin_color = self::sanitize_css_background( (string) $this->prop( 'pin_color', '' ) );
		if ( '' !== $pin_color ) {
			self::set_style(
				$render_slug,
				array(
					'selector'    => '%%order_class%% .squad-hotspots__marker',
					'declaration' => sprintf( 'background-color: %s;', esc_attr( $pin_color ) ),
				)
			);
		}
	}
}
