<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Star Rating Module Class which extend the Divi Builder Module Class.
 *
 * This class provides rating adding functionalities in the visual builder.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   1.4.0
 */

namespace DiviSquad\Modules\Creatives;

use DiviSquad\Base\DiviBuilder\Module;
use DiviSquad\Base\DiviBuilder\Utils;
use DiviSquad\Utils\Helper;
use function esc_attr;
use function esc_html__;
use function wp_parse_args;

/**
 * Star Rating Module Class.
 *
 * @package DiviSquad
 * @since   1.4.0
 */
class StarRating extends Module {

	/**
	 * Initiate Module.
	 * Set the module name on init.
	 *
	 * @return void
	 * @since 1.4.0
	 */
	public function init() {
		$this->name      = esc_html__( 'Star Rating', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Star Ratings', 'squad-modules-for-divi' );
		$this->icon_path = Helper::fix_slash( divi_squad()->get_icon_path() . '/star-rating.svg' );

		$this->slug             = 'disq_star_rating';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.$this->slug";

		$this->child_title_var          = 'title';
		$this->child_title_fallback_var = 'admin_label';

		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'main_content' => esc_html__( 'Rating', 'squad-modules-for-divi' ),
					'schema'       => esc_html__( 'Schema', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'stars'         => esc_html__( 'Stars', 'squad-modules-for-divi' ),
					'rating_number' => esc_html__( 'Rating Number', 'squad-modules-for-divi' ),
				),
			),
		);

		// Declare advanced fields for the module.
		$this->advanced_fields = array(
			'fonts'          => array(
				'header'        => Utils::add_font_field(
					esc_html__( 'Title', 'squad-modules-for-divi' ),
					array(
						'font_size'       => array(
							'default' => '18px',
						),
						'line_height'     => array(
							'default' => '1em',
						),
						'letter_spacing'  => array(
							'default' => '0px',
						),
						'hide_text_align' => true,
						'css'             => array(
							'main'  => "$this->main_css_element div .et_pb_module_header",
							'hover' => "$this->main_css_element div .et_pb_module_header:hover",
						),
						'tab_slug'        => 'advanced',
						'toggle_slug'     => 'stars',
					)
				),
				'rating_number' => Utils::add_font_field(
					esc_html__( 'Rating Number', 'squad-modules-for-divi' ),
					array(
						'font_size'       => array(
							'default' => '14px',
						),
						'line_height'     => array(
							'default' => '1em',
						),
						'letter_spacing'  => array(
							'default' => '0px',
						),
						'hide_text_align' => true,
						'text_shadow'     => array(
							'show_if' => array(
								'show_number' => 'on',
							),
						),
						'css'             => array(
							'main'  => "$this->main_css_element div .star-rating-text",
							'hover' => "$this->main_css_element div .star-rating-text:hover",
						),
						'tab_slug'        => 'advanced',
						'toggle_slug'     => 'rating_number',
					)
				),
			),
			'background'     => Utils::selectors_background( $this->main_css_element ),
			'borders'        => array( 'default' => Utils::selectors_default( $this->main_css_element ) ),
			'box_shadow'     => array( 'default' => Utils::selectors_default( $this->main_css_element ) ),
			'margin_padding' => Utils::selectors_margin_padding( $this->main_css_element ),
			'max_width'      => Utils::selectors_max_width( $this->main_css_element ),
			'height'         => Utils::selectors_default( $this->main_css_element ),
			'image_icon'     => false,
			'text'           => false,
			'button'         => false,
			'filters'        => false,
		);

