<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * DiviSquad AI Abilities Integration
 *
 * This file contains the Ai class which registers the plugin's AI capabilities
 * following the official WordPress AI Abilities API (WordPress 6.9+).
 *
 * @since      3.5.0
 * @package DiviSquad
 * @author     The WP Squad <support@squadmodules.com>
 * @license    GPL-3.0-only
 * @link       https://squadmodules.com
 * @see        https://developer.wordpress.org/apis/abilities-api/
 * @see        https://make.wordpress.org/core/2025/11/10/abilities-api-in-wordpress-6-9/
 */

namespace DiviSquad\Core\Supports;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use Throwable;
use WP_Error;

/**
 * AI Abilities Manager.
 *
 * Registers and manages AI capabilities for the Squad Modules plugin using the
 * official WordPress Abilities API. Categories are registered on
 * `wp_abilities_api_categories_init` and abilities on `wp_abilities_api_init`,
 * per the API lifecycle (registering abilities on any other hook triggers
 * `_doing_it_wrong()` and fails).
 *
 * @see     https://developer.wordpress.org/apis/abilities-api/
 *
 * @since   3.5.0
 * @package DiviSquad\Core\Supports
 */
class Ai {

	/**
	 * Ability category ID shared by every Squad Modules ability.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public const CATEGORY = 'squad-modules-for-divi';

	/**
	 * Ability ID: content generation.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public const ABILITY_GENERATE_CONTENT = 'squad-modules-for-divi/generate-content';

	/**
	 * Ability ID: design assistance.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public const ABILITY_DESIGN_ASSISTANCE = 'squad-modules-for-divi/design-assistance';

	/**
	 * Ability ID: module enhancement.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public const ABILITY_ENHANCE_MODULE = 'squad-modules-for-divi/enhance-module';

	/**
	 * AI constructor.
	 *
	 * Wires the Abilities API lifecycle hooks. Categories must be registered
	 * before abilities, so they run on the earlier `*_categories_init` hook.
	 *
	 * @since 3.5.0
	 */
	public function __construct() {
		add_action( 'wp_abilities_api_categories_init', array( $this, 'register_ability_category' ) );
		add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );

