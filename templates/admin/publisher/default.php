<?php
/**
 * Template file
 *
 * @since   2.0.0
 *
 * @author  The WP Squad <support@squadmodules.com>
 * @package DiviSquad
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

<main id="squad-generic-pages" class="squad-components">
	<?php echo $divi_squad_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</main>
