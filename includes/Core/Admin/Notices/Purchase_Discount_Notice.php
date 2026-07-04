<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Discount Notice Class
 *
 * Handles the display of the welcome discount notice.
 *
 * @since   3.3.3
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Core\Admin\Notices;

/**
 * Discount Notice Class
 *
 * @since   3.3.3
 * @package DiviSquad
 */
class Purchase_Discount_Notice extends Notice_Base {

	/**
	 * The notice ID.
	 *
	 * @var string
	 */
	protected string $notice_id = 'welcome-60%-discount';

	/**
	 * Check if the notice can be rendered.
	 *
	 * @since 3.3.3
	 *
	 * @return bool Whether the notice can be rendered.
	 */
	public function can_render_it(): bool {
		static $can_render = null;

		try {
			if ( null === $can_render ) {
				$can_use_premium_code = divi_squad_fs()->can_use_premium_code();
				$is_pro_notice_closed = (bool) divi_squad()->memory->get( 'beta_campaign_notice_close', false );

				$can_render = ! $can_use_premium_code && ! $is_pro_notice_closed;
			}
		} catch ( \Throwable $e ) {
			$can_render = false;
		}

		return (bool) $can_render;
	}

	/**
	 * Get the notice template arguments.
	 *
	 * @since 3.3.3
	 *
	 * @return array<string, mixed> The notice template arguments.
	 */
	public function get_template_args(): array {
		return array(
			'wrapper_classes' => 'divi-squad-success-banner welcome-discount',
			'logo'            => 'logos/divi-squad-d-default.svg',
			'content'         => sprintf(
			/* Translators: %1$s is the welcome message, %2$s is the coupon code. */
				esc_html__( '%1$s Get a special discount and start building stunning websites today. Use code "%2$s" at checkout.', 'squad-modules-for-divi' ),
				sprintf( '<strong>%s</strong>', esc_html__( 'Unleash Your Divi Creativity with Squad Modules Pro!', 'squad-modules-for-divi' ) ),
				'<code>WELCOME60</code>'
			),
			'is_dismissible'  => true,
		);
	}
}
