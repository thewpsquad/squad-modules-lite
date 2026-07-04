<?php // phpcs:ignore WordPress.Files.FileName

/**
 * WordPress Custom Field Definitions
 *
 * This file contains the WordPress class which extends Definition
 * and implements DefinitionInterface. It provides specific
 * implementations for WordPress standard custom fields in the context of Divi Builder.
 *
 * @since   3.1.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Utils\Elements\Custom_Fields\Definitions;

use DiviSquad\Builder\Utils\Elements\Custom_Fields\Definition;

/**
 * WordPress Custom Field Definitions Class
 *
 * Implements WordPress-specific custom field definitions for use with Divi Builder.
 *
 * @since   3.1.0
 * @package DiviSquad
 */
class WordPress extends Definition {

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
					'element_custom_field_none_notice',
					'element_custom_field_before',
					'element_custom_field_after',
				),
			)
		);

		// Register empty fields.
		$this->register_empty_field(
			'element_custom_field_none_notice',
			array(
				'type'            => 'disq_custom_warning',
				'message'         => esc_html__( 'You need to add one or more fields in the current post to see custom fields.', 'squad-modules-for-divi' ),
				'depends_show_if' => 'custom_field',
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
	 * taking into account any provided options.
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
					"element_custom_field_$post_type",
				),
			)
		);

		$this->register_post_type_field(
			$post_type,
			"element_custom_field_$post_type",
			divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Custom Field', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Choose a custom field to display for the current post.', 'squad-modules-for-divi' ),
					'options'         => $options,
					'depends_show_if' => 'custom_field',
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
	 * with the current context or implementation in WordPress.
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

		// Otherwise, register them now
		$this->register_associated_field(
			'element_custom_field_before',
			array(
				'label'           => esc_html__( 'Custom Field Before Text', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'The before text of your custom field text will appear in with your post element.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'depends_show_if' => 'custom_field',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'elements',
				'dynamic_content' => 'text',
			)
		);

		$this->register_associated_field(
			'element_custom_field_after',
			array(
				'label'           => esc_html__( 'Custom Field After Text', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'The after text of your custom field text will appear in with your post element.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'depends_show_if' => 'custom_field',
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
	 * WordPress custom fields are always eligible (built into core).
	 *
	 * @since 3.1.0
	 *
	 * @return bool Whether this definition processor can be used.
	 */
	public function is_eligible(): bool {
		return true;
	}
}
