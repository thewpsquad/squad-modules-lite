<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Logo Grid Item (child) Module Class.
 *
 * Represents a single logo in the Logo Grid parent module.
 *
 * @since   4.0.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Media;

use DiviSquad\Builder\Version4\Abstracts\Module\Child_Module;
use function esc_attr;
use function esc_html__;
use function esc_url;

/**
 * Logo Grid Item (child) Module Class.
 *
 * @since 4.0.0
 */
class Logo_Grid_Item extends Child_Module {

	/**
	 * Initiate Module.
	 *
	 * @since 4.0.0
	 * @return void
	 */
	public function init(): void {
		$this->name   = esc_html__( 'Logo Grid Item', 'squad-modules-for-divi' );
		$this->plural = esc_html__( 'Logo Grid Items', 'squad-modules-for-divi' );

		$this->slug             = 'disq_logo_grid_item';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.{$this->slug}";

		$this->child_title_var          = 'image_alt';
		$this->child_title_fallback_var = 'admin_label';

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'image_settings' => esc_html__( 'Logo Image', 'squad-modules-for-divi' ),
					'link_settings'  => esc_html__( 'Logo Link', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'image' => esc_html__( 'Image', 'squad-modules-for-divi' ),
				),
			),
		);

		$this->advanced_fields = array(
			'background'     => false,
			'borders'        => array(
				'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
				'image'   => array(
					'css'         => array(
						'main' => "{$this->main_css_element} .squad-logo-grid__logo",
					),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'image',
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
	 * @since 4.0.0
	 * @return array<string, array<string, mixed>>
	 */
	public function get_fields(): array {
		return array(
			'image'            => divi_squad()->d4_module_helper->add_media_upload_field(
				esc_html__( 'Logo Image', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Upload or select the logo image.', 'squad-modules-for-divi' ),
					'tab_slug'    => 'general',
					'toggle_slug' => 'image_settings',
				)
			),
			'image_alt'        => array(
				'label'       => esc_html__( 'Image Alt Text', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Alternative text for the logo image.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'tab_slug'    => 'general',
				'toggle_slug' => 'image_settings',
			),
			'logo_link'        => array(
				'label'       => esc_html__( 'Logo Link URL', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'URL to navigate to when the logo is clicked. Leave empty for no link.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'tab_slug'    => 'general',
				'toggle_slug' => 'link_settings',
			),
			'logo_link_target' => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Logo Link Target', 'squad-modules-for-divi' ),
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
	 * Render the logo item HTML.
	 *
	 * @since 4.0.0
	 *
	 * @param array<array-key, mixed> $attrs       Module attributes.
	 * @param string                  $content     Inner content (unused).
	 * @param string                  $render_slug Module render slug.
	 *
	 * @return string
	 */
	public function render( $attrs, $content, $render_slug ): string {
		$image_url = esc_url( $this->prop( 'image', '' ) );
		$logo_link = esc_url( $this->prop( 'logo_link', '' ) );
		$target    = $this->prop( 'logo_link_target', '_self' );
		$target    = in_array( $target, array( '_self', '_blank' ), true ) ? $target : '_self';

		if ( '' === $image_url ) {
			return '';
		}

		$img_html = sprintf(
			'<img class="squad-logo-grid__logo" src="%1$s" alt="%2$s" loading="lazy" />',
			$image_url,
			esc_attr( $this->prop( 'image_alt', '' ) )
		);

		if ( '' !== $logo_link ) {
			$rel = '_blank' === $target ? ' rel="noopener noreferrer"' : '';

			return sprintf(
				'<a href="%1$s" target="%2$s"%3$s>%4$s</a>',
				$logo_link,
				esc_attr( $target ),
				$rel,
				$img_html
			);
		}

		return $img_html;
	}
}
