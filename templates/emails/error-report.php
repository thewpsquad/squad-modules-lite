<?php
/**
 * Enhanced Error Report Email Template (No JavaScript)
 *
 * Comprehensive template for rendering detailed error reports with improved UI/UX
 * for better debugging and support capabilities, with email-client-friendly collapsibles.
 *
 * @since   3.1.7
 * @since   3.3.3 Added improved Divi detection reporting
 *
 * Available variables:
 * @package DiviSquad\Emails
 * @var array<string, mixed> $environment     Environment information (PHP version, WordPress version, etc.)
 * @var string               $error_message   The error message
 * @var mixed                $error_code      The error code
 * @var string               $error_file      The file where the error occurred
 * @var int                  $error_line      The line where the error occurred
 * @var string               $stack_trace     Stack trace of the error
 * @var string               $site_url        The website URL
 * @var string               $site_name       The website name
 * @var string               $timestamp       The timestamp when the error occurred
 * @var string               $charset         The site charset
 * @var array                $request_data    Information about the request (method, URL, IP)
 * @var string               $additional_info Additional context information
 * @var array<string, mixed> $extra_data      Additional debugging data
 * @var string               $debug_log       Recent debug log entries
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Prepare severity class based on error code.
$severity_class = 'medium';
if ( isset( $error_code ) ) {
	if ( is_numeric( $error_code ) && $error_code >= 500 ) {
		$severity_class = 'high';
	} elseif ( is_numeric( $error_code ) && $error_code >= 400 ) {
		$severity_class = 'medium';
	} elseif ( stripos( $error_message, 'fatal' ) !== false || stripos( $error_message, 'critical' ) !== false ) {
		$severity_class = 'high';
	} elseif ( stripos( $error_message, 'warning' ) !== false ) {
		$severity_class = 'medium';
	} elseif ( stripos( $error_message, 'notice' ) !== false ) {
		$severity_class = 'low';
	}
}

// Format the timestamp to be more readable
$formatted_timestamp = isset( $timestamp ) ? wp_date( 'Y-m-d H:i:s e', (int) strtotime( $timestamp ) ) : wp_date( 'Y-m-d H:i:s e' );

// Get error type and category
$error_type = 'Unknown Error';
if ( isset( $error_file ) ) {
	if ( strpos( $error_file, 'includes/Core' ) !== false ) {
		$error_type = 'Core Component Error';
	} elseif ( strpos( $error_file, 'includes/Modules' ) !== false ) {
		$error_type = 'Module Error';
	} elseif ( strpos( $error_file, 'includes/Builder' ) !== false ) {
		$error_type = 'Divi Builder Integration Error';
	} elseif ( strpos( $error_file, 'includes/Settings' ) !== false ) {
		$error_type = 'Settings Error';
	} elseif ( strpos( $error_file, 'includes/Utils' ) !== false ) {
		$error_type = 'Utility Error';
	} elseif ( strpos( $error_file, 'includes/Utils/Divi.php' ) !== false ) {
		$error_type = 'Divi Detection Error';
	}
}

// Get file path relative to plugin, if possible.
$relative_file_path = $error_file ?? 'Unknown file';
$plugin_path        = WP_PLUGIN_DIR . '/squad-modules-for-divi/';
if ( isset( $error_file ) && strpos( $error_file, $plugin_path ) === 0 ) {
	$relative_file_path = substr( $error_file, strlen( $plugin_path ) );
}

// Extract WordPress and PHP versions for quick reference.
$client_wp_version = $environment['wp_version'] ?? 'Unknown';
$php_version       = $environment['php_version'] ?? 'Unknown';
$plugin_version    = $environment['plugin_version'] ?? 'Unknown';
$divi_version      = $environment['divi_version'] ?? ( $extra_data['status_details']['theme_version'] ?? ( $extra_data['status_details']['plugin_version'] ?? 'Unknown' ) );

// Get current user info if available.
$user_info = '';
if ( function_exists( 'wp_get_current_user' ) ) {
	$reporter = wp_get_current_user();
	if ( $reporter instanceof WP_User && $reporter->exists() ) {
		$user_info = sprintf(
			'User: %s (ID: %d, Email: %s, Role: %s)',
			$reporter->user_login,
			$reporter->ID,
			$reporter->user_email,
			implode( ', ', $reporter->roles )
		);
	}
}

// Generate a unique error reference ID for tracking
$error_reference = substr( md5( $site_url . $error_file . $error_line . $timestamp ), 0, 8 );

// Prepare Divi theme information
$divi_theme_info = array(
	'version'          => $divi_version,
	'mode'             => $environment['divi_mode'] ?? 'Unknown',
	'theme_name'       => $environment['active_theme_name'] ?? 'Unknown',
	'is_child_theme'   => $environment['is_child_theme'] ?? 'Unknown',
	'parent_theme'     => $environment['parent_theme_name'] ?? 'N/A',
	'detection_method' => $environment['divi_detection_method'] ?? 'Unknown',
);
?>
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta charset="<?php echo esc_attr( $charset ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo esc_html( sprintf( '[%s] Error Report: %s', $severity_class, substr( $error_message, 0, 50 ) . ( strlen( $error_message ) > 50 ? '...' : '' ) ) ); ?></title>
	<style type="text/css">
		body {
			font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
			font-size: 14px;
			line-height: 1.6;
			color: #333;
			margin: 0;
			padding: 0;
			-webkit-text-size-adjust: 100%;
			-ms-text-size-adjust: 100%;
			background-color: #f5f5f5;
		}

		.container {
			max-width: 900px;
			margin: 20px auto;
			padding: 0;
			background-color: #ffffff;
			box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
			border-radius: 8px;
			overflow: hidden;
		}

		.header {
			background-color: #2271b1;
			color: #ffffff;
			padding: 20px 30px;
		}

		.content {
			padding: 30px;
		}

		.section {
			margin-bottom: 30px;
			border-bottom: 1px solid #eee;
			padding-bottom: 20px;
		}

		.section:last-child {
			border-bottom: none;
		}

		h1 {
			color: #ffffff;
			font-size: 24px;
			font-weight: 600;
			margin: 0;
		}

		h2 {
			color: #2271b1;
			font-size: 18px;
			font-weight: 600;
			margin-top: 0;
			margin-bottom: 15px;
			padding-bottom: 5px;
			border-bottom: 1px solid #e5e5e5;
		}

		h3 {
			color: #23282d;
			font-size: 16px;
			font-weight: 600;
			margin: 20px 0 10px;
		}

		table {
			width: 100%;
			border-collapse: collapse;
			margin-bottom: 20px;
			box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
		}

		table, th, td {
			border: 1px solid #e5e5e5;
		}

		th, td {
			padding: 12px 15px;
			text-align: left;
			vertical-align: top;
		}

		th {
			background-color: #f8f9fa;
			font-weight: 600;
			border-bottom-width: 2px;
		}

		tr:nth-child(even) {
			background-color: #f9f9f9;
		}

		tr:hover {
			background-color: #f3f3f3;
		}

		code, pre {
			font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
			font-size: 13px;
			background-color: #f8f9fa;
			border: 1px solid #eee;
			border-radius: 3px;
		}

		code {
			padding: 2px 5px;
			color: #d63384;
		}

		pre {
			padding: 15px;
			margin: 15px 0;
			white-space: pre-wrap;
			word-wrap: break-word;
			max-height: 350px;
			overflow-y: auto;
		}

		.severity-high {
			background-color: #d9534f;
		}

		.severity-high .error-reference {
			color: #d9534f;
		}

		.severity-medium {
			background-color: #f0ad4e;
		}

		.severity-medium .error-reference {
			color: #f0ad4e;
		}

		.severity-low {
			background-color: #5bc0de;
		}

		.severity-low .error-reference {
			color: #5bc0de;
		}

		.error-box {
			background-color: #f8f9fa;
			padding: 20px;
			margin: 20px 0;
			border-left: 4px solid;
			border-radius: 4px;
		}

		.error-box.high {
			border-color: #d9534f;
			background-color: #fdf7f7;
		}

		.error-box.medium {
			border-color: #f0ad4e;
			background-color: #fef9f3;
		}

		.error-box.low {
			border-color: #5bc0de;
			background-color: #f5fbfe;
		}

		.footer {
			margin-top: 30px;
			padding: 20px 30px;
			background-color: #f8f9fa;
			color: #6c757d;
			font-size: 13px;
			text-align: center;
			border-top: 1px solid #eee;
		}

		.metadata {
			background-color: #f8f9fa;
			padding: 15px;
			border-radius: 4px;
			margin-bottom: 20px;
			border: 1px solid #eee;
		}

		.metadata-item {
			display: inline-block;
			margin-right: 20px;
			margin-bottom: 10px;
		}

		.metadata-label {
			font-weight: 600;
			color: #495057;
		}

		.metadata-version {
			padding: 3px 8px;
			border-radius: 12px;
			background-color: #e9ecef;
			font-size: 12px;
			margin-left: 5px;
		}

		.stack-container {
			margin: 20px 0;
			border: 1px solid #e5e5e5;
			border-radius: 5px;
			overflow: hidden;
		}

		.stack-header {
			background-color: #f1f1f1;
			padding: 10px 15px;
			font-weight: 600;
			border-bottom: 1px solid #e5e5e5;
		}

		.stack-content {
			padding: 15px;
			background-color: #f8f9fa;
			max-height: 350px;
			overflow-y: auto;
			white-space: pre-wrap;
			word-wrap: break-word;
			font-family: monospace;
			font-size: 12px;
		}

		.env-item {
			margin-bottom: 10px;
			padding-bottom: 10px;
			border-bottom: 1px dashed #eee;
		}

		.env-item:last-child {
			border-bottom: none;
			margin-bottom: 0;
			padding-bottom: 0;
		}

		.env-label {
			font-weight: 600;
			color: #495057;
			display: inline-block;
			min-width: 180px;
		}

		.error-reference {
			display: inline-block;
			padding: 3px 10px;
			background-color: #e9ecef;
			border-radius: 12px;
			font-family: monospace;
			margin-left: 10px;
			font-size: 12px;
		}

		.error-location {
			margin-top: 10px;
			font-family: monospace;
			padding: 10px;
			background-color: #f8f9fa;
			border-radius: 4px;
			border: 1px solid #eee;
		}

		.file-path {
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
			max-width: 100%;
			display: inline-block;
		}

		.error-timestamp {
			color: #6c757d;
			font-size: 13px;
			margin-top: 5px;
		}

		.quick-stats {
			display: flex;
			flex-wrap: wrap;
			margin: 0 -10px 20px;
		}

		.stat-box {
			flex: 1 1 200px;
			margin: 10px;
			padding: 15px;
			border-radius: 5px;
			background-color: #f8f9fa;
			border: 1px solid #eee;
		}

		.stat-title {
			font-size: 13px;
			font-weight: 600;
			margin-bottom: 5px;
			color: #495057;
		}

		.stat-value {
			font-size: 16px;
			font-weight: bold;
			color: #2271b1;
		}

		.tools-section {
			background-color: #f0f6fc;
			padding: 15px;
			border-radius: 5px;
			margin-top: 20px;
		}

		.debug-tips {
			margin-top: 10px;
		}

		.debug-tips ul {
			margin-left: 20px;
			padding-left: 0;
		}

		.debug-tips li {
			margin-bottom: 5px;
		}

		.no-js-accordion {
			margin-bottom: 15px;
		}

		.no-js-accordion summary {
			padding: 10px 15px;
			background-color: #f8f9fa;
			border: 1px solid #e5e5e5;
			border-radius: 5px;
			font-weight: 600;
			cursor: pointer;
			position: relative;
			outline: none;
		}

		.no-js-accordion summary::-webkit-details-marker {
			display: none;
		}

		.no-js-accordion summary::after {
			content: "+";
			position: absolute;
			right: 15px;
			top: 50%;
			transform: translateY(-50%);
		}

		.no-js-accordion[open] summary::after {
			content: "-";
		}

		.no-js-accordion-content {
			padding: 15px;
			border: 1px solid #e5e5e5;
			border-top: none;
			border-radius: 0 0 5px 5px;
		}

		.no-js-accordion[open] summary {
			border-radius: 5px 5px 0 0;
			border-bottom: none;
		}

		.divi-section {
			background-color: #eef6fb;
			padding: 15px;
			border-radius: 5px;
			margin-top: 20px;
			border-left: 4px solid #2271b1;
		}

		.divi-info-grid {
			display: grid;
			grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
			gap: 15px;
			margin-top: 15px;
		}

		.divi-info-item {
			background-color: white;
			border-radius: 4px;
			padding: 12px;
			box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
		}

		.divi-info-label {
			font-size: 12px;
			color: #555;
			margin-bottom: 3px;
			font-weight: 600;
		}

		.divi-info-value {
			font-size: 14px;
			font-weight: 500;
		}

		.divi-detection-method {
			margin-top: 15px;
			padding: 10px;
			background-color: #f0f6fc;
			border-radius: 4px;
			font-family: monospace;
			font-size: 13px;
		}

		.badge {
			display: inline-block;
			padding: 3px 8px;
			border-radius: 12px;
			font-size: 11px;
			font-weight: 600;
			text-transform: uppercase;
			margin-left: 5px;
		}

		.badge-theme {
			background-color: #e1f5fe;
			color: #0288d1;
		}

		.badge-plugin {
			background-color: #e8f5e9;
			color: #388e3c;
		}

		.badge-custom {
			background-color: #fff3e0;
			color: #f57c00;
		}

		.badge-child {
			background-color: #f3e5f5;
			color: #8e24aa;
		}
	</style>
</head>
<body>
<div class="container">
	<div class="header severity-<?php echo esc_attr( $severity_class ); ?>">
		<h1>
			<?php esc_html_e( 'Error Report', 'squad-modules-for-divi' ); ?>
			<span class="error-reference">#<?php echo esc_html( $error_reference ); ?></span>
		</h1>
	</div>

	<div class="content">
		<!-- Quick Stats Section -->
		<div class="quick-stats">
			<div class="stat-box">
				<div class="stat-title"><?php esc_html_e( 'Error Type', 'squad-modules-for-divi' ); ?></div>
				<div class="stat-value"><?php echo esc_html( $error_type ); ?></div>
			</div>
			<div class="stat-box">
				<div class="stat-title"><?php esc_html_e( 'WP Version', 'squad-modules-for-divi' ); ?></div>
				<div class="stat-value"><?php echo esc_html( $client_wp_version ); ?></div>
			</div>
			<div class="stat-box">
				<div class="stat-title"><?php esc_html_e( 'PHP Version', 'squad-modules-for-divi' ); ?></div>
				<div class="stat-value"><?php echo esc_html( $php_version ); ?></div>
			</div>
			<div class="stat-box">
				<div class="stat-title"><?php esc_html_e( 'Plugin Version', 'squad-modules-for-divi' ); ?></div>
				<div class="stat-value"><?php echo esc_html( $plugin_version ); ?></div>
			</div>
			<div class="stat-box">
				<div class="stat-title"><?php esc_html_e( 'Divi Version', 'squad-modules-for-divi' ); ?></div>
				<div class="stat-value"><?php echo esc_html( $divi_version ); ?></div>
			</div>
		</div>

		<!-- Main Error Details Section -->
		<div class="section">
			<div class="error-box <?php echo esc_attr( $severity_class ); ?>">
				<h2><?php esc_html_e( 'Error Details', 'squad-modules-for-divi' ); ?></h2>
				<p><strong><?php esc_html_e( 'Message:', 'squad-modules-for-divi' ); ?></strong> <?php echo esc_html( $error_message ); ?></p>
				<p><strong><?php esc_html_e( 'Code:', 'squad-modules-for-divi' ); ?></strong> <?php echo esc_html( $error_code ); ?></p>

				<div class="error-location">
					<div><strong><?php esc_html_e( 'File:', 'squad-modules-for-divi' ); ?></strong> <span class="file-path"><?php echo esc_html( $relative_file_path ); ?></span></div>
					<div><strong><?php esc_html_e( 'Line:', 'squad-modules-for-divi' ); ?></strong> <?php echo esc_html( (string) $error_line ); ?></div>
				</div>

				<div class="error-timestamp">
					<strong><?php esc_html_e( 'Time:', 'squad-modules-for-divi' ); ?></strong> <?php echo esc_html( (string) $formatted_timestamp ); ?>
				</div>

				<?php if ( '' !== $user_info ) : ?>
					<p><strong><?php esc_html_e( 'User Context:', 'squad-modules-for-divi' ); ?></strong> <?php echo esc_html( $user_info ); ?></p>
				<?php endif; ?>
			</div>
		</div>

		<!-- Divi Environment Section -->
		<div class="section divi-section">
			<h2><?php esc_html_e( 'Divi Environment', 'squad-modules-for-divi' ); ?></h2>

			<div class="divi-info-grid">
				<div class="divi-info-item">
					<div class="divi-info-label"><?php esc_html_e( 'Theme Name', 'squad-modules-for-divi' ); ?></div>
					<div class="divi-info-value">
						<?php echo esc_html( $divi_theme_info['theme_name'] ); ?>
						<?php
						// Show badge based on theme type
						$badge_class = 'badge-theme';
						$badge_text  = 'Stock';

						if ( 'Yes' === $divi_theme_info['is_child_theme'] || true === $divi_theme_info['is_child_theme'] ) {
							$badge_class = 'badge-child';
							$badge_text  = 'Child';
						} elseif ( 'Divi' !== $divi_theme_info['theme_name'] && 'Extra' !== $divi_theme_info['theme_name'] ) {
							$badge_class = 'badge-custom';
							$badge_text  = 'Custom';
						}
						?>
						<span class="badge <?php echo esc_attr( $badge_class ); ?>"><?php echo esc_html( $badge_text ); ?></span>
					</div>
				</div>

				<div class="divi-info-item">
					<div class="divi-info-label"><?php esc_html_e( 'Divi Version', 'squad-modules-for-divi' ); ?></div>
					<div class="divi-info-value"><?php echo esc_html( $divi_version ); ?></div>
				</div>

				<div class="divi-info-item">
					<div class="divi-info-label"><?php esc_html_e( 'Implementation Mode', 'squad-modules-for-divi' ); ?></div>
					<div class="divi-info-value">
						<?php echo esc_html( ucfirst( $divi_theme_info['mode'] ) ); ?>
						<span class="badge <?php echo esc_attr( 'theme' === $divi_theme_info['mode'] ? 'badge-theme' : 'badge-plugin' ); ?>">
							<?php echo esc_html( 'theme' === $divi_theme_info['mode'] ? 'Theme' : 'Plugin' ); ?>
						</span>
					</div>
				</div>

				<?php if ( 'Yes' === $divi_theme_info['is_child_theme'] || true === $divi_theme_info['is_child_theme'] ) : ?>
					<div class="divi-info-item">
						<div class="divi-info-label"><?php esc_html_e( 'Parent Theme', 'squad-modules-for-divi' ); ?></div>
						<div class="divi-info-value"><?php echo esc_html( $divi_theme_info['parent_theme'] ); ?></div>
					</div>
				<?php endif; ?>

				<?php if ( isset( $environment['divi_modified'] ) && $environment['divi_modified'] ) : ?>
					<div class="divi-info-item">
						<div class="divi-info-label"><?php esc_html_e( 'Modification Status', 'squad-modules-for-divi' ); ?></div>
						<div class="divi-info-value">
							<?php esc_html_e( 'Modified/Customized', 'squad-modules-for-divi' ); ?>
							<span class="badge badge-custom"><?php esc_html_e( 'Custom', 'squad-modules-for-divi' ); ?></span>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $environment['divi_constants'] ) ) : ?>
					<div class="divi-info-item">
						<div class="divi-info-label"><?php esc_html_e( 'Detected Constants', 'squad-modules-for-divi' ); ?></div>
						<div class="divi-info-value"><?php echo esc_html( implode( ', ', (array) $environment['divi_constants'] ) ); ?></div>
					</div>
				<?php endif; ?>
			</div>

			<?php if ( isset( $divi_theme_info['detection_method'] ) && 'Unknown' !== $divi_theme_info['detection_method'] ) : ?>
				<div class="divi-detection-method">
					<strong><?php esc_html_e( 'Detection Method:', 'squad-modules-for-divi' ); ?></strong> <?php echo esc_html( $divi_theme_info['detection_method'] ); ?>
				</div>
			<?php endif; ?>
		</div>

		<!-- Site Information Section -->
		<div class="section">
			<h2><?php esc_html_e( 'Site Information', 'squad-modules-for-divi' ); ?></h2>
			<table>
				<tr>
					<th width="30%"><?php esc_html_e( 'Property', 'squad-modules-for-divi' ); ?></th>
					<th><?php esc_html_e( 'Value', 'squad-modules-for-divi' ); ?></th>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Site URL', 'squad-modules-for-divi' ); ?></td>
					<td><a href="<?php echo esc_url( $site_url ); ?>"><?php echo esc_html( $site_url ); ?></a></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Site Name', 'squad-modules-for-divi' ); ?></td>
					<td><?php echo esc_html( $site_name ); ?></td>
				</tr>
				<?php
				if ( isset( $request_data ) && is_array( $request_data ) ) :
					foreach ( $request_data as $key => $value ) :
						?>
						<tr>
							<td><?php echo esc_html( ucfirst( $key ) ); ?></td>
							<td><?php echo esc_html( $value ); ?></td>
						</tr>
						<?php
					endforeach;
				endif;
				?>
				<?php if ( '' !== $additional_info ) : ?>
					<tr>
						<td><?php esc_html_e( 'Additional Context', 'squad-modules-for-divi' ); ?></td>
						<td><?php echo esc_html( $additional_info ); ?></td>
					</tr>
				<?php endif; ?>
			</table>
		</div>

		<!-- Environment Section -->
		<?php if ( isset( $environment ) && is_array( $environment ) ) : ?>
			<div class="section">
				<h2><?php esc_html_e( 'Environment', 'squad-modules-for-divi' ); ?></h2>
				<div class="env-details">
					<?php
					// Sort environment variables alphabetically for easier scanning
					ksort( $environment );

					foreach ( $environment as $key => $value ) :
						// Skip the ones already shown in the quick stats or Divi section
						if ( in_array( $key, array( 'wp_version', 'php_version', 'plugin_version', 'divi_version', 'divi_mode', 'active_theme_name', 'is_child_theme', 'parent_theme_name', 'divi_detection_method', 'divi_constants', 'divi_modified' ), true ) ) {
							continue;
						}
						?>
						<div class="env-item">
							<span class="env-label"><?php echo esc_html( ucwords( str_replace( '_', ' ', $key ) ) ); ?>:</span>
							<span class="env-value"><?php echo is_array( $value ) || is_object( $value ) ? esc_html( wp_json_encode( $value ) ) : esc_html( $value ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>

		<!-- Stack Trace Section -->
		<?php if ( isset( $stack_trace ) && ! empty( $stack_trace ) ) : ?>
			<div class="section">
				<h2><?php esc_html_e( 'Stack Trace', 'squad-modules-for-divi' ); ?></h2>
				<details class="no-js-accordion">
					<summary><?php esc_html_e( 'Stack Trace Details', 'squad-modules-for-divi' ); ?></summary>
					<div class="no-js-accordion-content">
						<pre><?php echo esc_html( $stack_trace ); ?></pre>
					</div>
				</details>
			</div>
		<?php endif; ?>

		<!-- Extra Data Section -->
		<?php if ( isset( $extra_data ) && is_array( $extra_data ) && ! empty( $extra_data ) ) : ?>
			<div class="section">
				<h2><?php esc_html_e( 'Extra Data', 'squad-modules-for-divi' ); ?></h2>

				<?php foreach ( $extra_data as $key => $value ) : ?>
					<details class="no-js-accordion">
						<summary><?php echo esc_html( ucfirst( str_replace( '_', ' ', $key ) ) ); ?></summary>
						<div class="no-js-accordion-content">
							<?php if ( is_array( $value ) || is_object( $value ) ) : ?>
								<pre><?php echo esc_html( wp_json_encode( $value, JSON_PRETTY_PRINT ) ); ?></pre>
							<?php else : ?>
								<p><?php echo esc_html( $value ); ?></p>
							<?php endif; ?>
						</div>
					</details>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<!-- Debug Log Section -->
		<?php if ( ! empty( $debug_log ) ) : ?>
			<div class="section">
				<h2><?php esc_html_e( 'Debug Log', 'squad-modules-for-divi' ); ?></h2>
				<details class="no-js-accordion">
					<summary><?php esc_html_e( 'Recent Debug Log Entries', 'squad-modules-for-divi' ); ?></summary>
					<div class="no-js-accordion-content">
						<pre><?php echo esc_html( $debug_log ); ?></pre>
					</div>
				</details>
			</div>
		<?php endif; ?>

		<!-- Debugging Tips Section -->
		<div class="section tools-section">
			<h2><?php esc_html_e( 'Support Tools & Debugging Tips', 'squad-modules-for-divi' ); ?></h2>

			<div class="debug-tips">
				<h3><?php esc_html_e( 'Potential Solutions', 'squad-modules-for-divi' ); ?></h3>
				<ul>
					<li><?php esc_html_e( 'Make sure all plugins are updated to their latest versions', 'squad-modules-for-divi' ); ?></li>
					<li><?php esc_html_e( 'Check for conflicts by temporarily disabling other plugins', 'squad-modules-for-divi' ); ?></li>
					<li><?php esc_html_e( 'Verify Divi theme/builder is updated to the required version', 'squad-modules-for-divi' ); ?></li>
					<li><?php esc_html_e( 'Test with a default WordPress theme to rule out theme conflicts', 'squad-modules-for-divi' ); ?></li>
					<li><?php esc_html_e( 'Use System Status to verify all server requirements are met', 'squad-modules-for-divi' ); ?></li>
					<?php if ( strpos( $error_type, 'Divi' ) !== false ) : ?>
						<li><?php esc_html_e( 'If using a customized Divi theme, check that required Divi functions are available', 'squad-modules-for-divi' ); ?></li>
						<li><?php esc_html_e( 'Verify your theme contains all necessary Divi core files in the expected locations', 'squad-modules-for-divi' ); ?></li>
					<?php endif; ?>
				</ul>

				<h3><?php esc_html_e( 'Next Steps', 'squad-modules-for-divi' ); ?></h3>
				<ul>
					<li><?php esc_html_e( 'Reference error ID when contacting support', 'squad-modules-for-divi' ); ?></li>
					<li><?php esc_html_e( 'Check documentation for known issues with this component', 'squad-modules-for-divi' ); ?></li>
					<li><?php esc_html_e( 'Visit the support forum to see if others have experienced this issue', 'squad-modules-for-divi' ); ?></li>
				</ul>
			</div>
		</div>

		<div class="footer">
			<p>
				<?php
				printf(
				/* translators: %1$s: plugin name, %2$s: plugin version, %3$s: error reference ID */
					esc_html__( 'This error report was automatically generated by %1$s version %2$s • Reference ID: %3$s', 'squad-modules-for-divi' ),
					'<strong>Squad Modules</strong>',
					isset( $environment['plugin_version'] ) ? esc_html( $environment['plugin_version'] ) : '',
					'<code>' . esc_html( $error_reference ) . '</code>'
				);
				?>
			</p>
		</div>
	</div>
</div>
</body>
</html>
