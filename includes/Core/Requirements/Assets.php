<?php

/**
 * Assets manager class for requirements.
 *
 * Handles registration and enqueueing of assets for the requirements page.
 *
 * @since   3.5.0
 * @package DiviSquad\Core\Requirements
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Core\Requirements;

use DiviSquad\Core\Assets as Assets_Manager;
use DiviSquad\Core\Contracts\Hookable;
use DiviSquad\Utils\Helper as HelperUtil;
use Throwable;

/**
 * Class Assets_Manager
 *
 * Manages assets for the requirements page.
 *
 * @since   3.5.0
 * @package DiviSquad\Core\Requirements
 */
class Assets implements Hookable {
	/**
	 * Register hooks and filters for assets management.
	 *
	 * @since  3.5.0
	 * @access public
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'divi_squad_after_register_admin_assets', array( $this, 'register_assets' ) );
		add_action( 'divi_squad_after_enqueue_admin_assets', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register requirements assets
	 *
	 * @param Assets_Manager $assets Assets Manager instance.
	 *
	 * @return void
	 */
	public function register_assets( Assets_Manager $assets ): void {
		try {
			$assets->register_style(
				'plugin-requirements',
				array(
					'file' => 'requirements',
					'path' => 'admin',
				)
			);

			/**
			 * Fires after requirements assets are registered
			 *
			 * @param Assets_Manager $assets Assets Manager instance
			 */
			do_action( 'divi_squad_after_register_requirements_assets', $assets );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to register admin notices assets' );
		}
	}

	/**
	 * Enqueue requirements assets
	 *
	 * @param Assets_Manager $assets Assets Manager instance.
	 *
	 * @return void
	 */
	public function enqueue_assets( Assets_Manager $assets ): void {
		try {
			if ( HelperUtil::is_squad_page() ) {
				$assets->enqueue_style( 'plugin-requirements' );
			}

			/**
			 * Fires after requirements assets are enqueued
			 *
			 * @param Assets $assets Assets Manager instance
			 */
			do_action( 'divi_squad_after_enqueue_requirements_assets', $assets );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to enqueue requirements assets' );
		}
	}
}
