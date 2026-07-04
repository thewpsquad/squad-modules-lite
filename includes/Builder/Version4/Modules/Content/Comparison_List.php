<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Comparison List Module (Divi 4 shortcode).
 *
 * Parent module accepting Comparison List Item children. Renders a
 * `.squad-comparison-list` grid of feature rows. Each child owns only its
 * `data-status` attribute, label, and nested content (see
 * `Comparison_List_Item`) — this parent owns the three status icons
 * (included / excluded / neutral) and their colors, and paints the correct
 * glyph onto every row via CSS attribute selectors keyed off `data-status`,
 * plus responsive column count, row gap, divider, and zebra-striping CSS.
 *
 * @since   4.4.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Content;

defined( 'ABSPATH' ) || exit;

use DiviSquad\Builder\Version4\Abstracts\Module;
use DiviSquad\Utils\Divi;
use function absint;
use function esc_attr;
use function esc_html__;
use function et_pb_get_extended_font_icon_value;
use function in_array;
use function max;
use function sprintf;

/**
 * Comparison List Module class.
 *
 * @since 4.4.0
 */
class Comparison_List extends Module {

	/**
	 * Allowed row status values (must match `Comparison_List_Item::STATUSES`).
	 *
	 * @since 4.4.0
	 *
	 * @var array<int, string>
	 */
	private const STATUSES = array( 'included', 'excluded', 'neutral' );

	/**
	 * Allowed row divider line style tokens (first = fallback).
	 *
	 * @since 4.4.0
	 *
	 * @var array<int, string>
	 */
	private const DIVIDER_STYLES = array( 'solid', 'dashed', 'dotted' );

	/**
	 * Allowed icon position tokens (first = fallback).
	 *
	 * @since 4.4.0
	 *
	 * @var array<int, string>
	 */
	private const ICON_POSITIONS = array( 'left', 'right' );

	/**
	 * Default (ETModules extended-icon value) for the included status: a
	 * check mark.
	 *
	 * @since 4.4.0
	 *
	 * @var string
	 */
	private const DEFAULT_INCLUDED_ICON = '&#x4e;||divi||400';

	/**
	 * Default (ETModules extended-icon value) for the excluded status: a
	 * cross / times.
	 *
	 * @since 4.4.0
	 *
	 * @var string
	 */
	private const DEFAULT_EXCLUDED_ICON = '&#x4d;||divi||400';

	/**
	 * Default (ETModules extended-icon value) for the neutral status: a
	 * minus / dash.
	 *
	 * @since 4.4.0
	 *
	 * @var string
	 */
	private const DEFAULT_NEUTRAL_ICON = '&#x4b;||divi||400';

