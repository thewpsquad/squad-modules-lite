<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Custom Fields Utils Helper
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   3.1.0
 */

namespace DiviSquad\Builder\Utils\Elements;

use InvalidArgumentException;

/**
 * Custom Fields Utils Helper Class
 *
 * @package DiviSquad
 * @since   3.1.0
 */
class Custom_Fields {

	/**
	 * Processor type constants.
	 */
	public const PROCESSOR_COLLECTIONS = 'collections';
	public const PROCESSOR_DEFINITIONS = 'definitions';
	public const PROCESSOR_MANAGERS    = 'managers';

	/**
	 * Field type constants.
	 */
	public const FIELD_WORDPRESS = 'custom_fields';
	public const FIELD_ACF       = 'acf_fields';

	/**
	 * Manager type constants.
	 */
	public const MANAGER_FIELDS   = 'fields';
	public const MANAGER_UPGRADES = 'upgrades';

	/**
	 * Supported post types.
	 *
	 * @var array<string>
	 */
	protected array $post_types = array( 'post' );

	/**
	 * Runtime data storage.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	protected array $storage = array(
		'instances'   => array(),
		'options'     => array(),
		'definitions' => array(),
		'managers'    => array(),
	);

	/**
	 * Processor class registry for various types of processors.
	 *
	 * @var array<string, array<string, class-string>>
	 */
	protected array $processor_registry = array();

	/**
	 * Constructor for Custom_Fields class.
	 */
	public function __construct() {
		$this->initialize_processor_registry();
	}

	/**
	 * Initialize the processor registry with their default values.
	 *
	 * @return void
	 */
	protected function initialize_processor_registry(): void {
		$this->processor_registry = array(
			self::PROCESSOR_COLLECTIONS => array(
				self::FIELD_WORDPRESS => Custom_Fields\Processors\WordPress::class,
				self::FIELD_ACF       => Custom_Fields\Processors\Advanced::class,
			),
			self::PROCESSOR_DEFINITIONS => array(
				self::FIELD_WORDPRESS => Custom_Fields\Definitions\WordPress::class,
				self::FIELD_ACF       => Custom_Fields\Definitions\Advanced::class,
			),
			self::PROCESSOR_MANAGERS    => array(
				self::MANAGER_FIELDS   => Custom_Fields\Managers\Fields::class,
				self::MANAGER_UPGRADES => Custom_Fields\Managers\Upgrades::class,
			),
		);

		/**
		 * Filter to modify the default processor registry configuration.
		 *
		 * @since 3.1.0
		 *
		 * @param array $processor_registry The default processor registry configuration.
		 */
		$this->processor_registry = apply_filters( 'divi_squad_custom_fields_processor_registry', $this->processor_registry );
	}

	/**
	 * Initialize the Custom_Fields class.
	 *
	 * @return void
	 */
	public function init(): void {
		// Initialize the fields manager.
		$fields_manager = $this->get_manager( self::MANAGER_FIELDS );

		/**
		 * Action fired after initializing the Custom_Fields system.
		 *
		 * @since 3.1.0
		 *
		 * @param Custom_Fields\ManagerInterface $fields_manager The fields manager instance.
		 */
		do_action( 'divi_squad_custom_fields_initialized', $fields_manager );
	}

	/**
	 * Add a new processor to the processor registry.
	 *
	 * @param string       $processor_type The processor type (collections, definitions, managers).
	 * @param string       $field_type     The field type identifier.
	 * @param class-string $class_name     The fully qualified class name.
	 *
	 * @return bool True if the processor was added successfully, false otherwise.
	 */
	public function add_processor( string $processor_type, string $field_type, string $class_name ): bool {
		// Validate processor type.
		if ( ! isset( $this->processor_registry[ $processor_type ] ) ) {
			return false;
		}

		// Validate that the class exists.
		if ( ! class_exists( $class_name ) ) {
			return false;
		}

		// Add the processor.
		$this->processor_registry[ $processor_type ][ $field_type ] = $class_name;

		/**
		 * Action fired after adding a new processor.
		 *
		 * @since 3.1.0
		 *
		 * @param string $processor_type The processor type.
		 * @param string $field_type     The field type.
		 * @param string $class_name     The class name that was added.
		 */
		do_action( 'divi_squad_processor_added', $processor_type, $field_type, $class_name );

		return true;
	}

