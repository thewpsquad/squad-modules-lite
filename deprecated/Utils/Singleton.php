<?php

/**
 * Singleton trait for creating a single instance of a class.
 *
 * @package DiviSquad
 * @since   1.0.0
 * @deprecated 3.2.1 Use DiviSquad\Core\Traits\Singleton instead
 */

namespace DiviSquad\Utils;

/**
 * Singleton trait.
 *
 * @since      1.0.0
 * @package    DiviSquad
 * @deprecated 3.2.1 Use DiviSquad\Core\Traits\Singleton instead
 */
trait Singleton {

	/**
	 * The instance of the current class.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Get the instance of the current class.
	 *
	 * @return self
	 */
	public static function get_instance() {
		if ( null === static::$instance ) {
			static::$instance = static::create_instance();
		}

		return static::$instance;
	}

	/**
	 * Create an instance of the current class.
	 *
	 * @return self
	 */
	private static function create_instance() {
		try {
			$instance = new static();
			$instance->initialize();

			return $instance;
		} catch ( \Exception $e ) {
			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'SQUAD ERROR: ' . $e->getMessage() );

			// Fallback: Ensure a valid instance is always returned.
			try {
				return new static();
			} catch ( \Exception $e ) {
				error_log( 'SQUAD FATAL ERROR: Unable to create instance: ' . $e->getMessage() );

				return null; // Fallback if all attempts to create an instance fail.
			}
			// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}

	/**
	 * Initialize the instance.
	 */
	protected function initialize() {
		// Initialize properties.
		if ( method_exists( $this, 'init_properties' ) ) {
			$this->init_properties();
		}

		// Initialize hooks.
		if ( method_exists( $this, 'init_hooks' ) ) {
			$this->init_hooks();
		}
	}

	/**
	 * Serializing instances of this class is forbidden.
	 *
	 * @access public
	 * @since  1.0.0
	 */
	public function __wakeup() {}

	/**
	 * Cloning is forbidden.
	 *
	 * @access private
	 * @since  1.0.0
	 */
	private function __clone() {}
}
