<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Testimonial Item (child) Module (Divi 5 / Block API).
 *
 * A single testimonial card with schema.org Review markup.
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
use function in_array;
use function max;
use function min;
use function preg_replace;
use function substr;
use function wp_kses_post;
use function wpautop;

/**
 * Testimonial Item (child) module class.
 *
 * @since 4.2.0
 */
class Testimonial_Item extends Module {

	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/testimonial-item/';
	}

	/**
	 * Add CSS classnames to the module wrapper.
	 *
	 * @param array<string, mixed> $args Classnames arguments provided by Divi.
	 *
	 * @return void
	 */
	public static function module_classnames( array $args ): void {
		$args['classnamesInstance']->add( 'squad-testimonial-item' );
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
	 * Render the Testimonial card on the frontend.
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
			$item = $attrs['testimonial']['innerContent']['desktop']['value'] ?? array();

			$quote     = (string) ( $item['content'] ?? '' );
			$author    = (string) ( $item['author'] ?? '' );
			$role      = (string) ( $item['role'] ?? '' );
			$company   = (string) ( $item['company'] ?? '' );
			$avatar    = (string) ( $item['avatar'] ?? '' );
			$alignment = (string) ( $item['alignment'] ?? 'left' );
			$alignment = in_array( $alignment, array( 'left', 'center', 'right' ), true ) ? $alignment : 'left';
			$uid       = self::get_instance_uid( $block );

			$rating_html = '';
			if ( 'off' !== ( $item['useRating'] ?? 'on' ) ) {
				$rating_html = self::render_rating( max( 0, min( 5, (int) ( $item['rating'] ?? 5 ) ) ) );
			}

			$quote_html = '' !== $quote ? sprintf( '<div class="squad-testimonial__content" itemprop="reviewBody">%s</div>', wpautop( wp_kses_post( $quote ) ) ) : '';

			$avatar_html = '' !== $avatar ? sprintf(
				'<div class="squad-testimonial__avatar"><img src="%1$s" alt="%2$s" itemprop="image" loading="lazy" /></div>',
				esc_url( $avatar ),
				esc_attr( $author )
			) : '';

			if ( '' !== $company ) {
				$role_text = '' !== $role
					? sprintf( '%1$s, <span itemprop="worksFor">%2$s</span>', esc_html( $role ), esc_html( $company ) )
					: sprintf( '<span itemprop="worksFor">%s</span>', esc_html( $company ) );
			} else {
				$role_text = esc_html( $role );
			}

			$name_html = '' !== $author ? sprintf( '<span class="squad-testimonial__name" itemprop="name">%s</span>', esc_html( $author ) ) : '';
			$role_html = '' !== $role_text ? sprintf( '<span class="squad-testimonial__role">%s</span>', $role_text ) : '';

			$author_block = ( '' !== $avatar_html || '' !== $name_html || '' !== $role_html )
				? sprintf(
					'<div class="squad-testimonial__author">%1$s<div class="squad-testimonial__meta" itemprop="author" itemscope itemtype="https://schema.org/Person">%2$s%3$s</div></div>',
					$avatar_html,
					$name_html,
					$role_html
				)
				: '';

			$inline_css = self::get_card_css( $item, $uid );

			$style_components = $elements instanceof ModuleElements
				? (string) $elements->style_components( array( 'attrName' => 'module' ) )
				: '';

			$card_html = sprintf(
				'%1$s<div class="squad-testimonial squad-testimonial--align-%2$s %3$s" itemscope itemtype="https://schema.org/Review">%4$s%5$s%6$s</div>',
				'' !== $inline_css ? sprintf( '<style>%s</style>', $inline_css ) : '',
				esc_attr( $alignment ),
				esc_attr( $uid ),
				$rating_html,
				$quote_html,
				$author_block
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
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Testimonial Item module' );

			return '';
		}
	}

	protected static function get_instance_uid( WP_Block $block ): string {
		$raw = (string) ( $block->parsed_block['id'] ?? '' );
		$uid = preg_replace( '/[^a-z0-9]/', '', strtolower( $raw ) );

		return ( null !== $uid && '' !== $uid )
			? 'squad-tsi-' . $uid
			: 'squad-tsi-' . substr( md5( $raw ), 0, 10 );
	}

	/**
	 * Build the star-rating markup with schema.org Rating.
	 *
	 * @since 4.2.0
	 *
	 * @param int $rating Star count (0–5).
	 *
	 * @return string
	 */
	protected static function render_rating( int $rating ): string {
		$stars = '';
		for ( $i = 1; $i <= 5; $i++ ) {
			$stars .= sprintf(
				'<span class="squad-testimonial__star%s" aria-hidden="true">%s</span>',
				$i <= $rating ? ' is-filled' : '',
				$i <= $rating ? '★' : '☆'
			);
		}

		return sprintf(
			'<div class="squad-testimonial__rating" itemprop="reviewRating" itemscope itemtype="https://schema.org/Rating"><meta itemprop="ratingValue" content="%1$d" /><meta itemprop="bestRating" content="5" />%2$s</div>',
			$rating,
			$stars
		);
	}

	/**
	 * Build the scoped star-color CSS for a single testimonial.
	 *
	 * @since 4.2.0
	 *
	 * @param array<string, mixed> $item Testimonial item values.
	 * @param string               $uid  Unique scoping class for this instance.
	 *
	 * @return string Scoped CSS (may be empty).
	 */
	protected static function get_card_css( array $item, string $uid ): string {
		$star_color = self::sanitize_css_background( (string) ( $item['starColor'] ?? '#FFB400' ) );
		if ( '' === $star_color ) {
			return '';
		}

		return ".{$uid} .squad-testimonial__star.is-filled{color:" . esc_attr( $star_color ) . ';}';
	}
}
