<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Builder Utils CommonTrait
 *
 * @since   1.5.0
 * @author  The WP Squad <support@squadmodules.com>
 * @package DiviSquad
 */

namespace DiviSquad\Builder\Version4\Supports\Module_Helpers;

/**
 * Common Methods Trait
 *
 * @since   1.5.0
 * @since   3.3.3 Migrate to the new structure.
 * @package DiviSquad
 */
trait Common_Trait {

	/**
	 * Decode json data from properties in module.
	 *
	 * @param string $html_content json data raw content from module.
	 *
	 * @return array<string, mixed>
	 * @throws \JsonException When json error found.
	 */
	public function decode_json_data( string $html_content ): array {
		/**
		 * Filter the HTML content before JSON decoding.
		 *
		 * @since 3.3.3
		 *
		 * @param string $html_content The HTML content to be decoded.
		 */
		$html_content = (string) apply_filters( 'divi_squad_utils_before_decode_json', $html_content );

		// Collect data as unmanaged json string.
		$data = stripslashes( html_entity_decode( $html_content ) );

		/**
		 * Filter the processed data before JSON decoding.
		 *
		 * @since 3.3.3
		 *
		 * @param string $data         The data after stripping slashes and decoding HTML entities.
		 * @param string $html_content The original HTML content.
		 */
		$data = apply_filters( 'divi_squad_utils_json_data_before_decode', $data, $html_content );

		// Return json data as array for better management.
		$decoded_data = json_decode( $data, true, 512, JSON_THROW_ON_ERROR );

		/**
		 * Filter the decoded JSON data.
		 *
		 * @since 3.3.3
		 *
		 * @param array  $decoded_data The decoded JSON data.
		 * @param string $data         The data before decoding.
		 * @param string $html_content The original HTML content.
		 */
		return apply_filters( 'divi_squad_utils_decoded_json_data', $decoded_data, $data, $html_content );
	}

	/**
	 * Collect actual props from child module with escaping raw html.
	 *
	 * @param string $content The raw content form child element.
	 *
	 * @return string
	 */
	public function collect_raw_props( string $content ): string {
		/**
		 * Filter the content before collecting raw props.
		 *
		 * @since 3.3.3
		 *
		 * @param string $content The raw content from child element.
		 */
		$content = (string) apply_filters( 'divi_squad_utils_before_collect_raw_props', $content );

		$stripped_content = wp_strip_all_tags( $content );

		/**
		 * Filter the stripped content after collecting raw props.
		 *
		 * @since 3.3.3
		 *
		 * @param string $stripped_content The content after stripping all tags.
		 * @param string $content          The original raw content.
		 */
		return apply_filters( 'divi_squad_utils_after_collect_raw_props', $stripped_content, $content );
	}

