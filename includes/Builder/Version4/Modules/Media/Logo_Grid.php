<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Logo Grid Module Class.
 *
 * Static CSS Grid display of logos with hover effects, responsive columns,
 * and optional per-logo links. No JavaScript dependency.
 *
 * @since   4.0.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Media;

use DiviSquad\Builder\Version4\Abstracts\Module;
use function absint;
use function esc_html__;

/**
 * Logo Grid Module Class.
 *
 * @since 4.0.0
 */
class Logo_Grid extends Module {

	/**
	 * Initiate Module.
	 *
	 * @since 4.0.0
	 * @return void
	 */
	public function init(): void {
		$this->name      = esc_html__( 'Logo Grid', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Logo Grids', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'logo-grid.svg' );

		$this->slug             = 'disq_logo_grid';
		$this->child_slug       = 'disq_logo_grid_item';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.{$this->slug}";

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'grid_settings'  => esc_html__( 'Grid Settings', 'squad-modules-for-divi' ),
					'logo_settings'  => esc_html__( 'Logo Sizing', 'squad-modules-for-divi' ),
					'hover_settings' => esc_html__( 'Hover Effect', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'wrapper' => esc_html__( 'Wrapper', 'squad-modules-for-divi' ),
					'item'    => esc_html__( 'Item', 'squad-modules-for-divi' ),
				),
			),
		);

		$this->advanced_fields = array(
			'background'     => divi_squad()->d4_module_helper->selectors_background( $this->main_css_element ),
			'borders'        => array(
				'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
				'item'    => array(
					'css'         => array(
						'main' => "{$this->main_css_element} .disq_logo_grid_item",
					),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'item',
				),
			),
			'box_shadow'     => array(
				'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			),
			'margin_padding' => divi_squad()->d4_module_helper->selectors_margin_padding( $this->main_css_element ),
			'max_width'      => divi_squad()->d4_module_helper->selectors_max_width( $this->main_css_element ),
			'height'         => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			'fonts'          => false,
			'image_icon'     => false,
			'text'           => false,
			'button'         => false,
			'filters'        => false,
		);

		$this->custom_css_fields = array(
			'grid' => array(
				'label'    => esc_html__( 'Grid', 'squad-modules-for-divi' ),
				'selector' => '.squad-logo-grid',
			),
			'item' => array(
				'label'    => esc_html__( 'Item', 'squad-modules-for-divi' ),
				'selector' => '.disq_logo_grid_item',
			),
		);
	}

