<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Floating Images Module Class.
 *
 * Parent module: a relative container that hosts absolutely-positioned,
 * infinitely-bobbing Floating Image children. No frontend JS.
 *
 * @since   4.1.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Creative;

use DiviSquad\Builder\Version4\Abstracts\Module;
use function esc_html__;
use function sprintf;

/**
 * Floating Images parent module class.
 *
 * @since 4.1.0
 */
class Floating_Images extends Module {

	public function init(): void {
		$this->name      = esc_html__( 'Floating Images', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Floating Images', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'floating-images.svg' );

		$this->slug             = 'disq_floating_images';
		$this->child_slug       = 'disq_floating_image';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.{$this->slug}";

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		$this->settings_modal_toggles = array(
			'general' => array(
				'toggles' => array(
					'container' => esc_html__( 'Container', 'squad-modules-for-divi' ),
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
			'fonts'          => false,
			'image_icon'     => false,
			'text'           => false,
			'button'         => false,
			'filters'        => false,
		);
	}

	/**
	 * Declare fields for the module.
	 *
	 * @since 4.1.0
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_fields(): array {
		return array(
			'min_height' => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Container Min Height', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Minimum height of the floating-image container.', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '1', 'max' => '2000', 'step' => '1' ),
					'default'        => '400px',
					'default_unit'   => 'px',
					'mobile_options' => true,
					'tab_slug'       => 'general',
					'toggle_slug'    => 'container',
				)
			),
		);
	}

	/**
	 * Render the module output.
	 *
	 * @since 4.1.0
	 *
	 * @param array<string, mixed> $attrs       List of attributes.
	 * @param string               $content     Content being processed.
	 * @param string               $render_slug Slug of module that is used for rendering output.
	 *
	 * @return string
	 */
	public function render( $attrs, $content, $render_slug ): string {
		$this->apply_min_height_css( $render_slug );

		return sprintf( '<div class="squad-floating">%s</div>', $content );
	}

	/**
	 * Emit container min-height CSS for desktop, tablet, phone.
	 *
	 * @since 4.1.0
	 *
	 * @param string $render_slug Module render slug.
	 *
	 * @return void
	 */
	protected function apply_min_height_css( string $render_slug ): void {
		$sel = '%%order_class%% .squad-floating';

		$mh = self::sanitize_css_length( (string) $this->prop( 'min_height', '400px' ) );
		if ( '' !== $mh ) {
			self::set_style( $render_slug, array( 'selector' => $sel, 'declaration' => "min-height: {$mh};" ) );
		}
		$mh_tablet = self::sanitize_css_length( (string) $this->prop( 'min_height_tablet', '' ) );
		if ( '' !== $mh_tablet ) {
			self::set_style( $render_slug, array( 'selector' => $sel, 'declaration' => "min-height: {$mh_tablet};", 'media_query' => self::get_media_query( 'max_width_980' ) ) );
		}
		$mh_phone = self::sanitize_css_length( (string) $this->prop( 'min_height_phone', '' ) );
		if ( '' !== $mh_phone ) {
			self::set_style( $render_slug, array( 'selector' => $sel, 'declaration' => "min-height: {$mh_phone};", 'media_query' => self::get_media_query( 'max_width_767' ) ) );
		}
	}

}
