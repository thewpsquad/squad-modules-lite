<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Team Member Module Class.
 *
 * Parent module that lays out a responsive grid of child Team Member cards.
 *
 * @since   4.2.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Content;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use DiviSquad\Builder\Version4\Abstracts\Module;
use function absint;
use function esc_html__;
use function max;
use function min;

/**
 * Team Member Module Class.
 *
 * @since 4.2.0
 */
class Team_Member extends Module {

	public function init(): void {
		$this->name      = esc_html__( 'Team Member', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Team Members', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'team-member.svg' );

		$this->slug             = 'disq_team_member';
		$this->child_slug       = 'disq_team_member_item';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.{$this->slug}";

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		$this->settings_modal_toggles = array(
			'general' => array(
				'toggles' => array(
					'layout' => esc_html__( 'Layout', 'squad-modules-for-divi' ),
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
	 * Declare general fields for the module.
	 *
	 * @since 4.2.0
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_fields(): array {
		return array(
			'columns'    => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Columns', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Number of cards per row.', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '1', 'max' => '6', 'step' => '1' ),
					'default'        => '3',
					'unitless'       => true,
					'mobile_options' => true,
					'tab_slug'       => 'general',
					'toggle_slug'    => 'layout',
				)
			),
			'column_gap' => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Column Gap', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Horizontal gap between cards.', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '0', 'max' => '100', 'step' => '1' ),
					'default'        => '30px',
					'mobile_options' => true,
					'tab_slug'       => 'general',
					'toggle_slug'    => 'layout',
				)
			),
			'row_gap'    => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Row Gap', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Vertical gap between rows.', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '0', 'max' => '100', 'step' => '1' ),
					'default'        => '30px',
					'mobile_options' => true,
					'tab_slug'       => 'general',
					'toggle_slug'    => 'layout',
				)
			),
		);
	}

	/**
	 * Render module output.
	 *
	 * @since 4.2.0
	 *
	 * @param array<string, mixed> $attrs       List of attributes.
	 * @param string               $content     Content being processed (child cards).
	 * @param string               $render_slug Slug of module that is used for rendering output.
	 *
	 * @return string
	 */
	public function render( $attrs, $content, $render_slug ): string {
		$this->apply_grid_css( $render_slug );

		return sprintf(
			'<div class="squad-team-members squad-team-members--grid">%s</div>',
			(string) $content
		);
	}

	/**
	 * Emit responsive grid CSS for the columns and gaps.
	 *
	 * @since 4.2.0
	 *
	 * @param string $render_slug Slug of module that is used for rendering output.
	 *
	 * @return void
	 */
	public function apply_grid_css( string $render_slug ): void {
		$grid      = '%%order_class%% .squad-team-members--grid';
		$columns   = max( 1, min( 6, absint( $this->prop( 'columns', '3' ) ) ) );
		$col_gap   = self::sanitize_css_length( (string) $this->prop( 'column_gap', '30px' ) );
		$row_gap   = self::sanitize_css_length( (string) $this->prop( 'row_gap', '30px' ) );
		$row_value = '' !== $row_gap ? $row_gap : '30px';
		$col_value = '' !== $col_gap ? $col_gap : '30px';

		self::set_style(
			$render_slug,
			array(
				'selector'    => $grid,
				'declaration' => sprintf(
					'grid-template-columns: repeat(%1$d, minmax(0, 1fr)); gap: %2$s %3$s;',
					$columns,
					$row_value,
					$col_value
				),
			)
		);

		$columns_tablet = absint( $this->prop( 'columns_tablet', '0' ) );
		if ( $columns_tablet > 0 ) {
			self::set_style(
				$render_slug,
				array(
					'selector'    => $grid,
					'declaration' => sprintf( 'grid-template-columns: repeat(%d, minmax(0, 1fr));', max( 1, min( 6, $columns_tablet ) ) ),
					'media_query' => self::get_media_query( 'max_width_980' ),
				)
			);
		}

		$columns_phone = absint( $this->prop( 'columns_phone', '0' ) );
		self::set_style(
			$render_slug,
			array(
				'selector'    => $grid,
				'declaration' => sprintf( 'grid-template-columns: repeat(%d, minmax(0, 1fr));', $columns_phone > 0 ? max( 1, min( 6, $columns_phone ) ) : 1 ),
				'media_query' => self::get_media_query( 'max_width_767' ),
			)
		);
	}
}
