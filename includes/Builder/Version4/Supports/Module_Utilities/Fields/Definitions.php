<?php // phpcs:ignore WordPress.Files.FileName

namespace DiviSquad\Builder\Version4\Supports\Module_Utilities\Fields;

use DiviSquad\Builder\Version4\Abstracts\Module_Utility;
use ET_Global_Settings;
use function apply_filters;
use function wp_array_slice_assoc;
use function wp_parse_args;

/**
 * Field Definitions Class
 *
 * Provides utility methods for defining form field structures in modules.
 *
 * @since   1.5.0
 * @package DiviSquad
 */
class Definitions extends Module_Utility {
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

		/**
		 * Filter the button fields default configuration options.
		 *
		 * @since 3.0.0
		 *
		 * @param array $defaults The default configuration options for button fields.
		 * @param array $options  The options passed to the function.
		 */
		$defaults = apply_filters( 'divi_squad_button_fields_defaults', $defaults, $options );

		$config             = wp_parse_args( $options, $defaults );
		$base_name          = $config['base_attr_name'];
		$fields_after_text  = $config['fields_after_text'];
		$fields_after_image = $config['fields_after_image'];

		/**
		 * Filter the button fields configuration after applying the defaults.
		 *
		 * @since 3.0.0
		 *
		 * @param array $config  The parsed configuration options.
		 * @param array $options The original options passed to the function.
		 */
		$config = apply_filters( 'divi_squad_button_fields_config', $config, $options );

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
		$button_text_field = array_merge_recursive(
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

		/**
		 * Filter the button text field configuration.
		 *
		 * @since 3.0.0
		 *
		 * @param array $button_text_field The button text field configuration.
		 * @param array $config            The processed configuration options.
		 * @param array $conditions        The conditional display settings.
		 */
		$button_text_field = apply_filters( 'divi_squad_button_text_field', $button_text_field, $config, $conditions );

		$button_icon_fields = array(
			"{$base_name}_icon_type" => array_merge_recursive(
				$conditions,
				divi_squad()->d4_module_helper->add_select_box_field(
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

		/**
		 * Filter the button icon fields configuration.
		 *
		 * @since 3.0.0
		 *
		 * @param array $button_icon_fields The button icon fields configuration.
		 * @param array $config             The processed configuration options.
		 * @param array $conditions         The conditional display settings.
		 */
		$button_icon_fields = apply_filters( 'divi_squad_button_icon_fields', $button_icon_fields, $config, $conditions );

		$button_fields = array_merge(
			$button_text_field,
			$fields_after_text,
			$button_icon_fields,
			$fields_after_image
		);

		/**
		 * Filter the button fields combined configuration.
		 *
		 * @since 3.0.0
		 *
		 * @param array $button_fields The combined button fields configuration.
		 * @param array $config        The processed configuration options.
		 */
		$button_fields = apply_filters( 'divi_squad_button_fields_combined', $button_fields, $config );

		$all_fields = array_merge(
			$button_fields,
			$this->get_button_associated_fields( $config )
		);

		/**
		 * Filter the complete button fields configuration.
		 *
		 * @since 3.0.0
		 *
		 * @param array $all_fields The complete button fields configuration.
		 * @param array $config     The processed configuration options.
		 */
		return apply_filters( 'divi_squad_button_fields', $all_fields, $config );
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

		/**
		 * Filter the button associated fields default configuration options.
		 *
		 * @since 3.0.0
		 *
		 * @param array $defaults The default configuration options for button associated fields.
		 * @param array $options  The options passed to the function.
		 */
		$defaults = apply_filters( 'divi_squad_button_associated_fields_defaults', $defaults, $options );

		$config    = wp_parse_args( $options, $defaults );
		$base_name = $config['base_attr_name'];

		/**
		 * Filter the button associated fields configuration after applying the defaults.
		 *
		 * @since 3.0.0
		 *
		 * @param array $config  The parsed configuration options.
		 * @param array $options The original options passed to the function.
		 */
		$config = apply_filters( 'divi_squad_button_associated_fields_config', $config, $options );

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
				$this->module->generate_background_options(
					"{$base_name}_background",
					'color',
					'advanced',
					$config['toggle_slug'],
					"{$base_name}_background_color"
				),
				$this->module->generate_background_options(
					"{$base_name}_background",
					'gradient',
					'advanced',
					$config['toggle_slug'],
					"{$base_name}_background_color"
				),
				$this->module->generate_background_options(
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

		/**
		 * Filter the button background defaults.
		 *
		 * @since 3.0.0
		 *
		 * @param array $background_defaults The button background default settings.
		 * @param array $config              The processed configuration options.
		 */
		$background_defaults = apply_filters( 'divi_squad_button_background_defaults', $background_defaults, $config );

		$background[ "{$base_name}_background_color" ] = array_merge_recursive(
			$conditions,
			$background_defaults
		);

		$background[ "{$base_name}_background_color" ]['background_fields'][ "{$base_name}_background_color" ]['default'] = $default_colors;

		$background = array_merge(
			$background,
			$this->module->generate_background_options(
				"{$base_name}_background",
				'skip',
				'advanced',
				$config['toggle_slug'],
				"{$base_name}_background_color"
			)
		);

		/**
		 * Filter the button background fields.
		 *
		 * @since 3.0.0
		 *
		 * @param array $background The button background fields.
		 * @param array $config     The processed configuration options.
		 */
		$background = apply_filters( 'divi_squad_button_background_fields', $background, $config );

		// Button fields definitions.
		$fields = array_merge(
			$background,
			$config['fields_after_background'],
			array(
				"{$base_name}_icon_color" => divi_squad()->d4_module_helper->add_color_field(
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
				"{$base_name}_icon_size"    => divi_squad()->d4_module_helper->add_range_field(
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
				"{$base_name}_image_width"  => divi_squad()->d4_module_helper->add_range_field(
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
				"{$base_name}_image_height" => divi_squad()->d4_module_helper->add_range_field(
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
				"{$base_name}_icon_gap"     => divi_squad()->d4_module_helper->add_range_field(
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
				"{$base_name}_icon_placement"       => divi_squad()->d4_module_helper->add_placement_field(
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
				"{$base_name}_icon_on_hover"        => divi_squad()->d4_module_helper->add_yes_no_field(
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
				"{$base_name}_icon_hover_move_icon" => divi_squad()->d4_module_helper->add_yes_no_field(
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
				"{$base_name}_custom_width"       => divi_squad()->d4_module_helper->add_yes_no_field(
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
				"{$base_name}_width"              => divi_squad()->d4_module_helper->add_range_field(
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
				"{$base_name}_elements_alignment" => divi_squad()->d4_module_helper->add_alignment_field(
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
				"{$base_name}_icon_margin" => divi_squad()->d4_module_helper->add_margin_padding_field(
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
				"{$base_name}_margin"      => divi_squad()->d4_module_helper->add_margin_padding_field(
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
				"{$base_name}_padding"     => divi_squad()->d4_module_helper->add_margin_padding_field(
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

		/**
		 * Filter the button associated fields.
		 *
		 * @since 3.0.0
		 *
		 * @param array $fields The button associated fields configuration.
		 * @param array $config The processed configuration options.
		 */
		return apply_filters( 'divi_squad_button_associated_fields', $fields, $config );
	}

	/**
	 * Adds a background field configuration for Divi modules.
	 *
	 * @since 1.0.0
	 *
	 * @param array $properties Properties for the background field.
	 *
	 * @return array The background field configuration array.
	 */
	public function add_background_field( array $properties = array() ): array {
		[ $label, $base_name, $context, $tab_slug, $toggle_slug ] = $this->get_background_field_options( $properties );

		/**
		 * Filter the background field options.
		 *
		 * @since 3.0.0
		 *
		 * @param array $options    The background field options.
		 * @param array $properties The properties passed to the function.
		 */
		$options = apply_filters(
			'divi_squad_background_field_options',
			array(
				'label'       => $label,
				'base_name'   => $base_name,
				'context'     => $context,
				'tab_slug'    => $tab_slug,
				'toggle_slug' => $toggle_slug,
			),
			$properties
		);

		$background_fields = array_merge_recursive(
			$this->module->generate_background_options( $options['base_name'], 'color', $options['tab_slug'], $options['toggle_slug'], $options['context'] ),
			$this->module->generate_background_options( $options['base_name'], 'gradient', $options['tab_slug'], $options['toggle_slug'], $options['context'] ),
			$this->module->generate_background_options( $options['base_name'], 'image', $options['tab_slug'], $options['toggle_slug'], $options['context'] )
		);

		/**
		 * Filter the background fields.
		 *
		 * @since 3.0.0
		 *
		 * @param array $background_fields The generated background fields.
		 * @param array $options           The processed options.
		 * @param array $properties        The properties passed to the function.
		 */
		$background_fields = apply_filters( 'divi_squad_background_fields', $background_fields, $options, $properties );

		return $this->add_background_fields( $properties, $background_fields );
	}

	/**
	 * Gets background field options for Divi modules.
	 *
	 * @since 1.0.0
	 *
	 * @param array $properties Additional properties for the field.
	 *
	 * @return array Background field options.
	 */
	public function get_background_field_options( array $properties = array() ): array {
		$label       = $properties['label'] ?? '';
		$base_name   = $properties['base_name'] ?? '_background';
		$context     = $properties['context'] ?? '_background_color';
		$tab_slug    = $properties['tab_slug'] ?? 'advanced';
		$toggle_slug = $properties['toggle_slug'] ?? 'background';

		return array( $label, $base_name, $context, $tab_slug, $toggle_slug );
	}

	/**
	 * Adds all background fields for Divi modules.
	 *
	 * @since 1.0.0
	 *
	 * @param array $properties        Additional properties for the field.
	 * @param array $background_fields The additional background fields for the current field.
	 *
	 * @return array Complete background field configuration.
	 */
	protected function add_background_fields( array $properties = array(), array $background_fields = array() ): array {
		[ $label, $base_name, $context, $tab_slug, $toggle_slug ] = $this->get_background_field_options( $properties );

		$default_bg_color = ET_Global_Settings::get_value( 'all_buttons_bg_color' );
		$defaults         = array(
			'label'             => $label,
			'description'       => esc_html__( 'Customize the background of this element by adjusting the color, gradient, and image settings.', 'squad-modules-for-divi' ),
			'type'              => 'background-field',
			'base_name'         => $base_name,
			'context'           => $context,
			'option_category'   => 'layout',
			'custom_color'      => true,
			'default'           => $default_bg_color,
			'default_on_front'  => '',
			'tab_slug'          => $tab_slug,
			'toggle_slug'       => $toggle_slug,
			'background_fields' => $background_fields,
			'hover'             => 'tabs',
			'mobile_options'    => true,
			'sticky'            => true,
		);

		$conditions = wp_array_slice_assoc(
			$properties,
			array( 'depends_show_if', 'depends_show_if_not', 'show_if', 'show_if_not' )
		);

		$background_options = array(
			$context => array_merge_recursive( $conditions, $defaults ),
		);

		$background_options[ $context ]['background_fields'][ $context ]['default'] = $default_bg_color;

		return array_merge(
			$background_options,
			$this->module->generate_background_options( $base_name, 'skip', $tab_slug, $toggle_slug, $context )
		);
	}

	/**
	 * Adds a background gradient field configuration for Divi modules.
	 *
	 * @since 1.0.0
	 *
	 * @param array $properties Additional properties for the field.
	 *
	 * @return array The background gradient field configuration array.
	 */
	public function add_background_gradient_field( array $properties = array() ): array {
		[ , $base_name, $context, $tab_slug, $toggle_slug ] = $this->get_background_field_options( $properties );

		$background_fields = $this->module->generate_background_options( $base_name, 'gradient', $tab_slug, $toggle_slug, $context );

		return $this->add_background_fields( $properties, $background_fields );
	}
}
