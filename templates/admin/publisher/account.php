<?php
/**
 * Template file for the account.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   2.0.0
 *
 * @var string $args Arguments passed to the template.
 */

if ( ! ( defined( 'ABSPATH' ) && is_string( $args ) ) ) {
	die( 'Direct access forbidden.' );
}

if ( wp_doing_ajax() ) {
	die( 'Access forbidden from AJAX request.' );
}

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

?>

<main id="squad-modules-app" class="squad-modules-app squad-components">
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
		<div class="app-menu">
			<div class="app-menu-container">
				<div class="menu-list">
					<?php
					/**
					 * Fires to display the menu list in the dashboard.
					 *
					 * @since 3.2.3
					 *
					 * @param string $divi_squad_plugin_life_type The plugin life type.
					 */
					do_action( 'divi_squad_menu_list_html', $divi_squad_plugin_life_type );
					?>
				</div>
			</div>
		</div>
		<div class="wrapper-container">
			<div class="subscription-wrapper" style="display: none;">
				<?php echo $args; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</div>
	</div>
</main>
