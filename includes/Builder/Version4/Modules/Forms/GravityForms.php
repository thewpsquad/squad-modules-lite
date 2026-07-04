<?php // phpcs:ignore WordPress.Files.FileName

/**
 * GravityForms Form Styler Module
 *
 * This file contains the GravityForms class which extends the FormStyler
 * to provide custom styling options for Gravity Forms within the Divi Builder.
 *
 * @since   1.2.0
 * @since   3.2.0 Restructured the module to use the new structure.
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Forms;

use DiviSquad\Builder\Version4\Abstracts\Module\Form_Styler;
use DiviSquad\Utils\Divi as DiviUtil;
use Throwable;

/**
 * GravityForms Form Styler Module Class
 *
 * This class extends the FormStyler to provide custom styling options
 * specifically for Gravity Forms within the Divi Builder interface.
 *
 * @since      1.2.0
 * @package    DiviSquad
 */
class GravityForms extends Form_Styler {

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
		 * Fires before the Gravity Forms module is initialized.
		 *
		 * @since 3.2.0
		 *
		 * @param self $module The current module instance.
		 */
		do_action( 'divi_squad_before_module_gravity_forms_init', $this );

		$this->name      = esc_html__( 'Gravity Forms', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Gravity Forms', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'gravity-forms.svg' );

		$this->slug             = 'disq_form_styler_gravity_forms';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.$this->slug";

		/**
		 * Filters the module icon path.
		 *
		 * @since 3.2.0
		 *
		 * @param string $icon_path The current icon path.
		 * @param self   $module    The current module instance.
		 */
		$this->icon_path = apply_filters( 'divi_squad_module_gravity_forms_icon_path', $this->icon_path, $this );

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		// Get and filter CSS selectors.
		$selectors = $this->squad_get_css_selectors();

		/**
		 * Apply filter to CSS selectors for Gravity Forms styling.
		 *
		 * @since  3.2.0
		 *
		 * @see    squad_get_css_selectors() For the structure of the default selectors array.
		 *
		 * @param array<string, array<string, string>> $selectors The default array of CSS selectors from squad_get_css_selectors().
		 * @param self                                 $module    The GravityForms instance.
		 */
		$this->squad_css_selectors = apply_filters( 'divi_squad_module_gravity_forms_css_selectors', $selectors, $this );

		$this->squad_init_selectors();

		/**
		 * Fires after the Gravity Forms module has been initialized.
		 *
		 * @since 3.2.0
		 *
		 * @param self $module The current module instance.
		 */
		do_action( 'divi_squad_after_gravity_forms_init', $this );
	}

