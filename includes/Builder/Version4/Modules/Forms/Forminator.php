<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Forminator Form Styler Module Class
 *
 * This file contains the Forminator class which extends the FormStyler
 * to provide custom styling options for Forminator Forms within the Divi Builder.
 *
 * @since   1.2.0
 * @since   3.2.0 Restructured the module to use the new structure.
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Forms;

use DiviSquad\Builder\Version4\Abstracts\Module\Form_Styler;
use DiviSquad\Utils\Divi as DiviUtil;
use Exception;
use Throwable;

/**
 * Forminator Form Styler Module Class
 *
 * Extends the FormStyler base class to provide specific styling and functionality
 * for Forminator forms within the Divi Builder interface.
 *
 * @since      1.2.0
 * @package    DiviSquad
 */
class Forminator extends Form_Styler {

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
		 * Fires before the Forminator module is initialized.
		 *
		 * @since 3.2.0
		 *
		 * @param self $module The current module instance.
		 */
		do_action( 'divi_squad_before_module_forminator_init', $this );

		$this->name      = esc_html__( 'Forminator', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Forminator', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'forminator.svg' );

		$this->slug             = 'disq_form_styler_forminator';
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
		$this->icon_path = apply_filters( 'divi_squad_module_forminator_icon_path', $this->icon_path, $this );

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		// Get and filter CSS selectors.
		$selectors = $this->squad_get_css_selectors();

		/**
		 * Filters the CSS selectors for the Forminator module.
		 *
		 * @since 3.2.0
		 *
		 * @param array<string, array<string, string>> $selectors The default CSS selectors.
		 * @param self                                 $module    The current module instance.
		 */
		$this->squad_css_selectors = apply_filters( 'divi_squad_module_forminator_css_selectors', $selectors, $this );

		$this->squad_init_selectors();

		/**
		 * Fires after the Forminator module has been initialized.
		 *
		 * @since 3.2.0
		 *
		 * @param self $module The current module instance.
		 */
		do_action( 'divi_squad_after_forminator_init', $this );
	}

	/**
	 * Get settings modal toggles for the module.
	 *
	 * @since  1.2.0
	 * @access public
	 *
	 * @return array<string, array<string, mixed>> An array of toggle settings
	 */
	public function get_settings_modal_toggles(): array {
		$toggles = parent::get_settings_modal_toggles();

		// Add Forminator-specific toggle sections.
		$forminator_toggles = array(
			'field_validation' => array(
				'title'    => esc_html__( 'Field Validation', 'squad-modules-for-divi' ),
				'priority' => 50,
			),
		);

		/**
		 * Filters the Forminator-specific toggle sections.
		 *
		 * @since 3.2.0
		 *
		 * @param array $forminator_toggles The Forminator-specific toggles
		 * @param array $toggles            The base toggles
		 */
		$forminator_toggles = apply_filters( 'divi_squad_module_forminator_toggles', $forminator_toggles, $toggles );

		$toggles['advanced']['toggles'] = array_merge(
			$toggles['advanced']['toggles'],
			$forminator_toggles
		);

		return $toggles;
	}

