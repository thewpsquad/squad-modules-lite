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
 * @var array<string, mixed> $request_data    Information about the request (method, URL, IP)
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
} else {
	$error_file = '';
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
		/* Base Styles */
		body {
			font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
			font-size: 14px;
			line-height: 1.6;
			color: #1f2937;
			margin: 0;
			padding: 0;
			-webkit-text-size-adjust: 100%;
			-ms-text-size-adjust: 100%;
			background-color: #f9fafb;
		}

		.container {
			max-width: 900px;
			margin: 30px auto;
			padding: 0;
			background-color: #ffffff;
			box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
			border-radius: 12px;
			overflow: hidden;
		}

		/* Typography */
		h1 {
			font-size: 24px;
			font-weight: 700;
			margin: 0;
			letter-spacing: -0.02em;
			color: #ffffff;
		}

		h2 {
			color: #1e40af;
			font-size: 18px;
			font-weight: 600;
			margin-top: 0;
			margin-bottom: 15px;
			padding-bottom: 8px;
			border-bottom: 1px solid #e5e7eb;
			letter-spacing: -0.01em;
		}

		h3 {
			color: #374151;
			font-size: 16px;
			font-weight: 600;
			margin: 20px 0 10px;
			letter-spacing: -0.01em;
		}

		code, pre {
			font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;
			font-size: 13px;
			background-color: #f1f5f9;
			border-radius: 6px;
		}

		code {
			padding: 2px 5px;
			color: #be185d;
		}

		pre {
			padding: 16px;
			margin: 15px 0;
			white-space: pre-wrap;
			word-wrap: break-word;
			max-height: 350px;
			overflow-y: auto;
			border: 1px solid #e5e7eb;
		}

		a {
			color: #3b82f6;
			text-decoration: none;
		}

		a:hover {
			text-decoration: underline;
		}

		/* Layout Components */
		.header {
			padding: 24px 30px;
			display: flex;
			align-items: center;
			justify-content: space-between;
		}

		.content {
			padding: 30px;
		}

		.section {
			margin-bottom: 32px;
			border-radius: 8px;
			background-color: #ffffff;
			overflow: hidden;
		}

		.footer {
			margin-top: 40px;
			padding: 24px 30px;
			background-color: #f8fafc;
			color: #64748b;
			font-size: 13px;
			text-align: center;
			border-top: 1px solid #e5e7eb;
		}

		/* Header Severity Colors */
		.severity-high {
			background-color: #ef4444;
			color: #ffffff;
		}

		.severity-medium {
			background-color: #f59e0b;
			color: #ffffff;
		}

		.severity-low {
			background-color: #3b82f6;
			color: #ffffff;
		}

		/* Error Box Styles */
		.error-box {
			border-radius: 8px;
			padding: 24px;
			margin-bottom: 24px;
			box-shadow: 0 2px 5px rgba(0, 0, 0, 0.03);
			border: 1px solid;
		}

		.error-box.high {
			border-color: #fecaca;
			background-color: #fee2e2;
		}

		.error-box.medium {
			border-color: #fed7aa;
			background-color: #ffedd5;
		}

		.error-box.low {
			border-color: #bfdbfe;
			background-color: #dbeafe;
		}

		/* Location Box */
		.error-location {
			margin-top: 16px;
			font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;
			padding: 12px;
			background-color: rgba(255, 255, 255, 0.7);
			border-radius: 6px;
			border: 1px solid rgba(0, 0, 0, 0.05);
		}

		.file-path {
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
			max-width: 100%;
			display: inline-block;
			font-weight: 500;
		}

		.error-timestamp {
			color: #64748b;
			font-size: 13px;
			margin-top: 12px;
		}

		/* Quick Stats Cards */
		.quick-stats {
			display: flex;
			flex-wrap: wrap;
			margin: 0 -10px 28px;
			gap: 8px;
		}

		.stat-box {
			flex: 1 1 160px;
			margin: 8px;
			padding: 16px;
			border-radius: 8px;
			background-color: #f8fafc;
			border: 1px solid #e5e7eb;
			box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
			transition: transform 0.15s ease, box-shadow 0.15s ease;
		}

		.stat-box:hover {
			transform: translateY(-2px);
			box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
		}

		.stat-title {
			font-size: 13px;
			font-weight: 500;
			margin-bottom: 6px;
			color: #6b7280;
			text-transform: uppercase;
			letter-spacing: 0.03em;
		}

		.stat-value {
			font-size: 18px;
			font-weight: 600;
			color: #1e40af;
		}

		/* Tables */
		table {
			width: 100%;
			border-collapse: separate;
			border-spacing: 0;
			margin-bottom: 24px;
			box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
			border-radius: 8px;
			overflow: hidden;
		}

		th, td {
			padding: 14px 16px;
			text-align: left;
			vertical-align: top;
			border-bottom: 1px solid #e5e7eb;
		}

		th {
			background-color: #f8fafc;
			font-weight: 600;
			color: #4b5563;
			font-size: 13px;
			text-transform: uppercase;
			letter-spacing: 0.03em;
		}

		tr:last-child td {
			border-bottom: none;
		}

		tr:nth-child(even) {
			background-color: #f9fafb;
		}

		tr:hover {
			background-color: #f1f5f9;
		}

		/* Error Reference */
		.error-reference {
			display: inline-block;
			padding: 4px 10px;
			background-color: rgba(0, 0, 0, 0.2);
			border-radius: 6px;
			font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;
			font-size: 13px;
			margin-left: 12px;
			font-weight: 600;
			color: #ffffff;
		}

		/* Divi Section */
		.divi-section {
			background-color: #f0f9ff;
			padding: 24px;
			border-radius: 10px;
			margin-top: 24px;
			border-left: 4px solid #0ea5e9;
		}

		.divi-info-grid {
			display: grid;
			grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
			gap: 16px;
			margin-top: 16px;
		}

		.divi-info-item {
			background-color: white;
			border-radius: 8px;
			padding: 16px;
			box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
			border: 1px solid #e5e7eb;
			transition: transform 0.15s ease, box-shadow 0.15s ease;
		}

		.divi-info-item:hover {
			transform: translateY(-2px);
			box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
		}

		.divi-info-label {
			font-size: 13px;
			color: #6b7280;
			margin-bottom: 4px;
			font-weight: 500;
			text-transform: uppercase;
			letter-spacing: 0.03em;
		}

		.divi-info-value {
			font-size: 15px;
			font-weight: 500;
			color: #1f2937;
		}

		.divi-detection-method {
			margin-top: 16px;
			padding: 12px;
			background-color: #e0f2fe;
			border-radius: 6px;
			font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;
			font-size: 13px;
			color: #0c4a6e;
		}

		/* Badges */
		.badge {
			display: inline-block;
			padding: 4px 8px;
			border-radius: 6px;
			font-size: 11px;
			font-weight: 600;
			text-transform: uppercase;
			margin-left: 6px;
			letter-spacing: 0.05em;
		}

		.badge-theme {
			background-color: #dbeafe;
			color: #1e40af;
		}

		.badge-plugin {
			background-color: #dcfce7;
			color: #166534;
		}

		.badge-custom {
			background-color: #ffedd5;
			color: #9a3412;
		}

		.badge-child {
			background-color: #f3e8ff;
			color: #7e22ce;
		}

		/* Environment Details */
		.env-details {
			display: grid;
			grid-template-columns: 1fr;
			gap: 10px;
		}

		.env-item {
			padding: 12px;
			border-radius: 6px;
			background-color: #f8fafc;
			border: 1px solid #e5e7eb;
		}

		.env-label {
			font-weight: 600;
			color: #4b5563;
			display: block;
			margin-bottom: 4px;
			font-size: 13px;
		}

		.env-value {
			color: #1f2937;
			font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;
			font-size: 13px;
			word-break: break-word;
		}

		/* Accordions (Email Compatible) */
		.no-js-accordion {
			margin-bottom: 16px;
			border-radius: 8px;
			overflow: hidden;
			border: 1px solid #e5e7eb;
		}

		.no-js-accordion summary {
			padding: 14px 20px;
			background-color: #f8fafc;
			font-weight: 600;
			cursor: pointer;
			position: relative;
			outline: none;
			color: #1e40af;
			border-bottom: 1px solid #e5e7eb;
			user-select: none;
		}

		.no-js-accordion summary::-webkit-details-marker {
			display: none;
		}

		.no-js-accordion summary::after {
			content: '+';
			position: absolute;
			right: 20px;
			top: 50%;
			transform: translateY(-50%);
			font-size: 20px;
			color: #6b7280;
		}

		.no-js-accordion[open] summary::after {
			content: '-';
		}

		.no-js-accordion-content {
			padding: 16px 20px;
		}

		.no-js-accordion[open] summary {
			border-radius: 5px 5px 0 0;
			border-bottom: 1px solid #e5e7eb;
		}

		/* Debug Tips Section */
		.tools-section {
			background-color: #f0f9ff;
			padding: 24px;
			border-radius: 10px;
			margin-top: 20px;
			border-left: 4px solid #0ea5e9;
		}

		.debug-tips ul {
			margin-left: 20px;
			padding-left: 0;
			list-style: none;
		}

		.debug-tips li {
			margin-bottom: 10px;
			position: relative;
			padding-left: 24px;
			list-style: none;
		}

		.debug-tips li::before {
			content: '→';
			position: absolute;
			left: 0;
			color: #3b82f6;
			font-weight: bold;
		}

		/* Responsive Adjustments */
		@media (max-width: 768px) {
			.container {
				margin: 10px;
				border-radius: 8px;
			}

			.header, .content, .footer {
				padding: 20px;
			}

			.divi-info-grid, .env-details {
				grid-template-columns: 1fr;
			}

			.quick-stats {
				flex-direction: column;
			}

			.stat-box {
				flex: 1 1 auto;
			}
		}
	</style>
</head>
<body>
<div class="container">
	<div class="header severity-<?php echo esc_attr( $severity_class ); ?>">
		<h1>
			Error Report
			<span class="error-reference">#<?php echo esc_html( $error_reference ); ?></span>
		</h1>
	</div>

	<div class="content">
		<!-- Quick Stats Section -->
		<div class="quick-stats">
			<div class="stat-box">
				<div class="stat-title">Error Type</div>
				<div class="stat-value"><?php echo esc_html( $error_type ); ?></div>
			</div>
			<div class="stat-box">
				<div class="stat-title">WP Version</div>
				<div class="stat-value"><?php echo esc_html( $client_wp_version ); ?></div>
			</div>
			<div class="stat-box">
				<div class="stat-title">PHP Version</div>
				<div class="stat-value"><?php echo esc_html( $php_version ); ?></div>
			</div>
			<div class="stat-box">
				<div class="stat-title">Plugin Version</div>
				<div class="stat-value"><?php echo esc_html( $plugin_version ); ?></div>
			</div>
			<div class="stat-box">
				<div class="stat-title">Divi Version</div>
				<div class="stat-value"><?php echo esc_html( $divi_version ); ?></div>
			</div>
		</div>

		<!-- Main Error Details Section -->
		<div class="section">
			<div class="error-box <?php echo esc_attr( $severity_class ); ?>">
				<h2>Error Details</h2>
				<p><strong>Message:</strong> <?php echo esc_html( $error_message ); ?></p>
				<p><strong>Code:</strong> <?php echo esc_html( $error_code ); ?></p>

				<div class="error-location">
					<div><strong>File:</strong> <span class="file-path"><?php echo esc_html( $relative_file_path ); ?></span></div>
					<div><strong>Line:</strong> <?php echo esc_html( (string) $error_line ); ?></div>
				</div>

				<div class="error-timestamp">
					<strong>Time:</strong> <?php echo esc_html( (string) $formatted_timestamp ); ?>
				</div>

				<?php if ( '' !== $user_info ) : ?>
					<p><strong>User Context:</strong> <?php echo esc_html( $user_info ); ?></p>
				<?php endif; ?>
			</div>
		</div>

		<!-- Divi Environment Section -->
		<div class="section divi-section">
			<h2>Platform Environment</h2>

			<div class="divi-info-grid">
				<div class="divi-info-item">
					<div class="divi-info-label">Theme Name</div>
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
					<div class="divi-info-label">Divi Version</div>
					<div class="divi-info-value"><?php echo esc_html( $divi_version ); ?></div>
				</div>

				<div class="divi-info-item">
					<div class="divi-info-label">Implementation Mode</div>
					<div class="divi-info-value">
						<?php echo esc_html( ucfirst( $divi_theme_info['mode'] ) ); ?>
						<span class="badge <?php echo esc_attr( 'theme' === $divi_theme_info['mode'] ? 'badge-theme' : 'badge-plugin' ); ?>">
							<?php echo 'theme' === $divi_theme_info['mode'] ? 'Theme' : 'Plugin'; ?>
						</span>
					</div>
				</div>

				<?php if ( 'Yes' === $divi_theme_info['is_child_theme'] || true === $divi_theme_info['is_child_theme'] ) : ?>
					<div class="divi-info-item">
						<div class="divi-info-label">Parent Theme</div>
						<div class="divi-info-value"><?php echo esc_html( $divi_theme_info['parent_theme'] ); ?></div>
					</div>
				<?php endif; ?>

				<?php if ( isset( $environment['divi_modified'] ) && $environment['divi_modified'] ) : ?>
					<div class="divi-info-item">
						<div class="divi-info-label">Modification Status</div>
						<div class="divi-info-value">
							Modified/Customized
							<span class="badge badge-custom">Custom</span>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( isset( $environment['divi_constants'] ) ) : ?>
					<div class="divi-info-item">
						<div class="divi-info-label">Detected Constants</div>
						<div class="divi-info-value"><?php echo esc_html( implode( ', ', (array) $environment['divi_constants'] ) ); ?></div>
					</div>
				<?php endif; ?>
			</div>

			<?php if ( isset( $divi_theme_info['detection_method'] ) && 'Unknown' !== $divi_theme_info['detection_method'] ) : ?>
				<div class="divi-detection-method">
					<strong>Detection Method:</strong> <?php echo esc_html( $divi_theme_info['detection_method'] ); ?>
				</div>
			<?php endif; ?>
		</div>

		<!-- Site Information Section -->
		<div class="section">
			<h2>Site Information</h2>
			<table>
				<tr>
					<th width="30%">Property</th>
					<th><?php echo esc_html( 'Value' ); ?></th>
				</tr>
				<tr>
					<td>Site URL</td>
					<td><a href="<?php echo esc_url( $site_url ); ?>"><?php echo esc_html( $site_url ); ?></a></td>
				</tr>
				<tr>
					<td>Site Name</td>
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
						<td>Additional Context</td>
						<td><?php echo esc_html( $additional_info ); ?></td>
					</tr>
				<?php endif; ?>
			</table>
		</div>

		<!-- Environment Section -->
		<?php if ( isset( $environment ) && is_array( $environment ) ) : ?>
			<div class="section">
				<h2>Environment</h2>
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
		<?php if ( '' !== $stack_trace ) : ?>
			<div class="section">
				<h2>Stack Trace</h2>
				<details class="no-js-accordion">
					<summary>Stack Trace Details</summary>
					<div class="no-js-accordion-content">
						<pre><?php echo esc_html( $stack_trace ); ?></pre>
					</div>
				</details>
			</div>
		<?php endif; ?>

		<!-- Extra Data Section -->
		<?php if ( is_array( $extra_data ) && count( $extra_data ) > 0 ) : ?>
			<div class="section">
				<h2>Extra Data</h2>

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
		<?php if ( '' !== $debug_log ) : ?>
			<div class="section">
				<h2>Debug Log</h2>
				<details class="no-js-accordion">
					<summary>Recent Debug Log Entries</summary>
					<div class="no-js-accordion-content">
						<pre><?php echo esc_html( $debug_log ); ?></pre>
					</div>
				</details>
			</div>
		<?php endif; ?>

		<!-- Debugging Tips Section -->
		<div class="section tools-section">
			<h2>Support Tools & Debugging Tips</h2>

			<div class="debug-tips">
				<h3>Potential Solutions</h3>
				<ul>
					<li>Make sure all plugins are updated to their latest versions</li>
					<li>Check for conflicts by temporarily disabling other plugins</li>
					<li>Verify Divi theme/builder is updated to the required version</li>
					<li>Test with a default WordPress theme to rule out theme conflicts</li>
					<li>Use System Status to verify all server requirements are met</li>
					<?php if ( strpos( $error_type, 'Divi' ) !== false ) : ?>
						<li>If using a customized Divi theme, check that required Divi functions are available</li>
						<li>Verify your theme contains all necessary Divi core files in the expected locations</li>
					<?php endif; ?>
				</ul>

				<h3>Next Steps</h3>
				<ul>
					<li>Reference error ID when contacting support</li>
					<li>Check documentation for known issues with this component</li>
					<li>Visit the support forum to see if others have experienced this issue</li>
				</ul>
			</div>
		</div>

		<div class="footer">
			<p>
				<?php
				printf(
				/* translators: %1$s: plugin name, %2$s: plugin version, %3$s: error reference ID */
					esc_html__( 'This error report was automatically generated by %1$s version %2$s • Reference ID: %3$s' ),
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
