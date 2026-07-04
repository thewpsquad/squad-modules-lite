<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Inline Content Module Class.
 *
 * Parent module that flows a row of mixed inline items — text, icon, image,
 * button, divider — with CSS-only flex layout. No frontend JS.
 *
 * @since   4.1.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Creative;

use DiviSquad\Builder\Shared\Modules\Creative\Inline_Content\Inline_Helper;
use DiviSquad\Builder\Version4\Abstracts\Module;
use function esc_attr;
use function esc_html__;
use function sprintf;

/**
 * Inline Content parent module class.
 *
 * @since 4.1.0
 */
class Inline_Content extends Module {

	public function init(): void {
		$this->name      = esc_html__( 'Inline Content', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Inline Contents', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'inline-content.svg' );

		$this->slug             = 'disq_inline_content';
		$this->child_slug       = 'disq_inline_content_item';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.{$this->slug}";

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'layout' => esc_html__( 'Layout', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'gaps' => esc_html__( 'Gaps', 'squad-modules-for-divi' ),
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
			'fonts'          => false,
			'image_icon'     => false,
			'text'           => false,
			'button'         => false,
			'filters'        => false,
		);
	}

	/**
	 * Declare fields for the module.
	 *
	 * @since 4.1.0
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_fields(): array {
		return array(
			'content_alignment' => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Horizontal Alignment', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'How items are distributed horizontally.', 'squad-modules-for-divi' ),
					'options'     => array(
						'left'    => esc_html__( 'Left', 'squad-modules-for-divi' ),
						'center'  => esc_html__( 'Center', 'squad-modules-for-divi' ),
						'right'   => esc_html__( 'Right', 'squad-modules-for-divi' ),
						'between' => esc_html__( 'Space Between', 'squad-modules-for-divi' ),
					),
					'default'     => 'left',
					'tab_slug'    => 'general',
					'toggle_slug' => 'layout',
				)
			),
			'vertical_align'    => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Vertical Alignment', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'How items are aligned vertically.', 'squad-modules-for-divi' ),
					'options'     => array(
						'top'      => esc_html__( 'Top', 'squad-modules-for-divi' ),
						'center'   => esc_html__( 'Center', 'squad-modules-for-divi' ),
						'baseline' => esc_html__( 'Baseline', 'squad-modules-for-divi' ),
						'bottom'   => esc_html__( 'Bottom', 'squad-modules-for-divi' ),
					),
					'default'     => 'center',
					'tab_slug'    => 'general',
					'toggle_slug' => 'layout',
				)
			),
			'column_gap'        => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Column Gap', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Horizontal gap between items.', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '0', 'max' => '100', 'step' => '1' ),
					'default'        => '12px',
					'mobile_options' => true,
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'gaps',
				)
			),
			'row_gap'           => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Row Gap', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Vertical gap when items wrap to a new row.', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '0', 'max' => '100', 'step' => '1' ),
					'default'        => '8px',
					'mobile_options' => true,
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'gaps',
				)
			),
		);
	}

	/**
	 * Render the module output.
	 *
	 * @since 4.1.0
	 *
	 * @param array<string, mixed> $attrs       List of attributes.
	 * @param string               $content     Content being processed.
	 * @param string               $render_slug Slug of module that is used for rendering output.
	 *
	 * @return string
	 */
	public function render( $attrs, $content, $render_slug ): string {
		$raw_align  = (string) $this->prop( 'content_alignment', 'left' );
		$raw_valign = (string) $this->prop( 'vertical_align', 'center' );

		// Validate against allowlists — never inject raw user values into class names.
		$align  = Inline_Helper::is_valid_align( $raw_align ) ? $raw_align : 'left';
		$valign = Inline_Helper::is_valid_valign( $raw_valign ) ? $raw_valign : 'center';

		$this->apply_gap_css( $render_slug );

		return sprintf(
			'<div class="squad-inline squad-inline--align-%1$s squad-inline--valign-%2$s">%3$s</div>',
			esc_attr( $align ),
			esc_attr( $valign ),
			$content
		);
	}

	/**
	 * Emit column-gap and row-gap CSS for desktop, tablet, phone.
	 *
	 * Uses `sanitize_css_length()` (inherited from Module base) to reject any
	 * non-length value before writing it into the stylesheet, matching the
	 * Social_Share `apply_layout_css()` pattern exactly.
	 *
	 * @since 4.1.0
	 *
	 * @param string $render_slug Module render slug.
	 *
	 * @return void
	 */
	protected function apply_gap_css( string $render_slug ): void {
		$inner_sel = '%%order_class%% .squad-inline';

		// column_gap — desktop / tablet / phone.
		$col_gap = self::sanitize_css_length( (string) $this->prop( 'column_gap', '12px' ) );
		if ( '' !== $col_gap ) {
			self::set_style( $render_slug, array( 'selector' => $inner_sel, 'declaration' => "column-gap: {$col_gap};" ) );
		}
		$col_gap_tablet = self::sanitize_css_length( (string) $this->prop( 'column_gap_tablet', '' ) );
		if ( '' !== $col_gap_tablet ) {
			self::set_style( $render_slug, array( 'selector' => $inner_sel, 'declaration' => "column-gap: {$col_gap_tablet};", 'media_query' => self::get_media_query( 'max_width_980' ) ) );
		}
		$col_gap_phone = self::sanitize_css_length( (string) $this->prop( 'column_gap_phone', '' ) );
		if ( '' !== $col_gap_phone ) {
			self::set_style( $render_slug, array( 'selector' => $inner_sel, 'declaration' => "column-gap: {$col_gap_phone};", 'media_query' => self::get_media_query( 'max_width_767' ) ) );
		}

		// row_gap — desktop / tablet / phone.
		$row_gap = self::sanitize_css_length( (string) $this->prop( 'row_gap', '8px' ) );
		if ( '' !== $row_gap ) {
			self::set_style( $render_slug, array( 'selector' => $inner_sel, 'declaration' => "row-gap: {$row_gap};" ) );
		}
		$row_gap_tablet = self::sanitize_css_length( (string) $this->prop( 'row_gap_tablet', '' ) );
		if ( '' !== $row_gap_tablet ) {
			self::set_style( $render_slug, array( 'selector' => $inner_sel, 'declaration' => "row-gap: {$row_gap_tablet};", 'media_query' => self::get_media_query( 'max_width_980' ) ) );
		}
		$row_gap_phone = self::sanitize_css_length( (string) $this->prop( 'row_gap_phone', '' ) );
		if ( '' !== $row_gap_phone ) {
			self::set_style( $render_slug, array( 'selector' => $inner_sel, 'declaration' => "row-gap: {$row_gap_phone};", 'media_query' => self::get_media_query( 'max_width_767' ) ) );
		}
	}
}
