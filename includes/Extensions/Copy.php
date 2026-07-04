<?php // phpcs:ignore WordPress.Files.FileName

/**
 * The Post Duplicator extension class for Divi Squad.
 *
 * @since   1.4.8
 * @author  The WP Squad <support@squadmodules.com>
 * @package DiviSquad
 */

namespace DiviSquad\Extensions;

use DiviSquad\Core\Assets;
use DiviSquad\Utils\Helper;
use WP_Post;
use WP_User;
use function add_action;
use function add_filter;
use function current_user_can;
use function esc_attr;
use function esc_attr__;
use function esc_html__;
use function get_current_blog_id;
use function get_object_taxonomies;
use function get_post;
use function is_multisite;
use function load_template;
use function sanitize_post;
use function switch_to_blog;
use function wp_get_object_terms;
use function wp_insert_post;
use function wp_set_object_terms;

/**
 * The Post Duplicator class.
 *
 * @since   1.4.8
 * @package DiviSquad
 */
class Copy extends Extension {

	/**
	 * Get the extension name.
	 *
	 * @return string
	 */
	protected function get_name(): string {
		return 'Post_Duplicator';
	}

	/**
	 * Load the extension.
	 *
	 * @return void
	 */
	protected function load(): void {
		/**
		 * Fires before the extension is loaded.
		 *
		 * @since 3.0.0
		 *
		 * @param Copy $instance The Copy extension object.
		 */
		do_action( 'divi_squad_ext_copy_before_loaded', $this );

		// Add CSS body class name for the available post or page.
		add_filter( 'divi_squad_body_classes', array( $this, 'admin_body_class' ) );

		// Register and enqueue admin assets.
		add_action( 'divi_squad_register_admin_assets', array( $this, 'register' ) );
		add_action( 'divi_squad_enqueue_admin_assets', array( $this, 'enqueue' ) );

		// Load the template at footer.
		add_action( 'admin_footer', array( $this, 'admin_footer_template' ) );

		// Add a duplicate link in the array of row action links on the Posts list table.
		foreach ( $this->get_allowed_list_table_for_row_actions() as $list_table ) {
			if ( 'cuar/core/admin/content-list-table' === $list_table ) {
				add_filter( "$list_table/row-actions", array( $this, 'row_actions' ), 10, 2 );
			} else {
				add_filter( "{$list_table}_row_actions", array( $this, 'row_actions' ), 10, 2 );
			}
		}

		// Add duplicate action in the bulk actions menu of the list table.
		foreach ( $this->get_allowed_post_types_for_bulk_actions() as $post_type ) {
			if ( in_array( $post_type, array( 'cuar_private_file', 'cuar_private_page' ), true ) ) {
				add_filter( "cuar/core/admin/container-list-table/bulk-actions?post_type=$post_type", array( $this, 'bulk_actions' ) );
			} else {
				add_filter( "bulk_actions-edit-$post_type", array( $this, 'bulk_actions' ) );
			}
		}

		/**
		 * Fires after the extension is loaded.
		 *
		 * @since 3.0.0
		 *
		 * @param Copy $instance The Copy extension object.
		 */
		do_action( 'divi_squad_ext_copy_loaded', $this );
	}

	/**
	 * Add CSS body class name for the available post or page.
	 *
	 * @param array<string> $classes An array of body class names.
	 *
	 * @return array<string>
	 */
	public function admin_body_class( array $classes ): array {
		if ( ! $this->is_allowed_admin_screen() ) {
			return $classes;
		}

		// Update body classes.
		$classes[] = 'ext-copy-this';

		return $classes;
	}

