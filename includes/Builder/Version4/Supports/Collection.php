<?php // phpcs:ignore WordPress.Files.FileName

namespace DiviSquad\Builder\Version4\Supports;

use ArrayAccess;
use Countable;
use IteratorAggregate;

class Collection implements ArrayAccess, Countable, IteratorAggregate {
	/**
	 * The items contained in the collection.
	 *
	 * @var array
	 */
	protected $items = array();

	/**
	 * Create a new collection.
	 *
	 * @param array $items
	 */
	public function __construct( array $items = array() ) {
		/**
		 * Filter the items passed to the collection constructor.
		 *
		 * @since 3.3.3
		 *
		 * @param array      $items      The items to be stored in the collection.
		 * @param Collection $collection The collection instance.
		 */
		$this->items = apply_filters( 'divi_squad_collection_constructor_items', $items, $this );

		/**
		 * Action triggered after a collection is initialized.
		 *
		 * @since 3.3.3
		 *
		 * @param Collection $collection The collection instance.
		 * @param array      $items      The items stored in the collection.
		 */
		do_action( 'divi_squad_collection_after_init', $this, $this->items );
	}

	/**
	 * Get all items in the collection.
	 *
	 * @return array
	 */
	public function all(): array {
		/**
		 * Filter all items in the collection.
		 *
		 * @since 3.3.3
		 *
		 * @param array      $items      The items stored in the collection.
		 * @param Collection $collection The collection instance.
		 */
		return apply_filters( 'divi_squad_collection_all_items', $this->items, $this );
	}

