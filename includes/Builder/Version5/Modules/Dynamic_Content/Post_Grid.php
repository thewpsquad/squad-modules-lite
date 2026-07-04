<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Post Grid Module (Divi 5 / Block API).
 *
 * Native Divi 5 implementation of the Post Grid module. Queries posts server-side and, for
 * each post, renders the nested "Post Element" (Post_Grid_Child) modules with that post's
 * data — mirroring the Divi 4 module's grid output (`ul.squad-post-container > li.post`).
 *
 * Each child emits a base64 config marker (`<!--squad-pg-element:…-->`) from its render
 * callback; this parent replaces every marker, per post, with the rendered element.
 *
 * Phase 1: query (post type / category / tag / order / count / offset / sticky), the grid
 * wrapper, and the common elements. Pagination, load-more (AJAX), and the advanced elements
 * (gravatar, custom field, ACF, custom icon) are handled in a later phase.
 *
 * @since   3.4.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version5\Modules\Dynamic_Content;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

if ( ! class_exists( 'ET\Builder\Packages\Module\Module' ) ) {
	return;
}

use DiviSquad\Builder\Utils\Elements\Custom_Fields\Collection_Interface;
use DiviSquad\Builder\Version5\Abstracts\Module;
use ET\Builder\FrontEnd\Module\Style;
use ET\Builder\Packages\Module\Layout\Components\ModuleElements\ModuleElements;
use ET\Builder\Packages\Module\Module as DiviModule;
use ET\Builder\Packages\Module\Options\Css\CssStyle;
use ET\Builder\Packages\Module\Options\Element\ElementClassnames;
use Throwable;
use WP_Block;
use WP_Post;
use WP_Query;
use WP_Term;
use WP_User;
use function absint;
use function add_query_arg;
use function esc_attr;
use function esc_html;
use function esc_html__;
use function esc_url;
use function esc_url_raw;
use function get_author_posts_url;
use function get_avatar;
use function get_category_link;
use function get_comments_number;
use function get_permalink;
use function get_post_class;
use function get_queried_object;
use function get_query_var;
use function get_search_query;
use function get_tag_link;
use function get_the_post_thumbnail;
use function get_userdata;
use function is_archive;
use function is_author;
use function is_date;
use function is_search;
use function is_singular;
use function is_wp_error;
use function number_format_i18n;
use function paginate_links;
use function sanitize_key;
use function wp_date;
use function wp_enqueue_script;
use function wp_get_post_categories;
use function wp_get_post_tags;
use function wp_json_encode;
use function wp_kses_post;
use function wp_reset_postdata;
use function wp_strip_all_tags;

/**
 * Post Grid module class.
 *
 * @since 3.4.0
 */
class Post_Grid extends Module {

	/**
	 * Relative path to the generated module.json metadata folder.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/post-grid/';
	}

	/**
	 * Add the module classnames.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $args Classnames arguments.
	 *
	 * @return void
	 */
	public static function module_classnames( array $args ): void {
		// Mirror the Divi 4 wrapper class so the shared frontend load-more script can target the module.
		$args['classnamesInstance']->add( 'disq_post_grid' );
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
	 * @since 3.4.0
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
	 * @since 3.4.0
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
					$elements->style( array( 'attrName' => 'postItem' ) ),
					$elements->style( array( 'attrName' => 'loadMoreButton' ) ),
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
	 * Render callback for the Post Grid module.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $attrs                 Block attributes.
	 * @param string               $child_modules_content Rendered child (Post Element) content with config markers.
	 * @param WP_Block             $block                 Parsed block instance.
	 * @param object               $elements              ModuleElements instance.
	 *
	 * @return string Rendered HTML.
	 */
	public static function render_callback( array $attrs, string $child_modules_content, WP_Block $block, $elements ): string {
		try {
			if ( '' === trim( $child_modules_content ) ) {
				return self::render_notice( esc_html__( 'Add at least one Post Element to display.', 'squad-modules-for-divi' ) );
			}

			$inner = $attrs['query']['innerContent']['desktop']['value'] ?? array();
			$query = new WP_Query( self::build_query_args( $inner ) );

			if ( ! $query->have_posts() ) {
				wp_reset_postdata();

				return self::render_notice( esc_html__( 'No posts found.', 'squad-modules-for-divi' ) );
			}

			$items = self::render_post_items( $child_modules_content, $query );
			wp_reset_postdata();

			$columns = absint( $inner['listNumberOfColumns'] ?? 3 );
			$columns = $columns > 0 ? $columns : 3;
			$gap     = self::sanitize_css_length( (string) ( $inner['listItemGap'] ?? '30px' ) );
			$gap     = '' !== $gap ? $gap : '30px';

			$grid_html = sprintf(
				'<ul class="squad-post-container" style="list-style-type: none; --squad-post-grid-columns: %1$d; --squad-post-grid-gap: %2$s;">%3$s</ul>',
				$columns,
				esc_attr( $gap ),
				$items
			);

			$grid_html .= self::render_load_more( $inner, $query, $child_modules_content );
			$grid_html .= self::render_pagination( $inner, $query );

			$style_components = $elements instanceof ModuleElements
				? $elements->style_components( array( 'attrName' => 'module' ) )
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
					'children'            => $style_components . $grid_html,
				)
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Post Grid module' );

			return '';
		}
	}

