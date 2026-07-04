<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Advanced Custom Field Definitions
 *
 * This file contains the Advanced class which extends Definition and
 * implements DefinitionInterface. It provides more sophisticated
 * implementations for custom fields, supporting complex field types
 * and advanced filtering options.
 *
 * @since   3.1.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Utils\Elements\Custom_Fields\Definitions;

use DiviSquad\Builder\Utils\Elements\Custom_Fields\Definition;

/**
 * Advanced Custom Field Definitions Class
 *
 * Implements advanced custom field definitions with support for complex field types
 * and sophisticated filtering options.
 *
 * @since   3.1.0
 * @package DiviSquad
 */
class Advanced extends Definition {

	/**
	 * Initialize the definition.
	 *
	 * Sets up the common fields, empty fields, and not eligible fields.
	 *
	 * @since 3.1.0
	 *
	 * @return void
	 */
	protected function init(): void {
		// Register common fields.
		$this->register_common_field(
			'element',
			array(
				'affects' => array(
					'element_advanced_custom_field_none_notice',
					'element_advanced_custom_field_type',
					'element_advanced_custom_field_not_eligible_notice',
				),
			)
		);

		// Register empty fields.
		$this->register_empty_field(
			'element_advanced_custom_field_none_notice',
			array(
				'type'            => 'disq_custom_warning',
				'message'         => esc_html__( 'You need to add one or more fields in the custom post type to see custom fields.', 'squad-modules-for-divi' ),
				'depends_show_if' => 'advanced_custom_field',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'elements',
			)
		);

		// Register not eligible fields.
		$this->register_not_eligible_field(
			'element_advanced_custom_field_not_eligible_notice',
			array(
				'type'            => 'disq_custom_warning',
				'message'         => esc_html__( 'You need to enable and install ACF plugin to see advanced custom fields.', 'squad-modules-for-divi' ),
				'depends_show_if' => 'advanced_custom_field',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'elements',
			)
		);

		// Initialize associated fields (will be populated in get_associated_fields).
	}

	/**
	 * Get default fields for a specific post type.
	 *
	 * This method returns an array of default custom fields for the given post type,
	 * taking into account any provided options and potentially including advanced field types.
	 *
	 * @since 3.1.0
	 *
	 * @param string $post_type The post type for which to retrieve default fields.
	 * @param array  $options   Additional options to customize the returned fields.
	 *
	 * @return array<string, array<string, mixed>> An array of default custom field definitions for the specified post type.
	 */
	public function get_default_fields( string $post_type, array $options ): array {
		// If we already have these fields registered, return them from parent.
		if ( isset( $this->post_type_fields[ $post_type ] ) ) {
			return parent::get_default_fields( $post_type, $options );
		}

		// Otherwise, register them now.
		$this->register_common_field(
			'element',
			array(
				'affects' => array(
					"element_advanced_custom_field_$post_type",
				),
			)
		);

		$this->register_post_type_field(
			$post_type,
			"element_advanced_custom_field_$post_type",
			divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Custom Field', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Choose a custom field to display for current post.', 'squad-modules-for-divi' ),
					'options'         => $options,
					'default'         => 'publish',
					'depends_show_if' => 'advanced_custom_field',
					'tab_slug'        => 'general',
					'toggle_slug'     => 'elements',
				)
			)
		);

		// Return the newly registered fields.
		return parent::get_default_fields( $post_type, $options );
	}

	/**
	 * Get associated fields.
	 *
	 * This method returns an array of custom fields that are associated
	 * with the current context or implementation, potentially including
	 * fields with advanced functionality or relationships.
	 *
	 * @since 3.1.0
	 *
	 * @param array<string, string> $fields_types Mapping of field type identifiers to their human-readable names.
	 *
	 * @return array<string, array<string, mixed>> An array of associated custom field definitions.
	 */
	public function get_associated_fields( array $fields_types = array() ): array {
		// If we already have associated fields registered, return them.
		if ( count( $this->associated_fields ) > 0 ) {
			return parent::get_associated_fields( $fields_types );
		}

		// Otherwise, register them now.
		$this->register_associated_field(
			'element_advanced_custom_field_type',
			divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Custom Field Type', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Choose a custom field to display for current post.', 'squad-modules-for-divi' ),
					'options'         => $fields_types,
					'default'         => 'text',
					'depends_show_if' => 'advanced_custom_field',
					'affects'         => array(
						'element_advanced_custom_field_email_text',
						'element_advanced_custom_field_url_text',
						'element_advanced_custom_field_url_target',
						'element_advanced_custom_field_image_width',
						'element_advanced_custom_field_image_height',
						'element_advanced_custom_field_before',
						'element_advanced_custom_field_after',
					),
					'tab_slug'        => 'general',
					'toggle_slug'     => 'elements',
				)
			)
		);

		$this->register_associated_field(
			'element_advanced_custom_field_email_text',
			array(
				'label'           => esc_html__( 'Custom Email Text', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'The after text of your custom email field text will appear in with your post element.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'depends_show_if' => 'email',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'elements',
				'dynamic_content' => 'text',
			)
		);

		$this->register_associated_field(
			'element_advanced_custom_field_url_text',
			array(
				'label'           => esc_html__( 'Custom URL Text', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'The after text of your custom url field text will appear in with your post element.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => esc_html__( 'Visit the link', 'squad-modules-for-divi' ),
				'depends_show_if' => 'url',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'elements',
				'dynamic_content' => 'text',
			)
		);

		$this->register_associated_field(
			'element_advanced_custom_field_url_target',
			divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Custom URL Target', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Choose a custom url field target to open new tab or self.', 'squad-modules-for-divi' ),
					'options'         => array(
						'_self'  => esc_html__( 'In The Same Window', 'squad-modules-for-divi' ),
						'_blank' => esc_html__( 'In The New Tab', 'squad-modules-for-divi' ),
					),
					'default'         => '_self',
					'depends_show_if' => 'url',
					'tab_slug'        => 'general',
					'toggle_slug'     => 'elements',
				)
			)
		);

		$this->register_associated_field(
			'element_advanced_custom_field_image_width',
			divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Custom Image Width', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Here you can choose custom image width will appear in with your post element.', 'squad-modules-for-divi' ),
					'range_settings'  => array(
						'min'  => '1',
						'max'  => '200',
						'step' => '1',
					),
					'default'         => '100px',
					'depends_show_if' => 'image',
					'tab_slug'        => 'general',
					'toggle_slug'     => 'elements',
				)
			)
		);

		$this->register_associated_field(
			'element_advanced_custom_field_before',
			array(
				'label'           => esc_html__( 'Custom Field Before Text', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'The before text of your custom field text will appear in with your post element.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'depends_show_if' => 'text',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'elements',
				'dynamic_content' => 'text',
			)
		);

		$this->register_associated_field(
			'element_advanced_custom_field_after',
			array(
				'label'           => esc_html__( 'Custom Field After Text', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'The after text of your custom field text will appear in with your post element.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'depends_show_if' => 'text',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'elements',
				'dynamic_content' => 'text',
			)
		);

		// Return the newly registered fields.
		return parent::get_associated_fields( $fields_types );
	}

	/**
	 * Determines if this definition processor is eligible to provide field definitions.
	 *
	 * For Advanced custom fields, this checks if the ACF plugin is active.
	 *
	 * @since 3.1.0
	 *
	 * @return bool Whether this definition processor can be used.
	 */
	public function is_eligible(): bool {
		return function_exists( 'acf' ) || class_exists( 'ACF' );
	}
}
