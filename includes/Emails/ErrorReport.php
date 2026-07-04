<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Error Report Email Handler
 *
 * Provides a robust system for sending error reports via email with proper
 * WordPress integration, rate limiting, validation, and comprehensive error handling.
 *
 * @since      3.1.7
 * @package    DiviSquad
 */

namespace DiviSquad\Emails;

use DiviSquad\Utils\Divi;
use DiviSquad\Utils\WP;
use RuntimeException;
use Throwable;
use WP_Error;
use WP_Theme;

/**
 * Error Report Email Handler
 *
 * Provides a robust system for sending error reports via email with proper
 * WordPress integration, rate limiting, validation, and comprehensive error handling.
 *
 * Features:
 * - Configurable rate limiting to prevent email flooding
 * - Data validation and sanitization
 * - HTML email templates with fallback
 * - WordPress filter and action hooks for extensibility
 * - Error handling and logging
 * - Enhanced Divi theme detection reporting
 *
 * @since      3.1.7
 * @since      3.3.3 Added enhanced Divi theme detection reporting
 * @package    DiviSquad\Emails
 * @author     The WP Squad <support@squadmodules.com>
 */
class ErrorReport {
	/**
	 * Support email recipient
	 *
	 * @since 3.1.7
	 * @var string
	 */
	protected string $to = 'support@squadmodules.com';

	/**
	 * Error report data
	 *
	 * @since 3.1.7
	 * @var array<string, mixed>
	 */
	protected array $data;

	/**
	 * WP_Error instance for error handling
	 *
	 * @since 3.1.7
	 * @var WP_Error
	 */
	protected WP_Error $errors;

	/**
	 * Email sending result
	 *
	 * @since 3.1.7
	 * @var bool
	 */
	protected bool $result = false;

	/**
	 * Rate limit key prefix
	 *
	 * @since 3.1.7
	 * @var string
	 */
	protected const RATE_LIMIT_KEY = 'squad_error_report_';

	/**
	 * Rate limit window in seconds (15 minutes)
	 *
	 * @since 3.1.7
	 * @var int
	 */
	protected const RATE_LIMIT_WINDOW = 900;

	/**
	 * Maximum reports per window
	 *
	 * @since 3.1.7
	 * @var int
	 */
	protected const MAX_REPORTS = 5;

	/**
	 * Required data fields
	 *
	 * @since 3.1.7
	 * @var array<string>
	 */
	protected const REQUIRED_FIELDS = array(
		'error_message',
		'error_code',
		'error_file',
		'error_line',
	);

	/**
	 * Initialize error report
	 *
	 * Creates a new error report instance with sanitized data.
	 *
	 * @since 3.1.7
	 *
	 * @param array<string, mixed> $data Error report data.
	 */
	public function __construct( array $data = array() ) {
		$this->data   = $this->sanitize_data( $data );
		$this->errors = new WP_Error();

		/**
		 * Filter the recipient email address for error reports.
		 *
		 * @since 4.0.0
		 *
		 * @param string $to Default recipient email address.
		 */
		$this->to = apply_filters( 'divi_squad_error_report_recipient', $this->to );
	}

