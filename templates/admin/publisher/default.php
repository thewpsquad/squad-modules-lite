<?php
/**
 * Template file
 *
 * @since   2.0.0
 *
 * @author  The WP Squad <support@squadmodules.com>
 * @package DiviSquad
 *
 * @var array<string>|string $args Arguments passed to the template.
 */

if ( ! ( defined( 'ABSPATH' ) && is_string( $args ) ) ) {
	die( 'Direct access forbidden.' );
}

if ( wp_doing_ajax() ) {
	die( 'Access forbidden from AJAX request.' );
}

?>

<main id="squad-generic-pages" class="squad-components">
	<?php echo $args; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</main>
