<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Reset Password Form Module — Divi 4.
 *
 * @since   4.2.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Auth;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use DiviSquad\Builder\Utils\Auth_Form_Helper;
use DiviSquad\Builder\Version4\Abstracts\Module;
use Throwable;
use function esc_attr;
use function esc_html;
use function esc_html__;
use function esc_url;
use function get_custom_logo;
use function sanitize_text_field;
use function site_url;
use function wp_enqueue_script;
use function wp_generate_password;

/**
 * Reset Password Form D4 module.
 *
 * @since 4.2.0
 */
class Reset_Password_Form extends Module {

	public function init(): void {
		$this->name      = esc_html__( 'Reset Password Form', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Reset Password Forms', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'reset-password-form.svg' );

		$this->slug             = 'disq_reset_password_form';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.$this->slug";

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'layout'         => esc_html__( 'Layout', 'squad-modules-for-divi' ),
					'header_element' => esc_html__( 'Header', 'squad-modules-for-divi' ),
					'fields_element' => esc_html__( 'Fields', 'squad-modules-for-divi' ),
					'button_element' => esc_html__( 'Button', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'title_text'    => esc_html__( 'Title', 'squad-modules-for-divi' ),
					'input_element' => esc_html__( 'Input Fields', 'squad-modules-for-divi' ),
					'button_text'   => esc_html__( 'Button', 'squad-modules-for-divi' ),
				),
			),
		);

