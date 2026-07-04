<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Error Email Sender
 *
 * Handles creating and sending error report emails with proper WordPress integration,
 * template handling, and comprehensive error management.
 *
 * @since   3.4.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Core\Error;

use Throwable;
use WP_Error;

/**
 * Error Email Sender Class
 *
 * Handles the creation and sending of error report emails.
 *
 * Features:
 * - HTML email templates with fallback
 * - WordPress filter and action hooks for extensibility
 * - Comprehensive error handling
 *
 * @since   3.4.0
 * @package DiviSquad
 */
class Email_Sender {
	/**
	 * Support email recipient
	 *
	 * @since 3.4.0
	 * @var string
	 */
	protected string $to = 'support@squadmodules.com';

	/**
	 * WP mail failure errors
	 *
	 * @since 3.4.0
	 * @var WP_Error
	 */
	protected WP_Error $wp_mail_errors;

	/**
	 * Initialize error email sender
	 *
	 * Sets up the email sender with proper configuration.
	 *
	 * @since 3.4.0
	 */
	public function __construct() {
		// Initialise the collector so get_wp_mail_errors() is safe before a send.
		$this->wp_mail_errors = new WP_Error();

		/**
		 * Filter the recipient email address for error reports.
		 *
		 * @since 3.4.0
		 *
		 * @param string $to Default recipient email address.
		 */
		$this->to = apply_filters( 'divi_squad_error_report_recipient', $this->to );
	}

	/**
	 * Send error report email
	 *
	 * Creates and sends the error report email.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $data   Error report data.
	 * @param WP_Error             $errors Error object to store any errors.
	 *
	 * @return bool Success status.
	 */
	public function send_email( array $data, WP_Error $errors ): bool {
		try {
			// Ensure WordPress mail functions are available.
			if ( ! function_exists( 'wp_mail' ) ) {
				require_once ABSPATH . WPINC . '/pluggable.php';
			}

			// Initialize wp_mail error collector.
			$this->wp_mail_errors = new WP_Error();

			// Configure email.
			$this->add_email_filters();

			// Get email parameters.
			$to      = $this->get_recipient();
			$subject = $this->get_email_subject( $data );
			$message = $this->get_email_message_html( $data );
			$headers = $this->get_email_headers();

			/**
			 * Filter the email parameters before sending.
			 *
			 * @since 3.4.0
			 *
			 * @param array                $email_params {
			 *                                           Email parameters.
			 *
			 * @type string                $to           Email recipient.
			 * @type string                $subject      Email subject.
			 * @type string                $message      Email message.
			 * @type array<string>         $headers      Email headers.
			 *                                           }
			 *
			 * @param array<string, mixed> $data         Error report data.
			 */
			$email_params = apply_filters(
				'divi_squad_error_report_email_params',
				array(
					'to'      => $to,
					'subject' => $subject,
					'message' => $message,
					'headers' => $headers,
				),
				$data
			);

			// Send email.
			$sent = wp_mail(
				$email_params['to'],
				$email_params['subject'],
				$email_params['message'],
				$email_params['headers']
			);

			if ( false === $sent ) {
				foreach ( $this->wp_mail_errors->get_error_codes() as $code ) {
					foreach ( $this->wp_mail_errors->get_error_messages( $code ) as $error_line ) {
						$errors->add( $code, $error_line, $this->wp_mail_errors->get_error_data( $code ) );
					}
				}
			}

			return $sent;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Error sending email', false );
			$errors->add( 'email_send_failed', $e->getMessage() );

			return false;
		} finally {
			$this->remove_email_filters();
		}
	}

	/**
	 * Get the recipient email address
	 *
	 * @since 3.4.0
	 *
	 * @return string Email recipient.
	 */
	protected function get_recipient(): string {
		return $this->to;
	}

	/**
	 * Add email filters
	 *
	 * Sets up WordPress filters needed for email sending.
	 *
	 * @since 3.4.0
	 */
	protected function add_email_filters(): void {
		add_action( 'wp_mail_failed', array( $this, 'set_failure_errors' ) );
		add_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );

