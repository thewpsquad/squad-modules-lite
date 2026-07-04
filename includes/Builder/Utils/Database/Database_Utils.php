<?php // phpcs:disable WordPress.Files.FileName, WordPress.NamingConventions.ValidVariableName

/**
 * Database utilities for table management.
 *
 * This class provides helper methods for creating and managing database tables
 * in a WordPress environment with proper schema definitions.
 *
 * @since   3.1.0
 * @author  The WP Squad <support@squadmodules.com>
 * @package DiviSquad
 */

namespace DiviSquad\Builder\Utils\Database;

use Throwable;
use function dbDelta;

/**
 * Database utilities class for managing table structures.
 *
 * @since 3.1.0
 */
class Database_Utils {

	/**
	 * Table schema field type constants
	 */
	public const TYPE_INT       = 'int';
	public const TYPE_BIGINT    = 'bigint';
	public const TYPE_VARCHAR   = 'varchar';
	public const TYPE_TEXT      = 'text';
	public const TYPE_LONGTEXT  = 'longtext';
	public const TYPE_TINYTEXT  = 'tinytext';
	public const TYPE_DATETIME  = 'datetime';
	public const TYPE_TIMESTAMP = 'timestamp';
	public const TYPE_DATE      = 'date';
	public const TYPE_DECIMAL   = 'decimal';
	public const TYPE_FLOAT     = 'float';
	public const TYPE_DOUBLE    = 'double';
	public const TYPE_TINYINT   = 'tinyint';
	public const TYPE_SMALLINT  = 'smallint';
	public const TYPE_MEDIUMINT = 'mediumint';
	public const TYPE_BOOL      = 'bool';
	public const TYPE_BOOLEAN   = 'boolean';

	/**
	 * Generate SQL CREATE TABLE statement from schema.
	 *
	 * @since  3.1.0
	 *
	 * @param string                                                                                                                                                                                                                          $table_name Table name to generate SQL for.
	 * @param array<string, array{type: string, length?: int, unsigned?: bool, nullable?: bool, default?: string|null, auto_increment?: bool, primary?: bool, on_update?: string, index?: bool, unique?: bool, precision?: int, scale?: int}> $schema     Table schema definition.
	 *
	 * @return string Generated SQL statement.
	 */
	public static function generate_create_table_sql( string $table_name, array $schema ): string {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$columns      = array();
		$indices      = array();
		$primary_keys = array();

		foreach ( $schema as $column_name => $definition ) {
			$columns[] = self::generate_column_definition( $column_name, $definition );

			// Collect all primary keys
			if ( isset( $definition['primary'] ) && $definition['primary'] ) {
				$primary_keys[] = $column_name;
			}

			// Add regular indices
			if ( ( isset( $definition['index'] ) && $definition['index'] ) ||
				 ( isset( $definition['unique'] ) && $definition['unique'] ) ) {
				$indices[] = self::generate_index_definition( $column_name, $definition );
			}
		}

		// Add composite primary key if multiple primary keys are defined
		if ( count( $primary_keys ) > 1 ) {
			// Remove PRIMARY KEY from individual column definitions
			$columns = array_map(
				function ( $column ) {
					return str_replace( ' PRIMARY KEY', '', $column );
				},
				$columns
			);

			// Add composite primary key
			$primary_key_columns = array_map(
				function ( $column ) {
					return "`$column`";
				},
				$primary_keys
			);

			$indices[] = 'PRIMARY KEY (' . implode( ', ', $primary_key_columns ) . ')';
		}

		// Add unique composite index for meta_key and post_type if they exist in schema.
		if ( isset( $schema['meta_key'], $schema['post_type'] ) ) {
			$indices[] = 'UNIQUE KEY `idx_meta_key_post_type` (`meta_key`, `post_type`)';
		}

		/**
		 * Filter the generated SQL statement before it's returned.
		 *
		 * @since 3.1.0
		 *
		 * @param array  $columns    Array of column definition strings.
		 * @param string $table_name The name of the table.
		 * @param array  $schema     The original schema definition.
		 */
		$columns = apply_filters( 'divi_squad_db_utils_columns', $columns, $table_name, $schema );

		/**
		 * Filter the generated SQL statement before it's returned.
		 *
		 * @since 3.1.0
		 *
		 * @param array  $indices    Array of index definition strings.
		 * @param string $table_name The name of the table.
		 * @param array  $schema     The original schema definition.
		 */
		$indices = apply_filters( 'divi_squad_db_utils_indices', $indices, $table_name, $schema );

		return sprintf(
			"CREATE TABLE IF NOT EXISTS `%s` (\n\t%s%s\n) %s",
			$table_name,
			implode( ",\n\t", $columns ),
			count( $indices ) > 0 ? ",\n\t" . implode( ",\n\t", $indices ) : '',
			$charset_collate
		);
	}

