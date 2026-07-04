<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName, WordPress.Files.FileName.NotHyphenatedLowercase

/**
 * The Post Grid Module Class which extend the Divi Builder Module Class.
 *
 * This class provides the post-element in the grid system with functionalities in the visual builder.
 *
 * @package DiviSquad
 * @author  WP Squad <support@squadmodules.com>
 * @since   1.0.0
 */

namespace DiviSquad\Modules;

use DiviSquad\Base\DiviBuilder\Module;
use DiviSquad\Base\DiviBuilder\Utils;
use DiviSquad\Utils\Divi;
use DiviSquad\Utils\Helper;
use DiviSquad\Utils\LRCart;
use DiviSquad\Utils\Polyfills\Str;
use ET_Builder_Module_Helper_MultiViewOptions;
use WP_Post;
use WP_Query;
use WP_Term;
use WP_User;
use function apply_filters;
use function esc_attr;
use function esc_html;
use function esc_html__;
use function esc_url;
use function et_pb_background_options;
use function et_pb_get_extended_font_icon_value;
use function et_pb_media_options;
use function et_pb_multi_view_options;
use function get_permalink;
use function get_post;
use function get_post_class;
use function get_queried_object;
use function get_query_var;
use function get_search_query;
use function get_the_post_thumbnail;
use function get_userdata;
use function is_archive;
use function is_author;
use function is_date;
use function is_search;
use function is_singular;
use function paginate_links;
use function sanitize_text_field;
use function wp_array_slice_assoc;
use function wp_enqueue_script;
use function wp_get_post_categories;
use function wp_get_post_tags;
use function wp_json_encode;
use function wp_kses_post;
use function wp_parse_args;
use function wp_reset_postdata;
use function wp_strip_all_tags;

/**
 * The Post-Grid Module Class.
 *
 * @package DiviSquad
 * @since   1.0.0
 */
class PostGrid extends Module {

	/**
	 * Initiate Module.
	 * Set the module name on init.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init() {
		$this->name      = esc_html__( 'Post Grid', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Post Grids', 'squad-modules-for-divi' );
		$this->icon_path = Helper::fix_slash( divi_squad()->get_icon_path() . '/post-grid.svg' );

		$this->slug             = 'disq_post_grid';
		$this->child_slug       = 'disq_post_grid_child';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.$this->slug";

		// The icon eligible elements.
		$this->icon_not_eligible_elements = array( 'none', 'title', 'featured_image', 'content', 'gravatar', 'divider' );

		// Connect with utils.
		$this->squad_utils = Utils::connect( $this );

		// Declare settings modal toggles for the module.
		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'wrapper'          => esc_html__( 'Post Settings', 'squad-modules-for-divi' ),
					'layout'           => esc_html__( 'Layout Settings', 'squad-modules-for-divi' ),
					'pagination'       => esc_html__( 'Pagination Settings', 'squad-modules-for-divi' ),
					'load_more_button' => esc_html__( 'Load More', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'wrapper'                => esc_html__( 'Post Wrapper', 'squad-modules-for-divi' ),
					'elements'               => esc_html__( 'Element Wrapper', 'squad-modules-for-divi' ),
					'element_element'        => esc_html__( 'Element', 'squad-modules-for-divi' ),
					'load_more_button'       => esc_html__( 'Load More', 'squad-modules-for-divi' ),
					'load_more_button_text'  => esc_html__( 'Load More Text', 'squad-modules-for-divi' ),
					'pagination_wrapper'     => esc_html__( 'Pagination Wrapper', 'squad-modules-for-divi' ),
					'pagination'             => esc_html__( 'Pagination', 'squad-modules-for-divi' ),
					'pagination_text'        => esc_html__( 'Pagination Text', 'squad-modules-for-divi' ),
					'active_pagination'      => esc_html__( 'Active Pagination', 'squad-modules-for-divi' ),
					'active_pagination_text' => esc_html__( 'Active Pagination Text', 'squad-modules-for-divi' ),
				),
			),
		);

		// Declare advanced fields for the module.
		$this->advanced_fields = array(
			'fonts'          => array(
				'load_more_button_text'  => Utils::add_font_field(
					esc_html__( 'Button', 'squad-modules-for-divi' ),
					array(
						'font_size'       => array(
							'default' => '20px',
						),
						'text_align'      => array(
							'show_if' => array(
								'load_more__enable' => 'on',
							),
						),
						'text_shadow'     => array(
							'show_if' => array(
								'load_more__enable' => 'on',
							),
						),
						'hide_text_align' => true,
						'css'             => array(
							'main'  => "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button .button-text",
							'hover' => "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button:hover .button-text",
						),
					)
				),
				'pagination_text'        => Utils::add_font_field(
					esc_html__( 'Pagination', 'squad-modules-for-divi' ),
					array(
						'font_size'       => array(
							'default' => '16px',
						),
						'text_align'      => array(
							'show_if' => array(
								'pagination__enable' => 'on',
							),
						),
						'text_shadow'     => array(
							'show_if' => array(
								'pagination__enable' => 'on',
							),
						),
						'hide_text_align' => true,
						'css'             => array(
							'main'  => "$this->main_css_element div .squad-pagination .pagination-numbers .page-numbers, $this->main_css_element div .squad-pagination .pagination-entries",
							'hover' => "$this->main_css_element div .squad-pagination .pagination-numbers .page-numbers:hover, $this->main_css_element div .squad-pagination .pagination-entries:hover",
						),
					)
				),
				'active_pagination_text' => Utils::add_font_field(
					esc_html__( 'Pagination', 'squad-modules-for-divi' ),
					array(
						'font_size'       => array(
							'default' => '16px',
						),
						'text_align'      => array(
							'show_if' => array(
								'pagination__enable' => 'on',
							),
						),
						'text_shadow'     => array(
							'show_if' => array(
								'pagination__enable' => 'on',
							),
						),
						'hide_text_align' => true,
						'css'             => array(
							'main'  => "$this->main_css_element div .squad-pagination .pagination-numbers .page-numbers.current",
							'hover' => "$this->main_css_element div .squad-pagination .pagination-numbers .page-numbers.current:hover",
						),
					)
				),
			),
			'background'     => Utils::selectors_background( $this->main_css_element ),
			'borders'        => array(
				'default'            => Utils::selectors_default( $this->main_css_element ),
				'wrapper'            => array(
					'label_prefix' => esc_html__( 'Wrapper', 'squad-modules-for-divi' ),
					'css'          => array(
						'main' => array(
							'border_radii'        => "$this->main_css_element .squad-post-container .post",
							'border_radii_hover'  => "$this->main_css_element .squad-post-container .post:hover",
							'border_styles'       => "$this->main_css_element .squad-post-container .post",
							'border_styles_hover' => "$this->main_css_element .squad-post-container .post:hover",
						),
					),
					'defaults'     => array(
						'border_radii'  => 'on||||',
						'border_styles' => array(
							'width' => '1px|1px|1px|1px',
							'color' => '#d8d8d8',
							'style' => 'solid',
						),
					),
					'tab_slug'     => 'advanced',
					'toggle_slug'  => 'wrapper',
				),
				'elements'           => array(
					'label_prefix' => esc_html__( 'Wrapper', 'squad-modules-for-divi' ),
					'css'          => array(
						'main' => array(
							'border_radii'        => "$this->main_css_element .squad-post-container .post .post-elements",
							'border_radii_hover'  => "$this->main_css_element .squad-post-container .post:hover .post-elements",
							'border_styles'       => "$this->main_css_element .squad-post-container .post .post-elements",
							'border_styles_hover' => "$this->main_css_element .squad-post-container .post:hover .post-elements",
						),
					),
					'defaults'     => array(
						'border_radii'  => 'on||||',
						'border_styles' => array(
							'width' => '0px|0px|0px|0px',
							'color' => '#333',
							'style' => 'solid',
						),
					),
					'tab_slug'     => 'advanced',
					'toggle_slug'  => 'elements',
				),
				'element_element'    => array(
					'label_prefix' => esc_html__( 'Element', 'squad-modules-for-divi' ),
					'css'          => array(
						'main' => array(
							'border_radii'        => "$this->main_css_element .squad-post-container .post .squad-post-element",
							'border_radii_hover'  => "$this->main_css_element .squad-post-container .post:hover .squad-post-element",
							'border_styles'       => "$this->main_css_element .squad-post-container .post .squad-post-element",
							'border_styles_hover' => "$this->main_css_element .squad-post-container .post:hover .squad-post-element",
						),
					),
					'defaults'     => array(
						'border_radii'  => 'on||||',
						'border_styles' => array(
							'width' => '0px|0px|0px|0px',
							'color' => '#333',
							'style' => 'solid',
						),
					),
					'tab_slug'     => 'advanced',
					'toggle_slug'  => 'element_element',
				),
				'load_more_button'   => array(
					'label_prefix'    => esc_html__( 'Button', 'squad-modules-for-divi' ),
					'css'             => array(
						'main' => array(
							'border_radii'        => "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button",
							'border_radii_hover'  => "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button:hover",
							'border_styles'       => "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button",
							'border_styles_hover' => "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button:hover",
						),
					),
					'defaults'        => array(
						'border_radii'  => 'on|3px|3px|3px|3px',
						'border_styles' => array(
							'width' => '2px|2px|2px|2px',
							'color' => '#333',
							'style' => 'solid',
						),
					),
					'depends_on'      => array( 'load_more__enable' ),
					'depends_show_if' => 'on',
					'tab_slug'        => 'advanced',
					'toggle_slug'     => 'load_more_button',
				),
				'pagination'         => array(
					'label_prefix'    => esc_html__( 'Pagination', 'squad-modules-for-divi' ),
					'css'             => array(
						'main' => array(
							'border_radii'        => "$this->main_css_element div .squad-pagination .pagination-numbers .page-numbers, $this->main_css_element div .squad-pagination .pagination-entries",
							'border_radii_hover'  => "$this->main_css_element div .squad-pagination .pagination-numbers .page-numbers:hover, $this->main_css_element div .squad-pagination .pagination-entries:hover",
							'border_styles'       => "$this->main_css_element div .squad-pagination .pagination-numbers .page-numbers, $this->main_css_element div .squad-pagination .pagination-entries",
							'border_styles_hover' => "$this->main_css_element div .squad-pagination .pagination-numbers .page-numbers:hover, $this->main_css_element div .squad-pagination .pagination-entries:hover",
						),
					),
					'defaults'        => array(
						'border_radii'  => 'on||||',
						'border_styles' => array(
							'width' => '0px|0px|0px|0px',
							'color' => '#333',
							'style' => 'solid',
						),
					),
					'depends_on'      => array( 'pagination__enable' ),
					'depends_show_if' => 'on',
					'tab_slug'        => 'advanced',
					'toggle_slug'     => 'pagination',
				),
				'pagination_wrapper' => array(
					'label_prefix'    => esc_html__( 'Wrapper', 'squad-modules-for-divi' ),
					'css'             => array(
						'main' => array(
							'border_radii'        => "$this->main_css_element div .squad-pagination",
							'border_radii_hover'  => "$this->main_css_element div .squad-pagination:hover",
							'border_styles'       => "$this->main_css_element div .squad-pagination",
							'border_styles_hover' => "$this->main_css_element div .squad-pagination:hover",
						),
					),
					'defaults'        => array(
						'border_radii'  => 'on||||',
						'border_styles' => array(
							'width' => '0px|0px|0px|0px',
							'color' => '#333',
							'style' => 'solid',
						),
					),
					'depends_on'      => array( 'pagination__enable' ),
					'depends_show_if' => 'on',
					'tab_slug'        => 'advanced',
					'toggle_slug'     => 'pagination_wrapper',
				),
				'active_pagination'  => array(
					'label_prefix'    => esc_html__( 'Pagination', 'squad-modules-for-divi' ),
					'css'             => array(
						'main' => array(
							'border_radii'        => "$this->main_css_element div .squad-pagination .pagination-numbers .page-numbers.current",
							'border_radii_hover'  => "$this->main_css_element div .squad-pagination .pagination-numbers .page-numbers.current:hover",
							'border_styles'       => "$this->main_css_element div .squad-pagination .pagination-numbers .page-numbers.current",
							'border_styles_hover' => "$this->main_css_element div .squad-pagination .pagination-numbers .page-numbers.current:hover",
						),
					),
					'defaults'        => array(
						'border_radii'  => 'on||||',
						'border_styles' => array(
							'width' => '0px|0px|0px|0px',
							'color' => '#333',
							'style' => 'solid',
						),
					),
					'depends_on'      => array( 'pagination__enable' ),
					'depends_show_if' => 'on',
					'tab_slug'        => 'advanced',
					'toggle_slug'     => 'active_pagination',
				),
			),
			'box_shadow'     => array(
				'default'            => Utils::selectors_default( $this->main_css_element ),
				'wrapper'            => array(
					'label'             => esc_html__( 'Wrapper Box Shadow', 'squad-modules-for-divi' ),
					'option_category'   => 'layout',
					'css'               => array(
						'main'  => "$this->main_css_element .squad-post-container .post",
						'hover' => "$this->main_css_element .squad-post-container .post:hover",
					),
					'default_on_fronts' => array(
						'color'    => 'rgba(0,0,0,0.3)',
						'position' => 'outer',
					),
					'tab_slug'          => 'advanced',
					'toggle_slug'       => 'wrapper',
				),
				'elements'           => array(
					'label'             => esc_html__( 'Wrapper Box Shadow', 'squad-modules-for-divi' ),
					'option_category'   => 'layout',
					'css'               => array(
						'main'  => "$this->main_css_element .squad-post-container .post .post-elements",
						'hover' => "$this->main_css_element .squad-post-container .post:hover .post-elements",
					),
					'default_on_fronts' => array(
						'color'    => 'rgba(0,0,0,0.3)',
						'position' => 'outer',
					),
					'tab_slug'          => 'advanced',
					'toggle_slug'       => 'elements',
				),
				'element_element'    => array(
					'label'             => esc_html__( 'Element Box Shadow', 'squad-modules-for-divi' ),
					'option_category'   => 'layout',
					'css'               => array(
						'main'  => "$this->main_css_element .squad-post-container .post .squad-post-element",
						'hover' => "$this->main_css_element .squad-post-container .post:hover .squad-post-element",
					),
					'default_on_fronts' => array(
						'color'    => 'rgba(0,0,0,0.3)',
						'position' => 'outer',
					),
					'tab_slug'          => 'advanced',
					'toggle_slug'       => 'element_element',
				),
				'load_more_button'   => array(
					'label'             => esc_html__( 'Button Box Shadow', 'squad-modules-for-divi' ),
					'option_category'   => 'layout',
					'css'               => array(
						'main'  => "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button",
						'hover' => "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button:hover",
					),
					'default_on_fronts' => array(
						'color'    => 'rgba(0,0,0,0.3)',
						'position' => 'outer',
					),
					'depends_on'        => array( 'load_more__enable' ),
					'depends_show_if'   => 'on',
					'tab_slug'          => 'advanced',
					'toggle_slug'       => 'load_more_button',
				),
				'pagination_wrapper' => array(
					'label'             => esc_html__( 'Wrapper Box Shadow', 'squad-modules-for-divi' ),
					'option_category'   => 'layout',
					'css'               => array(
						'main'  => "$this->main_css_element div .squad-pagination",
						'hover' => "$this->main_css_element div .squad-pagination:hover",
					),
					'default_on_fronts' => array(
						'color'    => 'rgba(0,0,0,0.3)',
						'position' => 'outer',
					),
					'depends_on'        => array( 'pagination__enable' ),
					'depends_show_if'   => 'on',
					'tab_slug'          => 'advanced',
					'toggle_slug'       => 'pagination_wrapper',
				),
				'pagination'         => array(
					'label'             => esc_html__( 'Pagination Box Shadow', 'squad-modules-for-divi' ),
					'option_category'   => 'layout',
					'css'               => array(
						'main'  => "$this->main_css_element div .squad-pagination .pagination-numbers .page-numbers, $this->main_css_element div .squad-pagination .pagination-entries",
						'hover' => "$this->main_css_element div .squad-pagination .pagination-numbers .page-numbers:hover, $this->main_css_element div .squad-pagination .pagination-entries:hover",
					),
					'default_on_fronts' => array(
						'color'    => 'rgba(0,0,0,0.3)',
						'position' => 'outer',
					),
					'depends_on'        => array( 'pagination__enable' ),
					'depends_show_if'   => 'on',
					'tab_slug'          => 'advanced',
					'toggle_slug'       => 'pagination',
				),
				'active_pagination'  => array(
					'label'             => esc_html__( 'Pagination Box Shadow', 'squad-modules-for-divi' ),
					'option_category'   => 'layout',
					'css'               => array(
						'main'  => "$this->main_css_element div .squad-pagination .pagination-numbers .page-numbers.current",
						'hover' => "$this->main_css_element div .squad-pagination .pagination-numbers .page-numbers.current:hover",
					),
					'default_on_fronts' => array(
						'color'    => 'rgba(0,0,0,0.3)',
						'position' => 'outer',
					),
					'depends_on'        => array( 'pagination__enable' ),
					'depends_show_if'   => 'on',
					'tab_slug'          => 'advanced',
					'toggle_slug'       => 'active_pagination',
				),
			),
			'margin_padding' => Utils::selectors_margin_padding( $this->main_css_element ),
			'max_width'      => Utils::selectors_max_width( $this->main_css_element ),
			'height'         => Utils::selectors_default( $this->main_css_element ),
			'image_icon'     => false,
			'link_options'   => false,
			'filters'        => false,
			'text'           => false,
			'button'         => false,
		);

		// Declare custom css fields for the module.
		$this->custom_css_fields = array(
			'wrapper'                  => array(
				'label'    => esc_html__( 'Post Wrapper', 'squad-modules-for-divi' ),
				'selector' => "$this->main_css_element div .squad-post-container .post",
			),
			'load_more_button'         => array(
				'label'    => esc_html__( 'Load More Button', 'squad-modules-for-divi' ),
				'selector' => "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button",
			),
			'pagination_wrapper'       => array(
				'label'    => esc_html__( 'Pagination Wrapper', 'squad-modules-for-divi' ),
				'selector' => "$this->main_css_element div .squad-pagination",
			),
			'pagination_numbers'       => array(
				'label'    => esc_html__( 'Pagination', 'squad-modules-for-divi' ),
				'selector' => "$this->main_css_element div .squad-pagination .pagination-numbers .page-numbers, $this->main_css_element div .squad-pagination .pagination-entries",
			),
			'pagination_active_number' => array(
				'label'    => esc_html__( 'Active Pagination', 'squad-modules-for-divi' ),
				'selector' => "$this->main_css_element div .squad-pagination .pagination-numbers .page-numbers.current",
			),
		);
	}

	/**
	 * Add custom hooks
	 *
	 * @return void
	 */
	public function squad_init_custom_hooks() {
		add_filter( 'divi_squad_post_query_current_post_element_outside', array( $this, 'wp_hook_squad_current_outside_post_element' ), 10, 2 );
		add_filter( 'divi_squad_post_query_current_post_element_main', array( $this, 'wp_hook_squad_current_main_post_element' ), 10, 2 );
	}