		/**
		 * Action triggered when email filters are being added.
		 *
		 * Use this hook to add additional filters for email customization.
		 *
		 * @since 3.4.0
		 *
		 * @param Email_Sender $email_sender Current ErrorEmailSender instance.
		 */
		do_action( 'divi_squad_error_report_add_email_filters', $this );
	}

	/**
	 * Remove email filters
	 *
	 * Cleans up WordPress filters after email sending.
	 *
	 * @since 3.4.0
	 */
	protected function remove_email_filters(): void {
		remove_action( 'wp_mail_failed', array( $this, 'set_failure_errors' ) );
		remove_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );

		/**
		 * Action triggered when email filters are being removed.
		 *
		 * Use this hook to remove additional filters added during email customization.
		 *
		 * @since 3.4.0
		 *
		 * @param Email_Sender $email_sender Current ErrorEmailSender instance.
		 */
		do_action( 'divi_squad_error_report_remove_email_filters', $this );
	}

	/**
	 * Generate email subject
	 *
	 * Creates a descriptive subject line for the error report email.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $data Error report data.
	 *
	 * @return string Email subject.
	 */
	protected function get_email_subject( array $data ): string {
		$parsed_host   = wp_parse_url( home_url(), PHP_URL_HOST );
		$site_host     = is_string( $parsed_host ) ? $parsed_host : '';
		$error_message = $data['error_message'] ?? 'unknown error';

		$subject = sprintf(
			'[Error Report][%s]: %s',
			$site_host,
			$error_message
		);

		/**
		 * Filter the error report email subject.
		 *
		 * @since 3.4.0
		 *
		 * @param string               $subject Email subject.
		 * @param array<string, mixed> $data    Error report data.
		 */
		return apply_filters( 'divi_squad_error_report_subject', $subject, $data );
	}

	/**
	 * Get email headers
	 *
	 * Builds the headers for the error report email.
	 *
	 * @since 3.4.0
	 *
	 * @return array<string> Email headers.
	 */
	protected function get_email_headers(): array {
		$site_name   = get_bloginfo( 'name' );
		$admin_email = get_bloginfo( 'admin_email' );
		$charset     = get_bloginfo( 'charset' );

		$headers = array(
			sprintf( 'From: %s <%s>', $site_name, $admin_email ),
			'X-Mailer-Type: SquadModules/AutomatedErrorReport',
		);

		/**
		 * Filter the error report email headers.
		 *
		 * @since 3.4.0
		 *
		 * @param array<string> $headers Email headers.
		 */
		return apply_filters( 'divi_squad_error_report_headers', $headers );
	}

	/**
	 * Generate HTML email content
	 *
	 * Creates the HTML content for the error report email by using a template
	 * or falling back to a simple message if the template is not available.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $data Error report data.
	 *
	 * @return string Email HTML content.
	 */
	protected function get_email_message_html( array $data ): string {
		try {
			// Reporter::process_template_data() already prepared and filtered the
			// payload (incl. the divi_squad_error_report_template_data_processed
			// filter). Only backfill site keys for callers that bypass it; do not
			// re-apply a second template-data filter over the same data.
			$template_data              = $data;
			$template_data['site_url']  = $template_data['site_url'] ?? home_url();
			$template_data['site_name'] = $template_data['site_name'] ?? get_bloginfo( 'name' );
			$template_data['timestamp'] = $template_data['timestamp'] ?? current_time( 'mysql' );
			$template_data['charset']   = $template_data['charset'] ?? get_bloginfo( 'charset' );

			/**
			 * Filter the path to the error report email template.
			 *
			 * @since 3.4.0
			 *
			 * @param string $template_path Default template path.
			 */
			$template = apply_filters(
				'divi_squad_error_report_template_path',
				divi_squad()->get_template_path( '/emails/error-report.php' )
			);

			// Start output buffering.
			ob_start();

			if ( divi_squad()->get_wp_fs()->exists( $template ) ) {
				/**
				 * Action fired before including the error report template.
				 *
				 * @since 3.4.0
				 *
				 * @param string               $template Template path.
				 * @param array<string, mixed> $data     Template data.
				 */
				do_action( 'divi_squad_before_error_report_template', $template, $template_data );

				load_template( $template, false, $template_data );

				/**
				 * Action fired after including the error report template.
				 *
				 * @since 3.4.0
				 *
				 * @param string               $template Template path.
				 * @param array<string, mixed> $data     Template data.
				 */
				do_action( 'divi_squad_after_error_report_template', $template, $template_data );
			} else {
				return $this->generate_fallback_message( $template_data );
			}

			// Get the output buffer contents and clean the buffer.
			$output = ob_get_clean();
			if ( false === $output ) {
				return $this->generate_fallback_message( $template_data );
			}

			/**
			 * Filter the generated HTML content for the error report email.
			 *
			 * @since 3.4.0
			 *
			 * @param string               $output Generated HTML content.
			 * @param array<string, mixed> $data   Template data.
			 */
			return apply_filters( 'divi_squad_error_report_html_content', $output, $template_data );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Error generating email HTML content' );

			return $this->generate_fallback_message( $data );
		}
	}

	/**
	 * Generate fallback message when template is missing
	 *
	 * Creates a simple HTML message when the email template is not available.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $data Template data.
	 *
	 * @return string Fallback HTML message.
	 */
	protected function generate_fallback_message( array $data = array() ): string {
		try {
			$encoded_data = wp_json_encode( $data, JSON_PRETTY_PRINT );
			if ( false === $encoded_data ) {
				$encoded_data = esc_html__( 'Unable to encode data', 'squad-modules-for-divi' );
			}

			$message = sprintf(
				'<h2>%s</h2><p><strong>%s:</strong> %s</p><p><strong>%s:</strong> %s</p><pre>%s</pre>',
				esc_html__( 'Error Report', 'squad-modules-for-divi' ),
				esc_html__( 'Site', 'squad-modules-for-divi' ),
				esc_html( home_url() ),
				esc_html__( 'Error', 'squad-modules-for-divi' ),
				isset( $data['error_message'] ) ? esc_html( $data['error_message'] ) : esc_html__( 'Unknown error', 'squad-modules-for-divi' ),
				esc_html( $encoded_data )
			);

			/**
			 * Filter the fallback message when the email template is missing.
			 *
			 * @since 3.4.0
			 *
			 * @param string               $message Fallback message.
			 * @param array<string, mixed> $data    Template data.
			 */
			return apply_filters( 'divi_squad_error_report_fallback_message', $message, $data );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Error generating fallback message' );

			return '<h2>Error Report</h2><p>An error occurred while generating this report.</p>';
		}
	}

	/**
	 * Set HTML content type
	 *
	 * Used as a callback for the wp_mail_content_type filter.
	 *
	 * @since 3.4.0
	 *
	 * @return string Content type.
	 */
	public function set_html_content_type(): string {
		return 'text/html';
	}

	/**
	 * Handle mail failures
	 *
	 * Callback for the wp_mail_failed action to capture mail errors.
	 *
	 * @since 3.4.0
	 *
	 * @param WP_Error $error Mail error.
	 */
	public function set_failure_errors( WP_Error $error ): void {
		try {
			foreach ( $error->get_error_codes() as $code ) {
				foreach ( $error->get_error_messages( $code ) as $message ) {
					$this->wp_mail_errors->add( $code, $message, $error->get_error_data( $code ) );
				}
			}

			/**
			 * Action triggered when a mail failure occurs.
			 *
			 * @since 3.4.0
			 *
			 * @param WP_Error $error Current error object.
			 */
			do_action( 'divi_squad_error_report_mail_failed', $error );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Error handling mail failure' );
		}
	}

	/**
	 * Get WP mail failure errors
	 *
	 * @since 3.4.0
	 *
	 * @return WP_Error Mail failure errors.
	 */
	public function get_wp_mail_errors(): WP_Error {
		return $this->wp_mail_errors;
	}
}
