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
use DOMElement;
use function add_filter;
use function current_user_can;
use function esc_html__;
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
		add_filter( 'wp_handle_upload_prefilter', array( $this, 'sanitize_on_upload' ) );
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
		// An SVG can carry executable script, so only allow it for users who may
		// already publish raw HTML/JS (unfiltered_html). This blocks lower-
		// privileged uploaders from planting a stored-XSS payload.
		if ( ! current_user_can( 'unfiltered_html' ) ) {
			return array();
		}

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
		// Mirror the capability gate from get_available_mime_types().
		if ( ! current_user_can( 'unfiltered_html' ) ) {
			return $wp_check;
		}

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

	/**
	 * Sanitize an uploaded SVG before it is stored.
	 *
	 * Runs on `wp_handle_upload_prefilter`. Strips scripts, event handlers,
	 * `javascript:`/`data:` URLs, `<foreignObject>` and animation-based vectors,
	 * and external entity declarations (XXE) from the SVG markup. Rejects the
	 * upload if the file cannot be parsed as SVG.
	 *
	 * @since 3.4.2
	 *
	 * @param array<string, mixed> $file The `$_FILES` entry being uploaded.
	 *
	 * @return array<string, mixed> The (possibly error-flagged) file entry.
	 */
	public function sanitize_on_upload( array $file ): array {
		$name = isset( $file['name'] ) ? (string) $file['name'] : '';
		$tmp  = isset( $file['tmp_name'] ) ? (string) $file['tmp_name'] : '';

		if ( '' === $tmp || 'svg' !== strtolower( (string) pathinfo( $name, PATHINFO_EXTENSION ) ) ) {
			return $file;
		}

		$markup = (string) file_get_contents( $tmp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$clean  = $this->sanitize_svg_markup( $markup );

		if ( null === $clean ) {
			$file['error'] = esc_html__( 'This SVG could not be processed and was rejected for security reasons.', 'squad-modules-for-divi' );

			return $file;
		}

		file_put_contents( $tmp, $clean ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_put_contents_file_put_contents

		return $file;
	}

	/**
	 * Sanitize raw SVG markup with a DOM pass.
	 *
	 * @since 3.4.2
	 *
	 * @param string $markup Raw SVG file contents.
	 *
	 * @return string|null Sanitized SVG, or null when it cannot be parsed as SVG.
	 */
	protected function sanitize_svg_markup( string $markup ): ?string {
		$markup = trim( $markup );
		if ( '' === $markup ) {
			return null;
		}

		// Remove DOCTYPE / ENTITY declarations before parsing (XXE / entity expansion).
		$markup = (string) preg_replace( '/<!(?:DOCTYPE|ENTITY)\b[^>]*>/i', '', $markup );

		$dom = new \DOMDocument();

		$previous = libxml_use_internal_errors( true );
		$loaded   = $dom->loadXML( $markup, LIBXML_NONET );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		if ( false === $loaded || ! ( $dom->documentElement instanceof DOMElement ) ) {
			return null;
		}

		if ( 'svg' !== strtolower( $dom->documentElement->nodeName ) ) {
			return null;
		}

		$this->scrub_svg_node( $dom->documentElement );

		$out = $dom->saveXML( $dom->documentElement );

		return is_string( $out ) ? $out : null;
	}

	/**
	 * Recursively strip dangerous elements and attributes from an SVG node.
	 *
	 * @since 3.4.2
	 *
	 * @param DOMElement $node The element to scrub (mutated in place).
	 *
	 * @return void
	 */
	protected function scrub_svg_node( DOMElement $node ): void {
		$blocked_tags = array(
			'script', 'foreignobject', 'iframe', 'embed', 'object',
			'audio', 'video', 'animate', 'animatetransform', 'animatemotion',
			'set', 'handler', 'listener',
		);

		// Snapshot children first — removal mutates the live node list.
		$children = array();
		foreach ( $node->childNodes as $child ) {
			$children[] = $child;
		}
		foreach ( $children as $child ) {
			if ( ! ( $child instanceof DOMElement ) ) {
				continue;
			}
			if ( in_array( strtolower( (string) $child->localName ), $blocked_tags, true ) ) {
				$node->removeChild( $child );
				continue;
			}
			$this->scrub_svg_node( $child );
		}

		if ( ! $node->hasAttributes() ) {
			return;
		}

		$attributes = array();
		foreach ( $node->attributes as $attribute ) {
			$attributes[] = $attribute;
		}
		foreach ( $attributes as $attribute ) {
			$attr_name  = strtolower( (string) $attribute->nodeName );
			$attr_value = (string) $attribute->nodeValue;

			$is_event   = 0 === strpos( $attr_name, 'on' );
			$is_href    = in_array( $attr_name, array( 'href', 'xlink:href', 'src' ), true ) || 'href' === (string) $attribute->localName;
			$bad_scheme = 1 === preg_match( '/^\s*(?:javascript|vbscript|data)\s*:/i', $attr_value );

			if ( $is_event || ( $is_href && $bad_scheme ) ) {
				$node->removeAttributeNode( $attribute );
			}
		}
	}
}
