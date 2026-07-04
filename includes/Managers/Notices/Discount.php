<?php // phpcs:ignore WordPress.Files.FileName

/**
 * The Welcome Campaign Class for the plugin.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   2.0.0
 */

namespace DiviSquad\Managers\Notices;

use DiviSquad\Base\Factories\AdminNotice\Notice;
use function divi_squad;
use function esc_html__;

/**
 * Welcome (60%) Campaign Class
 *
 * @package DiviSquad
 * @since   2.0.0
 */
class Discount extends Notice {

	/**
	 * The notice id for the notice.
	 *
	 * @var string
	 */
	protected $notice_id = 'welcome-60%-discount';

	/**
	 * Check if we can render notice.
	 */
	public function can_render_it(): bool {
		static $can_render = false;

		if ( ! isset( $can_render ) ) {
			$can_use_premium_code = divi_squad_fs() instanceof \Freemius && divi_squad_fs()->can_use_premium_code();
			$is_pro_notice_closed = divi_squad()->memory->get( 'beta_campaign_notice_close', false );

			$can_render = ! $can_use_premium_code && ! $is_pro_notice_closed;
		}

		return $can_render;
	}

	/**
	 * Filters the CSS classes for the body tag in the admin.
	 *
	 * @return string
	 * @since 1.2.5
	 */
	public function get_body_classes(): string {
		return ' divi-squad-notice';
	}

	/**
	 * Get the template args.
	 *
	 * @return array
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
