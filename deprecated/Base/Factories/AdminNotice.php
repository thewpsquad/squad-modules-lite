<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Class AdminNotice - Factory adapter
 *
 * This class acts as a compatibility layer between the old notice factory system
 * and the new Core\Admin\AdminNotice class.
 *
 * @since      3.3.0
 * @deprecated 3.3.3
 * @package    DiviSquad
 * @author     The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Base\Factories;

use DiviSquad\Base\Factories\FactoryBase\Factory;
use DiviSquad\Core\Traits\Singleton;

/**
 * Class AdminNotice
 *
 * @since      2.0.0
 * @deprecated 3.3.3
 * @package    DiviSquad
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
		// Listen to filters from the new AdminNotice class
		add_filter( 'divi_squad_notices', array( $this, 'add_legacy_notices' ), 10, 2 );
		add_filter( 'divi_squad_admin_notice_body_classes', array( $this, 'add_legacy_notice_body_classes' ), 10, 2 );
		add_filter( 'divi_squad_notice_localize_data', array( $this, 'add_legacy_notice_localize_data' ), 10, 2 );
	}

	/**
	 * Add a new notice to the list of notices.
	 *
	 * @param string $class_name The class name of the notice to add to the list.
	 *
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
	 * Add legacy notices to the notices list.
	 *
	 * @param array $notices The notices from the new AdminNotice class.
	 * @param mixed $manager The AdminNotice manager instance.
	 *
	 * @return array The modified notices.
	 */
	public function add_legacy_notices( array $notices, $manager ): array {
		foreach ( self::$registries as $notice ) {
			if ( $notice->can_render_it() ) {
				$notices[] = array(
					'id'       => $notice->get_notice_id(),
					'template' => $notice->get_template(),
					'args'     => $notice->get_template_args(),
				);
			}
		}

		return $notices;
	}

	/**
	 * Add legacy notice body classes.
	 *
	 * @param string $classes The current body classes.
	 * @param mixed  $manager The AdminNotice manager instance.
	 *
	 * @return string The modified body classes.
	 */
	public function add_legacy_notice_body_classes( string $classes, $manager ): string {
		foreach ( self::$registries as $notice ) {
			if ( $notice->can_render_it() ) {
				$body_classes = $notice->get_body_classes();
				if ( ! empty( $body_classes ) ) {
					$classes .= ' ' . $body_classes . ' ';
				}
			}
		}

		return $classes;
	}

	/**
	 * Add legacy notice localize data.
	 *
	 * @param array $data    The current localize data.
	 * @param mixed $manager The AdminNotice manager instance.
	 *
	 * @return array The modified localize data.
	 */
	public function add_legacy_notice_localize_data( array $data, $manager ): array {
		$notice_data = array();

		foreach ( self::$registries as $notice ) {
			if ( $notice->can_render_it() ) {
				$notice_data[] = $notice->get_template_args();
			}
		}

		if ( ! empty( $notice_data ) ) {
			$data = array_merge( $data, array( 'legacy_notices' => $notice_data ) );
		}

		return $data;
	}

	/**
	 * Get all registered notices (compatibility method).
	 *
	 * @return array
	 */
	public function get_notices(): array {
		$notices = array();

		foreach ( self::$registries as $notice ) {
			if ( $notice->can_render_it() ) {
				$notices[] = $notice->get_template_args();
			}
		}

		return $notices;
	}

	/**
	 * Compatibility method for localize data.
	 *
	 * @param array $exists_data Existing data.
	 *
	 * @return array
	 */
	public function wp_localize_script_data( array $exists_data ): array {
		// This is now primarily handled by the Core Admin Notice class
		// but we maintain this for backward compatibility

		return $exists_data;
	}
}
