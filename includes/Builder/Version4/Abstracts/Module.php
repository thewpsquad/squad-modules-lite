<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Abstract Module Class
 *
 * Base implementation for all modules in the DiviSquad Builder.
 *
 * @since   1.5.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Abstracts;

use DiviSquad\Builder\Version4\Contracts\Module_Interface;
use DiviSquad\Builder\Version4\Supports\Module_Utility;
use DiviSquad\Core\Supports\Links;
use ET_Builder_Module;
use Throwable;

/**
 * Abstract Module class
 *
 *  Base implementation for all modules. Extends the ET_Builder_Module
 *  and implements the Module_Interface.
 *
 * @since   1.5.0
 * @package DiviSquad
 *
 * @property array  $props
 * @property string $render_slug
 */
#[\AllowDynamicProperties]
abstract class Module extends ET_Builder_Module implements Module_Interface {
	/**
	 * Stylesheet selector for tooltip container.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public string $tooltip_css_element = '';

	/**
	 * The instance of Utils class
	 *
	 * @var Module_Utility
	 */
	public Module_Utility $squad_utils;

	/**
	 * Default options for divider
	 *
	 * @var array|mixed|string|null
	 */
	public array $squad_divider_defaults;

	/**
	 * Show options for divider
	 *
	 * @var array|mixed|string|null
	 */
	public $squad_divider_show_options;

	/**
	 * The list of icon eligible element
	 *
	 * @var array
	 */
	protected array $icon_not_eligible_elements = array();

	/**
	 * Initialize the module
	 */
	public function __construct() {
		// Set default properties.
		$this->folder_name    = 'et_pb_divi_squad_modules';
		$this->module_credits = array(
			'module_uri' => '',
			'author'     => esc_html__( 'Divi Squad', 'squad-modules-for-divi' ),
			'author_uri' => Links::HOME_URL . '?utm_campaign=wporg&utm_source=module_modal&utm_medium=module_author_link',
		);

		/**
		 * Action triggered before parent constructor is called in module.
		 *
		 * @since 3.3.3
		 *
		 * @param Module $module The module instance.
		 */
		do_action( 'divi_squad_module_before_constructor', $this );

		// Call parent constructor.
		parent::__construct();

		/**
		 * Action triggered after a module is initialized.
		 *
		 * @since 3.3.3
		 *
		 * @param Module $module The module instance.
		 */
		do_action( 'divi_squad_module_after_constructor', $this );
	}

	/**
	 * Get module defined fields + automatically generated fields
	 *
	 * @return array
	 */
	public function get_complete_fields(): array {
		$fields = parent::get_complete_fields();

		$plugin_type = $this->get_module_type();

		// Add _squad_plugin_type field to all modules.
		$fields['_squad_plugin_type'] = array(
			'label'            => 'Plugin Type',
			'type'             => 'skip',
			'default'          => $plugin_type,
			'computed_affects' => array(
				'_squad_plugin_type',
			),
		);

		// Add _squad_core_version field to all modules.
		$fields['_squad_core_version'] = array(
			'label'            => 'Core Version',
			'type'             => 'skip',
			'default'          => divi_squad()->get_version_dot(),
			'computed_affects' => array(
				'_squad_core_version',
			),
		);

		/**
		 * Filter the fields for the module.
		 *
		 * @since 3.3.3
		 *
		 * @param array  $fields The module fields.
		 * @param Module $module The module instance.
		 */
		return apply_filters( 'divi_squad_module_complete_fields', $fields, $this );
	}

	/**
	 * Get the module type.
	 *
	 * @since 3.3.3
	 *
	 * @return string The module type.
	 */
	public function get_module_type(): string {
		$plugin_type = 'core';

		if ( $this instanceof \DiviSquadPro\Builder\Version4\Abstracts\Module || $this instanceof \DiviSquadPro\Base\DiviBuilder\Module ) {
			$plugin_type = 'pro';
		}

		/**
		 * Filter the module type.
		 *
		 * @since 3.3.3
		 *
		 * @param string $plugin_type The module type.
		 * @param Module $module      The module instance.
		 */
		return apply_filters( 'divi_squad_module_type', $plugin_type, $this );
	}

	/**
	 * Check if an element is eligible for an icon.
	 *
	 * @param string $element The element to check.
	 *
	 * @return bool
	 */
	protected function is_icon_eligible( string $element ): bool {
		$is_eligible = ! in_array( $element, $this->icon_not_eligible_elements, true );

		/**
		 * Filter whether an element is eligible for an icon.
		 *
		 * @since 3.3.3
		 *
		 * @param bool   $is_eligible Whether the element is eligible for an icon.
		 * @param string $element     The element.
		 * @param Module $module      The module instance.
		 */
		return (bool) apply_filters( 'divi_squad_is_icon_eligible', $is_eligible, $element, $this );
	}