	/**
	 * Generate column definition SQL.
	 *
	 * @since  3.1.0
	 *
	 * @param string                                                                                                                                                                              $column_name Column name.
	 * @param array{type: string, length?: int, unsigned?: bool, nullable?: bool, default?: string|null, auto_increment?: bool, primary?: bool, on_update?: string, precision?: int, scale?: int} $definition  Column definition array.
	 *
	 * @return string Column definition SQL.
	 */
	private static function generate_column_definition( string $column_name, array $definition ): string {
		$parts = array( "`$column_name`" );

		// Type with optional length or precision/scale
		$type = strtolower( $definition['type'] );

		if ( isset( $definition['precision'], $definition['scale'] ) &&
			 in_array( $type, array( 'decimal', 'float', 'double' ), true ) ) {
			$type .= "({$definition['precision']},{$definition['scale']})";
		} elseif ( isset( $definition['length'] ) && $definition['length'] > 0 ) {
			$type .= "({$definition['length']})";
		}

		$parts[] = $type;

		// Unsigned attribute (must come before NOT NULL).
		if ( isset( $definition['unsigned'] ) && $definition['unsigned'] ) {
			$parts[] = 'UNSIGNED';
		}

		// Nullable.
		$parts[] = isset( $definition['nullable'] ) && $definition['nullable'] ? 'NULL' : 'NOT NULL';

		// Default value.
		if ( array_key_exists( 'default', $definition ) ) {
			if ( null === $definition['default'] ) {
				// Explicitly set NULL as default if nullable
				if ( isset( $definition['nullable'] ) && $definition['nullable'] ) {
					$parts[] = 'DEFAULT NULL';
				}
			} elseif ( 'CURRENT_TIMESTAMP' === $definition['default'] ) {
				$parts[] = 'DEFAULT CURRENT_TIMESTAMP';
			} elseif ( is_numeric( $definition['default'] ) ) {
				// Numeric defaults shouldn't be quoted
				$parts[] = "DEFAULT {$definition['default']}";
			} else {
				$parts[] = "DEFAULT '{$definition['default']}'";
			}
		}

		// Auto increment.
		if ( isset( $definition['auto_increment'] ) && $definition['auto_increment'] ) {
			$parts[] = 'AUTO_INCREMENT';
		}

		// Primary key (only for single-column primary keys).
		if ( isset( $definition['primary'] ) && $definition['primary'] ) {
			$parts[] = 'PRIMARY KEY';
		}

		// On Update (for TIMESTAMP fields).
		if ( isset( $definition['on_update'] ) && 'CURRENT_TIMESTAMP' === $definition['on_update'] ) {
			$parts[] = 'ON UPDATE CURRENT_TIMESTAMP';
		}

		return implode( ' ', $parts );
	}

	/**
	 * Generate index definition SQL.
	 *
	 * @since  3.1.0
	 *
	 * @param string               $column_name Column name.
	 * @param array{unique?: bool} $definition  Column definition array.
	 *
	 * @return string Index definition SQL.
	 */
	private static function generate_index_definition( string $column_name, array $definition ): string {
		$index_name = "idx_{$column_name}";

		// Limit index name length to avoid MySQL errors (64 char limit)
		if ( strlen( $index_name ) > 60 ) {
			$index_name = substr( $index_name, 0, 60 );
		}

		$type = isset( $definition['unique'] ) && $definition['unique'] ? 'UNIQUE' : '';

		return trim( sprintf( '%s KEY `%s` (`%s`)', $type, $index_name, $column_name ) );
	}

