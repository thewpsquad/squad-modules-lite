<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Lost Password Form Module (Divi 5 / Block API).
 *
 * Renders the same `.disq-lostpw-form` markup as the Divi 4 module.
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
 * Lost Password Form module class (Divi 5).
 *
 * @since 4.2.0
 */
class Lost_Password_Form extends Module {

	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/lost-password-form/';
	}

	/**
	 * Add module-specific classnames.
	 *
	 * @param array<string, mixed> $args Module classnames arguments.
	 *
	 * @return void
	 */
	public static function module_classnames( array $args ): void {
		$args['classnamesInstance']->add( 'disq_lost_password_form' );
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
	 * Render callback for the Lost Password Form module (Divi 5).
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
			$title_text      = esc_html( (string) ( $inner['titleText'] ?? 'Reset your password' ) );
			$show_subtitle   = 'on' === (string) ( $inner['showSubtitle'] ?? 'on' );
			$subtitle_text   = esc_html( (string) ( $inner['subtitleText'] ?? '' ) );
			$username_label  = esc_html( (string) ( $inner['usernameLabel'] ?? 'Username or Email' ) );
			$button_text     = esc_html( (string) ( $inner['buttonText'] ?? 'Send Reset Link' ) );
			$show_login      = 'on' === (string) ( $inner['showLoginLink'] ?? 'on' );
			$success_message = esc_html( (string) ( $inner['successMessage'] ?? 'Check your email for a reset link.' ) );

			// phpcs:ignore WordPress.Security.NonceVerification
			$success = isset( $_GET['checkemail'] ) && 'confirm' === sanitize_key( (string) ( $_GET['checkemail'] ?? '' ) );

			// phpcs:ignore WordPress.Security.NonceVerification
			$error_value = sanitize_key( (string) ( $_GET['error'] ?? '' ) );
			$error_map   = array(
				'invalidkey' => esc_html__( 'This reset link is invalid or has expired.', 'squad-modules-for-divi' ),
				'expiredkey' => esc_html__( 'This reset link has expired. Request a new one.', 'squad-modules-for-divi' ),
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
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Lost Password Form module' );

			return '';
		}
	}
}