	/**
	 * Remove a processor from the processor registry.
	 *
	 * @param string $processor_type The processor type (collections, definitions, managers).
	 * @param string $field_type     The field type identifier.
	 *
	 * @return bool True if the processor was removed successfully, false otherwise.
	 */
	public function remove_processor( string $processor_type, string $field_type ): bool {
		if ( ! isset( $this->processor_registry[ $processor_type ][ $field_type ] ) ) {
			return false;
		}

		$removed_class = $this->processor_registry[ $processor_type ][ $field_type ];
		unset( $this->processor_registry[ $processor_type ][ $field_type ] );

		/**
		 * Action fired after removing a processor.
		 *
		 * @since 3.1.0
		 *
		 * @param string $processor_type The processor type.
		 * @param string $field_type     The field type.
		 * @param string $removed_class  The class name that was removed.
		 */
		do_action( 'divi_squad_processor_removed', $processor_type, $field_type, $removed_class );

		return true;
	}

	/**
	 * Get all processors of a specific type.
	 *
	 * @param string $processor_type The processor type (collections, definitions, managers).
	 *
	 * @return array<string, class-string> The processors of the specified type.
	 */
	public function get_processors_by_type( string $processor_type ): array {
		return $this->processor_registry[ $processor_type ] ?? array();
	}

	/**
	 * Get a specific processor class.
	 *
	 * @param string $processor_type The processor type (collections, definitions, managers).
	 * @param string $field_type     The field type identifier.
	 *
	 * @return string|null The processor class name or null if not found.
	 */
	public function get_processor( string $processor_type, string $field_type ): ?string {
		return $this->processor_registry[ $processor_type ][ $field_type ] ?? null;
	}

	/**
	 * Check if a processor exists.
	 *
	 * @param string $processor_type The processor type (collections, definitions, managers).
	 * @param string $field_type     The field type identifier.
	 *
	 * @return bool Whether the processor exists.
	 */
	public function has_processor( string $processor_type, string $field_type ): bool {
		return isset( $this->processor_registry[ $processor_type ][ $field_type ] );
	}

	/**
	 * Get all fields of a specific type.
	 *
	 * @param string $field_type The field type (acf, WordPress, etc.).
	 * @param int    $post_id    The current post id.
	 *
	 * @return array<string, mixed>
	 * @throws InvalidArgumentException If the field type is not supported.
	 */
	public function get_fields( string $field_type, int $post_id ): array {
		/**
		 * Filter the field type before processing.
		 *
		 * @since 3.1.0
		 *
		 * @param string $field_type The field type being requested.
		 * @param int    $post_id    The post ID.
		 */
		$field_type = (string) apply_filters( 'divi_squad_custom_fields_type', $field_type, $post_id );

		// Get the processor class.
		$processor_class = $this->get_processor( self::PROCESSOR_COLLECTIONS, $field_type );
		if ( ! $processor_class ) {
			throw new InvalidArgumentException(
				sprintf(
				/* translators: %s: The unsupported field type */
					esc_html__( 'Unsupported field type: %s', 'squad-modules-for-divi' ),
					esc_html( $field_type )
				)
			);
		}

		$cache_key = md5( $processor_class );

		if ( ! isset( $this->storage['options'][ $cache_key ][ $post_id ] ) ) {
			/** @var Custom_Fields\CollectionInterface $fields_processor */
			$fields_processor = $this->get( $field_type );

			/**
			 * Action before retrieving fields for a post.
			 *
			 * @since 3.1.0
			 *
			 * @param int                               $post_id         The post ID.
			 * @param string                            $field_type      The field type.
			 * @param Custom_Fields\CollectionInterface $fields_processor The processor instance.
			 */
			do_action( 'divi_squad_before_get_fields', $post_id, $field_type, $fields_processor );

			$fields = $fields_processor->get_fields( $post_id );

			/**
			 * Filter the retrieved fields.
			 *
			 * @since 3.1.0
			 *
			 * @param array                             $fields          The retrieved fields.
			 * @param int                               $post_id         The post ID.
			 * @param string                            $field_type      The field type.
			 * @param Custom_Fields\CollectionInterface $fields_processor The processor instance.
			 */
			$fields = apply_filters( 'divi_squad_custom_fields', $fields, $post_id, $field_type, $fields_processor );

			$this->storage['options'][ $cache_key ] = array(
				$post_id => $fields,
			);

			/**
			 * Action after retrieving fields for a post.
			 *
			 * @since 3.1.0
			 *
			 * @param array  $fields     The retrieved fields.
			 * @param int    $post_id    The post ID.
			 * @param string $field_type The field type.
			 */
			do_action( 'divi_squad_after_get_fields', $fields, $post_id, $field_type );
		}

		return $this->storage['options'][ $cache_key ][ $post_id ];
	}

