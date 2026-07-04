<?php
/**
 * Singleton trait for creating a single instance of a class.
 *
 * @package DiviSquad
 * @since   1.0.0
 */

namespace DiviSquad\Core\Traits;

use Throwable;

/**
 * Singleton trait.
 *
 * Provides functionality to ensure only one instance of a class exists.
 * Classes using this trait should implement initialize() method for setup.
 *
 * @since   1.0.0
 * @package DiviSquad
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
	 * @return static The singleton instance.
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
	 * @return static
	 * @throws \RuntimeException When all attempts to create an instance fail.
	 */
	private static function create_instance() {
		try {
			$instance = new static();
			$instance->initialize();

			return $instance;
		} catch ( Throwable $e ) {
			// Log the error with better context.
			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log(
				sprintf(
					'SQUAD ERROR in %s: %s (in %s:%d)',
					static::class,
					$e->getMessage(),
					$e->getFile(),
					$e->getLine()
				)
			);

			// Fallback: Create a basic instance without initialization.
			try {
				return new static();
			} catch ( Throwable $e ) {
				error_log(
					sprintf(
						'SQUAD FATAL ERROR: Unable to create instance of %s: %s',
						static::class,
						$e->getMessage()
					)
				);

				// We still need to return something of the correct type.
				throw new \RuntimeException( 'Failed to create singleton instance' );
			}
			// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}

	/**
	 * Initialize the instance.
	 *
	 * Classes using this trait can implement init_properties() and init_hooks()
	 * which will be automatically called during initialization.
	 *
	 * @return void
	 */
	protected function initialize(): void {
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
	 * Prevent unserializing of the instance.
	 *
	 * @throws \RuntimeException Always throws an exception.
	 * @return void
	 */
	public function __wakeup(): void {
		throw new \RuntimeException( 'Cannot unserialize singleton' );
	}

	/**
	 * Prevent cloning of the instance.
	 *
	 * @throws \RuntimeException Always throws an exception.
	 * @return void
	 */
	private function __clone() {
		throw new \RuntimeException( 'Cloning is not allowed for singleton' );
	}
}
