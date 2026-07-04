<?php
/**
 * Template file for the account.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   2.0.0
 *
 * @var array{content: string} $args Arguments passed to the template.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

if ( wp_doing_ajax() ) {
	die( 'Access forbidden from AJAX request.' );
}

// Extract the rendered Freemius content.
$divi_squad_content = is_array( $args ) ? (string) ( $args['content'] ?? '' ) : (string) $args;

?>

<main id="squad-modules-app" class="squad-modules-app squad-components squad-publisher">
	<?php load_template( divi_squad()->get_template_path( 'admin/publisher/parts/app-bar.php' ), false ); ?>

	<div class="squad-pub-body">
		<div class="squad-pub-card subscription-wrapper">
			<?php echo $divi_squad_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	</div>
</main>
