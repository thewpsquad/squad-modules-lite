<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Login Form Module — Divi 4.
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
use function esc_html__;
use function esc_url;
use function get_custom_logo;
use function get_option;
use function sanitize_key;
use function sanitize_text_field;
use function site_url;
use function wp_lostpassword_url;
use function wp_registration_url;

/**
 * Login Form D4 module.
 *
 * @since 4.2.0
 */
class Login_Form extends Module {

	/**
	 * Error code → display message map.
	 *
	 * @var array<string, string>
	 */
	private const ERROR_MAP = array(
		'failed'       => 'Incorrect username or password.',
		'empty'        => 'Please enter your username or password.',
		'invalidcombo' => 'Incorrect username or password.',
	);

	/**
	 * Module init — name, slug, toggles, advanced fields.
	 */
	public function init(): void {
		$this->name      = esc_html__( 'Login Form', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Login Forms', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'login-form.svg' );

		$this->slug             = 'disq_login_form';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.$this->slug";

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'layout'           => esc_html__( 'Layout', 'squad-modules-for-divi' ),
					'header_element'   => esc_html__( 'Header', 'squad-modules-for-divi' ),
					'fields_element'   => esc_html__( 'Fields', 'squad-modules-for-divi' ),
					'options_element'  => esc_html__( 'Options', 'squad-modules-for-divi' ),
					'button_element'   => esc_html__( 'Button', 'squad-modules-for-divi' ),
					'redirect_element' => esc_html__( 'Redirect', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'form_wrapper'  => esc_html__( 'Form Wrapper', 'squad-modules-for-divi' ),
					'title_text'    => esc_html__( 'Title', 'squad-modules-for-divi' ),
					'subtitle_text' => esc_html__( 'Subtitle', 'squad-modules-for-divi' ),
					'label_text'    => esc_html__( 'Labels', 'squad-modules-for-divi' ),
					'input_element' => esc_html__( 'Input Fields', 'squad-modules-for-divi' ),
					'button_text'   => esc_html__( 'Button', 'squad-modules-for-divi' ),
					'link_text'     => esc_html__( 'Links', 'squad-modules-for-divi' ),
					'error_element' => esc_html__( 'Error', 'squad-modules-for-divi' ),
				),
			),
		);

