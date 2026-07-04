<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Abstract class representing the Notice.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   2.0.0
 */

namespace DiviSquad\Base\Factories\AdminNotice;

/**
 * Abstract class representing the Notice.
 *
 * @package DiviSquad
 * @since   2.0.0
 */
abstract class Notice implements NoticeInterface {

	/**
	 * The template arguments
	 *
	 * @var array
	 */
	protected $template_args = array();

	/**
	 * Get the notice id.
	 *
	 * @var string
	 */
	protected $notice_id = '';

	/**
	 * Get the template arguments
	 *
	 * @return array
	 */
	abstract public function get_template_args();

	/**
	 * Get the template path.
	 *
	 * @return string
	 */
	public function get_template() {
		// If the template root is not set, return empty string.
		if ( ! $this->get_template_root() ) {
			return '';
		}

		// If the notice id is not set, return empty string.
		if ( empty( $this->get_notice_id() ) ) {
			return '';
		}

		return sprintf( '%1$s/notices/template.php', $this->get_template_root() );
	}

	/**
	 * Get the template root.
	 *
	 * @return string
	 */
	public function get_template_root() {
		return divi_squad()->get_template_path();
	}

	/**
	 * Get the notice id.
	 *
	 * @return string
	 */
	public function get_notice_id() {
		return $this->notice_id;
	}
}
