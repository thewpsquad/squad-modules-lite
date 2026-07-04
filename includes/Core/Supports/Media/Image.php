<?php
namespace DiviSquad\Core\Supports\Media;

use DiviSquad\Core\Traits\UseWPFilesystem;
use RuntimeException;
use WP_Error;
use WP_Filesystem_Base;

/**
 * The Image Class with performance optimizations.
 *
 * @package DiviSquad
 * @since   3.0.0
 */
class Image {
	use UseWPFilesystem;

	/**
	 * Cache of loaded images.
	 *
	 * @var array
	 */
	protected array $images = array();

	/**
	 * KSES defaults for HTML filtering.
	 *
	 * @var array
	 */
	protected array $kses_defaults = array();

	/**
	 * Base image directory path.
	 *
	 * @var string
	 */
	protected string $path;

	/**
	 * Supported image types.
	 *
	 * @var array
	 */
	protected array $valid_types = array( 'png', 'jpg', 'jpeg', 'gif', 'svg' );

	/**
	 * Path validation status.
	 *
	 * @var bool|WP_Error
	 */
	protected $path_validated;

	/**
	 * File modification time cache.
	 *
	 * @var array
	 */
	protected array $mtime_cache = array();

	/**
	 * Constructor with enhanced initialization.
	 *
	 * @param string $path Base image directory path.
	 */
	public function __construct( string $path ) {
		$this->path           = rtrim( $path, '/' );
		$this->path_validated = $this->validate_path();
	}

	/**
	 * Get image with optimized loading and caching.
	 *
	 * @param string $image     Image filename.
	 * @param string $type      Image type.
	 * @param bool   $as_base64 Whether to return base64 encoded image.
	 *
	 * @return string|WP_Error Base64 encoded image, raw image data, or error.
	 */
	public function get_image( string $image, string $type, bool $as_base64 = true ) {
		try {
			if ( ! $this->path_validated ) {
				throw new RuntimeException(
					esc_html__( 'Image path is not valid or accessible.', 'squad-modules-for-divi' )
				);
			}

			// Generate cache keys
			$format    = $as_base64 ? 'base64' : 'raw';
			$image_key = "{$type}_{$image}_{$format}";
			$cache_key = "image_{$format}_" . md5( $this->path . $image_key );

			// Try memory cache first
			if ( isset( $this->images[ $image_key ] ) ) {
				return $this->images[ $image_key ];
			}

			// Check persistent cache
			$cached_data = divi_squad()->cache->get( $cache_key );
			if ( false !== $cached_data ) {
				$this->images[ $image_key ] = $cached_data;

				return $cached_data;
			}

			// Validate image type
			if ( ! $this->validate_image_type( $type ) ) {
				throw new RuntimeException(
					sprintf(
					// translators: %s: image type
						esc_html__( 'Image type (%s) is not supported.', 'squad-modules-for-divi' ),
						esc_html( $type )
					)
				);
			}

			// Get raw image data
			$image_data = $this->get_image_raw( $image );
			if ( is_wp_error( $image_data ) ) {
				throw new RuntimeException( $image_data->get_error_message() );
			}

			// Process image based on format
			$processed_image = $as_base64 ? $this->process_as_base64( $image_data, $type ) : $image_data;

			// Cache the processed image
			$this->images[ $image_key ] = $processed_image;
			divi_squad()->cache->set( $cache_key, $processed_image, 'divi-squad', HOUR_IN_SECONDS );

			return $processed_image;
		} catch ( RuntimeException $e ) {
			return new WP_Error( 'divi_squad_image_error', $e->getMessage() );
		}
	}

	/**
	 * Process image as base64.
	 *
	 * @param string $image_data Raw image data.
	 * @param string $type       Image type.
	 *
	 * @return string Base64 encoded image data.
	 */
	protected function process_as_base64( string $image_data, string $type ): string {
		// Convert SVG type for proper MIME type
		$mime_type = ( 'svg' === $type ) ? 'svg+xml' : $type;

		// Generate base64 data
		$base64_data = 'data:image/' . $mime_type . ';base64,' . base64_encode( $image_data );

		/**
		 * Filter the base64 encoded image data.
		 *
		 * @since 3.0.0
		 *
		 * @param string $base64_data The base64 encoded image data.
		 * @param string $type        The image type.
		 */
		return apply_filters( 'divi_squad_image_base64', $base64_data, $type );
	}

