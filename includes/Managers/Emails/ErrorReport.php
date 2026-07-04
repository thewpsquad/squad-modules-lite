<?php // phpcs:ignore WordPress.Files.FileName

namespace DiviSquad\Managers\Emails;

use DiviSquad\Utils\WP;
use WP_Error;
use function wp_mail;

/**
 * Class ErrorReport
 *
 * Handles error report email creation and delivery with rate limiting,
 * validation, and comprehensive error handling.
 *
 * @since   3.1.7
 * @package DiviSquad\Managers\Emails
 */
class ErrorReport {
	/**
	 * Support email recipient
	 *
	 * @var string
	 */
	private string $to = 'support@squadmodules.com';

	/**
	 * Error report data
	 *
	 * @var array
	 */
	private array $data = array();

	/**
	 * WP_Error instance for error handling
	 *
	 * @var WP_Error
	 */
	private WP_Error $errors;

	/**
	 * Email sending result
	 *
	 * @var bool
	 */
	private bool $result = false;

	/**
	 * Rate limit key prefix
	 *
	 * @var string
	 */
	private const RATE_LIMIT_KEY = 'squad_error_report_';

	/**
	 * Rate limit window in seconds (15 minutes)
	 *
	 * @var int
	 */
	private const RATE_LIMIT_WINDOW = 900;

	/**
	 * Maximum reports per window
	 *
	 * @var int
	 */
	private const MAX_REPORTS = 5;

	/**
	 * Required data fields
	 *
	 * @var array
	 */
	private const REQUIRED_FIELDS = array(
		'error_message',
		'error_code',
		'error_file',
		'error_line',
	);

	/**
	 * Initialize error report
	 *
	 * @since 3.1.7
	 *
	 * @param array $data Error report data
	 */
	public function __construct( array $data ) {
		$this->data   = $this->sanitize_data( $data );
		$this->errors = new WP_Error();
	}

	/**
	 * Send error report email with rate limiting and validation
	 *
	 * @since 3.1.7
	 * @return bool Success status
	 */
	public function send(): bool {
		try {
			// Validate rate limit
			if ( ! $this->check_rate_limit() ) {
				throw new \RuntimeException(
					esc_html__( 'Error report rate limit exceeded. Please try again later.', 'squad-modules-for-divi' )
				);
			}

			// Validate data
			if ( ! $this->validate_data() ) {
				$errors = $this->get_error_messages();
				throw new \RuntimeException(
					sprintf(
					/* translators: %s: Error messages */
						esc_html__( 'Error report validation failed: %s', 'squad-modules-for-divi' ),
						implode( ', ', $errors )
					)
				);
			}

			// Ensure WordPress mail functions are available
			require_once ABSPATH . WPINC . '/pluggable.php';

			// Configure email
			$this->add_email_filters();

			// Send email
			$this->result = wp_mail(
				$this->to,
				$this->get_email_subject(),
				$this->get_email_message_html(),
				$this->get_email_headers()
			);

			// Increment rate limit counter on success
			if ( $this->result ) {
				$this->increment_rate_limit();
			}

			return $this->result;

		} catch ( \Throwable $e ) {
			$this->errors->add( 'send_failed', $e->getMessage() );

			return false;

		} finally {
			$this->remove_email_filters();
		}
	}

	/**
	 * Validate required data fields
	 *
	 * @since 3.1.7
	 * @return bool Validation result
	 */
	private function validate_data(): bool {
		foreach ( self::REQUIRED_FIELDS as $field ) {
			if ( empty( $this->data[ $field ] ) ) {
				$this->errors->add(
					$field,
					sprintf(
					/* translators: %s: Field name */
						esc_html__( '%s is required for error reporting.', 'squad-modules-for-divi' ),
						ucfirst( str_replace( '_', ' ', $field ) )
					)
				);
			}
		}

		return ! $this->errors->has_errors();
	}

	/**
	 * Sanitize input data
	 *
	 * @since 3.1.7
	 *
	 * @param array $data Raw input data
	 *
	 * @return array Sanitized data
	 */
	private function sanitize_data( array $data ): array {
		$sanitized = array();

		foreach ( $data as $key => $value ) {
			if ( is_string( $value ) ) {
				$sanitized[ $key ] = sanitize_text_field( $value );
			} elseif ( is_array( $value ) ) {
				$sanitized[ $key ] = $this->sanitize_data( $value );
			} else {
				$sanitized[ $key ] = $value;
			}
		}

		return $sanitized;
	}

	/**
	 * Get formatted error messages
	 *
	 * @since 3.1.7
	 * @return array Error messages
	 */
	private function get_error_messages(): array {
		$messages = array();

		foreach ( $this->errors->get_error_codes() as $code ) {
			$messages = array_merge(
				$messages,
				$this->errors->get_error_messages( $code )
			);
		}

		return array_unique( array_filter( $messages ) );
	}

	/**
	 * Check if rate limit is exceeded
	 *
	 * @since 3.1.7
	 * @return bool Whether sending is allowed
	 */
	private function check_rate_limit(): bool {
		$count = (int) get_transient( $this->get_rate_limit_key() );

		return $count < self::MAX_REPORTS;
	}

	/**
	 * Increment rate limit counter
	 *
	 * @since 3.1.7
	 */
	private function increment_rate_limit(): void {
		$key   = $this->get_rate_limit_key();
		$count = (int) get_transient( $key );

		if ( 0 === $count ) {
			set_transient( $key, 1, self::RATE_LIMIT_WINDOW );
		} else {
			set_transient( $key, $count + 1, self::RATE_LIMIT_WINDOW );
		}
	}

