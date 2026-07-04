<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Custom Fields Upgrader
 *
 * This file contains the Upgraders class which manages
 * database upgrades for the custom fields summary table.
 *
 * @since   3.1.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Utils\Elements\Custom_Fields\Managers;

use DiviSquad\Builder\Utils\Database\Database_Utils;
use Throwable;

/**
 * Upgrader Class
 *
 * Manages database upgrades for the custom fields summary table.
 * This class handles version tracking and applies necessary database
 * structure changes when upgrading from older versions.
 *
 * @since   3.1.1
 * @package DiviSquad
 */
class Upgrades {

	/**
	 * The name of the summary table in the database.
	 *
	 * @since 3.1.1
	 * @var string
	 */
	private string $summary_table_name = '';

	/**
	 * The option name used to store the current version in the database.
	 *
	 * @since 3.1.1
	 * @var string
	 */
	private string $version_option_name = '';

	/**
	 * The option name used to track upgrade lock status.
	 *
	 * @since 3.1.1
	 * @var string
	 */
	private string $lock_option_name = '';

	/**
	 * The current version of the database structure.
	 *
	 * @since 3.1.1
	 * @var string
	 */
	private string $current_version = '1.0';

	/**
	 * Flag indicating if upgrade operations are currently in progress.
	 *
	 * @since 3.1.1
	 * @var bool
	 */
	private bool $is_upgrading = false;

	/**
	 * Maximum time in seconds before considering a lock as stale.
	 *
	 * @since 3.1.1
	 * @var int
	 */
	private int $lock_timeout = 300; // 5 minutes

	/**
	 * Collection of upgrade procedures mapped by version.
	 *
	 * @since 3.1.1
	 * @var array<string, callable>
	 */
	private array $upgrade_procedures = array();

	/**
	 * Constructor.
	 *
	 * @since 3.1.1
	 *
	 * @param string $version_option_name Optional. Custom option name for version tracking.
	 * @param string $lock_option_name    Optional. Custom option name for upgrade lock.
	 */
	public function __construct(
		string $version_option_name = 'custom_fields_summary_version',
		string $lock_option_name = 'custom_fields_upgrade_in_progress'
	) {
		$this->version_option_name = $version_option_name;
		$this->lock_option_name    = $lock_option_name;
		$this->register_upgrade_procedures();
	}

	/**
	 * Register all available upgrade procedures.
	 *
	 * Each upgrade procedure is registered with the version it upgrades to.
	 *
	 * @since 3.1.1
	 *
	 * @return void
	 */
	private function register_upgrade_procedures(): void {
		// Register version 1.0 upgrade procedure
		// $this->register_upgrade_procedure( '1.0', array( $this, 'upgrade_to_1_0' ) );

		// Future upgrade procedures can be registered here
		// $this->register_upgrade_procedure('1.1', [$this, 'upgrade_to_1_1']);

		/**
		 * Action to register custom upgrade procedures.
		 *
		 * @since 3.1.1
		 *
		 * @param Upgrades $upgrades The upgrades instance.
		 */
		do_action( 'divi_squad_custom_fields_register_upgrades', $this );
	}

	/**
	 * Register a new upgrade procedure.
	 *
	 * @since 3.1.1
	 *
	 * @param string   $version   The version this procedure upgrades to.
	 * @param callable $procedure The upgrade procedure callback.
	 *
	 * @return void
	 */
	public function register_upgrade_procedure( string $version, callable $procedure ): void {
		$this->upgrade_procedures[ $version ] = $procedure;
	}

	/**
	 * Acquire an upgrade lock to prevent concurrent upgrades.
	 *
	 * @since 3.1.1
	 *
	 * @return bool True if lock was acquired, false otherwise.
	 */
	private function acquire_lock(): bool {
		// Check if another upgrade is in progress
		$lock_data = divi_squad()->memory->get( $this->lock_option_name );

		// If lock exists, check if it's stale
		if ( null !== $lock_data ) {
			$lock_time    = is_array( $lock_data ) && isset( $lock_data['time'] ) ? $lock_data['time'] : 0;
			$lock_expired = ( time() - $lock_time ) > $this->lock_timeout;

			// If lock is not stale, another upgrade is in progress
			if ( ! $lock_expired ) {
				divi_squad()->log_debug( 'Cannot acquire upgrade lock: another upgrade is in progress' );

				return false;
			}

			// Log that we're breaking a stale lock
			divi_squad()->log_debug( 'Breaking stale upgrade lock from ' . wp_date( 'Y-m-d H:i:s', $lock_time ) );
		}

		// Create new lock
		$lock_data = array(
			'time' => time(),
			'pid'  => getmypid(),
			'site' => get_current_blog_id(),
		);

		// Set the lock
		divi_squad()->memory->set( $this->lock_option_name, $lock_data );

		// Verify the lock was set properly
		$verified_lock = divi_squad()->memory->get( $this->lock_option_name );
		$lock_acquired = is_array( $verified_lock ) && isset( $verified_lock['pid'] ) && getmypid() === $verified_lock['pid'];

		if ( $lock_acquired ) {
			divi_squad()->log_debug( 'Upgrade lock acquired successfully' );
		} else {
			divi_squad()->log_debug( 'Failed to acquire upgrade lock' );
		}

		return $lock_acquired;
	}

