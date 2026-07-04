<?php // phpcs:ignore WordPress.Files.FileName
/**
 * Builder Utils Helper Class which help to the all module class
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   1.0.0
 */

namespace DiviSquad\Base\DiviBuilder\Utils;

use ET_Global_Settings;
use function esc_attr__;
use function esc_html__;
use function et_builder_get_acceptable_css_string_values;
use function et_builder_i18n;
use function wp_array_slice_assoc;
use function wp_parse_args;

/**
 * Fields class.
 *
 * @package DiviSquad
 * @since   1.0.0
 */
trait FieldsTrait {

	/**
	 * Get HTML tag elements for text item.
	 *
	 * @return array<string, string>
	 */
	public static function get_html_tag_elements(): array {
		return array(
			'h1'   => esc_html__( 'H1 tag', 'squad-modules-for-divi' ),
			'h2'   => esc_html__( 'H2 tag', 'squad-modules-for-divi' ),
			'h3'   => esc_html__( 'H3 tag', 'squad-modules-for-divi' ),
			'h4'   => esc_html__( 'H4 tag', 'squad-modules-for-divi' ),
			'h5'   => esc_html__( 'H5 tag', 'squad-modules-for-divi' ),
			'h6'   => esc_html__( 'H6 tag', 'squad-modules-for-divi' ),
			'p'    => esc_html__( 'P tag', 'squad-modules-for-divi' ),
			'span' => esc_html__( 'SPAN tag', 'squad-modules-for-divi' ),
			'div'  => esc_html__( 'DIV tag', 'squad-modules-for-divi' ),
		);
	}

	/**
	 * Default fields for Heading toggles.
	 *
	 * @param string $field_label The heading toggle label name.
	 * @param int    $priority    The toggle priority, default is 55.
	 *
	 * @return array
	 */
	public static function get_heading_toggles( string $field_label, int $priority = 55 ): array {
		return array(
			'title'             => $field_label,
			'priority'          => $priority,
			'tabbed_subtoggles' => true,
			'sub_toggles'       => self::get_heading_elements(),
		);
	}

	/**
	 * Get heading elements for toggles.
	 *
	 * @return string[][]
	 */
	public static function get_heading_elements(): array {
		return array(
			'h1' => array(
				'name' => 'H1',
				'icon' => 'text-h1',
			),
			'h2' => array(
				'name' => 'H2',
				'icon' => 'text-h2',
			),
			'h3' => array(
				'name' => 'H3',
				'icon' => 'text-h3',
			),
			'h4' => array(
				'name' => 'H4',
				'icon' => 'text-h4',
			),
			'h5' => array(
				'name' => 'H5',
				'icon' => 'text-h5',
			),
			'h6' => array(
				'name' => 'H6',
				'icon' => 'text-h6',
			),
		);
	}

	/**
	 * Get Block elements for toggles.
	 *
	 * @return string[][]
	 */
	public static function get_block_elements(): array {
		return array(
			'p'     => array(
				'name' => esc_html__( 'P', 'squad-modules-for-divi' ),
				'icon' => 'text-left',
			),
			'a'     => array(
				'name' => esc_html__( 'A', 'squad-modules-for-divi' ),
				'icon' => 'text-link',
			),
			'ul'    => array(
				'name' => esc_html__( 'UL', 'squad-modules-for-divi' ),
				'icon' => 'list',
			),
			'ol'    => array(
				'name' => esc_html__( 'OL', 'squad-modules-for-divi' ),
				'icon' => 'numbered-list',
			),
			'quote' => array(
				'name' => esc_html__( 'QUOTE', 'squad-modules-for-divi' ),
				'icon' => 'text-quote',
			),
		);
	}

