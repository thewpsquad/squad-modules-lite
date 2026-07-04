<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Admin page class for requirements.
 *
 * Handles the admin UI for displaying requirements status.
 *
 * @since   3.5.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Core\Requirements;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use DiviSquad\Core\Contracts\Hookable;
use DiviSquad\Core\Supports\Polyfills\Constant;
use DiviSquad\Utils\Divi;
use DiviSquad\Utils\Helper;
use Throwable;

/**
 * Class Admin_Page
 *
 * Manages the admin page for requirements.
 *
 * @since   3.5.0
 * @package DiviSquad
 */
class Admin_Page implements Hookable {
	/**
	 * Status checker instance.
	 *
	 * @since 3.5.0
	 *
	 * @var Status_Checker
	 */
	private Status_Checker $status_checker;

	/**
	 * Constructor.
	 *
	 * @since 3.5.0
	 *
	 * @param Status_Checker $status_checker Status checker instance.
	 */
	public function __construct( Status_Checker $status_checker ) {
		$this->status_checker = $status_checker;
	}

	/**
	 * Register hooks and filters for the admin page.
	 *
	 * @since  3.5.0
	 * @access public
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		// Register action hooks for the requirements template..
		add_action( 'divi_squad_after_minimum_requirements', array( $this, 'add_extended_requirements_info' ) );
		add_action( 'divi_squad_debug_info_items', array( $this, 'add_debug_info_items' ) );
		add_action( 'divi_squad_after_standard_sections', array( $this, 'add_custom_sections' ), 10, 3 );

		// Register filters for requirements template..
		add_filter( 'divi_squad_required_divi_version', array( $this, 'filter_divi_required_version' ) );
		add_filter( 'divi_squad_plugin_life_type', array( $this, 'filter_plugin_life_type' ) );
		add_filter( 'divi_squad_render_status_badge', array( $this, 'filter_render_status_badge' ), 10, 2 );
		add_filter( 'divi_squad_minimum_requirements', array( $this, 'filter_minimum_requirements' ), 10, 2 );
		add_filter( 'divi_squad_requirement_rows_config', array( $this, 'filter_requirement_rows_config' ), 10, 3 );
	}

	/**
	 * Register the admin page.
	 *
	 * @since  3.2.0
	 * @access public
	 *
	 * @return void
	 */
	public function register_pre_loaded_admin_page(): void {
		add_action( 'admin_menu', array( $this, 'register_admin_page' ) );
		add_action( 'admin_head', array( $this, 'clean_admin_content_section' ), Constant::PHP_INT_MAX );
		add_action( 'divi_squad_menu_badges', array( $this, 'add_badges' ) );
	}

	/**
	 * Register the admin page.
	 *
	 * @since  3.2.0
	 * @access public
	 *
	 * @return void
	 */
	public function register_admin_page(): void {
		// Load the image class..
		$image = divi_squad()->load_image( '/build/admin/images/logos' );

		// Get the menu icon..
		$menu_icon = $image->get_image( 'divi-squad-d-menu.svg', 'svg' );
		if ( is_wp_error( $menu_icon ) ) {
			$menu_icon = 'dashicons-warning';
		}

		$page_slug  = divi_squad()->get_admin_menu_slug();
		$capability = 'manage_options';

		// Register the admin page..
		add_menu_page(
			__( 'Divi Squad', 'squad-modules-for-divi' ),
			__( 'Divi Squad', 'squad-modules-for-divi' ),
			$capability,
			$page_slug,
			'',
			$menu_icon,
			divi_squad()->get_admin_menu_position()
		);

		// Register the admin page..
		add_submenu_page(
			$page_slug,
			__( 'Requirements', 'squad-modules-for-divi' ),
			__( 'Requirements', 'squad-modules-for-divi' ),
			$capability,
			$page_slug,
			array( $this, 'render_admin_page' ),
		);
	}

	/**
	 * Remove all notices from the squad template pages.
	 *
	 * @since  3.2.0
	 * @access public
	 *
	 * @return void
	 */
	public function clean_admin_content_section(): void {
		// Check if the current screen is available..
		if ( Helper::is_squad_page() ) {
			remove_all_actions( 'admin_notices' );
			remove_all_actions( 'network_admin_notices' );
			remove_all_actions( 'all_admin_notices' );
			remove_all_actions( 'user_admin_notices' );
		}
	}