	/**
	 * Collect actual props from child module with escaping raw html.
	 *
	 * @param string $content The raw content form child element.
	 *
	 * @return array<string, mixed>
	 * @throws \RuntimeException|\JsonException When json error found.
	 */
	public function collect_child_json_props( string $content ): array {
		/**
		 * Filter the content before collecting child JSON props.
		 *
		 * @since 3.3.3
		 *
		 * @param string $content The raw content from child element.
		 */
		$content = (string) apply_filters( 'divi_squad_utils_before_collect_child_json_props', $content );

		/**
		 * Action triggered before collecting child JSON props.
		 *
		 * @since 3.3.3
		 *
		 * @param string $content The raw content from child element.
		 */
		do_action( 'divi_squad_utils_before_collect_child_json_props_action', $content );

		$raw_props   = $this->json_format_raw_props( $content );
		$clean_props = str_replace( array( '},||', '},]' ), array( '},', '}]' ), $raw_props );

		/**
		 * Filter the cleaned props before JSON decoding.
		 *
		 * @since 3.3.3
		 *
		 * @param string $clean_props The cleaned props string ready for JSON decoding.
		 * @param string $raw_props   The raw props after initial formatting.
		 * @param string $content     The original content.
		 */
		$clean_props = apply_filters( 'divi_squad_utils_child_json_props_before_decode', $clean_props, $raw_props, $content );

		try {
			$child_props = json_decode( $clean_props, true, 512, JSON_THROW_ON_ERROR );

			/**
			 * Filter the decoded child JSON props.
			 *
			 * @since 3.3.3
			 *
			 * @param array  $child_props The decoded child JSON props.
			 * @param string $clean_props The cleaned props string that was decoded.
			 * @param string $content     The original content.
			 */
			$child_props = apply_filters( 'divi_squad_utils_child_json_props', $child_props, $clean_props, $content );

			/**
			 * Action triggered after successfully collecting child JSON props.
			 *
			 * @since 3.3.3
			 *
			 * @param array  $child_props The decoded child JSON props.
			 * @param string $content     The original content.
			 */
			do_action( 'divi_squad_utils_after_collect_child_json_props', $child_props, $content );

			return $child_props;
		} catch ( \Throwable $e ) {
			/**
			 * Action triggered when an error occurs during child JSON props collection.
			 *
			 * @since 3.3.3
			 *
			 * @param \Exception $e           The exception that occurred.
			 * @param string     $clean_props The cleaned props string that caused the error.
			 * @param string     $content     The original content.
			 */
			do_action( 'divi_squad_utils_child_json_props_error', $e, $clean_props, $content );

			// Log the error.
			divi_squad()->log_error(
				$e,
				sprintf(
				/* translators: 1: Error message. */
					esc_html__( 'Error decoding JSON data: %1$s', 'squad-modules-for-divi' ),
					esc_html( $e->getMessage() )
				)
			);

			return array();
		}
	}

	/**
	 * Collect actual props from child module with escaping raw html.
	 *
	 * @param string $content The raw content form child element.
	 *
	 * @return string
	 */
	public function json_format_raw_props( string $content ): string {
		/**
		 * Filter the content before formatting raw props.
		 *
		 * @since 3.3.3
		 *
		 * @param string $content The raw content from child element.
		 */
		$content = (string) apply_filters( 'divi_squad_utils_before_json_format_raw_props', $content );

		$formatted = sprintf( '[%s]', $content );

		/**
		 * Filter the formatted raw props.
		 *
		 * @since 3.3.3
		 *
		 * @param string $formatted The formatted raw props.
		 * @param string $content   The original content.
		 */
		return apply_filters( 'divi_squad_utils_after_json_format_raw_props', $formatted, $content );
	}

	/**
	 * Collect all modules from Divi Builder.
	 *
	 * @param array<string, array{label: string, title: string}> $modules_array  All modules array.
	 * @param array<string>                                      $allowed_prefix The allowed prefix list.
	 *
	 * @return array<string, string>
	 */
	public function get_all_modules( array $modules_array, array $allowed_prefix = array() ): array {
		/**
		 * Filter the modules array before processing.
		 *
		 * @since 3.3.3
		 *
		 * @param array $modules_array  All modules array.
		 * @param array $allowed_prefix The allowed prefix list.
		 */
		$modules_array = (array) apply_filters( 'divi_squad_utils_before_get_all_modules', $modules_array, $allowed_prefix );

		// Initiate default data.
		$default_allowed_prefix = array( 'disq' );
		$clean_modules          = array(
			'none'   => esc_html__( 'Select Module', 'squad-modules-for-divi' ),
			'custom' => esc_html__( 'Custom', 'squad-modules-for-divi' ),
		);

		/**
		 * Filter the default allowed prefix.
		 *
		 * @since 3.3.3
		 *
		 * @param array $default_allowed_prefix The default allowed prefix.
		 */
		$default_allowed_prefix = apply_filters( 'divi_squad_utils_default_allowed_prefix', $default_allowed_prefix );

		/**
		 * Filter the default clean modules.
		 *
		 * @since 3.3.3
		 *
		 * @param array $clean_modules The default clean modules.
		 */
		$clean_modules = apply_filters( 'divi_squad_utils_default_clean_modules', $clean_modules );

		// Merge new data with default prefix.
		$all_prefix = array_merge( $default_allowed_prefix, $allowed_prefix );

		/**
		 * Filter all prefixes after merging with defaults.
		 *
		 * @since 3.3.3
		 *
		 * @param array $all_prefix             All prefixes after merging.
		 * @param array $allowed_prefix         The allowed prefix list passed to the function.
		 * @param array $default_allowed_prefix The default allowed prefix.
		 */
		$all_prefix = apply_filters( 'divi_squad_utils_all_prefix', $all_prefix, $allowed_prefix, $default_allowed_prefix );

		foreach ( $modules_array as $module ) {
			$has_underscore = strpos( $module['label'], '_' );
			if ( false !== $has_underscore ) {
				$module_explode = explode( '_', $module['label'] );

				/**
				 * Filter the module explode result.
				 *
				 * @since 3.3.3
				 *
				 * @param array  $module_explode The module label after exploding by underscore.
				 * @param array  $module         The current module being processed.
				 * @param string $has_underscore The position of the underscore in the label.
				 */
				$module_explode = apply_filters( 'divi_squad_utils_module_explode', $module_explode, $module, $has_underscore );

				if ( in_array( $module_explode[0], $all_prefix, true ) ) {
					$clean_modules[ $module['label'] ] = $module['title'];
				}
			}
		}

		/**
		 * Filter the clean modules array after processing.
		 *
		 * @since 3.3.3
		 *
		 * @param array $clean_modules  The processed clean modules array.
		 * @param array $modules_array  The original modules array.
		 * @param array $allowed_prefix The allowed prefix list passed to the function.
		 */
		return apply_filters( 'divi_squad_utils_get_all_modules', $clean_modules, $modules_array, $allowed_prefix );
	}