	/**
	 * Add text clip settings.
	 *
	 * @param array $options The options for text clip fields.
	 *
	 * @return array
	 */
	public static function get_text_clip_fields( array $options = array() ): array {
		$fields   = array();
		$defaults = array(
			'title_prefix'        => '',
			'base_attr_name'      => '',
			'depends_show_if'     => '',
			'depends_show_if_not' => '',
			'show_if'             => '',
			'show_if_not'         => '',
			'toggle_slug'         => '',
			'sub_toggle'          => null,
			'priority'            => 30,
			'tab_slug'            => 'general',
		);
		$config   = wp_parse_args( $options, $defaults );

		$fields[ $config['base_attr_name'] . '_clip__enable' ]    = self::add_yes_no_field(
			esc_html__( 'Enable Clip', 'squad-modules-for-divi' ),
			array(
				'description'      => esc_html__( 'Here you can choose whether or not use clip for the text.', 'squad-modules-for-divi' ),
				'default_on_front' => 'off',
				'affects'          => array(
					$config['base_attr_name'] . '_bg_clip__enable',
					$config['base_attr_name'] . '_fill_color',
					$config['base_attr_name'] . '_stroke_color',
					$config['base_attr_name'] . '_stroke_width',
				),
				'tab_slug'         => $config['tab_slug'],
				'toggle_slug'      => $config['toggle_slug'],
			)
		);
		$fields[ $config['base_attr_name'] . '_bg_clip__enable' ] = self::add_yes_no_field(
			esc_html__( 'Enable Background Clip', 'squad-modules-for-divi' ),
			array(
				'description'      => esc_html__( 'Here you can choose whether or not use background clip for the text.', 'squad-modules-for-divi' ),
				'default_on_front' => 'off',
				'depends_show_if'  => 'on',
				'tab_slug'         => $config['tab_slug'],
				'toggle_slug'      => $config['toggle_slug'],
			)
		);
		$fields[ $config['base_attr_name'] . '_fill_color' ]      = self::add_color_field(
			esc_html__( 'Fill Color', 'squad-modules-for-divi' ),
			array(
				'description'     => esc_html__( 'Pick a color to use.', 'squad-modules-for-divi' ),
				'default'         => 'rgba(255,255,255,0)',
				'depends_show_if' => 'on',
				'tab_slug'        => $config['tab_slug'],
				'toggle_slug'     => $config['toggle_slug'],
				'hover'           => 'tabs',
			)
		);
		$fields[ $config['base_attr_name'] . '_stroke_color' ]    = self::add_color_field(
			esc_html__( 'Stroke Color', 'squad-modules-for-divi' ),
			array(
				'description'     => esc_html__( 'Pick a color to use.', 'squad-modules-for-divi' ),
				'depends_show_if' => 'on',
				'tab_slug'        => $config['tab_slug'],
				'toggle_slug'     => $config['toggle_slug'],
				'hover'           => 'tabs',
			)
		);
		$fields[ $config['base_attr_name'] . '_stroke_width' ]    = self::add_range_field(
			esc_html__( 'Stroke Width', 'squad-modules-for-divi' ),
			array(
				'description'    => esc_html__( 'Here you can choose stroke width.', 'squad-modules-for-divi' ),
				'range_settings' => array(
					'min'  => '1',
					'max'  => '100',
					'step' => '1',
				),
				'default'        => '1px',
				'default_unit'   => 'px',
				'tab_slug'       => $config['tab_slug'],
				'toggle_slug'    => $config['toggle_slug'],
				'hover'          => 'tabs',
				'mobile_options' => true,
			),
			array( 'use_hover' => false )
		);

		// add conditional settings if defined.
		if ( '' !== $config['show_if'] ) {
			$fields[ $config['base_attr_name'] . '_clip__enable' ]['show_if'] = $config['show_if'];
		}

		if ( '' !== $config['show_if_not'] ) {
			$fields[ $config['base_attr_name'] . '_clip__enable' ]['show_if_not'] = $config['show_if_not'];
		}

		if ( '' !== $config['depends_show_if'] ) {
			$fields[ $config['base_attr_name'] . '_clip__enable' ]['depends_show_if'] = $config['depends_show_if'];
		}

		if ( '' !== $config['depends_show_if_not'] ) {
			$fields[ $config['base_attr_name'] . '_clip__enable' ]['depends_show_if_not'] = $config['depends_show_if_not'];
		}

		return $fields;
	}

