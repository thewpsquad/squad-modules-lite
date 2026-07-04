<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Team Member Item (child) Module (Divi 5 / Block API).
 *
 * A single team-member card with schema.org Person markup.
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
use function wp_kses_post;
use function wpautop;

/**
 * Team Member Item (child) module class.
 *
 * @since 4.2.0
 */
class Team_Member_Item extends Module {

	/**
	 * Supported social networks: attribute suffix => human label.
	 *
	 * @since 4.2.0
	 *
	 * @var array<string, string>
	 */
	private const SOCIAL_NETWORKS = array(
		'Facebook'  => 'Facebook',
		'XTwitter'  => 'X (Twitter)',
		'Linkedin'  => 'LinkedIn',
		'Instagram' => 'Instagram',
		'Youtube'   => 'YouTube',
		'Website'   => 'Website',
	);

	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/team-member-item/';
	}

	/**
	 * Add CSS classnames to the module wrapper.
	 *
	 * @param array<string, mixed> $args Classnames arguments provided by Divi.
	 *
	 * @return void
	 */
	public static function module_classnames( array $args ): void {
		$args['classnamesInstance']->add( 'squad-team-member-item' );
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
	 * Render the Team Member card on the frontend.
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
			$item = $attrs['member']['innerContent']['desktop']['value'] ?? array();

			$name      = (string) ( $item['name'] ?? '' );
			$position  = (string) ( $item['position'] ?? '' );
			$bio       = (string) ( $item['bio'] ?? '' );
			$image     = (string) ( $item['image'] ?? '' );
			$alignment = (string) ( $item['alignment'] ?? 'center' );
			$alignment = in_array( $alignment, array( 'left', 'center', 'right' ), true ) ? $alignment : 'center';

			$image_html = '';
			if ( '' !== $image ) {
				$image_html = sprintf(
					'<div class="squad-team-member__image"><img src="%1$s" alt="%2$s" itemprop="image" loading="lazy" /></div>',
					esc_url( $image ),
					esc_attr( $name )
				);
			}

			$name_html     = '' !== $name ? sprintf( '<h3 class="squad-team-member__name" itemprop="name">%s</h3>', esc_html( $name ) ) : '';
			$position_html = '' !== $position ? sprintf( '<div class="squad-team-member__position" itemprop="jobTitle">%s</div>', esc_html( $position ) ) : '';
			$bio_html      = '' !== $bio ? sprintf( '<div class="squad-team-member__bio" itemprop="description">%s</div>', wpautop( wp_kses_post( $bio ) ) ) : '';

			$style_components = $elements instanceof ModuleElements
				? (string) $elements->style_components( array( 'attrName' => 'module' ) )
				: '';

			$card_html = sprintf(
				'<div class="squad-team-member squad-team-member--align-%1$s" itemscope itemtype="https://schema.org/Person">%2$s<div class="squad-team-member__body">%3$s%4$s%5$s%6$s</div></div>',
				esc_attr( $alignment ),
				$image_html,
				$name_html,
				$position_html,
				$bio_html,
				self::render_social_links( $item )
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
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Team Member Item module' );

			return '';
		}
	}

	/**
	 * Build the social-links list from the configured URLs.
	 *
	 * @since 4.2.0
	 *
	 * @param array<string, mixed> $item The member's inner content values.
	 *
	 * @return string
	 */
	protected static function render_social_links( array $item ): string {
		if ( 'off' === ( $item['useSocial'] ?? 'on' ) ) {
			return '';
		}

		$links = '';
		foreach ( self::SOCIAL_NETWORKS as $suffix => $label ) {
			$url = (string) ( $item[ 'social' . $suffix ] ?? '' );
			if ( '' === $url ) {
				continue;
			}

			$links .= sprintf(
				'<li class="squad-team-member__social-item"><a class="squad-team-member__social-link squad-team-member__social-link--%1$s" href="%2$s" target="_blank" rel="noopener noreferrer nofollow" aria-label="%3$s" itemprop="sameAs"><span class="screen-reader-text">%3$s</span></a></li>',
				esc_attr( strtolower( $suffix ) ),
				esc_url( $url ),
				esc_attr( $label )
			);
		}

		if ( '' === $links ) {
			return '';
		}

		return sprintf( '<ul class="squad-team-member__social">%s</ul>', $links );
	}
}
