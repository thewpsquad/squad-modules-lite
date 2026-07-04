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
	 * Determines whether the notice should be displayed to the current user.
	 *
	 * @since 3.3.3
	 *
	 * @return bool Whether the notice can be rendered.
	 */
	public function can_render_it(): bool;

	/**
	 * Get the notice template arguments.
	 *
	 * Returns the data needed to render the notice in both PHP and React contexts.
	 *
	 * @since 3.3.3
	 *
	 * @return array<string, mixed> The notice template arguments.
	 */
	public function get_template_args(): array;

	/**
	 * Get the notice body classes.
	 *
	 * Returns CSS classes to be added to the admin body when this notice is active.
	 *
	 * @since 3.3.3
	 *
	 * @return array<string> The notice body classes.
	 */
	public function get_body_classes(): array;

	/**
	 * Get the notice ID.
	 *
	 * Returns a unique identifier for this notice type.
	 *
	 * @since 3.3.3
	 *
	 * @return string The notice ID.
	 */
	public function get_notice_id(): string;

	/**
	 * Get the scopes where this notice should be displayed.
	 *
	 * Notices can be targeted to specific admin areas or contexts.
	 * Possible values include: 'global', 'dashboard', 'settings', etc.
	 *
	 * @since 3.3.3
	 *
	 * @return array<string> The notice scopes.
	 */
	public function get_scopes(): array;
}