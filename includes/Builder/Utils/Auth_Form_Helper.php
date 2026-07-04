<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Shared helper for the Auth form modules (Login, Register, Lost/Reset Password).
 *
 * Centralises the "already logged in" guard so every Auth module — across both
 * the Divi 4 shortcode and Divi 5 Block API — behaves identically.
 *
 * @since   4.2.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use DiviSquad\Utils\Divi as DiviUtil;
use WP_User;
use function esc_html;
use function esc_html__;
use function esc_url;
use function is_user_logged_in;
use function wp_get_current_user;
use function wp_logout_url;

/**
 * Auth form helper.
 *
 * @since 4.2.0
 */
class Auth_Form_Helper {

	/**
	 * Whether the auth form should render for the current request.
	 *
	 * The form is hidden from visitors who are already logged in on the
	 * front-end, but stays visible inside the Divi Visual Builder so admins
	 * can still style it while editing.
	 *
	 * @since 4.2.0
	 *
	 * @return bool True when the form should render, false when the logged-in
	 *              notice should be shown instead.
	 */
	public static function should_render_form(): bool {
		if ( ! is_user_logged_in() ) {
			return true;
		}

		// Logged in — only keep the form visible inside the Visual Builder.
		return DiviUtil::is_fb_enabled();
	}

	/**
	 * "You are already logged in" notice with a logout link.
	 *
	 * Returns an empty string when the form should render normally, so callers
	 * can use it as an early-return guard:
	 *
	 *     $notice = Auth_Form_Helper::logged_in_notice();
	 *     if ( '' !== $notice ) {
	 *         return $notice;
	 *     }
	 *
	 * The markup reuses the `.disq-login-form` wrapper so it inherits the same
	 * panel styling as the forms themselves.
	 *
	 * @since 4.2.0
	 *
	 * @return string Notice HTML, or empty string when the form should render.
	 */
	public static function logged_in_notice(): string {
		if ( self::should_render_form() ) {
			return '';
		}

		$user         = wp_get_current_user();
		$display_name = $user instanceof WP_User ? $user->display_name : '';
		$logout_url   = wp_logout_url();

		ob_start();
		?>
		<div class="disq-login-form disq-login-form--card disq-login-form--logged-in">
			<div class="disq-login-form__panel">
				<p class="disq-login-form__logged-in-message">
					<?php
					printf(
						/* translators: %s: current user display name. */
						esc_html__( 'You are already logged in as %s.', 'squad-modules-for-divi' ),
						esc_html( $display_name )
					);
					?>
				</p>
				<a class="disq-login-form__logout" href="<?php echo esc_url( $logout_url ); ?>">
					<?php echo esc_html__( 'Log out', 'squad-modules-for-divi' ); ?>
				</a>
			</div>
		</div>
		<?php

		return (string) ob_get_clean();
	}
}
