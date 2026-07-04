<?php // phpcs:ignore WordPress.Files.FileName

/**
 * NinjaForms Form Styler Module
 *
 * This file contains the NinjaForms class which extends the FormStyler base class.
 * It provides functionality to style Ninja Forms within the Divi Builder.
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
use function Ninja_Forms as Ninja_Forms_Plugin;

/**
 * NinjaForms Form Styler Module Class
 *
 * This class extends the FormStyler base class to provide specific styling
 * and functionality for Ninja Forms within the Divi Builder.
 *
 * @since   1.4.7
 * @package DiviSquad
 */
class Ninja_Forms extends Form_Styler {

	/**
	 * Module initialization.
	 *
	 * @since  1.4.7
	 * @access public
	 * @return void
	 */
	public function init(): void {
		/**
		 * Fires before the Ninja Forms module is initialized.
		 *
		 * @since 3.2.0
		 *
		 * @param self $module The instance of the NinjaForms class.
		 */
		do_action( 'divi_squad_before_module_ninja_forms_init', $this );

		$this->name      = esc_html__( 'Ninja Forms', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Ninja Forms', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'ninja-forms.svg' );

		$this->slug             = 'disq_form_styler_ninja_forms';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.$this->slug.et_pb_module";

		/**
		 * Filter the icon path for the Ninja Forms module.
		 *
		 * @since 3.2.0
		 *
		 * @param string $icon_path The default icon path.
		 * @param self   $module    The instance of the NinjaForms class.
		 */
		$this->icon_path = apply_filters( 'divi_squad_module_ninja_forms_icon_path', $this->icon_path, $this );

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		// Get and filter CSS selectors.
		$selectors = $this->squad_get_css_selectors();

		/**
		 * Apply filter to CSS selectors for Ninja Forms styling.
		 *
		 * @since 3.2.0
		 *
		 * @see   squad_get_css_selectors() For the structure of the default selectors array.
		 *
		 * @param array<string, array<string, string>> $selectors The default array of CSS selectors from squad_get_css_selectors().
		 * @param self                                 $module    The instance of the NinjaForms class.
		 */
		$this->squad_css_selectors = apply_filters( 'divi_squad_module_ninja_forms_css_selectors', $selectors, $this );

		$this->squad_init_selectors();

		/**
		 * Fires after the Ninja Forms module is initialized.
		 *
		 * @since 3.2.0
		 *
		 * @param self $module The instance of the NinjaForms class.
		 */
		do_action( 'divi_squad_after_module_ninja_forms_init', $this );
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
				if ( isset( $parent_toggles[ $tab ]['toggles'][ $key ] ) ) {
					unset( $parent_toggles[ $tab ]['toggles'][ $key ] );
				}
			}
		}

