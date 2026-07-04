<?php // phpcs:ignore WordPress.Files.FileName

/**
 * The Divi Library Shortcode extension class for Divi Squad.
 *
 * @since   1.2.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Extensions\Visual_Builder;

use DiviSquad\Extensions\Abstracts\Base_Extension;
use function add_action;
use function add_filter;
use function add_shortcode;
use function do_shortcode;
use function esc_attr;
use function esc_html__;
use function remove_filter;
use function shortcode_atts;
use function shortcode_exists;

/**
 * The Divi Library Shortcode class.
 *
 * @since   1.2.0
 * @package DiviSquad
 */
class Layout_Shortcode extends Base_Extension {

	/**
	 * Get the extension name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'Divi_Library_Shortcode';
	}

	/**
	 * Load the extension.
	 *
	 * @return void
	 */
	public function load(): void {
		// Create New Column in the Divi Library.
		add_filter( 'manage_et_pb_layout_posts_columns', array( $this, 'create_shortcode_column' ), 5 );
		add_action( 'manage_et_pb_layout_posts_custom_column', array( $this, 'shortcode_column_content' ), 5, 2 );

		// Register New Divi Shortcodes.
		if ( ! shortcode_exists( 'disq_divi_library_layout' ) ) {
			add_shortcode( 'disq_divi_library_layout', array( $this, 'shortcode_callback' ) );
		}

		// Register New Divi Shortcodes.
		if ( ! shortcode_exists( 'squad_divi_library_layout' ) ) {
			add_shortcode( 'squad_divi_library_layout', array( $this, 'shortcode_callback' ) );
		}

		// Add another shortcode if they do not exist.
		if ( ! shortcode_exists( 'divi_library_layout' ) ) {
			add_shortcode( 'divi_library_layout', array( $this, 'shortcode_callback' ) );
		}
	}

	/**
	 * Create New Admin Column
	 *
	 * @param array<string, string> $columns Exists columns array data.
	 *
	 * @return array<string, string>
	 */
	public function create_shortcode_column( array $columns ): array {
		$columns[ $this->get_column_slug() ] = $this->get_column_name();

		return $columns;
	}

	/**
	 * Get the column slug.
	 *
	 * @return string
	 */
	protected function get_column_slug(): string {
		return 'disq_shortcode_column';
	}

	/**
	 * Get the column name.
	 *
	 * @return string
	 */
	protected function get_column_name(): string {
		return esc_html__( 'Shortcode', 'squad-modules-for-divi' );
	}

	/**
	 * Display Shortcode
	 *
	 * @param string $column The current column name.
	 * @param int    $id     The current post id.
	 *
	 * @return void
	 */
	public function shortcode_column_content( string $column, int $id ): void {
		if ( $this->get_column_slug() === $column ) {
			echo wp_kses_post( $this->get_column_content( $id ) );
		}
	}

	/**
	 * Get the column content.
	 *
	 * @param int $id The current post id.
	 *
	 * @return string
	 */
	protected function get_column_content( int $id ): string {
		return sprintf( '<p>[squad_divi_library_layout id="%s"]</p>', esc_attr( (string) $id ) );
	}

	/**
	 * Create New Shortcode
	 *
	 * @param array<string, mixed>|string|mixed $atts The attributes of the current shortcode.
	 *
	 * @return string
	 */
	public function shortcode_callback( $atts ): string {
		$attributes    = shortcode_atts( array( 'id' => '*' ), $atts );
		$is_vb_preview = function_exists( 'is_et_pb_preview' ) && \is_et_pb_preview();

		if ( $is_vb_preview ) {
			add_filter( 'pre_do_shortcode_tag', array( $this, 'shortcode_set_ajax_module_index' ) );
		}

		$layout_content = do_shortcode( '[et_pb_section global_module="' . esc_attr( (string) $attributes['id'] ) . ' "][/et_pb_section]' );

		if ( $is_vb_preview ) {
			global $et_pb_predefined_module_index, $divi_squad_pbe_module_index_before;
			if ( isset( $divi_squad_pbe_module_index_before ) ) {
				$et_pb_predefined_module_index = $divi_squad_pbe_module_index_before; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
				unset( $divi_squad_pbe_module_index_before );
			} else {
				unset( $et_pb_predefined_module_index );
			}

			remove_filter( 'pre_do_shortcode_tag', array( $this, 'shortcode_set_ajax_module_index' ) );
		}

		return $layout_content;
	}

	/**
	 * Set a random high-module index when rendering in the visual builder to avoid conflicts with other modules on the same page
	 *
	 * @param mixed $value The content of current shortcode.
	 *
	 * @return mixed
	 */
	public function shortcode_set_ajax_module_index( $value ) {
		global $et_pb_predefined_module_index, $divi_squad_pbe_module_index, $divi_squad_pbe_module_index_before;
		if ( ! isset( $divi_squad_pbe_module_index ) ) {
			$divi_squad_pbe_module_index = wp_rand( 999, 999999 );
			if ( isset( $et_pb_predefined_module_index ) ) {
				$divi_squad_pbe_module_index_before = $et_pb_predefined_module_index;
			}
		}
		$et_pb_predefined_module_index = ++ $divi_squad_pbe_module_index;  // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

		return $value;
	}
}
