<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Abstract FormStyler Class
 *
 * This class provides the base functionality for styling form elements
 * in the Divi Builder. It includes methods for generating fields, styles,
 * and handling transitions for various form components.
 *
 * @since   1.0.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Abstracts\Module;

use DiviSquad\Builder\Version4\Abstracts\Module;

/**
 * Abstract Form Styler Class
 *
 * This class provides the base functionality for styling form elements
 * in the Divi Builder. It includes methods for generating fields, styles,
 * and handling transitions for various form components.
 *
 * @since   1.0.0
 * @package DiviSquad
 */
abstract class Form_Styler extends Module {

	/**
	 * CSS Selectors configuration
	 *
	 * @since 3.2.0
	 * @var array
	 */
	protected array $squad_css_selectors = array();

	/**
	 * CSS selector for the form
	 *
	 * @since 3.2.0
	 * @var string
	 */
	protected string $form_selector;

	/**
	 * CSS selector for form fields
	 *
	 * @since 3.2.0
	 * @var string
	 */
	protected string $field_selector;

	/**
	 * CSS selector for the submit button
	 *
	 * @since 3.2.0
	 * @var string
	 */
	protected string $submit_button_selector;

	/**
	 * CSS selector for error messages
	 *
	 * @since 3.2.0
	 * @var string
	 */
	protected string $error_message_selector;

	/**
	 * CSS selector for success messages
	 *
	 * @since 3.2.0
	 * @var string
	 */
	protected string $success_message_selector;

	/**
	 * Constructor for the Form Styler class.
	 *
	 * Initializes the parent constructor and sets up the selectors and hooks.
	 *
	 * @since  3.2.0
	 * @access public
	 * @return void
	 */
	public function __construct() {
		/**
		 * Fires before the Form Styler constructor runs.
		 *
		 * Allows executing code before the form styler is initialized.
		 *
		 * @since 3.2.0
		 *
		 * @param Form_Styler $this Current Form Styler instance
		 */
		do_action( 'divi_squad_form_styler_before_construct', $this );

		$this->squad_setup_hooks();
		parent::__construct();

		/**
		 * Fires after the Form Styler instance is constructed.
		 *
		 * @since 3.2.0
		 *
		 * @param Form_Styler $this Current Form Styler instance
		 */
		do_action( 'divi_squad_form_styler_after_construct', $this );
	}