		// Define new advanced toggles.
		$new_advanced_toggles = array(
			'wrapper'            => esc_html__( 'Form', 'squad-modules-for-divi' ),
			'form_title'         => esc_html__( 'Form Title', 'squad-modules-for-divi' ),
			'form_title_text'    => esc_html__( 'Form Title Text', 'squad-modules-for-divi' ),
			'field'              => esc_html__( 'Field', 'squad-modules-for-divi' ),
			'field_elements'     => array(
				'title'             => esc_html__( 'Field Elements Text', 'squad-modules-for-divi' ),
				'tabbed_subtoggles' => true,
				'sub_toggles'       => array(
					'label'       => array( 'name' => esc_html__( 'Label', 'squad-modules-for-divi' ) ),
					'input'       => array( 'name' => esc_html__( 'Input', 'squad-modules-for-divi' ) ),
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
		 * Filter the advanced toggles for the Ninja Forms module.
		 *
		 * @since 3.2.0
		 *
		 * @param array $new_advanced_toggles The new advanced toggles array.
		 * @param self  $module               The instance of the NinjaForms class.
		 */
		$new_advanced_toggles = apply_filters( 'divi_squad_module_ninja_forms_advanced_toggles', $new_advanced_toggles, $this );

		// Merge new toggles with existing advanced toggles.
		$advanced_toggles = array_merge_recursive(
			$new_advanced_toggles,
			array_slice( $parent_toggles['advanced']['toggles'], 1 )
		);

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
		 * Filter the advanced fields for the Ninja Forms module.
		 *
		 * @since 3.2.0
		 *
		 * @param array $advanced_fields The advanced fields array.
		 * @param self  $module          The instance of the NinjaForms class.
		 */
		return apply_filters( 'divi_squad_module_ninja_forms_advanced_fields', $advanced_fields, $this );
	}

	/**
	 * Render module output.
	 *
	 * @since  1.4.7
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
			 * Fires before the Ninja Forms module is rendered.
			 *
			 * @since 3.2.0
			 *
			 * @param array $attrs  The attributes array.
			 * @param self  $module The Ninja Forms instance.
			 */
			do_action( 'divi_squad_before_module_gravity_forms_render', $attrs, $this );

			if ( ! function_exists( 'Ninja_Forms_Plugin' ) ) {
				$message = esc_html__( 'Ninja Forms is not installed or activated. Please install Ninja Forms to use this module.', 'squad-modules-for-divi' );

				/**
				 * Filters the message displayed when Ninja Forms is not installed.
				 *
				 * @since 3.2.0
				 *
				 * @param string $message The default message.
				 * @param array  $attrs   The attributes array.
				 */
				$message = apply_filters( 'divi_squad_module_ninja_forms_not_activated_message', $message, $attrs );

				return $this->render_notice( $message, 'error' );
			}

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
					$message = apply_filters( 'divi_squad_module_ninja_forms_no_form_message_vb', $message, $attrs );

					return $this->render_notice( $message, 'warning' );
				}

				// If no form is selected in the frontend, return empty string.
				if ( ! DiviUtil::is_fb_enabled() && is_user_logged_in() ) {
					$message = esc_html__( 'No form selected from the Ninja Forms settings.', 'squad-modules-for-divi' );

					/**
					 * Filters the message shown when no form is selected.
					 *
					 * @since 3.2.0
					 *
					 * @param string $message The default message.
					 * @param array  $attrs   The module attributes.
					 */
					$message = apply_filters( 'divi_squad_module_ninja_forms_no_form_message_frontend', $message, $attrs );

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
				return apply_filters( 'divi_squad_module_ninja_forms_empty_form_html', '', $attrs, $this );
			}

			$this->squad_generate_all_styles( $attrs );

			/**
			 * Filters the form HTML output.
			 *
			 * @since 3.2.0
			 *
			 * @param string $form_html The default form HTML.
			 * @param array  $attrs     The module attributes.
			 * @param self   $module    The current module instance.
			 */
			return apply_filters( 'divi_squad_module_ninja_forms_form_html', $form_html, $attrs, $this );
		} catch ( Throwable $e ) {
			$this->log_error(
				$e,
				array(
					'attrs'       => $attrs,
					'render_slug' => $render_slug,
					'module'      => 'NinjaForms',
					'method'      => 'render',
					'message'     => 'Error occurred while rendering Ninja Forms module',
				)
			);

			return $this->render_error_message();
		}
	}

	/**
	 * Get CSS selectors for the Ninja Forms module.
	 *
	 * @since  1.4.7
	 * @access protected
	 *
	 * @return array<string, array<string, string>> An associative array of CSS selectors.
	 */
	protected function squad_get_css_selectors(): array {
		return array(
			'form'           => array(
				'wrapper' => "$this->main_css_element div .nf-form-cont",
				'form'    => "$this->main_css_element div .nf-form-cont .nf-form-layout form",
			),
			'typography'     => array(
				'title'        => "$this->main_css_element div .nf-form-cont .nf-form-title *",
				'description'  => "$this->main_css_element div .nf-form-cont .nf-form-fields-required",
				'labels'       => "$this->main_css_element div .nf-form-cont .nf-field-label label",
				'sub_labels'   => "$this->main_css_element div .nf-form-cont .nf-field-description",
				'placeholders' => array(
					'normal' => array(
						"$this->main_css_element div .nf-form-cont input::placeholder",
						"$this->main_css_element div .nf-form-cont select::placeholder",
						"$this->main_css_element div .nf-form-cont textarea::placeholder",
					),
					'hover'  => array(
						"$this->main_css_element div .nf-form-cont input:hover::placeholder",
						"$this->main_css_element div .nf-form-cont select:hover::placeholder",
						"$this->main_css_element div .nf-form-cont textarea:hover::placeholder",
					),
				),
			),
			'fields'         => array(
				'all'      => array(
					"$this->main_css_element div .nf-form-cont input[type=\"text\"]",
					"$this->main_css_element div .nf-form-cont input[type=\"email\"]",
					"$this->main_css_element div .nf-form-cont input[type=\"number\"]",
					"$this->main_css_element div .nf-form-cont input[type=\"tel\"]",
					"$this->main_css_element div .nf-form-cont input[type=\"url\"]",
					"$this->main_css_element div .nf-form-cont select",
					"$this->main_css_element div .nf-form-cont textarea",
				),
				'input'    => "$this->main_css_element div .nf-form-cont input",
				'select'   => "$this->main_css_element div .nf-form-cont select",
				'textarea' => "$this->main_css_element div .nf-form-cont textarea",
			),
			'submit_button'  => array(
				'all' => array(
					"$this->main_css_element div .nf-form-cont .submit-container input[type=\"button\"]",
					"$this->main_css_element div .nf-form-cont .submit-container input[type=\"submit\"]",
				),
			),
			'messages'       => array(
				'validation' => "$this->main_css_element div .nf-form-cont .nf-error-wrap .nf-error-msg",
				'error'      => array(
					"$this->main_css_element div .nf-form-cont .nf-error .nf-error-msg",
					"$this->main_css_element div .nf-form-cont .nf-error-field-errors",
				),
				'success'    => "$this->main_css_element div .nf-form-cont .nf-response-msg",
			),
			'required_field' => array(
				'wrapper' => "$this->main_css_element div .nf-form-cont .field-wrap.required",
				'text'    => "$this->main_css_element div .nf-form-cont .ninja-forms-req-symbol",
				'input'   => array(
					"$this->main_css_element div .nf-form-cont .field-wrap.required input",
					"$this->main_css_element div .nf-form-cont .field-wrap.required select",
					"$this->main_css_element div .nf-form-cont .field-wrap.required textarea",
				),
				'error'   => "$this->main_css_element div .nf-form-cont .field-wrap.required.nf-error",
			),
			'radio_checkbox' => array(
				'wrapper' => array(
					"$this->main_css_element div .nf-form-cont .listcheckbox-wrap",
					"$this->main_css_element div .nf-form-cont .listradio-wrap",
				),
				'item'    => "$this->main_css_element div .nf-form-cont .nf-field-element li",
				'input'   => array(
					"$this->main_css_element div .nf-form-cont .checkbox-wrap input[type=\"checkbox\"]",
					"$this->main_css_element div .nf-form-cont .checkbox-wrap input[type=\"checkbox\"]",
				),
				'label'   => array(
					"$this->main_css_element div .nf-form-cont .checkbox-wrap label",
					"$this->main_css_element div .nf-form-cont .listradio-wrap label",
				),
			),
		);
	}

	/**
	 * Initialize selectors for the form styler.
	 *
	 * @since  1.4.7
	 * @access protected
	 * @return void
	 */
	protected function squad_init_selectors(): void {
		$this->form_selector            = $this->squad_get_css_selector_string( 'form.form' );
		$this->field_selector           = $this->squad_get_css_selector_string( 'fields.all' );
		$this->submit_button_selector   = $this->squad_get_css_selector_string( 'submit_button.all' );
		$this->error_message_selector   = $this->squad_get_css_selector_string( 'messages.error' );
		$this->success_message_selector = $this->squad_get_css_selector_string( 'messages.success' );

		/**
		 * Fires after form selectors are initialized.
		 *
		 * @since 3.2.0
		 *
		 * @param array  $squad_css_selectors The CSS selectors array
		 * @param string $slug                The module slug
		 */
		do_action( 'divi_squad_form_styler_after_init_selectors', $this->squad_css_selectors, $this->slug );
	}

	/**
	 * Get removable fields for the module.
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
	 * Get general fields for the module.
	 *
	 * @since  1.4.7
	 * @access protected
	 *
	 * @return array Array of general fields.
	 */
	protected function squad_get_general_fields(): array {
		return array(
			'form_id'               => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Form', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Choose the Ninja Form to display.', 'squad-modules-for-divi' ),
					'options'          => divi_squad()->forms_element->get_forms_by( 'ninja_forms' ),
					'computed_affects' => array( '__forms' ),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'forms',
				)
			),
			'form_messages__enable' => divi_squad()->d4_module_helper->add_yes_no_field(
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
	}

	/**
	 * Get design fields for the module.
	 *
	 * @since  3.2.0
	 * @access protected
	 * @return array Array of design fields.
	 */
	protected function squad_get_design_fields(): array {
		$parent_fields             = parent::squad_get_design_fields();
		$form_title_fields         = $this->squad_get_form_title_fields();
		$validation_message_fields = $this->squad_get_validation_message_fields();

		return array_merge_recursive(
			$parent_fields,
			$form_title_fields,
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
	 * Get validation message fields for the module.
	 *
	 * Defines the fields related to the validation message styling.
	 *
	 * @since  3.2.0
	 * @access protected
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
	 * Get checkbox and radio fields for the module.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @return array Array of checkbox and radio fields.
	 */
	protected function squad_get_additional_design_fields(): array {
		$parent_fields = parent::squad_get_additional_design_fields();

		$checkbox_radio_border_colors = divi_squad()->d4_module_helper->add_color_field(
			esc_html__( 'Checkbox & Radio Active Border Color', 'squad-modules-for-divi' ),
			array(
				'description' => esc_html__( 'Here you can define a custom border color for checkbox and radio fields.', 'squad-modules-for-divi' ),
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'field',
			)
		);

		return array(
			'form_ch_rad_color'        => $parent_fields['form_ch_rad_color'],
			'form_ch_rad_border_color' => $checkbox_radio_border_colors,
			'form_ch_rad_size'         => $parent_fields['form_ch_rad_size'],
		);
	}

	/**
	 * Get additional custom fields for the module.
	 *
	 * @since  1.4.7
	 * @access protected
	 * @return array Array of additional custom fields.
	 */
	protected function squad_get_customizable_design_fields(): array {
		return array(
			'form_field_height'    => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'General Field Height', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Set the height of general form fields.', 'squad-modules-for-divi' ),
					'range_settings' => array(
						'min'  => '1',
						'max'  => '200',
						'step' => '1',
					),
					'default'        => '50px',
					'default_unit'   => 'px',
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'field',
				)
			),
			'form_textarea_height' => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Textarea Field Height', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Set the height of textarea fields.', 'squad-modules-for-divi' ),
					'range_settings' => array(
						'min'  => '1',
						'max'  => '1000',
						'step' => '1',
					),
					'default'        => '200px',
					'default_unit'   => 'px',
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'field',
				)
			),
			'form_button_height'   => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Button Height', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Set the height of form buttons.', 'squad-modules-for-divi' ),
					'range_settings' => array(
						'min'  => '1',
						'max'  => '200',
						'step' => '1',
					),
					'default'        => '50px',
					'default_unit'   => 'px',
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'form_button',
				)
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
			'form_title'        => divi_squad()->d4_module_helper->add_font_field(
				esc_html__( 'Form Title', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main'      => $this->squad_get_css_selector_string( 'typography.title' ),
						'important' => 'all',
					),
					'font_size'   => array( 'default' => '26px' ),
					'line_height' => array( 'default' => '1em' ),
					'toggle_slug' => 'form_title_text',
				)
			),
			'field_label'       => divi_squad()->d4_module_helper->add_font_field(
				esc_html__( 'Field Label', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main'  => $this->squad_get_css_selector_string( 'fields.label' ),
						'hover' => $this->squad_get_hover_selector_string( 'fields.label' ),
					),
					'font_size'   => array( 'default' => '14px' ),
					'toggle_slug' => 'field_elements',
					'sub_toggle'  => 'label',
				)
			),
			'field_input'       => divi_squad()->d4_module_helper->add_font_field(
				esc_html__( 'Field Input', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main'  => $this->squad_get_css_selector_string( 'fields.all' ),
						'hover' => $this->squad_get_hover_selector_string( 'fields.all' ),
					),
					'font_size'   => array( 'default' => '14px' ),
					'toggle_slug' => 'field_elements',
					'sub_toggle'  => 'input',
				)
			),
			'field_description' => divi_squad()->d4_module_helper->add_font_field(
				esc_html__( 'Field Description', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main'  => $this->squad_get_css_selector_string( 'typography.description' ),
						'hover' => $this->squad_get_hover_selector_string( 'typography.description' ),
					),
					'font_size'   => array( 'default' => '12px' ),
					'line_height' => array( 'default' => '1.5em' ),
					'toggle_slug' => 'field_elements',
					'sub_toggle'  => 'description',
				)
			),
			'field_placeholder' => divi_squad()->d4_module_helper->add_font_field(
				esc_html__( 'Field Placeholder', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main'  => $this->squad_get_css_selector_string( 'typography.placeholders' ),
						'hover' => $this->squad_get_hover_selector_string( 'typography.placeholders' ),
					),
					'font_size'   => array( 'default' => '14px' ),
					'toggle_slug' => 'field_elements',
					'sub_toggle'  => 'placeholder',
				)
			),
			'form_button'       => divi_squad()->d4_module_helper->add_font_field(
				esc_html__( 'Submit Button', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main'  => $this->squad_get_css_selector_string( 'submit_button.all' ),
						'hover' => $this->squad_get_hover_selector_string( 'submit_button.all' ),
					),
					'font_size'   => array( 'default' => '14px' ),
					'toggle_slug' => 'form_button_text',
				)
			),
			'validation'        => divi_squad()->d4_module_helper->add_font_field(
				esc_html__( 'Validation', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main'  => $this->squad_get_css_selector_string( 'messages.validation' ),
						'hover' => $this->squad_get_hover_selector_string( 'messages.validation' ),
					),
					'font_size'   => array( 'default' => '0.9em' ),
					'toggle_slug' => 'messages',
					'sub_toggle'  => 'validation',
				)
			),
			'error_message'     => divi_squad()->d4_module_helper->add_font_field(
				esc_html__( 'Error Message', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main'  => $this->squad_get_css_selector_string( 'messages.error' ),
						'hover' => $this->squad_get_hover_selector_string( 'messages.error' ),
					),
					'font_size'   => array( 'default' => '14px' ),
					'toggle_slug' => 'messages',
					'sub_toggle'  => 'error',
				)
			),
			'success_message'   => divi_squad()->d4_module_helper->add_font_field(
				esc_html__( 'Success Message', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main'  => $this->squad_get_css_selector_string( 'messages.success' ),
						'hover' => $this->squad_get_hover_selector_string( 'messages.success' ),
					),
					'font_size'   => array( 'default' => '14px' ),
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
		 * @param array $font_fields The font field configurations
		 * @param self  $module      Current module instance
		 */
		return apply_filters( 'divi_squad_module_ninja_forms_font_fields', $font_fields, $this );
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
							'width' => '0px',
							'color' => '#46b450',
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
		return apply_filters( 'divi_squad_module_ninja_forms_border_fields', $border_fields, $this );
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
				esc_html__( 'Success Message Box Shadow', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main' => $this->squad_get_css_selector_string( 'messages.success' ),
					),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'message_success',
				)
			),
			'message_success'    => divi_squad()->d4_module_helper->add_box_shadow_field(
				esc_html__( 'Validation Message Box Shadow', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main' => $this->squad_get_css_selector_string( 'messages.validation' ),
					),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'message_validation',
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
		return apply_filters( 'divi_squad_module_ninja_forms_box_shadow_fields', $box_shadow_fields, $this );
	}

	/**
	 * Add transition fields to the provided fields array.
	 *
	 * @since  1.4.7
	 * @access protected
	 *
	 * @param array $fields Array of fields to add transition fields to.
	 *
	 * @return void
	 */
	protected function squad_add_transition_fields( array &$fields ): void {
		parent::squad_add_transition_fields( $fields );
		$this->squad_add_form_title_transition_fields( $fields );
		$this->squad_add_validation_message_transition_fields( $fields );

		$fields['form_field_height']    = array(
			'height'      => $this->squad_get_css_selector_string( 'fields.all' ),
			'line-height' => $this->squad_get_css_selector_string( 'fields.all' ),
		);
		$fields['form_textarea_height'] = array(
			'height' => $this->squad_get_css_selector_string( 'fields.textarea' ),
		);
		$fields['form_button_height']   = array(
			'height' => $this->squad_get_css_selector_string( 'submit_button.all' ),
		);
		$fields['form_ch_rad_color']    = array(
			'color' => $this->squad_get_css_selector_string( 'radio_checkbox.input' ),
		);
		$fields['form_ch_rad_size']     = array(
			'width'  => $this->squad_get_css_selector_string( 'radio_checkbox.input' ),
			'height' => $this->squad_get_css_selector_string( 'radio_checkbox.input' ),
		);
	}

	/**
	 * Add wrapper transition fields.
	 *
	 * @since  1.0.0
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
	 * Get module stylesheet selectors.
	 *
	 * @since  1.4.7
	 * @access protected
	 *
	 * @param array $attrs List of attributes.
	 *
	 * @return array Array of stylesheet selectors.
	 */
	protected function squad_get_module_stylesheet_selectors( array $attrs ): array {
		$options = parent::squad_get_module_stylesheet_selectors( $attrs );

		$options['form_field_height']    = array(
			'type'      => 'default',
			'data_type' => 'range',
			'options'   => array(
				array(
					'selector'     => $this->squad_get_css_selector_string( 'fields.all' ),
					'css_property' => 'height',
				),
			),
		);
		$options['form_textarea_height'] = array(
			'type'         => 'default',
			'selector'     => $this->squad_get_css_selector_string( 'fields.textarea' ),
			'css_property' => 'height',
			'data_type'    => 'range',
		);
		$options['form_button_height']   = array(
			'type'         => 'default',
			'selector'     => $this->squad_get_css_selector_string( 'submit_button.all' ),
			'css_property' => 'height',
			'data_type'    => 'range',
		);

		return $options;
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
		$parent_fields['message_validation_background'] = $this->squad_get_css_selector_string( 'messages.validation' );

		return $parent_fields;
	}

	/**
	 * Add checkbox and radio options to the stylesheet selectors.
	 *
	 * @since  1.4.7
	 * @access protected
	 *
	 * @param array $options Array of options to add checkbox and radio options to.
	 *
	 * @return void
	 */
	protected function squad_add_checkbox_radio_stylesheet_options( array &$options ): void {
		parent::squad_add_checkbox_radio_stylesheet_options( $options );

		$checkbox_radio_selector = $this->squad_get_css_selector_string( 'radio_checkbox.input' );

		$options['form_ch_rad_color']        = array(
			'type'      => 'default',
			'data_type' => 'text',
			'options'   => array(
				array(
					'selector'       => $checkbox_radio_selector,
					'hover_selector' => $this->squad_get_hover_selector( $checkbox_radio_selector ),
					'css_property'   => 'accent-color',
				),
				array(
					'selector'       => "$this->main_css_element .nf-form-cont .nf-field-element label.nf-checked-label:before",
					'hover_selector' => "$this->main_css_element .nf-form-cont .nf-field-element label.nf-checked-label:hover:before",
					'css_property'   => 'color',
				),
			),
		);
		$options['form_ch_rad_border_color'] = array(
			'type'      => 'default',
			'data_type' => 'text',
			'options'   => array(
				array(
					'selector'       => "$this->main_css_element .nf-form-cont .nf-field-element label.nf-checked-label:before",
					'hover_selector' => "$this->main_css_element .nf-form-cont .nf-field-element label.nf-checked-label:hover:before",
					'css_property'   => 'accent-color',
				),
				array(
					'selector'       => "$this->main_css_element .nf-form-cont .nf-field-element label.nf-checked-label:after",
					'hover_selector' => "$this->main_css_element .nf-form-cont .nf-field-element label.nf-checked-label:hover:after",
					'css_property'   => 'border-color',
				),
			),
		);
		$options['form_ch_rad_size']         = array(
			'type'      => 'default',
			'data_type' => 'range',
			'options'   => array(
				array(
					'selector'       => $checkbox_radio_selector,
					'hover_selector' => $this->squad_get_hover_selector( $checkbox_radio_selector ),
					'css_property'   => 'width',
				),
				array(
					'selector'       => $checkbox_radio_selector,
					'hover_selector' => $this->squad_get_hover_selector( $checkbox_radio_selector ),
					'css_property'   => 'height',
				),
			),
		);
	}

	/**
	 * Get margin and padding option fields for various form elements.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @return array An associative array of form elements and their corresponding CSS selectors.
	 */
	protected function squad_get_margin_padding_stylesheet_option_fields(): array {
		$parent_fields = parent::squad_get_margin_padding_stylesheet_option_fields();

		$parent_fields['form_title'] = $this->squad_get_css_selector_string( 'typography.title' );

		return $parent_fields;
	}

	/**
	 * Get the form HTML.
	 *
	 * @since  1.4.7
	 * @access public
	 * @static
	 *
	 * @param array<string, string> $attrs List of attributes.
	 *
	 * @return string The HTML of the selected form or an empty string if no form is selected.
	 */
	public static function squad_form_styler__get_form_html( array $attrs ): string {
		/**
		 * Filters the Ninja Forms form HTML attributes.
		 *
		 * @since 3.2.0
		 *
		 * @param array $attrs The Ninja Forms form HTML attributes.
		 */
		$attrs = (array) apply_filters( 'divi_squad_module_ninja_forms_get_form_html_attrs', $attrs );

		if ( ! function_exists( 'Ninja_Forms_Plugin' ) || '' === $attrs['form_id'] || divi_squad()->forms_element::DEFAULT_FORM_ID === $attrs['form_id'] ) {
			return '';
		}

		$form_id_hash = $attrs['form_id'];
		$form_id_raw  = divi_squad()->memory->get( "form_id_original_$form_id_hash", '' );

		if ( '' === $form_id_raw ) {
			$collection = divi_squad()->forms_element->get_forms_by( 'ninja_forms', 'id' );

			/**
			 * Filters the form collection before generating HTML.
			 *
			 * @since 3.2.0
			 *
			 * @param array $collection The form collection.
			 * @param array $attrs      The form attributes.
			 */
			$collection = (array) apply_filters( 'divi_squad_module_ninja_forms_collection', $collection, $attrs );

			if ( ! isset( $collection[ $attrs['form_id'] ] ) ) {
				return '';
			}

			$form_id_raw = $collection[ $attrs['form_id'] ];
			divi_squad()->memory->set( "form_id_original_$form_id_hash", $form_id_raw );
			divi_squad()->memory->sync_data();
		}

		$i18n = Ninja_Forms_Plugin()->config( 'i18nFrontEnd' );

		/**
		 * Filters the Ninja Forms i18n strings.
		 *
		 * @since 3.2.0
		 *
		 * @param array $i18n  The Ninja Forms i18n strings.
		 * @param array $attrs The Ninja Forms form HTML attributes.
		 */
		$i18n = (array) apply_filters( 'divi_squad_module_ninja_forms_i18n', $i18n, $attrs );

		ob_start();
		Ninja_Forms_Plugin()->display( $form_id_raw );

		if ( count( $i18n ) > 0 ) {
			printf(
				'<script type="application/json" id="squad-nf-builder-js-i18n">%s</script>',
				wp_json_encode( $i18n )
			);
		}

		$html = (string) ob_get_clean();

		/**
		 * Filters the processed Ninja Forms form HTML.
		 *
		 * @since 3.2.0
		 *
		 * @param string $html    The processed Ninja Forms form HTML.
		 * @param int    $form_id The Ninja Forms form ID.
		 * @param array  $attrs   The Ninja Forms form HTML attributes.
		 */
		return apply_filters( 'divi_squad_module_ninja_forms_processed_form_html', $html, $form_id_raw, $attrs );
	}
}