	/**
	 * Release the upgrade lock.
	 *
	 * @since 3.1.1
	 *
	 * @return void
	 */
	private function release_lock(): void {
		// Get current lock
		$lock_data = divi_squad()->memory->get( $this->lock_option_name );

		// Only release if it's our lock
		if ( is_array( $lock_data ) && isset( $lock_data['pid'] ) && getmypid() === $lock_data['pid'] ) {
			divi_squad()->memory->delete( $this->lock_option_name );
			divi_squad()->log_debug( 'Upgrade lock released' );
		}
	}

	/**
	 * Run necessary database upgrades.
	 *
	 * Checks the installed version against the current version and
	 * performs any necessary upgrade procedures.
	 *
	 * @since 3.1.1
	 *
	 * @param string $table The name of the summary table in the database.
	 *
	 * @return bool True if upgrades were performed, false otherwise.
	 */
	public function run_upgrades( string $table ): bool {
		// Set the table name
		$this->summary_table_name = $table;

		// Get the installed version
		$installed_version = $this->get_installed_version();

		// Check if an upgrade is needed
		if ( version_compare( $installed_version, $this->current_version, '>=' ) ) {
			return false;
		}

		// Try to acquire lock for the upgrade process
		if ( ! $this->acquire_lock() ) {
			divi_squad()->log_debug( 'Skipping upgrade: another upgrade process is already running' );

			return false;
		}

		// Set upgrading flag
		$this->is_upgrading = true;

		try {
			// Log the upgrade
			divi_squad()->log_debug(
				sprintf(
					'Starting custom fields database upgrade from %s to %s',
					$installed_version,
					$this->current_version
				)
			);

			// Perform the upgrades
			$result = $this->perform_upgrades( $installed_version );

			// Update the version
			if ( $result ) {
				divi_squad()->memory->set( $this->version_option_name, $this->current_version );

				// Log success
				divi_squad()->log_debug(
					sprintf(
						'Custom fields database upgraded successfully to version %s',
						$this->current_version
					)
				);
			}

			return $result;
		} catch ( Throwable $e ) {
			// Log the error
			divi_squad()->log_error(
				$e,
				sprintf(
					'Error upgrading custom fields database from %s to %s',
					$installed_version,
					$this->current_version
				)
			);

			return false;
		} finally {
			// Clear upgrading flag
			$this->is_upgrading = false;

			// Always release the lock, even if an exception occurred
			$this->release_lock();
		}
	}

	/**
	 * Perform specific upgrade procedures.
	 *
	 * This method executes the upgrade procedures in order based on
	 * version numbers, skipping any that are already applied.
	 *
	 * @since 3.1.1
	 *
	 * @param string $from_version The version to upgrade from.
	 *
	 * @return bool True if upgrades were performed successfully, false otherwise.
	 */
	private function perform_upgrades( string $from_version ): bool {
		// Get all versions that need to be upgraded to
		$versions_to_apply = array_keys( $this->upgrade_procedures );

		// Sort them by version number
		usort( $versions_to_apply, 'version_compare' );

		// Track success
		$success = true;

		// Apply each upgrade procedure that's newer than the installed version
		foreach ( $versions_to_apply as $version ) {
			if ( version_compare( $version, $from_version, '>' ) ) {
				// Get the procedure
				$procedure = $this->upgrade_procedures[ $version ];

				// Apply the upgrade
				try {
					$result = $procedure();

					if ( false === $result ) {
						divi_squad()->log_debug(
							sprintf(
								'Upgrade to version %s failed',
								$version
							)
						);

						$success = false;
						break;
					}

					// Log success for this version
					divi_squad()->log_debug(
						sprintf(
							'Upgrade to version %s completed successfully',
							$version
						)
					);

					// Update from_version to the current successful upgrade
					$from_version = (string) $version;

					/**
					 * Action fired after a successful upgrade to a specific version.
					 *
					 * @since 3.1.1
					 *
					 * @param string $version            The version that was upgraded to.
					 * @param string $summary_table_name The name of the summary table.
					 */
					do_action( 'divi_squad_custom_fields_upgrade_completed', $version, $this->summary_table_name );
				} catch ( Throwable $e ) {
					divi_squad()->log_error(
						$e,
						sprintf(
							'Error during upgrade to version %s',
							$version
						)
					);

					$success = false;
					break;
				}
			}
		}

		return $success;
	}