	/**
	 * Get settings modal toggles for the module.
	 *
	 * @since  3.2.0
	 * @access public
	 *
	 * @return array Array of toggle settings.
	 */
	public function get_settings_modal_toggles(): array {
		$toggles = array(
			'general'  => array(
				'toggles' => array(
					'forms'       => esc_html__( 'Form Options', 'squad-modules-for-divi' ),
					'field_icons' => esc_html__( 'Field Icons', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'wrapper'                => esc_html__( 'Form', 'squad-modules-for-divi' ),
					'title'                  => esc_html__( 'Form Title', 'squad-modules-for-divi' ),
					'title_text'             => esc_html__( 'Form Title Text', 'squad-modules-for-divi' ),
					'form_before_text'       => esc_html__( 'Form Before Text', 'squad-modules-for-divi' ),
					'field'                  => esc_html__( 'Field', 'squad-modules-for-divi' ),
					'field_text'             => esc_html__( 'Field Text', 'squad-modules-for-divi' ),
					'field_elements'         => array(
						'title'             => esc_html__( 'Field Elements', 'squad-modules-for-divi' ),
						'tabbed_subtoggles' => true,
						'sub_toggles'       => array(
							'label'       => array(
								'name' => esc_html__( 'Label', 'squad-modules-for-divi' ),
							),
							'input'       => array(
								'name' => esc_html__( 'Input', 'squad-modules-for-divi' ),
							),
							'placeholder' => array(
								'name' => esc_html__( 'Placeholder', 'squad-modules-for-divi' ),
							),
						),
					),
					'field_label_text'       => esc_html__( 'Field Label Text', 'squad-modules-for-divi' ),
					'field_description_text' => esc_html__( 'Field Description Text', 'squad-modules-for-divi' ),
					'placeholder_text'       => esc_html__( 'Field Placeholder Text', 'squad-modules-for-divi' ),
					'form_custom_html'       => esc_html__( 'Custom HTML', 'squad-modules-for-divi' ),
					'form_custom_html_text'  => esc_html__( 'Custom HTML Text', 'squad-modules-for-divi' ),
					'form_button'            => esc_html__( 'Submit Button', 'squad-modules-for-divi' ),
					'form_button_text'       => esc_html__( 'Submit Button Text', 'squad-modules-for-divi' ),
					'message_error'          => esc_html__( 'Error Message', 'squad-modules-for-divi' ),
					'message_error_text'     => esc_html__( 'Error Message Text', 'squad-modules-for-divi' ),
					'message_success'        => esc_html__( 'Success Message', 'squad-modules-for-divi' ),
					'message_success_text'   => esc_html__( 'Success Message Text', 'squad-modules-for-divi' ),
				),
			),
		);

		/**
		 * Filters the settings modal toggles for the Form Styler.
		 *
		 * @since 3.2.0
		 *
		 * @param array $toggles The default toggles for the Form Styler.
		 */
		return apply_filters( 'divi_squad_form_styler_toggles', $toggles );
	}

	/**
	 * Get advanced fields configuration for the module.
	 *
	 * Defines the advanced field configurations for the module.
	 *
	 * @since  1.2.0
	 * @access public
	 *
	 * @return array An array of advanced field configurations.
	 */
	public function get_advanced_fields_config(): array {
		$advanced_fields = parent::get_advanced_fields_config();

		$advanced_fields['margin_padding'] = divi_squad()->d4_module_helper->selectors_margin_padding( $this->main_css_element );
		$advanced_fields['max_width']      = divi_squad()->d4_module_helper->selectors_max_width( $this->main_css_element );
		$advanced_fields['height']         = divi_squad()->d4_module_helper->selectors_default( $this->main_css_element );

		$advanced_fields['image_icon']   = false;
		$advanced_fields['link_options'] = false;
		$advanced_fields['filters']      = false;
		$advanced_fields['text']         = false;
		$advanced_fields['button']       = false;

		return $advanced_fields;
	}

	/**
	 * Get fields for the module.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return array Array of fields for the module.
	 */
	public function get_fields(): array {
		$fields = array_merge_recursive(
			$this->squad_get_general_fields(),
			$this->squad_get_design_fields(),
			$this->squad_get_advanced_fields()
		);

		/**
		 * Filters the fields for the Form Styler.
		 *
		 * @since 3.2.0
		 *
		 * @param string $slug   The slug of current module.
		 *
		 * @param array  $fields The default fields for the Form Styler.
		 */
		$fields = (array) apply_filters( 'divi_squad_form_styler_fields', $fields, $this->slug );

		return $this->squad_remove_pre_assigned_fields( $fields, $this->squad_get_removable_fields() );
	}

	/**
	 * Get custom CSS fields configuration.
	 *
	 * @since  3.2.0
	 * @access public
	 *
	 * @return array Custom CSS fields configuration.
	 */
	public function get_custom_css_fields_config(): array {
		return array(
			'wrapper'         => array(
				'label'    => esc_html__( 'Form', 'squad-modules-for-divi' ),
				'selector' => $this->form_selector,
			),
			'field'           => array(
				'label'    => esc_html__( 'Field', 'squad-modules-for-divi' ),
				'selector' => $this->field_selector,
			),
			'radio_checkbox'  => array(
				'label'    => esc_html__( 'Radio Checkbox', 'squad-modules-for-divi' ),
				'selector' => "$this->form_selector input[type=checkbox], $this->form_selector input[type=radio]",
			),
			'form_button'     => array(
				'label'    => esc_html__( 'Button', 'squad-modules-for-divi' ),
				'selector' => $this->submit_button_selector,
			),
			'message_error'   => array(
				'label'    => esc_html__( 'Error Message', 'squad-modules-for-divi' ),
				'selector' => $this->error_message_selector,
			),
			'message_success' => array(
				'label'    => esc_html__( 'Success Message', 'squad-modules-for-divi' ),
				'selector' => $this->success_message_selector,
			),
		);
	}

	/**
	 * Get transition fields CSS properties.
	 *
	 * @since  3.2.0
	 * @access public
	 *
	 * @return array Array of transition fields CSS properties.
	 */
	public function get_transition_fields_css_props(): array {
		$fields = (array) parent::get_transition_fields_css_props();
		$this->squad_add_transition_fields( $fields );

		return $fields;
	}

	/**
	 * Initialize selectors for the form styler.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @return void
	 */
	abstract protected function squad_init_selectors(): void;

	/**
	 * Set up hooks for the Form Styler.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @return void
	 */
	protected function squad_setup_hooks(): void {}

	/**
	 * Get a specific selector or group of selectors
	 *
	 * Here is an inline example of how to use this method:
	 * ```
	 * // Get the form selector
	 * $form_wrapper = $this->squad_get_css_selector('form.wrapper');
	 *
	 * // Get all field selectors
	 * $field_selectors = $this->squad_get_css_selector('fields.all');
	 * ```
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @param string $key Dot notation key for the selector.
	 *
	 * @return string|array
	 */
	protected function squad_get_css_selector( string $key ) {
		$keys  = explode( '.', $key );
		$value = $this->squad_css_selectors;

		foreach ( $keys as $k ) {
			if ( ! isset( $value[ $k ] ) ) {
				return '';
			}
			$value = $value[ $k ];
		}

		/**
		 * Filter the CSS selector value before return
		 *
		 * @since 3.2.0
		 *
		 * @param string       $key   Original selector key
		 * @param array        $keys  Exploded selector key parts
		 *
		 * @param string|array $value The selector value
		 */
		return apply_filters( 'divi_squad_form_styler_css_selector', $value, $key, $keys );
	}

	/**
	 * Get a selector string
	 *
	 * Here is an inline example of how to use this method:
	 * ```
	 * // Get the normal state form selector string
	 * $form_wrapper = $this->squad_get_css_selector_string('form.wrapper');
	 *
	 * // Get the hover state for all field selectors
	 * $field_selectors_hover = $this->squad_get_css_selector_string('fields.all', 'v1', 'hover');
	 * ```
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @param string $key   Dot notation key for the selector.
	 * @param string $state Selector state (default: 'normal').
	 *
	 * @return string
	 */
	protected function squad_get_css_selector_string( string $key, string $state = 'normal' ): string {
		$selector = $this->squad_get_css_selector( $key );

		if ( is_array( $selector ) ) {
			if ( isset( $selector[ $state ] ) ) {
				$selector = $selector[ $state ];
			}

			return is_array( $selector ) ? implode( ', ', $selector ) : $selector;
		}

		return $selector ?? '';
	}

	/**
	 * Get hover selector string
	 *
	 * @param string $key Dot notation key for the selector.
	 *
	 * @return string
	 */
	protected function squad_get_hover_selector_string( string $key ): ?string {
		if ( $this->squad_get_css_selector_string( $key, 'hover' ) ) {
			return $this->squad_get_css_selector_string( $key, 'hover' );
		}

		return $this->squad_get_hover_selector( $this->squad_get_css_selector_string( $key ) );
	}

	/**
	 * Get hover selector string
	 *
	 * Here is an inline example of how to use this method:
	 * ```
	 * // Get the hover selector for the submit button
	 * $submit_button_hover = $this->get_hover_selector_string('submit_button.all');
	 *
	 * // Get the hover selector for form fields
	 * $fields_hover = $this->get_hover_selector_string('fields.all');
	 * ```
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @param string $selector The base selector.
	 *
	 * @return string
	 */
	protected function squad_get_hover_selector( string $selector ): string {
		$selectors       = explode( ',', $selector );
		$hover_selectors = array_map( array( $this, 'squad_add_hover_to_selector' ), $selectors );

		/**
		 * Filter the hover selectors before they are combined
		 *
		 * @since 3.2.0
		 *
		 * @param array $hover_selectors Array of hover selectors
		 * @param array $selectors       Original selectors
		 */
		$hover_selectors = apply_filters( 'divi_squad_form_styler_hover_selectors', $hover_selectors, $selectors );

		return implode( ', ', $hover_selectors );
	}

	/**
	 * Update a specific selector
	 *
	 * Here is an inline example of how to use this method:
	 * ```
	 * // Update the form selector
	 * $this->squad_update_css_selector('form.wrapper', '%%order_class%% .new-form-wrapper');
	 *
	 * // Update field selectors with normal and hover states
	 * $this->squad_update_css_selector('fields.all', [
	 *     'normal' => '%%order_class%% .new-field',
	 *     'hover' => '%%order_class%% .new-field:hover'
	 * ]);
	 * ```
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @param string       $key     Dot notation key for the selector.
	 * @param string|array $value   New selector value.
	 * @param string       $version Selector version (default: 'v1').
	 *
	 * @return void
	 */
	protected function squad_update_css_selector( string $key, $value, string $version = 'v1' ): void {
		$keys   = explode( '.', $key );
		$target = &$this->squad_css_selectors[ $version ];

		foreach ( $keys as $k ) {
			if ( ! isset( $target[ $k ] ) ) {
				$target[ $k ] = array();
			}
			$target = &$target[ $k ];
		}

		$target = $value;
	}

	/**
	 * Add :hover pseudo-class to a single selector.
	 *
	 * @since  3.2.0
	 * @access private
	 *
	 * @param string $selector A single CSS selector.
	 *
	 * @return string The selector with :hover added.
	 */
	protected function squad_add_hover_to_selector( string $selector ): string {
		$selector = trim( $selector );
		if ( empty( $selector ) ) {
			return '';
		}

		$parts      = explode( ' ', $selector );
		$last_part  = &$parts[ count( $parts ) - 1 ];
		$last_part .= ':hover';

		/**
		 * Filter the hover selector for a single selector
		 *
		 * @since 3.2.0
		 *
		 * @param string $hover_selector The generated hover selector
		 * @param string $selector       Original selector
		 * @param array  $parts          Selector parts
		 */
		return apply_filters(
			'divi_squad_form_styler_single_hover_selector',
			implode( ' ', $parts ),
			$selector,
			$parts
		);
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
		/**
		 * Filters the general fields for the form styler module.
		 *
		 * @since 3.2.0
		 *
		 * @param array       $fields Default array of general fields
		 * @param Form_Styler $this   Current Form Styler instance
		 */
		return apply_filters( 'divi_squad_form_styler_general_fields', array(), $this );
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
		$customizable_design_fields = $this->squad_get_customizable_design_fields();
		$fields_after_background    = array();
		$fields_before_margin       = array();

		if ( isset( $customizable_design_fields['button_fields_after_background'] ) ) {
			$fields_after_background = $customizable_design_fields['button_fields_after_background'];
			unset( $customizable_design_fields['button_fields_after_background'] );
		}
		if ( isset( $customizable_design_fields['button_fields_before_margin'] ) ) {
			$fields_before_margin = $customizable_design_fields['button_fields_before_margin'];
			unset( $customizable_design_fields['button_fields_before_margin'] );
		}

		return array_merge_recursive(
			$this->squad_get_background_fields(),
			$this->squad_get_button_fields( $fields_after_background, $fields_before_margin ),
			$this->squad_get_additional_design_fields(),
			$customizable_design_fields,
			$this->squad_get_custom_spacing_fields()
		);
	}

	/**
	 * Get advanced fields for the module.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @return array Array of advanced fields.
	 */
	protected function squad_get_advanced_fields(): array {
		return array();
	}

	/**
	 * Get background fields for the module.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @return array Array of background fields.
	 */
	protected function squad_get_background_fields(): array {
		$wrapper_fields         = $this->squad_add_background_field(
			esc_html__( 'Form Background', 'squad-modules-for-divi' ),
			'form_wrapper_background',
			'wrapper'
		);
		$input_fields_fields    = $this->squad_add_background_field(
			esc_html__( 'Field Background', 'squad-modules-for-divi' ),
			'fields_background',
			'field'
		);
		$message_error_fields   = $this->squad_add_background_field(
			esc_html__( 'Error Message Background', 'squad-modules-for-divi' ),
			'message_error_background',
			'message_error'
		);
		$message_success_fields = $this->squad_add_background_field(
			esc_html__( 'Success Message Background', 'squad-modules-for-divi' ),
			'message_success_background',
			'message_success'
		);

		$background_fields = array_merge_recursive( $wrapper_fields, $input_fields_fields, $message_error_fields, $message_success_fields );

		/**
		 * Filters the background fields for the Form Styler.
		 *
		 * @since 3.2.0
		 *
		 * @param array  $background_fields The default background fields for the Form Styler.
		 * @param string $slug              The current module slug, example: disq_{module_category}_{module_name}
		 */
		return apply_filters( 'divi_squad_form_styler_background_fields', $background_fields, $this->slug );
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
	protected function squad_get_enable_form_field( string $type ): array {
		$label = ucfirst( $type );

		return divi_squad()->d4_module_helper->add_yes_no_field(
		// translators: Label of toggle field like title and description.
			sprintf( esc_html__( 'Show Form %s', 'squad-modules-for-divi' ), $label ),
			array(
				// translators: Label of toggle field like title and description.
				'description'      => sprintf( esc_html__( 'Choose whether to display the form %s.', 'squad-modules-for-divi' ), strtolower( $label ) ),
				'default_on_front' => 'off',
				'affects'          => $this->squad_get_enable_form_field_affects( $type ),
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
	protected function squad_get_enable_form_field_affects( string $type ): array {
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
	 * Add a background field.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @param string $label       Field label.
	 * @param string $base_name   Base name for the field.
	 * @param string $toggle_slug Toggle slug for the field.
	 *
	 * @return array Background field configuration.
	 */
	protected function squad_add_background_field( string $label, string $base_name, string $toggle_slug ): array {
		return $this->squad_utils->field_definitions->add_background_field(
			array(
				'label'       => $label,
				'base_name'   => $base_name,
				'context'     => "{$base_name}_color",
				'tab_slug'    => 'advanced',
				'toggle_slug' => $toggle_slug,
			)
		);
	}

	/**
	 * Get button fields for the module.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @param array $fields_after_background List of fields after the background fields.
	 * @param array $fields_before_margin    List of fields before the margin fields.
	 *
	 * @return array Array of button fields.
	 */
	protected function squad_get_button_fields( array $fields_after_background, array $fields_before_margin ): array {
		return $this->squad_utils->field_definitions->get_button_fields(
			array(
				'base_attr_name'          => 'form_button',
				'fields_after_background' => $fields_after_background,
				'fields_before_margin'    => $fields_before_margin,
				'toggle_slug'             => 'form_button',
				'depends_show_if'         => 'on',
			)
		);
	}

	/**
	 * Get additional design fields for the module.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @return array Array of checkbox and radio fields.
	 */
	protected function squad_get_additional_design_fields(): array {
		$checkbox_radio_fields = array(
			'form_ch_rad_color' => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Checkbox & Radio Active Color', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Here you can define a custom color for checkbox and radio fields.', 'squad-modules-for-divi' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'field',
				)
			),
			'form_ch_rad_size'  => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Checkbox & Radio Field Size', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Here you can choose size for checkbox and radio fields.', 'squad-modules-for-divi' ),
					'range_settings' => array(
						'min_limit' => '1',
						'min'       => '1',
						'max_limit' => '200',
						'max'       => '200',
						'step'      => '1',
					),
					'default_unit'   => 'px',
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'field',
				)
			),
		);

		/**
		 * Filters additional checkbox and radio fields for the Form Styler.
		 *
		 * @since 3.2.0
		 *
		 * @param array  $checkbox_radio_fields The default checkbox and radio fields for the Form Styler.
		 * @param string $slug                  The current module slug, example: disq_{module_category}_{module_name}
		 */
		return apply_filters( 'divi_squad_form_styler_checkbox_radio_fields', $checkbox_radio_fields, $this->slug );
	}

	/**
	 * Get custom spacing fields for the module.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @return array Array of custom spacing fields.
	 */
	protected function squad_get_custom_spacing_fields(): array {
		// Get all spacing prefixes from the Forms utility.
		$custom_spacing_prefixes = divi_squad()->forms_element->get_custom_spacing_prefixes();

		// Prepare array to collect all fields.
		$custom_spacing_fields = array();

		// Pre-build all ranges settings array to avoid recreating it.
		$base_range_settings = array(
			'range_settings' => array(
				'min_limit' => '1',
				'min'       => '1',
				'max_limit' => '100',
				'max'       => '100',
				'step'      => '1',
			),
			'tab_slug'       => 'advanced',
		);

		// Generate margin and padding fields for each prefix in a single pass.
		foreach ( $custom_spacing_prefixes as $prefix => $options ) {
			$label                   = ! empty( $options['label'] ) ? $options['label'] : '';
			$settings                = $base_range_settings;
			$settings['toggle_slug'] = $prefix;

			// Add margin field.
			$custom_spacing_fields[ "{$prefix}_margin" ] = $this->squad_add_custom_spacing_field(
			// translators: %s: Component name for margin setting.
				sprintf( esc_html__( '%s Margin', 'squad-modules-for-divi' ), $label ),
				'custom_margin',
				$settings
			);

			// Add padding field.
			$custom_spacing_fields[ "{$prefix}_padding" ] = $this->squad_add_custom_spacing_field(
			// translators: %s: Component name for padding setting.
				sprintf( esc_html__( '%s Padding', 'squad-modules-for-divi' ), $label ),
				'custom_padding',
				$settings
			);
		}

		/**
		 * Filters the custom spacing fields for the Form Styler.
		 *
		 * @since 3.2.0
		 *
		 * @param array $custom_spacing_fields The generated custom spacing fields.
		 */
		return apply_filters( 'divi_squad_form_styler_custom_spacing_fields', $custom_spacing_fields );
	}

	/**
	 * Get margin and padding fields for a specific element.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @param string $prefix Element prefix.
	 * @param string $label  Element label.
	 *
	 * @return array Margin and padding fields.
	 */
	protected function squad_get_margin_padding_fields( string $prefix, string $label ): array {
		$base_settings = array(
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
			"{$prefix}_margin"  => $this->squad_add_custom_spacing_field(
			// translators: %s: Element Label for margin.
				sprintf( esc_html__( '%s Margin', 'squad-modules-for-divi' ), $label ),
				'custom_margin',
				$base_settings
			),
			"{$prefix}_padding" => $this->squad_add_custom_spacing_field(
			// translators: %s: Element Label for padding.
				sprintf( esc_html__( '%s Padding', 'squad-modules-for-divi' ), $label ),
				'custom_padding',
				$base_settings
			),
		);
	}

	/**
	 * Add a custom spacing field.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @param string $label    Field label.
	 * @param string $type     Field type (custom_margin or custom_padding).
	 * @param array  $settings Additional field settings.
	 *
	 * @return array Custom spacing field configuration.
	 */
	protected function squad_add_custom_spacing_field( string $label, string $type, array $settings ): array {
		/**
		 * Filter the custom spacing field settings
		 *
		 * @since 3.2.0
		 *
		 * @param array  $field_settings The field settings to be merged
		 * @param string $label          Field label
		 * @param string $type           Field type
		 * @param array  $settings       Additional settings
		 */
		$field_settings = (array) apply_filters(
			'divi_squad_form_styler_spacing_field_settings',
			array(
				'type'        => $type,
				'description' => esc_html__( 'Here you can define a custom size.', 'squad-modules-for-divi' ),
			),
			$label,
			$type,
			$settings
		);

		return divi_squad()->d4_module_helper->add_margin_padding_field(
			$label,
			array_merge( $settings, $field_settings )
		);
	}

	/**
	 * Get additional custom fields for the module.
	 *
	 * This method can be overridden in child classes to add custom fields.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @return array Array of additional custom fields.
	 */
	protected function squad_get_customizable_design_fields(): array {
		if ( method_exists( $this, 'get_form_styler_additional_custom_fields' ) ) {
			return $this->get_form_styler_additional_custom_fields();
		}

		return array();
	}

	/**
	 * Get removable fields for the module.
	 *
	 * This method can be overridden in child classes to specify removable fields.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @return array Array of removable fields.
	 */
	protected function squad_get_removable_fields(): array {
		return array();
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
	protected function squad_add_transition_fields( array &$fields ): void {
		/**
		 * Fires before adding transition fields.
		 *
		 * @since 3.2.0
		 *
		 * @param array $fields Reference to fields array
		 */
		do_action( 'divi_squad_form_styler_before_add_transition_fields', $fields );

		$this->squad_add_wrapper_transition_fields( $fields );
		$this->squad_add_field_transition_fields( $fields );
		$this->squad_add_error_message_transition_fields( $fields );
		$this->squad_add_success_message_transition_fields( $fields );
		$this->squad_add_button_transition_fields( $fields );
		$this->squad_add_checkbox_radio_transition_fields( $fields );
		$this->squad_add_generic_transition_fields( $fields );

		/**
		 * Fires after adding transition fields.
		 *
		 * @since 3.2.0
		 *
		 * @param array $fields Reference to fields array
		 */
		do_action( 'divi_squad_form_styler_after_add_transition_fields', $fields );
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
	protected function squad_add_wrapper_transition_fields( array &$fields ): void {
		$fields['wrapper_background_color'] = array( 'background' => $this->form_selector );
		$fields['wrapper_margin']           = array( 'margin' => $this->form_selector );
		$fields['wrapper_padding']          = array( 'padding' => $this->form_selector );

		divi_squad()->d4_module_helper->fix_border_transition( $fields, 'wrapper', $this->form_selector );
		divi_squad()->d4_module_helper->fix_box_shadow_transition( $fields, 'wrapper', $this->form_selector );
	}

	/**
	 * Add field transition fields.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @param array $fields Array of fields to add field transition fields to.
	 *
	 * @return void
	 */
	protected function squad_add_field_transition_fields( array &$fields ): void {
		$fields['fields_background_color'] = array( 'background' => $this->field_selector );
		$fields['field_margin']            = array( 'margin' => $this->field_selector );
		$fields['field_padding']           = array( 'padding' => $this->field_selector );

		divi_squad()->d4_module_helper->fix_fonts_transition( $fields, 'field_text', $this->field_selector );
		divi_squad()->d4_module_helper->fix_border_transition( $fields, 'field', $this->field_selector );
		divi_squad()->d4_module_helper->fix_box_shadow_transition( $fields, 'field', $this->field_selector );
	}

	/**
	 * Add error message transition fields.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @param array $fields Array of fields to add error message transition fields to.
	 *
	 * @return void
	 */
	protected function squad_add_error_message_transition_fields( array &$fields ): void {
		$fields['message_error_background_color'] = array( 'background' => $this->error_message_selector );
		$fields['message_error_margin']           = array( 'margin' => $this->error_message_selector );
		$fields['message_error_padding']          = array( 'padding' => $this->error_message_selector );

		divi_squad()->d4_module_helper->fix_fonts_transition( $fields, 'message_error_text', $this->error_message_selector );
		divi_squad()->d4_module_helper->fix_border_transition( $fields, 'message_error', $this->error_message_selector );
		divi_squad()->d4_module_helper->fix_box_shadow_transition( $fields, 'message_error', $this->error_message_selector );
	}

	/**
	 * Add success message transition fields.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @param array $fields Array of fields to add success message transition fields to.
	 *
	 * @return void
	 */
	protected function squad_add_success_message_transition_fields( array &$fields ): void {
		$fields['message_success_background_color'] = array( 'background' => $this->success_message_selector );
		$fields['message_success_margin']           = array( 'margin' => $this->success_message_selector );
		$fields['message_success_padding']          = array( 'padding' => $this->success_message_selector );

		divi_squad()->d4_module_helper->fix_fonts_transition( $fields, 'message_success_text', $this->success_message_selector );
		divi_squad()->d4_module_helper->fix_border_transition( $fields, 'message_success', $this->success_message_selector );
		divi_squad()->d4_module_helper->fix_box_shadow_transition( $fields, 'message_success', $this->success_message_selector );
	}

	/**
	 * Add button transition fields.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @param array $fields Array of fields to add button transition fields to.
	 *
	 * @return void
	 */
	protected function squad_add_button_transition_fields( array &$fields ): void {
		$fields['form_button_background_color'] = array( 'background' => $this->submit_button_selector );
		$fields['form_button_width']            = array( 'width' => $this->submit_button_selector );
		$fields['form_button_height']           = array( 'height' => $this->submit_button_selector );
		$fields['form_button_margin']           = array( 'margin' => $this->submit_button_selector );
		$fields['form_button_padding']          = array( 'padding' => $this->submit_button_selector );

		divi_squad()->d4_module_helper->fix_fonts_transition( $fields, 'form_button_text', $this->submit_button_selector );
		divi_squad()->d4_module_helper->fix_border_transition( $fields, 'form_button', $this->submit_button_selector );
		divi_squad()->d4_module_helper->fix_box_shadow_transition( $fields, 'form_button', $this->submit_button_selector );
	}

	/**
	 * Add checkbox and radio transition fields.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @param array $fields Array of fields to add checkbox and radio transition fields to.
	 *
	 * @return void
	 */
	protected function squad_add_checkbox_radio_transition_fields( array &$fields ): void {
		$fields['form_ch_rad_color'] = array( 'color' => "$this->main_css_element input[type=checkbox], $this->main_css_element input[type=radio]" );
		$fields['form_ch_rad_size']  = array(
			'width'  => "$this->main_css_element input[type=checkbox], $this->main_css_element input[type=radio]",
			'height' => "$this->main_css_element input[type=checkbox], $this->main_css_element input[type=radio]",
		);
	}

	/**
	 * Add generic transition fields.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @param array $fields Array of fields to add generic transition fields to.
	 *
	 * @return void
	 */
	protected function squad_add_generic_transition_fields( array &$fields ): void {
		divi_squad()->d4_module_helper->fix_fonts_transition( $fields, 'field_label_text', "$this->form_selector label, $this->form_selector legend" );
		divi_squad()->d4_module_helper->fix_fonts_transition( $fields, 'placeholder_text', "$this->form_selector input::placeholder, $this->form_selector select::placeholder, $this->form_selector textarea::placeholder" );

		$fields['background_layout'] = array( 'color' => $this->form_selector );
	}

	/**
	 * Generate all styles for the module.
	 *
	 * Here is an inline example of how to use this method:
	 * ```
	 * // Generate all styles for the current module
	 * $this->squad_generate_all_styles($this->props);
	 *
	 * // Generate styles with custom attributes
	 * $custom_attrs = array_merge($this->props, ['custom_field' => 'value']);
	 * $this->squad_generate_all_styles($custom_attrs);
	 * ```
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @param array $attrs List of attributes.
	 *
	 * @return void
	 */
	protected function squad_generate_all_styles( array $attrs ): void {
		/**
		 * Fires before generating all module styles.
		 *
		 * @since 3.2.0
		 *
		 * @param array       $attrs  The style attributes
		 * @param Form_Styler $module The current Form_Styler instance
		 */
		do_action( 'divi_squad_form_styler_before_generate_styles', $attrs, $this );

		$attrs = array_merge( $attrs, $this->props );

		/**
		 * Filter the attributes before generating styles
		 *
		 * @since 3.2.0
		 *
		 * @param array $attrs The combined attributes
		 * @param array $props The original props
		 */
		$attrs = (array) apply_filters( 'divi_squad_form_styler_style_attributes', $attrs, $this->props );

		$options = $this->squad_get_module_stylesheet_selectors( $attrs );

		/**
		 * Filter the style options before generating styles
		 *
		 * @since 3.2.0
		 *
		 * @param array $options The style options
		 * @param array $attrs   The attributes
		 */
		$options = (array) apply_filters( 'divi_squad_form_styler_style_options', $options, $attrs );

		$this->squad_form_styler_generate_module_styles( $attrs, $options );

		/**
		 * Fires after generating all module styles.
		 *
		 * @since 3.2.0
		 *
		 * @param array  $attrs   The style attributes used
		 * @param array  $options The style options used
		 * @param string $slug    The module slug
		 */
		do_action( 'divi_squad_form_styler_after_generate_styles', $attrs, $options, $this->slug );
	}

	/**
	 * Get module stylesheet selectors.
	 *
	 * Here is an inline example of how to use this method:
	 * ```
	 * // Get all stylesheet selectors for the current module
	 * $selectors = $this->squad_get_module_stylesheet_selectors($this->props);
	 *
	 * // Get selectors with custom attributes
	 * $custom_attrs = array_merge($this->props, ['custom_field' => 'value']);
	 * $custom_selectors = $this->squad_get_module_stylesheet_selectors($custom_attrs);
	 * ```
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @param array $attrs List of attributes.
	 *
	 * @return array Array of stylesheet selectors.
	 */
	protected function squad_get_module_stylesheet_selectors( array $attrs ): array {
		$options = array();

		$this->squad_get_background_stylesheet_options( $options );
		$this->squad_add_checkbox_radio_stylesheet_options( $options );
		$this->squad_add_button_width_stylesheet_option( $options, $attrs );
		$this->squad_add_margin_padding_stylesheet_options( $options );

		return $options;
	}

	/**
	 * Add background options to the stylesheet selectors.
	 *
	 * This method populates the provided options array with background styling options
	 * for various form elements.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @param array $options Reference to the array of options to be populated with background options.
	 */
	protected function squad_get_background_stylesheet_options( array &$options ): void {
		$option_fields = $this->squad_get_background_stylesheet_option_fields();
		foreach ( $option_fields as $key => $selector ) {
			$options[ $key ] = array(
				'type'           => 'background',
				'selector'       => $selector,
				'selector_hover' => $this->squad_get_hover_selector( $selector ),
			);
		}
	}

	/**
	 * Get background option fields for various form elements.
	 *
	 * This method defines the selectors for applying background styles
	 * to different components of the form, such as the wrapper, fields, buttons,
	 * and message areas.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @return array An associative array of form elements and their corresponding CSS selectors.
	 */
	protected function squad_get_background_stylesheet_option_fields(): array {
		$option_fields = array(
			'form_wrapper_background'    => $this->form_selector,
			'fields_background'          => $this->field_selector,
			'form_button_background'     => $this->submit_button_selector,
			'message_error_background'   => $this->error_message_selector,
			'message_success_background' => $this->success_message_selector,
		);

		/**
		 * Filters the background option fields for the Form Styler.
		 *
		 * This filter allows modification of the background option fields
		 * before they're used in the module. It can be used to add new elements,
		 * modify existing ones, or remove elements from the background styling options.
		 *
		 * @since 3.2.0
		 *
		 * @param array $option_fields An associative array of background options and their CSS selectors.
		 *                             Default options include:
		 *                             - form_wrapper_background: The form
		 *                             - fields_background: Form fields
		 *                             - form_button_background: Submit button
		 *                             - message_error_background: Error message area
		 *                             - message_success_background: Success message area
		 */
		return apply_filters( 'divi_squad_form_styler_get_background_option_fields', $option_fields );
	}

	/**
	 * Add checkbox and radio options to the stylesheet selectors.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @param array $options Array of options to add checkbox and radio options to.
	 *
	 * @return void
	 */
	protected function squad_add_checkbox_radio_stylesheet_options( array &$options ): void {
		$checkbox_radio_selector      = "$this->form_selector input[type=checkbox], $this->form_selector input[type=radio]";
		$options['form_ch_rad_color'] = array(
			'type'           => 'default',
			'selector'       => $checkbox_radio_selector,
			'hover_selector' => "$checkbox_radio_selector:hover",
			'css_property'   => 'accent-color',
			'data_type'      => 'text',
		);
		$options['form_ch_rad_size']  = array(
			'type'      => 'default',
			'data_type' => 'range',
			'options'   => array(
				array(
					'selector'       => $checkbox_radio_selector,
					'hover_selector' => "$checkbox_radio_selector:hover",
					'css_property'   => 'width',
				),
				array(
					'selector'       => $checkbox_radio_selector,
					'hover_selector' => "$checkbox_radio_selector:hover",
					'css_property'   => 'height',
				),
			),
		);
	}

	/**
	 * Add button width option to the stylesheet selectors.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @param array $options Array of options to add button width option to.
	 * @param array $attrs   List of attributes.
	 *
	 * @return void
	 */
	protected function squad_add_button_width_stylesheet_option( array &$options, array $attrs ): void {
		if ( ! empty( $attrs['form_button_custom_width'] ) && 'on' === $attrs['form_button_custom_width'] ) {
			$options['form_button_width'] = array(
				'type'           => 'default',
				'selector'       => $this->submit_button_selector,
				'hover_selector' => $this->squad_get_hover_selector( $this->submit_button_selector ),
				'css_property'   => 'width',
				'data_type'      => 'range',
			);
		}
	}

	/**
	 * Add margin and padding options to the stylesheet selectors.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @param array $options Array of options to add margin and padding options to.
	 *
	 * @return void
	 */
	protected function squad_add_margin_padding_stylesheet_options( array &$options ): void {
		$option_fields = $this->squad_get_margin_padding_stylesheet_option_fields();
		foreach ( $option_fields as $key => $selector ) {
			foreach ( array( 'margin', 'padding' ) as $type ) {
				$options[ "{$key}_$type" ] = array(
					'type'           => $type,
					'selector'       => $selector,
					'hover_selector' => $this->squad_get_hover_selector( $selector ),
					'css_property'   => $type,
				);
			}
		}
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
	 * @return array An associative array of form elements and their corresponding CSS selectors.
	 */
	protected function squad_get_margin_padding_stylesheet_option_fields(): array {
		$option_fields = array(
			'wrapper'         => $this->form_selector,
			'field'           => $this->field_selector,
			'form_button'     => $this->submit_button_selector,
			'message_error'   => $this->error_message_selector,
			'message_success' => $this->success_message_selector,
		);

		/**
		 * Filters the margin and padding option fields for the Form Styler.
		 *
		 * This filter allows modification of the margin and padding option fields
		 * before they're used in the module. It can be used to add new elements,
		 * modify existing ones, or remove elements from the styling options.
		 *
		 * @since 3.2.0
		 *
		 * @param array $option_fields                An associative array of form elements and their CSS selectors.
		 *                                            Default elements include:
		 *                                            - wrapper: The form
		 *                                            - field: Form fields
		 *                                            - form_button: Submit button
		 *                                            - message_error: Error message area
		 *                                            - message_success: Success message area
		 */
		return apply_filters( 'divi_squad_form_styler_get_margin_padding_option_fields', $option_fields );
	}

	/**
	 * Generate module styles.
	 *
	 * Here is an inline example of how to use this method:
	 * ```
	 * // Generate styles for the current module
	 * $selectors = $this->squad_get_module_stylesheet_selectors($this->props);
	 * $this->squad_form_styler_generate_module_styles($this->props, $selectors);
	 *
	 * // Generate styles with custom attributes and selectors
	 * $custom_attrs = array_merge($this->props, ['custom_field' => 'value']);
	 * $custom_selectors = $this->squad_get_module_stylesheet_selectors($custom_attrs);
	 * $this->squad_form_styler_generate_module_styles($custom_attrs, $custom_selectors);
	 * ```
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @param array $attrs   List of attributes.
	 * @param array $options Control attributes.
	 *
	 * @return void
	 */
	protected function squad_form_styler_generate_module_styles( array $attrs, array $options ): void {
		/**
		 * Fires before generating individual module styles.
		 *
		 * @since 3.2.0
		 *
		 * @param array $attrs   The style attributes
		 * @param array $options The style options
		 */
		do_action( 'divi_squad_form_styler_before_generate_module_styles', $attrs, $options );

		foreach ( $options as $option_key => $option ) {
			/**
			 * Fires before generating style for a specific option.
			 *
			 * @since 3.2.0
			 *
			 * @param string $option_key The option key
			 * @param array  $option     The option settings
			 * @param array  $attrs      The style attributes
			 */
			do_action( 'divi_squad_form_styler_before_generate_option_style', $option_key, $option, $attrs );

			switch ( $option['type'] ) {
				case 'background':
					$this->squad_generate_background_style( $attrs, $option_key, $option );
					break;
				case 'default':
					$this->squad_generate_default_style( $option_key, $option );
					break;
				case 'margin':
				case 'padding':
					$this->squad_generate_margin_padding_style( $option_key, $option );
					break;
			}

			/**
			 * Fires after generating style for a specific option.
			 *
			 * @since 3.2.0
			 *
			 * @param string $option_key The option key
			 * @param array  $option     The option settings
			 * @param array  $attrs      The style attributes
			 */
			do_action( 'divi_squad_form_styler_after_generate_option_style', $option_key, $option, $attrs );
		}

		/**
		 * Fires after generating all individual module styles.
		 *
		 * @since 3.2.0
		 *
		 * @param array $attrs   The style attributes
		 * @param array $options The style options
		 */
		do_action( 'divi_squad_form_styler_after_generate_module_styles', $attrs, $options );
	}

	/**
	 * Generate background style.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @param array  $attrs      List of attributes.
	 * @param string $option_key Option key.
	 * @param array  $option     Option configuration.
	 *
	 * @return void
	 */
	protected function squad_generate_background_style( array $attrs, string $option_key, array $option ): void {
		$prop_name_aliases = array(
			"use_{$option_key}_color_gradient" => "{$option_key}_use_color_gradient",
			$option_key                        => "{$option_key}_color",
		);

		et_pb_background_options()->get_background_style(
			array(
				'base_prop_name'         => $option_key,
				'props'                  => $attrs,
				'function_name'          => $this->slug,
				'selector'               => $option['selector'],
				'selector_hover'         => $option['selector_hover'],
				'selector_sticky'        => $option['selector'],
				'important'              => ' !important',
				'use_background_video'   => false,
				'use_background_pattern' => false,
				'use_background_mask'    => false,
				'prop_name_aliases'      => $prop_name_aliases,
			)
		);
	}

	/**
	 * Generate default style.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @param string $option_key Option key.
	 * @param array  $option     Option configuration.
	 *
	 * @return void
	 */
	protected function squad_generate_default_style( string $option_key, array $option ): void {
		if ( isset( $option['options'] ) && is_array( $option['options'] ) ) {
			foreach ( $option['options'] as $nested_option ) {
				$this->squad_generate_single_style( $option_key, $nested_option, $option['data_type'] );
			}
		} else {
			$this->squad_generate_single_style( $option_key, $option, $option['data_type'] );
		}
	}

	/**
	 * Generate single style.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @param string $option_key Option key.
	 * @param array  $option     Option configuration.
	 * @param string $data_type  Data type.
	 *
	 * @return void
	 */
	protected function squad_generate_single_style( string $option_key, array $option, string $data_type ): void {
		/**
		 * Filter the style generation parameters
		 *
		 * @since 3.2.0
		 *
		 * @param array  $style_params Style generation parameters
		 * @param string $option_key   Option key
		 * @param array  $option       Option configuration
		 */
		$style_params = (array) apply_filters(
			'divi_squad_form_styler_single_style_params',
			array(
				'base_attr_name' => $option_key,
				'selector'       => $option['selector'],
				'hover_selector' => $option['hover_selector'] ?? '',
				'css_property'   => $option['css_property'],
				'render_slug'    => $this->slug,
				'type'           => $data_type,
			),
			$option_key,
			$option
		);

		$this->generate_styles( $style_params );
	}

	/**
	 * Generate margin and padding style.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @param string $option_key Option key.
	 * @param array  $option     Option configuration.
	 *
	 * @return void
	 */
	protected function squad_generate_margin_padding_style( string $option_key, array $option ): void {
		$this->squad_utils->field_css_generations->generate_margin_padding_styles(
			array(
				'field'          => $option_key,
				'selector'       => $option['selector'],
				'hover_selector' => $option['hover_selector'],
				'css_property'   => $option['type'],
				'type'           => $option['type'],
			)
		);
	}

	/**
	 * Remove pre-assigned fields from the fields array.
	 *
	 * Here is an inline example of how to use this method:
	 * ```
	 * // Remove specific fields from the fields array
	 * $all_fields = $this->get_fields();
	 * $fields_to_remove = ['field1', 'field2'];
	 * $updated_fields = $this->squad_remove_pre_assigned_fields($all_fields, $fields_to_remove);
	 *
	 * // Remove fields based on a condition
	 * $fields_to_remove = [];
	 * foreach ($all_fields as $key => $field) {
	 *     if (strpos($key, 'deprecated_') === 0) {
	 *         $fields_to_remove[] = $key;
	 *     }
	 * }
	 * $updated_fields = $this->squad_remove_pre_assigned_fields($all_fields, $fields_to_remove);
	 *  ```
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @param array $fields   List of fields.
	 * @param array $removals List of removable fields.
	 *
	 * @return array Updated fields array.
	 */
	protected function squad_remove_pre_assigned_fields( array $fields, array $removals ): array {
		/**
		 * Filters the list of fields to be removed.
		 *
		 * @since 3.2.0
		 *
		 * @param array $removals List of field keys to be removed.
		 * @param array $fields   The complete list of fields.
		 */
		$removals = (array) apply_filters( 'divi_squad_form_styler_removable_fields', $removals, $fields );

		if ( count( $fields ) > 1 && count( $removals ) > 1 ) {
			foreach ( $removals as $removal ) {
				unset( $fields[ $removal ] );
			}
		}

		return $fields;
	}
}
