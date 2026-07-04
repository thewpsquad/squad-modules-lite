<?php
/**
 * Template file to the plugin copy post or cpt.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   1.2.3
 *
 * @var array $args Arguments passed to the template.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

if ( 0 === count( $args ) ) {
	return;
}

?>

<div class="ext-copy-content" id="squad_ext_copy_content" style="display: none;">
	<fieldset class="options-container">
		<legend class="container-label" ><?php echo esc_html__( 'Copy Options', 'squad-modules-for-divi' ); ?></legend>
		<div class="fieldset-container">
			<div class="align-left copy-input">
				<label>
					<input value="1" placeholder="1" min="1" minlength="1" maxlength="5" max="10000" type="number" name="copied-post-count">
				</label>
			</div>
			<div class="align-left">&nbsp;<?php echo esc_html__( 'copy(s)', 'squad-modules-for-divi' ); ?></div>
			<div class="align-left">&nbsp;<?php echo esc_html__( 'to', 'squad-modules-for-divi' ); ?>&nbsp;</div>
			<div class="align-left">
				<label>
					<select name="copied-post-target-site" <?php echo esc_attr( $args['site_is_multi'] ); ?>>

						<option value="<?php echo esc_attr( $args['current_site'] ); ?>" data-type="dynamic">
							<?php esc_html_e( 'this site', 'squad-modules-for-divi' ); ?>
						</option>

						<?php if ( isset( $args['blog_sites'] ) && function_exists( 'is_multisite' ) && is_multisite() ) : ?>

							<?php foreach ( $args['blog_sites'] as $divi_squad_blog_site  => $divi_squad_blog_site_name ) : ?>
								<?php if ( absint( $divi_squad_blog_site ) !== $args['current_site'] ) : ?>

									<option value="<?php echo esc_attr( $divi_squad_blog_site ); ?>" data-type="dynamic">
										<?php echo esc_html( $divi_squad_blog_site_name ); ?>
									</option>

								<?php endif; ?>
							<?php endforeach; ?>

						<?php endif; ?>

					</select>
				</label>
			</div>
		</div>
	</fieldset>

	<button class="squad-admin-button fill-button" data-btn="copy-quick">
		<?php echo esc_html__( 'Copy now!', 'squad-modules-for-divi' ); ?>
	</button>
</div>

<div class="ext-copy-loader-overlay" style="opacity: 0">
	<div class="ext-copy-text-overlay">
		<h1 class="overlay-title-text">
			<?php echo esc_html__( 'Please wait, copying in progress...', 'squad-modules-for-divi' ); ?>
		</h1>
		<p class="overlay-description-text">
			<?php
			echo wp_kses_post(
				sprintf(
					// translators: %s: line break.
					esc_html__( 'If you’re making a lot of copies, it can take a while %1$s(up to 5 minutes if you’re on a slow server).', 'squad-modules-for-divi' ),
					'<br>'
				)
			)
			?>
		</p>
		<span class="overlay-notice-text">
			<?php echo esc_html__( 'Average time is 8 copies per second.', 'squad-modules-for-divi' ); ?>
		</span>
	</div>
	<div class="ext-copy-spinner"></div>
</div>