	/**
	 * Verify if a table exists and create it if it doesn't.
	 *
	 * @since  3.1.0
	 *
	 * @param string                                                                                                                                                                                                                          $table_name Table name to verify/create.
	 * @param array<string, array{type: string, length?: int, unsigned?: bool, nullable?: bool, default?: string|null, auto_increment?: bool, primary?: bool, on_update?: string, index?: bool, unique?: bool, precision?: int, scale?: int}> $schema     Table schema.
	 *
	 * @return bool True if table exists or was created successfully.
	 */
	public static function verify_and_create_table( string $table_name, array $schema ): bool {
		try {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			// Generate and execute creation SQL.
			$sql = self::generate_create_table_sql( $table_name, $schema );

			/**
			 * Filter the SQL used to create or update a table.
			 *
			 * @since 3.1.0
			 *
			 * @param string $sql        The SQL statement.
			 * @param string $table_name The name of the table.
			 * @param array  $schema     The table schema.
			 */
			$sql = apply_filters( 'divi_squad_db_utils_create_table_sql', $sql, $table_name, $schema );

			$result = dbDelta( $sql );

			/**
			 * Action fired after a table is created or verified.
			 *
			 * @since 3.1.0
			 *
			 * @param string $table_name The name of the table.
			 * @param array  $schema     The table schema.
			 * @param array  $result     The result from dbDelta.
			 */
			do_action( 'divi_squad_db_utils_table_created', $table_name, $schema, $result );

			// Double-check that the table exists
			global $wpdb;
			$table_exists = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s',
					DB_NAME,
					$table_name
				)
			);

			return ! empty( $table_exists );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, "Error creating table {$table_name}" );

			return false;
		}
	}

	/**
	 * Check if a table exists in the database.
	 *
	 * @since 3.1.0
	 *
	 * @param string $table_name Table name to check.
	 *
	 * @return bool True if the table exists.
	 */
	public static function table_exists( string $table_name ): bool {
		global $wpdb;

		$result = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s',
				DB_NAME,
				$table_name
			)
		);

		return ! empty( $result );
	}

	/**
	 * Get the table's columns with their properties.
	 *
	 * @since 3.1.0
	 *
	 * @param string $table_name Table name to inspect.
	 *
	 * @return array<string, array<string, mixed>> Array of column definitions.
	 */
	public static function get_table_columns( string $table_name ): array {
		global $wpdb;

		$columns = array();

		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SHOW COLUMNS FROM `%s`',
				$table_name
			),
			ARRAY_A
		);

		if ( is_array( $results ) ) {
			foreach ( $results as $result ) {
				$column_name             = $result['Field'];
				$columns[ $column_name ] = array(
					'type'     => $result['Type'],
					'nullable' => 'YES' === $result['Null'],
					'default'  => $result['Default'],
					'key'      => $result['Key'],
					'extra'    => $result['Extra'],
				);
			}
		}

		return $columns;
	}

	/**
	 * Truncate a table (remove all rows).
	 *
	 * @since 3.1.0
	 *
	 * @param string $table_name Table name to truncate.
	 *
	 * @return bool True if the operation was successful.
	 */
	public static function truncate_table( string $table_name ): bool {
		global $wpdb;

		try {
			return (bool) $wpdb->query( "TRUNCATE TABLE `$table_name`" );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, "Error truncating table {$table_name}" );

			return false;
		}
	}

	/**
	 * Get row count for a table.
	 *
	 * @since 3.1.0
	 *
	 * @param string $table_name Table name to count rows for.
	 *
	 * @return int Number of rows in the table.
	 */
	public static function get_row_count( string $table_name ): int {
		global $wpdb;

		$count = $wpdb->get_var( "SELECT COUNT(*) FROM `$table_name`" );

		return (int) $count;
	}

	/**
	 * Drop a table from the database.
	 *
	 * @since 3.1.0
	 *
	 * @param string $table_name Table name to drop.
	 *
	 * @return bool True if the operation was successful.
	 */
	public static function drop_table( string $table_name ): bool {
		global $wpdb;

		try {
			return (bool) $wpdb->query( "DROP TABLE IF EXISTS `$table_name`" );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, "Error dropping table {$table_name}" );

			return false;
		}
	}
}
