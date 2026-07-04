<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Post-Reading Time Module Class which extend the Divi Builder Module Class.
 *
 * This class provides item adding functionalities for a post-reading time element in the visual builder.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   1.2.2
 */

namespace DiviSquad\Modules;

use DiviSquad\Base\DiviBuilder\Module;
use DiviSquad\Base\DiviBuilder\Utils;
use DiviSquad\Core\Supports\Polyfills\Str;
use DiviSquad\Utils\Helper;
use function esc_attr;
use function esc_html__;
use function et_pb_background_options;
use function get_comments;
use function get_post_field;
use function get_post_type;
use function get_the_ID;
use function in_the_loop;
use function is_singular;
use function wp_kses_post;
use function wp_strip_all_tags;

/**
 * Post-Reading Time Module Class.
 *
 * @package DiviSquad
 * @since   1.2.2
 */
class PostReadingTime extends Module {
	/**
	 * Initiate Module.
	 * Set the module name on init.
	 *
	 * @return void
	 * @since 1.2.2
	 */
	public function init(): void {
		$this->name      = esc_html__( 'Post Reading Time', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Post Reading Times', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'post-reading-time.svg' );

		$this->slug             = 'disq_post_reading_time';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.$this->slug";

		$this->child_title_var          = 'admin_label';
		$this->child_title_fallback_var = 'admin_label';

		// Connect with utils.
		$this->squad_utils = Utils::connect( $this );
		$this->squad_utils->divider->initiate_element();

		// Declare settings modal toggles for the module.
		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'main_content'     => esc_html__( 'Content', 'squad-modules-for-divi' ),
					'reading_settings' => esc_html__( 'Reading Settings', 'squad-modules-for-divi' ),
					'time_divider'     => esc_html__( 'Divider', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'time_element' => esc_html__( 'Time', 'squad-modules-for-divi' ),
					'time_text'    => esc_html__( 'Time Text', 'squad-modules-for-divi' ),
					'time_divider' => esc_html__( 'Divider', 'squad-modules-for-divi' ),
				),
			),
		);