	/**
	 * Get raw image data.
	 *
	 * @param string $image Image filename.
	 *
	 * @return string|WP_Error Raw image data or error.
	 */
	protected function get_image_raw( string $image ) {
		try {
			$image_path = $this->path . '/' . $image;
			$cache_key  = 'image_raw_' . md5( $image_path );

			// Check cache first
			$cached_content = divi_squad()->cache->get( $cache_key );
			if ( false !== $cached_content ) {
				return $cached_content;
			}

			if ( ! $this->get_wp_fs()->exists( $image_path ) ) {
				throw new RuntimeException(
					sprintf(
					// translators: %s: image path
						esc_html__( 'Image file (%s) does not exist.', 'squad-modules-for-divi' ),
						esc_html( $image_path )
					)
				);
			}

			// Get file size for memory management
			$file_size = $this->get_wp_fs()->size( $image_path );
			if ( $file_size > wp_convert_hr_to_bytes( '64M' ) ) {
				throw new RuntimeException(
					sprintf(
					// translators: %s: image path
						esc_html__( 'Image file (%s) is too large to process.', 'squad-modules-for-divi' ),
						esc_html( $image_path )
					)
				);
			}

			// Read file with error handling
			$content = $this->get_wp_fs()->get_contents( $image_path );
			if ( false === $content ) {
				throw new RuntimeException(
					sprintf(
					// translators: %s: image path
						esc_html__( 'Failed to read image file (%s).', 'squad-modules-for-divi' ),
						esc_html( $image_path )
					)
				);
			}

			// Cache the content
			divi_squad()->cache->set( $cache_key, $content, 'divi-squad', HOUR_IN_SECONDS );

			return $content;

		} catch ( RuntimeException $e ) {
			return new WP_Error( 'divi_squad_image_raw_error', $e->getMessage() );
		}
	}

	/**
	 * Validate image type.
	 *
	 * @param string $type Image type to validate.
	 *
	 * @return bool Whether the type is valid.
	 */
	protected function validate_image_type( string $type ): bool {
		/**
		 * Filter the supported image types.
		 *
		 * @since 3.0.0
		 *
		 * @param array $supported_types The supported image types.
		 */
		$supported_types = apply_filters( 'divi_squad_image_supported_types', $this->valid_types );

		return in_array( $type, $supported_types, true );
	}

	/**
	 * Validate the image directory path.
	 *
	 * @return bool|WP_Error
	 */
	protected function validate_path() {
		try {
			if ( ! $this->get_wp_fs()->is_dir( $this->path ) ) {
				throw new RuntimeException(
					sprintf(
					// translators: %s: image path
						esc_html__( 'Image path (%s) is not a directory.', 'squad-modules-for-divi' ),
						esc_html( $this->path )
					)
				);
			}

			if ( ! $this->get_wp_fs()->is_readable( $this->path ) ) {
				throw new RuntimeException(
					sprintf(
					// translators: %s: image path
						esc_html__( 'Image path (%s) is not readable.', 'squad-modules-for-divi' ),
						esc_html( $this->path )
					)
				);
			}

			$cache_key = 'path_valid_' . md5( $this->path );
			divi_squad()->cache->set( $cache_key, true, 'divi-squad', DAY_IN_SECONDS );

			return true;

		} catch ( RuntimeException $e ) {
			return new WP_Error( 'divi_squad_path_validation', $e->getMessage() );
		}
	}

	/**
	 * Check if the image path is validated.
	 *
	 * @return bool|WP_Error
	 */
	public function is_path_validated() {
		if ( $this->path_validated instanceof WP_Error ) {
			return $this->path_validated;
		}

		$cache_key = 'path_valid_' . md5( $this->path );
		$is_valid  = divi_squad()->cache->get( $cache_key );

		if ( $is_valid === false ) {
			$this->path_validated = $this->validate_path();

			return $this->path_validated;
		}

		return true;
	}