	/**
	 * Clean order class name from the class list for current module.
	 *
	 * @param array<string> $classnames All CSS classes name the module has.
	 * @param string        $slug       Utils slug.
	 *
	 * @return array<string>
	 */
	public function clean_order_class( array $classnames, string $slug ): array {
		/**
		 * Filter the classnames before cleaning order class.
		 *
		 * @since 3.3.3
		 *
		 * @param array  $classnames All CSS classes name the module has.
		 * @param string $slug       Utils slug.
		 */
		$classnames = (array) apply_filters( 'divi_squad_utils_before_clean_order_class', $classnames, $slug );

		/**
		 * Filter the slug used for cleaning order class.
		 *
		 * @since 3.3.3
		 *
		 * @param string $slug       Utils slug.
		 * @param array  $classnames All CSS classes name the module has.
		 */
		$slug = (string) apply_filters( 'divi_squad_utils_clean_order_class_slug', $slug, $classnames );

		$filtered_classnames = array_filter(
			$classnames,
			static function ( $classname ) use ( $slug ) {
				return 0 !== strpos( $classname, "{$slug}_" );
			}
		);

		/**
		 * Filter the classnames after cleaning order class.
		 *
		 * @since 3.3.3
		 *
		 * @param array  $filtered_classnames The filtered classnames.
		 * @param array  $classnames          The original classnames.
		 * @param string $slug                Utils slug.
		 */
		return apply_filters( 'divi_squad_utils_after_clean_order_class', $filtered_classnames, $classnames, $slug );
	}

	/**
	 * Get margin and padding selectors for main and hover
	 *
	 * @param string $main_css_element Main css selector of element.
	 *
	 * @return array{use_padding: bool, use_margin: bool, css: array{important: string, margin: string, padding: string}}
	 */
	public function selectors_margin_padding( string $main_css_element ): array {
		/**
		 * Filter the main CSS element before creating margin padding selectors.
		 *
		 * @since 3.3.3
		 *
		 * @param string $main_css_element Main css selector of element.
		 */
		$main_css_element = (string) apply_filters( 'divi_squad_utils_margin_padding_main_css_element', $main_css_element );

		$selectors = array(
			'use_padding' => true,
			'use_margin'  => true,
			'css'         => array(
				'margin'    => $main_css_element,
				'padding'   => $main_css_element,
				'important' => 'all',
			),
		);

		/**
		 * Filter the margin padding selectors.
		 *
		 * @since 3.3.3
		 *
		 * @param array  $selectors        The margin padding selectors.
		 * @param string $main_css_element Main css selector of element.
		 */
		return apply_filters( 'divi_squad_utils_margin_padding_selectors', $selectors, $main_css_element );
	}

