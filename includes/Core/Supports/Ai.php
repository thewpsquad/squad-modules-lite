<?php // phpcs:ignore WordPress.Files.FileName

/**
 * DiviSquad AI Abilities Integration
 *
 * This file contains the Ai class which registers the plugin's AI capabilities
 * following the official WordPress AI Abilities API guidelines.
 *
 * @since      3.5.0
 * @package    DiviSquad
 * @author     The WP Squad <support@squadmodules.com>
 * @license    GPL-3.0-only
 * @link       https://squadmodules.com
 * @see        https://make.wordpress.org/ai/2025/07/17/abilities-api/
 */

namespace DiviSquad\Core\Supports;

use Throwable;

/**
 * AI Abilities Manager.
 *
 * Registers and manages AI capabilities for the Squad Modules plugin
 * following the official WordPress AI Abilities API guidelines.
 *
 * @see     https://make.wordpress.org/ai/2025/07/17/abilities-api/
 *
 * @since   3.5.0
 * @package DiviSquad\Core\Supports
 */
class Ai {

	/**
	 * AI constructor.
	 *
	 * Initializes the AI Abilities integration by registering hooks
	 * to define plugin capabilities on the init action.
	 *
	 * @since 3.5.0
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_abilities' ), 10 );

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
	 * Register AI Abilities.
	 *
	 * Registers the plugin's AI capabilities with WordPress following
	 * the official AI Abilities API guidelines.
	 *
	 * @since  3.5.0
	 * @access public
	 *
	 * @return void
	 */
	public function register_abilities(): void {
		try {
			// Check if the AI Abilities API is available.
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
	 * Register Content Generation Ability.
	 *
	 * Registers the content generation capability for AI-powered content creation
	 * within Divi modules. This ability allows AI to generate text content for
	 * various module fields.
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
				'thinking_message'    => __( 'Generating content for your module...', 'squad-modules-for-divi' ),
				'success_message'     => __( 'Content generated successfully.', 'squad-modules-for-divi' ),
				'execute_callback'    => array( $this, 'execute_content_generation' ),
				'input_schema'        => array(
					'type'                  => 'object',
					'properties'            => array(
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
							'description' => __( 'The tone of the generated content.', 'squad-modules-for-divi' ),
						),
						'length'      => array(
							'type'        => 'string',
							'enum'        => array( 'short', 'medium', 'long' ),
							'description' => __( 'The desired length of the generated content.', 'squad-modules-for-divi' ),
						),
					),
					'required'              => array( 'prompt' ),
					'additional_properties' => false,
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
				'permission_callback' => static function (): bool {
					return \current_user_can( 'edit_posts' );
				},
			);

			/**
			 * Filter the content generation ability configuration.
			 *
			 * Allows modification of the content generation ability settings.
			 *
			 * @since 3.5.0
			 *
			 * @param array<string, mixed> $ability_config The ability configuration.
			 * @param Ai                   $ai             The AI instance.
			 */
			$ability_config = apply_filters( 'divi_squad_ai_content_generation_ability', $ability_config, $this );

			wp_register_ability( 'squad-modules-for-divi/generate-content', $ability_config );

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
	 * Registers the design assistance capability for AI-powered design suggestions
	 * and improvements for Divi modules. This ability helps users optimize their
	 * module designs with AI recommendations.
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
				'thinking_message'    => __( 'Analyzing your module design...', 'squad-modules-for-divi' ),
				'success_message'     => __( 'Design suggestions generated successfully.', 'squad-modules-for-divi' ),
				'execute_callback'    => array( $this, 'execute_design_assistance' ),
				'input_schema'        => array(
					'type'                  => 'object',
					'properties'            => array(
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
					'required'              => array( 'module_type' ),
					'additional_properties' => false,
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
				'permission_callback' => static function (): bool {
					return \current_user_can( 'edit_posts' );
				},
			);

			/**
			 * Filter the design assistance ability configuration.
			 *
			 * Allows modification of the design assistance ability settings.
			 *
			 * @since 3.5.0
			 *
			 * @param array<string, mixed> $ability_config The ability configuration.
			 * @param Ai                   $ai             The AI instance.
			 */
			$ability_config = apply_filters( 'divi_squad_ai_design_assistance_ability', $ability_config, $this );

			wp_register_ability( 'squad-modules-for-divi/design-assistance', $ability_config );

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
	 * Registers the module enhancement capability for AI-powered enhancements
	 * and optimizations for Squad Modules. This ability helps optimize module
	 * settings and configurations.
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
				'thinking_message'    => __( 'Optimizing your module settings...', 'squad-modules-for-divi' ),
				'success_message'     => __( 'Module enhanced successfully.', 'squad-modules-for-divi' ),
				'execute_callback'    => array( $this, 'execute_module_enhancement' ),
				'input_schema'        => array(
					'type'                  => 'object',
					'properties'            => array(
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
					'required'              => array( 'module_type', 'enhancement_type' ),
					'additional_properties' => false,
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
				'permission_callback' => static function (): bool {
					return \current_user_can( 'edit_posts' );
				},
			);

			/**
			 * Filter the module enhancement ability configuration.
			 *
			 * Allows modification of the module enhancement ability settings.
			 *
			 * @since 3.5.0
			 *
			 * @param array<string, mixed> $ability_config The ability configuration.
			 * @param Ai                   $ai             The AI instance.
			 */
			$ability_config = apply_filters( 'divi_squad_ai_module_enhancement_ability', $ability_config, $this );

			wp_register_ability( 'squad-modules-for-divi/enhance-module', $ability_config );

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
	 * Callback function that executes the content generation ability.
	 * This function is called by the WordPress AI Abilities API when
	 * the content generation ability is invoked.
	 *
	 * @since  3.5.0
	 * @access public
	 *
	 * @param array<string, mixed> $input The input parameters from the AI request.
	 *
	 * @return array<string, mixed> The generated content and status.
	 */
	public function execute_content_generation( array $input ): array {
		try {
			// Validate required input.
			if ( ! isset( $input['prompt'] ) || '' === $input['prompt'] ) {
				return array(
					'content' => '',
					'success' => false,
				);
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

			return array(
				'content' => '',
				'success' => false,
			);
		}
	}

	/**
	 * Execute Design Assistance Ability.
	 *
	 * Callback function that executes the design assistance ability.
	 * This function is called by the WordPress AI Abilities API when
	 * the design assistance ability is invoked.
	 *
	 * @since  3.5.0
	 * @access public
	 *
	 * @param array<string, mixed> $input The input parameters from the AI request.
	 *
	 * @return array<string, mixed> The design suggestions and status.
	 */
	public function execute_design_assistance( array $input ): array {
		try {
			// Validate required input.
			if ( ! isset( $input['module_type'] ) || '' === $input['module_type'] ) {
				return array(
					'suggestions' => array(),
					'success'     => false,
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

			return array(
				'suggestions' => array(),
				'success'     => false,
			);
		}
	}

	/**
	 * Execute Module Enhancement Ability.
	 *
	 * Callback function that executes the module enhancement ability.
	 * This function is called by the WordPress AI Abilities API when
	 * the module enhancement ability is invoked.
	 *
	 * @since  3.5.0
	 * @access public
	 *
	 * @param array<string, mixed> $input The input parameters from the AI request.
	 *
	 * @return array<string, mixed> The enhancement recommendations and status.
	 */
	public function execute_module_enhancement( array $input ): array {
		try {
			// Validate required input.
			if ( ( ! isset( $input['module_type'] ) || '' === $input['module_type'] ) || ( ! isset( $input['enhancement_type'] ) || '' === $input['enhancement_type'] ) ) {
				return array(
					'enhancements' => array(),
					'success'      => false,
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

			return array(
				'enhancements' => array(),
				'success'      => false,
			);
		}
	}

	/**
	 * Check Edit Posts Permission.
	 *
	 * Permission callback to verify that the user has the capability
	 * to edit posts before allowing AI ability execution.
	 *
	 * @since  3.5.0
	 * @access public
	 *
	 * @return bool True if user can edit posts, false otherwise.
	 */
	public function check_edit_posts_permission(): bool {
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
		return apply_filters( 'divi_squad_ai_generate_content', '', $input, $this );
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
		return apply_filters( 'divi_squad_ai_generate_design_suggestions', array(), $input, $this );
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
		return apply_filters( 'divi_squad_ai_generate_enhancements', array(), $input, $this );
	}

	/**
	 * Get Registered Abilities.
	 *
	 * Retrieves all registered AI abilities for the plugin.
	 *
	 * @since  3.5.0
	 * @access public
	 *
	 * @return array<string, array<string, mixed>> Array of registered abilities.
	 */
	public function get_registered_abilities(): array {
		try {
			$abilities = array(
				'squad-modules-for-divi/generate-content'  => array(
					'slug'        => 'squad-modules-for-divi/generate-content',
					'label'       => __( 'Generate Content', 'squad-modules-for-divi' ),
					'description' => __( 'Generate and enhance text content for Squad Modules using AI capabilities.', 'squad-modules-for-divi' ),
				),
				'squad-modules-for-divi/design-assistance' => array(
					'slug'        => 'squad-modules-for-divi/design-assistance',
					'label'       => __( 'Design Assistance', 'squad-modules-for-divi' ),
					'description' => __( 'Get AI-powered design suggestions and improvements for Squad Modules.', 'squad-modules-for-divi' ),
				),
				'squad-modules-for-divi/enhance-module'    => array(
					'slug'        => 'squad-modules-for-divi/enhance-module',
					'label'       => __( 'Module Enhancement', 'squad-modules-for-divi' ),
					'description' => __( 'Enhance and optimize Squad Modules with AI-powered features and suggestions.', 'squad-modules-for-divi' ),
				),
			);

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
	 * Checks whether a specific AI ability is registered for the plugin.
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
			$abilities = $this->get_registered_abilities();

			return isset( $abilities[ $ability_slug ] );
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