	/**
	 * Get module definitions for module usages
	 *
	 * @param string $field_type The field type (acf, WordPress, etc.).
	 *
	 * @return array<string, mixed>
	 * @throws InvalidArgumentException If the field type is not supported.
	 */
	public function get_definitions( string $field_type ): array {
		/**
		 * Filter the field type before getting definitions.
		 *
		 * @since 3.1.0
		 *
		 * @param string $field_type The field type being requested.
		 */
		$field_type = (string) apply_filters( 'divi_squad_definition_field_type', $field_type );

		// Get the processor class.
		$processor_class = $this->get_processor( self::PROCESSOR_DEFINITIONS, $field_type );
		if ( ! $processor_class ) {
			throw new InvalidArgumentException(
				sprintf(
				/* translators: %s: The unsupported field type */
					esc_html__( 'Unsupported field type: %s', 'squad-modules-for-divi' ),
					esc_html( $field_type )
				)
			);
		}

		$cache_key = md5( $processor_class );

		if ( ! isset( $this->storage['definitions'][ $cache_key ] ) ) {
			$empty_fields        = array();
			$default_fields      = array();
			$associated_fields   = array();
			$not_eligible_fields = array();

			// Verify that field type is eligible or not.
			/** @var Custom_Fields\CollectionInterface $fields */
			$fields = $this->get( $field_type, self::PROCESSOR_COLLECTIONS );

			/** @var Custom_Fields\DefinitionInterface $definitions */
			$definitions = $this->get( $field_type, self::PROCESSOR_DEFINITIONS );

			/**
			 * Action before retrieving field definitions.
			 *
			 * @since 3.1.0
			 *
			 * @param string                            $field_type  The field type.
			 * @param Custom_Fields\CollectionInterface $fields      The fields processor.
			 * @param Custom_Fields\DefinitionInterface $definitions The definitions processor.
			 */
			do_action( 'divi_squad_before_get_definitions', $field_type, $fields, $definitions );

			if ( $fields->is_eligible() ) {
				$wp_fields_options = $fields->get_formatted_fields();

				/**
				 * Filter the formatted fields options.
				 *
				 * @since 3.1.0
				 *
				 * @param array  $wp_fields_options The formatted fields.
				 * @param string $field_type        The field type.
				 */
				$wp_fields_options = apply_filters( 'divi_squad_formatted_fields', $wp_fields_options, $field_type );

				// Add regular custom fields.
				foreach ( $wp_fields_options as $post_type => $options ) {
					if ( ! is_array( $options ) || 0 === count( $options ) ) {
						continue;
					}

					if ( ! in_array( $post_type, $this->get_supported_post_types(), true ) ) {
						continue;
					}

					$new_fields = $definitions->get_default_fields( $post_type, $options );

					/**
					 * Filter the default fields for a post type.
					 *
					 * @since 3.1.0
					 *
					 * @param array  $new_fields The default fields.
					 * @param string $post_type  The post type.
					 * @param array  $options    The field options.
					 */
					$new_fields = apply_filters( 'divi_squad_default_fields', $new_fields, $post_type, $options );

					$default_fields = array_merge_recursive( $default_fields, $new_fields );
				}

				// Verify and set default fields.
				$empty_fields = $definitions->get_empty_fields();

				/**
				 * Filter the empty fields.
				 *
				 * @since 3.1.0
				 *
				 * @param array  $empty_fields The empty fields.
				 * @param string $field_type   The field type.
				 */
				$empty_fields = apply_filters( 'divi_squad_empty_fields', $empty_fields, $field_type );

				$field_types = $fields->get_formatted_fields_types();

				/**
				 * Filter the formatted field types.
				 *
				 * @since 3.1.0
				 *
				 * @param array  $field_types The field types.
				 * @param string $field_type  The field type.
				 */
				$field_types = apply_filters( 'divi_squad_field_types', $field_types, $field_type );

				$associated_fields = $definitions->get_associated_fields( $field_types );

				/**
				 * Filter the associated fields.
				 *
				 * @since 3.1.0
				 *
				 * @param array  $associated_fields The associated fields.
				 * @param array  $field_types       The field types.
				 * @param string $field_type        The field type.
				 */
				$associated_fields = apply_filters( 'divi_squad_associated_fields', $associated_fields, $field_types, $field_type );
			} else {
				$not_eligible_fields = $definitions->get_not_eligible_fields();

				/**
				 * Filter the not eligible fields.
				 *
				 * @since 3.1.0
				 *
				 * @param array  $not_eligible_fields The not eligible fields.
				 * @param string $field_type          The field type.
				 */
				$not_eligible_fields = apply_filters( 'divi_squad_not_eligible_fields', $not_eligible_fields, $field_type );
			}

			$associated_fields = 0 === count( $default_fields ) ? array() : $associated_fields;

			if ( count( $not_eligible_fields ) > 0 ) {
				$default_fields = $not_eligible_fields;
			} elseif ( 0 === count( $default_fields ) ) {
				$default_fields = $empty_fields;
			}

			$common_fields = $definitions->get_common_fields();

			/**
			 * Filter the common fields.
			 *
			 * @since 3.1.0
			 *
			 * @param array  $common_fields The common fields.
			 * @param string $field_type    The field type.
			 */
			$common_fields = apply_filters( 'divi_squad_common_fields', $common_fields, $field_type );

			$this->storage['definitions'][ $cache_key ] = array_merge_recursive(
				$common_fields,
				$default_fields,
				$associated_fields
			);

			/**
			 * Filter the final field definitions.
			 *
			 * @since 3.1.0
			 *
			 * @param array  $definitions The merged field definitions.
			 * @param string $field_type  The field type.
			 */
			$this->storage['definitions'][ $cache_key ] = apply_filters(
				'divi_squad_field_definitions',
				$this->storage['definitions'][ $cache_key ],
				$field_type
			);

			/**
			 * Action after retrieving field definitions.
			 *
			 * @since 3.1.0
			 *
			 * @param array  $definitions The field definitions.
			 * @param string $field_type  The field type.
			 */
			do_action( 'divi_squad_after_get_definitions', $this->storage['definitions'][ $cache_key ], $field_type );
		}

		return $this->storage['definitions'][ $cache_key ];
	}

