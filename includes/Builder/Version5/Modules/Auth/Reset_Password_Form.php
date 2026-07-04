<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Reset Password Form Module (Divi 5 / Block API).
 *
 * Renders the same `.disq-resetpw-form` markup as the Divi 4 module.
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
 * Reset Password Form module class (Divi 5).
 *
 * @since 4.2.0
 */
class Reset_Password_Form extends Module {

	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/reset-password-form/';
	}

	/**
	 * Add module-specific classnames.
	 *
	 * @param array<string, mixed> $args Module classnames arguments.
	 *
	 * @return void
	 */
	public static function module_classnames( array $args ): void {
		$args['classnamesInstance']->add( 'disq_reset_password_form' );
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
	 * Render callback for the Reset Password Form module (Divi 5).
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

			$layout                 = sanitize_text_field( (string) ( $inner['layout'] ?? 'card' ) );
			$show_logo              = 'on' === (string) ( $inner['showLogo'] ?? 'on' );
			$title_text             = esc_html( (string) ( $inner['titleText'] ?? 'Set new password' ) );
			$new_password_label     = esc_html( (string) ( $inner['newPasswordLabel'] ?? 'New Password' ) );
			$confirm_password_label = esc_html( (string) ( $inner['confirmPasswordLabel'] ?? 'Confirm Password' ) );
			$show_strength          = 'on' === (string) ( $inner['showStrengthMeter'] ?? 'on' );
			$button_text            = esc_html( (string) ( $inner['buttonText'] ?? 'Save Password' ) );

			// phpcs:disable WordPress.Security.NonceVerification
			$rp_key   = sanitize_text_field( (string) ( $_GET['key'] ?? '' ) );
			$rp_login = sanitize_text_field( (string) ( $_GET['login'] ?? '' ) );
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
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Reset Password Form module' );

			return '';
		}
	}
}
