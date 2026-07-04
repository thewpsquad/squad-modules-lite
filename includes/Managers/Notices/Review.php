<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Plugin Review
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   1.2.3
 */

namespace DiviSquad\Managers\Notices;

use DiviSquad\Base\Factories\AdminNotice\Notice;
use DiviSquad\Core\Supports\Links;
use function esc_html__;

/**
 * Plugin Review Class
 *
 * @package DiviSquad
 * @since   1.2.3
 *
 * @ref essential-addons-for-elementor-lite/includes/Traits/Helper.php:551.
 */
class Review extends Notice {

	/**
	 * The notice id for the notice.
	 *
	 * @var string
	 */
	protected $notice_id = 'review';

	/**
	 * How Long timeout until first banner shown.
	 *
	 * @var int
	 */
	private int $first_time_show = 7;

	/**
	 * Init constructor.
	 */
	public function __construct() {
		$review_flag = divi_squad()->memory->get( 'review_flag' );
		$next_time   = divi_squad()->memory->get( 'next_review_time' );

		if ( '' === $review_flag && '' === $next_time ) {
			$activation = (int) divi_squad()->memory->get( 'activation_time' );
			$first_time = $this->first_time_show * DAY_IN_SECONDS;
			$next_time  = 0 !== $activation ? $activation : time();

			// Update the database for next review.
			divi_squad()->memory->set( 'review_flag', false );
			divi_squad()->memory->set( 'next_review_time', $next_time + $first_time );
		}
	}

	/**
	 * Check if we can render notice.
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
	 * Filters the CSS classes for the body tag in the admin.
	 *
	 * @return string
	 * @since 1.2.5
	 */
	public function get_body_classes(): string {
		return 'divi-squad-notice';
	}

	/**
	 * Get the template arguments
	 *
	 * @since 3.0.0
	 *
	 * @return array<string, mixed>
	 */
	public function get_template_args(): array {
		// phpcs:disable
		/**
		 * The arguments to the template.
		 *
		 * title: 'Enjoying Divi Squad?',
		 * content: 'Please consider leaving a review to help us spread the word and boost our motivation.',
		 *
		 * action-buttons: {
		 *    left: [
		 *   {
		 *     link: 'https://wordpress.org/support/plugin/squad-modules-for-divi/reviews/?rate=5#new-post',
		 *     classes: 'button button-primary',
		 *     text: 'Leave a Review',
		 *   },
		 *   {
		 *     link: '#',
		 *     classes: 'divi-squad-notice-close',
		 *     text: 'Maybe Later',
		 *   },
		 *   {
		 *     link: '#',
		 *     classes: 'divi-squad-notice-already',
		 *     text: 'Never show again',
		 *   },
		 *   ],
		 *  right: [
		 *   {
		 *    link: 'https://squadmodules.com/contact/',
		 *    classes: 'button button-secondary',
		 *    text: 'Contact Support',
		 *   },
		 *   ],
		 * },
		 */
		// phpcs:enable
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
