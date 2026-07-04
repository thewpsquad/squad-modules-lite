<?php
/**
 * Builder Base Class which help to the all module class
 *
 * @since      1.0.0
 * @deprecated 3.3.3
 * @author     The WP Squad <support@squadmodules.com>
 * @package    DiviSquad
 */

namespace DiviSquad\Base\DiviBuilder;

use DiviSquad\Core\Supports\Links;
use ET_Builder_Module;
use Throwable;

/**
 * Builder Utils class
 *
 * @since      1.0.0
 * @deprecated 3.3.3
 * @package    DiviSquad
 */
#[\AllowDynamicProperties]
abstract class Module extends ET_Builder_Module {
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
	 * @var \DiviSquad\Base\DiviBuilder\Utils\Base
	 */
	public $squad_utils;

	/**
	 * The list of icon eligible element
	 *
	 * @var array
	 */
	protected array $icon_not_eligible_elements = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		// Set default properties.
		$this->folder_name    = 'et_pb_divi_squad_modules';
		$this->module_credits = array(
			'module_uri' => '',
			'author'     => esc_html__( 'Divi Squad', 'squad-modules-for-divi' ),
			'author_uri' => Links::HOME_URL . '?utm_campaign=wporg&utm_source=module_modal&utm_medium=module_author_link',
		);

		// Call parent constructor.
		parent::__construct();
	}

	/**
	 * Get module defined fields + automatically generated fields
	 *
	 * @return array
	 */
	public function get_complete_fields(): array {
		$fields = parent::get_complete_fields();

		$plugin_type = $this instanceof \DiviSquad\Base\DiviBuilder\Module ? 'core' : '';
		$plugin_type = $this instanceof \DiviSquadPro\Base\DiviBuilder\Module ? 'pro' : $plugin_type;

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
	 * Check if an element is eligible for an icon.
	 *
	 * @param string $element
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
	 * @param mixed $error       The exception or error to log.
	 * @param array $context     Additional context for the error.
	 * @param bool  $send_report Whether to send an error report.
	 *
	 * @return void
	 */
	protected function log_error( Throwable $error, array $context = array(), bool $send_report = true ): void {
		// Add module-specific context
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

		// Log the error
		divi_squad()->log_error( $error, $context['message'] ?? '', $send_report, $error_context );
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

		return sprintf(
			'<div class="squad-notice squad-notice--%1$s">%2$s</div>',
			esc_attr( $type ),
			esc_html( $message )
		);
	}
}
