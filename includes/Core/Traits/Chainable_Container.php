<?php

namespace DiviSquad\Core\Traits;

trait Chainable_Container {

	/**
	 * List of containers
	 *
	 * @var array
	 */
	protected array $container = array();

	/**
	 * Set the plugin options.
	 *
	 * @param string $key The key to set.
	 *
	 * @return bool
	 */
	public function __isset( string $key ) {
		return isset( $this->container[ $key ] );
	}

	/**
	 * Set the plugin options.
	 *
	 * @param string $key The key to set.
	 *
	 * @return mixed
	 */
	public function __get( string $key ) {
		if ( array_key_exists( $key, $this->container ) ) {
			return $this->container[ $key ];
		}

		return new \stdClass();
	}

	/**
	 * Set the plugin options.
	 *
	 * @param string $key   The key to set.
	 * @param mixed  $value The value to set.
	 *
	 * @return void
	 */
	public function __set( string $key, $value ) {
		$this->container[ $key ] = $value;
	}
}
