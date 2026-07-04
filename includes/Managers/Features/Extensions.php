<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Extension Manager
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   1.0.0
 */

namespace DiviSquad\Managers\Features;

use DiviSquad\Base\Extension;
use DiviSquad\Base\Factories\SquadFeatures as ManagerBase;
use DiviSquad\Core\Memory;
use DiviSquad\Core\Supports\Polyfills\Arr;
use Throwable;
use function divi_squad;
use function esc_html__;

/**
 * Extension Manager class
 *
 * @package DiviSquad
 * @since   1.0.0
 */
class Extensions extends ManagerBase {

	/**
	 * Get available extensions.
	 *
	 * @return array[]
	 */
	public function get_registered_list() {
		$available_extensions = array(
			array(
				'classes'            => array( 'root_class' => \DiviSquad\Extensions\JSON::class ),
				'name'               => 'JSON',
				'label'              => esc_html__( 'JSON File Upload Support', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Enable this feature only if you would like allow JSON file through WordPress Media Uploader.', 'squad-modules-for-divi' ),
				'release_version'    => '1.2.0',
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'category'           => 'media-upload',
				'category_title'     => esc_html__( 'Media Upload', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array( 'root_class' => \DiviSquad\Extensions\SVG::class ),
				'name'               => 'SVG',
				'label'              => esc_html__( 'SVG Image Upload Support', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Enable this feature only if you would like allow svg file through WordPress Media Uploader.', 'squad-modules-for-divi' ),
				'release_version'    => '1.2.0',
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'category'           => 'media-upload',
				'category_title'     => esc_html__( 'Media Upload', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array( 'root_class' => \DiviSquad\Extensions\Font_Upload::class ),
				'name'               => 'Font_Upload',
				'label'              => esc_html__( 'Custom Fonts Upload Support', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Enable this feature only if you would like allow Font file through WordPress Media Uploader.', 'squad-modules-for-divi' ),
				'release_version'    => '1.2.0',
				'is_default_active'  => false,
				'is_premium_feature' => false,
				'category'           => 'media-upload',
				'category_title'     => esc_html__( 'Media Upload', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array( 'root_class' => \DiviSquad\Extensions\Divi_Layout_Shortcode::class ),
				'name'               => 'Divi_Layout_Shortcode',
				'label'              => esc_html__( 'Divi Library Shortcode', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Enable this feature only if you would like add Divi library shortcode feature.', 'squad-modules-for-divi' ),
				'release_version'    => '1.2.0',
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'category'           => 'enhancement',
				'category_title'     => esc_html__( 'Enhancement', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array( 'root_class' => \DiviSquad\Extensions\Copy::class ),
				'name'               => 'Copy',
				'label'              => esc_html__( 'Copy Post or Page', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Enable this feature only if you would like add Post or Page coping feature.', 'squad-modules-for-divi' ),
				'release_version'    => '1.4.8',
				'last_modified'      => array( '1.4.8', '3.0.0' ),
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'category'           => 'enhancement',
				'category_title'     => esc_html__( 'Enhancement', 'squad-modules-for-divi' ),
			),
		);

		return Arr::sort( $available_extensions, 'name' );
	}

	/**
	 * Get default active extensions.
	 *
	 * @return array
	 */
	public function get_default_registries(): array {
		return $this->get_filtered_registries(
			$this->get_registered_list(),
			function ( $extension ) {
				return $extension['is_default_active'];
			}
		);
	}

	/**
	 * Get inactive extensions.
	 *
	 * @return array
	 */
	public function get_inactive_registries(): array {
		return $this->get_filtered_registries(
			$this->get_registered_list(),
			function ( $extension ) {
				return ! $extension['is_default_active'];
			}
		);
	}

	/**
	 * Load enabled extensions
	 *
	 * @param string $path The defined directory.
	 *
	 * @return void
	 */
	public function load_extensions( string $path ) {
		if ( ! class_exists( Extension::class ) ) {
			return;
		}

		$this->load_extensions_files( $path, divi_squad()->memory );
	}

	/**
	 * Load enabled extensions
	 *
	 * @param string $path   The defined directory.
	 * @param Memory $memory The instance of Memory class.
	 *
	 * @return void
	 */
	protected function load_extensions_files( string $path, Memory $memory ) {
		try {
			// Retrieve total active extensions and current version from the memory.
			$current_version     = $memory->get( 'version' );
			$active_extensions   = $memory->get( 'active_extensions' );
			$inactive_extensions = $memory->get( 'inactive_extensions', array() );

			// Get all registered and default extensions.
			$features = $this->get_registered_list();
			$defaults = $this->get_default_registries();

			// Get verified active modules.
			$activated = $this->get_verified_registries( $features, $defaults, $active_extensions, $inactive_extensions, $current_version );

			foreach ( $activated as $extension ) {
				if ( isset( $extension['classes']['root_class'] ) ) {
					new $extension['classes']['root_class']();
				}
			}
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Extension loader' );
		}
	}
}