	/**
	 * Get settings modal toggles for the module.
	 *
	 * Defines the structure of settings toggles in the Divi Builder interface.
	 *
	 * @since  1.2.0
	 * @access public
	 *
	 * @return array<string, array<string, mixed>> An array of toggle settings.
	 */
	public function get_settings_modal_toggles(): array {
		$parent_toggles = parent::get_settings_modal_toggles();

		// Remove unnecessary toggles.
		$toggles_to_remove = array(
			'general'  => array( 'field_icons' ),
			'advanced' => array( 'field', 'field_text', 'field_label_text', 'field_description_text', 'field_placeholder_text', 'form_button', 'form_button_text', 'message_error', 'message_error_text', 'message_success', 'message_success_text' ),
		);

		foreach ( $toggles_to_remove as $tab => $toggle_keys ) {
			foreach ( $toggle_keys as $key ) {
				unset( $parent_toggles[ $tab ]['toggles'][ $key ] );
			}
		}

		// Define new advanced toggles.
		$new_advanced_toggles = array(
			'wrapper'            => esc_html__( 'Form', 'squad-modules-for-divi' ),
			'form_title'         => esc_html__( 'Form Title', 'squad-modules-for-divi' ),
			'form_description'   => esc_html__( 'Form Description', 'squad-modules-for-divi' ),
			'form_elements'      => array(
				'title'             => esc_html__( 'Form Elements', 'squad-modules-for-divi' ),
				'tabbed_subtoggles' => true,
				'sub_toggles'       => array(
					'title'       => array( 'name' => esc_html__( 'Title', 'squad-modules-for-divi' ) ),
					'description' => array( 'name' => esc_html__( 'Description', 'squad-modules-for-divi' ) ),
				),
			),
			'field'              => esc_html__( 'Field', 'squad-modules-for-divi' ),
			'field_elements'     => array(
				'title'             => esc_html__( 'Field Elements', 'squad-modules-for-divi' ),
				'tabbed_subtoggles' => true,
				'sub_toggles'       => array(
					'label'       => array( 'name' => esc_html__( 'Label', 'squad-modules-for-divi' ) ),
					'sub_label'   => array( 'name' => esc_html__( 'Sub Label', 'squad-modules-for-divi' ) ),
					'input'       => array( 'name' => esc_html__( 'Input', 'squad-modules-for-divi' ) ),
					'description' => array( 'name' => esc_html__( 'Description', 'squad-modules-for-divi' ) ),
					'placeholder' => array( 'name' => esc_html__( 'Placeholder', 'squad-modules-for-divi' ) ),
				),
			),
			'special_fields'     => array(
				'title'             => esc_html__( 'Special Fields', 'squad-modules-for-divi' ),
				'tabbed_subtoggles' => true,
				'sub_toggles'       => array(
					'section' => array( 'name' => esc_html__( 'Section', 'squad-modules-for-divi' ) ),
					'list'    => array( 'name' => esc_html__( 'List', 'squad-modules-for-divi' ) ),
					'consent' => array( 'name' => esc_html__( 'Consent', 'squad-modules-for-divi' ) ),
				),
			),
			'required_fields'    => array(
				'title'             => esc_html__( 'Required Fields', 'squad-modules-for-divi' ),
				'tabbed_subtoggles' => true,
				'sub_toggles'       => array(
					'wrapper' => array( 'name' => esc_html__( 'Form', 'squad-modules-for-divi' ) ),
					'text'    => array( 'name' => esc_html__( 'Text', 'squad-modules-for-divi' ) ),
					'input'   => array( 'name' => esc_html__( 'Input', 'squad-modules-for-divi' ) ),
					'error'   => array( 'name' => esc_html__( 'Error', 'squad-modules-for-divi' ) ),
				),
			),
			'page_steps_text'    => esc_html__( 'Page Steps Text', 'squad-modules-for-divi' ),
			'form_button'        => esc_html__( 'Submit Button', 'squad-modules-for-divi' ),
			'form_button_text'   => esc_html__( 'Submit Button Text', 'squad-modules-for-divi' ),
			'message_validation' => esc_html__( 'Validation Message', 'squad-modules-for-divi' ),
			'message_success'    => esc_html__( 'Success Message', 'squad-modules-for-divi' ),
			'message_error'      => esc_html__( 'Error Message', 'squad-modules-for-divi' ),
			'messages'           => array(
				'title'             => esc_html__( 'Messages', 'squad-modules-for-divi' ),
				'tabbed_subtoggles' => true,
				'sub_toggles'       => array(
					'validation' => array( 'name' => esc_html__( 'Validation', 'squad-modules-for-divi' ) ),
					'success'    => array( 'name' => esc_html__( 'Success', 'squad-modules-for-divi' ) ),
					'error'      => array( 'name' => esc_html__( 'Error', 'squad-modules-for-divi' ) ),
				),
			),
		);

		/**
		 * Filters the advanced toggles for the Gravity Forms module.
		 *
		 * @since 3.2.0
		 *
		 * @param array $new_advanced_toggles The new advanced toggles.
		 * @param self  $module               The GravityForms instance.
		 */
		$new_advanced_toggles = apply_filters( 'divi_squad_module_gravity_forms_advanced_toggles', $new_advanced_toggles, $this );

		// Merge new toggles with existing advanced toggles.
		$advanced_toggles = array_merge_recursive( $new_advanced_toggles, array_slice( $parent_toggles['advanced']['toggles'], 1 ) );

		return array(
			'general'  => array(
				'toggles' => array(
					'forms' => esc_html__( 'Forms Options', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => $advanced_toggles,
			),
		);
	}

	/**
	 * Get advanced fields configuration for the module.
	 *
	 * Defines the advanced field configurations for the module.
	 *
	 * @since  1.2.0
	 * @access public
	 *
	 * @return array<string, array<string, array<string, array>>> An array of advanced field configurations.
	 */
	public function get_advanced_fields_config(): array {
		$advanced_fields = parent::get_advanced_fields_config();

		// Add font configurations.
		$advanced_fields['fonts'] = $this->squad_get_font_fields();

		// Add border configurations.
		$advanced_fields['borders'] = $this->squad_get_border_fields();

		// Add box shadow configurations.
		$advanced_fields['box_shadow'] = $this->squad_get_box_shadow_fields();

		/**
		 * Filters the advanced fields configuration for the Gravity Forms module.
		 *
		 * @since 3.2.0
		 *
		 * @param array $advanced_fields The advanced fields configuration.
		 * @param self  $module          The GravityForms instance.
		 */
		return apply_filters( 'divi_squad_module_gravity_forms_advanced_fields', $advanced_fields, $this );
	}

	/**
	 * Render module output.
	 *
	 * @since  1.2.0
	 * @access public
	 *
	 * @param array<string, string> $attrs       List of unprocessed attributes.
	 * @param string                $content     Content being processed.
	 * @param string                $render_slug Slug of module that is used for rendering output.
	 *
	 * @return string The HTML output of the module.
	 */
	public function render( $attrs, $content, $render_slug ): string {
		try {
			/**
			 * Fires before the Gravity Forms module is rendered.
			 *
			 * @since 3.2.0
			 *
			 * @param array $attrs  The attributes array.
			 * @param self  $module The Gravity Forms instance.
			 */
			do_action( 'divi_squad_before_module_gravity_forms_render', $attrs, $this );

			// Check if Gravity Forms is installed.
			if ( ! function_exists( 'gravity_form' ) ) {
				$message = esc_html__( 'Gravity Forms is not installed or activated. Please install and activate Gravity Forms to use this module.', 'squad-modules-for-divi' );

				/**
				 * Filter the message displayed when Gravity Forms is not installed.
				 *
				 * @since 3.2.0
				 *
				 * @param string $message The default message.
				 * @param array  $attrs   The attributes array.
				 */
				$message = apply_filters( 'divi_squad_module_gravity_forms_not_activated_message', $message, $attrs );

				return $this->render_notice( $message, 'error' );
			}

			// Generate form HTML..
			$form_html = static::squad_form_styler__get_form_html( $attrs );

			// If no form HTML, return empty string.
			if ( '' === $form_html ) {

				// If no form is selected in Visual Builder, return notice.
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
					$message = apply_filters( 'divi_squad_module_gravity_forms_no_form_message_vb', $message, $attrs );

					return $this->render_notice( $message, 'warning' );
				}

				// If no form is selected in the frontend, return empty string.
				if ( ! DiviUtil::is_fb_enabled() && is_user_logged_in() ) {
					$message = esc_html__( 'No form selected from the Gravity Forms settings.', 'squad-modules-for-divi' );

					/**
					 * Filters the message shown when no form is selected.
					 *
					 * @since 3.2.0
					 *
					 * @param string $message The default message.
					 * @param array  $attrs   The module attributes.
					 */
					$message = apply_filters( 'divi_squad_module_gravity_forms_no_form_message_frontend', $message, $attrs );

					return $this->render_notice( $message, 'warning' );
				}

				/**
				 * Filters the empty form HTML output.
				 *
				 * @since 3.2.0
				 *
				 * @param string $form_html The default empty form HTML.
				 * @param array  $attrs     The module attributes.
				 * @param self   $module    The current module instance.
				 */
				return apply_filters( 'divi_squad_module_gravity_forms_empty_form_html', '', $attrs, $this );
			}

			// Generate styles.
			$this->squad_generate_all_styles( $attrs );

			/**
			 * Filters the Gravity Forms form HTML output.
			 *
			 * @since 3.2.0
			 *
			 * @param string $form_html The form HTML output.
			 * @param array  $attrs     The module attributes.
			 * @param self   $module    The GravityForms instance.
			 */
			return apply_filters( 'divi_squad_module_gravity_forms_form_html', $form_html, $attrs, $this );
		} catch ( Throwable $e ) {
			$this->log_error(
				$e,
				array(
					'attrs'       => $attrs,
					'render_slug' => $render_slug,
					'module'      => 'GravityForms',
					'method'      => 'render',
					'message'     => 'Error occurred while rendering Gravity Forms module',
				)
			);

			return $this->render_error_message();
		}
	}

	/**
	 * Get CSS selectors for the Gravity Forms module.
	 *
	 * This method defines and returns an array of CSS selectors used throughout the module
	 * for styling various elements of Gravity Forms. The selectors are organized into
	 * categories such as form, typography, fields, messages, etc.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @return array<string, array<string, string>> An associative array of CSS selectors.
	 */
	protected function squad_get_css_selectors(): array {
		$css_selectors = array(
			'form'            => array(
				'wrapper' => "$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper",
				'form'    => "$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper form",
			),
			'typography'      => array(
				'title'        => "$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper .gform_title",
				'description'  => "$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper .gform_description",
				'labels'       => array(
					"$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper .gfield_label",
					"$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper legend.gfield_label",
					"$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper .gform-field-label",
				),
				'sub_labels'   => "$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper .gform-field-label--type-sub",
				'placeholders' => array(
					'normal' => array(
						"$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper input::placeholder",
						"$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper select::placeholder",
						"$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper textarea::placeholder",
					),
					'hover'  => array(
						"$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper input:hover::placeholder",
						"$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper select:hover::placeholder",
						"$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper textarea:hover::placeholder",
					),
				),
			),
			'fields'          => array(
				'all'      => array(
					"$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper input[type=\"text\"]",
					"$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper input[type=\"email\"]",
					"$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper input[type=\"number\"]",
					"$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper input[type=\"password\"]",
					"$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper input[type=\"tel\"]",
					"$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper input[type=\"url\"]",
					"$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper select",
					"$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper textarea",
				),
				'input'    => "$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper input",
				'select'   => "$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper select",
				'textarea' => "$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper textarea",
			),
			'complex_fields'  => array(
				'name'       => "$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper .ginput_complex",
				'name_first' => "$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper .name_first",
				'name_last'  => "$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper .name_last",
				'address'    => "$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper .ginput_complex.ginput_container_address",
			),
			'submit_button'   => array(
				'all' => "$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper .gform_button.button",
			),
			'messages'        => array(
				'validation' => "$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper .gform_validation_errors",
				'error'      => array(
					"$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper .gfield_error .gfield_label",
					"$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper .gfield_error .gfield_repeater_cell label",
					"$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper .validation_message",
				),
				'success'    => "$this->main_css_element div .gform_confirmation_wrapper",
			),
			'required_field'  => array(
				'wrapper' => "$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper .gfield.gfield_contains_required",
				'text'    => "$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper .gfield_required.gfield_required_text",
				'input'   => "$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper .gfield_contains_required input, $this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper .gfield_contains_required select, $this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper .gfield_contains_required textarea",
				'error'   => "$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper .gfield_contains_required.gfield_error",
			),
			'description'     => array(
				'field'   => "$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper .gfield_description",
				'section' => "$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper .gsection_description",
			),
			'sections'        => array(
				'wrapper' => "$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper .gsection",
				'title'   => "$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper .gsection_title",
			),
			'list_fields'     => array(
				'wrapper' => "$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper .gfield_list",
				'header'  => "$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper table.gfield_list thead th",
				'cell'    => "$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper table.gfield_list tbody td",
			),
			'complex_buttons' => array(
				'add'    => "$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper .add_list_item",
				'delete' => "$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper .delete_list_item",
			),
			'consent_field'   => array(
				'wrapper' => "$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper .gfield_consent_description",
			),
			'radio_checkbox'  => array(
				'wrapper' => "$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper .gfield_checkbox, $this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper .gfield_radio",
				'item'    => "$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper .gchoice",
				'input'   => "$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper .gfield_checkbox input[type=\"checkbox\"], $this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper .gfield_radio input[type=\"radio\"]",
				'label'   => "$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper .gfield_checkbox label, $this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper .gfield_radio label",
			),
			'page_steps'      => array(
				'wrapper'     => "$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper .gf_page_steps",
				'step'        => "$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper .gf_step",
				'active_step' => "$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper .gf_step_active",
				'step_number' => "$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper .gf_step_number",
				'step_label'  => "$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper .gf_step_label",
			),
			'progress_bar'    => array(
				'wrapper'    => "$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper .gf_progressbar_wrapper",
				'bar'        => "$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper .gf_progressbar",
				'percentage' => "$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper .gf_progressbar_percentage",
				'text'       => "$this->main_css_element div .gform-theme.gform-theme--framework.gform_wrapper .gf_progressbar_percentage span",
			),
		);

		/**
		 * Filter the CSS selectors used for styling various elements of Gravity Forms.
		 *
		 * @since 3.2.0
		 *
		 * @param array  $css_selectors The CSS selectors array
		 * @param string $slug          The module slug
		 */
		return apply_filters( 'divi_squad_module_gravity_forms_css_selectors', $css_selectors, $this->slug );
	}

	/**
	 * Initialize selectors for the form styler.
	 *
	 * Sets up the CSS selectors used for various form elements.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @return void
	 */
	protected function squad_init_selectors(): void {
		$this->form_selector            = $this->squad_get_css_selector_string( 'form.wrapper' );
		$this->field_selector           = $this->squad_get_css_selector_string( 'fields.all' );
		$this->submit_button_selector   = $this->squad_get_css_selector_string( 'submit_button.all' );
		$this->error_message_selector   = $this->squad_get_css_selector_string( 'messages.error' );
		$this->success_message_selector = $this->squad_get_css_selector_string( 'messages.success' );

		/**
		 * Fires after form selectors are initialized.
		 *
		 * @since 3.2.0
		 *
		 * @param self $module The GravityForms instance.
		 */
		do_action( 'divi_squad_module_gravity_forms_after_init_selectors', $this );
	}

	/**
	 * Get general fields for the module.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @return array Array of general fields.
	 */
	protected function squad_get_general_fields(): array {
		return array(
			'form_id'                  => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Form', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Choose the Gravity Form to display.', 'squad-modules-for-divi' ),
					'options'          => divi_squad()->forms_element->get_forms_by( 'gravity_forms' ),
					'computed_affects' => array( '__forms' ),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'forms',
				)
			),
			'form_title__enable'       => $this->squad_get_enable_field( 'title' ),
			'form_description__enable' => $this->squad_get_enable_field( 'description' ),
			'form_with_ajax__enable'   => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Use AJAX On Submit', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Enable AJAX submission for the form.', 'squad-modules-for-divi' ),
					'default_on_front' => 'off',
					'computed_affects' => array( '__forms' ),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'forms',
				)
			),
			'form_messages__enable'    => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Show Error & Success Messages', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Display error and success messages in the Visual Builder.', 'squad-modules-for-divi' ),
					'default_on_front' => 'off',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'forms',
				)
			),
			'__forms'                  => array(
				'type'                => 'computed',
				'computed_callback'   => array( static::class, 'squad_form_styler__get_form_html' ),
				'computed_depends_on' => array( 'form_id', 'form_title__enable', 'form_description__enable', 'form_with_ajax__enable' ),
			),
		);
	}

	/**
	 * Get removable fields for the module.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @return array
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
	 * Get design fields for the module.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @return array Array of design fields.
	 */
	protected function squad_get_design_fields(): array {
		$parent_fields             = parent::squad_get_design_fields();
		$form_title_fields         = $this->squad_get_form_title_fields();
		$form_description_fields   = $this->squad_get_form_description_fields();
		$validation_message_fields = $this->squad_get_validation_message_fields();

		return array_merge_recursive(
			$parent_fields,
			$form_title_fields,
			$form_description_fields,
			$validation_message_fields
		);
	}

	/**
	 * Get form title fields for the module.
	 *
	 * Defines the fields related to the form title styling.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @return array An array of form title field definitions.
	 */
	protected function squad_get_form_title_fields(): array {
		$background_fields     = $this->squad_add_background_field(
			esc_html__( 'Title Background', 'squad-modules-for-divi' ),
			'form_title_background',
			'form_title'
		);
		$margin_padding_fields = $this->squad_get_margin_padding_fields(
			'form_title',
			esc_html__( 'Title', 'squad-modules-for-divi' )
		);

		return array_merge_recursive( $background_fields, $margin_padding_fields );
	}

	/**
	 * Get form description fields for the module.
	 *
	 * Defines the fields related to the form description styling.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @return array An array of form description field definitions.
	 */
	protected function squad_get_form_description_fields(): array {
		$background_fields     = $this->squad_add_background_field(
			esc_html__( 'Description Background', 'squad-modules-for-divi' ),
			'form_description_background',
			'form_description'
		);
		$margin_padding_fields = $this->squad_get_margin_padding_fields(
			'form_description',
			esc_html__( 'Description', 'squad-modules-for-divi' )
		);

		return array_merge_recursive( $background_fields, $margin_padding_fields );
	}

	/**
	 * Get validation message fields for the module.
	 *
	 * Defines the fields related to the validation message styling.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @return array An array of validation message field definitions.
	 */
	protected function squad_get_validation_message_fields(): array {
		$background_fields     = $this->squad_add_background_field(
			esc_html__( 'Validation Message Background', 'squad-modules-for-divi' ),
			'message_validation_background',
			'message_validation'
		);
		$margin_padding_fields = $this->squad_get_margin_padding_fields(
			'message_validation',
			esc_html__( 'Validation Message', 'squad-modules-for-divi' )
		);

		return array_merge_recursive( $background_fields, $margin_padding_fields );
	}

	/**
	 * Get enable field for title or description.
	 *
	 * Creates a toggle field for enabling/disabling the title or description.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @param string $type Either 'title' or 'description'.
	 *
	 * @return array The field definition array.
	 */
	protected function squad_get_enable_field( string $type ): array {
		$label = ucfirst( $type );

		return divi_squad()->d4_module_helper->add_yes_no_field(
		// translators: Label of toggle field like title and description.
			sprintf( esc_html__( 'Show Form %s', 'squad-modules-for-divi' ), $label ),
			array(
				// translators: Label of toggle field like title and description.
				'description'      => sprintf( esc_html__( 'Choose whether to display the form %s.', 'squad-modules-for-divi' ), strtolower( $label ) ),
				'default_on_front' => 'off',
				'affects'          => $this->squad_get_enable_field_affects( $type ),
				'computed_affects' => array( '__forms' ),
				'tab_slug'         => 'general',
				'toggle_slug'      => 'forms',
			)
		);
	}

	/**
	 * Get affects for enable field.
	 *
	 * Determines which fields are affected by the enable toggle.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @param string $type Either 'title' or 'description'.
	 *
	 * @return array An array of affected field names.
	 */
	protected function squad_get_enable_field_affects( string $type ): array {
		$prefix = "form_{$type}_";

		return array(
			"{$prefix}text",
			"{$prefix}text_font",
			"{$prefix}text_text_color",
			"{$prefix}text_text_align",
			"{$prefix}text_font_size",
			"{$prefix}text_letter_spacing",
			"{$prefix}text_line_height",
			"{$prefix}background_color",
			"{$prefix}margin",
			"{$prefix}padding",
		);
	}

	/**
	 * Get margin and padding fields for a specific element.
	 *
	 * Creates margin and padding fields for the given element.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @param string $prefix Element prefix.
	 * @param string $label  Element label.
	 *
	 * @return array An array of margin and padding field definitions.
	 */
	protected function squad_get_margin_padding_fields( string $prefix, string $label ): array {
		$field_attributes = array(
			'range_settings' => array(
				'min_limit' => '1',
				'min'       => '1',
				'max_limit' => '100',
				'max'       => '100',
				'step'      => '1',
			),
			'tab_slug'       => 'advanced',
			'toggle_slug'    => $prefix,
		);

		return array(
			"{$prefix}_margin"  => divi_squad()->d4_module_helper->add_margin_padding_field(
			// translators: Field Label.
				sprintf( esc_html__( '%s Margin', 'squad-modules-for-divi' ), $label ),
				array_merge( $field_attributes, array( 'type' => 'custom_margin' ) )
			),
			"{$prefix}_padding" => divi_squad()->d4_module_helper->add_margin_padding_field(
			// translators: Field Label.
				sprintf( esc_html__( '%s Padding', 'squad-modules-for-divi' ), $label ),
				array_merge( $field_attributes, array( 'type' => 'custom_padding' ) )
			),
		);
	}

	/**
	 * Get font field configurations
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @return array Font field configurations
	 */
	protected function squad_get_font_fields(): array {
		$font_fields = array(
			'form_title'         => divi_squad()->d4_module_helper->add_font_field(
				esc_html__( 'Form Title', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main'      => $this->squad_get_css_selector_string( 'typography.title' ),
						'important' => 'all',
					),
					'font_size'   => array( 'default' => '26px' ),
					'line_height' => array( 'default' => '1em' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'form_title',
				)
			),
			'form_description'   => divi_squad()->d4_module_helper->add_font_field(
				esc_html__( 'Form Description', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main'      => $this->squad_get_css_selector_string( 'typography.description' ),
						'important' => 'all',
					),
					'font_size'   => array( 'default' => '14px' ),
					'line_height' => array( 'default' => '1.5em' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'form_description',
				)
			),
			'field_label'        => divi_squad()->d4_module_helper->add_font_field(
				esc_html__( 'Field Label', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main'  => $this->squad_get_css_selector_string( 'fields.label' ),
						'hover' => $this->squad_get_hover_selector_string( 'fields.label' ),
					),
					'font_size'   => array( 'default' => '14px' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'field_elements',
					'sub_toggle'  => 'label',
				)
			),
			'field_input'        => divi_squad()->d4_module_helper->add_font_field(
				esc_html__( 'Field Input', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main'  => $this->squad_get_css_selector_string( 'fields.all' ),
						'hover' => $this->squad_get_hover_selector_string( 'fields.all' ),
					),
					'font_size'   => array( 'default' => '14px' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'field_elements',
					'sub_toggle'  => 'input',
				)
			),
			'field_description'  => divi_squad()->d4_module_helper->add_font_field(
				esc_html__( 'Field Description', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main'  => $this->squad_get_css_selector_string( 'typography.description' ),
						'hover' => $this->squad_get_hover_selector_string( 'typography.description' ),
					),
					'font_size'   => array( 'default' => '12px' ),
					'line_height' => array( 'default' => '1.5em' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'field_elements',
					'sub_toggle'  => 'description',
				)
			),
			'field_placeholder'  => divi_squad()->d4_module_helper->add_font_field(
				esc_html__( 'Field Placeholder', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main'  => $this->squad_get_css_selector_string( 'typography.placeholders' ),
						'hover' => $this->squad_get_hover_selector_string( 'typography.placeholders' ),
					),
					'font_size'   => array( 'default' => '14px' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'field_elements',
					'sub_toggle'  => 'placeholder',
				)
			),
			'form_button'        => divi_squad()->d4_module_helper->add_font_field(
				esc_html__( 'Submit Button', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main'  => $this->squad_get_css_selector_string( 'submit_button.all' ),
						'hover' => $this->squad_get_hover_selector_string( 'submit_button.all' ),
					),
					'font_size'   => array( 'default' => '14px' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'form_button',
				)
			),
			'validation_message' => divi_squad()->d4_module_helper->add_font_field(
				esc_html__( 'Validation Message', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main'      => $this->squad_get_css_selector_string( 'messages.validation' ) . ' *',
						'important' => 'all',
					),
					'font_size'   => array( 'default' => '14px' ),
					'line_height' => array( 'default' => '1.5em' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'messages',
					'sub_toggle'  => 'validation',
				)
			),
			'success_message'    => divi_squad()->d4_module_helper->add_font_field(
				esc_html__( 'Success Message', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main'  => $this->squad_get_css_selector_string( 'messages.success' ),
						'hover' => $this->squad_get_hover_selector_string( 'messages.success' ),
					),
					'font_size'   => array( 'default' => '14px' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'messages',
					'sub_toggle'  => 'success',
				)
			),
			'error_message'      => divi_squad()->d4_module_helper->add_font_field(
				esc_html__( 'Error Message', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main'  => $this->squad_get_css_selector_string( 'messages.error' ),
						'hover' => $this->squad_get_hover_selector_string( 'messages.error' ),
					),
					'font_size'   => array( 'default' => '14px' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'messages',
					'sub_toggle'  => 'error',
				)
			),
		);

		/**
		 * Filters the font field configurations.
		 *
		 * @since 3.2.0
		 *
		 * @param array $font_fields The font field configurations
		 * @param self  $module      Current module instance
		 */
		return apply_filters( 'divi_squad_module_gravity_forms_font_fields', $font_fields, $this );
	}

	/**
	 * Get border field configurations
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @return array Border field configurations
	 */
	protected function squad_get_border_fields(): array {
		$border_fields = array(
			'default'            => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			'wrapper'            => divi_squad()->d4_module_helper->add_border_field(
				esc_html__( 'Form', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main'      => array(
							'border_radii'  => $this->squad_get_css_selector_string( 'form.wrapper' ),
							'border_styles' => $this->squad_get_css_selector_string( 'form.wrapper' ),
						),
						'important' => 'all',
					),
					'defaults'    => array(
						'border_radii'  => 'on||||',
						'border_styles' => array(
							'width' => '0px',
							'color' => '#333333',
							'style' => 'solid',
						),
					),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'wrapper',
				)
			),
			'form_title'         => divi_squad()->d4_module_helper->add_border_field(
				esc_html__( 'Title', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main'      => array(
							'border_radii'  => $this->squad_get_css_selector_string( 'typography.title' ),
							'border_styles' => $this->squad_get_css_selector_string( 'typography.title' ),
						),
						'important' => 'all',
					),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'form_title',
				)
			),
			'form_description'   => divi_squad()->d4_module_helper->add_border_field(
				esc_html__( 'Description', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main'      => array(
							'border_radii'  => $this->squad_get_css_selector_string( 'typography.description' ),
							'border_styles' => $this->squad_get_css_selector_string( 'typography.description' ),
						),
						'important' => 'all',
					),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'form_description',
				)
			),
			'field'              => divi_squad()->d4_module_helper->add_border_field(
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
							'color' => '#bbbbbb',
							'style' => 'solid',
						),
					),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'field',
				)
			),
			'form_button'        => divi_squad()->d4_module_helper->add_border_field(
				esc_html__( 'Submit Button', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main'      => array(
							'border_radii'  => $this->squad_get_css_selector_string( 'submit_button.all' ),
							'border_styles' => $this->squad_get_css_selector_string( 'submit_button.all' ),
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
			'message_validation' => divi_squad()->d4_module_helper->add_border_field(
				esc_html__( 'Validation Message', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main'      => array(
							'border_radii'  => $this->squad_get_css_selector_string( 'messages.validation' ),
							'border_styles' => $this->squad_get_css_selector_string( 'messages.validation' ),
						),
						'important' => 'all',
					),
					'defaults'    => array(
						'border_radii'  => 'on||||',
						'border_styles' => array(
							'width' => '1px',
							'color' => '#d87b7b',
							'style' => 'solid',
						),
					),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'message_validation',
				)
			),
			'message_success'    => divi_squad()->d4_module_helper->add_border_field(
				esc_html__( 'Success Message', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main'      => array(
							'border_radii'  => $this->squad_get_css_selector_string( 'messages.success' ),
							'border_styles' => $this->squad_get_css_selector_string( 'messages.success' ),
						),
						'important' => 'all',
					),
					'defaults'    => array(
						'border_radii'  => 'on||||',
						'border_styles' => array(
							'width' => '0px',
							'color' => '#46b450',
							'style' => 'solid',
						),
					),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'message_success',
				)
			),
			'message_error'      => divi_squad()->d4_module_helper->add_border_field(
				esc_html__( 'Error Message', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main'      => array(
							'border_radii'  => $this->squad_get_css_selector_string( 'messages.error' ),
							'border_styles' => $this->squad_get_css_selector_string( 'messages.error' ),
						),
						'important' => 'all',
					),
					'defaults'    => array(
						'border_radii'  => 'on||||',
						'border_styles' => array(
							'width' => '0px',
							'color' => '#dc3232',
							'style' => 'solid',
						),
					),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'message_error',
				)
			),
		);

		/**
		 * Filters the border field configurations.
		 *
		 * @since 3.2.0
		 *
		 * @param array $border_fields The border field configurations
		 * @param self  $module        Current module instance
		 */
		return apply_filters( 'divi_squad_module_gravity_forms_border_fields', $border_fields, $this );
	}

	/**
	 * Get box shadow field configurations
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @return array Box shadow field configurations
	 */
	protected function squad_get_box_shadow_fields(): array {
		$box_shadow_fields = array(
			'default'            => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			'wrapper'            => divi_squad()->d4_module_helper->add_box_shadow_field(
				esc_html__( 'Form Box Shadow', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main' => $this->squad_get_css_selector_string( 'form.wrapper' ),
					),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'wrapper',
				)
			),
			'form_title'         => divi_squad()->d4_module_helper->add_box_shadow_field(
				esc_html__( 'Title Box Shadow', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main' => $this->squad_get_css_selector_string( 'typography.title' ),
					),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'form_title',
				)
			),
			'form_description'   => divi_squad()->d4_module_helper->add_box_shadow_field(
				esc_html__( 'Description Box Shadow', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main' => $this->squad_get_css_selector_string( 'typography.description' ),
					),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'form_description',
				)
			),
			'field'              => divi_squad()->d4_module_helper->add_box_shadow_field(
				esc_html__( 'Field Box Shadow', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main' => $this->squad_get_css_selector_string( 'fields.all' ),
					),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'field',
				)
			),
			'form_button'        => divi_squad()->d4_module_helper->add_box_shadow_field(
				esc_html__( 'Submit Button Box Shadow', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main' => $this->squad_get_css_selector_string( 'submit_button.all' ),
					),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'form_button',
				)
			),
			'message_validation' => divi_squad()->d4_module_helper->add_box_shadow_field(
				esc_html__( 'Validation Message Box Shadow', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main' => $this->squad_get_css_selector_string( 'messages.validation' ),
					),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'message_validation',
				)
			),
			'message_success'    => divi_squad()->d4_module_helper->add_box_shadow_field(
				esc_html__( 'Success Message Box Shadow', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main' => $this->squad_get_css_selector_string( 'messages.success' ),
					),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'message_success',
				)
			),
			'message_error'      => divi_squad()->d4_module_helper->add_box_shadow_field(
				esc_html__( 'Error Message Box Shadow', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main' => $this->squad_get_css_selector_string( 'messages.error' ),
					),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'message_error',
				)
			),
		);

		/**
		 * Filters the box shadow field configurations.
		 *
		 * @since 3.2.0
		 *
		 * @param array $box_shadow_fields The box shadow field configurations
		 * @param self  $module            Current module instance
		 */
		return apply_filters( 'divi_squad_module_gravity_forms_box_shadow_fields', $box_shadow_fields, $this );
	}

	/**
	 * Add transition fields to the provided fields array.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @param array $fields Array of fields to add transition fields to.
	 *
	 * @return void
	 */
	public function squad_add_transition_fields( array &$fields ): void {
		/**
		 * Fires before adding transition fields to the Gravity Forms module.
		 *
		 * @since 3.2.0
		 *
		 * @param array $fields Array of fields to add transition fields to.
		 * @param self  $module Current module instance.
		 */
		do_action( 'divi_squad_module_gravity_forms_before_add_transition_fields', $fields, $this );

		parent::squad_add_transition_fields( $fields );
		$this->squad_add_form_title_transition_fields( $fields );
		$this->squad_add_form_description_transition_fields( $fields );
		$this->squad_add_validation_message_transition_fields( $fields );

		divi_squad()->d4_module_helper->fix_fonts_transition( $fields, 'field_label_text', $this->squad_get_css_selector_string( 'typography.labels' ) );
		divi_squad()->d4_module_helper->fix_fonts_transition( $fields, 'field_sub_label_text', $this->squad_get_css_selector_string( 'typography.sub_labels' ) );
		divi_squad()->d4_module_helper->fix_fonts_transition( $fields, 'field_input_text', $this->squad_get_css_selector_string( 'field_elements.input' ) );
		divi_squad()->d4_module_helper->fix_fonts_transition( $fields, 'field_description_text', $this->squad_get_css_selector_string( 'description.field' ) );
		divi_squad()->d4_module_helper->fix_fonts_transition( $fields, 'field_placeholder_text', $this->squad_get_css_selector_string( 'typography.placeholders' ) );
		divi_squad()->d4_module_helper->fix_fonts_transition( $fields, 'section_title_text', $this->squad_get_css_selector_string( 'sections.title' ) );
		divi_squad()->d4_module_helper->fix_fonts_transition( $fields, 'list_header_text', $this->squad_get_css_selector_string( 'list_fields.header' ) );
		divi_squad()->d4_module_helper->fix_fonts_transition( $fields, 'consent_description_text', $this->squad_get_css_selector_string( 'consent_field.wrapper' ) );
		divi_squad()->d4_module_helper->fix_fonts_transition( $fields, 'required_wrapper', $this->squad_get_css_selector_string( 'required_field.wrapper' ) );
		divi_squad()->d4_module_helper->fix_fonts_transition( $fields, 'required_text', $this->squad_get_css_selector_string( 'required_field.text' ) );
		divi_squad()->d4_module_helper->fix_fonts_transition( $fields, 'required_input', $this->squad_get_css_selector_string( 'required_field.input' ) );
		divi_squad()->d4_module_helper->fix_fonts_transition( $fields, 'required_error', $this->squad_get_css_selector_string( 'required_field.error' ) );
		divi_squad()->d4_module_helper->fix_fonts_transition( $fields, 'page_steps_text', $this->squad_get_css_selector_string( 'page_steps.step' ) );

		/**
		 * Fires after adding transition fields to the Gravity Forms module.
		 *
		 * @since 3.2.0
		 *
		 * @param array $fields Array of fields to add transition fields to.
		 * @param self  $module Current module instance.
		 */
		do_action( 'divi_squad_module_gravity_forms_after_add_transition_fields', $fields, $this );
	}

	/**
	 * Add wrapper transition fields.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @param array $fields Array of fields to add wrapper transition fields to.
	 *
	 * @return void
	 */
	protected function squad_add_form_title_transition_fields( array &$fields ): void {
		$fields['form_title_background_color'] = array( 'background' => $this->squad_get_css_selector_string( 'typography.title' ) );
		$fields['form_title_margin']           = array( 'margin' => $this->squad_get_css_selector_string( 'typography.title' ) );
		$fields['form_title_padding']          = array( 'padding' => $this->squad_get_css_selector_string( 'typography.title' ) );
		divi_squad()->d4_module_helper->fix_fonts_transition( $fields, 'form_title_text', $this->squad_get_css_selector_string( 'typography.title' ) );
		divi_squad()->d4_module_helper->fix_border_transition( $fields, 'form_title', $this->squad_get_css_selector_string( 'typography.title' ) );
		divi_squad()->d4_module_helper->fix_box_shadow_transition( $fields, 'form_title', $this->squad_get_css_selector_string( 'typography.title' ) );
	}

	/**
	 * Add wrapper transition fields.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @param array $fields Array of fields to add wrapper transition fields to.
	 *
	 * @return void
	 */
	protected function squad_add_form_description_transition_fields( array &$fields ): void {
		$fields['form_description_background_color'] = array( 'background' => $this->squad_get_css_selector_string( 'typography.description' ) );
		$fields['form_description_margin']           = array( 'margin' => $this->squad_get_css_selector_string( 'typography.description' ) );
		$fields['form_description_padding']          = array( 'padding' => $this->squad_get_css_selector_string( 'typography.description' ) );
		divi_squad()->d4_module_helper->fix_fonts_transition( $fields, 'form_description_text', $this->squad_get_css_selector_string( 'typography.description' ) );
		divi_squad()->d4_module_helper->fix_border_transition( $fields, 'form_description', $this->squad_get_css_selector_string( 'typography.description' ) );
		divi_squad()->d4_module_helper->fix_box_shadow_transition( $fields, 'form_description', $this->squad_get_css_selector_string( 'typography.description' ) );
	}

	/**
	 * Add validation message transition fields.
	 *
	 * Adds transition fields for the validation message styling options.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @param array $fields Array of fields to add validation message transition fields to.
	 *
	 * @return void
	 */
	protected function squad_add_validation_message_transition_fields( array &$fields ): void {
		$fields['message_validation_background_color'] = array( 'background' => $this->squad_get_css_selector_string( 'messages.validation' ) );
		$fields['message_validation_margin']           = array( 'margin' => $this->squad_get_css_selector_string( 'messages.validation' ) );
		$fields['message_validation_padding']          = array( 'padding' => $this->squad_get_css_selector_string( 'messages.validation' ) );
		divi_squad()->d4_module_helper->fix_fonts_transition( $fields, 'message_validation_text', $this->squad_get_css_selector_string( 'messages.validation' ) );
		divi_squad()->d4_module_helper->fix_border_transition( $fields, 'message_validation', $this->squad_get_css_selector_string( 'messages.validation' ) );
		divi_squad()->d4_module_helper->fix_box_shadow_transition( $fields, 'message_validation', $this->squad_get_css_selector_string( 'messages.validation' ) );
	}

	/**
	 * Get background option fields for various form elements.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @return array An associative array of form elements and their corresponding CSS selectors.
	 */
	protected function squad_get_background_stylesheet_option_fields(): array {
		$parent_fields = parent::squad_get_background_stylesheet_option_fields();

		$parent_fields['form_title_background']         = $this->squad_get_css_selector_string( 'typography.title' );
		$parent_fields['form_description_background']   = $this->squad_get_css_selector_string( 'typography.description' );
		$parent_fields['message_validation_background'] = $this->squad_get_css_selector_string( 'messages.validation' );

		return $parent_fields;
	}

	/**
	 * Get margin and padding option fields for various form elements.
	 *
	 * This method defines the selectors for applying margin and padding styles
	 * to different components of the form, such as the wrapper, fields, buttons,
	 * and message areas.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @return array An associative array of form elements and their corresponding CSS selectors.
	 */
	protected function squad_get_margin_padding_stylesheet_option_fields(): array {
		$parent_fields = parent::squad_get_margin_padding_stylesheet_option_fields();

		$parent_fields['form_title']         = $this->squad_get_css_selector_string( 'typography.title' );
		$parent_fields['form_description']   = $this->squad_get_css_selector_string( 'typography.description' );
		$parent_fields['validation_message'] = $this->squad_get_css_selector_string( 'messages.validation' );

		return $parent_fields;
	}

	/**
	 * Get the form HTML.
	 *
	 * Retrieves the HTML for the selected Gravity Form.
	 *
	 * @since  1.2.0
	 * @access public
	 * @static
	 *
	 * @param array<string, string> $attrs List of attributes.
	 *
	 * @return string The HTML of the selected form or an empty string if no form is selected.
	 */
	public static function squad_form_styler__get_form_html( array $attrs ): string {
		/**
		 * Filters the form attributes before generating HTML.
		 *
		 * @since 3.2.0
		 *
		 * @param array $attrs The form attributes.
		 */
		$attrs = (array) apply_filters( 'divi_squad_module_gravity_forms_get_form_html_attrs', $attrs );

		if ( ! function_exists( '\gravity_form' ) || '' === $attrs['form_id'] || divi_squad()->forms_element::DEFAULT_FORM_ID === $attrs['form_id'] ) {
			return '';
		}

		$form_id_hash = $attrs['form_id'];
		$form_id_raw  = divi_squad()->memory->get( "form_id_original_$form_id_hash", '' );

		if ( '' === $form_id_raw ) {
			$collection = divi_squad()->forms_element->get_forms_by( 'gravity_forms', 'id' );

			/**
			 * Filters the form collection before generating HTML.
			 *
			 * @since 3.2.0
			 *
			 * @param array $collection The form collection.
			 * @param array $attrs      The form attributes.
			 */
			$collection = (array) apply_filters( 'divi_squad_module_gravity_forms_collection', $collection, $attrs );

			if ( ! isset( $collection[ $attrs['form_id'] ] ) ) {
				return '';
			}

			$form_id_raw = $collection[ $attrs['form_id'] ];
			divi_squad()->memory->set( "form_id_original_$form_id_hash", $form_id_raw );
			divi_squad()->memory->sync_data();
		}

		$form_title       = isset( $attrs['form_title__enable'] ) && 'on' === $attrs['form_title__enable'];
		$form_description = isset( $attrs['form_description__enable'] ) && 'on' === $attrs['form_description__enable'];
		$form_ajax        = isset( $attrs['form_with_ajax__enable'] ) && 'on' === $attrs['form_with_ajax__enable'];

		$html = (string) \gravity_form( $form_id_raw, $form_title, $form_description, false, null, $form_ajax, 0, false );

		/**
		 * Filters the processed form HTML.
		 *
		 * @since 3.2.0
		 *
		 * @param string $html        The processed form HTML.
		 * @param int    $form_id_raw The form ID.
		 * @param array  $attrs       The form attributes.
		 */
		return apply_filters( 'divi_squad_module_gravity_forms_processed_form_html', $html, $form_id_raw, $attrs );
	}
}
