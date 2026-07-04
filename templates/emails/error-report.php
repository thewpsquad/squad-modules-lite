<?php
/**
 * Error Report Email Template
 *
 * Enhanced template for error report emails with improved organization,
 * styling, and debugging information.
 *
 * @package DiviSquad\Managers\Emails
 * @since   3.1.7
 *
 * @var array $data {
 *     Template arguments
 *
 *     @type string $error_message  Error description
 *     @type string $error_code     Error identifier
 *     @type string $error_file     Source file
 *     @type int    $error_line     Line number
 *     @type string $stack_trace    Stack trace
 *     @type string $debug_log      Debug log excerpt
 *     @type array  $environment    Environment info
 *     @type array  $request_data   Request details
 *     @type string $site_url       Site URL
 *     @type string $site_name      Site name
 *     @type string $timestamp      Error timestamp
 * }
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo esc_attr( get_bloginfo( 'charset' ) ); ?>">
	<title><?php echo esc_html( sprintf( '[Error Report] %s', $data['site_name'] ) ); ?></title>
	<style>
		:root {
			--color-primary: #4f46e5;
			--color-error: #ef4444;
			--color-warning: #f59e0b;
			--color-success: #10b981;
			--color-text: #1f2937;
			--color-text-light: #6b7280;
			--color-border: #e5e7eb;
			--color-background: #ffffff;
			--color-background-light: #f9fafb;
			--radius-sm: 0.375rem;
			--radius-md: 0.5rem;
			--shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
			--shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
		}

		/* Reset & Base Styles */
		* {
			margin: 0;
			padding: 0;
			box-sizing: border-box;
		}

		body {
			font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
			line-height: 1.5;
			color: var(--color-text);
			background: #f3f4f6;
			padding: 2rem 1rem;
		}

		/* Layout */
		.container {
			max-width: 800px;
			margin: 0 auto;
			background: var(--color-background);
			border-radius: var(--radius-md);
			box-shadow: var(--shadow-md);
			overflow: hidden;
		}

		/* Header */
		.header {
			background: var(--color-primary);
			color: white;
			padding: 1.5rem;
			position: relative;
		}

		.header::after {
			content: '';
			position: absolute;
			bottom: 0;
			left: 0;
			right: 0;
			height: 4px;
			background: linear-gradient(to right, var(--color-primary), var(--color-error));
		}

		.site-info {
			margin-top: 0.5rem;
			font-size: 0.875rem;
			opacity: 0.9;
		}

		/* Main Content */
		.content {
			padding: 1.5rem;
		}

		/* Sections */
		.section {
			background: var(--color-background-light);
			border: 1px solid var(--color-border);
			border-radius: var(--radius-sm);
			padding: 1.25rem;
			margin-bottom: 1.5rem;
		}

		.section-header {
			display: flex;
			align-items: center;
			gap: 0.5rem;
			margin-bottom: 1rem;
			padding-bottom: 0.5rem;
			border-bottom: 1px solid var(--color-border);
		}

		.section-title {
			font-size: 1rem;
			font-weight: 600;
			color: var(--color-primary);
		}

		/* Error Details */
		.error-badge {
			display: inline-block;
			padding: 0.25rem 0.75rem;
			background: var(--color-error);
			color: white;
			border-radius: 999px;
			font-size: 0.75rem;
			font-weight: 500;
			margin-bottom: 1rem;
		}

		.error-message {
			font-size: 1.125rem;
			font-weight: 500;
			margin-bottom: 1rem;
			color: var(--color-error);
		}

		/* Info Grid */
		.info-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
			gap: 1rem;
		}

		.info-item {
			display: flex;
			flex-direction: column;
			gap: 0.25rem;
		}

		.info-label {
			font-size: 0.75rem;
			font-weight: 500;
			color: var(--color-text-light);
			text-transform: uppercase;
			letter-spacing: 0.05em;
		}

		.info-value {
			font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
			font-size: 0.875rem;
			padding: 0.5rem;
			background: white;
			border-radius: var(--radius-sm);
			border: 1px solid var(--color-border);
			word-break: break-all;
		}

		/* Code Blocks */
		pre {
			font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
			font-size: 0.875rem;
			line-height: 1.5;
			padding: 1rem;
			background: white;
			border: 1px solid var(--color-border);
			border-radius: var(--radius-sm);
			overflow-x: auto;
			margin: 0.5rem 0;
			max-height: 400px;
		}

		/* Footer */
		.footer {
			text-align: center;
			padding: 1.5rem;
			background: var(--color-background-light);
			border-top: 1px solid var(--color-border);
			font-size: 0.875rem;
			color: var(--color-text-light);
		}

		.powered-by {
			margin-top: 0.5rem;
			font-size: 0.75rem;
		}

		/* Responsive */
		@media (max-width: 640px) {
			body {
				padding: 1rem;
			}

			.header {
				padding: 1rem;
			}

			.content {
				padding: 1rem;
			}

			.info-grid {
				grid-template-columns: 1fr;
			}
		}
	</style>