	/**
	 * Render the admin page.
	 *
	 * @since  3.2.0
	 * @access public
	 *
	 * @return void
	 */
	public function render_admin_page(): void {
		try {
			$status       = $this->status_checker->get_status();
			$is_fulfilled = $this->status_checker->is_fulfilled();

			// Get the required Divi version.
			$required_version = $this->status_checker->get_required_version();

			// Prepare content for the notice container.
			$content = $this->get_notice_content();

			// Add unified status information.
			$status['is_fulfilled']          = $is_fulfilled;
			$status['divi_detection_method'] = Divi::get_version_detection_method();

			// Load the template with prepared data.
			load_template(
				divi_squad()->get_template_path( 'requirements.php' ),
				true,
				array(
					'content'          => $content,
					'status'           => $status,
					'required_version' => $required_version,
				)
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Error displaying requirements page', false );

			// Fallback basic display if template fails.
			$this->display_fallback_page( $e );
		}
	}

	/**
	 * Display a fallback page if the template fails to load.
	 *
	 * @since 3.4.0
	 *
	 * @param Throwable $error The error that occurred.
	 *
	 * @return void
	 */
	protected function display_fallback_page( Throwable $error ): void {
		$status       = $this->status_checker->get_status();
		$is_fulfilled = $this->status_checker->is_fulfilled();

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Squad Modules Requirements', 'squad-modules-for-divi' ); ?></h1>

			<?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
				<div class="notice notice-warning">
					<p><?php esc_html_e( 'Error loading requirements template:', 'squad-modules-for-divi' ); ?><?php echo esc_html( $error->getMessage() ); ?></p>
				</div>
			<?php endif; ?>

			<?php echo $this->get_notice_content(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Function escapes its output ?>

			<table class="widefat" style="margin-top: 20px;">
				<thead>
				<tr>
					<th><?php esc_html_e( 'Requirement', 'squad-modules-for-divi' ); ?></th>
					<th><?php esc_html_e( 'Status', 'squad-modules-for-divi' ); ?></th>
					<th><?php esc_html_e( 'Details', 'squad-modules-for-divi' ); ?></th>
				</tr>
				</thead>
				<tbody>
				<?php foreach ( $status as $key => $item ) : ?>
					<?php if ( is_array( $item ) && isset( $item['state'], $item['description'], $item['info'] ) ) : ?>
						<tr>
							<td><?php echo esc_html( $item['description'] ); ?></td>
							<td>
								<?php if ( 'success' === $item['state'] ) : ?>
									<span style="color: #46b450;">✓</span>
								<?php elseif ( 'error' === $item['state'] ) : ?>
									<span style="color: #dc3232;">✗</span>
								<?php else : ?>
									<span style="color: #ffb900;">?</span>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( $item['info'] ); ?></td>
						</tr>
					<?php endif; ?>
				<?php endforeach; ?>
				</tbody>
			</table>

			<p>
				<a href="<?php echo esc_url( admin_url() ); ?>" class="button button-secondary"><?php esc_html_e( 'Back to Dashboard', 'squad-modules-for-divi' ); ?></a>
				<?php if ( $is_fulfilled ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=divi_squad' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Go to Squad Modules Dashboard', 'squad-modules-for-divi' ); ?></a>
				<?php endif; ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Add badges to the requirements page.
	 *
	 * @since  3.2.3
	 * @access public
	 *
	 * @param string $plugin_life_type The plugin life type.
	 *
	 * @return void
	 */
	public function add_badges( string $plugin_life_type ): void {
		// Add the nightly badge..
		if ( 'nightly' === $plugin_life_type ) {
			printf(
				'<li class="nightly-badge"><span class="badge-name">%s</span><span class="badge-version">%s</span></li>',
				esc_html__( 'Nightly', 'squad-modules-for-divi' ),
				esc_html__( 'current', 'squad-modules-for-divi' )
			);
		}

		// Add the stable lite badge..
		if ( 'stable' === $plugin_life_type ) {
			printf(
				'<li class="stable-lite-badge"><span class="badge-name">%s</span><span class="badge-version">%s</span></li>',
				esc_html__( 'Lite', 'squad-modules-for-divi' ),
				esc_html( divi_squad()->get_version_dot() )
			);
		}
	}

	/**
	 * Renders a standardized notice banner.
	 *
	 * @since  3.3.0
	 * @access protected
	 *
	 * @param string                                         $type    Notice type: 'error', 'warning', 'success'.
	 * @param string                                         $title   Notice title.
	 * @param string                                         $message Notice message.
	 * @param array{url: string, text: string, icon: string} $action  Optional. Action button details.
	 *
	 * @return string The HTML for the notice banner.
	 */
	protected function render_notice_banner( string $type, string $title, string $message, array $action ): string {
		$output = sprintf(
			'<div class="notice divi-squad-banner divi-squad-%s-banner">
				<div class="divi-squad-banner-content">
					<h3>%s</h3>
					<p>%s</p>',
			esc_attr( $type ),
			esc_html( $title ),
			esc_html( $message )
		);

		// Add an action button if provided..
		if ( '' !== ( $action['url'] ?? '' ) ) {
			$output .= sprintf(
				'<div class="divi-squad-notice-action">
					<div class="divi-squad-notice-action-left">
						<a href="%s" %s class="button-primary divi-squad-notice-action-button">
							<span class="dashicons dashicons-%s"></span>
							<p>%s</p>
						</a>
					</div>
				</div>',
				esc_url( $action['url'] ),
				strpos( $action['url'], 'http' ) === 0 ? 'target="_blank"' : '',
				esc_attr( $action['icon'] ),
				esc_html( $action['text'] )
			);
		}

		$output .= '</div></div>';

		return $output;
	}

	/**
	 * Filter for the required Divi version.
	 *
	 * @since  3.4.0
	 * @access public
	 *
	 * @param string $version The Divi version requirement.
	 *
	 * @return string The filtered version requirement.
	 */
	public function filter_divi_required_version( string $version ): string {
		return $this->status_checker->get_required_version();
	}

	/**
	 * Filter for the plugin life type.
	 *
	 * @since  3.4.0
	 * @access public
	 *
	 * @param string $life_type The plugin life type.
	 *
	 * @return string
	 */
	public function filter_plugin_life_type( string $life_type ): string {
		return divi_squad()->is_dev() ? 'nightly' : $life_type;
	}

	/**
	 * Filter for the plugin slug.
	 *
	 * @since  3.4.0
	 * @access public
	 *
	 * @param string $status_badge_text Existing status badge.
	 * @param bool   $is_met            Whether the requirement is met.
	 *
	 * @return string
	 */
	public function filter_render_status_badge( string $status_badge_text, bool $is_met ): string {
		if ( '' === $status_badge_text ) {
			$status_badge_text = $is_met ? esc_html__( 'OK', 'squad-modules-for-divi' ) : esc_html__( 'FAILED', 'squad-modules-for-divi' );
		}

		$badge_class = $is_met ? 'success' : 'error';
		$icon_class  = $is_met ? 'dashicons-yes-alt' : 'dashicons-warning';

		return sprintf(
			'<span class="status-badge %1$s"><span class="dashicons %2$s"></span>%3$s</span>',
			esc_attr( $badge_class ),
			esc_attr( $icon_class ),
			esc_html( $status_badge_text )
		);
	}

	/**
	 * Filter the requirement rows configuration.
	 *
	 * @since  3.4.0
	 * @access public
	 *
	 * @param array<array<string, mixed>> $rows             The default requirement rows configuration.
	 * @param array<string, mixed>        $status           The current status values.
	 * @param string                      $required_version The required Divi version.
	 *
	 * @return array<array<string, mixed>> Filtered requirement rows configuration.
	 */
	public function filter_requirement_rows_config( array $rows, array $status, string $required_version ): array {
		// Divi Theme Installed row..
		$rows[] = array(
			'id'        => 'theme_installed',
			'label'     => __( 'Divi Theme Installed', 'squad-modules-for-divi' ),
			'value'     => $status['is_theme_installed'] ?? false,
			'show'      => true,
			'condition' => true,
		);
		// Divi Builder Plugin Installed row..
		$rows[] = array(
			'id'        => 'plugin_installed',
			'label'     => __( 'Divi Builder Plugin Installed', 'squad-modules-for-divi' ),
			'value'     => $status['is_plugin_installed'] ?? false,
			'show'      => true,
			'condition' => true,
		);
		// Divi Theme Activated row..
		$rows[] = array(
			'id'        => 'theme_active',
			'label'     => __( 'Divi Theme Activated', 'squad-modules-for-divi' ),
			'value'     => $status['is_theme_active'] ?? false,
			'show'      => true,
			'condition' => $status['is_theme_installed'] ?? false,
		);
		// Divi Builder Plugin Activated row..
		$rows[] = array(
			'id'        => 'plugin_active',
			'label'     => __( 'Divi Builder Plugin Activated', 'squad-modules-for-divi' ),
			'value'     => $status['is_plugin_active'] ?? false,
			'show'      => true,
			'condition' => $status['is_plugin_installed'] ?? false,
		);
		// Divi Theme Version row..
		$rows[] = array(
			'id'           => 'theme_version',
			'label'        => __( 'Divi Theme Version', 'squad-modules-for-divi' ),
			'value'        => version_compare( $status['theme_version'] ?? '0.0.0', $required_version, '>=' ),
			'show'         => true,
			'condition'    => $status['is_theme_active'] ?? false,
			'subtitle'     => sprintf(
			/* translators: %s is the required version */
				__( 'Required: %s or higher', 'squad-modules-for-divi' ),
				$required_version
			),
			'version_info' => $status['theme_version'] ?? esc_html__( 'Unknown', 'squad-modules-for-divi' ),
		);
		// Divi Builder Plugin Version row..
		$rows[] = array(
			'id'           => 'plugin_version',
			'label'        => __( 'Divi Builder Plugin Version', 'squad-modules-for-divi' ),
			'value'        => version_compare( $status['plugin_version'] ?? '0.0.0', $required_version, '>=' ),
			'show'         => true,
			'condition'    => $status['is_plugin_active'] ?? false,
			'subtitle'     => sprintf(
			/* translators: %s is the required version */
				__( 'Required: %s or higher', 'squad-modules-for-divi' ),
				$required_version
			),
			'version_info' => $status['plugin_version'] ?? esc_html__( 'Unknown', 'squad-modules-for-divi' ),
		);

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// Add an informational row about debug mode..
			$rows[] = array(
				'id'           => 'wp_debug',
				'label'        => __( 'WordPress Debug Mode', 'squad-modules-for-divi' ),
				'value'        => true,
				'show'         => true,
				'condition'    => true,
				'subtitle'     => __( 'Development environment detected', 'squad-modules-for-divi' ),
				'custom_badge' => sprintf(
					'<span class="status-badge info"><span class="dashicons dashicons-info"></span> %s</span>',
					esc_html__( 'Enabled', 'squad-modules-for-divi' )
				),
			);
		}

		return $rows;
	}

	/**
	 * Filter the minimum requirements list.
	 *
	 * @since  3.4.0
	 * @access public
	 *
	 * @param array<array<string, string>> $requirements     Array of requirement items.
	 * @param string                       $required_version The required Divi version.
	 *
	 * @return array<array<string, string>> Filtered array of requirement items.
	 */
	public function filter_minimum_requirements( array $requirements, string $required_version ): array {
		$requirements[] = array(
			'name'  => __( 'Divi Theme/Builder:', 'squad-modules-for-divi' ),
			// Translators: %s is the required version..
			'value' => sprintf( __( 'Version %s or higher', 'squad-modules-for-divi' ), $required_version ),
		);
		$requirements[] = array(
			'name'  => __( 'WordPress:', 'squad-modules-for-divi' ),
			// Translators: %s is the required version..
			'value' => sprintf( __( 'Version %s or higher', 'squad-modules-for-divi' ), apply_filters( 'divi_squad_required_wp_version', '6.0' ) ),
		);
		$requirements[] = array(
			'name'  => __( 'PHP:', 'squad-modules-for-divi' ),
			// Translators: %s is the required version..
			'value' => sprintf( __( 'Version %s or higher', 'squad-modules-for-divi' ), apply_filters( 'divi_squad_required_php_version', '7.4' ) ),
		);

		// Add server requirements if appropriate..
		$php_memory_limit = ini_get( 'memory_limit' );
		$required_memory  = '128M';

		// Only add memory requirement if it's a concern..
		if ( $this->status_checker->convert_memory_to_bytes( $php_memory_limit ) < $this->status_checker->convert_memory_to_bytes( $required_memory ) ) {
			$requirements[] = array(
				'name'  => __( 'PHP Memory Limit:', 'squad-modules-for-divi' ),
				'value' => sprintf(
				// Translators: %s is the recommended memory limit..
					__( 'Recommended: %1$s or higher (Current: %2$s)', 'squad-modules-for-divi' ),
					$required_memory,
					$php_memory_limit
				),
			);
		}

		// Check for max execution time if it's too low..
		$max_execution_time = ini_get( 'max_execution_time' );
		if ( '0' !== $max_execution_time && (int) $max_execution_time < 30 ) {
			$requirements[] = array(
				'name'  => __( 'PHP Max Execution Time:', 'squad-modules-for-divi' ),
				'value' => sprintf(
				// Translators: %s is the recommended max execution time..
					__( 'Recommended: 30 seconds or higher (Current: %s seconds)', 'squad-modules-for-divi' ),
					$max_execution_time
				),
			);
		}

		return $requirements;
	}

	/**
	 * Add extended requirements information after the minimum requirements list.
	 *
	 * Displays additional recommended hosting environment information when the
	 * 'divi_squad_show_hosting_recommendations' filter returns true.
	 *
	 * @since  3.4.0
	 * @access public
	 *
	 * @return void
	 */
	public function add_extended_requirements_info(): void {
		try {
			// For example, we could add a note about recommended hosting environments..
			$show_hosting_info = apply_filters( 'divi_squad_show_hosting_recommendations', false );

			if ( $show_hosting_info ) {
				echo '<div class="extended-requirements-info">';
				printf( '<h3>%s</h3>', esc_html__( 'Recommended Hosting Environment', 'squad-modules-for-divi' ) );
				printf( '<p>%s</p>', esc_html__( 'For optimal performance with Squad Modules and Divi, we recommend:', 'squad-modules-for-divi' ) );
				echo '<ul>';
				printf( '<li>%s</li>', esc_html__( 'PHP 8.0 or higher', 'squad-modules-for-divi' ) );
				printf( '<li>%s</li>', esc_html__( 'MySQL 5.7 or MariaDB 10.3 or higher', 'squad-modules-for-divi' ) );
				printf( '<li>%s</li>', esc_html__( 'PHP Memory limit of 256M or higher', 'squad-modules-for-divi' ) );
				printf( '<li>%s</li>', esc_html__( 'PHP max_execution_time of 60 seconds or higher', 'squad-modules-for-divi' ) );
				echo '</ul>';
				echo '</div>';
			}

			/**
			 * Fires after extended requirements information is displayed.
			 *
			 * @since 3.4.0
			 */
			do_action( 'divi_squad_after_extended_requirements_info' );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Extended_Requirements_Display_Error' );
		}
	}

	/**
	 * Add custom sections to the requirement page.
	 *
	 * Adds a troubleshooting section to help users resolve requirement issues
	 * when requirements are not met and the 'divi_squad_show_troubleshooting_section'
	 * filter returns true.
	 *
	 * @since  3.4.0
	 * @access public
	 *
	 * @param array<string, mixed> $status           The current status values.
	 * @param string               $required_version The required Divi version.
	 * @param bool                 $is_fulfilled     Whether all requirements are met.
	 *
	 * @return void
	 */
	public function add_custom_sections( array $status, string $required_version, bool $is_fulfilled ): void {
		try {
			// For example, add a troubleshooting section if requirements aren't met..
			if ( ! $is_fulfilled && apply_filters( 'divi_squad_show_troubleshooting_section', true ) ) {
				echo '<div class="requirements-section">';
				printf( '<h3>%s</h3>', esc_html__( 'Troubleshooting', 'squad-modules-for-divi' ) );
				printf( '<p>%s</p>', esc_html__( 'Having trouble meeting the requirements? Here are some common solutions:', 'squad-modules-for-divi' ) );

				echo '<div class="troubleshooting-tips">';
				printf( '<h3>%s</h3>', esc_html__( 'Common Issues', 'squad-modules-for-divi' ) );
				echo '<ul>';
				printf(
					'<li class="tip-item"><strong>%s</strong> %s</li>',
					esc_html__( 'Divi not detecting:', 'squad-modules-for-divi' ),
					esc_html__( 'Make sure you\'re using an official Elegant Themes version of Divi.', 'squad-modules-for-divi' )
				);
				printf(
					'<li class="tip-item"><strong>%s</strong> %s</li>',
					esc_html__( 'Version conflicts:', 'squad-modules-for-divi' ),
					esc_html__( 'Clear your browser cache and WordPress cache after updating Divi.', 'squad-modules-for-divi' )
				);
				printf(
					'<li class="tip-item"><strong>%s</strong> %s</li>',
					esc_html__( 'Activation issues:', 'squad-modules-for-divi' ),
					esc_html__( 'Temporarily deactivate other plugins to check for conflicts.', 'squad-modules-for-divi' )
				);
				echo '</ul>';
				echo '</div>';

				echo '</div>';
			}

			/**
			 * Fires after custom sections are added to the requirements page.
			 *
			 * @since 3.4.0
			 *
			 * @param array<string, mixed> $status           The current status values.
			 * @param string               $required_version The required Divi version.
			 * @param bool                 $is_fulfilled     Whether all requirements are met.
			 */
			do_action( 'divi_squad_after_custom_sections', $status, $required_version, $is_fulfilled );
		} catch ( Throwable $e ) {
			divi_squad()->log_error(
				$e,
				'Custom_Sections_Display_Error',
				true,
				array(
					'is_fulfilled'     => $is_fulfilled,
					'required_version' => $required_version,
				)
			);
		}
	}

	/**
	 * Add additional debug information items to the requirements page.
	 *
	 * Displays server information, MySQL version, PHP settings, and theme details
	 * to help with troubleshooting and support.
	 *
	 * @since  3.4.0
	 * @access public
	 *
	 * @return void
	 */
	public function add_debug_info_items(): void {
		try {
			// Server software info..
			printf(
				'<li><strong>%s</strong> %s</li>',
				esc_html__( 'Server Software:', 'squad-modules-for-divi' ),
				esc_html( sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ) ) )
			);

			// MySQL version..
			global $wpdb;
			$mysql_version = $wpdb->db_version();

			printf(
				'<li><strong>%s</strong> %s</li>',
				esc_html__( 'MySQL Version:', 'squad-modules-for-divi' ),
				esc_html( $mysql_version )
			);

			// Max execution time..
			printf(
				'<li><strong>%s</strong> %s</li>',
				esc_html__( 'PHP Max Execution Time:', 'squad-modules-for-divi' ),
				esc_html( ini_get( 'max_execution_time' ) . 's' )
			);

			// Active theme..
			$theme = wp_get_theme();
			printf(
				'<li><strong>%s</strong> %s</li>',
				esc_html__( 'Active Theme:', 'squad-modules-for-divi' ),
				esc_html( $theme->get( 'Name' ) . ' ' . $theme->get( 'Version' ) )
			);

			/**
			 * Fires after debug information items are added to the requirements page.
			 *
			 * Allows for adding additional debug information items.
			 *
			 * @since 3.4.0
			 */
			do_action( 'divi_squad_after_debug_info_items' );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Debug_Info_Display_Error' );
		}
	}

