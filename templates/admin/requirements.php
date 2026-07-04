<?php
/**
 * Template file for the Divi requirements page.
 *
 * @since   3.0.0
 * @updated 3.4.0
 *
 * @author  The WP Squad <support@squadmodules.com>
 * @package DiviSquad
 *
 * @var array{content: string, status: array<string, mixed>, required_version: string} $args The arguments to the template.
 */

use DiviSquad\Core\Supports\Links;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
	die( 'Access forbidden from AJAX request.' );
}

// Extract variables from args.
$divi_squad_content = $args['content'] ?? '';
$divi_squad_status  = $args['status'] ?? array();

/**
 * Filter the required Divi version.
 *
 * This filter allows modifying the minimum Divi version required for the plugin
 * to function correctly. The version is used for compatibility checks and
 * displaying system requirements to users.
 *
 * @since 3.4.0
 *
 * @param string $required_version The required Divi version. Default '4.14.0'.
 * @return string Modified required Divi version.
 */
$divi_squad_required_version = apply_filters( 'divi_squad_required_divi_version', '4.14.0' );

$divi_squad_is_fulfilled = (bool) ( $divi_squad_status['is_fulfilled'] ?? false );

/**
 * Filter the plugin life type.
 *
 * This filter allows modifying the plugin's life cycle type, which determines
 * how the plugin is displayed in the admin area and affects certain features.
 *
 * Possible values include:
 * - 'stable': Regular stable release version
 * - 'beta': Beta testing version with new features
 * - 'alpha': Early development version
 * - 'rc': Release candidate version
 * - 'dev': Development version
 *
 * @since 3.2.3
 *
 * @param string $plugin_life_type The plugin life type. Default is 'stable'.
 * @return string Modified plugin life type.
 */
$divi_squad_plugin_life_type = apply_filters( 'divi_squad_plugin_life_type', 'stable' );

/**
 * Filter the requirements page title.
 *
 * This filter allows customizing the main heading of the system requirements page.
 *
 * @since 3.4.0
 *
 * @param string $page_title The page title. Default is 'System Requirements'.
 * @return string Modified page title.
 */
$divi_squad_plugin_page_title = apply_filters( 'divi_squad_requirements_page_title', __( 'System Requirements', 'squad-modules-for-divi' ) );

?>