	/**
	 * Define module fields.
	 *
	 * @since 4.0.0
	 * @return array<string, mixed>
	 */
	public function get_fields(): array {
		return array(
			// Grid Settings.
			'columns'       => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Columns', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Number of logo columns.', 'squad-modules-for-divi' ),
					'range_settings' => array(
						'min'       => '1',
						'max'       => '8',
						'step'      => '1',
						'min_limit' => '1',
					),
					'default'        => '4',
					'unitless'       => true,
					'mobile_options' => true,
					'responsive'     => true,
					'hover'          => false,
					'tab_slug'       => 'general',
					'toggle_slug'    => 'grid_settings',
				)
			),
			'gap'           => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Gap', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( 'Gap between logo items in pixels.', 'squad-modules-for-divi' ),
					'range_settings' => array(
						'min'  => '0',
						'max'  => '100',
						'step' => '1',
					),
					'default'        => '30',
					'unitless'       => true,
					'mobile_options' => false,
					'hover'          => false,
					'tab_slug'       => 'general',
					'toggle_slug'    => 'grid_settings',
				)
			),
			// Logo Sizing.
			'logo_max_width'  => array(
				'label'       => esc_html__( 'Logo Max Width', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Maximum width of each logo image (e.g. 160px).', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '160px',
				'tab_slug'    => 'general',
				'toggle_slug' => 'logo_settings',
			),
			'logo_max_height' => array(
				'label'       => esc_html__( 'Logo Max Height', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Maximum height of each logo image (e.g. 80px).', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '80px',
				'tab_slug'    => 'general',
				'toggle_slug' => 'logo_settings',
			),
			// Hover Effect.
			'hover_effect'  => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Hover Effect', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Effect applied to logos on hover.', 'squad-modules-for-divi' ),
					'options'     => array(
						'grayscale' => esc_html__( 'Grayscale to Color', 'squad-modules-for-divi' ),
						'opacity'   => esc_html__( 'Opacity', 'squad-modules-for-divi' ),
						'zoom'      => esc_html__( 'Zoom', 'squad-modules-for-divi' ),
						'none'      => esc_html__( 'None', 'squad-modules-for-divi' ),
					),
					'default'     => 'grayscale',
					'affects'     => array( 'hover_opacity' ),
					'tab_slug'    => 'general',
					'toggle_slug' => 'hover_settings',
				)
			),
			'hover_opacity' => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Resting Opacity', 'squad-modules-for-divi' ),
				array(
					'description'     => esc_html__( 'Logo opacity at rest (applies when Hover Effect is Opacity).', 'squad-modules-for-divi' ),
					'range_settings'  => array(
						'min'  => '0',
						'max'  => '1',
						'step' => '0.05',
					),
					'default'         => '0.5',
					'unitless'        => true,
					'mobile_options'  => false,
					'hover'           => false,
					'depends_show_if' => 'opacity',
					'tab_slug'        => 'general',
					'toggle_slug'     => 'hover_settings',
				)
			),
		);
	}

	/**
	 * Render the module HTML.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $attrs       Module attributes.
	 * @param string               $content     Rendered child modules HTML.
	 * @param string               $render_slug Module render slug.
	 *
	 * @return string
	 */
	public function render( $attrs, $content, $render_slug ): string {
		$this->apply_grid_css( $render_slug );
		$this->apply_hover_css( $render_slug );
		$this->apply_logo_sizing_css( $render_slug );

		return sprintf(
			'<div class="squad-logo-grid">%s</div>',
			$content
		);
	}

	/**
	 * Apply responsive grid-template-columns and gap CSS via set_style.
	 *
	 * @since 4.0.0
	 *
	 * @param string $render_slug Module render slug.
	 *
	 * @return void
	 */
	public function apply_grid_css( string $render_slug ): void {
		$cols_desk = max( 1, absint( $this->prop( 'columns', '4' ) ) );
		$cols_tab  = max( 1, absint( $this->prop( 'columns_tablet', '' ) ?: 3 ) );
		$cols_mob  = max( 1, absint( $this->prop( 'columns_phone', '' ) ?: 2 ) );
		$gap       = max( 0, absint( $this->prop( 'gap', '30' ) ) );

		self::set_style( $render_slug, array(
			'selector'    => '%%order_class%% .squad-logo-grid',
			'declaration' => "grid-template-columns: repeat({$cols_desk}, 1fr); gap: {$gap}px;",
		) );

		self::set_style( $render_slug, array(
			'selector'    => '%%order_class%% .squad-logo-grid',
			'declaration' => "grid-template-columns: repeat({$cols_tab}, 1fr);",
			'media_query' => self::get_media_query( 'max_width_980' ),
		) );

		self::set_style( $render_slug, array(
			'selector'    => '%%order_class%% .squad-logo-grid',
			'declaration' => "grid-template-columns: repeat({$cols_mob}, 1fr);",
			'media_query' => self::get_media_query( 'max_width_767' ),
		) );
	}

	/**
	 * Apply hover effect CSS via set_style.
	 *
	 * @since 4.0.0
	 *
	 * @param string $render_slug Module render slug.
	 *
	 * @return void
	 */
	public function apply_hover_css( string $render_slug ): void {
		$effect = $this->prop( 'hover_effect', 'grayscale' );
		$logo   = '%%order_class%% .disq_logo_grid_item .squad-logo-grid__logo';
		$hover  = '%%order_class%% .disq_logo_grid_item:hover .squad-logo-grid__logo';

		switch ( $effect ) {
			case 'grayscale':
				self::set_style( $render_slug, array(
					'selector'    => $logo,
					'declaration' => 'filter: grayscale(100%); transition: filter 0.3s ease;',
				) );
				self::set_style( $render_slug, array(
					'selector'    => $hover,
					'declaration' => 'filter: grayscale(0%);',
				) );
				break;

			case 'opacity':
				$opacity = max( 0.0, min( 1.0, (float) $this->prop( 'hover_opacity', '0.5' ) ) );
				self::set_style( $render_slug, array(
					'selector'    => $logo,
					'declaration' => "opacity: {$opacity}; transition: opacity 0.3s ease;",
				) );
				self::set_style( $render_slug, array(
					'selector'    => $hover,
					'declaration' => 'opacity: 1;',
				) );
				break;

			case 'zoom':
				self::set_style( $render_slug, array(
					'selector'    => $logo,
					'declaration' => 'transition: transform 0.3s ease;',
				) );
				self::set_style( $render_slug, array(
					'selector'    => $hover,
					'declaration' => 'transform: scale(1.1);',
				) );
				break;

			case 'none':
				break;
		}
	}

	/**
	 * Apply logo max-width / max-height CSS.
	 *
	 * @since 4.0.0
	 *
	 * @param string $render_slug Module render slug.
	 *
	 * @return void
	 */
	public function apply_logo_sizing_css( string $render_slug ): void {
		$max_width  = self::sanitize_css_length( (string) $this->prop( 'logo_max_width', '160px' ) );
		$max_height = self::sanitize_css_length( (string) $this->prop( 'logo_max_height', '80px' ) );

		if ( '' === $max_width && '' === $max_height ) {
			return;
		}

		$declaration = '';
		if ( '' !== $max_width ) {
			$declaration .= "max-width: {$max_width}; ";
		}
		if ( '' !== $max_height ) {
			$declaration .= "max-height: {$max_height}; ";
		}

		self::set_style( $render_slug, array(
			'selector'    => '%%order_class%% .squad-logo-grid__logo',
			'declaration' => rtrim( $declaration ),
		) );
	}

	/**
	 * Validate a CSS length value against an allowlist of units.
	 *
	 * @since 4.0.0
	 *
	 * @param string $value Raw value.
	 *
	 * @return string Sanitized value or empty string.
	 */
	private static function sanitize_css_length( string $value ): string {
		$value = trim( $value );
		if ( '' === $value ) {
			return '';
		}
		if ( preg_match( '/^\d+(\.\d+)?(px|em|rem|%|vh|vw|vmin|vmax|ch|ex|cm|mm|pt|pc)$/', $value ) ) {
			return $value;
		}
		return '';
	}
}
