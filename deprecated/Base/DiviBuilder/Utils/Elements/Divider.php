<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Divider Utils Helper
 *
 * @since      1.5.0
 * @deprecated 3.3.3
 * @package    DiviSquad
 * @author     The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Base\DiviBuilder\Utils\Elements;

use ET_Global_Settings;
use function esc_html__;
use function et_builder_accent_color;
use function et_builder_get_border_styles;
use function et_builder_i18n;

/**
 * Divider Utils Helper Class
 *
 * @since      1.5.0
 * @deprecated 3.3.3
 * @package    DiviSquad
 */
class Divider {

	/**
	 * The instance of ET Builder Element.
	 *
	 * @var object
	 */
	public $module;

	/**
	 * Constructor.
	 *
	 * @param object $module The instance of ET Builder Element.
	 */
	public function __construct( $module ) {
		$this->module = $module;
	}

	/**
	 * Get show options for divider.
	 *
	 * @return array
	 */
	public function get_show_options() {
		return $this->module->squad_divider_show_options;
	}

	/**
	 * Get the default data.
	 *
	 * @param string $field The instance of ET Builder Element.
	 *
	 * @return string
	 */
	public function get_default( $field ) {
		return ! empty( $this->module->squad_divider_defaults[ $field ] ) ? $this->module->squad_divider_defaults[ $field ] : '';
	}

	/**
	 * Get the default data.
	 *
	 * @return array
	 */
	public function get_defaults() {
		return $this->module->squad_divider_defaults;
	}

	/**
	 * Get the default data for initiate.
	 *
	 * @return void The shape
	 */
	public function initiate_element() {
		$style_option_name    = sprintf( '%1$s-divider_style', $this->module->slug );
		$global_divider_style = ET_Global_Settings::get_value( $style_option_name );

		$position_option_name    = sprintf( '%1$s-divider_position', $this->module->slug );
		$global_divider_position = ET_Global_Settings::get_value( $position_option_name );

		$weight_option_name    = sprintf( '%1$s-divider_weight', $this->module->slug );
		$global_divider_weight = ET_Global_Settings::get_value( $weight_option_name );

		$this->module->squad_divider_defaults = array(
			'divider_style'    => ! empty( $global_divider_style ) ? $global_divider_style : 'solid',
			'divider_position' => ! empty( $global_divider_position ) ? $global_divider_position : 'bottom',
			'divider_weight'   => ! empty( $global_divider_weight ) ? $global_divider_weight : '2px',
		);

		// Show divider options are modifiable via customizer.
		$this->module->squad_divider_show_options = array(
			'off' => et_builder_i18n( 'No' ),
			'on'  => et_builder_i18n( 'Yes' ),
		);
	}