		$this->advanced_fields = array(
			'fonts'          => array(
				'title_text'    => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Title', 'squad-modules-for-divi' ),
					array(
						'css'         => array(
							'main'  => "$this->main_css_element .disq-login-form__title",
							'hover' => "$this->main_css_element .disq-login-form__title:hover",
						),
						'tab_slug'    => 'advanced',
						'toggle_slug' => 'title_text',
					)
				),
				'subtitle_text' => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Subtitle', 'squad-modules-for-divi' ),
					array(
						'css'         => array(
							'main'  => "$this->main_css_element .disq-login-form__subtitle",
							'hover' => "$this->main_css_element .disq-login-form__subtitle:hover",
						),
						'tab_slug'    => 'advanced',
						'toggle_slug' => 'subtitle_text',
					)
				),
				'label_text'    => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Labels', 'squad-modules-for-divi' ),
					array(
						'css'         => array(
							'main'  => "$this->main_css_element .disq-login-form__label",
							'hover' => "$this->main_css_element .disq-login-form__label:hover",
						),
						'tab_slug'    => 'advanced',
						'toggle_slug' => 'label_text',
					)
				),
				'button_text'   => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Button', 'squad-modules-for-divi' ),
					array(
						'css'         => array(
							'main'  => "$this->main_css_element .disq-login-form__submit",
							'hover' => "$this->main_css_element .disq-login-form__submit:hover",
						),
						'tab_slug'    => 'advanced',
						'toggle_slug' => 'button_text',
					)
				),
				'link_text'     => divi_squad()->d4_module_helper->add_font_field(
					esc_html__( 'Links', 'squad-modules-for-divi' ),
					array(
						'css'         => array(
							'main'  => "$this->main_css_element .disq-login-form__nav a",
							'hover' => "$this->main_css_element .disq-login-form__nav a:hover",
						),
						'tab_slug'    => 'advanced',
						'toggle_slug' => 'link_text',
					)
				),
			),
			'background'     => divi_squad()->d4_module_helper->selectors_background( $this->main_css_element ),
			'borders'        => array(
				'default'     => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
				'input_field' => array(
					'label_prefix' => esc_html__( 'Input', 'squad-modules-for-divi' ),
					'css'          => array(
						'main' => array(
							'border_radii'  => "$this->main_css_element .disq-login-form__input",
							'border_styles' => "$this->main_css_element .disq-login-form__input",
						),
					),
					'tab_slug'     => 'advanced',
					'toggle_slug'  => 'input_element',
				),
				'button'      => array(
					'label_prefix' => esc_html__( 'Button', 'squad-modules-for-divi' ),
					'css'          => array(
						'main' => array(
							'border_radii'  => "$this->main_css_element .disq-login-form__submit",
							'border_styles' => "$this->main_css_element .disq-login-form__submit",
						),
					),
					'tab_slug'     => 'advanced',
					'toggle_slug'  => 'button_text',
				),
			),
			'box_shadow'     => array(
				'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			),
			'margin_padding' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			'button'         => false,
			'link_options'   => false,
			'text'           => false,
		);
	}

	/**
	 * Content tab field definitions.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_fields(): array {
		return array(
			// ── Layout ──────────────────────────────────────────────
			'layout'               => array(
				'label'       => esc_html__( 'Layout', 'squad-modules-for-divi' ),
				'type'        => 'select',
				'options'     => array(
					'card'   => esc_html__( 'Card', 'squad-modules-for-divi' ),
					'split'  => esc_html__( 'Split', 'squad-modules-for-divi' ),
					'inline' => esc_html__( 'Inline', 'squad-modules-for-divi' ),
				),
				'default'     => 'card',
				'tab_slug'    => 'general',
				'toggle_slug' => 'layout',
			),
			// ── Header ──────────────────────────────────────────────
			'show_logo'            => array(
				'label'       => esc_html__( 'Show Logo', 'squad-modules-for-divi' ),
				'type'        => 'yes_no_button',
				'options'     => array( 'off' => esc_html__( 'No', 'squad-modules-for-divi' ), 'on' => esc_html__( 'Yes', 'squad-modules-for-divi' ) ),
				'default'     => 'on',
				'tab_slug'    => 'general',
				'toggle_slug' => 'header_element',
			),
			'show_title'           => array(
				'label'       => esc_html__( 'Show Title', 'squad-modules-for-divi' ),
				'type'        => 'yes_no_button',
				'options'     => array( 'off' => esc_html__( 'No', 'squad-modules-for-divi' ), 'on' => esc_html__( 'Yes', 'squad-modules-for-divi' ) ),
				'default'     => 'on',
				'tab_slug'    => 'general',
				'toggle_slug' => 'header_element',
			),
			'title_text'           => array(
				'label'       => esc_html__( 'Title', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => esc_html__( 'Welcome back', 'squad-modules-for-divi' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'header_element',
				'show_if'     => array( 'show_title' => 'on' ),
			),
			'show_subtitle'        => array(
				'label'       => esc_html__( 'Show Subtitle', 'squad-modules-for-divi' ),
				'type'        => 'yes_no_button',
				'options'     => array( 'off' => esc_html__( 'No', 'squad-modules-for-divi' ), 'on' => esc_html__( 'Yes', 'squad-modules-for-divi' ) ),
				'default'     => 'off',
				'tab_slug'    => 'general',
				'toggle_slug' => 'header_element',
			),
			'subtitle_text'        => array(
				'label'       => esc_html__( 'Subtitle', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'tab_slug'    => 'general',
				'toggle_slug' => 'header_element',
				'show_if'     => array( 'show_subtitle' => 'on' ),
			),
			// ── Fields ──────────────────────────────────────────────
			'username_label'       => array(
				'label'       => esc_html__( 'Username Label', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => esc_html__( 'Username or Email', 'squad-modules-for-divi' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'fields_element',
			),
			'password_label'       => array(
				'label'       => esc_html__( 'Password Label', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => esc_html__( 'Password', 'squad-modules-for-divi' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'fields_element',
			),
			'username_placeholder' => array(
				'label'       => esc_html__( 'Username Placeholder', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'tab_slug'    => 'general',
				'toggle_slug' => 'fields_element',
			),
			'password_placeholder' => array(
				'label'       => esc_html__( 'Password Placeholder', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'tab_slug'    => 'general',
				'toggle_slug' => 'fields_element',
			),
			// ── Options ─────────────────────────────────────────────
			'show_remember_me'     => array(
				'label'       => esc_html__( 'Show Remember Me', 'squad-modules-for-divi' ),
				'type'        => 'yes_no_button',
				'options'     => array( 'off' => esc_html__( 'No', 'squad-modules-for-divi' ), 'on' => esc_html__( 'Yes', 'squad-modules-for-divi' ) ),
				'default'     => 'on',
				'tab_slug'    => 'general',
				'toggle_slug' => 'options_element',
			),
			'remember_me_label'    => array(
				'label'       => esc_html__( 'Remember Me Label', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => esc_html__( 'Remember me', 'squad-modules-for-divi' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'options_element',
				'show_if'     => array( 'show_remember_me' => 'on' ),
			),
			'show_forgot_link'     => array(
				'label'       => esc_html__( 'Show Forgot Password Link', 'squad-modules-for-divi' ),
				'type'        => 'yes_no_button',
				'options'     => array( 'off' => esc_html__( 'No', 'squad-modules-for-divi' ), 'on' => esc_html__( 'Yes', 'squad-modules-for-divi' ) ),
				'default'     => 'on',
				'tab_slug'    => 'general',
				'toggle_slug' => 'options_element',
			),
			'forgot_link_text'     => array(
				'label'       => esc_html__( 'Forgot Link Text', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => esc_html__( 'Forgot password?', 'squad-modules-for-divi' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'options_element',
				'show_if'     => array( 'show_forgot_link' => 'on' ),
			),
			'show_register_link'   => array(
				'label'       => esc_html__( 'Show Register Link', 'squad-modules-for-divi' ),
				'type'        => 'yes_no_button',
				'options'     => array( 'off' => esc_html__( 'No', 'squad-modules-for-divi' ), 'on' => esc_html__( 'Yes', 'squad-modules-for-divi' ) ),
				'default'     => 'off',
				'tab_slug'    => 'general',
				'toggle_slug' => 'options_element',
			),
			'register_link_text'   => array(
				'label'       => esc_html__( 'Register Link Text', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => esc_html__( 'Create an account', 'squad-modules-for-divi' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'options_element',
				'show_if'     => array( 'show_register_link' => 'on' ),
			),
			// ── Button ──────────────────────────────────────────────
			'button_text'          => array(
				'label'       => esc_html__( 'Button Text', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => esc_html__( 'Log In', 'squad-modules-for-divi' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'button_element',
			),
			// ── Redirect ────────────────────────────────────────────
			'after_login_url'      => array(
				'label'       => esc_html__( 'After Login URL', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'description' => esc_html__( 'Leave empty to use the default (Dashboard).', 'squad-modules-for-divi' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'redirect_element',
			),
		);
	}

	/**
	 * Render the login form.
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

			$layout          = sanitize_text_field( $this->props['layout'] ?? 'card' );
			$show_logo       = 'on' === ( $this->props['show_logo'] ?? 'on' );
			$show_title      = 'on' === ( $this->props['show_title'] ?? 'on' );
			$title_text      = esc_html( $this->props['title_text'] ?? __( 'Welcome back', 'squad-modules-for-divi' ) );
			$show_subtitle   = 'on' === ( $this->props['show_subtitle'] ?? 'off' );
			$subtitle_text   = esc_html( $this->props['subtitle_text'] ?? '' );
			$username_label  = esc_html( $this->props['username_label'] ?? __( 'Username or Email', 'squad-modules-for-divi' ) );
			$password_label  = esc_html( $this->props['password_label'] ?? __( 'Password', 'squad-modules-for-divi' ) );
			$username_ph     = esc_attr( $this->props['username_placeholder'] ?? '' );
			$password_ph     = esc_attr( $this->props['password_placeholder'] ?? '' );
			$show_remember   = 'on' === ( $this->props['show_remember_me'] ?? 'on' );
			$remember_label  = esc_html( $this->props['remember_me_label'] ?? __( 'Remember me', 'squad-modules-for-divi' ) );
			$show_forgot     = 'on' === ( $this->props['show_forgot_link'] ?? 'on' );
			$forgot_text     = esc_html( $this->props['forgot_link_text'] ?? __( 'Forgot password?', 'squad-modules-for-divi' ) );
			$show_register   = 'on' === ( $this->props['show_register_link'] ?? 'off' );
			$register_text   = esc_html( $this->props['register_link_text'] ?? __( 'Create an account', 'squad-modules-for-divi' ) );
			$button_text     = esc_html( $this->props['button_text'] ?? __( 'Log In', 'squad-modules-for-divi' ) );
			$after_login_url = esc_url( $this->props['after_login_url'] ?? admin_url() );

			// phpcs:ignore WordPress.Security.NonceVerification
			$error_key = sanitize_key( $_GET['login'] ?? '' );
			$error_msg = '';
			if ( '' !== $error_key ) {
				$error_msg = self::ERROR_MAP[ $error_key ] ?? __( 'Login failed. Please try again.', 'squad-modules-for-divi' );
				$error_msg = esc_html( $error_msg );
			}

			$action_url = esc_url( site_url( 'wp-login.php', 'login_post' ) );

			ob_start();
			?>
			<div class="disq-login-form disq-login-form--<?php echo esc_attr( $layout ); ?>">

				<?php if ( 'split' === $layout ) : ?>
					<div class="disq-login-form__brand"></div>
				<?php endif; ?>

				<div class="disq-login-form__panel">

					<?php if ( $show_logo ) : ?>
						<div class="disq-login-form__logo">
							<?php echo get_custom_logo(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</div>
					<?php endif; ?>

					<?php if ( $show_title ) : ?>
						<h2 class="disq-login-form__title"><?php echo $title_text; ?></h2>
					<?php endif; ?>

					<?php if ( $show_subtitle && '' !== $subtitle_text ) : ?>
						<p class="disq-login-form__subtitle"><?php echo $subtitle_text; ?></p>
					<?php endif; ?>

					<?php if ( '' !== $error_msg ) : ?>
						<div class="disq-login-form__error" role="alert"><?php echo $error_msg; ?></div>
					<?php endif; ?>

					<form class="disq-login-form__form" action="<?php echo $action_url; ?>" method="post">

						<div class="disq-login-form__field">
							<label class="disq-login-form__label" for="disq-user-login"><?php echo $username_label; ?></label>
							<input
								id="disq-user-login"
								class="disq-login-form__input"
								type="text"
								name="log"
								autocomplete="username"
								placeholder="<?php echo $username_ph; ?>"
								required
							/>
						</div>

						<div class="disq-login-form__field">
							<label class="disq-login-form__label" for="disq-user-pass"><?php echo $password_label; ?></label>
							<input
								id="disq-user-pass"
								class="disq-login-form__input"
								type="password"
								name="pwd"
								autocomplete="current-password"
								placeholder="<?php echo $password_ph; ?>"
								required
							/>
						</div>

						<?php if ( $show_remember ) : ?>
							<div class="disq-login-form__remember">
								<label>
									<input type="checkbox" name="rememberme" value="forever"/>
									<?php echo $remember_label; ?>
								</label>
							</div>
						<?php endif; ?>

						<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $after_login_url ); ?>"/>

						<button type="submit" class="disq-login-form__submit"><?php echo $button_text; ?></button>

					</form>

					<?php if ( $show_forgot || $show_register ) : ?>
						<nav class="disq-login-form__nav">
							<?php if ( $show_forgot ) : ?>
								<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php echo $forgot_text; ?></a>
							<?php endif; ?>
							<?php if ( $show_register && (bool) get_option( 'users_can_register' ) ) : ?>
								<a href="<?php echo esc_url( wp_registration_url() ); ?>"><?php echo $register_text; ?></a>
							<?php endif; ?>
						</nav>
					<?php endif; ?>

				</div><!-- .disq-login-form__panel -->

			</div><!-- .disq-login-form -->
			<?php
			return (string) ob_get_clean();
		} catch ( Throwable $e ) {
			ob_get_clean();
			divi_squad()->log_error( $e, 'Login Form D4 render failed' );

			return '';
		}
	}
}
