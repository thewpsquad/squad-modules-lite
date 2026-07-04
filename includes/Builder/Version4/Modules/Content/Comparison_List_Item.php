<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Comparison List Item (child) Module (Divi 4 shortcode).
 *
 * Represents a single feature row inside the Comparison List parent module.
 * Each row picks a state (included / excluded / neutral), provides a text
 * label, and may hold free-form nested Divi module content. The parent owns
 * the three status icons + colors and injects the icon markup for each state
 * via CSS — this child never renders the glyph itself, only the state (as a
 * data attribute), the label, and any nested content.
 *
 * @since   4.4.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Content;

defined( 'ABSPATH' ) || exit;

use DiviSquad\Builder\Version4\Abstracts\Module\Child_Module;
use function esc_attr;
use function esc_html;
use function esc_html__;
use function in_array;
use function sprintf;

/**
 * Comparison List Item (child) Module class.
 *
 * @since 4.4.0
 */
class Comparison_List_Item extends Child_Module {

	/**
	 * Allowed row status values.
	 *
	 * @since 4.4.0
	 *
	 * @var array<int, string>
	 */
	private const STATUSES = array( 'included', 'excluded', 'neutral' );

	/**
	 * Initiate Module.
	 *
	 * @since 4.4.0
	 * @return void
	 */
	public function init(): void {
		$this->name   = esc_html__( 'Comparison List Item', 'squad-modules-for-divi' );
		$this->plural = esc_html__( 'Comparison List Items', 'squad-modules-for-divi' );

		$this->slug             = 'disq_comparison_list_item';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.{$this->slug}";

		$this->child_title_var          = 'title';
		$this->child_title_fallback_var = 'admin_label';

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		$this->settings_modal_toggles = array(
			'general' => array(
				'toggles' => array(
					'item_settings' => esc_html__( 'Feature', 'squad-modules-for-divi' ),
				),
			),
		);

		$this->advanced_fields = array(
			'background'     => false,
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
	 * Define module fields.
	 *
	 * @since 4.4.0
	 * @return array<string, array<string, mixed>>
	 */
	public function get_fields(): array {
		return array(
			'status' => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Status', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Whether this feature is included, excluded, or neutral. The parent module supplies the icon and color for each state.', 'squad-modules-for-divi' ),
					'options'     => array(
						'included' => esc_html__( 'Included', 'squad-modules-for-divi' ),
						'excluded' => esc_html__( 'Excluded', 'squad-modules-for-divi' ),
						'neutral'  => esc_html__( 'Neutral', 'squad-modules-for-divi' ),
					),
					'default'     => 'included',
					'tab_slug'    => 'general',
					'toggle_slug' => 'item_settings',
				)
			),
			'title'  => array(
				'label'       => esc_html__( 'Title', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'The feature label shown for this row.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'tab_slug'    => 'general',
				'toggle_slug' => 'item_settings',
			),
		);
	}

	/**
	 * Render the comparison list item HTML.
	 *
	 * The status icon glyph itself is NOT rendered here — the parent module
	 * owns the per-state icon/color configuration and targets rows by the
	 * `data-status` attribute set below. This child only contributes the
	 * state, the escaped title, and any nested Divi module content.
	 *
	 * @since 4.4.0
	 *
	 * @param array<array-key, mixed> $attrs       Module attributes.
	 * @param string                  $content     Rendered nested Divi module content.
	 * @param string                  $render_slug Module render slug.
	 *
	 * @return string
	 */
	public function render( $attrs, $content, $render_slug ): string {
		$status = (string) $this->prop( 'status', 'included' );
		if ( ! in_array( $status, self::STATUSES, true ) ) {
			$status = 'included';
		}

		$title = esc_html( (string) $this->prop( 'title', '' ) );

		return sprintf(
			'<div class="squad-comparison-list__item" data-status="%1$s"><span class="squad-comparison-list__icon" aria-hidden="true"></span><div class="squad-comparison-list__body"><span class="squad-comparison-list__title">%2$s</span><div class="squad-comparison-list__content">%3$s</div></div></div>',
			esc_attr( $status ),
			$title,
			(string) $content
		);
	}
}
