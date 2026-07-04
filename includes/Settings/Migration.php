<?php
/**
 * Main migration class.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   2.0.0
 * @since   3.0.0 move to Base\Factories\ModuleMigration
 */

namespace DiviSquad\Settings;

use ET_Builder_Element;

/**
 * Class Migration
 *
 * @package DiviSquad
 * @since   2.0.0
 * @since   3.0.0 move to Base\Factories\ModuleMigration
 */
abstract class Migration implements MigrationInterface {

	/**
	 * Used to migrate field names.
	 *
	 * @var array
	 */
	public static array $field_name_migrations = array();
	/**
	 * Array of hooks.
	 *
	 * @var array
	 */
	public static array $hooks = array(
		'the_content',
		'admin_enqueue_scripts',
		'et_pb_get_backbone_templates',
		'wp_ajax_et_pb_execute_content_shortcodes',
		'wp_ajax_et_fb_get_saved_layouts',
		'wp_ajax_et_fb_retrieve_builder_data',
	);
	/**
	 * The last checked hook.
	 *
	 * @var string
	 */
	public static string $last_hook_checked = '';
	/**
	 * Last hook check decision.
	 *
	 * @var bool
	 */
	public static bool $last_hook_check_decision = false;
	/**
	 * The largest version of the migrations defined in the migration array.
	 *
	 * @var string
	 */
	public static string $max_version = '4.24.1';
	/**
	 * Array of already migrated data.
	 *
	 * @var array
	 */
	public static array $migrated = array();
	/**
	 * Array of migrations in format( [ 'version' => 'name of migration script' ] ).
	 *
	 * @var string[]
	 */
	public static array $migrations = array(
		'4.24' => Migration\PostElement::class,
	);
	/**
	 * Migrations by version.
	 *
	 * @var array
	 */
	public static array $migrations_by_version = array();
	/**
	 * Used to exclude names in case of BB.
	 *
	 * @var array
	 */
	protected static array $bb_excluded_name_changes = array();
	/**
	 * Used for migrations where we want to separate the logic for
	 * migrating post-attributes and global migrating preset attributes.
	 *
	 * @var bool
	 */
	protected static bool $maybe_global_presets_migration = false;
	/**
	 * Version.
	 *
	 * @var string
	 */
	public string $version;

	/**
	 * Add or not missing fields.
	 *
	 * @var bool
	 */
	public bool $add_missing_fields = false;

	/**
	 * Get all modules to need to be migrated.
	 *
	 * @return array
	 */
	abstract public function get_modules(): array;

	/**
	 * Get all fields to need to be migrated.
	 *
	 * Contains an array with:
	 * - key as new field
	 * - value consists affected fields as old field and module location
	 *
	 * @return array New and old fields need to be migrated.
	 */
	abstract public function get_fields(): array;

	/**
	 * Initialize migration.
	 */
	public static function init() {
		add_filter( 'et_pb_module_processed_fields', array( self::class, 'maybe_override_processed_fields' ), 10, 2 );
		add_filter( 'et_pb_module_shortcode_attributes', array( self::class, 'maybe_override_shortcode_attributes' ), 10, 6 );
		add_filter( 'et_pb_module_content', array( self::class, 'maybe_override_content' ), 10, 4 );
	}

	/**
	 * Remove added filters.
	 *
	 * Used by WPUnit tests.
	 *
	 * @since 4.16.0
	 * @link  https://make.wordpress.org/core/handbook/testing/automated-testing/writing-phpunit-tests/#shared-setup-between-related-tests
	 */
	public static function tear_down() {
		remove_filter( 'et_pb_module_processed_fields', array( self::class, 'maybe_override_processed_fields' ) );
		remove_filter( 'et_pb_module_shortcode_attributes', array( self::class, 'maybe_override_shortcode_attributes' ) );
		remove_filter( 'et_pb_module_content', array( self::class, 'maybe_override_content' ) );
	}