<main id="squad-modules-app" class="squad-modules-app squad-components squad-system-requirements">
	<div class="app-wrapper">
		<div class="app-header">
			<?php
			load_template(
				divi_squad()->get_template_path( 'admin/common/layout-header.php' ),
				true,
				array( 'divi_squad_plugin_life_type' => $divi_squad_plugin_life_type )
			);
			?>
		</div>
		<div class="wrapper-container">
			<div class="requirements-wrapper">
				<div class="requirements-content">
					<h2><?php echo esc_html( $divi_squad_plugin_page_title ); ?></h2>

					<?php
					/**
					 * Fires before the requirements notice container is displayed.
					 *
					 * This action allows developers to add content or perform actions
					 * before the main notice container in the requirements page.
					 *
					 * @since 3.4.0
					 *
					 * @param array  $divi_squad_status           The current requirement status values.
					 * @param string $divi_squad_required_version The required Divi version.
					 * @param bool   $divi_squad_is_fulfilled     Whether all requirements are met.
					 */
					do_action( 'divi_squad_before_notice_container', $divi_squad_status, $divi_squad_required_version, $divi_squad_is_fulfilled );
					?>

					<div class="notice-container">
						<?php echo $divi_squad_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content is already escaped in the Requirements class ?>
					</div>

					<div class="requirements-info">
						<?php
						/**
						 * Fires before the requirements section is displayed.
						 *
						 * This action allows developers to add content or perform actions
						 * before the main requirements section in the requirements page.
						 *
						 * @since 3.4.0
						 *
						 * @param array  $divi_squad_status           The current requirement status values.
						 * @param string $divi_squad_required_version The required Divi version.
						 * @param bool   $divi_squad_is_fulfilled     Whether all requirements are met.
						 */
						do_action( 'divi_squad_before_requirements_section', $divi_squad_status, $divi_squad_required_version, $divi_squad_is_fulfilled );
						?>

						<div class="requirements-section">
							<?php
							/**
							 * Filter the system status section title.
							 *
							 * This filter allows customizing the heading of the system status
							 * section on the requirements page.
							 *
							 * @since 3.4.0
							 *
							 * @param string $section_title The section title. Default is 'System Status'.
							 * @return string Modified section title.
							 */
							$divi_squad_plugin_page_system_status_title = apply_filters( 'divi_squad_system_status_title', __( 'System Status', 'squad-modules-for-divi' ) );
							?>
							<h3><?php echo esc_html( $divi_squad_plugin_page_system_status_title ); ?></h3>

							<div class="requirements-table">
								<div class="requirement-row">
									<div class="requirement-name">
										<strong><?php esc_html_e( 'Overall Status', 'squad-modules-for-divi' ); ?></strong>
									</div>
									<div class="requirement-value">
										<?php
										/**
										 * Filter the status badge text and appearance.
										 *
										 * This filter allows customizing the HTML output for requirement status badges
										 * displayed throughout the requirements page. It controls the visual indicator
										 * showing whether requirements are met.
										 *
										 * @since 3.4.0
										 *
										 * @param string $status_badge_text The status badge text. Default is empty string.
										 * @param bool   $is_met            Whether the requirement is met.
										 *
										 * @return string The filtered status badge HTML. Should be properly escaped.
										 */
										$divi_squad_render_status_badge = apply_filters( 'divi_squad_render_status_badge', '', $divi_squad_is_fulfilled )
										?>
										<?php echo wp_kses_post( $divi_squad_render_status_badge ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Function escapes its output ?>
									</div>
								</div>

								<?php
								/**
								 * Filter the requirement rows to be displayed in the status table.
								 *
								 * This filter allows customizing the requirement rows that are displayed
								 * in the system status table. Each row should be an array with the following keys:
								 * - 'label': The name/label of the requirement (string)
								 * - 'show': Whether to show this requirement (boolean)
								 * - 'condition': Whether the requirement is met (boolean)
								 * - 'subtitle': Optional subtitle text (string)
								 * - 'version_info': Optional version information (string)
								 * - 'custom_badge': Optional custom badge HTML (string, pre-escaped)
								 *
								 * @since 3.4.0
								 *
								 * @param array  $divi_squad_plugin_requirement_rows Array of requirement rows. Default empty array.
								 * @param array  $divi_squad_status                  The current status values.
								 * @param string $divi_squad_required_version        The required Divi version.
								 * @return array Modified array of requirement rows.
								 */
								$divi_squad_plugin_requirement_rows = apply_filters( 'divi_squad_requirement_rows_config', array(), $divi_squad_status, $divi_squad_required_version );

								// Display each requirement row.
								foreach ( $divi_squad_plugin_requirement_rows as $divi_squad_plugin_requirement_row ) {
									// Skip if the row shouldn't be shown or its condition isn't met.
									if ( ! ( $divi_squad_plugin_requirement_row['show'] ?? false ) || ! ( $divi_squad_plugin_requirement_row['condition'] ?? false ) ) {
										continue;
									}

									// Determine if this is a version row (has version_info).
									$divi_squad_is_version_row = isset( $divi_squad_plugin_requirement_row['version_info'] );
									?>
									<div class="requirement-row">
										<div class="requirement-name">
											<strong><?php echo esc_html( $divi_squad_plugin_requirement_row['label'] ); ?></strong>
											<?php if ( isset( $divi_squad_plugin_requirement_row['subtitle'] ) ) : ?>
												<div class="requirement-sub">
													<?php echo esc_html( $divi_squad_plugin_requirement_row['subtitle'] ); ?>
												</div>
											<?php endif; ?>
										</div>
										<div class="requirement-value">
											<?php if ( $divi_squad_is_version_row ) : ?>
												<div class="version-info"><?php echo esc_html( $divi_squad_plugin_requirement_row['version_info'] ); ?></div>
											<?php endif; ?>
											<?php
											if ( isset( $divi_squad_plugin_requirement_row['custom_badge'] ) ) {
												echo $divi_squad_plugin_requirement_row['custom_badge']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Custom badges should be pre-escaped
											} else {
												/**
												 * Filter the status badge text and appearance.
												 *
												 * @since 3.4.0
												 *
												 * @param string $status_badge_text The status badge text.
												 * @param bool $is_met Whether the requirement is met.
												 *
												 * @return string The filtered status badge HTML.
												 */
												$divi_squad_render_status_badge = apply_filters( 'divi_squad_render_status_badge', '', $divi_squad_plugin_requirement_row['value'] ?? false );

												echo wp_kses_post( $divi_squad_render_status_badge ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Function escapes its output
											}
											?>
										</div>
									</div>
									<?php
								}
								?>

								<?php
								/**
								 * Fires after the standard requirement rows are displayed.
								 *
								 * This action allows developers to add additional requirement rows
								 * to the requirements table. Use this to add custom requirements checks.
								 *
								 * @since 3.4.0
								 *
								 * @param array  $divi_squad_status           The current requirement status values.
								 * @param string $divi_squad_required_version The required Divi version.
								 * @param bool   $divi_squad_is_fulfilled     Whether all requirements are met.
								 */
								do_action( 'divi_squad_requirement_rows', $divi_squad_status, $divi_squad_required_version, $divi_squad_is_fulfilled );
								?>
							</div>

							<?php
							/**
							 * Fires after the requirements table is displayed.
							 *
							 * This action allows developers to add content after the
							 * requirements table but still within the requirements section.
							 *
							 * @since 3.4.0
							 *
							 * @param array  $divi_squad_status           The current requirement status values.
							 * @param string $divi_squad_required_version The required Divi version.
							 * @param bool   $divi_squad_is_fulfilled     Whether all requirements are met.
							 */
							do_action( 'divi_squad_after_requirements_table', $divi_squad_status, $divi_squad_required_version, $divi_squad_is_fulfilled );
							?>
						</div>

						<div class="requirements-section">
							<?php
							/**
							 * Filter the minimum requirements section title.
							 *
							 * This filter allows customizing the heading of the minimum requirements
							 * section on the requirements page.
							 *
							 * @since 3.4.0
							 *
							 * @param string $section_title The section title. Default is 'Minimum Requirements'.
							 * @return string Modified section title.
							 */
							$divi_squad_minimum_requirements_title = apply_filters( 'divi_squad_minimum_requirements_title', __( 'Minimum Requirements', 'squad-modules-for-divi' ) );
							?>
							<h3><?php echo esc_html( $divi_squad_minimum_requirements_title ); ?></h3>
							<?php
							/**
							 * Filter the minimum requirements list.
							 *
							 * This filter allows customizing the list of minimum requirements displayed
							 * in the requirements page. Each item should be an array with 'name' and 'value' keys.
							 * Example: ['name' => 'PHP Version', 'value' => '7.4 or higher']
							 *
							 * @since 3.4.0
							 *
							 * @param array  $requirements     Array of requirement items, each with 'name' and 'value' keys. Default empty array.
							 * @param string $required_version The required Divi version.
							 * @return array Modified array of requirement items.
							 */
							$divi_squad_minimum_requirements = apply_filters( 'divi_squad_minimum_requirements', array(), $divi_squad_required_version );
							?>
							<ul>
								<?php foreach ( $divi_squad_minimum_requirements as $divi_squad_minimum_requirement ) : ?>
									<li>
										<strong><?php echo esc_html( $divi_squad_minimum_requirement['name'] ); ?></strong>
										<?php echo esc_html( $divi_squad_minimum_requirement['value'] ); ?>
									</li>
								<?php endforeach; ?>
							</ul>
							<?php
							/**
							 * Fires after the minimum requirements list is displayed.
							 *
							 * This action allows developers to add additional content
							 * after the minimum requirements list.
							 *
							 * @since 3.4.0
							 */
							do_action( 'divi_squad_after_minimum_requirements' );
							?>
						</div>

						<div class="requirements-section<?php echo ( false === $divi_squad_is_fulfilled ) ? ' highlighted' : ''; ?>">
							<?php
							/**
							 * Filter the help section title.
							 *
							 * This filter allows customizing the heading of the help section
							 * on the requirements page. The title may be different depending on
							 * whether requirements are met.
							 *
							 * @since 3.4.0
							 *
							 * @param string $section_title The section title. Default is 'Need Help?'.
							 * @param bool   $is_fulfilled  Whether all requirements are met.
							 * @return string Modified section title.
							 */
							$divi_squad_help_section_title = apply_filters( 'divi_squad_help_section_title', __( 'Need Help?', 'squad-modules-for-divi' ), $divi_squad_is_fulfilled );
							?>
							<h3><?php echo esc_html( $divi_squad_help_section_title ); ?></h3>

							<?php
							/**
							 * Filter the help section message.
							 *
							 * This filter allows customizing the help message displayed in
							 * the help section of the requirements page. The message may be
							 * different depending on whether requirements are met.
							 *
							 * @since 3.4.0
							 *
							 * @param string $help_message The help message. Default is instructional text about visiting support.
							 * @param bool   $is_fulfilled Whether all requirements are met.
							 * @return string Modified help message.
							 */
							$divi_squad_help_message = apply_filters(
								'divi_squad_help_section_message',
								__( 'If you need assistance with installation or have questions, please visit our support center:', 'squad-modules-for-divi' ),
								$divi_squad_is_fulfilled
							);
							?>
							<p><?php echo esc_html( $divi_squad_help_message ); ?></p>

							<p>
								<?php
								/**
								 * Filter the support center URL.
								 *
								 * This filter allows customizing the URL for the support center
								 * button displayed in the help section. The URL may be different
								 * depending on whether requirements are met.
								 *
								 * @since 3.4.0
								 *
								 * @param string $support_url  The support center URL. Default is the premium support URL.
								 * @param bool   $is_fulfilled Whether all requirements are met.
								 * @return string Modified support center URL.
								 */
								$divi_squad_support_url = apply_filters( 'divi_squad_support_center_url', Links::PREMIUM_SUPPORT_URL, $divi_squad_is_fulfilled );

								/**
								 * Filter the support center button text.
								 *
								 * This filter allows customizing the text displayed on the
								 * support center button in the help section. The text may be
								 * different depending on whether requirements are met.
								 *
								 * @since 3.4.0
								 *
								 * @param string $button_text  The button text. Default is 'Visit Support Center'.
								 * @param bool   $is_fulfilled Whether all requirements are met.
								 * @return string Modified button text.
								 */
								$divi_squad_support_button_text = apply_filters( 'divi_squad_support_button_text', __( 'Visit Support Center', 'squad-modules-for-divi' ), $divi_squad_is_fulfilled );
								?>
								<a href="<?php echo esc_url( $divi_squad_support_url ); ?>" class="squad-button fill-button" target="_blank">
									<?php echo esc_html( $divi_squad_support_button_text ); ?>
								</a>

								<a href="<?php echo esc_url( $divi_squad_support_url ); ?>" class="squad-button" target="_blank">
									<?php esc_html_e( 'View documentation', 'squad-modules-for-divi' ); ?>
									<span class="dashicons dashicons-external"></span>
								</a>
							</p>

							<?php if ( false === $divi_squad_is_fulfilled ) : ?>
								<p class="requirements-suggestion">
									<?php
									/**
									 * Filter the requirement suggestion message shown when requirements are not met.
									 *
									 * This filter allows customizing the suggestion message displayed when
									 * system requirements are not fulfilled. This message provides guidance
									 * to users on how to resolve requirement issues.
									 *
									 * @since 3.4.0
									 *
									 * @param string $suggestion_message The suggestion message. Default instructs to address highlighted issues.
									 * @param array  $status             The current status values.
									 * @return string Modified suggestion message.
									 */
									$divi_squad_suggestion_message = apply_filters(
										'divi_squad_requirements_suggestion',
										__( 'To get Squad Modules working properly, please address the issues highlighted above.', 'squad-modules-for-divi' ),
										$divi_squad_status
									);
									echo esc_html( $divi_squad_suggestion_message );
									?>
								</p>
							<?php endif; ?>

							<?php
							/**
							 * Fires at the end of the help section content.
							 *
							 * This action allows developers to add additional content
							 * to the help section of the requirement page.
							 *
							 * @since 3.4.0
							 *
							 * @param bool  $divi_squad_is_fulfilled Whether all requirements are met.
							 * @param array $divi_squad_status       The current requirement status values.
							 */
							do_action( 'divi_squad_help_section_content', $divi_squad_is_fulfilled, $divi_squad_status );
							?>
						</div>

						<?php
						/**
						 * Fires after all standard sections of the requirement page.
						 *
						 * This action allows developers to add completely new sections
						 * to the requirement page after all the standard sections.
						 *
						 * @since 3.4.0
						 *
						 * @param array  $divi_squad_status           The current requirement status values.
						 * @param string $divi_squad_required_version The required Divi version.
						 * @param bool   $divi_squad_is_fulfilled     Whether all requirements are met.
						 */
						do_action( 'divi_squad_after_standard_sections', $divi_squad_status, $divi_squad_required_version, $divi_squad_is_fulfilled );
						?>

						<?php
						/**
						 * Filter whether to show the debug information section.
						 *
						 * This filter allows controlling the visibility of the debug information
						 * section on the requirements page. By default, it's only shown when
						 * WP_DEBUG is enabled.
						 *
						 * @since 3.4.0
						 *
						 * @param bool  $show_debug Whether to show debug info. Default is true when WP_DEBUG is enabled.
						 * @param array $status     The current status values.
						 * @return bool Whether to show the debug section.
						 */
						if ( apply_filters( 'divi_squad_show_debug_section', defined( 'WP_DEBUG' ) && WP_DEBUG, $divi_squad_status ) ) :
							?>
							<div class="requirements-section debug-section">
								<h3><?php esc_html_e( 'Debug Information', 'squad-modules-for-divi' ); ?></h3>
								<p><?php esc_html_e( 'The following information may be helpful for troubleshooting:', 'squad-modules-for-divi' ); ?></p>

								<div class="debug-info">
									<ul>
										<li>
											<strong><?php esc_html_e( 'WordPress Version:', 'squad-modules-for-divi' ); ?></strong>
											<?php echo esc_html( get_bloginfo( 'version' ) ); ?>
										</li>
										<li>
											<strong><?php esc_html_e( 'PHP Version:', 'squad-modules-for-divi' ); ?></strong>
											<?php echo esc_html( PHP_VERSION ); ?>
										</li>
										<li>
											<strong><?php esc_html_e( 'PHP Memory Limit:', 'squad-modules-for-divi' ); ?></strong>
											<?php echo esc_html( ini_get( 'memory_limit' ) ); ?>
										</li>
										<li>
											<strong><?php esc_html_e( 'Squad Modules Version:', 'squad-modules-for-divi' ); ?></strong>
											<?php echo esc_html( divi_squad()->get_version_dot() ); ?>
										</li>
										<?php
										/**
										 * Fires after the standard debug information items.
										 *
										 * This action allows developers to add additional debug information
										 * items to the debug section of the requirements page.
										 *
										 * @since 3.4.0
										 */
										do_action( 'divi_squad_debug_info_items' );
										?>
									</ul>
								</div>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</main>