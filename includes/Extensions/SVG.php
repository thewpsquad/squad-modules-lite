<?php // phpcs:ignore WordPress.Files.FileName
/**
 * The SVG class for Divi Squad.
 *
 * This class handles svg image upload and used in the WordPress setup.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   1.2.0
 */

namespace DiviSquad\Extensions;

use DiviSquad\Base\Extension;
use function add_filter;
use function wp_check_filetype;

/**
 * The SVG class.
 *
 * @package DiviSquad
 * @since   1.2.0
 */
class SVG extends Extension {

	/**
	 * Allow extra mime type file upload in the current installation.
	 *
	 * @since 1.0.0
	 *
	 * @param array $existing_mimes The existing mime lists.
	 *
	 * @return array All mime lists with newly appended mimes.
	 */
	public function hook_add_extra_mime_types( array $existing_mimes ): array {
		return array_merge( $existing_mimes, $this->get_available_mime_types() );
	}

	/**
	 * All mime lists with newly appended mimes.
	 *
	 * @return array
	 */
	public function get_available_mime_types(): array {
		return array(
			'svg' => 'image/svg+xml',
		);
	}

	/**
	 * Filters the "real" file type of the given file.
	 *
	 * @param array    $wp_check Values for the extension, mime type, and corrected filename.
	 * @param string   $file     Full path to the file.
	 * @param string   $filename The name of the file.
	 * @param string[] $mimes    Array of mime types keyed by their file extension regex.
	 */
	public function enable__upload( array $wp_check, string $file, string $filename, array $mimes ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClassBeforeLastUsed
		if ( ! $wp_check['type'] ) {
			$check_filetype  = wp_check_filetype( $filename, $mimes );
			$ext             = $check_filetype['ext'];
			$type            = $check_filetype['type'];
			$proper_filename = $filename;

			if ( $type && 'svg' !== $ext && 0 === strpos( $type, 'image/' ) ) {
				$ext  = false;
				$type = false;
			}

			$wp_check = compact( 'ext', 'type', 'proper_filename' );
		}

		return $wp_check;
	}

	/**
	 * Get the extension name.
	 *
	 * @return string
	 */
	protected function get_name(): string {
		return 'SVG';
	}

	/**
	 * Load the extension.
	 *
	 * @return void
	 */
	protected function load(): void {
		add_filter( 'mime_types', array( $this, 'hook_add_extra_mime_types' ) );
		add_filter( 'upload_mimes', array( $this, 'hook_add_extra_mime_types' ) );
		add_filter( 'wp_check_filetype_and_ext', array( $this, 'enable__upload' ), 10, 4 );
	}
}