	/**
	 * Maybe override processed fields.
	 *
	 * @param array  $fields      Shortcode fields.
	 * @param string $module_slug Internal system name for the module type.
	 *
	 * @return array
	 */
	public static function maybe_override_processed_fields( array $fields, string $module_slug ): array {
		/**
		 * List of migrations.
		 *
		 * @var Migration[] $migrations
		 */
		$migrations = self::get_migrations( 'all' );

		foreach ( $migrations as $migration ) {
			if ( in_array( $module_slug, $migration->get_modules(), true ) ) {
				$fields = $migration->handle_field_name_migrations( $fields, $module_slug );
			}
		}

		return $fields;
	}

	/**
	 * Get migrations.
	 *
	 * @param string $module_version Module version.
	 *
	 * @return array|mixed
	 */
	public static function get_migrations( string $module_version ) {
		if ( isset( self::$migrations_by_version[ $module_version ] ) ) {
			return self::$migrations_by_version[ $module_version ];
		}

		self::$migrations_by_version[ $module_version ] = array();

		if ( 'all' !== $module_version && version_compare( $module_version, self::$max_version, '>=' ) ) {
			return array();
		}

		/**
		 * List of migrations.
		 *
		 * @since 3.2.0
		 *
		 * @var Migration[] $migrations
		 */
		$migrations = apply_filters( 'divi_squad_builder_module_migrations', self::$migrations );

		foreach ( $migrations as $version => $migration ) {
			if ( 'all' !== $module_version && version_compare( $module_version, $version, '>=' ) ) {
				continue;
			}

			// Create migration instance.
			if ( is_string( $migration ) ) {
				if ( ! class_exists( $migration ) ) {
					continue;
				}

				if ( ! is_subclass_of( $migration, self::class ) ) {
					continue;
				}

				$migration = new $migration();
			}

			// Set version.
			self::$migrations[ $version ] = $migration;

			// Add migration to the list.
			self::$migrations_by_version[ $module_version ][] = $migration;
		}

		return self::$migrations_by_version[ $module_version ];
	}

	/**
	 * Handle field name migrations.
	 *
	 * @param array  $fields      Shortcode fields.
	 * @param string $module_slug Internal system name for the module type.
	 *
	 * @return array
	 */
	public function handle_field_name_migrations( array $fields, string $module_slug ): array {
		if ( ! in_array( $module_slug, $this->get_modules(), true ) ) {
			return $fields;
		}

		foreach ( $this->get_fields() as $field_name => $field_info ) {
			foreach ( $field_info['affected_fields'] as $affected_field => $affected_modules ) {

				if ( $affected_field === $field_name || ! in_array( $module_slug, $affected_modules, true ) ) {
					continue;
				}

				foreach ( $affected_modules as $affected_module ) {
					if ( ! isset( self::$field_name_migrations[ $affected_module ][ $field_name ] ) ) {
						self::$field_name_migrations[ $affected_module ][ $field_name ] = array();
					}

					self::$field_name_migrations[ $affected_module ][ $field_name ][] = $affected_field;
				}
			}
		}

		if ( isset( self::$field_name_migrations[ $module_slug ] ) ) {
			return self::migrate_field_names( $fields, $module_slug, $this->version );
		}

		return $fields;
	}

	/**
	 * Migrate field names.
	 *
	 * @param array  $fields  Shortcode fields.
	 * @param string $slug    Internal system name for the module type.
	 * @param string $version Version of the migration.
	 *
	 * @return array
	 */
	protected static function migrate_field_names( array $fields, string $slug, string $version ) {
		foreach ( self::$field_name_migrations[ $slug ] as $new_name => $old_names ) {
			foreach ( $old_names as $old_name ) {
				if ( ! isset( $fields[ $old_name ] ) ) {
					// Add old to-be-migrated attribute as skipped field if it doesn't exist so its value can be used.
					$fields[ $old_name ] = array( 'type' => 'skip' );
				}

				// For the BB.
				if ( ! in_array( $old_name, self::$bb_excluded_name_changes, true ) ) {
					self::$migrated['field_name_changes'][ $slug ][ $old_name ] = array(
						'new_name' => $new_name,
						'version'  => $version,
					);
				}
			}
		}

		return $fields;
	}