	/**
	 * Clear the image cache.
	 *
	 * @return void
	 */
	public function clear_cache(): void {
		$this->images = array();

		// Clear path validation cache
		$path_cache_key = 'path_valid_' . md5( $this->path );
		divi_squad()->cache->delete( $path_cache_key, 'divi-squad' );

		// Clear HTML defaults cache
		$html_cache_key = 'divi_squad_kses_defaults';
		divi_squad()->cache->delete( $html_cache_key, 'divi-squad' );

		$this->kses_defaults = array();
	}

	/**
	 * Clear cache for a specific image.
	 *
	 * @param string $image Image filename.
	 * @param string $type  Image type.
	 *
	 * @return void
	 */
	public function clear_image_cache( string $image, string $type ): void {
		// Clear both raw and base64 versions
		$raw_key    = "{$type}_{$image}_raw";
		$base64_key = "{$type}_{$image}_base64";

		// Clear from static cache
		unset( $this->images[ $raw_key ], $this->images[ $base64_key ] );

		// Clear from persistent cache
		$raw_cache_key    = 'image_raw_' . md5( $this->path . $raw_key );
		$base64_cache_key = 'image_base64_' . md5( $this->path . $base64_key );

		divi_squad()->cache->delete( $raw_cache_key, 'divi-squad' );
		divi_squad()->cache->delete( $base64_cache_key, 'divi-squad' );
	}

	/**
	 * Get allowed HTML for image with enhanced caching.
	 *
	 * @return array
	 */
	public function get_image_allowed_html(): array {
		// Return cached defaults if available
		if ( array() !== $this->kses_defaults ) {
			return $this->kses_defaults;
		}

		// Try to get from persistent cache
		$cache_key       = 'divi_squad_kses_defaults';
		$cached_defaults = divi_squad()->cache->get( $cache_key );

		if ( false !== $cached_defaults ) {
			$this->kses_defaults = $cached_defaults;

			return $this->kses_defaults;
		}

		// Generate default allowed HTML
		$kses_defaults = wp_kses_allowed_html( 'post' );

		// Enhanced SVG support with security considerations
		$svg_args = array(
			'data'  => array(),
			'svg'   => array(
				'class'               => true,
				'aria-hidden'         => true,
				'aria-labelledby'     => true,
				'role'                => true,
				'xmlns'               => true,
				'width'               => true,
				'height'              => true,
				'viewbox'             => true,
				'preserveaspectratio' => true,
				'fill'                => true,
				'stroke'              => true,
				'stroke-width'        => true,
				'stroke-linecap'      => true,
				'stroke-linejoin'     => true,
				'xmlns:xlink'         => true,
			),
			'path'  => array(
				'd'    => true,
				'fill' => true,
				'id'   => true,
			),
			'g'     => array(
				'fill'      => true,
				'transform' => true,
				'id'        => true,
			),
			'title' => array(
				'title' => true,
				'id'    => true,
			),
			'desc'  => array(
				'id' => true,
			),
			'defs'  => array(
				'id' => true,
			),
			'stop'           => array(
				'offset'       => true,
				'stop-color'   => true,
				'stop-opacity' => true,
			),
			'lineargradient' => array(
				'id'            => true,
				'x1'            => true,
				'y1'            => true,
				'x2'            => true,
				'y2'            => true,
				'gradientUnits' => true,
			),
		);

		// Security: filter potentially dangerous attributes
		$filtered_svg_args = array();
		foreach ( $svg_args as $tag => $attributes ) {
			$filtered_svg_args[ sanitize_key( $tag ) ] = array_filter(
				$attributes,
				function ( $value, $key ) {
					return true === $value && ! preg_match( '/^on/i', $key );
				},
				ARRAY_FILTER_USE_BOTH
			);
		}

		// Merge and cache the results
		$this->kses_defaults = array_merge( $kses_defaults, $filtered_svg_args );
		divi_squad()->cache->set( $cache_key, $this->kses_defaults, 'divi-squad', DAY_IN_SECONDS );

		/**
		 * Filter the allowed HTML tags and attributes.
		 *
		 * @since 3.0.0
		 *
		 * @param array $allowed_html The allowed HTML tags and attributes.
		 */
		return apply_filters( 'divi_squad_image_allowed_html', $this->kses_defaults );
	}
}