		$this->advanced_fields = array(
			'fonts'          => array(
				'title_text' => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Title', 'squad-modules-for-divi' ),
					array(
						'css'         => array( 'main' => "$this->main_css_element .disq-resetpw-form__title" ),
						'tab_slug'    => 'advanced',
						'toggle_slug' => 'title_text',
					)
				),
			),
			'background'     => divi_squad()->d4_module_helper->selectors_background( $this->main_css_element ),
			'borders'        => array( 'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ) ),
			'box_shadow'     => array( 'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ) ),
			'margin_padding' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			'button'         => false,
			'link_options'   => false,
			'text'           => false,
		);
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public function get_fields(): array {
		return array(
			'layout'                 => array(
				'label'       => esc_html__( 'Layout', 'squad-modules-for-divi' ),
				'type'        => 'select',
				'options'     => array( 'card' => esc_html__( 'Card', 'squad-modules-for-divi' ), 'split' => esc_html__( 'Split', 'squad-modules-for-divi' ) ),
				'default'     => 'card',
				'tab_slug'    => 'general',
				'toggle_slug' => 'layout',
			),
			'show_logo'              => array(
				'label'       => esc_html__( 'Show Logo', 'squad-modules-for-divi' ),
				'type'        => 'yes_no_button',
				'options'     => array( 'off' => esc_html__( 'No', 'squad-modules-for-divi' ), 'on' => esc_html__( 'Yes', 'squad-modules-for-divi' ) ),
				'default'     => 'on',
				'tab_slug'    => 'general',
				'toggle_slug' => 'header_element',
			),
			'title_text'             => array(
				'label'       => esc_html__( 'Title', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => esc_html__( 'Set new password', 'squad-modules-for-divi' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'header_element',
			),
			'new_password_label'     => array(
				'label'       => esc_html__( 'New Password Label', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => esc_html__( 'New Password', 'squad-modules-for-divi' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'fields_element',
			),
			'confirm_password_label' => array(
				'label'       => esc_html__( 'Confirm Password Label', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => esc_html__( 'Confirm Password', 'squad-modules-for-divi' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'fields_element',
			),
			'show_strength_meter'    => array(
				'label'       => esc_html__( 'Show Strength Meter', 'squad-modules-for-divi' ),
				'type'        => 'yes_no_button',
				'options'     => array( 'off' => esc_html__( 'No', 'squad-modules-for-divi' ), 'on' => esc_html__( 'Yes', 'squad-modules-for-divi' ) ),
				'default'     => 'on',
				'tab_slug'    => 'general',
				'toggle_slug' => 'fields_element',
			),
			'button_text'            => array(
				'label'       => esc_html__( 'Button Text', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => esc_html__( 'Save Password', 'squad-modules-for-divi' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'button_element',
			),
		);
	}

	/**
	 * Render the reset password form.
	 *
	 * @param array<array-key, mixed> $attrs       Module attributes.
	 * @param string                  $content     Inner content (unused).
	 * @param string                  $render_slug Module slug.
	 *
	 * @return string HTML output.
	 */
	public function render( $attrs, $content, $render_slug ): string {
		try {
			// Already logged in (and not styling in the Visual Builder) — show a notice instead of the form.
			$logged_in_notice = Auth_Form_Helper::logged_in_notice();
			if ( '' !== $logged_in_notice ) {
				return $logged_in_notice;
			}

			$layout                 = sanitize_text_field( $this->props['layout'] ?? 'card' );
			$show_logo              = 'on' === ( $this->props['show_logo'] ?? 'on' );
			$title_text             = esc_html( $this->props['title_text'] ?? __( 'Set new password', 'squad-modules-for-divi' ) );
			$new_password_label     = esc_html( $this->props['new_password_label'] ?? __( 'New Password', 'squad-modules-for-divi' ) );
			$confirm_password_label = esc_html( $this->props['confirm_password_label'] ?? __( 'Confirm Password', 'squad-modules-for-divi' ) );
			$show_strength          = 'on' === ( $this->props['show_strength_meter'] ?? 'on' );
			$button_text            = esc_html( $this->props['button_text'] ?? __( 'Save Password', 'squad-modules-for-divi' ) );

			// phpcs:disable WordPress.Security.NonceVerification
			$rp_key   = sanitize_text_field( wp_unslash( $_GET['key'] ?? '' ) );
			$rp_login = sanitize_text_field( wp_unslash( $_GET['login'] ?? '' ) );
			// phpcs:enable

			if ( '' === $rp_key || '' === $rp_login ) {
				return '<div class="disq-resetpw-form disq-resetpw-form--error"><p>' .
				       esc_html__( 'Invalid or missing reset link. Request a new one.', 'squad-modules-for-divi' ) .
				       '</p></div>';
			}

			if ( $show_strength ) {
				wp_enqueue_script( 'user-profile' );
			}

			$action_url = esc_url( site_url( 'wp-login.php?action=resetpass', 'login_post' ) );

			ob_start();
			?>
			<div class="disq-resetpw-form disq-resetpw-form--<?php echo esc_attr( $layout ); ?>">
				<?php if ( 'split' === $layout ) : ?>
					<div class="disq-resetpw-form__brand"></div><?php endif; ?>
				<div class="disq-resetpw-form__panel">
					<?php if ( $show_logo ) : ?>
						<div class="disq-resetpw-form__logo"><?php echo get_custom_logo(); // phpcs:ignore WordPress.Security.EscapeOutput ?></div><?php endif; ?>
					<h2 class="disq-resetpw-form__title"><?php echo $title_text; ?></h2>
					<form class="disq-resetpw-form__form" action="<?php echo $action_url; ?>" method="post" autocomplete="off">
						<input type="hidden" name="rp_key" value="<?php echo esc_attr( $rp_key ); ?>"/>
						<input type="hidden" name="rp_login" value="<?php echo esc_attr( $rp_login ); ?>"/>
						<input type="hidden" name="user_login" value="<?php echo esc_attr( $rp_login ); ?>" class="hide-if-no-js"/>
						<div class="disq-resetpw-form__field">
							<label class="disq-resetpw-form__label" for="disq-pass1"><?php echo $new_password_label; ?></label>
							<div class="wp-pwd">
								<input
									id="disq-pass1"
									class="disq-resetpw-form__input"
									type="password"
									name="pass1"
									autocomplete="new-password"
									required
									<?php if ( $show_strength ) : ?>
										data-reveal="1"
										data-pw="<?php echo esc_attr( wp_generate_password( 16 ) ); ?>"
									<?php endif; ?>
								/>
							</div>
						</div>
						<div class="disq-resetpw-form__field">
							<label class="disq-resetpw-form__label" for="disq-pass2"><?php echo $confirm_password_label; ?></label>
							<input id="disq-pass2" class="disq-resetpw-form__input" type="password" name="pass2" autocomplete="new-password" required/>
						</div>
						<?php if ( $show_strength ) : ?>
							<div class="disq-resetpw-form__strength">
								<div id="pass-strength-result" aria-live="polite"></div>
							</div>
						<?php endif; ?>
						<button type="submit" class="disq-resetpw-form__submit"><?php echo $button_text; ?></button>
					</form>
				</div>
			</div>
			<?php
			return (string) ob_get_clean();
		} catch ( Throwable $e ) {
			ob_get_clean();
			divi_squad()->log_error( $e, 'Reset Password Form D4 render failed' );

			return '';
		}
	}
}
