<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Table of Contents Module (Divi 4 shortcode).
 *
 * Renders a thin config shell (`.squad-toc`) carrying data-* attributes. The
 * actual heading list is built client-side by `table-of-contents.ts`; PHP emits
 * no list items.
 *
 * @since   4.0.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Creative;

use DiviSquad\Builder\Shared\Modules\Creative\Table_Of_Contents\Toc_Helper;
use DiviSquad\Builder\Version4\Abstracts\Module;
use function absint;
use function esc_html__;
use function implode;
use function in_array;
use function max;
use function min;
use function sanitize_text_field;
use function wp_enqueue_script;

/**
 * Table of Contents Module class.
 *
 * @since 4.0.0
 */
class Table_Of_Contents extends Module {

	/**
	 * Initiate Module.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function init(): void {
		$this->name      = esc_html__( 'Table of Contents', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Tables of Contents', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'table-of-contents.svg' );

		$this->slug             = 'disq_table_of_contents';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.$this->slug";

		$this->child_title_var          = 'title';
		$this->child_title_fallback_var = 'admin_label';

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'title'    => esc_html__( 'Title', 'squad-modules-for-divi' ),
					'headings' => esc_html__( 'Headings', 'squad-modules-for-divi' ),
					'behavior' => esc_html__( 'Behavior', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'title_text' => esc_html__( 'Title Text', 'squad-modules-for-divi' ),
					'link_text'  => esc_html__( 'Links', 'squad-modules-for-divi' ),
					'colors'     => esc_html__( 'Colors', 'squad-modules-for-divi' ),
					'list'       => esc_html__( 'List', 'squad-modules-for-divi' ),
				),
			),
		);

		$this->advanced_fields = array(
			'fonts'          => array(
				'title_text' => divi_squad()->d4_module_helper->add_font_field(
					'',
					array(
						'font_size'   => array( 'default' => '20px' ),
						'css'         => array(
							'main'  => "$this->main_css_element .squad-toc__title",
							'hover' => "$this->main_css_element .squad-toc__title:hover",
						),
						'tab_slug'    => 'advanced',
						'toggle_slug' => 'title_text',
					)
				),
				'link_text'  => divi_squad()->d4_module_helper->add_font_field(
					'',
					array(
						'font_size'   => array( 'default' => '15px' ),
						'css'         => array(
							'main'  => "$this->main_css_element .squad-toc__link",
							'hover' => "$this->main_css_element .squad-toc__link:hover",
						),
						'tab_slug'    => 'advanced',
						'toggle_slug' => 'link_text',
					)
				),
			),
			'background'     => divi_squad()->d4_module_helper->selectors_background( $this->main_css_element ),
			'borders'        => array( 'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ) ),
			'box_shadow'     => array( 'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ) ),
			'margin_padding' => divi_squad()->d4_module_helper->selectors_margin_padding( $this->main_css_element ),
			'max_width'      => divi_squad()->d4_module_helper->selectors_max_width( $this->main_css_element ),
			'height'         => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			'image_icon'     => false,
			'text'           => false,
			'button'         => false,
			'filters'        => false,
			'link_options'   => false,
		);
	}

	/**
	 * Get fields for the module.
	 *
	 * @since 4.0.0
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_fields(): array {
		$heading_field = function ( string $label, string $default ) {
			return divi_squad()->d4_module_helper->add_yes_no_field(
				$label,
				array(
					'description' => esc_html__( 'Include this heading level in the table of contents.', 'squad-modules-for-divi' ),
					'default'     => $default,
					'tab_slug'    => 'general',
					'toggle_slug' => 'headings',
				)
			);
		};

		return array(
			// Title.
			'show_title'        => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Show Title', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Show a title above the table of contents.', 'squad-modules-for-divi' ),
					'default_on_front' => 'on',
					'default'          => 'on',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'title',
				)
			),
			'title'             => array(
				'label'       => esc_html__( 'Title', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'The heading shown above the list.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => esc_html__( 'Table of Contents', 'squad-modules-for-divi' ),
				'show_if'     => array( 'show_title' => 'on' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'title',
			),
			'title_tag'         => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Title Tag', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'HTML tag used for the title.', 'squad-modules-for-divi' ),
					'options'     => array(
						'h1' => 'H1',
						'h2' => 'H2',
						'h3' => 'H3',
						'h4' => 'H4',
						'h5' => 'H5',
						'h6' => 'H6',
					),
					'default'     => 'h3',
					'show_if'     => array( 'show_title' => 'on' ),
					'tab_slug'    => 'general',
					'toggle_slug' => 'title',
				)
			),

			// Headings.
			'include_h1'        => $heading_field( esc_html__( 'Include H1', 'squad-modules-for-divi' ), 'off' ),
			'include_h2'        => $heading_field( esc_html__( 'Include H2', 'squad-modules-for-divi' ), 'on' ),
			'include_h3'        => $heading_field( esc_html__( 'Include H3', 'squad-modules-for-divi' ), 'on' ),
			'include_h4'        => $heading_field( esc_html__( 'Include H4', 'squad-modules-for-divi' ), 'on' ),
			'include_h5'        => $heading_field( esc_html__( 'Include H5', 'squad-modules-for-divi' ), 'off' ),
			'include_h6'        => $heading_field( esc_html__( 'Include H6', 'squad-modules-for-divi' ), 'off' ),
			'content_selector'  => array(
				'label'       => esc_html__( 'Content Selector', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'A CSS selector for the element to scan. Leave empty to auto-detect the post content.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'tab_slug'    => 'general',
				'toggle_slug' => 'headings',
			),
			'min_headings'      => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Minimum Headings', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Hide the module unless at least this many headings are found.', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '1', 'max' => '20', 'step' => '1' ),
					'default'        => '1',
					'unitless'       => true,
					'tab_slug'       => 'general',
					'toggle_slug'    => 'headings',
				)
			),

			// Behavior.
			'list_style'        => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'List Style', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Marker style for the list.', 'squad-modules-for-divi' ),
					'options'     => array(
						'ordered'   => esc_html__( 'Numbered', 'squad-modules-for-divi' ),
						'unordered' => esc_html__( 'Bulleted', 'squad-modules-for-divi' ),
						'none'      => esc_html__( 'None', 'squad-modules-for-divi' ),
					),
					'default'     => 'unordered',
					'tab_slug'    => 'general',
					'toggle_slug' => 'behavior',
				)
			),
			'smooth_scroll'     => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Smooth Scroll', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Animate scrolling to the target heading.', 'squad-modules-for-divi' ),
					'default_on_front' => 'on',
					'default'          => 'on',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'behavior',
				)
			),
			'scroll_offset'     => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Scroll Offset', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Pixels to offset for a fixed header.', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '0', 'max' => '300', 'step' => '1' ),
					'default'        => '0',
					'unitless'       => true,
					'tab_slug'       => 'general',
					'toggle_slug'    => 'behavior',
				)
			),
			'scroll_spy'        => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Scroll Spy', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Highlight the active heading while scrolling.', 'squad-modules-for-divi' ),
					'default_on_front' => 'on',
					'default'          => 'on',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'behavior',
				)
			),
			'collapsible'       => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Collapsible', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Let visitors collapse/expand the list via the title.', 'squad-modules-for-divi' ),
					'default'     => 'off',
					'tab_slug'    => 'general',
					'toggle_slug' => 'behavior',
				)
			),
			'default_collapsed' => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Collapsed by Default', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Start collapsed on page load.', 'squad-modules-for-divi' ),
					'default'     => 'off',
					'show_if'     => array( 'collapsible' => 'on' ),
					'tab_slug'    => 'general',
					'toggle_slug' => 'behavior',
				)
			),
			'sticky'            => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Sticky', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Keep the module pinned while scrolling.', 'squad-modules-for-divi' ),
					'default'     => 'off',
					'tab_slug'    => 'general',
					'toggle_slug' => 'behavior',
				)
			),
			'sticky_offset'     => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Sticky Offset', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Top offset (px) when sticky.', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '0', 'max' => '300', 'step' => '1' ),
					'default'        => '0',
					'unitless'       => true,
					'show_if'        => array( 'sticky' => 'on' ),
					'tab_slug'       => 'general',
					'toggle_slug'    => 'behavior',
				)
			),

			// Design — Colors.
			'active_link_color' => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Active Link Color', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Color of the active (scroll-spy) link.', 'squad-modules-for-divi' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'colors',
				)
			),
			'marker_color'      => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Marker Color', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Color of the bullets/numbers.', 'squad-modules-for-divi' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'colors',
				)
			),

			// Design — List.
			'nested_indent'     => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Nested Indent', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Left indent per nested level.', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '0', 'max' => '80', 'step' => '1' ),
					'default'        => '16px',
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'list',
				)
			),
			'link_spacing'      => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Link Spacing', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Vertical gap between links.', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '0', 'max' => '40', 'step' => '1' ),
					'default'        => '8px',
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'list',
				)
			),
		);
	}

	/**
	 * Get CSS transition fields.
	 *
	 * @since 4.0.0
	 *
	 * @return array<string, array<string, string>>
	 */
	public function get_transition_fields_css_props(): array {
		$fields = parent::get_transition_fields_css_props();

		$fields['active_link_color'] = array( 'color' => "$this->main_css_element .squad-toc__link.is-active" );
		$fields['marker_color']      = array( 'color' => "$this->main_css_element .squad-toc__item::marker" );
		divi_squad()->d4_module_helper->fix_fonts_transition( $fields, 'title_text', "$this->main_css_element .squad-toc__title" );
		divi_squad()->d4_module_helper->fix_fonts_transition( $fields, 'link_text', "$this->main_css_element .squad-toc__link" );

		return $fields;
	}

