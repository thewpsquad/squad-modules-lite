<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Admin Notice Manager
 *
 * This class handles the registration and management of all WordPress admin notices
 * for the Divi Squad plugin. It provides a comprehensive system for registering,
 * filtering, and displaying notices throughout the WordPress admin.
 *
 * @since   3.3.3
 * @package DiviSquad\Core
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Core\Admin;

use DiviSquad\Core\Admin\Notices\Notice_Interface;
use DiviSquad\Core\Admin\Notices\Pro_Activation_Notice;
use DiviSquad\Core\Admin\Notices\Purchase_Discount_Notice;
use DiviSquad\Core\Admin\Notices\Review_Notice;
use Throwable;
use function add_action;
use function add_filter;
use function apply_filters;
use function do_action;
use function load_template;

/**
 * Admin Notice Manager class.
 *
 * @since   3.3.3
 * @package DiviSquad
 */
class Notice {

	/**
	 * Notice classes to be displayed.
	 *
	 * @var array<Notice_Interface>
	 */
	protected array $notice_instances = array();

	/**
	 * Initialize the Admin Notice Manager.
	 *
	 * This method sets up all necessary hooks for the notice system to function.
	 *
	 * @since 3.3.3
	 *
	 * @return void
	 */
	public function init(): void {
		try {
			add_action( 'wp_loaded', array( $this, 'setup_notice_classes' ) );
			add_action( 'admin_notices', array( $this, 'display_notices' ) );
			add_filter( 'admin_body_class', array( $this, 'add_body_classes' ) );
			add_filter( 'divi_squad_global_localize_data', array( $this, 'add_localize_data' ) );

			/**
			 * Action fired after notice manager is fully initialized.
			 *
			 * Use this hook to perform actions after the notice system is fully set up.
			 *
			 * @since 3.3.3
			 *
			 * @param Notice $manager The Admin Notice Manager instance.
			 */
			do_action( 'divi_squad_after_notice_init', $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Admin Notice Manager initialization' );
		}
	}

	/**
	 * Setup notice classes.
	 *
	 * This method initializes all registered notice classes and validates
	 * them before adding them to the display queue.
	 *
	 * @since 3.3.3
	 *
	 * @return void
	 */
	public function setup_notice_classes(): void {
		try {
			// Default notice classes.
			$default_notice_classes = array(
				Review_Notice::class,
				Purchase_Discount_Notice::class,
				Pro_Activation_Notice::class,
			);

			/**
			 * Filter to register notice classes.
			 *
			 * This filter allows developers to add, remove, or modify the classes
			 * that will be instantiated and potentially displayed as notices.
			 *
			 * @since 3.3.3
			 *
			 * @param array<string> $notice_classes Array of notice class names.
			 * @param Notice        $manager        The Admin Notice Manager instance.
			 */
			$notice_classes = apply_filters( 'divi_squad_notice_classes', $default_notice_classes, $this );

			/**
			 * Action before processing notice classes.
			 *
			 * Fires before individual notice classes are processed and validated.
			 *
			 * @since 3.3.3
			 *
			 * @param array<string> $notice_classes Array of notice class names to be processed.
			 * @param Notice        $manager        The Admin Notice Manager instance.
			 */
			do_action( 'divi_squad_before_process_notice_classes', $notice_classes, $this );

			foreach ( $notice_classes as $class_name ) {
				$implements = class_implements( $class_name );
				if ( ! is_array( $implements ) ) {
					continue;
				}

				// Skip if the class does not implement the Notice_Interface.
				if ( ! in_array( Notice_Interface::class, $implements, true ) ) {
					continue;
				}

				/**
				 * Filter to conditionally skip notice class instantiation.
				 *
				 * Allows developers to conditionally skip instantiating a notice class
				 * based on custom logic before the class is even instantiated.
				 *
				 * @since 3.3.3
				 *
				 * @param bool   $skip_notice Whether to skip the notice class. Default false.
				 * @param string $class_name  The notice class name.
				 * @param Notice $manager     The Admin Notice Manager instance.
				 */
				$skip_notice = apply_filters( 'divi_squad_skip_notice_class', false, $class_name, $this );
				if ( $skip_notice ) {
					continue;
				}

				/**
				 * Instantiate the notice class.
				 *
				 * @var Notice_Interface $notice_instance
				 */
				$notice_instance = new $class_name();

				// Skip notices that cannot be rendered.
				if ( ! $notice_instance->can_render_it() ) {
					/**
					 * Action when a notice is skipped due to can_render_it returning false.
					 *
					 * @since 3.3.3
					 *
					 * @param Notice_Interface $notice_instance The notice instance.
					 * @param string           $class_name      The notice class name.
					 * @param Notice           $manager         The Admin Notice Manager instance.
					 */
					do_action( 'divi_squad_notice_skipped', $notice_instance, $class_name, $this );
					continue;
				}

				/**
				 * Action when a notice is successfully validated.
				 *
				 * @since 3.3.3
				 *
				 * @param Notice_Interface $notice_instance The notice instance.
				 * @param string           $class_name      The notice class name.
				 * @param Notice           $manager         The Admin Notice Manager instance.
				 */
				do_action( 'divi_squad_notice_validated', $notice_instance, $class_name, $this );

				$this->notice_instances[] = $notice_instance;
			}

			/**
			 * Action after all notice classes are processed.
			 *
			 * Fires after all notice classes have been processed and validated.
			 *
			 * @since 3.3.3
			 *
			 * @param array<Notice_Interface> $notice_instances Array of validated notice instances.
			 * @param Notice                  $manager          The Admin Notice Manager instance.
			 */
			do_action( 'divi_squad_after_process_notice_classes', $this->notice_instances, $this );

			/**
			 * Filter to modify the final collection of notice instances.
			 *
			 * Allows developers to add, remove, or modify the final collection
			 * of notice instances that will be displayed.
			 *
			 * @since 3.3.3
			 *
			 * @param array<Notice_Interface> $notice_instances The validated notice instances.
			 * @param Notice                  $manager          The Admin Notice Manager instance.
			 */
			$this->notice_instances = apply_filters( 'divi_squad_notice_instances', $this->notice_instances, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to setup notice classes' );
		}
	}

	/**
	 * Display notices.
	 *
	 * This method renders all validated notice instances to the admin interface.
	 *
	 * @since 3.3.3
	 *
	 * @return void
	 */
	public function display_notices(): void {
		try {
			if ( count( $this->notice_instances ) === 0 ) {
				return;
			}

			$template_path = divi_squad()->get_template_path( 'admin/notices/banner.php' );

			if ( ! divi_squad()->get_wp_fs()->exists( $template_path ) ) {
				/**
				 * Action fired when the notice template is missing.
				 *
				 * @since 3.3.3
				 *
				 * @param string $template_path The missing template path.
				 * @param Notice $manager       The Admin Notice Manager instance.
				 */
				do_action( 'divi_squad_notice_template_missing', $template_path, $this );

				return;
			}

			/**
			 * Action before displaying notices.
			 *
			 * @since 3.3.3
			 *
			 * @param array<Notice_Interface> $notice_instances The notice instances to display.
			 * @param string                  $template_path    The template path used for rendering.
			 * @param Notice                  $manager          The Admin Notice Manager instance.
			 */
			do_action( 'divi_squad_before_display_notices', $this->notice_instances, $template_path, $this );

			foreach ( $this->notice_instances as $notice ) {
				/**
				 * Filter to modify notice template arguments before display.
				 *
				 * @since 3.3.3
				 *
				 * @param array<string, mixed> $template_args   The notice template arguments.
				 * @param Notice_Interface     $notice_instance The notice instance.
				 * @param Notice               $manager         The Admin Notice Manager instance.
				 */
				$template_args = apply_filters( 'divi_squad_notice_template_args', $notice->get_template_args(), $notice, $this );

				/**
				 * Action before displaying individual notice.
				 *
				 * @since 3.3.3
				 *
				 * @param Notice_Interface     $notice        The notice instance.
				 * @param array<string, mixed> $template_args The template arguments.
				 * @param string               $template_path The template path.
				 * @param Notice               $manager       The Admin Notice Manager instance.
				 */
				do_action( 'divi_squad_before_display_notice', $notice, $template_args, $template_path, $this );

				load_template( $template_path, false, $template_args );

				/**
				 * Action after displaying individual notice.
				 *
				 * @since 3.3.3
				 *
				 * @param Notice_Interface     $notice        The notice instance.
				 * @param array<string, mixed> $template_args The template arguments that were used.
				 * @param string               $template_path The template path that was used.
				 * @param Notice               $manager       The Admin Notice Manager instance.
				 */
				do_action( 'divi_squad_after_display_notice', $notice, $template_args, $template_path, $this );
			}

			/**
			 * Action after displaying all notices.
			 *
			 * @since 3.3.3
			 *
			 * @param array<Notice_Interface> $notice_instances The notice instances that were displayed.
			 * @param string                  $template_path    The template path that was used.
			 * @param Notice                  $manager          The Admin Notice Manager instance.
			 */
			do_action( 'divi_squad_after_display_notices', $this->notice_instances, $template_path, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to display notices' );
		}
	}

	/**
	 * Add body classes for notices.
	 *
	 * This method adds CSS classes to the admin body tag for proper styling of notices.
	 *
	 * @since 3.3.3
	 *
	 * @param string $classes Current body classes.
	 *
	 * @return string Modified body classes.
	 */
	public function add_body_classes( string $classes ): string {
		try {
			$classes = trim( $classes );

			// Add class if there are any notice instances.
			if ( count( $this->notice_instances ) > 0 ) {
				/**
				 * Filter for the base notice body class.
				 *
				 * @since 3.3.3
				 *
				 * @param string $base_class The base CSS class for notices.
				 * @param Notice $manager    The Admin Notice Manager instance.
				 */
				$base_class = apply_filters( 'divi_squad_notice_base_class', 'divi-squad-notice', $this );

				// Add base class if not empty.
				if ( '' !== $base_class ) {
					$classes .= ' ' . $base_class;
				}

				// Collect classes from all notice instances.
				$body_classes = array();
				foreach ( $this->notice_instances as $notice ) {
					$notice_classes = $notice->get_body_classes();
					if ( count( $notice_classes ) > 0 ) {
						foreach ( $notice_classes as $notice_class ) {
							$body_classes[] = $notice_class;
						}
					}

					// Add notice-specific ID class.
					$body_classes[] = 'divi-squad-notice-' . sanitize_html_class( $notice->get_notice_id() );
				}

				/**
				 * Filter for notice-specific body classes.
				 *
				 * @since 3.3.3
				 *
				 * @param array<string>           $body_classes     Array of notice-specific body classes.
				 * @param array<Notice_Interface> $notice_instances The notice instances.
				 * @param Notice                  $manager          The Admin Notice Manager instance.
				 */
				$body_classes = apply_filters( 'divi_squad_notice_body_classes_array', $body_classes, $this->notice_instances, $this );

				// Add the notice classes to the body classes.
				if ( count( $body_classes ) > 0 ) {
					$classes .= ' ' . implode( ' ', $body_classes );
				}
			}

			/**
			 * Filter the complete body classes string for notices.
			 *
			 * @since 3.3.3
			 *
			 * @param string                  $classes          Current body classes string.
			 * @param array<Notice_Interface> $notice_instances The notice instances.
			 * @param Notice                  $manager          The Admin Notice Manager instance.
			 */
			return apply_filters( 'divi_squad_admin_notice_body_classes', $classes, $this->notice_instances, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to add notice body classes' );

			return $classes;
		}
	}

	/**
	 * Add notice data to script localization.
	 *
	 * This method adds notice data to the JavaScript localization for client-side interaction.
	 *
	 * @since 3.3.3
	 *
	 * @param array<string, mixed> $data Current localization data.
	 *
	 * @return array<string, mixed> Modified localization data.
	 */
	public function add_localize_data( array $data ): array {
		try {
			$notice_data = array(
				'notices' => array(),
			);

			foreach ( $this->notice_instances as $instance ) {
				$notice_args = $instance->get_template_args();

				/**
				 * Filter to modify notice data for JavaScript localization.
				 *
				 * @since 3.3.3
				 *
				 * @param array<string, mixed> $notice_args The notice arguments.
				 * @param Notice_Interface     $notice      The notice instance.
				 * @param Notice               $manager     The Admin Notice Manager instance.
				 */
				$notice_args = apply_filters( 'divi_squad_notice_localize_instance_data', $notice_args, $instance, $this );

				$notice_data['notices'][] = $notice_args;
			}

			/**
			 * Filter for extra notice data properties for JavaScript.
			 *
			 * @since 3.3.3
			 *
			 * @param array<string, mixed>    $extra_data       Extra data to add to notice_data.
			 * @param array<Notice_Interface> $notice_instances The notice instances.
			 * @param Notice                  $manager          The Admin Notice Manager instance.
			 */
			$extra_data = (array) apply_filters( 'divi_squad_notice_localize_extra_data', array(), $this->notice_instances, $this );

			// Merge any extra data.
			if ( count( $extra_data ) > 0 ) {
				$notice_data = array_merge( $notice_data, $extra_data );
			}

			/**
			 * Filter the complete notice localization data.
			 *
			 * @since 3.3.3
			 *
			 * @param array<string, mixed>    $notice_data      The notice localization data.
			 * @param array<Notice_Interface> $notice_instances The notice instances.
			 * @param Notice                  $manager          The Admin Notice Manager instance.
			 */
			$notice_data = apply_filters( 'divi_squad_notice_localize_data', $notice_data, $this->notice_instances, $this );

			return array_merge( $data, $notice_data );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to add notice localization data' );

			return $data;
		}
	}

	/**
	 * Get all notice instances.
	 *
	 * Returns all validated notice instances that will be displayed.
	 *
	 * @since 3.3.3
	 *
	 * @return array<Notice_Interface> The notice instances.
	 */
	public function get_notice_instances(): array {
		return $this->notice_instances;
	}

	/**
	 * Add a notice instance directly.
	 *
	 * This method allows adding a pre-instantiated notice object directly
	 * to the collection of notices to be displayed.
	 *
	 * @since 3.3.3
	 *
	 * @param Notice_Interface $notice_instance The notice instance to add.
	 *
	 * @return bool True if the notice was added, false otherwise.
	 */
	public function add_notice_instance( Notice_Interface $notice_instance ): bool {
		try {
			// Skip notices that cannot be rendered.
			if ( ! $notice_instance->can_render_it() ) {
				return false;
			}

			$this->notice_instances[] = $notice_instance;

			/**
			 * Action when a notice instance is manually added.
			 *
			 * @since 3.3.3
			 *
			 * @param Notice_Interface $notice_instance The added notice instance.
			 * @param Notice           $manager         The Admin Notice Manager instance.
			 */
			do_action( 'divi_squad_notice_instance_added', $notice_instance, $this );

			return true;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to add notice instance' );

			return false;
		}
	}

	/**
	 * Remove a notice instance by its ID.
	 *
	 * This method allows removing a notice from the collection based on its ID.
	 *
	 * @since 3.3.3
	 *
	 * @param string $notice_id The ID of the notice to remove.
	 *
	 * @return bool True if a notice was removed, false otherwise.
	 */
	public function remove_notice_by_id( string $notice_id ): bool {
		try {
			$original_count = count( $this->notice_instances );

			$this->notice_instances = array_filter(
				$this->notice_instances,
				static function ( $notice ) use ( $notice_id ) {
					return $notice->get_notice_id() !== $notice_id;
				}
			);

			$removed = count( $this->notice_instances ) < $original_count;

			if ( $removed ) {
				/**
				 * Action when a notice is removed by ID.
				 *
				 * @since 3.3.3
				 *
				 * @param string $notice_id The ID of the removed notice.
				 * @param Notice $manager   The Admin Notice Manager instance.
				 */
				do_action( 'divi_squad_notice_removed_by_id', $notice_id, $this );
			}

			return $removed;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to remove notice by ID' );

			return false;
		}
	}

	/**
	 * Clear all notice instances.
	 *
	 * This method removes all notices from the collection.
	 *
	 * @since 3.3.3
	 *
	 * @return void
	 */
	public function clear_notices(): void {
		try {
			$old_instances          = $this->notice_instances;
			$this->notice_instances = array();

			/**
			 * Action when all notices are cleared.
			 *
			 * @since 3.3.3
			 *
			 * @param array<Notice_Interface> $old_instances The instances that were cleared.
			 * @param Notice                  $manager       The Admin Notice Manager instance.
			 */
			do_action( 'divi_squad_notices_cleared', $old_instances, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to clear notices' );
		}
	}
}
