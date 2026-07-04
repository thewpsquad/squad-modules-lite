<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Pricing Table Item (child) Module (Divi 5 / Block API).
 *
 * A single pricing-plan card with schema.org Product markup.
 *
 * @since   4.2.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version5\Modules\Content;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

if ( ! class_exists( 'ET\Builder\Packages\Module\Module' ) ) {
	return;
}

use DiviSquad\Builder\Version5\Abstracts\Module;
use DiviSquad\Core\Supports\Polyfills\Str;
use ET\Builder\FrontEnd\Module\Style;
use ET\Builder\Packages\Module\Layout\Components\ModuleElements\ModuleElements;
use ET\Builder\Packages\Module\Module as DiviModule;
use ET\Builder\Packages\Module\Options\Css\CssStyle;
use ET\Builder\Packages\Module\Options\Element\ElementClassnames;
use Throwable;
use WP_Block;
use function esc_attr;
use function esc_html;
use function esc_url;
use function explode;
use function ltrim;
use function preg_replace;
use function substr;
use function trim;

/**
 * Pricing Table Item (child) module class.
 *
 * @since 4.2.0
 */
class Pricing_Table_Item extends Module {

	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/pricing-table-item/';
	}

	/**
	 * Add CSS classnames to the module wrapper.
	 *
	 * @param array<string, mixed> $args Classnames arguments provided by Divi.
	 *
	 * @return void
	 */
	public static function module_classnames( array $args ): void {
		$args['classnamesInstance']->add( 'squad-pricing-table-item' );
		$args['classnamesInstance']->add(
			ElementClassnames::classnames(
				array( 'attrs' => $args['attrs']['module']['decoration'] ?? array() )
			)
		);
	}

	/**
	 * Register the module's script data.
	 *
	 * @param array<string, mixed> $args Script data arguments provided by Divi.
	 *
	 * @return void
	 */
	public static function module_script_data( array $args ): void {
		$args['elements']->script_data( array( 'attrName' => 'module' ) );
	}

	/**
	 * Register the module's styles.
	 *
	 * @param array<string, mixed> $args Style arguments provided by Divi.
	 *
	 * @return void
	 */
	public static function module_styles( array $args ): void {
		$attrs    = $args['attrs'] ?? array();
		$elements = $args['elements'];
		$settings = $args['settings'] ?? array();

		Style::add(
			array(
				'id'            => $args['id'],
				'name'          => $args['name'],
				'orderIndex'    => $args['orderIndex'],
				'storeInstance' => $args['storeInstance'],
				'styles'        => array(
					$elements->style(
						array(
							'attrName'   => 'module',
							'styleProps' => array(
								'disabledOn' => array(
									'disabledModuleVisibility' => $settings['disabledModuleVisibility'] ?? null,
								),
							),
						)
					),
					CssStyle::style(
						array( 'selector' => $args['orderClass'], 'attr' => $attrs['css'] ?? array() )
					),
				),
			)
		);
	}

	/**
	 * Render the Pricing Plan card on the frontend.
	 *
	 * @param array<string, mixed> $attrs    Block attributes.
	 * @param string               $content  Inner content (unused).
	 * @param WP_Block             $block    Parsed block instance.
	 * @param ModuleElements       $elements ModuleElements instance.
	 *
	 * @return string Rendered HTML.
	 */
	public static function render_callback( array $attrs, string $content, WP_Block $block, $elements ): string {
		try {
			$item = $attrs['plan']['innerContent']['desktop']['value'] ?? array();

			$title       = (string) ( $item['title'] ?? '' );
			$price       = (string) ( $item['price'] ?? '' );
			$period      = (string) ( $item['period'] ?? '' );
			$description = (string) ( $item['description'] ?? '' );
			$ribbon      = (string) ( $item['ribbon'] ?? '' );
			$is_featured = 'on' === ( $item['isFeatured'] ?? 'off' );
			$features    = (string) ( $item['features'] ?? '' );
			$button_text = (string) ( $item['buttonText'] ?? '' );
			$button_url  = (string) ( $item['buttonUrl'] ?? '#' );
			$uid         = self::get_instance_uid( $block );

			$ribbon_html = '' !== $ribbon ? sprintf( '<div class="squad-pricing__ribbon">%s</div>', esc_html( $ribbon ) ) : '';
			$title_html  = '' !== $title ? sprintf( '<h3 class="squad-pricing__title" itemprop="name">%s</h3>', esc_html( $title ) ) : '';

			$price_html = '';
			if ( '' !== $price ) {
				$period_html = '' !== $period ? sprintf( '<span class="squad-pricing__period">%s</span>', esc_html( $period ) ) : '';
				$price_html  = sprintf( '<div class="squad-pricing__price">%s%s</div>', esc_html( $price ), $period_html );
			}

			$desc_html     = '' !== $description ? sprintf( '<div class="squad-pricing__desc" itemprop="description">%s</div>', esc_html( $description ) ) : '';
			$features_html = self::render_features( $features );

			$button_html = '';
			if ( '' !== $button_text ) {
				$button_text_color = self::sanitize_css_background( (string) ( $item['buttonTextColor'] ?? '#ffffff' ) );
				$button_text_color = '' !== $button_text_color ? $button_text_color : '#ffffff';

				$button_html = sprintf(
					'<a class="squad-pricing__button" href="%s" style="color:%s;">%s</a>',
					esc_url( '' !== $button_url ? $button_url : '#' ),
					esc_attr( $button_text_color ),
					esc_html( $button_text )
				);
			}

			$inline_css = self::get_card_css( $item, $uid );

			$style_components = $elements instanceof ModuleElements
				? (string) $elements->style_components( array( 'attrName' => 'module' ) )
				: '';

			$card_html = sprintf(
				'%1$s<div class="squad-pricing%2$s %3$s" itemscope itemtype="https://schema.org/Product">%4$s<div class="squad-pricing__head">%5$s%6$s%7$s</div>%8$s%9$s</div>',
				'' !== $inline_css ? sprintf( '<style>%s</style>', $inline_css ) : '',
				$is_featured ? ' is-featured' : '',
				esc_attr( $uid ),
				$ribbon_html,
				$title_html,
				$price_html,
				$desc_html,
				$features_html,
				$button_html
			);

			return DiviModule::render(
				array(
					'orderIndex'          => $block->parsed_block['orderIndex'],
					'storeInstance'       => $block->parsed_block['storeInstance'],
					'attrs'               => $attrs,
					'elements'            => $elements,
					'id'                  => $block->parsed_block['id'],
					'name'                => $block->block_type->name,
					'moduleCategory'      => $block->block_type->category,
					'classnamesFunction'  => array( static::class, 'module_classnames' ),
					'stylesComponent'     => array( static::class, 'module_styles' ),
					'scriptDataComponent' => array( static::class, 'module_script_data' ),
					'children'            => $style_components . $card_html,
				)
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Pricing Table Item module' );

			return '';
		}
	}

	protected static function get_instance_uid( WP_Block $block ): string {
		$raw = (string) ( $block->parsed_block['id'] ?? '' );
		$uid = preg_replace( '/[^a-z0-9]/', '', strtolower( $raw ) );

		return ( null !== $uid && '' !== $uid )
			? 'squad-pti-' . $uid
			: 'squad-pti-' . substr( md5( $raw ), 0, 10 );
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
	protected static function render_features( string $features ): string {
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
	 * Build the scoped accent CSS for a single plan card.
	 *
	 * @since 4.2.0
	 *
	 * @param array<string, mixed> $item Plan item values.
	 * @param string               $uid  Unique scoping class for this instance.
	 *
	 * @return string Scoped CSS (may be empty).
	 */
	protected static function get_card_css( array $item, string $uid ): string {
		$accent = self::sanitize_css_background( (string) ( $item['accentColor'] ?? '#5E2EFF' ) );
		if ( '' === $accent ) {
			return '';
		}

		$accent = esc_attr( $accent );

		$css  = ".{$uid} .squad-pricing__button{background:{$accent};}";
		$css .= ".{$uid} .squad-pricing__ribbon{background:{$accent};}";
		$css .= ".{$uid} .squad-pricing.is-featured{border-color:{$accent};}";

		return $css;
	}
}