	/**
	 * Get a manager instance of a specific type.
	 *
	 * @param string $manager_type The manager type (fields, upgrades, etc.).
	 * @param array  $args         Optional. Arguments to pass to the manager constructor.
	 *
	 * @return Custom_Fields\ManagerInterface The manager instance.
	 * @throws InvalidArgumentException If the manager type is not supported.
	 */
	public function get_manager( string $manager_type, array $args = array() ): Custom_Fields\ManagerInterface {
		/**
		 * Filter the manager type before processing.
		 *
		 * @since 3.1.0
		 *
		 * @param string $manager_type The manager type being requested.
		 * @param array  $args         The constructor arguments.
		 */
		$manager_type = (string) apply_filters( 'divi_squad_manager_type', $manager_type, $args );

		// Get the processor class.
		$processor_class = $this->get_processor( self::PROCESSOR_MANAGERS, $manager_type );
		if ( null === $processor_class ) {
			throw new InvalidArgumentException(
				sprintf(
				/* translators: %s: The unsupported manager type */
					esc_html__( 'Unsupported manager type: %s', 'squad-modules-for-divi' ),
					esc_html( $manager_type )
				)
			);
		}

		$cache_key = md5( $processor_class . wp_json_encode( $args ) );

		if ( ! isset( $this->storage['managers'][ $cache_key ] ) ) {
			/**
			 * Filter the manager class before instantiation.
			 *
			 * @since 3.1.0
			 *
			 * @param string $processor_class The manager class.
			 * @param string $manager_type    The manager type.
			 * @param array  $args            The constructor arguments.
			 */
			$processor_class = apply_filters(
				'divi_squad_manager_class',
				$processor_class,
				$manager_type,
				$args
			);

			$default_args = array();

			// Special handling for known manager types.
			switch ( $manager_type ) {
				case self::MANAGER_FIELDS:
					$default_args = array( $this->get_supported_post_types() );
					break;
				case self::MANAGER_UPGRADES:
					// No default args needed.
					break;
			}

			// Merge default args with provided args.
			$constructor_args = array_merge( $default_args, $args );

			/**
			 * Action before instantiating a manager.
			 *
			 * @since 3.1.0
			 *
			 * @param string $processor_class  The manager class.
			 * @param string $manager_type     The manager type.
			 * @param array  $constructor_args The constructor arguments.
			 */
			do_action( 'divi_squad_before_get_manager', $processor_class, $manager_type, $constructor_args );

			// Create a new instance of the processor class with constructor args.
			$this->storage['managers'][ $cache_key ] = new $processor_class( ...$constructor_args );

			/**
			 * Action after instantiating a manager.
			 *
			 * @since 3.1.0
			 *
			 * @param Custom_Fields\ManagerInterface $manager     The manager instance.
			 * @param string                         $manager_type The manager type.
			 * @param array                          $args         The constructor arguments.
			 */
			do_action(
				'divi_squad_after_get_manager',
				$this->storage['managers'][ $cache_key ],
				$manager_type,
				$constructor_args
			);
		}

		/**
		 * Filter the manager instance.
		 *
		 * @since 3.1.0
		 *
		 * @param Custom_Fields\ManagerInterface $manager     The manager instance.
		 * @param string                         $manager_type The manager type.
		 * @param array                          $args         The constructor arguments.
		 */
		return apply_filters(
			'divi_squad_manager',
			$this->storage['managers'][ $cache_key ],
			$manager_type,
			$args
		);
	}

