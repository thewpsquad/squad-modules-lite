<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Register Form Module — Divi 4.
 *
 * @since   4.2.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Auth;

use DiviSquad\Builder\Utils\Auth_Form_Helper;
use DiviSquad\Builder\Version4\Abstracts\Module;
use Throwable;
use function add_query_arg;
use function esc_attr;
use function esc_html;
use function esc_html__;
use function esc_url;
use function get_custom_logo;
use function get_option;
use function sanitize_key;
use function sanitize_text_field;
use function site_url;
use function wp_kses_post;
use function wp_login_url;

/**
 * Register Form D4 module.
 *
 * @since 4.2.0
 */
class Register_Form extends Module {

	/**
	 * @var array<string, string>
	 */
	private const ERROR_MAP = array(
		'username_exists'  => 'That username is already taken.',
		'email_exists'     => 'An account with that email already exists.',
		'invalid_username' => 'Invalid username.',
		'invalid_email'    => 'Please enter a valid email address.',
	);

	public function init(): void {
		$this->name      = esc_html__( 'Register Form', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Register Forms', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'register-form.svg' );

		$this->slug             = 'disq_register_form';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.$this->slug";

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'layout'          => esc_html__( 'Layout', 'squad-modules-for-divi' ),
					'header_element'  => esc_html__( 'Header', 'squad-modules-for-divi' ),
					'fields_element'  => esc_html__( 'Fields', 'squad-modules-for-divi' ),
					'options_element' => esc_html__( 'Options', 'squad-modules-for-divi' ),
					'button_element'  => esc_html__( 'Button', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'title_text'    => esc_html__( 'Title', 'squad-modules-for-divi' ),
					'label_text'    => esc_html__( 'Labels', 'squad-modules-for-divi' ),
					'input_element' => esc_html__( 'Input Fields', 'squad-modules-for-divi' ),
					'button_text'   => esc_html__( 'Button', 'squad-modules-for-divi' ),
					'link_text'     => esc_html__( 'Links', 'squad-modules-for-divi' ),
				),
			),
		);