	/**
	 * Get rate limit key for current site
	 *
	 * @since 3.1.7
	 * @return string Rate limit key
	 */
	private function get_rate_limit_key(): string {
		return self::RATE_LIMIT_KEY . wp_hash( (string) get_current_blog_id() );
	}

	/**
	 * Add email filters
	 *
	 * @since 3.1.7
	 */
	private function add_email_filters(): void {
		add_action( 'wp_mail_failed', array( $this, 'set_failure_errors' ) );
		add_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );
	}

	/**
	 * Remove email filters
	 *
	 * @since 3.1.7
	 */
	private function remove_email_filters(): void {
		remove_action( 'wp_mail_failed', array( $this, 'set_failure_errors' ) );
		remove_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );
	}

	/**
	 * Generate email subject
	 *
	 * @since 3.1.7
	 * @return string Email subject
	 */
	private function get_email_subject(): string {
		return sprintf(
			'[Error Report][%s] %s: %s',
			wp_parse_url( home_url(), PHP_URL_HOST ),
			$this->data['error_code'],
			substr( $this->data['error_message'], 0, 50 )
		);
	}

	/**
	 * Get email headers
	 *
	 * @since 3.1.7
	 * @return array Email headers
	 */
	private function get_email_headers(): array {
		return array(
			sprintf( 'From: %s <%s>', get_bloginfo( 'name' ), get_bloginfo( 'admin_email' ) ),
			'X-Mailer-Type: SquadModules/Lite/ErrorReport',
			'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ),
		);
	}

	/**
	 * Generate HTML email content
	 *
	 * @since 3.1.7
	 * @return string Email HTML content
	 */
	private function get_email_message_html(): string {
		ob_start();
		$template = divi_squad()->get_template_path() . '/emails/error-report.php';

		if ( file_exists( $template ) ) {
			$data = array_merge(
				$this->data,
				array(
					'site_url'    => home_url(),
					'site_name'   => get_bloginfo( 'name' ),
					'timestamp'   => current_time( 'mysql' ),
					'environment' => $this->get_environment_info(),
				)
			);

			require_once $template;
		} else {
			$this->generate_fallback_message();
		}

		return ob_get_clean();
	}

	/**
	 * Generate fallback message when template is missing
	 *
	 * @since 3.1.7
	 */
	private function generate_fallback_message(): void {
		printf(
			'<h2>%s</h2><pre>%s</pre>',
			esc_html__( 'Error Report', 'squad-modules-for-divi' ),
			esc_html( print_r( $this->data, true ) ) // phpcs:ignore WordPress.PHP.DevelopmentFunctions
		);
	}

	/**
	 * Get environment information
	 *
	 * @since 3.1.7
	 * @return array Environment info
	 */
	private function get_environment_info(): array {
		return array(
			'php_version'    => PHP_VERSION,
			'wp_version'     => get_bloginfo( 'version' ),
			'plugin_version' => divi_squad()->get_version_dot(),
			'active_theme'   => wp_get_theme()->get( 'Name' ),
			'active_plugins' => self::get_active_plugins_list(),
		);
	}

	/**
	 * Set HTML content type
	 *
	 * @since 3.1.7
	 * @return string Content type
	 */
	public function set_html_content_type(): string {
		return 'text/html';
	}

	/**
	 * Handle mail failures
	 *
	 * @since 3.1.7
	 *
	 * @param WP_Error $error Mail error
	 */
	public function set_failure_errors( WP_Error $error ): void {
		$this->errors->merge_from( $error );
	}

	/**
	 * Get error object
	 *
	 * @since 3.1.7
	 * @return WP_Error Error object
	 */
	public function get_errors(): WP_Error {
		return $this->errors;
	}

	/**
	 * Get send result
	 *
	 * @since 3.1.7
	 * @return bool Result
	 */
	public function get_result(): bool {
		return $this->result;
	}

	/**
	 * Send error report quickly
	 *
	 * @since 3.1.7
	 *
	 * @param mixed $exception       Error/Exception object
	 * @param array $additional_data Additional context
	 *
	 * @return bool Success status
	 */
	public static function quick_send( $exception, array $additional_data = array() ): bool {
		$error_data = array_merge(
			array(
				'error_message' => $exception->getMessage(),
				'error_code'    => $exception->getCode(),
				'error_file'    => $exception->getFile(),
				'error_line'    => $exception->getLine(),
				'stack_trace'   => $exception->getTraceAsString(),
				'debug_log'     => self::get_debug_log(),
				'request_data'  => array(
					'method' => $_SERVER['REQUEST_METHOD'] ?? '',
					'uri'    => $_SERVER['REQUEST_URI'] ?? '',
					'ip'     => $_SERVER['REMOTE_ADDR'] ?? '',
				),
			),
			$additional_data
		);

		return ( new self( $error_data ) )->send();
	}

	/**
	 * Get active plugins
	 *
	 * @return string
	 */
	private static function get_active_plugins_list(): string {
		$active_plugins = WP::get_active_plugins();

		return implode( ', ', array_column( $active_plugins, 'name' ) );
	}

	/**
	 * Get the debug log.
	 *
	 * Retrieves the last 50 lines of the WordPress debug log file.
	 *
	 * @since 3.1.7
	 *
	 * @return string The last 50 lines of the debug log or an empty string if the log is not accessible.
	 */
	private static function get_debug_log(): string {
		$debug_log = '';
		$log_file  = WP_CONTENT_DIR . '/debug.log';

		if ( file_exists( $log_file ) && is_readable( $log_file ) ) {
			$debug_log = file_get_contents( $log_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			// Get only the last 100 lines of the debug log
			$debug_log = implode( "\n", array_slice( explode( "\n", $debug_log ), - 100 ) );
		}

		return $debug_log;
	}
}