	/**
	 * Get supported post types.
	 *
	 * @return array<string>
	 */
	public function get_supported_post_types(): array {
		/**
		 * Filters the supported post types for custom fields.
		 *
		 * @since 3.1.0
		 *
		 * @param array $post_types Array of post types supported by the custom fields system.
		 */
		return apply_filters( 'divi_squad_custom_fields_post_types', $this->post_types );
	}

	/**
	 * Fetch fields of a specific type.
	 *
	 * @param string $field_type The field type (acf, WordPress, etc.).
	 * @param string $storage    The storage type (collections, definitions, managers).
	 *
	 * @return Custom_Fields\CollectionInterface|Custom_Fields\DefinitionInterface|Custom_Fields\ManagerInterface
	 * @throws InvalidArgumentException If the field type is not supported.
	 */
	public function get( string $field_type, string $storage = self::PROCESSOR_COLLECTIONS ): object {
		// Check if the processor exists.
		if ( ! $this->has_processor( $storage, $field_type ) ) {
			/**
			 * Filter the error message for unsupported field types.
			 *
			 * @since 3.1.0
			 *
			 * @param string $error_message The error message.
			 * @param string $field_type    The unsupported field type.
			 */
			$error_message = apply_filters(
				'divi_squad_unsupported_field_type_message',
				sprintf(
				/* translators: %s: The unsupported field type */
					esc_html__( 'Unsupported field type: %s', 'squad-modules-for-divi' ),
					esc_html( $field_type )
				),
				$field_type
			);

			throw new InvalidArgumentException( $error_message );
		}

		// Get the processor class name and create a unique cache key.
		$processor_class = $this->get_processor( $storage, $field_type );
		$cache_key       = md5( $processor_class );

		// Check if an instance already exists in storage.
		if ( ! isset( $this->storage['instances'][ $cache_key ] ) ) {
			if ( ! in_array( $storage, array( self::PROCESSOR_COLLECTIONS, self::PROCESSOR_DEFINITIONS, self::PROCESSOR_MANAGERS ), true ) ) {
				/**
				 * Filter the error message for unsupported storage types.
				 *
				 * @since 3.1.0
				 *
				 * @param string $error_message The error message.
				 * @param string $storage       The unsupported storage type.
				 */
				$error_message = apply_filters(
					'divi_squad_unsupported_storage_type_message',
					sprintf(
					/* translators: %s: The unsupported storage type */
						esc_html__( 'Unsupported storage type: %s', 'squad-modules-for-divi' ),
						esc_html( $storage )
					),
					$storage
				);

				throw new InvalidArgumentException( $error_message );
			}

			/**
			 * Filter the processor class.
			 *
			 * @since 3.1.0
			 *
			 * @param string $processor_class The processor class.
			 * @param string $field_type      The field type.
			 * @param string $storage         The storage type.
			 */
			$processor_class = apply_filters(
				'divi_squad_processor_class',
				$processor_class,
				$field_type,
				$storage
			);

			// Create a new instance of the processor class.
			$this->storage['instances'][ $cache_key ] = new $processor_class();

			/**
			 * Action after instantiating a processor.
			 *
			 * @since 3.1.0
			 *
			 * @param object $processor_instance The processor instance.
			 * @param string $field_type         The field type.
			 * @param string $storage            The storage type.
			 */
			do_action(
				'divi_squad_processor_instantiated',
				$this->storage['instances'][ $cache_key ],
				$field_type,
				$storage
			);
		}

		return $this->storage['instances'][ $cache_key ];
	}

