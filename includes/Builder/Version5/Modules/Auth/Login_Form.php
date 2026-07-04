<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Login Form Module (Divi 5 / Block API).
 *
 * Renders the same `.disq-login-form` markup as the Divi 4 module.
 *
 * @since   4.2.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version5\Modules\Auth;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

if ( ! class_exists( 'ET\Builder\Packages\Module\Module' ) ) {
	return;
}

use DiviSquad\Builder\Utils\Auth_Form_Helper;
use DiviSquad\Builder\Version5\Abstracts\Module;
use ET\Builder\FrontEnd\Module\Style;
use ET\Builder\Packages\Module\Layout\Components\ModuleElements\ModuleElements;
use ET\Builder\Packages\Module\Module as DiviModule;
use ET\Builder\Packages\Module\Options\Css\CssStyle;
use ET\Builder\Packages\Module\Options\Element\ElementClassnames;
use Throwable;
use WP_Block;
use function admin_url;
use function esc_attr;
use function esc_html;
use function esc_url;
use function get_custom_logo;
use function get_option;
use function sanitize_key;
use function sanitize_text_field;
use function site_url;
use function wp_lostpassword_url;
use function wp_registration_url;

/**
 * Login Form module class (Divi 5).
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

	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/login-form/';
	}

	/**
	 * Add module-specific classnames.
	 *
	 * @param array<string, mixed> $args Module classnames arguments.
	 *
	 * @return void
	 */
	public static function module_classnames( array $args ): void {
		$args['classnamesInstance']->add( 'disq_login_form' );
		$args['classnamesInstance']->add(
			ElementClassnames::classnames(
				array( 'attrs' => $args['attrs']['module']['decoration'] ?? array() )
			)
		);
	}

	/**
	 * Add module script data.
	 *
	 * @param array<string, mixed> $args Module script data arguments.
	 *
	 * @return void
	 */
	public static function module_script_data( array $args ): void {
		$args['elements']->script_data( array( 'attrName' => 'module' ) );
	}

	/**
	 * Add module styles.
	 *
	 * @param array<string, mixed> $args Module styles arguments.
	 *
	 * @return void
	 */
	public static function module_styles( array $args ): void {
		$attrs    = $args['attrs'] ?? array();
		$elements = $args['elements'];
		$settings = $args['settings'] ?? array();

		Style::add(
			array(
				'id'            => $args['id'],
				'name'          => $args['name'],
				'orderIndex'    => $args['orderIndex'],
				'storeInstance' => $args['storeInstance'],
				'styles'        => array(
					$elements->style(
						array(
							'attrName'   => 'module',
							'styleProps' => array(
								'disabledOn' => array(
									'disabledModuleVisibility' => $settings['disabledModuleVisibility'] ?? null,
								),
							),
						)
					),
					CssStyle::style(
						array(
							'selector' => $args['orderClass'],
							'attr'     => $attrs['css'] ?? array(),
						)
					),
				),
			)
		);
	}

	/**
	 * Render callback for the Login Form module (Divi 5).
	 *
	 * @since 4.2.0
	 *
	 * @param array<string, mixed> $attrs    Block attributes.
	 * @param string               $content  Inner block content.
	 * @param WP_Block             $block    Parsed block instance.
	 * @param object               $elements ModuleElements instance.
	 *
	 * @return string Rendered HTML.
	 */
	public static function render_callback( array $attrs, string $content, WP_Block $block, $elements ): string {
		try {
			// Already logged in (and not styling in the Visual Builder) — show a notice instead of the form.
			$logged_in_notice = Auth_Form_Helper::logged_in_notice();
			if ( '' !== $logged_in_notice ) {
				return $logged_in_notice;
			}

			$inner = $attrs['content']['innerContent']['desktop']['value'] ?? array();

			$layout          = sanitize_text_field( (string) ( $inner['layout'] ?? 'card' ) );
			$show_logo       = 'on' === (string) ( $inner['showLogo'] ?? 'on' );
			$show_title      = 'on' === (string) ( $inner['showTitle'] ?? 'on' );
			$title_text      = esc_html( (string) ( $inner['titleText'] ?? 'Welcome back' ) );
			$show_subtitle   = 'on' === (string) ( $inner['showSubtitle'] ?? 'off' );
			$subtitle_text   = esc_html( (string) ( $inner['subtitleText'] ?? '' ) );
			$username_label  = esc_html( (string) ( $inner['usernameLabel'] ?? 'Username or Email' ) );
			$password_label  = esc_html( (string) ( $inner['passwordLabel'] ?? 'Password' ) );
			$username_ph     = esc_attr( (string) ( $inner['usernamePlaceholder'] ?? '' ) );
			$password_ph     = esc_attr( (string) ( $inner['passwordPlaceholder'] ?? '' ) );
			$show_remember   = 'on' === (string) ( $inner['showRememberMe'] ?? 'on' );
			$remember_label  = esc_html( (string) ( $inner['rememberMeLabel'] ?? 'Remember me' ) );
			$show_forgot     = 'on' === (string) ( $inner['showForgotLink'] ?? 'on' );
			$forgot_text     = esc_html( (string) ( $inner['forgotLinkText'] ?? 'Forgot password?' ) );
			$show_register   = 'on' === (string) ( $inner['showRegisterLink'] ?? 'off' );
			$register_text   = esc_html( (string) ( $inner['registerLinkText'] ?? 'Create an account' ) );
			$button_text     = esc_html( (string) ( $inner['buttonText'] ?? 'Log In' ) );
			$after_login_url = esc_url( (string) ( $inner['afterLoginUrl'] ?? admin_url() ) );

			// phpcs:ignore WordPress.Security.NonceVerification
			$error_key = sanitize_key( $_GET['login'] ?? '' );
			$error_msg = '';
			if ( '' !== $error_key ) {
				$raw_msg   = self::ERROR_MAP[ $error_key ] ?? 'Login failed. Please try again.';
				$error_msg = esc_html( $raw_msg );
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
			$html = (string) ob_get_clean();

			$style_components = $elements instanceof ModuleElements
				? (string) $elements->style_components( array( 'attrName' => 'module' ) )
				: '';

			return DiviModule::render(
				array(
					'orderIndex'          => $block->parsed_block['orderIndex'],
					'storeInstance'       => $block->parsed_block['storeInstance'],
					'attrs'               => $attrs,
					'elements'            => $elements,
					'id'                  => $block->parsed_block['id'],
					'name'                => $block->block_type->name,
					'moduleCategory'      => $block->block_type->category,
					'classnamesFunction'  => array( static::class, 'module_classnames' ),
					'stylesComponent'     => array( static::class, 'module_styles' ),
					'scriptDataComponent' => array( static::class, 'module_script_data' ),
					'children'            => $style_components . $html,
				)
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Login Form module' );

			return '';
		}
	}
}