	/**
	 * Render module output.
	 *
	 * @since 4.0.0
	 *
	 * @param array<array-key, mixed> $attrs       List of attributes.
	 * @param string                  $content     Content being processed.
	 * @param string                  $render_slug Slug of module being rendered.
	 *
	 * @return string
	 */
	public function render( $attrs, $content, $render_slug ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClassAfterLastUsed
		wp_enqueue_script( 'squad-module-table-of-contents' );

		$show_title  = 'off' === $this->prop( 'show_title', 'on' ) ? 'off' : 'on';
		$collapsible = 'on' === $this->prop( 'collapsible', 'off' ) && 'on' === $show_title;

		$props = array();
		for ( $n = 1; $n <= 6; $n ++ ) {
			$props[ 'include_h' . $n ] = $this->prop( 'include_h' . $n, '' );
		}
		$levels = Toc_Helper::selected_levels( $props );

		$list = $this->prop( 'list_style', 'unordered' );
		$list = in_array( $list, array( 'ordered', 'unordered', 'none' ), true ) ? $list : 'unordered';

		$config = array(
			'selector'      => sanitize_text_field( (string) $this->prop( 'content_selector', '' ) ),
			'levels'        => implode( ',', $levels ),
			'list'          => $list,
			'smooth'        => 'off' === $this->prop( 'smooth_scroll', 'on' ) ? '0' : '1',
			'offset'        => (string) absint( $this->prop( 'scroll_offset', '0' ) ),
			'spy'           => 'off' === $this->prop( 'scroll_spy', 'on' ) ? '0' : '1',
			'min'           => (string) max( 1, min( 20, absint( $this->prop( 'min_headings', '1' ) ) ) ),
			'collapsed'     => ( $collapsible && 'on' === $this->prop( 'default_collapsed', 'off' ) ) ? '1' : '0',
			'sticky_offset' => (string) absint( $this->prop( 'sticky_offset', '0' ) ),
		);

		$sticky = 'on' === $this->prop( 'sticky', 'off' );

		return Toc_Helper::build_shell(
			$config,
			$list,
			$sticky,
			$collapsible,
			$show_title,
			(string) $this->prop( 'title', esc_html__( 'Table of Contents', 'squad-modules-for-divi' ) ),
			(string) $this->prop( 'title_tag', 'h3' )
		);
	}
}
