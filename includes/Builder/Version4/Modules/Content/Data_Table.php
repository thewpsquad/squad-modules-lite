<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Data Table Module (Divi 4 shortcode).
 *
 * Parent module accepting Data Table Row children. Renders a semantic `<table>`
 * (the `.squad-data-table` shell) with a header row built from the parent column
 * config, plus responsive stack/scroll modes, highlight column/row, sticky
 * header, striped rows, optional client-side sort, and a ribbon. CSV-style paste
 * (one value per line) for fast authoring. No external lib.
 *
 * @since   4.3.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Content;

use DiviSquad\Builder\Shared\Modules\Content\Data_Table\Data_Table_Helper;
use DiviSquad\Builder\Version4\Abstracts\Module;
use function absint;
use function esc_attr;
use function esc_html__;
use function sprintf;
use function wp_enqueue_script;

/**
 * Data Table Module class.
 *
 * @since 4.3.0
 */
class Data_Table extends Module {

	/**
	 * Initiate Module.
	 *
	 * @since 4.3.0
	 * @return void
	 */
	public function init(): void {
		$this->name      = esc_html__( 'Data Table', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Data Tables', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'data-table.svg' );

		$this->slug             = 'disq_data_table';
		$this->child_slug       = 'disq_data_table_row';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.{$this->slug}";

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'columns_settings'    => esc_html__( 'Columns', 'squad-modules-for-divi' ),
					'behavior_settings'   => esc_html__( 'Behavior', 'squad-modules-for-divi' ),
					'ribbon_settings'     => esc_html__( 'Ribbon', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'header' => esc_html__( 'Header', 'squad-modules-for-divi' ),
					'cell'   => esc_html__( 'Cell', 'squad-modules-for-divi' ),
				),
			),
		);

