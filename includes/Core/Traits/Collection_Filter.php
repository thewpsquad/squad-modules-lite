<?php // phpcs:ignore WordPress.Files.FileName
/**
 * Collection Filter trait for filtering arrays with callbacks.
 *
 * @since   3.4.5
 * @package DiviSquad
 */

namespace DiviSquad\Core\Traits;

use Throwable;

/**
 * Collection Filter trait.
 *
 * Provides functionality to filter collections (modules, extensions, etc.)
 * using callback functions. This trait centralizes the duplicate filter logic
 * that was previously present in both Modules and Extensions classes.
 *
 * @since   3.4.5
 * @package DiviSquad
 */
trait Collection_Filter {

	/**
	 * Filter items in a collection based on callback
	 *
	 * This method provides a safe wrapper around array_filter that includes
	 * error handling and allows filtering with custom callback functions.
	 *
	 * @since 3.4.5
	 *
	 * @param array<string, mixed> $collection The collection to filter.
	 * @param callable             $callback   Function to filter items.
	 *
	 * @return array<string, mixed> Filtered collection.
	 */
	protected function filter_collection( array $collection, callable $callback ): array {
		try {
			return array_filter(
				$collection,
				static function ( $item, $key ) use ( $callback ) {
					return $callback( $item, $key );
				},
				ARRAY_FILTER_USE_BOTH
			);
		} catch ( Throwable $e ) {
			// Log error if logging is available.
			if ( function_exists( 'divi_squad' ) && method_exists( divi_squad(), 'log_error' ) ) {
				divi_squad()->log_error( $e, 'Failed to filter collection' );
			}

			return array();
		}
	}
}
