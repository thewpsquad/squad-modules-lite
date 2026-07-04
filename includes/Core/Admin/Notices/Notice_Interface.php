<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Notice Interface
 *
 * This interface defines the required methods for all notice classes.
 *
 * @since   3.3.3
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Core\Admin\Notices;

/**
 * Notice Interface
 *
 * @since   3.3.3
 * @package DiviSquad
 */
interface Notice_Interface {

	/**
	 * Check if the notice can be rendered.
	 *
	 * @since 3.3.3
	 *
	 * @return bool Whether the notice can be rendered.
	 */
	public function can_render_it(): bool;

	/**
	 * Get the notice template arguments.
	 *
	 * @since 3.3.3
	 *
	 * @return array<string, mixed> The notice template arguments.
	 */
	public function get_template_args(): array;

	/**
	 * Get the notice body classes.
	 *
	 * @since 3.3.3
	 *
	 * @return array<string> The notice body classes.
	 */
	public function get_body_classes(): array;

	/**
	 * Get the notice ID.
	 *
	 * @since 3.3.3
	 *
	 * @return string The notice ID.
	 */
	public function get_notice_id(): string;
}
