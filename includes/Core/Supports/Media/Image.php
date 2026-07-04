<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Class for managing image operations with performance optimizations.
 *
 * This file contains the Image class which handles image loading, processing,
 * caching, and validation with a focus on performance and security.
 *
 * @since   3.0.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Core\Supports\Media;

use DiviSquad\Core\Traits\WP\Use_WP_Filesystem;
use RuntimeException;
use Throwable;
use WP_Error;

/**
 * The Image Class with performance optimizations.
 *
 * Provides functionality for loading, caching, and processing images with
 * validation, security measures, and performance optimization techniques.
 *
 * @since   3.0.0
 * @package DiviSquad\Core\Supports\Media
 */
class Image {
	use Use_WP_Filesystem;

	/**
	 * Cache of loaded images.
	 *
	 * @since 3.0.0
	 * @var array<string, string>
	 */
	protected array $images = array();

	/**
	 * KSES defaults for HTML filtering.
	 *
	 * @since 3.0.0
	 * @var array<string, array<string, bool>>
	 */
	protected array $kses_defaults = array();

	/**
	 * Base image directory path.
	 *
	 * @since 3.0.0
	 * @var string
	 */
	protected string $path;

	/**
	 * Supported image types.
	 *
	 * @since 3.0.0
	 * @var array<string>
	 */
	protected array $valid_types = array( 'png', 'jpg', 'jpeg', 'gif', 'svg' );

	/**
	 * Path validation status.
	 *
	 * @since 3.0.0
	 * @var bool
	 */
	protected bool $path_validated;

	/**
	 * Constructor with enhanced initialization.
	 *
	 * @since 3.0.0
	 *
	 * @param string $path Base image directory path.
	 */
	public function __construct( string $path ) {
		$this->path           = rtrim( $path, '/' );
		$this->path_validated = $this->validate_path();

		/**
		 * Fires after Image class initialization.
		 *
		 * Allows other components to perform actions after the Image class is initialized.
		 *
		 * @since 3.0.0
		 *
		 * @param Image  $this     The Image instance.
		 * @param string $path     The base image directory path.
		 * @param bool   $is_valid Whether the path is validated.
		 */
		do_action( 'divi_squad_image_initialized', $this, $this->path, $this->path_validated );
	}

	/**
	 * Get image with optimized loading and caching.
	 *
	 * Retrieves an image with appropriate caching mechanisms and
	 * provides options for base64 encoding.
	 *
	 * @since 3.0.0
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

			/**
			 * Filters the image request before processing.
			 *
			 * Allows modification or short-circuiting of the image request before any processing occurs.
			 * Return a non-null value to short-circuit the default behavior.
			 *
			 * @since 3.0.0
			 *
			 * @param null|string|WP_Error $pre_result     Default null. Return a non-null value to short-circuit.
			 * @param string               $image          The image filename.
			 * @param string               $type           The image type.
			 * @param bool                 $as_base64      Whether to return base64 encoded image.
			 * @param Image                $image_instance The Image instance.
			 */
			$pre_result = apply_filters( 'divi_squad_pre_get_image', null, $image, $type, $as_base64, $this );
			if ( null !== $pre_result ) {
				return $pre_result;
			}

			// Generate cache keys.
			$format    = $as_base64 ? 'base64' : 'raw';
			$image_key = "{$type}_{$image}_{$format}";
			$cache_key = "image_{$format}_" . md5( $this->path . $image_key );

			// Try memory cache first.
			if ( isset( $this->images[ $image_key ] ) ) {
				return $this->images[ $image_key ];
			}

			// Check persistent cache
			$cached_data = divi_squad()->cache->get( $cache_key );
			if ( false !== $cached_data ) {
				$this->images[ $image_key ] = $cached_data;

				return $cached_data;
			}

