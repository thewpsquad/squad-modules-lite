<?php // phpcs:ignore WordPress.Files.FileName

/**
 * WPForms Form Styler Module Class
 *
 * This file contains the WPForms class which extends the FormStyler
 * to provide custom styling options for WPForms within the Divi Builder.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   1.2.0
 * @since   3.2.0 Restructured the module to use the new structure.
 */

namespace DiviSquad\Modules\Forms;

use DiviSquad\Base\DiviBuilder\Module\FormStyler;
use DiviSquad\Base\DiviBuilder\Utils;
use DiviSquad\Base\DiviBuilder\Utils\Elements\Forms as FormsUtil;
use DiviSquad\Utils\Divi as DiviUtil;
use DiviSquad\Utils\Helper as HelperUtil;
use Exception;
use Throwable;

/**
 * WPForms Form Styler Module Class
 *
 * Extends the FormStyler base class to provide specific styling and functionality
 * for WPForms within the Divi Builder interface.
 *
 * @since      1.2.0
 * @package    DiviSquad
 * @subpackage Modules\FormStyler
 */
class WPForms extends FormStyler {

	/**
	 * Module initialization.
	 *
	 * Sets up the module name, slug, and other initial properties.
	 * Also initializes the selectors used throughout the module.
	 *
	 * @since  1.2.0
	 * @access public
	 *
	 * @return void
	 */
	public function init(): void {
		/**
		 * Fires before the WPForms module is initialized.
		 *
		 * @since 3.2.0
		 *
		 * @param WPForms $module The current module instance.
		 */
		do_action( 'divi_squad_before_module_wpforms_init', $this );

		$this->name             = esc_html__( 'WP Forms', 'squad-modules-for-divi' );
		$this->plural           = esc_html__( 'WP Forms', 'squad-modules-for-divi' );
		$this->icon_path        = HelperUtil::fix_slash( divi_squad()->get_icon_path() . '/wp-forms.svg' );
		$this->slug             = 'disq_form_styler_wp_forms';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.$this->slug";

		/**
		 * Filters the module icon path.
		 *
		 * @since 3.2.0
		 *
		 * @param string  $icon_path The current icon path.
		 * @param WPForms $module      The current module instance.
		 */
		$this->icon_path = apply_filters( 'divi_squad_module_wpforms_icon_path', $this->icon_path, $this );

		$this->squad_utils = Utils::connect( $this );

		// Get and filter CSS selectors
		$selectors = $this->squad_get_css_selectors();

		/**
		 * Filters the CSS selectors for the WPForms module.
		 *
		 * @since 3.2.0
		 *
		 * @param array   $selectors The default CSS selectors.
		 * @param WPForms $module      The current module instance.
		 *
		 * @see squad_get_css_selectors() For the structure of the default selectors array.
		 */
		$this->squad_css_selectors = apply_filters( 'divi_squad_module_wpforms_css_selectors', $selectors, $this );

		$this->squad_init_selectors();

		/**
		 * Fires after the WPForms module has been initialized.
		 *
		 * @since 3.2.0
		 *
		 * @param WPForms $module The current module instance.
		 */
		do_action( 'divi_squad_after_module_wpforms_init', $this );
	}