	/**
	 * Get advanced fields configuration for the module.
	 *
	 * @since  1.2.0
	 * @access public
	 *
	 * @return array<string, array<string, array<string, array>>> Advanced fields configuration
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
		 * Filters the advanced fields configuration for the Forminator module.
		 *
		 * @since 3.2.0
		 *
		 * @param array $advanced_fields The advanced fields configuration.
		 * @param self  $module          The current module instance.
		 */
		return apply_filters( 'divi_squad_module_forminator_advanced_fields', $advanced_fields, $this );
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
	 * @return string Module's rendered output
	 * @throws Exception If there's an error during rendering.
	 */
	public function render( $attrs, $content, $render_slug ): string {
		try {
			/**
			 * Fires before the Forminator module is rendered.
			 *
			 * @since 3.2.0
			 *
			 * @param array $attrs  The module attributes.
			 * @param self  $module The current module instance.
			 */
			do_action( 'divi_squad_before_module_forminator_render', $attrs, $this );

			// Check if Forminator is installed.
			if ( ! class_exists( 'Forminator_API' ) ) {
				$message = __( 'Forminator is not installed or activated. Please install and activate Forminator to use this module.', 'squad-modules-for-divi' );

				/**
				 * Filters the message shown when Forminator is not installed.
				 *
				 * @since 3.2.0
				 *
				 * @param string $message The default message.
				 * @param array  $attrs   The module attributes.
				 */
				$message = apply_filters( 'divi_squad_module_forminator_not_activated_message', $message, $attrs );

				return $this->render_notice( $message, 'error' );
			}

			$form_html = static::squad_form_styler__get_form_html( $attrs );

			// If no form HTML, return empty string.
			if ( '' !== $form_html ) {

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
					$message = apply_filters( 'divi_squad_module_forminator_no_form_message_vb', $message, $attrs );

					return $this->render_notice( $message, 'warning' );
				}

				// If no form is selected in the frontend, return empty string.
				if ( ! DiviUtil::is_fb_enabled() && is_user_logged_in() ) {
					$message = esc_html__( 'No form selected from the Forminator Settings.', 'squad-modules-for-divi' );

					/**
					 * Filters the message shown when no form is selected.
					 *
					 * @since 3.2.0
					 *
					 * @param string $message The default message.
					 * @param array  $attrs   The module attributes.
					 */
					$message = apply_filters( 'divi_squad_module_forminator_no_form_message_frontend', $message, $attrs );

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
				return apply_filters( 'divi_squad_module_forminator_empty_form_html', '', $attrs, $this );
			}

			// Generate styles.
			$this->squad_generate_all_styles( $attrs );

			/**
			 * Filters the final HTML output of the Forminator module.
			 *
			 * @since 3.2.0
			 *
			 * @param string $form_html The form HTML.
			 * @param array  $attrs     The module attributes.
			 * @param self   $module    The current module instance.
			 */
			return apply_filters( 'divi_squad_module_forminator_form_html', $form_html, $attrs, $this );
		} catch ( Throwable $e ) {
			$this->log_error(
				$e,
				array(
					'attrs'       => $attrs,
					'render_slug' => $render_slug,
					'module'      => 'Forminator',
					'method'      => 'render',
					'message'     => 'Error occurred while rendering Forminator module',
				)
			);

			return $this->render_error_message();
		}
	}

	/**
	 * Get CSS selectors for the Forminator module.
	 *
	 * @since  1.2.0
	 * @access protected
	 *
	 * @return array<string, array<string, string>> An associative array of CSS selectors.
	 */
	protected function squad_get_css_selectors(): array {
		$css_selectors = array(
			'form'          => array(
				'form' => "$this->main_css_element div form.forminator-ui.forminator-custom-form",
			),
			'fields'        => array(
				'all'      => $this->get_field_selector(),
				'input'    => "$this->main_css_element div form.forminator-ui.forminator-custom-form .forminator-input",
				'textarea' => "$this->main_css_element div form.forminator-ui.forminator-custom-form .forminator-textarea",
				'select'   => "$this->main_css_element div form.forminator-ui.forminator-custom-form .forminator-select",
			),
			'typography'    => array(
				'labels'       => "$this->main_css_element div form.forminator-ui.forminator-custom-form .forminator-label",
				'placeholders' => array(
					'normal' => "$this->main_css_element div form.forminator-ui.forminator-custom-form input::placeholder, $this->main_css_element div form.forminator-ui.forminator-custom-form textarea::placeholder",
					'hover'  => "$this->main_css_element div form.forminator-ui.forminator-custom-form input:hover::placeholder, $this->main_css_element div form.forminator-ui.forminator-custom-form textarea:hover::placeholder",
				),
				'descriptions' => "$this->main_css_element div form.forminator-ui.forminator-custom-form .forminator-description",
			),
			'submit_button' => array(
				'all' => "$this->main_css_element div form.forminator-ui.forminator-custom-form .forminator-button-submit",
			),
			'messages'      => array(
				'validation' => "$this->main_css_element .forminator-ui .forminator-error-message",
				'error'      => "$this->main_css_element .forminator-ui .forminator-response-message.forminator-error",
				'success'    => "$this->main_css_element .forminator-ui .forminator-response-message.forminator-success",
			),
		);

		/**
		 * Filters the CSS selectors for the Forminator module.
		 *
		 * @since 3.2.0
		 *
		 * @param array $css_selectors The default CSS selectors.
		 * @param self  $module        The current module instance.
		 */
		return apply_filters( 'divi_squad_module_forminator_css_selectors', $css_selectors, $this );
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
		 * Fires after initializing selectors for the Forminator module.
		 *
		 * @since 3.2.0
		 *
		 * @param self $module The current module instance.
		 */
		do_action( 'divi_squad_module_forminator_after_init_selectors', $this );
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
			'form_id'               => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Form', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Choose the Forminator form to display.', 'squad-modules-for-divi' ),
					'options'          => divi_squad()->form_element->get_forms_by( 'forminator' ),
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

