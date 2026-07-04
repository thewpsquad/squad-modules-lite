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
use function sprintf;
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
		try {
			// Check if user can use premium code
			$can_use_premium_code = divi_squad_fs()->can_use_premium_code();

			// Check if pro activation notice is already closed
			$is_pro_notice_closed = (bool) divi_squad()->memory->get( 'pro_activation_notice_close', false );

			// Check if pro is installed but not activated
			$is_pro_installed_but_not_active = false;
			if ( ! $is_pro_notice_closed && $can_use_premium_code && ! divi_squad()->is_pro_activated() ) {
				$is_pro_installed_but_not_active = divi_squad()->is_pro_installed();
			}

			// Render if pro is installed but not activated and notice is not closed
			$can_render = $is_pro_installed_but_not_active;

			/**
			 * Filter whether the pro activation notice can be rendered.
			 *
			 * @since 3.3.3
			 *
			 * @param bool                  $can_render Whether the notice can be rendered.
			 * @param Pro_Activation_Notice $notice     The notice instance.
			 */
			return apply_filters( 'divi_squad_can_render_pro_activation_notice', $can_render, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Error checking if pro activation notice can be rendered' );

			return false;
		}
	}

	/**
	 * Get the notice template arguments.
	 *
	 * @since 3.3.3
	 *
	 * @return array<string, mixed> The notice template arguments.
	 */
	public function get_template_args(): array {
		try {
			// Get default arguments from parent class
			$args = $this->get_default_template_args();

			// Override with pro activation-specific values
			$activation_args = array(
				'wrapper_classes' => 'divi-squad-success-banner pro-activation-notice',
				'title'           => esc_html__( 'Approaching closer to unlocking the benefits of the Pro plugin.', 'squad-modules-for-divi' ),
				'content'         => sprintf(
				/* translators: %1$s: Product title; */
					esc_html__( ' The paid plugin of %1$s is already installed. Please activate it to start benefiting the Pro features.', 'squad-modules-for-divi' ),
					sprintf( '<em>%s</em>', esc_html__( 'Squad Modules Lite', 'squad-modules-for-divi' ) )
				),
				'action_buttons'  => array(
					'left' => array(
						array(
							'link'    => wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . divi_squad()->get_pro_basename(), 'activate-plugin_' . divi_squad()->get_pro_basename() ),
							'text'    => esc_html__( 'Active Pro Plugin', 'squad-modules-for-divi' ),
							'classes' => 'button-primary divi-squad-notice-action-button',
							'icon'    => 'dashicons-plugins-checked',
							'style'   => '',
							'type'    => 'primary',
							'action'  => null, // External link to plugins page
						),
					),
				),
				'is_dismissible'  => true,
				'rest_actions'    => array(
					'close' => 'notice/pro-activation/close',
				),
			);

			// Merge with default args
			$args = array_merge_recursive( $args, $activation_args );

			/**
			 * Filter the pro activation notice template arguments.
			 *
			 * @since 3.3.3
			 *
			 * @param array<string, mixed>  $args   The template arguments.
			 * @param Pro_Activation_Notice $notice The notice instance.
			 */
			return apply_filters( 'divi_squad_pro_activation_notice_template_args', $args, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Error getting pro activation notice template args' );

			// Fallback to basic notice if we encounter an error
			return array(
				'wrapper_classes' => 'divi-squad-success-banner pro-activation-notice',
				'title'           => esc_html__( 'Approaching closer to unlocking the benefits of the Pro plugin.', 'squad-modules-for-divi' ),
				'content'         => esc_html__( 'The paid plugin is already installed. Please activate it.', 'squad-modules-for-divi' ),
				'is_dismissible'  => true,
				'notice_id'       => $this->get_notice_id(),
			);
		}
	}

	/**
	 * Get the notice body classes.
	 *
	 * @since 3.3.3
	 *
	 * @return array<string> The notice body classes.
	 */
	public function get_body_classes(): array {
		$classes   = parent::get_body_classes();
		$classes[] = 'has-pro-activation-notice';

		/**
		 * Filter the pro activation notice body classes.
		 *
		 * @since 3.3.3
		 *
		 * @param array<string>         $classes The body classes.
		 * @param Pro_Activation_Notice $notice  The notice instance.
		 */
		return apply_filters( 'divi_squad_pro_activation_notice_body_classes', $classes, $this );
	}
}
