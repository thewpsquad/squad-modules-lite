<?php // phpcs:ignore WordPress.Files.FileName

/**
 * REST API Routes for the Admin Dashboard (v2)
 *
 * Provides aggregate statistics for the redesigned admin dashboard:
 * module/extension active+total counts, a by-category breakdown, the detected
 * Divi version with a compatibility flag, and (best-effort) assets-loaded KB.
 *
 * @since   3.5.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Rest_API_Routes\Version2;

use DiviSquad\Rest_API_Routes\Base_Route;
use DiviSquad\Utils\Divi as DiviUtil;
use Throwable;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Dashboard REST API v2 Route Handler.
 *
 * @since   3.5.0
 * @package DiviSquad
 */
class Dashboard extends Base_Route {

	/**
	 * Version identifier for API endpoints.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	protected string $version = 'v2';

	/**
	 * Minimum supported Divi version for compatibility reporting.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	protected string $min_divi_version = '4.14.0';

	/**
	 * Get available routes for the Dashboard API v2.
	 *
	 * @since 3.5.0
	 *
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	public function get_routes(): array {
		$routes = array(
			'/dashboard/stats' => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_stats' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
				),
			),
		);

		/**
		 * Filter the v2 dashboard routes.
		 *
		 * @since 3.5.0
		 *
		 * @param array $routes   The dashboard routes.
		 * @param self  $instance The current instance.
		 */
		return apply_filters( 'divi_squad_rest_v2_dashboard_routes', $routes, $this );
	}

	/**
	 * Build aggregate dashboard statistics.
	 *
	 * @since 3.5.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response Response containing dashboard stats.
	 */
	public function get_stats( WP_REST_Request $request ): WP_REST_Response {
		try {
			/**
			 * Action fired before building dashboard stats.
			 *
			 * @since 3.5.0
			 *
			 * @param WP_REST_Request $request The request object.
			 */
			do_action( 'divi_squad_rest_before_get_dashboard_stats', $request );

			$module_stats    = $this->build_feature_stats(
				divi_squad()->modules->get_registered_list(),
				array( divi_squad()->modules, 'is_module_active' )
			);
			$extension_stats = $this->build_feature_stats(
				divi_squad()->extensions->get_registered_list(),
				array( divi_squad()->extensions, 'is_extension_active' )
			);

			$divi_version = DiviUtil::get_builder_version();

			$stats = array(
				'modules'    => $module_stats,
				'extensions' => $extension_stats,
				'divi'       => array(
					'version'       => $divi_version,
					'is_compatible' => DiviUtil::meets_version_requirement( $this->min_divi_version ),
					'min_required'  => $this->min_divi_version,
				),
			);

			// Assets-loaded KB is best-effort. It is omitted unless a concrete
			// byte count is available; the dashboard placeholders the tile when absent.
			$assets_kb = $this->maybe_get_assets_kb();
			if ( null !== $assets_kb ) {
				$stats['assets_kb'] = $assets_kb;
			}

			/**
			 * Filter the dashboard stats payload.
			 *
			 * @since 3.5.0
			 *
			 * @param array           $stats    The stats payload.
			 * @param WP_REST_Request $request  The request object.
			 * @param self            $instance The current instance.
			 */
			$stats = apply_filters( 'divi_squad_rest_dashboard_stats', $stats, $request, $this );

			$response = rest_ensure_response( $stats );

			/**
			 * Action fired after building dashboard stats.
			 *
			 * @since 3.5.0
			 *
			 * @param WP_REST_Response $response The response object.
			 * @param array            $stats    The stats payload.
			 * @param WP_REST_Request  $request  The request object.
			 */
			do_action( 'divi_squad_rest_after_get_dashboard_stats', $response, $stats, $request );

			return $response;
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to build dashboard stats' );

			return new WP_REST_Response(
				array(
					'code'    => 'error',
					'message' => __( 'Failed to build dashboard stats.', 'squad-modules-for-divi' ),
				),
				500
			);
		}
	}

	/**
	 * Toggle Freemius anonymous usage tracking for this site.
	 *
	 * @since      3.5.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response
	 * @deprecated Canonical route is POST /v2/account/tracking in Account.php.
	 *             This alias forwards for backwards compatibility.
	 */
	public function set_tracking( WP_REST_Request $request ): WP_REST_Response {
		return ( new Account() )->set_tracking( $request );
	}

	/**
	 * Activate a Pro license key via Freemius.
	 *
	 * @since      3.5.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response
	 * @deprecated Canonical route is POST /v2/account/license/activate in Account.php.
	 *             This alias forwards for backwards compatibility.
	 */
	public function activate_license_key( WP_REST_Request $request ): WP_REST_Response {
		return ( new Account() )->activate_license( $request );
	}

	/**
	 * Build active/total + by-category stats for a feature collection.
	 *
	 * @since 3.5.0
	 *
	 * @param array<string, array<string, mixed>> $registered   Registered feature list.
	 * @param callable                            $is_active_cb Callback: ( string $name ) => bool.
	 *
	 * @return array<string, mixed>
	 */
	protected function build_feature_stats( array $registered, callable $is_active_cb ): array {
		$active      = 0;
		$by_category = array();

		foreach ( $registered as $feature ) {
			$name      = (string) ( $feature['name'] ?? '' );
			$raw_cat   = (string) ( $feature['category'] ?? 'uncategorized' );
			$category  = $this->normalize_category_id( $raw_cat );
			$cat_title = (string) ( $feature['category_title'] ?? $raw_cat );
			$is_active = '' !== $name && (bool) call_user_func( $is_active_cb, $name );

			if ( ! isset( $by_category[ $category ] ) ) {
				$by_category[ $category ] = array(
					'title'  => $cat_title,
					'active' => 0,
					'total'  => 0,
				);
			}

			++$by_category[ $category ]['total'];

			if ( $is_active ) {
				++$active;
				++$by_category[ $category ]['active'];
			}
		}

		return array(
			'active'      => $active,
			'total'       => count( $registered ),
			'by_category' => $by_category,
		);
	}

	/**
	 * Best-effort assets-loaded KB.
	 *
	 * Returns null when no concrete byte measurement is available. Per the design
	 * spec, the KB tile is placeholdered client-side when this is omitted, so we
	 * deliberately do not estimate a fabricated number here.
	 *
	 * @since 3.5.0
	 *
	 * @return float|null Kilobytes of currently-loaded squad admin assets, or null.
	 */
	protected function maybe_get_assets_kb(): ?float {
		/**
		 * Filter the assets-loaded KB figure for the dashboard.
		 *
		 * Return a float (kilobytes) to populate the "Assets loaded" tile, or null
		 * to leave it unset so the client renders a placeholder.
		 *
		 * @since 3.5.0
		 *
		 * @param float|null $assets_kb Kilobytes of loaded assets, or null.
		 * @param self       $instance  The current instance.
		 */
		return apply_filters( 'divi_squad_rest_dashboard_assets_kb', null, $this );
	}
}
