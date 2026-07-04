<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Builder Utils FieldsTrait
 *
 * @since   1.5.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Supports\Module_Helpers;

use function apply_filters;
use function esc_html__;
use function et_builder_i18n;
use function wp_parse_args;

/**
 * Fields Trait
 *
 * @since   1.5.0
 * @since   3.3.3 Migrate to the new structure.
 * @package DiviSquad
 */
trait Fields_Trait {

	/**
	 * Get HTML tag elements for text item.
	 *
	 * @return array<string, string>
	 */
	public function get_html_tag_elements(): array {
		$html_tags = array(
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

		/**
		 * Filter the HTML tag elements available for text items.
		 *
		 * @since 3.0.0
		 *
		 * @param array $html_tags Array of HTML tags and their labels.
		 */
		return apply_filters( 'divi_squad_html_tag_elements', $html_tags );
	}

	/**
	 * Default fields for Heading toggles.
	 *
	 * @param string $field_label The heading toggle label name.
	 * @param int    $priority    The toggle priority, default is 55.
	 *
	 * @return array
	 */
	public function get_heading_toggles( string $field_label, int $priority = 55 ): array {
		$toggles = array(
			'title'             => $field_label,
			'priority'          => $priority,
			'tabbed_subtoggles' => true,
			'sub_toggles'       => $this->get_heading_elements(),
		);

		/**
		 * Filter the heading toggles configuration.
		 *
		 * @since 3.0.0
		 *
		 * @param array  $toggles     The toggle configuration.
		 * @param string $field_label The field label used for the toggle.
		 * @param int    $priority    The toggle priority.
		 */
		return apply_filters( 'divi_squad_heading_toggles', $toggles, $field_label, $priority );
	}

	/**
	 * Get heading elements for toggles.
	 *
	 * @return string[][]
	 */
	public function get_heading_elements(): array {
		$elements = array(
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

		/**
		 * Filter the heading elements configuration for toggles.
		 *
		 * @since 3.0.0
		 *
		 * @param array $elements The heading elements configuration.
		 */
		return apply_filters( 'divi_squad_heading_elements', $elements );
	}

	/**
	 * Get Block elements for toggles.
	 *
	 * @return string[][]
	 */
	public function get_block_elements(): array {
		$elements = array(
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

		/**
		 * Filter the block elements configuration for toggles.
		 *
		 * @since 3.0.0
		 *
		 * @param array $elements The block elements configuration.
		 */
		return apply_filters( 'divi_squad_block_elements', $elements );
	}

	/**
	 * Add text clip settings.
	 *
	 * @param array $options The options for text clip fields.
	 *
	 * @return array
	 */
	public function get_text_clip_fields( array $options = array() ): array {
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

		/**
		 * Filter the text clip fields configuration options.
		 *
		 * @since 3.0.0
		 *
		 * @param array $config  The configuration options for text clip fields.
		 * @param array $options The original options passed to the function.
		 */
		$config = apply_filters( 'divi_squad_text_clip_config', $config, $options );

		$fields[ $config['base_attr_name'] . '_clip__enable' ]    = $this->add_yes_no_field(
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
		$fields[ $config['base_attr_name'] . '_bg_clip__enable' ] = $this->add_yes_no_field(
			esc_html__( 'Enable Background Clip', 'squad-modules-for-divi' ),
			array(
				'description'      => esc_html__( 'Here you can choose whether or not use background clip for the text.', 'squad-modules-for-divi' ),
				'default_on_front' => 'off',
				'depends_show_if'  => 'on',
				'tab_slug'         => $config['tab_slug'],
				'toggle_slug'      => $config['toggle_slug'],
			)
		);
		$fields[ $config['base_attr_name'] . '_fill_color' ]      = $this->add_color_field(
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
		$fields[ $config['base_attr_name'] . '_stroke_color' ]    = $this->add_color_field(
			esc_html__( 'Stroke Color', 'squad-modules-for-divi' ),
			array(
				'description'     => esc_html__( 'Pick a color to use.', 'squad-modules-for-divi' ),
				'depends_show_if' => 'on',
				'tab_slug'        => $config['tab_slug'],
				'toggle_slug'     => $config['toggle_slug'],
				'hover'           => 'tabs',
			)
		);
		$fields[ $config['base_attr_name'] . '_stroke_width' ]    = $this->add_range_field(
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

		/**
		 * Filter the text clip fields array.
		 *
		 * @since 3.0.0
		 *
		 * @param array $fields The text clip fields configuration.
		 * @param array $config The processed configuration options.
		 */
		return apply_filters( 'divi_squad_text_clip_fields', $fields, $config );
	}

	/**
	 * Add Z Index fields for element.
	 *
	 * @param array $options The options for z index fields.
	 *
	 * @return array
	 */
	public function add_z_index_field( array $options = array() ): array {
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
			'tab_slug'            => 'custom_css',
			'attr_name'           => 'z_index',
		);
		$config   = wp_parse_args( $options, $defaults );

		/**
		 * Filter the z-index field configuration options.
		 *
		 * @since 3.0.0
		 *
		 * @param array $config  The configuration options for z-index field.
		 * @param array $options The original options passed to the function.
		 */
		$config = apply_filters( 'divi_squad_z_index_config', $config, $options );

		// Set label.
		$label = empty( $config['label'] ) ? __( 'Z Index', 'squad-modules-for-divi' ) : $config['label'];
		if ( ! empty( $config['label_prefix'] ) ) {
			$label = $config['label_prefix'] . ' ' . $label;
		}

		// Set description.
		$description = empty( $config['description'] )
			? __( 'Here you can control element position on the z axis. Elements with higher z-index values will sit atop elements with lower z-index values.', 'squad-modules-for-divi' )
			: $config['description'];

		$field = array(
			$config['attr_name'] => array(
				'label'          => $label,
				'description'    => $description,
				'type'           => 'range',
				'range_settings' => array(
					'min'  => - 1000,
					'max'  => 1000,
					'step' => 1,
				),
				'default'        => (string) $config['default'],
				'mobile_options' => true,
				'sticky'         => true,
				'hover'          => 'tabs',
				'tab_slug'       => $config['tab_slug'],
				'toggle_slug'    => empty( $config['toggle_slug'] ) ? 'z_index' : $config['toggle_slug'],
			),
		);

		// Sub-toggle.
		if ( ! empty( $config['sub_toggle'] ) ) {
			$field[ $config['attr_name'] ]['sub_toggle'] = $config['sub_toggle'];
		}

		// Conditional logic.
		if ( ! empty( $config['depends_show_if'] ) ) {
			$field[ $config['attr_name'] ]['depends_show_if'] = $config['depends_show_if'];
		}
		if ( ! empty( $config['depends_show_if_not'] ) ) {
			$field[ $config['attr_name'] ]['depends_show_if_not'] = $config['depends_show_if_not'];
		}
		if ( ! empty( $config['show_if'] ) ) {
			$field[ $config['attr_name'] ]['show_if'] = $config['show_if'];
		}
		if ( ! empty( $config['show_if_not'] ) ) {
			$field[ $config['attr_name'] ]['show_if_not'] = $config['show_if_not'];
		}

		/**
		 * Filter the z-index field definition.
		 *
		 * @since 3.0.0
		 *
		 * @param array $field  The field definition.
		 * @param array $config The processed configuration options.
		 */
		return apply_filters( 'divi_squad_z_index_field', $field, $config );
	}

	/**
	 *  Get general fields.
	 *
	 * @return array[]
	 */
	public function get_general_fields(): array {
		$general_fields = array(
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

		/**
		 * Filter the general fields configuration.
		 *
		 * @since 3.0.0
		 *
		 * @param array $general_fields The general fields configuration.
		 */
		return apply_filters( 'divi_squad_general_fields', $general_fields );
	}
}