	/**
	 * Get the field for divider module
	 *
	 * @param array $options The options for divider module fields.
	 *
	 * @return array the field
	 */
	public function get_fields( $options = array() ) {
		// Collect toggle slug.
		$toggle_slug     = ! empty( $options['toggle_slug'] ) ? $options['toggle_slug'] : '';
		$toggle_slug_adv = ! empty( $options['toggle_slug_adv'] ) ? $options['toggle_slug_adv'] : $toggle_slug;

		$main_fields_defaults = array(
			'label'            => esc_html__( 'Show Divider', 'squad-modules-for-divi' ),
			'description'      => esc_html__( 'This settings turns on and off the 1px divider line, but does not affect the divider height.', 'squad-modules-for-divi' ),
			'default'          => 'on',
			'default_on_front' => 'on',
			'type'             => 'yes_no_button',
			'option_category'  => 'configuration',
			'options'          => $this->get_show_options(),
			'affects'          => array(
				'divider_color',
				'divider_style',
				'divider_position',
				'divider_weight',
				'divider_max_width',
				'divider_border_radius',
			),
			'tab_slug'         => 'general',
			'toggle_slug'      => $toggle_slug,
			'mobile_options'   => true,
		);

		return array(
			'show_divider'          => array_merge( $main_fields_defaults, $options ),
			'divider_color'         => array(
				'label'            => esc_html__( 'Line Color', 'squad-modules-for-divi' ),
				'description'      => esc_html__( 'This will adjust the color of the 1px divider line.', 'squad-modules-for-divi' ),
				'type'             => 'color-alpha',
				'default'          => et_builder_accent_color(),
				'default_on_front' => et_builder_accent_color(),
				'depends_show_if'  => 'on',
				'tab_slug'         => 'advanced',
				'toggle_slug'      => $toggle_slug_adv,
				'mobile_options'   => true,
				'sticky'           => true,
			),
			'divider_style'         => array(
				'label'            => esc_html__( 'Line Style', 'squad-modules-for-divi' ),
				'description'      => esc_html__( 'Select the shape of the dividing line used for the divider.', 'squad-modules-for-divi' ),
				'type'             => 'select',
				'option_category'  => 'layout',
				'options'          => et_builder_get_border_styles(),
				'depends_show_if'  => 'on',
				'tab_slug'         => 'advanced',
				'toggle_slug'      => $toggle_slug_adv,
				'default'          => $this->get_default( 'divider_style' ),
				'default_on_front' => $this->get_default( 'divider_style' ),
				'mobile_options'   => true,
			),
			'divider_position'      => array(
				'label'            => esc_html__( 'Line Position', 'squad-modules-for-divi' ),
				'description'      => esc_html__( 'The dividing line can be placed either above, below or in the center of the module.', 'squad-modules-for-divi' ),
				'type'             => 'select',
				'option_category'  => 'layout',
				'options'          => array(
					'top'    => et_builder_i18n( 'Top' ),
					'center' => esc_html__( 'Vertically Centered', 'squad-modules-for-divi' ),
					'bottom' => et_builder_i18n( 'Bottom' ),
				),
				'depends_show_if'  => 'on',
				'tab_slug'         => 'advanced',
				'toggle_slug'      => $toggle_slug_adv,
				'default'          => $this->get_default( 'divider_position' ),
				'default_on_front' => $this->get_default( 'divider_position' ),
				'mobile_options'   => true,
			),
			'divider_weight'        => array(
				'label'            => esc_html__( 'Divider Weight', 'squad-modules-for-divi' ),
				'description'      => esc_html__( 'Increasing the divider weight will increase the thickness of the dividing line.', 'squad-modules-for-divi' ),
				'type'             => 'range',
				'range_settings'   => array(
					'min'  => '1',
					'max'  => '100',
					'step' => '1',
				),
				'option_category'  => 'layout',
				'depends_show_if'  => 'on',
				'allowed_units'    => array( 'em', 'rem', 'px', 'cm', 'mm', 'in', 'pt', 'pc', 'ex', 'vh', 'vw' ),
				'default_unit'     => 'px',
				'default'          => $this->get_default( 'divider_weight' ),
				'default_on_front' => $this->get_default( 'divider_weight' ),
				'tab_slug'         => 'advanced',
				'toggle_slug'      => $toggle_slug_adv,
				'mobile_options'   => true,
				'sticky'           => true,
			),
			'divider_max_width'     => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Divider Max Width', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Here you can choose divider max width.', 'squad-modules-for-divi' ),
					'range_settings'  => array(
						'min'  => '1',
						'max'  => '1000',
						'step' => '1',
					),
					'allowed_units'   => array( '%', 'em', 'rem', 'px', 'cm', 'mm', 'in', 'pt', 'pc', 'ex', 'vh', 'vw' ),
					'allow_empty'     => true,
					'default_unit'    => 'px',
					'depends_show_if' => 'on',
					'tab_slug'        => 'advanced',
					'toggle_slug'     => $toggle_slug_adv,
				)
			),
			'divider_border_radius' => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Divider Border Radius', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Here you can choose divider border  radius.', 'squad-modules-for-divi' ),
					'range_settings'  => array(
						'min'  => '1',
						'max'  => '200',
						'step' => '1',
					),
					'allowed_units'   => array( '%', 'em', 'rem', 'px', 'cm', 'mm', 'in', 'pt', 'pc', 'ex', 'vh', 'vw' ),
					'allow_empty'     => true,
					'default_unit'    => 'px',
					'depends_show_if' => 'on',
					'tab_slug'        => 'advanced',
					'toggle_slug'     => $toggle_slug_adv,
				)
			),
		);
	}
}