		// Declare custom css fields for the module.
		$this->custom_css_fields = array(
			'title'         => array(
				'label'    => esc_html__( 'Title', 'squad-modules-for-divi' ),
				'selector' => 'div .star-rating-title',
			),
			'stars'         => array(
				'label'    => esc_html__( 'Star', 'squad-modules-for-divi' ),
				'selector' => 'div .star-rating i',
			),
			'rating_number' => array(
				'label'    => esc_html__( 'Rating Number', 'squad-modules-for-divi' ),
				'selector' => 'div .star-rating-text',
			),
		);
	}

	/**
	 * Declare general fields for the module
	 *
	 * @return array[]
	 * @since 1.4.0
	 */
	public function get_fields() {
		// All rating fields.
		$rating_fields = array(
			'rating_scale'   => Utils::add_select_box_field(
				esc_html__( 'Rating Scale', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Choose a rating scale for ratings.', 'squad-modules-for-divi' ),
					'options'          => array(
						'5'  => esc_html__( '0 - 5', 'squad-modules-for-divi' ),
						'10' => esc_html__( '0 - 10', 'squad-modules-for-divi' ),
					),
					'default_on_front' => '5',
					'default'          => '5',
					'affects'          => array(
						'rating_upto_5',
						'rating_upto_10',
					),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'main_content',
				)
			),
			'rating_upto_5'  => Utils::add_range_field(
				esc_html__( 'Rating', 'squad-modules-for-divi' ),
				array(
					'description'       => esc_html__( 'Choose the rating up to 5.', 'squad-modules-for-divi' ),
					'range_settings'    => array(
						'min'       => '0',
						'max'       => '5',
						'step'      => '0.1',
						'min_limit' => '0',
						'max_limit' => '5',
					),
					'default'           => '5',
					'default_on_front'  => '5',
					'number_validation' => true,
					'fixed_range'       => true,
					'unitless'          => true,
					'hover'             => false,
					'mobile_options'    => false,
					'responsive'        => false,
					'depends_show_if'   => '5',
					'tab_slug'          => 'general',
					'toggle_slug'       => 'main_content',
				)
			),
			'rating_upto_10' => Utils::add_range_field(
				esc_html__( 'Rating', 'squad-modules-for-divi' ),
				array(
					'description'       => esc_html__( 'Choose the rating up to 10.', 'squad-modules-for-divi' ),
					'range_settings'    => array(
						'min'       => '0',
						'max'       => '10',
						'step'      => '0.1',
						'min_limit' => '0',
						'max_limit' => '10',
					),
					'default'           => '10',
					'default_on_front'  => '10',
					'number_validation' => true,
					'fixed_range'       => true,
					'unitless'          => true,
					'hover'             => false,
					'mobile_options'    => false,
					'responsive'        => false,
					'depends_show_if'   => '10',
					'tab_slug'          => 'general',
					'toggle_slug'       => 'main_content',
				)
			),
		);

		// Rating title fields.
		$rating_title_fields = array(
			'title'                  => array(
				'label'           => esc_html__( 'Title', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'The text will appear in with your star rating.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'main_content',
				'dynamic_content' => 'text',
			),
			'text_element_tag'       => Utils::add_select_box_field(
				esc_html__( 'Title Tag', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Choose a tag to display with your title texts.', 'squad-modules-for-divi' ),
					'options'          => Utils::get_html_tag_elements(),
					'default_on_front' => 'h2',
					'default'          => 'h2',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'main_content',
				)
			),
			'stars_display_type'     => Utils::add_select_box_field(
				esc_html__( 'Stars Display Type', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Choose a display type for your stars and title view.', 'squad-modules-for-divi' ),
					'options'          => array(
						'inline-block' => esc_html__( 'Inline', 'squad-modules-for-divi' ),
						'block'        => esc_html__( 'Stacked', 'squad-modules-for-divi' ),
					),
					'default_on_front' => 'inline-block',
					'default'          => 'inline-block',
					'affects'          => array(
						'title_inline_position',
						'title_stacked_position',
					),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'main_content',
				)
			),
			'title_inline_position'  => Utils::add_select_box_field(
				esc_html__( 'Title Inline Position', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Choose a inline display position to display with your title texts.', 'squad-modules-for-divi' ),
					'options'          => array(
						'left'  => esc_html__( 'Left', 'squad-modules-for-divi' ),
						'right' => esc_html__( 'Right', 'squad-modules-for-divi' ),
					),
					'default_on_front' => 'left',
					'default'          => 'left',
					'depends_show_if'  => 'inline-block',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'main_content',
				)
			),
			'title_stacked_position' => Utils::add_select_box_field(
				esc_html__( 'Title Stacked Position', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Choose a stacked display position to display with your title texts.', 'squad-modules-for-divi' ),
					'options'          => array(
						'top'    => esc_html__( 'Top', 'squad-modules-for-divi' ),
						'bottom' => esc_html__( 'Bottom', 'squad-modules-for-divi' ),
					),
					'default_on_front' => 'bottom',
					'default'          => 'bottom',
					'depends_show_if'  => 'block',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'main_content',
				)
			),
			'title_gap'              => Utils::add_range_field(
				esc_html__( 'Gap Between Title and Stars', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Here you can define a gap between the title and the star rating.', 'squad-modules-for-divi' ),
					'range_settings' => array(
						'min'       => '0',
						'max'       => '200',
						'step'      => '1',
						'min_limit' => '0',
						'max_limit' => '200',
					),
					'default'        => '7px',
					'default_unit'   => 'px',
					'tab_slug'       => 'general',
					'toggle_slug'    => 'main_content',
					'allow_empty'    => false,
					'mobile_options' => true,
				),
				array( 'use_hover' => false )
			),
		);

		// Star associated advanced fields.
		$stars_associated_fields = array(
			'star_alignment' => Utils::add_alignment_field(
				esc_html__( 'Stars Alignment', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Align your Stars to the left, right or center of the module.', 'squad-modules-for-divi' ),
					'default_on_front' => 'left',
					'tab_slug'         => 'advanced',
					'toggle_slug'      => 'stars',
				)
			),
			'stars_size'     => Utils::add_range_field(
				esc_html__( 'Size', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Here you can choose stars size.', 'squad-modules-for-divi' ),
					'range_settings' => array(
						'min'       => '0',
						'max'       => '200',
						'step'      => '1',
						'min_limit' => '0',
						'max_limit' => '200',
					),
					'default'        => '14',
					'default_unit'   => 'px',
					'allow_empty'    => false,
					'hover'          => false,
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'stars',
					'mobile_options' => true,
				)
			),
			'stars_gap'      => Utils::add_range_field(
				esc_html__( 'Gap', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Here you can choose gap between each stars.', 'squad-modules-for-divi' ),
					'range_settings' => array(
						'min'       => '0',
						'max'       => '200',
						'step'      => '1',
						'min_limit' => '0',
						'max_limit' => '200',
					),
					'default'        => '0',
					'default_unit'   => 'px',
					'hover'          => false,
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'stars',
					'mobile_options' => true,
				)
			),
			'stars_color'    => Utils::add_color_field(
				esc_html__( 'Stars Color', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Here you can define a custom color for stars.', 'squad-modules-for-divi' ),
					'default'     => '#f0ad4e',
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'stars',
				)
			),
		);

		// Rating control fields.
		$rating_control_fields = array(
			'show_number' => Utils::add_yes_no_field(
				esc_html__( 'Show Rating Number', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can choose whether or not display the rating number after the stars.', 'squad-modules-for-divi' ),
					'default_on_front' => 'off',
					'affects'          => array(
						'rating_number',
						'rating_number_font',
						'rating_number_text_color',
						'rating_number_font_size',
						'rating_number_letter_spacing',
						'rating_number_line_height',
						'rating_number_text_shadow_style',
					),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'main_content',
				)
			),
		);

		// schema markup fields.
		$schema_fields = array(
			'stars_schema_markup'        => Utils::add_yes_no_field(
				esc_html__( 'Enable Schema Support', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can choose whether or not enable Schema Support.', 'squad-modules-for-divi' ),
					'default_on_front' => 'off',
					'affects'          => array(
						'stars_schema_author',
						'stars_schema_product_name',
						'stars_schema_num_of_rating',
					),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'schema',
				)
			),
			'stars_schema_author'        => array(
				'label'            => esc_html__( 'Author', 'squad-modules-for-divi' ),
				'description'      => esc_html__( 'The author of the product for this rating.', 'squad-modules-for-divi' ),
				'type'             => 'text',
				'option_category'  => 'basic_option',
				'default'          => '',
				'default_on_front' => '',
				'depends_show_if'  => 'on',
				'tab_slug'         => 'general',
				'toggle_slug'      => 'schema',
				'dynamic_content'  => 'text',
			),
			'stars_schema_product_name'  => array(
				'label'            => esc_html__( 'Product Name', 'squad-modules-for-divi' ),
				'description'      => esc_html__( 'The item name for the product.', 'squad-modules-for-divi' ),
				'type'             => 'text',
				'option_category'  => 'basic_option',
				'default'          => '',
				'default_on_front' => '',
				'depends_show_if'  => 'on',
				'tab_slug'         => 'general',
				'toggle_slug'      => 'schema',
				'dynamic_content'  => 'text',
			),
			'stars_schema_num_of_rating' => array(
				'label'            => esc_html__( 'Number of Rating', 'squad-modules-for-divi' ),
				'description'      => esc_html__( 'The total number of ratings based on the rating given.', 'squad-modules-for-divi' ),
				'type'             => 'text',
				'option_category'  => 'basic_option',
				'default'          => '',
				'default_on_front' => '',
				'depends_show_if'  => 'on',
				'tab_slug'         => 'general',
				'toggle_slug'      => 'schema',
				'dynamic_content'  => 'text',
			),
		);

		return array_merge(
			$rating_fields,
			$rating_title_fields,
			$rating_control_fields,
			$stars_associated_fields,
			$schema_fields
		);
	}

	/**
	 * Get CSS fields transition.
	 *
	 * Add form field options group and background image on the field list.
	 *
	 * @since 1.4.0
	 */
	public function get_transition_fields_css_props() {
		$fields = parent::get_transition_fields_css_props();

		$fields['stars_color'] = array( 'background' => "$this->main_css_element div .star-rating i" );
		Utils::fix_fonts_transition( $fields, 'header', "$this->main_css_element div .star-rating-title" );
		Utils::fix_fonts_transition( $fields, 'typed_text', "$this->main_css_element div .star-rating-text" );

		// Default styles.
		$fields['background_layout'] = array( 'color' => "$this->main_css_element div .star-rating" );

		return $fields;
	}

	/**
	 * Renders the module output.
	 *
	 * @param array  $attrs       List of attributes.
	 * @param string $content     Content being processed.
	 * @param string $render_slug Slug of module that is used for rendering output.
	 *
	 * @return string
	 */
	public function render( $attrs, $content, $render_slug ) {
		$stars_schema_markup    = $this->prop( 'stars_schema_markup', 'off' );
		$stars_display_type     = $this->prop( 'stars_display_type', 'inline' );
		$title_inline_position  = $this->prop( 'title_inline_position', 'left' );
		$title_stacked_position = $this->prop( 'title_stacked_position', 'bottom' );
		$title_display          = 'inline-block' === $stars_display_type ? $title_inline_position : $title_stacked_position;

		// Collect rating title.
		$title = $this->props['title'];
		if ( '' !== $title ) {
			$title = sprintf(
				'<%1$s class="star-rating-title et_pb_module_header"><span%3$s>%2$s</span></%1$s>',
				esc_attr( $this->prop( 'text_element_tag', 'h2' ) ),
				$title,
				'on' === $stars_schema_markup ? esc_attr( ' itemprop="name"' ) : ''
			);
		}

		// collect rating data with html.
		$rating_scale = absint( $this->prop( 'rating_scale', 5 ) );
		$rating       = (float) ( 10 === $rating_scale ) ? $this->prop( 'rating_upto_10', 10 ) : $this->prop( 'rating_upto_5', 5 );
		$stars_output = self::get_star_rating(
			array(
				'rating_scale'        => $rating_scale,
				'rating'              => $rating,
				'show_number'         => $this->prop( 'show_number', 'off' ),
				'stars_schema_markup' => $stars_schema_markup,
			)
		);

		if ( 'inline-block' === $stars_display_type ) {
			$position_output = sprintf(
				'%1$s<div class="star-rating" %6$s title="%2$s/%3$s">%4$s</div>%5$s',
				'left' === $title_inline_position ? $title : '',
				esc_attr( (string) $rating ),
				esc_attr( (string) $rating_scale ),
				$stars_output,
				'right' === $title_inline_position ? $title : '',
				'on' === $stars_schema_markup ? esc_attr( ' itemprop=reviewRating itemscope itemtype=http://schema.org/Rating' ) : ''
			);
		} else {
			$position_output = sprintf(
				'%1$s<div class="star-rating" %6$s title="%2$s/%3$s">%4$s</div>%5$s',
				'top' === $title_stacked_position ? $title : '',
				esc_attr( (string) $rating ),
				esc_attr( (string) $rating_scale ),
				$stars_output,
				'bottom' === $title_stacked_position ? $title : '',
				'on' === $stars_schema_markup ? esc_attr( ' itemprop=reviewRating itemscope itemtype=http://schema.org/Rating' ) : ''
			);
		}

		// Generate additional styles for frontend.
		$this->generate_additional_styles( $attrs );

		if ( 'on' === $stars_schema_markup ) {
			$stars_schema_product_item_name = $this->prop( 'stars_schema_product_name' );
			$stars_schema_num_of_rating     = $this->prop( 'stars_schema_num_of_rating' );
			$stars_schema_author            = $this->prop( 'stars_schema_author' );

			return sprintf(
				'<div itemscope itemtype="https://schema.org/Review" class="star-rating-wrapper d-type-%2$s star-title-position-%3$s">
					<div style="display:none" itemprop="itemReviewed" itemscope itemtype="https://schema.org/Product">
						<span itemprop="name">%5$s</span>
						<span itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">
							<span itemprop="ratingValue">%6$s</span>
							<span itemprop="ratingCount">%7$s</span>
						</span>
					</div>
					%1$s
					<span style="display:none" itemprop="author" itemscope itemtype="https://schema.org/Person">
				  		<span itemprop="name">%4$s</span>
					</span>
				</div>',
				$position_output,
				esc_html( $stars_display_type ),
				esc_html( $title_display ),
				esc_html( $stars_schema_author ),
				esc_html( $stars_schema_product_item_name ),
				esc_html( $stars_schema_num_of_rating ),
				esc_html( $rating )
			);
		}

		return sprintf(
			'<div class="star-rating-wrapper d-type-%2$s star-title-position-%3$s"> %1$s </div>',
			$position_output,
			esc_html( $stars_display_type ),
			esc_html( $title_display )
		);
	}

	/**
	 * Generate html markup for stars.
	 *
	 * @param array $args List of attributes.
	 *
	 * @return string
	 */
	public static function get_star_rating( array $args = array() ): string {
		$defaults = array(
			'rating_scale'        => 5,
			'rating'              => 5.0,
			'show_number'         => 'off',
			'stars_schema_markup' => 'off',
		);

		$args = wp_parse_args( $args, $defaults );

		$int_rating = absint( $args['rating'] );
		$output     = '';

		for ( $stars = 1; $stars <= $args['rating_scale']; $stars++ ) {
			if ( $stars <= $int_rating ) {
				$output .= '<i class="star-full">☆</i>';
			} elseif ( $int_rating + 1 === $stars && $args['rating'] !== $int_rating ) {
				$output .= '<i class="star-' . ( (float) $args['rating'] % 10 ) . '">☆</i>';
			} else {
				$output .= '<i class="star-empty">☆</i>';
			}
		}

		if ( 'on' === $args['show_number'] ) {
			if ( 'on' === $args['stars_schema_markup'] ) {
				$stars_number_html = '<meta itemprop="worstRating" content="1">(<span itemprop="ratingValue">' . $args['rating'] . '</span>/<span itemprop="bestRating">' . $args['rating_scale'] . '</span>)';
			} else {
				$stars_number_html = '(<span>' . $args['rating'] . '</span>/<span>' . $args['rating_scale'] . '</span>)';
			}

			$output .= ' <span class="star-rating-text">' . $stars_number_html . '</span>';
		}

		return $output;
	}

	/**
	 * Renders additional styles for the module output.
	 *
	 * @param array $attrs List of attributes.
	 *
	 * @return void
	 */
	private function generate_additional_styles( array $attrs ): void {
		// Fixed: the custom background doesn't work at frontend.
		$this->props = array_merge( $attrs, $this->props );

		$this->add_classname( array( $this->get_text_orientation_classname() ) );

		$stars_display_type     = $this->prop( 'stars_display_type', 'inline' );
		$title_inline_position  = $this->prop( 'title_inline_position', 'left' );
		$title_stacked_position = $this->prop( 'title_stacked_position', 'bottom' );
		$title_display          = 'inline-block' === $stars_display_type ? $title_inline_position : $title_stacked_position;

		if ( 'inline-block' === $stars_display_type ) {
			$inline_position = 'left' === $title_inline_position ? 'right' : 'left';
			$this->generate_styles(
				array(
					'base_attr_name' => 'title_gap',
					'selector'       => "$this->main_css_element div .star-title-position-$title_display .star-rating-title",
					'css_property'   => "margin-$inline_position",
					'render_slug'    => $this->slug,
					'type'           => 'input',
				)
			);
		} else {
			$stacked_position = 'top' === $title_stacked_position ? 'bottom' : 'top';
			$this->generate_styles(
				array(
					'base_attr_name' => 'title_gap',
					'selector'       => "$this->main_css_element div .star-title-position-$title_display .star-rating-title",
					'css_property'   => "margin-$stacked_position",
					'render_slug'    => $this->slug,
					'type'           => 'input',
				)
			);
		}

		$this->generate_styles(
			array(
				'base_attr_name' => 'star_alignment',
				'selector'       => $this->main_css_element,
				'css_property'   => 'text-align',
				'render_slug'    => $this->slug,
				'type'           => 'align',
			)
		);

		$this->generate_styles(
			array(
				'base_attr_name' => 'stars_size',
				'selector'       => "$this->main_css_element div .star-rating",
				'css_property'   => 'font-size',
				'render_slug'    => $this->slug,
				'type'           => 'input',
			)
		);

		$this->generate_styles(
			array(
				'base_attr_name' => 'stars_gap',
				'selector'       => "$this->main_css_element div .star-rating i:not(:last-of-type)",
				'css_property'   => 'margin-right',
				'render_slug'    => $this->slug,
				'type'           => 'input',
			)
		);

		if ( '#f0ad4e' !== $this->prop( 'stars_color', '#f0ad4e' ) ) {
			$this->generate_styles(
				array(
					'base_attr_name' => 'stars_color',
					'selector'       => "$this->main_css_element div .star-rating, $this->main_css_element div .star-rating i",
					'css_property'   => 'color',
					'render_slug'    => $this->slug,
					'type'           => 'string',
				)
			);
		}

		// Show star-rating-text.
		if ( 'off' === $this->prop( 'show_number', 'off' ) && 'on' === $this->prop( 'stars_schema_markup', 'off' ) ) {
			self::set_style(
				$this->slug,
				array(
					'selector'    => "$this->main_css_element .star-rating-text",
					'declaration' => 'display: none;',
				)
			);
		}
	}
}