	/**
	 * Get an item from the collection by key.
	 *
	 * @param mixed $key
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function get( $key, $default = null ) {
		$value = $this->has( $key ) ? $this->items[ $key ] : $default;

		/**
		 * Filter the value retrieved from the collection.
		 *
		 * @since 3.3.3
		 *
		 * @param mixed      $value      The value retrieved from the collection.
		 * @param mixed      $key        The key used to retrieve the value.
		 * @param mixed      $default    The default value if the key doesn't exist.
		 * @param Collection $collection The collection instance.
		 */
		return apply_filters( 'divi_squad_collection_get_item', $value, $key, $default, $this );
	}

	/**
	 * Check if an item exists in the collection by key.
	 *
	 * @param mixed $key
	 *
	 * @return bool
	 */
	public function has( $key ) {
		$exists = array_key_exists( $key, $this->items );

		/**
		 * Filter whether an item exists in the collection.
		 *
		 * @since 3.3.3
		 *
		 * @param bool       $exists     Whether the key exists in the collection.
		 * @param mixed      $key        The key to check for.
		 * @param Collection $collection The collection instance.
		 */
		return apply_filters( 'divi_squad_collection_has_item', $exists, $key, $this );
	}

	/**
	 * Put an item in the collection by key.
	 *
	 * @param mixed $key
	 * @param mixed $value
	 *
	 * @return void
	 */
	public function put( $key, $value ) {
		/**
		 * Filter the value before it's added to the collection.
		 *
		 * @since 3.3.3
		 *
		 * @param mixed      $value      The value to add to the collection.
		 * @param mixed      $key        The key to use.
		 * @param Collection $collection The collection instance.
		 */
		$filtered_value = apply_filters( 'divi_squad_collection_before_put', $value, $key, $this );

		$this->items[ $key ] = $filtered_value;

		/**
		 * Action triggered after an item is added to the collection.
		 *
		 * @since 3.3.3
		 *
		 * @param mixed      $value      The value added to the collection.
		 * @param mixed      $key        The key used.
		 * @param Collection $collection The collection instance.
		 */
		do_action( 'divi_squad_collection_after_put', $filtered_value, $key, $this );
	}

	/**
	 * Remove an item from the collection by key.
	 *
	 * @param mixed $key
	 *
	 * @return void
	 */
	public function forget( $key ): void {
		/**
		 * Action triggered before an item is removed from the collection.
		 *
		 * @since 3.3.3
		 *
		 * @param mixed      $key        The key to remove.
		 * @param mixed      $value      The value being removed (or null if not exists).
		 * @param Collection $collection The collection instance.
		 */
		do_action( 'divi_squad_collection_before_forget', $key, $this->get( $key ), $this );

		unset( $this->items[ $key ] );

		/**
		 * Action triggered after an item is removed from the collection.
		 *
		 * @since 3.3.3
		 *
		 * @param mixed      $key        The key that was removed.
		 * @param Collection $collection The collection instance.
		 */
		do_action( 'divi_squad_collection_after_forget', $key, $this );
	}

	/**
	 * Filter items by the given callback.
	 *
	 * @param callable $callback
	 *
	 * @return static
	 */
	public function filter( callable $callback ): Collection {
		/**
		 * Filter the callback used for filtering the collection.
		 *
		 * @since 3.3.3
		 *
		 * @param callable   $callback   The callback function used for filtering.
		 * @param Collection $collection The collection instance.
		 */
		$callback = apply_filters( 'divi_squad_collection_filter_callback', $callback, $this );

		$filtered_items = array_filter( $this->items, $callback, ARRAY_FILTER_USE_BOTH );

		/**
		 * Filter the resulting items after applying the filter callback.
		 *
		 * @since 3.3.3
		 *
		 * @param array      $filtered_items The filtered items.
		 * @param callable   $callback       The callback that was used.
		 * @param Collection $collection     The collection instance.
		 */
		$filtered_items = apply_filters( 'divi_squad_collection_filtered_items', $filtered_items, $callback, $this );

		return new static( $filtered_items );
	}

	/**
	 * Get the collection of items as a plain array.
	 *
	 * @return array
	 */
	public function toArray(): array {
		$array = array_map(
			static function ( $value ) {
				return $value instanceof self ? $value->toArray() : $value;
			},
			$this->items
		);

		/**
		 * Filter the array representation of the collection.
		 *
		 * @since 3.3.3
		 *
		 * @param array      $array      The array representation of the collection.
		 * @param Collection $collection The collection instance.
		 */
		return apply_filters( 'divi_squad_collection_to_array', $array, $this );
	}

	/**
	 * Count the number of items in the collection.
	 *
	 * @return int
	 */
	public function count(): int {
		$count = count( $this->items );

		/**
		 * Filter the count of items in the collection.
		 *
		 * @since 3.3.3
		 *
		 * @param int        $count      The number of items in the collection.
		 * @param Collection $collection The collection instance.
		 */
		return (int) apply_filters( 'divi_squad_collection_count', $count, $this );
	}

	/**
	 * Get an iterator for the items.
	 *
	 * @return \ArrayIterator
	 */
	public function getIterator(): \ArrayIterator {
		/**
		 * Filter the iterator used for the collection.
		 *
		 * @since 3.3.3
		 *
		 * @param \ArrayIterator $iterator   The iterator for the collection.
		 * @param Collection     $collection The collection instance.
		 */
		return apply_filters( 'divi_squad_collection_iterator', new \ArrayIterator( $this->items ), $this );
	}

	/**
	 * Determine if an item exists at an offset.
	 *
	 * @param mixed $offset
	 *
	 * @return bool
	 */
	public function offsetExists( $offset ): bool {
		return isset( $this->items[ $offset ] );
	}

	/**
	 * Get an item at a given offset.
	 *
	 * @param mixed $offset
	 *
	 * @return mixed
	 */
	public function offsetGet( $offset ) {
		return $this->items[ $offset ];
	}

	/**
	 * Set the item at a given offset.
	 *
	 * @param mixed $offset
	 * @param mixed $value
	 *
	 * @return void
	 */
	public function offsetSet( $offset, $value ): void {
		/**
		 * Filter the value before it's set using ArrayAccess interface.
		 *
		 * @since 3.3.3
		 *
		 * @param mixed      $value      The value to set.
		 * @param mixed      $offset     The offset to set it at.
		 * @param Collection $collection The collection instance.
		 */
		$value = apply_filters( 'divi_squad_collection_before_offset_set', $value, $offset, $this );

		if ( is_null( $offset ) ) {
			$this->items[] = $value;
		} else {
			$this->items[ $offset ] = $value;
		}

		/**
		 * Action triggered after a value is set using ArrayAccess interface.
		 *
		 * @since 3.3.3
		 *
		 * @param mixed      $value      The value that was set.
		 * @param mixed      $offset     The offset where it was set.
		 * @param Collection $collection The collection instance.
		 */
		do_action( 'divi_squad_collection_after_offset_set', $value, $offset, $this );
	}

	/**
	 * Unset the item at a given offset.
	 *
	 * @param mixed $offset
	 *
	 * @return void
	 */
	public function offsetUnset( $offset ): void {
		/**
		 * Action triggered before a value is unset using ArrayAccess interface.
		 *
		 * @since 3.3.3
		 *
		 * @param mixed      $offset     The offset to unset.
		 * @param mixed      $value      The value being unset (or null if not exists).
		 * @param Collection $collection The collection instance.
		 */
		do_action( 'divi_squad_collection_before_offset_unset', $offset, $this->offsetGet( $offset ), $this );

		unset( $this->items[ $offset ] );

		/**
		 * Action triggered after a value is unset using ArrayAccess interface.
		 *
		 * @since 3.3.3
		 *
		 * @param mixed      $offset     The offset that was unset.
		 * @param Collection $collection The collection instance.
		 */
		do_action( 'divi_squad_collection_after_offset_unset', $offset, $this );
	}
}