	/**
	 * Return an added new item(module) text.
	 *
	 * @return string
	 */
	public function add_new_child_text() {
		return esc_html__( 'Add New Element', 'squad-modules-for-divi' );
	}

	/**
	 * Declare general fields for the module.
	 *
	 * @since 1.0.0
	 *
	 * @return array[]
	 */
	public function get_fields() {
		$general_settings = array(
			'inherit_current_loop'          => Utils::add_yes_no_field(
				esc_html__( 'Posts For Current Page', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Display posts for the current page. Useful on all author pages.', 'squad-modules-for-divi' ),
					'default'          => 'off',
					'show_if'          => array(
						'function.isTBLayout' => 'on',
					),
					'computed_affects' => array(
						'__posts',
					),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'wrapper',
				)
			),
			'list_post_display_by'          => Utils::add_select_box_field(
				esc_html__( 'Display By', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Sort retrieved posts by parameter. Defaults to ‘recent’.', 'squad-modules-for-divi' ),
					'options'          => array(
						'recent'   => esc_html__( 'Recent', 'squad-modules-for-divi' ),
						'category' => esc_html__( 'Category', 'squad-modules-for-divi' ),
						'tag'      => esc_html__( 'Tag', 'squad-modules-for-divi' ),
					),
					'default'          => 'recent',
					'default_on_front' => 'recent',
					'computed_affects' => array(
						'__posts',
					),
					'affects'          => array(
						'list_post_include_categories',
						'list_post_include_tags',
					),
					'show_if_not'      => array(
						'inherit_current_loop' => 'on',
					),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'wrapper',
				)
			),
			'list_post_include_categories'  => array(
				'label'            => esc_html__( 'Include Categories', 'squad-modules-for-divi' ),
				'type'             => 'categories',
				'meta_categories'  => array(
					'all'     => esc_html__( 'All Categories', 'squad-modules-for-divi' ),
					'current' => esc_html__( 'Current Category', 'squad-modules-for-divi' ),
				),
				'renderer_options' => array(
					'use_terms' => true,
					'term_name' => 'category',
				),
				'taxonomy_name'    => 'category',
				'depends_show_if'  => 'category',
				'tab_slug'         => 'general',
				'toggle_slug'      => 'wrapper',
			),
			'list_post_include_tags'        => array(
				'label'            => esc_html__( 'Include Tags', 'squad-modules-for-divi' ),
				'type'             => 'categories',
				'renderer_options' => array(
					'use_terms' => true,
					'term_name' => 'post_tag',
				),
				'taxonomy_name'    => 'post_tag',
				'depends_show_if'  => 'tag',
				'tab_slug'         => 'general',
				'toggle_slug'      => 'wrapper',
			),
			'list_post_order_by'            => Utils::add_select_box_field(
				esc_html__( 'Order By', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Sort retrieved posts by parameter. Defaults to ‘date’.', 'squad-modules-for-divi' ),
					'options'          => array(
						'date'          => esc_html__( 'Publish Date', 'squad-modules-for-divi' ),
						'modified'      => esc_html__( 'Modified Date', 'squad-modules-for-divi' ),
						'name'          => esc_html__( 'Post Name', 'squad-modules-for-divi' ),
						'title'         => esc_html__( 'Post Title', 'squad-modules-for-divi' ),
						'author'        => esc_html__( 'Post Author', 'squad-modules-for-divi' ),
						'comment_count' => esc_html__( 'Comments', 'squad-modules-for-divi' ),
						'rand'          => esc_html__( 'Random', 'squad-modules-for-divi' ),
					),
					'computed_affects' => array(
						'__posts',
					),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'wrapper',
				)
			),
			'list_post_order'               => Utils::add_select_box_field(
				esc_html__( 'Order', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Designates the ascending or descending order of the ‘orderby‘ parameter. Defaults to ‘Ascending’..', 'squad-modules-for-divi' ),
					'options'          => array(
						'ASC'  => esc_html__( 'Ascending', 'squad-modules-for-divi' ),
						'DESC' => esc_html__( 'Descending', 'squad-modules-for-divi' ),
					),
					'default'          => 'ASC',
					'computed_affects' => array(
						'__posts',
					),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'wrapper',
				)
			),
			'list_post_count'               => Utils::add_range_field(
				esc_html__( 'Post Count', 'squad-modules-for-divi' ),
				array(
					'description'       => esc_html__( 'Here you can choose how much posts you would like to display per page.', 'squad-modules-for-divi' ),
					'type'              => 'range',
					'range_settings'    => array(
						'min_limit' => '1',
						'min'       => '1',
						'max_limit' => '1000',
						'max'       => '1000',
						'step'      => '1',
					),
					'default'           => 10,
					'number_validation' => true,
					'fixed_range'       => true,
					'unitless'          => true,
					'hover'             => false,
					'mobile_options'    => false,
					'responsive'        => false,
					'computed_affects'  => array(
						'__posts',
					),
					'tab_slug'          => 'general',
					'toggle_slug'       => 'wrapper',
				)
			),
			'list_post_offset'              => Utils::add_range_field(
				esc_html__( 'Post Offset', 'squad-modules-for-divi' ),
				array(
					'description'       => esc_html__( 'Here you can choose how much post show in the current page.', 'squad-modules-for-divi' ),
					'type'              => 'range',
					'range_settings'    => array(
						'min_limit' => '0',
						'min'       => '0',
						'max_limit' => '1000',
						'max'       => '1000',
						'step'      => '1',
					),
					'number_validation' => true,
					'fixed_range'       => true,
					'unitless'          => true,
					'hover'             => false,
					'mobile_options'    => false,
					'responsive'        => false,
					'computed_affects'  => array(
						'__posts',
					),
					'tab_slug'          => 'general',
					'toggle_slug'       => 'wrapper',
				)
			),
			'list_post_ignore_sticky_posts' => Utils::add_yes_no_field(
				esc_html__( 'Skip Sticky Posts', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( ' Ignore sticky  posts for the current page.', 'squad-modules-for-divi' ),
					'default'          => 'off',
					'computed_affects' => array(
						'__posts',
					),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'wrapper',
				)
			),
			'thumbnail_size'                => array(
				'label'            => esc_html__( 'Featured Image Size', 'squad-modules-for-divi' ),
				'description'      => esc_html__( 'If you would like to adjust the date format, input the appropriate PHP date format here.', 'squad-modules-for-divi' ),
				'type'             => 'text',
				'option_category'  => 'configuration',
				'default'          => 'M j, Y',
				'computed_affects' => array(
					'__posts',
				),
				'tab_slug'         => 'general',
				'toggle_slug'      => 'wrapper',
			),
			'date_format'                   => array(
				'label'            => esc_html__( 'Date Format', 'squad-modules-for-divi' ),
				'description'      => esc_html__( 'If you would like to adjust the date format, input the appropriate PHP date format here.', 'squad-modules-for-divi' ),
				'type'             => 'text',
				'option_category'  => 'configuration',
				'default'          => 'M j, Y',
				'computed_affects' => array(
					'__posts',
				),
				'tab_slug'         => 'general',
				'toggle_slug'      => 'wrapper',
			),
			'__posts'                       => array(
				'type'                => 'computed',
				'computed_callback'   => array( self::class, 'squad_get_posts_html' ),
				'computed_depends_on' => array(
					'inherit_current_loop',
					'list_post_display_by',
					'list_post_include_categories',
					'list_post_include_tags',
					'list_post_order_by',
					'list_post_order',
					'list_post_count',
					'list_post_offset',
					'list_post_ignore_sticky_posts',
					'date_format',
				),
			),
		);
		$layout_settings  = array(
			'list_number_of_columns' => Utils::add_range_field(
				esc_html__( 'Column Numbers', 'squad-modules-for-divi' ),
				array(
					'description'       => esc_html__( 'Here you can choose list column for grid layout.', 'squad-modules-for-divi' ),
					'range_settings'    => array(
						'min_limit' => '1',
						'min'       => '1',
						'max_limit' => '6',
						'max'       => '6',
						'step'      => '1',
					),
					'number_validation' => true,
					'fixed_range'       => true,
					'unitless'          => true,
					'default_on_front'  => '3',
					'default_on_tablet' => '2',
					'default_on_mobile' => '1',
					'tab_slug'          => 'general',
					'toggle_slug'       => 'layout',
					'hover'             => false,
				)
			),
			'list_item_gap'          => Utils::add_range_field(
				esc_html__( 'Columns Gap', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Here you can choose list item gap.', 'squad-modules-for-divi' ),
					'type'           => 'range',
					'range_settings' => array(
						'min_limit' => '1',
						'min'       => '1',
						'max_limit' => '100',
						'max'       => '100',
						'step'      => '1',
					),
					'default'        => '10px',
					'default_unit'   => 'px',
					'hover'          => false,
					'tab_slug'       => 'general',
					'toggle_slug'    => 'layout',
				)
			),
			'pagination__enable'     => Utils::add_yes_no_field(
				esc_html__( 'Show Pagination', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can choose whether or not show the pagination.', 'squad-modules-for-divi' ),
					'default_on_front' => 'off',
					'show_if_not'      => array(
						'load_more__enable' => 'on',
					),
					'affects'          => array(
						'pagination_numbers__enable',
						'pagination_icon_only__enable',
						'pagination_old_entries_icon',
						'pagination_next_entries_icon',
						'pagination_text',
						'pagination_text_font',
						'pagination_text_text_color',
						'pagination_text_text_align',
						'pagination_text_font_size',
						'pagination_text_letter_spacing',
						'pagination_text_line_height',
						'active_pagination_text',
						'active_pagination_text_font',
						'active_pagination_text_text_color',
						'active_pagination_text_text_align',
						'active_pagination_text_font_size',
						'active_pagination_text_letter_spacing',
						'active_pagination_text_line_height',
						'pagination_wrapper_background_color',
						'pagination_background_color',
						'active_pagination_background_color',
						'pagination_icon_color',
						'pagination_icon_size',
						'pagination_horizontal_alignment',
						'pagination_elements_gap',
						'pagination_wrapper_margin',
						'pagination_wrapper_padding',
						'pagination_icon_margin',
						'pagination_icon_padding',
						'pagination_margin',
						'pagination_padding',
						'active_pagination_margin',
						'active_pagination_padding',
					),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'layout',
				)
			),
			'load_more__enable'      => Utils::add_yes_no_field(
				esc_html__( 'Show Load More Button', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can choose whether or not load more button.', 'squad-modules-for-divi' ),
					'default_on_front' => 'off',
					'show_if_not'      => array(
						'pagination__enable' => 'on',
					),
					'affects'          => array(
						'load_more_spinner_show',
						'load_more_button_text',
						'load_more_button_text_font',
						'load_more_button_text_text_color',
						'load_more_button_text_text_align',
						'load_more_button_text_font_size',
						'load_more_button_text_letter_spacing',
						'load_more_button_text_line_height',
						'load_more_button_icon_type',
						'load_more_button_background_color',
						'load_more_button_hover_animation__enable',
						'load_more_button_custom_width',
						'load_more_button_horizontal_alignment',
						'load_more_button_margin',
						'load_more_button_padding',
					),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'layout',
				)
			),
			'load_more_spinner_show' => Utils::add_yes_no_field(
				esc_html__( 'Show Load More Spinner', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can choose whether or not load more spinner at the visual builder.', 'squad-modules-for-divi' ),
					'default_on_front' => 'on',
					'depends_show_if'  => 'on',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'layout',
				)
			),
		);

		$pagination_fields                    = array(
			'pagination_numbers__enable'   => Utils::add_yes_no_field(
				esc_html__( 'Show Numbers', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can choose whether or not show the pagination.', 'squad-modules-for-divi' ),
					'default_on_front' => 'off',
					'default'          => 'off',
					'depends_show_if'  => 'on',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'pagination',
				)
			),
			'pagination_icon_only__enable' => Utils::add_yes_no_field(
				esc_html__( 'Show Icon Only for Older and Next Entries ', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can choose whether or not show the pagination.', 'squad-modules-for-divi' ),
					'default_on_front' => 'off',
					'depends_show_if'  => 'on',
					'affects'          => array(
						'pagination_old_entries_text',
						'pagination_next_entries_text',
						'pagination_icon_text_gap',
					),
					'computed_affects' => array(
						'__posts',
					),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'pagination',
				)
			),
			'pagination_old_entries_text'  => array(
				'label'           => esc_html__( 'Old Entries Text', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'The text will appear in with your old entries.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'configuration',
				'depends_show_if' => 'off',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'pagination',
			),
			'pagination_old_entries_icon'  => array(
				'label'            => esc_html__( 'Old Entries Icon', 'squad-modules-for-divi' ),
				'description'      => esc_html__( 'Choose an icon to display with your old entries.', 'squad-modules-for-divi' ),
				'type'             => 'select_icon',
				'option_category'  => 'basic_option',
				'class'            => array( 'et-pb-font-icon' ),
				'default_on_front' => '&#x3c;||divi||400',
				'default'          => '&#x3c;||divi||400',
				'depends_show_if'  => 'on',
				'tab_slug'         => 'general',
				'toggle_slug'      => 'pagination',
				'hover'            => 'tabs',
				'mobile_options'   => true,
			),
			'pagination_next_entries_text' => array(
				'label'           => esc_html__( 'Next Entries Text', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'The text will appear in with your next entries.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'configuration',
				'depends_show_if' => 'off',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'pagination',
			),
			'pagination_next_entries_icon' => array(
				'label'            => esc_html__( 'Next Entries Icon', 'squad-modules-for-divi' ),
				'description'      => esc_html__( 'Choose an icon to display with your next entries.', 'squad-modules-for-divi' ),
				'type'             => 'select_icon',
				'option_category'  => 'basic_option',
				'class'            => array( 'et-pb-font-icon' ),
				'default_on_front' => '&#x3d;||divi||400',
				'default'          => '&#x3d;||divi||400',
				'depends_show_if'  => 'on',
				'tab_slug'         => 'general',
				'toggle_slug'      => 'pagination',
				'hover'            => 'tabs',
				'mobile_options'   => true,
			),
		);
		$pagination_wrapper_background_fields = $this->squad_utils->add_background_field(
			array(
				'label'           => esc_html__( 'Wrapper Background', 'squad-modules-for-divi' ),
				'base_name'       => 'pagination_wrapper_background',
				'context'         => 'pagination_wrapper_background_color',
				'depends_show_if' => 'on',
				'tab_slug'        => 'advanced',
				'toggle_slug'     => 'pagination_wrapper',
			)
		);
		$pagination_background_fields         = $this->squad_utils->add_background_field(
			array(
				'label'           => esc_html__( 'Pagination Background', 'squad-modules-for-divi' ),
				'base_name'       => 'pagination_background',
				'context'         => 'pagination_background_color',
				'depends_show_if' => 'on',
				'tab_slug'        => 'advanced',
				'toggle_slug'     => 'pagination',
			)
		);
		$active_pagination_background_fields  = $this->squad_utils->add_background_field(
			array(
				'label'           => esc_html__( 'Pagination Background', 'squad-modules-for-divi' ),
				'base_name'       => 'active_pagination_background',
				'context'         => 'active_pagination_background_color',
				'depends_show_if' => 'on',
				'tab_slug'        => 'advanced',
				'toggle_slug'     => 'active_pagination',
			)
		);
		$pagination_associated_fields         = array(
			'pagination_icon_color'           => Utils::add_color_field(
				esc_html__( 'Entries Icon Color', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Here you can define a custom color for your icon.', 'squad-modules-for-divi' ),
					'depends_show_if' => 'on',
					'tab_slug'        => 'advanced',
					'toggle_slug'     => 'pagination',
				)
			),
			'pagination_icon_size'            => Utils::add_range_field(
				esc_html__( 'Entries Icon Size', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Here you can choose icon size.', 'squad-modules-for-divi' ),
					'range_settings'  => array(
						'min_limit' => '1',
						'min'       => '1',
						'max_limit' => '200',
						'max'       => '200',
						'step'      => '1',
					),
					'depends_show_if' => 'on',
					'default'         => '16px',
					'default_unit'    => 'px',
					'tab_slug'        => 'advanced',
					'toggle_slug'     => 'pagination',
				)
			),
			'pagination_icon_text_gap'        => Utils::add_range_field(
				esc_html__( 'Gap Between Entries Icon and Text', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Here you can choose gap between entries icon and text.', 'squad-modules-for-divi' ),
					'range_settings'  => array(
						'min_limit' => '1',
						'min'       => '1',
						'max_limit' => '200',
						'max'       => '200',
						'step'      => '1',
					),
					'default'         => '10px',
					'default_unit'    => 'px',
					'depends_show_if' => 'off',
					'tab_slug'        => 'advanced',
					'toggle_slug'     => 'pagination',
					'hover'           => false,
					'mobile_options'  => true,
				)
			),
			'pagination_elements_gap'         => Utils::add_range_field(
				esc_html__( 'Gap Between Pagination Elements', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Here you can choose gap between pagination elements.', 'squad-modules-for-divi' ),
					'range_settings'  => array(
						'min_limit' => '1',
						'min'       => '1',
						'max_limit' => '200',
						'max'       => '200',
						'step'      => '1',
					),
					'default'         => '10px',
					'default_unit'    => 'px',
					'depends_show_if' => 'on',
					'tab_slug'        => 'advanced',
					'toggle_slug'     => 'pagination',
					'hover'           => false,
					'mobile_options'  => true,
				)
			),
			'pagination_horizontal_alignment' => Utils::add_alignment_field(
				esc_html__( 'Pagination Alignment', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Align icon to the left, right or center.', 'squad-modules-for-divi' ),
					'type'             => 'text_align',
					'default'          => 'center',
					'default_on_front' => 'center',
					'depends_show_if'  => 'on',
					'tab_slug'         => 'advanced',
					'toggle_slug'      => 'pagination_wrapper',
				)
			),
			'pagination_icon_margin'          => Utils::add_margin_padding_field(
				esc_html__( 'Entries Icon Margin', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Here you can define a custom margin size.', 'squad-modules-for-divi' ),
					'type'            => 'custom_margin',
					'range_settings'  => array(
						'min_limit' => '1',
						'min'       => '1',
						'max_limit' => '100',
						'max'       => '100',
						'step'      => '1',
					),
					'depends_show_if' => 'on',
					'tab_slug'        => 'advanced',
					'toggle_slug'     => 'pagination',
				)
			),
			'pagination_icon_padding'         => Utils::add_margin_padding_field(
				esc_html__( 'Entries Icon Padding', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Here you can define a custom padding size.', 'squad-modules-for-divi' ),
					'type'            => 'custom_padding',
					'range_settings'  => array(
						'min_limit' => '1',
						'min'       => '1',
						'max_limit' => '100',
						'max'       => '100',
						'step'      => '1',
					),
					'depends_show_if' => 'on',
					'tab_slug'        => 'advanced',
					'toggle_slug'     => 'pagination',
				)
			),
			'pagination_wrapper_margin'       => Utils::add_margin_padding_field(
				esc_html__( 'Wrapper Margin', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can define a custom margin size.', 'squad-modules-for-divi' ),
					'type'             => 'custom_margin',
					'range_settings'   => array(
						'min_limit' => '1',
						'min'       => '1',
						'max_limit' => '100',
						'max'       => '100',
						'step'      => '1',
					),
					'default'          => '20px|||||',
					'default_on_front' => '20px|||||',
					'depends_show_if'  => 'on',
					'tab_slug'         => 'advanced',
					'toggle_slug'      => 'pagination_wrapper',
				)
			),
			'pagination_wrapper_padding'      => Utils::add_margin_padding_field(
				esc_html__( 'Wrapper Padding', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Here you can define a custom padding size.', 'squad-modules-for-divi' ),
					'type'            => 'custom_padding',
					'range_settings'  => array(
						'min_limit' => '1',
						'min'       => '1',
						'max_limit' => '100',
						'max'       => '100',
						'step'      => '1',
					),
					'depends_show_if' => 'on',
					'tab_slug'        => 'advanced',
					'toggle_slug'     => 'pagination_wrapper',
				)
			),
			'pagination_margin'               => Utils::add_margin_padding_field(
				esc_html__( 'Pagination Margin', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Here you can define a custom margin size.', 'squad-modules-for-divi' ),
					'type'            => 'custom_margin',
					'range_settings'  => array(
						'min_limit' => '1',
						'min'       => '1',
						'max_limit' => '100',
						'max'       => '100',
						'step'      => '1',
					),
					'depends_show_if' => 'on',
					'tab_slug'        => 'advanced',
					'toggle_slug'     => 'pagination',
				)
			),
			'pagination_padding'              => Utils::add_margin_padding_field(
				esc_html__( 'Pagination Padding', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Here you can define a custom padding size.', 'squad-modules-for-divi' ),
					'type'            => 'custom_padding',
					'range_settings'  => array(
						'min_limit' => '1',
						'min'       => '1',
						'max_limit' => '100',
						'max'       => '100',
						'step'      => '1',
					),
					'depends_show_if' => 'on',
					'tab_slug'        => 'advanced',
					'toggle_slug'     => 'pagination',
				)
			),
			'active_pagination_margin'        => Utils::add_margin_padding_field(
				esc_html__( 'Pagination Margin', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Here you can define a custom margin size.', 'squad-modules-for-divi' ),
					'type'            => 'custom_margin',
					'range_settings'  => array(
						'min_limit' => '1',
						'min'       => '1',
						'max_limit' => '100',
						'max'       => '100',
						'step'      => '1',
					),
					'depends_show_if' => 'on',
					'tab_slug'        => 'advanced',
					'toggle_slug'     => 'active_pagination',
				)
			),
			'active_pagination_padding'       => Utils::add_margin_padding_field(
				esc_html__( 'Pagination Padding', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Here you can define a custom padding size.', 'squad-modules-for-divi' ),
					'type'            => 'custom_padding',
					'range_settings'  => array(
						'min_limit' => '1',
						'min'       => '1',
						'max_limit' => '100',
						'max'       => '100',
						'step'      => '1',
					),
					'depends_show_if' => 'on',
					'tab_slug'        => 'advanced',
					'toggle_slug'     => 'active_pagination',
				)
			),
		);

		// Button fields definitions.
		$load_more_button = $this->squad_utils->get_button_fields(
			array(
				'base_attr_name'       => 'load_more_button',
				'toggle_slug'          => 'load_more_button',
				'depends_show_if'      => 'on',
				'fields_after_colors'  => array(
					'load_more_spinner_p_color' => Utils::add_color_field(
						esc_html__( 'Spinner Primary Color', 'squad-modules-for-divi' ),
						array(
							'description'     => esc_html__( 'Here you can define a custom color for load more spinner.', 'squad-modules-for-divi' ),
							'depends_show_if' => 'on',
							'hover'           => false,
							'tab_slug'        => 'advanced',
							'toggle_slug'     => 'load_more_button',
						)
					),
					'load_more_spinner_s_color' => Utils::add_color_field(
						esc_html__( 'Spinner Secondary Color', 'squad-modules-for-divi' ),
						array(
							'description'     => esc_html__( 'Here you can define a custom color for load more spinner.', 'squad-modules-for-divi' ),
							'depends_show_if' => 'on',
							'hover'           => false,
							'tab_slug'        => 'advanced',
							'toggle_slug'     => 'load_more_button',
						)
					),
					'load_more_spinner_size'    => Utils::add_range_field(
						esc_html__( 'Spinner Size', 'squad-modules-for-divi' ),
						array(
							'description'    => esc_html__( 'Here you can choose the size of load more spinner.', 'squad-modules-for-divi' ),
							'type'           => 'range',
							'range_settings' => array(
								'min_limit' => '16',
								'min'       => '16',
								'max_limit' => '100',
								'max'       => '100',
								'step'      => '1',
							),
							'default'        => '24px',
							'default_unit'   => 'px',
							'hover'          => false,
							'tab_slug'       => 'advanced',
							'toggle_slug'    => 'load_more_button',
						)
					),
				),
				'fields_before_margin' => array(
					'load_more_button_horizontal_alignment' => Utils::add_alignment_field(
						esc_html__( 'Button Alignment', 'squad-modules-for-divi' ),
						array(
							'description'      => esc_html__( 'Align icon to the left, right or center.', 'squad-modules-for-divi' ),
							'type'             => 'align',
							'default_on_front' => 'left',
							'tab_slug'         => 'advanced',
							'toggle_slug'      => 'load_more_button',
						)
					),
				),
			)
		);

		$post_wrapper_background_fields    = $this->squad_utils->add_background_field(
			array(
				'label'       => esc_html__( 'Wrapper Background', 'squad-modules-for-divi' ),
				'base_name'   => 'post_wrapper_background',
				'context'     => 'post_wrapper_background_color',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'wrapper',
			)
		);
		$element_wrapper_background_fields = $this->squad_utils->add_background_field(
			array(
				'label'       => esc_html__( 'Wrapper Background', 'squad-modules-for-divi' ),
				'base_name'   => 'element_wrapper_background',
				'context'     => 'element_wrapper_background_color',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'elements',
			)
		);
		$element_background_fields         = $this->squad_utils->add_background_field(
			array(
				'label'       => esc_html__( 'Element Background', 'squad-modules-for-divi' ),
				'base_name'   => 'element_background',
				'context'     => 'element_background_color',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'element_element',
			)
		);

		$post_wrapper_associated_fields    = array(
			'post_text_orientation' => Utils::add_alignment_field(
				esc_html__( 'Text Alignment', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'This controls how your text is aligned within the module.', 'squad-modules-for-divi' ),
					'type'        => 'text_align',
					'options'     => et_builder_get_text_orientation_options(
						array( 'justified' ),
						array( 'justify' => 'Justified' )
					),
					'default'     => '',
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'wrapper',
				)
			),
			'post_wrapper_margin'   => Utils::add_margin_padding_field(
				esc_html__( 'Wrapper Margin', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Here you can define a custom margin size for the wrapper.', 'squad-modules-for-divi' ),
					'type'        => 'custom_margin',
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'wrapper',
				)
			),
			'post_wrapper_padding'  => Utils::add_margin_padding_field(
				esc_html__( 'Wrapper Padding', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can define a custom padding size.', 'squad-modules-for-divi' ),
					'type'             => 'custom_padding',
					'default'          => '19px|19px|19px|19px|true|true',
					'default_on_front' => '19px|19px|19px|19px|true|true',
					'tab_slug'         => 'advanced',
					'toggle_slug'      => 'wrapper',
				)
			),
		);
		$element_wrapper_associated_fields = array(
			'element_wrapper_margin'  => Utils::add_margin_padding_field(
				esc_html__( 'Wrapper Margin', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Here you can define a custom margin size for the wrapper.', 'squad-modules-for-divi' ),
					'type'        => 'custom_margin',
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'elements',
				)
			),
			'element_wrapper_padding' => Utils::add_margin_padding_field(
				esc_html__( 'Wrapper Padding', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Here you can define a custom padding size.', 'squad-modules-for-divi' ),
					'type'        => 'custom_padding',
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'elements',
				)
			),
		);
		$element_associated_fields         = array(
			'element_margin'  => Utils::add_margin_padding_field(
				esc_html__( 'Element Margin', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Here you can define a custom margin size.', 'squad-modules-for-divi' ),
					'type'           => 'custom_margin',
					'range_settings' => array(
						'min_limit' => '1',
						'min'       => '1',
						'max_limit' => '100',
						'max'       => '100',
						'step'      => '1',
					),
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'element_element',
				)
			),
			'element_padding' => Utils::add_margin_padding_field(
				esc_html__( 'Element Padding', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Here you can define a custom padding size.', 'squad-modules-for-divi' ),
					'type'           => 'custom_padding',
					'range_settings' => array(
						'min_limit' => '1',
						'min'       => '1',
						'max_limit' => '100',
						'max'       => '100',
						'step'      => '1',
					),
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'element_element',
				)
			),
		);

		return array_merge_recursive(
			$general_settings,
			$layout_settings,
			$pagination_fields,
			$pagination_wrapper_background_fields,
			$pagination_background_fields,
			$active_pagination_background_fields,
			$pagination_associated_fields,
			$load_more_button,
			$post_wrapper_background_fields,
			$post_wrapper_associated_fields,
			$element_wrapper_background_fields,
			$element_wrapper_associated_fields,
			$element_background_fields,
			$element_associated_fields
		);
	}

	/**
	 * Get CSS fields transition.
	 *
	 * Add form field options group and background image on the field list.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_transition_fields_css_props() {
		$fields = parent::get_transition_fields_css_props();

		// wrapper styles.
		$fields['post_wrapper_background_color'] = array( 'background' => "$this->main_css_element .squad-post-container .post" );
		$fields['post_wrapper_margin']           = array( 'margin' => "$this->main_css_element .squad-post-container .post" );
		$fields['post_wrapper_padding']          = array( 'padding' => "$this->main_css_element .squad-post-container .post" );
		Utils::fix_border_transition( $fields, 'wrapper', "$this->main_css_element .squad-post-container .post" );
		Utils::fix_box_shadow_transition( $fields, 'wrapper', "$this->main_css_element .squad-post-container .post" );

		// element wrapper styles.
		$fields['element_wrapper_background_color'] = array( 'background' => "$this->main_css_element .squad-post-container .post .post-elements" );
		$fields['element_wrapper_margin']           = array( 'margin' => "$this->main_css_element .squad-post-container .post .post-elements" );
		$fields['element_wrapper_padding']          = array( 'padding' => "$this->main_css_element .squad-post-container .post .post-elements" );
		Utils::fix_border_transition( $fields, 'elements', "$this->main_css_element .squad-post-container .post .post-elements" );
		Utils::fix_box_shadow_transition( $fields, 'elements', "$this->main_css_element .squad-post-container .post .post-elements" );

		// element styles.
		$fields['element_background_color'] = array( 'background' => "$this->main_css_element .squad-post-container .post .squad-post-element" );
		$fields['element_margin']           = array( 'margin' => "$this->main_css_element .squad-post-container .post .squad-post-element" );
		$fields['element_padding']          = array( 'padding' => "$this->main_css_element .squad-post-container .post .squad-post-element" );
		Utils::fix_border_transition( $fields, 'element_element', "$this->main_css_element .squad-post-container .post .squad-post-element" );
		Utils::fix_box_shadow_transition( $fields, 'element_element', "$this->main_css_element .squad-post-container .post .squad-post-element" );

		// button styles.
		$fields['load_more_button_background_color'] = array( 'background' => "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button" );
		$fields['load_more_button_width']            = array( 'width' => "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button" );
		$fields['load_more_button_icon_margin']      = array( 'margin' => "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button .icon-element" );
		$fields['load_more_button_margin']           = array( 'margin' => "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button" );
		$fields['load_more_button_padding']          = array( 'padding' => "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button" );
		Utils::fix_fonts_transition( $fields, 'load_more_button_text', "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button" );
		Utils::fix_border_transition( $fields, 'load_more_button', "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button" );
		Utils::fix_box_shadow_transition( $fields, 'load_more_button', "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button" );

		// Default styles.
		$fields['background_layout'] = array( 'color' => "$this->main_css_element .squad-post-container .post" );

		return $fields;
	}

	/**
	 * Render module output.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $attrs       List of unprocessed attributes.
	 * @param string $content     Content being processed.
	 * @param string $render_slug Slug of module that is used for rendering output.
	 *
	 * @return string module's rendered output.
	 */
	public function render( $attrs, $content, $render_slug ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClassAfterLastUsed
		// Show a notice message in the frontend if the list item is empty.
		if ( empty( $content ) ) {
			return sprintf(
				'<div class="squad-notice">%s</div>',
				esc_html__( 'No elements found. Please add one or more elements to display in the post grid.', 'squad-modules-for-divi' )
			);
		}

		$multi_view = et_pb_multi_view_options( $this );
		$props      = wp_parse_args( $attrs, $this->props );
		$post_html  = self::squad_get_posts_html( $props, $this->content, $multi_view );
		if ( ! empty( $post_html ) ) {
			$this->squad_generate_all_styles( $attrs );
			$this->squad_generate_layout_styles( $attrs );

			// Load font Awesome css for frontend.
			Divi::inject_fa_icons( $this->prop( 'load_more_button_icon', '&#xx4e;||divi||400' ) );
			Divi::inject_fa_icons( $this->prop( 'pagination_old_entries_icon', '&#x3c;||divi||400' ) );
			Divi::inject_fa_icons( $this->prop( 'pagination_next_entries_icon', '&#x3d;||divi||400' ) );

			return $post_html;
		}

		return sprintf(
			'<div class="squad-notice">%s</div>',
			esc_html__( 'No posts found that match the specified criteria.', 'squad-modules-for-divi' )
		);
	}

	/**
	 * Filter multi view value.
	 *
	 * @param mixed $raw_value Props raw value.
	 * @param array $args      Props arguments.
	 *
	 * @return mixed
	 *
	 * @see   ET_Builder_Module_Helper_MultiViewOptions::filter_value
	 */
	public function multi_view_filter_value( $raw_value, $args ) {
		$name = isset( $args['name'] ) ? $args['name'] : '';

		// process font icon.
		$icon_fields = array(
			'element_icon',
			'element_title_icon',
			'load_more_button_icon',
			'pagination_old_entries_icon',
			'pagination_next_entries_icon',
		);
		if ( $raw_value && in_array( $name, $icon_fields, true ) ) {
			return et_pb_get_extended_font_icon_value( $raw_value, true );
		}

		// process others.
		return $raw_value;
	}

	/**
	 * Render the post-elements in the outside wrapper.
	 *
	 * @param WP_Post      $post    The current post.
	 * @param string|array $content The parent content.
	 *
	 * @return string
	 */
	public function wp_hook_squad_current_outside_post_element( $post, $content ) {
		$callback = function ( $post, $child_prop ) {
			return $this->squad_render_post_element( $post, $child_prop, 'on' );
		};

		return $this->squad_generate_props_content( $post, $content, $callback );
	}

	/**
	 * Render the post-elements in the main wrapper.
	 *
	 * @param WP_Post      $post    The WP POST object.
	 * @param string|array $content The parent content.
	 *
	 * @return string
	 */
	public function wp_hook_squad_current_main_post_element( $post, $content ) {
		$callback = function ( $post, $child_prop ) {
			return $this->squad_render_post_element( $post, $child_prop, 'off' );
		};

		return $this->squad_generate_props_content( $post, $content, $callback );
	}

	/**
	 * Collect all posts from the database.
	 *
	 * @since 1.0.0
	 *
	 * @param array                                     $attrs      List of unprocessed attributes.
	 * @param string|array|null                         $content    Content being processed.
	 * @param ET_Builder_Module_Helper_MultiViewOptions $multi_view Multiview object instance.
	 *
	 * @return string the html output for the post-grid.
	 */
	public static function squad_get_posts_html( $attrs, $content = null, $multi_view = null ) {
		// Set the default values.
		$is_rest_query = ! empty( $attrs['is_rest_query'] ) ? $attrs['is_rest_query'] : 'off';

		$query_args = static::squad_build_post_query_args( $attrs, $content );
		$post_query = new WP_Query( $query_args );

		if ( ! $post_query->have_posts() ) {
			return '';
		}

		ob_start();

		if ( 'off' === $is_rest_query ) {
			print '<ul class="squad-post-container" style="list-style-type: none;">';
		}

		while ( $post_query->have_posts() ) {
			$post_query->the_post();
			static::squad_render_current_post( get_post(), $attrs, $content );
		}

		if ( 'off' === $is_rest_query ) {
			print '</ul>';
		}

		static::squad_maybe_render_pagination( $post_query, $attrs, $content, $multi_view );
		static::squad_maybe_render_load_more_button( $post_query, $attrs, $content, $multi_view );

		/* Restore original Post Data */
		wp_reset_postdata();

		return ob_get_clean();
	}

	/**
	 * Render a post element based on its properties.
	 *
	 * @param WP_Post $post           The current post.
	 * @param array   $child_prop     The child properties.
	 * @param string  $expected_state The expected state ('on' for outside, 'off' for main).
	 *
	 * @return string
	 */
	protected function squad_render_post_element( $post, $child_prop, $expected_state ) {
		$outside_enable = isset( $child_prop['element_outside__enable'] ) ? $child_prop['element_outside__enable'] : 'off';

		if ( $outside_enable !== $expected_state ) {
			return '';
		}

		$element_type  = isset( $child_prop['element'] ) ? $child_prop['element'] : 'none';
		$icon_type     = isset( $child_prop['element_icon_type'] ) ? $child_prop['element_icon_type'] : 'icon';
		$icon_excludes = $this->icon_not_eligible_elements;

		$output = '';

		// Render icon if applicable.
		if ( 'none' !== $icon_type && ! in_array( $element_type, $icon_excludes, true ) ) {
			$output .= $this->squad_render_element_icon( $child_prop );
		}

		// Render element content if applicable.
		if ( 'custom_icon' !== $element_type ) {
			$element_body = $this->squad_render_post_element_body( $child_prop, $post );
			if ( ! empty( $element_body ) ) {
				$output .= $element_body;
			} else {
				$output = '';
			}
		}

		if ( empty( $output ) ) {
			return '';
		}

		return sprintf(
			'<div class="post-elements et_pb_with_background">%s</div>',
			wp_kses_post( $output )
		);
	}

	/**
	 * Generate content by props with dynamic values.
	 *
	 * @param WP_Post      $post        The WP POST object.
	 * @param string|array $content     The parent content.
	 * @param callable     $callback    The render callback.
	 *
	 * @return string
	 */
	protected function squad_generate_props_content( $post, $content, $callback ) {
		ob_start();

		// Collect all child modules from Html content.
		$pattern = '/<div\s+class="[^"]*disq_post_grid_child[^"]*"[^>]*>.*?<\/div>\s+<\/div>/is';
		if ( is_string( $content ) && preg_match_all( $pattern, $content, $matches ) && isset( $matches[0] ) && count( $matches[0] ) ) {
			// Catch module with the main wrapper.
			$child_modules = $matches[0];

			// Output the split tags.
			foreach ( $child_modules as $child_module_content ) {
				$child_raw_props = Utils::collect_raw_props( $child_module_content );
				$child_props     = Utils::collect_child_json_props( $child_raw_props );
				$child_props     = isset( $child_props[0] ) && count( $child_props[0] ) ? $child_props[0] : array();

				if ( count( $child_props ) ) {
					$child_prop_markup = sprintf( '%s,||', wp_json_encode( $child_props ) );
					$html_output       = $callback( $post, $child_props );

					// check available content.
					if ( is_string( $html_output ) && ! empty( $html_output ) ) {
						// Merge with raw content.
						$module_content = str_replace( $child_prop_markup, $html_output, $child_module_content );

						// Show the generated child module content.
						print wp_kses_post( $module_content );
					}
				}
			}
		}

		return ob_get_clean();
	}

	/**
	 * Build the post query arguments.
	 *
	 * @since 3.1.0
	 *
	 * @param array $attrs   List of unprocessed attributes.
	 * @param mixed $content Content being processed.
	 *
	 * @return array
	 */
	protected static function squad_build_post_query_args( $attrs, $content = null ) {
		global $paged;

		$query_args = array(
			'post_status'    => array( 'publish' ),
			'perm'           => array( 'readable' ),
			'posts_per_page' => ! empty( $attrs['list_post_count'] ) ? absint( $attrs['list_post_count'] ) : 10,
			'orderby'        => ! empty( $attrs['list_post_order_by'] ) ? sanitize_key( $attrs['list_post_order_by'] ) : 'date',
			'order'          => ! empty( $attrs['list_post_order'] ) ? sanitize_key( $attrs['list_post_order'] ) : 'ASC',
		);

		$inherit_loop = ! empty( $attrs['inherit_current_loop'] ) ? $attrs['inherit_current_loop'] : 'off';
		if ( 'on' === $inherit_loop ) {
			$query_args = self::squad_add_current_loop_args( $query_args );
		} else {
			$query_args = self::squad_add_custom_display_args( $query_args, $attrs );
		}

		$query_args = self::squad_add_offset_args( $query_args, $attrs, $paged );
		$query_args = self::squad_add_pagination_args( $query_args, $attrs, $paged );

		if ( ! empty( $attrs['list_post_ignore_sticky_posts'] ) && 'on' === $attrs['list_post_ignore_sticky_posts'] ) {
			$query_args['ignore_sticky_posts'] = true;
		}

		/**
		 * Filter the post query arguments.
		 *
		 * @param array $query_args The WP_Query arguments.
		 * @param array $attrs      The module attributes.
		 * @param mixed $content    The content being processed.
		 */
		return apply_filters( 'divi_squad_build_post_query_args', $query_args, $attrs, $content );
	}

	/**
	 * Add query arguments for the current loop.
	 *
	 * @param array $query_args Existing query arguments.
	 * @return array Updated query arguments.
	 */
	protected static function squad_add_current_loop_args( $query_args ) {
		$queried_object = get_queried_object();

		if ( $queried_object instanceof WP_Post && is_singular() ) {
			$query_args = self::squad_add_related_post_args( $query_args, $queried_object );
		} elseif ( $queried_object instanceof WP_User && is_author() ) {
			$query_args['author'] = $queried_object->ID;
		} elseif ( $queried_object instanceof WP_Term && is_archive() ) {
			$query_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => $queried_object->taxonomy,
					'field'    => 'term_id',
					'terms'    => $queried_object->term_id,
				),
			);
		} elseif ( is_search() ) {
			$query_args['s'] = get_search_query();
		} elseif ( is_date() ) {
			$query_args = self::squad_add_date_args( $query_args );
		}

