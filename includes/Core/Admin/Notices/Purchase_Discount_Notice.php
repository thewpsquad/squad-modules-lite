<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Discount Notice Class
 *
 * Handles the display of the welcome discount notice.
 *
 * @since   3.3.3
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Core\Admin\Notices;

use Throwable;
use function esc_html__;
use function sprintf;

/**
 * Discount Notice Class
 *
 * @since   3.3.3
 * @package DiviSquad
 */
class Purchase_Discount_Notice extends Notice_Base {

	/**
	 * The notice ID.
	 *
	 * @var string
	 */
	protected string $notice_id = 'discount';

	/**
	 * Notice scopes.
	 *
	 * @var array<string>
	 */
	protected array $scopes = array( 'global' );

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

			// Check if notice is already closed
			$is_pro_notice_closed = (bool) divi_squad()->memory->get( 'beta_campaign_notice_close', false );

			// Only render if user cannot use premium code and notice is not closed
			$can_render = ! $can_use_premium_code && ! $is_pro_notice_closed;

			/**
			 * Filter whether the discount notice can be rendered.
			 *
			 * @since 3.3.3
			 *
			 * @param bool                     $can_render Whether the notice can be rendered.
			 * @param Purchase_Discount_Notice $notice     The notice instance.
			 */
			return apply_filters( 'divi_squad_can_render_discount_notice', $can_render, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Error checking if discount notice can be rendered' );

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

			// Override with discount-specific values
			$discount_args = array(
				'wrapper_classes' => 'divi-squad-success-banner welcome-discount',
				'title'           => esc_html__( 'Unleash Your Divi Creativity with Squad Modules Pro!', 'squad-modules-for-divi' ),
				'content'         => sprintf(
				/* Translators: %1$s is the welcome message, %2$s is the coupon code. */
					esc_html__( 'Get a special discount and start building stunning websites today. Use code "%1$s" at checkout.', 'squad-modules-for-divi' ),
					'<code>WELCOME60</code>'
				),
				'action_buttons'  => array(
					'left' => array(
						array(
							'link'    => esc_url( divi_squad_fs()->get_upgrade_url() ),
							'classes' => 'button-primary divi-squad-notice-action-button',
							'style'   => '',
							'text'    => esc_html__( 'Upgrade Now', 'squad-modules-for-divi' ),
							'icon'    => 'dashicons-cart',
							'type'    => 'primary',
							'action'  => null, // External link, no JS action
						),
					),
				),
				'is_dismissible'  => true,
				'rest_actions'    => array(
					'close' => 'notice/discount/close',
				),
			);

			// Merge with default args
			$args = array_merge( $args, $discount_args );

			/**
			 * Filter the discount notice template arguments.
			 *
			 * @since 3.3.3
			 *
			 * @param array<string, mixed>     $args   The template arguments.
			 * @param Purchase_Discount_Notice $notice The notice instance.
			 */
			return apply_filters( 'divi_squad_discount_notice_template_args', $args, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Error getting discount notice template args' );

			// Fallback to basic notice if we encounter an error
			return array(
				'wrapper_classes' => 'divi-squad-success-banner welcome-discount',
				'content'         => esc_html__( 'Get a special discount on Squad Modules Pro!', 'squad-modules-for-divi' ),
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
		$classes[] = 'has-discount-notice';

		/**
		 * Filter the discount notice body classes.
		 *
		 * @since 3.3.3
		 *
		 * @param array<string>            $classes The body classes.
		 * @param Purchase_Discount_Notice $notice  The notice instance.
		 */
		return apply_filters( 'divi_squad_discount_notice_body_classes', $classes, $this );
	}
}