</head>
<body>
<div class="container">
	<!-- Header -->
	<header class="header">
		<h1>Error Report</h1>
		<div class="site-info">
			<?php echo esc_html( $data['site_name'] ); ?> (<?php echo esc_html( $data['site_url'] ); ?>)
		</div>
	</header>

	<!-- Main Content -->
	<main class="content">
		<!-- Error Details -->
		<section class="section">
			<div class="section-header">
				<div class="section-title">Error Details</div>
				<span class="error-badge">Error <?php echo esc_html( $data['error_code'] ); ?></span>
			</div>

			<div class="error-message">
				<?php echo esc_html( $data['error_message'] ); ?>
			</div>

			<div class="info-grid">
				<div class="info-item">
					<span class="info-label">File Location</span>
					<code class="info-value"><?php echo esc_html( $data['error_file'] ); ?></code>
				</div>
				<div class="info-item">
					<span class="info-label">Line Number</span>
					<code class="info-value"><?php echo esc_html( $data['error_line'] ); ?></code>
				</div>
				<div class="info-item">
					<span class="info-label">Timestamp</span>
					<code class="info-value"><?php echo esc_html( $data['timestamp'] ); ?></code>
				</div>
			</div>
		</section>

		<!-- Environment Info -->
		<section class="section">
			<div class="section-header">
				<div class="section-title">Environment Information</div>
			</div>

			<div class="info-grid">
				<div class="info-item">
					<span class="info-label">PHP Version</span>
					<code class="info-value"><?php echo esc_html( $data['environment']['php_version'] ); ?></code>
				</div>
				<div class="info-item">
					<span class="info-label">WordPress Version</span>
					<code class="info-value"><?php echo esc_html( $data['environment']['wp_version'] ); ?></code>
				</div>
				<div class="info-item">
					<span class="info-label">Plugin Version</span>
					<code class="info-value"><?php echo esc_html( $data['environment']['plugin_version'] ); ?></code>
				</div>
				<div class="info-item">
					<span class="info-label">Active Theme</span>
					<code class="info-value"><?php echo esc_html( $data['environment']['active_theme'] ); ?></code>
				</div>
			</div>
		</section>

		<!-- Request Data -->
		<?php if ( ! empty( $data['request_data'] ) ) : ?>
			<section class="section">
				<div class="section-header">
					<div class="section-title">Request Information</div>
				</div>

				<div class="info-grid">
					<div class="info-item">
						<span class="info-label">Request Method</span>
						<code class="info-value"><?php echo esc_html( $data['request_data']['method'] ); ?></code>
					</div>
					<div class="info-item">
						<span class="info-label">Request URI</span>
						<code class="info-value"><?php echo esc_html( $data['request_data']['uri'] ); ?></code>
					</div>
					<div class="info-item">
						<span class="info-label">IP Address</span>
						<code class="info-value"><?php echo esc_html( $data['request_data']['ip'] ); ?></code>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<!-- Stack Trace -->
		<section class="section">
			<div class="section-header">
				<div class="section-title">Stack Trace</div>
			</div>
			<pre><?php echo esc_html( $data['stack_trace'] ); ?></pre>
		</section>

		<!-- Active Plugins -->
		<section class="section">
			<div class="section-header">
				<div class="section-title">Active Plugins</div>
			</div>
			<div class="info-item">
				<code class="info-value"><?php echo esc_html( $data['environment']['active_plugins'] ); ?></code>
			</div>
		</section>

		<!-- Debug Log -->
		<?php if ( ! empty( $data['debug_log'] ) ) : ?>
			<section class="section">
				<div class="section-header">
					<div class="section-title">Debug Log (Last 100 Lines)</div>
				</div>
				<pre><?php echo esc_html( $data['debug_log'] ); ?></pre>
			</section>
		<?php endif; ?>
	</main>

	<!-- Footer -->
	<footer class="footer">
		<p>This is an automated error report. Please investigate and take appropriate action.</p>
		<p>© <?php echo esc_html( wp_date( 'Y' ) ); ?> The WP Squad. All rights reserved.</p>
		<div class="powered-by">
			Powered by Squad Modules <?php echo esc_html( $data['environment']['plugin_version'] ); ?>
		</div>
	</footer>
</div>
</body>
</html>
