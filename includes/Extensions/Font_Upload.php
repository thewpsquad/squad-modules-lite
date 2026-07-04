<?php // phpcs:ignore WordPress.Files.FileName

/**
 * The Font Upload extension class for Divi Squad.
 *
 * @since   1.2.0
 * @author  The WP Squad <support@squadmodules.com>
 * @package DiviSquad
 */

namespace DiviSquad\Extensions;

use function add_filter;

/**
 * The Font Upload class.
 *
 * @since   1.2.0
 * @package DiviSquad
 */
class Font_Upload extends Extension {

	/**
	 * Allow extra mime type file upload in the current installation.
	 *
	 * @param array<string, bool|string> $existing_mimes The existing mime lists.
	 *
	 * @return array<string, bool|string> All mime lists with newly appended mimes.
	 */
	public function hook_add_extra_mime_types( array $existing_mimes ): array {
		return array_merge( $existing_mimes, $this->get_available_mime_types() );
	}

	/**
	 * All mime lists with newly appended mimes.
	 *
	 * @return array<string, bool|string>
	 */
	protected function get_available_mime_types(): array {
		return array(
			'ttf'   => 'font/ttf|application/font-ttf|application/x-font-ttf|application/octet-stream',
			'otf'   => 'font/otf|application/font-sfnt|application/font-otf|application/x-font-otf|application/octet-stream',
			'woff'  => 'font/woff|application/font-woff|application/x-font-woff|application/octet-stream',
			'woff2' => 'font/woff2|application/font-woff2|application/x-font-woff2|application/octet-stream',
		);
	}

	/**
	 * Get the extension name.
	 *
	 * @return string
	 */
	protected function get_name(): string {
		return 'Font_Upload';
	}

	/**
	 * Load the extension.
	 *
	 * @return void
	 */
	protected function load(): void {
		add_filter( 'mime_types', array( $this, 'hook_add_extra_mime_types' ) );
		add_filter( 'upload_mimes', array( $this, 'hook_add_extra_mime_types' ) );
	}
}