	/**
	 * Add Z Index fields for element.
	 *
	 * @param array $options The options for z index fields.
	 *
	 * @return array
	 */
	public static function add_z_index_field( array $options = array() ): array {
		$defaults = array(
			'label_prefix'        => '',
			'label'               => '',
			'description'         => '',
			'default'             => 0,
			'depends_show_if'     => '',
			'depends_show_if_not' => '',
			'show_if'             => '',
			'show_if_not'         => '',
			'toggle_slug'         => '',
			'sub_toggle'          => null,
			'tab_slug'            => 'general',
			'priority'            => 30,
		);
		$config   = wp_parse_args( $options, $defaults );

		// phpcs:disable WordPress.WP.I18n.MissingTranslatorsComment
		$i18n = array(
			'zindex' => array(
				'label'       => esc_html__( 'Z Index', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Here you can control element position on the z axis. Elements with higher z-index values will sit atop elements with lower z-index values.', 'squad-modules-for-divi' ),
			),
		);

		$field = array(
			'label'            => ! empty( $config['label'] ) ? $config['label'] : ( ( ! empty( $config['label_prefix'] ) ? $config['label_prefix'] . ' ' : '' ) . $i18n['zindex']['label'] ),
			'description'      => ! empty( $config['description'] ) ? $config['description'] : $i18n['zindex']['description'],
			'type'             => 'range',
			'range_settings'   => array(
				'min'  => - 500,
				'max'  => 500,
				'step' => 1,
			),
			'option_category'  => 'layout',
			'default'          => $config['default'],
			'default_on_child' => true,
			'tab_slug'         => $config['tab_slug'],
			'toggle_slug'      => $config['toggle_slug'],
			'sub_toggle'       => $config['sub_toggle'],
			'allowed_values'   => et_builder_get_acceptable_css_string_values( 'z-index' ),
			'unitless'         => true,
			'hover'            => 'tabs',
			'sticky'           => true,
			'responsive'       => true,
			'mobile_options'   => true,
		);

		if ( ! empty( $config['depends_show_if'] ) ) {
			$field['depends_show_if'] = $config['depends_show_if'];
		}
		if ( ! empty( $config['depends_show_if_not'] ) ) {
			$field['depends_show_if_not'] = $config['depends_show_if_not'];
		}
		if ( ! empty( $config['show_if'] ) ) {
			$field['show_if'] = $config['show_if'];
		}
		if ( ! empty( $config['show_if_not'] ) ) {
			$field['show_if_not'] = $config['show_if_not'];
		}

		return $field;
	}

	/**
	 *  Get general fields.
	 *
	 * @return array[]
	 */
	public static function get_general_fields(): array {
		return array(
			'admin_label'  => array(
				'label'           => et_builder_i18n( 'Admin Label' ),
				'description'     => esc_html__( 'This will change the label of the module in the builder for easy identification.', 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'configuration',
				'toggle_slug'     => 'admin_label',
			),
			'module_id'    => array(
				'label'           => esc_html__( 'CSS ID', 'squad-modules-for-divi' ),
				'description'     => esc_html__( "Assign a unique CSS ID to the element which can be used to assign custom CSS styles from within your child theme or from within Divi's custom CSS inputs.", 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'configuration',
				'tab_slug'        => 'custom_css',
				'toggle_slug'     => 'classes',
				'option_class'    => 'et_pb_custom_css_regular',
			),
			'module_class' => array(
				'label'           => esc_html__( 'CSS Class', 'squad-modules-for-divi' ),
				'description'     => esc_html__( "Assign any number of CSS Classes to the element, separated by spaces, which can be used to assign custom CSS styles from within your child theme or from within Divi's custom CSS inputs.", 'squad-modules-for-divi' ),
				'type'            => 'text',
				'option_category' => 'configuration',
				'tab_slug'        => 'custom_css',
				'toggle_slug'     => 'classes',
				'option_class'    => 'et_pb_custom_css_regular',
			),
		);
	}

	/**
	 *  Add button fields.
	 *
	 * @param array $options The options for button fields.
	 *
	 * @return array
	 */
	public function get_button_fields( array $options = array() ): array {
		$defaults = array(
			'title_prefix'                 => '',
			'base_attr_name'               => 'button',
			'button_icon'                  => '&#x4e;||divi||400',
			'button_image'                 => '',
			'fields_after_text'            => array(),
			'fields_after_image'           => array(),
			'fields_after_background'      => array(),
			'fields_after_colors'          => array(),
			'fields_before_margin'         => array(),
			'fields_before_icon_placement' => array(),
			'tab_slug'                     => 'general',
			'toggle_slug'                  => 'button_element',
			'sub_toggle'                   => null,
			'priority'                     => 30,
		);

		$config             = wp_parse_args( $options, $defaults );
		$base_name          = $config['base_attr_name'];
		$fields_after_text  = $config['fields_after_text'];
		$fields_after_image = $config['fields_after_image'];

		// Conditions.
		$conditions = wp_array_slice_assoc(
			$options,
			array(
				'depends_show_if',
				'depends_show_if_not',
				'show_if',
				'show_if_not',
			)
		);

		// Button fields definitions.
		$button_text_field  = array_merge_recursive(
			$conditions,
			array(
				"{$base_name}_text" => array(
					'label'           => esc_html__( 'Button', 'squad-modules-for-divi' ),
					'description'     => esc_html__( 'The text of your button will appear in with the module.', 'squad-modules-for-divi' ),
					'type'            => 'text',
					'option_category' => 'basic_option',
					'tab_slug'        => 'general',
					'toggle_slug'     => $config['toggle_slug'],
					'dynamic_content' => 'text',
					'hover'           => 'tabs',
					'mobile_options'  => true,
				),
			)
		);
		$button_icon_fields = array(
			"{$base_name}_icon_type" => array_merge_recursive(
				$conditions,
				self::add_select_box_field(
					esc_html__( 'Button Icon Type', 'squad-modules-for-divi' ),
					array(
						'description'      => esc_html__( 'Choose an icon type to display with your button.', 'squad-modules-for-divi' ),
						'options'          => array(
							'icon'  => esc_html__( 'Icon', 'squad-modules-for-divi' ),
							'image' => et_builder_i18n( 'Image' ),
							'none'  => esc_html__( 'None', 'squad-modules-for-divi' ),
						),
						'default_on_front' => 'icon',
						'affects'          => array(
							"{$base_name}_icon",
							"{$base_name}_image",
							"{$base_name}_icon_color",
							"{$base_name}_icon_size",
							"{$base_name}_image_width",
							"{$base_name}_image_height",
							"{$base_name}_icon_gap",
							"{$base_name}_icon_on_hover",
							"{$base_name}_icon_placement",
							"{$base_name}_icon_margin",
						),
						'tab_slug'         => 'general',
						'toggle_slug'      => $config['toggle_slug'],
					)
				)
			),
			"{$base_name}_icon"      => array(
				'label'            => esc_html__( 'Choose an icon', 'squad-modules-for-divi' ),
				'description'      => esc_html__( 'Choose an icon to display with your button.', 'squad-modules-for-divi' ),
				'type'             => 'select_icon',
				'option_category'  => 'basic_option',
				'class'            => array( 'et-pb-font-icon' ),
				'default_on_front' => ! empty( $config['button_icon'] ) ? '&#x4e;||divi||400' : '',
				'depends_show_if'  => 'icon',
				'tab_slug'         => 'general',
				'toggle_slug'      => $config['toggle_slug'],
				'hover'            => 'tabs',
				'mobile_options'   => true,
			),
			"{$base_name}_image"     => array(
				'label'              => et_builder_i18n( 'Image' ),
				'description'        => esc_html__( 'Upload an image to display at the top of your button.', 'squad-modules-for-divi' ),
				'type'               => 'upload',
				'option_category'    => 'basic_option',
				'upload_button_text' => et_builder_i18n( 'Upload an image' ),
				'choose_text'        => esc_attr__( 'Choose an Image', 'squad-modules-for-divi' ),
				'update_text'        => esc_attr__( 'Set As Image', 'squad-modules-for-divi' ),
				'depends_show_if'    => 'image',
				'tab_slug'           => 'general',
				'toggle_slug'        => $config['toggle_slug'],
				'hover'              => 'tabs',
				'dynamic_content'    => 'image',
				'mobile_options'     => true,
			),
		);
		$button_fields      = array_merge(
			$button_text_field,
			$fields_after_text,
			$button_icon_fields,
			$fields_after_image
		);

		return array_merge(
			$button_fields,
			$this->get_button_associated_fields( $config )
		);
	}

	/**
	 *  Add button associated fields.
	 *
	 * @param array $options The options for button fields.
	 *
	 * @return array
	 */
	public function get_button_associated_fields( array $options = array() ): array {
		$defaults = array(
			'title_prefix'                 => '',
			'base_attr_name'               => 'button',
			'button_icon'                  => '&#x4e;||divi||400',
			'button_image'                 => '',
			'fields_after_text'            => array(),
			'fields_after_image'           => array(),
			'fields_after_background'      => array(),
			'fields_after_colors'          => array(),
			'fields_before_icon_placement' => array(),
			'fields_before_margin'         => array(),
			'tab_slug'                     => 'general',
			'toggle_slug'                  => 'button_element',
			'sub_toggle'                   => null,
			'priority'                     => 30,
		);

		$config    = wp_parse_args( $options, $defaults );
		$base_name = $config['base_attr_name'];

		// Conditions.
		$conditions = wp_array_slice_assoc(
			$options,
			array(
				'depends_show_if',
				'depends_show_if_not',
				'show_if',
				'show_if_not',
			)
		);

		$background          = array();
		$default_colors      = ET_Global_Settings::get_value( 'all_buttons_bg_color' );
		$background_defaults = array(
			'label'             => sprintf(
				/* translators: Field Name */
				esc_html__( '%s Background', 'squad-modules-for-divi' ),
				$config['title_prefix']
			),
			'description'       => esc_html__( 'Adjust the background style of the button by customizing the background color, gradient, and image.', 'squad-modules-for-divi' ),
			'type'              => 'background-field',
			'base_name'         => "{$base_name}_background",
			'context'           => "{$base_name}_background_color",
			'option_category'   => 'button',
			'custom_color'      => true,
			'default'           => $default_colors,
			'default_on_front'  => '',
			'tab_slug'          => 'advanced',
			'toggle_slug'       => $config['toggle_slug'],
			'background_fields' => array_merge_recursive(
				$this->element->generate_background_options(
					"{$base_name}_background",
					'color',
					'advanced',
					$config['toggle_slug'],
					"{$base_name}_background_color"
				),
				$this->element->generate_background_options(
					"{$base_name}_background",
					'gradient',
					'advanced',
					$config['toggle_slug'],
					"{$base_name}_background_color"
				),
				$this->element->generate_background_options(
					"{$base_name}_background",
					'image',
					'advanced',
					$config['toggle_slug'],
					"{$base_name}_background_color"
				)
			),
			'hover'             => 'tabs',
			'mobile_options'    => true,
			'sticky'            => true,
		);

		$background[ "{$base_name}_background_color" ] = array_merge_recursive(
			$conditions,
			$background_defaults
		);

		$background[ "{$base_name}_background_color" ]['background_fields'][ "{$base_name}_background_color" ]['default'] = $default_colors;

		$background = array_merge(
			$background,
			$this->element->generate_background_options(
				"{$base_name}_background",
				'skip',
				'advanced',
				$config['toggle_slug'],
				"{$base_name}_background_color"
			)
		);

		// Button fields definitions.
		return array_merge(
			$background,
			$config['fields_after_background'],
			array(
				"{$base_name}_icon_color" => self::add_color_field(
					esc_html__( 'Icon Color', 'squad-modules-for-divi' ),
					array(
						'description'     => esc_html__( 'Here you can define a custom color for your button icon.', 'squad-modules-for-divi' ),
						'depends_show_if' => 'icon',
						'tab_slug'        => 'advanced',
						'toggle_slug'     => $config['toggle_slug'],
					)
				),
			),
			$config['fields_after_colors'],
			array(
				"{$base_name}_icon_size"    => self::add_range_field(
					esc_html__( 'Icon Size', 'squad-modules-for-divi' ),
					array(
						'description'     => esc_html__( 'Here you can choose icon size.', 'squad-modules-for-divi' ),
						'range_settings'  => array(
							'min'  => '1',
							'max'  => '200',
							'step' => '1',
						),
						'default'         => '16px',
						'default_unit'    => 'px',
						'tab_slug'        => 'advanced',
						'toggle_slug'     => $config['toggle_slug'],
						'depends_show_if' => 'icon',
					)
				),
				"{$base_name}_image_width"  => self::add_range_field(
					esc_html__( 'Image Width', 'squad-modules-for-divi' ),
					array(
						'description'     => esc_html__( 'Here you can choose image width.', 'squad-modules-for-divi' ),
						'range_settings'  => array(
							'min'  => '1',
							'max'  => '200',
							'step' => '1',
						),
						'default'         => '16px',
						'tab_slug'        => 'advanced',
						'toggle_slug'     => $config['toggle_slug'],
						'depends_show_if' => 'image',
					)
				),
				"{$base_name}_image_height" => self::add_range_field(
					esc_html__( 'Image Height', 'squad-modules-for-divi' ),
					array(
						'description'     => esc_html__( 'Here you can choose image height.', 'squad-modules-for-divi' ),
						'range_settings'  => array(
							'min'  => '1',
							'max'  => '200',
							'step' => '1',
						),
						'default'         => '16px',
						'depends_show_if' => 'image',
						'tab_slug'        => 'advanced',
						'toggle_slug'     => $config['toggle_slug'],
					)
				),
				"{$base_name}_icon_gap"     => self::add_range_field(
					esc_html__( 'Gap Between Icon/Image and Text', 'squad-modules-for-divi' ),
					array(
						'description'         => esc_html__( 'Here you can choose gap between icon and text.', 'squad-modules-for-divi' ),
						'range_settings'      => array(
							'min'  => '1',
							'max'  => '200',
							'step' => '1',
						),
						'default'             => '10px',
						'default_unit'        => 'px',
						'depends_show_if_not' => array( 'none' ),
						'tab_slug'            => 'advanced',
						'toggle_slug'         => $config['toggle_slug'],
						'mobile_options'      => true,
					),
					array( 'use_hover' => false )
				),
			),
			$config['fields_before_icon_placement'],
			array(
				"{$base_name}_icon_placement"       => self::add_placement_field(
					esc_html__( 'Icon Placement', 'squad-modules-for-divi' ),
					array(
						'description'         => esc_html__( 'Here you can choose where to place the icon.', 'squad-modules-for-divi' ),
						'options'             => array(
							'row-reverse' => et_builder_i18n( 'Left' ),
							'row'         => et_builder_i18n( 'Right' ),
						),
						'default_on_front'    => 'row',
						'depends_show_if_not' => array( 'none' ),
						'tab_slug'            => 'advanced',
						'toggle_slug'         => $config['toggle_slug'],
					)
				),
				"{$base_name}_icon_on_hover"        => self::add_yes_no_field(
					esc_html__( 'Show Icon On Hover', 'squad-modules-for-divi' ),
					array(
						'description'         => esc_html__( 'By default, button icon to always be displayed. If you would like button icon are displayed on hover, then you can enable this option.', 'squad-modules-for-divi' ),
						'default_on_front'    => 'off',
						'depends_show_if_not' => array( 'none' ),
						'affects'             => array(
							"{$base_name}_icon_hover_move_icon",
						),
						'tab_slug'            => 'advanced',
						'toggle_slug'         => $config['toggle_slug'],
					)
				),
				"{$base_name}_icon_hover_move_icon" => self::add_yes_no_field(
					esc_html__( 'Move Icon On Hover Only', 'squad-modules-for-divi' ),
					array(
						'description'      => esc_html__( 'By default, icon and text are both move on hover. If you would like button icon move on hover, then you can enable this option.', 'squad-modules-for-divi' ),
						'default_on_front' => 'off',
						'depends_show_if'  => 'on',
						'tab_slug'         => 'advanced',
						'toggle_slug'      => $config['toggle_slug'],
					)
				),
			),
			array(
				"{$base_name}_custom_width"       => self::add_yes_no_field(
					esc_html__( 'Resize Button', 'squad-modules-for-divi' ),
					array(
						'description'      => esc_html__( 'By default, the button element will be get default width. If you would like resize the button, then you can enable this option.', 'squad-modules-for-divi' ),
						'default_on_front' => 'off',
						'affects'          => array(
							"{$base_name}_width",
							"{$base_name}_elements_alignment",
						),
						'tab_slug'         => 'advanced',
						'toggle_slug'      => $config['toggle_slug'],
					)
				),
				"{$base_name}_width"              => self::add_range_field(
					esc_html__( 'Button Width', 'squad-modules-for-divi' ),
					array(
						'description'     => esc_html__( 'Adjust the width of the content within the button.', 'squad-modules-for-divi' ),
						'range_settings'  => array(
							'min'  => '0',
							'max'  => '1100',
							'step' => '1',
						),
						'allowed_units'   => array( '%', 'em', 'rem', 'px', 'cm', 'mm', 'in', 'pt', 'pc', 'ex', 'vh', 'vw' ),
						'allow_empty'     => true,
						'default_unit'    => 'px',
						'depends_show_if' => 'on',
						'tab_slug'        => 'advanced',
						'toggle_slug'     => $config['toggle_slug'],
					)
				),
				"{$base_name}_elements_alignment" => self::add_alignment_field(
					esc_html__( 'Button Elements Alignment', 'squad-modules-for-divi' ),
					array(
						'description'      => esc_html__( 'Align icon to the left, right or center.', 'squad-modules-for-divi' ),
						'type'             => 'text_align',
						'default_on_front' => 'left',
						'depends_show_if'  => 'on',
						'tab_slug'         => 'advanced',
						'toggle_slug'      => $config['toggle_slug'],
					)
				),
			),
			$config['fields_before_margin'],
			array(
				"{$base_name}_icon_margin" => self::add_margin_padding_field(
					esc_html__( 'Icon/Image Margin', 'squad-modules-for-divi' ),
					array(
						'description'         => esc_html__(
							'Here you can define a custom padding size.',
							'squad-modules-for-divi'
						),
						'type'                => 'custom_margin',
						'depends_show_if_not' => array( 'none' ),
						'tab_slug'            => 'advanced',
						'toggle_slug'         => $config['toggle_slug'],
					)
				),
				"{$base_name}_margin"      => self::add_margin_padding_field(
					esc_html__( 'Button Margin', 'squad-modules-for-divi' ),
					array(
						'description' => esc_html__(
							'Here you can define a custom margin size for the button.',
							'squad-modules-for-divi'
						),
						'type'        => 'custom_margin',
						'tab_slug'    => 'advanced',
						'toggle_slug' => $config['toggle_slug'],
					)
				),
				"{$base_name}_padding"     => self::add_margin_padding_field(
					esc_html__( 'Button Padding', 'squad-modules-for-divi' ),
					array(
						'description' => esc_html__( 'Here you can define a custom padding size.', 'squad-modules-for-divi' ),
						'type'        => 'custom_padding',
						'tab_slug'    => 'advanced',
						'toggle_slug' => $config['toggle_slug'],
					)
				),
			)
		);
	}
}
