<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Review Notice Class
 *
 * Handles the display of the plugin review notice.
 *
 * @since   3.3.3
 * @author  The WP Squad <support@squadmodules.com>
 * @package DiviSquad
 */

namespace DiviSquad\Core\Admin\Notices;

use DiviSquad\Core\Supports\Links;
use DiviSquad\Utils\Helper;
use function esc_html__;

/**
 * Review Notice Class
 *
 * @since   3.3.3
 * @package DiviSquad
 */
class Review_Notice extends Notice_Base {

	/**
	 * The notice ID.
	 *
	 * @var string
	 */
	protected string $notice_id = 'review';

	/**
	 * How Long timeout until first banner shown (in days).
	 *
	 * @var int
	 */
	private int $first_time_show = 7;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$review_flag = divi_squad()->memory->get( 'review_flag' );
		$next_time   = divi_squad()->memory->get( 'next_review_time' );

		if ( '' === $review_flag && '' === $next_time ) {
			$activation = (int) divi_squad()->memory->get( 'activation_time' );
			$first_time = Helper::get_second( $this->first_time_show );
			$next_time  = 0 !== $activation ? $activation : time();

			// Update the database for next review.
			divi_squad()->memory->set( 'review_flag', false );
			divi_squad()->memory->set( 'next_review_time', $next_time + $first_time );
		}
	}

	/**
	 * Check if the notice can be rendered.
	 *
	 * @since 3.3.3
	 *
	 * @return bool Whether the notice can be rendered.
	 */
	public function can_render_it(): bool {
		// Check if the review flag is set.
		if ( true === divi_squad()->memory->get( 'review_flag' ) ) {
			return false;
		}

		// Check if the review time is passed.
		return time() > absint( divi_squad()->memory->get( 'next_review_time' ) );
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
			'wrapper_classes' => 'divi-squad-review-banner',
			'logo'            => 'logos/divi-squad-d-default.svg',
			'title'           => esc_html__( 'Loving Squad Modules Lite?', 'squad-modules-for-divi' ),
			'content'         => esc_html__( 'Please consider leaving a 5-star review to help us spread the word and boost our motivation.', 'squad-modules-for-divi' ),
			'action-buttons'  => array(
				'left'  => array(
					array(
						'link'    => Links::RATTING_URL,
						'classes' => 'button-primary divi-squad-notice-action-button',
						'style'   => '',
						'text'    => esc_html__( 'Ok, you deserve it!', 'squad-modules-for-divi' ),
						'icon'    => 'dashicons-external',
					),
					array(
						'link'    => '#',
						'classes' => 'divi-squad-notice-close',
						'style'   => 'text-decoration: none;',
						'text'    => esc_html__( 'Maybe Later', 'squad-modules-for-divi' ),
						'icon'    => 'dashicons-calendar-alt',
					),
					array(
						'link'    => '#',
						'classes' => 'divi-squad-notice-already',
						'style'   => 'text-decoration: none;',
						'text'    => esc_html__( 'Already did it', 'squad-modules-for-divi' ),
						'icon'    => 'dashicons-dismiss',
					),
				),
				'right' => array(
					array(
						'link'     => Links::ISSUES_URL,
						'classes'  => 'support',
						'style'    => '',
						'text'     => esc_html__( 'Help Needed? Create a Issue', 'squad-modules-for-divi' ),
						'icon_svg' => 'icons/question.svg',
					),
				),
			),
			'is_dismissible'  => true,
		);
	}
}
