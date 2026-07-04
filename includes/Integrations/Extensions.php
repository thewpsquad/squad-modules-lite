<?php
/**
 * Extensions Integration Class
 *
 * Handles integration with the extensions system, listening to filters
 * and managing extension registration from various sources.
 *
 * @since   3.3.0
 * @author  The WP Squad <support@squadmodules.com>
 * @package DiviSquad\Integrations
 */

namespace DiviSquad\Integrations;

/**
 * Extensions Integration Class
 *
 * @since 3.3.0
 */
class Extensions {

	/**
	 * Flag to track if this integration is initialized
	 *
	 * @var bool
	 */
	private bool $initialized = false;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Initialize the integration
	 */
	public function init(): void {
		if ( $this->initialized ) {
			return;
		}

		// Register hooks
		$this->register_hooks();

		// Set initialized flag
		$this->initialized = true;
	}

	/**
	 * Register WordPress hooks
	 */
	protected function register_hooks(): void {
		add_filter( 'divi_squad_registered_extensions', array( $this, 'registered_extensions' ) );
	}

	/**
	 * Register core extensions
	 *
	 * @param array<int, array<string, mixed>> $existing_extensions Existing extensions data
	 *
	 * @return array<int, array<string, mixed>> Core extensions data
	 */
	public function registered_extensions( array $existing_extensions ): array {
		$extensions = array(
			array(
				'classes'            => array(
					'root_class' => \DiviSquad\Extensions\JSON::class,
				),
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
				'classes'            => array(
					'root_class' => \DiviSquad\Extensions\SVG::class,
				),
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
				'classes'            => array(
					'root_class' => \DiviSquad\Extensions\Font_Upload::class,
				),
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
				'classes'            => array(
					'root_class' => \DiviSquad\Extensions\Divi_Layout_Shortcode::class,
				),
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
				'classes'            => array(
					'root_class' => \DiviSquad\Extensions\Copy::class,
				),
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

		return array_merge( $existing_extensions, $extensions );
	}
}