	/**
	 * Build the WP_Query arguments from the query settings.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $inner Query inner-content values.
	 *
	 * @return array<string, mixed>
	 */
	public static function build_query_args( array $inner ): array {
		$args = array(
			'post_status'    => array( 'publish' ),
			'perm'           => array( 'readable' ),
			'posts_per_page' => absint( $inner['listPostCount'] ?? 10 ),
			'orderby'        => sanitize_key( $inner['listPostOrderBy'] ?? 'date' ),
			'order'          => 'ASC' === strtoupper( (string) ( $inner['listPostOrder'] ?? 'DESC' ) ) ? 'ASC' : 'DESC',
		);

		if ( 'on' === ( $inner['listInheritCurrentLoop'] ?? 'off' ) ) {
			$args = self::add_current_loop_args( $args );
		} else {
			$display_by = sanitize_key( $inner['listPostDisplayBy'] ?? 'recent' );
			if ( 'category' === $display_by && '' !== ( $inner['listPostIncludeCategories'] ?? '' ) ) {
				$args['cat'] = sanitize_text_field( (string) $inner['listPostIncludeCategories'] );
			} elseif ( 'tag' === $display_by && '' !== ( $inner['listPostIncludeTags'] ?? '' ) ) {
				$args['tag__in'] = array_filter( array_map( 'absint', explode( ',', (string) $inner['listPostIncludeTags'] ) ), static fn( int $id ): bool => $id > 0 );
			}
		}

		$exclude = array_filter( array_map( 'absint', explode( ',', (string) ( $inner['listPostExclude'] ?? '' ) ) ), static fn( int $id ): bool => $id > 0 );
		if ( count( $exclude ) > 0 ) {
			$args['post__not_in'] = isset( $args['post__not_in'] ) ? array_merge( (array) $args['post__not_in'], $exclude ) : $exclude;
		}

		$offset = absint( $inner['listPostOffset'] ?? 0 );
		if ( $offset > 0 ) {
			$args['offset'] = $offset;
		}

		// Pagination: respect the current page when numbered pagination is enabled.
		if ( 'on' === ( $inner['paginationEnable'] ?? 'off' ) ) {
			$paged = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
			if ( $paged > 1 ) {
				$args['paged'] = $paged;
				unset( $args['offset'] );
			}
		}

		if ( 'on' === ( $inner['listPostIgnoreSticky'] ?? 'off' ) ) {
			$args['ignore_sticky_posts'] = true;
		}

		/** This filter is documented in includes/Builder/Version4/Modules/Post_Grid.php */
		return (array) apply_filters( 'divi_squad_build_post_query_args', $args, $inner, '' );
	}

	/**
	 * Inherit the current page's query context (related posts, author / term / search / date).
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $args Existing query arguments.
	 *
	 * @return array<string, mixed>
	 */
	protected static function add_current_loop_args( array $args ): array {
		$queried = get_queried_object();

		if ( $queried instanceof WP_Post && is_singular() ) {
			$categories = wp_get_post_categories( $queried->ID, array( 'fields' => 'ids' ) );
			if ( ! is_wp_error( $categories ) && count( $categories ) > 0 ) {
				$args['category__in'] = $categories;
			}

			$tags = wp_get_post_tags( $queried->ID, array( 'fields' => 'ids' ) );
			if ( ! is_wp_error( $tags ) && count( $tags ) > 0 ) {
				$args['tag__in'] = $tags;
			}

			$args['post__not_in'] = array( $queried->ID );
			$args['author']       = $queried->post_author;
		} elseif ( $queried instanceof WP_User && is_author() ) {
			$args['author'] = $queried->ID;
		} elseif ( $queried instanceof WP_Term && is_archive() ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			                            array(
				                            'taxonomy' => $queried->taxonomy,
				                            'field'    => 'term_id',
				                            'terms'    => $queried->term_id,
			                            ),
			);
		} elseif ( is_search() ) {
			$args['s'] = get_search_query();
		} elseif ( is_date() ) {
			foreach ( array( 'year', 'monthnum', 'w', 'day', 'hour', 'minute', 'second' ) as $key ) {
				$value = get_query_var( $key );
				if ( '' !== $value ) {
					$args[ $key ] = $value;
				}
			}
		}