			// Validate image type.
			if ( ! $this->validate_image_type( $type ) ) {
				throw new RuntimeException(
					sprintf(
					// translators: %s: image type.
						esc_html__( 'Image type (%s) is not supported.', 'squad-modules-for-divi' ),
						esc_html( $type )
					)
				);
			}

			// Get raw image data.
			$image_data = $this->get_image_raw( $image );
			if ( is_wp_error( $image_data ) ) {
				throw new RuntimeException( $image_data->get_error_message() );
			}

			// Process image based on format.
			$processed_image = $as_base64 ? $this->process_as_base64( $image_data, $type ) : $image_data;

			/**
			 * Filters the processed image data.
			 *
			 * Allows modification of the processed image data before it's cached and returned.
			 *
			 * @since 3.0.0
			 *
			 * @param string $processed_image The processed image data.
			 * @param string $image           The image filename.
			 * @param string $type            The image type.
			 * @param bool   $as_base64       Whether the image is base64 encoded.
			 * @param Image  $image_instance  The Image instance.
			 */
			$processed_image = apply_filters( 'divi_squad_processed_image', $processed_image, $image, $type, $as_base64, $this );

			// Cache the processed image.
			$this->images[ $image_key ] = $processed_image;
			divi_squad()->cache->set( $cache_key, $processed_image, 'divi-squad', HOUR_IN_SECONDS );

			/**
			 * Fires after an image is successfully retrieved.
			 *
			 * @since 3.0.0
			 *
			 * @param string $processed_image The processed image data.
			 * @param string $image           The image filename.
			 * @param string $type            The image type.
			 * @param bool   $as_base64       Whether the image is base64 encoded.
			 * @param string $cache_key       The cache key used for this image.
			 */
			do_action( 'divi_squad_after_get_image', $processed_image, $image, $type, $as_base64, $cache_key );

