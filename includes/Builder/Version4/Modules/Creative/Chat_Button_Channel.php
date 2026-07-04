<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Chat Button Channel (child) Module (Divi 4 shortcode).
 *
 * Represents a single chat channel inside the Chat Button parent: a deep-link
 * row (WhatsApp / Telegram / Messenger / phone / email / custom URL) with an
 * auto brand-neutral icon and an optional label. Pure deep links, no
 * third-party script.
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
use DiviSquad\Builder\Version4\Abstracts\Module\Child_Module;
use function esc_html__;

/**
 * Chat Button Channel (child) Module class.
 *
 * @since 4.3.0
 */
class Chat_Button_Channel extends Child_Module {

	/**
	 * Initiate Module.
	 *
	 * @since 4.3.0
	 * @return void
	 */
	public function init(): void {
		$this->name   = esc_html__( 'Chat Button Channel', 'squad-modules-for-divi' );
		$this->plural = esc_html__( 'Chat Button Channels', 'squad-modules-for-divi' );

		$this->slug             = 'disq_chat_button_channel';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.{$this->slug}";

		$this->child_title_var          = 'label';
		$this->child_title_fallback_var = 'admin_label';

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		$this->settings_modal_toggles = array(
			'general' => array(
				'toggles' => array(
					'channel_settings' => esc_html__( 'Channel', 'squad-modules-for-divi' ),
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
			'channel_type'     => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Channel Type', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Which chat service this channel opens.', 'squad-modules-for-divi' ),
					'options'     => array(
						'whatsapp'  => esc_html__( 'WhatsApp', 'squad-modules-for-divi' ),
						'telegram'  => esc_html__( 'Telegram', 'squad-modules-for-divi' ),
						'messenger' => esc_html__( 'Messenger', 'squad-modules-for-divi' ),
						'phone'     => esc_html__( 'Phone', 'squad-modules-for-divi' ),
						'email'     => esc_html__( 'Email', 'squad-modules-for-divi' ),
						'custom'    => esc_html__( 'Custom URL', 'squad-modules-for-divi' ),
					),
					'default'     => 'whatsapp',
					'tab_slug'    => 'general',
					'toggle_slug' => 'channel_settings',
				)
			),
			'identifier'       => array(
				'label'       => esc_html__( 'Identifier', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Phone number, username, email, or URL depending on the channel type.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'tab_slug'    => 'general',
				'toggle_slug' => 'channel_settings',
			),
			'prefilled_message' => array(
				'label'       => esc_html__( 'Prefilled Message', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Message pre-populated for the visitor (WhatsApp and email only).', 'squad-modules-for-divi' ),
				'type'        => 'textarea',
				'default'     => '',
				'tab_slug'    => 'general',
				'toggle_slug' => 'channel_settings',
			),
			'label'            => array(
				'label'       => esc_html__( 'Label', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Text shown next to the channel icon. Defaults to the channel type.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'tab_slug'    => 'general',
				'toggle_slug' => 'channel_settings',
			),
			'channel_color'    => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Channel Color', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Accent color for this channel row.', 'squad-modules-for-divi' ),
					'tab_slug'    => 'general',
					'toggle_slug' => 'channel_settings',
				)
			),
		);
	}

	/**
	 * Render the channel HTML.
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
		$channel = array(
			'type'       => (string) $this->prop( 'channel_type', 'whatsapp' ),
			'identifier' => (string) $this->prop( 'identifier', '' ),
			'message'    => (string) $this->prop( 'prefilled_message', '' ),
			'label'      => (string) $this->prop( 'label', '' ),
			'color'      => (string) $this->prop( 'channel_color', '' ),
		);

		return Chat_Button_Helper::build_channel( $channel );
	}
}