		return $args;
	}

	/**
	 * Render the grid `<li>` items by replacing each post's element markers.
	 *
	 * Shared by the initial render and the load-more REST endpoint.
	 *
	 * @since 3.4.0
	 *
	 * @param string   $template   The child marker template (`<!--squad-pg-element:…-->`).
	 * @param WP_Query $query      The post query (already executed, with posts).
	 * @param string   $item_tag   Wrapper tag for each post (default `li`).
	 * @param string   $item_class Extra class on each post wrapper (e.g. `swiper-slide`).
	 *
	 * @return string
	 */
	public static function render_post_items( string $template, WP_Query $query, string $item_tag = 'li', string $item_class = '' ): string {
		$items = '';
		$tag   = preg_replace( '/[^a-z0-9]/', '', strtolower( $item_tag ) );
		$tag   = '' !== $tag ? $tag : 'li';

		while ( $query->have_posts() ) {
			$query->the_post();
			$post = $query->post;

			if ( ! $post instanceof WP_Post ) {
				continue;
			}

			$post_html = (string) preg_replace_callback(
				'/<!--squad-pg-element:([A-Za-z0-9+\/=]+)-->/',
				static function ( $matches ) use ( $post ): string {
					$config = json_decode( (string) base64_decode( $matches[1], true ), true );

					return is_array( $config ) ? self::render_post_element( $config, $post ) : '';
				},
				$template
			);

			$classes = get_post_class( 'post', $post->ID );
			if ( '' !== $item_class ) {
				array_unshift( $classes, $item_class );
			}

			$items .= sprintf(
				'<%1$s class="%2$s">%3$s</%1$s>',
				$tag,
				esc_attr( implode( ' ', $classes ) ),
				$post_html
			);
		}

		return $items;
	}

	/**
	 * Render the next page of grid items for the load-more REST endpoint.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $client_args Query arguments sent by the frontend.
	 * @param string               $template    The decoded child marker template.
	 *
	 * @return string The rendered `<li>` items, or an empty string when no posts remain.
	 */
	public static function render_more_posts( array $client_args, string $template ): string {
		$args = array(
			'post_status'    => array( 'publish' ),
			'perm'           => array( 'readable' ),
			'posts_per_page' => absint( $client_args['posts_per_page'] ?? 10 ),
			'offset'         => absint( $client_args['offset'] ?? 0 ),
			'orderby'        => sanitize_key( $client_args['orderby'] ?? 'date' ),
			'order'          => 'ASC' === strtoupper( (string) ( $client_args['order'] ?? 'DESC' ) ) ? 'ASC' : 'DESC',
		);

		$display_by = sanitize_key( $client_args['displayBy'] ?? 'recent' );
		if ( 'category' === $display_by && '' !== ( $client_args['categories'] ?? '' ) ) {
			$args['cat'] = sanitize_text_field( (string) $client_args['categories'] );
		} elseif ( 'tag' === $display_by && '' !== ( $client_args['tags'] ?? '' ) ) {
			$args['tag__in'] = array_filter( array_map( 'absint', explode( ',', (string) $client_args['tags'] ) ), static fn( int $id ): bool => $id > 0 );
		}

		$exclude = array_filter( array_map( 'absint', explode( ',', (string) ( $client_args['exclude'] ?? '' ) ) ), static fn( int $id ): bool => $id > 0 );
		if ( count( $exclude ) > 0 ) {
			$args['post__not_in'] = $exclude;
		}

		if ( 'on' === ( $client_args['ignoreSticky'] ?? 'off' ) ) {
			$args['ignore_sticky_posts'] = true;
		}

		/** This filter is documented in includes/Builder/Version4/Modules/Post_Grid.php */
		$args  = (array) apply_filters( 'divi_squad_build_post_query_args', $args, $client_args, '' );
		$query = new WP_Query( $args );

		if ( ! $query->have_posts() ) {
			wp_reset_postdata();

			return '';
		}

		$items = self::render_post_items( $template, $query );
		wp_reset_postdata();

		return $items;
	}

	/**
	 * Build the client-side query arguments carried in the load-more data block.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $inner Query inner-content values.
	 *
	 * @return array<string, mixed>
	 */
	protected static function build_client_query_args( array $inner ): array {
		$per_page = absint( $inner['listPostCount'] ?? 10 );
		$per_page = $per_page > 0 ? $per_page : 10;

		return array(
			'posts_per_page' => $per_page,
			// First load-more request starts after the already-rendered first page.
			'offset'         => $per_page + absint( $inner['listPostOffset'] ?? 0 ),
			'orderby'        => sanitize_key( $inner['listPostOrderBy'] ?? 'date' ),
			'order'          => 'ASC' === strtoupper( (string) ( $inner['listPostOrder'] ?? 'DESC' ) ) ? 'ASC' : 'DESC',
			'displayBy'      => sanitize_key( $inner['listPostDisplayBy'] ?? 'recent' ),
			'categories'     => sanitize_text_field( (string) ( $inner['listPostIncludeCategories'] ?? '' ) ),
			'tags'           => sanitize_text_field( (string) ( $inner['listPostIncludeTags'] ?? '' ) ),
			'exclude'        => sanitize_text_field( (string) ( $inner['listPostExclude'] ?? '' ) ),
			'ignoreSticky'   => 'on' === ( $inner['listPostIgnoreSticky'] ?? 'off' ) ? 'on' : 'off',
			'builder'        => 'divi-5',
		);
	}

	/**
	 * Render the AJAX load-more button + data block when enabled.
	 *
	 * Emits the same DOM contract as the Divi 4 module so the shared
	 * `play-load-more-posts` frontend script can drive it. The marker template is
	 * base64-encoded so its HTML comments survive REST request sanitisation.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $inner    Query inner-content values.
	 * @param WP_Query             $query    The post query.
	 * @param string               $template The child marker template.
	 *
	 * @return string
	 */
	protected static function render_load_more( array $inner, WP_Query $query, string $template ): string {
		if ( 'on' !== ( $inner['loadMoreEnable'] ?? 'off' ) ) {
			return '';
		}

		$per_page = absint( $inner['listPostCount'] ?? 10 );
		$per_page = $per_page > 0 ? $per_page : 10;

		// Nothing left to load beyond the first page.
		if ( (int) $query->found_posts <= $per_page ) {
			return '';
		}

		$text = (string) ( $inner['loadMoreText'] ?? '' );
		$text = '' !== $text ? $text : esc_html__( 'Load More', 'squad-modules-for-divi' );

		// Enqueue the shared frontend load-more handler.
		wp_enqueue_script( 'squad-module-post-grid' );

		$button_options = array(
			'endpoint_url'   => 'squad-modules-for-divi/v1/module/post-grid/load-more-divi5',
			'posts_per_page' => $per_page,
			'total_posts'    => (int) $query->found_posts,
			'total_pages'    => (int) $query->max_num_pages,
		);

		$query_options = array(
			'query_args' => self::build_client_query_args( $inner ),
			'content'    => base64_encode( $template ),
		);

		// Optional button icon.
		$icon_html  = '';
		$icon_char  = self::resolve_icon( $inner['loadMoreIcon'] ?? array() );
		if ( '' !== $icon_char ) {
			$icon_html = sprintf(
				'<span class="squad-icon-wrapper"><span class="icon-element"><span class="et-pb-icon squad-button-icon">%s</span></span></span>',
				esc_html( $icon_char )
			);
		}

		$placement  = 'right' === ( $inner['loadMoreIconPlacement'] ?? 'left' ) ? 'right' : 'left';
		$text_html  = sprintf( '<span class="button-text">%s</span>', esc_html( $text ) );
		$inner_html = 'right' === $placement ? $text_html . $icon_html : $icon_html . $text_html;

		$button_class = 'squad-load-more-button et_pb_with_background';
		if ( 'on' !== ( $inner['loadMoreSpinnerShow'] ?? 'on' ) ) {
			$button_class .= ' squad-no-spinner';
		}

		return sprintf(
			'<div class="squad-load-more-button-wrapper" data-options=\'%1$s\'><script type="application/json" style="display: none">%2$s</script><div class="%3$s">%4$s</div></div>',
			(string) wp_json_encode( $button_options ),
			(string) wp_json_encode( $query_options ),
			esc_attr( $button_class ),
			$inner_html
		);
	}

	/**
	 * Render a single post element from its child config + the current post.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $config The child element configuration.
	 * @param WP_Post              $post   The current post.
	 *
	 * @return string
	 */
	protected static function render_post_element( array $config, WP_Post $post ): string {
		$element = sanitize_key( $config['element'] ?? 'none' );
		$class   = sprintf( 'et_pb_with_background squad-post-element squad-post-element__%s', esc_attr( $element ) );

		switch ( $element ) {
			case 'title':
				$out = self::element_title( $config, $post, $class );
				break;
			case 'image':
			case 'featured_image':
				$out = self::element_image( $config, $post, $class );
				break;
			case 'content':
				$out = self::element_content( $config, $post, $class );
				break;
			case 'author':
				$out = self::element_author( $config, $post, $class );
				break;
			case 'gravatar':
				$out = self::element_gravatar( $config, $post, $class );
				break;
			case 'custom_field':
				$out = self::element_custom_field( $config, $post, $class );
				break;
			case 'advanced_custom_field':
				$out = self::element_acf( $config, $post, $class );
				break;
			case 'date':
				$out = self::element_date( $config, $post, $class );
				break;
			case 'read_more':
				$out = self::element_read_more( $config, $post, $class );
				break;
			case 'comments':
				$out = self::element_comments( $config, $post, $class );
				break;
			case 'categories':
				$out = self::element_categories( $config, $post, $class );
				break;
			case 'tags':
				$out = self::element_tags( $config, $post, $class );
				break;
			case 'custom_text':
				$out = self::element_custom_text( $config, $class );
				break;
			case 'custom_icon':
				// Empty wrapper; the icon is injected by maybe_prepend_icon().
				$out = sprintf( '<div class="%s"></div>', esc_attr( $class ) );
				break;
			case 'divider':
				$out = sprintf( '<div class="%s"><span class="divider-element"></span></div>', esc_attr( $class ) );
				break;
			default:
				$out = '';
		}

		return self::maybe_prepend_icon( $out, $config, $element, $class );
	}

	/**
	 * Inject the optional element icon for eligible element types.
	 *
	 * @since 3.4.0
	 *
	 * @param string               $html    The rendered element HTML.
	 * @param array<string, mixed> $config  Element config.
	 * @param string               $element The element type.
	 * @param string               $class   The element wrapper class.
	 *
	 * @return string
	 */
	protected static function maybe_prepend_icon( string $html, array $config, string $element, string $class ): string {
		if ( '' === $html ) {
			return $html;
		}

		$icon_char = self::resolve_icon( $config['elementIcon'] ?? array() );
		if ( '' === $icon_char ) {
			return $html;
		}

		// Icons are only meaningful on text-like elements (mirrors the Divi 4 module).
		$not_eligible = array( 'none', 'image', 'featured_image', 'content', 'gravatar', 'divider' );
		if ( in_array( $element, $not_eligible, true ) ) {
			return $html;
		}

		$placement = 'right' === ( $config['elementIconPlacement'] ?? 'left' ) ? 'right' : 'left';
		$icon_html = sprintf(
			'<span class="squad-element-icon-wrapper placement-%1$s"><span class="icon-element"><span class="et-pb-icon squad-element-icon">%2$s</span></span></span>',
			esc_attr( $placement ),
			esc_html( $icon_char )
		);

		// Insert the icon just inside the element wrapper, before or after the body.
		$open = sprintf( '<div class="%s">', esc_attr( $class ) );
		if ( 'right' === $placement ) {
			return (string) preg_replace( '/<\/div>$/', $icon_html . '</div>', $html, 1 );
		}

		return str_replace( $open, $open . $icon_html, $html );
	}

	/**
	 * Title element.
	 *
	 * @param array<string, mixed> $config Element config.
	 * @param WP_Post              $post   Post.
	 * @param string               $class  Wrapper class.
	 *
	 * @return string
	 */
	protected static function element_title( array $config, WP_Post $post, string $class ): string {
		if ( '' === $post->post_title ) {
			return '';
		}

		$tag    = self::sanitize_tag( (string) ( $config['elementTitleTag'] ?? 'span' ) );
		$output = sprintf( '<%1$s class="element-text">%2$s</%1$s>', esc_attr( $tag ), esc_html( $post->post_title ) );

		if ( 'on' === ( $config['linkToPost'] ?? 'off' ) ) {
			$output = sprintf( '<a href="%s" title="%s">%s</a>', esc_url( (string) get_permalink( $post->ID ) ), esc_attr( $post->post_title ), $output );
		}

		return sprintf( '<div class="%s">%s</div>', esc_attr( $class ), $output );
	}

	/**
	 * Featured image element.
	 *
	 * @param array<string, mixed> $config Element config.
	 * @param WP_Post              $post   Post.
	 * @param string               $class  Wrapper class.
	 *
	 * @return string
	 */
	protected static function element_image( array $config, WP_Post $post, string $class ): string {
		$image = get_the_post_thumbnail( $post->ID, 'full' );
		if ( '' === $image ) {
			return '';
		}

		if ( 'on' === ( $config['linkToPost'] ?? 'off' ) ) {
			$image = sprintf( '<a href="%s" class="image-link">%s</a>', esc_url( (string) get_permalink( $post->ID ) ), $image );
		}

		return sprintf( '<div class="%s">%s</div>', esc_attr( $class ), wp_kses_post( $image ) );
	}

	/**
	 * Content / excerpt element.
	 *
	 * @param array<string, mixed> $config Element config.
	 * @param WP_Post              $post   Post.
	 * @param string               $class  Wrapper class.
	 *
	 * @return string
	 */
	protected static function element_content( array $config, WP_Post $post, string $class ): string {
		$use_excerpt = 'on' === ( $config['elementExcerpt'] ?? 'off' );
		$content     = $use_excerpt ? $post->post_excerpt : wp_strip_all_tags( $post->post_content );

		if ( '' === $content ) {
			return '';
		}

		if ( 'on' === ( $config['elementContentLength'] ?? 'off' ) ) {
			$length = absint( $config['elementContentLengthValue'] ?? 20 );
			$words  = preg_split( '/\s+/', trim( $content ) );
			$words  = false !== $words ? $words : array();
			if ( count( $words ) > $length ) {
				$content = implode( ' ', array_slice( $words, 0, $length ) ) . '&hellip;';
			}
		}

		return sprintf( '<div class="%s"><span>%s</span></div>', esc_attr( $class ), wp_kses_post( $content ) );
	}

	/**
	 * Author element.
	 *
	 * @param array<string, mixed> $config Element config.
	 * @param WP_Post              $post   Post.
	 * @param string               $class  Wrapper class.
	 *
	 * @return string
	 */
	protected static function element_author( array $config, WP_Post $post, string $class ): string {
		$author = get_userdata( absint( $post->post_author ) );
		if ( false === $author ) {
			return '';
		}

		$type = sanitize_key( $config['elementAuthorNameType'] ?? 'nickname' );
		switch ( $type ) {
			case 'display':
				$name = $author->display_name;
				break;
			case 'first':
				$name = $author->first_name;
				break;
			case 'last':
				$name = $author->last_name;
				break;
			case 'full':
				$name = trim( $author->first_name . ' ' . $author->last_name );
				break;
			default:
				$name = $author->nickname;
		}
		$name = '' !== $name ? $name : $author->display_name;

		if ( 'on' === ( $config['linkToAuthor'] ?? 'off' ) ) {
			$content = sprintf( '<a href="%s">%s</a>', esc_url( (string) get_author_posts_url( $author->ID ) ), esc_html( $name ) );
		} else {
			$content = esc_html( $name );
		}

		return sprintf( '<div class="%s"><span>%s</span></div>', esc_attr( $class ), $content );
	}

	/**
	 * Date element.
	 *
	 * @param array<string, mixed> $config Element config.
	 * @param WP_Post              $post   Post.
	 * @param string               $class  Wrapper class.
	 *
	 * @return string
	 */
	protected static function element_date( array $config, WP_Post $post, string $class ): string {
		$type = sanitize_key( $config['elementDateType'] ?? 'publish' );
		$date = 'modified' === $type ? $post->post_modified : $post->post_date;

		$format = (string) ( $config['elementDateFormat'] ?? '' );
		$format = '' !== $format ? $format : 'M j, Y';

		$timestamp = strtotime( $date );
		$formatted = false !== $timestamp ? wp_date( $format, $timestamp ) : false;

		return sprintf(
			'<div class="%s"><time datetime="%s">%s</time></div>',
			esc_attr( $class ),
			esc_attr( $date ),
			esc_html( false !== $formatted ? $formatted : $date )
		);
	}

	/**
	 * Read more element.
	 *
	 * @param array<string, mixed> $config Element config.
	 * @param WP_Post              $post   Post.
	 * @param string               $class  Wrapper class.
	 *
	 * @return string
	 */
	protected static function element_read_more( array $config, WP_Post $post, string $class ): string {
		$text = (string) ( $config['elementReadMoreText'] ?? '' );
		$text = '' !== $text ? $text : esc_html__( 'Read More', 'squad-modules-for-divi' );

		return sprintf(
			'<div class="%s"><a href="%s">%s</a></div>',
			esc_attr( $class ),
			esc_url( (string) get_permalink( $post->ID ) ),
			esc_html( $text )
		);
	}

	/**
	 * Comments count element.
	 *
	 * @param array<string, mixed> $config Element config.
	 * @param WP_Post              $post   Post.
	 * @param string               $class  Wrapper class.
	 *
	 * @return string
	 */
	protected static function element_comments( array $config, WP_Post $post, string $class ): string {
		$count  = (int) get_comments_number( $post->ID );
		$before = (string) ( $config['elementCommentBefore'] ?? '' );
		$after  = (string) ( $config['elementCommentAfter'] ?? '' );

		$text = '' !== $before || '' !== $after
			? $before . (string) $count . $after
			: sprintf( _n( '%s Comment', '%s Comments', $count, 'squad-modules-for-divi' ), number_format_i18n( $count ) );

		return sprintf(
			'<div class="%s"><span class="element-text">%s</span></div>',
			esc_attr( $class ),
			esc_html( $text )
		);
	}

	/**
	 * Author avatar (gravatar) element.
	 *
	 * @param array<string, mixed> $config Element config.
	 * @param WP_Post              $post   Post.
	 * @param string               $class  Wrapper class.
	 *
	 * @return string
	 */
	protected static function element_gravatar( array $config, WP_Post $post, string $class ): string {
		$size     = absint( $config['elementGravatarSize'] ?? 40 );
		$gravatar = get_avatar( $post->post_author, $size > 0 ? $size : 40 );

		if ( false === $gravatar || '' === $gravatar ) {
			return '';
		}

		if ( 'on' === ( $config['linkToGravatar'] ?? 'off' ) ) {
			$gravatar = sprintf( '<a href="%s">%s</a>', esc_url( (string) get_author_posts_url( (int) $post->post_author ) ), $gravatar );
		}

		return sprintf( '<div class="%s">%s</div>', esc_attr( $class ), wp_kses_post( $gravatar ) );
	}

	/**
	 * Custom field (post meta) element.
	 *
	 * @param array<string, mixed> $config Element config.
	 * @param WP_Post              $post   Post.
	 * @param string               $class  Wrapper class.
	 *
	 * @return string
	 */
	protected static function element_custom_field( array $config, WP_Post $post, string $class ): string {
		$key = (string) ( $config['elementCustomFieldName'] ?? '' );
		if ( '' === $key ) {
			return '';
		}

		try {
			$fields = divi_squad()->custom_fields_element->get( 'custom_fields' );
			if ( ! $fields instanceof Collection_Interface || ! $fields->has_field( $post->ID, $key ) ) {
				return '';
			}
			$value = $fields->get_field_value( $post->ID, $key );
		} catch ( Throwable $e ) {
			return '';
		}

		if ( '' === (string) $value ) {
			return '';
		}

		return sprintf(
			'<div class="%s"><span class="element-text">%s%s%s</span></div>',
			esc_attr( $class ),
			esc_html( (string) ( $config['elementCustomFieldBefore'] ?? '' ) ),
			esc_html( (string) $value ),
			esc_html( (string) ( $config['elementCustomFieldAfter'] ?? '' ) )
		);
	}

	/**
	 * Advanced Custom Field (ACF) element.
	 *
	 * @param array<string, mixed> $config Element config.
	 * @param WP_Post              $post   Post.
	 * @param string               $class  Wrapper class.
	 *
	 * @return string
	 */
	protected static function element_acf( array $config, WP_Post $post, string $class ): string {
		$key = (string) ( $config['elementAcfName'] ?? '' );
		if ( '' === $key ) {
			return '';
		}

		try {
			$fields = divi_squad()->custom_fields_element->get( 'custom_fields' );
			if ( ! $fields instanceof Collection_Interface || ! $fields->has_field( $post->ID, $key ) ) {
				return '';
			}
			$value = $fields->get_field_value( $post->ID, $key );
		} catch ( Throwable $e ) {
			return '';
		}

		if ( '' === (string) $value ) {
			return '';
		}

		return sprintf(
			'<div class="%s"><span class="element-text">%s%s%s</span></div>',
			esc_attr( $class ),
			esc_html( (string) ( $config['elementAcfBefore'] ?? '' ) ),
			wp_kses_post( (string) $value ),
			esc_html( (string) ( $config['elementAcfAfter'] ?? '' ) )
		);
	}

	/**
	 * Categories element.
	 *
	 * @param array<string, mixed> $config Element config.
	 * @param WP_Post              $post   Post.
	 * @param string               $class  Wrapper class.
	 *
	 * @return string
	 */
	protected static function element_categories( array $config, WP_Post $post, string $class ): string {
		$categories = wp_get_post_categories( $post->ID, array( 'fields' => 'all' ) );
		if ( is_wp_error( $categories ) || 0 === count( $categories ) ) {
			return '';
		}

		$link  = 'on' === ( $config['linkToCategories'] ?? 'off' );
		$links = array();
		foreach ( $categories as $category ) {
			$links[] = $link
				? sprintf( '<a href="%s">%s</a>', esc_url( (string) get_category_link( $category->term_id ) ), esc_html( $category->name ) )
				: esc_html( $category->name );
		}

		$sep = (string) ( $config['elementCategoriesSepa'] ?? '' );
		$sep = '' !== $sep ? esc_html( $sep ) : ', ';

		return sprintf( '<div class="%s"><span class="element-text">%s</span></div>', esc_attr( $class ), implode( $sep, $links ) );
	}

	/**
	 * Tags element.
	 *
	 * @param array<string, mixed> $config Element config.
	 * @param WP_Post              $post   Post.
	 * @param string               $class  Wrapper class.
	 *
	 * @return string
	 */
	protected static function element_tags( array $config, WP_Post $post, string $class ): string {
		$tags = wp_get_post_tags( $post->ID );
		if ( is_wp_error( $tags ) || 0 === count( $tags ) ) {
			return '';
		}

		$link  = 'on' === ( $config['linkToTags'] ?? 'off' );
		$links = array();
		foreach ( $tags as $tag ) {
			$links[] = $link
				? sprintf( '<a href="%s">%s</a>', esc_url( (string) get_tag_link( $tag->term_id ) ), esc_html( $tag->name ) )
				: esc_html( $tag->name );
		}

		$sep = (string) ( $config['elementTagsSepa'] ?? '' );
		$sep = '' !== $sep ? esc_html( $sep ) : ', ';

		return sprintf( '<div class="%s"><span class="element-text">%s</span></div>', esc_attr( $class ), implode( $sep, $links ) );
	}

	/**
	 * Custom text element.
	 *
	 * @param array<string, mixed> $config Element config.
	 * @param string               $class  Wrapper class.
	 *
	 * @return string
	 */
	protected static function element_custom_text( array $config, string $class ): string {
		$text = (string) ( $config['elementCustomText'] ?? '' );
		if ( '' === $text ) {
			return '';
		}

		return sprintf( '<div class="%s"><span class="element-text">%s</span></div>', esc_attr( $class ), wp_kses_post( $text ) );
	}

	/**
	 * Restrict a title tag to a safe set.
	 *
	 * @param string $tag The requested tag.
	 *
	 * @return string
	 */
	protected static function sanitize_tag( string $tag ): string {
		$allowed = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span', 'div' );

		return in_array( $tag, $allowed, true ) ? $tag : 'span';
	}

	/**
	 * Render numbered pagination for the query when enabled.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $inner Query inner-content values.
	 * @param WP_Query             $query The post query.
	 *
	 * @return string
	 */
	protected static function render_pagination( array $inner, WP_Query $query ): string {
		if ( 'on' !== ( $inner['paginationEnable'] ?? 'off' ) ) {
			return '';
		}

		$total = (int) $query->max_num_pages;
		if ( $total <= 1 ) {
			return '';
		}

		$current = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );

		$icon_only    = 'on' === ( $inner['paginationIconOnly'] ?? 'off' );
		$show_numbers = 'on' === ( $inner['paginationNumbersEnable'] ?? 'off' );

		$prev_text = (string) ( $inner['paginationPrevText'] ?? '' );
		$prev_text = '' !== $prev_text ? $prev_text : esc_html__( 'Previous', 'squad-modules-for-divi' );
		$next_text = (string) ( $inner['paginationNextText'] ?? '' );
		$next_text = '' !== $next_text ? $next_text : esc_html__( 'Next', 'squad-modules-for-divi' );

		$prev_icon = self::render_pagination_icon( self::resolve_icon( $inner['paginationOldEntriesIcon'] ?? array() ), 'old' );
		$next_icon = self::render_pagination_icon( self::resolve_icon( $inner['paginationNextEntriesIcon'] ?? array() ), 'next' );

		$prev = $prev_icon . ( $icon_only ? '' : sprintf( '<span class="entries-text">%s</span>', esc_html( $prev_text ) ) );
		$next = ( $icon_only ? '' : sprintf( '<span class="entries-text">%s</span>', esc_html( $next_text ) ) ) . $next_icon;

		// Never let prev/next render empty (e.g. icon-only with no icon set).
		if ( '' === trim( $prev ) ) {
			$prev = esc_html( $prev_text );
		}
		if ( '' === trim( $next ) ) {
			$next = esc_html( $next_text );
		}

		$links_result = paginate_links(
			array(
				'base'      => esc_url_raw( (string) add_query_arg( 'paged', '%#%' ) ),
				'format'    => '',
				'current'   => $current,
				'total'     => $total,
				'prev_text' => $prev,
				'next_text' => $next,
				'type'      => 'array',
			)
		);

		// With 'type' => 'array', paginate_links() yields a list of link strings, but
		// returns void/null when there are fewer than two pages; casting that union to
		// an array gives an empty array in that case, which short-circuits below.
		$links = array_values( (array) $links_result );

		if ( count( $links ) === 0 ) {
			return '';
		}

		$has_prev = false !== strpos( (string) $links[0], 'prev' );
		$has_next = false !== strpos( (string) $links[ count( $links ) - 1 ], 'next' );

		$prev_html = '';
		$next_html = '';
		if ( $has_prev ) {
			$prev_html = str_replace( 'page-numbers', 'pagination-entries', (string) array_shift( $links ) );
		}
		if ( $has_next ) {
			$next_html = str_replace( 'page-numbers', 'pagination-entries', (string) array_pop( $links ) );
		}

		$numbers_html = '';
		if ( $show_numbers && count( $links ) > 0 ) {
			$numbers_html = sprintf( '<div class="pagination-numbers">%s</div>', implode( '', $links ) );
		}

		return sprintf(
			'<div class="squad-pagination clearfix">%s%s%s</div>',
			wp_kses_post( $prev_html ),
			wp_kses_post( $numbers_html ),
			wp_kses_post( $next_html )
		);
	}

	/**
	 * Render a pagination entry icon from an icon-picker value.
	 *
	 * @since 3.4.0
	 *
	 * @param string $icon The icon-picker value.
	 * @param string $dir  Either `old` (previous) or `next`.
	 *
	 * @return string
	 */
	protected static function render_pagination_icon( string $icon, string $dir ): string {
		if ( '' === $icon ) {
			return '';
		}

		// $icon is already the decoded glyph character from resolve_icon().
		return sprintf(
			'<span class="et-pb-icon squad-pagination_%1$s_entries-icon">%2$s</span>',
			esc_attr( $dir ),
			esc_html( $icon )
		);
	}

	/**
	 * Render a builder notice block.
	 *
	 * @param string $message The message.
	 *
	 * @return string
	 */
	protected static function render_notice( string $message ): string {
		return sprintf( '<div class="squad-notice squad-post-grid-notice">%s</div>', esc_html( $message ) );
	}}
