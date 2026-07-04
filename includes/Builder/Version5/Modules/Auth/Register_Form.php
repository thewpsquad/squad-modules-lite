<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Register Form Module (Divi 5 / Block API).
 *
 * Renders the same `.disq-register-form` markup as the Divi 4 module.
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

use DiviSquad\Builder\Version5\Abstracts\Module;
use ET\Builder\FrontEnd\Module\Style;
use ET\Builder\Packages\Module\Layout\Components\ModuleElements\ModuleElements;
use ET\Builder\Packages\Module\Module as DiviModule;
use ET\Builder\Packages\Module\Options\Css\CssStyle;
use ET\Builder\Packages\Module\Options\Element\ElementClassnames;
use Throwable;
use WP_Block;
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
 * Register Form module class (Divi 5).
 *
 * @since 4.2.0
 */
class Register_Form extends Module {

	/**
	 * Error code → display message map.
	 *
	 * @var array<string, string>
	 */
	private const ERROR_MAP = array(
		'username_exists'  => 'That username is already taken.',
		'email_exists'     => 'An account with that email already exists.',
		'invalid_username' => 'Invalid username.',
		'invalid_email'    => 'Please enter a valid email address.',
	);

	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/register-form/';
	}

	/**
	 * Add module-specific classnames.
	 *
	 * @param array<string, mixed> $args Module classnames arguments.
	 *
	 * @return void
	 */
	public static function module_classnames( array $args ): void {
		$args['classnamesInstance']->add( 'disq_register_form' );
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
	 * Render callback for the Register Form module (Divi 5).
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
			$inner = $attrs['content']['innerContent']['desktop']['value'] ?? array();

			if ( ! (bool) get_option( 'users_can_register' ) ) {
				return '<div class="disq-register-form disq-register-form--disabled"><p>' .
				       esc_html__( 'Registration is currently disabled.', 'squad-modules-for-divi' ) .
				       '</p></div>';
			}

			$layout         = sanitize_text_field( (string) ( $inner['layout'] ?? 'card' ) );
			$show_logo      = 'on' === (string) ( $inner['showLogo'] ?? 'on' );
			$show_title     = 'on' === (string) ( $inner['showTitle'] ?? 'on' );
			$title_text     = esc_html( (string) ( $inner['titleText'] ?? 'Create an account' ) );
			$username_label = esc_html( (string) ( $inner['usernameLabel'] ?? 'Username' ) );
			$email_label    = esc_html( (string) ( $inner['emailLabel'] ?? 'Email Address' ) );
			$show_login     = 'on' === (string) ( $inner['showLoginLink'] ?? 'on' );
			$privacy_notice = wp_kses_post( (string) ( $inner['privacyNotice'] ?? '' ) );
			$button_text    = esc_html( (string) ( $inner['buttonText'] ?? 'Register' ) );

			// phpcs:ignore WordPress.Security.NonceVerification
			$error_code = sanitize_key( $_GET['registration'] ?? '' );
			$error_msg  = '';
			if ( '' !== $error_code ) {
				$raw_msg   = self::ERROR_MAP[ $error_code ] ?? 'Registration failed. Please try again.';
				$error_msg = esc_html( $raw_msg );
			}

			// phpcs:ignore WordPress.Security.NonceVerification
			$success = isset( $_GET['registered'] ) && 'true' === sanitize_text_field( (string) ( $_GET['registered'] ?? '' ) );

			$action_url = esc_url( site_url( 'wp-login.php?action=register', 'login_post' ) );

			ob_start();
			?>
			<div class="disq-register-form disq-register-form--<?php echo esc_attr( $layout ); ?>">
				<?php if ( 'split' === $layout ) : ?>
					<div class="disq-register-form__brand"></div><?php endif; ?>
				<div class="disq-register-form__panel">
					<?php if ( $show_logo ) : ?>
						<div class="disq-register-form__logo"><?php echo get_custom_logo(); // phpcs:ignore WordPress.Security.EscapeOutput ?></div><?php endif; ?>
					<?php if ( $show_title ) : ?>
						<h2 class="disq-register-form__title"><?php echo $title_text; ?></h2>
					<?php endif; ?>
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
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Register Form module' );

			return '';
		}
	}
}
