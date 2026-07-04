<?php
/**
 * Template file for the layout header.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   3.2.3
 *
 * @var array<string, string> $args Arguments passed to the template.
 */

if ( ! ( defined( 'ABSPATH' ) && is_array( $args ) ) ) {
	die( 'Direct access forbidden.' );
}

if ( wp_doing_ajax() ) {
	die( 'Access forbidden from AJAX request.' );
}

// Load image loader.
$divi_squad_image = divi_squad()->load_image( '/build/admin/images/logos' );

// Check if image is validated.
if ( ! $divi_squad_image->is_path_validated() ) {
	return;
}

?>

<div class="app-title">
	<div class="title-wrapper">
		<?php
		$divi_squad_app_logo = $divi_squad_image->get_image( 'divi-squad-default.png', 'png' );
		if ( ! is_wp_error( $divi_squad_app_logo ) ) :
			?>
			<img class='logo' alt="<?php esc_attr_e( 'Divi Squad', 'squad-modules-for-divi' ); ?>" src="<?php echo esc_url( $divi_squad_app_logo, array( 'data' ) ); ?>"/>
		<?php endif; ?>

		<h1 class="title">
			<?php esc_html_e( 'Divi Squad', 'squad-modules-for-divi' ); ?>
		</h1>

		<ul class='badges'>
			<?php
			/**
			 * Fires after the badges in the requirements page.
			 *
			 * @since 3.2.3
			 *
			 * @param string $divi_squad_plugin_life_type The plugin life type.
			 */
			do_action( 'divi_squad_menu_badges', ( $args['divi_squad_plugin_life_type'] ?? 'nightly' ) );
			?>
		</ul>
	</div>
</div>
