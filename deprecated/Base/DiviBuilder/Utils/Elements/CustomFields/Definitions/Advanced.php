<?php
/**
 * Advanced Custom Field Definitions
 *
 * This file contains the Advanced class which extends CustomFieldDefinitions
 * and implements CustomFieldDefinitionsInterface. It provides more sophisticated
 * implementations for custom fields, potentially including support for complex
 * field types or advanced filtering options.
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
 * Advanced Custom Field Definitions Class
 *
 * Implements advanced custom field definitions with support for complex field types
 * and sophisticated filtering options.
 *
 * @package DiviSquad
 * @since   3.1.0
 * @deprecated 3.3.0
 */
class Advanced extends Definition {

	/**
	 * Get common fields that are applicable across different post types.
	 *
	 * This method returns an array of advanced custom fields that are commonly used
	 * and applicable to multiple or all post types, including more complex field types.
	 *
	 * @return array An array of common custom field definitions.
	 */
	public function get_common_fields() {
		return array(
			'element' => array(
				'affects' => array(
					'element_advanced_custom_field_none_notice',
					'element_advanced_custom_field_type',
					'element_advanced_custom_field_not_eligible_notice',
				),
			),
		);
	}

	/**
	 * Get an array of empty fields.
	 *
	 * This method returns an array of custom fields that are considered "empty"
	 * or have no default value, potentially including complex field types.
	 *
	 * @return array An array of empty custom field definitions.
	 */
	public function get_empty_fields() {
		return array(
			'element_advanced_custom_field_none_notice' => array(
				'type'            => 'disq_custom_warning',
				'message'         => esc_html__( 'You need to add one or more fields in the custom post type to see custom fields.', 'squad-modules-for-divi' ),
				'depends_show_if' => 'advanced_custom_field',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'elements',
			),
		);
	}

	/**
	 * Get default fields for a specific post type.
	 *
	 * This method returns an array of default custom fields for the given post type,
	 * taking into account any provided options and potentially including advanced field types.
	 *
	 * @param string $post_type The post type for which to retrieve default fields.
	 * @param array  $options   Additional options to customize the returned fields.
	 *
	 * @return array An array of default custom field definitions for the specified post type.
	 */
	public function get_default_fields( $post_type, $options ) {
		return array(
			'element'                                  => array(
				'affects' => array(
					"element_advanced_custom_field_$post_type",
				),
			),
			"element_advanced_custom_field_$post_type" => Utils::add_select_box_field(
				esc_html__( 'Custom Field', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Choose a custom field to display for current post.', 'squad-modules-for-divi' ),
					'options'         => $options,
					'default'         => 'publish',
					'depends_show_if' => 'advanced_custom_field',
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
	 * with the current context or implementation, potentially including
	 * fields with advanced functionality or relationships.
	 *
	 * @param array $fields_types Collect custom fields types.
	 *
	 * @return array An array of associated custom field definitions.
	 */
	public function get_associated_fields( $fields_types = array() ) {
		return array(
			'element_advanced_custom_field_type'        => Utils::add_select_box_field(
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
			),
			'element_advanced_custom_field_email_text'  => array(
				'label'           => esc_html__( 'Custom Email Text', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'The after text of your custom email field text will appear in with your post element.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'depends_show_if' => 'email',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'elements',
				'dynamic_content' => 'text',
			),
			'element_advanced_custom_field_url_text'    => array(
				'label'           => esc_html__( 'Custom URL Text', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'The after text of your custom url field text will appear in with your post element.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => esc_html__( 'Visit the link', 'squad-modules-for-divi' ),
				'depends_show_if' => 'url',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'elements',
				'dynamic_content' => 'text',
			),
			'element_advanced_custom_field_url_target'  => Utils::add_select_box_field(
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
			),
			'element_advanced_custom_field_image_width' => Utils::add_range_field(
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
			),
			'element_advanced_custom_field_before'      => array(
				'label'           => esc_html__( 'Custom Field Before Text', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'The before text of your custom field text will appear in with your post element.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'depends_show_if' => 'text',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'elements',
				'dynamic_content' => 'text',
			),
			'element_advanced_custom_field_after'       => array(
				'label'           => esc_html__( 'Custom Field After Text', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'The after text of your custom field text will appear in with your post element.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'depends_show_if' => 'text',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'elements',
				'dynamic_content' => 'text',
			),
		);
	}

	/**
	 * Get fields that are not eligible.
	 *
	 * This method returns an array of custom fields that are considered
	 * not eligible for use in the current context or implementation,
	 * potentially based on advanced criteria or user roles.
	 *
	 * @return array An array of custom field definitions that are not eligible.
	 */
	public function get_not_eligible_fields() {
		return array(
			'element_advanced_custom_field_not_eligible_notice' => array(
				'type'            => 'disq_custom_warning',
				'message'         => esc_html__( 'You need to enable install ACF plugin to see advanced custom fields.', 'squad-modules-for-divi' ),
				'depends_show_if' => 'advanced_custom_field',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'elements',
			),
		);
	}
}