	/**
	 * Upgrade procedure for version 1.0.
	 *
	 * Initial table structure setup.
	 *
	 * @since 3.1.1
	 *
	 * @return bool True if the upgrade was successful, false otherwise.
	 */
	private function upgrade_to_1_0(): bool {
		// For version 1.0, we just verify the table exists with the correct structure
		if ( '' === $this->summary_table_name ) {
			return false;
		}

		// Check if the table exists
		if ( ! Database_Utils::table_exists( $this->summary_table_name ) ) {
			return false;
		}

		// Version 1.0 was the initial version, so there's nothing to upgrade
		return true;
	}

	/**
	 * Check if an upgrade is locked (another process is running the upgrade).
	 *
	 * @since 3.1.1
	 *
	 * @return bool True if an upgrade is locked, false otherwise.
	 */
	public function is_upgrade_locked(): bool {
		$lock_data = divi_squad()->memory->get( $this->lock_option_name );

		if ( null === $lock_data ) {
			return false;
		}

		// Check if lock is stale
		$lock_time    = is_array( $lock_data ) && isset( $lock_data['time'] ) ? $lock_data['time'] : 0;
		$lock_expired = ( time() - $lock_time ) > $this->lock_timeout;

		// Return true only if lock exists and is not stale
		return ! $lock_expired;
	}

	/**
	 * Get the summary table name.
	 *
	 * @since 3.1.1
	 *
	 * @return string The summary table name.
	 */
	public function get_summary_table_name(): string {
		return $this->summary_table_name;
	}

	/**
	 * Set the summary table name.
	 *
	 * @since 3.1.1
	 *
	 * @param string $table_name The summary table name.
	 *
	 * @return self
	 */
	public function set_summary_table_name( string $table_name ): self {
		$this->summary_table_name = $table_name;

		return $this;
	}

	/**
	 * Get the current version of the database structure.
	 *
	 * @since 3.1.1
	 *
	 * @return string The current version.
	 */
	public function get_current_version(): string {
		return $this->current_version;
	}

	/**
	 * Set the current version of the database structure.
	 *
	 * This method is primarily used for testing purposes or manual version management.
	 *
	 * @since 3.1.1
	 *
	 * @param string $version The version to set.
	 *
	 * @return self
	 */
	public function set_current_version( string $version ): self {
		$this->current_version = $version;

		return $this;
	}

	/**
	 * Set the lock timeout value.
	 *
	 * @since 3.1.1
	 *
	 * @param int $timeout The timeout in seconds.
	 *
	 * @return self
	 */
	public function set_lock_timeout( int $timeout ): self {
		$this->lock_timeout = max( 60, $timeout ); // Minimum 60 seconds

		return $this;
	}

	/**
	 * Get the lock timeout value.
	 *
	 * @since 3.1.1
	 *
	 * @return int The timeout in seconds.
	 */
	public function get_lock_timeout(): int {
		return $this->lock_timeout;
	}

	/**
	 * Check if an upgrade is currently in progress.
	 *
	 * @since 3.1.1
	 *
	 * @return bool True if an upgrade is in progress, false otherwise.
	 */
	public function is_upgrading(): bool {
		return $this->is_upgrading;
	}

	/**
	 * Check if an upgrade is needed.
	 *
	 * Compares the installed version with the current version to determine
	 * if an upgrade is necessary.
	 *
	 * @since 3.1.1
	 *
	 * @return bool True if an upgrade is needed, false otherwise.
	 */
	public function is_upgrade_needed(): bool {
		$installed_version = $this->get_installed_version();

		return version_compare( $installed_version, $this->current_version, '<' );
	}

	/**
	 * Get the installed version of the database structure.
	 *
	 * @since 3.1.1
	 *
	 * @return string The installed version.
	 */
	public function get_installed_version(): string {
		return divi_squad()->memory->get( $this->version_option_name, '0' );
	}

	/**
	 * Get the option name used for version tracking.
	 *
	 * @since 3.1.1
	 *
	 * @return string The option name.
	 */
	public function get_version_option_name(): string {
		return $this->version_option_name;
	}

	/**
	 * Get the option name used for upgrade lock tracking.
	 *
	 * @since 3.1.1
	 *
	 * @return string The lock option name.
	 */
	public function get_lock_option_name(): string {
		return $this->lock_option_name;
	}

	/**
	 * Get all registered upgrade procedures.
	 *
	 * @since 3.1.1
	 *
	 * @return array<string, callable> Array of upgrade procedures indexed by version.
	 */
	public function get_upgrade_procedures(): array {
		return $this->upgrade_procedures;
	}

	/**
	 * Force release of any existing upgrade lock, regardless of owner.
	 * This should only be used for administrative purposes or debugging.
	 *
	 * @since 3.1.1
	 *
	 * @return bool True if a lock was released, false if none existed.
	 */
	public function force_release_lock(): bool {
		$lock_exists = (bool) divi_squad()->memory->get( $this->lock_option_name );

		if ( $lock_exists ) {
			divi_squad()->memory->delete( $this->lock_option_name );
			divi_squad()->log_debug( 'Upgrade lock forcibly released' );

			return true;
		}

		return false;
	}
}
