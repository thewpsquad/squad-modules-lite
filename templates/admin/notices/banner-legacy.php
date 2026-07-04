<?php
/**
 * Template file to the notice.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   3.0.0
 *
 * @var array<string, mixed> $args The arguments to the template.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

if ( 0 === count( $args ) ) {
	return;
}

// Load the image loader.
$divi_squad_image = divi_squad()->load_image( '/build/admin/images' );

// Get the allowed HTML for the image.
$divi_squad_image_allowed_html = $divi_squad_image->get_image_allowed_html();

// Check if image is validated.
if ( ! $divi_squad_image->is_path_validated() ) {
	return;
}
?>

<div class="notice divi-squad-banner is-dismissible <?php echo esc_attr( $args['wrapper_classes'] ?? '' ); ?>">

	<?php if ( '' !== ( $args['logo'] ?? '' ) ) : ?>
		<div class='divi-squad-banner-logo'>
			<?php $divi_squad_notice_logo = $divi_squad_image->get_image( $args['logo'], 'svg', false ); ?>
			<?php if ( ! is_wp_error( $divi_squad_notice_logo ) ) : ?>
				<?php echo wp_kses( $divi_squad_notice_logo, $divi_squad_image_allowed_html ); ?>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<div class="divi-squad-banner-content">
		<?php if ( '' !== ( $args['title'] ?? '' ) ) : ?>
			<h2><?php echo esc_html( $args['title'] ); ?></h2>
		<?php endif; ?>

		<?php if ( '' !== ( $args['content'] ?? '' ) ) : ?>
			<p><?php echo wp_kses_post( $args['content'] ); ?></p>
		<?php endif; ?>

		<?php if ( array() !== ( $args['action-buttons'] ?? array() ) ) : ?>
			<div class="divi-squad-notice-action">
				<?php if ( array() !== ( $args['action-buttons']['left'] ?? array() ) ) : ?>
					<div class="divi-squad-notice-action-left">
						<?php foreach ( $args['action-buttons']['left'] as $divi_squad_left_button ) : ?>
							<a href="<?php echo esc_url( $divi_squad_left_button['link'] ); ?>" target="_blank" class="<?php echo esc_attr( $divi_squad_left_button['classes'] ); ?>" style="<?php echo esc_attr( $divi_squad_left_button['style'] ) ?? ''; ?>">
								<?php if ( '' !== ( $divi_squad_left_button['icon'] ?? '' ) ) : ?>
									<span class="dashicons <?php echo esc_attr( $divi_squad_left_button['icon'] ); ?>"></span>
								<?php endif; ?>
								<?php if ( '' !== ( $divi_squad_left_button['icon_svg'] ?? '' ) ) : ?>
									<?php $divi_squad_notice_left_button_icon = $divi_squad_image->get_image( $divi_squad_left_button['icon_svg'], 'svg', false ); ?>
									<?php if ( ! is_wp_error( $divi_squad_notice_left_button_icon ) ) : ?>
										<?php echo wp_kses( $divi_squad_notice_left_button_icon, $divi_squad_image_allowed_html ); ?>
									<?php endif; ?>
								<?php endif; ?>
								<p><?php echo esc_html( $divi_squad_left_button['text'] ); ?></p>
							</a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<?php if ( array() !== ( $args['action-buttons']['right'] ?? array() ) ) : ?>
					<div class="divi-squad-notice-action-right">
						<?php foreach ( $args['action-buttons']['right'] as $divi_squad_right_button ) : ?>
							<a href="<?php echo esc_url( $divi_squad_right_button['link'] ); ?>" target="_blank" class="<?php echo esc_attr( $divi_squad_right_button['classes'] ); ?>" style="<?php echo esc_attr( $divi_squad_right_button['style'] ) ?? ''; ?>">
								<?php if ( '' !== ( $divi_squad_right_button['icon'] ?? '' ) ) : ?>
									<span class="dashicons <?php echo esc_attr( $divi_squad_right_button['icon'] ); ?>"></span>
								<?php endif; ?>
								<?php if ( '' !== ( $divi_squad_right_button['icon_svg'] ?? '' ) ) : ?>
									<?php $divi_squad_notice_right_button_icon = $divi_squad_image->get_image( $divi_squad_right_button['icon_svg'], 'svg', false ); ?>
									<?php if ( ! is_wp_error( $divi_squad_notice_right_button_icon ) ) : ?>
										<?php echo wp_kses( $divi_squad_notice_right_button_icon, $divi_squad_image_allowed_html ); ?>
									<?php endif; ?>
								<?php endif; ?>
								<?php echo esc_html( $divi_squad_right_button['text'] ); ?>
							</a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

			</div>
		<?php endif; ?>

	</div>

	<?php if ( '' !== ( $args['is_dismissible'] ?? '' ) ) : ?>
		<button type="button" class="notice-dismiss">
			<span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'squad-modules-for-divi' ); ?></span>
		</button>
	<?php endif; ?>

</div>
