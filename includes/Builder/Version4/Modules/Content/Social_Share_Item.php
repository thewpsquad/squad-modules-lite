<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Social Share Item (child) Module Class.
 *
 * A single network share button within the Social Share parent module.
 *
 * @since   4.0.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Content;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use DiviSquad\Builder\Shared\Modules\Content\Social_Share\Networks;
use DiviSquad\Builder\Version4\Abstracts\Module\Child_Module;
use function esc_attr;
use function esc_html;
use function esc_html__;
use function esc_url;
use function sanitize_text_field;
use function sprintf;

/**
 * Social Share Item (child) Module Class.
 *
 * @since 4.0.0
 */
class Social_Share_Item extends Child_Module {

	public function init(): void {
		$this->name   = esc_html__( 'Social Share Item', 'squad-modules-for-divi' );
		$this->plural = esc_html__( 'Social Share Items', 'squad-modules-for-divi' );

		$this->slug             = 'disq_social_share_child';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.{$this->slug}";

		$this->child_title_var          = 'network';
		$this->child_title_fallback_var = 'admin_label';

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'network_content' => esc_html__( 'Network', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'colors' => esc_html__( 'Colors', 'squad-modules-for-divi' ),
				),
			),
		);

		$this->advanced_fields = array(
			'background'     => false,
			'borders'        => array(
				'default' => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			),
			'box_shadow'     => array(
				'default' => array(
					'css' => array( 'main' => "{$this->main_css_element} .squad-social-share__btn" ),
				),
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
	 * @since 4.0.0
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_fields(): array {
		return array(
			'network'             => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Network', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Which social network this button shares to.', 'squad-modules-for-divi' ),
					'options'     => self::network_options(),
					'default'     => 'facebook',
					'tab_slug'    => 'general',
					'toggle_slug' => 'network_content',
				)
			),
			'custom_label'        => array(
				'label'       => esc_html__( 'Custom Label', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Override the default network label (shown when Button Style is Icon + Text).', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'tab_slug'    => 'general',
				'toggle_slug' => 'network_content',
			),
			'use_custom_colors'   => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Use Custom Colors', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Override the brand colors for this button.', 'squad-modules-for-divi' ),
					'default'     => 'off',
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'colors',
				)
			),
			'icon_color_override' => array(
				'label'       => esc_html__( 'Icon Color', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Custom icon/label color for this button.', 'squad-modules-for-divi' ),
				'type'        => 'color-alpha',
				'default'     => '#ffffff',
				'show_if'     => array( 'use_custom_colors' => 'on' ),
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'colors',
			),
			'bg_color_override'   => array(
				'label'       => esc_html__( 'Background Color', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Custom background color for this button.', 'squad-modules-for-divi' ),
				'type'        => 'color-alpha',
				'default'     => '',
				'show_if'     => array( 'use_custom_colors' => 'on' ),
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'colors',
			),
		);
	}

	/**
	 * Render module output.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $attrs       List of attributes.
	 * @param string               $content     Content being processed.
	 * @param string               $render_slug Slug of module that is used for rendering output.
	 *
	 * @return string
	 */
	public function render( $attrs, $content, $render_slug ): string {
		$network = (string) $this->prop( 'network', 'facebook' );

		// Allowlist validation: unknown network → render nothing.
		if ( ! Networks::is_valid( $network ) ) {
			return '';
		}

		$meta = Networks::get_network( $network );
		if ( null === $meta ) {
			return '';
		}

		$target = Social_Share::$share_target;
		$ctx    = Social_Share::$button_context;

		$share_title = '' !== $target['title'] ? $target['title'] : $target['desc'];
		$href        = Networks::build_share_url( $network, $target['url'], $share_title );
		if ( '' === $href ) {
			return '';
		}

		$is_email = Networks::is_email( $network );
		$style    = 'icon_text' === ( $ctx['style'] ?? 'icon' ) ? 'icon_text' : 'icon';

		$label = sanitize_text_field( (string) $this->prop( 'custom_label', '' ) );
		if ( '' === $label ) {
			$label = $meta['label'];
		}

		$this->apply_color_css( $render_slug, $meta['color'] );

		// Link attributes. email = mailto, no target/rel/popup.
		$link_attrs = sprintf( 'href="%s"', esc_url( $href ) );
		if ( ! $is_email ) {
			$link_attrs .= ' target="_blank" rel="noopener noreferrer nofollow"';
			if ( 'on' === ( $ctx['enable_popup'] ?? 'on' ) ) {
				$link_attrs .= ' data-squad-share="popup"';
			}
		}

		$icon_html  = sprintf( '<span class="squad-social-share__icon squad-social-share__icon--%s" aria-hidden="true"></span>', esc_attr( $network ) );
		$label_html = 'icon_text' === $style
			? sprintf( '<span class="squad-social-share__label">%s</span>', esc_html( $label ) )
			: '';

		return sprintf(
			'<a class="squad-social-share__btn squad-social-share__btn--%1$s" %2$s aria-label="%3$s">%4$s%5$s</a>',
			esc_attr( $network ),
			$link_attrs,
			/* translators: %s: network label */
			esc_attr( sprintf( esc_html__( 'Share on %s', 'squad-modules-for-divi' ), $meta['label'] ) ),
			$icon_html,
			$label_html
		);
	}

	public function apply_color_css( string $render_slug, string $brand_color ): void {
		$use_custom = 'on' === $this->prop( 'use_custom_colors', 'off' );

		$bg = $brand_color; // trusted hardcoded hex from Networks registry — no sanitize needed.
		if ( $use_custom ) {
			$override = self::sanitize_css_background( (string) $this->prop( 'bg_color_override', '' ) );
			if ( '' !== $override ) {
				$bg = $override;
			}
		}

		if ( '' !== $bg ) {
			self::set_style(
				$render_slug,
				array(
					'selector'    => '%%order_class%% .squad-social-share__btn',
					'declaration' => sprintf( 'background-color: %s;', $bg ),
				)
			);
		}

		if ( $use_custom ) {
			$icon = self::sanitize_css_background( (string) $this->prop( 'icon_color_override', '' ) );
			if ( '' !== $icon ) {
				self::set_style(
					$render_slug,
					array(
						'selector'    => '%%order_class%% .squad-social-share__btn',
						'declaration' => sprintf( 'color: %s;', $icon ),
					)
				);
			}
		}
	}

	/**
	 * Network select options derived from the shared registry.
	 *
	 * @return array<string, string>
	 */
	private static function network_options(): array {
		$options = array();
		foreach ( Networks::get_networks() as $slug => $meta ) {
			$options[ $slug ] = $meta['label'];
		}

		return $options;
	}
}
