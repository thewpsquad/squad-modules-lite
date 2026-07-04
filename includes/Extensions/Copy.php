<?php // phpcs:ignore WordPress.Files.FileName

/**
 * The Post Duplicator extension class for Divi Squad.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   1.4.8
 */

namespace DiviSquad\Extensions;

use DiviSquad\Base\Extension;
use DiviSquad\Utils\Asset;
use DiviSquad\Utils\WP;
use WP_Post;
use WP_User;
use function add_action;
use function add_filter;
use function current_user_can;
use function esc_attr;
use function esc_attr__;
use function esc_html__;
use function get_current_blog_id;
use function get_current_screen;
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
 * @package DiviSquad
 * @since   1.4.8
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
		add_filter( 'admin_body_class', array( $this, 'admin_body_class' ) );

		// Enqueuing scripts.
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

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
	 * @param string $classes An array of body class names.
	 *
	 * @return string
	 */
	public function admin_body_class( string $classes ): string {
		if ( ! $this->is_allowed_admin_screen() ) {
			return $classes;
		}

		// Update body classes.
		$classes .= ' divi-squad-ext-copy-this';

		return $classes;
	}

	/**
	 * Enqueuing scripts for all admin pages.
	 *
	 * @return void
	 */
	public function admin_enqueue_scripts(): void {
		if ( ! $this->is_allowed_admin_screen() ) {
			return;
		}

		if ( current_user_can( 'edit_posts' ) ) {
			Asset::register_script( 'vendor-tooltipster', Asset::vendor_asset_path( 'tooltipster.bundle' ) );
			Asset::register_style( 'vendor-tooltipster', Asset::vendor_asset_path( 'tooltipster.bundle', array( 'ext' => 'css' ) ) );
			Asset::register_script( 'vendor-toast', Asset::vendor_asset_path( 'jquery.toast' ), array( 'jquery' ) );
			Asset::register_style( 'vendor-toast', Asset::vendor_asset_path( 'jquery.toast', array( 'ext' => 'css' ) ) );

			Asset::enqueue_script( 'ext-copy', Asset::extension_asset_path( 'copy' ), array( 'lodash', 'jquery', 'wp-api-fetch', 'squad-vendor-tooltipster', 'squad-vendor-toast' ) );
			Asset::enqueue_style( 'ext-copy', Asset::extension_asset_path( 'copy', array( 'ext' => 'css' ) ), array( 'squad-vendor-tooltipster', 'squad-vendor-toast' ) );
			Asset::enqueue_script( 'ext-copy-bulk', Asset::extension_asset_path( 'copy-bulk' ), array( 'lodash', 'jquery', 'wp-api-fetch', 'squad-vendor-toast' ) );
			Asset::enqueue_style( 'ext-copy-bulk', Asset::extension_asset_path( 'copy-bulk', array( 'ext' => 'css' ) ), array( 'squad-vendor-toast' ) );

			// Load localize data.
			add_filter( 'divi_squad_assets_backend_extra_data', array( $this, 'wp_localize_script_data' ) );

			// Load script translations.
			WP::set_script_translations( 'squad-ext-copy', divi_squad()->get_name() );
		}
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
		if ( current_user_can( 'edit_posts' ) && file_exists( $template_path ) ) {
			load_template( $template_path, true, $template_args );
		}
	}

	/**
	 * Verify allowed screen.
	 *
	 * @return bool
	 */
	public function is_allowed_admin_screen(): bool {
		// Get the current screen.
		$screen = get_current_screen();

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

		return $screen instanceof \WP_Screen && in_array( $screen->id, $allowed_screen, true );
	}

	/**
	 * Set localize data for admin area.
	 *
	 * @param array $exists_data Exists extra data.
	 *
	 * @return array
	 */
	public function wp_localize_script_data( array $exists_data ): array {
		$screen = get_current_screen();
		// Check if the current screen is not a WP_Screen object.
		if ( ! $screen instanceof \WP_Screen ) {
			return $exists_data;
		}

		// Localize data.
		$admin_localize = array(
			'l10n' => array(
				'copy'             => esc_html__( 'Copy', 'squad-modules-for-divi' ),
				'copy_toast_title' => esc_html__( 'Squad Copy Extension', 'squad-modules-for-divi' ),
				'copy_empty_post'  => esc_html__( 'Kindly choose a minimum of one row for copying.', 'squad-modules-for-divi' ),
				'unknown_er'       => esc_html__( 'Something went wrong!', 'squad-modules-for-divi' ),
			),
		);

		return array_merge( $exists_data, $admin_localize );
	}

	/**
	 * Add the duplicate link to post, page, and custom actions
	 *
	 * @param string[] $actions An array of row action links. Defaults are 'Edit', 'Quick Edit', 'Restore', 'Trash', 'Delete Permanently', 'Preview', and 'View'.
	 * @param WP_Post  $post    The post-object.
	 *
	 * @return string[] An array of row action links.
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
	 * @param array $actions An array of the available bulk actions.
	 *
	 * @return string[] An array of bulk actions.
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
	 * @param array $options The post duplication options.
	 *
	 * @return void
	 * @throws \RuntimeException When the post duplication failed.
	 */
	public static function duplicate_the_post( array $options ) {
		// Get access to the database.
		global $wpdb, $current_user, $blog_id;

		// Collect current post.
		$post_ids   = isset( $options['post_ids'] ) ? array_map( 'absint', (array) $options['post_ids'] ) : array();
		$post_count = isset( $options['posts_count'] ) ? absint( sanitize_text_field( $options['posts_count'] ) ) : 1;
		$site_id    = isset( $options['site_id'] ) ? absint( sanitize_text_field( $options['site_id'] ) ) : 1;

		// Check the requested post ids is empty or not.
		if ( empty( $post_ids ) ) {
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

		if ( 0 !== count( $post_ids ) ) {
			$is_copy_done = false;

			/**
			 * Loop through all the selected posts and duplicate them.
			 *
			 * @param int $post_id The post ID.
			 * @param int $interval The post duplication interval.
			 */
			foreach ( $post_ids as $post_id ) {
				$post = sanitize_post( get_post( absint( $post_id ) ), 'db' );
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
		} else {
			throw new \RuntimeException( esc_html__( 'Kindly choose a minimum of one post for copying.', 'squad-modules-for-divi' ) );
		}
	}
}
