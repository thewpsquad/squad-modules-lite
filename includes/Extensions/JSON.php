<?php // phpcs:ignore WordPress.Files.FileName
/**
 * The JSON extension class for Divi Squad.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   1.2.0
 */

namespace DiviSquad\Extensions;

use DiviSquad\Base\Extension;
use function add_filter;
use function wp_strip_all_tags;

/**
 * The JSON class.
 *
 * @package DiviSquad
 * @since   1.2.0
 */
class JSON extends Extension {

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
			'json'   => 'application/json',
			'lottie' => 'application/zip',
		);
	}

	/**
	 * Filters the "real" file type of the given file.
	 *
	 * @param array  $wp_checked Values for the extension, mime type, and corrected filename.
	 * @param string $file       Full path to the file.
	 * @param string $filename   The name of the file.
	 */
	public function hook_wp_check_filetype_and_ext( array $wp_checked, string $file, string $filename ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClassBeforeLastUsed
		$ext             = false;
		$type            = false;
		$proper_filename = false;
		if ( isset( $wp_checked['ext'] ) ) {
			$ext = $wp_checked['ext'];
		}
		if ( isset( $wp_checked['type'] ) ) {
			$ext = $wp_checked['type'];
		}
		if ( isset( $wp_checked['proper_filename'] ) ) {
			$ext = $wp_checked['proper_filename'];
		}
		if ( false !== $ext ) {
			return $wp_checked;
		}

		// If a file extension is 2 or more.
		$f_sp        = explode( '.', $filename );
		$f_exp_count = count( $f_sp );

		// Filename type is "XXX" (There is not a file extension).
		if ( $f_exp_count <= 1 ) {
			return $wp_checked;
		}

		$f_ext = $f_sp[ $f_exp_count - 1 ];

		$flag             = false;
		$mime_type_values = array_keys( $this->get_available_mime_types() );
		if ( ! empty( $mime_type_values ) ) {
			foreach ( $mime_type_values as $line ) {
				// Ignore to the right of '#' on a line.
				$line = substr( $line, 0, strcspn( $line, '#' ) );
				// Escape Strings.
				$line = wp_strip_all_tags( $line );

				$line_value = explode( '=', $line );
				if ( 2 !== count( $line_value ) ) {
					continue;
				}
				// "　" is the Japanese multibyte space. If the character is found out, it automatically change the space.
				if ( trim( $line_value[0] ) === $f_ext ) {
					$ext  = $f_ext;
					$type = trim( str_replace( '　', ' ', $line_value[1] ) );
					$flag = true;
					break;
				}
			}
		}

		if ( $flag ) {
			return compact( 'ext', 'type', 'proper_filename' );
		}

		return $wp_checked;
	}

	/**
	 * Get the extension name.
	 *
	 * @return string
	 */
	protected function get_name(): string {
		return 'JSON';
	}

	/**
	 * Load the extension.
	 *
	 * @return void
	 */
	protected function load(): void {
		add_filter( 'mime_types', array( $this, 'hook_add_extra_mime_types' ) );
		add_filter( 'upload_mimes', array( $this, 'hook_add_extra_mime_types' ) );
		add_filter( 'wp_check_filetype_and_ext', array( $this, 'hook_wp_check_filetype_and_ext' ), 10, 3 );
	}
}
