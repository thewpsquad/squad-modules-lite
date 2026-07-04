<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Interface for Squad Modules Migration.
 *
 * @since   3.0.0
 * @package DiviSquad\Settings
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Settings;

/**
 * Migration Interface
 *
 * @since   3.0.0
 * @package DiviSquad\Base\Factories\ModuleMigration
 */
interface Migration_Interface {

	/**
	 * Initialize migration.
	 *
	 * @return void
	 */
	public static function init();

	/**
	 * Get all fields to need to be migrated.
	 *
	 * Contains an array with:
	 * - key as new field
	 * - value consists affected fields as old field and module location
	 *
	 * @since 3.0.0
	 *
	 * @return array<string, array<string, array<string, array<string>>>> New and old fields need   to be migrated.
	 */
	public function get_fields(): array;

	/**
	 * Get all modules affected.
	 *
	 * @since 3.0.0
	 *
	 * @return array<string> List of modules.
	 */
	public function get_modules(): array;

	/**
	 * Migrate from old value into new value.
	 *
	 * @since 3.0.0
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
	public function migrate( string $field_name, $current_value, string $module_slug, $saved_value, string $saved_field_name, array $attrs, $content, string $module_address );

	/**
	 * Get all modules to need to be migrated.
	 *
	 * @return array<string> List of modules.
	 */
	public function get_content_migration_modules(): array;

	/**
	 * This could have been written as abstract, but it's not as common to be expected to be implemented by every migration
	 *
	 * @param string               $module_slug Internal system name for the module type.
	 * @param array<string, mixed> $attrs       Shortcode attributes.
	 * @param mixed                $content     Text/HTML content within the current module.
	 *
	 * @return mixed
	 */
	public function migrate_content( string $module_slug, array $attrs, $content );
}