		// Declare advanced fields for the module.
		$this->advanced_fields = array(
			'fonts'          => array(
				'time_text' => Utils::add_font_field(
					esc_html__( 'Time', 'squad-modules-for-divi' ),
					array(
						'css'         => array(
							'main'  => "$this->main_css_element div .time-text-wrapper .time-text-container .time-text-item",
							'hover' => "$this->main_css_element div .time-text-wrapper:hover .time-text-container .time-text-item",
						),
						'tab_slug'    => 'advanced',
						'toggle_slug' => 'time_text',
					)
				),
			),
			'background'     => Utils::selectors_background( $this->main_css_element ),
			'borders'        => array(
				'default'      => Utils::selectors_default( $this->main_css_element ),
				'time_element' => array(
					'label_prefix' => esc_html__( 'Time', 'squad-modules-for-divi' ),
					'css'          => array(
						'main' => array(
							'border_radii'        => "$this->main_css_element div .time-text-wrapper .time-text-container",
							'border_radii_hover'  => "$this->main_css_element div .time-text-wrapper .time-text-container:hover",
							'border_styles'       => "$this->main_css_element div .time-text-wrapper .time-text-container",
							'border_styles_hover' => "$this->main_css_element div .time-text-wrapper .time-text-container:hover",
						),
					),
					'tab_slug'     => 'advanced',
					'toggle_slug'  => 'time_element',
				),
			),
			'box_shadow'     => array(
				'default'      => Utils::selectors_default( $this->main_css_element ),
				'time_element' => array(
					'label'             => esc_html__( 'Time Box Shadow', 'squad-modules-for-divi' ),
					'option_category'   => 'layout',
					'css'               => array(
						'main'  => "$this->main_css_element div .time-text-wrapper .time-text-container",
						'hover' => "$this->main_css_element div .time-text-wrapper .time-text-container:hover",
					),
					'default_on_fronts' => array(
						'color'    => 'rgba(0,0,0,0.3)',
						'position' => 'outer',
					),
					'tab_slug'          => 'advanced',
					'toggle_slug'       => 'title_element',
				),
			),
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
			'time'        => array(
				'label'    => esc_html__( 'Time', 'squad-modules-for-divi' ),
				'selector' => 'div .time-text-wrapper .time-text-container .time-text-item',
			),
			'time_infix'  => array(
				'label'    => esc_html__( 'Time Text', 'squad-modules-for-divi' ),
				'selector' => 'div .time-text-wrapper .time-text-element',
			),
			'time_prefix' => array(
				'label'    => esc_html__( 'Time Prefix', 'squad-modules-for-divi' ),
				'selector' => 'div .time-text-wrapper .time-text-prefix-element',
			),
			'time_suffix' => array(
				'label'    => esc_html__( 'Time Suffix', 'squad-modules-for-divi' ),
				'selector' => 'div .time-text-wrapper .time-text-suffix-element',
			),
		);
	}

	/**
	 * Declare general fields for the module
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array<string, string>>
	 */
	public function get_fields(): array {
		// Text fields definitions.
		$text_fields = array(
			'time_prefix_text'          => array(
				'label'           => esc_html__( 'Time Prefix Text', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'The first text will appear in with your time text.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'main_content',
				'dynamic_content' => 'text',
			),
			'time_suffix_text'          => array(
				'label'           => esc_html__( 'Time Suffix Text', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'The last text will appear in with your time text.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'main_content',
				'dynamic_content' => 'text',
			),
			'time_suffix_text_singular' => array(
				'label'           => esc_html__( 'Time Suffix Text (Singular)', 'squad-modules-for-divi' ),
				'description'     => esc_html__( 'The last text will appear in with your time text.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'tab_slug'        => 'general',
				'toggle_slug'     => 'main_content',
				'dynamic_content' => 'text',
			),
			'time_text_tag'             => Utils::add_select_box_field(
				esc_html__( 'Time Text Tag', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Choose a tag to display with your time text.', 'squad-modules-for-divi' ),
					'options'          => Utils::get_html_tag_elements(),
					'default_on_front' => 'div',
					'default'          => 'div',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'main_content',
				)
			),
		);

		// Time general settings.
		$general_settings = array(
			'words_per_minute'           => Utils::add_range_field(
				esc_html__( 'Words Per Minute', 'squad-modules-for-divi' ),
				array(
					'description'       => esc_html__( 'Here you can choose how much words you would like to count in a minute.', 'squad-modules-for-divi' ),
					'type'              => 'range',
					'range_settings'    => array(
						'min_limit' => '1',
						'min'       => '1',
						'max_limit' => '100',
						'max'       => '100',
						'step'      => '1',
					),
					'default'           => 250,
					'number_validation' => true,
					'fixed_range'       => true,
					'unitless'          => true,
					'hover'             => false,
					'mobile_options'    => false,
					'responsive'        => false,
					'tab_slug'          => 'general',
					'toggle_slug'       => 'reading_settings',
				)
			),
			'calculate_comments__enable' => Utils::add_yes_no_field(
				esc_html__( 'Include Comments', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can choose whether or not include comments in reading time.', 'squad-modules-for-divi' ),
					'default_on_front' => 'off',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'reading_settings',
				)
			),
			'calculate_images__enable'   => Utils::add_yes_no_field(
				esc_html__( 'Include Images', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Here you can choose whether or not show the form title.', 'squad-modules-for-divi' ),
					'default_on_front' => 'off',
					'affects'          => array(
						'calculate_images_count',
					),
					'tab_slug'         => 'general',
					'toggle_slug'      => 'reading_settings',
				)
			),
			'calculate_images_count'     => Utils::add_range_field(
				esc_html__( 'Images Count', 'squad-modules-for-divi' ),
				array(
					'description'       => esc_html__( 'Here you can choose how much images you would like to count in a minute.', 'squad-modules-for-divi' ),
					'type'              => 'range',
					'range_settings'    => array(
						'min_limit' => '1',
						'min'       => '1',
						'max_limit' => '100',
						'max'       => '100',
						'step'      => '1',
					),
					'default'           => 4,
					'number_validation' => true,
					'fixed_range'       => true,
					'unitless'          => true,
					'hover'             => false,
					'mobile_options'    => false,
					'responsive'        => false,
					'tab_slug'          => 'general',
					'toggle_slug'       => 'reading_settings',
				)
			),
		);

		// Text associate fields definitions.
		$time_background_fields = $this->squad_utils->add_background_field(
			array(
				'label'       => esc_html__( 'Time Background', 'squad-modules-for-divi' ),
				'base_name'   => 'time_background',
				'context'     => 'time_background_color',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'time_element',
			)
		);
		$text_associated_fields = array(
			'time_horizontal_alignment' => Utils::add_alignment_field(
				esc_html__( 'Alignment', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Align icon to the left, right or center.', 'squad-modules-for-divi' ),
					'default_on_front' => 'left',
					'tab_slug'         => 'advanced',
					'toggle_slug'      => 'time_element',
				)
			),
			'time_text_gap'             => Utils::add_range_field(
				esc_html__( 'Gap Between Texts', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Here you can choose gap between texts.', 'squad-modules-for-divi' ),
					'range_settings' => array(
						'min'       => '0',
						'max'       => '200',
						'step'      => '1',
						'min_limit' => '0',
						'max_limit' => '200',
					),
					'default'        => '10px',
					'default_unit'   => 'px',
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'time_element',
					'mobile_options' => true,
				),
				array( 'use_hover' => false )
			),
			'time_margin'               => Utils::add_margin_padding_field(
				esc_html__( 'Time Margin', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Here you can define a custom margin size for the time.', 'squad-modules-for-divi' ),
					'type'           => 'custom_margin',
					'range_settings' => array(
						'min'  => '1',
						'max'  => '100',
						'step' => '1',
					),
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'time_element',
				)
			),
			'time_padding'              => Utils::add_margin_padding_field(
				esc_html__( 'Time Padding', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Here you can define a custom padding size.', 'squad-modules-for-divi' ),
					'type'           => 'custom_padding',
					'range_settings' => array(
						'min'  => '1',
						'max'  => '100',
						'step' => '1',
					),
					'default'        => '10px|25px|10px|25px|false|false',
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'time_element',
				)
			),
		);

		$divider_fields = $this->squad_utils->divider->get_fields(
			array(
				'toggle_slug' => 'time_divider',
			)
		);

		return array_merge(
			$text_fields,
			$general_settings,
			$time_background_fields,
			$text_associated_fields,
			$divider_fields
		);
	}

	/**
	 * Get CSS fields transition.
	 *
	 * Add form field options group and background image on the field list.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array<string, string>>
	 */
	public function get_transition_fields_css_props(): array {
		$fields = parent::get_transition_fields_css_props();
		// time styles.
		$fields['time_background_color'] = array( 'background' => "$this->main_css_element div .time-text-wrapper .time-text-container " );
		$fields['time_margin']           = array( 'margin' => "$this->main_css_element div .time-text-wrapper .time-text-container" );
		$fields['time_padding']          = array( 'padding' => "$this->main_css_element div .time-text-wrapper .time-text-container" );
		Utils::fix_border_transition( $fields, 'time_element', "$this->main_css_element div .time-text-wrapper .time-text-container " );
		Utils::fix_box_shadow_transition( $fields, 'time_element', "$this->main_css_element div .time-text-wrapper .time-text-container" );

		// divider styles.
		$fields['divider_color']  = array( 'border-top-color' => "$this->main_css_element div .time-text-wrapper .time-text-item.time-divider-element:before" );
		$fields['divider_weight'] = array(
			'border-top-width' => "$this->main_css_element div .time-text-wrapper .time-text-item.time-divider-element:before",
			'height'           => "$this->main_css_element div .time-text-wrapper .time-text-item.time-divider-element:before",
		);

		// Default styles.
		$fields['background_layout'] = array( 'color' => "$this->main_css_element div .time-text-wrapper .time-text-container" );

		return $fields;
	}

	/**
	 * Renders the module output.
	 *
	 * @param array<string, string> $attrs       List of attributes.
	 * @param string                $content     Content being processed.
	 * @param string                $render_slug Slug of module that is used for rendering output.
	 *
	 * @return string
	 */
	public function render( $attrs, $content, $render_slug ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClassAfterLastUsed
		// Collect actual reading time.
		$reading_time_text = $this->squad_reading_time_text( array_merge( $attrs, $this->props ) );

		// Collect require data.
		$time_prefix_text     = $this->squad_render_time_optional_text( 'time_prefix_text', 'time-text-item time-text-prefix-element' );
		$time_suffix_text     = $this->squad_render_time_optional_text( 'time_suffix_text', 'time-text-item time-text-suffix-element' );
		$time_suffix_singular = $this->squad_render_time_optional_text( 'time_suffix_text_singular', 'time-text-item time-text-suffix-element' );

		// Update suffix text as per count.
		$updated_suffix_text = 2 < $reading_time_text ? $time_suffix_text : $time_suffix_singular;

		$time_level   = $this->prop( 'time_text_tag', 'div' );
		$time_divider = $this->squad_render_time_divider( $attrs );

		$this->squad_generate_additional_styles( $attrs );

		return sprintf(
			'<div class="time-text-wrapper et_pb_with_background"><%5$s class="time-text-container">%1$s%2$s%3$s</%5$s>%4$s</div>',
			wp_kses_post( $time_prefix_text ),
			wp_kses_post( $reading_time_text ),
			wp_kses_post( $updated_suffix_text ),
			wp_kses_post( $time_divider ),
			wp_kses_post( $time_level )
		);
	}

	/**
	 * Get the reading time text
	 *
	 * @param array<string, string> $attrs List of attributes.
	 *
	 * @return string
	 */
	private function squad_reading_time_text( array $attrs ): string {
		if ( in_the_loop() ) {
			// Get the reading time.
			$time_text = $this->squad_calculate_reading_time( get_the_ID(), $attrs );

			return sprintf( '<div class="time-text-item time-text-element" data-text="%1$s"></div>', esc_attr( $time_text ) );
		}

		return '';
	}

	/**
	 * Calculate the reading time of a post.
	 *
	 * Gets the post-content, counts the images, strips shortcodes, and strips tags.
	 * Then count the words. Converts images into a word count and outputs the total reading time.
	 *
	 * @param int                   $post  The Post ID.
	 * @param array<string, string> $attrs List of attributes.
	 *
	 * @return false|float|string The total reading time for the article or string if it's 0.
	 */
	public function squad_calculate_reading_time( int $post, array $attrs ) {
		$current_post_type  = get_post_type();
		$comment_word_count = 0;
		if ( ( 'post' === $current_post_type ) && in_the_loop() && is_singular() ) {
			$args           = array( 'post_id' => $post );  // use post_id, not post_ID.
			$comments       = get_comments( $args );
			$comment_string = '';

			if ( is_array( $comments ) && count( $comments ) > 0 ) {
				/* @var \WP_Comment[] $comments */
				foreach ( $comments as $comment ) {
					if ( ! $comment instanceof \WP_Comment ) {
						continue;
					}

					// Get the comment content and strip it of HTML tags.
					$comment_string .= ' ' . wp_strip_all_tags( $comment->comment_content );
				}

				$comment_word_count = Str::word_count( $comment_string );
			}
		}

		$post_content     = get_post_field( 'post_content', $post );
		$number_of_images = substr_count( strtolower( $post_content ), '<img ' );
		$post_content     = wp_strip_all_tags( $post_content );
		$word_count       = Str::word_count( $post_content );

		if ( isset( $attrs['calculate_comments__enable'] ) && 'on' === $attrs['calculate_comments__enable'] ) {
			$word_count += $comment_word_count;
		}

		// Calculate additional time added to post by images.
		$additional_words_for_images = $this->squad_calculate_images( $number_of_images, $attrs['words_per_minute'] );

		if ( isset( $attrs['calculate_images__enable'] ) && 'yes' === $attrs['calculate_images__enable'] ) {
			$word_count += $additional_words_for_images;
		}

		if ( $word_count > $attrs['words_per_minute'] ) {
			$reading_time = ceil( $word_count / $attrs['words_per_minute'] );
		} else {
			$reading_time = $word_count / $attrs['words_per_minute'];
		}

		// If the reading time is 0 then return it as < 1 instead of 0.
		if ( 1 > $reading_time ) {
			$reading_time = '< 1';
		}

		return $reading_time;
	}

	/**
	 * Adds additional reading time for images.
	 *
	 * @since 1.1.0
	 *
	 * @param int   $total_images     number of images in post.
	 * @param array $words_per_minute words per minute.
	 *
	 * @return int Additional time added to the reading time by images.
	 */
	public function squad_calculate_images( int $total_images, array $words_per_minute ) {
		$additional_time = 0;

		// For the first image adds 12 seconds, the second image adds 11, ..., for image 10+ add 3 seconds.
		for ( $i = 1; $i <= $total_images; $i++ ) {
			if ( $i >= 10 ) {
				$additional_time += 3 * absint( $words_per_minute ) / 60;
			} else {
				$additional_time += ( 12 - ( $i - 1 ) ) * absint( $words_per_minute ) / 60;
			}
		}

		return $additional_time;
	}

	/**
	 * Render time infix
	 *
	 * @param string $attribute    The text attribute name.
	 * @param string $css_selector The stylesheet selector for the attribute.
	 *
	 * @return string
	 */
	private function squad_render_time_optional_text( string $attribute, string $css_selector ): string {
		if ( empty( $this->prop( $attribute, '' ) ) ) {
			return '';
		}

		return sprintf( '<div class="%1$s" data-text="%2$s"></div>', esc_attr( $css_selector ), esc_attr( $this->prop( $attribute, '' ) ) );
	}

	/**
	 * Render time suffix
	 *
	 * @param array<string, string> $attrs List of unprocessed attributes.
	 *
	 * @return string
	 */
	private function squad_render_time_divider( array $attrs ): string {
		if ( 'on' === $this->prop( 'show_divider', 'off' ) ) {
			// Fixed: a custom background doesn't work at frontend.
			$this->props = array_merge( $attrs, $this->props );

			$time_divider_classes = array(
				'time-text-item',
				'time-text-divider-element',
				$this->prop( 'divider_position', 'bottom' ),
			);

			$this->squad_utils->generate_divider_styles(
				array(
					'selector'  => "$this->main_css_element div .time-text-wrapper .time-item.time-divider-element:before",
					'important' => true,
				)
			);

			return sprintf(
				' <span class="%1$s"></span>',
				wp_kses_post( implode( ' ', $time_divider_classes ) )
			);
		}

		return '';
	}

	/**
	 * Renders additional styles for the module output.
	 *
	 * @param array<string, string> $attrs List of attributes.
	 */
	private function squad_generate_additional_styles( array $attrs ): void {
		// Fixed: the custom background doesn't work at frontend.
		$this->props = array_merge( $attrs, $this->props );

		// Wrapper background with default, responsive, hover.
		et_pb_background_options()->get_background_style(
			array(
				'base_prop_name'         => 'time_background',
				'props'                  => $this->props,
				'selector'               => "$this->main_css_element div .time-text-wrapper .time-text-container",
				'selector_hover'         => "$this->main_css_element div .time-text-wrapper .time-text-container:hover",
				'selector_sticky'        => "$this->main_css_element div .time-text-wrapper .time-text-container",
				'function_name'          => $this->slug,
				'use_background_video'   => false,
				'use_background_pattern' => false,
				'use_background_mask'    => false,
				'prop_name_aliases'      => array(
					'use_time_background_color_gradient' => 'time_background_use_color_gradient',
					'time_background'                    => 'time_background_color',
				),
			)
		);

		// Wrapper margin with default, responsive, hover.
		$this->squad_utils->generate_margin_padding_styles(
			array(
				'field'        => 'time_margin',
				'selector'     => "$this->main_css_element div .time-text-wrapper .time-text-container",
				'hover'        => "$this->main_css_element div .time-text-wrapper .time-text-container:hover",
				'css_property' => 'margin',
				'type'         => 'margin',
			)
		);
		// Wrapper padding with default, responsive, hover.
		$this->squad_utils->generate_margin_padding_styles(
			array(
				'field'        => 'time_padding',
				'selector'     => "$this->main_css_element div .time-text-wrapper .time-text-container",
				'hover'        => "$this->main_css_element div .time-text-wrapper .time-text-container:hover",
				'css_property' => 'padding',
				'type'         => 'padding',
			)
		);
	}
}
