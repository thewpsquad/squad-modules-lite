<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Breadcrumbs Utils Helper
 *
 * @since   1.5.0
 * @author  The WP Squad <support@squadmodules.com>
 * @package DiviSquad
 */

namespace DiviSquad\Builder\Version4\Supports\Module_Utilities;

use DateTime;
use function esc_html;
use function esc_html__;
use function esc_url;
use function get_category_parents;
use function get_month_link;
use function get_permalink;
use function get_post;
use function get_post_type_archive_link;
use function get_post_type_object;
use function get_query_var;
use function get_search_query;
use function get_taxonomy;
use function get_term;
use function get_term_link;
use function get_the_title;
use function get_year_link;
use function home_url;
use function is_404;
use function is_archive;
use function is_author;
use function is_category;
use function is_date;
use function is_day;
use function is_front_page;
use function is_home;
use function is_month;
use function is_paged;
use function is_post_type_archive;
use function is_search;
use function is_singular;
use function is_tag;
use function is_tax;
use function is_year;
use function number_format_i18n;
use function sanitize_post;

/**
 * Breadcrumbs Utils Helper Class
 *
 * @since   1.5.0
 * @package DiviSquad
 */
class Breadcrumbs {
	/**
	 * Function that generates the HTML from breadcrumbs.
	 *
	 * @param string $_home_text   The home text.
	 * @param string $_before_text The before text for the titles.
	 * @param string $_delimiter   The separator.
	 *
	 * @return string
	 */
	public function get_hansel_and_gretel( string $_home_text = 'Home', string $_before_text = '', string $_delimiter = '&#x39;' ): string {
		// Set variables for later use.
		$here_text      = esc_html( $_before_text );
		$home_link      = home_url( '/' );
		$home_text      = esc_html( $_home_text );
		$link_before    = '<li property="itemListElement" typeof="ListItem" class="breadcrumb-list" style="display: inline; list-style: none;">';
		$link_after     = '</li>';
		$link_attr      = ' property="item" typeof="WebPage"';
		$link           = $link_before . '<a' . $link_attr . ' href="%1$s"><span property="name">%2$s<span></a><meta property="position" content="positionhere">' . $link_after;
		$delimiter      = $_delimiter;              // Delimiter between crumbs.
		$before         = '<li class="current">'; // Tag before the current crumb.
		$after          = '</li>';                // Tag after the current crumb.
		$page_addon     = '';                       // Adds the page number if the query is paged.
		$trail          = '';
		$category_links = '';
		$position       = 2;

		$delimiter = ' <li class="breadcrumb-item"><span class="separator et-pb-icon">' . $delimiter . '</span></li> ';

		/**
		 * Set our own $wp_the_query variable. Do not use the global variable version due to reliability.
		 */
		$wp_the_query   = $GLOBALS['wp_the_query'];
		$queried_object = $wp_the_query->get_queried_object();

		// Handle single post requests which includes single pages, posts and attatchments.
		if ( is_singular() ) {
			/**
			 * Set our own $post variable. Do not use the global variable version due to
			 * reliability. We will set $post_object variable to $GLOBALS['wp_the_query'].
			 *
			 * @var \WP_Post $post_object The post object.
			 */
			$post_object = sanitize_post( $queried_object );
			if ( ! $post_object instanceof \WP_Post ) {
				return '';
			}

			// Set variables.
			$title          = get_the_title( $post_object->ID );
			$parent         = $post_object->post_parent;
			$post_type      = $post_object->post_type;
			$post_id        = $post_object->ID;
			$post_link      = $before . $title . $after;
			$parent_string  = '';
			$post_type_link = '';

			if ( 'post' === $post_type ) {
				// Get the post-categories.
				$categories = \get_the_category( $post_id );
				if ( is_array( $categories ) && count( $categories ) > 0 ) {
					// Let's grab the first category.
					$category = $categories[0];
					if ( ! $category instanceof \WP_Term ) {
						return '';
					}

					$category_names = get_category_parents( $category->term_id );
					if ( is_wp_error( $category_names ) ) {
						return '';
					}

					$category_names_array = explode( '/', $category_names );

					$category_links = get_category_parents( $category->term_id, true, $delimiter );
					if ( is_wp_error( $category_links ) ) {
						return '';
					}

					$category_links = str_replace( array( '<a', '</a>' ), array( $link_before . '<a' . $link_attr, '</a>' . $link_after ), $category_links );
					foreach ( $category_names_array as $category_loop_name ) {
						if ( '' === $category_loop_name ) {
							continue;
						}
						$category_links = str_replace( $category_loop_name . '</a>', '<span property="name">' . $category_loop_name . '</span></a>', $category_links );
						$replaced_with  = '<span property="name">' . $category_loop_name . '</span></a><meta property="position" content="' . ( $position++ ) . '">';
						$category_links = str_replace( '<span property="name">' . $category_loop_name . '</span></a>', $replaced_with, $category_links );
					}
				}
			}

			if ( ! in_array( $post_type, array( 'post', 'page', 'attachment' ), true ) && post_type_exists( $post_type ) ) {
				$post_type_object = get_post_type_object( $post_type );
				if ( $post_type_object instanceof \WP_Post_Type ) {
					$archive_link = get_post_type_archive_link( $post_type );
					if ( false !== $archive_link ) {
						$post_type_link = sprintf( $link, esc_url( $archive_link ), $post_type_object->labels->singular_name );
						$post_type_link = str_replace( 'positionhere', (string) $position++, $post_type_link );
					}
				}
			}

			// Get post-parents if $parent !== 0.
			if ( 0 !== $parent ) {
				$parent_links = array();
				while ( $parent > 0 ) {
					$post_parent = get_post( $parent );
					if ( ! $post_parent instanceof \WP_Post ) {
						break;
					}

					$permalink = get_permalink( $post_parent->ID );
					if ( false === $permalink ) {
						break;
					}

					$temp_link = sprintf( $link, esc_url( $permalink ), get_the_title( $post_parent->ID ) );
					$temp_link = str_replace( 'positionhere', (string) $position++, $temp_link );

					$parent_links[] = $temp_link;

					$parent = $post_parent->post_parent;
				}

				$parent_links  = array_reverse( $parent_links );
				$parent_string = implode( $delimiter, $parent_links );
			}

			// Let's build the breadcrumb trail.
			if ( $parent_string ) {
				$trail = $parent_string . $delimiter . $post_link;
			} else {
				$trail = $post_link;
			}

			if ( $post_type_link ) {
				$trail = $post_type_link . $delimiter . $trail;
			}

			if ( $category_links ) {
				$trail = $category_links . $trail;
			}
		}

		// Handle archives that include category-, tag-, taxonomy-, date-, custom post type archives and author archives.
		if ( is_archive() ) {
			if ( is_category() || is_tag() || is_tax() ) {
				// Set the variables for this section.
				$term_object = get_term( $queried_object );
				if ( ! $term_object instanceof \WP_Term ) {
					return '';
				}

				$taxonomy        = $term_object->taxonomy;
				$term_id         = $term_object->term_id;
				$term_name       = $term_object->name;
				$term_parent     = $term_object->parent;
				$taxonomy_object = get_taxonomy( $taxonomy );
				if ( false === $taxonomy_object ) {
					return '';
				}

				// Categories: Tags: is set there.
				$current_term_link  = $before . $taxonomy_object->labels->singular_name . ': ' . $term_name . $after;
				$parent_term_string = '';

				if ( 0 !== $term_parent ) {
					// Get all the current term ancestors.
					$parent_term_links = array();
					while ( $term_parent > 0 ) {
						$term = get_term( $term_parent, $taxonomy );
						if ( ! $term instanceof \WP_Term ) {
							break;
						}

						$term_link = get_term_link( $term );
						if ( is_wp_error( $term_link ) ) {
							break;
						}

						$temp_link = sprintf( $link, $term_link, $term->name );
						$temp_link = str_replace( 'positionhere', (string) $position++, $temp_link );

						$parent_term_links[] = $temp_link;

						$term_parent = $term->parent;
					}

					$parent_term_links  = array_reverse( $parent_term_links );
					$parent_term_string = implode( $delimiter, $parent_term_links );
				}

				if ( '' !== $parent_term_string ) {
					$trail = $parent_term_string . $delimiter . $current_term_link;
				} else {
					$trail = $current_term_link;
				}
			} elseif ( is_author() ) {
				if ( ! $queried_object instanceof \WP_User ) {
					return '';
				}
				$trail = esc_html__( 'Author archive for ', 'squad-modules-for-divi' ) . $before . $queried_object->data->display_name . $after;
			} elseif ( is_date() ) {
				// Set default variables.
				$month_name = '';
				$year       = $wp_the_query->query_vars['year'] ?? '';
				$monthnum   = $wp_the_query->query_vars['monthnum'] ?? '';
				$day        = $wp_the_query->query_vars['day'] ?? '';

				// Get the month name if $monthnum has a value.
				if ( '' !== $monthnum ) {
					$date_time = DateTime::createFromFormat( '!m', $monthnum );
					if ( false !== $date_time ) {
						$month_name = $date_time->format( 'F' );
					}
				}

				if ( is_year() ) {
					$trail = $before . $year . $after;
				} elseif ( is_month() ) {
					$year_link = sprintf( $link, esc_url( get_year_link( $year ) ), $year );
					$year_link = str_replace( 'positionhere', (string) $position++, $year_link );

					$trail = $year_link . $delimiter . $before . $month_name . $after;
				} elseif ( is_day() ) {
					$year_link = sprintf( $link, esc_url( get_year_link( $year ) ), $year );
					$year_link = str_replace( 'positionhere', (string) $position++, $year_link );

					$month_link = sprintf( $link, esc_url( get_month_link( $year, $monthnum ) ), $month_name );
					$month_link = str_replace( 'positionhere', (string) $position++, $month_link );

					$trail = $year_link . $delimiter . $month_link . $delimiter . $before . $day . $after;
				}
			} elseif ( is_post_type_archive() ) {
				$post_type        = $wp_the_query->query_vars['post_type'];
				$post_type_object = get_post_type_object( $post_type );

				if ( $post_type_object instanceof \WP_Post_Type ) {
					$trail = $before . $post_type_object->labels->singular_name . $after;
				}
			}
		}

		// Handle the search page.
		if ( is_search() ) {
			$trail = esc_html__( 'Search query for: ', 'squad-modules-for-divi' ) . $before . get_search_query() . $after;
		}

		// Handle 404's.
		if ( is_404() ) {
			$trail = $before . esc_html__( 'Error 404', 'squad-modules-for-divi' ) . $after;
		}

		// Handle paged pages.
		if ( is_paged() ) {
			$current_page = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : get_query_var( 'page' );
			/* translators: 1. Page Title */
			$page_addon = $before . sprintf( esc_html__( ' ( Page %s )', 'squad-modules-for-divi' ), number_format_i18n( $current_page ) ) . $after;
		}

		$output_link = '';
		if ( is_home() || is_front_page() ) {
			// Do not show breadcrumbs on page one of home and frontpage.
			if ( is_paged() ) {
				$output_link .= '<span class="before">' . $here_text . '</span> ';
				$output_link .= '<ol vocab="https://schema.org/" typeof="BreadcrumbList" class="breadcrumb-list">';
				$output_link .= '<li property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" href="' . $home_link . '" class="home"><span property="name">' . $home_text . '</span><meta property="position" content="1"></a><meta property="position" content="1"></li>';
				$output_link .= $page_addon;
				$output_link .= '</ol>';
			}
		} else {
			$output_link .= '<span class="before">' . $here_text . '</span> ';
			$output_link .= '<ol vocab="https://schema.org/" typeof="BreadcrumbList" class="breadcrumb-list">';
			$output_link .= '<li property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" href="' . $home_link . '" class="home"><span property="name">' . $home_text . '</span></a><meta property="position" content="1"></li>';
			$output_link .= $delimiter;
			$output_link .= $trail;
			$output_link .= $page_addon;
			$output_link .= '</ol>';
		}

		return $output_link;
	}
}
