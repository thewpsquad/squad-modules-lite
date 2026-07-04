<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Data Table Row (child) Module (Divi 4 shortcode).
 *
 * Represents a single body row in the Data Table parent module. Each cell value
 * is one line of the Cells textarea, mapped positionally to the parent's column
 * headers. The header context (`data-label`, per-cell highlight) is applied
 * client-side by `data-table.ts`, so the child renders a header-independent
 * `<tr>`.
 *
 * @since   4.3.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Content;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use DiviSquad\Builder\Shared\Modules\Content\Data_Table\Data_Table_Helper;
use DiviSquad\Builder\Version4\Abstracts\Module\Child_Module;
use function esc_html__;

/**
 * Data Table Row (child) Module class.
 *
 * @since 4.3.0
 */
class Data_Table_Row extends Child_Module {

	/**
	 * Initiate Module.
	 *
	 * @since 4.3.0
	 * @return void
	 */
	public function init(): void {
		$this->name   = esc_html__( 'Data Table Row', 'squad-modules-for-divi' );
		$this->plural = esc_html__( 'Data Table Rows', 'squad-modules-for-divi' );

		$this->slug             = 'disq_data_table_row';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.{$this->slug}";

		$this->child_title_var          = 'cells';
		$this->child_title_fallback_var = 'admin_label';

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		$this->settings_modal_toggles = array(
			'general' => array(
				'toggles' => array(
					'row_settings' => esc_html__( 'Row', 'squad-modules-for-divi' ),
				),
			),
		);

		$this->advanced_fields = array(
			'background'     => false,
			'borders'        => array(
				'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			),
			'box_shadow'     => array(
				'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			),
			'margin_padding' => divi_squad()->d4_module_helper->selectors_margin_padding( $this->main_css_element ),
			'fonts'          => false,
			'image_icon'     => false,
			'text'           => false,
			'button'         => false,
			'filters'        => false,
		);
	}

	/**
	 * Define module fields.
	 *
	 * @since 4.3.0
	 * @return array<string, array<string, mixed>>
	 */
	public function get_fields(): array {
		return array(
			'cells'         => array(
				'label'           => esc_html__( 'Cells', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'One cell value per line, in the same order as the parent column headers.', 'squad-modules-for-divi' ),
				'type'            => 'textarea',
				'option_category' => 'basic_option',
				'default'         => '',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'row_settings',
			),
			'highlight_row' => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Highlight Row', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Visually emphasise this row.', 'squad-modules-for-divi' ),
					'default'     => 'off',
					'tab_slug'    => 'general',
					'toggle_slug' => 'row_settings',
				)
			),
		);
	}

	/**
	 * Render the data row HTML.
	 *
	 * @since 4.3.0
	 *
	 * @param array<array-key, mixed> $attrs       Module attributes.
	 * @param string                  $content     Inner content (unused).
	 * @param string                  $render_slug Module render slug.
	 *
	 * @return string
	 */
	public function render( $attrs, $content, $render_slug ): string {
		$row = array(
			'cells'     => Data_Table_Helper::split_lines( (string) $this->prop( 'cells', '' ) ),
			'highlight' => 'on' === (string) $this->prop( 'highlight_row', 'off' ) ? 'on' : 'off',
		);

		return Data_Table_Helper::build_row( $row );
	}
}
