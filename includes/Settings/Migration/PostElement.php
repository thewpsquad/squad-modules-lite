<?php
/**
 * Migration process to migrate image into Featured Image of Post Element modules.
 *
 * @package DiviSquad\Settings
 * @author  The WP Squad <support@squadmodules.com>
 * @since   2.0.0
 */

namespace DiviSquad\Settings\Migration;

use DiviSquad\Settings\Migration;

/**
 * Migration process to migrate image into Featured Image of Post Element modules.
 *
 * @since 2.0.0
 */
class PostElement extends Migration {

	/**
	 * Migration Version
	 *
	 * @since 2.0.0
	 *
	 * @var string
	 */
	public string $version = '4.24';

	/**
	 * Get all modules affected.
	 *
	 * @since 2.0.0
	 *
	 * @return array<string> List of modules.
	 */
	public function get_modules(): array {
		return array( 'disq_post_grid_child', 'disq_cpt_grid_child' );
	}

	/**
	 * Get all fields to need to be migrated.
	 *
	 * Contains an array with:
	 * - key as new field
	 * - value consists affected fields as old field and module location
	 *
	 * @since 2.0.0
	 *
	 * @return array<string, array<string, array<string, array<string>>>> New and old fields need to be migrated.
	 */
	public function get_fields(): array {
		return array(
			'element' => array(
				'affected_fields' => array(
					'element' => $this->get_modules(),
				),
			),
		);
	}

	/**
	 * Migrate from old value into new value.
	 *
	 * @param string               $field_name       The field name.
	 * @param mixed                $current_value    The current value.
	 * @param string               $module_slug      The module slug.
	 * @param mixed                $saved_value      The saved value.
	 * @param string               $saved_field_name The saved field name.
	 * @param array<string, mixed> $attrs            The attributes.
	 * @param mixed                $content          The content.
	 * @param string               $module_address   The module address.
	 *
	 * @return mixed
	 */
	public function migrate( string $field_name, $current_value, string $module_slug, $saved_value, string $saved_field_name, array $attrs, $content, string $module_address ) {
		return 'image' === $saved_value ? 'featured_image' : $saved_value;
	}
}