	/**
	 * Register the extension assets.
	 *
	 * @since 3.3.0
	 *
	 * @param Assets $assets The assets manager.
	 *
	 * @return void
	 */
	public function register( Assets $assets ): void {
		if ( ! $this->is_allowed_admin_screen() || ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		// Register vendor assets.
		$assets->register_script(
			'vendor-tooltipster',
			array(
				'file' => 'tooltipster.bundle',
				'path' => 'vendor',
				'deps' => array( 'jquery' ),
			)
		);
		$assets->register_style(
			'vendor-tooltipster',
			array(
				'file' => 'tooltipster.bundle',
				'path' => 'vendor',
				'ext'  => 'css',
			)
		);
		$assets->register_script(
			'vendor-toast',
			array(
				'file' => 'jquery.toast',
				'path' => 'vendor',
				'deps' => array( 'jquery' ),
			)
		);
		$assets->register_style(
			'vendor-toast',
			array(
				'file' => 'jquery.toast',
				'path' => 'vendor',
				'ext'  => 'css',
			)
		);

		// Register extension assets.
		$assets->register_script(
			'ext-copy-any-post-types',
			array(
				'file' => 'copy-any-post-types',
				'path' => 'extensions',
				'deps' => array( 'lodash', 'jquery', 'wp-api-fetch', 'squad-vendor-tooltipster', 'squad-vendor-toast' ),
			)
		);
		$assets->register_style(
			'ext-copy-any-post-types',
			array(
				'file' => 'copy-any-post-types',
				'path' => 'extensions',
				'ext'  => 'css',
				'deps' => array( 'squad-vendor-tooltipster', 'squad-vendor-toast' ),
			)
		);
		$assets->register_script(
			'ext-copy-any-post-types-bulk',
			array(
				'file' => 'copy-any-post-types-bulk',
				'path' => 'extensions',
				'deps' => array( 'lodash', 'jquery', 'wp-api-fetch', 'squad-vendor-toast' ),
			)
		);
		$assets->register_style(
			'ext-copy-any-post-types-bulk',
			array(
				'file' => 'copy-any-post-types-bulk',
				'path' => 'extensions',
				'ext'  => 'css',
				'deps' => array( 'squad-vendor-toast' ),
			)
		);
	}

	/**
	 * Enqueue the extension assets.
	 *
	 * @since 3.3.0
	 *
	 * @param Assets $assets The assets manager.
	 *
	 * @return void
	 */
	public function enqueue( Assets $assets ): void {
		if ( ! $this->is_allowed_admin_screen() || ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		// Enqueue vendor assets.
		$assets->enqueue_script( 'vendor-tooltipster' );
		$assets->enqueue_style( 'vendor-tooltipster' );
		$assets->enqueue_script( 'vendor-toast' );
		$assets->enqueue_style( 'vendor-toast' );

		// Enqueue extension assets.
		$assets->enqueue_script( 'ext-copy-any-post-types' );
		$assets->enqueue_style( 'ext-copy-any-post-types' );
		$assets->enqueue_script( 'ext-copy-any-post-types-bulk' );
		$assets->enqueue_style( 'ext-copy-any-post-types-bulk' );
	}

	/**
	 * Enqueuing scripts for all admin pages.
	 *
	 * @return void
	 */
	public function admin_footer_template(): void {
		if ( ! $this->is_allowed_admin_screen() ) {
			return;
		}

		// The template args.
		$template_args = array(
			'current_site'  => get_current_blog_id(),
			'site_is_multi' => is_multisite() !== true ? ' disabled' : '',
		);

		// Get all sites in multisite.
		if ( function_exists( '\get_sites' ) ) {
			$sites = \get_sites();
			foreach ( $sites as $site ) {
				$template_args['blog_sites'][ $site->blog_id ] = $site->blogname;
			}
		}

		// The template path.
		$template_path = sprintf( '%1$s/extensions/copy.php', divi_squad()->get_template_path() );
		if ( current_user_can( 'edit_posts' ) && divi_squad()->get_wp_fs()->exists( $template_path ) ) {
			load_template( $template_path, true, $template_args );
		}
	}

	/**
	 * Verify allowed screen.
	 *
	 * @return bool
	 */
	public function is_allowed_admin_screen(): bool {
		/**
		 * Filters the allowed screen for the extension.
		 *
		 * @since 3.0.0
		 *
		 * @param string[] $allowed_screen The allowed screen.
		 *
		 * @return string[]
		 */
		$allowed_screen = \apply_filters( 'divi_squad_ext_copy_allowed_screen', array( 'post', 'edit-post', 'page', 'edit-page' ) );

		return Helper::is_screen_allowed( $allowed_screen );
	}

	/**
	 * Add the duplicate link to post, page, and custom actions
	 *
	 * @param array<string, string> $actions An array of row action links. Defaults are 'Edit', 'Quick Edit', 'Restore', 'Trash', 'Delete Permanently', 'Preview', and 'View'.
	 * @param WP_Post               $post    The post-object.
	 *
	 * @return array<string, string> An array of row action links.
	 */
	public function row_actions( array $actions, WP_Post $post ): array {
		if ( 'trash' !== $post->post_status && current_user_can( 'edit_posts' ) ) {
			$actions['copy_this'] = sprintf(
				'<a class="copy-this-post-link" href="#" title="%1$s" data-id="%3$s" rel="permalink">%2$s</a>',
				esc_attr__( 'Copy this', 'squad-modules-for-divi' ),
				esc_html__( 'Copy', 'squad-modules-for-divi' ),
				esc_attr( (string) $post->ID )
			);
		}

		return $actions;
	}

	/**
	 * Add duplicate action in the bulk actions menu of the list table.
	 *
	 * @param array<string, string> $actions An array of the available bulk actions.
	 *
	 * @return array<string, string> An array of bulk actions.
	 */
	public function bulk_actions( array $actions ): array {
		if ( current_user_can( 'edit_posts' ) ) {
			$actions['copy_selected'] = esc_html__( 'Copy ', 'squad-modules-for-divi' );
		}

		return $actions;
	}

	/**
	 * Get the allowed list table for row actions.
	 *
	 * @return string[]
	 */
	public function get_allowed_list_table_for_row_actions(): array {
		/**
		 * Filters the allowed post types for row actions.
		 *
		 * @since 3.0.0
		 *
		 * @param string[] $post_types The allowed post types.
		 *
		 * @return string[]
		 */
		return apply_filters( 'divi_squad_ext_copy_allowed_post_types_for_row_actions', array( 'post', 'page', 'cuar/core/admin/content-list-table' ) );
	}

	/**
	 * Get the allowed post types.
	 *
	 * @return string[]
	 */
	public function get_allowed_post_types_for_bulk_actions(): array {
		$defaults = array( 'post', 'page', 'project', 'et_pb_layout', 'cuar_private_file', 'cuar_private_page' );

		/**
		 * Filters the allowed post types for bulk actions.
		 *
		 * @since 3.0.0
		 *
		 * @param string[] $post_types The allowed post types.
		 *
		 * @return string[]
		 */
		return apply_filters( 'divi_squad_ext_copy_allowed_post_types_for_bulk_actions', $defaults );
	}

	/**
	 * Duplicate the post.
	 *
	 * @param array<string, mixed> $options The post duplication options.
	 *
	 * @return void
	 * @throws \RuntimeException When the post duplication failed.
	 */
	public static function duplicate_the_post( array $options ): void {
		// Get access to the database.
		global $wpdb, $current_user, $blog_id;

		// Collect current post.
		$post_ids   = isset( $options['post_ids'] ) ? array_map( 'absint', (array) $options['post_ids'] ) : array();
		$post_count = isset( $options['posts_count'] ) ? absint( sanitize_text_field( $options['posts_count'] ) ) : 1;
		$site_id    = isset( $options['site_id'] ) ? absint( sanitize_text_field( $options['site_id'] ) ) : 1;

		// Check the requested post ids is empty or not.
		if ( count( $post_ids ) === 0 ) {
			throw new \RuntimeException( esc_html__( 'Kindly choose a minimum of one row for copying.', 'squad-modules-for-divi' ) );
		}

		// Check if the user is not super admin in multisite.
		if ( ! $current_user instanceof WP_User ) {
			throw new \RuntimeException( esc_html__( 'User not found.', 'squad-modules-for-divi' ) );
		}

		// Check if the user is not super admin in multisite.
		if ( is_multisite() && ! is_super_admin( $current_user->ID ) ) {
			throw new \RuntimeException( esc_html__( 'You do not have permission to access this endpoint.', 'squad-modules-for-divi' ) );
		}

		$is_copy_done = false;

		/**
		 * Loop through all the selected posts and duplicate them.
		 *
		 * @param array<int> $post_ids The post IDs.
		 * @param int        $post_id  The post ID.
		 * @param int        $interval The post duplication interval.
		 */
		foreach ( $post_ids as $post_id ) {
			$post_object = get_post( absint( $post_id ) );
			if ( ! $post_object instanceof WP_Post ) {
				continue;
			}

			$post = sanitize_post( $post_object, 'db' );
			if ( $post instanceof WP_Post ) {

				// Switch to target blog site when multisite available.
				if ( $site_id !== $blog_id && is_multisite() ) {
					switch_to_blog( $site_id );
				}

				for ( $interval = 1; $interval <= $post_count; $interval++ ) {
					$args = array(
						'post_status'    => 'draft',
						'comment_status' => $post->comment_status,
						'ping_status'    => $post->ping_status,
						'post_author'    => $current_user->ID,
						'post_content'   => $post->post_content,
						'post_excerpt'   => $post->post_excerpt,
						'post_name'      => $post->post_name,
						'post_parent'    => $post->post_parent,
						'post_password'  => $post->post_password,
						'post_title'     => 1 === $post_count ? $post->post_title : "$post->post_title #$interval",
						'post_type'      => $post->post_type,
						'to_ping'        => $post->to_ping,
						'menu_order'     => $post->menu_order,
					);

					$new_post_id = wp_insert_post( $args );

					// get all current post-terms ad set them to the new post-draft.
					$taxonomies = get_object_taxonomies( $post->post_type );
					foreach ( $taxonomies as $taxonomy ) {
						$post_terms = wp_get_object_terms( array( absint( $post_id ) ), $taxonomy, array( 'fields' => 'slugs' ) );
						if ( is_wp_error( $post_terms ) ) {
							continue;
						}

						// set the post terms.
						wp_set_object_terms( $new_post_id, $post_terms, $taxonomy );
					}

					// Duplicate all post meta just in two SQL queries.
					$meta_data = get_post_meta( absint( $post_id ) );
					if ( 0 !== count( $meta_data ) ) {
						$query  = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) VALUES ";
						$values = array();

						foreach ( $meta_data as $meta_key => $meta_value ) {
							// Do not duplicate the following post meta.
							$excluded_defaults = array( '_wp_old_slug', '_wp_old_date', '_edit_lock', '_edit_last', '_wp_trash_meta_status', '_wp_trash_meta_time', 'fakerpress_flag' );

							/**
							 * Filters the excluded meta keys for post duplication.
							 *
							 * @since 3.0.0
							 *
							 * @param string[] $excluded_meta_keys The excluded meta keys.
							 *
							 * @return string[]
							 */
							$excluded_meta_keys = apply_filters( 'divi_squad_ext_copy_excluded_meta_keys', $excluded_defaults );
							if ( in_array( $meta_key, $excluded_meta_keys, true ) ) {
								continue;
							}

							$values[] = $wpdb->prepare( '(%d, %s, %s)', $new_post_id, $meta_key, $meta_value[0] );
						}

						// Join all values.
						$query .= implode( ', ', $values );

						// Insert the post meta.
						$wpdb->query( $query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
					}
				}

				// Rollback to current blog site when multisite available.
				if ( $site_id !== $blog_id && is_multisite() ) {
					switch_to_blog( absint( $blog_id ) );
				}

				$is_copy_done = true;
			} else {
				$is_copy_done = false;
				break;
			}
		}

		if ( ! $is_copy_done ) {
			throw new \RuntimeException( esc_html__( 'Post(s) duplication failed.', 'squad-modules-for-divi' ) );
		}
	}
}
