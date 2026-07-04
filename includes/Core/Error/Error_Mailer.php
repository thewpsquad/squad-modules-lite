<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Error Mailer
 *
 * Handles email delivery for error reports with optimized template processing.
 *
 * @since   3.4.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Core\Error;

use Throwable;
use WP_Error;

/**
 * Error_Mailer Class
 *
 * Optimized email sender for error reports with minimal overhead.
 *
 * @since   3.4.0
 * @package DiviSquad
 */
class Error_Mailer {
	/**
	 * Support email address
	 *
	 * @var string
	 */
	private string $recipient = 'support@squadmodules.com';

	/**
	 * Email headers cache
	 *
	 * @var array<string>|null
	 */
	private ?array $headers_cache = null;

	/**
	 * Initialize mailer
	 */
	public function __construct() {
		$this->recipient = apply_filters( 'divi_squad_error_report_recipient', $this->recipient );
	}

	/**
	 * Send error report email
	 *
	 * @param array<string, mixed> $data   Error report data
	 * @param WP_Error             $errors Error collector
	 *
	 * @return bool Success status
	 */
	public function send( array $data, WP_Error $errors ): bool {
		if ( ! function_exists( 'wp_mail' ) ) {
			require_once ABSPATH . WPINC . '/pluggable.php';
		}

		try {
			$this->setup_mail_filters();

			$result = wp_mail(
				$this->recipient,
				$this->build_subject( $data ),
				$this->build_message( $data ),
				$this->get_headers()
			);

			return (bool) $result;

		} catch ( Throwable $e ) {
			$errors->add( 'mail_failed', $e->getMessage() );
			divi_squad()->log_error( $e, 'Email send failed', false );
			return false;
		} finally {
			$this->cleanup_mail_filters();
		}
	}

	/**
	 * Build email subject
	 *
	 * @param array<string, mixed> $data Error data
	 *
	 * @return string Subject line
	 */
	private function build_subject( array $data ): string {
		$site_host = wp_parse_url( home_url(), PHP_URL_HOST );
		$error_msg = $data['error_message'] ?? 'Unknown Error';

		return sprintf( '[Squad Error][%s]: %s', $site_host, $error_msg );
	}

	/**
	 * Build email message
	 *
	 * @param array<string, mixed> $data Error data
	 *
	 * @return string HTML message
	 */
	private function build_message( array $data ): string {
		$template_path = apply_filters(
			'divi_squad_error_template_path',
			divi_squad()->get_template_path( '/emails/error-report.php' )
		);

		if ( file_exists( $template_path ) ) {
			return $this->render_template( $template_path, $data );
		}

		return $this->build_fallback_message( $data );
	}

	/**
	 * Render email template
	 *
	 * @param string               $template Template path
	 * @param array<string, mixed> $data     Template data
	 *
	 * @return string Rendered HTML
	 */
	private function render_template( string $template, array $data ): string {
		ob_start();
		extract( $data ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		include $template;
		$content = ob_get_clean();

		if ( false !== $content ) {
			return $content;
		}

		return $this->build_fallback_message( $data );
	}

	/**
	 * Build fallback message
	 *
	 * @param array<string, mixed> $data Error data
	 *
	 * @return string HTML fallback
	 */
	private function build_fallback_message( array $data ): string {
		$error_msg = esc_html( $data['error_message'] ?? 'Unknown error' );
		$site_url  = esc_url( home_url() );
		$json_data = esc_html( wp_json_encode( $data, JSON_PRETTY_PRINT ) ?: 'Data encoding failed' );

		return sprintf(
			'<h2>Error Report</h2><p><strong>Site:</strong> %s</p><p><strong>Error:</strong> %s</p><pre>%s</pre>',
			$site_url,
			$error_msg,
			$json_data
		);
	}

	/**
	 * Get email headers (cached)
	 *
	 * @return array<string> Headers
	 */
	private function get_headers(): array {
		if ( null === $this->headers_cache ) {
			$this->headers_cache = array(
				sprintf( 'From: %s <%s>', get_bloginfo( 'name' ), get_bloginfo( 'admin_email' ) ),
				'X-Mailer: SquadModules/Error_Reporter',
				'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ),
			);
		}

		return $this->headers_cache;
	}

	/**
	 * Setup mail filters
	 */
	private function setup_mail_filters(): void {
		add_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );
		add_action( 'wp_mail_failed', array( $this, 'handle_mail_failure' ) );
	}

	/**
	 * Cleanup mail filters
	 */
	private function cleanup_mail_filters(): void {
		remove_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );
		remove_action( 'wp_mail_failed', array( $this, 'handle_mail_failure' ) );
	}

	/**
	 * Set HTML content type
	 *
	 * @return string Content type
	 */
	public function set_html_content_type(): string {
		return 'text/html';
	}

	/**
	 * Handle mail failure
	 *
	 * @param WP_Error $error Mail error
	 */
	public function handle_mail_failure( WP_Error $error ): void {
		divi_squad()->log_error( new \RuntimeException( $error->get_error_message() ), 'WP Mail failed', false );
	}
}
