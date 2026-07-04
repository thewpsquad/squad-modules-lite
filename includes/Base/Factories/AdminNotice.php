<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Class AdminNotice
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   2.0.0
 */

namespace DiviSquad\Base\Factories;

use DiviSquad\Base\Factories\FactoryBase\Factory;
use DiviSquad\Core\Traits\Singleton;

/**
 * Class AdminNotice
 *
 * @package DiviSquad
 * @since   2.0.0
 */
final class AdminNotice extends Factory {

	use Singleton;

	/**
	 * Store all registry
	 *
	 * @var AdminNotice\NoticeInterface[]
	 */
	private static array $registries = array();

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	protected function init_hooks() {
		// Load all admin notices and body classes for admin.
		add_action( 'admin_notices', array( $this, 'add_admin_notices' ), 0 );
		add_filter( 'admin_body_class', array( $this, 'add_body_classes' ), 0 );

		// Load localize data.
		add_filter( 'divi_squad_global_localize_data', array( $this, 'wp_localize_script_data' ), 0 );
	}

	/**
	 * Add a new notice to the list of notices.
	 *
	 * @param string $class_name The class name of the notice to add to the list. The class must implement the NoticeInterface.
	 *
	 * @see AdminNotice\NoticeInterface interface.
	 * @return void
	 */
	public function add( $class_name ) {
		if ( ! class_exists( $class_name ) ) {
			return;
		}

		$notice = new $class_name();
		if ( ! $notice instanceof AdminNotice\NoticeInterface ) {
			return;
		}

		self::$registries[] = $notice;
	}

	/**
	 * Prints admin screen notices in the WordPress admin area.
	 *
	 * @return void
	 */
	public function add_admin_notices() {
		if ( ! empty( self::$registries ) ) {
			foreach ( self::$registries as $notice ) {
				if ( $notice->can_render_it() && divi_squad()->get_wp_fs()->exists( $notice->get_template() ) ) {
					load_template( $notice->get_template(), false, $notice->get_template_args() );
				}
			}
		}
	}

	/**
	 * Filters the CSS classes for the body tag in the admin.
	 *
	 * @since 1.0.4
	 *
	 * @param string $classes Space-separated list of CSS classes.
	 *
	 * @return string
	 */
	public function add_body_classes( string $classes ): string {
		if ( ! empty( self::$registries ) ) {
			foreach ( self::$registries as $notice ) {
				if ( $notice->can_render_it() ) {
					$classes .= ' ' . $notice->get_body_classes();
				}
			}
		}

		return $classes;
	}

	/**
	 * Registered all notices.
	 *
	 * @return array
	 */
	public function get_notices(): array {
		$registries = array();
		foreach ( self::$registries as $notice ) {
			if ( $notice->can_render_it() ) {
				$registries[] = $notice->get_template_args();
			}
		}

		return $registries;
	}

	/**
	 * Set localize data for admin area.
	 *
	 * @param array $exists_data Exists extra data.
	 *
	 * @return array
	 */
	public function wp_localize_script_data( array $exists_data ): array {
		// Localize data for squad admin.
		$notice_localize = array(
			'notices' => $this->get_notices(),
		);

		return array_merge( $exists_data, $notice_localize );
	}
}