	/**
	 * Get settings modal toggles for the module.
	 *
	 * @since  1.2.0
	 * @access public
	 *
	 * @return array Array of toggle settings
	 */
	public function get_settings_modal_toggles(): array {
		$toggles = parent::get_settings_modal_toggles();

		// Add WPForms-specific toggle sections
		$wpforms_toggles = array(
			'field_elements'   => array(
				'title'             => esc_html__( 'Field Elements', 'squad-modules-for-divi' ),
				'priority'          => 45,
				'tabbed_subtoggles' => true,
				'sub_toggles'       => array(
					'label'       => array( 'name' => esc_html__( 'Label', 'squad-modules-for-divi' ) ),
					'sub_label'   => array( 'name' => esc_html__( 'Sub Label', 'squad-modules-for-divi' ) ),
					'input'       => array( 'name' => esc_html__( 'Input', 'squad-modules-for-divi' ) ),
					'placeholder' => array( 'name' => esc_html__( 'Placeholder', 'squad-modules-for-divi' ) ),
				),
			),
			'field_validation' => array(
				'title'    => esc_html__( 'Field Validation', 'squad-modules-for-divi' ),
				'priority' => 50,
			),
		);

		/**
		 * Filters the WPForms-specific toggle sections.
		 *
		 * @since 3.2.0
		 *
		 * @param array $wpforms_toggles The WPForms-specific toggles
		 * @param array $toggles         The base toggles
		 */
		$wpforms_toggles = apply_filters( 'divi_squad_module_wpforms_toggles', $wpforms_toggles, $toggles );

		$toggles['advanced']['toggles'] = array_merge(
			$toggles['advanced']['toggles'],
			$wpforms_toggles
		);

		return $toggles;
	}

	/**
	 * Get advanced fields configuration for the module.
	 *
	 * @since  1.2.0
	 * @access public
	 *
	 * @return array Advanced fields configuration
	 */
	public function get_advanced_fields_config(): array {
		$advanced_fields = parent::get_advanced_fields_config();

		// Add font configurations
		$advanced_fields['fonts'] = $this->squad_get_font_fields();

		// Add border configurations
		$advanced_fields['borders'] = $this->squad_get_border_fields();

		// Add box shadow configurations
		$advanced_fields['box_shadow'] = $this->squad_get_box_shadow_fields();

		/**
		 * Filters the advanced fields configuration for the WPForms module.
		 *
		 * @since 3.2.0
		 *
		 * @param array   $advanced_fields The advanced fields configuration.
		 * @param WPForms $module          The current module instance.
		 */
		return apply_filters( 'divi_squad_module_wpforms_advanced_fields', $advanced_fields, $this );
	}

