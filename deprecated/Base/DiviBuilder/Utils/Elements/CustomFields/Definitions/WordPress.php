<?php // phpcs:ignore WordPress.Files.FileName
/**
 * WordPress Custom Field Definitions
 *
 * This file contains the WordPress class which extends CustomFieldDefinitions
 * and implements CustomFieldDefinitionsInterface. It provides specific
 * implementations for WordPress custom fields in the context of Divi Builder.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   3.1.0
 * @deprecated 3.3.0
 */

namespace DiviSquad\Base\DiviBuilder\Utils\Elements\CustomFields\Definitions;

use DiviSquad\Base\DiviBuilder\Utils;
use DiviSquad\Base\DiviBuilder\Utils\Elements\CustomFields\Definition;

/**
 * WordPress Custom Field Definitions Class
 *
 * Implements WordPress-specific custom field definitions for use with Divi Builder.
 *
 * @package DiviSquad
 * @since 3.1.0
 * @deprecated 3.3.0
 */
class WordPress extends Definition {

	/**
	 * Get common fields that are applicable across different post types.
	 *
	 * This method returns an array of custom fields that are commonly used
	 * and applicable to multiple or all post types in WordPress.
	 *
	 * @return array An array of common custom field definitions.
	 */
	public function get_common_fields() {
		return array(
			'element' => array(
				'affects' => array(
					'element_custom_field_none_notice',
					'element_custom_field_before',
					'element_custom_field_after',
				),
			),
		);
	}

	/**
	 * Get an array of empty fields.
	 *
	 * This method returns an array of custom fields that are considered "empty"
	 * or have no default value, specifically for WordPress integration.
	 *
	 * @return array An array of empty custom field definitions.
	 */
	public function get_empty_fields() {
		return array(
			'element_custom_field_none_notice' => array(
				'type'            => 'disq_custom_warning',
				'message'         => esc_html__( 'You need to add one or more fields in the current post to see custom fields.', 'squad-modules-for-divi' ),
				'depends_show_if' => 'custom_field',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'elements',
			),
		);
	}

	/**
	 * Get default fields for a specific post type.
	 *
	 * This method returns an array of default custom fields for the given post type,
	 * taking into account any provided options.
	 *
	 * @param string $post_type The post type for which to retrieve default fields.
	 * @param array  $options   Additional options to customize the returned fields.
	 *
	 * @return array An array of default custom field definitions for the specified post type.
	 */
	public function get_default_fields( $post_type, $options ) {
		return array(
			'element'                         => array(
				'affects' => array(
					"element_custom_field_$post_type",
				),
			),
			"element_custom_field_$post_type" => Utils::add_select_box_field(
				esc_html__( 'Custom Field', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Choose a custom field to display for the current post.', 'squad-modules-for-divi' ),
					'options'         => $options,
					'depends_show_if' => 'custom_field',
					'tab_slug'        => 'general',
					'toggle_slug'     => 'elements',
				)
			),
		);
	}

	/**
	 * Get associated fields.
	 *
	 * This method returns an array of custom fields that are associated
	 * with the current context or implementation in WordPress.
	 *
	 * @param array $fields_types Collect custom fields types.
	 *
	 * @return array An array of associated custom field definitions.
	 */
	public function get_associated_fields( $fields_types = array() ) {
		return array(
			'element_custom_field_before' => array(
				'label'           => esc_html__( 'Custom Field Before Text', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'The before text of your custom field text will appear in with your post element.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'depends_show_if' => 'custom_field',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'elements',
				'dynamic_content' => 'text',
			),
			'element_custom_field_after'  => array(
				'label'           => esc_html__( 'Custom Field After Text', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'The after text of your custom field text will appear in with your post element.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'depends_show_if' => 'custom_field',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'elements',
				'dynamic_content' => 'text',
			),
		);
	}

	/**
	 * Get fields when custom fields are not eligible.
	 *
	 * This method returns an array of custom fields that are considered
	 * not eligible for use in the current WordPress context or implementation.
	 *
	 * @return array An array of custom field definitions when custom fields are not eligible.
	 */
	public function get_not_eligible_fields() {
		return array();
	}
}
