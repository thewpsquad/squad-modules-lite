<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Image Accordion Module Class.
 *
 * Parent module: a horizontal or vertical row of image panels. One panel is
 * "active" (expanded) at a time; the rest collapse to a thin strip showing a
 * persistent label. Activating a panel (click or hover) reveals its nested
 * Divi module content overlaid on the panel's image with a scrim behind it.
 *
 * @since   4.4.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Media;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use DiviSquad\Builder\Version4\Abstracts\Module;
use function absint;
use function esc_attr;
use function esc_html__;
use function in_array;
use function max;
use function min;
use function preg_replace;
use function preg_replace_callback;
use function sprintf;
use function substr_count;
use function trim;
use function wp_enqueue_script;

/**
 * Image Accordion Module Class.
 *
 * @since 4.4.0
 */
class Image_Accordion extends Module {

	/**
	 * Initiate Module.
	 *
	 * @since 4.4.0
	 * @return void
	 */
	public function init(): void {
		$this->name      = esc_html__( 'Image Accordion', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Image Accordions', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'image-accordion.svg' );

		$this->slug             = 'disq_image_accordion';
		$this->child_slug       = 'disq_image_accordion_item';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.{$this->slug}";

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'content_settings' => esc_html__( 'Accordion Settings', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'panel' => esc_html__( 'Panel', 'squad-modules-for-divi' ),
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

		$this->custom_css_fields = array(
			'accordion' => array(
				'label'    => esc_html__( 'Accordion', 'squad-modules-for-divi' ),
				'selector' => '.squad-image-accordion',
			),
			'panel'     => array(
				'label'    => esc_html__( 'Panel', 'squad-modules-for-divi' ),
				'selector' => '.squad-image-accordion__panel',
			),
		);
	}

	/**
	 * Declare general fields for the module.
	 *
	 * @since 4.4.0
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_fields(): array {
		return array(
			'orientation'        => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Orientation', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'Lay the panels out side-by-side (horizontal) or stacked (vertical).', 'squad-modules-for-divi' ),
					'options'          => array(
						'horizontal' => esc_html__( 'Horizontal', 'squad-modules-for-divi' ),
						'vertical'   => esc_html__( 'Vertical', 'squad-modules-for-divi' ),
					),
					'default'          => 'horizontal',
					'default_on_front' => 'horizontal',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'content_settings',
				)
			),
			'expand_trigger'     => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Expand Trigger', 'squad-modules-for-divi' ),
				array(
					'description'      => esc_html__( 'What activates a panel: a click (works on touch) or a hover (desktop enhancement layered on top of click).', 'squad-modules-for-divi' ),
					'options'          => array(
						'click' => esc_html__( 'Click', 'squad-modules-for-divi' ),
						'hover' => esc_html__( 'Hover', 'squad-modules-for-divi' ),
					),
					'default'          => 'click',
					'default_on_front' => 'click',
					'tab_slug'         => 'general',
					'toggle_slug'      => 'content_settings',
				)
			),
			'default_active'     => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Default Active Panel', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Which panel is expanded by default (1 = first). Clamped to the number of panels.', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '1', 'max' => '20', 'step' => '1' ),
					'default'        => '1',
					'unitless'       => true,
					'tab_slug'       => 'general',
					'toggle_slug'    => 'content_settings',
				)
			),
			'collapsed_size'     => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Collapsed Size', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Thin-strip width (horizontal) / height (vertical) of inactive panels.', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '0', 'max' => '600', 'step' => '1' ),
					'allowed_units'  => array( 'px', '%', 'vw' ),
					'default_unit'   => 'px',
					'default'        => '60px',
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'panel',
				)
			),
			'overlay_background' => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Overlay Background', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Scrim shown behind the nested content when a panel is active.', 'squad-modules-for-divi' ),
					'default'     => 'rgba(0,0,0,0.6)',
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'panel',
				)
			),
			'overlay_opacity'    => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Overlay Opacity', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Opacity of the active-panel scrim.', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '0', 'max' => '100', 'step' => '1' ),
					'allowed_units'  => array( '%' ),
					'default_unit'   => '%',
					'default'        => '60',
					'unitless'       => true,
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'panel',
				)
			),
		);
	}

	/**
	 * Render module output.
	 *
	 * @since 4.4.0
	 *
	 * @param array<string, mixed> $attrs       List of attributes.
	 * @param string               $content     Content being processed (rendered child panels).
	 * @param string               $render_slug Slug of module that is used for rendering output.
	 *
	 * @return string
	 */
	public function render( $attrs, $content, $render_slug ): string {
		if ( '' === trim( (string) $content ) ) {
			return sprintf(
				'<div class="squad-notice">%s</div>',
				esc_html__( 'Add at least one Image Accordion Item.', 'squad-modules-for-divi' )
			);
		}

		wp_enqueue_script( 'squad-module-image-accordion' );

		$this->apply_panel_css( $render_slug );

		$orientation = (string) $this->prop( 'orientation', 'horizontal' );
		$orientation = in_array( $orientation, array( 'horizontal', 'vertical' ), true ) ? $orientation : 'horizontal';

		$trigger = (string) $this->prop( 'expand_trigger', 'click' );
		$trigger = in_array( $trigger, array( 'click', 'hover' ), true ) ? $trigger : 'click';

		list( $content, $active_index ) = $this->mark_active_panel( (string) $content );

		return sprintf(
			'<div class="squad-image-accordion squad-image-accordion--%1$s" data-trigger="%2$s" data-active-index="%3$d">%4$s</div>',
			esc_attr( $orientation ),
			esc_attr( $trigger ),
			$active_index,
			$content
		);
	}

	/**
	 * Emit panel-sizing / overlay CSS custom properties via set_style.
	 *
	 * @since 4.4.0
	 *
	 * @param string $render_slug Module render slug.
	 *
	 * @return void
	 */
	public function apply_panel_css( string $render_slug ): void {
		$collapsed = self::sanitize_css_length( (string) $this->prop( 'collapsed_size', '60px' ), '60px' );

		self::set_style(
			$render_slug,
			array(
				'selector'    => '%%order_class%%.disq_image_accordion',
				'declaration' => "--squad-ia-collapsed: {$collapsed};",
			)
		);

		$overlay_bg = self::sanitize_css_background( (string) $this->prop( 'overlay_background', 'rgba(0,0,0,0.6)' ) );
		if ( '' !== $overlay_bg ) {
			self::set_style(
				$render_slug,
				array(
					'selector'    => '%%order_class%%.disq_image_accordion',
					'declaration' => "--squad-ia-overlay-bg: {$overlay_bg};",
				)
			);
		}

		$opacity_pct = max( 0, min( 100, absint( $this->prop( 'overlay_opacity', '60' ) ) ) );
		$opacity     = $opacity_pct / 100;

		self::set_style(
			$render_slug,
			array(
				'selector'    => '%%order_class%%.disq_image_accordion',
				'declaration' => "--squad-ia-overlay-opacity: {$opacity};",
			)
		);
	}

	/**
	 * Number the rendered child panels sequentially (0-indexed) and mark the
	 * default-active one with `is-active` / `aria-expanded="true"`.
	 *
	 * Each child panel is rendered independently by
	 * {@see Image_Accordion_Item::render()} and has no visibility into its own
	 * sibling position, so it emits a literal placeholder token
	 * ({@see Image_Accordion_Item::INDEX_TOKEN}) in place of `data-index`. This
	 * method replaces every occurrence sequentially with its real 0-indexed
	 * position, then flips exactly one matching panel to the active state
	 * based on the clamped `default_active` field.
	 *
	 * @since 4.4.0
	 *
	 * @param string $content Rendered (concatenated) child panel HTML.
	 *
	 * @return array{0: string, 1: int} [$processed_content, $active_index (0-indexed)].
	 */
	public function mark_active_panel( string $content ): array {
		$panel_count = substr_count( $content, Image_Accordion_Item::INDEX_TOKEN );

		if ( 0 === $panel_count ) {
			return array( $content, 0 );
		}

		$counter = -1;
		$content = (string) preg_replace_callback(
			'/' . preg_quote( Image_Accordion_Item::INDEX_TOKEN, '/' ) . '/',
			static function () use ( &$counter ): string {
				++$counter;

				return (string) $counter;
			},
			$content
		);

		$requested    = max( 1, absint( $this->prop( 'default_active', '1' ) ) );
		$active_index = min( $panel_count, $requested ) - 1;

		$pattern = sprintf(
			'/(<div class="squad-image-accordion__panel)(" data-index="%d" style="[^"]*" tabindex="0" role="button" aria-expanded=")false(")/',
			$active_index
		);

		$content = (string) preg_replace( $pattern, '$1 is-active$2true$3', $content, 1 );

		return array( $content, $active_index );
	}
}