	/**
	 * Render module output.
	 *
	 * @since  1.2.0
	 * @access public
	 *
	 * @param array  $attrs       List of unprocessed attributes
	 * @param string $content     Content being processed
	 * @param string $render_slug Slug of module that is used for rendering output
	 *
	 * @return string Module's rendered output
	 * @throws Exception If there's an error during rendering.
	 */
	public function render( $attrs, $content, $render_slug ): string {
		try {
			/**
			 * Fires before the WPForms module is rendered.
			 *
			 * @since 3.2.0
			 *
			 * @param array   $attrs  The module attributes.
			 * @param WPForms $module The current module instance.
			 */
			do_action( 'divi_squad_before_module_wpforms_render', $attrs, $this );

			// Check if WPForms is installed
			if ( ! function_exists( 'wpforms' ) ) {
				$message = __( 'WPForms is not installed or activated. Please install and activate WPForms to use this module.', 'squad-modules-for-divi' );

				/**
				 * Filters the message shown when WPForms is not installed.
				 *
				 * @since 3.2.0
				 *
				 * @param string $message The default message.
				 * @param array  $attrs   The module attributes.
				 */
				$message = apply_filters( 'divi_squad_module_wpforms_not_installed_message', $message, $attrs );

				return $this->render_notice( $message, 'error' );
			}

			$form_html = static::squad_form_styler__get_form_html( $attrs );

			// If no form is selected in Visual Builder, return notice
			if ( empty( $form_html ) && DiviUtil::is_fb_enabled() ) {
				$message = __( 'Please select a form.', 'squad-modules-for-divi' );

				/**
				 * Filters the message shown when no form is selected.
				 *
				 * @since 3.2.0
				 *
				 * @param string $message The default message.
				 * @param array  $attrs   The module attributes.
				 */
				$message = apply_filters( 'divi_squad_module_wpforms_no_form_message', $message, $attrs );

				return $this->render_notice( $message, 'warning' );
			}

			// If no form HTML, return empty string
			if ( empty( $form_html ) ) {

				// If no form is selected in Visual Builder, return notice
				if ( DiviUtil::is_fb_enabled() ) {
					$message = esc_html__( 'Please select a form to display.', 'squad-modules-for-divi' );

					/**
					 * Filters the message shown when no form is selected.
					 *
					 * @since 3.2.0
					 *
					 * @param string $message The default message.
					 * @param array  $attrs   The module attributes.
					 */
					$message = apply_filters( 'divi_squad_module_wpforms_no_form_message_vb', $message, $attrs );

					return $this->render_notice( $message, 'warning' );
				}

				// If no form is selected in the frontend, return empty string
				if ( ! DiviUtil::is_fb_enabled() && is_user_logged_in() ) {
					$message = esc_html__( 'No form selected from the WP Forms settings.', 'squad-modules-for-divi' );

					/**
					 * Filters the message shown when no form is selected.
					 *
					 * @since 3.2.0
					 *
					 * @param string $message The default message.
					 * @param array  $attrs   The module attributes.
					 */
					$message = apply_filters( 'divi_squad_module_wpforms_no_form_message_frontend', $message, $attrs );

					return $this->render_notice( $message, 'warning' );
				}

				/**
				 * Filters the empty form HTML output.
				 *
				 * @since 3.2.0
				 *
				 * @param string  $form_html The default empty form HTML.
				 * @param array   $attrs     The module attributes.
				 * @param WPForms $module    The current module instance.
				 */
				return apply_filters( 'divi_squad_module_wpforms_empty_form_html', '', $attrs, $this );
			}

			// Generate styles
			$this->squad_generate_all_styles( $attrs );

			/**
			 * Filters the final HTML output of the WPForms module.
			 *
			 * @since 3.2.0
			 *
			 * @param string  $form_html The form HTML.
			 * @param array   $attrs     The module attributes.
			 * @param WPForms $module    The current module instance.
			 */
			return apply_filters( 'divi_squad_module_wpforms_form_html', $form_html, $attrs, $this );
		} catch ( Throwable $e ) {
			$this->log_error(
				$e,
				array(
					'attrs'       => $attrs,
					'render_slug' => $render_slug,
					'module'      => 'WPForms',
					'method'      => 'render',
					'message'     => 'Error occurred while rendering WPForms module',
				)
			);

			return $this->render_error_message();
		}
	}

	/**
	 * Get CSS selectors for the WPForms module.
	 *
	 * @since  1.2.0
	 * @access protected
	 *
	 * @return array An associative array of CSS selectors.
	 */
	protected function squad_get_css_selectors(): array {
		$css_selectors = array(
			'form'          => array(
				'wrapper' => "$this->main_css_element div.wpforms-container",
				'form'    => "$this->main_css_element div.wpforms-container form.wpforms-form",
			),
			'typography'    => array(
				'labels'       => "$this->main_css_element div.wpforms-container form.wpforms-form .wpforms-field-label",
				'sub-labels'   => "$this->main_css_element div.wpforms-container form.wpforms-form .wpforms-field-sublabel",
				'placeholders' => array(
					'normal' => "$this->main_css_element div.wpforms-container form.wpforms-form input::placeholder, $this->main_css_element div.wpforms-container form.wpforms-form textarea::placeholder",
					'hover'  => "$this->main_css_element div.wpforms-container form.wpforms-form input:hover::placeholder, $this->main_css_element div.wpforms-container form.wpforms-form textarea:hover::placeholder",
				),
			),
			'fields'        => array(
				'all'      => "$this->main_css_element div.wpforms-container form.wpforms-form .wpforms-field input:not([type=radio]):not([type=checkbox]):not([type=submit]):not([type=button]):not([type=image]):not([type=file]), $this->main_css_element div.wpforms-container form.wpforms-form .wpforms-field textarea, $this->main_css_element div.wpforms-container form.wpforms-form .wpforms-field select",
				'input'    => "$this->main_css_element div.wpforms-container form.wpforms-form .wpforms-field input",
				'textarea' => "$this->main_css_element div.wpforms-container form.wpforms-form .wpforms-field textarea",
				'select'   => "$this->main_css_element div.wpforms-container form.wpforms-form .wpforms-field select",
			),
			'submit_button' => array(
				'all' => "$this->main_css_element div.wpforms-container-full .wpforms-submit-container input[type=submit],$this->main_css_element div.wpforms-container-full .wpforms-submit-container button[type=submit],$this->main_css_element div.wpforms-container-full .wpforms-submit-container .wpforms-page-button",
			),
			'messages'      => array(
				'validation' => "$this->main_css_element div.wpforms-container form.wpforms-form .wpforms-error",
				'error'      => "$this->main_css_element div.wpforms-container .wpforms-error-container, $this->main_css_element div.wpforms-container .wpforms-error-container-full",
				'success'    => "$this->main_css_element div.wpforms-container .wpforms-confirmation-container, $this->main_css_element div.wpforms-container .wpforms-confirmation-container-full",
			),
		);

		/**
		 * Filters the CSS selectors for the WPForms module.
		 *
		 * @since 3.2.0
		 *
		 * @param array   $css_selectors The default CSS selectors.
		 * @param WPForms $module        The current module instance.
		 */
		return apply_filters( 'divi_squad_module_wpforms_css_selectors', $css_selectors, $this );
	}

