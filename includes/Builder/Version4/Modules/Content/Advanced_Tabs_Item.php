<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Advanced Tabs Item (child) Module Class.
 *
 * A single tab panel: a title, optional icon and rich content. The parent
 * module builds the clickable tab navigation from these panels on the frontend.
 *
 * @since   4.2.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Content;

use DiviSquad\Builder\Version4\Abstracts\Module\Child_Module;
use DiviSquad\Utils\Divi;
use function esc_attr;
use function esc_html__;
use function et_pb_get_extended_font_icon_value;
use function wp_kses_post;
use function wpautop;

/**
 * Advanced Tabs Item (child) Module Class.
 *
 * @since 4.2.0
 */
class Advanced_Tabs_Item extends Child_Module {

	public function init(): void {
		$this->name   = esc_html__( 'Tab', 'squad-modules-for-divi' );
		$this->plural = esc_html__( 'Tabs', 'squad-modules-for-divi' );

		$this->slug             = 'disq_advanced_tabs_item';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.{$this->slug}";

		$this->child_title_var          = 'title';
		$this->child_title_fallback_var = 'admin_label';

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'content' => esc_html__( 'Tab', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'body' => esc_html__( 'Body Text', 'squad-modules-for-divi' ),
				),
			),
		);

		$this->advanced_fields = array(
			'background'     => divi_squad()->d4_module_helper->selectors_background( $this->main_css_element ),
			'borders'        => array(
				'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			),
			'margin_padding' => divi_squad()->d4_module_helper->selectors_margin_padding( "{$this->main_css_element} .squad-tab-panel__body" ),
			'fonts'          => array(
				'body' => array(
					'label'       => esc_html__( 'Body', 'squad-modules-for-divi' ),
					'css'         => array( 'main' => "{$this->main_css_element} .squad-tab-panel__body" ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'body',
					'font_size'   => array( 'default' => '15px' ),
					'line_height' => array( 'default' => '1.7em' ),
				),
			),
			'box_shadow'     => false,
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
			'title'    => array(
				'label'       => esc_html__( 'Tab Title', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'The label shown on the tab button.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => esc_html__( 'Tab', 'squad-modules-for-divi' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'content',
			),
			'tab_icon' => array(
				'label'           => esc_html__( 'Tab Icon', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'Optional icon shown beside the tab title.', 'squad-modules-for-divi' ),
				'type'            => 'select_icon',
				'option_category' => 'basic_option',
				'class'           => array( 'et-pb-font-icon' ),
				'default'         => '',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'content',
			),
			'content'  => array(
				'label'           => esc_html__( 'Content', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'The content shown when this tab is active.', 'squad-modules-for-divi' ),
				'type'            => 'tiny_mce',
				'option_category' => 'basic_option',
				'default'         => esc_html__( 'Tab content goes here.', 'squad-modules-for-divi' ),
				'tab_slug'        => 'general',
				'toggle_slug'     => 'content',
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
		$title = (string) $this->prop( 'title', '' );
		$body  = (string) $this->prop( 'content', '' );

		$icon_glyph = '';
		$icon_raw   = (string) $this->prop( 'tab_icon', '' );
		if ( '' !== $icon_raw ) {
			Divi::inject_fa_icons( $icon_raw );
			$icon_glyph = (string) et_pb_get_extended_font_icon_value( $icon_raw, true );
		}

		$body_html = '' !== $body ? sprintf( '<div class="squad-tab-panel__body">%s</div>', wpautop( wp_kses_post( $body ) ) ) : '';

		return sprintf(
			'<div class="squad-tab-panel" role="tabpanel" data-tab-title="%1$s" data-tab-icon="%2$s">%3$s</div>',
			esc_attr( $title ),
			esc_attr( $icon_glyph ),
			$body_html
		);
	}
}
