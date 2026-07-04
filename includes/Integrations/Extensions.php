<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Extensions Integration Class
 *
 * Handles integration with the extensions system, listening to filters
 * and managing extension registration from various sources.
 *
 * @since   3.3.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

/**
 * Extensions Integration Class
 *
 * @since   3.3.0
 * @package DiviSquad
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

		// Register hooks.
		$this->register_hooks();

		// Set initialized flag.
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
	 * @param array<int, array<string, mixed>> $existing_extensions Existing extensions data.
	 *
	 * @return array<int, array<string, mixed>> Core extensions data
	 */
	public function registered_extensions( array $existing_extensions ): array {
		$extensions = array(
			array(
				'classes'            => array(
					'root_class' => \DiviSquad\Extensions\WordPress\Copy::class,
				),
				'name'               => 'Copy',
				'label'              => esc_html__( 'Copy Post or Page', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'One-click post and page duplication — Multisite-ready.', 'squad-modules-for-divi' ),
				'release_version'    => '1.4.8',
				'last_modified'      => array( '1.4.8', '3.0.0' ),
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'category'           => 'enhancement',
				'category_title'     => esc_html__( 'Enhancement', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class' => \DiviSquad\Extensions\Visual_Builder\Layout_Shortcode::class,
				),
				'name'               => 'Divi_Layout_Shortcode',
				'label'              => esc_html__( 'Divi Library Shortcode', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Insert Divi Library layouts anywhere via shortcode.', 'squad-modules-for-divi' ),
				'release_version'    => '1.2.0',
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'category'           => 'enhancement',
				'category_title'     => esc_html__( 'Enhancement', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class' => \DiviSquad\Extensions\WordPress\Login_Experience::class,
				),
				'name'               => 'Login_Experience',
				'label'              => esc_html__( 'Custom Login Page', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Custom wp-login styling — logo, colors, and redirects.', 'squad-modules-for-divi' ),
				'release_version'    => '4.2.0',
				'is_default_active'  => false,
				'is_premium_feature' => false,
				'category'           => 'login-experience',
				'category_title'     => esc_html__( 'Login Experience', 'squad-modules-for-divi' ),
				'group'              => 'Login Experience',
				'is_new'             => true,
			),
			array(
				'name'               => 'Forgot_Password',
				'label'              => esc_html__( 'Forgot Password', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Lost-password screen branded to match your custom login.', 'squad-modules-for-divi' ),
				'release_version'    => '4.2.0',
				'is_default_active'  => false,
				'is_premium_feature' => false,
				'category'           => 'login-experience',
				'category_title'     => esc_html__( 'Login Experience', 'squad-modules-for-divi' ),
				'group'              => 'Login Experience',
				'is_new'             => true,
			),
			array(
				'name'               => 'Reset_Password',
				'label'              => esc_html__( 'Reset Password', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Password-reset form with strength meter and redirect.', 'squad-modules-for-divi' ),
				'release_version'    => '4.2.0',
				'is_default_active'  => false,
				'is_premium_feature' => false,
				'category'           => 'login-experience',
				'category_title'     => esc_html__( 'Login Experience', 'squad-modules-for-divi' ),
				'group'              => 'Login Experience',
				'is_new'             => true,
			),
			array(
				'classes'            => array(
					'root_class' => \DiviSquad\Extensions\Visual_Builder\Font_Upload::class,
				),
				'name'               => 'Font_Upload',
				'label'              => esc_html__( 'Font Upload', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Allow custom font files through the Media Uploader.', 'squad-modules-for-divi' ),
				'release_version'    => '1.2.0',
				'is_default_active'  => false,
				'is_premium_feature' => false,
				'category'           => 'media-upload',
				'category_title'     => esc_html__( 'Media Upload', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class' => \DiviSquad\Extensions\Visual_Builder\JSON::class,
				),
				'name'               => 'JSON',
				'label'              => esc_html__( 'JSON Upload', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Import/export Divi layouts and settings in JSON format.', 'squad-modules-for-divi' ),
				'release_version'    => '1.2.0',
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'category'           => 'media-upload',
				'category_title'     => esc_html__( 'Media Upload', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class' => \DiviSquad\Extensions\Visual_Builder\SVG::class,
				),
				'name'               => 'SVG',
				'label'              => esc_html__( 'SVG Upload', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Safely upload and serve scalable SVGs in WordPress.', 'squad-modules-for-divi' ),
				'release_version'    => '1.2.0',
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'category'           => 'media-upload',
				'category_title'     => esc_html__( 'Media Upload', 'squad-modules-for-divi' ),
			),
			array(
				'name'               => 'Popup_Maker',
				'label'              => esc_html__( 'Popup Maker', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Build Divi popups with custom triggers and display rules.', 'squad-modules-for-divi' ),
				'release_version'    => '5.0.0',
				'is_default_active'  => false,
				'is_premium_feature' => true,
				'category'           => 'pro-extensions',
				'category_title'     => esc_html__( 'Pro Extensions', 'squad-modules-for-divi' ),
				'group'              => 'Pro Extensions',
			),
			array(
				'name'               => 'Display_Conditions',
				'label'              => esc_html__( 'Display Conditions', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Show or hide elements by role, date, device, or cookie.', 'squad-modules-for-divi' ),
				'release_version'    => '5.0.0',
				'is_default_active'  => false,
				'is_premium_feature' => true,
				'category'           => 'pro-extensions',
				'category_title'     => esc_html__( 'Pro Extensions', 'squad-modules-for-divi' ),
				'group'              => 'Pro Extensions',
			),
			array(
				'name'               => 'Scheduled_Content',
				'label'              => esc_html__( 'Scheduled Content', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Schedule sections to appear & disappear automatically.', 'squad-modules-for-divi' ),
				'release_version'    => '5.0.0',
				'is_default_active'  => false,
				'is_premium_feature' => true,
				'category'           => 'pro-extensions',
				'category_title'     => esc_html__( 'Pro Extensions', 'squad-modules-for-divi' ),
				'group'              => 'Pro Extensions',
			),
		);

		return array_merge( $existing_extensions, $extensions );
	}
}
