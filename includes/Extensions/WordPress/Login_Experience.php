<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Login Experience extension class.
 *
 * @since   4.2.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Extensions\WordPress;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use DiviSquad\Extensions\Abstracts\Base_Extension;
use Throwable;
use function absint;
use function add_action;
use function add_filter;
use function add_query_arg;
use function do_action;
use function get_option;
use function get_permalink;
use function get_post_status;
use function is_wp_error;
use function rawurlencode;
use function sanitize_key;
use function sanitize_text_field;
use function update_option;
use function wp_insert_post;
use function wp_safe_redirect;

/**
 * Login Experience extension.
 *
 * Replaces wp-login.php with Divi-designed pages for all 4 auth states.
 * Forms POST natively to wp-login.php — WP core handles all auth.
 *
 * @since 4.2.0
 */
class Login_Experience extends Base_Extension {

	/** WP_options key for page ID map. */
	public const PAGES_OPTION = 'divi_squad_login_experience_pages';

	/** WP_options key for per-state creation guard. */
	public const CREATED_OPTION = 'divi_squad_login_experience_created';

	/** Valid state keys. */
	private const STATES = array( 'login', 'register', 'lost_password', 'reset_password' );

	/** Actions that must always use wp-login.php. */
	private const BYPASS_ACTIONS = array(
		'logout',
		'postpass',
		'confirmaction',
		'checkemail',
		'confirm_admin_email',
	);

	/** Maps wp-login.php action value → extension state key. */
	private const ACTION_MAP = array(
		'login'            => 'login',
		'register'         => 'register',
		'lostpassword'     => 'lost_password',
		'retrievepassword' => 'lost_password',
		'resetpass'        => 'reset_password',
		'rp'               => 'reset_password',
	);

	/**
	 * {@inheritdoc}
	 */
	public function get_name(): string {
		return 'Login_Experience';
	}

	/**
	 * {@inheritdoc}
	 */
	public function load(): void {
		try {
			do_action( 'divi_squad_ext_login_experience_before_loaded', $this );

			$this->register_hooks();

			do_action( 'divi_squad_ext_login_experience_loaded', $this );
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Login Experience extension loading issue' );
		}
	}