	/**
	 * Log an error and optionally send an error report.
	 *
	 * @since 3.2.0
	 *
	 * @param Throwable $error       The exception or error to log.
	 * @param array     $context     Additional context for the error.
	 * @param bool      $send_report Whether to send an error report.
	 *
	 * @return void
	 */
	protected function log_error( Throwable $error, array $context = array(), bool $send_report = true ): void {
		// Add module-specific context.
		$error_context = array_merge(
			array(
				'module'         => static::class,
				'module_slug'    => $this->slug ?? '',
				'module_version' => divi_squad()->get_version_dot(),
				'is_vb'          => et_core_is_fb_enabled(),
				'is_bfb'         => et_builder_bfb_enabled(),
				'is_tb'          => et_builder_tb_enabled(),
				'current_screen' => get_current_screen() instanceof \WP_Screen ? get_current_screen()->id : null,
			),
			$context
		);

		/**
		 * Filter the error context before logging.
		 *
		 * @since 3.3.3
		 *
		 * @param array     $error_context The error context.
		 * @param Throwable $error         The exception or error to log.
		 * @param Module    $module        The module instance.
		 */
		$error_context = apply_filters( 'divi_squad_module_error_context', $error_context, $error, $this );

		/**
		 * Action triggered before an error is logged.
		 *
		 * @since 3.3.3
		 *
		 * @param Throwable $error         The exception or error to log.
		 * @param array     $error_context The error context.
		 * @param bool      $send_report   Whether to send an error report.
		 * @param Module    $module        The module instance.
		 */
		do_action( 'divi_squad_module_before_log_error', $error, $error_context, $send_report, $this );

		// Log the error.
		divi_squad()->log_error( $error, $context['message'] ?? '', $send_report, $error_context );

		/**
		 * Action triggered after an error is logged.
		 *
		 * @since 3.3.3
		 *
		 * @param Throwable $error         The exception or error that was logged.
		 * @param array     $error_context The error context.
		 * @param bool      $send_report   Whether an error report was sent.
		 * @param Module    $module        The module instance.
		 */
		do_action( 'divi_squad_module_after_log_error', $error, $error_context, $send_report, $this );
	}

	/**
	 * Render an error message when an exception occurs.
	 *
	 * @return string The HTML for the error message.
	 */
	protected function render_error_message(): string {
		if ( current_user_can( 'manage_options' ) ) {
			$error_message = __( 'An error occurred while rendering this module.', 'squad-modules-for-divi' );

			/**
			 * Filter the error message displayed when an exception occurs.
			 *
			 * @since 3.2.0
			 *
			 * @param string $error_message The error message.
			 * @param Module $module        The module instance.
			 */
			$error_message = apply_filters( 'divi_squad_render_error_message', $error_message, $this );

			return $this->render_notice( $error_message, 'error' );
		}

		/**
		 * Filter the error message displayed when an exception occurs.
		 *
		 * @since 3.2.0
		 *
		 * @param string $error_message The error message.
		 * @param Module $module        The module instance.
		 */
		return (string) apply_filters( 'divi_squad_render_error_message', '', $this );
	}

	/**
	 * Render a notice.
	 *
	 * @since 3.2.0
	 *
	 * @param string $message The message to display.
	 * @param string $type    The type of notice (error, warning, success, info).
	 *
	 * @return string
	 */
	protected function render_notice( string $message, string $type = 'info' ): string {
		/**
		 * Filter the allowed notice types.
		 *
		 * @since 3.3.3
		 *
		 * @param array $allowed_types The allowed notice types.
		 */
		$allowed_types = apply_filters( 'divi_squad_notice_types', array( 'error', 'warning', 'success', 'info' ) );

		$type = in_array( $type, $allowed_types, true ) ? $type : 'info';

		/**
		 * Filter the notice type.
		 *
		 * @since 3.3.3
		 *
		 * @param string $type    The notice type.
		 * @param string $message The notice message.
		 */
		$type = (string) apply_filters( 'divi_squad_notice_type', $type, $message );

		/**
		 * Filter the notice message before rendering.
		 *
		 * @since 3.3.3
		 *
		 * @param string $message The notice message.
		 * @param string $type    The notice type.
		 * @param Module $module  The module instance.
		 */
		$message = (string) apply_filters( 'divi_squad_notice_message', $message, $type, $this );

		/**
		 * Action triggered before a notice is rendered.
		 *
		 * @since 3.3.3
		 *
		 * @param string $message The notice message.
		 * @param string $type    The notice type.
		 * @param Module $module  The module instance.
		 */
		do_action( 'divi_squad_before_render_notice', $message, $type, $this );

		$notice = sprintf(
			'<div class="squad-notice squad-notice--%1$s">%2$s</div>',
			esc_attr( $type ),
			esc_html( $message )
		);

		/**
		 * Filter the rendered notice HTML.
		 *
		 * @since 3.3.3
		 *
		 * @param string $notice  The rendered notice HTML.
		 * @param string $message The notice message.
		 * @param string $type    The notice type.
		 * @param Module $module  The module instance.
		 */
		$notice = apply_filters( 'divi_squad_rendered_notice', $notice, $message, $type, $this );

		/**
		 * Action triggered after a notice is rendered.
		 *
		 * @since 3.3.3
		 *
		 * @param string $notice  The rendered notice HTML.
		 * @param string $message The notice message.
		 * @param string $type    The notice type.
		 * @param Module $module  The module instance.
		 */
		do_action( 'divi_squad_after_render_notice', $notice, $message, $type, $this );

		return $notice;
	}
}
