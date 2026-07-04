<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Base Notice Class
 *
 * This abstract class implements common functionality for all notice classes.
 *
 * @since   3.3.3
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Core\Admin\Notices;

/**
 * Base Notice Class
 *
 * @since   3.3.3
 * @package DiviSquad
 */
abstract class Notice_Base implements Notice_Interface {

	/**
	 * The notice ID.
	 *
	 * @var string
	 */
	protected string $notice_id = '';

	/**
	 * Get the notice ID.
	 *
	 * @since 3.3.3
	 *
	 * @return string The notice ID.
	 */
	public function get_notice_id(): string {
		return $this->notice_id;
	}

	/**
	 * Get the notice body classes.
	 *
	 * @since 3.3.3
	 *
	 * @return array<string> The notice body classes.
	 */
	public function get_body_classes(): array {
		return array();
	}

	/**
	 * Check if the notice can be rendered.
	 *
	 * @since 3.3.3
	 *
	 * @return bool Whether the notice can be rendered.
	 */
	abstract public function can_render_it(): bool;

	/**
	 * Get the notice template arguments.
	 *
	 * @since 3.3.3
	 *
	 * @return array<string, mixed> The notice template arguments.
	 */
	abstract public function get_template_args(): array;
}
