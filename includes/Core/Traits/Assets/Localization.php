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
	 *
	 * @return bool Whether the data was added
	 */
	public function add_localize_data( string $object_name, array $data ): bool {
		/**
		 * Filters the object name before adding localization data.
		 *
		 * @since 3.4.0
		 *
		 * @param string $object_name JavaScript object name.
		 * @param array  $data        Data to localize.
		 */
		$object_name = (string) apply_filters( 'divi_squad_localize_object_name', $object_name, $data );

		/**
		 * Filters the data before adding localization.
		 *
		 * @since 3.4.0
		 *
		 * @param array  $data        Data to localize.
		 * @param string $object_name JavaScript object name.
		 */
		$data = (array) apply_filters( 'divi_squad_pre_localize_data', $data, $object_name );

		/**
		 * Filters the data specifically for the given object name before adding localization.
		 *
		 * @since 3.4.0
		 *
		 * @param array  $data        Data to localize.
		 * @param string $object_name JavaScript object name.
		 */
		$data = (array) apply_filters( "divi_squad_pre_localize_data_{$object_name}", $data, $object_name );

		/**
		 * Filters whether the localization data should be added.
		 *
		 * @since 3.4.0
		 *
		 * @param bool   $should_add  Whether to add the localization data.
		 * @param string $object_name JavaScript object name.
		 * @param array  $data        Data to localize.
		 */
		$should_add = apply_filters( 'divi_squad_should_add_localize_data', true, $object_name, $data );

		if ( ! $should_add ) {
			return false;
		}

		/**
		 * Fires before localization data is added.
		 *
		 * @since 3.4.0
		 *
		 * @param string $object_name    JavaScript object name.
		 * @param array  $data           Data to localize.
		 * @param array  $localized_data Current localized data.
		 */
		do_action( 'divi_squad_before_add_localize_data', $object_name, $data, $this->localized_data );

		$this->localized_data[ $object_name ] = $data;

		/**
		 * Fires after localization data is added.
		 *
		 * @since 3.4.0
		 *
		 * @param string $object_name    JavaScript object name.
		 * @param array  $data           Data that was localized.
		 * @param array  $localized_data Current localized data after addition.
		 */
		do_action( 'divi_squad_after_add_localize_data', $object_name, $data, $this->localized_data );

		return true;
	}

	/**
	 * Get localization data by object name
	 *
	 * @param string $object_name JavaScript object name.
	 *
	 * @return array<string, mixed>
	 */
	public function get_localize_data( string $object_name ): array {
		/**
		 * Filters the object name before retrieving localization data.
		 *
		 * @since 3.4.0
		 *
		 * @param string $object_name JavaScript object name.
		 */
		$object_name = (string) apply_filters( 'divi_squad_get_localize_object_name', $object_name );

		$data = $this->localized_data[ $object_name ] ?? array();

		/**
		 * Filters the retrieved localization data.
		 *
		 * @since 3.4.0
		 *
		 * @param array  $data        The localization data.
		 * @param string $object_name JavaScript object name.
		 */
		return apply_filters( 'divi_squad_get_localize_data', $data, $object_name );
	}

	/**
	 * Update existing localization data for an object
	 *
	 * @param string               $object_name JavaScript object name.
	 * @param array<string, mixed> $data        Data to merge with existing data.
	 *
	 * @return bool Whether the data was updated
	 */
	public function update_localize_data( string $object_name, array $data ): bool {
		/**
		 * Filters the object name before updating localization data.
		 *
		 * @since 3.4.0
		 *
		 * @param string $object_name JavaScript object name.
		 * @param array  $data        Data to update.
		 */
		$object_name = (string) apply_filters( 'divi_squad_update_localize_object_name', $object_name, $data );

		if ( ! isset( $this->localized_data[ $object_name ] ) ) {
			/**
			 * Fires when attempting to update non-existent localization data.
			 *
			 * @since 3.4.0
			 *
			 * @param string $object_name JavaScript object name.
			 * @param array  $data        Data that would be updated.
			 */
			do_action( 'divi_squad_update_nonexistent_localize_data', $object_name, $data );

			return false;
		}

		$existing_data = $this->localized_data[ $object_name ];

		/**
		 * Filters the data before updating localization.
		 *
		 * @since 3.4.0
		 *
		 * @param array  $data          Data to update.
		 * @param string $object_name   JavaScript object name.
		 * @param array  $existing_data Existing data for the object.
		 */
		$data = (array) apply_filters( 'divi_squad_pre_update_localize_data', $data, $object_name, $existing_data );

		/**
		 * Filters the data specifically for the given object name before updating.
		 *
		 * @since 3.4.0
		 *
		 * @param array  $data          Data to update.
		 * @param string $object_name   JavaScript object name.
		 * @param array  $existing_data Existing data for the object.
		 */
		$data = (array) apply_filters( "divi_squad_pre_update_localize_data_{$object_name}", $data, $object_name, $existing_data );

		/**
		 * Fires before localization data is updated.
		 *
		 * @since 3.4.0
		 *
		 * @param string $object_name   JavaScript object name.
		 * @param array  $data          New data to be merged.
		 * @param array  $existing_data Existing data before update.
		 */
		do_action( 'divi_squad_before_update_localize_data', $object_name, $data, $existing_data );

		$merged_data = array_merge( $existing_data, $data );

		/**
		 * Filters the merged data during update.
		 *
		 * @since 3.4.0
		 *
		 * @param array  $merged_data   The merged data result.
		 * @param array  $existing_data Original existing data.
		 * @param array  $data          New data being merged in.
		 * @param string $object_name   JavaScript object name.
		 */
		$merged_data = apply_filters( 'divi_squad_merged_localize_data', $merged_data, $existing_data, $data, $object_name );

		$this->localized_data[ $object_name ] = $merged_data;

		/**
		 * Fires after localization data is updated.
		 *
		 * @since 3.4.0
		 *
		 * @param string $object_name   JavaScript object name.
		 * @param array  $merged_data   The merged data result.
		 * @param array  $existing_data Original existing data.
		 * @param array  $data          New data that was merged in.
		 */
		do_action( 'divi_squad_after_update_localize_data', $object_name, $merged_data, $existing_data, $data );

		return true;
	}

	/**
	 * Output localized data
	 */
	protected function output_localized_data(): void {
		/**
		 * Filters the localized data before output.
		 *
		 * @since 3.4.0
		 *
		 * @param array $localized_data The complete localized data.
		 * @param self  $instance       Current instance.
		 */
		$this->localized_data = apply_filters( 'divi_squad_localized_data_before_output', $this->localized_data, $this );

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
			 * Filters whether to output the localized data for a specific object.
			 *
			 * @since 3.4.0
			 *
			 * @param bool   $output      Whether to output.
			 * @param string $object_name The object name.
			 * @param array  $data        The localized data.
			 */
			$should_output = apply_filters( 'divi_squad_should_output_localize_data', true, $object_name, $data );

			/**
			 * Filters whether to output the localized data for a specific object (object-specific filter).
			 *
			 * @since 3.4.0
			 *
			 * @param bool   $output      Whether to output.
			 * @param string $object_name The object name.
			 * @param array  $data        The localized data.
			 */
			$should_output = apply_filters( "divi_squad_should_output_localize_data_{$object_name}", $should_output, $object_name, $data );

			if ( ! $should_output ) {
				continue;
			}

			/**
			 * Fires before specific object's localized data is output.
			 *
			 * @since 3.4.0
			 *
			 * @param string $object_name The object name.
			 * @param array  $data        The localized data.
			 */
			do_action( 'divi_squad_before_output_specific_localize_data', $object_name, $data );

			/**
			 * Filters the localized data for a specific object.
			 *
			 * @since 3.3.0
			 *
			 * @param array<string, mixed> $data        The localized data.
			 * @param string               $object_name The object name.
			 */
			$filtered_data = apply_filters( "divi_squad_localize_data_{$object_name}", $data, $object_name );

			/**
			 * Filters the JSON-encoded object name for output.
			 *
			 * @since 3.4.0
			 *
			 * @param string $encoded_name JSON-encoded object name.
			 * @param string $object_name  Original object name.
			 */
			$encoded_name = apply_filters( 'divi_squad_encoded_localize_object_name', wp_json_encode( $object_name ), $object_name );

			/**
			 * Filters the JSON-encoded data for output.
			 *
			 * @since 3.4.0
			 *
			 * @param string $encoded_data  JSON-encoded data.
			 * @param array  $filtered_data Original filtered data.
			 * @param string $object_name   Object name.
			 */
			$encoded_data = apply_filters( 'divi_squad_encoded_localize_data', wp_json_encode( $filtered_data ), $filtered_data, $object_name );

			/**
			 * Filters the complete script tag for localized data.
			 *
			 * @since 3.4.0
			 *
			 * @param string $script       The complete script tag.
			 * @param string $encoded_name JSON-encoded object name.
			 * @param string $encoded_data JSON-encoded data.
			 * @param string $object_name  Original object name.
			 */
			$script = apply_filters(
				'divi_squad_localize_script_tag',
				sprintf(
					'<script type="text/javascript">window[%s] = %s;</script>',
					$encoded_name,
					$encoded_data
				),
				$encoded_name,
				$encoded_data,
				$object_name
			);

			echo $script; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			/**
			 * Fires after specific object's localized data is output.
			 *
			 * @since 3.4.0
			 *
			 * @param string $object_name   The object name.
			 * @param array  $filtered_data The localized data that was output.
			 */
			do_action( 'divi_squad_after_output_specific_localize_data', $object_name, $filtered_data );
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

		/**
		 * Fires before global data is prepared.
		 *
		 * @since 3.4.0
		 */
		do_action( 'divi_squad_before_prepare_global_data' );

		$nonce = wp_create_nonce( 'divi_squad_nonce' );

		/**
		 * Filters the nonce used in global data.
		 *
		 * @since 3.4.0
		 *
		 * @param string $nonce The nonce.
		 */
		$nonce = apply_filters( 'divi_squad_global_data_nonce', $nonce );

		$config = array(
			'isAdmin'  => is_admin(),
			'siteType' => is_multisite() ? 'multi' : 'single',
			'siteId'   => get_current_blog_id(),
			'siteName' => get_bloginfo( 'name' ),
			'isDev'    => divi_squad()->is_dev(),
		);

		/**
		 * Filters the config section of global data.
		 *
		 * @since 3.4.0
		 *
		 * @param array $config The config data.
		 */
		$config = apply_filters( 'divi_squad_global_data_config', $config );

		$version = array(
			'core'        => divi_squad()->get_version_dot(),
			'builder'     => Divi::get_builder_version(),
			'builderType' => Divi::get_builder_mode(),
			'wordpress'   => get_bloginfo( 'version' ),
		);

		/**
		 * Filters the version section of global data.
		 *
		 * @since 3.4.0
		 *
		 * @param array $version The version data.
		 */
		$version = apply_filters( 'divi_squad_global_data_version', $version );

		$urls = array(
			'site_url'   => esc_url( home_url() ),
			'admin_url'  => esc_url( admin_url() ),
			'rest_url'   => esc_url( rest_url() ),
			'ajax_url'   => esc_url( admin_url( 'admin-ajax.php' ) ),
			'assets_url' => esc_url( divi_squad()->get_asset_url() ),
		);

		/**
		 * Filters the URLs section of global data.
		 *
		 * @since 3.4.0
		 *
		 * @param array $urls The URL data.
		 */
		$urls = apply_filters( 'divi_squad_global_data_urls', $urls );

		$global_data = array(
			'nonce'   => $nonce,
			'config'  => $config,
			'version' => $version,
			'urls'    => $urls,
		);

		/**
		 * Fires after global data is prepared but before filtering.
		 *
		 * @since 3.4.0
		 *
		 * @param array $global_data The global data before filtering.
		 */
		do_action( 'divi_squad_after_prepare_global_data', $global_data );

		/**
		 * Filters the global localized data.
		 *
		 * @since 3.3.0
		 *
		 * @param array<string, mixed> $global_data The global localized data.
		 */
		$filtered_data = apply_filters( 'divi_squad_global_localize_data', $global_data );

		/**
		 * Filters the global data object name.
		 *
		 * @since 3.4.0
		 *
		 * @param string $object_name The global data object name.
		 */
		$global_object_name = apply_filters( 'divi_squad_global_data_object_name', 'DiviSquadExtra' );

		/**
		 * Filters the script ID for global data.
		 *
		 * @since 3.4.0
		 *
		 * @param string $script_id The script ID.
		 */
		$script_id = apply_filters( 'divi_squad_global_data_script_id', 'divi-squad-global-localize-data' );

		/**
		 * Fires before global data is output.
		 *
		 * @since 3.4.0
		 *
		 * @param array  $filtered_data      The filtered global data.
		 * @param string $global_object_name The global object name.
		 */
		do_action( 'divi_squad_before_output_global_data', $filtered_data, $global_object_name );

		/**
		 * Filters the complete script tag for global localized data.
		 *
		 * @since 3.4.0
		 *
		 * @param string $script             The complete script tag.
		 * @param string $script_id          The script ID.
		 * @param string $global_object_name The global object name.
		 * @param array  $filtered_data      The filtered global data.
		 */
		$script = apply_filters(
			'divi_squad_global_localize_script_tag',
			sprintf(
				'<script id="%s" type="text/javascript">window["%s"] = %s;</script>',
				esc_attr( $script_id ),
				esc_js( $global_object_name ),
				wp_json_encode( $filtered_data )
			),
			$script_id,
			$global_object_name,
			$filtered_data
		);

		echo $script; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		/**
		 * Fires after global data is output.
		 *
		 * @since 3.4.0
		 *
		 * @param array  $filtered_data      The filtered global data that was output.
		 * @param string $global_object_name The global object name.
		 */
		do_action( 'divi_squad_after_output_global_data', $filtered_data, $global_object_name );
	}

	/**
	 * Remove localization data
	 *
	 * @param string $object_name JavaScript object name.
	 */
	protected function remove_localize_data( string $object_name ): bool {
		/**
		 * Filters the object name before removing localization data.
		 *
		 * @since 3.4.0
		 *
		 * @param string $object_name JavaScript object name.
		 */
		$object_name = (string) apply_filters( 'divi_squad_remove_localize_object_name', $object_name );

		if ( ! isset( $this->localized_data[ $object_name ] ) ) {
			/**
			 * Fires when attempting to remove non-existent localization data.
			 *
			 * @since 3.4.0
			 *
			 * @param string $object_name JavaScript object name.
			 */
			do_action( 'divi_squad_remove_nonexistent_localize_data', $object_name );

			return false;
		}

		/**
		 * Filters whether the localization data should be removed.
		 *
		 * @since 3.4.0
		 *
		 * @param bool   $should_remove Whether to remove the localization data.
		 * @param string $object_name   JavaScript object name.
		 * @param array  $data          The data to be removed.
		 */
		$should_remove = apply_filters( 'divi_squad_should_remove_localize_data', true, $object_name, $this->localized_data[ $object_name ] );

		if ( ! $should_remove ) {
			return false;
		}

		$data = $this->localized_data[ $object_name ];

		/**
		 * Fires before localization data is removed.
		 *
		 * @since 3.4.0
		 *
		 * @param string $object_name    JavaScript object name.
		 * @param array  $data           The data being removed.
		 * @param array  $localized_data All localized data before removal.
		 */
		do_action( 'divi_squad_before_remove_localize_data', $object_name, $data, $this->localized_data );

		unset( $this->localized_data[ $object_name ] );

		/**
		 * Fires after localized data is removed.
		 *
		 * @since 3.3.0
		 *
		 * @param string $object_name    JavaScript object name.
		 * @param array  $data           The data that was removed.
		 * @param array  $localized_data All localized data after removal.
		 */
		do_action( 'divi_squad_localize_data_removed', $object_name, $data, $this->localized_data );

		return true;
	}

	/**
	 * Check if localization data exists for an object
	 *
	 * @param string $object_name JavaScript object name.
	 *
	 * @return bool Whether localization data exists
	 */
	public function has_localize_data( string $object_name ): bool {
		/**
		 * Filters the object name before checking for localization data.
		 *
		 * @since 3.4.0
		 *
		 * @param string $object_name JavaScript object name.
		 */
		$object_name = (string) apply_filters( 'divi_squad_check_localize_object_name', $object_name );

		$exists = isset( $this->localized_data[ $object_name ] );

		/**
		 * Filters whether localization data exists for an object.
		 *
		 * @since 3.4.0
		 *
		 * @param bool   $exists      Whether the data exists.
		 * @param string $object_name JavaScript object name.
		 */
		return apply_filters( 'divi_squad_has_localize_data', $exists, $object_name );
	}

	/**
	 * Get all localization data
	 *
	 * @return array<string, array<string, mixed>> All localization data
	 */
	public function get_all_localize_data(): array {
		/**
		 * Filters all localization data when retrieved directly.
		 *
		 * @since 3.4.0
		 *
		 * @param array $localized_data All localization data.
		 */
		return apply_filters( 'divi_squad_get_all_localize_data', $this->localized_data );
	}
}
