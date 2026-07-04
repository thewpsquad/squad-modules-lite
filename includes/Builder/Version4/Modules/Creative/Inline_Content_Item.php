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

use DiviSquad\Builder\Shared\Modules\Creative\Inline_Content\Inline_Helper;
use DiviSquad\Builder\Version4\Abstracts\Module\Child_Module;
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
	}}
