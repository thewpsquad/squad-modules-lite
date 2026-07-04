<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Pricing Table Item (child) Module Class.
 *
 * A single pricing-plan card (title, price, features, CTA) rendered with
 * schema.org Product markup.
 *
 * @since   4.2.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Content;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use DiviSquad\Builder\Version4\Abstracts\Module\Child_Module;
use DiviSquad\Core\Supports\Polyfills\Str;
use function esc_attr;
use function esc_html;
use function esc_html__;
use function esc_url;
use function explode;
use function ltrim;
use function trim;

/**
 * Pricing Table Item (child) Module Class.
 *
 * @since 4.2.0
 */
class Pricing_Table_Item extends Child_Module {

	public function init(): void {
		$this->name   = esc_html__( 'Pricing Plan', 'squad-modules-for-divi' );
		$this->plural = esc_html__( 'Pricing Plans', 'squad-modules-for-divi' );

		$this->slug             = 'disq_pricing_table_item';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.{$this->slug}";

		$this->child_title_var          = 'title';
		$this->child_title_fallback_var = 'admin_label';

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'content'  => esc_html__( 'Plan', 'squad-modules-for-divi' ),
					'features' => esc_html__( 'Features', 'squad-modules-for-divi' ),
					'button'   => esc_html__( 'Button', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'highlight' => esc_html__( 'Highlight', 'squad-modules-for-divi' ),
					'title'     => esc_html__( 'Title Text', 'squad-modules-for-divi' ),
					'price'     => esc_html__( 'Price Text', 'squad-modules-for-divi' ),
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
			'fonts'          => array(
				'title' => array(
					'label'        => esc_html__( 'Title', 'squad-modules-for-divi' ),
					'css'          => array( 'main' => "{$this->main_css_element} .squad-pricing__title" ),
					'tab_slug'     => 'advanced',
					'toggle_slug'  => 'title',
					'font_size'    => array( 'default' => '20px' ),
					'line_height'  => array( 'default' => '1.3em' ),
				),
				'price' => array(
					'label'       => esc_html__( 'Price', 'squad-modules-for-divi' ),
					'css'         => array( 'main' => "{$this->main_css_element} .squad-pricing__price" ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'price',
					'font_size'   => array( 'default' => '40px' ),
					'line_height' => array( 'default' => '1.1em' ),
				),
			),
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
			'title'       => array(
				'label'       => esc_html__( 'Plan Title', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'The plan name.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => esc_html__( 'Starter', 'squad-modules-for-divi' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'content',
			),
			'price'       => array(
				'label'       => esc_html__( 'Price', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'The plan price, e.g. $29.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '$29',
				'tab_slug'    => 'general',
				'toggle_slug' => 'content',
			),
			'period'      => array(
				'label'       => esc_html__( 'Period', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Billing period, e.g. /mo.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => esc_html__( '/mo', 'squad-modules-for-divi' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'content',
			),
			'description' => array(
				'label'       => esc_html__( 'Description', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'A short description under the price.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'tab_slug'    => 'general',
				'toggle_slug' => 'content',
			),
			'ribbon'      => array(
				'label'       => esc_html__( 'Ribbon Text', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Optional badge, e.g. Popular. Leave empty to hide.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'tab_slug'    => 'general',
				'toggle_slug' => 'content',
			),
			'is_featured' => divi_squad()->d4_module_helper->add_yes_no_field(
				esc_html__( 'Featured Plan', 'squad-modules-for-divi' ),
				array(
					'description' => esc_html__( 'Highlight this plan with the accent color.', 'squad-modules-for-divi' ),
					'default'     => 'off',
					'tab_slug'    => 'general',
					'toggle_slug' => 'content',
				)
			),
			'features'    => array(
				'label'       => esc_html__( 'Features', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'One feature per line. Start a line with "-" to show it as not included.', 'squad-modules-for-divi' ),
				'type'        => 'textarea',
				'default'     => esc_html__( "10 projects\n5 GB storage\nEmail support\n- Priority support", 'squad-modules-for-divi' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'features',
			),
			'button_text' => array(
				'label'       => esc_html__( 'Button Text', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Call-to-action label. Leave empty to hide.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => esc_html__( 'Get Started', 'squad-modules-for-divi' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'button',
			),
			'button_url'        => array(
				'label'       => esc_html__( 'Button URL', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Where the button links to.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '#',
				'tab_slug'    => 'general',
				'toggle_slug' => 'button',
			),
			'button_text_color' => array(
				'label'       => esc_html__( 'Button Text Color', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Color of the button label.', 'squad-modules-for-divi' ),
				'type'        => 'color-alpha',
				'default'     => '#ffffff',
				'tab_slug'    => 'general',
				'toggle_slug' => 'button',
			),
			'accent_color' => array(
				'label'       => esc_html__( 'Accent Color', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Used for the button, ribbon and featured highlight.', 'squad-modules-for-divi' ),
				'type'        => 'color-alpha',
				'default'     => '#5E2EFF',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'highlight',
			),
		);
	}

	/**
	 * Render module output.
	 *
	 * @since 4.2.0
	 *
	 * @param array<string, mixed> $attrs       List of unprocessed attributes.
	 * @param string               $content     Content being processed.
	 * @param string               $render_slug Slug of module that is used for rendering output.
	 *
	 * @return string
	 */
	public function render( $attrs, $content, $render_slug ): string {
		$this->apply_card_css( $render_slug );

		$title       = (string) $this->prop( 'title', '' );
		$price       = (string) $this->prop( 'price', '' );
		$period      = (string) $this->prop( 'period', '' );
		$description = (string) $this->prop( 'description', '' );
		$ribbon      = (string) $this->prop( 'ribbon', '' );
		$is_featured = 'on' === $this->prop( 'is_featured', 'off' );
		$features    = (string) $this->prop( 'features', '' );
		$button_text = (string) $this->prop( 'button_text', '' );
		$button_url  = (string) $this->prop( 'button_url', '#' );

		$ribbon_html = '' !== $ribbon ? sprintf( '<div class="squad-pricing__ribbon">%s</div>', esc_html( $ribbon ) ) : '';

		$title_html = '' !== $title ? sprintf( '<h3 class="squad-pricing__title" itemprop="name">%s</h3>', esc_html( $title ) ) : '';

		$price_html = '';
		if ( '' !== $price ) {
			$period_html = '' !== $period ? sprintf( '<span class="squad-pricing__period">%s</span>', esc_html( $period ) ) : '';
			$price_html  = sprintf( '<div class="squad-pricing__price">%s%s</div>', esc_html( $price ), $period_html );
		}

		$desc_html     = '' !== $description ? sprintf( '<div class="squad-pricing__desc" itemprop="description">%s</div>', esc_html( $description ) ) : '';
		$features_html = $this->render_features( $features );

		$button_html = '';
		if ( '' !== $button_text ) {
			$button_text_color = self::sanitize_css_background( (string) $this->prop( 'button_text_color', '#ffffff' ) );
			$button_text_color = '' !== $button_text_color ? $button_text_color : '#ffffff';

			$button_html = sprintf(
				'<a class="squad-pricing__button" href="%s" style="color:%s;">%s</a>',
				esc_url( '' !== $button_url ? $button_url : '#' ),
				esc_attr( $button_text_color ),
				esc_html( $button_text )
			);
		}

		return sprintf(
			'<div class="squad-pricing%1$s" itemscope itemtype="https://schema.org/Product">%2$s<div class="squad-pricing__head">%3$s%4$s%5$s</div>%6$s%7$s</div>',
			$is_featured ? ' is-featured' : '',
			$ribbon_html,
			$title_html,
			$price_html,
			$desc_html,
			$features_html,
			$button_html
		);
	}

	/**
	 * Build the feature list. Lines starting with "-" render as not-included.
	 *
	 * @since 4.2.0
	 *
	 * @param string $features Raw newline-separated feature list.
	 *
	 * @return string
	 */
	private function render_features( string $features ): string {
		if ( '' === trim( $features ) ) {
			return '';
		}

		$items = '';
		foreach ( explode( "\n", $features ) as $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}

			$unavailable = Str::starts_with( $line, '-' );
			$text        = $unavailable ? trim( ltrim( $line, '-' ) ) : $line;

			$items .= sprintf(
				'<li class="squad-pricing__feature %1$s"><span class="squad-pricing__icon" aria-hidden="true">%2$s</span><span class="squad-pricing__feature-text">%3$s</span></li>',
				$unavailable ? 'is-unavailable' : 'is-available',
				$unavailable ? '✕' : '✓',
				esc_html( $text )
			);
		}

		return '' !== $items ? sprintf( '<ul class="squad-pricing__features">%s</ul>', $items ) : '';
	}

	/**
	 * Emit accent + featured CSS for the card.
	 *
	 * @since 4.2.0
	 *
	 * @param string $render_slug Slug of module that is used for rendering output.
	 *
	 * @return void
	 */
	public function apply_card_css( string $render_slug ): void {
		$accent = self::sanitize_css_background( (string) $this->prop( 'accent_color', '#5E2EFF' ) );
		if ( '' === $accent ) {
			return;
		}

		$accent = esc_attr( $accent );

		self::set_style(
			$render_slug,
			array(
				'selector'    => '%%order_class%% .squad-pricing__button',
				'declaration' => sprintf( 'background: %s;', $accent ),
			)
		);
		self::set_style(
			$render_slug,
			array(
				'selector'    => '%%order_class%% .squad-pricing__ribbon',
				'declaration' => sprintf( 'background: %s;', $accent ),
			)
		);
		self::set_style(
			$render_slug,
			array(
				'selector'    => '%%order_class%% .squad-pricing.is-featured',
				'declaration' => sprintf( 'border-color: %s;', $accent ),
			)
		);
	}
}
