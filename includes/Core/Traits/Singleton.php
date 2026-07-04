<?php // phpcs:ignore WordPress.Files.FileName
/**
 * Singleton trait for creating a single instance of a class.
 *
 * @since   1.0.0
 * @package DiviSquad
 */

namespace DiviSquad\Core\Traits;

use RuntimeException;
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
	 * The instance storage for all singleton classes.
	 *
	 * Using a static array allows proper instance tracking across child classes.
	 *
	 * @var array<string, static>
	 */
	private static $instances = array();

	/**
	 * Initialization status tracking.
	 *
	 * @var bool
	 */
	private bool $is_initialized = false;

	/**
	 * Get the instance of the current class.
	 *
	 * @return static The singleton instance.
	 */
	public static function get_instance() {
		$class = static::class;

		if ( ! isset( self::$instances[ $class ] ) ) {
			self::$instances[ $class ] = static::create_instance();
		}

		return self::$instances[ $class ];
	}

	/**
	 * Check if an instance exists without creating one.
	 *
	 * @return bool True if instance exists, false otherwise.
	 */
	public static function has_instance(): bool {
		return isset( self::$instances[ static::class ] );
	}

	/**
	 * Reset the instance (primarily for testing purposes).
	 *
	 * @return void
	 */
	public static function reset_instance(): void {
		$class = static::class;
		if ( isset( self::$instances[ $class ] ) ) {
			unset( self::$instances[ $class ] );
		}
	}

	/**
	 * Create an instance of the current class.
	 *
	 * @return static
	 * @throws RuntimeException When all attempts to create an instance fail.
	 */
	public static function create_instance() {
		try {
			$instance = new static();
			$instance->initialize();
			$instance->is_initialized = true;

			return $instance;
		} catch ( Throwable $e ) {
			// Log the error with better context.
			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log(
				sprintf(
					'SQUAD ERROR in %s: %s (in %s:%d) [%s]',
					static::class,
					$e->getMessage(),
					$e->getFile(),
					$e->getLine(),
					$e->getTraceAsString()
				)
			);

			// Fallback: Create a basic instance without initialization.
			try {
				$instance = new static();

				// Log that we're using an uninitialized instance
				error_log(
					sprintf(
						'SQUAD WARNING: Using uninitialized instance of %s due to initialization failure',
						static::class
					)
				);

				return $instance;
			} catch ( Throwable $e ) {
				error_log(
					sprintf(
						'SQUAD FATAL ERROR: Unable to create instance of %s: %s',
						static::class,
						$e->getMessage()
					)
				);

				// We still need to return something of the correct type.
				throw new RuntimeException(
					sprintf( 'Failed to create singleton instance of %s', static::class ),
					0,
					$e // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				);
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
		// Prevent double initialization
		if ( $this->is_initialized ) {
			return;
		}

		// Backward compatibility support.
		if ( method_exists( $this, 'init_hooks' ) ) { // @phpstan-ignore-line function.impossibleType
			$this->init_hooks();
		}

		if ( function_exists( 'do_action' ) ) {
			/**
			 * Action that fires after a singleton has been initialized.
			 *
			 * @since 1.0.0
			 *
			 * @param object $instance The singleton instance.
			 */
			do_action( 'divi_squad_singleton_initialized', $this );
		}
	}

	/**
	 * Prevent unserializing of the instance.
	 *
	 * @return void
	 * @throws RuntimeException Always throws an exception.
	 */
	public function __wakeup(): void {
		throw new RuntimeException(
			sprintf( 'Cannot unserialize singleton: %s', static::class )
		);
	}

	/**
	 * Prevent cloning of the instance.
	 *
	 * @return void
	 * @throws RuntimeException Always throws an exception.
	 */
	private function __clone() {
		throw new RuntimeException(
			sprintf( 'Cloning is not allowed for singleton: %s', static::class )
		);
	}
}