		return $query_args;
	}

	/**
	 * Add query arguments for related posts.
	 *
	 * @param array   $query_args Existing query arguments.
	 * @param WP_Post $post       Current post object.
	 * @return array Updated query arguments.
	 */
	protected static function squad_add_related_post_args( $query_args, $post ) {
		$categories = wp_get_post_categories( $post->ID, array( 'fields' => 'ids' ) );
		if ( ! empty( $categories ) ) {
			$query_args['cat'] = implode( ',', $categories );
		} else {
			$tags = wp_get_post_tags( $post->ID, array( 'fields' => 'ids' ) );
			if ( ! empty( $tags ) ) {
				$query_args['tag__in'] = $tags;
			}
		}

		$query_args['post__not_in'] = array( $post->ID ); // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
		$query_args['author']       = $post->post_author;

		return $query_args;
	}

	/**
	 * Add query arguments for custom display options.
	 *
	 * @param array $query_args Existing query arguments.
	 * @param array $attrs      Module attributes.
	 * @return array Updated query arguments.
	 */
	protected static function squad_add_custom_display_args( $query_args, $attrs ) {
		$display_by = ! empty( $attrs['list_post_display_by'] ) ? $attrs['list_post_display_by'] : 'recent';

		if ( 'category' === $display_by && ! empty( $attrs['list_post_include_categories'] ) ) {
			$query_args['cat'] = $attrs['list_post_include_categories'];
		} elseif ( 'tag' === $display_by && ! empty( $attrs['list_post_include_tags'] ) ) {
			$query_args['tag__in'] = $attrs['list_post_include_tags'];
		}

		return $query_args;
	}

	/**
	 * Add query arguments for post offset.
	 *
	 * @param array $query_args Existing query arguments.
	 * @param array $attrs      Module attributes.
	 * @param int   $paged      Current page number.
	 * @return array Updated query arguments.
	 */
	protected static function squad_add_offset_args( $query_args, $attrs, $paged ) {
		$offset = ! empty( $attrs['list_post_offset'] ) ? absint( $attrs['list_post_offset'] ) : 0;
		if ( $offset > 0 ) {
			if ( ! empty( $attrs['pagination__enable'] ) && 'on' === $attrs['pagination__enable'] && $paged > 1 ) {
				$query_args['offset'] = ( ( $paged - 1 ) * $offset ) + $offset;
			} else {
				$query_args['offset'] = $offset;
			}
		}
		return $query_args;
	}

	/**
	 * Add query arguments for pagination.
	 *
	 * @param array $query_args Existing query arguments.
	 * @param array $attrs      Module attributes.
	 * @param int   $paged      Current page number.
	 * @return array Updated query arguments.
	 */
	protected static function squad_add_pagination_args( $query_args, $attrs, $paged ) {
		if ( ! empty( $attrs['pagination__enable'] ) && 'on' === $attrs['pagination__enable'] ) {
			$query_args['paged'] = $paged;
		}
		return $query_args;
	}

	/**
	 * Add query arguments for date archives.
	 *
	 * @param array $query_args Existing query arguments.
	 * @return array Updated query arguments.
	 */
	protected static function squad_add_date_args( $query_args ) {
		$date_queries = array( 'year', 'monthnum', 'w', 'day', 'hour', 'minute', 'second' );
		foreach ( $date_queries as $key ) {
			$value = get_query_var( $key );
			if ( $value ) {
				$query_args[ $key ] = $value;
			}
		}
		return $query_args;
	}

	/**
	 * Get queried arguments for client side rendering.
	 *
	 * @param array $attrs List of module attributes.
	 * @return array Filtered query arguments.
	 */
	protected static function squad_get_client_query_args( $attrs ) {
		// Allowed properties.
		$allowed_props = array(
			'inherit_current_loop',
			'date_format',
			'list_post_display_by',
			'list_post_count',
			'list_post_include_categories',
			'list_post_include_tags',
			'list_post_offset',
			'list_post_order',
			'list_post_order_by',
		);

		// Filter allowed properties.
		$query_arguments = wp_array_slice_assoc( $attrs, $allowed_props );

		// Remove empty values.
		$array_filter = array();
		foreach ( $query_arguments as $key => $string ) {
			if ( strlen( $string ) ) {
				$array_filter[ $key ] = $string;
			}
		}

		return $array_filter;
	}

	/**
	 * Render the current post.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post    The WP POST object.
	 * @param array   $attrs   The module attributes.
	 * @param mixed   $content The content being processed.
	 *
	 * @return void
	 */
	protected static function squad_render_current_post( $post, $attrs, $content = null ) {
		// Identify the current page state.
		$is_divi_builder = isset( $content ) && is_array( $content );

		// Set the default values.
		$date_format  = ! empty( $attrs['date_format'] ) ? $attrs['date_format'] : 'M j, Y';
		$post_classes = get_post_class( 'post', $post );

		printf( '<li class="%s">', esc_attr( implode( ' ', $post_classes ) ) );

		if ( $is_divi_builder ) {
			$date_replacement = str_replace( '\\\\', '\\', $date_format );
			$author           = get_userdata( absint( $post->post_author ) );
			$post_data        = self::squad_prepare_post_data( $post, $author, $date_replacement );

			/**
			 * Filters the post data for the frontend.
			 *
			 * This filter allows you to modify or extend the post data that will be
			 * available in the frontend for each rendered post.
			 *
			 * @since 1.0.0
			 *
			 * @param array   $post_data The prepared post data.
			 * @param WP_Post $post      The current post object.
			 * @param mixed   $content   The content being processed.
			 */
			$post_data = apply_filters( 'divi_squad_post_query_current_post_data', $post_data, $post, $content );

			printf(
				'<script type="application/json" style="display: none">%s</script>',
				wp_json_encode( $post_data )
			);
		}

		/**
		 * Filters the post elements in the outside wrapper.
		 *
		 * This filter allows you to add or modify content that will be rendered
		 * in the outer wrapper of each post in the grid.
		 *
		 * @since 1.0.0
		 *
		 * @param WP_Post $post    The current post object.
		 * @param mixed   $content The content being processed.
		 */
		$outside = apply_filters( 'divi_squad_post_query_current_post_element_outside', $post, $content );

		/**
		 * Filters the post elements in the main wrapper.
		 *
		 * This filter allows you to add or modify content that will be rendered
		 * in the main wrapper of each post in the grid.
		 *
		 * @since 1.0.0
		 *
		 * @param WP_Post $post    The current post object.
		 * @param mixed   $content The content being processed.
		 */
		$inside = apply_filters( 'divi_squad_post_query_current_post_element_main', $post, $content );

		// Show outer elements in the frontend.
		if ( ! empty( $outside ) && is_string( $outside ) ) {
			printf( '<div class="squad-post-outer">%s</div>', wp_kses_post( $outside ) );
		}

		// Show inner elements in the frontend.
		if ( ! empty( $inside ) && is_string( $inside ) ) {
			printf( '<div class="squad-post-inner">%s</div>', wp_kses_post( $inside ) );
		}

		echo '</li>';
	}

	/**
	 * Prepare post data for frontend rendering.
	 *
	 * @param WP_Post $post              The WP POST object.
	 * @param WP_User $author            The post author object.
	 * @param string  $date_replacement  The date format string.
	 *
	 * @return array
	 */
	protected static function squad_prepare_post_data( $post, $author, $date_replacement ) {
		// Get categories with permalinks
		$categories      = wp_get_post_categories( $post->ID, array( 'fields' => 'all' ) );
		$categories      = ! $categories instanceof \WP_Error ? $categories : array();
		$categories_data = array();
		foreach ( $categories as $key => $category ) {
			$categories_data[ $key ] = array(
				'name'      => $category->name,
				'permalink' => get_category_link( $category->term_id ),
			);
		}

		// Get tags with permalinks
		$tags      = wp_get_post_tags( $post->ID, array( 'fields' => 'all' ) );
		$tags      = ! $tags instanceof \WP_Error ? $tags : array();
		$tags_data = array();
		foreach ( $tags as $key => $tag ) {
			$tags_data[ $key ] = array(
				'name'      => $tag->name,
				'permalink' => get_tag_link( $tag->term_id ),
			);
		}

		$post_data = array(
			'id'               => $post->ID,
			'title'            => $post->post_title,
			'excerpt'          => $post->post_excerpt,
			'comments'         => $post->comment_count,
			'date'             => $post->post_date,
			'modified'         => $post->post_modified,
			'author'           => array(
				'nickname'     => $author->user_nicename,
				'display-name' => $author->display_name,
				'full-name'    => sprintf( '%1$s %2$s', $author->first_name, $author->last_name ),
				'first-name'   => $author->first_name,
				'last-name'    => $author->last_name,
			),
			'content'          => wp_strip_all_tags( $post->post_content ),
			'featured_image'   => get_the_post_thumbnail( $post->ID, 'full' ),
			'categories'       => $categories_data,
			'tags'             => $tags_data,
			'permalink'        => get_permalink( $post->ID ),
			'gravatar'         => get_avatar( $post->post_author, 40 ),
			'author_posts_url' => get_author_posts_url( $post->post_author ),
			'formatted'        => array(
				'publish'  => wp_date( $date_replacement, strtotime( $post->post_date ) ),
				'modified' => wp_date( $date_replacement, strtotime( $post->post_modified ) ),
			),
			'custom_fields'    => Utils\Elements\CustomFields::get_fields( 'custom_fields', $post->ID ),
			'acf_fields'       => Utils\Elements\CustomFields::get_fields( 'acf_fields', $post->ID ),
		);

		/**
		 * Filters the post data for the frontend.
		 *
		 * This filter allows you to modify or extend the post data that will be
		 * available in the frontend for each rendered post.
		 *
		 * @since 3.1.4
		 *
		 * @param array   $post_data The prepared post data.
		 * @param WP_Post $post      The current post object.
		 * @param WP_User $author    The post author object.
		 */
		return apply_filters( 'divi_squad_prepare_post_data', $post_data, $post, $author );
	}

	/**
	 * Render the pagination or load more button.
	 *
	 * @param WP_Query                                  $post_query The WP_Query object.
	 * @param array                                     $attrs The module attributes.
	 * @param string|array|null                         $content The content being processed.
	 * @param ET_Builder_Module_Helper_MultiViewOptions $multi_view The multiview object instance.
	 *
	 * @return void
	 */
	protected static function squad_maybe_render_pagination( $post_query, $attrs, $content = null, $multi_view = null ) {
		// Identify the current page state.
		$is_divi_builder = isset( $content ) && is_array( $content );

		// Set the default values.
		$is_rest_query  = ! empty( $attrs['is_rest_query'] ) ? $attrs['is_rest_query'] : 'off';
		$load_more      = ! empty( $attrs['load_more__enable'] ) ? $attrs['load_more__enable'] : 'off';
		$posts_per_page = ! empty( $attrs['list_post_count'] ) ? absint( $attrs['list_post_count'] ) : 10;

		// Verify the ability of load more of pagination.
		$is_posts_exists = $post_query->found_posts > $posts_per_page;

		if ( ! $is_divi_builder && $is_posts_exists && 'off' === $is_rest_query && 'on' === $load_more ) {
			$button_text = $multi_view->render_element(
				array(
					'tag'            => 'span',
					'content'        => '{{load_more_button_text}}',
					'attrs'          => array(
						'class' => 'button-text',
					),
					'hover_selector' => '%%order_class%%.disq_post_grid div .squad-load-more-button-wrapper .squad-load-more-button',
				)
			);

			if ( '' !== $button_text ) {
				$icon_element_html  = '';
				$icon_element       = '';
				$icon_wrapper_class = 'squad-icon-wrapper';
				$button_classes     = 'squad-load-more-button et_pb_with_background';
				$button_icon_hover  = isset( $attrs['load_more_button_icon_on_hover'] ) ? $attrs['load_more_button_icon_on_hover'] : 'off';
				$animation__enable  = isset( $attrs['load_more_button_hover_animation__enable'] ) ? $attrs['load_more_button_hover_animation__enable'] : 'off';
				$animation_type     = isset( $attrs['load_more_button_hover_animation_type'] ) ? $attrs['load_more_button_hover_animation_type'] : 'fill';
				$button_icon_type   = isset( $attrs['load_more_button_icon_type'] ) ? $attrs['load_more_button_icon_type'] : 'icon';

				if ( 'on' === $animation__enable ) {
					$button_classes .= " $animation_type";
				}

				if ( 'icon' === $button_icon_type ) {
					$icon_element = $multi_view->render_element(
						array(
							'content'        => '{{load_more_button_icon}}',
							'attrs'          => array(
								'class' => 'et-pb-icon squad-button-icon',
							),
							'hover_selector' => '%%order_class%%.disq_post_grid div .squad-load-more-button-wrapper .squad-load-more-button',
						)
					);
				}

				if ( 'image' === $button_icon_type ) {
					$image_classes          = 'squad-load-more-button-image et_pb_image_wrap';
					$image_attachment_class = et_pb_media_options()->get_image_attachment_class( $attrs, 'load_more_button_image' );

					if ( ! empty( $image_attachment_class ) ) {
						$image_classes .= " $image_attachment_class";
					}

					$icon_element = $multi_view->render_element(
						array(
							'tag'            => 'img',
							'attrs'          => array(
								'src'   => '{{load_more_button_image}}',
								'class' => $image_classes,
								'alt'   => '',
							),
							'required'       => 'load_more_button_image',
							'hover_selector' => '%%order_class%%.disq_post_grid div .squad-load-more-button-wrapper .squad-load-more-button',
						)
					);
				}

				if ( ( 'none' !== $button_icon_type ) && ! empty( $icon_element ) ) {
					if ( 'on' === $button_icon_hover ) {
						$icon_wrapper_class .= ' show-on-hover';
					}

					$icon_element_html = sprintf(
						'<span class="%1$s"><span class="icon-element">%2$s</span></span>',
						esc_attr( $icon_wrapper_class ),
						wp_kses_post( $icon_element )
					);
				}

				// Enqueue associated script.
				wp_enqueue_script( 'squad-module-post-grid' );

				// Load more options.
				$button_options = array(
					'endpoint_url'   => 'squad-modules-for-divi/v1/module/post-grid/load-more',
					'posts_per_page' => $posts_per_page,
					'total_posts'    => $post_query->found_posts,
					'total_pages'    => $post_query->max_num_pages,
				);
				$query_options  = array(
					'query_args' => self::squad_get_client_query_args( $attrs ),
					'content'    => $content,
				);

				print sprintf(
					'<div class="squad-load-more-button-wrapper" data-options=\'%4$s\'><script type="application/json" style="display: none">%5$s</script><div class="%3$s">%1$s%2$s</div></div>',
					wp_kses_post( $button_text ),
					wp_kses_post( $icon_element_html ),
					esc_attr( $button_classes ),
					wp_json_encode( $button_options ),
					wp_json_encode( $query_options )
				);
			}
		}
	}

	/**
	 * Render the pagination or load more button.
	 *
	 * @param WP_Query                                  $post_query The WP_Query object.
	 * @param array                                     $attrs The module attributes.
	 * @param string|array|null                         $content The content being processed.
	 * @param ET_Builder_Module_Helper_MultiViewOptions $multi_view The multiview object instance.
	 *
	 * @return void
	 */
	protected static function squad_maybe_render_load_more_button( $post_query, $attrs, $content = null, $multi_view = null ) {
		// Identify the current page state.
		$is_divi_builder = isset( $content ) && is_array( $content );

		// Set the default values.
		$is_rest_query  = ! empty( $attrs['is_rest_query'] ) ? $attrs['is_rest_query'] : 'off';
		$pagination     = ! empty( $attrs['pagination__enable'] ) ? $attrs['pagination__enable'] : 'off';
		$posts_per_page = ! empty( $attrs['list_post_count'] ) ? absint( $attrs['list_post_count'] ) : 10;

		// Verify the ability of load more of pagination.
		$is_posts_exists = $post_query->found_posts > $posts_per_page;

		if ( ! $is_divi_builder && $is_posts_exists && 'off' === $is_rest_query && 'on' === $pagination ) {
			$prev_text         = ''; // &#x3c;
			$next_text         = ''; // &#x3d;
			$icon_only__enable = ! empty( $attrs['pagination_icon_only__enable'] ) ? $attrs['pagination_icon_only__enable'] : 'off';
			$numbers__enable   = ! empty( $attrs['pagination_numbers__enable'] ) ? $attrs['pagination_numbers__enable'] : 'off';
			$old_entries_text  = ! empty( $attrs['pagination_old_entries_text'] ) ? esc_html( $attrs['pagination_old_entries_text'] ) : __( 'Old Entries', 'squad-modules-for-divi' );
			$next_entries_text = ! empty( $attrs['pagination_next_entries_text'] ) ? esc_html( $attrs['pagination_next_entries_text'] ) : __( 'Next Entries', 'squad-modules-for-divi' );

			// Set icon for pagination prev element.
			$prev_text .= $multi_view->render_element(
				array(
					'content'        => '{{pagination_old_entries_icon}}',
					'attrs'          => array(
						'class' => 'et-pb-icon squad-pagination_old_entries-icon',
					),
					'custom_props'   => $attrs,
					'hover_selector' => '%%order_class%%.disq_post_grid div .squad-pagination .pagination-entries.prev',
				)
			);

			// Set text for pagination prev and next element.
			if ( 'on' !== $icon_only__enable ) {
				$prev_text .= sprintf( '<span class="entries-text">%1$s</span>', esc_html( $old_entries_text ) );
				$next_text .= sprintf( '<span class="entries-text">%1$s</span>', esc_html( $next_entries_text ) );
			}

			// Set icon for pagination next element.
			$next_text .= $multi_view->render_element(
				array(
					'content'        => '{{pagination_next_entries_icon}}',
					'attrs'          => array(
						'class' => 'et-pb-icon squad-pagination_next_entries-icon',
					),
					'custom_props'   => $attrs,
					'hover_selector' => '%%order_class%%.disq_post_grid div .squad-pagination .pagination-entries.next',
				)
			);

			// Collect all links for pagination.
			$paginate_links = paginate_links(
				array(
					'format'    => '?paged=%#%',
					'current'   => max( 1, get_query_var( 'paged' ) ),
					'total'     => $post_query->max_num_pages,
					'prev_text' => $prev_text,
					'next_text' => $next_text,
					'type'      => 'array',
				)
			);

			if ( isset( $paginate_links ) && count( $paginate_links ) ) {
				print '<div class="squad-pagination clearfix">';
				$is_prev_found  = false;
				$is_next_found  = false;
				$first_paginate = array_shift( $paginate_links );
				$last_paginate  = array_pop( $paginate_links );

				$paginate_prev_text = '';
				$paginate_next_text = '';

				// Update class name for the fist paginate link.
				if ( false !== strpos( $first_paginate, 'prev' ) ) {
					$is_prev_found      = true;
					$paginate_prev_text = str_replace( 'page-numbers', 'pagination-entries', $first_paginate );
				}

				// Update class name for the last paginate link.
				if ( false !== strpos( $last_paginate, 'next' ) ) {
					$is_next_found      = true;
					$paginate_next_text = str_replace( 'page-numbers', 'pagination-entries', $last_paginate );
				}

				// Show the fist paginate link.
				if ( $is_prev_found ) {
					print wp_kses_post( $paginate_prev_text );
				}

				// Show the last paginated numbers.
				if ( ( 'on' === $numbers__enable ) && ( false === $is_prev_found || false === $is_next_found || count( $paginate_links ) ) ) {
					print '<div class="pagination-numbers">';
					if ( false === $is_prev_found ) {
						print wp_kses_post( $first_paginate );
					}
					if ( count( $paginate_links ) ) {
						foreach ( $paginate_links as $paginate_link ) {
							print wp_kses_post( $paginate_link );
						}
					}
					if ( false === $is_next_found ) {
						print wp_kses_post( $last_paginate );
					}
					print '</div>';
				}

				// Show the last paginate link.
				if ( $is_next_found ) {
					print wp_kses_post( $paginate_next_text );
				}

				print '</div>';
			}
		}
	}

	/**
	 * Render icon which on is active.
	 *
	 * @param array $attrs List of attributes.
	 *
	 * @return string
	 */
	protected function squad_render_element_icon( $attrs ) {
		$wrapper_classes = array( 'squad-element-icon-wrapper' );

		if ( isset( $attrs['element_icon_on_hover'] ) && 'on' === $attrs['element_icon_on_hover'] ) {
			$wrapper_classes[] = 'show-on-hover';
		}

		return sprintf(
			'<span class="%1$s"><span class="icon-element">%2$s%3$s%4$s</span></span>',
			implode( ' ', $wrapper_classes ),
			wp_kses_post( $this->squad_render_element_font_icon( $attrs ) ),
			wp_kses_post( $this->squad_render_element_icon_image( $attrs ) ),
			wp_kses_post( $this->squad_render_element_icon_text( $attrs ) )
		);
	}

	/**
	 * Render icon.
	 *
	 * @param array $attrs List of unprocessed attributes.
	 *
	 * @return string
	 */
	protected function squad_render_element_font_icon( $attrs ) {
		if ( 'icon' === $attrs['element_icon_type'] ) {
			$multi_view   = et_pb_multi_view_options( $this );
			$element_type = isset( $attrs['element'] ) ? $attrs['element'] : 'none';
			$icon_classes = array( 'et-pb-icon', 'squad-element-icon' );

			return $multi_view->render_element(
				array(
					'custom_props'   => $attrs,
					'content'        => '{{element_icon}}',
					'attrs'          => array(
						'class' => implode( ' ', $icon_classes ),
					),
					'hover_selector' => "$this->main_css_element div .post-elements .squad-post-element.squad-element_$element_type",
				)
			);
		}

		return '';
	}

	/**
	 * Render image.
	 *
	 * @param array $attrs List of unprocessed attributes.
	 *
	 * @return string
	 */
	protected function squad_render_element_icon_image( $attrs ) {
		if ( 'image' === $attrs['element_icon_type'] ) {
			$multi_view    = et_pb_multi_view_options( $this );
			$alt_text      = $this->_esc_attr( 'alt' );
			$title_text    = $this->_esc_attr( 'title_text' );
			$element_type  = isset( $attrs['element'] ) ? $attrs['element'] : 'none';
			$image_classes = array( 'disq_list_image', 'et_pb_image_wrap' );

			$image_attachment_class = et_pb_media_options()->get_image_attachment_class( $this->props, 'element_image' );

			if ( ! empty( $image_attachment_class ) ) {
				$image_classes[] = esc_attr( $image_attachment_class );
			}

			return $multi_view->render_element(
				array(
					'custom_props'   => $attrs,
					'tag'            => 'img',
					'attrs'          => array(
						'src'   => '{{element_image}}',
						'class' => implode( ' ', $image_classes ),
						'alt'   => $alt_text,
						'title' => $title_text,
					),
					'required'       => 'element_image',
					'hover_selector' => "$this->main_css_element div .post-elements .squad-post-element.squad-element_$element_type",
				)
			);
		}

		return '';
	}

	/**
	 * Render image.
	 *
	 * @param array $attrs List of unprocessed attributes.
	 *
	 * @return string
	 */
	protected function squad_render_element_icon_text( $attrs ) {
		if ( 'text' === $attrs['element_icon_type'] ) {
			$multi_view        = et_pb_multi_view_options( $this );
			$element_type      = isset( $attrs['element'] ) ? $attrs['element'] : 'none';
			$icon_text_classes = array( 'squad-element-icon-text' );

			return $multi_view->render_element(
				array(
					'custom_props'   => $attrs,
					'content'        => '{{element_icon_text}}',
					'attrs'          => array(
						'class' => implode( ' ', $icon_text_classes ),
					),
					'hover_selector' => "$this->main_css_element div .post-elements .squad-post-element.squad-element_$element_type",
				)
			);
		}

		return '';
	}

	/**
	 * Render element body.
	 *
	 * @since 1.0.0
	 *
	 * @param array         $attrs List of attributes.
	 * @param false|WP_Post $post  The current post object.
	 *
	 * @return string
	 */
	protected function squad_render_post_element_body( $attrs, $post ) {
		$element    = isset( $attrs['element'] ) ? $attrs['element'] : 'none';
		$class_name = sprintf( 'et_pb_with_background squad-post-element squad-post-element__%s', esc_attr( $element ) );
		$post_id    = $post->ID;

		$output = '';

		switch ( $element ) {
			case 'title':
				$output = $this->squad_render_title_element( $attrs, $post, $class_name );
				break;
			case 'image':
			case 'featured_image':
				$output = $this->squad_render_image_element( $attrs, $post, $class_name );
				break;
			case 'content':
				$output = $this->squad_render_content_element( $attrs, $post, $class_name );
				break;
			case 'author':
				$output = $this->squad_render_author_element( $attrs, $post, $class_name );
				break;
			case 'gravatar':
				$output = $this->squad_render_gravatar_element( $attrs, $post, $class_name );
				break;
			case 'date':
				$output = $this->squad_render_date_element( $attrs, $post, $class_name );
				break;
			case 'read_more':
				$output = $this->squad_render_read_more_element( $attrs, $post_id, $class_name );
				break;
			case 'comments':
				$output = $this->squad_render_comments_element( $attrs, $post, $class_name );
				break;
			case 'categories':
				$output = $this->squad_render_categories_element( $attrs, $post_id, $class_name );
				break;
			case 'tags':
				$output = $this->squad_render_tags_element( $attrs, $post_id, $class_name );
				break;
			case 'divider':
				$output = $this->squad_render_divider_element( $attrs, $class_name );
				break;
			case 'custom_text':
				$output = $this->squad_render_custom_text_element( $attrs, $class_name );
				break;
			case 'custom_field':
				$output = $this->squad_render_custom_field_element( $attrs, $post_id, $class_name );
				break;
			case 'advanced_custom_field':
				$output = $this->squad_render_advanced_custom_field_element( $attrs, $post_id, $class_name );
				break;
		}

		/**
		 * Filters the rendered post element body HTML.
		 *
		 * Allows modifying the rendered HTML output for a post element in the grid.
		 *
		 * @since 3.1.8
		 *
		 * @param string  $output     The rendered HTML output for the element.
		 * @param int     $post_id    The post ID.
		 * @param array   $attrs      The element attributes.
		 * @param string  $class_name The element class name.
		 * @param string  $element    The element type being rendered.
		 */
		return apply_filters( 'divi_squad_post_element_body', $output, $post_id, $attrs, $class_name, $element );
	}

	/**
	 * Render title element.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $attrs      List of attributes.
	 * @param WP_Post $post      The post object.
	 * @param string $class_name The class name for the element.
	 *
	 * @return string
	 */
	protected function squad_render_title_element( $attrs, $post, $class_name ) {
		$post_title = $post->post_title;
		if ( empty( $post_title ) ) {
			return '';
		}

		$title_tag = isset( $attrs['element_title_tag'] ) ? sanitize_text_field( $attrs['element_title_tag'] ) : 'span';
		$content   = sprintf( '<span class="element-text">%s</span>', ucfirst( $post_title ) );

		$title_icon = '';
		if ( isset( $attrs['element_title_icon__enable'] ) && 'on' === $attrs['element_title_icon__enable'] && ! empty( $attrs['element_title_icon'] ) ) {
			$title_icon = $this->squad_render_post_title_font_icon( $attrs );
		}

		$output = sprintf( '<%1$s class="element-text">%2$s</%1$s>%3$s', esc_attr( $title_tag ), $content, wp_kses_post( $title_icon ) );

		if ( isset( $attrs['link_to_post__enable'] ) && 'on' === $attrs['link_to_post__enable'] ) {
			$output = sprintf( '<a href="%s" title="%s">%s</a>', esc_url( get_permalink( $post->ID ) ), esc_attr( $post_title ), $output );
		}

		return sprintf( '<div class="%s">%s</div>', esc_attr( $class_name ), $output );
	}

	/**
	 * Render image element.
	 *
	 * @since 1.0.0
	 * @since 3.1.8 Added the ability to link to the post.
	 *
	 * @param array   $attrs      List of attributes.
	 * @param WP_Post $post       The post object.
	 * @param string  $class_name The class name for the element.
	 *
	 * @return string
	 */
	protected function squad_render_image_element( $attrs, $post, $class_name ) {
		$post_image = get_the_post_thumbnail( $post->ID, 'full' );
		if ( empty( $post_image ) ) {
			return '';
		}

		$output = $post_image;

		// Check if linking to post is enabled
		if ( isset( $attrs['link_to_post__enable'] ) && 'on' === $attrs['link_to_post__enable'] ) {
			$permalink  = get_permalink( $post->ID );
			$post_title = get_the_title( $post->ID );
			if ( $permalink ) {
				$output = sprintf(
					'<a href="%1$s" title="%2$s" class="image-link">%3$s</a>',
					esc_url( $permalink ),
					esc_attr( $post_title ),
					$post_image
				);
			}
		}

		return sprintf(
			'<div class="%1$s">%2$s</div>',
			esc_attr( $class_name ),
			wp_kses_post( $output )
		);
	}

	/**
	 * Render content element.
	 *
	 * @since 1.0.0
	 *
	 * @param array     $attrs      List of attributes.
	 * @param WP_Post   $post       The post object.
	 * @param string    $class_name The class name for the element.
	 *
	 * @return string
	 */
	protected function squad_render_content_element( $attrs, $post, $class_name ) {
		$post_excerpt__enable = isset( $attrs['element_excerpt__enable'] ) ? sanitize_text_field( $attrs['element_excerpt__enable'] ) : 'off';
		$post_content         = ( 'on' === $post_excerpt__enable ) ? $post->post_excerpt : wp_strip_all_tags( $post->post_content );

		if ( empty( $post_content ) ) {
			return '';
		}

		$post_content_length__enable = isset( $attrs['element_ex_con_length__enable'] ) ? sanitize_text_field( $attrs['element_ex_con_length__enable'] ) : 'off';
		$post_content_length         = isset( $attrs['element_ex_con_length'] ) ? absint( $attrs['element_ex_con_length'] ) : 20;

		if ( 'on' === $post_content_length__enable ) {
			$post_content = $this->squad_truncate_content( $post_content, $post_content_length );
		}

		return sprintf(
			'<div class="%s"><span>%s</span></div>',
			esc_attr( $class_name ),
			wp_kses_post( $post_content )
		);
	}

	/**
	 * Truncate content to a specified number of words, supporting an expanded set of special characters.
	 *
	 * @since 1.0.0
	 *
	 * @param string $content The content to truncate.
	 * @param int    $length  The number of words to keep.
	 *
	 * @return string
	 */
	protected function squad_truncate_content( $content, $length ) {
		$character_map = LRCart::get_character_map();

		/**
		 * Filter the character map for the post content.
		 *
		 * @since 3.1.4
		 *
		 * @param string $character_map The character map.
		 *
		 * @return string
		 */
		$character_map = apply_filters( 'divi_squad_post_query_content_character_map', $character_map );

		// Use Str::word_count with the character map
		$words = Str::word_count( $content, 2, $character_map );

		if ( count( $words ) > $length ) {
			$truncated_words   = array_slice( $words, 0, $length, true );
			$last_word_pos     = array_key_last( $truncated_words );
			$truncated_content = mb_substr( $content, 0, $last_word_pos + mb_strlen( $truncated_words[ $last_word_pos ] ) );
			return $truncated_content . '...';
		}

		return $content;
	}

	/**
	 * Render author element.
	 *
	 * @since 1.0.0
	 *
	 * @param array     $attrs      List of attributes.
	 * @param WP_Post   $post       The post object.
	 * @param string    $class_name The class name for the element.
	 *
	 * @return string
	 */
	protected function squad_render_author_element( $attrs, $post, $class_name ) {
		$author = get_userdata( absint( $post->post_author ) );
		if ( ! $author ) {
			return '';
		}

		$default_name_type = 'nickname';
		$author_name_type  = isset( $attrs['element_author_name_type'] ) ? sanitize_text_field( $attrs['element_author_name_type'] ) : $default_name_type;

		$content = $this->squad_get_author_name( $author, $author_name_type );

		if ( isset( $attrs['link_to_author__enable'] ) && 'on' === $attrs['link_to_author__enable'] ) {
			$content = sprintf(
				'<a href="%s" title="%s">%s</a>',
				esc_url( get_author_posts_url( $author->ID ) ),
				// Translators: %s is the author name.
				esc_attr( sprintf( esc_html__( 'Posts by %s', 'squad-modules-for-divi' ), $content ) ),
				esc_html( $content )
			);
		} else {
			$content = esc_html( $content );
		}

		return sprintf(
			'<div class="%s"><span>%s</span></div>',
			esc_attr( $class_name ),
			$content
		);
	}

	/**
	 * Get author name based on the specified type.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_User $author           The author object.
	 * @param string  $author_name_type The type of author name to return.
	 *
	 * @return string
	 */
	protected function squad_get_author_name( $author, $author_name_type ) {
		switch ( $author_name_type ) {
			case 'nickname':
				return $author->nickname;
			case 'first-name':
				return $author->first_name;
			case 'last-name':
				return $author->last_name;
			case 'full-name':
				return sprintf( '%s %s', $author->first_name, $author->last_name );
			default:
				return $author->display_name;
		}
	}

	/**
	 * Render gravatar element.
	 *
	 * @since 1.0.0
	 *
	 * @param array     $attrs      List of attributes.
	 * @param WP_Post   $post       The post object.
	 * @param string    $class_name The class name for the element.
	 *
	 * @return string
	 */
	protected function squad_render_gravatar_element( $attrs, $post, $class_name ) {
		$gravatar_size = isset( $attrs['element_gravatar_size'] ) ? absint( $attrs['element_gravatar_size'] ) : 40;
		$gravatar      = get_avatar( $post->post_author, $gravatar_size );

		if ( empty( $gravatar ) ) {
			return '';
		}

		$output = $gravatar;

		// Check if linking to gravatar is enabled
		if ( isset( $attrs['link_to_gravatar__enable'] ) && 'on' === $attrs['link_to_gravatar__enable'] ) {
			$author = get_userdata( $post->post_author );
			if ( $author ) {
				$author_url  = get_author_posts_url( $author->ID );
				$author_name = $author->display_name;
				$output      = sprintf(
					'<a href="%s" title="%s">%s</a>',
					esc_url( $author_url ),
					// Translators: %s is the author name.
					esc_attr( sprintf( esc_html__( 'Posts by %s', 'squad-modules-for-divi' ), $author_name ) ),
					$gravatar
				);
			}
		}

		return sprintf(
			'<div class="%s">%s</div>',
			esc_attr( $class_name ),
			wp_kses_post( $output )
		);
	}

	/**
	 * Render date element.
	 *
	 * @since 1.0.0
	 *
	 * @param array     $attrs      List of attributes.
	 * @param WP_Post   $post       The post object.
	 * @param string    $class_name The class name for the element.
	 *
	 * @return string
	 */
	protected function squad_render_date_element( $attrs, $post, $class_name ) {
		$element_date_type = isset( $attrs['element_date_type'] ) ? sanitize_text_field( $attrs['element_date_type'] ) : 'publish';
		$date_format       = isset( $attrs['parent_prop_date_format'] ) ? json_decode( sanitize_text_field( $attrs['parent_prop_date_format'] ), true ) : array( 'date_format' => 'M j, Y' );
		$date_format       = isset( $date_format['date_format'] ) ? $date_format['date_format'] : 'M j, Y';

		$date          = 'modified' === $element_date_type ? $post->post_modified : $post->post_date;
		$date_modified = str_replace( '\\\\', '\\', $date_format );

		return sprintf(
			'<div class="%s"><time datetime="%s">%s</time></div>',
			esc_attr( $class_name ),
			esc_attr( $date ),
			esc_html( wp_date( $date_modified, strtotime( $date ) ) )
		);
	}

	/**
	 * Render read more element.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $attrs      List of attributes.
	 * @param int    $post_id    The post ID.
	 * @param string $class_name The class name for the element.
	 *
	 * @return string
	 */
	protected function squad_render_read_more_element( $attrs, $post_id, $class_name ) {
		$permalink_url   = get_permalink( $post_id );
		$read_more_title = esc_html__( 'Read the post', 'squad-modules-for-divi' );
		$default_text    = esc_html__( 'Read More', 'squad-modules-for-divi' );
		$read_more_text  = isset( $attrs['element_read_more_text'] ) ? sanitize_text_field( $attrs['element_read_more_text'] ) : $default_text;

		return sprintf(
			'<div class="%s"><a href="%s" title="%s">%s</a></div>',
			esc_attr( $class_name ),
			esc_url( $permalink_url ),
			esc_attr( $read_more_title ),
			esc_html( $read_more_text )
		);
	}

	/**
	 * Render comments element.
	 *
	 * @since 1.0.0
	 *
	 * @param array     $attrs      List of attributes.
	 * @param WP_Post   $post       The post object.
	 * @param string    $class_name The class name for the element.
	 *
	 * @return string
	 */
	protected function squad_render_comments_element( $attrs, $post, $class_name ) {
		$comment_before_text = isset( $attrs['element_comment_before'] ) ? sanitize_text_field( $attrs['element_comment_before'] ) : '';
		$comment_after_text  = isset( $attrs['element_comments_after'] ) ? sanitize_text_field( $attrs['element_comments_after'] ) : '';

		return sprintf(
			'<div class="%s"><span class="element-text">%s%s%s</span></div>',
			esc_attr( $class_name ),
			esc_html( $comment_before_text ),
			esc_html( $post->comment_count ),
			esc_html( $comment_after_text )
		);
	}

	/**
	 * Render categories element.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $attrs      List of attributes.
	 * @param int    $post_id    The post ID.
	 * @param string $class_name The class name for the element.
	 *
	 * @return string
	 */
	protected function squad_render_categories_element( $attrs, $post_id, $class_name ) {
		$categories = wp_get_post_categories( $post_id, array( 'fields' => 'all' ) );
		if ( empty( $categories ) ) {
			return '';
		}

		$categories_separator = isset( $attrs['element_categories_sepa'] ) ? $attrs['element_categories_sepa'] : '';
		$link_enabled         = isset( $attrs['link_to_categories__enable'] ) && 'on' === $attrs['link_to_categories__enable'];

		$category_links = array();
		foreach ( $categories as $category ) {
			if ( $link_enabled ) {
				$category_links[] = sprintf(
					'<a href="%s" title="%s">%s</a>',
					esc_url( get_category_link( $category->term_id ) ),
					// Translators: %s is the category name.
					esc_attr( sprintf( esc_html__( 'View all posts in %s', 'squad-modules-for-divi' ), $category->name ) ),
					esc_html( $category->name )
				);
			} else {
				$category_links[] = esc_html( $category->name );
			}
		}

		$content = implode( "$categories_separator ", $category_links );

		return sprintf(
			'<div class="%s"><span class="element-text">%s</span></div>',
			esc_attr( $class_name ),
			$content
		);
	}

	/**
	 * Render tags element.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $attrs      List of attributes.
	 * @param int    $post_id    The post ID.
	 * @param string $class_name The class name for the element.
	 *
	 * @return string
	 */
	protected function squad_render_tags_element( $attrs, $post_id, $class_name ) {
		$tags = wp_get_post_tags( $post_id, array( 'fields' => 'all' ) );
		if ( empty( $tags ) ) {
			return '';
		}

		$tags_separator = isset( $attrs['element_tags_sepa'] ) ? $attrs['element_tags_sepa'] : '';
		$link_enabled   = isset( $attrs['link_to_tags__enable'] ) && 'on' === $attrs['link_to_tags__enable'];

		$tag_links = array();
		foreach ( $tags as $tag ) {
			if ( $link_enabled ) {
				$tag_links[] = sprintf(
					'<a href="%s" title="%s">%s</a>',
					esc_url( get_tag_link( $tag->term_id ) ),
					// Translators: %s is the tag name.
					esc_attr( sprintf( esc_html__( 'View all posts tagged %s', 'squad-modules-for-divi' ), $tag->name ) ),
					esc_html( $tag->name )
				);
			} else {
				$tag_links[] = esc_html( $tag->name );
			}
		}

		$content = implode( "$tags_separator ", $tag_links );

		return sprintf(
			'<div class="%s"><span class="element-text">%s</span></div>',
			esc_attr( $class_name ),
			$content
		);
	}

	/**
	 * Render divider element.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $attrs      List of attributes.
	 * @param string $class_name The class name for the element.
	 *
	 * @return string
	 */
	protected function squad_render_divider_element( $attrs, $class_name ) {
		if ( isset( $attrs['show_divider'] ) && 'on' === $attrs['show_divider'] ) {
			$divider_position = isset( $attrs['divider_position'] ) ? sanitize_text_field( $attrs['divider_position'] ) : 'bottom';
			$divider_classes  = implode( ' ', array( 'divider-element', $divider_position ) );

			return sprintf(
				'<div class="%s"><span class="%s"></span></div>',
				esc_attr( $class_name ),
				esc_attr( $divider_classes )
			);
		}

		return '';
	}

	/**
	 * Render custom text element.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $attrs      List of attributes.
	 * @param string $class_name The class name for the element.
	 *
	 * @return string
	 */
	protected function squad_render_custom_text_element( $attrs, $class_name ) {
		if ( empty( $attrs['element_custom_text'] ) ) {
			return '';
		}

		$custom_text = sanitize_text_field( $attrs['element_custom_text'] );

		return sprintf(
			'<div class="%s"><span class="element-text">%s</span></div>',
			esc_attr( $class_name ),
			esc_html( $custom_text )
		);
	}

	/**
	 * Render custom field element.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $attrs      List of attributes.
	 * @param int    $post_id    The post ID.
	 * @param string $class_name The class name for the element.
	 *
	 * @return string
	 */
	protected function squad_render_custom_field_element( $attrs, $post_id, $class_name ) {
		if ( empty( $attrs['element_custom_field_post'] ) ) {
			return '';
		}

		$custom_field_key = $attrs['element_custom_field_post'];
		$custom_field     = Utils\Elements\CustomFields::get( 'custom_fields' );
		if ( ! $custom_field->has_field( $post_id, $custom_field_key ) ) {
			return '';
		}

		$custom_field_value = $custom_field->get_field_value( $post_id, $custom_field_key );
		if ( empty( $custom_field_value ) ) {
			return '';
		}

		$comment_before_text = isset( $attrs['element_custom_field_before'] ) ? sanitize_text_field( $attrs['element_custom_field_before'] ) : '';
		$comment_after_text  = isset( $attrs['element_custom_field_after'] ) ? sanitize_text_field( $attrs['element_custom_field_after'] ) : '';

		return sprintf(
			'<div class="%s"><span class="element-text">%s%s%s</span></div>',
			esc_attr( $class_name ),
			esc_html( $comment_before_text ),
			esc_html( $custom_field_value ),
			esc_html( $comment_after_text )
		);
	}

	/**
	 * Render advanced custom field element.
	 *
	 * @since 1.0.0
	 *
	 * @param array $attrs      List of attributes.
	 * @param int   $post_id    The post ID.
	 * @param string $class_name The class name for the element.
	 *
	 * @return string
	 */
	protected function squad_render_advanced_custom_field_element( $attrs, $post_id, $class_name ) {
		if ( empty( $attrs['element_advanced_custom_field_post'] ) ) {
			return '';
		}

		$acf_field_key  = $attrs['element_advanced_custom_field_post'];
		$acf_fields     = Utils\Elements\CustomFields::get( 'acf_fields' );
		$acf_field_type = isset( $attrs['element_advanced_custom_field_type'] ) ? sanitize_text_field( $attrs['element_advanced_custom_field_type'] ) : 'text';

		// Add new body class when user set advanced custom field image class.
		$class_name .= sprintf( ' advanced_custom_field__%1$s', $acf_field_type );

		if ( ! $acf_fields->has_field( $post_id, $acf_field_key ) ) {
			return '';
		}

		$acf_field_value = $acf_fields->get_field_value( $post_id, $acf_field_key );
		if ( empty( $acf_field_value ) ) {
			return '';
		}

		$acf_field_value = $this->squad_format_acf_field_value( $attrs, $acf_field_value, $acf_field_type );

		$acf_before_text = '';
		$acf_after_text  = '';
		if ( 'text' === $acf_field_type ) {
			$acf_before_text = isset( $attrs['element_advanced_custom_field_before'] ) ? sanitize_text_field( $attrs['element_advanced_custom_field_before'] ) : '';
			$acf_after_text  = isset( $attrs['element_advanced_custom_field_after'] ) ? sanitize_text_field( $attrs['element_advanced_custom_field_after'] ) : '';
		}

		return sprintf(
			'<div class="%s"><span class="element-text">%s%s%s</span></div>',
			esc_attr( $class_name ),
			esc_html( $acf_before_text ),
			wp_kses_post( $acf_field_value ),
			esc_html( $acf_after_text )
		);
	}

	/**
	 * Format ACF field value based on field type.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $attrs           List of attributes.
	 * @param mixed  $acf_field_value The ACF field value.
	 * @param string $acf_field_type  The ACF field type.
	 *
	 * @return string
	 */
	protected function squad_format_acf_field_value( $attrs, $acf_field_value, $acf_field_type ) {
		switch ( $acf_field_type ) {
			case 'email':
				$acf_email_text = isset( $attrs['element_advanced_custom_field_email_text'] ) ? sanitize_text_field( $attrs['element_advanced_custom_field_email_text'] ) : $acf_field_value;
				return sprintf( '<a href="mailto:%s" target="_blank">%s</a>', esc_attr( $acf_field_value ), esc_html( $acf_email_text ) );

			case 'url':
				$acf_url_text   = isset( $attrs['element_advanced_custom_field_url_text'] ) ? sanitize_text_field( $attrs['element_advanced_custom_field_url_text'] ) : esc_html__( 'Visit the link', 'squad-modules-for-divi' );
				$acf_url_target = isset( $attrs['element_advanced_custom_field_url_target'] ) ? sanitize_text_field( $attrs['element_advanced_custom_field_url_target'] ) : '_self';
				return sprintf( '<a href="%s" target="%s">%s</a>', esc_url( $acf_field_value ), esc_attr( $acf_url_target ), esc_html( $acf_url_text ) );

			case 'image':
				$acf_image_width = isset( $attrs['element_advanced_custom_field_image_width'] ) ? sanitize_text_field( $attrs['element_advanced_custom_field_image_width'] ) : '100px';
				$acf_image_sizes = array( absint( $acf_image_width ), 'full' );
				$acf_image_attr  = array( 'width' => $acf_image_width );
				return wp_get_attachment_image( (int) $acf_field_value, $acf_image_sizes, false, $acf_image_attr );

			default:
				return $acf_field_value;
		}
	}

	/**
	 * Render post name icon.
	 *
	 * @param array $attrs List of attributes.
	 *
	 * @return string
	 */
	protected function squad_render_post_title_font_icon( $attrs ) {
		$multi_view   = et_pb_multi_view_options( $this );
		$element_type = isset( $attrs['element'] ) ? $attrs['element'] : 'none';
		$icon_classes = array( 'et-pb-icon', 'squad-element_title-icon' );

		if ( 'on' !== $attrs['element_title_icon_show_on_hover'] ) {
			$icon_classes[] = 'always_show';
		}

		return $multi_view->render_element(
			array(
				'custom_props'   => $attrs,
				'content'        => '{{element_title_icon}}',
				'attrs'          => array(
					'class' => implode( ' ', $icon_classes ),
				),
				'hover_selector' => "$this->main_css_element div .post-elements .squad-post-element.squad-element_$element_type",
			)
		);
	}

	/**
	 * Generate styles.
	 *
	 * @param array $attrs List of unprocessed attributes.
	 *
	 * @return void
	 */
	protected function squad_generate_all_styles( $attrs ) {
		// Fixed: the custom background doesn't work at frontend.
		$this->props = array_merge( $attrs, $this->props );

		// Post columns default, responsive.
		$this->squad_utils->generate_additional_styles(
			array(
				'field'          => 'list_number_of_columns',
				'selector'       => "$this->main_css_element .squad-post-container",
				'css_property'   => 'grid-template-columns',
				'type'           => 'grid',
				'mapping_values' => function ( $current_value ) {
					return sprintf( 'repeat( %1$s, 1fr )', $current_value );
				},
			)
		);
		// post gap.
		$this->generate_styles(
			array(
				'base_attr_name' => 'list_item_gap',
				'selector'       => "$this->main_css_element .squad-post-container",
				'css_property'   => 'gap',
				'render_slug'    => $this->slug,
				'type'           => 'input',
			)
		);

		// background with default, responsive, hover.
		et_pb_background_options()->get_background_style(
			array(
				'base_prop_name'         => 'post_wrapper_background',
				'props'                  => $this->props,
				'selector'               => "$this->main_css_element .squad-post-container .post",
				'selector_hover'         => "$this->main_css_element .squad-post-container .post:hover",
				'selector_sticky'        => "$this->main_css_element .squad-post-container .post",
				'function_name'          => $this->slug,
				'important'              => ' !important',
				'use_background_video'   => false,
				'use_background_pattern' => false,
				'use_background_mask'    => false,
				'prop_name_aliases'      => array(
					'use_post_wrapper_background_color_gradient' => 'post_wrapper_background_use_color_gradient',
					'post_wrapper_background' => 'post_wrapper_background_color',
				),
			)
		);
		et_pb_background_options()->get_background_style(
			array(
				'base_prop_name'         => 'element_wrapper_background',
				'props'                  => $this->props,
				'selector'               => "$this->main_css_element .squad-post-container .post .post-elements",
				'selector_hover'         => "$this->main_css_element .squad-post-container .post:hover .post-elements",
				'selector_sticky'        => "$this->main_css_element .squad-post-container .post .post-elements",
				'function_name'          => $this->slug,
				'important'              => ' !important',
				'use_background_video'   => false,
				'use_background_pattern' => false,
				'use_background_mask'    => false,
				'prop_name_aliases'      => array(
					'use_element_wrapper_background_color_gradient' => 'element_wrapper_background_use_color_gradient',
					'element_wrapper_background' => 'element_wrapper_background_color',
				),
			)
		);
		et_pb_background_options()->get_background_style(
			array(
				'base_prop_name'         => 'element_background',
				'props'                  => $this->props,
				'selector'               => "$this->main_css_element .squad-post-container .post .squad-post-element",
				'selector_hover'         => "$this->main_css_element .squad-post-container .post:hover .squad-post-element",
				'selector_sticky'        => "$this->main_css_element .squad-post-container .post .squad-post-element",
				'function_name'          => $this->slug,
				'use_background_video'   => false,
				'use_background_pattern' => false,
				'use_background_mask'    => false,
				'prop_name_aliases'      => array(
					'use_element_background_color_gradient' => 'element_background_use_color_gradient',
					'element_background' => 'element_background_color',
				),
			)
		);

		// text aligns with default, responsive, hover.
		$this->generate_styles(
			array(
				'base_attr_name' => 'post_text_orientation',
				'selector'       => "$this->main_css_element .squad-post-container .post",
				'hover_selector' => "$this->main_css_element .squad-post-container .post",
				'css_property'   => 'text-align',
				'render_slug'    => $this->slug,
				'type'           => 'align',
			)
		);
		$this->generate_styles(
			array(
				'base_attr_name' => 'element_text_orientation',
				'selector'       => "$this->main_css_element .squad-post-container .post .post-elements",
				'selector_hover' => "$this->main_css_element .squad-post-container .post:hover .post-elements",
				'css_property'   => 'text-align',
				'render_slug'    => $this->slug,
				'type'           => 'align',
			)
		);

		// margin, padding with default, responsive, hover.
		$this->squad_utils->generate_margin_padding_styles(
			array(
				'field'          => 'post_wrapper_margin',
				'selector'       => "$this->main_css_element .squad-post-container .post",
				'hover_selector' => "$this->main_css_element .squad-post-container .post:hover",
				'css_property'   => 'margin',
				'type'           => 'margin',
			)
		);
		$this->squad_utils->generate_margin_padding_styles(
			array(
				'field'          => 'post_wrapper_padding',
				'selector'       => "$this->main_css_element .squad-post-container .post",
				'hover_selector' => "$this->main_css_element .squad-post-container .post:hover",
				'css_property'   => 'padding',
				'type'           => 'padding',
			)
		);
		$this->squad_utils->generate_margin_padding_styles(
			array(
				'field'          => 'element_wrapper_margin',
				'selector'       => "$this->main_css_element .squad-post-container .post .post-elements",
				'hover_selector' => "$this->main_css_element .squad-post-container .post .post-elements",
				'css_property'   => 'margin',
				'type'           => 'margin',
			)
		);
		$this->squad_utils->generate_margin_padding_styles(
			array(
				'field'          => 'element_wrapper_padding',
				'selector'       => "$this->main_css_element .squad-post-container .post .post-elements",
				'hover_selector' => "$this->main_css_element .squad-post-container .post .post-elements",
				'css_property'   => 'padding',
				'type'           => 'padding',
			)
		);
		$this->squad_utils->generate_margin_padding_styles(
			array(
				'field'          => 'element_margin',
				'selector'       => "$this->main_css_element .squad-post-container .post .squad-post-element",
				'hover_selector' => "$this->main_css_element .squad-post-container .post:hover .squad-post-element",
				'css_property'   => 'margin',
				'type'           => 'margin',
				'important'      => false,
			)
		);
		$this->squad_utils->generate_margin_padding_styles(
			array(
				'field'          => 'element_padding',
				'selector'       => "$this->main_css_element .squad-post-container .post .squad-post-element",
				'hover_selector' => "$this->main_css_element .squad-post-container .post:hover .squad-post-element",
				'css_property'   => 'padding',
				'type'           => 'padding',
				'important'      => false,
			)
		);
	}

	/**
	 * Generate styles.
	 *
	 * @param array $attrs List of unprocessed attributes.
	 *
	 * @return void
	 */
	protected function squad_generate_layout_styles( $attrs ) {
		// Fixed: the custom background doesn't work at frontend.
		$this->props = array_merge( $attrs, $this->props );

		if ( 'on' === $this->prop( 'load_more__enable', 'off' ) ) {
			// button background with default, responsive, hover.
			et_pb_background_options()->get_background_style(
				array(
					'base_prop_name'         => 'load_more_button_background',
					'props'                  => $this->props,
					'selector'               => "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button",
					'selector_hover'         => "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button:hover",
					'selector_sticky'        => "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button",
					'function_name'          => $this->slug,
					'important'              => ' !important',
					'use_background_video'   => false,
					'use_background_pattern' => false,
					'use_background_mask'    => false,
					'prop_name_aliases'      => array(
						'use_button_background_color_gradient' => 'button_background_use_color_gradient',
						'button_background' => 'button_background_color',
					),
				)
			);

			$this->generate_styles(
				array(
					'base_attr_name' => 'load_more_button_horizontal_alignment',
					'selector'       => "$this->main_css_element div .squad-load-more-button-wrapper",
					'hover_selector' => "$this->main_css_element div .squad-load-more-button-wrapper:hover",
					'css_property'   => 'justify-content',
					'render_slug'    => $this->slug,
					'type'           => 'align',
					'important'      => true,
				)
			);
			$this->generate_styles(
				array(
					'base_attr_name' => 'load_more_button_elements_alignment',
					'selector'       => "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button",
					'hover_selector' => "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button:hover",
					'css_property'   => 'justify-content',
					'render_slug'    => $this->slug,
					'type'           => 'align',
					'important'      => true,
				)
			);
			$this->generate_styles(
				array(
					'base_attr_name' => 'load_more_button_width',
					'selector'       => "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button",
					'hover_selector' => "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button:hover",
					'css_property'   => 'width',
					'render_slug'    => $this->slug,
					'type'           => 'input',
					'important'      => true,
				)
			);

			$this->generate_styles(
				array(
					'base_attr_name' => 'load_more_button_icon_placement',
					'selector'       => "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button",
					'hover_selector' => "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button:hover",
					'css_property'   => 'flex-direction',
					'render_slug'    => $this->slug,
					'type'           => 'align',
					'important'      => true,
				)
			);
			$this->generate_styles(
				array(
					'base_attr_name' => 'load_more_button_icon_gap',
					'selector'       => "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button",
					'hover'          => "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button:hover",
					'css_property'   => 'gap',
					'render_slug'    => $this->slug,
					'type'           => 'input',
					'important'      => true,
				)
			);

			if ( 'on' === $this->prop( 'load_more_spinner_show', 'off' ) ) {
				$this->generate_styles(
					array(
						'base_attr_name' => 'load_more_spinner_p_color',
						'selector'       => "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button.is-loading .spinner::after",
						'css_property'   => 'border-color',
						'render_slug'    => $this->slug,
						'type'           => 'color',
						'important'      => true,
					)
				);
				$this->generate_styles(
					array(
						'base_attr_name' => 'load_more_spinner_s_color',
						'selector'       => "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button.is-loading .spinner::before",
						'css_property'   => 'border-color',
						'render_slug'    => $this->slug,
						'type'           => 'color',
						'important'      => true,
					)
				);
				$this->generate_styles(
					array(
						'base_attr_name' => 'load_more_spinner_size',
						'selector'       => "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button.is-loading .spinner",
						'css_property'   => 'width',
						'render_slug'    => $this->slug,
						'type'           => 'input',
						'important'      => true,
					)
				);
				$this->generate_styles(
					array(
						'base_attr_name' => 'load_more_spinner_size',
						'selector'       => "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button.is-loading .spinner",
						'css_property'   => 'height',
						'render_slug'    => $this->slug,
						'type'           => 'input',
						'important'      => true,
					)
				);
			}

			// button margin with default, responsive, hover.
			$this->squad_utils->generate_margin_padding_styles(
				array(
					'field'          => 'load_more_button_icon_margin',
					'selector'       => "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button .icon-element",
					'hover_selector' => "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button:hover .icon-element",
					'css_property'   => 'margin',
					'type'           => 'margin',
				)
			);
			$this->squad_utils->generate_margin_padding_styles(
				array(
					'field'          => 'load_more_button_margin',
					'selector'       => "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button",
					'hover_selector' => "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button:hover",
					'css_property'   => 'margin',
					'type'           => 'margin',
				)
			);
			$this->squad_utils->generate_margin_padding_styles(
				array(
					'field'          => 'load_more_button_padding',
					'selector'       => "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button",
					'hover_selector' => "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button:hover",
					'css_property'   => 'padding',
					'type'           => 'padding',
				)
			);

			if ( ( 'none' !== $this->props['load_more_button_icon_type'] ) ) {

				if ( 'icon' === $this->props['load_more_button_icon_type'] ) {
					$this->generate_styles(
						array(
							'utility_arg'    => 'icon_font_family',
							'render_slug'    => $this->slug,
							'base_attr_name' => 'load_more_button_icon',
							'important'      => true,
							'selector'       => "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button .et-pb-icon",
							'processor'      => array(
								'ET_Builder_Module_Helper_Style_Processor',
								'process_extended_icon',
							),
						)
					);
					$this->generate_styles(
						array(
							'base_attr_name' => 'load_more_button_icon_color',
							'selector'       => "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button .et-pb-icon",
							'hover_selector' => "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button:hover .et-pb-icon",
							'css_property'   => 'color',
							'render_slug'    => $this->slug,
							'type'           => 'color',
							'important'      => true,
						)
					);
					$this->generate_styles(
						array(
							'base_attr_name' => 'load_more_button_icon_size',
							'selector'       => "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button .et-pb-icon",
							'hover_selector' => "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button:hover .et-pb-icon",
							'css_property'   => 'font-size',
							'render_slug'    => $this->slug,
							'type'           => 'range',
							'important'      => true,
						)
					);
				}

				if ( 'image' === $this->props['load_more_button_icon_type'] ) {
					$this->generate_styles(
						array(
							'base_attr_name' => 'load_more_button_image_width',
							'selector'       => "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button .squad-icon-wrapper img",
							'hover_selector' => "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button:hover .squad-icon-wrapper img",
							'css_property'   => 'width',
							'render_slug'    => $this->slug,
							'type'           => 'range',
							'important'      => true,
						)
					);
					$this->generate_styles(
						array(
							'base_attr_name' => 'load_more_button_image_height',
							'selector'       => "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button .squad-icon-wrapper img",
							'hover_selector' => "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button:hover .squad-icon-wrapper img",
							'css_property'   => 'height',
							'render_slug'    => $this->slug,
							'type'           => 'range',
							'important'      => true,
						)
					);
				}

				if ( ( 'on' === $this->props['load_more_button_icon_on_hover'] ) ) {
					$mapping_values = array(
						'inherit'     => '0 0 0 0',
						'column'      => '0 0 -#px 0',
						'row'         => '0 -#px 0 0',
						'row-reverse' => '0 0 0 -#px',
					);

					if ( 'on' === $this->prop( 'load_more_button_icon_hover_move_icon', 'off' ) ) {
						$mapping_values = array(
							'inherit'     => '0 0 0 0',
							'column'      => '#px 0 -#px 0',
							'row'         => '0 -#px 0 #px',
							'row-reverse' => '0 #px 0 -#px',
						);
					}

					// set icon placement for button image with default, hover, and responsive.
					$this->squad_utils->generate_show_icon_on_hover_styles(
						array(
							'field'          => 'load_more_button_icon_placement',
							'trigger'        => 'load_more_button_icon_type',
							'depends_on'     => array(
								'icon'  => 'load_more_button_icon_size',
								'image' => 'load_more_button_image_width',
							),
							'selector'       => "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button .squad-icon-wrapper.show-on-hover",
							'hover'          => "$this->main_css_element div .squad-load-more-button-wrapper .squad-load-more-button:hover .squad-icon-wrapper.show-on-hover",
							'css_property'   => 'margin',
							'type'           => 'margin',
							'mapping_values' => $mapping_values,
							'defaults'       => array(
								'icon'  => '40px',
								'image' => '40px',
								'field' => 'row',
							),
						)
					);
				}
			}
		}
		if ( 'on' === $this->prop( 'pagination__enable', 'off' ) ) {
			// background with default, responsive, hover.
			et_pb_background_options()->get_background_style(
				array(
					'base_prop_name'         => 'pagination_wrapper_background',
					'props'                  => $this->props,
					'selector'               => "$this->main_css_element div .squad-pagination",
					'selector_hover'         => "$this->main_css_element div .squad-pagination:hover",
					'selector_sticky'        => "$this->main_css_element div .squad-pagination",
					'function_name'          => $this->slug,
					'important'              => ' !important',
					'use_background_video'   => false,
					'use_background_pattern' => false,
					'use_background_mask'    => false,
					'prop_name_aliases'      => array(
						'use_pagination_wrapper_background_color_gradient' => 'pagination_wrapper_background_use_color_gradient',
						'pagination_wrapper_background' => 'pagination_wrapper_background_color',
					),
				)
			);
			et_pb_background_options()->get_background_style(
				array(
					'base_prop_name'         => 'pagination_background',
					'props'                  => $this->props,
					'selector'               => "$this->main_css_element div .squad-pagination .pagination-numbers .page-numbers, $this->main_css_element div .squad-pagination .pagination-entries",
					'selector_hover'         => "$this->main_css_element div .squad-pagination .pagination-numbers .page-numbers:hover, $this->main_css_element div .squad-pagination .pagination-entries:hover",
					'selector_sticky'        => "$this->main_css_element div .squad-pagination .pagination-numbers .page-numbers, $this->main_css_element div .squad-pagination .pagination-entries",
					'function_name'          => $this->slug,
					'important'              => ' !important',
					'use_background_video'   => false,
					'use_background_pattern' => false,
					'use_background_mask'    => false,
					'prop_name_aliases'      => array(
						'use_pagination_background_color_gradient' => 'pagination_background_use_color_gradient',
						'pagination_background' => 'pagination_background_color',
					),
				)
			);
			et_pb_background_options()->get_background_style(
				array(
					'base_prop_name'         => 'active_pagination_background',
					'props'                  => $this->props,
					'selector'               => "$this->main_css_element div .squad-pagination .pagination-numbers .page-numbers.current",
					'selector_hover'         => "$this->main_css_element div .squad-pagination .pagination-numbers .page-numbers.current:hover",
					'selector_sticky'        => "$this->main_css_element div .squad-pagination .pagination-numbers .page-numbers.current",
					'function_name'          => $this->slug,
					'important'              => ' !important',
					'use_background_video'   => false,
					'use_background_pattern' => false,
					'use_background_mask'    => false,
					'prop_name_aliases'      => array(
						'use_active_pagination_background_color_gradient' => 'active_pagination_background_use_color_gradient',
						'active_pagination_background' => 'active_pagination_background_color',
					),
				)
			);

			// Pagination horizontal alignment with default, responsive, hover.
			$this->generate_styles(
				array(
					'base_attr_name' => 'pagination_horizontal_alignment',
					'selector'       => "$this->main_css_element div .squad-pagination",
					'hover_selector' => "$this->main_css_element div .squad-pagination:hover",
					'css_property'   => 'justify-content',
					'render_slug'    => $this->slug,
					'type'           => 'align',
					'important'      => true,
				)
			);

			// pagination icon with default, responsive, hover.
			$this->generate_styles(
				array(
					'utility_arg'    => 'icon_font_family',
					'render_slug'    => $this->slug,
					'base_attr_name' => 'pagination_old_entries_icon',
					'important'      => true,
					'selector'       => "$this->main_css_element div .squad-pagination .pagination-entries .squad-pagination_old_entries-icon.et-pb-icon",
					'processor'      => array(
						'ET_Builder_Module_Helper_Style_Processor',
						'process_extended_icon',
					),
				)
			);
			$this->generate_styles(
				array(
					'utility_arg'    => 'icon_font_family',
					'render_slug'    => $this->slug,
					'base_attr_name' => 'pagination_next_entries_icon',
					'important'      => true,
					'selector'       => "$this->main_css_element div .squad-pagination .pagination-entries .squad-pagination_next_entries-icon.et-pb-icon",
					'processor'      => array(
						'ET_Builder_Module_Helper_Style_Processor',
						'process_extended_icon',
					),
				)
			);

			$this->generate_styles(
				array(
					'base_attr_name' => 'pagination_icon_color',
					'selector'       => "$this->main_css_element div .squad-pagination .pagination-entries span.et-pb-icon",
					'hover_selector' => "$this->main_css_element div .squad-pagination .pagination-entries:hover span.et-pb-icon",
					'css_property'   => 'color',
					'render_slug'    => $this->slug,
					'type'           => 'color',
					'important'      => true,
				)
			);
			$this->generate_styles(
				array(
					'base_attr_name' => 'pagination_icon_size',
					'selector'       => "$this->main_css_element div .squad-pagination .pagination-entries span.et-pb-icon",
					'hover_selector' => "$this->main_css_element div .squad-pagination .pagination-entries:hover span.et-pb-icon",
					'css_property'   => 'font-size',
					'render_slug'    => $this->slug,
					'type'           => 'range',
					'important'      => true,
				)
			);
			$this->generate_styles(
				array(
					'base_attr_name' => 'pagination_icon_text_gap',
					'selector'       => "$this->main_css_element div .squad-pagination .pagination-entries",
					'hover'          => "$this->main_css_element div .squad-pagination .pagination-entries:hover",
					'css_property'   => 'gap',
					'render_slug'    => $this->slug,
					'type'           => 'input',
					'important'      => true,
				)
			);
			$this->generate_styles(
				array(
					'base_attr_name' => 'pagination_elements_gap',
					'selector'       => "$this->main_css_element div .squad-pagination, $this->main_css_element div .squad-pagination .pagination-numbers",
					'hover'          => "$this->main_css_element div .squad-pagination:hover, $this->main_css_element div .squad-pagination .pagination-numbers:hover",
					'css_property'   => 'gap',
					'render_slug'    => $this->slug,
					'type'           => 'input',
					'important'      => true,
				)
			);

			// wrapper margin with default, responsive, hover.
			$this->squad_utils->generate_margin_padding_styles(
				array(
					'field'          => 'pagination_wrapper_margin',
					'selector'       => "$this->main_css_element div .squad-pagination",
					'hover_selector' => "$this->main_css_element div .squad-pagination:hover",
					'css_property'   => 'margin',
					'type'           => 'margin',
				)
			);
			$this->squad_utils->generate_margin_padding_styles(
				array(
					'field'          => 'pagination_wrapper_padding',
					'selector'       => "$this->main_css_element div .squad-pagination",
					'hover_selector' => "$this->main_css_element div .squad-pagination:hover",
					'css_property'   => 'padding',
					'type'           => 'padding',
				)
			);

			// pagination margin with default, responsive, hover.
			$this->squad_utils->generate_margin_padding_styles(
				array(
					'field'          => 'pagination_margin',
					'selector'       => "$this->main_css_element div .squad-pagination .pagination-numbers .page-numbers, $this->main_css_element div .squad-pagination .pagination-entries",
					'hover_selector' => "$this->main_css_element div .squad-pagination .pagination-numbers .page-numbers:hover, $this->main_css_element div .squad-pagination .pagination-entries:hover",
					'css_property'   => 'margin',
					'type'           => 'margin',
				)
			);
			$this->squad_utils->generate_margin_padding_styles(
				array(
					'field'          => 'pagination_padding',
					'selector'       => "$this->main_css_element div .squad-pagination .pagination-numbers .page-numbers, $this->main_css_element div .squad-pagination .pagination-entries",
					'hover_selector' => "$this->main_css_element div .squad-pagination .pagination-numbers .page-numbers:hover, $this->main_css_element div .squad-pagination .pagination-entries:hover",
					'css_property'   => 'padding',
					'type'           => 'padding',
				)
			);

			// active pagination margin with default, responsive, hover.
			$this->squad_utils->generate_margin_padding_styles(
				array(
					'field'          => 'active_pagination_margin',
					'selector'       => "$this->main_css_element div .squad-pagination .pagination-numbers .page-numbers.current",
					'hover_selector' => "$this->main_css_element div .squad-pagination .pagination-numbers .page-numbers.current:hover",
					'css_property'   => 'margin',
					'type'           => 'margin',
				)
			);
			$this->squad_utils->generate_margin_padding_styles(
				array(
					'field'          => 'active_pagination_padding',
					'selector'       => "$this->main_css_element div .squad-pagination .pagination-numbers .page-numbers.current",
					'hover_selector' => "$this->main_css_element div .squad-pagination .pagination-numbers .page-numbers.current:hover",
					'css_property'   => 'padding',
					'type'           => 'padding',
				)
			);

			// icon margin with default, responsive, hover.
			$this->squad_utils->generate_margin_padding_styles(
				array(
					'field'          => 'pagination_icon_margin',
					'selector'       => "$this->main_css_element div .squad-pagination .pagination-entries span.et-pb-icon",
					'hover_selector' => "$this->main_css_element div .squad-pagination .pagination-entries:hover span.et-pb-icon",
					'css_property'   => 'margin',
					'type'           => 'margin',
				)
			);
			$this->squad_utils->generate_margin_padding_styles(
				array(
					'field'          => 'pagination_icon_padding',
					'selector'       => "$this->main_css_element div .squad-pagination .pagination-entries span.et-pb-icon",
					'hover_selector' => "$this->main_css_element div .squad-pagination .pagination-entries:hover span.et-pb-icon",
					'css_property'   => 'padding',
					'type'           => 'padding',
				)
			);
		}
	}
}
