<?php
/**
 * Template file for the Divi requirements page.
 *
 * @since   3.0.0
 * @updated 3.3.0
 *
 * @author  The WP Squad <support@squadmodules.com>
 * @package DiviSquad
 *
 * @var array{content: string, status: array<string, mixed>, required_version: string} $args The arguments to the template.
 *
 * @wordpress
 * @uses    wp_doing_ajax()
 * @uses    esc_html__()
 * @uses    load_template()
 * @uses    esc_html_e()
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

$divi_squad_required_version = $args['required_version'] ?? divi_squad()->get_option( 'RequiresDIVI', '4.14.0' );
$divi_squad_is_fulfilled     = (bool) ( $divi_squad_status['is_fulfilled'] ?? false );

// Verify current plugin life type.
$divi_squad_plugin_life_type = divi_squad()->is_dev() ? 'nightly' : 'stable';

/**
 * Filter the plugin life type.
 *
 * @since 3.2.3
 *
 * @param string $divi_squad_plugin_life_type The plugin life type.
 */
$divi_squad_plugin_life_type = apply_filters( 'divi_squad_plugin_life_type', $divi_squad_plugin_life_type );

/**
 * Helper function to render a status badge.
 *
 * @param bool $is_met Whether the requirement is met.
 *
 * @return string HTML for the status badge.
 */