	/**
	 * Get the HTML content for the admin notice.
	 *
	 * @return string Notice HTML.
	 */
	protected function get_notice_content(): string {
		$status           = $this->status_checker->get_status();
		$required_version = $this->status_checker->get_required_version();
		$is_fulfilled     = $this->status_checker->is_fulfilled();

		// Theme is active but outdated..
		if ( true === ( $status['is_theme_active'] ?? false ) && version_compare( $status['theme_version'] ?? '0.0.0', $required_version, '<' ) ) {
			return $this->render_notice_banner(
				'warning',
				__( 'Divi Update Required', 'squad-modules-for-divi' ),
				sprintf(
				/* translators: %1$s: Required Divi version, %2$s: Current Divi version */
					__( 'Squad Modules requires Divi version %1$s or higher. Your current Divi version is %2$s. Please update to continue using Squad Modules.', 'squad-modules-for-divi' ),
					$required_version,
					$status['theme_version']
				),
				array(
					'url'  => admin_url( 'themes.php' ),
					'text' => __( 'Update Divi', 'squad-modules-for-divi' ),
					'icon' => 'update',
				)
			);
		}

		// Plugin is active but outdated..
		if ( true === ( $status['is_plugin_active'] ?? false ) && version_compare( $status['plugin_version'] ?? '0.0.0', $required_version, '<' ) ) {
			return $this->render_notice_banner(
				'warning',
				__( 'Divi Builder Update Required', 'squad-modules-for-divi' ),
				sprintf(
				/* translators: %1$s: Required Divi version, %2$s: Current Divi version */
					__( 'Squad Modules requires Divi Builder version %1$s or higher. Your current Divi Builder version is %2$s. Please update to continue using Squad Modules.', 'squad-modules-for-divi' ),
					$required_version,
					$status['plugin_version']
				),
				array(
					'url'  => admin_url( 'plugins.php' ),
					'text' => __( 'Update Divi Builder', 'squad-modules-for-divi' ),
					'icon' => 'update',
				)
			);
		}

		// Theme is installed but not active..
		if ( true === ( $status['is_theme_installed'] ?? false ) && true !== ( $status['is_theme_active'] ?? false ) ) {
			return $this->render_notice_banner(
				'error',
				__( 'Divi Theme Not Activated', 'squad-modules-for-divi' ),
				__( 'Squad Modules has detected that you have Divi theme installed but not activated. Please activate the Divi theme to use Squad Modules.', 'squad-modules-for-divi' ),
				array(
					'url'  => admin_url( 'themes.php' ),
					'text' => __( 'Activate Divi Theme', 'squad-modules-for-divi' ),
					'icon' => 'admin-appearance',
				)
			);
		}

		// Plugin is installed but not active..
		if ( true === ( $status['is_plugin_installed'] ?? false ) && true !== ( $status['is_plugin_active'] ?? false ) ) {
			return $this->render_notice_banner(
				'error',
				__( 'Divi Builder Plugin Not Activated', 'squad-modules-for-divi' ),
				__( 'Squad Modules has detected that you have the Divi Builder plugin installed but not activated. Please activate the Divi Builder plugin to use Squad Modules.', 'squad-modules-for-divi' ),
				array(
					'url'  => admin_url( 'plugins.php' ),
					'text' => __( 'Activate Divi Builder', 'squad-modules-for-divi' ),
					'icon' => 'admin-plugins',
				)
			);
		}

		// Not installed — fallback when nothing is installed or general failure..
		if ( ! $is_fulfilled ) {
			return $this->render_notice_banner(
				'error',
				__( 'Divi Not Installed', 'squad-modules-for-divi' ),
				__( 'Squad Modules requires either the Divi/Extra theme or Divi Builder plugin to be installed and activated. Please install Divi to use Squad Modules.', 'squad-modules-for-divi' ),
				array(
					'url'  => 'https://www.elegantthemes.com/gallery/divi/',
					'text' => __( 'Get Divi', 'squad-modules-for-divi' ),
					'icon' => 'external',
				)
			);
		}

		// All requirements are met..
		return $this->render_notice_banner(
			'success',
			__( 'Divi Requirements Met', 'squad-modules-for-divi' ),
			__( 'Your Divi installation meets all the requirements for Squad Modules. You can start using the modules now!', 'squad-modules-for-divi' ),
			array(
				'url'  => admin_url( 'admin.php?page=' . divi_squad()->get_admin_menu_slug() ),
				'text' => __( 'Go to Dashboard', 'squad-modules-for-divi' ),
				'icon' => 'admin-home',
			)
		);
	}
}
