<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Asset Localization Trait
 *
 * Handles script localization and data management.
 *
 * @since   3.3.0
 * @package DiviSquad
 */

namespace DiviSquad\Core\Traits\Assets;

use DiviSquad\Utils\Divi;

/**
 * Asset Localization Trait
 *
 * @since 3.3.0
 */
trait Localization {

	/**
	 * List of localized data for scripts
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $localized_data = array();

	/**
	 * Add localization data
	 *
	 * @param string               $object_name JavaScript object name.
	 * @param array<string, mixed> $data        Data to localize.
	 */
	public function add_localize_data( string $object_name, array $data ): void {
		$this->localized_data[ $object_name ] = $data;
	}

	/**
	 * Get localization data by object name
	 *
	 * @param string $object_name JavaScript object name.
	 *
	 * @return array<string, mixed>
	 */
	public function get_localize_data( string $object_name ): array {
		return $this->localized_data[ $object_name ] ?? array();
	}

	/**
	 * Output localized data
	 */
	protected function output_localized_data(): void {
		/**
		 * Fires before localized scripts are output.
		 *
		 * @since 3.3.0
		 *
		 * @param self $assets Current instance.
		 */
		do_action( 'divi_squad_before_localize_scripts', $this );

		foreach ( $this->localized_data as $object_name => $data ) {
			/**
			 * Filters the localized data for a specific object.
			 *
			 * @since 3.3.0
			 *
			 * @param array<string, mixed> $data        The localized data.
			 * @param string               $object_name The object name.
			 */
			$filtered_data = apply_filters( "divi_squad_localize_data_{$object_name}", $data, $object_name );

			printf(
				'<script type="text/javascript">window[%s] = %s;</script>',
				wp_json_encode( $object_name ),
				wp_json_encode( $filtered_data )
			);
		}

		$this->output_global_data();

		/**
		 * Fires after localized scripts are output.
		 *
		 * @since 3.3.0
		 *
		 * @param self $assets Current instance.
		 */
		do_action( 'divi_squad_after_localize_scripts', $this );
	}

	/**
	 * Output global data
	 */
	protected function output_global_data(): void {
		/**
		 * Filters whether to disable global localized data.
		 *
		 * @since 3.3.0
		 *
		 * @param bool $disable Whether to disable global localized data.
		 */
		if ( apply_filters( 'divi_squad_disable_global_localize_data', false ) ) {
			return;
		}

		$global_data = array(
			'nonce'   => wp_create_nonce( 'divi_squad_nonce' ),
			'config'  => array(
				'isAdmin'  => is_admin(),
				'siteType' => is_multisite() ? 'multi' : 'single',
				'siteId'   => get_current_blog_id(),
				'siteName' => get_bloginfo( 'name' ),
				'isDev'    => divi_squad()->is_dev(),
			),
			'version' => array(
				'core'        => divi_squad()->get_version_dot(),
				'builder'     => Divi::get_builder_version(),
				'builderType' => Divi::get_builder_mode(),
				'wordpress'   => get_bloginfo( 'version' ),
			),
			'urls'    => array(
				'site_url'   => esc_url( home_url() ),
				'admin_url'  => esc_url( admin_url() ),
				'rest_url'   => esc_url( rest_url() ),
				'ajax_url'   => esc_url( admin_url( 'admin-ajax.php' ) ),
				'assets_url' => esc_url( divi_squad()->get_asset_url() ),
			),
		);

		/**
		 * Filters the global localized data.
		 *
		 * @since 3.3.0
		 *
		 * @param array<string, mixed> $global_data The global localized data.
		 */
		$filtered_data = apply_filters( 'divi_squad_global_localize_data', $global_data );

		printf(
			'<script id="divi-squad-global-localize-data" type="text/javascript">window.DiviSquadExtra = %s;</script>',
			wp_json_encode( $filtered_data )
		);
	}

	/**
	 * Remove localization data
	 *
	 * @param string $object_name JavaScript object name.
	 */
	protected function remove_localize_data( string $object_name ): bool {
		if ( ! isset( $this->localized_data[ $object_name ] ) ) {
			return false;
		}

		unset( $this->localized_data[ $object_name ] );

		/**
		 * Fires after localized data is removed.
		 *
		 * @since 3.3.0
		 *
		 * @param string $object_name The object name.
		 */
		do_action( 'divi_squad_localize_data_removed', $object_name );

		return true;
	}
}
