<?php // phpcs:ignore WordPress.Files.FileName

/**
 * The Font Upload extension class for Divi Squad.
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
 * The Font Upload class.
 *
 * @since   1.2.0
 * @package DiviSquad
 */
class Font_Upload extends Base_Extension {

	/**
	 * Get the extension name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'Font_Upload';
	}

	/**
	 * Load the extension.
	 *
	 * @return void
	 */
	public function load(): void {
		add_filter( 'mime_types', array( $this, 'hook_add_extra_mime_types' ) );
		add_filter( 'upload_mimes', array( $this, 'hook_add_extra_mime_types' ) );
		add_filter( 'wp_handle_upload_prefilter', array( $this, 'validate_font_upload' ) );
	}

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
	 * Verify an uploaded font by its file signature.
	 *
	 * The MIME allow-list accepts `application/octet-stream` (many servers report
	 * fonts that way), which on its own would let any binary through under a font
	 * extension. This reads the file's magic bytes and rejects anything that is
	 * not a real font, so a disguised payload renamed `.ttf`/`.otf`/… is blocked.
	 *
	 * @since 3.4.2
	 *
	 * @param array<string, mixed> $file The `$_FILES` entry being uploaded.
	 *
	 * @return array<string, mixed> The (possibly error-flagged) file entry.
	 */
	public function validate_font_upload( array $file ): array {
		$name = isset( $file['name'] ) ? (string) $file['name'] : '';
		$tmp  = isset( $file['tmp_name'] ) ? (string) $file['tmp_name'] : '';
		$ext  = strtolower( (string) pathinfo( $name, PATHINFO_EXTENSION ) );

		if ( '' === $tmp || ! in_array( $ext, array( 'ttf', 'otf', 'woff', 'woff2' ), true ) ) {
			return $file;
		}

		$magic = (string) file_get_contents( $tmp, false, null, 0, 4 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		if ( ! $this->is_valid_font_signature( $ext, $magic ) ) {
			$file['error'] = esc_html__( 'This file does not appear to be a valid font and was rejected.', 'squad-modules-for-divi' );
		}

		return $file;
	}

	/**
	 * Whether the first bytes match a known font signature for the extension.
	 *
	 * @since 3.4.2
	 *
	 * @param string $ext   Lower-case file extension.
	 * @param string $magic First four bytes of the file.
	 *
	 * @return bool
	 */
	protected function is_valid_font_signature( string $ext, string $magic ): bool {
		// SFNT (TrueType/OpenType) container signatures.
		$sfnt = array( "\x00\x01\x00\x00", 'true', 'ttcf', 'typ1', 'OTTO' );

		switch ( $ext ) {
			case 'ttf':
			case 'otf':
				return in_array( $magic, $sfnt, true );
			case 'woff':
				return 'wOFF' === $magic;
			case 'woff2':
				return 'wOF2' === $magic;
			default:
				return false;
		}
	}
}
