<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Login Experience Extension REST API
 *
 * REST API endpoints for managing Login Experience page assignments.
 *
 * @since   4.2.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Rest_API_Routes\Version1\Extensions;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use DiviSquad\Extensions\WordPress\Login_Experience as Login_Experience_Ext;
use DiviSquad\Rest_API_Routes\Base_Route;
use Throwable;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use function absint;
use function get_option;
use function get_permalink;
use function get_post_status;
use function get_the_title;
use function update_option;

/**
 * Login Experience Extension REST API Handler
 *
 * Manages REST API endpoints for reading and updating the page assignments
 * used by the Login Experience extension (login, register, lost-password,
 * reset-password pages).
 *
 * @since   4.2.0
 * @package DiviSquad
 */
class Login_Experience extends Base_Route {

	/** Valid state keys. */
	private const STATES = array( 'login', 'register', 'lost_password', 'reset_password' );

	/**
	 * Get available routes for the Login Experience Extension API.
	 *
	 * @since 4.2.0
	 *
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	public function get_routes(): array {
		return array(
			'/extension/login-experience/pages' => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_pages' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
				),
				array(
					'methods'             => 'PATCH',
					'callback'            => array( $this, 'update_pages' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
					'args'                => $this->get_page_args(),
				),
			),
		);
	}

	/**
	 * GET handler — returns current page assignments with page metadata.
	 *
	 * @since 4.2.0
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_pages() {
		try {
			$stored = (array) get_option( Login_Experience_Ext::PAGES_OPTION, array() );
			$data   = array();

			foreach ( self::STATES as $state ) {
				$id  = absint( $stored[ $state ] ?? 0 );
				$url = $id > 0 ? get_permalink( $id ) : false;

				$data[ $state ] = array(
					'id'    => $id > 0 ? $id : null,
					'title' => $id > 0 ? get_the_title( $id ) : '',
					'url'   => ( false !== $url && '' !== $url ) ? (string) $url : '',
					'valid' => $id > 0 && 'publish' === get_post_status( $id ),
				);
			}

			return $this->success_response( $data );
		} catch ( Throwable $e ) {
			return $this->handle_exception( $e, 'Login Experience get_pages' );
		}
	}

	/**
	 * PATCH handler — updates stored page assignments.
	 *
	 * @since 4.2.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_pages( WP_REST_Request $request ) {
		try {
			$params  = (array) $request->get_json_params();
			$current = (array) get_option( Login_Experience_Ext::PAGES_OPTION, array() );

			foreach ( self::STATES as $state ) {
				if ( array_key_exists( $state, $params ) ) {
					$id = absint( $params[ $state ] );
					if ( $id > 0 ) {
						// Only published pages may serve the login-experience flows;
						// reject IDs pointing at other post types or unpublished content.
						if ( 'page' !== get_post_type( $id ) || 'publish' !== get_post_status( $id ) ) {
							return new WP_Error(
								'divi_squad_invalid_page',
								/* translators: %s: the login-experience state key (e.g. login, register). */
								sprintf( esc_html__( 'The selected post for "%s" must be a published page.', 'squad-modules-for-divi' ), $state ),
								array( 'status' => 400 )
							);
						}
						$current[ $state ] = $id;
					} else {
						unset( $current[ $state ] );
					}
				}
			}

			update_option( Login_Experience_Ext::PAGES_OPTION, $current );

			return $this->success_response(
				null,
				esc_html__( 'Login Experience page assignments saved.', 'squad-modules-for-divi' )
			);
		} catch ( Throwable $e ) {
			return $this->handle_exception( $e, 'Login Experience update_pages' );
		}
	}

	/**
	 * Argument schema for the PATCH endpoint.
	 *
	 * @since 4.2.0
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function get_page_args(): array {
		$args = array();
		foreach ( self::STATES as $state ) {
			$args[ $state ] = array(
				'required'          => false,
				'type'              => array( 'integer', 'null' ),
				'minimum'           => 0,
				'sanitize_callback' => 'absint',
			);
		}

		return $args;
	}
}
