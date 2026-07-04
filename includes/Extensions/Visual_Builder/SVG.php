<?php // phpcs:ignore WordPress.Files.FileName

/**
 * The SVG class for Divi Squad.
 *
 * This class handles svg image upload and used in the WordPress setup.
 *
 * @since   1.2.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Extensions\Visual_Builder;

use DiviSquad\Extensions\Abstracts\Base_Extension;
use function add_filter;
use function wp_check_filetype;

/**
 * The SVG class.
 *
 * @since   1.2.0
 * @package DiviSquad
 */
class SVG extends Base_Extension {

	/**
	 * Get the extension name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'SVG';
	}

	/**
	 * Load the extension.
	 *
	 * @return void
	 */
	public function load(): void {
		add_filter( 'mime_types', array( $this, 'hook_add_extra_mime_types' ) );
		add_filter( 'upload_mimes', array( $this, 'hook_add_extra_mime_types' ) );
		add_filter( 'wp_check_filetype_and_ext', array( $this, 'enable_upload' ), 10, 4 );
	}

	/**
	 * Allow extra mime type file upload in the current installation.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, string> $existing_mimes The existing mime lists.
	 *
	 * @return array<string, string> All mime lists with newly appended mimes.
	 */
	public function hook_add_extra_mime_types( array $existing_mimes ): array {
		return array_merge( $existing_mimes, $this->get_available_mime_types() );
	}

	/**
	 * All mime lists with newly appended mimes.
	 *
	 * @return array<string, string> All mime lists with newly appended mimes.
	 */
	public function get_available_mime_types(): array {
		return array(
			'svg' => 'image/svg+xml',
		);
	}

	/**
	 * Filters the "real" file type of the given file.
	 *
	 * @param array<string, bool|string> $wp_check Values for the extension, mime type, and corrected filename.
	 * @param string                     $file     Full path to the file.
	 * @param string                     $filename The name of the file.
	 * @param string[]|null              $mimes    Array of mime types keyed by their file extension regex.
	 *
	 * @return array<string, bool|string> Values for the extension, mime type, and corrected filename.
	 */
	public function enable_upload( array $wp_check, string $file, string $filename, $mimes = null ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClassBeforeLastUsed
		if ( ! is_string( $wp_check['type'] ) ) {
			$check_filetype  = wp_check_filetype( $filename, $mimes );
			$ext             = $check_filetype['ext'];
			$type            = $check_filetype['type'];
			$proper_filename = $filename;

			if ( is_string( $type ) && 'svg' !== $ext && 0 === strpos( $type, 'image/' ) ) {
				$ext  = false;
				$type = false;
			}

			$wp_check = compact( 'ext', 'type', 'proper_filename' );
		}

		return $wp_check;
	}
}