	/**
	 * Initialize selectors for the form styler.
	 *
	 * @since  1.2.0
	 * @access protected
	 *
	 * @return void
	 */
	protected function squad_init_selectors(): void {
		$this->form_selector            = $this->squad_get_css_selector_string( 'form.form' );
		$this->field_selector           = $this->squad_get_css_selector_string( 'fields.all' );
		$this->submit_button_selector   = $this->squad_get_css_selector_string( 'submit_button.all' );
		$this->error_message_selector   = $this->squad_get_css_selector_string( 'messages.error' );
		$this->success_message_selector = $this->squad_get_css_selector_string( 'messages.success' );

		/**
		 * Fires after initializing selectors for the WPForms module.
		 *
		 * @since 3.2.0
		 *
		 * @param WPForms $module The current module instance.
		 */
		do_action( 'divi_squad_module_wpforms_after_init_selectors', $this );
	}

	/**
	 * Get general fields for the module.
	 *
	 * @since  1.2.0
	 * @access protected
	 *
	 * @return array Array of general fields.
	 */
	protected function squad_get_general_fields(): array {
		$fields = array(
			'form_id'               => Utils::add_select_box_field(
				esc_html__( 'Form', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Choose the WPForm to display.', 'squad-modules-for-divi' ),
					'options'          => FormsUtil::get_forms_by( 'wpforms' ),
					'computed_affects' => array( '__forms' ),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'forms',
				)
			),
			'form_messages__enable' => Utils::add_yes_no_field(
				esc_html__( 'Show Error & Success Messages', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Display error and success messages in the Visual Builder.', 'squad-modules-for-divi' ),
					'default_on_front' => 'off',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'forms',
				)
			),
			'__forms'               => array(
				'type'                => 'computed',
				'computed_callback'   => array( static::class, 'squad_form_styler__get_form_html' ),
				'computed_depends_on' => array( 'form_id' ),
			),
		);

		/**
		 * Filters the general fields for the WPForms module.
		 *
		 * @since 3.2.0
		 *
		 * @param array   $fields The default general fields.
		 * @param WPForms $module The current module instance.
		 */
		return apply_filters( 'divi_squad_module_wpforms_general_fields', $fields, $this );
	}

	/**
	 * Get removable fields for the module.
	 *
	 * @since  1.2.0
	 * @access protected
	 *
	 * @return array Array of removable fields.
	 */
	protected function squad_get_removable_fields(): array {
		return array(
			'form_button_text',
			'form_button_icon_type',
			'form_button_icon',
			'form_button_icon_color',
			'form_button_image',
			'form_button_icon_size',
			'form_button_image_width',
			'form_button_image_height',
			'form_button_icon_gap',
			'form_button_icon_placement',
			'form_button_icon_margin',
			'form_button_icon_on_hover',
			'form_button_icon_hover_move_icon',
			'form_button_hover_animation__enable',
			'form_button_hover_animation_type',
			'form_button_custom_width',
			'form_button_width',
			'form_button_elements_alignment',
		);
	}

