<?php
/**
 * Branded app bar for the Freemius publisher (account / pricing / affiliation)
 * pages, matching the redesigned admin SPA app bar.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   4.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

$divi_squad_is_pro    = function_exists( 'divi_squad_fs' ) && divi_squad_fs()->can_use_premium_code();
$divi_squad_version   = divi_squad()->get_version();
$divi_squad = admin_url( 'admin.php?page=divi_squad' );
?>

<header class="squad-pub-bar">
	<div class="squad-pub-bar__brand">
		<span class="squad-pub-bar__tile" aria-hidden="true">
			<svg viewBox="0 0 128 128" role="img" aria-label="Squad Modules">
				<path d="M40 24 C80 24 104 44 104 64 C104 84 80 104 40 104" fill="none" stroke="currentColor" stroke-width="26" stroke-linecap="round" stroke-linejoin="round"/>
				<rect x="27" y="24" width="26" height="80" rx="13" fill="currentColor"/>
				<circle cx="40" cy="24" r="13" fill="currentColor"/>
			</svg>
		</span>
		<span class="squad-pub-bar__wordmark"><strong><?php esc_html_e( 'Squad', 'squad-modules-for-divi' ); ?></strong> <?php esc_html_e( 'Modules', 'squad-modules-for-divi' ); ?></span>
		<span class="squad-pub-bar__edition squad-pub-bar__edition--<?php echo $divi_squad_is_pro ? 'pro' : 'lite'; ?>">
			<?php echo $divi_squad_is_pro ? esc_html__( 'PRO', 'squad-modules-for-divi' ) : esc_html__( 'LITE', 'squad-modules-for-divi' ); ?>
		</span>
		<span class="squad-pub-bar__ver"><?php echo esc_html( $divi_squad_version ); ?></span>
	</div>
	<a class="squad-pub-bar__back" href="<?php echo esc_url( $divi_squad ); ?>">
		<span aria-hidden="true">&larr;</span> <?php esc_html_e( 'Back to Dashboard', 'squad-modules-for-divi' ); ?>
	</a>
</header>
