<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Builder Form Utils Helper Class
 *
 * Provides utilities for handling various form types in Divi modules.
 *
 * @since   1.5.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Utils\Elements;

use DiviSquad\Builder\Utils\Elements\Forms\CollectionInterface;
use InvalidArgumentException;

/**
 * Main class for handling various form types.
 *
 * This class provides methods for retrieving, processing, and managing
 * forms from different form plugins within WordPress.
 *
 * @since   1.5.0
 * @package DiviSquad
 */
class Forms {
	/**
	 * Default form ID used as placeholder when no form is selected.
	 *
	 * @since 1.5.0
	 * @var string
	 */
	public const DEFAULT_FORM_ID = 'cfcd208495d565ef66e7dff9f98764da';

	/**
	 * Supported form types with their corresponding processor classes.
	 *
	 * @since 1.5.0
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
	 * Form collections cache.
	 *
	 * @since 1.5.0
	 * @var array<string, array<string, array<string, string>>>
	 */
	protected array $collections = array();

	/**
	 * Form processor instances.
	 *
	 * @since 1.5.0
	 * @var array<string, CollectionInterface>
	 */
	protected array $processors = array();

	/**
	 * Get allowed fields for the module.
	 *
	 * Returns a list of HTML elements that are allowed to be targeted
	 * in form modules.
	 *
	 * @since 1.5.0
	 *
	 * @return array<int, string>|null List of allowed field types.
	 */
	public function get_allowed_fields(): ?array {
		static $allowed_fields = null;

		if ( null === $allowed_fields ) {
			$allowed_fields = $this->initialize_allowed_fields();
		}

		/**
		 * Filters the allowed fields for the form module.
		 *
		 * @since 3.2.0
		 *
		 * @param array<int, string> $allowed_fields List of allowed field types.
		 */
		return apply_filters( 'divi_squad_form_allowed_fields', $allowed_fields );
	}

	/**
	 * Get custom spacing prefixes for the module.
	 *
	 * Returns an array of prefixes used for custom spacing in form modules.
	 *
	 * @since 1.5.0
	 *
	 * @return array<string, array<string, string>>|null Custom spacing prefixes.
	 */
	public function get_custom_spacing_prefixes(): ?array {
		static $prefixes = null;

		if ( null === $prefixes ) {
			$prefixes = $this->initialize_custom_spacing_prefixes();
		}

		/**
		 * Filters the custom spacing prefixes for form modules.
		 *
		 * @since 3.2.0
		 *
		 * @param array<string, array<string, string>> $prefixes Custom spacing prefixes.
		 */
		return apply_filters( 'divi_squad_form_custom_spacing_prefixes', $prefixes );
	}

	/**
	 * Initialize allowed fields.
	 *
	 * Defines the list of HTML elements that are allowed to be targeted
	 * in form modules.
	 *
	 * @since 1.5.0
	 *
	 * @return array<int, string> List of allowed field types.
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
	 * Defines the spacing prefixes used for custom spacing in form modules.
	 *
	 * @since 1.5.0
	 *
	 * @return array<string, array<string, string>> Custom spacing prefixes.
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
		try {
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
		} catch ( InvalidArgumentException $e ) {
			// Re-throw InvalidArgumentException as it's expected to be handled by caller
			throw $e;
		} catch ( \Throwable $e ) {
			// Log other unexpected errors
			divi_squad()->log_error( $e, "Error in get_forms_by for {$form_type}" );

			// Return just the default option
			return array( static::DEFAULT_FORM_ID => esc_html__( 'Select a form', 'squad-modules-for-divi' ) );
		}
	}

	/**
	 * Fetch forms of a specific type.
	 *
	 * @since 3.2.0
	 *
	 * @param string $form_type  The form type (cf7, fluent_forms, etc.).
	 * @param string $collection The collection type (title or id).
	 *
	 * @return array<string, string> An array of form keys and labels.
	 */
	protected function fetch_forms( string $form_type, string $collection ): array {
		try {
			if ( ! isset( $this->processors[ $form_type ] ) ) {
				$this->processors[ $form_type ] = new $this->supported_form_types[ $form_type ]();
			}

			return $this->processors[ $form_type ]->get_forms( $collection );
		} catch ( \Throwable $e ) {
			$error_message = sprintf( 'Error fetching forms for %s', $form_type );

			/**
			 * Filters the error message when failing to fetch forms.
			 *
			 * @since 3.2.0
			 *
			 * @param string     $error_message The error message to be logged.
			 * @param string     $form_type     The form type that failed (cf7, fluent_forms, etc.).
			 * @param \Throwable $e             The exception that was thrown.
			 */
			$error_message = apply_filters( 'divi_squad_form_fetch_error_message', $error_message, $form_type, $e );

			// Log the error
			divi_squad()->log_error( $e, $error_message );

			/**
			 * Fires after a form fetch error occurs.
			 *
			 * @since 3.2.0
			 *
			 * @param string     $form_type  The form type that failed (cf7, fluent_forms, etc.).
			 * @param string     $collection The collection type (title or id).
			 * @param \Throwable $e          The exception that was thrown.
			 */
			do_action( 'divi_squad_form_fetch_error', $form_type, $collection, $e );

			return array();
		}
	}
}
