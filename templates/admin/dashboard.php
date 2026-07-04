<?php
/**
 * Template file to manage layouts.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}
?>

<main id="squad-modules-app" class="squad-components">
	<style id='squad-modules-app-loader-css'>
		/* ── Reset wp-admin interference ──────────────────────────── */
		body #wpwrap #wpcontent { padding-left: 0; }
		body #wpwrap #wpbody-content { padding-bottom: 0; }

		#squad-modules-app.squad-components {
			position: relative;
			display: block;
			background-color: #EEF3FE;
			width: 100%;
			max-width: 2560px;
			min-height: 85vmin;
			font-size: 16px;
			color: #1E1733;
			font-family: "Plus Jakarta Sans", Inter, -apple-system, system-ui, sans-serif;
			box-sizing: border-box;
		}
		#squad-modules-app.squad-components *,
		#squad-modules-app.squad-components *::before,
		#squad-modules-app.squad-components *::after { box-sizing: border-box; }

		/* ── Shimmer ───────────────────────────────────────────────── */
		@keyframes sq-shimmer {
			0%   { background-position: -400px 0; }
			100% { background-position: 400px 0; }
		}
		.sq-sk {
			background: linear-gradient(90deg, #E3E7F3 25%, #F3F5FB 50%, #E3E7F3 75%);
			background-size: 800px 100%;
			animation: sq-shimmer 1.6s infinite linear;
			border-radius: 6px;
			display: block;
			flex-shrink: 0;
		}

		/* ── App bar ───────────────────────────────────────────────── */
		.sq-appbar {
			display: flex;
			align-items: center;
			justify-content: space-between;
			padding: 12px 28px;
			border-bottom: 1px solid #E3E7F3;
			background: rgba(238,243,254,0.82);
			backdrop-filter: blur(8px);
		}
		.sq-appbar-left { display: flex; align-items: center; gap: 13px; }
		.sq-brand-icon {
			width: 48px; height: 48px;
			border-radius: 14px;
			border: 1px solid #E3E7F3;
			background: #fff;
			flex-shrink: 0;
		}
		.sq-brand-text { display: flex; flex-direction: column; gap: 6px; }
		.sq-appbar-right { display: flex; align-items: center; gap: 10px; }

		/* ── Nav ───────────────────────────────────────────────────── */
		.sq-nav {
			display: flex;
			gap: 3px;
			padding: 0 28px;
			border-bottom: 1px solid #E3E7F3;
			background: #EEF3FE;
		}
		.sq-nav-item {
			display: flex;
			align-items: center;
			gap: 8px;
			padding: 14px 16px;
		}
		.sq-nav-item:first-child .sq-nav-label { width: 72px; }
		.sq-nav-item:nth-child(2) .sq-nav-label { width: 58px; }
		.sq-nav-item:nth-child(3) .sq-nav-label { width: 76px; }
		.sq-nav-item:nth-child(4) .sq-nav-label { width: 70px; }

		/* ── Main ──────────────────────────────────────────────────── */
		.sq-main { padding: 24px 28px 40px; }

		/* ── Hero ──────────────────────────────────────────────────── */
		.sq-hero {
			display: flex;
			align-items: center;
			gap: 28px;
			border-radius: 18px;
			border: 1px solid #E3E7F3;
			background: #fff;
			padding: 30px 32px;
			margin-bottom: 18px;
			overflow: hidden;
		}
		.sq-hero-body { display: flex; flex-direction: column; gap: 10px; max-width: 600px; flex: 1; }
		.sq-hero-btns { display: flex; gap: 10px; margin-top: 8px; }

		/* ── Stats ─────────────────────────────────────────────────── */
		.sq-stats {
			display: grid;
			grid-template-columns: repeat(4, 1fr);
			gap: 16px;
			margin-bottom: 18px;
		}
		.sq-stat-card {
			background: #fff;
			border: 1px solid #E3E7F3;
			border-radius: 10px;
			padding: 19px 19px 17px;
			box-shadow: 0 1px 1px rgba(30,23,51,.03);
			display: flex;
			flex-direction: column;
			gap: 0;
		}
		.sq-stat-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }

		/* ── Bottom grid ───────────────────────────────────────────── */
		.sq-bottom {
			display: grid;
			grid-template-columns: 1.55fr 1fr;
			gap: 18px;
		}
		.sq-card {
			background: #fff;
			border: 1px solid #E3E7F3;
			border-radius: 10px;
			padding: 24px;
			box-shadow: 0 1px 1px rgba(30,23,51,.03);
		}
		.sq-right-col { display: flex; flex-direction: column; gap: 18px; }
		.sq-pro-card {
			background: #F2F0FB;
			border: 1px solid #D6D1EC;
			border-radius: 18px;
			padding: 24px;
		}
		.sq-section-title { display: flex; flex-direction: column; gap: 8px; margin-bottom: 16px; }
		.sq-qa-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
		.sq-qa-item {
			display: flex; align-items: flex-start; gap: 13px;
			padding: 15px;
			border: 1px solid #E3E7F3; border-radius: 14px;
			background: #F5F3FC;
		}
		.sq-changelog-items { display: flex; flex-direction: column; }
		.sq-cl-item { display: flex; gap: 13px; padding: 12px 0; border-top: 1px solid #E3E7F3; }
		.sq-cl-item:first-child { border-top: none; }

		/* ── Responsive ────────────────────────────────────────────── */
		@media (max-width: 960px) {
			.sq-stats { grid-template-columns: 1fr 1fr; }
			.sq-bottom { grid-template-columns: 1fr; }
		}
		@media (max-width: 600px) {
			.sq-stats { grid-template-columns: 1fr; }
			.sq-hero-btns { flex-wrap: wrap; }
		}
	</style>

	<!-- ── App bar skeleton ─────────────────────────────────────── -->
	<header class="sq-appbar">
		<div class="sq-appbar-left">
			<div class="sq-brand-icon sq-sk"></div>
			<div class="sq-brand-text">
				<span class="sq-sk" style="width:140px;height:16px;border-radius:5px"></span>
				<span class="sq-sk" style="width:90px;height:11px;border-radius:4px"></span>
			</div>
			<span class="sq-sk" style="width:80px;height:28px;border-radius:999px;display:none" id="sq-lic-chip"></span>
		</div>
		<div class="sq-appbar-right">
			<span class="sq-sk" style="width:200px;height:38px;border-radius:10px"></span>
			<span class="sq-sk" style="width:42px;height:42px;border-radius:11px"></span>
			<span class="sq-sk" style="width:42px;height:42px;border-radius:11px"></span>
			<span class="sq-sk" style="width:120px;height:38px;border-radius:10px"></span>
		</div>
	</header>

	<!-- ── Nav skeleton ─────────────────────────────────────────── -->
	<nav class="sq-nav">
		<?php for ( $i = 0; $i < 4; $i++ ) : ?>
		<div class="sq-nav-item">
			<span class="sq-sk" style="width:16px;height:16px;border-radius:4px"></span>
			<span class="sq-sk sq-nav-label" style="height:13px;border-radius:4px"></span>
		</div>
		<?php endfor; ?>
	</nav>

	<!-- ── Main content skeleton ─────────────────────────────────── -->
	<main class="sq-main">

		<!-- Hero -->
		<div class="sq-hero">
			<div class="sq-hero-body">
				<span class="sq-sk" style="width:90px;height:11px"></span>
				<span class="sq-sk" style="width:260px;height:24px;border-radius:7px"></span>
				<span class="sq-sk" style="width:100%;height:12px"></span>
				<span class="sq-sk" style="width:75%;height:12px"></span>
				<div class="sq-hero-btns">
					<span class="sq-sk" style="width:140px;height:38px;border-radius:10px"></span>
					<span class="sq-sk" style="width:110px;height:38px;border-radius:10px"></span>
				</div>
			</div>
			<span class="sq-sk" style="width:120px;height:120px;border-radius:50%;margin-left:auto;flex-shrink:0"></span>
		</div>

		<!-- Stat cards -->
		<div class="sq-stats">
			<?php for ( $i = 0; $i < 4; $i++ ) : ?>
			<div class="sq-stat-card">
				<div class="sq-stat-top">
					<span class="sq-sk" style="width:38px;height:38px;border-radius:11px"></span>
					<span class="sq-sk" style="width:52px;height:22px;border-radius:999px"></span>
				</div>
				<span class="sq-sk" style="width:80px;height:36px;border-radius:7px;margin-bottom:6px"></span>
				<span class="sq-sk" style="width:110px;height:13px"></span>
				<span class="sq-sk" style="width:100%;height:6px;border-radius:999px;margin-top:14px"></span>
			</div>
			<?php endfor; ?>
		</div>

		<!-- Bottom grid -->
		<div class="sq-bottom">
			<!-- Left card: Quick actions + Library breakdown -->
			<div class="sq-card">
				<div class="sq-section-title">
					<span class="sq-sk" style="width:70px;height:11px"></span>
					<span class="sq-sk" style="width:110px;height:18px;border-radius:5px"></span>
				</div>
				<div class="sq-qa-grid">
					<?php for ( $i = 0; $i < 4; $i++ ) : ?>
					<div class="sq-qa-item">
						<span class="sq-sk" style="width:38px;height:38px;border-radius:11px;flex-shrink:0"></span>
						<div style="flex:1;display:flex;flex-direction:column;gap:6px">
							<span class="sq-sk" style="width:80%;height:13px"></span>
							<span class="sq-sk" style="width:100%;height:11px"></span>
							<span class="sq-sk" style="width:70%;height:11px"></span>
						</div>
					</div>
					<?php endfor; ?>
				</div>
				<!-- Library breakdown -->
				<div style="margin-top:24px">
					<span class="sq-sk" style="width:100px;height:11px;margin-bottom:8px"></span>
					<span class="sq-sk" style="width:140px;height:18px;border-radius:5px;margin-bottom:14px"></span>
					<span class="sq-sk" style="width:100%;height:14px;border-radius:999px;margin-bottom:12px"></span>
					<div style="display:flex;gap:14px;flex-wrap:wrap">
						<?php foreach ( array( 62, 90, 72, 86, 68 ) as $w ) : ?>
						<span class="sq-sk" style="width:<?php echo esc_attr( (string) $w ); ?>px;height:14px"></span>
						<?php endforeach; ?>
					</div>
				</div>
			</div>

			<!-- Right col: Pro upsell + Mini changelog -->
			<div class="sq-right-col">
				<!-- Pro upsell -->
				<div class="sq-pro-card">
					<span class="sq-sk" style="width:100px;height:11px;margin-bottom:8px;background:linear-gradient(90deg,#d4caf5 25%,#e8e3fb 50%,#d4caf5 75%);background-size:800px 100%;animation:sq-shimmer 1.6s infinite linear"></span>
					<span class="sq-sk" style="width:180px;height:18px;border-radius:5px;margin-bottom:14px;background:linear-gradient(90deg,#d4caf5 25%,#e8e3fb 50%,#d4caf5 75%);background-size:800px 100%;animation:sq-shimmer 1.6s infinite linear"></span>
					<?php for ( $i = 0; $i < 3; $i++ ) : ?>
					<div style="display:flex;align-items:center;gap:9px;margin-bottom:9px">
						<span class="sq-sk" style="width:18px;height:18px;border-radius:6px;background:linear-gradient(90deg,#d4caf5 25%,#e8e3fb 50%,#d4caf5 75%);background-size:800px 100%;animation:sq-shimmer 1.6s infinite linear"></span>
						<span class="sq-sk" style="width:<?php echo esc_attr( (string) array( 170, 140, 160 )[ $i ] ); ?>px;height:12px;background:linear-gradient(90deg,#d4caf5 25%,#e8e3fb 50%,#d4caf5 75%);background-size:800px 100%;animation:sq-shimmer 1.6s infinite linear"></span>
					</div>
					<?php endfor; ?>
					<span class="sq-sk" style="width:100%;height:38px;border-radius:10px;margin-top:4px;background:linear-gradient(90deg,#d4caf5 25%,#e8e3fb 50%,#d4caf5 75%);background-size:800px 100%;animation:sq-shimmer 1.6s infinite linear"></span>
				</div>

				<!-- Mini changelog -->
				<div class="sq-card">
					<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
						<span class="sq-sk" style="width:80px;height:16px;border-radius:5px"></span>
						<span class="sq-sk" style="width:60px;height:30px;border-radius:8px"></span>
					</div>
					<div class="sq-changelog-items" style="margin-top:14px">
						<?php
						$cl_widths = array(
							array(
								'badge' => 40,
								'ver' => 44,
								'text' => '100%',
							),
							array(
								'badge' => 62,
								'ver' => 44,
								'text' => '80%',
							),
							array(
								'badge' => 48,
								'ver' => 44,
								'text' => '100%',
							),
						);
						foreach ( $cl_widths as $cl ) :
							?>
						<div class="sq-cl-item">
							<span class="sq-sk" style="width:9px;height:9px;border-radius:50%;margin-top:5px;flex-shrink:0"></span>
							<div style="flex:1;display:flex;flex-direction:column;gap:4px">
								<div style="display:flex;align-items:center;gap:6px">
									<span class="sq-sk" style="width:<?php echo esc_attr( (string) $cl['badge'] ); ?>px;height:18px;border-radius:6px"></span>
									<span class="sq-sk" style="width:<?php echo esc_attr( (string) $cl['ver'] ); ?>px;height:13px;border-radius:4px"></span>
								</div>
								<span class="sq-sk" style="width:<?php echo esc_attr( $cl['text'] ); ?>;height:12px"></span>
							</div>
						</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>

	</main>
</main>