	/**
	 * Maybe override shortcode attributes.
	 *
	 * @param array  $attrs                          Shortcode attributes.
	 * @param array  $unprocessed_attrs              Attributes that have not yet been processed.
	 * @param string $module_slug                    Internal system name for the module type.
	 * @param string $module_address                 Location of the current module on the page.
	 * @param mixed  $content                        Text/HTML content within the current module.
	 * @param bool   $maybe_global_presets_migration Whether to include global presets.
	 *
	 * @return array
	 */
	public static function maybe_override_shortcode_attributes( array $attrs, array $unprocessed_attrs, string $module_slug, string $module_address, $content = '', bool $maybe_global_presets_migration = false ): array {
		if ( empty( $attrs['_builder_version'] ) ) {
			$attrs['_builder_version'] = '3.0.47';
		}

		if ( ! self::should_handle_render( $module_slug ) ) {
			return $attrs;
		}

		self::$maybe_global_presets_migration = $maybe_global_presets_migration;

		/**
		 * List of migrations.
		 *
		 * @var Migration[] $migrations
		 */
		$migrations = self::get_migrations( $attrs['_builder_version'] );

		// Register address-based name module's field name change.
		if ( isset( self::$migrated['field_name_changes'][ $module_slug ] ) ) {
			foreach ( self::$migrated['field_name_changes'][ $module_slug ] as $old_name => $name_change ) {
				if ( version_compare( $attrs['_builder_version'], $name_change['version'], '<' ) ) {
					self::$migrated['name_changes'][ $module_address ][ $old_name ] = $name_change['new_name'];
				}
			}
		}

		foreach ( $migrations as $migration ) {
			$migrated_attrs_count = 0;

			if ( ! in_array( $module_slug, $migration->get_modules(), true ) ) {
				continue;
			}

			$migration_fields = $migration->get_fields();

			// Each "migration field" is an object with a field name (key) and field info (property/value pairs).
			foreach ( $migration_fields as $field_name => $field_info ) {
				// Each "affected field" is a field name (key) with a list of modules that use that field name.
				foreach ( $field_info['affected_fields'] as $affected_field => $affected_modules ) {

					// Skip [what are we skipping?] if either:
					// * there is no instruction to add missing fields AND the "affected field" is missing
					// * this module isn't in the list of matching modules that use the field name.
					if ( ( ! $migration->add_missing_fields && ! isset( $attrs[ $affected_field ] ) ) || ! in_array( $module_slug, $affected_modules, true ) ) {
						continue;
					}

					// If the "migration field" name and the "affected field" name are different,
					// then add the affected field name to the "unprocessed_attrs" list.
					if ( $affected_field !== $field_name ) {
						// Field name changed.
						$unprocessed_attrs[ $field_name ] = $attrs[ $affected_field ];
					}

					// If a value is set in the "unprocessed_attrs" list for the current field we're
					// looking at (field_name), then inherit that value as the "before" state.
					$current_value = isset( $unprocessed_attrs[ $field_name ] ) ? $unprocessed_attrs[ $field_name ] : '';

					$saved_value = isset( $attrs[ $field_name ] ) ? $attrs[ $field_name ] : '';

					$new_value = $migration->migrate( $field_name, $current_value, $module_slug, $saved_value, $affected_field, $attrs, $content, $module_address );

					// If a null value was returned, then we want to unset this attribute.
					if ( is_null( $new_value ) ) {
						continue;
					}

					if ( $new_value !== $saved_value || ( $affected_field !== $field_name && $new_value !== $current_value ) ) {
						// Update migrated value.
						self::$migrated['value_changes'][ $module_address ][ $field_name ] = $new_value;

						// Update attribute's value.
						$attrs[ $field_name ] = $new_value;

						// Update count.
						++$migrated_attrs_count;
					}
				}
			}

			if ( $migrated_attrs_count > 0 ) {
				$attrs['_builder_version'] = $migration->version;
			}
		}

		return $attrs;
	}

