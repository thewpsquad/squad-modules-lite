<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Admin Notice Manager
 *
 * This class handles the registration and management of all WordPress admin notices
 * for the Divi Squad plugin. It provides a comprehensive system for registering,
 * filtering, and displaying notices throughout the WordPress admin.
 *
 * @since   3.3.3
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Core\Admin;

use DiviSquad\Core\Admin\Notices\Notice_Interface;
use DiviSquad\Core\Admin\Notices\Pro_Activation_Notice;
use DiviSquad\Core\Admin\Notices\Purchase_Discount_Notice;
use DiviSquad\Core\Admin\Notices\Review_Notice;
use DiviSquad\Core\Assets as Assets_Manager;
use DiviSquad\Utils\Helper;
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
	 * Flag to determine if React notices are enabled.
	 *
	 * @since 3.3.3
	 * @var bool
	 */
	protected bool $use_react_notices = false;

	/**
	 * PHP template path for notices.
	 *
	 * @since 3.3.3
	 * @var string
	 */
	protected string $php_template_path = '';

	/**
	 * React template path for notices.
	 *
	 * @since 3.3.3
	 * @var string
	 */
	protected string $react_template_path = '';

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
			/**
			 * Action before initializing the notice manager.
			 *
			 * @since 3.3.3
			 *
			 * @param Notice $manager The Admin Notice Manager instance.
			 */
			do_action( 'divi_squad_before_notice_init', $this );

			// Set up template paths
			$this->php_template_path   = divi_squad()->get_template_path( 'admin/notices/banner-legacy.php' );
			$this->react_template_path = divi_squad()->get_template_path( 'admin/notices/banner-react.php' );

			add_filter( 'divi_squad_use_react_notices', '__return_true' );

			// Check if React notices are supported and enabled
			$this->check_react_notices_support();

			// Common setup regardless of notice rendering method
			add_action( 'wp_loaded', array( $this, 'setup_notice_classes' ) );
			add_filter( 'admin_body_class', array( $this, 'add_body_classes' ) );
			add_action( 'admin_notices', array( $this, 'display_notices' ) );

			add_action( 'divi_squad_after_register_admin_assets', array( $this, 'register_assets' ) );
			add_action( 'divi_squad_after_enqueue_admin_assets', array( $this, 'enqueue_assets' ) );
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
	 * Check if React notices are supported and enable/disable accordingly.
	 *
	 * @since 3.3.3
	 *
	 * @return void
	 */
	protected function check_react_notices_support(): void {
		try {
			/**
			 * Filters whether to use React-based notices instead of PHP-based notices.
			 *
			 * @since 3.3.3
			 *
			 * @param bool   $use_react Whether to use React-based notices. Default false.
			 * @param Notice $manager   The Admin Notice Manager instance.
			 */
			$use_react = apply_filters( 'divi_squad_use_react_notices', false, $this );

			// Check if required files exist
			if ( $use_react ) {
				$template_exists = divi_squad()->is_template_exists( 'admin/notices/banner-react.php' );
				$script_exists   = divi_squad()->is_asset_exists( 'admin/scripts/notices.js' );

				$use_react = $template_exists && $script_exists;

				if ( ! $use_react ) {
					// Log warning if React notices were requested but files are missing
					divi_squad()->log_error(
						new \Exception( 'React notices were requested but required files are missing' ),
						'React Notices Support Check',
						false
					);
				}
			}

			$this->use_react_notices = $use_react;

			/**
			 * Action fired after React notices support check.
			 *
			 * @since 3.4.0
			 *
			 * @param bool   $use_react Whether React notices will be used.
			 * @param Notice $manager   The Admin Notice Manager instance.
			 */
			do_action( 'divi_squad_after_react_notices_check', $this->use_react_notices, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to check React notices support' );
			$this->use_react_notices = false;
		}
	}

	/**
	 * Register admin notices assets
	 *
	 * @param Assets_Manager $assets Assets Manager instance.
	 *
	 * @return void
	 */
	public function register_assets( Assets_Manager $assets ): void {
		try {
			$assets->register_script(
				'admin-notices',
				array(
					'file' => 'notices',
					'path' => 'admin',
				)
			);

			$assets->register_style(
				'admin-notices',
				array(
					'file' => 'notices',
					'path' => 'admin',
				)
			);

			$assets->register_style(
				'admin-notices-legacy',
				array(
					'file' => 'notices-legacy',
					'path' => 'admin',
				)
			);

			/**
			 * Fires after admin notices assets are registered
			 *
			 * @param Assets_Manager $assets Assets Manager instance
			 */
			do_action( 'divi_squad_after_register_admin_notices_assets', $assets );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to register admin notices assets' );
		}
	}

	/**
	 * Enqueue admin notices assets
	 *
	 * @param Assets_Manager $assets Assets Manager instance.
	 *
	 * @return void
	 */
	public function enqueue_assets( Assets_Manager $assets ): void {
		try {
			if ( $this->use_react_notices ) {
				// For React notices, we need both the container and the scripts
				$assets->enqueue_script( 'admin-notices' );
				$assets->enqueue_style( 'admin-notices' );
			} else {
				// For PHP notices, we just need the display action
				$assets->enqueue_style( 'admin-notices-legacy' );
			}

			/**
			 * Fires after admin notices assets are enqueued
			 *
			 * @param Assets_Manager $assets Assets Manager instance
			 */
			do_action( 'divi_squad_after_enqueue_admin_notices_assets', $assets );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to enqueue admin notices assets' );
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
	 * This method determines which notice renderer to use and calls the appropriate method.
	 *
	 * @since 3.3.3
	 *
	 * @return void
	 */
	public function display_notices(): void {
		try {
			/**
			 * Action before checking for notices to display.
			 *
			 * @since 3.3.3
			 *
			 * @param Notice $manager The Admin Notice Manager instance.
			 */
			do_action( 'divi_squad_before_display_notices_check', $this );

			if ( count( $this->notice_instances ) === 0 ) {
				return;
			}

			// Choose the appropriate renderer based on configuration
			if ( $this->use_react_notices ) {
				$this->render_react_notices();
			} else {
				$this->render_legacy_notices();
			}

			/**
			 * Action after notices are displayed.
			 *
			 * @since 3.3.3
			 *
			 * @param bool   $use_react        Whether React notices were used.
			 * @param array  $notice_instances The notice instances.
			 * @param Notice $manager          The Admin Notice Manager instance.
			 */
			do_action( 'divi_squad_after_display_notices_complete', $this->use_react_notices, $this->notice_instances, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to display notices' );
		}
	}

	/**
	 * Render notices using the legacy template.
	 *
	 * This method renders notices using the traditional PHP approach.
	 *
	 * @since 3.3.3
	 *
	 * @return void
	 */
	protected function render_legacy_notices(): void {
		try {
			if ( ! divi_squad()->get_wp_fs()->exists( $this->php_template_path ) ) {
				/**
				 * Action fired when the PHP notice template is missing.
				 *
				 * @since 3.3.3
				 *
				 * @param string $template_path The missing template path.
				 * @param Notice $manager       The Admin Notice Manager instance.
				 */
				do_action( 'divi_squad_php_notice_template_missing', $this->php_template_path, $this );

				return;
			}

			/**
			 * Action before rendering PHP notices.
			 *
			 * @since 3.3.3
			 *
			 * @param array<Notice_Interface> $notice_instances The notice instances to display.
			 * @param string                  $template_path    The template path used for rendering.
			 * @param Notice                  $manager          The Admin Notice Manager instance.
			 */
			do_action( 'divi_squad_before_render_php_notices', $this->notice_instances, $this->php_template_path, $this );

			// Filter notices that can be rendered
			$renderable_notices = array_filter(
				$this->notice_instances,
				static function ( $notice ) {
					return $notice->can_render_it();
				}
			);

			foreach ( $renderable_notices as $notice ) {
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
				 * Action before rendering individual PHP notice.
				 *
				 * @since 3.3.3
				 *
				 * @param Notice_Interface     $notice        The notice instance.
				 * @param array<string, mixed> $template_args The template arguments.
				 * @param string               $template_path The template path.
				 * @param Notice               $manager       The Admin Notice Manager instance.
				 */
				do_action( 'divi_squad_before_render_php_notice', $notice, $template_args, $this->php_template_path, $this );

				load_template( $this->php_template_path, false, $template_args );

				/**
				 * Action after rendering individual PHP notice.
				 *
				 * @since 3.3.3
				 *
				 * @param Notice_Interface     $notice        The notice instance.
				 * @param array<string, mixed> $template_args The template arguments that were used.
				 * @param string               $template_path The template path that was used.
				 * @param Notice               $manager       The Admin Notice Manager instance.
				 */
				do_action( 'divi_squad_after_render_php_notice', $notice, $template_args, $this->php_template_path, $this );
			}

			/**
			 * Action after rendering all PHP notices.
			 *
			 * @since 3.3.3
			 *
			 * @param array<Notice_Interface> $notice_instances The notice instances that were displayed.
			 * @param string                  $template_path    The template path that was used.
			 * @param Notice                  $manager          The Admin Notice Manager instance.
			 */
			do_action( 'divi_squad_after_render_php_notices', $renderable_notices, $this->php_template_path, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to render PHP notices' );
		}
	}

	/**
	 * Render notices using the React container.
	 *
	 * This method renders the container element for React-rendered notices.
	 *
	 * @since 3.3.3
	 *
	 * @return void
	 */
	protected function render_react_notices(): void {
		try {
			if ( ! divi_squad()->get_wp_fs()->exists( $this->react_template_path ) ) {
				/**
				 * Action fired when the React container template is missing.
				 *
				 * @since 3.3.3
				 *
				 * @param string $template_path The missing template path.
				 * @param Notice $manager       The Admin Notice Manager instance.
				 */
				do_action( 'divi_squad_react_notice_template_missing', $this->react_template_path, $this );

				// Fallback to PHP notices if React template is missing
				$this->render_legacy_notices();

				return;
			}

			// Collect all notice scopes
			$all_scopes = array( 'global' );
			if( Helper::is_squad_page() ) {
				foreach ( $this->get_notice_instances() as $notice ) {
					$scopes     = $notice->get_scopes();
					$all_scopes = array_merge( $all_scopes, $scopes );
				}
				$all_scopes = array_unique( $all_scopes );
			}

			// Prepare container template arguments
			$container_args = array(
				'container_id'      => 'divi-squad-admin-notices',
				'container_classes' => 'divi-squad-react-notice-container',
				'notice_count'      => count( $this->get_notice_instances() ),
				'scopes'            => $all_scopes,
				'auto_slide'        => true,
				'slide_interval'    => 8000, // 8 seconds
				'loading_text'      => esc_html__( 'Loading notices...', 'squad-modules-for-divi' ),
				'fallback_notices'  => array(), // No fallback notices by default
			);

			/**
			 * Filter the React notice container arguments.
			 *
			 * @since 3.3.3
			 *
			 * @param array<string, mixed>    $container_args   The container arguments.
			 * @param array<Notice_Interface> $notice_instances The notice instances.
			 * @param Notice                  $manager          The Admin Notice Manager instance.
			 */
			$container_args = apply_filters( 'divi_squad_react_notice_container_args', $container_args, $this->get_notice_instances(), $this );

			/**
			 * Action before rendering the React notice container.
			 *
			 * @since 3.3.3
			 *
			 * @param array<string, mixed> $container_args The container arguments.
			 * @param string               $template_path  The template path.
			 * @param Notice               $manager        The Admin Notice Manager instance.
			 */
			do_action( 'divi_squad_before_render_react_container', $container_args, $this->react_template_path, $this );

			// Render the React container
			load_template( $this->react_template_path, true, $container_args );

			/**
			 * Action after rendering the React notice container.
			 *
			 * @since 3.3.3
			 *
			 * @param array<string, mixed> $container_args The container arguments that were used.
			 * @param string               $template_path  The template path that was used.
			 * @param Notice               $manager        The Admin Notice Manager instance.
			 */
			do_action( 'divi_squad_after_render_react_container', $container_args, $this->react_template_path, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to render React notice container' );

			// Fallback to PHP notices if an error occurs
			$this->render_legacy_notices();
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
			if ( count( $this->get_notice_instances() ) > 0 ) {
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
				foreach ( $this->get_notice_instances() as $notice ) {
					$notice_classes = $notice->get_body_classes();
					if ( count( $notice_classes ) > 0 ) {
						foreach ( $notice_classes as $notice_class ) {
							$body_classes[] = 'divi-squad-' . $notice_class;
						}
					}
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
			return apply_filters( 'divi_squad_admin_notice_body_classes', $classes, $this->get_notice_instances(), $this );
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
				'slides'          => array(),
				'useReactNotices' => $this->use_react_notices,
				'containerId'     => 'divi-squad-admin-notices',
				'slideInterval'   => 8000,
				'autoSlide'       => true,
			);

			foreach ( $this->get_notice_instances() as $instance ) {
				$notice_args = $this->resolve_icon_params( $instance );

				// Add the notice ID and scopes to the args
				$notice_args['notice_id']  = $instance->get_notice_id();
				$notice_args['scopes']     = $instance->get_scopes();
				$notice_args['can_render'] = $instance->can_render_it();

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

				$notice_data['slides'][] = $notice_args;
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
			$extra_data = (array) apply_filters( 'divi_squad_notice_localize_extra_data', array(), $this->get_notice_instances(), $this );

			// Merge any extra data.
			if ( count( $extra_data ) > 0 ) {
				$notice_data = array_merge_recursive( $notice_data, $extra_data );
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
			$notice_data = apply_filters( 'divi_squad_notice_localize_data', $notice_data, $this->get_notice_instances(), $this );

			// Update notices data to existing localize data store.
			$data['notices'] = $data['notices'] ?? array();
			$data['notices'] = array_merge_recursive( $data['notices'], $notice_data );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to add notice localization data' );
		} finally {
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
		/**
		 * Filter to get notice instances.
		 *
		 * @since 3.3.3
		 *
		 * @param array<Notice_Interface> $notice_instances All notice instances.
		 * @param Notice                  $manager          The Admin Notice Manager instance.
		 */
		return apply_filters( 'divi_squad_get_notice_instances', $this->notice_instances, $this );
	}

	/**
	 * Resolves SVG icon parameters for notice templates.
	 *
	 * This method enhances notice template arguments by processing SVG icons
	 * for both the notice logo and action buttons. It converts icon file references
	 * to actual SVG content when using React notices.
	 *
	 * @since  3.4.0
	 * @access public
	 *
	 * @param Notice_Interface $notice The notice instance to process.
	 *
	 * @return array<string, mixed> Enhanced template arguments with resolved SVG content.
	 */
	public function resolve_icon_params( Notice_Interface $notice ): array {
		try {
			// Get original template parameters from the notice
			$params = $notice->get_template_args();

			// Only process SVG icons if using React notices
			if ( ! $this->is_using_react_notices() ) {
				return $params;
			}

			/**
			 * Filter the notice parameters before resolving icons.
			 *
			 * @since 3.4.0
			 *
			 * @param array<string, mixed> $params  The original notice parameters.
			 * @param Notice_Interface     $notice  The notice instance.
			 * @param Notice               $manager The Notice manager instance.
			 */
			$params = (array) apply_filters( 'divi_squad_before_resolve_notice_icons', $params, $notice, $this );

			// Load the image loader service
			$divi_squad_image = divi_squad()->load_image( '/build/admin/images' );

			// Process the main logo if it exists
			if ( isset( $params['logo'] ) && '' !== $params['logo'] ) {
				$divi_squad_notice_logo = $divi_squad_image->get_image( $params['logo'], 'svg', false );
				if ( ! is_wp_error( $divi_squad_notice_logo ) ) {
					$params['logo'] = $divi_squad_notice_logo;
				}
			}

			// Process icons in action buttons if they exist
			if ( isset( $params['action_buttons'] ) && is_array( $params['action_buttons'] ) ) {
				foreach ( $params['action_buttons'] as $position => $buttons ) {
					if ( ! is_array( $buttons ) || count( $buttons ) === 0 ) {
						continue;
					}

					// Create a new array for processed buttons at this position
					$processed_buttons = array();

					foreach ( $buttons as $button ) {
						// Process the button's icon if it exists
						if ( isset( $button['icon_svg'] ) && '' !== $button['icon_svg'] ) {
							$button_icon = $divi_squad_image->get_image( $button['icon_svg'], 'svg', false );
							if ( ! is_wp_error( $button_icon ) ) {
								$button['icon_svg'] = $button_icon;
							} else {
								$button['icon_svg'] = '';
							}
						}

						$processed_buttons[] = $button;
					}

					// Replace the original buttons with processed ones
					$params['action_buttons'][ $position ] = $processed_buttons;
				}
			}

			/**
			 * Filter the notice parameters after resolving icons.
			 *
			 * @since 3.3.3
			 *
			 * @param array<string, mixed> $params  The processed notice parameters.
			 * @param Notice_Interface     $notice  The notice instance.
			 * @param Notice               $manager The Notice manager instance.
			 */
			return apply_filters( 'divi_squad_after_resolve_notice_icons', $params, $notice, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to resolve notice icon parameters' );

			return $notice->get_template_args(); // Return original params on error
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
			/**
			 * Filter to determine if a notice can be removed by ID.
			 *
			 * @since 3.3.3
			 *
			 * @param bool   $can_remove Whether the notice can be removed. Default true.
			 * @param string $notice_id  The ID of the notice to remove.
			 * @param Notice $manager    The Admin Notice Manager instance.
			 */
			$can_remove = apply_filters( 'divi_squad_can_remove_notice_by_id', true, $notice_id, $this );

			if ( ! $can_remove ) {
				return false;
			}

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
	 * Check if React notices are enabled.
	 *
	 * @since 3.3.3
	 *
	 * @return bool Whether React notices are enabled.
	 */
	public function is_using_react_notices(): bool {
		/**
		 * Filter to determine if React notices are being used.
		 *
		 * @since 3.3.3
		 *
		 * @param bool   $use_react The current React notices setting.
		 * @param Notice $manager   The Admin Notice Manager instance.
		 */
		return apply_filters( 'divi_squad_is_using_react_notices', $this->use_react_notices, $this );
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
			/**
			 * Filter to determine if notices can be cleared.
			 *
			 * @since 3.3.3
			 *
			 * @param bool   $can_clear Whether notices can be cleared. Default true.
			 * @param Notice $manager   The Admin Notice Manager instance.
			 */
			$can_clear = apply_filters( 'divi_squad_can_clear_notices', true, $this );

			if ( ! $can_clear ) {
				return;
			}

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
