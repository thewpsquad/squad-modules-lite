<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Chat Button Module (Divi 4 shortcode).
 *
 * Parent module accepting Chat Button Channel children. Renders a floating
 * `.squad-chat-button` launcher (fixed-position FAB + greeting bubble +
 * expandable panel) whose children are chat channels (WhatsApp / Telegram /
 * Messenger / phone / email / custom URL). GDPR-friendly: pure deep links, no
 * third-party script. Optional online-hours schedule. A tiny frontend engine
 * (`chat-button.ts`) handles the toggle, outside-click/Esc close, and schedule.
 * No external lib dependency.
 *
 * @since   4.3.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Creative;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use DiviSquad\Builder\Shared\Modules\Creative\Chat_Button\Chat_Button_Helper;
use DiviSquad\Builder\Version4\Abstracts\Module;
use DiviSquad\Utils\Divi;
use function esc_attr;
use function esc_html__;
use function et_pb_get_extended_font_icon_value;
use function max;
use function min;
use function wp_enqueue_script;

/**
 * Chat Button Module class.
 *
 * @since 4.3.0
 */
class Chat_Button extends Module {

	/**
	 * Initiate Module.
	 *
	 * @since 4.3.0
	 * @return void
	 */
	public function init(): void {
		$this->name      = esc_html__( 'Chat Button', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Chat Buttons', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'chat-button.svg' );

		$this->slug             = 'disq_chat_button';
		$this->child_slug       = 'disq_chat_button_channel';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.{$this->slug}";

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'launcher_settings' => esc_html__( 'Launcher', 'squad-modules-for-divi' ),
					'panel_settings'    => esc_html__( 'Panel', 'squad-modules-for-divi' ),
					'schedule_settings' => esc_html__( 'Schedule', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'button' => esc_html__( 'Button', 'squad-modules-for-divi' ),
				),
			),
		);

