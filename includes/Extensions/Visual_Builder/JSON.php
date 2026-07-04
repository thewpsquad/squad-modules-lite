<?php // phpcs:ignore WordPress.Files.FileName

/**
 * The JSON extension class for Divi Squad.
 *
 * @since   1.2.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Extensions\Visual_Builder;

use DiviSquad\Extensions\Abstracts\Base_Extension;
use function add_filter;
use function esc_html__;

/**
 * The JSON class.
 *
 * @since   1.2.0
 * @package DiviSquad
 */
class JSON extends Base_Extension {

	/**
	 * Get the extension name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'JSON';
	}

	/**
	 * Load the extension.
	 *
	 * @return void
	 */
	public function load(): void {
		add_filter( 'mime_types', array( $this, 'hook_add_extra_mime_types' ) );
		add_filter( 'upload_mimes', array( $this, 'hook_add_extra_mime_types' ) );
		add_filter( 'wp_check_filetype_and_ext', array( $this, 'hook_wp_check_filetype_and_ext' ), 10, 3 );
		add_filter( 'wp_handle_upload_prefilter', array( $this, 'validate_on_upload' ) );
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
			'json'   => 'application/json',
			'lottie' => 'application/zip',
		);
	}

	/**
	 * Filters the "real" file type of the given file.
	 *
	 * @param array<string, bool|string> $wp_checked Values for the extension, mime type, and corrected filename.
	 * @param string                     $file       Full path to the file.
	 * @param string                     $filename   The name of the file.
	 *
	 * @return array<string, bool|string> Values for the extension, mime type, and corrected filename.
	 */
	public function hook_wp_check_filetype_and_ext( array $wp_checked, string $file, string $filename ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClassBeforeLastUsed
		// Already resolved by WordPress — leave it alone.
		$resolved_ext  = isset( $wp_checked['ext'] ) && is_string( $wp_checked['ext'] ) && '' !== $wp_checked['ext'];
		$resolved_type = isset( $wp_checked['type'] ) && is_string( $wp_checked['type'] ) && '' !== $wp_checked['type'];
		if ( $resolved_ext && $resolved_type ) {
			return $wp_checked;
		}

		$ext   = strtolower( (string) pathinfo( $filename, PATHINFO_EXTENSION ) );
		$types = $this->get_available_mime_types();

		if ( isset( $types[ $ext ] ) ) {
			$wp_checked['ext']  = $ext;
			$wp_checked['type'] = $types[ $ext ];
		}

		return $wp_checked;
	}

	/**
	 * Validate the actual contents of an uploaded .json / .lottie file.
	 *
	 * The extension allow-lists `.json` (application/json) and `.lottie`
	 * (application/zip) by extension. Without a content check, any file renamed
	 * to those extensions would be accepted. This verifies the real payload:
	 * `.json` must parse as JSON, `.lottie` must be a real ZIP archive.
	 *
	 * @since 3.4.2
	 *
	 * @param array<string, mixed> $file The `$_FILES` entry being uploaded.
	 *
	 * @return array<string, mixed> The (possibly error-flagged) file entry.
	 */
	public function validate_on_upload( array $file ): array {
		$name = isset( $file['name'] ) ? (string) $file['name'] : '';
		$tmp  = isset( $file['tmp_name'] ) ? (string) $file['tmp_name'] : '';
		$ext  = strtolower( (string) pathinfo( $name, PATHINFO_EXTENSION ) );

		if ( '' === $tmp ) {
			return $file;
		}

		if ( 'json' === $ext ) {
			$contents = (string) file_get_contents( $tmp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			json_decode( $contents );
			if ( JSON_ERROR_NONE !== json_last_error() ) {
				$file['error'] = esc_html__( 'This file is not valid JSON and was rejected.', 'squad-modules-for-divi' );
			}
		} elseif ( 'lottie' === $ext ) {
			$magic = (string) file_get_contents( $tmp, false, null, 0, 4 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			// ZIP local-file-header ("PK\x03\x04") or empty-archive marker ("PK\x05\x06").
			if ( "PK\x03\x04" !== $magic && "PK\x05\x06" !== $magic ) {
				$file['error'] = esc_html__( 'This .lottie file is not a valid archive and was rejected.', 'squad-modules-for-divi' );
			}
		}

		return $file;
	}
}