function divi_squad_render_status_badge( bool $is_met ): string {
	if ( $is_met ) {
		return sprintf(
			'<span class="status-badge success"><span class="dashicons dashicons-yes-alt"></span> %s</span>',
			esc_html__( 'Met', 'squad-modules-for-divi' )
		);
	}

	return sprintf(
		'<span class="status-badge error"><span class="dashicons dashicons-warning"></span> %s</span>',
		esc_html__( 'Not Met', 'squad-modules-for-divi' )
	);
}

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
					<h2><?php esc_html_e( 'System Requirements', 'squad-modules-for-divi' ); ?></h2>

					<div class="notice-container">
						<?php echo $divi_squad_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content is already escaped in the Requirements class ?>
					</div>

					<div class="requirements-info">
						<div class="requirements-section">
							<h3><?php esc_html_e( 'System Status', 'squad-modules-for-divi' ); ?></h3>

							<div class="requirements-table">
								<div class="requirement-row">
									<div class="requirement-name">
										<strong><?php esc_html_e( 'Overall Status', 'squad-modules-for-divi' ); ?></strong>
									</div>
									<div class="requirement-value">
										<?php echo divi_squad_render_status_badge( $divi_squad_is_fulfilled ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Function escapes its output ?>
									</div>
								</div>
								<div class="requirement-row">
									<div class="requirement-name">
										<strong><?php esc_html_e( 'Divi Theme Installed', 'squad-modules-for-divi' ); ?></strong>
									</div>
									<div class="requirement-value">
										<?php echo divi_squad_render_status_badge( $divi_squad_status['is_theme_installed'] ?? false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</div>
								</div>
								<div class="requirement-row">
									<div class="requirement-name">
										<strong><?php esc_html_e( 'Divi Builder Plugin Installed', 'squad-modules-for-divi' ); ?></strong>
									</div>
									<div class="requirement-value">
										<?php echo divi_squad_render_status_badge( $divi_squad_status['is_plugin_installed'] ?? false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</div>
								</div>
								<?php if ( ( $divi_squad_status['is_theme_installed'] ?? false ) === true ) : ?>
									<div class="requirement-row">
										<div class="requirement-name">
											<strong><?php esc_html_e( 'Divi Theme Activated', 'squad-modules-for-divi' ); ?></strong>
										</div>
										<div class="requirement-value">
											<?php echo divi_squad_render_status_badge( $divi_squad_status['is_theme_active'] ?? false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										</div>
									</div>
								<?php endif; ?>
								<?php if ( ( $divi_squad_status['is_plugin_installed'] ?? false ) === true ) : ?>
									<div class="requirement-row">
										<div class="requirement-name">
											<strong><?php esc_html_e( 'Divi Builder Plugin Activated', 'squad-modules-for-divi' ); ?></strong>
										</div>
										<div class="requirement-value">
											<?php echo divi_squad_render_status_badge( $divi_squad_status['is_plugin_active'] ?? false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										</div>
									</div>
								<?php endif; ?>
								<?php if ( ( $divi_squad_status['is_theme_active'] ?? false ) === true ) : ?>
									<div class="requirement-row">
										<div class="requirement-name">
											<strong><?php esc_html_e( 'Divi Theme Version', 'squad-modules-for-divi' ); ?></strong>
											<div class="requirement-sub">
												<?php
												echo esc_html(
													sprintf(
													/* translators: %s is the required version */
														__( 'Required: %s or higher', 'squad-modules-for-divi' ),
														$divi_squad_required_version
													)
												);
												?>
											</div>
										</div>
										<div class="requirement-value">
											<div class="version-info"><?php echo esc_html( $divi_squad_status['theme_version'] ?? 'Unknown' ); ?></div>
											<?php
											$divi_squad_meets_theme_version = isset( $divi_squad_status['theme_version'] ) && version_compare( $divi_squad_status['theme_version'], $divi_squad_required_version, '>=' );
											echo divi_squad_render_status_badge( $divi_squad_meets_theme_version ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
											?>
										</div>
									</div>
								<?php endif; ?>
								<?php if ( ( $divi_squad_status['is_plugin_active'] ?? false ) === true ) : ?>
									<div class="requirement-row">
										<div class="requirement-name">
											<strong><?php esc_html_e( 'Divi Builder Plugin Version', 'squad-modules-for-divi' ); ?></strong>
											<div class="requirement-sub">
												<?php
												echo esc_html(
													sprintf(
													/* translators: %s is the required version */
														__( 'Required: %s or higher', 'squad-modules-for-divi' ),
														$divi_squad_required_version
													)
												);
												?>
											</div>
										</div>
										<div class="requirement-value">
											<div class="version-info"><?php echo esc_html( $divi_squad_status['plugin_version'] ?? 'Unknown' ); ?></div>
											<?php
											$divi_squad_meets_plugin_version = isset( $divi_squad_status['plugin_version'] ) && version_compare( $divi_squad_status['plugin_version'], $divi_squad_required_version, '>=' );
											echo divi_squad_render_status_badge( $divi_squad_meets_plugin_version ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
											?>
										</div>
									</div>
								<?php endif; ?>
							</div>
						</div>

						<div class="requirements-section">
							<h3><?php esc_html_e( 'Minimum Requirements', 'squad-modules-for-divi' ); ?></h3>
							<ul>
								<li>
									<strong><?php esc_html_e( 'Divi Theme/Builder:', 'squad-modules-for-divi' ); ?></strong>
									<?php
									// translators: %s is the required version.
									echo esc_html( sprintf( __( 'Version %s or higher', 'squad-modules-for-divi' ), $divi_squad_required_version ) );
									?>
								</li>
								<li>
									<strong><?php esc_html_e( 'WordPress:', 'squad-modules-for-divi' ); ?></strong>
									<?php
									// translators: %s is the required version.
									echo esc_html( sprintf( __( 'Version %s or higher', 'squad-modules-for-divi' ), '6.0' ) );
									?>
								</li>
								<li>
									<strong><?php esc_html_e( 'PHP:', 'squad-modules-for-divi' ); ?></strong>
									<?php
									// translators: %s is the required version.
									echo esc_html( sprintf( __( 'Version %s or higher', 'squad-modules-for-divi' ), '7.4' ) );
									?>
								</li>
							</ul>
						</div>

						<div class="requirements-section<?php echo ( false === $divi_squad_is_fulfilled ) ? ' highlighted' : ''; ?>">
							<h3><?php esc_html_e( 'Need Help?', 'squad-modules-for-divi' ); ?></h3>
							<p>
								<?php esc_html_e( 'If you need assistance with installation or have questions, please visit our support center:', 'squad-modules-for-divi' ); ?>
							</p>
							<p>
								<a href="<?php echo esc_url( Links::PREMIUM_SUPPORT_URL ); ?>" class="button button-primary squad-button fill-button" target="_blank" style="max-width: fit-content;">
									<?php esc_html_e( 'Visit Support Center', 'squad-modules-for-divi' ); ?>
								</a>
							</p>
							<?php if ( false === $divi_squad_is_fulfilled ) : ?>
								<p class="requirements-suggestion">
									<?php esc_html_e( 'To get Squad Modules working properly, please address the issues highlighted above.', 'squad-modules-for-divi' ); ?>
								</p>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</main>
