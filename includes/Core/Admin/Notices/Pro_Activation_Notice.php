<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Pro Activation Notice Class
 *
 * Handles the display of the pro plugin activation notice.
 *
 * @since   3.3.3
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Core\Admin\Notices;

use Throwable;
use function esc_html__;
use function wp_nonce_url;

/**
 * Pro Activation Notice Class
 *
 * @since   3.3.3
 * @package DiviSquad
 */
class Pro_Activation_Notice extends Notice_Base {

	/**
	 * The notice ID.
	 *
	 * @var string
	 */
	protected string $notice_id = 'pro-activation';

	/**
	 * Check if the notice can be rendered.
	 *
	 * @since 3.3.3
	 *
	 * @return bool Whether the notice can be rendered.
	 */
	public function can_render_it(): bool {
		static $can_render = null;

		try {
			if ( null === $can_render ) {
				$can_use_premium_code = divi_squad_fs()->can_use_premium_code();
				$is_pro_notice_closed = (bool) divi_squad()->memory->get( 'pro_activation_notice_close', false );

				if ( ! $is_pro_notice_closed && $can_use_premium_code && ! divi_squad()->is_pro_activated() ) {
					$can_render = divi_squad()->is_pro_installed();
				}

				$can_render = $can_render ?? false;
			}
		} catch ( Throwable $e ) {
			$can_render = false;
		}

		return (bool) $can_render;
	}

	/**
	 * Get the notice template arguments.
	 *
	 * @since 3.3.3
	 *
	 * @return array<string, mixed> The notice template arguments.
	 */
	public function get_template_args(): array {
		return array(
			'wrapper_classes' => 'divi-squad-success-banner pro-activation-notice',
			'logo'            => 'logos/divi-squad-d-default.svg',
			'title'           => esc_html__( 'Approaching closer to unlocking the benefits of the Pro plugin.', 'squad-modules-for-divi' ),
			'content'         => sprintf(
			/* translators: %1$s: Product title; */
				esc_html__( ' The paid plugin of %1$s is already installed. Please activate it to start benefiting the Pro features.', 'squad-modules-for-divi' ),
				sprintf( '<em>%s</em>', esc_html__( 'Squad Modules Lite', 'squad-modules-for-divi' ) )
			),
			'action-buttons'  => array(
				'left' => array(
					array(
						'link'    => wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . divi_squad()->get_pro_basename(), 'activate-plugin_' . divi_squad()->get_pro_basename() ),
						'text'    => esc_html__( 'Active Pro Plugin', 'squad-modules-for-divi' ),
						'classes' => 'button-primary divi-squad-notice-action-button',
						'icon'    => 'dashicons-external',
						'style'   => '',
					),
				),
			),
			'is_dismissible'  => true,
		);
	}
}