	/**
	 * Get font field configurations
	 *
	 * @since 3.2.0
	 *
	 * @return array Font field configurations
	 */
	protected function squad_get_font_fields(): array {
		$font_fields = array(
			'field_label_text'     => Utils::add_font_field(
				esc_html__( 'Label', 'squad-modules-for-divi' ),
				array(
					'css'         => array( 'main' => $this->squad_get_css_selector_string( 'typography.labels' ) ),
					'font_size'   => array( 'default' => '14px' ),
					'line_height' => array( 'default' => '1.5em' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'field_elements',
					'sub_toggle'  => 'label',
				)
			),
			'field_sub_label_text' => Utils::add_font_field(
				esc_html__( 'Sub Label', 'squad-modules-for-divi' ),
				array(
					'css'         => array( 'main' => $this->squad_get_css_selector_string( 'typography.sub-labels' ) ),
					'font_size'   => array( 'default' => '14px' ),
					'line_height' => array( 'default' => '1.5em' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'field_elements',
					'sub_toggle'  => 'sub_label',
				)
			),
			'field_text'           => Utils::add_font_field(
				esc_html__( 'Input', 'squad-modules-for-divi' ),
				array(
					'css'         => array( 'main' => $this->squad_get_css_selector_string( 'fields.all' ) ),
					'font_size'   => array( 'default' => '14px' ),
					'line_height' => array( 'default' => '1.5em' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'field_elements',
					'sub_toggle'  => 'input',
				)
			),
			'placeholder_text'     => Utils::add_font_field(
				esc_html__( 'Placeholder', 'squad-modules-for-divi' ),
				array(
					'css'         => array( 'main' => $this->squad_get_css_selector_string( 'typography.placeholders' ) ),
					'font_size'   => array( 'default' => '14px' ),
					'line_height' => array( 'default' => '1.5em' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'field_elements',
					'sub_toggle'  => 'placeholder',
				)
			),
			'message_error_text'   => Utils::add_font_field(
				esc_html__( 'Error Message', 'squad-modules-for-divi' ),
				array(
					'css'         => array( 'main' => $this->squad_get_css_selector_string( 'messages.error' ) ),
					'font_size'   => array( 'default' => '14px' ),
					'line_height' => array( 'default' => '1.5em' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'messages',
					'sub_toggle'  => 'error',
				)
			),
			'message_success_text' => Utils::add_font_field(
				esc_html__( 'Success Message', 'squad-modules-for-divi' ),
				array(
					'css'         => array( 'main' => $this->squad_get_css_selector_string( 'messages.success' ) ),
					'font_size'   => array( 'default' => '14px' ),
					'line_height' => array( 'default' => '1.5em' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'messages',
					'sub_toggle'  => 'success',
				)
			),
		);

		/**
		 * Filters the font field configurations.
		 *
		 * @since 3.2.0
		 *
		 * @param array   $font_fields The font field configurations
		 * @param WPForms $module      Current module instance
		 */
		return apply_filters( 'divi_squad_module_wpforms_font_fields', $font_fields, $this );
	}

	/**
	 * Get border field configurations
	 *
	 * @sinc 3.2.0
	 *
	 * @return array Border field configurations
	 */
	protected function squad_get_border_fields(): array {
		$border_fields = array(
			'default'         => Utils::selectors_default( $this->main_css_element ),
			'wrapper'         => Utils::add_border_field(
				esc_html__( 'Form', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main'      => array(
							'border_radii'  => $this->squad_get_css_selector_string( 'form.wrapper' ),
							'border_styles' => $this->squad_get_css_selector_string( 'form.wrapper' ),
						),
						'important' => 'all',
					),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'wrapper',
				)
			),
			'field'           => Utils::add_border_field(
				esc_html__( 'Field', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main'      => array(
							'border_radii'  => $this->squad_get_css_selector_string( 'fields.all' ),
							'border_styles' => $this->squad_get_css_selector_string( 'fields.all' ),
						),
						'important' => 'all',
					),
					'defaults'    => array(
						'border_radii'  => 'on||||',
						'border_styles' => array(
							'width' => '1px',
							'color' => '#ddd',
							'style' => 'solid',
						),
					),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'field',
				)
			),
			'form_button'     => Utils::add_border_field(
				esc_html__( 'Submit Button', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main'      => array(
							'border_radii'  => $this->submit_button_selector,
							'border_styles' => $this->submit_button_selector,
						),
						'important' => 'all',
					),
					'defaults'    => array(
						'border_radii'  => 'on||||',
						'border_styles' => array(
							'width' => '2px',
							'color' => '#2ea3f2',
							'style' => 'solid',
						),
					),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'form_button',
				)
			),
			'message_error'   => Utils::add_border_field(
				esc_html__( 'Error Message', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main'      => array(
							'border_radii'  => $this->error_message_selector,
							'border_styles' => $this->error_message_selector,
						),
						'important' => 'all',
					),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'messages',
					'sub_toggle'  => 'error',
				)
			),
			'message_success' => Utils::add_border_field(
				esc_html__( 'Success Message', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main'      => array(
							'border_radii'  => $this->success_message_selector,
							'border_styles' => $this->success_message_selector,
						),
						'important' => 'all',
					),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'messages',
					'sub_toggle'  => 'success',
				)
			),
		);

		/**
		 * Filters the border field configurations.
		 *
		 * @since 3.2.0
		 *
		 * @param array   $border_fields The border field configurations
		 * @param WPForms $module        Current module instance
		 */
		return apply_filters( 'divi_squad_module_wpforms_border_fields', $border_fields, $this );
	}

	/**
	 * Get box shadow field configurations
	 *
	 * @since 3.2.0
	 *
	 * @return array Box shadow field configurations
	 */
	protected function squad_get_box_shadow_fields(): array {
		$box_shadow_fields = array(
			'default'         => Utils::selectors_default( $this->main_css_element ),
			'wrapper'         => Utils::add_box_shadow_field(
				esc_html__( 'Form Box Shadow', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main' => $this->squad_get_css_selector_string( 'form.wrapper' ),
					),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'wrapper',
				)
			),
			'field'           => Utils::add_box_shadow_field(
				esc_html__( 'Field Box Shadow', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main' => $this->squad_get_css_selector_string( 'fields.all' ),
					),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'field',
				)
			),
			'form_button'     => Utils::add_box_shadow_field(
				esc_html__( 'Submit Button Box Shadow', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main' => $this->submit_button_selector,
					),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'form_button',
				)
			),
			'message_error'   => Utils::add_box_shadow_field(
				esc_html__( 'Error Message Box Shadow', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main' => $this->error_message_selector,
					),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'messages',
					'sub_toggle'  => 'error',
				)
			),
			'message_success' => Utils::add_box_shadow_field(
				esc_html__( 'Success Message Box Shadow', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main' => $this->success_message_selector,
					),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'messages',
					'sub_toggle'  => 'success',
				)
			),
		);

		/**
		 * Filters the box shadow field configurations.
		 *
		 * @since 3.2.0
		 *
		 * @param array   $box_shadow_fields The box shadow field configurations
		 * @param WPForms $module            Current module instance
		 */
		return apply_filters( 'divi_squad_module_wpforms_box_shadow_fields', $box_shadow_fields, $this );
	}

	/**
	 * Get module stylesheet selectors.
	 *
	 * @since  1.2.0
	 * @access protected
	 *
	 * @param array $attrs List of attributes.
	 *
	 * @return array Array of stylesheet selectors.
	 */
	protected function squad_get_module_stylesheet_selectors( array $attrs ): array {
		$options = parent::squad_get_module_stylesheet_selectors( $attrs );

		// Add custom field width options for WPForms
		$field_selectors        = array( 'input', 'select', '.wpforms-field-row' );
		$medium_field_selectors = array();
		$small_field_selectors  = array();

		foreach ( $field_selectors as $selector ) {
			$medium_field_selectors[] = "$this->main_css_element .wpforms-container $selector.wpforms-field-medium";
			$small_field_selectors[]  = "$this->main_css_element .wpforms-container $selector.wpforms-field-small";
		}

		$options['form_field_width'] = array(
			'type'      => 'default',
			'data_type' => 'range',
			'options'   => array(
				array(
					'selector'     => implode( ', ', $medium_field_selectors ),
					'css_property' => 'max-width',
				),
				array(
					'selector'     => implode( ', ', $small_field_selectors ),
					'css_property' => 'max-width',
				),
			),
		);

		/**
		 * Filters the stylesheet selectors for the WPForms module.
		 *
		 * @since 3.2.0
		 *
		 * @param array   $options The stylesheet selectors.
		 * @param array   $attrs   The module attributes.
		 * @param WPForms $module  The current module instance.
		 */
		return apply_filters( 'divi_squad_module_wpforms_stylesheet_selectors', $options, $attrs, $this );
	}

	/**
	 * Get the form HTML.
	 *
	 * @since  1.2.0
	 * @access public
	 * @static
	 *
	 * @param array $attrs List of module attributes.
	 *
	 * @return string The HTML of the selected form or empty string if no form selected.
	 */
	public static function squad_form_styler__get_form_html( array $attrs ): string {
		/**
		 * Filters the form attributes before generating HTML.
		 *
		 * @since 3.2.0
		 *
		 * @param array $attrs The form attributes.
		 */
		$attrs = (array) apply_filters( 'divi_squad_module_wpforms_get_form_html_attrs', $attrs );

		if ( empty( $attrs['form_id'] ) || FormsUtil::DEFAULT_FORM_ID === $attrs['form_id'] || ! function_exists( 'wpforms' ) ) {
			return '';
		}

		$form_id_hash = $attrs['form_id'];
		$form_id_raw  = divi_squad()->memory->get( "form_id_original_{$form_id_hash}", '' );

		if ( empty( $form_id_raw ) ) {
			$collection = FormsUtil::get_forms_by( 'wpforms', 'id' );

			/**
			 * Filters the forms collection before form selection.
			 *
			 * @since 3.2.0
			 *
			 * @param array $collection The forms collection.
			 * @param array $attrs      The form attributes.
			 */
			$collection = apply_filters( 'divi_squad_module_wpforms_forms_collection', $collection, $attrs );

			if ( ! isset( $collection[ $attrs['form_id'] ] ) ) {
				return '';
			}

			$form_id_raw = $collection[ $attrs['form_id'] ];
			divi_squad()->memory->set( "form_id_original_{$form_id_hash}", $form_id_raw );
			divi_squad()->memory->sync_data();
		}

		$shortcode = sprintf( '[wpforms id="%s"]', esc_attr( $form_id_raw ) );

		/**
		 * Filters the WPForms shortcode before processing.
		 *
		 * @since 3.2.0
		 *
		 * @param string  $shortcode  The form shortcode.
		 * @param int     $form_id    The form ID.
		 * @param array   $attrs      The form attributes.
		 */
		$shortcode = apply_filters( 'divi_squad_module_wpforms_form_shortcode', $shortcode, $form_id_raw, $attrs );

		$html = do_shortcode( $shortcode );

		/**
		 * Filters the form HTML after shortcode processing.
		 *
		 * @since 3.2.0
		 *
		 * @param string $html      The processed form HTML.
		 * @param int    $form_id   The form ID.
		 * @param string $shortcode The form shortcode.
		 * @param array  $attrs     The form attributes.
		 */
		return apply_filters( 'divi_squad_module_wpforms_processed_form_html', $html, $form_id_raw, $shortcode, $attrs );
	}
}
