<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Image Accordion Item (child) Module Class.
 *
 * A single accordion panel: a background image, a persistent label, and
 * arbitrary nested Divi module content shown only while the panel is active.
 * The parent module tracks which panel is active and marks it server-side.
 *
 * @since   4.4.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Media;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use DiviSquad\Builder\Version4\Abstracts\Module\Child_Module;
use function esc_attr;
use function esc_html;
use function esc_html__;
use function esc_url;

/**
 * Image Accordion Item (child) Module Class.
 *
 * @since 4.4.0
 */
class Image_Accordion_Item extends Child_Module {

	/**
	 * Placeholder token substituted by Image_Accordion::mark_active_panel()
	 * with this panel's 0-indexed position among its rendered siblings.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	public const INDEX_TOKEN = '%%SQUAD_IA_INDEX%%';

	/**
	 * Initiate Module.
	 *
	 * @since 4.4.0
	 * @return void
	 */
	public function init(): void {
		$this->name   = esc_html__( 'Image Accordion Item', 'squad-modules-for-divi' );
		$this->plural = esc_html__( 'Image Accordion Items', 'squad-modules-for-divi' );

		$this->slug             = 'disq_image_accordion_item';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.{$this->slug}";

		$this->child_title_var          = 'label';
		$this->child_title_fallback_var = 'admin_label';

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'image_settings' => esc_html__( 'Panel Image', 'squad-modules-for-divi' ),
					'label_settings' => esc_html__( 'Label', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'label' => esc_html__( 'Label Text', 'squad-modules-for-divi' ),
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
			'margin_padding' => divi_squad()->d4_module_helper->selectors_margin_padding( "{$this->main_css_element} .squad-image-accordion__content" ),
			'fonts'          => array(
				'label' => array(
					'label'       => esc_html__( 'Label', 'squad-modules-for-divi' ),
					'css'         => array( 'main' => "{$this->main_css_element} .squad-image-accordion__label" ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'label',
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
	 * @since 4.4.0
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_fields(): array {
		return array(
			'image' => divi_squad()->d4_module_helper->add_media_upload_field(
				esc_html__( 'Panel Image', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Background image for this accordion panel. Required.', 'squad-modules-for-divi' ),
					'tab_slug'    => 'general',
					'toggle_slug' => 'image_settings',
				)
			),
			'label' => array(
				'label'       => esc_html__( 'Label', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Persistent label shown on the collapsed strip and the expanded panel.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => esc_html__( 'Panel', 'squad-modules-for-divi' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'label_settings',
			),
		);
	}

	/**
	 * Render the accordion panel HTML.
	 *
	 * The `data-index` value is a literal placeholder token — the parent
	 * module (`Image_Accordion::mark_active_panel()`) fills in the real
	 * 0-indexed position and marks exactly one panel `is-active` /
	 * `aria-expanded="true"` after all children have rendered, since a
	 * child module has no visibility into its own sibling position.
	 *
	 * @since 4.4.0
	 *
	 * @param array<string, mixed> $attrs       Module attributes.
	 * @param string               $content     Rendered nested Divi module content.
	 * @param string               $render_slug Module render slug.
	 *
	 * @return string
	 */
	public function render( $attrs, $content, $render_slug ): string {
		$image_url = esc_url( (string) $this->prop( 'image', '' ) );
		if ( '' === $image_url ) {
			return '';
		}

		$label = esc_html( (string) $this->prop( 'label', '' ) );

		return sprintf(
			'<div class="squad-image-accordion__panel" data-index="%1$s" style="background-image:url(%2$s)" tabindex="0" role="button" aria-expanded="false"><span class="squad-image-accordion__label">%3$s</span><div class="squad-image-accordion__overlay"><div class="squad-image-accordion__content">%4$s</div></div></div>',
			esc_attr( self::INDEX_TOKEN ),
			$image_url,
			$label,
			(string) $content
		);
	}
}