		$this->advanced_fields = array(
			'background'     => divi_squad()->d4_module_helper->selectors_background( $this->main_css_element ),
			'borders'        => array(
				'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
				'header'  => array(
					'css'         => array(
						'main' => "{$this->main_css_element} .squad-data-table__th",
					),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'header',
				),
				'cell'    => array(
					'css'         => array(
						'main' => "{$this->main_css_element} .squad-data-table__td",
					),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'cell',
				),
			),
			'box_shadow'     => array(
				'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			),
			'margin_padding' => divi_squad()->d4_module_helper->selectors_margin_padding( $this->main_css_element ),
			'max_width'      => divi_squad()->d4_module_helper->selectors_max_width( $this->main_css_element ),
			'height'         => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			'fonts'          => false,
			'image_icon'     => false,
			'text'           => false,
			'button'         => false,
			'filters'        => false,
		);

		$this->custom_css_fields = array(
			'table'  => array(
				'label'    => esc_html__( 'Table', 'squad-modules-for-divi' ),
				'selector' => '.squad-data-table',
			),
			'header' => array(
				'label'    => esc_html__( 'Header', 'squad-modules-for-divi' ),
				'selector' => '.squad-data-table__th',
			),
			'cell'   => array(
				'label'    => esc_html__( 'Cell', 'squad-modules-for-divi' ),
				'selector' => '.squad-data-table__td',
			),
			'ribbon' => array(
				'label'    => esc_html__( 'Ribbon', 'squad-modules-for-divi' ),
				'selector' => '.squad-data-table__ribbon',
			),
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
			// Columns.
			'columns'           => array(
				'label'           => esc_html__( 'Column Headers', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'One header per line. These become the table header cells.', 'squad-modules-for-divi' ),
				'type'            => 'textarea',
				'option_category' => 'basic_option',
				'default'         => "Feature\nBasic\nPro",
				'tab_slug'        => 'general',
				'toggle_slug'     => 'columns_settings',
			),
			// Behavior.
			'responsive_mode'   => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Responsive Mode', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'How the table adapts on small screens.', 'squad-modules-for-divi' ),
					'options'     => array(
						'stack'  => esc_html__( 'Stack', 'squad-modules-for-divi' ),
						'scroll' => esc_html__( 'Horizontal Scroll', 'squad-modules-for-divi' ),
					),
					'default'     => 'stack',
					'tab_slug'    => 'general',
					'toggle_slug' => 'behavior_settings',
				)
			),
			'highlight_column'  => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Highlight Column', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Highlight a column (1-based). 0 disables highlighting.', 'squad-modules-for-divi' ),
					'range_settings' => array(
						'min'  => '0',
						'max'  => '10',
						'step' => '1',
					),
					'default'        => '0',
					'unitless'       => true,
					'mobile_options' => false,
					'hover'          => false,
					'tab_slug'       => 'general',
					'toggle_slug'    => 'behavior_settings',
				)
			),
			'sticky_header'     => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Sticky Header', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Keep the header row visible while scrolling.', 'squad-modules-for-divi' ),
					'default'     => 'off',
					'tab_slug'    => 'general',
					'toggle_slug' => 'behavior_settings',
				)
			),
			'striped_rows'      => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Striped Rows', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Alternate the background of even rows.', 'squad-modules-for-divi' ),
					'default'     => 'on',
					'tab_slug'    => 'general',
					'toggle_slug' => 'behavior_settings',
				)
			),
			'sortable'          => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Sortable Columns', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Let visitors click a header to sort the table.', 'squad-modules-for-divi' ),
					'default'     => 'off',
					'tab_slug'    => 'general',
					'toggle_slug' => 'behavior_settings',
				)
			),
			// Ribbon.
			'ribbon_text'       => array(
				'label'       => esc_html__( 'Ribbon Text', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Optional badge shown on the table (e.g. "Popular"). Leave empty to hide.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'tab_slug'    => 'general',
				'toggle_slug' => 'ribbon_settings',
			),
			// Advanced colors.
			'header_bg_color'   => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Header Background Color', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Background color of the header row.', 'squad-modules-for-divi' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'header',
				)
			),
			'header_text_color' => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Header Text Color', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Text color of the header row.', 'squad-modules-for-divi' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'header',
				)
			),
			'cell_text_color'   => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Cell Text Color', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Text color of the body cells.', 'squad-modules-for-divi' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'cell',
				)
			),
		);
	}

	/**
	 * Render the module HTML.
	 *
	 * @since 4.3.0
	 *
	 * @param array<string, mixed> $attrs       Module attributes.
	 * @param string               $content     Rendered child modules HTML.
	 * @param string               $render_slug Module render slug.
	 *
	 * @return string
	 */
	public function render( $attrs, $content, $render_slug ): string {
		wp_enqueue_script( 'squad-module-data-table' );

		$highlight_ui = absint( $this->prop( 'highlight_column', '0' ) );

		$config = array(
			'headers'         => Data_Table_Helper::split_lines( (string) $this->prop( 'columns', '' ) ),
			'responsive'      => (string) $this->prop( 'responsive_mode', 'stack' ),
			'highlightColumn' => $highlight_ui > 0 ? $highlight_ui - 1 : -1,
			'sticky'          => 'on' === (string) $this->prop( 'sticky_header', 'off' ) ? 'on' : 'off',
			'striped'         => 'on' === (string) $this->prop( 'striped_rows', 'on' ) ? 'on' : 'off',
			'sortable'        => 'on' === (string) $this->prop( 'sortable', 'off' ) ? 'on' : 'off',
			'ribbon'          => (string) $this->prop( 'ribbon_text', '' ),
		);

		$this->apply_color_styles( $render_slug );

		return Data_Table_Helper::build_table( $config, $content );
	}

	/**
	 * Apply header / cell color CSS via set_style.
	 *
	 * @since 4.3.0
	 *
	 * @param string $render_slug Module render slug.
	 *
	 * @return void
	 */
	protected function apply_color_styles( string $render_slug ): void {
		$header_bg = self::sanitize_css_background( (string) $this->prop( 'header_bg_color', '' ) );
		if ( '' !== $header_bg ) {
			self::set_style(
				$render_slug,
				array(
					'selector'    => '%%order_class%% .squad-data-table__th',
					'declaration' => sprintf( 'background-color: %s;', esc_attr( $header_bg ) ),
				)
			);
		}

		$header_text = self::sanitize_css_background( (string) $this->prop( 'header_text_color', '' ) );
		if ( '' !== $header_text ) {
			self::set_style(
				$render_slug,
				array(
					'selector'    => '%%order_class%% .squad-data-table__th',
					'declaration' => sprintf( 'color: %s;', esc_attr( $header_text ) ),
				)
			);
		}

		$cell_text = self::sanitize_css_background( (string) $this->prop( 'cell_text_color', '' ) );
		if ( '' !== $cell_text ) {
			self::set_style(
				$render_slug,
				array(
					'selector'    => '%%order_class%% .squad-data-table__td',
					'declaration' => sprintf( 'color: %s;', esc_attr( $cell_text ) ),
				)
			);
		}
	}
}