		$this->advanced_fields = array(
			'background'     => divi_squad()->d4_module_helper->selectors_background( $this->main_css_element ),
			'borders'        => array(
				'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
				'button'  => array(
					'css'         => array(
						'main' => "{$this->main_css_element} .squad-chat-button__toggle",
					),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'button',
				),
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
			'chat_button' => array(
				'label'    => esc_html__( 'Chat Button', 'squad-modules-for-divi' ),
				'selector' => '.squad-chat-button',
			),
			'toggle'      => array(
				'label'    => esc_html__( 'Toggle', 'squad-modules-for-divi' ),
				'selector' => '.squad-chat-button__toggle',
			),
			'panel'       => array(
				'label'    => esc_html__( 'Panel', 'squad-modules-for-divi' ),
				'selector' => '.squad-chat-button__panel',
			),
			'channel'     => array(
				'label'    => esc_html__( 'Channel', 'squad-modules-for-divi' ),
				'selector' => '.squad-chat-button__channel',
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
			// Launcher.
			'position'         => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Position', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Which screen corner the floating button is pinned to.', 'squad-modules-for-divi' ),
					'options'     => array(
						'bottom-right' => esc_html__( 'Bottom Right', 'squad-modules-for-divi' ),
						'bottom-left'  => esc_html__( 'Bottom Left', 'squad-modules-for-divi' ),
						'top-right'    => esc_html__( 'Top Right', 'squad-modules-for-divi' ),
						'top-left'     => esc_html__( 'Top Left', 'squad-modules-for-divi' ),
					),
					'default'     => 'bottom-right',
					'tab_slug'    => 'general',
					'toggle_slug' => 'launcher_settings',
				)
			),
			'toggle_icon'      => array(
				'label'            => esc_html__( 'Toggle Icon', 'squad-modules-for-divi' ),
				'description'      => esc_html__( 'Pick an icon for the floating button. Leave empty to use the default chat bubble.', 'squad-modules-for-divi' ),
				'type'             => 'select_icon',
				'option_category'  => 'basic_option',
				'class'            => array( 'et-pb-font-icon' ),
				'default_on_front' => '',
				'tab_slug'         => 'general',
				'toggle_slug'      => 'launcher_settings',
			),
			'toggle_label'     => array(
				'label'       => esc_html__( 'Toggle Aria Label', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Accessible label for the floating button.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => esc_html__( 'Open chat', 'squad-modules-for-divi' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'launcher_settings',
			),
			// Panel.
			'header_title'     => array(
				'label'       => esc_html__( 'Panel Header', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Title shown at the top of the chat panel. Leave empty to hide.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => esc_html__( 'Chat with us', 'squad-modules-for-divi' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'panel_settings',
			),
			'greeting'         => array(
				'label'       => esc_html__( 'Greeting', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Short greeting bubble shown above the channels. Leave empty to hide.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'tab_slug'    => 'general',
				'toggle_slug' => 'panel_settings',
			),
			// Schedule.
			'schedule_enabled' => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Enable Schedule', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Only show the button during set online hours (visitor local time).', 'squad-modules-for-divi' ),
					'default'          => 'off',
					'default_on_front' => 'off',
					'affects'          => array( 'schedule_start', 'schedule_end' ),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'schedule_settings',
				)
			),
			'schedule_start'   => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Start Hour', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Hour (0-23) when the button starts showing.', 'squad-modules-for-divi' ),
					'range_settings'  => array(
						'min'  => '0',
						'max'  => '23',
						'step' => '1',
					),
					'default'         => '9',
					'unitless'        => true,
					'mobile_options'  => false,
					'hover'           => false,
					'depends_show_if' => 'on',
					'tab_slug'        => 'general',
					'toggle_slug'     => 'schedule_settings',
				)
			),
			'schedule_end'     => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'End Hour', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Hour (0-23) when the button stops showing.', 'squad-modules-for-divi' ),
					'range_settings'  => array(
						'min'  => '0',
						'max'  => '23',
						'step' => '1',
					),
					'default'         => '17',
					'unitless'        => true,
					'mobile_options'  => false,
					'hover'           => false,
					'depends_show_if' => 'on',
					'tab_slug'        => 'general',
					'toggle_slug'     => 'schedule_settings',
				)
			),
			// Advanced.
			'button_color'     => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Button Color', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Background color of the floating button.', 'squad-modules-for-divi' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'button',
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
		wp_enqueue_script( 'squad-module-chat-button' );

		$icon_glyph = '';
		$icon_raw   = (string) $this->prop( 'toggle_icon', '' );
		if ( '' !== $icon_raw ) {
			Divi::inject_fa_icons( $icon_raw );
			$icon_glyph = (string) et_pb_get_extended_font_icon_value( $icon_raw, true );
		}

		$config = array(
			'position'        => (string) $this->prop( 'position', 'bottom-right' ),
			'toggleIcon'      => $icon_glyph,
			'toggleLabel'     => (string) $this->prop( 'toggle_label', '' ),
			'headerTitle'     => (string) $this->prop( 'header_title', '' ),
			'greeting'        => (string) $this->prop( 'greeting', '' ),
			'scheduleEnabled' => 'on' === (string) $this->prop( 'schedule_enabled', 'off' ) ? 'on' : 'off',
			'scheduleStart'   => max( 0, min( 23, (int) $this->prop( 'schedule_start', '9' ) ) ),
			'scheduleEnd'     => max( 0, min( 23, (int) $this->prop( 'schedule_end', '17' ) ) ),
		);

		$this->apply_color_styles( $render_slug );

		return Chat_Button_Helper::build_widget( $config, $content );
	}

	/**
	 * Apply button color CSS via set_style.
	 *
	 * @since 4.3.0
	 *
	 * @param string $render_slug Module render slug.
	 *
	 * @return void
	 */
	protected function apply_color_styles( string $render_slug ): void {
		$button_color = self::sanitize_css_background( (string) $this->prop( 'button_color', '' ) );
		if ( '' !== $button_color ) {
			self::set_style(
				$render_slug,
				array(
					'selector'    => '%%order_class%% .squad-chat-button__toggle',
					'declaration' => sprintf( 'background-color: %s;', esc_attr( $button_color ) ),
				)
			);
		}
	}
}
