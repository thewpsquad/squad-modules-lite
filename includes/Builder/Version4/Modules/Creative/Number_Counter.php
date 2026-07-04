<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Number Counter Module (Divi 4 shortcode).
 *
 * Renders a `.squad-counter` shell carrying the formatted END value plus data-*
 * config. The count-up animation is run client-side by `number-counter.ts`.
 *
 * @since   4.0.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Creative;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use DiviSquad\Builder\Shared\Modules\Creative\Number_Counter\Counter_Helper;
use DiviSquad\Builder\Version4\Abstracts\Module;
use function absint;
use function esc_attr;
use function esc_html__;
use function esc_url;
use function et_pb_get_extended_font_icon_value;
use function max;
use function min;
use function sprintf;
use function wp_enqueue_script;

/**
 * Number Counter Module class.
 *
 * @since 4.0.0
 */
class Number_Counter extends Module {

	/**
	 * Initiate Module.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function init(): void {
		$this->name      = esc_html__( 'Number Counter', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Number Counters', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'number-counter.svg' );

		$this->slug             = 'disq_number_counter';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.$this->slug";

		$this->child_title_var          = 'title';
		$this->child_title_fallback_var = 'admin_label';

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'number' => esc_html__( 'Number', 'squad-modules-for-divi' ),
					'title'  => esc_html__( 'Title', 'squad-modules-for-divi' ),
					'media'  => esc_html__( 'Icon / Image', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'number_text' => esc_html__( 'Number Text', 'squad-modules-for-divi' ),
					'affix_text'  => esc_html__( 'Prefix & Suffix', 'squad-modules-for-divi' ),
					'title_text'  => esc_html__( 'Title Text', 'squad-modules-for-divi' ),
					'media'       => esc_html__( 'Icon / Image', 'squad-modules-for-divi' ),
				),
			),
		);

		$this->advanced_fields = array(
			'fonts'          => array(
				'number_text' => divi_squad()->d4_module_helper->add_font_field(
					'',
					array(
						'font_size'   => array( 'default' => '40px' ),
						'css'         => array( 'main' => "$this->main_css_element .squad-counter__number" ),
						'tab_slug'    => 'advanced',
						'toggle_slug' => 'number_text',
					)
				),
				'affix_text'  => divi_squad()->d4_module_helper->add_font_field(
					'',
					array(
						'font_size'   => array( 'default' => '20px' ),
						'css'         => array( 'main' => "$this->main_css_element .squad-counter__prefix, $this->main_css_element .squad-counter__suffix" ),
						'tab_slug'    => 'advanced',
						'toggle_slug' => 'affix_text',
					)
				),
				'title_text'  => divi_squad()->d4_module_helper->add_font_field(
					'',
					array(
						'font_size'   => array( 'default' => '16px' ),
						'css'         => array( 'main' => "$this->main_css_element .squad-counter__title" ),
						'tab_slug'    => 'advanced',
						'toggle_slug' => 'title_text',
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
		return array(
			// Number.
			'start_number'        => array(
				'label'       => esc_html__( 'Start Number', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'The value the counter animates from.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '0',
				'tab_slug'    => 'general',
				'toggle_slug' => 'number',
			),
			'end_number'          => array(
				'label'       => esc_html__( 'End Number', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'The final value the counter animates to.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '100',
				'tab_slug'    => 'general',
				'toggle_slug' => 'number',
			),
			'number_prefix'       => array(
				'label'       => esc_html__( 'Prefix', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Text shown before the number (e.g. $).', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'tab_slug'    => 'general',
				'toggle_slug' => 'number',
			),
			'number_suffix'       => array(
				'label'       => esc_html__( 'Suffix', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Text shown after the number (e.g. %, +, K).', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'tab_slug'    => 'general',
				'toggle_slug' => 'number',
			),
			'thousands_separator' => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Thousands Separator', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Character used to group thousands.', 'squad-modules-for-divi' ),
					'options'     => array(
						'none'  => esc_html__( 'None', 'squad-modules-for-divi' ),
						'comma' => esc_html__( 'Comma (1,000)', 'squad-modules-for-divi' ),
						'dot'   => esc_html__( 'Dot (1.000)', 'squad-modules-for-divi' ),
						'space' => esc_html__( 'Space (1 000)', 'squad-modules-for-divi' ),
					),
					'default'     => 'comma',
					'tab_slug'    => 'general',
					'toggle_slug' => 'number',
				)
			),
			'decimal_separator'   => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Decimal Separator', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Character used before the decimals.', 'squad-modules-for-divi' ),
					'options'     => array(
						'dot'   => esc_html__( 'Dot (1.5)', 'squad-modules-for-divi' ),
						'comma' => esc_html__( 'Comma (1,5)', 'squad-modules-for-divi' ),
					),
					'default'     => 'dot',
					'tab_slug'    => 'general',
					'toggle_slug' => 'number',
				)
			),
			'decimal_places'      => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Decimal Places', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Number of digits after the decimal separator.', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '0', 'max' => '4', 'step' => '1' ),
					'default'        => '0',
					'unitless'       => true,
					'tab_slug'       => 'general',
					'toggle_slug'    => 'number',
				)
			),
			'duration'            => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Animation Duration', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'How long the count-up runs (ms).', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '100', 'max' => '10000', 'step' => '100' ),
					'default'        => '2000',
					'unitless'       => true,
					'tab_slug'       => 'general',
					'toggle_slug'    => 'number',
				)
			),
			'easing'              => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Easing', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Speed curve of the animation.', 'squad-modules-for-divi' ),
					'options'     => array(
						'linear'      => esc_html__( 'Linear', 'squad-modules-for-divi' ),
						'ease-in'     => esc_html__( 'Ease In', 'squad-modules-for-divi' ),
						'ease-out'    => esc_html__( 'Ease Out', 'squad-modules-for-divi' ),
						'ease-in-out' => esc_html__( 'Ease In Out', 'squad-modules-for-divi' ),
					),
					'default'     => 'ease-out',
					'tab_slug'    => 'general',
					'toggle_slug' => 'number',
				)
			),

			// Title.
			'title'               => array(
				'label'       => esc_html__( 'Title', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Caption shown with the number.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'tab_slug'    => 'general',
				'toggle_slug' => 'title',
			),
			'title_position'      => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Title Position', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Where the title sits relative to the number.', 'squad-modules-for-divi' ),
					'options'     => array(
						'above' => esc_html__( 'Above', 'squad-modules-for-divi' ),
						'below' => esc_html__( 'Below', 'squad-modules-for-divi' ),
					),
					'default'     => 'below',
					'tab_slug'    => 'general',
					'toggle_slug' => 'title',
				)
			),

			// Media.
			'use_media'           => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Use Icon / Image', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Show an icon or image alongside the number.', 'squad-modules-for-divi' ),
					'options'     => array(
						'none'  => esc_html__( 'None', 'squad-modules-for-divi' ),
						'icon'  => esc_html__( 'Icon', 'squad-modules-for-divi' ),
						'image' => esc_html__( 'Image', 'squad-modules-for-divi' ),
					),
					'default'     => 'none',
					'tab_slug'    => 'general',
					'toggle_slug' => 'media',
				)
			),
			'icon'                => array(
				'label'            => esc_html__( 'Choose an Icon', 'squad-modules-for-divi' ),
				'description'      => esc_html__( 'Pick the icon to display.', 'squad-modules-for-divi' ),
				'type'             => 'select_icon',
				'option_category'  => 'basic_option',
				'class'            => array( 'et-pb-font-icon' ),
				'default_on_front' => '',
				'show_if'          => array( 'use_media' => 'icon' ),
				'tab_slug'         => 'general',
				'toggle_slug'      => 'media',
			),
			'image'               => array(
				'label'              => esc_html__( 'Upload an Image', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Upload the image to display.', 'squad-modules-for-divi' ),
				'type'               => 'upload',
				'option_category'    => 'basic_option',
				'upload_button_text' => esc_html__( 'Upload an image', 'squad-modules-for-divi' ),
				'choose_text'        => esc_html__( 'Choose an Image', 'squad-modules-for-divi' ),
				'update_text'        => esc_html__( 'Set As Image', 'squad-modules-for-divi' ),
				'show_if'            => array( 'use_media' => 'image' ),
				'tab_slug'           => 'general',
				'toggle_slug'        => 'media',
			),
			'image_alt'           => array(
				'label'       => esc_html__( 'Image Alt Text', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Provide descriptive alt text for the counter image (used by screen readers).', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'show_if'     => array( 'use_media' => 'image' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'media',
			),
			'media_position'      => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Icon / Image Position', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Where the media sits relative to the number.', 'squad-modules-for-divi' ),
					'options'     => array(
						'above' => esc_html__( 'Above', 'squad-modules-for-divi' ),
						'left'  => esc_html__( 'Left', 'squad-modules-for-divi' ),
						'right' => esc_html__( 'Right', 'squad-modules-for-divi' ),
					),
					'default'     => 'above',
					'show_if_not' => array( 'use_media' => 'none' ),
					'tab_slug'    => 'general',
					'toggle_slug' => 'media',
				)
			),

			// Design — media.
			'icon_color'          => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Icon Color', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Color of the icon.', 'squad-modules-for-divi' ),
					'show_if'     => array( 'use_media' => 'icon' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'media',
				)
			),
			'media_size'          => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Icon / Image Size', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Size of the icon or image.', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '8', 'max' => '200', 'step' => '1' ),
					'default'        => '48px',
					'show_if_not'    => array( 'use_media' => 'none' ),
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'media',
				)
			),
			'media_spacing'       => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Media Spacing', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Gap between the media and the number.', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '0', 'max' => '80', 'step' => '1' ),
					'default'        => '12px',
					'show_if_not'    => array( 'use_media' => 'none' ),
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'media',
				)
			),
			'number_color'        => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Number Color', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Color of the animated number.', 'squad-modules-for-divi' ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'number_text',
				)
			),
			'content_alignment'   => divi_squad()->d4_module_helper->add_alignment_field(
				esc_html__( 'Content Alignment', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Horizontal alignment of the counter content.', 'squad-modules-for-divi' ),
					'type'        => 'text_align',
					'default'     => 'center',
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'number_text',
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

		$fields['number_color'] = array( 'color' => "$this->main_css_element .squad-counter__number" );
		$fields['icon_color']   = array( 'color' => "$this->main_css_element .squad-counter__icon" );
		divi_squad()->d4_module_helper->fix_fonts_transition( $fields, 'number_text', "$this->main_css_element .squad-counter__number" );
		divi_squad()->d4_module_helper->fix_fonts_transition( $fields, 'title_text', "$this->main_css_element .squad-counter__title" );

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
		wp_enqueue_script( 'squad-module-number-counter' );

		$use_media  = (string) $this->prop( 'use_media', 'none' );
		$media_html = '';
		if ( 'icon' === $use_media ) {
			$icon = (string) $this->prop( 'icon', '' );
			if ( '' !== $icon ) {
				$media_html = sprintf(
					'<span class="squad-counter__icon et-pb-icon">%1$s</span>',
					esc_attr( et_pb_get_extended_font_icon_value( $icon, true ) )
				);
			}
		} elseif ( 'image' === $use_media ) {
			$image     = (string) $this->prop( 'image', '' );
			$image_alt = (string) $this->prop( 'image_alt', '' );
			if ( '' !== $image ) {
				$media_html = sprintf( '<img class="squad-counter__icon" src="%1$s" alt="%2$s" />', esc_url( $image ), esc_attr( $image_alt ) );
			}
		}

		$config = array(
			'start'       => (string) (float) $this->prop( 'start_number', '0' ),
			'end'         => (string) (float) $this->prop( 'end_number', '100' ),
			'duration'    => (string) max( 100, min( 10000, absint( $this->prop( 'duration', '2000' ) ) ) ),
			'easing'      => Counter_Helper::is_valid_easing( (string) $this->prop( 'easing', 'ease-out' ) ) ? (string) $this->prop( 'easing', 'ease-out' ) : 'ease-out',
			'separator'   => Counter_Helper::separator_char( (string) $this->prop( 'thousands_separator', 'comma' ) ),
			'decimal_sep' => Counter_Helper::decimal_char( (string) $this->prop( 'decimal_separator', 'dot' ) ),
			'decimals'    => (string) max( 0, min( 4, absint( $this->prop( 'decimal_places', '0' ) ) ) ),
		);

		return Counter_Helper::build_shell(
			$config,
			$use_media,
			(string) $this->prop( 'media_position', 'above' ),
			(string) $this->prop( 'title', '' ),
			(string) $this->prop( 'title_position', 'below' ),
			(string) $this->prop( 'number_prefix', '' ),
			(string) $this->prop( 'number_suffix', '' ),
			$media_html
		);
	}
}