	/**
	 * Send error report email with rate limiting and validation
	 *
	 * Processes the error report data, applies rate limiting, validates the data,
	 * and sends the email if all checks pass.
	 *
	 * @since 3.1.7
	 *
	 * @return bool Success status.
	 * @throws RuntimeException If rate limit is exceeded or validation fails.
	 */
	public function send(): bool {
		try {
			/**
			 * Action triggered before sending an error report.
			 *
			 * @since 4.0.0
			 *
			 * @param array    $data   Error report data.
			 * @param WP_Error $errors Current error collection.
			 */
			do_action( 'divi_squad_before_send_error_report', $this->data, $this->errors );

			// Validate rate limit.
			if ( ! $this->check_rate_limit() ) {
				throw new RuntimeException(
					esc_html__( 'Error report rate limit exceeded. Please try again later.', 'squad-modules-for-divi' )
				);
			}

			// Validate data
			if ( ! $this->validate_data() ) {
				$errors = $this->get_error_messages();
				throw new RuntimeException(
					sprintf(
					/* translators: %s: Error messages */
						esc_html__( 'Error report validation failed: %s', 'squad-modules-for-divi' ),
						implode( ', ', $errors )
					)
				);
			}

			// Ensure WordPress mail functions are available
			if ( ! function_exists( 'wp_mail' ) ) {
				require_once ABSPATH . WPINC . '/pluggable.php';
			}

			// Configure email
			$this->add_email_filters();

			// Get email parameters
			$to      = $this->get_recipient();
			$subject = $this->get_email_subject();
			$message = $this->get_email_message_html();
			$headers = $this->get_email_headers();

			/**
			 * Filter the email parameters before sending.
			 *
			 * @since 4.0.0
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
				$this->data
			);

			// Send email
			$this->result = wp_mail(
				$email_params['to'],
				$email_params['subject'],
				$email_params['message'],
				$email_params['headers']
			);

			// Increment rate limit counter on success
			if ( $this->result ) {
				$this->increment_rate_limit();

				/**
				 * Action triggered after successfully sending an error report.
				 *
				 * @since 4.0.0
				 *
				 * @param array<string, mixed> $data Error report data.
				 */
				do_action( 'divi_squad_error_report_sent', $this->data );
			} else {
				/**
				 * Action triggered when error report sending fails.
				 *
				 * @since 4.0.0
				 *
				 * @param array<string, mixed> $data   Error report data.
				 * @param WP_Error             $errors Current error collection.
				 */
				do_action( 'divi_squad_error_report_failed', $this->data, $this->errors );
			}

			return $this->result;

		} catch ( Throwable $e ) {
			$this->errors->add( 'send_failed', $e->getMessage() );

			/**
			 * Action triggered when an exception occurs while sending an error report.
			 *
			 * @since 4.0.0
			 *
			 * @param Throwable $e      The exception that occurred.
			 * @param array     $data   Error report data.
			 * @param WP_Error  $errors Current error collection.
			 */
			do_action( 'divi_squad_error_report_exception', $e, $this->data, $this->errors );

			return false;

		} finally {
			$this->remove_email_filters();
		}
	}

	/**
	 * Get the recipient email address
	 *
	 * @since 4.0.0
	 *
	 * @return string Email recipient.
	 */
	protected function get_recipient(): string {
		return $this->to;
	}

	/**
	 * Validate required data fields
	 *
	 * Ensures all required fields are present in the error report data.
	 *
	 * @since 3.1.7
	 *
	 * @return bool Validation result.
	 */
	protected function validate_data(): bool {
		/**
		 * Filter the required fields for error reports.
		 *
		 * @since 4.0.0
		 *
		 * @param array<string> $required_fields List of required field names.
		 */
		$required_fields = apply_filters( 'divi_squad_error_report_required_fields', static::REQUIRED_FIELDS );

		foreach ( $required_fields as $field ) {
			if ( ! isset( $this->data[ $field ] ) || '' === $this->data[ $field ] ) {
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

		$is_valid = ! $this->errors->has_errors();

		/**
		 * Filter the validation result.
		 *
		 * @since 4.0.0
		 *
		 * @param bool     $is_valid Whether the data is valid.
		 * @param array    $data     Error report data.
		 * @param WP_Error $errors   Current error collection.
		 */
		return apply_filters( 'divi_squad_error_report_validation_result', $is_valid, $this->data, $this->errors );
	}

	/**
	 * Sanitize input data
	 *
	 * Recursively sanitizes all string values in the error report data.
	 *
	 * @since 3.1.7
	 *
	 * @param array<string, mixed> $data Raw input data.
	 *
	 * @return array<string, mixed> Sanitized data.
	 */
	protected function sanitize_data( array $data ): array {
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

		/**
		 * Filter the sanitized error report data.
		 *
		 * @since 4.0.0
		 *
		 * @param array<string, mixed> $sanitized Sanitized data.
		 * @param array<string, mixed> $data      Raw input data.
		 */
		return apply_filters( 'divi_squad_error_report_sanitized_data', $sanitized, $data );
	}

	/**
	 * Get formatted error messages
	 *
	 * Collects all error messages from the WP_Error object and formats them.
	 *
	 * @since 3.1.7
	 *
	 * @return array<string> Formatted error messages.
	 */
	protected function get_error_messages(): array {
		$messages = array();
		$codes    = $this->errors->get_error_codes();

		foreach ( $codes as $code ) {
			$code_messages = $this->errors->get_error_messages( $code );
			if ( count( $code_messages ) > 0 ) {
				foreach ( $code_messages as $message ) {
					if ( is_string( $message ) && '' !== $message ) {
						$messages[] = $message;
					}
				}
			}
		}

		return array_unique( $messages );
	}

	/**
	 * Check if rate limit is exceeded
	 *
	 * Determines if the current site has exceeded the maximum number of error reports
	 * within the rate limit window.
	 *
	 * @since 3.1.7
	 *
	 * @return bool Whether sending is allowed.
	 */
	protected function check_rate_limit(): bool {
		/**
		 * Filter whether to apply rate limiting to error reports.
		 *
		 * @since 4.0.0
		 *
		 * @param bool $apply_rate_limiting Whether to apply rate limiting.
		 */
		$apply_rate_limiting = apply_filters( 'divi_squad_error_report_apply_rate_limiting', true );

		if ( ! $apply_rate_limiting ) {
			return true;
		}

		$count = (int) get_transient( $this->get_rate_limit_key() );

		/**
		 * Filter the maximum number of error reports allowed within the rate limit window.
		 *
		 * @since 4.0.0
		 *
		 * @param int $max_reports Maximum number of reports.
		 */
		$max_reports = apply_filters( 'divi_squad_error_report_max_reports', static::MAX_REPORTS );

		return $count < $max_reports;
	}

	/**
	 * Increment rate limit counter
	 *
	 * Increases the counter for the number of error reports sent within the current window.
	 *
	 * @since 3.1.7
	 */
	protected function increment_rate_limit(): void {
		$key   = $this->get_rate_limit_key();
		$count = (int) get_transient( $key );

		/**
		 * Filter the rate limit window duration in seconds.
		 *
		 * @since 4.0.0
		 *
		 * @param int $window_duration Window duration in seconds.
		 */
		$window_duration = apply_filters( 'divi_squad_error_report_rate_limit_window', static::RATE_LIMIT_WINDOW );

		if ( 0 === $count ) {
			set_transient( $key, 1, $window_duration );
		} else {
			set_transient( $key, $count + 1, $window_duration );
		}
	}

	/**
	 * Get rate limit key for current site
	 *
	 * Generates a unique key for storing the rate limit counter for the current site.
	 *
	 * @since 3.1.7
	 *
	 * @return string Rate limit key.
	 */
	protected function get_rate_limit_key(): string {
		$site_id = get_current_blog_id();

		return static::RATE_LIMIT_KEY . wp_hash( (string) $site_id );
	}

	/**
	 * Add email filters
	 *
	 * Sets up WordPress filters needed for email sending.
	 *
	 * @since 3.1.7
	 */
	protected function add_email_filters(): void {
		add_action( 'wp_mail_failed', array( $this, 'set_failure_errors' ) );
		add_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );

		/**
		 * Action triggered when email filters are being added.
		 *
		 * Use this hook to add additional filters for email customization.
		 *
		 * @since 4.0.0
		 *
		 * @param ErrorReport $error_report Current ErrorReport instance.
		 */
		do_action( 'divi_squad_error_report_add_email_filters', $this );
	}

	/**
	 * Remove email filters
	 *
	 * Cleans up WordPress filters after email sending.
	 *
	 * @since 3.1.7
	 */
	protected function remove_email_filters(): void {
		remove_action( 'wp_mail_failed', array( $this, 'set_failure_errors' ) );
		remove_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );

		/**
		 * Action triggered when email filters are being removed.
		 *
		 * Use this hook to remove additional filters added during email customization.
		 *
		 * @since 4.0.0
		 *
		 * @param ErrorReport $error_report Current ErrorReport instance.
		 */
		do_action( 'divi_squad_error_report_remove_email_filters', $this );
	}

	/**
	 * Generate email subject
	 *
	 * Creates a descriptive subject line for the error report email.
	 *
	 * @since 3.1.7
	 *
	 * @return string Email subject.
	 */
	protected function get_email_subject(): string {
		$site_host     = wp_parse_url( home_url(), PHP_URL_HOST );
		$error_message = $this->data['error_message'] ?? 'unknown error';

		$subject = sprintf(
			'[Error Report][%s]: %s',
			$site_host,
			$error_message
		);

		/**
		 * Filter the error report email subject.
		 *
		 * @since 4.0.0
		 *
		 * @param string               $subject Email subject.
		 * @param array<string, mixed> $data    Error report data.
		 */
		return apply_filters( 'divi_squad_error_report_subject', $subject, $this->data );
	}

	/**
	 * Get email headers
	 *
	 * Builds the headers for the error report email.
	 *
	 * @since 3.1.7
	 *
	 * @return array<string> Email headers.
	 */
	protected function get_email_headers(): array {
		$site_name   = get_bloginfo( 'name' );
		$admin_email = get_bloginfo( 'admin_email' );
		$charset     = get_bloginfo( 'charset' );

		$headers = array(
			sprintf( 'From: %s <%s>', $site_name, $admin_email ),
			'X-Mailer-Type: SquadModules/ErrorReport',
			'Content-Type: text/html; charset=' . $charset,
		);

		/**
		 * Filter the error report email headers.
		 *
		 * @since 4.0.0
		 *
		 * @param array<string>        $headers Email headers.
		 * @param array<string, mixed> $data    Error report data.
		 */
		return apply_filters( 'divi_squad_error_report_headers', $headers, $this->data );
	}

	/**
	 * Generate HTML email content
	 *
	 * Creates the HTML content for the error report email by using a template
	 * or falling back to a simple message if the template is not available.
	 *
	 * @since 3.1.7
	 *
	 * @return string Email HTML content.
	 */
	protected function get_email_message_html(): string {
		// Prepare data for the email.
		$data = array_merge(
			$this->data,
			array(
				'site_url'    => home_url(),
				'site_name'   => get_bloginfo( 'name' ),
				'timestamp'   => current_time( 'mysql' ),
				'environment' => $this->get_environment_info(),
				'charset'     => get_bloginfo( 'charset' ),
			)
		);

		/**
		 * Filter the data used in the error report email template.
		 *
		 * @since 4.0.0
		 *
		 * @param array<string, mixed> $data Prepared data for the email template.
		 */
		$data = apply_filters( 'divi_squad_error_report_template_data', $data );

		/**
		 * Filter the path to the error report email template.
		 *
		 * @since 4.0.0
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
			// Extract variables to make them available in the template.
			extract( $data ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract

			/**
			 * Action fired before including the error report template.
			 *
			 * @since 4.0.0
			 *
			 * @param string               $template Template path.
			 * @param array<string, mixed> $data     Template data.
			 */
			do_action( 'divi_squad_before_error_report_template', $template, $data );

			include $template;

			/**
			 * Action fired after including the error report template.
			 *
			 * @since 4.0.0
			 *
			 * @param string               $template Template path.
			 * @param array<string, mixed> $data     Template data.
			 */
			do_action( 'divi_squad_after_error_report_template', $template, $data );
		} else {
			return $this->generate_fallback_message( $data );
		}

		// Get the output buffer contents and clean the buffer.
		$output = ob_get_clean();
		if ( false === $output ) {
			return $this->generate_fallback_message( $data );
		}

		/**
		 * Filter the generated HTML content for the error report email.
		 *
		 * @since 4.0.0
		 *
		 * @param string               $output Generated HTML content.
		 * @param array<string, mixed> $data   Template data.
		 */
		return apply_filters( 'divi_squad_error_report_html_content', $output, $data );
	}

	/**
	 * Generate fallback message when template is missing
	 *
	 * Creates a simple HTML message when the email template is not available.
	 *
	 * @since 3.1.7
	 *
	 * @param array<string, mixed> $data Template data.
	 *
	 * @return string Fallback HTML message.
	 */
	protected function generate_fallback_message( array $data = array() ): string {
		$data         = count( $data ) > 0 ? $data : $this->data;
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
		 * @since 4.0.0
		 *
		 * @param string               $message Fallback message.
		 * @param array<string, mixed> $data    Template data.
		 */
		return apply_filters( 'divi_squad_error_report_fallback_message', $message, $data );
	}

	/**
	 * Get environment information
	 *
	 * Collects information about the WordPress environment for debugging.
	 *
	 * @since 3.1.7
	 * @since 3.3.3 Added enhanced Divi theme detection information
	 *
	 * @return array<string, mixed> Environment information.
	 */
	protected function get_environment_info(): array {
		// Basic environment information
		$environment = array(
			'php_version'      => PHP_VERSION,
			'wp_version'       => get_bloginfo( 'version' ),
			'plugin_version'   => divi_squad()->get_version_dot(),
			'divi_version'     => Divi::get_builder_version(),
			'divi_mode'        => Divi::get_builder_mode(),
			'memory_limit'     => ini_get( 'memory_limit' ),
			'is_multisite'     => is_multisite() ? 'Yes' : 'No',
			'active_plugins'   => static::get_active_plugins_list(),
			'installed_themes' => static::get_installed_themes_list(),
		);

		// Get current theme information
		$current_theme = wp_get_theme();
		if ( $current_theme instanceof WP_Theme ) {
			$environment['active_theme_name']    = $current_theme->get( 'Name' );
			$environment['active_theme_version'] = $current_theme->get( 'Version' );
			$environment['active_theme_author']  = $current_theme->get( 'Author' );

			// Check if it's a child theme
			$parent_theme = $current_theme->parent();
			if ( $parent_theme instanceof WP_Theme ) {
				$environment['is_child_theme']       = 'Yes';
				$environment['parent_theme_name']    = $parent_theme->get( 'Name' );
				$environment['parent_theme_version'] = $parent_theme->get( 'Version' );
				$environment['parent_theme_author']  = $parent_theme->get( 'Author' );
			} else {
				$environment['is_child_theme'] = 'No';
			}
		}

		// Collect detailed Divi detection information
		$environment = $this->add_divi_detection_info( $environment );

		/**
		 * Filter the environment information included in error reports.
		 *
		 * @since 4.0.0
		 *
		 * @param array<string, mixed> $environment Environment information.
		 */
		return apply_filters( 'divi_squad_error_report_environment_info', $environment );
	}

	/**
	 * Add Divi detection information to environment data
	 *
	 * Collects detailed information about Divi detection methods,
	 * customizations, and theme status to help with debugging.
	 *
	 * @since  3.3.3
	 * @access protected
	 *
	 * @param array<string, mixed> $environment Base environment information.
	 *
	 * @return array<string, mixed> Enhanced environment with Divi detection info.
	 */
	protected function add_divi_detection_info( array $environment ): array {
		// Set detection method placeholder
		$detection_method = 'Unknown';

		// Check for Divi constants (very reliable signal)
		$divi_constants     = array();
		$constants_to_check = array(
			'ET_CORE_VERSION',
			'ET_BUILDER_VERSION',
			'ET_BUILDER_THEME',
			'ET_BUILDER_PLUGIN_VERSION',
			'ET_BUILDER_PLUGIN_DIR',
			'ET_BUILDER_PLUGIN_URI',
			'ET_BUILDER_DIR',
			'ET_BUILDER_URI',
			'ET_BUILDER_LAYOUT_POST_TYPE',
		);

		foreach ( $constants_to_check as $constant ) {
			if ( defined( $constant ) ) {
				$divi_constants[] = $constant;
			}
		}

		if ( count( $divi_constants ) > 0 ) {
			$detection_method              = 'Constants: ' . implode( ', ', $divi_constants );
			$environment['divi_constants'] = $divi_constants;
		}

		// Check for theme modifications
		$current_theme       = wp_get_theme();
		$is_modified         = false;
		$standard_divi_theme = in_array( $current_theme->get( 'Name' ), array( 'Divi', 'Extra' ), true );
		$is_child_theme      = (bool) $current_theme->parent();

		// If it's not a standard Divi/Extra theme and not a direct child theme, it's likely modified
		if ( ! $standard_divi_theme && ! $is_child_theme && Divi::is_any_divi_theme_active() ) {
			$is_modified      = true;
			$detection_method = 'Custom theme with Divi framework';
		}

		// Child theme detection
		if ( $is_child_theme ) {
			$parent = $current_theme->parent();
			if ( $parent && in_array( $parent->get( 'Name' ), array( 'Divi', 'Extra' ), true ) ) {
				$detection_method = 'Child theme of ' . $parent->get( 'Name' );
			}
		}

		// Plugin detection
		if ( Divi::is_divi_builder_plugin_active() ) {
			$detection_method = 'Divi Builder Plugin';

			// Add plugin specific info
			if ( defined( 'ET_BUILDER_PLUGIN_VERSION' ) ) {
				$environment['plugin_specific_version'] = ET_BUILDER_PLUGIN_VERSION;
			}
		}

		// Check for Divi functions
		$divi_functions     = array();
		$functions_to_check = array(
			'et_setup_theme',
			'et_divi_fonts_url',
			'et_pb_is_pagebuilder_used',
			'et_core_is_fb_enabled',
			'et_builder_get_fonts',
			'et_builder_bfb_enabled',
			'et_fb_is_theme_builder_used_on_page',
		);

		foreach ( $functions_to_check as $function ) {
			if ( function_exists( $function ) ) {
				$divi_functions[] = $function;
			}
		}

		if ( ! empty( $divi_functions ) ) {
			$environment['divi_functions'] = $divi_functions;
			if ( empty( $detection_method ) || 'Unknown' === $detection_method ) {
				$detection_method = 'Functions: ' . implode( ', ', array_slice( $divi_functions, 0, 3 ) );
			}
		}

		// Check for directory structure (least reliable but useful as fallback)
		if ( 'Unknown' === $detection_method && $current_theme instanceof WP_Theme ) {
			$theme_dir         = $current_theme->get_stylesheet_directory();
			$directory_markers = array(
				'includes/builder',
				'epanel',
				'core',
				'includes/builder/feature',
				'includes/builder/frontend-builder',
			);

			$found_markers = array();
			foreach ( $directory_markers as $marker ) {
				$path = trailingslashit( $theme_dir ) . $marker;
				if ( file_exists( $path ) ) {
					$found_markers[] = $marker;
				}
			}

			if ( count( $found_markers ) >= 2 ) {
				$detection_method                      = 'Directory structure: ' . implode( ', ', $found_markers );
				$environment['divi_directory_markers'] = $found_markers;
			}
		}

		// Add detection method and modification status to environment
		$environment['divi_detection_method'] = $detection_method;
		$environment['divi_modified']         = $is_modified;

		// Include framework source info
		if ( $is_child_theme && isset( $environment['parent_theme_name'] ) ) {
			$environment['divi_framework_source'] = 'Parent Theme: ' . $environment['parent_theme_name'];
		} elseif ( Divi::is_divi_builder_plugin_active() ) {
			$environment['divi_framework_source'] = 'Divi Builder Plugin';
		} else {
			$environment['divi_framework_source'] = 'Direct Theme';
		}

		// Include divi theme status details
		try {
			$environment['status_details'] = divi_squad()->requirements->get_status();
		} catch ( \Throwable $e ) {
			// If we encounter any errors, just continue without this data
			$environment['requirements_error'] = $e->getMessage();
		}

		return $environment;
	}

	/**
	 * Set HTML content type
	 *
	 * Used as a callback for the wp_mail_content_type filter.
	 *
	 * @since 3.1.7
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
	 * @since 3.1.7
	 *
	 * @param WP_Error $error Mail error.
	 */
	public function set_failure_errors( WP_Error $error ): void {
		$this->errors->merge_from( $error );

		/**
		 * Action triggered when a mail failure occurs.
		 *
		 * @since 4.0.0
		 *
		 * @param WP_Error             $error Current error object.
		 * @param array<string, mixed> $data  Error report data.
		 */
		do_action( 'divi_squad_error_report_mail_failed', $error, $this->data );
	}

	/**
	 * Get error object
	 *
	 * @since 3.1.7
	 *
	 * @return WP_Error Error object.
	 */
	public function get_errors(): WP_Error {
		return $this->errors;
	}

	/**
	 * Get send result
	 *
	 * @since 3.1.7
	 *
	 * @return bool Result.
	 */
	public function get_result(): bool {
		return $this->result;
	}

	/**
	 * Send error report quickly
	 *
	 * Static helper method to quickly send an error report from an exception.
	 *
	 * @since 3.1.7
	 * @since 3.3.3 Added Divi detection context to extra data
	 *
	 * @param mixed                $exception       Error/Exception object.
	 * @param array<string, mixed> $additional_data Additional context.
	 *
	 * @return bool Success status.
	 */
	public static function quick_send( $exception, array $additional_data = array() ): bool {
		if ( ! $exception instanceof Throwable ) {
			return false;
		}

		$error_data = array(
			'error_message' => $exception->getMessage(),
			'error_code'    => $exception->getCode(),
			'error_file'    => $exception->getFile(),
			'error_line'    => $exception->getLine(),
			'stack_trace'   => $exception->getTraceAsString(),
			'debug_log'     => static::get_debug_log(),
			'request_data'  => array(
				'method' => isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '',
				'uri'    => isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '',
				'ip'     => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
			),
		);

		// Add Divi version info if applicable
		$divi_version = Divi::get_builder_version();

		if ( ! empty( $divi_version ) ) {
			$error_data['divi_context'] = array(
				'version'          => $divi_version,
				'is_theme_active'  => class_exists( '\DiviSquad\Utils\Divi' ) && Divi::is_any_divi_theme_active(),
				'is_plugin_active' => class_exists( '\DiviSquad\Utils\Divi' ) ? Divi::is_divi_builder_plugin_active() : false,
			);
		}

		if ( count( $additional_data ) > 0 ) {
			$error_data = array_merge( $error_data, $additional_data );
		}

		/**
		 * Filter the error data before quick sending an error report.
		 *
		 * @since 4.0.0
		 *
		 * @param array<string, mixed> $error_data      Error data to be sent.
		 * @param Throwable            $exception       The exception that triggered the report.
		 * @param array<string, mixed> $additional_data Additional context data provided.
		 */
		$error_data = apply_filters( 'divi_squad_quick_error_report_data', $error_data, $exception, $additional_data );

		return ( new self( $error_data ) )->send();
	}

	/**
	 * Get active themes
	 *
	 * Retrieves a formatted list of active themes for debugging.
	 *
	 * @since 3.1.7
	 * @since 3.3.3 Added indication of Divi-based themes
	 * @return string Comma-separated list of active themes.
	 */
	protected static function get_installed_themes_list(): string {
		$wp_themes = wp_get_themes();

		/**
		 * Filter the list of installed themes included in error reports.
		 *
		 * @since 4.0.0
		 *
		 * @param array<string, \WP_Theme> $wp_themes List of installed themes.
		 */
		$wp_themes = apply_filters( 'divi_squad_error_report_installed_themes', $wp_themes );

		$installed_themes = array();
		foreach ( $wp_themes as $theme ) {
			// Check if the theme is Divi-based
			$is_divi_based = false;
			$name          = $theme->get( 'Name' );

			if ( in_array( $name, array( 'Divi', 'Extra' ), true ) ) {
				$is_divi_based = true;
			}

			// Check for Divi as parent
			$parent = $theme->parent();
			if ( $parent && in_array( $parent->get( 'Name' ), array( 'Divi', 'Extra' ), true ) ) {
				$is_divi_based = true;
			}

			// Check for other Divi markers
			if ( ! $is_divi_based ) {
				$theme_dir = $theme->get_stylesheet_directory();
				if ( file_exists( $theme_dir . '/includes/builder' ) &&
					 ( file_exists( $theme_dir . '/epanel' ) || file_exists( $theme_dir . '/core' ) ) ) {
					$is_divi_based = true;
				}
			}

			$theme_info = sprintf(
				'%s (%s)%s',
				$name,
				$theme->get( 'Version' ),
				$is_divi_based ? ' [Divi-based]' : ''
			);

			$installed_themes[] = $theme_info;
		}

		return implode( ', ', $installed_themes );
	}

	/**
	 * Get active plugins
	 *
	 * Retrieves a formatted list of active plugins for debugging.
	 *
	 * @since 3.1.7
	 * @return string Comma-separated list of active plugins.
	 */
	protected static function get_active_plugins_list(): string {
		$active_plugins = WP::get_active_plugins();

		/**
		 * Filter the list of active plugins included in error reports.
		 *
		 * @since 4.0.0
		 *
		 * @param array<array<string, string>> $active_plugins List of active plugins.
		 */
		$active_plugins = apply_filters( 'divi_squad_error_report_active_plugins', $active_plugins );

		if ( 0 === count( $active_plugins ) ) {
			return '';
		}

		foreach ( $active_plugins as $key => $plugin ) {
			if ( ! isset( $plugin['name'], $plugin['version'] ) ) {
				unset( $active_plugins[ $key ] );
			}

			$active_plugins[ $key ]['name'] = sprintf(
				'%s (%s)',
				$plugin['name'],
				$plugin['version']
			);
		}

		return implode( ', ', array_column( $active_plugins, 'name' ) );
	}

	/**
	 * Get the debug log
	 *
	 * Retrieves the last 100 lines of the WordPress debug log file.
	 *
	 * @since 3.1.7
	 *
	 * @return string The last 100 lines of the debug log or an empty string if the log is not accessible.
	 */
	protected static function get_debug_log(): string {
		$debug_log = '';

		// Default WordPress debug log location
		$log_file = WP_CONTENT_DIR . '/debug.log';

		/**
		 * Filter the debug log file path.
		 *
		 * @since 4.0.0
		 *
		 * @param string $log_file Path to the debug log file.
		 */
		$log_file = apply_filters( 'divi_squad_error_report_debug_log_path', $log_file );

		if ( divi_squad()->get_wp_fs()->exists( $log_file ) && divi_squad()->get_wp_fs()->is_readable( $log_file ) ) {
			$debug_log = file_get_contents( $log_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			if ( false === $debug_log ) {
				return '';
			}

			// Get only the last 100 lines of the debug log
			$debug_log_lines = explode( "\n", $debug_log );

			/**
			 * Filter the number of debug log lines to include in error reports.
			 *
			 * @since 4.0.0
			 *
			 * @param int $line_count Number of lines to include. Default 100.
			 */
			$line_count = apply_filters( 'divi_squad_error_report_debug_log_lines', 100 );

			$debug_log = implode( "\n", array_slice( $debug_log_lines, - $line_count ) );
		}

		return $debug_log;
	}
}
