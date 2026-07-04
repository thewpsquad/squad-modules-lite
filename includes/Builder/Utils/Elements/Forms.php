<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Builder Form Utils Helper Class
 *
 * @since   1.5.0
 * @author  The WP Squad <support@squadmodules.com>
 * @package DiviSquad
 */

namespace DiviSquad\Builder\Utils\Elements;

use InvalidArgumentException;

/**
 * Main class for handling various form types.
 *
 * @since   1.5.0
 * @package DiviSquad
 */
class Forms {
	public const DEFAULT_FORM_ID = 'cfcd208495d565ef66e7dff9f98764da';

	/**
	 * Supported form types with their corresponding processor classes.
	 *
	 * @var array<string, string>
	 */
	protected array $supported_form_types = array(
		'cf7'           => Forms\Collections\ContactForm7::class,
		'wpforms'       => Forms\Collections\WPForms::class,
		'fluent_forms'  => Forms\Collections\FluentForms::class,
		'ninja_forms'   => Forms\Collections\NinjaForms::class,
		'gravity_forms' => Forms\Collections\GravityForms::class,
		'forminator'    => Forms\Collections\Forminator::class,
		'formidable'    => Forms\Collections\Formidable::class,
	);

	/**
	 * Form collections.
	 *
	 * @var array<string, array<string, string>>
	 */
	protected array $collections = array();

	/**
	 * Form processors.
	 *
	 * @var array<string, Forms\CollectionInterface|object>
	 */
	protected array $processors = array();

	/**
	 * Get allowed fields for the module.
	 *
	 * @return array List of allowed field types
	 */
	public function get_allowed_fields(): ?array {
		static $allowed_fields = null;

		if ( null === $allowed_fields ) {
			$allowed_fields = $this->initialize_allowed_fields();
		}

		/**
		 * Filter the allowed fields for the module.
		 *
		 * @since 3.2.0
		 *
		 * @param array $allowed_fields List of allowed field types.
		 */
		return apply_filters( 'divi_squad_form_allowed_fields', $allowed_fields );
	}

	/**
	 * Get custom spacing prefixes for the module.
	 *
	 * @return array Custom spacing prefixes
	 */
	public function get_custom_spacing_prefixes(): ?array {
		static $prefixes = null;

		if ( null === $prefixes ) {
			$prefixes = $this->initialize_custom_spacing_prefixes();
		}

		/**
		 * Filter the custom spacing prefixes.
		 *
		 * @since 3.2.0
		 *
		 * @param array $prefixes Custom spacing prefixes.
		 */
		return apply_filters( 'divi_squad_form_custom_spacing_prefixes', $prefixes );
	}

	/**
	 * Initialize allowed fields.
	 *
	 * @return array List of allowed field types
	 */
	protected function initialize_allowed_fields(): array {
		return array(
			'input[type=email]',
			'input[type=text]',
			'input[type=url]',
			'input[type=tel]',
			'input[type=number]',
			'input[type=date]',
			'input[type=file]',
			'select',
			'textarea',
		);
	}

	/**
	 * Initialize custom spacing prefixes.
	 *
	 * @return array Custom spacing prefixes
	 */
	protected function initialize_custom_spacing_prefixes(): array {
		return array(
			'wrapper'         => array( 'label' => __( 'Wrapper', 'squad-modules-for-divi' ) ),
			'field'           => array( 'label' => __( 'Field', 'squad-modules-for-divi' ) ),
			'message_error'   => array( 'label' => __( 'Message', 'squad-modules-for-divi' ) ),
			'message_success' => array( 'label' => __( 'Message', 'squad-modules-for-divi' ) ),
		);
	}

	/**
	 * Get all forms of a specific type.
	 *
	 * @param string $form_type  The form type (cf7, fluent_forms, etc.).
	 * @param string $collection The collection type (title or id).
	 *
	 * @return array<string, string>
	 * @throws InvalidArgumentException If the form type is not supported.
	 */
	public function get_forms_by( string $form_type, string $collection = 'title' ): array {
		if ( ! isset( $this->supported_form_types[ $form_type ] ) ) {
			throw new InvalidArgumentException( esc_html__( 'Unsupported form type.', 'squad-modules-for-divi' ) );
		}

		if ( ! isset( $this->collections[ $form_type ][ $collection ] ) ) {
			$this->collections[ $form_type ][ $collection ] = $this->fetch_forms( $form_type, $collection );
		}

		return array_merge(
			array( static::DEFAULT_FORM_ID => esc_html__( 'Select a form', 'squad-modules-for-divi' ) ),
			(array) $this->collections[ $form_type ][ $collection ]
		);
	}

	/**
	 * Fetch forms of a specific type.
	 *
	 * @param string $form_type  The form type (cf7, fluent_forms, etc.).
	 * @param string $collection The collection type (title or id).
	 *
	 * @return array<string, string>
	 */
	protected function fetch_forms( string $form_type, string $collection ): array {
		if ( ! isset( $this->processors[ $form_type ] ) ) {
			$this->processors[ $form_type ] = new $this->supported_form_types[ $form_type ]();
		}

		return $this->processors[ $form_type ]->get_forms( $collection );
	}
}
