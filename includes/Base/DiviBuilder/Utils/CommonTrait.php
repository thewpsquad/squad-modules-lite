<?php // phpcs:ignore WordPress.Files.FileName
/**
 * Utils Common.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   1.0.0
 */

namespace DiviSquad\Base\DiviBuilder\Utils;

use function esc_html;
use function esc_html__;
use function wp_kses_post;
use function wp_strip_all_tags;

/**
 * Common trait.
 *
 * @package DiviSquad
 * @since   1.0.0
 */
trait CommonTrait {

	/**
	 * Decode json data from properties in module.
	 *
	 * @param string $html_content json data raw content from module.
	 *
	 * @return array<string, mixed>
	 * @throws \JsonException When json error found.
	 */
	public static function decode_json_data( string $html_content ): array {
		// Collect data as unmanaged json string.
		$data = stripslashes( html_entity_decode( $html_content ) );

		// Return json data as array for better management.
		return json_decode( $data, true, 512, JSON_THROW_ON_ERROR );
	}

	/**
	 * Collect actual props from child module with escaping raw html.
	 *
	 * @param string $content The raw content form child element.
	 *
	 * @return string
	 */
	public static function collect_raw_props( string $content ): string {
		return wp_strip_all_tags( $content );
	}

	/**
	 * Collect actual props from child module with escaping raw html.
	 *
	 * @param string $content The raw content form child element.
	 *
	 * @return array<string, mixed>
	 * @throws \RuntimeException When json error found.
	 */
	public static function collect_child_json_props( string $content ): array {
		$raw_props   = static::json_format_raw_props( $content );
		$clean_props = str_replace( array( '},||', '},]' ), array( '},', '}]' ), $raw_props );
		$child_props = json_decode( $clean_props, true );

		if ( JSON_ERROR_NONE !== json_last_error() ) {
			throw new \RuntimeException(
				sprintf(
					/* translators: 1: Error message. */
					esc_html__( '%1$s found when decoding the content: %2$s', 'squad-modules-for-divi' ),
					esc_html( json_last_error_msg() ),
					wp_kses_post( $content )
				)
			);
		}

		return $child_props;
	}

	/**
	 * Collect actual props from child module with escaping raw html.
	 *
	 * @param string $content The raw content form child element.
	 *
	 * @return string
	 */
	public static function json_format_raw_props( string $content ): string {
		return sprintf( '[%s]', $content );
	}

	/**
	 * Collect all modules from Divi Builder.
	 *
	 * @param array<string, array{label: string, title: string}> $modules_array  All modules array.
	 * @param array<string>                                      $allowed_prefix The allowed prefix list.
	 *
	 * @return array<string, string>
	 */
	public static function get_all_modules( array $modules_array, array $allowed_prefix = array() ): array {
		// Initiate default data.
		$default_allowed_prefix = array( 'disq' );
		$clean_modules          = array(
			'none'   => esc_html__( 'Select Module', 'squad-modules-for-divi' ),
			'custom' => esc_html__( 'Custom', 'squad-modules-for-divi' ),
		);

		// Merge new data with default prefix.
		$all_prefix = array_merge( $default_allowed_prefix, $allowed_prefix );

		foreach ( $modules_array as $module ) {
			$has_underscore = strpos( $module['label'], '_' );
			if ( false !== $has_underscore ) {
				$module_explode = explode( '_', $module['label'] );

				if ( in_array( $module_explode[0], $all_prefix, true ) ) {
					$clean_modules[ $module['label'] ] = $module['title'];
				}
			}
		}

		return $clean_modules;
	}

	/**
	 * Clean order class name from the class list for current module.
	 *
	 * @param array<string> $classnames All CSS classes name the module has.
	 * @param string        $slug Utils slug.
	 *
	 * @return array<string>
	 */
	public static function clean_order_class( array $classnames, string $slug ): array {
		return array_filter(
			$classnames,
			static function ( $classname ) use ( $slug ) {
				return 0 !== strpos( $classname, "{$slug}_" );
			}
		);
	}

	/**
	 * Get margin and padding selectors for main and hover
	 *
	 * @param string $main_css_element Main css selector of element.
	 *
	 * @return array{use_padding: bool, use_margin: bool, css: array{important: string, margin: string, padding: string}}
	 */
	public static function selectors_margin_padding( string $main_css_element ): array {
		return array(
			'use_padding' => true,
			'use_margin'  => true,
			'css'         => array(
				'margin'    => $main_css_element,
				'padding'   => $main_css_element,
				'important' => 'all',
			),
		);
	}

	/**
	 * Get max_width selectors for main and hover
	 *
	 * @param string $main_css_element Main css selector of an element.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function selectors_max_width( string $main_css_element ): array {
		return array_merge(
			self::selectors_default( $main_css_element ),
			array(
				'css' => array(
					'module_alignment' => "$main_css_element.et_pb_module",
				),
			)
		);
	}

	/**
	 * Get default selectors for main and hover
	 *
	 * @param string $main_css_element Main css selector of element.
	 *
	 * @return array{css: array{main: string, hover: string}}
	 */
	public static function selectors_default( string $main_css_element ): array {
		return array(
			'css' => array(
				'main'  => $main_css_element,
				'hover' => "$main_css_element:hover",
			),
		);
	}

	/**
	 * Get background selectors for main and hover
	 *
	 * @param string $main_css_element Main css selector of an element.
	 *
	 * @return array{settings: array{color: string}, css: array{main: string, hover: string}}
	 */
	public static function selectors_background( string $main_css_element ): array {
		return array_merge(
			self::selectors_default( $main_css_element ),
			array(
				'settings' => array(
					'color' => 'alpha',
				),
			)
		);
	}

	/**
	 * Convert field name into css property name.
	 *
	 * @param string $field Field name.
	 *
	 * @return string|string[]
	 */
	public static function field_to_css_prop( string $field ) {
		return str_replace( '_', '-', $field );
	}
}