		$this->advanced_fields = array(
			'fonts'          => array(
				'title_text'  => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Title', 'squad-modules-for-divi' ),
					array(
						'css'         => array(
							'main'  => "$this->main_css_element .disq-register-form__title",
							'hover' => "$this->main_css_element .disq-register-form__title:hover",
						),
						'tab_slug'    => 'advanced',
						'toggle_slug' => 'title_text',
					)
				),
				'label_text'  => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Labels', 'squad-modules-for-divi' ),
					array(
						'css'         => array(
							'main'  => "$this->main_css_element .disq-register-form__label",
							'hover' => "$this->main_css_element .disq-register-form__label:hover",
						),
						'tab_slug'    => 'advanced',
						'toggle_slug' => 'label_text',
					)
				),
				'button_text' => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Button', 'squad-modules-for-divi' ),
					array(
						'css'         => array(
							'main'  => "$this->main_css_element .disq-register-form__submit",
							'hover' => "$this->main_css_element .disq-register-form__submit:hover",
						),
						'tab_slug'    => 'advanced',
						'toggle_slug' => 'button_text',
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
				'options'     => array(
					'card'  => esc_html__( 'Card', 'squad-modules-for-divi' ),
					'split' => esc_html__( 'Split', 'squad-modules-for-divi' ),
				),
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
				'default'     => esc_html__( 'Create an account', 'squad-modules-for-divi' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'header_element',
			),
			'username_label'  => array(
				'label'       => esc_html__( 'Username Label', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => esc_html__( 'Username', 'squad-modules-for-divi' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'fields_element',
			),
			'email_label'     => array(
				'label'       => esc_html__( 'Email Label', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => esc_html__( 'Email Address', 'squad-modules-for-divi' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'fields_element',
			),
			'show_login_link' => array(
				'label'       => esc_html__( 'Show Login Link', 'squad-modules-for-divi' ),
				'type'        => 'yes_no_button',
				'options'     => array( 'off' => esc_html__( 'No', 'squad-modules-for-divi' ), 'on' => esc_html__( 'Yes', 'squad-modules-for-divi' ) ),
				'default'     => 'on',
				'tab_slug'    => 'general',
				'toggle_slug' => 'options_element',
			),
			'privacy_notice'  => array(
				'label'       => esc_html__( 'Privacy Notice', 'squad-modules-for-divi' ),
				'type'        => 'textarea',
				'default'     => '',
				'tab_slug'    => 'general',
				'toggle_slug' => 'options_element',
			),
			'button_text'     => array(
				'label'       => esc_html__( 'Button Text', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => esc_html__( 'Register', 'squad-modules-for-divi' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'button_element',
			),
		);
	}

	/**
	 * Render the registration form.
	 *
	 * @param array<string, mixed> $attrs       Module attributes.
	 * @param string               $content     Inner content (unused).
	 * @param string               $render_slug Module slug.
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

			if ( ! (bool) get_option( 'users_can_register' ) ) {
				return '<div class="disq-register-form disq-register-form--disabled"><p>' .
				       esc_html__( 'Registration is currently disabled.', 'squad-modules-for-divi' ) .
				       '</p></div>';
			}

			$layout         = sanitize_text_field( $this->props['layout'] ?? 'card' );
			$show_logo      = 'on' === ( $this->props['show_logo'] ?? 'on' );
			$title_text     = esc_html( $this->props['title_text'] ?? __( 'Create an account', 'squad-modules-for-divi' ) );
			$username_label = esc_html( $this->props['username_label'] ?? __( 'Username', 'squad-modules-for-divi' ) );
			$email_label    = esc_html( $this->props['email_label'] ?? __( 'Email Address', 'squad-modules-for-divi' ) );
			$show_login     = 'on' === ( $this->props['show_login_link'] ?? 'on' );
			$privacy_notice = wp_kses_post( $this->props['privacy_notice'] ?? '' );
			$button_text    = esc_html( $this->props['button_text'] ?? __( 'Register', 'squad-modules-for-divi' ) );

			// phpcs:ignore WordPress.Security.NonceVerification
			$error_code = sanitize_key( $_GET['registration'] ?? '' );
			$error_msg  = '';
			if ( '' !== $error_code ) {
				$error_msg = esc_html( self::ERROR_MAP[ $error_code ] ?? __( 'Registration failed. Please try again.', 'squad-modules-for-divi' ) );
			}

			// phpcs:ignore WordPress.Security.NonceVerification
			$success = isset( $_GET['registered'] ) && 'true' === sanitize_text_field( wp_unslash( $_GET['registered'] ) );

			$action_url = esc_url( site_url( 'wp-login.php?action=register', 'login_post' ) );

			ob_start();
			?>
			<div class="disq-register-form disq-register-form--<?php echo esc_attr( $layout ); ?>">
				<?php if ( 'split' === $layout ) : ?>
					<div class="disq-register-form__brand"></div><?php endif; ?>
				<div class="disq-register-form__panel">
					<?php if ( $show_logo ) : ?>
						<div class="disq-register-form__logo"><?php echo get_custom_logo(); // phpcs:ignore WordPress.Security.EscapeOutput ?></div><?php endif; ?>
					<h2 class="disq-register-form__title"><?php echo $title_text; ?></h2>
					<?php if ( $success ) : ?>
						<div class="disq-register-form__success" role="status">
							<?php esc_html_e( 'Registration successful. Please check your email.', 'squad-modules-for-divi' ); ?>
						</div>
					<?php elseif ( '' !== $error_msg ) : ?>
						<div class="disq-register-form__error" role="alert"><?php echo $error_msg; ?></div>
					<?php endif; ?>
					<form class="disq-register-form__form" action="<?php echo $action_url; ?>" method="post">
						<div class="disq-register-form__field">
							<label class="disq-register-form__label" for="disq-reg-login"><?php echo $username_label; ?></label>
							<input id="disq-reg-login" class="disq-register-form__input" type="text" name="user_login" autocomplete="username" required/>
						</div>
						<div class="disq-register-form__field">
							<label class="disq-register-form__label" for="disq-reg-email"><?php echo $email_label; ?></label>
							<input id="disq-reg-email" class="disq-register-form__input" type="email" name="user_email" autocomplete="email" required/>
						</div>
						<?php if ( '' !== $privacy_notice ) : ?>
							<div class="disq-register-form__privacy"><?php echo $privacy_notice; ?></div>
						<?php endif; ?>
						<input type="hidden" name="redirect_to" value="<?php echo esc_attr( add_query_arg( 'registered', 'true', wp_login_url() ) ); ?>"/>
						<button type="submit" class="disq-register-form__submit"><?php echo $button_text; ?></button>
					</form>
					<?php if ( $show_login ) : ?>
						<nav class="disq-register-form__nav">
							<a href="<?php echo esc_url( wp_login_url() ); ?>"><?php esc_html_e( 'Already have an account? Log in', 'squad-modules-for-divi' ); ?></a>
						</nav>
					<?php endif; ?>
				</div>
			</div>
			<?php
			return (string) ob_get_clean();
		} catch ( Throwable $e ) {
			ob_get_clean();
			divi_squad()->log_error( $e, 'Register Form D4 render failed' );

			return '';
		}
	}
}
