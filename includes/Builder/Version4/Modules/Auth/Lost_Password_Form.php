<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Lost Password Form Module — Divi 4.
 *
 * @since   4.2.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Auth;

use DiviSquad\Builder\Utils\Auth_Form_Helper;
use DiviSquad\Builder\Version4\Abstracts\Module;
use Throwable;
use function esc_attr;
use function esc_html;
use function esc_html__;
use function esc_url;
use function get_custom_logo;
use function sanitize_key;
use function sanitize_text_field;
use function site_url;
use function wp_login_url;

/**
 * Lost Password Form D4 module.
 *
 * @since 4.2.0
 */
class Lost_Password_Form extends Module {

	public function init(): void {
		$this->name      = esc_html__( 'Lost Password Form', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Lost Password Forms', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'lost-password-form.svg' );

		$this->slug             = 'disq_lost_password_form';
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
						'css'         => array( 'main' => "$this->main_css_element .disq-lostpw-form__title" ),
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
			'layout'          => array(
				'label'       => esc_html__( 'Layout', 'squad-modules-for-divi' ),
				'type'        => 'select',
				'options'     => array( 'card' => esc_html__( 'Card', 'squad-modules-for-divi' ), 'split' => esc_html__( 'Split', 'squad-modules-for-divi' ) ),
				'default'     => 'card',
				'tab_slug'    => 'general',
				'toggle_slug' => 'layout',
			),
			'show_logo'       => array(
				'label'       => esc_html__( 'Show Logo', 'squad-modules-for-divi' ),
				'type'        => 'yes_no_button',
				'options'     => array( 'off' => esc_html__( 'No', 'squad-modules-for-divi' ), 'on' => esc_html__( 'Yes', 'squad-modules-for-divi' ) ),
				'default'     => 'on',
				'tab_slug'    => 'general',
				'toggle_slug' => 'header_element',
			),
			'title_text'      => array(
				'label'       => esc_html__( 'Title', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => esc_html__( 'Reset your password', 'squad-modules-for-divi' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'header_element',
			),
			'show_subtitle'   => array(
				'label'       => esc_html__( 'Show Subtitle', 'squad-modules-for-divi' ),
				'type'        => 'yes_no_button',
				'options'     => array( 'off' => esc_html__( 'No', 'squad-modules-for-divi' ), 'on' => esc_html__( 'Yes', 'squad-modules-for-divi' ) ),
				'default'     => 'on',
				'tab_slug'    => 'general',
				'toggle_slug' => 'header_element',
			),
			'subtitle_text'   => array(
				'label'       => esc_html__( 'Subtitle', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => esc_html__( "Enter your email and we'll send a reset link.", 'squad-modules-for-divi' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'header_element',
				'show_if'     => array( 'show_subtitle' => 'on' ),
			),
			'username_label'  => array(
				'label'       => esc_html__( 'Username Label', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => esc_html__( 'Username or Email', 'squad-modules-for-divi' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'fields_element',
			),
			'button_text'     => array(
				'label'       => esc_html__( 'Button Text', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => esc_html__( 'Send Reset Link', 'squad-modules-for-divi' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'button_element',
			),
			'show_login_link' => array(
				'label'       => esc_html__( 'Show Login Link', 'squad-modules-for-divi' ),
				'type'        => 'yes_no_button',
				'options'     => array( 'off' => esc_html__( 'No', 'squad-modules-for-divi' ), 'on' => esc_html__( 'Yes', 'squad-modules-for-divi' ) ),
				'default'     => 'on',
				'tab_slug'    => 'general',
				'toggle_slug' => 'button_element',
			),
			'success_message' => array(
				'label'       => esc_html__( 'Success Message', 'squad-modules-for-divi' ),
				'type'        => 'textarea',
				'default'     => esc_html__( 'Check your email for a reset link.', 'squad-modules-for-divi' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'button_element',
			),
		);
	}

	/**
	 * Render the lost password form.
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

			$layout          = sanitize_text_field( $this->props['layout'] ?? 'card' );
			$show_logo       = 'on' === ( $this->props['show_logo'] ?? 'on' );
			$title_text      = esc_html( $this->props['title_text'] ?? __( 'Reset your password', 'squad-modules-for-divi' ) );
			$show_subtitle   = 'on' === ( $this->props['show_subtitle'] ?? 'on' );
			$subtitle_text   = esc_html( $this->props['subtitle_text'] ?? '' );
			$username_label  = esc_html( $this->props['username_label'] ?? __( 'Username or Email', 'squad-modules-for-divi' ) );
			$button_text     = esc_html( $this->props['button_text'] ?? __( 'Send Reset Link', 'squad-modules-for-divi' ) );
			$show_login      = 'on' === ( $this->props['show_login_link'] ?? 'on' );
			$success_message = esc_html( $this->props['success_message'] ?? __( 'Check your email for a reset link.', 'squad-modules-for-divi' ) );

			// phpcs:ignore WordPress.Security.NonceVerification
			$success = isset( $_GET['checkemail'] ) && 'confirm' === sanitize_key( $_GET['checkemail'] );

			// phpcs:ignore WordPress.Security.NonceVerification
			$error_value = sanitize_key( $_GET['error'] ?? '' );
			$error_map   = array(
				'invalidkey' => __( 'This reset link is invalid or has expired.', 'squad-modules-for-divi' ),
				'expiredkey' => __( 'This reset link has expired. Request a new one.', 'squad-modules-for-divi' ),
			);
			$error_msg   = '' !== $error_value ? esc_html( $error_map[ $error_value ] ?? '' ) : '';

			$action_url = esc_url( site_url( 'wp-login.php?action=lostpassword', 'login_post' ) );

			ob_start();
			?>
			<div class="disq-lostpw-form disq-lostpw-form--<?php echo esc_attr( $layout ); ?>">
				<?php if ( 'split' === $layout ) : ?>
					<div class="disq-lostpw-form__brand"></div><?php endif; ?>
				<div class="disq-lostpw-form__panel">
					<?php if ( $show_logo ) : ?>
						<div class="disq-lostpw-form__logo"><?php echo get_custom_logo(); // phpcs:ignore WordPress.Security.EscapeOutput ?></div><?php endif; ?>
					<h2 class="disq-lostpw-form__title"><?php echo $title_text; ?></h2>
					<?php if ( $show_subtitle && '' !== $subtitle_text ) : ?><p class="disq-lostpw-form__subtitle"><?php echo $subtitle_text; ?></p><?php endif; ?>
					<?php if ( $success ) : ?>
						<div class="disq-lostpw-form__success" role="status"><?php echo $success_message; ?></div>
					<?php else : ?>
						<?php if ( '' !== $error_msg ) : ?>
							<div class="disq-lostpw-form__error" role="alert"><?php echo $error_msg; ?></div><?php endif; ?>
						<form class="disq-lostpw-form__form" action="<?php echo $action_url; ?>" method="post">
							<div class="disq-lostpw-form__field">
								<label class="disq-lostpw-form__label" for="disq-lostpw-login"><?php echo $username_label; ?></label>
								<input id="disq-lostpw-login" class="disq-lostpw-form__input" type="text" name="user_login" autocomplete="username" required/>
							</div>
							<button type="submit" class="disq-lostpw-form__submit"><?php echo $button_text; ?></button>
						</form>
					<?php endif; ?>
					<?php if ( $show_login ) : ?>
						<nav class="disq-lostpw-form__nav"><a href="<?php echo esc_url( wp_login_url() ); ?>"><?php esc_html_e( 'Back to login', 'squad-modules-for-divi' ); ?></a></nav><?php endif; ?>
				</div>
			</div>
			<?php
			return (string) ob_get_clean();
		} catch ( Throwable $e ) {
			ob_get_clean();
			divi_squad()->log_error( $e, 'Lost Password Form D4 render failed' );

			return '';
		}
	}
}