	/**
	 * Register all WordPress hooks.
	 */
	public function register_hooks(): void {
		add_action( 'init', array( $this, 'maybe_create_pages' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'login_url', array( $this, 'filter_login_url' ), 10, 3 );
		add_filter( 'register_url', array( $this, 'filter_register_url' ) );
		add_filter( 'lostpassword_url', array( $this, 'filter_lostpassword_url' ), 10, 2 );
		add_action( 'login_init', array( $this, 'intercept_login_page' ) );
		add_action( 'wp_login_failed', array( $this, 'on_login_failed' ) );
	}

	/**
	 * Return the page ID stored for a given state, or null if unset/deleted.
	 *
	 * @param string $state One of: login, register, lost_password, reset_password.
	 */
	public function get_page_id( string $state ): ?int {
		$pages = (array) get_option( self::PAGES_OPTION, array() );
		$id    = absint( $pages[ $state ] ?? 0 );

		return $id > 0 ? $id : null;
	}

	/**
	 * Create WP pages for each auth state that hasn't been created yet.
	 * Idempotent: checks per-state guard before inserting.
	 */
	public function maybe_create_pages(): void {
		try {
			$created = (array) get_option( self::CREATED_OPTION, array() );
			$pages   = (array) get_option( self::PAGES_OPTION, array() );

			$shortcodes = array(
				'login'          => '[disq_login_form layout="card"]',
				'register'       => '[disq_register_form layout="card"]',
				'lost_password'  => '[disq_lost_password_form layout="card"]',
				'reset_password' => '[disq_reset_password_form layout="card"]',
			);

			$titles = array(
				'login'          => __( 'Login', 'squad-modules-for-divi' ),
				'register'       => __( 'Register', 'squad-modules-for-divi' ),
				'lost_password'  => __( 'Lost Password', 'squad-modules-for-divi' ),
				'reset_password' => __( 'Reset Password', 'squad-modules-for-divi' ),
			);

			$changed = false;

			foreach ( self::STATES as $state ) {
				if ( true === ( $created[ $state ] ?? false ) ) {
					continue;
				}

				$page_id = wp_insert_post(
					array(
						'post_title'   => $titles[ $state ],
						'post_content' => $shortcodes[ $state ],
						'post_status'  => 'draft',
						'post_type'    => 'page',
					),
					true
				);

				if ( is_wp_error( $page_id ) || 0 === $page_id ) {
					continue;
				}

				$pages[ $state ]   = $page_id;
				$created[ $state ] = true;
				$changed           = true;
			}

			if ( $changed ) {
				update_option( self::PAGES_OPTION, $pages );
				update_option( self::CREATED_OPTION, $created );
			}
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Login Experience: failed to create pages' );
		}
	}

	/**
	 * Enqueue frontend scripts + styles on pages that host our modules.
	 */
	public function enqueue_assets(): void {
		$pages = (array) get_option( self::PAGES_OPTION, array() );

		if ( ! is_page( array_values( array_filter( $pages, static fn( $v ) => $v > 0 ) ) ) ) {
			return;
		}

		$plugin_url  = divi_squad()->get_url();
		$plugin_path = divi_squad()->get_path();
		$base_url    = trailingslashit( $plugin_url ) . 'build/extensions/login-experience';
		$base_path   = trailingslashit( $plugin_path ) . 'build/extensions/login-experience';
		$css_path    = $base_path . '/login-experience.css';

		if ( file_exists( $css_path ) ) {
			wp_enqueue_style(
				'squad-login-experience',
				$base_url . '/login-experience.css',
				array(),
				(string) filemtime( $css_path )
			);
		}

		$current    = get_queried_object_id();
		$script_map = array(
			'login'          => 'login-form',
			'register'       => 'register-form',
			'lost_password'  => 'lost-password-form',
			'reset_password' => 'reset-password-form',
		);

		foreach ( $script_map as $state => $handle_suffix ) {
			if ( ! isset( $pages[ $state ] ) || (int) $pages[ $state ] !== $current ) {
				continue;
			}
			$js_path = $base_path . "/{$handle_suffix}.js";
			if ( ! file_exists( $js_path ) ) {
				continue;
			}
			wp_enqueue_script(
				"squad-{$handle_suffix}",
				$base_url . "/{$handle_suffix}.js",
				array(),
				(string) filemtime( $js_path ),
				array(
					'strategy' => 'defer',
					'in_footer' => true,
				)
			);
		}
	}

	/**
	 * Helper — return page permalink only if the page is published.
	 *
	 * @param string $state One of: login, register, lost_password, reset_password.
	 */
	private function get_published_page_url( string $state ): ?string {
		$id = $this->get_page_id( $state );
		if ( null === $id ) {
			return null;
		}
		if ( 'publish' !== get_post_status( $id ) ) {
			return null;
		}
		$url = get_permalink( $id );

		return ( false !== $url && '' !== $url ) ? $url : null;
	}

	/**
	 * Filter: login_url
	 *
	 * @param string $url          Original login URL.
	 * @param string $redirect     redirect_to value.
	 * @param bool   $force_reauth Ignored.
	 */
	public function filter_login_url( string $url, string $redirect, bool $force_reauth ): string {
		$custom = $this->get_published_page_url( 'login' );
		if ( null === $custom ) {
			return $url;
		}

		return '' !== $redirect
			? add_query_arg( 'redirect_to', rawurlencode( $redirect ), $custom )
			: $custom;
	}

	/**
	 * Filter: register_url
	 *
	 * @param string $url Original URL.
	 */
	public function filter_register_url( string $url ): string {
		return $this->get_published_page_url( 'register' ) ?? $url;
	}

	/**
	 * Filter: lostpassword_url
	 *
	 * @param string $url      Original URL.
	 * @param string $redirect redirect_to value.
	 */
	public function filter_lostpassword_url( string $url, string $redirect ): string {
		$custom = $this->get_published_page_url( 'lost_password' );
		if ( null === $custom ) {
			return $url;
		}

		return '' !== $redirect
			? add_query_arg( 'redirect_to', rawurlencode( $redirect ), $custom )
			: $custom;
	}

	/**
	 * Action: login_init
	 * Intercepts wp-login.php and redirects to custom pages.
	 */
	public function intercept_login_page(): void {
		try {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			if ( isset( $_GET['interim-login'] ) && '1' === $_GET['interim-login'] ) {
				return;
			}
			if ( isset( $_GET['customize-login'] ) ) {
				return;
			}

			$action = sanitize_key( $_GET['action'] ?? 'login' );
			// phpcs:enable

			if ( in_array( $action, self::BYPASS_ACTIONS, true ) ) {
				return;
			}

			$state = self::ACTION_MAP[ $action ] ?? null;
			if ( null === $state ) {
				return;
			}

			$id = $this->get_page_id( $state );
			if ( null === $id || 'publish' !== get_post_status( $id ) ) {
				return;
			}

			$url = get_permalink( $id );
			if ( false === $url || '' === $url ) {
				return;
			}

			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			if ( 'reset_password' === $state ) {
				$url = add_query_arg(
					array(
						'key'   => sanitize_text_field( wp_unslash( $_GET['key'] ?? '' ) ),
						'login' => rawurlencode( sanitize_text_field( wp_unslash( $_GET['login'] ?? '' ) ) ),
					),
					$url
				);
			}

			if ( isset( $_GET['redirect_to'] ) && '' !== $_GET['redirect_to'] ) {
				$url = add_query_arg( 'redirect_to', rawurlencode( sanitize_text_field( wp_unslash( $_GET['redirect_to'] ) ) ), $url );
			}
			// phpcs:enable

			wp_safe_redirect( $url );
			$this->do_exit();
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Login Experience: intercept_login_page failed' );
		}
	}

	/**
	 * Action: wp_login_failed
	 * Redirects to custom login page with ?login=failed.
	 *
	 * @param string $username Username/email that failed auth.
	 */
	public function on_login_failed( string $username ): void {
		try {
			$id = $this->get_page_id( 'login' );
			if ( null === $id || 'publish' !== get_post_status( $id ) ) {
				return;
			}

			$url = get_permalink( $id );
			if ( false === $url || '' === $url ) {
				return;
			}

			wp_safe_redirect(
				add_query_arg(
					array(
						'login' => 'failed',
						'email' => rawurlencode( $username ),
					),
					$url
				)
			);
			$this->do_exit();
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Login Experience: on_login_failed redirect failed' );
		}
	}

	/**
	 * Terminate script execution after redirect.
	 * Extracted so tests can override without calling exit.
	 */
	protected function do_exit(): void {
		exit; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