	/**
	 * Register custom field types and processors
	 *
	 * @param string      $field_type        The field type to register.
	 * @param class-string $collections_class The class for collections processing.
	 * @param class-string $definitions_class The class for definitions processing.
	 *
	 * @return bool Whether the registration was successful.
	 */
	public function register_field_type( string $field_type, string $collections_class, string $definitions_class ): bool {
		if ( $this->has_processor( self::PROCESSOR_COLLECTIONS, $field_type ) ) {
			/**
			 * Action when attempting to register an existing field type.
			 *
			 * @since 3.1.0
			 *
			 * @param string $field_type The field type.
			 */
			do_action( 'divi_squad_field_type_already_registered', $field_type );
			return false;
		}

		$success = $this->add_processor( self::PROCESSOR_COLLECTIONS, $field_type, $collections_class ) &&
					$this->add_processor( self::PROCESSOR_DEFINITIONS, $field_type, $definitions_class );

		if ( $success ) {
			/**
			 * Action after registering a new field type.
			 *
			 * @since 3.1.0
			 *
			 * @param string $field_type        The field type.
			 * @param string $collections_class The collections class.
			 * @param string $definitions_class The definitions class.
			 */
			do_action( 'divi_squad_field_type_registered', $field_type, $collections_class, $definitions_class );
		}

		return $success;
	}

	/**
	 * Register a manager type.
	 *
	 * @param string       $manager_type The manager type identifier.
	 * @param class-string $manager_class The class for manager implementation.
	 *
	 * @return bool Whether the registration was successful.
	 */
	public function register_manager_type( string $manager_type, string $manager_class ): bool {
		if ( $this->has_processor( self::PROCESSOR_MANAGERS, $manager_type ) ) {
			/**
			 * Action when attempting to register an existing manager type.
			 *
			 * @since 3.1.0
			 *
			 * @param string $manager_type The manager type.
			 */
			do_action( 'divi_squad_manager_type_already_registered', $manager_type );
			return false;
		}

		$success = $this->add_processor( self::PROCESSOR_MANAGERS, $manager_type, $manager_class );

		if ( $success ) {
			/**
			 * Action after registering a new manager type.
			 *
			 * @since 3.1.0
			 *
			 * @param string $manager_type  The manager type.
			 * @param string $manager_class The manager class.
			 */
			do_action( 'divi_squad_manager_type_registered', $manager_type, $manager_class );
		}

		return $success;
	}

