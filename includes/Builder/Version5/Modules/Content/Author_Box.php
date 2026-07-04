<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Author Box Module (Divi 5 / Block API).
 *
 * Native Divi 5 implementation of the Author Box module. Resolves the post
 * author server-side and renders the avatar, name, bio, and links markup.
 *
 * @since   4.0.0
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
use WP_User;
use function absint;
use function esc_attr;
use function esc_html;
use function esc_url;
use function et_core_is_fb_enabled;
use function get_author_posts_url;
use function get_avatar;
use function get_post_field;
use function get_the_ID;
use function get_user_by;
use function get_userdata;
use function in_array;
use function in_the_loop;
use function wp_kses_post;

/**
 * Author Box module class.
 *
 * @since 4.0.0
 */
class Author_Box extends Module {

	/**
	 * Relative path to the generated module.json metadata folder.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/author-box/';
	}

	/**
	 * Add the module classnames.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $args Classnames arguments.
	 *
	 * @return void
	 */
	public static function module_classnames( array $args ): void {
		$args['classnamesInstance']->add( 'disq_author_box' );
		$args['classnamesInstance']->add(
			ElementClassnames::classnames(
				array(
					'attrs' => $args['attrs']['module']['decoration'] ?? array(),
				)
			)
		);
	}

	/**
	 * Assign the module's frontend script data.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $args Script data arguments.
	 *
	 * @return void
	 */
	public static function module_script_data( array $args ): void {
		$args['elements']->script_data(
			array(
				'attrName' => 'module',
			)
		);
	}

