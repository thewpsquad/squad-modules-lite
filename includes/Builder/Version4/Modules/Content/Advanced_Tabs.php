<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Advanced Tabs Module Class.
 *
 * Parent module that turns its child Tab panels into an accessible tabbed
 * interface with horizontal / vertical layouts, an optional mobile accordion,
 * icon tabs and URL-hash deep-linking. The navigation is built on the frontend
 * from the rendered panels.
 *
 * @since   4.2.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Content;

use DiviSquad\Builder\Version4\Abstracts\Module;
use function absint;
use function esc_attr;
use function esc_html__;
use function in_array;
use function max;
use function wp_enqueue_script;

/**
 * Advanced Tabs Module Class.
 *
 * @since 4.2.0
 */
class Advanced_Tabs extends Module {

	public function init(): void {
		$this->name      = esc_html__( 'Advanced Tabs', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Advanced Tabs', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'advanced-tabs.svg' );

		$this->slug             = 'disq_advanced_tabs';
		$this->child_slug       = 'disq_advanced_tabs_item';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.{$this->slug}";

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'layout'  => esc_html__( 'Layout', 'squad-modules-for-divi' ),
					'options' => esc_html__( 'Options', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'nav'    => esc_html__( 'Tab Navigation', 'squad-modules-for-divi' ),
					'active' => esc_html__( 'Active Tab', 'squad-modules-for-divi' ),
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
				'nav' => array(
					'label'       => esc_html__( 'Tab', 'squad-modules-for-divi' ),
					'css'         => array( 'main' => "{$this->main_css_element} .squad-tabs__nav-item" ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'nav',
					'font_size'   => array( 'default' => '16px' ),
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
	 * @since 4.2.0
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_fields(): array {
		return array(
			'layout'           => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Layout', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Position the tab navigation above (horizontal) or beside (vertical) the content.', 'squad-modules-for-divi' ),
					'options'          => array(
						'horizontal' => esc_html__( 'Horizontal', 'squad-modules-for-divi' ),
						'vertical'   => esc_html__( 'Vertical', 'squad-modules-for-divi' ),
					),
					'default'          => 'horizontal',
					'default_on_front' => 'horizontal',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'layout',
				)
			),
			'tab_alignment'    => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Tab Alignment', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Horizontal alignment of the tab buttons.', 'squad-modules-for-divi' ),
					'options'          => array(
						'left'   => esc_html__( 'Left', 'squad-modules-for-divi' ),
						'center' => esc_html__( 'Center', 'squad-modules-for-divi' ),
						'right'  => esc_html__( 'Right', 'squad-modules-for-divi' ),
					),
					'default'          => 'left',
					'default_on_front' => 'left',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'layout',
				)
			),
			'active_tab'       => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Default Active Tab', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Which tab is open by default (1 = first).', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '1', 'max' => '20', 'step' => '1' ),
					'default'        => '1',
					'unitless'       => true,
					'tab_slug'       => 'general',
					'toggle_slug'    => 'options',
				)
			),
			'mobile_accordion' => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Accordion on Mobile', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Collapse the tabs into a stacked accordion on small screens.', 'squad-modules-for-divi' ),
					'default'          => 'on',
					'default_on_front' => 'on',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'options',
				)
			),
			'enable_hash'      => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Deep-link via URL Hash', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Update the URL hash when a tab opens so tabs can be linked to directly.', 'squad-modules-for-divi' ),
					'default'          => 'off',
					'default_on_front' => 'off',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'options',
				)
			),
			'tab_text_color'   => array(
				'label'       => esc_html__( 'Tab Text Color', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Text color of inactive tabs.', 'squad-modules-for-divi' ),
				'type'        => 'color-alpha',
				'default'     => '',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'nav',
			),
			'active_bg_color'  => array(
				'label'       => esc_html__( 'Active Tab Background', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Background color of the active tab.', 'squad-modules-for-divi' ),
				'type'        => 'color-alpha',
				'default'     => '#5E2EFF',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'active',
			),
			'active_text_color' => array(
				'label'       => esc_html__( 'Active Tab Text Color', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Text color of the active tab.', 'squad-modules-for-divi' ),
				'type'        => 'color-alpha',
				'default'     => '#ffffff',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'active',
			),
		);
	}

	/**
	 * Render module output.
	 *
	 * @since 4.2.0
	 *
	 * @param array<string, mixed> $attrs       List of attributes.
	 * @param string               $content     Content being processed (child panels).
	 * @param string               $render_slug Slug of module that is used for rendering output.
	 *
	 * @return string
	 */
	public function render( $attrs, $content, $render_slug ): string {
		if ( '' === trim( (string) $content ) ) {
			return sprintf(
				'<div class="squad-notice">%s</div>',
				esc_html__( 'Add at least one Tab.', 'squad-modules-for-divi' )
			);
		}

		wp_enqueue_script( 'squad-module-advanced-tabs' );

		$this->apply_tabs_css( $render_slug );

		$layout = (string) $this->prop( 'layout', 'horizontal' );
		$layout = in_array( $layout, array( 'horizontal', 'vertical' ), true ) ? $layout : 'horizontal';

		$align = (string) $this->prop( 'tab_alignment', 'left' );
		$align = in_array( $align, array( 'left', 'center', 'right' ), true ) ? $align : 'left';

		$active     = max( 1, absint( $this->prop( 'active_tab', '1' ) ) );
		$accordion  = 'on' === $this->prop( 'mobile_accordion', 'on' );
		$enable_hash = 'on' === $this->prop( 'enable_hash', 'off' );

		return sprintf(
			'<div class="squad-tabs squad-tabs--%1$s squad-tabs--align-%2$s%3$s" data-active="%4$d" data-accordion="%5$s" data-hash="%6$s"><div class="squad-tabs__nav" role="tablist"></div><div class="squad-tabs__panels">%7$s</div></div>',
			esc_attr( $layout ),
			esc_attr( $align ),
			$accordion ? ' squad-tabs--mobile-accordion' : '',
			$active - 1,
			$accordion ? 'on' : 'off',
			$enable_hash ? 'on' : 'off',
			(string) $content
		);
	}

	/**
	 * Emit active-tab and inactive-tab colour CSS.
	 *
	 * @since 4.2.0
	 *
	 * @param string $render_slug Slug of module that is used for rendering output.
	 *
	 * @return void
	 */
	public function apply_tabs_css( string $render_slug ): void {
		$tab_color = self::sanitize_css_background( (string) $this->prop( 'tab_text_color', '' ) );
		if ( '' !== $tab_color ) {
			self::set_style(
				$render_slug,
				array(
					'selector'    => '%%order_class%% .squad-tabs__nav-item',
					'declaration' => sprintf( 'color: %s;', esc_attr( $tab_color ) ),
				)
			);
		}

		$active_bg = self::sanitize_css_background( (string) $this->prop( 'active_bg_color', '#5E2EFF' ) );
		if ( '' !== $active_bg ) {
			self::set_style(
				$render_slug,
				array(
					'selector'    => '%%order_class%% .squad-tabs__nav-item.is-active',
					'declaration' => sprintf( 'background: %s; border-color: %s;', esc_attr( $active_bg ), esc_attr( $active_bg ) ),
				)
			);
		}

		$active_color = self::sanitize_css_background( (string) $this->prop( 'active_text_color', '#ffffff' ) );
		if ( '' !== $active_color ) {
			self::set_style(
				$render_slug,
				array(
					'selector'    => '%%order_class%% .squad-tabs__nav-item.is-active',
					'declaration' => sprintf( 'color: %s;', esc_attr( $active_color ) ),
				)
			);
		}
	}
}