	/**
	 * Clear cached data for a specific field type and post.
	 *
	 * @param string|null $field_type The field type to clear.
	 * @param int|null    $post_id    The post ID to clear cache for.
	 *
	 * @return void
	 */
	public function clear_cache( ?string $field_type = null, ?int $post_id = null ): void {
		if ( null === $field_type && null === $post_id ) {
			$this->storage['options']     = array();
			$this->storage['definitions'] = array();
			$this->storage['managers']    = array();
		} elseif ( null === $post_id && null !== $field_type ) {
			// Clear collection fields cache
			$processor_class = $this->get_processor( self::PROCESSOR_COLLECTIONS, $field_type );
			if ( $processor_class ) {
				$collection_key = md5( $processor_class );
				if ( isset( $this->storage['options'][ $collection_key ] ) ) {
					$this->storage['options'][ $collection_key ] = array();
				}
			}

			// Clear definition fields cache
			$def_processor_class = $this->get_processor( self::PROCESSOR_DEFINITIONS, $field_type );
			if ( $def_processor_class ) {
				$definition_key = md5( $def_processor_class );
				if ( isset( $this->storage['definitions'][ $definition_key ] ) ) {
					unset( $this->storage['definitions'][ $definition_key ] );
				}
			}
		} elseif ( null !== $field_type && null !== $post_id ) {
			$processor_class = $this->get_processor( self::PROCESSOR_COLLECTIONS, $field_type );
			if ( $processor_class ) {
				$cache_key = md5( $processor_class );
				if ( isset( $this->storage['options'][ $cache_key ][ $post_id ] ) ) {
					unset( $this->storage['options'][ $cache_key ][ $post_id ] );
				}
			}
		}

		/**
		 * Action after clearing cache.
		 *
		 * @since 3.1.0
		 *
		 * @param string|null $field_type The field type or null if all.
		 * @param int|null    $post_id    The post ID or null if all.
		 */
		do_action( 'divi_squad_custom_fields_cache_cleared', $field_type, $post_id );
	}

	/**
	 * Clear manager cache for a specific manager type.
	 *
	 * @param string|null $manager_type The manager type to clear or null for all managers.
	 *
	 * @return void
	 */
	public function clear_manager_cache( ?string $manager_type = null ): void {
		if ( null === $manager_type ) {
			// Clear all manager caches
			$this->storage['managers'] = array();

			// Also clear all manager instances
			foreach ( $this->processor_registry[ self::PROCESSOR_MANAGERS ] as $type => $class ) {
				$cache_key = md5( $class );
				if ( isset( $this->storage['instances'][ $cache_key ] ) ) {
					// If the manager implements ManagerInterface, call its clear_cache method
					if ( $this->storage['instances'][ $cache_key ] instanceof Custom_Fields\ManagerInterface ) {
						$this->storage['instances'][ $cache_key ]->clear_cache();
					}

					// Remove the instance from storage
					unset( $this->storage['instances'][ $cache_key ] );
				}
			}
		} else {
			// Clear only the specified manager type
			$processor_class = $this->get_processor( self::PROCESSOR_MANAGERS, $manager_type );
			if ( $processor_class ) {
				// Find and remove all manager instances with this class
				foreach ( $this->storage['managers'] as $key => $manager ) {
					if ( $manager instanceof $processor_class ) {
						// Call clear_cache on the manager if it implements ManagerInterface
						if ( $manager instanceof Custom_Fields\ManagerInterface ) {
							$manager->clear_cache();
						}

						// Remove from storage
						unset( $this->storage['managers'][ $key ] );
					}
				}

				// Also clear the class instance
				$cache_key = md5( $processor_class );
				if ( isset( $this->storage['instances'][ $cache_key ] ) ) {
					if ( $this->storage['instances'][ $cache_key ] instanceof Custom_Fields\ManagerInterface ) {
						$this->storage['instances'][ $cache_key ]->clear_cache();
					}

					unset( $this->storage['instances'][ $cache_key ] );
				}
			}
		}

		/**
		 * Action after clearing manager cache.
		 *
		 * @since 3.1.0
		 *
		 * @param string|null $manager_type The manager type or null if all.
		 */
		do_action( 'divi_squad_manager_cache_cleared', $manager_type );
	}
}
