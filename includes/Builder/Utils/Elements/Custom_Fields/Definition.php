<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Definition Abstract Class
 *
 * This file contains the Definition abstract class which provides a base
 * implementation for all definition classes in the DiviSquad plugin.
 *
 * @since   3.1.0
 * @author  The WP Squad <support@squadmodules.com>
 * @package DiviSquad
 */

namespace DiviSquad\Builder\Utils\Elements\Custom_Fields;

/**
 * Class Definition
 *
 * Provides a base implementation for definition classes in the DiviSquad plugin.
 * Definition classes are responsible for defining the UI components for custom fields.
 *
 * @since   3.1.0
 * @package DiviSquad
 */
abstract class Definition implements DefinitionInterface {

	/**
	 * Collection of common field definitions.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	protected array $common_fields = array();

	/**
	 * Collection of empty field definitions.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	protected array $empty_fields = array();

	/**
	 * Collection of post type specific field definitions.
	 *
	 * @var array<string, array<string, array<string, mixed>>>
	 */
	protected array $post_type_fields = array();

	/**
	 * Collection of associated field definitions.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	protected array $associated_fields = array();

	/**
	 * Collection of not eligible field definitions.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	protected array $not_eligible_fields = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Initialize the definition.
	 *
	 * @return void
	 */
	protected function init(): void {
		// Child classes can override this to perform initialization.
	}

	/**
	 * Get common fields that are applicable across different post types.
	 *
	 * @return array<string, array<string, mixed>> An array of common custom field definitions.
	 */
	public function get_common_fields(): array {
		/**
		 * Filter the common fields.
		 *
		 * @since 3.1.0
		 *
		 * @param array $common_fields The common fields.
		 */
		return apply_filters( 'divi_squad_definition_common_fields', $this->common_fields );
	}

	/**
	 * Get an array of empty fields.
	 *
	 * @return array<string, array<string, mixed>> An array of empty custom field definitions.
	 */
	public function get_empty_fields(): array {
		/**
		 * Filter the empty fields.
		 *
		 * @since 3.1.0
		 *
		 * @param array $empty_fields The empty fields.
		 */
		return apply_filters( 'divi_squad_definition_empty_fields', $this->empty_fields );
	}

	/**
	 * Get default fields for a specific post type.
	 *
	 * @param string $post_type The post type for which to retrieve default fields.
	 * @param array  $options   Additional options to customize the returned fields.
	 *
	 * @return array<string, array<string, mixed>> An array of default custom field definitions for the specified post type.
	 */
	public function get_default_fields( string $post_type, array $options ): array {
		$default_fields = $this->post_type_fields[ $post_type ] ?? array();

		/**
		 * Filter the default fields for a post type.
		 *
		 * @since 3.1.0
		 *
		 * @param array  $default_fields The default fields.
		 * @param string $post_type      The post type.
		 * @param array  $options        The options.
		 */
		return apply_filters( 'divi_squad_definition_default_fields', $default_fields, $post_type, $options );
	}

	/**
	 * Get associated fields.
	 *
	 * @param array $fields_types Collect custom fields types.
	 *
	 * @return array<string, array<string, mixed>> An array of associated custom field definitions.
	 */
	public function get_associated_fields( array $fields_types = array() ): array {
		/**
		 * Filter the associated fields.
		 *
		 * @since 3.1.0
		 *
		 * @param array $associated_fields The associated fields.
		 * @param array $fields_types      The field types.
		 */
		return apply_filters( 'divi_squad_definition_associated_fields', $this->associated_fields, $fields_types );
	}

	/**
	 * Get fields that are not eligible.
	 *
	 * @return array<string, array<string, mixed>> An array of custom field definitions that are not eligible.
	 */
	public function get_not_eligible_fields(): array {
		/**
		 * Filter the not eligible fields.
		 *
		 * @since 3.1.0
		 *
		 * @param array $not_eligible_fields The not eligible fields.
		 */
		return apply_filters( 'divi_squad_definition_not_eligible_fields', $this->not_eligible_fields );
	}

	/**
	 * Determines if this definition processor is eligible to provide field definitions.
	 *
	 * @return bool Whether this definition processor can be used.
	 */
	public function is_eligible(): bool {
		/**
		 * Filter the eligibility of this definition processor.
		 *
		 * @since 3.1.0
		 *
		 * @param bool $is_eligible Whether this definition processor is eligible.
		 */
		return apply_filters( 'divi_squad_definition_is_eligible', true );
	}

	/**
	 * Register a common field definition.
	 *
	 * @param string $field_key  The field key.
	 * @param array  $field_data The field data.
	 *
	 * @return self
	 */
	protected function register_common_field( string $field_key, array $field_data ): self {
		$this->common_fields[ $field_key ] = $field_data;

		return $this;
	}

	/**
	 * Register an empty field definition.
	 *
	 * @param string $field_key  The field key.
	 * @param array  $field_data The field data.
	 *
	 * @return self
	 */
	protected function register_empty_field( string $field_key, array $field_data ): self {
		$this->empty_fields[ $field_key ] = $field_data;

		return $this;
	}

	/**
	 * Register a post type field definition.
	 *
	 * @param string $post_type  The post type.
	 * @param string $field_key  The field key.
	 * @param array  $field_data The field data.
	 *
	 * @return self
	 */
	protected function register_post_type_field( string $post_type, string $field_key, array $field_data ): self {
		if ( ! isset( $this->post_type_fields[ $post_type ] ) ) {
			$this->post_type_fields[ $post_type ] = array();
		}

		$this->post_type_fields[ $post_type ][ $field_key ] = $field_data;

		return $this;
	}

	/**
	 * Register an associated field definition.
	 *
	 * @param string $field_key  The field key.
	 * @param array  $field_data The field data.
	 *
	 * @return self
	 */
	protected function register_associated_field( string $field_key, array $field_data ): self {
		$this->associated_fields[ $field_key ] = $field_data;

		return $this;
	}

	/**
	 * Register a not eligible field definition.
	 *
	 * @param string $field_key  The field key.
	 * @param array  $field_data The field data.
	 *
	 * @return self
	 */
	protected function register_not_eligible_field( string $field_key, array $field_data ): self {
		$this->not_eligible_fields[ $field_key ] = $field_data;

		return $this;
	}
}