		/**
		 * Filters the general fields for the Forminator module.
		 *
		 * @since 3.2.0
		 *
		 * @param array $fields The default general fields.
		 * @param self  $module The current module instance.
		 */
		return apply_filters( 'divi_squad_module_forminator_general_fields', $fields, $this );
	}

	/**
	 * Get advanced fields for the module.
	 *
	 * @since  1.2.0
	 * @access protected
	 *
	 * @return array Array of advanced fields.
	 */
	protected function squad_get_additional_design_fields(): array {
		$fields = parent::squad_get_additional_design_fields();

		$fields['field_width'] = divi_squad()->d4_module_helper->add_range_field(
			esc_html__( 'Field Width', 'squad-modules-for-divi' ),
			array(
				'default'     => 100,
				'description' => esc_html__( 'Set the width of the form fields.', 'squad-modules-for-divi' ),
				'max'         => 100,
				'min'         => 0,
				'step'        => 1,
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'field',
			)
		);

		/**
		 * Filters the checkbox and radio fields for the Forminator module.
		 *
		 * @since 3.2.0
		 *
		 * @param array $fields The default checkbox and radio fields.
		 * @param self  $module The current module instance.
		 */
		return apply_filters( 'divi_squad_module_forminator_checkbox_radio_fields', $fields, $this );
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
	 * Get the field selector.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @return string The field selector.
	 */
	protected function get_field_selector(): string {
		$allowed_fields = array(
			'input[type=email]',
			'input[type=text]',
			'input[type=url]',
			'input[type=tel]',
			'input[type=number]',
			'input[type=date]',
			'input[type=file]',
			'select',
			'textarea',
		);

		$selectors = array_map(
			fn( $field ) => "$this->main_css_element div form.forminator-ui.forminator-custom-form $field",
			$allowed_fields
		);

		/**
		 * Filters the field selectors for the Forminator module.
		 *
		 * @since 3.2.0
		 *
		 * @param array $selectors The field selectors.
		 * @param self  $module    The current module instance.
		 */
		$selectors = apply_filters( 'divi_squad_module_forminator_field_selectors', $selectors, $this );

		return implode( ', ', $selectors );
	}

	/**
	 * Get font field configurations.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @return array Font field configurations.
	 */
	protected function squad_get_font_fields(): array {
		$font_fields = array(
			'field_label_text'       => divi_squad()->d4_module_helper->add_font_field(
				esc_html__( 'Label', 'squad-modules-for-divi' ),
				array(
					'css'         => array( 'main' => $this->squad_get_css_selector_string( 'typography.labels' ) ),
					'font_size'   => array( 'default' => '14px' ),
					'line_height' => array( 'default' => '1.5em' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'field_label_text',
				)
			),
			'field_text'             => divi_squad()->d4_module_helper->add_font_field(
				esc_html__( 'Field', 'squad-modules-for-divi' ),
				array(
					'css'         => array( 'main' => $this->squad_get_css_selector_string( 'fields.all' ) ),
					'font_size'   => array( 'default' => '14px' ),
					'line_height' => array( 'default' => '1.5em' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'field_text',
				)
			),
			'field_description_text' => divi_squad()->d4_module_helper->add_font_field(
				esc_html__( 'Description', 'squad-modules-for-divi' ),
				array(
					'css'         => array( 'main' => $this->squad_get_css_selector_string( 'typography.descriptions' ) ),
					'font_size'   => array( 'default' => '14px' ),
					'line_height' => array( 'default' => '1.5em' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'field_description_text',
				)
			),
			'placeholder_text'       => divi_squad()->d4_module_helper->add_font_field(
				esc_html__( 'Placeholder', 'squad-modules-for-divi' ),
				array(
					'css'         => array( 'main' => $this->squad_get_css_selector_string( 'typography.placeholders' ) ),
					'font_size'   => array( 'default' => '14px' ),
					'line_height' => array( 'default' => '1.5em' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'placeholder_text',
				)
			),
			'form_button_text'       => divi_squad()->d4_module_helper->add_font_field(
				esc_html__( 'Submit Button', 'squad-modules-for-divi' ),
				array(
					'css'         => array( 'main' => $this->submit_button_selector ),
					'font_size'   => array( 'default' => '14px' ),
					'line_height' => array( 'default' => '1.5em' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'form_button_text',
				)
			),
			'message_error_text'     => divi_squad()->d4_module_helper->add_font_field(
				esc_html__( 'Error Message', 'squad-modules-for-divi' ),
				array(
					'css'         => array( 'main' => $this->error_message_selector ),
					'font_size'   => array( 'default' => '14px' ),
					'line_height' => array( 'default' => '1.5em' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'message_error_text',
				)
			),
			'message_success_text'   => divi_squad()->d4_module_helper->add_font_field(
				esc_html__( 'Success Message', 'squad-modules-for-divi' ),
				array(
					'css'         => array( 'main' => $this->success_message_selector ),
					'font_size'   => array( 'default' => '14px' ),
					'line_height' => array( 'default' => '1.5em' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'message_success_text',
				)
			),
		);

		/**
		 * Filters the font field configurations.
		 *
		 * @since 3.2.0
		 *
		 * @param array $font_fields The font field configurations.
		 * @param self  $module      Current module instance.
		 */
		return apply_filters( 'divi_squad_module_forminator_font_fields', $font_fields, $this );
	}

	/**
	 * Get border field configurations.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @return array Border field configurations.
	 */
	protected function squad_get_border_fields(): array {
		$border_fields = array(
			'default'         => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			'wrapper'         => divi_squad()->d4_module_helper->add_border_field(
				esc_html__( 'Form', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main'      => array(
							'border_radii'  => $this->squad_get_css_selector_string( 'form.form' ),
							'border_styles' => $this->squad_get_css_selector_string( 'form.form' ),
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
					'toggle_slug' => 'wrapper',
				)
			),
			'field'           => divi_squad()->d4_module_helper->add_border_field(
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
			'form_button'     => divi_squad()->d4_module_helper->add_border_field(
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
			'message_error'   => divi_squad()->d4_module_helper->add_border_field(
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
					'toggle_slug' => 'message_error',
				)
			),
			'message_success' => divi_squad()->d4_module_helper->add_border_field(
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
					'toggle_slug' => 'message_success',
				)
			),
		);

		/**
		 * Filters the border field configurations.
		 *
		 * @since 3.2.0
		 *
		 * @param array $border_fields The border field configurations.
		 * @param self  $module        Current module instance.
		 */
		return apply_filters( 'divi_squad_module_forminator_border_fields', $border_fields, $this );
	}

	/**
	 * Get box shadow field configurations.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @return array Box shadow field configurations.
	 */
	protected function squad_get_box_shadow_fields(): array {
		$box_shadow_fields = array(
			'default'         => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			'wrapper'         => divi_squad()->d4_module_helper->add_box_shadow_field(
				esc_html__( 'Form Box Shadow', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main' => $this->squad_get_css_selector_string( 'form.form' ),
					),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'wrapper',
				)
			),
			'field'           => divi_squad()->d4_module_helper->add_box_shadow_field(
				esc_html__( 'Field Box Shadow', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main' => $this->squad_get_css_selector_string( 'fields.all' ),
					),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'field',
				)
			),
			'form_button'     => divi_squad()->d4_module_helper->add_box_shadow_field(
				esc_html__( 'Submit Button Box Shadow', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main' => $this->submit_button_selector,
					),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'form_button',
				)
			),
			'message_error'   => divi_squad()->d4_module_helper->add_box_shadow_field(
				esc_html__( 'Error Message Box Shadow', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main' => $this->error_message_selector,
					),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'message_error',
				)
			),
			'message_success' => divi_squad()->d4_module_helper->add_box_shadow_field(
				esc_html__( 'Success Message Box Shadow', 'squad-modules-for-divi' ),
				array(
					'css'         => array(
						'main' => $this->success_message_selector,
					),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'message_success',
				)
			),
		);

		/**
		 * Filters the box shadow field configurations.
		 *
		 * @since 3.2.0
		 *
		 * @param array $box_shadow_fields The box shadow field configurations.
		 * @param self  $module            Current module instance.
		 */
		return apply_filters( 'divi_squad_module_forminator_box_shadow_fields', $box_shadow_fields, $this );
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
		$attrs = (array) apply_filters( 'divi_squad_module_forminator_get_form_html_attrs', $attrs );

		if ( ! class_exists( 'Forminator_API' ) || '' === $attrs['form_id'] || divi_squad()->form_element::DEFAULT_FORM_ID === $attrs['form_id'] ) {
			return '';
		}

		$form_id_hash = $attrs['form_id'];
		$form_id_raw  = divi_squad()->memory->get( "form_id_original_$form_id_hash", '' );

		if ( '' === $form_id_raw ) {
			$collection = divi_squad()->form_element->get_forms_by( 'forminator', 'id' );

			/**
			 * Filters the forms collection before form selection.
			 *
			 * @since 3.2.0
			 *
			 * @param array $collection The forms collection.
			 * @param array $attrs      The form attributes.
			 */
			$collection = apply_filters( 'divi_squad_module_forminator_forms_collection', $collection, $attrs );

			if ( ! isset( $collection[ $attrs['form_id'] ] ) ) {
				return '';
			}

			$form_id_raw = $collection[ $attrs['form_id'] ];
			divi_squad()->memory->set( "form_id_original_$form_id_hash", $form_id_raw );
			divi_squad()->memory->sync_data();
		}

		$shortcode = sprintf( '[forminator_form id="%s"]', esc_attr( $form_id_raw ) );

		/**
		 * Filters the Forminator shortcode before processing.
		 *
		 * @since 3.2.0
		 *
		 * @param string $shortcode The form shortcode.
		 * @param string $form_id   The form ID.
		 * @param array  $attrs     The form attributes.
		 */
		$shortcode = apply_filters( 'divi_squad_module_forminator_form_shortcode', $shortcode, $form_id_raw, $attrs );

		$html = do_shortcode( $shortcode );

		/**
		 * Filters the form HTML after shortcode processing.
		 *
		 * @since 3.2.0
		 *
		 * @param string $html      The processed form HTML.
		 * @param string $form_id   The form ID.
		 * @param string $shortcode The original shortcode.
		 * @param array  $attrs     The form attributes.
		 */
		return apply_filters( 'divi_squad_module_forminator_processed_form_html', $html, $form_id_raw, $shortcode, $attrs );
	}
}
