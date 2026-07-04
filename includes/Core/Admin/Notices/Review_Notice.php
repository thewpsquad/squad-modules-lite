<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Review Notice Class
 *
 * Handles the display of the plugin review notice.
 *
 * @since   3.3.3
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Core\Admin\Notices;

use DiviSquad\Core\Supports\Links;
use DiviSquad\Utils\Helper;
use Throwable;
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
	 * Notice scopes.
	 *
	 * @var array<string>
	 */
	protected array $scopes = array( 'global', 'dashboard' );

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
		try {
			/**
			 * Filter the initial timeout for the review notice.
			 *
			 * @since 3.3.3
			 *
			 * @param int           $days   Number of days to wait before showing.
			 * @param Review_Notice $notice The notice instance.
			 */
			$this->first_time_show = apply_filters( 'divi_squad_review_notice_initial_timeout', $this->first_time_show, $this );

			// Initialize review notice data if it doesn't exist
			$this->initialize_review_data();

			/**
			 * Action fired after review notice is constructed.
			 *
			 * @since 3.3.3
			 *
			 * @param Review_Notice $notice The notice instance.
			 */
			do_action( 'divi_squad_review_notice_constructed', $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Error initializing review notice' );
		}
	}

	/**
	 * Initialize review notice data.
	 *
	 * @since 3.3.3
	 *
	 * @return void
	 */
	private function initialize_review_data(): void {
		$activation  = (int) divi_squad()->memory->get( 'activation_time', 0 );
		$review_flag = divi_squad()->memory->get( 'review_flag', '' );
		$next_time   = divi_squad()->memory->get( 'next_review_time', '' );

		if ( '' === $review_flag && '' === $next_time ) {
			$first_time = Helper::get_second( $this->first_time_show );
			$next_time  = 0 !== $activation ? $activation : time();

			// Update the database for next review.
			divi_squad()->memory->set( 'review_flag', false );
			divi_squad()->memory->set( 'next_review_time', $next_time + $first_time );

			/**
			 * Action fired after review notice data is initialized.
			 *
			 * @since 3.3.3
			 *
			 * @param int           $next_time  The timestamp for the next review display.
			 * @param int           $first_time The delay in seconds before first showing.
			 * @param Review_Notice $notice     The notice instance.
			 */
			do_action( 'divi_squad_review_notice_data_initialized', $next_time + $first_time, $first_time, $this );
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
		try {
			// Check if the review flag is set.
			if ( true === (bool) divi_squad()->memory->get( 'review_flag' ) ) {
				return false;
			}

			// Check if the review time is passed.
			$can_render = time() > absint( divi_squad()->memory->get( 'next_review_time' ) );

			/**
			 * Filter whether the review notice can be rendered.
			 *
			 * @since 3.3.3
			 *
			 * @param bool          $can_render Whether the notice can be rendered.
			 * @param Review_Notice $notice     The notice instance.
			 */
			return apply_filters( 'divi_squad_can_render_review_notice', $can_render, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Error checking if review notice can be rendered' );

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

			// Override with review-specific values
			$review_args = array(
				'wrapper_classes' => 'divi-squad-review-banner',
				'title'           => esc_html__( 'Loving Squad Modules Lite?', 'squad-modules-for-divi' ),
				'content'         => esc_html__( 'Please consider leaving a 5-star review to help us spread the word and boost our motivation.', 'squad-modules-for-divi' ),
				'action_buttons'  => array(
					'left'  => array(
						array(
							'link'    => Links::RATTING_URL,
							'classes' => 'button-primary divi-squad-notice-action-button',
							'style'   => '',
							'text'    => esc_html__( 'Ok, you deserve it!', 'squad-modules-for-divi' ),
							'icon'    => 'dashicons-external',
							'type'    => 'primary',
							'action'  => 'review_done',
						),
						array(
							'link'    => '#',
							'classes' => 'divi-squad-notice-close',
							'style'   => 'text-decoration: none',
							'text'    => esc_html__( 'Maybe Later', 'squad-modules-for-divi' ),
							'icon'    => 'dashicons-calendar-alt',
							'type'    => 'link',
							'action'  => 'review_later',
						),
						array(
							'link'    => '#',
							'classes' => 'divi-squad-notice-already',
							'style'   => 'text-decoration: none',
							'text'    => esc_html__( 'Already did it', 'squad-modules-for-divi' ),
							'icon'    => 'dashicons-dismiss',
							'type'    => 'link',
							'action'  => 'review_done',
						),
					),
					'right' => array(
						array(
							'link'     => Links::ISSUES_URL,
							'classes'  => 'support',
							'style'    => '',
							'text'     => esc_html__( 'Help Needed? Create a Issue', 'squad-modules-for-divi' ),
							'icon_svg' => 'icons/question.svg',
							'type'     => 'link',
							'action'   => 'ask_support',
						),
					),
				),
				'is_dismissible'  => true,
				'rest_actions'    => array(
					'review_done'  => 'notice/review/done',
					'review_later' => 'notice/review/next-week',
					'ask_support'  => 'notice/review/ask-support',
					'close'        => 'notice/review/close',
				),
			);

			// Merge with default args
			$args = array_merge_recursive( $args, $review_args );

			/**
			 * Filter the review notice template arguments.
			 *
			 * @since 3.3.3
			 *
			 * @param array<string, mixed> $args   The template arguments.
			 * @param Review_Notice        $notice The notice instance.
			 */
			return apply_filters( 'divi_squad_review_notice_template_args', $args, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Error getting review notice template args' );

			// Fallback to basic notice if we encounter an error
			return array(
				'wrapper_classes' => 'divi-squad-review-banner',
				'title'           => esc_html__( 'Loving Squad Modules Lite?', 'squad-modules-for-divi' ),
				'content'         => esc_html__( 'Please consider leaving a review.', 'squad-modules-for-divi' ),
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
		$classes[] = 'has-review-notice';

		/**
		 * Filter the review notice body classes.
		 *
		 * @since 3.3.3
		 *
		 * @param array<string> $classes The body classes.
		 * @param Review_Notice $notice  The notice instance.
		 */
		return apply_filters( 'divi_squad_review_notice_body_classes', $classes, $this );
	}
}