		/**
		 * Action triggered after AI is initialized.
		 *
		 * Allows for additional setup or integration steps after the AI
		 * component has been initialized.
		 *
		 * @since 3.5.0
		 *
		 * @param Ai $ai The AI instance.
		 */
		do_action( 'divi_squad_ai_init', $this );
	}

	/**
	 * Register the Squad Modules ability category.
	 *
	 * Runs on `wp_abilities_api_categories_init` so the category exists before
	 * any ability referencing it is registered.
	 *
	 * @since  3.5.0
	 * @access public
	 *
	 * @return void
	 */
	public function register_ability_category(): void {
		try {
			if ( ! function_exists( 'wp_register_ability_category' ) ) {
				return;
			}

			$category_config = array(
				'label'       => __( 'Squad Modules', 'squad-modules-for-divi' ),
				'description' => __( 'AI capabilities provided by Squad Modules for Divi.', 'squad-modules-for-divi' ),
			);

			/**
			 * Filter the Squad Modules ability category configuration.
			 *
			 * @since 3.5.0
			 *
			 * @param array<string, mixed> $category_config The category configuration.
			 * @param Ai                   $ai              The AI instance.
			 */
			$category_config = apply_filters( 'divi_squad_ai_ability_category', $category_config, $this );

			wp_register_ability_category( self::CATEGORY, $category_config );
		} catch ( Throwable $e ) {
			divi_squad()->log_error(
				$e,
				'Failed to register AI ability category',
				false,
				array( 'function' => __METHOD__ )
			);
		}
	}

	/**
	 * Register AI Abilities.
	 *
	 * Registers the plugin's AI capabilities with WordPress following the
	 * official Abilities API guidelines. Runs on `wp_abilities_api_init`.
	 *
	 * @since  3.5.0
	 * @access public
	 *
	 * @return void
	 */
	public function register_abilities(): void {
		try {
			// Check if the Abilities API is available.
			if ( ! function_exists( 'wp_register_ability' ) ) {
				/**
				 * Fires when AI Abilities API is not available.
				 *
				 * @since 3.5.0
				 *
				 * @param Ai $ai The AI instance.
				 */
				do_action( 'divi_squad_ai_abilities_api_not_available', $this );

				return;
			}

			/**
			 * Fires before AI abilities are registered.
			 *
			 * Allows for custom setup before abilities are registered.
			 *
			 * @since 3.5.0
			 *
			 * @param Ai $ai The AI instance.
			 */
			do_action( 'divi_squad_before_register_ai_abilities', $this );

			// Register content generation ability.
			$this->register_content_generation_ability();

			// Register design assistance ability.
			$this->register_design_assistance_ability();

			// Register module enhancement ability.
			$this->register_module_enhancement_ability();

			/**
			 * Fires after AI abilities are registered.
			 *
			 * Allows for additional processing after abilities have been registered.
			 *
			 * @since 3.5.0
			 *
			 * @param Ai $ai The AI instance.
			 */
			do_action( 'divi_squad_after_register_ai_abilities', $this );
		} catch ( Throwable $e ) {
			// Log the error.
			divi_squad()->log_error(
				$e,
				'Failed to register AI abilities',
				false,
				array( 'function' => __METHOD__ )
			);

			/**
			 * Fires when an error occurs during AI abilities registration.
			 *
			 * @since 3.5.0
			 *
			 * @param Throwable $error The error that occurred.
			 * @param Ai        $ai    The AI instance.
			 */
			do_action( 'divi_squad_ai_abilities_registration_error', $e, $this );
		}
	}

	/**
	 * Default meta block shared by Squad Modules abilities.
	 *
	 * All current abilities are advisory/generative: they return suggestions or
	 * generated text and never persist changes, so they are read-only and
	 * non-destructive. They are not idempotent because AI output varies between
	 * identical calls.
	 *
	 * @since 3.5.0
	 *
	 * @return array<string, mixed> The shared meta configuration.
	 */
	private function default_ability_meta(): array {
		return array(
			'show_in_rest' => true,
			'mcp'          => array(
				'public' => true,
			),
			'annotations'  => array(
				'readonly'    => true,
				'destructive' => false,
				'idempotent'  => false,
			),
		);
	}

	/**
	 * Register Content Generation Ability.
	 *
	 * @since  3.5.0
	 * @access private
	 *
	 * @return void
	 *
	 * @throws Throwable When an error occurs during ability registration (handled internally).
	 */
	private function register_content_generation_ability(): void {
		try {
			$ability_config = array(
				'label'               => __( 'Generate Content', 'squad-modules-for-divi' ),
				'description'         => __( 'Generate and enhance text content for Squad Modules using AI capabilities.', 'squad-modules-for-divi' ),
				'category'            => self::CATEGORY,
				'execute_callback'    => array( $this, 'execute_content_generation' ),
				'permission_callback' => array( $this, 'check_edit_posts_permission' ),
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'prompt'      => array(
							'type'        => 'string',
							'description' => __( 'The content generation prompt or description.', 'squad-modules-for-divi' ),
						),
						'module_type' => array(
							'type'        => 'string',
							'description' => __( 'The type of Squad Module to generate content for.', 'squad-modules-for-divi' ),
						),
						'tone'        => array(
							'type'        => 'string',
							'enum'        => array( 'professional', 'casual', 'friendly', 'formal' ),
							'default'     => 'professional',
							'description' => __( 'The tone of the generated content.', 'squad-modules-for-divi' ),
						),
						'length'      => array(
							'type'        => 'string',
							'enum'        => array( 'short', 'medium', 'long' ),
							'default'     => 'medium',
							'description' => __( 'The desired length of the generated content.', 'squad-modules-for-divi' ),
						),
					),
					'required'             => array( 'prompt' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'content' => array(
							'type'        => 'string',
							'description' => __( 'The generated content.', 'squad-modules-for-divi' ),
						),
						'success' => array(
							'type'        => 'boolean',
							'description' => __( 'Whether the content generation was successful.', 'squad-modules-for-divi' ),
						),
					),
					'required'   => array( 'content', 'success' ),
				),
				'meta'                => $this->default_ability_meta(),
			);

			/**
			 * Filter the content generation ability configuration.
			 *
			 * @since 3.5.0
			 *
			 * @param array<string, mixed> $ability_config The ability configuration.
			 * @param Ai                   $ai             The AI instance.
			 */
			$ability_config = apply_filters( 'divi_squad_ai_content_generation_ability', $ability_config, $this );

			wp_register_ability( self::ABILITY_GENERATE_CONTENT, $ability_config );

			/**
			 * Fires after content generation ability is registered.
			 *
			 * @since 3.5.0
			 *
			 * @param array<string, mixed> $ability_config The registered ability configuration.
			 * @param Ai                   $ai             The AI instance.
			 */
			do_action( 'divi_squad_ai_content_generation_registered', $ability_config, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error(
				$e,
				'Failed to register content generation ability',
				false,
				array( 'function' => __METHOD__ )
			);

			/**
			 * Fires when content generation ability registration fails.
			 *
			 * @since 3.5.0
			 *
			 * @param Throwable $error The error that occurred.
			 * @param Ai        $ai    The AI instance.
			 */
			do_action( 'divi_squad_ai_content_generation_error', $e, $this );
		}
	}

	/**
	 * Register Design Assistance Ability.
	 *
	 * @since  3.5.0
	 * @access private
	 *
	 * @return void
	 *
	 * @throws Throwable When an error occurs during ability registration (handled internally).
	 */
	private function register_design_assistance_ability(): void {
		try {
			$ability_config = array(
				'label'               => __( 'Design Assistance', 'squad-modules-for-divi' ),
				'description'         => __( 'Get AI-powered design suggestions and improvements for Squad Modules.', 'squad-modules-for-divi' ),
				'category'            => self::CATEGORY,
				'execute_callback'    => array( $this, 'execute_design_assistance' ),
				'permission_callback' => array( $this, 'check_edit_posts_permission' ),
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'module_type'   => array(
							'type'        => 'string',
							'description' => __( 'The type of Squad Module to analyze.', 'squad-modules-for-divi' ),
						),
						'current_style' => array(
							'type'        => 'string',
							'description' => __( 'Description of the current module styling.', 'squad-modules-for-divi' ),
						),
						'design_goal'   => array(
							'type'        => 'string',
							'description' => __( 'The desired design goal or outcome.', 'squad-modules-for-divi' ),
						),
						'audience'      => array(
							'type'        => 'string',
							'description' => __( 'The target audience for the design.', 'squad-modules-for-divi' ),
						),
					),
					'required'             => array( 'module_type' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'suggestions' => array(
							'type'        => 'array',
							'items'       => array(
								'type' => 'string',
							),
							'description' => __( 'Array of design suggestions.', 'squad-modules-for-divi' ),
						),
						'success'     => array(
							'type'        => 'boolean',
							'description' => __( 'Whether the design analysis was successful.', 'squad-modules-for-divi' ),
						),
					),
					'required'   => array( 'suggestions', 'success' ),
				),
				'meta'                => $this->default_ability_meta(),
			);

			/**
			 * Filter the design assistance ability configuration.
			 *
			 * @since 3.5.0
			 *
			 * @param array<string, mixed> $ability_config The ability configuration.
			 * @param Ai                   $ai             The AI instance.
			 */
			$ability_config = apply_filters( 'divi_squad_ai_design_assistance_ability', $ability_config, $this );

			wp_register_ability( self::ABILITY_DESIGN_ASSISTANCE, $ability_config );

			/**
			 * Fires after design assistance ability is registered.
			 *
			 * @since 3.5.0
			 *
			 * @param array<string, mixed> $ability_config The registered ability configuration.
			 * @param Ai                   $ai             The AI instance.
			 */
			do_action( 'divi_squad_ai_design_assistance_registered', $ability_config, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error(
				$e,
				'Failed to register design assistance ability',
				false,
				array( 'function' => __METHOD__ )
			);

			/**
			 * Fires when design assistance ability registration fails.
			 *
			 * @since 3.5.0
			 *
			 * @param Throwable $error The error that occurred.
			 * @param Ai        $ai    The AI instance.
			 */
			do_action( 'divi_squad_ai_design_assistance_error', $e, $this );
		}
	}

	/**
	 * Register Module Enhancement Ability.
	 *
	 * @since  3.5.0
	 * @access private
	 *
	 * @return void
	 *
	 * @throws Throwable When an error occurs during ability registration (handled internally).
	 */
	private function register_module_enhancement_ability(): void {
		try {
			$ability_config = array(
				'label'               => __( 'Module Enhancement', 'squad-modules-for-divi' ),
				'description'         => __( 'Enhance and optimize Squad Modules with AI-powered features and suggestions.', 'squad-modules-for-divi' ),
				'category'            => self::CATEGORY,
				'execute_callback'    => array( $this, 'execute_module_enhancement' ),
				'permission_callback' => array( $this, 'check_edit_posts_permission' ),
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'module_type'      => array(
							'type'        => 'string',
							'description' => __( 'The type of Squad Module to enhance.', 'squad-modules-for-divi' ),
						),
						'current_config'   => array(
							'type'        => 'object',
							'description' => __( 'The current module configuration.', 'squad-modules-for-divi' ),
						),
						'enhancement_type' => array(
							'type'        => 'string',
							'enum'        => array( 'performance', 'accessibility', 'seo', 'ux' ),
							'description' => __( 'The type of enhancement to apply.', 'squad-modules-for-divi' ),
						),
					),
					'required'             => array( 'module_type', 'enhancement_type' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'enhancements' => array(
							'type'        => 'object',
							'description' => __( 'The recommended enhancements.', 'squad-modules-for-divi' ),
						),
						'success'      => array(
							'type'        => 'boolean',
							'description' => __( 'Whether the enhancement was successful.', 'squad-modules-for-divi' ),
						),
					),
					'required'   => array( 'enhancements', 'success' ),
				),
				'meta'                => $this->default_ability_meta(),
			);

			/**
			 * Filter the module enhancement ability configuration.
			 *
			 * @since 3.5.0
			 *
			 * @param array<string, mixed> $ability_config The ability configuration.
			 * @param Ai                   $ai             The AI instance.
			 */
			$ability_config = apply_filters( 'divi_squad_ai_module_enhancement_ability', $ability_config, $this );

			wp_register_ability( self::ABILITY_ENHANCE_MODULE, $ability_config );

			/**
			 * Fires after module enhancement ability is registered.
			 *
			 * @since 3.5.0
			 *
			 * @param array<string, mixed> $ability_config The registered ability configuration.
			 * @param Ai                   $ai             The AI instance.
			 */
			do_action( 'divi_squad_ai_module_enhancement_registered', $ability_config, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error(
				$e,
				'Failed to register module enhancement ability',
				false,
				array( 'function' => __METHOD__ )
			);

			/**
			 * Fires when module enhancement ability registration fails.
			 *
			 * @since 3.5.0
			 *
			 * @param Throwable $error The error that occurred.
			 * @param Ai        $ai    The AI instance.
			 */
			do_action( 'divi_squad_ai_module_enhancement_error', $e, $this );
		}
	}

	/**
	 * Execute Content Generation Ability.
	 *
	 * Invoked by the Abilities API. Returns the generated content on success or
	 * a {@see WP_Error} (using the plugin's standardized error vocabulary) when
	 * the input is invalid or generation fails.
	 *
	 * @since  3.5.0
	 * @access public
	 *
	 * @param array<string, mixed>|null $input The input parameters from the AI request.
	 *
	 * @return array<string, mixed>|WP_Error The generated content and status, or an error.
	 */
	public function execute_content_generation( $input = null ) {
		try {
			$input = is_array( $input ) ? $input : array();

			// Required-field validation (does not use empty(): a "0" prompt is valid input).
			if ( ! isset( $input['prompt'] ) || ! is_string( $input['prompt'] ) || '' === $input['prompt'] ) {
				return new WP_Error(
					'divi_squad_missing_prompt',
					__( 'A prompt is required to generate content.', 'squad-modules-for-divi' )
				);
			}

			// Apply schema defaults the Abilities API does not inject into the callback.
			if ( ! isset( $input['tone'] ) || ! is_string( $input['tone'] ) || '' === $input['tone'] ) {
				$input['tone'] = 'professional';
			}
			if ( ! isset( $input['length'] ) || ! is_string( $input['length'] ) || '' === $input['length'] ) {
				$input['length'] = 'medium';
			}

			/**
			 * Filter the content generation input before processing.
			 *
			 * @since 3.5.0
			 *
			 * @param array<string, mixed> $input The input parameters.
			 * @param Ai                   $ai    The AI instance.
			 */
			$input = (array) apply_filters( 'divi_squad_ai_content_generation_input', $input, $this );

			// Generate content (placeholder for actual AI integration).
			$generated_content = $this->generate_content_from_prompt( $input );

			/**
			 * Filter the generated content before returning.
			 *
			 * @since 3.5.0
			 *
			 * @param string               $generated_content The generated content.
			 * @param array<string, mixed> $input             The input parameters.
			 * @param Ai                   $ai                The AI instance.
			 */
			$generated_content = apply_filters( 'divi_squad_ai_generated_content', $generated_content, $input, $this );

			return array(
				'content' => $generated_content,
				'success' => '' !== $generated_content,
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error(
				$e,
				'Content generation execution failed',
				false,
				array( 'function' => __METHOD__ )
			);

			return new WP_Error(
				'divi_squad_content_data_unavailable',
				__( 'Unable to generate content. Please try again.', 'squad-modules-for-divi' )
			);
		}
	}

	/**
	 * Execute Design Assistance Ability.
	 *
	 * @since  3.5.0
	 * @access public
	 *
	 * @param array<string, mixed>|null $input The input parameters from the AI request.
	 *
	 * @return array<string, mixed>|WP_Error The design suggestions and status, or an error.
	 */
	public function execute_design_assistance( $input = null ) {
		try {
			$input = is_array( $input ) ? $input : array();

			if ( ! isset( $input['module_type'] ) || ! is_string( $input['module_type'] ) || '' === $input['module_type'] ) {
				return new WP_Error(
					'divi_squad_missing_module_type',
					__( 'A module_type is required to provide design assistance.', 'squad-modules-for-divi' )
				);
			}

			/**
			 * Filter the design assistance input before processing.
			 *
			 * @since 3.5.0
			 *
			 * @param array<string, mixed> $input The input parameters.
			 * @param Ai                   $ai    The AI instance.
			 */
			$input = (array) apply_filters( 'divi_squad_ai_design_assistance_input', $input, $this );

			// Generate design suggestions (placeholder for actual AI integration).
			$suggestions = $this->generate_design_suggestions( $input );

			/**
			 * Filter the design suggestions before returning.
			 *
			 * @since 3.5.0
			 *
			 * @param array<string>        $suggestions The design suggestions.
			 * @param array<string, mixed> $input       The input parameters.
			 * @param Ai                   $ai          The AI instance.
			 */
			$suggestions = apply_filters( 'divi_squad_ai_design_suggestions', $suggestions, $input, $this );

			return array(
				'suggestions' => $suggestions,
				'success'     => array() !== $suggestions,
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error(
				$e,
				'Design assistance execution failed',
				false,
				array( 'function' => __METHOD__ )
			);

			return new WP_Error(
				'divi_squad_design_data_unavailable',
				__( 'Unable to generate design suggestions. Please try again.', 'squad-modules-for-divi' )
			);
		}
	}

	/**
	 * Execute Module Enhancement Ability.
	 *
	 * @since  3.5.0
	 * @access public
	 *
	 * @param array<string, mixed>|null $input The input parameters from the AI request.
	 *
	 * @return array<string, mixed>|WP_Error The enhancement recommendations and status, or an error.
	 */
	public function execute_module_enhancement( $input = null ) {
		try {
			$input = is_array( $input ) ? $input : array();

			if ( ! isset( $input['module_type'] ) || ! is_string( $input['module_type'] ) || '' === $input['module_type'] ) {
				return new WP_Error(
					'divi_squad_missing_module_type',
					__( 'A module_type is required to enhance a module.', 'squad-modules-for-divi' )
				);
			}

			if ( ! isset( $input['enhancement_type'] ) || ! is_string( $input['enhancement_type'] ) || '' === $input['enhancement_type'] ) {
				return new WP_Error(
					'divi_squad_missing_enhancement_type',
					__( 'An enhancement_type is required to enhance a module.', 'squad-modules-for-divi' )
				);
			}

			$allowed_types = array( 'performance', 'accessibility', 'seo', 'ux' );
			if ( ! in_array( $input['enhancement_type'], $allowed_types, true ) ) {
				return new WP_Error(
					'divi_squad_invalid_enhancement_type',
					sprintf(
						/* translators: %s: comma-separated list of allowed enhancement types. */
						__( 'The enhancement_type must be one of: %s.', 'squad-modules-for-divi' ),
						implode( ', ', $allowed_types )
					)
				);
			}

			/**
			 * Filter the module enhancement input before processing.
			 *
			 * @since 3.5.0
			 *
			 * @param array<string, mixed> $input The input parameters.
			 * @param Ai                   $ai    The AI instance.
			 */
			$input = (array) apply_filters( 'divi_squad_ai_module_enhancement_input', $input, $this );

			// Generate enhancement recommendations (placeholder for actual AI integration).
			$enhancements = $this->generate_enhancements( $input );

			/**
			 * Filter the enhancements before returning.
			 *
			 * @since 3.5.0
			 *
			 * @param array<string, mixed> $enhancements The enhancement recommendations.
			 * @param array<string, mixed> $input        The input parameters.
			 * @param Ai                   $ai           The AI instance.
			 */
			$enhancements = apply_filters( 'divi_squad_ai_enhancements', $enhancements, $input, $this );

			return array(
				'enhancements' => $enhancements,
				'success'      => array() !== $enhancements,
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error(
				$e,
				'Module enhancement execution failed',
				false,
				array( 'function' => __METHOD__ )
			);

			return new WP_Error(
				'divi_squad_enhancement_data_unavailable',
				__( 'Unable to generate enhancements. Please try again.', 'squad-modules-for-divi' )
			);
		}
	}

	/**
	 * Check Edit Posts Permission.
	 *
	 * Permission callback for every Squad Modules ability. Receives the same
	 * input as the execute callback (unused here) and returns whether the
	 * current user may run the ability.
	 *
	 * @since  3.5.0
	 * @access public
	 *
	 * @param array<string, mixed>|null $input The input parameters (unused).
	 *
	 * @return bool True if user can edit posts, false otherwise.
	 */
	public function check_edit_posts_permission( $input = null ): bool {
		unset( $input );

		return current_user_can( 'edit_posts' );
	}

	/**
	 * Generate Content From Prompt.
	 *
	 * Helper method to generate content based on the provided prompt.
	 * This is a placeholder that should be extended with actual AI integration.
	 *
	 * @since  3.5.0
	 * @access private
	 *
	 * @param array<string, mixed> $input The input parameters including prompt.
	 *
	 * @return string The generated content.
	 */
	private function generate_content_from_prompt( array $input ): string {
		/**
		 * Filter to allow custom content generation implementation.
		 *
		 * @since 3.5.0
		 *
		 * @param string               $content The generated content (empty by default).
		 * @param array<string, mixed> $input   The input parameters.
		 * @param Ai                   $ai      The AI instance.
		 */
		return (string) apply_filters( 'divi_squad_ai_generate_content', '', $input, $this );
	}

	/**
	 * Generate Design Suggestions.
	 *
	 * Helper method to generate design suggestions for a module.
	 * This is a placeholder that should be extended with actual AI integration.
	 *
	 * @since  3.5.0
	 * @access private
	 *
	 * @param array<string, mixed> $input The input parameters.
	 *
	 * @return array<string> Array of design suggestions.
	 */
	private function generate_design_suggestions( array $input ): array {
		/**
		 * Filter to allow custom design suggestion implementation.
		 *
		 * @since 3.5.0
		 *
		 * @param array<string>        $suggestions The design suggestions (empty by default).
		 * @param array<string, mixed> $input       The input parameters.
		 * @param Ai                   $ai          The AI instance.
		 */
		return (array) apply_filters( 'divi_squad_ai_generate_design_suggestions', array(), $input, $this );
	}

	/**
	 * Generate Enhancements.
	 *
	 * Helper method to generate enhancement recommendations for a module.
	 * This is a placeholder that should be extended with actual AI integration.
	 *
	 * @since  3.5.0
	 * @access private
	 *
	 * @param array<string, mixed> $input The input parameters.
	 *
	 * @return array<string, mixed> Array of enhancement recommendations.
	 */
	private function generate_enhancements( array $input ): array {
		/**
		 * Filter to allow custom enhancement implementation.
		 *
		 * @since 3.5.0
		 *
		 * @param array<string, mixed> $enhancements The enhancements (empty by default).
		 * @param array<string, mixed> $input        The input parameters.
		 * @param Ai                   $ai           The AI instance.
		 */
		return (array) apply_filters( 'divi_squad_ai_generate_enhancements', array(), $input, $this );
	}

	/**
	 * Get Registered Abilities.
	 *
	 * Retrieves the Squad Modules abilities from the live Abilities API registry
	 * when available, falling back to the known ability slugs otherwise.
	 *
	 * @since  3.5.0
	 * @access public
	 *
	 * @return array<string, array<string, mixed>> Array of registered abilities keyed by slug.
	 */
	public function get_registered_abilities(): array {
		try {
			$abilities = array();

			if ( function_exists( 'wp_get_abilities' ) ) {
				foreach ( wp_get_abilities() as $ability ) {
					$name = is_object( $ability ) && method_exists( $ability, 'get_name' )
						? $ability->get_name()
						: '';

					if ( '' === $name || 0 !== strpos( $name, self::CATEGORY . '/' ) ) {
						continue;
					}

					$abilities[ $name ] = array(
						'slug'        => $name,
						'label'       => method_exists( $ability, 'get_label' ) ? $ability->get_label() : '',
						'description' => method_exists( $ability, 'get_description' ) ? $ability->get_description() : '',
					);
				}
			}

			// Fallback to the statically known abilities if the registry is empty
			// (e.g. queried before the registration hooks have run).
			if ( array() === $abilities ) {
				$abilities = array(
					self::ABILITY_GENERATE_CONTENT  => array(
						'slug'        => self::ABILITY_GENERATE_CONTENT,
						'label'       => __( 'Generate Content', 'squad-modules-for-divi' ),
						'description' => __( 'Generate and enhance text content for Squad Modules using AI capabilities.', 'squad-modules-for-divi' ),
					),
					self::ABILITY_DESIGN_ASSISTANCE => array(
						'slug'        => self::ABILITY_DESIGN_ASSISTANCE,
						'label'       => __( 'Design Assistance', 'squad-modules-for-divi' ),
						'description' => __( 'Get AI-powered design suggestions and improvements for Squad Modules.', 'squad-modules-for-divi' ),
					),
					self::ABILITY_ENHANCE_MODULE    => array(
						'slug'        => self::ABILITY_ENHANCE_MODULE,
						'label'       => __( 'Module Enhancement', 'squad-modules-for-divi' ),
						'description' => __( 'Enhance and optimize Squad Modules with AI-powered features and suggestions.', 'squad-modules-for-divi' ),
					),
				);
			}

			/**
			 * Filter the registered abilities.
			 *
			 * Allows modification of the registered abilities list.
			 *
			 * @since 3.5.0
			 *
			 * @param array<string, array<string, mixed>> $abilities The registered abilities.
			 * @param Ai                                  $ai        The AI instance.
			 */
			return apply_filters( 'divi_squad_ai_registered_abilities', $abilities, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error(
				$e,
				'Failed to get registered abilities',
				false,
				array( 'function' => __METHOD__ )
			);

			return array();
		}
	}

	/**
	 * Check If Ability Is Registered.
	 *
	 * Checks whether a specific AI ability is registered. Uses the live Abilities
	 * API registry when available, falling back to the known slug list.
	 *
	 * @since  3.5.0
	 * @access public
	 *
	 * @param string $ability_slug The ability slug to check.
	 *
	 * @return bool True if the ability is registered, false otherwise.
	 */
	public function is_ability_registered( string $ability_slug ): bool {
		try {
			if ( function_exists( 'wp_get_ability' ) ) {
				return null !== wp_get_ability( $ability_slug );
			}

			return isset( $this->get_registered_abilities()[ $ability_slug ] );
		} catch ( Throwable $e ) {
			divi_squad()->log_error(
				$e,
				'Failed to check if ability is registered',
				false,
				array(
					'function'     => __METHOD__,
					'ability_slug' => $ability_slug,
				)
			);

			return false;
		}
	}
}
