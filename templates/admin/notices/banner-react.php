<?php
/**
 * Template for React Notice Container
 *
 * This template provides the container element for React-rendered notices.
 * It creates the mounting point for the React application and provides
 * fallback content when JavaScript is disabled.
 *
 * @since   3.4.0
 *
 * @author  The WP Squad <support@squadmodules.com>
 * @package DiviSquad
 * @var array<string, mixed> $args The arguments to the template.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

// Default container attributes
$container_id      = $args['container_id'] ?? 'divi-squad-admin-notices';
$container_classes = $args['container_classes'] ?? 'divi-squad-react-notice-container';
$loading_text      = $args['loading_text'] ?? esc_html__( 'Loading notices...', 'squad-modules-for-divi' );
$notice_count      = $args['notice_count'] ?? 0;
$data_attributes   = array();

// Add data attributes for notice configuration
if ( $notice_count > 0 ) {
	$data_attributes['data-notice-count'] = (int) $notice_count;
}

if ( isset( $args['scopes'] ) && is_array( $args['scopes'] ) ) {
	$data_attributes['data-scopes'] = esc_attr( implode( ',', $args['scopes'] ) );
}

if ( isset( $args['auto_slide'] ) ) {
	$data_attributes['data-auto-slide'] = true === $args['auto_slide'] ? 'true' : 'false';
}

if ( isset( $args['slide_interval'] ) ) {
	$data_attributes['data-slide-interval'] = (int) $args['slide_interval'];
}

// Add development mode indicator if needed
if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
	$data_attributes['data-dev-mode'] = 'true';
}

// Build the data attributes string
$data_attr_string = '';
foreach ( $data_attributes as $key => $value ) {
	$data_attr_string .= ' ' . $key . '="' . $value . '"';
}

/**
 * Filters the React notice container HTML attributes.
 *
 * @since 3.4.0
 *
 * @param string               $attributes   The HTML attributes string.
 * @param string               $container_id The container ID.
 * @param array<string, mixed> $args         The template arguments.
 */
$attributes = apply_filters( 'divi_squad_react_notice_container_attributes', $data_attr_string, $container_id, $args );

/**
 * Action before rendering the React notice container.
 *
 * @since 3.4.0
 *
 * @param string               $container_id The container ID.
 * @param array<string, mixed> $args         The template arguments.
 */
do_action( 'divi_squad_before_react_notice_container_template', $container_id, $args );
?>

	<div id="<?php echo esc_attr( $container_id ); ?>" class="notice <?php echo esc_attr( $container_classes ); ?>"<?php echo $attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php if ( '' !== $loading_text ) : ?>
			<div class="divi-squad-react-notice-loading">
				<span class="spinner is-active"></span>
				<span class="loading-text"><?php echo esc_html( $loading_text ); ?></span>
			</div>
		<?php endif; ?>

		<?php
		/**
		 * Action for adding content inside the React notice container.
		 *
		 * This hook can be used to add custom content or debug information
		 * inside the container.
		 *
		 * @since 3.4.0
		 *
		 * @param string               $container_id The container ID.
		 * @param array<string, mixed> $args         The template arguments.
		 */
		do_action( 'divi_squad_react_notice_container_content', $container_id, $args );
		?>

		<noscript>
			<?php
			/**
			 * This section is displayed when JavaScript is disabled in the browser.
			 * It can either show the notices in a traditional way or a message about
			 * JavaScript being required.
			 */
			?>
			<div class="divi-squad-no-js-notice">
				<?php if ( isset( $args['fallback_notices'] ) && is_array( $args['fallback_notices'] ) ) : ?>
					<?php
					foreach ( $args['fallback_notices'] as $notice ) :
						// Load the fallback notice template if JavaScript is disabled
						load_template( divi_squad()->get_template_path( 'admin/notices/banner.php' ), false, $notice );
					endforeach;
					?>
				<?php else : ?>
					<div class="notice notice-warning">
						<p><?php esc_html_e( 'JavaScript is required for enhanced admin notices. Please enable JavaScript or contact your administrator.', 'squad-modules-for-divi' ); ?></p>
					</div>
				<?php endif; ?>
			</div>
		</noscript>
	</div>

<?php
/**
 * Action after the React notice container is rendered.
 *
 * @since 3.4.0
 *
 * @param string               $container_id The container ID.
 * @param array<string, mixed> $args         The template arguments.
 */
do_action( 'divi_squad_after_react_notice_container_template', $container_id, $args );
?>