	/**
	 * Register the module style declarations.
	 *
	 * @since 4.0.0
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
					$elements->style( array( 'attrName' => 'avatar' ) ),
					$elements->style( array( 'attrName' => 'authorName' ) ),
					$elements->style( array( 'attrName' => 'authorBio' ) ),
					$elements->style( array( 'attrName' => 'postsLink' ) ),
					$elements->style( array( 'attrName' => 'websiteLink' ) ),
					CssStyle::style(
						array(
							'selector' => $args['orderClass'],
							'attr'     => $attrs['css'] ?? array(),
						)
					),
				),
			)
		);
	}

	/**
	 * Render callback for the Author Box module.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $attrs    Block attributes.
	 * @param string               $content  Inner content.
	 * @param WP_Block             $block    Parsed block instance.
	 * @param object               $elements ModuleElements instance.
	 *
	 * @return string Rendered HTML.
	 */
	public static function render_callback( array $attrs, string $content, WP_Block $block, $elements ): string {
		try {
			$inner = $attrs['content']['innerContent']['desktop']['value'] ?? array();
			$user  = static::resolve_author( $inner );

			if ( ! $user instanceof WP_User ) {
				return '';
			}

			$layout       = (string) ( $inner['layout'] ?? 'horizontal' );
			$avatar_html  = static::render_avatar( $user, $inner );
			$content_html = static::render_content( $user, $inner );

			$html = sprintf(
				'<div class="squad-author-box squad-author-box--%1$s et_pb_with_background">%2$s%3$s</div>',
				esc_attr( $layout ),
				$avatar_html,
				$content_html
			);

			$style_components = $elements instanceof ModuleElements
				? (string) $elements->style_components( array( 'attrName' => 'module' ) )
				: '';

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
					'children'            => $style_components . $html,
				)
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Author Box module' );

			return '';
		}
	}

	/**
	 * Resolve the author WP_User from inner content attrs or current post context.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $inner The module's content inner values.
	 *
	 * @return WP_User|false
	 */
	protected static function resolve_author( array $inner ) {
		$user_id_override = trim( (string) ( $inner['authorUserId'] ?? '' ) );
		if ( '' !== $user_id_override ) {
			return get_user_by( 'id', absint( $user_id_override ) );
		}

		$is_vb   = function_exists( 'et_core_is_fb_enabled' ) && et_core_is_fb_enabled();
		$in_loop = in_the_loop();

		if ( ! $in_loop && ! $is_vb ) {
			return false;
		}

		$post_id = get_the_ID();
		if ( false === $post_id ) {
			return false;
		}

		return get_userdata( (int) get_post_field( 'post_author', $post_id ) );
	}

	/**
	 * Render the avatar block.
	 *
	 * @since 4.0.0
	 *
	 * @param WP_User              $user  The author user object.
	 * @param array<string, mixed> $inner The module's content inner values.
	 *
	 * @return string
	 */
	protected static function render_avatar( WP_User $user, array $inner ): string {
		if ( 'on' !== (string) ( $inner['showAvatar'] ?? 'on' ) ) {
			return '';
		}

		$size = absint( $inner['avatarSize'] ?? 96 );

		return sprintf(
			'<div class="squad-author-box__avatar">%s</div>',
			(string) get_avatar( $user->ID, $size, '', esc_attr( $user->display_name ) )
		);
	}

	/**
	 * Render the content block (name + bio + links).
	 *
	 * @since 4.0.0
	 *
	 * @param WP_User              $user  The author user object.
	 * @param array<string, mixed> $inner The module's content inner values.
	 *
	 * @return string
	 */
	protected static function render_content( WP_User $user, array $inner ): string {
		$name_html  = static::render_name( $user, $inner );
		$bio_html   = static::render_bio( $user, $inner );
		$links_html = static::render_links( $user, $inner );

		return sprintf(
			'<div class="squad-author-box__content">%s%s%s</div>',
			$name_html,
			$bio_html,
			$links_html
		);
	}

	/**
	 * Render the author name element.
	 *
	 * @since 4.0.0
	 *
	 * @param WP_User              $user  The author user object.
	 * @param array<string, mixed> $inner The module's content inner values.
	 *
	 * @return string
	 */
	protected static function render_name( WP_User $user, array $inner ): string {
		if ( 'on' !== (string) ( $inner['showName'] ?? 'on' ) ) {
			return '';
		}

		$allowed = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'div' );
		$tag     = (string) ( $inner['nameTag'] ?? 'h4' );
		$tag     = in_array( $tag, $allowed, true ) ? $tag : 'h4';

		return sprintf(
			'<%1$s class="squad-author-box__name">%2$s</%1$s>',
			$tag,
			esc_html( $user->display_name )
		);
	}

	/**
	 * Render the author bio element.
	 *
	 * @since 4.0.0
	 *
	 * @param WP_User              $user  The author user object.
	 * @param array<string, mixed> $inner The module's content inner values.
	 *
	 * @return string
	 */
	protected static function render_bio( WP_User $user, array $inner ): string {
		if ( 'on' !== (string) ( $inner['showBio'] ?? 'on' ) ) {
			return '';
		}
		if ( '' === (string) $user->description ) {
			return '';
		}

		return sprintf(
			'<div class="squad-author-box__bio">%s</div>',
			wp_kses_post( $user->description )
		);
	}

	/**
	 * Render the links block.
	 *
	 * @since 4.0.0
	 *
	 * @param WP_User              $user  The author user object.
	 * @param array<string, mixed> $inner The module's content inner values.
	 *
	 * @return string
	 */
	protected static function render_links( WP_User $user, array $inner ): string {
		$posts_link   = static::render_posts_link( $user, $inner );
		$website_link = static::render_website_link( $user, $inner );

		if ( '' === $posts_link && '' === $website_link ) {
			return '';
		}

		return sprintf(
			'<div class="squad-author-box__links">%s%s</div>',
			$posts_link,
			$website_link
		);
	}

	/**
	 * Render the "View all posts" link.
	 *
	 * @since 4.0.0
	 *
	 * @param WP_User              $user  The author user object.
	 * @param array<string, mixed> $inner The module's content inner values.
	 *
	 * @return string
	 */
	protected static function render_posts_link( WP_User $user, array $inner ): string {
		if ( 'on' !== (string) ( $inner['showPostsLink'] ?? 'on' ) ) {
			return '';
		}

		$text   = (string) ( $inner['postsLinkText'] ?? 'View all posts' );
		$target = (string) ( $inner['postsLinkTarget'] ?? '_blank' );

		return sprintf(
			'<a class="squad-author-box__posts-link" href="%s" target="%s">%s</a>',
			esc_url( get_author_posts_url( $user->ID ) ),
			esc_attr( $target ),
			esc_html( $text )
		);
	}

	/**
	 * Render the author website link.
	 *
	 * @since 4.0.0
	 *
	 * @param WP_User              $user  The author user object.
	 * @param array<string, mixed> $inner The module's content inner values.
	 *
	 * @return string
	 */
	protected static function render_website_link( WP_User $user, array $inner ): string {
		if ( 'on' !== (string) ( $inner['showWebsiteLink'] ?? 'on' ) ) {
			return '';
		}
		if ( '' === (string) $user->user_url ) {
			return '';
		}

		$text   = (string) ( $inner['websiteLinkText'] ?? 'Visit website' );
		$target = (string) ( $inner['websiteLinkTarget'] ?? '_blank' );

		return sprintf(
			'<a class="squad-author-box__website-link" href="%s" target="%s" rel="noopener noreferrer">%s</a>',
			esc_url( $user->user_url ),
			esc_attr( $target ),
			esc_html( $text )
		);
	}
}