	/**
	 * Check if the current hook should be handled.
	 *
	 * @param string $slug Internal system name for the module type.
	 *
	 * @return bool
	 */
	public static function should_handle_render( string $slug ): bool {
		// Get all module slugs to compare against this slug
		$all_module_slugs = ET_Builder_Element::get_all_module_slugs();
		if ( ! in_array( $slug, $all_module_slugs, true ) ) {
			return false;
		}

		// Check current hook
		global $wp_current_filter;
		$current_hook = $wp_current_filter[0];

		// Return cached decision if hook hasn't changed
		if ( $current_hook === self::$last_hook_checked ) {
			return self::$last_hook_check_decision;
		}

		// Update last checked hook
		self::$last_hook_checked = $current_hook;

		/**
		 * Filters the list of hooks where migrations should be processed.
		 *
		 * @since 3.2.0
		 *
		 * @param array $hooks Default hooks array containing:
		 *     - 'the_content'
		 *     - 'admin_enqueue_scripts'
		 *     - 'et_pb_get_backbone_templates'
		 *     - 'wp_ajax_et_pb_execute_content_shortcodes'
		 *     - 'wp_ajax_et_fb_get_saved_layouts'
		 *     - 'wp_ajax_et_fb_retrieve_builder_data'
		 *
		 * @return array Modified array of hook names where migrations should run.
		 */
		$hooks = apply_filters( 'divi_squad_builder_module_migrations_hooks', self::$hooks );
		foreach ( $hooks as $hook ) {
			if ( $hook === $current_hook && did_action( $hook ) > 1 ) {
				self::$last_hook_check_decision = false;
				return false;
			}
		}

		self::$last_hook_check_decision = true;
		return true;
	}

	/**
	 * Migrate from old value into new value.
	 *
	 * @param string $field_name       Current field name within the current module.
	 * @param mixed  $current_value    Current field value within the current module.
	 * @param string $module_slug      Internal system name for the module type.
	 * @param mixed  $saved_value      Saved field value within the current module.
	 * @param string $saved_field_name Saved field name within the current module.
	 * @param array  $attrs            Shortcode attributes.
	 * @param mixed  $content          Text/HTML content within the current module.
	 * @param string $module_address   Location of the current module on the page.
	 *
	 * @return mixed
	 */
	abstract public function migrate( string $field_name, $current_value, string $module_slug, $saved_value, string $saved_field_name, array $attrs, $content, string $module_address );

	/**
	 * Maybe override content.
	 *
	 * @param mixed  $content           Text/HTML content within the current module.
	 * @param array  $attrs             Shortcode attributes.
	 * @param array  $unprocessed_attrs Attributes that have not yet been processed.
	 * @param string $module_slug       Internal system name for the module type.
	 *
	 * @return mixed
	 */
	public static function maybe_override_content( $content, array $attrs, array $unprocessed_attrs, string $module_slug ) {
		if ( empty( $attrs['_builder_version'] ) ) {
			$attrs['_builder_version'] = '3.0.47';
		}

		if ( ! self::should_handle_render( $module_slug ) ) {
			return $content;
		}

		/**
		 * List of migrations.
		 *
		 * @var Migration[] $migrations
		 */
		$migrations = self::get_migrations( $attrs['_builder_version'] );

		foreach ( $migrations as $migration ) {
			$migrated_content = false;

			if ( ! in_array( $module_slug, $migration->get_content_migration_modules(), true ) ) {
				continue;
			}

			foreach ( $migration->get_content_migration_modules() as $module ) {
				$new_content = $migration->migrate_content( $module_slug, $attrs, $content );

				if ( $new_content !== $content ) {
					$migrated_content = true;
				}

				$content = $new_content;
			}

			if ( $migrated_content ) {
				$attrs['_builder_version'] = $migration->version;
			}
		}

		return $content;
	}

	/**
	 * Get all modules to need to be migrated.
	 *
	 * @return array
	 */
	public function get_content_migration_modules(): array {
		return array();
	}

	/**
	 * This could have been written as abstract, but it's not as common to be expected to be implemented by every migration
	 *
	 * @param string $module_slug Internal system name for the module type.
	 * @param array  $attrs       Shortcode attributes.
	 * @param mixed  $content     Text/HTML content within the current module.
	 *
	 * @return mixed
	 */
	public function migrate_content( string $module_slug, array $attrs, $content ) {
		return $content;
	}
}