	/**
	 * Initiate Module.
	 *
	 * @since 4.4.0
	 * @return void
	 */
	public function init(): void {
		$this->name      = esc_html__( 'Comparison List', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Comparison Lists', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'comparison-list.svg' );

		$this->slug             = 'disq_comparison_list';
		$this->child_slug       = 'disq_comparison_list_item';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.{$this->slug}";

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		$this->settings_modal_toggles = array(
			'general' => array(
				'toggles' => array(
					'icon_settings'   => esc_html__( 'Icons', 'squad-modules-for-divi' ),
					'layout_settings' => esc_html__( 'Layout', 'squad-modules-for-divi' ),
					'row_settings'    => esc_html__( 'Rows', 'squad-modules-for-divi' ),
				),
			),
		);

		$this->advanced_fields = array(
			'background'     => divi_squad()->d4_module_helper->selectors_background( $this->main_css_element ),
			'borders'        => array(
				'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			),
			'box_shadow'     => array(
				'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			),
			'margin_padding' => divi_squad()->d4_module_helper->selectors_margin_padding( $this->main_css_element ),
			'max_width'      => divi_squad()->d4_module_helper->selectors_max_width( $this->main_css_element ),
			'fonts'          => false,
			'image_icon'     => false,
			'text'           => false,
			'button'         => false,
			'filters'        => false,
		);

		$this->custom_css_fields = array(
			'list' => array(
				'label'    => esc_html__( 'List', 'squad-modules-for-divi' ),
				'selector' => '.squad-comparison-list',
			),
			'row'  => array(
				'label'    => esc_html__( 'Row', 'squad-modules-for-divi' ),
				'selector' => '.squad-comparison-list__item',
			),
		);
	}

	/**
	 * Define module fields.
	 *
	 * @since 4.4.0
	 * @return array<string, array<string, mixed>>
	 */
	public function get_fields(): array {
		return array(
			// Icons.
			'included_icon'       => array(
				'label'            => esc_html__( 'Included Icon', 'squad-modules-for-divi' ),
				'description'      => esc_html__( 'Icon shown on rows marked as included.', 'squad-modules-for-divi' ),
				'type'             => 'select_icon',
				'option_category'  => 'basic_option',
				'class'            => array( 'et-pb-font-icon' ),
				'default_on_front' => self::DEFAULT_INCLUDED_ICON,
				'default'          => self::DEFAULT_INCLUDED_ICON,
				'tab_slug'         => 'general',
				'toggle_slug'      => 'icon_settings',
			),
			'included_icon_color' => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Included Icon Color', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Color of the included-row icon.', 'squad-modules-for-divi' ),
					'default'     => '#2ecc71',
					'tab_slug'    => 'general',
					'toggle_slug' => 'icon_settings',
				)
			),
			'excluded_icon'       => array(
				'label'            => esc_html__( 'Excluded Icon', 'squad-modules-for-divi' ),
				'description'      => esc_html__( 'Icon shown on rows marked as excluded.', 'squad-modules-for-divi' ),
				'type'             => 'select_icon',
				'option_category'  => 'basic_option',
				'class'            => array( 'et-pb-font-icon' ),
				'default_on_front' => self::DEFAULT_EXCLUDED_ICON,
				'default'          => self::DEFAULT_EXCLUDED_ICON,
				'tab_slug'         => 'general',
				'toggle_slug'      => 'icon_settings',
			),
			'excluded_icon_color' => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Excluded Icon Color', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Color of the excluded-row icon.', 'squad-modules-for-divi' ),
					'default'     => '#e74c3c',
					'tab_slug'    => 'general',
					'toggle_slug' => 'icon_settings',
				)
			),
			'neutral_icon'        => array(
				'label'            => esc_html__( 'Neutral Icon', 'squad-modules-for-divi' ),
				'description'      => esc_html__( 'Icon shown on rows marked as neutral.', 'squad-modules-for-divi' ),
				'type'             => 'select_icon',
				'option_category'  => 'basic_option',
				'class'            => array( 'et-pb-font-icon' ),
				'default_on_front' => self::DEFAULT_NEUTRAL_ICON,
				'default'          => self::DEFAULT_NEUTRAL_ICON,
				'tab_slug'         => 'general',
				'toggle_slug'      => 'icon_settings',
			),
			'neutral_icon_color'  => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Neutral Icon Color', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Color of the neutral-row icon.', 'squad-modules-for-divi' ),
					'default'     => '#9aa0a6',
					'tab_slug'    => 'general',
					'toggle_slug' => 'icon_settings',
				)
			),
			'icon_size'           => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Icon Size', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Size applied to all three status icons.', 'squad-modules-for-divi' ),
					'range_settings' => array(
						'min'  => '10',
						'max'  => '60',
						'step' => '1',
					),
					'default'        => '20px',
					'mobile_options' => false,
					'hover'          => false,
					'tab_slug'       => 'general',
					'toggle_slug'    => 'icon_settings',
				)
			),
			'icon_position'       => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Icon Position', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Place the status icon before or after the feature label.', 'squad-modules-for-divi' ),
					'options'     => array(
						'left'  => esc_html__( 'Left', 'squad-modules-for-divi' ),
						'right' => esc_html__( 'Right', 'squad-modules-for-divi' ),
					),
					'default'     => 'left',
					'tab_slug'    => 'general',
					'toggle_slug' => 'icon_settings',
				)
			),
			// Layout.
			'columns'             => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Columns', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Number of feature-row columns.', 'squad-modules-for-divi' ),
					'range_settings' => array(
						'min'       => '1',
						'max'       => '4',
						'step'      => '1',
						'min_limit' => '1',
					),
					'default'        => '1',
					'unitless'       => true,
					'mobile_options' => true,
					'responsive'     => true,
					'hover'          => false,
					'tab_slug'       => 'general',
					'toggle_slug'    => 'layout_settings',
				)
			),
			'row_gap'             => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Row Gap', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Vertical space between feature rows.', 'squad-modules-for-divi' ),
					'range_settings' => array(
						'min'  => '0',
						'max'  => '80',
						'step' => '1',
					),
					'default'        => '12px',
					'mobile_options' => false,
					'hover'          => false,
					'tab_slug'       => 'general',
					'toggle_slug'    => 'layout_settings',
				)
			),
			// Rows.
			'row_divider'         => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Show Row Divider', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Draw a divider line between feature rows.', 'squad-modules-for-divi' ),
					'default'     => 'on',
					'affects'     => array( 'row_divider_color', 'row_divider_width', 'row_divider_style' ),
					'tab_slug'    => 'general',
					'toggle_slug' => 'row_settings',
				)
			),
			'row_divider_color'   => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Row Divider Color', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Color of the row divider line.', 'squad-modules-for-divi' ),
					'default'         => '#e5e7eb',
					'depends_show_if' => 'on',
					'tab_slug'        => 'general',
					'toggle_slug'     => 'row_settings',
				)
			),
			'row_divider_width'   => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Row Divider Width', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Thickness of the row divider line.', 'squad-modules-for-divi' ),
					'range_settings'  => array(
						'min'  => '1',
						'max'  => '10',
						'step' => '1',
					),
					'default'         => '1px',
					'mobile_options'  => false,
					'hover'           => false,
					'depends_show_if' => 'on',
					'tab_slug'        => 'general',
					'toggle_slug'     => 'row_settings',
				)
			),
			'row_divider_style'   => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Row Divider Style', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Line style of the row divider.', 'squad-modules-for-divi' ),
					'options'         => array(
						'solid'  => esc_html__( 'Solid', 'squad-modules-for-divi' ),
						'dashed' => esc_html__( 'Dashed', 'squad-modules-for-divi' ),
						'dotted' => esc_html__( 'Dotted', 'squad-modules-for-divi' ),
					),
					'default'         => 'solid',
					'depends_show_if' => 'on',
					'tab_slug'        => 'general',
					'toggle_slug'     => 'row_settings',
				)
			),
			'row_background'      => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Row Background Color', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Background color applied to every row.', 'squad-modules-for-divi' ),
					'default'     => '',
					'tab_slug'    => 'general',
					'toggle_slug' => 'row_settings',
				)
			),
			'row_background_alt'  => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Alternate Row Background Color', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Zebra-stripe background applied to every even row.', 'squad-modules-for-divi' ),
					'default'     => '',
					'tab_slug'    => 'general',
					'toggle_slug' => 'row_settings',
				)
			),
		);
	}

	/**
	 * Render the module HTML.
	 *
	 * @since 4.4.0
	 *
	 * @param array<string, mixed> $attrs       Module attributes.
	 * @param string               $content     Rendered child modules HTML.
	 * @param string               $render_slug Module render slug.
	 *
	 * @return string
	 */
	public function render( $attrs, $content, $render_slug ): string {
		$this->apply_layout_css( $render_slug );
		$this->apply_row_css( $render_slug );
		$this->apply_icon_css( $render_slug );

		$icon_position = (string) $this->prop( 'icon_position', 'left' );
		$icon_position = in_array( $icon_position, self::ICON_POSITIONS, true ) ? $icon_position : 'left';

		$divider = 'on' === (string) $this->prop( 'row_divider', 'on' ) ? 'on' : 'off';

		return sprintf(
			'<div class="squad-comparison-list squad-comparison-list--icon-%1$s" data-divider="%2$s">%3$s</div>',
			esc_attr( $icon_position ),
			esc_attr( $divider ),
			(string) $content
		);
	}

	/**
	 * Apply responsive column count, row gap, and icon size custom
	 * properties via `set_style()`. These are consumed by the static CSS as
	 * `var( --squad-cl-columns )`, etc., rather than being hardcoded here,
	 * so the same variable names can be shared with the Divi 5 SCSS.
	 *
	 * @since 4.4.0
	 *
	 * @param string $render_slug Module render slug.
	 *
	 * @return void
	 */
	public function apply_layout_css( string $render_slug ): void {
		$cols_desktop = max( 1, absint( $this->prop( 'columns', '1' ) ) );

		self::set_style(
			$render_slug,
			array(
				'selector'    => '%%order_class%% .squad-comparison-list',
				'declaration' => "--squad-cl-columns: {$cols_desktop};",
			)
		);

		$cols_tablet_raw = (string) $this->prop( 'columns_tablet', '' );
		if ( '' !== $cols_tablet_raw ) {
			$cols_tablet = max( 1, absint( $cols_tablet_raw ) );

			self::set_style(
				$render_slug,
				array(
					'selector'    => '%%order_class%% .squad-comparison-list',
					'declaration' => "--squad-cl-columns: {$cols_tablet};",
					'media_query' => self::get_media_query( 'max_width_980' ),
				)
			);
		}

		$cols_phone_raw = (string) $this->prop( 'columns_phone', '' );
		if ( '' !== $cols_phone_raw ) {
			$cols_phone = max( 1, absint( $cols_phone_raw ) );

			self::set_style(
				$render_slug,
				array(
					'selector'    => '%%order_class%% .squad-comparison-list',
					'declaration' => "--squad-cl-columns: {$cols_phone};",
					'media_query' => self::get_media_query( 'max_width_767' ),
				)
			);
		}

		// `sanitize_css_length()` is inherited from the V4 `Module` base
		// class and already implements the allowlist regex required for
		// any user-controlled value interpolated into generated CSS.
		$row_gap = self::sanitize_css_length( (string) $this->prop( 'row_gap', '12px' ), '12px' );
		self::set_style(
			$render_slug,
			array(
				'selector'    => '%%order_class%% .squad-comparison-list',
				'declaration' => "--squad-cl-row-gap: {$row_gap};",
			)
		);

		$icon_size = self::sanitize_css_length( (string) $this->prop( 'icon_size', '20px' ), '20px' );
		self::set_style(
			$render_slug,
			array(
				'selector'    => '%%order_class%% .squad-comparison-list',
				'declaration' => "--squad-cl-icon-size: {$icon_size};",
			)
		);
	}

	/**
	 * Apply row background, zebra-stripe, and divider custom properties via
	 * `set_style()`.
	 *
	 * @since 4.4.0
	 *
	 * @param string $render_slug Module render slug.
	 *
	 * @return void
	 */
	public function apply_row_css( string $render_slug ): void {
		$bg = self::sanitize_css_background( (string) $this->prop( 'row_background', '' ) );
		if ( '' !== $bg ) {
			self::set_style(
				$render_slug,
				array(
					'selector'    => '%%order_class%% .squad-comparison-list',
					'declaration' => sprintf( '--squad-cl-row-bg: %s;', esc_attr( $bg ) ),
				)
			);
		}

		$bg_alt = self::sanitize_css_background( (string) $this->prop( 'row_background_alt', '' ) );
		if ( '' !== $bg_alt ) {
			self::set_style(
				$render_slug,
				array(
					'selector'    => '%%order_class%% .squad-comparison-list',
					'declaration' => sprintf( '--squad-cl-row-bg-alt: %s;', esc_attr( $bg_alt ) ),
				)
			);
		}

		if ( 'on' !== (string) $this->prop( 'row_divider', 'on' ) ) {
			return;
		}

		$divider_color = self::sanitize_css_background( (string) $this->prop( 'row_divider_color', '#e5e7eb' ) );
		if ( '' === $divider_color ) {
			$divider_color = '#e5e7eb';
		}
		self::set_style(
			$render_slug,
			array(
				'selector'    => '%%order_class%% .squad-comparison-list',
				'declaration' => sprintf( '--squad-cl-row-divider-color: %s;', esc_attr( $divider_color ) ),
			)
		);

		$divider_width = self::sanitize_css_length( (string) $this->prop( 'row_divider_width', '1px' ), '1px' );
		self::set_style(
			$render_slug,
			array(
				'selector'    => '%%order_class%% .squad-comparison-list',
				'declaration' => "--squad-cl-row-divider-width: {$divider_width};",
			)
		);

		$divider_style = (string) $this->prop( 'row_divider_style', 'solid' );
		$divider_style = in_array( $divider_style, self::DIVIDER_STYLES, true ) ? $divider_style : 'solid';
		self::set_style(
			$render_slug,
			array(
				'selector'    => '%%order_class%% .squad-comparison-list',
				'declaration' => sprintf( '--squad-cl-row-divider-style: %s;', esc_attr( $divider_style ) ),
			)
		);
	}

	/**
	 * Apply the per-status icon glyph and color CSS for all three states.
	 *
	 * @since 4.4.0
	 *
	 * @param string $render_slug Module render slug.
	 *
	 * @return void
	 */
	public function apply_icon_css( string $render_slug ): void {
		$this->render_row_icon( $render_slug, 'included', 'included_icon', 'included_icon_color', self::DEFAULT_INCLUDED_ICON, '#2ecc71' );
		$this->render_row_icon( $render_slug, 'excluded', 'excluded_icon', 'excluded_icon_color', self::DEFAULT_EXCLUDED_ICON, '#e74c3c' );
		$this->render_row_icon( $render_slug, 'neutral', 'neutral_icon', 'neutral_icon_color', self::DEFAULT_NEUTRAL_ICON, '#9aa0a6' );
	}

	/**
	 * Emit the CSS glyph + color custom property for one comparison-list
	 * status.
	 *
	 * `Comparison_List_Item` deliberately renders an empty, `aria-hidden`
	 * icon span and tags its row with `data-status` (see that class's
	 * render()) rather than printing the glyph itself — this parent owns
	 * the icon/color configuration for all three states and paints every
	 * row sharing a status identically via the `[data-status="…"]`
	 * attribute selector, so no HTML string-parsing of the already-rendered
	 * child `$content` is required.
	 *
	 * The author picks each state's icon with the native Divi icon picker
	 * (`type => 'select_icon'`). The raw extended-icon value is resolved to
	 * a glyph exactly as `Icon_Box`/`Step_Flow_Item` do —
	 * `Divi::inject_fa_icons()` (loads the relevant icon font) then
	 * `et_pb_get_extended_font_icon_value()`. Because the resolved glyph is
	 * interpolated into a CSS `content` string (not HTML text, so
	 * `esc_html()` can't apply) it is first run through
	 * `sanitize_css_background()` to strip any character that could break
	 * out of the declaration — the raw picker value is never written to CSS
	 * unvalidated.
	 *
	 * Named `render_row_icon()` rather than `render_image()`/`render_button()`
	 * to avoid colliding with `ET_Builder_Element`'s reserved method names.
	 *
	 * @since 4.4.0
	 *
	 * @param string $render_slug   Module render slug.
	 * @param string $status        One of self::STATUSES.
	 * @param string $icon_field    Prop name holding the extended-icon value.
	 * @param string $color_field   Prop name holding the icon color.
	 * @param string $default_icon  Fallback extended-icon value.
	 * @param string $default_color Fallback icon color.
	 *
	 * @return void
	 */
	private function render_row_icon( string $render_slug, string $status, string $icon_field, string $color_field, string $default_icon, string $default_color ): void {
		if ( ! in_array( $status, self::STATUSES, true ) ) {
			return;
		}

		$icon_raw = (string) $this->prop( $icon_field, $default_icon );
		if ( '' === $icon_raw ) {
			$icon_raw = $default_icon;
		}

		Divi::inject_fa_icons( $icon_raw );
		$icon_glyph = (string) et_pb_get_extended_font_icon_value( $icon_raw, true );

		// Strip any CSS-breakout characters before interpolating the
		// resolved glyph into a `content` declaration.
		$icon_glyph = self::sanitize_css_background( $icon_glyph );
		if ( '' !== $icon_glyph ) {
			self::set_style(
				$render_slug,
				array(
					'selector'    => "%%order_class%% .squad-comparison-list__item[data-status=\"{$status}\"] .squad-comparison-list__icon::before",
					'declaration' => sprintf( 'content: "%s"; font-family: "ETModules";', $icon_glyph ),
				)
			);
		}

		$color = self::sanitize_css_background( (string) $this->prop( $color_field, $default_color ) );
		if ( '' === $color ) {
			$color = $default_color;
		}

		self::set_style(
			$render_slug,
			array(
				'selector'    => "%%order_class%% .squad-comparison-list__item[data-status=\"{$status}\"]",
				'declaration' => sprintf( '--squad-cl-icon-color: %s;', esc_attr( $color ) ),
			)
		);
	}
}