			return $processed_image;
		} catch ( RuntimeException $e ) {
			divi_squad()->log_error(
				$e,
				'Image retrieval error',
				true,
				array(
					'image'     => $image,
					'type'      => $type,
					'as_base64' => $as_base64,
					'message'   => $e->getMessage(),
				)
			);

			/**
			 * Fires when an image retrieval error occurs.
			 *
			 * @since 3.0.0
			 *
			 * @param RuntimeException $e              The exception object.
			 * @param string           $image          The image filename.
			 * @param string           $type           The image type.
			 * @param bool             $as_base64      Whether base64 encoding was requested.
			 * @param Image            $image_instance The Image instance.
			 */
			do_action( 'divi_squad_image_retrieval_error', $e, $image, $type, $as_base64, $this );

			return new WP_Error( 'divi_squad_image_error', $e->getMessage() );
		}
	}

	/**
	 * Process image as base64.
	 *
	 * Converts raw image data to a base64 encoded string with
	 * appropriate MIME type handling.
	 *
	 * @since 3.0.0
	 *
	 * @param string $image_data Raw image data.
	 * @param string $type       Image type.
	 *
	 * @return string Base64 encoded image data.
	 */
	protected function process_as_base64( string $image_data, string $type ): string {
		try {
			// Convert SVG type for proper MIME type.
			$mime_type = ( 'svg' === $type ) ? 'svg+xml' : $type;

			/**
			 * Filters the MIME type used for base64 encoding.
			 *
			 * @since 3.0.0
			 *
			 * @param string $mime_type      The MIME type to use for the image.
			 * @param string $type           The original image type.
			 * @param Image  $image_instance The Image instance.
			 */
			$mime_type = apply_filters( 'divi_squad_image_mime_type', $mime_type, $type, $this );

			// Generate base64 data.
			$base64_data = 'data:image/' . $mime_type . ';base64,' . base64_encode( $image_data ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64, WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

			/**
			 * Filters the base64 encoded image data.
			 *
			 * Allows modification of the base64 encoded image data before it's returned.
			 *
			 * @since 3.0.0
			 *
			 * @param string $base64_data    The base64 encoded image data.
			 * @param string $type           The image type.
			 * @param Image  $image_instance The Image instance.
			 */
			return apply_filters( 'divi_squad_image_base64', $base64_data, $type, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error(
				$e,
				'Base64 processing error',
				true,
				array(
					'type'    => $type,
					'message' => $e->getMessage(),
				)
			);

			/**
			 * Fires when a base64 processing error occurs.
			 *
			 * @since 3.0.0
			 *
			 * @param Throwable $e              The exception object.
			 * @param string    $type           The image type.
			 * @param Image     $image_instance The Image instance.
			 */
			do_action( 'divi_squad_base64_processing_error', $e, $type, $this );

			// Return empty string in case of error
			return '';
		}
	}

	/**
	 * Get raw image data.
	 *
	 * Retrieves the raw content of an image file with caching and
	 * comprehensive error handling.
	 *
	 * @since 3.0.0
	 *
	 * @param string $image Image filename.
	 *
	 * @return string|WP_Error Raw image data or error.
	 */
	protected function get_image_raw( string $image ) {
		try {
			$image_path = $this->path . '/' . $image;
			$cache_key  = 'image_raw_' . md5( $image_path );

			/**
			 * Filters the raw image request before processing.
			 *
			 * Allows modification or short-circuiting of the raw image request before any processing occurs.
			 * Return a non-null value to short-circuit the default behavior.
			 *
			 * @since 3.0.0
			 *
			 * @param null|string|WP_Error $pre_result     Default null. Return a non-null value to short-circuit.
			 * @param string               $image          The image filename.
			 * @param string               $image_path     The full image path.
			 * @param Image                $image_instance The Image instance.
			 */
			$pre_result = apply_filters( 'divi_squad_pre_get_image_raw', null, $image, $image_path, $this );
			if ( null !== $pre_result ) {
				return $pre_result;
			}

			// Check cache first.
			$cached_content = divi_squad()->cache->get( $cache_key );
			if ( false !== $cached_content ) {
				return $cached_content;
			}

			if ( ! $this->get_wp_fs()->exists( $image_path ) ) {
				throw new RuntimeException(
					sprintf(
					// translators: %s: image path.
						esc_html__( 'Image file (%s) does not exist.', 'squad-modules-for-divi' ),
						esc_html( $image_path )
					)
				);
			}

			// Get file size for memory management.
			$file_size = $this->get_wp_fs()->size( $image_path );

			/**
			 * Filters the maximum allowed image file size.
			 *
			 * @since 3.0.0
			 *
			 * @param int    $max_size       The maximum allowed file size in bytes.
			 * @param string $image          The image filename.
			 * @param string $image_path     The full image path.
			 * @param Image  $image_instance The Image instance.
			 */
			$max_file_size = apply_filters( 'divi_squad_max_image_file_size', wp_convert_hr_to_bytes( '64M' ), $image, $image_path, $this );

			if ( $file_size > $max_file_size ) {
				throw new RuntimeException(
					sprintf(
					// translators: %s: image path.
						esc_html__( 'Image file (%s) is too large to process.', 'squad-modules-for-divi' ),
						esc_html( $image_path )
					)
				);
			}

			// Read file with error handling.
			$content = $this->get_wp_fs()->get_contents( $image_path );
			if ( false === $content ) {
				throw new RuntimeException(
					sprintf(
					// translators: %s: image path.
						esc_html__( 'Failed to read image file (%s).', 'squad-modules-for-divi' ),
						esc_html( $image_path )
					)
				);
			}

			/**
			 * Filters the raw image content before caching.
			 *
			 * @since 3.0.0
			 *
			 * @param string $content        The raw image content.
			 * @param string $image          The image filename.
			 * @param string $image_path     The full image path.
			 * @param Image  $image_instance The Image instance.
			 */
			$content = apply_filters( 'divi_squad_raw_image_content', $content, $image, $image_path, $this );

			// Cache the content.
			$cache_duration = apply_filters( 'divi_squad_image_cache_duration', HOUR_IN_SECONDS, $image, $image_path, $this );
			divi_squad()->cache->set( $cache_key, $content, 'divi-squad', $cache_duration );

			/**
			 * Fires after raw image data is successfully retrieved.
			 *
			 * @since 3.0.0
			 *
			 * @param string $content    The raw image content.
			 * @param string $image      The image filename.
			 * @param string $image_path The full image path.
			 * @param string $cache_key  The cache key used for this image.
			 */
			do_action( 'divi_squad_after_get_image_raw', $content, $image, $image_path, $cache_key );

			return $content;

		} catch ( RuntimeException $e ) {
			divi_squad()->log_error(
				$e,
				'Raw image retrieval error',
				true,
				array(
					'image'   => $image,
					'path'    => $this->path,
					'message' => $e->getMessage(),
				)
			);

			/**
			 * Fires when a raw image retrieval error occurs.
			 *
			 * @since 3.0.0
			 *
			 * @param RuntimeException $e              The exception object.
			 * @param string           $image          The image filename.
			 * @param Image            $image_instance The Image instance.
			 */
			do_action( 'divi_squad_raw_image_retrieval_error', $e, $image, $this );

			return new WP_Error( 'divi_squad_image_raw_error', $e->getMessage() );
		}
	}

	/**
	 * Validate image type.
	 *
	 * Checks if the provided image type is in the list of supported types.
	 *
	 * @since 3.0.0
	 *
	 * @param string $type Image type to validate.
	 *
	 * @return bool Whether the type is valid.
	 */
	protected function validate_image_type( string $type ): bool {
		/**
		 * Filters the supported image types.
		 *
		 * Allows modification of the list of supported image types.
		 *
		 * @since 3.0.0
		 *
		 * @param array  $supported_types The supported image types.
		 * @param string $type            The type being validated.
		 * @param Image  $image_instance  The Image instance.
		 */
		$supported_types = apply_filters( 'divi_squad_image_supported_types', $this->valid_types, $type, $this );

		return in_array( $type, $supported_types, true );
	}

	/**
	 * Validate the image directory path.
	 *
	 * Checks if the image directory path is valid, exists, and is readable.
	 *
	 * @since 3.0.0
	 *
	 * @return bool Whether the path is valid.
	 */
	protected function validate_path(): bool {
		try {
			/**
			 * Filters the path validation before processing.
			 *
			 * Allows short-circuiting of the path validation process.
			 * Return a non-null boolean value to short-circuit the default validation.
			 *
			 * @since 3.0.0
			 *
			 * @param null|bool $pre_result     Default null. Return a boolean to short-circuit.
			 * @param string    $path           The image directory path.
			 * @param Image     $image_instance The Image instance.
			 */
			$pre_result = apply_filters( 'divi_squad_pre_validate_image_path', null, $this->path, $this );
			if ( null !== $pre_result ) {
				return (bool) $pre_result;
			}

			if ( ! $this->get_wp_fs()->is_dir( $this->path ) ) {
				throw new RuntimeException(
					sprintf(
					// translators: %s: image path.
						esc_html__( 'Image path (%s) is not a directory.', 'squad-modules-for-divi' ),
						esc_html( $this->path )
					)
				);
			}

			if ( ! $this->get_wp_fs()->is_readable( $this->path ) ) {
				throw new RuntimeException(
					sprintf(
					// translators: %s: image path.
						esc_html__( 'Image path (%s) is not readable.', 'squad-modules-for-divi' ),
						esc_html( $this->path )
					)
				);
			}

			$cache_key = 'path_valid_' . md5( $this->path );
			divi_squad()->cache->set( $cache_key, true, 'divi-squad', DAY_IN_SECONDS );

			/**
			 * Fires after a path is successfully validated.
			 *
			 * @since 3.0.0
			 *
			 * @param string $path           The image directory path.
			 * @param string $cache_key      The cache key used for path validation.
			 * @param Image  $image_instance The Image instance.
			 */
			do_action( 'divi_squad_after_path_validation', $this->path, $cache_key, $this );

			return true;
		} catch ( RuntimeException $e ) {
			divi_squad()->log_warning(
				$e,
				'Image path validation failed',
				array(
					'path'    => $this->path,
					'message' => $e->getMessage(),
				)
			);

			/**
			 * Fires when a path validation error occurs.
			 *
			 * @since 3.0.0
			 *
			 * @param RuntimeException $e              The exception object.
			 * @param string           $path           The image directory path.
			 * @param Image            $image_instance The Image instance.
			 */
			do_action( 'divi_squad_path_validation_error', $e, $this->path, $this );

			return false;
		}
	}

	/**
	 * Check if the image path is validated.
	 *
	 * Verifies if the path has been validated, with cache support.
	 *
	 * @since 3.0.0
	 *
	 * @return bool Whether the path is validated.
	 */
	public function is_path_validated(): bool {
		try {
			if ( ! $this->path_validated ) {
				return false;
			}

			$cache_key = 'path_valid_' . md5( $this->path );
			$is_valid  = divi_squad()->cache->get( $cache_key );

			if ( false === $is_valid ) {
				$this->path_validated = $this->validate_path();

				return $this->path_validated;
			}

			return true;
		} catch ( Throwable $e ) {
			divi_squad()->log_error(
				$e,
				'Path validation check error',
				true,
				array(
					'path'    => $this->path,
					'message' => $e->getMessage(),
				)
			);

			return false;
		}
	}

	/**
	 * Clear the image cache.
	 *
	 * Removes all cached images and related cache entries.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function clear_cache(): void {
		try {
			/**
			 * Fires before image cache is cleared.
			 *
			 * @since 3.0.0
			 *
			 * @param array  $images         The cached images array.
			 * @param string $path           The image directory path.
			 * @param Image  $image_instance The Image instance.
			 */
			do_action( 'divi_squad_before_clear_image_cache', $this->images, $this->path, $this );

			$this->images = array();

			// Clear path validation cache.
			$path_cache_key = 'path_valid_' . md5( $this->path );
			divi_squad()->cache->delete( $path_cache_key, 'divi-squad' );

			// Clear HTML defaults cache.
			$html_cache_key = 'divi_squad_kses_defaults';
			divi_squad()->cache->delete( $html_cache_key, 'divi-squad' );

			$this->kses_defaults = array();

			/**
			 * Fires after image cache is cleared.
			 *
			 * @since 3.0.0
			 *
			 * @param string $path           The image directory path.
			 * @param Image  $image_instance The Image instance.
			 */
			do_action( 'divi_squad_after_clear_image_cache', $this->path, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error(
				$e,
				'Cache clearing error',
				true,
				array(
					'message' => $e->getMessage(),
				)
			);

			/**
			 * Fires when a cache clearing error occurs.
			 *
			 * @since 3.0.0
			 *
			 * @param Throwable $e              The exception object.
			 * @param Image     $image_instance The Image instance.
			 */
			do_action( 'divi_squad_cache_clearing_error', $e, $this );
		}
	}

	/**
	 * Clear cache for a specific image.
	 *
	 * Removes cached entries for a specific image.
	 *
	 * @since 3.0.0
	 *
	 * @param string $image Image filename.
	 * @param string $type  Image type.
	 *
	 * @return void
	 */
	public function clear_image_cache( string $image, string $type ): void {
		try {
			/**
			 * Fires before a specific image cache is cleared.
			 *
			 * @since 3.0.0
			 *
			 * @param string $image          The image filename.
			 * @param string $type           The image type.
			 * @param string $path           The image directory path.
			 * @param Image  $image_instance The Image instance.
			 */
			do_action( 'divi_squad_before_clear_specific_image_cache', $image, $type, $this->path, $this );

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

			/**
			 * Fires after a specific image cache is cleared.
			 *
			 * @since 3.0.0
			 *
			 * @param string $image            The image filename.
			 * @param string $type             The image type.
			 * @param string $raw_cache_key    The raw image cache key.
			 * @param string $base64_cache_key The base64 image cache key.
			 * @param Image  $image_instance   The Image instance.
			 */
			do_action( 'divi_squad_after_clear_specific_image_cache', $image, $type, $raw_cache_key, $base64_cache_key, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error(
				$e,
				'Image cache clearing error',
				true,
				array(
					'image'   => $image,
					'type'    => $type,
					'message' => $e->getMessage(),
				)
			);

			/**
			 * Fires when a specific image cache clearing error occurs.
			 *
			 * @since 3.0.0
			 *
			 * @param Throwable $e              The exception object.
			 * @param string    $image          The image filename.
			 * @param string    $type           The image type.
			 * @param Image     $image_instance The Image instance.
			 */
			do_action( 'divi_squad_specific_image_cache_clearing_error', $e, $image, $type, $this );
		}
	}

	/**
	 * Get allowed HTML for image with enhanced caching.
	 *
	 * Provides a security-enhanced list of allowed HTML tags and
	 * attributes for image processing.
	 *
	 * @since 3.0.0
	 *
	 * @return array<string, array<string, bool>> Allowed HTML tags and attributes.
	 */
	public function get_image_allowed_html(): array {
		try {
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
				'data'           => array(),
				'svg'            => array(
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
				'path'           => array(
					'd'    => true,
					'fill' => true,
					'id'   => true,
				),
				'g'              => array(
					'fill'      => true,
					'transform' => true,
					'id'        => true,
				),
				'title'          => array(
					'title' => true,
					'id'    => true,
				),
				'desc'           => array(
					'id' => true,
				),
				'defs'           => array(
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

			/**
			 * Filters the SVG attributes to allow in HTML.
			 *
			 * @since 3.0.0
			 *
			 * @param array $svg_args       The SVG attributes to allow.
			 * @param Image $image_instance The Image instance.
			 */
			$svg_args = apply_filters( 'divi_squad_allowed_svg_attributes', $svg_args, $this );

			// Security: filter potentially dangerous attributes
			$filtered_svg_args = array();
			foreach ( $svg_args as $tag => $attributes ) {
				$filtered_svg_args[ sanitize_key( $tag ) ] = array_filter(
					$attributes,
					static function ( $value, $key ) {
						return true === $value && 0 !== stripos( $key, 'on' );
					},
					ARRAY_FILTER_USE_BOTH
				);
			}

			// Merge and cache the results
			$this->kses_defaults = array_merge( $kses_defaults, $filtered_svg_args );
			divi_squad()->cache->set( $cache_key, $this->kses_defaults, 'divi-squad', DAY_IN_SECONDS );

			/**
			 * Filters the allowed HTML tags and attributes.
			 *
			 * Allows modification of the allowed HTML tags and attributes for image processing.
			 *
			 * @since 3.0.0
			 *
			 * @param array $allowed_html   The allowed HTML tags and attributes.
			 * @param Image $image_instance The Image instance.
			 */
			return apply_filters( 'divi_squad_image_allowed_html', $this->kses_defaults, $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error(
				$e,
				'Allowed HTML retrieval error',
				true,
				array(
					'message' => $e->getMessage(),
				)
			);

			/**
			 * Fires when an allowed HTML retrieval error occurs.
			 *
			 * @since 3.0.0
			 *
			 * @param Throwable $e              The exception object.
			 * @param Image     $image_instance The Image instance.
			 */
			do_action( 'divi_squad_allowed_html_retrieval_error', $e, $this );

			return wp_kses_allowed_html( 'post' ); // Fallback to basic allowed HTML
		}
	}
}
