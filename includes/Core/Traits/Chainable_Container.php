<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Chainable Container Trait
 *
 * This trait provides a flexible, chainable container implementation
 * for storing and retrieving values using magic methods. It allows
 * for fluent property setting and access within classes.
 *
 * @since      3.3.3
 * @package    DiviSquad
 * @subpackage DiviSquad\Core\Traits
 * @author     The WP Squad <support@squadmodules.com>
 * @license    GPL-2.0+
 * @link       https://wpsquad.com
 */

namespace DiviSquad\Core\Traits;

/**
 * Chainable_Container Trait
 *
 * A trait that implements magic methods to provide container-like
 * functionality to any class. Allows setting and getting values
 * using object property syntax while maintaining the underlying
 * values in a protected array.
 *
 * @since 3.3.3
 */
trait Chainable_Container {

	/**
	 * Container for storing key-value pairs
	 *
	 * Holds all the values added to the container that can be
	 * accessed via the magic methods.
	 *
	 * @since 3.3.3
	 * @var   array<string, mixed>
	 */
	protected array $container = array();

	/**
	 * Checks if a property exists in the container
	 *
	 * Implements the magic __isset method to check if a specific
	 * key exists in the container array.
	 *
	 * @since  3.3.3
	 * @access public
	 *
	 * @param string $key The key to check existence for.
	 *
	 * @return bool   True if the key exists, false otherwise.
	 */
	public function __isset( string $key ): bool {
		return isset( $this->container[ $key ] );
	}

	/**
	 * Retrieves a value from the container
	 *
	 * Implements the magic __get method to retrieve a value from
	 * the container array by key. Returns an empty stdClass object
	 * if the key doesn't exist to allow for chainable operations.
	 *
	 * @since  3.3.3
	 * @access public
	 *
	 * @param string $key The key to retrieve the value for.
	 *
	 * @return mixed  The value associated with the key or an empty stdClass if not found.
	 */
	public function __get( string $key ) {
		if ( array_key_exists( $key, $this->container ) ) {
			return $this->container[ $key ];
		}

		return new \stdClass();
	}

	/**
	 * Sets a value in the container
	 *
	 * Implements the magic __set method to store a value in
	 * the container array with the specified key.
	 *
	 * @since  3.3.3
	 * @access public
	 *
	 * @param string $key   The key to store the value under.
	 * @param mixed  $value The value to store.
	 *
	 * @return void
	 */
	public function __set( string $key, $value ): void {
		$this->container[ $key ] = $value;
	}

	/**
	 * Gets all values from the container
	 *
	 * Returns the entire container array. Useful when needing to
	 * access or iterate over all stored values.
	 *
	 * @since  3.3.3
	 * @access public
	 *
	 * @return array<string, mixed> The complete container array.
	 */
	public function get_all(): array {
		return $this->container ?? array();
	}

	/**
	 * Checks if the container is empty
	 *
	 * Determines if there are any values stored in the container.
	 *
	 * @since  3.3.3
	 * @access public
	 *
	 * @return bool True if the container is empty, false otherwise.
	 */
	public function is_empty(): bool {
		return count( $this->container ) === 0;
	}

	/**
	 * Removes a value from the container
	 *
	 * Deletes a key-value pair from the container by key.
	 *
	 * @since  3.3.3
	 * @access public
	 *
	 * @param string $key The key to remove from the container.
	 *
	 * @return bool   True if the key was removed, false if it didn't exist.
	 */
	public function remove( string $key ): bool {
		if ( array_key_exists( $key, $this->container ) ) {
			unset( $this->container[ $key ] );

			return true;
		}

		return false;
	}

	/**
	 * Clears all values from the container
	 *
	 * Removes all key-value pairs from the container,
	 * effectively resetting it to an empty array.
	 *
	 * @since  3.3.3
	 * @access public
	 *
	 * @return void
	 */
	public function clear(): void {
		$this->container = array();
	}
}