	/**
	 * Get max_width selectors for main and hover
	 *
	 * @param string $main_css_element Main css selector of an element.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function selectors_max_width( string $main_css_element ): array {
		/**
		 * Filter the main CSS element before creating max width selectors.
		 *
		 * @since 3.3.3
		 *
		 * @param string $main_css_element Main css selector of element.
		 */
		$main_css_element = (string) apply_filters( 'divi_squad_utils_max_width_main_css_element', $main_css_element );

		$default_selectors = $this->selectors_default( $main_css_element );

		$max_width_selectors = array_merge(
			$default_selectors,
			array(
				'css' => array(
					'module_alignment' => "$main_css_element.et_pb_module",
				),
			)
		);

		/**
		 * Filter the max width selectors.
		 *
		 * @since 3.3.3
		 *
		 * @param array  $max_width_selectors The max width selectors.
		 * @param string $main_css_element    Main css selector of element.
		 * @param array  $default_selectors   The default selectors.
		 */
		return apply_filters( 'divi_squad_utils_max_width_selectors', $max_width_selectors, $main_css_element, $default_selectors );
	}

	/**
	 * Get default selectors for main and hover
	 *
	 * @param string $main_css_element Main css selector of element.
	 *
	 * @return array{css: array{main: string, hover: string}}
	 */
	public function selectors_default( string $main_css_element ): array {
		/**
		 * Filter the main CSS element before creating default selectors.
		 *
		 * @since 3.3.3
		 *
		 * @param string $main_css_element Main css selector of element.
		 */
		$main_css_element = (string) apply_filters( 'divi_squad_utils_default_main_css_element', $main_css_element );

		$selectors = array(
			'css' => array(
				'main'  => $main_css_element,
				'hover' => "$main_css_element:hover",
			),
		);

		/**
		 * Filter the default selectors.
		 *
		 * @since SQUAD_MODULES_CORE_SINCE
		 *
		 * @param array  $selectors        The default selectors.
		 * @param string $main_css_element Main css selector of element.
		 */
		return apply_filters( 'divi_squad_utils_default_selectors', $selectors, $main_css_element );
	}

	/**
	 * Get background selectors for main and hover
	 *
	 * @param string $main_css_element Main css selector of an element.
	 *
	 * @return array{settings: array{color: string}, css: array{main: string, hover: string}}
	 */
	public function selectors_background( string $main_css_element ): array {
		/**
		 * Filter the main CSS element before creating background selectors.
		 *
		 * @since SQUAD_MODULES_CORE_SINCE
		 *
		 * @param string $main_css_element Main css selector of element.
		 */
		$main_css_element = (string) apply_filters( 'divi_squad_utils_background_main_css_element', $main_css_element );

		$default_selectors = $this->selectors_default( $main_css_element );

		$background_selectors = array_merge(
			$default_selectors,
			array(
				'settings' => array(
					'color' => 'alpha',
				),
			)
		);

		/**
		 * Filter the background selectors.
		 *
		 * @since SQUAD_MODULES_CORE_SINCE
		 *
		 * @param array  $background_selectors The background selectors.
		 * @param string $main_css_element     Main css selector of element.
		 * @param array  $default_selectors    The default selectors.
		 */
		return apply_filters( 'divi_squad_utils_background_selectors', $background_selectors, $main_css_element, $default_selectors );
	}

	/**
	 * Convert field name into css property name.
	 *
	 * @param string $field Field name.
	 *
	 * @return string|string[]
	 */
	public function field_to_css_prop( string $field ) {
		/**
		 * Filter the field name before converting to CSS property.
		 *
		 * @since SQUAD_MODULES_CORE_SINCE
		 *
		 * @param string $field Field name.
		 */
		$field = (string) apply_filters( 'divi_squad_utils_before_field_to_css_prop', $field );

		$css_prop = str_replace( '_', '-', $field );

		/**
		 * Filter the CSS property after conversion.
		 *
		 * @since SQUAD_MODULES_CORE_SINCE
		 *
		 * @param string|string[] $css_prop The CSS property.
		 * @param string          $field    The original field name.
		 */
		return apply_filters( 'divi_squad_utils_after_field_to_css_prop', $css_prop, $field );
	}
}
